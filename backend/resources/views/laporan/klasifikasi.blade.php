<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #222; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 0 0 12px; color: #555; font-weight: normal; }
        .meta { font-size: 11px; color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #bbb; padding: 5px 7px; text-align: left; }
        th { background: #eef2ff; font-size: 11px; }
        td.num { text-align: right; font-family: monospace; }
        .center { text-align: center; }
        .layak { color: #166534; font-weight: bold; }
        .tidak { color: #444; font-weight: bold; }
        .badge { padding: 1px 5px; border-radius: 3px; font-size: 10px; background: #e5e7eb; }
        .foot { margin-top: 18px; font-size: 10px; color: #888; border-top: 1px solid #ccc; padding-top: 6px; }
    </style>
</head>
<body>
    <h1>SIKLAS-NB — Laporan Hasil Klasifikasi Kelayakan Bantuan Sosial</h1>
    <h2>Desa Pangalasiang, Kecamatan Sojol, Kabupaten Donggala</h2>
    <div class="meta">
        Periode data: {{ $periode ?: 'semua' }} · Status: {{ $statusFilter ?: 'semua' }} ·
        Dicetak: {{ $dicetak->format('d M Y H:i') }} · Total: {{ $hasil->count() }} baris
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>NIK</th>
                <th>Nama</th>
                <th class="center">P(Layak)</th>
                <th class="center">Prediksi</th>
                <th class="center">Approval</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($hasil as $i => $h)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $h->warga?->nik }}</td>
                    <td>{{ $h->warga?->nama }}</td>
                    <td class="num">{{ number_format((float) $h->prob_layak * 100, 2) }}%</td>
                    <td class="center {{ $h->prediksi_kelas === 'layak' ? 'layak' : 'tidak' }}">
                        {{ $h->prediksi_kelas === 'layak' ? 'Layak' : 'Tidak Layak' }}
                    </td>
                    <td class="center"><span class="badge">{{ $h->status_approval }}</span></td>
                    <td>{{ $h->catatan_approval }}</td>
                </tr>
            @endforeach
            @if($hasil->isEmpty())
                <tr><td colspan="7" class="center">Tidak ada data.</td></tr>
            @endif
        </tbody>
    </table>

    <div class="foot">
        Catatan: Hasil klasifikasi Naive Bayes bersifat <b>rekomendasi/pendukung keputusan</b>.
        Keputusan akhir ditetapkan oleh perangkat desa berwenang (role approver).
    </div>
</body>
</html>
