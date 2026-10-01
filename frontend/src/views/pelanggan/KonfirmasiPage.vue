<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { getImageUrl } from '@/services/api'
import { useCartStore } from '@/store/cart'
import PageHeader from '@/components/PageHeader.vue'

const router = useRouter()
const cart = useCartStore()

const items = computed(() => cart.items ?? [])
const totalHarga = computed(() => cart.totalHarga ?? 0)
const totalItem = computed(() => items.value.reduce((n, i) => n + (i.qty || 0), 0))

const daftarMeja = ref([])
const loadingMeja = ref(true)
const idMeja = ref('')
const namaPemesan = ref('')
const submitting = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

const mejaDipilih = computed(() => daftarMeja.value.find((m) => String(m.id) === String(idMeja.value)))

const langkah = computed(() => [
  { no: 1, label: 'Meja', done: !!idMeja.value },
  { no: 2, label: 'Pemesan', done: namaPemesan.value.trim().length > 0 },
  { no: 3, label: 'Ringkasan', done: items.value.length > 0 && !!idMeja.value },
])

const fmt = (h) => new Intl.NumberFormat('id-ID').format(h || 0)

// Cart menyimpan path gambar asli dari API — pakai itu dulu (pasti benar),
// baru fallback ke /logo.png bila gagal.
function imgSrc(item) {
  return item.gambar ? getImageUrl(item.gambar) : '/logo.png'
}
function handleImgError(event) {
  const img = event.target
  if (img.dataset.fallback) return
  img.dataset.fallback = '1'
  img.src = '/logo.png'
}

function isTerisi(meja) {
  return String(meja.status_meja ?? '').toLowerCase() === 'terisi'
}
function isDipilih(meja) {
  return String(idMeja.value) === String(meja.id)
}
function pilihMeja(meja) {
  if (isTerisi(meja)) return
  idMeja.value = String(meja.id)
  if (errorMsg.value.includes('meja')) errorMsg.value = ''
}

async function loadMeja() {
  loadingMeja.value = true
  try {
    const res = await api.get('/meja')
    const semua = Array.isArray(res) ? res : (res.data ?? [])
    daftarMeja.value = Array.isArray(semua) ? semua : []
  } catch (e) {
    console.error('Gagal ambil daftar meja:', e)
    daftarMeja.value = []
  } finally {
    loadingMeja.value = false
  }
}

function goBack() {
  router.push('/keranjang')
}

async function submitPesanan() {
  errorMsg.value = ''
  successMsg.value = ''

  if (!idMeja.value) {
    errorMsg.value = 'Pilih nomor meja dulu sebelum konfirmasi pesanan.'
    document.getElementById('step-meja')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    return
  }
  if (items.value.length === 0) {
    errorMsg.value = 'Keranjang masih kosong.'
    return
  }
  if (!namaPemesan.value.trim()) {
    errorMsg.value = 'Isi nama pemesan dulu — dipakai dapur & kasir untuk memanggil pesanan.'
    document.getElementById('step-pemesan')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    return
  }

  submitting.value = true
  try {
    const res = await api.post('/pesanan', {
      id_meja: idMeja.value,
      nama_pelanggan: namaPemesan.value.trim(),
      items: items.value.map((i) => ({
        id_menu: i.id,
        jumlah: i.qty,
        catatan: i.catatan || null,
      })),
    })

    const pesanan = Array.isArray(res) ? res[0] : (res.data ?? res)
    const idPesanan = pesanan?.id_pesanan ?? pesanan?.id ?? ''
    if (idPesanan) localStorage.setItem('lastPesananId', String(idPesanan))
    localStorage.setItem('lastMejaId', String(idMeja.value))

    cart.clear()
    successMsg.value = 'Pesanan berhasil dikirim! Menunggu diproses dapur.'

    setTimeout(() => {
      router.push({ path: '/status', query: { meja: idMeja.value } })
    }, 1200)
  } catch (e) {
    errorMsg.value = e.response?.data?.message || e.message || 'Gagal mengirim pesanan. Coba lagi.'
    console.error('Gagal submit pesanan:', e)
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  if (items.value.length === 0) {
    router.push('/menu')
    return
  }
  loadMeja()
})
</script>

