<script setup>
import { ref, onMounted } from 'vue'
import { Evaluasi } from '@/services/endpoints'
import { useAuthStore } from '@/stores/auth'
import PageHeader from '@/components/PageHeader.vue'
import { BarChart2, Settings, Play, CheckCircle, Calendar, Layers } from '@lucide/vue'

const auth = useAuthStore()
const riwayat = ref([])
const metode = ref('holdout')
const testRatio = ref(0.2)
const k = ref(5)
const busy = ref(false)
const msg = ref('')
const canRun = auth.role === 'admin' || auth.role === 'superadmin'

async function load() {
  const res = await Evaluasi.list()
  riwayat.value = res.data
}
onMounted(load)

async function run() {
  busy.value = true; msg.value = ''
  try {
    const opts = { metode: metode.value }
    if (metode.value === 'kfold') {
      opts.k = Number(k.value)
    } else {
      opts.test_ratio = Number(testRatio.value)
    }
    const r = await Evaluasi.run(opts)
    const acc = (Number(r.data.accuracy) * 100).toFixed(1)
    msg.value = `Selesai: accuracy ${acc}% (${metode.value === 'kfold' ? `k=${k.value}` : `split ${testRatio.value}`}).`
    await load()
  } catch (e) {
    msg.value = e.response?.data?.message || 'Gagal evaluasi.'
  } finally { busy.value = false }
}

const pct = (n) => (Number(n) * 100).toFixed(2) + '%'
const metodeLabel = (m) => m === 'kfold' ? 'k-Fold CV' : 'Hold-out'
</script>

