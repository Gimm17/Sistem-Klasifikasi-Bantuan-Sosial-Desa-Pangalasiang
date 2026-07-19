<?php

namespace App\Http\Requests\Warga;

use App\Models\KategoriAtribut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $wargaId = $this->route('warga')->id ?? $this->route('warga');
        $pekerjaanLabels = $this->kategoriLabels('pekerjaan');
        $kondisiLabels = $this->kategoriLabels('kondisi_rumah');

        return [
            // unique HANYA cek baris yang belum di-soft-delete (lihat catatan StoreWargaRequest).
            'nik' => ['required', 'string', 'digits:16', Rule::unique('warga', 'nik')->ignore($wargaId)->whereNull('deleted_at')],
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'dusun' => ['nullable', 'string', 'max:100'],
            'penghasilan_bulanan' => ['required', 'integer', 'min:0', 'max:100000000'],
            'status_pekerjaan' => ['required', 'string', Rule::in($pekerjaanLabels)],
            'jumlah_tanggungan' => ['required', 'integer', 'min:0', 'max:20'],
            // kondisi_rumah nullable di draft — diisi saat validasi (setelah foto diunggah).
            // Admin boleh ubah label kapan saja (termasuk setelah divalidasi), tapi harus tetap
            // salah satu kategori aktif bila diisi.
            'kondisi_rumah' => ['nullable', 'string', Rule::in($kondisiLabels)],
            'periode_data' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.digits' => 'NIK harus tepat 16 digit.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'status_pekerjaan.in' => 'Status pekerjaan harus salah satu kategori yang tersedia.',
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
