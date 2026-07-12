<?php

namespace App\Http\Requests\Kategori;

use App\Models\AtributKlasifikasi;
use App\Models\KategoriAtribut;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi kategori/bin atribut.
 *
 * Aturan bisnis (docs Fase 3): bin numerik TIDAK boleh saling overlap.
 * Overlap bin menyebabkan kategorisasi ambigu — ditolak di sini.
 * (Cek "lubang"/gap didelegasikan ke AttributeCategorizer yang melempar error
 *  jelas bila suatu nilai tidak masuk bin manapun — keputusan terdokumentasi.)
 */
class StoreKategoriAtributRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'atribut_klasifikasi_id' => ['required', 'exists:atribut_klasifikasi,id'],
            'label' => ['required', 'string', 'max:100'],
            'batas_bawah' => ['nullable', 'numeric'],
            'batas_atas' => ['nullable', 'numeric'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(fn ($v) => $this->validateBinConsistency($v));
    }

    protected function validateBinConsistency($validator): void
    {
        $atribut = AtributKlasifikasi::find($this->atribut_klasifikasi_id);
        if (! $atribut) {
            return;
        }

        // Atribut kategorikal: batas_* tidak relevan.
        if ($atribut->tipe !== 'numerik') {
            if ($this->filled('batas_bawah') || $this->filled('batas_atas')) {
                $validator->errors()->add('batas_bawah', 'Atribut kategorikal tidak memakai batas_bawah/batas_atas.');
            }

            return;
        }

        $bawah = $this->filled('batas_bawah') ? (float) $this->batas_bawah : -INF;
        $atas = $this->filled('batas_atas') ? (float) $this->batas_atas : INF;

        if ($bawah > $atas) {
            $validator->errors()->add('batas_bawah', 'batas_bawah tidak boleh lebih besar dari batas_atas.');

            return;
        }

        // Cek overlap dengan bin lain pada atribut yang sama.
        $existing = KategoriAtribut::where('atribut_klasifikasi_id', $atribut->id);
        if ($this->route('kategori_atribut')) {
            $existing->where('id', '!=', $this->route('kategori_atribut')->id);
        }

        foreach ($existing->get() as $bin) {
            $eBawah = $bin->batas_bawah !== null ? (float) $bin->batas_bawah : -INF;
            $eAtas = $bin->batas_atas !== null ? (float) $bin->batas_atas : INF;

            // Dua interval [a,b] & [c,d] overlap iff a<=d && c<=b.
            if ($bawah <= $eAtas && $eBawah <= $atas) {
                $validator->errors()->add('batas_atas', 'Rentang tumpang tindih dengan kategori "'.$bin->label.'". Bin numerik tidak boleh overlap.');

                return;
            }
        }
    }
}
