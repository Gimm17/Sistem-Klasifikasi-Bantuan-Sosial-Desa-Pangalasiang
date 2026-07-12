<script setup>
import { ref, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Warga, downloadBlob } from '@/services/endpoints'
import { useAuthStore } from '@/stores/auth'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import ImportModal from '@/components/ImportModal.vue'
import Skeleton from '@/components/Skeleton.vue'
import { Plus, FileSpreadsheet, Upload, Download, Search, Filter, Trash2, Edit, Eye, CheckCircle2, ChevronLeft, ChevronRight } from '@lucide/vue'

const auth = useAuthStore()
const items = ref([])
const meta = ref(null)
const loading = ref(false)
const filters = ref({ nama: '', nik: '', status_validasi: '', page: 1 })

async function load() {
  loading.value = true
  try {
    const res = await Warga.list(filters.value)
    items.value = res.data
    meta.value = res.meta
  } finally {
    loading.value = false
  }
}
onMounted(load)
watch(filters, load, { deep: true })

function resetPage() { filters.value.page = 1 }

async function destroy(id, nama) {
  if (!confirm(`Hapus data warga "${nama}"? (soft delete)`)) return
  await Warga.remove(id)
  load()
}

async function validasi(id) {
  await Warga.validasi(id)
  load()
}

// Export (CSV & Excel), template (XLSX rapi), import multi-file (csv & xlsx)
async function exportCsv() {
  const f = { status_validasi: filters.value.status_validasi }
  downloadBlob(await Warga.exportCsv(f), `warga_${Date.now()}.csv`)
}
async function exportXlsx() {
  const f = { status_validasi: filters.value.status_validasi }
  downloadBlob(await Warga.exportXlsx(f), `warga_${Date.now()}.xlsx`)
}
async function template() {
  const blob = await Warga.downloadTemplate('xlsx')
  downloadBlob(blob, 'template_warga.xlsx')
}

const showImport = ref(false)
const openImport = () => { showImport.value = true }

const rupiah = (n) => 'Rp' + Number(n || 0).toLocaleString('id-ID')
</script>

