<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

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
        <!-- Decorative floating shapes -->
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

        <!-- Feature pills -->
        <div class="feature-pills">
          <div class="pill">
            <span class="material-symbols-outlined">warehouse</span>
            Multi-Branch
          </div>
          <div class="pill">
            <span class="material-symbols-outlined">receipt_long</span>
            Orders
          </div>
          <div class="pill">
            <span class="material-symbols-outlined">bar_chart</span>
            Reports
          </div>
          <div class="pill">
            <span class="material-symbols-outlined">shield</span>
            Role-Based
          </div>
        </div>
      </div>

      <!-- Bottom attribution -->
      <div class="left-footer">
        <span class="material-symbols-outlined footer-icon">package_2</span>
        Enterprise Inventory Solution
      </div>
    </div>

    <!-- ═══ Right Panel — Login Form ═══ -->
    <div class="login-right">
      <div class="form-container">

        <!-- Mobile brand (visible only on small screens) -->
        <div class="mobile-brand">
          <div class="mobile-logo">
            <span class="material-symbols-outlined">inventory</span>
          </div>
          <span class="mobile-brand-text">Flow<span class="mobile-accent">ventory</span></span>
        </div>

        <div class="form-header">
          <h2>Welcome back</h2>
          <p>Sign in to access your dashboard</p>
        </div>

        <!-- Error Banner -->
        <div v-if="error" class="error-banner">
          <span class="material-symbols-outlined">warning</span>
          <span>{{ error }}</span>
          <button class="error-close" @click="error = null">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <form @submit.prevent="login" class="login-form">

          <!-- Email -->
          <div class="form-group">
            <label for="login-email">Email Address</label>
            <div class="input-wrapper" :class="{ focused: false }">
              <span class="material-symbols-outlined input-icon">mail</span>
              <input
                id="login-email"
                v-model="email"
                type="email"
                placeholder="you@company.com"
                required
                autocomplete="email"
              />
            </div>
          </div>

          <!-- Password -->
          <div class="form-group">
            <label for="login-password">Password</label>
            <div class="input-wrapper">
              <span class="material-symbols-outlined input-icon">lock</span>
              <input
                id="login-password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Enter your password"
                required
                autocomplete="current-password"
              />
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
            </div>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn-login" :disabled="loading">
            <span v-if="loading" class="material-symbols-outlined spin">progress_activity</span>
            <span v-else class="material-symbols-outlined btn-icon">login</span>
            {{ loading ? 'Signing in...' : 'Sign In' }}
          </button>
        </form>

        <!-- Footer info -->
        <div class="form-footer">
          <div class="footer-divider">
            <span>Secure Login</span>
          </div>
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
/* ═══════════════════════════════════════════
   COLOR TOKENS — White & Brown Palette
   ═══════════════════════════════════════════ */
:root {
  /* Brown spectrum */
  --brown-900: #3E2723;
  --brown-800: #4E342E;
  --brown-700: #5D4037;
  --brown-600: #6D4C41;
  --brown-500: #795548;
  --brown-400: #8D6E63;
  --brown-300: #A1887F;
  --brown-200: #BCAAA4;
  --brown-100: #D7CCC8;
  --brown-50:  #EFEBE9;

  /* Warm neutrals */
  --warm-950: #1C1210;
  --warm-900: #2A1F1B;
  --warm-800: #3D2E28;
  --warm-700: #5A4A42;
  --warm-100: #F5F0ED;
  --warm-50:  #FAF8F6;

  /* Accent */
  --accent: #8D6E63;
  --accent-light: #A1887F;
  --accent-dark: #5D4037;
}

/* ═══════════════════════════════════════════
   PAGE LAYOUT — Split screen
   ═══════════════════════════════════════════ */
.login-page {
  display: flex;
  min-height: 100vh;
  background: #FAF8F6;
  animation: pageIn 0.6s ease;
}

@keyframes pageIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* ═══════════════════════════════════════════
   LEFT PANEL — Brand showcase
   ═══════════════════════════════════════════ */
