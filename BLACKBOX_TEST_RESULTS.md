# BLACKBOX_TEST_RESULTS.md — SIKLAS-NB

Lampiran pengujian blackbox (input/output sesuai spesifikasi, tanpa melihat kode
internal) per metodologi proposal. Diperbarui bertahap tiap fase.

> Format kolom mengikuti `docs/implementation_plan.md` §Fase 8.

---

## Fase 2 — Autentikasi & User Management

Endpoint: `POST /api/login`, `GET /api/me`, `POST /api/logout`, `GET|POST|PUT|DELETE /api/users`.

Diuji via simulasi klien HTTP (alur SPA Sanctum: `GET /sanctum/csrf-cookie` → `POST /api/login` → `GET /api/me`).

| No | Fitur | Skenario | Input | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| B2.1 | CSRF cookie | Ambil token awal | `GET /sanctum/csrf-cookie` | 204 + cookie `XSRF-TOKEN` & session ter-set | 204, kedua cookie ter-set | ✅ |
| B2.2 | Login | Kredensial benar | admin@siklas.test / password | 200 + data user | 200, `{id,name,email,role}` | ✅ |
| B2.3 | Sesi persisten | Akses endpoint terlindungi setelah login | `GET /api/me` (pakai session cookie) | 200 + data user | 200 | ✅ |
| B2.4 | Otorisasi role | Admin mengakses CRUD user | `GET /api/users` sbg admin | 403 Forbidden | 403 | ✅ |
| B2.5 | Login | Password salah | admin@siklas.test / salah | 422 + "Email atau password salah." | 422, pesan sesuai | ✅ |
| B2.6 | Otorisasi role | Superadmin mengakses CRUD user | `GET /api/users` sbg superadmin | 200 + daftar user | 200, list user | ✅ |
| B2.7 | Otentikasi | Akses tanpa login | `GET /api/me` (tanpa cookie) | 401 Unauthenticated | 401 | ✅ |
| B2.8 | CSRF | Token XSRF basi | POST login dengan token lama | 419 CSRF token mismatch | 419 (proteksi aktif) | ✅ |

### Catatan teknis penting (untuk skripsi)
- Laravel 13 memerlukan middleware `\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class` **ditambahkan eksplisit** ke grup middleware API (`bootstrap/app.php`, `$middleware->api(prepend: [...])`). Tanpa ini, `Auth::guard('web')->attempt()` / `session()->regenerate()` gagal dengan "Session store not set on request" karena stack session/CSRF tidak berjalan pada route API.
- Cookie sesi & XSRF-TOKEN di-set dengan `domain=localhost`; klien (axios) harus memanggil `http://localhost:8000` agar cookie ter-match. Frontend `withCredentials:true` + `withXSRFToken:true` (axios) otomatis melampirkan header `X-XSRF-TOKEN` segar per request.

---

## Fase 3 — Master Data Warga + Atribut/Kategori

Endpoint: `/api/warga` (CRUD + `PATCH /validasi`), `/api/atribut-klasifikasi`, `/api/kategori-atribut` (CRUD).

| No | Fitur | Skenario | Input | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| B3.1 | Atribut | List 4 atribut + kategori | `GET /api/atribut-klasifikasi` (admin) | 200, 4 atribut termuat dgn kategori | 200, 4 atribut | ✅ |
| B3.2 | Warga | Data valid | semua field benar | 201 Created | 201 | ✅ |
| B3.3 | Warga | NIK duplikat | NIK sudah ada | 422 "NIK sudah terdaftar" | 422 | ✅ |
| B3.4 | Warga | Penghasilan negatif | penghasilan=-500000 | 422 "Penghasilan minimal 0" | 422 | ✅ |
| B3.5 | Warga | Pekerjaan di luar kategori | status_pekerjaan="Astronot" | 422 | 422 | ✅ |
| B3.6 | Warga | List + filter | `?status_validasi=draft` | 200 + paginate | 200 | ✅ |
| B3.7 | Warga | Detail + history | `GET /warga/{id}` | 200 + `hasil_klasifikasi` (array) | 200 | ✅ |
| B3.8 | Warga | Validasi | `PATCH /warga/{id}/validasi` | 200, status→divalidasi | 200 | ✅ |
| B3.9 | Warga | Update | PUT field berubah | 200 | 200 | ✅ |
| B3.10 | Kategori | Bin numerik overlap | batas 1.5jt–2.5jt (penghasilan) | 422 "tumpang tindih" | 422 | ✅ |
| B3.11 | Kategori | Tambah kategori kategorikal | label "Guru" (pekerjaan) | 201 | 201 | ✅ |
| B3.12 | Warga | Role: approver create | approver POST /warga | 403 | 403 | ✅ |
| B3.13 | Warga | Role: approver list | approver GET /warga | 200 | 200 | ✅ |
| B3.14 | Warga | Delete (soft) | DELETE /warga/{id} | 204, row ter-soft-delete | 204 (deleted_at terisi) | ✅ |

