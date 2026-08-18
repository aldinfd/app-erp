import * as React from 'react';
import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/lib/utils';
import { index, store } from '@/routes/purchase-orders';
import type { PurchaseProductOption, PurchaseVendorOption } from '@/types';

type Props = {
    vendors: PurchaseVendorOption[];
    products: PurchaseProductOption[];
};

type ItemRow = {
    key: number;
    productId: string;
    qty: string;
    unitCost: string;
};

const selectClass = 'border-input bg-background h-9 rounded-md border px-3 text-sm';

let rowKey = 0;

export default function PurchaseOrdersCreate({ vendors, products }: Props) {
    const [items, setItems] = React.useState<ItemRow[]>([{ key: ++rowKey, productId: '', qty: '', unitCost: '' }]);

    const productById = React.useMemo(
        () => new Map(products.map((product) => [String(product.id), product])),
        [products],
    );

    // Subtotal & total preview dari input saat ini (server yang menghitung resmi).
    const subtotal = items.reduce(
        (sum, item) => sum + (Number(item.qty) || 0) * (Number(item.unitCost) || 0),
        0,
    );

    function updateItem(key: number, patch: Partial<ItemRow>) {
        setItems((rows) => rows.map((row) => (row.key === key ? { ...row, ...patch } : row)));
    }

    /** Ganti produk: isi harga beli terakhir sebagai default unit_cost. */
    function changeProduct(key: number, productId: string) {
        const product = productById.get(productId);
        updateItem(key, { productId, unitCost: product ? String(Number(product.cost_price)) : '' });
    }

    function addItem() {
        setItems((rows) => [...rows, { key: ++rowKey, productId: '', qty: '', unitCost: '' }]);
    }

    function removeItem(key: number) {
        setItems((rows) => (rows.length > 1 ? rows.filter((row) => row.key !== key) : rows));
    }

    return (
        <>
            <Head title="PO Baru" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">PO Baru</h1>

                <Form {...store.form()} className="max-w-3xl space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="vendor_id">Vendor</Label>
                                    <select id="vendor_id" name="vendor_id" required defaultValue="" className={selectClass}>
                                        <option value="" disabled>
                                            — Pilih vendor —
                                        </option>
                                        {vendors.map((vendor) => (
                                            <option key={vendor.id} value={vendor.id}>
                                                {vendor.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.vendor_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="order_date">Tanggal PO</Label>
                                    <Input id="order_date" name="order_date" type="date" required defaultValue={new Date().toISOString().slice(0, 10)} />
                                    <InputError message={errors.order_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="expected_date">Estimasi Barang Datang (opsional)</Label>
                                    <Input id="expected_date" name="expected_date" type="date" />
                                    <InputError message={errors.expected_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="tax">Pajak (opsional)</Label>
                                    <Input id="tax" name="tax" type="number" min="0" step="0.01" defaultValue="0" />
                                    <InputError message={errors.tax} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Catatan (opsional)</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={2}
                                    placeholder="mis. kirim sebelum tanggal X"
                                    className="border-input bg-background rounded-md border px-3 py-2 text-sm"
                                />
                                <InputError message={errors.notes} />
                            </div>

                            {/* Item PO: input dinamis dikirim sebagai items[i][…]. */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label>Item Pembelian</Label>
                                    <Button type="button" variant="outline" size="sm" onClick={addItem}>
                                        + Tambah Item
                                    </Button>
                                </div>

                                {items.map((row, index) => {
                                    const product = row.productId ? productById.get(row.productId) : undefined;
                                    const rowSubtotal = (Number(row.qty) || 0) * (Number(row.unitCost) || 0);

                                    return (
                                        <div key={row.key} className="grid grid-cols-12 items-start gap-2 rounded-lg border p-3">
                                            <div className="col-span-12 grid gap-1 sm:col-span-5">
                                                <select
                                                    name={`items[${index}][product_id]`}
                                                    required
                                                    value={row.productId}
                                                    onChange={(e) => changeProduct(row.key, e.target.value)}
                                                    className={selectClass}
                                                >
                                                    <option value="">— Pilih produk —</option>
                                                    {products.map((product) => (
                                                        <option key={product.id} value={product.id}>
                                                            {product.sku} — {product.name}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError message={errors[`items.${index}.product_id`]} />
                                            </div>

                                            <div className="col-span-4 grid gap-1 sm:col-span-2">
                                                <Input
                                                    name={`items[${index}][qty]`}
                                                    type="number"
                                                    min="0.01"
                                                    step={product?.unit?.allows_fraction ? '0.01' : '1'}
                                                    placeholder={product?.unit?.allows_fraction ? 'mis. 2.5' : 'mis. 10'}
                                                    required
                                                    value={row.qty}
                                                    onChange={(e) => updateItem(row.key, { qty: e.target.value })}
                                                />
                                                <InputError message={errors[`items.${index}.qty`]} />
                                            </div>

                                            <div className="col-span-4 grid gap-1 sm:col-span-2">
                                                <Input
                                                    name={`items[${index}][unit_cost]`}
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="Harga beli"
                                                    required
                                                    value={row.unitCost}
                                                    onChange={(e) => updateItem(row.key, { unitCost: e.target.value })}
                                                />
                                                <InputError message={errors[`items.${index}.unit_cost`]} />
                                            </div>

                                            <div className="col-span-3 flex items-center justify-end gap-2 pt-1 text-sm sm:col-span-2">
                                                <span className="whitespace-nowrap tabular-nums">{formatCurrency(rowSubtotal)}</span>
                                            </div>

                                            <div className="col-span-1 flex items-center justify-end pt-1">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => removeItem(row.key)}
                                                    disabled={items.length <= 1}
                                                    aria-label="Hapus item"
                                                >
                                                    ✕
                                                </Button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            <div className="flex items-center justify-between rounded-lg border p-4 text-sm">
                                <span className="text-neutral-500">Subtotal item (belum termasuk pajak)</span>
                                <span className="font-semibold tabular-nums">{formatCurrency(subtotal)}</span>
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Simpan Draft PO
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href={index.url()}>Batal</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

PurchaseOrdersCreate.layout = {
    breadcrumbs: [
        { title: 'Purchase Order', href: index.url() },
        { title: 'PO Baru' },
    ],
};
