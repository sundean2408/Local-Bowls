<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'

const route = useRoute()
const router = useRouter()
// Meja selalu dipilih manual di Konfirmasi; Status baca dari query hasil
// submit atau sesi terakhir, tanpa preload/paksa nilai apa pun.
const idMeja = ref(route.query.meja || localStorage.getItem('lastMejaId') || '')
const pesananList = ref([])
const loading = ref(true)
const error = ref(null)
let intervalId = null

const STAGES = [
  { key: 'baru', label: 'Dipesan', icon: '✓' },
  { key: 'diproses', label: 'Di proses', icon: '👨‍🍳' },
  { key: 'selesai', label: 'Siap diambil', icon: '🍽️' },
  { key: 'selesai_makan', label: 'Selesai makan', icon: '🍴' },
]

function stageIndex(status) {
  const i = STAGES.findIndex((s) => s.key === status)
  return i === -1 ? 0 : i
}

function formatJam(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return '-'
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

function waktuStage(pesanan, stageKeyIndex) {
  const current = stageIndex(pesanan.status_pesanan)
  if (stageKeyIndex > current) return null
  if (stageKeyIndex === 0) return pesanan.created_at
  if (stageKeyIndex === current) return pesanan.updated_at
  return pesanan.updated_at
}

// Generate path dengan multiple case variations
function getImagePaths(nama) {
  if (!nama) return []
  const withDash = nama.replace(/\s+/g, '-')
  return [
    `/${withDash}.jpg`,           // "Mie-Aceh.jpg"
    `/${withDash.toLowerCase()}.jpg`, // "mie-aceh.jpg"
  ]
}

// Handle image error dengan multiple fallback
function handleImgError(event, menuName) {
  const img = event.target
  const currentStage = Number(img.dataset.stage || 0)
  const paths = getImagePaths(menuName)
  
  if (currentStage === 0) {
    // Stage 0: Coba path pertama (original case)
    img.dataset.stage = '1'
    img.src = paths[0]
    return
  }
  
  if (currentStage === 1) {
    // Stage 1: Coba path kedua (lowercase)
    img.dataset.stage = '2'
    img.src = paths[1]
    return
  }
  
  if (currentStage === 2) {
    // Stage 2: Fallback ke stock image
    img.dataset.stage = '3'
    img.src = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=100&h=100&fit=crop'
  } else {
    img.src = 'https://via.placeholder.com/80x80?text=Food'
  }
}

async function fetchStatus() {
  if (!idMeja.value) {
    error.value = 'Belum ada meja terdeteksi. Buat pesanan dulu lewat Konfirmasi.'
    loading.value = false
    return
  }
  try {
    const res = await api.get(`/meja/${idMeja.value}/pesanan`)
    const semua = Array.isArray(res) ? res : (res.data ?? res)
    pesananList.value = Array.isArray(semua) ? semua : []
    error.value = null
  } catch (e) {
    error.value = 'Gagal memuat status pesanan. Coba lagi.'
    console.error('Gagal fetch status pesanan:', e)
  } finally {
    loading.value = false
  }
}

function kembali() {
  if (window.history.length > 1) router.back()
  else router.push('/')
}

onMounted(() => {
  fetchStatus()
  intervalId = setInterval(fetchStatus, 5000)
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})
</script>

<template>
  <div class="stat-page">
    <!-- Header -->
    <div class="stat-header">
      <div class="stat-header__inner">
        <button @click="kembali" class="stat-back" aria-label="Kembali">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
        </button>
        <div>
          <h1 class="stat-header__title">Status Pesanan</h1>
          <p class="stat-header__sub">Pesanan kamu sedang diproses</p>
        </div>
      </div>
    </div>

    <div class="stat-body">
      <!-- Loading -->
      <div v-if="loading" class="stat-loading">
        <div class="lb-spinner"></div>
        <p>Memuat status pesanan...</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="lb-alert lb-alert--error stat-center">
        <p>{{ error }}</p>
        <button @click="loading = true; fetchStatus()" class="lb-btn-primary">Coba Lagi</button>
      </div>

      <!-- Empty -->
      <div v-else-if="pesananList.length === 0" class="lb-empty">
        <p class="stat-empty-title">Belum ada pesanan aktif</p>
        <router-link to="/menu" class="lb-btn-primary">Pesan Sekarang</router-link>
      </div>

      <!-- Orders -->
      <div v-else class="stat-orders">
        <div v-for="pesanan in pesananList" :key="pesanan.id" class="stat-order">

          <!-- Info card -->
          <div class="stat-info">
            <div class="stat-info__item">
              <div class="stat-info__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 9v2m12-2v2M9 14h6"/></svg>
              </div>
              <div><p class="stat-info__label">Nomor Meja</p><p class="stat-info__val">{{ idMeja }}</p></div>
            </div>
            <div class="stat-info__item">
              <div class="stat-info__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
              <div><p class="stat-info__label">Nama Pemesan</p><p class="stat-info__val">{{ pesanan.nama_pelanggan || 'Tamu' }}</p></div>
            </div>
          </div>

          <!-- Stepper -->
          <div class="stat-stepper">
            <template v-for="(stage, i) in STAGES" :key="stage.key">
              <div class="stat-step">
                <div class="stat-step__circle" :class="{ 'is-done': i < stageIndex(pesanan.status_pesanan), 'is-active': i === stageIndex(pesanan.status_pesanan) }">
                  {{ stage.icon }}
                </div>
                <p class="stat-step__label">{{ stage.label }}</p>
                <p class="stat-step__time">{{ formatJam(waktuStage(pesanan, i)) }}</p>
              </div>
              <div v-if="i < STAGES.length - 1" class="stat-connector" :class="{ 'is-done': i < stageIndex(pesanan.status_pesanan) }"></div>
            </template>
          </div>

          <!-- Items -->
          <div class="lb-card stat-items">
            <h2 class="stat-items__title">Pesanan Kamu</h2>
            <div class="stat-item-list">
              <div v-for="detail in pesanan.detail_pesanan" :key="detail.id" class="stat-item">
                <div class="stat-item__img">
                  <img :src="getImagePaths(detail.menu?.nama_menu)[0]" :alt="detail.menu?.nama_menu" data-stage="0" @error="handleImgError($event, detail.menu?.nama_menu)" />
                </div>
                <div class="stat-item__info">
                  <h3>{{ detail.menu?.nama_menu }}</h3>
                  <p class="stat-item__price">Rp {{ Number(detail.menu?.harga ?? (detail.subtotal / detail.jumlah)).toLocaleString('id-ID') }}</p>
                  <p v-if="detail.catatan" class="stat-item__note">📝 {{ detail.catatan }}</p>
                  <div class="stat-item__qty">
                    <span class="stat-qty-badge">{{ detail.jumlah }}×</span>
                    <span class="stat-item__sub">Rp {{ Number(detail.subtotal).toLocaleString('id-ID') }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="stat-total">
              <span>Total Pesanan</span>
              <strong>Rp {{ Number(pesanan.total_harga).toLocaleString('id-ID') }}</strong>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stat-page { min-height: 100vh; background: var(--lb-bg); color: var(--lb-ink); }

.stat-header { position: sticky; top: 0; z-index: 20; background: var(--lb-bg); border-bottom: 1px solid var(--lb-line); padding: 1rem 1.5rem; }
.stat-header__inner { display: flex; align-items: center; gap: 1rem; max-width: 56rem; margin: 0 auto; }
.stat-back { width: 2.5rem; height: 2.5rem; border-radius: 50%; border: none; background: rgba(217,119,87,0.1); cursor: pointer; display: grid; place-items: center; flex-shrink: 0; }
.stat-back svg { width: 1.25rem; height: 1.25rem; color: var(--lb-ink); }
.stat-header__title { margin: 0; font-size: 1.3rem; font-weight: 800; }
.stat-header__sub { margin: 0; font-size: 0.82rem; color: var(--lb-muted); }

.stat-body { max-width: 56rem; margin: 0 auto; padding: 1.5rem 1.5rem 6rem; }
.stat-loading { text-align: center; padding: 4rem 0; color: var(--lb-muted); }
.stat-loading p { margin: 0; }
.stat-center { text-align: center; display: grid; gap: 1rem; }
.stat-empty-title { margin: 0 0 1rem; font-weight: 800; font-size: 1.1rem; }

.stat-orders { display: grid; gap: 2rem; }
.stat-order { display: grid; gap: 1rem; }

/* Info card */
.stat-info { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: var(--lb-soft); border-radius: 18px; padding: 1.25rem; }
.stat-info__item { display: flex; align-items: center; gap: 0.75rem; }
.stat-info__icon { width: 3rem; height: 3rem; border-radius: 50%; background: rgba(217,119,87,0.15); display: grid; place-items: center; flex-shrink: 0; }
.stat-info__icon svg { width: 1.25rem; height: 1.25rem; color: var(--lb-brand); }
.stat-info__label { margin: 0; font-size: 0.72rem; font-weight: 600; color: var(--lb-muted); }
.stat-info__val { margin: 0; font-size: 1.1rem; font-weight: 800; }

/* Stepper */
.stat-stepper { display: flex; align-items: center; overflow-x: auto; padding-bottom: 0.5rem; gap: 0; }
.stat-step { display: flex; flex-direction: column; align-items: center; min-width: 5rem; flex-shrink: 0; }
.stat-step__circle { width: 3rem; height: 3rem; border-radius: 50%; background: #E9E4DA; color: #9C9284; display: grid; place-items: center; font-size: 1.1rem; font-weight: 800; margin-bottom: 0.4rem; transition: background 0.2s; }
.stat-step__circle.is-done { background: var(--lb-ok); color: #fff; }
.stat-step__circle.is-active { background: var(--lb-brand); color: #fff; }
.stat-step__label { margin: 0; font-size: 0.72rem; font-weight: 600; text-align: center; line-height: 1.2; }
.stat-step__time { margin: 0.15rem 0 0; font-size: 0.68rem; color: var(--lb-faint); }
.stat-connector { height: 0.25rem; flex: 1; min-width: 1rem; border-radius: 999px; background: #E9E4DA; transition: background 0.2s; flex-shrink: 0; }
.stat-connector.is-done { background: var(--lb-ok); }

/* Items */
.stat-items { padding: 1.25rem; }
.stat-items__title { margin: 0 0 1rem; font-size: 1.05rem; font-weight: 800; }
.stat-item-list { display: grid; gap: 0; }
.stat-item { display: flex; gap: 1rem; padding-block: 0.85rem; border-bottom: 1px dashed var(--lb-line); }
.stat-item:last-child { border-bottom: none; }
.stat-item__img { width: 5rem; height: 5rem; border-radius: 12px; overflow: hidden; flex-shrink: 0; background: var(--lb-soft); }
.stat-item__img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.stat-item__info { flex: 1; display: flex; flex-direction: column; gap: 0.2rem; }
.stat-item__info h3 { margin: 0; font-size: 0.95rem; font-weight: 800; }
.stat-item__price { margin: 0; font-size: 0.85rem; color: var(--lb-brand); font-weight: 700; }
.stat-item__note { margin: 0; font-size: 0.78rem; color: var(--lb-muted); font-style: italic; }
.stat-item__qty { display: flex; align-items: center; gap: 0.5rem; margin-top: auto; }
.stat-qty-badge { background: var(--lb-soft); color: var(--lb-ink); font-weight: 700; font-size: 0.8rem; padding: 0.2rem 0.6rem; border-radius: 999px; }
.stat-item__sub { font-size: 0.85rem; color: var(--lb-muted); }
.stat-total { display: flex; align-items: baseline; justify-content: space-between; margin-top: 1rem; padding-top: 0.85rem; border-top: 2px dashed var(--lb-line); }
.stat-total span { font-weight: 700; color: var(--lb-muted); }
.stat-total strong { font-family: 'Fraunces', Georgia, serif; font-size: 1.4rem; color: var(--lb-brand); }
</style>