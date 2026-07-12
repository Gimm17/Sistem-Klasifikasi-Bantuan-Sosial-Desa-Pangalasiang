<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel hasil training Naive Bayes (auditable, bukan sekadar cache JSON).
     * Satu model_version berisi:
     *   - 2 baris prior (atribut_kode = NULL): P(kelas) untuk layak & tidak_layak
     *   - baris likelihood: P(atribut=kategori | kelas)
     *
     * Dibangun oleh ProbabilityTableBuilder::build() (Fase 4).
     */
    public function up(): void
    {
        Schema::create('model_probabilitas', function (Blueprint $table) {
            $table->id();
            $table->string('model_version'); // mis. 'v20260705-1'
            $table->enum('kelas', ['layak', 'tidak_layak']);
            $table->decimal('prior_probability', 10, 8); // P(kelas)
            $table->string('atribut_kode')->nullable();   // NULL jika baris ini = prior
            $table->string('kategori')->nullable();
            $table->decimal('likelihood', 10, 8)->nullable(); // P(atribut=kategori | kelas)
            $table->timestamps();

            // Nama index eksplisit (nama default > 64 char -> ditolak MySQL).
            $table->index(['model_version', 'kelas', 'atribut_kode', 'kategori'], 'modprob_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_probabilitas');
    }
};
