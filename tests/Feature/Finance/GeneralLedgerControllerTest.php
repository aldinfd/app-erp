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

class GeneralLedgerControllerTest extends TestCase
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

    /** Helper: post jurnal manual (kas debit, pendapatan kredit). */
    private function postKasEntry(string $date, float $amount): void
    {
        $this->journalService->post(
            source: 'manual',
            entryDate: $date,
            description: 'Mutasi kas tes',
            lines: [
                ['account_id' => $this->accountId('1-1000'), 'debit' => $amount, 'credit' => 0],
                ['account_id' => $this->accountId('4-1000'), 'debit' => 0, 'credit' => $amount],
            ],
        );
    }

    public function test_index_defaults_to_first_account_with_running_balance(): void
    {
        $this->actingAsRole('admin');

        $this->postKasEntry(today()->subDay()->toDateString(), 100000);
        $this->postKasEntry(today()->toDateString(), 50000);

        // Tanpa query → akun pertama (1-1000 Kas & Bank).
        $this->get(route('general-ledger.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('finance/general-ledger/index')
                    ->where('account.code', '1-1000')
                    ->where('ledger.lines.0.balance', 100000)
                    ->where('ledger.lines.1.balance', 150000)
                    ->where('ledger.closing', 150000),
            );
    }

    public function test_index_includes_opening_balance_from_movements_before_from(): void
    {
        $this->actingAsRole('admin');

        $this->postKasEntry(today()->subDays(10)->toDateString(), 100000);
        $this->postKasEntry(today()->toDateString(), 50000);

        $this->get(route('general-ledger.index', [
            'account_id' => $this->accountId('1-1000'),
            'from' => today()->subDays(5)->toDateString(),
        ]))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('ledger.opening', 100000)
                    ->has('ledger.lines', 1) // hanya mutasi dalam rentang
                    ->where('ledger.lines.0.balance', 150000),
            );
    }

    public function test_index_shows_credit_nature_account_with_positive_balance(): void
    {
        $this->actingAsRole('staff_finance');

        $this->postKasEntry(today()->toDateString(), 100000);

        // Akun pendapatan (credit-nature): saldo = kredit − debit.
        $this->get(route('general-ledger.index', [
            'account_id' => $this->accountId('4-1000'),
        ]))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('account.code', '4-1000')
                    ->where('ledger.lines.0.credit', 100000)
                    ->where('ledger.lines.0.balance', 100000)
                    ->where('ledger.closing', 100000),
            );
    }

    public function test_staff_gudang_is_forbidden(): void
    {
        $this->actingAsRole('staff_gudang');

        $this->get(route('general-ledger.index'))->assertForbidden();
    }
}
