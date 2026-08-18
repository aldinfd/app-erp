---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Route model binding: nama variabel harus cocok dengan param route
Nama variabel parameter controller HARUS camelCase dari nama param route (mis. route `chart-of-accounts/{chart_of_account}` → `ChartOfAccount $chartOfAccount`). Jika beda, implicit binding tidak jalan: Laravel inject model baru kosong (id NULL) lewat container — unique-ignore kosong, update/destroy diam-diam tidak mengubah baris mana pun. Selalu uji update+destroy per resource, bukan hanya index/store.
