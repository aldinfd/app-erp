import { Form, Head, router } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency, formatDate, formatQty } from '@/lib/utils';
import { index, ordered, receive, cancel } from '@/routes/purchase-orders';
import { store as storeVendorInvoice } from '@/routes/vendor-invoices';
import { store as storeVendorPayment } from '@/routes/vendor-invoices/payments';
import type { PurchaseOrderDetail } from '@/types';
import {
    purchaseOrderStatusLabels,
    purchaseOrderStatusVariants,
    vendorInvoiceStatusLabels,
    vendorInvoiceStatusVariants,
    vendorPaymentMethodLabels,
} from './status';

type Props = {
    order: PurchaseOrderDetail;
    canOrder: boolean;
    canReceive: boolean;
    canCancel: boolean;
    canRecordInvoice: boolean;
    canPay: boolean;
};

const selectClass = 'border-input bg-background h-9 rounded-md border px-3 text-sm';

export default function PurchaseOrderShow({ order, canOrder, canReceive, canCancel, canRecordInvoice, canPay }: Props) {
    const invoice = order.vendorInvoice;

    function handleStatusAction(action: 'ordered' | 'receive' | 'cancel') {
        const messages = {
            ordered: `Tandai PO ${order.po_number} dipesan ke vendor?`,
            receive: `Terima barang PO ${order.po_number}? Stok akan bertambah dan jurnal otomatis dibuat.`,
            cancel: `Batalkan PO ${order.po_number}?`,
        } as const;

        if (confirm(messages[action])) {
            const urls = {
                ordered: ordered.url({ purchase_order: order.id }),
                receive: receive.url({ purchase_order: order.id }),
                cancel: cancel.url({ purchase_order: order.id }),
            } as const;

            router.post(urls[action]);
        }
    }

    const remaining = invoice ? Number(invoice.amount) - Number(invoice.amount_paid) : 0;

    return (
        <>
            <Head title={`PO ${order.po_number}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <h1 className="font-mono text-lg font-semibold">{order.po_number}</h1>
                        <Badge variant={purchaseOrderStatusVariants[order.status]}>
                            {purchaseOrderStatusLabels[order.status]}
                        </Badge>
                    </div>
                    <div className="flex gap-2">
                        {canOrder && (
                            <Button size="sm" onClick={() => handleStatusAction('ordered')}>
                                Tandai Dipesan
                            </Button>
                        )}
                        {canReceive && (
                            <Button size="sm" onClick={() => handleStatusAction('receive')}>
                                Terima Barang
                            </Button>
                        )}
                        {canCancel && (
                            <Button variant="destructive" size="sm" onClick={() => handleStatusAction('cancel')}>
                                Batalkan PO
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Data vendor */}
                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 text-sm font-semibold">Vendor</h2>
                        <dl className="space-y-1 text-sm">
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Nama</dt>
                                <dd>{order.vendor?.name ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Email</dt>
                                <dd className="truncate">{order.vendor?.email ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Telepon</dt>
                                <dd>{order.vendor?.phone ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-neutral-500">Alamat</dt>
                                <dd className="mt-1">{order.vendor?.address ?? '-'}</dd>
                            </div>
                        </dl>
                    </section>

                    {/* Ringkasan nominal */}
                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 text-sm font-semibold">Ringkasan</h2>
                        <dl className="space-y-1 text-sm">
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Tanggal PO</dt>
                                <dd>{formatDate(order.order_date)}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Estimasi Datang</dt>
                                <dd>{order.expected_date ? formatDate(order.expected_date) : '-'}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Subtotal</dt>
                                <dd>{formatCurrency(order.subtotal)}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Pajak</dt>
                                <dd>{formatCurrency(order.tax)}</dd>
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

                    {/* Invoice vendor */}
                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 text-sm font-semibold">Invoice Vendor</h2>
                        {invoice ? (
                            <dl className="space-y-1 text-sm">
                                <div className="flex justify-between gap-4">
                                    <dt className="text-neutral-500">Nomor</dt>
                                    <dd className="font-mono text-xs">{invoice.vendor_invoice_number}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-neutral-500">Tanggal</dt>
                                    <dd>{formatDate(invoice.invoice_date)}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-neutral-500">Jatuh Tempo</dt>
                                    <dd>{invoice.due_date ? formatDate(invoice.due_date) : '-'}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-neutral-500">Jumlah</dt>
                                    <dd>{formatCurrency(invoice.amount)}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-neutral-500">Dibayar</dt>
                                    <dd>{formatCurrency(invoice.amount_paid)}</dd>
                                </div>
                                <div className="flex items-center justify-between gap-4">
                                    <dt className="text-neutral-500">Status</dt>
                                    <dd>
                                        <Badge variant={vendorInvoiceStatusVariants[invoice.status]}>
                                            {vendorInvoiceStatusLabels[invoice.status]}
                                        </Badge>
                                    </dd>
                                </div>
                                {remaining > 0 && (
                                    <div className="flex justify-between gap-4 border-t pt-2">
                                        <dt className="text-neutral-500">Sisa</dt>
                                        <dd className="font-semibold">{formatCurrency(remaining)}</dd>
                                    </div>
                                )}
                            </dl>
                        ) : (
                            <p className="text-sm text-neutral-500">Belum ada invoice vendor.</p>
                        )}
                    </section>
                </div>

                {/* Item PO */}
                <section className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">SKU</th>
                                <th className="px-4 py-2 font-medium">Produk</th>
                                <th className="px-4 py-2 font-medium text-right">Qty</th>
                                <th className="px-4 py-2 font-medium text-right">Harga Beli</th>
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
                                            {formatCurrency(item.unit_cost)}
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

                {/* Form catat invoice vendor (finance, PO received belum ada invoice) */}
                {canRecordInvoice && (
                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 text-sm font-semibold">Catat Invoice Vendor</h2>
                        <Form {...storeVendorInvoice.form({ purchase_order: order.id })} className="grid gap-4 sm:grid-cols-5">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="vendor_invoice_number">Nomor Invoice Vendor</Label>
                                        <Input id="vendor_invoice_number" name="vendor_invoice_number" required placeholder="mis. INV/VD/2026/001" />
                                        <InputError message={errors.vendor_invoice_number} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="invoice_date">Tanggal</Label>
                                        <Input id="invoice_date" name="invoice_date" type="date" required defaultValue={new Date().toISOString().slice(0, 10)} />
                                        <InputError message={errors.invoice_date} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="due_date">Jatuh Tempo (opsional)</Label>
                                        <Input id="due_date" name="due_date" type="date" />
                                        <InputError message={errors.due_date} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="amount">Jumlah</Label>
                                        <Input id="amount" name="amount" type="number" min="0.01" step="0.01" required defaultValue={String(Number(order.grand_total))} />
                                        <InputError message={errors.amount} />
                                    </div>
                                    <div className="sm:col-span-5">
                                        <Button type="submit" disabled={processing}>
                                            Simpan Invoice
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </section>
                )}

                {/* Riwayat pembayaran vendor */}
                {invoice && (
                    <section className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                                <tr>
                                    <th className="px-4 py-2 font-medium">Metode</th>
                                    <th className="px-4 py-2 font-medium">No. Referensi</th>
                                    <th className="px-4 py-2 font-medium text-right">Jumlah</th>
                                    <th className="px-4 py-2 font-medium">Tanggal Bayar</th>
                                    <th className="px-4 py-2 font-medium">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {invoice.payments.map((payment) => (
                                    <tr key={payment.id} className="border-t">
                                        <td className="px-4 py-2">{vendorPaymentMethodLabels[payment.method] ?? payment.method}</td>
                                        <td className="px-4 py-2 font-mono text-xs">{payment.reference_no ?? '-'}</td>
                                        <td className="px-4 py-2 text-right whitespace-nowrap">
                                            {formatCurrency(payment.amount)}
                                        </td>
                                        <td className="px-4 py-2 whitespace-nowrap">{formatDate(payment.paid_at)}</td>
                                        <td className="px-4 py-2 text-neutral-500">{payment.notes ?? '-'}</td>
                                    </tr>
                                ))}
                                {invoice.payments.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-neutral-500">
                                            Belum ada pembayaran.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </section>
                )}

                {/* Form bayar vendor (finance, invoice belum lunas) */}
                {canPay && invoice && (
                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 text-sm font-semibold">Bayar Invoice Vendor</h2>
                        <Form {...storeVendorPayment.form({ vendor_invoice: invoice.id })} className="grid gap-4 sm:grid-cols-6">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="pay_amount">Jumlah</Label>
                                        <Input id="pay_amount" name="amount" type="number" min="0.01" step="0.01" required defaultValue={String(remaining)} />
                                        <InputError message={errors.amount} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="method">Metode</Label>
                                        <select id="method" name="method" required defaultValue="bank_transfer" className={selectClass}>
                                            <option value="bank_transfer">Transfer Bank</option>
                                            <option value="cash">Tunai</option>
                                        </select>
                                        <InputError message={errors.method} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="reference_no">No. Referensi (opsional)</Label>
                                        <Input id="reference_no" name="reference_no" placeholder="mis. bukti transfer" />
                                        <InputError message={errors.reference_no} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="paid_at">Tanggal Bayar</Label>
                                        <Input id="paid_at" name="paid_at" type="date" required defaultValue={new Date().toISOString().slice(0, 10)} />
                                        <InputError message={errors.paid_at} />
                                    </div>
                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="pay_notes">Catatan (opsional)</Label>
                                        <textarea
                                            id="pay_notes"
                                            name="notes"
                                            rows={1}
                                            className="border-input bg-background rounded-md border px-3 py-2 text-sm"
                                        />
                                        <InputError message={errors.notes} />
                                    </div>
                                    <div className="sm:col-span-6">
                                        <Button type="submit" disabled={processing}>
                                            Simpan Pembayaran
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </section>
                )}
            </div>
        </>
    );
}

PurchaseOrderShow.layout = {
    breadcrumbs: [
        { title: 'Purchase Order', href: index.url() },
        { title: `Detail PO` },
    ],
};
