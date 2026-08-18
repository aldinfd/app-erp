<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Nilai harus bilangan bulat — "7" maupun "7.00" diterima, "7.5" ditolak.
 * Dipakai untuk qty stok & reorder point produk yang satuannya tidak bisa
 * pecahan (lihat Unit::allows_fraction).
 */
class WholeNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (floor((float) $value) !== (float) $value) {
            $fail('The :attribute must be a whole number for this unit.');
        }
    }
}
