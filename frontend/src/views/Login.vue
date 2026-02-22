<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

// UI Components
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorBanner from '@/components/ui/ErrorBanner.vue'

const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref(null)
const loading = ref(false)
const showPassword = ref(false)

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

    <!-- ═══ Left Panel — Branding & Illustration ═══ -->
    <div class="login-left">
      <div class="left-content">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>

        <div class="left-brand">
          <div class="left-logo">
            <span class="material-symbols-outlined">inventory</span>
          </div>
          <span class="left-brand-text">Flow<span class="left-accent">ventory</span></span>
        </div>

        <h1 class="left-title">Smart Inventory<br/>& Order Management</h1>
        <p class="left-desc">
          Multi-branch inventory tracking, order processing, and real-time reporting — all in one platform.
        </p>

        <div class="feature-pills">
          <div class="pill"><span class="material-symbols-outlined">warehouse</span> Multi-Branch</div>
          <div class="pill"><span class="material-symbols-outlined">receipt_long</span> Orders</div>
          <div class="pill"><span class="material-symbols-outlined">bar_chart</span> Reports</div>
          <div class="pill"><span class="material-symbols-outlined">shield</span> Role-Based</div>
        </div>
      </div>
      <div class="left-footer"><span class="material-symbols-outlined footer-icon">package_2</span> Enterprise Inventory Solution</div>
    </div>

    <!-- ═══ Right Panel — Login Form ═══ -->
    <div class="login-right">
      <div class="form-container">
        
        <div class="mobile-brand">
          <div class="mobile-logo"><span class="material-symbols-outlined">inventory</span></div>
          <span class="mobile-brand-text">Flow<span class="mobile-accent">ventory</span></span>
        </div>

        <div class="form-header">
          <h2>Welcome back</h2>
          <p>Sign in to access your dashboard</p>
        </div>

        <ErrorBanner :show="!!error" :error="error" @close="error = null" />

        <form @submit.prevent="login" class="login-form">
          <BaseInput
            id="login-email"
            v-model="email"
            type="email"
            label="Email Address"
            icon="mail"
            placeholder="you@company.com"
            required
            autocomplete="email"
          />

          <BaseInput
            id="login-password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            label="Password"
            icon="lock"
            placeholder="Enter your password"
            required
            autocomplete="current-password"
          >
            <template #suffix>
              <button
                type="button"
                class="toggle-password"
                @click="showPassword = !showPassword"
                tabindex="-1"
              >
                <span class="material-symbols-outlined">
                  {{ showPassword ? 'visibility_off' : 'visibility' }}
                </span>
              </button>
            </template>
          </BaseInput>

          <BaseButton
            type="submit"
            :loading="loading"
            fullWidth
            icon="login"
            class="login-btn-spacing"
          >
            Sign In
          </BaseButton>
        </form>

        <div class="form-footer">
          <div class="footer-divider"><span>Secure Login</span></div>
          <p class="footer-note">
            <span class="material-symbols-outlined note-icon">lock</span>
            Protected with Laravel Sanctum authentication
          </p>
        </div>
      </div>
    </div>
  </div>
</template>


<style scoped>
@import "../styles/views/Login.css";
</style>