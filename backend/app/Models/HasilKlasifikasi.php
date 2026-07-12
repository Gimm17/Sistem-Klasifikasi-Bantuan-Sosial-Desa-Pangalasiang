<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'warga_id', 'model_version',
    'prob_layak', 'prob_tidak_layak', 'prediksi_kelas',
    'breakdown_json',
    'status_approval', 'approved_by', 'catatan_approval',
])]
class HasilKlasifikasi extends Model
{
    /** @use HasFactory<\Database\Factories\HasilKlasifikasiFactory> */
    use HasFactory;

    protected $table = 'hasil_klasifikasi';

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected function casts(): array
    {
        return [
            'breakdown_json' => 'array',
            'prob_layak' => 'decimal:8',
            'prob_tidak_layak' => 'decimal:8',
        ];
    }
}
