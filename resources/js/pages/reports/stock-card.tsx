import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatQty } from '@/lib/utils';
import { stockCard } from '@/routes/reports';
import { excel as stockCardExcel } from '@/routes/reports/stock-card';
import { pdf as stockCardPdf } from '@/routes/reports/stock-card';
import type { ProductOption, StockCard } from '@/types';

type Props = {
    products: ProductOption[];
    card: StockCard | null;
    filters: { from: string; to: string; product: number | null };
};

const typeLabels: Record<string, string> = {
    in: 'Masuk',
    out: 'Keluar',
    adjust: 'Penyesuaian',
};

/** Tombol export: <a> polos agar browser mengunduh, bukan visit Inertia. */
function ExportButtons({ from, to, product }: { from: string; to: string; product: number | null }) {
    const query = { query: { from: from || undefined, to: to || undefined, product: product ?? undefined } };

    return (
        <div className="flex items-center gap-2">
            <Button asChild variant="outline">
                <a href={stockCardPdf.url(query)}>Export PDF</a>
            </Button>
            <Button asChild variant="outline">
                <a href={stockCardExcel.url(query)}>Export Excel</a>
            </Button>
        </div>
    );
}

export default function StockCardPage({ products, card, filters }: Props) {
    const [from, setFrom] = React.useState(filters.from);
    const [to, setTo] = React.useState(filters.to);
    const [product, setProduct] = React.useState(filters.product?.toString() ?? '');

    const selectClass = 'border-input bg-background h-9 rounded-md border px-3 text-sm';

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(
            stockCard.url(),
            { from: from || undefined, to: to || undefined, product: product || undefined },
            { preserveState: true },
        );
    }

    return (
        <>
            <Head title="Kartu Stok" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">Kartu Stok</h1>
                    {card !== null && <ExportButtons from={card.from} to={card.to} product={card.product.id} />}
                </div>

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <select
                        name="product"
                        value={product}
                        onChange={(e) => setProduct(e.target.value)}
                        className={selectClass}
                        aria-label="Produk"
                    >
                        {products.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.sku} — {option.name}
                            </option>
                        ))}
                    </select>
                    <Input type="date" name="from" value={from} onChange={(e) => setFrom(e.target.value)} className="w-40" aria-label="Dari tanggal" />
                    <span className="text-sm text-neutral-500">s.d.</span>
                    <Input type="date" name="to" value={to} onChange={(e) => setTo(e.target.value)} className="w-40" aria-label="Sampai tanggal" />
                    <Button type="submit" variant="outline">
                        Tampilkan
                    </Button>
                </form>

                {card === null ? (
                    <p className="text-sm text-neutral-500">Belum ada produk — buat produk terlebih dahulu.</p>
                ) : (
                    <>
                        <p className="text-sm text-neutral-500">
                            {card.product.sku} — {card.product.name} ({card.product.unit}) · periode {card.from} s.d. {card.to}
                        </p>

                        <div className="overflow-x-auto rounded-lg border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-neutral-50 dark:bg-neutral-900">
                                        <th className="px-4 py-2 text-left font-medium">Tanggal</th>
                                        <th className="px-4 py-2 text-left font-medium">Tipe</th>
                                        <th className="px-4 py-2 text-right font-medium">Qty ({card.product.unit})</th>
                                        <th className="px-4 py-2 text-right font-medium">Saldo ({card.product.unit})</th>
                                        <th className="px-4 py-2 text-left font-medium">Referensi</th>
                                        <th className="px-4 py-2 text-left font-medium">Catatan</th>
                                        <th className="px-4 py-2 text-left font-medium">Oleh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr className="border-b bg-neutral-50/50 dark:bg-neutral-900/50">
                                        <td className="px-4 py-2 font-medium" colSpan={3}>
                                            Saldo awal
                                        </td>
                                        <td className="px-4 py-2 text-right font-medium tabular-nums">
                                            {formatQty(card.opening, card.product.allows_fraction)}
                                        </td>
                                        <td colSpan={3} />
                                    </tr>
                                    {card.lines.map((line, index) => (
                                        <tr key={index} className="border-b last:border-b-0">
                                            <td className="px-4 py-2">{line.date}</td>
                                            <td className="px-4 py-2">{typeLabels[line.type] ?? line.type}</td>
                                            <td className="px-4 py-2 text-right tabular-nums">
                                                {formatQty(line.qty, card.product.allows_fraction)}
                                            </td>
                                            <td className="px-4 py-2 text-right tabular-nums">
                                                {formatQty(line.balance, card.product.allows_fraction)}
                                            </td>
                                            <td className="px-4 py-2">{line.reference ?? '-'}</td>
                                            <td className="px-4 py-2">{line.note ?? '-'}</td>
                                            <td className="px-4 py-2">{line.user ?? '-'}</td>
                                        </tr>
                                    ))}
                                    {card.lines.length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="px-4 py-4 text-center text-neutral-500">
                                                Tidak ada mutasi pada periode ini.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                                <tfoot>
                                    <tr className="border-t bg-neutral-50 font-medium dark:bg-neutral-900">
                                        <td className="px-4 py-2" colSpan={3}>
                                            Saldo akhir · masuk{' '}
                                            {formatQty(card.total_in, card.product.allows_fraction)} / keluar{' '}
                                            {formatQty(Math.abs(card.total_out), card.product.allows_fraction)}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {formatQty(card.closing, card.product.allows_fraction)}
                                        </td>
                                        <td colSpan={3} />
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </>
                )}
            </div>
        </>
    );
}

StockCardPage.layout = {
    breadcrumbs: [{ title: 'Kartu Stok', href: stockCard.url() }],
};
