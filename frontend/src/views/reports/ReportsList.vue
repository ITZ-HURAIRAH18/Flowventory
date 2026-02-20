<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const branches = ref([])
const loading = ref(false)
const user = ref(null)
const search = ref('')

const filteredBranches = computed(() => {
  if (!search.value.trim()) return branches.value
  const q = search.value.toLowerCase()
  return branches.value.filter(b =>
    b.name.toLowerCase().includes(q) ||
    (b.address && b.address.toLowerCase().includes(q))
  )
})

onMounted(async () => {
  const stored = localStorage.getItem('user')
  if (stored) user.value = JSON.parse(stored)

  loading.value = true
  try {
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

    <!-- Empty (no branches at all) -->
    <div v-else-if="branches.length === 0" class="empty-state">
      <span class="material-symbols-outlined empty-icon">analytics</span>
      <p>No branches available for reporting</p>
    </div>

    <template v-else>
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
      <div v-if="filteredBranches.length === 0" class="empty-state">
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

/* Branch Search Bar */
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

@media (max-width: 640px) {
  .branch-grid {
    grid-template-columns: 1fr;
  }
}
</style>
