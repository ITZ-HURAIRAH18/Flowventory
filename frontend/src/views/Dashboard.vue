<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import branchService from '@/services/branchService'
import productService from '@/services/productService'
import inventoryService from '@/services/inventoryService'
import LoadingScreen from '@/components/LoadingScreen.vue'

const router = useRouter()
const user   = ref(null)

/* ── loading & error state ── */
const loading = ref(true)
const error   = ref('')

/* ── real data ── */
const branches   = ref([])
const products   = ref([])
const inventory  = ref([])
const inventoryTotal = ref(0)
const inventoryStats = ref({
  total_volume: 0,
  low_stock_count: 0,
  out_of_stock_count: 0,
  total_skus: 0
})

/* ── pagination for stock positions ── */
const stockCurrentPage = ref(1)
const stockItemsPerPage = 5
const stockTotalItems = ref(0) // Total items from server
const inventoryLoading = ref(false) // Loading state for pagination

/* ── computed for pagination ── */
const totalStockPages = computed(() => {
  return Math.ceil(stockTotalItems.value / stockItemsPerPage)
})

const hasStockPrevPage = computed(() => stockCurrentPage.value > 1)
const hasStockNextPage = computed(() => stockCurrentPage.value < totalStockPages.value)

/* ── fetch inventory with pagination ── */
const fetchInventory = async (page = 1) => {
  inventoryLoading.value = true
  try {
    console.log('Fetching inventory page:', page)
    const response = await inventoryService.getAll(page, stockItemsPerPage)
    console.log('Inventory API response:', response)
    
    const data = response.data
    
    // Handle both paginated and non-paginated responses
    if (data.data) {
      // Paginated response
      inventory.value = data.data
      stockTotalItems.value = data.total || 0
      stockCurrentPage.value = data.current_page || page
      console.log('Paginated response - Inventory set to:', inventory.value.length, 'items')
    } else if (Array.isArray(data)) {
      // Direct array response
      inventory.value = data.slice(0, stockItemsPerPage)
      stockTotalItems.value = data.length
      stockCurrentPage.value = page
      console.log('Array response - Inventory set to:', inventory.value.length, 'items')
    } else {
      // Unexpected response structure
      console.warn('Unexpected inventory response structure:', data)
      inventory.value = []
      stockTotalItems.value = 0
    }
    
    // If no inventory data and this is not page 1, try fallback
    if (inventory.value.length === 0 && page > 1) {
      console.log('No data on page', page, 'trying fallback to fetch all and slice')
      // Fallback: fetch all inventory data and slice client-side
      const fallbackResponse = await inventoryService.getAll()
      const allData = fallbackResponse.data?.data || fallbackResponse.data || []
      
      if (Array.isArray(allData) && allData.length > 0) {
        const startIndex = (page - 1) * stockItemsPerPage
        const endIndex = startIndex + stockItemsPerPage
        inventory.value = allData.slice(startIndex, endIndex)
        stockTotalItems.value = allData.length
        stockCurrentPage.value = page
        console.log('Fallback success - Inventory set to:', inventory.value.length, 'items from total', allData.length)
      }
    }
    
    console.log('Final inventory state:', inventory.value)
    console.log('Total items:', stockTotalItems.value)
    console.log('Current page:', stockCurrentPage.value)
    
  } catch (error) {
    console.error('Failed to fetch inventory:', error)
    
    // Final fallback - try to get all data without pagination
    try {
      console.log('Attempting final fallback for inventory')
      const fallbackResponse = await inventoryService.getAll()
      const allData = fallbackResponse.data?.data || fallbackResponse.data || []
      
      if (Array.isArray(allData) && allData.length > 0) {
        const startIndex = (page - 1) * stockItemsPerPage
        const endIndex = startIndex + stockItemsPerPage
        inventory.value = allData.slice(startIndex, endIndex)
        stockTotalItems.value = allData.length
        stockCurrentPage.value = page
        console.log('Final fallback success - Inventory set to:', inventory.value.length, 'items')
      } else {
        inventory.value = []
        stockTotalItems.value = 0
      }
    } catch (fallbackError) {
      console.error('All fallback attempts failed:', fallbackError)
      inventory.value = []
      stockTotalItems.value = 0
    }
  } finally {
    inventoryLoading.value = false
  }
}

/* ── pagination handlers ── */
const goToStockPage = async (page) => {
  if (page >= 1 && page <= totalStockPages.value) {
    await fetchInventory(page)
  }
}

const prevStockPage = async () => {
  if (hasStockPrevPage.value) {
    await fetchInventory(stockCurrentPage.value - 1)
  }
}

const nextStockPage = async () => {
  if (hasStockNextPage.value) {
    await fetchInventory(stockCurrentPage.value + 1)
  }
}

/* ── derived KPIs ── */
const totalBranches  = computed(() => branches.value.length)
const managedBranches = computed(() => branches.value.filter(b => b.manager).length)
const totalProducts  = computed(() => products.value.length)
const activeProducts = computed(() => products.value.filter(p => p.status === 'active').length)
const totalStock     = computed(() => inventoryStats.value.total_volume || 0)
const lowStockItems  = computed(() => inventoryStats.value.low_stock_count || 0)

