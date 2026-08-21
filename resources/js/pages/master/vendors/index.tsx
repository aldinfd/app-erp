import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, destroy, edit, index } from '@/routes/vendors';
import type { Paginated, Vendor } from '@/types';

type Props = {
    vendors: Paginated<Vendor>;
    filters: { q?: string; status?: string };
};

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

export default function VendorsIndex({ vendors, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');
    const [status, setStatus] = React.useState(filters.status ?? '');

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(
            index.url(),
            { q: q || undefined, status: status || undefined },
            { preserveState: true },
        );
    }

    function handleDelete(vendor: Vendor) {
        if (confirm(`Hapus vendor "${vendor.name}"?`)) {
            router.delete(destroy.url({ vendor: vendor.id }));
        }
    }

    return (
        <>
            <Head title="Vendor" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Vendor"
                    actions={
                        <Button asChild>
                            <Link href={create.url()}>Tambah Vendor</Link>
                        </Button>
                    }
                />

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nama / email / telepon…"
                        className="max-w-xs"
                    />
                    <select
                        name="status"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className={selectClass}
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-xl border bg-card shadow-xs">
                    <table className="ledger-table w-full text-sm">
                        <thead className="text-left">
                            <tr>
                                <th className="px-4 py-2.5">Nama</th>
                                <th className="px-4 py-2.5">Email</th>
                                <th className="px-4 py-2.5">Telepon</th>
                                <th className="px-4 py-2.5">Status</th>
                                <th className="px-4 py-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {vendors.data.map((vendor) => (
                                <tr key={vendor.id}>
                                    <td className="px-4 py-2.5 font-medium">{vendor.name}</td>
                                    <td className="px-4 py-2.5">{vendor.email ?? '-'}</td>
                                    <td className="px-4 py-2.5 font-mono text-xs">{vendor.phone ?? '-'}</td>
                                    <td className="px-4 py-2.5">
                                        <Badge variant={vendor.is_active ? 'default' : 'secondary'}>
                                            {vendor.is_active ? 'Aktif' : 'Nonaktif'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2.5">
                                        <div className="flex gap-2">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={edit.url({ vendor: vendor.id })}>Edit</Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" onClick={() => handleDelete(vendor)}>
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {vendors.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                        Tidak ada vendor.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination items={vendors} />
            </div>
        </>
    );
}

VendorsIndex.layout = {
    breadcrumbs: [{ title: 'Vendor', href: index.url() }],
};
