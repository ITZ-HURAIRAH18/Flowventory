<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
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

const form = reactive({
  from_branch_id: '',
  to_branch_id: '',
  product_id: '',
  quantity: 1
})

const loading    = ref(false)
const fetching   = ref(true)
const globalError = ref('')
const fieldErrors = reactive({
  from_branch_id: '',
  to_branch_id: '',
  product_id: '',
  quantity: ''
})

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

const validate = () => {
  let valid = true
  if (!form.from_branch_id) { fieldErrors.from_branch_id = 'Source required'; valid = false }
  if (!form.to_branch_id) { fieldErrors.to_branch_id = 'Destination required'; valid = false }
  if (form.from_branch_id === form.to_branch_id) { 
    fieldErrors.to_branch_id = 'Cannot transfer to same branch'
    valid = false 
  }
  if (!form.product_id) { fieldErrors.product_id = 'Product required'; valid = false }
  if (form.quantity < 1) { fieldErrors.quantity = 'Invalid quantity'; valid = false }
  return valid
}

const transfer = async () => {
  if (!validate()) return
  
  loading.value = true
  globalError.value = ''
  try {
    await inventoryApi.transfer(form)
    router.push('/inventory')
  } catch (err) {
    globalError.value = err.response?.data?.message || 'Failed to transfer stock.'
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
        
        <ErrorBanner :show="!!globalError" :error="globalError" />

        <div class="tr-split">
          <BaseSelect
            label="Source Branch"
            v-model="form.from_branch_id"
            icon="outbox"
            placeholder="— From —"
            :options="branches"
            :error="fieldErrors.from_branch_id"
            @change="fieldErrors.from_branch_id = ''"
            required
          />

          <BaseSelect
            label="Destination"
            v-model="form.to_branch_id"
            icon="inbox"
            placeholder="— To —"
            :options="branches.map(b => ({ ...b, disabled: b.value === form.from_branch_id }))"
            :error="fieldErrors.to_branch_id"
            @change="fieldErrors.to_branch_id = ''"
            required
          />
        </div>

        <BaseSelect
          label="Product to Move"
          v-model="form.product_id"
          icon="inventory_2"
          placeholder="— Select product —"
          :options="products"
          :error="fieldErrors.product_id"
          @change="fieldErrors.product_id = ''"
          required
        />

        <BaseInput
          type="number"
          label="Transfer Quantity"
          v-model.number="form.quantity"
          icon="numbers"
          min="1"
          :error="fieldErrors.quantity"
          @input="fieldErrors.quantity = ''"
          required
        />

        <!-- Actions -->
        <div class="tr-actions">
          <BaseButton variant="secondary" @click="router.push('/inventory')">Cancel</BaseButton>
          <BaseButton 
            type="submit" 
            :loading="loading" 
            icon="move_down"
            id="execute-transfer-btn"
          >
            Execute Transfer
          </BaseButton>
        </div>
      </form>
    </div>

  </div>
</template>


<style scoped>
@import "../../styles/views/StockTransfer.css";
</style>