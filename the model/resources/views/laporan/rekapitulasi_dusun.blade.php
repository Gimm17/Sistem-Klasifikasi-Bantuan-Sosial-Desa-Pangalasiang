<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Rekapitulasi Per Dusun - SIKLAS-NB</title>
<style>
  @page { margin: 20mm; }
  body { font-family: 'Inter', Arial, sans-serif; font-size: 11px; color: #1F2937; }
  .kop { text-align: center; margin-bottom: 15px; }
  .kop h2 { margin: 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; }
  .kop p { margin: 2px 0; font-size: 10px; color: #4B5563; }
  .kop hr { border: none; border-top: 2px solid #1F2937; margin: 8px 0; }
  h4 { font-size: 13px; margin: 12px 0 6px; border-bottom: 1px solid #D5D3C9; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; font-size: 10px; }
  th { background: #E6F0F4; padding: 6px 8px; text-align: left; border: 1px solid #D5D3C9; font-size: 9px; text-transform: uppercase; }
  td { padding: 5px 8px; border: 1px solid #D5D3C9; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }
  .layak { color: #155724; font-weight: bold; }
  .footer { margin-top: 30px; display: flex; justify-content: space-between; font-size: 10px; }
  .footer .ttd { text-align: center; width: 40%; }
  .stats { display: flex; gap: 10px; margin: 8px 0; }
  .stat-box { flex: 1; border: 1px solid #D5D3C9; padding: 6px; text-align: center; font-size: 10px; }
  .stat-box .val { font-size: 16px; font-weight: bold; }
</style>
</head>
<body>
<div class="kop">
  <h2>Pemerintah Desa Pangalasiang</h2>
  <p>Kecamatan Sojol, Kabupaten Donggala, Provinsi Sulawesi Tengah</p>
  <p>Jln. Poros Palu-Tolitoli KM. 123, Kode Pos: 94356</p>
  <hr>
  <h4 style="border:none; margin:0;">Laporan Rekapitulasi Klasifikasi Per Dusun</h4>
  <p style="font-size:10px;color:#4B5563;">Periode: Semua | Dicetak: {{ $dicetak->format('d/m/Y H:i') }}</p>
</div>

<div class="stats">
  <div class="stat-box">
    <div>Total Dusun</div>
    <div class="val">{{ $data['total_dusun'] }}</div>
  </div>
  <div class="stat-box">
    <div>Total Warga Terklasifikasi</div>
    <div class="val">{{ $data["total_warga"] }}</div>
  </div>
  <div class="stat-box">
    <div>Warga Layak</div>
    <div class="val" style="color:#155724;">{{ $data["total_layak"] }}</div>
  </div>
  <div class="stat-box">
    <div>Warga Tidak Layak</div>
    <div class="val" style="color:#721C24;">{{ $data["total_tidak_layak"] }}</div>
  </div>
</div>

<h4>Rekapitulasi Per Dusun</h4>
<table>
  <thead>
    <tr>
      <th>No</th>
      <th>Nama Dusun</th>
      <th class="text-right">Total</th>
      <th class="text-right">Layak</th>
      <th class="text-right">% Layak</th>
      <th class="text-right">Tidak Layak</th>
      <th class="text-right">Rata-rata Prob.</th>
    </tr>
  </thead>
  <tbody>
    @forelse($data["detail"] as $i => $d)
    <tr>
      <td class="text-center">{{ $i + 1 }}</td>
      <td>{{ $d['dusun'] }}</td>
      <td class="text-right">{{ $d['total_warga'] }}</td>
      <td class="text-right layak">{{ $d['layak'] }}</td>
      <td class="text-right">{{ $d['pct_layak'] }}%</td>
      <td class="text-right">{{ $d['tidak_layak'] }}</td>
      <td class="text-right">{{ $d['avg_prob_layak'] }}%</td>
    </tr>
    @empty
    <tr><td colspan="7" class="text-center">Belum ada data.</td></tr>
    @endforelse
  </tbody>
  <tfoot style="font-weight:bold;">
    <tr>
      <td colspan="2" class="text-right">TOTAL</td>
      <td class="text-right">{{ $data["total_warga"] }}</td>
      <td class="text-right layak">{{ $data["total_layak"] }}</td>
      <td class="text-right">{{ $data["total_warga"] > 0 ? number_format(($data["total_layak"]/$data["total_warga"])*100,1) : 0 }}%</td>
      <td class="text-right">{{ $data["total_tidak_layak"] }}</td>
      <td class="text-right">{{ $data["total_warga"] > 0 ? number_format(($data["total_layak"]/max($data["total_layak"]+$data["total_tidak_layak"],1))*100,1) : 0 }}%</td>
    </tr>
  </tfoot>
</table>

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
<p style="font-size:9px; color:#9CA3AF; text-align:center; margin-top:10px;">Dokumen ini dicetak dari Sistem Klasifikasi Bantuan Sosial (SIKLAS-NB) Desa Pangalasiang</p>
</body>
</html>
