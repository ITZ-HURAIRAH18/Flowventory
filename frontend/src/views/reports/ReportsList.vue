<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import Chart from 'chart.js/auto'
import LoadingScreen from '@/components/LoadingScreen.vue'

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
  const role = typeof user.value.role === 'string' ? user.value.role : user.value.role.name
  return role === 'super_admin' ? 'All Branches' : 'My Branches'
})

onMounted(async () => {
  const stored = localStorage.getItem('user')
  if (stored) user.value = JSON.parse(stored)

  loading.value = true
  summaryLoading.value = true

  try {
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

  await nextTick()
  renderChart()
})

function renderChart() {
  if (!salesChart.value || !summary.value) return

  const ctx = salesChart.value.getContext('2d')
  const gradient = ctx.createLinearGradient(0, 0, 0, 400)
  gradient.addColorStop(0, 'rgba(141, 110, 99, 0.9)')
  gradient.addColorStop(1, 'rgba(141, 110, 99, 0.2)')

  new Chart(salesChart.value, {
    type: 'bar',
    data: {
      labels: ['Today Sales', 'Monthly Sales'],
      datasets: [
        {
          label: 'Sales Revenue',
          data: [
            summary.value.today_sales,
            summary.value.monthly_sales
          ],
          backgroundColor: gradient,
          borderColor: '#8D6E63',
          borderWidth: 2,
          borderRadius: 12,
          barPercentage: 0.4,
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
          grid: { color: 'rgba(215, 204, 200, 0.2)', drawTicks: false },
          border: { display: false }
        }
      }
    }
  })
}

const viewReport = (id) => router.push(`/branches/${id}/report`)
</script>

<template>
  <div class="rp-page">
    
    <!-- ══════ HEADER ══════ -->
    <div class="rp-header">
      <div class="rp-header-left">
        <div class="rp-icon-wrap">
          <span class="material-symbols-outlined">analytics</span>
        </div>
        <div>
          <h1 class="rp-title">Analytics Dashboard</h1>
          <p class="rp-subtitle">Strategic overview across {{ roleLabel }}</p>
        </div>
      </div>
    </div>

    <!-- ══════ LOADING ══════ -->
    <LoadingScreen v-if="loading" :show="loading" message="Compiling financial data…" />

    <template v-else>
      <!-- ══════ STATS CARDS ══════ -->
      <div v-if="summary" class="rp-summary">
        <div class="stats-grid">
          <div class="stat-card" style="--delay: 0.1s">
            <div class="stat-ico ico-brown"><span class="material-symbols-outlined">payments</span></div>
            <div class="stat-info">
              <span class="stat-label">Daily Revenue</span>
              <span class="stat-value">PKR {{ (summary?.today_sales ?? 0).toLocaleString() }}</span>
            </div>
          </div>
          <div class="stat-card" style="--delay: 0.2s">
            <div class="stat-ico ico-tan"><span class="material-symbols-outlined">account_balance_wallet</span></div>
            <div class="stat-info">
              <span class="stat-label">Monthly Cumulative</span>
              <span class="stat-value">PKR {{ (summary?.monthly_sales ?? 0).toLocaleString() }}</span>
            </div>
          </div>
          <div class="stat-card" style="--delay: 0.3s">
            <div class="stat-ico ico-warm"><span class="material-symbols-outlined">orders</span></div>
            <div class="stat-info">
              <span class="stat-label">Order Volume</span>
              <span class="stat-value">{{ (summary?.total_orders ?? 0).toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <div class="main-grid">
          <!-- Sales Visualization -->
          <div class="viz-card" style="--delay: 0.4s">
            <div class="viz-head">
              <span class="material-symbols-outlined">bar_chart</span>
              <h3>Revenue Trends</h3>
            </div>
            <div v-if="summary" class="viz-body">
              <canvas ref="salesChart"></canvas>
            </div>
            <div v-else class="rp-empty-small">
              <p>Loading chart data…</p>
            </div>
          </div>

          <!-- Top Sellers -->
          <div class="viz-card" style="--delay: 0.5s">
            <div class="viz-head">
              <span class="material-symbols-outlined">auto_graph</span>
              <h3>Performance Leaders</h3>
            </div>
            <div class="viz-body">
              <div v-if="summary.top_products?.length" class="leaders-list">
                <div v-for="(p, i) in summary.top_products" :key="p.name" class="leader-item">
                  <div class="leader-rank">{{ i + 1 }}</div>
                  <div class="leader-name">{{ p.name }}</div>
                  <div class="leader-stat">{{ p.total_sold }} <small>sold</small></div>
                </div>
              </div>
              <div v-else class="rp-empty-small">
                <span class="material-symbols-outlined">inventory_2</span>
                <p>No sales data available</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Inventory Warnings -->
        <div v-if="summary.low_stock?.length" class="alert-strip" style="--delay: 0.6s">
          <div class="alert-head">
            <span class="material-symbols-outlined">warning</span>
            <h4>Critical Stock Alerts</h4>
          </div>
          <div class="alert-track">
            <div v-for="item in summary.low_stock" :key="item.id" class="alert-chip">
              <span class="alert-name">{{ item.product?.name }}</span>
              <span class="alert-qty">{{ item.quantity }} left</span>
              <span class="alert-branch">at {{ item.branch?.name }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ══════ BRANCH DIRECTORY ══════ -->
      <div class="directory-section">
        <div class="directory-head">
          <div class="dir-title">
            <span class="material-symbols-outlined">storefront</span>
            <h2>Branch Analytics</h2>
          </div>
          <div class="dir-search">
            <span class="material-symbols-outlined">search</span>
            <input v-model="search" placeholder="Filter by store or location…" />
          </div>
        </div>

        <div v-if="filteredBranches.length" class="dir-grid">
          <div 
            v-for="b in filteredBranches" 
            :key="b.id" 
            class="dir-card"
            @click="viewReport(b.id)"
          >
            <div class="dir-ico"><span class="material-symbols-outlined">location_on</span></div>
            <div class="dir-info">
              <h4>{{ b.name }}</h4>
              <p>{{ b.address || 'Central Hub' }}</p>
            </div>
            <span class="material-symbols-outlined dir-arrow">arrow_forward</span>
          </div>
        </div>
        <div v-else class="rp-empty-full">
          <span class="material-symbols-outlined">search_off</span>
          <p>No branches match your filter criteria.</p>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
@import "../../styles/views/ReportsList.css";
</style>
