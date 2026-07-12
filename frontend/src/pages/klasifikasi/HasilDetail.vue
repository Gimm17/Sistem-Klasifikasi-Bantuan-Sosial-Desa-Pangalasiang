<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Klasifikasi } from '@/services/endpoints'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import ProbabilityBreakdownTable from '@/components/ProbabilityBreakdownTable.vue'
import ProbabilityGauge from '@/components/ProbabilityGauge.vue'
import LikelihoodBarChart from '@/components/LikelihoodBarChart.vue'
import { User, CreditCard, Cpu, ShieldCheck, BarChart3, TrendingUp, Table, AlertTriangle, FileText } from '@lucide/vue'

const route = useRoute()
const hasil = ref(null)
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true; error.value = ''
  try { hasil.value = await Klasifikasi.get(route.params.id) }
  catch { error.value = 'Hasil tidak ditemukan.' }
  finally { loading.value = false }
}
onMounted(load)

const bd = (h) => h?.breakdown ?? {}
</script>

<template>
  <div>
    <PageHeader title="Rincian Hasil Klasifikasi" subtitle="Breakdown probabilitas dan transparansi perhitungan Naive Bayes (Explainable AI)" />

    <div class="p-6 space-y-6">
      <div v-if="error" class="p-4 bg-[#F8D7DA] text-[#721C24] rounded-xl border border-[#721C24]/20 text-xs font-semibold flex items-center gap-2">
        <AlertTriangle class="w-4 h-4 flex-shrink-0" />
        <span>{{ error }}</span>
      </div>
      <div v-if="loading" class="text-[#9CA3AF] py-12 text-center text-sm font-medium">Memuat rincian perhitungan klasifikasi…</div>

      <div v-if="hasil" class="space-y-6">
        <!-- Ringkas -->
        <div class="bg-white rounded-xl shadow-xs p-5 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm border border-[#D5D3C9]">
          <div class="p-3 bg-[#F8F7F2] rounded-xl border border-[#EBE9E0]">
            <p class="text-[#4B5563] text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5 mb-1">
              <User class="w-3.5 h-3.5 text-[#96B6C5]" />
              <span>Nama Warga</span>
            </p>
            <p class="font-bold text-[#1F2937] text-base truncate">{{ hasil.warga?.nama }}</p>
          </div>
          <div class="p-3 bg-[#F8F7F2] rounded-xl border border-[#EBE9E0]">
            <p class="text-[#4B5563] text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5 mb-1">
              <CreditCard class="w-3.5 h-3.5 text-[#96B6C5]" />
              <span>NIK Warga</span>
            </p>
            <p class="font-mono text-xs text-[#1F2937] font-semibold tnum">{{ hasil.warga?.nik }}</p>
          </div>
          <div class="p-3 bg-[#F8F7F2] rounded-xl border border-[#EBE9E0]">
            <p class="text-[#4B5563] text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5 mb-1">
              <Cpu class="w-3.5 h-3.5 text-[#96B6C5]" />
              <span>Versi Model NB</span>
            </p>
            <p class="text-xs font-mono text-[#1A3B47] font-bold bg-[#E6F0F4] px-2 py-0.5 rounded inline-block mt-0.5">{{ hasil.model_version }}</p>
          </div>
          <div class="p-3 bg-[#F8F7F2] rounded-xl border border-[#EBE9E0]">
            <p class="text-[#4B5563] text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5 mb-1">
              <ShieldCheck class="w-3.5 h-3.5 text-[#96B6C5]" />
              <span>Status Approval</span>
            </p>
            <div class="mt-1"><StatusBadge type="approval" :value="hasil.status_approval" /></div>
          </div>
        </div>

        <!-- Gauge Probabilitas -->
        <div class="bg-white rounded-xl shadow-xs p-6 border border-[#D5D3C9]">
          <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
              <h3 class="font-bold text-[#1F2937] text-base flex items-center gap-2">
                <BarChart3 class="w-5 h-5 text-[#96B6C5]" />
                <span>Probabilitas Akhir Normalisasi</span>
              </h3>
              <p class="text-xs text-[#4B5563]">Hasil persentase kelayakan setelah normalisasi probabilitas layak vs tidak layak</p>
            </div>
            <span class="bg-[#D4EDDA] text-[#155724] text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">Hasil Prediksi Mesin</span>
          </div>
          <ProbabilityGauge
            :prob-layak="hasil.prob_layak"
            :prob-tidak-layak="hasil.prob_tidak_layak"
            :prediksi="hasil.prediksi_kelas"
          />
        </div>

        <!-- Likelihood Bar Chart (Visualisasi Atribut) -->
        <div class="bg-white rounded-xl shadow-xs p-6 border border-[#D5D3C9]">
          <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
              <h3 class="font-bold text-[#1F2937] text-base flex items-center gap-2">
                <TrendingUp class="w-5 h-5 text-[#96B6C5]" />
                <span>Visualisasi Likelihood Atribut P(x | Kelas)</span>
              </h3>
              <p class="text-xs text-[#4B5563]">Perbandingan peluang munculnya nilai atribut warga pada masing-masing kelas kelayakan</p>
            </div>
            <span class="bg-[#E6F0F4] text-[#1A3B47] text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">Bab IV - Skripsi</span>
          </div>
          <LikelihoodBarChart
            :detail="bd(hasil)?.detail ?? bd(hasil)"
            :kategori-input="bd(hasil)?.kategori_input"
          />
        </div>

        <!-- Breakdown explainable table -->
        <div class="bg-white rounded-xl shadow-xs p-6 border border-[#D5D3C9]">
          <div class="mb-4">
            <h3 class="font-bold text-[#1F2937] text-base flex items-center gap-2">
              <Table class="w-5 h-5 text-[#96B6C5]" />
              <span>Tabel Rincian Perhitungan Naive Bayes</span>
            </h3>
            <p class="text-xs text-[#4B5563]">Rincian perkalian Prior Probability dengan Likelihood setiap atribut (rumus: P(c) &times; &Pi; P(xᵢ|c))</p>
          </div>
          <ProbabilityBreakdownTable
            :breakdown="bd(hasil)"
            :prob-layak="hasil.prob_layak"
            :prob-tidak-layak="hasil.prob_tidak_layak"
            :prediksi="hasil.prediksi_kelas"
            :kategori-input="bd(hasil)?.kategori_input"
          />
        </div>

        <!-- Info override -->
        <div v-if="bd(hasil)?.override" class="bg-[#D1ECF1]/50 border border-[#0C5460]/20 rounded-xl p-5 text-sm shadow-xs">
          <p class="font-bold text-[#0C5460] flex items-center gap-2 text-base">
            <AlertTriangle class="w-5 h-5 flex-shrink-0" />
            <span>Keputusan Di-override oleh Approver</span>
          </p>
          <p class="text-[#0C5460] mt-2 text-xs leading-relaxed">Kelas asli prediksi mesin Naive Bayes: <b class="uppercase font-mono bg-white px-2 py-0.5 rounded border">{{ bd(hasil).prediksi_asli }}</b> &rarr; diubah oleh verifikator menjadi: <b class="uppercase font-mono bg-white px-2 py-0.5 rounded border">{{ hasil.prediksi_kelas }}</b></p>
          <p class="text-[#0C5460] mt-2 text-xs pt-2 border-t border-[#0C5460]/20 flex items-center justify-between">
            <span>Oleh Verifikator: <b class="font-semibold">{{ bd(hasil).override.name }}</b></span>
            <span>Alasan: "<span class="italic font-medium">{{ bd(hasil).override.alasan }}</span>"</span>
          </p>
        </div>

        <div v-if="hasil.catatan_approval" class="bg-[#FFF3CD]/50 border border-[#856404]/20 rounded-xl p-5 text-xs text-[#856404] shadow-xs flex items-start gap-3">
          <FileText class="w-5 h-5 flex-shrink-0 text-[#856404] mt-0.5" />
          <div>
            <b class="text-sm block text-[#856404] mb-1">Catatan Verifikasi / Approval:</b>
            <span class="italic text-[#856404]/90">{{ hasil.catatan_approval }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

