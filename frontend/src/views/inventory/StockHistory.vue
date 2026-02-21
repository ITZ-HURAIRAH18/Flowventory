<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/inventoryService'
import LoadingScreen from '@/components/LoadingScreen.vue'

const history = ref([])
const loading = ref(true)
const error   = ref(null)
const currentPage = ref(1)
const perPage = ref(20)
const totalItems = ref(0)
const lastPage = ref(1)

const fetchHistory = async (page = 1) => {
  loading.value = true
  error.value = null
  currentPage.value = page
  try {
    const res = await api.history(page, perPage.value)
    history.value = res.data.data ?? res.data
    totalItems.value = res.data.total ?? res.data.length
    lastPage.value = res.data.last_page ?? 1
    currentPage.value = res.data.current_page ?? page
  } catch (err) {
    error.value = 'Failed to load stock history logs.'
  } finally {
    loading.value = false
  }
}

const formatAction = (type) => {
  const labels = { add: 'Inbound', adjust: 'Adjustment', transfer_in: 'Stock In', transfer_out: 'Stock Out' }
  return labels[type] || type
}

const actionClass = (type) => {
  const map = { add: 'act-add', adjust: 'act-adj', transfer_in: 'act-in', transfer_out: 'act-out' }
  return map[type] || ''
}

const actionIcon = (type) => {
  const map = { add: 'add_circle', adjust: 'tune', transfer_in: 'login', transfer_out: 'logout' }
  return map[type] || 'swap_horiz'
}

const goToPage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    fetchHistory(page)
  }
}

onMounted(() => fetchHistory(1))
</script>

