import axios from 'axios'

/**
 * Instance axios terpusat.
 *
 * - baseURL membaca VITE_API_BASE_URL (lihat frontend/.env), default http://localhost:8000
 * - withCredentials & withXSRFToken = true agar cookie sesi Sanctum + token XSRF
 *   dikirim bersama setiap request cross-origin (frontend :5173 -> backend :8000).
 *
 * Aturan (lihat docs/system_prompt.md): JANGAN panggil axios langsung dari komponen —
 * semua pemanggilan API lewat instance ini (atau modul service yang membungkusnya).
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && (error.response.status === 401 || error.response.status === 419)) {
      const url = error.config?.url || ''
      if (!url.includes('/login') && !url.includes('/me') && !url.includes('/sanctum/csrf-cookie')) {
        if (window.location.pathname !== '/login') {
          window.location.href = '/login?expired=1'
        }
      }
    }
    return Promise.reject(error)
  }
)

export default api
