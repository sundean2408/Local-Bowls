<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'

const route = useRoute()
const router = useRouter()
const idMeja = ref(route.query.meja || '')
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
    error.value = 'Meja tidak terdeteksi. Scan ulang QR code.'
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
  else router.push({ path: '/', query: { meja: idMeja.value } })
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
  <div class="min-h-screen" style="background-color: #FDF6EC; color: #3D2817;">
    
    <!-- Header -->
    <div class="sticky top-0 z-20 px-6 py-4" style="background-color: #FDF6EC; border-bottom: 1px solid rgba(61, 40, 23, 0.1);">
      <div class="flex items-center gap-4">
        <button 
          @click="kembali" 
          class="flex items-center justify-center w-10 h-10 rounded-full transition"
          style="background-color: rgba(217, 119, 87, 0.1); cursor: pointer; border: none;"
          aria-label="Kembali"
        >
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #3D2817;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div class="flex-1">
          <h1 class="text-2xl font-bold m-0" style="color: #3D2817;">Status Pesanan</h1>
          <p class="text-sm m-0" style="color: #8B6344;">Pesanan kamu sedang diproses</p>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="px-6 py-6 pb-32" style="max-width: 900px; margin: 0 auto;">
      
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-20">
        <div class="inline-block">
          <div class="w-12 h-12 border-4 rounded-full" style="border-color: rgba(217, 119, 87, 0.2); border-top-color: #D97757; animation: spin 1s linear infinite; margin-bottom: 16px;"></div>
          <p style="color: #8B6344;">Memuat status pesanan...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="rounded-2xl px-6 py-8 text-center" style="background-color: rgba(192, 42, 42, 0.08); border: 1px solid rgba(192, 42, 42, 0.2);">
        <div class="text-4xl mb-4">⚠️</div>
        <p class="mb-4" style="color: #c02a2a;">{{ error }}</p>
        <button 
          @click="loading = true; fetchStatus()" 
          class="px-6 py-3 rounded-full font-bold text-white transition"
          style="background-color: #D97757; border: none; cursor: pointer;"
        >
          Coba Lagi
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="pesananList.length === 0" class="text-center py-20 rounded-2xl" style="background-color: rgba(217, 119, 87, 0.05); border: 2px dashed rgba(217, 119, 87, 0.2);">
        <div class="text-5xl mb-4">📋</div>
        <p class="text-lg font-medium mb-4" style="color: #8B6344;">Belum ada pesanan aktif</p>
        <router-link 
          :to="{ path: '/menu', query: { meja: idMeja } }" 
          class="inline-block px-6 py-2 rounded-full font-bold transition"
          style="background-color: #D97757; color: white; text-decoration: none;"
        >
          Pesan Sekarang
        </router-link>
      </div>

      <!-- Orders List -->
      <div v-else class="space-y-8">
        <div v-for="pesanan in pesananList" :key="pesanan.id">
          
          <!-- Info Card -->
          <div class="rounded-2xl p-5" style="background-color: #F5E8DC; border: 1px solid rgba(61, 40, 23, 0.1);">
            <div class="grid grid-cols-2 gap-4">
              <!-- Meja Info -->
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(217, 119, 87, 0.15);">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #D97757;">
                    <rect x="2" y="6" width="20" height="12" rx="2" />
                    <path d="M6 9v2m12-2v2M9 14h6" />
                  </svg>
                </div>
                <div>
                  <p class="text-xs font-medium m-0" style="color: #8B6344;">Nomor Meja</p>
                  <p class="text-xl font-bold m-0" style="color: #3D2817;">{{ idMeja }}</p>
                </div>
              </div>

              <!-- Nama Pemesan Info -->
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(217, 119, 87, 0.15);">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #D97757;">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                  </svg>
                </div>
                <div>
                  <p class="text-xs font-medium m-0" style="color: #8B6344;">Nama Pemesan</p>
                  <p class="text-lg font-bold m-0" style="color: #3D2817;">{{ pesanan.nama_pelanggan || pesanan.customer_name || 'Tamu' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Stepper -->
          <div class="flex items-center justify-between gap-2 overflow-x-auto pb-4">
            <template v-for="(stage, i) in STAGES" :key="stage.key">
              <!-- Step Circle -->
              <div class="flex flex-col items-center flex-shrink-0" style="min-width: 80px;">
                <div
                  class="w-12 h-12 rounded-full flex items-center justify-center font-lg font-bold transition mb-2"
                  :style="{
                    backgroundColor: i < stageIndex(pesanan.status_pesanan) ? '#4F7D52' : 
                                    i === stageIndex(pesanan.status_pesanan) ? '#D97757' : '#E9E4DA',
                    color: i <= stageIndex(pesanan.status_pesanan) ? '#fff' : '#9C9284'
                  }"
                >
                  {{ stage.icon }}
                </div>
                <p class="text-xs font-medium text-center m-0" style="color: #3D2817; line-height: 1.2;">{{ stage.label }}</p>
                <p class="text-xs m-0 mt-1" style="color: #9C9284;">{{ formatJam(waktuStage(pesanan, i)) }}</p>
              </div>

              <!-- Connector Line -->
              <div 
                v-if="i < STAGES.length - 1"
                class="h-1 flex-1 rounded-full transition min-w-4 flex-shrink-0"
                :style="{
                  backgroundColor: i < stageIndex(pesanan.status_pesanan) ? '#4F7D52' : '#E9E4DA'
                }"
              ></div>
            </template>
          </div>

          <!-- Items Card -->
          <div class="rounded-2xl p-6" style="background-color: #FFF; border: 1px solid rgba(61, 40, 23, 0.08); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
            <h2 class="text-lg font-bold mb-4 m-0" style="color: #3D2817;">Pesanan Kamu</h2>

            <!-- Items List -->
            <div class="space-y-3">
              <div 
                v-for="detail in pesanan.detail_pesanan" 
                :key="detail.id"
                class="flex gap-4 pb-4 border-b"
                style="border-color: rgba(61, 40, 23, 0.08);"
              >
                <!-- Image -->
                <div class="w-20 h-20 rounded-lg overflow-hidden flex-shrink-0" style="background-color: #f2e9db;">
                  <img
                    :src="getImagePaths(detail.menu?.nama_menu)[0]"
                    :alt="detail.menu?.nama_menu"
                    data-stage="0"
                    class="w-full h-full object-cover"
                    @error="handleImgError($event, detail.menu?.nama_menu)"
                    style="display: block;"
                  />
                </div>

                <!-- Info -->
                <div class="flex-1 flex flex-col justify-between">
                  <div>
                    <h3 class="font-bold m-0 mb-1" style="color: #3D2817;">{{ detail.menu?.nama_menu }}</h3>
                    <p class="text-sm m-0 mb-1" style="color: #D97757; font-weight: 600;">Rp {{ Number(detail.menu?.harga ?? (detail.subtotal / detail.jumlah)).toLocaleString('id-ID') }}</p>
                    <p v-if="detail.catatan" class="text-xs m-0" style="color: #8B6344; font-style: italic;">📝 {{ detail.catatan }}</p>
                  </div>
                  <div class="flex items-center gap-2 text-sm">
                    <span class="px-3 py-1 rounded-full" style="background-color: #F3EFE7; color: #3D2817; font-weight: 600;">{{ detail.jumlah }}×</span>
                    <span style="color: #6B4A34;">Rp {{ Number(detail.subtotal).toLocaleString('id-ID') }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Total -->
            <div class="mt-6 pt-4 border-t-2 border-dashed flex items-center justify-between" style="border-color: rgba(61, 40, 23, 0.15);">
              <span class="font-bold" style="color: #6B4A34;">Total Pesanan</span>
              <span class="text-2xl font-bold" style="color: #D97757;">Rp {{ Number(pesanan.total_harga).toLocaleString('id-ID') }}</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

button {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

button:hover:not(:disabled) {
  opacity: 0.9;
}

button:active:not(:disabled) {
  transform: scale(0.98);
}

::-webkit-scrollbar {
  height: 4px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: rgba(217, 119, 87, 0.3);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(217, 119, 87, 0.5);
}
</style>