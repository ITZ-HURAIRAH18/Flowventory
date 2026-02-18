<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref(null)
const loading = ref(false)

const login = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await api.post('/login', {
      email: email.value,
      password: password.value
    })

    localStorage.setItem('token', response.data.access_token)
    localStorage.setItem('user', JSON.stringify(response.data.user))

    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-container">
    <h2>Login</h2>

    <form @submit.prevent="login">
      <div>
        <input v-model="email" type="email" placeholder="Email" required />
      </div>

      <div>
        <input v-model="password" type="password" placeholder="Password" required />
      </div>

      <div v-if="error" class="error">
        {{ error }}
      </div>

      <button type="submit" :disabled="loading">
        {{ loading ? 'Logging in...' : 'Login' }}
      </button>
    </form>
  </div>
</template>