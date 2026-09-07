<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilKlasifikasi;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Rekapitulasi per dusun & export PDF (Rekap Dusun, Rincian Warga, Rincian KK).
 */
class RekapitulasiController extends Controller
{
    /**
     * GET /api/rekapitulasi/dusun
     */
    public function perDusun(): JsonResponse
    {
        $dusunList = ['Dusun I', 'Dusun II', 'Dusun III', 'Dusun IV', 'Dusun V'];
        
        $wargaAll = Warga::with('latestHasil')->get();
        
        $totalWarga = $wargaAll->count();
        $totalLayak = 0;
        $totalTidakLayak = 0;
        $probSum = 0;
        $probCount = 0;
        
        $detail = [];
        $dusunTertinggi = null;
        $maxPct = -1;
        
        foreach ($dusunList as $dusun) {
            $wargaDusun = $wargaAll->filter(fn ($w) => ($w->dusun ?: 'Dusun I') === $dusun);
            $countDusun = $wargaDusun->count();
            
            $layak = 0;
            $tidakLayak = 0;
            $dusunProbSum = 0;
            $dusunProbCount = 0;
            
            foreach ($wargaDusun as $w) {
                $hasil = $w->latestHasil;
                if ($hasil) {
                    if ($hasil->prediksi_kelas === 'layak') {
                        $layak++;
                        $totalLayak++;
                    } else {
                        $tidakLayak++;
                        $totalTidakLayak++;
                    }
                    $probSum += $hasil->prob_layak;
                    $probCount++;
                    $dusunProbSum += $hasil->prob_layak;
                    $dusunProbCount++;
                } else {
                    // Jika belum diklasifikasi, default ke layak untuk simulasi jika diperlukan atau biarkan 0
                }
            }
            
            $pctLayak = $countDusun > 0 ? round(($layak / $countDusun) * 100, 1) : 0;
            $avgProb = $dusunProbCount > 0 ? round(($dusunProbSum / $dusunProbCount) * 100, 1) : 0;
            
            if ($pctLayak > $maxPct && $countDusun > 0) {
                $maxPct = $pctLayak;
                $dusunTertinggi = [
                    'dusun' => $dusun,
                    'pct_layak' => $pctLayak,
                ];
            }
            
            $detail[] = [
                'dusun' => $dusun,
                'total_warga' => $countDusun,
                'layak' => $layak,
                'tidak_layak' => $tidakLayak,
                'pct_layak' => $pctLayak,
                'avg_prob_layak' => $avgProb,
            ];
        }
        
        $rataRataProb = $probCount > 0 ? round(($probSum / $probCount) * 100, 1) : 0;
        
        return response()->json([
            'total_dusun' => count($dusunList),
            'total_warga' => $totalWarga,
            'total_layak' => $totalLayak,
            'total_tidak_layak' => $totalTidakLayak,
            'rata_rata_prob_layak' => $rataRataProb,
            'dusun_tertinggi' => $dusunTertinggi ?: ['dusun' => 'Dusun I', 'pct_layak' => 0],
            'detail' => $detail,
        ]);
    }

    /**
     * GET /api/rekapitulasi/dusun.pdf
     */
    public function pdfRekapDusun(Request $request)
    {
        $res = $this->perDusun()->getData(true);
        
        $html = view('laporan.rekapitulasi_dusun', [
            'data' => $res,
            'dicetak' => now(),
        ])->render();

        $pdf = app('dompdf.wrapper')->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'laporan_rekapitulasi_dusun_'.now()->format('Ymd_His').'.pdf';
        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    /**
     * GET /api/laporan/rincian-warga.pdf?dusun=Dusun%20III
     */
    public function pdfRincianWarga(Request $request)
    {
        $dusunFilter = $request->query('dusun');
        $query = Warga::with('latestHasil')->orderBy('dusun')->orderBy('nama');
        
        if ($dusunFilter && $dusunFilter !== 'Semua') {
            $query->where('dusun', $dusunFilter);
        }
        
        $wargaList = $query->get();
        $grouped = $wargaList->groupBy(fn ($w) => $w->dusun ?: 'Dusun I');
        
        $html = view('laporan.rincian_warga', [
            'grouped' => $grouped,
            'dusunFilter' => $dusunFilter,
            'dicetak' => now(),
        ])->render();

        $pdf = app('dompdf.wrapper')->loadHtml($html);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'laporan_rincian_warga_'.now()->format('Ymd_His').'.pdf';
        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    /**
     * GET /api/laporan/rincian-kk/{warga}.pdf
     */
    public function pdfRincianKk(Request $request, Warga $warga)
    {
        $warga->load('latestHasil.approver', 'createdBy');

        // Opsi sertakan foto rumah (?include_foto=1). Embed base64 agar dompdf
        // tidak perlu isRemoteEnabled (paling andal di shared host).
        $includeFoto = $request->boolean('include_foto');
        $fotosBase64 = [];
        if ($includeFoto) {
            $warga->load('fotos');
            foreach ($warga->fotos as $foto) {
                $abs = Storage::disk('public')->path($foto->path);
                if (! is_file($abs)) {
                    continue;
                }
                $data = file_get_contents($abs);
                $ext = strtolower(pathinfo($foto->path, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    default => 'image/jpeg',
                };
                $fotosBase64[] = 'data:'.$mime.';base64,'.base64_encode($data);
            }
        }

        $html = view('laporan.rincian_kk', [
            'warga' => $warga,
            'hasil' => $warga->latestHasil,
            'dicetak' => now(),
            'fotos' => $fotosBase64,
        ])->render();

        $pdf = app('dompdf.wrapper')->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'laporan_rincian_kk_'.$warga->nik.'_'.now()->format('Ymd_His').'.pdf';
        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
