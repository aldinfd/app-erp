import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/stock-movements';
import { formatQty } from '@/lib/utils';
import type { PaginatedMovements, StockMovement, StockMovementType } from '@/types';

type Props = {
    movements: PaginatedMovements;
    types: StockMovementType[];
    filters: { q?: string; type?: string; date_from?: string; date_to?: string };
};

const TYPE_LABELS: Record<StockMovementType, string> = {
    in: 'Masuk',
    out: 'Keluar',
    adjust: 'Penyesuaian',
};

const REFERENCE_LABELS: Record<string, string> = {
    sales_order: 'Sales Order',
    purchase_order: 'Purchase Order',
    stock_opname: 'Stock Opname',
};

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

function formatSignedQty(movement: StockMovement): string {
    const qty = Number(movement.qty);
    const sign = qty > 0 ? '+' : '';

    return sign + formatQty(qty, movement.product?.unit?.allows_fraction);
}

function formatDateTime(iso: string): string {
    return new Date(iso).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function StockMovementsIndex({ movements, types, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');
    const [type, setType] = React.useState(filters.type ?? '');
    const [dateFrom, setDateFrom] = React.useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = React.useState(filters.date_to ?? '');

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(
            index.url(),
            {
                q: q || undefined,
                type: type || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true },
        );
    }

    return (
        <>
            <Head title="Riwayat Stok" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Riwayat Stok"
                    description="Perubahan stok hanya dicatat otomatis — tidak bisa diedit/dihapus."
                />

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari SKU / nama produk…"
                        className="max-w-xs"
                    />
                    <select
                        name="type"
                        value={type}
                        onChange={(e) => setType(e.target.value)}
                        className={selectClass}
                    >
                        <option value="">Semua tipe</option>
                        {types.map((movementType) => (
                            <option key={movementType} value={movementType}>
                                {TYPE_LABELS[movementType]}
                            </option>
                        ))}
                    </select>
                    <Input
                        type="date"
                        name="date_from"
                        value={dateFrom}
                        onChange={(e) => setDateFrom(e.target.value)}
                        className="w-40"
                    />
                    <Input
                        type="date"
                        name="date_to"
                        value={dateTo}
                        onChange={(e) => setDateTo(e.target.value)}
                        className="w-40"
                    />
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-xl border bg-card shadow-xs">
                    <table className="ledger-table w-full text-sm">
                        <thead className="text-left">
                            <tr>
                                <th className="px-4 py-2.5">Tanggal</th>
                                <th className="px-4 py-2.5">Produk</th>
                                <th className="px-4 py-2.5">Tipe</th>
                                <th className="px-4 py-2.5 text-right">Qty</th>
                                <th className="px-4 py-2.5 text-right">Stok Awal</th>
                                <th className="px-4 py-2.5 text-right">Stok Akhir</th>
                                <th className="px-4 py-2.5">Referensi</th>
                                <th className="px-4 py-2.5">Oleh</th>
                                <th className="px-4 py-2.5">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {movements.data.map((movement) => (
                                <tr key={movement.id}>
                                    <td className="px-4 py-2.5 font-mono text-xs whitespace-nowrap">{formatDateTime(movement.created_at)}</td>
                                    <td className="px-4 py-2.5">
                                        <span className="font-mono text-xs">{movement.product?.sku}</span>
                                        <br />
                                        {movement.product?.name}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        <Badge
                                            variant={
                                                movement.type === 'in' ? 'default' : movement.type === 'out' ? 'destructive' : 'secondary'
                                            }
                                        >
                                            {TYPE_LABELS[movement.type]}
                                        </Badge>
                                    </td>
                                    <td
                                        className={`px-4 py-2.5 text-right font-mono ${
                                            Number(movement.qty) < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'
                                        }`}
                                    >
                                        {formatSignedQty(movement)}
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-mono">
                                        {formatQty(movement.before_qty, movement.product?.unit?.allows_fraction)}
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-mono">
                                        {formatQty(movement.after_qty, movement.product?.unit?.allows_fraction)}
                                    </td>
                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                        {movement.reference_type
                                            ? `${REFERENCE_LABELS[movement.reference_type] ?? movement.reference_type}${movement.reference_id ? ` #${movement.reference_id}` : ''}`
                                            : '-'}
                                    </td>
                                    <td className="px-4 py-2.5 whitespace-nowrap">{movement.user?.name ?? 'Sistem'}</td>
                                    <td className="max-w-48 truncate px-4 py-2.5">{movement.note ?? '-'}</td>
                                </tr>
                            ))}
                            {movements.data.length === 0 && (
                                <tr>
                                    <td colSpan={9} className="px-4 py-8 text-center text-muted-foreground">
                                        Belum ada perubahan stok.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination items={movements} />
            </div>
        </>
    );
}

StockMovementsIndex.layout = {
    breadcrumbs: [{ title: 'Riwayat Stok', href: index.url() }],
};
