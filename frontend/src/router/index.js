import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

/**
 * meta.roles: daftar peran yang boleh mengakses route.
 *   admin = petugas pendataan, approver = kades/sekdes, superadmin = maintenance.
 */
const routes = [
  { path: '/login', name: 'login', component: () => import('@/pages/auth/Login.vue'), meta: { guest: true } },

  {
    path: '/',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { auth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('@/pages/dashboard/Dashboard.vue') },
      {
        path: 'warga', name: 'warga.list', component: () => import('@/pages/warga/WargaList.vue'),
        meta: { roles: ['admin', 'approver'] },
      },
      {
        path: 'warga/new', name: 'warga.new', component: () => import('@/pages/warga/WargaForm.vue'),
        meta: { roles: ['admin'] },
      },
      {
        path: 'warga/:id/edit', name: 'warga.edit', component: () => import('@/pages/warga/WargaForm.vue'),
        meta: { roles: ['admin'] },
      },
      {
        path: 'warga/:id', name: 'warga.show', component: () => import('@/pages/warga/WargaDetail.vue'),
        meta: { roles: ['admin', 'approver'] },
      },
      {
        path: 'data-training', name: 'training.list', component: () => import('@/pages/training/DataTrainingList.vue'),
        meta: { roles: ['admin', 'superadmin'] },
      },
      {
        path: 'data-training/new', name: 'training.new', component: () => import('@/pages/training/DataTrainingForm.vue'),
        meta: { roles: ['admin', 'superadmin'] },
      },
      {
        path: 'data-training/:id/edit', name: 'training.edit', component: () => import('@/pages/training/DataTrainingForm.vue'),
        meta: { roles: ['admin', 'superadmin'] },
      },
      {
        path: 'kategori', name: 'kategori', component: () => import('@/pages/settings/KategoriAtribut.vue'),
        meta: { roles: ['admin', 'superadmin'] },
      },
      {
        path: 'audit', name: 'audit', component: () => import('@/pages/settings/ActivityLogPage.vue'),
        meta: { roles: ['superadmin'] },
      },
      {
        path: 'users', name: 'users', component: () => import('@/pages/settings/UserManagement.vue'),
        meta: { roles: ['superadmin'] },
      },
      {
        path: 'klasifikasi', name: 'klasifikasi.index', component: () => import('@/pages/klasifikasi/KlasifikasiPage.vue'),
        meta: { roles: ['admin', 'approver'] },
      },
      {
        path: 'klasifikasi/:id', name: 'klasifikasi.show', component: () => import('@/pages/klasifikasi/HasilDetail.vue'),
        meta: { roles: ['admin', 'approver'] },
      },
      {
        path: 'rekapitulasi', name: 'rekapitulasi', component: () => import('@/pages/rekapitulasi/RekapitulasiPerDusun.vue'),
        meta: { roles: ['admin', 'approver', 'superadmin'] },
      },
      {
        path: 'approval', name: 'approval', component: () => import('@/pages/approval/ApprovalQueue.vue'),
        meta: { roles: ['approver'] },
      },
      {
        path: 'evaluasi', name: 'evaluasi', component: () => import('@/pages/evaluasi/ModelEvaluasi.vue'),
        meta: { roles: ['admin', 'approver', 'superadmin'] },
      },
    ],
  },
  {
    path: '/laporan/pdf-preview',
    name: 'pdf-preview',
    component: () => import('@/pages/laporan/PdfPreview.vue'),
    meta: { auth: true },
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // saat refresh, coba ambil user berdasarkan cookie sesi yang aktif
  if (to.meta.auth !== undefined && !auth.isAuthenticated) {
    try { await auth.fetchUser() } catch { /* belum login */ }
  }

  // butuh auth tapi belum login
  if (to.meta.auth !== undefined && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  // halaman tamu (login) tapi sudah login
  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  // pembatasan peran
  if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