.login-left {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  width: 48%;
  padding: 3rem;
  background: linear-gradient(160deg, #3E2723 0%, #5D4037 40%, #6D4C41 70%, #795548 100%);
  position: relative;
  overflow: hidden;
}

.left-content {
  position: relative;
  z-index: 2;
  max-width: 440px;
  animation: slideRight 0.7s ease 0.15s both;
}

@keyframes slideRight {
  from { opacity: 0; transform: translateX(-30px); }
  to { opacity: 1; transform: translateX(0); }
}

/* ─── Decorative shapes ─── */
.shape {
  position: absolute;
  border-radius: 50%;
  opacity: 0.06;
  background: #fff;
}

.shape-1 {
  width: 320px;
  height: 320px;
  top: -80px;
  right: -60px;
}

.shape-2 {
  width: 200px;
  height: 200px;
  bottom: 60px;
  left: -50px;
}

.shape-3 {
  width: 140px;
  height: 140px;
  top: 40%;
  right: 15%;
  opacity: 0.04;
}

/* ─── Left brand logo ─── */
.left-brand {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  margin-bottom: 2.5rem;
}

.left-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.left-logo .material-symbols-outlined {
  font-size: 26px;
  color: #fff;
}

.left-brand-text {
  font-size: 1.6rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: -0.02em;
}

.left-accent {
  color: #D7CCC8;
}

/* ─── Left title & description ─── */
.left-title {
  font-size: 2.25rem;
  font-weight: 800;
  color: #fff;
  line-height: 1.2;
  margin: 0 0 1rem;
  letter-spacing: -0.03em;
}

.left-desc {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.65;
  margin: 0 0 2.25rem;
  max-width: 380px;
}

/* ─── Feature pills ─── */
.feature-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem 0.85rem;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 100px;
  color: rgba(255, 255, 255, 0.88);
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.01em;
  transition: background 0.25s, transform 0.2s;
}

.pill:hover {
  background: rgba(255, 255, 255, 0.18);
  transform: translateY(-1px);
}

.pill .material-symbols-outlined {
  font-size: 16px;
}

/* ─── Left footer ─── */
.left-footer {
  position: absolute;
  bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: rgba(255, 255, 255, 0.35);
  font-size: 0.75rem;
  font-weight: 500;
  letter-spacing: 0.04em;
  z-index: 2;
}

.footer-icon {
  font-size: 16px;
}

/* ═══════════════════════════════════════════
   RIGHT PANEL — Login form
   ═══════════════════════════════════════════ */
.login-right {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: #FFFFFF;
}

.form-container {
  width: 100%;
  max-width: 400px;
  animation: slideUp 0.6s ease 0.25s both;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ─── Mobile brand (hidden on desktop) ─── */
.mobile-brand {
  display: none;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 2rem;
  justify-content: center;
}

.mobile-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, #5D4037, #795548);
}

.mobile-logo .material-symbols-outlined {
  font-size: 22px;
  color: #fff;
}

.mobile-brand-text {
  font-size: 1.35rem;
  font-weight: 800;
  color: #3E2723;
}

.mobile-accent {
  color: #8D6E63;
}

/* ─── Form Header ─── */
.form-header {
  margin-bottom: 2rem;
}

.form-header h2 {
  font-size: 1.65rem;
  font-weight: 800;
  color: #3E2723;
  margin: 0 0 0.35rem;
  letter-spacing: -0.02em;
}

.form-header p {
  color: #8D6E63;
  font-size: 0.92rem;
  margin: 0;
  font-weight: 500;
}

/* ─── Error Banner ─── */
.error-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #FFF5F5;
  border: 1px solid #FED7D7;
  border-radius: 12px;
  color: #C53030;
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 1.5rem;
  animation: shakeIn 0.4s ease;
}

@keyframes shakeIn {
  0%, 100% { transform: translateX(0); }
  20% { transform: translateX(-6px); }
  40% { transform: translateX(6px); }
  60% { transform: translateX(-3px); }
  80% { transform: translateX(3px); }
}

