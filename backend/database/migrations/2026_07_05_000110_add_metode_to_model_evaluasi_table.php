<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah metadata metode evaluasi (untuk membedakan hold-out vs k-fold).
     *  - metode: 'holdout' (default utk backward-compat) atau 'kfold'
     *  - k: jumlah fold (nullable, hanya diisi metode='kfold')
     */
    public function up(): void
    {
        Schema::table('model_evaluasi', function (Blueprint $table) {
            $table->string('metode', 10)->default('holdout')->after('model_version');
            $table->unsignedTinyInteger('k')->nullable()->after('metode');
        });
    }

    public function down(): void
    {
        Schema::table('model_evaluasi', function (Blueprint $table) {
            $table->dropColumn(['metode', 'k']);
        });
    }
};
