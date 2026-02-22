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
@import "../../styles/views/BranchDashboard.css";
</style>
