<?php

return [

    // Path yang dilewati CORS middleware. API + endpoint CSRF Sanctum.
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Metode HTTP yang diizinkan.
    'allowed_methods' => ['*'],

    // Origin frontend SPA Vue. WAJIB eksplisit (bukan '*') karena
    // supports_credentials=true — cookie tidak akan dikirim untuk origin '*'.
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Penting: true agar cookie sesi (Sanctum SPA) dikirim cross-origin
    // dari frontend (5173) ke backend (8000).
    'supports_credentials' => true,

];
