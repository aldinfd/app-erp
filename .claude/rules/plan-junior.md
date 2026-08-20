# Plan ERP — Versi Junior 🚀

> Halo! Ini adalah panduan pembuatan aplikasi ERP versi **sederhana dan mudah dipahami**.
> Anggap saja ini seperti **resep masak** — ikuti urutannya, dan kamu akan dapet hasilnya.
> Sumber lengkap: [plan.md](plan.md) | Kebutuhan: [requirement.md](requirement.md)
> Update 2026-08-16: bagian login sekarang pakai **React Starter Kit + Fortify** (pengganti Breeze — Breeze sudah tidak direkomendasikan di Laravel terbaru).
> Update 2026-08-16: desain tabel database udah jadi & sebagian disetujui — detail teknisnya ada di [schema-database.md](schema-database.md).

---
x
## 📖 Cara Baca Plan Ini

- Kerjakan dari **atas ke bawah**, jangan loncat-loncat.
- Tiap bagian punya 4 hal:
  - 🎯 **Tujuan** — apa yang mau dicapai
  - ✅ **Langkah** — hal konkret yang harus kamu lakukan
  - 🏁 **Selesai kalau** — tanda pekerjaan ini udah beres
  - 💡 **Tips** — catatan penting dari senior
- Kalau bingung istilah, cek **Kamus Istilah** di bawah dulu.

---

## 📚 Kamus Istilah Singkat (baca dulu sekilas)

| Istilah | Artinya gampangnya |
|---|---|
| **Migration** | Cara Laravel bikin tabel database pakai kode (bukan klik-klik manual) |
| **Model** | Perwakilan 1 tabel di database, misal `Product` = tabel produk |
| **Controller** | Tempat logika: nerima request, proses data, balikin hasil |
| **Inertia** | Jembatan biar React (frontend) & Laravel (backend) seolah jadi 1 app |
| **ERD** | Gambar/desain tabel database & hubungannya |
| **CRUD** | Create, Read, Update, Delete — operasi dasar data |
| **RBAC** | Sistem hak akses (siapa boleh lihat/edit apa) |
| **Double-entry** | Aturan akuntansi: tiap transaksi harus ada 2 sisi — Debit & Kredit, jumlahnya sama |
| **Jurnal otomatis** | System catat keuangan sendiri saat ada transaksi (tanpa input manual) |
| **Sandbox** | Mode "latihan" dari payment gateway, uangnya palsu, aman buat tes |
| **Webhook** | Notifikasi otomatis dari sistem lain (misal Midtrans bilang "udah dibayar") |
| **Starter Kit** | Paket awalan resmi Laravel — halaman login, layout & komponen UI udah ada dari pabrik. Kita pakai versi React |
| **Fortify** | "Mesin" di balik layar yang ngurusin login, lupa password, dll (dia kerja tanpa tampilan sendiri) |

---

## ⚖️ Aturan Main (penting, baca baik-baik)

1. **Satu hal dalam satu waktu.** Jangan mulai modul Sales sebelum Inventory beres.
2. **Selalu commit per langkah.** Pesan commit jelas, misal `feat: tambah tabel produk`.
3. **Tes dulu sebelum lanjut.** Kalau langkah ini belum sesuai 🏁, jangan maju.
4. **Stok & uang jangan diubah sembarangan.** Pakai "service" khusus (dijelaskan nanti).
5. **Kalau bingung, tanya/baca** — jangan nebak terus.

---

## 🏗️ Gambaran Besar

ERP ini buat **1 toko online**. Ada 2 "sisi":

```
┌─────────────────────────┐        ┌──────────────────────────┐
│   STOREFRONT (publik)   │        │   BACK-OFFICE (internal) │
│  Customer lihat & beli  │  ───►  │  Tim internal kelola     │
│  - katalog produk       │        │  - Admin (akses penuh)   │
│  - keranjang + bayar    │        │  - Staff Gudang (stok)   │
└─────────────────────────┘        │  - Staff Finance (uang)  │
                                   └──────────────────────────┘
```

**3 modul inti:** Inventory (stok) → Sales (jualan) → Finance (keuangan).
Semua modul **terhubung**: jualan → stok berkurang → uang masuk tercatat otomatis.

---

## FASE 0 — Siapkan Perkakas 🧰

🎯 **Tujuan:** Proyek bisa jalan di laptopmu.

✅ **Langkah:**
1. Bikin proyek Laravel baru pakai **React Starter Kit** — sekali bikin langsung dapat: React + Inertia, halaman login, TailwindCSS, dan Fortify (mesin login-nya)
2. Sambungkan ke database MySQL
3. Jalankan: `php artisan serve` (backend) + `npm run dev` (frontend)
4. Commit pertama

🏁 **Selesai kalau:** Buka browser, halaman login muncul, dan bisa konek ke DB MySQL.

