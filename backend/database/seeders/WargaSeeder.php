<?php

namespace Database\Seeders;

use App\Models\HasilKlasifikasi;
use App\Models\User;
use App\Models\Warga;
use App\Services\NaiveBayes\ProbabilityTableBuilder;
use App\Services\NaiveBayes\NaiveBayesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk data Warga & Klasifikasi awal.
 *
 * 1. Memastikan semua data warga memiliki kolom 'dusun' terisi (Dusun I - V).
 * 2. Jika tabel warga masih kosong, men-generate 35 data warga realistis
 *    di seluruh Dusun I - V, kemudian melatih model Naive Bayes dan melakukan
 *    klasifikasi batch otomatis untuk keperluan demonstrasi/skripsi.
 */
class WargaSeeder extends Seeder
{
    public function run(): void
    {
        $dusunList = ['Dusun I', 'Dusun II', 'Dusun III', 'Dusun IV', 'Dusun V'];

        // 1. Update warga yang sudah ada tapi belum punya dusun
        $existingWarga = Warga::whereNull('dusun')->orWhere('dusun', '')->get();
        if ($existingWarga->count() > 0) {
            $existingWarga->each(function ($w, $i) use ($dusunList) {
                $w->update(['dusun' => $dusunList[$i % count($dusunList)]]);
            });
            $this->command?->info("Berhasil mengupdate dusun untuk {$existingWarga->count()} data warga lama.");
        }

        // 2. Jika tidak ada warga sama sekali, buat 35 data warga baru
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        if (Warga::count() === 0) {
            $namaList = [
                'Ahmad Subarjo', 'Siti Aminah', 'Budi Santoso', 'Nurhayati', 'Herman Sulaiman',
                'Dewi Lestari', 'Ruslan Abdul', 'Fitriani', 'Kadir Jafar', 'Maryati',
                'Zulkifli', 'Ratna Sari', 'Hasan Basri', 'Yuliana', 'Mustafa',
                'Farida', 'Anwar Sadat', 'Halimah', 'Lukman Hakim', 'Salmawati',
                'Darwis', 'Rismawati', 'Supriadi', 'Asniar', 'Rudi Hartono',
                'Murniati', 'Baharuddin', 'Nuraeni', 'Irwan', 'Sukmawati',
                'Jamaluddin', 'Wahuni', 'Syamsul Bahri', 'Masyita', 'Zainal Abidin'
            ];

            $pekerjaanList = ['Buruh/Tani', 'Wiraswasta/Pedagang', 'PNS/Pegawai', 'Tidak Bekerja', 'Lainnya'];
            $rumahList = ['Tidak Layak Huni', 'Kurang Layak Huni', 'Layak Huni'];

            $wargaRows = [];
            foreach ($namaList as $i => $nama) {
                $dusun = $dusunList[$i % count($dusunList)];
                
                // Buat variasi penghasilan dan kondisi yang realistis
                if ($i % 3 === 0) {
                    $penghasilan = rand(400, 950) * 1000;
                    $pekerjaan = 'Buruh/Tani';
                    $tanggungan = rand(3, 6);
                    $rumah = 'Tidak Layak Huni';
                } elseif ($i % 3 === 1) {
                    $penghasilan = rand(1100, 2200) * 1000;
                    $pekerjaan = $pekerjaanList[$i % count($pekerjaanList)];
                    $tanggungan = rand(1, 4);
                    $rumah = 'Kurang Layak Huni';
                } else {
                    $penghasilan = rand(2800, 4500) * 1000;
                    $pekerjaan = 'Wiraswasta/Pedagang';
                    $tanggungan = rand(0, 2);
                    $rumah = 'Layak Huni';
                }

                $wargaRows[] = [
                    'nik' => '7203' . str_pad($i + 100000000001, 12, '0', STR_PAD_LEFT),
                    'nama' => $nama,
                    'alamat' => "RT 0" . (($i % 3) + 1) . " / RW 01, " . $dusun,
                    'dusun' => $dusun,
                    'penghasilan_bulanan' => $penghasilan,
                    'status_pekerjaan' => $pekerjaan,
                    'jumlah_tanggungan' => $tanggungan,
                    'kondisi_rumah' => $rumah,
                    'periode_data' => now()->format('Y-m-01'),
                    'status_validasi' => 'divalidasi',
                    'created_by' => $adminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Warga::insert($wargaRows);
            $this->command?->info("Berhasil membuat 35 data warga baru di seluruh Dusun I - V.");
        }

        // 3. Pastikan model Naive Bayes sudah dilatih
        $nbService = app(NaiveBayesService::class);
        $modelVersion = $nbService->activeModelVersion();
        
        if (!$modelVersion) {
            $builder = app(ProbabilityTableBuilder::class);
            $newVersion = $builder->build('v' . now()->format('Ymd-His'), $adminId, true, 'observed');
            $modelVersion = $newVersion->model_version;
            $this->command?->info("Berhasil melatih model Naive Bayes versi {$modelVersion}.");
        }

        // 4. Lakukan klasifikasi batch jika ada warga yang belum diklasifikasi
        if (Warga::where('status_validasi', 'divalidasi')->count() > HasilKlasifikasi::count()) {
            $klasifikasiController = app(\App\Http\Controllers\Api\KlasifikasiController::class);
            $klasifikasiController->batch(new \Illuminate\Http\Request());
            $this->command?->info("Berhasil melakukan klasifikasi batch.");
        }

        // 5. Berikan variasi status approval (approved, rejected, pending) jika masih pending semua
        if (HasilKlasifikasi::where('status_approval', '!=', 'pending')->count() === 0) {
            $allHasil = HasilKlasifikasi::all();
            foreach ($allHasil as $idx => $hasil) {
                if ($idx % 4 === 0) {
                    $hasil->update([
                        'status_approval' => 'approved',
                        'approved_by' => $adminId,
                        'catatan_approval' => 'Disetujui berdasarkan verifikasi lapangan RT/RW.',
                    ]);
                } elseif ($idx % 7 === 0) {
                    $hasil->update([
                        'status_approval' => 'rejected',
                        'approved_by' => $adminId,
                        'catatan_approval' => 'Ditolak karena yang bersangkutan sudah pindah domisili.',
                    ]);
                }
            }
            $this->command?->info("Berhasil mengeset sampel status approval (approved/rejected/pending).");
        }
    }
}
