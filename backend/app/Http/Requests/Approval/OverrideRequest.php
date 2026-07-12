<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Override: ubah kelas prediksi secara manual. Catatan alasan WAJIB + kelas baru.
 * Kelas asli disimpan controller di breakdown_json (prediksi_asli) utk audit.
 */
class OverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan_approval' => ['required', 'string', 'max:1000'],
            'prediksi_kelas_override' => ['required', Rule::in(['layak', 'tidak_layak'])],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan_approval.required' => 'Catatan alasan wajib diisi saat override.',
            'prediksi_kelas_override.in' => 'Kelas override harus layak atau tidak_layak.',
        ];
    }
}
