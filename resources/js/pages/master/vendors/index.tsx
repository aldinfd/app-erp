import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, destroy, edit, index } from '@/routes/vendors';
import type { Paginated, Vendor } from '@/types';

type Props = {
    vendors: Paginated<Vendor>;
    filters: { q?: string; status?: string };
};

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
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">Vendor</h1>
                    <Button asChild>
                        <Link href={create.url()}>Tambah Vendor</Link>
                    </Button>
                </div>

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
                        className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">Nama</th>
                                <th className="px-4 py-2 font-medium">Email</th>
                                <th className="px-4 py-2 font-medium">Telepon</th>
                                <th className="px-4 py-2 font-medium">Status</th>
                                <th className="px-4 py-2 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {vendors.data.map((vendor) => (
                                <tr key={vendor.id} className="border-t">
                                    <td className="px-4 py-2">{vendor.name}</td>
                                    <td className="px-4 py-2">{vendor.email ?? '-'}</td>
                                    <td className="px-4 py-2">{vendor.phone ?? '-'}</td>
                                    <td className="px-4 py-2">
                                        <Badge variant={vendor.is_active ? 'default' : 'secondary'}>
                                            {vendor.is_active ? 'Aktif' : 'Nonaktif'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2">
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
                                    <td colSpan={5} className="px-4 py-8 text-center text-neutral-500">
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
