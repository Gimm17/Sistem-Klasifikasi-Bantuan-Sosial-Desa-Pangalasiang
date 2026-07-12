<?php

namespace App\Http\Requests\Kategori;

class UpdateKategoriAtributRequest extends StoreKategoriAtributRequest
{
    // Aturan & validasi bin konsisten dengan Store; route model binding mengecualikan
    // diri sendiri saat cek overlap (lihat validateBinConsistency pada parent).
}
