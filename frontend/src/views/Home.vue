<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import LoadingScreen from '@/components/LoadingScreen.vue'

const router = useRouter()
const loading = ref(true)
const showContent = ref(false)

// Called by LoadingScreen when its timer fires
const onLoadDone = () => {
  loading.value = false
  setTimeout(() => (showContent.value = true), 100)
}

const goToLogin = () => {
  router.push('/login')
}
</script>

<template>
  <!-- ═══════════════════════════════════════════
       LOADING SCREEN — Reusable Component
       ═══════════════════════════════════════════ -->
  <LoadingScreen
    v-if="loading"
    message="Preparing your workspace…"
    :duration="2800"
    @done="onLoadDone"
  />

  <!-- ═══════════════════════════════════════════
       HOME PAGE — Professional Landing
       ═══════════════════════════════════════════ -->
  <div v-if="!loading" class="home-page" :class="{ 'home-visible': showContent }" id="home-page">

    <!-- ── Floating shapes background ── -->
    <div class="bg-shapes">
      <div class="bg-shape bg-shape-1"></div>
      <div class="bg-shape bg-shape-2"></div>
      <div class="bg-shape bg-shape-3"></div>
    </div>

    <!-- ══════════ NAVBAR ══════════ -->
    <nav class="home-nav" id="home-nav">
      <div class="nav-inner">
        <div class="nav-brand">
          <div class="nav-logo-box">
            <span class="material-symbols-outlined">inventory</span>
          </div>
          <span class="nav-brand-text">Flow<span class="nav-brand-accent">ventory</span></span>
        </div>
        <button class="nav-cta" id="nav-login-btn" @click="goToLogin">
          <span class="material-symbols-outlined btn-icon-sm">login</span>
          Sign In
        </button>
      </div>
    </nav>

    <!-- ══════════ HERO SECTION ══════════ -->
    <section class="hero" id="hero-section">
      <div class="hero-inner">
        <div class="hero-content">
          <div class="hero-badge">
            <span class="material-symbols-outlined badge-icon">rocket_launch</span>
            Multi-Branch Inventory Platform
          </div>
          <h1 class="hero-title">
            Smart <span class="hero-gradient">Inventory</span><br/>
            & Order Management
          </h1>
          <p class="hero-desc">
            Streamline your multi-branch operations with real-time stock tracking, 
            intelligent order processing, role-based access control, and comprehensive 
            reporting — all in one powerful platform.
          </p>
          <div class="hero-actions">
            <button class="btn-primary" id="hero-login-btn" @click="goToLogin">
              <span class="material-symbols-outlined">arrow_forward</span>
              Get Started
            </button>
            <a href="#features" class="btn-secondary" id="hero-features-btn">
              <span class="material-symbols-outlined">visibility</span>
              Explore Features
            </a>
          </div>

          <!-- Trust indicators -->
          <div class="trust-row">
            <div class="trust-item">
              <span class="material-symbols-outlined trust-icon">verified</span>
              <span>Enterprise Security</span>
            </div>
            <div class="trust-item">
              <span class="material-symbols-outlined trust-icon">speed</span>
              <span>Real-Time Sync</span>
            </div>
            <div class="trust-item">
              <span class="material-symbols-outlined trust-icon">devices</span>
              <span>All Devices</span>
            </div>
          </div>
        </div>

        <!-- Hero visual -->
        <div class="hero-visual">
          <div class="hero-card hero-card-main">
            <div class="card-header-row">
              <div class="card-dot card-dot-green"></div>
              <div class="card-dot card-dot-yellow"></div>
              <div class="card-dot card-dot-red"></div>
            </div>
            <div class="card-metric-grid">
              <div class="metric-card metric-brown">
                <span class="material-symbols-outlined metric-icon">inventory_2</span>
                <div class="metric-info">
                  <span class="metric-value">2,847</span>
                  <span class="metric-label">Products</span>
                </div>
              </div>
              <div class="metric-card metric-warm">
                <span class="material-symbols-outlined metric-icon">store</span>
                <div class="metric-info">
                  <span class="metric-value">12</span>
                  <span class="metric-label">Branches</span>
                </div>
              </div>
              <div class="metric-card metric-tan">
                <span class="material-symbols-outlined metric-icon">receipt_long</span>
                <div class="metric-info">
                  <span class="metric-value">1,204</span>
                  <span class="metric-label">Orders</span>
                </div>
              </div>
              <div class="metric-card metric-coffee">
                <span class="material-symbols-outlined metric-icon">trending_up</span>
                <div class="metric-info">
                  <span class="metric-value">+18%</span>
                  <span class="metric-label">Growth</span>
                </div>
              </div>
            </div>
            <!-- Mini bar chart -->
            <div class="mini-chart">
              <div class="chart-bar" style="height: 40%"></div>
              <div class="chart-bar" style="height: 65%"></div>
              <div class="chart-bar" style="height: 45%"></div>
              <div class="chart-bar" style="height: 80%"></div>
              <div class="chart-bar" style="height: 55%"></div>
              <div class="chart-bar" style="height: 90%"></div>
              <div class="chart-bar" style="height: 70%"></div>
            </div>
          </div>

          <!-- Floating notification cards -->
          <div class="floating-card float-card-1">
            <span class="material-symbols-outlined fc-icon fc-icon-green">check_circle</span>
            <div class="fc-text">
              <span class="fc-title">Stock Updated</span>
              <span class="fc-sub">Branch A — 250 units added</span>
            </div>
          </div>

          <div class="floating-card float-card-2">
            <span class="material-symbols-outlined fc-icon fc-icon-brown">local_shipping</span>
            <div class="fc-text">
              <span class="fc-title">Transfer Complete</span>
              <span class="fc-sub">Branch B → Branch C</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ FEATURES SECTION ══════════ -->
    <section class="features-section" id="features">
      <div class="section-inner">
        <div class="section-header">
          <span class="section-badge">
            <span class="material-symbols-outlined">auto_awesome</span>
            Features
          </span>
          <h2 class="section-title">Everything You Need to<br/><span class="title-gradient">Manage Your Inventory</span></h2>
          <p class="section-desc">
            A comprehensive suite of tools designed for modern multi-branch businesses.
          </p>
        </div>

        <div class="features-grid">
          <div class="feature-card" id="feature-branches">
            <div class="feature-icon-wrap icon-wrap-1">
              <span class="material-symbols-outlined">store</span>
            </div>
            <h3>Multi-Branch Management</h3>
            <p>Create and manage unlimited branches. Each branch has its own inventory, staff, and reporting dashboard.</p>
          </div>

          <div class="feature-card" id="feature-products">
            <div class="feature-icon-wrap icon-wrap-2">
              <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <h3>Product Catalog</h3>
            <p>Maintain a centralized product catalog with SKUs, pricing, categories, and real-time stock levels across branches.</p>
          </div>

          <div class="feature-card" id="feature-orders">
            <div class="feature-icon-wrap icon-wrap-3">
              <span class="material-symbols-outlined">receipt_long</span>
            </div>
            <h3>Order Processing</h3>
            <p>Create, track, and fulfill orders efficiently. Automatic stock deduction ensures inventory accuracy.</p>
          </div>

          <div class="feature-card" id="feature-stock">
            <div class="feature-icon-wrap icon-wrap-4">
              <span class="material-symbols-outlined">warehouse</span>
            </div>
            <h3>Stock Operations</h3>
            <p>Add stock, adjust quantities, and transfer between branches with full audit trail and history tracking.</p>
          </div>

          <div class="feature-card" id="feature-reports">
            <div class="feature-icon-wrap icon-wrap-5">
              <span class="material-symbols-outlined">bar_chart</span>
            </div>
            <h3>Analytics & Reports</h3>
            <p>Generate detailed branch reports with sales data, stock summaries, and performance insights at a glance.</p>
          </div>

          <div class="feature-card" id="feature-roles">
            <div class="feature-icon-wrap icon-wrap-6">
              <span class="material-symbols-outlined">shield</span>
            </div>
            <h3>Role-Based Access</h3>
            <p>Three-tier access control — Super Admin, Branch Manager, and Sales User — each with tailored permissions.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ FOOTER ══════════ -->
    <footer class="home-footer" id="home-footer">
      <div class="section-inner footer-inner">
        <div class="footer-brand">
          <div class="nav-logo-box footer-logo-box">
            <span class="material-symbols-outlined">inventory</span>
          </div>
          <span class="footer-brand-text">Flow<span class="nav-brand-accent">ventory</span></span>
        </div>
        <p class="footer-copy">&copy; 2026 Flowventory. Smart Inventory Management System.</p>

      </div>
    </footer>
  </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════
   HOME PAGE STYLES
   Loader styles have been moved to LoadingScreen.vue
   ═══════════════════════════════════════════ */

