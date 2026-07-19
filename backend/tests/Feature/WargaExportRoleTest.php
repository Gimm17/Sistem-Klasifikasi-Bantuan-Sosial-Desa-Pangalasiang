<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test penegakan peran pada endpoint export data warga.
 *
 * Bug asal: GET /api/warga/export middleware hanya role:admin, padahal tombol
 * Export CSV/Excel di WargaList.vue tampil untuk approver juga → 403. Fix:
 * middleware diperluas ke role:admin,approver. Test ini mengunci agar approver
 * tetap diizinkan export di masa depan.
 */
class WargaExportRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Satu warga agar query export tidak kosong (header tetap ditulis walau 0 baris).
        Warga::create([
            'nik' => '7402010101910001',
            'nama' => 'Budi Santoso',
            'penghasilan_bulanan' => 800000,
            'status_pekerjaan' => 'Buruh/Tani',
            'jumlah_tanggungan' => 4,
            'periode_data' => now()->format('Y-m-d'),
            'created_by' => User::factory()->create()->id,
        ]);
    }

    public function test_admin_bisa_export_csv(): void
    {
        $res = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson('/api/warga/export?format=csv');

        $res->assertStatus(200);
        $res->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_approver_bisa_export_csv(): void
    {
        $res = $this->actingAs(User::factory()->create(['role' => 'approver']))
            ->getJson('/api/warga/export?format=csv');

        $res->assertStatus(200);
        $res->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_approver_bisa_export_xlsx(): void
    {
        $res = $this->actingAs(User::factory()->create(['role' => 'approver']))
            ->getJson('/api/warga/export?format=xlsx');

        $res->assertStatus(200);
        $res->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
