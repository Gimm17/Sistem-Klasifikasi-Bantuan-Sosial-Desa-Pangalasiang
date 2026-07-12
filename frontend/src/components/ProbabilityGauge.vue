<script setup>
defineProps({
  probLayak: { type: [Number, String], default: 0 },
  probTidakLayak: { type: [Number, String], default: 0 },
  prediksi: { type: String, default: '' }
})

const pct = (n) => (Number(n || 0) * 100).toFixed(1)
</script>

<template>
  <div class="bg-white rounded-xl p-5 border border-[#D5D3C9] shadow-xs space-y-4">
    <div class="flex justify-between items-center text-sm">
      <span class="font-semibold text-[#1F2937] flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-[#27AE60] shadow-xs"></span>
        Layak Menerima: <span class="tnum font-bold text-[#27AE60] text-base">{{ pct(probLayak) }}%</span>
      </span>
      <span class="font-semibold text-[#4B5563] flex items-center gap-2">
        Tidak Layak: <span class="tnum font-bold text-[#DC3545] text-base">{{ pct(probTidakLayak) }}%</span>
        <span class="w-3 h-3 rounded-full bg-[#DC3545] shadow-xs"></span>
      </span>
    </div>

    <!-- Dual progress gauge -->
    <div class="w-full bg-[#F0F4F6] rounded-full h-6 flex overflow-hidden shadow-inner p-1 gap-1 border border-[#E5E3D9]">
      <div 
        class="bg-gradient-to-r from-[#27AE60] to-[#20904E] h-full rounded-l-full transition-all duration-700 flex items-center justify-center text-[11px] font-bold text-white shadow-xs"
        :style="{ width: Math.max(Number(probLayak) * 100, 5) + '%' }"
      >
        <span v-if="Number(probLayak) > 0.15" class="tnum">{{ pct(probLayak) }}%</span>
      </div>
      <div 
        class="bg-gradient-to-r from-[#E55360] to-[#DC3545] h-full rounded-r-full transition-all duration-700 flex items-center justify-center text-[11px] font-bold text-white shadow-xs"
        :style="{ width: Math.max(Number(probTidakLayak) * 100, 5) + '%' }"
      >
        <span v-if="Number(probTidakLayak) > 0.15" class="tnum">{{ pct(probTidakLayak) }}%</span>
      </div>
    </div>

    <div class="flex justify-between items-center text-xs text-[#4B5563] pt-2 border-t border-[#EBE9E0]">
      <span>Keputusan Sistem: <b class="uppercase font-bold tracking-wide px-2 py-0.5 rounded-md" :class="prediksi === 'layak' ? 'bg-[#D4EDDA] text-[#155724]' : 'bg-[#F8D7DA] text-[#721C24]'">{{ prediksi?.replace('_', ' ') || '-' }}</b></span>
      <span class="text-[#9CA3AF]">Normalisasi 100%</span>
    </div>
  </div>
</template>

