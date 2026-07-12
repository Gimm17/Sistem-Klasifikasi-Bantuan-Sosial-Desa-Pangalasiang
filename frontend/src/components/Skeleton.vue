<!-- Reusable skeleton placeholder — pure CSS animate-pulse, GPU-only.
     Improves perceived load time + reduces CLS (reserves space before data). -->
<script setup>
defineProps({
  // text | circle | rect — base shapes
  variant: { type: String, default: 'text' },
  w: { type: String, default: '100%' },
  h: { type: String, default: '' },
  rounded: { type: String, default: 'md' }, // none|sm|md|lg|xl|full
})
const H = { text: '14px', circle: '40px', rect: '100px' }
const R = { none: '0', sm: '4px', md: '8px', lg: '12px', xl: '16px', full: '9999px' }
</script>

<template>
  <div
    class="animate-pulse bg-[#E5E3D9]"
    :style="{
      width: w,
      height: h || H[variant] || '14px',
      borderRadius: variant === 'circle' ? '50%' : (R[rounded] || '8px'),
    }"
  ></div>
</template>
