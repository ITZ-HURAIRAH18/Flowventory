<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import userService from '@/services/userService'
import UserForm from './UserForm.vue'
import LoadingScreen from '@/components/LoadingScreen.vue'

const users       = ref([])
const pagination  = ref({})
const loading     = ref(true)
const error       = ref('')
const search      = ref('')
const roleFilter  = ref('')
const currentPage = ref(1)
const perPage     = ref(8)
const showModal   = ref(false)
const editTarget  = ref(null)
const deletingId  = ref(null)

/* ── fetch ── */
const fetchUsers = async (page = 1) => {
  loading.value = true
  error.value   = ''
  try {
    const params = {
      page,
      per_page: perPage.value,
      search: search.value || undefined,
      role: roleFilter.value || undefined
    }
    const res = await userService.getAll(params)
    users.value = res.data.data ?? res.data
    pagination.value = res.data
    currentPage.value = page
  } catch {
    error.value = 'Failed to load user list. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchUsers(1))

// Watch for search and filter changes and reset to page 1
watch([search, roleFilter], () => {
  currentPage.value = 1
  fetchUsers(1)
}, { deep: true })

/* ── filters ── */
const filtered = computed(() => {
  // Since we're doing server-side filtering, just return the users from API
  return users.value
})

/* ── pagination helpers ── */
const totalPages = computed(() => pagination.value.last_page || 1)
const hasNextPage = computed(() => currentPage.value < totalPages.value)
const hasPrevPage = computed(() => currentPage.value > 1)
const startItem = computed(() => pagination.value.from || 0)
const endItem = computed(() => pagination.value.to || 0)

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
    fetchUsers(page)
  }
}

const nextPage = () => {
  if (hasNextPage.value) {
    goToPage(currentPage.value + 1)
  }
}

const prevPage = () => {
  if (hasPrevPage.value) {
    goToPage(currentPage.value - 1)
  }
}

/* ── stats ── */
const totalCount   = computed(() => pagination.value.total || users.value.length)
const adminCount   = computed(() => pagination.value.role_counts?.super_admin || 0)
const managerCount = computed(() => pagination.value.role_counts?.branch_manager || 0)
const salesCount   = computed(() => pagination.value.role_counts?.sales_user || 0)

