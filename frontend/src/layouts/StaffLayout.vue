<template>
  <div class="min-h-screen flex staff">
    <aside class="hidden md:flex md:flex-col w-64 shrink-0 staff__side">
      <div class="staff__brand">
        <img src="/logo.png" alt="LocalBowls" class="staff__logo-img" />
        <span class="staff__word">LocalBowls</span>
      </div>

      <nav class="staff__nav">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="staff__link"
          :class="{ 'is-active': isActive(item.to) }"
        >
          <span class="staff__link-icon" aria-hidden="true">{{ item.icon }}</span>
          {{ item.label }}
        </router-link>
      </nav>

      <div class="staff__user">
        <p class="staff__user-name">{{ authStore.user?.nama || 'Staff' }}</p>
        <p class="staff__user-role">{{ roleLabel }}</p>
        <button type="button" @click="handleLogout" class="staff__logout">Keluar</button>
      </div>
    </aside>

    <div v-if="mobileNavOpen" class="md:hidden fixed inset-0 z-50 flex">
      <div class="absolute inset-0 bg-black/40" @click="mobileNavOpen = false"></div>
      <aside class="relative w-64 staff__side flex flex-col">
        <div class="staff__brand">
          <img src="/logo.png" alt="LocalBowls" class="staff__logo-img" />
          <span class="staff__word">LocalBowls</span>
          <button type="button" @click="mobileNavOpen = false" class="staff__close" aria-label="Tutup menu">✕</button>
        </div>
        <nav class="staff__nav">
          <router-link
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            @click="mobileNavOpen = false"
            class="staff__link"
            :class="{ 'is-active': isActive(item.to) }"
          >
            <span class="staff__link-icon" aria-hidden="true">{{ item.icon }}</span>
            {{ item.label }}
          </router-link>
        </nav>
        <div class="staff__user">
          <p class="staff__user-name">{{ authStore.user?.nama || 'Staff' }}</p>
          <p class="staff__user-role">{{ roleLabel }}</p>
          <button type="button" @click="handleLogout" class="staff__logout">Keluar</button>
        </div>
      </aside>
    </div>

    <div class="flex-1 flex flex-col min-w-0">
      <header class="staff__topbar">
        <div class="staff__topbar-left">
          <button
            type="button"
            @click="mobileNavOpen = true"
            class="md:hidden staff__burger"
            aria-label="Menu"
          >☰</button>
          <h1 class="staff__title">{{ pageTitle }}</h1>
        </div>
        <p class="staff__who">
          {{ authStore.user?.nama || 'Staff' }} · <span class="capitalize">{{ roleLabel }}</span>
        </p>
      </header>

      <main class="flex-1 overflow-y-auto staff__main">
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

const navItems = computed(() => {
  switch (authStore.user?.role) {
    case 'kitchen':
      return [{ to: '/dapur', label: 'Dapur (KDS)', icon: '🍳' }]
    case 'kasir':
      return [{ to: '/kasir', label: 'Kasir', icon: '🧾' }]
    case 'admin':
      return [{ to: '/admin', label: 'Dashboard Admin', icon: '📊' }]
    default:
      return []
  }
})

const roleLabel = computed(() => {
  const labels = { kitchen: 'Dapur', kasir: 'Kasir', admin: 'Admin' }
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

<style scoped>
.staff {
  background: var(--lb-bg);
  color: var(--lb-ink);
}
.staff__side {
  background: linear-gradient(180deg, #341a08 0%, #4a2310 60%, #5c2b12 100%);
  color: #FFD9A3;
}
.staff__brand {
  height: 4rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding-inline: 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  font-weight: 800;
}
.staff__brand .staff__word {
  font-family: 'Fraunces', Georgia, serif;
  font-size: 1.05rem;
  color: #fff;
}
.staff__logo-img {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  object-fit: cover;
  background: #fff;
  border: 1px solid rgba(255, 255, 255, 0.3);
  flex-shrink: 0;
}
.staff__close {
  margin-left: auto;
  background: none;
  border: none;
  color: inherit;
  font-size: 1rem;
  cursor: pointer;
}
.staff__nav {
  flex: 1;
  padding: 0.9rem 0.75rem;
  display: grid;
  gap: 0.25rem;
  align-content: start;
}
.staff__link {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 0.8rem;
  border-radius: 12px;
  font-size: 0.88rem;
  font-weight: 600;
  text-decoration: none;
  color: rgba(255, 217, 163, 0.72);
}
.staff__link:hover { background: rgba(255, 255, 255, 0.07); color: #fff; }
.staff__link.is-active { background: var(--lb-brand); color: #fff; }
.staff__link-icon { font-size: 1rem; }
.staff__user {
  padding: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}
.staff__user-name {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: #fff;
}
.staff__user-role {
  margin: 0.15rem 0 0.75rem;
  font-size: 0.75rem;
  text-transform: capitalize;
  opacity: 0.65;
}
.staff__logout {
  width: 100%;
  text-align: left;
  padding: 0.6rem 0.8rem;
  border-radius: 10px;
  border: none;
  background: transparent;
  color: inherit;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}
.staff__logout:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

.staff__topbar {
  height: 4rem;
  flex-shrink: 0;
  position: sticky;
  top: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-inline: 1rem;
  background: #fff;
  border-bottom: 1px solid var(--lb-line);
}
.staff__topbar-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}
.staff__burger {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 10px;
  border: 1px solid var(--lb-line);
  background: #fff;
  cursor: pointer;
}
.staff__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.staff__who {
  display: none;
  margin: 0;
  font-size: 0.85rem;
  color: var(--lb-muted);
}
.staff__main {
  padding: 1.25rem 1rem 3rem;
}
@media (min-width: 640px) {
  .staff__topbar { padding-inline: 1.5rem; }
  .staff__who { display: block; }
  .staff__main { padding: 1.5rem 1.5rem 3rem; }
}
</style>
