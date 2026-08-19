<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Services\FinanceReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralLedgerController extends Controller
{
    public function __construct(
        private readonly FinanceReportService $reportService,
    ) {}

    /**
     * Buku besar satu akun: pilih akun (default akun pertama) + filter
     * rentang tanggal; saldo awal dihitung dari mutasi sebelum `from`.
     */
    public function index(Request $request): Response
    {
        $accounts = ChartOfAccount::query()
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        $account = $accounts->firstWhere('id', (int) $request->query('account_id')) ?? $accounts->first();

        $ledger = $account !== null
            ? $this->reportService->ledger($account, $request->query('from'), $request->query('to'))
            : ['opening' => 0, 'closing' => 0, 'total_debit' => 0, 'total_credit' => 0, 'lines' => []];

        return Inertia::render('finance/general-ledger/index', [
            'accounts' => $accounts,
            'account' => $account,
            'ledger' => $ledger,
            'filters' => $request->only(['account_id', 'from', 'to']),
        ]);
    }
}
