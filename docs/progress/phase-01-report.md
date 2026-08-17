# 📋 Laporan Phase 1 — Fondasi: DB/ERD, Auth, RBAC, Audit, Notifikasi

> Status: ✅ **SELESAI** (17 Agustus 2026) — email Resend menunggu API key
> Diperbarui: 17 Agustus 2026
> Rencana asli: [plan.md](../../.claude/rules/plan.md) — Phase 1

---

## 🎯 Apa itu Phase 1?

Phase 1 adalah tahap **pondasi keamanan & data**: menyiapkan struktur database,
sistem login, hak akses (RBAC), pencatat aktivitas, dan notifikasi — semuanya
sebelum ada data transaksi.

> 📌 **Catatan:** bagian database (ERD + migration) dikerjakan lebih awal,
> bersamaan dengan akhir Phase 0 — atas keputusan user. Sisanya diselesaikan
> pada 17 Agustus 2026 (sesi lanjutan setelah Phase 0 selesai).

---

## ✅ Yang Sudah Dikerjakan (bagian DB/ERD)

### 1. Desain Database Lengkap → `schema-database.md`
Dokumen desain tersimpan di [.claude/rules/schema-database.md](../../.claude/rules/schema-database.md)
(versi 1.2). Berisi rincian **18 tabel** + relasinya untuk seluruh aplikasi:
master data (produk, kategori, customer, vendor, dll), transaksi sales,
transaksi pembelian, stok, dan jurnal keuangan.

**9 keputusan desain penting** sudah dibahas dan disetujui user (16 Agustus),
antara lain:

1. Tidak ada kolom `role` di tabel users — hak akses diatur spatie
2. Catatan pergerakan stok memakai angka **bertanda** (masuk = plus, keluar = minus)
3. Ada tabel `journal_mappings` — pemetaan akun jurnal otomatis yang bisa
   diubah tanpa ganti kode (tidak di-hard-code)
4. Pembayaran customer (`payments`) dan pembayaran ke vendor
   (`vendor_payments`) dipisah — kebutuhan kolomnya berbeda
5. Uang disimpan tipe `DECIMAL` (bukan float) supaya perhitungan presisi

### 2. Migration Dibuat & Dijalankan
5 file migration dibuat (16 Agustus):

| File | Isi |
|---|---|
| `..._create_master_data_tables.php` | categories, units, products, customers, vendors, chart_of_accounts |
| `..._create_sales_tables.php` | sales_orders, sales_order_items, invoices, payments |
| `..._create_purchase_tables.php` | purchase_orders, purchase_order_items, vendor_invoices, vendor_payments |
| `..._create_inventory_tables.php` | stock_movements |
| `..._create_finance_tables.php` | journal_entries, journal_lines, journal_mappings |

Semua sudah **dijalankan (migrate) ke MySQL `erp`** — diverifikasi berhasil.

### 3. Tabel Package Spatie
Tabel bawaan **spatie permission** (hak akses) dan **activitylog** (pencatat
aktivitas) ikut di-publish & migrate (17 Agustus). Total sekarang 12 migration
tercatat "Ran".

### 4. Test Otomatis Schema
`tests/Feature/DatabaseSchemaTest.php` — memastikan semua tabel tercipta dan
keputusan desain benar (misal: tidak ada kolom `role` di users). ✅ Lulus.

---

## ⏳ Yang Sudah Dikerjakan (sisa Phase 1 — sesi 17 Agustus 2026)

Semua 7 item tersisa selesai, lengkap dengan test otomatis:

### 5. Registrasi Publik Dimatikan
- `Features::registration()` dihapus dari `config/fortify.php` — route
  `/register` tidak ada lagi (404)
- Halaman register React + link "Sign up" di halaman login dihapus
- Route TypeScript di-regenerate ulang (`wayfinder:generate --with-form`)

### 6. 3 Role + 1 User per Role (Seeder)
- `RoleSeeder` — bikin role `admin`, `staff_gudang`, `staff_finance`
- `UserSeeder` — 1 user uji per role (password sama: `password`)
  - `admin@erp.test` (admin)
  - `staff.gudang@erp.test` (staff_gudang)
  - `staff.finance@erp.test` (staff_finance)