<template>
  <div>
    <PageHeader title="Data Warga" subtitle="Master data calon penerima bantuan">
      <template #actions>
        <button v-if="auth.role === 'admin'" @click="template" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#1F2937] hover:bg-[#F8F7F2] shadow-xs transition-colors">
          <FileSpreadsheet class="w-4 h-4 text-[#96B6C5]" />
          <span>Template</span>
        </button>
        <button v-if="auth.role === 'admin'" @click="openImport" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#1F2937] hover:bg-[#F8F7F2] shadow-xs transition-colors">
          <Upload class="w-4 h-4 text-[#96B6C5]" />
          <span>Import</span>
        </button>
        <button @click="exportCsv" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#1F2937] hover:bg-[#F8F7F2] shadow-xs transition-colors">
          <Download class="w-4 h-4 text-[#96B6C5]" />
          <span>Export CSV</span>
        </button>
        <button @click="exportXlsx" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#1F2937] hover:bg-[#F8F7F2] shadow-xs transition-colors">
          <FileSpreadsheet class="w-4 h-4 text-[#96B6C5]" />
          <span>Export Excel</span>
        </button>
        <RouterLink v-if="auth.role === 'admin'" :to="{ name: 'warga.new' }" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] shadow-xs transition-colors">
          <Plus class="w-4 h-4 text-[#96B6C5]" />
          <span>Tambah Warga</span>
        </RouterLink>
      </template>
    </PageHeader>

    <div class="p-6 space-y-6">
      <!-- Filter -->
      <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[180px]">
          <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF] pointer-events-none" />
          <input v-model="filters.nama" @input="resetPage" placeholder="Cari nama warga…" class="w-full pl-10 pr-3 py-2 bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl text-sm text-[#1F2937] placeholder-[#9CA3AF] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all" />
        </div>
        <div class="relative flex-1 min-w-[180px]">
          <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF] pointer-events-none" />
          <input v-model="filters.nik" @input="resetPage" placeholder="Cari NIK…" class="w-full pl-10 pr-3 py-2 bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl text-sm text-[#1F2937] placeholder-[#9CA3AF] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all font-mono" />
        </div>
        <div class="relative min-w-[160px]">
          <Filter class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF] pointer-events-none" />
          <select v-model="filters.status_validasi" @change="resetPage" class="w-full pl-10 pr-8 py-2 bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all appearance-none">
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="divalidasi">Divalidasi</option>
          </select>
        </div>
      </div>

      <!-- Tabel -->
      <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead class="bg-[#E6F0F4] text-[#1F2937] text-xs font-semibold uppercase tracking-wider">
              <tr>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">NIK</th>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Nama Warga</th>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Pekerjaan</th>
                <th class="text-right px-4 py-3.5 border-b border-[#EBE9E0]">Penghasilan</th>
                <th class="text-center px-4 py-3.5 border-b border-[#EBE9E0]">Tanggungan</th>
                <th class="text-center px-4 py-3.5 border-b border-[#EBE9E0]">Validasi</th>
                <th class="text-right px-4 py-3.5 border-b border-[#EBE9E0]">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EBE9E0]">
              <tr v-for="w in items" :key="w.id" class="hover:bg-[#F8F7F2] transition-colors">
                <td class="px-4 py-3.5 font-mono text-xs text-[#4B5563] tnum">{{ w.nik }}</td>
                <td class="px-4 py-3.5 font-semibold text-[#1F2937]">{{ w.nama }}</td>
                <td class="px-4 py-3.5 text-[#4B5563]">{{ w.status_pekerjaan }}</td>
                <td class="px-4 py-3.5 text-right font-mono tnum font-medium text-[#1F2937]">{{ rupiah(w.penghasilan_bulanan) }}</td>
                <td class="px-4 py-3.5 text-center font-mono tnum text-[#1F2937] font-semibold">{{ w.jumlah_tanggungan }}</td>
                <td class="px-4 py-3.5 text-center"><StatusBadge type="validasi" :value="w.status_validasi" /></td>
                <td class="px-4 py-3.5 text-right space-x-1 whitespace-nowrap">
                  <RouterLink :to="{ name: 'warga.show', params: { id: w.id } }" title="Detail" class="inline-flex items-center justify-center p-1.5 text-[#1A3B47] hover:bg-[#E6F0F4] rounded-lg transition-colors">
                    <Eye class="w-4 h-4" />
                  </RouterLink>
                  <button v-if="auth.role === 'admin' && w.status_validasi === 'draft'" @click="validasi(w.id)" title="Validasi Data" class="inline-flex items-center justify-center p-1.5 text-[#155724] hover:bg-[#D4EDDA] rounded-lg transition-colors">
                    <CheckCircle2 class="w-4 h-4" />
                  </button>
                  <RouterLink v-if="auth.role === 'admin'" :to="{ name: 'warga.edit', params: { id: w.id } }" title="Edit" class="inline-flex items-center justify-center p-1.5 text-[#4B5563] hover:bg-[#F0F4F6] rounded-lg transition-colors">
                    <Edit class="w-4 h-4" />
                  </RouterLink>
                  <button v-if="auth.role === 'admin'" @click="destroy(w.id, w.nama)" title="Hapus" class="inline-flex items-center justify-center p-1.5 text-[#721C24] hover:bg-[#F8D7DA] rounded-lg transition-colors">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <template v-if="loading && !items.length">
                <tr v-for="i in 5" :key="'sk'+i" class="border-b border-[#EBE9E0]">
                  <td v-for="j in 7" :key="j" class="px-4 py-3.5"><Skeleton w="70%" h="12px" /></td>
                </tr>
              </template>
              <tr v-else-if="!items.length">
                <td colspan="7" class="px-4 py-10 text-center text-[#9CA3AF] text-sm font-medium">Tidak ada data warga ditemukan.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Paginasi -->
      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between text-xs font-semibold text-[#4B5563] bg-white rounded-xl border border-[#D5D3C9] p-4 shadow-xs">
        <p>Halaman <span class="text-[#1F2937] tnum">{{ meta.current_page }}</span> dari <span class="text-[#1F2937] tnum">{{ meta.last_page }}</span> (<span class="text-[#1F2937] tnum">{{ meta.total }}</span> total data)</p>
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

      <!-- Modal import (multi-file: csv & xlsx, drag-drop, ringkasan per-file) -->
      <ImportModal
        v-model:open="showImport"
        :import-fn="Warga.importFiles"
        :template-fn="() => Warga.downloadTemplate('xlsx')"
        template-name="template_warga.xlsx"
        title="Import Data Warga"
      >
        <template #help>
          Boleh <span class="font-semibold">beberapa file sekaligus</span> (.csv &amp; .xlsx). Unduh
          <button type="button" @click="template" class="text-[#1A3B47] font-semibold underline hover:text-[#96B6C5]">template Excel</button>
          dulu bila perlu. Baris keliru dilewati, NIK ganda dilewati.
        </template>
      </ImportModal>
    </div>
  </div>
</template>

