<script setup>
import { ref, onMounted, reactive } from 'vue'
import { Users } from '@/services/endpoints'
import { useAuthStore } from '@/stores/auth'
import PageHeader from '@/components/PageHeader.vue'
import Skeleton from '@/components/Skeleton.vue'
import { UserPlus, Edit3, Trash2, Shield, Key, Mail, User, ChevronLeft, ChevronRight, AlertCircle } from '@lucide/vue'

const auth = useAuthStore()
const items = ref([])
const meta = ref(null)
const loading = ref(false)
const page = ref(1)

const showModal = ref(false)
const isEditing = ref(false)
const currentId = ref(null)
const form = reactive({
  name: '',
  email: '',
  role: 'admin',
  password: ''
})
const formError = ref('')
const saving = ref(false)

async function load() {
  loading.value = true
  try {
    const res = await Users.list({ page: page.value })
    items.value = res.data
    meta.value = res.meta
  } finally {
    loading.value = false
  }
}
onMounted(load)

function openCreate() {
  isEditing.value = false
  currentId.value = null
  form.name = ''
  form.email = ''
  form.role = 'admin'
  form.password = ''
  formError.value = ''
  showModal.value = true
}

function openEdit(u) {
  isEditing.value = true
  currentId.value = u.id
  form.name = u.name
  form.email = u.email
  form.role = u.role
  form.password = '' // kosongkan, isi jika ingin reset
  formError.value = ''
  showModal.value = true
}

async function save() {
  if (!form.name || !form.email) {
    formError.value = 'Nama dan Email wajib diisi.'
    return
  }
  if (!isEditing.value && !form.password) {
    formError.value = 'Password wajib diisi untuk user baru.'
    return
  }
  if (form.password && form.password.length < 8) {
    formError.value = 'Password minimal 8 karakter.'
    return
  }

  saving.value = true
  formError.value = ''
  try {
    const payload = {
      name: form.name,
      email: form.email,
      role: form.role
    }
    if (form.password) {
      payload.password = form.password
    }

    if (isEditing.value) {
      await Users.update(currentId.value, payload)
    } else {
      await Users.create(payload)
    }
    showModal.value = false
    await load()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal menyimpan user.'
  } finally {
    saving.value = false
  }
}

async function destroy(u) {
  if (u.id === auth.user?.id) {
    alert('Anda tidak dapat menghapus akun Anda sendiri.')
    return
  }
  if (!confirm(`Hapus user "${u.name}" (${u.email})?`)) return
  await Users.remove(u.id)
  await load()
}

const roleLabel = {
  admin: 'Petugas Pendataan',
  approver: 'Kades / Sekdes',
  superadmin: 'Super Admin (IT)'
}
const roleColor = {
  admin: 'bg-[#E6F0F4] text-[#1A3B47] border-[#96B6C5]/30',
  approver: 'bg-[#FFF3CD] text-[#856404] border-[#856404]/30',
  superadmin: 'bg-[#D1ECF1] text-[#0C5460] border-[#0C5460]/30'
}
</script>

