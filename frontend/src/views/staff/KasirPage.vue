<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

// ================== TAB AKTIF ==================
const tabAktif = ref('bayar')

function pindahTab(tab) {
  tabAktif.value = tab
  if (tab === 'bayar' && daftarPesanan.value.length === 0) fetchSiapBayar()
  if (tab === 'riwayat' && daftarRiwayat.value.length === 0) fetchRiwayat()
}

const daftarPesanan = ref([])
const loading = ref(true)
const error = ref(null)

const submitting = ref({})
const errorPerKartu = ref({})

// Nama pembeli opsional, diinput kasir saat konfirmasi bayar.
// Disimpan sementara di frontend saja (tidak dikirim ke backend / database),
// jadi hanya dipakai untuk ditampilkan di struk transaksi yang sedang berjalan.
const namaPembeli = ref({})

const successMsg = ref('')
let successTimeout = null

const strukAktif = ref(null)

function generateNomorStruk(idPesanan) {
  const now = new Date()
  const pad = (n) => String(n).padStart(2, '0')
  const tanggal = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}`
  const jam = `${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`
  return `STRK-${tanggal}-${jam}-${idPesanan}`
}

function formatTanggalStruk(date) {
  const pad = (n) => String(n).padStart(2, '0')
  return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function tutupStruk() {
  strukAktif.value = null
}

function cetakStruk() {
  window.print()
}

// Helper untuk aman baca response Laravel, baik yang berupa array langsung
// maupun yang dibungkus { data: [...] } -- sama seperti pola di DapurPage.vue.
function ekstrakList(res) {
  const semua = Array.isArray(res) ? res : (res?.data ?? res)
  return Array.isArray(semua) ? semua : []
}

async function fetchSiapBayar() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/pesanan/siap-bayar')
    daftarPesanan.value = ekstrakList(res)
  } catch (e) {
    error.value = 'Gagal memuat daftar pesanan. Pastikan backend berjalan & kamu masih login.'
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function konfirmasiBayar(pesanan) {
  errorPerKartu.value[pesanan.id] = ''
  submitting.value[pesanan.id] = true

  try {
    const nomorStruk = generateNomorStruk(pesanan.id)
    const waktuBayar = new Date()
    const nama = (namaPembeli.value[pesanan.id] || '').trim()

    await api.post('/pembayaran', {
      id_pesanan: pesanan.id,
      metode_pembayaran: 'tunai',
      total_bayar: pesanan.total_harga,
      nomor_struk_digital: nomorStruk,
    })

    daftarPesanan.value = daftarPesanan.value.filter((p) => p.id !== pesanan.id)

    successMsg.value = `Pembayaran pesanan #${String(pesanan.id).padStart(3, '0')} berhasil dikonfirmasi.`
    if (successTimeout) clearTimeout(successTimeout)
    successTimeout = setTimeout(() => {
      successMsg.value = ''
    }, 3000)

    strukAktif.value = {
      ...pesanan,
      nomor_struk: nomorStruk,
      waktu_bayar: formatTanggalStruk(waktuBayar),
      metode: 'Tunai',
      nama_pembeli: nama || null,
    }

    // Bersihkan input nama untuk pesanan ini setelah dipakai
    delete namaPembeli.value[pesanan.id]

    daftarRiwayat.value = []
  } catch (e) {
    errorPerKartu.value[pesanan.id] = 'Gagal konfirmasi pembayaran. Coba lagi.'
    console.error(e)
  } finally {
    submitting.value[pesanan.id] = false
  }
}

// ================== RIWAYAT TRANSAKSI ==================
const daftarRiwayat = ref([])
const loadingRiwayat = ref(false)
const errorRiwayat = ref(null)

async function fetchRiwayat() {
  loadingRiwayat.value = true
  errorRiwayat.value = null
  try {
    const res = await api.get('/pembayaran')
    daftarRiwayat.value = ekstrakList(res)
  } catch (e) {
    errorRiwayat.value = 'Gagal memuat riwayat transaksi.'
    console.error(e)
  } finally {
    loadingRiwayat.value = false
  }
}

