<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Kalkulasi laporan keuangan (plan Phase 6): buku besar per akun, laba rugi,
 * dan neraca. Sumber data selalu journal_lines + journal_entries — jurnal
 * immutable sehingga laporan selalu bisa direkonstruksi dari riwayat.
 */
class FinanceReportService
{
    /**
     * Buku besar satu akun: saldo awal (sebelum `from`), mutasi dalam rentang
     * tanggal, dan saldo berjalan per baris. Akun bertipe asset/expense
     * bersaldo normal debit; liability/equity/revenue bersaldo normal kredit.
     *
     * @return array{
     *     opening: float,
     *     closing: float,
     *     total_debit: float,
     *     total_credit: float,
     *     lines: list<array{entry_number: string, entry_date: string, description: string, debit: float, credit: float, balance: float}>,
     * }
     */
    public function ledger(ChartOfAccount $account, ?string $from, ?string $to): array
    {
        $sign = $this->isDebitNature($account->type) ? 1 : -1;

        $opening = 0.0;

        if ($from !== null) {
            $opening = round($this->sumDebitCredit($account->id, to: null, before: $from) * $sign, 2);
        }

        $rows = $this->lineQuery($account->id, $from, $to)->get();

        $balance = $opening;
        $lines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($rows as $row) {
            $totalDebit += (float) $row->debit;
            $totalCredit += (float) $row->credit;
            $balance = round($balance + (((float) $row->debit - (float) $row->credit) * $sign), 2);

            $lines[] = [
                'entry_number' => $row->entry_number,
                'entry_date' => (string) $row->entry_date,
                'description' => $row->description,
                'debit' => (float) $row->debit,
                'credit' => (float) $row->credit,
                'balance' => $balance,
            ];
        }

        return [
            'opening' => $opening,
            'closing' => $balance,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'lines' => $lines,
        ];
    }

    /**
     * Laba rugi periode: pendapatan (saldo kredit) dikurangi beban (saldo
     * debit) dari akun postable yang bergerak dalam rentang tanggal.
     *
     * @return array{
     *     from: string,
     *     to: string,
     *     revenues: list<array{code: string, name: string, amount: float}>,
     *     expenses: list<array{code: string, name: string, amount: float}>,
     *     total_revenue: float,
     *     total_expense: float,
     *     net_income: float,
     * }
     */
    public function incomeStatement(string $from, string $to): array
    {
        $revenues = $this->accountsWithType('revenue', $from, $to);
        $expenses = $this->accountsWithType('expense', $from, $to);

        $revenueRows = $this->toReportRows($revenues, creditNature: true);
        $expenseRows = $this->toReportRows($expenses, creditNature: false);

        $totalRevenue = round(array_sum(array_column($revenueRows, 'amount')), 2);
        $totalExpense = round(array_sum(array_column($expenseRows, 'amount')), 2);

        return [
            'from' => $from,
            'to' => $to,
            'revenues' => $revenueRows,
            'expenses' => $expenseRows,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_income' => round($totalRevenue - $totalExpense, 2),
        ];
    }

    /**
     * Neraca per tanggal: aset = liabilitas + ekuitas. Tanpa jurnal penutup,
     * laba tahun berjalan (pendapatan − beban s.d. tanggal) ditampilkan
     * sebagai baris terpisah di bagian ekuitas.
     *
     * @return array{
     *     as_of: string,
     *     assets: list<array{code: string, name: string, amount: float}>,
     *     liabilities: list<array{code: string, name: string, amount: float}>,
     *     equity: list<array{code: string, name: string, amount: float}>,
     *     current_earnings: float,
     *     total_assets: float,
     *     total_liabilities: float,
     *     total_equity: float,
     * }
     */
    public function balanceSheet(string $asOf): array
    {
        $assets = $this->toReportRows($this->accountsWithType('asset', null, $asOf), creditNature: false);
        $liabilities = $this->toReportRows($this->accountsWithType('liability', null, $asOf), creditNature: true);
        $equity = $this->toReportRows($this->accountsWithType('equity', null, $asOf), creditNature: true);

        $revenues = $this->toReportRows($this->accountsWithType('revenue', null, $asOf), creditNature: true);
        $expenses = $this->toReportRows($this->accountsWithType('expense', null, $asOf), creditNature: false);

        $totalAssets = round(array_sum(array_column($assets, 'amount')), 2);
        $totalLiabilities = round(array_sum(array_column($liabilities, 'amount')), 2);
        $totalEquityAccounts = round(array_sum(array_column($equity, 'amount')), 2);

        return [
            'as_of' => $asOf,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'current_earnings' => round(array_sum(array_column($revenues, 'amount')) - array_sum(array_column($expenses, 'amount')), 2),
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquityAccounts,
        ];
    }

