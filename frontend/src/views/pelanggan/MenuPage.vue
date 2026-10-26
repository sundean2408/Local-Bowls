<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api, { getImageUrl } from '@/services/api'
import { useCartStore } from '../../store/cart'
import PageHeader from '../../components/PageHeader.vue'

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

function kurangiDariKeranjang(menu) {
  cart.updateQty(menu.id, qtyDiKeranjang(menu.id) - 1)
}

function onMenuCardKeydown(event, menu) {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    tambahKeKeranjang(menu)
  }
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
  <div class="lb-wrap menu-page">
    <PageHeader
      eyebrow="Dari dapur LocalBowls"
      title="Pilih Menu"
      subtitle="Temukan hidangan favoritmu dan tambahkan ke keranjang."
    />

    <!-- Pencarian -->
    <div role="search" class="menu-page__search">
      <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-4.34-4.34M19 11a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
      <input
        id="menu-search"
        v-model="searchQuery"
        type="text"
        placeholder="Cari menu..."
        aria-label="Cari menu"
        autocomplete="off"
        class="menu-page__search-input"
      />
    </div>

    <!-- Filter kategori -->
    <nav aria-label="Filter kategori menu" class="menu-page__filters">
      <button
        type="button"
        @click="activeKategori = 'semua'"
        :aria-pressed="activeKategori === 'semua'"
        class="menu-page__filter"
        :class="{ 'is-active': activeKategori === 'semua' }"
      >Semua</button>
      <button
        v-for="kat in kategoriList"
        :key="kat.id"
        type="button"
        @click="activeKategori = kat.id"
        :aria-pressed="activeKategori === kat.id"
        class="menu-page__filter"
        :class="{ 'is-active': activeKategori === kat.id }"
      >{{ kat.nama_kategori }}</button>
    </nav>

    <!-- Status muat -->
    <p v-if="loading" role="status" aria-live="polite" class="menu-page__message">Memuat menu...</p>

    <div v-else-if="errorMsg" role="alert" class="menu-page__message">
      <p>{{ errorMsg }}</p>
      <button type="button" @click="loadData" class="lb-btn-primary">
        Coba lagi
      </button>
    </div>

    <p v-else-if="menuTampil.length === 0" role="status" class="menu-page__message">
      Tidak ada menu yang cocok. Coba kata kunci atau kategori lain.
    </p>

    <!-- Daftar menu -->
    <div v-else class="menu-page__grid">
      <article
        v-for="menu in menuTampil"
        :key="menu.id"
        class="lb-card menu-page__card"
        :class="{ 'opacity-60': !isTersedia(menu) }"
        role="button"
        tabindex="0"
        @click="tambahKeKeranjang(menu)"
        @keydown="onMenuCardKeydown($event, menu)"
      >
        <img
          :src="getGambarUrl(menu.gambar)"
          :alt="menu.nama_menu"
          class="menu-page__image"
          loading="lazy"
          @error="$event.target.src = FALLBACK_IMG"
        />
        <h3 class="menu-page__name">{{ menu.nama_menu }}</h3>
        <p class="menu-page__description">{{ menu.deskripsi || 'Racikan rempah pilihan, disajikan hangat dari dapur.' }}</p>

        <div class="menu-page__card-foot">
          <span class="menu-page__price">Rp {{ formatHarga(menu.harga) }}</span>

          <span v-if="!isTersedia(menu)" class="menu-page__sold-out">
            Habis
          </span>

          <div v-else class="menu-page__add">
            <div v-if="qtyDiKeranjang(menu.id) > 0" class="menu-page__quantity">
              <button
                type="button"
                class="menu-page__quantity-button"
                @click.stop="kurangiDariKeranjang(menu)"
                :aria-label="`Kurangi ${menu.nama_menu} dari keranjang`"
              >−</button>
              <span class="menu-page__quantity-count" :aria-label="`${qtyDiKeranjang(menu.id)} ${menu.nama_menu} di keranjang`">
                {{ qtyDiKeranjang(menu.id) }}
              </span>
              <button
                type="button"
                class="menu-page__quantity-button menu-page__quantity-button--plus"
                @click.stop="tambahKeKeranjang(menu)"
                :aria-label="`Tambah ${menu.nama_menu} ke keranjang`"
              >+</button>
            </div>
            <button
              v-else
              type="button"
              @click.stop="tambahKeKeranjang(menu)"
              class="menu-page__add-button"
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
      class="menu-page__toast"
      role="status"
      aria-live="polite"
    >{{ toast }}</div>

    <!-- Bar keranjang -->
    <router-link
      v-if="cart.totalItems > 0"
      :to="{ name: 'Keranjang' }"
      :aria-label="`Lihat keranjang, ${cart.totalItems} item, total Rp ${formatHarga(cart.totalHarga)}`"
      class="menu-page__cart-bar"
    >
      <span class="text-sm font-semibold">{{ cart.totalItems }} item</span>
      <span class="text-sm font-bold">Lihat Keranjang · Rp {{ formatHarga(cart.totalHarga) }}</span>
    </router-link>
  </div>
