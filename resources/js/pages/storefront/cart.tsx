import { Head, Link } from '@inertiajs/react';
import { Minus, Package, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import CheckoutSteps from '@/components/storefront/checkout-steps';
import { useCart } from '@/components/storefront/cart-context';
import type { StoredCartItem } from '@/types/sales';
import { formatCurrency } from '@/lib/utils';
import { create } from '@/routes/checkout';

/** Stepper qty baris keranjang — clamp bawah step satuan (0,5 / 1). */
function QtyStepper({
    line,
    onChange,
}: {
    line: StoredCartItem;
    onChange: (qty: number) => void;
}) {
    const step = line.allows_fraction ? 0.5 : 1;

    return (
        <div className="flex h-9 w-fit items-center rounded-lg border border-ink/15 dark:border-border">
            <button
                type="button"
                aria-label={`Kurangi jumlah ${line.name}`}
                onClick={() => onChange(Math.max(step, Number((line.qty - step).toFixed(2))))}
                className="flex h-full w-8 items-center justify-center text-muted-foreground transition-colors hover:text-foreground"
            >
                <Minus className="size-3.5" />
            </button>
            <Input
                type="number"
                value={line.qty}
                min={step}
                step={step}
                onChange={(event) => onChange(Number(event.target.value))}
                className="h-9 w-16 rounded-none border-x border-ink/15 text-center focus-visible:ring-0 dark:border-border"
                aria-label={`Jumlah ${line.name}`}
            />
            <button
                type="button"
                aria-label={`Tambah jumlah ${line.name}`}
                onClick={() => onChange(Number((line.qty + step).toFixed(2)))}
                className="flex h-full w-8 items-center justify-center text-muted-foreground transition-colors hover:text-foreground"
            >
                <Plus className="size-3.5" />
            </button>
        </div>
    );
}

export default function Cart() {
    const { items, setQty, removeItem, subtotal } = useCart();

    const anyInvalidQty = items.some((line) => !(line.qty > 0));
    const canCheckout = items.length > 0 && !anyInvalidQty;

    // Format query checkout: "1:2,3:1.5" (product_id:qty) — dibaca ulang
    // oleh server untuk memuat harga & stok terkini.
    const itemsQuery = items.map((line) => `${line.product_id}:${line.qty}`).join(',');

    return (
        <>
            <Head title="Keranjang" />

            <section className="py-8 sm:py-10">
                <CheckoutSteps current={0} />
                <h1 className="mt-5 font-serif text-3xl tracking-tight sm:text-4xl">
                    Keranjang
                </h1>

                {items.length === 0 ? (
                    <div className="mt-10 flex flex-col items-center gap-4 py-16 text-center">
                        <span className="flex size-16 items-center justify-center rounded-full bg-paper-dim/70 dark:bg-muted">
                            <Package
                                aria-hidden
                                className="size-7 text-muted-foreground/60"
                            />
                        </span>
                        <div>
                            <p className="font-serif text-xl">
                                Keranjang Anda masih kosong
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Yuk pilih produk dari katalog dulu.
                            </p>
                        </div>
                        <Button
                            asChild
                            variant="outline"
                            className="rounded-lg border-ink/20 hover:bg-ink/5"
                        >
                            <Link href="/">Lihat Katalog</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_340px]">
                        <div className="overflow-x-auto rounded-xl border border-ink/10 bg-card dark:border-border">
                            <table className="w-full text-sm">
                                <thead className="border-b border-ink/10 bg-paper-dim/40 text-left font-mono text-[10px] tracking-[0.16em] text-muted-foreground uppercase dark:border-border dark:bg-muted">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Produk
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Harga
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Jumlah
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Subtotal
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink/10 dark:divide-border">
                                    {items.map((line) => (
                                        <tr key={line.product_id}>
                                            <td className="px-4 py-3.5">
                                                <div className="font-medium">
                                                    {line.name}
                                                </div>
                                                <div className="mt-0.5 font-mono text-[10px] tracking-wide text-muted-foreground uppercase">
                                                    {line.sku}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3.5 font-mono whitespace-nowrap">
                                                {formatCurrency(line.price)}
                                                <span className="text-xs text-muted-foreground">
                                                    {' '}
                                                    /{line.unit_abbreviation}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3.5">
                                                <QtyStepper
                                                    line={line}
                                                    onChange={(qty) =>
                                                        setQty(line.product_id, qty)
                                                    }
                                                />
                                            </td>
                                            <td className="px-4 py-3.5 text-right font-mono font-medium whitespace-nowrap">
                                                {formatCurrency(line.price * line.qty)}
                                            </td>
                                            <td className="px-4 py-3.5 text-right">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() =>
                                                        removeItem(line.product_id)
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                    <span className="sr-only sm:not-sr-only">
                                                        Hapus
                                                    </span>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Ringkasan bergaya struk — kertas hangat, garis titik-titik
                         * ala daftar harga, angka mono. */}
                        <div className="rounded-xl border border-ink/15 bg-paper p-5 text-ink shadow-[0_12px_32px_-20px_rgba(0,0,0,0.35)] dark:border-border dark:bg-card dark:text-foreground dark:shadow-none">
                            <p className="font-mono text-[10px] tracking-[0.18em] text-ink/60 uppercase dark:text-muted-foreground">
                                Ringkasan belanja
                            </p>

                            <div className="mt-4 flex items-baseline gap-2">
                                <span className="text-sm">Subtotal</span>
                                <span
                                    aria-hidden
                                    className="flex-1 -translate-y-1 border-b border-dotted border-ink/30 dark:border-border"
                                />
                                <span className="font-mono text-lg font-semibold">
                                    {formatCurrency(subtotal)}
                                </span>
                            </div>

                            <div className="mt-4 border-t border-dashed border-ink/20 pt-4 dark:border-border">
                                <p className="text-xs leading-relaxed text-ink/60 dark:text-muted-foreground">
                                    Harga dan ketersediaan stok dihitung ulang
                                    saat checkout.
                                </p>

                                {anyInvalidQty && (
                                    <InputError
                                        className="mt-2 text-xs"
                                        message="Jumlah tiap barang minimal 1 (atau 0,5 untuk satuan pecahan)."
                                    />
                                )}

                                {canCheckout ? (
                                    <Button
                                        asChild
                                        className="mt-4 h-11 w-full rounded-lg bg-ink text-white transition-all hover:bg-ledger active:translate-y-px"
                                    >
                                        <Link
                                            href={create.url({
                                                query: { items: itemsQuery },
                                            })}
                                        >
                                            Lanjut ke Checkout
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        className="mt-4 h-11 w-full rounded-lg"
                                        disabled
                                    >
                                        Lanjut ke Checkout
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </section>
        </>
    );
}
