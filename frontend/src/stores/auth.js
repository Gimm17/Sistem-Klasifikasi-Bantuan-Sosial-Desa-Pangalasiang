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
 *
 * Optimistik cache (performa): user disalin ke localStorage saat login/fetchUser
 * agar pada cold-load halaman ter-auth, route guard tampilkan UI segera tanpa
 * menunggu /api/me. fetchUser() tetap jalan di background untuk verifikasi;
 * jika 401, cache dibersihkan & user di-logout.
 */
const USER_CACHE_KEY = 'siklas_user_cache'

function loadCachedUser() {
  try {
    const raw = localStorage.getItem(USER_CACHE_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

function saveCachedUser(user) {
  try {
    if (user) localStorage.setItem(USER_CACHE_KEY, JSON.stringify(user))
    else localStorage.removeItem(USER_CACHE_KEY)
  } catch {
    // localStorage mungkin diblokir (private mode) — abaikan, cache best-effort
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: loadCachedUser(),
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
      saveCachedUser(this.user)
      return this.user
    },

    async fetchUser() {
      try {
        const { data } = await api.get('/api/me')
        // Laravel JsonResource membungkus respons dalam { data: {...} }
        // sehingga data.data adalah objek user yang sebenarnya
        this.user = data.data ?? data
        this.initialized = true
        saveCachedUser(this.user)
        return this.user
      } catch (e) {
        this.user = null
        this.initialized = true
        saveCachedUser(null)
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
        saveCachedUser(null)
      }
    },
  },
})
