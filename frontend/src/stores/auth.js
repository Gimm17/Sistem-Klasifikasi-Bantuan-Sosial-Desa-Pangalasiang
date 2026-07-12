import { defineStore } from 'pinia'
import api from '@/services/api'

/**
 * Auth store (Pinia).
 *
 * Alur login SPA Sanctum (cookie-based):
 *   1. GET  /sanctum/csrf-cookie  -> backend set XSRF-TOKEN + session cookie
 *   2. POST /api/login            -> otentikasi, sesi ter-auth
 *   3. GET  /api/me               -> ambil data user login
 *
 * Role: 'admin' | 'approver' | 'superadmin' (lihat docs/architecture.md §7).
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    initialized: false, // apakah fetchUser awal sudah pernah dijalankan
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    role: (state) => state.user?.role ?? null,
  },

  actions: {
    async getCsrfCookie() {
      await api.get('/sanctum/csrf-cookie')
    },

    async login(credentials) {
      await this.getCsrfCookie()
      const { data } = await api.post('/api/login', credentials)
      this.user = data.data ?? data
      this.initialized = true
      return this.user
    },

    async fetchUser() {
      try {
        const { data } = await api.get('/api/me')
        // Laravel JsonResource membungkus respons dalam { data: {...} }
        // sehingga data.data adalah objek user yang sebenarnya
        this.user = data.data ?? data
        this.initialized = true
        return this.user
      } catch (e) {
        this.user = null
        this.initialized = true
        throw e
      }
    },

    async logout() {
      try {
        await api.post('/api/logout')
      } catch {
        // abaikan error jaringan saat logout
      } finally {
        this.user = null
      }
    },
  },
})
