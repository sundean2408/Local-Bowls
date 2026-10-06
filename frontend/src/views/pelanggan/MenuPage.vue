<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api, { getImageUrl } from '@/services/api'
import { useCartStore } from '../../store/cart'

const cart = useCartStore()
const route = useRoute()

// Scan QR meja -> /menu?meja={id}: simpan agar Konfirmasi bisa preselect.
const mejaDariQR = String(route.query.meja ?? '')
if (mejaDariQR) localStorage.setItem('lastMejaId', mejaDariQR)

// ===== STATE =====
const menuList = ref([])
const kategoriList = ref([])
const loading = ref(true)
const errorMsg = ref('')
const activeKategori = ref('semua')
const searchQuery = ref('')
const toast = ref('')
let toastTimer = null

// ===== HELPERS =====
// Logo lokal selalu ada — fallback aman tanpa ketergantungan eksternal.
const FALLBACK_IMG = '/logo.png'

const getGambarUrl = (gambar) => getImageUrl(gambar, FALLBACK_IMG)

const formatHarga = (harga) => new Intl.NumberFormat('id-ID').format(harga || 0)

const isTersedia = (menu) =>
  menu.status_tersedia !== false && menu.status_tersedia !== 0 && menu.status_tersedia !== '0'

const qtyDiKeranjang = (id) => cart.items.find((i) => i.id === id)?.qty || 0

const menuTampil = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return (menuList.value ?? []).filter((m) => {
    const cocokKategori = activeKategori.value === 'semua' || m.id_kategori === activeKategori.value
    const cocokCari =
      !q ||
      (m.nama_menu || '').toLowerCase().includes(q) ||
      (m.deskripsi || '').toLowerCase().includes(q)
    return cocokKategori && cocokCari
  })
})

function showToast(pesan) {
  toast.value = pesan
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => (toast.value = ''), 1800)
}

// ===== AKSI =====
function tambahKeKeranjang(menu) {
  if (!isTersedia(menu)) return

  cart.addItem(menu)
  showToast(`${menu.nama_menu} ditambahkan`)
}

