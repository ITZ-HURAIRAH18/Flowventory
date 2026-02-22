<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import branchService from '@/services/branchService'
import BranchForm from './BranchForm.vue'
import LoadingScreen from '@/components/LoadingScreen.vue'

const router = useRouter()

const branches    = ref([])
const loading     = ref(true)
const error       = ref('')
const search      = ref('')
const showModal   = ref(false)
const editTarget  = ref(null)   // null = create, object = edit
const deletingId  = ref(null)

const currentPage = ref(1)
const totalPages  = ref(1)
const totalItems  = ref(0)

/* ── fetch ── */
const fetchBranches = async () => {
  loading.value = true
  error.value   = ''
  try {
    const params = { page: currentPage.value, per_page: 8, search: search.value }
    const res = await branchService.getAll(params)
    branches.value = res.data.data ?? res.data
    totalPages.value = res.data.last_page || 1
    totalItems.value = res.data.total || 0
  } catch {
    error.value = 'Failed to load branches. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchBranches)

/* ── search ── */
let searchTimeout = null
watch(search, () => {
  currentPage.value = 1
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(fetchBranches, 300)
})

/* ── stats ── */
const totalBranches  = computed(() => totalItems.value)
const managedCount   = computed(() => branches.value.filter(b => b.manager).length) // This will be inaccurate with pagination
const unmanagedCount = computed(() => totalBranches.value - managedCount.value) // This will be inaccurate with pagination

/* ── actions ── */
const openCreate = () => { editTarget.value = null; showModal.value = true }
const openEdit   = (b) => { editTarget.value = b;    showModal.value = true }
const onSaved    = () => { 
  // Don't refresh immediately - let the toast be visible
  setTimeout(() => {
    showModal.value = false; 
    fetchBranches();
  }, 100)
}
const onClose    = () => { showModal.value = false }

const deleteBranch = async (id) => {
  if (!confirm('Delete this branch? This cannot be undone.')) return
  deletingId.value = id
  try {
    await branchService.delete(id)
    // Refetch current page after deletion
    fetchBranches()
  } catch {
    alert('Failed to delete branch.')
  } finally {
    deletingId.value = null
  }
}

const viewBranch = (id) => router.push(`/branches/${id}`)

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchBranches()
}

/* ── initials avatar ── */
const initials = (name) =>
  (name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
</script>

<template>
  <div class="bl-page">

    <!-- ══════ PAGE HEADER ══════ -->
    <div class="bl-header">
      <div class="bl-header-left">
        <div class="bl-icon-wrap">
          <span class="material-symbols-outlined">store</span>
        </div>
        <div>
          <h1 class="bl-title">Branches</h1>
          <p class="bl-subtitle">Manage all your store branches</p>
        </div>
      </div>
      <button class="btn-add" @click="openCreate" id="add-branch-btn">
        <span class="material-symbols-outlined">add</span>
        Add Branch
      </button>
    </div>

    <!-- ══════ STATS BAR ══════ -->
    <div class="bl-stats">
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico">store</span>
        <span class="stat-val">{{ totalBranches }}</span>
        <span class="stat-lbl">Total</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico ico-green">verified</span>
        <span class="stat-val">{{ managedCount }}</span>
        <span class="stat-lbl">Managed</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico ico-amber">warning</span>
        <span class="stat-val">{{ unmanagedCount }}</span>
        <span class="stat-lbl">No Manager</span>
      </div>
    </div>

    <!-- ══════ SEARCH BAR ══════ -->
    <div class="bl-toolbar">
      <div class="search-wrap">
        <span class="material-symbols-outlined search-ico">search</span>
        <input
          v-model="search"
          class="search-input"
          placeholder="Search branches by name, address or manager…"
          id="branch-search"
        />
        <button v-if="search" class="search-clear" @click="search = ''">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <span class="result-count" v-if="!loading">
        {{ totalItems }} result{{ totalItems !== 1 ? 's' : '' }}
      </span>
    </div>

    <!-- ══════ FULL-PAGE LOADING ══════ -->
    <LoadingScreen
      v-if="loading"
      :show="loading"
      :duration="0"
      message="Loading branches…"
    />

    <!-- ══════ ERROR ══════ -->
    <div v-else-if="error" class="bl-error">
      <span class="material-symbols-outlined">error</span>
      {{ error }}
      <button @click="fetchBranches" class="retry-btn">Retry</button>
    </div>

    <!-- ══════ EMPTY ══════ -->
    <div v-else-if="branches.length === 0" class="bl-empty">
      <span class="material-symbols-outlined bl-empty-icon">store_mall_directory</span>
      <p class="bl-empty-title">{{ search ? 'No results found' : 'No branches yet' }}</p>
      <p class="bl-empty-sub">{{ search ? 'Try a different search term.' : 'Add your first branch to get started.' }}</p>
      <button v-if="!search" class="btn-add mt-3" @click="openCreate">
        <span class="material-symbols-outlined">add</span>
        Add Branch
      </button>
    </div>

    <!-- ══════ BRANCH CARDS ══════ -->
    <div v-else class="bl-grid">
      <div
        v-for="(branch, i) in branches"
        :key="branch.id"
        class="branch-card"
        :style="{ animationDelay: `${i * 0.06}s` }"
      >
        <!-- Card header strip -->
        <div class="bc-strip"></div>

        <div class="bc-body">
          <!-- Avatar + Name -->
          <div class="bc-top">
            <div class="bc-avatar">{{ initials(branch.name) }}</div>
            <div class="bc-info">
              <h3 class="bc-name">{{ branch.name }}</h3>
              <p class="bc-address">
                <span class="material-symbols-outlined">location_on</span>
                {{ branch.address || '—' }}
              </p>
            </div>
          </div>

          <!-- Manager row -->
          <div class="bc-manager-row">
            <span class="material-symbols-outlined bc-mgr-icon">manage_accounts</span>
            <span v-if="branch.manager" class="bc-mgr-name">{{ branch.manager.name }}</span>
            <span v-else class="bc-mgr-none">No manager assigned</span>
          </div>

          <!-- Stats row -->
          <div class="bc-meta">
            <div class="bc-meta-item">
              <span class="material-symbols-outlined">inventory_2</span>
              <span>{{ branch.inventories?.length ?? '—' }} products</span>
            </div>
            <div class="bc-meta-item">
              <span class="material-symbols-outlined">receipt_long</span>
              <span>{{ branch.orders?.length ?? '—' }} orders</span>
            </div>
          </div>

          <!-- Action buttons -->
          <div class="bc-actions">
            <button class="bc-btn bc-btn-edit" @click="openEdit(branch)">
              <span class="material-symbols-outlined">edit</span>
              Edit
            </button>
            <button
              class="bc-btn bc-btn-del"
              @click="deleteBranch(branch.id)"
              :disabled="deletingId === branch.id"
            >
              <span class="material-symbols-outlined">
                {{ deletingId === branch.id ? 'hourglass_empty' : 'delete' }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════ PAGINATION ══════ -->
    <div v-if="totalPages > 1" class="pagination">
      <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1" class="page-btn">
        <span class="material-symbols-outlined">chevron_left</span>
      </button>
      <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
      <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages" class="page-btn">
        <span class="material-symbols-outlined">chevron_right</span>
      </button>
    </div>

    <!-- ══════ MODAL ══════ -->
    <transition name="modal-fade">
      <div v-if="showModal" class="modal-backdrop" @click.self="onClose">
        <div class="modal-box">
          <BranchForm
            :branch="editTarget"
            @saved="onSaved"
            @close="onClose"
          />
        </div>
      </div>
    </transition>

  </div>
</template>

<style scoped>
/* ════════════════════════════════
   PAGE
════════════════════════════════ */
.bl-page {
  min-height: 100%;
  background: #FAF8F6;
  padding-bottom: 3rem;
  animation: pageIn 0.45s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pageIn {
  from { opacity:0; transform:translateY(10px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ════════════════════════════════
   PAGE HEADER
════════════════════════════════ */
.bl-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.bl-header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.bl-icon-wrap {
  width: 48px; height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #5D4037, #795548);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 16px rgba(93,64,55,0.25);
  flex-shrink: 0;
}
.bl-icon-wrap .material-symbols-outlined { font-size: 24px; color: #fff; }

.bl-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: #3E2723;
  margin: 0;
  letter-spacing: -0.03em;
}
.bl-subtitle {
  font-size: 0.82rem;
  color: #A1887F;
  margin: 0;
}

.btn-add {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.65rem 1.35rem;
  background: linear-gradient(135deg, #5D4037, #795548);
  color: #fff;
  border: none; border-radius: 12px;
  font-size: 0.88rem; font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  box-shadow: 0 4px 16px rgba(93,64,55,0.25);
  transition: all 0.2s ease;
}
.btn-add:hover { transform:translateY(-2px); box-shadow: 0 8px 24px rgba(93,64,55,0.3); }
.btn-add .material-symbols-outlined { font-size: 19px; }
.mt-3 { margin-top: 0.75rem; }

/* ════════════════════════════════
   STATS BAR
════════════════════════════════ */
.bl-stats {
  display: flex;
  align-items: center;
  gap: 0;
  background: #fff;
  border: 1px solid #EDE8E4;
  border-radius: 14px;
  padding: 1rem 1.5rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 2px 12px rgba(93,64,55,0.05);
}
.stat-chip {
  display: flex; align-items: center; gap: 0.5rem;
  flex: 1; justify-content: center;
}
.stat-ico { font-size: 20px; color: #8D6E63; }
.ico-green { color: #16a34a !important; }
.ico-amber { color: #b45309 !important; }
.stat-val  { font-size: 1.3rem; font-weight: 800; color: #3E2723; }
.stat-lbl  { font-size: 0.78rem; color: #A1887F; font-weight: 500; }
.stat-divider { width: 1px; height: 32px; background: #EDE8E4; margin: 0 0.5rem; }

/* ════════════════════════════════
   TOOLBAR
════════════════════════════════ */
.bl-toolbar {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.search-wrap {
  position: relative; flex: 1; min-width: 250px;
}
.search-ico {
  position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%);
  font-size: 19px; color: #A1887F; pointer-events: none;
}
.search-input {
  width: 100%;
  padding: 0.72rem 2.8rem 0.72rem 2.6rem;
  background: #fff;
  border: 1.5px solid #D7CCC8;
  border-radius: 12px;
  font-size: 0.88rem;
  color: #3E2723;
  font-family: inherit;
  transition: border-color 0.2s;
  outline: none;
}
.search-input:focus { border-color: #A1887F; box-shadow: 0 0 0 3px rgba(161, 136, 127, 0.1); }
.search-input::placeholder { color: #BCAAA4; }
.search-clear {
  position: absolute; right: 0.75rem; top: 50%; transform:translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: #BCAAA4; display: flex; padding: 2px;
}
.search-clear:hover { color: #5D4037; }
.search-clear .material-symbols-outlined { font-size: 18px; }
.result-count {
  font-size: 0.8rem; color: #A1887F; font-weight: 500; white-space: nowrap;
}

/* ════════════════════════════════
   STATES
════════════════════════════════ */
.bl-loading {
  display: flex; flex-direction: column; align-items: center;
  gap: 1rem; padding: 4rem 0; color: #A1887F;
}
.spinner {
  width: 36px; height: 36px; border-radius: 50%;
  border: 3px solid #EDE8E4;
  border-top-color: #795548;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.bl-error {
  display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
  padding: 1rem 1.25rem;
  background: #FFF5F5; border: 1px solid #fca5a5;
  border-radius: 12px; color: #dc2626; font-size: 0.9rem;
}
.bl-error .material-symbols-outlined { font-size: 20px; }
.retry-btn {
  margin-left: auto; padding: 0.35rem 1rem;
  background: #dc2626; color: #fff; border: none;
  border-radius: 8px; cursor: pointer; font-size: 0.82rem;
  font-family: inherit; font-weight: 600;
}

.bl-empty {
  display: flex; flex-direction: column; align-items: center;
  padding: 5rem 1rem; text-align: center;
}
.bl-empty-icon { font-size: 56px; color: #D7CCC8; margin-bottom: 1rem; }
.bl-empty-title { font-size: 1.1rem; font-weight: 700; color: #5D4037; margin: 0 0 0.35rem; }
.bl-empty-sub   { font-size: 0.85rem; color: #A1887F; margin: 0; }

/* ════════════════════════════════
   CARDS GRID
════════════════════════════════ */
.bl-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.25rem;
}

.branch-card {
  background: #fff;
  border: 1.5px solid #E0D7D0;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(93,64,55,0.06);
  transition: all 0.25s ease;
  animation: cardIn 0.45s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes cardIn {
  from { opacity:0; transform:translateY(14px); }
  to   { opacity:1; transform:translateY(0); }
}
.branch-card:hover {
  box-shadow: 0 12px 36px rgba(93,64,55,0.15);
  transform: translateY(-4px);
  border-color: #A1887F;
}

/* top brown strip */
.bc-strip {
  height: 5px;
  background: linear-gradient(90deg, #5D4037, #A1887F, #D7CCC8);
}

.bc-body { padding: 1.4rem 1.5rem 1.25rem; }

/* top row — avatar + info */
.bc-top {
  display: flex; align-items: flex-start; gap: 1rem;
  margin-bottom: 1rem;
}
.bc-avatar {
  width: 46px; height: 46px; border-radius: 12px;
  background: linear-gradient(135deg, #5D4037, #8D6E63);
  color: #fff; font-size: 0.9rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; letter-spacing: 0.02em;
}
.bc-info { flex: 1; min-width: 0; }
.bc-name {
  font-size: 1rem; font-weight: 700; color: #3E2723;
  margin: 0 0 0.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.bc-address {
  display: flex; align-items: center; gap: 0.25rem;
  font-size: 0.78rem; color: #A1887F; margin: 0;
}
.bc-address .material-symbols-outlined { font-size: 14px; }

/* manager row */
.bc-manager-row {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.6rem 0.75rem;
  background: #FAF8F6;
  border-radius: 10px;
  margin-bottom: 0.9rem;
}
.bc-mgr-icon { font-size: 17px; color: #8D6E63; }
.bc-mgr-name { font-size: 0.83rem; font-weight: 600; color: #3E2723; }
.bc-mgr-none { font-size: 0.8rem; color: #BCAAA4; font-style: italic; }

/* meta chips */
.bc-meta {
  display: flex; gap: 0.75rem; margin-bottom: 1.1rem;
}
.bc-meta-item {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.3rem 0.65rem;
  background: rgba(93,64,55,0.06);
  border-radius: 99px;
  font-size: 0.75rem; color: #795548; font-weight: 500;
}
.bc-meta-item .material-symbols-outlined { font-size: 14px; }

/* action row */
.bc-actions {
  display: flex; gap: 0.5rem; align-items: center;
}
.bc-btn {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.5rem 0.9rem;
  border: none; border-radius: 9px;
  font-size: 0.8rem; font-weight: 600; cursor: pointer;
  font-family: inherit; transition: all 0.18s ease;
}
.bc-btn .material-symbols-outlined { font-size: 16px; }

.bc-btn-view {
  background: rgba(93,64,55,0.08); color: #5D4037;
  flex: 1;
}
.bc-btn-view:hover { background: rgba(93,64,55,0.14); }

.bc-btn-edit {
  background: rgba(121,85,72,0.08); color: #795548;
  flex: 1;
}
.bc-btn-edit:hover { background: rgba(121,85,72,0.15); }

.bc-btn-del {
  background: rgba(239,68,68,0.08); color: #dc2626;
  padding: 0.5rem;
}
.bc-btn-del:hover:not(:disabled) { background: rgba(239,68,68,0.14); }
.bc-btn-del:disabled { opacity: 0.5; cursor: not-allowed; }

/* ════════════════════════════════
   MODAL
════════════════════════════════ */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 2000;
  background: rgba(30,15,10,0.45);
  backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
}
.modal-box {
  width: 100%; max-width: 520px;
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(0,0,0,0.3);
  animation: modalBounce 0.35s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes modalBounce {
  from { opacity:0; transform:scale(0.93) translateY(20px); }
  to   { opacity:1; transform:scale(1)    translateY(0); }
}

.modal-fade-enter-active,
.modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from,
.modal-fade-leave-to     { opacity: 0; }

/* ════════════════════════════════
   PAGINATION
════════════════════════════════ */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 2rem;
  gap: 0.5rem;
}
.page-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1.5px solid #D7CCC8;
  background: #fff;
  color: #795548;
  cursor: pointer;
  transition: all 0.2s ease;
}
.page-btn:hover:not(:disabled) {
  background: #F5F1EE;
  border-color: #A1887F;
}
.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.page-info {
  font-size: 0.85rem;
  font-weight: 600;
  color: #5D4037;
  padding: 0 0.5rem;
}

/* ════════════════════════════════
   RESPONSIVE
════════════════════════════════ */
@media (max-width: 640px) {
  .bl-stats { flex-wrap: wrap; gap: 0.75rem;}
  .stat-divider { display: none; }
  .bl-grid { grid-template-columns: 1fr; }
}
</style>