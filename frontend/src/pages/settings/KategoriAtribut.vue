<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Atribut, Kategori } from '@/services/endpoints'
import PageHeader from '@/components/PageHeader.vue'
import { Sliders, Edit3, Trash2, Plus, AlertCircle, Layers } from '@lucide/vue'

const atributList = ref([])
const aktifAtribut = ref(null) // atribut_klasifikasi_id terpilih
const items = ref([])
const loading = ref(false)

// form tambah/edit
const editing = ref(null)
const form = reactive({ label: '', batas_bawah: '', batas_atas: '', urutan: 0 })
const errMsg = ref('')
const saving = ref(false)

async function loadAtribut() {
  atributList.value = await Atribut.list()
  if (!aktifAtribut.value && atributList.value.length) {
    aktifAtribut.value = atributList.value[0].id
    await loadKategori()
  }
}
async function loadKategori() {
  if (!aktifAtribut.value) return
  loading.value = true
  items.value = await Kategori.list(aktifAtribut.value)
  loading.value = false
}
onMounted(loadAtribut)

const isNumerik = () => atributList.value.find((a) => a.id === aktifAtribut.value)?.tipe === 'numerik'

function reset() { editing.value = null; form.label = ''; form.batas_bawah = ''; form.batas_atas = ''; form.urutan = 0; errMsg.value = '' }
function editK(k) { editing.value = k.id; form.label = k.label; form.batas_bawah = k.batas_bawah ?? ''; form.batas_atas = k.batas_atas ?? ''; form.urutan = k.urutan }

