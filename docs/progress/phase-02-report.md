# 📋 Laporan Phase 2 — Master Data

> Status: ✅ **SELESAI** (18 Agustus 2026)
> Diperbarui: 18 Agustus 2026
> Rencana asli: [plan.md](../../.claude/rules/plan.md) — Phase 2
> Catatan sesi: UI sengaja tidak diprioritaskan (permintaan user) — fokus fungsi

---

## 🎯 Apa itu Phase 2?

Phase 2 menyiapkan **data induk (master data)** — bahan baku sebelum modul
transaksi: kategori, satuan, produk, customer, vendor, dan Chart of Accounts.
Semua bisa di-CRUD lewat back-office dengan search/filter, paginasi, upload
gambar produk, dan pembatasan akses per role.

---

## ✅ Yang Sudah Dikerjakan

### 1. Model (6) + Factory (6)

Model di `app/Models/`: `Category`, `Unit`, `Product`, `Customer`, `Vendor`,
`ChartOfAccount`.

- Konvensi PHP 8.5 `#[Fillable([...])]` + `casts()` untuk uang/qty (`decimal:2`) dan boolean
- `SoftDeletes` semua kecuali `Unit` (sesuai schema-database.md §4)
- Trait `HasActivity` (spatie activitylog) di semua model → aksi CRUD master
  otomatis tercatat di `activity_log` dengan before/after (`logFillable()->logOnlyDirty()`)
- ⚠️ `Product.stock_qty` **sengaja tidak fillable** — hanya boleh diubah
  StockService (Phase 3). Ada test yang membuktikan `stock_qty` dari request diabaikan
- Catatan sesi: percobaan trait custom `LogsCrudActivity` dihapus karena
  bertabrakan dengan `HasActivity::getActivitylogOptions()` — method
  `getActivitylogOptions()` ditulis langsung di tiap model

### 2. Resource Controller (6) + Routes + RBAC

Controller di `app/Http/Controllers/Master/`, route resource hanya 6 aksi
(index/create/store/edit/update/destroy — tanpa `show`).

| Master | Route | Role yang boleh |
|---|---|---|
| Categories | `/categories` | admin, staff_gudang |
| Units | `/units` | admin, staff_gudang |
| Products | `/products` | admin, staff_gudang |
| Customers | `/customers` | admin, staff_finance |
| Vendors | `/vendors` | admin, staff_finance |
| Chart of Accounts | `/chart-of-accounts` | admin, staff_finance |

Fitur per controller: validasi inline, search (`q`), filter (produk: kategori +
status aktif; vendor: status; CoA: tipe akun), paginasi 10/baris dengan
`withQueryString()`, soft delete, dan guard hapus (kategori yang masih dipakai
produk/anak, satuan yang dipakai produk, akun yang punya sub-akun ditolak).

Bug penting yang ditemukan & diperbaiki: **implicit route model binding**
mensyaratkan nama variabel controller = camelCase nama param route
(`{chart_of_account}` → `$chartOfAccount`). Salah nama → Laravel inject model
kosong (id NULL) → update/destroy diam-diam tidak berefek. Dicatat sebagai
aturan di `.ai/rules/controllers.md`.

### 3. Seeder Data Contoh

- `MasterDataSeeder` — 4 kategori, 4 satuan, 7 produk contoh (idempotent, `firstOrCreate`)
- `ChartOfAccountSeeder` — CoA standar: 5 akun header (non-postable) + 10 akun
  postable (Kas & Bank, Piutang, Persediaan, Hutang Vendor, Utang PPN, Modal,
  Laba Ditahan, Pendapatan Penjualan, HPP, Beban Operasional) — siap dipakai
  `journal_mappings` di Phase 6
- Keduanya ditambahkan ke `DatabaseSeeder`

### 4. Halaman FE (18 halaman)

`resources/js/pages/master/{master}/{index,create,edit}.tsx` untuk 6 master.
Fokus fungsi: tabel HTML polos, form Inertia `<Form {...route.form()}>`,
validasi server tampil via `InputError`, checkbox boolean pakai pola hidden
`value="0"` + checkbox `value="1"` agar nilai terkirim saat tidak dicentang.

