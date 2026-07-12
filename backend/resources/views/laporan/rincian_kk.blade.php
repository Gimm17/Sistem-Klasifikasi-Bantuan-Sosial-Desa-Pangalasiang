<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rincian KK - {{ $warga->nama }} (SIKLAS-NB)</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1F2937; font-size: 11px; margin: 0; padding: 15px; }
        .kop-surat { border-bottom: 3px double #1F2937; padding-bottom: 12px; margin-bottom: 18px; text-align: center; }
        .kop-surat h1 { font-size: 16px; margin: 0 0 4px; font-weight: bold; text-transform: uppercase; }
        .kop-surat p { font-size: 10px; margin: 0; color: #4B5563; }
        .judul-laporan { text-align: center; margin-bottom: 18px; }
        .judul-laporan h3 { font-size: 14px; margin: 0 0 4px; text-decoration: underline; text-transform: uppercase; }
        .judul-laporan p { font-size: 10px; margin: 0; color: #4B5563; }
        .section-title { font-size: 12px; font-weight: bold; color: #1A3B47; background: #E6F0F4; padding: 6px 10px; margin-top: 16px; margin-bottom: 8px; border-left: 4px solid #96B6C5; }
        table.profile-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.profile-table td { padding: 6px 8px; border-bottom: 1px solid #EBE9E0; }
        table.profile-table td.label { width: 35%; font-weight: bold; color: #4B5563; }
        table.profile-table td.value { width: 65%; color: #1F2937; font-weight: bold; }
        table.attr-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.attr-table th, table.attr-table td { border: 1px solid #96B6C5; padding: 6px 8px; text-align: left; }
        table.attr-table th { background: #E6F0F4; color: #1A3B47; font-size: 10px; font-weight: bold; }
        table.attr-table td.center { text-align: center; }
        .result-box { border: 2px solid #1A3B47; background: #F8F7F2; padding: 12px; border-radius: 6px; text-align: center; margin-top: 16px; margin-bottom: 20px; }
        .result-label { font-size: 10px; color: #4B5563; text-transform: uppercase; font-weight: bold; }
        .result-val { font-size: 18px; font-weight: bold; margin: 6px 0; }
        .layak { color: #166534; }
        .tidak-layak { color: #721C24; }
        .result-prob { font-size: 11px; color: #4B5563; }
        .ttd-box { width: 100%; margin-top: 30px; border-collapse: collapse; page-break-inside: avoid; }
        .ttd-box td { width: 50%; text-align: center; vertical-align: top; padding: 10px; }
        .ttd-space { height: 60px; }
        .ttd-nama { font-weight: bold; text-decoration: underline; margin-bottom: 2px; }
        .ttd-jabatan { font-size: 10px; color: #4B5563; }
        .footer-note { margin-top: 25px; font-size: 9px; color: #6B7280; border-top: 1px solid #D5D3C9; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="kop-surat">
        <h1>PEMERINTAH KABUPATEN DONGGALA — DESA PANGALASIANG<br>SISTEM KLASIFIKASI BANTUAN SOSIAL BERBASIS NAIVE BAYES (SIKLAS-NB)</h1>
        <p>Jalan Poros Palu - Ogoamas, Desa Pangalasiang, Kec. Sojol, Kab. Donggala, Sulawesi Tengah 94371</p>
    </div>

    <div class="judul-laporan">
        <h3>LEMBAR HASIL ANALISIS KELAYAKAN PER KARTU KELUARGA (KK)</h3>
        <p>Nomor Identitas Kependudukan (NIK): {{ $warga->nik }} · Dicetak: {{ $dicetak->format('d/m/Y H:i') }}</p>
    </div>

    <div class="section-title">A. IDENTITAS KELUARGA / CALON PENERIMA</div>
    <table class="profile-table">
        <tr>
            <td class="label">Nama Lengkap Kepala Keluarga</td>
            <td class="value">{{ $warga->nama }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Induk Kependudukan (NIK)</td>
            <td class="value">{{ $warga->nik }}</td>
        </tr>
        <tr>
            <td class="label">Wilayah Dusun / Alamat</td>
            <td class="value">{{ $warga->dusun ?: 'Dusun I' }} — {{ $warga->alamat ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Periode Pendataan</td>
            <td class="value">{{ $warga->periode_data ? \Carbon\Carbon::parse($warga->periode_data)->format('F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Petugas Pendata</td>
            <td class="value">{{ $warga->createdBy?->name ?: 'Administrator' }}</td>
        </tr>
    </table>

    <div class="section-title">B. RINCIAN ATRIBUT SOSIAL EKONOMI</div>
    <table class="attr-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="center">No</th>
                <th>Atribut Penilaian (Naive Bayes)</th>
                <th>Nilai Aktual Warga</th>
                <th>Kategori Sistem</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">1</td>
                <td>Penghasilan Bulanan</td>
                <td>Rp {{ number_format($warga->penghasilan_bulanan, 0, ',', '.') }} / bulan</td>
                <td style="font-weight: bold; color: #1A3B47;">{{ $hasil?->kategori_input['penghasilan_bulanan'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="center">2</td>
                <td>Status Pekerjaan Utama</td>
                <td>{{ $warga->status_pekerjaan }}</td>
                <td style="font-weight: bold; color: #1A3B47;">{{ $hasil?->kategori_input['status_pekerjaan'] ?? $warga->status_pekerjaan }}</td>
            </tr>
            <tr>
                <td class="center">3</td>
                <td>Jumlah Tanggungan Keluarga</td>
                <td>{{ $warga->jumlah_tanggungan }} Orang</td>
                <td style="font-weight: bold; color: #1A3B47;">{{ $hasil?->kategori_input['jumlah_tanggungan'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="center">4</td>
                <td>Kondisi Tempat Tinggal / Rumah</td>
                <td>{{ $warga->kondisi_rumah }}</td>
                <td style="font-weight: bold; color: #1A3B47;">{{ $hasil?->kategori_input['kondisi_rumah'] ?? $warga->kondisi_rumah }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">C. HASIL PERHITUNGAN ALGORITMA NAIVE BAYES</div>
    @if($hasil)
        <div class="result-box">
            <div class="result-label">REKOMENDASI SISTEM SIKLAS-NB (MODEL v{{ $hasil->model_version }})</div>
            <div class="result-val {{ $hasil->prediksi_kelas === 'layak' ? 'layak' : 'tidak-layak' }}">
                {{ $hasil->prediksi_kelas === 'layak' ? 'LAYAK MENERIMA BANTUAN SOSIAL' : 'TIDAK LAYAK MENERIMA BANTUAN SOSIAL' }}
            </div>
            <div class="result-prob">
                Probabilitas Layak: <b>{{ round($hasil->prob_layak * 100, 2) }}%</b> &nbsp;|&nbsp; 
                Probabilitas Tidak Layak: <b>{{ round($hasil->prob_tidak_layak * 100, 2) }}%</b>
                <br>
                Status Keputusan (Approval): <b>{{ strtoupper($hasil->status_approval) }}</b>
                @if($hasil->catatan_approval)
                    <br>Catatan Verifikator: <i>"{{ $hasil->catatan_approval }}"</i>
                @endif
            </div>
        </div>
    @else
        <div class="result-box" style="border-color: #9CA3AF;">
            <div class="result-label">STATUS KLASIFIKASI</div>
            <div class="result-val" style="color: #6B7280; font-size: 14px;">BELUM DIKLASIFIKASI OLEH SISTEM</div>
        </div>
    @endif

    <table class="ttd-box">
        <tr>
            <td>
                <p class="ttd-jabatan">Verifikator / Approver,<br>Sekretaris Desa Pangalasiang</p>
                <div class="ttd-space"></div>
                <p class="ttd-nama">( {{ $hasil?->approver?->name ?: '........................................' }} )</p>
                <p class="ttd-jabatan">NIP. ....................................</p>
            </td>
            <td>
                <p class="ttd-jabatan">Pangalasiang, {{ date('d F Y') }}<br>Kepala Desa Pangalasiang</p>
                <div class="ttd-space"></div>
                <p class="ttd-nama">( ........................................ )</p>
                <p class="ttd-jabatan">NIP. ....................................</p>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        <b>Catatan Audit SIKLAS-NB:</b> Lembar rincian ini adalah bukti analisis objektif berbasis data kependudukan nyata Desa Pangalasiang yang diolah dengan metode Explainable AI (XAI) Naive Bayes.
    </div>
</body>
</html>
