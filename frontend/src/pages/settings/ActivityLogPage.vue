<script setup>
import { ref, onMounted } from 'vue'
import { ActivityLogs } from '@/services/endpoints'
import PageHeader from '@/components/PageHeader.vue'
import Skeleton from '@/components/Skeleton.vue'
import { History, Clock, User, Activity } from '@lucide/vue'

const items = ref([])
const meta = ref(null)
const page = ref(1)
const loading = ref(false)

async function load() {
  loading.value = true
  try {
    const res = await ActivityLogs.list()
    items.value = res.data
    meta.value = res.meta
  } finally { loading.value = false }
}
onMounted(load)

const labelAksi = {
  'warga.create': 'Tambah Warga', 'warga.update': 'Ubah Data Warga', 'warga.delete': 'Hapus Data Warga',
  'warga.validasi': 'Validasi Klasifikasi', 'model.train': 'Training Ulang Model NB',
  'approval.approve': 'Setujui Kelayakan', 'approval.reject': 'Tolak Kelayakan', 'approval.override': 'Override Keputusan',
}
</script>

<template>
  <div>
    <PageHeader title="Jejak Audit Aktivitas (Audit Log)" subtitle="Catatan historis aktivitas pengguna dan keamanan sistem (Hak Akses Super Admin)" />

    <div class="p-6">
      <div class="bg-white rounded-xl shadow-xs border border-[#D5D3C9] overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead class="bg-[#E6F0F4] text-[#1F2937] text-xs font-semibold uppercase tracking-wider">
              <tr>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Waktu Kejadian</th>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Pengguna</th>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Jenis Aksi</th>
                <th class="text-left px-4 py-3.5 border-b border-[#EBE9E0]">Keterangan Detail</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EBE9E0]">
              <tr v-for="log in items" :key="log.id" class="hover:bg-[#F8F7F2] transition-colors">
                <td class="px-4 py-3.5 text-xs text-[#4B5563] font-mono whitespace-nowrap tnum flex items-center gap-1.5">
                  <Clock class="w-3.5 h-3.5 text-[#9CA3AF]" />
                  <span>{{ new Date(log.created_at).toLocaleString('id-ID') }}</span>
                </td>
                <td class="px-4 py-3.5 font-bold text-[#1F2937]">
                  <span class="inline-flex items-center gap-1.5">
                    <User class="w-3.5 h-3.5 text-[#96B6C5]" />
                    <span>{{ log.user?.name ?? 'Sistem / Guest' }}</span>
                  </span>
                </td>
                <td class="px-4 py-3.5">
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#F8F7F2] border border-[#D5D3C9] rounded-lg text-xs font-semibold text-[#1A3B47]">
                    <Activity class="w-3 h-3 text-[#96B6C5]" />
                    <span>{{ labelAksi[log.action] ?? log.action }}</span>
                  </span>
                </td>
                <td class="px-4 py-3.5 text-[#4B5563] text-xs leading-relaxed">{{ log.description }}</td>
              </tr>
              <template v-if="loading && !items.length">
                <tr v-for="i in 5" :key="'sk'+i" class="border-b border-[#EBE9E0]">
                  <td v-for="j in 4" :key="j" class="px-4 py-3.5"><Skeleton w="70%" h="12px" /></td>
                </tr>
              </template>
              <tr v-else-if="!items.length">
                <td colspan="4" class="px-4 py-10 text-center text-[#9CA3AF] text-sm font-medium">Belum ada catatan aktivitas tercatat.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

