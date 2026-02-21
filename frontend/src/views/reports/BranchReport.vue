<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import Chart from 'chart.js/auto'
import LoadingScreen from '@/components/LoadingScreen.vue'

const route = useRoute()
const router = useRouter()
const branchId = route.params.branchId || route.params.id

const report = ref({
  today_sales: 0,
  monthly_sales: 0,
  total_orders: 0,
  top_products: [],
  low_stock: [],
  branch: null
})

const loading = ref(true)
const error = ref(null)
const salesChart = ref(null)

onMounted(async () => {
  try {
    const res = await api.get(`/branches/${branchId}/report`)
    report.value = res.data

    loading.value = false
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

  const ctx = salesChart.value.getContext('2d')
  const gradient = ctx.createLinearGradient(0, 0, 0, 400)
  gradient.addColorStop(0, 'rgba(62, 39, 35, 0.95)')
  gradient.addColorStop(1, 'rgba(62, 39, 35, 0.3)')

  new Chart(salesChart.value, {
    type: 'bar',
    data: {
      labels: ['Daily Revenue', 'Monthly Revenue'],
      datasets: [
        {
          label: 'Revenue ($)',
          data: [
            report.value.today_sales,
            report.value.monthly_sales
          ],
          backgroundColor: gradient,
          borderColor: '#3E2723',
          borderWidth: 2,
          borderRadius: 14,
          barPercentage: 0.45,
          categoryPercentage: 0.5
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#3E2723',
          titleColor: '#fff',
          bodyColor: '#D7CCC8',
          padding: 14,
          cornerRadius: 12,
          displayColors: false,
          callbacks: {
            label: (ctx) => `Revenue: $${Number(ctx.raw).toLocaleString()}`
          }
        }
      },
      scales: {
        x: {
          ticks: { color: '#8D6E63', font: { family: 'Outfit', size: 13, weight: '600' } },
          grid: { display: false },
          border: { display: false }
        },
        y: {
          ticks: {
            color: '#A1887F',
            font: { family: 'Outfit', size: 12 },
            callback: (val) => '$' + val.toLocaleString()
          },
          grid: { color: 'rgba(215, 204, 200, 0.25)', drawTicks: false },
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
  <div class="br-page">
    
    <!-- ══════ HEADER ══════ -->
    <div class="br-header">
      <button class="br-back-btn" @click="goBack">
        <span class="material-symbols-outlined">arrow_back</span>
      </button>
      <div class="br-title-row">
        <div class="br-icon-box">
          <span class="material-symbols-outlined">storefront</span>
        </div>
        <div>
          <h1 class="br-title">Branch Performance</h1>
          <p class="br-subtitle">Granular report for <b>{{ report.branch?.name || 'Local Store' }}</b></p>
        </div>
      </div>
    </div>

    <!-- ══════ LOADING ══════ -->
    <LoadingScreen v-if="loading" :show="loading" message="Extracting store data…" />

    <!-- ══════ ERROR ══════ -->
    <div v-else-if="error" class="br-error-state">
      <span class="material-symbols-outlined br-err-ico">cloud_off</span>
      <h3>Data Retrieval Failed</h3>
      <p>{{ error }}</p>
      <button @click="$router.go(0)" class="br-retry-btn">
        <span class="material-symbols-outlined">refresh</span>
        Retry Sync
      </button>
    </div>

    <!-- ══════ CONTENT ══════ -->
    <div v-else class="br-content">
      
      <!-- Summary Cards -->
      <div class="br-summary-grid">
        <div class="br-card br-stat-card" style="--delay: 0.1s">
          <div class="br-ico-wrap ico-brown"><span class="material-symbols-outlined">savings</span></div>
          <div class="br-info">
            <span class="br-label">Today Sales</span>
            <span class="br-value">${{ Number(report.today_sales).toLocaleString() }}</span>
          </div>
        </div>
        <div class="br-card br-stat-card" style="--delay: 0.2s">
          <div class="br-ico-wrap ico-tan"><span class="material-symbols-outlined">account_balance</span></div>
          <div class="br-info">
            <span class="br-label">Monthly Sales</span>
            <span class="br-value">${{ Number(report.monthly_sales).toLocaleString() }}</span>
          </div>
        </div>
        <div class="br-card br-stat-card" style="--delay: 0.3s">
          <div class="br-ico-wrap ico-warm"><span class="material-symbols-outlined">assignment_turned_in</span></div>
          <div class="br-info">
            <span class="br-label">Orders Completed</span>
            <span class="br-value">{{ report.total_orders.toLocaleString() }}</span>
          </div>
        </div>
      </div>

      <div class="br-main-grid">
        <!-- Chart -->
        <div class="br-card br-viz-card" style="--delay: 0.4s">
          <div class="br-card-head">
            <span class="material-symbols-outlined">insights</span>
            <h3>Revenue Breakdown</h3>
          </div>
          <div class="br-chart-wrap">
            <canvas ref="salesChart"></canvas>
          </div>
        </div>

        <div class="br-side-col">
          <!-- Top Products -->
          <div class="br-card br-list-card" style="--delay: 0.5s">
            <div class="br-card-head">
              <span class="material-symbols-outlined">grade</span>
              <h3>Top Selling Units</h3>
            </div>
            <div v-if="report.top_products?.length" class="br-list">
              <div v-for="(p, i) in report.top_products" :key="p.name" class="br-list-item">
                <div class="br-rank">{{ i + 1 }}</div>
                <div class="br-item-name">{{ p.name }}</div>
                <div class="br-item-count">{{ p.total_sold }} <small>sold</small></div>
              </div>
            </div>
            <div v-else class="br-empty-state">
              <p>No products sold yet</p>
            </div>
          </div>

          <!-- Low Stock -->
          <div class="br-card br-list-card" style="--delay: 0.6s">
            <div class="br-card-head warning">
              <span class="material-symbols-outlined">fmcg</span>
              <h3>Restock Alerts</h3>
            </div>
            <div v-if="report.low_stock?.length" class="br-list">
              <div v-for="item in report.low_stock" :key="item.id" class="br-list-item">
                <div class="br-item-name">{{ item.product?.name }}</div>
                <div class="br-item-qty" :class="{ 'critical': item.quantity <= 3 }">
                  {{ item.quantity }} left
                </div>
              </div>
            </div>
            <div v-else class="br-empty-state green">
              <span class="material-symbols-outlined">check_circle</span>
              <p>Stock levels optimal</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.br-page {
  animation: pageIn 0.5s cubic-bezier(0.16,1,0.3,1) both;
  background: #FCFAF9; min-height: 100vh; padding: 1.5rem;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Header */
.br-header { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 2.5rem; }
.br-back-btn {
  width: 44px; height: 44px; border-radius: 12px; border: 1.5px solid #E0D7D0;
  background: #fff; color: #8D6E63; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s; flex-shrink: 0;
}
.br-back-btn:hover { background: #FAF9F7; border-color: #8D6E63; color: #3E2723; }

.br-title-row { display: flex; align-items: center; gap: 1rem; }
.br-icon-box {
  width: 52px; height: 52px; border-radius: 16px; background: #3E2723;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 20px rgba(62,39,35,0.22);
}
.br-icon-box .material-symbols-outlined { color: #fff; font-size: 26px; }
.br-title { font-size: 1.7rem; font-weight: 850; color: #3E2723; margin: 0; letter-spacing: -0.04em; }
.br-subtitle { font-size: 0.92rem; color: #8D6E63; margin: 0.15rem 0 0; font-weight: 500; }
.br-subtitle b { color: #5D4037; font-weight: 800; }

/* Grid & Cards */
.br-content { display: flex; flex-direction: column; gap: 1.25rem; }
.br-card {
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 24px;
  box-shadow: 0 4px 16px rgba(93,64,55,0.04);
  animation: cardIn 0.6s cubic-bezier(0.16,1,0.3,1) both; animation-delay: var(--delay);
}
@keyframes cardIn {
  from { opacity:0; transform: translateY(15px); }
  to   { opacity:1; transform: translateY(0); }
}

.br-summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; }
.br-stat-card { padding: 1.25rem 1.75rem; display: flex; align-items: center; gap: 1.25rem; }

.br-ico-wrap {
  width: 50px; height: 50px; border-radius: 15px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.br-ico-wrap .material-symbols-outlined { font-size: 26px; }
.ico-brown { background: rgba(62,39,35,0.08); color: #3E2723; }
.ico-tan   { background: rgba(141,110,99,0.08); color: #8D6E63; }
.ico-warm  { background: rgba(161,136,127,0.08); color: #A1887F; }

.br-label { display: block; font-size: 0.75rem; font-weight: 750; color: #A1887F; text-transform: uppercase; letter-spacing: 0.05em; }
.br-value { display: block; font-size: 1.6rem; font-weight: 850; color: #3E2723; letter-spacing: -0.02em; }

/* Main Section */
.br-main-grid { display: grid; grid-template-columns: 1fr 340px; gap: 1.25rem; }
.br-side-col { display: flex; flex-direction: column; gap: 1.25rem; }

.br-viz-card { padding: 1.75rem 2rem; }
.br-list-card { padding: 1.5rem; }

.br-card-head { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; }
.br-card-head .material-symbols-outlined { color: #8D6E63; font-size: 22px; }
.br-card-head h3 { font-size: 1.1rem; font-weight: 850; color: #3E2723; margin: 0; }
.br-card-head.warning .material-symbols-outlined { color: #E53935; }
.br-card-head.warning h3 { color: #C62828; }

.br-chart-wrap { height: 320px; position: relative; }

/* Lists */
.br-list { display: flex; flex-direction: column; gap: 0.6rem; }
.br-list-item {
  display: flex; align-items: center; gap: 1rem; padding: 0.85rem 1rem;
  background: #FAF9F7; border-radius: 14px; border: 1px solid #F0EDE9;
}
.br-rank {
  width: 26px; height: 26px; border-radius: 8px; background: #3E2723;
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 800;
}
.br-item-name { flex: 1; font-size: 0.92rem; font-weight: 700; color: #5D4037; }
.br-item-count { font-size: 0.88rem; font-weight: 800; color: #8D6E63; }
.br-item-count small { font-size: 0.7rem; color: #A1887F; }

.br-item-qty { 
  font-size: 0.85rem; font-weight: 800; color: #d97706; 
  background: #FFFBEB; padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid #FEF3C7;
}
.br-item-qty.critical { color: #DC2626; background: #FEF2F2; border-color: #FEE2E2; }

/* Empty / Error */
.br-empty-state { padding: 3rem 1rem; text-align: center; color: #BCAAA4; }
.br-empty-state.green { color: #166534; }
.br-empty-state.green span { font-size: 32px; margin-bottom: 0.5rem; }

.br-error-state { padding: 6rem 2rem; text-align: center; color: #A1887F; }
.br-err-ico { font-size: 56px; color: #D7CCC8; margin-bottom: 1.25rem; }
.br-error-state h3 { color: #3E2723; font-weight: 850; margin-bottom: 0.5rem; }
.br-retry-btn {
  margin-top: 1.5rem; padding: 0.75rem 1.5rem; background: #3E2723; color: #fff;
  border: none; border-radius: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;
}

@media (max-width: 1024px) {
  .br-main-grid { grid-template-columns: 1fr; }
  .br-side-col { flex-direction: row; }
  .br-side-col > div { flex: 1; }
}
@media (max-width: 768px) {
  .br-summary-grid { grid-template-columns: 1fr; }
  .br-side-col { flex-direction: column; }
}
</style>