async function submit() {
  saving.value = true; errMsg.value = ''
  const payload = {
    atribut_klasifikasi_id: aktifAtribut.value,
    label: form.label,
    batas_bawah: form.batas_bawah === '' ? null : Number(form.batas_bawah),
    batas_atas: form.batas_atas === '' ? null : Number(form.batas_atas),
    urutan: Number(form.urutan),
  }
  try {
    if (editing.value) await Kategori.update(editing.value, payload)
    else await Kategori.create(payload)
    reset(); await loadKategori()
  } catch (e) { errMsg.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Gagal.' }
  finally { saving.value = false }
}
async function destroy(id) {
  if (!confirm('Hapus kategori ini?')) return
  await Kategori.remove(id); await loadKategori()
}
</script>

<template>
  <div>
    <PageHeader title="Kategori & Bin Atribut" subtitle="Kelola parameter bin, batas numerik, dan kategori dari 4 atribut utama Naive Bayes" />

    <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Pemilih atribut + daftar kategori -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-4 flex flex-wrap gap-2">
          <button v-for="a in atributList" :key="a.id" @click="aktifAtribut = a.id; loadKategori(); reset()"
            :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-2', aktifAtribut === a.id ? 'bg-[#1F2937] text-white shadow-xs' : 'bg-[#F8F7F2] text-[#4B5563] hover:bg-[#EBE9E0] border border-[#D5D3C9]']">
            <Sliders :class="['w-3.5 h-3.5', aktifAtribut === a.id ? 'text-[#96B6C5]' : 'text-[#9CA3AF]']" />
            <span>{{ a.nama_tampilan }}</span>
            <span :class="['text-[10px] uppercase px-1.5 py-0.5 rounded font-mono font-bold', aktifAtribut === a.id ? 'bg-white/10 text-white' : 'bg-[#EBE9E0] text-[#1F2937]']">{{ a.tipe }}</span>
          </button>
        </div>

        <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
              <thead class="bg-[#E6F0F4] text-[#1F2937] text-xs font-semibold uppercase tracking-wider">
                <tr>
                  <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Label Kategori</th>
                  <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]" v-if="isNumerik()">Batas Bawah</th>
                  <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]" v-if="isNumerik()">Batas Atas</th>
                  <th class="text-center px-4 py-3.5 border-b border-[#EBE9E0]">Urutan Bin</th>
                  <th class="text-right px-4 py-3.5 border-b border-[#EBE9E0]">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#EBE9E0]">
                <tr v-for="k in items" :key="k.id" class="hover:bg-[#F8F7F2] transition-colors">
                  <td class="px-4 py-3.5 font-bold text-[#1F2937]">{{ k.label }}</td>
                  <td class="px-4 py-3.5 font-mono text-xs text-[#4B5563]" v-if="isNumerik()">{{ k.batas_bawah ?? '−∞ (Tidak terbatas)' }}</td>
                  <td class="px-4 py-3.5 font-mono text-xs text-[#4B5563]" v-if="isNumerik()">{{ k.batas_atas ?? '∞ (Tidak terbatas)' }}</td>
                  <td class="px-4 py-3.5 text-center font-mono font-semibold text-[#1F2937]">{{ k.urutan }}</td>
                  <td class="px-4 py-3.5 text-right space-x-1 whitespace-nowrap">
                    <button @click="editK(k)" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-[#D5D3C9] text-[#1F2937] rounded-lg text-xs font-semibold hover:bg-[#F8F7F2] transition-colors shadow-xs">
                      <Edit3 class="w-3 h-3 text-[#96B6C5]" />
                      <span>Edit</span>
                    </button>
                    <button @click="destroy(k.id)" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-[#F8D7DA] text-[#721C24] rounded-lg text-xs font-semibold hover:bg-[#f5c6cb] transition-colors shadow-xs">
                      <Trash2 class="w-3 h-3" />
                      <span>Hapus</span>
                    </button>
                  </td>
                </tr>
                <tr v-if="!items.length">
                  <td :colspan="isNumerik() ? 5 : 3" class="px-4 py-8 text-center text-[#9CA3AF] text-sm font-medium">Belum ada kategori atribut.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Form tambah/edit -->
      <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-6 h-fit space-y-4">
        <div class="pb-3 border-b border-[#EBE9E0]">
          <h3 class="font-bold text-[#1F2937] text-base flex items-center gap-2">
            <Layers class="w-4 h-4 text-[#96B6C5]" />
            <span>{{ editing ? 'Edit Kategori Bin' : 'Tambah Kategori Bin' }}</span>
          </h3>
          <p class="text-xs text-[#4B5563] mt-0.5">Konfigurasi interval atau label bin atribut</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div v-if="errMsg" class="p-3 bg-[#F8D7DA] text-[#721C24] rounded-xl border border-[#721C24]/20 text-xs font-semibold flex items-center gap-2">
            <AlertCircle class="w-4 h-4 flex-shrink-0" />
            <span>{{ errMsg }}</span>
          </div>

          <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Label Bin / Kategori <span class="text-[#DC3545]">*</span></span>
            <input v-model="form.label" required class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]" placeholder="mis. 1.000.000–2.000.000 atau Buruh" />
          </label>

          <div v-if="isNumerik()" class="grid grid-cols-2 gap-3">
            <label class="block">
              <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Batas Bawah</span>
              <input v-model="form.batas_bawah" type="number" step="0.01" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3 py-2 text-sm text-[#1F2937] font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]" placeholder="Kosong = −∞" />
            </label>
            <label class="block">
              <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Batas Atas</span>
              <input v-model="form.batas_atas" type="number" step="0.01" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3 py-2 text-sm text-[#1F2937] font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]" placeholder="Kosong = ∞" />
            </label>
          </div>

          <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Urutan Bin (Ke-N) <span class="text-[#DC3545]">*</span></span>
            <input v-model="form.urutan" required type="number" min="0" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] font-mono font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]" />
          </label>

          <p class="text-[11px] text-[#9CA3AF] leading-relaxed bg-[#F8F7F2] p-2.5 rounded-lg border border-[#EBE9E0]" v-if="isNumerik()">
            💡 <b class="text-[#1F2937]">Info Bin Numerik:</b> Bin tidak boleh tumpang tindih (overlap). Batas atas kosong berarti hingga <span class="font-mono">∞</span>, batas bawah kosong berarti dari <span class="font-mono">−∞</span>.
          </p>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#EBE9E0]">
            <button v-if="editing" type="button" @click="reset" class="px-3.5 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#4B5563] hover:bg-[#F8F7F2] transition-colors">Batal</button>
            <button :disabled="saving" type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] disabled:opacity-50 transition-colors shadow-xs">
              <Plus v-if="!editing" class="w-4 h-4 text-[#96B6C5]" />
              <span>{{ editing ? 'Simpan Perubahan' : 'Tambah Kategori Bin' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

