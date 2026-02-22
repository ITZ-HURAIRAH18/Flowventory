<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import userService from '@/services/userService'

// UI Components
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorBanner from '@/components/ui/ErrorBanner.vue'

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

const roles       = ref([])
const saving      = ref(false)
const globalError = ref('')
const rolesLoad   = ref(true)
const fieldErrors = reactive({ name: '', email: '', role_id: '', password: '' })

/* ── fetch roles ── */
const fetchRoles = async () => {
  rolesLoad.value = true
  try {
    const res = await userService.getRoles()
    roles.value = res.data.map(r => ({
      label: roleLabel(r.name),
      value: r.id
    }))
  } catch {
    globalError.value = 'Failed to load user roles.'
  } finally {
    rolesLoad.value = false
  }
}

/* ── validation ── */
const validate = () => {
  fieldErrors.name   = form.name.trim() ? '' : 'Full name is required.'
  fieldErrors.email  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email) ? '' : 'Valid email is required.'
  fieldErrors.role_id = form.role_id ? '' : 'Please select a role.'
  
  if (!isEdit.value && !form.password) {
    fieldErrors.password = 'Password is required for new users.'
  } else if (form.password && form.password.length < 8) {
    fieldErrors.password = 'Password must be at least 8 characters.'
  } else if (form.password !== form.password_confirmation) {
    fieldErrors.password = 'Passwords do not match.'
  } else {
    fieldErrors.password = ''
  }

  return !fieldErrors.name && !fieldErrors.email && !fieldErrors.role_id && !fieldErrors.password
}

const submitForm = async () => {
  if (!validate()) return
  saving.value = true
  globalError.value = ''
  
  try {
    if (isEdit.value) {
      await userService.update(props.userObject.id, form)
    } else {
      await userService.create(form)
    }
    emit('saved')
    emit('close')
  } catch (err) {
    globalError.value = err?.response?.data?.message || 'An error occurred. Please try again.'
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const serverErrors = err.response.data.errors
      Object.keys(serverErrors).forEach(key => {
        if (fieldErrors.hasOwnProperty(key)) fieldErrors[key] = serverErrors[key][0]
      })
    }
  } finally {
    saving.value = false
  }
}

function roleLabel(name) {
  const map = { super_admin: 'Super Admin', branch_manager: 'Branch Manager', sales_user: 'Sales User' }
  return map[name] || name
}

onMounted(fetchRoles)
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

      <ErrorBanner :show="!!globalError" :error="globalError" />

      <BaseInput
        label="Full Name"
        v-model="form.name"
        icon="badge"
        placeholder="e.g. John Doe"
        :error="fieldErrors.name"
        @input="fieldErrors.name = ''"
        required
      />

      <BaseInput
        label="Email Address"
        v-model="form.email"
        type="email"
        icon="mail"
        placeholder="john@flowventory.com"
        :error="fieldErrors.email"
        @input="fieldErrors.email = ''"
        required
      />

      <BaseSelect
        label="System Role"
        v-model="form.role_id"
        icon="shield_person"
        :placeholder="rolesLoad ? 'Loading roles...' : 'Select a role'"
        :options="roles"
        :error="fieldErrors.role_id"
        :disabled="rolesLoad"
        required
      />

      <div class="uf-row">
        <BaseInput
          type="password"
          :label="isEdit ? 'New Password' : 'Password'"
          v-model="form.password"
          icon="lock"
          :placeholder="isEdit ? '••••••••' : 'Min 8 chars'"
          :error="fieldErrors.password"
          @input="fieldErrors.password = ''"
          :required="!isEdit"
        />
        <BaseInput
          type="password"
          label="Confirm"
          v-model="form.password_confirmation"
          icon="lock_reset"
          placeholder="••••••••"
        />
      </div>

      <p v-if="isEdit && !fieldErrors.password" class="uf-hint">
        <span class="material-symbols-outlined">info</span>
        Leave blank to keep current password.
      </p>

      <!-- Actions -->
      <div class="uf-actions">
        <BaseButton variant="secondary" @click="$emit('close')">Cancel</BaseButton>
        <BaseButton 
          type="submit" 
          :loading="saving" 
          :icon="isEdit ? 'save' : 'person_add'"
          id="user-save-btn"
        >
          {{ isEdit ? 'Update User' : 'Create User' }}
        </BaseButton>
      </div>

    </form>
  </div>
</template>

<style scoped>
@import "@/styles/views/UserForm.css";
</style>


