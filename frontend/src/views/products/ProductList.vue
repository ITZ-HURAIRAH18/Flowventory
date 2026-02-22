<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import productService from '@/services/productService'
import { toast } from '@/composables/useToast'
import ProductForm from './ProductForm.vue'
import LoadingScreen from '@/components/LoadingScreen.vue'

const products    = ref([])
const loading     = ref(true)
const error       = ref('')
const search      = ref('')
const showModal   = ref(false)
const editTarget  = ref(null)   // null = create, object = edit
const deletingId  = ref(null)

const currentPage = ref(1)
const totalPages  = ref(1)
const totalItems  = ref(0)

/* ── fetch ── */
const fetchProducts = async () => {
  loading.value = true
  error.value   = ''
  try {
    const params = { page: currentPage.value, per_page: 8, search: search.value }
    const res = await productService.getAll(params)
    products.value = res.data.data ?? res.data
    totalPages.value = res.data.last_page || 1
    totalItems.value = res.data.total || 0
  } catch {
    error.value = 'Failed to load products. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchProducts)

/* ── search ── */
let searchTimeout = null
watch(search, () => {
  currentPage.value = 1
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(fetchProducts, 300)
})


/* ── stats ── */
const totalProducts  = computed(() => totalItems.value)
const activeCount    = computed(() => products.value.filter(p => p.status === 'active').length) // Inaccurate with pagination
const inactiveCount  = computed(() => totalProducts.value - activeCount.value) // Inaccurate with pagination

/* ── actions ── */
const openCreate = () => { editTarget.value = null; showModal.value = true }
const openEdit   = (p) => { editTarget.value = p;    showModal.value = true }
const onSaved    = () => { 
  // Don't refresh immediately - let the toast be visible
  setTimeout(() => {
    showModal.value = false; 
    fetchProducts();
  }, 100)
}
const onClose    = () => { showModal.value = false }

const deleteProduct = async (id) => {
  if (!confirm('Delete this product? This will remove it from the catalog.')) return
  deletingId.value = id
  const product = products.value.find(p => p.id === id)
  try {
    await productService.delete(id)
    toast.success('Product Deleted', `${product?.name || 'Product'} has been deleted successfully.`)
    // Refetch after delete
    fetchProducts()
  } catch {
    toast.error('Delete Failed', 'Failed to delete product. Please try again.')
  } finally {
    deletingId.value = null
  }
}

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchProducts()
}

/* ── helpers ── */
const formatPrice = (val) => parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const initials = (name) => (name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
</script>

<template>
  <div class="pl-page">

    <!-- ══════ PAGE HEADER ══════ -->
    <div class="pl-header">
      <div class="pl-header-left">
        <div class="pl-icon-wrap">
          <span class="material-symbols-outlined">inventory_2</span>
        </div>
        <div>
          <h1 class="pl-title">Product Catalog</h1>
          <p class="pl-subtitle">Manage your inventory products and specifications</p>
        </div>
      </div>
      <button class="btn-add" @click="openCreate" id="add-product-btn">
        <span class="material-symbols-outlined">add</span>
        Add Product
      </button>
    </div>

    <!-- ══════ STATS BAR ══════ -->
    <div class="pl-stats">
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico">category</span>
        <span class="stat-val">{{ totalProducts }}</span>
        <span class="stat-lbl">Total</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico ico-green">check_circle</span>
        <span class="stat-val">{{ activeCount }}</span>
        <span class="stat-lbl">Active</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-chip">
        <span class="material-symbols-outlined stat-ico ico-amber">pause_circle</span>
        <span class="stat-val">{{ inactiveCount }}</span>
        <span class="stat-lbl">Inactive</span>
      </div>
    </div>

    <!-- ══════ SEARCH BAR ══════ -->
    <div class="pl-toolbar">
      <div class="search-wrap">
        <span class="material-symbols-outlined search-ico">search</span>
        <input
          v-model="search"
          class="search-input"
          placeholder="Search products by name or SKU…"
          id="product-search"
        />
        <button v-if="search" class="search-clear" @click="search = ''">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <span class="result-count" v-if="!loading">
        {{ totalItems }} product{{ totalItems !== 1 ? 's' : '' }} found
      </span>
    </div>

    <!-- ══════ FULL-PAGE LOADING ══════ -->
    <LoadingScreen
      v-if="loading"
      :show="loading"
      :duration="0"
      message="Accessing catalog…"
    />

    <!-- ══════ ERROR ══════ -->
    <div v-else-if="error" class="pl-error">
      <span class="material-symbols-outlined">error</span>
      {{ error }}
      <button @click="fetchProducts" class="retry-btn">Retry</button>
    </div>

    <!-- ══════ EMPTY ══════ -->
    <div v-else-if="products.length === 0" class="pl-empty">
      <span class="material-symbols-outlined pl-empty-icon">inventory</span>
      <p class="pl-empty-title">{{ search ? 'No results found' : 'No products cataloged' }}</p>
      <p class="pl-empty-sub">{{ search ? 'Try checking your spelling or use different terms.' : 'Start building your catalog by adding products.' }}</p>
      <button v-if="!search" class="btn-add mt-3" @click="openCreate">
        <span class="material-symbols-outlined">add</span>
        Add Your First Product
      </button>
    </div>

    <!-- ══════ PRODUCT CARDS ══════ -->
    <div v-else class="pl-grid">
      <div
        v-for="(product, i) in products"
        :key="product.id"
        class="product-card"
        :style="{ animationDelay: `${i * 0.05}s` }"
      >
        <!-- Card header strip -->
        <div class="pc-strip" :class="{ 'inactive-strip': product.status !== 'active' }"></div>

        <div class="pc-body">
          <!-- Top row — Avatar + Name + SKU -->
          <div class="pc-top">
            <div class="pc-avatar" :class="{ 'inactive-avatar': product.status !== 'active' }">
              {{ initials(product.name) }}
            </div>
            <div class="pc-info">
              <h3 class="pc-name">{{ product.name }}</h3>
              <p class="pc-sku">
                <span class="material-symbols-outlined">barcode</span>
                {{ product.sku || '---' }}
              </p>
            </div>
          </div>

          <!-- Price & Status -->
          <div class="pc-details">
            <div class="pc-price">
              <span class="pc-price-label">Price</span>
              <span class="pc-price-value">PKR {{ formatPrice(product.sale_price) }}</span>
            </div>
            <div class="pc-status" :class="product.status">
              {{ product.status }}
            </div>
          </div>


          <!-- Action buttons -->
          <div class="pc-actions">
            <button class="pc-btn pc-btn-edit" @click="openEdit(product)">
              <span class="material-symbols-outlined">edit</span>
              Edit
            </button>
            <button
              class="pc-btn pc-btn-del"
              @click="deleteProduct(product.id)"
              :disabled="deletingId === product.id"
            >
              <span class="material-symbols-outlined">
                {{ deletingId === product.id ? 'hourglass_empty' : 'delete' }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════ PAGINATION ══════ -->
    <div v-if="totalPages > 1" class="pagination">
      <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1" class="page-btn">
        <span class="material-symbols-outlined">chevron_left</span>
      </button>
      <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
      <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages" class="page-btn">
        <span class="material-symbols-outlined">chevron_right</span>
      </button>
    </div>

    <!-- ══════ MODAL ══════ -->
    <transition name="modal-fade">
      <div v-if="showModal" class="modal-backdrop" @click.self="onClose">
        <div class="modal-box">
          <ProductForm
            :productObject="editTarget"
            @saved="onSaved"
            @close="onClose"
          />
        </div>
      </div>
    </transition>

  </div>
</template>

<style scoped>
@import "../../styles/views/ProductList.css";
</style>