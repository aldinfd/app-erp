# Plan Implementasi Aplikasi ERP (Toko Online Single-Seller)

> Sumber: [requirement.md](requirement.md)
> Tech stack: Laravel (BE) + React (FE) + Inertia + MySQL + Eloquent ORM
> Deployment: Render (app) + MySQL managed (Aiven / PlanetScale / Railway)
> Revisi 2026-08-16: auth scaffolding diganti dari Breeze ke **React Starter Kit + Fortify** (Breeze tidak lagi tercantum di dokumentasi resmi Starter Kits Laravel 13). Fortify menangani backend auth; Starter Kit menyediakan UI React 19 + TypeScript + Inertia 3 + Tailwind 4 + shadcn/ui dan struktur aplikasi.
> Revisi 2026-08-16: desain schema database dirinci di [database.md](database.md); keputusan desain #2–#4 (qty stok signed delta, tabel `journal_mappings`, `payments`/`vendor_payments` terpisah) disetujui user & disinkronkan ke section 2.

---

## 0. Ringkasan & Strategi

ERP internal untuk 1 toko/UMKM yang jualan online. Tim internal (Admin, Staff Gudang, Staff Finance) memakai back-office ERP; customer hanya memesan lewat storefront publik (bukan user ERP).

**Prinsip pengembangan:**
- Bangun **fondasi dulu** (autentikasi, RBAC, master data, audit) sebelum modul transaksi.
- Modul transaksi dibangun mengikuti **alur bisnis end-to-end**: setiap transaksi (Sales/Purchase) harus sampai ke **auto-jurnal Finance**.
- Integrasi pihak ketiga di-**isolasi** di service layer agar mudah di-swap/mocking saat test.
- Setiap phase punya **Definition of Done (DoD)** yang bisa diuji sebelum lanjut.

**Urutan fasa:**
1. Phase 0 — Setup proyek & tooling
2. Phase 1 — Fondasi (DB/ERD, Auth, RBAC, Audit, Notifikasi)
3. Phase 2 — Master Data
4. Phase 3 — Modul Inventory
5. Phase 4 — Modul Sales + Storefront
6. Phase 5 — Modul Purchase (Pembelian)
7. Phase 6 — Modul Finance (Jurnal & Laporan)
8. Phase 7 — Dashboard
9. Phase 8 — Reporting & Export
10. Phase 9 — Testing & QA
11. Phase 10 — Deployment & Go-live

---

## 1. Arsitektur & Keputusan Teknis

| Area | Pilihan | Catatan |
|---|---|---|
| Backend | PHP + Laravel | Latest LTS |
| Frontend | React (TypeScript) | via Inertia |
| Bridge BE↔FE | Inertia.js | SPA feel tanpa API terpisah |
| Styling UI | TailwindCSS | Tailwind 4 — bawaan React Starter Kit |
| Database | MySQL | via Eloquent ORM (driver `pdo_mysql`); hosting lihat Deployment |
| Auth scaffolding | React Starter Kit + Laravel Fortify | Fortify = backend auth headless (login/logout/register/reset password/email verify/2FA/rate-limit, dikontrol via `config/fortify.php`); Starter Kit = UI (React 19 + TS + Inertia 3 + shadcn/ui) + layout sidebar/header |
| RBAC | spatie/laravel-permission | Role: admin, staff_gudang, staff_finance |
| Audit trail | spatie/laravel-activitylog | Catat create/update/delete + before/after |
| Notifikasi | Laravel Notification (db + mail) | Email via Resend |
| Payment gateway | Midtrans (midtrans/midtrans-php) | Sandbox dulu |
| Email | Resend (resend/resend-laravel) | |
| Upload gambar | Cloudinary (cloudinary/laravel) | Free tier |
| Ongkir (opsional) | RajaOngkir via HTTP Client | Free |
| Export PDF | barryvdh/laravel-dompdf | |
| Export Excel | maatwebsite/excel | |

