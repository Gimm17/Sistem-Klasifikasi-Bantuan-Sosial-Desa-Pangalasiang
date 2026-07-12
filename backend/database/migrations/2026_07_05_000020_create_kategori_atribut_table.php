<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori / bin per atribut.
     * - Atribut numerik (penghasilan, tanggungan): pakai batas_bawah & batas_atas.
     * - Atribut kategorikal (pekerjaan, kondisi_rumah): batas_* NULL, cukup label.
     *
     * Aturan (divalidasi di controller Fase 3): bin numerik TIDAK boleh overlap / berlubang.
     */
    public function up(): void
    {
        Schema::create('kategori_atribut', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atribut_klasifikasi_id')
                ->constrained('atribut_klasifikasi')
                ->cascadeOnDelete();
            $table->string('label');
            $table->decimal('batas_bawah', 15, 2)->nullable();
            $table->decimal('batas_atas', 15, 2)->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('atribut_klasifikasi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_atribut');
    }
};
