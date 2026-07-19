<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { Warga, Atribut, Klasifikasi } from '@/services/endpoints'
import { useAuthStore } from '@/stores/auth'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import Skeleton from '@/components/Skeleton.vue'
import WargaFotoGallery from '@/components/WargaFotoGallery.vue'
import WargaFotoLightbox from '@/components/WargaFotoLightbox.vue'
import { Zap, User, History, ArrowRight, AlertCircle, CheckCircle2, Camera, FileDown, X } from '@lucide/vue'

const route = useRoute()
const auth = useAuthStore()
const warga = ref(null)
const loading = ref(true)
const error = ref('')

// Modal validasi
const showValidasiModal = ref(false)
const atribut = ref([])
const kondisiLabels = computed(() => (atribut.value.find((a) => a.kode === 'kondisi_rumah')?.kategori ?? []).map((k) => k.label))
const validasiLabel = ref('')
const validasiSaving = ref(false)
const validasiError = ref('')

// PDF
const showPdfModal = ref(false)
const pdfIncludeFoto = ref(false)

// Lightbox (modal validasi preview foto diklik → ukuran besar)
const lightboxIndex = ref(-1)
function openLightbox(i) {
  if (i < 0 || i >= (warga.value?.fotos?.length ?? 0)) return
  lightboxIndex.value = i
}

async function load() {
  loading.value = true
  try {
    warga.value = await Warga.get(route.params.id)
  } catch { error.value = 'Data tidak ditemukan.' }
  finally { loading.value = false }
}
onMounted(async () => { await load(); await loadAtribut(); if (route.query.validasi === '1') showValidasiModal.value = true })

async function loadAtribut() {
  try { atribut.value = await Atribut.list() } catch { /* abaikan */ }
}

async function onFotosChanged() {
  await load()
}

async function runKlasifikasi() {
  error.value = ''
  try {
    await Klasifikasi.predict(route.params.id)
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal klasifikasi.'
  }
}

function openValidasi() {
  validasiLabel.value = warga.value?.kondisi_rumah ?? ''
  validasiError.value = ''
  showValidasiModal.value = true
}

async function submitValidasi() {
  validasiError.value = ''
  if (!validasiLabel.value) { validasiError.value = 'Pilih label kondisi rumah terlebih dahulu.'; return }
  validasiSaving.value = true
  try {
    await Warga.validasi(route.params.id, { kondisi_rumah: validasiLabel.value })
    showValidasiModal.value = false
    await load()
  } catch (e) {
    validasiError.value = e.response?.data?.message || e.response?.data?.errors?.kondisi_rumah?.[0] || e.response?.data?.errors?.fotos?.[0] || 'Gagal memvalidasi.'
  } finally {
    validasiSaving.value = false
  }
}

async function cetakKkPdf() {
  const id = route.params.id
  const q = pdfIncludeFoto.value ? '?include_foto=1' : ''
  showPdfModal.value = false
  // PdfPreview.vue baca query.url (endpoint API PDF). Auth cookie ikut via fetch iframe same-origin.
  window.open(`/laporan/pdf-preview?url=${encodeURIComponent(`/api/laporan/rincian-kk/${id}.pdf${q}`)}&title=Rincian+KK`, '_blank')
}

const rupiah = (n) => 'Rp' + Number(n || 0).toLocaleString('id-ID')
const fotoCount = computed(() => warga.value?.fotos?.length ?? 0)
</script>

