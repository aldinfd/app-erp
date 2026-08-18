<?php

namespace Database\Factories;

use App\Models\VendorInvoice;
use App\Models\VendorPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorPayment>
 */
class VendorPaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_invoice_id' => VendorInvoice::factory(),
            'amount' => fake()->randomFloat(2, 10_000, 1_000_000),
            'method' => VendorPayment::METHOD_BANK_TRANSFER,
            'reference_no' => 'TRF-TEST-'.fake()->unique()->numberBetween(1000, 9999),
            'paid_at' => now(),
            'notes' => null,
        ];
    }
}