<template>
  <div class="lb-wrap konfirm">
    <PageHeader eyebrow="Langkah terakhir" title="Konfirmasi Pesanan" :subtitle="`${totalItem} item · Rp ${fmt(totalHarga)}`">
      <template #action>
        <button type="button" class="lb-btn-ghost" @click="goBack">← Keranjang</button>
      </template>
    </PageHeader>

    <!-- Stepper garis -->
    <ol class="konfirm__steps">
      <li
        v-for="(s, i) in langkah"
        :key="s.no"
        class="konfirm__step"
        :class="{ 'is-done': s.done, 'is-current': i === (idMeja ? (namaPemesan.trim() ? 2 : 1) : 0) }"
      >
        <span class="konfirm__dot">{{ s.done ? '✓' : s.no }}</span>
        <span class="konfirm__cap">{{ s.label }}</span>
        <span v-if="i < langkah.length - 1" class="konfirm__line" :class="{ 'is-done': s.done }"></span>
      </li>
    </ol>

    <div v-if="items.length === 0" class="lb-empty">
      <p class="konfirm__empty-title">Keranjang masih kosong.</p>
      <router-link to="/menu" class="lb-btn-primary konfirm__empty-cta">Lihat Menu</router-link>
    </div>

    <div v-else class="konfirm__grid">
      <div class="konfirm__main">
        <!-- 1 MEJA -->
        <section id="step-meja" class="lb-card konfirm__card">
          <header class="konfirm__head">
            <span class="konfirm__num">1</span>
            <div class="konfirm__head-text">
              <h2>Pilih Meja</h2>
              <p>Pilih nomor meja tempat kamu duduk.</p>
            </div>
            <span v-if="mejaDipilih" class="konfirm__picked">Meja {{ mejaDipilih.nomor_meja }}</span>
          </header>

          <div v-if="loadingMeja" class="konfirm__meja-grid">
            <div v-for="i in 8" :key="i" class="lb-skeleton konfirm__meja-sk"></div>
          </div>
          <div v-else-if="daftarMeja.length > 0" class="konfirm__meja-grid">
            <button
              v-for="meja in daftarMeja"
              :key="meja.id"
              type="button"
              :disabled="isTerisi(meja)"
              @click="pilihMeja(meja)"
              class="konfirm__meja"
              :class="{ 'is-picked': isDipilih(meja), 'is-full': isTerisi(meja) }"
              :aria-pressed="isDipilih(meja)"
            >
              <span class="konfirm__meja-no">{{ meja.nomor_meja }}</span>
              <span class="konfirm__meja-state">{{ isTerisi(meja) ? 'Terisi' : isDipilih(meja) ? 'Dipilih' : 'Kosong' }}</span>
            </button>
          </div>
          <div v-else class="lb-alert lb-alert--error">
            Daftar meja belum bisa dimuat.
            <button type="button" class="konfirm__link" @click="loadMeja">Muat ulang</button>
          </div>
        </section>

        <!-- 2 PEMESAN -->
        <section id="step-pemesan" class="lb-card konfirm__card">
          <header class="konfirm__head">
            <span class="konfirm__num">2</span>
            <div class="konfirm__head-text">
              <h2>Data Pemesan</h2>
              <p>Nama dipakai dapur & kasir untuk memanggil pesanan.</p>
            </div>
          </header>
          <input v-model="namaPemesan" type="text" class="lb-input" placeholder="Nama pemesan (wajib diisi)" maxlength="60" required />
        </section>

        <!-- 3 ITEM -->
        <section class="lb-card konfirm__card">
          <header class="konfirm__head">
            <span class="konfirm__num">3</span>
            <div class="konfirm__head-text">
              <h2>Item Pesanan</h2>
              <p>{{ totalItem }} item dalam pesanan ini.</p>
            </div>
          </header>
          <ul class="konfirm__items">
            <li v-for="item in items" :key="item.id" class="konfirm__item">
              <img
                :src="imgSrc(item)"
                :alt="item.nama_menu"
                class="konfirm__thumb"
                loading="lazy"
                @error="handleImgError"
              />
              <div class="konfirm__body">
                <div class="konfirm__top">
                  <h3>{{ item.nama_menu }}</h3>
                  <button type="button" class="konfirm__remove" @click="cart.removeItem(item.id)" :aria-label="`Hapus ${item.nama_menu}`">✕</button>
                </div>
                <p class="konfirm__meta">{{ item.qty }}× @ Rp {{ fmt(item.harga) }}</p>
                <input v-model="item.catatan" type="text" class="konfirm__note" placeholder="Catatan (opsional)" maxlength="120" />
                <p class="konfirm__sub">Rp {{ fmt(item.qty * item.harga) }}</p>
              </div>
            </li>
          </ul>
        </section>
      </div>

      <!-- RINGKASAN -->
      <aside class="konfirm__side">
        <div class="lb-card konfirm__summary">
          <h2>Ringkasan</h2>
          <dl>
            <div><dt>Pemesan</dt><dd>{{ namaPemesan.trim() || 'Tamu' }}</dd></div>
            <div><dt>Meja</dt><dd :class="{ 'is-warn': !mejaDipilih }">{{ mejaDipilih?.nomor_meja || 'Belum dipilih' }}</dd></div>
            <div><dt>Item</dt><dd>{{ totalItem }} item</dd></div>
          </dl>
          <div class="konfirm__total">
            <span>Total bayar</span>
            <strong>Rp {{ fmt(totalHarga) }}</strong>
          </div>

          <p v-if="errorMsg" class="lb-alert lb-alert--error">{{ errorMsg }}</p>
          <p v-if="successMsg" class="lb-alert lb-alert--ok">{{ successMsg }}</p>

          <button type="button" class="lb-btn-primary konfirm__submit" :disabled="submitting || !idMeja || !namaPemesan.trim()" @click="submitPesanan">
            {{ submitting ? 'Mengirim...' : !idMeja ? 'Pilih Meja Dulu' : !namaPemesan.trim() ? 'Isi Nama Dulu' : 'Konfirmasi Pesanan' }}
          </button>
          <p class="konfirm__hint">Bayar langsung di kasir setelah makan.</p>
        </div>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.konfirm { padding-block: 0.5rem 3rem; }

