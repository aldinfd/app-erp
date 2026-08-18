import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

            <section className="py-8">
                <h1 className="text-2xl font-bold tracking-tight">Katalog Produk</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Pilih produk, masukkan ke keranjang, lalu checkout tanpa perlu akun.
                </p>

                {products.length === 0 ? (
                    <div className="mt-16 flex flex-col items-center gap-3 py-16 text-center">
                        <span className="text-4xl">🛍️</span>
                        <p className="text-muted-foreground">Belum ada produk yang tersedia.</p>
                    </div>
                ) : (
                    <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
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
        <div className="flex flex-col overflow-hidden rounded-xl border border-border bg-card">
            <div className="flex aspect-square items-center justify-center bg-muted">
                {product.image_url ? (
                    <img
                        src={product.image_url}
                        alt={product.name}
                        className="size-full object-cover"
                        loading="lazy"
                    />
                ) : (
                    <span className="text-4xl" aria-hidden>
                        📦
                    </span>
                )}
            </div>

            <div className="flex flex-1 flex-col gap-2 p-3">
                <div className="flex items-start justify-between gap-2">
                    <h2 className="line-clamp-2 text-sm font-semibold leading-snug">{product.name}</h2>
                    {inStock ? (
                        <Badge variant="secondary" className="shrink-0">
                            Tersedia
                        </Badge>
                    ) : (
                        <Badge variant="destructive" className="shrink-0">
                            Habis
                        </Badge>
                    )}
                </div>

                <p className="text-xs text-muted-foreground">
                    {formatCurrency(product.selling_price)}
                    {unit ? ` / ${unit.abbreviation}` : ''} · stok {formatQty(product.stock_qty, allowsFraction)}{' '}
                    {unit?.abbreviation ?? ''}
                </p>

                <div className="mt-auto flex items-center gap-2">
                    <Input
                        type="number"
                        value={qty}
                        min={step}
                        step={step}
                        disabled={!inStock}
                        onChange={(event) => setQty(Number(event.target.value))}
                        className="h-9 w-20"
                        aria-label={`Jumlah ${product.name}`}
                    />
                    <Button
                        type="button"
                        size="sm"
                        disabled={!canAdd}
                        onClick={handleAdd}
                        className={cn('flex-1', added && 'bg-emerald-600 text-white hover:bg-emerald-600')}
                    >
                        {added ? 'Ditambahkan ✓' : 'Tambah ke Keranjang'}
                    </Button>
                </div>

                {inStock && qtyExceedsStock && (
                    <p className="text-xs text-destructive">
                        Jumlah melebihi stok ({formatQty(product.stock_qty, allowsFraction)}).
                    </p>
                )}
            </div>
        </div>
    );
}
