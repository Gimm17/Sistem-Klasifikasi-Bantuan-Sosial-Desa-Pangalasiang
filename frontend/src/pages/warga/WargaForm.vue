<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Warga, Atribut } from '@/services/endpoints'
import PageHeader from '@/components/PageHeader.vue'
import { Save, ArrowLeft } from '@lucide/vue'

const router = useRouter()
const route = useRoute()
const isEdit = computed(() => !!route.params.id)

const atribut = ref([])
const form = reactive({
  nik: '', nama: '', alamat: '', dusun: '',
  penghasilan_bulanan: 0, status_pekerjaan: '', jumlah_tanggungan: 0, kondisi_rumah: '',
  periode_data: new Date().toISOString().slice(0, 10),
})
const errors = ref({})
const saving = ref(false)

const pekerjaanLabels = computed(() => (atribut.value.find((a) => a.kode === 'pekerjaan')?.kategori ?? []).map((k) => k.label))
const kondisiLabels = computed(() => (atribut.value.find((a) => a.kode === 'kondisi_rumah')?.kategori ?? []).map((k) => k.label))

async function loadAtribut() {
  atribut.value = await Atribut.list()
  // default nilai pertama jika belum dipilih
  if (!form.status_pekerjaan && pekerjaanLabels.value.length) form.status_pekerjaan = pekerjaanLabels.value[0]
  // kondisi_rumah TIDAK di-default — diisi saat validasi (setelah foto diunggah).
}

async function loadWarga() {
  if (!isEdit.value) return
  const w = await Warga.get(route.params.id)
  Object.assign(form, {
    nik: w.nik, nama: w.nama, alamat: w.alamat ?? '', dusun: w.dusun ?? '',
    penghasilan_bulanan: w.penghasilan_bulanan, status_pekerjaan: w.status_pekerjaan,
    jumlah_tanggungan: w.jumlah_tanggungan, kondisi_rumah: w.kondisi_rumah ?? '',
    periode_data: w.periode_data,
  })
}

onMounted(async () => { await loadAtribut(); await loadWarga() })

