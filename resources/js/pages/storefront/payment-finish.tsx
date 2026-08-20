import { Head, Link, usePage } from '@inertiajs/react';
import { CircleAlert, PackageSearch } from 'lucide-react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import CheckoutSteps from '@/components/storefront/checkout-steps';
import { useCart } from '@/components/storefront/cart-context';
import { cn, formatCurrency } from '@/lib/utils';
import { home } from '@/routes';

type Props = {
    order: {
        order_number: string;
        status: string;
        grand_total: string;
        payment_status: string | null;
    } | null;
};

const paymentStatusLabels: Record<string, string> = {
    pending: 'Menunggu konfirmasi pembayaran',
    settlement: 'Pembayaran diterima',
    capture: 'Pembayaran diterima',
    deny: 'Pembayaran ditolak',
    expire: 'Tautan pembayaran kedaluwarsa',
    cancel: 'Pembayaran dibatalkan',
    refund: 'Pembayaran dikembalikan',
};

/** Status pembayaran yang berujung gagal/dibatalkan. */
const failedStatuses = ['deny', 'expire', 'cancel', 'refund'];

/**
 * Stempel status — impresi cap tinta di atas struk: LUNAS (paling menonjol),
 * MENUNGGU (garis putus-putus), atau GAGAL (merah). Sedikit miring seperti
 * cap sungguhan.
 */
function StatusStamp({ paymentStatus, isPaid }: { paymentStatus: string | null; isPaid: boolean }) {
    const failed = paymentStatus !== null && failedStatuses.includes(paymentStatus);

    const label = isPaid ? 'LUNAS' : failed ? 'GAGAL' : 'MENUNGGU';

    return (
        <span
            aria-hidden
            className={cn(
                'inline-flex -rotate-4 items-center rounded-md border-2 px-4 py-1.5 font-mono text-sm font-bold tracking-[0.28em] select-none',
                isPaid &&
                    'border-ink/80 bg-manila/30 text-ink',
                !isPaid && !failed && 'border-dashed border-ink/40 text-ink/60',
                failed && 'border-red-600/60 bg-red-500/10 text-red-700 dark:text-red-500',
            )}
        >
            {label}
        </span>
    );
}

export default function PaymentFinish({ order }: Props) {
    const { flash } = usePage().props;
    const { clear } = useCart();

    // Order sudah tersimpan → keranjang boleh dikosongkan.
    useEffect(() => {
        if (order) {
            clear();
        }
    }, [order, clear]);

    const paymentLabel = order?.payment_status ? (paymentStatusLabels[order.payment_status] ?? order.payment_status) : null;
    const isPaid = order?.status === 'paid';

    return (
        <>
            <Head title="Status Pesanan" />

            <section className="py-8 sm:py-10">
                <CheckoutSteps current={2} />

                {flash?.error && (
                    <div
                        role="alert"
                        className="mt-6 flex items-start gap-2.5 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm font-medium text-destructive"
                    >
                        <CircleAlert aria-hidden className="mt-0.5 size-4 shrink-0" />
                        {flash.error}
                    </div>
                )}

                {order === null ? (
                    <div className="mt-10 flex flex-col items-center gap-4 py-14 text-center">
                        <span className="flex size-16 items-center justify-center rounded-full bg-paper-dim/70 dark:bg-muted">
                            <PackageSearch
                                aria-hidden
                                className="size-7 text-muted-foreground/60"
                            />
                        </span>
                        <div>
                            <h1 className="font-serif text-2xl">
                                Order tidak ditemukan
                            </h1>
                            <p className="mx-auto mt-2 max-w-md text-sm leading-relaxed text-muted-foreground">
                                Nomor order tidak dikenali. Periksa kembali
                                tautan Anda atau hubungi kami.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="mx-auto mt-6 w-full max-w-md">
                        {/* Struk pesanan — kertas hangat, garis titik-titik, dan
                         * stempel status. Identitas satu keluarga dengan
                         * halaman login back-office. */}
                        <div className="relative rounded-xl border border-ink/15 bg-paper p-6 text-ink shadow-[0_16px_40px_-24px_rgba(0,0,0,0.4)] sm:p-8 dark:border-border dark:text-foreground dark:shadow-none">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="font-mono text-[10px] tracking-[0.22em] text-ink/55 uppercase dark:text-muted-foreground">
                                        Struk pesanan
                                    </p>
                                    <p className="mt-1.5 font-mono text-lg font-semibold tracking-wide">
                                        {order.order_number}
                                    </p>
                                </div>
                                <StatusStamp
                                    paymentStatus={order.payment_status}
                                    isPaid={isPaid}
                                />
                            </div>

                            <div className="mt-5 border-t border-dashed border-ink/20 pt-5 dark:border-border">
                                <h1 className="font-serif text-2xl text-balance">
                                    {isPaid
                                        ? 'Pembayaran Berhasil!'
                                        : 'Terima kasih atas pesanan Anda'}
                                </h1>
                                <p className="mt-2 text-sm leading-relaxed text-ink/70 dark:text-muted-foreground">
                                    {paymentLabel ??
                                        'Simpan nomor order berikut untuk referensi Anda.'}
                                </p>
                            </div>

                            <dl className="mt-5 space-y-3 border-t border-dashed border-ink/20 pt-5 text-sm dark:border-border">
                                <div className="flex items-baseline gap-2">
                                    <dt className="text-ink/60 dark:text-muted-foreground">
                                        Nomor Order
                                    </dt>
                                    <span
                                        aria-hidden
                                        className="flex-1 -translate-y-1 border-b border-dotted border-ink/30 dark:border-border"
                                    />
                                    <dd className="font-mono font-semibold">
                                        {order.order_number}
                                    </dd>
                                </div>
                                <div className="flex items-baseline gap-2">
                                    <dt className="text-ink/60 dark:text-muted-foreground">
                                        Total
                                    </dt>
                                    <span
                                        aria-hidden
                                        className="flex-1 -translate-y-1 border-b border-dotted border-ink/30 dark:border-border"
                                    />
                                    <dd className="font-mono text-base font-bold">
                                        {formatCurrency(order.grand_total)}
                                    </dd>
                                </div>
                                <div className="flex items-baseline gap-2">
                                    <dt className="text-ink/60 dark:text-muted-foreground">
                                        Status Pembayaran
                                    </dt>
                                    <span
                                        aria-hidden
                                        className="flex-1 -translate-y-1 border-b border-dotted border-ink/30 dark:border-border"
                                    />
                                    <dd>{paymentLabel ?? '—'}</dd>
                                </div>
                            </dl>
                        </div>

                        {!isPaid && (
                            <p className="mx-auto mt-4 max-w-md text-center text-xs leading-relaxed text-muted-foreground">
                                Pembayaran Anda sedang diproses. Halaman ini
                                tidak ter-update otomatis — muat ulang nanti
                                untuk melihat status terbaru.
                            </p>
                        )}
                    </div>
                )}

                <div className="mt-8 flex justify-center">
                    <Button
                        asChild
                        variant="outline"
                        className="rounded-lg border-ink/20 hover:bg-ink/5"
                    >
                        <Link href={home()}>Kembali ke Katalog</Link>
                    </Button>
                </div>
            </section>
        </>
    );
}
