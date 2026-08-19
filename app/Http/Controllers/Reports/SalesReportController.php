<?php

namespace App\Http\Controllers\Reports;

use App\Exports\SalesReportExport;
use App\Http\Controllers\Controller;
use App\Services\SalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Laporan penjualan (plan Phase 8): preview di browser + export PDF/Excel.
 */
class SalesReportController extends Controller
{
    public function __construct(
        private readonly SalesReportService $reportService,
    ) {}

    public function index(Request $request): Response
    {
        [$from, $to] = $this->period($request);

        return Inertia::render('reports/sales', [
            'report' => $this->reportService->salesReport($from, $to),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function pdf(Request $request): \Illuminate\Http\Response
    {
        [$from, $to] = $this->period($request);

        return Pdf::loadView('reports.sales', [
            'report' => $this->reportService->salesReport($from, $to),
        ])->download("laporan-penjualan_{$from}_{$to}.pdf");
    }

    public function excel(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->period($request);

        return Excel::download(
            new SalesReportExport($this->reportService->salesReport($from, $to)),
            "laporan-penjualan_{$from}_{$to}.xlsx",
        );
    }

    /**
     * Rentang tanggal laporan — default bulan berjalan (sama seperti laba rugi).
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
}
