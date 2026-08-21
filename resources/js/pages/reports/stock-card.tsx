import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
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

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

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
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Kartu Stok"
                    description="Riwayat mutasi satu produk — masuk, keluar, dan saldo."
                    actions={card !== null ? <ExportButtons from={card.from} to={card.to} product={card.product.id} /> : undefined}
                />

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
                    <span className="text-sm text-muted-foreground">s.d.</span>
                    <Input type="date" name="to" value={to} onChange={(e) => setTo(e.target.value)} className="w-40" aria-label="Sampai tanggal" />
                    <Button type="submit" variant="outline">
                        Tampilkan
                    </Button>
                </form>

                {card === null ? (
                    <p className="text-sm text-muted-foreground">Belum ada produk — buat produk terlebih dahulu.</p>
                ) : (
                    <>
                        <p className="text-sm text-muted-foreground">
                            {card.product.sku} — {card.product.name} ({card.product.unit}) · periode {card.from} s.d. {card.to}
                        </p>

                        <div className="overflow-x-auto rounded-xl border bg-card shadow-xs">
                            <table className="ledger-table w-full text-sm">
                                <thead className="text-left">
                                    <tr>
                                        <th className="px-4 py-2.5">Tanggal</th>
                                        <th className="px-4 py-2.5">Tipe</th>
                                        <th className="px-4 py-2.5 text-right">Qty ({card.product.unit})</th>
                                        <th className="px-4 py-2.5 text-right">Saldo ({card.product.unit})</th>
                                        <th className="px-4 py-2.5">Referensi</th>
                                        <th className="px-4 py-2.5">Catatan</th>
                                        <th className="px-4 py-2.5">Oleh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr className="bg-muted/40">
                                        <td className="px-4 py-2.5 font-medium" colSpan={3}>
                                            Saldo awal
                                        </td>
                                        <td className="px-4 py-2.5 text-right font-mono font-medium">
                                            {formatQty(card.opening, card.product.allows_fraction)}
                                        </td>
                                        <td colSpan={3} />
                                    </tr>
                                    {card.lines.map((line, index) => (
                                        <tr key={index}>
                                            <td className="px-4 py-2.5">{line.date}</td>
                                            <td className="px-4 py-2.5">{typeLabels[line.type] ?? line.type}</td>
                                            <td className="px-4 py-2.5 text-right font-mono">
                                                {formatQty(line.qty, card.product.allows_fraction)}
                                            </td>
                                            <td className="px-4 py-2.5 text-right font-mono">
                                                {formatQty(line.balance, card.product.allows_fraction)}
                                            </td>
                                            <td className="px-4 py-2.5">{line.reference ?? '-'}</td>
                                            <td className="px-4 py-2.5">{line.note ?? '-'}</td>
                                            <td className="px-4 py-2.5">{line.user ?? '-'}</td>
                                        </tr>
                                    ))}
                                    {card.lines.length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="px-4 py-4 text-center text-muted-foreground">
                                                Tidak ada mutasi pada periode ini.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                                <tfoot>
                                    <tr className="border-t border-dashed bg-muted/60 font-medium">
                                        <td className="px-4 py-2.5" colSpan={3}>
                                            Saldo akhir · masuk{' '}
                                            {formatQty(card.total_in, card.product.allows_fraction)} / keluar{' '}
                                            {formatQty(Math.abs(card.total_out), card.product.allows_fraction)}
                                        </td>
                                        <td className="px-4 py-2.5 text-right font-mono">
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
