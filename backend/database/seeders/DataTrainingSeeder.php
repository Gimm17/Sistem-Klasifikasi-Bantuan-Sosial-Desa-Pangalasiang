<?php

namespace Database\Seeders;

use App\Models\DataTraining;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data latih REALISTIS (120 baris: 60 layak / 60 tidak_layak) untuk keperluan
 * simulasi skripsi & pengujian model Naive Bayes Desa Pangalasiang.
 *
 * Catatan Domain Bantuan Sosial:
 * - "layak" = Layak menerima bantuan sosial (keluarga kurang mampu / rentan).
 * - "tidak_layak" = Tidak layak menerima bantuan sosial (keluarga mampu / mandiri).
 *
 * Jalankan: php artisan db:seed --class=DataTrainingSeeder
 */
class DataTrainingSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan data lama agar tidak duplikat saat di-seed ulang
        DB::table('data_training')->delete();

        $rows = [];

        // ==========================================
        // 1. DATA LAYAK MENERIMA BANTUAN (60 Data)
        // Karakteristik dominan: Penghasilan rendah (<1jt atau 1-2jt),
        // Pekerjaan Buruh/Tani/Tidak Bekerja, Tanggungan banyak (4-5 atau >5),
        // Kondisi rumah Tidak Layak Huni / Kurang Layak Huni.
        // ==========================================
        
        // Kelompok Sangat Rentan (25 data)
        for ($i = 0; $i < 25; $i++) {
            $rows[] = [
                'penghasilan_kategori' => '<1.000.000',
                'pekerjaan_kategori' => ($i % 2 == 0) ? 'Buruh/Tani' : 'Tidak Bekerja',
                'tanggungan_kategori' => ($i % 3 == 0) ? '>5' : '4–5',
                'kondisi_rumah_kategori' => ($i % 2 == 0) ? 'Tidak Layak Huni' : 'Kurang Layak Huni',
                'label_kelas' => 'layak',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Kelompok Rentan & Buruh Harian (20 data)
        for ($i = 0; $i < 20; $i++) {
            $rows[] = [
                'penghasilan_kategori' => '1.000.000–2.000.000',
                'pekerjaan_kategori' => ($i % 3 == 0) ? 'Lainnya' : 'Buruh/Tani',
                'tanggungan_kategori' => ($i % 2 == 0) ? '4–5' : '2–3',
                'kondisi_rumah_kategori' => 'Kurang Layak Huni',
                'label_kelas' => 'layak',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Kelompok Kasus Khusus / Kasus Batas - Layak (15 data)
        // Misal: penghasilan 1-2jt tapi tanggungan >5 dan rumah tidak layak
        for ($i = 0; $i < 15; $i++) {
            $rows[] = [
                'penghasilan_kategori' => ($i % 2 == 0) ? '<1.000.000' : '1.000.000–2.000.000',
                'pekerjaan_kategori' => ($i % 3 == 0) ? 'Wiraswasta/Pedagang' : 'Buruh/Tani',
                'tanggungan_kategori' => '>5',
                'kondisi_rumah_kategori' => 'Tidak Layak Huni',
                'label_kelas' => 'layak',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ==========================================
        // 2. DATA TIDAK LAYAK MENERIMA BANTUAN (60 Data)
        // Karakteristik dominan: Penghasilan menengah ke atas (>3jt atau 2-3jt),
        // Pekerjaan PNS/Pegawai atau Wiraswasta, Tanggungan sedikit (0-1 atau 2-3),
        // Kondisi rumah Layak Huni.
        // ==========================================

        // Kelompok Mampu / Mandiri (25 data)
        for ($i = 0; $i < 25; $i++) {
            $rows[] = [
                'penghasilan_kategori' => '>3.000.000',
                'pekerjaan_kategori' => ($i % 2 == 0) ? 'PNS/Pegawai' : 'Wiraswasta/Pedagang',
                'tanggungan_kategori' => ($i % 3 == 0) ? '0–1' : '2–3',
                'kondisi_rumah_kategori' => 'Layak Huni',
                'label_kelas' => 'tidak_layak',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Kelompok Menengah (20 data)
        for ($i = 0; $i < 20; $i++) {
            $rows[] = [
                'penghasilan_kategori' => '2.000.000–3.000.000',
                'pekerjaan_kategori' => ($i % 2 == 0) ? 'Wiraswasta/Pedagang' : 'PNS/Pegawai',
                'tanggungan_kategori' => ($i % 2 == 0) ? '0–1' : '2–3',
                'kondisi_rumah_kategori' => 'Layak Huni',
                'label_kelas' => 'tidak_layak',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Kelompok Kasus Khusus / Kasus Batas - Tidak Layak (15 data)
        // Misal: penghasilan 1-2jt tapi tidak ada tanggungan (0-1) dan rumah layak huni
        for ($i = 0; $i < 15; $i++) {
            $rows[] = [
                'penghasilan_kategori' => ($i % 2 == 0) ? '2.000.000–3.000.000' : '1.000.000–2.000.000',
                'pekerjaan_kategori' => 'Wiraswasta/Pedagang',
                'tanggungan_kategori' => '0–1',
                'kondisi_rumah_kategori' => 'Layak Huni',
                'label_kelas' => 'tidak_layak',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert ke database dalam batch
        DataTraining::insert($rows);
    }
}
