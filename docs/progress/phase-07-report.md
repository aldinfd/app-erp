# 📋 Laporan Phase 7 — Dashboard

> Status: ✅ **SELESAI** (19 Agustus 2026)
> Rencana asli: [plan.md](../../.claude/rules/plan.md) — Phase 7
> Tanpa migration, tanpa package baru, tanpa perubahan schema/keputusan
> yang sudah disetujui. Grafik dibuat sebagai **komponen chart ringan
> custom** (div/Tailwind) — tidak menambah dependency (aturan kerja #8).

---

## 🎯 Apa itu Phase 7?

Halaman dashboard Starter Kit masih placeholder polos. Phase 7 menggantinya
dengan ringkasan harian yang menarik datanya real-time dari modul yang sudah
dibangun (Inventory, Sales, Purchase, Finance) — dengan **konteks berbeda
per role**: gudang melihat stok & barang dalam perjalanan, finance melihat
penjualan & pembayaran, admin melihat keduanya.

---

## ✅ Yang Sudah Dikerjakan

### 1. DashboardController — widget difilter per-role di SERVER

- Route `dashboard` diganti dari `Route::inertia` polos menjadi
  `Route::get(..., DashboardController::class)` (invocable, preseden
  `MidtransController`)
- Widget yang tidak relevan untuk role **tidak dikirim ke FE sama sekali**
  (bukan sekadar disembunyikan di UI) — dicek via spatie `hasRole()`
  di controller:
  - **admin + staff_gudang** → `low_stock`, `po_waiting_goods`
  - **admin + staff_finance** → `monthly_sales`, `pending_sales`,
    `po_waiting_payment`, `sales_chart`

### 2. Widget (sesuai plan Phase 7)

| Prop | Isi | Definisi |
|---|---|---|
| `low_stock` | `count` + 10 produk stok terendah (dengan satuan) | `stock_qty <= reorder_point` — definisi sama dengan notifikasi stok Phase 3 |
| `po_waiting_goods` | jumlah PO | status `ordered` (sudah dipesan, belum diterima) |
| `monthly_sales` | `total_orders` + `revenue` bulan berjalan | total order = semua non-cancelled bulan ini; revenue = Σ grand_total order **paid** bulan ini |
| `pending_sales` | `orders` + `invoices` | order `draft`/`confirmed`; invoice `unpaid`/`partial` |
| `po_waiting_payment` | jumlah invoice vendor | `vendor_invoices` status `unpaid`/`partial` |
| `sales_chart` | 6 titik `{month, revenue}` | revenue order paid per bulan (5 bulan lalu + berjalan); bulan kosong tetap 0. Grouping SQL `substr(order_date,1,7)` — berjalan di MySQL & SQLite |

### 3. Frontend

- **`StatTile`** — kartu KPI reusable (label / nilai / deskripsi, bisa
  jadi tautan ke halaman terkait: produk, sales order, PO)
- **`SalesChart`** — bar chart custom ringan TANPA library: kolom ≤24px
  rounded atas 4px, gridline hairline + skala "mulus" (1/2/5×10^p),
  label bulan id-ID, **tooltip per batang saat hover & fokus keyboard**,
  nilai bulan terakhir dilabel langsung, **tabel sr-only** untuk screen
  reader. Warna biru lolos validator kontras ≥3:1 di permukaan light &
  dark (`#2a78d6` / `#3987e5`)
- **Halaman `dashboard.tsx`** rewrite: baris kartu KPI responsif per
  role (gudang 2 kartu + tabel stok menipis; finance 4 kartu + grafik
  full-width; admin 6 kartu + grafik & tabel berdampingan), empty state
  "Belum ada penjualan" saat revenue 0, link silang ke Laba Rugi
- `types/dashboard.ts` baru (+export di `types/index.ts`) — semua props
  optional karena per-role

### 4. Bug ditemukan & diperbaiki saat penulisan test

**`today()`/`now()` mengembalikan `CarbonImmutable`** (Carbon 3 +
Laravel 13) — `$cursor->addMonth()` TIDAK memutasi sehingga semua titik
grafik memakai bulan yang sama (2026-03 × 6). Fix: reassign hasil
(`$cursor = $cursor->addMonth()`). Jebakan ini **direkam ke
`.ai/rules/general.md`** (record-rule) agar tidak terulang di modul lain.

### 5. Verifikasi di aplikasi nyata (bukan hanya test)

Login HTTP sebagai `staff.gudang@erp.test` & `staff.finance@erp.test`
ke server dev (:8000), ambil props Inertia `/dashboard`:

- staff_gudang hanya menerima `low_stock` + `po_waiting_goods` ✓
- staff_finance hanya menerima `monthly_sales`, `pending_sales`,
  `po_waiting_payment`, `sales_chart` ✓
- Semua angka **cocok persis dengan query langsung ke DB dev**:
  low_stock 1 (Topping Laptop 0/10), PO ordered 1, revenue bulan ini
  Rp 45.000 (1 order paid), chart benar Mar–Agu 2026 dengan titik
  terakhir 45.000

---

## 🧪 Testing

`tests/Feature/DashboardTest.php` diperluas **2 → 6 test** (semua lulus):

- Guest redirect & user terautentikasi bisa membuka dashboard (2 lama)
- Admin menerima semua 6 widget; `sales_chart` tepat 6 titik
- staff_gudang: widget gudang ada, **props finance `missing()`**
- staff_finance: widget finance ada, **props gudang `missing()`**
- Angka widget diuji terhadap data nyata: 2 produk menipis (stok diisi
  lewat `StockService` — tetap satu pintu), revenue 150rb bulan ini,
  order bulan lalu 100rb jatuh di titik chart indeks 4, invoice/PO/
  vendor invoice pending terhitung

Full suite: **235 test — 233 passed, 2 skipped** (baseline Phase 6: 225
+ ekspansi DashboardTest). Pint bersih, `tsc --noEmit` bersih,
`npm run build` sukses (Node 25).

Catatan teknis test: nilai float ber-fraksi nol di-encode JSON sebagai
int (`150000.0` → `150000`) tanpa `JSON_PRESERVE_ZERO_FRACTION` —
assertion Inertia (strict `===`) memakai int.

---

## ⚠️ Catatan & Kendala

1. **Verifikasi visual di browser belum dilakukan** — tidak ada tool
   browser/screenshot di sesi ini (chromium-cli & Playwright tidak
   tersedia). Halaman ter-render 200 + props & build terverifikasi;
   silakan buka `/dashboard` di browser (server dev :8000 sudah jalan,
   asset hasil `npm run build` terbaru) untuk cek visual grafik.
2. **Admin di DB dev adalah `erp@replyloop.com`** (bukan
   `admin@erp.test`) — password bukan password seed, jadi tampilan
   gabungan admin tidak diverifikasi via HTTP; kedua set widget
   terverifikasi lewat 2 akun staff (union-nya = tampilan admin).
3. **Definisi revenue** = order berstatus `paid` (uang benar-benar
   masuk), bukan confirmed — dicatat supaya konsisten ke depan.
4. Widget hanya membaca data (read-only) → tidak ada aksi CRUD baru
   yang perlu diaudit; activity log modul lain tetap berjalan.

---

## 📖 Cara Memakai

Buka **Dashboard** setelah login (semua role internal):

1. **admin** — 6 kartu: Produk stok menipis, Penjualan bulan ini, Order
   tertunda, Invoice belum lunas, PO menunggu barang, PO menunggu
   pembayaran + grafik penjualan 6 bulan + tabel 10 produk stok
   terendah.
2. **staff_gudang** — kartu stok menipis & PO menunggu barang + tabel
   produk stok menipis (klik "Kelola produk" untuk ke halaman produk,
   lanjut buat PO dari sana).
3. **staff_finance** — kartu penjualan bulan ini (revenue + jumlah
   order), order tertunda, invoice belum lunas, PO menunggu pembayaran
   + grafik penjualan 6 bulan (hover/fokus batang untuk nilai per bulan,
   tautan "Lihat Laba Rugi" untuk laporan detail).

Kartu yang punya tautan bisa diklik langsung ke halaman terkait
(produk / sales order / purchase order). Angka dashboard selalu
fresh — dihitung dari query langsung setiap kali halaman dibuka.

---

## 📁 File Penting

| Jenis | Lokasi |
|---|---|
| Controller | `app/Http/Controllers/DashboardController.php` (baru) |
| Routes | `routes/web.php` (dashboard → controller) |
| FE komponen | `resources/js/components/dashboard/{stat-tile,sales-chart}.tsx` (baru) |
| FE halaman | `resources/js/pages/dashboard.tsx` (rewrite) |
| Types | `resources/js/types/dashboard.ts` (baru) |
| Test | `tests/Feature/DashboardTest.php` (2 → 8 test) |
| Aturan baru | `.ai/rules/general.md` (+jebakan CarbonImmutable) |

---

## 🏁 DoD Phase 7

- [x] Dashboard per-role (gudang / finance / admin — konteks berbeda,
      widget difilter server-side)
- [x] Widget produk stok menipis (dari reorder_point)
- [x] Ringkasan penjualan bulanan (total order, revenue)
- [x] Order/pembayaran tertunda
- [x] PO menunggu barang/pembayaran
- [x] Grafik sederhana penjualan per bulan (komponen ringan, tanpa
      dependency baru)
- [x] Data real-time dari modul terbangun (diverifikasi vs DB dev)
- [x] RBAC benar (middleware role + filter props; 403/redirect diuji
      di suite)
- [x] Tidak ada error fatal di happy path (suite + verifikasi HTTP)
- [x] Commit terpisah per fasa (pesan commit diserahkan ke user)

## ▶️ Langkah Berikutnya

1. **Phase 8 — Reporting & Export**: laporan Penjualan, Inventory
   (kartu stok), Keuangan + filter tanggal + export PDF (dompdf) &
   Excel (maatwebsite/excel) — keduanya sudah ter-install sejak Phase 0.
2. Cek visual dashboard di browser (point 1 di Catatan & Kendala).
3. Uji manual checkout end-to-end Midtrans sandbox (sisa Phase 4/6).
