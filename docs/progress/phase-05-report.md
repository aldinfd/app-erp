# 📋 Laporan Phase 5 — Modul Purchase (Pembelian)

> Status: ✅ **SELESAI** (18 Agustus 2026)
> Rencana asli: [plan.md](../../.claude/rules/plan.md) — Phase 5
> Commit: `d3f39e0` (feat: add purchase module with PO lifecycle, vendor
> payments and auto-journal) + follow-up `780e7ae` (seeder vendor) —
> lihat §Perbaikan Pasca-Laporan
> Tanpa migration baru — tabel purchase sudah ada di migration schema final
> (2026-08-16); tidak ada keputusan/schema disetujui yang diubah

---

## 🎯 Apa itu Phase 5?

Alur pembelian end-to-end: staff gudang buat **Purchase Order** ke vendor →
PO dipesan → barang **diterima** (stok bertambah via StockService +
auto-jurnal Persediaan/Hutang Vendor) → finance catat **invoice vendor** →
**bayar** (auto-jurnal Hutang Vendor/Kas & Bank) → PO `paid`. Setiap transisi
yang menyentuh stok + jurnal berjalan dalam SATU DB transaction.

---

## ✅ Yang Sudah Dikerjakan

### 1. Model Eloquent + factory

- `PurchaseOrder` (status `draft/ordered/received/paid/cancelled`, relasi
  `vendor/items/vendorInvoice`), `PurchaseOrderItem` (snapshot `unit_cost`,
  tanpa timestamps), `VendorInvoice` (`unpaid/partial/paid/void`),
  `VendorPayment` (`bank_transfer`/`cash`)
- Factory: `PurchaseOrder` (state `ordered()`, `received()`), `VendorInvoice`
  (state `paid()`), `VendorPayment`

### 2. JournalService — auto-jurnal pembelian

- `postPurchaseReceived(order)` — D `persediaan` = grand_total,
  C `hutang_vendor` = grand_total (source `purchase_received`)
- `postPurchasePayment(payment)` — D `hutang_vendor` = amount,
  C `kas_bank` = amount (source `purchase_payment`); **per payment** → cicilan
  tetap menghasilkan entry balance masing-masing
- `requireMappings()` — helper mapping wajib via `journal_mappings`
  (id akun tidak pernah di-hard-code); mapping hilang → `RuntimeException`
  → rollback atomic
- `JournalMappingSeeder` +4 baris: `purchase_received/persediaan` → 1-3000,
  `purchase_received/hutang_vendor` → 2-1000, `purchase_payment/hutang_vendor`
  → 2-1000, `purchase_payment/kas_bank` → 1-1000 — sudah dijalankan di MySQL
  dev `erp` (total 7 baris terverifikasi)

### 3. PurchaseService (inti alur)

- `create()` — PO `draft` + item snapshot harga beli dari **input staff**
  (bukan `cost_price` produk); validasi produk ada, qty > 0, qty pecahan
  hanya untuk satuan `allows_fraction`, harga ≥ 0; nomor `PO-YYYYMM-####`
- `markOrdered()` — `draft → ordered` (lock + re-check); notifikasi ke
  admin & staff_gudang
- `receive()` — `ordered → received` dalam satu transaction:
  `StockService::add()` per item (movement `in`, reference `purchase_order`,
  user tercatat) + status + `postPurchaseReceived`; notifikasi ke admin &
  staff_finance ("catat invoice vendor dan lakukan pembayaran")
- `cancel()` — hanya `draft/ordered`; PO `received`/`paid` **tidak bisa
  dibatalkan** (stok & jurnal sudah posting — koreksi lewat reversal Phase 6)
- `recordVendorInvoice()` — hanya untuk PO `received`, 1 PO maksimal
  1 invoice (UNIQUE `purchase_order_id`)
- `pay()` — `vendor_payments`, `amount_paid` bertambah (tolak bayar melebihi
  sisa), invoice `unpaid → partial → paid`, **lunas → PO `paid`**,
  auto-jurnal per payment — semua dalam satu transaction

### 4. Controller + routes dengan RBAC terpisah

- `PurchaseOrderController` — `index` (search nomor PO / nama vendor, filter
  status, paginasi 10), `create` (vendor aktif + produk aktif), `store`,
  `show` (detail + item + invoice + riwayat pembayaran + flag aksi),
  `markOrdered`/`receive`/`cancel`
