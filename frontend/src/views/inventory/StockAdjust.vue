<script setup>
import { ref, onMounted } from 'vue'
import inventoryApi from '@/services/inventoryService'
import api from '@/services/api'


const branchId = ref('')
const productId = ref('')
const quantity = ref(0)
const loading = ref(false)

const branches = ref([])
const products = ref([])

const fetchOptions = async () => {
  try {
    const [branchRes, productRes] = await Promise.all([
      api.get('/my-branches'),
      api.get('/all-products')
    ])
    branches.value = branchRes.data
    products.value = productRes.data.data || productRes.data
  } catch (error) {
    console.error('Failed to load options:', error)
  }
}

const adjustStock = async () => {
  if (!branchId.value || !productId.value) {
    alert('Please select both a branch and a product.')
    return
  }
  loading.value = true
  try {
    await inventoryApi.adjust({
      branch_id: branchId.value,
      product_id: productId.value,
      quantity: quantity.value
    })
    alert('Stock adjusted successfully!')
    branchId.value = ''
    productId.value = ''
    quantity.value = 0
  } catch (error) {
    console.error('Failed to adjust stock:', error)
    const msg = error.response?.data?.message || 'Failed to adjust stock. Please try again.'
    alert(msg)
  }
  loading.value = false
}

onMounted(fetchOptions)
</script>

<template>
  <div class="inventory-form">
    <router-link to="/inventory" class="back-link">
      <span class="material-symbols-outlined">arrow_back</span>
      Back to Inventory
    </router-link>
    <h2>Adjust Stock</h2>
    <p class="form-subtitle">Select a branch and product, then enter the quantity adjustment (use negative values to reduce).</p>

    <form @submit.prevent="adjustStock">
      <div class="form-group">
        <label for="branch-select">Branch</label>
        <select id="branch-select" v-model="branchId" required>
          <option value="" disabled>-- Select Branch --</option>
          <option v-for="branch in branches" :key="branch.id" :value="branch.id">
            {{ branch.name }}
          </option>
        </select>
      </div>

      <div class="form-group">
        <label for="product-select">Product</label>
        <select id="product-select" v-model="productId" required>
          <option value="" disabled>-- Select Product --</option>
          <option
            v-for="product in products"
            :key="product.id"
            :value="product.id"
            :disabled="product.status === 'inactive'"
          >
            {{ product.name }} (SKU: {{ product.sku }}){{ product.status === 'inactive' ? ' [Inactive]' : '' }}
          </option>
        </select>
      </div>

      <div class="form-group">
        <label for="quantity-input">Quantity Adjustment</label>
        <input
          id="quantity-input"
          type="number"
          v-model.number="quantity"
          placeholder="e.g. +10 or -5"
          required
        />
        <small class="field-hint">Use positive values to add, negative to subtract.</small>
      </div>

      <button type="submit" :disabled="loading" class="btn-submit">
        {{ loading ? 'Adjusting...' : 'Adjust Stock' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.inventory-form {
  max-width: 500px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #818cf8;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 1rem;
  transition: color 0.2s;
}

.back-link:hover {
  color: #a5b4fc;
}

.back-link .material-symbols-outlined {
  font-size: 18px;
}

.inventory-form h2 {
  margin-bottom: 0.25rem;
}

.form-subtitle {
  color: #888;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.2rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.4rem;
  font-weight: 600;
  font-size: 0.9rem;
}

.form-group select,
.form-group input {
  width: 100%;
  padding: 0.6rem 0.8rem;
  border: 1px solid #444;
  border-radius: 6px;
  background: #1a1a2e;
  color: #eee;
  font-size: 0.95rem;
  box-sizing: border-box;
}

.form-group select:focus,
.form-group input:focus {
  outline: none;
  border-color: #646cff;
  box-shadow: 0 0 0 2px rgba(100, 108, 255, 0.25);
}

.field-hint {
  display: block;
  margin-top: 0.3rem;
  color: #777;
  font-size: 0.8rem;
}

.btn-submit {
  width: 100%;
  padding: 0.7rem;
  background: #646cff;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  margin-top: 0.5rem;
}

.btn-submit:hover:not(:disabled) {
  background: #535bf2;
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>