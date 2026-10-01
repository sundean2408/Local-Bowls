<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import PageHeader from '@/components/PageHeader.vue'

const tabAktif = ref('bayar')
const query = ref('')
const daftarPesanan = ref([])
const daftarRiwayat = ref([])
const loading = ref(true)
const error = ref(null)
const loadingRiwayat = ref(false)
const errorRiwayat = ref(null)

const selectedId = ref(null)
const nominal = ref(0)
const submitting = ref({})
const errorPerKartu = ref({})
const successMsg = ref('')
let successTimeout = null
const strukAktif = ref(null)
let pollId = null

function ekstrakList(res) {
  const semua = Array.isArray(res) ? res : (res?.data ?? res)
  return Array.isArray(semua) ? semua : []
}
const fmt = (h) => new Intl.NumberFormat('id-ID').format(h || 0)

function generateNomorStruk(idPesanan) {
  const now = new Date()
  const pad = (n) => String(n).padStart(2, '0')
  return `STRK-${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}-${idPesanan}`
}
function formatTanggalStruk(date) {
  const pad = (n) => String(n).padStart(2, '0')
  return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}
function jamSingkat(dateStr) {
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return '—'
  const pad = (n) => String(n).padStart(2, '0')
  return `${pad(d.getHours())}:${pad(d.getMinutes())}`
}
function isHariIni(dateStr) {
  const d = new Date(dateStr)
  const now = new Date()
  return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate()
}

const antreanTersaring = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return daftarPesanan.value
  return daftarPesanan.value.filter((p) =>
    String(p.id).includes(q) ||
    String(p.meja?.nomor_meja ?? '').toLowerCase().includes(q) ||
    String(p.nama_pelanggan ?? '').toLowerCase().includes(q)
  )
})
const selected = computed(() => {
  if (selectedId.value) return daftarPesanan.value.find((p) => String(p.id) === String(selectedId.value)) ?? null
  return antreanTersaring.value[0] ?? null
})
const jumlahItem = computed(() => (selected.value?.detail_pesanan ?? []).reduce((n, d) => n + Number(d.jumlah || 0), 0))
const kembalian = computed(() => Math.max(0, Number(nominal.value || 0) - Number(selected.value?.total_harga || 0)))
const nominalKurang = computed(() => Number(nominal.value || 0) < Number(selected.value?.total_harga || 0))

const totalSiap = computed(() => daftarPesanan.value.length)
const omsetSiap = computed(() => daftarPesanan.value.reduce((s, p) => s + Number(p.total_harga || 0), 0))
const riwayatHariIni = computed(() => daftarRiwayat.value.filter((t) => isHariIni(t.tanggal_bayar)))
const omsetHariIni = computed(() => riwayatHariIni.value.reduce((s, t) => s + Number(t.total_bayar || 0), 0))
const rataNota = computed(() => (riwayatHariIni.value.length ? Math.round(omsetHariIni.value / riwayatHariIni.value.length) : 0))

function pilihPesanan(p) {
  selectedId.value = p.id
  nominal.value = Number(p.total_harga || 0)
  delete errorPerKartu.value[p.id]
}

function pindahTab(tab) {
  tabAktif.value = tab
  if (tab === 'riwayat' && daftarRiwayat.value.length === 0) fetchRiwayat()
}

async function fetchSiapBayar(silent = false) {
  if (!silent) { loading.value = true; error.value = null }
  try {
    const list = ekstrakList(await api.get('/pesanan/siap-bayar'))
    const idAktif = selected.value ? String(selected.value.id) : (selectedId.value ? String(selectedId.value) : '')
    daftarPesanan.value = list
    // Klik meja = data panel otomatis terisi: pertahankan pilihan, atau pilih pertama.
    const masihAda = idAktif && list.some((p) => String(p.id) === idAktif)
    if (masihAda) {
      selectedId.value = idAktif
      const cur = list.find((p) => String(p.id) === idAktif)
      if (!nominal.value) nominal.value = Number(cur?.total_harga || 0)
    } else if (list[0]) {
      pilihPesanan(list[0])
    } else {
      selectedId.value = null
    }
    error.value = null
  } catch (e) {
    if (!silent) error.value = 'Gagal memuat antrean. Periksa koneksi dan sesi login.'
    console.error(e)
  } finally { loading.value = false }
}

