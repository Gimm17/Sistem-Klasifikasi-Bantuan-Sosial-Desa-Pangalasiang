<script setup>
import { computed } from 'vue'

const props = defineProps({
  breakdown: { type: Object, default: () => ({}) },
  probLayak: { type: [Number, String], default: 0 },
  probTidakLayak: { type: [Number, String], default: 0 },
  prediksi: { type: String, default: '' },
  kategoriInput: { type: Object, default: () => ({}) },
})

const atributLabel = {
  penghasilan: 'Penghasilan',
  pekerjaan: 'Pekerjaan',
  tanggungan: 'Tanggungan',
  kondisi_rumah: 'Kondisi Rumah',
}

const detail = computed(() => props.breakdown?.detail ?? props.breakdown ?? {})
const kelasList = computed(() => Object.keys(detail.value).filter((k) => detail.value[k]?.atribut))

const fmt = (n) => Number(n).toLocaleString('id-ID', { maximumFractionDigits: 6 })
const pct = (n) => (Number(n) * 100).toFixed(2) + '%'

const getIntensityStyle = (val) => {
  const n = Number(val)
  if (isNaN(n) || n <= 0) return {}
  const alpha = Math.min(Math.max(n * 0.45, 0.05), 0.35).toFixed(2)
  return { backgroundColor: `rgba(150, 182, 197, ${alpha})` }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Hasil akhir ternormalisasi -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="rounded-xl border p-4 transition-all" :class="prediksi === 'layak' ? 'border-[#27AE60] bg-[#D4EDDA]/40 shadow-xs' : 'border-[#D5D3C9] bg-white'">
        <p class="text-xs font-medium text-[#4B5563]">Probabilitas Akhir &middot; P(Layak | X)</p>
        <p class="text-3xl font-bold tnum mt-1" :class="prediksi === 'layak' ? 'text-[#155724]' : 'text-[#1F2937]'">{{ pct(probLayak) }}</p>
      </div>
      <div class="rounded-xl border p-4 transition-all" :class="prediksi === 'tidak_layak' ? 'border-[#DC3545] bg-[#F8D7DA]/40 shadow-xs' : 'border-[#D5D3C9] bg-white'">
        <p class="text-xs font-medium text-[#4B5563]">Probabilitas Akhir &middot; P(Tidak Layak | X)</p>
        <p class="text-3xl font-bold tnum mt-1" :class="prediksi === 'tidak_layak' ? 'text-[#721C24]' : 'text-[#1F2937]'">{{ pct(probTidakLayak) }}</p>
      </div>
    </div>

    <!-- Tabel likelihood per atribut per kelas -->
    <div class="overflow-x-auto rounded-xl border border-[#EBE9E0] shadow-xs bg-white">
      <table class="w-full text-sm border-collapse">
        <thead class="bg-[#E6F0F4] text-[#1F2937] font-semibold">
          <tr>
            <th class="text-left px-4 py-3 border-b border-[#EBE9E0]">Atribut</th>
            <th class="text-left px-4 py-3 border-b border-[#EBE9E0]">Kategori Input</th>
            <th v-for="k in kelasList" :key="k" class="text-right px-4 py-3 border-b border-[#EBE9E0] capitalize">
              P(&middot; | {{ k.replace('_', ' ') }})
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#EBE9E0]">
          <tr v-for="attr in ['penghasilan','pekerjaan','tanggungan','kondisi_rumah']" :key="attr" class="hover:bg-[#F8F7F2] transition-colors">
            <td class="px-4 py-3 font-medium text-[#1F2937]">{{ atributLabel[attr] }}</td>
            <td class="px-4 py-3 text-[#4B5563]">{{ (kategoriInput[attr] ?? detail[kelasList[0]]?.atribut?.[attr]?.kategori) || '—' }}</td>
            <td 
              v-for="k in kelasList" 
              :key="k" 
              class="text-right px-4 py-3 font-mono tnum text-[#1F2937] transition-colors"
              :style="getIntensityStyle(detail[k]?.atribut?.[attr]?.likelihood)"
            >
              {{ fmt(detail[k]?.atribut?.[attr]?.likelihood ?? 0) }}
            </td>
          </tr>
          <tr class="bg-[#F8F7F2] font-semibold text-[#1F2937]">
            <td colspan="2" class="px-4 py-3">Prior P(kelas)</td>
            <td v-for="k in kelasList" :key="k" class="text-right px-4 py-3 font-mono tnum">{{ fmt(detail[k]?.prior ?? 0) }}</td>
          </tr>
          <tr class="bg-[#EEE0C9]/40 font-semibold text-[#1F2937] border-t-2 border-[#D5D3C9]">
            <td colspan="2" class="px-4 py-3">Skor (belum ternormalisasi)</td>
            <td v-for="k in kelasList" :key="k" class="text-right px-4 py-3 font-mono tnum text-base">{{ fmt(detail[k]?.skor_sebelum_normalisasi ?? 0) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="p-3 bg-[#E6F0F4]/50 rounded-lg border border-[#96B6C5]/30 flex items-start gap-2.5 text-xs text-[#4B5563]">
      <span class="text-[#96B6C5] font-bold text-sm">💡</span>
      <p>
        <strong class="text-[#1F2937]">Explainable AI:</strong> Skor dihitung dari perkalian probabilitas Prior dengan likelihood setiap atribut (<code class="font-mono bg-white px-1 rounded">Skor = Prior &times; &Pi; P(atribut|kelas)</code>). Probabilitas akhir adalah hasil normalisasi skor agar total peluang kedua kelas bernilai 100%. Warna latar tabel yang lebih pekat menunjukkan probabilitas likelihood yang lebih tinggi.
      </p>
    </div>
  </div>
</template>

