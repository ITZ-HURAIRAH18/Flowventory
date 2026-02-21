<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import productService from '@/services/productService'

const props = defineProps({ productObject: Object })
const emit  = defineEmits(['saved', 'close'])

const isEdit = computed(() => !!props.productObject?.id)

const form = reactive({
  name:           props.productObject?.name           || '',
  sku:            props.productObject?.sku            || '',
  cost_price:     props.productObject?.cost_price     || '',
  sale_price:     props.productObject?.sale_price     || '',
  tax_percentage: props.productObject?.tax_percentage || '',
  status:         props.productObject?.status         || 'active'
})

const saving    = ref(false)
const error     = ref('')
const errors    = reactive({ name: '', sku: '', cost_price: '', sale_price: '', tax_percentage: '' })

/* ── field validation ── */
const validate = () => {
  errors.name           = form.name.trim() ? '' : 'Product name is required.'
  errors.sku            = form.sku.trim() ? '' : 'SKU is required.'
  errors.cost_price     = form.cost_price !== '' ? '' : 'Cost price is required.'
  errors.sale_price     = form.sale_price !== '' ? '' : 'Sale price is required.'
  errors.tax_percentage = form.tax_percentage !== '' ? '' : 'Tax percentage is required.'
  
  return !errors.name && !errors.sku && !errors.cost_price && !errors.sale_price && !errors.tax_percentage
}

