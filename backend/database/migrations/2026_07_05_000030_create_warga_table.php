<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data warga / calon penerima.
     * Menyimpan nilai MENTAH (penghasilan_bulanan, dst.); versi kategorikal
     * dihitung on-the-fly saat klasifikasi (lihat AttributeCategorizer, Fase 4).
     */
    public function up(): void
    {
        Schema::create('warga', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('dusun', 100)->nullable(); // dipakai laporan rekapitulasi per dusun
            $table->unsignedBigInteger('penghasilan_bulanan')->default(0); // rupiah, integer
            $table->string('status_pekerjaan');   // salah satu label kategori 'pekerjaan'
            $table->unsignedInteger('jumlah_tanggungan')->default(0);
            $table->string('kondisi_rumah');      // salah satu label kategori 'kondisi_rumah'
            $table->date('periode_data');         // batasan #3: data terbaru & tervalidasi
            $table->enum('status_validasi', ['draft', 'divalidasi'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index('status_validasi');
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warga');
    }
};
