import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api.js'

function loadUser() {
  try {
    const user = JSON.parse(localStorage.getItem('user') || 'null')
    return user && typeof user === 'object' ? user : null
  } catch {
    localStorage.removeItem('user')
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  // State (dipulihkan dari localStorage supaya login bertahan saat refresh)
  const user = ref(loadUser())
  const token = ref(localStorage.getItem('authToken') || null)

  const isAuthenticated = computed(() => !!token.value)

  // Login ke backend Laravel (Sanctum) - POST /api/login { username, password }
  async function login(username, password) {
    const res = await api.post('/login', { username, password })

    token.value = res.token
    user.value = res.user

    localStorage.setItem('authToken', res.token)
    localStorage.setItem('user', JSON.stringify(res.user))
    localStorage.setItem('nama_user', res.user?.nama || '')

    return res.user
  }

  async function logout() {
    try {
      await api.post('/logout', {})
    } catch (e) {
      // Token mungkin sudah kadaluarsa di server, tetap lanjut hapus sesi lokal
    }

    token.value = null
    user.value = null
    localStorage.removeItem('authToken')
    localStorage.removeItem('user')
    localStorage.removeItem('nama_user')
  }

  // Halaman dashboard sesuai role user yang login
  function roleHomePath() {
    switch (user.value?.role) {
      case 'admin':
        return '/admin'
      case 'kitchen':
        return '/dapur'
      case 'kasir':
        return '/kasir'
      default:
        return '/login'
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    logout,
    roleHomePath,
  }
})