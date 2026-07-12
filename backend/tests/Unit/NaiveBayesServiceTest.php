<?php

namespace Tests\Unit;

use App\Models\DataTraining;
use App\Models\ModelProbabilitas;
use App\Services\NaiveBayes\NaiveBayesService;
use App\Services\NaiveBayes\ProbabilityTableBuilder;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit test mesin Naive Bayes — angka DIVERIFIKASI MANUAL (kalkulator) di bawah.
 * Dataset sengaja kecil & simetris supaya setiap probabilitas memiliki bentuk pecahan eksak.
 *
 * Rumus (docs/implementation_plan.md §4.2):
 *   prior      P(C)    = count(C)/total
 *   likelihood P(xi|C) = (count(xi,C)+1)/(count(C)+unique_i)        [Laplace]
 *   skor(C|X)  = P(C)·Π P(xi|C)
 *   P(C|X)     = skor(C|X) / Σ skor
 */
class NaiveBayesServiceTest extends TestCase
{
    use RefreshDatabase;

    private const MV = 'test-model-1';

    protected function setUp(): void
    {
        parent::setUp();

        // --- Dataset 4 baris, 2 per kelas, identik berpasangan ---
        // LAYAK: penghasilan >3jt, PNS, tanggungan 0–1, rumah Layak
        $layak = [
            'penghasilan_kategori' => '>3.000.000',
            'pekerjaan_kategori' => 'PNS/Pegawai',
            'tanggungan_kategori' => '0–1',
            'kondisi_rumah_kategori' => 'Layak Huni',
            'label_kelas' => 'layak',
        ];
        // TIDAK LAYAK: penghasilan <1jt, Tidak Bekerja, tanggungan >5, rumah Tidak Layak
        $tidak = [
            'penghasilan_kategori' => '<1.000.000',
            'pekerjaan_kategori' => 'Tidak Bekerja',
            'tanggungan_kategori' => '>5',
            'kondisi_rumah_kategori' => 'Tidak Layak Huni',
            'label_kelas' => 'tidak_layak',
        ];
        DataTraining::create($layak);
        DataTraining::create($layak);
        DataTraining::create($tidak);
        DataTraining::create($tidak);

        app(ProbabilityTableBuilder::class)->build(self::MV);
    }

    /** Test bahwa komputasi log-space menghasilkan angka yg sama & ada key skor_log. */
    public function test_log_space_identity(): void
    {
        $r = app(NaiveBayesService::class)->predictCategories([
            'penghasilan' => '>3.000.000',
            'pekerjaan' => 'PNS/Pegawai',
            'tanggungan' => '0–1',
            'kondisi_rumah' => 'Layak Huni',
        ], self::MV);

        // skor_log = ln(skor) (dalam presisi floating)
        $this->assertEqualsWithDelta(log(81 / 512), $r['skor_log']['layak'], 1e-9);
        $this->assertEqualsWithDelta(81 / 512, $r['skor']['layak'], 1e-9);

        // normalized probabilities harus 81/82
        $this->assertEqualsWithDelta(81 / 82, $r['prob_layak'], 1e-9);
    }

    /**
     * Test vocab_source='defined': unique > observed, likelihood lebih rendah (Laplace lbh luas).
     * Butuh seeding kategori_atribut karena definedVocabSizes() query DB.
     */
    public function test_vocab_defined_increases_unique_and_reduces_likelihood(): void
    {
        // Seed kategori_atribut: 3 kategori per atribut (2 in training + 1 extra)
        $atributData = [
            'penghasilan' => ['<1.000.000', '>3.000.000', '1.000.000–2.000.000'],
            'pekerjaan' => ['PNS/Pegawai', 'Tidak Bekerja', 'Wiraswasta/Pedagang'],
            'tanggungan' => ['0–1', '>5', '2–3'],
            'kondisi_rumah' => ['Layak Huni', 'Tidak Layak Huni', 'Kurang Layak Huni'],
        ];
        foreach ($atributData as $kode => $labels) {
            $a = \App\Models\AtributKlasifikasi::create([
                'kode' => $kode, 'nama_tampilan' => $kode,
                'tipe' => (in_array($kode, ['pekerjaan', 'kondisi_rumah']) ? 'kategorikal' : 'numerik'),
                'aktif' => true,
            ]);
            foreach ($labels as $i => $l) {
                \App\Models\KategoriAtribut::create([
                    'atribut_klasifikasi_id' => $a->id, 'label' => $l, 'urutan' => $i,
                ]);
            }
        }

        $data = \App\Models\DataTraining::all();
        $table = app(ProbabilityTableBuilder::class)->computeTable($data, 'defined');

        // unique = 3 per atribut (defined), bukan 2 (observed)
        $this->assertEquals(3, $table['unique']['penghasilan']);
        $this->assertEquals(3, $table['unique']['pekerjaan']);
        $this->assertEquals(3, $table['unique']['tanggungan']);
        $this->assertEquals(3, $table['unique']['kondisi_rumah']);

        // Likelihood utk >3.000.000|layak: count=2, classCount=2, unique=3 -> (2+1)/(2+3)=3/5=0.6
        $this->assertEqualsWithDelta(0.6, $table['likelihood']['layak']['penghasilan']['>3.000.000'], 1e-9);

        // Vocab_source & unique_per_attribute tercatat di model lama (observed)
        $this->assertEquals('observed', \App\Models\ModelVersion::where('model_version', self::MV)->value('vocab_source'));
        $this->assertEquals(
            ['penghasilan' => 2, 'pekerjaan' => 2, 'tanggungan' => 2, 'kondisi_rumah' => 2],
            \App\Models\ModelVersion::where('model_version', self::MV)->value('unique_per_attribute')
        );
    }

