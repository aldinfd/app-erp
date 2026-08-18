<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-TEST-'.fake()->unique()->numberBetween(1000, 9999),
            'sales_order_id' => SalesOrder::factory(),
            'issued_date' => today(),
            'due_date' => null,
            'amount' => fake()->randomFloat(2, 10_000, 1_000_000),
            'amount_paid' => 0,
            'status' => Invoice::STATUS_UNPAID,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_paid' => $attributes['amount'],
            'status' => Invoice::STATUS_PAID,
        ]);
    }
}