**Struktur folder saran (FE) — mengikuti struktur bawaan React Starter Kit:**
- `resources/js/pages/` — halaman (Inertia)
- `resources/js/components/` — komponen reusable (Tabel, Filter, Form, Modal; komponen shadcn/ui di `components/ui/`)
- `resources/js/layouts/` — layout bawaan (app sidebar/header, auth) — layout Storefront publik ditambahkan sendiri
- `resources/js/hooks/`, `resources/js/lib/`, `resources/js/types/` — bawaan Starter Kit

**Struktur folder saran (BE):**
- `app/Services/` — logika integrasi (MidtransService, ResendService, CloudinaryService, RajaOngkirService, JournalService)
- `app/Actions/` — aksi domain (CreateSalesOrder, ReceivePurchaseOrder, PostAutoJournal)
- `app/Http/Controllers/`, `app/Models/`

---

## 2. ERD & Model Data (proposal — mengisi section 11 requirement)

> Desain lengkap per kolom: lihat [database.md](database.md). Untuk pembuatan migration, database.md yang jadi acuan.

### Master data
- **users** (id, name, email, password, timestamps) — **tanpa kolom `role`**; RBAC via spatie (disetujui 2026-08-16)
- **roles / permissions** (via spatie)
- **products** (id, name, sku, category_id, unit_id, cost_price, selling_price, stock_qty, reorder_point, image_url, is_active, timestamps)
- **categories** (id, name, parent_id nullable)
- **units** (id, name, abbreviation) — pcs, kg, dll
- **customers** (id, name, email, phone, address, timestamps)
- **vendors** (id, name, email, phone, address, timestamps)
- **chart_of_accounts** (id, code, name, type[asset/liability/equity/revenue/expense], parent_id nullable, is_active)

### Transaksi Sales
- **sales_orders** (id, order_number, customer_id, order_date, status[draft/confirmed/paid/cancelled], subtotal, tax, shipping, grand_total, timestamps)
- **sales_order_items** (id, sales_order_id, product_id, qty, unit_price, subtotal)
- **invoices** (id, invoice_number, sales_order_id, issued_date, due_date, amount_paid, status[unpaid/partial/paid], timestamps)
- **payments** (id, invoice_id, amount, method, paid_at, gateway, gateway_ref, status)

### Transaksi Purchase
- **purchase_orders** (id, po_number, vendor_id, order_date, status[draft/ordered/received/paid/cancelled], grand_total, timestamps) — status `paid` disetujui 2026-08-16 (alur Phase 5: `draft → ordered → received → paid`)
- **purchase_order_items** (id, purchase_order_id, product_id, qty, unit_cost, subtotal)
- **vendor_invoices** (id, vendor_invoice_number, purchase_order_id, invoice_date, due_date, amount, amount_paid, status[unpaid/partial/paid], timestamps)
- **vendor_payments** (id, vendor_invoice_id, amount, method[bank_transfer/cash], reference_no, paid_at, notes, timestamps) — **tabel terpisah dari `payments`** (disetujui 2026-08-16: field & siklus hidup berbeda — Midtrans vs transfer manual)

### Inventory & Finance
- **stock_movements** (id, product_id, type[in/out/adjust], qty **signed delta** — in=+, out=−, adjust=± (disetujui 2026-08-16), before_qty, after_qty, reference_type, reference_id, user_id, note, created_at)
- **journal_entries** (id, entry_number, date, description, reference_type, reference_id, posted_by, timestamps)
- **journal_lines** (id, journal_entry_id, account_id, debit, credit)
- **journal_mappings** (id, transaction_type, account_key, account_id) — mapping akun auto-jurnal, UNIQUE(transaction_type, account_key); disetujui 2026-08-16
- **notifications** (via Laravel)
- **activity_log** (via spatie activitylog)

> **Catatan jurnal:** gunakan **double-entry**. Setiap `journal_entry` harus balance (sum debit == sum credit), divalidasi di model/service.

---

