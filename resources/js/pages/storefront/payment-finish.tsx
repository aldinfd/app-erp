import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { useCart } from '@/components/storefront/cart-context';
import { formatCurrency } from '@/lib/utils';
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

            <section className="flex flex-col items-center gap-6 py-16 text-center sm:py-24">
                <span className="text-5xl" aria-hidden>
                    {order ? (isPaid ? '✅' : '🧾') : '🔍'}
                </span>

                {flash?.error && (
                    <p className="max-w-xl rounded-md bg-destructive/10 px-4 py-2 text-sm text-destructive">{flash.error}</p>
                )}

                {order === null ? (
                    <>
                        <h1 className="text-2xl font-bold tracking-tight">Order tidak ditemukan</h1>
                        <p className="max-w-md text-muted-foreground">
                            Nomor order tidak dikenali. Periksa kembali tautan Anda atau hubungi kami.
                        </p>
                    </>
                ) : (
                    <>
                        <h1 className="text-2xl font-bold tracking-tight">
                            {isPaid ? 'Pembayaran Berhasil!' : 'Terima kasih atas pesanan Anda'}
                        </h1>
                        <p className="max-w-md text-muted-foreground">
                            {paymentLabel ?? 'Simpan nomor order berikut untuk referensi Anda.'}
                        </p>

                        <dl className="w-full max-w-sm space-y-2 rounded-xl border border-border p-4 text-left text-sm">
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">Nomor Order</dt>
                                <dd className="font-mono font-semibold">{order.order_number}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">Total</dt>
                                <dd className="font-semibold">{formatCurrency(order.grand_total)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">Status Pembayaran</dt>
                                <dd>{paymentLabel ?? '—'}</dd>
                            </div>
                        </dl>

                        {!isPaid && (
                            <p className="max-w-md text-xs text-muted-foreground">
                                Pembayaran Anda sedang diproses. Halaman ini tidak ter-update otomatis —
                                muat ulang nanti untuk melihat status terbaru.
                            </p>
                        )}
                    </>
                )}

                <Button asChild variant="outline">
                    <Link href={home()}>Kembali ke Katalog</Link>
                </Button>
            </section>
        </>
    );
}