<template>
  <div>
    <PageHeader title="Kelola Pengguna & Hak Akses" subtitle="Manajemen akun perangkat desa, operator pendataan, dan reset password sistem">
      <template #actions>
        <button @click="openCreate" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] shadow-xs transition-colors">
          <UserPlus class="w-4 h-4 text-[#96B6C5]" />
          <span>Tambah Pengguna Baru</span>
        </button>
      </template>
    </PageHeader>

    <div class="p-6 space-y-6">
      <!-- Tabel -->
      <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead class="bg-[#E6F0F4] text-[#1F2937] text-xs font-semibold uppercase tracking-wider">
              <tr>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Nama Pengguna</th>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Alamat Email</th>
                <th class="text-center px-4 py-3.5 border-b border-[#EBE9E0]">Peran (Role Akses)</th>
                <th class="text-center px-4 py-3.5 border-b border-[#EBE9E0]">Tanggal Terdaftar</th>
                <th class="text-right px-4 py-3.5 border-b border-[#EBE9E0]">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EBE9E0]">
              <tr v-for="u in items" :key="u.id" class="hover:bg-[#F8F7F2] transition-colors">
                <td class="px-4 py-3.5 font-bold text-[#1F2937] flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-[#E6F0F4] text-[#1A3B47] flex items-center justify-center font-bold text-xs uppercase">
                    {{ u.name ? u.name.substring(0, 2) : 'US' }}
                  </div>
                  <div>
                    <span>{{ u.name }}</span>
                    <span v-if="u.id === auth.user?.id" class="ml-1.5 text-[10px] bg-[#D4EDDA] text-[#155724] px-1.5 py-0.5 rounded-md font-bold uppercase tracking-wider border border-[#27AE60]/30">Anda</span>
                  </div>
                </td>
                <td class="px-4 py-3.5 text-[#4B5563] font-mono text-xs">{{ u.email }}</td>
                <td class="px-4 py-3.5 text-center">
                  <span :class="roleColor[u.role] || 'bg-gray-100'" class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider border inline-block">
                    {{ roleLabel[u.role] || u.role }}
                  </span>
                </td>
                <td class="px-4 py-3.5 text-center text-xs text-[#4B5563] font-mono tnum">{{ new Date(u.created_at).toLocaleDateString('id-ID') }}</td>
                <td class="px-4 py-3.5 text-right space-x-1 whitespace-nowrap">
                  <button @click="openEdit(u)" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-[#D5D3C9] text-[#1F2937] rounded-lg text-xs font-semibold hover:bg-[#F8F7F2] transition-colors shadow-xs">
                    <Edit3 class="w-3 h-3 text-[#96B6C5]" />
                    <span>Edit / Reset Pass</span>
                  </button>
                  <button v-if="u.id !== auth.user?.id" @click="destroy(u)" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-[#F8D7DA] text-[#721C24] rounded-lg text-xs font-semibold hover:bg-[#f5c6cb] transition-colors shadow-xs">
                    <Trash2 class="w-3 h-3" />
                    <span>Hapus</span>
                  </button>
                </td>
              </tr>
              <template v-if="loading && !items.length">
                <tr v-for="i in 5" :key="'sk'+i" class="border-b border-[#EBE9E0]">
                  <td v-for="j in 5" :key="j" class="px-4 py-3.5"><Skeleton w="70%" h="12px" /></td>
                </tr>
              </template>
              <tr v-else-if="!items.length">
                <td colspan="5" class="px-4 py-10 text-center text-[#9CA3AF] text-sm font-medium">Tidak ada data pengguna ditemukan.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Paginasi -->
      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between text-xs font-semibold text-[#4B5563] bg-white rounded-xl border border-[#D5D3C9] p-4 shadow-xs">
        <p>Halaman <span class="text-[#1F2937] tnum">{{ meta.current_page }}</span> dari <span class="text-[#1F2937] tnum">{{ meta.last_page }}</span> <span class="text-[#9CA3AF]">({{ meta.total }} total pengguna)</span></p>
        <div class="flex items-center gap-2">
          <button :disabled="meta.current_page === 1" @click="page--; load()" class="inline-flex items-center gap-1 px-3 py-1.5 border border-[#D5D3C9] rounded-lg hover:bg-[#F8F7F2] disabled:opacity-40 disabled:pointer-events-none transition-colors">
            <ChevronLeft class="w-3.5 h-3.5" />
            <span>Sebelumnya</span>
          </button>
          <button :disabled="meta.current_page === meta.last_page" @click="page++; load()" class="inline-flex items-center gap-1 px-3 py-1.5 border border-[#D5D3C9] rounded-lg hover:bg-[#F8F7F2] disabled:opacity-40 disabled:pointer-events-none transition-colors">
            <span>Selanjutnya</span>
            <ChevronRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Modal Form User / Reset Password -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click.self="showModal = false">
        <div class="bg-white rounded-2xl shadow-xl border border-[#D5D3C9] w-full max-w-md p-6 space-y-4">
          <div class="flex justify-between items-center border-b border-[#EBE9E0] pb-3">
            <h3 class="font-bold text-[#1F2937] text-lg flex items-center gap-2">
              <UserPlus v-if="!isEditing" class="w-5 h-5 text-[#96B6C5]" />
              <Edit3 v-else class="w-5 h-5 text-[#96B6C5]" />
              <span>{{ isEditing ? 'Edit User / Reset Password' : 'Tambah Pengguna Baru' }}</span>
            </h3>
            <button @click="showModal = false" class="text-[#9CA3AF] hover:text-[#1F2937] transition-colors">✕</button>
          </div>

          <div v-if="formError" class="p-3 bg-[#F8D7DA] text-[#721C24] rounded-xl border border-[#721C24]/20 text-xs font-semibold flex items-center gap-2">
            <AlertCircle class="w-4 h-4 flex-shrink-0" />
            <span>{{ formError }}</span>
          </div>

          <form @submit.prevent="save" class="space-y-3.5">
            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-[#4B5563] mb-1">Nama Lengkap <span class="text-[#DC3545]">*</span></label>
              <input v-model="form.name" required type="text" placeholder="Masukkan nama lengkap operator..." class="w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2 text-sm text-[#1F2937] font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]" />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-[#4B5563] mb-1">Alamat Email <span class="text-[#DC3545]">*</span></label>
              <input v-model="form.email" required type="email" placeholder="email@desa-pangalasiang.id" class="w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2 text-sm text-[#1F2937] font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]" />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-[#4B5563] mb-1">Peran Akses (Role) <span class="text-[#DC3545]">*</span></label>
              <select v-model="form.role" class="w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2 text-sm text-[#1F2937] font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]">
                <option value="admin">Petugas Pendataan (Operator Warga)</option>
                <option value="approver">Kades / Sekdes (Approver Kelayakan)</option>
                <option value="superadmin">Super Admin (IT & Pengelola Sistem)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-[#4B5563] mb-1">
                {{ isEditing ? 'Password Baru (Kosongkan jika tidak ubah)' : 'Password Akun Baru' }} <span v-if="!isEditing" class="text-[#DC3545]">*</span>
              </label>
              <input v-model="form.password" :required="!isEditing" type="password" placeholder="Minimal 8 karakter..." class="w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2 text-sm text-[#1F2937] font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]" />
              <p v-if="isEditing" class="text-[11px] text-[#9CA3AF] mt-1">Isi hanya jika ingin melakukan reset kata sandi pengguna ini.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#EBE9E0]">
              <button type="button" @click="showModal = false" class="px-4 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#4B5563] hover:bg-[#F8F7F2] transition-colors">Batal</button>
              <button :disabled="saving" type="submit" class="px-5 py-2 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] disabled:opacity-50 transition-colors shadow-xs">
                {{ saving ? 'Menyimpan Pengguna…' : 'Simpan Pengguna' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