/* ═══════════════════════════════════════════
   HOME PAGE — White & Brown Base
   ═══════════════════════════════════════════ */
.home-page {
  min-height: 100vh;
  background: #FAF8F6;
  color: #3E2723;
  position: relative;
  overflow: hidden;
  opacity: 0;
  transform: translateY(12px);
  transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

.home-page.home-visible {
  opacity: 1;
  transform: translateY(0);
}

/* ── Background shapes ── */
.bg-shapes {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.bg-shape {
  position: absolute;
  border-radius: 50%;
  opacity: 0.04;
}

.bg-shape-1 { width: 600px; height: 600px; top: -200px; right: -100px; background: #8D6E63; }
.bg-shape-2 { width: 400px; height: 400px; bottom: -100px; left: -80px; background: #5D4037; }
.bg-shape-3 { width: 300px; height: 300px; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #A1887F; }

/* ═══════════════════════════════════════════
   NAVBAR — Brown Gradient
   ═══════════════════════════════════════════ */
.home-nav {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(250, 248, 246, 0.85);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid #E8E0DB;
}

.nav-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
  height: 68px;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.nav-logo-box {
  width: 38px;
  height: 38px;
  border-radius: 11px;
  background: linear-gradient(135deg, #5D4037, #795548);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(93, 64, 55, 0.3);
}

.nav-logo-box .material-symbols-outlined {
  font-size: 20px;
  color: #fff;
}

.nav-brand-text {
  font-size: 1.3rem;
  font-weight: 800;
  color: #3E2723;
  letter-spacing: -0.02em;
}

.nav-brand-accent {
  color: #8D6E63;
}

.nav-cta {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.55rem 1.25rem;
  background: linear-gradient(135deg, #5D4037, #795548);
  border: none;
  border-radius: 10px;
  color: #fff;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s ease;
  font-family: 'Inter', sans-serif;
}

.nav-cta:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 24px rgba(62, 39, 35, 0.3);
}

.btn-icon-sm {
  font-size: 18px;
}

/* ═══════════════════════════════════════════
   HERO SECTION
   ═══════════════════════════════════════════ */
.hero {
  padding: 5rem 2rem 4rem;
  position: relative;
  z-index: 1;
}

.hero-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 1rem;
  background: rgba(141, 110, 99, 0.08);
  border: 1px solid rgba(141, 110, 99, 0.18);
  border-radius: 100px;
  color: #795548;
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  margin-bottom: 1.5rem;
  animation: fadeInUp 0.6s ease 0.1s both;
}

.badge-icon {
  font-size: 16px;
  color: #8D6E63;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 800;
  line-height: 1.1;
  color: #3E2723;
  letter-spacing: -0.04em;
  margin: 0 0 1.5rem;
  animation: fadeInUp 0.6s ease 0.2s both;
}

.hero-gradient {
  background: linear-gradient(135deg, #5D4037, #8D6E63, #A1887F);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-desc {
  font-size: 1.1rem;
  color: #795548;
  line-height: 1.7;
  margin: 0 0 2rem;
  max-width: 520px;
  animation: fadeInUp 0.6s ease 0.3s both;
}

.hero-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2.5rem;
  animation: fadeInUp 0.6s ease 0.4s both;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.85rem 1.75rem;
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
  box-shadow: 0 4px 20px rgba(62, 39, 35, 0.3);
  text-decoration: none;
  position: relative;
  overflow: hidden;
}

.btn-primary::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, transparent 60%);
  opacity: 0;
  transition: opacity 0.3s;
}

.btn-primary:hover::before {
  opacity: 1;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(62, 39, 35, 0.4);
}

.btn-primary .material-symbols-outlined {
  font-size: 20px;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.85rem 1.75rem;
  background: transparent;
  color: #5D4037;
  border: 1.5px solid #D7CCC8;
  border-radius: 12px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s ease;
  font-family: 'Inter', sans-serif;
  text-decoration: none;
}

.btn-secondary:hover {
  background: rgba(141, 110, 99, 0.06);
  border-color: #A1887F;
  color: #3E2723;
}

.btn-secondary .material-symbols-outlined {
  font-size: 20px;
}

.btn-lg {
  padding: 1rem 2.25rem;
  font-size: 1.05rem;
}

/* ── Trust Row ── */
.trust-row {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  animation: fadeInUp 0.6s ease 0.5s both;
}

.trust-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  color: #8D6E63;
  font-size: 0.82rem;
  font-weight: 500;
}

.trust-icon {
  font-size: 16px;
  color: #6D4C41;
}

/* ── Hero Visual (Dashboard preview) ── */
.hero-visual {
  position: relative;
  animation: fadeInRight 0.8s ease 0.4s both;
}

.hero-card-main {
  background: #fff;
  border: 1px solid #E8E0DB;
  border-radius: 20px;
  padding: 1.25rem;
  box-shadow: 0 20px 60px rgba(62, 39, 35, 0.1), 0 4px 16px rgba(62, 39, 35, 0.06);
}

.card-header-row {
  display: flex;
  gap: 6px;
  margin-bottom: 1.25rem;
}

.card-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.card-dot-red { background: #ef4444; }
.card-dot-yellow { background: #eab308; }
.card-dot-green { background: #22c55e; }

.card-metric-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.metric-card {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.85rem;
  border-radius: 14px;
  border: 1px solid #EFEBE9;
}

.metric-brown  { background: rgba(93, 64, 55, 0.04); }
.metric-warm   { background: rgba(141, 110, 99, 0.06); }
.metric-tan    { background: rgba(161, 136, 127, 0.06); }
.metric-coffee { background: rgba(121, 85, 72, 0.05); }

.metric-icon {
  font-size: 22px;
}

.metric-brown  .metric-icon { color: #5D4037; }
.metric-warm   .metric-icon { color: #8D6E63; }
.metric-tan    .metric-icon { color: #A1887F; }
.metric-coffee .metric-icon { color: #6D4C41; }

.metric-info {
  display: flex;
  flex-direction: column;
}

.metric-value {
  font-size: 1.15rem;
  font-weight: 800;
  color: #3E2723;
  line-height: 1.2;
}

.metric-label {
  font-size: 0.72rem;
  color: #A1887F;
  font-weight: 500;
}

/* ── Mini Chart ── */
.mini-chart {
  display: flex;
  align-items: flex-end;
  gap: 6px;
  height: 60px;
  padding: 0.75rem;
  background: #FAF8F6;
  border-radius: 12px;
  border: 1px solid #EFEBE9;
}

.chart-bar {
  flex: 1;
  background: linear-gradient(180deg, #795548, rgba(141, 110, 99, 0.3));
  border-radius: 4px 4px 0 0;
  animation: barGrow 0.8s ease forwards;
  transform-origin: bottom;
}

@keyframes barGrow {
  from { transform: scaleY(0); }
  to { transform: scaleY(1); }
}

/* ── Floating notification cards ── */
.floating-card {
  position: absolute;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.7rem 1rem;
  background: #fff;
  border: 1px solid #E8E0DB;
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(62, 39, 35, 0.1);
}

.float-card-1 {
  top: -10px;
  right: -10px;
  animation: floatCard 3s ease-in-out infinite;
}

.float-card-2 {
  bottom: 30px;
  left: -20px;
  animation: floatCard 3s ease-in-out 1.5s infinite;
}

@keyframes floatCard {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

.fc-icon {
  font-size: 22px;
  padding: 0.4rem;
  border-radius: 10px;
}

.fc-icon-green {
  color: #6D4C41;
  background: rgba(109, 76, 65, 0.08);
}

.fc-icon-brown {
  color: #795548;
  background: rgba(121, 85, 72, 0.08);
}

.fc-text {
  display: flex;
  flex-direction: column;
}

.fc-title {
  font-size: 0.82rem;
  font-weight: 700;
  color: #3E2723;
}

.fc-sub {
  font-size: 0.7rem;
  color: #A1887F;
  font-weight: 500;
}

/* ═══════════════════════════════════════════
   SHARED SECTION STYLES
   ═══════════════════════════════════════════ */
.section-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.section-header {
  text-align: center;
  margin-bottom: 3.5rem;
}

.section-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.9rem;
  background: rgba(141, 110, 99, 0.08);
  border: 1px solid rgba(141, 110, 99, 0.15);
  border-radius: 100px;
  color: #795548;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 1.25rem;
}

.section-badge .material-symbols-outlined {
  font-size: 16px;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #3E2723;
  line-height: 1.15;
  letter-spacing: -0.03em;
  margin: 0 0 1rem;
}

.title-gradient {
  background: linear-gradient(135deg, #5D4037, #8D6E63);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.section-desc {
  font-size: 1.05rem;
  color: #8D6E63;
  max-width: 560px;
  margin: 0 auto;
  line-height: 1.6;
}

/* ═══════════════════════════════════════════
   FEATURES GRID
   ═══════════════════════════════════════════ */
.features-section {
  padding: 5rem 0;
  position: relative;
  z-index: 1;
  background: #fff;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
}

.feature-card {
  padding: 1.75rem;
  background: #FAF8F6;
  border: 1px solid #EFEBE9;
  border-radius: 18px;
  transition: all 0.35s ease;
  cursor: default;
}

.feature-card:hover {
  background: #fff;
  border-color: #D7CCC8;
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(62, 39, 35, 0.08);
}

.feature-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

.feature-icon-wrap .material-symbols-outlined {
  font-size: 24px;
}

.icon-wrap-1 { background: rgba(62, 39, 35, 0.08);  color: #3E2723; }
.icon-wrap-2 { background: rgba(93, 64, 55, 0.08);  color: #5D4037; }
.icon-wrap-3 { background: rgba(109, 76, 65, 0.08); color: #6D4C41; }
.icon-wrap-4 { background: rgba(121, 85, 72, 0.08); color: #795548; }
.icon-wrap-5 { background: rgba(141, 110, 99, 0.08); color: #8D6E63; }
.icon-wrap-6 { background: rgba(161, 136, 127, 0.08); color: #A1887F; }

.feature-card h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #3E2723;
  margin: 0 0 0.6rem;
}

.feature-card p {
  font-size: 0.88rem;
  color: #8D6E63;
  line-height: 1.6;
  margin: 0;
}

/* ═══════════════════════════════════════════
   STATS SECTION
   ═══════════════════════════════════════════ */
.stats-section {
  padding: 4rem 0;
  position: relative;
  z-index: 1;
  background: #FAF8F6;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
}

.stat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 2rem 1.25rem;
  background: #fff;
  border: 1px solid #EFEBE9;
  border-radius: 18px;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  border-color: #D7CCC8;
  box-shadow: 0 8px 24px rgba(62, 39, 35, 0.06);
}

.stat-icon-wrap {
  width: 46px;
  height: 46px;
  border-radius: 13px;
  background: linear-gradient(135deg, rgba(93, 64, 55, 0.08), rgba(141, 110, 99, 0.06));
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

.stat-icon-wrap .material-symbols-outlined {
  font-size: 22px;
  color: #795548;
}

.stat-number {
  font-size: 1.55rem;
  font-weight: 800;
  color: #3E2723;
  letter-spacing: -0.02em;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.82rem;
  color: #A1887F;
  font-weight: 500;
}

/* ═══════════════════════════════════════════
   CTA SECTION
   ═══════════════════════════════════════════ */
.cta-section {
  padding: 5rem 0;
  position: relative;
  z-index: 1;
  background: #fff;
}

.cta-inner {
  position: relative;
  overflow: hidden;
  background: linear-gradient(160deg, #3E2723 0%, #5D4037 40%, #6D4C41 70%, #795548 100%);
  border: none;
  border-radius: 24px;
  padding: 4rem 2rem;
  text-align: center;
}

.cta-glow {
  position: absolute;
  width: 400px;
  height: 400px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  filter: blur(100px);
  top: -150px;
  left: 50%;
  transform: translateX(-50%);
  pointer-events: none;
}

.cta-content {
  position: relative;
  z-index: 1;
}

.cta-content h2 {
  font-size: 2.2rem;
  font-weight: 800;
  color: #fff;
  margin: 0 0 1rem;
  letter-spacing: -0.03em;
}

.cta-gradient {
  color: #D7CCC8;
}

.cta-content p {
  font-size: 1.05rem;
  color: rgba(255, 255, 255, 0.7);
  margin: 0 0 2rem;
  max-width: 480px;
  margin-left: auto;
  margin-right: auto;
  line-height: 1.6;
}

.cta-section .btn-primary {
  background: #fff;
  color: #3E2723;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.cta-section .btn-primary:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}

/* ═══════════════════════════════════════════
   FOOTER
   ═══════════════════════════════════════════ */
.home-footer {
  padding: 2.5rem 0;
  border-top: 1px solid #EFEBE9;
  position: relative;
  z-index: 1;
  background: #FAF8F6;
}

.footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 0.55rem;
}

.footer-logo-box {
  width: 32px;
  height: 32px;
  border-radius: 9px;
}

.footer-logo-box .material-symbols-outlined {
  font-size: 17px;
}

.footer-brand-text {
  font-size: 1.1rem;
  font-weight: 800;
  color: #4E342E;
}

.footer-copy {
  font-size: 0.8rem;
  color: #A1887F;
  margin: 0;
}

/* ═══════════════════════════════════════════
   ANIMATIONS
   ═══════════════════════════════════════════ */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInRight {
  from { opacity: 0; transform: translateX(30px); }
  to { opacity: 1; transform: translateX(0); }
}

/* ═══════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════ */
@media (max-width: 1024px) {
  .hero-inner {
    grid-template-columns: 1fr;
    gap: 3rem;
  }

  .hero-title {
    font-size: 2.8rem;
  }

  .hero-visual {
    max-width: 500px;
  }
}

@media (max-width: 768px) {
  .hero {
    padding: 3rem 1.5rem;
  }

  .hero-title {
    font-size: 2.2rem;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .section-title {
    font-size: 1.8rem;
  }

  .footer-inner {
    flex-direction: column;
    text-align: center;
  }

  .floating-card {
    display: none;
  }

  .trust-row {
    flex-wrap: wrap;
    gap: 1rem;
  }

  .nav-inner {
    padding: 0 1.25rem;
  }
}

@media (max-width: 480px) {
  .hero-title {
    font-size: 1.8rem;
  }

  .hero-desc {
    font-size: 0.95rem;
  }

  .hero-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-primary, .btn-secondary {
    justify-content: center;
  }

  .card-metric-grid {
    grid-template-columns: 1fr;
  }
}
</style>
