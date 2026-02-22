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
/* ════════════════════════════════
   PAGE - Reduced Whiteness
════════════════════════════════ */
.inv-page {
  animation: pageIn 0.45s cubic-bezier(0.16,1,0.3,1) both;
  padding: 1.5rem;
  background: #FCFAF9;
  min-height: 100vh;
}
@keyframes pageIn {
  from { opacity:0; transform:translateY(10px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ════════════════════════════════
   HEADER - Balanced Contrast
════════════════════════════════ */
.inv-header { display: flex; align-items: center; margin-bottom: 2rem; }
.inv-header-left { display: flex; align-items: center; gap: 1.25rem; }
.inv-icon-wrap {
  width: 52px; height: 52px; border-radius: 16px;
  background: #3E2723;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 20px rgba(62,39,35,0.2);
}
.inv-icon-wrap .material-symbols-outlined { color: #fff; font-size: 26px; }

.inv-title { font-size: 1.7rem; font-weight: 800; color: #3E2723; margin: 0; letter-spacing: -0.04em; }
.inv-subtitle { font-size: 0.88rem; color: #8D6E63; margin: 0.1rem 0 0; font-weight: 500; }

/* ════════════════════════════════
   NAV CARDS - Centered Layout
════════════════════════════════ */
.inv-nav {
  display: flex; flex-wrap: wrap; justify-content: center;
  gap: 1.25rem; margin-bottom: 2rem;
}
.nav-card {
  display: flex; align-items: center; gap: 1.25rem;
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 20px;
  padding: 1.1rem 1.4rem; text-decoration: none; transition: all 0.25s ease;
  box-shadow: 0 2px 10px rgba(93,64,55,0.03);
  width: 280px; /* Fixed width for better centering symmetry */
}
.nav-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(93,64,55,0.1); border-color: #8D6E63; }

.nav-ico-box {
  width: 46px; height: 46px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.nav-ico-box .material-symbols-outlined { font-size: 24px; }
.ico-brown { background: rgba(62,39,35,0.08); color: #3E2723; }
.ico-tan   { background: rgba(141,110,99,0.08); color: #8D6E63; }
.ico-warm  { background: rgba(161,136,127,0.08); color: #A1887F; }
.ico-gray  { background: rgba(215,204,200,0.12); color: #795548; }

.nav-info h3 { font-size: 0.98rem; font-weight: 800; color: #3E2723; margin: 0 0 0.15rem; }
.nav-info p  { font-size: 0.75rem; color: #A1887F; margin: 0; font-weight: 500; }
.nav-arrow { margin-left: auto; font-size: 19px; color: #D7CCC8; transition: transform 0.2s; }
.nav-card:hover .nav-arrow { transform: translateX(3px); color: #8D6E63; }

/* ════════════════════════════════
   STATS - Dividerless Design
════════════════════════════════ */
.inv-stats {
  display: flex; align-items: center;
  background: #FAF9F7; border: 1.5px solid #E0D7D0;
  border-radius: 18px; padding: 1.25rem 2rem;
  margin-bottom: 2rem; box-shadow: inset 0 0 40px rgba(0,0,0,0.01);
  justify-content: space-around;
}
.stat-chip { display: flex; align-items: center; gap: 0.8rem; }
.stat-ico { font-size: 24px; color: #8D6E63; }
.ico-amber { color: #d97706 !important; }
.ico-red   { color: #dc2626 !important; }
.ico-blue  { color: #2563eb !important; }
.stat-val { font-size: 1.5rem; font-weight: 800; color: #3E2723; letter-spacing: -0.02em; }
.stat-lbl { font-size: 0.72rem; color: #8D6E63; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }

/* ════════════════════════════════
   LISTING - Ultra Premium Table
════════════════════════════════ */
.list-section {
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 22px;
  overflow: hidden; box-shadow: 0 4px 24px rgba(93,64,55,0.05);
}
.list-head {
  padding: 1.4rem 2rem; border-bottom: 1px solid #F3EDE9;
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.25rem;
  background: #FFF;
}
.list-title-box { display: flex; align-items: center; gap: 0.75rem; }
.list-title-box .material-symbols-outlined { color: #3E2723; font-size: 22px; }
.list-title-box h2 { font-size: 1.15rem; font-weight: 800; color: #3E2723; margin: 0; letter-spacing: -0.02em; }

.search-wrap { position: relative; width: 320px; }
.search-ico { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); font-size: 19px; color: #A1887F; }
.search-input {
  width: 100%; padding: 0.7rem 2.6rem; background: #FAF8F6;
  border: 1.5px solid #E0D7D0; border-radius: 12px;
  font-size: 0.88rem; color: #3E2723; outline: none; transition: all 0.2s;
}
.search-input:focus { border-color: #8D6E63; background: #fff; box-shadow: 0 0 0 4px rgba(141,110,99,0.08); }

.inv-table { width: 100%; border-collapse: collapse; }
.inv-table th {
  padding: 1rem 2rem; background: #FAF9F7; text-align: left;
  font-size: 0.72rem; font-weight: 750; color: #5D4037; text-transform: uppercase; letter-spacing: 0.08em;
  border-bottom: 1.5px solid #E0D7D0;
}
.inv-table td { padding: 1.25rem 2rem; border-bottom: 1px solid #F8F5F2; vertical-align: middle; }
.inv-table tbody tr:last-child td { border-bottom: none; }
.inv-table tbody tr:hover td { background: #FFFAF8; }

.prod-cell { display: flex; align-items: center; gap: 1rem; }
.prod-ico {
  width: 40px; height: 40px; border-radius: 10px; background: #F5F1EE;
  display: flex; align-items: center; justify-content: center; color: #8D6E63;
  border: 1px solid #E0D7D0;
}
.prod-name { font-size: 0.92rem; font-weight: 750; color: #3E2723; margin: 0 0 0.15rem; }
.prod-sku  { font-size: 0.72rem; color: #A1887F; font-family: 'JetBrains Mono', 'Courier New', monospace; margin: 0; font-weight: 600; }

.branch-cell { font-size: 0.88rem; font-weight: 650; color: #5D4037; display: flex; align-items: center; gap: 0.5rem; }
.branch-cell .material-symbols-outlined { font-size: 20px; color: #BCAAA4; }

.stock-wrap { display: flex; align-items: center; gap: 0.85rem; min-width: 150px; }
.stock-num { font-size: 1.1rem; font-weight: 800; color: #3E2723; }

.stock-badge {
  display: inline-block; padding: 0.35rem 0.75rem; border-radius: 8px;
  font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;
}
.badge-ok  { background: #ECFDF5; color: #065F46; border: 1px solid #D1FAE5; }
.badge-low { background: #FFFBEB; color: #92400E; border: 1px solid #FEF3C7; }
.badge-out { background: #FEF2F2; color: #991B1B; border: 1px solid #FEE2E2; }

/* ════════════════════════════════
   ENHANCED PAGINATION
════════════════════════════════ */
.pagination-controls {
  padding: 1.5rem 2rem; border-top: 1px solid #F3EDE9;
  background: #FAF9F7;
}
.pagination-info {
  text-align: center; color: #64748b; font-size: 0.875rem;
  margin-bottom: 1.5rem; font-weight: 500;
}
.pagination-buttons {
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; flex-wrap: wrap;
}
.paginate-btn {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.6rem 1.2rem; background: #fff; border: 1.5px solid #E0D7D0;
  border-radius: 10px; font-weight: 700; font-size: 0.88rem; color: #3E2723;
  cursor: pointer; transition: all 0.2s; outline: none;
  min-width: 100px; justify-content: center;
}
.paginate-btn:hover:not(:disabled) { border-color: #8D6E63; background: #FAF8F6; }
.paginate-btn:disabled { opacity: 0.4; cursor: not-allowed; }

.page-numbers {
  display: flex; align-items: center; gap: 0.5rem;
}
.page-number {
  width: 40px; height: 40px;
  border: 1px solid #e2e8f0; border-radius: 8px;
  background: #fff; color: #374151;
  font-size: 0.875rem; font-weight: 500;
  cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.page-number:hover { background: #f8fafc; border-color: #94a3b8; }
.page-number.active {
  background: #3E2723; border-color: #3E2723; color: #fff;
}
.pages-ellipsis {
  color: #94a3b8; font-weight: bold; padding: 0 0.25rem;
}

@media (max-width: 768px) {
  .pagination-controls { padding: 1rem; }
  .pagination-buttons { flex-direction: column; gap: 1rem; }
  .paginate-btn { width: 100%; justify-content: center; }
}
</style>