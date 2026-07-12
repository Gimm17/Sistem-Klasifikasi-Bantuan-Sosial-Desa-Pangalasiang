<?php

namespace App\Http\Requests\Warga;

use App\Models\KategoriAtribut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi data warga baru.
 *
 * status_pekerjaan & kondisi_rumah harus berisi salah satu label kategori aktif
 * yang didefinisikan admin (modul Fase 3) — bukan teks bebas. Ini agar nilai
 * kategorikal langsung dapat dipakai oleh mesin Naive Bayes tanpa binning ulang.
 *
 * created_by TIDAK divalidasi dari input; diisi otomatis controller dari user login.
 */
class StoreWargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pekerjaanLabels = $this->kategoriLabels('pekerjaan');
        $kondisiLabels = $this->kategoriLabels('kondisi_rumah');

        return [
            'nik' => ['required', 'string', 'digits:16', 'unique:warga,nik'],
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'dusun' => ['nullable', 'string', 'max:100'],
            'penghasilan_bulanan' => ['required', 'integer', 'min:0', 'max:100000000'],
            'status_pekerjaan' => ['required', 'string', Rule::in($pekerjaanLabels)],
            'jumlah_tanggungan' => ['required', 'integer', 'min:0', 'max:20'],
            'kondisi_rumah' => ['required', 'string', Rule::in($kondisiLabels)],
            'periode_data' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.digits' => 'NIK harus tepat 16 digit.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'penghasilan_bulanan.min' => 'Penghasilan minimal 0.',
            'jumlah_tanggungan.max' => 'Jumlah tanggungan tidak masuk akal (maks 20).',
            'status_pekerjaan.in' => 'Status pekerjaan harus salah satu kategori yang tersedia.',
            'kondisi_rumah.in' => 'Kondisi rumah harus salah satu kategori yang tersedia.',
        ];
    }

    /**
     * Ambil label kategori aktif untuk sebuah atribut (by kode).
     */
    protected function kategoriLabels(string $kode): array
    {
        return KategoriAtribut::whereRelation('atribut', function ($q) use ($kode) {
            $q->where('kode', $kode)->where('aktif', true);
        })->orderBy('urutan')->pluck('label')->all();
    }
}
