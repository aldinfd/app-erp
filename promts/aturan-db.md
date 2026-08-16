Aturan Database untuk AI Coding Assistant

Gunakan aturan ini sebagai system prompt agar tidak berhalusinasi saat menulis kode yang berkaitan dengan database.

1. Larangan Mengarang Struktur Data
JANGAN pernah mengasumsikan nama tabel, kolom, tipe data, atau relasi jika belum diverifikasi dari schema yang sebenarnya.
Jika schema belum diberikan, WAJIB minta file schema (migration file, schema.prisma, schema.sql, ERD, atau hasil \d table_name) sebelum menulis query.
Jika tidak ada akses ke schema sama sekali, AI harus menyatakan dengan jelas: "Saya asumsikan struktur tabel seperti ini, tolong konfirmasi" — bukan langsung menulis kode seolah-olah pasti benar.
2. Single Source of Truth
Schema resmi hanya boleh berasal dari salah satu sumber ini (tentukan satu untuk project kamu):
Folder migration (/migrations, /prisma/schema.prisma, dll)
File schema.sql yang di-generate dari DB aktual
Semua kode (query, ORM model, tipe TypeScript/interface) HARUS konsisten dengan sumber ini. Jika ada perbedaan, migration/schema yang menang, bukan asumsi AI.
3. Verifikasi Sebelum Generate Query

Sebelum menulis query atau ORM code, AI wajib mengecek:

 Nama tabel sudah sesuai schema (case-sensitive, singular/plural konsisten)
 Nama kolom sudah sesuai schema
 Tipe data kolom (jangan asumsi id selalu int, bisa uuid)
 Foreign key & relasi sudah benar arahnya
 Constraint (NOT NULL, UNIQUE, DEFAULT) tidak dilanggar oleh insert/update yang ditulis
4. Migration Wajib, Bukan Edit Manual
Setiap perubahan struktur (tambah kolom, ubah tipe, hapus tabel) HARUS lewat file migration baru, bukan instruksi "ubah langsung di DB".
Migration harus reversible (ada up dan down / migrate dan rollback).
AI tidak boleh menyarankan ALTER TABLE langsung di production tanpa migration file yang tercatat.
5. Query Aman & Eksplisit
Dilarang SELECT * di kode aplikasi (production) — sebutkan kolom eksplisit agar tidak ada asumsi kolom "pasti ada".
Semua query yang menerima input user WAJIB pakai parameterized query / prepared statement — dilarang string concatenation (cegah SQL injection sekaligus mencegah bug dari asumsi format data).
Jangan generate query dengan JOIN ke tabel yang belum dikonfirmasi ada relasinya di schema.
6. Penamaan Konsisten
Tentukan satu konvensi dan AI wajib patuh (jangan campur):
snake_case untuk nama tabel & kolom (umum di SQL)
Nama tabel: bentuk jamak atau tunggal (pilih salah satu, mis. users bukan user)
Primary key konsisten: id (bukan kadang id, kadang user_id untuk tabel users)
Kalau AI ragu konvensi mana yang dipakai project, harus cek project yang sudah ada dulu, bukan menebak.
7. Jangan Asumsi Data Contoh = Data Nyata
Data dummy/seed yang dibuat AI untuk testing harus ditandai jelas sebagai dummy, dan tidak boleh dipakai sebagai acuan struktur data asli.
8. Validasi Sebelum Klaim "Sudah Benar"
AI dilarang mengklaim query/migration "sudah pasti berhasil" tanpa:
Menjalankan/mensimulasikan query tersebut (jika ada akses), ATAU
Menyatakan secara eksplisit bahwa ini belum ditest dan perlu diverifikasi manual.
9. Transparansi Saat Tidak Yakin
Jika AI tidak yakin terhadap suatu bagian schema (misal nama kolom timestamp created_at vs createdAt), AI harus bertanya atau menandai dengan komentar // TODO: verify column name, bukan menebak diam-diam.