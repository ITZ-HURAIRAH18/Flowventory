import { createRouter, createWebHistory } from 'vue-router'

import Login from '@/views/Login.vue'
import Dashboard from '@/views/Dashboard.vue'
import AdminPanel from '@/views/AdminPanel.vue'

import BranchList from '@/views/branches/BranchList.vue'
import BranchDashboard from '@/views/branches/BranchDashboard.vue'
import BranchForm from '@/views/branches/BranchForm.vue'

import ProductList from '@/views/products/ProductList.vue'
import ProductForm from '@/views/products/ProductForm.vue'

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
    meta: { requiresAuth: true, role: 'super_admin' }
  },

  // Branch routes
  {
    path: '/branches',
    component: BranchList,
    meta: { requiresAuth: true, role: 'super_admin' }
  },
  {
    path: '/branches/:id',
    component: BranchDashboard,
    meta: { requiresAuth: true, role: 'super_admin' }
  },
  {
    path: '/branches/create',
    component: BranchForm,
    meta: { requiresAuth: true, role: 'super_admin' }
  },
  {
    path: '/branches/:id/edit',
    component: BranchForm,
    meta: { requiresAuth: true, role: 'super_admin' }
  },

  // ✅ Product routes
  {
    path: '/products',
    component: ProductList,
    meta: { requiresAuth: true, role: 'super_admin' }
  },
  {
    path: '/products/create',
    component: ProductForm,
    meta: { requiresAuth: true, role: 'super_admin' }
  },
  {
    path: '/products/:id/edit',
    component: ProductForm,
    meta: { requiresAuth: true, role: 'super_admin' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user'))

  if (to.meta.requiresAuth && !token) {
    return next('/login')
  }

  if (to.meta.role) {
    if (!user || user.role.name !== to.meta.role) {
      alert("You don't have permission to access this page")
      return next('/dashboard')
    }
  }

  next()
})

export default router