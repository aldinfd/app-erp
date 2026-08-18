<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalesOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderController extends Controller
{
    /**
     * Daftar sales order: search nomor order / nama customer + filter status.
     */
    public function index(Request $request): Response
    {
        $q = $request->query('q');
        $status = $request->query('status');

        $salesOrders = SalesOrder::query()
            ->with('customer:id,name,email')
            ->when($q, fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('order_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$q}%"));
            }))
            ->when(in_array($status, SalesOrder::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('sales-orders/index', [
            'salesOrders' => $salesOrders,
            'statuses' => SalesOrder::STATUSES,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    /**
     * Detail order: item (harga snapshot), invoice, riwayat payment.
     */
    public function show(SalesOrder $salesOrder): Response
    {
        $salesOrder->load([
            'customer:id,name,email,phone,address',
            'items.product:id,sku,name,unit_id',
            'items.product.unit:id,abbreviation,allows_fraction',
            'invoice',
            'payments' => fn ($query) => $query->orderByDesc('id'),
        ]);

        return Inertia::render('sales-orders/show', [
            'order' => $salesOrder,
            'canCancel' => $this->canCancel($salesOrder),
        ]);
    }

    /**
     * Batalkan order draft/confirmed yang belum pernah dibayar: SO cancelled,
     * invoice void, payment pending cancel. Stok tidak di-restore karena
     * pengurangan stok hanya terjadi saat pembayaran (webhook Midtrans).
     */
    public function cancel(SalesOrder $salesOrder): RedirectResponse
    {
        $cancelled = DB::transaction(function () use ($salesOrder) {
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($salesOrder->id);
            $order->load('payments');

            if (! $this->canCancel($order)) {
                return false;
            }

            $order->update(['status' => SalesOrder::STATUS_CANCELLED]);
            $order->invoice?->update(['status' => Invoice::STATUS_VOID]);

            foreach ($order->payments as $payment) {
                if ($payment->status === Payment::STATUS_PENDING) {
                    $payment->update(['status' => Payment::STATUS_CANCEL]);
                }
            }

            return true;
        });

        if (! $cancelled) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('error', 'Order tidak bisa dibatalkan: sudah dibayar atau sudah berstatus cancelled.');
        }

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('success', "Order {$salesOrder->order_number} berhasil dibatalkan.");
    }

    /**
     * Boleh dibatalkan hanya bila masih draft/confirmed dan tidak ada
     * payment yang sukses (settlement/capture).
     */
    private function canCancel(SalesOrder $order): bool
    {
        if (! in_array($order->status, [SalesOrder::STATUS_DRAFT, SalesOrder::STATUS_CONFIRMED], true)) {
            return false;
        }

        return ! $order->payments->contains(
            fn (Payment $payment) => in_array($payment->status, Payment::PAID_STATUSES, true),
        );
    }
}
