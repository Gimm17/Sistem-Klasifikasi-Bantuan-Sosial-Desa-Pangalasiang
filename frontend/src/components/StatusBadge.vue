<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: { type: String, default: '' }, // 'approval' | 'kelas' | 'validasi' | etc
  value: { type: String, required: true },
})

const variantMap = {
  // Success
  approved: 'bg-[#D4EDDA] text-[#155724] border border-[#155724]/10',
  layak: 'bg-[#D4EDDA] text-[#155724] border border-[#155724]/10',
  divalidasi: 'bg-[#D4EDDA] text-[#155724] border border-[#155724]/10',
  aktif: 'bg-[#D4EDDA] text-[#155724] border border-[#155724]/10',
  success: 'bg-[#D4EDDA] text-[#155724] border border-[#155724]/10',

  // Danger
  rejected: 'bg-[#F8D7DA] text-[#721C24] border border-[#721C24]/10',
  tidak_layak: 'bg-[#F8D7DA] text-[#721C24] border border-[#721C24]/10',
  gagal: 'bg-[#F8D7DA] text-[#721C24] border border-[#721C24]/10',
  danger: 'bg-[#F8D7DA] text-[#721C24] border border-[#721C24]/10',

  // Warning
  pending: 'bg-[#FFF3CD] text-[#856404] border border-[#856404]/10',
  draft: 'bg-[#FFF3CD] text-[#856404] border border-[#856404]/10',
  warning: 'bg-[#FFF3CD] text-[#856404] border border-[#856404]/10',

  // Info
  overridden: 'bg-[#D1ECF1] text-[#0C5460] border border-[#0C5460]/10',
  diproses: 'bg-[#D1ECF1] text-[#0C5460] border border-[#0C5460]/10',
  info: 'bg-[#D1ECF1] text-[#0C5460] border border-[#0C5460]/10',
  admin: 'bg-[#E6F0F4] text-[#1A3B47] border border-[#96B6C5]/30',
  approver: 'bg-[#D1ECF1] text-[#0C5460] border border-[#0C5460]/10',
  superadmin: 'bg-[#EEE0C9] text-[#4A3F30] border border-[#4A3F30]/10',
}

const labelMap = {
  tidak_layak: 'Tidak Layak',
  layak: 'Layak',
  pending: 'Pending',
  approved: 'Disetujui',
  rejected: 'Ditolak',
  overridden: 'Di-override',
  draft: 'Draft',
  divalidasi: 'Divalidasi',
  admin: 'Petugas',
  approver: 'Kades / Sekdes',
  superadmin: 'Super Admin',
}

const cls = computed(() => variantMap[props.value.toLowerCase()] ?? 'bg-[#F0F4F6] text-[#4B5563] border border-[#4B5563]/10')
const label = computed(() => labelMap[props.value.toLowerCase()] ?? props.value)
</script>

<template>
  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium leading-tight whitespace-nowrap shadow-xs', cls]">
    {{ label }}
  </span>
</template>

