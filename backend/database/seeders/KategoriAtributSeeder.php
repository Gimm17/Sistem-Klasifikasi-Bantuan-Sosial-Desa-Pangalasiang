<?php

namespace Database\Seeders;

use App\Models\AtributKlasifikasi;
use App\Models\KategoriAtribut;
use Illuminate\Database\Seeder;

/**
 * Kategori / bin default per atribut.
 *
 * Konvensi batas (atribut numerik): interval SETENGAH TERBUKA [bawah, atas].
 * Batas atas pakai pecahan .99 (untuk penghasilan, rupiah integer) supaya nilai
 * bulat 1.000.000 / 2.000.000 / 3.000.000 tidak overlap antar bin. Tanggungan
 * berupa bilangan bulat kecil sehingga batas atas integer saja sudah bebas overlap.
 *
 * BIN INI DAPAT DIUBAH ADMIN (modul Fase 3); service tidak boleh hardcode nilai ini.
 */
class KategoriAtributSeeder extends Seeder
{
    public function run(): void
    {
        $bins = [
            'penghasilan' => [
                ['label' => '<1.000.000', 'batas_bawah' => 0, 'batas_atas' => 999999.99, 'urutan' => 1],
                ['label' => '1.000.000–2.000.000', 'batas_bawah' => 1000000, 'batas_atas' => 1999999.99, 'urutan' => 2],
                ['label' => '2.000.000–3.000.000', 'batas_bawah' => 2000000, 'batas_atas' => 2999999.99, 'urutan' => 3],
                ['label' => '>3.000.000', 'batas_bawah' => 3000000, 'batas_atas' => null, 'urutan' => 4],
            ],
            'pekerjaan' => [
                ['label' => 'Tidak Bekerja', 'batas_bawah' => null, 'batas_atas' => null, 'urutan' => 1],
                ['label' => 'Buruh/Tani', 'batas_bawah' => null, 'batas_atas' => null, 'urutan' => 2],
                ['label' => 'Wiraswasta/Pedagang', 'batas_bawah' => null, 'batas_atas' => null, 'urutan' => 3],
                ['label' => 'PNS/Pegawai', 'batas_bawah' => null, 'batas_atas' => null, 'urutan' => 4],
                ['label' => 'Lainnya', 'batas_bawah' => null, 'batas_atas' => null, 'urutan' => 5],
            ],
            'tanggungan' => [
                ['label' => '0–1', 'batas_bawah' => 0, 'batas_atas' => 1, 'urutan' => 1],
                ['label' => '2–3', 'batas_bawah' => 2, 'batas_atas' => 3, 'urutan' => 2],
                ['label' => '4–5', 'batas_bawah' => 4, 'batas_atas' => 5, 'urutan' => 3],
                ['label' => '>5', 'batas_bawah' => 6, 'batas_atas' => null, 'urutan' => 4],
            ],
            'kondisi_rumah' => [
                ['label' => 'Tidak Layak Huni', 'batas_bawah' => null, 'batas_atas' => null, 'urutan' => 1],
                ['label' => 'Kurang Layak Huni', 'batas_bawah' => null, 'batas_atas' => null, 'urutan' => 2],
                ['label' => 'Layak Huni', 'batas_bawah' => null, 'batas_atas' => null, 'urutan' => 3],
            ],
        ];

        foreach ($bins as $kode => $list) {
            $atribut = AtributKlasifikasi::where('kode', $kode)->first();
            if (! $atribut) {
                continue;
            }

            foreach ($list as $row) {
                KategoriAtribut::updateOrCreate(
                    ['atribut_klasifikasi_id' => $atribut->id, 'label' => $row['label']],
                    $row
                );
            }
        }
    }
}
