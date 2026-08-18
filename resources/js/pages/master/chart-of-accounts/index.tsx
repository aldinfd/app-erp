import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
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
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">Chart of Accounts</h1>
                    <Button asChild>
                        <Link href={create.url()}>Tambah Akun</Link>
                    </Button>
                </div>

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
                        className="border-input bg-background h-9 rounded-md border px-3 text-sm"
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

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">Kode</th>
                                <th className="px-4 py-2 font-medium">Nama</th>
                                <th className="px-4 py-2 font-medium">Tipe</th>
                                <th className="px-4 py-2 font-medium">Induk</th>
                                <th className="px-4 py-2 font-medium">Postable</th>
                                <th className="px-4 py-2 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {accounts.data.map((account) => (
                                <tr key={account.id} className="border-t">
                                    <td className="px-4 py-2 font-mono text-xs">{account.code}</td>
                                    <td className="px-4 py-2">{account.name}</td>
                                    <td className="px-4 py-2">{TYPE_LABELS[account.type] ?? account.type}</td>
                                    <td className="px-4 py-2">{account.parent?.code ?? '-'}</td>
                                    <td className="px-4 py-2">
                                        <Badge variant={account.is_postable ? 'default' : 'secondary'}>
                                            {account.is_postable ? 'Ya' : 'Header'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2">
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
                                    <td colSpan={6} className="px-4 py-8 text-center text-neutral-500">
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
