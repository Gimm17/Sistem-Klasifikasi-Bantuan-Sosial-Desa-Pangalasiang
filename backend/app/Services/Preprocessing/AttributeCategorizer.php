<?php

namespace App\Services\Preprocessing;

use App\Models\AtributKlasifikasi;
use App\Models\KategoriAtribut;
use RuntimeException;

/**
 * Mengubah nilai MENTAH (rupiah, jumlah tanggungan, teks kategorikal) menjadi
 * LABEL kategori siap dipakai Naive Bayes, berdasarkan konfigurasi bin di tabel
 * kategori_atribut (dikelola admin, modul Fase 3).
 *
 * - Atribut kategorikal (pekerjaan, kondisi_rumah): nilai sudah berupa label -> dilewatkan.
 * - Atribut numerik (penghasilan, tanggungan): dicari bin yang memuat nilai.
 *
 * Cache property menyimpan hasil query per atribut -> eliminasi N+1 saat batch.
 */
class AttributeCategorizer
{
    /** @var array<string, \App\Models\AtributKlasifikasi> [kode => model] */
    protected array $atributCache = [];

    /** @var array<string, \Illuminate\Support\Collection> [kode => Collection of KategoriAtribut] */
    protected array $kategoriCache = [];

    public function categorize(string $atributKode, mixed $nilaiMentah): string
    {
        $atribut = $this->getAtribut($atributKode);

        if ($atribut->tipe === 'kategorikal') {
            return (string) $nilaiMentah;
        }

        // numerik: cari bin yang mencakup nilai.
        $kategori = $this->getKategoriCollection($atributKode, $atribut->id)
            ->filter(fn ($k) => $this->binCovers($k, $nilaiMentah))
            ->first();

        if (! $kategori) {
            throw new RuntimeException(
                "Nilai {$nilaiMentah} untuk atribut '{$atributKode}' tidak masuk kategori mana pun. "
                .'Periksa konfigurasi bin (kemungkinan ada lubang/ gap).'
            );
        }

        return $kategori->label;
    }

    /**
     * Kategorisasi 4 atribut inti sebuah warga sekaligus.
     */
    public function categorizeAll(array $raw): array
    {
        return [
            'penghasilan' => $this->categorize('penghasilan', $raw['penghasilan']),
            'pekerjaan' => $this->categorize('pekerjaan', $raw['pekerjaan']),
            'tanggungan' => $this->categorize('tanggungan', $raw['tanggungan']),
            'kondisi_rumah' => $this->categorize('kondisi_rumah', $raw['kondisi_rumah']),
        ];
    }

    protected function getAtribut(string $kode): AtributKlasifikasi
    {
        if (! isset($this->atributCache[$kode])) {
            $this->atributCache[$kode] = AtributKlasifikasi::where('kode', $kode)->firstOrFail();
        }

        return $this->atributCache[$kode];
    }

    protected function getKategoriCollection(string $kode, int $atributId): \Illuminate\Support\Collection
    {
        if (! isset($this->kategoriCache[$kode])) {
            $this->kategoriCache[$kode] = KategoriAtribut::where('atribut_klasifikasi_id', $atributId)
                ->orderBy('urutan')
                ->get();
        }

        return $this->kategoriCache[$kode];
    }

    /**
     * Apakah suatu bin matematis mencakup nilai?
     */
    protected function binCovers(KategoriAtribut $kategori, mixed $nilai): bool
    {
        $bawah = $kategori->batas_bawah;
        $atas = $kategori->batas_atas;

        if ($bawah !== null && $nilai < $bawah) {
            return false;
        }
        if ($atas !== null && $nilai > $atas) {
            return false;
        }

        return true;
    }
}
