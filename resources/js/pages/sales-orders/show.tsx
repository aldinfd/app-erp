import { Head, router } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate, formatQty } from '@/lib/utils';
import { index, cancel } from '@/routes/sales-orders';
import type { SalesOrderDetail } from '@/types';
import {
    invoiceStatusLabels,
    invoiceStatusVariants,
    paymentMethodLabels,
    paymentStatusLabels,
    salesOrderStatusLabels,
    salesOrderStatusVariants,
} from './status';

type Props = {
    order: SalesOrderDetail;
    canCancel: boolean;
};

export default function SalesOrderShow({ order, canCancel }: Props) {
    function handleCancel() {
        if (confirm(`Batalkan order ${order.order_number}? Invoice akan di-void dan payment pending dibatalkan.`)) {
            router.post(cancel.url({ sales_order: order.id }));
        }
    }

    return (
        <>
            <Head title={`Order ${order.order_number}`} />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title={order.order_number}
                    description="Sales Order"
                    actions={
                        <>
                            <Badge variant={salesOrderStatusVariants[order.status]}>
                                {salesOrderStatusLabels[order.status]}
                            </Badge>
                            {canCancel && (
                                <Button variant="destructive" size="sm" onClick={handleCancel}>
                                    Batalkan Order
                                </Button>
                            )}
                        </>
                    }
                />

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Data customer */}
                    <section className="rounded-xl border bg-card p-4 shadow-xs">
                        <h2 className="mb-3 font-mono text-[10px] tracking-[0.14em] uppercase text-muted-foreground">
                            Customer
                        </h2>
                        <dl className="space-y-1 text-sm">
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Nama</dt>
                                <dd>{order.customer?.name ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Email</dt>
                                <dd className="truncate">{order.customer?.email ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Telepon</dt>
                                <dd>{order.customer?.phone ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Alamat</dt>
                                <dd className="mt-1">{order.customer?.address ?? '-'}</dd>
                            </div>
                        </dl>
                    </section>

                    {/* Invoice */}
                    <section className="rounded-xl border bg-card p-4 shadow-xs">
                        <h2 className="mb-3 font-mono text-[10px] tracking-[0.14em] uppercase text-muted-foreground">
                            Invoice
                        </h2>
                        {order.invoice ? (
                            <dl className="space-y-1 text-sm">
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">Nomor</dt>
                                    <dd className="font-mono text-xs">{order.invoice.invoice_number}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">Terbit</dt>
                                    <dd>{formatDate(order.invoice.issued_date)}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">Jumlah</dt>
                                    <dd className="font-mono">{formatCurrency(order.invoice.amount)}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">Dibayar</dt>
                                    <dd className="font-mono">{formatCurrency(order.invoice.amount_paid)}</dd>
                                </div>
                                <div className="flex items-center justify-between gap-4">
                                    <dt className="text-muted-foreground">Status</dt>
                                    <dd>
                                        <Badge variant={invoiceStatusVariants[order.invoice.status]}>
                                            {invoiceStatusLabels[order.invoice.status]}
                                        </Badge>
                                    </dd>
                                </div>
                            </dl>
                        ) : (
                            <p className="text-sm text-muted-foreground">Belum ada invoice.</p>
                        )}
                    </section>

                    {/* Ringkasan nominal */}
                    <section className="rounded-xl border bg-card p-4 shadow-xs">
                        <h2 className="mb-3 font-mono text-[10px] tracking-[0.14em] uppercase text-muted-foreground">
                            Ringkasan
                        </h2>
                        <dl className="space-y-1 text-sm">
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Tanggal</dt>
                                <dd>{formatDate(order.order_date)}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Subtotal</dt>
                                <dd className="font-mono">{formatCurrency(order.subtotal)}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Pajak</dt>
                                <dd className="font-mono">{formatCurrency(order.tax)}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Ongkir</dt>
                                <dd className="font-mono">{formatCurrency(order.shipping)}</dd>
                            </div>
                            <div className="mt-2 flex justify-between gap-4 border-t border-dashed pt-2 font-semibold">
                                <dt>Total</dt>
                                <dd className="font-mono">{formatCurrency(order.grand_total)}</dd>
                            </div>
                        </dl>
                        {order.notes && (
                            <p className="mt-3 border-t border-dashed pt-3 text-xs text-muted-foreground">{order.notes}</p>
                        )}
                    </section>
                </div>

                {/* Item pesanan */}
                <section className="overflow-hidden rounded-xl border bg-card shadow-xs">
                    <h2 className="border-b border-dashed px-4 py-3 font-mono text-[10px] tracking-[0.14em] uppercase text-muted-foreground">
                        Item Pesanan
                    </h2>
                    <div className="overflow-x-auto">
                        <table className="ledger-table w-full text-sm">
                            <thead className="text-left">
                                <tr>
                                    <th className="px-4 py-2.5">SKU</th>
                                    <th className="px-4 py-2.5">Produk</th>
                                    <th className="px-4 py-2.5 text-right">Qty</th>
                                    <th className="px-4 py-2.5 text-right">Harga</th>
                                    <th className="px-4 py-2.5 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                {order.items.map((item) => {
                                    const unit = item.product?.unit ?? null;

                                    return (
                                        <tr key={item.id}>
                                            <td className="px-4 py-2.5 font-mono text-xs">{item.product?.sku ?? '-'}</td>
                                            <td className="px-4 py-2.5 font-medium">{item.product?.name ?? '-'}</td>
                                            <td className="px-4 py-2.5 text-right font-mono">
                                                {formatQty(item.qty, unit?.allows_fraction ?? false)}{' '}
                                                {unit?.abbreviation ?? ''}
                                            </td>
                                            <td className="px-4 py-2.5 text-right font-mono whitespace-nowrap">
                                                {formatCurrency(item.unit_price)}
                                            </td>
                                            <td className="px-4 py-2.5 text-right font-mono whitespace-nowrap">
                                                {formatCurrency(item.subtotal)}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </section>

                {/* Riwayat payment */}
                <section className="overflow-hidden rounded-xl border bg-card shadow-xs">
                    <h2 className="border-b border-dashed px-4 py-3 font-mono text-[10px] tracking-[0.14em] uppercase text-muted-foreground">
                        Riwayat Payment
                    </h2>
                    <div className="overflow-x-auto">
                        <table className="ledger-table w-full text-sm">
                            <thead className="text-left">
                                <tr>
                                    <th className="px-4 py-2.5">Metode</th>
                                    <th className="px-4 py-2.5">Referensi Gateway</th>
                                    <th className="px-4 py-2.5 text-right">Jumlah</th>
                                    <th className="px-4 py-2.5">Status</th>
                                    <th className="px-4 py-2.5">Dibayar Pada</th>
                                </tr>
                            </thead>
                            <tbody>
                                {order.payments.map((payment) => (
                                    <tr key={payment.id}>
                                        <td className="px-4 py-2.5">
                                            {paymentMethodLabels[payment.method] ?? payment.method}
                                        </td>
                                        <td className="px-4 py-2.5 font-mono text-xs">{payment.gateway_ref ?? '-'}</td>
                                        <td className="px-4 py-2.5 text-right font-mono whitespace-nowrap">
                                            {formatCurrency(payment.amount)}
                                        </td>
                                        <td className="px-4 py-2.5">{paymentStatusLabels[payment.status]}</td>
                                        <td className="px-4 py-2.5 whitespace-nowrap">
                                            {payment.paid_at ? formatDate(payment.paid_at) : '-'}
                                        </td>
                                    </tr>
                                ))}
                                {order.payments.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                            Belum ada payment.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </>
    );
}

SalesOrderShow.layout = {
    breadcrumbs: [
        { title: 'Sales Order', href: index.url() },
        { title: 'Detail Order' },
    ],
};
