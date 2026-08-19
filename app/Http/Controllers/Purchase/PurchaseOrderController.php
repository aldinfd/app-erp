<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

    /**
     * Daftar PO: search nomor PO / nama vendor + filter status.
     */
    public function index(Request $request): Response
    {
        $q = $request->query('q');
        $status = $request->query('status');

        $purchaseOrders = PurchaseOrder::query()
            ->with('vendor:id,name')
            ->when($q, fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('po_number', 'like', "%{$q}%")
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', "%{$q}%"));
            }))
            ->when(in_array($status, PurchaseOrder::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('purchase-orders/index', [
            'purchaseOrders' => $purchaseOrders,
            'statuses' => PurchaseOrder::STATUSES,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    /**
     * Form PO baru: vendor aktif + produk aktif (harga beli terakhir jadi
     * default unit_cost, staff boleh ubah sesuai kesepakatan vendor).
     */
    public function create(): Response
    {
        return Inertia::render('purchase-orders/create', [
            'vendors' => Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()
                ->where('is_active', true)
                ->with('unit:id,abbreviation,allows_fraction')
                ->orderBy('name')
                ->get(['id', 'sku', 'name', 'cost_price', 'unit_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $order = $this->purchaseService->create(
            [...$validated, 'tax' => $validated['tax'] ?? 0],
            $validated['items'],
        );

        return redirect()
            ->route('purchase-orders.show', $order)
            ->with('success', "PO {$order->po_number} berhasil dibuat (draft).");
    }

    /**
     * Detail PO: vendor, item (harga snapshot), invoice vendor + riwayat
     * pembayaran, beserta flag aksi sesuai status & role user.
     */
    public function show(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load([
            'vendor:id,name,email,phone,address',
            'items.product:id,sku,name,unit_id',
            'items.product.unit:id,abbreviation,allows_fraction',
            'invoice.payments' => fn ($query) => $query->orderByDesc('id'),
        ]);

        $isWarehouse = $request->user()->hasAnyRole(['admin', 'staff_gudang']);
        $isFinance = $request->user()->hasAnyRole(['admin', 'staff_finance']);
        $invoice = $purchaseOrder->invoice;

        return Inertia::render('purchase-orders/show', [
            'order' => $purchaseOrder,
            'canOrder' => $isWarehouse && $purchaseOrder->status === PurchaseOrder::STATUS_DRAFT,
            'canReceive' => $isWarehouse && $purchaseOrder->status === PurchaseOrder::STATUS_ORDERED,
            'canCancel' => $isWarehouse && in_array($purchaseOrder->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_ORDERED], true),
            'canRecordInvoice' => $isFinance
                && $purchaseOrder->status === PurchaseOrder::STATUS_RECEIVED
                && $invoice === null,
            'canPay' => $isFinance
                && $invoice !== null
                && (float) $invoice->amount_paid < (float) $invoice->amount,
        ]);
    }

    /**
     * Transisi draft → ordered: PO dikirim ke vendor.
     */
    public function markOrdered(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $this->purchaseService->markOrdered($purchaseOrder)) {
            return redirect()
                ->route('purchase-orders.show', $purchaseOrder)
                ->with('error', "PO tidak bisa dipesan: status sudah {$purchaseOrder->status}.");
        }

        return redirect()
            ->route('purchase-orders.show', $purchaseOrder)
            ->with('success', "PO {$purchaseOrder->po_number} ditandai dipesan — menunggu barang datang.");
    }

    /**
     * Transisi ordered → received: stok bertambah via StockService + auto-jurnal.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $this->purchaseService->receive($purchaseOrder, $request->user())) {
            return redirect()
                ->route('purchase-orders.show', $purchaseOrder)
                ->with('error', "PO tidak bisa diterima: status {$purchaseOrder->status} (harus ordered).");
        }

        return redirect()
            ->route('purchase-orders.show', $purchaseOrder)
            ->with('success', "Barang PO {$purchaseOrder->po_number} diterima — stok bertambah & jurnal tercatat.");
    }

    /**
     * Batalkan PO draft/ordered (belum pernah diterima).
     */
    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $this->purchaseService->cancel($purchaseOrder)) {
            return redirect()
                ->route('purchase-orders.show', $purchaseOrder)
                ->with('error', "PO tidak bisa dibatalkan: status {$purchaseOrder->status}.");
        }

        return redirect()
            ->route('purchase-orders.show', $purchaseOrder)
            ->with('success', "PO {$purchaseOrder->po_number} berhasil dibatalkan.");
    }
}
