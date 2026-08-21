import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index, show } from '@/routes/sales-orders';
import type { PaginatedSalesOrders, SalesOrderStatus } from '@/types';
import { salesOrderStatusLabels, salesOrderStatusVariants } from './status';

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

type Props = {
    salesOrders: PaginatedSalesOrders;
    statuses: SalesOrderStatus[];
    filters: { q?: string; status?: string };
};

export default function SalesOrdersIndex({ salesOrders, statuses, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');
    const [status, setStatus] = React.useState(filters.status ?? '');

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
            <Head title="Sales Order" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader title="Sales Order" />

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nomor order / nama customer…"
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
                                {salesOrderStatusLabels[status]}
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
                                <th className="px-4 py-2.5">Nomor Order</th>
                                <th className="px-4 py-2.5">Tanggal</th>
                                <th className="px-4 py-2.5">Customer</th>
                                <th className="px-4 py-2.5">Status</th>
                                <th className="px-4 py-2.5 text-right">Total</th>
                                <th className="px-4 py-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {salesOrders.data.map((order) => (
                                <tr key={order.id}>
                                    <td className="px-4 py-2.5 font-mono text-xs font-medium">{order.order_number}</td>
                                    <td className="px-4 py-2.5 whitespace-nowrap">{formatDate(order.order_date)}</td>
                                    <td className="px-4 py-2.5 font-medium">{order.customer?.name ?? '-'}</td>
                                    <td className="px-4 py-2.5">
                                        <Badge variant={salesOrderStatusVariants[order.status]}>
                                            {salesOrderStatusLabels[order.status]}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-mono whitespace-nowrap">
                                        {formatCurrency(order.grand_total)}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={show.url({ sales_order: order.id })}>Detail</Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {salesOrders.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                        Tidak ada sales order.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination items={salesOrders} />
            </div>
        </>
    );
}

SalesOrdersIndex.layout = {
    breadcrumbs: [{ title: 'Sales Order', href: index.url() }],
};