async function fetchRiwayat() {
  loadingRiwayat.value = true
  errorRiwayat.value = null
  try {
    daftarRiwayat.value = ekstrakList(await api.get('/pembayaran'))
  } catch (e) {
    errorRiwayat.value = 'Gagal memuat riwayat transaksi.'
    console.error(e)
  } finally { loadingRiwayat.value = false }
}

async function konfirmasiBayar() {
  const pesanan = selected.value
  if (!pesanan || submitting.value[pesanan.id]) return
  if (nominalKurang.value) {
    errorPerKartu.value[pesanan.id] = `Nominal kurang Rp ${fmt(Number(pesanan.total_harga) - Number(nominal.value || 0))}.`
    return
  }
  errorPerKartu.value[pesanan.id] = ''
  submitting.value[pesanan.id] = true
  try {
    const nomorStruk = generateNomorStruk(pesanan.id)
    const waktuBayar = new Date()
    const totalBayar = Number(nominal.value)
    await api.post('/pembayaran', {
      id_pesanan: pesanan.id,
      metode_pembayaran: 'tunai',
      total_bayar: totalBayar,
      nomor_struk_digital: nomorStruk,
    })
    daftarPesanan.value = daftarPesanan.value.filter((p) => p.id !== pesanan.id)
    const sisa = daftarPesanan.value[0]
    successMsg.value = `Order #${String(pesanan.id).padStart(3, '0')} lunas — Tunai Rp ${fmt(totalBayar)}.`
    if (successTimeout) clearTimeout(successTimeout)
    successTimeout = setTimeout(() => (successMsg.value = ''), 3500)
    strukAktif.value = {
      ...pesanan,
      nomor_struk: nomorStruk,
      waktu_bayar: formatTanggalStruk(waktuBayar),
      metode: 'Tunai',
      diterima: totalBayar,
      kembali: totalBayar - Number(pesanan.total_harga),
    }
    if (sisa) pilihPesanan(sisa)
    else { selectedId.value = null }
    daftarRiwayat.value = []
  } catch (e) {
    errorPerKartu.value[pesanan.id] = e.response?.data?.message || 'Gagal konfirmasi pembayaran. Coba lagi.'
    console.error(e)
  } finally { submitting.value[pesanan.id] = false }
}

function lihatStrukRiwayat(trx) {
  strukAktif.value = {
    id: trx.pesanan?.id, meja: trx.pesanan?.meja,
    nama_pelanggan: trx.pesanan?.nama_pelanggan ?? null,
    detail_pesanan: trx.pesanan?.detail_pesanan ?? [], total_harga: trx.pesanan?.total_harga,
    nomor_struk: trx.nomor_struk_digital, waktu_bayar: formatTanggalRiwayat(trx.tanggal_bayar),
    metode: 'Tunai',
    diterima: Number(trx.total_bayar), kembali: Number(trx.total_bayar) - Number(trx.pesanan?.total_harga ?? trx.total_bayar),
  }
}
function formatTanggalRiwayat(dateStr) {
  const d = new Date(dateStr)
  const pad = (n) => String(n).padStart(2, '0')
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}
function tutupStruk() { strukAktif.value = null }
function cetakStruk() { window.print() }

onMounted(() => {
  fetchSiapBayar()
  fetchRiwayat()
  pollId = setInterval(() => { if (tabAktif.value === 'bayar') fetchSiapBayar(true) }, 10000)
})
onUnmounted(() => { if (pollId) clearInterval(pollId) })
</script>

