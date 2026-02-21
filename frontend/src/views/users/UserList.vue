<script setup>
import { ref, computed, onMounted } from 'vue'
import userService from '@/services/userService'
import UserForm from './UserForm.vue'
import LoadingScreen from '@/components/LoadingScreen.vue'

const users       = ref([])
const loading     = ref(true)
const error       = ref('')
const search      = ref('')
const roleFilter  = ref('')
const showModal   = ref(false)
const editTarget  = ref(null)
const deletingId  = ref(null)

/* ── fetch ── */
const fetchUsers = async () => {
  loading.value = true
  error.value   = ''
  try {
    const res = await userService.getAll()
    users.value = res.data.data ?? res.data
  } catch {
    error.value = 'Failed to load user list. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchUsers)

/* ── filters ── */
const filtered = computed(() => {
  let list = users.value
  const q = search.value.toLowerCase()
  
  if (q) {
    list = list.filter(u => 
      u.name.toLowerCase().includes(q) || 
      u.email.toLowerCase().includes(q)
    )
  }
  
  if (roleFilter.value) {
    list = list.filter(u => (u.role?.name || u.role) === roleFilter.value)
  }
  
  return list
})

/* ── stats ── */
const totalCount   = computed(() => users.value.length)
const adminCount   = computed(() => users.value.filter(u => (u.role?.name || u.role) === 'super_admin').length)
const managerCount = computed(() => users.value.filter(u => (u.role?.name || u.role) === 'branch_manager').length)
const salesCount   = computed(() => users.value.filter(u => (u.role?.name || u.role) === 'sales_user').length)

/* ── helpers ── */
const initials = (name) => (name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
const roleLabel = (name) => {
  const map = { super_admin: 'Super Admin', branch_manager: 'Branch Manager', sales_user: 'Sales User' }
  return map[name] || name
}

/* ── actions ── */
const openCreate = () => { editTarget.value = null; showModal.value = true }
const openEdit   = (u) => { editTarget.value = u;    showModal.value = true }
const onSaved    = () => { showModal.value = false; fetchUsers() }
const onClose    = () => { showModal.value = false }

const deleteUser = async (id, name) => {
  if (!confirm(`Permanently delete user "${name}"? This cannot be undone.`)) return
  deletingId.value = id
  try {
    await userService.delete(id)
    users.value = users.value.filter(u => u.id !== id)
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
      <p class="ul-empty-sub">Try Adjusting your search or filters to find what you're looking for.</p>
    </div>

    <!-- ══════ CARDS GRID ══════ -->
    <div v-else class="ul-grid">
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
/* ════════════════════════════════
   PAGE
════════════════════════════════ */
.ul-page {
  animation: pageIn 0.45s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pageIn {
  from { opacity:0; transform:translateY(10px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ════════════════════════════════
   HEADER
════════════════════════════════ */
.ul-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;
}
.ul-header-left { display: flex; align-items: center; gap: 1rem; }
.ul-icon-wrap {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #5D4037, #795548);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 16px rgba(93,64,55,0.25);
}
.ul-icon-wrap .material-symbols-outlined { color: #fff; font-size: 24px; }

.ul-title { font-size: 1.6rem; font-weight: 800; color: #3E2723; margin: 0; letter-spacing: -0.03em; }
.ul-subtitle { font-size: 0.82rem; color: #A1887F; margin: 0; }

.btn-add {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.65rem 1.35rem;
  background: linear-gradient(135deg, #5D4037, #795548);
  color: #fff; border: none; border-radius: 12px;
  font-size: 0.88rem; font-weight: 700; cursor: pointer;
  box-shadow: 0 4px 16px rgba(93,64,55,0.25);
  transition: all 0.2s ease; font-family: inherit;
}
.btn-add:hover { transform:translateY(-2px); box-shadow: 0 8px 24px rgba(93,64,55,0.3); }

/* ════════════════════════════════
   STATS
════════════════════════════════ */
.ul-stats {
  display: flex; align-items: center; gap: 0;
  background: #fff; border: 1px solid #EDE8E4;
  border-radius: 14px; padding: 1rem 1.5rem;
  margin-bottom: 1.5rem; box-shadow: 0 2px 12px rgba(93,64,55,0.05);
}
.stat-chip { flex: 1; display: flex; align-items: center; gap: 0.5rem; justify-content: center; }
.stat-ico { font-size: 20px; color: #8D6E63; }
.ico-red   { color: #ef4444 !important; }
.ico-blue  { color: #3b82f6 !important; }
.ico-green { color: #10b981 !important; }
.stat-val { font-size: 1.3rem; font-weight: 800; color: #3E2723; }
.stat-lbl { font-size: 0.78rem; color: #A1887F; font-weight: 500; }
.stat-divider { width: 1px; height: 32px; background: #EDE8E4; margin: 0 0.5rem; }

/* ════════════════════════════════
   TOOLBAR
════════════════════════════════ */
.ul-toolbar { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.search-wrap { position: relative; flex: 1; min-width: 250px; }
.search-ico { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); font-size: 19px; color: #A1887F; pointer-events: none; }
.search-input {
  width: 100%; padding: 0.7rem 2.5rem; background: #fff;
  border: 1.5px solid #D7CCC8; border-radius: 12px;
  font-size: 0.88rem; color: #3E2723; outline: none; transition: all 0.2s;
}
.search-input:focus { border-color: #A1887F; box-shadow: 0 0 0 3px rgba(161, 136, 127, 0.1); }

.filter-wrap { position: relative; width: 220px; }
.role-select {
  width: 100%; padding: 0.7rem 1.25rem; background: #fff;
  border: 1.5px solid #D7CCC8; border-radius: 12px;
  font-size: 0.88rem; color: #3E2723; outline: none;
  appearance: none; cursor: pointer; transition: all 0.2s;
}
.role-select:focus { border-color: #A1887F; }
.select-arrow { position: absolute; right: 0.8rem; top: 50%; transform: translateY(-50%); color: #A1887F; pointer-events: none; }

/* ════════════════════════════════
   ERROR / EMPTY
════════════════════════════════ */
.ul-error { display: flex; align-items: center; gap: 0.6rem; padding: 1rem; background: #FFF5F5; border: 1px solid #fca5a5; border-radius: 12px; color: #dc2626; }
.ul-empty { display: flex; flex-direction: column; align-items: center; padding: 5rem 1rem; text-align: center; }
.ul-empty-icon { font-size: 56px; color: #D7CCC8; margin-bottom: 1rem; }
.ul-empty-title { font-size: 1.1rem; font-weight: 700; color: #5D4037; margin: 0 0 0.35rem; }
.ul-empty-sub   { font-size: 0.85rem; color: #A1887F; margin: 0; }

/* ════════════════════════════════
   GRID
════════════════════════════════ */
.ul-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(310px, 1fr)); gap: 1.25rem;
}
.user-card {
  background: #fff; border: 1.5px solid #E0D7D0; border-radius: 18px;
  overflow: hidden; box-shadow: 0 2px 12px rgba(93,64,55,0.06);
  transition: all 0.25s ease; animation: cardIn 0.4s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes cardIn {
  from { opacity:0; transform:translateY(12px); }
  to   { opacity:1; transform:translateY(0); }
}
.user-card:hover { transform: translateY(-4px); box-shadow: 0 12px 36px rgba(93,64,55,0.15); border-color: #A1887F; }

.uc-body { padding: 1.5rem; }
.uc-top { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem; }
.uc-avatar {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #5D4037, #8D6E63);
  color: #fff; font-size: 0.9rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.uc-info { flex: 1; min-width: 0; }
.uc-name { font-size: 1.05rem; font-weight: 800; color: #3E2723; margin: 0 0 0.15rem; }
.uc-email { font-size: 0.8rem; color: #A1887F; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.uc-meta { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.uc-role-tag {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.25rem 0.65rem; border-radius: 8px; font-size: 0.72rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.03em;
}
.uc-role-tag.super_admin { background: #fee2e2; color: #991b1b; }
.uc-role-tag.branch_manager { background: #dbeafe; color: #1e40af; }
.uc-role-tag.sales_user { background: #dcfce7; color: #166534; }
.uc-role-tag .material-symbols-outlined { font-size: 14px; }

.uc-date { font-size: 0.72rem; color: #BCAAA4; font-weight: 500; }

.uc-actions { display: flex; gap: 0.5rem; }
.uc-btn-edit {
  flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
  padding: 0.55rem; background: rgba(93,64,55,0.06); color: #5D4037;
  border: none; border-radius: 10px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.uc-btn-edit:hover { background: rgba(93,64,55,0.12); }
.uc-btn-edit .material-symbols-outlined { font-size: 16px; }

.uc-btn-del {
  padding: 0.55rem; background: rgba(239,68,68,0.06); color: #dc2626;
  border: none; border-radius: 10px; cursor: pointer; transition: all 0.2s;
}
.uc-btn-del:hover:not(:disabled) { background: rgba(239,68,68,0.12); }

/* ════════════════════════════════
   MODAL
════════════════════════════════ */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 2000; background: rgba(30,15,10,0.45);
  backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.modal-box {
  width: 100%; max-width: 500px; border-radius: 22px; overflow: hidden;
  box-shadow: 0 32px 80px rgba(0,0,0,0.3); animation: modalIn 0.35s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes modalIn {
  from { opacity:0; transform:scale(0.93) translateY(20px); }
  to   { opacity:1; transform:scale(1)    translateY(0); }
}

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from,  .modal-fade-leave-to     { opacity: 0; }

@media (max-width: 640px) {
  .ul-stats { flex-wrap: wrap; gap: 0.75rem; }
  .stat-divider { display: none; }
  .filter-wrap { width: 100%; }
}
</style>
