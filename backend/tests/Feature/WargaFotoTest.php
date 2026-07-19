<?php

namespace Tests\Feature;

use App\Models\AtributKlasifikasi;
use App\Models\KategoriAtribut;
use App\Models\User;
use App\Models\Warga;
use App\Models\WargaFoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature test galeri foto rumah + validasi warga.
 *
 * DB test = SQLite :memory: (phpunit.xml). Atribut + kategori disiapkan manual.
 * Storage difake ('public') agar file tidak benar-benar tertulis ke disk.
 */
class WargaFotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $kondisi = AtributKlasifikasi::create(['kode' => 'kondisi_rumah', 'nama_tampilan' => 'Kondisi Rumah', 'tipe' => 'kategorikal', 'aktif' => true]);
        foreach (['Tidak Layak Huni', 'Kurang Layak Huni', 'Layak Huni'] as $i => $l) {
            KategoriAtribut::create(['atribut_klasifikasi_id' => $kondisi->id, 'label' => $l, 'urutan' => $i + 1]);
        }
        $pekerjaan = AtributKlasifikasi::create(['kode' => 'pekerjaan', 'nama_tampilan' => 'Pekerjaan', 'tipe' => 'kategorikal', 'aktif' => true]);
        foreach (['Buruh/Tani', 'Wiraswasta/Pedagang'] as $i => $l) {
            KategoriAtribut::create(['atribut_klasifikasi_id' => $pekerjaan->id, 'label' => $l, 'urutan' => $i + 1]);
        }
        $penghasilan = AtributKlasifikasi::create(['kode' => 'penghasilan', 'nama_tampilan' => 'Penghasilan', 'tipe' => 'numerik', 'aktif' => true]);
        KategoriAtribut::create(['atribut_klasifikasi_id' => $penghasilan->id, 'label' => '<1.000.000', 'urutan' => 1]);
        $tanggungan = AtributKlasifikasi::create(['kode' => 'tanggungan', 'nama_tampilan' => 'Tanggungan', 'tipe' => 'numerik', 'aktif' => true]);
        KategoriAtribut::create(['atribut_klasifikasi_id' => $tanggungan->id, 'label' => '0–1', 'urutan' => 1]);

        Storage::fake('public');
    }

    protected function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** Buat warga draft (tanpa kondisi_rumah — sesuai alur baru). */
    protected function draftWarga(): Warga
    {
        return Warga::create([
            'nik' => '7402010101910001',
            'nama' => 'Budi Santoso',
            'penghasilan_bulanan' => 800000,
            'status_pekerjaan' => 'Buruh/Tani',
            'jumlah_tanggungan' => 4,
            'periode_data' => now()->format('Y-m-d'),
            'created_by' => User::factory()->create()->id,
        ]);
    }

    public function test_create_warga_tanpa_kondisi_rumah_berhasil_draft(): void
    {
        $admin = $this->adminUser();
        $payload = [
            'nik' => '7402010101910002',
            'nama' => 'Siti Aminah',
            'penghasilan_bulanan' => 1200000,
            'status_pekerjaan' => 'Buruh/Tani',
            'jumlah_tanggungan' => 2,
            'periode_data' => now()->format('Y-m-d'),
        ];

        $res = $this->actingAs($admin)->postJson('/api/warga', $payload);

        $res->assertCreated()
            ->assertJsonPath('data.status_validasi', 'draft')
            ->assertJsonPath('data.kondisi_rumah', null);
    }

    public function test_upload_foto_menulis_disk_dan_mengembalikan_url(): void
    {
        $admin = $this->adminUser();
        $warga = $this->draftWarga();

        $res = $this->actingAs($admin)->postJson("/api/warga/{$warga->id}/fotos", [
            'fotos' => [UploadedFile::fake()->image('rumah1.jpg', 800, 600)],
        ]);

        $res->assertCreated();
        $this->assertDatabaseCount('warga_fotos', 1);
        $foto = WargaFoto::first();
        Storage::disk('public')->assertExists($foto->path);
        $this->assertNotEmpty($res->json('data.0.url'));
    }

    public function test_upload_melebihi_batas_5_foto_ditolak(): void
    {
        $admin = $this->adminUser();
        $warga = $this->draftWarga();

        // Sudah 4 foto
        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($admin)->postJson("/api/warga/{$warga->id}/fotos", [
                'fotos' => [UploadedFile::fake()->image("r{$i}.jpg")],
            ])->assertCreated();
        }

        // Coba tambah 2 sekaligus -> 4+2=6 > 5 -> 422
        $res = $this->actingAs($admin)->postJson("/api/warga/{$warga->id}/fotos", [
            'fotos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ]);

        $res->assertStatus(422);
        $this->assertDatabaseCount('warga_fotos', 4);
    }

    public function test_validasi_gagal_tanpa_foto(): void
    {
        $admin = $this->adminUser();
        $warga = $this->draftWarga(); // 0 foto

        $res = $this->actingAs($admin)->patchJson("/api/warga/{$warga->id}/validasi", [
            'kondisi_rumah' => 'Tidak Layak Huni',
        ]);

        $res->assertStatus(422);
        $this->assertSame('draft', $warga->fresh()->status_validasi);
    }

    public function test_validasi_gagal_tanpa_label(): void
    {
        $admin = $this->adminUser();
        $warga = $this->draftWarga();
        $this->actingAs($admin)->postJson("/api/warga/{$warga->id}/fotos", [
            'fotos' => [UploadedFile::fake()->image('r.jpg')],
        ])->assertCreated();

        $res = $this->actingAs($admin)->patchJson("/api/warga/{$warga->id}/validasi", []);

        $res->assertStatus(422);
    }

    public function test_validasi_berhasil_dengan_foto_dan_label(): void
    {
        $admin = $this->adminUser();
        $warga = $this->draftWarga();
        $this->actingAs($admin)->postJson("/api/warga/{$warga->id}/fotos", [
            'fotos' => [UploadedFile::fake()->image('r.jpg')],
        ])->assertCreated();

        $res = $this->actingAs($admin)->patchJson("/api/warga/{$warga->id}/validasi", [
            'kondisi_rumah' => 'Tidak Layak Huni',
        ]);

        $res->assertOk()
            ->assertJsonPath('data.status_validasi', 'divalidasi')
            ->assertJsonPath('data.kondisi_rumah', 'Tidak Layak Huni');
    }

    public function test_hapus_foto_terakhir_saat_divalidasi_ditolak(): void
    {
        $admin = $this->adminUser();
        $warga = $this->draftWarga();
        $this->actingAs($admin)->postJson("/api/warga/{$warga->id}/fotos", [
            'fotos' => [UploadedFile::fake()->image('r.jpg')],
        ])->assertCreated();
        $this->actingAs($admin)->patchJson("/api/warga/{$warga->id}/validasi", [
            'kondisi_rumah' => 'Tidak Layak Huni',
        ])->assertOk();

        $foto = WargaFoto::first();
        $res = $this->actingAs($admin)->deleteJson("/api/warga/{$warga->id}/fotos/{$foto->id}");

        $res->assertStatus(422);
        $this->assertDatabaseCount('warga_fotos', 1);
    }

    public function test_hapus_foto_saat_ada_lebih_dari_satu_berhasil(): void
    {
        $admin = $this->adminUser();
        $warga = $this->draftWarga();
        $this->actingAs($admin)->postJson("/api/warga/{$warga->id}/fotos", [
            'fotos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ])->assertCreated();

        $foto = WargaFoto::first();
        $res = $this->actingAs($admin)->deleteJson("/api/warga/{$warga->id}/fotos/{$foto->id}");

        $res->assertNoContent();
        $this->assertDatabaseCount('warga_fotos', 1);
        Storage::disk('public')->assertMissing($foto->path);
    }
}
