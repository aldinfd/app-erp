# 📋 Laporan Phase 6 — Modul Finance (Jurnal & Laporan)

> Status: ✅ **SELESAI** (18 Agustus 2026)
> Rencana asli: [plan.md](../../.claude/rules/plan.md) — Phase 6
> Tanpa migration baru — tabel jurnal & mapping sudah ada di migration schema
> final (2026-08-16); tidak ada keputusan/schema disetujui yang diubah.
> Dua keputusan baru disetujui user 2026-08-18: **COGS diposting sekarang**
> (tambah 2 baris data mapping, key sudah ada di schema) & **ongkir ditunda**
> sampai fitur aktif (tidak ada perubahan kode).

---

## 🎯 Apa itu Phase 6?

Semua transaksi Sales/Purchase sudah menghasilkan jurnal otomatis lewat
`JournalService` (Phase 4–5). Phase 6 membangun **sisi penglihatan &
kontrol finance**: halaman Jurnal Umum, jurnal manual untuk koreksi, Buku
Besar per akun, dan laporan **Laba Rugi** + **Neraca** — semua bisa
direkonstruksi dari riwayat jurnal yang immutable.

---

## ✅ Yang Sudah Dikerjakan

### 1. Keputusan desain disetujui user (2026-08-18)

| Keputusan | Pilihan |
|---|---|
| COGS/HPP penjualan | **Posting sekarang** — jurnal `sales_payment` menambah D `hpp` / C `persediaan` = Σ(qty × `cost_price` produk saat pembayaran); skip bila 0 |
| Akun pendapatan ongkir | **Tunda** sampai fitur ongkir aktif (shipping selalu 0 — tidak ada perubahan) |

### 2. COGS di JournalService + seeder mapping

- `postSalesPayment()` kini eager-load `items.product.cost_price` dan
  menambah 2 line COGS (skip saat COGS = 0, mengikuti pola `utang_ppn`)
- `JournalMappingSeeder` +2 baris: `sales_payment/hpp` → `5-1000`,
  `sales_payment/persediaan` → `1-3000` — **total 9 baris**, seeder
  idempotent, sudah dijalankan di MySQL dev `erp` (terverifikasi)
- Jurnal penjualan kini 4 line: kas D grand_total / pendapatan C subtotal /
  utang PPN C tax (jika ada) / HPP D + persediaan C COGS

### 3. FinanceReportService — kalkulasi laporan

- `ledger(account, from, to)` — buku besar per akun: saldo awal (mutasi
  sebelum `from`), mutasi dalam rentang, **saldo berjalan** per baris;
  arah saldo mengikuti sifat akun (asset/expense = debit-nature,
  liability/equity/revenue = credit-nature)
- `incomeStatement(from, to)` — pendapatan & beban per akun postable yang
  bergerak, total, dan laba/rugi bersih
- `balanceSheet(asOf)` — aset/liabilitas/ekuitas per akun per tanggal +
  baris **"Laba Tahun Berjalan"** (pendapatan − beban s.d. tanggal; tanpa
  jurnal penutup) sehingga Aset = Liabilitas + Ekuitas selalu balance