const submitForm = async () => {
  if (!validate()) return
  saving.value = true
  error.value  = ''
  try {
    if (isEdit.value) {
      await productService.update(props.productObject.id, form)
    } else {
      await productService.create(form)
    }
    emit('saved')
    emit('close')
  } catch (err) {
    error.value = err?.response?.data?.message || 'An error occurred. Please try again.'
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const serverErrors = err.response.data.errors
      Object.keys(serverErrors).forEach(key => {
        if (errors.hasOwnProperty(key)) errors[key] = serverErrors[key][0]
      })
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="pf-wrap">

    <!-- Header -->
    <div class="pf-head">
      <div class="pf-head-inner">
        <div class="pf-head-icon">
          <span class="material-symbols-outlined">{{ isEdit ? 'edit' : 'add_box' }}</span>
        </div>
        <div>
          <h2 class="pf-title">{{ isEdit ? 'Edit Product' : 'New Product' }}</h2>
          <p class="pf-subtitle">{{ isEdit ? 'Update product specifications below.' : 'Fill in the details to catalog a new product.' }}</p>
        </div>
      </div>
      <button class="pf-close" @click="$emit('close')" aria-label="Close">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <!-- Form -->
    <form class="pf-body" @submit.prevent="submitForm" novalidate>

      <!-- Error banner -->
      <div v-if="error" class="pf-error-banner">
        <span class="material-symbols-outlined">error</span>
        {{ error }}
      </div>

      <!-- Product Name -->
      <div class="pf-field" :class="{ 'has-error': errors.name }">
        <label class="pf-label" for="product-name">
          <span class="material-symbols-outlined">inventory_2</span>
          Product Name
        </label>
        <input
          id="product-name"
          v-model="form.name"
          class="pf-input"
          placeholder="e.g. Wireless Mouse G-Pro"
          @input="errors.name = ''"
        />
        <p v-if="errors.name" class="pf-err-msg">{{ errors.name }}</p>
      </div>

      <!-- SKU -->
      <div class="pf-field" :class="{ 'has-error': errors.sku }">
        <label class="pf-label" for="product-sku">
          <span class="material-symbols-outlined">barcode</span>
          SKU Code
        </label>
        <input
          id="product-sku"
          v-model="form.sku"
          class="pf-input"
          placeholder="e.g. WM-001"
          @input="errors.sku = ''"
        />
        <p v-if="errors.sku" class="pf-err-msg">{{ errors.sku }}</p>
      </div>

      <!-- Prices Row -->
      <div class="pf-row">
        <div class="pf-field" :class="{ 'has-error': errors.cost_price }">
          <label class="pf-label" for="product-cost">
            <span class="material-symbols-outlined">payments</span>
            Cost Price
          </label>
          <input
            id="product-cost"
            v-model="form.cost_price"
            type="number"
            step="0.01"
            class="pf-input"
            placeholder="0.00"
            @input="errors.cost_price = ''"
          />
          <p v-if="errors.cost_price" class="pf-err-msg">{{ errors.cost_price }}</p>
        </div>

        <div class="pf-field" :class="{ 'has-error': errors.sale_price }">
          <label class="pf-label" for="product-sale">
            <span class="material-symbols-outlined">sell</span>
            Sale Price
          </label>
          <input
            id="product-sale"
            v-model="form.sale_price"
            type="number"
            step="0.01"
            class="pf-input"
            placeholder="0.00"
            @input="errors.sale_price = ''"
          />
          <p v-if="errors.sale_price" class="pf-err-msg">{{ errors.sale_price }}</p>
        </div>
      </div>

      <!-- Tax & Status Row -->
      <div class="pf-row">
        <div class="pf-field" :class="{ 'has-error': errors.tax_percentage }">
          <label class="pf-label" for="product-tax">
            <span class="material-symbols-outlined">percentage</span>
            Tax (%)
          </label>
          <input
            id="product-tax"
            v-model="form.tax_percentage"
            type="number"
            step="0.01"
            class="pf-input"
            placeholder="0.00"
            @input="errors.tax_percentage = ''"
          />
          <p v-if="errors.tax_percentage" class="pf-err-msg">{{ errors.tax_percentage }}</p>
        </div>

        <div class="pf-field">
          <label class="pf-label" for="product-status">
            <span class="material-symbols-outlined">toggle_on</span>
            Status
          </label>
          <div class="pf-select-wrap">
            <select
              id="product-status"
              v-model="form.status"
              class="pf-input pf-select"
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            <span class="material-symbols-outlined pf-select-arrow">expand_more</span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="pf-actions">
        <button type="button" class="pf-btn-cancel" @click="$emit('close')">Cancel</button>
        <button type="submit" class="pf-btn-save" :disabled="saving" id="product-save-btn">
          <span v-if="saving" class="pf-spinner"></span>
          <span v-else class="material-symbols-outlined">{{ isEdit ? 'save' : 'add' }}</span>
          {{ saving ? 'Saving…' : (isEdit ? 'Update Product' : 'Create Product') }}
        </button>
      </div>

    </form>
  </div>
</template>

<style scoped>
/* ════════════════════════════════
   WRAPPER
   Adopted from BranchForm style
════════════════════════════════ */
.pf-wrap {
  background: #fff;
  font-family: 'Inter', system-ui, sans-serif;
}

/* ════════════════════════════════
   HEADER
════════════════════════════════ */
.pf-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 1.5rem 1.75rem 1.25rem;
  background: linear-gradient(135deg, #5D4037, #795548);
}
.pf-head-inner {
  display: flex; align-items: center; gap: 0.85rem;
}
.pf-head-icon {
  width: 42px; height: 42px; border-radius: 12px;
  background: rgba(255,255,255,0.18);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.pf-head-icon .material-symbols-outlined { font-size: 22px; color: #fff; }

.pf-title {
  font-size: 1.1rem; font-weight: 800; color: #fff;
  margin: 0 0 0.15rem; letter-spacing: -0.02em;
}
.pf-subtitle {
  font-size: 0.78rem; color: rgba(255,255,255,0.65); margin: 0;
}
.pf-close {
  background: rgba(255,255,255,0.15); border: none;
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #fff; transition: background 0.2s;
  flex-shrink: 0; margin-top: 2px;
}
.pf-close:hover { background: rgba(255,255,255,0.28); }
.pf-close .material-symbols-outlined { font-size: 18px; }

/* ════════════════════════════════
   FORM BODY
════════════════════════════════ */
.pf-body {
  padding: 1.5rem 1.75rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.pf-error-banner {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #FFF5F5; border: 1px solid #fca5a5;
  border-radius: 10px; color: #dc2626; font-size: 0.83rem;
}
.pf-error-banner .material-symbols-outlined { font-size: 18px; }

/* ════════════════════════════════
   FIELD
════════════════════════════════ */
.pf-field { display: flex; flex-direction: column; gap: 0.4rem; }

.pf-label {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.82rem; font-weight: 600; color: #5D4037;
}
.pf-label .material-symbols-outlined { font-size: 16px; color: #8D6E63; }

.pf-input {
  width: 100%;
  padding: 0.7rem 0.95rem;
  background: #FAF8F6;
  border: 1.5px solid #EDE8E4;
  border-radius: 11px;
  font-size: 0.9rem;
  color: #3E2723;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s, background 0.2s;
  box-sizing: border-box;
}
.pf-input:focus {
  border-color: #A1887F;
  background: #fff;
}
.pf-input::placeholder { color: #BCAAA4; }

.has-error .pf-input { border-color: #f87171; background: #fff; }
.pf-err-msg { font-size: 0.76rem; color: #dc2626; margin: 0; }

.pf-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

/* Select */
.pf-select-wrap { position: relative; }
.pf-select { appearance: none; padding-right: 2.5rem; cursor: pointer; }
.pf-select-arrow {
  position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%);
  font-size: 20px; color: #A1887F; pointer-events: none;
}

/* ════════════════════════════════
   ACTIONS
════════════════════════════════ */
.pf-actions {
  display: flex; align-items: center; gap: 0.75rem;
  margin-top: 0.5rem;
}
.pf-btn-cancel {
  flex: 1;
  padding: 0.7rem;
  background: #FAF8F6;
  border: 1.5px solid #EDE8E4;
  border-radius: 11px;
  font-size: 0.88rem; font-weight: 600;
  color: #795548; cursor: pointer;
  font-family: inherit; transition: all 0.2s;
}
.pf-btn-cancel:hover { background: #fff; border-color: #D7CCC8; }

.pf-btn-save {
  flex: 2;
  display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
  padding: 0.7rem 1.25rem;
  background: linear-gradient(135deg, #5D4037, #795548);
  color: #fff;
  border: none; border-radius: 11px;
  font-size: 0.88rem; font-weight: 700;
  cursor: pointer; font-family: inherit;
  box-shadow: 0 4px 14px rgba(93,64,55,0.25);
  transition: all 0.2s;
}
.pf-btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(93,64,55,0.3); }
.pf-btn-save:disabled { opacity: 0.65; cursor: not-allowed; }
.pf-btn-save .material-symbols-outlined { font-size: 18px; }

.pf-spinner {
  width: 16px; height: 16px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 480px) {
  .pf-row { grid-template-columns: 1fr; }
}
</style>