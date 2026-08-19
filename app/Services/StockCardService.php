<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;

/**
 * Kalkulasi kartu stok per produk (plan Phase 8): saldo awal sebelum
 * tanggal mulai, mutasi dalam rentang dengan saldo berjalan, dan saldo
 * akhir. Sumber data stock_movements — append-only sehingga kartu stok
 * selalu bisa direkonstruksi dari riwayat.
 */
class StockCardService
{
    /**
     * @return array{
     *     product: array{id: int, sku: string, name: string, unit: string, allows_fraction: bool},
     *     from: string,
     *     to: string,
     *     opening: float,
     *     closing: float,
     *     total_in: float,
     *     total_out: float,
     *     lines: list<array{date: string, type: string, qty: float, balance: float, reference: ?string, note: ?string, user: ?string}>,
     * }
     */
    public function card(Product $product, string $from, string $to): array
    {
        $product->loadMissing('unit:id,abbreviation,allows_fraction');

        $opening = (float) StockMovement::query()
            ->where('product_id', $product->id)
            ->whereDate('created_at', '<', $from)
            ->sum('qty');

        $movements = StockMovement::query()
            ->where('product_id', $product->id)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->with('user:id,name')
            ->orderBy('id')
            ->get(['id', 'type', 'qty', 'reference_type', 'reference_id', 'user_id', 'note', 'created_at']);

        $balance = $opening;
        $totalIn = 0.0;
        $totalOut = 0.0;
        $lines = [];

        foreach ($movements as $movement) {
            $qty = (float) $movement->qty;
            $balance = round($balance + $qty, 2);

            if ($qty >= 0) {
                $totalIn += $qty;
            } else {
                $totalOut += $qty;
            }

            $lines[] = [
                'date' => $movement->created_at->toDateString(),
                'type' => $movement->type,
                'qty' => $qty,
                'balance' => $balance,
                'reference' => $movement->reference_type !== null
                    ? "{$movement->reference_type} #{$movement->reference_id}"
                    : null,
                'note' => $movement->note,
                'user' => $movement->user?->name,
            ];
        }

        return [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'unit' => $product->unit->abbreviation,
                'allows_fraction' => (bool) $product->unit->allows_fraction,
            ],
            'from' => $from,
            'to' => $to,
            'opening' => $opening,
            'closing' => $balance,
            'total_in' => round($totalIn, 2),
            'total_out' => round($totalOut, 2),
            'lines' => $lines,
        ];
    }
}
