<template>
  <div class="lb-login">
    <!-- Panel kiri: brand -->
    <aside class="lb-brand">
      <div class="lb-brand__inner">
        <img src="/logo.png" alt="" class="lb-logo-img" />
        <h1 class="lb-brand__title">LocalBowls</h1>
        <p class="lb-brand__desc">Semangkuk mie hangat, dari dapur ke mejamu.</p>
        <ul class="lb-brand__points" aria-label="Fitur dashboard">
          <li><span class="dot" aria-hidden="true"></span>Dapur (KDS) real-time</li>
          <li><span class="dot" aria-hidden="true"></span>Kasir & struk digital</li>
          <li><span class="dot" aria-hidden="true"></span>Admin: menu, meja, laporan</li>
        </ul>
        <router-link to="/" class="lb-back"><span aria-hidden="true">←</span> Kembali ke Halaman Utama</router-link>
      </div>
      <div class="lb-brand__bowl" aria-hidden="true"></div>
    </aside>

    <!-- Panel kanan: form -->
    <main class="lb-form-wrap">
      <div class="lb-card">
        <p class="lb-eyebrow">Area Staf</p>
        <h2 class="lb-title">Masuk Dashboard</h2>
        <p class="lb-sub">Gunakan username + password akun staff kamu.</p>

        <form @submit.prevent="handleLogin" class="lb-form">
          <label class="lb-field">
            <span>Username</span>
            <input
              v-model="form.username"
              type="text"
              placeholder="cth: kasir"
              autocomplete="username"
              autocapitalize="none"
              spellcheck="false"
              required
            />
          </label>

          <label class="lb-field">
            <span>Password</span>
            <div class="lb-pass">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                autocomplete="current-password"
                required
              />
              <button
                type="button"
                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                :aria-pressed="showPassword"
                @click="showPassword = !showPassword"
              >
                {{ showPassword ? '🙈' : '👁️' }}
              </button>
            </div>
          </label>

          <label class="lb-remember">
            <input v-model="form.rememberMe" type="checkbox" />
            <span>Ingat saya</span>
          </label>

          <p v-if="errorMessage" id="login-error" class="lb-error" role="alert">{{ errorMessage }}</p>

          <button type="submit" :disabled="isLoading" :aria-busy="isLoading" class="lb-submit">
            {{ isLoading ? 'Memproses...' : 'Masuk Sekarang' }}
          </button>
        </form>

        <div v-if="demoAccounts.length" class="lb-quick">
          <p class="lb-quick__title">Login Cepat</p>
          <p class="lb-quick__sub">Isi formulir dengan akun demo yang tersedia.</p>
          <div class="lb-quick__grid">
            <button
              v-for="akun in demoAccounts"
              :key="akun.username"
              type="button"
              @click="isiOtomatis(akun)"
              class="lb-quick__btn"
            >
              <span class="lb-quick__icon">{{ akun.icon }}</span>
              <span>{{ akun.label }}</span>
            </button>
          </div>
        </div>
      </div>
    </main>
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
  username: localStorage.getItem('rememberMe') === 'true'
    ? localStorage.getItem('rememberedUsername') || ''
    : '',
  password: '',
  rememberMe: localStorage.getItem('rememberMe') === 'true',
})

const showPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

const demoAccounts = import.meta.env.DEV
  ? [
      { label: 'Dapur', icon: '🧑‍🍳', username: 'kitchen', password: 'kitchen123' },
      { label: 'Kasir', icon: '🧾', username: 'kasir', password: 'kasir123' },
      { label: 'Admin', icon: '⚙️', username: 'admin', password: 'admin123' },
    ]
  : []

function isiOtomatis(akun) {
  form.value.username = akun.username
  form.value.password = akun.password
  errorMessage.value = ''
}

