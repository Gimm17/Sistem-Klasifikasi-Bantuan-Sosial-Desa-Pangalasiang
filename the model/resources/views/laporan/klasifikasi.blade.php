<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Laporan Klasifikasi - SIKLAS-NB</title>
<style>
  @page { margin: 20mm; }
  body { font-family: 'Inter', Arial, sans-serif; font-size: 11px; color: #1F2937; line-height: 1.5; }
  .kop { text-align: center; margin-bottom: 15px; }
  .kop h2 { margin: 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; }
  .kop p { margin: 2px 0; font-size: 11px; color: #4B5563; }
  .kop hr { border: none; border-top: 2px solid #1F2937; margin: 10px 0; }
  h4 { font-size: 14px; margin: 15px 0 8px; border-bottom: 1px solid #D5D3C9; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 10px; }
  th { background: #E6F0F4; padding: 6px 8px; text-align: left; border: 1px solid #D5D3C9; font-size: 9px; text-transform: uppercase; }
  td { padding: 5px 8px; border: 1px solid #D5D3C9; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }
  .layak { color: #155724; font-weight: bold; }
  .tidak-layak { color: #721C24; font-weight: bold; }
  .footer { margin-top: 30px; text-align: right; font-size: 10px; }
  .footer .ttd { margin-top: 5px; }
  .meta { font-size: 10px; color: #4B5563; margin-bottom: 10px; }
</style>
</head>
<body>
<div class="kop">
  <h2>Desa Pangalasiang</h2>
  <p>Kecamatan Sojol, Kabupaten Donggala, Provinsi Sulawesi Tengah</p>
  <p>Jln. Poros Palu-Tolitoli KM. 123, Kode Pos: 94356</p>
  <hr>
  <h4 style="border:none; margin:0;">Laporan Hasil Klasifikasi Bantuan Sosial</h4>
  <p class="meta">Periode: {{ $periode ?? 'Semua' }} | Dicetak: {{ $dicetak->format('d/m/Y H:i') }}</p>
</div>

<p style="margin:5px 0;">Status Approval Filter: <strong>{{ $statusFilter ?? 'Semua' }}</strong></p>

<table>
  <thead>
    <tr>
      <th>No</th>
      <th>NIK</th>
      <th>Nama</th>
      <th>Prob. Layak</th>
      <th>Prob. Tidak</th>
      <th>Prediksi</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    @forelse($hasil as $i => $h)
    <tr>
      <td class="text-center">{{ $i + 1 }}</td>
      <td style="font-family:monospace;">{{ $h->warga?->nik ?? '-' }}</td>
      <td>{{ $h->warga?->nama ?? '-' }}</td>
      <td class="text-right">{{ number_format($h->prob_layak * 100, 1) }}%</td>
      <td class="text-right">{{ number_format($h->prob_tidak_layak * 100, 1) }}%</td>
      <td class="text-center {{ $h->prediksi_kelas === 'layak' ? 'layak' : 'tidak-layak' }}">{{ ucfirst($h->prediksi_kelas) }}</td>
      <td>{{ $h->status_approval }}</td>
    </tr>
    @empty
    <tr><td colspan="7" class="text-center">Tidak ada data.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="footer">
  <p>Dicetak melalui Sistem Informasi Klasifikasi Bantuan Sosial (SIKLAS-NB)</p>
  <p>Desa Pangalasiang, {{ $dicetak->format('d F Y') }}</p>
  <br>
  <p>Mengetahui,</p>
  <p class="ttd" style="margin-top: 40px;"><strong>Kepala Desa Pangalasiang</strong></p>
  <p>_________________________</p>
</div>
</body>
</html>
