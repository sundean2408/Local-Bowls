<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api, { getImageUrl } from '@/services/api'
import { useAuthStore } from '../../store/auth.js'
import { formatAppDateTime, getAppWeekday } from '@/utils/dateTime'

const router = useRouter()
const authStore = useAuthStore()

// ===== STATE =====
const activeTab = ref('dashboard')
const loading = ref(false)
const mobileSidebarOpen = ref(false)
const adminMenuOpen = ref(false)

const userList = ref([])
const showUserModal = ref(false)
const editingUser = ref(null)
const userForm = ref({ nama: '', username: '', password: '', role: 'kasir' })

const menuList = ref([])
const showMenuModal = ref(false)
const editingMenu = ref(null)
const menuForm = ref({ nama_menu: '', id_kategori: '', deskripsi: '', harga: 0, status_tersedia: true })
const menuPreview = ref(null)
const menuFile = ref(null)

const kategoriList = ref([])
const showKategoriModal = ref(false)
const editingKategori = ref(null)
const kategoriForm = ref({ nama_kategori: '' })

const reportStats = ref({ totalTransaksi: 0, totalPendapatan: 0, totalMinggu: 0, totalBulan: 0, rataRata: 0 })
const recentTransactions = ref([])

const mejaList = ref([])
const showMejaModal = ref(false)
const editingMeja = ref(null)
const mejaForm = ref({ nomor_meja: '' })

const riwayatList = ref([])
const menuTerlaris = ref([])

const navItems = [
  { id: 'dashboard', label: 'Dashboard', icon: 'grid' },
  { id: 'menu', label: 'Kelola Menu', icon: 'bowl' },
  { id: 'kategori', label: 'Kategori', icon: 'tag' },
  { id: 'meja', label: 'Meja', icon: 'table' },
  { id: 'riwayat', label: 'Riwayat Transaksi', icon: 'receipt' },
  { id: 'laporan', label: 'Laporan Penjualan', icon: 'chart' },
  { id: 'user', label: 'Kelola User', icon: 'users' },
]

const pageTitle = computed(() => navItems.find(t => t.id === activeTab.value)?.label || 'Dashboard')

const adminInitials = computed(() => {
  const nama = authStore.user?.nama || 'Admin'
  const parts = nama.trim().split(/\s+/)
  return ((parts[0]?.[0] || 'A') + (parts[1]?.[0] || '')).toUpperCase()
})

function selectTab(id) {
  activeTab.value = id
  mobileSidebarOpen.value = false
}

function handleEscape() {
  if (showUserModal.value) showUserModal.value = false
  else if (showMenuModal.value) showMenuModal.value = false
  else if (showKategoriModal.value) showKategoriModal.value = false
  else if (showMejaModal.value) showMejaModal.value = false
  else if (mobileSidebarOpen.value) mobileSidebarOpen.value = false
  else adminMenuOpen.value = false
}

// ===== HELPERS =====
const getGambarUrl = (gambar) => getImageUrl(gambar, '/logo.png')
const fmt = (h) => new Intl.NumberFormat('id-ID').format(h || 0)
const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return formatAppDateTime(dateStr)
}

const roleBadge = (role) => {
  const m = { admin: { bg: 'var(--lb-orange-bg)', color: 'var(--lb-orange-text)' }, kitchen: { bg: 'var(--lb-blue-bg)', color: 'var(--lb-blue-text)' }, kasir: { bg: 'var(--lb-purple-bg)', color: 'var(--lb-purple-text)' } }
  return m[role] || { bg: 'var(--lb-warm3)', color: 'var(--lb-neutral2)' }
}

// ===== CHART DATA =====
const paymentBreakdown = computed(() => {
  const totals = {}
  recentTransactions.value.forEach((trx) => {
    const key = trx.metode_pembayaran || 'Lainnya'
    totals[key] = (totals[key] || 0) + Number(trx.total_bayar || 0)
  })
  const entries = Object.entries(totals)
  const max = Math.max(1, ...entries.map(([, v]) => v))
  return entries.map(([label, value]) => ({ label, value, percent: Math.round((value / max) * 100) }))
})

const HARI_URUTAN = [1, 2, 3, 4, 5, 6, 0]
const HARI_LABEL = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']

const weeklyChartData = computed(() => {
  const totals = [0, 0, 0, 0, 0, 0, 0]
  recentTransactions.value.forEach((trx) => {
    const weekday = getAppWeekday(trx.tanggal_bayar)
    if (weekday === null) return
    const idx = HARI_URUTAN.indexOf(weekday)
    if (idx !== -1) totals[idx] += Number(trx.total_bayar || 0)
  })
  return HARI_LABEL.map((label, i) => ({ label, value: totals[i] }))
})

const CHART_W = 700, CHART_H = 220, CHART_PAD = 24
const chartMax = computed(() => Math.max(1, ...weeklyChartData.value.map((d) => d.value)))
const chartPoints = computed(() => {
  const n = weeklyChartData.value.length
  const stepX = (CHART_W - CHART_PAD * 2) / (n - 1)
  return weeklyChartData.value.map((d, i) => {
    const x = CHART_PAD + i * stepX
    const y = CHART_H - CHART_PAD - (d.value / chartMax.value) * (CHART_H - CHART_PAD * 2)
    return { x, y, ...d }
  })
})
const linePath = computed(() => chartPoints.value.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' '))
const areaPath = computed(() => {
  const pts = chartPoints.value
  if (pts.length === 0) return ''
  const baseline = CHART_H - CHART_PAD
  return `${linePath.value} L ${pts[pts.length - 1].x.toFixed(1)} ${baseline} L ${pts[0].x.toFixed(1)} ${baseline} Z`
})

const CHART_COLORS = ['#D97757', '#F5B542', '#4CAF50', '#8B5CF6', '#3B82F6', '#EC4899', '#14B8A6']
const kategoriBreakdown = computed(() => {
  const total = menuList.value.length || 1
  return kategoriList.value
    .map((kat, i) => {
      const count = menuList.value.filter((m) => m.id_kategori === kat.id).length
      return { id: kat.id, label: kat.nama_kategori, count, percent: Math.round((count / total) * 100), color: CHART_COLORS[i % CHART_COLORS.length] }
    })
    .filter((k) => k.count > 0)
})
const donutBackground = computed(() => {
  if (kategoriBreakdown.value.length === 0) return 'var(--lb-soft)'
  let cursor = 0
  const stops = kategoriBreakdown.value.map((k) => { const s = cursor; cursor += k.percent; return `${k.color} ${s}% ${cursor}%` })
  if (cursor < 100) { const last = kategoriBreakdown.value[kategoriBreakdown.value.length - 1]; stops.push(`${last.color} ${cursor}% 100%`) }
  return `conic-gradient(${stops.join(', ')})`
})
function kategoriColor(id) { return kategoriBreakdown.value.find((k) => k.id === id)?.color || 'var(--lb-muted)' }
function namaKategori(id) { return kategoriList.value.find((k) => k.id === id)?.nama_kategori || '-' }
const menuTerbaru = computed(() => [...menuList.value].sort((a, b) => (b.id || 0) - (a.id || 0)).slice(0, 4))

