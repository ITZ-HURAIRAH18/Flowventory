<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import branchService from '@/services/branchService'
import api from '@/services/api'

const props = defineProps({ branch: Object })
const emit  = defineEmits(['saved', 'close'])

const isEdit = computed(() => !!props.branch?.id)

const form = reactive({
  name:       props.branch?.name       || '',
  address:    props.branch?.address    || '',
  manager_id: props.branch?.manager_id || '',
})

const managers  = ref([])
const saving    = ref(false)
const error     = ref('')

/* ── field validation ── */
const errors = reactive({ name: '', address: '', manager_id: '' })

const validate = () => {
  errors.name       = form.name.trim()    ? '' : 'Branch name is required.'
  errors.address    = form.address.trim() ? '' : 'Address is required.'
  errors.manager_id = form.manager_id     ? '' : 'Please assign a branch manager.'
  return !errors.name && !errors.address && !errors.manager_id
}

const fetchManagers = async () => {
  try {
    const res = await api.get('/users/managers')
    managers.value = res.data
  } catch { /* silent */ }
}

const submitForm = async () => {
  if (!validate()) return
  saving.value = true
  error.value  = ''
  try {
    if (isEdit.value) {
      await branchService.update(props.branch.id, form)
    } else {
      await branchService.create(form)
    }
    emit('saved')
    emit('close')
  } catch (err) {
    error.value = err?.response?.data?.message || 'An error occurred. Please try again.'
  } finally {
    saving.value = false
  }
}

onMounted(fetchManagers)
</script>

<template>
  <div class="bf-wrap">

    <!-- Header -->
    <div class="bf-head">
      <div class="bf-head-inner">
        <div class="bf-head-icon">
          <span class="material-symbols-outlined">{{ isEdit ? 'edit' : 'add_business' }}</span>
        </div>
        <div>
          <h2 class="bf-title">{{ isEdit ? 'Edit Branch' : 'New Branch' }}</h2>
          <p class="bf-subtitle">{{ isEdit ? 'Update branch details below.' : 'Fill in the details to create a new branch.' }}</p>
        </div>
      </div>
      <button class="bf-close" @click="$emit('close')" aria-label="Close">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <!-- Form -->
    <form class="bf-body" @submit.prevent="submitForm" novalidate>

      <!-- Error banner -->
      <div v-if="error" class="bf-error-banner">
        <span class="material-symbols-outlined">error</span>
        {{ error }}
      </div>

      <!-- Branch Name -->
      <div class="bf-field" :class="{ 'has-error': errors.name }">
        <label class="bf-label" for="branch-name">
          <span class="material-symbols-outlined">store</span>
          Branch Name
        </label>
        <input
          id="branch-name"
          v-model="form.name"
          class="bf-input"
          placeholder="e.g. Main Street Branch"
          @input="errors.name = ''"
        />
        <p v-if="errors.name" class="bf-err-msg">{{ errors.name }}</p>
      </div>

      <!-- Address -->
      <div class="bf-field" :class="{ 'has-error': errors.address }">
        <label class="bf-label" for="branch-address">
          <span class="material-symbols-outlined">location_on</span>
          Address
        </label>
        <input
          id="branch-address"
          v-model="form.address"
          class="bf-input"
          placeholder="Full branch address"
          @input="errors.address = ''"
        />
        <p v-if="errors.address" class="bf-err-msg">{{ errors.address }}</p>
      </div>

      <!-- Manager (required) -->
      <div class="bf-field" :class="{ 'has-error': errors.manager_id }">
        <label class="bf-label" for="branch-manager">
          <span class="material-symbols-outlined">manage_accounts</span>
          Branch Manager
        </label>
        <div class="bf-select-wrap">
          <select
            id="branch-manager"
            v-model="form.manager_id"
            class="bf-input bf-select"
            @change="errors.manager_id = ''"
          >
            <option value="">— Select a manager —</option>
            <option v-for="u in managers" :key="u.id" :value="u.id">{{ u.name }}</option>
          </select>
          <span class="material-symbols-outlined bf-select-arrow">expand_more</span>
        </div>
        <p v-if="errors.manager_id" class="bf-err-msg">{{ errors.manager_id }}</p>
      </div>

      <!-- Actions -->
      <div class="bf-actions">
        <button type="button" class="bf-btn-cancel" @click="$emit('close')">Cancel</button>
        <button type="submit" class="bf-btn-save" :disabled="saving" id="branch-save-btn">
          <span v-if="saving" class="btn-spinner"></span>
          <span v-else class="material-symbols-outlined">{{ isEdit ? 'save' : 'add' }}</span>
          {{ saving ? 'Saving…' : (isEdit ? 'Update Branch' : 'Create Branch') }}
        </button>
      </div>

    </form>
  </div>
