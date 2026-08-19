# 📋 Laporan Phase 8 — Reporting & Export

> Status: ✅ **SELESAI** (19 Agustus 2026)
> Rencana asli: [plan.md](../../.claude/rules/plan.md) — Phase 8
> Tanpa migration, tanpa package baru — dompdf (3.1.2) & maatwebsite/excel
> (4.0.0) sudah ter-install sejak Phase 0. Tidak ada keputusan/schema
> disetujui yang diubah.

---

## 🎯 Apa itu Phase 8?

Modul 1–7 menyimpan semua transaksi, tetapi laporannya hanya bisa dilihat
di layar. Phase 8 membuat **3 laporan utama bisa dipreview, difilter
rentang tanggal/parameter, dan di-export ke PDF + Excel**:

1. **Laporan Penjualan** — order non-cancelled dalam rentang tanggal + total
2. **Kartu Stok** (Inventory) — mutasi per produk: saldo awal → mutasi
   dengan saldo berjalan → saldo akhir
3. **Laporan Keuangan** — Laba Rugi & Neraca (preview dari Phase 6, kini
   plus export)

RBAC sesuai plan: **admin & staff_finance** (staff_gudang tetap punya
halaman Riwayat Stok sendiri).

---

## ✅ Yang Sudah Dikerjakan

### 1. Backend — service + controller + export

- **`SalesReportService`** — daftar order (nomor, tanggal, customer,
  status, subtotal/pajak/ongkir/total) + total per kolom. Order
  **cancelled dikecualikan** (definisi sama dengan dashboard). Filter
  tanggal pakai `whereDate` agar konsisten MySQL & SQLite test.
- **`StockCardService`** — kartu stok per produk: saldo awal (Σ qty
  sebelum tanggal mulai), mutasi dalam rentang **dengan saldo berjalan**,
  saldo akhir, total masuk/keluar. Sumber `stock_movements` (append-only)
  — angka selalu bisa direkonstruksi; qty signed delta (out = negatif).
- **`Reports/SalesReportController` & `Reports/StockCardController`** —
  masing-masing `index` (preview Inertia) + `pdf` (dompdf) + `excel`
  (maatwebsite). Default periode bulan berjalan (sama seperti Laba Rugi);
  kartu stok default produk pertama urut nama.
- **`FinancialReportController`** ditambah 4 method export (Laba Rugi &
  Neraca × PDF & Excel) — kalkulasi tetap lewat `FinanceReportService`
  (tidak duplikasi rumus).
- **`app/Exports/`** (konvensi maatwebsite): 4 class export
  `FromArray + WithHeadings + ShouldAutoSize`. **maatwebsite/excel 4.0
  mewajibkan native return type** (`array(): array`) — signature 3.x lama
  akan fatal. Nominal dibiarkan angka polos agar bisa dihitung ulang di
  spreadsheet.
- **View PDF** `resources/views/reports/` — layout bersama + 4 laporan
  (tabel border, nominal `Rp ... 2 desimal`, label status/tipe dari konstanta
  model `SalesOrder::STATUS_LABELS` & `StockMovement::TYPE_LABELS` —
  dipakai bersama PDF & Excel).
- **Routes** — group baru `role:admin|staff_finance` berisi 10 route:
  `reports/sales{,/pdf,/excel}`, `reports/stock-card{,/pdf,/excel}`,
  `reports/income-statement{,/pdf,/excel}`,
  `reports/balance-sheet{,/pdf,/excel}`.
- Nama file unduhan informatif: `laporan-penjualan_2026-08-01_2026-08-31.pdf`,
  `kartu-stok_SKU-MM-002_....xlsx`, dst.

### 2. Frontend

- **`pages/reports/sales`** — filter dari/sampai tanggal, tabel order
  (badge status dipakai ulang dari modul Sales), baris total, tombol
  **Export PDF / Export Excel** yang mengikuti periode yang sedang
  ditampilkan.
- **`pages/reports/stock-card`** — dropdown produk (SKU — nama) + rentang
  tanggal; baris saldo awal, mutasi (tipe, qty, saldo berjalan, referensi,
  catatan, pelaku), saldo akhir + ringkasan masuk/keluar. Qty format
  pecahan mengikuti satuan (`formatQty` dengan `allows_fraction`).
