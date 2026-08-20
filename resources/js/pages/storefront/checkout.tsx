import { Form, Head } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CheckoutSteps from '@/components/storefront/checkout-steps';
import { cn, formatCurrency, formatQty } from '@/lib/utils';
import { store } from '@/routes/checkout';
import type { CatalogProduct } from '@/types/sales';

type Props = {
    products: CatalogProduct[];
    /** Map product_id => qty dari query ?items=1:2,3:1.5 (key JSON = string). */
    requested: Record<string, number>;
};

type ClientErrors = {
    name?: string;
    email?: string;
    phone?: string;
    address?: string;
};

/**
 * Validasi sisi klien dengan pesan Indonesia — menggantikan gelembung
 * validasi bawaan browser (form memakai noValidate). Error dari server
 * (mis. qty/stok) tetap tampil di slot inline yang sama.
 */
function validateCheckout(data: Record<string, unknown>): ClientErrors {
    const errors: ClientErrors = {};
    const name = String(data.name ?? '').trim();
    const email = String(data.email ?? '').trim();
    const phone = String(data.phone ?? '').trim();
    const address = String(data.address ?? '').trim();

    if (!name) {
        errors.name = 'Nama lengkap wajib diisi.';
    }
    if (!email) {
        errors.email = 'Email wajib diisi.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.email = 'Format email tidak valid — contoh: nama@email.com.';
    }
    if (!phone) {
        errors.phone = 'No. telepon wajib diisi.';
    }
    if (!address) {
        errors.address = 'Alamat pengiriman wajib diisi.';
    }

    return errors;
}

