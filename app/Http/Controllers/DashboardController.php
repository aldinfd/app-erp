<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\VendorInvoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard ringkasan harian (plan Phase 7). Widget berbeda per role:
 * gudang melihat stok & barang dalam perjalanan, finance melihat
 * penjualan & pembayaran, admin melihat keduanya. Widget yang tidak
 * relevan untuk role TIDAK dikirim ke frontend (bukan hanya disembunyikan).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $props = [];

        if ($user->hasRole('admin') || $user->hasRole('staff_gudang')) {
            $props['low_stock'] = $this->lowStock();
            $props['po_waiting_goods'] = PurchaseOrder::query()
                ->where('status', PurchaseOrder::STATUS_ORDERED)
                ->count();
        }

        if ($user->hasRole('admin') || $user->hasRole('staff_finance')) {
            $monthStart = today()->startOfMonth();

            $props['monthly_sales'] = [
                'total_orders' => SalesOrder::query()
                    ->where('order_date', '>=', $monthStart->toDateString())
                    ->whereNot('status', SalesOrder::STATUS_CANCELLED)
                    ->count(),
                'revenue' => (float) SalesOrder::query()
                    ->where('status', SalesOrder::STATUS_PAID)
                    ->where('order_date', '>=', $monthStart->toDateString())
                    ->sum('grand_total'),
            ];
            $props['pending_sales'] = [
                'orders' => SalesOrder::query()
                    ->whereIn('status', [SalesOrder::STATUS_DRAFT, SalesOrder::STATUS_CONFIRMED])
                    ->count(),
                'invoices' => Invoice::query()
                    ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
                    ->count(),
            ];
            $props['po_waiting_payment'] = VendorInvoice::query()
                ->whereIn('status', [VendorInvoice::STATUS_UNPAID, VendorInvoice::STATUS_PARTIAL])
                ->count();
            $props['sales_chart'] = $this->salesChart();
        }

        return Inertia::render('dashboard', $props);
    }

    /**
     * Produk stok menipis (stock_qty <= reorder_point — definisi sama
     * dengan notifikasi stok Phase 3): total untuk kartu angka + 10 stok
     * terendah untuk tabel.
     *
     * @return array{count: int, products: Collection<int, Product>}
     */
    private function lowStock(): array
    {
        $base = Product::query()->whereColumn('stock_qty', '<=', 'reorder_point');

        return [
            'count' => (clone $base)->count(),
            'products' => (clone $base)
                ->with('unit:id,abbreviation,allows_fraction')
                ->orderBy('stock_qty')
                ->limit(10)
                ->get(['id', 'sku', 'name', 'stock_qty', 'reorder_point', 'unit_id']),
        ];
    }

    /**
     * Revenue (sales order berstatus paid) per bulan selama 6 bulan
     * terakhir termasuk bulan berjalan. Bulan tanpa penjualan tetap
     * muncul dengan nilai 0 supaya sumbu-x grafik selalu lengkap.
     *
     * @return list<array{month: string, revenue: float}>
     */
    private function salesChart(): array
    {
        $start = today()->subMonths(5)->startOfMonth();

        $revenueByMonth = SalesOrder::query()
            ->where('status', SalesOrder::STATUS_PAID)
            ->where('order_date', '>=', $start->toDateString())
            ->selectRaw('substr(order_date, 1, 7) as month, SUM(grand_total) as revenue')
            ->groupBy('month')
            ->pluck('revenue', 'month');

        $chart = [];
        $cursor = $start;

        // Helper tanggal (today()) mengembalikan CarbonImmutable — addMonth()
        // tidak memutasi, hasilnya WAJIB di-reassign.
        for ($i = 0; $i < 6; $i++) {
            $key = $cursor->format('Y-m');
            $chart[] = ['month' => $key, 'revenue' => (float) ($revenueByMonth[$key] ?? 0)];
            $cursor = $cursor->addMonth();
        }

        return $chart;
    }
}
