<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import inventoryService from '@/services/inventoryService'
import orderService from '@/services/orderService'

const router = useRouter()

const branches = ref([])
const products = ref([])
const branch_id = ref('')
const items = ref([{ product_id: '', quantity: 1 }])

const loadingBranches = ref(false)
const loadingProducts = ref(false)
const submitting = ref(false)
const error = ref(null)
const success = ref(null)

// Load branches on mount
onMounted(async () => {
  loadingBranches.value = true
  try {
    const res = await api.get('/all-branches')
    branches.value = res.data
  } catch (err) {
    error.value = 'Failed to load branches. Please refresh the page.'
  }
  loadingBranches.value = false
})

// When branch changes, fetch products
const onBranchChange = async () => {
  error.value = null
  if (!branch_id.value) {
    products.value = []
    return
  }

  loadingProducts.value = true
  try {
    const res = await inventoryService.getProductsByBranch(branch_id.value)
    products.value = res.data
    items.value = [{ product_id: '', quantity: 1 }]
  } catch (err) {
    error.value = 'Failed to load products for this branch.'
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

// Get product details for display
const getProduct = (productId) => {
  return products.value.find(p => p.id === productId)
}

const subtotal = computed(() => {
  return items.value.reduce((sum, item) => {
    const product = getProduct(item.product_id)
    if (!product) return sum
    return sum + product.sale_price * item.quantity
  }, 0)
})

const tax = computed(() => {
  return items.value.reduce((sum, item) => {
    const product = getProduct(item.product_id)
    if (!product) return sum
    return sum + (product.sale_price * item.quantity * (product.tax_percentage / 100))
  }, 0)
})

const total = computed(() => subtotal.value + tax.value)

const validItems = computed(() => items.value.filter(i => i.product_id && i.quantity > 0))

const canSubmit = computed(() => branch_id.value && validItems.value.length > 0 && !submitting.value)

const submitOrder = async () => {
  error.value = null
  success.value = null
  submitting.value = true

  try {
    await orderService.create({
      branch_id: branch_id.value,
      items: validItems.value
    })
    success.value = 'Order created successfully!'
    // Reset form after short delay
    setTimeout(() => {
      branch_id.value = ''
      products.value = []
      items.value = [{ product_id: '', quantity: 1 }]
      success.value = null
    }, 2000)
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to create order. Please try again.'
  }
  submitting.value = false
}
</script>

<template>
  <div class="order-page">
    <!-- Page Header -->
    <div class="page-header">
      <router-link to="/dashboard" class="back-link">
        <span class="material-symbols-outlined">arrow_back</span>
        Back to Dashboard
      </router-link>
      <h1>
        <span class="material-symbols-outlined page-icon">receipt_long</span>
        Create Order
      </h1>
      <p class="page-sub">Select a branch, add products, and submit your order</p>
    </div>

    <!-- Error Banner -->
    <div v-if="error" class="alert alert-error">
      <span class="material-symbols-outlined">error</span>
      {{ error }}
      <button class="alert-close" @click="error = null">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <!-- Success Banner -->
    <div v-if="success" class="alert alert-success">
      <span class="material-symbols-outlined">check_circle</span>
      {{ success }}
    </div>

    <div class="order-layout">
      <!-- Left: Form -->
      <div class="order-form-section">
        <!-- Branch Selection -->
        <div class="section-card">
          <h3 class="section-title">
            <span class="material-symbols-outlined">store</span>
            Select Branch
          </h3>

          <div v-if="loadingBranches" class="field-loading">
            <span class="material-symbols-outlined spin">progress_activity</span>
            Loading branches...
          </div>

          <div v-else class="form-group">
            <select v-model="branch_id" @change="onBranchChange" class="form-select">
              <option value="" disabled>-- Select Branch --</option>
              <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Products Section -->
        <div class="section-card">
          <h3 class="section-title">
            <span class="material-symbols-outlined">shopping_cart</span>
            Order Items
          </h3>

          <p v-if="!branch_id" class="hint-text">
            <span class="material-symbols-outlined">info</span>
            Please select a branch first
          </p>

          <div v-else-if="loadingProducts" class="field-loading">
            <span class="material-symbols-outlined spin">progress_activity</span>
            Loading products...
          </div>

          <p v-else-if="products.length === 0" class="hint-text">
            <span class="material-symbols-outlined">inventory_2</span>
            No products with stock at this branch
          </p>

          <template v-else>
            <div v-for="(item, index) in items" :key="index" class="item-row">
              <div class="item-fields">
                <div class="form-group flex-2">
                  <label>Product</label>
                  <select v-model="item.product_id" class="form-select">
                    <option value="" disabled>-- Select Product --</option>
                    <option
                      v-for="product in products"
                      :key="product.id"
                      :value="product.id"
                      :disabled="product.status === 'inactive'"
                    >
                      {{ product.name }} — Rs.{{ product.sale_price }} (Stock: {{ product.stock }}){{ product.status === 'inactive' ? ' [Inactive]' : '' }}
                    </option>
                  </select>
                </div>

                <div class="form-group flex-1">
                  <label>Qty</label>
                  <input
                    type="number"
                    v-model.number="item.quantity"
                    min="1"
                    class="form-input"
                    placeholder="1"
                  />
                </div>

                <button
                  class="btn-remove"
                  @click="removeItem(index)"
                  :disabled="items.length <= 1"
                  title="Remove item"
                >
                  <span class="material-symbols-outlined">delete</span>
                </button>
              </div>

              <!-- Line total preview -->
              <div v-if="getProduct(item.product_id)" class="line-total">
                Line total: Rs.{{ (getProduct(item.product_id).sale_price * item.quantity).toFixed(2) }}
              </div>
            </div>

            <button class="btn-add-item" @click="addItem">
              <span class="material-symbols-outlined">add</span>
              Add Another Product
            </button>
          </template>
        </div>
      </div>

      <!-- Right: Order Summary -->
      <div class="order-summary-section">
        <div class="section-card summary-card">
          <h3 class="section-title">
            <span class="material-symbols-outlined">summarize</span>
            Order Summary
          </h3>

          <div class="summary-rows">
            <div class="summary-row">
              <span>Items</span>
              <span>{{ validItems.length }}</span>
            </div>
            <div class="summary-row">
              <span>Subtotal</span>
              <span>Rs.{{ subtotal.toFixed(2) }}</span>
            </div>
            <div class="summary-row">
              <span>Tax</span>
              <span>Rs.{{ tax.toFixed(2) }}</span>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-row total-row">
              <span>Grand Total</span>
              <span>Rs.{{ total.toFixed(2) }}</span>
            </div>
          </div>

          <button
            class="btn-submit"
            :disabled="!canSubmit"
            @click="submitOrder"
          >
            <span v-if="submitting" class="material-symbols-outlined spin">progress_activity</span>
            <span v-else class="material-symbols-outlined">shopping_cart_checkout</span>
            {{ submitting ? 'Processing...' : 'Submit Order' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.order-page {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ── Header ── */
.page-header {
  margin-bottom: 1.5rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #818cf8;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 0.75rem;
  transition: color 0.2s;
}

.back-link:hover {
  color: #a5b4fc;
}

.back-link .material-symbols-outlined {
  font-size: 18px;
}

.page-header h1 {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 1.6rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 0.3rem;
}

.page-icon {
  font-size: 28px;
  color: #fbbf24;
}

.page-sub {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0;
}

/* ── Alerts ── */
.alert {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 500;
  margin-bottom: 1.25rem;
  animation: fadeIn 0.3s ease;
}

.alert-error {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #f87171;
}

.alert-success {
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.25);
  color: #34d399;
}

.alert-close {
  margin-left: auto;
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  opacity: 0.7;
  padding: 0;
}

.alert-close:hover {
  opacity: 1;
}

.alert-close .material-symbols-outlined {
  font-size: 18px;
}

/* ── Layout ── */
.order-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 1rem;
  align-items: start;
}

.section-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 14px;
  padding: 1.25rem;
  margin-bottom: 0.75rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: #e2e8f0;
  margin: 0 0 1rem;
}

.section-title .material-symbols-outlined {
  font-size: 20px;
  color: #94a3b8;
}

/* ── Loading ── */
.field-loading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 0;
  color: #64748b;
  font-size: 0.88rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
  font-size: 20px;
}

/* ── Hint text ── */
.hint-text {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  font-size: 0.88rem;
  margin: 0;
  padding: 0.5rem 0;
}

.hint-text .material-symbols-outlined {
  font-size: 18px;
}

/* ── Form fields ── */
.form-group {
  margin-bottom: 0.75rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.35rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.form-select,
.form-input {
  width: 100%;
  padding: 0.6rem 0.8rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #e2e8f0;
  font-size: 0.9rem;
  font-family: inherit;
  box-sizing: border-box;
  transition: border-color 0.2s;
}

.form-select:focus,
.form-input:focus {
  outline: none;
  border-color: rgba(251, 191, 36, 0.4);
  box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.1);
}

.form-select option {
  background: #1e1f2e;
  color: #e2e8f0;
}

/* ── Item rows ── */
.item-row {
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.item-row:last-of-type {
  border-bottom: none;
}

.item-fields {
  display: flex;
  gap: 0.75rem;
  align-items: flex-end;
}

.flex-2 { flex: 2; }
.flex-1 { flex: 1; }

.btn-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 8px;
  border: 1px solid rgba(239, 68, 68, 0.2);
  background: rgba(239, 68, 68, 0.08);
  color: #f87171;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.2s;
}

.btn-remove:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.35);
}

