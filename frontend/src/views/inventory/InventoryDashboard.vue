<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/inventoryService'

const inventory = ref([])
const loading = ref(false)

const fetchInventory = async () => {
  loading.value = true
  try {
    const res = await api.getAll()
    inventory.value = res.data
  } catch (error) {
    console.error('Failed to load inventory:', error)
  }
  loading.value = false
}

onMounted(fetchInventory)
</script>

<template>
  <div>
    <h2>Inventory Dashboard</h2>
    <div v-if="loading">Loading...</div>

    <table v-else>
      <thead>
        <tr>
          <th>Product</th>
          <th>Branch</th>
          <th>Stock</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in inventory" :key="item.id">
          <td>{{ item.product.name }}</td>
          <td>{{ item.branch.name }}</td>
          <td :class="{ 'text-red-600': item.stock <= 5 }">{{ item.stock }}</td>
          <td>{{ item.stock > 0 ? 'Available' : 'Out of stock' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>