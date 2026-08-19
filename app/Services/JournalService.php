<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\JournalMapping;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\VendorPayment;
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
     * Pendapatan Penjualan (C) = subtotal, Utang PPN (C) = tax bila > 0,
     * plus HPP (disetujui 2026-08-18): HPP (D) / Persediaan (C) = Σ qty ×
     * cost_price produk saat pembayaran (items hanya snapshot harga jual).
     * Akun dari journal_mappings (transaction_type = sales_payment) — tidak
     * ada id akun yang di-hard-code.
     *
     * @throws RuntimeException bila mapping wajib belum di-seed
     */
    public function postSalesPayment(Payment $payment): JournalEntry
    {
        $order = $payment->invoice->salesOrder;
        $order->load('items.product:id,cost_price');

        $mappings = $this->requireMappings(
            JournalMapping::TRANSACTION_TYPE_SALES_PAYMENT,
            [JournalMapping::KEY_KAS_BANK, JournalMapping::KEY_PENDAPATAN_PENJUALAN],
        );

        $lines = [
            ['account_id' => $mappings[JournalMapping::KEY_KAS_BANK]->account_id, 'debit' => (float) $order->grand_total, 'credit' => 0],
            ['account_id' => $mappings[JournalMapping::KEY_PENDAPATAN_PENJUALAN]->account_id, 'debit' => 0, 'credit' => (float) $order->subtotal],
        ];

        if ((float) $order->tax > 0) {
            if (! isset($mappings[JournalMapping::KEY_UTANG_PPN])) {
                throw new RuntimeException(
                    'Mapping jurnal sales_payment/'.JournalMapping::KEY_UTANG_PPN.' belum di-seed (JournalMappingSeeder).',
                );
            }

            $lines[] = ['account_id' => $mappings[JournalMapping::KEY_UTANG_PPN]->account_id, 'debit' => 0, 'credit' => (float) $order->tax];
        }

        $cogs = $this->salesCostOfGoodsSold($order);

        if ($cogs > 0) {
            foreach ([JournalMapping::KEY_HPP, JournalMapping::KEY_PERSEDIAAN] as $key) {
                if (! isset($mappings[$key])) {
                    throw new RuntimeException(
                        "Mapping jurnal sales_payment/{$key} belum di-seed (JournalMappingSeeder).",
                    );
                }
            }

            $lines[] = ['account_id' => $mappings[JournalMapping::KEY_HPP]->account_id, 'debit' => $cogs, 'credit' => 0];
            $lines[] = ['account_id' => $mappings[JournalMapping::KEY_PERSEDIAAN]->account_id, 'debit' => 0, 'credit' => $cogs];
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
     * HPP penjualan = Σ qty item × cost_price produk saat ini (approx —
     * items order hanya menyimpan snapshot harga jual).
     */
    private function salesCostOfGoodsSold(SalesOrder $order): float
    {
        return round($order->items->sum(
            fn ($item) => (float) $item->qty * (float) $item->product->cost_price,
        ), 2);
    }

    /**
     * Auto-jurnal penerimaan barang PO: Persediaan (D) = grand_total,
     * Hutang Vendor (C) = grand_total (pajak beli masuk ke persediaan —
     * schema-database.md §8.3 tanpa akun PPN masukan). Akun dari
     * journal_mappings (transaction_type = purchase_received).
     *
     * @throws RuntimeException bila mapping wajib belum di-seed
     */
    public function postPurchaseReceived(PurchaseOrder $order, ?int $postedBy = null): JournalEntry
    {
        $mappings = $this->requireMappings(
            JournalMapping::TRANSACTION_TYPE_PURCHASE_RECEIVED,
            [JournalMapping::KEY_PERSEDIAAN, JournalMapping::KEY_HUTANG_VENDOR],
        );

        return $this->post(
            source: JournalEntry::SOURCE_PURCHASE_RECEIVED,
            entryDate: today()->toDateString(),
            description: "Penerimaan barang {$order->po_number}",
            lines: [
                ['account_id' => $mappings[JournalMapping::KEY_PERSEDIAAN]->account_id, 'debit' => (float) $order->grand_total, 'credit' => 0],
                ['account_id' => $mappings[JournalMapping::KEY_HUTANG_VENDOR]->account_id, 'debit' => 0, 'credit' => (float) $order->grand_total],
            ],
            referenceType: 'purchase_order',
            referenceId: $order->id,
            postedBy: $postedBy,
        );
    }

    /**
     * Auto-jurnal pembayaran vendor: Hutang Vendor (D) = amount payment,
     * Kas/Bank (C) = amount payment (per payment — cicilan tetap balance
     * per entry). Akun dari journal_mappings (transaction_type =
     * purchase_payment).
     *
     * @throws RuntimeException bila mapping wajib belum di-seed
     */
    public function postPurchasePayment(VendorPayment $payment, ?int $postedBy = null): JournalEntry
    {
        $mappings = $this->requireMappings(
            JournalMapping::TRANSACTION_TYPE_PURCHASE_PAYMENT,
            [JournalMapping::KEY_HUTANG_VENDOR, JournalMapping::KEY_KAS_BANK],
        );

        return $this->post(
            source: JournalEntry::SOURCE_PURCHASE_PAYMENT,
            entryDate: $payment->paid_at->toDateString(),
            description: 'Pembayaran '.$payment->vendorInvoice->vendor_invoice_number,
            lines: [
                ['account_id' => $mappings[JournalMapping::KEY_HUTANG_VENDOR]->account_id, 'debit' => (float) $payment->amount, 'credit' => 0],
                ['account_id' => $mappings[JournalMapping::KEY_KAS_BANK]->account_id, 'debit' => 0, 'credit' => (float) $payment->amount],
            ],
            referenceType: 'vendor_payment',
            referenceId: $payment->id,
            postedBy: $postedBy,
        );
    }

    /**
     * Ambil mapping akun wajib untuk satu transaction_type.
     *
     * @param  list<string>  $requiredKeys
     * @return array<string, JournalMapping>
     *
     * @throws RuntimeException bila ada key yang belum di-seed
     */
    private function requireMappings(string $transactionType, array $requiredKeys): array
    {
        $mappings = JournalMapping::query()
            ->where('transaction_type', $transactionType)
            ->get()
            ->keyBy('account_key');

        foreach ($requiredKeys as $requiredKey) {
            if (! $mappings->has($requiredKey)) {
                throw new RuntimeException(
                    "Mapping jurnal {$transactionType}/{$requiredKey} belum di-seed (JournalMappingSeeder).",
                );
            }
        }

        return $mappings->all();
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