- `VendorInvoiceController` — `store` (catat invoice), `storePayment` (bayar)
- RBAC mengikuti requirement.md (gudang kelola PO, finance kelola uang):
  - PO CRUD + transisi: **admin | staff_gudang**
  - Invoice vendor + pembayaran: **admin | staff_finance**
  - Index/show PO: **3 role internal** (finance perlu akses halaman PO untuk
    mencatat invoice di sana)
- Urutan route: `purchase-orders/create` didaftarkan sebelum
  `purchase-orders/{purchase_order}` agar tidak tertangkap param show

### 5. Frontend back-office

- `purchase-orders/index` — tabel + search + filter status; tombol buat PO
  hanya tampil untuk admin & staff_gudang
- `purchase-orders/create` — form dinamis multi-item (pilih produk → harga
  beli terisi dari `cost_price`, boleh diubah), step qty mengikuti
  `allows_fraction`, subtotal live, tambah/hapus baris
- `purchase-orders/show` — kartu vendor/ringkasan/invoice, tabel item dengan
  satuan, aksi berkonfirmasi (Tandai Dipesan / Terima Barang / Batalkan),
  form catat invoice (muncul untuk finance saat `received`), tabel
  pembayaran + form bayar (amount default sisa, metode transfer/cash)
- `types/purchase.ts`, `status.ts` (label & badge), menu sidebar
  "Purchase Order" (ikon Truck, 3 role)

---

## 🧪 Testing

File baru **2** — **31 test baru**, semuanya lulus:

- `Purchase/PurchaseServiceTest` (13): receive cascade (stok + movement
  `in` + jurnal 2 line balance + `user_id` movement), tolak receive dari
  status salah, **rollback atomic saat mapping jurnal dihapus**, notifikasi
  per role (ordered → gudang, received → finance), pay full (invoice + PO
  `paid` + jurnal), pay partial 2 tahap (2 jurnal masing-masing balance),
  tolak overpay & invoice lunas, invoice hanya untuk `received`, tolak
  duplikat invoice per PO, snapshot harga input, tolak qty pecahan
- `Purchase/PurchaseOrderControllerTest` (18): index render/search/filter,
  create hanya vendor & produk aktif, store (draft + `PO-202608-0001` +
  snapshot), tolak qty pecahan (error `items.0.qty`), flag aksi di show per
  role, transisi ordered/receive/cancel via HTTP (receive = cascade penuh),
  tolak transisi salah, alur penuh invoice → bayar → PO `paid` + jurnal,
  tolak overpay, guest redirect, **staff_finance 403 di aksi gudang tapi
  boleh index/show**, **staff_gudang 403 di aksi finance**

Full suite: **205 test — 203 passed, 2 skipped** (baseline Phase 4: 172).
Pint: bersih. `tsc --noEmit`: bersih. Build FE: sukses (Node 25).
Wayfinder: regenerate `--with-form`.

Perbaikan test infra: `UnitFactory` abbreviation (3 huruf pertama kata)
bisa tabrakan UNIQUE antar kata berbeda → ditambah angka unik.

---

## ⚠️ Catatan & Kendala

1. **Pajak beli masuk ke persediaan** — mapping `purchase_received` hanya
   `persediaan` & `hutang_vendor` (sesuai schema-database.md §8.3; tidak
   ada akun PPN masukan). Kalau nanti ingin memisahkan PPN masukan,
   tinggal tambah baris mapping + ubah `postPurchaseReceived`.
2. **PO received/paid tidak bisa dibatalkan** — stok sudah masuk & hutang
   sudah dijurnal; koreksi lewat jurnal reversal (Phase 6). Cancel hanya
   untuk draft/ordered (belum menyentuh stok/jurnal).
3. **Jurnal per cicilan** — pembayaran parsial menghasilkan beberapa entry
   `purchase_payment` (satu per payment, masing-masing balance) — bukan satu
   entry gabungan. Konsisten dengan jurnal immutable.
4. **Notifikasi email** — sama seperti Phase 4: lokal pakai `MAIL_MAILER=log`;
   in-app tetap jalan. Produksi perlu domain Resend terverifikasi.
