<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import PageHeader from '@/components/PageHeader.vue'
import {
  HelpCircle,
  BookOpen,
  Eye,
  BrainCircuit,
  LogIn,
  Users,
  Camera,
  Zap,
  ClipboardCheck,
  FileDown,
  ShieldCheck,
  CheckCircle2,
  XCircle,
  AlertTriangle,
  Info,
} from '@lucide/vue'

// Gambar (di-bundle Vite, hashed, cache-bust). Sumber: documentation/gambar_svg/.
import imgWorkflow from '@/assets/dokumentasi/Gambar_4.1_workflow_5tahap.svg'
import imgApproval from '@/assets/dokumentasi/Gambar_6.5_alur_approval.svg'
import imgAudit from '@/assets/dokumentasi/Gambar_10.1_keamanan_audit.svg'
import imgKonsep from '@/assets/dokumentasi/Gambar_8.1_konsep_naive_bayes.svg'
import imgTraining from '@/assets/dokumentasi/Gambar_8.3_proses_training.svg'
import imgPrediksi from '@/assets/dokumentasi/Gambar_8.4_alur_prediksi.svg'
import imgEvaluasi from '@/assets/dokumentasi/Gambar_9.1_evaluasi_metode.svg'
import imgConfusion from '@/assets/dokumentasi/Gambar_9.2_confusion_matrix.svg'

const tab = ref('panduan')
const tabs = [
  { key: 'panduan', label: 'Panduan Penggunaan', icon: BookOpen },
  { key: 'baca', label: 'Cara Baca Hasil', icon: Eye },
  { key: 'nb', label: 'Naive Bayes', icon: BrainCircuit },
]

const steps = [
  { n: 1, icon: LogIn, title: 'Login', body: 'Buka https://siklas.pinnhost.my.id, masukkan email & password. Login berbasis cookie (aman terhadap CSRF). Salah 5× per menit akan dikunci sementara.' },
  { n: 2, icon: Zap, title: 'Latih Model (sekali / setelah data training baru)', body: 'Menu Klasifikasi → "Latih Ulang Model". Sistem menghitung probabilitas dari data latih berlabel → membuat Model Versi Aktif baru. Khusus admin / superadmin.' },
  { n: 3, icon: Users, title: 'Input Warga', body: 'Menu Data Warga → "Tambah Warga". Isi NIK, nama, dusun, penghasilan, pekerjaan, tanggungan, periode. Kondisi rumah TIDAK diisi di sini (diisi saat validasi). Status awal: DRAFT.' },
  { n: 4, icon: Camera, title: 'Upload Foto Rumah + Validasi', body: 'Di halaman Detail Warga → section "Dokumentasi Foto Rumah" → unggah ≥1 foto (jpg/png/webp, maks 2MB, maks 5 foto). Klik thumbnail untuk preview besar. Lalu "Validasi Data" → modal tampilkan foto + dropdown label kondisi rumah → pilih → "Validasi & Tetapkan Label". Status: DIVALIDASI. AI hanya memproses data DIVALIDASI.' },
  { n: 5, icon: BrainCircuit, title: 'Klasifikasi (Prediksi AI)', body: 'Menu Klasifikasi → "Klasifikasi Batch" (semua warga divalidasi sekaligus) atau di Detail Warga → "Jalankan Klasifikasi" (satuan). Hasil: prediksi LAYAK / TIDAK LAYAK + persentase keyakinan, status PENDING APPROVAL. Foto TIDAK masuk hitung NB.' },
  { n: 6, icon: ClipboardCheck, title: 'Approval (Kepala Desa / Sekdes)', body: 'Menu Antrean Approval → klik warga → baca breakdown → Setujui / Tolak / Override. Tolak & Override wajib isi catatan alasan (tercatat di audit log). Lihat hasil sudah diputus lewat menu Klasifikasi → filter Status Approval.' },
  { n: 7, icon: FileDown, title: 'Rekapitulasi & Cetak PDF', body: 'Menu Rekap. Per Dusun → pantau persebaran bantuan. Cetak PDF rekapitulasi / rincian KK per warga (ber-kop resmi). Rincian KK bisa sertakan foto rumah (opsi centang saat cetak).' },
]

