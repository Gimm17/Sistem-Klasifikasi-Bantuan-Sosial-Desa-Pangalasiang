<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registri tiap versi model hasil training Naive Bayes + statistik training.
     * Dibutuhkan untuk:
     *  - GET /api/model/versions (riwayat model)
     *  - menghitung likelihood fallback bagi kategori yang tak pernah muncul di
     *    training (butuh count_class & dapat diturunkan dari sini)
     *  - audit (siapa & kapan training).
     */
    public function up(): void
    {
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('model_version')->unique(); // mis. 'v20260705-143012'
            $table->unsignedInteger('total_data');
            $table->unsignedInteger('count_layak');
            $table->unsignedInteger('count_tidak_layak');
            $table->boolean('is_active')->default(false);
            $table->foreignId('trained_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_versions');
    }
};
