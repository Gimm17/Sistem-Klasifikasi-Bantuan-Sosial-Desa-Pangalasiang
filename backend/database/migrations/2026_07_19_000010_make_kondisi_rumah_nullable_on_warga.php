<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buat kondisi_rumah nullable — label diisi saat validasi manual (setelah foto diunggah),
 * bukan saat create draft. Lihat plan: upload galeri foto rumah + label saat validasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warga', function (Blueprint $table) {
            $table->string('kondisi_rumah')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Hanya aman jika tidak ada baris NULL; di environment baru tidak ada data.
        Schema::table('warga', function (Blueprint $table) {
            $table->string('kondisi_rumah')->nullable(false)->change();
        });
    }
};
