<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['model_version', 'total_data', 'count_layak', 'count_tidak_layak', 'vocab_source', 'unique_per_attribute', 'is_active', 'trained_by'])]
class ModelVersion extends Model
{
    /** @use HasFactory<\Database\Factories\ModelVersionFactory> */
    use HasFactory;

    protected $table = 'model_versions';

    public function trainedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trained_by');
    }

    protected function casts(): array
    {
        return [
            'total_data' => 'integer',
            'count_layak' => 'integer',
            'count_tidak_layak' => 'integer',
            'vocab_source' => 'string',
            'unique_per_attribute' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
