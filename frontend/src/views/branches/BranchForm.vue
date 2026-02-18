<!-- src/views/branches/BranchForm.vue -->
<template>
  <div class="p-4 border bg-white rounded shadow">
    <h2 class="text-xl font-semibold mb-4">{{ branch?.id ? 'Edit Branch' : 'Add Branch' }}</h2>
    <form @submit.prevent="submitForm">
      <div class="mb-2">
        <label class="block">Name</label>
        <input v-model="form.name" required class="input"/>
      </div>
      <div class="mb-2">
        <label class="block">Address</label>
        <input v-model="form.address" required class="input"/>
      </div>
      <div class="mb-2">
        <label class="block">Manager</label>
        <select v-model="form.manager_id" class="input">
          <option value="">Select Manager</option>
          <option v-for="user in managers" :key="user.id" :value="user.id">{{ user.name }}</option>
        </select>
      </div>
      <div class="mt-4 flex gap-2">
        <button type="submit" class="btn btn-primary">{{ branch?.id ? 'Update' : 'Save' }}</button>
        <button type="button" @click="$emit('close')" class="btn btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import branchService from '@/services/branchService';
import api from '@/services/api';

const props = defineProps({
  branch: Object
});

const emit = defineEmits(['saved', 'close']);

const form = reactive({
  name: props.branch?.name || '',
  address: props.branch?.address || '',
  manager_id: props.branch?.manager_id || null,
});

const managers = ref([]);

const fetchManagers = async () => {
  try {
    const res = await api.get('/users/managers')
    managers.value = res.data
  } catch (err) {
    console.error('Cannot fetch managers', err)
  }
}

const submitForm = async () => {
  if (props.branch?.id) {
    await branchService.update(props.branch.id, form);
  } else {
    await branchService.create(form);
  }
  emit('saved');
  emit('close');
};

onMounted(fetchManagers);
</script>