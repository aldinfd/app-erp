import { Link, Head } from '@inertiajs/react';
import { cart, home, login } from '@/routes';
import { CartProvider, useCart } from '@/components/storefront/cart-context';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

type Props = {
    children: React.ReactNode;
};

/** Link keranjang dengan badge jumlah item (dibaca setelah mount agar SSR-safe). */
function CartLink() {
    const { count } = useCart();

    return (
        <Link
            href={cart()}
            className="relative inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
        >
            <span aria-hidden>🛒</span>
            <span className="hidden sm:inline">Keranjang</span>
            {count > 0 && (
                <span className="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-xs font-semibold text-primary-foreground">
                    {Math.round(count)}
                </span>
            )}
        </Link>
    );
}

export default function StorefrontLayout({ children }: Props) {
    return (
        <CartProvider>
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <Head>
                    <meta name="description" content={`Katalog produk resmi ${appName}`} />
                </Head>

                <header className="sticky top-0 z-40 border-b border-border bg-background">
                    <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
                        <div className="flex items-center gap-8">
                            <Link href={home()} className="text-lg font-bold tracking-tight">
                                {appName}
                            </Link>

                            <nav className="hidden items-center gap-5 text-sm text-muted-foreground sm:flex">
                                <Link href={home()} className="transition-colors hover:text-foreground">
                                    Katalog
                                </Link>
                            </nav>
                        </div>

                        <div className="flex items-center gap-2">
                            <CartLink />
                            <Link
                                href={login()}
                                className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                            >
                                Masuk Staff
                            </Link>
                        </div>
                    </div>
                </header>

                <main className="flex-1">
                    <div className="mx-auto w-full max-w-6xl px-4">{children}</div>
                </main>

                <footer className="border-t border-border">
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-2 px-4 py-6 text-sm text-muted-foreground sm:flex-row">
                        <span>
                            &copy; {new Date().getFullYear()} {appName}. Semua hak dilindungi.
                        </span>
                        <span>Belanja mudah, pembayaran aman.</span>
                    </div>
                </footer>
            </div>
        </CartProvider>
    );
}
