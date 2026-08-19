<?php

namespace App\Http\Controllers\Reports;

use App\Exports\StockCardExport;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StockCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Kartu stok per produk (plan Phase 8): preview di browser + export
 * PDF/Excel. Produk dipilih lewat dropdown; default produk pertama.
 */
class StockCardController extends Controller
{
    public function __construct(
        private readonly StockCardService $cardService,
    ) {}

    public function index(Request $request): Response
    {
        [$from, $to] = $this->period($request);

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'unit_id']);

        $selected = $products->firstWhere('id', (int) $request->query('product')) ?? $products->first();

        return Inertia::render('reports/stock-card', [
            'products' => $products,
            'card' => $selected !== null ? $this->cardService->card($selected, $from, $to) : null,
            'filters' => ['from' => $from, 'to' => $to, 'product' => $selected?->id],
        ]);
    }

    public function pdf(Request $request): \Illuminate\Http\Response
    {
        [$from, $to] = $this->period($request);
        $card = $this->cardService->card($this->resolveProduct($request), $from, $to);

        return Pdf::loadView('reports.stock-card', ['card' => $card])
            ->download("kartu-stok_{$card['product']['sku']}_{$from}_{$to}.pdf");
    }

    public function excel(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->period($request);
        $card = $this->cardService->card($this->resolveProduct($request), $from, $to);

        return Excel::download(
            new StockCardExport($card),
            "kartu-stok_{$card['product']['sku']}_{$from}_{$to}.xlsx",
        );
    }

    /**
     * Rentang tanggal laporan — default bulan berjalan.
     *
     * @return array{0: string, 1: string}
     */
    private function period(Request $request): array
    {
        return [
            $request->query('from') ?: today()->startOfMonth()->toDateString(),
            $request->query('to') ?: today()->endOfMonth()->toDateString(),
        ];
    }

    /**
     * Produk terpilih dari query `product`; fallback produk pertama urut nama.
     * Export butuh produk konkret — 404 bila belum ada produk sama sekali.
     */
    private function resolveProduct(Request $request): Product
    {
        return Product::query()
            ->whereKey((int) $request->query('product'))
            ->first()
            ?? Product::query()->orderBy('name')->firstOrFail();
    }
}
