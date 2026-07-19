<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NIK jadi nullable agar warga yang di-soft-delete dapat di-null-kan NIK-nya,
 * sehingga index UNIQUE (yang membolehkan banyak NULL) tidak menghalangi
 * pendaftaran ulang NIK yang sama. NIK aktif tetap wajib 16 digit (divalidasi
 * di Form Request) & unik di antara baris yang belum dihapus.
 *
 * Latar: sebelumnya `nik` NOT NULL + unique biasa -> soft-delete mempertahankan
 * NIK -> NIK tidak bisa dipakai ulang (UNIQUE constraint failed saat create).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warga', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->change();
        });

        // Bersihkan NIK warga yang SUDAH di-soft-delete sebelum migrasi ini, agar
        // NIK mereka bisa dipakai ulang. (Warga yang dihapus setelah ini akan di-null
        // otomatis oleh WargaController::destroy.) No-op di instalasi bersih.
        DB::table('warga')->whereNotNull('deleted_at')->whereNotNull('nik')->update(['nik' => null]);
    }

    public function down(): void
    {
        // Null-out TIDAK bisa dibalik aman jika ada baris soft-delete dgn NIK NULL.
        // Batasi: hanya ubah kembali ke NOT NULL jika tidak ada baris NULL.
        Schema::table('warga', function (Blueprint $table) {
            $table->string('nik', 16)->nullable(false)->default('')->change();
        });
    }
};