## 3. Fasa Implementasi

### Phase 0 — Setup Proyek & Tooling
**Tujuan:** proyek bisa jalan lokal + struktur dasar siap.

1. Buat proyek Laravel baru dengan **React Starter Kit** (via `laravel new`, pilih React) — sudah include: Fortify (backend auth), React 19 + TypeScript, Inertia 3, Tailwind 4, shadcn/ui, layout sidebar/header
2. Verifikasi TailwindCSS & TypeScript (include dari Starter Kit — tidak perlu install terpisah)
3. Konfigurasi koneksi MySQL di `.env` (`DB_CONNECTION=mysql`, isi `DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD`)
4. Install package pendukung: spatie/permission, spatie/activitylog, dompdf, excel, resend, cloudinary, midtrans/php
5. Setup struktur folder FE & BE sesuai section 1
6. Inisialisasi git, commit awal, siapkan repo
7. Buat base layout BackOffice (sidebar + topbar) dan layout Storefront (publik) — adaptasi dari layout bawaan Starter Kit

**DoD:** `php artisan serve` + `npm run dev` jalan, halaman login Starter Kit tampil, DB MySQL terkoneksi.

---

### Phase 1 — Fondasi: Auth, RBAC, Audit, Notifikasi
**Tujuan:** pondasi keamanan & pelacakan siap sebelum ada data.

1. Verifikasi & sesuaikan scaffolding Auth dari Starter Kit/Fortify (login, logout, lupa password); matikan registrasi publik (hapus `Features::registration()` di `config/fortify.php`) karena user ERP dibuat oleh admin
2. Setup spatie/permission: seed 3 role — `admin`, `staff_gudang`, `staff_finance`
3. Middleware role-based (cek akses per route/menu)
4. Guard storefront: route publik customer TIDAK butuh login ERP; hanya admin/staff yang akses back-office
5. Setup spatie/activitylog: global observer untuk catat CRUD pada model penting (before/after JSON)
6. Setup Laravel Notification: channel `database` + driver email Resend; siapkan template base
7. Seed 1 user per role untuk uji

**DoD:** login berfungsi, menu dibatasi sesuai role, aksi CRUD tercatat di activity log, notifikasi tes terkirim (db + email).

---

### Phase 2 — Master Data
**Tujuan:** semua data induk bisa di-CRUD + punya search/filter.

1. Migration + model + resource controller untuk: **categories**, **units**, **products**, **customers**, **vendors**, **chart_of_accounts**
2. Seeder data contoh (kategori, satuan, beberapa produk, CoA standar)
3. Halaman index per master dengan: tabel, **search**, **filter**, paginasi
4. Form create/edit (validasi server-side + FE)
5. Upload gambar produk → CloudinaryService (simpan `image_url`)
6. RBAC: mis. produk/kategori dikelola admin & staff_gudang; customer/vendor oleh admin & staff_finance
7. Soft-delete opsional pada data penting

**DoD:** semua master bisa dibuat/diedit/dihapus, gambar produk tersimpan di Cloudinary, search/filter berfungsi.

---

### Phase 3 — Modul Inventory
**Tujuan:** stok selalu akurat & tertelusur.

1. Logic stok sentral via service `StockService`: `add()`, `deduct()`, `adjust()` — selalu menulis `stock_movements`
2. Aturan: perubahan stok HANYA lewat StockService (tidak boleh update `products.stock_qty` langsung)
3. Halaman **Stock Movement** (riwayat masuk/keluar/adjust + filter tanggal/produk)
4. Halaman **Stock Opname / Adjust** (koreksi stok + alasan)
5. **Reorder point**: jika `stock_qty <= reorder_point` → emit event/notifikasi "stok menipis"
6. Dashboard widget "produk stok menipis" (bisa di-lanjut di Phase 7)
7. RBAC: staff_gudang & admin kelola stok

**DoD:** setiap perubahan stok tercatat di stock_movements, notifikasi stok menipis memicu benar, saldo selalu match antara `products.stock_qty` dan sum stock_movements.

