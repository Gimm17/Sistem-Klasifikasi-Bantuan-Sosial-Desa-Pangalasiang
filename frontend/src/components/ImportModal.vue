<script setup>
/**
 * Modal import generik (multi-file csv & xlsx, drag-drop, ringkasan per-file).
 * Dipakai ulang oleh Data Warga & Data Training.
 *
 * Props:
 *  - importFn(files[]) : Promise -> { diimpor, gagal, per_file[], detail_gagal[] }
 *  - templateFn()      : Promise -> Blob  (unduh template)
 *  - templateName      : string          (nama file template saat diunduh)
 *  - title             : string
 *  - help              : string          (teks bantuan, boleh HTML link template via slot)
 */
import { ref } from 'vue'
import { downloadBlob } from '@/services/endpoints'
import { Upload } from '@lucide/vue'

const props = defineProps({
  importFn: { type: Function, required: true },
  templateFn: { type: Function, required: true },
  templateName: { type: String, default: 'template.xlsx' },
  title: { type: String, default: 'Import Data' },
  help: { type: String, default: '' },
})

const open = defineModel('open', { type: Boolean, default: false }) // v-model:open

const ALLOWED = ['csv', 'xlsx', 'xls', 'txt']
const files = ref([])
const result = ref(null)
const msg = ref('')
const isError = ref(false)
const importing = ref(false)
const dragging = ref(false)
const inputEl = ref(null)

function reset() {
  files.value = []
  result.value = null
  msg.value = ''
  isError.value = false
  if (inputEl.value) inputEl.value.value = ''
}
function show() { reset(); open.value = true }
function close() { open.value = false }
function add(list) {
  for (const f of Array.from(list || [])) {
    const ext = (f.name.split('.').pop() || '').toLowerCase()
    if (ALLOWED.includes(ext)) {
      files.value.push(f)
    } else {
      msg.value = `File "${f.name}" diabaikan — hanya .csv / .xlsx / .xls yang diperbolehkan.`
      isError.value = true
    }
  }
}
function onPick(e) { add(e.target.files) }
function onDrop(e) { dragging.value = false; add(e.dataTransfer?.files) }
function removeAt(i) { files.value.splice(i, 1) }
async function downloadTpl() {
  const blob = await props.templateFn()
  downloadBlob(blob, props.templateName)
}
async function run() {
  if (!files.value.length) return
  importing.value = true; msg.value = ''; isError.value = false; result.value = null
  try {
    const r = await props.importFn(files.value)
    result.value = r
    msg.value = `Selesai: ${r.diimpor} diimpor, ${r.gagal} baris gagal dari ${r.per_file?.length || 0} file.`
    isError.value = r.gagal > 0
    files.value = []
    if (inputEl.value) inputEl.value.value = ''
  } catch (e) {
    isError.value = true
    if (e.response?.status === 422) {
      const errs = e.response?.data?.errors
      msg.value = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message || 'Validasi gagal.')
    } else if (e.response?.status === 413) {
      msg.value = 'Ukuran total file terlalu besar. Unggah lebih sedikit/kecil per batch.'
    } else {
      msg.value = e.response?.data?.message || 'Gagal import.'
    }
  } finally { importing.value = false }
}
</script>

<template>
  <div v-if="open" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click.self="close">
    <div class="bg-white rounded-2xl shadow-xl border border-[#D5D3C9] w-full max-w-lg p-6 space-y-4">
      <div>
        <h3 class="font-bold text-[#1F2937] text-lg">{{ title }}</h3>
        <p class="text-xs text-[#4B5563] mt-1">
          <slot name="help">{{ help }}</slot>
        </p>
      </div>

      <div
        class="rounded-xl border-2 border-dashed p-4 text-center transition-colors"
        :class="dragging ? 'border-[#96B6C5] bg-[#E6F0F4]' : 'border-[#D5D3C9] bg-[#F8F7F2]'"
        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop"
      >
        <Upload class="w-6 h-6 mx-auto text-[#96B6C5]" />
        <p class="text-xs text-[#4B5563] mt-1.5">Tarik-lepas file ke sini, atau</p>
        <input ref="inputEl" type="file" accept=".csv,.xlsx,.xls" multiple @change="onPick" class="block mx-auto mt-2 text-xs text-[#4B5563] file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#1F2937] file:text-white hover:file:bg-[#374151]" />
      </div>

      <ul v-if="files.length" class="space-y-1">
        <li v-for="(f, i) in files" :key="f.name + i" class="flex items-center justify-between gap-2 text-xs text-[#1F2937] bg-[#F8F7F2] border border-[#D5D3C9] rounded-lg px-2.5 py-1.5">
          <span class="truncate font-mono">{{ f.name }} <span class="text-[#9CA3AF]">({{ (f.size/1024).toFixed(0) }} KB)</span></span>
          <button @click="removeAt(i)" class="text-[#721C24] hover:bg-[#F8D7DA] rounded px-1.5 py-0.5 shrink-0">Hapus</button>
        </li>
      </ul>

      <div v-if="msg" class="text-xs font-semibold p-3 rounded-xl border" :class="isError ? 'text-[#721C24] bg-[#F8D7DA] border-[#F5C2C7]' : 'text-[#155724] bg-[#D4EDDA] border-[#C3E6CB]'">
        {{ msg }}
      </div>

      <div v-if="result && result.per_file" class="max-h-56 overflow-y-auto rounded-xl border border-[#D5D3C9] divide-y divide-[#EBE9E0]">
        <div v-for="(pf, i) in result.per_file" :key="i" class="px-3 py-2 text-xs">
          <div class="flex items-center justify-between gap-2">
            <span class="font-mono text-[#1F2937] truncate">{{ pf.file }}</span>
            <span class="shrink-0 text-[#4B5563]"><span class="text-[#155724] font-semibold">{{ pf.diimpor }}</span> diimpor · <span class="text-[#721C24] font-semibold">{{ pf.gagal }}</span> gagal</span>
          </div>
          <p v-if="pf.catatan" class="text-[#721C24] mt-0.5">{{ pf.catatan }}</p>
          <ul v-if="pf.detail_gagal && pf.detail_gagal.length" class="mt-1 pl-3 list-disc text-[#721C24] space-y-0.5">
            <li v-for="(g, j) in pf.detail_gagal.slice(0, 10)" :key="j">{{ g }}</li>
            <li v-if="pf.detail_gagal.length > 10" class="text-[#9CA3AF]">…dan {{ pf.detail_gagal.length - 10 }} lainnya.</li>
          </ul>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button @click="close" class="px-4 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#4B5563] hover:bg-[#F8F7F2] transition-colors">Tutup</button>
        <button :disabled="importing || !files.length" @click="run" class="px-4 py-2 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] disabled:opacity-50 transition-colors">
          {{ importing ? 'Mengimpor…' : `Mulai Import (${files.length})` }}
        </button>
      </div>
    </div>
  </div>
</template>
