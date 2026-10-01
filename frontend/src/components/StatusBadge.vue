<template>
  <span
    class="lb-chip"
    :style="{ color: meta.color, backgroundColor: meta.bg }"
  >
    <span v-if="dot" class="lb-chip__dot" :style="{ backgroundColor: meta.color }"></span>
    {{ meta.label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, default: '' },
  dot: { type: Boolean, default: true },
})

const PETA = {
  baru: { label: 'Baru', color: '#c02a2a', bg: '#FBEAEA' },
  diproses: { label: 'Diproses', color: '#B85A38', bg: '#FBEAD9' },
  selesai: { label: 'Siap Diambil', color: '#2f7d32', bg: '#E9F6EA' },
  selesai_makan: { label: 'Selesai', color: '#6B4A34', bg: '#F1E7D7' },
  kosong: { label: 'Kosong', color: '#2f7d32', bg: '#E9F6EA' },
  terisi: { label: 'Terisi', color: '#c02a2a', bg: '#FBEAEA' },
  tunai: { label: 'Tunai', color: '#2f7d32', bg: '#E9F6EA' },
  qris: { label: 'QRIS', color: '#3B82F6', bg: '#E7EFFE' },
  tersedia: { label: 'Tersedia', color: '#2f7d32', bg: '#E9F6EA' },
  habis: { label: 'Habis', color: '#c02a2a', bg: '#FBEAEA' },
}

const meta = computed(() => {
  const key = String(props.status ?? '').toLowerCase()
  if (PETA[key]) return PETA[key]
  const label = props.status ? String(props.status) : '-'
  return { label, color: '#6B4A34', bg: '#F1E7D7' }
})
</script>

<style scoped>
.lb-chip__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}
</style>
