<template>
  <div>
    <h2>Create Order</h2>

    <label>Branch</label>
    <select v-model="branch_id">
      <option v-for="branch in branches" :key="branch.id" :value="branch.id">
        {{ branch.name }}
      </option>
    </select>

    <hr />

    <h3>Add Products</h3>

    <div v-for="(item, index) in items" :key="index">
      <select v-model="item.product_id">
        <option v-for="product in products" :key="product.id" :value="product.id">
          {{ product.name }} ({{ product.price }})
        </option>
      </select>

      <input type="number" v-model.number="item.quantity" min="1" />

      <button @click="removeItem(index)">Remove</button>
    </div>

    <button @click="addItem">Add Product</button>

    <hr />

    <p>Subtotal: {{ subtotal }}</p>
    <p>Tax (10%): {{ tax }}</p>
    <h3>Total: {{ total }}</h3>

    <button @click="submitOrder">Submit Order</button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const branches = ref([])
const products = ref([])
const branch_id = ref(null)
const items = ref([{ product_id: null, quantity: 1 }])

onMounted(async () => {
  branches.value = (await axios.get('/api/branches')).data
  products.value = (await axios.get('/api/products')).data
})

const addItem = () => {
  items.value.push({ product_id: null, quantity: 1 })
}

const removeItem = (index) => {
  items.value.splice(index, 1)
}

const subtotal = computed(() => {
  return items.value.reduce((sum, item) => {
    const product = products.value.find(p => p.id === item.product_id)
    if (!product) return sum
    return sum + product.price * item.quantity
  }, 0)
})

const tax = computed(() => subtotal.value * 0.10)
const total = computed(() => subtotal.value + tax.value)

const submitOrder = async () => {
  try {
    await axios.post('/api/orders', {
      branch_id: branch_id.value,
      items: items.value
    })
    alert('Order created successfully')
  } catch (e) {
    alert(e.response?.data?.message || 'Error creating order')
  }
}
</script>