const roleMatrix = [
  { fitur: 'Input & Validasi Warga', admin: '✅ Utama', approver: '👁️ Lihat', superadmin: '✅ Bisa' },
  { fitur: 'Kelola Data Training & Latih Model', admin: '✅ Utama', approver: '❌', superadmin: '✅ Bisa' },
  { fitur: 'Jalankan Klasifikasi AI', admin: '✅ Utama', approver: '👁️ Lihat', superadmin: '✅ Bisa' },
  { fitur: 'Verifikasi & Approval', admin: '❌', approver: '✅ Penuh', superadmin: '❌' },
  { fitur: 'Rekapitulasi & Cetak PDF', admin: '✅', approver: '✅ Utama', superadmin: '✅' },
  { fitur: 'Kelola Akun & Audit Log', admin: '❌', approver: '❌', superadmin: '✅ Penuh' },
]

const atributTable = [
  { a: 'Penghasilan', t: 'Numerik (di-bin)', k: '<1jt • 1–2jt • 2–3jt • >3jt' },
  { a: 'Pekerjaan', t: 'Kategorikal', k: 'Tidak Bekerja • Buruh/Tani • Wiraswasta • PNS • Lainnya' },
  { a: 'Tanggungan', t: 'Numerik (di-bin)', k: '0–1 • 2–3 • 4–5 • >5' },
  { a: 'Kondisi Rumah', t: 'Kategorikal', k: 'Tidak Layak Huni • Kurang Layak Huni • Layak Huni' },
]

const glossary = [
  { t: 'Naive Bayes', d: 'Algoritma klasifikasi probabilistik berdasar Teorema Bayes' },
  { t: 'Kelas (Class)', d: 'Label hasil: layak / tidak_layak' },
  { t: 'Atribut (Feature)', d: 'Ciri penentu: penghasilan, pekerjaan, tanggungan, kondisi rumah' },
  { t: 'Data Training', d: 'Data berlabel yang dipakai melatih model' },
  { t: 'Prior P(C)', d: 'Peluang dasar sebuah kelas (dari data training)' },
  { t: 'Likelihood P(Xᵢ|C)', d: 'Peluang atribut muncul pada kelas tertentu' },
  { t: 'Laplace Smoothing', d: 'Trik +1 agar probabilitas tidak jadi nol pada kategori yang tak terlihat' },
  { t: 'Binning / Diskritisasi', d: 'Mengelompokkan angka mentah ke kategori' },
  { t: 'Log-Space', d: 'Hitung di ruang logaritma agar stabil (cegah underflow)' },
  { t: 'Confusion Matrix', d: 'Tabel TP/TN/FP/FN pembanding prediksi vs aktual' },
  { t: 'Hold-out / k-Fold', d: 'Metode evaluasi dengan data uji terpisah' },
  { t: 'Breakdown JSON', d: 'Rincian matematis tiap prediksi (Explainable AI)' },
  { t: 'Approval', d: 'Persetujuan manusia (Kades) atas rekomendasi AI' },
]
</script>

