<?php

namespace App\Services;

use App\Events\LowStockDetected;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Satu-satunya pintu untuk mengubah products.stock_qty (plan Phase 3.2).
 *
 * Setiap perubahan selalu menulis baris stock_movements (qty signed delta:
 * in = +, out = -, adjust = +/-) dan dijalankan dalam satu DB transaction
 * dengan lock pada baris produk, supaya dua proses bersamaan tidak saling
 * menimpa (before_qty/after_qty tetap konsisten).
 */
class StockService
{
    /**
     * Tambah stok (mis. barang belian diterima di Phase 5).
     *
     * @param  float  $qty  selalu positif
     */
    public function add(Product $product, float $qty, ?string $referenceType = null, ?int $referenceId = null, ?string $note = null, ?User $user = null): StockMovement
    {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Qty stok masuk harus lebih besar dari 0.');
        }

        ['movement' => $movement] = $this->move($product, StockMovement::TYPE_IN, $qty, $referenceType, $referenceId, $note, $user);

        return $movement;
    }

    /**
     * Kurangi stok (mis. penjualan dikonfirmasi di Phase 4).
     *
     * @param  float  $qty  selalu positif; movement dicatat negatif
     *
     * @throws InsufficientStockException bila stok tidak cukup (transaksi dibatalkan)
     */
    public function deduct(Product $product, float $qty, ?string $referenceType = null, ?int $referenceId = null, ?string $note = null, ?User $user = null): StockMovement
    {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Qty stok keluar harus lebih besar dari 0.');
        }

        ['movement' => $movement] = $this->move($product, StockMovement::TYPE_OUT, $qty, $referenceType, $referenceId, $note, $user);

        return $movement;
    }

    /**
     * Koreksi stok hasil stock opname: set stok ke angka hasil hitung fisik.
     * Mengembalikan null bila angka tidak berubah (tidak perlu movement).
     */
    public function adjust(Product $product, float $newQty, string $note, ?User $user = null): ?StockMovement
    {
        if ($newQty < 0) {
            throw new InvalidArgumentException('Stok hasil opname tidak boleh negatif.');
        }

        if (trim($note) === '') {
            throw new InvalidArgumentException('Alasan penyesuaian stok wajib diisi.');
        }

        ['movement' => $movement] = $this->move($product, StockMovement::TYPE_ADJUST, $newQty, 'stock_opname', null, $note, $user);

        return $movement;
    }

    /**
     * Jalankan satu perubahan stok dalam DB transaction.
     *
     * @param  float  $qty  untuk in/out: qty perubahan (selalu positif). Untuk adjust: stok hasil opname (absolut), delta dihitung dari stok terkunci.
     * @return array{movement: StockMovement|null, lowStock: bool} movement null bila adjust tanpa perubahan
     */
    private function move(Product $product, string $type, float $qty, ?string $referenceType, ?int $referenceId, ?string $note, ?User $user): array
    {
        $result = DB::transaction(function () use ($product, $type, $qty, $referenceType, $referenceId, $note, $user): array {
            $locked = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $before = (float) $locked->stock_qty;

            $delta = match ($type) {
                StockMovement::TYPE_IN => round(abs($qty), 2),
                StockMovement::TYPE_OUT => -round(abs($qty), 2),
                StockMovement::TYPE_ADJUST => round($qty - $before, 2),
            };

            if ($delta === 0.0) {
                return ['movement' => null, 'lowStock' => false];
            }

            $after = round($before + $delta, 2);

            if ($after < 0) {
                throw InsufficientStockException::forProduct($locked, abs($delta), $before);
            }

            $locked->stock_qty = $after;
            $locked->save();

            $movement = StockMovement::create([
                'product_id' => $locked->id,
                'type' => $type,
                'qty' => $delta,
                'before_qty' => $before,
                'after_qty' => $after,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => $user?->id,
                'note' => $note,
            ]);

            $reorderPoint = (float) $locked->reorder_point;

            return [
                'movement' => $movement,
                'lowStock' => $before > $reorderPoint && $after <= $reorderPoint,
            ];
        });

        // Event setelah transaction commit — notifikasi tidak terkirim untuk
        // perubahan yang gagal/rollback.
        if ($result['lowStock']) {
            LowStockDetected::dispatch($product, (float) $result['movement']->after_qty);
        }

        return $result;
    }
}
