<script setup>
/**
 * Lightbox foto rumah — preview ukuran besar saat thumbnail diklik.
 * Reusable: dipakai di WargaFotoGallery (detail) & modal validasi (WargaDetail).
 * Navigasi: tombol ‹/›, panah keyboard ←/→, tutup Esc / klik backdrop / tombol X.
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { X, ChevronLeft, ChevronRight } from '@lucide/vue'

const props = defineProps({
  fotos: { type: Array, default: () => [] }, // [{id,url,original_name,size_bytes}]
  /** indeks foto aktif; -1 = tutup. Bind dua-arah via v-model:index. */
  index: { type: Number, default: -1 },
})
const emit = defineEmits(['update:index'])

const open = computed(() => props.index >= 0 && props.index < props.fotos.length)
const current = computed(() => (open.value ? props.fotos[props.index] : null))

function setIndex(i) {
  emit('update:index', i)
}
function close() { setIndex(-1) }
function prev() {
  if (!open.value) return
  setIndex((props.index - 1 + props.fotos.length) % props.fotos.length)
}
function next() {
  if (!open.value) return
  setIndex((props.index + 1) % props.fotos.length)
}
function fmtSize(n) {
  if (!n) return ''
  return n > 1024 * 1024 ? (n / 1024 / 1024).toFixed(1) + ' MB' : Math.round(n / 1024) + ' KB'
}

function onKey(e) {
  if (!open.value) return
  if (e.key === 'Escape') close()
  else if (e.key === 'ArrowLeft') prev()
  else if (e.key === 'ArrowRight') next()
}
onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))
// Jaga indeks tetap valid bila foto berkurang (mis. dihapus saat lightbox terbuka).
watch(() => props.fotos.length, (n) => { if (props.index >= n) close() })
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[100] bg-black/85 backdrop-blur-sm flex items-center justify-center p-4" @click.self="close">
      <!-- Tutup -->
      <button type="button" @click="close" title="Tutup (Esc)"
        class="absolute top-3 right-3 sm:top-5 sm:right-5 p-2 bg-black/50 hover:bg-black/70 text-white rounded-full transition-colors">
        <X class="w-5 h-5 sm:w-6 sm:h-6" />
      </button>
      <!-- Counter -->
      <div class="absolute top-3 left-3 sm:top-5 sm:left-5 px-3 py-1.5 bg-black/50 text-white text-xs sm:text-sm font-mono rounded-full tnum">
        {{ index + 1 }} / {{ fotos.length }}
      </div>
      <!-- Caption -->
      <div v-if="current?.original_name" class="absolute bottom-3 left-1/2 -translate-x-1/2 px-3 py-1.5 bg-black/50 text-white text-[11px] sm:text-xs rounded-full max-w-[80vw] truncate">
        {{ current.original_name }}<span v-if="current.size_bytes" class="ml-2 opacity-70">· {{ fmtSize(current.size_bytes) }}</span>
      </div>
      <!-- Navigasi kiri -->
      <button v-if="fotos.length > 1" type="button" @click="prev" title="Sebelumnya (←)"
        class="absolute left-2 sm:left-5 top-1/2 -translate-y-1/2 p-2 sm:p-3 bg-black/40 hover:bg-black/60 text-white rounded-full transition-colors">
        <ChevronLeft class="w-6 h-6 sm:w-8 sm:h-8" />
      </button>
      <img :src="current.url" :alt="current.original_name || 'Foto rumah'"
        class="max-w-full max-h-[80vh] object-contain rounded-md shadow-2xl select-none" />
      <!-- Navigasi kanan -->
      <button v-if="fotos.length > 1" type="button" @click="next" title="Selanjutnya (→)"
        class="absolute right-2 sm:right-5 top-1/2 -translate-y-1/2 p-2 sm:p-3 bg-black/40 hover:bg-black/60 text-white rounded-full transition-colors">
        <ChevronRight class="w-6 h-6 sm:w-8 sm:h-8" />
      </button>
    </div>
  </Teleport>
</template>
