import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency, formatQty } from '@/lib/utils';
import { store } from '@/routes/checkout';
import type { CatalogProduct } from '@/types/sales';

type Props = {
    products: CatalogProduct[];
    /** Map product_id => qty dari query ?items=1:2,3:1.5 (key JSON = string). */
    requested: Record<string, number>;
};

export default function Checkout({ products, requested }: Props) {
    const [quantities, setQuantities] = useState<Record<number, number>>(() =>
        Object.fromEntries(products.map((product) => [product.id, requested[String(product.id)] ?? 0])),
    );

    const rows = products.map((product) => ({
        product,
        qty: quantities[product.id] ?? 0,
    }));

    const total = rows.reduce((sum, row) => sum + Number(row.product.selling_price) * row.qty, 0);
    const canSubmit = rows.length > 0 && rows.every((row) => row.qty > 0);

    return (
        <>
            <Head title="Checkout" />

            <section className="py-8">
                <h1 className="text-2xl font-bold tracking-tight">Checkout</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Isi data Anda, lalu lanjutkan pembayaran. Tanpa perlu akun.
                </p>

                {rows.length === 0 ? (
                    <div className="mt-6">
                        <Alert>
                            <AlertTitle>Tidak ada barang untuk di-checkout.</AlertTitle>
                        </Alert>
                    </div>
                ) : (
                    <Form {...store.form()} className="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
                        {({ processing, errors }) => (
                            <>
                                <div className="space-y-4 rounded-xl border border-border p-4">
                                    <h2 className="font-semibold">Data Pemesan</h2>

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Nama Lengkap</Label>
                                        <Input id="name" name="name" required autoFocus />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input id="email" name="email" type="email" required />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">No. Telepon</Label>
                                        <Input id="phone" name="phone" required placeholder="mis. 08123456789" />
                                        <InputError message={errors.phone} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="address">Alamat Pengiriman</Label>
                                        <textarea
                                            id="address"
                                            name="address"
                                            required
                                            rows={3}
                                            className="border-input bg-background field-sizing-content rounded-md border px-3 py-2 text-sm"
                                        />
                                        <InputError message={errors.address} />
                                    </div>
                                </div>

                                <div className="h-fit space-y-4 rounded-xl border border-border p-4">
                                    <h2 className="font-semibold">Ringkasan Pesanan</h2>

                                    <div className="space-y-3">
                                        {rows.map((row, index) => {
                                            const product = row.product;
                                            const unit = product.unit ?? null;
                                            const stockQty = Number(product.stock_qty);
                                            const stockChanged = row.qty > stockQty;

                                            return (
                                                <div key={product.id} className="rounded-lg border border-border p-3 text-sm">
                                                    {/* Item dikirim sebagai array: items[i][product_id], items[i][qty]. */}
                                                    <input type="hidden" name={`items[${index}][product_id]`} value={product.id} />

                                                    <div className="flex items-start justify-between gap-2">
                                                        <div>
                                                            <div className="font-medium">{product.name}</div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {formatCurrency(product.selling_price)}
                                                                {unit ? ` / ${unit.abbreviation}` : ''}
                                                            </div>
                                                        </div>
                                                        <div className="text-right font-medium whitespace-nowrap">
                                                            {formatCurrency(Number(product.selling_price) * row.qty)}
                                                        </div>
                                                    </div>

                                                    <div className="mt-2 flex items-center gap-2">
                                                        <Input
                                                            type="number"
                                                            name={`items[${index}][qty]`}
                                                            value={row.qty}
                                                            min={unit?.allows_fraction ? 0.5 : 1}
                                                            step={unit?.allows_fraction ? 0.5 : 1}
                                                            onChange={(event) =>
                                                                setQuantities((current) => ({
                                                                    ...current,
                                                                    [product.id]: Number(event.target.value),
                                                                }))
                                                            }
                                                            className="h-9 w-24"
                                                            aria-label={`Jumlah ${product.name}`}
                                                        />
                                                    </div>

                                                    {stockChanged && (
                                                        <p className="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                                            Stok berubah — tersisa {formatQty(product.stock_qty, unit?.allows_fraction ?? false)}{' '}
                                                            {unit?.abbreviation ?? ''}.
                                                        </p>
                                                    )}
                                                    <InputError className="mt-1" message={errors[`items.${index}.qty`]} />
                                                </div>
                                            );
                                        })}
                                    </div>

                                    <div className="flex items-center justify-between border-t border-border pt-3">
                                        <span className="text-muted-foreground">Total</span>
                                        <span className="text-lg font-bold">{formatCurrency(total)}</span>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        Total final dihitung ulang server saat pesanan dibuat.
                                    </p>

                                    <Button type="submit" className="w-full" disabled={processing || !canSubmit}>
                                        {processing ? 'Memproses…' : 'Bayar Sekarang'}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                )}
            </section>
        </>
    );
}
