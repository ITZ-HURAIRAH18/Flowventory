<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/inventoryService'
import LoadingScreen from '@/components/LoadingScreen.vue'

const history = ref([])
const loading = ref(true)
const error   = ref(null)
const currentPage = ref(1)
const perPage = ref(5)
const totalItems = ref(0)
const lastPage = ref(1)
const itemsFrom = ref(0)
const itemsTo = ref(0)

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
    itemsFrom.value = res.data.from ?? 0
    itemsTo.value = res.data.to ?? 0
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

        <!-- Enhanced Pagination controls -->
        <div class="sh-pagination">
          <div class="sh-pagination-info">
            Showing {{ itemsFrom }} to {{ itemsTo }} of {{ totalItems }} history records
          </div>
          <div class="sh-pagination-buttons">
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="sh-paginate-btn">
              <span class="material-symbols-outlined">chevron_left</span> Previous
            </button>
            
            <div class="sh-page-numbers">
              <template v-for="page in Math.min(5, lastPage)" :key="page">
                <button 
                  v-if="page <= lastPage"
                  class="sh-page-number" 
                  :class="{ active: page === currentPage }"
                  @click="goToPage(page)"
                >
                  {{ page }}
                </button>
              </template>
              <span v-if="lastPage > 5" class="sh-pages-ellipsis">...</span>
              <button 
                v-if="lastPage > 5 && currentPage < lastPage - 2"
                class="sh-page-number" 
                @click="goToPage(lastPage)"
              >
                {{ lastPage }}
              </button>
            </div>
            
            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage" class="sh-paginate-btn">
              Next <span class="material-symbols-outlined">chevron_right</span>
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
@import "../../styles/views/StockHistory.css";
</style>