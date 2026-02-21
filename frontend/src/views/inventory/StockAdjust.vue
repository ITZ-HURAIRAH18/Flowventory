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
.ad-page {
  animation: pageIn 0.4s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Header */
.ad-header { margin-bottom: 2rem; }
.ad-back {
  display: inline-flex; align-items: center; gap: 0.4rem;
  color: #A1887F; text-decoration: none; font-size: 0.85rem; font-weight: 600;
  margin-bottom: 1.25rem; transition: color 0.2s;
}
.ad-back:hover { color: #5D4037; }
.ad-back .material-symbols-outlined { font-size: 18px; }

.ad-title-row { display: flex; align-items: center; gap: 1rem; }
.ad-icon {
  width: 52px; height: 52px; border-radius: 16px;
  background: #3E2723;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 20px rgba(62,39,35,0.22);
}
.ad-icon .material-symbols-outlined { color: #fff; font-size: 26px; }

.ad-title { font-size: 1.6rem; font-weight: 800; color: #3E2723; margin: 0; letter-spacing: -0.03em; }
.ad-subtitle { font-size: 0.85rem; color: #A1887F; margin: 0; }

/* Container */
.ad-container {
  max-width: 540px; background: #fff;
  border: 1.5px solid #E0D7D0; border-radius: 20px;
  box-shadow: 0 4px 24px rgba(93,64,55,0.06);
  overflow: hidden;
}

.ad-form { padding: 2rem; display: flex; flex-direction: column; gap: 1.25rem; }

.ad-error {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.75rem; background: #FFF5F5; border: 1px solid #fca5a5;
  border-radius: 10px; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.5rem;
}

.ad-field { display: flex; flex-direction: column; gap: 0.45rem; }
.ad-label {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.85rem; font-weight: 700; color: #5D4037;
}
.ad-label .material-symbols-outlined { font-size: 16px; color: #A1887F; }

.ad-input {
  width: 100%; padding: 0.75rem 1rem; background: #FAF8F6;
  border: 1.5px solid #EDE8E4; border-radius: 11px;
  font-size: 0.92rem; color: #3E2723; font-family: inherit;
  outline: none; transition: border-color 0.2s, background 0.2s;
  box-sizing: border-box;
}
.ad-input:focus { border-color: #A1887F; background: #fff; }

.ad-hint { display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #A1887F; margin: 0; }
.ad-hint .material-symbols-outlined { font-size: 14px; }

.ad-select-wrap { position: relative; }
.ad-select { appearance: none; padding-right: 2.5rem; cursor: pointer; }
.ad-arrow {
  position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%);
  color: #BCAAA4; pointer-events: none; font-size: 20px;
}

.ad-actions { display: flex; align-items: center; gap: 1rem; margin-top: 1rem; }
.ad-btn-cancel {
  flex: 1; padding: 0.75rem; background: #FAF8F6; border: 1.5px solid #EDE8E4;
  border-radius: 12px; font-size: 0.9rem; font-weight: 600; color: #795548;
  cursor: pointer; font-family: inherit; transition: all 0.2s;
}
.ad-btn-cancel:hover { background: #fff; border-color: #D7CCC8; }

.ad-btn-save {
  flex: 1.6; display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem;
  padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #5D4037, #795548);
  color: #fff; border: none; border-radius: 12px;
  font-size: 0.9rem; font-weight: 700; cursor: pointer;
  box-shadow: 0 4px 14px rgba(93,64,55,0.25);
  transition: all 0.2s; font-family: inherit;
}
.ad-btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(93,64,55,0.3); }
.ad-btn-save:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

.ad-spinner {
  width: 16px; height: 16px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>