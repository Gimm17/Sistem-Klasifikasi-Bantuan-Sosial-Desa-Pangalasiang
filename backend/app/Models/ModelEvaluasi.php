<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'model_version', 'metode', 'k',
    'jumlah_data_uji',
    'accuracy', 'precision', 'recall', 'f1_score',
    'confusion_matrix_json',
])]
class ModelEvaluasi extends Model
{
    /** @use HasFactory<\Database\Factories\ModelEvaluasiFactory> */
    use HasFactory;

    protected $table = 'model_evaluasi';

    protected function casts(): array
    {
        return [
            'k' => 'integer',
            'jumlah_data_uji' => 'integer',
            'accuracy' => 'decimal:4',
            'precision' => 'decimal:4',
            'recall' => 'decimal:4',
            'f1_score' => 'decimal:4',
            'confusion_matrix_json' => 'array',
        ];
    }
}
