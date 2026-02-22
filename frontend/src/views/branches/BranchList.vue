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
@import "../../styles/views/BranchList.css";
</style>