<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\VendorInvoice;
use App\Models\VendorPayment;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Invoice vendor & pembayaran (staff_finance & admin sesuai requirement
 * "Staff Finance — kelola pembayaran, invoice"). Nomor invoice milik vendor,
 * bukan digenerate sistem (schema-database.md §6.3).
 */
class VendorInvoiceController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

    /**
     * Catat invoice vendor untuk PO received (1 PO maksimal 1 invoice).
     */
    public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_invoice_number' => ['required', 'string', 'max:50'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $this->purchaseService->recordVendorInvoice($purchaseOrder, $validated);

        return redirect()
            ->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Invoice vendor berhasil dicatat.');
    }

    /**
     * Catat pembayaran ke vendor: update status invoice, lunas → PO paid,
     * auto-jurnal purchase_payment.
     */
    public function storePayment(Request $request, VendorInvoice $vendorInvoice): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', Rule::in(VendorPayment::METHODS)],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->purchaseService->pay($vendorInvoice, $validated, $request->user());

        return redirect()
            ->route('purchase-orders.show', $vendorInvoice->purchase_order_id)
            ->with('success', 'Pembayaran vendor berhasil dicatat.');
    }
}
