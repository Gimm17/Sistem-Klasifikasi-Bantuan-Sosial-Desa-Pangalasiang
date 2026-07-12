<script setup>
defineProps({
  items: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <div class="space-y-4">
    <p class="text-xs text-[#4B5563] leading-relaxed">
      Tingkat pengaruh dihitung berdasarkan pembobotan variasi probabilitas (<em class="font-medium text-[#1F2937]">Likelihood Divergence</em>) antara kelas <span class="font-semibold text-[#27AE60]">Layak</span> dan <span class="font-semibold text-[#DC3545]">Tidak Layak</span> pada model Naive Bayes aktif.
    </p>

    <div v-if="!items.length" class="py-8 text-center text-sm text-[#9CA3AF]">
      Belum ada data model aktif.
    </div>

    <div v-else class="space-y-4">
      <div v-for="(item, index) in items" :key="item.atribut" class="space-y-1.5">
        <div class="flex justify-between text-xs font-semibold text-[#1F2937]">
          <span class="flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-[#E6F0F4] text-[#1A3B47] flex items-center justify-center text-[10px] font-bold shadow-xs">{{ index + 1 }}</span>
            {{ item.label }}
          </span>
          <span class="text-[#96B6C5] font-bold tnum">{{ item.score }}%</span>
        </div>
        <div class="w-full bg-[#F0F4F6] rounded-full h-2.5 overflow-hidden p-0.5 border border-[#E5E3D9]">
          <div 
            class="bg-gradient-to-r from-[#96B6C5] to-[#638B9F] h-1.5 rounded-full transition-all duration-700 shadow-xs" 
            :style="{ width: Math.min(Math.max(item.score, 5), 100) + '%' }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

