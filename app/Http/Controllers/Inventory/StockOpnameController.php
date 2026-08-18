<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    /**
     * Daftar produk untuk stock opname — koreksi stok diinput per baris
     * bersama alasan wajib.
     */
    public function index(Request $request): Response
    {
        $q = $request->query('q');

        $products = Product::query()
            ->with('unit:id,abbreviation')
            ->when($q, fn ($query) => $query
                ->where(fn ($where) => $where
                    ->where('sku', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")))
            ->orderBy('sku')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('inventory/stock-opname/index', [
            'products' => $products,
            'filters' => $request->only(['q']),
        ]);
    }

    public function adjust(Request $request, StockService $stockService): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'new_qty' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'note' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $movement = $stockService->adjust(
            $product,
            (float) $validated['new_qty'],
            $validated['note'],
            $request->user(),
        );

        if ($movement === null) {
            return redirect()
                ->route('stock-opname.index')
                ->with('error', 'Tidak ada perubahan: stok baru sama dengan stok saat ini.');
        }

        return redirect()
            ->route('stock-opname.index')
            ->with('success', 'Stok '.$product->name.' disesuaikan menjadi '.$movement->after_qty.'.');
    }
}