const roleName   = computed(() => {
  if (!user.value?.role) return ''
  return typeof user.value.role === 'string' ? user.value.role : user.value.role.name
})

const isSuperAdmin    = computed(() => roleName.value === 'super_admin')
const isBranchManager = computed(() => roleName.value === 'branch_manager')
const isSalesUser     = computed(() => roleName.value === 'sales_user')

/* ── greeting ── */
const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Good morning'
  if (h < 17) return 'Good afternoon'
  return 'Good evening'
})

const dateStr = computed(() =>
  new Date().toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  })
)

const roleLabel = computed(() => {
  const m = {
    super_admin:    'Super Admin',
    branch_manager: 'Branch Manager',
    sales_user:     'Sales Specialist'
  }
  return m[roleName.value] || roleName.value
})

/* ── fetch all data ── */
const fetchData = async () => {
  loading.value = true
  error.value   = ''
  try {
    const tasks = []
    // Sales users don't need these analytics for now
    if (isSuperAdmin.value || isBranchManager.value) {
      console.log('User role detected:', roleName.value)
      
      if (isSuperAdmin.value) {
        tasks.push(branchService.getAll().then(r  => { branches.value  = r.data.data ?? r.data }))
        tasks.push(productService.getAll().then(r  => { products.value  = r.data.data ?? r.data }))
      } else {
        tasks.push(api.get('/all-branches').then(r  => { branches.value  = r.data.data ?? r.data }))
        tasks.push(api.get('/all-products').then(r  => { products.value  = r.data.data ?? r.data }))
      }
      
      // Fetch only the first page of inventory (5 items)
      tasks.push(fetchInventory(1))
      
      // Get inventory statistics (total volume, low stock count, etc.)
      tasks.push(inventoryService.getStats().then(r => { 
        inventoryStats.value = r.data
      }))
    } else {
      console.log('User role not authorized for inventory data:', roleName.value)
    }
    
    await Promise.all(tasks)
    console.log('All data fetched. Final inventory:', inventory.value)
    
  } catch (err) {
    console.error('Error fetching dashboard data:', err)
    error.value = 'Operational data synchronized partially.'
  } finally {
    loading.value = false
  }
}

const navigate = (path) => router.push(path)

onMounted(() => {
  const stored = localStorage.getItem('user')
  if (stored) {
    user.value = JSON.parse(stored)
  }
  fetchData()
})
</script>

