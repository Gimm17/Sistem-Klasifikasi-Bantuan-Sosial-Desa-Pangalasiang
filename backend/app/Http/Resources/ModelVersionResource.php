<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModelVersionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'model_version' => $this->model_version,
            'is_active' => (bool) $this->is_active,
            'total_data' => (int) $this->total_data,
            'count_layak' => (int) $this->count_layak,
            'count_tidak_layak' => (int) $this->count_tidak_layak,
            'trained_by' => $this->whenLoaded('trainedBy', fn () => [
                'id' => $this->trainedBy?->id,
                'name' => $this->trainedBy?->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
