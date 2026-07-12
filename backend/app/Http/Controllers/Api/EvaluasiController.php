<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModelEvaluasiResource;
use App\Models\ModelEvaluasi;
use App\Services\NaiveBayes\ModelEvaluator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Evaluasi akurasi model Naive Bayes (train/test split).
 *  - run: admin & superadmin.
 *  - index (riwayat): admin, approver, superadmin.
 */
class EvaluasiController extends Controller
{
    public function __construct(
        protected ModelEvaluator $evaluator
    ) {}

    public function run(Request $request): ModelEvaluasiResource
    {
        $data = $request->validate([
            'metode' => ['nullable', Rule::in(['holdout', 'kfold'])],
            'test_ratio' => ['nullable', 'numeric', 'min:0.05', 'max:0.5'],
            'k' => ['nullable', 'integer', 'min:2', 'max:20'],
        ]);

        $opts = ['metode' => $data['metode'] ?? 'holdout'];
        if ($opts['metode'] === 'holdout') {
            $opts['test_ratio'] = (float) ($data['test_ratio'] ?? 0.2);
        } else {
            $opts['k'] = (int) ($data['k'] ?? 5);
        }

        $evaluasi = $this->evaluator->run($opts);

        return (new ModelEvaluasiResource($evaluasi))
            ->additional(['message' => 'Evaluasi selesai.']);
    }

    public function index(): AnonymousResourceCollection
    {
        return ModelEvaluasiResource::collection(
            ModelEvaluasi::orderByDesc('id')->paginate(15)
        );
    }
}
