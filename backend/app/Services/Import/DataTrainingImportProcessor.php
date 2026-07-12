<?php

namespace App\Services\Import;

use App\Models\AtributKlasifikasi;
use App\Models\DataTraining;
use App\Models\KategoriAtribut;

/**
 * Pemroses baris data latih berlabel hasil parse file (CSV/XLSX) — dipakai
 * ImportExportController utk import data_training. Baris berlabel duplikat pada
 * data training sah (memperkuat estimasi probabilitas), jadi TIDAK ada anti-duplikat:
 * tiap baris valid langsung disimpan.
 *
 * Kolom: penghasilan_kategori, pekerjaan_kategori, tanggungan_kategori,
 * kondisi_rumah_kategori, label_kelas (layak|tidak_layak).
 */
class DataTrainingImportProcessor
{
    /** @var array<string,string[]> [kode_atribut => [label,...]] */
    protected array $labelsCache = [];

    /** Kolom kategori yang divalidasi terhadap label aktif tiap atribut. */
    public const KATEGORI_KOLOM = [
        'penghasilan_kategori' => 'penghasilan',
        'pekerjaan_kategori' => 'pekerjaan',
        'tanggungan_kategori' => 'tanggungan',
        'kondisi_rumah_kategori' => 'kondisi_rumah',
    ];

    public const LABEL_KELAS = ['layak', 'tidak_layak'];

    /**
     * Proses satu baris asosiatif.
     *
     * @return array{ok:bool, error:?string}
     */
    public function processRow(array $row, int $rowNum): array
    {
        $get = fn ($col) => isset($row[$col]) ? trim((string) $row[$col]) : '';

        foreach (self::KATEGORI_KOLOM as $kolom => $kode) {
            $val = $get($kolom);
            if ($val === '') {
                return $this->err($rowNum, "kolom {$kolom} kosong.");
            }
            if (! in_array($val, $this->labels($kode), true)) {
                return $this->err($rowNum, "{$kolom} \"{$val}\" bukan kategori valid.");
            }
        }

        $label = $get('label_kelas');
        if (! in_array($label, self::LABEL_KELAS, true)) {
            return $this->err($rowNum, "label_kelas \"{$label}\" harus 'layak' atau 'tidak_layak'.");
        }

        try {
            DataTraining::create([
                'penghasilan_kategori' => $get('penghasilan_kategori'),
                'pekerjaan_kategori' => $get('pekerjaan_kategori'),
                'tanggungan_kategori' => $get('tanggungan_kategori'),
                'kondisi_rumah_kategori' => $get('kondisi_rumah_kategori'),
                'label_kelas' => $label,
            ]);
        } catch (\Throwable $e) {
            return $this->err($rowNum, 'gagal menyimpan ('.$e->getMessage().').');
        }

        return ['ok' => true, 'error' => null];
    }

    /** @return string[] */
    public function labels(string $kode): array
    {
        if (isset($this->labelsCache[$kode])) {
            return $this->labelsCache[$kode];
        }

        $atribut = AtributKlasifikasi::where('kode', $kode)->first();
        $labels = $atribut
            ? KategoriAtribut::where('atribut_klasifikasi_id', $atribut->id)->pluck('label')->all()
            : [];

        return $this->labelsCache[$kode] = $labels;
    }

    protected function err(int $rowNum, string $msg): array
    {
        return ['ok' => false, 'error' => "Baris {$rowNum}: {$msg}"];
    }
}
