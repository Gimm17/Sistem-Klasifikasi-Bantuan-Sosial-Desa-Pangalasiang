<?php

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

/**
 * Pembaca file spreadsheet generik (CSV & XLSX/XLS) → daftar baris asosiatif.
 * Netral domain: dipakai utk import warga maupun data training (dll).
 *
 * - Header ditentukan dari baris pertama (lowercased & trimmed).
 * - Nilai numerik dibersihkan (cegah notasi ilmiah / presisi hilang).
 * - Kolom tanggal (periode_data) dari format serial Excel -> Y-m-d.
 * - Baris kosong dilewati.
 *
 * Mengembalikan ['__error' => pesan] bila file tak bisa dibaca / header invalid.
 */
class SpreadsheetReader
{
    /**
     * @return array<int,array<string,string>>|array<string,string>
     */
    public function read(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return in_array($ext, ['csv', 'txt'], true)
            ? $this->readCsv($file)
            : $this->readSpreadsheet($file);
    }

    /** @return array<int,array<string,string>>|array<string,string> */
    protected function readCsv(UploadedFile $upload): array
    {
        $file = $upload->openFile('r');
        $header = $file->fgetcsv();
        if (! $header) {
            return ['__error' => 'File kosong / header tidak terbaca.'];
        }
        $idx = array_change_key_case(array_flip(array_map('trim', $header)));

        $rows = [];
        while (! $file->eof()) {
            $row = $file->fgetcsv();
            if (! $row || count(array_filter($row, fn ($x) => trim((string) $x) !== '')) === 0) {
                continue;
            }
            $rows[] = $this->normalizeRow($row, $idx);
        }

        return $rows;
    }

    /** @return array<int,array<string,string>>|array<string,string> */
    protected function readSpreadsheet(UploadedFile $upload): array
    {
        try {
            $reader = IOFactory::createReaderForFile($upload->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($upload->getRealPath());
        } catch (\Throwable $e) {
            return ['__error' => 'File spreadsheet tidak bisa dibaca ('.$e->getMessage().').'];
        }

        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        if (empty($data)) {
            return ['__error' => 'Sheet kosong.'];
        }

        $header = array_map(fn ($v) => strtolower(trim((string) $v)), $data[0]);
        $idx = array_flip($header);

        $rows = [];
        $count = count($data);
        for ($r = 1; $r < $count; $r++) {
            $raw = $data[$r];
            if (count(array_filter($raw, fn ($x) => trim((string) $x) !== '')) === 0) {
                continue;
            }
            $rows[] = $this->normalizeRow($raw, $idx);
        }

        return $rows;
    }

    /**
     * @param  array  $raw   baris indeks-nilai
     * @param  array<string,int>  $idx  col-name => index
     * @return array<string,string>
     */
    protected function normalizeRow(array $raw, array $idx): array
    {
        $assoc = [];
        foreach ($idx as $col => $i) {
            $val = $raw[$i] ?? '';

            if ($col === 'periode_data' && is_numeric($val) && (float) $val > 20000) {
                try {
                    $val = SpreadsheetDate::excelToDateTimeObject((float) $val)->format('Y-m-d');
                } catch (\Throwable) {
                    // biarkan nilai mentah
                }
            }

            $assoc[$col] = $this->cleanScalar($val);
        }

        return $assoc;
    }

    protected function cleanScalar(mixed $val): string
    {
        if ($val === null) {
            return '';
        }
        if (is_float($val) || is_int($val)) {
            return (string) (fmod((float) $val, 1.0) === 0.0 ? (int) $val : $val);
        }

        return trim((string) $val);
    }
}
