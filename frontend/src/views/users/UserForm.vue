<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import userService from '@/services/userService'

const props = defineProps({ userObject: Object })
const emit  = defineEmits(['saved', 'close'])

const isEdit = computed(() => !!props.userObject?.id)

const form = reactive({
  name:                  props.userObject?.name    || '',
  email:                 props.userObject?.email   || '',
  password:              '',
  password_confirmation: '',
  role_id:               props.userObject?.role_id || ''
})

const roles     = ref([])
const saving    = ref(false)
const error     = ref('')
const rolesLoad = ref(true)
const errors    = reactive({ name: '', email: '', role_id: '', password: '' })

/* ── fetch roles ── */
const fetchRoles = async () => {
  rolesLoad.value = true
  try {
    const res = await userService.getRoles()
    roles.value = res.data
  } catch {
    error.value = 'Failed to load user roles.'
  } finally {
    rolesLoad.value = false
  }
}

/* ── validation ── */
const validate = () => {
  errors.name   = form.name.trim() ? '' : 'Full name is required.'
  errors.email  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email) ? '' : 'Valid email is required.'
  errors.role_id = form.role_id ? '' : 'Please select a role.'
  
  if (!isEdit.value && !form.password) {
    errors.password = 'Password is required for new users.'
  } else if (form.password && form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters.'
  } else if (form.password !== form.password_confirmation) {
    errors.password = 'Passwords do not match.'
  } else {
    errors.password = ''
  }

  return !errors.name && !errors.email && !errors.role_id && !errors.password
}

const submitForm = async () => {
  if (!validate()) return
  saving.value = true
  error.value  = ''
  try {
    if (isEdit.value) {
      await userService.update(props.userObject.id, form)
    } else {
      await userService.create(form)
    }
    emit('saved')
    emit('close')
  } catch (err) {
    error.value = err?.response?.data?.message || 'An error occurred. Please try again.'
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const serverErrors = err.response.data.errors
      Object.keys(serverErrors).forEach(key => {
        if (errors.hasOwnProperty(key)) errors[key] = serverErrors[key][0]
      })
    }
  } finally {
    saving.value = false
  }
}

onMounted(fetchRoles)

const roleLabel = (name) => {
  const map = { super_admin: 'Super Admin', branch_manager: 'Branch Manager', sales_user: 'Sales User' }
  return map[name] || name
}
</script>

<template>
  <div class="uf-wrap">

    <!-- Header -->
    <div class="uf-head">
      <div class="uf-head-inner">
        <div class="uf-head-icon">
          <span class="material-symbols-outlined">{{ isEdit ? 'manage_accounts' : 'person_add' }}</span>
        </div>
        <div>
          <h2 class="uf-title">{{ isEdit ? 'Edit User' : 'New User Account' }}</h2>
          <p class="uf-subtitle">{{ isEdit ? 'Update profile and permissions.' : 'Setup a new user for the system.' }}</p>
        </div>
      </div>
      <button class="uf-close" @click="$emit('close')" aria-label="Close">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <!-- Form -->
    <form class="uf-body" @submit.prevent="submitForm" novalidate>

      <!-- Error banner -->
      <div v-if="error" class="uf-error-banner">
        <span class="material-symbols-outlined">error</span>
        {{ error }}
      </div>

      <!-- Full Name -->
      <div class="uf-field" :class="{ 'has-error': errors.name }">
        <label class="uf-label" for="user-name">
          <span class="material-symbols-outlined">badge</span>
          Full Name
        </label>
        <input
          id="user-name"
          v-model="form.name"
          class="uf-input"
          placeholder="e.g. John Doe"
          @input="errors.name = ''"
        />
        <p v-if="errors.name" class="uf-err-msg">{{ errors.name }}</p>
      </div>

      <!-- Email -->
      <div class="uf-field" :class="{ 'has-error': errors.email }">
        <label class="uf-label" for="user-email">
          <span class="material-symbols-outlined">mail</span>
          Email Address
        </label>
        <input
          id="user-email"
          v-model="form.email"
          type="email"
          class="uf-input"
          placeholder="john@flowventory.com"
          @input="errors.email = ''"
        />
        <p v-if="errors.email" class="uf-err-msg">{{ errors.email }}</p>
      </div>

      <!-- Role Selection -->
      <div class="uf-field" :class="{ 'has-error': errors.role_id }">
        <label class="uf-label" for="user-role">
          <span class="material-symbols-outlined">shield_person</span>
          System Role
        </label>
        <div class="uf-select-wrap">
          <select
            id="user-role"
            v-model="form.role_id"
            class="uf-input uf-select"
            :disabled="rolesLoad"
          >
            <option value="">— {{ rolesLoad ? 'Loading roles...' : 'Select a role' }} —</option>
            <option v-for="r in roles" :key="r.id" :value="r.id">{{ roleLabel(r.name) }}</option>
          </select>
          <span class="material-symbols-outlined uf-select-arrow">expand_more</span>
        </div>
        <p v-if="errors.role_id" class="uf-err-msg">{{ errors.role_id }}</p>
      </div>

      <!-- Passwords Row -->
      <div class="uf-row">
        <div class="uf-field" :class="{ 'has-error': errors.password }">
          <label class="uf-label" for="user-pass">
            <span class="material-symbols-outlined">lock</span>
            {{ isEdit ? 'New Password' : 'Password' }}
          </label>
          <input
            id="user-pass"
            v-model="form.password"
            type="password"
            class="uf-input"
            :placeholder="isEdit ? '••••••••' : 'Min 8 chars'"
            @input="errors.password = ''"
          />
        </div>
        <div class="uf-field">
          <label class="uf-label" for="user-conf">
            <span class="material-symbols-outlined">lock_reset</span>
            Confirm
          </label>
          <input
            id="user-conf"
            v-model="form.password_confirmation"
            type="password"
            class="uf-input"
            placeholder="••••••••"
          />
        </div>
      </div>
      <p v-if="errors.password" class="uf-err-msg">{{ errors.password }}</p>
      <p v-if="isEdit && !errors.password" class="uf-hint">
        <span class="material-symbols-outlined">info</span>
        Leave blank to keep current password.
      </p>

      <!-- Actions -->
      <div class="uf-actions">
        <button type="button" class="uf-btn-cancel" @click="$emit('close')">Cancel</button>
        <button type="submit" class="uf-btn-save" :disabled="saving" id="user-save-btn">
          <span v-if="saving" class="uf-spinner"></span>
          <span v-else class="material-symbols-outlined">{{ isEdit ? 'save' : 'person_add' }}</span>
          {{ saving ? 'Saving…' : (isEdit ? 'Update User' : 'Create User') }}
        </button>
      </div>

    </form>
  </div>
