<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah metadata training Naive Bayes:
     *  - vocab_source: sumber jumlahKategoriUnik utk Laplace ('observed' di data training
     *    atau 'defined' dari tabel kategori_atribut). Default 'observed' (backward-compatible).
     *  - unique_per_attribute: ukuran vocab per atribut saat training, dipakai fallback
     *    likelihood saat prediksi (konsisten dgn vocab_source yg dipakai).
     */
    public function up(): void
    {
        Schema::table('model_versions', function (Blueprint $table) {
            $table->string('vocab_source')->default('observed')->after('count_tidak_layak');
            $table->json('unique_per_attribute')->nullable()->after('vocab_source');
        });
    }

    public function down(): void
    {
        Schema::table('model_versions', function (Blueprint $table) {
            $table->dropColumn(['vocab_source', 'unique_per_attribute']);
        });
    }
};
