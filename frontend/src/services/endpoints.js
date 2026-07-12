/**
 * Semua pemanggilan API SIKLAS-NB terpusat di sini.
 * Komponen TIDAK boleh memanggil axios langsung — gunakan helper di modul ini.
 * (aturan docs/system_prompt.md)
 */
import api from './api'

const qs = (params) => {
  const u = new URLSearchParams()
  for (const [k, v] of Object.entries(params || {})) {
    if (v !== '' && v !== null && v !== undefined) u.append(k, v)
  }
  const s = u.toString()
  return s ? `?${s}` : ''
}

export const Auth = {
  csrf: () => api.get('/sanctum/csrf-cookie'),
}

export const Dashboard = {
  get: () => api.get('/api/dashboard').then((r) => r.data),
}

export const Users = {
  list: (params) => api.get(`/api/users${qs(params)}`).then((r) => r.data),
  get: (id) => api.get(`/api/users/${id}`).then((r) => r.data.data),
  create: (data) => api.post('/api/users', data).then((r) => r.data),
  update: (id, data) => api.put(`/api/users/${id}`, data).then((r) => r.data),
  remove: (id) => api.delete(`/api/users/${id}`),
}

export const Warga = {
  list: (params) => api.get(`/api/warga${qs(params)}`).then((r) => r.data),
  get: (id) => api.get(`/api/warga/${id}`).then((r) => r.data.data),
  create: (data) => api.post('/api/warga', data).then((r) => r.data),
  update: (id, data) => api.put(`/api/warga/${id}`, data).then((r) => r.data),
  remove: (id) => api.delete(`/api/warga/${id}`),
  validasi: (id) => api.patch(`/api/warga/${id}/validasi`).then((r) => r.data),
  exportCsv: (params) => api.get('/api/warga/export', { responseType: 'blob', params: { ...params, format: 'csv' } }).then((r) => r.data),
  exportXlsx: (params) => api.get('/api/warga/export', { responseType: 'blob', params: { ...params, format: 'xlsx' } }).then((r) => r.data),
  // format: 'xlsx' (default, rapi + dropdown) atau 'csv'
  downloadTemplate: (format = 'xlsx') => api.get('/api/warga/import/template', { responseType: 'blob', params: { format } }).then((r) => r.data),
  // Impor BANYAK file sekaligus (campur .csv & .xlsx). files = File[] / FileList.
  importFiles: (files) => {
    const fd = new FormData()
    Array.from(files).forEach((f, i) => fd.append(`files[${i}]`, f))
    return api.post('/api/warga/import', fd, { headers: { 'Content-Type': 'multipart/form-data' } }).then((r) => r.data)
  },
}

export const Atribut = {
  list: () => api.get('/api/atribut-klasifikasi').then((r) => r.data.data),
}

export const Kategori = {
  list: (atributId) => api.get(`/api/kategori-atribut${qs({ atribut_klasifikasi_id: atributId })}`).then((r) => r.data.data),
  create: (data) => api.post('/api/kategori-atribut', data).then((r) => r.data),
  update: (id, data) => api.put(`/api/kategori-atribut/${id}`, data).then((r) => r.data),
  remove: (id) => api.delete(`/api/kategori-atribut/${id}`),
}

export const DataTraining = {
  list: (params) => api.get(`/api/data-training${qs(params)}`).then((r) => r.data),
  create: (data) => api.post('/api/data-training', data).then((r) => r.data),
  update: (id, data) => api.put(`/api/data-training/${id}`, data).then((r) => r.data),
  remove: (id) => api.delete(`/api/data-training/${id}`),
  exportCsv: (params) => api.get(`/api/data-training/export${qs({ ...params, format: 'csv' })}`, { responseType: 'blob' }).then((r) => r.data),
  exportXlsx: (params) => api.get(`/api/data-training/export${qs({ ...params, format: 'xlsx' })}`, { responseType: 'blob' }).then((r) => r.data),
  downloadTemplate: (format = 'xlsx') => api.get('/api/data-training/import/template', { responseType: 'blob', params: { format } }).then((r) => r.data),
  importFiles: (files) => {
    const fd = new FormData()
    Array.from(files).forEach((f, i) => fd.append(`files[${i}]`, f))
    return api.post('/api/data-training/import', fd, { headers: { 'Content-Type': 'multipart/form-data' } }).then((r) => r.data)
  },
}

