---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Build frontend sebelum test halaman Inertia baru
Setelah menambah halaman Inertia baru, test feature akan 500 dengan "Unable to locate file in Vite manifest" sampai `npm run build` dijalankan ulang. Shell default memakai nvm Node 18 — build butuh Node 25 (vite pakai `node:util styleText`): `export NVM_DIR="$HOME/.nvm" && . "$NVM_DIR/nvm.sh" && nvm use 25` dulu, lalu `npm run build`, baru jalankan test.
