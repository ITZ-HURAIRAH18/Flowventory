<script setup>
import { reactive, ref, computed } from 'vue'
import productService from '@/services/productService'

// UI Components
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorBanner from '@/components/ui/ErrorBanner.vue'

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

const saving      = ref(false)
const globalError = ref('')
const fieldErrors = reactive({ 
  name: '', 
  sku: '', 
  cost_price: '', 
  sale_price: '', 
  tax_percentage: '' 
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const validate = () => {
  fieldErrors.name           = form.name.trim() ? '' : 'Product name is required.'
  fieldErrors.sku            = form.sku.trim() ? '' : 'SKU is required.'
  fieldErrors.cost_price     = form.cost_price !== '' ? '' : 'Cost price is required.'
  fieldErrors.sale_price     = form.sale_price !== '' ? '' : 'Sale price is required.'
  fieldErrors.tax_percentage = form.tax_percentage !== '' ? '' : 'Tax percentage is required.'
  
  return !fieldErrors.name && !fieldErrors.sku && !fieldErrors.cost_price && !fieldErrors.sale_price && !fieldErrors.tax_percentage
}

const submitForm = async () => {
  if (!validate()) return
  saving.value = true
  globalError.value = ''
  
  try {
    if (isEdit.value) {
      await productService.update(props.productObject.id, form)
    } else {
      await productService.create(form)
    }
    emit('saved')
    emit('close')
  } catch (err) {
    globalError.value = err?.response?.data?.message || 'An error occurred. Please try again.'
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const serverErrors = err.response.data.errors
      Object.keys(serverErrors).forEach(key => {
        if (fieldErrors.hasOwnProperty(key)) fieldErrors[key] = serverErrors[key][0]
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

      <ErrorBanner :show="!!globalError" :error="globalError" />

      <BaseInput
        label="Product Name"
        v-model="form.name"
        icon="inventory_2"
        placeholder="e.g. Wireless Mouse G-Pro"
        :error="fieldErrors.name"
        @input="fieldErrors.name = ''"
        required
      />

      <BaseInput
        label="SKU Code"
        v-model="form.sku"
        icon="barcode"
        placeholder="e.g. WM-001"
        :error="fieldErrors.sku"
        @input="fieldErrors.sku = ''"
        required
      />

      <div class="pf-row">
        <BaseInput
          type="number"
          label="Cost Price"
          v-model="form.cost_price"
          icon="payments"
          placeholder="0.00"
          step="0.01"
          :error="fieldErrors.cost_price"
          @input="fieldErrors.cost_price = ''"
          required
        />

        <BaseInput
          type="number"
          label="Sale Price"
          v-model="form.sale_price"
          icon="sell"
          placeholder="0.00"
          step="0.01"
          :error="fieldErrors.sale_price"
          @input="fieldErrors.sale_price = ''"
          required
        />
      </div>

      <div class="pf-row">
        <BaseInput
          type="number"
          label="Tax (%)"
          v-model="form.tax_percentage"
          icon="percentage"
          placeholder="0.00"
          step="0.01"
          :error="fieldErrors.tax_percentage"
          @input="fieldErrors.tax_percentage = ''"
          required
        />

        <BaseSelect
          label="Status"
          v-model="form.status"
          icon="toggle_on"
          :options="statusOptions"
          required
        />
      </div>

      <!-- Actions -->
      <div class="pf-actions">
        <BaseButton variant="secondary" @click="$emit('close')">Cancel</BaseButton>
        <BaseButton 
          type="submit" 
          :loading="saving" 
          :icon="isEdit ? 'save' : 'add'"
          id="product-save-btn"
        >
          {{ isEdit ? 'Update Product' : 'Create Product' }}
        </BaseButton>
      </div>

    </form>
  </div>
</template>

<style scoped>
@import "@/styles/views/ProductForm.css";
</style>