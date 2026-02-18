<!-- src/views/branches/BranchList.vue -->
<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Branches</h1>

    <button @click="showCreateForm = true" class="mb-4 btn btn-primary">Add Branch</button>

    <table class="table-auto w-full border">
      <thead>
        <tr>
          <th>Name</th>
          <th>Address</th>
          <th>Manager</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="branch in branches.data" :key="branch.id">
          <td>{{ branch.name }}</td>
          <td>{{ branch.address }}</td>
          <td>{{ branch.manager?.name || 'N/A' }}</td>
          <td>
            <button @click="editBranch(branch)" class="btn btn-sm btn-warning">Edit</button>
            <button @click="deleteBranch(branch.id)" class="btn btn-sm btn-danger">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showCreateForm">
      <BranchForm @saved="fetchBranches" @close="showCreateForm=false"/>
    </div>

    <div v-if="showEditForm">
      <BranchForm :branch="selectedBranch" @saved="fetchBranches" @close="showEditForm=false"/>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import branchService from '@/services/branchService';
import BranchForm from './BranchForm.vue';

const branches = ref({ data: [] });
const showCreateForm = ref(false);
const showEditForm = ref(false);
const selectedBranch = ref(null);

const fetchBranches = async () => {
  const response = await branchService.getAll();
  branches.value = response.data;
};

const editBranch = (branch) => {
  selectedBranch.value = branch;
  showEditForm.value = true;
};

const deleteBranch = async (id) => {
  if(confirm('Are you sure you want to delete this branch?')){
    await branchService.delete(id);
    fetchBranches();
  }
};

onMounted(fetchBranches);
</script>