<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Rincian Warga Per Dusun - SIKLAS-NB</title>
<style>
  @page { margin: 20mm; }
  body { font-family: 'Inter', Arial, sans-serif; font-size: 11px; color: #1F2937; }
  .kop { text-align: center; margin-bottom: 10px; }
  .kop h2 { margin: 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; }
  .kop p { margin: 2px 0; font-size: 10px; color: #4B5563; }
  .kop hr { border: none; border-top: 2px solid #1F2937; margin: 6px 0; }
  h4 { font-size: 13px; margin: 10px 0 5px; }
  h5 { font-size: 12px; margin: 15px 0 5px; background: #E6F0F4; padding: 4px 8px; }
  table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 10px; }
  th { background: #E6F0F4; padding: 5px 6px; text-align: left; border: 1px solid #D5D3C9; font-size: 9px; text-transform: uppercase; }
  td { padding: 4px 6px; border: 1px solid #D5D3C9; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }
  .layak { color: #155724; font-weight: bold; }
  .tidak-layak { color: #721C24; font-weight: bold; }
  .page-break { page-break-before: always; }
  .footer { margin-top: 25px; text-align: right; font-size: 10px; }
  .dusun-header { font-weight: bold; font-size: 12px; margin: 10px 0 3px; color: #446370; }
</style>
</head>
<body>
<div class="kop">
  <h2>Pemerintah Desa Pangalasiang</h2>
  <p>Kecamatan Sojol, Kabupaten Donggala, Provinsi Sulawesi Tengah</p>
  <p>Jln. Poros Palu-Tolitoli KM. 123, Kode Pos: 94356</p>
  <hr>
  <h4 style="border:none; margin:0;">Laporan Rincian Klasifikasi Warga Per Dusun</h4>
  <p style="font-size:10px;color:#4B5563;">Dicetak: {{ $dicetak->format('d/m/Y H:i') }}</p>
</div>

<p style="font-size:10px;">Total Warga Terdata: <strong>{{ $grouped->sum(fn($g) => $g->count()) }}</strong></p>

@forelse($grouped as $dusun => $wargas)
<h5>Dusun: {{ $dusun }}</h5>
<table>
  <thead>
    <tr>
      <th>No</th>
      <th>NIK</th>
      <th>Nama Warga</th>
      <th class="text-right">Prob. Layak</th>
      <th class="text-center">Prediksi</th>
      <th class="text-center">Status Approval</th>
    </tr>
  </thead>
  <tbody>
    @foreach($wargas as $i => $item)
    <tr>
      <td class="text-center">{{ $i + 1 }}</td>
      <td style="font-family:monospace;">{{ $item['nik'] }}</td>
      <td>{{ $item['nama'] }}</td>
      <td class="text-right">{{ $item['prob_layak'] }}</td>
      <td class="text-center {{ $item['kelas'] === 'layak' ? 'layak' : 'tidak-layak' }}">{{ ucfirst($item['kelas']) }}</td>
      <td class="text-center">{{ $item['status_approval'] }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@empty
<p style="text-align:center;color:#9CA3AF;">Belum ada data warga.</p>
@endforelse

<div class="footer">
  <p>Dicetak melalui Sistem Informasi Klasifikasi Bantuan Sosial (SIKLAS-NB)</p>
  <p>Desa Pangalasiang, {{ $dicetak->format('d F Y') }}</p>
  <p style="margin-top: 30px;"><strong>Kepala Desa Pangalasiang</strong></p>
  <p>_________________________</p>
</div>
</body>
</html>
