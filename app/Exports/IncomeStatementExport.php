<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Export Excel laba rugi (plan Phase 8). Kolom "Bagian" menandai kelompok
 * akun + baris total sehingga struktur laporan tetap terbaca di spreadsheet.
 */
class IncomeStatementExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array{revenues: list<array{code: string, name: string, amount: float}>, expenses: list<array{code: string, name: string, amount: float}>, total_revenue: float, total_expense: float, net_income: float}  $report
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

        foreach ($this->report['revenues'] as $row) {
            $rows[] = ['Pendapatan', $row['code'], $row['name'], $row['amount']];
        }

        $rows[] = ['Pendapatan', null, 'Total Pendapatan', $this->report['total_revenue']];

        foreach ($this->report['expenses'] as $row) {
            $rows[] = ['Beban', $row['code'], $row['name'], $row['amount']];
        }

        $rows[] = ['Beban', null, 'Total Beban', $this->report['total_expense']];
        $rows[] = ['Hasil', null, $this->report['net_income'] >= 0 ? 'Laba Bersih' : 'Rugi Bersih', $this->report['net_income']];

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Bagian', 'Kode', 'Nama Akun', 'Jumlah'];
    }
}
