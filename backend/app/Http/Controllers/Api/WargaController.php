<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warga\StoreWargaRequest;
use App\Http\Requests\Warga\UpdateWargaRequest;
use App\Http\Requests\Warga\ValidasiWargaRequest;
use App\Http\Resources\WargaResource;
use App\Models\ActivityLog;
use App\Models\Warga;
use App\Models\WargaFoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * CRUD master data warga.
 *  - admin: full CRUD + validasi + upload/hapus foto rumah
 *  - approver: lihat (index/show)
 *  Pembatasan per peran di-gate oleh middleware 'role' pada route.
 *
 * Galeri foto rumah: dokumentasi kondisi rumah. Maks 5 foto/warga, 2MB/foto.
 * Foto WAJIB (≥1) sebelum warga bisa divalidasi. Label kondisi_rumah diisi saat
 * validasi oleh admin setelah meninjau foto.
 */
class WargaController extends Controller
{
    /** Maks foto per warga. */
    public const MAX_FOTOS = 5;

    public function index(): AnonymousResourceCollection
    {
        $query = Warga::query()->with('createdBy')->withCount('fotos');

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
        // refresh() agar default DB (status_validasi='draft') terhidrasi ke model,
        // sehingga resource mengembalikan nilai benar (bukan null).
        $warga->refresh();
        ActivityLog::record('warga.create', $warga, "Tambah warga {$warga->nama}", [], $request->user());

        return (new WargaResource($warga->load(['createdBy', 'fotos'])))
            ->additional(['message' => 'Data warga berhasil ditambahkan.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Warga $warga): WargaResource
    {
        // sertakan galeri foto + histori hasil klasifikasi (jika ada).
        return new WargaResource($warga->load(['createdBy', 'hasilKlasifikasi', 'fotos']));
    }

    public function update(UpdateWargaRequest $request, Warga $warga): WargaResource
    {
        $warga->update($request->validated());
        ActivityLog::record('warga.update', $warga, "Perbarui warga {$warga->nama}", [], $request->user());

        return (new WargaResource($warga->fresh(['createdBy', 'fotos'])))
            ->additional(['message' => 'Data warga berhasil diperbarui.']);
    }

    public function destroy(Warga $warga): \Illuminate\Http\Response
    {
        // Hapus file foto fisik sebelum soft-delete warga.
        foreach ($warga->fotos as $foto) {
            Storage::disk('public')->delete($foto->path);
        }

        // Null-out NIK sebelum soft-delete agar NIK bisa dipakai ulang.
        // Lihat migrations/2026_07_19_000030_make_nik_nullable_on_warga.
        // Baris di-soft-delete tapi NIK tetap konflik dengan UNIQUE index MySQL/SQLite
        // jika tidak di-null. NULL bypass unique index (uniqueness tidak berlaku untuk NULL).
        ActivityLog::record('warga.delete', $warga, "Hapus warga {$warga->nama}", [], request()->user());
        $warga->nik = null; // lepas NIK agar eligible dipakai ulang
        $warga->save();
        $warga->delete(); // soft delete (set deleted_at)

        return response()->noContent();
    }

    /**
     * Tandai data sebagai "divalidasi" (Batasan Masalah #3: data tervalidasi perangkat desa).
     * Wajib: ≥1 foto rumah + label kondisi_rumah (dipilih admin setelah tinjau foto).
     *
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\WargaResource
     */
    public function validasi(ValidasiWargaRequest $request, Warga $warga)
    {
        if ($warga->fotos()->count() < 1) {
            return response()->json([
                'message' => 'Minimal 1 foto rumah wajib diunggah sebelum validasi.',
                'errors' => ['fotos' => ['Unggah minimal 1 foto rumah terlebih dahulu.']],
            ], 422);
        }

        $warga->update([
            'status_validasi' => 'divalidasi',
            'kondisi_rumah' => $request->validated('kondisi_rumah'),
        ]);
        ActivityLog::record('warga.validasi', $warga, "Validasi warga {$warga->nama}", [], $request->user());

        return (new WargaResource($warga->load('fotos')))
            ->additional(['message' => 'Data warga divalidasi.']);
    }

    // -----------------------------------------------------------------------
    // Galeri foto rumah
    // -----------------------------------------------------------------------

    /** GET /api/warga/{warga}/fotos */
    public function listFotos(Warga $warga): JsonResponse
    {
        return response()->json([
            'data' => $warga->fotos->map(fn ($f) => [
                'id' => $f->id,
                'url' => $f->url,
                'urutan' => $f->urutan,
                'original_name' => $f->original_name,
                'size_bytes' => $f->size_bytes,
            ]),
        ]);
    }

    /**
     * POST /api/warga/{warga}/fotos  (multipart: fotos[] | foto)
     * Maks self::MAX_FOTOS foto per warga; masing-masing image|max:2048.
     */
    public function storeFotos(\Illuminate\Http\Request $request, Warga $warga): JsonResponse
    {
        $request->validate([
            'fotos' => ['required', 'array', 'min:1'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $existing = $warga->fotos()->count();
        $incoming = count($request->file('fotos'));
        if ($existing + $incoming > self::MAX_FOTOS) {
            return response()->json([
                'message' => 'Maksimal '.self::MAX_FOTOS.' foto per warga. Saat ini sudah '.$existing.' foto.',
                'errors' => ['fotos' => ['Melebihi batas maksimal '.self::MAX_FOTOS.' foto.']],
            ], 422);
        }

        $nextUrutan = $warga->fotos()->max('urutan') ?? -1;
        $created = [];
        foreach ($request->file('fotos') as $file) {
            $path = $file->store("warga/fotos/{$warga->id}", 'public');
            $nextUrutan++;
            $created[] = $warga->fotos()->create([
                'path' => $path,
                'urutan' => $nextUrutan,
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
            ]);
        }

        ActivityLog::record('warga.foto.upload', $warga, "Unggah {$incoming} foto rumah untuk {$warga->nama}", [], $request->user());

        return response()->json([
            'message' => count($created).' foto berhasil diunggah.',
            'data' => collect($created)->map(fn ($f) => [
                'id' => $f->id,
                'url' => $f->url,
                'urutan' => $f->urutan,
                'original_name' => $f->original_name,
                'size_bytes' => $f->size_bytes,
            ]),
        ], 201);
    }

    /** DELETE /api/warga/{warga}/fotos/{foto}
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function destroyFoto(Warga $warga, WargaFoto $foto)
    {
        if ($foto->warga_id !== $warga->id) {
            abort(404);
        }

        // Cegah hapus foto terakhir pada data yang sudah divalidasi (jaga integritas bukti).
        if ($warga->status_validasi === 'divalidasi' && $warga->fotos()->count() <= 1) {
            return response()->json([
                'message' => 'Tidak boleh menghapus foto terakhir pada data yang sudah divalidasi.',
                'errors' => ['fotos' => ['Data tervalidasi wajib menyimpan minimal 1 foto.']],
            ], 422);
        }

        Storage::disk('public')->delete($foto->path);
        $foto->delete();
        ActivityLog::record('warga.foto.delete', $warga, "Hapus foto rumah untuk {$warga->nama}", [], request()->user());

        return response()->noContent();
    }

    private function search(string $key): ?string
    {
        $value = request()->query($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
