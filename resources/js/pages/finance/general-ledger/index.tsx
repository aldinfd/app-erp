import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
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

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

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

    return (
        <>
            <Head title="Buku Besar" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader title="Buku Besar" description="Mutasi dan saldo per akun." />

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
                    <div className="text-sm text-muted-foreground">
                        Akun <span className="font-mono text-xs text-foreground">{account.code}</span> {account.name}
                        {accountTypeLabels[account.type] ? ` (${accountTypeLabels[account.type]})` : ''}
                    </div>
                )}

                <div className="overflow-x-auto rounded-xl border bg-card shadow-xs">
                    <table className="ledger-table w-full text-sm">
                        <thead className="text-left">
                            <tr>
                                <th className="px-4 py-2.5">Tanggal</th>
                                <th className="px-4 py-2.5">Nomor Jurnal</th>
                                <th className="px-4 py-2.5">Deskripsi</th>
                                <th className="px-4 py-2.5 text-right">Debit</th>
                                <th className="px-4 py-2.5 text-right">Kredit</th>
                                <th className="px-4 py-2.5 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colSpan={5} className="px-4 py-2.5 text-muted-foreground">
                                    Saldo awal{from ? ` (sebelum ${formatDate(from)})` : ''}
                                </td>
                                <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(ledger.opening)}</td>
                            </tr>
                            {ledger.lines.map((line, index) => (
                                <tr key={`${line.entry_number}-${index}`}>
                                    <td className="px-4 py-2.5 whitespace-nowrap">{formatDate(line.entry_date)}</td>
                                    <td className="px-4 py-2.5 font-mono text-xs">{line.entry_number}</td>
                                    <td className="px-4 py-2.5">{line.description}</td>
                                    <td className="px-4 py-2.5 text-right font-mono">{line.debit > 0 ? formatCurrency(line.debit) : '-'}</td>
                                    <td className="px-4 py-2.5 text-right font-mono">{line.credit > 0 ? formatCurrency(line.credit) : '-'}</td>
                                    <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(line.balance)}</td>
                                </tr>
                            ))}
                            {ledger.lines.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                        Tidak ada mutasi pada rentang ini.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        <tfoot>
                            <tr className="border-t border-dashed bg-muted/60">
                                <td colSpan={3} className="px-4 py-2.5 font-medium">Total mutasi</td>
                                <td className="px-4 py-2.5 text-right font-mono font-medium">{formatCurrency(ledger.total_debit)}</td>
                                <td className="px-4 py-2.5 text-right font-mono font-medium">{formatCurrency(ledger.total_credit)}</td>
                                <td className="px-4 py-2.5 text-right font-mono font-medium">{formatCurrency(ledger.closing)}</td>
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
