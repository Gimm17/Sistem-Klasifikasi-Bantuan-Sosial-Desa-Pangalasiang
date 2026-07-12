<?php

namespace Tests\Feature;

use App\Models\AtributKlasifikasi;
use App\Models\KategoriAtribut;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penegakan batasan proposal #3: klasifikasi hanya utk data terbaru & tervalidasi
 * dalam periode tertentu (default 3 bulan). ?periode_bulan=N mengatur; 0 = semua.
 */
class KlasifikasiPeriodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // seed minimal agar warga bisa dibuat (created_by butuh user; kategori tdk wajib utk test ini)
        $pk = AtributKlasifikasi::create(['kode' => 'pekerjaan', 'nama_tampilan' => 'Pekerjaan', 'tipe' => 'kategorikal', 'aktif' => true]);
        KategoriAtribut::create(['atribut_klasifikasi_id' => $pk->id, 'label' => 'Buruh/Tani', 'urutan' => 1]);
    }

    protected function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function warga(\DateTimeInterface $periode): Warga
    {
        return Warga::create([
            'nik' => '740201010191'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            'nama' => 'Test',
            'penghasilan_bulanan' => 1000000,
            'status_pekerjaan' => 'Buruh/Tani',
            'jumlah_tanggungan' => 2,
            'kondisi_rumah' => 'Layak Huni',
            'periode_data' => $periode->format('Y-m-d'),
            'status_validasi' => 'divalidasi',
            'created_by' => $this->adminUser()->id,
        ]);
    }

    public function test_warga_periode_lama_ditolak_default_3_bulan(): void
    {
        $lama = $this->warga(now()->subMonths(6)); // 6 bulan lalu -> di luar periode 3 bulan

        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson("/api/klasifikasi/{$lama->id}");

        $response->assertStatus(422);
        $this->assertStringContainsString('di luar periode', $response->json('message'));
    }

    public function test_periode_bulan_0_membolehkan_data_lama(): void
    {
        $lama = $this->warga(now()->subMonths(6));

        // ?periode_bulan=0 -> pengecekan periode dilewati; lanjut ke cek model aktif (belum ada) -> 422 lain
        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson("/api/klasifikasi/{$lama->id}?periode_bulan=0");

        // Bukan error periode lagi; pesan tentang model aktif.
        $response->assertStatus(422);
        $this->assertStringContainsString('model aktif', $response->json('message'));
    }

    public function test_warga_periode_baru_lewat_cek_periode(): void
    {
        $baru = $this->warga(now()->subDays(10)); // dalam 3 bulan -> lewat cek periode

        $response = $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson("/api/klasifikasi/{$baru->id}");

        // Lewat cek periode, tapi belum ada model aktif -> 422 model.
        $response->assertStatus(422);
        $this->assertStringContainsString('model aktif', $response->json('message'));
    }
}
