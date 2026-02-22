<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import branchService from '@/services/branchService'
import api from '@/services/api'

// UI Components
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorBanner from '@/components/ui/ErrorBanner.vue'

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
const globalError = ref('')

/* ── field validation ── */
const fieldErrors = reactive({ name: '', address: '', manager_id: '' })

const validate = () => {
  fieldErrors.name       = form.name.trim()    ? '' : 'Branch name is required.'
  fieldErrors.address    = form.address.trim() ? '' : 'Address is required.'
  fieldErrors.manager_id = form.manager_id     ? '' : 'Please assign a branch manager.'
  return !fieldErrors.name && !fieldErrors.address && !fieldErrors.manager_id
}

const fetchManagers = async () => {
  try {
    const res = await api.get('/users/managers')
    managers.value = res.data.map(m => ({ label: m.name, value: m.id }))
  } catch { /* silent */ }
}

const submitForm = async () => {
  if (!validate()) return
  saving.value = true
  globalError.value = ''
  try {
    if (isEdit.value) {
      await branchService.update(props.branch.id, form)
    } else {
      await branchService.create(form)
    }
    emit('saved')
    emit('close')
  } catch (err) {
    globalError.value = err?.response?.data?.message || 'An error occurred. Please try again.'
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

      <ErrorBanner :show="!!globalError" :error="globalError" />

      <BaseInput
        label="Branch Name"
        v-model="form.name"
        icon="store"
        placeholder="e.g. Main Street Branch"
        :error="fieldErrors.name"
        @input="fieldErrors.name = ''"
        required
      />

      <BaseInput
        label="Address"
        v-model="form.address"
        icon="location_on"
        placeholder="Full branch address"
        :error="fieldErrors.address"
        @input="fieldErrors.address = ''"
        required
      />

      <BaseSelect
        label="Branch Manager"
        v-model="form.manager_id"
        icon="manage_accounts"
        placeholder="— Select a manager —"
        :options="managers"
        :error="fieldErrors.manager_id"
        @change="fieldErrors.manager_id = ''"
        required
      />

      <!-- Actions -->
      <div class="bf-actions">
        <BaseButton variant="secondary" @click="$emit('close')">Cancel</BaseButton>
        <BaseButton 
          type="submit" 
          :loading="saving" 
          :icon="isEdit ? 'save' : 'add'"
          id="branch-save-btn"
        >
          {{ isEdit ? 'Update Branch' : 'Create Branch' }}
        </BaseButton>
      </div>

    </form>
  </div>
</template>


<style scoped>
@import "../../styles/views/BranchForm.css";
</style>