<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Rincian Klasifikasi Keluarga - SIKLAS-NB</title>
<style>
  @page { margin: 20mm; }
  body { font-family: 'Inter', Arial, sans-serif; font-size: 11px; color: #1F2937; }
  .kop { text-align: center; margin-bottom: 10px; }
  .kop h2 { margin: 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; }
  .kop p { margin: 2px 0; font-size: 10px; color: #4B5563; }
  .kop hr { border: none; border-top: 2px solid #1F2937; margin: 6px 0; }
  h4 { font-size: 14px; margin: 12px 0 6px; border-bottom: 1px solid #D5D3C9; padding-bottom: 3px; color: #446370; }
  table { width: 100%; border-collapse: collapse; font-size: 11px; }
  th { background: #E6F0F4; padding: 6px 8px; text-align: left; border: 1px solid #D5D3C9; font-size: 9px; text-transform: uppercase; }
  td { padding: 6px 8px; border: 1px solid #D5D3C9; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }
  .layak { color: #155724; font-weight: bold; }
  .tidak-layak { color: #721C24; font-weight: bold; }
  .result-box { border: 2px solid #96B6C5; padding: 10px; margin: 10px 0; text-align: center; }
  .result-box .pred { font-size: 18px; font-weight: bold; }
  .info { font-size: 10px; color: #4B5563; }
  .footer { margin-top: 30px; display: flex; justify-content: space-between; font-size: 10px; }
  .footer .ttd { text-align: center; width: 40%; }
</style>
</head>
<body>
<div class="kop">
  <h2>Pemerintah Desa Pangalasiang</h2>
  <p>Kecamatan Sojol, Kabupaten Donggala, Provinsi Sulawesi Tengah</p>
  <p>Jln. Poros Palu-Tolitoli KM. 123, Kode Pos: 94356</p>
  <hr>
  <h4 style="border:none; margin:0;">Laporan Rincian Hasil Klasifikasi Keluarga</h4>
  <p style="font-size:10px;color:#4B5563;">Dicetak: {{ $dicetak->format('d/m/Y H:i') }}</p>
</div>

<h4>Identitas Keluarga</h4>
<table>
  <tr><th style="width:30%;">Nama Kepala Keluarga</th><td>{{ $warga->nama }}</td></tr>
  <tr><th>NIK</th><td style="font-family:monospace;">{{ $warga->nik }}</td></tr>
  <tr><th>Alamat</th><td>{{ $warga->alamat ?? '-' }}</td></tr>
  <tr><th>Dusun</th><td>{{ $warga->dusun ?? '-' }}</td></tr>
  <tr><th>Periode Data</th><td>{{ $warga->periode_data }}</td></tr>
</table>

<h4>Data Atribut Sosial Ekonomi</h4>
<table>
  <tr><th style="width:30%;">Penghasilan Bulanan</th><td>Rp{{ number_format($warga->penghasilan_bulanan, 0, ',', '.') }}</td></tr>
  <tr><th>Status Pekerjaan</th><td>{{ $warga->status_pekerjaan }}</td></tr>
  <tr><th>Jumlah Tanggungan</th><td>{{ $warga->jumlah_tanggungan }} Jiwa</td></tr>
  <tr><th>Kondisi Rumah</th><td>{{ $warga->kondisi_rumah }}</td></tr>
  <tr><th>Status Validasi</th><td>{{ $warga->status_validasi }}</td></tr>
</table>

@if($hasil)
<h4>Hasil Klasifikasi Naive Bayes</h4>
<table>
  <tr><th style="width:30%;">Probabilitas Layak</th><td>{{ number_format($hasil->prob_layak * 100, 2) }}%</td></tr>
  <tr><th>Probabilitas Tidak Layak</th><td>{{ number_format($hasil->prob_tidak_layak * 100, 2) }}%</td></tr>
  <tr><th>Keputusan Sistem</th><td class="{{ $hasil->prediksi_kelas === 'layak' ? 'layak' : 'tidak-layak' }}">{{ strtoupper($hasil->prediksi_kelas) }}</td></tr>
  <tr><th>Status Approval</th><td>{{ $hasil->status_approval }}</td></tr>
</table>

<div class="result-box">
  <p class="info">Rekomendasi Sistem Klasifikasi</p>
  <p class="pred {{ $hasil->prediksi_kelas === 'layak' ? 'layak' : 'tidak-layak' }}">
    {{ $hasil->prediksi_kelas === 'layak' ? 'LAYAK MENERIMA BANTUAN' : 'TIDAK LAYAK MENERIMA BANTUAN' }}
  </p>
  <p class="info">Berdasarkan perhitungan algoritma Naive Bayes pada sistem SIKLAS-NB</p>
</div>
@endif

<div class="footer">
  <div class="ttd">
    <p>Mengetahui,</p>
    <p style="margin-top: 40px;"><strong>Kepala Desa Pangalasiang</strong></p>
    <p>_________________________</p>
  </div>
  <div class="ttd">
    <p>Ditetapkan di,</p>
    <p>Pangalasiang, {{ $dicetak->format('d F Y') }}</p>
    <br>
    <p><strong>Sekretaris Desa</strong></p>
    <p>_________________________</p>
  </div>
</div>
<p style="font-size:9px; color:#9CA3AF; text-align:center; margin-top:10px;">Dokumen ini dicetak dari Sistem Informasi Klasifikasi Bantuan Sosial (SIKLAS-NB) Desa Pangalasiang</p>
</body>
</html>
