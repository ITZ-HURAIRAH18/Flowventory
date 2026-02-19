<script setup>
import { ref, onMounted } from 'vue'

const user = ref(null)

onMounted(() => {
  const stored = localStorage.getItem('user')
  if (stored) {
    user.value = JSON.parse(stored)
  }
})
</script>

<template>
  <div class="dashboard-page">
    <!-- Welcome Section -->
    <div class="welcome-section">
      <div class="welcome-text">
        <h1>
          Welcome back,
          <span class="user-name">{{ user?.name || 'User' }}</span>
        </h1>
        <p class="welcome-sub">
          Here's what's happening with your inventory today.
        </p>
      </div>
      <div class="role-indicator">
        <span class="material-symbols-outlined">verified_user</span>
        <span>{{ user?.role?.name?.replace('_', ' ') }}</span>
      </div>
    </div>

    <!-- Quick Actions (role-aware) -->
    <div class="quick-actions">
      <h2 class="section-title">Quick Actions</h2>
      <div class="actions-grid">

        <!-- Super Admin actions -->
        <template v-if="user?.role?.name === 'super_admin'">
          <router-link to="/branches" class="action-card">
            <span class="material-symbols-outlined action-icon icon-blue">store</span>
            <span class="action-label">Branches</span>
            <span class="action-desc">Manage all branches</span>
          </router-link>

          <router-link to="/products" class="action-card">
            <span class="material-symbols-outlined action-icon icon-purple">inventory_2</span>
            <span class="action-label">Products</span>
            <span class="action-desc">Manage product catalog</span>
          </router-link>

          <router-link to="/inventory" class="action-card">
            <span class="material-symbols-outlined action-icon icon-emerald">warehouse</span>
            <span class="action-label">Inventory</span>
            <span class="action-desc">View all stock levels</span>
          </router-link>

          <router-link to="/reports" class="action-card">
            <span class="material-symbols-outlined action-icon icon-cyan">bar_chart</span>
            <span class="action-label">Reports</span>
            <span class="action-desc">View sales reports</span>
          </router-link>
        </template>

        <!-- Branch Manager actions -->
        <template v-if="user?.role?.name === 'branch_manager'">
          <router-link to="/inventory" class="action-card">
            <span class="material-symbols-outlined action-icon icon-emerald">warehouse</span>
            <span class="action-label">Inventory</span>
            <span class="action-desc">Manage branch stock</span>
          </router-link>

          <router-link to="/orders/create" class="action-card">
            <span class="material-symbols-outlined action-icon icon-amber">receipt_long</span>
            <span class="action-label">Orders</span>
            <span class="action-desc">Create new orders</span>
          </router-link>

          <router-link to="/reports" class="action-card">
            <span class="material-symbols-outlined action-icon icon-cyan">bar_chart</span>
            <span class="action-label">Reports</span>
            <span class="action-desc">View branch reports</span>
          </router-link>
        </template>

        <!-- Sales User actions -->
        <template v-if="user?.role?.name === 'sales_user'">
          <router-link to="/orders/create" class="action-card">
            <span class="material-symbols-outlined action-icon icon-amber">receipt_long</span>
            <span class="action-label">Create Order</span>
            <span class="action-desc">Place a new order</span>
          </router-link>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-page {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ─── Welcome Section ─── */
.welcome-section {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 2.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.welcome-text h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 0.4rem;
  line-height: 1.3;
}

.user-name {
  background: linear-gradient(135deg, #818cf8, #a78bfa);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.welcome-sub {
  color: #64748b;
  font-size: 0.95rem;
  margin: 0;
}

.role-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(99, 102, 241, 0.08);
  border: 1px solid rgba(99, 102, 241, 0.15);
  border-radius: 10px;
  color: #a5b4fc;
  font-size: 0.85rem;
  font-weight: 500;
  text-transform: capitalize;
}

.role-indicator .material-symbols-outlined {
  font-size: 18px;
  color: #6366f1;
}

/* ─── Quick Actions ─── */
.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #cbd5e1;
  margin: 0 0 1rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}

.action-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 1.25rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 14px;
  text-decoration: none;
  color: inherit;
  transition: all 0.25s ease;
  cursor: pointer;
}

.action-card:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(99, 102, 241, 0.25);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.action-icon {
  font-size: 28px;
  padding: 0.65rem;
  border-radius: 12px;
}

.icon-blue {
  color: #60a5fa;
  background: rgba(59, 130, 246, 0.1);
}

.icon-purple {
  color: #a78bfa;
  background: rgba(139, 92, 246, 0.1);
}

.icon-emerald {
  color: #34d399;
  background: rgba(16, 185, 129, 0.1);
}

.icon-amber {
  color: #fbbf24;
  background: rgba(245, 158, 11, 0.1);
}

.icon-cyan {
  color: #22d3ee;
  background: rgba(6, 182, 212, 0.1);
}

.action-label {
  font-size: 1rem;
  font-weight: 600;
  color: #e2e8f0;
}

.action-desc {
  font-size: 0.8rem;
  color: #64748b;
}

@media (max-width: 640px) {
  .welcome-text h1 {
    font-size: 1.35rem;
  }

  .actions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