const handleLogin = async () => {
  errorMessage.value = ''
  isLoading.value = true

  try {
    if (!form.value.username.trim() || !form.value.password) {
      throw new Error('Username dan password tidak boleh kosong')
    }

    await authStore.login(form.value.username.trim(), form.value.password)

    if (form.value.rememberMe) {
      localStorage.setItem('rememberMe', 'true')
      localStorage.setItem('rememberedUsername', form.value.username.trim())
    } else {
      localStorage.removeItem('rememberMe')
      localStorage.removeItem('rememberedUsername')
    }

    const requestedPath = typeof route.query.redirect === 'string'
      && route.query.redirect.startsWith('/')
      && !route.query.redirect.startsWith('//')
      ? route.query.redirect
      : ''
    const destination = requestedPath ? router.resolve(requestedPath) : null
    const allowedRoles = destination?.meta.roles || (destination?.meta.role ? [destination.meta.role] : null)
    const redirectPath = destination?.meta.requiresAuth && allowedRoles && !allowedRoles.includes(authStore.user?.role)
      ? authStore.roleHomePath()
      : requestedPath || authStore.roleHomePath()
    router.replace(redirectPath)
  } catch (error) {
    errorMessage.value = error.message || 'Login gagal. Silakan cek kredensial Anda.'
    console.error('Login error:', error)
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.lb-login {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr;
  background: var(--lb-bg);
  color: var(--lb-ink);
  font-family: 'Plus Jakarta Sans', sans-serif;
}

/* Brand panel */
.lb-brand {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, var(--lb-ink) 0%, #8B3A1A 55%, var(--lb-brand) 100%);
  color: #FFF;
  padding: 3rem 2rem;
  display: none;
}
.lb-brand__inner { position: relative; z-index: 1; max-width: 380px; }
.lb-logo-img {
  width: 56px; height: 56px; border-radius: 18px;
  object-fit: cover; margin-bottom: 1.25rem;
  background: var(--lb-card); border: 1px solid rgba(255,255,255,0.2);
}
.lb-brand__title { font-size: 2rem; font-weight: 800; margin: 0 0 0.5rem; }
.lb-brand__desc { opacity: 0.85; margin: 0 0 1.5rem; }
.lb-brand__points { list-style: none; padding: 0; margin: 0 0 2rem; display: grid; gap: 0.6rem; font-size: 0.9rem; }
.lb-brand__points li { display: flex; align-items: center; gap: 0.6rem; opacity: 0.9; }
.dot { width: 8px; height: 8px; border-radius: 50%; background: #FFC875; flex-shrink: 0; }
.lb-back { color: #FFD9A3; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
.lb-back:hover { color: #FFF; }
.lb-brand__bowl {
  position: absolute; right: -80px; bottom: -80px;
  width: 280px; height: 280px; border-radius: 50%;
  background: radial-gradient(circle at 35% 35%, rgba(255,255,255,0.25), transparent 60%);
  border: 24px solid rgba(255,255,255,0.08);
}

/* Form panel */
.lb-form-wrap {
  display: flex; align-items: center; justify-content: center;
  padding: 2.5rem 1.25rem;
}
.lb-card {
  width: 100%; max-width: 420px;
  background: var(--lb-card); border-radius: 24px;
  border: 1px solid var(--lb-line);
  box-shadow: var(--lb-shadow);
  padding: 2rem;
}
.lb-eyebrow {
  font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em;
  text-transform: uppercase; color: var(--lb-brand); margin: 0 0 0.35rem;
}
.lb-title { font-size: 1.6rem; font-weight: 800; margin: 0 0 0.25rem; }
.lb-sub { font-size: 0.9rem; color: var(--lb-muted); margin: 0 0 1.5rem; }

.lb-form { display: grid; gap: 1rem; }
.lb-field { display: grid; gap: 0.4rem; font-size: 0.85rem; font-weight: 600; }
.lb-field input {
  width: 100%; padding: 0.8rem 1rem; font-size: 0.95rem;
  border: 2px solid var(--lb-soft); border-radius: 14px;
  background: var(--lb-bg); color: var(--lb-ink); outline: none;
}
.lb-field input:focus { border-color: var(--lb-brand); background: var(--lb-card); }
.lb-pass { position: relative; }
.lb-pass button {
  position: absolute; right: 0.6rem; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; font-size: 1.1rem;
}
.lb-remember { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--lb-muted); cursor: pointer; }
.lb-remember input { accent-color: var(--lb-brand); width: 1rem; height: 1rem; }

.lb-error {
  margin: 0; font-size: 0.85rem; color: var(--lb-danger);
  background: rgba(192, 42, 42, 0.08);
  border: 1px solid rgba(192, 42, 42, 0.2);
  border-radius: 12px; padding: 0.7rem 1rem;
}
.lb-submit {
  padding: 0.9rem; border: none; border-radius: 999px;
  background: var(--lb-brand); color: #FFF;
  font-weight: 800; font-size: 1rem; cursor: pointer;
  box-shadow: 0 10px 24px rgba(217, 119, 87, 0.4);
}
.lb-submit:hover:not(:disabled) { background: var(--lb-brand-dark); }
.lb-submit:disabled { opacity: 0.6; cursor: not-allowed; }

.lb-quick { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px dashed var(--lb-line); }
.lb-quick__title { font-size: 0.85rem; font-weight: 700; margin: 0; }
.lb-quick__sub { font-size: 0.75rem; color: var(--lb-muted); margin: 0.15rem 0 0.75rem; }
.lb-quick__grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.6rem; }
.lb-quick__btn {
  display: flex; flex-direction: column; align-items: center; gap: 0.3rem;
  padding: 0.8rem 0.4rem; border-radius: 16px; cursor: pointer;
  border: 2px solid var(--lb-soft); background: var(--lb-bg);
  font-size: 0.8rem; font-weight: 700; color: var(--lb-ink);
}
.lb-quick__btn:hover { border-color: var(--lb-brand); background: var(--lb-card); }
.lb-quick__icon { font-size: 1.4rem; }

@media (min-width: 900px) {
  .lb-login { grid-template-columns: 1fr 1.2fr; }
  .lb-brand { display: block; }
}

.lb-login { min-height: 100svh; }
.lb-brand {
  display: flex;
  align-items: center;
  padding: clamp(2.5rem, 6vw, 5rem);
  background:
    radial-gradient(circle at 15% 85%, rgba(255, 200, 117, 0.18), transparent 28rem),
    linear-gradient(145deg, #282019 0%, #713b20 58%, #a9572f 100%);
}
.lb-brand__inner { max-width: 28rem; }
.lb-logo-img { width: 4rem; height: 4rem; border-radius: 1.25rem; box-shadow: 0 12px 30px rgba(0, 0, 0, 0.16); }
.lb-brand__title { font-size: clamp(2.1rem, 4vw, 3.1rem); letter-spacing: -0.04em; }
.lb-brand__desc { max-width: 24rem; font-size: 1.05rem; line-height: 1.7; }
.lb-brand__points { gap: 0.9rem; margin-bottom: 2.5rem; }
.lb-brand__points li { font-size: 0.95rem; }
.lb-back { display: inline-flex; align-items: center; gap: 0.45rem; border-radius: 8px; transition: color 0.18s ease, transform 0.18s ease; }
.lb-back:hover { transform: translateX(-2px); }
.lb-brand__bowl { width: 25rem; height: 25rem; right: -8rem; bottom: -9rem; border-width: 2rem; }
.lb-form-wrap { min-width: 0; padding: clamp(1rem, 4vw, 3rem); }
.lb-card { max-width: 28rem; padding: clamp(1.35rem, 4vw, 2.4rem); border-radius: 1.5rem; box-shadow: 0 22px 70px rgba(46, 31, 20, 0.11); }
.lb-eyebrow { color: var(--lb-brand-dark); }
.lb-title { font-size: clamp(1.55rem, 4vw, 1.9rem); letter-spacing: -0.035em; }
.lb-sub { line-height: 1.6; }
.lb-form { gap: 1.05rem; }
.lb-field { color: var(--lb-ink); font-weight: 750; }
.lb-field input { min-height: 3.15rem; border: 1px solid var(--lb-line); border-radius: 12px; background: #fff; transition: border-color 0.18s ease, box-shadow 0.18s ease; }
.lb-field input:focus { border-color: var(--lb-brand); box-shadow: 0 0 0 3px rgba(212, 130, 41, 0.13); }
.lb-pass input { padding-right: 3.2rem; }
.lb-pass button { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; right: 0.45rem; border-radius: 9px; color: var(--lb-muted); transition: background 0.18s ease; }
.lb-pass button:hover { background: var(--lb-soft); }
.lb-remember { width: fit-content; }
.lb-error { line-height: 1.5; }
.lb-submit { min-height: 3.2rem; border-radius: 12px; background: linear-gradient(135deg, var(--lb-brand) 0%, var(--lb-brand-dark) 100%); box-shadow: 0 9px 20px rgba(217, 119, 87, 0.24); transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease; }
.lb-submit:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 12px 24px rgba(217, 119, 87, 0.3); }
.lb-quick { margin-top: 1.7rem; }
.lb-quick__title { color: var(--lb-ink); font-size: 0.9rem; }
.lb-quick__sub { line-height: 1.5; }
.lb-quick__grid { gap: 0.65rem; }
.lb-quick__btn { min-height: 5.1rem; border: 1px solid var(--lb-line); border-radius: 13px; transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease; }
.lb-quick__btn:hover { transform: translateY(-2px); border-color: var(--lb-brand); box-shadow: 0 8px 16px rgba(64, 45, 30, 0.07); }
.lb-quick__icon { font-size: 1.3rem; }
.lb-login :is(a, button, input):focus-visible { outline: 3px solid rgba(212, 130, 41, 0.48); outline-offset: 3px; }

@media (min-width: 900px) {
  .lb-login { grid-template-columns: minmax(20rem, 0.9fr) minmax(30rem, 1.1fr); }
}
@media (max-width: 380px) {
  .lb-quick__grid { grid-template-columns: 1fr; }
  .lb-quick__btn { min-height: 3.4rem; flex-direction: row; justify-content: center; }
}
@media (prefers-reduced-motion: reduce) {
  .lb-login *,
  .lb-login *::before,
  .lb-login *::after { transition-duration: 0.01ms !important; }
}
</style>
