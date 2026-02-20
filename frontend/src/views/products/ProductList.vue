<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import productService from '@/services/productService'

const router = useRouter()
const products = ref([])
const search = ref('')
const statusFilter = ref('')
const loading = ref(false)

const fetchProducts = async () => {
  loading.value = true
  try {
    const params = {}
    if (search.value) params.search = search.value
    if (statusFilter.value) params.status = statusFilter.value

    const res = await productService.getAll(params)
    products.value = res.data.data
  } catch (error) {
    console.error(error)
  }
  loading.value = false
}

const deleteProduct = async (id) => {
  if (!confirm('Are you sure you want to delete this product?')) return
  await productService.delete(id)
  fetchProducts()
}

// Debounced search
let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchProducts, 300)
})

// Instant filter on status change
watch(statusFilter, fetchProducts)

onMounted(fetchProducts)
</script>

<template>
  <div class="product-page">

    <!-- Page Header -->
    <div class="page-header">
      <h1>
        <span class="material-symbols-outlined page-icon">inventory_2</span>
        Product Management
      </h1>
      <p class="page-sub">Manage your product catalog</p>
    </div>

    <!-- Toolbar: Search + Filter + Create -->
    <div class="toolbar">
      <div class="toolbar-filters">
        <!-- Search -->
        <div class="search-box">
          <span class="material-symbols-outlined search-icon">search</span>
          <input
            v-model="search"
            placeholder="Search by name or SKU..."
            class="search-input"
          />
        </div>

        <!-- Status Filter -->
        <select v-model="statusFilter" class="filter-select">
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>

      <button @click="router.push('/products/create')" class="btn-create">
        <span class="material-symbols-outlined">add</span>
        Add Product
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <span class="material-symbols-outlined spin">progress_activity</span>
      Loading products...
    </div>

    <!-- Empty State -->
    <div v-else-if="products.length === 0" class="empty-state">
      <span class="material-symbols-outlined empty-icon">category</span>
      <p>No products found</p>
    </div>

    <!-- Product Table -->
    <div v-else class="table-section">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>SKU</th>
              <th>Cost Price</th>
              <th>Sale Price</th>
              <th>Tax %</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products" :key="product.id">
              <td class="product-name">{{ product.name }}</td>
              <td class="sku-cell">{{ product.sku }}</td>
              <td>{{ parseFloat(product.cost_price).toFixed(2) }}</td>
              <td>{{ parseFloat(product.sale_price).toFixed(2) }}</td>
              <td>{{ parseFloat(product.tax_percentage).toFixed(2) }}%</td>
              <td>
                <span class="status-badge" :class="{
                  'status-active': product.status === 'active',
                  'status-inactive': product.status === 'inactive'
                }">
                  {{ product.status === 'active' ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="actions-cell">
                <button
                  @click="router.push(`/products/${product.id}/edit`)"
                  class="action-btn edit-btn"
                  title="Edit"
                >
                  <span class="material-symbols-outlined">edit</span>
                </button>
                <button
                  @click="deleteProduct(product.id)"
                  class="action-btn delete-btn"
                  title="Delete"
                >
                  <span class="material-symbols-outlined">delete</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-page {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ─── Page Header ─── */
.page-header {
  margin-bottom: 1.5rem;
}

.page-header h1 {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 1.6rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 0.3rem;
}

.page-icon {
  font-size: 28px;
  color: #818cf8;
}

.page-sub {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0;
}

/* ─── Toolbar ─── */
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.toolbar-filters {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.8rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  flex: 1;
  max-width: 320px;
  transition: border-color 0.2s;
}

.search-box:focus-within {
  border-color: rgba(99, 102, 241, 0.4);
}

.search-icon {
  font-size: 20px;
  color: #64748b;
  flex-shrink: 0;
}

.search-input {
  background: transparent;
  border: none;
  outline: none;
  color: #e2e8f0;
  font-size: 0.88rem;
  width: 100%;
}

.search-input::placeholder {
  color: #475569;
}

.filter-select {
  padding: 0.5rem 0.8rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  color: #e2e8f0;
  font-size: 0.88rem;
  cursor: pointer;
  transition: border-color 0.2s;
  min-width: 140px;
}

.filter-select:focus {
  outline: none;
  border-color: rgba(99, 102, 241, 0.4);
}

.filter-select option {
  background: #1e1e2e;
  color: #e2e8f0;
}

.btn-create {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.55rem 1.1rem;
  background: #646cff;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
  white-space: nowrap;
}

.btn-create:hover {
  background: #535bf2;
  transform: translateY(-1px);
}

.btn-create .material-symbols-outlined {
  font-size: 18px;
}

/* ─── Loading / Empty state ─── */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 3rem 1rem;
  color: #64748b;
  font-size: 0.9rem;
}

.empty-icon {
  font-size: 40px;
  color: #334155;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
  font-size: 22px;
}

/* ─── Table Section ─── */
.table-section {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 14px;
  padding: 0.25rem;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  padding: 0.65rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

td {
  padding: 0.75rem 1rem;
  font-size: 0.88rem;
  color: #cbd5e1;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

tr:hover td {
  background: rgba(255, 255, 255, 0.02);
}

.product-name {
  font-weight: 500;
  color: #e2e8f0;
}

.sku-cell {
  font-family: 'Courier New', monospace;
  font-size: 0.82rem;
  color: #94a3b8;
}

/* ─── Status Badges ─── */
.status-badge {
  display: inline-block;
  padding: 0.2rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.status-active {
  background: rgba(16, 185, 129, 0.1);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.status-inactive {
  background: rgba(239, 68, 68, 0.1);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

/* ─── Action Buttons ─── */
.actions-cell {
  display: flex;
  gap: 0.4rem;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.35rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 6px;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn .material-symbols-outlined {
  font-size: 18px;
}

.edit-btn {
  color: #60a5fa;
}

.edit-btn:hover {
  background: rgba(59, 130, 246, 0.1);
  border-color: rgba(59, 130, 246, 0.3);
}

.delete-btn {
  color: #f87171;
}

.delete-btn:hover {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.3);
}

/* ─── Responsive ─── */
@media (max-width: 640px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-filters {
    flex-direction: column;
  }

  .search-box {
    max-width: 100%;
  }

  .page-header h1 {
    font-size: 1.3rem;
  }
}
</style>