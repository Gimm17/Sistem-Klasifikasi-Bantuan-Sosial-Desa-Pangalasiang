<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Building2, Mail, Lock, AlertCircle, ArrowRight, ShieldCheck } from '@lucide/vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '' })
const error = ref('')
const expiredMsg = ref('')
const loading = ref(false)

onMounted(() => {
  if (route.query.expired === '1') {
    expiredMsg.value = 'Sesi Anda telah berakhir. Silakan masuk kembali.'
  }
})

async function submit() {
  error.value = ''
  expiredMsg.value = ''
  loading.value = true
  try {
    await auth.login({ ...form })
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message || 'Login gagal. Periksa email & password.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-[#F1F0E8] relative overflow-hidden">
    <!-- Decorative background elements -->
    <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-[#E6F0F4] blur-3xl opacity-60 pointer-events-none"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-[#EEE0C9] blur-3xl opacity-60 pointer-events-none"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
      <div class="flex justify-center">
        <div class="w-16 h-16 rounded-2xl bg-[#1F2937] text-[#96B6C5] flex items-center justify-center shadow-md">
          <Building2 class="w-9 h-9" />
        </div>
      </div>
      <h1 class="mt-4 text-center text-2xl font-bold tracking-tight text-[#1F2937]">
        SIKLAS-NB
      </h1>
      <p class="mt-1 text-center text-sm text-[#4B5563] font-medium">
        Sistem Klasifikasi Bantuan Sosial &middot; Desa Pangalasiang
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-[420px] relative z-10 px-4 sm:px-0">
      <div class="bg-white py-8 px-6 sm:px-10 rounded-2xl shadow-lg border border-[#E5E3D9]">
        <form @submit.prevent="submit" class="space-y-5">
          <div v-if="expiredMsg" class="flex items-start gap-3 text-sm text-[#856404] bg-[#FFF3CD] border border-[#856404]/20 p-3.5 rounded-xl font-medium">
            <AlertCircle class="w-5 h-5 text-[#856404] flex-shrink-0 mt-0.5" />
            <span>{{ expiredMsg }}</span>
          </div>

          <div v-if="error" class="flex items-start gap-3 text-sm text-[#721C24] bg-[#F8D7DA] border border-[#721C24]/20 p-3.5 rounded-xl font-medium">
            <AlertCircle class="w-5 h-5 text-[#721C24] flex-shrink-0 mt-0.5" />
            <span>{{ error }}</span>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[#4B5563] mb-1.5">
              Alamat Email
            </label>
            <div class="relative rounded-xl shadow-xs">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#9CA3AF]">
                <Mail class="w-4 h-4" />
              </div>
              <input
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                placeholder="nama@pangalasiang.desa.id"
                class="block w-full pl-10 pr-4 py-2.5 bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl text-sm text-[#1F2937] placeholder-[#9CA3AF] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[#4B5563] mb-1.5">
              Kata Sandi
            </label>
            <div class="relative rounded-xl shadow-xs">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#9CA3AF]">
                <Lock class="w-4 h-4" />
              </div>
              <input
                v-model="form.password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="block w-full pl-10 pr-4 py-2.5 bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl text-sm text-[#1F2937] placeholder-[#9CA3AF] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all"
              />
            </div>
          </div>

          <div class="pt-2">
            <button
              :disabled="loading"
              type="submit"
              class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-[#1F2937] text-white rounded-xl hover:bg-[#374151] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1F2937] text-sm font-semibold shadow-md transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed group"
            >
              <span>{{ loading ? 'Memverifikasi...' : 'Masuk ke Sistem' }}</span>
              <ArrowRight v-if="!loading" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
            </button>
          </div>
        </form>

        <div class="mt-6 pt-5 border-t border-[#EBE9E0] flex items-center justify-center gap-2 text-xs text-[#9CA3AF]">
          <ShieldCheck class="w-4 h-4 text-[#27AE60]" />
          <span>Akses Diamankan &middot; Pemerintah Desa Pangalasiang</span>
        </div>
      </div>
    </div>
  </div>
</template>

