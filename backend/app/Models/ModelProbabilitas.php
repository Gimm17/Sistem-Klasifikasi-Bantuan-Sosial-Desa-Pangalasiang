<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['model_version', 'kelas', 'prior_probability', 'atribut_kode', 'kategori', 'likelihood'])]
class ModelProbabilitas extends Model
{
    /** @use HasFactory<\Database\Factories\ModelProbabilitasFactory> */
    use HasFactory;

    protected $table = 'model_probabilitas';

    protected function casts(): array
    {
        return [
            'prior_probability' => 'decimal:8',
            'likelihood' => 'decimal:8',
        ];
    }
}
