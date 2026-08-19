import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index } from '@/routes/general-ledger';
import type { Ledger, LedgerAccount } from '@/types';

type Props = {
    accounts: LedgerAccount[];
    account: LedgerAccount | null;
    ledger: Ledger;
    filters: { account_id?: string; from?: string; to?: string };
};

const accountTypeLabels: Record<string, string> = {
    asset: 'Aset',
    liability: 'Liabilitas',
    equity: 'Ekuitas',
    revenue: 'Pendapatan',
    expense: 'Beban',
};

export default function GeneralLedgerIndex({ accounts, account, ledger, filters }: Props) {
    const [accountId, setAccountId] = React.useState(filters.account_id ?? (account ? String(account.id) : ''));
    const [from, setFrom] = React.useState(filters.from ?? '');
    const [to, setTo] = React.useState(filters.to ?? '');

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(
            index.url(),
            {
                account_id: accountId || undefined,
                from: from || undefined,
                to: to || undefined,
            },
            { preserveState: true },
        );
    }

    const selectClass = 'border-input bg-background h-9 rounded-md border px-3 text-sm';

    return (
        <>
            <Head title="Buku Besar" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Buku Besar</h1>

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <select
                        name="account_id"
                        value={accountId}
                        onChange={(e) => setAccountId(e.target.value)}
                        className={selectClass}
                    >
                        {accounts.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.code} — {option.name}
                            </option>
                        ))}
                    </select>
                    <Input type="date" name="from" value={from} onChange={(e) => setFrom(e.target.value)} className="w-40" aria-label="Dari tanggal" />
                    <Input type="date" name="to" value={to} onChange={(e) => setTo(e.target.value)} className="w-40" aria-label="Sampai tanggal" />
                    <Button type="submit" variant="outline">
                        Tampilkan
                    </Button>
                </form>

                {account && (
                    <div className="text-sm text-neutral-500">
                        Akun <span className="font-mono text-xs text-foreground">{account.code}</span> {account.name}
                        {accountTypeLabels[account.type] ? ` (${accountTypeLabels[account.type]})` : ''}
                    </div>
                )}

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">Tanggal</th>
                                <th className="px-4 py-2 font-medium">Nomor Jurnal</th>
                                <th className="px-4 py-2 font-medium">Deskripsi</th>
                                <th className="px-4 py-2 font-medium text-right">Debit</th>
                                <th className="px-4 py-2 font-medium text-right">Kredit</th>
                                <th className="px-4 py-2 font-medium text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr className="border-t">
                                <td colSpan={5} className="px-4 py-2 text-neutral-500">
                                    Saldo awal{from ? ` (sebelum ${formatDate(from)})` : ''}
                                </td>
                                <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(ledger.opening)}</td>
                            </tr>
                            {ledger.lines.map((line, index) => (
                                <tr key={`${line.entry_number}-${index}`} className="border-t">
                                    <td className="px-4 py-2 whitespace-nowrap">{formatDate(line.entry_date)}</td>
                                    <td className="px-4 py-2 font-mono text-xs">{line.entry_number}</td>
                                    <td className="px-4 py-2">{line.description}</td>
                                    <td className="px-4 py-2 text-right tabular-nums">{line.debit > 0 ? formatCurrency(line.debit) : '-'}</td>
                                    <td className="px-4 py-2 text-right tabular-nums">{line.credit > 0 ? formatCurrency(line.credit) : '-'}</td>
                                    <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(line.balance)}</td>
                                </tr>
                            ))}
                            {ledger.lines.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-neutral-500">
                                        Tidak ada mutasi pada rentang ini.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        <tfoot>
                            <tr className="border-t bg-neutral-50 dark:bg-neutral-900">
                                <td colSpan={3} className="px-4 py-2 font-medium">Total mutasi</td>
                                <td className="px-4 py-2 text-right font-medium tabular-nums">{formatCurrency(ledger.total_debit)}</td>
                                <td className="px-4 py-2 text-right font-medium tabular-nums">{formatCurrency(ledger.total_credit)}</td>
                                <td className="px-4 py-2 text-right font-medium tabular-nums">{formatCurrency(ledger.closing)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </>
    );
}

GeneralLedgerIndex.layout = {
    breadcrumbs: [{ title: 'Buku Besar', href: index.url() }],
};
