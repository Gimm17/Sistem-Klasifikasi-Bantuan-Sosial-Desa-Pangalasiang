# SIKLAS-NB — Klasifikasi Bantuan Sosial (Naive Bayes)

Implementasi **Naive Bayes Classifier** berbasis Laravel 13 + Vue 3 SPA untuk mendukung keputusan kelayakan penerima bantuan sosial di **Desa Pangalasiang, Kecamatan Sojol, Kabupaten Donggala**.

[![Live App](https://img.shields.io/badge/LIVE-siklas.pinnhost.my.id-0f172a?style=flat-square&logo=googlechrome&logoColor=white)](https://siklas.pinnhost.my.id/)
![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![Vue 3](https://img.shields.io/badge/Vue-3-4FC08D?style=flat-square&logo=vuedotjs&logoColor=white)

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/dashboard.webp" alt="SIKLAS-NB Dashboard" /></td>
    <td width="50%"><img src="docs/screenshots/evaluation.webp" alt="Evaluasi Model Naive Bayes" /></td>
  </tr>
</table>

### Kapabilitas Utama

- **Engine Naive Bayes Terkalibrasi** — Menghitung probabilitas kelayakan beserta pembobotan atribut berpengaruh (*Likelihood Divergence*).
- **Evaluasi Model Lengkap** — Dukungan *Hold-out Split* dan *Stratified k-Fold Cross-Validation* (`Accuracy`, `Precision`, `Recall`, `F1-Score`, Confusion Matrix).
- **Workflow Desa End-to-End** — Impor massal CSV/XLSX, antrean persetujuan Kepala Desa/Sekdes, galeri rumah warga, hingga laporan PDF rekapitulasi per dusun.

### Struktur Monorepo & Menjalankan

```bash
# Backend API (:8000)
cd backend && composer install && cp .env.example .env && php artisan migrate --seed && php artisan serve

# Frontend SPA (:5173)
cd frontend && npm install && npm run dev
```

Referensi arsitektur & spesifikasi lengkap berada di [`docs/`](docs/CONTEXT.md).
