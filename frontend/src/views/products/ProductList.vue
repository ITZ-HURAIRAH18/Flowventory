<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import productService from '@/services/productService'

const router = useRouter()
const products = ref([])
const search = ref('')
const loading = ref(false)

const fetchProducts = async () => {
  loading.value = true
  try {
    const res = await productService.getAll({
      search: search.value
    })
    products.value = res.data.data
  } catch (error) {
    console.error(error)
  }
  loading.value = false
}

const deleteProduct = async (id) => {
  if (!confirm('Are you sure?')) return
  await productService.delete(id)
  fetchProducts()
}

onMounted(fetchProducts)
</script>

<template>
  <div>
    <h2>Products</h2>

    <input
      v-model="search"
      placeholder="Search..."
      @input="fetchProducts"
    />

    <button @click="router.push('/products/create')">
      Create Product
    </button>

    <div v-if="loading">Loading...</div>

    <table v-else>
      <thead>
        <tr>
          <th>Name</th>
          <th>SKU</th>
          <th>Sale Price</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="product in products" :key="product.id">
          <td>{{ product.name }}</td>
          <td>{{ product.sku }}</td>
          <td>{{ product.sale_price }}</td>
          <td>{{ product.status === 'active' ? 'Active' : 'Inactive' }}</td>
          <td>
            <button @click="router.push(`/products/${product.id}/edit`)">
              Edit
            </button>
            <button @click="deleteProduct(product.id)">
              Delete
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>