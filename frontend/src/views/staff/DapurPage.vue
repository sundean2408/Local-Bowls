<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../store/auth.js'
import api, { getImageUrl } from '../../services/api'

const router = useRouter()
const authStore = useAuthStore()

const orders = ref([])
const loading = ref(true)
const error = ref(null)
const activeTab = ref('baru')
let intervalId = null

// Peta status -> label, warna, dan aksi tombol berikutnya.
const STATUS_META = {
  baru: { label: 'Baru', color: '#c02a2a', bg: '#FBEAEA', next: 'diproses', nextLabel: 'Mulai Masak', nextIcon: '🍳' },
  diproses: { label: 'Diproses', color: '#D97757', bg: '#FBEAD9', next: 'selesai', nextLabel: 'Selesai Masak', nextIcon: '✓' },
  selesai: { label: 'Siap Diambil', color: '#4CAF50', bg: '#E9F6EA', next: null },
}

const TABS = [
  { id: 'baru', label: 'Baru', icon: '🆕' },
  { id: 'diproses', label: 'Diproses', icon: '🍳' },
  { id: 'selesai', label: 'Siap Diambil', icon: '✅' },
]

function ordersByStatus(status) {
  return orders.value.filter((o) => o.status_pesanan === status)
}

const baruCount = computed(() => ordersByStatus('baru').length)
const diprosesCount = computed(() => ordersByStatus('diproses').length)
const selesaiCount = computed(() => ordersByStatus('selesai').length)

const activeOrders = computed(() =>
  [...ordersByStatus(activeTab.value)].sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
)

function nomorMeja(pesanan) {
  return pesanan.meja?.nomor_meja ?? pesanan.nomor_meja ?? pesanan.id_meja ?? '-'
}

function waktuLalu(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return '-'
  const menit = Math.max(0, Math.floor((Date.now() - d.getTime()) / 60000))
  if (menit < 1) return 'Baru saja'
  if (menit < 60) return `${menit} menit lalu`
  const jam = Math.floor(menit / 60)
  return `${jam} jam lalu`
}

const lastUpdated = ref(null)

async function fetchOrders() {
  try {
    const res = await api.getStaffOrders('kitchen')
    // api.get() (fetch-based) mengembalikan body JSON asli dari Laravel,
    // bisa berupa array langsung ATAU objek { data: [...] } tergantung
    // bagaimana controller Laravel membungkus response-nya.
    const semua = Array.isArray(res) ? res : (res.data ?? res)
    orders.value = Array.isArray(semua) ? semua : []
    error.value = null
    lastUpdated.value = new Date()
  } catch (e) {
    error.value = 'Gagal memuat pesanan dapur. Pastikan backend Laravel sedang berjalan.'
    console.error('Gagal fetch pesanan dapur:', e)
  } finally {
    loading.value = false
  }
}

async function majukanStatus(pesanan) {
  const meta = STATUS_META[pesanan.status_pesanan]
  if (!meta?.next) return
  const statusLama = pesanan.status_pesanan
  pesanan.status_pesanan = meta.next // optimistic update
  try {
    await api.updateOrderStatus(pesanan.id, meta.next)
  } catch (e) {
    pesanan.status_pesanan = statusLama // rollback kalau gagal
    alert('Gagal memperbarui status pesanan. Coba lagi.')
    console.error(e)
  }
}

onMounted(() => {
  if (!authStore.isAuthenticated || authStore.user?.role !== 'kitchen') {
    router.push('/login')
    return
  }
  fetchOrders()
  intervalId = setInterval(fetchOrders, 5000)
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})
</script>

