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
@import "../styles/views/Home.css";
</style>
