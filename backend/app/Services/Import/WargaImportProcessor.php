<?php

namespace App\Services\Import;

use App\Models\AtributKlasifikasi;
use App\Models\KategoriAtribut;
use App\Models\Warga;
use Illuminate\Support\Collection;

/**
 * Pemroses baris data warga hasil parse file (CSV/XLSX) — SATU sumber
 * kebenaran validasi & penyimpanan, dipakai bersama oleh ImportExportController
 * untuk format CSV maupun XLSX. Mencegah duplikasi aturan validasi antar format.
 *
 * State dipertahankan per-request:
 *  - label valid (pekerjaan, kondisi_rumah) — di-cache sekali dari DB.
 *  - NIK yang sudah ada di DB (flip-set, lookup O(1)).
 *  - NIK yang sudah diproses di sesi impor ini (anti-duplikat antar baris &
 *    antar file pada bulk upload).
 *
 * Kolom yang dikenali: nik, nama, alamat, dusun, penghasilan_bulanan,
 * status_pekerjaan, jumlah_tanggungan, kondisi_rumah, periode_data.
 */
class WargaImportProcessor
{
    /** @var array<string,string[]> [kode_atribut => [label,...]] */
    protected array $labelsCache = [];

    /** @var \Illuminate\Support\Collection<int,string> NIK eksisting (flip-set) */
    protected Collection $existingNik;

    /** @var array<string,true> NIK sudah diproses sesi ini */
    protected array $seenNik = [];

    public function __construct()
    {
        // withTrashed(): NIK pada baris soft-deleted tetap memakai constraint unique
        // di DB, jadi harus ikut dihitung sbg duplikat (jangan biarkan lolos lalu
        // Warga::create melempar QueryException tidak tertangkap -> 500).
        $this->existingNik = Warga::withTrashed()->pluck('nik')->flip();
    }

    /**
     * Proses satu baris asosiatif.
     *
     * @param  array  $row   data baris (key = nama kolom, lowercased & trimmed)
     * @param  int    $rowNum nomor baris utk pelaporan error (relatif file)
     * @param  int    $userId user yang melakukan impor (created_by)
     * @return array{ok:bool, error:?string}
     */
    public function processRow(array $row, int $rowNum, int $userId): array
    {
        $get = fn ($col) => isset($row[$col]) ? trim((string) $row[$col]) : '';

        $nik = $get('nik');
        $nama = $get('nama');
        $penghasilan = $get('penghasilan_bulanan');
        $pekerjaan = $get('status_pekerjaan');
        $tanggungan = $get('jumlah_tanggungan');
        $kondisi = $get('kondisi_rumah');
        $periode = $get('periode_data') ?: now()->format('Y-m-d');

        // --- validasi baris ---
        if (! preg_match('/^\d{16}$/', $nik)) {
            return $this->err($rowNum, 'NIK harus 16 digit.');
        }
        if ($this->existingNik->has($nik) || array_key_exists($nik, $this->seenNik)) {
            return $this->err($rowNum, "NIK {$nik} duplikat.");
        }
        if ($nama === '') {
            return $this->err($rowNum, 'nama kosong.');
        }
        if (! ctype_digit((string) $penghasilan) || (int) $penghasilan < 0) {
            return $this->err($rowNum, 'penghasilan tidak valid.');
        }
        if (! in_array($pekerjaan, $this->labels('pekerjaan'), true)) {
            return $this->err($rowNum, "pekerjaan \"{$pekerjaan}\" bukan kategori valid.");
        }
        if (! ctype_digit((string) $tanggungan) || (int) $tanggungan < 0 || (int) $tanggungan > 20) {
            return $this->err($rowNum, 'tanggungan tidak valid.');
        }
        if (! in_array($kondisi, $this->labels('kondisi_rumah'), true)) {
            return $this->err($rowNum, "kondisi rumah \"{$kondisi}\" bukan kategori valid.");
        }

        // try/catch: error DB-level apa pun (mis. race condition NIK, atau NIK yang
        // lolos pengecekan) jadi baris dilewati, BUKAN menggagalkan seluruh import.
        try {
            Warga::create([
                'nik' => $nik,
                'nama' => $nama,
                'alamat' => $get('alamat'),
                'dusun' => $get('dusun'),
                'penghasilan_bulanan' => (int) $penghasilan,
                'status_pekerjaan' => $pekerjaan,
                'jumlah_tanggungan' => (int) $tanggungan,
                'kondisi_rumah' => $kondisi,
                'periode_data' => $periode,
                'status_validasi' => 'draft',
                'created_by' => $userId,
            ]);
        } catch (\Throwable $e) {
            return $this->err($rowNum, 'gagal menyimpan ('.$e->getMessage().').');
        }

        $this->seenNik[$nik] = true;

        return ['ok' => true, 'error' => null];
    }

    /**
     * Daftar label kategori valid untuk sebuah atribut (di-cache).
     *
     * @return string[]
     */
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

    /**
     * Tambah NIK hasil impor ke set eksisting — berguna agar ekspor/ulang
     * dalam request yang sama tetap mendeteksi duplikat.
     */
    public function markSeen(string $nik): void
    {
        $this->seenNik[$nik] = true;
    }

    protected function err(int $rowNum, string $msg): array
    {
        return ['ok' => false, 'error' => "Baris {$rowNum}: {$msg}"];
    }
}
