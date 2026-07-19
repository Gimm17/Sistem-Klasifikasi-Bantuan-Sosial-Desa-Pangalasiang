<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter, useRoute, RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  LayoutDashboard,
  Users,
  BookOpen,
  Settings2,
  Shield,
  History,
  BrainCircuit,
  ClipboardCheck,
  BarChart3,
  LogOut,
  Building2,
  Map,
  Menu,
  X,
  HelpCircle
} from '@lucide/vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

// Mobile sidebar drawer state. Open by default on desktop via lg:translate-x-0.
const sidebarOpen = ref(false)
// Auto-close drawer when navigating (mobile UX).
watch(() => route.fullPath, () => { sidebarOpen.value = false })

// Item navigasi + peran yang boleh melihat.
const navItems = [
  { to: { name: 'dashboard' }, label: 'Dashboard', icon: LayoutDashboard, roles: ['admin', 'approver', 'superadmin'], exact: true },
  { to: { name: 'warga.list' }, label: 'Data Warga', icon: Users, roles: ['admin', 'approver'] },
  { to: { name: 'training.list' }, label: 'Data Training', icon: BookOpen, roles: ['admin', 'superadmin'] },
  { to: { name: 'kategori' }, label: 'Kategori Atribut', icon: Settings2, roles: ['admin', 'superadmin'] },
  { to: { name: 'users' }, label: 'Kelola User', icon: Shield, roles: ['superadmin'] },
  { to: { name: 'audit' }, label: 'Jejak Audit', icon: History, roles: ['superadmin'] },
  { to: { name: 'rekapitulasi' }, label: 'Rekap. Per Dusun', icon: Map, roles: ['admin', 'approver', 'superadmin'] },
  { to: { name: 'klasifikasi.index' }, label: 'Klasifikasi', icon: BrainCircuit, roles: ['admin', 'approver'] },
  { to: { name: 'approval' }, label: 'Antrean Approval', icon: ClipboardCheck, roles: ['approver'] },
  { to: { name: 'evaluasi' }, label: 'Evaluasi Model', icon: BarChart3, roles: ['admin', 'approver', 'superadmin'] },
  { to: { name: 'dokumentasi' }, label: 'Dokumentasi', icon: HelpCircle, roles: ['admin', 'approver', 'superadmin'] },
]

const visibleNav = computed(() => navItems.filter((i) => i.roles.includes(auth.role)))

function isNavActive(item) {
  if (item.exact) {
    return route.name === item.to.name
  }
  const prefix = item.to.name.split('.')[0]
  return route.name?.startsWith(prefix)
}

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}

const roleLabel = { admin: 'Petugas Pendataan', approver: 'Kades / Sekdes', superadmin: 'Super Admin' }
</script>

<template>
  <div class="min-h-screen flex bg-[#F1F0E8] text-[#1F2937]">
    <!-- Mobile topbar (hidden on desktop) -->
    <header class="lg:hidden fixed top-0 inset-x-0 z-30 h-14 bg-[#1F2937] text-white flex items-center justify-between px-4 shadow-md">
      <button
        @click="sidebarOpen = true"
        title="Buka menu"
        class="p-2 -ml-2 text-gray-200 hover:text-white hover:bg-white/10 rounded-md transition-colors"
      >
        <Menu class="w-6 h-6" />
      </button>
      <div class="flex items-center gap-2">
        <Building2 class="w-5 h-5 text-[#96B6C5]" />
        <span class="font-bold text-sm tracking-tight">SIKLAS-NB</span>
      </div>
      <div class="w-8"></div>
    </header>

    <!-- Overlay backdrop (mobile only, when drawer open) -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-black/50 z-30 lg:hidden"
    ></div>

    <!-- Sidebar -->
    <aside
      :class="[
        'w-[260px] bg-[#1F2937] text-gray-100 flex flex-col flex-shrink-0 shadow-lg h-screen top-0 self-start z-40',
        'fixed lg:sticky transition-transform duration-200 ease-out',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Brand Area -->
      <div class="px-5 py-4 border-b border-[#374151] flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-[#96B6C5] text-[#1A3B47] flex items-center justify-center font-bold text-xl shadow-inner flex-shrink-0">
          <Building2 class="w-6 h-6" />
        </div>
        <div class="overflow-hidden flex-1">
          <h1 class="font-bold text-lg leading-tight tracking-tight text-white">SIKLAS-NB</h1>
          <p class="text-[11px] text-[#D1D5DB]/70 truncate">Desa Pangalasiang</p>
        </div>
        <!-- Close button (mobile only) -->
        <button
          @click="sidebarOpen = false"
          title="Tutup menu"
          class="lg:hidden p-1.5 text-gray-400 hover:text-white hover:bg-white/10 rounded-md transition-colors"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
        <RouterLink
          v-for="item in visibleNav"
          :key="item.label"
          :to="item.to"
          :class="[
            'flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-md transition-all duration-150',
            isNavActive(item)
              ? '!bg-[#96B6C5] !text-white font-semibold shadow-sm border-l-[3px] !border-[#E6F0F4] !rounded-l-none -ml-3 pl-[19px]'
              : 'text-[#D1D5DB] hover:bg-white/5 hover:text-white'
          ]"
        >
          <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
          <span class="truncate">{{ item.label }}</span>
        </RouterLink>
      </nav>

      <!-- User Profile & Logout -->
      <div class="p-4 border-t border-[#374151] bg-[#1F2937]/50">
        <div class="flex items-center justify-between gap-3">
          <div class="overflow-hidden">
            <p class="text-sm font-medium text-white truncate">{{ auth.user?.name || 'Pengguna' }}</p>
            <p class="text-xs text-[#96B6C5] truncate font-medium">{{ roleLabel[auth.role] || auth.role }}</p>
          </div>
          <button
            @click="logout"
            title="Keluar"
            class="p-2 text-gray-400 hover:text-[#DC3545] hover:bg-white/5 rounded-md transition-colors"
          >
            <LogOut class="w-5 h-5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Konten -->
    <main class="flex-1 overflow-x-auto flex flex-col min-w-0 pt-14 lg:pt-0">
      <RouterView />
    </main>
  </div>
</template>

