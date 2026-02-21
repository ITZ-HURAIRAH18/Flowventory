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
      tasks.push(branchService.getAll().then(r  => { branches.value  = r.data.data ?? r.data }))
      tasks.push(productService.getAll().then(r  => { products.value  = r.data.data ?? r.data }))
      tasks.push(inventoryService.getAll().then(r => { 
        // Handle paginated response
        const data = r.data
        inventory.value = data.data ?? data
        // Store total for KPI calculation
        inventoryTotal.value = data.total ?? 0
      }))
      // Get inventory statistics (total volume, low stock count, etc.)
      tasks.push(inventoryService.getStats().then(r => { 
        inventoryStats.value = r.data
      }))
    }
    await Promise.all(tasks)
  } catch {
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

        <div v-if="inventory.length === 0" class="ov-empty">
          <span class="material-symbols-outlined">info</span>
          <p>No inventory records found for your permissions.</p>
        </div>

        <div v-else class="ov-table-wrap">
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
              <tr v-for="item in inventory.slice(0, 8)" :key="item.id">
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
        </div>
      </section>

    </template>
  </div>
</template>

<style scoped>
.db-page {
  min-height: 100vh; background: #FCFAF9; padding: 1.5rem;
  animation: pageIn 0.5s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Header */
.db-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1.5px solid #EDE8E4;
}
.db-header-left { display: flex; align-items: center; gap: 1rem; }
.db-header-icon {
  width: 52px; height: 52px; border-radius: 16px;
  background: #3E2723; display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 20px rgba(62,39,35,0.22);
}
.db-header-icon .material-symbols-outlined { font-size: 26px; color: #fff; }
.db-title { font-size: 1.6rem; font-weight: 850; color: #3E2723; margin: 0; letter-spacing: -0.04em; }
.db-name-accent { color: #8D6E63; }
.db-subtitle { font-size: 0.82rem; color: #A1887F; margin: 0.15rem 0 0; font-weight: 500; }

.btn-primary {
  background: #3E2723; color: #fff; border: none; padding: 0.7rem 1.4rem;
  border-radius: 12px; font-weight: 700; font-size: 0.88rem; cursor: pointer;
  display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;
}
.btn-primary:hover { background: #5D4037; transform: translateY(-1px); }

/* Error bar */
.db-error-bar {
  display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1.25rem;
  background: #FFF5F2; border: 1px solid #FFEDEB; border-radius: 14px;
  color: #C62828; font-size: 0.85rem; margin-bottom: 1.5rem; font-weight: 600;
}
.retry-btn {
  margin-left: auto; background: #C62828; color: #fff; border: none;
  padding: 0.3rem 0.8rem; border-radius: 8px; cursor: pointer; font-family: inherit;
}

/* KPIs */
.kpi-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.25rem; margin-bottom: 1.5rem;
}
.kpi-card {
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 22px;
  padding: 1.4rem 1.6rem; display: flex; align-items: center; gap: 1.25rem;
  transition: all 0.25s ease;
}
.kpi-card[role="button"] { cursor: pointer; }
.kpi-card[role="button"]:hover { transform: translateY(-3px); border-color: #8D6E63; box-shadow: 0 10px 25px rgba(93,64,55,0.08); }

.kpi-dark { background: #3E2723; border-color: #3E2723; color: #fff; }
.kpi-dark .kpi-label { color: rgba(255,255,255,0.7); }
.kpi-dark .kpi-sub { color: rgba(255,255,255,0.5); }
.kpi-dark .kpi-value { color: #fff; }

.kpi-icon-wrap {
  width: 50px; height: 50px; border-radius: 15px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  background: rgba(93,64,55,0.08); color: #8D6E63;
}
.kpi-dark .kpi-icon-wrap { background: rgba(255,255,255,0.12); color: #fff; }

.kpi-info { flex: 1; min-width: 0; }
.kpi-label { display: block; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #A1887F; }
.kpi-value { display: block; font-size: 1.7rem; font-weight: 850; color: #3E2723; margin: 0.1rem 0; letter-spacing: -0.03em; }
.kpi-sub { display: block; font-size: 0.72rem; color: #BCAAA4; font-weight: 500; }

.kpi-alert { background: #FFF9F7; border-color: #F8D7DA; }
.kpi-alert .kpi-icon-wrap { background: rgba(229,57,53,0.08); color: #E53935; }
.kpi-alert .kpi-value { color: #C62828; }

.kpi-arrow { font-size: 18px; color: #D7CCC8; }

/* Sales Specific welcome */
.sales-welcome {
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 24px;
  padding: 4rem 2rem; text-align: center; margin-bottom: 2rem;
}
.sw-art {
  width: 80px; height: 80px; border-radius: 50%; background: #FAF9F7;
  display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;
}
.sw-art .material-symbols-outlined { font-size: 40px; color: #8D6E63; }
.sw-content h2 { font-size: 1.6rem; color: #3E2723; font-weight: 850; margin: 0 0 0.5rem; }
.sw-content p { color: #A1887F; font-size: 0.95rem; margin-bottom: 2rem; }
.sw-actions { display: flex; justify-content: center; gap: 1rem; }
.btn-outline {
  background: #fff; border: 1.5px solid #D7CCC8; padding: 0.7rem 1.4rem;
  border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s;
}

/* Table Card */
.db-card {
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 24px;
  padding: 1.5rem 1.75rem; box-shadow: 0 4px 20px rgba(93,64,55,0.05);
}
.card-head { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; }
.card-head-ico { color: #8D6E63; font-size: 22px; }
.card-title { font-size: 1.1rem; font-weight: 850; color: #3E2723; margin: 0; }
.card-head-link { margin-left: auto; font-size: 0.8rem; font-weight: 800; color: #8D6E63; text-decoration: none; }

.ov-table { width: 100%; border-collapse: collapse; }
.ov-table th {
  padding: 0.8rem 1.25rem; text-align: left; font-size: 0.7rem; font-weight: 800;
  text-transform: uppercase; color: #A1887F; letter-spacing: 0.08em; border-bottom: 1.5px solid #F0EDE9;
}
.ov-table td { padding: 1rem 1.25rem; border-bottom: 1px solid #FAF9F7; font-size: 0.9rem; }
.ov-td-product { display: flex; align-items: center; gap: 0.75rem; font-weight: 750; color: #3E2723; }
.ov-dot { width: 8px; height: 8px; border-radius: 50%; }
.dot-red { background: #E53935; } 
.dot-amber { background: #FB8C00; }
.dot-brown { background: #8D6E63; }

.ov-td-branch { color: #8D6E63; font-weight: 500; }
.ov-td-qty { font-weight: 800; color: #3E2723; }

.ov-status { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; padding: 0.2rem 0.6rem; border-radius: 6px; }
.s-alert { background: #FEF2F2; color: #EF4444; }
.s-ok { background: #ECFDF5; color: #10B981; }

@media (max-width: 768px) {
  .kpi-grid { grid-template-columns: 1fr; }
  .sw-actions { flex-direction: column; }
}
</style>
