<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AtributKlasifikasiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DataTrainingController;
use App\Http\Controllers\Api\EvaluasiController;
use App\Http\Controllers\Api\ImportExportController;
use App\Http\Controllers\Api\KategoriAtributController;
use App\Http\Controllers\Api\KlasifikasiController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\RekapitulasiController;
use App\Http\Controllers\Api\ModelController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WargaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — SIKLAS-NB
|--------------------------------------------------------------------------
| Otentikasi SPA Sanctum (cookie-based). GET /sanctum/csrf-cookie disediakan
| otomatis oleh Sanctum. Pembatasan peran di-gate middleware 'role:...'.
*/

// Publik (login tidak butuh auth, dengan rate limit 5x per menit).
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Terotentikasi.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- User management (superadmin) ---
    Route::apiResource('/users', UserController::class)->middleware('role:superadmin');

    // --- Atribut & kategori klasifikasi ---
    Route::get('/atribut-klasifikasi', [AtributKlasifikasiController::class, 'index'])
        ->middleware('role:admin,approver');
    Route::get('/atribut-klasifikasi/{atributKlasifikasi}', [AtributKlasifikasiController::class, 'show'])
        ->middleware('role:admin,approver,superadmin');

    // CRUD kategori/bin — admin & superadmin.
    Route::apiResource('/kategori-atribut', KategoriAtributController::class)
        ->middleware('role:admin,superadmin');

    // --- Data latih berlabel (MVP #4) — admin & superadmin ---
    // Rute statis import/export HARUS sebelum apiResource (/data-training/{data_training}).
    Route::get('/data-training/export', [ImportExportController::class, 'exportDataTraining'])->middleware('role:admin,superadmin');
    Route::get('/data-training/import/template', [ImportExportController::class, 'templateDataTraining'])->middleware('role:admin,superadmin');
    Route::post('/data-training/import', [ImportExportController::class, 'importDataTraining'])->middleware('role:admin,superadmin');
    Route::apiResource('/data-training', DataTrainingController::class)
        ->middleware('role:admin,superadmin');

    // --- Master data warga ---
    Route::get('/warga', [WargaController::class, 'index'])->middleware('role:admin,approver');
    // Rute statis HARUS sebelum /warga/{warga} agar tak tertangkap sbg {id}.
    Route::get('/warga/export', [ImportExportController::class, 'exportWarga'])->middleware('role:admin');
    Route::get('/warga/import/template', [ImportExportController::class, 'templateWarga'])->middleware('role:admin');
    Route::post('/warga/import', [ImportExportController::class, 'importWarga'])->middleware('role:admin');
    Route::get('/warga/{warga}', [WargaController::class, 'show'])->middleware('role:admin,approver');
    Route::post('/warga', [WargaController::class, 'store'])->middleware('role:admin');
    Route::put('/warga/{warga}', [WargaController::class, 'update'])->middleware('role:admin');
    Route::patch('/warga/{warga}/validasi', [WargaController::class, 'validasi'])->middleware('role:admin');
    Route::delete('/warga/{warga}', [WargaController::class, 'destroy'])->middleware('role:admin');

    // --- Model Naive Bayes (training & versi) — admin & superadmin ---
    Route::post('/model/train', [ModelController::class, 'train'])->middleware('role:admin,superadmin');
    Route::get('/model/versions', [ModelController::class, 'versions'])->middleware('role:admin,superadmin');
    Route::get('/model/atribut-importance', [ModelController::class, 'atributImportance'])->middleware('role:admin,approver,superadmin');

    // --- Klasifikasi (prediksi & hasil) ---
    Route::post('/klasifikasi/batch', [KlasifikasiController::class, 'batch'])->middleware('role:admin');
    Route::post('/klasifikasi/{warga}', [KlasifikasiController::class, 'predictSingle'])->middleware('role:admin');
    Route::get('/klasifikasi', [KlasifikasiController::class, 'index'])->middleware('role:admin,approver');
    Route::get('/klasifikasi/{hasilKlasifikasi}', [KlasifikasiController::class, 'show'])->middleware('role:admin,approver');

    // --- Approval / keputusan akhir — HANYA approver (kades/sekdes) ---
    Route::get('/approval/queue', [ApprovalController::class, 'queue'])->middleware('role:approver');
    Route::patch('/approval/{hasilKlasifikasi}/approve', [ApprovalController::class, 'approve'])->middleware('role:approver');
    Route::patch('/approval/{hasilKlasifikasi}/reject', [ApprovalController::class, 'reject'])->middleware('role:approver');
    Route::patch('/approval/{hasilKlasifikasi}/override', [ApprovalController::class, 'override'])->middleware('role:approver');

    // --- Evaluasi model (akurasi/confusion matrix) ---
    Route::post('/evaluasi/run', [EvaluasiController::class, 'run'])->middleware('role:admin,superadmin');
    Route::get('/evaluasi', [EvaluasiController::class, 'index'])->middleware('role:admin,approver,superadmin');

    // --- Dashboard (statistik ringkas) ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('role:admin,approver,superadmin');

    // --- Rekapitulasi per dusun ---
    Route::get('/rekapitulasi/dusun', [RekapitulasiController::class, 'perDusun'])->middleware('role:admin,approver,superadmin');
    Route::get('/rekapitulasi/dusun.pdf', [RekapitulasiController::class, 'pdfRekapDusun'])->middleware('role:admin,approver,superadmin');
    Route::get('/laporan/rincian-warga.pdf', [RekapitulasiController::class, 'pdfRincianWarga'])->middleware('role:admin,approver,superadmin');
    Route::get('/laporan/rincian-kk/{warga}.pdf', [RekapitulasiController::class, 'pdfRincianKk'])->middleware('role:admin,approver,superadmin');

    // --- Laporan hasil klasifikasi CSV/PDF (TAMBAHAN) ---
    Route::get('/laporan/klasifikasi.csv', [LaporanController::class, 'csv'])->middleware('role:admin,approver,superadmin');
    Route::get('/laporan/klasifikasi.pdf', [LaporanController::class, 'pdf'])->middleware('role:admin,approver,superadmin');

    // --- Audit log (TAMBAHAN) — superadmin ---
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('role:superadmin');
});
