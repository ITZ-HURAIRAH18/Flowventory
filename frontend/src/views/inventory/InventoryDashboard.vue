<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/inventoryService'

const inventory = ref([])
const loading = ref(false)

const fetchInventory = async () => {
  loading.value = true
  try {
    const res = await api.getAll()
    inventory.value = res.data
  } catch (error) {
    console.error('Failed to load inventory:', error)
  }
  loading.value = false
}

onMounted(fetchInventory)
</script>

<template>
  <div class="inventory-page">

    <!-- Page Header -->
    <div class="page-header">
      <h1>
        <span class="material-symbols-outlined page-icon">warehouse</span>
        Inventory Management
      </h1>
      <p class="page-sub">Manage stock across all branches</p>
    </div>

    <!-- Quick Action Cards -->
    <div class="action-nav">
      <router-link to="/inventory/add" class="action-nav-card">
        <span class="material-symbols-outlined card-icon icon-green">add_circle</span>
        <div class="card-content">
          <span class="card-title">Add Stock</span>
          <span class="card-desc">Add new stock to a branch</span>
        </div>
        <span class="material-symbols-outlined arrow">chevron_right</span>
      </router-link>

      <router-link to="/inventory/adjust" class="action-nav-card">
        <span class="material-symbols-outlined card-icon icon-orange">tune</span>
        <div class="card-content">
          <span class="card-title">Adjust Stock</span>
          <span class="card-desc">Correct stock quantities</span>
        </div>
        <span class="material-symbols-outlined arrow">chevron_right</span>
      </router-link>

      <router-link to="/inventory/transfer" class="action-nav-card">
        <span class="material-symbols-outlined card-icon icon-blue">swap_horiz</span>
        <div class="card-content">
          <span class="card-title">Transfer Stock</span>
          <span class="card-desc">Move stock between branches</span>
        </div>
        <span class="material-symbols-outlined arrow">chevron_right</span>
      </router-link>

      <router-link to="/inventory/history" class="action-nav-card">
        <span class="material-symbols-outlined card-icon icon-purple">history</span>
        <div class="card-content">
          <span class="card-title">Stock History</span>
          <span class="card-desc">View movement logs</span>
        </div>
        <span class="material-symbols-outlined arrow">chevron_right</span>
      </router-link>
    </div>

    <!-- Inventory Table -->
    <div class="table-section">
      <h2 class="section-title">Current Stock Levels</h2>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <span class="material-symbols-outlined spin">progress_activity</span>
        Loading inventory...
      </div>

      <!-- Empty State -->
      <div v-else-if="inventory.length === 0" class="empty-state">
        <span class="material-symbols-outlined empty-icon">inventory_2</span>
        <p>No inventory data found</p>
      </div>

      <!-- Table -->
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Product</th>
              <th>Branch</th>
              <th>Stock</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in inventory" :key="item.id">
              <td class="product-name">{{ item.product.name }}</td>
              <td>{{ item.branch.name }}</td>
              <td>
                <span class="stock-count" :class="{
                  'stock-low': item.stock > 0 && item.stock <= 5,
                  'stock-out': item.stock <= 0,
                  'stock-ok': item.stock > 5
                }">
                  {{ item.stock }}
                </span>
              </td>
              <td>
                <span class="status-badge" :class="{
                  'status-available': item.stock > 5,
                  'status-low': item.stock > 0 && item.stock <= 5,
                  'status-out': item.stock <= 0
                }">
                  {{ item.stock > 5 ? 'In Stock' : item.stock > 0 ? 'Low Stock' : 'Out of Stock' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.inventory-page {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ─── Page Header ─── */
.page-header {
  margin-bottom: 2rem;
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
  color: #818cf8;
}

.page-sub {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0;
}

/* ─── Action Navigation Cards ─── */
.action-nav {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 0.75rem;
  margin-bottom: 2rem;
}

.action-nav-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 1rem 1.15rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  text-decoration: none;
  color: inherit;
  transition: all 0.25s ease;
  cursor: pointer;
}

.action-nav-card:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(99, 102, 241, 0.25);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
}

.card-icon {
  font-size: 26px;
  padding: 0.55rem;
  border-radius: 10px;
  flex-shrink: 0;
}

.icon-green {
  color: #34d399;
  background: rgba(16, 185, 129, 0.1);
}

.icon-orange {
  color: #fb923c;
  background: rgba(249, 115, 22, 0.1);
}

.icon-blue {
  color: #60a5fa;
  background: rgba(59, 130, 246, 0.1);
}

.icon-purple {
  color: #a78bfa;
  background: rgba(139, 92, 246, 0.1);
}

.card-content {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.card-title {
  font-size: 0.92rem;
  font-weight: 600;
  color: #e2e8f0;
}

.card-desc {
  font-size: 0.75rem;
  color: #64748b;
}

.arrow {
  font-size: 20px;
  color: #475569;
  transition: color 0.2s, transform 0.2s;
}

.action-nav-card:hover .arrow {
  color: #818cf8;
  transform: translateX(3px);
}

/* ─── Table Section ─── */
.table-section {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 14px;
  padding: 1.25rem;
}

.section-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: #cbd5e1;
  margin: 0 0 1rem;
}

/* ─── Loading / Empty state ─── */
.loading-state,
.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 3rem 1rem;
  color: #64748b;
  font-size: 0.9rem;
}

.empty-icon {
  font-size: 40px;
  color: #334155;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
  font-size: 22px;
}

/* ─── Table ─── */
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

/* ─── Stock Count ─── */
.stock-count {
  font-weight: 600;
  font-size: 0.9rem;
}

.stock-ok { color: #34d399; }
.stock-low { color: #fbbf24; }
.stock-out { color: #f87171; }

/* ─── Status Badges ─── */
.status-badge {
  display: inline-block;
  padding: 0.2rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.status-available {
  background: rgba(16, 185, 129, 0.1);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.status-low {
  background: rgba(245, 158, 11, 0.1);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.status-out {
  background: rgba(239, 68, 68, 0.1);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

/* ─── Responsive ─── */
@media (max-width: 640px) {
  .action-nav {
    grid-template-columns: 1fr;
  }

  .page-header h1 {
    font-size: 1.3rem;
  }
}
</style>