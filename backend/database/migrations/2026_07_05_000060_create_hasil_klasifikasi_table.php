<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Output prediksi Naive Bayes per warga + alur approval.
     * breakdown_json menyimpan rincian prior/likelihood per atribut (explainable —
     * untuk transparansi & bahan sidang skripsi).
     */
    public function up(): void
    {
        Schema::create('hasil_klasifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warga_id')->constrained('warga')->cascadeOnDelete();
            $table->string('model_version');
            $table->decimal('prob_layak', 10, 8);
            $table->decimal('prob_tidak_layak', 10, 8);
            $table->enum('prediksi_kelas', ['layak', 'tidak_layak']);
            $table->json('breakdown_json');
            $table->enum('status_approval', ['pending', 'approved', 'rejected', 'overridden'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_approval')->nullable();
            $table->timestamps();

            $table->index('status_approval');
            $table->index(['warga_id', 'model_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_klasifikasi');
    }
};
