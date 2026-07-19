<?php

namespace Tests\Feature;

use App\Models\AtributKlasifikasi;
use App\Models\KategoriAtribut;
use App\Models\HasilKlasifikasi;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test perilaku soft-delete warga:
 *  - NIK warga yang dihapus boleh dipakai ulang (unique hanya cek baris aktif).
 *  - Hasil klasifikasi milik warga yang dihapus tidak tampil di dashboard/klasifikasi
 *    (agar UI tidak menampilkan baris dgn nama warga kosong / "yatim").
 */
class WargaSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed minimal atribut+kategori agar Form Request Rule::in lolos.
        $pekerjaan = AtributKlasifikasi::create(['kode' => 'pekerjaan', 'nama_tampilan' => 'Pekerjaan', 'tipe' => 'kategorikal', 'aktif' => true]);
        foreach (['Buruh/Tani', 'Wiraswasta/Pedagang'] as $i => $l) {
            KategoriAtribut::create(['atribut_klasifikasi_id' => $pekerjaan->id, 'label' => $l, 'urutan' => $i + 1]);
        }
        $kondisi = AtributKlasifikasi::create(['kode' => 'kondisi_rumah', 'nama_tampilan' => 'Kondisi Rumah', 'tipe' => 'kategorikal', 'aktif' => true]);
        foreach (['Tidak Layak Huni', 'Kurang Layak Huni', 'Layak Huni'] as $i => $l) {
            KategoriAtribut::create(['atribut_klasifikasi_id' => $kondisi->id, 'label' => $l, 'urutan' => $i + 1]);
        }
        $penghasilan = AtributKlasifikasi::create(['kode' => 'penghasilan', 'nama_tampilan' => 'Penghasilan', 'tipe' => 'numerik', 'aktif' => true]);
        KategoriAtribut::create(['atribut_klasifikasi_id' => $penghasilan->id, 'label' => '<1.000.000', 'urutan' => 1]);
        $tanggungan = AtributKlasifikasi::create(['kode' => 'tanggungan', 'nama_tampilan' => 'Tanggungan', 'tipe' => 'numerik', 'aktif' => true]);
        KategoriAtribut::create(['atribut_klasifikasi_id' => $tanggungan->id, 'label' => '0–1', 'urutan' => 1]);
    }

    protected function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function payload(string $nik): array
    {
        return [
            'nik' => $nik,
            'nama' => 'Warga Test',
            'penghasilan_bulanan' => 800000,
            'status_pekerjaan' => 'Buruh/Tani',
            'jumlah_tanggungan' => 4,
            'periode_data' => now()->format('Y-m-d'),
        ];
    }

    public function test_nik_warga_yang_dihapus_boleh_dipakai_ulang(): void
    {
        $admin = $this->adminUser();
        $nik = '7402010101910003';

        // Buat lalu hapus (soft-delete) warga dgn NIK tsb.
        $w = $this->actingAs($admin)->postJson('/api/warga', $this->payload($nik))->assertCreated()->json('data.id');
        $this->actingAs($admin)->deleteJson("/api/warga/{$w}")->assertNoContent();
        $this->assertSoftDeleted('warga', ['id' => $w]);

        // Daftar warga baru dgn NIK yang sama HARUS berhasil (bukan 422 unique).
        $res = $this->actingAs($admin)->postJson('/api/warga', $this->payload($nik));
        $res->assertCreated();
        $this->assertSame($nik, $res->json('data.nik'));
    }

    public function test_hasil_warga_terhapus_tidak_tampil_di_klasifikasi_dan_approval(): void
    {
        $admin = $this->adminUser();
        $approver = User::factory()->create(['role' => 'approver']);

        $wargaAktif = Warga::create(array_merge($this->payload('7402010101910004'), ['created_by' => $admin->id]));
        $wargaDihapus = Warga::create(array_merge($this->payload('7402010101910005'), ['created_by' => $admin->id]));

        // Buat hasil pending untuk kedua warga.
        foreach ([$wargaAktif, $wargaDihapus] as $w) {
            HasilKlasifikasi::create([
                'warga_id' => $w->id,
                'model_version' => 'v-test',
                'prob_layak' => 0.7,
                'prob_tidak_layak' => 0.3,
                'prediksi_kelas' => 'layak',
                'breakdown_json' => [],
                'status_approval' => 'pending',
            ]);
        }

        // Hapus salah satu warga (soft-delete) -> hasil-nya jadi yatim.
        $wargaDihapus->delete();

        // Klasifikasi index: hanya hasil warga aktif yang muncul.
        $res = $this->actingAs($admin)->getJson('/api/klasifikasi');
        $res->assertOk();
        $ids = collect($res->json('data'))->pluck('warga.id')->filter()->all();
        $this->assertContains($wargaAktif->id, $ids);
        $this->assertNotContains($wargaDihapus->id, $ids);

        // Approval queue: hanya warga aktif yang muncul.
        $q = $this->actingAs($approver)->getJson('/api/approval/queue');
        $q->assertOk();
        $qIds = collect($q->json('data'))->pluck('warga.id')->filter()->all();
        $this->assertContains($wargaAktif->id, $qIds);
        $this->assertNotContains($wargaDihapus->id, $qIds);
    }

    public function test_dashboard_pending_approval_tidak_menghitung_hasil_warga_terhapus(): void
    {
        $admin = $this->adminUser();
        $w = Warga::create(array_merge($this->payload('7402010101910006'), ['created_by' => $admin->id]));
        HasilKlasifikasi::create([
            'warga_id' => $w->id,
            'model_version' => 'v-test',
            'prob_layak' => 0.6,
            'prob_tidak_layak' => 0.4,
            'prediksi_kelas' => 'layak',
            'breakdown_json' => [],
            'status_approval' => 'pending',
        ]);

        // Sebelum hapus: pending = 1.
        $this->actingAs($admin)->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('pending_approval', 1);

        // Setelah hapus warga: pending harus 0 (hasil yatim tidak dihitung).
        $w->delete();
        $this->actingAs($admin)->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('pending_approval', 0);
    }
}
