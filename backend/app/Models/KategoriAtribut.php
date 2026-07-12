<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['atribut_klasifikasi_id', 'label', 'batas_bawah', 'batas_atas', 'urutan'])]
class KategoriAtribut extends Model
{
    /** @use HasFactory<\Database\Factories\KategoriAtributFactory> */
    use HasFactory;

    protected $table = 'kategori_atribut';

    public function atribut(): BelongsTo
    {
        return $this->belongsTo(AtributKlasifikasi::class, 'atribut_klasifikasi_id');
    }

    protected function casts(): array
    {
        return [
            'batas_bawah' => 'decimal:2',
            'batas_atas' => 'decimal:2',
            'urutan' => 'integer',
        ];
    }
}
