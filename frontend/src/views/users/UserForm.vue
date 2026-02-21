<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import userService from '@/services/userService'

const route = useRoute()
const router = useRouter()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role_id: ''
})

const roles = ref([])
const errors = ref({})
const generalError = ref('')
const loading = ref(false)
const rolesLoading = ref(true)
const isEdit = !!route.params.id

// Fetch roles for dropdown
const fetchRoles = async () => {
  rolesLoading.value = true
  try {
    const res = await userService.getRoles()
    roles.value = res.data
  } catch (err) {
    generalError.value = 'Failed to load roles.'
  }
  rolesLoading.value = false
}

// If editing, fetch existing user data
const fetchUser = async () => {
  if (!isEdit) return
  try {
    const res = await userService.get(route.params.id)
    const user = res.data
    form.name = user.name
    form.email = user.email
    form.role_id = user.role_id
    // Don't populate password fields on edit
  } catch (err) {
    generalError.value = 'Failed to load user data.'
  }
}

onMounted(async () => {
  await fetchRoles()
  await fetchUser()
})

const roleLabel = (roleName) => {
  const map = {
    super_admin: 'Super Admin',
    branch_manager: 'Branch Manager',
    sales_user: 'Sales User'
  }
  return map[roleName] || roleName
}

const submit = async () => {
  errors.value = {}
  generalError.value = ''
  loading.value = true

  try {
    if (isEdit) {
      await userService.update(route.params.id, form)
    } else {
      await userService.create(form)
    }
    router.push('/users')
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else if (err.response?.data?.message) {
      generalError.value = err.response.data.message
    } else {
      generalError.value = 'An unexpected error occurred. Please try again.'
    }
  }

  loading.value = false
}
</script>

<template>
  <div class="user-form-page">
    <router-link to="/users" class="back-link">
      <span class="material-symbols-outlined">arrow_back</span>
      Back to Users
    </router-link>

    <h2>
      <span class="material-symbols-outlined title-icon">{{ isEdit ? 'edit' : 'person_add' }}</span>
      {{ isEdit ? 'Edit User' : 'Create New User' }}
    </h2>
    <p class="form-subtitle">
      {{ isEdit ? 'Update the user details below.' : 'Fill in the details to create a new user account.' }}
    </p>

    <!-- General error banner -->
    <div v-if="generalError" class="error-banner">
      <span class="material-symbols-outlined">error</span>
      {{ generalError }}
    </div>

    <form @submit.prevent="submit">

      <!-- Name -->
      <div class="form-group" :class="{ 'has-error': errors.name }">
        <label for="user-name">
          <span class="material-symbols-outlined label-icon">badge</span>
          Full Name
        </label>
        <input
          id="user-name"
          v-model="form.name"
          type="text"
          placeholder="Enter full name"
          required
        />
        <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
      </div>

      <!-- Email -->
      <div class="form-group" :class="{ 'has-error': errors.email }">
        <label for="user-email">
          <span class="material-symbols-outlined label-icon">mail</span>
          Email Address
        </label>
        <input
          id="user-email"
          v-model="form.email"
          type="email"
          placeholder="user@example.com"
          required
        />
        <span v-if="errors.email" class="field-error">{{ errors.email[0] }}</span>
      </div>

      <!-- Role -->
      <div class="form-group" :class="{ 'has-error': errors.role_id }">
        <label for="user-role">
          <span class="material-symbols-outlined label-icon">shield_person</span>
          Role
        </label>
        <select id="user-role" v-model="form.role_id" required :disabled="rolesLoading">
          <option value="" disabled>{{ rolesLoading ? 'Loading roles...' : 'Select a role' }}</option>
          <option v-for="role in roles" :key="role.id" :value="role.id">
            {{ roleLabel(role.name) }}
          </option>
        </select>
        <span v-if="errors.role_id" class="field-error">{{ errors.role_id[0] }}</span>
      </div>

      <!-- Password -->
      <div class="form-row">
        <div class="form-group" :class="{ 'has-error': errors.password }">
          <label for="user-password">
            <span class="material-symbols-outlined label-icon">lock</span>
            {{ isEdit ? 'New Password' : 'Password' }}
          </label>
          <input
            id="user-password"
            v-model="form.password"
            type="password"
            :placeholder="isEdit ? 'Leave blank to keep current' : 'Min 8 characters'"
            :required="!isEdit"
          />
          <span v-if="errors.password" class="field-error">{{ errors.password[0] }}</span>
        </div>

        <div class="form-group" :class="{ 'has-error': errors.password_confirmation }">
          <label for="user-password-confirm">
            <span class="material-symbols-outlined label-icon">lock_reset</span>
            Confirm Password
          </label>
          <input
            id="user-password-confirm"
            v-model="form.password_confirmation"
            type="password"
            :placeholder="isEdit ? 'Leave blank to keep current' : 'Re-enter password'"
            :required="!isEdit"
          />
        </div>
      </div>

      <!-- Password hint for edit mode -->
      <p v-if="isEdit" class="password-hint">
        <span class="material-symbols-outlined hint-icon">info</span>
        Leave password fields empty to keep the current password unchanged.
      </p>

      <button type="submit" :disabled="loading" class="btn-submit">
        <span v-if="loading" class="material-symbols-outlined spin">progress_activity</span>
        <span v-else class="material-symbols-outlined btn-icon">{{ isEdit ? 'save' : 'person_add' }}</span>
        {{ loading ? 'Saving...' : (isEdit ? 'Update User' : 'Create User') }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.user-form-page {
  max-width: 560px;
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #818cf8;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 1rem;
  transition: color 0.2s;
}

.back-link:hover {
  color: #a5b4fc;
}

.back-link .material-symbols-outlined {
  font-size: 18px;
}

.user-form-page h2 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.5rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 0.25rem;
}

