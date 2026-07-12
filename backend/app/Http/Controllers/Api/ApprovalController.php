<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\OverrideRequest;
use App\Http\Requests\Approval\RejectRequest;
use App\Http\Resources\HasilKlasifikasiResource;
use App\Models\ActivityLog;
use App\Models\HasilKlasifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Antrean approval hasil klasifikasi — HANYA role approver (kades/sekdes).
 * Hasil klasifikasi = rekomendasi; keputusan akhir tetap manusia (Batasan Masalah #5).
 *
 * Aksi: approve | reject (catatan wajib) | override (kelas manual + catatan wajib).
 * Hanya item berstatus `pending` yang dapat diproses.
 *
 * Catatan: nama variabel $hasilKlasifikasi HARUS cocok dgn param route {hasilKlasifikasi}
 * agar implicit route model binding berjalan.
 */
class ApprovalController extends Controller
{
    public function queue(Request $request): AnonymousResourceCollection
    {
        $query = HasilKlasifikasi::where('status_approval', 'pending')
            ->with(['warga', 'approver']);

        if ($s = $request->query('prediksi_kelas')) {
            $query->where('prediksi_kelas', $s);
        }

        return HasilKlasifikasiResource::collection(
            $query->orderBy('id')->paginate(15)->withQueryString()
        );
    }

    public function approve(Request $request, HasilKlasifikasi $hasilKlasifikasi): HasilKlasifikasiResource
    {
        $this->ensurePending($hasilKlasifikasi);

        $hasilKlasifikasi->update([
            'status_approval' => 'approved',
            'approved_by' => $request->user()->id,
            'catatan_approval' => $request->input('catatan_approval'),
        ]);
        ActivityLog::record('approval.approve', $hasilKlasifikasi, "Setujui hasil {$hasilKlasifikasi->warga?->nama}", [], $request->user());

        return (new HasilKlasifikasiResource($hasilKlasifikasi->load(['warga', 'approver'])))
            ->additional(['message' => 'Hasil klasifikasi disetujui.']);
    }

    public function reject(RejectRequest $request, HasilKlasifikasi $hasilKlasifikasi): HasilKlasifikasiResource
    {
        $this->ensurePending($hasilKlasifikasi);

        $hasilKlasifikasi->update([
            'status_approval' => 'rejected',
            'approved_by' => $request->user()->id,
            'catatan_approval' => $request->input('catatan_approval'),
        ]);
        ActivityLog::record('approval.reject', $hasilKlasifikasi, "Tolak hasil {$hasilKlasifikasi->warga?->nama}", [], $request->user());

        return (new HasilKlasifikasiResource($hasilKlasifikasi->load(['warga', 'approver'])))
            ->additional(['message' => 'Hasil klasifikasi ditolak.']);
    }

    public function override(OverrideRequest $request, HasilKlasifikasi $hasilKlasifikasi): HasilKlasifikasiResource
    {
        $this->ensurePending($hasilKlasifikasi);

        // simpan kelas asli + metadata override di breakdown (audit trail).
        $breakdown = $hasilKlasifikasi->breakdown_json;
        $breakdown['prediksi_asli'] = $hasilKlasifikasi->prediksi_kelas;
        $breakdown['override'] = [
            'user_id' => $request->user()->id,
            'name' => $request->user()->name,
            'alasan' => $request->input('catatan_approval'),
        ];

        $hasilKlasifikasi->update([
            'status_approval' => 'overridden',
            'prediksi_kelas' => $request->input('prediksi_kelas_override'),
            'approved_by' => $request->user()->id,
            'catatan_approval' => $request->input('catatan_approval'),
            'breakdown_json' => $breakdown,
        ]);
        ActivityLog::record('approval.override', $hasilKlasifikasi, "Override hasil {$hasilKlasifikasi->warga?->nama} → {$request->input('prediksi_kelas_override')}", [], $request->user());

        return (new HasilKlasifikasiResource($hasilKlasifikasi->load(['warga', 'approver'])))
            ->additional(['message' => 'Hasil klasifikasi di-override.']);
    }

    /**
     * Tolak aksi ganda: item sudah diproses tidak bisa diproses ulang.
     */
    protected function ensurePending(HasilKlasifikasi $hasilKlasifikasi): void
    {
        if ($hasilKlasifikasi->status_approval !== 'pending') {
            abort(422, 'Item ini sudah diproses (status: '.$hasilKlasifikasi->status_approval.').');
        }
    }
}
