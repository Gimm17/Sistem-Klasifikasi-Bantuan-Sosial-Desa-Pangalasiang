<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen p-8">
    <header class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">SIKLAS-NB</h1>
        <p class="text-sm text-gray-500">
          Sistem Klasifikasi Kelayakan Penerima Bantuan Sosial — Desa Pangalasiang
        </p>
      </div>
      <div class="text-right">
        <p class="text-sm text-gray-600">
          Login: <b>{{ auth.user?.name }}</b>
          <span class="ml-2 inline-block px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs uppercase">
            {{ auth.role }}
          </span>
        </p>
        <button
          @click="logout"
          class="mt-1 text-sm text-red-600 hover:underline"
        >
          Logout
        </button>
      </div>
    </header>

    <main class="bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-semibold text-gray-700 mb-2">Status Scaffold</h2>
      <p class="text-gray-600">
        Fase 0 — scaffold backend (Laravel) & frontend (Vue) siap. Modul inti
        (autentikasi, data warga, engine Naive Bayes, dst.) akan dibangun
        bertahap sesuai <code class="bg-gray-100 px-1 rounded">implementation_plan.md</code>.
      </p>
    </main>
  </div>
</template>
