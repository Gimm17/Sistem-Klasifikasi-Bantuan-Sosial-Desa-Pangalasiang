<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data latih berlabel: kombinasi kategori 4 atribut + label kelas (layak/tidak_layak).
     * Dipakai ProbabilityTableBuilder untuk menghitung prior & likelihood (Fase 4).
     *
     * Catatan: kolom *_kategori menyimpan LABEL kategori (bukan nilai mentah).
     * warga_id nullable karena data training boleh independen (bukan dari warga).
     */
    public function up(): void
    {
        Schema::create('data_training', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warga_id')->nullable()->constrained('warga')->nullOnDelete();
            $table->string('penghasilan_kategori');
            $table->string('pekerjaan_kategori');
            $table->string('tanggungan_kategori');
            $table->string('kondisi_rumah_kategori');
            $table->enum('label_kelas', ['layak', 'tidak_layak']);
            $table->timestamps();

            $table->index('label_kelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_training');
    }
};
