<?php

namespace App\Http\Requests\Model;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi request training model.
 *  - vocab_source: sumber jumlahKategoriUnik utk Laplace. 'observed' (default) atau
 *    'defined' (semua kategori yg didefinisikan admin).
 */
class TrainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vocab_source' => ['nullable', Rule::in(['observed', 'defined'])],
        ];
    }
}
