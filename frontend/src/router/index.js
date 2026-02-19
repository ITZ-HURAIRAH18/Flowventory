import { createRouter, createWebHistory } from 'vue-router'

import Login from '@/views/Login.vue'
import Dashboard from '@/views/Dashboard.vue'
import AdminPanel from '@/views/AdminPanel.vue'

import BranchList from '@/views/branches/BranchList.vue'
import BranchDashboard from '@/views/branches/BranchDashboard.vue'
import BranchForm from '@/views/branches/BranchForm.vue'

import ProductList from '@/views/products/ProductList.vue'
import ProductForm from '@/views/products/ProductForm.vue'

// ✅ Inventory Views
import InventoryDashboard from '@/views/inventory/InventoryDashboard.vue'
import StockAdd from '@/views/inventory/StockAdd.vue'
import StockAdjust from '@/views/inventory/StockAdjust.vue'
import StockTransfer from '@/views/inventory/StockTransfer.vue'
import StockHistory from '@/views/inventory/StockHistory.vue'
import BranchReport from '@/views/reports/BranchReport.vue'
import ReportsList from '@/views/reports/ReportsList.vue'

// ✅ Order Views
import OrderCreate from '@/views/orders/OrderCreate.vue'

const routes = [
  { path: '/login', component: Login },

  {
    path: '/dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },

  {
    path: '/admin',
    component: AdminPanel,
    meta: { requiresAuth: true, roles: ['super_admin'] }
  },

  // =============================
  // Branch routes (Super Admin only)
  // =============================
  {
    path: '/branches',
    component: BranchList,
    meta: { requiresAuth: true, roles: ['super_admin'] }
  },
  {
    path: '/branches/create',
    component: BranchForm,
    meta: { requiresAuth: true, roles: ['super_admin'] }
  },
  {
    path: '/branches/:id/edit',
    component: BranchForm,
    meta: { requiresAuth: true, roles: ['super_admin'] }
  },
  {
    path: '/branches/:id',
    component: BranchDashboard,
    meta: { requiresAuth: true, roles: ['super_admin'] }
  },

  // =============================
  // Product routes (Super Admin only)
  // =============================
  {
    path: '/products',
    component: ProductList,
    meta: { requiresAuth: true, roles: ['super_admin'] }
  },
  {
    path: '/products/create',
    component: ProductForm,
    meta: { requiresAuth: true, roles: ['super_admin'] }
  },
  {
    path: '/products/:id/edit',
    component: ProductForm,
    meta: { requiresAuth: true, roles: ['super_admin'] }
  },

  // =============================
  // Inventory routes (Super Admin + Branch Manager)
  // =============================
  {
    path: '/inventory',
    component: InventoryDashboard,
    meta: { requiresAuth: true, roles: ['super_admin', 'branch_manager'] }
  },
  {
    path: '/inventory/add',
    component: StockAdd,
    meta: { requiresAuth: true, roles: ['super_admin', 'branch_manager'] }
  },
  {
    path: '/inventory/adjust',
    component: StockAdjust,
    meta: { requiresAuth: true, roles: ['super_admin', 'branch_manager'] }
  },
  {
    path: '/inventory/transfer',
    component: StockTransfer,
    meta: { requiresAuth: true, roles: ['super_admin', 'branch_manager'] }
  },
  {
    path: '/inventory/history',
    component: StockHistory,
    meta: { requiresAuth: true, roles: ['super_admin', 'branch_manager'] }
  },

  // =============================
  // Order routes (Branch Manager + Sales User)
  // Per assignment spec: Admin = No, Manager = Yes, Sales = Yes
  // =============================
  {
    path: '/orders/create',
    component: OrderCreate,
    meta: { requiresAuth: true, roles: ['branch_manager', 'sales_user'] }
  },

  // =============================
  // Report routes (Super Admin + Branch Manager)
  // =============================
  {
    path: '/reports',
    component: ReportsList,
    meta: { requiresAuth: true, roles: ['super_admin', 'branch_manager'] }
  },
  {
    path: '/branches/:id/report',
    component: BranchReport,
    meta: { requiresAuth: true, roles: ['super_admin', 'branch_manager'] },
    props: route => ({ branchId: route.params.id })
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user'))

  // Check if route requires authentication
  if (to.meta.requiresAuth && !token) {
    return next('/login')
  }

  // Check if route requires specific roles (supports MULTIPLE roles)
  if (to.meta.roles) {
    if (!user || !user.role || !to.meta.roles.includes(user.role.name)) {
      alert("You don't have permission to access this page")
      return next('/dashboard')
    }
  }

  next()
})

export default router