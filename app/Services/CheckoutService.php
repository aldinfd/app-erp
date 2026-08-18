<?php

namespace App\Services;

use App\Events\SalesOrderCreated;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Checkout guest storefront: buat Sales Order confirmed + Invoice unpaid +
 * Payment pending Midtrans dalam SATU DB transaction (schema-database.md
 * §10.7 — aksi multi-tabel harus atomic).
 *
 * Harga selalu diambil dari DB (selling_price), tidak pernah dari client —
 * client hanya mengirim product_id + qty. Cek stok di sini bersifat advisory
 * (tanpa lock); pengurangan stok otoritatif terjadi saat webhook pembayaran.
 */
class CheckoutService
{
    /**
     * @param  array{name: string, email: string, phone: string, address: string}  $customerData  sudah tervalidasi controller
     * @param  array<int, array{product_id: int|string, qty: float|int|string}>  $items  sudah tervalidasi controller (numeric > 0)
     *
     * @throws ValidationException bila produk tidak ada/tidak aktif, qty pecahan
     *                             untuk satuan bulat, atau stok tidak cukup
     */
    public function createOrder(array $customerData, array $items): SalesOrder
    {
        $order = DB::transaction(function () use ($customerData, $items): SalesOrder {
            [$lines, $errors] = $this->buildLines($items);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            // Guest find-or-create by email (schema customers tanpa UNIQUE email —
            // dedup di level aplikasi). Customer lama dipakai apa adanya.
            $customer = Customer::query()->where('email', $customerData['email'])->first()
                ?? Customer::create($customerData);

            $subtotal = round(array_sum(array_column($lines, 'subtotal')), 2);

            $order = SalesOrder::create([
                'order_number' => NumberGenerator::next('SO', 'sales_orders', 'order_number'),
                'customer_id' => $customer->id,
                'order_date' => now()->toDateString(),
                'status' => SalesOrder::STATUS_CONFIRMED,
                'subtotal' => $subtotal,
                'tax' => 0,
                'shipping' => 0,
                'grand_total' => $subtotal,
            ]);

            foreach ($lines as $line) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            $invoice = Invoice::create([
                'invoice_number' => NumberGenerator::next('INV', 'invoices', 'invoice_number'),
                'sales_order_id' => $order->id,
                'issued_date' => today(),
                'amount' => $subtotal,
                'amount_paid' => 0,
                'status' => Invoice::STATUS_UNPAID,
            ]);

            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $subtotal,
                'method' => Payment::METHOD_MIDTRANS,
                'gateway' => 'midtrans',
                'gateway_ref' => $order->order_number,
                'status' => Payment::STATUS_PENDING,
            ]);

            return $order;
        });

        // Event setelah commit — notifikasi tidak terkirim untuk order yang rollback.
        SalesOrderCreated::dispatch($order);

        return $order;
    }

    /**
     * Validasi tiap item terhadap data produk terkini + hitung subtotal
     * memakai harga jual dari DB (snapshot harga disimpan di item).
     *
     * @param  array<int, array{product_id: int|string, qty: float|int|string}>  $items
     * @return array{0: array<int, array{product: Product, qty: float, unit_price: float, subtotal: float}>, 1: array<string, string>}
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

            if ($product === null || ! $product->is_active) {
                $errors["items.{$i}.product_id"] = 'Produk tidak ditemukan atau tidak aktif.';

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

            if ((float) $product->stock_qty < $qty) {
                $errors["items.{$i}.qty"] = sprintf(
                    'Stok %s tidak cukup (tersisa %s %s).',
                    $product->name,
                    rtrim(rtrim(number_format((float) $product->stock_qty, 2, '.', ''), '0'), '.'),
                    $product->unit->abbreviation,
                );

                continue;
            }

            $unitPrice = (float) $product->selling_price;

            $lines[] = [
                'product' => $product,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => round($qty * $unitPrice, 2),
            ];
        }

        return [$lines, $errors];
    }
}
