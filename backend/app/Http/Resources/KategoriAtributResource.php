<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KategoriAtributResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'atribut_klasifikasi_id' => $this->atribut_klasifikasi_id,
            'atribut_kode' => $this->whenLoaded('atribut', fn () => $this->atribut->kode),
            'label' => $this->label,
            'batas_bawah' => $this->batas_bawah,
            'batas_atas' => $this->batas_atas,
            'urutan' => (int) $this->urutan,
        ];
    }
}