### Catatan desain (untuk skripsi)
- `status_pekerjaan` & `kondisi_rumah` divalidasi terhadap **label kategori aktif** (tabel `kategori_atribut`) — bukan teks bebas. Dengan demikian nilai kategorikal langsung siap dipakai mesin Naive Bayes tanpa binning ulang.
- Bin numerik (penghasilan, tanggungan) memakai interval setengah-terbuka dengan trik `.99` pada batas atas (mis. `999999.99`) agar nilai bulat 1jt/2jt/3jt tidak overlap antar bin. Validasi overlap dilakukan di `StoreKategoriAtributRequest` (interval [a,b] ∩ [c,d] ↔ a≤d ∧ c≤b). Cek "lubang"/gap didelegasikan ke `AttributeCategorizer` (Fase 4) yang melempar `RuntimeException` jelas bila nilai tak masuk bin mana pun.
- Otorisasi per peran memakai middleware `role:...` (bukan Policy/Gate), karena model aksesnya murni berbasis peran tanpa kepemilikan sumber daya — pilihan implementasi yang setara & lebih ringkas.

---

## Fase 4 — Engine Naive Bayes (INTI RISET)

Endpoint: `/api/model/train`, `/api/model/versions`, `/api/klasifikasi/{warga}` (predict), `/api/klasifikasi/batch`, `/api/klasifikasi` (list), `/api/klasifikasi/{id}` (detail), `/api/data-training` (CRUD).

### 4a. Unit test matematis (`tests/Unit/NaiveBayesServiceTest.php`) — 7 test, 18 assertion, semua LULUS

Dataset 4 baris (2 layak, 2 tidak) yang angkanya diverifikasi manual (kalkulator/ pecahan eksak):

| Kasus | Yang dihitung manual | Hasil service | Status |
|---|---|---|---|
| Prior | P(layak)=2/4=0.5 | 0.5 | ✅ |
| Likelihood Laplace | P(>3jt\|layak)=(2+1)/(2+2)=0.75; P(<1jt\|layak)=(0+1)/4=0.25 | 0.75 / 0.25 | ✅ |
| Prediksi "jelas layak" | skor(layak)=½·(¾)⁴=81/512 → P(layak)=81/82≈0.98780488 | 81/82 | ✅ |
| Prediksi "jelas tidak layak" | simetri → P(tidak)=81/82 | 81/82 | ✅ |
| Kategori tak terlihat (fallback) | fallback=(0+1)/(2+2)=0.25 → P(layak)=27/28≈0.96428571 | 27/28 | ✅ |
| Normalisasi | P(layak)+P(tidak)=1 | 1.0 | ✅ |

### 4b. Uji HTTP end-to-end (data training demo 16 baris)