<template>
  <div class="sh-page">

    <LoadingScreen v-if="loading" :show="loading" message="Retrieving audit logs…" />

    <!-- Header -->
    <div class="sh-header">
      <router-link to="/inventory" class="sh-back">
        <span class="material-symbols-outlined">arrow_back</span>
        Back to Inventory
      </router-link>
      <div class="sh-title-row">
        <div class="sh-icon">
          <span class="material-symbols-outlined">history</span>
        </div>
        <div>
          <h1 class="sh-title">Movement Logs</h1>
          <p class="sh-subtitle">Complete historical audit trail of all warehouse activities</p>
        </div>
      </div>
    </div>

    <!-- Listing -->
    <div class="sh-table-container">
      <div class="sh-table-head">
        <div class="sh-table-title">
          <span class="material-symbols-outlined">view_list</span>
          Activity Stream
        </div>
        <button class="sh-refresh" @click="fetchHistory">
          <span class="material-symbols-outlined">refresh</span>
          Refresh Logs
        </button>
      </div>

      <div v-if="error" class="sh-error">
        <span class="material-symbols-outlined">error</span>
        {{ error }}
        <button @click="fetchHistory" class="retry-btn">Retry</button>
      </div>

      <div v-else-if="history.length === 0" class="sh-empty">
        <span class="material-symbols-outlined sh-empty-icon">history_toggle_off</span>
        <h3>No activity recorded</h3>
        <p>No stock movements have been logged for this period.</p>
      </div>

      <div v-else class="sh-table-wrap">
        <table class="sh-table">
          <thead>
            <tr>
              <th>Stock Event</th>
              <th>Product Information</th>
              <th>Storage Site</th>
              <th>Variance</th>
              <th>Timestamp</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in history" :key="item.id">
              <td>
                <div class="sh-event-tag" :class="actionClass(item.action)">
                  <span class="material-symbols-outlined">{{ actionIcon(item.action) }}</span>
                  {{ formatAction(item.action) }}
                </div>
              </td>
              <td>
                <div class="sh-prod">
                  <p class="sh-prod-name">{{ item.product?.name || 'Unknown' }}</p>
                  <p class="sh-prod-sku">{{ item.product?.sku || 'N/A' }}</p>
                </div>
              </td>
              <td class="sh-branch">
                <span class="material-symbols-outlined">storefront</span>
                {{ item.branch?.name || 'Global' }}
              </td>
              <td>
                <span class="sh-qty" :class="item.quantity > 0 ? 'qty-pos' : 'qty-neg'">
                  {{ item.quantity > 0 ? '+' : '' }}{{ item.quantity }}
                </span>
              </td>
              <td class="sh-date">
                {{ new Date(item.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination controls -->
        <div class="sh-pagination">
          <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="sh-paginate-btn">
            <span class="material-symbols-outlined">chevron_left</span> Previous
          </button>
          <div class="sh-pagination-info">
            Page {{ currentPage }} of {{ lastPage }}
          </div>
          <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage" class="sh-paginate-btn">
            Next <span class="material-symbols-outlined">chevron_right</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.sh-page {
  animation: pageIn 0.4s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Header */
.sh-header { margin-bottom: 2rem; }
.sh-back {
  display: inline-flex; align-items: center; gap: 0.4rem;
  color: #A1887F; text-decoration: none; font-size: 0.85rem; font-weight: 600;
  margin-bottom: 1.25rem; transition: color 0.2s;
}
.sh-back:hover { color: #5D4037; }
.sh-back .material-symbols-outlined { font-size: 18px; }

.sh-title-row { display: flex; align-items: center; gap: 1rem; }
.sh-icon {
  width: 52px; height: 52px; border-radius: 16px;
  background: #3E2723;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 20px rgba(62,39,35,0.22);
}
.sh-icon .material-symbols-outlined { color: #fff; font-size: 26px; }

.sh-title { font-size: 1.6rem; font-weight: 800; color: #3E2723; margin: 0; letter-spacing: -0.03em; }
.sh-subtitle { font-size: 0.85rem; color: #A1887F; margin: 0; }

/* Table Container */
.sh-table-container {
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 20px;
  overflow: hidden; box-shadow: 0 4px 20px rgba(93,64,55,0.06);
}
.sh-table-head {
  padding: 1.25rem 1.75rem; border-bottom: 1px solid #F3EDE9;
  display: flex; align-items: center; justify-content: space-between;
}
.sh-table-title {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.95rem; font-weight: 800; color: #3E2723;
}
.sh-table-title .material-symbols-outlined { font-size: 18px; color: #8D6E63; }

.sh-refresh {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; background: #FAF8F6; border: 1.5px solid #EDE8E4;
  border-radius: 10px; font-size: 0.78rem; font-weight: 700; color: #5D4037;
  cursor: pointer; font-family: inherit; transition: all 0.2s;
}
.sh-refresh:hover { background: #fff; border-color: #D7CCC8; }

.sh-table-wrap { overflow-x: auto; }
.sh-table { width: 100%; border-collapse: collapse; }
.sh-table th {
  padding: 1rem 1.75rem; background: #FAF8F6; text-align: left;
  font-size: 0.7rem; font-weight: 700; color: #795548; text-transform: uppercase; letter-spacing: 0.08em;
  border-bottom: 1px solid #EDE8E4;
}
.sh-table td { padding: 1.1rem 1.75rem; border-bottom: 1px solid #F8F5F2; vertical-align: middle; }
.sh-table tbody tr:hover td { background: #FFFAF8; }

/* Event Badges */
.sh-event-tag {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.3rem 0.65rem; border-radius: 8px; font-size: 0.75rem; font-weight: 700;
}
.sh-event-tag .material-symbols-outlined { font-size: 14px; }

.act-add { background: #DCFCE7; color: #166534; }
.act-adj { background: #FEF3C7; color: #92400E; }
.act-in  { background: #DBEAFE; color: #1E40AF; }
.act-out { background: #FEE2E2; color: #991B1B; }

/* Content */
.sh-prod-name { font-size: 0.9rem; font-weight: 700; color: #3E2723; margin: 0; }
.sh-prod-sku  { font-size: 0.72rem; color: #A1887F; font-family: monospace; margin: 0; }

.sh-branch { font-size: 0.85rem; font-weight: 600; color: #5D4037; display: flex; align-items: center; gap: 0.35rem; }
.sh-branch .material-symbols-outlined { font-size: 16px; color: #BCAAA4; }

.sh-qty { font-size: 0.95rem; font-weight: 800; }
.qty-pos { color: #166534; }
.qty-neg { color: #991B1B; }

.sh-date { font-size: 0.78rem; color: #A1887F; font-weight: 500; }

.sh-empty { padding: 5rem 2rem; text-align: center; }
.sh-empty-icon { font-size: 50px; color: #D7CCC8; margin-bottom: 1rem; }
.sh-empty h3 { font-size: 1.1rem; color: #3E2723; margin: 0 0 0.4rem; }
.sh-empty p { font-size: 0.85rem; color: #A1887F; margin: 0; }

.sh-error { padding: 2rem; text-align: center; color: #dc2626; font-size: 0.9rem; }
.retry-btn { margin-left: 0.5rem; text-decoration: underline; background: none; border: none; font-weight: 600; cursor: pointer; color: #dc2626; }

/* Pagination */
.sh-pagination {
  display: flex; align-items: center; justify-content: center;
  gap: 1.5rem; padding: 1.5rem 1.75rem; border-top: 1px solid #F3EDE9;
  background: #FAF9F7;
}
.sh-paginate-btn {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 1rem; background: #fff; border: 1.5px solid #E0D7D0;
  border-radius: 10px; font-weight: 700; font-size: 0.78rem; color: #3E2723;
  cursor: pointer; transition: all 0.2s; outline: none; font-family: inherit;
}
.sh-paginate-btn:hover:not(:disabled) { border-color: #8D6E63; background: #FAF8F6; }
.sh-paginate-btn:disabled {
  opacity: 0.4; cursor: not-allowed;
}
.sh-pagination-info {
  font-size: 0.78rem; font-weight: 700; color: #5D4037;
  padding: 0 1rem;
}

@media (max-width: 768px) {
  .sh-pagination { flex-direction: column; gap: 1rem; padding: 1rem; }
  .sh-paginate-btn { width: 100%; justify-content: center; }
  .sh-pagination-info { width: 100%; text-align: center; }
}
</style>