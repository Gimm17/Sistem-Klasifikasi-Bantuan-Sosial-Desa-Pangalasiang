<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nik', 'nama', 'alamat', 'dusun',
    'penghasilan_bulanan', 'status_pekerjaan', 'jumlah_tanggungan', 'kondisi_rumah',
    'periode_data', 'status_validasi', 'created_by',
])]
class Warga extends Model
{
    /** @use HasFactory<\Database\Factories\WargaFactory> */
    use HasFactory, SoftDeletes;

    // 'warga' adalah kata massa (tidak plural) -> kunci eksplisit nama tabel.
    protected $table = 'warga';

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function latestHasil(): HasOne
    {
        return $this->hasOne(HasilKlasifikasi::class)->latestOfMany();
    }

    public function hasilKlasifikasi(): HasMany
    {
        return $this->hasMany(HasilKlasifikasi::class);
    }

    public function dataTraining(): HasMany
    {
        return $this->hasMany(DataTraining::class);
    }

    protected function casts(): array
    {
        return [
            'penghasilan_bulanan' => 'integer',
            'jumlah_tanggungan' => 'integer',
            'periode_data' => 'date',
        ];
    }
}
