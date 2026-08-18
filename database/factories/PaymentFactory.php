<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount' => fake()->randomFloat(2, 10_000, 1_000_000),
            'method' => Payment::METHOD_MIDTRANS,
            'gateway' => 'midtrans',
            'gateway_ref' => 'SO-TEST-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => Payment::STATUS_PENDING,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_SETTLEMENT,
            'paid_at' => now(),
        ]);
    }
}