<template>
  <div>
    <PageHeader title="Dokumentasi" subtitle="Panduan penggunaan & penjelasan Naive Bayes — SIKLAS-NB">
      <template #actions>
        <RouterLink :to="{ name: 'klasifikasi.index' }" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#1F2937] hover:bg-[#F8F7F2] shadow-xs transition-colors">
          <BrainCircuit class="w-4 h-4 text-[#96B6C5]" />
          <span>Lihat Klasifikasi</span>
        </RouterLink>
      </template>
    </PageHeader>

    <div class="p-6 space-y-6">
      <!-- Tab switcher -->
      <div class="flex flex-wrap gap-2">
        <button
          v-for="t in tabs"
          :key="t.key"
          @click="tab = t.key"
          :class="[
            'inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-xs',
            tab === t.key
              ? 'bg-[#1F2937] text-white'
              : 'bg-white border border-[#D5D3C9] text-[#4B5563] hover:bg-[#F8F7F2]',
          ]"
        >
          <component :is="t.icon" class="w-4 h-4" :class="tab === t.key ? 'text-[#96B6C5]' : ''" />
          <span>{{ t.label }}</span>
        </button>
      </div>

      <!-- ====================== TAB: PANDUAN ====================== -->
      <template v-if="tab === 'panduan'">
        <!-- Pengenalan -->
        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937] flex items-center gap-2">
            <Info class="w-5 h-5 text-[#96B6C5]" />
            <span>Apa itu SIKLAS-NB?</span>
          </h3>
          <p class="text-sm text-[#4B5563] leading-relaxed">
            SIKLAS-NB adalah <strong>Sistem Klasifikasi Kelayakan Penerima Bantuan Sosial berbasis Naive Bayes</strong>
            untuk Desa Pangalasiang. Setiap tahun pemerintah desa harus menentukan warga mana yang layak menerima
            bantuan sosial (PKH, BPNT, BLT). Sistem ini memakai algoritma Naive Bayes untuk memprediksi kelayakan
            berdasarkan 4 ciri sosial-ekonomi: penghasilan, pekerjaan, jumlah tanggungan, dan kondisi rumah.
          </p>
          <div class="p-4 rounded-xl bg-[#E6F0F4] border border-[#96B6C5]/30 flex items-start gap-3">
            <ShieldCheck class="w-5 h-5 text-[#1A3B47] flex-shrink-0 mt-0.5" />
            <p class="text-sm text-[#1A3B47] leading-relaxed">
              <strong>Human-in-the-Loop.</strong> Sistem ini <strong>bukan</strong> menggantikan keputusan manusia.
              Naive Bayes hanya memberikan <strong>rekomendasi</strong> (mis. "AI memprediksi warga ini 88% LAYAK").
              Keputusan akhir tetap diambil oleh <strong>Kepala Desa / Sekretaris Desa</strong> lewat menu Approval.
            </p>
          </div>
        </section>

        <!-- Alur 5 tahap -->
        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Alur Kerja 5 Tahap</h3>
          <p class="text-sm text-[#4B5563]">SIKLAS-NB memakai prinsip Pemisahan Wewenang (Separation of Duties) sesuai birokrasi desa.</p>
          <img :src="imgWorkflow" alt="Alur kerja 5 tahap SIKLAS-NB" class="w-full rounded-xl border border-[#D5D3C9] bg-[#F8F7F2] p-3" />
          <ol class="space-y-2 text-sm text-[#4B5563] list-decimal list-inside">
            <li><strong>Training Model</strong> (admin) — melatih "otak" Naive Bayes memakai data historis berlabel.</li>
            <li><strong>Input + Foto + Validasi</strong> (admin) — masukkan data warga, unggah foto rumah, tetapkan label kondisi.</li>
            <li><strong>Klasifikasi</strong> (admin) — AI menghitung probabilitas & menetapkan prediksi (status: pending approval).</li>
            <li><strong>Verifikasi</strong> (kades/sekdes) — Approve / Reject / Override + catatan alasan.</li>
            <li><strong>Rekapitulasi & Laporan</strong> (semua) — sebaran per dusun + cetak PDF untuk Musdes.</li>
          </ol>
        </section>

        <!-- Matriks role -->
        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Wewenang Pengguna (Role)</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
              <thead class="bg-[#E6F0F4] text-[#1F2937] text-xs font-semibold uppercase tracking-wider">
                <tr>
                  <th class="text-left px-4 py-3 border-b border-[#EBE9E0]">Fitur</th>
                  <th class="text-center px-4 py-3 border-b border-[#EBE9E0]">Admin (Petugas)</th>
                  <th class="text-center px-4 py-3 border-b border-[#EBE9E0]">Approver (Kades)</th>
                  <th class="text-center px-4 py-3 border-b border-[#EBE9E0]">Superadmin</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#EBE9E0]">
                <tr v-for="r in roleMatrix" :key="r.fitur" class="hover:bg-[#F8F7F2] transition-colors">
                  <td class="px-4 py-3 font-medium text-[#1F2937]">{{ r.fitur }}</td>
                  <td class="px-4 py-3 text-center text-xs">{{ r.admin }}</td>
                  <td class="px-4 py-3 text-center text-xs">{{ r.approver }}</td>
                  <td class="px-4 py-3 text-center text-xs">{{ r.superadmin }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Step by step -->
        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-5">
          <h3 class="text-base font-bold text-[#1F2937]">Langkah-Langkah Menggunakan Website</h3>
          <div class="space-y-4">
            <div v-for="s in steps" :key="s.n" class="flex gap-4">
              <div class="flex flex-col items-center flex-shrink-0">
                <div class="w-10 h-10 rounded-xl bg-[#96B6C5] text-[#1A3B47] flex items-center justify-center font-bold text-sm shadow-inner">
                  {{ s.n }}
                </div>
                <div v-if="s.n < steps.length" class="w-px flex-1 bg-[#EBE9E0] my-1"></div>
              </div>
              <div class="pb-2">
                <h4 class="text-sm font-bold text-[#1F2937] flex items-center gap-2">
                  <component :is="s.icon" class="w-4 h-4 text-[#96B6C5]" />
                  <span>{{ s.title }}</span>
                </h4>
                <p class="text-sm text-[#4B5563] mt-1 leading-relaxed">{{ s.body }}</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Alur approval -->
        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Alur Approval (Kepala Desa / Sekdes)</h3>
          <img :src="imgApproval" alt="Alur approval: Setujui / Tolak / Override" class="w-full rounded-xl border border-[#D5D3C9] bg-[#F8F7F2] p-3" />
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="p-3 rounded-xl bg-[#D4EDDA]/40 border border-[#155724]/20">
              <p class="text-xs font-bold text-[#155724] flex items-center gap-1.5"><CheckCircle2 class="w-4 h-4" /> Setujui</p>
              <p class="text-xs text-[#155724] mt-1">Terima rekomendasi AI. Catatan opsional.</p>
            </div>
            <div class="p-3 rounded-xl bg-[#F8D7DA]/40 border border-[#721C24]/20">
              <p class="text-xs font-bold text-[#721C24] flex items-center gap-1.5"><XCircle class="w-4 h-4" /> Tolak</p>
              <p class="text-xs text-[#721C24] mt-1">Wajib isi alasan (mis. sudah pindah domisili).</p>
            </div>
            <div class="p-3 rounded-xl bg-[#D1ECF1]/40 border border-[#0C5460]/20">
              <p class="text-xs font-bold text-[#0C5460] flex items-center gap-1.5"><AlertTriangle class="w-4 h-4" /> Override</p>
              <p class="text-xs text-[#0C5460] mt-1">Ubah keputusan AI, wajib alasan (mis. baru kena musibah).</p>
            </div>
          </div>
        </section>

        <!-- Keamanan -->
        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937] flex items-center gap-2">
            <ShieldCheck class="w-5 h-5 text-[#96B6C5]" />
            <span>Keamanan & Audit</span>
          </h3>
          <img :src="imgAudit" alt="Keamanan & audit sistem" class="w-full rounded-xl border border-[#D5D3C9] bg-[#F8F7F2] p-3" />
          <ul class="space-y-1.5 text-sm text-[#4B5563] list-disc list-inside">
            <li>Login berbasis cookie (Sanctum), tahan serangan CSRF.</li>
            <li>Rate limit 5× salah per menit.</li>
            <li>Setiap aksi penting (input, validasi, upload/hapus foto, approval, override) tercatat di <strong>Jejak Audit</strong>.</li>
            <li>Hapus warga = soft-delete: data audit tetap utuh, NIK bisa dipakai ulang, hasil warga terhapus disembunyikan dari daftar.</li>
          </ul>
        </section>
      </template>

      <!-- ====================== TAB: CARA BACA HASIL ====================== -->
      <template v-if="tab === 'baca'">
        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Cara Membaca Hasil Klasifikasi</h3>
          <p class="text-sm text-[#4B5563]">
            Setiap hasil klasifikasi disimpan lengkap sebagai <strong>breakdown</strong> matematis.
            Buka di menu Klasifikasi → klik <strong>Rincian</strong> pada baris warga. Baca dalam 3 urutan berikut.
          </p>

          <div class="space-y-3">
            <div class="p-4 rounded-xl bg-[#F8F7F2] border border-[#EBE9E0]">
              <p class="text-xs font-bold uppercase tracking-wider text-[#96B6C5]">1. Gauge Probabilitas</p>
              <p class="text-sm text-[#4B5563] mt-1">
                Dua batang: hijau <strong>P(Layak)</strong> vs merah <strong>P(Tidak Layak)</strong>, dinormalisasi
                ke 100%. Persentase terbesar = prediksi AI. Contoh: 88.5% vs 11.5% → AI "yakin" 88.5% warga layak
                → prediksi <strong>LAYAK</strong>.
              </p>
            </div>
            <div class="p-4 rounded-xl bg-[#F8F7F2] border border-[#EBE9E0]">
              <p class="text-xs font-bold uppercase tracking-wider text-[#96B6C5]">2. Likelihood Bar Chart</p>
              <p class="text-sm text-[#4B5563] mt-1">
                Memperlihatkan atribut mana yang paling "berat" mendorong keputusan. Bandingkan peluang munculnya
                nilai atribut warga pada kelas Layak vs Tidak Layak. Atribut dengan selisih terbesar = paling
                menentukan untuk warga ini.
              </p>
            </div>
            <div class="p-4 rounded-xl bg-[#F8F7F2] border border-[#EBE9E0]">
              <p class="text-xs font-bold uppercase tracking-wider text-[#96B6C5]">3. Tabel Rincian Perhitungan</p>
              <p class="text-sm text-[#4B5563] mt-1">
                Menampilkan <strong>Prior P(kelas)</strong>, skor belum ternormalisasi, dan likelihood tiap atribut
                per kelas. Warna latar lebih pekat = probabilitas likelihood lebih tinggi.
                Rumus: <code class="font-mono text-xs bg-white px-1.5 py-0.5 rounded border border-[#D5D3C9]">Skor = Prior × Π P(atribut|kelas)</code>.
              </p>
            </div>
          </div>
        </section>

        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Mengapa Seorang Warga Diprediksi LAYAK?</h3>
          <p class="text-sm text-[#4B5563]">Contoh pembacaan (tampilan nyata ada di menu Rincian):</p>
          <pre class="text-xs font-mono bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl p-4 overflow-x-auto text-[#1F2937]">Prediksi AI     : LAYAK
