<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HasilKlasifikasiResource;
use App\Models\HasilKlasifikasi;
use App\Models\Warga;
use App\Services\NaiveBayes\NaiveBayesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Klasifikasi kelayakan warga memakai Naive Bayes.
 *  - admin: trigger prediksi (single/batch).
 *  - admin & approver: lihat hasil + breakdown.
 *
 * Hasil = REKOMENDASI (status pending) -> masuk antrean approval (Fase 5).
 */
class KlasifikasiController extends Controller
{
    public function __construct(
        protected NaiveBayesService $nb
    ) {}

    /**
     * Prediksi satu warga. Wajib: warga divalidasi & ada model aktif.
     */
    public function predictSingle(Request $request, Warga $warga): JsonResponse
    {
        if ($warga->status_validasi !== 'divalidasi') {
            return response()->json([
                'message' => 'Data warga belum divalidasi. Validasi data terlebih dahulu sebelum klasifikasi.',
            ], 422);
        }

        // Batasan proposal #3: hanya data terbaru & tervalidasi (default 3 bulan terakhir).
        // ?periode_bulan=0 untuk menonaktifkan (mis. verifikasi data lama).
        $periodeBulan = max(0, (int) $request->query('periode_bulan', 3));
        if ($periodeBulan > 0 && $warga->periode_data && $warga->periode_data->lt(now()->startOfDay()->subMonths($periodeBulan))) {
            return response()->json([
                'message' => "Data warga berperiode {$warga->periode_data->format('Y-m-d')} di luar periode {$periodeBulan} bulan terakhir (batasan data terbaru). Perbarui periode_data atau set periode_bulan=0 untuk tetap mengklasifikasi.",
            ], 422);
        }

        $modelVersion = $this->nb->activeModelVersion();
        if (! $modelVersion) {
            return response()->json([
                'message' => 'Belum ada model aktif. Jalankan training terlebih dahulu.',
            ], 422);
        }

        $result = $this->nb->predict($warga, $modelVersion);

        // updateOrCreate: jika warga sudah punya hasil utk versi aktif, perbarui; jika tidak, buat.
        $hasil = HasilKlasifikasi::updateOrCreate(
            ['warga_id' => $warga->id, 'model_version' => $modelVersion],
            [
                'prob_layak' => $result['prob_layak'],
                'prob_tidak_layak' => $result['prob_tidak_layak'],
                'prediksi_kelas' => $result['prediksi_kelas'],
                'breakdown_json' => [
                    'kategori_input' => $result['kategori_input'],
                    'skor' => $result['skor'],
                    'detail' => $result['breakdown'],
                    'model_version' => $modelVersion,
                ],
            ]
        );

        return (new HasilKlasifikasiResource($hasil->load(['warga', 'approver'])))
            ->additional(['message' => 'Klasifikasi selesai.'])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Batch: klasifikasi semua warga divalidasi yang belum diklasifikasi dgn versi aktif.
     * Memuat tabel probabilitas SEKALI & kategorisasi dari cache -> eliminasi N× query.
     *
     * Batasan proposal #3: data yang dianalisis = data terbaru & tervalidasi dalam
     * periode tertentu. Default `periode_bulan`=3 (contoh proposal); 0 = semua periode.
     */
    public function batch(Request $request): JsonResponse
    {
        $modelVersion = $this->nb->activeModelVersion();
        if (! $modelVersion) {
            return response()->json(['message' => 'Belum ada model aktif. Jalankan training terlebih dahulu.'], 422);
        }

        $periodeBulan = max(0, (int) $request->query('periode_bulan', 3));

        // muat tabel probabilitas sekali untuk semua warga (memoized di loadTable)
        $table = $this->nb->loadTable($modelVersion);
        $query = Warga::where('status_validasi', 'divalidasi');
        if ($periodeBulan > 0) {
            $query->where('periode_data', '>=', now()->startOfDay()->subMonths($periodeBulan));
        }
        $wargaList = $query->get();
        $done = 0;
        $skipped = 0;
        $errors = [];

        foreach ($wargaList as $warga) {
            $exists = HasilKlasifikasi::where('warga_id', $warga->id)
                ->where('model_version', $modelVersion)
                ->exists();
            if ($exists) {
                $skipped++;

                continue;
            }

            try {
                $kategoriInput = $this->nb->categorizer()->categorizeAll([
                    'penghasilan' => $warga->penghasilan_bulanan,
                    'pekerjaan' => $warga->status_pekerjaan,
                    'tanggungan' => $warga->jumlah_tanggungan,
                    'kondisi_rumah' => $warga->kondisi_rumah,
                ]);

                $result = $this->nb->predictWithTable($kategoriInput, $table);
                $result['kategori_input'] = $kategoriInput;

                HasilKlasifikasi::create([
                    'warga_id' => $warga->id,
                    'model_version' => $modelVersion,
                    'prob_layak' => $result['prob_layak'],
                    'prob_tidak_layak' => $result['prob_tidak_layak'],
                    'prediksi_kelas' => $result['prediksi_kelas'],
                    'breakdown_json' => [
                        'kategori_input' => $result['kategori_input'],
                        'skor' => $result['skor'],
                        'detail' => $result['breakdown'],
                        'model_version' => $modelVersion,
                    ],
                ]);
                $done++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'warga_id' => $warga->id,
                    'nama' => $warga->nama,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Klasifikasi batch selesai.',
            'model_version' => $modelVersion,
            'diklasifikasi' => $done,
            'dilewati' => $skipped,
            'gagal' => count($errors),
            'detail_gagal' => $errors,
            'periode_bulan' => $periodeBulan, // batasan #3: data terbaru & tervalidasi
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = HasilKlasifikasi::query()->with(['warga', 'approver']);

        if ($s = $request->query('status_approval')) {
            $query->where('status_approval', $s);
        }
        if ($s = $request->query('prediksi_kelas')) {
            $query->where('prediksi_kelas', $s);
        }

        return HasilKlasifikasiResource::collection(
            $query->orderByDesc('id')->paginate(15)->withQueryString()
        );
    }

    public function show(HasilKlasifikasi $hasilKlasifikasi): HasilKlasifikasiResource
    {
        return new HasilKlasifikasiResource($hasilKlasifikasi->load(['warga', 'approver']));
    }
}