- Semua query select kolom eksplisit (aturan-database #5); sumber data
  selalu `journal_lines` + `journal_entries`

### 4. Controller + routes RBAC admin | staff_finance

- `JournalEntryController` — `index` (search nomor/deskripsi, filter
  rentang tanggal & sumber, paginasi 10 + lines per entry), `create`
  (hanya akun postable aktif), `store` (jurnal manual via
  **JournalService** — validasi balance/postable tetap satu pintu; user
  tercatat `posted_by`; gagal → kembali ke form dengan pesan error),
  `show` (detail + lines + pembuat)
- `GeneralLedgerController` — pilih akun (default pertama) + rentang
  tanggal, saldo awal dihitung dari mutasi sebelum `from`
- `FinancialReportController` — `reports/income-statement` (default bulan
  berjalan) & `reports/balance-sheet` (default hari ini)
- Route group `auth + verified + role:admin|staff_finance`;
  `journal-entries/create` didaftarkan sebelum `{journal_entry}`

### 5. Frontend back-office

- `finance/journal-entries/index` — tabel + filter (search, dari/sampai
  tanggal, sumber) + badge sumber + total debit per entry
- `finance/journal-entries/create` — form jurnal manual multi-baris:
  pilih akun, isi debit ATAU kredit (mengisi satu sisi mengosongkan sisi
  lain), **total live + indikator "✓ Jurnal balance"**, tombol submit
  terkunci sampai balance
- `finance/journal-entries/show` — detail entry + tabel lines + total
- `finance/general-ledger/index` — pilih akun + rentang tanggal, baris
  saldo awal, saldo berjalan per mutasi, total mutasi & saldo akhir
- `finance/reports/income-statement` & `finance/reports/balance-sheet` —
  filter periode/tanggal, section per kelompok akun, laba/rugi bersih,
  cek "✓ Neraca balance" + tautan silang antar laporan
- `types/finance.ts`, `finance/source.ts` (label & badge sumber), menu
  sidebar: **Jurnal Umum, Buku Besar, Laporan Keuangan** (admin &
  staff_finance)

### 6. Perbaikan saat penulisan test (2 bug ditemukan & difix)

1. **Relasi `postedBy` berganti `poster`** — Eloquent me-serialize relasi
   camelCase jadi snake_case (`posted_by`) sehingga bertabrakan dengan
   kolom `posted_by` (angka user id tertimpa array user / prop
   `entry.postedBy` tak pernah ada). Relasi satu kata menghindari
   tabrakan & konsisten dengan konvensi project (`vendor`, `customer`).
2. **Cast `entry_date` → `date:Y-m-d`** — cast `date` polos menyimpan
   `'Y-m-d H:i:s'`; di MySQL kolom DATE dipotong otomatis (aman), tapi di
   SQLite test perbandingan string `entry_date <= '2026-08-18'` gagal
   terhadap `'2026-08-18 00:00:00'` → neraca per hari kosong. Format
   eksplisit menyimpan `'Y-m-d'` di kedua database.

---

## 🧪 Testing

File baru **3** — **18 test baru**, semuanya lulus:

- `Finance/JournalEntryControllerTest` (10): index render + filter
  tanggal/search, create hanya akun postable aktif, store sukses
  (source `manual` + `posted_by` user), store timpang → error + 0 entry,
  store akun header → ditolak JournalService, validasi field wajib,
  show render + pembuat, **staff_gudang 403 semua route**, guest redirect
  (termasuk buku besar & laporan)
- `Finance/GeneralLedgerControllerTest` (4): default akun pertama +
  saldo berjalan (100k → 150k), **saldo awal dari mutasi sebelum
  rentang**, akun credit-nature tampil saldo positif, staff_gudang 403
- `Finance/FinancialReportTest` (4): laba rugi (revenue/expense/laba
  bersih 70k), filter periode mengecualikan bulan lalu, **neraca balance
  Aset 120k = Liabilitas 50k + laba berjalan 70k** (beban tidak jadi
  baris aset), staff_gudang 403
- Plus **+1 test COGS** di `JournalServiceTest` & update assertion
  `MidtransWebhookTest` (jurnal settlement kini 4 line)

Full suite: **225 test — 223 passed, 2 skipped** (baseline Phase 5: 206).
Pint: bersih. `tsc --noEmit`: bersih. Build FE: sukses (Node 25).

---

## ⚠️ Catatan & Kendala

1. ~~**🐛 Bug ditemukan di Phase 5 — BELUM diperbaiki (menunggu persetujuan,
   di luar scope sesi ini)**~~ **RESOLVED (19 Agustus 2026)**: halaman
   detail PO membaca `order.vendorInvoice` padahal Eloquent mengirim
   relasi multi-kata sebagai key `vendor_invoice` (snake_case) → section
   invoice vendor & pembayaran tidak pernah tampil. Fix mengikuti preseden
   `poster`: relasi model diganti satu kata `invoice()` (simetris dengan
   `SalesOrder::invoice()`) + regression test yang meng-assert nested prop
   `order.invoice.*` (dulu tidak di-assert sehingga lolos).
2. **Cast `date` polos di model lain** (`SalesOrder.order_date`,
   `PurchaseOrder.order_date/expected_date`, `Invoice.issued_date/due_date`,
   `VendorInvoice.*`) menyimpan `'Y-m-d H:i:s'` di SQLite test. Aman di
   MySQL (kolom DATE memotong) dan belum ada query boundary sama-hari di
   modul lain — tapi rentan bila nanti ada filter `<= tanggal`. Ikuti
   pola `date:Y-m-d` saat disentuh.
3. **Neraca tanpa jurnal penutup** — laba tahun berjalan tampil sebagai
   baris ekuitas terhitung, bukan diposting ke `Laba Ditahan`
   (`3-2000`). Jurnal penutup tahunan bisa ditambah nanti lewat jurnal
   manual.
4. **Ongkir ditunda** (keputusan user) — `shipping` selalu 0; mapping
   `sales_payment/pendapatan_ongkir` dibuat saat fitur ongkir aktif.
5. ~~Blocker Midtrans~~ **RESOLVED (19 Agustus 2026)** — key di `.env`
   terverifikasi **sandbox** (HTTP 201 dari Snap API sandbox saat buat
   token uji; `MIDTRANS_IS_PRODUCTION` tidak di-set → `false`).
   Catatan: prefix `SB-`/`Mid-` **bukan** penanda environment — key
   sandbox & production berbeda walau prefix sama; yang menentukan flag
   `MIDTRANS_IS_PRODUCTION`. Sisa: uji manual checkout end-to-end di
   browser (bayar kartu uji Midtrans).

---

## 📖 Cara Memakai

Menu baru di sidebar (login sebagai **admin** atau **staff_finance**):

1. **Jurnal Umum** (`/journal-entries`) — daftar semua jurnal (otomatis
   dari Sales/Purchase + manual). Filter: search nomor/deskripsi, rentang
   tanggal, sumber. Klik baris → detail lines. Tombol **+ Jurnal Manual**:
   pilih tanggal + deskripsi, tambah baris (akun + debit/kredit — cukup
   isi salah satu sisi), total harus balance (indikator hijau) sebelum
   simpan.
2. **Buku Besar** (`/general-ledger`) — pilih akun + rentang tanggal →
   saldo awal, mutasi per jurnal dengan saldo berjalan, saldo akhir.
3. **Laporan Keuangan** (`/reports/income-statement`) — Laba Rugi per
   periode; tautan **Lihat Neraca** (`/reports/balance-sheet`) — posisi
   per tanggal + cek balance otomatis.

Alur uji cepat: buat order di storefront (atau PO di back-office) →
bayar/terima → cek jurnal otomatis muncul di Jurnal Umum → buka Buku
Besar akun Kas & Bank → buka Laba Rugi & Neraca, angka konsisten.

---

## 📁 File Penting

| Jenis | Lokasi |
|---|---|
| Service | `app/Services/FinanceReportService.php` (baru), `app/Services/JournalService.php` (+COGS) |
| Controller | `app/Http/Controllers/Finance/{JournalEntryController,GeneralLedgerController,FinancialReportController}.php` |
| Routes | `routes/web.php` (group finance RBAC) |
| FE | `resources/js/pages/finance/**` (6 halaman + `source.ts`), `types/finance.ts`, menu `components/app-sidebar.tsx` |
| Seeder | `database/seeders/JournalMappingSeeder.php` (+2 baris COGS, total 9) — sudah jalan di MySQL dev |
| Model fix | `app/Models/JournalEntry.php` (relasi `poster`, cast `date:Y-m-d`) |
| Test | `tests/Feature/Finance/{JournalEntryControllerTest,GeneralLedgerControllerTest,FinancialReportTest}.php` (baru), `JournalServiceTest` (+COGS), `tests/Feature/Sales/MidtransWebhookTest.php` (4 line) |

---

## 🏁 DoD Phase 6

- [x] `JournalService` terpusat dengan validasi balance + auto nomor +
      link reference (dari Phase 4–5; dipakai ulang oleh jurnal manual)
- [x] JournalService dipanggil dari Phase 4 & 5 (Sales/Purchase) —
      single source of truth; mapping via `journal_mappings` (9 baris)
- [x] Halaman Jurnal Umum (list + lines, filter tanggal & sumber)
- [x] Manual journal entry (staff_finance) — validasi balance & postable
- [x] Buku Besar per akun (saldo awal + saldo berjalan)
- [x] Laporan Laba Rugi & Neraca — angka konsisten dengan jurnal
- [x] RBAC staff_finance & admin (403 diuji; guest redirect)
- [x] Audit trail (activity log menyertai CRUD jurnal manual)
- [x] Commit terpisah per fasa (pesan commit diserahkan ke user)

## ▶️ Langkah Berikutnya

1. **Phase 7 — Dashboard**: widget stok menipis, ringkasan penjualan
   bulanan, order/pembayaran tertunda, PO menunggu; grafik ringan.
2. Uji manual checkout end-to-end di browser (key sandbox terverifikasi
   2026-08-19 — bayar kartu uji Midtrans; jurnal 4 line + COGS ikut
   teruji). Status struk kini juga di-sync dari Status API saat payment
   masih pending (webhook tak bisa menjangkau dev lokal).
