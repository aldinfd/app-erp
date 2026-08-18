<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Rules\WholeNumber;
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
            ->with('unit:id,abbreviation,allows_fraction')
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
        // Ambil produk dulu (null-safe) supaya aturan new_qty bisa menyesuaikan satuan:
        // bilangan bulat kecuali satuannya boleh pecahan (mis. kg).
        $product = Product::query()
            ->with('unit:id,abbreviation,allows_fraction')
            ->find($request->input('product_id'));

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'new_qty' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999',
                ...($product?->unit?->allows_fraction ? [] : [new WholeNumber]),
            ],
            'note' => ['required', 'string', 'max:255'],
        ]);

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

        // Tampilkan angka bulat untuk satuan non-pecahan (after_qty bertipe "8.00").
        $afterQty = $product->unit?->allows_fraction
            ? $movement->after_qty
            : (string) (float) $movement->after_qty;

        return redirect()
            ->route('stock-opname.index')
            ->with('success', 'Stok '.$product->name.' disesuaikan menjadi '.$afterQty.'.');
    }
}