export const Model = {
  train: () => api.post('/api/model/train').then((r) => r.data),
  versions: () => api.get('/api/model/versions').then((r) => r.data.data),
}

export const Klasifikasi = {
  predict: (wargaId) => api.post(`/api/klasifikasi/${wargaId}`).then((r) => r.data),
  batch: (params) => api.post(`/api/klasifikasi/batch${qs(params)}`).then((r) => r.data),
  list: (params) => api.get(`/api/klasifikasi${qs(params)}`).then((r) => r.data),
  get: (id) => api.get(`/api/klasifikasi/${id}`).then((r) => r.data.data),
}

export const Approval = {
  queue: (params) => api.get(`/api/approval/queue${qs(params)}`).then((r) => r.data),
  approve: (id, catatan) => api.patch(`/api/approval/${id}/approve`, { catatan_approval: catatan }).then((r) => r.data),
  reject: (id, catatan) => api.patch(`/api/approval/${id}/reject`, { catatan_approval: catatan }).then((r) => r.data),
  override: (id, catatan, kelas) => api.patch(`/api/approval/${id}/override`, { catatan_approval: catatan, prediksi_kelas_override: kelas }).then((r) => r.data),
}

export const Evaluasi = {
  run: (opts = {}) => api.post('/api/evaluasi/run', opts).then((r) => r.data),
  list: () => api.get('/api/evaluasi').then((r) => r.data),
}

export const Laporan = {
  csvUrl: (params) => `${api.defaults.baseURL}/api/laporan/klasifikasi.csv${qs(params)}`,
  pdfUrl: (params) => `${api.defaults.baseURL}/api/laporan/klasifikasi.pdf${qs(params)}`,
  /** Buka laporan klasifikasi di halaman preview PDF. */
  previewPdf: (params) => {
    const url = `${api.defaults.baseURL}/api/laporan/klasifikasi.pdf${qs(params)}`
    pdfPreview(url, 'Laporan Hasil Klasifikasi')
  },
}

export const Rekapitulasi = {
  perDusun: () => api.get('/api/rekapitulasi/dusun').then((r) => r.data),
  pdfRekapDusun: () => `${api.defaults.baseURL}/api/rekapitulasi/dusun.pdf`,
  pdfRincianWarga: (dusun) => `${api.defaults.baseURL}/api/laporan/rincian-warga.pdf${qs({ dusun })}`,
  pdfRincianKk: (wargaId) => `${api.defaults.baseURL}/api/laporan/rincian-kk/${wargaId}.pdf`,
  /** Buka di halaman preview. */
  previewRekapDusun: () => {
    const url = `${api.defaults.baseURL}/api/rekapitulasi/dusun.pdf`
    pdfPreview(url, 'Rekapitulasi Per Dusun')
  },
  previewRincianWarga: (dusun) => {
    const url = `${api.defaults.baseURL}/api/laporan/rincian-warga.pdf${qs({ dusun })}`
    pdfPreview(url, 'Rincian Warga Per Dusun')
  },
  previewRincianKk: (wargaId, nama) => {
    const url = `${api.defaults.baseURL}/api/laporan/rincian-kk/${wargaId}.pdf`
    pdfPreview(url, `Rincian KK - ${nama}`)
  },
}

export const ActivityLogs = {
  list: () => api.get('/api/activity-logs').then((r) => r.data),
}

/** Memicu unduhan blob sebagai file (utk export CSV). */
export function downloadBlob(blob, filename) {
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  a.remove()
  window.URL.revokeObjectURL(url)
}

/**
 * Helper: buka PDF di halaman pratinjau (stream) dengan toolbar custom.
 * Di halaman preview, user bisa download (?download=1) atau cetak.
 * Berlaku untuk SEMUA laporan PDF.
 */
export function pdfPreview(url, title = 'Laporan PDF') {
  const base = window.location.origin
  const previewUrl = `${base}/laporan/pdf-preview?url=${encodeURIComponent(url)}&title=${encodeURIComponent(title)}`
  window.open(previewUrl, '_blank', 'noopener,noreferrer')
}