<template>
  <div>
    <PageHeader title="Evaluasi Model Naive Bayes" subtitle="Pengujian akurasi dengan Hold-out (Train/Test Split) & k-Fold Stratified Cross-Validation" />

    <div class="p-6 space-y-6">
      <!-- Control Bar -->
      <div v-if="canRun" class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#E6F0F4] text-[#1A3B47] flex items-center justify-center flex-shrink-0">
            <Settings class="w-5 h-5 text-[#96B6C5]" />
          </div>
          <div>
            <h3 class="font-bold text-[#1F2937] text-sm">Konfigurasi Pengujian Model</h3>
            <p class="text-xs text-[#4B5563]">Pilih parameter validasi untuk mengukur performa model pada data latih berlabel</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-[#4B5563]">Metode:</label>
            <select v-model="metode" class="bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3 py-1.5 text-xs font-semibold text-[#1F2937] focus:outline-none focus:ring-2 focus:ring-[#96B6C5]">
              <option value="holdout">Hold-out (Train/Test Split)</option>
              <option value="kfold">k-Fold Cross-Validation</option>
            </select>
          </div>

          <template v-if="metode === 'holdout'">
            <div class="flex items-center gap-2">
              <label class="text-xs font-semibold text-[#4B5563]">Rasio Data Uji:</label>
              <select v-model="testRatio" class="bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3 py-1.5 text-xs font-semibold text-[#1F2937] focus:outline-none focus:ring-2 focus:ring-[#96B6C5]">
                <option :value="0.1">10% Data Uji</option>
                <option :value="0.2">20% Data Uji</option>
                <option :value="0.3">30% Data Uji</option>
                <option :value="0.4">40% Data Uji</option>
              </select>
            </div>
          </template>
          <template v-else>
            <div class="flex items-center gap-2">
              <label class="text-xs font-semibold text-[#4B5563]">Fold (k):</label>
              <select v-model="k" class="bg-[#F8F7F2] border border-[#D5D3C9] rounded-xl px-3 py-1.5 text-xs font-semibold text-[#1F2937] focus:outline-none focus:ring-2 focus:ring-[#96B6C5]">
                <option :value="2">2 Fold</option>
                <option :value="3">3 Fold</option>
                <option :value="4">4 Fold</option>
                <option :value="5">5 Fold</option>
                <option :value="10">10 Fold</option>
              </select>
            </div>
          </template>

          <button :disabled="busy" @click="run" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#1F2937] text-white rounded-xl text-xs font-semibold hover:bg-[#374151] shadow-xs transition-colors disabled:opacity-50">
            <Play class="w-3.5 h-3.5 text-[#96B6C5] fill-current" />
            <span>{{ busy ? 'Menghitung Evaluasi…' : 'Jalankan Evaluasi' }}</span>
          </button>
        </div>

        <div v-if="msg" class="w-full text-xs font-semibold text-[#155724] bg-[#D4EDDA] p-3 rounded-xl border border-[#27AE60]/30 flex items-center gap-2">
          <CheckCircle class="w-4 h-4 flex-shrink-0 text-[#27AE60]" />
          <span>{{ msg }}</span>
        </div>
      </div>

      <div v-if="!riwayat.length" class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-12 text-center text-[#9CA3AF] text-sm font-medium">
        Belum ada riwayat pengujian evaluasi model.
      </div>

      <div v-for="e in riwayat" :key="e.id" class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] p-6 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-2 pb-4 border-b border-[#EBE9E0]">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#E6F0F4] text-[#1A3B47] flex items-center justify-center font-bold text-sm">
              <BarChart2 class="w-5 h-5 text-[#96B6C5]" />
            </div>
            <div>
              <h4 class="font-bold text-[#1F2937] text-base">{{ e.model_version }}</h4>
              <p class="text-xs text-[#4B5563] flex items-center gap-2 mt-0.5">
                <span class="inline-flex items-center gap-1"><Calendar class="w-3.5 h-3.5 text-[#9CA3AF]" /> {{ new Date(e.created_at).toLocaleString('id-ID') }}</span>
                <span>&bull;</span>
                <span class="px-2 py-0.5 bg-[#E6F0F4] text-[#1A3B47] rounded-full text-[10px] font-bold uppercase tracking-wider">{{ metodeLabel(e.metode) }}{{ e.metode === 'kfold' ? ` (k=${e.k})` : '' }}</span>
                <span>&bull;</span>
                <span class="font-mono tnum text-[#1F2937] font-semibold">{{ e.jumlah_data_uji }} data uji</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
          <div class="bg-[#F8F7F2] rounded-xl p-4 border border-[#EBE9E0]">
            <p class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Accuracy</p>
            <p class="text-2xl font-bold text-[#1F2937] mt-1 tnum">{{ pct(e.accuracy) }}</p>
          </div>
          <div class="bg-[#F8F7F2] rounded-xl p-4 border border-[#EBE9E0]">
            <p class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Precision</p>
            <p class="text-2xl font-bold text-[#1F2937] mt-1 tnum">{{ pct(e.precision) }}</p>
          </div>
          <div class="bg-[#F8F7F2] rounded-xl p-4 border border-[#EBE9E0]">
            <p class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">Recall</p>
            <p class="text-2xl font-bold text-[#1F2937] mt-1 tnum">{{ pct(e.recall) }}</p>
          </div>
          <div class="bg-[#F8F7F2] rounded-xl p-4 border border-[#EBE9E0]">
            <p class="text-xs font-semibold uppercase tracking-wider text-[#4B5563]">F1-Score</p>
            <p class="text-2xl font-bold text-[#1F2937] mt-1 tnum">{{ pct(e.f1_score) }}</p>
          </div>
        </div>

        <!-- Confusion matrix grid -->
        <div>
          <h5 class="font-bold text-[#1F2937] text-sm mb-2">Confusion Matrix (Matriks Kebingungan)</h5>
          <p class="text-xs text-[#4B5563] mb-3">Perbandingan klasifikasi prediksi mesin vs label aktual pada data uji</p>
          
          <div class="grid grid-cols-3 gap-2 text-xs max-w-md font-mono">
            <div></div>
            <div class="text-center font-bold text-[#1F2937] bg-[#E6F0F4] py-2 rounded-lg">Pred: Layak</div>
            <div class="text-center font-bold text-[#1F2937] bg-[#E6F0F4] py-2 rounded-lg">Pred: Tidak Layak</div>
            
            <div class="text-right font-bold text-[#1F2937] flex items-center justify-end pr-2">Aktual: Layak</div>
            <div class="bg-[#D4EDDA] border border-[#27AE60]/30 text-center py-3.5 rounded-xl font-bold text-[#155724] text-base tnum flex flex-col items-center justify-center">
              <span>{{ e.confusion_matrix.TP }}</span>
              <span class="text-[10px] font-sans font-semibold text-[#155724]/70 uppercase">True Positive (TP)</span>
            </div>
            <div class="bg-[#F8D7DA] border border-[#721C24]/30 text-center py-3.5 rounded-xl font-bold text-[#721C24] text-base tnum flex flex-col items-center justify-center">
              <span>{{ e.confusion_matrix.FN }}</span>
              <span class="text-[10px] font-sans font-semibold text-[#721C24]/70 uppercase">False Negative (FN)</span>
            </div>
            
            <div class="text-right font-bold text-[#1F2937] flex items-center justify-end pr-2">Aktual: Tidak Layak</div>
            <div class="bg-[#FFF3CD] border border-[#856404]/30 text-center py-3.5 rounded-xl font-bold text-[#856404] text-base tnum flex flex-col items-center justify-center">
              <span>{{ e.confusion_matrix.FP }}</span>
              <span class="text-[10px] font-sans font-semibold text-[#856404]/70 uppercase">False Positive (FP)</span>
            </div>
            <div class="bg-[#F8F7F2] border border-[#D5D3C9] text-center py-3.5 rounded-xl font-bold text-[#1F2937] text-base tnum flex flex-col items-center justify-center">
              <span>{{ e.confusion_matrix.TN }}</span>
              <span class="text-[10px] font-sans font-semibold text-[#4B5563] uppercase">True Negative (TN)</span>
            </div>
          </div>
        </div>

        <!-- k-fold extra info -->
        <div v-if="e.metode === 'kfold' && e.confusion_matrix.fold_distribution" class="pt-3 border-t border-[#EBE9E0] text-xs font-mono text-[#4B5563] flex items-center gap-2">
          <Layers class="w-4 h-4 text-[#9CA3AF]" />
          <span>Distribusi ukuran fold: <b class="text-[#1F2937]">{{ e.confusion_matrix.fold_distribution.join(', ') }}</b></span>
        </div>
      </div>
    </div>
  </div>
</template>

