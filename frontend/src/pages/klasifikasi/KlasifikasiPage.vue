<script setup>
import { ref, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Model, Klasifikasi, Laporan } from '@/services/endpoints'
import { useAuthStore } from '@/stores/auth'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { Play, Layers, Download, Printer, Filter, CheckCircle2, ArrowRight, ChevronLeft, ChevronRight, Zap } from '@lucide/vue'

const auth = useAuthStore()
const items = ref([])
const meta = ref(null)
const filters = ref({ status_approval: '', prediksi_kelas: '', page: 1 })
const busy = ref(false)
const msg = ref('')
const periodeBulan = ref(3) // batasan #3: data terbaru & tervalidasi (default 3 bulan; 0 = semua)

async function loadList() {
  const res = await Klasifikasi.list(filters.value)
  items.value = res.data; meta.value = res.meta
}
onMounted(loadList)
watch(filters, loadList, { deep: true })

async function train() {
  busy.value = true; msg.value = ''
  try { const r = await Model.train(); msg.value = 'Model ' + (r.data?.model_version ?? '') + ' aktif.'; await loadList() }
  catch (e) { msg.value = e.response?.data?.message || 'Gagal training.' }
  finally { busy.value = false }
}
async function batch() {
  busy.value = true; msg.value = ''
  try { const r = await Klasifikasi.batch({ periode_bulan: periodeBulan.value }); msg.value = `Batch: ${r.diklasifikasi} diklasifikasi, ${r.dilewati} dilewati, ${r.gagal} gagal.`; await loadList() }
  catch (e) { msg.value = e.response?.data?.message || 'Gagal batch.' }
  finally { busy.value = false }
}
</script>

