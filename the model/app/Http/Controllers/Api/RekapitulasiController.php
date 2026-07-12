<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilKlasifikasi;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Rekapitulasi data klasifikasi per dusun & laporan PDF.
 *  - admin, approver, superadmin.
 */
class RekapitulasiController extends Controller
{
    /**
     * Statistik per dusun untuk halaman Rekapitulasi Per Dusun.
     */
    public function perDusun(): JsonResponse
    {
        $wargas = Warga::whereNotNull('dusun')->get();
        $dusunList = $wargas->groupBy('dusun');

        $detail = [];
        $totalLayak = 0;
        $totalTidakLayak = 0;
        $totalWarga = 0;

        foreach ($dusunList as $dusun => $items) {
            $ids = $items->pluck('id')->all();
            $hasil = HasilKlasifikasi::whereIn('warga_id', $ids)->get();
            $layak = $hasil->where('prediksi_kelas', 'layak')->count();
            $tidakLayak = $hasil->where('prediksi_kelas', 'tidak_layak')->count();
            $totalWargaDusun = $items->count();
            $avgProb = $hasil->avg('prob_layak') ?? 0;
            $pctLayak = $totalWargaDusun > 0 ? round(($layak / max($totalWargaDusun, 1)) * 100, 1) : 0;

            $detail[] = [
                'dusun' => $dusun,
                'total_warga' => $totalWargaDusun,
                'layak' => $layak,
                'tidak_layak' => $tidakLayak,
                'pct_layak' => $pctLayak,
                'avg_prob_layak' => round($avgProb * 100, 1),
            ];

            $totalLayak += $layak;
            $totalTidakLayak += $tidakLayak;
            $totalWarga += $totalWargaDusun;
        }

        $tertinggi = collect($detail)->sortByDesc('pct_layak')->first();

        return response()->json([
            'total_dusun' => count($detail),
            'total_warga' => $totalWarga,
            'total_layak' => $totalLayak,
            'total_tidak_layak' => $totalTidakLayak,
            'rata_rata_prob_layak' => $totalLayak + $totalTidakLayak > 0
                ? round(($totalLayak / max($totalLayak + $totalTidakLayak, 1)) * 100, 1) : 0,
            'dusun_tertinggi' => $tertinggi ? [
                'dusun' => $tertinggi['dusun'],
                'pct_layak' => $tertinggi['pct_layak'],
            ] : null,
            'detail' => $detail,
        ]);
    }

    /**
     * Export PDF Rekapitulasi Per Dusun.
     */
    public function pdfRekapDusun()
    {
        $data = $this->perDusun()->getData(true);

        $html = view('laporan.rekapitulasi_dusun', [
            'totalDusun' => $data['total_dusun'],
            'totalWarga' => $data['total_warga'],
            'totalLayak' => $data['total_layak'],
            'totalTidakLayak' => $data['total_tidak_layak'],
            'detail' => $data['detail'],
            'periode' => 'Semua',
        ])->render();

        $pdf = app('dompdf.wrapper')->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('rekap_dusun_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export PDF Rincian Warga Per Dusun.
     */
    public function pdfRincianWarga(Request $request)
    {
        $dusunFilter = $request->query('dusun');

        $query = Warga::with('latestHasil')
            ->whereNotNull('dusun')
            ->orderBy('dusun')
            ->orderBy('nama');

        if ($dusunFilter) {
            $query->where('dusun', $dusunFilter);
        }

        $wargas = $query->get();

        $grouped = [];
        $totalLayak = 0;
        $totalTidakLayak = 0;

        foreach ($wargas as $w) {
            $h = $w->latestHasil;
            $kelas = $h?->prediksi_kelas ?? 'belum_diklasifikasi';
            $probLayak = $h ? number_format($h->prob_layak * 100, 1).'%' : '-';
            $statusApproval = $h?->status_approval ?? '-';

            if ($kelas === 'layak') $totalLayak++;
            if ($kelas === 'tidak_layak') $totalTidakLayak++;

            $grouped[$w->dusun][] = [
                'nik' => $w->nik,
                'nama' => $w->nama,
                'prob_layak' => $probLayak,
                'kelas' => $kelas,
                'status_approval' => $statusApproval,
            ];
        }

        $html = view('laporan.rincian_warga', [
            'grouped' => $grouped,
            'totalWarga' => $wargas->count(),
            'totalLayak' => $totalLayak,
            'totalTidakLayak' => $totalTidakLayak,
        ])->render();

        $pdf = app('dompdf.wrapper')->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('rincian_warga_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export PDF Rincian Per KK (satu warga).
     */
    public function pdfRincianKk(Warga $warga)
    {
        $hasil = HasilKlasifikasi::where('warga_id', $warga->id)
            ->latest('id')
            ->first();

        $html = view('laporan.rincian_kk', [
            'warga' => $warga->load('createdBy'),
            'hasil' => $hasil,
        ])->render();

        $pdf = app('dompdf.wrapper')->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('rincian_kk_'.$warga->nik.'_'.now()->format('Ymd_His').'.pdf');
    }
}