    /** Prior P(layak)=P(tidak)=0.5 (2/4). */
    public function test_prior_probability(): void
    {
        $priorLayak = ModelProbabilitas::where('model_version', self::MV)
            ->where('kelas', 'layak')->whereNull('atribut_kode')->value('prior_probability');
        $this->assertEqualsWithDelta(0.5, (float) $priorLayak, 1e-9);
    }

    /** Likelihood P(>3.000.000|layak)=3/4=0.75, P(<1.000.000|layak)=1/4=0.25. */
    public function test_likelihood_dengan_laplace(): void
    {
        $get = fn ($k, $kat) => (float) ModelProbabilitas::where('model_version', self::MV)
            ->where('kelas', $k)->where('atribut_kode', 'penghasilan')->where('kategori', $kat)->value('likelihood');

        $this->assertEqualsWithDelta(0.75, $get('layak', '>3.000.000'), 1e-9);
        $this->assertEqualsWithDelta(0.25, $get('layak', '<1.000.000'), 1e-9); // count=0 + Laplace
        $this->assertEqualsWithDelta(0.75, $get('tidak_layak', '<1.000.000'), 1e-9);
        $this->assertEqualsWithDelta(0.25, $get('tidak_layak', '>3.000.000'), 1e-9);
    }

    /**
     * Kasus 1 — profil "jelas layak".
     * skor(layak)=½·(¾)⁴=81/512, skor(tidak)=½·(¼)⁴=1/512
     * => P(layak)=81/82≈0.9878048780, P(tidak)=1/82≈0.0121951220.
     */
    public function test_prediksi_kasus_layak(): void
    {
        $r = app(NaiveBayesService::class)->predictCategories([
            'penghasilan' => '>3.000.000',
            'pekerjaan' => 'PNS/Pegawai',
            'tanggungan' => '0–1',
            'kondisi_rumah' => 'Layak Huni',
        ], self::MV);

        $this->assertSame('layak', $r['prediksi_kelas']);
        $this->assertEqualsWithDelta(81 / 82, $r['prob_layak'], 1e-9);
        $this->assertEqualsWithDelta(1 / 82, $r['prob_tidak_layak'], 1e-9);
        $this->assertEqualsWithDelta(81 / 512, $r['skor']['layak'], 1e-9);
    }

    /**
     * Kasus 2 — profil "jelas tidak layak" (simetri).
     * => P(tidak)=81/82.
     */
    public function test_prediksi_kasus_tidak_layak(): void
    {
        $r = app(NaiveBayesService::class)->predictCategories([
            'penghasilan' => '<1.000.000',
            'pekerjaan' => 'Tidak Bekerja',
            'tanggungan' => '>5',
            'kondisi_rumah' => 'Tidak Layak Huni',
        ], self::MV);

        $this->assertSame('tidak_layak', $r['prediksi_kelas']);
        $this->assertEqualsWithDelta(81 / 82, $r['prob_tidak_layak'], 1e-9);
    }

    /**
     * Kasus 3 — nilai penghasilan TAK PERNAH muncul di training ("1.000.000–2.000.000").
     * Fallback Laplace: (0+1)/(count_class+unique) = 1/(2+2) = 0.25 utk kedua kelas.
     * skor(layak)=½·¼·(¾)³=27/512, skor(tidak)=½·(¼)⁴=1/512
     * => P(layak)=27/28≈0.9642857143, P(tidak)=1/28≈0.0357142857.
     */
    public function test_prediksi_kategori_unseen_fallback(): void
    {
        $r = app(NaiveBayesService::class)->predictCategories([
            'penghasilan' => '1.000.000–2.000.000',
            'pekerjaan' => 'PNS/Pegawai',
            'tanggungan' => '0–1',
            'kondisi_rumah' => 'Layak Huni',
        ], self::MV);

        $this->assertSame('layak', $r['prediksi_kelas']);
        $this->assertEqualsWithDelta(27 / 28, $r['prob_layak'], 1e-9);
        $this->assertEqualsWithDelta(1 / 28, $r['prob_tidak_layak'], 1e-9);

        // likelihood penghasilan = fallback 0.25 utk kedua kelas (bukti fallback jalan)
        $this->assertEqualsWithDelta(0.25, $r['breakdown']['layak']['atribut']['penghasilan']['likelihood'], 1e-9);
        $this->assertEqualsWithDelta(0.25, $r['breakdown']['tidak_layak']['atribut']['penghasilan']['likelihood'], 1e-9);
    }

    /** Probabilitas setelah normalisasi harus berjumlah 1. */
    public function test_probabilitas_berjumlah_satu(): void
    {
        $r = app(NaiveBayesService::class)->predictCategories([
            'penghasilan' => '>3.000.000',
            'pekerjaan' => 'PNS/Pegawai',
            'tanggungan' => '0–1',
            'kondisi_rumah' => 'Layak Huni',
        ], self::MV);

        $this->assertEqualsWithDelta(1.0, $r['prob_layak'] + $r['prob_tidak_layak'], 1e-9);
    }
}
