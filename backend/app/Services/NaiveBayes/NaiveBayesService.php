<?php

namespace App\Services\NaiveBayes;

use App\Models\ModelProbabilitas;
use App\Models\ModelVersion;
use App\Models\Warga;
use App\Services\Preprocessing\AttributeCategorizer;

/**
 * Mesin Naive Bayes — prediksi kelayakan.
 *
 * Komputasi di LOG-SPACE (numerical stability):
 *   logScore(C) = ln P(C) + Σ ln P(xi|C)
 *   P(C|X)      = exp(logScore(C)) / Σ exp(logScore(Ci))   [log-sum-exp]
 *   prediksi    = argmax_C logScore(C)
 *
 * Log monoton -> argmax & probabilitas ternormalisasi IDENTIK dgn perkalian langsung,
 * tapi tak rentan underflow. predictWithTable() = inti murni (menerima tabel in-memory),
 * dipakai predictCategories() (tabel dari DB) & ModelEvaluator (in-memory).
 */
class NaiveBayesService
{
    /** Atribut inti (PUSAT konfigurasi — rujukan tunggal). */
    public const ATRIBUT = ['penghasilan', 'pekerjaan', 'tanggungan', 'kondisi_rumah'];
    protected const KELAS = ['layak', 'tidak_layak'];

    public function __construct(
        protected AttributeCategorizer $categorizer
    ) {}

    /** Cache tabel per model_version dalam satu request (eliminasi reload di batch). */
    protected array $tableCache = [];

    /**
     * Prediksi Warga: kategorisasi nilai mentah, lalu prediksi pakai model aktif.
     */
    public function predict(Warga $warga, string $modelVersion): array
    {
        $kategoriInput = $this->categorizer->categorizeAll([
            'penghasilan' => $warga->penghasilan_bulanan,
            'pekerjaan' => $warga->status_pekerjaan,
            'tanggungan' => $warga->jumlah_tanggungan,
            'kondisi_rumah' => $warga->kondisi_rumah,
        ]);

        $result = $this->predictCategories($kategoriInput, $modelVersion);
        $result['kategori_input'] = $kategoriInput;

        return $result;
    }

    /**
     * Prediksi dari 4 label kategori memakai tabel di DB (model_version).
     */
    public function predictCategories(array $input, string $modelVersion): array
    {
        $this->ensureModelExists($modelVersion);

        return $this->predictWithTable($input, $this->loadTable($modelVersion));
    }

