<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import api, { getImageUrl } from '@/services/api'

// --- Hero carousel ---
// Nama file HARUS PERSIS sama dengan yang ada di folder frontend/public/
const heroSlides = [
  { img: '/Mie-Aceh.jpg', nama: 'Mie Aceh', asal: 'Aceh' },
  { img: '/Mie-Koba.jpg', nama: 'Mie Koba', asal: 'Bangka' },
  { img: '/Mie-Kocok-Bandung.jpg', nama: 'Mie Kocok', asal: 'Bandung' },
  { img: '/Mie-Tiaw.jpg', nama: 'Mie Tiaw', asal: 'Pontianak' },
]

const getFallbackImg = (index) => {
  const fallbacks = [
    'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?q=80&w=1000&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1552611052-33e04de081de?q=80&w=1000&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1617093727343-374698b1b08d?q=80&w=1000&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1555126634-323283e090fa?q=80&w=1000&auto=format&fit=crop',
  ]
  return fallbacks[index % fallbacks.length]
}

const activeIndex = ref(0)
let intervalId = null

function goToSlide(i) { activeIndex.value = i }
function prevSlide() { activeIndex.value = (activeIndex.value - 1 + heroSlides.length) % heroSlides.length }
function nextSlide() { activeIndex.value = (activeIndex.value + 1) % heroSlides.length }

// --- Menu Favorit (Top Mie Terlaris) ---
const menuFavorit = ref([])
async function fetchMenuFavorit() {
  try {
    const res = await api.get('/menu')
    // api.get() (fetch-based) mengembalikan body JSON asli dari Laravel,
    // bisa berupa array langsung ATAU objek { data: [...] } tergantung
    // bagaimana controller Laravel membungkus response-nya.
    const semua = Array.isArray(res) ? res : (res.data ?? res)
    menuFavorit.value = Array.isArray(semua) ? semua.slice(0, 4) : []
    if (menuFavorit.value.length === 0) {
      console.warn('Data menu kosong atau format response tidak dikenali:', res)
    }
  } catch (e) {
    console.error('Gagal ambil menu:', e)
  }
}

// --- Reveal "Tentang Kami" saat scroll ---
const tentangRef = ref(null)
const tentangVisible = ref(false)
let ticking = false

function updateOnScroll() {
  if (tentangRef.value) {
    const rect = tentangRef.value.getBoundingClientRect()
    const vh = window.innerHeight
    if (rect.top < vh * 0.85) tentangVisible.value = true
  }
  ticking = false
}
function onScroll() {
  if (!ticking) {
    window.requestAnimationFrame(updateOnScroll)
    ticking = true
  }
}

const paragraf = [
  'Selamat datang di Local Bowls. Kami percaya bahwa mi bukan sekadar makanan pengenyang, melainkan bahasa universal yang menyatukan kehangatan keluarga dan kebersamaan. Terinspirasi dari kekayaan kuliner Indonesia, Local Bowls hadir untuk membawa Anda menjelajahi kelezatan rempah asli Nusantara dari Sabang sampai Merauke melalui semangkuk mi.',
  'Setiap lembar mi kami dibuat segar setiap hari menggunakan bahan-bahan pilihan tanpa pengawet. Kami memadukannya dengan racikan bumbu tradisional khas daerah, menghasilkan harmoni rasa gurih, pedas, dan manis yang autentik.',
  'Mari singgah, nikmati kehangatan bumbu lokal, dan rasakan warisan rasa Nusantara di setiap suapan!',
]

