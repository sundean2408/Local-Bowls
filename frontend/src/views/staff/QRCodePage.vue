<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const loading = ref(true)
const error = ref(null)
const mejaList = ref([])
const userName = ref(localStorage.getItem('nama_user') || 'Admin')

function urlPesanMeja(idMeja) {
  return `${window.location.origin}/?meja=${idMeja}`
}
function urlQR(idMeja, size = 260) {
  const target = urlPesanMeja(idMeja)
  return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(target)}`
}

async function fetchMeja() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/meja')
    mejaList.value = res.data.data ?? res.data
  } catch (e) {
    error.value = 'Gagal memuat data meja. Pastikan backend berjalan.'
    console.error(e)
  } finally {
    loading.value = false
  }
}

function downloadQR(meja) {
  const link = document.createElement('a')
  link.href = urlQR(meja.id, 500)
  link.download = `QR-Meja-${meja.nomor_meja}.png`
  link.target = '_blank'
  link.click()
}

function printSatu(meja) {
  const win = window.open('', 'PRINT_QR', 'height=650,width=500')
  win.document.write(`
    <html>
      <head><title>QR Meja ${meja.nomor_meja}</title></head>
      <body onload="window.print()" style="font-family: sans-serif; text-align:center; padding: 2rem;">
        <h2 style="margin-bottom: 0.25rem;">LocalBowls</h2>
        <p style="color:#6B4A34; margin-top:0;">Scan untuk pesan</p>
        <img src="${urlQR(meja.id, 400)}" style="margin: 1.5rem 0;" />
        <h1 style="font-size: 2rem; margin: 0;">Meja ${meja.nomor_meja}</h1>
      </body>
    </html>
  `)
  win.document.close()
}

function printSemua() {
  const win = window.open('', 'PRINT_ALL_QR', 'height=800,width=700')
  const kartu = mejaList.value.map(meja => `
    <div style="page-break-inside: avoid; display:inline-block; width:45%; margin:2.5%; text-align:center; border:1px dashed #ccc; padding:1rem; border-radius:12px;">
      <h3 style="margin: 0 0 0.25rem;">LocalBowls</h3>
      <p style="color:#6B4A34; margin: 0 0 0.75rem; font-size:0.8rem;">Scan untuk pesan</p>
      <img src="${urlQR(meja.id, 260)}" style="width:100%; max-width:220px;" />
      <h2 style="margin: 0.75rem 0 0;">Meja ${meja.nomor_meja}</h2>
    </div>
  `).join('')

  win.document.write(`
    <html>
      <head><title>QR Semua Meja - LocalBowls</title></head>
      <body onload="window.print()" style="font-family: sans-serif; text-align:center;">
        ${kartu}
      </body>
    </html>
  `)
  win.document.close()
}

function logout() {
  localStorage.removeItem('token')
  localStorage.removeItem('role')
  localStorage.removeItem('nama_user')
  router.push('/login')
}

onMounted(fetchMeja)
</script>

<template>
  <div class="lb-page">
    <!-- Navbar -->
    <nav class="lb-nav">
      <div class="lb-nav__inner">
        <router-link to="/admin" class="lb-brand">
          <span class="lb-brand__logo">
            <img src="/logo.png" alt="Logo" @error="$event.target.src='https://via.placeholder.com/100x100/D97757/FFFFFF?text=L'" />
          </span>
          <span>
            <span class="lb-brand__nama">LocalBowls</span>
            <span class="lb-brand__tagline">Rasa Nusantara</span>
          </span>
        </router-link>

        <div class="lb-nav__links">
          <router-link to="/admin">← Kembali ke Admin</router-link>
          <span class="nav-user">👤 {{ userName }}</span>
        </div>

        <button @click="logout" class="nav-logout">Logout</button>
      </div>
    </nav>

    <!-- Header -->
    <div class="page-header">
      <div class="page-header__blob" aria-hidden="true"></div>
      <p class="page-header__eyebrow">Manajemen Meja</p>
      <h1 class="page-header__title">QR Code Meja</h1>
      <p class="page-header__desc">Unduh atau cetak QR code untuk ditempel di setiap meja.</p>

      <button v-if="mejaList.length > 0" @click="printSemua" class="btn-print-all">
        🖨️ Cetak Semua QR
      </button>
    </div>

    <!-- Loading & Error -->
    <div v-if="loading" class="state-msg">Memuat data meja...</div>
    <div v-else-if="error" class="state-msg state-msg--error">{{ error }}</div>

    <!-- QR Grid -->
    <div v-else class="qr-grid">
      <div v-for="meja in mejaList" :key="meja.id" class="qr-card">
        <div class="qr-card__badge" :class="`badge--${meja.status}`">{{ meja.status }}</div>
        <div class="qr-card__img-wrap">
          <img :src="urlQR(meja.id)" :alt="`QR Meja ${meja.nomor_meja}`" class="qr-card__img" />
        </div>
        <h3 class="qr-card__nomor">Meja {{ meja.nomor_meja }}</h3>
        <p class="qr-card__url">{{ urlPesanMeja(meja.id) }}</p>

        <div class="qr-card__actions">
          <button @click="downloadQR(meja)" class="btn-sm btn-sm--download">⬇️ Unduh</button>
          <button @click="printSatu(meja)" class="btn-sm btn-sm--print">🖨️ Cetak</button>
        </div>
      </div>

      <div v-if="mejaList.length === 0" class="qr-grid__kosong">
        Belum ada data meja. Tambahkan meja dulu di Admin &gt; Manajemen Meja.
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.lb-page {
  --lb-bg: #FDF6EC;
  --lb-accent: #D97757;
  --lb-accent-soft: #E8935A;
  --lb-text: #3D2817;
  --lb-text-soft: #6B4A34;
  background: var(--lb-bg);
  color: var(--lb-text);
  font-family: 'Plus Jakarta Sans', sans-serif;
  min-height: 100vh;
  padding-bottom: 2rem;
}

/* ===== NAVBAR ===== */
.lb-nav { position: sticky; top: 0; z-index: 50; background: rgba(253, 246, 236, 0.9); backdrop-filter: blur(8px); border-bottom: 1px solid rgba(61, 40, 23, 0.08); padding: 0.9rem 1.25rem; }
.lb-nav__inner { max-width: 1120px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.lb-brand { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; color: var(--lb-text); }
.lb-brand__logo { width: 38px; height: 38px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: var(--lb-accent); }
.lb-brand__logo img { width: 100%; height: 100%; object-fit: cover; }
.lb-brand__nama { display: block; font-family: 'Fraunces', serif; font-weight: 600; font-size: 1.05rem; line-height: 1.1; }
.lb-brand__tagline { display: block; font-size: 0.65rem; color: var(--lb-accent); font-weight: 600; }
.lb-nav__links { display: flex; gap: 1.25rem; align-items: center; font-size: 0.85rem; color: var(--lb-text-soft); }
.lb-nav__links a { color: var(--lb-text-soft); text-decoration: none; font-weight: 600; }
.lb-nav__links a:hover { color: var(--lb-accent); }
.nav-logout { background: var(--lb-accent); color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; }

/* ===== HEADER ===== */
.page-header { position: relative; text-align: center; padding: 2.5rem 1.25rem 1.5rem; overflow: hidden; }
.page-header__blob { position: absolute; top: -60px; left: 50%; transform: translateX(-50%); width: 260px; height: 260px; background: var(--lb-accent-soft); opacity: 0.18; border-radius: 50%; filter: blur(60px); }
.page-header__eyebrow { position: relative; font-size: 0.75rem; font-weight: 700; color: var(--lb-accent); margin: 0 0 0.4rem; }
.page-header__title { position: relative; font-family: 'Fraunces', serif; font-size: clamp(1.7rem, 5vw, 2.4rem); font-weight: 600; margin: 0 0 0.5rem; }
.page-header__desc { position: relative; color: var(--lb-text-soft); font-size: 0.9rem; max-width: 32ch; margin: 0 auto 1.25rem; }
.btn-print-all { position: relative; background: var(--lb-accent); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 700; font-size: 0.9rem; cursor: pointer; box-shadow: 0 8px 20px rgba(217, 119, 87, 0.3); }

/* ===== STATE ===== */
.state-msg { text-align: center; padding: 4rem 1.25rem; font-size: 0.95rem; color: var(--lb-accent); }
.state-msg--error { color: #B0432E; background: rgba(217, 119, 87, 0.08); margin: 0 1.25rem; border-radius: 16px; }

/* ===== QR GRID ===== */
.qr-grid { max-width: 1120px; margin: 0 auto; padding: 0 1.25rem; display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.1rem; }
.qr-grid__kosong { grid-column: 1 / -1; text-align: center; color: var(--lb-text-soft); padding: 3rem 1rem; }

.qr-card { position: relative; background: #fff; border-radius: 20px; padding: 1.25rem; text-align: center; box-shadow: 0 10px 24px rgba(61, 40, 23, 0.08); }
.qr-card__badge { position: absolute; top: 0.75rem; right: 0.75rem; font-size: 0.65rem; font-weight: 700; padding: 0.25rem 0.55rem; border-radius: 999px; text-transform: capitalize; }
.badge--kosong { background: #E8F5E9; color: #2E7D32; }
.badge--terisi { background: #FFEBEE; color: #C62828; }

.qr-card__img-wrap { background: #fff; border: 1px solid rgba(61, 40, 23, 0.08); border-radius: 14px; padding: 0.75rem; margin-bottom: 0.9rem; }
.qr-card__img { width: 100%; height: auto; display: block; }

.qr-card__nomor { font-family: 'Fraunces', serif; font-size: 1.2rem; font-weight: 700; margin: 0 0 0.3rem; }
.qr-card__url { font-size: 0.65rem; color: var(--lb-text-soft); word-break: break-all; margin: 0 0 1rem; opacity: 0.75; }

.qr-card__actions { display: flex; gap: 0.5rem; }
.btn-sm { flex: 1; background: none; border: 1px solid; padding: 0.5rem 0.6rem; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease; }
.btn-sm--download { border-color: var(--lb-accent); color: var(--lb-accent); }
.btn-sm--download:hover { background: rgba(217, 119, 87, 0.1); }
.btn-sm--print { border-color: #0288D1; color: #0288D1; }
.btn-sm--print:hover { background: rgba(2, 136, 209, 0.1); }

/* ===== DESKTOP ===== */
@media (min-width: 900px) {
  .page-header { padding: 3.5rem 3rem 2rem; }
  .qr-grid { padding: 0 3rem; grid-template-columns: repeat(4, 1fr); }
}
</style>