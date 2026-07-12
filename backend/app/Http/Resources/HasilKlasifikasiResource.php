<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Output hasil klasifikasi. `breakdown` memuat rincian perhitungan (prior,
 * likelihood per atribut, skor) — explainable, untuk transparansi & sidang.
 */
class HasilKlasifikasiResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'warga' => $this->whenLoaded('warga', fn () => [
                'id' => $this->warga->id,
                'nik' => $this->warga->nik,
                'nama' => $this->warga->nama,
            ]),
            'model_version' => $this->model_version,
            'prob_layak' => $this->prob_layak,
            'prob_tidak_layak' => $this->prob_tidak_layak,
            'prediksi_kelas' => $this->prediksi_kelas,
            'status_approval' => $this->status_approval,
            'catatan_approval' => $this->catatan_approval,
            'approved_by' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
            ]),
            // rincian perhitungan lengkap (kategori input, skor, prior & likelihood).
            'breakdown' => $this->breakdown_json,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
