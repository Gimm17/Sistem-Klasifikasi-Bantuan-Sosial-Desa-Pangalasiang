<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'warga_id',
    'penghasilan_kategori', 'pekerjaan_kategori', 'tanggungan_kategori', 'kondisi_rumah_kategori',
    'label_kelas',
])]
class DataTraining extends Model
{
    /** @use HasFactory<\Database\Factories\DataTrainingFactory> */
    use HasFactory;

    protected $table = 'data_training';

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class);
    }
}