async function submit() {
  saving.value = true; errors.value = {}
  try {
    const payload = { ...form, penghasilan_bulanan: Number(form.penghasilan_bulanan), jumlah_tanggungan: Number(form.jumlah_tanggungan) }
    if (isEdit.value) {
      await Warga.update(route.params.id, payload)
      router.push({ name: 'warga.show', params: { id: route.params.id } })
    } else {
      const created = await Warga.create(payload)
      // Arahkan ke detail agar langsung upload foto rumah + validasi.
      const newId = created?.data?.id ?? created?.id
      router.push({ name: 'warga.show', params: { id: newId } })
    }
  } catch (e) {
    errors.value = e.response?.data?.errors ?? { _: [e.response?.data?.message ?? 'Gagal menyimpan'] }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader :title="isEdit ? 'Edit Data Warga' : 'Tambah Warga Baru'" subtitle="Formulir pendataan calon penerima bantuan sosial">
      <template #actions>
        <button type="button" @click="router.push({ name: 'warga.list' })" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-[#D5D3C9] rounded-xl text-xs font-semibold text-[#4B5563] hover:bg-[#F8F7F2] shadow-xs transition-colors">
          <ArrowLeft class="w-4 h-4" />
          <span>Kembali ke Daftar</span>
        </button>
      </template>
    </PageHeader>

    <div class="p-6">
      <form @submit.prevent="submit" class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-6 max-w-3xl space-y-6">
        <div v-if="errors._" class="p-3.5 bg-[#F8D7DA] text-[#721C24] rounded-xl border border-[#721C24]/20 text-xs font-semibold">
          {{ errors._[0] }}
        </div>

        <div>
          <h3 class="font-bold text-[#1F2937] text-base mb-1">Identitas Warga</h3>
          <p class="text-xs text-[#4B5563] mb-4">Informasi pribadi dasar warga desa</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="block">
              <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">NIK (16 Digit) <span class="text-[#DC3545]">*</span></span>
              <input v-model="form.nik" maxlength="16" placeholder="3321000000000000" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 font-mono text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all" />
              <span v-if="errors.nik" class="text-xs text-[#DC3545] mt-1 block">{{ errors.nik[0] }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Nama Lengkap <span class="text-[#DC3545]">*</span></span>
              <input v-model="form.nama" placeholder="Nama sesuai KTP" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all" />
              <span v-if="errors.nama" class="text-xs text-[#DC3545] mt-1 block">{{ errors.nama[0] }}</span>
            </label>
          </div>
        </div>

        <label class="block">
          <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Alamat Domisili</span>
          <textarea v-model="form.alamat" rows="2" placeholder="Nama Jalan, RT/RW, Dusun..." class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all"></textarea>
        </label>

        <label class="block">
          <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Dusun / Wilayah</span>
          <select v-model="form.dusun" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] transition-all">
            <option value="">Pilih Dusun (Opsional)</option>
            <option value="Dusun I">Dusun I</option>
            <option value="Dusun II">Dusun II</option>
            <option value="Dusun III">Dusun III</option>
            <option value="Dusun IV">Dusun IV</option>
            <option value="Dusun V">Dusun V</option>
            <option value="Dusun VI">Dusun VI</option>
            <option value="Dusun VII">Dusun VII</option>
            <option value="Dusun VIII">Dusun VIII</option>
          </select>
        </label>

        <div class="pt-4 border-t border-[#EBE9E0]">
          <h3 class="font-bold text-[#1F2937] text-base mb-1">Atribut Klasifikasi Sosial Ekonomi</h3>
          <p class="text-xs text-[#4B5563] mb-4">Indikator penentu kelayakan dalam algoritma Naive Bayes</p>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="block">
              <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Penghasilan Bulanan (Rp) <span class="text-[#DC3545]">*</span></span>
              <input v-model="form.penghasilan_bulanan" type="number" min="0" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 font-mono text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all" />
              <span v-if="errors.penghasilan_bulanan" class="text-xs text-[#DC3545] mt-1 block">{{ errors.penghasilan_bulanan[0] }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Jumlah Tanggungan <span class="text-[#DC3545]">*</span></span>
              <input v-model="form.jumlah_tanggungan" type="number" min="0" max="20" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 font-mono text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all" />
            </label>
            <label class="block">
              <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Status Pekerjaan <span class="text-[#DC3545]">*</span></span>
              <select v-model="form.status_pekerjaan" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all">
                <option v-for="l in pekerjaanLabels" :key="l" :value="l">{{ l }}</option>
              </select>
              <span v-if="errors.status_pekerjaan" class="text-xs text-[#DC3545] mt-1 block">{{ errors.status_pekerjaan[0] }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Kondisi Rumah</span>
              <select v-model="form.kondisi_rumah" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all">
                <option value="">— Diisi saat validasi —</option>
                <option v-for="l in kondisiLabels" :key="l" :value="l">{{ l }}</option>
              </select>
              <span class="text-[11px] text-[#9CA3AF] mt-1 block">Label wajib dipilih saat validasi setelah foto rumah diunggah. Bisa diisi sekarang jika sudah pasti.</span>
              <span v-if="errors.kondisi_rumah" class="text-xs text-[#DC3545] mt-1 block">{{ errors.kondisi_rumah[0] }}</span>
            </label>
            <label class="block md:col-span-2">
              <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Periode Data Pendataan</span>
              <input v-model="form.periode_data" type="date" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5] focus:border-transparent transition-all max-w-xs" />
            </label>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EBE9E0]">
          <button type="button" @click="router.push({ name: 'warga.list' })" class="px-5 py-2.5 bg-white border border-[#D5D3C9] text-[#4B5563] rounded-xl text-xs font-semibold hover:bg-[#F8F7F2] shadow-xs transition-colors">Batal</button>
          <button :disabled="saving" type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] shadow-xs transition-colors disabled:opacity-50">
            <Save class="w-4 h-4 text-[#96B6C5]" />
            <span>{{ saving ? 'Menyimpan Data…' : 'Simpan Data Warga' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
