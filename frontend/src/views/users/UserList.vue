<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import userService from '@/services/userService'

const router = useRouter()
const users = ref([])
const search = ref('')
const roleFilter = ref('')
const loading = ref(false)
const error = ref('')

const fetchUsers = async () => {
  loading.value = true
  error.value = ''
  try {
    const params = {}
    if (search.value) params.search = search.value
    if (roleFilter.value) params.role = roleFilter.value

    const res = await userService.getAll(params)
    users.value = res.data.data
  } catch (err) {
    error.value = 'Failed to load users. Please try again.'
    console.error(err)
  }
  loading.value = false
}

const deleteUser = async (id, name) => {
  if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) return
  try {
    await userService.delete(id)
    fetchUsers()
  } catch (err) {
    if (err.response?.data?.message) {
      alert(err.response.data.message)
    } else {
      alert('Failed to delete user.')
    }
  }
}

const roleLabel = (roleName) => {
  const map = {
    super_admin: 'Super Admin',
    branch_manager: 'Branch Manager',
    sales_user: 'Sales User'
  }
  return map[roleName] || roleName
}

const roleBadgeClass = (roleName) => {
  const map = {
    super_admin: 'role-admin',
    branch_manager: 'role-manager',
    sales_user: 'role-sales'
  }
  return map[roleName] || ''
}

// Debounced search
let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchUsers, 300)
})

// Instant filter on role change
watch(roleFilter, fetchUsers)

onMounted(fetchUsers)
</script>

