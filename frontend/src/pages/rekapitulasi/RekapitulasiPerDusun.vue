<script setup>
import { ref, onMounted, computed } from 'vue'
import { Rekapitulasi } from '@/services/endpoints'
import PageHeader from '@/components/PageHeader.vue'
import { Map, TrendingUp, BarChart3, Users, Download } from '@lucide/vue'

const data = ref(null)
const loading = ref(true)

async function load() {
  loading.value = true
  try {
    const res = await Rekapitulasi.perDusun()
    data.value = res
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}
onMounted(load)

const maxLayak = computed(() => {
  if (!data.value?.detail?.length) return 1
  return Math.max(...data.value.detail.map(d => d.layak))
})
const maxTidak = computed(() => {
  if (!data.value?.detail?.length) return 1
  return Math.max(...data.value.detail.map(d => d.tidak_layak))
})
</script>

<template>
  <div>
    <PageHeader title="Rekapitulasi Per Dusun" subtitle="Distribusi klasifikasi bantuan sosial berdasarkan wilayah dusun">
      <template #actions>
        <button @click="Rekapitulasi.previewRekapDusun()" class="inline-flex items-center gap-1.5 px-4 py-2 border border-[#96B6C5] text-[#96B6C5] rounded-xl text-xs font-semibold hover:bg-[#96B6C5]/10 transition-colors">
          <Download class="w-3.5 h-3.5" />
          <span>Export Laporan PDF</span>
        </button>
      </template>
    </PageHeader>

    <div class="p-6 space-y-6">
      <!-- Loading -->
      <div v-if="loading" class="text-center py-12 text-[#9CA3AF] text-sm font-medium">Memuat data rekapitulasi...</div>

      <template v-if="data && !loading">
        <!-- Stat Cards -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="bg-white rounded-xl p-5 border border-[#D5D3C9] shadow-[0_1px_3px_rgba(0,0,0,0.06)] flex flex-col gap-3">
            <div class="flex items-center justify-between">
              <p class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Total Dusun</p>
              <span class="w-8 h-8 bg-[#E6F0F4] rounded-lg flex items-center justify-center">
                <Map class="w-4 h-4 text-[#446370]" />
              </span>
            </div>
            <p class="text-2xl font-bold text-[#1F2937]">{{ data.total_dusun }}</p>
          </div>

          <div class="bg-white rounded-xl p-5 border border-[#D5D3C9] shadow-[0_1px_3px_rgba(0,0,0,0.06)] flex flex-col gap-3 relative overflow-hidden">
            <div class="absolute -right-6 -top-6 text-[#EEE0C9] opacity-40">
              <TrendingUp class="w-20 h-20" />
            </div>
            <div class="relative z-10">
              <p class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Dusun Penerima Tertinggi</p>
              <p class="text-2xl font-bold text-[#1F2937] mt-1">{{ data.dusun_tertinggi?.dusun ?? '-' }}</p>
              <p class="text-xs font-semibold text-[#27AE60] flex items-center gap-1 mt-1">
                <TrendingUp class="w-3.5 h-3.5" />
                {{ data.dusun_tertinggi?.pct_layak ?? 0 }}% dari total
              </p>
            </div>
          </div>

          <div class="bg-white rounded-xl p-5 border border-[#D5D3C9] shadow-[0_1px_3px_rgba(0,0,0,0.06)] flex flex-col gap-3">
            <div class="flex items-center justify-between">
              <p class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Rata-rata Prob. Layak</p>
              <span class="w-8 h-8 bg-[#E6F0F4] rounded-lg flex items-center justify-center">
                <BarChart3 class="w-4 h-4 text-[#17A2B8]" />
              </span>
            </div>
            <p class="text-2xl font-bold text-[#1F2937]">{{ data.rata_rata_prob_layak }}%</p>
            <div class="w-full bg-[#EBE9E0] h-2 rounded-full">
              <div class="bg-[#17A2B8] h-2 rounded-full" :style="{ width: data.rata_rata_prob_layak + '%' }"></div>
            </div>
          </div>

          <div class="bg-[#EEE0C9] rounded-xl p-5 border border-[#d2c5af] shadow-[0_1px_3px_rgba(0,0,0,0.06)] flex flex-col gap-3">
            <div class="flex items-center justify-between">
              <p class="text-xs font-semibold uppercase tracking-wider text-[#4E4635]">Total Terklasifikasi</p>
              <span class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm">
                <Users class="w-4 h-4 text-[#675d4b]" />
              </span>
            </div>
            <p class="text-2xl font-bold text-[#211B0D]">{{ (data.total_layak || 0) + (data.total_tidak_layak || 0) }}</p>
            <p class="text-xs font-semibold text-[#4E4635]">Seluruh wilayah</p>
          </div>
        </section>

        <!-- Tabel Rincian -->
        <section class="bg-white rounded-xl border border-[#D5D3C9] shadow-[0_1px_3px_rgba(0,0,0,0.06)] overflow-hidden">
          <div class="p-5 border-b border-[#EBE9E0] bg-[#F8F7F2]/50 flex justify-between items-center">
            <h3 class="font-bold text-[#1F2937] text-base">Rincian Per Dusun</h3>
            <span class="text-xs text-[#4B5563]">Total warga terdata: {{ data.total_warga }}</span>
          </div>

          <div v-if="!data.detail?.length" class="p-8 text-center text-[#9CA3AF] text-sm">
            Belum ada data dusun. Tambahkan field dusun pada data warga terlebih dahulu.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
              <thead>
                <tr class="bg-[#E6F0F4]/60">
                  <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-[#1F2937] border-b border-[#EBE9E0] w-12 text-center">No</th>
                  <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-[#1F2937] border-b border-[#EBE9E0]">Nama Dusun</th>
                  <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-[#1F2937] border-b border-[#EBE9E0] text-right">Total Warga</th>
                  <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-[#1F2937] border-b border-[#EBE9E0] text-right">Layak</th>
                  <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-[#1F2937] border-b border-[#EBE9E0] text-right">Tidak Layak</th>
                  <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-[#1F2937] border-b border-[#EBE9E0] text-right">% Layak</th>
                  <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-[#1F2937] border-b border-[#EBE9E0] text-right">Rata-rata Prob.</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#EBE9E0]">
                <tr v-for="(d, i) in data.detail" :key="d.dusun" class="hover:bg-[#F8F7F2] transition-colors">
                  <td class="px-4 py-3.5 text-center text-xs text-[#4B5563]">{{ i + 1 }}</td>
                  <td class="px-4 py-3.5 font-semibold text-[#446370] text-sm">{{ d.dusun }}</td>
                  <td class="px-4 py-3.5 text-right text-sm font-medium">{{ d.total_warga }}</td>
                  <td class="px-4 py-3.5 text-right text-sm font-semibold text-[#27AE60]">{{ d.layak }}</td>
                  <td class="px-4 py-3.5 text-right text-sm text-[#4B5563]">{{ d.tidak_layak }}</td>
                  <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <span class="text-sm font-semibold :class d.pct_layak >= 70 ? 'text-[#27AE60]' : ''">{{ d.pct_layak }}%</span>
                      <div class="w-16 bg-[#EBE9E0] h-1.5 rounded-full">
                        <div class="h-1.5 rounded-full" :class="d.pct_layak >= 70 ? 'bg-[#27AE60]' : d.pct_layak >= 40 ? 'bg-[#F39C12]' : 'bg-[#DC3545]'" :style="{ width: d.pct_layak + '%' }"></div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3.5 text-right text-sm font-mono tnum font-semibold">{{ d.avg_prob_layak }}%</td>
                </tr>
              </tbody>
              <tfoot class="bg-[#F8F7F2]/50 text-xs font-semibold border-t-2 border-[#D5D3C9]">
                <tr>
                  <td colspan="2" class="px-4 py-3.5 text-right font-bold text-[#1F2937]">TOTAL KESELURUHAN</td>
                  <td class="px-4 py-3.5 text-right font-bold text-[#1F2937]">{{ data.total_warga }}</td>
                  <td class="px-4 py-3.5 text-right font-bold text-[#27AE60]">{{ data.total_layak }}</td>
                  <td class="px-4 py-3.5 text-right font-bold text-[#4B5563]">{{ data.total_tidak_layak }}</td>
                  <td class="px-4 py-3.5 text-right font-bold">{{ data.total_warga > 0 ? ((data.total_layak / data.total_warga) * 100).toFixed(1) : 0 }}%</td>
                  <td class="px-4 py-3.5 text-right font-bold font-mono">{{ data.rata_rata_prob_layak }}%</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </section>
      </template>

      <!-- Empty state ketika data null -->
      <div v-if="!data && !loading" class="bg-white rounded-xl p-12 text-center text-[#9CA3AF] text-sm font-medium border border-[#D5D3C9]">
        Gagal memuat data rekapitulasi. Pastikan ada warga dengan field dusun terisi.
      </div>
    </div>
  </div>
</template>
