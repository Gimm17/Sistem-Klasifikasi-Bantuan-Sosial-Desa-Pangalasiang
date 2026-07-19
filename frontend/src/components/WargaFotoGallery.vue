<script setup>
/**
 * Galeri foto rumah warga (dokumentasi kondisi rumah).
 * Upload multi-file, hapus, counter n/5. Maks 5 foto, 2MB/foto (dipaksa backend).
 */
import { ref, computed } from 'vue'
import { Warga } from '@/services/endpoints'
import WargaFotoLightbox from '@/components/WargaFotoLightbox.vue'
import { Camera, Upload, Trash2, Loader2, Maximize2 } from '@lucide/vue'

const props = defineProps({
  wargaId: { type: [Number, String], required: true },
  fotos: { type: Array, default: () => [] }, // [{id,url,urutan,original_name,size_bytes}]
  editable: { type: Boolean, default: true },
  max: { type: Number, default: 5 },
})
const emit = defineEmits(['changed'])

const uploading = ref(false)
const error = ref('')
const fileInput = ref(null)

const remaining = computed(() => props.max - props.fotos.length)

// --- Lightbox (klik thumbnail → preview ukuran besar) ---
const lightboxIndex = ref(-1)
function openLightbox(i) {
  if (i < 0 || i >= props.fotos.length) return
  lightboxIndex.value = i
}

async function onPick(e) {
  const files = Array.from(e.target.files || [])
  e.target.value = '' // reset agar file sama bisa dipilih ulang
  if (!files.length) return
  if (props.fotos.length + files.length > props.max) {
    error.value = `Maksimal ${props.max} foto. Sisa slot: ${remaining.value}.`
    return
  }
  error.value = ''
  uploading.value = true
  try {
    await Warga.uploadFotos(props.wargaId, files)
    emit('changed')
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.errors?.fotos?.[0] || 'Gagal mengunggah foto.'
  } finally {
    uploading.value = false
  }
}

async function remove(fotoId) {
  if (!confirm('Hapus foto ini?')) return
  error.value = ''
  try {
    await Warga.removeFoto(props.wargaId, fotoId)
    emit('changed')
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.errors?.fotos?.[0] || 'Gagal menghapus foto.'
  }
}

function fmtSize(n) {
  if (!n) return ''
  return n > 1024 * 1024 ? (n / 1024 / 1024).toFixed(1) + ' MB' : Math.round(n / 1024) + ' KB'
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <Camera class="w-4 h-4 text-[#96B6C5]" />
        <h4 class="text-sm font-semibold text-[#1F2937]">Dokumentasi Foto Rumah</h4>
        <span class="text-[11px] font-mono bg-[#E6F0F4] text-[#1A3B47] px-2 py-0.5 rounded-full tnum">
          {{ fotos.length }}/{{ max }}
        </span>
      </div>
      <button
        v-if="editable && remaining > 0"
        type="button"
        :disabled="uploading"
        @click="fileInput?.click()"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#1F2937] text-white rounded-lg text-xs font-semibold hover:bg-[#374151] disabled:opacity-50 transition-colors"
      >
        <Loader2 v-if="uploading" class="w-3.5 h-3.5 animate-spin" />
        <Upload v-else class="w-3.5 h-3.5 text-[#96B6C5]" />
        <span>{{ uploading ? 'Mengunggah…' : 'Tambah Foto' }}</span>
      </button>
    </div>

    <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="onPick" />

    <div v-if="error" class="mb-3 p-2.5 bg-[#F8D7DA] text-[#721C24] rounded-lg border border-[#721C24]/20 text-xs font-medium">
      {{ error }}
    </div>

    <div v-if="fotos.length" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
      <div
        v-for="(f, i) in fotos"
        :key="f.id"
        class="relative group rounded-xl overflow-hidden border border-[#D5D3C9] bg-[#F8F7F2] aspect-square cursor-zoom-in"
        @click="openLightbox(i)"
      >
        <img :src="f.url" :alt="f.original_name || 'Foto rumah'" class="w-full h-full object-cover" loading="lazy" />
        <div class="absolute top-1.5 left-1.5 p-1 bg-black/50 text-white rounded-md opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
          <Maximize2 class="w-3.5 h-3.5" />
        </div>
        <button
          v-if="editable"
          type="button"
          @click.stop="remove(f.id)"
          title="Hapus foto"
          class="absolute top-1.5 right-1.5 p-1 bg-black/60 text-white rounded-md opacity-0 group-hover:opacity-100 transition-opacity hover:bg-[#DC3545]"
        >
          <Trash2 class="w-3.5 h-3.5" />
        </button>
        <span v-if="f.size_bytes" class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[9px] px-1.5 py-0.5 tnum pointer-events-none">{{ fmtSize(f.size_bytes) }}</span>
      </div>
    </div>

    <div v-else class="py-8 text-center text-xs text-[#9CA3AF] bg-[#F8F7F2] rounded-xl border border-dashed border-[#D5D3C9]">
      <Camera class="w-7 h-7 mx-auto mb-2 text-[#D5D3C9]" />
      Belum ada foto rumah.
      <span v-if="editable" class="block mt-1">Klik "Tambah Foto" untuk mengunggah (jpg/png/webp, maks 2MB).</span>
    </div>

    <!-- Lightbox: preview foto ukuran besar (komponen shared, dipakai juga di modal validasi) -->
    <WargaFotoLightbox v-model:index="lightboxIndex" :fotos="fotos" />
  </div>
</template>
