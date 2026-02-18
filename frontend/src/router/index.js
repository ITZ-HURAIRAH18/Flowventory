import { createRouter, createWebHistory } from 'vue-router'

import Login from '@/views/Login.vue'
import Dashboard from '@/views/Dashboard.vue'
import AdminPanel from '@/views/AdminPanel.vue' // <-- import component

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
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

/* 🔒 ROUTE GUARD */
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
      return next('/dashboard')
    }
  }

  next()
})

export default router