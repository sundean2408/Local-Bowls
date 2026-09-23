<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api, { getImageUrl } from '@/services/api'
import { useAuthStore } from '../../store/auth.js'

const router = useRouter()
const authStore = useAuthStore()

// ===== STATE =====
const activeTab = ref('dashboard')
const loading = ref(false)
const mobileSidebarOpen = ref(false)
const adminMenuOpen = ref(false)
const searchQuery = ref('')

const stats = ref({ todaySales: 0, completedOrders: 0, totalMenus: 0, occupiedTables: 0 })

const userList = ref([])
const showUserModal = ref(false)
const editingUser = ref(null)
const userForm = ref({ nama: '', username: '', password: '', role: 'waiter' })

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

const reportPeriod = ref('hari')
const reportStats = ref({ totalTransaksi: 0, totalPendapatan: 0, rataRata: 0 })
const recentTransactions = ref([])

const mejaList = ref([])

const navItems = [
  { id: 'dashboard', label: 'Dashboard', icon: 'grid' },
  { id: 'user', label: 'Kelola User', icon: 'users' },
  { id: 'menu', label: 'Kelola Menu', icon: 'bowl' },
  { id: 'kategori', label: 'Kategori', icon: 'tag' },
  { id: 'laporan', label: 'Laporan', icon: 'chart' },
  { id: 'meja', label: 'QR Meja', icon: 'qr' },
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

// ===== HELPERS =====
const getGambarUrl = (gambar) => {
  return getImageUrl(gambar, 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=600&q=80')
}

const formatHarga = (harga) => new Intl.NumberFormat('id-ID').format(harga || 0)

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const roleBadgeColor = (role) => {
  const colors = {
    admin: '#CC6B19',
    kitchen: '#3B82F6',
    kasir: '#6366F1',
    waiter: '#4CAF50',
  }
  return colors[role] || '#8B6F47'
}

const getMejaURL = (mejaId) => `${window.location.origin}/?meja=${mejaId}`

// Ringkasan pendapatan per metode pembayaran, dipakai buat "chart" batang
// sederhana di dashboard (dihitung dari transaksi terbaru yang sudah di-load).
const paymentBreakdown = computed(() => {
  const totals = {}
  recentTransactions.value.forEach((trx) => {
    const key = trx.metode_pembayaran || 'Lainnya'
    totals[key] = (totals[key] || 0) + Number(trx.total_bayar || 0)
  })
  const entries = Object.entries(totals)
  const max = Math.max(1, ...entries.map(([, v]) => v))
  return entries.map(([label, value]) => ({
    label,
    value,
    percent: Math.round((value / max) * 100),
  }))
})

// ===== DASHBOARD: grafik penjualan mingguan (SVG, tanpa library) =====
// Dikelompokkan dari recentTransactions (mengikuti data yang sudah termuat
// dari endpoint laporan). Kalau reportPeriod sedang 'hari', datanya cuma
// mencakup transaksi hari ini -- untuk grafik mingguan yang lebih akurat,
// idealnya backend punya endpoint khusus (misal /admin/laporan?period=minggu).
const HARI_URUTAN = [1, 2, 3, 4, 5, 6, 0] // Senin..Minggu (Date.getDay())
const HARI_LABEL = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']

const weeklyChartData = computed(() => {
  const totals = [0, 0, 0, 0, 0, 0, 0]
  recentTransactions.value.forEach((trx) => {
    const d = new Date(trx.tanggal_bayar)
    if (isNaN(d.getTime())) return
    const idx = HARI_URUTAN.indexOf(d.getDay())
    if (idx !== -1) totals[idx] += Number(trx.total_bayar || 0)
  })
  return HARI_LABEL.map((label, i) => ({ label, value: totals[i] }))
})

const CHART_W = 700
const CHART_H = 220
const CHART_PAD = 24

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

const linePath = computed(() =>
  chartPoints.value.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' ')
)

const areaPath = computed(() => {
  const pts = chartPoints.value
  if (pts.length === 0) return ''
  const baseline = CHART_H - CHART_PAD
  const first = pts[0]
  const last = pts[pts.length - 1]
  return `${linePath.value} L ${last.x.toFixed(1)} ${baseline} L ${first.x.toFixed(1)} ${baseline} Z`
})

// ===== DASHBOARD: donut kategori menu (CSS conic-gradient, tanpa library) =====
const CHART_COLORS = ['#E76F2A', '#F5B542', '#4CAF50', '#8B5CF6', '#3B82F6', '#EC4899', '#14B8A6']

const kategoriBreakdown = computed(() => {
  const total = menuList.value.length || 1
  return kategoriList.value
    .map((kat, i) => {
      const count = menuList.value.filter((m) => m.id_kategori === kat.id).length
      return {
        id: kat.id,
        label: kat.nama_kategori,
        count,
        percent: Math.round((count / total) * 100),
        color: CHART_COLORS[i % CHART_COLORS.length],
      }
    })
    .filter((k) => k.count > 0)
})

const donutBackground = computed(() => {
  if (kategoriBreakdown.value.length === 0) return '#E9E4DA'
  let cursor = 0
  const stops = kategoriBreakdown.value.map((k) => {
    const start = cursor
    cursor += k.percent
    return `${k.color} ${start}% ${cursor}%`
  })
  if (cursor < 100) {
    const last = kategoriBreakdown.value[kategoriBreakdown.value.length - 1]
    stops.push(`${last.color} ${cursor}% 100%`)
  }
  return `conic-gradient(${stops.join(', ')})`
})

function kategoriColor(idKategori) {
  const found = kategoriBreakdown.value.find((k) => k.id === idKategori)
  return found ? found.color : '#8B6F47'
}

const menuTerbaru = computed(() =>
  [...menuList.value].sort((a, b) => (b.id || 0) - (a.id || 0)).slice(0, 4)
)

function namaKategori(idKategori) {
  return kategoriList.value.find((k) => k.id === idKategori)?.nama_kategori || '-'
}

// ===== DATA FETCHING =====
const loadDashboard = async () => {
  try {
    const res = await api.get('/admin/dashboard')
    stats.value = res.data
  } catch (e) { console.error(e) }
}

const loadUsers = async () => {
  try {
    const res = await api.get('/users')
    userList.value = res.data
  } catch (e) { console.error(e) }
}

const loadMenus = async () => {
  try {
    const res = await api.get('/menu')
    menuList.value = res.data
  } catch (e) { console.error(e) }
}

const loadKategori = async () => {
  try {
    const res = await api.get('/kategori')
    kategoriList.value = res.data
  } catch (e) { console.error(e) }
}

const loadLaporan = async () => {
  try {
    const res = await api.get(`/admin/laporan?period=${reportPeriod.value}`)
    reportStats.value = res.data.stats || {}
    recentTransactions.value = res.data.transactions || []
  } catch (e) { console.error(e) }
}

const loadMeja = async () => {
  try {
    const res = await api.get('/meja')
    mejaList.value = res.data
  } catch (e) { console.error(e) }
}

const loadAll = async () => {
  loading.value = true
  await Promise.all([loadDashboard(), loadUsers(), loadMenus(), loadKategori(), loadLaporan(), loadMeja()])
  loading.value = false
}

// ===== USER CRUD =====
const openUserModal = (user = null) => {
  editingUser.value = user
  userForm.value = user
    ? { nama: user.nama, username: user.username, password: '', role: user.role }
    : { nama: '', username: '', password: '', role: 'waiter' }
  showUserModal.value = true
}

const saveUser = async () => {
  try {
    if (editingUser.value) {
      await api.put(`/users/${editingUser.value.id}`, userForm.value)
    } else {
      await api.post('/users', userForm.value)
    }
    showUserModal.value = false
    await loadUsers()
  } catch (e) { alert(e.response?.data?.message || 'Gagal menyimpan user') }
}

const deleteUser = async (id) => {
  if (!confirm('Yakin hapus user ini?')) return
  try {
    await api.delete(`/users/${id}`)
    await loadUsers()
  } catch (e) { alert(e.response?.data?.message || 'Gagal menghapus user') }
}

// ===== MENU CRUD =====
const openMenuModal = (menu = null) => {
  editingMenu.value = menu
  menuForm.value = menu
    ? { ...menu, status_tersedia: menu.status_tersedia !== false && menu.status_tersedia !== 0 && menu.status_tersedia !== '0' }
    : { nama_menu: '', id_kategori: kategoriList.value[0]?.id || '', deskripsi: '', harga: 0, status_tersedia: true }
  menuPreview.value = menu ? getGambarUrl(menu.gambar) : null
  menuFile.value = null
  showMenuModal.value = true
}

const handleFileUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    menuFile.value = file
    menuPreview.value = URL.createObjectURL(file)
  }
}

