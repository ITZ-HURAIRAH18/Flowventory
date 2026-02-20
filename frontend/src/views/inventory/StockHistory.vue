<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/inventoryService'

const history = ref([])
const loading = ref(false)
const error = ref(null)

const fetchHistory = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await api.history()
    history.value = res.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load stock history'
  }
  loading.value = false
}

// Format action type for display
const formatAction = (type) => {
  const labels = {
    add: 'Added',
    adjust: 'Adjusted',
    transfer_in: 'Transfer In',
    transfer_out: 'Transfer Out'
  }
  return labels[type] || type
}

const actionClass = (type) => {
  const map = {
    add: 'action-add',
    adjust: 'action-adjust',
    transfer_in: 'action-in',
    transfer_out: 'action-out'
  }
  return map[type] || ''
}

const actionIcon = (type) => {
  const map = {
    add: 'add_circle',
    adjust: 'tune',
    transfer_in: 'arrow_downward',
    transfer_out: 'arrow_upward'
  }
  return map[type] || 'swap_horiz'
}

onMounted(fetchHistory)
</script>

<template>
  <div class="history-page">
    <!-- Header -->
    <div class="page-header">
      <router-link to="/inventory" class="back-link">
        <span class="material-symbols-outlined">arrow_back</span>
        Back to Inventory
      </router-link>
      <h1>
        <span class="material-symbols-outlined page-icon">history</span>
        Stock Movement History
      </h1>
      <p class="page-sub">Complete audit trail of all stock changes</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <span class="material-symbols-outlined spin">progress_activity</span>
      Loading history...
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error-state">
      <span class="material-symbols-outlined error-icon">error</span>
      <p>{{ error }}</p>
      <button class="retry-btn" @click="fetchHistory">
        <span class="material-symbols-outlined">refresh</span>
        Retry
      </button>
    </div>

    <!-- Empty -->
    <div v-else-if="history.length === 0" class="empty-state">
      <span class="material-symbols-outlined empty-icon">history</span>
      <p>No stock movements recorded yet</p>
    </div>

    <!-- History Table -->
    <div v-else class="table-section">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Action</th>
              <th>Product</th>
              <th>Branch</th>
              <th>Quantity</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in history" :key="item.id">
              <td>
                <span class="action-badge" :class="actionClass(item.action)">
                  <span class="material-symbols-outlined">{{ actionIcon(item.action) }}</span>
                  {{ formatAction(item.action) }}
                </span>
              </td>
              <td class="product-name">{{ item.product.name }}</td>
              <td>{{ item.branch.name }}</td>
              <td>
                <span class="qty" :class="{ 'qty-positive': item.quantity > 0, 'qty-negative': item.quantity < 0 }">
                  {{ item.quantity > 0 ? '+' : '' }}{{ item.quantity }}
                </span>
              </td>
              <td class="date-cell">{{ new Date(item.created_at).toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.history-page {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ── Header ── */
.page-header {
  margin-bottom: 1.5rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #818cf8;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 0.75rem;
  transition: color 0.2s;
}

.back-link:hover {
  color: #a5b4fc;
}

.back-link .material-symbols-outlined {
  font-size: 18px;
}

.page-header h1 {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 1.6rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 0.3rem;
}

.page-icon {
  font-size: 28px;
  color: #a78bfa;
}

.page-sub {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0;
}

/* ── Loading / Empty / Error ── */
.loading-state,
.empty-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 5rem 1rem;
  color: #64748b;
  font-size: 0.95rem;
}

.empty-icon {
  font-size: 48px;
  color: #334155;
}

.error-icon {
  font-size: 48px;
  color: #ef4444;
}

.retry-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1.2rem;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.05);
  color: #e2e8f0;
  cursor: pointer;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.retry-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
  font-size: 22px;
}

/* ── Table ── */
.table-section {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 14px;
  padding: 1.25rem;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  padding: 0.65rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

td {
  padding: 0.75rem 1rem;
  font-size: 0.88rem;
  color: #cbd5e1;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

tr:hover td {
  background: rgba(255, 255, 255, 0.02);
}

.product-name {
  font-weight: 500;
  color: #e2e8f0;
}

.date-cell {
  color: #64748b;
  font-size: 0.82rem;
}

/* ── Action badges ── */
.action-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.2rem 0.6rem;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 600;
}

.action-badge .material-symbols-outlined {
  font-size: 16px;
}

.action-add {
  background: rgba(16, 185, 129, 0.1);
  color: #34d399;
}

.action-adjust {
  background: rgba(251, 191, 36, 0.1);
  color: #fbbf24;
}

.action-in {
  background: rgba(59, 130, 246, 0.1);
  color: #60a5fa;
}

.action-out {
  background: rgba(239, 68, 68, 0.1);
  color: #f87171;
}

/* ── Quantity ── */
.qty {
  font-weight: 600;
  font-size: 0.9rem;
}

.qty-positive {
  color: #34d399;
}

.qty-negative {
  color: #f87171;
}

/* ── Responsive ── */
@media (max-width: 640px) {
  .page-header h1 {
    font-size: 1.3rem;
  }
}
</style>