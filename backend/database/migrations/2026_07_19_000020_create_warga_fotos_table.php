<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Galeri foto rumah per warga (dokumentasi kondisi rumah).
 * Foto disimpan di disk 'public' (storage/app/public/warga/fotos/{warga_id}/...).
 * Maks 5 foto/warga & 2MB/foto — dibatasi di controller (bukan DB).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warga_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warga_id')->constrained('warga')->cascadeOnDelete();
            $table->string('path');                       // path relatif di disk public
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index(['warga_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warga_fotos');
    }
};
