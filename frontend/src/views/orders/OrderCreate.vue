<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import inventoryService from '@/services/inventoryService'
import orderService from '@/services/orderService'
import { useAppData } from '@/composables/useAppData'
import { toast } from '@/composables/useToast'

// UI Components
import LoadingScreen from '@/components/LoadingScreen.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorBanner from '@/components/ui/ErrorBanner.vue'

const router = useRouter()
const { fetchProducts } = useAppData()

const branches = ref([])
const productOptions = ref([])
const productMap = ref({})
const branch_id = ref('')

// Separate reactive storage for product selections and quantities
const productSelections = ref({})  // key: item.id, value: product_id
const quantities = ref({})         // key: item.id, value: quantity

const items = ref([{ id: 1 }])
let nextItemId = ref(2)

const loadingBranches = ref(false)
const loadingProducts = ref(false)
const submitting = ref(false)
const globalError = ref('')
const success = ref('')

// Ensure items always have their selections/quantities initialized
watch(items, (newItems) => {
  newItems.forEach(item => {
    if (!(item.id in productSelections.value)) {
      productSelections.value[item.id] = ''
    }
    if (!(item.id in quantities.value)) {
      quantities.value[item.id] = 1
    }
  })
}, { deep: true })

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
    productOptions.value = []
    productMap.value = {}
    return
  }
  
  loadingProducts.value = true
  try {
    const res = await inventoryService.getProductsByBranch(branch_id.value, 1, 100)
    const rawList = res.data.data || res.data || []
    
    productOptions.value = []
    productMap.value = {}
    
    ;(Array.isArray(rawList) ? rawList : [])
      .filter(item => item && item.product) 
      .forEach(item => {
        const p = item.product
        const productId = p.id
        
        productMap.value[productId] = {
          id: productId,
          name: p.name,
          sku: p.sku,
          sale_price: parseFloat(p.sale_price) || 0,
          cost_price: parseFloat(p.cost_price) || 0,
          tax_percentage: parseFloat(p.tax_percentage) || 0,
          status: p.status,
          stock: item.quantity
        }
        
        productOptions.value.push({
          label: `${p.name} (${p.sku}) - Stock: ${item.quantity}`,
          value: productId,
          disabled: p.status === 'inactive' || item.quantity < 1
        })
      })
    
    // Reset with new initial item
    items.value = [{ id: 1 }]
    productSelections.value = { 1: '' }
    quantities.value = { 1: 1 }
    nextItemId.value = 2
  } catch (err) {
    globalError.value = 'Failed to sync branch inventory.'
    productOptions.value = []
    productMap.value = {}
  } finally {
    loadingProducts.value = false
  }
}

const addItem = () => {
  const newId = nextItemId.value++
  items.value.push({ id: newId })
  productSelections.value[newId] = ''
  quantities.value[newId] = 1
}

const removeItem = (idx) => {
  const removedItem = items.value[idx]
  delete productSelections.value[removedItem.id]
  delete quantities.value[removedItem.id]
  items.value.splice(idx, 1)
}

const getProduct = (productId) => {
  if (!productId || !productMap.value[productId]) return null
  return productMap.value[productId]
}

const getLineTotal = (productId, quantity) => {
  const p = getProduct(productId)
  if (!p) return 0
  return p.sale_price * (parseFloat(quantity) || 0)
}

const getLineTax = (productId, quantity) => {
  const p = getProduct(productId)
  if (!p) return 0
  const price = p.sale_price * (parseFloat(quantity) || 0)
  return price * (p.tax_percentage / 100)
}

const subtotal = computed(() => {
  return items.value.reduce((sum, item) => {
    const productId = productSelections.value[item.id]
    const quantity = quantities.value[item.id]
    return sum + getLineTotal(productId, quantity)
  }, 0)
})

const tax = computed(() => {
  return items.value.reduce((sum, item) => {
    const productId = productSelections.value[item.id]
    const quantity = quantities.value[item.id]
    return sum + getLineTax(productId, quantity)
  }, 0)
})

const total = computed(() => subtotal.value + tax.value)

const validItems = computed(() => {
  return items.value
    .filter(item => productSelections.value[item.id] && parseFloat(quantities.value[item.id]) > 0)
    .map(item => ({
      product_id: productSelections.value[item.id],
      quantity: parseFloat(quantities.value[item.id])
    }))
})

const canSubmit = computed(() => {
  return branch_id.value && validItems.value.length > 0 && !submitting.value
})