---

### Phase 4 — Modul Sales + Storefront
**Tujuan:** alur penjualan lengkap dari storefront sampai jurnal otomatis.

**A. Storefront (publik, customer)**
1. Halaman katalog produk (gambar, harga, stok status)
2. Keranjang (cart) — client-side / session
3. Checkout: isi data customer (guest), pilih ongkir via RajaOngkir (opsional)
4. Submit order → buat **Sales Order** status `draft/confirmed` + kunci item & harga
5. Redirect ke Midtrans Snap untuk bayar (Sandbox)

**B. Back-office (Sales)**
6. Webhook/payment notification dari Midtrans → update status pembayaran
7. Saat pembayaran **paid**:
   - Ubah Sales Order → `paid`
   - Terbitkan **Invoice** (status `paid`)
   - **Kurangi stok** via StockService (type `out`, reference = sales_order)
   - **Post auto-jurnal** ke Finance (via JournalService — di-detail di Phase 6)
8. Halaman manajemen Sales Order (list, detail, status, batalkan order)
9. Notifikasi "order baru" ke staff/admin

**Auto-jurnal Sales (contoh mapping):**
- Debit: Kas/Bank (akun aset) = grand_total
- Kredit: Pendapatan Penjualan (akun revenue) = subtotal
- Kredit: Utang PPN (opsional) = tax
- (opsional) Debit HPP / Kredit Persediaan untuk COGS

**DoD:** customer bisa checkout & bayar (sandbox), pembayaran memicu pengurangan stok + penerbitan invoice + jurnal otomatis, stok tetap akurat.

---

### Phase 5 — Modul Purchase (Pembelian)
**Tujuan:** alur pembelian ke vendor lengkap sampai jurnal otomatis.

1. Halaman **Purchase Order** (PO): staff_gudang buat PO ke vendor (dari saran stok menipis)
2. Status PO: `draft → ordered → received → paid`
3. Saat **barang diterima**:
   - Tambah stok via StockService (type `in`, reference = purchase_order)
   - Update status PO → `received`
4. **Invoice vendor & pembayaran**: catat vendor invoice (`vendor_invoices`), lalu bayar (`vendor_payments`)
5. Saat dibayar → **post auto-jurnal** ke Finance
6. Notifikasi "PO perlu ditindaklanjuti"

**Auto-jurnal Purchase (contoh mapping):**
- Saat diterima: Debit Persediaan / Kredit Hutang ke Vendor
- Saat dibayar: Debit Hutang ke Vendor / Kredit Kas/Bank

**DoD:** PO bisa dibuat, penerimaan menambah stok, pembayaran memicu jurnal otomatis, stok tetap akurat.

---

### Phase 6 — Modul Finance (Jurnal & Laporan)
**Tujuan:** semua transaksi ter-refleksi di keuangan, bisa dilaporkan.

1. `JournalService` terpusat: `post(entry)` dengan validasi **balance** (debit == credit), auto `entry_number`, link `reference`
2. JournalService dipanggil dari Phase 4 & 5 (single source of truth untuk auto-jurnal); mapping akun via tabel `journal_mappings` — bukan hard-code
3. Halaman **Jurnal Umum** (list journal_entries + lines, filter tanggal)
4. **Manual journal entry** (staff_finance) untuk koreksi/penyesuaian
5. **Buku Besar (General Ledger)** per akun
6. **Laporan keuangan dasar**: Laba Rugi, Neraca (jika CoA lengkap), Arus Kas (opsional)
7. RBAC: staff_finance & admin

**DoD:** setiap transaksi Sales/Purchase menghasilkan jurnal balance, laporan Laba Rugi & Neraca bisa di-generate dan angkanya konsisten dengan transaksi.

---

### Phase 7 — Dashboard
**Tujuan:** ringkasan eksekutif untuk pengambilan keputusan harian.

