<?php

namespace App\Services\NaiveBayes;

use App\Models\AtributKlasifikasi;
use App\Models\DataTraining;
use App\Models\KategoriAtribut;
use App\Models\ModelProbabilitas;
use App\Models\ModelVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Training Naive Bayes.
 *
 * Rumus (docs/implementation_plan.md §4.2):
 *   prior      P(C)    = count(C) / total
 *   likelihood P(xi|C) = (count(xi,C)+1) / (count(C) + jumlahKategoriUnik_i)
 *
 * computeTable() = inti matematika murni (tanpa DB); dipakai build() (simpan ke DB)
 * dan ModelEvaluator (train/test split in-memory). Satu sumber kebenaran rumus.
 *
 * vocab_source (titik tanya penguji):
 *  - 'observed' (default): jumlahKategoriUnik_i = nilai unik atribut i yg MUNCUL di
 *    data training. Standar Laplace berbasis observed data.
 *  - 'defined': jumlahKategoriUnik_i = SEMUA kategori atribut i yg didefinisikan admin
 *    (tabel kategori_atribut). Lebih prinsipil: mengakui seluruh domain atribut.
 */
class ProbabilityTableBuilder
{
    /** Atribut inti & kolom kategori-nya di tabel data_training. */
    public const ATRIBUT_KOLOM = [
        'penghasilan' => 'penghasilan_kategori',
        'pekerjaan' => 'pekerjaan_kategori',
        'tanggungan' => 'tanggungan_kategori',
        'kondisi_rumah' => 'kondisi_rumah_kategori',
    ];

    public const KELAS = ['layak', 'tidak_layak'];

    /** Cache ukuran vocab "defined" per atribut (dari kategori_atribut). */
    protected ?array $definedVocabCache = null;

    /**
     * Hitung tabel probabilitas dari koleksi data training (murni, tanpa I/O DB
     * selain utk vocab_source='defined'). Single-pass O(n).
     *
     * @return array{
     *   total:int,
     *   count_class:array<string,int>,
     *   prior:array<string,float>,
     *   unique:array<string,int>,
     *   observed:array<string,array<string>>,
     *   likelihood:array<string,array<string,array<string,float>>>,
     *   vocab_source:string
     * }
     */
    public function computeTable(Collection $data, string $vocabSource = 'observed'): array
    {
        $total = $data->count();
        if ($total === 0) {
            throw new RuntimeException('Data training kosong, tidak bisa training model.');
        }

        // --- single-pass counting ---
        $countClass = array_fill_keys(self::KELAS, 0);
        $countAttrClass = [];   // [atribut][kelas][kategori] => int
        $observedSets = [];     // [atribut] => [kategori => true]
        foreach (self::ATRIBUT_KOLOM as $atributKode => $_) {
            $countAttrClass[$atributKode] = array_fill_keys(self::KELAS, []);
            $observedSets[$atributKode] = [];
        }

        foreach ($data as $row) {
            $kelas = $row->label_kelas;
            if (! isset($countClass[$kelas])) {
                continue; // skip label tak dikenal
            }
            $countClass[$kelas]++;
            foreach (self::ATRIBUT_KOLOM as $atributKode => $kolom) {
                $kategori = $row->{$kolom};
                $countAttrClass[$atributKode][$kelas][$kategori] = ($countAttrClass[$atributKode][$kelas][$kategori] ?? 0) + 1;
                $observedSets[$atributKode][$kategori] = true;
            }
        }

        $observed = [];
        $unique = [];
        $definedSizes = $vocabSource === 'defined' ? $this->definedVocabSizes() : null;
        foreach (self::ATRIBUT_KOLOM as $atributKode => $_) {
            $cats = array_keys($observedSets[$atributKode]);
            $observed[$atributKode] = $cats;
            $unique[$atributKode] = $vocabSource === 'defined'
                ? ($definedSizes[$atributKode] ?? count($cats))
                : count($cats);
        }

        // prior + likelihood
        $prior = [];
        foreach (self::KELAS as $kelas) {
            $prior[$kelas] = $countClass[$kelas] / $total;
        }

        $likelihood = [];
        foreach (self::KELAS as $kelas) {
            foreach (self::ATRIBUT_KOLOM as $atributKode => $_) {
                foreach ($observed[$atributKode] as $kategori) {
                    $count = $countAttrClass[$atributKode][$kelas][$kategori] ?? 0;
                    $likelihood[$kelas][$atributKode][$kategori] = LaplaceSmoothing::likelihood(
                        $count, $countClass[$kelas], $unique[$atributKode]
                    );
                }
            }
        }

        return [
            'total' => $total,
            'count_class' => $countClass,
            'prior' => $prior,
            'unique' => $unique,
            'observed' => $observed,
            'likelihood' => $likelihood,
            'vocab_source' => $vocabSource,
        ];
    }

