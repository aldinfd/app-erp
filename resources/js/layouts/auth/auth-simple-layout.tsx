import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/** Baris dokumen pada kartu stok — jenis dokumen nyata yang di-generate ERP ini. */
const documents = [
    { code: 'SO', label: 'Order penjualan', number: 'SO-202608-0042' },
    { code: 'PO', label: 'Order pembelian', number: 'PO-202608-0017' },
    { code: 'INV', label: 'Tagihan terbit', number: 'INV-202608-0039' },
    { code: 'JE', label: 'Jurnal otomatis', number: 'JE-202608-0118' },
];

/** Lockup logo + eyebrow mono — dipakai di panel (desktop) dan band (mobile). */
function Lockup() {
    return (
        <Link
            href={home()}
            className="group inline-flex items-center gap-3 rounded-md outline-none focus-visible:ring-2 focus-visible:ring-manila/70"
        >
            <span className="flex size-10 items-center justify-center rounded-lg border border-white/15 bg-white/5 transition-colors group-hover:border-manila/40">
                <AppLogoIcon className="size-5 fill-current text-manila" />
            </span>
            <span className="font-mono text-[10px] font-medium tracking-[0.22em] text-white/55">
                ERP · BACK OFFICE
            </span>
        </Link>
    );
}

/**
 * Kartu stok kertas — dokumen paling atas dari tumpukan. Barisnya memakai
 * nomor dokumen ber-format asli (SO/PO/INV/JE) dan footernya menunjukkan
 * invariant double-entry: debit = kredit.
 */
function StockCard() {
    return (
        <div className="relative w-full -rotate-[1.4deg] rounded-xl bg-paper p-5 text-ink shadow-[0_1px_2px_rgba(0,0,0,0.25),0_10px_20px_-8px_rgba(0,0,0,0.35),0_36px_64px_-24px_rgba(0,0,0,0.65)] transition-transform duration-300 ease-out group-hover:-translate-y-1.5 group-hover:rotate-0 dark:brightness-95">
            <div className="mb-4 flex items-center justify-between">
                <span className="font-mono text-[10px] font-medium tracking-[0.18em] text-ink/55">
                    KARTU STOK — DOKUMEN TERAKHIR
                </span>
                {/* Barcode dekoratif — artefak retail/gudang */}
                <span aria-hidden className="flex h-4 items-end gap-[2px]">
                    <span className="w-[2px] self-stretch bg-ink/80" />
                    <span className="w-[1px] self-stretch bg-ink/60" />
                    <span className="w-[3px] self-stretch bg-ink/80" />
                    <span className="w-[1px] self-stretch bg-ink/40" />
                    <span className="w-[2px] self-stretch bg-ink/80" />
                    <span className="w-[1px] self-stretch bg-ink/60" />
                    <span className="w-[4px] self-stretch bg-ink/80" />
                </span>
            </div>

            <ul className="flex flex-col gap-2.5">
                {documents.map((doc) => (
                    <li key={doc.code} className="flex items-baseline gap-2.5">
                        <span className="rounded-[4px] bg-ink px-1.5 py-0.5 font-mono text-[10px] font-medium tracking-wide text-paper">
                            {doc.code}
                        </span>
                        <span className="text-[13px] text-ink/80">
                            {doc.label}
                        </span>
                        <span
                            aria-hidden
                            className="mx-1 flex-1 -translate-y-1 border-b border-dotted border-ink/25"
                        />
                        <span className="font-mono text-[11px] text-ink/55">
                            {doc.number}
                        </span>
                    </li>
                ))}
            </ul>

            <div className="mt-4 flex items-end justify-between border-t border-ink/10 pt-3.5">
                <div>
                    <p className="font-mono text-[9px] tracking-[0.18em] text-ink/45">
                        DEBIT
                    </p>
                    <p className="font-mono text-sm font-medium">12.480.000</p>
                </div>
                <div className="text-right">
                    <p className="font-mono text-[9px] tracking-[0.18em] text-ink/45">
                        KREDIT
                    </p>
                    <p className="font-mono text-sm font-medium">12.480.000</p>
                </div>
                <span className="rounded-full border border-manila/60 bg-manila/15 px-2.5 py-1 font-mono text-[9px] font-medium tracking-[0.14em] text-ink/70">
                    ✓ SEIMBANG
                </span>
            </div>
        </div>
    );
}

/**
 * Signature halaman auth — tumpukan dokumen tergeletak di kain hijau:
 * dua dokumen berisi (baris catatan tersirat) mengintip dari bawah,
 * kartu stok di paling atas. Seluruh tumpukan melayang pelan
 * (dihormati prefers-reduced-motion).
 */
