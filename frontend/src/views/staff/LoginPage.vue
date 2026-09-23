<template>
  <div class="min-h-screen bg-gradient-to-br from-cream-50 via-terracotta-50 to-orange-100 flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
      <!-- Logo & Title -->
      <div class="text-center mb-8">
        <div class="flex justify-center mb-4">
          <div class="w-16 h-16 bg-gradient-to-br from-terracotta-500 to-orange-600 rounded-full flex items-center justify-center shadow-lg">
            <span class="text-white text-4xl">🍜</span>
          </div>
        </div>
        <h1 class="text-4xl font-bold text-terracotta-800 mb-2">LocalBowls</h1>
        <p class="text-terracotta-600">Staff Login</p>
      </div>

      <!-- Login Card -->
      <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-terracotta-100">
        <!-- Header -->
        <div class="bg-gradient-to-r from-terracotta-600 to-orange-500 px-8 py-6">
          <h2 class="text-2xl font-bold text-white mb-1">Masuk sebagai Staff</h2>
          <p class="text-cream-100 text-sm">Akses dashboard operasional</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleLogin" class="p-8">
          <!-- Email/Username -->
          <div class="mb-6">
            <label class="block text-sm font-semibold text-terracotta-800 mb-2">
              Email / Username
            </label>
            <input
              v-model="form.email"
              type="text"
              placeholder="nama@localbowls.id"
              class="w-full px-4 py-3 border-2 border-cream-200 rounded-lg focus:outline-none focus:border-terracotta-500 bg-cream-50 text-terracotta-900 placeholder-terracotta-400"
              required
            />
          </div>

          <!-- Password -->
          <div class="mb-6">
            <label class="block text-sm font-semibold text-terracotta-800 mb-2">
              Password
            </label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                class="w-full px-4 py-3 border-2 border-cream-200 rounded-lg focus:outline-none focus:border-terracotta-500 bg-cream-50 text-terracotta-900 placeholder-terracotta-400"
                required
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-3 text-terracotta-600 hover:text-terracotta-800"
              >
                <svg v-if="!showPassword" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                  <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                  <path d="M15.171 13.576l1.472 1.473a2.097 2.097 0 01-.97.242 2.097 2.097 0 01-1.472-.61l-1.514-1.514a4 4 0 002.484-.591z" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Remember Me -->
          <div class="mb-6">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="form.rememberMe"
                type="checkbox"
                class="w-4 h-4 accent-terracotta-600"
              />
              <span class="text-sm text-terracotta-700">Ingat saya</span>
            </label>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700 text-sm font-medium">{{ errorMessage }}</p>
          </div>

          <!-- Loading State -->
          <div v-if="isLoading" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-blue-700 text-sm font-medium">Sedang memproses login...</p>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full bg-gradient-to-r from-terracotta-600 to-orange-500 hover:from-terracotta-700 hover:to-orange-600 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold py-3 rounded-lg transition duration-200 flex items-center justify-center gap-2"
          >
            <span v-if="!isLoading">Masuk Sekarang</span>
            <span v-else>Memproses...</span>
          </button>
        </form>

        <!-- Demo Credentials -->
        <div class="px-8 pb-8 border-t border-cream-200">
          <p class="text-sm text-terracotta-600 mb-1 font-semibold pt-6">Login Cepat:</p>
          <p class="text-xs text-terracotta-500 mb-3">Klik salah satu untuk mengisi form otomatis</p>
          <div class="grid grid-cols-2 gap-3">
            <button
              v-for="akun in demoAccounts"
              :key="akun.email"
              type="button"
              @click="isiOtomatis(akun)"
              class="flex flex-col items-center justify-center gap-1.5 py-4 rounded-xl border-2 border-cream-200 bg-cream-50 hover:border-terracotta-400 hover:bg-terracotta-50 transition"
            >
              <span class="text-2xl">{{ akun.icon }}</span>
              <span class="text-sm font-bold text-terracotta-800">{{ akun.label }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Footer Links -->
      <div class="mt-8 text-center">
        <router-link
          to="/"
          class="inline-flex items-center gap-2 text-terracotta-700 hover:text-terracotta-900 font-medium transition"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Kembali ke Halaman Utama
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../store/auth.js'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: '',
  rememberMe: false
})

const showPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

// Daftar akun demo yang ditampilkan di bawah form. Klik salah satu baris
// otomatis mengisi field Email/Username & Password di atas -- nggak perlu
// ngetik manual tiap kali mau tes akun yang berbeda.
const demoAccounts = [
  { label: 'Waiter', icon: '🛎️', email: 'waiter', password: 'waiter123' },
  { label: 'Kitchen', icon: '🧑‍🍳', email: 'kitchen', password: 'kitchen123' },
  { label: 'Kasir', icon: '🧾', email: 'kasir', password: 'kasir123' },
  { label: 'Admin', icon: '⚙️', email: 'admin', password: 'admin123' },
]

function isiOtomatis(akun) {
  form.value.email = akun.email
  form.value.password = akun.password
  errorMessage.value = ''
}

const handleLogin = async () => {
  errorMessage.value = ''
  isLoading.value = true

  try {
    if (form.value.email.length === 0 || form.value.password.length === 0) {
      throw new Error('Username dan password tidak boleh kosong')
    }

    // Login ke backend Laravel (Sanctum). Field "Email / Username" dikirim
    // sebagai username karena backend memvalidasi kolom `username`.
    const user = await authStore.login(form.value.email, form.value.password)

    if (form.value.rememberMe) {
      localStorage.setItem('rememberMe', 'true')
      localStorage.setItem('rememberedEmail', form.value.email)
    }

    // Redirect sesuai role yang dikembalikan backend
    const redirectPath = route.query.redirect || authStore.roleHomePath()
    router.push(redirectPath)

  } catch (error) {
    errorMessage.value = error.message || 'Login gagal. Silakan cek kredensial Anda.'
    console.error('Login error:', error)
  } finally {
    isLoading.value = false
  }
}

// Load remembered email if exists
if (localStorage.getItem('rememberMe') === 'true') {
  form.value.email = localStorage.getItem('rememberedEmail') || ''
  form.value.rememberMe = true
}
</script>

<style scoped>
input[type="radio"] {
  appearance: none;
}
</style>