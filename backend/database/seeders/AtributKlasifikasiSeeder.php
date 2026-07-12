<?php

namespace Database\Seeders;

use App\Models\AtributKlasifikasi;
use Illuminate\Database\Seeder;

/**
 * Mengisi 4 atribut klasifikasi inti (Batasan Masalah #1).
 * Nama & tipe atribut TERKUNCI sesuai proposal — hanya kategori/bin-nya yang
 * boleh diubah admin (lihat KategoriAtributSeeder / modul Fase 3).
 */
class AtributKlasifikasiSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['kode' => 'penghasilan', 'nama_tampilan' => 'Penghasilan Bulanan', 'tipe' => 'numerik'],
            ['kode' => 'pekerjaan', 'nama_tampilan' => 'Status Pekerjaan', 'tipe' => 'kategorikal'],
            ['kode' => 'tanggungan', 'nama_tampilan' => 'Jumlah Tanggungan', 'tipe' => 'numerik'],
            ['kode' => 'kondisi_rumah', 'nama_tampilan' => 'Kondisi Tempat Tinggal', 'tipe' => 'kategorikal'],
        ];

        foreach ($rows as $r) {
            AtributKlasifikasi::updateOrCreate(['kode' => $r['kode']], $r);
        }
    }
}
