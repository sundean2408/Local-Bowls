<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { getImageUrl } from '@/services/api'
import { useCart } from '@/store/cart'

const route = useRoute()
const router = useRouter()
const { items, removeItem, clearCart, totalHarga } = useCart()

const idMeja = ref(route.query.meja || '')
const namaPemesan = ref('')
const submitting = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

const fmt = (h) => new Intl.NumberFormat('id-ID').format(h || 0)

// ===== Foto: coba file lokal di /public dulu (dicocokkan dari nama menu),
// baru fallback ke foto backend, baru terakhir foto stock generik.
// Sama persis pola yang dipakai di MenuPage.vue supaya konsisten.
function slugFromNama(nama) {
  return (nama || '').trim().replace(/\s+/g, '-')
}
function localImgSrc(nama) {
  return `/${slugFromNama(nama)}.jpg`
}
function handleImgError(event, item) {
  const img = event.target
  const stage = Number(img.dataset.stage || 0)
  if (stage === 0) {
    img.dataset.stage = '1'
    img.src = localImgSrc(item.nama_menu).toLowerCase()
  } else if (stage === 1 && item.gambar) {
    img.dataset.stage = '2'
    img.src = getImageUrl(item.gambar)
  } else {
    img.src = 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?q=80&w=500'
  }
}

function goBackToMenu() {
  router.push({ path: '/menu', query: { meja: idMeja.value } })
}

