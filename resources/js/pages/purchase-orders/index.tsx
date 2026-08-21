import * as React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index, create, show } from '@/routes/purchase-orders';
import type { PaginatedPurchaseOrders, PurchaseOrderStatus } from '@/types';
import { purchaseOrderStatusLabels, purchaseOrderStatusVariants } from './status';

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

type Props = {
    purchaseOrders: PaginatedPurchaseOrders;
    statuses: PurchaseOrderStatus[];
    filters: { q?: string; status?: string };
};

export default function PurchaseOrdersIndex({ purchaseOrders, statuses, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');
    const [status, setStatus] = React.useState(filters.status ?? '');

    // Buat PO hanya admin & staff_gudang; halaman ini juga dilihat
    // staff_finance untuk mencatat invoice/pembayaran.
    const canCreate = usePage().props.auth.roles.some((role) => role === 'admin' || role === 'staff_gudang');

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(
            index.url(),
            {
                q: q || undefined,
                status: status || undefined,
            },
            { preserveState: true },
        );
    }

    return (
        <>
            <Head title="Purchase Order" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Purchase Order"
                    description="Pesan barang ke vendor — dari PO sampai pembayaran."
                    actions={
                        canCreate ? (
                            <Button asChild size="sm">
                                <Link href={create.url()}>+ PO Baru</Link>
                            </Button>
                        ) : undefined
                    }
                />

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nomor PO / nama vendor…"
                        className="max-w-xs"
                    />
                    <select
                        name="status"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className={selectClass}
                    >
                        <option value="">Semua status</option>
                        {statuses.map((status) => (
                            <option key={status} value={status}>
                                {purchaseOrderStatusLabels[status]}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-xl border bg-card shadow-xs">
                    <table className="ledger-table w-full text-sm">
                        <thead className="text-left">
                            <tr>
                                <th className="px-4 py-2.5">Nomor PO</th>
                                <th className="px-4 py-2.5">Tanggal</th>
                                <th className="px-4 py-2.5">Vendor</th>
                                <th className="px-4 py-2.5">Status</th>
                                <th className="px-4 py-2.5 text-right">Total</th>
                                <th className="px-4 py-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {purchaseOrders.data.map((order) => (
                                <tr key={order.id}>
                                    <td className="px-4 py-2.5 font-mono text-xs font-medium">{order.po_number}</td>
                                    <td className="px-4 py-2.5 whitespace-nowrap">{formatDate(order.order_date)}</td>
                                    <td className="px-4 py-2.5 font-medium">{order.vendor?.name ?? '-'}</td>
                                    <td className="px-4 py-2.5">
                                        <Badge variant={purchaseOrderStatusVariants[order.status]}>
                                            {purchaseOrderStatusLabels[order.status]}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-mono whitespace-nowrap">
                                        {formatCurrency(order.grand_total)}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={show.url({ purchase_order: order.id })}>Detail</Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {purchaseOrders.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                        Tidak ada purchase order.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination items={purchaseOrders} />
            </div>
        </>
    );
}

PurchaseOrdersIndex.layout = {
    breadcrumbs: [{ title: 'Purchase Order', href: index.url() }],
};
