<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['warga_id', 'path', 'urutan', 'original_name', 'size_bytes'])]
class WargaFoto extends Model
{
    protected $table = 'warga_fotos';

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class);
    }

    /** URL publik foto (dilayani via /storage symlink). */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'size_bytes' => 'integer',
        ];
    }
}
