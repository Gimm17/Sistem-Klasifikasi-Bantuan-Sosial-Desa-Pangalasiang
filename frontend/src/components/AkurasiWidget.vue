<script setup>
defineProps({
  evaluasi: {
    type: Object,
    default: null
  }
})

const pct = (n) => (Number(n || 0) * 100).toFixed(1) + '%'
</script>

<template>
  <div>
    <div v-if="!evaluasi" class="py-6 text-center text-sm text-[#9CA3AF] bg-white rounded-xl border border-[#D5D3C9] p-4">
      Belum ada riwayat evaluasi model. Jalankan evaluasi di menu Evaluasi Model.
    </div>

    <div v-else class="space-y-4">
      <div class="flex items-center justify-between text-xs text-[#4B5563] border-b border-[#EBE9E0] pb-2">
        <span>Status Model Terakhir</span>
        <span class="font-mono tnum">{{ new Date(evaluasi.created_at).toLocaleDateString('id-ID') }}</span>
      </div>

      <div class="grid grid-cols-2 gap-3 text-center">
        <div class="bg-[#E6F0F4] border border-[#96B6C5]/40 rounded-xl p-3.5 shadow-xs">
          <p class="text-[11px] font-semibold text-[#1A3B47] uppercase tracking-wider">Accuracy</p>
          <p class="text-2xl font-extrabold text-[#1A3B47] mt-1 tnum">{{ pct(evaluasi.accuracy) }}</p>
          <p class="text-[10px] text-[#1A3B47]/80 mt-0.5">Ketepatan Klasifikasi</p>
        </div>
        <div class="bg-[#EEE0C9]/60 border border-[#EEE0C9] rounded-xl p-3.5 shadow-xs">
          <p class="text-[11px] font-semibold text-[#4A3F30] uppercase tracking-wider">F1-Score</p>
          <p class="text-2xl font-extrabold text-[#4A3F30] mt-1 tnum">{{ pct(evaluasi.f1_score) }}</p>
          <p class="text-[10px] text-[#4A3F30]/80 mt-0.5">Keseimbangan Presisi</p>
        </div>
        <div class="bg-white border border-[#D5D3C9] rounded-xl p-3 shadow-xs">
          <p class="text-[11px] text-[#4B5563] font-medium">Precision</p>
          <p class="text-lg font-bold text-[#1F2937] tnum mt-0.5">{{ pct(evaluasi.precision) }}</p>
        </div>
        <div class="bg-white border border-[#D5D3C9] rounded-xl p-3 shadow-xs">
          <p class="text-[11px] text-[#4B5563] font-medium">Recall (Sensitivitas)</p>
          <p class="text-lg font-bold text-[#1F2937] tnum mt-0.5">{{ pct(evaluasi.recall) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

