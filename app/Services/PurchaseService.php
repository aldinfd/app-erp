<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\VendorInvoice;
use App\Models\VendorPayment;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alur pembelian Phase 5: PO draft → ordered → received → paid
 * (plan.md Phase 5.2).
 *
 * Setiap transisi yang menyentuh stok + jurnal dijalankan dalam SATU DB
 * transaction (schema-database.md §10.7) — kegagalan di salah satu langkah
 * (mis. mapping jurnal belum di-seed) mengembalikan semuanya.
 */
class PurchaseService
{
    public function __construct(
        private readonly JournalService $journalService,
        private readonly StockService $stockService,
    ) {}

    /**
     * Buat PO draft + item (snapshot harga beli dari input staff).
     *
     * @param  array{vendor_id: int, order_date: string, expected_date?: string|null, tax: float|int|string, notes?: string|null}  $data  sudah tervalidasi controller
     * @param  array<int, array{product_id: int|string, qty: float|int|string, unit_cost: float|int|string}>  $items  sudah tervalidasi controller (numeric)
     *
     * @throws ValidationException bila produk tidak ada atau qty pecahan untuk satuan bulat
     */
    public function create(array $data, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $items): PurchaseOrder {
            [$lines, $errors] = $this->buildLines($items);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $subtotal = round(array_sum(array_column($lines, 'subtotal')), 2);
            $tax = round((float) $data['tax'], 2);

            $order = PurchaseOrder::create([
                'po_number' => NumberGenerator::next('PO', 'purchase_orders', 'po_number'),
                'vendor_id' => (int) $data['vendor_id'],
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'grand_total' => round($subtotal + $tax, 2),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'qty' => $line['qty'],
                    'unit_cost' => $line['unit_cost'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            return $order;
        });
    }

    /**
     * Transisi draft → ordered (PO dikirim ke vendor).
     *
     * @return bool false bila status PO bukan draft (mis. sudah pernah diproses)
     */
    public function markOrdered(PurchaseOrder $order): bool
    {
        $changed = DB::transaction(function () use ($order): bool {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->status !== PurchaseOrder::STATUS_DRAFT) {
                return false;
            }

            $locked->update(['status' => PurchaseOrder::STATUS_ORDERED]);

            return true;
        });

        if ($changed) {
            $this->notifyRoles(
                ['admin', 'staff_gudang'],
                "PO {$order->po_number} dipesan",
                "PO ke vendor {$order->vendor->name} menunggu penerimaan barang.",
            );
        }

        return $changed;
    }

    /**
     * Transisi ordered → received: tambah stok tiap item via StockService
     * (type in, reference purchase_order) + auto-jurnal purchase_received —
     * semuanya dalam satu transaction.
     *
     * @return bool false bila status PO bukan ordered (harus lewat markOrdered dulu)
     */
    public function receive(PurchaseOrder $order, ?User $user = null): bool
    {
        $changed = DB::transaction(function () use ($order, $user): bool {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->status !== PurchaseOrder::STATUS_ORDERED) {
                return false;
            }

            $locked->load('items.product', 'vendor');

            foreach ($locked->items as $item) {
                $this->stockService->add(
                    $item->product,
                    (float) $item->qty,
                    'purchase_order',
                    $locked->id,
                    "Pembelian {$locked->po_number}",
                    $user,
                );
            }

            $locked->update(['status' => PurchaseOrder::STATUS_RECEIVED]);

            $this->journalService->postPurchaseReceived($locked, $user?->id);

            return true;
        });

        if ($changed) {
            $this->notifyRoles(
                ['admin', 'staff_finance'],
                "PO {$order->po_number} diterima",
                'Barang sudah masuk gudang. Catat invoice vendor dan lakukan pembayaran.',
            );
        }

