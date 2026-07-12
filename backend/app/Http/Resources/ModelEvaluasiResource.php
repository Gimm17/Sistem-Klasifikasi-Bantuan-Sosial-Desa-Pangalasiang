<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModelEvaluasiResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'model_version' => $this->model_version,
            'metode' => $this->metode,
            'k' => $this->k,
            'jumlah_data_uji' => (int) $this->jumlah_data_uji,
            'accuracy' => $this->accuracy,
            'precision' => $this->precision,
            'recall' => $this->recall,
            'f1_score' => $this->f1_score,
            'confusion_matrix' => $this->confusion_matrix_json,
            'created_at' => $this->created_at,
        ];
    }
}
