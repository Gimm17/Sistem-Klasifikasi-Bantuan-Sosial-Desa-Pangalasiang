<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Warga, Klasifikasi } from '@/services/endpoints'
import { useAuthStore } from '@/stores/auth'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { Zap, User, History, ArrowRight, Clock, CheckCircle, AlertCircle } from '@lucide/vue'

const route = useRoute()
const auth = useAuthStore()
const warga = ref(null)
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true
  try {
    const res = await Warga.get(route.params.id)
    warga.value = res.data ?? res
  } catch { error.value = 'Data tidak ditemukan.' }
  finally { loading.value = false }
}
onMounted(load)

async function runKlasifikasi() {
  error.value = ''
  try {
    await Klasifikasi.predict(route.params.id)
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal klasifikasi.'
  }
}

const rupiah = (n) => 'Rp' + Number(n || 0).toLocaleString('id-ID')
</script>

<template>
  <div>
    <PageHeader title="Detail Warga" :subtitle="warga?.nama ?? ''">
      <template #actions>
        <button v-if="auth.role === 'admin'" @click="runKlasifikasi" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#96B6C5] text-[#1A3B47] rounded-xl text-xs font-semibold hover:bg-[#7A9EAF] shadow-xs transition-colors">
          <Zap class="w-4 h-4" />
          <span>Jalankan Klasifikasi Kelayakan</span>
        </button>
      </template>
    </PageHeader>

    <div class="p-6 space-y-6">
      <div v-if="error" class="p-4 bg-[#F8D7DA] text-[#721C24] rounded-xl border border-[#721C24]/20 text-xs font-semibold flex items-center gap-2">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        <span>{{ error }}</span>
      </div>
      <div v-if="loading" class="py-12 text-center text-sm text-[#9CA3AF] font-medium">Memuat data warga…</div>

      <div v-if="warga" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
            <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Kondisi Rumah</dt><dd class="font-semibold text-[#4A3F30] bg-[#EEE0C9] px-2.5 py-0.5 rounded-full text-xs">{{ warga.kondisi_rumah }}</dd></div>
            <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Periode Data</dt><dd class="font-mono tnum text-[#4B5563]">{{ warga.periode_data }}</dd></div>
            <div class="flex justify-between items-center pt-2"><dt class="text-[#4B5563] font-medium">Status Validasi</dt><dd><StatusBadge type="validasi" :value="warga.status_validasi" /></dd></div>
            <div class="flex justify-between pt-2"><dt class="text-[#4B5563] font-medium">Petugas Pendata</dt><dd class="text-xs text-[#4B5563] font-medium">{{ warga.created_by?.name || '-' }}</dd></div>
          </dl>
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
  </div>
</template>

