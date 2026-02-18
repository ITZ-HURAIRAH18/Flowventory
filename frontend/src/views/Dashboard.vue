<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const user = ref(null)

onMounted(() => {
  const stored = localStorage.getItem('user')
  if (stored) {
    user.value = JSON.parse(stored)
  }
})

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}
</script>

<template>
  <div class="dashboard-container">
    <header>
      <h1>Dashboard</h1>
      <button @click="logout">Logout</button>
    </header>

    <div v-if="user">
      <p>Welcome, {{ user.name }}</p>
      <p>Role: {{ user.role?.name }}</p>
    </div>
  </div>
</template>
