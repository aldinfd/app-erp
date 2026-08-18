<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh master data untuk development — bukan data produksi.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Pakaian'],
            ['name' => 'Elektronik'],
            ['name' => 'Makanan & Minuman'],
            ['name' => 'Aksesoris'],
        ])->mapWithKeys(fn (array $attributes) => [
            $attributes['name'] => Category::firstOrCreate($attributes),
        ]);

        $units = collect([
            ['name' => 'Pieces', 'abbreviation' => 'pcs'],
            ['name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['name' => 'Pack', 'abbreviation' => 'pack'],
            ['name' => 'Lusin', 'abbreviation' => 'lusin'],
        ])->mapWithKeys(fn (array $attributes) => [
            $attributes['abbreviation'] => Unit::firstOrCreate($attributes),
        ]);

        $products = [
            ['sku' => 'SKU-PK-001', 'name' => 'Kaos Polos Hitam', 'category' => 'Pakaian', 'unit' => 'pcs', 'cost_price' => 35000, 'selling_price' => 55000, 'reorder_point' => 10],
            ['sku' => 'SKU-PK-002', 'name' => 'Kemeja Flanel Kotak', 'category' => 'Pakaian', 'unit' => 'pcs', 'cost_price' => 85000, 'selling_price' => 129000, 'reorder_point' => 5],
            ['sku' => 'SKU-EL-001', 'name' => 'Mouse Wireless', 'category' => 'Elektronik', 'unit' => 'pcs', 'cost_price' => 95000, 'selling_price' => 145000, 'reorder_point' => 5],
            ['sku' => 'SKU-EL-002', 'name' => 'Keyboard Mekanik', 'category' => 'Elektronik', 'unit' => 'pcs', 'cost_price' => 350000, 'selling_price' => 499000, 'reorder_point' => 3],
            ['sku' => 'SKU-MM-001', 'name' => 'Kopi Arabika 250gr', 'category' => 'Makanan & Minuman', 'unit' => 'pack', 'cost_price' => 42000, 'selling_price' => 65000, 'reorder_point' => 20],
            ['sku' => 'SKU-MM-002', 'name' => 'Beras Premium', 'category' => 'Makanan & Minuman', 'unit' => 'kg', 'cost_price' => 12000, 'selling_price' => 15500, 'reorder_point' => 50],
            ['sku' => 'SKU-AK-001', 'name' => 'Topping Laptop', 'category' => 'Aksesoris', 'unit' => 'pcs', 'cost_price' => 25000, 'selling_price' => 45000, 'reorder_point' => 10],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'category_id' => $categories[$product['category']]->id,
                    'unit_id' => $units[$product['unit']]->id,
                    'cost_price' => $product['cost_price'],
                    'selling_price' => $product['selling_price'],
                    'reorder_point' => $product['reorder_point'],
                ],
            );
        }

        $this->command?->info(sprintf(
            'Master data: %d kategori, %d satuan, %d produk.',
            DB::table('categories')->count(),
            DB::table('units')->count(),
            DB::table('products')->count(),
        ));
    }
}