export default function Checkout({ products, requested }: Props) {
    const [clientErrors, setClientErrors] = useState<ClientErrors>({});

    const [quantities, setQuantities] = useState<Record<number, number>>(() =>
        Object.fromEntries(products.map((product) => [product.id, requested[String(product.id)] ?? 0])),
    );

    /** Hapus error klien field tertentu saat user mulai mengetik ulang. */
    const clearClientError = (field: keyof ClientErrors) => {
        setClientErrors((prev) =>
            prev[field] ? { ...prev, [field]: undefined } : prev,
        );
    };

    const rows = products.map((product) => ({
        product,
        qty: quantities[product.id] ?? 0,
    }));

    const total = rows.reduce((sum, row) => sum + Number(row.product.selling_price) * row.qty, 0);
    const canSubmit = rows.length > 0 && rows.every((row) => row.qty > 0);

    return (
        <>
            <Head title="Checkout" />

            <section className="py-8 sm:py-10">
                <CheckoutSteps current={1} />

                <div className="mt-5">
                    <span
                        aria-hidden
                        className="mb-4 block h-0.75 w-10 rounded-full bg-manila"
                    />
                    <h1 className="font-serif text-3xl tracking-tight sm:text-4xl">
                        Checkout
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Isi data Anda, lalu lanjutkan pembayaran. Tanpa perlu
                        akun.
                    </p>
                </div>

                {rows.length === 0 ? (
                    <div className="mt-6 rounded-xl border border-ink/10 bg-card p-6 dark:border-border">
                        <p className="font-medium">Tidak ada barang untuk di-checkout.</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kembali ke keranjang dan pilih produk dulu.
                        </p>
                    </div>
                ) : (
                    <Form
                        {...store.form()}
                        noValidate
                        onBefore={(visit) => {
                            const next = validateCheckout(visit.data);
                            setClientErrors(next);
                            return Object.keys(next).length === 0;
                        }}
                        className="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_380px]"
                    >
                        {({ processing, errors }) => {
                            const nameError = clientErrors.name ?? errors.name;
                            const emailError = clientErrors.email ?? errors.email;
                            const phoneError = clientErrors.phone ?? errors.phone;
                            const addressError = clientErrors.address ?? errors.address;

                            return (
                                <>
                                    <div className="space-y-5 rounded-xl border border-ink/10 bg-card p-5 dark:border-border sm:p-6">
                                        <h2 className="font-mono text-[11px] font-medium tracking-[0.18em] text-muted-foreground uppercase">
                                            Data Pemesan
                                        </h2>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="name"
                                                className="font-mono text-[11px] font-medium tracking-[0.14em] text-muted-foreground uppercase"
                                            >
                                                Nama Lengkap
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                required
                                                autoFocus
                                                aria-invalid={nameError ? true : undefined}
                                                onInput={() => clearClientError('name')}
                                                className={cn(
                                                    'h-11 rounded-lg transition-shadow focus-visible:border-manila/60 focus-visible:ring-manila/25',
                                                    nameError &&
                                                        'border-red-600/60 focus-visible:border-red-600/60 focus-visible:ring-red-500/20 dark:border-red-400/60 dark:focus-visible:border-red-400/60 dark:focus-visible:ring-red-400/20',
                                                )}
                                            />
                                            <InputError message={nameError} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="email"
                                                className="font-mono text-[11px] font-medium tracking-[0.14em] text-muted-foreground uppercase"
                                            >
                                                Email
                                            </Label>
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                                required
                                                aria-invalid={emailError ? true : undefined}
                                                onInput={() => clearClientError('email')}
                                                className={cn(
                                                    'h-11 rounded-lg transition-shadow focus-visible:border-manila/60 focus-visible:ring-manila/25',
                                                    emailError &&
                                                        'border-red-600/60 focus-visible:border-red-600/60 focus-visible:ring-red-500/20 dark:border-red-400/60 dark:focus-visible:border-red-400/60 dark:focus-visible:ring-red-400/20',
                                                )}
                                            />
                                            <InputError message={emailError} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="phone"
                                                className="font-mono text-[11px] font-medium tracking-[0.14em] text-muted-foreground uppercase"
                                            >
                                                No. Telepon
                                            </Label>
                                            <Input
                                                id="phone"
                                                name="phone"
                                                required
                                                placeholder="mis. 08123456789"
                                                aria-invalid={phoneError ? true : undefined}
                                                onInput={() => clearClientError('phone')}
                                                className={cn(
                                                    'h-11 rounded-lg transition-shadow focus-visible:border-manila/60 focus-visible:ring-manila/25',
                                                    phoneError &&
                                                        'border-red-600/60 focus-visible:border-red-600/60 focus-visible:ring-red-500/20 dark:border-red-400/60 dark:focus-visible:border-red-400/60 dark:focus-visible:ring-red-400/20',
                                                )}
                                            />
                                            <InputError message={phoneError} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="address"
                                                className="font-mono text-[11px] font-medium tracking-[0.14em] text-muted-foreground uppercase"
                                            >
                                                Alamat Pengiriman
                                            </Label>
                                            <textarea
                                                id="address"
                                                name="address"
                                                required
                                                rows={3}
                                                aria-invalid={addressError ? true : undefined}
                                                onInput={() => clearClientError('address')}
                                                className={cn(
                                                    'field-sizing-content min-h-22 w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm transition-shadow outline-none focus-visible:border-manila/60 focus-visible:ring-[3px] focus-visible:ring-manila/25',
                                                    addressError &&
                                                        'border-red-600/60 focus-visible:border-red-600/60 focus-visible:ring-red-500/20 dark:border-red-400/60 dark:focus-visible:border-red-400/60 dark:focus-visible:ring-red-400/20',
                                                )}
                                            />
                                            <InputError message={addressError} />
                                        </div>
                                    </div>

                                    {/* Ringkasan bergaya struk — kertas hangat + garis
                                     * titik-titik ala daftar harga. */}
                                    <div className="rounded-xl border border-ink/15 bg-paper p-5 text-ink shadow-[0_12px_32px_-20px_rgba(0,0,0,0.35)] dark:border-border dark:bg-card dark:text-foreground dark:shadow-none">
                                        <h2 className="font-mono text-[10px] font-medium tracking-[0.18em] text-ink/60 uppercase dark:text-muted-foreground">
                                            Ringkasan Pesanan
                                        </h2>

                                        <div className="mt-4 space-y-3">
                                            {rows.map((row, index) => {
                                                const product = row.product;
                                                const unit = product.unit ?? null;
                                                const stockQty = Number(product.stock_qty);
                                                const stockChanged = row.qty > stockQty;
                                                const qtyError = errors[`items.${index}.qty`];

                                                return (
                                                    <div
                                                        key={product.id}
                                                        className="rounded-lg border border-ink/10 p-3 text-sm dark:border-border"
                                                    >
                                                        {/* Item dikirim sebagai array: items[i][product_id], items[i][qty]. */}
                                                        <input
                                                            type="hidden"
                                                            name={`items[${index}][product_id]`}
                                                            value={product.id}
                                                        />

                                                        <div className="flex items-start justify-between gap-2">
                                                            <div>
                                                                <div className="font-medium">
                                                                    {product.name}
                                                                </div>
                                                                <div className="mt-0.5 font-mono text-xs text-ink/60 dark:text-muted-foreground">
                                                                    {formatCurrency(product.selling_price)}
                                                                    {unit ? ` / ${unit.abbreviation}` : ''}
                                                                </div>
                                                            </div>
                                                            <div className="font-mono font-medium whitespace-nowrap">
                                                                {formatCurrency(Number(product.selling_price) * row.qty)}
                                                            </div>
                                                        </div>

                                                        <div className="mt-2.5 flex items-center justify-between gap-2">
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
                                                                className={cn(
                                                                    'h-9 w-24 rounded-lg',
                                                                    qtyError &&
                                                                        'border-red-600/60 focus-visible:border-red-600/60 dark:border-red-400/60',
                                                                )}
                                                                aria-label={`Jumlah ${product.name}`}
                                                            />
                                                            <span className="font-mono text-[10px] tracking-wide text-ink/50 uppercase dark:text-muted-foreground/70">
                                                                {unit?.abbreviation ?? 'pcs'}
                                                            </span>
                                                        </div>

                                                        {stockChanged && (
                                                            <p className="mt-2.5 flex items-start gap-2 rounded-lg bg-amber-500/10 px-2.5 py-2 text-xs leading-relaxed text-amber-700 dark:text-amber-400">
                                                                <TriangleAlert
                                                                    aria-hidden
                                                                    className="mt-0.5 size-3.5 shrink-0"
                                                                />
                                                                Stok berubah — tersisa{' '}
                                                                {formatQty(product.stock_qty, unit?.allows_fraction ?? false)}{' '}
                                                                {unit?.abbreviation ?? ''}.
                                                            </p>
                                                        )}
                                                        <InputError
                                                            className="mt-2 text-xs"
                                                            message={qtyError}
                                                        />
                                                    </div>
                                                );
                                            })}
                                        </div>

                                        <div className="mt-4 flex items-baseline gap-2 border-t border-dashed border-ink/20 pt-4 dark:border-border">
                                            <span className="text-sm">Total</span>
                                            <span
                                                aria-hidden
                                                className="flex-1 -translate-y-1 border-b border-dotted border-ink/30 dark:border-border"
                                            />
                                            <span className="font-mono text-lg font-bold">
                                                {formatCurrency(total)}
                                            </span>
                                        </div>
                                        <p className="mt-3 text-xs leading-relaxed text-ink/60 dark:text-muted-foreground">
                                            Total final dihitung ulang server
                                            saat pesanan dibuat.
                                        </p>

                                        <Button
                                            type="submit"
                                            className="mt-4 h-11 w-full rounded-lg bg-ink text-white transition-all hover:bg-ledger active:translate-y-px"
                                            disabled={processing || !canSubmit}
                                        >
                                            {processing ? 'Memproses…' : 'Bayar Sekarang'}
                                        </Button>
                                    </div>
                                </>
                            );
                        }}
                    </Form>
                )}
            </section>
        </>
    );
}
