import * as React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index, create, show } from '@/routes/purchase-orders';
import type { PaginatedPurchaseOrders, PurchaseOrderStatus } from '@/types';
import { purchaseOrderStatusLabels, purchaseOrderStatusVariants } from './status';

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
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">Purchase Order</h1>
                    {canCreate && (
                        <Button asChild size="sm">
                            <Link href={create.url()}>+ PO Baru</Link>
                        </Button>
                    )}
                </div>

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
                        className="border-input bg-background h-9 rounded-md border px-3 text-sm"
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

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">Nomor PO</th>
                                <th className="px-4 py-2 font-medium">Tanggal</th>
                                <th className="px-4 py-2 font-medium">Vendor</th>
                                <th className="px-4 py-2 font-medium">Status</th>
                                <th className="px-4 py-2 font-medium text-right">Total</th>
                                <th className="px-4 py-2 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {purchaseOrders.data.map((order) => (
                                <tr key={order.id} className="border-t">
                                    <td className="px-4 py-2 font-mono text-xs">{order.po_number}</td>
                                    <td className="px-4 py-2 whitespace-nowrap">{formatDate(order.order_date)}</td>
                                    <td className="px-4 py-2">{order.vendor?.name ?? '-'}</td>
                                    <td className="px-4 py-2">
                                        <Badge variant={purchaseOrderStatusVariants[order.status]}>
                                            {purchaseOrderStatusLabels[order.status]}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2 text-right whitespace-nowrap">
                                        {formatCurrency(order.grand_total)}
                                    </td>
                                    <td className="px-4 py-2">
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={show.url({ purchase_order: order.id })}>Detail</Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {purchaseOrders.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-neutral-500">
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