async function submitPesanan() {
  errorMsg.value = ''
  successMsg.value = ''

  if (!idMeja.value) {
    errorMsg.value = 'Meja tidak terdeteksi. Silakan scan ulang QR code.'
    return
  }
  if (items.length === 0) {
    errorMsg.value = 'Keranjang masih kosong.'
    return
  }

  submitting.value = true
  try {
    await api.post('/pesanan', {
      id_meja: idMeja.value,
      nama_pemesan: namaPemesan.value || null,
      items: items.map((i) => ({
        id_menu: i.id,
        jumlah: i.qty,
        catatan: i.catatan || null,
      })),
    })

    clearCart()
    successMsg.value = 'Pesanan berhasil dikirim! Menunggu diproses dapur.'

    setTimeout(() => {
      router.push({ path: '/status', query: { meja: idMeja.value } })
    }, 1200)
  } catch (e) {
    errorMsg.value = e.response?.data?.message || e.message || 'Gagal mengirim pesanan. Coba lagi.'
    console.error('Gagal submit pesanan:', e)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen" style="background-color: #FDF6EC; color: #3D2817;">

    <!-- Header -->
    <div class="sticky top-0 z-20 px-6 py-4" style="background-color: #FDF6EC; border-bottom: 1px solid rgba(61, 40, 23, 0.08);">
      <div class="flex items-center justify-between">
        <button @click="goBackToMenu" class="flex items-center justify-center w-10 h-10 hover:opacity-70 transition" style="background: none; border: none; cursor: pointer;">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #3D2817;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div class="flex-1 text-center">
          <h1 class="text-2xl font-bold" style="color: #3D2817;">Konfirmasi Pesanan</h1>
        </div>
        <div class="w-10"></div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="px-6 py-6 pb-28">

      <!-- User Info Card -->
      <div class="rounded-2xl p-5 mb-6" style="background-color: #F5E8DC; border: 1px solid rgba(61, 40, 23, 0.08);">
        <div class="grid grid-cols-2 gap-6 mb-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(217, 119, 87, 0.2);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #D97757;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
            </div>
            <div>
              <p class="text-xs" style="color: #6B4A34;">Nama</p>
              <p class="text-lg font-bold" style="color: #3D2817;">{{ namaPemesan || 'Tamu' }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(217, 119, 87, 0.2);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #D97757;">
                <rect x="2" y="6" width="20" height="12" rx="2" />
                <path d="M6 9v2m12-2v2" />
              </svg>
            </div>
            <div>
              <p class="text-xs" style="color: #6B4A34;">Meja</p>
              <p class="text-lg font-bold" style="color: #3D2817;">{{ idMeja || '—' }}</p>
            </div>
          </div>
        </div>
        <input
          v-model="namaPemesan"
          type="text"
          placeholder="Masukkan nama Anda (opsional)"
          class="w-full rounded-lg px-4 py-3 text-sm focus:outline-none transition border"
          :style="{
            backgroundColor: '#FFF',
            borderColor: 'rgba(217, 119, 87, 0.2)',
            color: '#3D2817'
          }"
        />
      </div>

      <!-- Empty State -->
      <div v-if="items.length === 0" class="text-center py-16 rounded-2xl border-dashed mb-6" style="background-color: rgba(217, 119, 87, 0.05); border: 2px dashed rgba(61, 40, 23, 0.2); color: #6B4A34;">
        <div class="text-3xl mb-3">📋</div>
        Keranjang masih kosong.
        <router-link :to="{ path: '/menu', query: { meja: idMeja } }" class="block mt-3 font-bold" style="color: #D97757;">Lihat menu kembali</router-link>
      </div>

      <template v-else>
        <!-- Items List -->
        <div class="space-y-4 mb-6">
          <div
            v-for="item in items"
            :key="item.cartItemId"
            class="rounded-2xl p-4 flex gap-4"
            style="background-color: #FFF; border: 1px solid rgba(61, 40, 23, 0.08);"
          >
            <!-- Image -->
            <div class="w-24 h-24 rounded-xl overflow-hidden flex-shrink-0" style="background-color: #f2e9db;">
              <img
                :src="localImgSrc(item.nama_menu)"
                :alt="item.nama_menu"
                data-stage="0"
                class="w-full h-full object-cover"
                @error="handleImgError($event, item)"
              />
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0 flex flex-col justify-between">
              <div>
                <div class="flex items-start justify-between gap-2 mb-1">
                  <h3 class="font-bold text-base" style="color: #3D2817;">{{ item.nama_menu }}</h3>
                  <button
                    @click="removeItem(item.cartItemId)"
                    class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition hover:opacity-70"
                    :style="{ color: '#c02a2a', background: 'rgba(192, 42, 42, 0.1)' }"
                    type="button"
                    style="border: none; cursor: pointer; padding: 0;"
                    aria-label="Hapus item"
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                      <line x1="18" y1="6" x2="6" y2="18" />
                      <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                  </button>
                </div>
                <input
                  v-model="item.catatan"
                  type="text"
                  placeholder="Tambah catatan (opsional)"
                  class="w-full text-xs mb-2 bg-transparent border-none outline-none p-0"
                  style="color: #6B4A34;"
                />
              </div>
              <div class="flex items-end justify-between">
                <div>
                  <p class="text-xs" style="color: #6B4A34;">Harga</p>
                  <p class="font-bold" style="color: #D97757;">Rp {{ fmt(item.harga) }}</p>
                </div>
                <div class="text-right">
                  <p class="text-xs" style="color: #6B4A34;">{{ item.qty }}× Rp {{ fmt(item.qty * item.harga) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Total Section -->
        <div class="rounded-2xl p-6 mb-6" style="background-color: #F5E8DC; border: 1px solid rgba(217, 119, 87, 0.2);">
          <div class="flex items-center justify-between">
            <span class="text-lg font-bold" style="color: #3D2817;">Total Pesanan</span>
            <span class="text-3xl font-bold" style="color: #D97757;">Rp {{ fmt(totalHarga) }}</span>
          </div>
        </div>

        <p v-if="errorMsg" class="text-sm mb-4 rounded-lg px-4 py-3" style="color: #c02a2a; background-color: rgba(192, 42, 42, 0.1);">{{ errorMsg }}</p>
        <p v-if="successMsg" class="text-sm mb-4 rounded-lg px-4 py-3" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.1);">{{ successMsg }}</p>
      </template>
    </div>

    <!-- Fixed Submit Button -->
    <div v-if="items.length > 0" class="fixed bottom-0 left-0 right-0 px-6 py-6 z-10" style="background: linear-gradient(to top, #FDF6EC, rgba(253, 246, 236, 0.95));">
      <button
        @click="submitPesanan"
        :disabled="submitting"
        class="w-full py-4 px-6 text-base flex items-center justify-center gap-3 rounded-full font-bold text-white transition shadow-lg"
        :style="{
          backgroundColor: '#D97757',
          opacity: submitting ? 0.5 : 1,
          cursor: submitting ? 'not-allowed' : 'pointer'
        }"
        type="button"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
          <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" fill="white"/>
        </svg>
        <span>{{ submitting ? 'Mengirim...' : 'Konfirmasi Pesanan' }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
button:disabled {
  opacity: 0.5 !important;
  cursor: not-allowed !important;
}
</style>