onMounted(() => {
  intervalId = setInterval(nextSlide, 4500)
  fetchMenuFavorit()
  window.addEventListener('scroll', onScroll, { passive: true })
  updateOnScroll()
})
onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <div class="lb-page">
    <!-- ===== HERO ===== -->
    <section class="hero">
      <!-- Foto full-bleed sebagai background -->
      <div class="hero__media">
        <transition-group name="fade">
          <img
            v-for="(slide, i) in heroSlides"
            v-show="i === activeIndex"
            :key="slide.img"
            :src="slide.img"
            :alt="slide.nama"
            class="hero__media-img"
            @error="$event.target.src = getFallbackImg(i)"
          />
        </transition-group>
        <div class="hero__scrim" aria-hidden="true"></div>
      </div>

      <div class="hero__inner">
        <span class="hero__eyebrow">Mie Nusantara Favoritmu</span>
        <h1 class="hero__title">Lagi Laper? Waktunya Makan Mie!</h1>
        <p class="hero__desc">
          Dari mie gurih hingga kuah hangat yang nikmat, temukan rasa favoritmu di LocalBowls.
          Pesan tanpa antre dan nikmati hidangan pilihanmu.
        </p>

        <div class="hero__actions">
          <router-link to="/menu" class="btn-cta">Gas Pesan!</router-link>
          <router-link to="/menu" class="btn-outline">Lihat Menu</router-link>
        </div>

        <ul class="hero__fitur">
          <li><span class="hero__fitur-ikon"></span>Pesan tanpa antre</li>
          <li><span class="hero__fitur-ikon"></span>Lacak pesanan real-time</li>
        </ul>
      </div>

      <!-- Badge nama menu yang lagi tampil -->
      <div class="hero__badge">
        <span class="hero__badge-nama">{{ heroSlides[activeIndex].nama }}</span>
        <span class="hero__badge-asal">{{ heroSlides[activeIndex].asal }}</span>
      </div>

      <!-- Panah navigasi -->
      <button class="hero__arrow hero__arrow--prev" @click="prevSlide" aria-label="Slide sebelumnya">‹</button>
      <button class="hero__arrow hero__arrow--next" @click="nextSlide" aria-label="Slide berikutnya">›</button>

      <!-- Dots -->
      <div class="hero__dots">
        <button
          v-for="(slide, i) in heroSlides"
          :key="'dot-' + i"
          @click="goToSlide(i)"
          class="hero__dot"
          :class="{ 'is-active': i === activeIndex }"
          :aria-label="`Lihat ${slide.nama}`"
        ></button>
      </div>
    </section>

    <!-- ===== TOP MIE / MENU FAVORIT ===== -->
    <section class="top-menu">
      <div class="top-menu__head">
        <div>
          <p class="top-menu__eyebrow">Pilihan Terbaik</p>
          <h2 class="top-menu__title">Menu Mie Terlaris</h2>
        </div>
        <router-link to="/menu" class="top-menu__link">Lihat semua menu →</router-link>
      </div>

      <div class="top-menu__grid">
        <article v-for="(item, index) in menuFavorit" :key="item.id" class="mie-card" :class="`mie-card--${(index % 4) + 1}`">
          <div class="mie-card__badge">#{{ index + 1 }}</div>
          <div class="mie-card__foto-wrap">
            <img
              v-if="item.gambar"
              class="mie-card__foto"
              :src="getImageUrl(item.gambar, getFallbackImg(index))"
              :alt="item.nama_menu"
              @error="$event.target.src = getFallbackImg(index)"
            />
            <div v-else class="mie-card__foto mie-card__foto--kosong">Foto Menu</div>
          </div>
          <p class="mie-card__kategori">{{ item.kategori?.nama_kategori || 'Menu' }}</p>
          <h3 class="mie-card__nama">{{ item.nama_menu }}</h3>
          <p class="mie-card__harga">Rp {{ new Intl.NumberFormat('id-ID').format(item.harga) }}</p>
        </article>

        <p v-if="menuFavorit.length === 0" class="top-menu__kosong">Menu sedang dimuat...</p>
      </div>
    </section>

    <!-- ===== SOROTAN MENU (selang-seling) ===== -->
    <section v-if="menuFavorit.length >= 3" class="sorotan">
      <p class="sorotan__divider">Dua Rasa yang Paling Dicari</p>

      <div class="sorotan__row">
        <div class="sorotan__foto-wrap">
          <img
            class="sorotan__foto"
            :src="getImageUrl(menuFavorit[0].gambar, getFallbackImg(0))"
            :alt="menuFavorit[0].nama_menu"
            @error="$event.target.src = getFallbackImg(0)"
          />
        </div>
        <div class="sorotan__teks">
          <h3>{{ menuFavorit[0].nama_menu }}</h3>
          <p>{{ menuFavorit[0].deskripsi || 'Diracik dari bumbu rempah pilihan, disajikan hangat langsung dari dapur ke mejamu.' }}</p>
          <router-link to="/menu" class="sorotan__link">Pesan menu ini →</router-link>
        </div>
      </div>

      <div class="sorotan__row sorotan__row--reverse">
        <div class="sorotan__foto-wrap">
          <img
            class="sorotan__foto"
            :src="getImageUrl(menuFavorit[2].gambar, getFallbackImg(2))"
            :alt="menuFavorit[2].nama_menu"
            @error="$event.target.src = getFallbackImg(2)"
          />
        </div>
        <div class="sorotan__teks">
          <h3>{{ menuFavorit[2].nama_menu }}</h3>
          <p>{{ menuFavorit[2].deskripsi || 'Favorit pelanggan yang selalu habis lebih dulu, cocok dinikmati bersama keluarga.' }}</p>
          <router-link to="/menu" class="sorotan__link">Pesan menu ini →</router-link>
        </div>
      </div>
    </section>

    <!-- ===== CARA PEMESANAN ===== -->
    <section id="cara-pesan" class="cara-pesan">
      <p class="cara-pesan__eyebrow">Proses Mudah</p>
      <h2 class="cara-pesan__title">Cara Pemesanan</h2>

      <div class="cara-pesan__grid">
        <div class="cara-pesan__card">
          <div class="cara-pesan__angka">1</div>
          <h3>Scan QR di Meja</h3>
          <p>Arahkan kamera HP ke QR code yang tersedia di mejamu.</p>
        </div>
        <div class="cara-pesan__card">
          <div class="cara-pesan__angka">2</div>
          <h3>Pilih & Pesan</h3>
          <p>Tambahkan menu favorit ke keranjang lalu kirim pesanan.</p>
        </div>
        <div class="cara-pesan__card">
          <div class="cara-pesan__angka">3</div>
          <h3>Santap & Bayar</h3>
          <p>Tunggu pesanan diantar, lalu bayar langsung di kasir.</p>
        </div>
      </div>
    </section>

    <!-- ===== TENTANG KAMI ===== -->
    <div id="tentang" ref="tentangRef" class="tentang">
      <div class="tentang__inner">
        <p v-if="tentangVisible" class="tentang__eyebrow reveal-item reveal-visible">Tentang Kami</p>
        <h2 v-if="tentangVisible" class="tentang__title reveal-item reveal-visible">Warisan Rasa Nusantara</h2>
        <div v-if="tentangVisible" class="tentang__paragraf">
          <p v-for="(p, i) in paragraf" :key="i" class="reveal-item reveal-visible" :style="{ transitionDelay: (i * 150) + 'ms' }">{{ p }}</p>
        </div>
        <router-link
          v-if="tentangVisible"
          to="/menu"
          class="btn-cta reveal-item reveal-visible"
          :style="{ transitionDelay: (paragraf.length * 150 + 100) + 'ms' }"
        >
          Jelajahi Menu Kami
        </router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.lb-page {
  --lb-accent: var(--lb-brand);
  --lb-accent-soft: #E8935A;
  --lb-text: var(--lb-ink);
  --lb-text-soft: var(--lb-muted);
  background: var(--lb-bg);
  color: var(--lb-text);
  font-family: 'Plus Jakarta Sans', sans-serif;
  overflow-x: hidden;
}

