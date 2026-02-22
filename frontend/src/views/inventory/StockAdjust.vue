<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import inventoryApi from '@/services/inventoryService'
import api from '@/services/api'
import LoadingScreen from '@/components/LoadingScreen.vue'

const router = useRouter()

const branchId   = ref('')
const productId  = ref('')
const quantity   = ref(0)
const loading    = ref(false)
const fetching   = ref(true)
const error      = ref('')

const branches = ref([])
const products = ref([])

const fetchOptions = async () => {
  fetching.value = true
  try {
    const [branchRes, productRes] = await Promise.all([
      api.get('/my-branches'),
      api.get('/all-products')
    ])
    branches.value = branchRes.data
    products.value = productRes.data.data || productRes.data
  } catch (err) {
    error.value = 'Failed to load options. Please refresh.'
  } finally {
    fetching.value = false
  }
}

const adjustStock = async () => {
  if (!branchId.value || !productId.value) {
    return
  }
  loading.value = true
  try {
    await inventoryApi.adjust({
      branch_id: branchId.value,
      product_id: productId.value,
      quantity: quantity.value
    })
    router.push('/inventory')
  } catch (err) {
    const msg = err.response?.data?.message || 'Failed to adjust stock.'
    alert(msg)
  } finally {
    loading.value = false
  }
}

onMounted(fetchOptions)
</script>

<template>
  <div class="ad-page">
    
    <LoadingScreen v-if="fetching" :show="fetching" message="Configuring inventory…" />

    <!-- Header -->
    <div class="ad-header">
      <router-link to="/inventory" class="ad-back">
        <span class="material-symbols-outlined">arrow_back</span>
        Back to Inventory
      </router-link>
      <div class="ad-title-row">
        <div class="ad-icon">
          <span class="material-symbols-outlined">tune</span>
        </div>
        <div>
          <h1 class="ad-title">Adjust Stock</h1>
          <p class="ad-subtitle">Modify inventory levels manually for corrections or shrinkage</p>
        </div>
      </div>
    </div>

    <!-- Container -->
    <div class="ad-container">
      <form class="ad-form" @submit.prevent="adjustStock">
        
        <div v-if="error" class="ad-error">
          <span class="material-symbols-outlined">error</span>
          {{ error }}
        </div>

        <!-- Branch -->
        <div class="ad-field">
          <label class="ad-label">
            <span class="material-symbols-outlined">store_mall_directory</span>
            Target Branch
          </label>
          <div class="ad-select-wrap">
            <select v-model="branchId" class="ad-input ad-select" required>
              <option value="" disabled>— Select active branch —</option>
              <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <span class="material-symbols-outlined ad-arrow">expand_more</span>
          </div>
        </div>

        <!-- Product -->
        <div class="ad-field">
          <label class="ad-label">
            <span class="material-symbols-outlined">inventory_2</span>
            Select Product
          </label>
          <div class="ad-select-wrap">
            <select v-model="productId" class="ad-input ad-select" required>
              <option value="" disabled>— Select product —</option>
              <option 
                v-for="p in products" 
                :key="p.id" 
                :value="p.id"
                :disabled="p.status === 'inactive'"
              >
                {{ p.name }} {{ p.sku ? `(${p.sku})` : '' }}
              </option>
            </select>
            <span class="material-symbols-outlined ad-arrow">expand_more</span>
          </div>
        </div>

        <!-- Adjustment Qty -->
        <div class="ad-field">
          <label class="ad-label">
            <span class="material-symbols-outlined">swap_vert</span>
            Quantity Adjustment
          </label>
          <input 
            type="number" 
            v-model.number="quantity" 
            class="ad-input" 
            placeholder="e.g. +10 or -5"
            required
          />
          <p class="ad-hint">
            <span class="material-symbols-outlined">info</span>
            Use <b>positive</b> values to add, <b>negative</b> values to subtract.
          </p>
        </div>

        <!-- Actions -->
        <div class="ad-actions">
          <button type="button" class="ad-btn-cancel" @click="router.push('/inventory')">Cancel</button>
          <button type="submit" class="ad-btn-save" :disabled="loading || !branchId || !productId">
            <span v-if="loading" class="ad-spinner"></span>
            <span v-else class="material-symbols-outlined">auto_fix_high</span>
            {{ loading ? 'Updating…' : 'Apply Adjustment' }}
          </button>
        </div>
      </form>
    </div>

  </div>
</template>

<style scoped>
@import "../../styles/views/StockAdjust.css";
</style>