// API Configuration
// Auto-detect dari alamat yang dipakai browser saat ini (window.location.hostname),
// jadi kalau IP laptop berubah (pindah WiFi, restart hotspot, dll), URL API ikut
// menyesuaikan otomatis tanpa perlu edit file ini setiap kali. Backend Laravel
// diasumsikan selalu di port 8000, di mesin yang sama dengan frontend.
// VITE_API_BASE_URL di .env tetap didahulukan kalau di-set secara eksplisit.
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'https://localhost:8000/api'

class ApiService {
  constructor(baseURL = API_BASE_URL) {
    this.baseURL = baseURL
  }

  // Helper method untuk add authorization header
  getHeaders() {
    const token = localStorage.getItem('authToken')
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json', // penting: kalau tidak ada, Laravel bisa redirect (bukan 401 JSON) saat auth gagal
      ...(token && { 'Authorization': `Bearer ${token}` })
    }
  }

  // Helper method untuk image URL. Foto menu ternyata disimpan langsung di
  // backend/public/images/menu/... (BUKAN di storage/app/public), jadi Laravel
  // serve otomatis tanpa perlu prefix "/storage/" -- cukup base URL + path-nya.
  getImageUrl(imagePath, fallback = '/placeholder.png') {
    if (!imagePath) return fallback
    if (imagePath.startsWith('http')) return imagePath
    // Backend (Windows) menyimpan path pakai backslash, misal "images\menu\Mie-Aceh.jpg".
    // Backslash tidak valid di URL, jadi harus dikonversi ke forward slash dulu,
    // baru dibuang slash di depan (kalau ada).
    const cleanPath = imagePath.replace(/\\/g, '/').replace(/^\//, '')
    return `${this.baseURL.replace('/api', '')}/${cleanPath}`
  }

  // GET request
  async get(endpoint) {
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        method: 'GET',
        headers: this.getHeaders()
      })
      return await this.handleResponse(response)
    } catch (error) {
      throw this.handleError(error)
    }
  }

  // POST request
  async post(endpoint, data) {
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify(data)
      })
      return await this.handleResponse(response)
    } catch (error) {
      throw this.handleError(error)
    }
  }

  // PUT request
  async put(endpoint, data) {
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        method: 'PUT',
        headers: this.getHeaders(),
        body: JSON.stringify(data)
      })
      return await this.handleResponse(response)
    } catch (error) {
      throw this.handleError(error)
    }
  }

  // PATCH request
  async patch(endpoint, data) {
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        method: 'PATCH',
        headers: this.getHeaders(),
        body: JSON.stringify(data)
      })
      return await this.handleResponse(response)
    } catch (error) {
      throw this.handleError(error)
    }
  }

  // DELETE request
  async delete(endpoint) {
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        method: 'DELETE',
        headers: this.getHeaders()
      })
      return await this.handleResponse(response)
    } catch (error) {
      throw this.handleError(error)
    }
  }

  // Handle response
  async handleResponse(response) {
    if (!response.ok) {
      const error = await response.json().catch(() => ({}))
      throw new Error(error.message || `HTTP ${response.status}`)
    }
    return await response.json()
  }

  // Handle error
  handleError(error) {
    console.error('API Error:', error)
    return error
  }

  // ===== MENU ENDPOINTS =====
  getMenu() {
    return this.get('/menu')
  }

  getMenuById(id) {
    return this.get(`/menu/${id}`)
  }

  // ===== ORDER ENDPOINTS =====
  placeOrder(orderData) {
    return this.post('/orders', orderData)
  }

  getOrder(orderId) {
    return this.get(`/orders/${orderId}`)
  }

  getOrderStatus(orderId) {
    return this.get(`/orders/${orderId}/status`)
  }

  // Backend beneran punya PATCH /pesanan/{id}/status (lihat routes/api.php).
  // Field yang divalidasi controller PesananController@updateStatus bernama
  // "status_pesanan", BUKAN "status" -- ini yang sebelumnya bikin request
  // selalu gagal validasi (422) walaupun endpoint & method sudah benar.
  updateOrderStatus(orderId, statusPesanan) {
    return this.patch(`/pesanan/${orderId}/status`, { status_pesanan: statusPesanan })
  }

  // ===== STAFF ENDPOINTS =====
  // Backend belum punya route '/staff/orders'. Route yang beneran ada adalah
  // GET /pesanan (semua pesanan) -- difilter per status di sisi frontend
  // (lihat DapurPage.vue). Parameter `role` disimpan di signature untuk
  // kompatibilitas kalau nanti backend menambah filter per role.
  getStaffOrders(role) {
    return this.get('/pesanan')
  }

  updateOrderProgress(orderId, progress) {
    return this.put(`/orders/${orderId}/progress`, { progress })
  }

  completeOrder(orderId) {
    return this.put(`/orders/${orderId}/complete`, {})
  }

  // ===== PAYMENT ENDPOINTS =====
  processPayment(paymentData) {
    return this.post('/payments', paymentData)
  }

  getPaymentStatus(transactionId) {
    return this.get(`/payments/${transactionId}`)
  }

  // ===== STATISTICS ENDPOINTS =====
  getDailyStats() {
    return this.get('/stats/daily')
  }

  getMonthlyStats() {
    return this.get('/stats/monthly')
  }

  getTotalRevenue() {
    return this.get('/stats/revenue')
  }
}

const apiInstance = new ApiService()
export default apiInstance

// Named export supaya `import api, { getImageUrl } from '@/services/api'` di
// HomePage/KonfirmasiPage/AdminPage tidak lagi error "no export named getImageUrl".
export function getImageUrl(imagePath, fallback = '/placeholder.png') {
  return apiInstance.getImageUrl(imagePath, fallback)
}

// MenuPage.vue memakai nama berbeda: `storageUrl` (string base URL, bukan fungsi).
// Foto menu ada langsung di public/images/menu (bukan storage/app/public),
// jadi base-nya cukup URL backend tanpa suffix apa pun.
export const storageUrl = API_BASE_URL.replace('/api', '')