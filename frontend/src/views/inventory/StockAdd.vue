<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import inventoryApi from '@/services/inventoryService'
import api from '@/services/api'
import LoadingScreen from '@/components/LoadingScreen.vue'

const router = useRouter()

const branchId   = ref('')
const productId  = ref('')
const quantity   = ref(1)
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

const submit = async () => {
  if (!branchId.value || !productId.value || quantity.value < 1) {
    return
  }
  loading.value = true
  try {
    await inventoryApi.add({
      branch_id: branchId.value,
      product_id: productId.value,
      quantity: quantity.value
    })
    // Simple redirect on success
    router.push('/inventory')
  } catch (err) {
    alert('Failed to add stock. Please try again.')
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
        
        <div v-if="error" class="sa-error">
          <span class="material-symbols-outlined">error</span>
          {{ error }}
        </div>

        <!-- Branch Selection -->
        <div class="sa-field">
          <label class="sa-label">
            <span class="material-symbols-outlined">store_mall_directory</span>
            Target Branch
          </label>
          <div class="sa-select-wrap">
            <select v-model="branchId" class="sa-input sa-select" required>
              <option value="" disabled>— Select active branch —</option>
              <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <span class="material-symbols-outlined sa-arrow">expand_more</span>
          </div>
        </div>

        <!-- Product Selection -->
        <div class="sa-field">
          <label class="sa-label">
            <span class="material-symbols-outlined">inventory_2</span>
            Select Product
          </label>
          <div class="sa-select-wrap">
            <select v-model="productId" class="sa-input sa-select" required>
              <option value="" disabled>— Select product from catalog —</option>
              <option 
                v-for="p in products" 
                :key="p.id" 
                :value="p.id"
                :disabled="p.status === 'inactive'"
              >
                {{ p.name }} {{ p.sku ? `(${p.sku})` : '' }}
              </option>
            </select>
            <span class="material-symbols-outlined sa-arrow">expand_more</span>
          </div>
        </div>

        <!-- Quantity -->
        <div class="sa-field">
          <label class="sa-label">
            <span class="material-symbols-outlined">numbers</span>
            Quantity to Add
          </label>
          <input 
            type="number" 
            v-model.number="quantity" 
            class="sa-input" 
            min="1" 
            placeholder="e.g. 50"
            required
          />
        </div>

        <!-- Actions -->
        <div class="sa-actions">
          <button type="button" class="sa-btn-cancel" @click="router.push('/inventory')">Cancel</button>
          <button type="submit" class="sa-btn-save" :disabled="loading || !branchId || !productId">
            <span v-if="loading" class="sa-spinner"></span>
            <span v-else class="material-symbols-outlined">add_circle</span>
            {{ loading ? 'Updating…' : 'Add Stock Items' }}
          </button>
        </div>
      </form>
    </div>

  </div>
</template>

<style scoped>
.sa-page {
  animation: pageIn 0.4s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Header */
.sa-header { margin-bottom: 2rem; }
.sa-back {
  display: inline-flex; align-items: center; gap: 0.4rem;
  color: #A1887F; text-decoration: none; font-size: 0.85rem; font-weight: 600;
  margin-bottom: 1.25rem; transition: color 0.2s;
}
.sa-back:hover { color: #5D4037; }
.sa-back .material-symbols-outlined { font-size: 18px; }

.sa-title-row { display: flex; align-items: center; gap: 1rem; }
.sa-icon {
  width: 52px; height: 52px; border-radius: 16px;
  background: #3E2723;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 20px rgba(62,39,35,0.22);
}
.sa-icon .material-symbols-outlined { color: #fff; font-size: 26px; }

.sa-title { font-size: 1.6rem; font-weight: 800; color: #3E2723; margin: 0; letter-spacing: -0.03em; }
.sa-subtitle { font-size: 0.85rem; color: #A1887F; margin: 0; }

/* Container */
.sa-container {
  max-width: 540px; background: #fff;
  border: 1.5px solid #E0D7D0; border-radius: 20px;
  box-shadow: 0 4px 24px rgba(93,64,55,0.06);
  overflow: hidden;
}

.sa-form { padding: 2rem; display: flex; flex-direction: column; gap: 1.25rem; }

.sa-error {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.75rem; background: #FFF5F5; border: 1px solid #fca5a5;
  border-radius: 10px; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.5rem;
}

.sa-field { display: flex; flex-direction: column; gap: 0.45rem; }
.sa-label {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.85rem; font-weight: 700; color: #5D4037;
}
.sa-label .material-symbols-outlined { font-size: 16px; color: #A1887F; }

.sa-input {
  width: 100%; padding: 0.75rem 1rem; background: #FAF8F6;
  border: 1.5px solid #EDE8E4; border-radius: 11px;
  font-size: 0.92rem; color: #3E2723; font-family: inherit;
  outline: none; transition: border-color 0.2s, background 0.2s;
  box-sizing: border-box;
}
.sa-input:focus { border-color: #A1887F; background: #fff; }

.sa-select-wrap { position: relative; }
.sa-select { appearance: none; padding-right: 2.5rem; cursor: pointer; }
.sa-arrow {
  position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%);
  color: #BCAAA4; pointer-events: none; font-size: 20px;
}

.sa-actions { display: flex; align-items: center; gap: 1rem; margin-top: 1rem; }
.sa-btn-cancel {
  flex: 1; padding: 0.75rem; background: #FAF8F6; border: 1.5px solid #EDE8E4;
  border-radius: 12px; font-size: 0.9rem; font-weight: 600; color: #795548;
  cursor: pointer; font-family: inherit; transition: all 0.2s;
}
.sa-btn-cancel:hover { background: #fff; border-color: #D7CCC8; }

.sa-btn-save {
  flex: 1.6; display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem;
  padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #5D4037, #795548);
  color: #fff; border: none; border-radius: 12px;
  font-size: 0.9rem; font-weight: 700; cursor: pointer;
  box-shadow: 0 4px 14px rgba(93,64,55,0.25);
  transition: all 0.2s; font-family: inherit;
}
.sa-btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(93,64,55,0.3); }
.sa-btn-save:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

.sa-spinner {
  width: 16px; height: 16px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>