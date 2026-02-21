<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import branchService from '@/services/branchService'
import BranchForm from './BranchForm.vue'

const route  = useRoute()
const router = useRouter()

const branch  = ref(null)
const loading = ref(true)
const error   = ref('')
const showEditModal = ref(false)

/* ── KPIs ── */
const totalProducts  = computed(() => branch.value?.inventories?.length  ?? 0)
const totalOrders    = computed(() => branch.value?.orders?.length        ?? 0)
const lowStockCount  = computed(() =>
  (branch.value?.inventories ?? []).filter(i => i.quantity <= 5).length
)
const totalStock = computed(() =>
  (branch.value?.inventories ?? []).reduce((s, i) => s + (i.quantity || 0), 0)
)

/* ── inventory filtering ── */
const invSearch   = ref('')
const invFilter   = ref('all')   // 'all' | 'low' | 'ok'

const filteredInv = computed(() => {
  let list = branch.value?.inventories ?? []
  if (invFilter.value === 'low') list = list.filter(i => i.quantity <= 5)
  if (invFilter.value === 'ok')  list = list.filter(i => i.quantity  >  5)
  const q = invSearch.value.toLowerCase()
  if (q) list = list.filter(i => (i.product?.name ?? '').toLowerCase().includes(q))
  return list
})

