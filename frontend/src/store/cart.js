import { defineStore } from 'pinia'

function baca(key, fallback) {
  try {
    const raw = localStorage.getItem(key)
    return raw ? JSON.parse(raw) : fallback
  } catch {
    return fallback
  }
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: baca('cartItems', []),
  }),

  getters: {
    totalItems: (state) => state.items.reduce((n, i) => n + (i.qty || 0), 0),
    totalHarga: (state) => state.items.reduce((n, i) => n + (Number(i.harga) || 0) * (i.qty || 0), 0),
  },

  actions: {
    simpan() {
      localStorage.setItem('cartItems', JSON.stringify(this.items))
    },

    // Tambah 1 porsi menu ke keranjang
    addItem(menu) {
      const ada = this.items.find((i) => i.id === menu.id)
      if (ada) {
        ada.qty += 1
      } else {
        this.items.push({
          id: menu.id,
          nama_menu: menu.nama_menu,
          harga: Number(menu.harga) || 0,
          gambar: menu.gambar,
          qty: 1,
        })
      }
      this.simpan()
    },

    // Ubah jumlah; kalau jadi 0 atau kurang, item dihapus
    updateQty(id, qty) {
      const item = this.items.find((i) => i.id === id)
      if (!item) return
      if (qty <= 0) {
        this.removeItem(id)
        return
      }
      item.qty = qty
      this.simpan()
    },

    removeItem(id) {
      this.items = this.items.filter((i) => i.id !== id)
      this.simpan()
    },

    // Kosongkan keranjang (panggil setelah pesanan berhasil dibuat)
    clear() {
      this.items = []
      this.simpan()
    },
  },
})