/* ===== HERO (full-bleed) ===== */
.hero {
  position: relative;
  min-height: 86vh;
  display: flex;
  align-items: flex-end;
  overflow: hidden;
  color: #fff;
}

.hero__media { position: absolute; inset: 0; z-index: 0; }
.hero__media-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.9s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.hero__scrim {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(35, 20, 10, 0.15) 0%, rgba(35, 20, 10, 0.55) 55%, rgba(28, 16, 8, 0.92) 100%);
}

.hero__inner {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 1120px;
  margin: 0 auto;
  padding: 0 1.25rem 4.5rem;
}

.hero__eyebrow {
  display: inline-block; font-size: 0.8rem; font-weight: 600; color: #fff;
  background: rgba(217, 119, 87, 0.85); padding: 0.3rem 0.8rem; border-radius: 999px; margin-bottom: 1rem;
}
.hero__title {
  font-family: 'Fraunces', serif; font-size: clamp(2rem, 7vw, 3.4rem); font-weight: 600; line-height: 1.1;
  margin: 0 0 1rem; max-width: 16ch; text-shadow: 0 4px 24px rgba(0,0,0,0.35);
}
.hero__desc { color: rgba(255,255,255,0.88); font-size: 1rem; line-height: 1.6; max-width: 42ch; margin: 0 0 1.5rem; }

