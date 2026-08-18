import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
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
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">Kategori</h1>
                    <Button asChild>
                        <Link href={create.url()}>Tambah Kategori</Link>
                    </Button>
                </div>

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

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">Nama</th>
                                <th className="px-4 py-2 font-medium">Induk</th>
                                <th className="px-4 py-2 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {categories.data.map((category) => (
                                <tr key={category.id} className="border-t">
                                    <td className="px-4 py-2">{category.name}</td>
                                    <td className="px-4 py-2">{category.parent?.name ?? '-'}</td>
                                    <td className="px-4 py-2">
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
                                    <td colSpan={3} className="px-4 py-8 text-center text-neutral-500">
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
