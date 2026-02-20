<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import Chart from 'chart.js/auto'

const router = useRouter()
const branches = ref([])
const loading = ref(false)
const user = ref(null)
const search = ref('')

// Summary report data
const summary = ref(null)
const summaryLoading = ref(false)
const summaryError = ref(null)
const salesChart = ref(null)

const filteredBranches = computed(() => {
  if (!search.value.trim()) return branches.value
  const q = search.value.toLowerCase()
  return branches.value.filter(b =>
    b.name.toLowerCase().includes(q) ||
    (b.address && b.address.toLowerCase().includes(q))
  )
})

const roleLabel = computed(() => {
  if (!user.value?.role) return 'Your'
  return user.value.role.name === 'super_admin' ? 'All Branches' : 'My Branches'
})

onMounted(async () => {
  const stored = localStorage.getItem('user')
  if (stored) user.value = JSON.parse(stored)

  loading.value = true
  summaryLoading.value = true

  try {
    // Fetch branches and summary in parallel
    const [branchRes, summaryRes] = await Promise.all([
      api.get('/my-branches'),
      api.get('/report/summary')
    ])
    branches.value = branchRes.data
    summary.value = summaryRes.data
  } catch (error) {
    console.error('Failed to load data:', error)
    summaryError.value = error.response?.data?.message || 'Failed to load summary'
  }

  loading.value = false
  summaryLoading.value = false

  // Render chart after DOM update
  await nextTick()
  renderChart()
})

function renderChart() {
  if (!salesChart.value || !summary.value) return

  new Chart(salesChart.value, {
    type: 'bar',
    data: {
      labels: ['Today Sales', 'Monthly Sales'],
      datasets: [
        {
          label: 'Sales ($)',
          data: [
            summary.value.today_sales,
            summary.value.monthly_sales
          ],
          backgroundColor: ['rgba(34, 211, 238, 0.7)', 'rgba(34, 211, 238, 0.3)'],
          borderColor: ['#22d3ee', '#22d3ee'],
          borderWidth: 1,
          borderRadius: 6,
          barPercentage: 0.5,
          categoryPercentage: 0.6
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1a1a1a',
          titleColor: '#ffffff',
          bodyColor: '#cccccc',
          borderColor: 'rgba(255,255,255,0.1)',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8,
          callbacks: {
            label: function(context) {
              return `$${Number(context.raw).toLocaleString()}`
            }
          }
        }
      },
      scales: {
        x: {
          ticks: { color: '#888888', font: { size: 13, weight: '500' } },
          grid: { display: false },
          border: { display: false }
        },
        y: {
          ticks: {
            color: '#666666',
            font: { size: 12 },
            callback: function(value) { return '$' + value.toLocaleString() }
          },
          grid: { color: 'rgba(255,255,255,0.04)' },
          border: { display: false }
        }
      }
    }
  })
}

const viewReport = (branchId) => {
  router.push(`/branches/${branchId}/report`)
}
</script>