.hero__actions { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.btn-cta {
  font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1rem; font-weight: 600; text-decoration: none;
  color: #fff; background: var(--lb-accent); border: none; padding: 0.9rem 1.8rem; border-radius: 14px; cursor: pointer;
  box-shadow: 0 8px 20px rgba(217, 119, 87, 0.45); transition: transform 0.15s ease, box-shadow 0.15s ease; display: inline-block;
  min-height: 44px; text-align: center;
}
.btn-cta:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(217, 119, 87, 0.5); }
.btn-outline {
  font-weight: 600; text-decoration: none; color: #fff; background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.5); padding: 0.9rem 1.8rem; border-radius: 14px; display: inline-block;
  min-height: 44px; text-align: center; backdrop-filter: blur(4px);
}
.btn-outline:hover { background: rgba(255,255,255,0.18); }

.hero__fitur { list-style: none; display: flex; flex-wrap: wrap; gap: 0.5rem 1.5rem; padding: 0; margin: 1.75rem 0 0; }
.hero__fitur li { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: rgba(255,255,255,0.85); font-weight: 500; }
.hero__fitur-ikon { width: 8px; height: 8px; border-radius: 50%; background: var(--lb-accent-soft); flex-shrink: 0; }

/* Badge nama menu yang lagi tampil */
.hero__badge {
  position: absolute; z-index: 1; top: 1.25rem; right: 1.25rem;
  background: rgba(255,255,255,0.95); border-radius: 16px; padding: 0.55rem 1rem;
  box-shadow: 0 10px 24px rgba(0,0,0,0.2); display: flex; flex-direction: column; line-height: 1.1;
}
.hero__badge-nama { font-family: 'Fraunces', serif; font-size: 1rem; font-weight: 600; color: var(--lb-accent); }
.hero__badge-asal { font-size: 0.7rem; color: var(--lb-text-soft); }

/* Panah navigasi kiri/kanan */
.hero__arrow {
  position: absolute; z-index: 1; top: 50%; transform: translateY(-50%);
  width: 40px; height: 40px; border-radius: 999px; border: none; cursor: pointer;
  background: rgba(255,255,255,0.18); color: #fff; font-size: 1.4rem; line-height: 1;
  backdrop-filter: blur(4px); transition: background 0.2s ease;
  display: none; align-items: center; justify-content: center;
}
.hero__arrow:hover { background: rgba(255,255,255,0.32); }
.hero__arrow--prev { left: 1.25rem; }
.hero__arrow--next { right: 1.25rem; }

