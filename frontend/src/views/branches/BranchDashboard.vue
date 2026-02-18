<!-- src/views/branches/BranchDashboard.vue -->
<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Branch Dashboard</h1>
      <router-link to="/branches" class="btn btn-secondary">← Back to Branches</router-link>
    </div>

    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-else-if="branch">
      <div class="mb-6">
        <h2 class="text-xl font-semibold">{{ branch.name }}</h2>
        <p class="text-gray-600">{{ branch.address }}</p>
        <p class="text-gray-600">Manager: {{ branch.manager?.name || 'N/A' }}</p>
      </div>

      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white shadow rounded p-4">
          <h3 class="text-sm text-gray-500">Total Products</h3>
          <p class="text-2xl font-bold">{{ branch.inventories?.length || 0 }}</p>
        </div>
        <div class="bg-white shadow rounded p-4">
          <h3 class="text-sm text-gray-500">Total Orders</h3>
          <p class="text-2xl font-bold">{{ branch.orders?.length || 0 }}</p>
        </div>
        <div class="bg-white shadow rounded p-4">
          <h3 class="text-sm text-gray-500">Low Stock Items</h3>
          <p class="text-2xl font-bold text-red-600">{{ lowStockCount }}</p>
        </div>
      </div>

      <!-- Inventory Table -->
      <h3 class="text-lg font-semibold mb-2">Inventory</h3>
      <table class="table-auto w-full border mb-6">
        <thead>
          <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in branch.inventories" :key="item.id">
            <td>{{ item.product?.name }}</td>
            <td>{{ item.quantity }}</td>
            <td>
              <span v-if="item.quantity <= 5" class="text-red-600 font-bold">Low Stock</span>
              <span v-else class="text-green-600">In Stock</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="text-red-500">Branch not found.</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const branch = ref(null)
const loading = ref(true)

const lowStockCount = computed(() => {
  if (!branch.value?.inventories) return 0
  return branch.value.inventories.filter(i => i.quantity <= 5).length
})

const fetchBranch = async () => {
  loading.value = true
  try {
    const res = await api.get(`/branches/${route.params.id}`)
    branch.value = res.data.data || res.data
  } catch (err) {
    console.error('Failed to load branch', err)
  } finally {
    loading.value = false
  }
}

onMounted(fetchBranch)
</script>
