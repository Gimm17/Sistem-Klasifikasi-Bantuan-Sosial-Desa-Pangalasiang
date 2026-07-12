<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — SPA Catch-All
|--------------------------------------------------------------------------
| Semua non-API request diteruskan ke Vue SPA (index.html di public/dist/).
| API routes ada di routes/api.php (prefix /api).
| Pada production (cPanel), public_html mengarah ke backend/public/.
*/

// Catch-all: serve Vue SPA untuk semua GET request yang bukan API/Sanctum
Route::get('{any?}', function () {
    $indexPath = public_path('dist/index.html');
    if (!file_exists($indexPath)) {
        abort(503, 'Frontend belum di-build. Jalankan: cd frontend && npm run build');
    }
    return response()->file($indexPath);
})->where('any', '.*');
