<template>
  <div class="min-h-screen flex flex-col bg-cream-50">
    <!-- Navbar -->
    <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-terracotta-100 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
        <!-- Logo -->
        <router-link :to="{ path: '/', query: mejaQuery }" class="flex items-center gap-2 shrink-0">
          <img src="/logo.png" alt="LocalBowls" class="w-9 h-9 rounded-full object-cover" />
          <span class="text-lg font-bold text-terracotta-800 hidden sm:inline">LocalBowls</span>
        </router-link>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center gap-6">
          <router-link
            :to="{ path: '/', query: mejaQuery }"
            class="text-sm font-medium text-earth-dark hover:text-terracotta-600 transition"
          >Beranda</router-link>
          <router-link
            :to="{ path: '/menu', query: mejaQuery }"
            class="text-sm font-medium text-earth-dark hover:text-terracotta-600 transition"
          >Menu</router-link>
          <router-link
            :to="{ path: '/status', query: mejaQuery }"
            class="text-sm font-medium text-earth-dark hover:text-terracotta-600 transition"
          >Status Pesanan</router-link>
        </div>

        <!-- Info Meja & Pelanggan + Cart + Burger -->
        <div class="flex items-center gap-3">
          <div v-if="nomorMeja" class="hidden sm:flex flex-col items-end leading-tight">
            <span class="text-xs text-earth-dark/70">{{ namaPelanggan || 'Tamu' }}</span>
            <span class="text-xs font-semibold text-terracotta-700">Meja {{ nomorMeja }}</span>
          </div>

          <router-link :to="{ path: '/keranjang', query: mejaQuery }" class="relative">
            <button
              class="relative w-10 h-10 flex items-center justify-center rounded-lg border border-terracotta-100 hover:bg-terracotta-50 transition"
              aria-label="Keranjang"
            >
              <span class="text-lg">🛒</span>
              <span
                v-if="cartStore.itemCount > 0"
                class="absolute -top-1.5 -right-1.5 bg-terracotta-600 text-white text-[10px] font-bold min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center"
              >{{ cartStore.itemCount }}</span>
            </button>
          </router-link>

          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="md:hidden w-10 h-10 flex items-center justify-center rounded-lg border border-terracotta-100 hover:bg-terracotta-50 transition"
            aria-label="Menu"
          >
            <span class="text-lg">{{ mobileMenuOpen ? '✕' : '☰' }}</span>
          </button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div v-if="mobileMenuOpen" class="md:hidden bg-cream-50 border-t border-terracotta-100 px-4 py-3 flex flex-col gap-1">
        <div v-if="nomorMeja" class="pb-2 mb-1 border-b border-terracotta-100 text-sm">
          <span class="text-earth-dark/70">{{ namaPelanggan || 'Tamu' }}</span>
          <span class="font-semibold text-terracotta-700"> · Meja {{ nomorMeja }}</span>
        </div>
        <router-link @click="mobileMenuOpen = false" :to="{ path: '/', query: mejaQuery }" class="py-2 text-sm font-medium text-earth-dark">Beranda</router-link>
        <router-link @click="mobileMenuOpen = false" :to="{ path: '/menu', query: mejaQuery }" class="py-2 text-sm font-medium text-earth-dark">Menu</router-link>
        <router-link @click="mobileMenuOpen = false" :to="{ path: '/status', query: mejaQuery }" class="py-2 text-sm font-medium text-earth-dark">Status Pesanan</router-link>
      </div>
    </nav>

    <!-- Konten halaman -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-terracotta-900 text-cream-100 mt-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 grid grid-cols-1 sm:grid-cols-3 gap-8 text-sm">
        <div>
          <p class="text-lg font-bold text-white mb-2">🍜 LocalBowls</p>
          <p class="text-cream-100/80">Semangkuk mie hangat, dari dapur ke mejamu.</p>
        </div>
        <div>
          <p class="font-semibold text-white mb-2">Menu</p>
          <ul class="space-y-1 text-cream-100/80">
            <li><router-link :to="{ path: '/menu', query: mejaQuery }" class="hover:text-white">Lihat Menu</router-link></li>
            <li><router-link :to="{ path: '/status', query: mejaQuery }" class="hover:text-white">Status Pesanan</router-link></li>
          </ul>
        </div>
        <div>
          <p class="font-semibold text-white mb-2">Kontak</p>
          <ul class="space-y-1 text-cream-100/80">
            <li>Yogyakarta, Indonesia</li>
            <li>info@localbowls.id</li>
          </ul>
        </div>
      </div>
      <div class="border-t border-white/10 py-4 text-center text-xs text-cream-100/60">
        &copy; {{ currentYear }} LocalBowls. All rights reserved.
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '../store/cart.js'

const route = useRoute()
const cartStore = useCartStore()
const mobileMenuOpen = ref(false)
const currentYear = new Date().getFullYear()

// Nomor meja: prioritas dari query ?meja=, fallback ke sesi yang tersimpan
// di cartStore supaya tetap terbawa walau berpindah halaman tanpa query.
const nomorMeja = computed(() => route.query.meja || cartStore.customerInfo.table || '')
const namaPelanggan = computed(() => cartStore.customerInfo.name)
const mejaQuery = computed(() => (nomorMeja.value ? { meja: nomorMeja.value } : {}))

watch(
  () => route.query.meja,
  (meja) => {
    if (meja) cartStore.setCustomerInfo({ table: meja })
  },
  { immediate: true }
)
</script>