    /**
     * Mutasi per akun postable bertipe tertentu — akun tanpa gerakan dalam
     * rentang tidak dikembalikan (baris nol tidak ditampilkan di laporan).
     *
     * @return list<array{account: ChartOfAccount, debit: float, credit: float}>
     */
    private function accountsWithType(string $type, ?string $from, ?string $to): array
    {
        $accounts = ChartOfAccount::query()
            ->where('type', $type)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        if ($accounts->isEmpty()) {
            return [];
        }

        $sums = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereIn('journal_lines.account_id', $accounts->keys())
            ->when($from, fn ($query) => $query->where('journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($query) => $query->where('journal_entries.entry_date', '<=', $to))
            ->groupBy('journal_lines.account_id')
            ->selectRaw('journal_lines.account_id, SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
            ->get();

        $movements = [];

        foreach ($sums as $sum) {
            $account = $accounts->get($sum->account_id);

            if ($account === null || ((float) $sum->total_debit == 0.0 && (float) $sum->total_credit == 0.0)) {
                continue;
            }

            $movements[] = ['account' => $account, 'debit' => (float) $sum->total_debit, 'credit' => (float) $sum->total_credit];
        }

        return $movements;
    }

    /**
     * Baris laporan: nominal positif bersesuaian dengan sifat akun
     * (creditNature: kredit − debit; sebaliknya debit − kredit).
     *
     * @param  list<array{account: ChartOfAccount, debit: float, credit: float}>  $movements
     * @return list<array{code: string, name: string, amount: float}>
     */
    private function toReportRows(array $movements, bool $creditNature): array
    {
        return array_map(fn (array $movement): array => [
            'code' => $movement['account']->code,
            'name' => $movement['account']->name,
            'amount' => round($creditNature
                ? $movement['credit'] - $movement['debit']
                : $movement['debit'] - $movement['credit'], 2),
        ], $movements);
    }

    private function isDebitNature(string $type): bool
    {
        return in_array($type, ['asset', 'expense'], true);
    }

    /**
     * Query baris buku besar satu akun dalam rentang tanggal (urut tanggal
     * lalu id agar stabil).
     *
     * @return Builder
     */
    private function lineQuery(int $accountId, ?string $from, ?string $to)
    {
        return DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.account_id', $accountId)
            ->when($from, fn ($query) => $query->where('journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($query) => $query->where('journal_entries.entry_date', '<=', $to))
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_lines.id')
            ->select('journal_lines.debit', 'journal_lines.credit', 'journal_entries.entry_number', 'journal_entries.entry_date', 'journal_entries.description');
    }

    /**
     * Σ(debit − credit) satu akun sampai batas `to` / sebelum `before`.
     */
    private function sumDebitCredit(int $accountId, ?string $to, ?string $before): float
    {
        return (float) DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.account_id', $accountId)
            ->when($to, fn ($query) => $query->where('journal_entries.entry_date', '<=', $to))
            ->when($before, fn ($query) => $query->where('journal_entries.entry_date', '<', $before))
            ->selectRaw('COALESCE(SUM(journal_lines.debit - journal_lines.credit), 0) as total')
            ->value('total');
    }
}
