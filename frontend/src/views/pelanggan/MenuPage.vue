<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { storageUrl } from '@/services/api'
import { useCart } from '@/store/cart'

const route = useRoute()
const router = useRouter()
const { addItem, totalQty, totalHarga } = useCart()

// State Data
const kategori = ref([])
const menu = ref([])
const kategoriAktif = ref('semua')
const loading = ref(true)
const error = ref(null)
const idMeja = ref(route.query.meja || '')

// Filter Logic
const menuFiltered = computed(() => {
  if (kategoriAktif.value === 'semua') return menu.value
  return menu.value.filter(m => m.id_kategori === kategoriAktif.value)
})

// UI State
const filterOpen = ref(false)
const namaKategoriAktif = computed(() => {
  if (kategoriAktif.value === 'semua') return 'Semua Kategori'
  const found = kategori.value.find(k => k.id === kategoriAktif.value)
  return found ? found.nama_kategori : 'Semua'
})

function toggleFilter() { filterOpen.value = !filterOpen.value }
function pilihKategori(id) {
  kategoriAktif.value = id
  filterOpen.value = false
}

// Helper: normalisasi response fetch-based api.js
// (bisa array langsung ATAU dibungkus { data: [...] } tergantung controller Laravel)
function ambilArray(res) {
  const semua = Array.isArray(res) ? res : (res?.data ?? res)
  return Array.isArray(semua) ? semua : []
}

// Fetch Data
async function fetchData() {
  loading.value = true
  error.value = null
  try {
    const [resKategori, resMenu] = await Promise.all([
      api.get('/kategori'),
      api.get('/menu'),
    ])
    kategori.value = ambilArray(resKategori)
    menu.value = ambilArray(resMenu)
  } catch (e) {
    error.value = 'Gagal memuat menu. Pastikan backend Laravel sedang berjalan.'
    console.error('Gagal fetch menu/kategori:', e)
  } finally {
    loading.value = false
  }
}

// Modal Logic
const modalItem = ref(null)
const modalQty = ref(1)
const modalCatatan = ref('')

function bukaModal(item) {
  modalItem.value = item
  modalQty.value = 1
  modalCatatan.value = ''
}

function tutupModal() { modalItem.value = null }

function konfirmasiTambah() {
  addItem({
    id: modalItem.value.id,
    nama_menu: modalItem.value.nama_menu,
    harga: modalItem.value.harga,
    gambar: modalItem.value.gambar,
    qty: modalQty.value,
    catatan: modalCatatan.value,
  })
  tutupModal()
}

function goCheckout() {
  router.push({ path: '/konfirmasi', query: { meja: idMeja.value } })
}

// ===== Helper Gambar =====
// Prioritas sumber foto menu:
//   1. File lokal di /public, dicocokkan dari nama menu (mis. "Mie Aceh" -> /Mie-Aceh.jpg)
//   2. Versi huruf kecil semua (mis. /mie-aceh.jpg) -- buat foto yang disimpan lowercase (es-dawet.jpg, dll)
//   3. Foto dari backend (field `gambar`, disimpan di storage Laravel), kalau ada
//   4. Foto stock generik dari Unsplash sebagai jaring pengaman terakhir

const getFallbackImg = (index) => {
  const fallbacks = [
    'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?q=80&w=500',
    'https://images.unsplash.com/photo-1552611052-33e04de081de?q=80&w=500',
    'https://images.unsplash.com/photo-1617093727343-374698b1b08d?q=80&w=500',
    'https://images.unsplash.com/photo-1555126634-323283e090fa?q=80&w=500'
  ]
  return fallbacks[index % fallbacks.length]
}

// Bikin nama file dari nama menu: "Mie Celor Palembang" -> "Mie-Celor-Palembang"
function slugFromNama(nama) {
  return (nama || '').trim().replace(/\s+/g, '-')
}

function localImgSrc(nama) {
  return `/${slugFromNama(nama)}.jpg`
}

const getImg = (g, index = 0) => {
  if (!g) return getFallbackImg(index)
  const cleanPath = g.replace(/\\/g, '/')
  return `${storageUrl}/${cleanPath}`
}