</template>

<style scoped>
/* ════════════════════════════════
   WRAPPER
════════════════════════════════ */
.bf-wrap {
  background: #fff;
  font-family: 'Inter', system-ui, sans-serif;
}

/* ════════════════════════════════
   HEADER
════════════════════════════════ */
.bf-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 1.5rem 1.75rem 1.25rem;
  border-bottom: 1px solid #EDE8E4;
  background: linear-gradient(135deg, #5D4037, #795548);
}
.bf-head-inner {
  display: flex; align-items: center; gap: 0.85rem;
}
.bf-head-icon {
  width: 42px; height: 42px; border-radius: 12px;
  background: rgba(255,255,255,0.18);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.bf-head-icon .material-symbols-outlined { font-size: 22px; color: #fff; }

.bf-title {
  font-size: 1.1rem; font-weight: 800; color: #fff;
  margin: 0 0 0.15rem; letter-spacing: -0.02em;
}
.bf-subtitle {
  font-size: 0.78rem; color: rgba(255,255,255,0.65); margin: 0;
}
.bf-close {
  background: rgba(255,255,255,0.15); border: none;
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #fff; transition: background 0.2s;
  flex-shrink: 0; margin-top: 2px;
}
.bf-close:hover { background: rgba(255,255,255,0.28); }
.bf-close .material-symbols-outlined { font-size: 18px; }

/* ════════════════════════════════
   FORM BODY
════════════════════════════════ */
.bf-body {
  padding: 1.5rem 1.75rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.bf-error-banner {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #FFF5F5; border: 1px solid #fca5a5;
  border-radius: 10px; color: #dc2626; font-size: 0.83rem;
}
.bf-error-banner .material-symbols-outlined { font-size: 18px; }

/* ════════════════════════════════
   FIELD
════════════════════════════════ */
.bf-field { display: flex; flex-direction: column; gap: 0.4rem; }

.bf-label {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.82rem; font-weight: 600; color: #5D4037;
}
.bf-label .material-symbols-outlined { font-size: 16px; color: #8D6E63; }
.bf-optional {
  margin-left: auto;
  font-size: 0.7rem; color: #BCAAA4; font-weight: 400;
  background: #FAF8F6; border: 1px solid #EDE8E4;
  border-radius: 99px; padding: 0.1rem 0.5rem;
}

.bf-input {
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
.bf-input:focus {
  border-color: #A1887F;
  background: #fff;
}
.bf-input::placeholder { color: #BCAAA4; }

.has-error .bf-input { border-color: #f87171; background: #fff; }
.bf-err-msg { font-size: 0.76rem; color: #dc2626; margin: 0; }

/* Select */
.bf-select-wrap { position: relative; }
.bf-select { appearance: none; padding-right: 2.5rem; cursor: pointer; }
.bf-select-arrow {
  position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%);
  font-size: 20px; color: #A1887F; pointer-events: none;
}

/* ════════════════════════════════
   ACTIONS
════════════════════════════════ */
.bf-actions {
  display: flex; align-items: center; gap: 0.75rem;
  margin-top: 0.5rem;
}
.bf-btn-cancel {
  flex: 1;
  padding: 0.7rem;
  background: #FAF8F6;
  border: 1.5px solid #EDE8E4;
  border-radius: 11px;
  font-size: 0.88rem; font-weight: 600;
  color: #795548; cursor: pointer;
  font-family: inherit; transition: all 0.2s;
}
.bf-btn-cancel:hover { background: #fff; border-color: #D7CCC8; }

.bf-btn-save {
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
.bf-btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(93,64,55,0.3); }
.bf-btn-save:disabled { opacity: 0.65; cursor: not-allowed; }
.bf-btn-save .material-symbols-outlined { font-size: 18px; }

.btn-spinner {
  width: 16px; height: 16px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>