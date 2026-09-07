<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResearchPopulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_membedakan_populasi_dan_target_sampel(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']), 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('total_populasi_kk', 1041)
            ->assertJsonPath('target_sampel', 91)
            ->assertJsonPath('total_warga', 0);
    }

    public function test_rekapitulasi_memuat_populasi_dan_sampel_proporsional_delapan_dusun(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']), 'sanctum')
            ->getJson('/api/rekapitulasi/dusun');

        $response->assertOk()
            ->assertJsonPath('total_dusun', 8)
            ->assertJsonPath('total_populasi_kk', 1041)
            ->assertJsonPath('target_sampel', 91)
            ->assertJsonCount(8, 'detail');

        $this->assertSame(
            [
                ['dusun' => 'Dusun I', 'populasi_kk' => 222, 'target_sampel' => 19],
                ['dusun' => 'Dusun II', 'populasi_kk' => 123, 'target_sampel' => 11],
                ['dusun' => 'Dusun III', 'populasi_kk' => 224, 'target_sampel' => 20],
                ['dusun' => 'Dusun IV', 'populasi_kk' => 91, 'target_sampel' => 8],
                ['dusun' => 'Dusun V', 'populasi_kk' => 118, 'target_sampel' => 10],
                ['dusun' => 'Dusun VI', 'populasi_kk' => 84, 'target_sampel' => 7],
                ['dusun' => 'Dusun VII', 'populasi_kk' => 89, 'target_sampel' => 8],
                ['dusun' => 'Dusun VIII', 'populasi_kk' => 90, 'target_sampel' => 8],
            ],
            collect($response->json('detail'))
                ->map(fn (array $row): array => [
                    'dusun' => $row['dusun'],
                    'populasi_kk' => $row['populasi_kk'],
                    'target_sampel' => $row['target_sampel'],
                ])
                ->all()
        );
    }
}
