<template>
  <div class="min-h-screen flex bg-cream-50">
    <!-- Sidebar (desktop) -->
    <aside class="hidden md:flex md:flex-col w-64 shrink-0 bg-terracotta-900 text-cream-50">
      <div class="h-16 flex items-center gap-2 px-6 border-b border-white/10">
        <span class="text-xl">🍜</span>
        <span class="font-bold">LocalBowls</span>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="block px-3 py-2.5 rounded-lg text-sm font-medium transition"
          :class="isActive(item.to) ? 'bg-terracotta-700 text-white' : 'text-cream-100/70 hover:bg-white/5 hover:text-white'"
        >{{ item.label }}</router-link>
      </nav>

      <div class="p-4 border-t border-white/10">
        <p class="text-sm font-semibold text-white truncate">{{ authStore.user?.nama || 'Staff' }}</p>
        <p class="text-xs text-cream-100/60 capitalize mb-3">{{ roleLabel }}</p>
        <button
          @click="handleLogout"
          class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-cream-100/80 hover:bg-white/10 hover:text-white transition"
        >Keluar</button>
      </div>
    </aside>

    <!-- Mobile drawer -->
    <div v-if="mobileNavOpen" class="md:hidden fixed inset-0 z-50 flex">
      <div class="absolute inset-0 bg-black/40" @click="mobileNavOpen = false"></div>
      <aside class="relative w-64 bg-terracotta-900 text-cream-50 flex flex-col">
        <div class="h-16 flex items-center justify-between px-6 border-b border-white/10">
          <span class="font-bold">🍜 LocalBowls</span>
          <button @click="mobileNavOpen = false" class="text-cream-100/80">✕</button>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1">
          <router-link
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            @click="mobileNavOpen = false"
            class="block px-3 py-2.5 rounded-lg text-sm font-medium transition"
            :class="isActive(item.to) ? 'bg-terracotta-700 text-white' : 'text-cream-100/70 hover:bg-white/5 hover:text-white'"
          >{{ item.label }}</router-link>
        </nav>
        <div class="p-4 border-t border-white/10">
          <p class="text-sm font-semibold text-white truncate">{{ authStore.user?.nama || 'Staff' }}</p>
          <p class="text-xs text-cream-100/60 capitalize mb-3">{{ roleLabel }}</p>
          <button
            @click="handleLogout"
            class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-cream-100/80 hover:bg-white/10 hover:text-white transition"
          >Keluar</button>
        </div>
      </aside>
    </div>

    <!-- Main column -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Topbar -->
      <header class="h-16 shrink-0 flex items-center justify-between px-4 sm:px-6 bg-white border-b border-terracotta-100">
        <div class="flex items-center gap-3">
          <button
            @click="mobileNavOpen = true"
            class="md:hidden w-9 h-9 flex items-center justify-center rounded-lg border border-terracotta-100"
            aria-label="Menu"
          >☰</button>
          <h1 class="text-base sm:text-lg font-bold text-terracotta-900">{{ pageTitle }}</h1>
        </div>
        <div class="text-sm text-earth-dark/70 hidden sm:block">
          {{ authStore.user?.nama || 'Staff' }} · <span class="capitalize">{{ roleLabel }}</span>
        </div>
      </header>

      <!-- Konten dashboard -->
      <main class="flex-1 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth.js'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const mobileNavOpen = ref(false)

// Menu sidebar per role. Admin nanti bisa ditambah sub-menu (Kategori, Meja, User, Laporan)
// begitu AdminPage dipecah jadi beberapa halaman - untuk sekarang mengarah ke satu dashboard.
const navItems = computed(() => {
  switch (authStore.user?.role) {
    case 'waiter':
      return [{ to: '/waiter', label: 'Monitoring Meja' }]
    case 'kitchen':
      return [{ to: '/dapur', label: 'Dapur (KDS)' }]
    case 'kasir':
      return [{ to: '/kasir', label: 'Kasir' }]
    case 'admin':
      return [{ to: '/admin', label: 'Dashboard Admin' }]
    default:
      return []
  }
})

const roleLabel = computed(() => {
  const labels = { waiter: 'Waiter', kitchen: 'Dapur', kasir: 'Kasir', admin: 'Admin' }
  return labels[authStore.user?.role] || '-'
})

const pageTitle = computed(() => route.meta?.title?.split(' - ')[0] || 'Dashboard')

function isActive(to) {
  return route.path === to
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>