</template>

<style scoped>
.menu-page {
  box-sizing: border-box;
  width: 100%;
  max-width: none;
  padding: 1.5rem clamp(1rem, 3vw, 2.5rem) max(7rem, calc(5rem + env(safe-area-inset-bottom)));
}
.menu-page__search {
  display: flex; align-items: center; gap: 0.7rem; min-height: 3.25rem;
  margin-bottom: 1rem; padding: 0.55rem 1rem; border: 1px solid var(--lb-line);
  border-radius: 999px; background: #fff; transition: border-color 0.15s, box-shadow 0.15s;
}
.menu-page__search:focus-within { border-color: var(--lb-brand); box-shadow: 0 0 0 3px rgba(217, 119, 87, 0.14); }
.menu-page__search svg { width: 1.1rem; height: 1.1rem; flex: 0 0 auto; color: var(--lb-muted); }
.menu-page__search-input { width: 100%; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--lb-ink); font-size: 0.9rem; }
.menu-page__search-input::placeholder { color: var(--lb-faint); }
.menu-page__filters { display: flex; gap: 0.5rem; overflow-x: auto; margin-bottom: 1.25rem; padding: 0.15rem 0.1rem 0.45rem; scrollbar-width: thin; }
.menu-page__filter {
  flex: 0 0 auto; min-height: 2.5rem; padding: 0.55rem 1rem; border: 1px solid var(--lb-line);
  border-radius: 999px; background: #fff; color: var(--lb-muted); font-size: 0.84rem; font-weight: 700; cursor: pointer;
  transition: color 0.15s, background 0.15s, border-color 0.15s;
}
.menu-page__filter:hover { border-color: var(--lb-brand); color: var(--lb-ink); }
.menu-page__filter.is-active { border-color: var(--lb-brand); background: var(--lb-brand); color: #fff; }
.menu-page__message { display: grid; justify-items: center; gap: 0.75rem; padding: 3rem 1rem; color: var(--lb-muted); text-align: center; }
.menu-page__message p { margin: 0; }
.menu-page__grid { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 1rem; }
.menu-page__card {
  display: flex; min-width: 0; flex-direction: column; padding: 0.9rem; cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}
.menu-page__card:hover { transform: translateY(-2px); border-color: rgba(217, 119, 87, 0.35); box-shadow: 0 16px 36px -16px rgba(61, 40, 23, 0.28); }
.menu-page__card:focus-visible { outline: 2px solid var(--lb-brand); outline-offset: 3px; }
.menu-page__image { width: 100%; height: 11rem; margin-bottom: 0.75rem; border-radius: 14px; background: var(--lb-soft); object-fit: cover; }
.menu-page__name { margin: 0 0 0.3rem; color: var(--lb-ink); font-size: 1rem; font-weight: 800; }
.menu-page__description { flex: 1; margin: 0; color: var(--lb-muted); font-size: 0.84rem; line-height: 1.5; }
.menu-page__card-foot { display: flex; align-items: center; justify-content: space-between; gap: 0.6rem; margin-top: 1rem; }
.menu-page__price { color: var(--lb-brand-dark); font-size: 0.95rem; font-weight: 800; }
.menu-page__sold-out { padding: 0.3rem 0.7rem; border-radius: 999px; background: var(--lb-red-bg); color: var(--lb-red-text); font-size: 0.72rem; font-weight: 800; }
.menu-page__add { display: flex; align-items: center; gap: 0.55rem; }
.menu-page__quantity { display: flex; align-items: center; gap: 0.55rem; }
.menu-page__quantity-button { display: grid; width: 2.5rem; height: 2.5rem; place-items: center; border: 1px solid var(--lb-line); border-radius: 50%; background: #fff; color: var(--lb-brand-dark); font-size: 1.2rem; font-weight: 800; cursor: pointer; transition: background 0.15s, transform 0.15s; }
.menu-page__quantity-button:hover { transform: translateY(-1px); background: var(--lb-soft); }
.menu-page__quantity-button--plus { border-color: var(--lb-brand); background: var(--lb-brand); color: #fff; }
.menu-page__quantity-button--plus:hover { background: var(--lb-brand-dark); }
.menu-page__quantity-count { min-width: 1.25rem; color: var(--lb-ink); font-size: 0.9rem; font-weight: 800; text-align: center; font-variant-numeric: tabular-nums; }
.menu-page__add-button { display: grid; width: 2.75rem; height: 2.75rem; flex: 0 0 auto; place-items: center; border: 0; border-radius: 50%; background: var(--lb-brand); color: #fff; cursor: pointer; transition: background 0.15s, transform 0.15s; }
.menu-page__add-button:hover { transform: translateY(-1px); background: var(--lb-brand-dark); }
.menu-page__add-button svg { width: 1.2rem; height: 1.2rem; }
.menu-page__toast { position: fixed; z-index: 40; bottom: 6rem; left: 50%; transform: translateX(-50%); padding: 0.65rem 1rem; border-radius: 999px; background: var(--lb-ink); color: #fff; font-size: 0.85rem; box-shadow: var(--lb-shadow); white-space: nowrap; }
.menu-page__cart-bar {
  position: fixed; z-index: 30; right: 1rem; bottom: max(1rem, env(safe-area-inset-bottom)); left: 1rem;
  display: flex; min-height: 3rem; align-items: center; justify-content: space-between; gap: 0.75rem;
  padding: 0.75rem 1.1rem; border-radius: 999px; background: var(--lb-brand); color: #fff; text-decoration: none;
  font-size: 0.84rem; box-shadow: 0 10px 26px rgba(61, 40, 23, 0.22); transition: background 0.15s, transform 0.15s;
}
.menu-page__cart-bar:hover { transform: translateY(-1px); background: var(--lb-brand-dark); }
@media (max-width: 639px) {
  .menu-page__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem; }
  .menu-page__card { padding: 0.45rem; border-radius: 14px; }
  .menu-page__image {
    height: auto;
    aspect-ratio: 1 / 1;
    margin-bottom: 0.45rem;
    border-radius: 10px;
  }
  .menu-page__name { font-size: 0.8rem; line-height: 1.25; overflow-wrap: anywhere; }
  .menu-page__description { font-size: 0.68rem; line-height: 1.35; }
  .menu-page__card-foot { align-items: flex-start; flex-direction: column; gap: 0.45rem; margin-top: 0.65rem; }
  .menu-page__price { font-size: 0.75rem; }
  .menu-page__quantity { gap: 0.3rem; }
  .menu-page__quantity-button { width: 1.75rem; height: 1.75rem; font-size: 1rem; }
  .menu-page__quantity-count { min-width: 0.8rem; font-size: 0.75rem; }
  .menu-page__add-button { width: 2rem; height: 2rem; }
  .menu-page__add-button svg { width: 1rem; height: 1rem; }
}
@media (min-width: 640px) { .menu-page__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (min-width: 900px) { .menu-page__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (min-width: 1200px) { .menu-page__grid { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
@media (min-width: 640px) { .menu-page__cart-bar { right: auto; left: 50%; width: min(100% - 3rem, 28rem); transform: translateX(-50%); } .menu-page__cart-bar:hover { transform: translate(-50%, -1px); } }
</style>