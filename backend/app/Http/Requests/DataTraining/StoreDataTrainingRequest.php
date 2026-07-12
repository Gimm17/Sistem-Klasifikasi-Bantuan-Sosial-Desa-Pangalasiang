<?php

namespace App\Http\Requests\DataTraining;

use App\Models\KategoriAtribut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi data latih berlabel. Tiap *_kategori harus merupakan label kategori
 * aktif yang didefinisikan untuk atribut bersangkutan (modul Fase 3).
 */
class StoreDataTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warga_id' => ['nullable', 'exists:warga,id'],
            'penghasilan_kategori' => ['required', 'string', Rule::in($this->labels('penghasilan'))],
            'pekerjaan_kategori' => ['required', 'string', Rule::in($this->labels('pekerjaan'))],
            'tanggungan_kategori' => ['required', 'string', Rule::in($this->labels('tanggungan'))],
            'kondisi_rumah_kategori' => ['required', 'string', Rule::in($this->labels('kondisi_rumah'))],
            'label_kelas' => ['required', Rule::in(['layak', 'tidak_layak'])],
        ];
    }

    public function messages(): array
    {
        return [
            '*.in' => 'Nilai :attribute bukan kategori yang valid untuk atribut tersebut.',
        ];
    }

    protected function labels(string $kode): array
    {
        return KategoriAtribut::whereRelation('atribut', fn ($q) => $q->where('kode', $kode))
            ->orderBy('urutan')
            ->pluck('label')
            ->all();
    }
}
