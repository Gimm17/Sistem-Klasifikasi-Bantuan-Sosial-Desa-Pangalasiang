<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * Otentikasi SPA Sanctum (cookie-based).
 *
 * Alur: GET /sanctum/csrf-cookie -> POST /api/login (session regenerate) -> GET /api/me.
 */
class AuthController extends Controller
{
    /**
     * Login. Berhasil -> 200 + data user; kredensial salah -> 422.
     */
    public function login(LoginRequest $request): JsonResource|JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::guard('web')->attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah.'], 422);
        }

        $request->session()->regenerate();

        return (new UserResource($request->user()))
            ->additional(['message' => 'Login berhasil.']);
    }

    /**
     * Data user yang sedang login.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Logout: hapus sesi + invalidate cookie.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
