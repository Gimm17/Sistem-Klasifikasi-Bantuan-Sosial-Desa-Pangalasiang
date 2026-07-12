<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTraining\StoreDataTrainingRequest;
use App\Http\Requests\DataTraining\UpdateDataTrainingRequest;
use App\Http\Resources\DataTrainingResource;
use App\Models\DataTraining;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CRUD data latih berlabel (modul MVP #4).
 *  - admin & superadmin: kelola.
 *  Data ini menjadi dasar training ProbabilityTableBuilder (Fase 4).
 */
class DataTrainingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = DataTraining::query();

        if ($s = $request->query('label_kelas')) {
            $query->where('label_kelas', $s);
        }

        return DataTrainingResource::collection(
            $query->orderByDesc('id')->paginate(20)->withQueryString()
        );
    }

    public function store(StoreDataTrainingRequest $request): JsonResponse
    {
        $dt = DataTraining::create($request->validated());

        return (new DataTrainingResource($dt))
            ->additional(['message' => 'Data training berhasil ditambahkan.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(DataTraining $dataTraining): DataTrainingResource
    {
        return new DataTrainingResource($dataTraining);
    }

    public function update(UpdateDataTrainingRequest $request, DataTraining $dataTraining): DataTrainingResource
    {
        $dataTraining->update($request->validated());

        return (new DataTrainingResource($dataTraining))
            ->additional(['message' => 'Data training berhasil diperbarui.']);
    }

    public function destroy(DataTraining $dataTraining): \Illuminate\Http\Response
    {
        $dataTraining->delete();

        return response()->noContent();
    }
}
