<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DataTraining;
use App\Models\Warga;
use App\Services\Import\DataTrainingImportProcessor;
use App\Services\Import\SpreadsheetReader;
use App\Services\Import\WargaImportProcessor;
use App\Services\Import\WargaSpreadsheetBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import/Export — warga & data training. Format CSV & XLSX, bulk upload multi-file.
 *
 * Fitur TAMBAHAN operasional (nice-to-have) agar input data lebih cepat; BUKAN
 * mengubah atribut/metode klasifikasi Naive Bayes. Data training tetap hanya
 * dikelola admin/superadmin (sama seperti CRUD-nya).
 *
 * Kolom kanonik warga (lihat WargaSpreadsheetBuilder::HEADER) mencakup `dusun`.
 * Pembacaan file difaktorkan ke SpreadsheetReader (netral domain).
 */
class ImportExportController extends Controller
{
    public function __construct(
        protected WargaImportProcessor $wargaProcessor,
        protected WargaSpreadsheetBuilder $builder,
        protected SpreadsheetReader $reader,
        protected DataTrainingImportProcessor $trainingProcessor,
    ) {}

    // =========================================================================
    //  WARGA
    // =========================================================================

    /**
     * Export data warga.
     *  ?format=xlsx|csv (default csv) | ?status_validasi= | ?dusun= (filter subset).
     */
    public function exportWarga(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'csv'));
        $query = Warga::query()->orderBy('id');

        if ($s = $request->query('status_validasi')) {
            $query->where('status_validasi', $s);
        }
        if ($d = $request->query('dusun')) {
            $query->where('dusun', $d);
        }

        if ($format === 'xlsx') {
            return $this->downloadSpreadsheet(
                $this->builder->buildExport($query->get()),
                'warga_'.now()->format('Ymd_His').'.xlsx'
            );
        }

        // --- CSV (default) ---
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['nik', 'nama', 'alamat', 'dusun', 'penghasilan_bulanan', 'status_pekerjaan', 'jumlah_tanggungan', 'kondisi_rumah', 'periode_data', 'status_validasi']);

        foreach ($query->cursor() as $w) {
            fputcsv($handle, [
                $w->nik, $w->nama, $w->alamat, $w->dusun,
                $w->penghasilan_bulanan, $w->status_pekerjaan, $w->jumlah_tanggungan,
                $w->kondisi_rumah, $w->periode_data?->format('Y-m-d'), $w->status_validasi,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::make("\xEF\xBB\xBF".$csv, 200, [ // BOM agar Excel baca UTF-8
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="warga_'.now()->format('Ymd_His').'.csv"',
        ]);
    }

    /** Template warga. ?format=xlsx|csv (default XLSX rapi). */
    public function templateWarga(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $csv = "nik,nama,alamat,dusun,penghasilan_bulanan,status_pekerjaan,jumlah_tanggungan,kondisi_rumah,periode_data\n".
                "7402010101910001,Contoh Warga,Dusun Contoh,Dusun Tengah,1200000,Buruh/Tani,4,Kurang Layak Huni,2026-07-01\n";

            return Response::make("\xEF\xBB\xBF".$csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="template_warga.csv"',
            ]);
        }

        return $this->downloadSpreadsheet($this->builder->buildTemplate(), 'template_warga.xlsx');
    }

    /**
     * Import warga — BANYAK file (csv &/atau xlsx). NIK ganda (DB/antar baris/file)
     * dilewati. Detail per file + total dikembalikan; batch dicatat di audit log.
     */
    public function importWarga(Request $request): JsonResponse
    {
        return $this->importGeneric(
            $request,
            processor: $this->wargaProcessor,
            userId: $request->user()->id,
            action: 'warga.import',
            description: 'Import warga',
        );
    }

    // =========================================================================
    //  DATA TRAINING
    // =========================================================================

    /** Template data training. ?format=xlsx|csv (default XLSX rapi + dropdown). */
    public function templateDataTraining(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $csv = "penghasilan_kategori,pekerjaan_kategori,tanggungan_kategori,kondisi_rumah_kategori,label_kelas\n".
                ">3.000.000,PNS/Pegawai,0–1,Layak Huni,layak\n";

            return Response::make("\xEF\xBB\xBF".$csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="template_data_training.csv"',
            ]);
        }

        return $this->downloadSpreadsheet(
            $this->builder->buildDataTrainingTemplate($this->trainingProcessor),
            'template_data_training.xlsx'
        );
    }

    /** Export data training. ?format=xlsx|csv (default csv) | ?label_kelas=. */
    public function exportDataTraining(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'csv'));
        $query = DataTraining::query()->orderBy('id');

        if ($l = $request->query('label_kelas')) {
            $query->where('label_kelas', $l);
        }

        if ($format === 'xlsx') {
            return $this->downloadSpreadsheet(
                $this->builder->buildDataTrainingExport($query->get()),
                'data_training_'.now()->format('Ymd_His').'.xlsx'
            );
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['penghasilan_kategori', 'pekerjaan_kategori', 'tanggungan_kategori', 'kondisi_rumah_kategori', 'label_kelas']);
        foreach ($query->cursor() as $d) {
            fputcsv($handle, [$d->penghasilan_kategori, $d->pekerjaan_kategori, $d->tanggungan_kategori, $d->kondisi_rumah_kategori, $d->label_kelas]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::make("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="data_training_'.now()->format('Ymd_His').'.csv"',
        ]);
    }

    /**
     * Import data training — BANYAK file. Tiap baris valid langsung disimpan
     * (baris berlabel identik sah & memperkuat probabilitas). Baris keliru dilewati.
     */
    public function importDataTraining(Request $request): JsonResponse
    {
        return $this->importGeneric(
            $request,
            processor: $this->trainingProcessor,
            userId: null,
            action: 'data_training.import',
            description: 'Import data training',
        );
    }

    // =========================================================================
    //  INTI BERSAMA
    // =========================================================================

    /**
     * Logika import generik: validasi files[], baca tiap file (SpreadsheetReader),
     * proses tiap baris via processor->processRow(), agregat per-file + total,
     * catat audit log.
     *
     * @param  object  $processor  WargaImportProcessor | DataTrainingImportProcessor
     */
    protected function importGeneric(Request $request, object $processor, ?int $userId, string $action, string $description): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $perFile = [];
        $totalImported = 0;
        $totalErrors = 0;
        $allErrors = [];

        foreach ($request->file('files') as $file) {
            $rows = $this->reader->read($file);

            if (isset($rows['__error'])) {
                $perFile[] = ['file' => $file->getClientOriginalName(), 'diimpor' => 0, 'gagal' => 0, 'catatan' => $rows['__error']];
                $allErrors[] = "{$file->getClientOriginalName()}: {$rows['__error']}";
                continue;
            }

            $imported = 0;
            $errors = [];
            foreach ($rows as $i => $assoc) {
                // indeks 0 = baris data pertama = baris file ke-2 (baris 1 = header).
                $rowNum = $i + 2;
                // WargaImportProcessor butuh userId; DataTrainingImportProcessor mengabaikannya.
                $result = $userId !== null
                    ? $processor->processRow($assoc, $rowNum, $userId)
                    : $processor->processRow($assoc, $rowNum);

                if ($result['ok']) {
                    $imported++;
                } else {
                    $errors[] = $result['error'];
                }
            }

            $perFile[] = [
                'file' => $file->getClientOriginalName(),
                'diimpor' => $imported,
                'gagal' => count($errors),
                'detail_gagal' => array_slice($errors, 0, 20),
            ];
            $totalImported += $imported;
            $totalErrors += count($errors);
            $allErrors = array_merge($allErrors, array_slice($errors, 0, 50));
        }

        ActivityLog::record(
            $action,
            null,
            "{$description}: {$totalImported} diimpor dari ".count($perFile).' file',
            ['diimpor' => $totalImported, 'gagal' => $totalErrors, 'file' => count($perFile)],
            $request->user()
        );

        return response()->json([
            'message' => 'Import selesai.',
            'diimpor' => $totalImported,
            'gagal' => $totalErrors,
            'detail_gagal' => array_slice($allErrors, 0, 50),
            'per_file' => $perFile,
        ]);
    }

    /** Stream Spreadsheet sebagai unduhan .xlsx. */
    protected function downloadSpreadsheet($spreadsheet, string $filename)
    {
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
