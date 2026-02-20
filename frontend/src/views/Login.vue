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
    error.value = err.response?.data?.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-card">
      <!-- Branding -->
      <div class="login-brand">
        <div class="brand-icon">
          <span class="material-symbols-outlined">inventory</span>
        </div>
        <span class="brand-text">Flow<span class="brand-accent">ventory</span></span>
      </div>

      <h2>Welcome back</h2>
      <p class="login-sub">Sign in to your account to continue</p>

      <!-- Error Banner -->
      <div v-if="error" class="error-banner">
        <span class="material-symbols-outlined">error</span>
        {{ error }}
      </div>

      <form @submit.prevent="login">
        <div class="form-group">
          <label for="login-email">Email</label>
          <div class="input-wrapper">
            <span class="material-symbols-outlined input-icon">mail</span>
            <input
              id="login-email"
              v-model="email"
              type="email"
              placeholder="Enter your email"
              required
              autocomplete="email"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="login-password">Password</label>
          <div class="input-wrapper">
            <span class="material-symbols-outlined input-icon">lock</span>
            <input
              id="login-password"
              v-model="password"
              type="password"
              placeholder="Enter your password"
              required
              autocomplete="current-password"
            />
          </div>
        </div>

        <button type="submit" class="btn-login" :disabled="loading">
          <span v-if="loading" class="material-symbols-outlined spin">progress_activity</span>
          <span v-else class="material-symbols-outlined">login</span>
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 1rem;
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}

.login-card {
  width: 100%;
  max-width: 400px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 20px;
  padding: 2.5rem 2rem;
}

/* Brand */
.login-brand {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 2rem;
  justify-content: center;
}

.brand-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35);
}

.brand-icon .material-symbols-outlined {
  font-size: 22px;
  color: #fff;
}

.brand-text {
  font-size: 1.4rem;
  font-weight: 700;
  color: #e2e8f0;
}

.brand-accent {
  color: #818cf8;
}

/* Heading */
.login-card h2 {
  font-size: 1.4rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 0.3rem;
  text-align: center;
}

.login-sub {
  color: #64748b;
  font-size: 0.88rem;
  text-align: center;
  margin: 0 0 1.75rem;
}

/* Error */
.error-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.7rem 0.9rem;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: 10px;
  color: #f87171;
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 1.25rem;
}

.error-banner .material-symbols-outlined {
  font-size: 18px;
  flex-shrink: 0;
}

/* Form */
.form-group {
  margin-bottom: 1.15rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.4rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.input-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0 0.9rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  transition: border-color 0.2s;
}

.input-wrapper:focus-within {
  border-color: rgba(99, 102, 241, 0.4);
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
}

.input-icon {
  font-size: 20px;
  color: #475569;
  flex-shrink: 0;
}

.input-wrapper input {
  flex: 1;
  padding: 0.7rem 0;
  background: transparent;
  border: none;
  outline: none;
  color: #e2e8f0;
  font-size: 0.92rem;
  font-family: inherit;
}

.input-wrapper input::placeholder {
  color: #475569;
}

/* Button */
.btn-login {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.8rem;
  margin-top: 0.5rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s;
  font-family: inherit;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 24px rgba(99, 102, 241, 0.35);
}

.btn-login:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-login .material-symbols-outlined {
  font-size: 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
}
</style>