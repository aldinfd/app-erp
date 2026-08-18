<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\VendorInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorInvoice>
 */
class VendorInvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_invoice_number' => 'VI-TEST-'.fake()->unique()->numberBetween(1000, 9999),
            'purchase_order_id' => PurchaseOrder::factory(),
            'invoice_date' => today(),
            'due_date' => null,
            'amount' => fake()->randomFloat(2, 10_000, 1_000_000),
            'amount_paid' => 0,
            'status' => VendorInvoice::STATUS_UNPAID,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_paid' => $attributes['amount'],
            'status' => VendorInvoice::STATUS_PAID,
        ]);
    }
}
