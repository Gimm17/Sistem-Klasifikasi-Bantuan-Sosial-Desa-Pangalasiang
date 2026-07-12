<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { DataTraining, Atribut } from '@/services/endpoints'
import PageHeader from '@/components/PageHeader.vue'
import { Save, X, Database, AlertCircle } from '@lucide/vue'

const router = useRouter()
const route = useRoute()
const isEdit = computed(() => !!route.params.id)

const atribut = ref([])
const form = reactive({
  penghasilan_kategori: '', pekerjaan_kategori: '', tanggungan_kategori: '', kondisi_rumah_kategori: '',
  label_kelas: 'layak',
})
const errors = ref({})
const saving = ref(false)

const labels = (kode) => (atribut.value.find((a) => a.kode === kode)?.kategori ?? []).map((k) => k.label)

async function loadAtribut() {
  atribut.value = await Atribut.list()
  for (const k of ['penghasilan_kategori', 'pekerjaan_kategori', 'tanggungan_kategori', 'kondisi_rumah_kategori']) {
    const kode = k.replace('_kategori', '')
    if (!form[k] && labels(kode).length) form[k] = labels(kode)[0]
  }
}
async function loadData() {
  if (!isEdit.value) return
  const res = await DataTraining.list({ label_kelas: '' })
  const all = []
  let p = 1
  while (true) {
    const r = await DataTraining.list({ page: p })
    all.push(...r.data)
    if (p >= (r.meta?.last_page ?? 1)) break
    p++
  }
  const d = all.find((x) => x.id == route.params.id)
  if (d) Object.assign(form, { penghasilan_kategori: d.penghasilan_kategori, pekerjaan_kategori: d.pekerjaan_kategori, tanggungan_kategori: d.tanggungan_kategori, kondisi_rumah_kategori: d.kondisi_rumah_kategori, label_kelas: d.label_kelas })
}
onMounted(async () => { await loadAtribut(); await loadData() })

async function submit() {
  saving.value = true; errors.value = {}
  try {
    if (isEdit.value) await DataTraining.update(route.params.id, form)
    else await DataTraining.create(form)
    router.push({ name: 'training.list' })
  } catch (e) {
    errors.value = e.response?.data?.errors ?? { _: [e.response?.data?.message ?? 'Gagal menyimpan'] }
  } finally { saving.value = false }
}
</script>

<template>
  <div>
    <PageHeader :title="isEdit ? 'Edit Data Training' : 'Tambah Data Training'" subtitle="Formulir data observasi berlabel untuk pelatihan model Naive Bayes" />
    <div class="p-6">
      <form @submit.prevent="submit" class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-6 max-w-2xl space-y-5">
        <div v-if="errors._" class="p-4 bg-[#F8D7DA] text-[#721C24] rounded-xl border border-[#721C24]/20 text-xs font-semibold flex items-center gap-2">
          <AlertCircle class="w-4 h-4 flex-shrink-0" />
          <span>{{ errors._[0] }}</span>
        </div>

        <div class="flex items-center gap-3 pb-4 border-b border-[#EBE9E0]">
          <div class="w-10 h-10 rounded-xl bg-[#E6F0F4] text-[#1A3B47] flex items-center justify-center font-bold">
            <Database class="w-5 h-5 text-[#96B6C5]" />
          </div>
          <div>
            <h3 class="font-bold text-[#1F2937] text-sm">Atribut & Label Observasi</h3>
            <p class="text-xs text-[#4B5563]">Pastikan kategori atribut sesuai dengan kondisi riil lapangan</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Kategori Penghasilan</span>
            <select v-model="form.penghasilan_kategori" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]">
              <option v-for="l in labels('penghasilan')" :key="l" :value="l">{{ l }}</option>
            </select>
          </label>

          <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Kategori Pekerjaan</span>
            <select v-model="form.pekerjaan_kategori" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]">
              <option v-for="l in labels('pekerjaan')" :key="l" :value="l">{{ l }}</option>
            </select>
          </label>

          <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Kategori Tanggungan</span>
            <select v-model="form.tanggungan_kategori" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]">
              <option v-for="l in labels('tanggungan')" :key="l" :value="l">{{ l }}</option>
            </select>
          </label>

          <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Kondisi Rumah</span>
            <select v-model="form.kondisi_rumah_kategori" class="mt-1.5 w-full bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3.5 py-2.5 text-sm text-[#1F2937] font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]">
              <option v-for="l in labels('kondisi_rumah')" :key="l" :value="l">{{ l }}</option>
            </select>
          </label>

          <label class="block md:col-span-2 pt-2 border-t border-[#EBE9E0]">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#1A3B47] flex items-center gap-1.5">
              <span>Label Kelas Akhir (Observasi Lapangan / Ground Truth)</span>
              <span class="text-[#DC3545]">*</span>
            </span>
            <select v-model="form.label_kelas" class="mt-1.5 w-full bg-[#E6F0F4] border border-[#96B6C5]/50 rounded-xl px-3.5 py-2.5 text-sm text-[#1A3B47] font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#96B6C5]">
              <option value="layak">LAYAK Menerima Bantuan Sosial</option>
              <option value="tidak_layak">TIDAK LAYAK Menerima Bantuan Sosial</option>
            </select>
          </label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EBE9E0]">
          <button type="button" @click="router.push({ name: 'training.list' })" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-[#D5D3C9] text-[#4B5563] rounded-xl text-xs font-semibold hover:bg-[#F8F7F2] transition-colors">
            <X class="w-4 h-4" />
            <span>Batal</span>
          </button>
          <button :disabled="saving" type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] disabled:opacity-50 transition-colors shadow-xs">
            <Save class="w-4 h-4 text-[#96B6C5]" />
            <span>{{ saving ? 'Menyimpan…' : 'Simpan Data Training' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

