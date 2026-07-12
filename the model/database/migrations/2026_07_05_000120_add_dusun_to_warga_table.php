<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah field dusun pada tabel warga untuk mendukung
     * rekapitulasi per wilayah (Fitur Rekapitulasi Per Dusun).
     */
    public function up(): void
    {
        Schema::table('warga', function (Blueprint $table) {
            $table->string('dusun', 100)->nullable()->after('alamat');
        });
    }

    public function down(): void
    {
        Schema::table('warga', function (Blueprint $table) {
            $table->dropColumn('dusun');
        });
    }
};