.error-banner .material-symbols-outlined {
  font-size: 20px;
  flex-shrink: 0;
  color: #E53E3E;
}

.error-close {
  margin-left: auto;
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px;
  border-radius: 4px;
  display: flex;
  color: #C53030;
  transition: background 0.15s;
}

.error-close:hover {
  background: rgba(197, 48, 48, 0.1);
}

.error-close .material-symbols-outlined {
  font-size: 18px;
}

/* ─── Form Group ─── */
.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.82rem;
  font-weight: 700;
  color: #5D4037;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.input-wrapper {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0 1rem;
  background: #FAF8F6;
  border: 1.5px solid #E8E0DB;
  border-radius: 12px;
  transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
}

.input-wrapper:focus-within {
  border-color: #8D6E63;
  box-shadow: 0 0 0 3px rgba(141, 110, 99, 0.12);
  background: #fff;
}

.input-icon {
  font-size: 20px;
  color: #A1887F;
  flex-shrink: 0;
  transition: color 0.25s;
}

.input-wrapper:focus-within .input-icon {
  color: #5D4037;
}

.input-wrapper input {
  flex: 1;
  padding: 0.8rem 0;
  background: transparent;
  border: none;
  outline: none;
  color: #3E2723;
  font-size: 0.95rem;
  font-family: 'Inter', sans-serif;
  font-weight: 500;
}

.input-wrapper input::placeholder {
  color: #BCAAA4;
  font-weight: 400;
}

/* ─── Password toggle ─── */
.toggle-password {
  display: flex;
  align-items: center;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  color: #A1887F;
  transition: color 0.2s, background 0.2s;
}

.toggle-password:hover {
  color: #5D4037;
  background: rgba(93, 64, 55, 0.06);
}

.toggle-password .material-symbols-outlined {
  font-size: 20px;
}

/* ─── Login Button ─── */
.btn-login {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.85rem;
  margin-top: 0.75rem;
  background: linear-gradient(135deg, #5D4037 0%, #6D4C41 50%, #795548 100%);
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: 'Inter', sans-serif;
  letter-spacing: 0.01em;
  position: relative;
  overflow: hidden;
}

.btn-login::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, transparent 60%);
  opacity: 0;
  transition: opacity 0.3s;
}

.btn-login:hover:not(:disabled)::before {
  opacity: 1;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(62, 39, 35, 0.3), 0 2px 8px rgba(62, 39, 35, 0.15);
}

.btn-login:active:not(:disabled) {
  transform: translateY(0);
}

.btn-login:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
}

.btn-icon {
  font-size: 20px;
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

/* ─── Form Footer ─── */
.form-footer {
  margin-top: 2rem;
}

.footer-divider {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.footer-divider::before,
.footer-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #E8E0DB;
}

.footer-divider span {
  font-size: 0.72rem;
  font-weight: 600;
  color: #BCAAA4;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  white-space: nowrap;
}

.footer-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  color: #A1887F;
  font-size: 0.78rem;
  font-weight: 500;
  margin: 0;
}

.note-icon {
  font-size: 14px;
  color: #BCAAA4;
}

/* ═══════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════ */
@media (max-width: 900px) {
  .login-page {
    flex-direction: column;
  }

  .login-left {
    width: 100%;
    padding: 2.5rem 2rem 2rem;
    min-height: auto;
  }

  .left-content {
    max-width: 100%;
  }

  .left-title {
    font-size: 1.75rem;
  }

  .left-desc {
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
  }

  .left-footer {
    display: none;
  }

  .login-right {
    padding: 2rem 1.5rem 3rem;
  }
}

@media (max-width: 600px) {
  .login-left {
    display: none;
  }

  .mobile-brand {
    display: flex;
  }

  .login-right {
    min-height: 100vh;
    align-items: flex-start;
    padding-top: 3rem;
  }

  .form-header h2 {
    text-align: center;
  }

  .form-header p {
    text-align: center;
  }
}
</style>