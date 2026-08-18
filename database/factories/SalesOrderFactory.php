<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    /**
     * Order number factory tidak memakai format resmi SO-YYYYMM-#### (itunya
     * NumberGenerator via CheckoutService) — cukup unik untuk kebutuhan test.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10_000, 1_000_000);

        return [
            'order_number' => 'SO-TEST-'.fake()->unique()->numberBetween(1000, 9999),
            'customer_id' => Customer::factory(),
            'order_date' => today(),
            'status' => SalesOrder::STATUS_CONFIRMED,
            'subtotal' => $subtotal,
            'tax' => 0,
            'shipping' => 0,
            'grand_total' => $subtotal,
        ];
    }
}