/* Stepper: titik + garis penghubung */
.konfirm__steps {
  list-style: none; margin: 0 0 1.25rem; padding: 0;
  display: flex; align-items: flex-start;
}
.konfirm__step {
  flex: 1; display: flex; align-items: center; gap: 0.55rem;
  min-width: 0; position: relative;
}
.konfirm__dot {
  width: 1.75rem; height: 1.75rem; border-radius: 50%;
  display: grid; place-items: center; flex-shrink: 0;
  font-size: 0.8rem; font-weight: 800;
  background: #fff; border: 1.5px solid var(--lb-line); color: var(--lb-faint);
}
.konfirm__cap { font-size: 0.82rem; font-weight: 700; color: var(--lb-faint); white-space: nowrap; }
.konfirm__line { flex: 1; height: 2px; background: var(--lb-line); border-radius: 2px; margin-inline: 0.55rem; min-width: 0.75rem; }
.konfirm__line.is-done { background: var(--lb-ok); }
.konfirm__step.is-done .konfirm__dot { background: var(--lb-ok); border-color: var(--lb-ok); color: #fff; }
.konfirm__step.is-done .konfirm__cap { color: var(--lb-ink); }
.konfirm__step.is-current .konfirm__dot { background: var(--lb-brand); border-color: var(--lb-brand); color: #fff; }
.konfirm__step.is-current .konfirm__cap { color: var(--lb-ink); }

.konfirm__empty-title { margin: 0 0 1rem; font-weight: 800; color: var(--lb-ink); }
.konfirm__empty-cta { text-decoration: none; }

.konfirm__grid { display: grid; gap: 1rem; align-items: start; }
@media (min-width: 960px) {
  .konfirm__grid { grid-template-columns: minmax(0, 1.55fr) minmax(280px, 0.9fr); }
  .konfirm__side { position: sticky; top: 5rem; }
}
.konfirm__main { display: grid; gap: 1rem; min-width: 0; }
.konfirm__card { padding: 1.25rem; }
@media (min-width: 640px) { .konfirm__card { padding: 1.5rem; } }

/* Kepala kartu: nomor + teks sejajar */
.konfirm__head { display: flex; align-items: flex-start; gap: 0.8rem; margin-bottom: 1rem; }
.konfirm__num {
  width: 1.75rem; height: 1.75rem; border-radius: 50%;
  display: grid; place-items: center; flex-shrink: 0;
  background: var(--lb-ink); color: #fff; font-size: 0.82rem; font-weight: 800;
  margin-top: 0.1rem;
}
.konfirm__head-text { flex: 1; min-width: 0; }
.konfirm__head-text h2 { margin: 0; font-size: 1.05rem; font-weight: 800; }
.konfirm__head-text p { margin: 0.2rem 0 0; font-size: 0.84rem; color: var(--lb-muted); }
.konfirm__picked {
  flex-shrink: 0; font-size: 0.76rem; font-weight: 800; color: #fff;
  background: var(--lb-brand); border-radius: 999px; padding: 0.35rem 0.8rem; white-space: nowrap;
}

/* Grid meja rata */
.konfirm__meja-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.55rem; }
@media (min-width: 640px) { .konfirm__meja-grid { grid-template-columns: repeat(6, 1fr); } }
.konfirm__meja-sk { height: 4.2rem; }
.konfirm__meja {
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.15rem;
  min-height: 4.2rem; padding: 0.6rem 0.3rem; border-radius: 14px;
  border: 1.5px solid var(--lb-line); background: #FFFBF0; cursor: pointer;
}
.konfirm__meja-no { font-weight: 800; font-size: 1.02rem; line-height: 1; }
.konfirm__meja-state { font-size: 0.68rem; color: var(--lb-muted); }
.konfirm__meja.is-picked { background: var(--lb-brand); border-color: var(--lb-brand); color: #fff; }
.konfirm__meja.is-picked .konfirm__meja-state { color: rgba(255,255,255,0.85); }
.konfirm__meja.is-full { background: #F3EFE7; color: var(--lb-faint); cursor: not-allowed; }
.konfirm__link { background: none; border: none; padding: 0; color: inherit; font-weight: 800; text-decoration: underline; cursor: pointer; }

/* Item: teks sejajar vertikal */
.konfirm__items { list-style: none; margin: 0; padding: 0; }
.konfirm__item { display: flex; gap: 0.85rem; padding: 0.85rem 0; border-top: 1px solid var(--lb-line); }
.konfirm__item:first-child { border-top: none; padding-top: 0; }
.konfirm__item:last-child { padding-bottom: 0; }
.konfirm__thumb { width: 4rem; height: 4rem; border-radius: 14px; object-fit: cover; background: var(--lb-soft); flex-shrink: 0; }
.konfirm__body { flex: 1; min-width: 0; }
.konfirm__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
.konfirm__top h3 { margin: 0; font-size: 0.94rem; font-weight: 800; }
.konfirm__remove {
  width: 1.55rem; height: 1.55rem; border-radius: 50%; border: none; cursor: pointer; flex-shrink: 0;
  background: rgba(192,42,42,0.1); color: var(--lb-danger); font-size: 0.68rem;
}
.konfirm__remove:hover { background: rgba(192,42,42,0.2); }
.konfirm__meta { margin: 0.2rem 0 0; font-size: 0.8rem; color: var(--lb-muted); }
.konfirm__note {
  width: 100%; margin-top: 0.45rem; padding: 0.5rem 0.7rem;
  border: 1px solid var(--lb-line); border-radius: 10px; background: #FFFBF0;
  font-size: 0.8rem; color: var(--lb-ink); outline: none;
}
.konfirm__note:focus { border-color: var(--lb-brand); background: #fff; }
.konfirm__sub { margin: 0.45rem 0 0; text-align: right; font-weight: 800; color: var(--lb-brand-dark); font-size: 0.92rem; }

/* Ringkasan */
.konfirm__summary { padding: 1.25rem; }
@media (min-width: 640px) { .konfirm__summary { padding: 1.5rem; } }
.konfirm__summary h2 { margin: 0 0 0.9rem; font-size: 1.05rem; font-weight: 800; }
.konfirm__summary dl { margin: 0; display: grid; gap: 0.5rem; }
.konfirm__summary dl > div { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; font-size: 0.88rem; }
.konfirm__summary dt { color: var(--lb-muted); }
.konfirm__summary dd { margin: 0; font-weight: 700; text-align: right; }
.konfirm__summary dd.is-warn { color: var(--lb-danger); }
.konfirm__total {
  display: flex; align-items: baseline; justify-content: space-between; gap: 1rem;
  margin-top: 0.9rem; padding: 0.85rem 1rem; border-radius: 14px;
  background: var(--lb-soft); font-size: 0.9rem; font-weight: 700;
}
.konfirm__total strong { font-family: 'Fraunces', Georgia, serif; font-size: 1.3rem; color: var(--lb-brand-dark); }
.konfirm__summary .lb-alert { margin: 0.9rem 0 0; }
.konfirm__submit { width: 100%; margin-top: 0.9rem; }
.konfirm__hint { margin: 0.6rem 0 0; text-align: center; font-size: 0.78rem; color: var(--lb-faint); }
</style>
