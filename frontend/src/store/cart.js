import { defineStore, storeToRefs } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const customerInfo = ref({
    name: '',
    phone: '',
    table: '',
    notes: ''
  })

  // Total harga (pakai `harga` & `qty`, konsisten dengan MenuPage.vue dkk)
  const total = computed(() => {
    return items.value.reduce((sum, item) => sum + (item.harga * item.qty), 0)
  })

  // Total jumlah item
  const itemCount = computed(() => {
    return items.value.reduce((sum, item) => sum + item.qty, 0)
  })

  // Add item to cart
  const addItem = (item) => {
    const existingItem = items.value.find(i => i.id === item.id)

    if (existingItem) {
      existingItem.qty += item.qty || 1
      // Kalau ada catatan baru dikirim, timpa catatan lama
      if (item.catatan !== undefined) existingItem.catatan = item.catatan
    } else {
      items.value.push({
        ...item,
        qty: item.qty || 1,
        cartItemId: Date.now() // Unique ID untuk cart item
      })
    }
  }

  // Remove item from cart
  const removeItem = (cartItemId) => {
    items.value = items.value.filter(i => i.cartItemId !== cartItemId)
  }

  // Update item quantity
  const updateQuantity = (cartItemId, qty) => {
    const item = items.value.find(i => i.cartItemId === cartItemId)
    if (item) {
      if (qty <= 0) {
        removeItem(cartItemId)
      } else {
        item.qty = qty
      }
    }
  }

  // Clear cart
  const clearCart = () => {
    items.value = []
  }

  // Set customer info
  const setCustomerInfo = (info) => {
    customerInfo.value = { ...customerInfo.value, ...info }
  }

  // Place order
  const placeOrder = async () => {
    // TODO: Call API to place order
    return {
      orderId: Math.floor(Math.random() * 100000),
      items: items.value,
      total: total.value,
      customerInfo: customerInfo.value,
      timestamp: new Date()
    }
  }

  return {
    items,
    customerInfo,
    total,
    itemCount,
    addItem,
    removeItem,
    updateQuantity,
    clearCart,
    setCustomerInfo,
    placeOrder
  }
})

// MenuPage.vue (dan mungkin halaman lain) memakai nama & bentuk berbeda:
// `useCart()` yang mengembalikan { totalItems, totalHarga, ... } alih-alih
// `useCartStore()` yang mengembalikan { itemCount, total, ... }. Composable
// ini menjembatani supaya keduanya mengarah ke store yang sama persis
// (bukan store terpisah), reaktivitasnya tetap terjaga lewat storeToRefs.
export function useCart() {
  const store = useCartStore()
  const { itemCount, total } = storeToRefs(store)
  return {
    ...store,
    totalItems: itemCount,
    totalQty: itemCount,
    totalHarga: total,
  }
}