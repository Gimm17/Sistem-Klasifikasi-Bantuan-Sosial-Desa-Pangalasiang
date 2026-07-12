<script setup>
import { computed } from 'vue'

const props = defineProps({
  detail: { type: Object, default: () => ({}) },
  kategoriInput: { type: Object, default: () => ({}) }
})

const atributList = [
  { key: 'penghasilan', label: 'Penghasilan Bulanan' },
  { key: 'pekerjaan', label: 'Status Pekerjaan' },
  { key: 'tanggungan', label: 'Jumlah Tanggungan' },
  { key: 'kondisi_rumah', label: 'Kondisi Rumah' }
]

const chartItems = computed(() => {
  return atributList.map(attr => {
    const kat = props.kategoriInput[attr.key] ?? props.detail?.layak?.atribut?.[attr.key]?.kategori ?? '-'
    const likeLayak = Number(props.detail?.layak?.atribut?.[attr.key]?.likelihood || 0)
    const likeTidak = Number(props.detail?.tidak_layak?.atribut?.[attr.key]?.likelihood || 0)
    const total = likeLayak + likeTidak
    const pctLayak = total > 0 ? round((likeLayak / total) * 100) : 50
    const pctTidak = total > 0 ? round((likeTidak / total) * 100) : 50

    return {
      key: attr.key,
      label: attr.label,
      kategori: kat,
      likeLayak,
      likeTidak,
      pctLayak,
      pctTidak
    }
  })
})

function round(val) {
  return Math.round(val * 10) / 10
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between text-xs text-[#4B5563] pb-2 border-b border-[#EBE9E0]">
      <span>Perbandingan Likelihood P(Atribut | Kelas)</span>
      <div class="flex items-center gap-3 font-medium">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-[#27AE60]"></span> P(x | Layak)</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-[#ADC4CE]"></span> P(x | Tidak Layak)</span>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="item in chartItems" :key="item.key" class="bg-white border border-[#D5D3C9] rounded-xl p-4 shadow-xs space-y-2.5">
        <div class="flex justify-between items-start text-xs">
          <div>
            <p class="font-semibold text-[#1F2937] text-sm">{{ item.label }}</p>
            <p class="text-[11px] font-medium text-[#1A3B47] bg-[#E6F0F4] inline-block px-2 py-0.5 rounded-md mt-1">Nilai: {{ item.kategori }}</p>
          </div>
          <div class="text-right font-mono tnum text-xs">
            <p class="text-[#27AE60] font-bold">{{ item.likeLayak.toFixed(4) }}</p>
            <p class="text-[#4B5563]">{{ item.likeTidak.toFixed(4) }}</p>
          </div>
        </div>

        <!-- Visual comparison bar -->
        <div class="space-y-1.5 pt-1">
          <div class="flex items-center gap-2 text-[11px]">
            <span class="w-10 text-right font-medium text-[#155724]">Layak</span>
            <div class="flex-1 bg-[#F0F4F6] rounded-full h-2.5 overflow-hidden">
              <div class="bg-[#27AE60] h-full rounded-full transition-all duration-500" :style="{ width: Math.min(item.likeLayak * 100 * 1.5, 100) + '%' }"></div>
            </div>
            <span class="font-mono tnum w-12 text-[#4B5563] font-medium">{{ (item.likeLayak * 100).toFixed(1) }}%</span>
          </div>
          <div class="flex items-center gap-2 text-[11px]">
            <span class="w-10 text-right font-medium text-[#4B5563]">Tidak</span>
            <div class="flex-1 bg-[#F0F4F6] rounded-full h-2.5 overflow-hidden">
              <div class="bg-[#ADC4CE] h-full rounded-full transition-all duration-500" :style="{ width: Math.min(item.likeTidak * 100 * 1.5, 100) + '%' }"></div>
            </div>
            <span class="font-mono tnum w-12 text-[#4B5563] font-medium">{{ (item.likeTidak * 100).toFixed(1) }}%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

