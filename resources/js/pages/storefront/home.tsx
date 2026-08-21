import { Head } from '@inertiajs/react';
import { Minus, Package, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { useCart } from '@/components/storefront/cart-context';
import { cn, formatCurrency, formatQty } from '@/lib/utils';
import type { CatalogProduct, StoredCartItem } from '@/types/sales';

type Props = {
    products: CatalogProduct[];
};

export default function Home({ products }: Props) {
    return (
        <>
            <Head title="Katalog" />

            {/* Band etalase — pembuka khas toko, satu keluarga dengan halaman
             * login back-office (dash manila + judul serif). */}
            <section className="border-b border-ink/10 py-10 dark:border-border sm:py-12">
                <span
                    aria-hidden
                    className="mb-5 block h-0.75 w-10 rounded-full bg-manila"
                />
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="font-serif text-4xl tracking-tight text-balance sm:text-5xl">
                            Katalog Produk
                        </h1>
                        <p className="mt-3 max-w-md text-sm leading-relaxed text-muted-foreground">
                            Pilih produk, masukkan ke keranjang, lalu checkout
                            tanpa perlu akun.
                        </p>
                    </div>
                    <p className="font-mono text-[11px] tracking-[0.18em] text-muted-foreground uppercase">
                        {products.length} produk tersedia
                    </p>
                </div>
            </section>

            <section className="py-8 sm:py-10">
                {products.length === 0 ? (
                    <div className="flex flex-col items-center gap-4 py-20 text-center">
                        <span className="flex size-16 items-center justify-center rounded-full bg-paper-dim/70 dark:bg-muted">
                            <Package
                                aria-hidden
                                className="size-7 text-muted-foreground/60"
                            />
                        </span>
                        <div>
                            <p className="font-serif text-xl">
                                Etalase masih kosong
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Produk akan segera tersedia — kembali lagi
                                nanti.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                        {products.map((product) => (
                            <CatalogCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
            </section>
        </>
    );
}

function CatalogCard({ product }: { product: CatalogProduct }) {
    const { addItem } = useCart();

    const unit = product.unit ?? null;
    const allowsFraction = unit?.allows_fraction ?? false;
    const step = allowsFraction ? 0.5 : 1;

    const stockQty = Number(product.stock_qty);
    const inStock = stockQty > 0;

    const [qty, setQty] = useState(step);
    const [added, setAdded] = useState(false);
    const feedbackTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (feedbackTimer.current) {
                clearTimeout(feedbackTimer.current);
            }
        };
    }, []);

    const qtyExceedsStock = qty > stockQty;
    const canAdd = inStock && qty > 0 && !qtyExceedsStock;

    const handleAdd = () => {
        if (!canAdd) {
            return;
        }

        const item: StoredCartItem = {
            product_id: product.id,
            sku: product.sku,
            name: product.name,
            price: Number(product.selling_price),
            qty,
            unit_abbreviation: unit?.abbreviation ?? 'pcs',
            allows_fraction: allowsFraction,
            image_url: product.image_url,
        };

        addItem(item, qty);

        setAdded(true);
        if (feedbackTimer.current) {
            clearTimeout(feedbackTimer.current);
        }
        feedbackTimer.current = setTimeout(() => setAdded(false), 2000);
    };

    return (
        <div className="flex flex-col overflow-hidden rounded-xl border border-ink/10 bg-card transition-shadow hover:shadow-[0_10px_28px_-16px_rgba(0,0,0,0.3)] dark:border-border">
            <div className="relative flex aspect-square items-center justify-center bg-paper-dim/70 dark:bg-muted">
                {product.image_url ? (
                    <img
                        src={product.image_url}
                        alt={product.name}
                        className="size-full object-cover"
                        loading="lazy"
                    />
                ) : (
                    <Package
                        aria-hidden
                        className="size-10 text-muted-foreground/40"
                    />
                )}
                {!inStock && (
                    <span className="absolute inset-x-0 bottom-0 bg-ink/85 py-1 text-center font-mono text-[10px] tracking-[0.18em] text-white uppercase">
                        Stok habis
                    </span>
                )}
            </div>

            <div className="flex flex-1 flex-col gap-2.5 p-4">
                <h2 className="line-clamp-2 min-h-10 text-sm leading-snug font-semibold">
                    {product.name}
                </h2>

                {/* Harga gaya "daftar harga" — angka mono, satuan diikuti titik-titik. */}
                <p className="flex items-baseline gap-1 font-mono">
                    <span className="text-[15px] font-medium">
                        {formatCurrency(product.selling_price)}
                    </span>
                    {unit && (
                        <span className="text-xs text-muted-foreground">
                            /{unit.abbreviation}
                        </span>
                    )}
                </p>
                <p className="flex items-baseline gap-1 text-xs text-muted-foreground">
                    <span>stok {formatQty(product.stock_qty, allowsFraction)}</span>
                    <span
                        aria-hidden
                        className="mx-1 flex-1 -translate-y-1 border-b border-dotted border-muted-foreground/40"
                    />
                    <span className="font-mono text-[10px] tracking-wide text-muted-foreground/70 uppercase">
                        {product.sku}
                    </span>
                </p>

                {inStock ? (
                    <div className="mt-auto flex flex-col gap-2 pt-2.5 xl:flex-row xl:items-center">
                        <div className="flex h-9 w-full items-center rounded-lg border border-ink/15 xl:w-auto dark:border-border">
                            <button
                                type="button"
                                aria-label={`Kurangi jumlah ${product.name}`}
                                onClick={() => setQty((current) => Math.max(step, Number((current - step).toFixed(2))))}
                                className="flex h-full w-8 items-center justify-center text-muted-foreground transition-colors hover:text-foreground"
                            >
                                <Minus className="size-3.5" />
                            </button>
                            <Input
                                type="number"
                                value={qty}
                                min={step}
                                step={step}
                                onChange={(event) => setQty(Number(event.target.value))}
                                className="no-number-spinner h-9 flex-1 rounded-none border-x border-ink/15 text-center focus-visible:ring-0 xl:w-13 xl:flex-none dark:border-border"
                                aria-label={`Jumlah ${product.name}`}
                            />
                            <button
                                type="button"
                                aria-label={`Tambah jumlah ${product.name}`}
                                onClick={() => setQty((current) => Number((current + step).toFixed(2)))}
                                className="flex h-full w-8 items-center justify-center text-muted-foreground transition-colors hover:text-foreground"
                            >
                                <Plus className="size-3.5" />
                            </button>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            disabled={!canAdd}
                            onClick={handleAdd}
                            className={cn(
                                'h-9 w-full rounded-lg bg-ink text-white transition-all hover:bg-ledger active:translate-y-px xl:w-auto xl:flex-1',
                                added && 'bg-ledger hover:bg-ledger',
                            )}
                        >
                            {added ? 'Ditambahkan ✓' : 'Tambah'}
                        </Button>
                    </div>
                ) : (
                    <div className="mt-auto pt-1">
                        <Button
                            type="button"
                            size="sm"
                            disabled
                            className="h-9 w-full rounded-lg"
                        >
                            Tidak tersedia
                        </Button>
                    </div>
                )}

                {inStock && qtyExceedsStock && (
                    <InputError
                        className="text-xs"
                        message={`Jumlah melebihi stok (${formatQty(product.stock_qty, allowsFraction)}).`}
                    />
                )}
            </div>
        </div>
    );
}