/* ── helpers ── */
const initials = (name) => (name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
const roleLabel = (name) => {
  const map = { super_admin: 'Super Admin', branch_manager: 'Branch Manager', sales_user: 'Sales User' }
  return map[name] || name
}

/* ── actions ── */
const openCreate = () => { editTarget.value = null; showModal.value = true }
const openEdit   = (u) => { editTarget.value = u;    showModal.value = true }
const onSaved    = () => { 
  // Don't refresh immediately - let the toast be visible
  setTimeout(() => {
    showModal.value = false; 
    fetchUsers(currentPage.value);
  }, 100)
}
const onClose    = () => { showModal.value = false }

const deleteUser = async (id, name) => {
  if (!confirm(`Permanently delete user "${name}"? This cannot be undone.`)) return
  deletingId.value = id
  try {
    await userService.delete(id)
    // If we're on the last page and delete the last item, go to previous page
    const shouldGoToPrevPage = users.value.length === 1 && currentPage.value > 1
    const targetPage = shouldGoToPrevPage ? currentPage.value - 1 : currentPage.value
    fetchUsers(targetPage)
  } catch {
    alert('Failed to delete user.')
  } finally {
    deletingId.value = null
  }
}
</script>

<template>
  <div class="ul-page">

    <!-- ══════ HEADER ══════ -->
    <div class="ul-header">
      <div class="ul-header-left">
        <div class="ul-icon-wrap">
          <span class="material-symbols-outlined">group</span>
        </div>
        <div>
          <h1 class="ul-title">User Management</h1>
          <p class="ul-subtitle">Manage system access, roles, and user profiles</p>
        </div>
      </div>
      <button class="btn-add" @click="openCreate" id="add-user-btn">
        <span class="material-symbols-outlined">person_add</span>
        Add User
      </button>
    </div>

    <!-- ══════ STATS BAR ══════ -->
    <div class="ul-stats">
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico">group</span>
        <span class="stat-val">{{ totalCount }}</span>
        <span class="stat-lbl">Total</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico ico-red">shield_person</span>
        <span class="stat-val">{{ adminCount }}</span>
        <span class="stat-lbl">Admins</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico ico-blue">manage_accounts</span>
        <span class="stat-val">{{ managerCount }}</span>
        <span class="stat-lbl">Managers</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico ico-green">sell</span>
        <span class="stat-val">{{ salesCount }}</span>
        <span class="stat-lbl">Sales</span>
      </div>
    </div>

    <!-- ══════ TOOLBAR ══════ -->
    <div class="ul-toolbar">
      <div class="search-wrap">
        <span class="material-symbols-outlined search-ico">search</span>
        <input
          v-model="search"
          class="search-input"
          placeholder="Search by name or email…"
        />
        <button v-if="search" class="search-clear" @click="search = ''">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <div class="filter-wrap">
        <select v-model="roleFilter" class="role-select">
          <option value="">All Roles</option>
          <option value="super_admin">Super Admins</option>
          <option value="branch_manager">Branch Managers</option>
          <option value="sales_user">Sales Users</option>
        </select>
        <span class="material-symbols-outlined select-arrow">expand_more</span>
      </div>
    </div>

    <!-- ══════ LOADING ══════ -->
    <LoadingScreen v-if="loading" :show="loading" :duration="0" message="Fetching team directory…" />

    <!-- ══════ ERROR ══════ -->
    <div v-else-if="error" class="ul-error">
      <span class="material-symbols-outlined">error</span>
      {{ error }}
      <button @click="fetchUsers" class="retry-btn">Retry</button>
    </div>

    <!-- ══════ EMPTY ══════ -->
    <div v-else-if="filtered.length === 0" class="ul-empty">
      <span class="material-symbols-outlined ul-empty-icon">person_off</span>
      <p class="ul-empty-title">{{ search || roleFilter ? 'No matching users found' : 'No users registered' }}</p>
      <p class="ul-empty-sub">Try adjusting your search or filters to find what you're looking for.</p>
    </div>

    <!-- ══════ CONTENT WITH PAGINATION ══════ -->
    <div v-else>
      <!-- ══════ CARDS GRID ══════ -->
      <div class="ul-grid">
        <div
          v-for="(user, i) in filtered"
          :key="user.id"
          class="user-card"
          :style="{ animationDelay: `${i * 0.05}s` }"
        >
          <div class="uc-body">
            <div class="uc-top">
              <div class="uc-avatar">
                {{ initials(user.name) }}
              </div>
              <div class="uc-info">
                <h3 class="uc-name">{{ user.name }}</h3>
                <p class="uc-email">{{ user.email }}</p>
              </div>
            </div>
            
            <div class="uc-meta">
              <div class="uc-role-tag" :class="user.role?.name || user.role">
                <span class="material-symbols-outlined">verified_user</span>
                {{ roleLabel(user.role?.name || user.role) }}
              </div>
              <div class="uc-date">
                Joined {{ new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) }}
              </div>
            </div>

            <div class="uc-actions">
              <button class="uc-btn-edit" @click="openEdit(user)">
                <span class="material-symbols-outlined">edit</span>
                Edit Profile
              </button>
              <button
                class="uc-btn-del"
                @click="deleteUser(user.id, user.name)"
                :disabled="deletingId === user.id"
              >
                <span class="material-symbols-outlined">
                  {{ deletingId === user.id ? 'hourglass_empty' : 'delete' }}
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ══════ PAGINATION ══════ -->
      <div v-if="totalPages > 1" class="ul-pagination">
        <div class="pagination-info">
          Showing {{ startItem }} to {{ endItem }} of {{ totalCount }} users
        </div>
        <div class="pagination-controls">
          <button 
            class="pagination-btn" 
            :class="{ disabled: !hasPrevPage }"
            @click="prevPage"
            :disabled="!hasPrevPage"
          >
            <span class="material-symbols-outlined">chevron_left</span>
            Previous
          </button>
          
          <div class="pagination-pages">
            <template v-for="page in Math.min(5, totalPages)" :key="page">
              <button 
                v-if="page <= totalPages"
                class="pagination-page" 
                :class="{ active: page === currentPage }"
                @click="goToPage(page)"
              >
                {{ page }}
              </button>
            </template>
            <span v-if="totalPages > 5" class="pagination-ellipsis">...</span>
            <button 
              v-if="totalPages > 5 && currentPage < totalPages - 2"
              class="pagination-page" 
              @click="goToPage(totalPages)"
            >
              {{ totalPages }}
            </button>
          </div>

          <button 
            class="pagination-btn" 
            :class="{ disabled: !hasNextPage }"
            @click="nextPage"
            :disabled="!hasNextPage"
          >
            Next
            <span class="material-symbols-outlined">chevron_right</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ══════ MODAL ══════ -->
    <transition name="modal-fade">
      <div v-if="showModal" class="modal-backdrop" @click.self="onClose">
        <div class="modal-box">
          <UserForm
            :userObject="editTarget"
            @saved="onSaved"
            @close="onClose"
          />
        </div>
      </div>
    </transition>

  </div>
</template>

<style scoped>
@import "../../styles/views/UserList.css";
</style>