    /**
     * Training: hitung tabel lalu simpan ke DB + catat versi. Mengembalikan ModelVersion.
     */
    public function build(string $modelVersion, ?int $trainedBy = null, bool $activate = true, string $vocabSource = 'observed'): ModelVersion
    {
        $data = DataTraining::all();
        $table = $this->computeTable($data, $vocabSource);

        return DB::transaction(function () use ($table, $modelVersion, $trainedBy, $activate) {
            if ($activate) {
                ModelVersion::where('is_active', true)->update(['is_active' => false]);
            }

            $version = ModelVersion::create([
                'model_version' => $modelVersion,
                'total_data' => $table['total'],
                'count_layak' => $table['count_class']['layak'],
                'count_tidak_layak' => $table['count_class']['tidak_layak'],
                'vocab_source' => $table['vocab_source'],
                'unique_per_attribute' => $table['unique'],
                'is_active' => $activate,
                'trained_by' => $trainedBy,
            ]);

            $rows = [];
            foreach (self::KELAS as $kelas) {
                $prior = $table['prior'][$kelas];

                // baris prior
                $rows[] = $this->row($modelVersion, $kelas, $prior, null, null, null);

                foreach (self::ATRIBUT_KOLOM as $atributKode => $_) {
                    foreach ($table['observed'][$atributKode] as $kategori) {
                        $rows[] = $this->row(
                            $modelVersion, $kelas, $prior, $atributKode, $kategori,
                            $table['likelihood'][$kelas][$atributKode][$kategori]
                        );
                    }
                }
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                ModelProbabilitas::insert($chunk);
            }

            return $version;
        });
    }

    /**
     * Ukuran vocab "defined" tiap atribut (jumlah baris kategori_atribut).
     *
     * @return array<string,int>  [atribut_kode => jumlah kategori]
     */
    protected function definedVocabSizes(): array
    {
        if ($this->definedVocabCache !== null) {
            return $this->definedVocabCache;
        }

        $atributIds = AtributKlasifikasi::whereIn('kode', array_keys(self::ATRIBUT_KOLOM))
            ->pluck('id', 'kode');

        $counts = KategoriAtribut::whereIn('atribut_klasifikasi_id', $atributIds->values())
            ->selectRaw('atribut_klasifikasi_id, COUNT(*) as jml')
            ->groupBy('atribut_klasifikasi_id')
            ->pluck('jml', 'atribut_klasifikasi_id');

        $byKode = [];
        foreach ($atributIds as $kode => $id) {
            $byKode[$kode] = $counts[$id] ?? 0;
        }

        return $this->definedVocabCache = $byKode;
    }

    private function row(string $mv, string $kelas, $prior, ?string $atribut, ?string $kategori, $likelihood): array
    {
        return [
            'model_version' => $mv,
            'kelas' => $kelas,
            'prior_probability' => $prior,
            'atribut_kode' => $atribut,
            'kategori' => $kategori,
            'likelihood' => $likelihood,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
