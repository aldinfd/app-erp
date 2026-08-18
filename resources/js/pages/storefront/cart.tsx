import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCart } from '@/components/storefront/cart-context';
import { formatCurrency } from '@/lib/utils';
import { create } from '@/routes/checkout';

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

            <section className="py-8">
                <h1 className="text-2xl font-bold tracking-tight">Keranjang</h1>

                {items.length === 0 ? (
                    <div className="mt-16 flex flex-col items-center gap-3 py-16 text-center">
                        <span className="text-4xl">🛒</span>
                        <p className="text-muted-foreground">Keranjang Anda masih kosong.</p>
                        <Button asChild variant="outline">
                            <Link href="/">Lihat Katalog</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Produk</th>
                                        <th className="px-4 py-3 font-medium">Harga</th>
                                        <th className="px-4 py-3 font-medium">Jumlah</th>
                                        <th className="px-4 py-3 text-right font-medium">Subtotal</th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {items.map((line) => (
                                        <tr key={line.product_id}>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{line.name}</div>
                                                <div className="text-xs text-muted-foreground">{line.sku}</div>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                {formatCurrency(line.price)} / {line.unit_abbreviation}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Input
                                                    type="number"
                                                    value={line.qty}
                                                    min={line.allows_fraction ? 0.5 : 1}
                                                    step={line.allows_fraction ? 0.5 : 1}
                                                    onChange={(event) => setQty(line.product_id, Number(event.target.value))}
                                                    className="h-9 w-24"
                                                    aria-label={`Jumlah ${line.name}`}
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-right whitespace-nowrap">
                                                {formatCurrency(line.price * line.qty)}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() => removeItem(line.product_id)}
                                                >
                                                    Hapus
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="h-fit rounded-xl border border-border p-4">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Subtotal</span>
                                <span className="font-semibold">{formatCurrency(subtotal)}</span>
                            </div>
                            <p className="mt-2 text-xs text-muted-foreground">
                                Harga dan ketersediaan stok dihitung ulang saat checkout.
                            </p>

                            {anyInvalidQty && (
                                <p className="mt-2 text-xs text-destructive">Jumlah tiap barang minimal 1 (atau 0,5 untuk satuan pecahan).</p>
                            )}

                            {canCheckout ? (
                                <Button asChild className="mt-4 w-full">
                                    <Link href={create.url({ query: { items: itemsQuery } })}>Lanjut ke Checkout</Link>
                                </Button>
                            ) : (
                                <Button className="mt-4 w-full" disabled>
                                    Lanjut ke Checkout
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </section>
        </>
    );
}