- Seeder idempoten (aman dijalankan berulang)

### 7. Middleware Role-Based
- Alias `role`, `permission`, `role_or_permission` (bawaan spatie)
  didaftarkan di `bootstrap/app.php`
- Route back-office (dashboard) dilindungi
  `role:admin|staff_gudang|staff_finance` — user tanpa role → 403

### 8. Guard Storefront
- Halaman publik `/` tetap tanpa login; user login pun tetap bisa lihat
  (dibuktikan test)

### 9. Activitylog Aktif di Model User
- Trait `HasActivity` + `getActivitylogOptions()` di model User:
  catat created/updated/deleted + before/after (`attribute_changes`),
  KECUALI kolom sensitif (password, 2FA, dll)
- Model transaksi (Product, dll) menyusul di phase masing-masing

### 10. Notifikasi (database + email)
- **Temuan**: starter kit Laravel 13 tidak menyediakan tabel `notifications`
  — dibuatkan migration sendiri (13 migration total sekarang)
- `App\Notifications\SystemNotification` — template base 2 channel:
  database (in-app) + mail (markdown `resources/views/mail/system-notification.blade.php`)
- Uji nyata ke user admin: tersimpan di tabel `notifications` ✅ dan email
  lengkap masuk `storage/logs/laravel.log` ✅
- ⚠️ `MAIL_MAILER=log` — kirim email NYATA via Resend menunggu
  `RESEND_API_KEY` di `.env` (masih kosong)

### 11. Menu Sidebar per Role (FE)
- Middleware Inertia mengirim `auth.roles` (daftar role user login)
- Sidebar menyaring menu lewat field `roles` di `NavItem`
  (menu tanpa `roles` = semua role internal boleh lihat)
- Nama role utama tampil di bawah nama user (sidebar)

---

## 🏁 Cek "Selesai Kalau" (DoD)

| Syarat | Status |
|---|---|
| Login berfungsi | ✅ (test suite lulus) |
| Menu dibatasi sesuai role | ✅ (roles terkirim ke FE + filter menu; route 403 untuk role salah) |
| Aksi CRUD tercatat di activity log | ✅ (User: create/update/delete + before/after) |
| Notifikasi tes terkirim (db + email) | ✅ db nyata; email via mailer `log` (Resend menunggu API key) |

**Hasil test:** 56 test — 54 lulus, 2 skip (test registrasi yang memang
otomatis skip karena fitur dimatikan), 205 assertion.

---

## 📚 Kamus Istilah di Phase 1

| Istilah | Arti |
|---|---|
| **ERD** | Gambar/desain tabel database & hubungannya |
| **RBAC** | Role-Based Access Control — sistem "siapa boleh apa" |
| **Role** | Peran user: admin / staff gudang / staff finance |
| **Migration** | Kode pembuat tabel database — tercatat & bisa dibalik (rollback) |
| **Migrate** | Menjalankan migration = benar-benar membuat tabel di database |
| **Seed / Seeder** | Kode pengisi data awal (misal: daftar role) |
| **Middleware** | "Penjaga pintu" yang memeriksa request sebelum masuk ke halaman |
| **Audit trail** | Jejak rekam semua aksi: siapa, melakukan apa, kapan |
| **Polymorphic** | Relasi fleksibel ke banyak jenis data (dipakai di relasi stok & jurnal) |
| **DECIMAL** | Tipe kolom angka yang presisi — wajib untuk uang (float bisa selisih sen) |
| **Observer** | Kode yang otomatis jalan saat data dibuat/diubah/dihapus |
| **spatie** | Vendor pembuat package permission & activitylog yang kita pakai |
| **403 / Forbidden** | Kode HTTP "akses ditolak" — dipakai saat role tidak berhak |
| **Mailer `log`** | Mode email "pura-pura kirim" — email ditulis ke file log, tidak benar-benar terkirim |
| **API key** | Kode rahasia untuk memakai layanan pihak ketiga (mis. Resend) |
