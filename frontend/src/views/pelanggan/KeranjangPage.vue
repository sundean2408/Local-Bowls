<script setup>
import { ref, computed, unref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCart } from '../../store/cart'
import api, { getImageUrl } from '../../services/api'

const router = useRouter()

// Meja dipilih di halaman "Pilih Meja" (satu QR yang sama untuk semua meja),
// lalu id & nomor meja disimpan di localStorage. Di sini id itu yang dipakai
// untuk id_meja (wajib id asli tabel meja, sesuai validasi backend), sementara
// nomor_meja cuma ditampilkan ke pelanggan.
const idMejaAktif = ref(localStorage.getItem('meja_aktif_id') || '')
const nomorMejaAktif = ref(localStorage.getItem('meja_aktif_nomor') || '')

function gantiMeja() {
  localStorage.removeItem('meja_aktif_id')
  localStorage.removeItem('meja_aktif_nomor')
  router.push('/pilih-meja')
}

onMounted(() => {
  if (!idMejaAktif.value) {
    router.push('/pilih-meja')
  }
})

// Dibungkus computed dengan fallback (?? []) supaya kalau suatu saat store
// belum siap / bentuk datanya berubah lagi, halaman tetap tampil (misalnya
// "Keranjang masih kosong") daripada seluruh komponen gagal render jadi blank.
//
// PENTING soal bug "Rp NaN": `cart.totalHarga` sebenarnya sebuah Vue ref
// (dibungkus lewat storeToRefs di store/cart.js), tapi karena diakses lewat
// properti biasa (bukan destructuring top-level di <script setup>), Vue
// TIDAK otomatis membuka nilainya -- jadi yang didapat adalah objek ref itu
// sendiri, bukan angkanya, dan Number(refObject) = NaN. unref() aman dipakai
// baik nilainya berupa ref maupun angka biasa.
const cart = useCart()
const items = computed(() => cart.items ?? [])
const totalHarga = computed(() => unref(cart.totalHarga) ?? 0)

// Nama field foto menu belum dipastikan (bisa "gambar", "foto", "image", dst
// tergantung skema Menu model di backend) -- dicoba beberapa kemungkinan,
// baru fallback ke placeholder bawaan getImageUrl kalau tidak ketemu.
function fotoItem(item) {
  const raw = item.gambar ?? item.foto ?? item.foto_menu ?? item.image ?? item.thumbnail
  return getImageUrl(raw)
}

const submitting = ref(false)
const errorMsg = ref('')

function increment(item) {
  cart.updateQuantity?.(item.cartItemId, item.qty + 1)
}

function decrement(item) {
  cart.updateQuantity?.(item.cartItemId, item.qty - 1)
}

function hapusItem(item) {
  cart.removeItem?.(item.cartItemId)
}

const fmt = (h) => new Intl.NumberFormat('id-ID').format(h || 0)

