<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Model\TrainRequest;
use App\Http\Resources\ModelVersionResource;
use App\Models\ModelVersion;
use App\Services\NaiveBayes\NaiveBayesService;
use App\Services\NaiveBayes\ProbabilityTableBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manajemen model Naive Bayes: training & daftar versi + analisis atribut.
 *  - admin & superadmin (train, versions).
 *  - admin, approver, superadmin (atribut-importance).
 */
class ModelController extends Controller
{
    public function __construct(
        protected ProbabilityTableBuilder $builder,
        protected NaiveBayesService $nb,
    ) {}

    /**
     * Training model baru dari seluruh data_training berlabel.
     * vocab_source: 'observed' (default) atau 'defined'.
     */
    public function train(TrainRequest $request): JsonResponse
    {
        $modelVersion = 'v'.now()->format('Ymd-His');
        $vocabSource = $request->input('vocab_source', 'observed');

        try {
            $version = $this->builder->build($modelVersion, $request->user()->id, true, $vocabSource);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        \App\Models\ActivityLog::record('model.train', $version, "Training model {$version->model_version} (vocab={$vocabSource})", [], $request->user());

        return (new ModelVersionResource($version->load('trainedBy')))
            ->additional(['message' => 'Model berhasil di-training.'])
            ->response()
            ->setStatusCode(201);
    }

    public function versions(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return ModelVersionResource::collection(
            ModelVersion::with('trainedBy')->latest('id')->get()
        );
    }

    /**
     * Analisis pengaruh tiap atribut (menjawab tujuan riset #2).
     */
    public function atributImportance(): JsonResponse
    {
        $mv = $this->nb->activeModelVersion();
        if (! $mv) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $this->nb->atributImportance($mv),
        ]);
    }
}
