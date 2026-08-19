---
paths:
  - '**/*.php'
---

# General

## Semua class yang direferensikan wajib di-import (cek blok use)
PHP tidak error untuk `Foo::class` yang tidak di-import — ia hanya menghasilkan string nama class salah (mis. `Tests\Feature\...LowStockDetected`) sehingga Event::fake()/assertions salah target diam-diam. Sudah 2x terjadi (Inertia di controller, LowStockDetected di test). Sebelum menjalankan test, cek semua referensi class baru ada di blok `use`.

## Helper tanggal mengembalikan CarbonImmutable — reassign hasil mutator
today()/now() (Carbon 3 + Laravel 13) mengembalikan CarbonImmutable — addMonth()/startOfDay() dsb. TIDAK memutasi, hasilnya harus di-reassign: $cursor = $cursor->addMonth(). Tanpa reassign, loop tanggal diam-diam memakai tanggal yang sama terus (terjadi di salesChart DashboardController). Immutable juga menyebabkan ->copy() jadi no-op.