async function submitPesanan() {
  if (!idMejaAktif.value) {
    errorMsg.value = 'Meja belum dipilih. Silakan pilih meja dulu.'
    return
  }
  if (items.value.length === 0) {
    errorMsg.value = 'Keranjang masih kosong.'
    return
  }

  submitting.value = true
  errorMsg.value = ''

  try {
    await api.post('/pesanan', {
      id_meja: idMejaAktif.value,
      // Backend memvalidasi field "jumlah" (lihat error "The items.0.jumlah
      // field is required"), bukan "qty" -- qty di frontend cuma nama
      // variabel lokal di cart store, beda dengan nama field yang divalidasi
      // Laravel di sisi server.
      items: items.value.map((i) => ({
        id_menu: i.id,
        jumlah: i.qty,
      })),
    })
    cart.clearCart?.()
    router.push('/status')
  } catch (e) {
    errorMsg.value = 'Gagal mengirim pesanan. Coba lagi.'
    console.error(e)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen" style="background-color: #FDF6EC;">
    <!-- Navbar -->
    <nav class="sticky top-0 z-30 border-b px-8 py-5" style="background: rgba(253, 246, 236, 0.92); border-color: rgba(61, 40, 23, 0.08); backdrop-filter: blur(6px);">
      <div class="flex items-center justify-between max-w-3xl mx-auto">
        <router-link to="/" class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full font-bold text-xl flex items-center justify-center text-white" style="background-color: #D97757;">L</div>
          <div>
            <h1 class="text-lg font-bold font-serif leading-none" style="color: #3D2817;">Local Bowls</h1>
            <p class="text-[10px] tracking-widest font-bold uppercase mt-0.5" style="color: #D97757;">Keranjang</p>
          </div>
        </router-link>
        <router-link to="/menu" class="text-xs tracking-wide font-medium" style="color: #6B4A34;">
          ← Lanjut Pesan
        </router-link>
      </div>
    </nav>

    <div class="max-w-3xl mx-auto px-6 py-10">
      <div class="mb-8 pb-4" style="border-bottom: 1px solid rgba(61, 40, 23, 0.1);">
        <p class="text-xs tracking-widest font-bold uppercase mb-1" style="color: #D97757;">Sebelum Pesan</p>
        <h2 class="text-3xl font-bold font-serif" style="color: #3D2817;">Keranjang Kamu</h2>
      </div>

      <!-- Kosong -->
      <div
        v-if="items.length === 0"
        class="text-center py-20 rounded-2xl border-dashed"
        style="color: #6B4A34; background-color: rgba(217, 119, 87, 0.05); border: 2px dashed rgba(217, 119, 87, 0.2);"
      >
        <p class="text-sm mb-4">Keranjang masih kosong. Yuk pilih menu favoritmu dulu.</p>
        <router-link
          to="/menu"
          class="inline-block text-sm font-semibold px-6 py-2.5 rounded-full text-white shadow-lg transition"
          style="background-color: #D97757;"
        >
          Lihat Menu
        </router-link>
      </div>

      <!-- Ada item -->
      <div v-else class="space-y-6">
        <div class="rounded-2xl border overflow-hidden" style="background-color: #FFF; border-color: rgba(61, 40, 23, 0.08);">
          <div
            v-for="(item, idx) in items"
            :key="item.cartItemId"
            class="flex items-center gap-4 px-6 py-5"
            :style="idx !== items.length - 1 ? 'border-bottom: 1px dashed rgba(61, 40, 23, 0.12);' : ''"
          >
            <img
              :src="fotoItem(item)"
              :alt="item.nama_menu"
              class="w-16 h-16 rounded-xl object-cover shrink-0"
              style="background-color: #FDF6EC;"
              @error="$event.target.src = '/placeholder.png'"
            />

            <div class="flex-1 min-w-0">
              <p class="font-medium truncate" style="color: #3D2817;">{{ item.nama_menu }}</p>
              <p class="text-sm mt-0.5 tabular-nums" style="color: #D97757;">Rp {{ fmt(item.harga) }}</p>
              <button
                @click="hapusItem(item)"
                class="text-xs mt-1.5 font-medium underline underline-offset-2"
                style="color: #6B4A34;"
              >
                Hapus
              </button>
            </div>

            <div class="flex items-center gap-3 shrink-0">
              <button
                @click="decrement(item)"
                class="w-8 h-8 rounded-full border-2 flex items-center justify-center font-medium transition"
                style="border-color: rgba(217, 119, 87, 0.3); color: #D97757;"
              >
                −
              </button>
              <span class="w-5 text-center text-sm font-semibold tabular-nums" style="color: #3D2817;">{{ item.qty }}</span>
              <button
                @click="increment(item)"
                class="w-8 h-8 rounded-full flex items-center justify-center font-medium text-white transition"
                style="background-color: #D97757;"
              >
                +
              </button>
            </div>

            <div class="w-24 text-right shrink-0">
              <p class="text-sm font-semibold tabular-nums" style="color: #3D2817;">Rp {{ fmt(item.harga * item.qty) }}</p>
            </div>
          </div>
        </div>

        <!-- Total -->
        <div class="rounded-2xl border p-6 flex items-center justify-between" style="background-color: #FFF; border-color: rgba(61, 40, 23, 0.08);">
          <span class="font-semibold" style="color: #3D2817;">Total Bayar</span>
          <span class="text-xl font-bold font-serif tabular-nums" style="color: #D97757;">Rp {{ fmt(totalHarga) }}</span>
        </div>

        <!-- Nomor Meja (dipilih sebelumnya di halaman Pilih Meja) -->
        <div class="rounded-2xl border p-6 flex items-center justify-between" style="background-color: #FFF; border-color: rgba(61, 40, 23, 0.08);">
          <div>
            <p class="text-xs font-medium mb-1" style="color: #6B4A34;">Meja</p>
            <p class="text-lg font-bold font-serif" :style="nomorMejaAktif ? 'color: #3D2817;' : 'color: #c02a2a;'">
              {{ nomorMejaAktif || 'Belum dipilih' }}
            </p>
          </div>
          <button
            @click="gantiMeja"
            class="text-xs font-semibold px-4 py-2 rounded-full border-2 transition"
            style="border-color: rgba(217, 119, 87, 0.3); color: #D97757;"
          >
            Ganti Meja
          </button>
        </div>
        <p v-if="errorMsg" class="text-xs -mt-3" style="color: #c02a2a;">{{ errorMsg }}</p>

        <!-- Submit -->
        <button
          @click="submitPesanan"
          :disabled="submitting"
          class="w-full font-semibold py-4 rounded-full transition disabled:opacity-50 text-white shadow-lg"
          style="background-color: #D97757;"
        >
          {{ submitting ? 'MENGIRIM PESANAN...' : 'PESAN SEKARANG' }}
        </button>
      </div>
    </div>
  </div>
</template>