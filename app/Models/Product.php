<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property int|null $category_id
 * @property int $unit_id
 * @property string $cost_price
 * @property string $selling_price
 * @property string $stock_qty
 * @property string $reorder_point
 * @property string|null $image_url
 * @property bool $is_active
 * @property Carbon|null $deleted_at
 */
#[Fillable(['sku', 'name', 'category_id', 'unit_id', 'cost_price', 'selling_price', 'reorder_point', 'image_url', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasActivity, HasFactory, SoftDeletes;

    /**
     * Catatan desain: `stock_qty` SENGAJA tidak fillable — perubahan stok
     * hanya boleh lewat StockService (Phase 3) agar selalu tercatat
     * di stock_movements. Form create/edit tidak mengisi stok.
     */
    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock_qty' => 'decimal:2',
            'reorder_point' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
