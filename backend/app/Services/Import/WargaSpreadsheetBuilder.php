<?php

namespace App\Services\Import;

use App\Models\Warga;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Pembangun spreadsheet XLSX untuk warga: template (rapi + dropdown) & export.
 *
 * Dipakai ImportExportController agar controller tetap ringkas. Satu sumber
 * definisi kolom kanonik (HEADER) — konsisten antar template, export, CSV.
 */
class WargaSpreadsheetBuilder
{
    /** Kolom kanonik (urutan = urutan di file). Kunci = header, nilai = atribut DB. */
    public const HEADER = [
        'nik' => 'nik',
        'nama' => 'nama',
        'alamat' => 'alamat',
        'dusun' => 'dusun',
        'penghasilan_bulanan' => 'penghasilan_bulanan',
        'status_pekerjaan' => 'status_pekerjaan',
        'jumlah_tanggungan' => 'jumlah_tanggungan',
        'kondisi_rumah' => 'kondisi_rumah',
        'periode_data' => 'periode_data',
    ];

    public function __construct(protected WargaImportProcessor $processor) {}

    /**
     * Template kosong siap dipakai: header berstyle, baris dibekukan, autofilter,
     * dropdown (data validation) utk kolom pekerjaan & kondisi_rumah berisi label
     * valid dari DB, satu baris contoh, + lembar "Petunjuk & Kategori".
     */
    public function buildTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Warga');

        $headers = array_keys(self::HEADER);
        $colCount = count($headers);

        // --- header row ---
        foreach ($headers as $i => $header) {
            $sheet->getCell($this->colLetter($i + 1).'1')->setValue($header);
        }
        $lastCol = $this->colLetter($colCount);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($this->headerStyle());

        // --- contoh baris (baris 2) ---
        $sample = [
            '7402010101910001',
            'Contoh Warga',
            'Dusun Contoh',
            'Dusun Tengah',
            1200000,
            'Buruh/Tani',
            4,
            'Kurang Layak Huni',
            now()->format('Y-m-d'),
        ];
        foreach ($sample as $i => $val) {
            $sheet->getCell($this->colLetter($i + 1).'2')->setValue($val);
        }
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray($this->sampleStyle());

        // --- lebar kolom otomatis & freeze & autofilter ---
        foreach (range(1, $colCount) as $i) {
            $sheet->getColumnDimension($this->colLetter($i))->setAutoSize(true);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastCol}1");

        // format rupiah kolom penghasilan
        $pengIdx = array_search('penghasilan_bulanan', $headers) + 1;
        $sheet->getStyle($this->colLetter($pengIdx).'2:'.$this->colLetter($pengIdx).'1000')
            ->getNumberFormat()->setFormatCode('#,##0');

        // --- dropdown utk kolom kategorikal (pekerjaan, kondisi_rumah) ---
        $this->addDropdown($sheet, array_search('status_pekerjaan', $headers) + 1, $this->processor->labels('pekerjaan'), "'Petunjuk & Kategori'");
        $this->addDropdown($sheet, array_search('kondisi_rumah', $headers) + 1, $this->processor->labels('kondisi_rumah'), "'Petunjuk & Kategori'");

        // --- lembar petunjuk & daftar kategori (sumber dropdown) ---
        $this->buildGuideSheet($spreadsheet);

        // pastikan lembar "Data Warga" tetap aktif saat file dibuka.
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Export seluruh warga ke XLSX berstyle.
     */
    public function buildExport(Collection $warga): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Warga');

        $headers = array_keys(self::HEADER);
        $colCount = count($headers);

        foreach ($headers as $i => $header) {
            $sheet->getCell($this->colLetter($i + 1).'1')->setValue($header);
        }
        $lastCol = $this->colLetter($colCount);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($this->headerStyle());

        $pengIdx = array_search('penghasilan_bulanan', $headers) + 1;
        $pengCol = $this->colLetter($pengIdx);

        $row = 2;
        foreach ($warga as $w) {
            $values = [
                $w->nik,
                $w->nama,
                $w->alamat,
                $w->dusun,
                (int) $w->penghasilan_bulanan,
                $w->status_pekerjaan,
                (int) $w->jumlah_tanggungan,
                $w->kondisi_rumah,
                $w->periode_data?->format('Y-m-d'),
            ];
            foreach ($values as $i => $val) {
                $sheet->getCell($this->colLetter($i + 1).$row)->setValue($val);
            }
            $row++;
        }