| No | Fitur | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| B4.1 | Training | POST /model/train (ada data) | 201 + model_version aktif | 201, mv=`v20260704-184916` | ✅ |
| B4.2 | Model | GET /model/versions | 200, ada 1 versi aktif | 200 | ✅ |
| B4.3 | Prediksi | Warga status draft | 422 "belum divalidasi" | 422 | ✅ |
| B4.4 | Prediksi | Profil layak (4jt/PNS/1/Layak) | 200, prediksi=layak, Σ prob=1 | 200, P(layak)=0.9989, **sum=1** | ✅ |
| B4.5 | Prediksi | Profil tidak layak (500rb/Tidak Bekerja/6/Tidak Layak) | 200, prediksi=tidak_layak | 200 | ✅ |
| B4.6 | Detail | GET /klasifikasi/{id} | 200 + breakdown lengkap | 200 (prior, likelihood, skor tersaji) | ✅ |
| B4.7 | Batch | POST /klasifikasi/batch | 200, skip yg sudah | 200 (dilewati=2) | ✅ |
| B4.8 | List | GET /klasifikasi | 200 | 200 | ✅ |

### Bukti explainable (contoh profil layak, model 16-baris)
Kategori input: penghasilan `>3.000.000`, pekerjaan `PNS/Pegawai`, tanggungan `0–1`, kondisi `Layak Huni`.

Likelihood kelas **layak** (dihitung & cocok manual):
- P(>3.000.000 \| layak) = (4+1)/(8+4) = 5/12 = **0.41667** ✓
- P(PNS/Pegawai \| layak) = (4+1)/(8+5) = 5/13 = **0.38462** ✓
- P(0–1 \| layak) = 5/12 = **0.41667** ✓
- P(Layak Huni \| layak) = (6+1)/(8+3) = 7/11 = **0.63636** ✓
- skor(layak) = 0.5 × 0.41667 × 0.38462 × 0.41667 × 0.63636 = **0.0212461** ✓ → P(layak) = 0.99886

