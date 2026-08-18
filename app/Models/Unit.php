<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $abbreviation
 * @property bool $allows_fraction true = qty boleh pecahan (mis. kg)
 */
#[Fillable(['name', 'abbreviation', 'allows_fraction'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasActivity, HasFactory;

    protected function casts(): array
    {
        return [
            'allows_fraction' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