function formatTanggalRiwayat(dateStr) {
  const d = new Date(dateStr)
  const pad = (n) => String(n).padStart(2, '0')
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function lihatStrukRiwayat(trx) {
  strukAktif.value = {
    id: trx.pesanan?.id,
    meja: trx.pesanan?.meja,
    // Laravel otomatis konversi nama relasi ke snake_case saat di-JSON-kan,
    // jadi key aslinya "detail_pesanan", bukan "detailPesanan".
    detail_pesanan: trx.pesanan?.detail_pesanan ?? [],
    total_harga: trx.pesanan?.total_harga,
    nomor_struk: trx.nomor_struk_digital,
    waktu_bayar: formatTanggalRiwayat(trx.tanggal_bayar),
    metode: trx.metode_pembayaran === 'qris' ? 'QRIS' : 'Tunai',
    // Nama pembeli tidak disimpan ke database (Opsi A: frontend-only),
    // jadi tidak tersedia lagi untuk transaksi lama di riwayat.
    nama_pembeli: null,
  }
}

async function logout() {
  try {
    await api.post('/logout')
  } catch (e) {
    console.error(e)
  } finally {
    // Key yang dipakai getHeaders() di api.js adalah "authToken", bukan "token" --
    // sebelumnya token lama tidak beneran terhapus saat logout.
    localStorage.removeItem('authToken')
    localStorage.removeItem('role')
    router.push('/login')
  }
}

const fmt = (h) => new Intl.NumberFormat('id-ID').format(h || 0)

onMounted(fetchSiapBayar)
</script>

<template>
  <div class="min-h-screen animate-fade-in" style="background-color: #FDF6EC;">
    <!-- Navbar -->
    <nav class="sticky top-0 z-30 border-b px-8 py-5" style="background: rgba(253, 246, 236, 0.9); border-color: rgba(61, 40, 23, 0.08);">
      <div class="flex items-center justify-between max-w-4xl mx-auto">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full font-bold text-xl overflow-hidden flex items-center justify-center text-white border border-white" style="background-color: #D97757;">L</div>
          <div>
            <h1 class="text-lg tracking-wide font-bold font-serif" style="color: #3D2817;">Local Bowls</h1>
            <p class="text-[10px] tracking-widest font-bold uppercase" style="color: #D97757;">Kasir</p>
          </div>
        </div>
        <div class="flex items-center gap-6">
          <button @click="pindahTab('bayar')" class="text-xs tracking-wide transition font-medium pb-1" :style="tabAktif === 'bayar' ? { color: '#D97757', borderBottom: '2px solid #D97757' } : { color: '#6B4A34' }">
            KONFIRMASI BAYAR
          </button>
          <button @click="pindahTab('riwayat')" class="text-xs tracking-wide transition font-medium pb-1" :style="tabAktif === 'riwayat' ? { color: '#D97757', borderBottom: '2px solid #D97757' } : { color: '#6B4A34' }">
            RIWAYAT TRANSAKSI
          </button>
          <button @click="logout" class="text-xs tracking-wide transition font-medium" style="color: #6B4A34;">
            KELUAR →
          </button>
        </div>
      </div>
    </nav>

    <!-- Tab: Konfirmasi Bayar -->
    <div v-if="tabAktif === 'bayar'" class="max-w-4xl mx-auto px-6 py-10">
      <div class="mb-8 pb-4 flex items-center justify-between" style="border-bottom: 1px solid rgba(61, 40, 23, 0.1);">
        <div>
          <p class="text-xs tracking-widest font-bold uppercase mb-1" style="color: #D97757;">Konfirmasi Bayar</p>
          <h2 class="text-3xl font-bold font-serif" style="color: #3D2817;">Pesanan Siap Dibayar</h2>
        </div>
        <button
          @click="fetchSiapBayar"
          class="text-xs rounded-full px-4 py-2 transition font-medium border-2"
          style="color: #3D2817; border-color: rgba(217, 119, 87, 0.2); background-color: rgba(217, 119, 87, 0.05);"
        >
          ↻ Muat Ulang
        </button>
      </div>

      <!-- Notifikasi sukses -->
      <transition name="fade-slide">
        <div
          v-if="successMsg"
          class="mb-6 text-sm rounded-lg px-5 py-4 flex items-center gap-2"
          style="background-color: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.3); color: #4CAF50;"
        >
          <span class="font-bold">✓</span>
          {{ successMsg }}
        </div>
      </transition>

      <div v-if="loading" class="text-center py-16" style="color: #6B4A34;">MEMUAT DATA...</div>
      <div v-else-if="error" class="text-center py-16 rounded-2xl border-dashed" style="color: #c02a2a; background-color: rgba(192, 42, 42, 0.1); border: 2px dashed rgba(192, 42, 42, 0.2);">{{ error }}</div>

      <div v-else-if="daftarPesanan.length === 0" class="text-center py-16 rounded-2xl border-dashed" style="color: #6B4A34; background-color: rgba(217, 119, 87, 0.05); border: 2px dashed rgba(217, 119, 87, 0.2);">
        Belum ada pesanan yang siap dibayar saat ini.
      </div>

      <div v-else class="space-y-6">
        <div
          v-for="pesanan in daftarPesanan"
          :key="pesanan.id"
          class="relative rounded-2xl p-6 border"
          style="background-color: #FFF; border-color: rgba(61, 40, 23, 0.08);"
        >
          <div class="flex items-center justify-between px-0 pb-4">
            <div>
              <p class="text-xs uppercase font-bold" style="color: #6B4A34;">
                PESANAN #{{ String(pesanan.id).padStart(3, '0') }}
              </p>
              <p class="text-sm font-medium mt-0.5" style="color: #3D2817;">
                Meja {{ pesanan.meja?.nomor_meja ?? '—' }}
              </p>
            </div>
            <span class="inline-block -rotate-6 text-[11px] font-bold tracking-widest px-3 py-1 rounded-full border-2 border-dashed text-white" style="background-color: #4CAF50; border-color: #4CAF50;">
              SIAP DIAMBIL
            </span>
          </div>

          <!-- Garis perforasi -->
          <div class="relative mx-0 mb-4" style="border-top: 2px dashed rgba(217, 119, 87, 0.2);">
            <span class="absolute top-1/2 -translate-y-1/2 -left-3 w-6 h-6 rounded-full" style="background-color: #FDF6EC;"></span>
            <span class="absolute top-1/2 -translate-y-1/2 -right-3 w-6 h-6 rounded-full" style="background-color: #FDF6EC;"></span>
          </div>

          <div class="py-4 px-0">
            <div
              v-for="detail in pesanan.detail_pesanan"
              :key="detail.id"
              class="flex items-center justify-between py-3 border-b border-dashed last:border-b-0"
              style="border-color: rgba(61, 40, 23, 0.1);"
            >
              <div>
                <p class="text-sm font-medium" style="color: #3D2817;">{{ detail.menu.nama_menu }} <span style="color: #6B4A34;">×{{ detail.jumlah }}</span></p>
                <p v-if="detail.catatan" class="text-xs italic mt-0.5" style="color: #6B4A34;">Catatan: {{ detail.catatan }}</p>
              </div>
              <p class="text-sm tabular-nums" style="color: #3D2817;">Rp {{ fmt(detail.subtotal) }}</p>
            </div>
          </div>

          <div class="flex items-center justify-between pt-4" style="border-top: 2px dashed rgba(217, 119, 87, 0.2);">
            <span class="font-semibold" style="color: #3D2817;">Total</span>
            <span class="text-lg font-bold tabular-nums" style="color: #D97757;">Rp {{ fmt(pesanan.total_harga) }}</span>
          </div>

          <!-- Nama pembeli (opsional, tampil di struk saja) -->
          <div class="mt-4">
            <label class="text-xs font-medium mb-1.5 block" style="color: #6B4A34;">
              Nama Pembeli <span class="font-normal italic" style="color: #B08968;">(opsional, untuk struk)</span>
            </label>
            <input
              v-model="namaPembeli[pesanan.id]"
              type="text"
              placeholder="Contoh: Budi"
              class="w-full text-sm rounded-full px-4 py-2.5 outline-none border-2 transition"
              style="border-color: rgba(217, 119, 87, 0.2); color: #3D2817; background-color: #FDF6EC;"
              @keyup.enter="konfirmasiBayar(pesanan)"
            />
          </div>

          <!-- Konfirmasi bayar -->
          <div class="mt-4 pt-0">
            <p v-if="errorPerKartu[pesanan.id]" class="text-red-400 text-xs mb-3">{{ errorPerKartu[pesanan.id] }}</p>

            <button
              @click="konfirmasiBayar(pesanan)"
              :disabled="submitting[pesanan.id]"
              class="w-full font-semibold py-3 rounded-full transition disabled:opacity-50 text-white shadow-lg"
              style="background-color: #D97757;"
            >
              {{ submitting[pesanan.id] ? 'MEMPROSES...' : 'KONFIRMASI PEMBAYARAN' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Riwayat Transaksi -->
    <div v-if="tabAktif === 'riwayat'" class="max-w-4xl mx-auto px-6 py-10">
      <div class="mb-8 pb-4 flex items-center justify-between" style="border-bottom: 1px solid rgba(61, 40, 23, 0.1);">
        <div>
          <p class="text-xs tracking-widest font-bold uppercase mb-1" style="color: #D97757;">Riwayat</p>
          <h2 class="text-3xl font-bold font-serif" style="color: #3D2817;">Riwayat Transaksi</h2>
        </div>
        <button
          @click="fetchRiwayat"
          class="text-xs rounded-full px-4 py-2 transition font-medium border-2"
          style="color: #3D2817; border-color: rgba(217, 119, 87, 0.2); background-color: rgba(217, 119, 87, 0.05);"
        >
          ↻ Muat Ulang
        </button>
      </div>

      <div v-if="loadingRiwayat" class="text-center py-16" style="color: #6B4A34;">MEMUAT DATA...</div>
      <div v-else-if="errorRiwayat" class="text-center py-16 rounded-2xl border-dashed" style="color: #c02a2a; background-color: rgba(192, 42, 42, 0.1); border: 2px dashed rgba(192, 42, 42, 0.2);">{{ errorRiwayat }}</div>

      <div v-else-if="daftarRiwayat.length === 0" class="text-center py-16 rounded-2xl border-dashed" style="color: #6B4A34; background-color: rgba(217, 119, 87, 0.05); border: 2px dashed rgba(217, 119, 87, 0.2);">
        Belum ada transaksi yang tercatat.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="trx in daftarRiwayat"
          :key="trx.id"
          class="flex items-center justify-between rounded-2xl border px-5 py-4"
          style="background-color: #FFF; border-color: rgba(61, 40, 23, 0.08);"
        >
          <div>
            <p class="text-sm font-semibold" style="color: #3D2817;">
              Meja {{ trx.pesanan?.meja?.nomor_meja ?? '—' }}
              <span class="text-xs ml-2" style="color: #6B4A34;">{{ trx.nomor_struk_digital }}</span>
            </p>
            <p class="text-xs mt-0.5" style="color: #6B4A34;">
              {{ formatTanggalRiwayat(trx.tanggal_bayar) }} · {{ trx.metode_pembayaran?.toUpperCase() }} · Kasir: {{ trx.kasir?.nama ?? '—' }}
            </p>
          </div>
          <div class="flex items-center gap-4">
            <span class="font-bold text-sm tabular-nums" style="color: #D97757;">
              Rp {{ fmt(trx.total_bayar) }}
            </span>
            <button
              @click="lihatStrukRiwayat(trx)"
              class="text-xs rounded-full px-3 py-1.5 transition font-medium border-2"
              style="color: #3D2817; border-color: rgba(217, 119, 87, 0.2); background-color: rgba(217, 119, 87, 0.05);"
            >
              Lihat Struk
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Struk -->
    <div
      v-if="strukAktif"
      class="fixed inset-0 z-50 flex items-center justify-center px-6"
      style="background-color: rgba(61, 40, 23, 0.55); backdrop-filter: blur(4px);"
      @click.self="tutupStruk"
    >
      <div class="receipt-wrap w-full" style="max-width: 21rem;">
        <div class="receipt-scroll max-h-[80vh] overflow-y-auto">
          <!-- Konten struk -->
          <div class="struk-cetak receipt-paper" style="color: #3D2817;">
            <div class="text-center pt-8 pb-4 px-7">
              <div class="mx-auto mb-2.5 w-10 h-10 rounded-full flex items-center justify-center text-white font-serif font-bold text-lg" style="background-color: #D97757;">L</div>
              <p class="font-serif font-bold text-[15px] tracking-wide">LOCAL BOWLS</p>
              <p class="text-[10px] tracking-[0.15em] uppercase mt-1" style="color: #B08968;">Warung Mie Nusantara</p>
            </div>

            <div class="receipt-divider mx-7"></div>

            <div class="px-7 py-4 receipt-mono text-[11px] leading-6" style="color: #6B4A34;">
              <div class="flex justify-between gap-3"><span>No. Struk</span><span class="text-right" style="color: #3D2817;">{{ strukAktif.nomor_struk }}</span></div>
              <div class="flex justify-between gap-3"><span>Tanggal</span><span style="color: #3D2817;">{{ strukAktif.waktu_bayar }}</span></div>
              <div class="flex justify-between gap-3"><span>Pesanan</span><span style="color: #3D2817;">#{{ String(strukAktif.id).padStart(3, '0') }}</span></div>
              <div class="flex justify-between gap-3"><span>Meja</span><span style="color: #3D2817;">{{ strukAktif.meja?.nomor_meja ?? '—' }}</span></div>
              <div v-if="strukAktif.nama_pembeli" class="flex justify-between gap-3"><span>Nama</span><span style="color: #3D2817;">{{ strukAktif.nama_pembeli }}</span></div>
            </div>

            <div class="receipt-divider mx-7"></div>

            <div class="px-7 py-4">
              <div
                v-for="detail in strukAktif.detail_pesanan"
                :key="detail.id"
                class="mb-3 last:mb-0"
              >
                <div class="flex justify-between items-baseline gap-3">
                  <span class="text-[13px] font-medium">{{ detail.menu.nama_menu }}</span>
                  <span class="receipt-mono text-[12px] tabular-nums shrink-0">{{ fmt(detail.subtotal) }}</span>
                </div>
                <p class="receipt-mono text-[10px] mt-0.5" style="color: #B08968;">{{ detail.jumlah }} × Rp {{ fmt(detail.subtotal / detail.jumlah) }}</p>
              </div>
            </div>

            <div class="receipt-divider mx-7"></div>

            <div class="px-7 py-4">
              <div class="flex justify-between items-baseline">
                <span class="text-[11px] font-semibold tracking-wide" style="color: #6B4A34;">TOTAL BAYAR</span>
                <span class="text-2xl font-bold font-serif tabular-nums" style="color: #D97757;">Rp {{ fmt(strukAktif.total_harga) }}</span>
              </div>
              <div class="flex justify-between text-[11px] mt-2 receipt-mono" style="color: #B08968;">
                <span>Metode</span>
                <span style="color: #6B4A34;">{{ strukAktif.metode ?? 'Tunai' }}</span>
              </div>
            </div>

            <div class="receipt-divider mx-7"></div>

            <div class="text-center px-7 pt-5 pb-8">
              <p class="text-[13px] italic font-serif" style="color: #6B4A34;">Terima kasih sudah mampir!</p>
              <p class="text-[10px] mt-1" style="color: #B08968;">Sampai jumpa lagi 🌿</p>
              <div class="receipt-barcode mx-auto mt-5"></div>
            </div>
          </div>
        </div>

        <!-- Tombol aksi -->
        <div class="no-print flex gap-3 pt-4">
          <button
            @click="tutupStruk"
            class="flex-1 text-sm font-medium py-2.5 rounded-full transition border-2"
            style="color: #3D2817; border-color: rgba(217, 119, 87, 0.2); background-color: rgba(217, 119, 87, 0.05);"
          >
            Tutup
          </button>
          <button
            @click="cetakStruk"
            class="flex-1 text-sm font-medium py-2.5 rounded-full transition text-white shadow-lg"
            style="background-color: #D97757;"
          >
            🖨 Cetak Struk
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap');

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ===== Struk kasir ala kertas thermal ===== */
.receipt-wrap {
  filter: drop-shadow(0 18px 34px rgba(61, 40, 23, 0.28));
}
.receipt-scroll {
  clip-path: polygon(
    0% 10px, 8.33% 0, 16.67% 10px, 25% 0, 33.33% 10px, 41.67% 0,
    50% 10px, 58.33% 0, 66.67% 10px, 75% 0, 83.33% 10px, 91.67% 0, 100% 10px,
    100% calc(100% - 10px), 91.67% 100%, 83.33% calc(100% - 10px), 75% 100%,
    66.67% calc(100% - 10px), 58.33% 100%, 50% calc(100% - 10px), 41.67% 100%,
    33.33% calc(100% - 10px), 25% 100%, 16.67% calc(100% - 10px), 8.33% 100%, 0% calc(100% - 10px)
  );
}
.receipt-paper {
  background-color: #FFFDF9;
  background-image: repeating-linear-gradient(180deg, rgba(61, 40, 23, 0.015) 0px, rgba(61, 40, 23, 0.015) 1px, transparent 1px, transparent 3px);
}
.receipt-divider {
  border-top: 1.5px dashed rgba(107, 74, 52, 0.28);
}
.receipt-mono {
  font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
.receipt-barcode {
  width: 130px;
  height: 26px;
  background-image: repeating-linear-gradient(
    90deg,
    #3D2817 0px, #3D2817 2px,
    transparent 2px, transparent 4px,
    #3D2817 4px, #3D2817 5px,
    transparent 5px, transparent 8px,
    #3D2817 8px, #3D2817 11px,
    transparent 11px, transparent 13px
  );
  opacity: 0.55;
}

@media print {
  body * {
    visibility: hidden;
  }
  .struk-cetak,
  .struk-cetak * {
    visibility: visible;
  }
  .receipt-scroll {
    clip-path: none;
  }
  .struk-cetak {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
  }
  .no-print {
    display: none !important;
  }
}
</style>