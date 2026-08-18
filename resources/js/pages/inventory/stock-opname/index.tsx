import * as React from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { index, adjust } from '@/routes/stock-opname';
import type { OpnameProduct, Paginated } from '@/types';

type Props = {
    products: Paginated<OpnameProduct>;
    filters: { q?: string };
};

function OpnameRow({
    product,
    showErrors,
    onRowSubmit,
}: {
    product: OpnameProduct;
    showErrors: boolean;
    onRowSubmit: (productId: number) => void;
}) {
    const { errors } = usePage().props;
    const [newQty, setNewQty] = React.useState('');
    const [note, setNote] = React.useState('');
    const [processing, setProcessing] = React.useState(false);

    function submit(event: React.FormEvent) {
        event.preventDefault();
        setProcessing(true);
        onRowSubmit(product.id);

        router.post(
            adjust.url(),
            {
                product_id: product.id,
                new_qty: newQty,
                note,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNewQty('');
                    setNote('');
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    const qtyError = showErrors ? errors.new_qty : undefined;
    const noteError = showErrors ? errors.note : undefined;
    const isLow = Number(product.stock_qty) <= Number(product.reorder_point);

    return (
        <tr className="border-t">
            <td className="px-4 py-2 font-mono text-xs">{product.sku}</td>
            <td className="px-4 py-2">{product.name}</td>
            <td className="px-4 py-2">{product.unit?.abbreviation ?? '-'}</td>
            <td className={`px-4 py-2 text-right font-mono ${isLow ? 'text-red-600 dark:text-red-400' : ''}`}>
                {product.stock_qty}
            </td>
            <td className="px-4 py-2 text-right font-mono">{product.reorder_point}</td>
            <td className="px-4 py-2">
                <form onSubmit={submit} className="flex flex-wrap items-center justify-end gap-2">
                    <div className="w-28">
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={newQty}
                            onChange={(e) => setNewQty(e.target.value)}
                            placeholder="Stok fisik"
                            required
                        />
                        {qtyError && <InputError message={qtyError} className="mt-1" />}
                    </div>
                    <div className="w-56">
                        <Input
                            value={note}
                            onChange={(e) => setNote(e.target.value)}
                            placeholder="Alasan penyesuaian (wajib)"
                            required
                        />
                        {noteError && <InputError message={noteError} className="mt-1" />}
                    </div>
                    <Button type="submit" size="sm" disabled={processing || newQty === ''}>
                        Simpan
                    </Button>
                </form>
            </td>
        </tr>
    );
}

export default function StockOpnameIndex({ products, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');
    // Hanya baris yang terakhir submit yang menampilkan error validasi.
    const [submittedProductId, setSubmittedProductId] = React.useState<number | null>(null);

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(index.url(), { q: q || undefined }, { preserveState: true });
    }

    return (
        <>
            <Head title="Stock Opname" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">Stock Opname</h1>
                    <p className="text-sm text-neutral-500">
                        Isi stok hasil hitung fisik — sistem mencatat selisihnya sebagai penyesuaian stok.
                    </p>
                </div>

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari SKU / nama produk…"
                        className="max-w-xs"
                    />
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">SKU</th>
                                <th className="px-4 py-2 font-medium">Nama</th>
                                <th className="px-4 py-2 font-medium">Satuan</th>
                                <th className="px-4 py-2 font-medium text-right">Stok Sistem</th>
                                <th className="px-4 py-2 font-medium text-right">Reorder Point</th>
                                <th className="px-4 py-2 font-medium text-right">Koreksi Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.data.map((product) => (
                                <OpnameRow
                                    key={product.id}
                                    product={product}
                                    showErrors={submittedProductId === product.id}
                                    onRowSubmit={setSubmittedProductId}
                                />
                            ))}
                            {products.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-neutral-500">
                                        Produk tidak ditemukan.
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

StockOpnameIndex.layout = {
    breadcrumbs: [{ title: 'Stock Opname', href: index.url() }],
};
