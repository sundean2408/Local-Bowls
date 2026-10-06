<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../../store/cart'
import { getImageUrl } from '../../services/api'
import PageHeader from '../../components/PageHeader.vue'

const router = useRouter()
const cart = useCartStore()

const items = computed(() => cart.items ?? [])
const totalHarga = computed(() => cart.totalHarga ?? 0)
const totalQty = computed(() => items.value.reduce((n, i) => n + (i.qty || 0), 0))

function fotoItem(item) {
  const raw = item.gambar ?? item.foto ?? item.foto_menu ?? item.image ?? item.thumbnail
  return getImageUrl(raw)
}

function handleImgError(event) {
  const img = event.target
  if (img.dataset.fallback) return
  img.dataset.fallback = '1'
  img.src = '/logo.png'
}

function increment(item) {
  cart.updateQty(item.id, item.qty + 1)
}

function decrement(item) {
  cart.updateQty(item.id, item.qty - 1)
}

function hapusItem(item) {
  cart.removeItem(item.id)
}

const fmt = (h) => new Intl.NumberFormat('id-ID').format(h || 0)

function lanjutKonfirmasi() {
  if (items.value.length === 0) return
  router.push('/konfirmasi')
}
</script>

<template>
  <div class="lb-wrap keranjang">
    <PageHeader
      eyebrow="Sebelum pesan"
      title="Keranjang Kamu"
      :subtitle="items.length ? `${totalQty} item · Rp ${fmt(totalHarga)}` : 'Pilih menu favoritmu dulu'"
    >
      <template #action>
        <router-link to="/menu" class="lb-btn-ghost keranjang__back">+ Pesan Lagi</router-link>
      </template>
    </PageHeader>

    <div v-if="items.length === 0" class="lb-empty" role="status">
      <img src="/logo.png" alt="LocalBowls" class="keranjang__logo" />
      <p class="keranjang__empty-title">Keranjang masih kosong.</p>
      <p class="keranjang__empty-sub">Yuk pilih menu favoritmu dulu.</p>
      <router-link to="/menu" class="lb-btn-primary keranjang__cta">Lihat Menu</router-link>
    </div>

    <div v-else class="keranjang__grid">
      <section class="lb-card keranjang__list" aria-label="Item di keranjang">
        <article v-for="(item, idx) in items" :key="item.id" class="keranjang__item" :class="{ 'is-last': idx === items.length - 1 }">
          <img :src="fotoItem(item)" :alt="item.nama_menu" class="keranjang__thumb" loading="lazy" @error="handleImgError" />
          <div class="keranjang__info">
            <h3>{{ item.nama_menu }}</h3>
            <p class="keranjang__price">Rp {{ fmt(item.harga) }}</p>
            <button type="button" class="keranjang__hapus" @click="hapusItem(item)" :aria-label="`Hapus ${item.nama_menu} dari keranjang`">Hapus</button>
          </div>
          <div class="keranjang__qty">
            <button type="button" @click="decrement(item)" :aria-label="`Kurangi jumlah ${item.nama_menu}`">−</button>
            <span :aria-label="`${item.qty} ${item.nama_menu}`">{{ item.qty }}</span>
            <button type="button" class="is-plus" @click="increment(item)" :aria-label="`Tambah jumlah ${item.nama_menu}`">+</button>
          </div>
          <p class="keranjang__sub">Rp {{ fmt(item.harga * item.qty) }}</p>
        </article>
      </section>

      <aside class="lb-card keranjang__side" aria-labelledby="keranjang-ringkasan">
        <h2 id="keranjang-ringkasan">Ringkasan</h2>
        <dl>
          <div><dt>Item</dt><dd>{{ totalQty }} item</dd></div>
          <div><dt>Menu</dt><dd>{{ items.length }} jenis</dd></div>
        </dl>
        <div class="keranjang__total" aria-live="polite"><span>Total bayar</span><strong>Rp {{ fmt(totalHarga) }}</strong></div>
        <router-link to="/menu" class="lb-btn-ghost keranjang__full">+ Pesan Lagi</router-link>
        <button type="button" class="lb-btn-primary keranjang__full" @click="lanjutKonfirmasi">Lanjut ke Konfirmasi</button>
        <p class="keranjang__hint">Pilih meja di halaman berikutnya.</p>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.keranjang { padding-block: 0.5rem 3rem; }
