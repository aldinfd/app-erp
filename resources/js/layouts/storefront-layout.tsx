import { Link, Head } from '@inertiajs/react';
import { home, login } from '@/routes';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

type Props = {
    children: React.ReactNode;
};

export default function StorefrontLayout({ children }: Props) {
    return (
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

                    <Link
                        href={login()}
                        className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        Masuk Staff
                    </Link>
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
                    <span>Katalog produk segera hadir.</span>
                </div>
            </footer>
        </div>
    );
}