const saveMenu = async () => {
  try {
    const formData = new FormData()
    Object.keys(menuForm.value).forEach(key => {
      formData.append(key, menuForm.value[key])
    })
    if (menuFile.value) formData.append('gambar', menuFile.value)

    if (editingMenu.value) {
      formData.append('_method', 'PUT')
      await api.post(`/menu/${editingMenu.value.id}`, formData)
    } else {
      await api.post('/menu', formData)
    }
    showMenuModal.value = false
    await loadMenus()
  } catch (e) { alert(e.response?.data?.message || 'Gagal menyimpan menu') }
}

const deleteMenu = async (id) => {
  if (!confirm('Yakin hapus menu ini?')) return
  try {
    await api.delete(`/menu/${id}`)
    await loadMenus()
  } catch (e) { alert(e.response?.data?.message || 'Gagal menghapus menu') }
}

// ===== KATEGORI CRUD =====
const openKategoriModal = (kat = null) => {
  editingKategori.value = kat
  kategoriForm.value = kat ? { nama_kategori: kat.nama_kategori } : { nama_kategori: '' }
  showKategoriModal.value = true
}

const saveKategori = async () => {
  try {
    if (editingKategori.value) {
      await api.put(`/kategori/${editingKategori.value.id}`, kategoriForm.value)
    } else {
      await api.post('/kategori', kategoriForm.value)
    }
    showKategoriModal.value = false
    await loadKategori()
  } catch (e) { alert(e.response?.data?.message || 'Gagal menyimpan kategori') }
}

const deleteKategori = async (id) => {
  if (!confirm('Yakin hapus kategori ini?')) return
  try {
    await api.delete(`/kategori/${id}`)
    await loadKategori()
  } catch (e) { alert(e.response?.data?.message || 'Gagal menghapus kategori') }
}

// ===== QR PRINT =====
const printAllQR = () => {
  const printWindow = window.open('', '_blank')
  let html = '<html><head><title>QR Code Meja - Local Bowls</title>'
  html += '<style>body{font-family:Arial,sans-serif;text-align:center;padding:20px;} .qr-item{display:inline-block;margin:20px;page-break-inside:avoid;} img{width:200px;height:200px;} h3{margin-top:10px;}</style>'
  html += '</head><body><h1>Local Bowls - QR Code Meja</h1>'
  mejaList.value.forEach(meja => {
    const url = getMejaURL(meja.id)
    html += `<div class="qr-item"><img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}" /><h3>Meja ${meja.nomor_meja}</h3></div>`
  })
  html += '</body></html>'
  printWindow.document.write(html)
  printWindow.document.close()
  printWindow.print()
}