- Tombol Export PDF/Excel juga ditambahkan di halaman **Laba Rugi** &
  **Neraca** (mengikuti filter periode/tanggal aktif).
- Menu sidebar baru: **Laporan Penjualan** & **Kartu Stok**
  (admin & staff_finance).
- Catatan teknis: tombol export memakai `<a>` polos (bukan `<Link>`
  Inertia) — browser mengunduh file dari `Content-Disposition: attachment`
  tanpa memicu visit SPA.
- `types/reports.ts` baru; Wayfinder di-regenerate `--with-form` (route
  3-segmen seperti `reports.sales.pdf` menghasilkan file terpisah
  `routes/reports/sales` → `pdf`, `excel`).

### 3. Bug ditemukan & diperbaiki saat penulisan test

1. **Relasi `unit` NULL di kartu stok** — query produk di controller
   `get(['id','sku','name'])` tidak memuat `unit_id`, sehingga
   `loadMissing('unit:...')` mencari unit dengan key NULL → 500.
   Fix: select `unit_id` ikut diambil.
2. **`travelTo` + `today()` menumpuk** — `travelTo(today()->subDays(2))`
   dievaluasi SETELAH waktu dimundurkan, jadi `today()` ikut mundur dan
   selisih tanggal saling menumpuk (saldo awal salah). Fix: semua tanggal
   dihitung jadi variabel SEBELUM `travelTo` (pola baik untuk test
   berbasis waktu lain).

---

## 🧪 Testing

File baru **2** + expand **1** — **11 test baru**, semuanya lulus:

- `Reports/SalesReportTest` (5): preview + angka (2 order dihitung,
  cancelled & luar periode dikecualikan, total per kolom), PDF 200
  `application/pdf`, Excel 200 `…spreadsheetml.sheet`, staff_gudang 403
  (index+pdf+excel), guest redirect.
- `Reports/StockCardReportTest` (5): saldo awal dari mutasi sebelum
  rentang, saldo berjalan (10 → +5 = 15 → −2 = 13), mutasi produk lain
  tidak bocor, default produk pertama urut nama, PDF+Excel 200,
  staff_gudang 403, guest redirect. (Movements dibuat lewat
  **StockService** — tetap satu pintu; tanggal via `travelTo`.)
- `Finance/FinancialReportTest` (+1): export Laba Rugi & Neraca × PDF &
  Excel; route export ditambahkan ke test 403 staff_gudang.

Full suite: **246 test — 244 passed, 2 skipped** (baseline Phase 7: 235).
Pint: bersih. `tsc --noEmit`: bersih. Build FE: sukses (Node 25).

**Verifikasi aplikasi nyata** (login `staff.finance@erp.test` ke server
dev :8000): ke-8 unduhan mengembalikan file valid — PDF asli (`PDF
document, version 1.7`) berisi data nyata (SO-202608-0012, Aldi,
"Rp 45.000") dan xlsx asli (`Microsoft Excel 2007+`) berisi heading +
baris data; angka laporan penjualan **cocok persis dengan query DB dev**
(1 order, 45.000.00); kartu stok benar untuk satuan pecahan (Beras
Premium kg, 120.5).

---

## ⚠️ Catatan & Kendala

1. **Cek visual di browser belum dilakukan** di sesi ini (tidak ada tool
   browser) — halaman ter-render 200, props & unduhan terverifikasi.
   Silakan buka `/reports/sales`, `/reports/stock-card`, dan tombol export
   di browser (server dev :8000 dibiarkan berjalan, asset hasil build
   terbaru).
2. **PDF dompdf styling sederhana** (tabel border, tanpa kop surat/logo)
   — cukup untuk laporan internal; bisa ditingkatkan nanti di layout
   `resources/views/reports/layout.blade.php` tanpa ubah controller.
3. **Excel tanpa format angka/rupiah** — angka polos supaya bisa
   dihitung; judul laporan ada di nama file.