<template>
  <div>
    <PageHeader title="Klasifikasi Naive Bayes" subtitle="Training model AI & prediksi kelayakan bantuan sosial">
      <template #actions>
        <a :href="Laporan.csvUrl({ status_approval: filters.status_approval })" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#1F2937] hover:bg-[#F8F7F2] shadow-xs transition-colors">
          <Download class="w-4 h-4 text-[#96B6C5]" />
          <span>Export CSV</span>
        </a>
        <button @click="Laporan.previewPdf({ status_approval: filters.status_approval })" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#1F2937] hover:bg-[#F8F7F2] shadow-xs transition-colors">
          <Printer class="w-4 h-4 text-[#96B6C5]" />
          <span>Cetak PDF</span>
        </button>
      </template>
    </PageHeader>

    <div class="p-6 space-y-6">
      <!-- Aksi model (admin/superadmin) -->
      <div v-if="auth.role === 'admin' || auth.role === 'superadmin'" class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#E6F0F4] text-[#1A3B47] flex items-center justify-center flex-shrink-0 font-bold">
            <Zap class="w-5 h-5 text-[#96B6C5]" />
          </div>
          <div>
            <h3 class="font-bold text-[#1F2937] text-sm">Mesin Prediksi Naive Bayes</h3>
            <p class="text-xs text-[#4B5563]">Latih ulang model dengan data latih baru atau jalankan klasifikasi massal</p>
          </div>
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
          <button :disabled="busy" @click="train" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] shadow-xs transition-colors disabled:opacity-50">
            <Play class="w-3.5 h-3.5 text-[#96B6C5] fill-current" />
            <span>{{ busy ? 'Memproses…' : 'Latih Ulang Model' }}</span>
          </button>
          <label class="inline-flex items-center gap-1.5 px-3 py-2 bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#4B5563]" title="Batasan #3: hanya data terbaru & tervalidasi dalam N bulan (0 = semua)">
            <span>Periode</span>
            <input v-model.number="periodeBulan" type="number" min="0" max="60" class="w-12 bg-white border border-[#D5D3C9] rounded-lg px-1.5 py-0.5 text-center text-xs text-[#1F2937]" />
            <span>bln</span>
          </label>
          <button :disabled="busy" @click="batch" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#96B6C5] text-[#1A3B47] rounded-xl text-xs font-semibold hover:bg-[#7A9EAF] shadow-xs transition-colors disabled:opacity-50">
            <Layers class="w-4 h-4" />
            <span>{{ busy ? 'Memproses…' : 'Klasifikasi Batch' }}</span>
          </button>
        </div>
        <div v-if="msg" class="w-full text-xs font-semibold text-[#1A3B47] bg-[#E6F0F4] p-3 rounded-xl border border-[#96B6C5]/30">
          {{ msg }}
        </div>
      </div>

      <!-- Filter hasil -->
      <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-[200px] flex-1">
          <Filter class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF] pointer-events-none" />
          <select v-model="filters.prediksi_kelas" @change="filters.page = 1" class="w-full pl-10 pr-8 py-2 bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all appearance-none">
            <option value="">Semua Hasil Prediksi</option>
            <option value="layak">Terprediksi Layak</option>
            <option value="tidak_layak">Terprediksi Tidak Layak</option>
          </select>
        </div>
        <div class="relative min-w-[220px] flex-1">
          <Filter class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF] pointer-events-none" />
          <select v-model="filters.status_approval" @change="filters.page = 1" class="w-full pl-10 pr-8 py-2 bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all appearance-none">
            <option value="">Semua Status Approval</option>
            <option value="pending">Pending Approval</option>
            <option value="approved">Disetujui (Approved)</option>
            <option value="rejected">Ditolak (Rejected)</option>
            <option value="overridden">Di-override</option>
          </select>
        </div>
      </div>

      <!-- Tabel hasil -->
      <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead class="bg-[#E6F0F4] text-[#1F2937] text-xs font-semibold uppercase tracking-wider">
              <tr>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Warga</th>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Versi Model</th>
                <th class="text-right px-4 py-3.5 border-b border-[#EBE9E0]">P(Layak)</th>
                <th class="text-center px-4 py-3.5 border-b border-[#EBE9E0]">Prediksi Mesin</th>
                <th class="text-center px-4 py-3.5 border-b border-[#EBE9E0]">Approval</th>
                <th class="text-right px-4 py-3.5 border-b border-[#EBE9E0]">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EBE9E0]">
              <tr v-for="h in items" :key="h.id" class="hover:bg-[#F8F7F2] transition-colors">
                <td class="px-4 py-3.5">
                  <p class="font-semibold text-[#1F2937]">{{ h.warga?.nama }}</p>
                  <p class="text-xs text-[#9CA3AF] font-mono tnum">{{ h.warga?.nik }}</p>
                </td>
                <td class="px-4 py-3.5 font-mono text-xs text-[#1A3B47] font-medium">{{ h.model_version }}</td>
                <td class="px-4 py-3.5 text-right font-mono font-bold text-[#1F2937] tnum">{{ (Number(h.prob_layak) * 100).toFixed(1) }}%</td>
                <td class="px-4 py-3.5 text-center"><StatusBadge type="kelas" :value="h.prediksi_kelas" /></td>
                <td class="px-4 py-3.5 text-center"><StatusBadge type="approval" :value="h.status_approval" /></td>
                <td class="px-4 py-3.5 text-right">
                  <RouterLink :to="{ name: 'klasifikasi.show', params: { id: h.id } }" class="inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-[#1F2937] text-white rounded-lg hover:bg-[#374151] font-medium shadow-xs transition-colors">
                    <span>Rincian</span>
                    <ArrowRight class="w-3 h-3 text-[#96B6C5]" />
                  </RouterLink>
                </td>
              </tr>
              <tr v-if="!items.length">
                <td colspan="6" class="px-4 py-10 text-center text-[#9CA3AF] text-sm font-medium">Belum ada hasil klasifikasi ditemukan.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Paginasi -->
      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between text-xs font-semibold text-[#4B5563] bg-white rounded-xl border border-[#D5D3C9] p-4 shadow-xs">
        <p>Halaman <span class="text-[#1F2937] tnum">{{ meta.current_page }}</span> dari <span class="text-[#1F2937] tnum">{{ meta.last_page }}</span></p>
        <div class="flex items-center gap-2">
          <button :disabled="meta.current_page === 1" @click="filters.page--" class="inline-flex items-center gap-1 px-3 py-1.5 border border-[#D5D3C9] rounded-lg hover:bg-[#F8F7F2] disabled:opacity-40 disabled:pointer-events-none transition-colors">
            <ChevronLeft class="w-3.5 h-3.5" />
            <span>Sebelumnya</span>
          </button>
          <button :disabled="meta.current_page === meta.last_page" @click="filters.page++" class="inline-flex items-center gap-1 px-3 py-1.5 border border-[#D5D3C9] rounded-lg hover:bg-[#F8F7F2] disabled:opacity-40 disabled:pointer-events-none transition-colors">
            <span>Selanjutnya</span>
            <ChevronRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

