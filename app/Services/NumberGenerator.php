<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Nomor dokumen berurutan per bulan: {prefix}-YYYYMM-#### (SO-, INV-, JE-).
 *
 * WAJIB dipanggil di dalam DB transaction milik pemanggil. Race dua proses
 * bersamaan ditangkap oleh UNIQUE constraint kolom (transaksi kalah batal —
 * cukup untuk skala toko single-seller).
 */
class NumberGenerator
{
    public static function next(string $prefix, string $table, string $column): string
    {
        $ym = now()->format('Ym');
        $prefixStr = "{$prefix}-{$ym}-";

        $max = DB::table($table)
            ->where($column, 'like', "{$prefixStr}%")
            ->max($column);

        $seq = $max ? (int) substr($max, strlen($prefixStr)) + 1 : 1;

        return $prefixStr.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