💡 **Tips:** Simpan semua "kunci rahasia" (API key, password DB) di file `.env`, **jangan** di-commit ke git!

---

## FASE 1 — Fondasi: Login, Hak Akses, Pencatat 🔐

🎯 **Tujuan:** Aman dulu sebelum ada data. Siapa boleh apa udah jelas.

✅ **Langkah:**
1. Cek halaman login bawaan Starter Kit (login, logout, lupa password) — sudah jadi
2. Matikan halaman "daftar akun" (register) — user ERP dibuat oleh admin, bukan daftar sendiri (atur di `config/fortify.php`)
3. Bikin 3 role: **admin, staff_gudang, staff_finance**
4. Batasi menu sesuai role (misal: staff gudang tidak bisa lihat menu keuangan)
5. Pasang pencatat aktivitas (audit trail) — tiap ada yang nambah/edit/hapus data, tercatat siapa & kapan
6. Siapin sistem notifikasi (muncul di app + kirim email)
7. Bikin 1 user contoh per role buat tes

🏁 **Selesai kalau:** Login jalan, menu beda-beda per role, aksi CRUD kecatat di log.

💡 **Tips:** Customer yang beli di storefront **bukan** user ERP. Mereka butuh login pun tidak. Jangan campur.

---

## FASE 2 — Data Induk (Master Data) 📇

🎯 **Tujuan:** Bikin "bahan baku" data sebelum bikin transaksi.

✅ **Langkah (buat tabel + halaman CRUD untuk masing-masing):**
1. **Kategori produk** (contoh: Pakaian, Elektronik)
2. **Satuan** (pcs, kg, lusin)
3. **Produk** (nama, harga, stok awal, dll) + upload gambar (disimpan di server sendiri dulu — Cloudinary menyusul kalau sudah dukung Laravel 13)
4. **Customer** (data pelanggan)
5. **Vendor** (data pemasok)
6. **Chart of Account / CoA** (daftar akun keuangan: Kas, Pendapatan, dll)

Tiap halaman harus ada: **tabel + search + filter + tombol tambah/edit/hapus**.

🏁 **Selesai kalau:** Bisa tambah produk beserta gambarnya, dan data tersimpan ke DB.

💡 **Tips:** Bikin "seeder" (data contoh) biar gak bolak-balik input manual pas tes.

---

## FASE 3 — Modul Inventory (Stok) 📦

🎯 **Tujuan:** Stok selalu akurat & ada jejaknya.

✅ **Langkah:**
1. Bikin **StockService** — SATU pintu untuk ubah stok (tambah/kurang/koreksi)
2. Setiap ubah stok → catat di tabel **stock_movements** (siapa, kapan, berapa, kenapa). Trik: angkanya pakai tanda — masuk = **plus**, keluar = **minus**, koreksi = plus/minus. Jadi stok sekarang = tinggal jumlah semua catatan
3. Halaman **riwayat stok** (bisa filter tanggal/produk)
4. Halaman **stock opname** (koreksi stok + alasan)
5. Alarm **stok menipis**: kalau stok ≤ batas minimum, kirim notifikasi

🏁 **Selesai kalau:** Stok berubah ada catatannya, dan alarm stok menipis bunyi pas tepat.

💡 **Tips:** ⚠️ **JANGAN** update angka stok langsung di tabel produk. SELALU lewat StockService. Ini penting biar gak ada stok "hilang" tanpa jejak.

---

## FASE 4 — Modul Sales + Storefront 🛒

🎯 **Tujuan:** Customer bisa beli, bayar, lalu stok & keuangan update otomatis.

✅ **Langkah:**

**A. Storefront (customer):**
1. Halaman katalog produk (gambar + harga)
2. Keranjang belanja
3. Halaman checkout (isi data, lihat ongkir)
4. Bayar lewat Midtrans (mode **Sandbox** = uang palsu)

**B. Back-office (tim):**
5. Terima notifikasi dari Midtrans kalau udah dibayar
6. Saat **sudah dibayar**, otomatis:
   - Buat Invoice
   - **Kurangi stok** (lewat StockService!)
   - **Catat keuangan** (jurnal otomatis — detail di Fase 6)
7. Halaman kelola Sales Order (lihat, ubah status, batalkan)

🏁 **Selesai kalau:** Customer checkout → bayar (sandbox) → stok berkurang → invoice muncul → keuangan kecatat. Semua otomatis.

💡 **Tips:** Notifikasi pembayaran Midtrans bisa datang **2 kali**. Cek dulu apakah transaksi udah diproses, jangan proses dobel!

---

## FASE 5 — Modul Purchase (Beli ke Vendor) 🚚

🎯 **Tujuan:** Beli barang dari supplier, stok nambah, bayar, keuangan kecatat.