/* ── fetch ── */
const fetchBranch = async () => {
  loading.value = true
  error.value   = ''
  try {
    const res = await api.get(`/branches/${route.params.id}`)
    branch.value = res.data.data ?? res.data
  } catch {
    error.value = 'Failed to load branch details. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchBranch)

/* ── helpers ── */
const stockLevel = (qty) => {
  if (qty <= 0)  return { label: 'Out of Stock', cls: 'badge-out' }
  if (qty <= 5)  return { label: 'Low Stock',    cls: 'badge-low' }
  if (qty <= 20) return { label: 'Limited',       cls: 'badge-lim' }
  return          { label: 'In Stock',            cls: 'badge-ok' }
}

const onSaved = () => { showEditModal.value = false; fetchBranch() }
</script>

<template>
  <div class="bd-page">

    <!-- ══════ BACK + TITLE ══════ -->
    <div class="bd-topbar">
      <button class="back-btn" @click="router.push('/branches')">
        <span class="material-symbols-outlined">arrow_back</span>
        Branches
      </button>
    </div>

    <!-- ══════ LOADING ══════ -->
    <div v-if="loading" class="bd-loading">
      <div class="spinner"></div>
      <p>Loading branch…</p>
    </div>

    <!-- ══════ ERROR ══════ -->
    <div v-else-if="error" class="bd-error">
      <span class="material-symbols-outlined">error</span>
      {{ error }}
      <button class="retry-btn" @click="fetchBranch">Retry</button>
    </div>

    <!-- ══════ NOT FOUND ══════ -->
    <div v-else-if="!branch" class="bd-empty">
      <span class="material-symbols-outlined bd-empty-icon">store_mall_directory</span>
      <p>Branch not found.</p>
    </div>

    <template v-else>

      <!-- ══════ HERO HEADER ══════ -->
      <div class="bd-hero">
        <div class="bd-hero-left">
          <div class="bd-avatar">
            {{ (branch.name || '?').split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2) }}
          </div>
          <div>
            <h1 class="bd-name">{{ branch.name }}</h1>
            <p class="bd-address">
              <span class="material-symbols-outlined">location_on</span>
              {{ branch.address || 'No address' }}
            </p>
            <div class="bd-manager-row">
              <span class="material-symbols-outlined">manage_accounts</span>
              <span v-if="branch.manager" class="bd-mgr-name">{{ branch.manager.name }}</span>
              <span v-else class="bd-mgr-none">No manager</span>
            </div>
          </div>
        </div>
        <div class="bd-hero-actions">
          <button class="bd-btn-edit" @click="showEditModal = true" id="edit-branch-btn">
            <span class="material-symbols-outlined">edit</span>
            Edit Branch
          </button>
          <router-link :to="`/branches/${branch.id}/report`" class="bd-btn-report">
            <span class="material-symbols-outlined">bar_chart</span>
            Report
          </router-link>
        </div>
      </div>

      <!-- ══════ KPI CARDS ══════ -->
      <div class="kpi-row">
        <div class="kpi-card kpi-brown">
          <span class="material-symbols-outlined kpi-ico">inventory_2</span>
          <span class="kpi-val">{{ totalProducts }}</span>
          <span class="kpi-lbl">Products</span>
        </div>
        <div class="kpi-card kpi-warm">
          <span class="material-symbols-outlined kpi-ico">layers</span>
          <span class="kpi-val">{{ totalStock.toLocaleString() }}</span>
          <span class="kpi-lbl">Total Units</span>
        </div>
        <div class="kpi-card kpi-tan">
          <span class="material-symbols-outlined kpi-ico">receipt_long</span>
          <span class="kpi-val">{{ totalOrders }}</span>
          <span class="kpi-lbl">Orders</span>
        </div>
        <div class="kpi-card" :class="lowStockCount > 0 ? 'kpi-alert' : 'kpi-ok'">
          <span class="material-symbols-outlined kpi-ico">{{ lowStockCount > 0 ? 'warning' : 'check_circle' }}</span>
          <span class="kpi-val">{{ lowStockCount }}</span>
          <span class="kpi-lbl">Low Stock</span>
        </div>
      </div>

      <!-- ══════ INVENTORY TABLE ══════ -->
      <div class="bd-section">
        <!-- section header -->
        <div class="bd-sec-head">
          <div class="bd-sec-title-row">
            <span class="material-symbols-outlined">warehouse</span>
            <h2 class="bd-sec-title">Inventory</h2>
            <span class="bd-count-badge">{{ (branch.inventories ?? []).length }}</span>
          </div>
          <div class="bd-inv-controls">
            <!-- search -->
            <div class="inv-search-wrap">
              <span class="material-symbols-outlined">search</span>
              <input v-model="invSearch" class="inv-search" placeholder="Search products…" />
            </div>
            <!-- filter -->
            <div class="filter-tabs">
              <button
                v-for="f in [{v:'all',l:'All'},{v:'ok',l:'In Stock'},{v:'low',l:'Low Stock'}]"
                :key="f.v"
                class="filter-tab"
                :class="{ active: invFilter === f.v }"
                @click="invFilter = f.v"
              >{{ f.l }}</button>
            </div>
          </div>
        </div>

        <!-- empty state -->
        <div v-if="filteredInv.length === 0" class="inv-empty">
          <span class="material-symbols-outlined">inventory_2</span>
          <p>{{ invSearch || invFilter !== 'all' ? 'No matching items.' : 'No inventory yet.' }}</p>
        </div>

        <!-- table -->
        <div v-else class="table-wrap">
          <table class="inv-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, idx) in filteredInv" :key="item.id">
                <td class="td-idx">{{ idx + 1 }}</td>
                <td class="td-product">
                  <div class="prod-dot"></div>
                  {{ item.product?.name ?? '—' }}
                </td>
                <td class="td-qty">
                  <div class="qty-bar-wrap">
                    <span class="qty-num">{{ item.quantity }}</span>
                    <div class="qty-bar-track">
                      <div
                        class="qty-bar-fill"
                        :class="item.quantity <= 5 ? 'fill-low' : item.quantity <= 20 ? 'fill-lim' : 'fill-ok'"
                        :style="{ width: Math.min(item.quantity / 100 * 100, 100) + '%' }"
                      ></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="stock-badge" :class="stockLevel(item.quantity).cls">
                    {{ stockLevel(item.quantity).label }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </template>

    <!-- ══════ EDIT MODAL ══════ -->
    <transition name="modal-fade">
      <div v-if="showEditModal" class="modal-backdrop" @click.self="showEditModal = false">
        <div class="modal-box">
          <BranchForm :branch="branch" @saved="onSaved" @close="showEditModal = false" />
        </div>
      </div>
    </transition>

  </div>
</template>

<style scoped>
/* ════════════════════════
   PAGE
════════════════════════ */
.bd-page {
  min-height: 100%;
  background: #FAF8F6;
  padding-bottom: 3rem;
  animation: pageIn 0.4s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pageIn {
  from { opacity:0; transform:translateY(10px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ════════════════════════
   TOPBAR
════════════════════════ */
.bd-topbar { margin-bottom: 1.25rem; }
.back-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  background: #fff;
  border: 1.5px solid #EDE8E4;
  border-radius: 10px;
  padding: 0.5rem 1rem;
  font-size: 0.85rem; font-weight: 600; color: #5D4037;
  cursor: pointer; font-family: inherit;
  transition: all 0.18s;
}
.back-btn:hover { background: #FAF8F6; border-color: #D7CCC8; }
.back-btn .material-symbols-outlined { font-size: 18px; }

/* ════════════════════════
   STATES
════════════════════════ */
.bd-loading {
  display: flex; flex-direction: column; align-items: center;
  gap: 1rem; padding: 5rem 0; color: #A1887F;
}
.spinner {
  width: 36px; height: 36px; border-radius: 50%;
  border: 3px solid #EDE8E4; border-top-color: #795548;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.bd-error {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 1rem 1.25rem;
  background: #FFF5F5; border: 1px solid #fca5a5;
  border-radius: 12px; color: #dc2626; font-size: 0.9rem;
}
.retry-btn {
  margin-left: auto; padding: 0.3rem 0.9rem;
  background: #dc2626; color: #fff; border: none;
  border-radius: 8px; cursor: pointer; font-size: 0.8rem;
  font-family: inherit; font-weight: 600;
}
.bd-empty {
  text-align: center; padding: 4rem; color: #A1887F;
  display: flex; flex-direction: column; align-items: center; gap: 0.75rem;
}
.bd-empty-icon { font-size: 52px; color: #D7CCC8; }

/* ════════════════════════
   HERO HEADER
════════════════════════ */
.bd-hero {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 2rem 2.25rem;
  background: linear-gradient(135deg, #5D4037 0%, #795548 60%, #8D6E63 100%);
  border-radius: 20px;
  margin-bottom: 1.5rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(93,64,55,0.2);
}
.bd-hero::before {
  content:''; position:absolute;
  width:280px; height:280px; border-radius:50%;
  background:rgba(255,255,255,0.05); right:-70px; top:-100px;
  pointer-events:none;
}

.bd-hero-left {
  display: flex; align-items: center; gap: 1.25rem;
}
.bd-avatar {
  width: 56px; height: 56px; border-radius: 16px;
  background: rgba(255,255,255,0.18);
  border: 2px solid rgba(255,255,255,0.28);
  color: #fff; font-size: 1.1rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  letter-spacing: 0.02em; flex-shrink: 0;
}
.bd-name {
  font-size: 1.5rem; font-weight: 800; color: #fff;
  margin: 0 0 0.3rem; letter-spacing: -0.03em;
}
.bd-address {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.82rem; color: rgba(255,255,255,0.7); margin: 0 0 0.35rem;
}
.bd-address .material-symbols-outlined { font-size: 15px; }
.bd-manager-row {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.8rem; color: rgba(255,255,255,0.65);
}
.bd-manager-row .material-symbols-outlined { font-size: 15px; }
.bd-mgr-name { font-weight: 600; color: rgba(255,255,255,0.9); }
.bd-mgr-none { font-style: italic; }

.bd-hero-actions {
  display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;
}
.bd-btn-edit,
.bd-btn-report {
  display: inline-flex; align-items: center; gap: 0.38rem;
  padding: 0.6rem 1.1rem;
  border-radius: 10px;
  font-size: 0.84rem; font-weight: 700;
  cursor: pointer; font-family: inherit;
  transition: all 0.18s; text-decoration: none;
}
.bd-btn-edit {
  background: rgba(255,255,255,0.18);
  border: 1.5px solid rgba(255,255,255,0.3);
  color: #fff;
}
.bd-btn-edit:hover { background: rgba(255,255,255,0.28); }
.bd-btn-report {
  background: rgba(255,255,255,0.1);
  border: 1.5px solid rgba(255,255,255,0.2);
  color: rgba(255,255,255,0.9);
}
.bd-btn-report:hover { background: rgba(255,255,255,0.2); }
.bd-btn-edit .material-symbols-outlined,
.bd-btn-report .material-symbols-outlined { font-size: 17px; }

/* ════════════════════════
   KPI ROW
════════════════════════ */
.kpi-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px,1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.kpi-card {
  display: flex; flex-direction: column; align-items: flex-start;
  gap: 0.25rem; padding: 1.25rem 1.35rem;
  border-radius: 16px; border: 1px solid #EDE8E4;
  box-shadow: 0 2px 10px rgba(93,64,55,0.05);
}
.kpi-brown { background: linear-gradient(135deg,#5D4037,#795548); color:#fff; border:none; }
.kpi-warm  { background: #fff; }
.kpi-tan   { background: #FAF8F6; }
.kpi-alert { background: #FFF5F5; border-color: #fca5a5; }
.kpi-ok    { background: #F0FFF4; border-color: #6ee7b7; }

.kpi-ico { font-size: 22px; margin-bottom: 0.25rem; }
.kpi-brown .kpi-ico { color: rgba(255,255,255,0.85); }
.kpi-warm  .kpi-ico { color: #8D6E63; }
.kpi-tan   .kpi-ico { color: #8D6E63; }
.kpi-alert .kpi-ico { color: #dc2626; }
.kpi-ok    .kpi-ico { color: #059669; }

.kpi-val {
  font-size: 1.6rem; font-weight: 800; letter-spacing: -0.03em;
}
.kpi-brown .kpi-val { color: #fff; }
.kpi-warm  .kpi-val,
.kpi-tan   .kpi-val  { color: #3E2723; }
.kpi-alert .kpi-val  { color: #dc2626; }
.kpi-ok    .kpi-val  { color: #059669; }

.kpi-lbl { font-size: 0.72rem; font-weight: 600;
  letter-spacing: 0.05em; text-transform: uppercase; }
.kpi-brown .kpi-lbl { color: rgba(255,255,255,0.65); }
.kpi-warm  .kpi-lbl,
.kpi-tan   .kpi-lbl  { color: #A1887F; }
.kpi-alert .kpi-lbl  { color: #ef4444; }
.kpi-ok    .kpi-lbl  { color: #10b981; }

/* ════════════════════════
   SECTION
════════════════════════ */
.bd-section {
  background: #fff;
  border: 1px solid #EDE8E4;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(93,64,55,0.05);
}

.bd-sec-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #F3EDE9;
}
.bd-sec-title-row {
  display: flex; align-items: center; gap: 0.5rem;
}
.bd-sec-title-row .material-symbols-outlined { font-size: 20px; color: #8D6E63; }
.bd-sec-title {
  font-size: 1rem; font-weight: 700; color: #3E2723; margin: 0;
}
.bd-count-badge {
  padding: 0.15rem 0.55rem;
  background: rgba(93,64,55,0.08);
  border-radius: 99px;
  font-size: 0.75rem; font-weight: 700; color: #5D4037;
}

.bd-inv-controls {
  display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;
}
.inv-search-wrap {
  position: relative; display: flex; align-items: center;
}
.inv-search-wrap .material-symbols-outlined {
  position: absolute; left: 0.65rem; font-size: 17px; color: #A1887F;
  pointer-events: none;
}
.inv-search {
  padding: 0.45rem 0.75rem 0.45rem 2.2rem;
  background: #FAF8F6;
  border: 1.5px solid #EDE8E4;
  border-radius: 9px; font-size: 0.82rem;
  color: #3E2723; font-family: inherit; outline: none;
  transition: border-color 0.2s; width: 200px;
}
.inv-search:focus { border-color: #A1887F; background: #fff; }
.inv-search::placeholder { color: #BCAAA4; }

.filter-tabs {
  display: flex; gap: 0.3rem;
}
.filter-tab {
  padding: 0.4rem 0.8rem;
  border: 1.5px solid #EDE8E4; border-radius: 8px;
  background: #FAF8F6; color: #8D6E63;
  font-size: 0.78rem; font-weight: 600; font-family: inherit;
  cursor: pointer; transition: all 0.18s;
}
.filter-tab:hover { border-color: #BCAAA4; }
.filter-tab.active { background: #5D4037; border-color: #5D4037; color: #fff; }

/* ════════════════════════
   TABLE
════════════════════════ */
.inv-empty {
  display: flex; flex-direction: column; align-items: center;
  gap: 0.5rem; padding: 3rem; color: #A1887F;
}
.inv-empty .material-symbols-outlined { font-size: 36px; color: #D7CCC8; }
.inv-empty p { margin: 0; font-size: 0.88rem; }

.table-wrap { overflow-x: auto; }

.inv-table {
  width: 100%; border-collapse: collapse;
  font-size: 0.87rem;
}
.inv-table th {
  padding: 0.75rem 1.25rem;
  text-align: left;
  background: #FAF8F6;
  color: #795548;
  font-size: 0.72rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.06em;
  border-bottom: 1px solid #EDE8E4;
}
.inv-table td {
  padding: 0.85rem 1.25rem;
  color: #3E2723; border-bottom: 1px solid #F3EDE9;
  vertical-align: middle;
}
.inv-table tbody tr:last-child td { border-bottom: none; }
.inv-table tbody tr:hover td { background: #FAF8F6; }

.td-idx { color: #BCAAA4; font-weight: 500; width: 40px; }
.td-product {
  font-weight: 600; display: flex; align-items: center; gap: 0.6rem;
}
.prod-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: linear-gradient(135deg, #5D4037, #A1887F); flex-shrink: 0;
}

.td-qty { min-width: 180px; }
.qty-bar-wrap { display: flex; align-items: center; gap: 0.6rem; }
.qty-num { font-weight: 700; min-width: 30px; }
.qty-bar-track {
  flex: 1; height: 5px; background: #EDE8E4; border-radius: 99px; overflow: hidden;
}
.qty-bar-fill {
  height: 100%; border-radius: 99px; transition: width 0.4s;
}
.fill-ok  { background: #22c55e; }
.fill-lim { background: #f59e0b; }
.fill-low { background: #ef4444; }

.stock-badge {
  display: inline-block;
  padding: 0.25rem 0.7rem;
  border-radius: 99px;
  font-size: 0.73rem; font-weight: 700;
}
.badge-ok  { background: rgba(34,197,94,0.12);  color: #16a34a; }
.badge-lim { background: rgba(245,158,11,0.12); color: #b45309; }
.badge-low { background: rgba(239,68,68,0.12);  color: #dc2626; }
.badge-out { background: rgba(100,116,139,0.1); color: #475569; }

/* ════════════════════════
   MODAL
════════════════════════ */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 2000;
  background: rgba(30,15,10,0.45);
  backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.modal-box {
  width: 100%; max-width: 520px;
  border-radius: 22px; overflow: hidden;
  box-shadow: 0 32px 80px rgba(0,0,0,0.3);
  animation: modalIn 0.35s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes modalIn {
  from { opacity:0; transform:scale(0.93) translateY(20px); }
  to   { opacity:1; transform:scale(1)    translateY(0); }
}
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from,  .modal-fade-leave-to     { opacity: 0; }

/* ════════════════════════
   RESPONSIVE
════════════════════════ */
@media (max-width: 640px) {
  .bd-hero        { padding: 1.5rem; }
  .bd-hero-left   { flex-wrap: wrap; }
  .bd-hero-actions{ justify-content: flex-start; }
  .kpi-row        { grid-template-columns: 1fr 1fr; gap: 0.75rem; }
  .bd-inv-controls{ flex-direction: column; align-items: flex-start; }
  .inv-search     { width: 100%; }
}
</style>
