<?php

namespace App\Exports;

use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Export Excel kartu stok (plan Phase 8). Baris pertama saldo awal, baris
 * terakhir saldo akhir; qty angka polos (signed delta, out = negatif).
 */
class StockCardExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array{opening: float, closing: float, lines: list<array{date: string, type: string, qty: float, balance: float, reference: ?string, note: ?string, user: ?string}>}  $card
     */
    public function __construct(
        private readonly array $card,
    ) {}

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        $rows = [
            ['Saldo awal', null, null, $this->card['opening'], null, null, null],
        ];

        foreach ($this->card['lines'] as $line) {
            $rows[] = [
                $line['date'],
                StockMovement::TYPE_LABELS[$line['type']] ?? $line['type'],
                $line['qty'],
                $line['balance'],
                $line['reference'],
                $line['note'],
                $line['user'],
            ];
        }

        $rows[] = ['Saldo akhir', null, null, $this->card['closing'], null, null, null];

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Tanggal', 'Tipe', 'Qty', 'Saldo', 'Referensi', 'Catatan', 'Oleh'];
    }
}
