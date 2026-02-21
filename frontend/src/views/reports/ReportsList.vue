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
              <span class="stat-value">PKR {{ Number(summary.today_sales).toLocaleString() }}</span>
            </div>
          </div>
          <div class="stat-card" style="--delay: 0.2s">
            <div class="stat-ico ico-tan"><span class="material-symbols-outlined">account_balance_wallet</span></div>
            <div class="stat-info">
              <span class="stat-label">Monthly Cumulative</span>
              <span class="stat-value">PKR {{ Number(summary.monthly_sales).toLocaleString() }}</span>
            </div>
          </div>
          <div class="stat-card" style="--delay: 0.3s">
            <div class="stat-ico ico-warm"><span class="material-symbols-outlined">orders</span></div>
            <div class="stat-info">
              <span class="stat-label">Order Volume</span>
              <span class="stat-value">{{ summary.total_orders.toLocaleString() }}</span>
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
            <div class="viz-body">
              <canvas ref="salesChart"></canvas>
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
.rp-page {
  animation: pageIn 0.5s cubic-bezier(0.16,1,0.3,1) both;
  background: #FCFAF9; min-height: 100vh; padding: 1.5rem;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Header */
.rp-header { margin-bottom: 2.5rem; }
.rp-header-left { display: flex; align-items: center; gap: 1.25rem; }
.rp-icon-wrap {
  width: 52px; height: 52px; border-radius: 16px;
  background: #3E2723; display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 20px rgba(62,39,35,0.22);
}
.rp-icon-wrap .material-symbols-outlined { color: #fff; font-size: 26px; }
.rp-title { font-size: 1.8rem; font-weight: 800; color: #3E2723; margin: 0; letter-spacing: -0.04em; }
.rp-subtitle { font-size: 0.92rem; color: #8D6E63; margin: 0.15rem 0 0; font-weight: 500; }

/* Stats Grid */
.stats-grid { 
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.25rem;
}
.stat-card {
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 20px;
  padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1.25rem;
  box-shadow: 0 4px 12px rgba(93,64,55,0.04);
  animation: cardIn 0.6s cubic-bezier(0.16,1,0.3,1) both; animation-delay: var(--delay);
}
@keyframes cardIn {
  from { opacity: 0; transform: translateY(15px); }
  to   { opacity: 1; transform: translateY(0); }
}

.stat-ico {
  width: 48px; height: 48px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.stat-ico .material-symbols-outlined { font-size: 24px; }
.ico-brown { background: rgba(62,39,35,0.08); color: #3E2723; }
.ico-tan   { background: rgba(141,110,99,0.08); color: #8D6E63; }
.ico-warm  { background: rgba(161,136,127,0.08); color: #A1887F; }

.stat-label { display: block; font-size: 0.75rem; font-weight: 700; color: #A1887F; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.15rem; }
.stat-value { display: block; font-size: 1.6rem; font-weight: 850; color: #3E2723; letter-spacing: -0.02em; }

/* Main Viz Grid */
.main-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
.viz-card {
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 24px;
  padding: 1.5rem 1.75rem; box-shadow: 0 4px 20px rgba(93,64,55,0.05);
  animation: cardIn 0.6s cubic-bezier(0.16,1,0.3,1) both; animation-delay: var(--delay);
}
.viz-head { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; }
.viz-head .material-symbols-outlined { color: #8D6E63; font-size: 22px; }
.viz-head h3 { font-size: 1.1rem; font-weight: 800; color: #3E2723; margin: 0; }

.viz-body { height: 260px; position: relative; }

/* Leaders List */
.leaders-list { display: flex; flex-direction: column; gap: 0.75rem; }
.leader-item {
  display: flex; align-items: center; gap: 1rem; padding: 0.85rem 1rem;
  background: #FAF9F7; border-radius: 14px; border: 1px solid #F0EDE9;
}
.leader-rank {
  width: 28px; height: 28px; border-radius: 8px; background: #3E2723;
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 0.8rem; font-weight: 800;
}
.leader-name { flex: 1; font-size: 0.95rem; font-weight: 700; color: #5D4037; }
.leader-stat { font-size: 0.9rem; font-weight: 800; color: #8D6E63; }
.leader-stat small { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: #A1887F; }

/* Alert Strip */
.alert-strip {
  background: #FFF9F7; border: 1.5px solid #FFEDEB; border-radius: 18px; padding: 1.25rem; margin-bottom: 2.5rem;
}
.alert-head { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; }
.alert-head .material-symbols-outlined { color: #E53935; font-size: 20px; }
.alert-head h4 { font-size: 0.9rem; font-weight: 800; color: #C62828; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }

.alert-track { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.alert-chip {
  background: #fff; border: 1px solid #FFCDD2; padding: 0.5rem 1rem; border-radius: 12px;
  display: flex; align-items: center; gap: 0.75rem; font-size: 0.85rem;
}
.alert-name { font-weight: 700; color: #3E2723; }
.alert-qty  { font-weight: 800; color: #E53935; background: #FFEBEE; padding: 0.1rem 0.4rem; border-radius: 4px; }
.alert-branch { color: #A1887F; font-size: 0.8rem; }

/* Directory */
.directory-section { margin-top: 2rem; border-top: 1.5px solid #EDE8E4; padding-top: 2.5rem; }
.directory-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.dir-title { display: flex; align-items: center; gap: 0.75rem; }
.dir-title .material-symbols-outlined { color: #8D6E63; font-size: 24px; }
.dir-title h2 { font-size: 1.3rem; font-weight: 800; color: #3E2723; margin: 0; }

.dir-search { position: relative; width: 320px; }
.dir-search .material-symbols-outlined { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); color: #A1887F; font-size: 20px; }
.dir-search input {
  width: 100%; padding: 0.75rem 1rem 0.75rem 2.6rem; background: #fff;
  border: 1.5px solid #E0D7D0; border-radius: 12px; font-size: 0.9rem;
  outline: none; transition: all 0.2s;
}
.dir-search input:focus { border-color: #8D6E63; box-shadow: 0 0 0 4px rgba(141,110,99,0.06); }

.dir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem; }
.dir-card {
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 18px;
  padding: 1.15rem 1.4rem; display: flex; align-items: center; gap: 1.25rem;
  cursor: pointer; transition: all 0.25s ease;
}
.dir-card:hover { transform: translateY(-3px); border-color: #8D6E63; box-shadow: 0 10px 25px rgba(93,64,55,0.1); }
.dir-ico {
  width: 44px; height: 44px; border-radius: 12px; background: rgba(141,110,99,0.08);
  display: flex; align-items: center; justify-content: center; color: #8D6E63;
}
.dir-info h4 { font-size: 1.05rem; font-weight: 800; color: #3E2723; margin: 0 0 0.15rem; }
.dir-info p { font-size: 0.8rem; color: #A1887F; margin: 0; font-weight: 500; }
.dir-arrow { margin-left: auto; color: #D7CCC8; transition: transform 0.2s; }
.dir-card:hover .dir-arrow { transform: translateX(4px); color: #8D6E63; }

/* Empty States */
.rp-empty-small, .rp-empty-full { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
.rp-empty-small { padding: 2rem 1rem; color: #BCAAA4; }
.rp-empty-small span { font-size: 36px; margin-bottom: 0.5rem; }
.rp-empty-full { padding: 4rem 1rem; color: #A1887F; width: 100%; }
.rp-empty-full span { font-size: 52px; margin-bottom: 1rem; }

@media (max-width: 1024px) {
  .main-grid { grid-template-columns: 1fr; }
  .stats-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
  .stats-grid { grid-template-columns: 1fr; }
  .dir-search { width: 100%; }
}
</style>
