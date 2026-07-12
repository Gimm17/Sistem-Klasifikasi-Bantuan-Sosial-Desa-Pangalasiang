<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Dashboard as DashboardApi, Laporan } from '@/services/endpoints'
import { useAuthStore } from '@/stores/auth'
import DistribusiChart from '@/components/DistribusiChart.vue'
import AtributBarChart from '@/components/AtributBarChart.vue'
import AkurasiWidget from '@/components/AkurasiWidget.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { Users, UserCheck, CheckCircle2, XCircle, Clock, BookOpen, Zap, ClipboardCheck, BarChart3, Layers, ArrowRight, Printer, X, FileDown } from '@lucide/vue'

const auth = useAuthStore()
const stats = ref(null)
const loading = ref(true)
const error = ref('')
const showCetakModal = ref(false)
const cetakForm = ref({
  ringkasan: true,
  distribusi: true,
  daftar_terbaru: false,
  periode: 'bulan_ini',
})

function handleCetakPdf() {
  showCetakModal.value = false
  Laporan.previewPdf({ periode: cetakForm.value.periode })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    stats.value = await DashboardApi.get()
  } catch (e) {
    error.value = 'Gagal memuat statistik.'
  } finally {
    loading.value = false
  }
}
onMounted(load)

const cards = (s) => [
  { label: 'Total Warga', value: s.total_warga, color: 'text-[#1F2937]', bg: 'bg-white border-[#D5D3C9]', icon: Users, iconBg: 'bg-[#E6F0F4] text-[#1A3B47]' },
  { label: 'Warga Divalidasi', value: s.warga_divalidasi, color: 'text-[#1A3B47]', bg: 'bg-white border-[#D5D3C9]', icon: UserCheck, iconBg: 'bg-[#E6F0F4] text-[#1A3B47]' },
  { label: 'Terprediksi Layak', value: s.total_layak, color: 'text-[#155724]', bg: 'bg-white border-[#D5D3C9]', icon: CheckCircle2, iconBg: 'bg-[#D4EDDA] text-[#155724]' },
  { label: 'Tidak Layak', value: s.total_tidak_layak, color: 'text-[#721C24]', bg: 'bg-white border-[#D5D3C9]', icon: XCircle, iconBg: 'bg-[#F8D7DA] text-[#721C24]' },
  { label: 'Pending Approval', value: s.pending_approval, color: 'text-[#856404]', bg: 'bg-[#FFF3CD]/30 border-[#856404]/30', icon: Clock, iconBg: 'bg-[#FFF3CD] text-[#856404]' },
  { label: 'Data Training', value: s.total_data_training, color: 'text-[#4A3F30]', bg: 'bg-white border-[#D5D3C9]', icon: BookOpen, iconBg: 'bg-[#EEE0C9] text-[#4A3F30]' },
]
</script>

