<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilKlasifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Laporan hasil klasifikasi — CSV & PDF.
 *
 * Catatan: fitur TAMBAHAN (modul "Laporan" nice-to-have, docs/list_feature.md §9)
 * untuk keperluan administrasi desa. Hasil = rekomendasi; keputusan final manusia.
 */
class LaporanController extends Controller
{
    /** Query dasar hasil klasifikasi (boleh difilter periode & status approval). */
    protected function query(Request $request)
    {
        $q = HasilKlasifikasi::with(['warga', 'approver'])->orderByDesc('id');

        if ($periode = $request->query('periode')) {
            if ($periode === 'bulan_ini') {
                $q->whereHas('warga', fn ($w) => $w->whereMonth('periode_data', now()->month)->whereYear('periode_data', now()->year));
            } elseif ($periode === '3_bulan') {
                $q->whereHas('warga', fn ($w) => $w->where('periode_data', '>=', now()->subMonths(3)));
            } elseif ($periode === 'tahun_ini') {
                $q->whereHas('warga', fn ($w) => $w->whereYear('periode_data', now()->year));
            } elseif ($periode !== 'semua' && $periode !== '') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $periode)) {
                    $q->whereHas('warga', fn ($w) => $w->where('periode_data', $periode));
                } elseif (preg_match('/^\d{4}-\d{2}$/', $periode)) {
                    $q->whereHas('warga', fn ($w) => $w->where('periode_data', 'like', $periode . '%'));
                }
            }
        }
        if ($status = $request->query('status_approval')) {
            $q->where('status_approval', $status);
        }

        return $q->get();
    }

    /** Export hasil klasifikasi ke CSV. */
    public function csv(Request $request)
    {
        $hasil = $this->query($request);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['nik', 'nama', 'model_version', 'prob_layak', 'prob_tidak_layak', 'prediksi_kelas', 'status_approval', 'approver', 'catatan', 'created_at']);

        foreach ($hasil as $h) {
            fputcsv($handle, [
                $h->warga?->nik, $h->warga?->nama, $h->model_version,
                $h->prob_layak, $h->prob_tidak_layak, $h->prediksi_kelas,
                $h->status_approval, $h->approver?->name, $h->catatan_approval,
                $h->created_at,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::make("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_klasifikasi_'.now()->format('Ymd_His').'.csv"',
        ]);
    }

    /** Cetak laporan hasil klasifikasi ke PDF. */
    public function pdf(Request $request)
    {
        $hasil = $this->query($request);

        $html = view('laporan.klasifikasi', [
            'hasil' => $hasil,
            'periode' => $request->query('periode'),
            'statusFilter' => $request->query('status_approval'),
            'dicetak' => now(),
        ])->render();

        $pdf = app('dompdf.wrapper')->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'laporan_klasifikasi_'.now()->format('Ymd_His').'.pdf';
        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