5. ~~Blocker Midtrans~~ **RESOLVED (19 Agustus 2026)** — key di `.env`
   terverifikasi sandbox (Snap API sandbox merespons 201; prefix key
   bukan penanda environment — lihat laporan Phase 6 Catatan #5).

---

## 🔧 Perbaikan Pasca-Laporan (18 Agustus 2026)

Ditemukan saat uji manual setelah laporan terbit:

1. **Dropdown vendor kosong** (commit `780e7ae`) — bukan bug kode: tabel
   `vendors` belum pernah diisi (seeder hanya menyediakan kategori/satuan/
   produk). Fix: `MasterDataSeeder` + 4 vendor contoh (semua aktif,
   idempotent, sudah jalan di MySQL dev) + regression test
   `VendorTest::test_master_data_seeder_provides_active_vendors`.
   Full suite kini **206 test — 204 passed, 2 skipped**.
2. **Input qty menolak angka bulat** — pesan browser
   *"The two nearest valid values are 9,01 and 10,01"* di form PO.
   Penyebab: atribut `min="0.01"` dipadukan `step="1"` untuk satuan bulat —
   browser menghitung nilai valid **mulai dari `min`** sehingga nilai sah
   menjadi 0,01 / 1,01 / … Fix: `min` mengikuti satuan — `1` untuk bulat,
   `0.01` untuk pecahan (kg) — di `purchase-orders/create.tsx`.
   Halaman lain sudah dicek aman (stock opname & reorder point pakai
   `min="0"`; storefront min/step sudah selaras). **Belum di-commit.**

---

## 📁 File Penting

| Jenis | Lokasi |
|---|---|
| Model | `app/Models/{PurchaseOrder,PurchaseOrderItem,VendorInvoice,VendorPayment}.php` |
| Service | `app/Services/PurchaseService.php`, `JournalService.php` (2 method baru) |
| Controller | `app/Http/Controllers/Purchase/{PurchaseOrderController,VendorInvoiceController}.php` |
| Routes | `routes/web.php` (3 group: gudang / view 3-role / finance) |
| FE | `resources/js/pages/purchase-orders/{index,create,show,status}` , `types/purchase.ts`, menu `components/app-sidebar.tsx` |
| Seeder | `database/seeders/JournalMappingSeeder.php`, `MasterDataSeeder.php` (vendor contoh) — sudah jalan di MySQL dev |
| Factory | `database/factories/{PurchaseOrderFactory,VendorInvoiceFactory,VendorPaymentFactory,UnitFactory}.php` |
| Test | `tests/Feature/Purchase/{PurchaseServiceTest,PurchaseOrderControllerTest}.php`, `tests/Feature/Master/VendorTest.php` (+1 seeder test) |

---

## 🏁 DoD Phase 5

- [x] PO bisa dibuat (draft, snapshot harga, nomor `PO-YYYYMM-####`)
- [x] Penerimaan menambah stok via `StockService->add()` + movement `in`
      + jurnal `purchase_received` balance — dalam satu transaction
- [x] Pembayaran vendor memicu jurnal `purchase_payment` otomatis;
      lunas → invoice `paid` + PO `paid`
- [x] Stok tetap akurat (perubahan hanya lewat StockService, rollback
      atomic teruji saat mapping jurnal hilang)
- [x] RBAC: gudang vs finance dipisah (403 diuji kedua arah), view 3 role
- [x] Notifikasi "PO perlu ditindaklanjuti" (dipesan → gudang,
      diterima → finance untuk invoice & pembayaran)
- [x] Search/filter halaman Purchase Order
- [x] Commit terpisah per fasa (`d3f39e0`)

## ▶️ Langkah Berikutnya

1. **Phase 6 — Modul Finance**: halaman Jurnal Umum (list entry + lines,
   filter tanggal), **jurnal manual** (staff_finance), Buku Besar per akun,
   laporan Laba Rugi & Neraca. `JournalService` + seluruh mapping sudah
   terpasang dari Phase 4–5 — Phase 6 fokus ke UI + pelaporan.
   Pertimbangan yang menunggu keputusan Phase 6: akun pendapatan ongkir &
   COGS/HPP untuk penjualan (lihat laporan Phase 4 Catatan #2).
2. Uji manual checkout end-to-end (key Midtrans sandbox terverifikasi
   2026-08-19).