<template>
  <div>
    <PageHeader title="Dashboard" subtitle="Ringkasan klasifikasi kelayakan bantuan sosial (SIKLAS-NB)">
      <template #actions>
        <button @click="showCetakModal = true" class="inline-flex items-center gap-1.5 px-4 py-2 border border-[#96B6C5] text-[#96B6C5] bg-transparent rounded-xl text-xs font-semibold hover:bg-[#96B6C5]/10 transition-colors active:scale-95">
          <Printer class="w-4 h-4" />
          <span>Cetak Laporan PDF</span>
        </button>
      </template>
    </PageHeader>

    <div class="p-6 space-y-6">
      <div v-if="error" class="bg-[#F8D7DA] text-[#721C24] p-4 rounded-xl border border-[#721C24]/20 text-sm font-medium flex items-center gap-2">
        <XCircle class="w-5 h-5 flex-shrink-0" />
        <span>{{ error }}</span>
      </div>

      <!-- Kartu statistik -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div v-for="c in cards(stats || {})" :key="c.label" :class="c.bg" class="rounded-xl border shadow-xs p-4 transition-all hover:shadow-md flex flex-col justify-between">
          <div class="flex items-center justify-between gap-2 mb-3">
            <p class="text-xs text-[#4B5563] font-medium leading-tight">{{ c.label }}</p>
            <div :class="c.iconBg" class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0">
              <component :is="c.icon" class="w-4 h-4" />
            </div>
          </div>
          <p class="text-2xl font-bold tnum" :class="c.color">{{ loading ? '…' : (c.value ?? 0) }}</p>
        </div>
      </div>

      <!-- Baris Grafik & Analisis Skripsi -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Distribusi kelas -->
        <div class="bg-white rounded-xl shadow-xs p-5 border border-[#D5D3C9] flex flex-col justify-between">
          <div>
            <h3 class="font-semibold text-[#1F2937] text-base mb-1">Distribusi Hasil Klasifikasi</h3>
            <p class="text-xs text-[#4B5563] mb-4">Perbandingan warga terprediksi layak vs tidak layak</p>
          </div>
          <DistribusiChart :layak="stats?.total_layak ?? 0" :tidak-layak="stats?.total_tidak_layak ?? 0" />
        </div>

        <!-- Atribut Berpengaruh (Skripsi) -->
        <div class="bg-white rounded-xl shadow-xs p-5 border border-[#D5D3C9]">
          <div class="mb-4">
            <h3 class="font-semibold text-[#1F2937] text-base mb-1 flex items-center gap-2">
              Atribut Berpengaruh
              <span class="bg-[#E6F0F4] text-[#1A3B47] text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Bab IV</span>
            </h3>
            <p class="text-xs text-[#4B5563]">Bobot pengaruh atribut pada probabilitas Naive Bayes</p>
          </div>
          <AtributBarChart :items="stats?.atribut_berpengaruh || []" />
        </div>

        <!-- Evaluasi Model Terakhir (Skripsi) -->
        <div class="bg-white rounded-xl shadow-xs p-5 border border-[#D5D3C9]">
          <div class="mb-4">
            <h3 class="font-semibold text-[#1F2937] text-base mb-1 flex items-center gap-2">
              Metrik Akurasi Model
              <span class="bg-[#D4EDDA] text-[#155724] text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Evaluasi</span>
            </h3>
            <p class="text-xs text-[#4B5563]">Hasil pengujian akurasi pada data latih berlabel</p>
          </div>
          <AkurasiWidget :evaluasi="stats?.evaluasi_terakhir" />
        </div>
      </div>

      <!-- Baris Model & Antrean Terbaru -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Model aktif & Status Approval -->
        <div class="bg-white rounded-xl shadow-xs p-5 border border-[#D5D3C9] space-y-6">
          <div>
            <h3 class="font-semibold text-[#1F2937] text-base mb-3 flex items-center gap-2">
              <Layers class="w-4 h-4 text-[#96B6C5]" />
              <span>Model Naive Bayes Aktif</span>
            </h3>
            <div v-if="stats?.model_aktif" class="text-xs space-y-2 text-[#4B5563] bg-[#F8F7F2] p-3.5 rounded-xl border border-[#E5E3D9]">
              <div class="flex justify-between items-center"><span>Versi Model:</span> <b class="font-mono tnum text-[#1A3B47] font-bold bg-[#E6F0F4] px-2 py-0.5 rounded">{{ stats.model_aktif.model_version }}</b></div>
              <div class="flex justify-between items-center"><span>Total Data Latih:</span> <b class="tnum text-[#1F2937] font-bold">{{ stats.model_aktif.total_data }} data</b></div>
              <div class="flex justify-between items-center"><span>Layak / Tidak Layak:</span> <b class="tnum text-[#1F2937] font-bold">{{ stats.model_aktif.count_layak }} / {{ stats.model_aktif.count_tidak_layak }}</b></div>
              <p class="text-[11px] text-[#9CA3AF] pt-2 border-t border-[#EBE9E0] mt-2 tnum">Di-training: {{ new Date(stats.model_aktif.created_at).toLocaleString('id-ID') }}</p>
            </div>
            <p v-else class="text-sm text-[#9CA3AF] bg-[#F8F7F2] p-4 rounded-xl border border-[#E5E3D9] text-center">Belum ada model. Jalankan training di menu Klasifikasi.</p>
          </div>

          <div>
            <h3 class="font-semibold text-[#1F2937] text-base mb-3">Distribusi Status Approval</h3>
            <ul class="text-xs space-y-2" v-if="stats">
              <li class="flex justify-between items-center p-2.5 rounded-xl bg-[#FFF3CD]/40 border border-[#856404]/20"><span class="text-[#856404] font-semibold">Pending Approval</span><b class="text-[#856404] text-sm tnum">{{ stats.distribusi_approval.pending }}</b></li>
              <li class="flex justify-between items-center p-2.5 rounded-xl bg-[#D4EDDA]/40 border border-[#155724]/20"><span class="text-[#155724] font-semibold">Disetujui (Approved)</span><b class="text-[#155724] text-sm tnum">{{ stats.distribusi_approval.approved }}</b></li>
              <li class="flex justify-between items-center p-2.5 rounded-xl bg-[#F8D7DA]/40 border border-[#721C24]/20"><span class="text-[#721C24] font-semibold">Ditolak (Rejected)</span><b class="text-[#721C24] text-sm tnum">{{ stats.distribusi_approval.rejected }}</b></li>
              <li class="flex justify-between items-center p-2.5 rounded-xl bg-[#D1ECF1]/40 border border-[#0C5460]/20"><span class="text-[#0C5460] font-semibold">Di-override</span><b class="text-[#0C5460] text-sm tnum">{{ stats.distribusi_approval.overridden }}</b></li>
            </ul>
          </div>
        </div>

        <!-- Antrean Approval Terbaru -->
        <div class="bg-white rounded-xl shadow-xs p-5 border border-[#D5D3C9] lg:col-span-2 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
              <div>
                <h3 class="font-semibold text-[#1F2937] text-base">Antrean Approval Terbaru</h3>
                <p class="text-xs text-[#4B5563]">Warga menunggu verifikasi Kepala Desa / Sekdes</p>
              </div>
              <RouterLink v-if="auth.role === 'approver'" :to="{ name: 'approval' }" class="text-xs text-[#1A3B47] hover:text-[#96B6C5] font-semibold flex items-center gap-1 bg-[#E6F0F4] px-3 py-1.5 rounded-lg transition-colors">
                <span>Lihat Semua ({{ stats?.pending_approval || 0 }})</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </RouterLink>
            </div>

            <div class="overflow-x-auto rounded-xl border border-[#EBE9E0]">
              <table class="w-full text-sm border-collapse">
                <thead class="bg-[#E6F0F4] text-[#1F2937] text-xs font-semibold uppercase tracking-wider">
                  <tr>
                    <th class="text-left px-4 py-3 border-b border-[#EBE9E0]">Warga</th>
                    <th class="text-center px-4 py-3 border-b border-[#EBE9E0]">Prediksi NB</th>
                    <th class="text-right px-4 py-3 border-b border-[#EBE9E0]">P(Layak)</th>
                    <th class="text-right px-4 py-3 border-b border-[#EBE9E0]">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#EBE9E0]">
                  <tr v-for="item in stats?.pending_terbaru || []" :key="item.id" class="hover:bg-[#F8F7F2] transition-colors">
                    <td class="px-4 py-3">
                      <p class="font-semibold text-[#1F2937]">{{ item.warga_nama }}</p>
                      <p class="text-xs font-mono text-[#9CA3AF] tnum">{{ item.warga_nik }}</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <StatusBadge type="kelas" :value="item.prediksi_kelas" />
                    </td>
                    <td class="px-4 py-3 text-right font-mono font-bold text-[#1F2937] tnum">
                      {{ (Number(item.prob_layak) * 100).toFixed(1) }}%
                    </td>
                    <td class="px-4 py-3 text-right">
                      <RouterLink :to="{ name: 'klasifikasi.show', params: { id: item.id } }" class="inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-[#1F2937] text-white rounded-lg hover:bg-[#374151] font-medium shadow-xs transition-colors">
                        <span>Periksa</span>
                      </RouterLink>
                    </td>
                  </tr>
                  <tr v-if="!stats?.pending_terbaru?.length">
                    <td colspan="4" class="px-4 py-10 text-center text-[#9CA3AF] text-sm">
                      Tidak ada antrean pending approval saat ini.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Akses cepat -->
          <div class="mt-6 pt-5 border-t border-[#EBE9E0]">
            <p class="text-xs font-semibold text-[#4B5563] mb-3 uppercase tracking-wider">Akses Cepat</p>
            <div class="flex flex-wrap gap-2 text-xs font-semibold">
              <RouterLink v-if="auth.role === 'admin' || auth.role === 'approver'" :to="{ name: 'klasifikasi.index' }" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#96B6C5] text-[#1A3B47] rounded-xl hover:bg-[#7A9EAF] shadow-xs transition-colors">
                <Zap class="w-4 h-4" />
                <span>Jalankan Klasifikasi</span>
              </RouterLink>
              <RouterLink v-if="auth.role === 'approver'" :to="{ name: 'approval' }" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#1F2937] text-white rounded-xl hover:bg-[#374151] shadow-xs transition-colors">
                <ClipboardCheck class="w-4 h-4 text-[#96B6C5]" />
                <span>Antrean Approval ({{ stats?.pending_approval ?? 0 }})</span>
              </RouterLink>
              <RouterLink v-if="auth.role === 'admin' || auth.role === 'superadmin'" :to="{ name: 'training.list' }" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#EEE0C9] text-[#4A3F30] rounded-xl hover:bg-[#E2CFB3] shadow-xs transition-colors">
                <BookOpen class="w-4 h-4" />
                <span>Kelola Data Training</span>
              </RouterLink>
              <RouterLink v-if="auth.role === 'admin' || auth.role === 'approver' || auth.role === 'superadmin'" :to="{ name: 'evaluasi' }" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-[#D5D3C9] text-[#1F2937] rounded-xl hover:bg-[#F8F7F2] shadow-xs transition-colors">
                <BarChart3 class="w-4 h-4 text-[#96B6C5]" />
                <span>Evaluasi Model</span>
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>

      <!-- Modal Cetak Laporan PDF -->
      <div v-if="showCetakModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showCetakModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 mx-4">
          <div class="flex items-center justify-between mb-5">
            <div>
              <h3 class="text-lg font-bold text-[#1F2937]">Konfirmasi Cetak Laporan</h3>
              <p class="text-sm text-[#4B5563] mt-0.5">Pilih bagian laporan yang akan dicetak</p>
            </div>
            <button @click="showCetakModal = false" class="p-1.5 text-[#9CA3AF] hover:text-[#1F2937] hover:bg-[#F8F7F2] rounded-lg transition-colors">
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Bagian Laporan -->
          <div class="mb-5">
            <p class="text-sm font-semibold text-[#4B5563] mb-3">Bagian Laporan</p>
            <div class="space-y-3">
              <label class="flex items-center gap-3 p-3 rounded-xl border border-[#D5D3C9] hover:bg-[#F8F7F2] transition-colors cursor-pointer">
                <input type="checkbox" v-model="cetakForm.ringkasan" class="w-4 h-4 rounded border-[#D5D3C9] text-[#96B6C5] focus:ring-[#96B6C5]" />
                <div>
                  <p class="text-sm font-medium text-[#1F2937]">Ringkasan Statistik</p>
                  <p class="text-xs text-[#9CA3AF]">Total warga, pending approval, model aktif</p>
                </div>
              </label>
              <label class="flex items-center gap-3 p-3 rounded-xl border border-[#D5D3C9] hover:bg-[#F8F7F2] transition-colors cursor-pointer">
                <input type="checkbox" v-model="cetakForm.distribusi" class="w-4 h-4 rounded border-[#D5D3C9] text-[#96B6C5] focus:ring-[#96B6C5]" />
                <div>
                  <p class="text-sm font-medium text-[#1F2937]">Distribusi Kelayakan</p>
                  <p class="text-xs text-[#9CA3AF]">Grafik distribusi Layak vs Tidak Layak</p>
                </div>
              </label>
              <label class="flex items-center gap-3 p-3 rounded-xl border border-[#D5D3C9] hover:bg-[#F8F7F2] transition-colors cursor-pointer">
                <input type="checkbox" v-model="cetakForm.daftar_terbaru" class="w-4 h-4 rounded border-[#D5D3C9] text-[#96B6C5] focus:ring-[#96B6C5]" />
                <div>
                  <p class="text-sm font-medium text-[#1F2937]">Daftar Klasifikasi Terbaru</p>
                  <p class="text-xs text-[#9CA3AF]">Tabel 5 warga terakhir yang diklasifikasi</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Periode Laporan -->
          <div class="mb-6">
            <p class="text-sm font-semibold text-[#4B5563] mb-3">Periode Laporan</p>
            <select v-model="cetakForm.periode" class="w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-4 py-2.5 text-sm text-[#1F2937] font-medium focus:outline-none focus:ring-2 focus:ring-[#96B6C5]">
              <option value="bulan_ini">Bulan Ini</option>
              <option value="3_bulan">3 Bulan Terakhir</option>
              <option value="tahun_ini">Tahun Ini</option>
              <option value="semua">Semua Periode</option>
            </select>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EBE9E0]">
            <button @click="showCetakModal = false" class="px-5 py-2 text-sm font-medium text-[#4B5563] hover:bg-[#F8F7F2] rounded-xl transition-colors">Batal</button>
            <button @click="handleCetakPdf" class="inline-flex items-center gap-2 px-5 py-2 bg-[#96B6C5] text-white rounded-xl text-sm font-semibold hover:bg-[#7A9EAF] shadow-xs transition-colors">
              <FileDown class="w-4 h-4" />
              <span>Cetak PDF</span>
            </button>
          </div>
        </div>
      </div>
  </div>
</template>