.btn-remove:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.btn-remove .material-symbols-outlined {
  font-size: 18px;
}

.line-total {
  font-size: 0.78rem;
  color: #64748b;
  margin-top: 0.35rem;
  text-align: right;
}

.btn-add-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  width: 100%;
  padding: 0.6rem;
  border: 1px dashed rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  background: transparent;
  color: #94a3b8;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  margin-top: 0.75rem;
  transition: all 0.2s;
}

.btn-add-item:hover {
  border-color: rgba(251, 191, 36, 0.3);
  color: #fbbf24;
  background: rgba(251, 191, 36, 0.05);
}

.btn-add-item .material-symbols-outlined {
  font-size: 18px;
}

/* ── Summary ── */
.summary-card {
  position: sticky;
  top: 80px;
}

.summary-rows {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  margin-bottom: 1.25rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.88rem;
  color: #94a3b8;
}

.total-row {
  font-size: 1.1rem;
  font-weight: 700;
  color: #f8fafc;
}

.summary-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.06);
  margin: 0.25rem 0;
}

.btn-submit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.75rem;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s;
  font-family: inherit;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
}

.btn-submit:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-submit .material-symbols-outlined {
  font-size: 20px;
}

/* ── Responsive ── */
@media (max-width: 900px) {
  .order-layout {
    grid-template-columns: 1fr;
  }

  .summary-card {
    position: static;
  }
}

@media (max-width: 640px) {
  .item-fields {
    flex-wrap: wrap;
  }

  .flex-2, .flex-1 {
    flex: 1 1 100%;
  }

  .page-header h1 {
    font-size: 1.3rem;
  }
}
</style>