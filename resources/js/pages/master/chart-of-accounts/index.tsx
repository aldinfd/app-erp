import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, destroy, edit, index } from '@/routes/chart-of-accounts';
import type { ChartOfAccount, Paginated } from '@/types';

type Props = {
    accounts: Paginated<ChartOfAccount>;
    types: string[];
    filters: { q?: string; type?: string };
};

const TYPE_LABELS: Record<string, string> = {
    asset: 'Aset',
    liability: 'Liabilitas',
    equity: 'Ekuitas',
    revenue: 'Pendapatan',
    expense: 'Beban',
};

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

export default function ChartOfAccountsIndex({ accounts, types, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');
    const [type, setType] = React.useState(filters.type ?? '');

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(
            index.url(),
            { q: q || undefined, type: type || undefined },
            { preserveState: true },
        );
    }

    function handleDelete(account: ChartOfAccount) {
        if (confirm(`Hapus akun "${account.code} — ${account.name}"?`)) {
            router.delete(destroy.url({ chart_of_account: account.id }));
        }
    }

    return (
        <>
            <Head title="Chart of Accounts" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Chart of Accounts"
                    description="Bagan akun double-entry."
                    actions={
                        <Button asChild>
                            <Link href={create.url()}>Tambah Akun</Link>
                        </Button>
                    }
                />

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari kode / nama akun…"
                        className="max-w-xs"
                    />
                    <select
                        name="type"
                        value={type}
                        onChange={(e) => setType(e.target.value)}
                        className={selectClass}
                    >
                        <option value="">Semua tipe</option>
                        {types.map((typeOption) => (
                            <option key={typeOption} value={typeOption}>
                                {TYPE_LABELS[typeOption] ?? typeOption}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-xl border bg-card shadow-xs">
                    <table className="ledger-table w-full text-sm">
                        <thead className="text-left">
                            <tr>
                                <th className="px-4 py-2.5">Kode</th>
                                <th className="px-4 py-2.5">Nama</th>
                                <th className="px-4 py-2.5">Tipe</th>
                                <th className="px-4 py-2.5">Induk</th>
                                <th className="px-4 py-2.5">Postable</th>
                                <th className="px-4 py-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {accounts.data.map((account) => (
                                <tr key={account.id}>
                                    <td className="px-4 py-2.5 font-mono text-xs">{account.code}</td>
                                    <td className="px-4 py-2.5 font-medium">{account.name}</td>
                                    <td className="px-4 py-2.5">{TYPE_LABELS[account.type] ?? account.type}</td>
                                    <td className="px-4 py-2.5 font-mono text-xs">{account.parent?.code ?? '-'}</td>
                                    <td className="px-4 py-2.5">
                                        <Badge variant={account.is_postable ? 'default' : 'secondary'}>
                                            {account.is_postable ? 'Ya' : 'Header'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2.5">
                                        <div className="flex gap-2">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={edit.url({ chart_of_account: account.id })}>Edit</Link>
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => handleDelete(account)}
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {accounts.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                        Tidak ada akun.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination items={accounts} />
            </div>
        </>
    );
}

ChartOfAccountsIndex.layout = {
    breadcrumbs: [{ title: 'Chart of Accounts', href: index.url() }],
};
