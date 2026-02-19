<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import Chart from 'chart.js/auto'

const route = useRoute()
const router = useRouter()
const branchId = route.params.branchId || route.params.id
const report = ref({
  today_sales: 0,
  monthly_sales: 0,
  total_orders: 0,
  top_products: [],
  low_stock: []
})
const loading = ref(true)
const error = ref(null)
const salesChart = ref(null)

onMounted(async () => {
  try {
    const res = await api.get(`/branches/${branchId}/report`)
    report.value = res.data

    // First set loading to false so the canvas element appears in the DOM
    loading.value = false

    // Wait for Vue to update the DOM, then render the chart
    await nextTick()
    renderChart()
  } catch (err) {
    console.error('Failed to load report:', err)
    error.value = err.response?.data?.message || 'Failed to load report data'
    loading.value = false
  }
})

function renderChart() {
  if (!salesChart.value) return

  new Chart(salesChart.value, {
    type: 'bar',
    data: {
      labels: ['Today Sales', 'Monthly Sales'],
      datasets: [
        {
          label: 'Sales ($)',
          data: [
            report.value.today_sales,
            report.value.monthly_sales
          ],
          backgroundColor: ['rgba(255, 255, 255, 0.85)', 'rgba(255, 255, 255, 0.45)'],
          borderColor: ['#ffffff', '#999999'],
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
        legend: {
          display: false
        },
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
            callback: function(value) {
              return '$' + value.toLocaleString()
            }
          },
          grid: { color: 'rgba(255,255,255,0.04)' },
          border: { display: false }
        }
      }
    }
  })
}

function goBack() {
  router.push('/reports')
}
</script>

<template>
  <div class="report-page">
    <!-- Page Header -->
    <div class="page-header">
      <button class="back-btn" @click="goBack">
        <span class="material-symbols-outlined">arrow_back</span>
      </button>
      <div>
        <h1>
          <span class="material-symbols-outlined page-icon">analytics</span>
          Branch Report
        </h1>
        <p class="page-sub">Sales performance, top products, and stock overview</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <span class="material-symbols-outlined spin">progress_activity</span>
      Loading report data...
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error-state">
      <span class="material-symbols-outlined error-icon">error</span>
      <p>{{ error }}</p>
      <button class="retry-btn" @click="$router.go(0)">
        <span class="material-symbols-outlined">refresh</span>
        Retry
      </button>
    </div>

    <!-- Report Content -->
    <div v-else class="report-content">
      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card" style="--delay: 0.05s">
          <div class="card-icon-wrap">
            <span class="material-symbols-outlined">point_of_sale</span>
          </div>
          <div class="card-info">
            <span class="card-label">Today Sales</span>
            <span class="card-value">${{ Number(report.today_sales).toLocaleString() }}</span>
          </div>
        </div>

        <div class="summary-card" style="--delay: 0.1s">
          <div class="card-icon-wrap">
            <span class="material-symbols-outlined">calendar_month</span>
          </div>
          <div class="card-info">
            <span class="card-label">Monthly Sales</span>
            <span class="card-value">${{ Number(report.monthly_sales).toLocaleString() }}</span>
          </div>
        </div>

        <div class="summary-card" style="--delay: 0.15s">
          <div class="card-icon-wrap">
            <span class="material-symbols-outlined">receipt_long</span>
          </div>
          <div class="card-info">
            <span class="card-label">Total Orders</span>
            <span class="card-value">{{ report.total_orders }}</span>
          </div>
        </div>
      </div>

      <!-- Chart Section -->
      <div class="section-card chart-section" style="--delay: 0.2s">
        <h3 class="section-title">
          <span class="material-symbols-outlined">bar_chart</span>
          Sales Overview
        </h3>
        <div class="chart-container">
          <canvas ref="salesChart"></canvas>
        </div>
      </div>

      <!-- Two-Column Bottom -->
      <div class="bottom-grid">
        <!-- Top Products -->
        <div class="section-card" style="--delay: 0.25s">
          <h3 class="section-title">
            <span class="material-symbols-outlined">trending_up</span>
            Top 5 Products
          </h3>
          <div v-if="report.top_products && report.top_products.length" class="product-list">
            <div
              v-for="(product, index) in report.top_products"
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

        <!-- Low Stock -->
        <div class="section-card" style="--delay: 0.3s">
          <h3 class="section-title warning-title">
            <span class="material-symbols-outlined">warning</span>
            Low Stock Items
          </h3>
          <div v-if="report.low_stock && report.low_stock.length" class="stock-list">
            <div
              v-for="item in report.low_stock"
              :key="item.id"
              class="stock-row"
            >
              <span class="stock-name">{{ item.product?.name || 'Unknown' }}</span>
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
  </div>
</template>

<style scoped>
.report-page {
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

/* ── Header ── */
.page-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.back-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.04);
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.back-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.15);
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
  color: #ffffff;
}

.page-sub {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0;
}

/* ── Loading / Error ── */
.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 6rem 1rem;
  color: #64748b;
  font-size: 0.95rem;
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
  font-size: 24px;
}

/* ── Summary Cards ── */
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
  background: rgba(255, 255, 255, 0.06);
  color: #ffffff;
  flex-shrink: 0;
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

/* ── Section Cards ── */
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

/* ── Chart ── */
.chart-section {
  margin-bottom: 0.75rem;
}

.chart-container {
  position: relative;
  height: 260px;
}

/* ── Bottom Grid ── */
.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

/* ── Product List ── */
.product-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.product-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  transition: background 0.15s;
}

.product-row:last-child {
  border-bottom: none;
}

.product-row:hover {
  background: rgba(255, 255, 255, 0.02);
  margin: 0 -0.5rem;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
  border-radius: 8px;
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

/* ── Stock List ── */
.stock-list {
  display: flex;
  flex-direction: column;
}

.stock-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.stock-row:last-child {
  border-bottom: none;
}

.stock-name {
  font-size: 0.9rem;
  color: #e2e8f0;
  font-weight: 500;
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

/* ── Empty Section ── */
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

/* ── Responsive ── */
@media (max-width: 900px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }
  .bottom-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .page-header h1 {
    font-size: 1.3rem;
  }
  .card-value {
    font-size: 1.2rem;
  }
}
</style>