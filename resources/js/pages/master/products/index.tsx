import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, destroy, edit, index } from '@/routes/products';
import { formatCurrency, formatQty } from '@/lib/utils';
import type { Category, Paginated, Product, Unit } from '@/types';

type Props = {
    products: Paginated<Product>;
    categories: Category[];
    filters: { q?: string; category_id?: string; status?: string };
};

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

export default function ProductsIndex({ products, categories, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');
    const [categoryId, setCategoryId] = React.useState(filters.category_id ?? '');
    const [status, setStatus] = React.useState(filters.status ?? '');

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(
            index.url(),
            {
                q: q || undefined,
                category_id: categoryId || undefined,
                status: status || undefined,
            },
            { preserveState: true },
        );
    }

    function handleDelete(product: Product) {
        if (confirm(`Hapus produk "${product.name}"?`)) {
            router.delete(destroy.url({ product: product.id }));
        }
    }

    return (
        <>
            <Head title="Produk" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Produk"
                    actions={
                        <Button asChild>
                            <Link href={create.url()}>Tambah Produk</Link>
                        </Button>
                    }
                />

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari SKU / nama…"
                        className="max-w-xs"
                    />
                    <select
                        name="category_id"
                        value={categoryId}
                        onChange={(e) => setCategoryId(e.target.value)}
                        className={selectClass}
                    >
                        <option value="">Semua kategori</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>
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
                                <th className="px-4 py-2.5">SKU</th>
                                <th className="px-4 py-2.5">Nama</th>
                                <th className="px-4 py-2.5">Kategori</th>
                                <th className="px-4 py-2.5">Satuan</th>
                                <th className="px-4 py-2.5 text-right">Harga Beli</th>
                                <th className="px-4 py-2.5 text-right">Harga Jual</th>
                                <th className="px-4 py-2.5 text-right">Stok</th>
                                <th className="px-4 py-2.5">Status</th>
                                <th className="px-4 py-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.data.map((product) => (
                                <tr key={product.id}>
                                    <td className="px-4 py-2.5 font-mono text-xs">{product.sku}</td>
                                    <td className="px-4 py-2.5 font-medium">{product.name}</td>
                                    <td className="px-4 py-2.5">{product.category?.name ?? '-'}</td>
                                    <td className="px-4 py-2.5">{product.unit?.abbreviation ?? '-'}</td>
                                    <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(product.cost_price)}</td>
                                    <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(product.selling_price)}</td>
                                    <td className="px-4 py-2.5 text-right font-mono">
                                        {formatQty(product.stock_qty, product.unit?.allows_fraction)}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        <Badge variant={product.is_active ? 'default' : 'secondary'}>
                                            {product.is_active ? 'Aktif' : 'Nonaktif'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2.5">
                                        <div className="flex gap-2">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={edit.url({ product: product.id })}>Edit</Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" onClick={() => handleDelete(product)}>
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {products.data.length === 0 && (
                                <tr>
                                    <td colSpan={9} className="px-4 py-8 text-center text-muted-foreground">
                                        Tidak ada produk.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination items={products} />
            </div>
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [{ title: 'Produk', href: index.url() }],
};