// ===== LOGOUT =====
const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

// ===== WATCHERS =====
watch(reportPeriod, () => loadLaporan())
watch(activeTab, (newTab) => {
  if (newTab === 'laporan') loadLaporan()
})

// ===== INIT =====
onMounted(() => {
  loadAll()
})
</script>

<template>
  <div class="min-h-screen flex bg-cream-50" @click="adminMenuOpen = false">
    <!-- ===== SIDEBAR (desktop) ===== -->
    <aside class="hidden md:flex md:flex-col w-64 shrink-0 bg-terracotta-900 text-cream-50 relative overflow-hidden">
      <div class="h-16 flex items-center gap-2 px-6 border-b border-white/10 relative z-10">
        <span class="text-2xl">🍜</span>
        <span class="font-bold text-lg text-terracotta-300">LocalBowls</span>
      </div>
      <nav class="flex-1 px-3 py-4 space-y-1 relative z-10">
        <button
          v-for="item in navItems"
          :key="item.id"
          @click="selectTab(item.id)"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition"
          :class="activeTab === item.id ? 'bg-terracotta-600 text-white shadow-lg' : 'text-cream-100/70 hover:bg-white/5 hover:text-white'"
        >
          <!-- Dashboard -->
          <svg v-if="item.icon === 'grid'" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.75" y="3.75" width="7" height="7" rx="1.5"/><rect x="13.25" y="3.75" width="7" height="7" rx="1.5"/><rect x="3.75" y="13.25" width="7" height="7" rx="1.5"/><rect x="13.25" y="13.25" width="7" height="7" rx="1.5"/></svg>
          <!-- Users -->
          <svg v-else-if="item.icon === 'users'" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="7.5" r="3.25"/><path d="M3.5 20a5.5 5.5 0 0111 0"/><circle cx="17" cy="8.5" r="2.5"/><path d="M15 20a4.5 4.5 0 015.5-4.4"/></svg>
          <!-- Bowl / Menu -->
          <svg v-else-if="item.icon === 'bowl'" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3.5 11h17a8.5 6 0 01-17 0z"/><path d="M6 11a6 6 0 0112 0"/><path d="M9 3.5c-.7.7-.7 1.3 0 2M12 3c-.7.7-.7 1.3 0 2"/></svg>
          <!-- Tag / Kategori -->
          <svg v-else-if="item.icon === 'tag'" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10.6 3H5.75A1.75 1.75 0 004 4.75v4.85c0 .46.18.9.51 1.24l8.4 8.4a1.75 1.75 0 002.47 0l4.85-4.85a1.75 1.75 0 000-2.47l-8.4-8.4A1.75 1.75 0 0010.6 3z"/><circle cx="7.75" cy="7.75" r="1"/></svg>
          <!-- Chart / Laporan -->
          <svg v-else-if="item.icon === 'chart'" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="12.5" width="4" height="8" rx="0.8"/><rect x="10" y="8" width="4" height="12.5" rx="0.8"/><rect x="16.5" y="4" width="4" height="16.5" rx="0.8"/></svg>
          <!-- QR -->
          <svg v-else-if="item.icon === 'qr'" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="3.5" width="6" height="6" rx="1"/><rect x="14.5" y="3.5" width="6" height="6" rx="1"/><rect x="3.5" y="14.5" width="6" height="6" rx="1"/><rect x="14.75" y="14.75" width="2.2" height="2.2"/><rect x="18.3" y="14.75" width="1.7" height="1.7"/><rect x="14.75" y="18.3" width="1.7" height="1.7"/><rect x="17.5" y="17.5" width="2.5" height="2.5"/></svg>
          {{ item.label }}
        </button>
      </nav>

      <!-- Ilustrasi dekoratif bawah sidebar -->
      <div class="relative z-10 px-6 pb-6 pt-2">
        <svg viewBox="0 0 100 80" class="w-24 h-20 mx-auto opacity-70 text-terracotta-400" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M15 40h70a35 20 0 01-70 0z"/>
          <path d="M22 40a28 22 0 0156 0"/>
          <path d="M42 18c-3 3-3 6 0 9M52 15c-3 3-3 6 0 9M62 18c-3 3-3 6 0 9"/>
        </svg>
        <p class="sidebar-tagline text-center text-terracotta-300 mt-1">Rasa Lokal untuk Semua</p>
      </div>
    </aside>

    <!-- ===== SIDEBAR (mobile drawer) ===== -->
    <div v-if="mobileSidebarOpen" class="md:hidden fixed inset-0 z-50 flex">
      <div class="absolute inset-0 bg-black/40" @click="mobileSidebarOpen = false"></div>
      <aside class="relative w-64 bg-terracotta-900 text-cream-50 flex flex-col">
        <div class="h-16 flex items-center justify-between px-6 border-b border-white/10">
          <span class="font-bold text-terracotta-300">🍜 LocalBowls</span>
          <button @click="mobileSidebarOpen = false" class="text-cream-100/80">✕</button>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1">
          <button
            v-for="item in navItems"
            :key="item.id"
            @click="selectTab(item.id)"
            class="w-full text-left px-3 py-2.5 rounded-lg text-sm font-medium transition"
            :class="activeTab === item.id ? 'bg-terracotta-700 text-white' : 'text-cream-100/70 hover:bg-white/5 hover:text-white'"
          >{{ item.label }}</button>
        </nav>
        <div class="p-4 border-t border-white/10">
          <p class="text-sm font-semibold text-white truncate">{{ authStore.user?.nama || 'Admin' }}</p>
          <button
            @click="handleLogout"
            class="w-full text-left px-3 py-2 mt-2 rounded-lg text-sm font-medium text-cream-100/80 hover:bg-white/10 hover:text-white transition"
          >Keluar</button>
        </div>
      </aside>
    </div>

    <!-- ===== MAIN COLUMN ===== -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Topbar -->
      <header class="h-16 shrink-0 flex items-center gap-3 px-4 sm:px-6 bg-white border-b border-terracotta-100">
        <button
          @click.stop="mobileSidebarOpen = true"
          class="md:hidden w-9 h-9 flex items-center justify-center rounded-lg border border-terracotta-100 shrink-0"
          aria-label="Menu"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
        </button>

        <!-- Search bar -->
        <div class="flex-1 max-w-md hidden sm:flex items-center gap-2 bg-cream-50 border border-terracotta-100 rounded-full px-4 py-2">
          <svg class="w-4 h-4 text-earth-dark/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-4.34-4.34M19 11a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari menu, kategori, atau data..."
            class="bg-transparent text-sm outline-none flex-1 text-earth-dark placeholder:text-earth-dark/40"
          />
        </div>

        <div class="flex-1 sm:hidden">
          <p class="text-base font-bold text-terracotta-900">{{ pageTitle }}</p>
        </div>

        <div class="ml-auto flex items-center gap-3 shrink-0">
          <!-- Notifikasi -->
          <button class="relative w-10 h-10 flex items-center justify-center rounded-full hover:bg-cream-50 transition" aria-label="Notifikasi">
            <svg class="w-5 h-5 text-earth-dark/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M14.86 17.08a24 24 0 005.45-1.31A9 9 0 0118 9.75V9a6 6 0 00-12 0v.75a9 9 0 01-2.31 6.02c1.73.64 3.56 1.09 5.45 1.31m5.71 0a24.3 24.3 0 01-5.71 0m5.71 0a2.86 2.86 0 01-5.71 0"/></svg>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500"></span>
          </button>

          <!-- Avatar & dropdown -->
          <div class="relative">
            <button @click.stop="adminMenuOpen = !adminMenuOpen" class="flex items-center gap-2">
              <span class="w-9 h-9 rounded-full bg-terracotta-900 text-white text-sm font-bold flex items-center justify-center shrink-0">{{ adminInitials }}</span>
              <span class="hidden sm:inline text-sm font-medium text-earth-dark">Admin</span>
              <svg class="hidden sm:block w-4 h-4 text-earth-dark/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            </button>
            <div
              v-if="adminMenuOpen"
              @click.stop
              class="absolute right-0 top-12 w-48 bg-white rounded-xl shadow-xl border border-terracotta-100 py-2 z-50"
            >
              <p class="px-4 py-1.5 text-sm font-semibold text-earth-dark truncate">{{ authStore.user?.nama || 'Admin' }}</p>
              <p class="px-4 pb-2 text-xs text-earth-dark/50">Administrator</p>
              <hr class="border-cream-100" />
              <button @click="handleLogout" class="w-full text-left px-4 py-2 text-sm text-earth-dark hover:bg-cream-50">Keluar</button>
            </div>
          </div>
        </div>
      </header>

      <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-6">

        <!-- ===== TAB: DASHBOARD ===== -->
        <div v-if="activeTab === 'dashboard'">
          <!-- Header sambutan -->
          <div class="relative rounded-3xl bg-gradient-to-r from-[#FBEAD9] to-[#FDF6EC] p-6 sm:p-8 mb-6 overflow-hidden">
            <p class="text-sm text-earth-dark/70 mb-1">Selamat Datang,</p>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-terracotta-900 mb-1">Admin {{ authStore.user?.nama || 'LocalBowls' }}</h1>
            <p class="text-sm text-earth-dark/60">Kelola data restoran dengan mudah dan cepat.</p>
            <svg viewBox="0 0 200 140" class="hidden sm:block absolute right-4 top-1/2 -translate-y-1/2 w-40 h-28 text-terracotta-300 opacity-80" fill="none" stroke="currentColor" stroke-width="3">
              <path d="M30 80h140a70 40 0 01-140 0z"/>
              <path d="M44 80a56 44 0 01112 0"/>
              <path d="M84 36c-6 6-6 12 0 18M104 30c-6 6-6 12 0 18M124 36c-6 6-6 12 0 18"/>
            </svg>
          </div>

          <!-- Kartu statistik -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="rounded-2xl p-5 bg-white border border-terracotta-100 flex items-center justify-between">
              <div>
                <p class="text-xs text-earth-dark/60 mb-1">Total User</p>
                <p class="text-2xl font-bold text-earth-dark">{{ userList.length }}</p>
              </div>
              <span class="w-11 h-11 rounded-full flex items-center justify-center text-white shrink-0" style="background-color:#E9863C;">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.25"/><path d="M5 20a7 7 0 0114 0"/></svg>
              </span>
            </div>
            <div class="rounded-2xl p-5 bg-white border border-terracotta-100 flex items-center justify-between">
              <div>
                <p class="text-xs text-earth-dark/60 mb-1">Total Menu</p>
                <p class="text-2xl font-bold text-earth-dark">{{ menuList.length }}</p>
              </div>
              <span class="w-11 h-11 rounded-full flex items-center justify-center text-white shrink-0" style="background-color:#4CAF50;">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 12h16a8 5.5 0 01-16 0z"/><path d="M6.5 12a5.5 5.5 0 0111 0"/></svg>
              </span>
            </div>
            <div class="rounded-2xl p-5 bg-white border border-terracotta-100 flex items-center justify-between">
              <div>
                <p class="text-xs text-earth-dark/60 mb-1">Kategori</p>
                <p class="text-2xl font-bold text-earth-dark">{{ kategoriList.length }}</p>
              </div>
              <span class="w-11 h-11 rounded-full flex items-center justify-center text-white shrink-0" style="background-color:#8B5CF6;">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.6 3H5.75A1.75 1.75 0 004 4.75v4.85c0 .46.18.9.51 1.24l8.4 8.4a1.75 1.75 0 002.47 0l4.85-4.85a1.75 1.75 0 000-2.47l-8.4-8.4A1.75 1.75 0 0010.6 3z"/><circle cx="7.75" cy="7.75" r="1"/></svg>
              </span>
            </div>
            <div class="rounded-2xl p-5 bg-white border border-terracotta-100 flex items-center justify-between">
              <div>
                <p class="text-xs text-earth-dark/60 mb-1">Total Meja</p>
                <p class="text-2xl font-bold text-earth-dark">{{ mejaList.length }}</p>
              </div>
              <span class="w-11 h-11 rounded-full flex items-center justify-center text-white shrink-0" style="background-color:#3B82F6;">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="3.5" width="6" height="6" rx="1"/><rect x="14.5" y="3.5" width="6" height="6" rx="1"/><rect x="3.5" y="14.5" width="6" height="6" rx="1"/><rect x="15" y="15" width="5" height="5" rx="1"/></svg>
              </span>
            </div>
          </div>

          <!-- Grafik: Penjualan Mingguan & Kategori Menu -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="rounded-2xl p-5 bg-white border border-terracotta-100">
              <div class="flex items-center justify-between mb-4">
                <p class="font-bold text-terracotta-900">Penjualan Mingguan</p>
                <span class="text-xs text-earth-dark/50">Berdasarkan transaksi termuat</span>
              </div>
              <svg :viewBox="`0 0 ${CHART_W} ${CHART_H}`" class="w-full h-48">
                <defs>
                  <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#E9863C" stop-opacity="0.35" />
                    <stop offset="100%" stop-color="#E9863C" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <path :d="areaPath" fill="url(#areaFill)" />
                <path :d="linePath" fill="none" stroke="#E9863C" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                <circle v-for="(p, i) in chartPoints" :key="i" :cx="p.x" :cy="p.y" r="4" fill="#E9863C" stroke="#fff" stroke-width="2" />
              </svg>
              <div class="flex justify-between text-xs text-earth-dark/50 px-1">
                <span v-for="d in weeklyChartData" :key="d.label">{{ d.label }}</span>
              </div>
            </div>

            <div class="rounded-2xl p-5 bg-white border border-terracotta-100">
              <p class="font-bold text-terracotta-900 mb-4">Kategori Menu</p>
              <div v-if="kategoriBreakdown.length === 0" class="text-sm text-earth-dark/60">Belum ada data kategori.</div>
              <div v-else class="flex items-center gap-6">
                <div class="relative w-32 h-32 rounded-full shrink-0" :style="{ background: donutBackground }">
                  <div class="absolute inset-3 bg-white rounded-full flex flex-col items-center justify-center">
                    <span class="text-xl font-extrabold text-earth-dark">{{ menuList.length }}</span>
                    <span class="text-[11px] text-earth-dark/50">Menu</span>
                  </div>
                </div>
                <div class="flex-1 space-y-2 min-w-0">
                  <div v-for="k in kategoriBreakdown" :key="k.id" class="flex items-center justify-between text-sm gap-2">
                    <span class="flex items-center gap-2 min-w-0">
                      <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: k.color }"></span>
                      <span class="truncate text-earth-dark">{{ k.label }}</span>
                    </span>
                    <span class="text-earth-dark/60 shrink-0">{{ k.percent }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Menu Terbaru & Menu Cepat -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-2xl p-5 bg-white border border-terracotta-100">
              <div class="flex items-center justify-between mb-4">
                <p class="font-bold text-terracotta-900">Menu Terbaru</p>
                <button @click="selectTab('menu')" class="text-sm font-semibold text-terracotta-600 flex items-center gap-1">
                  Lihat Semua
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4.5 12h15M13.5 6l6 6-6 6"/></svg>
                </button>
              </div>
              <div v-if="menuTerbaru.length === 0" class="text-sm text-earth-dark/60">Belum ada menu.</div>
              <table v-else class="w-full text-sm">
                <tbody>
                  <tr v-for="menu in menuTerbaru" :key="menu.id" class="border-b border-cream-100 last:border-0">
                    <td class="py-2.5 pr-3 w-12">
                      <img :src="getGambarUrl(menu.gambar)" :alt="menu.nama_menu" class="w-10 h-10 rounded-lg object-cover" />
                    </td>
                    <td class="py-2.5 pr-3">
                      <p class="font-medium text-earth-dark leading-tight">{{ menu.nama_menu }}</p>
                      <span
                        class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full mt-1 text-white"
                        :style="{ backgroundColor: kategoriColor(menu.id_kategori) }"
                      >{{ namaKategori(menu.id_kategori) }}</span>
                    </td>
                    <td class="py-2.5 pr-3 text-right font-semibold text-earth-dark whitespace-nowrap">Rp {{ formatHarga(menu.harga) }}</td>
                    <td class="py-2.5 text-right">
                      <span class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white" :style="{ backgroundColor: menu.status_tersedia ? '#4CAF50' : '#c02a2a' }">
                        {{ menu.status_tersedia ? 'Tersedia' : 'Habis' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="rounded-2xl p-5 bg-white border border-terracotta-100">
              <p class="font-bold text-terracotta-900 mb-4">Menu Cepat</p>
              <div class="grid grid-cols-2 gap-3">
                <button @click="selectTab('user')" class="flex items-center justify-between rounded-xl p-3.5 text-left transition hover:opacity-90" style="background-color:#FBEAD9;">
                  <span class="text-sm font-semibold text-earth-dark">Kelola User</span>
                  <svg class="w-4 h-4 text-earth-dark/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4.5 12h15M13.5 6l6 6-6 6"/></svg>
                </button>
                <button @click="selectTab('menu')" class="flex items-center justify-between rounded-xl p-3.5 text-left transition hover:opacity-90" style="background-color:#E4F3E4;">
                  <span class="text-sm font-semibold text-earth-dark">Kelola Menu</span>
                  <svg class="w-4 h-4 text-earth-dark/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4.5 12h15M13.5 6l6 6-6 6"/></svg>
                </button>
                <button @click="selectTab('kategori')" class="flex items-center justify-between rounded-xl p-3.5 text-left transition hover:opacity-90" style="background-color:#EDE6FB;">
                  <span class="text-sm font-semibold text-earth-dark">Kategori</span>
                  <svg class="w-4 h-4 text-earth-dark/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4.5 12h15M13.5 6l6 6-6 6"/></svg>
                </button>
                <button @click="selectTab('laporan')" class="flex items-center justify-between rounded-xl p-3.5 text-left transition hover:opacity-90" style="background-color:#DCEBFB;">
                  <span class="text-sm font-semibold text-earth-dark">Laporan</span>
                  <svg class="w-4 h-4 text-earth-dark/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4.5 12h15M13.5 6l6 6-6 6"/></svg>
                </button>
                <button @click="selectTab('meja')" class="col-span-2 flex items-center justify-between rounded-xl p-3.5 text-left transition hover:opacity-90" style="background-color:#FBEAD9;">
                  <span class="text-sm font-semibold text-earth-dark">QR Meja</span>
                  <svg class="w-4 h-4 text-earth-dark/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4.5 12h15M13.5 6l6 6-6 6"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: KELOLA USER ===== -->
        <div v-if="activeTab === 'user'">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-terracotta-900">Kelola User</h2>
            <button @click="openUserModal()" class="text-sm font-bold px-4 py-2 rounded-full text-white bg-terracotta-600 hover:bg-terracotta-700 transition">
              + Tambah User
            </button>
          </div>

          <div class="rounded-2xl overflow-hidden border border-terracotta-100 bg-white">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-cream-50 border-b border-terracotta-100">
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">ID</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Nama</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Username</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Role</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in userList" :key="user.id" class="border-b border-cream-100 last:border-0">
                  <td class="px-4 py-3 text-earth-dark/70">{{ user.id }}</td>
                  <td class="px-4 py-3 font-medium text-earth-dark">{{ user.nama }}</td>
                  <td class="px-4 py-3 text-earth-dark/70">{{ user.username }}</td>
                  <td class="px-4 py-3">
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" :style="{ backgroundColor: roleBadgeColor(user.role) }">{{ user.role }}</span>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex gap-3">
                      <button @click="openUserModal(user)" class="text-sm font-medium text-terracotta-700">Edit</button>
                      <button @click="deleteUser(user.id)" class="text-sm font-medium" style="color:#c02a2a;">Hapus</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===== TAB: KELOLA MENU ===== -->
        <div v-if="activeTab === 'menu'">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-terracotta-900">Kelola Menu</h2>
            <button @click="openMenuModal()" class="text-sm font-bold px-4 py-2 rounded-full text-white bg-terracotta-600 hover:bg-terracotta-700 transition">
              + Tambah Menu
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="menu in menuList" :key="menu.id" class="rounded-2xl p-4 border border-terracotta-100 bg-white">
              <img
                :src="getGambarUrl(menu.gambar)"
                :alt="menu.nama_menu"
                class="w-full h-36 rounded-xl object-cover mb-3"
                @error="$event.target.src = 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=600&q=80'"
              />
              <h3 class="font-bold text-earth-dark">{{ menu.nama_menu }}</h3>
              <p class="text-sm text-earth-dark/70 line-clamp-2">{{ menu.deskripsi }}</p>
              <div class="flex items-center justify-between mt-3">
                <span class="font-bold text-terracotta-700">Rp {{ formatHarga(menu.harga) }}</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" :style="{ backgroundColor: menu.status_tersedia ? '#4CAF50' : '#c02a2a' }">
                  {{ menu.status_tersedia ? 'Tersedia' : 'Habis' }}
                </span>
              </div>
              <div class="flex gap-2 mt-3">
                <button @click="openMenuModal(menu)" class="flex-1 text-xs font-bold py-2 rounded-full border-2 border-terracotta-100 bg-terracotta-50 text-earth-dark">Edit</button>
                <button @click="deleteMenu(menu.id)" class="flex-1 text-xs font-bold py-2 rounded-full text-white" style="background-color:#c02a2a;">Hapus</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: KATEGORI ===== -->
        <div v-if="activeTab === 'kategori'">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-terracotta-900">Kelola Kategori</h2>
            <button @click="openKategoriModal()" class="text-sm font-bold px-4 py-2 rounded-full text-white bg-terracotta-600 hover:bg-terracotta-700 transition">
              + Tambah Kategori
            </button>
          </div>

          <div class="rounded-2xl overflow-hidden border border-terracotta-100 bg-white">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-cream-50 border-b border-terracotta-100">
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">ID</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Nama Kategori</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Jumlah Menu</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="kat in kategoriList" :key="kat.id" class="border-b border-cream-100 last:border-0">
                  <td class="px-4 py-3 text-earth-dark/70">{{ kat.id }}</td>
                  <td class="px-4 py-3 font-medium text-earth-dark">{{ kat.nama_kategori }}</td>
                  <td class="px-4 py-3 text-earth-dark/70">{{ kat.jumlah_menu || 0 }}</td>
                  <td class="px-4 py-3">
                    <div class="flex gap-3">
                      <button @click="openKategoriModal(kat)" class="text-sm font-medium text-terracotta-700">Edit</button>
                      <button @click="deleteKategori(kat.id)" class="text-sm font-medium" style="color:#c02a2a;">Hapus</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===== TAB: LAPORAN ===== -->
        <div v-if="activeTab === 'laporan'">
          <h2 class="text-xl font-bold text-terracotta-900 mb-6">Laporan Penjualan</h2>

          <div class="flex gap-2 mb-6">
            <button
              v-for="period in ['hari', 'minggu', 'bulan']"
              :key="period"
              @click="reportPeriod = period"
              class="px-4 py-2 rounded-full text-sm font-medium transition"
              :class="reportPeriod === period ? 'bg-terracotta-600 text-white' : 'bg-white text-earth-dark border border-terracotta-100'"
            >
              {{ period === 'hari' ? 'Hari Ini' : period === 'minggu' ? 'Minggu Ini' : 'Bulan Ini' }}
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="rounded-2xl p-5 border border-terracotta-100 bg-white">
              <p class="text-sm mb-1 text-earth-dark/70">Total Transaksi</p>
              <p class="text-2xl font-bold text-earth-dark">{{ reportStats.totalTransaksi }}</p>
            </div>
            <div class="rounded-2xl p-5 border border-terracotta-100 bg-white">
              <p class="text-sm mb-1 text-earth-dark/70">Total Pendapatan</p>
              <p class="text-2xl font-bold text-terracotta-700">Rp {{ formatHarga(reportStats.totalPendapatan) }}</p>
            </div>
            <div class="rounded-2xl p-5 border border-terracotta-100 bg-white">
              <p class="text-sm mb-1 text-earth-dark/70">Rata-rata per Transaksi</p>
              <p class="text-2xl font-bold text-earth-dark">Rp {{ formatHarga(reportStats.rataRata) }}</p>
            </div>
          </div>

          <h3 class="text-lg font-bold text-terracotta-900 mb-4">Transaksi Terbaru</h3>
          <div class="rounded-2xl overflow-hidden border border-terracotta-100 bg-white">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-cream-50 border-b border-terracotta-100">
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">No. Struk</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Tanggal</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Meja</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Total</th>
                  <th class="text-left px-4 py-3 font-bold text-earth-dark">Metode</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="trx in recentTransactions" :key="trx.id" class="border-b border-cream-100 last:border-0">
                  <td class="px-4 py-3 text-earth-dark/70">{{ trx.nomor_struk_digital }}</td>
                  <td class="px-4 py-3 text-earth-dark/70">{{ formatDate(trx.tanggal_bayar) }}</td>
                  <td class="px-4 py-3 text-earth-dark/70">Meja {{ trx.nomor_meja }}</td>
                  <td class="px-4 py-3 font-bold text-terracotta-700">Rp {{ formatHarga(trx.total_bayar) }}</td>
                  <td class="px-4 py-3">
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" style="background-color:#4CAF50;">{{ trx.metode_pembayaran }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===== TAB: QR MEJA ===== -->
        <div v-if="activeTab === 'meja'">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-terracotta-900">QR Code Meja</h2>
            <button @click="printAllQR" class="text-sm font-bold px-4 py-2 rounded-full border-2 border-terracotta-100 bg-terracotta-50 text-earth-dark transition">
              Cetak Semua
            </button>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div v-for="meja in mejaList" :key="meja.id" class="rounded-2xl p-4 text-center border border-terracotta-100 bg-white">
              <img
                :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${getMejaURL(meja.id)}`"
                :alt="`QR Meja ${meja.nomor_meja}`"
                class="w-32 h-32 mx-auto mb-3 rounded-lg p-2 bg-white border border-terracotta-100"
              />
              <h3 class="font-bold mb-1 text-earth-dark">Meja {{ meja.nomor_meja }}</h3>
              <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" :style="{ backgroundColor: meja.status_meja === 'kosong' ? '#4CAF50' : '#c02a2a' }">
                {{ meja.status_meja }}
              </span>
              <div class="flex gap-2 mt-3">
                <a
                  :href="`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${getMejaURL(meja.id)}`"
                  download
                  class="flex-1 text-xs font-bold py-2 rounded-full border-2 border-terracotta-100 bg-terracotta-50 text-earth-dark block"
                >Download</a>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ===== MODAL: USER ===== -->
    <div v-if="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showUserModal = false">
      <div class="rounded-2xl p-6 w-full max-w-md bg-white">
        <h3 class="text-xl font-bold mb-4 text-terracotta-900">{{ editingUser ? 'Edit User' : 'Tambah User' }}</h3>
        <form @submit.prevent="saveUser" class="space-y-4">
          <div>
            <label class="block text-sm mb-1 text-earth-dark/70">Nama</label>
            <input v-model="userForm.nama" class="w-full rounded-xl px-4 py-2.5 text-sm border border-terracotta-100 focus:outline-none focus:border-terracotta-400" required />
          </div>
          <div>
            <label class="block text-sm mb-1 text-earth-dark/70">Username</label>
            <input v-model="userForm.username" class="w-full rounded-xl px-4 py-2.5 text-sm border border-terracotta-100 focus:outline-none focus:border-terracotta-400" required />
          </div>
          <div>
            <label class="block text-sm mb-1 text-earth-dark/70">Password {{ editingUser ? '(kosongkan jika tidak diubah)' : '' }}</label>
            <input v-model="userForm.password" type="password" class="w-full rounded-xl px-4 py-2.5 text-sm border border-terracotta-100 focus:outline-none focus:border-terracotta-400" :required="!editingUser" />
          </div>
          <div>
            <label class="block text-sm mb-1 text-earth-dark/70">Role</label>
            <select v-model="userForm.role" class="w-full rounded-xl px-4 py-2.5 text-sm border border-terracotta-100 focus:outline-none focus:border-terracotta-400" required>
              <option value="admin">Admin</option>
              <option value="kitchen">Dapur</option>
              <option value="kasir">Kasir</option>
              <option value="waiter">Waiter</option>
            </select>
          </div>
          <div class="flex gap-3 pt-2">
            <button type="button" @click="showUserModal = false" class="flex-1 text-sm font-bold py-2.5 rounded-full border-2 border-terracotta-100 bg-terracotta-50 text-earth-dark">Batal</button>
            <button type="submit" class="flex-1 text-sm font-bold py-2.5 rounded-full text-white bg-terracotta-600">Simpan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===== MODAL: MENU ===== -->
    <div v-if="showMenuModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showMenuModal = false">
      <div class="rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto bg-white">
        <h3 class="text-xl font-bold mb-4 text-terracotta-900">{{ editingMenu ? 'Edit Menu' : 'Tambah Menu' }}</h3>
        <form @submit.prevent="saveMenu" class="space-y-4">
          <div>
            <label class="block text-sm mb-1 text-earth-dark/70">Nama Menu</label>
            <input v-model="menuForm.nama_menu" class="w-full rounded-xl px-4 py-2.5 text-sm border border-terracotta-100 focus:outline-none focus:border-terracotta-400" required />
          </div>
          <div>
            <label class="block text-sm mb-1 text-earth-dark/70">Kategori</label>
            <select v-model="menuForm.id_kategori" class="w-full rounded-xl px-4 py-2.5 text-sm border border-terracotta-100 focus:outline-none focus:border-terracotta-400" required>
              <option v-for="kat in kategoriList" :key="kat.id" :value="kat.id">{{ kat.nama_kategori }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm mb-1 text-earth-dark/70">Deskripsi</label>
            <textarea v-model="menuForm.deskripsi" class="w-full rounded-xl px-4 py-2.5 text-sm border border-terracotta-100 focus:outline-none focus:border-terracotta-400" rows="3"></textarea>
          </div>
          <div>
            <label class="block text-sm mb-1 text-earth-dark/70">Harga</label>
            <input v-model.number="menuForm.harga" type="number" class="w-full rounded-xl px-4 py-2.5 text-sm border border-terracotta-100 focus:outline-none focus:border-terracotta-400" required min="0" />
          </div>
          <div>
            <label class="block text-sm mb-1 text-earth-dark/70">Foto Menu</label>
            <input type="file" @change="handleFileUpload" accept="image/*" class="w-full text-sm text-earth-dark" />
            <img v-if="menuPreview" :src="menuPreview" class="w-24 h-24 rounded-xl object-cover mt-2 border border-terracotta-100" />
          </div>
          <div>
            <label class="flex items-center gap-2 text-sm cursor-pointer text-earth-dark/70">
              <input v-model="menuForm.status_tersedia" type="checkbox" class="w-4 h-4 rounded accent-terracotta-600" />
              Tersedia
            </label>
          </div>
          <div class="flex gap-3 pt-2">
            <button type="button" @click="showMenuModal = false" class="flex-1 text-sm font-bold py-2.5 rounded-full border-2 border-terracotta-100 bg-terracotta-50 text-earth-dark">Batal</button>
            <button type="submit" class="flex-1 text-sm font-bold py-2.5 rounded-full text-white bg-terracotta-600">Simpan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===== MODAL: KATEGORI ===== -->
    <div v-if="showKategoriModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showKategoriModal = false">
      <div class="rounded-2xl p-6 w-full max-w-md bg-white">
        <h3 class="text-xl font-bold mb-4 text-terracotta-900">{{ editingKategori ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
        <form @submit.prevent="saveKategori" class="space-y-4">
          <div>
            <label class="block text-sm mb-1 text-earth-dark/70">Nama Kategori</label>
            <input v-model="kategoriForm.nama_kategori" class="w-full rounded-xl px-4 py-2.5 text-sm border border-terracotta-100 focus:outline-none focus:border-terracotta-400" required />
          </div>
          <div class="flex gap-3 pt-2">
            <button type="button" @click="showKategoriModal = false" class="flex-1 text-sm font-bold py-2.5 rounded-full border-2 border-terracotta-100 bg-terracotta-50 text-earth-dark">Batal</button>
            <button type="submit" class="flex-1 text-sm font-bold py-2.5 rounded-full text-white bg-terracotta-600">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Caveat:wght@600&display=swap');
.sidebar-tagline {
  font-family: 'Caveat', cursive;
  font-size: 1.15rem;
  line-height: 1;
}
</style>