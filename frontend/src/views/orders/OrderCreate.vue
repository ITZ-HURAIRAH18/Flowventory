<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import inventoryService from '@/services/inventoryService'
import orderService from '@/services/orderService'
import LoadingScreen from '@/components/LoadingScreen.vue'

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

const fetchBranches = async () => {
  loadingBranches.value = true
  try {
    const res = await api.get('/my-branches')
    branches.value = res.data
  } catch (err) {
    error.value = 'Failed to load store registry.'
  } finally {
    loadingBranches.value = false
  }
}

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
    error.value = 'Failed to sync branch inventory.'
    products.value = []
  } finally {
    loadingProducts.value = false
  }
}

const addItem = () => items.value.push({ product_id: '', quantity: 1 })
const removeItem = (i) => items.value.splice(i, 1)

const getProduct = (id) => products.value.find(p => p.id === id)

const subtotal = computed(() => {
  return items.value.reduce((s, i) => {
    const p = getProduct(i.product_id)
    return s + (p ? (p.sale_price * i.quantity) : 0)
  }, 0)
})

const tax = computed(() => {
  return items.value.reduce((s, i) => {
    const p = getProduct(i.product_id)
    return s + (p ? (p.sale_price * i.quantity * (p.tax_percentage / 100)) : 0)
  }, 0)
})

const total = computed(() => subtotal.value + tax.value)
const validItems = computed(() => items.value.filter(i => i.product_id && i.quantity > 0))
const canSubmit = computed(() => branch_id.value && validItems.value.length > 0 && !submitting.value)

const submitOrder = async () => {
  error.value = null
  submitting.value = true
  try {
    await orderService.create({
      branch_id: branch_id.value,
      items: validItems.value
    })
    success.value = 'Order confirmed.'
    setTimeout(() => router.push('/dashboard'), 1200)
  } catch (e) {
    error.value = e.response?.data?.message || 'Checkout error.'
  } finally {
    submitting.value = false
  }
}

onMounted(fetchBranches)
</script>

