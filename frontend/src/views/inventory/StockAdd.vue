<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import inventoryApi from '@/services/inventoryService'
import api from '@/services/api'

// UI Components
import LoadingScreen from '@/components/LoadingScreen.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorBanner from '@/components/ui/ErrorBanner.vue'

const router = useRouter()

const branchId   = ref('')
const productId  = ref('')
const quantity   = ref(1)
const loading    = ref(false)
const fetching   = ref(true)
const globalError = ref('')

const branches = ref([])
const products = ref([])

const fetchOptions = async () => {
  fetching.value = true
  try {
    const [branchRes, productRes] = await Promise.all([
      api.get('/my-branches'),
      api.get('/all-products')
    ])
    branches.value = branchRes.data.map(b => ({ label: b.name, value: b.id }))
    const rawProducts = productRes.data.data || productRes.data
    products.value = rawProducts.map(p => ({ 
      label: `${p.name} ${p.sku ? `(${p.sku})` : ''}`, 
      value: p.id,
      disabled: p.status === 'inactive'
    }))
  } catch (err) {
    globalError.value = 'Failed to load options. Please refresh.'
  } finally {
    fetching.value = false
  }
}

const submit = async () => {
  if (!branchId.value || !productId.value || quantity.value < 1) {
    return
  }
  loading.value = true
  globalError.value = ''
  try {
    await inventoryApi.add({
      branch_id: branchId.value,
      product_id: productId.value,
      quantity: quantity.value
    })
    router.push('/inventory')
  } catch (err) {
    globalError.value = err.response?.data?.message || 'Failed to add stock.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchOptions)
</script>

<template>
  <div class="sa-page">
    
    <LoadingScreen v-if="fetching" :show="fetching" message="Configuring inventory…" />

    <!-- Header -->
    <div class="sa-header">
      <router-link to="/inventory" class="sa-back">
        <span class="material-symbols-outlined">arrow_back</span>
        Back to Inventory
      </router-link>
      <div class="sa-title-row">
        <div class="sa-icon">
          <span class="material-symbols-outlined">add_box</span>
        </div>
        <div>
          <h1 class="sa-title">Add Stock</h1>
          <p class="sa-subtitle">Increase inventory levels for a specific branch</p>
        </div>
      </div>
    </div>

    <!-- Form Container -->
    <div class="sa-container">
      <form class="sa-form" @submit.prevent="submit">
        
        <ErrorBanner :show="!!globalError" :error="globalError" />

        <BaseSelect
          label="Target Branch"
          v-model="branchId"
          icon="store_mall_directory"
          placeholder="— Select active branch —"
          :options="branches"
          required
        />

        <BaseSelect
          label="Select Product"
          v-model="productId"
          icon="inventory_2"
          placeholder="— Select product from catalog —"
          :options="products"
          required
        />

        <BaseInput
          type="number"
          label="Quantity to Add"
          v-model.number="quantity"
          icon="numbers"
          min="1"
          placeholder="e.g. 50"
          required
        />

        <!-- Actions -->
        <div class="sa-actions">
          <BaseButton variant="secondary" @click="router.push('/inventory')">Cancel</BaseButton>
          <BaseButton 
            type="submit" 
            :loading="loading" 
            :disabled="!branchId || !productId"
            icon="add_circle"
            id="sa-save-btn"
          >
            Add Stock Items
          </BaseButton>
        </div>
      </form>
    </div>

  </div>
</template>

<style scoped>
@import "../../styles/views/StockAdd.css";
</style>