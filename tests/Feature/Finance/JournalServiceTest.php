<?php

namespace Tests\Feature\Finance;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\JournalMapping;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\JournalService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\JournalMappingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class JournalServiceTest extends TestCase
{
    use RefreshDatabase;

    private JournalService $journalService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalService = app(JournalService::class);

        $this->seed([ChartOfAccountSeeder::class, JournalMappingSeeder::class]);
    }

    /** Helper: id akun postable/berdasarkan code CoA seeder. */
    private function accountId(string $code): int
    {
        $id = ChartOfAccount::where('code', $code)->value('id');

        $this->assertNotNull($id, "Akun {$code} tidak ada — seeder tidak jalan.");

        return $id;
    }

    /** Helper: order + invoice + payment utuh untuk postSalesPayment. */
    private function makePaidPayment(float $subtotal, float $tax): Payment
    {
        $order = SalesOrder::factory()->create([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => 0,
            'grand_total' => $subtotal + $tax,
        ]);

        $invoice = Invoice::factory()->for($order)->create(['amount' => $subtotal + $tax]);

        return Payment::factory()->for($invoice)->create(['amount' => $subtotal + $tax]);
    }

    public function test_posts_a_balanced_entry_with_sequential_entry_numbers(): void
    {
        $kas = $this->accountId('1-1000');
        $pendapatan = $this->accountId('4-1000');

        $first = $this->journalService->post(
            source: JournalEntry::SOURCE_MANUAL,
            entryDate: today()->toDateString(),
            description: 'Jurnal tes pertama',
            lines: [
                ['account_id' => $kas, 'debit' => 150000, 'credit' => 0],
                ['account_id' => $pendapatan, 'debit' => 0, 'credit' => 150000],
            ],
        );

        $second = $this->journalService->post(
            source: JournalEntry::SOURCE_MANUAL,
            entryDate: today()->toDateString(),
            description: 'Jurnal tes kedua',
            lines: [
                ['account_id' => $kas, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $pendapatan, 'debit' => 0, 'credit' => 50000],
            ],
        );

        $ym = now()->format('Ym');

        $this->assertSame("JE-{$ym}-0001", $first->entry_number);
        $this->assertSame("JE-{$ym}-0002", $second->entry_number);
        $this->assertSame(2, JournalLine::where('journal_entry_id', $first->id)->count());
    }

    public function test_rejects_an_unbalanced_entry(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->journalService->post(
            source: JournalEntry::SOURCE_MANUAL,
            entryDate: today()->toDateString(),
            description: 'Jurnal timpang',
            lines: [
                ['account_id' => $this->accountId('1-1000'), 'debit' => 100000, 'credit' => 0],
                ['account_id' => $this->accountId('4-1000'), 'debit' => 0, 'credit' => 90000],
            ],
        );
    }

    public function test_rejects_a_line_with_both_debit_and_credit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->journalService->post(
            source: JournalEntry::SOURCE_MANUAL,
            entryDate: today()->toDateString(),
            description: 'Jurnal salah',
            lines: [
                ['account_id' => $this->accountId('1-1000'), 'debit' => 100000, 'credit' => 100000],
            ],
        );
    }

    public function test_rejects_a_line_with_zero_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->journalService->post(
            source: JournalEntry::SOURCE_MANUAL,
            entryDate: today()->toDateString(),
            description: 'Jurnal kosong',
            lines: [
                ['account_id' => $this->accountId('1-1000'), 'debit' => 0, 'credit' => 0],
            ],
        );
    }

    public function test_rejects_a_non_postable_account(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $header = $this->accountId('1-0000'); // akun header, is_postable = 0

        $this->journalService->post(
            source: JournalEntry::SOURCE_MANUAL,
            entryDate: today()->toDateString(),
            description: 'Jurnal ke akun header',
            lines: [
                ['account_id' => $header, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $this->accountId('4-1000'), 'debit' => 0, 'credit' => 100000],
            ],
        );
    }

    public function test_sales_payment_skips_utang_ppn_line_when_tax_is_zero(): void
    {
        $payment = $this->makePaidPayment(subtotal: 100000, tax: 0);

        $entry = $this->journalService->postSalesPayment($payment);

        $lines = $entry->lines()->get();

        $this->assertSame(JournalEntry::SOURCE_SALES_PAYMENT, $entry->source);
        $this->assertSame('payment', $entry->reference_type);
        $this->assertSame($payment->id, $entry->reference_id);
        $this->assertSame(2, $lines->count());
        $this->assertSame(
            100000.0,
            (float) $lines->firstWhere('account_id', $this->accountId('1-1000'))->debit,
        );
        $this->assertSame(
            100000.0,
            (float) $lines->firstWhere('account_id', $this->accountId('4-1000'))->credit,
        );
        $this->assertNull($lines->firstWhere('account_id', $this->accountId('2-2000')));
    }

    public function test_sales_payment_includes_utang_ppn_line_when_tax_is_positive(): void
    {
        $payment = $this->makePaidPayment(subtotal: 100000, tax: 11000);

        $entry = $this->journalService->postSalesPayment($payment);

        $lines = $entry->lines()->get();

        $this->assertSame(3, $lines->count());
        $this->assertSame(
            111000.0,
            (float) $lines->firstWhere('account_id', $this->accountId('1-1000'))->debit,
        );
        $this->assertSame(
            100000.0,
            (float) $lines->firstWhere('account_id', $this->accountId('4-1000'))->credit,
        );
        $this->assertSame(
            11000.0,
            (float) $lines->firstWhere('account_id', $this->accountId('2-2000'))->credit,
        );
    }

    public function test_sales_payment_posts_cogs_lines_when_order_has_items(): void
    {
        $order = SalesOrder::factory()->create([
            'subtotal' => 100000,
            'tax' => 0,
            'shipping' => 0,
            'grand_total' => 100000,
        ]);

        $product = Product::factory()->create(['cost_price' => 15000]);

        SalesOrderItem::create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 2,
            'unit_price' => 50000,
            'subtotal' => 100000,
        ]);

        $invoice = Invoice::factory()->for($order)->create(['amount' => 100000]);
        $payment = Payment::factory()->for($invoice)->create(['amount' => 100000]);

        $entry = $this->journalService->postSalesPayment($payment);

        // 4 line: kas/pendapatan + HPP/persediaan (COGS = 2 × 15000).
        $lines = $entry->lines()->get();

        $this->assertSame(4, $lines->count());
        $this->assertSame(30000.0, (float) $lines->firstWhere('account_id', $this->accountId('5-1000'))->debit);
        $this->assertSame(30000.0, (float) $lines->firstWhere('account_id', $this->accountId('1-3000'))->credit);
    }

    public function test_sales_payment_throws_when_mapping_is_missing(): void
    {
        JournalMapping::query()
            ->where('transaction_type', JournalMapping::TRANSACTION_TYPE_SALES_PAYMENT)
            ->where('account_key', JournalMapping::KEY_KAS_BANK)
            ->delete();

        $payment = $this->makePaidPayment(subtotal: 100000, tax: 0);

        $this->expectException(RuntimeException::class);

        $this->journalService->postSalesPayment($payment);
    }
}