.keranjang__back { text-decoration: none; }
.keranjang__logo { width: 4.5rem; height: 4.5rem; object-fit: contain; margin: 0 auto 1rem; }
.keranjang__empty-title { margin: 0; font-weight: 800; color: var(--lb-ink); }
.keranjang__empty-sub { margin: 0.25rem 0 1rem; font-size: 0.88rem; }
.keranjang__cta { text-decoration: none; }
.keranjang__grid { display: grid; gap: 1rem; align-items: start; }
@media (min-width: 900px) { .keranjang__grid { grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.9fr); } .keranjang__side { position: sticky; top: 5rem; } }
.keranjang__list { padding: 0.5rem 1.1rem; }
.keranjang__item { display: grid; grid-template-columns: 3.5rem minmax(0, 1fr); gap: 0.5rem 0.75rem; align-items: center; padding-block: 0.9rem; border-bottom: 1px dashed var(--lb-line); }
.keranjang__item.is-last { border-bottom: none; }
.keranjang__thumb { width: 3.5rem; height: 3.5rem; border-radius: 14px; object-fit: cover; background: var(--lb-soft); grid-column: 1; grid-row: 1 / span 3; }
.keranjang__info { min-width: 0; }
.keranjang__info h3 { margin: 0; font-size: 0.94rem; font-weight: 800; overflow-wrap: anywhere; }
.keranjang__price { margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--lb-brand-dark); font-weight: 700; }
.keranjang__hapus { background: none; border: none; padding: 0; margin-top: 0.2rem; font-size: 0.76rem; font-weight: 700; color: var(--lb-muted); text-decoration: underline; text-underline-offset: 2px; cursor: pointer; }
.keranjang__hapus:hover { color: var(--lb-danger); }
.keranjang__qty { display: flex; align-items: center; gap: 0.6rem; grid-column: 2; }
.keranjang__qty button { width: 2.75rem; height: 2.75rem; border-radius: 50%; border: 1.5px solid var(--lb-line); background: #fff; color: var(--lb-brand-dark); font-weight: 800; font-size: 1.1rem; cursor: pointer; }
.keranjang__qty button.is-plus { background: var(--lb-brand); border-color: var(--lb-brand); color: #fff; }
.keranjang__qty span { min-width: 1.2rem; text-align: center; font-weight: 800; font-variant-numeric: tabular-nums; }
.keranjang__sub { margin: 0; grid-column: 2; font-weight: 800; font-size: 0.9rem; text-align: left; font-variant-numeric: tabular-nums; }
.keranjang__side { padding: 1.25rem; display: grid; gap: 0.85rem; }
.keranjang__side h2 { margin: 0; font-size: 1.05rem; font-weight: 800; }
.keranjang__side dl { margin: 0; display: grid; gap: 0.4rem; }
.keranjang__side dl > div { display: flex; justify-content: space-between; font-size: 0.88rem; }
.keranjang__side dt { color: var(--lb-muted); }
.keranjang__side dd { margin: 0; font-weight: 700; }
.keranjang__total { display: flex; align-items: baseline; justify-content: space-between; background: var(--lb-soft); border-radius: 14px; padding: 0.8rem 1rem; font-weight: 800; }
.keranjang__total strong { font-family: 'Fraunces', Georgia, serif; font-size: 1.3rem; color: var(--lb-brand-dark); }
.keranjang__full { width: 100%; text-decoration: none; }
.keranjang__hint { margin: 0; text-align: center; font-size: 0.76rem; color: var(--lb-faint); }
.keranjang :is(a, button):focus-visible { outline: 3px solid var(--lb-brand); outline-offset: 3px; }
@media (min-width: 560px) {
  .keranjang__item { grid-template-columns: 4rem minmax(0, 1fr) auto auto; gap: 0.5rem 1rem; }
  .keranjang__thumb { width: 4rem; height: 4rem; grid-row: auto; }
  .keranjang__qty { grid-column: auto; }
  .keranjang__sub { grid-column: auto; text-align: right; min-width: 7rem; }
}
@media (max-width: 360px) {
  .keranjang__list { padding-inline: 0.75rem; }
  .keranjang__qty { gap: 0.4rem; }
  .keranjang__qty button { width: 2.5rem; height: 2.5rem; }
  .keranjang__total { gap: 0.6rem; padding-inline: 0.75rem; }
  .keranjang__total strong { font-size: 1.1rem; text-align: right; }
}
</style>
