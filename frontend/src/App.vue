<template>
  <StaffLayout v-if="isStaffRoute">
    <router-view />
  </StaffLayout>

  <router-view v-else-if="route.name === 'Login' || route.name === 'Admin'" />

  <CustomerLayout v-else>
    <router-view />
  </CustomerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import CustomerLayout from './layouts/CustomerLayout.vue'
import StaffLayout from './layouts/StaffLayout.vue'

const route = useRoute()

const STAFF_ROUTE_NAMES = ['Waiter', 'Dapur', 'Kasir']
const isStaffRoute = computed(() => STAFF_ROUTE_NAMES.includes(route.name))
</script>