Tambahan pendukung:
- `components/master/pagination.tsx` — navigasi Sebelumnya/Berikutnya
- `components/flash-messages.tsx` + share `flash` di `HandleInertiaRequests` —
  notifikasi sukses/gagal hasil CRUD (alert di atas konten)
- Menu sidebar per role (`app-sidebar.tsx`) — produk/kategori/satuan hanya
  tampak untuk admin & staff_gudang; customer/vendor/CoA untuk admin &
  staff_finance
- `types/master.ts` — tipe TS untuk props master + `Paginated<T>`

### 5. Upload Gambar Produk

- Input file di form create/edit produk → disimpan ke disk `public`
  (`storage/app/public/products/`)
- URL disimpan di kolom `image_url` (URL-agnostic, siap swap ke Cloudinary)
- `php artisan storage:link` sudah aktif; validasi `image|max:2048`

---

## 🧪 Testing

File baru `tests/Feature/Master/` — 48 test, semuanya lulus:
- CRUD + validasi per master (wajib diisi, duplikat SKU/kode/singkatan, email invalid, tipe CoA tidak sah)
- Search & filter (nama/SKU/email/telepon, filter kategori/status/tipe)
- RBAC (role salah → 403 di semua master)
- Upload gambar (`Storage::fake`) — file tersimpan + URL di `image_url`
- Invariant: `stock_qty` dari request diabaikan (tetap 0.00)
- Guard hapus (kategori/satuan/akun yang masih dipakai tidak bisa dihapus)

Full suite: **102 passed, 2 skipped** (skip bawaan starter kit).
Pint: beres (fixer import di 7 file). `npx tsc --noEmit`: bersih. Build FE: sukses.

---

## ⚠️ Catatan & Kendala

1. **Node versi**: `npm run build` gagal di Node 18 (nvm default) karena
   rolldown butuh `styleText` dari `node:util`. Build dijalankan manual dengan
   `/opt/homebrew/bin/node` (v25). → Sebaiknya `nvm use` versi baru sebelum
   `npm run dev`/`build`.
2. **UI sederhana disengaja** — tidak ada sorting kolom, tidak ada konfirmasi
   hapus selain `confirm()` browser, tanpa state kosong bergambar. Pantas
   dipoles nanti setelah modul inti selesai.
3. Hapus kategori/satuan/CoA memakai **soft delete** (kecuali Unit yang
   memang tanpa soft delete sesuai schema — dihapus permanen jika tidak dipakai).
4. Produk yang dihapus (soft delete) tidak muncul lagi di index — riwayat
   transaksi Phase 4/5 tetap aman karena barisnya masih ada di DB.

---

## 📁 File Penting

| Jenis | Lokasi |
|---|---|
| Model | `app/Models/{Category,Unit,Product,Customer,Vendor,ChartOfAccount}.php` |
| Controller | `app/Http/Controllers/Master/*Controller.php` |
| Routes | `routes/web.php` |
| Seeder | `database/seeders/{MasterDataSeeder,ChartOfAccountSeeder}.php` |
| FE | `resources/js/pages/master/**`, `resources/js/components/master/pagination.tsx`, `resources/js/components/flash-messages.tsx` |
| Test | `tests/Feature/Master/*Test.php` |
| Aturan baru | `.ai/rules/controllers.md` (route model binding) |

---

## 🏁 DoD Phase 2

- [x] Semua master bisa dibuat/diedit/dihapus (CRUD jalan, ada guard hapus)
- [x] Gambar produk tersimpan di storage lokal (`public` disk)
- [x] Search/filter berfungsi di semua index
- [x] RBAC per master sesuai pembagian role
- [x] Seeder data contoh + CoA standar
- [x] Test lulus (48 test master, full suite 102 passed)

## ▶️ Langkah Berikutnya

Phase 3 — Modul Inventory: `StockService` (satu-satunya pintu ubah stok),
halaman stock movement, stock opname, notifikasi stok menipis.