1. Dashboard per-role (konteks berbeda untuk admin vs staff)
2. Widget:
   - Produk stok menipis (dari reorder_point)
   - Ringkasan penjualan bulanan (total order, revenue)
   - Order/pembayaran tertunda
   - PO menunggu barang/pembayaran
3. Grafik sederhana (penjualan per bulan) — gunakan komponen chart ringan

**DoD:** dashboard menampilkan data real-time dari modul yang sudah dibangun.

---

### Phase 8 — Reporting & Export
**Tujuan:** laporan bisa diekspor untuk keperluan eksternal.

1. Laporan: Penjualan, Inventory (kartu stok), Keuangan
2. Filter rentang tanggal + parameter
3. **Export PDF** via dompdf (barryvdh/laravel-dompdf)
4. **Export Excel** via maatwebsite/excel
5. RBAC: staff_finance & admin

**DoD:** setiap laporan utama bisa di-preview, di-export PDF & Excel dengan benar.

---

### Phase 9 — Testing & QA
**Tujuan:** memastikan alur end-to-end bebas bug sebelum produksi.

1. Feature test alur kritis:
   - Sales: checkout → bayar → stok berkurang → invoice → jurnal
   - Purchase: PO → terima → stok bertambah → bayar → jurnal
   - Validasi balance jurnal
2. Unit test service: StockService, JournalService
3. Test integrasi (mock Midtrans/Resend/Cloudinary di env test)
4. Test RBAC (akses ditolak untuk role salah)
5. Uji happy path storefront di browser

**DoD:** test suite lulus, alur inti terverifikasi otomatis.

---

### Phase 10 — Deployment & Go-live
**Tujuan:** aplikasi live & dapat diakses.

1. Siapkan environment produksi Render (web service + build npm)
2. Provision DB MySQL managed (Aiven/PlanetScale/Railway) lalu set environment variables produksi (DB MySQL, Midtrans, Resend, Cloudinary, APP_KEY, dll)
3. Migration & seed awal di DB MySQL produksi
4. Build asset: `npm run build`
5. Setup webhook Midtrans ke URL produksi
6. Smoke test produksi (login, buat order sandbox, cek jurnal)
7. Dokumentasi singkat: cara deploy, env, akun default

**DoD:** aplikasi live di Render, DB MySQL terkoneksi, alur inti berfungsi di produksi (mode sandbox untuk payment).

---

## 4. Catatan Risiko & Pertimbangan

- **Konsistensi stok & jurnal**: gunakan **DB transaction** pada setiap aksi yang menyentuh stok + jurnal agar tidak setengah-jalan (data korup).
- **Engine MySQL**: pastikan tabel memakai **InnoDB** (default Laravel/MySQL modern) agar foreign key & DB transaction berfungsi; kolom nominal/uang pakai tipe `DECIMAL` (bukan `FLOAT`) demi presisi.
- **Idempotensi webhook Midtrans**: handle notifikasi pembayaran ganda (cek `gateway_ref`/status sebelum proses ulang).
- **Auto-jurnal mapping**: siapkan tabel/config mapping akun per jenis transaksi agar fleksibel, jangan hard-code id akun. → Realisasi: tabel `journal_mappings` (disetujui 2026-08-16, detail di database.md §8.3).
- **Keamanan storefront**: rate-limit checkout, validasi input customer, anti-spam order.
- **Mode sandbox vs produksi**: pisahkan config Midtrans via env, mudah switch.
- **Backup DB MySQL**: aktifkan jadwal backup/restore point di provider terpilih.

---

## 5. Checklist Antar-Fasa (Acceptance Gate)

Setiap fasa dianggap selesai jika:
- [ ] Fitur sesuai requirement
- [ ] RBAC diterapkan dengan benar
- [ ] Search/filter berfungsi (jika relevan)
- [ ] Audit trail mencatat aksi penting
- [ ] Tidak ada error fatal pada happy path
- [ ] Commit terpisah per fasa
