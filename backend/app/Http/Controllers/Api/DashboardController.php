<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilKlasifikasi;
use App\Models\ModelVersion;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;

/**
 * Statistik ringkas untuk dashboard.
 *  - admin, approver, superadmin.
 */
class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalWarga = Warga::count();
        $divalidasi = Warga::where('status_validasi', 'divalidasi')->count();

        $hasil = HasilKlasifikasi::whereHas('warga')->whereIn('id', function ($q) {
            // hasil terbaru per warga
            $q->from('hasil_klasifikasi')
                ->selectRaw('MAX(id)')
                ->groupBy('warga_id');
        })->get();

        $totalLayak = $hasil->where('prediksi_kelas', 'layak')->count();
        $totalTidak = $hasil->where('prediksi_kelas', 'tidak_layak')->count();
        $pending = $hasil->where('status_approval', 'pending')->count();

        $modelAktif = ModelVersion::where('is_active', true)->latest('id')->first();

        // Hitung atribut paling berpengaruh dari model aktif (Likelihood divergence)
        $atributBerpengaruh = [];
        if ($modelAktif) {
            $probs = \App\Models\ModelProbabilitas::where('model_version', $modelAktif->model_version)
                ->whereNotNull('atribut_kode')
                ->get();
            
            $grouped = $probs->groupBy('atribut_kode');
            foreach ($grouped as $kode => $items) {
                $cats = $items->groupBy('kategori');
                $totalDiff = 0;
                foreach ($cats as $cat => $catItems) {
                    $layak = $catItems->where('kelas', 'layak')->first()->likelihood ?? 0;
                    $tidak = $catItems->where('kelas', 'tidak_layak')->first()->likelihood ?? 0;
                    $totalDiff += abs($layak - $tidak);
                }
                $atributBerpengaruh[] = [
                    'atribut' => $kode,
                    'label' => ucwords(str_replace('_', ' ', $kode)),
                    'score' => round(($totalDiff / 2) * 100, 1),
                ];
            }
            usort($atributBerpengaruh, fn($a, $b) => $b['score'] <=> $a['score']);
        }

        $evaluasiTerakhir = \App\Models\ModelEvaluasi::latest('id')->first();

        $pendingTerbaru = HasilKlasifikasi::whereHas('warga')
            ->with('warga')
            ->where('status_approval', 'pending')
            ->latest('id')
            ->take(5)
            ->get()
            ->map(function ($h) {
                return [
                    'id' => $h->id,
                    'warga_nama' => $h->warga->nama ?? '-',
                    'warga_nik' => $h->warga->nik ?? '-',
                    'prob_layak' => $h->prob_layak,
                    'prediksi_kelas' => $h->prediksi_kelas,
                    'created_at' => $h->created_at,
                ];
            });

        return response()->json([
            'total_warga' => $totalWarga,
            'warga_divalidasi' => $divalidasi,
            'total_layak' => $totalLayak,
            'total_tidak_layak' => $totalTidak,
            'pending_approval' => $pending,
            'distribusi_kelas' => [
                'layak' => $totalLayak,
                'tidak_layak' => $totalTidak,
            ],
            'distribusi_approval' => [
                'pending' => $hasil->where('status_approval', 'pending')->count(),
                'approved' => $hasil->where('status_approval', 'approved')->count(),
                'rejected' => $hasil->where('status_approval', 'rejected')->count(),
                'overridden' => $hasil->where('status_approval', 'overridden')->count(),
            ],
            'model_aktif' => $modelAktif ? [
                'model_version' => $modelAktif->model_version,
                'total_data' => $modelAktif->total_data,
                'count_layak' => $modelAktif->count_layak,
                'count_tidak_layak' => $modelAktif->count_tidak_layak,
                'created_at' => $modelAktif->created_at,
            ] : null,
            'total_data_training' => \App\Models\DataTraining::count(),
            'atribut_berpengaruh' => $atributBerpengaruh,
            'evaluasi_terakhir' => $evaluasiTerakhir ? [
                'accuracy' => $evaluasiTerakhir->accuracy,
                'precision' => $evaluasiTerakhir->precision,
                'recall' => $evaluasiTerakhir->recall,
                'f1_score' => $evaluasiTerakhir->f1_score,
                'created_at' => $evaluasiTerakhir->created_at,
            ] : null,
            'pending_terbaru' => $pendingTerbaru,
        ]);
    }
}