<template>
  <div class="reports-page">
    <!-- Page Header -->
    <div class="page-header">
      <h1>
        <span class="material-symbols-outlined page-icon">bar_chart</span>
        Reports
      </h1>
      <p class="page-sub">Overview across {{ roleLabel }} — click a branch below to drill down</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <span class="material-symbols-outlined spin">progress_activity</span>
      Loading report data...
    </div>

    <template v-else>
      <!-- ═══════════════════════════════ -->
      <!-- Summary Dashboard              -->
      <!-- ═══════════════════════════════ -->
      <div v-if="summary" class="summary-section">
        <!-- Summary Cards -->
        <div class="summary-grid">
          <div class="summary-card" style="--delay: 0.05s">
            <div class="card-icon-wrap icon-cyan">
              <span class="material-symbols-outlined">point_of_sale</span>
            </div>
            <div class="card-info">
              <span class="card-label">Today Sales</span>
              <span class="card-value">${{ Number(summary.today_sales).toLocaleString() }}</span>
            </div>
          </div>

          <div class="summary-card" style="--delay: 0.1s">
            <div class="card-icon-wrap icon-cyan">
              <span class="material-symbols-outlined">calendar_month</span>
            </div>
            <div class="card-info">
              <span class="card-label">Monthly Sales</span>
              <span class="card-value">${{ Number(summary.monthly_sales).toLocaleString() }}</span>
            </div>
          </div>

          <div class="summary-card" style="--delay: 0.15s">
            <div class="card-icon-wrap icon-cyan">
              <span class="material-symbols-outlined">receipt_long</span>
            </div>
            <div class="card-info">
              <span class="card-label">Total Orders</span>
              <span class="card-value">{{ summary.total_orders }}</span>
            </div>
          </div>
        </div>

        <!-- Chart -->
        <div class="section-card chart-section" style="--delay: 0.2s">
          <h3 class="section-title">
            <span class="material-symbols-outlined">bar_chart</span>
            Sales Overview
          </h3>
          <div class="chart-container">
            <canvas ref="salesChart"></canvas>
          </div>
        </div>

        <!-- Two Column: Top Products + Low Stock -->
        <div class="bottom-grid">
          <div class="section-card" style="--delay: 0.25s">
            <h3 class="section-title">
              <span class="material-symbols-outlined">trending_up</span>
              Top 5 Products
            </h3>
            <div v-if="summary.top_products && summary.top_products.length" class="product-list">
              <div
                v-for="(product, index) in summary.top_products"
                :key="product.name"
                class="product-row"
              >
                <div class="product-rank">{{ index + 1 }}</div>
                <span class="product-name">{{ product.name }}</span>
                <span class="product-sold">{{ product.total_sold }} sold</span>
              </div>
            </div>
            <div v-else class="empty-section">
              <span class="material-symbols-outlined">inventory_2</span>
              <p>No product data yet</p>
            </div>
          </div>

          <div class="section-card" style="--delay: 0.3s">
            <h3 class="section-title warning-title">
              <span class="material-symbols-outlined">warning</span>
              Low Stock Items
            </h3>
            <div v-if="summary.low_stock && summary.low_stock.length" class="stock-list">
              <div
                v-for="item in summary.low_stock"
                :key="item.id"
                class="stock-row"
              >
                <div class="stock-info">
                  <span class="stock-name">{{ item.product?.name || 'Unknown' }}</span>
                  <span v-if="item.branch" class="stock-branch">{{ item.branch.name }}</span>
                </div>
                <span class="stock-qty" :class="{ 'critical': item.quantity <= 3 }">
                  {{ item.quantity }} left
                </span>
              </div>
            </div>
            <div v-else class="empty-section">
              <span class="material-symbols-outlined">check_circle</span>
              <p>All items stocked</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Error loading summary -->
      <div v-else-if="summaryError" class="error-banner">
        <span class="material-symbols-outlined">error</span>
        {{ summaryError }}
      </div>

      <!-- ═══════════════════════════════ -->
      <!-- Branch Drill-Down Section      -->
      <!-- ═══════════════════════════════ -->
      <div class="drill-down-section" v-if="branches.length > 0">
        <h2 class="section-heading">
          <span class="material-symbols-outlined">store</span>
          Branch Reports
          <span class="branch-count">{{ branches.length }}</span>
        </h2>

        <!-- Search Bar -->
        <div class="search-bar">
          <span class="material-symbols-outlined search-icon">search</span>
          <input
            v-model="search"
            placeholder="Search branches by name or address..."
            class="search-input"
          />
          <span v-if="search" class="result-count">{{ filteredBranches.length }} found</span>
        </div>

        <!-- No search results -->
        <div v-if="filteredBranches.length === 0" class="empty-state small-empty">
          <span class="material-symbols-outlined empty-icon">search_off</span>
          <p>No branches match "{{ search }}"</p>
        </div>

        <!-- Branch Cards -->
        <div v-else class="branch-grid">
          <div
            v-for="branch in filteredBranches"
            :key="branch.id"
            class="branch-card"
            @click="viewReport(branch.id)"
          >
            <div class="branch-card-icon">
              <span class="material-symbols-outlined">store</span>
            </div>
            <div class="branch-card-info">
              <span class="branch-name">{{ branch.name }}</span>
              <span class="branch-address">{{ branch.address || 'No address' }}</span>
            </div>
            <span class="material-symbols-outlined arrow">chevron_right</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.reports-page {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}

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
  color: #22d3ee;
}

.page-sub {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0;
}

/* Loading / Empty */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 4rem 1rem;
  color: #64748b;
  font-size: 0.95rem;
}

.small-empty {
  padding: 2rem 1rem;
}

.empty-icon {
  font-size: 48px;
  color: #334155;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
  font-size: 24px;
}

/* ═══════════════════════════════ */
/* Summary Dashboard               */
/* ═══════════════════════════════ */
.summary-section {
  margin-bottom: 2.5rem;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.3rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 14px;
  transition: all 0.25s ease;
  animation: slideUp 0.5s ease both;
  animation-delay: var(--delay);
}

.summary-card:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.12);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.card-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  flex-shrink: 0;
}

.icon-cyan {
  background: rgba(6, 182, 212, 0.1);
  color: #22d3ee;
}

.card-icon-wrap .material-symbols-outlined {
  font-size: 24px;
}

