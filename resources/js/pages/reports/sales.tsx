import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
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
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Laporan Penjualan"
                    description="Semua order pada satu periode — siap di-export."
                    actions={<ExportButtons from={report.from} to={report.to} />}
                />

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input type="date" name="from" value={from} onChange={(e) => setFrom(e.target.value)} className="w-40" aria-label="Dari tanggal" />
                    <span className="text-sm text-muted-foreground">s.d.</span>
                    <Input type="date" name="to" value={to} onChange={(e) => setTo(e.target.value)} className="w-40" aria-label="Sampai tanggal" />
                    <Button type="submit" variant="outline">
                        Tampilkan
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
                                <th className="px-4 py-2.5 text-right">Subtotal</th>
                                <th className="px-4 py-2.5 text-right">Pajak</th>
                                <th className="px-4 py-2.5 text-right">Ongkir</th>
                                <th className="px-4 py-2.5 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.orders.map((order) => (
                                <tr key={order.order_number}>
                                    <td className="px-4 py-2.5 font-mono text-xs font-medium">{order.order_number}</td>
                                    <td className="px-4 py-2.5">{order.order_date}</td>
                                    <td className="px-4 py-2.5 font-medium">{order.customer_name}</td>
                                    <td className="px-4 py-2.5">
                                        <Badge variant={salesOrderStatusVariants[order.status as keyof typeof salesOrderStatusVariants] ?? 'outline'}>
                                            {salesOrderStatusLabels[order.status as keyof typeof salesOrderStatusLabels] ?? order.status}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(order.subtotal)}</td>
                                    <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(order.tax)}</td>
                                    <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(order.shipping)}</td>
                                    <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(order.grand_total)}</td>
                                </tr>
                            ))}
                            {report.orders.length === 0 && (
                                <tr>
                                    <td colSpan={8} className="px-4 py-4 text-center text-muted-foreground">
                                        Tidak ada order pada periode ini.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        <tfoot>
                            <tr className="border-t border-dashed bg-muted/60 font-medium">
                                <td className="px-4 py-2.5" colSpan={4}>
                                    Total {report.total_orders} order
                                </td>
                                <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(report.total_subtotal)}</td>
                                <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(report.total_tax)}</td>
                                <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(report.total_shipping)}</td>
                                <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(report.total_grand_total)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <p className="text-sm text-muted-foreground">Order dibatalkan tidak dihitung dalam laporan penjualan.</p>
            </div>
        </>
    );
}

SalesReport.layout = {
    breadcrumbs: [{ title: 'Laporan Penjualan', href: sales.url() }],
};
