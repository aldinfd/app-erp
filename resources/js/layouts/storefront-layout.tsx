import { Link, Head } from '@inertiajs/react';
import { ShoppingCart } from 'lucide-react';
import { cart, home, login } from '@/routes';
import { CartProvider, useCart } from '@/components/storefront/cart-context';

const appName = import.meta.env.VITE_APP_NAME || 'Integra';

type Props = {
    children: React.ReactNode;
};

/** Link keranjang dengan badge jumlah item (dibaca setelah mount agar SSR-safe). */
function CartLink() {
    const { count } = useCart();

    return (
        <Link
            href={cart()}
            className="relative inline-flex h-9 items-center gap-2 rounded-lg border border-ink/15 bg-card px-3 text-sm font-medium text-foreground transition-colors hover:border-ink/40 hover:bg-ink/5 focus-visible:ring-2 focus-visible:ring-manila/60 focus-visible:outline-none"
        >
            <ShoppingCart aria-hidden className="size-4" />
            <span className="hidden sm:inline">Keranjang</span>
            {count > 0 && (
                <span className="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-ink px-1 font-mono text-xs font-semibold text-white">
                    {Math.round(count)}
                </span>
            )}
        </Link>
    );
}

export default function StorefrontLayout({ children }: Props) {
    return (
        <CartProvider>
            {/* Strip tinta di tepi atas — "kanopi" toko, identitas satu keluarga
             * dengan tombol back-office. */}
            <div className="flex min-h-screen flex-col border-t-4 border-ink bg-paper text-foreground dark:bg-background">
                <Head>
                    <meta
                        name="description"
                        content={`Katalog produk resmi ${appName}`}
                    />
                </Head>

                <header className="sticky top-0 z-40 border-b border-ink/10 bg-paper/90 backdrop-blur dark:border-border dark:bg-background/90">
                    <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
                        <div className="flex items-center gap-8">
                            <Link
                                href={home()}
                                className="rounded-md font-serif text-2xl tracking-tight focus-visible:ring-2 focus-visible:ring-manila/60 focus-visible:outline-none"
                            >
                                {appName}
                            </Link>

                            <nav className="hidden items-center gap-5 text-sm text-muted-foreground sm:flex">
                                <Link
                                    href={home()}
                                    className="transition-colors hover:text-foreground"
                                >
                                    Katalog
                                </Link>
                            </nav>
                        </div>

                        <div className="flex items-center gap-4">
                            <CartLink />
                            <span
                                aria-hidden
                                className="hidden h-5 w-px bg-ink/15 sm:block dark:bg-border"
                            />
                            <Link
                                href={login()}
                                className="font-mono text-[11px] tracking-[0.14em] text-muted-foreground uppercase transition-colors hover:text-foreground"
                            >
                                Masuk Staff
                            </Link>
                        </div>
                    </div>
                </header>

                <main className="flex-1">
                    <div className="mx-auto w-full max-w-6xl px-4">{children}</div>
                </main>

                <footer className="border-t border-ink/10 bg-paper-dim/40 dark:border-border dark:bg-background">
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-3 px-4 py-8 text-sm text-muted-foreground sm:flex-row">
                        <span className="font-serif text-base text-foreground">
                            {appName}
                        </span>
                        <span>Belanja mudah, pembayaran aman.</span>
                        <span>
                            &copy; {new Date().getFullYear()} {appName}.
                        </span>
                    </div>
                </footer>
            </div>
        </CartProvider>
    );
}