const handleConfirmOrder = async (e) => {
  if (e) {
    e.preventDefault()
    e.stopPropagation()
  }
  // Don't trigger loading screen, let it be fast
  await submitOrder()
}

const submitOrder = async () => {
  globalError.value = ''
  
  // Validate before submitting
  if (!branch_id.value) {
    globalError.value = 'Please select a branch'
    return
  }
  
  if (validItems.value.length === 0) {
    globalError.value = 'Please add at least one item with valid quantity'
    return
  }
  
  submitting.value = true
  
  try {
    console.log('Submitting order with:', {
      branch_id: branch_id.value,
      items: validItems.value
    })
    
    const response = await orderService.create({
      branch_id: branch_id.value,
      items: validItems.value
    })
    
    console.log('Order created successfully:', response)
    toast.success('Order Created', `Order with ${validItems.value.length} item(s) has been created successfully.`)
    
    // Wait for router navigation to complete before function exits
    console.log('Redirecting to dashboard...')
    try {
      await router.push('/dashboard')
      console.log('Navigation completed successfully')
    } catch (navErr) {
      console.error('Navigation error:', navErr)
      // Even if navigation fails, don't stay on this page
      window.location.href = '/dashboard'
    }
    
  } catch (e) {
    console.error('Order creation failed:', e)
    submitting.value = false  // Only set to false on error
    const errorMsg = e.response?.data?.message || e.message || 'Failed to create order. Please try again.'
    globalError.value = errorMsg
    toast.error('Order Failed', errorMsg)
  }
}

onMounted(fetchBranches)
</script>

<template>
  <div class="oc-page">
    <LoadingScreen 
      v-if="loadingBranches" 
      :show="true" 
      :message="'Loading Sites…'" 
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
              <span class="sum-val">PKR {{ subtotal.toLocaleString('en-PK', { minimumFractionDigits: 0 }) }}</span>
            </div>
            <div class="sum-row">
              <span class="sum-lbl">VAT / TAX</span>
              <span class="sum-val">PKR {{ tax.toLocaleString('en-PK', { minimumFractionDigits: 0 }) }}</span>
            </div>
            <div class="sum-divider"></div>
            <div class="sum-row sum-main">
              <span class="sum-lbl">GRAND TOTAL</span>
              <span class="sum-val">PKR {{ total.toLocaleString('en-PK', { minimumFractionDigits: 0 }) }}</span>
            </div>
          </div>

          <div class="btn-finalize-wrap">
            <BaseButton
              type="button"
              fullWidth
              variant="primary"
              :disabled="!canSubmit"
              :loading="submitting"
              icon="check_circle"
              @click="handleConfirmOrder"
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
              <div v-for="(item, idx) in items" :key="`item-${item.id}`" class="oc-item-row">
                <div class="oc-item-cols">
                  <div class="oc-col-prod">
                    <BaseSelect
                      label="PRODUCT SKU"
                      v-model="productSelections[item.id]"
                      :options="productOptions"
                      placeholder="— CHOOSE ITEM —"
                      icon="inventory_2"
                    />
                  </div>
                  
                  <div class="oc-col-qty">
                    <BaseInput
                      type="number"
                      label="QTY"
                      :value="quantities[item.id]"
                      @input="(e) => { quantities[item.id] = parseFloat(e.target.value) || 1 }"
                      @change="(e) => { quantities[item.id] = Math.max(1, parseFloat(e.target.value) || 1) }"
                      min="1"
                      step="1"
                    />
                  </div>

                  <div class="oc-col-price">
                    <span class="oc-line-price-label">LINE TOTAL</span>
                    <div class="oc-line-price-val">
                      PKR {{ getLineTotal(productSelections[item.id], quantities[item.id]).toLocaleString('en-PK', { minimumFractionDigits: 0 }) }}
                    </div>
                  </div>

                  <button class="oc-btn-del" @click="removeItem(idx)" :disabled="items.length < 2">
                    <span class="material-symbols-outlined">close</span>
                  </button>
                </div>
                <!-- Mini info -->
                <div v-if="getProduct(productSelections[item.id])" class="oc-item-details">
                  UNIT: PKR {{ getProduct(productSelections[item.id]).sale_price.toLocaleString('en-PK', { minimumFractionDigits: 0 }) }} • TAX: {{ getProduct(productSelections[item.id]).tax_percentage }}%
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
@import "../../styles/views/OrderCreate.css";
</style>