<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
    <!-- Judul konten + status auto-refresh -->
    <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
      <div>
        <h2 class="text-xl font-bold text-terracotta-900">Pesanan Dapur</h2>
        <p class="text-sm text-earth-dark/60">
          Diperbarui otomatis tiap 5 detik
          <span v-if="lastUpdated"> · terakhir {{ waktuLalu(lastUpdated) }}</span>
        </p>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-3 gap-3 sm:gap-4 mb-6">
      <div class="rounded-2xl p-4 sm:p-5 bg-white border border-terracotta-100 flex items-center gap-3">
        <span class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0" style="background-color:#FBEAEA;">🆕</span>
        <div class="min-w-0">
          <p class="text-xs text-earth-dark/60 mb-0.5 truncate">Pesanan Baru</p>
          <p class="text-xl sm:text-2xl font-bold" style="color:#c02a2a;">{{ baruCount }}</p>
        </div>
      </div>
      <div class="rounded-2xl p-4 sm:p-5 bg-white border border-terracotta-100 flex items-center gap-3">
        <span class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0" style="background-color:#FBEAD9;">🍳</span>
        <div class="min-w-0">
          <p class="text-xs text-earth-dark/60 mb-0.5 truncate">Diproses</p>
          <p class="text-xl sm:text-2xl font-bold text-terracotta-700">{{ diprosesCount }}</p>
        </div>
      </div>
      <div class="rounded-2xl p-4 sm:p-5 bg-white border border-terracotta-100 flex items-center gap-3">
        <span class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0" style="background-color:#E9F6EA;">✅</span>
        <div class="min-w-0">
          <p class="text-xs text-earth-dark/60 mb-0.5 truncate">Siap Diambil</p>
          <p class="text-xl sm:text-2xl font-bold" style="color:#4CAF50;">{{ selesaiCount }}</p>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 mb-6 overflow-x-auto pb-1">
      <button
        v-for="tab in TABS"
        :key="tab.id"
        @click="activeTab = tab.id"
        class="flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition"
        :class="activeTab === tab.id ? 'text-white shadow-sm' : 'bg-white text-earth-dark border border-terracotta-100'"
        :style="activeTab === tab.id ? { backgroundColor: STATUS_META[tab.id].color } : {}"
      >
        <span>{{ tab.icon }}</span>
        {{ tab.label }} ({{ tab.id === 'baru' ? baruCount : tab.id === 'diproses' ? diprosesCount : selesaiCount }})
      </button>
    </div>

    <!-- States -->
    <div v-if="loading" class="text-center py-16 text-earth-dark/60">
      <div class="inline-block w-6 h-6 border-2 border-terracotta-300 border-t-terracotta-600 rounded-full animate-spin mb-3"></div>
      <p>Memuat pesanan...</p>
    </div>
    <div v-else-if="error" class="text-center py-16 rounded-2xl border-2 border-dashed border-red-200 bg-red-50">
      <p class="text-red-600 mb-3">{{ error }}</p>
      <button @click="loading = true; fetchOrders()" class="text-sm font-bold px-5 py-2.5 rounded-full text-white bg-terracotta-600 hover:bg-terracotta-700 transition">Coba Lagi</button>
    </div>
    <div v-else-if="activeOrders.length === 0" class="text-center py-16 rounded-2xl border-2 border-dashed border-terracotta-100 text-earth-dark/60">
      <div class="text-3xl mb-2">🍽️</div>
      Tidak ada pesanan {{ TABS.find(t => t.id === activeTab)?.label.toLowerCase() }} saat ini.
    </div>

    <!-- Order list -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div
        v-for="pesanan in activeOrders"
        :key="pesanan.id"
        class="rounded-2xl bg-white border overflow-hidden flex flex-col"
        :style="{ borderColor: STATUS_META[pesanan.status_pesanan].color + '55' }"
      >
        <div class="flex items-center justify-between px-5 py-3" :style="{ backgroundColor: STATUS_META[pesanan.status_pesanan].bg }">
          <div>
            <p class="text-xs font-bold tracking-wide" :style="{ color: STATUS_META[pesanan.status_pesanan].color }">
              PESANAN #{{ String(pesanan.id).padStart(3, '0') }}
            </p>
            <p class="text-sm text-earth-dark/70">Meja {{ nomorMeja(pesanan) }} · {{ waktuLalu(pesanan.created_at) }}</p>
          </div>
          <span
            class="text-xs font-bold px-3 py-1 rounded-full text-white shrink-0"
            :style="{ backgroundColor: STATUS_META[pesanan.status_pesanan].color }"
          >{{ STATUS_META[pesanan.status_pesanan].label }}</span>
        </div>

        <div class="px-5 py-4 flex-1">
          <div
            v-for="detail in pesanan.detail_pesanan"
            :key="detail.id"
            class="flex items-start gap-3 py-2 border-b border-cream-100 last:border-0"
          >
            <img
              :src="getImageUrl(detail.menu?.gambar)"
              :alt="detail.menu?.nama_menu"
              class="w-14 h-14 rounded-lg object-cover shrink-0 bg-cream-100"
              @error="$event.target.src = '/placeholder.png'"
            />
            <div class="min-w-0">
              <p class="font-bold text-earth-dark">{{ detail.jumlah }}× {{ detail.menu?.nama_menu }}</p>
              <p v-if="detail.catatan" class="text-xs text-earth-dark/60 mt-0.5">📝 {{ detail.catatan }}</p>
            </div>
          </div>
        </div>

        <div v-if="STATUS_META[pesanan.status_pesanan].next" class="px-5 pb-5">
          <button
            @click="majukanStatus(pesanan)"
            class="w-full py-3 rounded-full font-bold text-white text-sm transition hover:opacity-90"
            :style="{ backgroundColor: STATUS_META[pesanan.status_pesanan].color }"
          >
            {{ STATUS_META[pesanan.status_pesanan].nextIcon }} {{ STATUS_META[pesanan.status_pesanan].nextLabel }}
          </button>
        </div>
        <div v-else class="px-5 pb-5">
          <p class="text-center text-xs font-medium py-2.5 rounded-full" style="background-color:#E9F6EA; color:#4CAF50;">
            ✓ Menunggu diambil pelanggan
          </p>
        </div>
      </div>
    </div>
  </div>
</template>