<?php

namespace App\Exports;

use App\Models\SalesOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Export Excel laporan penjualan (plan Phase 8). Nominal dibiarkan angka
 * polos (tanpa format rupiah) supaya bisa dihitung ulang di spreadsheet.
 */
class SalesReportExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array{orders: list<array{order_number: string, order_date: string, customer_name: string, status: string, subtotal: float, tax: float, shipping: float, grand_total: float}>, total_subtotal: float, total_tax: float, total_shipping: float, total_grand_total: float}  $report
     */
    public function __construct(
        private readonly array $report,
    ) {}

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        $rows = [];

        foreach ($this->report['orders'] as $order) {
            $rows[] = [
                $order['order_number'],
                $order['order_date'],
                $order['customer_name'],
                SalesOrder::STATUS_LABELS[$order['status']] ?? $order['status'],
                $order['subtotal'],
                $order['tax'],
                $order['shipping'],
                $order['grand_total'],
            ];
        }

        $rows[] = [
            'TOTAL ('.count($this->report['orders']).' order)',
            null,
            null,
            null,
            $this->report['total_subtotal'],
            $this->report['total_tax'],
            $this->report['total_shipping'],
            $this->report['total_grand_total'],
        ];

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Nomor Order', 'Tanggal', 'Customer', 'Status', 'Subtotal', 'Pajak', 'Ongkir', 'Total'];
    }
}
