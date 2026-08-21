import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, destroy, edit, index } from '@/routes/units';
import type { Paginated, Unit } from '@/types';

type Props = {
    units: Paginated<Unit>;
    filters: { q?: string };
};

export default function UnitsIndex({ units, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');

    function applySearch(event: React.FormEvent) {
        event.preventDefault();
        router.get(index.url(), { q: q || undefined }, { preserveState: true });
    }

    function handleDelete(unit: Unit) {
        if (confirm(`Hapus satuan "${unit.name}"?`)) {
            router.delete(destroy.url({ unit: unit.id }));
        }
    }

    return (
        <>
            <Head title="Satuan" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Satuan"
                    actions={
                        <Button asChild>
                            <Link href={create.url()}>Tambah Satuan</Link>
                        </Button>
                    }
                />

                <form onSubmit={applySearch} className="flex max-w-sm gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nama / singkatan…"
                    />
                    <Button type="submit" variant="outline">
                        Cari
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-xl border bg-card shadow-xs">
                    <table className="ledger-table w-full text-sm">
                        <thead className="text-left">
                            <tr>
                                <th className="px-4 py-2.5">Nama</th>
                                <th className="px-4 py-2.5">Singkatan</th>
                                <th className="px-4 py-2.5">Boleh Pecahan</th>
                                <th className="px-4 py-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {units.data.map((unit) => (
                                <tr key={unit.id}>
                                    <td className="px-4 py-2.5 font-medium">{unit.name}</td>
                                    <td className="px-4 py-2.5 font-mono text-xs">{unit.abbreviation}</td>
                                    <td className="px-4 py-2.5">{unit.allows_fraction ? 'Ya' : 'Tidak'}</td>
                                    <td className="px-4 py-2.5">
                                        <div className="flex gap-2">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={edit.url({ unit: unit.id })}>Edit</Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" onClick={() => handleDelete(unit)}>
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {units.data.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                                        Tidak ada satuan.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination items={units} />
            </div>
        </>
    );
}

UnitsIndex.layout = {
    breadcrumbs: [{ title: 'Satuan', href: index.url() }],
};