.card-info {
  display: flex;
  flex-direction: column;
}

.card-label {
  font-size: 0.78rem;
  font-weight: 500;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.card-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f8fafc;
  line-height: 1.2;
}

/* Section Cards */
.section-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 14px;
  padding: 1.5rem;
  animation: slideUp 0.5s ease both;
  animation-delay: var(--delay);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: #e2e8f0;
  margin: 0 0 1.2rem;
}

.section-title .material-symbols-outlined {
  font-size: 20px;
  color: #94a3b8;
}

.warning-title .material-symbols-outlined {
  color: #f59e0b;
}

/* Chart */
.chart-section {
  margin-bottom: 0.75rem;
}

.chart-container {
  position: relative;
  height: 220px;
}

/* Bottom Grid */
.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

/* Product List */
.product-list {
  display: flex;
  flex-direction: column;
}

.product-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.product-row:last-child {
  border-bottom: none;
}

.product-rank {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.06);
  color: #94a3b8;
  font-size: 0.8rem;
  font-weight: 700;
  flex-shrink: 0;
}

.product-name {
  flex: 1;
  font-size: 0.9rem;
  color: #e2e8f0;
  font-weight: 500;
}

.product-sold {
  font-size: 0.8rem;
  font-weight: 600;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.04);
  padding: 0.25rem 0.6rem;
  border-radius: 6px;
}

/* Stock List */
.stock-list {
  display: flex;
  flex-direction: column;
}

.stock-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.7rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.stock-row:last-child {
  border-bottom: none;
}

.stock-info {
  display: flex;
  flex-direction: column;
}

.stock-name {
  font-size: 0.9rem;
  color: #e2e8f0;
  font-weight: 500;
}

.stock-branch {
  font-size: 0.75rem;
  color: #64748b;
}

.stock-qty {
  font-size: 0.8rem;
  font-weight: 600;
  color: #f59e0b;
  background: rgba(245, 158, 11, 0.08);
  padding: 0.25rem 0.6rem;
  border-radius: 6px;
}

.stock-qty.critical {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.08);
}

/* Empty Section */
.empty-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 2rem 1rem;
  color: #475569;
  font-size: 0.85rem;
}

.empty-section .material-symbols-outlined {
  font-size: 32px;
  color: #334155;
}

/* Error Banner */
.error-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: 8px;
  color: #f87171;
  font-size: 0.88rem;
  font-weight: 500;
  margin-bottom: 2rem;
}

.error-banner .material-symbols-outlined {
  font-size: 20px;
}

/* ═══════════════════════════════ */
/* Drill-Down Section              */
/* ═══════════════════════════════ */
.drill-down-section {
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 2rem;
}

.section-heading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.15rem;
  font-weight: 600;
  color: #e2e8f0;
  margin: 0 0 1.25rem;
}

.section-heading .material-symbols-outlined {
  font-size: 22px;
  color: #22d3ee;
}

.branch-count {
  font-size: 0.72rem;
  font-weight: 600;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.15rem 0.5rem;
  border-radius: 6px;
  margin-left: 0.25rem;
}

/* Search Bar */
.search-bar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  margin-bottom: 1.25rem;
  transition: border-color 0.2s;
}

.search-bar:focus-within {
  border-color: rgba(34, 211, 238, 0.35);
}

.search-icon {
  font-size: 20px;
  color: #64748b;
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #e2e8f0;
  font-size: 0.9rem;
}

.search-input::placeholder {
  color: #475569;
}

.result-count {
  font-size: 0.75rem;
  font-weight: 600;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.2rem 0.6rem;
  border-radius: 6px;
  white-space: nowrap;
}

/* Branch Grid */
.branch-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 0.75rem;
}

.branch-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.15rem 1.25rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.25s ease;
}

.branch-card:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(34, 211, 238, 0.25);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
}

.branch-card-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: rgba(6, 182, 212, 0.1);
  color: #22d3ee;
  flex-shrink: 0;
}

.branch-card-icon .material-symbols-outlined {
  font-size: 22px;
}

.branch-card-info {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.branch-name {
  font-size: 0.95rem;
  font-weight: 600;
  color: #e2e8f0;
}

.branch-address {
  font-size: 0.8rem;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.arrow {
  font-size: 20px;
  color: #475569;
  transition: color 0.2s, transform 0.2s;
}

.branch-card:hover .arrow {
  color: #22d3ee;
  transform: translateX(3px);
}

/* Responsive */
@media (max-width: 900px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }
  .bottom-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .branch-grid {
    grid-template-columns: 1fr;
  }
  .page-header h1 {
    font-size: 1.3rem;
  }
  .card-value {
    font-size: 1.2rem;
  }
}
</style>
