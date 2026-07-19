<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WargaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nik' => $this->nik,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'dusun' => $this->dusun,
            'penghasilan_bulanan' => (int) $this->penghasilan_bulanan,
            'status_pekerjaan' => $this->status_pekerjaan,
            'jumlah_tanggungan' => (int) $this->jumlah_tanggungan,
            'kondisi_rumah' => $this->kondisi_rumah, // boleh null saat draft
            'periode_data' => $this->periode_data?->format('Y-m-d'),
            'status_validasi' => $this->status_validasi,
            'created_by' => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
            ],
            // Galeri foto rumah (diload eager saat show/update/store). Inline mapping
            // supaya tidak bergantung pada resource terpisah.
            'fotos' => $this->whenLoaded('fotos', fn () => $this->fotos->map(fn ($f) => [
                'id' => $f->id,
                'url' => $f->url,
                'urutan' => $f->urutan,
                'original_name' => $f->original_name,
                'size_bytes' => $f->size_bytes,
            ])),
            // withCount('fotos') pada index -> $fotos_count.
            'foto_count' => $this->when(isset($this->fotos_count), $this->fotos_count),
            // Histori hasil klasifikasi (diload eager saat show). Inline supaya tidak
            // bergantung pada HasilKlasifikasiResource (dibuat di Fase 5).
            'hasil_klasifikasi' => $this->whenLoaded('hasilKlasifikasi', fn () => $this->hasilKlasifikasi->map(fn ($h) => [
                'id' => $h->id,
                'model_version' => $h->model_version,
                'prediksi_kelas' => $h->prediksi_kelas,
                'prob_layak' => $h->prob_layak,
                'prob_tidak_layak' => $h->prob_tidak_layak,
                'status_approval' => $h->status_approval,
                'created_at' => $h->created_at,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
