# 📋 Laporan Phase 3 — Modul Inventory

> Status: ✅ **SELESAI** (18 Agustus 2026)
> Rencana asli: [plan.md](../../.claude/rules/plan.md) — Phase 3
> Catatan sesi: UI tetap sederhana (fokus fungsi, lanjutan keputusan Phase 2)

---

## 🎯 Apa itu Phase 3?

Modul Inventory menjaga **stok selalu akurat & tertelusur**: semua perubahan
stok lewat satu pintu (`StockService`), setiap perubahan tercatat di
`stock_movements`, dan stok menipis memicu notifikasi otomatis.

---

## ✅ Yang Sudah Dikerjakan

### 1. StockService — satu-satunya pintu ubah stok

`app/Services/StockService.php` dengan tiga method publik:

| Method | Fungsi | Movement |
|---|---|---|
| `add()` | tambah stok (Phase 5: barang diterima) | type `in`, qty positif |
| `deduct()` | kurangi stok (Phase 4: penjualan) | type `out`, qty negatif |
| `adjust()` | koreksi hasil opname, alasan wajib | type `adjust`, qty ± delta |

Jaminan desain (semua diuji):
- Berjalan dalam **satu DB transaction** + `lockForUpdate()` pada baris produk —
  dua proses bersamaan tidak saling menimpa
- Selalu menulis baris `stock_movements` (before/after/qty signed delta;
  invariant `after_qty = before_qty + qty`)
- Stok kurang saat `deduct()` → `InsufficientStockException`, transaksi
  di-rollback (stok & movement tidak berubah)
- `adjust()` tanpa perubahan angka → tidak ada movement (return null)
- Event `LowStockDetected` di-dispatch **setelah commit** — gagal rollback
  tidak mengirim notifikasi
- `Product.stock_qty` memang tidak fillable (dipasang di Phase 2) — form
  mana pun tidak bisa menyentuh stok langsung

### 2. Notifikasi stok menipis (reorder point)

- Event `App\Events\LowStockDetected` + listener `SendLowStockNotification`
  (auto-discovery) → kirim `SystemNotification` (database + email) ke semua
  user ber-role **admin & staff_gudang**
- Aturan pemicu: notifikasi hanya saat stok **turun menembus** reorder point
  (sebelumnya di atas → sekarang `<=`). Movement berikutnya selama masih di
  bawah ambang **tidak** mengirim ulang (anti-spam); setelah stok pulih lalu
  turun lagi → kirim lagi

### 3. Halaman Riwayat Stok (read-only)

`/stock-movements` — tabel jejak audit: tanggal, produk, tipe (Masuk/Keluar/
Penyesuaian), qty berwarna (+/−), stok awal→akhir, referensi (Sales Order/
Purchase Order/Stock Opname), pelaku, catatan. Filter: **cari SKU/nama, tipe,
rentang tanggal**. Tidak ada tombol edit/hapus — tabel append-only.

### 4. Halaman Stock Opname

`/stock-opname` — daftar produk (stok sistem + reorder point, merah bila
menipis) dengan form per baris: **stok hasil hitung fisik + alasan (wajib)**.
Selisih dihitung sistem (± delta) dan dicatat sebagai movement `adjust`
dengan `user_id` pelaku.

### 5. RBAC

Route inventory dalam group `role:admin|staff_gudang` — `staff_finance`
ditolak 403 (diuji). Menu sidebar "Riwayat Stok" & "Stock Opname" hanya
tampil untuk admin & staff_gudang.

### 6. Seeder stok awal

`MasterDataSeeder` menambah stok awal 7 produk **via StockService** (bukan
update langsung) → 7 movement `in` tercatat. Idempotent (hanya produk tanpa
riwayat). `SKU-AK-001` sengaja di bawah reorder point (4 < 10) untuk demo
kondisi stok menipis. Sudah dijalankan di MySQL dev `erp`.

---

## 🧪 Testing

File baru `tests/Feature/Inventory/` — **27 test**, semuanya lulus:
- `StockServiceTest` (16): add/deduct/adjust + snapshot before/after,
  reference polymorphic, rollback saat stok kurang, deduct tepat 0,
  adjust naik/turun/tanpa perubahan, note wajib, **invariant
  `stock_qty` = Σ movement setelah operasi campuran**, notifikasi crossing
  (peran tepat, tidak dobel, tidak kirim saat aman, kirim lagi setelah pulih,
  tidak kirim saat rollback)
- `StockMovementControllerTest` (5): render + filter SKU/nama/tipe/tanggal,
  RBAC 403, guest redirect
- `StockOpnameControllerTest` (6): opname via HTTP (movement + user + note),
  angka sama → flash error tanpa movement, validasi note/qty/produk,
  RBAC 403

Full suite: **129 passed, 2 skipped**. Pint: bersih. `tsc --noEmit`: bersih.
Build FE: sukses (via `/opt/homebrew/bin/node`).

---

## ⚠️ Catatan & Kendala

1. **Tanpa factory StockMovement** — movement yang dibuat factory bisa
   melanggar invariant `stock_qty = Σ qty`; semua test & seeder memakai
   StockService sehingga konsistensi terjamin.
2. **Dashboard widget "produk stok menipis"** (plan Phase 3 butir 6)
   ditunda ke Phase 7 sesuai catatan rencana ("bisa di-lanjut di Phase 7") —
   dashboard masih placeholder starter kit.
3. Jebakan debugging sesi ini: `LowStockDetected::class` di test sempat tidak
   di-import → `Event::fake()` mem-fake class yang salah tanpa error.
   Dicatat sebagai aturan `.ai/rules/general.md` (cek blok `use`).
4. Halaman & filter mengikuti pola master Phase 2 (tabel polos, pagination,
   preserveState) — konsisten & mudah di-maintain.

---

## 📁 File Penting

| Jenis | Lokasi |
|---|---|
| Service | `app/Services/StockService.php` |
| Model | `app/Models/StockMovement.php` (immutable, tanpa updated_at), relasi `stockMovements()` di `Product` |
| Event/Listener | `app/Events/LowStockDetected.php`, `app/Listeners/SendLowStockNotification.php` |
| Exception | `app/Exceptions/InsufficientStockException.php` |
| Controller | `app/Http/Controllers/Inventory/{StockMovementController,StockOpnameController}.php` |
| Routes | `routes/web.php` (group gudang: `/stock-movements`, `/stock-opname`) |
| FE | `resources/js/pages/inventory/{stock-movements,stock-opname}/index.tsx`, menu di `components/app-sidebar.tsx`, `types/inventory.ts` |
| Seeder | `database/seeders/MasterDataSeeder.php` (stok awal via StockService) |
| Test | `tests/Feature/Inventory/*Test.php` (3 file) |
| Aturan baru | `.ai/rules/general.md` (import class wajib) |

---

## 🏁 DoD Phase 3

- [x] Setiap perubahan stok tercatat di `stock_movements` (service teruji,
      seeder & opname lewat service)
- [x] Notifikasi stok menipis memicu benar (crossing, tanpa duplikat,
      hanya admin & staff_gudang)
- [x] Saldo selalu match: `products.stock_qty` = Σ `stock_movements.qty`
      (test invariant + lock anti-race)
- [x] Halaman riwayat stok + stock opname dengan filter
- [x] RBAC staff_gudang & admin (403 diuji untuk staff_finance)

## ▶️ Langkah Berikutnya

Phase 4 — Modul Sales + Storefront: katalog publik, keranjang, checkout,
Midtrans sandbox, pengurangan stok otomatis via `StockService->deduct()`.
