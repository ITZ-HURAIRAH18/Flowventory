<script setup>
import { ref, computed, onMounted } from 'vue'
import inventoryService from '@/services/inventoryService'
import LoadingScreen from '@/components/LoadingScreen.vue'

const inventory = ref([])
const loading   = ref(true)
const error     = ref('')
const search    = ref('')
const currentPage = ref(1)
const perPage = ref(5)
const totalItems = ref(0)
const lastPage = ref(1)
const itemsFrom = ref(0)
const itemsTo = ref(0)

/* ── fetch ── */
const fetchInventory = async (page = 1) => {
  loading.value = true
  error.value   = ''
  currentPage.value = page
  try {
    const res = await inventoryService.getAll(page, perPage.value)
    inventory.value = res.data.data ?? res.data
    totalItems.value = res.data.total ?? res.data.length
    lastPage.value = res.data.last_page ?? 1
    currentPage.value = res.data.current_page ?? page
    itemsFrom.value = res.data.from ?? 0
    itemsTo.value = res.data.to ?? 0
  } catch {
    error.value = 'Failed to load inventory data.'
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchInventory(1))

/* ── filter ── */
const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return inventory.value.filter(item => 
    (item.product?.name ?? '').toLowerCase().includes(q) ||
    (item.branch?.name  ?? '').toLowerCase().includes(q)
  )
})

/* ── stats ── */
const totalItemsCount   = computed(() => totalItems.value)
const lowStock     = computed(() => inventory.value.filter(i => i.quantity > 0 && i.quantity <= 5).length)
const outOfStock   = computed(() => inventory.value.filter(i => i.quantity <= 0).length)
const totalUnits   = computed(() => inventory.value.reduce((s, i) => s + (i.quantity || 0), 0))

/* ── helpers ── */
const stockLevel = (qty) => {
  if (qty <= 0)  return { label: 'Out of Stock', cls: 'badge-out' }
  if (qty <= 5)  return { label: 'Low Stock',    cls: 'badge-low' }
  return          { label: 'In Stock',     cls: 'badge-ok' }
}

const goToPage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    fetchInventory(page)
  }
}

</script>

