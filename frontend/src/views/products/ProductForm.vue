<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import productService from '@/services/productService'

const route = useRoute()
const router = useRouter()

const product = reactive({
  name: '',
  sku: '',
  cost_price: '',
  sale_price: '',
  tax_percentage: '',
  status: 'active'
})

const errors = ref({})
const generalError = ref('')
const loading = ref(false)
const isEdit = !!route.params.id

onMounted(async () => {
  if (isEdit) {
    try {
      const res = await productService.get(route.params.id)
      Object.assign(product, res.data)
    } catch (err) {
      generalError.value = 'Failed to load product data.'
    }
  }
})

const submit = async () => {
  errors.value = {}
  generalError.value = ''
  loading.value = true

  try {
    if (isEdit) {
      await productService.update(route.params.id, product)
    } else {
      await productService.create(product)
    }
    router.push('/products')
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      // Laravel validation errors — map to field-level messages
      errors.value = err.response.data.errors
    } else if (err.response?.data?.message) {
      generalError.value = err.response.data.message
    } else {
      generalError.value = 'An unexpected error occurred. Please try again.'
    }
  }

  loading.value = false
}
</script>

<template>
  <div class="product-form-page">
    <router-link to="/products" class="back-link">
      <span class="material-symbols-outlined">arrow_back</span>
      Back to Products
    </router-link>

    <h2>{{ isEdit ? 'Edit Product' : 'Create Product' }}</h2>
    <p class="form-subtitle">
      {{ isEdit ? 'Update the product details below.' : 'Fill in the details to add a new product.' }}
    </p>

    <!-- General error banner -->
    <div v-if="generalError" class="error-banner">
      <span class="material-symbols-outlined">error</span>
      {{ generalError }}
    </div>

    <form @submit.prevent="submit">
      <!-- Name -->
      <div class="form-group" :class="{ 'has-error': errors.name }">
        <label for="product-name">Name</label>
        <input
          id="product-name"
          v-model="product.name"
          type="text"
          placeholder="Product name"
          required
        />
        <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
      </div>

      <!-- SKU -->
      <div class="form-group" :class="{ 'has-error': errors.sku }">
        <label for="product-sku">SKU</label>
        <input
          id="product-sku"
          v-model="product.sku"
          type="text"
          placeholder="Unique SKU code"
          required
        />
        <span v-if="errors.sku" class="field-error">{{ errors.sku[0] }}</span>
      </div>

      <!-- Cost Price & Sale Price side by side -->
      <div class="form-row">
        <div class="form-group" :class="{ 'has-error': errors.cost_price }">
          <label for="product-cost">Cost Price</label>
          <input
            id="product-cost"
            v-model="product.cost_price"
            type="number"
            step="0.01"
            min="0"
            placeholder="0.00"
            required
          />
          <span v-if="errors.cost_price" class="field-error">{{ errors.cost_price[0] }}</span>
        </div>

        <div class="form-group" :class="{ 'has-error': errors.sale_price }">
          <label for="product-sale">Sale Price</label>
          <input
            id="product-sale"
            v-model="product.sale_price"
            type="number"
            step="0.01"
            min="0"
            placeholder="0.00"
            required
          />
          <span v-if="errors.sale_price" class="field-error">{{ errors.sale_price[0] }}</span>
        </div>
      </div>

      <!-- Tax % & Status side by side -->
      <div class="form-row">
        <div class="form-group" :class="{ 'has-error': errors.tax_percentage }">
          <label for="product-tax">Tax Percentage (%)</label>
          <input
            id="product-tax"
            v-model="product.tax_percentage"
            type="number"
            step="0.01"
            min="0"
            max="100"
            placeholder="0.00"
            required
          />
          <span v-if="errors.tax_percentage" class="field-error">{{ errors.tax_percentage[0] }}</span>
        </div>

        <div class="form-group" :class="{ 'has-error': errors.status }">
          <label for="product-status">Status</label>
          <select id="product-status" v-model="product.status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <span v-if="errors.status" class="field-error">{{ errors.status[0] }}</span>
        </div>
      </div>

      <button type="submit" :disabled="loading" class="btn-submit">
        <span v-if="loading" class="material-symbols-outlined spin">progress_activity</span>
        {{ loading ? 'Saving...' : (isEdit ? 'Update Product' : 'Create Product') }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.product-form-page {
  max-width: 560px;
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
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

.product-form-page h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 0.25rem;
}

.form-subtitle {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0 0 1.5rem;
}

/* ─── Error Banner ─── */
.error-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: 8px;
  color: #f87171;
  font-size: 0.88rem;
  font-weight: 500;
  margin-bottom: 1.25rem;
}

.error-banner .material-symbols-outlined {
  font-size: 20px;
  flex-shrink: 0;
}

/* ─── Form Group ─── */
.form-group {
  margin-bottom: 1.2rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.4rem;
  font-weight: 600;
  font-size: 0.9rem;
  color: #cbd5e1;
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
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group select:focus,
.form-group input:focus {
  outline: none;
  border-color: #646cff;
  box-shadow: 0 0 0 2px rgba(100, 108, 255, 0.25);
}

/* Error state */
.form-group.has-error select,
.form-group.has-error input {
  border-color: #ef4444;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.15);
}

.field-error {
  display: block;
  margin-top: 0.35rem;
  color: #f87171;
  font-size: 0.8rem;
  font-weight: 500;
}

/* ─── Side-by-side row ─── */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

@media (max-width: 480px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

/* ─── Submit Button ─── */
.btn-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
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

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
  font-size: 18px;
}
</style>