// Dipanggil saat foto lokal gagal dimuat (<img @error>). Jalan berurutan
// lewat tahap-tahap fallback di atas, dilacak lewat data-stage di elemen img.
function handleImgError(event, item, index = 0) {
  const img = event.target
  const stage = Number(img.dataset.stage || 0)

  if (stage === 0) {
    // Coba versi huruf kecil semua
    img.dataset.stage = '1'
    img.src = localImgSrc(item.nama_menu).toLowerCase()
  } else if (stage === 1 && item.gambar) {
    // Coba foto dari backend kalau field gambar ada isinya
    img.dataset.stage = '2'
    img.src = getImg(item.gambar, index)
  } else {
    // Jaring pengaman terakhir
    img.src = getFallbackImg(index)
  }
}

const fmt = (h) => new Intl.NumberFormat('id-ID').format(h)

onMounted(fetchData)
</script>

<template>
  <div class="min-h-screen pb-28 animate-fade-in" style="background-color: #FDF6EC; color: #3D2817;">

    <!-- Header Section -->
    <div class="text-center py-12 px-4 relative overflow-hidden">
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full blur-3xl -z-10" style="background: rgba(217, 119, 87, 0.08);"></div>

      <p class="text-xs tracking-[4px] font-bold uppercase mb-2" style="color: #D97757;">Pilihan Terbaik</p>
      <h2 class="text-4xl sm:text-5xl mb-3 font-serif font-bold" style="color: #3D2817;">Daftar Menu</h2>
      <p class="text-sm max-w-md mx-auto" style="color: #6B4A34;">Pilih menu favoritmu dan pesan langsung dari meja.</p>
    </div>

    <!-- Filter Dropdown (Mobile Friendly) -->
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-8 mb-8">
      <div class="relative w-full sm:max-w-xs mx-auto sm:mx-0">
        <button
          @click="toggleFilter"
          class="w-full flex items-center justify-between gap-3 px-5 py-3 rounded-xl text-sm font-medium border transition shadow-sm"
          style="background-color: #FFF; color: #3D2817; border-color: rgba(61, 40, 23, 0.15); min-height: 44px;"
          @mouseover="$event.target.style.borderColor = '#D97757'"
          @mouseout="$event.target.style.borderColor = 'rgba(61, 40, 23, 0.15)'"
        >
          <span class="truncate">{{ namaKategoriAktif }}</span>
          <svg class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': filterOpen }" viewBox="0 0 20 20" fill="none" :style="{ color: '#3D2817' }">
            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>

        <transition name="dropdown">
          <div v-if="filterOpen" class="absolute left-0 right-0 mt-2 rounded-xl border shadow-lg overflow-hidden z-30 max-h-60 overflow-y-auto custom-scrollbar" style="background-color: #FFF; border-color: rgba(61, 40, 23, 0.15);">
            <button
              @click="pilihKategori('semua')"
              :class="['w-full text-left px-5 py-3 text-sm transition border-b', kategoriAktif === 'semua' ? 'font-bold' : '']"
              :style="{
                backgroundColor: kategoriAktif === 'semua' ? 'rgba(217, 119, 87, 0.1)' : '#FFF',
                color: kategoriAktif === 'semua' ? '#D97757' : '#3D2817',
                borderColor: 'rgba(61, 40, 23, 0.08)'
              }"
            >
              Semua Kategori
            </button>
            <button
              v-for="k in kategori"
              :key="k.id"
              @click="pilihKategori(k.id)"
              :class="['w-full text-left px-5 py-3 text-sm transition', kategoriAktif === k.id ? 'font-bold' : '']"
              :style="{
                backgroundColor: kategoriAktif === k.id ? 'rgba(217, 119, 87, 0.1)' : '#FFF',
                color: kategoriAktif === k.id ? '#D97757' : '#3D2817'
              }"
            >
              {{ k.nama_kategori }}
            </button>
          </div>
        </transition>
      </div>
    </div>

    <!-- Loading & Error States -->
    <div v-if="loading" class="text-center py-20 animate-pulse" style="color: #D97757;">Memuat menu lezat...</div>
    <div v-else-if="error" class="text-center py-16 rounded-2xl mx-4 border flex flex-col items-center gap-4" style="color: #c02a2a; background-color: rgba(192, 42, 42, 0.1); border-color: rgba(192, 42, 42, 0.2);">
      <span>{{ error }}</span>
      <button @click="fetchData" class="font-bold px-5 py-2.5 rounded-full text-white text-sm" style="background-color: #c02a2a; min-height: 40px;">Coba Lagi</button>
    </div>

    <!-- Menu Grid -->
    <div v-else class="w-full max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 px-4 sm:px-8 pb-16">
      <div
        v-for="(item, index) in menuFiltered"
        :key="item.id"
        class="group cursor-pointer flex flex-col h-full rounded-2xl overflow-hidden transition-all duration-300"
        :style="{
          backgroundColor: '#FFF',
          borderRadius: index % 4 === 1 || index % 4 === 3 ? '12px 28px 12px 28px' : '28px 12px 28px 12px',
          boxShadow: '0 10px 24px rgba(61, 40, 23, 0.08)',
          border: '1px solid rgba(61, 40, 23, 0.08)'
        }"
        @click="bukaModal(item)"
      >
        <div class="h-48 sm:h-56 overflow-hidden relative" :style="{ borderRadius: index % 4 === 1 || index % 4 === 3 ? '8px 20px 0 0' : '20px 8px 0 0' }">
          <img
            :src="localImgSrc(item.nama_menu)"
            :alt="item.nama_menu"
            data-stage="0"
            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
            @error="handleImgError($event, item, index)"
          />

          <!-- Badge Status -->
          <div v-if="item.status_tersedia === false || item.status_tersedia === 0" class="absolute inset-0 flex items-center justify-center" style="background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(2px);">
            <span class="text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg" style="background-color: #c02a2a;">HABIS</span>
          </div>
        </div>

        <div class="p-5 flex flex-col flex-grow">
          <div class="mb-2">
            <span class="text-[10px] uppercase tracking-wider font-bold" style="color: #D97757;">{{ item.kategori?.nama_kategori || 'Menu' }}</span>
            <h3 class="text-lg mb-1 font-serif font-semibold leading-tight transition-colors" style="color: #3D2817;">{{ item.nama_menu }}</h3>
          </div>

          <p v-if="item.deskripsi" class="text-xs mb-4 line-clamp-2 flex-grow leading-relaxed" style="color: #6B4A34;">{{ item.deskripsi }}</p>
          <div v-else class="flex-grow"></div>

          <div class="flex items-center justify-between pt-4 mt-auto" style="border-top: 1px solid rgba(61, 40, 23, 0.1);">
            <span class="text-lg font-bold" style="color: #D97757;">Rp {{ fmt(item.harga) }}</span>
            <button
              @click.stop="bukaModal(item)"
              class="w-8 h-8 rounded-full text-white flex items-center justify-center hover:scale-110 transition shadow-glow"
              :style="{ backgroundColor: '#D97757' }"
              aria-label="Tambah ke keranjang"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            </button>
          </div>
        </div>
      </div>

      <div v-if="menuFiltered.length === 0" class="col-span-full text-center py-20 rounded-2xl border-dashed" style="background-color: rgba(217, 119, 87, 0.05); border: 2px dashed rgba(61, 40, 23, 0.2); color: #6B4A34;">
        Belum ada menu di kategori ini.
      </div>
    </div>

    <!-- Modal Tambah Menu -->
    <div v-if="modalItem" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="tutupModal">
      <div class="absolute inset-0 backdrop-blur-sm animate-fade-in" style="background-color: rgba(0, 0, 0, 0.4);"></div>

      <div class="relative w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl border shadow-2xl max-h-[90vh] overflow-y-auto animate-slide-up" style="background-color: #FFF; border-color: rgba(61, 40, 23, 0.1);">
        <!-- Modal Header Image -->
        <div class="relative h-40 sm:h-48 overflow-hidden rounded-t-3xl sm:rounded-t-3xl">
          <img
            :src="localImgSrc(modalItem.nama_menu)"
            :alt="modalItem.nama_menu"
            data-stage="0"
            class="w-full h-full object-cover"
            @error="handleImgError($event, modalItem)"
          />
          <div class="absolute inset-0" style="background: linear-gradient(to top, #FDF6EC, transparent);"></div>
          <button @click="tutupModal" class="absolute top-4 right-4 w-8 h-8 rounded-full text-white flex items-center justify-center transition" style="background-color: rgba(0, 0, 0, 0.4);">&times;</button>
        </div>

        <div class="p-6 sm:p-8 -mt-12 relative z-10">
          <div class="rounded-2xl p-4 mb-6 border shadow-lg" style="background-color: #FDF6EC; border-color: rgba(217, 119, 87, 0.2);">
            <div class="flex items-start justify-between mb-2">
              <h3 class="text-xl font-serif font-bold leading-tight pr-4" style="color: #3D2817;">{{ modalItem.nama_menu }}</h3>
              <p class="text-lg font-bold whitespace-nowrap" style="color: #D97757;">Rp {{ fmt(modalItem.harga) }}</p>
            </div>
            <p class="text-xs uppercase tracking-wider font-bold mb-2" style="color: #D97757;">{{ modalItem.kategori?.nama_kategori }}</p>
            <p v-if="modalItem.deskripsi" class="text-sm leading-relaxed" style="color: #6B4A34;">{{ modalItem.deskripsi }}</p>
          </div>

          <div class="mb-6">
            <label class="block text-sm font-medium mb-2" style="color: #6B4A34;">Catatan Khusus</label>
            <textarea
              v-model="modalCatatan"
              maxlength="200"
              placeholder="Contoh: Jangan terlalu pedas..."
              class="w-full border rounded-xl px-4 py-3 placeholder-opacity-60 focus:outline-none focus:ring-1 transition-all resize-none"
              :style="{
                backgroundColor: '#FDF6EC',
                borderColor: 'rgba(61, 40, 23, 0.15)',
                color: '#3D2817'
              }"
              rows="3"
            ></textarea>
            <p class="text-right text-[10px] mt-1" style="color: #6B4A34;">{{ modalCatatan.length }}/200</p>
          </div>

          <div class="flex items-center justify-between mb-8 p-4 rounded-xl border" style="background-color: rgba(217, 119, 87, 0.05); border-color: rgba(217, 119, 87, 0.2);">
            <span class="font-medium" style="color: #3D2817;">Jumlah Pesanan</span>
            <div class="flex items-center gap-4 rounded-lg p-1 border" style="background-color: #FDF6EC; border-color: rgba(61, 40, 23, 0.1);">
              <button @click="modalQty = Math.max(1, modalQty - 1)" class="w-10 h-10 rounded-md flex items-center justify-center transition font-bold text-lg" style="background-color: rgba(217, 119, 87, 0.1); color: #3D2817;">−</button>
              <span class="font-bold w-8 text-center text-lg" style="color: #3D2817;">{{ modalQty }}</span>
              <button @click="modalQty++" class="w-10 h-10 rounded-md flex items-center justify-center transition font-bold text-lg text-white" style="background-color: #D97757;">+</button>
            </div>
          </div>

          <button @click="konfirmasiTambah" class="w-full font-bold py-4 rounded-full transition shadow-lg text-white" style="background-color: #D97757;">
            Tambahkan Pesanan • Rp {{ fmt(modalItem.harga * modalQty) }}
          </button>
        </div>
      </div>
    </div>

    <!-- Floating Cart Bar -->
    <transition name="slide-up">
      <div v-if="totalQty > 0" class="fixed bottom-0 left-0 right-0 z-40 p-4 sm:p-6 pointer-events-none">
        <div class="max-w-2xl mx-auto rounded-2xl sm:rounded-full p-2 pl-6 pr-2 flex items-center justify-between shadow-2xl pointer-events-auto animate-slide-up" style="background-color: rgba(253, 246, 236, 0.95); backdrop-filter: blur(8px); border: 1px solid rgba(217, 119, 87, 0.3);">
          <div>
            <div class="text-xs uppercase tracking-wider font-bold mb-0.5" style="color: #6B4A34;">Total Keranjang</div>
            <div class="flex items-baseline gap-2">
              <span class="font-bold text-lg" style="color: #3D2817;">{{ totalQty }} Item</span>
              <span class="font-bold text-xl" style="color: #D97757;">Rp {{ fmt(totalHarga) }}</span>
            </div>
          </div>
          <button @click="goCheckout" class="font-bold rounded-full px-6 sm:px-8 py-3 transition shadow-glow flex items-center gap-2 text-white" style="background-color: #D97757; min-height: 44px;">
            Checkout
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
          </button>
        </div>
      </div>
    </transition>

  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

/* Animasi Dropdown */
.dropdown-enter-active, .dropdown-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-10px); }

/* Custom Scrollbar untuk Dropdown */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #D97757; border-radius: 10px; opacity: 0.5; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #D97757; opacity: 0.8; }
</style>