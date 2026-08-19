<?php

namespace App\Http\Controllers\Finance;

use App\Exports\BalanceSheetExport;
use App\Exports\IncomeStatementExport;
use App\Http\Controllers\Controller;
use App\Services\FinanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Laporan keuangan (plan Phase 6: preview; Phase 8: export PDF/Excel).
 */
class FinancialReportController extends Controller
{
    public function __construct(
        private readonly FinanceReportService $reportService,
    ) {}

    /**
     * Laba rugi — default periode bulan berjalan.
     */
    public function incomeStatement(Request $request): Response
    {
        [$from, $to] = $this->period($request);

        return Inertia::render('finance/reports/income-statement', [
            'report' => $this->reportService->incomeStatement($from, $to),
        ]);
    }

    public function incomeStatementPdf(Request $request): \Illuminate\Http\Response
    {
        [$from, $to] = $this->period($request);

        return Pdf::loadView('reports.income-statement', [
            'report' => $this->reportService->incomeStatement($from, $to),
        ])->download("laba-rugi_{$from}_{$to}.pdf");
    }

    public function incomeStatementExcel(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->period($request);

        return Excel::download(
            new IncomeStatementExport($this->reportService->incomeStatement($from, $to)),
            "laba-rugi_{$from}_{$to}.xlsx",
        );
    }

    /**
     * Neraca per tanggal — default hari ini.
     */
    public function balanceSheet(Request $request): Response
    {
        $asOf = $request->query('as_of') ?: today()->toDateString();

        return Inertia::render('finance/reports/balance-sheet', [
            'report' => $this->reportService->balanceSheet($asOf),
        ]);
    }

    public function balanceSheetPdf(Request $request): \Illuminate\Http\Response
    {
        $asOf = $this->asOf($request);

        return Pdf::loadView('reports.balance-sheet', [
            'report' => $this->reportService->balanceSheet($asOf),
        ])->download("neraca_{$asOf}.pdf");
    }

    public function balanceSheetExcel(Request $request): BinaryFileResponse
    {
        $asOf = $this->asOf($request);

        return Excel::download(
            new BalanceSheetExport($this->reportService->balanceSheet($asOf)),
            "neraca_{$asOf}.xlsx",
        );
    }

    /**
     * Rentang periode laba rugi — default bulan berjalan.
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

    private function asOf(Request $request): string
    {
        return $request->query('as_of') ?: today()->toDateString();
    }
}
