<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DataTrainingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'warga_id' => $this->warga_id,
            'penghasilan_kategori' => $this->penghasilan_kategori,
            'pekerjaan_kategori' => $this->pekerjaan_kategori,
            'tanggungan_kategori' => $this->tanggungan_kategori,
            'kondisi_rumah_kategori' => $this->kondisi_rumah_kategori,
            'label_kelas' => $this->label_kelas,
            'created_at' => $this->created_at,
        ];
    }
}