// ===== DATA FETCHING =====
const loadUsers = async () => { try { const r = await api.get('/users'); userList.value = Array.isArray(r) ? r : (r.data ?? []) } catch (e) { console.error(e) } }
const loadMenus = async () => { try { const r = await api.get('/menu'); menuList.value = Array.isArray(r) ? r : (r.data ?? []) } catch (e) { console.error(e) } }
const loadKategori = async () => { try { const r = await api.get('/kategori'); kategoriList.value = Array.isArray(r) ? r : (r.data ?? []) } catch (e) { console.error(e) } }
const loadMeja = async () => { try { const r = await api.get('/meja'); mejaList.value = Array.isArray(r) ? r : (r.data ?? []) } catch (e) { console.error(e) } }
const loadLaporan = async () => {
  try {
    const r = await api.get('/laporan')
    const data = r.data ?? r
    reportStats.value = {
      totalTransaksi: data.jumlah_transaksi_hari_ini || 0,
      totalPendapatan: data.total_hari_ini || 0,
      totalMinggu: data.total_minggu_ini || 0,
      totalBulan: data.total_bulan_ini || 0,
      rataRata: data.jumlah_transaksi_hari_ini ? Math.round((data.total_hari_ini || 0) / data.jumlah_transaksi_hari_ini) : 0,
    }
    recentTransactions.value = data.transaksi_terbaru || []
    menuTerlaris.value = data.menu_terlaris || []
  } catch (e) { console.error(e) }
}
const loadRiwayat = async () => {
  try { const r = await api.get('/pembayaran'); riwayatList.value = Array.isArray(r) ? r : (r.data ?? []) } catch (e) { console.error(e) }
}
const loadAll = async () => { loading.value = true; await Promise.all([loadUsers(), loadMenus(), loadKategori(), loadLaporan(), loadMeja(), loadRiwayat()]); loading.value = false }

// ===== MEJA CRUD =====
const openMejaModal = (meja = null) => {
  editingMeja.value = meja
  mejaForm.value = meja ? { nomor_meja: meja.nomor_meja } : { nomor_meja: '' }
  showMejaModal.value = true
}
const saveMeja = async () => {
  try {
    if (editingMeja.value) { await api.put(`/meja/${editingMeja.value.id}`, mejaForm.value) }
    else { await api.post('/meja', mejaForm.value) }
    showMejaModal.value = false; await loadMeja()
  } catch (e) { alert(e.response?.data?.message || e.message || 'Gagal menyimpan meja') }
}
const deleteMeja = async (id) => { if (!confirm('Yakin hapus meja ini?')) return; try { await api.delete(`/meja/${id}`); await loadMeja() } catch (e) { alert(e.message || 'Gagal menghapus meja') } }
const toggleStatusMeja = async (meja) => {
  const baru = meja.status_meja === 'kosong' ? 'terisi' : 'kosong'
  try { await api.put(`/meja/${meja.id}`, { status_meja: baru }); await loadMeja() } catch (e) { alert(e.message || 'Gagal mengubah status meja') }
}

// ===== USER CRUD =====
const openUserModal = (user = null) => {
  editingUser.value = user
  userForm.value = user ? { nama: user.nama, username: user.username, password: '', role: user.role } : { nama: '', username: '', password: '', role: 'kasir' }
  showUserModal.value = true
}
const saveUser = async () => {
  try {
    if (editingUser.value) { await api.put(`/users/${editingUser.value.id}`, userForm.value) }
    else { await api.post('/users', userForm.value) }
    showUserModal.value = false; await loadUsers()
  } catch (e) { alert(e.response?.data?.message || e.message || 'Gagal menyimpan user') }
}
const deleteUser = async (id) => { if (!confirm('Yakin hapus user ini?')) return; try { await api.delete(`/users/${id}`); await loadUsers() } catch (e) { alert(e.message || 'Gagal menghapus user') } }

// ===== MENU CRUD =====
const openMenuModal = (menu = null) => {
  editingMenu.value = menu
  menuForm.value = menu ? { ...menu, status_tersedia: menu.status_tersedia !== false && menu.status_tersedia !== 0 && menu.status_tersedia !== '0' } : { nama_menu: '', id_kategori: kategoriList.value[0]?.id || '', deskripsi: '', harga: 0, status_tersedia: true }
  menuPreview.value = menu ? getGambarUrl(menu.gambar) : null
  menuFile.value = null; showMenuModal.value = true
}
const handleFileUpload = (e) => { const f = e.target.files[0]; if (f) { menuFile.value = f; menuPreview.value = URL.createObjectURL(f) } }
const saveMenu = async () => {
  try {
    const fd = new FormData()
    Object.keys(menuForm.value).forEach(k => fd.append(k, menuForm.value[k]))
    if (menuFile.value) fd.append('gambar', menuFile.value)
    if (editingMenu.value) { fd.append('_method', 'PUT'); await api.post(`/menu/${editingMenu.value.id}`, fd) }
    else { await api.post('/menu', fd) }
    showMenuModal.value = false; await loadMenus()
  } catch (e) { alert(e.response?.data?.message || e.message || 'Gagal menyimpan menu') }
}
const deleteMenu = async (id) => { if (!confirm('Yakin hapus menu ini?')) return; try { await api.delete(`/menu/${id}`); await loadMenus() } catch (e) { alert(e.message || 'Gagal menghapus menu') } }

// ===== KATEGORI CRUD =====
const openKategoriModal = (kat = null) => {
  editingKategori.value = kat
  kategoriForm.value = kat ? { nama_kategori: kat.nama_kategori } : { nama_kategori: '' }
  showKategoriModal.value = true
}
const saveKategori = async () => {
  try {
    if (editingKategori.value) { await api.put(`/kategori/${editingKategori.value.id}`, kategoriForm.value) }
    else { await api.post('/kategori', kategoriForm.value) }
    showKategoriModal.value = false; await loadKategori()
  } catch (e) { alert(e.message || 'Gagal menyimpan kategori') }
}
const deleteKategori = async (id) => { if (!confirm('Yakin hapus kategori ini?')) return; try { await api.delete(`/kategori/${id}`); await loadKategori() } catch (e) { alert(e.message || 'Gagal menghapus kategori') } }

// ===== LOGOUT =====
const handleLogout = async () => { await authStore.logout(); router.push('/login') }

watch(activeTab, (t) => {
  if (t === 'laporan') loadLaporan()
  if (t === 'riwayat') loadRiwayat()
})
onMounted(() => loadAll())
</script>

