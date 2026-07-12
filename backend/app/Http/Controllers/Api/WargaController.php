<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warga\StoreWargaRequest;
use App\Http\Requests\Warga\UpdateWargaRequest;
use App\Http\Resources\WargaResource;
use App\Models\ActivityLog;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CRUD master data warga.
 *  - admin: full CRUD + validasi
 *  - approver: lihat (index/show)
 *  Pembatasan per peran di-gate oleh middleware 'role' pada route.
 */
class WargaController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = Warga::query()->with('createdBy');

        // Filter: nama, nik, status_validasi.
        if ($q = $this->search('nama')) {
            $query->where('nama', 'like', "%{$q}%");
        }
        if ($q = $this->search('nik')) {
            $query->where('nik', 'like', "%{$q}%");
        }
        if ($q = $this->search('status_validasi')) {
            $query->where('status_validasi', $q);
        }

        return WargaResource::collection(
            $query->orderByDesc('id')->paginate(15)->withQueryString()
        );
    }

    public function store(StoreWargaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $warga = Warga::create($data);
        ActivityLog::record('warga.create', $warga, "Tambah warga {$warga->nama}", [], $request->user());

        return (new WargaResource($warga->load('createdBy')))
            ->additional(['message' => 'Data warga berhasil ditambahkan.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Warga $warga): WargaResource
    {
        // sertakan histori hasil klasifikasi (jika ada).
        return new WargaResource($warga->load(['createdBy', 'hasilKlasifikasi']));
    }

    public function update(UpdateWargaRequest $request, Warga $warga): WargaResource
    {
        $warga->update($request->validated());
        ActivityLog::record('warga.update', $warga, "Perbarui warga {$warga->nama}", [], $request->user());

        return (new WargaResource($warga->fresh('createdBy')))
            ->additional(['message' => 'Data warga berhasil diperbarui.']);
    }

    public function destroy(Warga $warga): \Illuminate\Http\Response
    {
        ActivityLog::record('warga.delete', $warga, "Hapus warga {$warga->nama}", [], request()->user());
        $warga->delete(); // soft delete

        return response()->noContent();
    }

    /**
     * Tandai data sebagai "divalidasi" (Batasan Masalah #3: data tervalidasi perangkat desa).
     */
    public function validasi(\Illuminate\Http\Request $request, Warga $warga): WargaResource
    {
        $warga->update(['status_validasi' => 'divalidasi']);
        ActivityLog::record('warga.validasi', $warga, "Validasi warga {$warga->nama}", [], $request->user());

        return (new WargaResource($warga))
            ->additional(['message' => 'Data warga divalidasi.']);
    }

    private function search(string $key): ?string
    {
        $value = request()->query($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