    /**
     * Inti prediksi terhadap tabel in-memory. Murni matematis (log-space).
     *
     * @param  array  $table  hasil ProbabilityTableBuilder::computeTable()
     */
    public function predictWithTable(array $input, array $table): array
    {
        // validasi input
        foreach (self::ATRIBUT as $atributKode) {
            if (! array_key_exists($atributKode, $input)) {
                throw new \InvalidArgumentException("Input prediksi kurang atribut '{$atributKode}'.");
            }
        }

        $logScore = [];
        $breakdown = [];

        foreach (self::KELAS as $kelas) {
            $prior = (float) $table['prior'][$kelas];
            $sum = $prior > 0 ? log($prior) : -INF;
            $detailAtribut = [];

            foreach (self::ATRIBUT as $atributKode) {
                $kategori = $input[$atributKode];
                $likelihood = $table['likelihood'][$kelas][$atributKode][$kategori] ?? null;

                // fallback: kategori tak terlihat di training -> Laplace dgn count=0.
                if ($likelihood === null) {
                    $likelihood = LaplaceSmoothing::likelihood(
                        0,
                        $table['count_class'][$kelas],
                        $table['unique'][$atributKode]
                    );
                }

                $sum += log($likelihood); // likelihood selalu > 0 (Laplace) -> aman
                $detailAtribut[$atributKode] = [
                    'kategori' => $kategori,
                    'likelihood' => $likelihood,
                ];
            }

            $logScore[$kelas] = $sum;
            $breakdown[$kelas] = [
                'prior' => $prior,
                'atribut' => $detailAtribut,
                'skor_sebelum_normalisasi' => exp($sum),
                'skor_log' => $sum,
            ];
        }

        // log-sum-exp normalization
        $maxLog = max($logScore['layak'], $logScore['tidak_layak']);
        $e = [
            'layak' => exp($logScore['layak'] - $maxLog),
            'tidak_layak' => exp($logScore['tidak_layak'] - $maxLog),
        ];
        $sumE = $e['layak'] + $e['tidak_layak'];
        $probLayak = $sumE > 0 ? $e['layak'] / $sumE : 0.5;
        $probTidak = $sumE > 0 ? $e['tidak_layak'] / $sumE : 0.5;
        $prediksi = ($probLayak >= $probTidak) ? 'layak' : 'tidak_layak';

        return [
            'prob_layak' => $probLayak,
            'prob_tidak_layak' => $probTidak,
            'prediksi_kelas' => $prediksi,
            'skor' => [
                'layak' => exp($logScore['layak']),
                'tidak_layak' => exp($logScore['tidak_layak']),
            ],
            'skor_log' => $logScore,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Muat tabel probabilitas dari DB (memoized per request).
     */
    public function loadTable(string $modelVersion): array
    {
        if (isset($this->tableCache[$modelVersion])) {
            return $this->tableCache[$modelVersion];
        }

        $version = ModelVersion::where('model_version', $modelVersion)->firstOrFail();

        $priors = ModelProbabilitas::where('model_version', $modelVersion)
            ->whereNull('atribut_kode')->pluck('prior_probability', 'kelas')->all();

        $countClass = [
            'layak' => $version->count_layak,
            'tidak_layak' => $version->count_tidak_layak,
        ];

        $likelihood = [];
        $observed = [];
        foreach (self::ATRIBUT as $atributKode) {
            $rows = ModelProbabilitas::where('model_version', $modelVersion)
                ->where('atribut_kode', $atributKode)->get();
            foreach (self::KELAS as $kelas) {
                foreach ($rows->where('kelas', $kelas) as $row) {
                    $likelihood[$kelas][$atributKode][$row->kategori] = (float) $row->likelihood;
                }
            }
            $observed[$atributKode] = $rows->pluck('kategori')->unique()->values()->all();
        }

        $storedUnique = $version->unique_per_attribute;
        $unique = [];
        foreach (self::ATRIBUT as $atributKode) {
            $unique[$atributKode] = $storedUnique[$atributKode] ?? count($observed[$atributKode]);
        }

        return $this->tableCache[$modelVersion] = [
            'total' => $version->total_data,
            'count_class' => $countClass,
            'prior' => ['layak' => (float) $priors['layak'], 'tidak_layak' => (float) $priors['tidak_layak']],
            'unique' => $unique,
            'observed' => $observed,
            'likelihood' => $likelihood,
            'vocab_source' => $version->vocab_source ?? 'observed',
        ];
    }

    /**
     * Hitung peringkat pengaruh tiap atribut berdasarkan rata-rata divergensi
     * log-likelihood antar kelas. Semakin tinggi skor -> semakin diskriminatif.
     *
     * Menjawab tujuan riset: "identifikasi atribut paling berpengaruh".
     */
    public function atributImportance(string $modelVersion): array
    {
        $table = $this->loadTable($modelVersion);

        $result = [];
        foreach (self::ATRIBUT as $atributKode) {
            $cats = $table['observed'][$atributKode] ?? [];
            if (empty($cats)) {
                continue;
            }

            $totalDiff = 0;
            foreach ($cats as $kategori) {
                $lLayak = $table['likelihood']['layak'][$atributKode][$kategori]
                    ?? LaplaceSmoothing::likelihood(0, $table['count_class']['layak'], $table['unique'][$atributKode]);
                $lTidak = $table['likelihood']['tidak_layak'][$atributKode][$kategori]
                    ?? LaplaceSmoothing::likelihood(0, $table['count_class']['tidak_layak'], $table['unique'][$atributKode]);

                $diff = abs(log($lLayak) - log($lTidak));
                $totalDiff += $diff;
            }

            $result[] = [
                'atribut' => $atributKode,
                'label' => ucwords(str_replace('_', ' ', $atributKode)),
                'score' => round(($totalDiff / max(1, count($cats))) * 100, 1),
            ];
        }

        usort($result, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $result;
    }

    /**
     * Dapatkan categorizer instance (dipakai batch classification).
     */
    public function categorizer(): AttributeCategorizer
    {
        return $this->categorizer;
    }

    protected function ensureModelExists(string $modelVersion): void
    {
        if (! ModelVersion::where('model_version', $modelVersion)->exists()) {
            throw new \RuntimeException("Model version '{$modelVersion}' tidak ditemukan. Jalankan training dahulu.");
        }
    }

    public function activeModelVersion(): ?string
    {
        $v = ModelVersion::where('is_active', true)->latest('id')->first();

        return $v?->model_version;
    }
}