<template>
  <div class="adm" @click="adminMenuOpen = false" @keydown.esc="handleEscape">
    <!-- SIDEBAR desktop -->
    <aside class="adm__side hidden md:flex">
      <div class="adm__brand">
        <img src="/logo.png" alt="LocalBowls" class="adm__brand-img" />
        <span class="adm__brand-word">LocalBowls</span>
      </div>
      <nav class="adm__nav">
        <button v-for="item in navItems" :key="item.id" type="button" @click="selectTab(item.id)" class="adm__nav-link" :class="{ 'is-active': activeTab === item.id }" :aria-current="activeTab === item.id ? 'page' : undefined">
          <svg v-if="item.icon === 'grid'" class="adm__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.75" y="3.75" width="7" height="7" rx="1.5"/><rect x="13.25" y="3.75" width="7" height="7" rx="1.5"/><rect x="3.75" y="13.25" width="7" height="7" rx="1.5"/><rect x="13.25" y="13.25" width="7" height="7" rx="1.5"/></svg>
          <svg v-else-if="item.icon === 'users'" class="adm__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="7.5" r="3.25"/><path d="M3.5 20a5.5 5.5 0 0111 0"/><circle cx="17" cy="8.5" r="2.5"/><path d="M15 20a4.5 4.5 0 015.5-4.4"/></svg>
          <svg v-else-if="item.icon === 'bowl'" class="adm__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3.5 11h17a8.5 6 0 01-17 0z"/><path d="M6 11a6 6 0 0112 0"/><path d="M9 3.5c-.7.7-.7 1.3 0 2M12 3c-.7.7-.7 1.3 0 2"/></svg>
          <svg v-else-if="item.icon === 'tag'" class="adm__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10.6 3H5.75A1.75 1.75 0 004 4.75v4.85c0 .46.18.9.51 1.24l8.4 8.4a1.75 1.75 0 002.47 0l4.85-4.85a1.75 1.75 0 000-2.47l-8.4-8.4A1.75 1.75 0 0010.6 3z"/><circle cx="7.75" cy="7.75" r="1"/></svg>
          <svg v-else-if="item.icon === 'chart'" class="adm__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="12.5" width="4" height="8" rx="0.8"/><rect x="10" y="8" width="4" height="12.5" rx="0.8"/><rect x="16.5" y="4" width="4" height="16.5" rx="0.8"/></svg>
          <svg v-else-if="item.icon === 'table'" class="adm__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="3.5" width="6" height="6" rx="1"/><rect x="14.5" y="3.5" width="6" height="6" rx="1"/><rect x="3.5" y="14.5" width="6" height="6" rx="1"/><rect x="14.5" y="14.5" width="6" height="6" rx="1"/></svg>
          <svg v-else-if="item.icon === 'receipt'" class="adm__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 3v18l2.5-2 2.5 2 2.5-2 2.5 2V3H5z"/><path d="M8 8h6M8 12h4"/></svg>
          {{ item.label }}
        </button>
      </nav>
      <div class="adm__side-user">
        <p class="adm__side-name">{{ authStore.user?.nama || 'Admin' }}</p>
        <p class="adm__side-role">Administrator</p>
        <button type="button" @click="handleLogout" class="adm__side-logout">Keluar</button>
      </div>
    </aside>

    <!-- SIDEBAR mobile -->
    <div v-if="mobileSidebarOpen" class="md:hidden adm__drawer-overlay">
    <button type="button" class="adm__drawer-bg" aria-label="Tutup menu" @click="mobileSidebarOpen = false"></button>
    <aside class="adm__side adm__drawer" aria-label="Navigasi admin">
        <div class="adm__brand">
          <img src="/logo.png" alt="" class="adm__brand-img" />
          <span class="adm__brand-word">LocalBowls</span>
        <button type="button" @click="mobileSidebarOpen = false" class="adm__drawer-close" aria-label="Tutup menu">✕</button>
        </div>
        <nav class="adm__nav">
        <button v-for="item in navItems" :key="item.id" type="button" @click="selectTab(item.id)" class="adm__nav-link" :class="{ 'is-active': activeTab === item.id }" :aria-current="activeTab === item.id ? 'page' : undefined">{{ item.label }}</button>
        </nav>
        <div class="adm__side-user">
          <p class="adm__side-name">{{ authStore.user?.nama || 'Admin' }}</p>
          <button type="button" @click="handleLogout" class="adm__side-logout">Keluar</button>
        </div>
      </aside>
    </div>

    <!-- MAIN -->
    <div class="adm__main">
      <!-- Topbar -->
      <header class="adm__topbar">
        <div class="adm__topbar-left">
          <button type="button" @click.stop="mobileSidebarOpen = true" class="adm__burger md:hidden" aria-label="Buka menu" :aria-expanded="mobileSidebarOpen">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
          </button>
          <h1 class="adm__topbar-title">{{ pageTitle }}</h1>
        </div>
        <div class="adm__topbar-right">
          <button type="button" class="adm__avatar-wrap" @click.stop="adminMenuOpen = !adminMenuOpen" aria-haspopup="menu" :aria-expanded="adminMenuOpen" aria-label="Menu akun">
            <span class="adm__avatar">{{ adminInitials }}</span>
            <span class="adm__avatar-name hidden sm:inline">{{ authStore.user?.nama || 'Admin' }}</span>
          </button>
          <div v-if="adminMenuOpen" class="adm__dropdown" role="menu" @click.stop>
            <p class="adm__dropdown-name">{{ authStore.user?.nama || 'Admin' }}</p>
            <p class="adm__dropdown-role">Administrator</p>
            <hr />
            <button type="button" role="menuitem" @click="handleLogout" class="adm__dropdown-btn">Keluar</button>
          </div>
        </div>
      </header>

      <div class="adm__content">
        <div v-if="loading" class="adm__loading"><div class="lb-spinner"></div><p>Memuat data admin...</p></div>
        <template v-else>

        <!-- ===== DASHBOARD ===== -->
        <div v-if="activeTab === 'dashboard'">
          <div class="adm__welcome">
            <p class="adm__welcome-eyebrow">Selamat Datang,</p>
            <h2 class="adm__welcome-title">{{ authStore.user?.nama || 'Admin' }}</h2>
            <p class="adm__welcome-sub">Kelola data restoran dengan mudah dan cepat.</p>
          </div>

          <div class="adm__stats">
            <div class="adm__stat" style="--stat-accent: #D97757;">
              <div><p class="adm__stat-label">Total User</p><p class="adm__stat-num">{{ userList.length }}</p></div>
              <span class="adm__stat-icon" style="background: #FFF3E0;"><svg viewBox="0 0 24 24" fill="none" stroke="#D97757" stroke-width="1.8"><circle cx="12" cy="8" r="3.25"/><path d="M5 20a7 7 0 0114 0"/></svg></span>
            </div>
            <div class="adm__stat" style="--stat-accent: #4CAF50;">
              <div><p class="adm__stat-label">Total Menu</p><p class="adm__stat-num">{{ menuList.length }}</p></div>
              <span class="adm__stat-icon" style="background: #E8F5E9;"><svg viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="1.8"><path d="M4 12h16a8 5.5 0 01-16 0z"/><path d="M6.5 12a5.5 5.5 0 0111 0"/></svg></span>
            </div>
            <div class="adm__stat" style="--stat-accent: #7C4DFF;">
              <div><p class="adm__stat-label">Kategori</p><p class="adm__stat-num">{{ kategoriList.length }}</p></div>
              <span class="adm__stat-icon" style="background: #EDE7F6;"><svg viewBox="0 0 24 24" fill="none" stroke="#7C4DFF" stroke-width="1.8"><path d="M10.6 3H5.75A1.75 1.75 0 004 4.75v4.85c0 .46.18.9.51 1.24l8.4 8.4a1.75 1.75 0 002.47 0l4.85-4.85a1.75 1.75 0 000-2.47l-8.4-8.4A1.75 1.75 0 0010.6 3z"/><circle cx="7.75" cy="7.75" r="1"/></svg></span>
            </div>
            <div class="adm__stat" style="--stat-accent: #1E88E5;">
              <div><p class="adm__stat-label">Total Meja</p><p class="adm__stat-num">{{ mejaList.length }}</p></div>
              <span class="adm__stat-icon" style="background: #E3F2FD;"><svg viewBox="0 0 24 24" fill="none" stroke="#1E88E5" stroke-width="1.8"><rect x="3.5" y="3.5" width="6" height="6" rx="1"/><rect x="14.5" y="3.5" width="6" height="6" rx="1"/><rect x="3.5" y="14.5" width="6" height="6" rx="1"/><rect x="14.5" y="14.5" width="6" height="6" rx="1"/></svg></span>
            </div>
          </div>

          <div class="adm__charts">
            <div class="lb-card adm__chart-card">
              <div class="adm__chart-head"><p class="adm__chart-title">Penjualan Mingguan</p><span class="adm__chart-hint">Berdasarkan transaksi termuat</span></div>
              <svg :viewBox="`0 0 ${CHART_W} ${CHART_H}`" class="adm__line-chart" role="img" aria-label="Grafik penjualan mingguan">
                <defs><linearGradient id="aFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="var(--lb-brand)" stop-opacity="0.3" /><stop offset="100%" stop-color="var(--lb-brand)" stop-opacity="0" /></linearGradient></defs>
                <path :d="areaPath" fill="url(#aFill)" /><path :d="linePath" fill="none" stroke="var(--lb-brand)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                <circle v-for="(p, i) in chartPoints" :key="i" :cx="p.x" :cy="p.y" r="4.5" fill="var(--lb-brand)" stroke="#fff" stroke-width="2.5" />
              </svg>
              <div class="adm__chart-labels"><span v-for="d in weeklyChartData" :key="d.label">{{ d.label }}</span></div>
            </div>
            <div class="lb-card adm__chart-card">
              <p class="adm__chart-title">Kategori Menu</p>
              <div v-if="kategoriBreakdown.length === 0" class="adm__chart-empty">Belum ada data kategori.</div>
              <div v-else class="adm__donut-wrap">
                <div class="adm__donut" role="img" :aria-label="`Komposisi ${kategoriBreakdown.length} kategori dari ${menuList.length} menu`" :style="{ background: donutBackground }"><div class="adm__donut-hole"><strong>{{ menuList.length }}</strong><span>Menu</span></div></div>
                <div class="adm__donut-legend">
                  <div v-for="k in kategoriBreakdown" :key="k.id" class="adm__donut-item">
                    <span class="adm__donut-dot" :style="{ background: k.color }"></span>
                    <span class="adm__donut-label">{{ k.label }}</span>
                    <span class="adm__donut-pct">{{ k.percent }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="adm__bottom-row">
            <div class="lb-card adm__recent">
              <div class="adm__chart-head"><p class="adm__chart-title">Menu Terbaru</p><button type="button" @click="selectTab('menu')" class="adm__see-all">Lihat Semua →</button></div>
              <div v-if="menuTerbaru.length === 0" class="adm__chart-empty">Belum ada menu.</div>
              <table v-else class="adm__table adm__table--compact">
                <tbody>
                  <tr v-for="menu in menuTerbaru" :key="menu.id">
                    <td class="adm__td-img"><img :src="getGambarUrl(menu.gambar)" :alt="menu.nama_menu" /></td>
                    <td><strong>{{ menu.nama_menu }}</strong><br /><span class="adm__chip" :style="{ background: kategoriColor(menu.id_kategori), color: '#fff' }">{{ namaKategori(menu.id_kategori) }}</span></td>
                    <td class="is-num"><strong>Rp {{ fmt(menu.harga) }}</strong></td>
                    <td><span class="adm__chip" :style="{ background: menu.status_tersedia ? 'var(--lb-green-bg)' : 'var(--lb-red-bg)', color: menu.status_tersedia ? 'var(--lb-green-text)' : 'var(--lb-red-text)' }">{{ menu.status_tersedia ? 'Tersedia' : 'Habis' }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="lb-card adm__quick">
              <p class="adm__chart-title">Menu Cepat</p>
              <div class="adm__quick-grid">
                <button @click="selectTab('user')" class="adm__quick-btn" style="background: #FFF3E0;">Kelola User →</button>
                <button @click="selectTab('menu')" class="adm__quick-btn" style="background: #E8F5E9;">Kelola Menu →</button>
                <button @click="selectTab('kategori')" class="adm__quick-btn" style="background: #EDE7F6;">Kategori →</button>
                <button @click="selectTab('laporan')" class="adm__quick-btn" style="background: #E3F2FD;">Laporan →</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== KELOLA USER ===== -->
        <div v-if="activeTab === 'user'">
          <div class="adm__section-head"><h2>Kelola User</h2><button @click="openUserModal()" class="lb-btn-primary">+ Tambah User</button></div>
          <div class="lb-card adm__table-wrap">
            <table class="adm__table">
              <thead><tr><th>ID</th><th>Nama</th><th>Username</th><th>Role</th><th>Aksi</th></tr></thead>
              <tbody>
                <tr v-for="user in userList" :key="user.id">
                  <td>{{ user.id }}</td>
                  <td><strong>{{ user.nama }}</strong></td>
                  <td>{{ user.username }}</td>
                  <td><span class="adm__chip" :style="{ background: roleBadge(user.role).bg, color: roleBadge(user.role).color }">{{ user.role }}</span></td>
                  <td><div class="adm__actions"><button @click="openUserModal(user)" class="adm__act-edit">Edit</button><button @click="deleteUser(user.id)" class="adm__act-del">Hapus</button></div></td>
                </tr>
                <tr v-if="userList.length === 0"><td colspan="5" style="text-align:center;color:var(--lb-muted);padding:2rem;">Belum ada user. Klik “+ Tambah User” untuk menambah yang pertama.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===== KELOLA MENU ===== -->
        <div v-if="activeTab === 'menu'">
          <div class="adm__section-head"><h2>Kelola Menu</h2><button @click="openMenuModal()" class="lb-btn-primary">+ Tambah Menu</button></div>
          <div class="adm__menu-grid">
            <p v-if="menuList.length === 0" class="lb-empty" style="grid-column:1/-1;">Belum ada menu. Klik “+ Tambah Menu” untuk menambah yang pertama.</p>
            <div v-for="menu in menuList" :key="menu.id" class="lb-card adm__menu-card">
              <img :src="getGambarUrl(menu.gambar)" :alt="menu.nama_menu" class="adm__menu-img" @error="$event.target.src = '/logo.png'" />
              <div class="adm__menu-body">
                <h3>{{ menu.nama_menu }}</h3>
                <p class="adm__menu-desc">{{ menu.deskripsi || '-' }}</p>
                <div class="adm__menu-meta">
                  <strong>Rp {{ fmt(menu.harga) }}</strong>
                  <span class="adm__chip" :style="{ background: menu.status_tersedia ? 'var(--lb-green-bg)' : 'var(--lb-red-bg)', color: menu.status_tersedia ? 'var(--lb-green-text)' : 'var(--lb-red-text)' }">{{ menu.status_tersedia ? 'Tersedia' : 'Habis' }}</span>
                </div>
                <div class="adm__menu-actions">
                  <button @click="openMenuModal(menu)" class="adm__act-edit">Edit</button>
                  <button @click="deleteMenu(menu.id)" class="adm__act-del">Hapus</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== KATEGORI ===== -->
        <div v-if="activeTab === 'kategori'">
          <div class="adm__section-head"><h2>Kelola Kategori</h2><button @click="openKategoriModal()" class="lb-btn-primary">+ Tambah Kategori</button></div>
          <div class="lb-card adm__table-wrap">
            <table class="adm__table">
              <thead><tr><th>ID</th><th>Nama Kategori</th><th>Jumlah Menu</th><th>Aksi</th></tr></thead>
              <tbody>
                <tr v-for="kat in kategoriList" :key="kat.id">
                  <td>{{ kat.id }}</td>
                  <td><strong>{{ kat.nama_kategori }}</strong></td>
                  <td>{{ menuList.filter(m => m.id_kategori === kat.id).length }}</td>
                  <td><div class="adm__actions"><button @click="openKategoriModal(kat)" class="adm__act-edit">Edit</button><button @click="deleteKategori(kat.id)" class="adm__act-del">Hapus</button></div></td>
                </tr>
                <tr v-if="kategoriList.length === 0"><td colspan="4" style="text-align:center;color:var(--lb-muted);padding:2rem;">Belum ada kategori. Klik “+ Tambah Kategori” untuk menambah yang pertama.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===== MEJA ===== -->
        <div v-if="activeTab === 'meja'">
          <div class="adm__section-head"><h2>Kelola Meja</h2><button @click="openMejaModal()" class="lb-btn-primary">+ Tambah Meja</button></div>
          <div class="lb-card adm__table-wrap">
            <table class="adm__table">
              <thead><tr><th>ID</th><th>Nomor Meja</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody>
                <tr v-for="meja in mejaList" :key="meja.id">
                  <td>{{ meja.id }}</td>
                  <td><strong>Meja {{ meja.nomor_meja }}</strong></td>
                  <td>
                    <button type="button" @click="toggleStatusMeja(meja)" class="adm__chip" :style="{ background: meja.status_meja === 'kosong' ? 'var(--lb-green-bg)' : 'var(--lb-red-bg)', color: meja.status_meja === 'kosong' ? 'var(--lb-green-text)' : 'var(--lb-red-text)', border: 'none', cursor: 'pointer' }">
                      {{ meja.status_meja }}
                    </button>
                  </td>
                  <td><div class="adm__actions"><button @click="openMejaModal(meja)" class="adm__act-edit">Edit</button><button @click="deleteMeja(meja.id)" class="adm__act-del">Hapus</button></div></td>
                </tr>
                <tr v-if="mejaList.length === 0"><td colspan="4" style="text-align:center;color:var(--lb-muted);padding:2rem;">Belum ada meja. Klik “+ Tambah Meja” untuk menambah yang pertama.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===== RIWAYAT TRANSAKSI ===== -->
        <div v-if="activeTab === 'riwayat'">
          <h2 class="adm__section-title">Riwayat Transaksi</h2>
          <div class="lb-card adm__table-wrap">
            <table class="adm__table">
              <thead><tr><th>No. Struk</th><th>Pelanggan</th><th>Meja</th><th>Kasir</th><th>Tanggal</th><th>Metode</th><th class="is-num">Total</th></tr></thead>
              <tbody>
                <tr v-for="trx in riwayatList" :key="trx.id">
                  <td><span style="font-family:monospace;font-size:0.78rem;">{{ trx.nomor_struk_digital }}</span></td>
                  <td>{{ trx.pesanan?.nama_pelanggan || 'Tamu' }}</td>
                  <td>Meja {{ trx.pesanan?.meja?.nomor_meja ?? '—' }}</td>
                  <td>{{ trx.kasir?.nama || '—' }}</td>
                  <td>{{ formatDate(trx.tanggal_bayar) }}</td>
                  <td><span class="adm__chip" style="background:var(--lb-green-bg);color:var(--lb-green-text);">{{ trx.metode_pembayaran }}</span></td>
                  <td class="is-num"><strong>Rp {{ fmt(trx.total_bayar) }}</strong></td>
                </tr>
                <tr v-if="riwayatList.length === 0"><td colspan="7" style="text-align:center;color:var(--lb-muted);padding:2rem;">Belum ada transaksi.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===== LAPORAN PENJUALAN ===== -->
        <div v-if="activeTab === 'laporan'">
          <h2 class="adm__section-title">Laporan Penjualan</h2>
          <div class="adm__stats">
            <div class="adm__stat" style="--stat-accent:#D97757;"><div><p class="adm__stat-label">Transaksi Hari Ini</p><p class="adm__stat-num">{{ reportStats.totalTransaksi }}</p></div></div>
            <div class="adm__stat" style="--stat-accent:#4CAF50;"><div><p class="adm__stat-label">Pendapatan Hari Ini</p><p class="adm__stat-num">Rp {{ fmt(reportStats.totalPendapatan) }}</p></div></div>
            <div class="adm__stat" style="--stat-accent:#7C4DFF;"><div><p class="adm__stat-label">Total Minggu Ini</p><p class="adm__stat-num">Rp {{ fmt(reportStats.totalMinggu) }}</p></div></div>
            <div class="adm__stat" style="--stat-accent:#1E88E5;"><div><p class="adm__stat-label">Total Bulan Ini</p><p class="adm__stat-num">Rp {{ fmt(reportStats.totalBulan) }}</p></div></div>
          </div>
          <h3 class="adm__sub-title">Menu Terlaris</h3>
          <div class="lb-card adm__table-wrap" style="margin-bottom:1.25rem;">
            <table class="adm__table">
              <thead><tr><th>Menu</th><th class="is-num">Total Terjual</th></tr></thead>
              <tbody>
                <tr v-for="m in menuTerlaris" :key="m.id_menu">
                  <td><strong>{{ m.menu?.nama_menu || '—' }}</strong></td>
                  <td class="is-num">{{ m.total_terjual }} porsi</td>
                </tr>
                <tr v-if="menuTerlaris.length === 0"><td colspan="2" style="text-align:center;color:var(--lb-muted);padding:1.5rem;">Belum ada data.</td></tr>
              </tbody>
            </table>
          </div>
          <h3 class="adm__sub-title">10 Transaksi Terbaru</h3>
          <div class="lb-card adm__table-wrap">
            <table class="adm__table">
              <thead><tr><th>No. Struk</th><th>Meja</th><th>Tanggal</th><th>Metode</th><th class="is-num">Total</th></tr></thead>
              <tbody>
                <tr v-for="trx in recentTransactions" :key="trx.id">
                  <td><span style="font-family:monospace;font-size:0.78rem;">{{ trx.nomor_struk_digital }}</span></td>
                  <td>Meja {{ trx.pesanan?.meja?.nomor_meja ?? '—' }}</td>
                  <td>{{ formatDate(trx.tanggal_bayar) }}</td>
                  <td><span class="adm__chip" style="background:var(--lb-green-bg);color:var(--lb-green-text);">{{ trx.metode_pembayaran }}</span></td>
                  <td class="is-num"><strong>Rp {{ fmt(trx.total_bayar) }}</strong></td>
                </tr>
                <tr v-if="recentTransactions.length === 0"><td colspan="5" style="text-align:center;color:var(--lb-muted);padding:1.5rem;">Belum ada data.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        </template>

      </div>
    </div>

    <!-- ===== MODAL: USER ===== -->
    <div v-if="showUserModal" class="adm__overlay" @click.self="showUserModal = false">
      <div class="lb-card adm__modal" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
        <h3 id="user-modal-title">{{ editingUser ? 'Edit User' : 'Tambah User' }}</h3>
        <form @submit.prevent="saveUser" class="adm__form">
          <label>Nama <input v-model="userForm.nama" class="lb-input" required /></label>
          <label>Username <input v-model="userForm.username" class="lb-input" required /></label>
          <label>Password {{ editingUser ? '(kosongkan jika tidak diubah)' : '' }} <input v-model="userForm.password" type="password" class="lb-input" :required="!editingUser" /></label>
          <label>Role
            <select v-model="userForm.role" class="lb-input" required>
              <option value="admin">Admin</option><option value="kitchen">Dapur</option><option value="kasir">Kasir</option>
            </select>
          </label>
          <div class="adm__modal-foot"><button type="button" @click="showUserModal = false" class="lb-btn-ghost">Batal</button><button type="submit" class="lb-btn-primary">Simpan</button></div>
        </form>
      </div>
    </div>

    <!-- ===== MODAL: MENU ===== -->
    <div v-if="showMenuModal" class="adm__overlay" @click.self="showMenuModal = false">
      <div class="lb-card adm__modal adm__modal--scroll" role="dialog" aria-modal="true" aria-labelledby="menu-modal-title">
        <h3 id="menu-modal-title">{{ editingMenu ? 'Edit Menu' : 'Tambah Menu' }}</h3>
        <form @submit.prevent="saveMenu" class="adm__form">
          <label>Nama Menu <input v-model="menuForm.nama_menu" class="lb-input" required /></label>
          <label>Kategori
            <select v-model="menuForm.id_kategori" class="lb-input" required>
              <option v-for="kat in kategoriList" :key="kat.id" :value="kat.id">{{ kat.nama_kategori }}</option>
            </select>
          </label>
          <label>Deskripsi <textarea v-model="menuForm.deskripsi" class="lb-input" rows="3"></textarea></label>
          <label>Harga <input v-model.number="menuForm.harga" type="number" class="lb-input" required min="0" /></label>
          <label>Foto Menu <input type="file" @change="handleFileUpload" accept="image/*" /> <img v-if="menuPreview" :src="menuPreview" class="adm__preview" /></label>
          <label class="adm__check"><input v-model="menuForm.status_tersedia" type="checkbox" /> Tersedia</label>
          <div class="adm__modal-foot"><button type="button" @click="showMenuModal = false" class="lb-btn-ghost">Batal</button><button type="submit" class="lb-btn-primary">Simpan</button></div>
        </form>
      </div>
    </div>

    <!-- ===== MODAL: KATEGORI ===== -->
    <div v-if="showKategoriModal" class="adm__overlay" @click.self="showKategoriModal = false">
      <div class="lb-card adm__modal" role="dialog" aria-modal="true" aria-labelledby="kategori-modal-title">
        <h3 id="kategori-modal-title">{{ editingKategori ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
        <form @submit.prevent="saveKategori" class="adm__form">
          <label>Nama Kategori <input v-model="kategoriForm.nama_kategori" class="lb-input" required /></label>
          <div class="adm__modal-foot"><button type="button" @click="showKategoriModal = false" class="lb-btn-ghost">Batal</button><button type="submit" class="lb-btn-primary">Simpan</button></div>
        </form>
      </div>
    </div>

    <!-- ===== MODAL: MEJA ===== -->
    <div v-if="showMejaModal" class="adm__overlay" @click.self="showMejaModal = false">
      <div class="lb-card adm__modal" role="dialog" aria-modal="true" aria-labelledby="meja-modal-title">
        <h3 id="meja-modal-title">{{ editingMeja ? 'Edit Meja' : 'Tambah Meja' }}</h3>
        <form @submit.prevent="saveMeja" class="adm__form">
          <label>Nomor Meja <input v-model="mejaForm.nomor_meja" class="lb-input" required placeholder="Contoh: 1, 2, A1" /></label>
          <div class="adm__modal-foot"><button type="button" @click="showMejaModal = false" class="lb-btn-ghost">Batal</button><button type="submit" class="lb-btn-primary">Simpan</button></div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ===== LAYOUT ===== */
.adm { display: flex; min-height: 100vh; min-height: 100dvh; background: var(--lb-bg); color: var(--lb-ink); font-family: 'Plus Jakarta Sans', sans-serif; }

/* SIDEBAR */
.adm__side { width: 16rem; flex-shrink: 0; flex-direction: column; background: linear-gradient(180deg, var(--lb-sidebar-dark) 0%, var(--lb-sidebar-mid) 60%, var(--lb-sidebar-light) 100%); color: var(--lb-sidebar-text); }
.adm > .adm__side { position: sticky; top: 0; height: 100vh; height: 100dvh; }
.adm__brand { height: 4rem; display: flex; align-items: center; gap: 0.6rem; padding-inline: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
.adm__brand-img { width: 2.25rem; height: 2.25rem; border-radius: 50%; object-fit: cover; background: #fff; border: 1px solid rgba(255,255,255,0.25); flex-shrink: 0; }
.adm__brand-word { font-family: 'Fraunces', Georgia, serif; font-size: 1.05rem; font-weight: 800; color: #fff; }
.adm__nav { flex: 1; padding: 0.9rem 0.75rem; display: grid; gap: 0.2rem; align-content: start; overflow-y: auto; }
.adm__nav-link { display: flex; align-items: center; gap: 0.65rem; padding: 0.65rem 0.8rem; border-radius: 12px; border: none; background: none; font-size: 0.88rem; font-weight: 600; color: rgba(255,217,163,0.7); cursor: pointer; text-align: left; width: 100%; }
.adm__nav-link:hover { background: rgba(255,255,255,0.07); color: #fff; }
.adm__nav-link.is-active { background: var(--lb-brand); color: #fff; }
.adm__nav-icon { width: 1.1rem; height: 1.1rem; flex-shrink: 0; }
.adm__side-user { padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); }
.adm__side-name { margin: 0; font-size: 0.88rem; font-weight: 700; color: #fff; }
.adm__side-role { margin: 0.15rem 0 0.6rem; font-size: 0.72rem; opacity: 0.6; }
.adm__side-logout { width: 100%; text-align: left; padding: 0.55rem 0.8rem; border-radius: 10px; border: none; background: transparent; color: rgba(255,217,163,0.7); font-size: 0.82rem; font-weight: 600; cursor: pointer; }
.adm__side-logout:hover { background: rgba(255,255,255,0.08); color: #fff; }

/* MOBILE DRAWER */
.adm__drawer-overlay { position: fixed; inset: 0; z-index: 50; display: flex; }
.adm__drawer-bg { position: absolute; inset: 0; width: 100%; border: 0; background: rgba(0,0,0,0.5); cursor: pointer; }
.adm__drawer { position: relative; display: flex !important; flex-direction: column; max-width: min(19rem, 88vw); height: 100%; overflow-y: auto; box-shadow: 12px 0 40px rgba(30, 25, 20, 0.2); }
.adm__drawer-close { margin-left: auto; background: none; border: none; color: rgba(255,255,255,0.7); font-size: 1rem; cursor: pointer; }

/* TOPBAR */
.adm__topbar { height: 4rem; flex-shrink: 0; position: sticky; top: 0; z-index: 30; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-inline: 1.25rem; background: rgba(255,255,255,0.94); border-bottom: 1px solid var(--lb-line); backdrop-filter: blur(12px); }
.adm__topbar-left { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }
.adm__burger { width: 2.25rem; height: 2.25rem; border-radius: 10px; border: 1px solid var(--lb-line); background: #fff; cursor: pointer; display: grid; place-items: center; }
.adm__burger svg { width: 1.1rem; height: 1.1rem; }
.adm__topbar-title { margin: 0; font-size: 1.05rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.adm__topbar-right { display: flex; align-items: center; gap: 0.5rem; position: relative; }
.adm__avatar-wrap { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; border: 0; padding: 0.25rem; border-radius: 999px; background: transparent; color: inherit; font: inherit; }
.adm__avatar-wrap:hover { background: var(--lb-soft); }
.adm__avatar { width: 2.25rem; height: 2.25rem; border-radius: 50%; background: var(--lb-ink); color: #fff; display: grid; place-items: center; font-size: 0.8rem; font-weight: 800; flex-shrink: 0; }
.adm__avatar-name { font-size: 0.85rem; font-weight: 600; }
.adm__dropdown { position: absolute; right: 0; top: 3rem; width: 12rem; background: #fff; border-radius: 14px; box-shadow: 0 12px 32px rgba(0,0,0,0.12); border: 1px solid var(--lb-line); padding: 0.75rem; z-index: 50; }
.adm__dropdown-name { margin: 0; font-size: 0.85rem; font-weight: 700; }
.adm__dropdown-role { margin: 0.1rem 0 0; font-size: 0.72rem; color: var(--lb-muted); }
.adm__dropdown hr { border: none; border-top: 1px solid var(--lb-line); margin: 0.5rem 0; }
.adm__dropdown-btn { width: 100%; text-align: left; padding: 0.5rem 0.6rem; border-radius: 8px; border: none; background: none; font-size: 0.82rem; cursor: pointer; }
.adm__dropdown-btn:hover { background: var(--lb-soft); }

/* MAIN */
.adm__main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.adm__content { flex: 1; min-width: 0; padding: 1.5rem clamp(1rem, 2.5vw, 2rem); }
.adm__loading { text-align: center; padding: 4rem 1rem; color: var(--lb-muted); display: grid; gap: 0.75rem; justify-items: center; }
.adm__loading p { margin: 0; }
@media (min-width: 640px) { .adm__content { padding: 1.5rem; } }

/* WELCOME */
.adm__welcome { background: radial-gradient(circle at 90% 10%, rgba(255,255,255,0.72), transparent 35%), linear-gradient(135deg, #FBEAD9 0%, var(--lb-bg) 100%); border: 1px solid rgba(217,119,87,0.1); border-radius: 20px; padding: 1.5rem 1.75rem; margin-bottom: 1.25rem; }
.adm__welcome-eyebrow { margin: 0; font-size: 0.82rem; color: var(--lb-muted); }
.adm__welcome-title { margin: 0.15rem 0 0.25rem; font-family: 'Fraunces', Georgia, serif; font-size: clamp(1.4rem, 4vw, 1.8rem); font-weight: 800; }
.adm__welcome-sub { margin: 0; font-size: 0.85rem; color: var(--lb-muted); }

/* STAT CARDS */
.adm__stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.85rem; margin-bottom: 1.25rem; }
.adm__stats--3 { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
.adm__stat { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; min-width: 0; padding: 1.15rem 1.25rem; background: #fff; border-radius: 16px; border: 1px solid var(--lb-line); border-left: 4px solid var(--stat-accent, var(--lb-brand)); box-shadow: 0 4px 16px rgba(51, 42, 31, 0.035); }
.adm__stat-label { margin: 0; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--lb-muted); }
.adm__stat-num { margin: 0.2rem 0 0; font-family: 'Fraunces', Georgia, serif; font-size: 1.5rem; font-weight: 700; }
.adm__stat-icon { width: 2.75rem; height: 2.75rem; border-radius: 14px; display: grid; place-items: center; flex-shrink: 0; }
.adm__stat-icon svg { width: 1.25rem; height: 1.25rem; }

/* CHARTS */
.adm__charts { display: grid; grid-template-columns: 1fr; gap: 0.85rem; margin-bottom: 1.25rem; }
@media (min-width: 1024px) { .adm__charts { grid-template-columns: 1.2fr 0.8fr; } }
.adm__chart-card { min-width: 0; padding: 1.25rem; }
.adm__chart-head { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 1rem; gap: 0.5rem; }
.adm__chart-title { margin: 0; font-size: 0.95rem; font-weight: 800; }
.adm__chart-hint { font-size: 0.72rem; color: var(--lb-faint); }
.adm__chart-empty { font-size: 0.85rem; color: var(--lb-muted); padding: 1rem 0; }
.adm__line-chart { width: 100%; height: 12rem; overflow: visible; }
.adm__chart-labels { display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--lb-faint); padding-inline: 0.25rem; margin-top: 0.25rem; }

/* DONUT */
.adm__donut-wrap { display: flex; align-items: center; gap: 1.5rem; }
.adm__donut { position: relative; width: 8rem; height: 8rem; border-radius: 50%; flex-shrink: 0; }
.adm__donut-hole { position: absolute; inset: 0.75rem; background: #fff; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.adm__donut-hole strong { font-family: 'Fraunces', Georgia, serif; font-size: 1.4rem; }
.adm__donut-hole span { font-size: 0.68rem; color: var(--lb-muted); }
.adm__donut-legend { flex: 1; min-width: 0; display: grid; gap: 0.35rem; }
.adm__donut-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; }
.adm__donut-dot { width: 0.55rem; height: 0.55rem; border-radius: 50%; flex-shrink: 0; }
.adm__donut-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.adm__donut-pct { color: var(--lb-muted); font-variant-numeric: tabular-nums; }

/* BOTTOM ROW */
.adm__bottom-row { display: grid; grid-template-columns: 1fr; gap: 0.85rem; }
@media (min-width: 1024px) { .adm__bottom-row { grid-template-columns: 1.3fr 0.7fr; } }
.adm__recent { padding: 1.25rem; }
.adm__see-all { background: none; border: none; color: var(--lb-brand-dark); font-size: 0.8rem; font-weight: 700; cursor: pointer; }
.adm__quick { padding: 1.25rem; }
.adm__quick-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; }
.adm__quick-btn { border: none; border-radius: 14px; padding: 0.9rem 1rem; text-align: left; font-size: 0.82rem; font-weight: 700; cursor: pointer; color: var(--lb-ink); }
.adm__quick-btn:hover { opacity: 0.85; }

/* TABLE */
.adm__section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.adm__section-head h2 { margin: 0; font-size: 1.15rem; font-weight: 800; }
.adm__section-title { margin: 0 0 1rem; font-size: 1.15rem; font-weight: 800; }
.adm__sub-title { margin: 1.5rem 0 0.75rem; font-size: 1rem; font-weight: 800; }
.adm__table-wrap { overflow-x: auto; overscroll-behavior-inline: contain; -webkit-overflow-scrolling: touch; }
.adm__table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.adm__table th { position: sticky; top: 0; text-align: left; padding: 0.75rem 1rem; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--lb-muted); background: var(--lb-soft); border-bottom: 1px solid var(--lb-line); white-space: nowrap; }
.adm__table td { padding: 0.7rem 1rem; border-bottom: 1px solid var(--lb-line); vertical-align: middle; }
.adm__table tr:last-child td { border-bottom: none; }
.adm__table tbody tr:not(:last-child):hover { background: rgba(248, 244, 238, 0.62); }
.adm__table td.is-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.adm__table--compact td { padding: 0.55rem 0.75rem; }
.adm__td-img img { width: 2.5rem; height: 2.5rem; border-radius: 10px; object-fit: cover; display: block; }
.adm__chip { display: inline-block; font-size: 0.68rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 999px; white-space: nowrap; text-transform: capitalize; }
.adm__actions { display: flex; gap: 0.75rem; }
.adm__act-edit { background: none; border: none; font-size: 0.82rem; font-weight: 600; color: var(--lb-brand-dark); cursor: pointer; padding: 0; }
.adm__act-del { background: none; border: none; font-size: 0.82rem; font-weight: 600; color: #C62828; cursor: pointer; padding: 0; }

/* MENU GRID */
.adm__menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 260px), 1fr)); gap: 0.85rem; }
.adm__menu-card { overflow: hidden; display: flex; flex-direction: column; }
.adm__menu-img { width: 100%; height: 9rem; object-fit: cover; }
.adm__menu-body { padding: 1rem; flex: 1; display: flex; flex-direction: column; gap: 0.35rem; }
.adm__menu-body h3 { margin: 0; font-size: 0.95rem; font-weight: 800; }
.adm__menu-desc { margin: 0; font-size: 0.8rem; color: var(--lb-muted); display: -webkit-box; line-clamp: 2; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.adm__menu-meta { display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 0.5rem; }
.adm__menu-meta strong { color: var(--lb-brand-dark); }
.adm__menu-actions { display: flex; gap: 0.5rem; margin-top: 0.6rem; }
.adm__menu-actions button { flex: 1; padding: 0.45rem; border-radius: 10px; font-size: 0.78rem; font-weight: 700; cursor: pointer; }
.adm__menu-actions .adm__act-edit { background: var(--lb-soft); border: 1px solid var(--lb-line); color: var(--lb-ink); }
.adm__menu-actions .adm__act-del { background: #FFEBEE; border: 1px solid #FFCDD2; color: #C62828; }

/* PERIOD TABS */
.adm__period-tabs { display: flex; gap: 0.4rem; margin-bottom: 1.25rem; }
.adm__period-btn { padding: 0.5rem 1rem; border-radius: 999px; border: 1.5px solid var(--lb-line); background: #fff; font-size: 0.82rem; font-weight: 700; color: var(--lb-muted); cursor: pointer; }
.adm__period-btn.is-active { background: var(--lb-brand); border-color: var(--lb-brand); color: #fff; }

/* MODAL */
.adm__overlay { position: fixed; inset: 0; z-index: 50; display: grid; place-items: center; padding: 1rem; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); }
.adm__modal { width: 100%; max-width: 26rem; padding: 1.5rem; max-height: min(90vh, 48rem); max-height: min(90dvh, 48rem); overflow-y: auto; overscroll-behavior: contain; }
.adm__modal--scroll { max-height: 90vh; overflow-y: auto; }
.adm__modal h3 { margin: 0 0 1.25rem; font-size: 1.1rem; font-weight: 800; }
.adm__form { display: grid; gap: 0.85rem; }
.adm__form label { display: grid; gap: 0.3rem; font-size: 0.8rem; font-weight: 600; color: var(--lb-muted); }
.adm__form textarea { resize: vertical; }
.adm__check { display: flex !important; align-items: center; gap: 0.5rem; flex-direction: row !important; font-size: 0.85rem !important; cursor: pointer; }
.adm__preview { width: 5rem; height: 5rem; border-radius: 12px; object-fit: cover; margin-top: 0.35rem; border: 1px solid var(--lb-line); }
.adm__modal-foot { display: flex; gap: 0.6rem; padding-top: 0.5rem; }
.adm__modal-foot button { flex: 1; }

.adm :is(button, input, select, textarea):focus-visible { outline: 3px solid rgba(217,119,87,0.55); outline-offset: 3px; }
.adm__nav-link, .adm__quick-btn, .adm__act-edit, .adm__act-del, .adm__see-all, .adm__burger, .adm__side-logout, .adm__dropdown-btn, .adm__menu-actions button { transition: background-color 160ms ease, color 160ms ease, border-color 160ms ease, transform 160ms ease; }
.adm__act-edit, .adm__act-del, .adm__side-logout, .adm__dropdown-btn, .adm__drawer-close, .adm__see-all { min-height: 2.5rem; }
.adm__section-head { gap: 0.75rem; }
.adm__section-head > .lb-btn-primary { flex-shrink: 0; }
.adm__menu-card { min-width: 0; transition: transform 180ms ease, box-shadow 180ms ease; }
.adm__menu-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(51,42,31,0.09); }
.adm__menu-body h3, .adm__menu-desc { overflow-wrap: anywhere; }
.adm__form input[type="file"] { max-width: 100%; font: inherit; }

@media (max-width: 639px) {
  .adm__content { padding: 1rem; }
  .adm__topbar { padding-inline: 0.85rem; }
  .adm__welcome { padding: 1.25rem; }
  .adm__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.6rem; }
  .adm__stat { padding: 0.9rem; gap: 0.4rem; }
  .adm__stat-label { font-size: 0.64rem; }
  .adm__stat-num { font-size: 1.25rem; overflow-wrap: anywhere; }
  .adm__stat-icon { width: 2.25rem; height: 2.25rem; }
  .adm__donut-wrap { gap: 1rem; }
  .adm__donut { width: 6.5rem; height: 6.5rem; }
  .adm__section-head { align-items: flex-start; }
  .adm__section-head h2 { font-size: 1rem; }
  .adm__section-head > .lb-btn-primary { padding-inline: 0.75rem; white-space: nowrap; }
  .adm__chart-card, .adm__recent, .adm__quick { padding: 1rem; }
}

@media (max-width: 380px) {
  .adm__avatar-name { display: none; }
  .adm__stat-icon { display: none; }
  .adm__donut-wrap { align-items: flex-start; flex-direction: column; }
  .adm__quick-grid { grid-template-columns: 1fr; }
  .adm__section-head { flex-direction: column; }
}

@media (prefers-reduced-motion: reduce) {
  .adm *, .adm *::before, .adm *::after { scroll-behavior: auto !important; transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; }
}
</style>