.hero__dots { position: relative; z-index: 1; display: flex; gap: 0.5rem; margin: 0 auto; padding: 0 1.25rem 1.5rem; }
.hero__dot { width: 8px; height: 8px; border-radius: 999px; background: rgba(255,255,255,0.4); border: none; cursor: pointer; transition: all 0.2s ease; }
.hero__dot.is-active { width: 26px; background: #fff; }

/* ===== TOP MENU ===== */
.top-menu { padding: 1rem 1.25rem 3rem; max-width: 1120px; margin: 0 auto; }
.top-menu__head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.top-menu__eyebrow { font-size: 0.75rem; font-weight: 700; color: var(--lb-accent); margin: 0 0 0.25rem; }
.top-menu__title { font-family: 'Fraunces', serif; font-size: 1.6rem; font-weight: 600; margin: 0; }
.top-menu__link { color: var(--lb-accent); text-decoration: none; font-weight: 600; font-size: 0.9rem; }
.top-menu__kosong { color: var(--lb-text-soft); font-size: 0.9rem; }

.top-menu__grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; align-items: stretch; }

.mie-card { position: relative; display: flex; flex-direction: column; height: 100%; background: #fff; padding: 1rem; border-radius: 28px 12px 28px 12px; box-shadow: 0 10px 24px rgba(61, 40, 23, 0.08); transition: transform 0.2s ease, box-shadow 0.2s ease; }
.mie-card:hover { transform: translateY(-4px); box-shadow: 0 16px 32px rgba(61, 40, 23, 0.12); }
.mie-card--2, .mie-card--4 { border-radius: 12px 28px 12px 28px; }

.mie-card__badge { position: absolute; top: 0.6rem; left: 0.6rem; background: var(--lb-text); color: #fff; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 999px; z-index: 1; }

.mie-card__foto-wrap { aspect-ratio: 1 / 0.85; overflow: hidden; border-radius: 20px 8px 20px 8px; margin-bottom: 0.75rem; flex-shrink: 0; }
.mie-card--2 .mie-card__foto-wrap, .mie-card--4 .mie-card__foto-wrap { border-radius: 8px 20px 8px 20px; }
.mie-card__foto { width: 100%; height: 100%; object-fit: cover; }
.mie-card__foto--kosong { display: flex; align-items: center; justify-content: center; background: #f2e9db; color: var(--lb-text-soft); font-size: 0.75rem; }

.mie-card__kategori { font-size: 0.7rem; font-weight: 700; color: var(--lb-accent); text-transform: uppercase; letter-spacing: 0.03em; margin: 0 0 0.15rem; }
.mie-card__nama {
  font-size: 0.95rem; font-weight: 600; margin: 0 0 0.3rem; line-height: 1.3;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.6em;
}
.mie-card__harga { font-weight: 700; color: var(--lb-accent); margin: auto 0 0; }

/* ===== SOROTAN MENU (selang-seling) ===== */
.sorotan { padding: 1rem 1.25rem 3rem; max-width: 1000px; margin: 0 auto; }
.sorotan__divider {
  text-align: center; font-family: 'Fraunces', serif; font-size: 1.15rem; font-weight: 500; color: var(--lb-text-soft);
  margin: 0 0 2.5rem; position: relative; padding-bottom: 1rem;
}
.sorotan__divider::after {
  content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
  width: 48px; height: 2px; background: var(--lb-accent-soft); border-radius: 999px;
}
.sorotan__row { display: flex; flex-direction: column; align-items: center; gap: 1.5rem; margin-bottom: 3rem; }
.sorotan__row:last-child { margin-bottom: 0; }
.sorotan__foto-wrap { width: min(260px, 70vw); aspect-ratio: 1.1 / 1; flex-shrink: 0; }
.sorotan__foto {
  width: 100%; height: 100%; object-fit: cover;
  border-radius: 58% 42% 40% 60% / 55% 45% 55% 45%;
  box-shadow: 0 16px 32px rgba(61,40,23,0.12);
}
.sorotan__row--reverse .sorotan__foto { border-radius: 42% 58% 60% 40% / 45% 55% 45% 55%; }
.sorotan__teks { text-align: center; max-width: 40ch; }
.sorotan__teks h3 { font-family: 'Fraunces', serif; font-size: 1.4rem; font-weight: 600; margin: 0 0 0.6rem; }
.sorotan__teks p { color: var(--lb-text-soft); font-size: 0.9rem; line-height: 1.6; margin: 0 0 0.9rem; }
.sorotan__link { color: var(--lb-accent); font-weight: 600; font-size: 0.9rem; text-decoration: none; }

/* ===== CARA PEMESANAN ===== */
.cara-pesan { padding: 3rem 1.25rem; max-width: 900px; margin: 0 auto; text-align: center; }
.cara-pesan__eyebrow { font-size: 0.75rem; font-weight: 700; color: var(--lb-accent); margin: 0 0 0.25rem; }
.cara-pesan__title { font-family: 'Fraunces', serif; font-size: 1.8rem; font-weight: 600; margin: 0 0 2.5rem; }
.cara-pesan__grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
.cara-pesan__card { background: #fff; border-radius: 24px; padding: 2rem 1.5rem; box-shadow: 0 10px 24px rgba(61,40,23,0.06); }
.cara-pesan__angka {
  width: 48px; height: 48px; border-radius: 16px; background: var(--lb-accent); color: #fff; font-weight: 700; font-size: 1.2rem;
  display: flex; align-items: center; justify-content: center; margin: 0 auto 1.2rem;
}
.cara-pesan__card h3 { font-size: 1.05rem; font-weight: 700; margin: 0 0 0.5rem; }
.cara-pesan__card p { color: var(--lb-text-soft); font-size: 0.85rem; margin: 0; }

/* ===== TENTANG KAMI ===== */
.tentang { padding: 4rem 1.5rem; background: #F5E9D8; }
.tentang__inner { max-width: 720px; margin: 0 auto; text-align: center; }
.tentang__eyebrow { font-size: 0.75rem; letter-spacing: 0.15em; font-weight: 700; color: var(--lb-accent); margin: 0 0 0.75rem; }
.tentang__title { font-family: 'Fraunces', serif; font-size: clamp(1.7rem, 5vw, 2.6rem); font-weight: 500; margin: 0 0 1.5rem; }
.tentang__paragraf { color: var(--lb-text-soft); line-height: 1.7; text-align: left; }
.tentang__paragraf p { margin: 0 0 1.2rem; }
.tentang .btn-cta { margin-top: 1rem; }

.reveal-item { opacity: 0; transform: translateY(30px); animation: revealIn 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
@keyframes revealIn { to { opacity: 1; transform: translateY(0); } }
@media (prefers-reduced-motion: reduce) { .reveal-item { opacity: 1; transform: none; animation: none; } }

/* ===== TABLET (600px ke atas) ===== */
@media (min-width: 600px) and (max-width: 899px) {
  .top-menu__grid { grid-template-columns: repeat(3, 1fr); gap: 1rem; }
  .cara-pesan__grid { grid-template-columns: repeat(3, 1fr); gap: 1rem; }
  .hero__arrow { display: flex; }
}

/* ===== DESKTOP ===== */
@media (min-width: 900px) {
  .hero { min-height: 92vh; }
  .hero__inner { padding: 0 3rem 6rem; }
  .hero__arrow { display: flex; }
  .hero__arrow--prev { left: 2rem; }
  .hero__arrow--next { right: 2rem; }
  .hero__dots { padding: 0 3rem 2rem; }
  .hero__badge { top: 2rem; right: 2rem; }

  .top-menu { padding: 1rem 3rem 4rem; }
  .top-menu__grid { grid-template-columns: repeat(4, 1fr); }

  .cara-pesan__grid { grid-template-columns: repeat(3, 1fr); }

  .sorotan__row { flex-direction: row; text-align: left; gap: 3rem; }
  .sorotan__row--reverse { flex-direction: row-reverse; }
  .sorotan__foto-wrap { width: 320px; }
  .sorotan__teks { text-align: left; max-width: 32ch; }
}
</style>