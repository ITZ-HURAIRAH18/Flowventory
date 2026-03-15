import { ref, readonly } from 'vue'
import api from '@/services/api'
import productService from '@/services/productService'

// ============================================
// Global app data cache
// ============================================
const branches = ref([])
const products = ref([])
const isLoadingData = ref(false)
const dataError = ref(null)

// Track when data was last fetched
const lastFetchTime = ref({
  branches: 0,
  products: 0
})

// Cache duration in milliseconds (5 minutes)
const CACHE_DURATION = 5 * 60 * 1000

/**
 * Check if cache is still valid
 */
const isCacheValid = (key) => {
  const lastFetch = lastFetchTime.value[key] || 0
  return Date.now() - lastFetch < CACHE_DURATION
}

/**
 * Fetch branches (with caching)
 */
const fetchBranches = async (forceRefresh = false) => {
  if (!forceRefresh && branches.value.length > 0 && isCacheValid('branches')) {
    return branches.value // Return cached data
  }

  isLoadingData.value = true
  dataError.value = null

  try {
    const res = await api.get('/all-branches')
    branches.value = res.data.data || res.data
    lastFetchTime.value.branches = Date.now()
    return branches.value
  } catch (error) {
    dataError.value = 'Failed to load branches'
    console.error('Error fetching branches:', error)
    return []
  } finally {
    isLoadingData.value = false
  }
}

/**
 * Fetch products (with caching)
 */
const fetchProducts = async (forceRefresh = false, search = null, status = null) => {
  // Skip cache if search or status filters are applied
  if (!search && !status && !forceRefresh && products.value.length > 0 && isCacheValid('products')) {
    return products.value // Return cached data
  }

  isLoadingData.value = true
  dataError.value = null

  try {
    const params = {}
    if (search) params.search = search
    if (status) params.status = status
    
    const res = await productService.getAll(params)
    const data = res.data.data || res.data
    
    // Only cache if no filters applied
    if (!search && !status) {
      products.value = data
      lastFetchTime.value.products = Date.now()
    }
    
    return data
  } catch (error) {
    dataError.value = 'Failed to load products'
    console.error('Error fetching products:', error)
    return []
  } finally {
    isLoadingData.value = false
  }
}

/**
 * Fetch both branches and products in parallel (initial page load)
 */
const fetchAllAppData = async () => {
  isLoadingData.value = true
  dataError.value = null

  try {
    const [branchesData, productsData] = await Promise.all([
      fetchBranches(),
      fetchProducts()
    ])
    return { branches: branchesData, products: productsData }
  } catch (error) {
    dataError.value = 'Failed to sync application data'
    console.error('Error fetching app data:', error)
    return { branches: [], products: [] }
  } finally {
    isLoadingData.value = false
  }
}

/**
 * Clear all cached data (use on logout)
 */
const clearCache = () => {
  branches.value = []
  products.value = []
  lastFetchTime.value = { branches: 0, products: 0 }
  dataError.value = null
}

/**
 * Invalidate cache (force refresh on next fetch)
 */
const invalidateCache = () => {
  lastFetchTime.value = { branches: 0, products: 0 }
}

export const useAppData = () => {
  return {
    // Data
    branches: readonly(branches),
    products: readonly(products),
    isLoadingData: readonly(isLoadingData),
    dataError: readonly(dataError),
    
    // Methods
    fetchBranches,
    fetchProducts,
    fetchAllAppData,
    clearCache,
    invalidateCache
  }
}
