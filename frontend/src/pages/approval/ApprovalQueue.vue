<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Approval } from '@/services/endpoints'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { CheckCircle2, XCircle, Edit3, AlertCircle, ArrowRight, Filter, ChevronLeft, ChevronRight } from '@lucide/vue'

const items = ref([])
const meta = ref(null)
const filters = ref({ prediksi_kelas: '', page: 1 })

// modal aksi
const aktif = ref(null) // item yang sedang diproses
const form = reactive({ catatan: '', overrideKelas: 'layak' })
const mode = ref('') // 'approve' | 'reject' | 'override'
const busy = ref(false)
const errMsg = ref('')

async function load() {
  const res = await Approval.queue(filters.value)
  items.value = res.data; meta.value = res.meta
}
onMounted(load)
watch(filters, load, { deep: true })

function buka(item, m) {
  aktif.value = item; mode.value = m; form.catatan = ''; form.overrideKelas = item.prediksi_kelas; errMsg.value = ''
}
function tutup() { aktif.value = null }

async function submit() {
  if (!aktif.value) return
  if ((mode.value === 'reject' || mode.value === 'override') && !form.catatan.trim()) {
    errMsg.value = 'Catatan wajib diisi.'; return
  }
  busy.value = true; errMsg.value = ''
  try {
    if (mode.value === 'approve') await Approval.approve(aktif.value.id, form.catatan)
    else if (mode.value === 'reject') await Approval.reject(aktif.value.id, form.catatan)
    else await Approval.override(aktif.value.id, form.catatan, form.overrideKelas)
    tutup(); await load()
  } catch (e) { errMsg.value = e.response?.data?.message || 'Gagal.' }
  finally { busy.value = false }
}
</script>

<template>
  <div>
    <PageHeader title="Antrean Approval" subtitle="Verifikasi dan keputusan akhir kelayakan bantuan (Kepala Desa / Sekdes)">
      <template #actions>
        <div class="relative min-w-[180px]">
          <Filter class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF] pointer-events-none" />
          <select v-model="filters.prediksi_kelas" @change="filters.page = 1" class="w-full pl-10 pr-8 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#1F2937] focus:outline-none focus:ring-2 focus:ring-[#96B6C5] appearance-none shadow-xs">
            <option value="">Semua Prediksi Mesin</option>
            <option value="layak">Prediksi Layak</option>
            <option value="tidak_layak">Prediksi Tidak Layak</option>
          </select>
        </div>
      </template>
    </PageHeader>

    <div class="p-6 space-y-6">
      <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead class="bg-[#E6F0F4] text-[#1F2937] text-xs font-semibold uppercase tracking-wider">
              <tr>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Warga</th>
                <th class="text-right px-4 py-3.5 border-b border-[#EBE9E0]">P(Layak)</th>
                <th class="text-center px-4 py-3.5 border-b border-[#EBE9E0]">Rekomendasi Mesin</th>
                <th class="text-right px-4 py-3.5 border-b border-[#EBE9E0]">Aksi Verifikasi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EBE9E0]">
              <tr v-for="h in items" :key="h.id" class="hover:bg-[#F8F7F2] transition-colors">
                <td class="px-4 py-3.5">
                  <p class="font-semibold text-[#1F2937]">{{ h.warga?.nama }}</p>
                  <p class="text-xs text-[#9CA3AF] font-mono tnum">{{ h.warga?.nik }}</p>
                  <RouterLink :to="{ name: 'klasifikasi.show', params: { id: h.id } }" class="inline-flex items-center gap-1 text-xs text-[#1A3B47] hover:text-[#96B6C5] font-semibold mt-1">
                    <span>Lihat rincian perhitungan AI</span>
                    <ArrowRight class="w-3 h-3" />
                  </RouterLink>
                </td>
                <td class="px-4 py-3.5 text-right font-mono font-bold text-[#1F2937] tnum">{{ (Number(h.prob_layak) * 100).toFixed(1) }}%</td>
                <td class="px-4 py-3.5 text-center"><StatusBadge type="kelas" :value="h.prediksi_kelas" /></td>
                <td class="px-4 py-3.5 text-right space-x-2 whitespace-nowrap">
                  <button @click="buka(h, 'approve')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#D4EDDA] text-[#155724] rounded-lg text-xs font-semibold hover:bg-[#c3e6cb] transition-colors shadow-xs">
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    <span>Setujui</span>
                  </button>
                  <button @click="buka(h, 'reject')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#F8D7DA] text-[#721C24] rounded-lg text-xs font-semibold hover:bg-[#f5c6cb] transition-colors shadow-xs">
                    <XCircle class="w-3.5 h-3.5" />
                    <span>Tolak</span>
                  </button>
                  <button @click="buka(h, 'override')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#D1ECF1] text-[#0C5460] rounded-lg text-xs font-semibold hover:bg-[#bee5eb] transition-colors shadow-xs">
                    <Edit3 class="w-3.5 h-3.5" />
                    <span>Override</span>
                  </button>
                </td>
              </tr>
              <tr v-if="!items.length">
                <td colspan="4" class="px-4 py-10 text-center text-[#9CA3AF] text-sm font-medium">Antrean approval saat ini kosong.</td>
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

      <!-- Modal aksi -->
      <div v-if="aktif" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click.self="tutup">
        <div class="bg-white rounded-2xl shadow-xl border border-[#D5D3C9] w-full max-w-md p-6 space-y-4">
          <div class="pb-3 border-b border-[#EBE9E0]">
            <h3 class="font-bold text-[#1F2937] text-lg flex items-center gap-2">
              <span>{{ mode === 'approve' ? '✅ Setujui Kelayakan' : mode === 'reject' ? '❌ Tolak Kelayakan' : '⚡ Override Keputusan' }}</span>
            </h3>
            <p class="text-xs text-[#4B5563] mt-1">Warga: <strong class="text-[#1F2937]">{{ aktif.warga?.nama }}</strong> &middot; Rekomendasi Mesin: <span class="uppercase font-bold text-[#1A3B47]">{{ aktif.prediksi_kelas }}</span> ({{ (Number(aktif.prob_layak)*100).toFixed(1) }}% layak)</p>
          </div>

          <label v-if="mode === 'override'" class="block">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Tetapkan Kelas Akhir <span class="text-[#DC3545]">*</span></span>
            <select v-model="form.overrideKelas" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] font-semibold">
              <option value="layak">Layak Menerima Bantuan</option>
              <option value="tidak_layak">Tidak Layak Menerima Bantuan</option>
            </select>
          </label>

          <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Catatan Verifikasi {{ mode === 'approve' ? '(Opsional)' : '(Wajib Diisi)' }} <span v-if="mode !== 'approve'" class="text-[#DC3545]">*</span></span>
            <textarea v-model="form.catatan" rows="3" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] transition-all" :placeholder="mode === 'approve' ? 'Alasan persetujuan kelayakan...' : 'Wajib mencantumkan alasan verifikasi...'"></textarea>
          </label>

          <div v-if="errMsg" class="p-3 bg-[#F8D7DA] text-[#721C24] rounded-xl border border-[#721C24]/20 text-xs font-semibold flex items-center gap-2">
            <AlertCircle class="w-4 h-4 flex-shrink-0" />
            <span>{{ errMsg }}</span>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button @click="tutup" class="px-4 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#4B5563] hover:bg-[#F8F7F2] transition-colors">Batal</button>
            <button :disabled="busy" @click="submit" class="px-4 py-2 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] disabled:opacity-50 transition-colors shadow-xs">
              {{ busy ? 'Memproses Keputusan…' : 'Konfirmasi Keputusan' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