.title-icon {
  font-size: 26px;
  color: #818cf8;
}

.form-subtitle {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0 0 1.5rem;
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

/* ─── Form Group ─── */
.form-group {
  margin-bottom: 1.2rem;
}

.form-group label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.4rem;
  font-weight: 600;
  font-size: 0.9rem;
  color: #cbd5e1;
}

.label-icon {
  font-size: 16px;
  color: #64748b;
}

.form-group select,
.form-group input {
  width: 100%;
  padding: 0.6rem 0.8rem;
  border: 1px solid #444;
  border-radius: 6px;
  background: #1a1a2e;
  color: #eee;
  font-size: 0.95rem;
  box-sizing: border-box;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group select:focus,
.form-group input:focus {
  outline: none;
  border-color: #646cff;
  box-shadow: 0 0 0 2px rgba(100, 108, 255, 0.25);
}

.form-group select option {
  background: #1a1a2e;
  color: #eee;
}

/* Error state */
.form-group.has-error select,
.form-group.has-error input {
  border-color: #ef4444;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.15);
}

.field-error {
  display: block;
  margin-top: 0.35rem;
  color: #f87171;
  font-size: 0.8rem;
  font-weight: 500;
}

/* ─── Side-by-side row ─── */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

@media (max-width: 480px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

/* ─── Password Hint ─── */
.password-hint {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin: -0.5rem 0 1.2rem;
  padding: 0.5rem 0.75rem;
  background: rgba(99, 102, 241, 0.06);
  border: 1px solid rgba(99, 102, 241, 0.15);
  border-radius: 6px;
  color: #94a3b8;
  font-size: 0.78rem;
  font-weight: 500;
}

.hint-icon {
  font-size: 16px;
  color: #818cf8;
  flex-shrink: 0;
}

/* ─── Submit Button ─── */
.btn-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.7rem;
  background: #646cff;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  margin-top: 0.5rem;
}

.btn-submit:hover:not(:disabled) {
  background: #535bf2;
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-icon {
  font-size: 18px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
  font-size: 18px;
}
</style>