<template>
  <div class="lb-wrap pos">
    <PageHeader
      eyebrow="Kasir"
      title="Point of Sale"
      :subtitle="`Shift hari ini · ${riwayatHariIni.length} transaksi · Rp ${fmt(omsetHariIni)}`"
    >
      <template #action>
        <div class="pos__seg" role="tablist" aria-label="Mode kasir">
          <button type="button" role="tab" :aria-selected="tabAktif === 'bayar'" :class="{ 'is-active': tabAktif === 'bayar' }" @click="pindahTab('bayar')">Kasir · {{ totalSiap }}</button>
          <button type="button" role="tab" :aria-selected="tabAktif === 'riwayat'" :class="{ 'is-active': tabAktif === 'riwayat' }" @click="pindahTab('riwayat')">Riwayat</button>
        </div>
        <button type="button" class="lb-btn-ghost pos__refresh" @click="tabAktif === 'bayar' ? fetchSiapBayar() : fetchRiwayat()">Muat Ulang</button>
      </template>
    </PageHeader>

    <div class="pos__kpi">
      <div class="lb-card pos__kpi-card"><span class="pos__kpi-label">Antrean</span><strong class="pos__kpi-num">{{ totalSiap }}</strong><span class="pos__kpi-sub">order siap bayar</span></div>
      <div class="lb-card pos__kpi-card"><span class="pos__kpi-label">Piutang Kasir</span><strong class="pos__kpi-num">Rp {{ fmt(omsetSiap) }}</strong><span class="pos__kpi-sub">belum dibayar</span></div>
      <div class="lb-card pos__kpi-card"><span class="pos__kpi-label">Pendapatan Hari Ini</span><strong class="pos__kpi-num">Rp {{ fmt(omsetHariIni) }}</strong><span class="pos__kpi-sub">{{ riwayatHariIni.length }} struk</span></div>
      <div class="lb-card pos__kpi-card"><span class="pos__kpi-label">Rata-rata Nota</span><strong class="pos__kpi-num">Rp {{ fmt(rataNota) }}</strong><span class="pos__kpi-sub">per transaksi</span></div>
    </div>

    <p v-if="successMsg" class="lb-alert lb-alert--ok pos__ok" role="status">{{ successMsg }}</p>

    <div v-if="tabAktif === 'bayar'">
      <div v-if="loading" class="pos__work">
        <div class="lb-card pos__queue"><div v-for="i in 5" :key="i" class="lb-skeleton pos__sk"></div></div>
        <div class="lb-card pos__panel"><div class="lb-skeleton pos__sk"></div><div class="lb-skeleton pos__sk"></div><div class="lb-skeleton pos__sk"></div></div>
      </div>
      <div v-else-if="error" class="lb-alert lb-alert--error">{{ error }}</div>
      <div v-else-if="daftarPesanan.length === 0" class="lb-empty">
        <p class="pos__empty-title">Antrean kosong.</p>
        <p class="pos__empty-sub">Order dari dapur yang berstatus Selesai muncul di sini.</p>
      </div>
      <div v-else class="pos__work">
        <section class="lb-card pos__queue" aria-label="Daftar order">
          <header class="pos__queue-head">
            <h2>Order Aktif <span>({{ antreanTersaring.length }})</span></h2>
            <input v-model="query" type="search" class="lb-input pos__search" placeholder="Cari ID / meja" aria-label="Cari order" />
          </header>
          <p v-if="antreanTersaring.length === 0" class="pos__queue-empty">Tidak ada hasil.</p>
          <button
            v-for="p in antreanTersaring"
            :key="p.id"
            type="button"
            class="pos__order"
            :class="{ 'is-active': selected && String(selected.id) === String(p.id) }"
            :aria-pressed="selected && String(selected.id) === String(p.id)"
            @click="pilihPesanan(p)"
          >
            <span class="pos__dot" aria-hidden="true"></span>
            <span class="pos__order-main">
              <strong>#{{ String(p.id).padStart(3, '0') }} · {{ p.nama_pelanggan || 'Tamu' }}</strong>
              <span>Meja {{ p.meja?.nomor_meja ?? '—' }} · {{ (p.detail_pesanan ?? []).length }} menu · {{ jamSingkat(p.created_at) }}</span>
            </span>
            <strong class="pos__order-total">Rp {{ fmt(p.total_harga) }}</strong>
          </button>
        </section>

        <section v-if="selected" class="lb-card pos__panel" aria-live="polite">
          <header class="pos__inv-head">
            <div><p class="pos__inv-no">INV-{{ String(selected.id).padStart(3, '0') }} · {{ selected.nama_pelanggan || 'Tamu' }}</p><p class="pos__inv-sub">Meja {{ selected.meja?.nomor_meja ?? '—' }} · {{ jumlahItem }} item · {{ jamSingkat(selected.created_at) }}</p></div>
            <span class="pos__status">Belum Bayar</span>
          </header>

          <table class="pos__lines">
            <tbody>
              <tr v-for="d in selected.detail_pesanan" :key="d.id">
                <td><strong>{{ d.menu?.nama_menu ?? 'Menu' }}</strong><span v-if="d.catatan" class="pos__catatan">{{ d.catatan }}</span></td>
                <td class="is-num">{{ d.jumlah }}×</td>
                <td class="is-num">Rp {{ fmt(d.subtotal) }}</td>
              </tr>
            </tbody>
          </table>

          <dl class="pos__sum">
            <div class="is-total"><dt>Total Tagihan</dt><dd>Rp {{ fmt(selected.total_harga) }}</dd></div>
          </dl>

          <p class="pos__tunai-label">Pembayaran Tunai</p>

          <div class="pos__cash">
            <label>Nominal diterima<input v-model.number="nominal" type="number" min="0" class="lb-input pos__nominal" inputmode="numeric" @keyup.enter="konfirmasiBayar" /></label>
            <div class="pos__denom">
              <button type="button" @click="nominal = Number(selected.total_harga)">Pas</button>
              <button type="button" @click="nominal = 20000">20rb</button>
              <button type="button" @click="nominal = 50000">50rb</button>
              <button type="button" @click="nominal = 100000">100rb</button>
            </div>
            <p class="pos__change" :class="{ 'is-short': nominalKurang }">Kembalian Rp {{ fmt(kembalian) }}<span v-if="nominalKurang"> · kurang Rp {{ fmt(Number(selected.total_harga) - Number(nominal || 0)) }}</span></p>
          </div>

          <p v-if="errorPerKartu[selected.id]" class="lb-alert lb-alert--error pos__err">{{ errorPerKartu[selected.id] }}</p>
          <button type="button" class="lb-btn-primary pos__cta" :disabled="!!submitting[selected.id] || nominalKurang" @click="konfirmasiBayar">
            {{ submitting[selected.id] ? 'Memproses…' : `Bayar Rp ${fmt(nominal)}` }}
          </button>
        </section>
      </div>
    </div>

    <div v-else>
      <div v-if="loadingRiwayat" class="pos__loading"><div class="lb-spinner"></div><p>Memuat riwayat…</p></div>
      <div v-else-if="errorRiwayat" class="lb-alert lb-alert--error">{{ errorRiwayat }}</div>
      <div v-else-if="daftarRiwayat.length === 0" class="lb-empty"><p class="pos__empty-title">Belum ada transaksi.</p></div>
      <div v-else class="lb-card pos__ledger">
        <table>
          <thead><tr><th>No. Struk</th><th>Order</th><th>Waktu</th><th>Metode</th><th class="is-num">Total</th><th></th></tr></thead>
          <tbody>
            <tr v-for="trx in daftarRiwayat" :key="trx.id">
              <td class="is-mono">{{ trx.nomor_struk_digital }}</td>
              <td>#{{ String(trx.pesanan?.id ?? trx.id).padStart(3, '0') }} · {{ trx.pesanan?.nama_pelanggan || 'Tamu' }} · M{{ trx.pesanan?.meja?.nomor_meja ?? '—' }}</td>
              <td class="is-mono">{{ formatTanggalRiwayat(trx.tanggal_bayar) }}</td>
              <td><span class="pos__m">{{ trx.metode_pembayaran?.toUpperCase() }}</span></td>
              <td class="is-num"><strong>Rp {{ fmt(trx.total_bayar) }}</strong></td>
              <td class="is-num"><button type="button" class="pos__link" @click="lihatStrukRiwayat(trx)">Struk</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="strukAktif" class="pos__overlay" @click.self="tutupStruk">
      <div class="pos__modal" role="dialog" aria-label="Struk">
        <div class="pos__receipt">
          <p class="pos__r-brand">LOCAL BOWLS</p>
          <p class="pos__r-sub">{{ strukAktif.nomor_struk }} · {{ strukAktif.waktu_bayar }}</p>
          <p class="pos__r-sub">#{{ String(strukAktif.id).padStart(3, '0') }} · {{ strukAktif.nama_pelanggan || 'Tamu' }} · Meja {{ strukAktif.meja?.nomor_meja ?? '—' }}</p>
          <hr />
          <div v-for="d in strukAktif.detail_pesanan" :key="d.id" class="pos__r-line">
            <span>{{ d.jumlah }}× {{ d.menu?.nama_menu ?? 'Menu' }}</span><span>Rp {{ fmt(d.subtotal) }}</span>
          </div>
          <hr />
          <p class="pos__r-total"><span>Total</span><strong>Rp {{ fmt(strukAktif.total_harga) }}</strong></p>
          <p class="pos__r-sub">{{ strukAktif.metode ?? 'Tunai' }}{{ strukAktif.diterima != null ? ` · Diterima Rp ${fmt(strukAktif.diterima)} · Kembali Rp ${fmt(Math.max(0, strukAktif.kembali ?? 0))}` : '' }}</p>
          <p class="pos__r-thanks">Terima kasih.</p>
        </div>
        <div class="pos__modal-actions no-print">
          <button type="button" class="lb-btn-ghost" @click="tutupStruk">Tutup</button>
          <button type="button" class="lb-btn-primary" @click="cetakStruk">Cetak</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pos { padding-block: 0.5rem 1.5rem; }