<template>
  <div class="db-page">

    <LoadingScreen
      v-if="loading"
      :show="loading"
      :duration="0"
      message="Initializing workspace…"
    />

    <!-- Header -->
    <div v-if="!loading" class="db-header">
      <div class="db-header-left">
        <div class="db-header-icon">
          <span class="material-symbols-outlined">dashboard</span>
        </div>
        <div>
          <h1 class="db-title">{{ greeting }}, <span class="db-name-accent">{{ user?.name || 'User' }}</span></h1>
          <p class="db-subtitle">{{ dateStr }} &nbsp;·&nbsp; {{ roleLabel }}</p>
        </div>
      </div>
      <div class="db-header-right" v-if="isSalesUser">
        <button class="btn-primary" @click="navigate('/orders/create')">
          <span class="material-symbols-outlined">add_shopping_cart</span>
          New Sale
        </button>
      </div>
    </div>

    <!-- Error Banner -->
    <div v-if="error && !loading" class="db-error-bar">
      <span class="material-symbols-outlined">info</span>
      {{ error }}
      <button class="retry-btn" @click="fetchData">Refresh Sync</button>
    </div>

    <!-- Content -->
    <template v-if="!loading">

      <!-- Admin/Manager Analytics -->
      <div class="kpi-grid" v-if="isSuperAdmin || isBranchManager">

        <!-- Branches (Super Admin only) -->
        <div v-if="isSuperAdmin" class="kpi-card kpi-dark" @click="navigate('/branches')" role="button">
          <div class="kpi-icon-wrap">
            <span class="material-symbols-outlined">store</span>
          </div>
          <div class="kpi-info">
            <span class="kpi-label">Active Branches</span>
            <span class="kpi-value">{{ totalBranches }}</span>
            <span class="kpi-sub">{{ managedBranches }} managed</span>
          </div>
          <span class="material-symbols-outlined kpi-arrow">arrow_forward</span>
        </div>

        <!-- Products (Admin only) -->
        <div v-if="isSuperAdmin" class="kpi-card kpi-light" @click="navigate('/products')" role="button">
          <div class="kpi-icon-wrap">
            <span class="material-symbols-outlined">inventory_2</span>
          </div>
          <div class="kpi-info">
            <span class="kpi-label">Listing</span>
            <span class="kpi-value">{{ totalProducts }}</span>
            <span class="kpi-sub">{{ activeProducts }} available</span>
          </div>
          <span class="material-symbols-outlined kpi-arrow">arrow_forward</span>
        </div>

        <!-- Stock Units -->
        <div class="kpi-card kpi-muted" @click="navigate('/inventory')" role="button">
          <div class="kpi-icon-wrap">
            <span class="material-symbols-outlined">warehouse</span>
          </div>
          <div class="kpi-info">
            <span class="kpi-label">Total Volume</span>
            <span class="kpi-value">{{ totalStock.toLocaleString() }}</span>
            <span class="kpi-sub">items in warehouse</span>
          </div>
          <span class="material-symbols-outlined kpi-arrow">arrow_forward</span>
        </div>

        <!-- Alerts -->
        <div class="kpi-card" :class="lowStockItems > 0 ? 'kpi-alert' : 'kpi-ok'">
          <div class="kpi-icon-wrap">
            <span class="material-symbols-outlined">{{ lowStockItems > 0 ? 'priority_high' : 'done_all' }}</span>
          </div>
          <div class="kpi-info">
            <span class="kpi-label">Health Check</span>
            <span class="kpi-value">{{ lowStockItems }}</span>
            <span class="kpi-sub">{{ lowStockItems > 0 ? 'low stock warnings' : 'inventory optimal' }}</span>
          </div>
        </div>
      </div>

      <!-- Sales Specialist Quick Access -->
      <div v-if="isSalesUser" class="sales-welcome">
        <div class="sw-content">
          <div class="sw-art">
            <span class="material-symbols-outlined">shopping_bag</span>
          </div>
          <h2>Ready to assist?</h2>
          <p>Create a new order or view your recent sales to get started.</p>
          <div class="sw-actions">
            <button class="btn-primary" @click="navigate('/orders/create')">Create Transaction</button>
            <button class="btn-outline" @click="navigate('/inventory')">Check Inventory</button>
          </div>
        </div>
      </div>

      <!-- Inventory Preview (Restricted) -->
      <section class="db-card" v-if="isSuperAdmin || isBranchManager">
        <div class="card-head">
          <span class="material-symbols-outlined card-head-ico">view_list</span>
          <h2 class="card-title">Latest Stock Positions</h2>
          <router-link to="/inventory" class="card-head-link">Advanced View</router-link>
        </div>

        <div v-if="inventory.length === 0 && !inventoryLoading" class="ov-empty">
          <span class="material-symbols-outlined">info</span>
          <p>No inventory records found for your permissions.</p>
        </div>

        <div v-if="inventoryLoading" class="ov-loading">
          <span class="material-symbols-outlined loading-spinner">hourglass_empty</span>
          <p>Loading inventory data...</p>
        </div>

        <div v-else-if="inventory.length > 0" class="ov-table-wrap">
          <table class="ov-table">
            <thead>
              <tr>
                <th>Product</th>
                <th v-if="isSuperAdmin">Branch</th>
                <th>Quantity</th>
                <th>Confidence</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in inventory" :key="item.id">
                <td class="ov-td-product">
                  <span class="ov-dot" :class="(item.quantity ?? item.stock ?? 0) <= 5 ? 'dot-red' : (item.quantity ?? item.stock ?? 0) <= 20 ? 'dot-amber' : 'dot-brown'"></span>
                  {{ item.product?.name ?? '—' }}
                </td>
                <td class="ov-td-branch" v-if="isSuperAdmin">{{ item.branch?.name ?? '—' }}</td>
                <td class="ov-td-qty">{{ (item.quantity ?? item.stock ?? 0).toLocaleString() }}</td>
                <td>
                  <span class="ov-status" :class="(item.quantity ?? item.stock ?? 0) <= 5 ? 's-alert' : 's-ok'">
                    {{ (item.quantity ?? item.stock ?? 0) <= 5 ? 'Restock Required' : 'Optimal' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination Controls -->
          <div v-if="totalStockPages > 1" class="ov-pagination">
            <div class="pagination-info">
              <span>Page {{ stockCurrentPage }} of {{ totalStockPages }}</span>
              <span class="pagination-total">({{ stockTotalItems }} items)</span>
            </div>
            <div class="pagination-controls">
              <button 
                class="pagination-btn" 
                :disabled="!hasStockPrevPage"
                @click="prevStockPage"
                aria-label="Previous page"
              >
                <span class="material-symbols-outlined">chevron_left</span>
              </button>
              
              <button 
                v-for="page in Math.min(totalStockPages, 5)" 
                :key="page"
                class="pagination-btn pagination-page"
                :class="{ active: page === stockCurrentPage }"
                @click="goToStockPage(page)"
              >
                {{ page }}
              </button>
              
              <button 
                class="pagination-btn"
                :disabled="!hasStockNextPage"
                @click="nextStockPage"
                aria-label="Next page"
              >
                <span class="material-symbols-outlined">chevron_right</span>
              </button>
            </div>
          </div>
        </div>
      </section>

    </template>
  </div>
</template>

<style scoped>
@import "../styles/views/Dashboard.css";
</style>
