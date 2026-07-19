<?php

namespace App\Http\Requests\Warga;

use App\Models\KategoriAtribut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi saat menandai warga "divalidasi".
 * Wajib: minimal 1 foto rumah (dicek di controller) + label kondisi_rumah.
 * Label dipilih admin secara manual setelah meninjau foto.
 */
class ValidasiWargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kondisi_rumah' => ['required', 'string', Rule::in($this->kategoriLabels('kondisi_rumah'))],
        ];
    }

    public function messages(): array
    {
        return [
            'kondisi_rumah.required' => 'Label kondisi rumah wajib dipilih saat validasi.',
            'kondisi_rumah.in' => 'Kondisi rumah harus salah satu kategori yang tersedia.',
        ];
    }

    protected function kategoriLabels(string $kode): array
    {
        return KategoriAtribut::whereRelation('atribut', function ($q) use ($kode) {
            $q->where('kode', $kode)->where('aktif', true);
        })->orderBy('urutan')->pluck('label')->all();
    }
}
