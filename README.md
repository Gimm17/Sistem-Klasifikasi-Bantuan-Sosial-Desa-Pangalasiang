# SIKLAS-NB — Sistem Klasifikasi Kelayakan Penerima Bantuan Sosial

Implementasi algoritme **Naive Bayes** untuk mengklasifikasikan kelayakan warga
menerima bantuan sosial di **Desa Pangalasiang, Kecamatan Sojol, Kabupaten Donggala**.
Proyek skripsi Teknik Informatika.

> Dokumen acuan utama (source of truth) ada di folder [`docs/`](./docs):
> `CONTEXT.md`, `architecture.md`, `tech_stack.md`, `list_feature.md`, `implementation_plan.md`, `system_prompt.md`.

---

## Struktur (monorepo)

```
.
├── docs/          # dokumentasi & rencana proyek (acuan utama)
├── backend/       # Laravel 13 (PHP 8.4) — REST API + engine Naive Bayes
└── frontend/      # Vue 3 (Vite) — SPA
```

## Prasyarat (terpenuhi di mesin ini)

- PHP 8.4, Composer 2.9
- Node 22, NPM 10
- MySQL (XAMPP) berjalan di `127.0.0.1:3306`

## Setup awal

### 1. Database
Database `siklas_nb` sudah dibuat (MySQL, `utf8mb4`).

### 2. Backend (`backend/`)
```bash
cd backend
composer install                 # sudah dijalankan saat scaffold
cp .env.example .env             # .env sudah dikonfigurasi (MySQL siklas_nb, Sanctum, CORS)
php artisan key:generate         # sudah
php artisan migrate              # buat semua tabel
php artisan db:seed              # (setelah seeder Fase 1 dibuat)
php artisan serve --port=8000
```

### 3. Frontend (`frontend/`)
```bash
cd frontend
npm install                      # sudah dijalankan
npm run dev                      # http://localhost:5173
```

## Menjalankan (sehari-hari)

| Layanan | Perintah | URL |
|---|---|---|
| Backend API | `cd backend && php artisan serve --port=8000` | http://localhost:8000 |
| Frontend SPA | `cd frontend && npm run dev` | http://localhost:5173 |

Auth memakai **Sanctum (cookie-based)**. Frontend `:5173` sudah didaftarkan di
`SANCTUM_STATEFUL_DOMAINS` dan `config/cors.php` (`supports_credentials=true`).

## Status pengerjaan

Lihat [docs/implementation_plan.md](./docs/implementation_plan.md) untuk detail tiap fase.

| Fase | Status |
|---|---|
| 0 — Setup project | ✅ selesai |
| 1 — Skema database | ✅ selesai |
| 2 — Auth & user management | ✅ selesai |
| 3 — Master data warga + atribut | ✅ selesai |
| 4 — Engine Naive Bayes (INTI) | ✅ selesai |
| 5 — Approval | ✅ selesai |
| 6 — Evaluasi model | ✅ selesai |
| 7 — Dashboard & frontend polish | ✅ selesai |
| 8 — Blackbox testing | ✅ selesai (lihat [BLACKBOX_TEST_RESULTS.md](./BLACKBOX_TEST_RESULTS.md)) |
| 9 — Dokumentasi skripsi | ✅ selesai (lihat [docs/SKRIPSI_LAMPIRAN.md](./docs/SKRIPSI_LAMPIRAN.md)) |
| **+** Fitur tambahan (Import/Export CSV & **XLSX** + bulk upload multi-file untuk **warga & data training**, penegakan batasan periode #3, filter export, laporan PDF, audit log) | ✅ selesai |
| **+** Galeri foto rumah + **label kondisi_rumah saat validasi manual** + lightbox preview (revisi stakeholder) | ✅ selesai |
| **+** Soft-delete warga + **NIK reusable** + sembunyikan hasil yatim (dashboard/klasifikasi/approval) | ✅ selesai |
| **+** PDF Rincian KK opsional **sertakan foto rumah** (`?include_foto=1`) | ✅ selesai |
| **+** **Deploy produksi** `https://siklas.pinnhost.my.id` (cPanel, PHP 8.4, LiteSpeed, MySQL) | ✅ selesai |
| **+** **Export warga untuk role approver** (CSV/XLSX) + fix `array_flip()` fatal saat ada warga soft-delete dengan NIK NULL | ✅ selesai |

## Pengujian otomatis

Feature test PHPUnit **37/37 lulus** (123 assertion):
- `tests/Unit/NaiveBayesServiceTest.php` — verifikasi matematis mesin NB (7 test).
- `tests/Feature/WargaFotoTest.php` — galeri foto + validasi (8 test).
- `tests/Feature/WargaSoftDeleteTest.php` — NIK reuse + hasil yatim (3 test).
- `tests/Feature/ImportWargaTest.php` — CSV/XLSX + bulk (9 test, bertambah 4 regresi import + validasi NIK).
- `tests/Feature/KlasifikasiPeriodeTest.php` — batasan #3 (data N bulan terakhir).
- `tests/Feature/WargaExportRoleTest.php` — role:admin,approver untuk export (3 test).
- `tests/Feature/WargaImportProcessorNullNikTest.php` — konstruktor tidak gagal saat ada soft-delete nik NULL (1 test).

```bash
cd backend && php artisan test      # 37/37
```

## Deploy ke cPanel

Lihat [`PANDUAN_AKSES_CPANEL_SSH.md`](./PANDUAN_AKSES_CPANEL_SSH.md) (SSH via paramiko, tarball + SFTP).
Produksi: `https://siklas.pinnhost.my.id`. Gotcha kritis: root `.htaccess` perlu
`RewriteRule ^storage/(.*)$ public/storage/$1 [L]` agar URL `/storage/...` (foto rumah)
terlayani (docroot = project root, bukan `public/`).
