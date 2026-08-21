import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, destroy, edit, index } from '@/routes/categories';
import type { Category, Paginated } from '@/types';

type Props = {
    categories: Paginated<Category>;
    filters: { q?: string };
};

export default function CategoriesIndex({ categories, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');

    function applySearch(event: React.FormEvent) {
        event.preventDefault();
        router.get(index.url(), { q: q || undefined }, { preserveState: true });
    }

    function handleDelete(category: Category) {
        if (confirm(`Hapus kategori "${category.name}"?`)) {
            router.delete(destroy.url({ category: category.id }));
        }
    }

    return (
        <>
            <Head title="Kategori" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Kategori"
                    actions={
                        <Button asChild>
                            <Link href={create.url()}>Tambah Kategori</Link>
                        </Button>
                    }
                />

                <form onSubmit={applySearch} className="flex max-w-sm gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nama kategori…"
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
                                <th className="px-4 py-2.5">Induk</th>
                                <th className="px-4 py-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {categories.data.map((category) => (
                                <tr key={category.id}>
                                    <td className="px-4 py-2.5 font-medium">{category.name}</td>
                                    <td className="px-4 py-2.5">{category.parent?.name ?? '-'}</td>
                                    <td className="px-4 py-2.5">
                                        <div className="flex gap-2">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={edit.url({ category: category.id })}>Edit</Link>
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => handleDelete(category)}
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {categories.data.length === 0 && (
                                <tr>
                                    <td colSpan={3} className="px-4 py-8 text-center text-muted-foreground">
                                        Tidak ada kategori.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination items={categories} />
            </div>
        </>
    );
}

CategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Kategori', href: index.url() }],
};
