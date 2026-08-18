# 📋 Laporan Phase 4 — Modul Sales + Storefront

> Status: ✅ **SELESAI** (18 Agustus 2026) — uji manual Midtrans **belum tuntas**
> (key sandbox belum terpasang, lihat §Setup Integrasi)
> Rencana asli: [plan.md](../../.claude/rules/plan.md) — Phase 4
> Commit: `e8d33b9` (feat: add sales module + storefront checkout with Midtrans)
> Catatan sesi: ongkir & pajak dilewati (= 0, disetujui), keranjang client-side
> localStorage, invoice terbit saat checkout (deviasi disetujui — lihat Catatan #1)

---

## 🎯 Apa itu Phase 4?

Alur penjualan end-to-end: customer checkout di **storefront publik** → bayar
via **Midtrans Snap** (sandbox) → **webhook** → Sales Order `paid` + invoice
`paid` + **stok berkurang** via StockService + **auto-jurnal balance** via
JournalService baru. Semua dalam satu transaction per aksi (anti setengah-jalan).

---

## ✅ Yang Sudah Dikerjakan

### 1. Model Eloquent modul Sales & Finance

- `SalesOrder` (const status draft/confirmed/paid/cancelled, relasi
  `customer/items/invoice` + `payments()` hasManyThrough lewat invoice),
  `SalesOrderItem` (harga snapshot, tanpa timestamps), `Invoice`
  (unpaid/partial/paid/void), `Payment` (const method + status Midtrans,
  `PAID_STATUSES = [settlement, capture]`)
- Model jurnal: `JournalEntry` (source sales_payment/purchase_received/
  purchase_payment/manual; immutable seperti StockMovement — tanpa activity
  log), `JournalLine`, `JournalMapping`
- Factory: `SalesOrder`, `Invoice` (state `paid()`), `Payment` (state
  `paid()`)
- Tanpa migration baru — semua tabel sudah ada dari migration schema final

### 2. NumberGenerator + JournalService + seeder mapping

- `NumberGenerator::next(prefix, table, column)` → `{PREFIX}-YYYYMM-####`
  (max+1; race ditangkap UNIQUE constraint)
- `JournalService::post()` — satu pintu jurnal, dalam DB transaction:
  validasi **tepat satu sisi per line** (> 0, bukan dua-duanya), akun wajib
  `is_postable`, **Σ debit = Σ credit** (toleransi 0,001)
- `JournalService::postSalesPayment(payment)` — mapping akun via tabel
  `journal_mappings` (**tanpa hard-code id**): D `kas_bank` = grand_total,
  C `pendapatan_penjualan` = subtotal, C `utang_ppn` = tax (line dilewati
  saat tax = 0). Mapping wajib kosong → `RuntimeException` → rollback
- `JournalMappingSeeder` (idempotent, resolve akun **by code** 1-1000/4-1000/
  2-2000) — terdaftar di `DatabaseSeeder` dan sudah dijalankan di MySQL dev
  `erp` (3 baris `sales_payment` terverifikasi)

### 3. MidtransService (integrasi terisolasi di service layer)

- `createSnapTransaction(order)` → Snap `redirect_url`; params dikoreksi
  dari fakta SDK v2.6.2: **Midtrans menghitung ulang `gross_amount` dari
  `item_details`** dan qty wajib integer → kirim quantity=1 +
  price=subtotal baris, qty pecahan (kg) ditulis di nama item
- `verifySignature(payload)` — **manual sha512** (`order_id.status_code.
  gross_amount.server_key` + `hash_equals`); class `Midtrans\Notification`
  tidak dipakai karena tidak memverifikasi signature
- Config `services.midtrans` dari env + placeholder kosong di `.env.example`

### 4. Checkout backend (storefront publik)

- `CheckoutService::createOrder()` — **SATU DB transaction**: find-or-create
  Customer by email → SO `confirmed` (`SO-YYYYMM-####`) + items **snapshot
  harga dari DB** (harga client diabaikan) → invoice `unpaid` → payment
  `pending` dengan `gateway_ref = order_number` (NULL UNIQUE = kunci
  idempotensi webhook). Validasi: stok cukup (advisory), produk aktif, qty
  pecahan hanya untuk satuan `allows_fraction`. Event `SalesOrderCreated`
  di-dispatch **setelah commit**
- `SalesOrderCreated` + listener `SendNewOrderNotification` → notifikasi
  "Order baru" (in-app + email) ke **admin & staff_finance**
- Route publik: `/` (katalog), `/cart`, `/checkout` GET/POST, `/payment/finish`;
  checkout di-throttle **10/menit/IP**
- Submit checkout: order dibuat → redirect ke Snap; **pembuatan Snap gagal**
  (mis. key kosong) → order TETAP tersimpan, customer diarahkan ke halaman
  finish dengan pesan simpan nomor order

### 5. Webhook Midtrans

- `POST /webhooks/midtrans` — CSRF-exempt, throttle 60/menit/IP, signature
  salah → **401**
- `PaymentService::handleMidtransNotification()`:
  - `settlement` / `capture`+accept → **markPaid dalam 1 DB transaction**
    (lock + re-check idempoten): payment settlement + `paid_at`, invoice
    `paid`, SO `paid`, **StockService::deduct() per item** (movement `out`,
    reference sales_order), **JournalService::postSalesPayment()**
  - `capture`+challenge → noop (tetap pending); `deny/expire/cancel/refund`
    → update status payment saja
  - Order tak dikenal → log warning + 204 tanpa efek samping
  - Kegagalan cascade (stok kurang / mapping hilang / error lain) →
    **rollback atomic penuh** + `report()` + notifikasi ke admin &
    staff_finance + tetap respons sukses — payment `pending`, notifikasi
    ulang Midtrans bisa mencoba lagi

### 6. Storefront frontend

- Keranjang **localStorage** via `CartProvider`/`useCart` (client-side murni;
  server re-validasi harga & stok saat checkout)
- Halaman: **Katalog** (grid kartu, badge Tersedia/Habis, stepper qty per
  satuan, feedback "Ditambahkan ✓"), **Keranjang** (ubah qty/hapus, subtotal,
  CTA bawa query `?items=id:qty`), **Checkout** (form guest + ringkasan pakai
  harga server + peringatan stok berubah + error per baris item),
  **Payment Finish** (status order, tampil flash error, keranjang dikosongkan
  otomatis saat order ditemukan)
- Layout storefront: badge jumlah item keranjang di header
- Helper baru: `formatCurrency` (IDR), `formatDate`; `types/sales.ts`

### 7. Back-office Sales Order

- `SalesOrderController` — `index` (search nomor order / nama customer,
  filter status, pagination 10), `show` (customer, item + satuan, invoice,
  riwayat payment, flag `canCancel`), `cancel` (DB transaction + lock +
  re-check: SO `cancelled`, invoice `void`, payment pending → `cancel`;
  **ditolak** untuk order paid/cancelled — tanpa restore stok karena belum
  pernah deduct)
- Halaman `sales-orders/{index,show}` + `status.ts` (label & warna badge
  share), menu sidebar "Sales Order" (ikon ReceiptText, role admin &
  staff_finance)

---

## 🧪 Testing

File baru **4 + update 1** — **38 test baru**, semuanya lulus:

- `Finance/JournalServiceTest` (8): nomor JE berurut, balance, tolak
  unbalanced / dua sisi / nol / akun header (non-postable), utang_ppn
  skip-when-zero & include, mapping hilang → throw
- `Sales/CheckoutTest` (9): order+invoice+payment terbuat benar (snapshot
  harga server, `gateway_ref`), customer reuse by email, tolak stok kurang /
  qty pecahan satuan bulat / produk nonaktif / email invalid, gagal Snap →
  order tetap tersimpan + redirect finish, notifikasi hanya ke admin &
  staff_finance, rate limit 429
- `Sales/MidtransWebhookTest` (9): settlement cascade penuh (semua status +
  movement `out` negatif + jurnal 2 line balance), **idempoten 2× kirim**,
  capture accept ≈ settlement, capture challenge → pending, signature salah
  → 401, order tak dikenal → tanpa efek, deny/expire → payment saja,
  **stok kurang → rollback atomic + notifikasi**, mapping hilang → rollback
- `Sales/SalesOrderControllerTest` (10): index render+search+filter, show
  detail + canCancel, cancel sukses (invoice void + payment cancel), cancel
  ditolak untuk paid/cancelled, guest redirect login, staff_gudang 403
- `StorefrontTest` (2 → 4): prop `products` hanya produk aktif, halaman cart
  render guest

Full suite: **172 passed, 2 skipped** (baseline Phase 3: 129). Pint: bersih.
`tsc --noEmit`: bersih. Wayfinder: regenerate `--with-form`.

---

## ⚠️ Catatan & Kendala

1. **Deviasi disetujui**: invoice terbit saat **checkout** (status `unpaid`),
   bukan saat paid (plan.md B.7) — di-flip ke `paid` oleh webhook. Efek DoD
   identik.
2. **Ongkir & pajak = 0** (keputusan sesi): balance jurnal sales saat ini
   bergantung `shipping = 0` — saat ongkir aktif nanti butuh mapping akun
   pendapatan ongkir (keputusan Phase 6, satu paket dengan COGS/HPP yang
   memang di-skip ke Phase 6).
3. **Retry payment belum ada**: `gateway_ref = order_number` berarti 1 attempt
   per SO (Midtrans menolak order_id duplikat). Order dengan payment
   mati (`deny`/`expire`) dibersihkan manual via cancel di back-office.
4. **Window oversell** antara cek stok checkout (advisory, tanpa lock) dan
   deduct di webhook — by design ditangani `InsufficientStockException`:
   rollback atomic + notifikasi admin, payment pending bisa dicoba ulang
   notifikasi berikutnya.
5. **Kendala manual testing email**: notifikasi "order baru" dikirim ke 2
   staff (admin + staff_finance) via Resend; akun Resend tanpa domain
   terverifikasi hanya boleh kirim ke alamat pemilik akun → pengiriman ke
   staff kedua gagal. **Solusi lokal: `MAIL_MAILER=log`** (email ke
   laravel.log, notifikasi in-app tetap jalan). Produksi perlu verifikasi
   domain resend.com/domains.
6. **Known issue (belum dikerjakan, usulan sesi berikutnya)**: listener
   notifikasi berjalan sinkron — kegagalan kirim email bisa membuat request
   checkout 500 **setelah** order tersimpan (order aman, tapi redirect ke
   Snap tidak terjadi). Perbaikan: queue/try-catch di listener.
7. `payment/finish` menampilkan status/total ke siapa pun yang pegang nomor
   order (nomor sequential) — data minimal, acceptable untuk sandbox; catat
   untuk hardening.
8. Jebakan build FE: shell default nvm Node 18, Vite butuh ≥ 20 — test
   halaman Inertia baru 500 "Vite manifest" sampai `npm run build` dijalankan
   dengan Node 25. Dicatat sebagai aturan `.ai/rules/pages.md`.

---

## 🔧 Setup Integrasi (sesi lanjutan, 18 Agustus 2026 — setelah commit)

Pemasangan API key yang disiapkan user:

1. **RajaOngkir** ✅ terpasang di config (fitur belum aktif — lihat Catatan #2):
   - `config/services.php` → block `rajaongkir` (`key`, `base_url` default
     Starter/free, bisa dioverride via `RAJAONGKIR_BASE_URL` untuk Basic/Pro)
   - `.env` → `RAJAONGKIR_API_KEY` (var dirapikan dari `RAJA_ONGKIR_API_KEY`)
   - `.env.example` → placeholder `RAJAONGKIR_API_KEY` + `RAJAONGKIR_BASE_URL`
   - Catatan: API RajaOngkir tidak terjangkau dari jaringan sesi ini (timeout)
     → key belum divalidasi online; validasi menyusul saat fitur ongkir
     diaktifkan
2. **Midtrans** ⚠️ **TER-BLOCKER untuk uji manual**:
   - `.env` berisi `MIDTRANS_SERVER_KEY` berprefix `Mid-server-…` = key
     **PRODUCTION**, padahal `MIDTRANS_IS_PRODUCTION=false` (mode sandbox)
   - Kombinasi ini tidak akan jalan: request Snap dikirim ke server sandbox
     memakai credential produksi → ditolak → checkout selalu gagal di tahap
     pembuatan pembayaran (order tetap tersimpan, redirect ke halaman finish
     dengan pesan error)
   - **User sudah mencoba mengganti ke key sandbox tapi nilai di `.env` masih
     key production** — perlu dilanjutkan di sesi berikutnya:
     1. Login **https://dashboard.sandbox.midtrans.com** (BUKAN
        dashboard.midtrans.com) → Settings → Access Keys
     2. Salin Server Key berprefix **`SB-Mid-server-`**
     3. Ganti nilai `MIDTRANS_SERVER_KEY` di `.env`, restart `php artisan serve`
     4. Verifikasi: `php artisan tinker --execute 'echo substr((string) config("services.midtrans.server_key"), 0, 11);'`
        → harus `SB-Mid-server`
   - `MIDTRANS_CLIENT_KEY` belum diisi — opsional untuk alur redirect Snap
     (hanya perlu server key); isi juga kalau mau lengkap
3. **Email** ✅ `MAIL_MAILER=log` sudah diset user (solusi kendala #5);
   notifikasi in-app tetap jalan normal

---

## 🔑 Yang Perlu Diisi User (testing sandbox)

- [x] `RAJAONGKIR_API_KEY` — terpasang
- [x] `MIDTRANS_SERVER_KEY` — terisi, **tapi masih key production** (lihat
      §Setup Integrasi #2 — blocker uji manual)
- [ ] `MIDTRANS_CLIENT_KEY` — opsional untuk alur redirect
- [ ] Webhook ke lokal: `ngrok http 8000` → daftarkan URL publik sebagai
      **Notification URL** di dashboard sandbox (Settings → Configuration);
      tanpa ngrok, webhook bisa disimulasikan via curl ke localhost dengan
      signature sha512 (contoh di laporan sesi / test `MidtransWebhookTest`)

---

## 📁 File Penting

| Jenis | Lokasi |
|---|---|
| Model | `app/Models/{SalesOrder,SalesOrderItem,Invoice,Payment,JournalEntry,JournalLine,JournalMapping}.php` |
| Service | `app/Services/{CheckoutService,PaymentService,MidtransService,JournalService,NumberGenerator}.php` |
| Event/Listener | `app/Events/SalesOrderCreated.php`, `app/Listeners/SendNewOrderNotification.php` |
| Controller | `app/Http/Controllers/Storefront/{Catalog,Checkout}Controller.php`, `Webhook/MidtransController.php`, `Sales/SalesOrderController.php` |
| Routes | `routes/web.php` (publik + webhook + group sales-orders admin\|staff_finance) |
| FE storefront | `resources/js/pages/storefront/{home,cart,checkout,payment-finish}.tsx`, `components/storefront/cart-context.tsx`, `layouts/storefront-layout.tsx` |
| FE back-office | `resources/js/pages/sales-orders/{index,show,status}.tsx`, menu di `components/app-sidebar.tsx`, `types/sales.ts` |
| Seeder | `database/seeders/JournalMappingSeeder.php` (sudah jalan di MySQL dev) |
| Test | `tests/Feature/Finance/JournalServiceTest.php`, `tests/Feature/Sales/{CheckoutTest,MidtransWebhookTest,SalesOrderControllerTest}.php` |
| Aturan baru | `.ai/rules/pages.md` (build FE sebelum test halaman Inertia baru) |

---

## 🏁 DoD Phase 4

- [x] Customer bisa checkout & bayar — alur lengkap terverifikasi otomatis
      (checkout → payment → cascade); uji manual sandbox tinggal menunggu
      key Midtrans diisi user
- [x] Pembayaran memicu pengurangan stok (movement `out` via StockService) +
      penerbitan/pelunasan invoice + jurnal otomatis balance
- [x] Stok tetap akurat (deduct hanya lewat StockService, rollback atomic
      saat gagal, idempoten terhadap notifikasi ganda)
- [x] RBAC back-office sales (admin & staff_finance; 403 diuji)
- [x] Notifikasi "order baru" ke admin & staff_finance
- [x] Search/filter halaman Sales Order
- [x] Commit terpisah per fasa (`e8d33b9`)

## ▶️ Langkah Berikutnya

1. **Tuntaskan uji manual Midtrans** — ganti `MIDTRANS_SERVER_KEY` dengan key
   sandbox `SB-Mid-server-…` (langkah detail di §Setup Integrasi #2), lalu
   test checkout → bayar sandbox → verifikasi cascade (SO paid, stok
   berkurang, jurnal balance). Perubahan config sejak commit (`services.php`,
   `.env.example`, `.env`) + laporan ini + `.ai/rules/pages.md` **belum
   di-commit**.
2. Opsional (usulan): tahan-gagal-nya notifikasi email (queue/try-catch di
   listener) supaya kegagalan Resend tidak mengganggu checkout
3. Phase 5 — Modul Purchase: PO ke vendor (`draft → ordered → received →
   paid`), barang diterima → stok bertambah via `StockService->add()`,
   vendor invoice & payment, auto-jurnal `purchase_received` /
   `purchase_payment` (mapping akun sudah disiapkan di schema)
