# 📋 Laporan Phase 0 — Setup Proyek & Tooling

> Status: ✅ **SELESAI** (dinyatakan selesai pada 2026-08-17)
> Pengerjaan: 16–17 Agustus 2026
> Rencana asli: [plan.md](../../.claude/rules/plan.md) — Phase 0

---

## 🎯 Apa itu Phase 0?

Phase 0 adalah tahap **persiapan semua perkakas** sebelum aplikasi dibangun.
Anggap seperti menyiapkan dapur sebelum masak: kompor menyala, bahan tersusun,
alat tersedia. Setelah Phase 0 selesai, kita bisa langsung "memasak" fitur.

---

## ✅ Yang Sudah Dikerjakan

### 1. Bikin Proyek Laravel + React Starter Kit
Proyek dibuat pakai **React Starter Kit** resmi Laravel. Sekali buat, langsung
dapat paket lengkap:

- **Laravel 13** — kerangka backend (server)
- **React 19 + TypeScript** — kerangka frontend (tampilan). TypeScript = JavaScript
  yang bisa "memeriksa tipe data", biar typo/ketidaksengajaan ketahuan lebih awal
- **Inertia 3** — jembatan React ↔ Laravel, jadi terasa seperti satu aplikasi
- **Tailwind CSS 4** — untuk mempercantik tampilan
- **shadcn/ui** — kumpulan komponen siap pakai (tombol, form, tabel, dll)
- **Fortify** — "mesin" login di balik layar (login, lupa password, dll)

### 2. Verifikasi Tailwind & TypeScript
Sudah termasuk dari pabrik Starter Kit — diverifikasi jalan, tidak perlu
install terpisah.

### 3. Sambungan Database MySQL
File `.env` (file pengaturan rahasia proyek) diisi koneksi:

| Item | Nilai |
|---|---|
| Database | MySQL 9.3.0 |
| Host | `127.0.0.1:3308` (komputer sendiri) |
| Nama database | `erp` |

### 4. Install Package Pendukung

| Package | Versi | Fungsi |
|---|---|---|
| spatie/laravel-permission | 8.3.0 | Hak akses per role (siapa boleh apa) |
| spatie/laravel-activitylog | 5.1.0 | Pencatat aktivitas (audit trail) |
| resend/resend-laravel | 1.4.0 | Kirim email |
| midtrans/midtrans-php | 2.6.2 | Pembayaran online |
| barryvdh/laravel-dompdf | 3.1.2 | Export PDF |
| maatwebsite/excel | 4.0.0 | Export Excel |

> ⚠️ **Cloudinary tidak jadi dipakai** — belum mendukung Laravel 13.
> Upload gambar produk sementara pakai **storage lokal** (folder di server
> sendiri). Kolom database-nya dibuat fleksibel, jadi nanti bisa pindah ke
> Cloudinary tanpa ubah struktur database.

### 5. Struktur Folder
Folder frontend mengikuti bawaan Starter Kit (`resources/js/pages`,
`components`, `layouts`, dll). Folder khusus backend (`app/Services`,
`app/Actions`) dibuat nanti saat modulnya dikerjakan — tidak perlu dibuat kosong duluan.

### 6. Git & Commit
Git sudah jalan, commit dilakukan bertahap oleh user sendiri.

### 7. Layout BackOffice + Storefront (step terakhir)
- **BackOffice** (halaman internal tim): layout sidebar + topbar sudah tersedia
  dari Starter Kit — dipakai apa adanya.
- **Storefront** (halaman publik customer): dibuat baru:
  - `resources/js/layouts/storefront-layout.tsx` — kerangka halaman publik
    (header nama toko + menu, footer)
  - `resources/js/pages/storefront/home.tsx` — halaman depan bertuliskan
    "Katalog produk segera hadir" (isi aslinya menyusul di Phase 4)
  - Halaman lama `welcome.tsx` (contoh bawaan kit) dihapus
  - Dites otomatis: `tests/Feature/StorefrontTest.php` ✅ lulus

---

## 🏁 Cek "Selesai Kalau" (DoD)

| Syarat | Status |
|---|---|
| `composer run dev` jalan (server + tampilan) | ✅ |
| Halaman login Starter Kit tampil | ✅ |
| Database MySQL terkoneksi | ✅ |

**Cara menjalankan aplikasi:**

```bash
composer run dev        # jalankan server + vite sekaligus
```

lalu buka **http://localhost:8000** di browser.

---

## ⚠️ Kendala yang Pernah Terjadi & Solusinya

1. **Halaman "Laravel + Vite"** tampil di browser.
   Penyebab: salah buka alamat — `http://localhost:5173` itu milik Vite
   (server penyedia file tampilan), bukan aplikasinya.
   Solusi: buka `http://localhost:8000`.
2. **Database `erp` tadinya terisi tabel aplikasi lain** (47 tabel aplikasi lama).
   Solusi: user mengosongkan sendiri, lalu migration kita dijalankan ulang bersih.
3. **Test awal error "cannot VACUUM"** — test memakai SQLite (database kecil
   untuk tes) dan perintah `migrate:fresh` tidak cocok padanya.
   Solusi: test memakai `migrate` biasa — akhirnya lulus semua.

---

## 📚 Kamus Istilah di Phase 0

| Istilah | Arti |
|---|---|
| **Backend / Frontend** | Sisi server (logika & data) / sisi tampilan yang dilihat user |
| **Starter Kit** | Paket awalan resmi — halaman login, layout, komponen udah ada |
| **Fortify** | Mesin auth (login/lupa password) tanpa tampilan sendiri |
| **Inertia** | Jembatan React & Laravel biar seperti satu aplikasi |
| **Vite** | Alat pengurus file tampilan (CSS/JS) + auto-reload saat diedit |
| **HMR / hot reload** | Perubahan kode langsung tampil di browser tanpa refresh manual |
| **.env** | File pengaturan rahasia (password database, API key) — tidak boleh di-commit |
| **Migration** | Kode untuk membuat/ubah tabel database, tercatat & bisa dibalik |
| **Layout** | Kerangka halaman (header, menu, footer) yang dipakai berulang |
| **DoD** | Definition of Done — daftar syarat sebuah fase dinyatakan selesai |
| **Pest** | Alat test otomatis bawaan proyek ini |
