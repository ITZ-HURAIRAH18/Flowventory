<script setup>
import { ref, computed, onMounted } from 'vue'
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

/* ── fetch ── */
const fetchProducts = async () => {
  loading.value = true
  error.value   = ''
  try {
    const res = await productService.getAll()
    products.value = res.data.data ?? res.data
  } catch {
    error.value = 'Failed to load products. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchProducts)

/* ── filtered list ── */
const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return products.value.filter(p =>
    p.name.toLowerCase().includes(q) ||
    (p.sku || '').toLowerCase().includes(q)
  )
})

/* ── stats ── */
const totalProducts  = computed(() => products.value.length)
const activeCount    = computed(() => products.value.filter(p => p.status === 'active').length)
const inactiveCount  = computed(() => totalProducts.value - activeCount.value)

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
    products.value = products.value.filter(p => p.id !== id)
    toast.success('Product Deleted', `${product?.name || 'Product'} has been deleted successfully.`)
  } catch {
    toast.error('Delete Failed', 'Failed to delete product. Please try again.')
  } finally {
    deletingId.value = null
  }
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
        {{ filtered.length }} product{{ filtered.length !== 1 ? 's' : '' }} found
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
    <div v-else-if="filtered.length === 0" class="pl-empty">
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
        v-for="(product, i) in filtered"
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
            <div class="pc-status" :class="product.status">
              {{ product.status }}
            </div>
          </div>

          <!-- Price Row -->
          <div class="pc-financials">
            <div class="fin-item">
              <span class="fin-label">Cost</span>
              <span class="fin-val">PKR {{ formatPrice(product.cost_price) }}</span>
            </div>
            <div class="fin-divider"></div>
            <div class="fin-item highlight">
              <span class="fin-label">Sale</span>
              <span class="fin-val">PKR {{ formatPrice(product.sale_price) }}</span>
            </div>
            <div class="fin-divider"></div>
            <div class="fin-item">
              <span class="fin-label">Tax</span>
              <span class="fin-val">{{ formatPrice(product.tax_percentage) }}%</span>
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
/* ════════════════════════════════
   PAGE
════════════════════════════════ */
.pl-page {
  min-height: 100%;
  background: #FAF8F6;
  padding-bottom: 3rem;
  animation: pageIn 0.45s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pageIn {
  from { opacity:0; transform:translateY(10px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ════════════════════════════════
   PAGE HEADER
════════════════════════════════ */
.pl-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.pl-header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.pl-icon-wrap {
  width: 48px; height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #5D4037, #795548);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 16px rgba(93,64,55,0.25);
  flex-shrink: 0;
}
.pl-icon-wrap .material-symbols-outlined { font-size: 24px; color: #fff; }

.pl-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: #3E2723;
  margin: 0;
  letter-spacing: -0.03em;
}
.pl-subtitle {
  font-size: 0.82rem;
  color: #A1887F;
  margin: 0;
}

.btn-add {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.65rem 1.35rem;
  background: linear-gradient(135deg, #5D4037, #795548);
  color: #fff;
  border: none; border-radius: 12px;
  font-size: 0.88rem; font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  box-shadow: 0 4px 16px rgba(93,64,55,0.25);
  transition: all 0.2s ease;
}
.btn-add:hover { transform:translateY(-2px); box-shadow: 0 8px 24px rgba(93,64,55,0.3); }
.btn-add .material-symbols-outlined { font-size: 19px; }
.mt-3 { margin-top: 0.75rem; }

/* ════════════════════════════════
   STATS BAR
════════════════════════════════ */
.pl-stats {
  display: flex;
  align-items: center;
  gap: 0;
  background: #fff;
  border: 1px solid #EDE8E4;
  border-radius: 14px;
  padding: 1rem 1.5rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 2px 12px rgba(93,64,55,0.05);
}
.stat-chip {
  display: flex; align-items: center; gap: 0.5rem;
  flex: 1; justify-content: center;
}
.stat-ico { font-size: 20px; color: #8D6E63; }
.ico-green { color: #16a34a !important; }
.ico-amber { color: #b45309 !important; }
.stat-val  { font-size: 1.3rem; font-weight: 800; color: #3E2723; }
.stat-lbl  { font-size: 0.78rem; color: #A1887F; font-weight: 500; }
.stat-divider { width: 1px; height: 32px; background: #EDE8E4; margin: 0 0.5rem; }

/* ════════════════════════════════
   TOOLBAR
════════════════════════════════ */
.pl-toolbar {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.search-wrap {
  position: relative; flex: 1; min-width: 250px;
}
.search-ico {
  position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%);
  font-size: 19px; color: #A1887F; pointer-events: none;
}
.search-input {
  width: 100%;
  padding: 0.72rem 2.8rem 0.72rem 2.6rem;
  background: #fff;
  border: 1.5px solid #D7CCC8;
  border-radius: 12px;
  font-size: 0.88rem;
  color: #3E2723;
  font-family: inherit;
  transition: border-color 0.2s;
  outline: none;
}
.search-input:focus { border-color: #A1887F; box-shadow: 0 0 0 3px rgba(161, 136, 127, 0.1); }
.search-input::placeholder { color: #BCAAA4; }
.search-clear {
  position: absolute; right: 0.75rem; top: 50%; transform:translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: #BCAAA4; display: flex; padding: 2px;
}
.search-clear:hover { color: #5D4037; }
.search-clear .material-symbols-outlined { font-size: 18px; }
.result-count {
  font-size: 0.8rem; color: #A1887F; font-weight: 500; white-space: nowrap;
}

/* ════════════════════════════════
   STATES
════════════════════════════════ */
.pl-error {
  display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
  padding: 1rem 1.25rem;
  background: #FFF5F5; border: 1px solid #fca5a5;
  border-radius: 12px; color: #dc2626; font-size: 0.9rem;
}
.pl-error .material-symbols-outlined { font-size: 20px; }
.retry-btn {
  margin-left: auto; padding: 0.35rem 1rem;
  background: #dc2626; color: #fff; border: none;
  border-radius: 8px; cursor: pointer; font-size: 0.82rem;
  font-family: inherit; font-weight: 600;
}

.pl-empty {
  display: flex; flex-direction: column; align-items: center;
  padding: 5rem 1rem; text-align: center;
}
.pl-empty-icon { font-size: 56px; color: #D7CCC8; margin-bottom: 1rem; }
.pl-empty-title { font-size: 1.1rem; font-weight: 700; color: #5D4037; margin: 0 0 0.35rem; }
.pl-empty-sub   { font-size: 0.85rem; color: #A1887F; margin: 0; }

/* ════════════════════════════════
   CARDS GRID
════════════════════════════════ */
.pl-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

.product-card {
  background: #fff;
  border: 1.5px solid #E0D7D0;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(93,64,55,0.06);
  transition: all 0.25s ease;
  animation: cardIn 0.45s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes cardIn {
  from { opacity:0; transform:translateY(14px); }
  to   { opacity:1; transform:translateY(0); }
}
.product-card:hover {
  box-shadow: 0 12px 36px rgba(93,64,55,0.15);
  transform: translateY(-4px);
  border-color: #A1887F;
}

.pc-strip {
  height: 5px;
  background: linear-gradient(90deg, #5D4037, #A1887F, #D7CCC8);
}
.pc-strip.inactive-strip {
  background: #D1D5DB;
}

.pc-body { padding: 1.4rem 1.5rem 1.25rem; }

/* top row */
.pc-top {
  display: flex; align-items: center; gap: 1rem;
  margin-bottom: 1rem;
}
.pc-avatar {
  width: 42px; height: 42px; border-radius: 12px;
  background: linear-gradient(135deg, #5D4037, #8D6E63);
  color: #fff; font-size: 0.85rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.pc-avatar.inactive-avatar {
  background: #9CA3AF;
}
.pc-info { flex: 1; min-width: 0; }
.pc-name {
  font-size: 1.05rem; font-weight: 800; color: #3E2723;
  margin: 0 0 0.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.pc-sku {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.78rem; color: #A1887F; margin: 0;
  font-family: 'Courier New', monospace; font-weight: 600;
}
.pc-sku .material-symbols-outlined { font-size: 14px; }

.pc-status {
  font-size: 0.62rem; font-weight: 800; text-transform: uppercase;
  padding: 0.22rem 0.55rem; border-radius: 6px;
  letter-spacing: 0.05em; flex-shrink: 0;
}
.pc-status.active { background: #DCFCE7; color: #166534; }
.pc-status.inactive { background: #F3F4F6; color: #4B5563; }

/* financials */
.pc-financials {
  display: flex; align-items: center;
  background: #FAF8F6;
  border-radius: 12px;
  padding: 0.75rem;
  margin-bottom: 1.25rem;
}
.fin-item { flex: 1; display: flex; flex-direction: column; align-items: center; }
.fin-label { font-size: 0.65rem; color: #A1887F; font-weight: 600; text-transform: uppercase; margin-bottom: 0.15rem; }
.fin-val { font-size: 0.9rem; font-weight: 700; color: #5D4037; }
.fin-item.highlight .fin-val { color: #8D6E63; }
.fin-divider { width: 1px; height: 24px; background: #EDE8E4; }

/* action row */
.pc-actions {
  display: flex; gap: 0.5rem; align-items: center;
}
.pc-btn {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.5rem 0.9rem;
  border: none; border-radius: 9px;
  font-size: 0.8rem; font-weight: 600; cursor: pointer;
  font-family: inherit; transition: all 0.18s ease;
}
.pc-btn .material-symbols-outlined { font-size: 16px; }

.pc-btn-edit {
  background: rgba(93,64,55,0.08); color: #5D4037;
  flex: 1; justify-content: center;
}
.pc-btn-edit:hover { background: rgba(93,64,55,0.14); }

.pc-btn-del {
  background: rgba(239,68,68,0.08); color: #dc2626;
  padding: 0.5rem;
}
.pc-btn-del:hover:not(:disabled) { background: rgba(239,68,68,0.14); }
.pc-btn-del:disabled { opacity: 0.5; cursor: not-allowed; }

/* ════════════════════════════════
   MODAL
════════════════════════════════ */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 2000;
  background: rgba(30,15,10,0.45);
  backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
}
.modal-box {
  width: 100%; max-width: 520px;
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(0,0,0,0.3);
  animation: modalBounce 0.35s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes modalBounce {
  from { opacity:0; transform:scale(0.93) translateY(20px); }
  to   { opacity:1; transform:scale(1)    translateY(0); }
}

.modal-fade-enter-active,
.modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from,
.modal-fade-leave-to     { opacity: 0; }

/* ════════════════════════════════
   RESPONSIVE
════════════════════════════════ */
@media (max-width: 640px) {
  .pl-stats { flex-wrap: wrap; gap: 0.75rem;}
  .stat-divider { display: none; }
  .pl-grid { grid-template-columns: 1fr; }
}
</style>