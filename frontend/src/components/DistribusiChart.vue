<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
  layak: { type: Number, default: 0 },
  tidakLayak: { type: Number, default: 0 },
})

const chartData = computed(() => ({
  labels: ['Layak Menerima', 'Tidak Layak'],
  datasets: [
    {
      data: [props.layak, props.tidakLayak],
      backgroundColor: ['#27AE60', '#DC3545'],
      hoverBackgroundColor: ['#20904E', '#BD2130'],
      borderWidth: 2,
      borderColor: '#FFFFFF',
    },
  ],
}))

const options = {
  responsive: true,
  plugins: { 
    legend: { 
      position: 'bottom',
      labels: {
        font: { family: 'Inter', size: 12, weight: '500' },
        color: '#1F2937',
        padding: 16,
        usePointStyle: true,
        pointStyle: 'circle'
      }
    } 
  },
  maintainAspectRatio: false,
}
</script>

<template>
  <div class="h-60">
    <Doughnut v-if="layak + tidakLayak > 0" :data="chartData" :options="options" />
    <div v-else class="h-full flex items-center justify-center text-sm text-[#9CA3AF]">
      Belum ada hasil klasifikasi.
    </div>
  </div>
</template>

