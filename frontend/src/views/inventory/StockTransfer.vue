<script setup>
import { ref, onMounted } from 'vue'
import inventoryApi from '@/services/inventoryService'
import branchService from '@/services/branchService'
import productService from '@/services/productService'

const fromBranch = ref('')
const toBranch = ref('')
const productId = ref('')
const quantity = ref(1)
const loading = ref(false)

const branches = ref([])
const products = ref([])

const fetchOptions = async () => {
  try {
    const [branchRes, productRes] = await Promise.all([
      branchService.getAll(),
      productService.getAll()
    ])
    branches.value = branchRes.data.data || branchRes.data
    products.value = productRes.data.data || productRes.data
  } catch (error) {
    console.error('Failed to load options:', error)
  }
}

const transfer = async () => {
  if (!fromBranch.value || !toBranch.value || !productId.value || quantity.value < 1) {
    alert('Please fill in all fields correctly.')
    return
  }
  if (fromBranch.value === toBranch.value) {
    alert('Source and destination branches must be different.')
    return
  }
  loading.value = true
  try {
    await inventoryApi.transfer({
      from_branch_id: fromBranch.value,
      to_branch_id: toBranch.value,
      product_id: productId.value,
      quantity: quantity.value
    })
    alert('Stock transferred successfully!')
    fromBranch.value = ''
    toBranch.value = ''
    productId.value = ''
    quantity.value = 1
  } catch (error) {
    console.error('Failed to transfer stock:', error)
    const msg = error.response?.data?.message || 'Failed to transfer stock. Please try again.'
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
    <h2>Transfer Stock</h2>
    <p class="form-subtitle">Move stock between branches by selecting source, destination, product, and quantity.</p>

    <form @submit.prevent="transfer">
      <div class="form-group">
        <label for="from-branch">From Branch</label>
        <select id="from-branch" v-model="fromBranch" required>
          <option value="" disabled>-- Select Source Branch --</option>
          <option v-for="branch in branches" :key="branch.id" :value="branch.id">
            {{ branch.name }}
          </option>
        </select>
      </div>

      <div class="form-group">
        <label for="to-branch">To Branch</label>
        <select id="to-branch" v-model="toBranch" required>
          <option value="" disabled>-- Select Destination Branch --</option>
          <option
            v-for="branch in branches"
            :key="branch.id"
            :value="branch.id"
            :disabled="branch.id === fromBranch"
          >
            {{ branch.name }}
          </option>
        </select>
      </div>

      <div class="form-group">
        <label for="product-select">Product</label>
        <select id="product-select" v-model="productId" required>
          <option value="" disabled>-- Select Product --</option>
          <option v-for="product in products" :key="product.id" :value="product.id">
            {{ product.name }} (SKU: {{ product.sku }})
          </option>
        </select>
      </div>

      <div class="form-group">
        <label for="quantity-input">Quantity</label>
        <input
          id="quantity-input"
          type="number"
          v-model.number="quantity"
          min="1"
          placeholder="Enter quantity to transfer"
          required
        />
      </div>

      <button type="submit" :disabled="loading" class="btn-submit">
        {{ loading ? 'Transferring...' : 'Transfer Stock' }}
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