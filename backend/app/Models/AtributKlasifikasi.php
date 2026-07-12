<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama_tampilan', 'tipe', 'aktif'])]
class AtributKlasifikasi extends Model
{
    /** @use HasFactory<\Database\Factories\AtributKlasifikasiFactory> */
    use HasFactory;

    protected $table = 'atribut_klasifikasi';

    public function kategori(): HasMany
    {
        return $this->hasMany(KategoriAtribut::class, 'atribut_klasifikasi_id');
    }

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }
}
