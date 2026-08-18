<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * PO number factory tidak memakai format resmi PO-YYYYMM-#### (itunya
     * NumberGenerator via PurchaseService) — cukup unik untuk kebutuhan test.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10_000, 1_000_000);

        return [
            'po_number' => 'PO-TEST-'.fake()->unique()->numberBetween(1000, 9999),
            'vendor_id' => Vendor::factory(),
            'order_date' => today(),
            'expected_date' => null,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'subtotal' => $subtotal,
            'tax' => 0,
            'grand_total' => $subtotal,
            'notes' => null,
        ];
    }

    public function ordered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrder::STATUS_ORDERED,
        ]);
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrder::STATUS_RECEIVED,
        ]);
    }
}
