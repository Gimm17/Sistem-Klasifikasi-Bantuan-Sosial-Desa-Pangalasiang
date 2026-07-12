<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reject hasil klasifikasi — catatan alasan WAJIB (audit).
 */
class RejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan_approval' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan_approval.required' => 'Catatan alasan wajib diisi saat menolak.',
        ];
    }
}
