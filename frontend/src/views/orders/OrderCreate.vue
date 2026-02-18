<template>
  <div>
    <h2>Create Order</h2>

    <label>Branch</label>
    <select v-model="branch_id" @change="onBranchChange">
      <option value="" disabled>-- Select Branch --</option>
      <option v-for="branch in branches" :key="branch.id" :value="branch.id">
        {{ branch.name }}
      </option>
    </select>

    <hr />

    <h3>Add Products</h3>

    <p v-if="!branch_id" style="color: #888;">Please select a branch first.</p>
    <p v-else-if="products.length === 0" style="color: #888;">No products with stock at this branch.</p>

    <div v-for="(item, index) in items" :key="index">
      <select v-model="item.product_id">
        <option value="" disabled>-- Select Product --</option>
        <option v-for="product in products" :key="product.id" :value="product.id">
          {{ product.name }} — Rs.{{ product.sale_price }} (Stock: {{ product.stock }})
        </option>
      </select>

      <input type="number" v-model.number="item.quantity" min="1" />

      <button @click="removeItem(index)">Remove</button>
    </div>

    <button @click="addItem" :disabled="!branch_id">Add Product</button>

    <hr />

    <p>Subtotal: Rs.{{ subtotal.toFixed(2) }}</p>
    <p>Tax: Rs.{{ tax.toFixed(2) }}</p>
    <h3>Total: Rs.{{ total.toFixed(2) }}</h3>

    <button @click="submitOrder" :disabled="!branch_id || items.length === 0">Submit Order</button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import branchService from '@/services/branchService'
import inventoryService from '@/services/inventoryService'
import orderService from '@/services/orderService'

const branches = ref([])
const products = ref([])
const branch_id = ref('')
const items = ref([{ product_id: '', quantity: 1 }])
const loadingProducts = ref(false)

// Load branches on mount
onMounted(async () => {
  try {
    const res = await branchService.getAll()
    branches.value = res.data.data || res.data
  } catch (error) {
    console.error('Failed to load branches:', error)
  }
})

// When branch changes, fetch products available at that branch
const onBranchChange = async () => {
  if (!branch_id.value) {
    products.value = []
    return
  }

  loadingProducts.value = true
  try {
    const res = await inventoryService.getProductsByBranch(branch_id.value)
    products.value = res.data
    // Reset product selections since available products changed
    items.value = [{ product_id: '', quantity: 1 }]
  } catch (error) {
    console.error('Failed to load products for branch:', error)
    products.value = []
  }
  loadingProducts.value = false
}

const addItem = () => {
  items.value.push({ product_id: '', quantity: 1 })
}

const removeItem = (index) => {
  items.value.splice(index, 1)
}

const subtotal = computed(() => {
  return items.value.reduce((sum, item) => {
    const product = products.value.find(p => p.id === item.product_id)
    if (!product) return sum
    return sum + product.sale_price * item.quantity
  }, 0)
})

const tax = computed(() => {
  return items.value.reduce((sum, item) => {
    const product = products.value.find(p => p.id === item.product_id)
    if (!product) return sum
    return sum + (product.sale_price * item.quantity * (product.tax_percentage / 100))
  }, 0)
})

const total = computed(() => subtotal.value + tax.value)

const submitOrder = async () => {
  try {
    await orderService.create({
      branch_id: branch_id.value,
      items: items.value.filter(i => i.product_id) // only send items with selected products
    })
    alert('Order created successfully')
    // Reset form
    branch_id.value = ''
    products.value = []
    items.value = [{ product_id: '', quantity: 1 }]
  } catch (e) {
    alert(e.response?.data?.message || 'Error creating order')
  }
}
</script>