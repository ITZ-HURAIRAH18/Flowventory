import { ref, computed, readonly } from 'vue'
import api from '@/services/api'

// Global auth state
const user = ref(null)
const isAuthenticated = ref(false)
const isLoading = ref(false)
const authError = ref(null)

// Initialize from localStorage
const initializeAuth = () => {
  const stored = localStorage.getItem('user')
  const token = localStorage.getItem('token')
  
  if (stored && token && stored !== 'null' && token !== 'null' && stored !== 'undefined' && token !== 'undefined') {
    try {
      const userData = JSON.parse(stored)
      user.value = userData
      isAuthenticated.value = true
    } catch (error) {
      console.error('Failed to parse stored user data:', error)
      clearAuth()
    }
  } else {
    clearAuth()
  }
}

// Force refresh authentication from localStorage
const refreshAuth = () => {
  initializeAuth()
}

// Clear authentication state
const clearAuth = () => {
  user.value = null
  isAuthenticated.value = false
  authError.value = null
  
  // Clear localStorage completely
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  
  // Also clear any potentially corrupted entries
  if (localStorage.getItem('token') === 'null' || localStorage.getItem('token') === 'undefined') {
    localStorage.removeItem('token')
  }
  if (localStorage.getItem('user') === 'null' || localStorage.getItem('user') === 'undefined') {
    localStorage.removeItem('user')
  }
}

// Login user
const login = async (credentials) => {
  isLoading.value = true
  authError.value = null
  
  // Clear any existing auth state first
  clearAuth()
  
  try {
    const response = await api.post('/login', credentials)
    
    const { access_token, user: userData } = response.data
    
    // Store in localStorage
    localStorage.setItem('token', access_token)
    localStorage.setItem('user', JSON.stringify(userData))
    
    // Update reactive state
    user.value = userData
    isAuthenticated.value = true
    
    return { success: true, user: userData }
  } catch (error) {
    const message = error.response?.data?.message || 'Login failed. Please try again.'
    authError.value = message
    clearAuth()
    throw new Error(message)
  } finally {
    isLoading.value = false
  }
}

// Logout user
const logout = async () => {
  isLoading.value = true
  
  try {
    // Call API logout (ignore errors since token might be expired)
    await api.post('/logout').catch(() => {})
  } finally {
    clearAuth()
    isLoading.value = false
  }
}

// Update user data
const updateUser = (userData) => {
  user.value = { ...user.value, ...userData }
  localStorage.setItem('user', JSON.stringify(user.value))
}

// Computed properties
const userRole = computed(() => user.value?.role?.name || null)
const userInitials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name
    .split(' ')
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const roleLabel = computed(() => {
  const map = {
    super_admin: 'Super Admin',
    branch_manager: 'Branch Manager', 
    sales_user: 'Sales User'
  }
  return map[userRole.value] || userRole.value || 'User'
})

const roleBadgeClass = computed(() => {
  const map = {
    super_admin: 'badge-admin',
    branch_manager: 'badge-manager',
    sales_user: 'badge-sales'
  }
  return map[userRole.value] || ''
})

// Permission helpers
const hasPermission = (permission) => {
  if (!user.value?.role) return false
  
  const permissions = {
    super_admin: ['manage-branches', 'manage-products', 'manage-users', 'manage-inventory', 'view-reports'],
    branch_manager: ['manage-inventory', 'create-orders', 'view-reports'],
    sales_user: ['create-orders']
  }
  
  return permissions[userRole.value]?.includes(permission) || false
}

const canAccess = (route) => {
  if (!isAuthenticated.value) return false
  
  // Route access mapping
  const routePermissions = {
    '/branches': 'manage-branches',
    '/products': 'manage-products', 
    '/users': 'manage-users',
    '/inventory': 'manage-inventory',
    '/orders': 'create-orders',
    '/reports': 'view-reports'
  }
  
  const permission = routePermissions[route]
  return permission ? hasPermission(permission) : true
}

// Navigation links based on permissions
const navigationLinks = computed(() => {
  const links = []
  
  // Dashboard is always available
  links.push({ label: 'Dashboard', path: '/dashboard', icon: 'dashboard' })
  
  if (hasPermission('manage-branches')) {
    links.push({ label: 'Branches', path: '/branches', icon: 'store' })
  }
  
  if (hasPermission('manage-products')) {
    links.push({ label: 'Products', path: '/products', icon: 'inventory_2' })
  }
  
  if (hasPermission('manage-users')) {
    links.push({ label: 'Users', path: '/users', icon: 'group' })
  }
  
  if (hasPermission('manage-inventory')) {
    links.push({ label: 'Inventory', path: '/inventory', icon: 'warehouse' })
  }
  
  if (hasPermission('create-orders')) {
    links.push({ label: 'Orders', path: '/orders/create', icon: 'receipt_long' })
  }
  
  if (hasPermission('view-reports')) {
    links.push({ label: 'Reports', path: '/reports', icon: 'bar_chart' })
  }
  
  return links
})

// Initialize auth state
initializeAuth()

export const useAuth = () => {
  return {
    // State
    user: readonly(user),
    isAuthenticated: readonly(isAuthenticated),
    isLoading: readonly(isLoading),
    authError: readonly(authError),
    
    // Computed
    userRole,
    userInitials,
    roleLabel,
    roleBadgeClass,
    navigationLinks,
    
    // Methods
    login,
    logout,
    updateUser,
    clearAuth,
    hasPermission,
    canAccess,
    refreshAuth,
    
    // State management
    initializeAuth
  }
}