### Catatan desain (untuk skripsi)
- `jumlahKategoriUnik` dihitung dari nilai unik yang **muncul di data training** (observed), sesuai docs §4.3 — bukan seluruh bin yang didefinisikan admin. Dokumentasikan pilihan ini; merupakan titik tanya dosen penguji yang lazim.
- Likelihood bagi kategori tak terlihat di training memakai **fallback Laplace** = (0+1)/(count_class + unique_observed), didukung statistik training di tabel `model_versions`.
- Hasil klasifikasi = **rekomendasi** (status `pending`) → wajib lewat approval (Fase 5) sebelum jadi keputusan resmi.
- Ditambahkan modul CRUD **data_training** (MVP #4, tak tercantum eksplisit di tabel endpoint docs) karena dibutuhkan untuk mengisi data latih; seeder contoh (`DataTrainingSeeder`) opsional, tidak dijalankan `DatabaseSeeder` otomatis.

---

## Fase 5 — Approval / Keputusan Akhir

Endpoint: `/api/approval/queue`, `/api/approval/{id}/approve|reject|override` — semua role **approver**.

| No | Fitur | Skenario | Input | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| B5.1 | Queue | approver lihat antrean | GET /approval/queue | 200 + daftar pending | 200 (3 pending) | ✅ |
| B5.2 | Reject | tanpa catatan | reject tanpa catatan | 422 "catatan wajib" | 422 | ✅ |
| B5.3 | Approve | setujui item pending | approve (+catatan) | 200, status→approved | 200 | ✅ |
| B5.4 | Reject | tolak dgn catatan | reject + catatan | 200, status→rejected | 200 | ✅ |
| B5.5 | Override | ubah kelas manual | override + catatan + kelas | 200, status→overridden, **kelas asli tersimpan di breakdown** | 200 (prediksi_asli tercatat) | ✅ |
| B5.6 | Anti ganda | proses item sudah diproses | approve item approved | 422 "sudah diproses" | 422 | ✅ |
| B5.7 | Separation of duty | admin coba approve | admin PATCH /approve | 403 | 403 | ✅ |

### Catatan desain (untuk skripsi)
- Override menyimpan `prediksi_asli` + metadata pemberi override di `breakdown_json` agar jejak keputusan manusia (yg menimpa mesin) dapat diaudit — selaras prinsip "hasil = rekomendasi, keputusan akhir manusia" (Batasan Masalah #5).
- Validasi `catatan_approval` wajib hanya untuk reject & override (approve tidak wajib).
- **Pelajaran teknis (terdokumentasi):** implicit route model binding Laravel mensyaratkan nama variabel controller **sama** dgn nama param route (`{hasilKlasifikasi}` ↔ `$hasilKlasifikasi`). Ketidakcocokan menyebabkan model kosong ter-inject → bug senyap (status terbaca null). Telah diperbaiki.

---

## Fase 6 — Evaluasi Model (Akurasi)

Endpoint: `POST /api/evaluasi/run` (admin/superadmin), `GET /api/evaluasi` (semua role).

| No | Fitur | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| B6.1 | Evaluasi | run rasio 0.2 | 201 + metrik 0..1 | acc=0.75, P=1, R=0.667, F1=0.8 | ✅ |
| B6.2 | Metrik | semua antara 0..1 | 0 ≤ x ≤ 1 | terpenuhi | ✅ |
| B6.3 | Reproducibility | run 2× data sama | hasil identik | acc #1 = acc #2 = 0.7500 | ✅ |
| B6.4 | Split | rasio berbeda (0.4) | test_size & metrik berubah | test_size=7, acc=0.857 | ✅ |
| B6.5 | Riwayat | GET /evaluasi | 200 + list | 200 | ✅ |
| B6.6 | Role | approver coba run | 403 | 403 | ✅ |
| B6.7 | Role | approver lihat riwayat | 200 | 200 | ✅ |

Contoh confusion matrix (rasio 0.2, data demo 16 baris): `{TP:2, TN:1, FP:0, FN:1}` → accuracy = (2+1)/4 = 0.75.

### Catatan desain (untuk skripsi)
- Metodologi **hold-out jujur**: model dilatih ulang pada train-split (12 baris) lalu diuji pada test-split (4 baris) — bukan diuji pada data training sendiri (resubstitution).
- Split **pseudo-random deterministik** (urut `md5(id)`) agar kelas tercampur di test set DAN reproducible antar run. (Order by id murni menghasilkan test set homogen bila data di-seed per kelas — dihindari.)
- Kelas positif = `layak`. `precision = TP/(TP+FP)`, `recall = TP/(TP+FN)`, `F1 = 2PR/(P+R)`, semua di-guard terhadap pembagi nol.
- Refactor engine: `ProbabilityTableBuilder::computeTable()` (pure) + `NaiveBayesService::predictWithTable()` (pure) menjadi **satu sumber kebenaran** rumus, dipakai bersama training (DB) & evaluasi (in-memory). Unit test engine tetap 7/7 lulus setelah refactor.

---

## Fitur Tambahan (Nice-to-have) — di luar batasan inti proposal

> Fitur berikut adalah **tambahan operasional** (docs/list_feature.md "Boleh menyusul"), TIDAK mengubah atribut/metode klasifikasi inti. Dokumentasikan di skripsi sebagai pengayaan, bukan klaim inti riset.

### A. Import/Export Warga — CSV **dan XLSX** + Bulk Upload Multi-file

> Diperluas dari versi CSV awal. Kini mendukung format **XLSX** (template rapi + dropdown),
> serta **bulk upload beberapa file sekaligus** (boleh campur `.csv` & `.xlsx`) agar input
> data lapangan lebih cepat. Kolom `dusun` ikut disertakan (sebelumnya absen di CSV).

**Endpoint (semua butuh `role:admin`):**
| Method | Path | Fungsi |
|---|---|---|
| GET | `/api/warga/import/template?format=xlsx\|csv` | Unduh template (default **xlsx** rapi) |
| GET | `/api/warga/export?format=xlsx\|csv` | Export data warga (default csv) |
| POST | `/api/warga/import` | Import; field `files[]` menerima banyak file |

**Kolom kanonik** (urutan di file): `nik, nama, alamat, dusun, penghasilan_bulanan, status_pekerjaan, jumlah_tanggungan, kondisi_rumah, periode_data`. Export menambahkan `status_validasi` di akhir. `status_pekerjaan` & `kondisi_rumah` harus salah satu label kategori aktif (lihat sheet "Petunjuk & Kategori" pada template).

**Template XLSX (rapi & user-friendly):** header berstyle, baris dibekukan (freeze), auto-filter, format rupiah otomatis pada kolom penghasilan, **dropdown (data validation)** untuk kolom pekerjaan & kondisi rumah yang opsi-nya ditarik dinamis dari DB, satu baris contoh, serta lembar tambahan **"Petunjuk & Kategori"** berisi daftar nilai valid + panduan pengisian. Kolom NIK diatur format **teks** agar NIK 16 digit tidak berubah jadi notasi ilmiah di Excel.

**Bulk upload:** satu request bisa membawa banyak file. Format tiap file dideteksi dari ekstensinya. Baris tervalidasi satu per satu; baris keliru **dilewati** (tidak menggagalkan file/baris lain), NIK ganda — baik di DB maupun antar file — otomatis dilewati. Respons berisi ringkasan **per-file** (`diimpor`, `gagal`, `detail_gagal`) plus total. Tiap batch dicatat di **audit log** (`warga.import`).

| No | Skenario | Hasil | Status |
|---|---|---|---|
| T1 | Unduh template **XLSX** | 200, application/vnd…spreadsheetml; sheet "Data Warga" aktif, dropdown F→`'Petunjuk & Kategori'!$A$1:$A$5` | ✅ |
| T2 | Unduh template CSV (`?format=csv`) | 200, text/csv (dgn BOM UTF-8) | ✅ |
| T3 | Export **XLSX** | 200, application/vnd…spreadsheetml; kolom `dusun` terisi | ✅ |
| T4 | Export CSV (default) | 200, text/csv; memuat kolom `dusun` | ✅ |
| T5 | Import **XLSX** (1 baris valid) | 200: `diimpor:1` | ✅ |
| T6 | Import **CSV** (2 valid + 1 NIK salah) | 200: `diimpor:2, gagal:1` ("NIK harus 16 digit") | ✅ |
| T7 | **Bulk**: 2 file campur (xlsx + csv), 1 NIK duplikat DB | 200: `diimpor:2, gagal:1`; per-file jelas; duplikat dilewati | ✅ |
| T8 | Import file ekstensi tak didukung (mis. .docx) | 422 validation error | ✅ |
| T9 | Audit log setelah import | `activity_logs`: `warga.import` + user + `{diimpor,gagal,file}` | ✅ |

**Catatan teknis (untuk skripsi):**
- Library: `phpoffice/phpspreadsheet` 5.8 (manual PHP, bukan layanan eksternal). Komputasi di **log-space** TIDAK terkait di sini — fitur ini murni I/O data.
- Validasi baris dipusatkan di `app/Services/Import/WargaImportProcessor.php` (satu sumber kebenaran, dipakai csv & xlsx), builder spreadsheet di `WargaSpreadsheetBuilder.php`.
- **Bug ditemukan & diperbaiki:** file migration `warga` di repo tidak memuat kolom `dusun`, padahal DB live & model (serta laporan rekapitulasi per dusun) memakainya. Migrasi diperbaiki agar `migrate:fresh` tidak lagi menghapus `dusun`.
- **Uji otomatis:** `tests/Feature/ImportWargaTest.php` (5 test, 20 assertion). Full suite 15/15 lulus.
- **Validasi end-to-end pada DB live `siklas_nb`** (35 warga) via kernel HTTP: bulk xlsx+csv dengan 1 NIK duplikat → tepat 2 baru masuk, 1 dilewati, data uji dibersihkan setelahnya.

### A-bis. Perbaikan bug NIK soft-delete → HTTP 500
> Bug ditemukan saat review: NIK pada baris yang di-soft-delete tetap memakai constraint unique
> di DB, tetapi pengecekan duplikat import (`Warga::pluck('nik')`) tidak membaca soft-deleted →
> re-import NIK itu lolos lalu `Warga::create` melempar QueryException tak tertangkap → 500,
> seluruh import gagal di tengah.
- **Perbaikan:** pengecekan duplikat kini `Warga::withTrashed()->pluck('nik')` + `Warga::create`
  dibungkus `try/catch` sehingga error DB-level apa pun menjadi **baris dilewati** (juga menutup
  race condition dua admin import NIK sama bersamaan).
- **Test:** `test_nik_soft_deleted_dilewati_bukan_500`.

### D. Import/Export Data Training (CSV & XLSX, bulk)
> Fitur **berbobot akademik**: data training = dataset berlabel yang membangun model NB.
> Kini bisa diimpor/diekspor massal (csv & xlsx, multi-file) — memudahkan ganti dataset & re-train.

| No | Skenario | Hasil | Status |
|---|---|---|---|
| T10 | Unduh template data training XLSX | 200, sheet "Data Training", dropdown 5 kolom | ✅ |
| T11 | Import data training (2 file: csv + xlsx, 1 label invalid) | 200: 3 valid masuk, 1 dilewati | ✅ |
| T12 | Export data training XLSX/CSV | 200, format benar | ✅ |
| T13 | Audit log import data training | `activity_logs`: `data_training.import` | ✅ |

Kolom kanonik: `penghasilan_kategori, pekerjaan_kategori, tanggungan_kategori, kondisi_rumah_kategori, label_kelas`. Semua 4 kolom kategori + label_kelas punya dropdown (label dari DB). Baris berlabel identik sah & memperkuat probabilitas → tidak ada anti-duplikat.

### E. Penegakan Batasan #3 — "data terbaru & tervalidasi dalam periode tertentu"
> Proposal batasan #3 menyatakan data yang dianalisis = data terbaru & tervalidasi dalam periode
> tertentu (mis. 3 bulan terakhir). Sebelumnya `periode_data` ada tapi **tidak ditegakkan**.
- **Perbaikan:** klasifikasi (single & batch) kini hanya memproses warga dgn `periode_data` dalam
  **N bulan terakhir** (default **3**, parameter `?periode_bulan=N`; `0` = semua periode).
- Default 3 bulan mengikuti contoh proposal; admin dapat mengatur via input di halaman Klasifikasi.
- **Test:** `tests/Feature/KlasifikasiPeriodeTest.php` (3 test) — data 6 bulan lalu ditolak (422),
  `periode_bulan=0` membolehkan, data baru lolos cek periode.

### F. Filter Export Warga (status_validasi / dusun)
> Export warga kini menerima `?status_validasi=` & `?dusun=` untuk mengekspor subset (mis. hanya
> warga divalidasi utk batch klasifikasi selektif). Test: `test_export_warga_filter_status_validasi`.

### Catatan rekapitulasi tes otomatis (pasca penambahan)
- `tests/Feature/ImportWargaTest.php` (9 test) + `KlasifikasiPeriodeTest.php` (3 test).
- **Full suite: 22/22 lulus, 66 assertion.** Frontend `vite build` sukses.



### B. Laporan Hasil Klasifikasi (CSV & PDF)
| No | Skenario | Hasil | Status |
|---|---|---|---|
| T5 | GET /laporan/klasifikasi.csv | 200, text/csv | ✅ |
| T6 | GET /laporan/klasifikasi.pdf (dompdf) | 200, application/pdf (~879 KB) | ✅ |

### C. Audit Log
| No | Skenario | Hasil | Status |
|---|---|---|---|
| T7 | Aktivitas (validasi) tercatat | activity_log: `warga.validasi` + user + deskripsi | ✅ |
| T8 | GET /activity-logs (superadmin) | 200, daftar aktivitas | ✅ |
| T9 | Role: admin akses audit log | 403 | ✅ |

### Alur lengkap terintegrasi (16 langkah, semua LULUS)
login tiap role → dashboard → buat+validasi+klasifikasi 2 warga (prediksi benar: profil miskin→tidak_layak, profil mapan→layak) → training → evaluasi (acc 0.75) → approval (approve+reject) → audit log → laporan CSV+PDF → 401 untuk no-auth.

---





