<?php

namespace Tests\Feature;

use App\Models\AtributKlasifikasi;
use App\Models\KategoriAtribut;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/**
 * Feature test Import/Export warga — CSV & XLSX, multi-file, duplikat NIK.
 *
 * DB test = SQLite :memory: (phpunit.xml). Atribut + kategori disiapkan manual
 * agar label pekerjaan/kondisi_rumah yang divalidasi tersedia tanpa seeder penuh.
 */
class ImportWargaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // --- seed 4 atribut + label (cukup utk validasi import warga & data training) ---
        $pekerjaan = AtributKlasifikasi::create(['kode' => 'pekerjaan', 'nama_tampilan' => 'Pekerjaan', 'tipe' => 'kategorikal', 'aktif' => true]);
        foreach (['Tidak Bekerja', 'Buruh/Tani', 'Wiraswasta/Pedagang', 'PNS/Pegawai', 'Lainnya'] as $i => $l) {
            KategoriAtribut::create(['atribut_klasifikasi_id' => $pekerjaan->id, 'label' => $l, 'urutan' => $i + 1]);
        }
        $kondisi = AtributKlasifikasi::create(['kode' => 'kondisi_rumah', 'nama_tampilan' => 'Kondisi Rumah', 'tipe' => 'kategorikal', 'aktif' => true]);
        foreach (['Tidak Layak Huni', 'Kurang Layak Huni', 'Layak Huni'] as $i => $l) {
            KategoriAtribut::create(['atribut_klasifikasi_id' => $kondisi->id, 'label' => $l, 'urutan' => $i + 1]);
        }
        $penghasilan = AtributKlasifikasi::create(['kode' => 'penghasilan', 'nama_tampilan' => 'Penghasilan', 'tipe' => 'numerik', 'aktif' => true]);
        foreach (['<1.000.000', '1.000.000–2.000.000', '2.000.000–3.000.000', '>3.000.000'] as $i => $l) {
            KategoriAtribut::create(['atribut_klasifikasi_id' => $penghasilan->id, 'label' => $l, 'urutan' => $i + 1]);
        }
        $tanggungan = AtributKlasifikasi::create(['kode' => 'tanggungan', 'nama_tampilan' => 'Tanggungan', 'tipe' => 'numerik', 'aktif' => true]);
        foreach (['0–1', '2–3', '4–5', '>5'] as $i => $l) {
            KategoriAtribut::create(['atribut_klasifikasi_id' => $tanggungan->id, 'label' => $l, 'urutan' => $i + 1]);
        }

        Storage::fake('local');
    }

    /** Helper: user admin terotentikasi Sanctum. */
    protected function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_import_csv_multi_baris_dengan_dusun(): void
    {
        $csv = "nik,nama,alamat,dusun,penghasilan_bulanan,status_pekerjaan,jumlah_tanggungan,kondisi_rumah,periode_data\n".
            "7402010101910001,Budi,Dusun A,Dusun Tengah,1500000,Buruh/Tani,4,Kurang Layak Huni,2026-07-01\n".
            "7402010101910002,Siti,Dusun B,Dusun Bawah,800000,Tidak Bekerja,5,Tidak Layak Huni,2026-07-01\n".
            "0000,nama salah,,,,,,,2026-07-01\n"; // baris invalid (NIK <16 digit)

        $file = UploadedFile::fake()->createWithContent('warga.csv', $csv);

        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson('/api/warga/import', ['files' => [$file]]);

        $response->assertOk()
            ->assertJsonPath('diimpor', 2)
            ->assertJsonPath('gagal', 1);

        $this->assertDatabaseHas('warga', ['nik' => '7402010101910001', 'dusun' => 'Dusun Tengah']);
        $this->assertDatabaseHas('warga', ['nik' => '7402010101910002']);
        $this->assertSame(2, Warga::count());
    }

    public function test_import_xlsx_multi_baris(): void
    {
        $file = $this->makeXlsx([
            ['nik', 'nama', 'alamat', 'dusun', 'penghasilan_bulanan', 'status_pekerjaan', 'jumlah_tanggungan', 'kondisi_rumah', 'periode_data'],
            ['7402010101910010', 'Andi', 'Dusun A', 'Dusun Atas', 2500000, 'PNS/Pegawai', 2, 'Layak Huni', '2026-07-01'],
        ]);

        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson('/api/warga/import', ['files' => [$file]]);

        $response->assertOk()->assertJsonPath('diimpor', 1);
        $this->assertDatabaseHas('warga', ['nik' => '7402010101910010', 'penghasilan_bulanan' => 2500000]);
    }

    public function test_import_multi_file_dan_duplikat_nik(): void
    {
        // NIK sama di dua file -> hanya boleh masuk sekali.
        $csv = "nik,nama,penghasilan_bulanan,status_pekerjaan,jumlah_tanggungan,kondisi_rumah\n".
            "7402010101910020,Sama,1000000,Buruh/Tani,3,Kurang Layak Huni\n";

        $xlsx = $this->makeXlsx([
            ['nik', 'nama', 'penghasilan_bulanan', 'status_pekerjaan', 'jumlah_tanggungan', 'kondisi_rumah'],
            ['7402010101910020', 'Sama Lagi', 1000000, 'Buruh/Tani', 3, 'Kurang Layak Huni'],
            ['7402010101910021', 'Baru', 900000, 'Tidak Bekerja', 4, 'Tidak Layak Huni'],
        ]);

        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson('/api/warga/import', ['files' => [
                UploadedFile::fake()->createWithContent('a.csv', $csv),
                $xlsx,
            ]]);

        $response->assertOk();
        // total diimpor = 2 (NIK ...020 sekali + ...021), 1 duplikat dilewati.
        $this->assertSame(2, Warga::count());
        $this->assertDatabaseHas('warga', ['nik' => '7402010101910020']);
        $this->assertDatabaseHas('warga', ['nik' => '7402010101910021']);
    }

    public function test_template_xlsx_terunduh_dengan_header(): void
    {
        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->get('/api/warga/import/template?format=xlsx');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // baca balik xlsx dari body, pastikan header ada
        $tmp = tmpfile();
        fwrite($tmp, $response->streamedContent());
        $path = stream_get_meta_data($tmp)['uri'];
        $sheet = IOFactory::load($path)->getActiveSheet();
        $this->assertSame('nik', $sheet->getCell('A1')->getValue());
        $this->assertSame('dusun', $sheet->getCell('D1')->getValue());
    }

    public function test_nik_soft_deleted_dilewati_bukan_500(): void
    {
        // NIK yang di-soft-delete tetap memakai constraint unique di DB.
        // Sebelumnya: dup-check lolos -> Warga::create melempar exception -> 500.
        // Sekarang: dideteksi sbg duplikat (withTrashed) -> dilewati dgn aman.
        $deleted = Warga::create([
            'nik' => '7402010101910050', 'nama' => 'Akan Dihapus',
            'penghasilan_bulanan' => 1000000, 'status_pekerjaan' => 'Buruh/Tani',
            'jumlah_tanggungan' => 2, 'kondisi_rumah' => 'Layak Huni',
            'periode_data' => '2026-07-01', 'status_validasi' => 'draft',
            'created_by' => $this->adminUser()->id,
        ]);
        $deleted->delete(); // soft-delete

        $csv = "nik,nama,penghasilan_bulanan,status_pekerjaan,jumlah_tanggungan,kondisi_rumah\n".
            "7402010101910050,Warga Hidup Lagi,1000000,Buruh/Tani,2,Layak Huni\n".
            "7402010101910051,Warga Benar2 Baru,900000,Tidak Bekerja,3,Kurang Layak Huni\n";

        $file = UploadedFile::fake()->createWithContent('a.csv', $csv);
        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson('/api/warga/import', ['files' => [$file]]);

        $response->assertOk()->assertJsonPath('diimpor', 1)->assertJsonPath('gagal', 1);
        // TIDAK boleh ada warga "hidup" dgn NIK soft-deleted (constraint unique utuh)
        $this->assertSame(1, Warga::withTrashed()->where('nik', '7402010101910050')->count());
        $this->assertDatabaseHas('warga', ['nik' => '7402010101910051']);
    }

    public function test_export_xlsx_mengandung_dusun(): void
    {
        Warga::create([
            'nik' => '7402010101910099', 'nama' => 'Exp', 'dusun' => 'Dusun X',
            'penghasilan_bulanan' => 1000000, 'status_pekerjaan' => 'Buruh/Tani',
            'jumlah_tanggungan' => 2, 'kondisi_rumah' => 'Layak Huni',
            'periode_data' => '2026-07-01', 'status_validasi' => 'draft',
            'created_by' => $this->adminUser()->id,
        ]);

        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->get('/api/warga/export?format=xlsx');

        $response->assertOk();
        $tmp = tmpfile();
        fwrite($tmp, $response->streamedContent());
        $sheet = IOFactory::load(stream_get_meta_data($tmp)['uri'])->getActiveSheet();
        $this->assertSame('Dusun X', $sheet->getCell('D2')->getValue()); // kolom D = dusun
    }

    public function test_export_warga_filter_status_validasi(): void
    {
        $admin = $this->adminUser();
        Warga::create(['nik' => '7402010101910080', 'nama' => 'Draft', 'penghasilan_bulanan' => 1000000, 'status_pekerjaan' => 'Buruh/Tani', 'jumlah_tanggungan' => 1, 'kondisi_rumah' => 'Layak Huni', 'periode_data' => '2026-07-01', 'status_validasi' => 'draft', 'created_by' => $admin->id]);
        Warga::create(['nik' => '7402010101910081', 'nama' => 'Valid', 'penghasilan_bulanan' => 1000000, 'status_pekerjaan' => 'Buruh/Tani', 'jumlah_tanggungan' => 1, 'kondisi_rumah' => 'Layak Huni', 'periode_data' => '2026-07-01', 'status_validasi' => 'divalidasi', 'created_by' => $admin->id]);

        $response = $this->actingAs($admin, 'sanctum')->get('/api/warga/export?format=csv&status_validasi=divalidasi');
        $response->assertOk();
        // hanya baris 'Valid' (1 data + 1 header) -> 2 baris CSV
        $this->assertSame(2, substr_count($response->content(), "\n"));
    }

    // ----- DATA TRAINING -----

    public function test_import_data_training_xlsx_dan_csv(): void
    {
        $csv = "penghasilan_kategori,pekerjaan_kategori,tanggungan_kategori,kondisi_rumah_kategori,label_kelas\n".
            ">3.000.000,PNS/Pegawai,0–1,Layak Huni,layak\n".
            "<1.000.000,Tidak Bekerja,4–5,Tidak Layak Huni,tidak_layak\n".
            "<1.000.000,Tidak Bekerja,4–5,Tidak Layak Huni,bukan_label\n"; // label invalid

        $xlsx = $this->makeXlsx([
            ['penghasilan_kategori', 'pekerjaan_kategori', 'tanggungan_kategori', 'kondisi_rumah_kategori', 'label_kelas'],
            ['>3.000.000', 'PNS/Pegawai', '0–1', 'Layak Huni', 'layak'],
        ]);

        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson('/api/data-training/import', ['files' => [
                UploadedFile::fake()->createWithContent('dt.csv', $csv),
                $xlsx,
            ]]);

        $response->assertOk();
        // 2 valid di csv + 1 valid di xlsx = 3; 1 gagal (label invalid).
        $this->assertSame(3, \App\Models\DataTraining::count());
    }

    public function test_template_data_training_xlsx_terunduh(): void
    {
        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->get('/api/data-training/import/template?format=xlsx');
        $response->assertOk();
        $tmp = tmpfile();
        fwrite($tmp, $response->streamedContent());
        $sheet = IOFactory::load(stream_get_meta_data($tmp)['uri'])->getSheetByName('Data Training');
        $this->assertSame('label_kelas', $sheet->getCell('E1')->getValue());
    }

    /**
     * Bangun UploadedFile xlsx dari array baris (baris pertama = header).
     */
    protected function makeXlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows, null, 'A1');

        $tmpPath = tempnam(sys_get_temp_dir(), 'test_').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tmpPath);

        return new UploadedFile($tmpPath, 'data.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
