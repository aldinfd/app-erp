import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency } from '@/lib/utils';
import { sales } from '@/routes/reports';
import { excel as salesExcel } from '@/routes/reports/sales';
import { pdf as salesPdf } from '@/routes/reports/sales';
import { salesOrderStatusLabels, salesOrderStatusVariants } from '@/pages/sales-orders/status';
import type { SalesReport } from '@/types';

type Props = {
    report: SalesReport;
    filters: { from: string; to: string };
};

/**
 * Tombol export: <a> polos (bukan <Link> Inertia) supaya browser
 * mengunduh file PDF/Excel, bukan mengunjunginya sebagai halaman.
 */
function ExportButtons({ from, to }: { from: string; to: string }) {
    const query = { query: { from: from || undefined, to: to || undefined } };

    return (
        <div className="flex items-center gap-2">
            <Button asChild variant="outline">
                <a href={salesPdf.url(query)}>Export PDF</a>
            </Button>
            <Button asChild variant="outline">
                <a href={salesExcel.url(query)}>Export Excel</a>
            </Button>
        </div>
    );
}

export default function SalesReport({ report, filters }: Props) {
    const [from, setFrom] = React.useState(filters.from);
    const [to, setTo] = React.useState(filters.to);

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(sales.url(), { from: from || undefined, to: to || undefined }, { preserveState: true });
    }

    return (
        <>
            <Head title="Laporan Penjualan" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">Laporan Penjualan</h1>
                    <ExportButtons from={report.from} to={report.to} />
                </div>

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input type="date" name="from" value={from} onChange={(e) => setFrom(e.target.value)} className="w-40" aria-label="Dari tanggal" />
                    <span className="text-sm text-neutral-500">s.d.</span>
                    <Input type="date" name="to" value={to} onChange={(e) => setTo(e.target.value)} className="w-40" aria-label="Sampai tanggal" />
                    <Button type="submit" variant="outline">
                        Tampilkan
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-neutral-50 dark:bg-neutral-900">
                                <th className="px-4 py-2 text-left font-medium">Nomor Order</th>
                                <th className="px-4 py-2 text-left font-medium">Tanggal</th>
                                <th className="px-4 py-2 text-left font-medium">Customer</th>
                                <th className="px-4 py-2 text-left font-medium">Status</th>
                                <th className="px-4 py-2 text-right font-medium">Subtotal</th>
                                <th className="px-4 py-2 text-right font-medium">Pajak</th>
                                <th className="px-4 py-2 text-right font-medium">Ongkir</th>
                                <th className="px-4 py-2 text-right font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.orders.map((order) => (
                                <tr key={order.order_number} className="border-b last:border-b-0">
                                    <td className="px-4 py-2 font-mono text-xs">{order.order_number}</td>
                                    <td className="px-4 py-2">{order.order_date}</td>
                                    <td className="px-4 py-2">{order.customer_name}</td>
                                    <td className="px-4 py-2">
                                        <Badge variant={salesOrderStatusVariants[order.status as keyof typeof salesOrderStatusVariants] ?? 'outline'}>
                                            {salesOrderStatusLabels[order.status as keyof typeof salesOrderStatusLabels] ?? order.status}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(order.subtotal)}</td>
                                    <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(order.tax)}</td>
                                    <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(order.shipping)}</td>
                                    <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(order.grand_total)}</td>
                                </tr>
                            ))}
                            {report.orders.length === 0 && (
                                <tr>
                                    <td colSpan={8} className="px-4 py-4 text-center text-neutral-500">
                                        Tidak ada order pada periode ini.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        <tfoot>
                            <tr className="border-t bg-neutral-50 font-medium dark:bg-neutral-900">
                                <td className="px-4 py-2" colSpan={4}>
                                    Total {report.total_orders} order
                                </td>
                                <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(report.total_subtotal)}</td>
                                <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(report.total_tax)}</td>
                                <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(report.total_shipping)}</td>
                                <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(report.total_grand_total)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <p className="text-sm text-neutral-500">Order dibatalkan tidak dihitung dalam laporan penjualan.</p>
            </div>
        </>
    );
}

SalesReport.layout = {
    breadcrumbs: [{ title: 'Laporan Penjualan', href: sales.url() }],
};
