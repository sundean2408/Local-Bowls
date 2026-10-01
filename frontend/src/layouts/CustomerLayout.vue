<template>
  <div class="min-h-screen flex flex-col">
    <nav class="lb-nav">
      <div class="lb-wrap lb-nav__inner">
        <router-link to="/" class="lb-nav__brand">
          <img src="/logo.png" alt="LocalBowls" class="lb-nav__logo-img" />
          <span class="lb-nav__word">LocalBowls</span>
        </router-link>

        <div class="lb-nav__links">
          <router-link to="/" class="lb-nav__link">Beranda</router-link>
          <router-link to="/menu" class="lb-nav__link">Menu</router-link>
          <router-link to="/status" class="lb-nav__link">Status Pesanan</router-link>
        </div>

        <div class="lb-nav__actions">
          <router-link to="/keranjang" class="lb-nav__cart" aria-label="Keranjang">
            <svg class="lb-nav__cart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M6 6h15l-1.5 9H7L6 6z"/><path d="M6 6L5 2H2"/><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/></svg>
            <span v-if="cartStore.totalItems > 0" class="lb-nav__badge">{{ cartStore.totalItems }}</span>
          </router-link>
          <button type="button" class="lb-nav__burger" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Menu">
            <span v-if="!mobileMenuOpen" aria-hidden="true">☰</span>
            <span v-else aria-hidden="true">✕</span>
          </button>
        </div>
      </div>
      <div v-if="mobileMenuOpen" class="lb-nav__mobile">
        <router-link @click="mobileMenuOpen = false" to="/" class="lb-nav__mobile-link">Beranda</router-link>
        <router-link @click="mobileMenuOpen = false" to="/menu" class="lb-nav__mobile-link">Menu</router-link>
        <router-link @click="mobileMenuOpen = false" to="/status" class="lb-nav__mobile-link">Status Pesanan</router-link>
      </div>
    </nav>

    <main class="flex-1">
      <slot />
    </main>

    <footer class="lb-footer">
      <div class="lb-wrap lb-footer__grid">
        <div>
          <p class="lb-footer__brand"><img src="/logo.png" alt="" class="lb-footer__logo-img" /> LocalBowls</p>
          <p class="lb-footer__muted">Semangkuk mie hangat, dari dapur ke mejamu. Pesan tanpa antre, lacak pesanan real-time.</p>
        </div>
        <div>
          <p class="lb-footer__head">Jelajah</p>
          <ul class="lb-footer__links">
            <li><router-link to="/menu">Lihat Menu</router-link></li>
            <li><router-link to="/status">Status Pesanan</router-link></li>
            <li><router-link to="/keranjang">Keranjang</router-link></li>
          </ul>
        </div>
        <div>
          <p class="lb-footer__head">Lokasi</p>
          <p class="lb-footer__muted">Yogyakarta, Indonesia<br />info@localbowls.id</p>
        </div>
      </div>
      <div class="lb-footer__copy">&copy; {{ currentYear }} LocalBowls · Rasa Lokal untuk Semua</div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useCartStore } from '../store/cart.js'

const cartStore = useCartStore()
const mobileMenuOpen = ref(false)
const currentYear = new Date().getFullYear()
</script>

<style scoped>
.lb-nav {
  position: sticky;
  top: 0;
  z-index: 40;
  background: rgba(253, 246, 236, 0.92);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--lb-line);
}
.lb-nav__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-block: 0.85rem;
}
.lb-nav__brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  text-decoration: none;
  color: var(--lb-ink);
  font-weight: 800;
}
.lb-nav__logo {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: var(--lb-brand);
  color: #fff;
  font-size: 1.1rem;
}
.lb-nav__logo-img {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  object-fit: cover;
  background: #fff;
  border: 1px solid var(--lb-line);
}
.lb-nav__word {
  font-family: 'Fraunces', Georgia, serif;
  font-size: 1.1rem;
  letter-spacing: -0.02em;
}
.lb-nav__links {
  display: none;
  align-items: center;
  gap: 1.35rem;
}
.lb-nav__link {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--lb-muted);
  text-decoration: none;
  padding-bottom: 2px;
  border-bottom: 2px solid transparent;
}
.lb-nav__link:hover { color: var(--lb-ink); }
.lb-nav__link.router-link-active,
.lb-nav__link.router-link-exact-active {
  color: var(--lb-ink);
  border-bottom-color: var(--lb-brand);
}
.lb-nav__actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
.lb-nav__cart {
  position: relative;
  width: 2.6rem;
  height: 2.6rem;
  display: grid;
  place-items: center;
  border-radius: 12px;
  border: 1px solid var(--lb-line);
  background: #fff;
  color: var(--lb-ink);
  text-decoration: none;
}
.lb-nav__cart:hover { border-color: var(--lb-brand); }
.lb-nav__cart-icon { width: 20px; height: 20px; }
.lb-nav__badge {
  position: absolute;
  top: -6px;
  right: -6px;
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  border-radius: 999px;
  background: var(--lb-brand);
  color: #fff;
  font-size: 0.7rem;
  font-weight: 800;
  display: grid;
  place-items: center;
  border: 2px solid var(--lb-bg);
}
.lb-nav__burger {
  width: 2.6rem;
  height: 2.6rem;
  border-radius: 12px;
  border: 1px solid var(--lb-line);
  background: #fff;
  cursor: pointer;
  font-size: 1.1rem;
}
.lb-nav__mobile {
  border-top: 1px solid var(--lb-line);
  background: #FFFBF0;
  padding: 0.65rem 1rem 0.85rem;
  display: grid;
  gap: 0.2rem;
}
.lb-nav__mobile-link {
  padding: 0.65rem 0.4rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--lb-muted);
  text-decoration: none;
  border-radius: 10px;
}
.lb-nav__mobile-link:hover { background: #fff; color: var(--lb-ink); }

.lb-footer {
  margin-top: 2rem;
  background: #2A1608;
  color: #FFD9A3;
}
.lb-footer__grid {
  display: grid;
  gap: 2rem;
  padding-block: 2.5rem;
}
.lb-footer__brand {
  font-family: 'Fraunces', Georgia, serif;
  font-size: 1.2rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.lb-footer__logo-img {
  width: 1.8rem;
  height: 1.8rem;
  border-radius: 50%;
  object-fit: cover;
  background: #fff;
}
.lb-footer__head {
  font-size: 0.85rem;
  font-weight: 800;
  color: #fff;
  margin: 0 0 0.6rem;
}
.lb-footer__muted {
  font-size: 0.88rem;
  line-height: 1.6;
  opacity: 0.85;
  margin: 0;
}
.lb-footer__links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 0.45rem;
}
.lb-footer__links a {
  color: inherit;
  text-decoration: none;
  opacity: 0.85;
  font-size: 0.88rem;
}
.lb-footer__links a:hover { opacity: 1; color: #fff; text-decoration: underline; text-underline-offset: 3px; }
.lb-footer__copy {
  border-top: 1px solid rgba(255,255,255,0.12);
  text-align: center;
  padding: 1rem;
  font-size: 0.78rem;
  opacity: 0.6;
}
@media (min-width: 820px) {
  .lb-nav__links { display: flex; }
  .lb-nav__burger,
  .lb-nav__mobile { display: none; }
  .lb-footer__grid { grid-template-columns: 1.4fr 0.8fr 0.9fr; }
}
</style>