Probabilitas    : P(Layak) = 88.5%   |   P(Tidak Layak) = 11.5%

Breakdown per atribut (Likelihood P(Atribut | Kelas)):
  Penghasilan   <1.000.000       → sangat mendukung LAYAK
  Pekerjaan     Buruh/Tani       → mendukung LAYAK
  Tanggungan    >5               → mendukung LAYAK
  Kondisi Rumah Tidak Layak Huni → sangat mendukung LAYAK</pre>
          <p class="text-sm text-[#4B5563] leading-relaxed">
            Karena keempat atribut warga ini lebih sering muncul pada data latih berlabel <strong>layak</strong>,
            probabilitasnya menumpuk ke kelas Layak (88.5%). AI hanya membandingkan kedua pembilang — kelas dengan
            nilai terbesar menjadi prediksi.
          </p>
        </section>

        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Status Approval</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="p-3 rounded-xl bg-[#FFF3CD]/40 border border-[#856404]/20 text-center">
              <p class="text-xs font-bold text-[#856404]">pending</p>
              <p class="text-[11px] text-[#856404] mt-0.5">Menunggu keputusan Kades</p>
            </div>
            <div class="p-3 rounded-xl bg-[#D4EDDA]/40 border border-[#155724]/20 text-center">
              <p class="text-xs font-bold text-[#155724]">approved</p>
              <p class="text-[11px] text-[#155724] mt-0.5">Disetujui</p>
            </div>
            <div class="p-3 rounded-xl bg-[#F8D7DA]/40 border border-[#721C24]/20 text-center">
              <p class="text-xs font-bold text-[#721C24]">rejected</p>
              <p class="text-[11px] text-[#721C24] mt-0.5">Ditolak</p>
            </div>
            <div class="p-3 rounded-xl bg-[#D1ECF1]/40 border border-[#0C5460]/20 text-center">
              <p class="text-xs font-bold text-[#0C5460]">overridden</p>
              <p class="text-[11px] text-[#0C5460] mt-0.5">Diubah manusia</p>
            </div>
          </div>
          <p class="text-xs text-[#9CA3AF]">
            Lihat hasil sudah diputus: menu Klasifikasi → filter dropdown "Status Approval" → pilih Disetujui /
            Ditolak / Di-override. Bisa cetak PDF / export CSV per filter.
          </p>
        </section>

        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Kapan Approve, Tolak, atau Override?</h3>
          <ul class="space-y-2 text-sm text-[#4B5563]">
            <li class="flex gap-2"><CheckCircle2 class="w-4 h-4 text-[#155724] flex-shrink-0 mt-0.5" /><span><strong>Setujui</strong> — rekomendasi AI sesuai kondisi lapangan & dokumen pendukung.</span></li>
            <li class="flex gap-2"><XCircle class="w-4 h-4 text-[#721C24] flex-shrink-0 mt-0.5" /><span><strong>Tolak</strong> — warga sudah pindah domisili, meninggal, atau sudah menerima bantuan lain yang setara.</span></li>
            <li class="flex gap-2"><AlertTriangle class="w-4 h-4 text-[#0C5460] flex-shrink-0 mt-0.5" /><span><strong>Override</strong> — AI memprediksi Tidak Layak tapi ada musibah baru (kebakaran, sakit kronis) yang membuat warga layak, atau sebaliknya. Wajib isi alasan, tercatat di audit.</span></li>
          </ul>
        </section>
      </template>

      <!-- ====================== TAB: NAIVE BAYES ====================== -->
      <template v-if="tab === 'nb'">
        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937] flex items-center gap-2">
            <BrainCircuit class="w-5 h-5 text-[#96B6C5]" />
            <span>Konsep & Rumus</span>
          </h3>
          <p class="text-sm text-[#4B5563]">
            Naive Bayes berdasar pada <strong>Teorema Bayes</strong> dengan asumsi naif bahwa ke-4 atribut saling
            bebas (conditional independence).
          </p>
          <img :src="imgKonsep" alt="Konsep Teorema Bayes" class="w-full rounded-xl border border-[#D5D3C9] bg-[#F8F7F2] p-3" />
          <pre class="text-xs font-mono bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl p-4 overflow-x-auto text-[#1F2937]">        P(C) × P(X₁|C) × P(X₂|C) × P(X₃|C) × P(X₄|C)
