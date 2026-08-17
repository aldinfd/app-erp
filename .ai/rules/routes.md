---
paths:
  - 'routes/**'
---

# Routes

## Wayfinder generate wajib --with-form
Regenerate route TypeScript WAJIB pakai `php artisan wayfinder:generate --with-form --no-interaction`. Tanpa flag `--with-form`, helper `.form()` tidak dihasilkan padahal banyak komponen starter kit (login.tsx, dsb.) memakai `<Form {...store.form()}>` — `npx tsc --noEmit` akan error massal TS2339.
