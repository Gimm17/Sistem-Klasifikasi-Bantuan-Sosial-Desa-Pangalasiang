<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

// Verifikasi cache user di background: jika sesi server masih valid, data user
// diperbarui dari /api/me. Jika 401 (sesi habis), cache dibersihkan & interceptor
// di api.js redirect ke /login?expired=1. Non-blocking — halaman sudah tampil.
onMounted(() => {
  if (auth.isAuthenticated) {
    auth.fetchUser().catch(() => { /* interceptor handle redirect */ })
  }
})
</script>

<template>
  <RouterView />
</template>
