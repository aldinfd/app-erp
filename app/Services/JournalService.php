<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\JournalMapping;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Satu-satunya pintu untuk membuat jurnal keuangan (plan Phase 6.1, dipakai
 * mulai Phase 4). Setiap entry divalidasi balance (Σ debit = Σ credit) dan
 * hanya boleh ke akun postable (schema-database.md §8).
 *
 * Jurnal immutable: koreksi lewat jurnal reversal, bukan edit/delete.
 */
class JournalService
{
    /**
     * Post satu jurnal berimbang. Validasi + insert dalam satu DB transaction
     * (menjadi savepoint bila dipanggil di dalam transaction pemanggil).
     *
     * @param  array<int, array{account_id: int, debit: float|int|string, credit: float|int|string}>  $lines
     *
     * @throws InvalidArgumentException bila baris tidak sah atau jurnal tidak balance
     */
    public function post(
        string $source,
        string $entryDate,
        string $description,
        array $lines,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $postedBy = null,
    ): JournalEntry {
        return DB::transaction(function () use ($source, $entryDate, $description, $lines, $referenceType, $referenceId, $postedBy): JournalEntry {
            $normalized = $this->normalizeLines($lines);

            $this->assertAccountsPostable($normalized);

            $totalDebit = round(array_sum(array_column($normalized, 'debit')), 2);
            $totalCredit = round(array_sum(array_column($normalized, 'credit')), 2);

            if (abs($totalDebit - $totalCredit) >= 0.001) {
                throw new InvalidArgumentException(
                    sprintf('Jurnal tidak balance: debit %s ≠ credit %s.', $totalDebit, $totalCredit),
                );
            }

            $entry = JournalEntry::create([
                'entry_number' => NumberGenerator::next('JE', 'journal_entries', 'entry_number'),
                'entry_date' => $entryDate,
                'description' => $description,
                'source' => $source,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'posted_by' => $postedBy,
            ]);

            foreach ($normalized as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            return $entry;
        });
    }

    /**
     * Auto-jurnal pembayaran penjualan: Kas/Bank (D) = grand_total,
     * Pendapatan Penjualan (C) = subtotal, Utang PPN (C) = tax bila > 0.
     * Akun dari journal_mappings (transaction_type = sales_payment) — tidak
     * ada id akun yang di-hard-code.
     *
     * @throws RuntimeException bila mapping wajib belum di-seed
     */
    public function postSalesPayment(Payment $payment): JournalEntry
    {
        $order = $payment->invoice->salesOrder;

        $mappings = JournalMapping::query()
            ->where('transaction_type', JournalMapping::TRANSACTION_TYPE_SALES_PAYMENT)
            ->get()
            ->keyBy('account_key');

        foreach ([JournalMapping::KEY_KAS_BANK, JournalMapping::KEY_PENDAPATAN_PENJUALAN] as $requiredKey) {
            if (! $mappings->has($requiredKey)) {
                throw new RuntimeException(
                    "Mapping jurnal sales_payment/{$requiredKey} belum di-seed (JournalMappingSeeder).",
                );
            }
        }

        $lines = [
            ['account_id' => $mappings[JournalMapping::KEY_KAS_BANK]->account_id, 'debit' => (float) $order->grand_total, 'credit' => 0],
            ['account_id' => $mappings[JournalMapping::KEY_PENDAPATAN_PENJUALAN]->account_id, 'debit' => 0, 'credit' => (float) $order->subtotal],
        ];

        if ((float) $order->tax > 0) {
            if (! $mappings->has(JournalMapping::KEY_UTANG_PPN)) {
                throw new RuntimeException(
                    'Mapping jurnal sales_payment/'.JournalMapping::KEY_UTANG_PPN.' belum di-seed (JournalMappingSeeder).',
                );
            }

            $lines[] = ['account_id' => $mappings[JournalMapping::KEY_UTANG_PPN]->account_id, 'debit' => 0, 'credit' => (float) $order->tax];
        }

        return $this->post(
            source: JournalEntry::SOURCE_SALES_PAYMENT,
            entryDate: $payment->paid_at?->toDateString() ?? today()->toDateString(),
            description: "Pembayaran order {$order->order_number}",
            lines: $lines,
            referenceType: 'payment',
            referenceId: $payment->id,
            postedBy: null,
        );
    }

    /**
     * Validasi bentuk tiap baris: tepat satu sisi debit/credit yang > 0.
     *
     * @param  array<int, array{account_id: int, debit: float|int|string, credit: float|int|string}>  $lines
     * @return array<int, array{account_id: int, debit: float, credit: float}>
     */
    private function normalizeLines(array $lines): array
    {
        if ($lines === []) {
            throw new InvalidArgumentException('Jurnal harus punya minimal satu baris.');
        }

        $normalized = [];

        foreach ($lines as $line) {
            $debit = round((float) $line['debit'], 2);
            $credit = round((float) $line['credit'], 2);

            if (($debit > 0) === ($credit > 0)) {
                throw new InvalidArgumentException(
                    'Setiap baris jurnal harus punya tepat satu sisi debit/kredit yang lebih dari 0.',
                );
            }

            $normalized[] = ['account_id' => (int) $line['account_id'], 'debit' => $debit, 'credit' => $credit];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{account_id: int, debit: float, credit: float}>  $lines
     */
    private function assertAccountsPostable(array $lines): void
    {
        $accountIds = array_unique(array_column($lines, 'account_id'));

        $accounts = ChartOfAccount::query()
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        foreach ($accountIds as $accountId) {
            $account = $accounts->get($accountId);

            if ($account === null || ! $account->is_postable) {
                throw new InvalidArgumentException("Jurnal hanya boleh ke akun postable: id {$accountId} tidak sah.");
            }
        }
    }
}
