<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Download, Printer, X, ZoomIn, ZoomOut, FileText } from '@lucide/vue'

const route = useRoute()
const router = useRouter()

const pdfUrl = ref('')
const title = ref('Laporan PDF')
const loading = ref(true)

onMounted(() => {
  const url = route.query.url
  const t = route.query.title

  if (!url) {
    router.push({ name: 'dashboard' })
    return
  }

  pdfUrl.value = url
  title.value = t || 'Laporan PDF'
  loading.value = false
})

const downloadUrl = computed(() => {
  const separator = pdfUrl.value.includes('?') ? '&' : '?'
  return pdfUrl.value + separator + 'download=1'
})

// Fungsi untuk browser print
function cetak() {
  window.frames['pdf-frame'].focus()
  window.frames['pdf-frame'].print()
}

function tutup() {
  router.back()
}
</script>

<template>
  <div class="min-h-screen bg-[#1F2937] flex flex-col">
    <!-- Toolbar -->
    <header class="bg-[#1F2937] text-white px-4 py-2.5 flex items-center justify-between border-b border-[#374151] select-none">
      <div class="flex items-center gap-3">
        <button @click="tutup" class="p-1.5 text-gray-400 hover:text-white hover:bg-white/10 rounded-lg transition-colors" title="Tutup">
          <X class="w-5 h-5" />
        </button>
        <div class="w-px h-5 bg-[#374151]"></div>
        <FileText class="w-5 h-5 text-[#96B6C5]" />
        <div>
          <h1 class="text-sm font-semibold text-white truncate max-w-[300px] md:max-w-[500px]">{{ title }}</h1>
          <p class="text-[10px] text-gray-400">Pratinjau Dokumen SIKLAS-NB</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a
          :href="downloadUrl"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#96B6C5] text-[#1A3B47] rounded-lg text-xs font-bold hover:bg-[#7A9EAF] shadow-xs transition-colors"
        >
          <Download class="w-4 h-4" />
          <span class="hidden sm:inline">Download PDF</span>
          <span class="sm:hidden">Download</span>
        </a>
        <button @click="cetak" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white/10 text-white rounded-lg text-xs font-semibold hover:bg-white/20 transition-colors">
          <Printer class="w-4 h-4" />
          <span class="hidden sm:inline">Cetak</span>
        </button>
      </div>
    </header>

    <!-- Loading -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <div class="text-center text-gray-400">
        <div class="w-8 h-8 border-2 border-[#96B6C5] border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
        <p class="text-sm">Memuat dokumen…</p>
      </div>
    </div>

    <!-- PDF Viewer (via iframe) -->
    <div v-else class="flex-1 bg-[#374151] overflow-y-auto">
      <div class="mx-auto" style="max-width: 900px;">
        <iframe
          id="pdf-frame"
          :src="pdfUrl"
          class="w-full h-screen border-0"
          style="min-height: calc(100vh - 52px);"
          @load="loading = false"
        ></iframe>
      </div>
    </div>
  </div>
</template>
