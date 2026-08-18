import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
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
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">Satuan</h1>
                    <Button asChild>
                        <Link href={create.url()}>Tambah Satuan</Link>
                    </Button>
                </div>

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

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">Nama</th>
                                <th className="px-4 py-2 font-medium">Singkatan</th>
                                <th className="px-4 py-2 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {units.data.map((unit) => (
                                <tr key={unit.id} className="border-t">
                                    <td className="px-4 py-2">{unit.name}</td>
                                    <td className="px-4 py-2">{unit.abbreviation}</td>
                                    <td className="px-4 py-2">
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
                                    <td colSpan={3} className="px-4 py-8 text-center text-neutral-500">
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
