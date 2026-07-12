<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware otorisasi berbasis peran.
 *
 * Dipakai setelah auth:sanctum (agar $request->user() tersedia).
 * Contoh route: ->middleware(['auth:sanctum', 'role:admin,superadmin'])
 *
 * Prinsip (docs/system_prompt.md): jangan andalkan cek role di frontend saja —
 * setiap endpoint backend wajib memverifikasi otorisasi.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Akses ditolak: peran Anda tidak diizinkan untuk aksi ini.');
        }

        return $next($request);
    }
}
