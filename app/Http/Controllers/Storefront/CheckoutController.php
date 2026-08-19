<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\CheckoutService;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    /**
     * Halaman checkout: muat produk yang diminta dari DB supaya harga & stok
     * yang tampil selalu terkini (keranjang client hanya display).
     * Format query: ?items=1:2,3:1.5 (product_id:qty).
     */
    public function create(Request $request): Response
    {
        $requested = $this->parseItems((string) $request->query('items', ''));

        $products = Product::query()
            ->with('unit:id,name,abbreviation,allows_fraction')
            ->where('is_active', true)
            ->whereIn('id', array_keys($requested))
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'selling_price', 'stock_qty', 'image_url', 'unit_id']);

        return Inertia::render('storefront/checkout', [
            'products' => $products,
            'requested' => $requested,
        ]);
    }

    /**
     * Submit checkout guest: validasi → buat SO + invoice + payment pending
     * (satu transaction) → redirect ke Midtrans Snap. Kegagalan pembuatan Snap
     * TIDAK membatalkan order (sudah tersimpan) — customer diarahkan ke
     * halaman finish dengan pesan menyimpan nomor order.
     *
     * Return type union: Inertia::location() mengembalikan Response 409
     * (request X-Inertia) atau RedirectResponse (request biasa non-JS).
     */
    public function store(Request $request, CheckoutService $checkout, MidtransService $midtrans): HttpResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01', 'max:999999'],
        ]);

        $order = $checkout->createOrder(
            $request->only(['name', 'email', 'phone', 'address']),
            $validated['items'],
        );

        try {
            $snapUrl = $midtrans->createSnapTransaction($order);
        } catch (\Exception $e) {
            report($e);

            return redirect()
                ->route('payment.finish', ['order_id' => $order->order_number])
                ->with('error', 'Pesanan Anda tersimpan, namun pembayaran gagal dibuat. Simpan nomor order Anda dan hubungi kami.');
        }

        // Redirect eksternal WAJIB Inertia::location() — 302 biasa tidak bisa
        // diikuti request Inertia (XHR) karena lintas domain → network error.
        return Inertia::location($snapUrl);
    }

    /**
     * Halaman status setelah kembali dari Midtrans (callback finish) atau
     * saat pembayaran gagal dibuat. Lookup by order_number; tidak ketemu →
     * state "order tidak ditemukan", bukan 404.
     */
    public function finish(Request $request, PaymentService $payments): Response
    {
        $orderId = (string) $request->query('order_id', '');

        $order = SalesOrder::query()
            ->where('order_number', $orderId)
            ->with('invoice.payments')
            ->first();

        // Webhook Midtrans bisa belum sampai (dev lokal tanpa URL publik,
        // atau notifikasi terlambat) — tarik status terbaru langsung dari
        // Midtrans agar struk tidak menampilkan "pending" padahal sudah dibayar.
        $payment = $order?->invoice?->payments->first();

        if ($payment !== null && $payment->status === Payment::STATUS_PENDING) {
            $payments->syncGatewayStatus($payment);

            $order->refresh(); // atribut + relasi ter-load diambil ulang setelah sync
        }

        return Inertia::render('storefront/payment-finish', [
            'order' => $order === null ? null : [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'grand_total' => $order->grand_total,
                'payment_status' => $order->invoice?->payments->first()?->status,
            ],
        ]);
    }

    /**
     * Parse "1:2,3:1.5" menjadi map product_id => qty; format salah diabaikan.
     *
     * @return array<int, float>
     */
    private function parseItems(string $items): array
    {
        preg_match_all('/(\d+):(\d+(?:\.\d{1,2})?)/', $items, $matches, PREG_SET_ORDER);

        $requested = [];

        foreach ($matches as $match) {
            $requested[(int) $match[1]] = (float) $match[2];
        }

        return $requested;
    }
}
