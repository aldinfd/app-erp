<?php

namespace Tests\Feature\Finance;

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\JournalService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    private JournalService $journalService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalService = app(JournalService::class);

        $this->seed([RoleSeeder::class, ChartOfAccountSeeder::class]);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    /** Helper: id akun postable by code CoA seeder. */
    private function accountId(string $code): int
    {
        return (int) ChartOfAccount::where('code', $code)->value('id');
    }

    /**
     * Post jurnal penjualan: D kas 150k / C pendapatan 100k / C utang PPN 50k.
     */
    private function postSale(string $date): void
    {
        $this->journalService->post(
            source: 'sales_payment',
            entryDate: $date,
            description: 'Penjualan tes',
            lines: [
                ['account_id' => $this->accountId('1-1000'), 'debit' => 150000, 'credit' => 0],
                ['account_id' => $this->accountId('4-1000'), 'debit' => 0, 'credit' => 100000],
                ['account_id' => $this->accountId('2-2000'), 'debit' => 0, 'credit' => 50000],
            ],
        );
    }

    /**
     * Post jurnal beban: D beban operasional 30k / C kas 30k.
     */
    private function postExpense(string $date): void
    {
        $this->journalService->post(
            source: 'manual',
            entryDate: $date,
            description: 'Beban tes',
            lines: [
                ['account_id' => $this->accountId('5-2000'), 'debit' => 30000, 'credit' => 0],
                ['account_id' => $this->accountId('1-1000'), 'debit' => 0, 'credit' => 30000],
            ],
        );
    }

    public function test_income_statement_sums_revenues_expenses_and_net_income(): void
    {
        $this->actingAsRole('admin');

        $this->postSale(today()->toDateString());
        $this->postExpense(today()->toDateString());

        $this->get(route('reports.income-statement', [
            'from' => today()->startOfMonth()->toDateString(),
            'to' => today()->endOfMonth()->toDateString(),
        ]))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('finance/reports/income-statement')
                    ->has('report.revenues', 1)
                    ->where('report.revenues.0.amount', 100000)
                    ->has('report.expenses', 1)
                    ->where('report.expenses.0.amount', 30000)
                    ->where('report.total_revenue', 100000)
                    ->where('report.total_expense', 30000)
                    ->where('report.net_income', 70000),
            );
    }

    public function test_income_statement_excludes_movements_outside_period(): void
    {
        $this->actingAsRole('admin');

        $this->postSale(today()->subMonth()->toDateString());
        $this->postSale(today()->toDateString());

        $this->get(route('reports.income-statement', [
            'from' => today()->startOfMonth()->toDateString(),
            'to' => today()->endOfMonth()->toDateString(),
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('report.total_revenue', 100000));
    }

    public function test_balance_sheet_balances_assets_against_liabilities_and_equity(): void
    {
        $this->actingAsRole('staff_finance');

        $this->postSale(today()->toDateString());
        $this->postExpense(today()->toDateString());

        // Kas = 150k − 30k = 120k; utang PPN 50k; laba berjalan = 100k − 30k = 70k.
        // Beban tidak jadi baris aset — sudah terwakili di laba berjalan.
        $this->get(route('reports.balance-sheet', ['as_of' => today()->toDateString()]))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('finance/reports/balance-sheet')
                    ->has('report.assets', 1)
                    ->where('report.assets.0.amount', 120000)
                    ->has('report.liabilities', 1)
                    ->where('report.liabilities.0.amount', 50000)
                    ->where('report.total_assets', 120000)
                    ->where('report.total_liabilities', 50000)
                    ->where('report.total_equity', 0)
                    ->where('report.current_earnings', 70000),
            );
    }

    public function test_staff_gudang_is_forbidden(): void
    {
        $this->actingAsRole('staff_gudang');

        $this->get(route('reports.income-statement'))->assertForbidden();
        $this->get(route('reports.balance-sheet'))->assertForbidden();
    }
}