<template>
  <div class="oc-page">
    <LoadingScreen v-if="loadingBranches || submitting" :show="true" :message="submitting ? 'Processing Transaction…' : 'Loading Sites…'" />

    <!-- ══ HEADER ══ -->
    <div class="oc-v2-header">
      <router-link to="/dashboard" class="oc-back">
        <span class="material-symbols-outlined">arrow_back</span>
        DASHBOARD
      </router-link>
      <div class="oc-title-row">
        <h1 class="oc-title">CREATE ORDER</h1>
        <div class="oc-badge">V2 ENTERPRISE</div>
      </div>
    </div>

    <!-- ══ ALERTS ══ -->
    <div v-if="error" class="oc-banner al-err">
      <span class="material-symbols-outlined">report</span>
      {{ error }}
      <button @click="error = null" class="al-close">×</button>
    </div>
    <div v-if="success" class="oc-banner al-ok">
      <span class="material-symbols-outlined">check_circle</span>
      {{ success }}
    </div>

    <!-- ══ REVERSED GRID ══ -->
    <div class="oc-layout">
      
      <!-- LEFT: FINAL SUMMARY (REVERTED DESIGN) -->
      <div class="oc-side">
        <div class="oc-card oc-sticky">
          <div class="oc-card-head">
            <span class="material-symbols-outlined">receipt_long</span>
            <h3>FINAL SUMMARY</h3>
          </div>

          <div class="sum-box">
            <div class="sum-row">
              <span class="sum-lbl">SKU COUNT</span>
              <span class="sum-val">{{ validItems.length }}</span>
            </div>
            <div class="sum-row">
              <span class="sum-lbl">NET TOTAL</span>
              <span class="sum-val">PKR {{ subtotal.toLocaleString() }}</span>
            </div>
            <div class="sum-row">
              <span class="sum-lbl">VAT / TAX</span>
              <span class="sum-val">PKR {{ tax.toLocaleString() }}</span>
            </div>
            <div class="sum-divider"></div>
            <div class="sum-row sum-main">
              <span class="sum-lbl">GRAND TOTAL</span>
              <span class="sum-val">PKR {{ total.toLocaleString() }}</span>
            </div>
          </div>

          <button class="btn-finalize" :disabled="!canSubmit" @click="submitOrder">
            <span class="material-symbols-outlined">check_circle</span>
            {{ submitting ? 'PROCESSING...' : 'CONFIRM ORDER' }}
          </button>
          <p class="oc-hint-footer">Automated stock deduction will follow confirmation.</p>
        </div>
      </div>

      <!-- RIGHT: ORDER FORM -->
      <div class="oc-main">
        <!-- Site Allocation -->
        <div class="oc-card">
          <div class="oc-card-head">
            <span class="material-symbols-outlined">business</span>
            <h3>BRANCH ALLOCATION</h3>
          </div>
          <div class="oc-select-wrap">
            <select v-model="branch_id" @change="onBranchChange" class="oc-input oc-select">
              <option value="" disabled>— SELECT ACTIVE BRANCH —</option>
              <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <span class="material-symbols-outlined oc-arrow">expand_more</span>
          </div>
        </div>

        <!-- Catalog Selection -->
        <div class="oc-card">
          <div class="oc-card-head">
            <span class="material-symbols-outlined">inventory</span>
            <h3>ORDER PARTICULARS</h3>
          </div>

          <div v-if="!branch_id" class="oc-empty-pad">
            <span class="material-symbols-outlined">info</span>
            SELECT A BRANCH ABOVE TO BEGIN TRANSACTION
          </div>

          <template v-else>
            <div class="oc-list">
              <div v-for="(item, idx) in items" :key="idx" class="oc-item-row">
                <div class="oc-item-cols">
                  <div class="oc-col-prod">
                    <label>PRODUCT SKU</label>
                    <div class="oc-select-wrap">
                      <select v-model="item.product_id" class="oc-input oc-select">
                        <option value="" disabled>— CHOOSE ITEM —</option>
                        <option 
                          v-for="p in products" 
                          :key="p.id" 
                          :value="p.id" 
                          :disabled="p.status === 'inactive' || p.stock < 1"
                        >
                          {{ p.name }} (STOCK: {{ p.stock }})
                        </option>
                      </select>
                      <span class="material-symbols-outlined oc-arrow">expand_more</span>
                    </div>
                  </div>
                  
                  <div class="oc-col-qty">
                    <label>QTY</label>
                    <input type="number" v-model.number="item.quantity" class="oc-input" min="1" />
                  </div>

                  <div class="oc-col-price">
                    <label>LINE TOTAL</label>
                    <div class="oc-line-price">
                      PKR {{ (getProduct(item.product_id)? (getProduct(item.product_id).sale_price * item.quantity) : 0).toLocaleString() }}
                    </div>
                  </div>

                  <button class="oc-btn-del" @click="removeItem(idx)" :disabled="items.length < 2">
                    <span class="material-symbols-outlined">close</span>
                  </button>
                </div>
                <!-- Mini info -->
                <div v-if="getProduct(item.product_id)" class="oc-item-details">
                  UNIT: PKR {{ getProduct(item.product_id).sale_price }} • TAX: {{ getProduct(item.product_id).tax_percentage }}%
                </div>
              </div>
            </div>

            <button class="oc-btn-add" @click="addItem">
              <span class="material-symbols-outlined">add</span>
              APPEND NEW LINE
            </button>
          </template>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.oc-page {
  animation: pageIn 0.5s cubic-bezier(0.16,1,0.3,1) both;
  background: #FCFAF9; min-height: 100vh; padding: 1.5rem;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Header */
.oc-v2-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; border-bottom: 1.5px solid #EDE8E4; padding-bottom: 1.5rem; }
.oc-back { display: flex; align-items: center; gap: 0.4rem; color: #8D6E63; text-decoration: none; font-size: 0.8rem; font-weight: 800; }
.oc-title-row { text-align: right; }
.oc-title { font-size: 1.5rem; font-weight: 900; color: #3E2723; margin: 0; letter-spacing: -0.02em; }
.oc-badge { font-size: 0.65rem; font-weight: 900; color: #fff; background: #3E2723; padding: 0.2rem 0.5rem; border-radius: 4px; display: inline-block; margin-top: 0.25rem; }

/* Banner */
.oc-banner { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.25rem; font-weight: 700; font-size: 0.85rem; }
.al-err { background: #fee2e2; border: 1.5px solid #fecaca; color: #b91c1c; }
.al-ok { background: #ecfdf5; border: 1.5px solid #d1fae5; color: #065f46; }
.al-close { margin-left: auto; border: none; background: none; font-size: 1.25rem; color: inherit; cursor: pointer; }

/* Reversed Layout */
.oc-layout { display: grid; grid-template-columns: 360px 1fr; gap: 1.5rem; align-items: start; }

.oc-card { background: #fff; border: 1.5px solid #E0D7D0; border-radius: 24px; padding: 1.5rem; box-shadow: 0 4px 20px rgba(93,64,55,0.04); margin-bottom: 1.25rem; }
.oc-card-head { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1.5rem; }
.oc-card-head .material-symbols-outlined { color: #8D6E63; font-size: 20px; }
.oc-card-head h3 { font-size: 1.1rem; font-weight: 850; color: #3E2723; margin: 0; }

.oc-input {
  width: 100%; padding: 0.8rem 1rem; border: 1.5px solid #D7CCC8; border-radius: 12px;
  background: #fff; font-family: inherit; font-size: 0.9rem; color: #3E2723; font-weight: 600; outline: none; transition: all 0.2s;
}
.oc-input:focus { border-color: #8D6E63; box-shadow: 0 0 0 4px rgba(141,110,99,0.06); }
.oc-select { appearance: none; cursor: pointer; }
.oc-select-wrap { position: relative; }
.oc-arrow { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); pointer-events: none; color: #A1887F; font-size: 20px; }

/* Left Summary Card (Reverted Style) */
.sum-box { margin-bottom: 1.5rem; }
.sum-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem; }
.sum-lbl { font-size: 0.8rem; font-weight: 800; color: #A1887F; text-transform: uppercase; letter-spacing: 0.05em; }
.sum-val { font-size: 1rem; font-weight: 850; color: #3E2723; }
.sum-divider { height: 1.5px; background: #EDE8E4; margin: 1rem 0; border: none; }
.sum-main .sum-lbl { font-size: 0.95rem; color: #3E2723; font-weight: 900; }
.sum-main .sum-val { font-size: 1.4rem; color: #3E2723; font-weight: 950; }

.btn-finalize {
  width: 100%; padding: 1rem; border: none; border-radius: 14px; background: #3E2723; color: #fff;
  font-size: 1rem; font-weight: 900; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.6rem;
  transition: all 0.2s; box-shadow: 0 4px 16px rgba(62,39,35,0.22);
}
.btn-finalize:hover:not(:disabled) { background: #5D4037; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(62,39,35,0.3); }
.btn-finalize:disabled { opacity: 0.5; transform: none; box-shadow: none; cursor: not-allowed; }
.oc-hint-footer { font-size: 0.7rem; color: #BCAAA4; text-align: center; margin-top: 1rem; font-weight: 600; }

.oc-sticky { position: sticky; top: 1.5rem; }

/* Right Form Style */
.oc-empty-pad { padding: 4rem 1rem; text-align: center; color: #BCAAA4; font-size: 0.85rem; font-weight: 800; border: 1.5px dashed #E0D7D0; border-radius: 12px; }
.oc-list { display: flex; flex-direction: column; gap: 1rem; }
.oc-item-row { padding-bottom: 1rem; border-bottom: 1px solid #FAF9F7; }
.oc-item-row:last-child { border-bottom: none; }

.oc-item-cols { display: flex; align-items: flex-end; gap: 1rem; }
.oc-item-cols label { display: block; font-size: 0.68rem; font-weight: 900; color: #8D6E63; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em; }
.oc-col-prod { flex: 1; }
.oc-col-qty { width: 90px; border-color: #D7CCC8; }
.oc-col-price { width: 140px; text-align: right; }
.oc-line-price { font-size: 1rem; font-weight: 850; color: #3E2723; padding: 0.65rem 0; }

.oc-btn-del { 
  width: 44px; height: 44px; border-radius: 12px; background: #fff; border: 1.5px solid #F8D7DA; color: #C62828; 
  display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;
}
.oc-btn-del:hover:not(:disabled) { background: #FFF5F5; border-color: #EF4444; }

.oc-item-details { display: flex; gap: 1.25rem; margin-top: 0.5rem; font-size: 0.72rem; font-weight: 750; color: #A1887F; background: #FAF9F7; padding: 0.3rem 0.6rem; border-radius: 6px; }

.oc-btn-add {
  width: 100%; padding: 0.8rem; border: 1.5px dashed #D7CCC8; border-radius: 14px; background: #FAF9F7; color: #8D6E63;
  font-weight: 900; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; transition: all 0.2s; margin-top: 1.5rem;
}
.oc-btn-add:hover { background: #fff; border-color: #8D6E63; color: #3E2723; }

@media (max-width: 1024px) {
  .oc-layout { grid-template-columns: 1fr; }
  .oc-side { order: 2; }
  .oc-main { order: 1; }
  .oc-sticky { position: static; }
}
</style>