P(C|X) = ───────────────────────────────────────────────────────
                          P(X)</pre>
          <ul class="space-y-1.5 text-sm text-[#4B5563] list-disc list-inside">
            <li><strong>C</strong> = kelas target: <code>layak</code> / <code>tidak_layak</code>.</li>
            <li><strong>X</strong> = 4 atribut warga (penghasilan, pekerjaan, tanggungan, kondisi rumah).</li>
            <li><strong>P(C)</strong> = peluang dasar kelas (dari data training).</li>
            <li><strong>P(Xᵢ|C)</strong> = peluang kemunculan atribut ke-i pada kelas C (Likelihood).</li>
          </ul>
          <div class="p-3 rounded-xl bg-[#E6F0F4] border border-[#96B6C5]/30 text-sm text-[#1A3B47]">
            Karena penyebut <strong>P(X)</strong> sama untuk kedua kelas, cukup bandingkan pembilangnya.
            <strong>Prediksi = kelas dengan nilai terbesar.</strong>
          </div>
        </section>

        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">4 Atribut & Binning (Diskritisasi)</h3>
          <p class="text-sm text-[#4B5563]">
            Versi ini memakai <strong>Categorical Naive Bayes</strong> — atribut harus berupa label kategori.
            Angka mentah (mis. penghasilan Rp 1.250.000) dikelompokkan ke bin agar peluangnya tidak nol.
          </p>
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
              <thead class="bg-[#E6F0F4] text-[#1F2937] text-xs font-semibold uppercase tracking-wider">
                <tr>
                  <th class="text-left px-4 py-3 border-b border-[#EBE9E0]">Atribut</th>
                  <th class="text-left px-4 py-3 border-b border-[#EBE9E0]">Tipe</th>
                  <th class="text-left px-4 py-3 border-b border-[#EBE9E0]">Kategori / Bin</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#EBE9E0]">
                <tr v-for="a in atributTable" :key="a.a" class="hover:bg-[#F8F7F2] transition-colors">
                  <td class="px-4 py-3 font-medium text-[#1F2937]">{{ a.a }}</td>
                  <td class="px-4 py-3 text-xs text-[#4B5563]">{{ a.t }}</td>
                  <td class="px-4 py-3 text-xs text-[#4B5563] font-mono">{{ a.k }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="text-xs text-[#9CA3AF]">Batas bin disimpan di tabel kategori_atribut (dinamis). Admin bisa ubah tanpa mengubah kode — adaptif terhadap perubahan UMR/inflasi.</p>
        </section>

        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Proses Training Model</h3>
          <img :src="imgTraining" alt="Proses training: prior → likelihood + Laplace → simpan DB" class="w-full rounded-xl border border-[#D5D3C9] bg-[#F8F7F2] p-3" />
          <p class="text-sm text-[#4B5563]">Saat admin klik "Latih Ulang Model", backend menjalankan:</p>
          <ol class="space-y-2 text-sm text-[#4B5563] list-decimal list-inside">
            <li><strong>Kumpulkan data latih</strong> — baca seluruh data_training (120 data: 60 layak, 60 tidak layak).</li>
            <li><strong>Prior P(C)</strong> — <code class="font-mono text-xs bg-[#F8F7F2] px-1.5 py-0.5 rounded border border-[#D5D3C9]">P(layak) = 60/120 = 0.50</code></li>
            <li><strong>Likelihood + Laplace</strong> — cegah zero probability: <code class="font-mono text-xs bg-[#F8F7F2] px-1.5 py-0.5 rounded border border-[#D5D3C9]">P(Xᵢ=k|C) = (Count+1) / (Count(C)+|Vᵢ|)</code></li>
            <li><strong>Simpan permanen</strong> — ke model_versions + model_probabilitas. Probabilitas tidak dihitung ulang tiap klasifikasi → sangat cepat.</li>
          </ol>
        </section>

        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Proses Prediksi</h3>
          <img :src="imgPrediksi" alt="Alur prediksi: input → binning → log-score → keputusan" class="w-full rounded-xl border border-[#D5D3C9] bg-[#F8F7F2] p-3" />
          <ol class="space-y-2 text-sm text-[#4B5563] list-decimal list-inside">
            <li><strong>Kategorisasi</strong> — nilai mentah → label bin (mis. Rp 1.500.000 → "1–2jt").</li>
            <li><strong>Log-Score</strong> tiap kelas: <code class="font-mono text-xs bg-[#F8F7F2] px-1.5 py-0.5 rounded border border-[#D5D3C9]">LogScore(C) = ln P(C) + Σ ln P(Xᵢ|C)</code>. Dihitung di ruang logaritma agar tidak underflow.</li>
            <li><strong>Normalisasi Log-Sum-Exp</strong> → persentase: <code class="font-mono text-xs bg-[#F8F7F2] px-1.5 py-0.5 rounded border border-[#D5D3C9]">P(C|X) = exp(LogScore(C)−MaxLog) / Σ exp(...)</code></li>
            <li><strong>Prediksi</strong> = kelas probabilitas terbesar (seri → layak).</li>
            <li><strong>Simpan breakdown</strong> (prior, likelihood tiap atribut, log-score) → status pending approval.</li>
          </ol>
        </section>

        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Keunggulan Teknis</h3>
          <ul class="space-y-2 text-sm text-[#4B5563]">
            <li class="flex gap-2"><CheckCircle2 class="w-4 h-4 text-[#96B6C5] flex-shrink-0 mt-0.5" /><span><strong>Log-Space</strong> — cegah floating-point underflow, stabil walau data ribuan.</span></li>
            <li class="flex gap-2"><CheckCircle2 class="w-4 h-4 text-[#96B6C5] flex-shrink-0 mt-0.5" /><span><strong>Laplace Smoothing</strong> — cegah probabilitas nol pada kategori tak terlihat.</span></li>
            <li class="flex gap-2"><CheckCircle2 class="w-4 h-4 text-[#96B6C5] flex-shrink-0 mt-0.5" /><span><strong>Memoization</strong> — tabel probabilitas dimuat sekali untuk seluruh batch → klasifikasi ratusan warga dalam milidetik.</span></li>
            <li class="flex gap-2"><CheckCircle2 class="w-4 h-4 text-[#96B6C5] flex-shrink-0 mt-0.5" /><span><strong>Explainable AI</strong> — breakdown_json menyimpan bukti matematis tiap prediksi → bisa diaudit saat sidang.</span></li>
            <li class="flex gap-2"><CheckCircle2 class="w-4 h-4 text-[#96B6C5] flex-shrink-0 mt-0.5" /><span><strong>Dynamic Binning</strong> — batas kategori di database, adaptif tanpa ubah kode.</span></li>
          </ul>
        </section>

        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Evaluasi Model</h3>
          <img :src="imgEvaluasi" alt="Perbandingan Hold-out vs k-Fold" class="w-full rounded-xl border border-[#D5D3C9] bg-[#F8F7F2] p-3" />
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="p-3 rounded-xl bg-[#F8F7F2] border border-[#EBE9E0]">
              <p class="font-bold text-[#1F2937] text-xs uppercase tracking-wider">Hold-out</p>
              <p class="text-xs text-[#4B5563] mt-1">Data dibagi: 80% dilatih, 20% diuji. Model dilatih ulang di train-split, diuji di test-split.</p>
            </div>
            <div class="p-3 rounded-xl bg-[#F8F7F2] border border-[#EBE9E0]">
              <p class="font-bold text-[#1F2937] text-xs uppercase tracking-wider">k-Fold (stratified)</p>
              <p class="text-xs text-[#4B5563] mt-1">Data dibagi 5 lipatan seimbang per kelas. Tiap lipatan diuji 1×, dilatih di 4 lain. Confusion matrix diakumulasi.</p>
            </div>
          </div>
          <img :src="imgConfusion" alt="Confusion matrix TP/TN/FP/FN" class="w-full rounded-xl border border-[#D5D3C9] bg-[#F8F7F2] p-3" />
          <pre class="text-xs font-mono bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl p-4 overflow-x-auto text-[#1F2937]">Accuracy  = (TP + TN) / (TP + TN + FP + FN)
Precision = TP / (TP + FP)
Recall    = TP / (TP + FN)
F1-Score  = 2 × Precision × Recall / (Precision + Recall)</pre>
          <div class="p-3 rounded-xl bg-[#FFF3CD]/40 border border-[#856404]/30 text-xs text-[#856404] flex items-start gap-2">
            <AlertTriangle class="w-4 h-4 flex-shrink-0 mt-0.5" />
            <span>Catatan: angka akurasi 100% saat menguji pada data latih sendiri (resubstitution) wajar karena data seed bersih & seimbang. Untuk mengukur generalisasi, gunakan Hold-out / k-Fold — itulah angka yang sebaiknya dicantumkan di Bab IV skripsi.</span>
          </div>
        </section>

        <section class="bg-white rounded-xl border border-[#D5D3C9] p-6 space-y-4">
          <h3 class="text-base font-bold text-[#1F2937]">Glosarium</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
              <tbody class="divide-y divide-[#EBE9E0]">
                <tr v-for="g in glossary" :key="g.t" class="hover:bg-[#F8F7F2] transition-colors">
                  <td class="px-4 py-2.5 font-semibold text-[#1F2937] whitespace-nowrap align-top">{{ g.t }}</td>
                  <td class="px-4 py-2.5 text-xs text-[#4B5563]">{{ g.d }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>
