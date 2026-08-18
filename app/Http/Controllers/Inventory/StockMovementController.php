<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    /**
     * Riwayat perubahan stok (read-only — tabel append-only, tidak ada
     * create/edit/delete dari UI).
     */
    public function index(Request $request): Response
    {
        $q = $request->query('q');
        $type = $request->query('type');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $movements = StockMovement::query()
            ->with(['product:id,sku,name,unit_id', 'product.unit:id,abbreviation,allows_fraction', 'user:id,name'])
            ->when($q, fn ($query) => $query->whereHas('product', fn ($product) => $product
                ->where('sku', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%")))
            ->when(in_array($type, StockMovement::TYPES, true), fn ($query) => $query->where('type', $type))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('inventory/stock-movements/index', [
            'movements' => $movements,
            'types' => StockMovement::TYPES,
            'filters' => $request->only(['q', 'type', 'date_from', 'date_to']),
        ]);
    }
}
