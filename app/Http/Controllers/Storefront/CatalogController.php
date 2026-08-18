<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    /**
     * Katalog storefront — hanya produk aktif (is_active), soft-delete
     * otomatis tersaring oleh model.
     */
    public function index(): Response
    {
        $products = Product::query()
            ->with('unit:id,name,abbreviation,allows_fraction')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'selling_price', 'stock_qty', 'image_url', 'unit_id']);

        return Inertia::render('storefront/home', [
            'products' => $products,
        ]);
    }
}
