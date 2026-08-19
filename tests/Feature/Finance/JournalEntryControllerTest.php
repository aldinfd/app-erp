<?php

namespace Tests\Feature\Finance;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\JournalService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class JournalEntryControllerTest extends TestCase
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

    /** Helper: post jurnal manual 2 line via service. */
    private function postEntry(string $date, float $amount, string $description = 'Jurnal tes'): JournalEntry
    {
        return $this->journalService->post(
            source: JournalEntry::SOURCE_MANUAL,
            entryDate: $date,
            description: $description,
            lines: [
                ['account_id' => $this->accountId('1-1000'), 'debit' => $amount, 'credit' => 0],
                ['account_id' => $this->accountId('4-1000'), 'debit' => 0, 'credit' => $amount],
            ],
        );
    }

    public function test_index_renders_entries_with_lines(): void
    {
        $this->actingAsRole('admin');

        $this->postEntry(today()->toDateString(), 100000);
        $this->postEntry(today()->toDateString(), 50000);

        $this->get(route('journal-entries.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('finance/journal-entries/index')
                    ->has('entries.data', 2)
                    ->has('entries.data.0.lines', 2),
            );
    }

    public function test_index_filters_by_date_range_source_and_search(): void
    {
        $this->actingAsRole('admin');

        $old = $this->postEntry(today()->subMonth()->toDateString(), 100000, 'Jurnal bulan lalu');
        $new = $this->postEntry(today()->toDateString(), 50000, 'Koreksi biaya kirim');

        // Rentang tanggal hanya bulan ini.
        $this->get(route('journal-entries.index', [
            'from' => today()->startOfMonth()->toDateString(),
            'to' => today()->endOfMonth()->toDateString(),
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('entries.total', 1)
                ->where('entries.data.0.id', $new->id));

        // Search deskripsi.
        $this->get(route('journal-entries.index', ['q' => 'bulan lalu']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('entries.total', 1)
                ->where('entries.data.0.id', $old->id));
    }

    public function test_create_lists_postable_active_accounts_only(): void
    {
        $this->actingAsRole('staff_finance');

        ChartOfAccount::factory()->create([
            'code' => '9-0000',
            'type' => 'expense',
            'is_postable' => false,
        ]);
        ChartOfAccount::factory()->create([
            'code' => '9-1000',
            'type' => 'expense',
            'is_active' => false,
        ]);

        $this->get(route('journal-entries.create'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('finance/journal-entries/create')
                    ->has('accounts', 10) // hanya akun postable aktif dari seeder
                    ->where('accounts.0.code', '1-1000'),
            );
    }

    public function test_store_creates_manual_entry_with_posted_by(): void
    {
        $user = $this->actingAsRole('staff_finance');

        $this->post(route('journal-entries.store'), [
            'entry_date' => today()->toDateString(),
            'description' => 'Koreksi pencatatan biaya kirim',
            'lines' => [
                ['account_id' => $this->accountId('5-2000'), 'debit' => 75000, 'credit' => 0],
                ['account_id' => $this->accountId('1-1000'), 'debit' => 0, 'credit' => 75000],
            ],
        ])->assertRedirect(route('journal-entries.show', JournalEntry::query()->sole()->id));

        $entry = JournalEntry::query()->sole();

        $this->assertSame(JournalEntry::SOURCE_MANUAL, $entry->source);
        $this->assertSame($user->id, $entry->posted_by);
        $this->assertSame(2, $entry->lines()->count());
    }

    public function test_store_rejects_unbalanced_lines(): void
    {
        $this->actingAsRole('staff_finance');

        $this->from(route('journal-entries.create'))
            ->post(route('journal-entries.store'), [
                'entry_date' => today()->toDateString(),
                'description' => 'Jurnal timpang',
                'lines' => [
                    ['account_id' => $this->accountId('1-1000'), 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->accountId('4-1000'), 'debit' => 0, 'credit' => 90000],
                ],
            ])
            ->assertRedirect(route('journal-entries.create'))
            ->assertSessionHas('error');

        $this->assertSame(0, JournalEntry::count());
    }

    public function test_store_rejects_non_postable_account(): void
    {
        $this->actingAsRole('staff_finance');

        $header = $this->accountId('1-0000'); // akun header

        $this->from(route('journal-entries.create'))
            ->post(route('journal-entries.store'), [
                'entry_date' => today()->toDateString(),
                'description' => 'Jurnal ke akun header',
                'lines' => [
                    ['account_id' => $header, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->accountId('4-1000'), 'debit' => 0, 'credit' => 100000],
                ],
            ])
            ->assertRedirect(route('journal-entries.create'))
            ->assertSessionHas('error');

        $this->assertSame(0, JournalEntry::count());
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAsRole('staff_finance');

        $this->from(route('journal-entries.create'))
            ->post(route('journal-entries.store'), [
                'entry_date' => today()->toDateString(),
                'description' => '',
                'lines' => [],
            ])
            ->assertSessionHasErrors(['description', 'lines']);

        $this->assertSame(0, JournalEntry::count());
    }

    public function test_show_renders_entry_with_lines_and_poster(): void
    {
        $user = $this->actingAsRole('admin');

        $entry = $this->journalService->post(
            source: JournalEntry::SOURCE_MANUAL,
            entryDate: today()->toDateString(),
            description: 'Jurnal detail tes',
            lines: [
                ['account_id' => $this->accountId('1-1000'), 'debit' => 100000, 'credit' => 0],
                ['account_id' => $this->accountId('4-1000'), 'debit' => 0, 'credit' => 100000],
            ],
            postedBy: $user->id,
        );

        $this->get(route('journal-entries.show', $entry))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('finance/journal-entries/show')
                    ->where('entry.id', $entry->id)
                    ->has('entry.lines', 2)
                    ->where('entry.poster.name', $user->name),
            );
    }

    public function test_staff_gudang_is_forbidden(): void
    {
        $this->actingAsRole('staff_gudang');

        $entry = $this->postEntry(today()->toDateString(), 100000);

        $this->get(route('journal-entries.index'))->assertForbidden();
        $this->get(route('journal-entries.create'))->assertForbidden();
        $this->get(route('journal-entries.show', $entry))->assertForbidden();
        $this->post(route('journal-entries.store'), [])->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('journal-entries.index'))->assertRedirect(route('login'));
        $this->get(route('general-ledger.index'))->assertRedirect(route('login'));
        $this->get(route('reports.income-statement'))->assertRedirect(route('login'));
        $this->get(route('reports.balance-sheet'))->assertRedirect(route('login'));
    }
}
