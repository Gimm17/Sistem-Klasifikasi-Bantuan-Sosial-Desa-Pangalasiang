<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekapitulasi Per Dusun - SIKLAS-NB</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1F2937; font-size: 11px; margin: 0; padding: 10px; }
        .kop-surat { border-bottom: 3px double #1F2937; padding-bottom: 12px; margin-bottom: 16px; text-align: center; }
        .kop-surat h1 { font-size: 16px; margin: 0 0 4px; font-weight: bold; text-transform: uppercase; }
        .kop-surat h2 { font-size: 14px; margin: 0 0 4px; font-weight: bold; }
        .kop-surat p { font-size: 10px; margin: 0; color: #4B5563; }
        .judul-laporan { text-align: center; margin-bottom: 16px; }
        .judul-laporan h3 { font-size: 14px; margin: 0 0 4px; text-decoration: underline; text-transform: uppercase; }
        .judul-laporan p { font-size: 10px; margin: 0; color: #4B5563; }
        .stats-grid { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .stats-grid td { width: 25%; padding: 8px; border: 1px solid #D5D3C9; background: #F8F7F2; text-align: center; }
        .stats-label { font-size: 9px; color: #4B5563; text-transform: uppercase; margin-bottom: 4px; display: block; font-weight: bold; }
        .stats-value { font-size: 14px; font-weight: bold; color: #1A3B47; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #96B6C5; padding: 6px 8px; text-align: left; }
        table.data-table th { background: #E6F0F4; color: #1A3B47; font-size: 10px; font-weight: bold; text-align: center; }
        table.data-table td.num { text-align: right; font-family: monospace; }
        table.data-table td.center { text-align: center; }
        table.data-table tfoot th { background: #F8F7F2; font-weight: bold; text-align: right; }
        .pct-bar-wrap { width: 60px; background: #EBE9E0; height: 8px; border-radius: 4px; display: inline-block; vertical-align: middle; margin-left: 6px; }
        .pct-bar { height: 8px; border-radius: 4px; background: #27AE60; }
        .ttd-box { width: 100%; margin-top: 30px; border-collapse: collapse; page-break-inside: avoid; }
        .ttd-box td { width: 50%; text-align: center; vertical-align: top; padding: 10px; }
        .ttd-space { height: 60px; }
        .ttd-nama { font-weight: bold; text-decoration: underline; margin-bottom: 2px; }
        .ttd-jabatan { font-size: 10px; color: #4B5563; }
        .footer-note { margin-top: 20px; font-size: 9px; color: #6B7280; border-top: 1px solid #D5D3C9; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="kop-surat">
        <h1>PEMERINTAH KABUPATEN DONGGALA<br>KECAMATAN SOJOL<br>DESA PANGALASIANG</h1>
        <p>Jalan Poros Palu - Ogoamas, Desa Pangalasiang, Kec. Sojol, Kab. Donggala, Sulawesi Tengah 94371</p>
    </div>

    <div class="judul-laporan">
        <h3>REKAPITULASI HASIL KLASIFIKASI KELAYAKAN BANTUAN SOSIAL PER DUSUN</h3>
        <p>Sistem Klasifikasi Bantuan Sosial Berbasis Naive Bayes (SIKLAS-NB) — Dicetak: {{ $dicetak->format('d/m/Y H:i') }}</p>
    </div>

    <table class="stats-grid">
        <tr>
            <td>
                <span class="stats-label">Total Dusun</span>
                <span class="stats-value">{{ $data['total_dusun'] }} Wilayah</span>
            </td>
            <td>
                <span class="stats-label">Total Warga Terdata</span>
                <span class="stats-value">{{ $data['total_warga'] }} Jiwa</span>
            </td>
            <td>
                <span class="stats-label">Dusun Tertinggi (Layak)</span>
                <span class="stats-value">{{ $data['dusun_tertinggi']['dusun'] ?? '-' }} ({{ $data['dusun_tertinggi']['pct_layak'] ?? 0 }}%)</span>
            </td>
            <td>
                <span class="stats-label">Rata-rata Probabilitas</span>
                <span class="stats-value">{{ $data['rata_rata_prob_layak'] }}%</span>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Nama Dusun / Wilayah</th>
                <th style="width: 80px;">Total Warga</th>
                <th style="width: 70px;">Layak</th>
                <th style="width: 70px;">Tidak Layak</th>
                <th style="width: 110px;">Persentase Layak</th>
                <th style="width: 90px;">Rata-rata Prob.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['detail'] as $index => $row)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td style="font-weight: bold; color: #1A3B47;">{{ $row['dusun'] }}</td>
                    <td class="num">{{ $row['total_warga'] }}</td>
                    <td class="num" style="color: #27AE60; font-weight: bold;">{{ $row['layak'] }}</td>
                    <td class="num" style="color: #6B7280;">{{ $row['tidak_layak'] }}</td>
                    <td class="num">
                        {{ $row['pct_layak'] }}%
                        <div class="pct-bar-wrap">
                            <div class="pct-bar" style="width: {{ $row['pct_layak'] }}%; background: {{ $row['pct_layak'] >= 70 ? '#27AE60' : ($row['pct_layak'] >= 40 ? '#F39C12' : '#DC3545') }};"></div>
                        </div>
                    </td>
                    <td class="num">{{ $row['avg_prob_layak'] }}%</td>
                </tr>
            @endforeach
            @if(empty($data['detail']))
                <tr><td colspan="7" class="center">Belum ada data rekapitulasi dusun.</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">TOTAL KESELURUHAN</th>
                <th class="num">{{ $data['total_warga'] }}</th>
                <th class="num" style="color: #27AE60;">{{ $data['total_layak'] }}</th>
                <th class="num">{{ $data['total_tidak_layak'] }}</th>
                <th class="num">{{ $data['total_warga'] > 0 ? round(($data['total_layak'] / $data['total_warga']) * 100, 1) : 0 }}%</th>
                <th class="num">{{ $data['rata_rata_prob_layak'] }}%</th>
            </tr>
        </tfoot>
    </table>

    <table class="ttd-box">
        <tr>
            <td>
                <p class="ttd-jabatan">Mengetahui,<br>Sekretaris Desa Pangalasiang</p>
                <div class="ttd-space"></div>
                <p class="ttd-nama">( ........................................ )</p>
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
        <b>Catatan Audit SIKLAS-NB:</b> Dokumen ini dihasilkan secara otomatis oleh sistem berdasarkan model algoritma Naive Bayes Classifier. Data telah diverifikasi melalui proses penyaringan dan validasi perangkat desa berwenang.
    </div>
</body>
</html>