<template>
  <div class="inv-page">

    <!-- ══════ HEADER ══════ -->
    <div class="inv-header">
      <div class="inv-header-left">
        <div class="inv-icon-wrap">
          <span class="material-symbols-outlined">warehouse</span>
        </div>
        <div>
          <h1 class="inv-title">Global Inventory</h1>
          <p class="inv-subtitle">Monitor and manage stock levels across all active branches</p>
        </div>
      </div>
    </div>

    <!-- ══════ QUICK ACTIONS ══════ -->
    <div class="inv-nav">
      <router-link to="/inventory/add" class="nav-card">
        <div class="nav-ico-box ico-brown"><span class="material-symbols-outlined">add_box</span></div>
        <div class="nav-info"><h3>Add Stock</h3><p>Increase branch inventory</p></div>
        <span class="material-symbols-outlined nav-arrow">chevron_right</span>
      </router-link>
      <router-link to="/inventory/adjust" class="nav-card">
        <div class="nav-ico-box ico-tan"><span class="material-symbols-outlined">tune</span></div>
        <div class="nav-info"><h3>Adjustments</h3><p>Correct stock variances</p></div>
        <span class="material-symbols-outlined nav-arrow">chevron_right</span>
      </router-link>
      <router-link to="/inventory/transfer" class="nav-card">
        <div class="nav-ico-box ico-warm"><span class="material-symbols-outlined">local_shipping</span></div>
        <div class="nav-info"><h3>Transfers</h3><p>Move items between sites</p></div>
        <span class="material-symbols-outlined nav-arrow">chevron_right</span>
      </router-link>
      <router-link to="/inventory/history" class="nav-card">
        <div class="nav-ico-box ico-gray"><span class="material-symbols-outlined">history</span></div>
        <div class="nav-info"><h3>Logs</h3><p>Review movement trails</p></div>
        <span class="material-symbols-outlined nav-arrow">chevron_right</span>
      </router-link>
    </div>

    <!-- ══════ STATS ══════ -->
    <div class="inv-stats">
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico">inventory</span>
        <span class="stat-val">{{ totalItemsCount }}</span>
        <span class="stat-lbl">SKUs Listed</span>
      </div>
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico ico-amber">warning</span>
        <span class="stat-val">{{ lowStock }}</span>
        <span class="stat-lbl">Low Stock</span>
      </div>
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico ico-red">report</span>
        <span class="stat-val">{{ outOfStock }}</span>
        <span class="stat-lbl">Out of Stock</span>
      </div>
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico ico-blue">equalizer</span>
        <span class="stat-val">{{ totalUnits.toLocaleString() }}</span>
        <span class="stat-lbl">Total Units</span>
      </div>
    </div>

    <!-- ══════ LISTING ══════ -->
    <div class="list-section">
      <div class="list-head">
        <div class="list-title-box">
          <span class="material-symbols-outlined">format_list_bulleted</span>
          <h2>Stock Directory</h2>
        </div>
        <div class="search-wrap">
          <span class="material-symbols-outlined search-ico">search</span>
          <input v-model="search" class="search-input" placeholder="Search by product or branch…" />
        </div>
      </div>

      <LoadingScreen v-if="loading" :show="loading" :duration="0" message="Analyzing stock records…" />

      <div v-else-if="error" class="inv-error">
        <span class="material-symbols-outlined">error</span>
        {{ error }}
        <button @click="fetchInventory" class="retry-btn">Retry</button>
      </div>

      <div v-else-if="filtered.length === 0" class="inv-empty">
        <span class="material-symbols-outlined inv-empty-icon">inventory_2</span>
        <p class="inv-empty-title">No inventory found</p>
        <p class="inv-empty-sub">We couldn't find any stock records matching your criteria.</p>
      </div>

      <div v-else class="table-wrap">
        <table class="inv-table">
          <thead>
            <tr>
              <th>Product Details</th>
              <th>Branch</th>
              <th>Current Stock</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in filtered" :key="item.id">
              <td>
                <div class="prod-cell">
                  <div class="prod-ico"><span class="material-symbols-outlined">package_2</span></div>
                  <div>
                    <p class="prod-name">{{ item.product?.name || 'Unknown Product' }}</p>
                    <p class="prod-sku">{{ item.product?.sku || 'NO-SKU' }}</p>
                  </div>
                </div>
              </td>
              <td class="branch-cell">
                <span class="material-symbols-outlined">store_mall_directory</span>
                {{ item.branch?.name || 'Global' }}
              </td>
              <td>
                <div class="stock-wrap">
                  <span class="stock-num">{{ item.quantity ?? item.stock ?? 0 }}</span>
                  <!-- Removal of the "pipe" (progress bar) as requested -->
                </div>
              </td>
              <td>
                <span class="stock-badge" :class="stockLevel(item.quantity ?? item.stock ?? 0).cls">
                  {{ stockLevel(item.quantity ?? item.stock ?? 0).label }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Enhanced Pagination controls -->
        <div class="pagination-controls">
          <div class="pagination-info">
            Showing {{ itemsFrom }} to {{ itemsTo }} of {{ totalItems }} inventory items
          </div>
          <div class="pagination-buttons">
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="paginate-btn">
              <span class="material-symbols-outlined">chevron_left</span> Previous
            </button>
            
            <div class="page-numbers">
              <template v-for="page in Math.min(5, lastPage)" :key="page">
                <button 
                  v-if="page <= lastPage"
                  class="page-number" 
                  :class="{ active: page === currentPage }"
                  @click="goToPage(page)"
                >
                  {{ page }}
                </button>
              </template>
              <span v-if="lastPage > 5" class="pages-ellipsis">...</span>
              <button 
                v-if="lastPage > 5 && currentPage < lastPage - 2"
                class="page-number" 
                @click="goToPage(lastPage)"
              >
                {{ lastPage }}
              </button>
            </div>
            
            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage" class="paginate-btn">
              Next <span class="material-symbols-outlined">chevron_right</span>
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
@import "../../styles/views/InventoryDashboard.css";
</style>