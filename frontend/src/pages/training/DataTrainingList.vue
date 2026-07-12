<script setup>
import { ref, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { DataTraining, downloadBlob } from '@/services/endpoints'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import ImportModal from '@/components/ImportModal.vue'
import { Plus, Filter, Edit3, Trash2, ChevronLeft, ChevronRight, FileSpreadsheet, Upload, Download } from '@lucide/vue'

const items = ref([])
const meta = ref(null)
const filters = ref({ label_kelas: '', page: 1 })

async function load() {
  const res = await DataTraining.list(filters.value)
  items.value = res.data
  meta.value = res.meta
}
onMounted(load)
watch(filters, load, { deep: true })

async function destroy(id) {
  if (!confirm('Hapus baris data training ini?')) return
  await DataTraining.remove(id); load()
}

// Export / template / import
async function exportCsv() { downloadBlob(await DataTraining.exportCsv({ label_kelas: filters.value.label_kelas }), `data_training_${Date.now()}.csv`) }
async function exportXlsx() { downloadBlob(await DataTraining.exportXlsx({ label_kelas: filters.value.label_kelas }), `data_training_${Date.now()}.xlsx`) }
async function template() { downloadBlob(await DataTraining.downloadTemplate('xlsx'), 'template_data_training.xlsx') }

const showImport = ref(false)
</script>

<template>
  <div>
    <PageHeader title="Data Training Naive Bayes" subtitle="Data latih berlabel historis untuk membangun dan melatih model AI">
      <template #actions>
        <button @click="template" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#1F2937] hover:bg-[#F8F7F2] shadow-xs transition-colors">
          <FileSpreadsheet class="w-4 h-4 text-[#96B6C5]" />
          <span>Template</span>
        </button>
        <button @click="showImport = true" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#1F2937] hover:bg-[#F8F7F2] shadow-xs transition-colors">
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
        <RouterLink :to="{ name: 'training.new' }" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] shadow-xs transition-colors">
          <Plus class="w-4 h-4 text-[#96B6C5]" />
          <span>Tambah Data Training</span>
        </RouterLink>
      </template>
    </PageHeader>

    <div class="p-6 space-y-6">
      <!-- Filter -->
      <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-[200px]">
          <Filter class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF] pointer-events-none" />
          <select v-model="filters.label_kelas" @change="filters.page = 1" class="w-full pl-10 pr-8 py-2 bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl text-sm font-semibold text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] appearance-none">
            <option value="">Semua Label Kelas</option>
            <option value="layak">Label Layak</option>
            <option value="tidak_layak">Label Tidak Layak</option>
          </select>
        </div>
      </div>

      <!-- Tabel -->
      <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead class="bg-[#E6F0F4] text-[#1F2937] text-xs font-semibold uppercase tracking-wider">
              <tr>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Penghasilan</th>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Pekerjaan</th>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Tanggungan</th>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Kondisi Rumah</th>
                <th class="text-center px-4 py-3.5 border-b border-[#EBE9E0]">Label Kelas</th>
                <th class="text-right px-4 py-3.5 border-b border-[#EBE9E0]">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EBE9E0]">
              <tr v-for="d in items" :key="d.id" class="hover:bg-[#F8F7F2] transition-colors">
                <td class="px-4 py-3.5 font-semibold text-[#1F2937]">{{ d.penghasilan_kategori }}</td>
                <td class="px-4 py-3.5 text-[#4B5563]">{{ d.pekerjaan_kategori }}</td>
                <td class="px-4 py-3.5 text-[#4B5563]">{{ d.tanggungan_kategori }}</td>
                <td class="px-4 py-3.5 text-[#4B5563]">{{ d.kondisi_rumah_kategori }}</td>
                <td class="px-4 py-3.5 text-center"><StatusBadge type="kelas" :value="d.label_kelas" /></td>
                <td class="px-4 py-3.5 text-right space-x-1 whitespace-nowrap">
                  <RouterLink :to="{ name: 'training.edit', params: { id: d.id } }" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-[#D5D3C9] text-[#1F2937] rounded-lg text-xs font-semibold hover:bg-[#F8F7F2] transition-colors shadow-xs">
                    <Edit3 class="w-3 h-3 text-[#96B6C5]" />
                    <span>Edit</span>
                  </RouterLink>
                  <button @click="destroy(d.id)" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-[#F8D7DA] text-[#721C24] rounded-lg text-xs font-semibold hover:bg-[#f5c6cb] transition-colors shadow-xs">
                    <Trash2 class="w-3 h-3" />
                    <span>Hapus</span>
                  </button>
                </td>
              </tr>
              <tr v-if="!items.length">
                <td colspan="6" class="px-4 py-10 text-center text-[#9CA3AF] text-sm font-medium">Belum ada data training berlabel.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Paginasi -->
      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between text-xs font-semibold text-[#4B5563] bg-white rounded-xl border border-[#D5D3C9] p-4 shadow-xs">
        <p>Halaman <span class="text-[#1F2937] tnum">{{ meta.current_page }}</span> dari <span class="text-[#1F2937] tnum">{{ meta.last_page }}</span> <span class="text-[#9CA3AF]">({{ meta.total }} total data)</span></p>
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

      <!-- Modal import data training -->
      <ImportModal
        v-model:open="showImport"
        :import-fn="DataTraining.importFiles"
        :template-fn="() => DataTraining.downloadTemplate('xlsx')"
        template-name="template_data_training.xlsx"
        title="Import Data Training"
      >
        <template #help>
          Beberapa file sekaligus (.csv &amp; .xlsx). Unduh
          <button type="button" @click="template" class="text-[#1A3B47] font-semibold underline hover:text-[#96B6C5]">template Excel</button>
          dulu. Tiap baris = 1 data latih (boleh duplikat, memperkuat probabilitas). Baris keliru dilewati.
        </template>
      </ImportModal>
    </div>
  </div>
</template>

