<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../store/auth.js'
import api, { getImageUrl } from '../../services/api'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { getAppTimestamp } from '@/utils/dateTime'

const router = useRouter()
const authStore = useAuthStore()

const orders = ref([])
const loading = ref(true)
const error = ref(null)
const activeTab = ref('semua')
const query = ref('')
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
  const q = query.value.trim().toLowerCase()
  const list = activeTab.value === 'semua' ? ordersAktif() : ordersByStatus(activeTab.value)
  const disaring = !q ? list : list.filter((o) =>
    String(o.id).includes(q) ||
    (o.nama_pelanggan || '').toLowerCase().includes(q) ||
    String(nomorMeja(o)).toLowerCase().includes(q) ||
    detailList(o).some((d) => (d.menu?.nama_menu || '').toLowerCase().includes(q))
  )
  return [...disaring].sort((a, b) =>
    (getAppTimestamp(a.created_at) ?? Number.POSITIVE_INFINITY)
    - (getAppTimestamp(b.created_at) ?? Number.POSITIVE_INFINITY)
  )
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
  const timestamp = getAppTimestamp(dateStr)
  if (timestamp === null) return '-'
  const menit = Math.max(0, Math.floor((Date.now() - timestamp) / 60000))
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
    <div v-if="loading" class="dapur__stats" aria-hidden="true">
      <div v-for="i in 4" :key="i" class="lb-card dapur__stat">
        <div class="lb-skeleton dapur__sk-line"></div>
        <div class="lb-skeleton dapur__sk-num"></div>
      </div>
    </div>
    <div v-else class="dapur__stats">
      <div class="lb-card dapur__stat is-total">
        <p class="dapur__stat-label">Total Pesanan</p>
        <p class="dapur__stat-num">{{ totalAktif }}</p>
      </div>
      <div class="lb-card dapur__stat is-baru">
        <p class="dapur__stat-label">Pending</p>
        <p class="dapur__stat-num">{{ baruCount }}</p>
      </div>
      <div class="lb-card dapur__stat is-proses">
        <p class="dapur__stat-label">Diproses</p>
        <p class="dapur__stat-num">{{ diprosesCount }}</p>
      </div>
      <div class="lb-card dapur__stat is-siap">
        <p class="dapur__stat-label">Siap / Selesai</p>
        <p class="dapur__stat-num">{{ selesaiHariIni }}</p>
      </div>
    </div>

    <!-- Cari -->
    <div class="dapur__search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 21l-4.34-4.34M19 11a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
      <input v-model="query" type="search" placeholder="Cari ID / nama / meja / menu" aria-label="Cari pesanan" class="dapur__search-input" />
      <button v-if="query" type="button" @click="query = ''" class="dapur__search-clear" aria-label="Bersihkan pencarian">×</button>
    </div>

    <!-- Tab status -->
    <div class="dapur__tabs" role="tablist" aria-label="Filter status pesanan">
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
      <span class="dapur__empty-icon" aria-hidden="true">
        <svg viewBox="0 0 48 48" fill="none"><path d="M12 16h24l3 25H9l3-25Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M18 18v-4a6 6 0 0 1 12 0v4M17 26h14M17 32h9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
      </span>
      <p class="dapur__empty-title">{{ query ? 'Pesanan tidak ditemukan' : `Tidak ada pesanan ${activeTab === 'semua' ? 'aktif' : TABS.find(t => t.id === activeTab)?.label.toLowerCase()}` }}</p>
      <p class="dapur__empty-sub">{{ query ? 'Coba kata kunci lain atau bersihkan pencarian.' : 'Pesanan baru akan muncul di sini otomatis. Pesanan yang selesai diteruskan ke kasir.' }}</p>
      <button v-if="query" type="button" class="lb-btn-ghost dapur__empty-action" @click="query = ''">Bersihkan pencarian</button>
    </div>

    <!-- Daftar kartu pesanan -->
    <div v-else class="dapur__grid">
      <article v-for="pesanan in activeOrders" :key="pesanan.id" class="lb-card dapur__card" :class="`is-${pesanan.status_pesanan}`">
        <header class="dapur__card-head">
          <div>
            <p class="dapur__order-no">Pesanan #{{ String(pesanan.id).padStart(3, '0') }}</p>
            <p class="dapur__customer">{{ pesanan.nama_pelanggan || 'Tamu' }}</p>
            <p class="dapur__order-meta"><span class="dapur__table-tag">Meja {{ nomorMeja(pesanan) }}</span><span>{{ waktuLalu(pesanan.created_at) }}</span></p>
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
              @error="$event.target.src = '/logo.png'"
            />
            <div class="dapur__item-text">
              <div class="dapur__item-detail">
                <p class="dapur__item-name">{{ detail.menu?.nama_menu || 'Menu' }}</p>
                <p v-if="detail.catatan" class="dapur__item-note">Catatan: {{ detail.catatan }}</p>
              </div>
              <span class="dapur__qty" :aria-label="`${detail.jumlah} porsi`">{{ detail.jumlah }}×</span>
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
            :aria-busy="!!busy[pesanan.id]"
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
.dapur { max-width: 96rem; padding-block: 0.5rem 1.5rem; }
.dapur__refresh { display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem; min-height: 2.65rem; }
.dapur__refresh svg { width: 1rem; height: 1rem; }
.dapur__toast { margin-bottom: 1rem; }
.dapur__stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
.dapur__stat { position: relative; overflow: hidden; min-width: 0; padding: 1rem 1.1rem; border: 1px solid rgba(70, 46, 24, 0.08); border-radius: 16px; background: linear-gradient(180deg, #fff 0%, #fffaf5 100%); box-shadow: 0 8px 22px rgba(64, 45, 30, 0.045); }
.dapur__stat::before { position: absolute; inset: 0 0 auto; height: 3px; background: var(--lb-ink); content: ""; }
.dapur__stat.is-baru::before { background: #d44c3e; }
.dapur__stat.is-proses::before { background: #d48229; }
.dapur__stat.is-siap::before { background: #21845c; }
.dapur__stat-label { margin: 0 0 0.25rem; color: var(--lb-muted); font-size: 0.68rem; font-weight: 800; letter-spacing: 0.075em; text-transform: uppercase; }
.dapur__stat-num { margin: 0; color: var(--lb-ink); font-family: 'Fraunces', Georgia, serif; font-size: clamp(1.55rem, 5vw, 2.1rem); font-weight: 700; line-height: 1.15; font-variant-numeric: tabular-nums; }
.dapur__sk-line { width: 60%; height: 0.8rem; margin-bottom: 0.6rem; }
.dapur__sk-num { width: 40%; height: 2rem; }
.dapur__search { display: flex; align-items: center; gap: 0.65rem; min-height: 3.1rem; margin-bottom: 0.9rem; padding: 0.55rem 0.9rem; border: 1px solid var(--lb-line); border-radius: 14px; background: #fff; box-shadow: 0 4px 14px rgba(64, 45, 30, 0.035); transition: border-color 0.18s ease, box-shadow 0.18s ease; }
.dapur__search:focus-within { border-color: var(--lb-brand); box-shadow: 0 0 0 3px rgba(212, 130, 41, 0.14); }
.dapur__search svg { width: 1.05rem; height: 1.05rem; flex-shrink: 0; color: var(--lb-muted); }
.dapur__search-input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--lb-ink); font-size: 0.9rem; }
.dapur__search-input::placeholder { color: var(--lb-faint); }
.dapur__search-clear { display: grid; place-items: center; width: 1.8rem; height: 1.8rem; border: 0; border-radius: 999px; background: var(--lb-soft); color: var(--lb-muted); cursor: pointer; font-size: 1.15rem; line-height: 1; }
.dapur__tabs { display: flex; gap: 0.5rem; overflow-x: auto; margin-bottom: 1rem; padding-bottom: 0.25rem; scrollbar-width: thin; }
.dapur__tab { display: inline-flex; align-items: center; gap: 0.55rem; padding: 0.55rem 0.85rem; border: 1px solid var(--lb-line); border-radius: 12px; background: #fff; color: var(--lb-muted); cursor: pointer; font-size: 0.82rem; font-weight: 750; white-space: nowrap; transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease, transform 0.18s ease; }
.dapur__tab:hover { transform: translateY(-1px); border-color: rgba(92, 63, 41, 0.28); }
.dapur__tab.is-active { border-color: var(--lb-ink); background: var(--lb-ink); color: #fff; box-shadow: 0 5px 12px rgba(38, 29, 22, 0.13); }
.dapur__tab-count { display: inline-grid; place-items: center; min-width: 1.45rem; height: 1.45rem; padding-inline: 0.35rem; border-radius: 999px; background: #f2ebe3; color: var(--lb-ink); font-size: 0.7rem; font-variant-numeric: tabular-nums; }
.dapur__tab.is-active .dapur__tab-count { background: var(--lb-brand); color: #fff; }
.dapur__loading { padding: 3rem 1.5rem; color: var(--lb-muted); text-align: center; }
.dapur__loading p { margin: 0.8rem 0 0; }
.dapur__alert p { margin: 0 0 1rem; }
.dapur__retry { width: 100%; }
.dapur__empty-title { margin: 0 0 0.35rem; color: var(--lb-ink); font-size: 1rem; font-weight: 850; }
.dapur__empty-sub { max-width: 34rem; margin: 0 auto; font-size: 0.88rem; line-height: 1.6; }
.dapur__empty-icon { display: grid; place-items: center; width: 3.8rem; height: 3.8rem; margin: 0 auto 0.75rem; border-radius: 18px; background: #f5eadb; color: var(--lb-brand-dark); }
.dapur__empty-icon svg { width: 2rem; height: 2rem; }
.dapur__empty-action { margin-top: 0.9rem; }
.dapur__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(22rem, 100%), 1fr)); gap: 1rem; align-items: stretch; }
.dapur__card { position: relative; display: flex; overflow: hidden; flex-direction: column; min-width: 0; border: 1px solid rgba(70, 46, 24, 0.1); border-radius: 18px; box-shadow: 0 10px 28px rgba(64, 45, 30, 0.055); transition: transform 0.2s ease, box-shadow 0.2s ease; }
.dapur__card:hover { transform: translateY(-2px); box-shadow: 0 15px 34px rgba(64, 45, 30, 0.09); }
.dapur__card::before { position: absolute; inset: 0 0 auto; z-index: 1; height: 3px; background: #d44c3e; content: ""; }
.dapur__card.is-diproses::before { background: #d48229; }
.dapur__card-head { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--lb-line); background: linear-gradient(135deg, #fffaf1 0%, #fff 100%); }
.dapur__order-no { margin: 0; color: var(--lb-brand-dark); font-size: 0.67rem; font-weight: 900; letter-spacing: 0.1em; text-transform: uppercase; }
.dapur__customer { margin: 0.16rem 0 0; color: var(--lb-ink); font-size: 1.05rem; font-weight: 850; }
.dapur__order-meta { display: flex; align-items: center; gap: 0.5rem; margin: 0.4rem 0 0; color: var(--lb-muted); font-size: 0.75rem; }
.dapur__table-tag { padding: 0.2rem 0.45rem; border-radius: 6px; background: #f2ebe3; color: var(--lb-ink); font-size: 0.68rem; font-weight: 800; }
.dapur__items { display: grid; list-style: none; gap: 0; margin: 0; padding: 0.35rem 1.1rem; }
.dapur__item { display: flex; align-items: center; gap: 0.75rem; min-width: 0; padding-block: 0.75rem; border-bottom: 1px dashed var(--lb-line); }
.dapur__item:last-child { border-bottom: 0; }
.dapur__thumb { width: 3.25rem; height: 3.25rem; flex: 0 0 auto; border: 1px solid rgba(70, 46, 24, 0.08); border-radius: 12px; object-fit: cover; background: var(--lb-soft); }
.dapur__item-text { display: flex; flex: 1; align-items: flex-start; justify-content: space-between; gap: 0.6rem; min-width: 0; }
.dapur__item-detail { min-width: 0; }
.dapur__item-name { margin: 0; color: var(--lb-ink); font-size: 0.9rem; font-weight: 750; overflow-wrap: anywhere; }
.dapur__item-note { margin: 0.25rem 0 0; color: var(--lb-muted); font-size: 0.77rem; font-style: italic; line-height: 1.45; overflow-wrap: anywhere; }
.dapur__qty { flex: 0 0 auto; padding: 0.3rem 0.48rem; border: 1px solid #efdfcb; border-radius: 8px; background: #fff8eb; color: var(--lb-brand-dark); font-size: 0.76rem; font-weight: 850; font-variant-numeric: tabular-nums; }
.dapur__inline-err { margin: 0 1.1rem 0.75rem; }
.dapur__foot { display: grid; gap: 0.65rem; margin-top: auto; padding: 0.9rem 1.1rem 1.1rem; border-top: 1px solid rgba(70, 46, 24, 0.07); background: #fffdfa; }
.dapur__kelola { display: grid; gap: 0.4rem; color: var(--lb-muted); font-size: 0.72rem; font-weight: 800; }
.dapur__select { width: 100%; min-height: 2.8rem; border-radius: 10px; color: var(--lb-ink); }
.dapur__cta { width: 100%; min-height: 3rem; border-radius: 11px; font-weight: 850; box-shadow: 0 6px 14px rgba(92, 63, 41, 0.14); }
.dapur__cta:disabled { cursor: wait; opacity: 0.65; }
.dapur :is(button, input, select):focus-visible { outline: 3px solid rgba(212, 130, 41, 0.45); outline-offset: 2px; }
@media (min-width: 640px) {
  .dapur__stats { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.85rem; }
  .dapur__retry { width: auto; }
  .dapur__grid { gap: 1.15rem; }
}
@media (prefers-reduced-motion: reduce) {
  .dapur *,
  .dapur *::before,
  .dapur *::after { scroll-behavior: auto !important; transition-duration: 0.01ms !important; }
}
</style>
