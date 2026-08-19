<?php

namespace App\Services;

use App\Models\SalesOrder;

/**
 * Kalkulasi laporan penjualan (plan Phase 8): daftar order dalam rentang
 * tanggal + total per kolom nominal. Order cancelled tidak dihitung
 * sebagai penjualan (definisi sama dengan total order di dashboard).
 */
class SalesReportService
{
    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     orders: list<array{order_number: string, order_date: string, customer_name: string, status: string, subtotal: float, tax: float, shipping: float, grand_total: float}>,
     *     total_orders: int,
     *     total_subtotal: float,
     *     total_tax: float,
     *     total_shipping: float,
     *     total_grand_total: float,
     * }
     */
    public function salesReport(string $from, string $to): array
    {
        $orders = SalesOrder::query()
            ->whereNot('status', SalesOrder::STATUS_CANCELLED)
            ->whereDate('order_date', '>=', $from)
            ->whereDate('order_date', '<=', $to)
            ->with('customer:id,name')
            ->orderBy('order_date')
            ->orderBy('id')
            ->get(['id', 'order_number', 'customer_id', 'order_date', 'status', 'subtotal', 'tax', 'shipping', 'grand_total']);

        return [
            'from' => $from,
            'to' => $to,
            'orders' => $orders->map(fn (SalesOrder $order) => [
                'order_number' => $order->order_number,
                'order_date' => $order->order_date->toDateString(),
                'customer_name' => $order->customer->name,
                'status' => $order->status,
                'subtotal' => (float) $order->subtotal,
                'tax' => (float) $order->tax,
                'shipping' => (float) $order->shipping,
                'grand_total' => (float) $order->grand_total,
            ])->all(),
            'total_orders' => $orders->count(),
            'total_subtotal' => (float) $orders->sum('subtotal'),
            'total_tax' => (float) $orders->sum('tax'),
            'total_shipping' => (float) $orders->sum('shipping'),
            'total_grand_total' => (float) $orders->sum('grand_total'),
        ];
    }
}
