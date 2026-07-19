<?php

namespace Tests\Feature;

use App\Services\Import\WargaImportProcessor;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi bug produksi: setelah fitur soft-delete NIK (nik nullable + di-null saat
 * hapus), warga yang di-soft-delete menyimpan NIK = NULL. WargaImportProcessor
 * memanggil `Warga::withTrashed()->pluck('nik')->flip()` — `array_flip()` menolak
 * nilai NULL → "array_flip(): Can only flip string and integer values" (500).
 *
 * Test ini mengunci agar konstruktor processor tetap bisa dipanggil saat ada
 * warga ter-soft-delete dengan NIK NULL.
 */
class WargaImportProcessorNullNikTest extends TestCase
{
    use RefreshDatabase;

    public function test_construct_tidak_gagal_karena_nik_null_pada_soft_deleted(): void
    {
        // Warga aktif (nik terisi)
        Warga::create([
            'nik' => '7402010101910001',
            'nama' => 'Aktif',
            'penghasilan_bulanan' => 1000000,
            'status_pekerjaan' => 'Buruh/Tani',
            'jumlah_tanggungan' => 2,
            'periode_data' => now()->format('Y-m-d'),
            'created_by' => User::factory()->create()->id,
        ]);

        // Warga soft-deleted dengan nik NULL (hasil dari fitur NIK reusable)
        $trashed = Warga::create([
            'nik' => '7402010101910999',
            'nama' => 'Terhapus',
            'penghasilan_bulanan' => 500000,
            'status_pekerjaan' => 'Buruh/Tani',
            'jumlah_tanggungan' => 1,
            'periode_data' => now()->format('Y-m-d'),
            'created_by' => User::factory()->create()->id,
        ]);
        $trashed->nik = null;
        $trashed->save();
        $trashed->delete();

        $this->assertDatabaseHas('warga', ['id' => $trashed->id, 'deleted_at' => $trashed->fresh()->deleted_at]);

        // Konstruktor TIDAK boleh melempar array_flip() fatal.
        $processor = new WargaImportProcessor();
        $this->assertInstanceOf(WargaImportProcessor::class, $processor);
    }
}
