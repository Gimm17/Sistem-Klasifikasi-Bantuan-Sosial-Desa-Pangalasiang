<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat evaluasi model (accuracy, precision, recall, F1, confusion matrix)
     * per model_version. Bahan Bab Hasil & Pembahasan skripsi.
     */
    public function up(): void
    {
        Schema::create('model_evaluasi', function (Blueprint $table) {
            $table->id();
            $table->string('model_version');
            $table->unsignedInteger('jumlah_data_uji');
            $table->decimal('accuracy', 5, 4);
            $table->decimal('precision', 5, 4);
            $table->decimal('recall', 5, 4);
            $table->decimal('f1_score', 5, 4);
            $table->json('confusion_matrix_json'); // {TP, TN, FP, FN}
            $table->timestamps();

            $table->index('model_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_evaluasi');
    }
};