.pos__seg { display: inline-flex; background: #F1E7D7; border-radius: 999px; padding: 0.2rem; gap: 0.15rem; }
.pos__seg button { border: none; background: transparent; border-radius: 999px; padding: 0.5rem 1rem; font-size: 0.8rem; font-weight: 800; color: var(--lb-muted); cursor: pointer; }
.pos__seg button.is-active { background: #fff; color: var(--lb-ink); box-shadow: 0 2px 8px rgba(61,40,23,0.12); }
.pos__refresh { margin-left: 0.5rem; }
.pos__kpi { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1rem; }
@media (min-width: 900px) { .pos__kpi { grid-template-columns: repeat(4, 1fr); } }
.pos__kpi-card { padding: 0.9rem 1rem; display: grid; gap: 0.1rem; }
.pos__kpi-label { font-size: 0.68rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--lb-muted); }
.pos__kpi-num { font-size: 1.15rem; font-weight: 800; font-variant-numeric: tabular-nums; }
.pos__kpi-sub { font-size: 0.75rem; color: var(--lb-faint); }
.pos__ok { margin-bottom: 1rem; }
.pos__work { display: grid; gap: 1rem; align-items: start; }
@media (min-width: 960px) { .pos__work { grid-template-columns: minmax(280px, 360px) minmax(0, 1fr); } }
.pos__queue { padding: 1rem; display: grid; gap: 0.5rem; align-content: start; max-height: 72vh; overflow-y: auto; }
.pos__queue-head { display: grid; gap: 0.6rem; }
.pos__queue-head h2 { margin: 0; font-size: 0.85rem; font-weight: 800; }
.pos__queue-head h2 span { color: var(--lb-faint); font-weight: 600; }
.pos__search { font-size: 0.85rem; padding-block: 0.6rem; }
.pos__queue-empty { margin: 0; font-size: 0.85rem; color: var(--lb-faint); }
.pos__order { display: flex; align-items: center; gap: 0.7rem; text-align: left; width: 100%; padding: 0.7rem 0.8rem; border-radius: 12px; border: 1px solid var(--lb-line); background: #fff; cursor: pointer; }
.pos__order.is-active { border-color: var(--lb-ink); background: var(--lb-ink); color: #fff; }
.pos__order.is-active .pos__order-main span, .pos__order.is-active .pos__order-total { color: rgba(255,255,255,0.8); }
.pos__dot { width: 0.55rem; height: 0.55rem; border-radius: 50%; background: var(--lb-brand); flex-shrink: 0; }
.pos__order.is-active .pos__dot { background: #4ade80; }
.pos__order-main { flex: 1; min-width: 0; display: grid; }
.pos__order-main strong { font-size: 0.85rem; }
.pos__order-main span { font-size: 0.75rem; color: var(--lb-muted); }
.pos__order-total { font-size: 0.85rem; font-variant-numeric: tabular-nums; }
.pos__panel { padding: 1.25rem; display: grid; gap: 0.9rem; }
.pos__inv-head { display: flex; justify-content: space-between; gap: 0.75rem; align-items: flex-start; padding-bottom: 0.85rem; border-bottom: 1px solid var(--lb-line); }
.pos__inv-no { margin: 0; font-weight: 800; letter-spacing: 0.04em; }
.pos__inv-sub { margin: 0.2rem 0 0; font-size: 0.8rem; color: var(--lb-muted); }
.pos__status { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #92400e; background: #fef3c7; padding: 0.3rem 0.65rem; border-radius: 999px; }
.pos__lines { width: 100%; border-collapse: collapse; }
.pos__lines td { padding: 0.5rem 0; border-bottom: 1px dashed var(--lb-line); font-size: 0.88rem; vertical-align: top; }
.pos__lines tr:last-child td { border-bottom: none; }
.pos__lines strong { display: block; }
.pos__catatan { font-size: 0.75rem; color: var(--lb-muted); font-style: italic; }
.is-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.pos__sum { margin: 0; display: grid; gap: 0.3rem; background: #FFFBF0; border-radius: 12px; padding: 0.85rem 1rem; }
.pos__sum > div { display: flex; justify-content: space-between; font-size: 0.85rem; }
.pos__sum dd { margin: 0; font-weight: 700; font-variant-numeric: tabular-nums; }
.pos__sum .is-total { border-top: 1px solid var(--lb-line); padding-top: 0.5rem; margin-top: 0.25rem; font-size: 0.95rem; }
.pos__sum .is-total dd { font-size: 1.25rem; font-family: 'Fraunces', Georgia, serif; color: var(--lb-brand-dark); }
.pos__tunai-label { margin: 0; font-size: 0.75rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: var(--lb-muted); }
.pos__cash { display: grid; gap: 0.55rem; }
.pos__cash label { display: grid; gap: 0.3rem; font-size: 0.78rem; font-weight: 800; color: var(--lb-muted); }
.pos__nominal { font-variant-numeric: tabular-nums; font-weight: 800; }
.pos__denom { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.pos__denom button { padding: 0.4rem 0.75rem; border-radius: 8px; border: 1px solid var(--lb-line); background: #fff; font-size: 0.78rem; font-weight: 700; cursor: pointer; }
.pos__denom button:hover { border-color: var(--lb-ink); }
.pos__change { margin: 0; font-size: 0.85rem; font-weight: 800; color: #166534; }
.pos__change.is-short { color: var(--lb-danger); }
.pos__err { margin: 0; }
.pos__cta { width: 100%; }
.pos__sk { height: 3rem; }
.pos__empty-title { margin: 0 0 0.2rem; font-weight: 800; color: var(--lb-ink); }
.pos__empty-sub { margin: 0; font-size: 0.88rem; }
.pos__loading { text-align: center; padding: 2rem; color: var(--lb-muted); }
.pos__ledger { overflow-x: auto; }
.pos__ledger table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 640px; }
.pos__ledger th, .pos__ledger td { padding: 0.7rem 1rem; border-bottom: 1px solid var(--lb-line); text-align: left; }
.pos__ledger thead th { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.07em; color: var(--lb-faint); background: #FFFBF0; }
.pos__ledger tbody tr:last-child td { border-bottom: none; }
.is-mono { font-family: ui-monospace, monospace; font-size: 0.78rem; }
.pos__m { font-size: 0.7rem; font-weight: 800; background: #F1E7D7; padding: 0.2rem 0.5rem; border-radius: 6px; }
.pos__link { background: none; border: none; color: var(--lb-brand-dark); font-weight: 800; cursor: pointer; font-size: 0.8rem; }
.pos__overlay { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(20,12,6,0.6); }
.pos__modal { width: 100%; max-width: 20rem; }
.pos__receipt { background: #fff; border-radius: 8px; padding: 1.25rem; font-family: ui-monospace, monospace; font-size: 0.75rem; max-height: 70vh; overflow-y: auto; }
.pos__r-brand { text-align: center; font-weight: 800; letter-spacing: 0.1em; margin: 0; }
.pos__r-sub { text-align: center; color: #666; margin: 0.25rem 0 0; }
.pos__receipt hr { border: none; border-top: 1px dashed #ccc; margin: 0.75rem 0; }
.pos__r-line { display: flex; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.35rem; }
.pos__r-total { display: flex; justify-content: space-between; font-size: 0.9rem; margin: 0; }
.pos__r-thanks { text-align: center; margin: 0.75rem 0 0; }
.pos__modal-actions { display: flex; gap: 0.6rem; margin-top: 0.75rem; }
.pos__modal-actions > * { flex: 1; }
@media print { .pos__overlay { position: static; background: none; padding: 0; } .no-print { display: none !important; } body * { visibility: hidden; } .pos__receipt, .pos__receipt * { visibility: visible; } .pos__receipt { position: absolute; left: 0; top: 0; width: 100%; } }
</style>
