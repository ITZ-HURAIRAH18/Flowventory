<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import inventoryService from '@/services/inventoryService'
import orderService from '@/services/orderService'

// UI Components
import LoadingScreen from '@/components/LoadingScreen.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorBanner from '@/components/ui/ErrorBanner.vue'

const router = useRouter()

const branches = ref([])
const products = ref([])
const branch_id = ref('')
const items = ref([{ product_id: '', quantity: 1 }])

const loadingBranches = ref(false)
const loadingProducts = ref(false)
const submitting = ref(false)
const globalError = ref('')
const success = ref('')

const fetchBranches = async () => {
  loadingBranches.value = true
  try {
    const res = await api.get('/my-branches')
    branches.value = res.data.map(b => ({ label: b.name, value: b.id }))
  } catch (err) {
    globalError.value = 'Failed to load store registry.'
  } finally {
    loadingBranches.value = false
  }
}

const onBranchChange = async () => {
  globalError.value = ''
  if (!branch_id.value) {
    products.value = []
    return
  }
  loadingProducts.value = true
  try {
    const res = await inventoryService.getProductsByBranch(branch_id.value)
    products.value = res.data.map(p => ({
      ...p,
      label: `${p.name} (STOCK: ${p.stock})`,
      value: p.id,
      disabled: p.status === 'inactive' || p.stock < 1
    }))
    items.value = [{ product_id: '', quantity: 1 }]
  } catch (err) {
    globalError.value = 'Failed to sync branch inventory.'
    products.value = []
  } finally {
    loadingProducts.value = false
  }
}

const addItem = () => items.value.push({ product_id: '', quantity: 1 })
const removeItem = (i) => items.value.splice(i, 1)

const getProduct = (id) => products.value.find(p => p.value === id)

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
  globalError.value = ''
  submitting.value = true
  try {
    await orderService.create({
      branch_id: branch_id.value,
      items: validItems.value
    })
    success.value = 'Order confirmed.'
    setTimeout(() => router.push('/dashboard'), 1200)
  } catch (e) {
    globalError.value = e.response?.data?.message || 'Checkout error.'
  } finally {
    submitting.value = false
  }
}

onMounted(fetchBranches)
</script>

<template>
  <div class="oc-page">
    <LoadingScreen 
      v-if="loadingBranches || submitting" 
      :show="true" 
      :message="submitting ? 'Processing Transaction…' : 'Loading Sites…'" 
    />

    <!-- Header -->
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

    <ErrorBanner :show="!!globalError" :error="globalError" />
    
    <div v-if="success" class="oc-banner al-ok">
      <span class="material-symbols-outlined">check_circle</span>
      {{ success }}
    </div>

    <!-- Layout -->
    <div class="oc-layout">
      
      <!-- LEFT: Summary -->
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

          <div class="btn-finalize-wrap">
            <BaseButton
              fullWidth
              variant="primary"
              :disabled="!canSubmit"
              :loading="submitting"
              icon="check_circle"
              @click="submitOrder"
            >
              {{ submitting ? 'PROCESSING...' : 'CONFIRM ORDER' }}
            </BaseButton>
          </div>
          <p class="oc-hint-footer">Automated stock deduction will follow confirmation.</p>
        </div>
      </div>

      <!-- RIGHT: Order Form -->
      <div class="oc-main">
        <!-- Site Allocation -->
        <div class="oc-card">
          <div class="oc-card-head">
            <span class="material-symbols-outlined">business</span>
            <h3>BRANCH ALLOCATION</h3>
          </div>
          <BaseSelect
            v-model="branch_id"
            :options="branches"
            placeholder="— SELECT ACTIVE BRANCH —"
            icon="business"
            @change="onBranchChange"
          />
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
                    <BaseSelect
                      label="PRODUCT SKU"
                      v-model="item.product_id"
                      :options="products"
                      placeholder="— CHOOSE ITEM —"
                      icon="inventory_2"
                    />
                  </div>
                  
                  <div class="oc-col-qty">
                    <BaseInput
                      type="number"
                      label="QTY"
                      v-model.number="item.quantity"
                      min="1"
                    />
                  </div>

                  <div class="oc-col-price">
                    <span class="oc-line-price-label">LINE TOTAL</span>
                    <div class="oc-line-price-val">
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
@import "@/styles/views/OrderCreate.css";
</style>