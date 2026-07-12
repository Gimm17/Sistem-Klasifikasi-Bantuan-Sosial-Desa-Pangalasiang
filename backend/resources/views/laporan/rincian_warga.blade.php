<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rincian Warga Per Dusun - SIKLAS-NB</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1F2937; font-size: 10px; margin: 0; padding: 10px; }
        .kop-surat { border-bottom: 3px double #1F2937; padding-bottom: 10px; margin-bottom: 14px; text-align: center; }
        .kop-surat h1 { font-size: 15px; margin: 0 0 4px; font-weight: bold; text-transform: uppercase; }
        .kop-surat p { font-size: 9px; margin: 0; color: #4B5563; }
        .judul-laporan { text-align: center; margin-bottom: 14px; }
        .judul-laporan h3 { font-size: 13px; margin: 0 0 4px; text-decoration: underline; text-transform: uppercase; }
        .judul-laporan p { font-size: 9px; margin: 0; color: #4B5563; }
        .dusun-header { background: #1A3B47; color: #FFFFFF; padding: 6px 10px; font-size: 11px; font-weight: bold; margin-top: 14px; margin-bottom: 0; border-radius: 3px 3px 0 0; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.data-table th, table.data-table td { border: 1px solid #96B6C5; padding: 5px 7px; text-align: left; }
        table.data-table th { background: #E6F0F4; color: #1A3B47; font-size: 9px; font-weight: bold; text-align: center; }
        table.data-table td.num { text-align: right; font-family: monospace; }
        table.data-table td.center { text-align: center; }
        .layak { color: #166534; font-weight: bold; background: #D4EDDA; padding: 2px 6px; border-radius: 3px; }
        .tidak-layak { color: #721C24; font-weight: bold; background: #F8D7DA; padding: 2px 6px; border-radius: 3px; }
        .status-badge { padding: 2px 5px; border-radius: 3px; font-size: 8px; background: #EBE9E0; color: #4B5563; text-transform: uppercase; }
        .ttd-box { width: 100%; margin-top: 20px; border-collapse: collapse; page-break-inside: avoid; }
        .ttd-box td { width: 50%; text-align: center; vertical-align: top; padding: 10px; }
        .ttd-space { height: 50px; }
        .ttd-nama { font-weight: bold; text-decoration: underline; margin-bottom: 2px; }
        .ttd-jabatan { font-size: 9px; color: #4B5563; }
        .footer-note { margin-top: 16px; font-size: 8px; color: #6B7280; border-top: 1px solid #D5D3C9; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="kop-surat">
        <h1>PEMERINTAH KABUPATEN DONGGALA — DESA PANGALASIANG<br>SISTEM KLASIFIKASI BANTUAN SOSIAL BERBASIS NAIVE BAYES (SIKLAS-NB)</h1>
        <p>Jalan Poros Palu - Ogoamas, Desa Pangalasiang, Kec. Sojol, Kab. Donggala, Sulawesi Tengah 94371</p>
    </div>

    <div class="judul-laporan">
        <h3>LAPORAN RINCIAN DAFTAR WARGA PER WILAYAH DUSUN</h3>
        <p>Filter Wilayah: {{ $dusunFilter ?: 'Semua Dusun' }} · Dicetak: {{ $dicetak->format('d/m/Y H:i') }}</p>
    </div>

    @foreach($grouped as $dusun => $wargaList)
        <div class="dusun-header">WILAYAH: {{ strtoupper($dusun) }} (Total: {{ $wargaList->count() }} Warga)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25px;">No</th>
                    <th style="width: 100px;">NIK</th>
                    <th>Nama Lengkap</th>
                    <th>Alamat / RT/RW</th>
                    <th style="width: 70px;">Prob. Layak</th>
                    <th style="width: 80px;">Prediksi AI</th>
                    <th style="width: 80px;">Status Akhir</th>
                </tr>
            </thead>
            <tbody>
                @foreach($wargaList as $index => $w)
                    @php $hasil = $w->latestHasil; @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td class="num">{{ $w->nik }}</td>
                        <td style="font-weight: bold; color: #1F2937;">{{ $w->nama }}</td>
                        <td>{{ $w->alamat ?: '-' }}</td>
                        <td class="num font-bold">{{ $hasil ? round($hasil->prob_layak * 100, 1) . '%' : '-' }}</td>
                        <td class="center">
                            @if($hasil)
                                <span class="{{ $hasil->prediksi_kelas === 'layak' ? 'layak' : 'tidak-layak' }}">
                                    {{ $hasil->prediksi_kelas === 'layak' ? 'LAYAK' : 'TIDAK LAYAK' }}
                                </span>
                            @else
                                <span style="color: #9CA3AF;">Belum Diklasifikasi</span>
                            @endif
                        </td>
                        <td class="center">
                            <span class="status-badge">{{ $hasil ? $hasil->status_approval : $w->status_validasi }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    @if($grouped->isEmpty())
        <div style="text-align: center; padding: 30px; color: #9CA3AF;">Tidak ada data warga untuk ditampilkan.</div>
    @endif

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
        <b>Catatan Audit SIKLAS-NB:</b> Dokumen ini merupakan bukti rincian hasil analisis algoritma Naive Bayes Classifier di tingkat desa.
    </div>
</body>
</html>
