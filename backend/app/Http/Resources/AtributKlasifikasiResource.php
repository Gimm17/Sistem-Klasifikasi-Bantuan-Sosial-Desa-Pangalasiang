<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AtributKlasifikasiResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama_tampilan' => $this->nama_tampilan,
            'tipe' => $this->tipe,
            'aktif' => (bool) $this->aktif,
            // Daftar kategori/bin termuat saat eager loaded.
            'kategori' => KategoriAtributResource::collection($this->whenLoaded('kategori')),
        ];
    }
}
