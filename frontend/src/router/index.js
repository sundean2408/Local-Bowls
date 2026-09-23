import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../store/auth.js'

// Views - Pelanggan
import HomePage from '../views/pelanggan/HomePage.vue'
import MenuPage from '../views/pelanggan/MenuPage.vue'
import KeranjangPage from '../views/pelanggan/KeranjangPage.vue'
import KonfirmasiPage from '../views/pelanggan/KonfirmasiPage.vue'
import StatusPage from '../views/pelanggan/StatusPage.vue'

// Views - Staff
import LoginPage from '../views/staff/LoginPage.vue'
import WaiterPage from '../views/staff/WaiterPage.vue'
import DapurPage from '../views/staff/DapurPage.vue'
import KasirPage from '../views/staff/KasirPage.vue'
import AdminPage from '../views/staff/AdminPage.vue'

const routes = [
  // Pelanggan Routes
  {
    path: '/',
    name: 'Home',
    component: HomePage,
    meta: { title: 'LocalBowls - Pesan Makanan Lezat' }
  },
  {
    path: '/menu',
    name: 'Menu',
    component: MenuPage,
    meta: { title: 'Menu - LocalBowls' }
  },
  {
    path: '/keranjang',
    name: 'Keranjang',
    component: KeranjangPage,
    meta: { title: 'Keranjang Belanja - LocalBowls' }
  },
  {
    path: '/konfirmasi',
    name: 'Konfirmasi',
    component: KonfirmasiPage,
    meta: { title: 'Konfirmasi Pesanan - LocalBowls' }
  },
  {
    path: '/status',
    name: 'Status',
    component: StatusPage,
    meta: { title: 'Status Pesanan - LocalBowls', requiresAuth: false }
  },

  // Staff Routes
  {
    path: '/login',
    name: 'Login',
    component: LoginPage,
    meta: { title: 'Login Staff - LocalBowls' }
  },
  {
    path: '/waiter',
    name: 'Waiter',
    component: WaiterPage,
    meta: { title: 'Waiter Dashboard - LocalBowls', requiresAuth: true, role: 'waiter' }
  },
  {
    path: '/dapur',
    name: 'Dapur',
    component: DapurPage,
    meta: { title: 'Kitchen Dashboard - LocalBowls', requiresAuth: true, role: 'kitchen' }
  },
  {
    path: '/kasir',
    name: 'Kasir',
    component: KasirPage,
    meta: { title: 'Cashier Dashboard - LocalBowls', requiresAuth: true, role: 'kasir' }
  },
  {
    path: '/admin',
    name: 'Admin',
    component: AdminPage,
    meta: { title: 'Admin Dashboard - LocalBowls', requiresAuth: true, role: 'admin' }
  },

  // Catch all - redirect to home
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Guard untuk proteksi staff routes
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const requiresAuth = to.meta.requiresAuth
  const requiredRole = to.meta.role

  if (requiresAuth) {
    if (!authStore.isAuthenticated) {
      next('/login')
    } else if (requiredRole && authStore.user?.role !== requiredRole) {
      next('/login')
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router