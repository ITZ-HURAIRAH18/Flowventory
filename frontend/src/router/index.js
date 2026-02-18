import { createRouter, createWebHistory } from 'vue-router'

import Login from '@/views/Login.vue'
import Dashboard from '@/views/Dashboard.vue'
import AdminPanel from '@/views/AdminPanel.vue'
import BranchList from '@/views/branches/BranchList.vue'      // 👈 Branch list view
import BranchDashboard from '@/views/branches/BranchDashboard.vue' // 👈 Branch dashboard view
import BranchForm from '@/views/branches/BranchForm.vue'      // 👈 Optional for standalone form

const routes = [
  {
    path: '/login',
    component: Login
  },
  {
    path: '/dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  // 🔐 SUPER ADMIN ONLY ROUTE
  {
    path: '/admin',
    component: AdminPanel,
    meta: { requiresAuth: true, role: 'super_admin' }
  },
  // 🔐 BRANCH MANAGEMENT (Admin only)
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
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user'))

  // Not logged in
  if (to.meta.requiresAuth && !token) {
    return next('/login')
  }

  // Role check
  if (to.meta.role) {
    if (!user || user.role.name !== to.meta.role) {
      alert("You don't have permission to access this page")
      return next('/dashboard')
    }
  }

  next()
})

export default router