// ===== DATA =====
async function loadData() {
  loading.value = true
  errorMsg.value = ''
  try {
    const [resMenu, resKategori] = await Promise.all([api.get('/menu'), api.get('/kategori')])
    menuList.value = Array.isArray(resMenu) ? resMenu : (resMenu.data ?? [])
    kategoriList.value = Array.isArray(resKategori) ? resKategori : (resKategori.data ?? [])
  } catch (e) {
    console.error(e)
    errorMsg.value = 'Menu belum bisa dimuat. Periksa koneksi lalu coba lagi.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <div class="w-full max-w-none px-4 sm:px-6 lg:px-8 2xl:px-10 py-6 pb-28">
    <!-- Judul & info meja -->
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
      <h1 class="text-2xl font-extrabold text-terracotta-900">Menu</h1>
    </div>

    <!-- Pencarian -->
    <div role="search" class="flex items-center gap-2 bg-white border border-terracotta-100 rounded-full px-4 py-2 mb-4 focus-within:ring-2 focus-within:ring-terracotta-600">
      <svg aria-hidden="true" class="w-4 h-4 text-earth-dark/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-4.34-4.34M19 11a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
      <input
        id="menu-search"
        v-model="searchQuery"
        type="text"
        placeholder="Cari menu..."
        aria-label="Cari menu"
        autocomplete="off"
        class="bg-transparent text-sm outline-none flex-1 text-earth-dark placeholder:text-earth-dark/40"
      />
    </div>

    <!-- Filter kategori -->
    <nav aria-label="Filter kategori menu" class="flex gap-2 overflow-x-auto pb-2 mb-4">
      <button
        type="button"
        @click="activeKategori = 'semua'"
        :aria-pressed="activeKategori === 'semua'"
        class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-terracotta-700"
        :class="activeKategori === 'semua' ? 'bg-terracotta-600 text-white' : 'bg-white text-earth-dark border border-terracotta-100'"
      >Semua</button>
      <button
        v-for="kat in kategoriList"
        :key="kat.id"
        type="button"
        @click="activeKategori = kat.id"
        :aria-pressed="activeKategori === kat.id"
        class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-terracotta-700"
        :class="activeKategori === kat.id ? 'bg-terracotta-600 text-white' : 'bg-white text-earth-dark border border-terracotta-100'"
      >{{ kat.nama_kategori }}</button>
    </nav>

    <!-- Status muat -->
    <p v-if="loading" role="status" aria-live="polite" class="text-sm text-earth-dark/60 py-8 text-center">Memuat menu...</p>

    <div v-else-if="errorMsg" role="alert" class="text-center py-8">
      <p class="text-sm text-earth-dark/70 mb-3">{{ errorMsg }}</p>
      <button type="button" @click="loadData" class="min-h-11 text-sm font-bold px-4 py-2 rounded-full text-white bg-terracotta-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-terracotta-700">
        Coba lagi
      </button>
    </div>

    <p v-else-if="menuTampil.length === 0" role="status" class="text-sm text-earth-dark/60 py-8 text-center">
      Tidak ada menu yang cocok. Coba kata kunci atau kategori lain.
    </p>

    <!-- Daftar menu -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
      <article
        v-for="menu in menuTampil"
        :key="menu.id"
        class="min-w-0 rounded-2xl p-4 bg-white border border-terracotta-100 flex flex-col"
        :class="{ 'opacity-60': !isTersedia(menu) }"
      >
        <img
          :src="getGambarUrl(menu.gambar)"
          :alt="menu.nama_menu"
          class="w-full h-40 rounded-xl object-cover mb-3"
          loading="lazy"
          @error="$event.target.src = FALLBACK_IMG"
        />
        <h3 class="font-bold text-earth-dark">{{ menu.nama_menu }}</h3>
        <p class="text-sm text-earth-dark/70 flex-1">{{ menu.deskripsi || 'Racikan rempah pilihan, disajikan hangat dari dapur.' }}</p>

        <div class="flex items-center justify-between mt-3">
          <span class="font-bold text-terracotta-700">Rp {{ formatHarga(menu.harga) }}</span>

          <span v-if="!isTersedia(menu)" class="text-xs font-bold px-3 py-1 rounded-full text-white" style="background-color:var(--lb-danger);">
            Habis
          </span>

          <div v-else class="flex items-center gap-2">
            <span v-if="qtyDiKeranjang(menu.id) > 0" class="text-xs font-semibold text-earth-dark/70">
              {{ qtyDiKeranjang(menu.id) }} di keranjang
            </span>
            <button
              type="button"
              @click="tambahKeKeranjang(menu)"
              class="w-11 h-11 rounded-full bg-terracotta-600 text-white flex items-center justify-center hover:bg-terracotta-700 transition shrink-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-terracotta-700"
              :aria-label="`Tambah ${menu.nama_menu} ke keranjang`"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            </button>
          </div>
        </div>
        </article>
    </div>

    <!-- Notifikasi kecil -->
    <div
      v-if="toast"
      class="fixed left-1/2 -translate-x-1/2 bottom-24 z-40 bg-earth-dark text-white text-sm px-4 py-2 rounded-full shadow-lg"
      role="status"
      aria-live="polite"
    >{{ toast }}</div>

    <!-- Bar keranjang -->
    <router-link
      v-if="cart.totalItems > 0"
      :to="{ name: 'Keranjang' }"
      :aria-label="`Lihat keranjang, ${cart.totalItems} item, total Rp ${formatHarga(cart.totalHarga)}`"
      class="fixed left-4 right-4 sm:left-1/2 sm:right-auto sm:-translate-x-1/2 sm:w-full sm:max-w-md bottom-[max(1rem,env(safe-area-inset-bottom))] z-30 flex min-h-12 items-center justify-between gap-3 bg-terracotta-600 text-white rounded-full px-4 sm:px-5 py-3 shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-earth-dark"
    >
      <span class="text-sm font-semibold">{{ cart.totalItems }} item</span>
      <span class="text-sm font-bold">Lihat Keranjang · Rp {{ formatHarga(cart.totalHarga) }}</span>
    </router-link>
  </div>
</template>