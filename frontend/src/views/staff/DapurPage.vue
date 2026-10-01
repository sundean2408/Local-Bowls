<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../store/auth.js'
import api, { getImageUrl } from '../../services/api'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const router = useRouter()
const authStore = useAuthStore()

const orders = ref([])
const loading = ref(true)
const error = ref(null)
const activeTab = ref('semua')
const busy = ref({})
const gagal = ref({})
const toast = ref('')
const selesaiHariIni = ref(0)
let intervalId = null
let toastTimer = null

const STATUS_META = {
  baru: { next: 'diproses', nextLabel: 'Mulai Masak' },
  diproses: { next: 'selesai', nextLabel: 'Selesai Masak' },
}

const TABS = [
  { id: 'semua', label: 'Semua Aktif' },
  { id: 'baru', label: 'Baru' },
  { id: 'diproses', label: 'Diproses' },
]

const PILIHAN_STATUS = [
  { id: 'baru', label: 'Baru' },
  { id: 'diproses', label: 'Diproses' },
  { id: 'selesai', label: 'Selesai → Kasir' },
]

function isAktif(o) {
  return o.status_pesanan === 'baru' || o.status_pesanan === 'diproses'
}

function ordersAktif() {
  return orders.value.filter(isAktif)
}

function ordersByStatus(status) {
  return ordersAktif().filter((o) => o.status_pesanan === status)
}

const baruCount = computed(() => ordersByStatus('baru').length)
const diprosesCount = computed(() => ordersByStatus('diproses').length)
const totalAktif = computed(() => ordersAktif().length)

