Aplikasi ERP

1. Tujuan dan Masalah
- Tujuan: Untuk mempermudah proses bisnis
- Masalah: data masih terpisah-pisah dan laporan manual

2. Modul-modul dan fungsinya
- Inventory: Mengelola stok gudang
- Sales: Mengelola penjualan
- Finance: Mengelola keuangan

3. Alur proses bisnis
   Alur Penjualan:
   Sales Order → Cek & kurangi stok → Invoice terbit → Payment → Jurnal otomatis ke Finance
   
   Alur Pembelian:
   Stok menipis → Staff gudang buat PO ke vendor → Barang diterima & stok bertambah 
   → Invoice vendor dibayar → Jurnal otomatis ke Finance

4. Struktur data master
- Produk
- Kategori produk, satuan/unit (pcs, kg, dll)
- Chart of account
- Customer
- Vendor

5. Siapa penggunanya & peran (role)
- Admin — akses penuh
- Staff Gudang — kelola stok, PO ke vendor
- Staff Finance — kelola pembayaran, invoice, laporan keuangan

6. Konteks organisasi
- "ERP internal untuk toko online single-seller"
  Skenario: 1 toko/UMKM yang jualan online. Tim internal (admin, staff gudang, 
  staff finance) pakai ERP untuk kelola stok, order, dan keuangan. Ada storefront 
  kecil untuk customer beli — customer bukan "user ERP", hanya pihak yang membuat 
  order lewat storefront.

7. Integrasi & konektivitas
- Payment gateway: Midtrans Sandbox
- Kirim email: Resend
- Upload gambar produk: Cloudinary Free Tier
- Ongkos kirim (opsional): RajaOngkir Free
- Export laporan: Native Laravel (barryvdh/laravel-dompdf, maatwebsite/excel)

8. Fitur inti
- Notifikasi in-app/email saat stok menipis / order baru
- Search dan filter di tiap tabel data
- Export laporan ke PDF
- Autentikasi & role
- Transaksi (sales order, purchase order, stock movement)
- Dashboard (stok menipis, laporan penjualan bulanan, dll)
- Audit trail

9. Tech stack
- BE: PHP & Laravel
- FE: React
- Penghubung: Inertia
- DB: PostgreSQL (Neon/Supabase)
- ORM: Eloquent ORM

10. Deployment
- Aplikasi (Laravel + Inertia + React): Render
- Database: Neon (PostgreSQL)

11. ERD dan proses bisnis