4. Kartu stok memilih satu produk per unduhan (sesuai konteks "kartu
   stok"); laporan semua produk sekaligus tetap tersedia lewat halaman
   Riwayat Stok.
5. Label status/tipe di BE dikonstanta model (`STATUS_LABELS`,
   `TYPE_LABELS`) — FE punya map sendiri (`status.ts`); keduanya disengaja
   agar tiap layer mandiri (Inertia tidak mengirim label).

---

## 📖 Cara Memakai

Login sebagai **admin** atau **staff_finance**, menu baru di sidebar:

1. **Laporan Penjualan** (`/reports/sales`) — pilih rentang tanggal →
   Tampilkan → tabel order + total. Tombol **Export PDF / Export Excel**
   di kanan atas mengunduh sesuai periode yang sedang ditampilkan.
2. **Kartu Stok** (`/reports/stock-card`) — pilih produk + rentang
   tanggal → saldo awal, mutasi dengan saldo berjalan, saldo akhir +
   total masuk/keluar. Export mengikuti produk & periode terpilih.
3. **Laporan Keuangan** (`/reports/income-statement`) — Laba Rugi seperti
   biasa, kini ada tombol **Export PDF / Export Excel**; begitu juga di
   **Neraca** (`/reports/balance-sheet`, export mengikuti tanggal posisi).

Contoh alur uji cepat: buka Laporan Penjualan bulan ini → cocokkan total
dengan kartu "Penjualan bulan ini" di dashboard → export PDF (cek total
di baris bawah) → buka Kartu Stok produk terjual → mutasi `out` sesuai
order → buka Laba Rugi → export Excel.

---

## 📁 File Penting

| Jenis | Lokasi |
|---|---|
| Service | `app/Services/SalesReportService.php`, `app/Services/StockCardService.php` (baru) |
| Controller | `app/Http/Controllers/Reports/{SalesReportController,StockCardController}.php` (baru), `Finance/FinancialReportController.php` (+4 export) |
| Export Excel | `app/Exports/{SalesReportExport,StockCardExport,IncomeStatementExport,BalanceSheetExport}.php` (baru) |
| View PDF | `resources/views/reports/{layout,sales,stock-card,income-statement,balance-sheet}.blade.php` (baru) |
| Routes | `routes/web.php` (group reporting RBAC) |
| FE | `resources/js/pages/reports/{sales,stock-card}.tsx` (baru), `finance/reports/*.tsx` (+tombol export), `components/app-sidebar.tsx`, `types/reports.ts` |
| Model | `app/Models/{SalesOrder,StockMovement}.php` (+const LABELS untuk PDF/Excel) |
| Test | `tests/Feature/Reports/{SalesReportTest,StockCardReportTest}.php` (baru), `Finance/FinancialReportTest.php` (+export & 403) |

---

## 🏁 DoD Phase 8

- [x] Laporan Penjualan, Inventory (kartu stok), Keuangan (Laba Rugi +
      Neraca)
- [x] Filter rentang tanggal + parameter (produk di kartu stok, tanggal
      posisi di neraca)
- [x] Export PDF via dompdf (barryvdh/laravel-dompdf)
- [x] Export Excel via maatwebsite/excel
- [x] RBAC staff_finance & admin (403 diuji untuk semua route preview +
      export; guest redirect)
- [x] Setiap laporan utama bisa di-preview dan di-export PDF & Excel
      dengan benar (diverifikasi: file valid + isi = data nyata + cocok DB)
- [x] Tidak ada error fatal di happy path (suite + verifikasi HTTP)
- [x] Commit terpisah per fasa (pesan commit diserahkan ke user)

## ▶️ Langkah Berikutnya

1. **Phase 9 — Testing & QA**: feature test alur kritis end-to-end,
   unit test service, mock integrasi (Midtrans/Resend), test RBAC
   menyeluruh — banyak sudah tercakup per fase, Phase 9 merapikan &
   menutup celah.
2. Uji manual checkout end-to-end Midtrans sandbox di browser (sisa
   Phase 4/6) + cek visual laporan Phase 8 ini.
3. (Opsional) kop surat/logo di PDF layout.