const activeOrders = computed(() => {
  const list = activeTab.value === 'semua' ? ordersAktif() : ordersByStatus(activeTab.value)
  return [...list].sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

function countTab(id) {
  if (id === 'baru') return baruCount.value
  if (id === 'diproses') return diprosesCount.value
  return totalAktif.value
}

function nomorMeja(pesanan) {
  return pesanan.meja?.nomor_meja ?? pesanan.nomor_meja ?? pesanan.id_meja ?? '-'
}

function detailList(pesanan) {
  return pesanan.detail_pesanan ?? pesanan.detailPesanan ?? []
}

function waktuLalu(dateStr) {
  if (!dateStr) return '-'
  const d = dateStr instanceof Date ? dateStr : new Date(dateStr)
  if (isNaN(d.getTime())) return '-'
  const menit = Math.max(0, Math.floor((Date.now() - d.getTime()) / 60000))
  if (menit < 1) return 'Baru saja'
  if (menit < 60) return `${menit} mnt lalu`
  const jam = Math.floor(menit / 60)
  return `${jam} jam lalu`
}

const lastUpdated = ref(null)

function showToast(pesan) {
  toast.value = pesan
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => (toast.value = ''), 2500)
}

async function fetchOrders() {
  try {
    const res = await api.getStaffOrders('kitchen')
    const semua = Array.isArray(res) ? res : (res.data ?? res)
    const list = Array.isArray(semua) ? semua : []
    // Siap-ambil tidak tampil di dapur: hanya baru + diproses yang disimpan.
    // ponytail: arsip selesai hari ini butuh endpoint khusus; pakai counter sesi dulu.
    const aktif = list.filter((o) => o.status_pesanan === 'baru' || o.status_pesanan === 'diproses')
    selesaiHariIni.value = list.filter((o) => o.status_pesanan === 'selesai').length
    orders.value = aktif
    error.value = null
    lastUpdated.value = new Date()
  } catch (e) {
    error.value = 'Gagal memuat pesanan dapur. Pastikan backend Laravel berjalan dan kamu masih login.'
    console.error('Gagal fetch pesanan dapur:', e)
  } finally {
    loading.value = false
  }
}

function muatUlang() {
  loading.value = orders.value.length === 0
  fetchOrders()
}

// Kelola status: bisa maju, mundur, atau langsung selesai. Selesai = hilang dari dapur.
async function ubahStatus(pesanan, statusBaru) {
  if (!statusBaru || statusBaru === pesanan.status_pesanan || busy.value[pesanan.id]) return
  const statusLama = pesanan.status_pesanan
  busy.value[pesanan.id] = true
  delete gagal.value[pesanan.id]
  pesanan.status_pesanan = statusBaru
  try {
    await api.updateOrderStatus(pesanan.id, statusBaru)
    if (statusBaru === 'selesai') {
      orders.value = orders.value.filter((o) => o.id !== pesanan.id)
      selesaiHariIni.value += 1
      showToast(`Pesanan #${String(pesanan.id).padStart(3, '0')} selesai — diteruskan ke kasir`)
    }
  } catch (e) {
    pesanan.status_pesanan = statusLama
    gagal.value[pesanan.id] = 'Gagal memperbarui status. Coba lagi.'
    console.error(e)
  } finally {
    delete busy.value[pesanan.id]
  }
}

function majukanStatus(pesanan) {
  const meta = STATUS_META[pesanan.status_pesanan]
  if (meta?.next) ubahStatus(pesanan, meta.next)
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
  <div class="lb-wrap dapur">
    <PageHeader
      eyebrow="Kitchen Display"
      title="Kelola Pesanan"
      :subtitle="`Auto-refresh tiap 5 detik${lastUpdated ? ' · terakhir ' + waktuLalu(lastUpdated) : ''}${totalAktif ? ' · ' + totalAktif + ' perlu tindakan' : ''}`"
    >
      <template #action>
        <button type="button" class="lb-btn-ghost dapur__refresh" @click="muatUlang">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 11a8 8 0 10-2.3 5.6M20 5v6h-6"/></svg>
          Muat Ulang
        </button>
      </template>
    </PageHeader>

    <div v-if="toast" class="lb-alert lb-alert--ok dapur__toast" role="status">{{ toast }}</div>

    <!-- Ringkasan -->
    <div v-if="loading" class="dapur__stats">
      <div v-for="i in 3" :key="i" class="lb-card dapur__stat">
        <div class="lb-skeleton dapur__sk-line"></div>
        <div class="lb-skeleton dapur__sk-num"></div>
      </div>
    </div>
    <div v-else class="dapur__stats">
      <div class="lb-card dapur__stat is-baru">
        <p class="dapur__stat-label">Pesanan Baru</p>
        <p class="dapur__stat-num">{{ baruCount }}</p>
      </div>
      <div class="lb-card dapur__stat is-proses">
        <p class="dapur__stat-label">Diproses</p>
        <p class="dapur__stat-num">{{ diprosesCount }}</p>
      </div>
      <div class="lb-card dapur__stat is-siap">
        <p class="dapur__stat-label">Selesai → Kasir</p>
        <p class="dapur__stat-num">{{ selesaiHariIni }}</p>
      </div>
    </div>

    <!-- Tab status -->
    <div class="dapur__tabs" role="tablist">
      <button
        v-for="tab in TABS"
        :key="tab.id"
        type="button"
        role="tab"
        :aria-selected="activeTab === tab.id"
        @click="activeTab = tab.id"
        class="dapur__tab"
        :class="{ 'is-active': activeTab === tab.id }"
      >
        {{ tab.label }}
        <span class="dapur__tab-count">{{ countTab(tab.id) }}</span>
      </button>
    </div>

    <!-- Status -->
    <div v-if="loading" class="lb-card dapur__loading">
      <div class="lb-spinner"></div>
      <p>Memuat pesanan dapur...</p>
    </div>

    <div v-else-if="error" class="lb-alert lb-alert--error dapur__alert">
      <p>{{ error }}</p>
      <button type="button" class="lb-btn-primary dapur__retry" @click="muatUlang">Coba Lagi</button>
    </div>

    <div v-else-if="activeOrders.length === 0" class="lb-empty">
      <p class="dapur__empty-title">Tidak ada pesanan {{ activeTab === 'semua' ? 'aktif' : TABS.find(t => t.id === activeTab)?.label.toLowerCase() }}</p>
      <p class="dapur__empty-sub">Pesanan baru akan muncul di sini otomatis. Yang sudah Selesai langsung hilang dan diteruskan ke kasir.</p>
    </div>

    <!-- Daftar kartu pesanan -->
    <div v-else class="dapur__grid">
      <article v-for="pesanan in activeOrders" :key="pesanan.id" class="lb-card dapur__card">
        <header class="dapur__card-head">
          <div>
            <p class="dapur__order-no">Pesanan #{{ String(pesanan.id).padStart(3, '0') }} · {{ pesanan.nama_pelanggan || 'Tamu' }}</p>
            <p class="dapur__order-meta">Meja {{ nomorMeja(pesanan) }} · {{ waktuLalu(pesanan.created_at) }}</p>
          </div>
          <StatusBadge :status="pesanan.status_pesanan" />
        </header>

        <ul class="dapur__items">
          <li v-for="detail in detailList(pesanan)" :key="detail.id" class="dapur__item">
            <img
              :src="getImageUrl(detail.menu?.gambar)"
              :alt="detail.menu?.nama_menu"
              class="dapur__thumb"
              loading="lazy"
              @error="$event.target.src = '/placeholder.png'"
            />
            <div class="dapur__item-text">
              <p class="dapur__item-name"><strong>{{ detail.jumlah }}×</strong> {{ detail.menu?.nama_menu }}</p>
              <p v-if="detail.catatan" class="dapur__item-note">Catatan: {{ detail.catatan }}</p>
            </div>
          </li>
        </ul>

        <p v-if="gagal[pesanan.id]" class="lb-alert lb-alert--error dapur__inline-err">{{ gagal[pesanan.id] }}</p>

        <footer class="dapur__foot">
          <label class="dapur__kelola">
            <span>Ubah status</span>
            <select
              class="lb-input dapur__select"
              :value="pesanan.status_pesanan"
              :disabled="busy[pesanan.id]"
              @change="ubahStatus(pesanan, $event.target.value)"
            >
              <option v-for="s in PILIHAN_STATUS" :key="s.id" :value="s.id">{{ s.label }}</option>
            </select>
          </label>
          <button
            v-if="STATUS_META[pesanan.status_pesanan]?.next"
            type="button"
            class="lb-btn-primary dapur__cta"
            :disabled="busy[pesanan.id]"
            @click="majukanStatus(pesanan)"
          >
            {{ busy[pesanan.id] ? 'Memproses...' : STATUS_META[pesanan.status_pesanan].nextLabel }}
          </button>
        </footer>
      </article>
    </div>
  </div>
</template>

<style scoped>
.dapur { padding-block: 0.5rem 1rem; }

.dapur__refresh svg { width: 1rem; height: 1rem; }

.dapur__toast { margin-bottom: 1rem; }

.dapur__stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.dapur__stat {
  padding: 1rem 1.1rem;
  border-top: 4px solid transparent;
}
.dapur__stat.is-baru { border-top-color: var(--lb-danger); }
.dapur__stat.is-proses { border-top-color: var(--lb-brand); }
.dapur__stat.is-siap { border-top-color: var(--lb-ok); }
.dapur__stat-label {
  margin: 0 0 0.25rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--lb-muted);
}
.dapur__stat-num {
  margin: 0;
  font-family: 'Fraunces', Georgia, serif;
  font-size: clamp(1.5rem, 5vw, 2rem);
  font-weight: 700;
}
.dapur__sk-line { height: 0.8rem; width: 60%; margin-bottom: 0.6rem; }
.dapur__sk-num { height: 2rem; width: 40%; }

.dapur__tabs {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  padding-bottom: 0.25rem;
  margin-bottom: 1rem;
}
.dapur__tab {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 1rem;
  border-radius: 999px;
  border: 1.5px solid var(--lb-line);
  background: #fff;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--lb-muted);
  cursor: pointer;
  white-space: nowrap;
}
.dapur__tab.is-active {
  background: var(--lb-ink);
  border-color: var(--lb-ink);
  color: #fff;
}
.dapur__tab-count {
  min-width: 1.5rem;
  height: 1.5rem;
  padding: 0 0.4rem;
  border-radius: 999px;
  display: inline-grid;
  place-items: center;
  font-size: 0.75rem;
  background: rgba(217, 119, 87, 0.15);
  color: inherit;
}
.dapur__tab.is-active .dapur__tab-count { background: var(--lb-brand); color: #fff; }

.dapur__loading {
  text-align: center;
  padding: 3rem 1.5rem;
  color: var(--lb-muted);
}
.dapur__loading p { margin: 0; }

.dapur__alert p { margin: 0 0 1rem; }
.dapur__retry { width: 100%; }
@media (min-width: 640px) { .dapur__retry { width: auto; } }

.dapur__empty-title { margin: 0 0 0.25rem; font-weight: 800; color: var(--lb-ink); }
.dapur__empty-sub { margin: 0; font-size: 0.88rem; }

.dapur__grid {
  display: grid;
  gap: 1rem;
}
@media (min-width: 1024px) { .dapur__grid { grid-template-columns: repeat(2, 1fr); } }

.dapur__card { overflow: hidden; display: flex; flex-direction: column; }
.dapur__card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1rem 1.15rem;
  background: #FFFBF0;
  border-bottom: 1px solid var(--lb-line);
}
.dapur__order-no {
  margin: 0;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--lb-brand-dark);
}
.dapur__order-meta { margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--lb-muted); }

.dapur__items {
  list-style: none;
  margin: 0;
  padding: 0.5rem 1.15rem;
  display: grid;
}
.dapur__item {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  padding-block: 0.7rem;
  border-bottom: 1px dashed var(--lb-line);
}
.dapur__item:last-child { border-bottom: none; }
.dapur__thumb {
  width: 3.25rem;
  height: 3.25rem;
  border-radius: 12px;
  object-fit: cover;
  background: var(--lb-soft);
  flex-shrink: 0;
}
.dapur__item-text { min-width: 0; }
.dapur__item-name { margin: 0; font-size: 0.92rem; }
.dapur__item-note {
  margin: 0.25rem 0 0;
  font-size: 0.8rem;
  color: var(--lb-muted);
  font-style: italic;
}

.dapur__inline-err { margin: 0 1.15rem 0.75rem; }

.dapur__foot { padding: 0 1.15rem 1.15rem; margin-top: auto; display: grid; gap: 0.6rem; }
.dapur__kelola { display: grid; gap: 0.35rem; font-size: 0.78rem; font-weight: 700; color: var(--lb-muted); }
.dapur__select { width: 100%; }
.dapur__cta { width: 100%; }
</style>