</template>

<style scoped>
/* ════════════════════════════════
   WRAPPER
════════════════════════════════ */
.uf-wrap {
  background: #fff;
  font-family: 'Inter', system-ui, sans-serif;
}

/* ════════════════════════════════
   HEADER
════════════════════════════════ */
.uf-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 1.5rem 1.75rem 1.25rem;
  background: linear-gradient(135deg, #5D4037, #795548);
}
.uf-head-inner {
  display: flex; align-items: center; gap: 0.85rem;
}
.uf-head-icon {
  width: 42px; height: 42px; border-radius: 12px;
  background: rgba(255,255,255,0.18);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.uf-head-icon .material-symbols-outlined { font-size: 22px; color: #fff; }

.uf-title {
  font-size: 1.1rem; font-weight: 800; color: #fff;
  margin: 0 0 0.15rem; letter-spacing: -0.02em;
}
.uf-subtitle {
  font-size: 0.78rem; color: rgba(255,255,255,0.9); margin: 0;
}
.uf-close {
  background: rgba(255,255,255,0.15); border: none;
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #fff; transition: background 0.2s;
  flex-shrink: 0; margin-top: 2px;
}
.uf-close:hover { background: rgba(255,255,255,0.28); }
.uf-close .material-symbols-outlined { font-size: 18px; }

/* ════════════════════════════════
   FORM BODY
════════════════════════════════ */
.uf-body {
  padding: 1.5rem 1.75rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.uf-error-banner {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #FFF5F5; border: 1px solid #fca5a5;
  border-radius: 10px; color: #dc2626; font-size: 0.83rem;
}
.uf-error-banner .material-symbols-outlined { font-size: 18px; }

/* ════════════════════════════════
   FIELD
════════════════════════════════ */
.uf-field { display: flex; flex-direction: column; gap: 0.4rem; }

.uf-label {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.82rem; font-weight: 600; color: #5D4037;
}
.uf-label .material-symbols-outlined { font-size: 16px; color: #8D6E63; }

.uf-input {
  width: 100%;
  padding: 0.7rem 0.95rem;
  background: #FAF8F6;
  border: 1.5px solid #EDE8E4;
  border-radius: 11px;
  font-size: 0.9rem;
  color: #3E2723;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s, background 0.2s;
  box-sizing: border-box;
}
.uf-input:focus {
  border-color: #A1887F;
  background: #fff;
}
.uf-input::placeholder { color: #BCAAA4; }

.has-error .uf-input { border-color: #f87171; background: #fff; }
.uf-err-msg { font-size: 0.76rem; color: #dc2626; margin: 0; }

.uf-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.uf-hint {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.75rem; color: #A1887F; margin: -0.5rem 0 0;
}
.uf-hint .material-symbols-outlined { font-size: 14px; }

/* Select */
.uf-select-wrap { position: relative; }
.uf-select { appearance: none; padding-right: 2.5rem; cursor: pointer; }
.uf-select-arrow {
  position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%);
  font-size: 20px; color: #A1887F; pointer-events: none;
}

/* ════════════════════════════════
   ACTIONS
════════════════════════════════ */
.uf-actions {
  display: flex; align-items: center; gap: 0.75rem;
  margin-top: 0.5rem;
}
.uf-btn-cancel {
  flex: 1;
  padding: 0.7rem;
  background: #FAF8F6;
  border: 1.5px solid #EDE8E4;
  border-radius: 11px;
  font-size: 0.88rem; font-weight: 600;
  color: #795548; cursor: pointer;
  font-family: inherit; transition: all 0.2s;
}
.uf-btn-cancel:hover { background: #fff; border-color: #D7CCC8; }

.uf-btn-save {
  flex: 2;
  display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
  padding: 0.7rem 1.25rem;
  background: linear-gradient(135deg, #5D4037, #795548);
  color: #fff;
  border: none; border-radius: 11px;
  font-size: 0.88rem; font-weight: 700;
  cursor: pointer; font-family: inherit;
  box-shadow: 0 4px 14px rgba(93,64,55,0.25);
  transition: all 0.2s;
}
.uf-btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(93,64,55,0.3); }
.uf-btn-save:disabled { opacity: 0.65; cursor: not-allowed; }
.uf-btn-save .material-symbols-outlined { font-size: 18px; }

.uf-spinner {
  width: 16px; height: 16px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 480px) {
  .uf-row { grid-template-columns: 1fr; }
}
</style>

