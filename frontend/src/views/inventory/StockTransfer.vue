<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import inventoryApi from '@/services/inventoryService'
import api from '@/services/api'
import LoadingScreen from '@/components/LoadingScreen.vue'

const router = useRouter()

const fromBranch = ref('')
const toBranch   = ref('')
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

const transfer = async () => {
  if (!fromBranch.value || !toBranch.value || !productId.value || quantity.value < 1) {
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
    router.push('/inventory')
  } catch (err) {
    const msg = err.response?.data?.message || 'Failed to transfer stock.'
    alert(msg)
  } finally {
    loading.value = false
  }
}

onMounted(fetchOptions)
</script>

<template>
  <div class="tr-page">
    
    <LoadingScreen v-if="fetching" :show="fetching" message="Configuring inventory…" />

    <!-- Header -->
    <div class="tr-header">
      <router-link to="/inventory" class="tr-back">
        <span class="material-symbols-outlined">arrow_back</span>
        Back to Inventory
      </router-link>
      <div class="tr-title-row">
        <div class="tr-icon">
          <span class="material-symbols-outlined">local_shipping</span>
        </div>
        <div>
          <h1 class="tr-title">Stock Transfer</h1>
          <p class="tr-subtitle">Move products from one branch to another securely</p>
        </div>
      </div>
    </div>

    <!-- Container -->
    <div class="tr-container">
      <form class="tr-form" @submit.prevent="transfer">
        
        <div v-if="error" class="tr-error">
          <span class="material-symbols-outlined">error</span>
          {{ error }}
        </div>

        <div class="tr-split">
          <!-- Source -->
          <div class="tr-field">
            <label class="tr-label">
              <span class="material-symbols-outlined">outbox</span>
              Source Branch
            </label>
            <div class="tr-select-wrap">
              <select v-model="fromBranch" class="tr-input tr-select" required>
                <option value="" disabled>— From —</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
              <span class="material-symbols-outlined tr-arrow">expand_more</span>
            </div>
          </div>

          <!-- Destination -->
          <div class="tr-field">
            <label class="tr-label">
              <span class="material-symbols-outlined">inbox</span>
              Destination
            </label>
            <div class="tr-select-wrap">
              <select v-model="toBranch" class="tr-input tr-select" required>
                <option value="" disabled>— To —</option>
                <option 
                  v-for="b in branches" 
                  :key="b.id" 
                  :value="b.id"
                  :disabled="b.id === fromBranch"
                >
                  {{ b.name }}
                </option>
              </select>
              <span class="material-symbols-outlined tr-arrow">expand_more</span>
            </div>
          </div>
        </div>

        <!-- Product -->
        <div class="tr-field">
          <label class="tr-label">
            <span class="material-symbols-outlined">inventory_2</span>
            Product to Move
          </label>
          <div class="tr-select-wrap">
            <select v-model="productId" class="tr-input tr-select" required>
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
            <span class="material-symbols-outlined tr-arrow">expand_more</span>
          </div>
        </div>

        <!-- Qty -->
        <div class="tr-field">
          <label class="tr-label">
            <span class="material-symbols-outlined">numbers</span>
            Transfer Quantity
          </label>
          <input 
            type="number" 
            v-model.number="quantity" 
            class="tr-input" 
            min="1" 
            placeholder="0"
            required
          />
        </div>

        <!-- Actions -->
        <div class="tr-actions">
          <button type="button" class="tr-btn-cancel" @click="router.push('/inventory')">Cancel</button>
          <button type="submit" class="tr-btn-save" :disabled="loading || !fromBranch || !toBranch || !productId">
            <span v-if="loading" class="tr-spinner"></span>
            <span v-else class="material-symbols-outlined">move_down</span>
            {{ loading ? 'Processing…' : 'Execute Transfer' }}
          </button>
        </div>
      </form>
    </div>

  </div>
</template>

<style scoped>
.tr-page {
  animation: pageIn 0.4s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Header */
.tr-header { margin-bottom: 2rem; }
.tr-back {
  display: inline-flex; align-items: center; gap: 0.4rem;
  color: #A1887F; text-decoration: none; font-size: 0.85rem; font-weight: 600;
  margin-bottom: 1.25rem; transition: color 0.2s;
}
.tr-back:hover { color: #5D4037; }
.tr-back .material-symbols-outlined { font-size: 18px; }

.tr-title-row { display: flex; align-items: center; gap: 1rem; }
.tr-icon {
  width: 52px; height: 52px; border-radius: 16px;
  background: #3E2723;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 20px rgba(62,39,35,0.22);
}
.tr-icon .material-symbols-outlined { color: #fff; font-size: 26px; }

.tr-title { font-size: 1.6rem; font-weight: 800; color: #3E2723; margin: 0; letter-spacing: -0.03em; }
.tr-subtitle { font-size: 0.85rem; color: #A1887F; margin: 0; }

/* Container */
.tr-container {
  max-width: 580px; background: #fff;
  border: 1.5px solid #E0D7D0; border-radius: 20px;
  box-shadow: 0 4px 24px rgba(93,64,55,0.06);
  overflow: hidden;
}

.tr-form { padding: 2rem; display: flex; flex-direction: column; gap: 1.25rem; }

.tr-error {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.75rem; background: #FFF5F5; border: 1px solid #fca5a5;
  border-radius: 10px; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.5rem;
}

.tr-split { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

.tr-field { display: flex; flex-direction: column; gap: 0.45rem; }
.tr-label {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.85rem; font-weight: 700; color: #5D4037;
}
.tr-label .material-symbols-outlined { font-size: 16px; color: #A1887F; }

.tr-input {
  width: 100%; padding: 0.75rem 1rem; background: #FAF8F6;
  border: 1.5px solid #EDE8E4; border-radius: 11px;
  font-size: 0.92rem; color: #3E2723; font-family: inherit;
  outline: none; transition: border-color 0.2s, background 0.2s;
  box-sizing: border-box;
}
.tr-input:focus { border-color: #A1887F; background: #fff; }

.tr-select-wrap { position: relative; }
.tr-select { appearance: none; padding-right: 2.5rem; cursor: pointer; }
.tr-arrow {
  position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%);
  color: #BCAAA4; pointer-events: none; font-size: 20px;
}

.tr-actions { display: flex; align-items: center; gap: 1rem; margin-top: 1rem; }
.tr-btn-cancel {
  flex: 1; padding: 0.75rem; background: #FAF8F6; border: 1.5px solid #EDE8E4;
  border-radius: 12px; font-size: 0.9rem; font-weight: 600; color: #795548;
  cursor: pointer; font-family: inherit; transition: all 0.2s;
}
.tr-btn-cancel:hover { background: #fff; border-color: #D7CCC8; }

.tr-btn-save {
  flex: 1.6; display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem;
  padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #5D4037, #795548);
  color: #fff; border: none; border-radius: 12px;
  font-size: 0.9rem; font-weight: 700; cursor: pointer;
  box-shadow: 0 4px 14px rgba(93,64,55,0.25);
  transition: all 0.2s; font-family: inherit;
}
.tr-btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(93,64,55,0.3); }
.tr-btn-save:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

.tr-spinner {
  width: 16px; height: 16px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 480px) { .tr-split { grid-template-columns: 1fr; } }
</style>