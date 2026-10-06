// API Configuration
// ponytail: port 8000 hardcode, add when deploy multi-host.
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

class ApiService {
  constructor(baseURL = API_BASE_URL) {
    this.baseURL = baseURL
  }

  // Helper method untuk add authorization header
  getHeaders(isMultipart = false) {
    const token = localStorage.getItem('authToken')
    return {
      ...(!isMultipart && { 'Content-Type': 'application/json' }),
      'Accept': 'application/json', // penting: kalau tidak ada, Laravel bisa redirect (bukan 401 JSON) saat auth gagal
      ...(token && { 'Authorization': `Bearer ${token}` })
    }
  }

  getBody(data) {
    return data instanceof FormData ? data : JSON.stringify(data)
  }

  // Helper method untuk image URL. Foto menu tersimpan di
  // backend/public/images/menu/... jadi langsung bisa di-serve web server
  // (ikuti deploy: folder images/ ikut ter-upload ke public_html).
  // URL dibentuk dari base backend + path gambar, fallback logo lokal (selalu ada).
  getImageUrl(imagePath, fallback = '/logo.png') {
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
        headers: this.getHeaders(data instanceof FormData),
        body: this.getBody(data)
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
        headers: this.getHeaders(data instanceof FormData),
        body: this.getBody(data)
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
        headers: this.getHeaders(data instanceof FormData),
        body: this.getBody(data)
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

  // Backend beneran punya PATCH /pesanan/{id}/status (lihat routes/api.php).
  // Field yang divalidasi controller PesananController@updateStatus bernama
  // "status_pesanan", BUKAN "status" -- ini yang sebelumnya bikin request
  // selalu gagal validasi (422) walaupun endpoint & method sudah benar.
  updateOrderStatus(orderId, statusPesanan) {
    return this.patch(`/pesanan/${orderId}/status`, { status_pesanan: statusPesanan })
  }

  // ===== STAFF ENDPOINTS =====
  // Backend cuma punya GET /pesanan — difilter per status di frontend (lihat DapurPage.vue).
  getStaffOrders(role) {
    return this.get('/pesanan')
  }
}

const apiInstance = new ApiService()
export default apiInstance

// Named export supaya `import api, { getImageUrl } from '@/services/api'` di
// HomePage/KonfirmasiPage/AdminPage tidak lagi error "no export named getImageUrl".
export function getImageUrl(imagePath, fallback = '/logo.png') {
  return apiInstance.getImageUrl(imagePath, fallback)
}

// MenuPage.vue memakai nama berbeda: `storageUrl` (string base URL, bukan fungsi).
// Foto menu ada langsung di public/images/menu (bukan storage/app/public),
// jadi base-nya cukup URL backend tanpa suffix apa pun.
export const storageUrl = API_BASE_URL.replace('/api', '')