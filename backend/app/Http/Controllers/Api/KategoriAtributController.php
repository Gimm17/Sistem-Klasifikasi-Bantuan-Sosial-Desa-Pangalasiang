<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kategori\StoreKategoriAtributRequest;
use App\Http\Requests\Kategori\UpdateKategoriAtributRequest;
use App\Http\Resources\KategoriAtributResource;
use App\Models\KategoriAtribut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CRUD kategori/bin per atribut — admin & superadmin.
 * Validasi non-overlap untuk bin numerik ada di Store/UpdateKategoriAtributRequest.
 */
class KategoriAtributController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = KategoriAtribut::query()->with('atribut')->orderBy('atribut_klasifikasi_id')->orderBy('urutan');

        if ($atributId = request()->query('atribut_klasifikasi_id')) {
            $query->where('atribut_klasifikasi_id', $atributId);
        }

        return KategoriAtributResource::collection($query->get());
    }

    public function store(StoreKategoriAtributRequest $request): JsonResponse
    {
        $kategori = KategoriAtribut::create($request->validated());

        return (new KategoriAtributResource($kategori->load('atribut')))
            ->additional(['message' => 'Kategori atribut berhasil dibuat.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(KategoriAtribut $kategoriAtribut): KategoriAtributResource
    {
        return new KategoriAtributResource($kategoriAtribut->load('atribut'));
    }

    public function update(UpdateKategoriAtributRequest $request, KategoriAtribut $kategoriAtribut): KategoriAtributResource
    {
        $kategoriAtribut->update($request->validated());

        return (new KategoriAtributResource($kategoriAtribut->fresh('atribut')))
            ->additional(['message' => 'Kategori atribut berhasil diperbarui.']);
    }

    public function destroy(KategoriAtribut $kategoriAtribut): \Illuminate\Http\Response
    {
        $kategoriAtribut->delete();

        return response()->noContent();
    }
}
