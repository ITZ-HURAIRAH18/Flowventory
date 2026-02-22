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
          label: 'Revenue (PKR)',
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
            label: (ctx) => `Revenue: PKR ${Number(ctx.raw).toLocaleString()}`
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
            callback: (val) => 'PKR ' + val.toLocaleString()
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
            <span class="br-value">PKR {{ Number(report.today_sales).toLocaleString() }}</span>
          </div>
        </div>
        <div class="br-card br-stat-card" style="--delay: 0.2s">
          <div class="br-ico-wrap ico-tan"><span class="material-symbols-outlined">account_balance</span></div>
          <div class="br-info">
            <span class="br-label">Monthly Sales</span>
            <span class="br-value">PKR {{ Number(report.monthly_sales).toLocaleString() }}</span>
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
@import "../../styles/views/BranchReport.css";
</style>