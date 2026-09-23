<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'

const router = useRouter()

const daftarMeja = ref([])
const loading = ref(true)
const error = ref(null)
let intervalId = null

async function fetchMeja() {
  try {
    const res = await api.get('/meja')
    daftarMeja.value = res.data.data ?? res.data
    error.value = null
  } catch (e) {
    error.value = 'Gagal memuat data meja. Pastikan backend berjalan & kamu masih login.'
    console.error(e)
  } finally {
    loading.value = false
  }
}

function isKosong(meja) {
  return String(meja.status_meja).toLowerCase() === 'kosong'
}

async function logout() {
  try {
    await api.post('/logout')
  } catch (e) {
    console.error(e)
  } finally {
    localStorage.removeItem('token')
    router.push('/login')
  }
}

onMounted(() => {
  fetchMeja()
  intervalId = setInterval(fetchMeja, 5000) // auto-refresh tiap 5 detik
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})
</script>

<template>
  <div class="min-h-screen bg-white [font-family:'Plus_Jakarta_Sans',_system-ui,_sans-serif]">
    <!-- Navbar kop nota -->
    <nav class="bg-[#3F6048] px-8 py-5">
      <div class="flex items-center justify-between max-w-4xl mx-auto">
        <div class="flex items-center gap-3">
          <img src="/logo.png" alt="Local Bowls" class="w-10 h-10 rounded-full object-cover flex-shrink-0 ring-2 ring-[#D9A441]/40" />
          <div>
            <h1 class="[font-family:'Fraunces',_serif] text-lg tracking-wide text-white">Local Bowls</h1>
            <p class="text-[10px] tracking-[3px] text-[#D9A441] [font-family:'Space_Mono',_monospace]">WAITER</p>
          </div>
        </div>
        <button @click="logout" class="text-xs tracking-wide text-white/70 hover:text-[#D9A441] transition">
          KELUAR →
        </button>
      </div>
    </nav>

    <!-- pita batik -->
    <div
      class="h-[6px] w-full"
      style="background: repeating-linear-gradient(135deg, #3F6048 0 10px, #D9A441 10px 20px);"
    ></div>

    <div class="max-w-4xl mx-auto px-6 py-10">
      <div class="mb-8 border-b-2 border-dashed border-[#243127]/20 pb-4 flex items-center justify-between">
        <div>
          <p class="text-xs tracking-[3px] text-[#D9A441] [font-family:'Space_Mono',_monospace] mb-1">STATUS MEJA</p>
          <h2 class="[font-family:'Fraunces',_serif] text-3xl text-[#243127]">Ketersediaan Meja</h2>
        </div>
        <button
          @click="fetchMeja"
          class="text-xs border border-[#243127]/20 rounded-full px-4 py-2 text-[#243127] hover:border-[#3F6048] hover:text-[#3F6048] transition"
        >
          ↻ Muat Ulang
        </button>
      </div>

      <div v-if="loading" class="text-center text-[#6B756D] py-16 [font-family:'Space_Mono',_monospace] text-sm">MEMUAT DATA...</div>
      <div v-else-if="error" class="text-center text-[#B94A48] py-16">{{ error }}</div>

      <div v-else-if="daftarMeja.length === 0" class="text-center text-[#6B756D] py-16 bg-[#F7F9F5] rounded-lg border-2 border-dashed border-[#243127]/15">
        Belum ada data meja.
      </div>

      <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        <div
          v-for="meja in daftarMeja"
          :key="meja.id"
          :class="[
            'rounded-lg border-2 border-dashed p-6 text-center transition',
            isKosong(meja) ? 'border-[#4F7D52]/40 bg-[#4F7D52]/5' : 'border-[#B94A48]/40 bg-[#B94A48]/5'
          ]"
        >
          <p class="text-[10px] tracking-[3px] text-[#6B756D] [font-family:'Space_Mono',_monospace] mb-2">MEJA</p>
          <p class="text-3xl font-bold text-[#243127] [font-family:'Fraunces',_serif] mb-3">{{ meja.nomor_meja }}</p>
          <span
            :class="[
              'inline-block text-[11px] font-bold tracking-[2px] px-3 py-1 rounded-full [font-family:\'Space_Mono\',_monospace]',
              isKosong(meja) ? 'bg-[#4F7D52] text-white' : 'bg-[#B94A48] text-white'
            ]"
          >
            {{ isKosong(meja) ? 'KOSONG' : 'TERISI' }}
          </span>
        </div>
      </div>

      <!-- Ringkasan kecil -->
      <div v-if="daftarMeja.length > 0" class="mt-8 flex items-center gap-6 text-sm text-[#6B756D]">
        <span class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full bg-[#4F7D52]"></span>
          {{ daftarMeja.filter(isKosong).length }} meja kosong
        </span>
        <span class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full bg-[#B94A48]"></span>
          {{ daftarMeja.filter(m => !isKosong(m)).length }} meja terisi
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap');
</style>