<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const branches = ref([])
const loading = ref(false)
const user = ref(null)

onMounted(async () => {
  const stored = localStorage.getItem('user')
  if (stored) user.value = JSON.parse(stored)

  loading.value = true
  try {
    // /my-branches returns all branches for admin, own branch for manager
    const res = await api.get('/my-branches')
    branches.value = res.data
  } catch (error) {
    console.error('Failed to load branches:', error)
  }
  loading.value = false
})

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
      <p class="page-sub">Select a branch to view its reporting dashboard</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <span class="material-symbols-outlined spin">progress_activity</span>
      Loading branches...
    </div>

    <!-- Empty -->
    <div v-else-if="branches.length === 0" class="empty-state">
      <span class="material-symbols-outlined empty-icon">analytics</span>
      <p>No branches available for reporting</p>
    </div>

    <!-- Branch Cards -->
    <div v-else class="branch-grid">
      <div
        v-for="branch in branches"
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

<style scoped>
.reports-page {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
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

@media (max-width: 640px) {
  .branch-grid {
    grid-template-columns: 1fr;
  }
}
</style>
