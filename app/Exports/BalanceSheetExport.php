<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Export Excel neraca (plan Phase 8). Total Ekuitas sudah termasuk laba
 * tahun berjalan (tanpa jurnal penutup — konsisten dengan halaman web).
 */
class BalanceSheetExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array{assets: list<array{code: string, name: string, amount: float}>, liabilities: list<array{code: string, name: string, amount: float}>, equity: list<array{code: string, name: string, amount: float}>, current_earnings: float, total_assets: float, total_liabilities: float, total_equity: float}  $report
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

        foreach ($this->report['assets'] as $row) {
            $rows[] = ['Aset', $row['code'], $row['name'], $row['amount']];
        }

        $rows[] = ['Aset', null, 'Total Aset', $this->report['total_assets']];

        foreach ($this->report['liabilities'] as $row) {
            $rows[] = ['Liabilitas', $row['code'], $row['name'], $row['amount']];
        }

        $rows[] = ['Liabilitas', null, 'Total Liabilitas', $this->report['total_liabilities']];

        foreach ($this->report['equity'] as $row) {
            $rows[] = ['Ekuitas', $row['code'], $row['name'], $row['amount']];
        }

        $rows[] = ['Ekuitas', null, 'Laba Tahun Berjalan', $this->report['current_earnings']];
        $rows[] = ['Ekuitas', null, 'Total Ekuitas', $this->report['total_equity'] + $this->report['current_earnings']];

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