✅ **Langkah:**
1. Bikin **Purchase Order (PO)** — staff gudang pesan barang ke vendor
2. Status PO: *draft → dipesan → diterima → dibayar*
3. Saat **barang diterima**: tambah stok (lewat StockService!)
4. Catat **invoice vendor** (`vendor_invoices`), lalu bayar (`vendor_payments` — beda tabel dengan pembayaran customer)
5. Saat dibayar → catat keuangan (jurnal otomatis)

🏁 **Selesai kalau:** PO dibuat → barang diterima stok nambah → dibayar keuangan kecatat.

💡 **Tips:** Bisa tambah tombol "pesan lagi" langsung dari daftar produk stok menipis. Mempermudah staff gudang.

---

## FASE 6 — Modul Finance (Keuangan & Jurnal) 💰

🎯 **Tujuan:** Semua transaksi (jualan & beli) muncul di laporan keuangan.

✅ **Langkah:**
1. Bikin **JournalService** — SATU pintu buat catat jurnal
2. Jurnal harus **balance**: total Debit = total Kredit (kayak timbangan)
3. Panggil JournalService dari Fase 4 (jualan) & Fase 5 (beli)
4. Halaman **Jurnal Umum** (lihat semua catatan keuangan)
5. Halaman **bisa input jurnal manual** (bukan koreksi kecil)
6. Bikin **Laporan**: Laba Rugi & Neraca

🏁 **Selesai kalau:** Tiap jualan/beli otomatis bikin jurnal yang balance, laporan keuangan angkanya cocok.

💡 **Tips:** Jangan hard-code ID akun (misal "akun kas = no 5"). Bikin tabel mapping (**journal_mappings**) biar gampang diganti. Dan selalu bungkus proses stok+jurnal pakai **DB transaction** — biar kalau ada error di tengah, semua batal (gak setengah-setengah/corrupt).

---

## FASE 7 — Dashboard 📊

🎯 **Tujuan:** Layar ringkasan biar gampang pantau toko.

✅ **Langkah:**
1. Bikin dashboard (bisa beda per role)
2. Tampilkan kartu info:
   - Produk stok menipis
   - Total penjualan bulan ini
   - Order/pembayaran tertunda
   - PO nunggu barang/bayar
3. (Opsional) grafik penjualan per bulan

🏁 **Selesai kalau:** Buka dashboard, langsung lihat kondisi toko hari ini.

---

## FASE 8 — Laporan & Export 📄

🎯 **Tujuan:** Laporan bisa diunduh jadi PDF/Excel.

✅ **Langkah:**
1. Bikin laporan: Penjualan, Stok, Keuangan
2. Tambah filter tanggal
3. Tombol **Export PDF** (pakai dompdf)
4. Tombol **Export Excel** (pakai maatwebsite/excel)

🏁 **Selesai kalau:** Laporan bisa diunduh PDF & Excel dengan data benar.

---

## FASE 9 — Testing 🧪

🎯 **Tujuan:** Pastikan semua alur jalan tanpa bug.

✅ **Langkah:**
1. Tes alur jualan: checkout → bayar → stok berkurang → jurnal
2. Tes alur beli: PO → terima → stok nambah → bayar → jurnal
3. Cek jurnal selalu balance
4. Tes hak akses (role salah harus ditolak)
5. Coba bayar pakai Midtrans Sandbox

🏁 **Selesai kalau:** Semua alur kritis lewat tanpa error.

---

## FASE 10 — Deploy ke Online 🌐

🎯 **Tujuan:** App bisa diakses dari internet.

✅ **Langkah:**
1. Upload ke **Render** (app) + **MySQL** (database)
2. Set semua konfigurasi rahasia di server
3. Jalankan migration di DB produksi
4. Bangun aset frontend: `npm run build`
5. Set URL webhook Midtrans ke alamat produksi
6. Tes sekali lagi di versi online

🏁 **Selesai kalau:** Buka alamat web, login, bikin order sandbox, semua jalan.

💡 **Tips:** Untuk pembayaran, tetap pakai mode **Sandbox** dulu sampai yakin 100%. Baru switch ke produksi.

---

## 🎯 Rangkuman Alur (ingat ini!)

**Jualan:**
`Customer checkout → bayar Midtrans → stok berkurang → invoice terbit → jurnal otomatis`

**Beli barang:**
`Stok menipis → bikin PO ke vendor → barang datang (stok nambah) → bayar vendor → jurnal otomatis`

> Inti dari seluruh app: **tiap transaksi menyentuh 3 hal — stok, invoice, jurnal.** Jaga konsistensi 3 itu, dan app-mu sudah benar.

---

Semangat! Kerjakan pelan-pelan, satu fase satu waktu. Kalau ada yang bingung, balik ke [plan.md](plan.md) untuk versi detail, atau tanya lagi ya. 💪