<template>
  <div class="user-page">

    <!-- Page Header -->
    <div class="page-header">
      <h1>
        <span class="material-symbols-outlined page-icon">group</span>
        User Management
      </h1>
      <p class="page-sub">Create and manage users, assign roles</p>
    </div>

    <!-- Toolbar: Search + Filter + Create -->
    <div class="toolbar">
      <div class="toolbar-filters">
        <!-- Search -->
        <div class="search-box">
          <span class="material-symbols-outlined search-icon">search</span>
          <input
            v-model="search"
            placeholder="Search by name or email..."
            class="search-input"
          />
        </div>

        <!-- Role Filter -->
        <select v-model="roleFilter" class="filter-select">
          <option value="">All Roles</option>
          <option value="super_admin">Super Admin</option>
          <option value="branch_manager">Branch Manager</option>
          <option value="sales_user">Sales User</option>
        </select>
      </div>

      <button @click="router.push('/users/create')" class="btn-create">
        <span class="material-symbols-outlined">person_add</span>
        Add User
      </button>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-banner">
      <span class="material-symbols-outlined">error</span>
      {{ error }}
      <button @click="fetchUsers" class="retry-btn">Retry</button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <span class="material-symbols-outlined spin">progress_activity</span>
      Loading users...
    </div>

    <!-- Empty State -->
    <div v-else-if="users.length === 0 && !error" class="empty-state">
      <span class="material-symbols-outlined empty-icon">person_off</span>
      <p>No users found</p>
      <p class="empty-hint" v-if="search || roleFilter">Try adjusting your search or filters</p>
    </div>

    <!-- User Table -->
    <div v-else-if="!error" class="table-section">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Role</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td class="user-cell">
                <div class="user-avatar-small">
                  {{ user.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) }}
                </div>
                <span class="user-name">{{ user.name }}</span>
              </td>
              <td class="email-cell">{{ user.email }}</td>
              <td>
                <span class="role-badge" :class="roleBadgeClass(user.role?.name)">
                  {{ roleLabel(user.role?.name) }}
                </span>
              </td>
              <td class="date-cell">
                {{ new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
              </td>
              <td class="actions-cell">
                <button
                  @click="router.push(`/users/${user.id}/edit`)"
                  class="action-btn edit-btn"
                  title="Edit User"
                >
                  <span class="material-symbols-outlined">edit</span>
                </button>
                <button
                  @click="deleteUser(user.id, user.name)"
                  class="action-btn delete-btn"
                  title="Delete User"
                >
                  <span class="material-symbols-outlined">delete</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.user-page {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ─── Page Header ─── */
.page-header {
  margin-bottom: 1.5rem;
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
  color: #818cf8;
}

.page-sub {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0;
}

/* ─── Toolbar ─── */
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.toolbar-filters {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.8rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  flex: 1;
  max-width: 320px;
  transition: border-color 0.2s;
}

.search-box:focus-within {
  border-color: rgba(99, 102, 241, 0.4);
}

.search-icon {
  font-size: 20px;
  color: #64748b;
  flex-shrink: 0;
}

.search-input {
  background: transparent;
  border: none;
  outline: none;
  color: #e2e8f0;
  font-size: 0.88rem;
  width: 100%;
}

.search-input::placeholder {
  color: #475569;
}

.filter-select {
  padding: 0.5rem 0.8rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  color: #e2e8f0;
  font-size: 0.88rem;
  cursor: pointer;
  transition: border-color 0.2s;
  min-width: 160px;
}

.filter-select:focus {
  outline: none;
  border-color: rgba(99, 102, 241, 0.4);
}

.filter-select option {
  background: #1e1e2e;
  color: #e2e8f0;
}

.btn-create {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.55rem 1.1rem;
  background: #646cff;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
  white-space: nowrap;
}

.btn-create:hover {
  background: #535bf2;
  transform: translateY(-1px);
}

.btn-create .material-symbols-outlined {
  font-size: 18px;
}

/* ─── Error Banner ─── */
.error-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: 8px;
  color: #f87171;
  font-size: 0.88rem;
  font-weight: 500;
  margin-bottom: 1.25rem;
}

.error-banner .material-symbols-outlined {
  font-size: 20px;
  flex-shrink: 0;
}

.retry-btn {
  margin-left: auto;
  padding: 0.3rem 0.8rem;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 6px;
  color: #f87171;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.retry-btn:hover {
  background: rgba(239, 68, 68, 0.25);
}

/* ─── Loading / Empty state ─── */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 3rem 1rem;
  color: #64748b;
  font-size: 0.9rem;
}

.empty-icon {
  font-size: 40px;
  color: #334155;
}

.empty-hint {
  font-size: 0.82rem;
  color: #475569;
  margin: 0;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
  font-size: 22px;
}

/* ─── Table Section ─── */
.table-section {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 14px;
  padding: 0.25rem;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  padding: 0.65rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

td {
  padding: 0.75rem 1rem;
  font-size: 0.88rem;
  color: #cbd5e1;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

tr:hover td {
  background: rgba(255, 255, 255, 0.02);
}

/* ─── User Cell with Avatar ─── */
.user-cell {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.user-avatar-small {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #a78bfa);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  flex-shrink: 0;
  letter-spacing: 0.02em;
}

.user-name {
  font-weight: 500;
  color: #e2e8f0;
}

.email-cell {
  color: #94a3b8;
  font-size: 0.85rem;
}

.date-cell {
  color: #64748b;
  font-size: 0.82rem;
}

/* ─── Role Badges ─── */
.role-badge {
  display: inline-block;
  padding: 0.2rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.role-admin {
  background: rgba(239, 68, 68, 0.1);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.role-manager {
  background: rgba(59, 130, 246, 0.1);
  color: #93c5fd;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.role-sales {
  background: rgba(34, 197, 94, 0.1);
  color: #86efac;
  border: 1px solid rgba(34, 197, 94, 0.2);
}

/* ─── Action Buttons ─── */
.actions-cell {
  display: flex;
  gap: 0.4rem;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.35rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 6px;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn .material-symbols-outlined {
  font-size: 18px;
}

.edit-btn {
  color: #60a5fa;
}

.edit-btn:hover {
  background: rgba(59, 130, 246, 0.1);
  border-color: rgba(59, 130, 246, 0.3);
}

.delete-btn {
  color: #f87171;
}

.delete-btn:hover {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.3);
}

/* ─── Responsive ─── */
@media (max-width: 640px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-filters {
    flex-direction: column;
  }

  .search-box {
    max-width: 100%;
  }

  .page-header h1 {
    font-size: 1.3rem;
  }
}
</style>
