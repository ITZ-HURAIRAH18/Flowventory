<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/inventoryService'

const history = ref([])
const loading = ref(false)

const fetchHistory = async () => {
  loading.value = true
  try {
    const res = await api.history()
    history.value = res.data
  } catch (error) {
    console.error('Failed to load stock history:', error)
  }
  loading.value = false
}

onMounted(fetchHistory)
</script>

<template>
  <div>
    <h2>Stock History</h2>
    <div v-if="loading">Loading...</div>
    <table v-else>
      <thead>
        <tr>
          <th>Product</th>
          <th>Branch</th>
          <th>Quantity</th>
          <th>Action</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in history" :key="item.id">
          <td>{{ item.product.name }}</td>
          <td>{{ item.branch.name }}</td>
          <td>{{ item.quantity }}</td>
          <td>{{ item.action }}</td>
          <td>{{ new Date(item.created_at).toLocaleString() }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>