function DocumentStack() {
    return (
        <div className="group relative max-w-md motion-safe:animate-auth-float">
            <div
                aria-hidden
                className="absolute inset-0 translate-x-6 translate-y-5 rotate-[3.5deg] rounded-xl bg-paper-dim p-5 shadow-[0_20px_36px_-16px_rgba(0,0,0,0.55)]"
            >
                <span className="mb-4 block h-2.5 w-24 rounded-sm bg-ink/15" />
                <span className="mb-2.5 block h-1.75 w-4/5 rounded-sm bg-ink/10" />
                <span className="mb-2.5 block h-1.75 w-3/5 rounded-sm bg-ink/10" />
                <span className="block h-1.75 w-2/3 rounded-sm bg-ink/10" />
            </div>
            <div
                aria-hidden
                className="absolute inset-0 translate-x-3 translate-y-2.5 rotate-[-2.5deg] rounded-xl bg-paper-dim/90 p-5 shadow-[0_14px_28px_-12px_rgba(0,0,0,0.5)]"
            >
                <span className="mb-4 block h-2.5 w-28 rounded-sm bg-ink/12" />
                <span className="mb-2.5 block h-1.75 w-3/4 rounded-sm bg-ink/8" />
                <span className="mb-2.5 block h-1.75 w-1/2 rounded-sm bg-ink/8" />
                <span className="block h-1.75 w-5/6 rounded-sm bg-ink/8" />
            </div>
            <StockCard />
        </div>
    );
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh lg:grid-cols-[1.1fr_1fr]">
            {/*
             | Panel hijau "buku besar" — identitas brand. Hanya tampil penuh
             * di desktop; di mobile menyusut jadi band atas yang ringkas.
             */}
            <aside className="auth-panel relative hidden flex-col justify-between overflow-hidden p-11 text-white xl:p-16 lg:flex">
                <div className="motion-safe:animate-auth-rise">
                    <Lockup />
                </div>

                <div className="flex flex-col gap-9 xl:gap-12">
                    <div className="max-w-lg motion-safe:animate-auth-rise [animation-delay:90ms]">
                        <h2 className="font-serif text-[2.15rem] leading-[1.12] text-balance xl:text-[2.9rem]">
                            Stok, penjualan, dan kas —{' '}
                            <em className="text-manila">satu buku besar</em>,
                            selalu seimbang.
                        </h2>
                        <p className="mt-5 max-w-sm text-sm leading-relaxed text-white/60">
                            Data tidak lagi terpisah-pisah: order, stok, dan
                            keuangan tercatat otomatis dalam satu sistem.
                        </p>
                    </div>

                    <div className="motion-safe:animate-auth-rise [animation-delay:180ms]">
                        <DocumentStack />
                    </div>
                </div>

                <p className="border-t border-white/10 pt-5 font-mono text-[10px] tracking-[0.2em] text-white/40 motion-safe:animate-auth-rise [animation-delay:270ms]">
                    UNTUK TIM INTERNAL — ADMIN · GUDANG · FINANCE
                </p>
            </aside>

            {/*
             | Band mobile — versi ringkas panel hijau di atas layar kecil.
             */}
            <div className="auth-panel flex flex-col gap-4 p-6 text-white sm:p-8 lg:hidden">
                <Lockup />
                <p className="font-serif text-xl leading-snug text-balance">
                    Stok, penjualan, dan kas —{' '}
                    <em className="text-manila">satu buku besar</em>.
                </p>
                <p className="font-mono text-[9px] tracking-[0.2em] text-white/40">
                    UNTUK TIM INTERNAL — ADMIN · GUDANG · FINANCE
                </p>
            </div>

            {/*
             | Kolom form — kanvas hangat halus, mengikuti tema light/dark app.
             | Marginalia vertikal (nomor dokumen) di tepi kanan: detail
             * editorial yang menggemakan panel, hanya di layar lebar.
             */}
            <main className="form-canvas relative flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16 xl:px-20">
                <span
                    aria-hidden
                    className="pointer-events-none absolute top-1/2 right-7 hidden -translate-y-1/2 font-mono text-[10px] tracking-[0.25em] text-foreground/15 [writing-mode:vertical-rl] xl:block"
                >
                    SO-202608-0042 · PO-202608-0017 · INV-202608-0039 ·
                    JE-202608-0118
                </span>

                <div className="mx-auto w-full max-w-[26rem]">
                    <span
                        aria-hidden
                        className="mb-5 block h-0.75 w-10 rounded-full bg-manila"
                    />
                    <h1 className="font-serif text-[2rem] leading-tight text-balance">
                        {title}
                    </h1>
                    {description && (
                        <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                            {description}
                        </p>
                    )}
                    <div className="mt-8">{children}</div>
                </div>
            </main>
        </div>
    );
}
