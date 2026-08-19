<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinanceReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
        $from = $request->query('from') ?: today()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: today()->endOfMonth()->toDateString();

        return Inertia::render('finance/reports/income-statement', [
            'report' => $this->reportService->incomeStatement($from, $to),
        ]);
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
}
