import { Head, router } from '@inertiajs/react';
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
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <h1 className="font-mono text-lg font-semibold">{order.order_number}</h1>
                        <Badge variant={salesOrderStatusVariants[order.status]}>
                            {salesOrderStatusLabels[order.status]}
                        </Badge>
                    </div>
                    {canCancel && (
                        <Button variant="destructive" size="sm" onClick={handleCancel}>
                            Batalkan Order
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Data customer */}
                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 text-sm font-semibold">Customer</h2>
                        <dl className="space-y-1 text-sm">
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Nama</dt>
                                <dd>{order.customer?.name ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Email</dt>
                                <dd className="truncate">{order.customer?.email ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Telepon</dt>
                                <dd>{order.customer?.phone ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-neutral-500">Alamat</dt>
                                <dd className="mt-1">{order.customer?.address ?? '-'}</dd>
                            </div>
                        </dl>
                    </section>

                    {/* Invoice */}
                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 text-sm font-semibold">Invoice</h2>
                        {order.invoice ? (
                            <dl className="space-y-1 text-sm">
                                <div className="flex justify-between gap-4">
                                    <dt className="text-neutral-500">Nomor</dt>
                                    <dd className="font-mono text-xs">{order.invoice.invoice_number}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-neutral-500">Terbit</dt>
                                    <dd>{formatDate(order.invoice.issued_date)}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-neutral-500">Jumlah</dt>
                                    <dd>{formatCurrency(order.invoice.amount)}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-neutral-500">Dibayar</dt>
                                    <dd>{formatCurrency(order.invoice.amount_paid)}</dd>
                                </div>
                                <div className="flex items-center justify-between gap-4">
                                    <dt className="text-neutral-500">Status</dt>
                                    <dd>
                                        <Badge variant={invoiceStatusVariants[order.invoice.status]}>
                                            {invoiceStatusLabels[order.invoice.status]}
                                        </Badge>
                                    </dd>
                                </div>
                            </dl>
                        ) : (
                            <p className="text-sm text-neutral-500">Belum ada invoice.</p>
                        )}
                    </section>

                    {/* Ringkasan nominal */}
                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 text-sm font-semibold">Ringkasan</h2>
                        <dl className="space-y-1 text-sm">
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Tanggal</dt>
                                <dd>{formatDate(order.order_date)}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Subtotal</dt>
                                <dd>{formatCurrency(order.subtotal)}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Pajak</dt>
                                <dd>{formatCurrency(order.tax)}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Ongkir</dt>
                                <dd>{formatCurrency(order.shipping)}</dd>
                            </div>
                            <div className="mt-2 flex justify-between gap-4 border-t pt-2 font-semibold">
                                <dt>Total</dt>
                                <dd>{formatCurrency(order.grand_total)}</dd>
                            </div>
                        </dl>
                        {order.notes && (
                            <p className="mt-3 border-t pt-3 text-xs text-neutral-500">{order.notes}</p>
                        )}
                    </section>
                </div>

                {/* Item pesanan */}
                <section className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">SKU</th>
                                <th className="px-4 py-2 font-medium">Produk</th>
                                <th className="px-4 py-2 font-medium text-right">Qty</th>
                                <th className="px-4 py-2 font-medium text-right">Harga</th>
                                <th className="px-4 py-2 font-medium text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {order.items.map((item) => {
                                const unit = item.product?.unit ?? null;

                                return (
                                    <tr key={item.id} className="border-t">
                                        <td className="px-4 py-2 font-mono text-xs">{item.product?.sku ?? '-'}</td>
                                        <td className="px-4 py-2">{item.product?.name ?? '-'}</td>
                                        <td className="px-4 py-2 text-right font-mono">
                                            {formatQty(item.qty, unit?.allows_fraction ?? false)}{' '}
                                            {unit?.abbreviation ?? ''}
                                        </td>
                                        <td className="px-4 py-2 text-right whitespace-nowrap">
                                            {formatCurrency(item.unit_price)}
                                        </td>
                                        <td className="px-4 py-2 text-right whitespace-nowrap">
                                            {formatCurrency(item.subtotal)}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </section>

                {/* Riwayat payment */}
                <section className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">Metode</th>
                                <th className="px-4 py-2 font-medium">Referensi Gateway</th>
                                <th className="px-4 py-2 font-medium text-right">Jumlah</th>
                                <th className="px-4 py-2 font-medium">Status</th>
                                <th className="px-4 py-2 font-medium">Dibayar Pada</th>
                            </tr>
                        </thead>
                        <tbody>
                            {order.payments.map((payment) => (
                                <tr key={payment.id} className="border-t">
                                    <td className="px-4 py-2">
                                        {paymentMethodLabels[payment.method] ?? payment.method}
                                    </td>
                                    <td className="px-4 py-2 font-mono text-xs">{payment.gateway_ref ?? '-'}</td>
                                    <td className="px-4 py-2 text-right whitespace-nowrap">
                                        {formatCurrency(payment.amount)}
                                    </td>
                                    <td className="px-4 py-2">{paymentStatusLabels[payment.status]}</td>
                                    <td className="px-4 py-2 whitespace-nowrap">
                                        {payment.paid_at ? formatDate(payment.paid_at) : '-'}
                                    </td>
                                </tr>
                            ))}
                            {order.payments.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-8 text-center text-neutral-500">
                                        Belum ada payment.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
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
