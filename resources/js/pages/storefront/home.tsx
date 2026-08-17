import { Head } from '@inertiajs/react';

export default function Home() {
    return (
        <>
            <Head title="Beranda" />

            <section className="flex flex-col items-center justify-center gap-6 py-24 text-center sm:py-32">
                <span className="text-5xl">🛍️</span>

                <h1 className="max-w-2xl text-4xl font-bold tracking-tight text-balance sm:text-5xl">
                    Katalog produk segera hadir
                </h1>

                <p className="max-w-xl text-lg text-muted-foreground sm:text-xl">
                    Kami sedang menyiapkan etalase produk untuk Anda. Silakan kembali
                    lagi dalam waktu dekat.
                </p>
            </section>
        </>
    );
}