        foreach (range(1, $colCount) as $i) {
            $sheet->getColumnDimension($this->colLetter($i))->setAutoSize(true);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastCol}1");
        $sheet->getStyle($pengCol.'2:'.$pengCol.$row)->getNumberFormat()->setFormatCode('#,##0');

        return $spreadsheet;
    }

    // =========================================================================
    //  DATA TRAINING
    // =========================================================================

    /** Kolom kanonik data training. */
    public const HEADER_TRAINING = [
        'penghasilan_kategori' => 'penghasilan_kategori',
        'pekerjaan_kategori' => 'pekerjaan_kategori',
        'tanggungan_kategori' => 'tanggungan_kategori',
        'kondisi_rumah_kategori' => 'kondisi_rumah_kategori',
        'label_kelas' => 'label_kelas',
    ];

    /**
     * Template data latih: header, freeze, autofilter, dropdown utk SEMUA 5 kolom
     * (4 kategori + label_kelas), baris contoh, + lembar "Kategori & Label".
     */
    public function buildDataTrainingTemplate(DataTrainingImportProcessor $dt): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Training');

        $headers = array_keys(self::HEADER_TRAINING);
        $colCount = count($headers);

        foreach ($headers as $i => $header) {
            $sheet->getCell($this->colLetter($i + 1).'1')->setValue($header);
        }
        $lastCol = $this->colLetter($colCount);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($this->headerStyle());

        // contoh: profil layak
        $sample = ['>3.000.000', 'PNS/Pegawai', '0–1', 'Layak Huni', 'layak'];
        foreach ($sample as $i => $val) {
            $sheet->getCell($this->colLetter($i + 1).'2')->setValue($val);
        }
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray($this->sampleStyle());

        foreach (range(1, $colCount) as $i) {
            $sheet->getColumnDimension($this->colLetter($i))->setAutoSize(true);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastCol}1");

        // lembar sumber dropdown
        $this->buildTrainingGuideSheet($spreadsheet, $dt);

        // dropdown tiap kolom kategori referensi kolom A..E di guide sheet
        $guideCols = ['A', 'B', 'C', 'D', 'E'];
        $sources = [
            $dt->labels('penghasilan'),
            $dt->labels('pekerjaan'),
            $dt->labels('tanggungan'),
            $dt->labels('kondisi_rumah'),
            DataTrainingImportProcessor::LABEL_KELAS,
        ];
        foreach ($headers as $i => $_) {
            $this->addDropdownRange($sheet, $i + 1, $sources[$i], "'Kategori & Label'", $guideCols[$i]);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /** Export seluruh data training ke XLSX. */
    public function buildDataTrainingExport(Collection $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Training');

        $headers = array_keys(self::HEADER_TRAINING);
        foreach ($headers as $i => $header) {
            $sheet->getCell($this->colLetter($i + 1).'1')->setValue($header);
        }
        $lastCol = $this->colLetter(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($this->headerStyle());

        $row = 2;
        foreach ($rows as $d) {
            $values = [
                $d->penghasilan_kategori, $d->pekerjaan_kategori,
                $d->tanggungan_kategori, $d->kondisi_rumah_kategori, $d->label_kelas,
            ];
            foreach ($values as $i => $val) {
                $sheet->getCell($this->colLetter($i + 1).$row)->setValue($val);
            }
            $row++;
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->colLetter($i))->setAutoSize(true);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastCol}1");

        return $spreadsheet;
    }

    /**
     * Lembar "Kategori & Label": tiap kolom (A..E) berisi daftar label valid utk
     * satu kolom data training (jadi sumber dropdown per kolom).
     */
    protected function buildTrainingGuideSheet(Spreadsheet $spreadsheet, DataTrainingImportProcessor $dt): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Kategori & Label');

        $columns = [
            'A' => ['judul' => 'penghasilan', 'labels' => $dt->labels('penghasilan')],
            'B' => ['judul' => 'pekerjaan', 'labels' => $dt->labels('pekerjaan')],
            'C' => ['judul' => 'tanggungan', 'labels' => $dt->labels('tanggungan')],
            'D' => ['judul' => 'kondisi_rumah', 'labels' => $dt->labels('kondisi_rumah')],
            'E' => ['judul' => 'label_kelas', 'labels' => DataTrainingImportProcessor::LABEL_KELAS],
        ];
        foreach ($columns as $col => $cfg) {
            $sheet->getCell($col.'1')->setValue($cfg['judul']);
            $sheet->getStyle($col.'1')->applyFromArray($this->headerStyle());
            foreach ($cfg['labels'] as $i => $l) {
                $sheet->getCell($col.($i + 2))->setValue($l);
            }
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Tambah dropdown dgn sumber pada KOLOM tertentu di guide sheet
     * (bukan selalu kolom A seperti addDropdown utk warga).
     */
    protected function addDropdownRange(Worksheet $sheet, int $colIdx, array $labels, string $guideSheetName, string $guideCol): void
    {
        if (empty($labels)) {
            return;
        }
        $col = $this->colLetter($colIdx);
        $range = "{$guideSheetName}!{$guideCol}\$1:{$guideCol}\$".count($labels);

        $dv = new DataValidation();
        $dv->setType(DataValidation::TYPE_LIST);
        $dv->setFormula1($range);
        $dv->setAllowBlank(true);
        $dv->setShowDropDown(true);
        $dv->setError('Pilih salah satu nilai dari daftar.');
        $dv->setErrorTitle('Nilai tidak valid');
        $dv->setShowErrorMessage(true);

        $sheet->setDataValidation("{$col}2:{$col}1000", $dv);
    }

    /**
     * Tambah dropdown (data validation list) pada sebuah kolom, referensi range
     * di lembar petunjuk (list dinamis dari label DB).
     */
    protected function addDropdown(Worksheet $sheet, int $colIdx, array $labels, string $guideSheetName): void
    {
        if (empty($labels)) {
            return;
        }
        $col = $this->colLetter($colIdx);
        $range = "{$guideSheetName}!\$A\$1:\$A\$".count($labels);

        $dv = new DataValidation();
        $dv->setType(DataValidation::TYPE_LIST);
        $dv->setFormula1($range);
        $dv->setAllowBlank(true);
        $dv->setShowDropDown(true); // tampilkan panah dropdown di Excel
        $dv->setError('Pilih salah satu kategori dari daftar.');
        $dv->setErrorTitle('Kategori tidak valid');
        $dv->setShowErrorMessage(true);

        $sheet->setDataValidation("{$col}2:{$col}1000", $dv);
    }

    /**
     * Lembar "Petunjuk & Kategori": penjelasan kolom + daftar label valid
     * (yang menjadi sumber dropdown kolom pekerjaan di kolom A).
     */
    protected function buildGuideSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Petunjuk & Kategori');

        // Kolom A = daftar PEKERJAAN (sumber dropdown utk 'status_pekerjaan').
        $sheet->getCell('A1')->setValue('Daftar Pekerjaan Valid');
        $sheet->getStyle('A1')->applyFromArray($this->headerStyle());
        foreach ($this->processor->labels('pekerjaan') as $i => $label) {
            $sheet->getCell('A'.($i + 2))->setValue($label);
        }

        // Kolom C = daftar KONDISI RUMAH.
        $startRow = 1;
        $sheet->getCell('C1')->setValue('Daftar Kondisi Rumah Valid');
        $sheet->getStyle('C1')->applyFromArray($this->headerStyle());
        foreach ($this->processor->labels('kondisi_rumah') as $i => $label) {
            $sheet->getCell('C'.($i + 2))->setValue($label);
        }

        // Kolom E = petunjuk pengisian.
        $sheet->getCell('E1')->setValue('Petunjuk Pengisian');
        $sheet->getStyle('E1')->applyFromArray($this->headerStyle());
        $notes = [
            '1. Isi data warga di lembar "Data Warga", mulai baris 2.',
            '2. Kolom status_pekerjaan & kondisi_rumah punya dropdown — pilih dari daftar.',
            '3. penghasilan_bulanan dalam Rupiah (angka tanpa titik), mis. 1200000.',
            '4. jumlah_tanggungan antara 0–20.',
            '5. nik harus tepat 16 digit angka.',
            '6. periode_data format YYYY-MM-DD. Kosong = tanggal hari ini.',
            '7. Setelah selesai, simpan & unggah file ini lewat menu Import.',
            '8. Baris dengan format keliru akan dilewati (tidak menggagalkan lainnya).',
            '9. Format .xlsx dan .csv keduanya diterima; boleh unggah beberapa file sekaligus.',
        ];
        foreach ($notes as $i => $line) {
            $sheet->getCell('E'.($i + 2))->setValue($line);
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getStyle('E2:E'.(count($notes) + 1))->getAlignment()->setWrapText(true);
    }

    protected function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6F0F4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '96B6C5']]],
        ];
    }

    protected function sampleStyle(): array
    {
        return [
            'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
        ];
    }

    /** Nomor kolom (1-based) -> huruf (A, B, ..., Z, AA). */
    protected function colLetter(int $n): string
    {
        // PhpSpreadsheet menyediakan Coordinate::stringFromColumnIndex, pakai itu.
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($n);
    }
}