        return $changed;
    }

    /**
     * Batalkan PO yang belum diterima (draft/ordered). PO received tidak
     * bisa dibatalkan — stok sudah masuk & hutang sudah dijurnal; koreksi
     * lewat jurnal reversal (Phase 6).
     *
     * @return bool false bila status PO sudah received/paid/cancelled
     */
    public function cancel(PurchaseOrder $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            if (! in_array($locked->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_ORDERED], true)) {
                return false;
            }

            $locked->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

            return true;
        });
    }

    /**
     * Catat invoice vendor untuk PO received (1 PO maksimal 1 invoice —
     * UNIQUE purchase_order_id, schema-database.md §6.3).
     *
     * @param  array{vendor_invoice_number: string, invoice_date: string, due_date?: string|null, amount: float|int|string}  $data  sudah tervalidasi controller
     *
     * @throws ValidationException bila PO belum received atau sudah punya invoice
     */
    public function recordVendorInvoice(PurchaseOrder $order, array $data): VendorInvoice
    {
        return DB::transaction(function () use ($order, $data): VendorInvoice {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->status !== PurchaseOrder::STATUS_RECEIVED) {
                throw ValidationException::withMessages([
                    'vendor_invoice_number' => 'Invoice vendor hanya bisa dicatat untuk PO berstatus received.',
                ]);
            }

            if ($locked->invoice()->exists()) {
                throw ValidationException::withMessages([
                    'vendor_invoice_number' => "PO {$locked->po_number} sudah punya invoice vendor.",
                ]);
            }

            return VendorInvoice::create([
                'vendor_invoice_number' => $data['vendor_invoice_number'],
                'purchase_order_id' => $locked->id,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'amount' => round((float) $data['amount'], 2),
                'amount_paid' => 0,
                'status' => VendorInvoice::STATUS_UNPAID,
            ]);
        });
    }

    /**
     * Bayar invoice vendor: catat vendor_payment, update amount_paid/status
     * invoice (unpaid → partial → paid), lunas → PO paid, lalu auto-jurnal
     * purchase_payment — dalam satu transaction. Pembayaran melebihi sisa
     * ditolak (schema-database.md §10.6).
     *
     * @param  array{amount: float|int|string, method: string, reference_no?: string|null, paid_at: string, notes?: string|null}  $data  sudah tervalidasi controller
     *
     * @throws ValidationException bila invoice sudah lunas/void atau pembayaran melebihi sisa
     */
    public function pay(VendorInvoice $invoice, array $data, ?User $user = null): VendorPayment
    {
        return DB::transaction(function () use ($invoice, $data, $user): VendorPayment {
            $lockedInvoice = VendorInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if (in_array($lockedInvoice->status, [VendorInvoice::STATUS_PAID, VendorInvoice::STATUS_VOID], true)) {
                throw ValidationException::withMessages([
                    'amount' => 'Invoice vendor sudah lunas atau void.',
                ]);
            }

            $amount = round((float) $data['amount'], 2);
            $newPaid = round((float) $lockedInvoice->amount_paid + $amount, 2);

            if ($newPaid > (float) $lockedInvoice->amount + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Pembayaran melebihi sisa invoice (maksimal Rp %s).',
                        number_format((float) $lockedInvoice->amount - (float) $lockedInvoice->amount_paid, 2, ',', '.'),
                    ),
                ]);
            }

            $payment = VendorPayment::create([
                'vendor_invoice_id' => $lockedInvoice->id,
                'amount' => $amount,
                'method' => $data['method'],
                'reference_no' => $data['reference_no'] ?? null,
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            $isPaid = $newPaid >= (float) $lockedInvoice->amount;
            $lockedInvoice->update([
                'amount_paid' => $newPaid,
                'status' => $isPaid ? VendorInvoice::STATUS_PAID : VendorInvoice::STATUS_PARTIAL,
            ]);

            if ($isPaid) {
                PurchaseOrder::query()
                    ->whereKey($lockedInvoice->purchase_order_id)
                    ->update(['status' => PurchaseOrder::STATUS_PAID]);
            }

            $this->journalService->postPurchasePayment($payment, $user?->id);

            return $payment;
        });
    }

    /**
     * Validasi tiap item terhadap produk terkini + hitung subtotal memakai
     * harga beli dari input (snapshot disimpan di item). Produk nonaktif tetap
     * boleh dibeli (restock); stok tidak dicek karena pembelian menambah stok.
     *
     * @param  array<int, array{product_id: int|string, qty: float|int|string, unit_cost: float|int|string}>  $items
     * @return array{0: array<int, array{product: Product, qty: float, unit_cost: float, subtotal: float}>, 1: array<string, string>}
     */
    private function buildLines(array $items): array
    {
        $products = Product::query()
            ->with('unit:id,abbreviation,allows_fraction')
            ->whereIn('id', array_map(fn ($item) => (int) $item['product_id'], $items))
            ->get()
            ->keyBy('id');

        $lines = [];
        $errors = [];

        foreach ($items as $i => $item) {
            $product = $products->get((int) $item['product_id']);
            $qty = round((float) $item['qty'], 2);
            $unitCost = round((float) $item['unit_cost'], 2);

            if ($product === null) {
                $errors["items.{$i}.product_id"] = 'Produk tidak ditemukan.';

                continue;
            }

            if ($qty <= 0) {
                $errors["items.{$i}.qty"] = "Jumlah untuk {$product->name} harus lebih dari 0.";

                continue;
            }

            if (! $product->unit?->allows_fraction && floor($qty) !== $qty) {
                $errors["items.{$i}.qty"] = "Jumlah untuk {$product->name} harus bilangan bulat (satuan {$product->unit->abbreviation}).";

                continue;
            }

            if ($unitCost < 0) {
                $errors["items.{$i}.unit_cost"] = "Harga beli untuk {$product->name} tidak boleh negatif.";

                continue;
            }

            $lines[] = [
                'product' => $product,
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'subtotal' => round($qty * $unitCost, 2),
            ];
        }

        return [$lines, $errors];
    }

    /**
     * Kirim notifikasi in-app + email setelah transaction commit.
     *
     * @param  list<string>  $roles
     */
    private function notifyRoles(array $roles, string $title, string $body): void
    {
        User::role($roles)->get()->each(
            fn (User $user) => $user->notify(new SystemNotification($title, $body)),
        );
    }
}