<template>
  <div>
    <PageHeader title="Detail Warga" :subtitle="warga?.nama ?? ''">
      <template #actions>
        <button v-if="auth.role === 'admin'" @click="openValidasi" :disabled="warga?.status_validasi === 'divalidasi'"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#EEE0C9] text-[#4A3F30] rounded-xl text-xs font-semibold hover:bg-[#E2CFB3] shadow-xs transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
          <CheckCircle2 class="w-4 h-4" />
          <span>{{ warga?.status_validasi === 'divalidasi' ? 'Sudah Divalidasi' : 'Validasi Data' }}</span>
        </button>
        <button v-if="auth.role === 'admin'" @click="runKlasifikasi" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#96B6C5] text-[#1A3B47] rounded-xl text-xs font-semibold hover:bg-[#7A9EAF] shadow-xs transition-colors">
          <Zap class="w-4 h-4" />
          <span>Jalankan Klasifikasi</span>
        </button>
      </template>
    </PageHeader>

    <div class="p-6 space-y-6">
      <div v-if="error" class="p-4 bg-[#F8D7DA] text-[#721C24] rounded-xl border border-[#721C24]/20 text-xs font-semibold flex items-center gap-2">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        <span>{{ error }}</span>
      </div>

      <!-- Banner: belum ada foto / belum divalidasi -->
      <div v-if="warga && warga.status_validasi !== 'divalidasi'" class="p-4 rounded-xl border text-xs font-medium flex items-center gap-2"
        :class="fotoCount < 1 ? 'bg-[#FFF3CD]/40 border-[#856404]/30 text-[#856404]' : 'bg-[#E6F0F4] border-[#96B6C5]/30 text-[#1A3B47]'">
        <Camera class="w-4 h-4 flex-shrink-0" />
        <span v-if="fotoCount < 1">Belum ada foto rumah. Unggah minimal 1 foto sebelum memvalidasi.</span>
        <span v-else>Foto sudah tersedia. Klik "Validasi Data" untuk meninjau foto & menetapkan label kondisi rumah.</span>
      </div>

      <template v-if="loading">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-6 space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-[#EBE9E0]">
              <Skeleton variant="rect" w="20px" h="20px" rounded="full" />
              <Skeleton w="140px" h="14px" />
            </div>
            <div v-for="i in 8" :key="'sk'+i" class="flex justify-between pt-2">
              <Skeleton w="100px" h="12px" />
              <Skeleton w="120px" h="12px" />
            </div>
          </div>
          <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-6 space-y-4">
            <Skeleton w="100%" h="180px" rounded="lg" />
          </div>
        </div>
      </template>

      <div v-if="warga" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-6">
          <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-6 space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-[#EBE9E0]">
              <User class="w-5 h-5 text-[#96B6C5]" />
              <h3 class="font-bold text-[#1F2937] text-base">Identitas & Atribut Sosial</h3>
            </div>

            <dl class="text-sm space-y-3 divide-y divide-[#EBE9E0]/60">
              <div class="flex justify-between pt-1"><dt class="text-[#4B5563] font-medium">NIK</dt><dd class="font-mono font-semibold text-[#1F2937] tnum">{{ warga.nik }}</dd></div>
              <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Nama Lengkap</dt><dd class="font-bold text-[#1F2937]">{{ warga.nama }}</dd></div>
              <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Alamat Domisili</dt><dd class="text-right text-[#1F2937] max-w-[220px]">{{ warga.alamat || '-' }}</dd></div>
              <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Dusun / Wilayah</dt><dd class="font-semibold text-[#1F2937]">{{ warga.dusun || '-' }}</dd></div>
              <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Penghasilan Bulanan</dt><dd class="font-mono font-bold text-[#1F2937] tnum">{{ rupiah(warga.penghasilan_bulanan) }}</dd></div>
              <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Status Pekerjaan</dt><dd class="font-semibold text-[#1A3B47] bg-[#E6F0F4] px-2.5 py-0.5 rounded-full text-xs">{{ warga.status_pekerjaan }}</dd></div>
              <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Jumlah Tanggungan</dt><dd class="font-mono font-bold text-[#1F2937] tnum">{{ warga.jumlah_tanggungan }} Jiwa</dd></div>
              <div class="flex justify-between items-center pt-2">
                <dt class="text-[#4B5563] font-medium">Kondisi Rumah</dt>
                <dd>
                  <span v-if="warga.kondisi_rumah" class="font-semibold text-[#4A3F30] bg-[#EEE0C9] px-2.5 py-0.5 rounded-full text-xs">{{ warga.kondisi_rumah }}</span>
                  <span v-else class="text-[11px] text-[#856404] bg-[#FFF3CD]/40 px-2.5 py-0.5 rounded-full">Belum dilabeli</span>
                </dd>
              </div>
              <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Periode Data</dt><dd class="font-mono tnum text-[#4B5563]">{{ warga.periode_data }}</dd></div>
              <div class="flex justify-between items-center pt-2"><dt class="text-[#4B5563] font-medium">Status Validasi</dt><dd><StatusBadge type="validasi" :value="warga.status_validasi" /></dd></div>
              <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Petugas Pendata</dt><dd class="text-xs text-[#4B5563] font-medium">{{ warga.created_by?.name || '-' }}</dd></div>
            </dl>

            <div class="pt-4 border-t border-[#EBE9E0] flex flex-wrap gap-2">
              <button v-if="auth.role === 'admin' || auth.role === 'approver' || auth.role === 'superadmin'" @click="showPdfModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-[#D5D3C9] text-[#1F2937] rounded-xl text-xs font-semibold hover:bg-[#F8F7F2] shadow-xs transition-colors">
                <FileDown class="w-4 h-4 text-[#96B6C5]" />
                <span>Cetak Rincian KK (PDF)</span>
              </button>
            </div>
          </div>

          <!-- Galeri foto rumah -->
          <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-6">
            <WargaFotoGallery
              :warga-id="warga.id"
              :fotos="warga.fotos || []"
              :editable="auth.role === 'admin'"
              @changed="onFotosChanged"
            />
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-6 space-y-4">
          <div class="flex items-center gap-2 pb-3 border-b border-[#EBE9E0]">
            <History class="w-5 h-5 text-[#96B6C5]" />
            <h3 class="font-bold text-[#1F2937] text-base">Riwayat Hasil Klasifikasi</h3>
          </div>

          <div v-if="!warga.hasil_klasifikasi?.length" class="py-12 text-center text-sm text-[#9CA3AF] bg-[#F8F7F2] rounded-xl border border-[#E5E3D9]">
            Belum pernah dilakukan klasifikasi kelayakan pada warga ini.
          </div>

          <ul v-else class="space-y-3">
            <li v-for="h in warga.hasil_klasifikasi" :key="h.id" class="p-4 rounded-xl border transition-all" :class="h.prediksi_kelas === 'layak' ? 'bg-[#D4EDDA]/30 border-[#27AE60]/40' : 'bg-[#F8F7F2] border-[#D5D3C9]'">
              <div class="flex justify-between items-center mb-2">
                <StatusBadge type="kelas" :value="h.prediksi_kelas" />
                <RouterLink :to="{ name: 'klasifikasi.show', params: { id: h.id } }" class="inline-flex items-center gap-1 text-xs font-semibold text-[#1A3B47] hover:text-[#96B6C5] bg-[#E6F0F4] px-2.5 py-1 rounded-lg transition-colors">
                  <span>Lihat Breakdown</span>
                  <ArrowRight class="w-3.5 h-3.5" />
                </RouterLink>
              </div>
              <div class="flex items-center justify-between text-xs text-[#4B5563] mt-2 font-medium">
                <span>P(Layak) = <strong class="font-mono text-[#1F2937] tnum">{{ (Number(h.prob_layak)*100).toFixed(1) }}%</strong></span>
                <span class="font-mono bg-white px-2 py-0.5 rounded border border-[#E5E3D9] text-[11px]">{{ h.model_version }}</span>
              </div>
              <div class="flex items-center justify-between text-xs text-[#9CA3AF] mt-2 pt-2 border-t border-[#EBE9E0]/60">
                <span class="flex items-center gap-1">Status: <StatusBadge type="approval" :value="h.status_approval" /></span>
                <span class="font-mono tnum">{{ new Date(h.created_at).toLocaleString('id-ID') }}</span>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Modal Validasi -->
    <div v-if="showValidasiModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showValidasiModal = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-bold text-[#1F2937]">Validasi Data Warga</h3>
            <p class="text-sm text-[#4B5563] mt-0.5">Tinjau foto rumah, lalu tetapkan label kondisi rumah.</p>
          </div>
          <button @click="showValidasiModal = false" class="p-1.5 text-[#9CA3AF] hover:text-[#1F2937] hover:bg-[#F8F7F2] rounded-lg transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Foto preview (read-only) — klik thumbnail → lightbox ukuran besar -->
        <div v-if="fotoCount" class="grid grid-cols-4 gap-2 mb-5">
          <div v-for="(f, i) in warga.fotos" :key="f.id"
            class="rounded-lg overflow-hidden border border-[#D5D3C9] aspect-square cursor-zoom-in"
            @click="openLightbox(i)">
            <img :src="f.url" class="w-full h-full object-cover" loading="lazy" />
          </div>
        </div>
        <div v-else class="mb-5 p-4 bg-[#FFF3CD]/40 border border-[#856404]/30 rounded-xl text-xs text-[#856404] font-medium flex items-center gap-2">
          <AlertCircle class="w-4 h-4" />
          <span>Belum ada foto rumah. Unggah minimal 1 foto di galeri sebelum memvalidasi.</span>
        </div>

        <label class="block mb-5">
          <span class="text-sm font-semibold text-[#4B5563]">Label Kondisi Rumah <span class="text-[#DC3545]">*</span></span>
          <select v-model="validasiLabel" :disabled="fotoCount < 1"
            class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] disabled:opacity-50">
            <option value="">— Pilih kondisi rumah —</option>
            <option v-for="l in kondisiLabels" :key="l" :value="l">{{ l }}</option>
          </select>
        </label>

        <div v-if="validasiError" class="mb-4 p-3 bg-[#F8D7DA] text-[#721C24] rounded-xl border border-[#721C24]/20 text-xs font-semibold flex items-center gap-2">
          <AlertCircle class="w-4 h-4 flex-shrink-0" />
          <span>{{ validasiError }}</span>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EBE9E0]">
          <button @click="showValidasiModal = false" class="px-5 py-2 text-sm font-medium text-[#4B5563] hover:bg-[#F8F7F2] rounded-xl transition-colors">Batal</button>
          <button @click="submitValidasi" :disabled="validasiSaving || fotoCount < 1 || !validasiLabel"
            class="inline-flex items-center gap-2 px-5 py-2 bg-[#1F2937] text-white rounded-xl text-sm font-semibold hover:bg-[#374151] disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            <CheckCircle2 class="w-4 h-4 text-[#96B6C5]" />
            <span>{{ validasiSaving ? 'Memvalidasi…' : 'Validasi & Tetapkan Label' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Cetak Rincian KK -->
    <div v-if="showPdfModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showPdfModal = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-bold text-[#1F2937]">Cetak Rincian KK</h3>
            <p class="text-sm text-[#4B5563] mt-0.5">Lembar analisis kelayakan per KK (PDF).</p>
          </div>
          <button @click="showPdfModal = false" class="p-1.5 text-[#9CA3AF] hover:text-[#1F2937] hover:bg-[#F8F7F2] rounded-lg transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>

        <label class="flex items-center gap-3 p-3 rounded-xl border border-[#D5D3C9] hover:bg-[#F8F7F2] transition-colors cursor-pointer mb-2">
          <input type="checkbox" v-model="pdfIncludeFoto" class="w-4 h-4 rounded border-[#D5D3C9] text-[#96B6C5] focus:ring-[#96B6C5]" :disabled="fotoCount < 1" />
          <div>
            <p class="text-sm font-medium text-[#1F2937]">Sertakan foto rumah</p>
            <p class="text-xs text-[#9CA3AF]">Cetak dokumentasi foto kondisi rumah di laporan{{ fotoCount < 1 ? ' (tidak ada foto)' : '' }}</p>
          </div>
        </label>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EBE9E0]">
          <button @click="showPdfModal = false" class="px-5 py-2 text-sm font-medium text-[#4B5563] hover:bg-[#F8F7F2] rounded-xl transition-colors">Batal</button>
          <button @click="cetakKkPdf" class="inline-flex items-center gap-2 px-5 py-2 bg-[#96B6C5] text-white rounded-xl text-sm font-semibold hover:bg-[#7A9EAF] shadow-xs transition-colors">
            <FileDown class="w-4 h-4" />
            <span>Cetak PDF</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Lightbox validasi preview (dipakai saat klik thumbnail di modal validasi) -->
    <WargaFotoLightbox v-model:index="lightboxIndex" :fotos="warga?.fotos || []" />
  </div>
</template>
