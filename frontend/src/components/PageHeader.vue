<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <header class="bg-white border-b border-[#EBE9E0] px-6 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs">
    <div>
      <h2 class="text-2xl font-semibold text-[#1F2937] tracking-tight">{{ title }}</h2>
      <p v-if="subtitle" class="text-sm text-[#4B5563] mt-0.5">{{ subtitle }}</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap"><slot name="actions" /></div>
  </header>
</template>

