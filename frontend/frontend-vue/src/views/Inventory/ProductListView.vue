<template>
  <Layout>
    <div class="prod-list-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Inventory</p>
          <h1 class="page-title">Product List</h1>
        </div>
        <div class="header-actions">
          <div class="select-wrap">
            <i class="fa-solid fa-layer-group input-icon"></i>
            <select v-model="selectedCategory" @change="handleCategoryChange" class="form-select">
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat.CategoryID" :value="String(cat.CategoryID)">
                {{ cat.CategoryName }}
              </option>
            </select>
            <i class="fa-solid fa-chevron-down select-caret"></i>
          </div>
          <RouterLink to="/products/create" class="btn btn--primary">
            <i class="fa-solid fa-plus"></i> Add Product
          </RouterLink>
        </div>
      </div>

      <!-- ── ALERT ── -->
      <transition name="alert-fade">
        <div v-if="successMessage" class="alert alert--success">
          <i class="fa-solid fa-circle-check"></i>
          <span>{{ successMessage }}</span>
          <button class="alert-close" @click="successMessage = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>

      <!-- ── TABLE CARD ── -->
      <div class="table-card">
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width:32%">Product</th>
                <th style="width:14%">Category</th>
                <th style="width:13%">SKU</th>
                <th style="width:12%">Price</th>
                <th style="width:10%">Stock</th>
                <th style="width:19%; text-align:right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in filteredProducts" :key="product.ProductID">

                <!-- Product name + avatar -->
                <td>
                  <div class="cell-product">
                    <div class="product-avatar">
                      <img v-if="product.Product_Image" :src="imagePath(product.Product_Image)" class="product-avatar-img" />
                      <span v-else class="product-avatar-initials">{{ product.ProductName.substring(0,2).toUpperCase() }}</span>
                    </div>
                    <div class="product-info">
                      <RouterLink :to="`/products/${product.ProductID}`" class="product-name-link">
                        {{ product.ProductName }}
                      </RouterLink>
                      <span class="product-sku">{{ product.SKU }}</span>
                    </div>
                  </div>
                </td>

                <td class="cell-muted">{{ product.category?.CategoryName || '—' }}</td>
                <td class="cell-mono cell-muted">{{ product.SKU }}</td>
                <td class="cell-price">₱{{ formatPrice(product.SellingPrice) }}</td>

                <!-- Stock -->
                <td>
                  <span class="stock-badge" :class="stockClass(product)">
                    {{ product.inventory?.QuantityOnHand ?? 0 }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="td-actions">
                  <div class="action-row">
                    <button class="restock-btn" @click="openRestockModal(product)">
                      <i class="fa-solid fa-boxes-stacked"></i> Restock
                    </button>
                    <button
                      :ref="setMenuButtonRef(product.ProductID)"
                      @click="toggleMenu(product.ProductID)"
                      class="menu-btn"
                      :class="{ 'menu-btn--active': openMenuId === product.ProductID }"
                    >
                      <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="filteredProducts.length === 0">
                <td colspan="6" class="td-empty">
                  <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <p>No products found</p>
                    <RouterLink to="/products/create" class="btn btn--primary btn--sm">
                      <i class="fa-solid fa-plus"></i> Add Product
                    </RouterLink>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- ── DROPDOWN ── -->
    <Teleport to="body">
      <div
        v-if="openMenuId && currentProduct"
        ref="dropdownRef"
        :style="dropdownStyle"
        class="prod-dropdown"
      >
        <RouterLink :to="`/products/${currentProduct.ProductID}`" class="prod-dropdown-item" @click="closeMenu">
          <span class="prod-dropdown-icon prod-dropdown-icon--blue"><i class="fa-solid fa-eye"></i></span>
          View Details
        </RouterLink>
        <RouterLink :to="`/products/${currentProduct.ProductID}/edit`" class="prod-dropdown-item" @click="closeMenu">
          <span class="prod-dropdown-icon prod-dropdown-icon--amber"><i class="fa-solid fa-pen-to-square"></i></span>
          Edit Product
        </RouterLink>
        <div class="prod-dropdown-divider"></div>
        <button class="prod-dropdown-item prod-dropdown-item--danger" @click="promptDelete(currentProduct)">
          <span class="prod-dropdown-icon prod-dropdown-icon--red"><i class="fa-solid fa-trash"></i></span>
          Delete
        </button>
      </div>
    </Teleport>

    <!-- ── RESTOCK MODAL ── -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="modalVisible" class="modal-backdrop" @click.self="closeRestockModal">
          <div class="restock-modal">
            <div class="restock-modal-header">
              <div class="restock-modal-icon">
                <i class="fa-solid fa-boxes-stacked"></i>
              </div>
              <div>
                <p class="restock-modal-eyebrow">Inventory</p>
                <h3 class="restock-modal-title">Restock Product</h3>
              </div>
              <button class="modal-close-btn" @click="closeRestockModal"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="restock-modal-body">
              <div class="restock-product-chip">
                <div class="restock-chip-avatar">
                  <img v-if="modalProduct?.Product_Image" :src="imagePath(modalProduct.Product_Image)" class="restock-chip-img" />
                  <span v-else>{{ modalProduct?.ProductName?.substring(0,2).toUpperCase() }}</span>
                </div>
                <div>
                  <p class="restock-chip-name">{{ modalProduct?.ProductName }}</p>
                  <p class="restock-chip-stock">Current stock: <strong>{{ modalProduct?.inventory?.QuantityOnHand ?? 0 }}</strong></p>
                </div>
              </div>

              <form @submit.prevent="submitRestock">
                <div class="restock-field">
                  <label class="restock-label">Quantity to Add <span class="req">*</span></label>
                  <div class="qty-row">
                    <button type="button" class="qty-btn" @click="restockQty = Math.max(1, restockQty - 1)"><i class="fa-solid fa-minus"></i></button>
                    <input v-model.number="restockQty" type="number" min="1" required class="qty-input" />
                    <button type="button" class="qty-btn" @click="restockQty++"><i class="fa-solid fa-plus"></i></button>
                  </div>
                  <p class="restock-hint">
                    New stock after restock:
                    <strong>{{ (modalProduct?.inventory?.QuantityOnHand ?? 0) + (restockQty || 0) }}</strong>
                  </p>
                </div>

                <div class="restock-footer">
                  <button type="button" class="btn btn--ghost" @click="closeRestockModal">Cancel</button>
                  <button type="submit" class="btn btn--green" :disabled="isRestocking">
                    <i v-if="isRestocking" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-boxes-stacked"></i>
                    {{ isRestocking ? 'Restocking…' : 'Confirm Restock' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- ── DELETE MODAL ── -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="showDeleteModal" class="modal-backdrop" @click.self="cancelDelete">
          <div class="del-modal">
            <div class="del-modal-icon-ring">
              <div class="del-modal-icon"><i class="fa-solid fa-trash-can"></i></div>
            </div>
            <div class="del-modal-body">
              <h3 class="del-modal-title">Delete Product</h3>
              <p class="del-modal-desc">
                You are about to permanently delete
                <strong class="del-modal-name">{{ productToDelete?.ProductName }}</strong>.
                This cannot be undone.
              </p>
              <div class="del-modal-chip">
                <div class="del-modal-chip-avatar">
                  {{ productToDelete?.ProductName?.substring(0,2).toUpperCase() }}
                </div>
                <div class="del-modal-chip-info">
                  <p class="del-modal-chip-name">{{ productToDelete?.ProductName }}</p>
                  <p class="del-modal-chip-meta">SKU: {{ productToDelete?.SKU }}</p>
                </div>
              </div>
            </div>
            <div class="del-modal-footer">
              <button class="del-modal-btn del-modal-btn--cancel" @click="cancelDelete" :disabled="deleting">Cancel</button>
              <button class="del-modal-btn del-modal-btn--confirm" @click="confirmDelete" :disabled="deleting">
                <i v-if="deleting" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-trash-can"></i>
                {{ deleting ? 'Deleting…' : 'Yes, Delete' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </Layout>
</template>

<script setup>
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'
import { ref, onMounted, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route  = useRoute()
const router = useRouter()

const products        = ref([])
const categories      = ref([])
const selectedCategory = ref('')
const successMessage  = ref('')
const searchQuery     = ref('')

/* restock */
const modalVisible  = ref(false)
const modalProduct  = ref(null)
const restockQty    = ref(1)
const isRestocking  = ref(false)

/* dropdown */
const openMenuId     = ref(null)
const dropdownRef    = ref(null)
const dropdownStyle  = ref({ position:'fixed', top:'0px', left:'0px' })
const menuButtonRefs = new Map()

/* delete modal */
const showDeleteModal  = ref(false)
const productToDelete  = ref(null)
const deleting         = ref(false)

const setMenuButtonRef = id => el => { if (el) menuButtonRefs.set(id, el); else menuButtonRefs.delete(id) }
const imagePath   = path => `http://127.0.0.1:8000/storage/${path}`
const formatPrice = v => Number(v||0).toLocaleString(undefined, { minimumFractionDigits:2 })
const stockClass  = p => {
  const qty = p.inventory?.QuantityOnHand ?? 0
  if (qty <= 0)  return 'stock-badge--oos'
  if (qty < 5)   return 'stock-badge--low'
  return 'stock-badge--ok'
}

async function loadProducts()   { const r = await api.get('/api/product-list'); products.value = r.data }
async function loadCategories() { const r = await api.get('/api/categories');   categories.value = r.data.categories }

onMounted(() => {
  loadProducts(); loadCategories()
  if (route.query.category) selectedCategory.value = String(route.query.category)
})

watch(() => route.query.search, v => { searchQuery.value = (v || '').toLowerCase() }, { immediate: true })
watch(selectedCategory, nc => { router.replace({ query: { ...route.query, category: nc || undefined } }) })

const currentProduct = computed(() => filteredProducts.value.find(p => p.ProductID === openMenuId.value))
const filteredProducts = computed(() =>
  products.value.filter(p => {
    const ms = !searchQuery.value || [p.ProductName, p.SKU, p.category?.CategoryName].join(' ').toLowerCase().includes(searchQuery.value)
    const mc = !selectedCategory.value || String(p.CategoryID) === selectedCategory.value
    return ms && mc
  })
)

function toggleMenu(id) {
  if (openMenuId.value === id) { closeMenu(); return }
  openMenuId.value = id
  nextTick(() => {
    const btn = menuButtonRefs.get(id); if (!btn) return
    const rect = btn.getBoundingClientRect()
    dropdownStyle.value = { position:'fixed', top:`${rect.bottom+8}px`, left:`${rect.right-176}px` }
  })
}
function closeMenu() { openMenuId.value = null }
function handleCategoryChange() { closeMenu() }

function onClickOutside(e) {
  if (!openMenuId.value) return
  const dd = dropdownRef.value; const btn = menuButtonRefs.get(openMenuId.value)
  if (dd && !dd.contains(e.target) && btn && !btn.contains(e.target)) closeMenu()
}
function onKeydown(e) { if (e.key === 'Escape') { closeMenu(); closeRestockModal(); cancelDelete() } }
document.addEventListener('click', onClickOutside)
document.addEventListener('keydown', onKeydown)
onBeforeUnmount(() => { document.removeEventListener('click', onClickOutside); document.removeEventListener('keydown', onKeydown) })

function openRestockModal(product) { modalProduct.value = product; restockQty.value = 1; modalVisible.value = true }
function closeRestockModal() { modalVisible.value = false; modalProduct.value = null; restockQty.value = 1 }

async function submitRestock() {
  if (!modalProduct.value || !restockQty.value || restockQty.value < 1 || isRestocking.value) return
  isRestocking.value = true
  try {
    await api.post(`/api/products/${modalProduct.value.ProductID}/restock`, { quantity: Number(restockQty.value) }, { withCredentials: true })
    successMessage.value = 'Product restocked successfully!'
    closeRestockModal(); await loadProducts()
    setTimeout(() => successMessage.value = '', 2500)
  } catch (err) { alert(err?.response?.data?.message || 'Failed to restock product') }
  finally { isRestocking.value = false }
}

function promptDelete(product) { closeMenu(); productToDelete.value = product; showDeleteModal.value = true }
function cancelDelete() { if (deleting.value) return; showDeleteModal.value = false; productToDelete.value = null }

async function confirmDelete() {
  if (!productToDelete.value || deleting.value) return
  deleting.value = true
  try {
    await api.delete(`/api/products/${productToDelete.value.ProductID}`, { withCredentials: true })
    successMessage.value = `"${productToDelete.value.ProductName}" deleted successfully.`
    showDeleteModal.value = false; productToDelete.value = null
    await loadProducts()
    setTimeout(() => successMessage.value = '', 2500)
  } catch (err) { alert(err?.response?.data?.message || `Delete failed (${err?.response?.status})`) }
  finally { deleting.value = false }
}
</script>

<style scoped>
.prod-list-page {
  --pl-bg:             var(--c-bg);
  --pl-surface:        var(--c-surface);
  --pl-surface-raised: var(--c-surface-raised);
  --pl-border:         var(--c-border);
  --pl-border-strong:  var(--c-border-strong);
  --pl-text-primary:   var(--c-text-primary);
  --pl-text-secondary: var(--c-text-secondary);
  --pl-text-muted:     var(--c-text-muted);
  --pl-text-faint:     var(--c-text-faint);
  --pl-accent:         var(--c-accent);
  --pl-accent-soft:    var(--c-accent-soft);
  --pl-accent-border:  var(--c-accent-border);
  --pl-ring:           var(--c-accent-ring);
  --pl-green:          #10b981;
  --pl-red:            #f43f5e;
  --pl-blue:           #3b82f6;
  --pl-amber:          #f59e0b;
  --radius: 16px;

  min-height: 100%;
  background: var(--pl-bg);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 20px;
  transition: background-color .22s;
}

/* HEADER */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--pl-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--pl-text-primary); letter-spacing: -.03em; margin: 0; transition: color .22s; }
.header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

/* CATEGORY SELECT */
.select-wrap { position: relative; display: flex; align-items: center; }
.input-icon  { position: absolute; left: 11px; font-size: 11px; color: var(--pl-text-faint); pointer-events: none; z-index: 1; }
.select-caret{ position: absolute; right: 9px; font-size: 9px; color: var(--pl-text-faint); pointer-events: none; }
.form-select {
  height: 40px; padding: 0 30px 0 30px;
  border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface); color: var(--pl-text-secondary);
  font-size: 13px; font-weight: 600; font-family: inherit;
  outline: none; appearance: none; -webkit-appearance: none; cursor: pointer;
  transition: border-color .18s, background-color .22s, color .22s;
}
.form-select:focus { border-color: var(--pl-accent); box-shadow: 0 0 0 3px var(--pl-ring); }

/* BUTTONS */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn--primary {
  background: linear-gradient(135deg, var(--pl-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--ghost { background: var(--pl-surface); border-color: var(--pl-border-strong); color: var(--pl-text-secondary); }
.btn--ghost:hover { background: var(--pl-surface-raised); border-color: var(--pl-accent-border); color: var(--pl-text-primary); }
.btn--green {
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff; border: none; box-shadow: 0 2px 8px rgba(16,185,129,.30);
}
.btn--green:hover { filter: brightness(1.08); transform: translateY(-1px); }
.btn--green:disabled { opacity: .6; cursor: not-allowed; filter: none; transform: none; }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }

/* ALERT */
.alert { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 12px; font-size: 13.5px; font-weight: 500; border: 1px solid transparent; }
.alert--success { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); color: var(--pl-green); }
html[data-theme="dark"] .alert--success { background: rgba(16,185,129,.14); color: #4ADE80; }
.alert-close { background: none; border: none; cursor: pointer; color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 2px; transition: opacity .15s; }
.alert-close:hover { opacity: 1; }
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* TABLE CARD */
.table-card { background: var(--pl-surface); border: 1px solid var(--pl-border); border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden; transition: background-color .22s, border-color .22s; }
.table-wrap  { overflow-x: auto; }

.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; table-layout: fixed; }
.data-table thead th {
  background: var(--pl-surface-raised); color: var(--pl-text-faint);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--pl-border);
  white-space: nowrap; transition: background-color .22s, color .22s, border-color .22s;
}
.data-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--pl-border); vertical-align: middle; transition: background-color .15s, border-color .22s; }
.data-table tbody tr:hover td { background: var(--pl-surface-raised); }
.data-table tbody tr:last-child td { border-bottom: none; }

/* PRODUCT CELL */
.cell-product { display: flex; align-items: center; gap: 12px; }
.product-avatar {
  width: 42px; height: 42px; border-radius: 10px; flex-shrink: 0;
  background: var(--pl-surface-raised); border: 1px solid var(--pl-border);
  display: flex; align-items: center; justify-content: center; overflow: hidden;
  transition: background-color .22s;
}
.product-avatar-img      { width: 100%; height: 100%; object-fit: cover; }
.product-avatar-initials { font-size: 13px; font-weight: 800; color: var(--pl-accent); }
.product-info     { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.product-name-link{ font-weight: 600; color: var(--pl-accent); text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 13.5px; transition: color .15s; }
.product-name-link:hover { text-decoration: underline; }
.product-sku      { font-size: 11px; color: var(--pl-text-faint); }
.cell-muted       { color: var(--pl-text-muted); transition: color .22s; }
.cell-mono        { font-variant-numeric: tabular-nums; font-size: 12.5px; }
.cell-price       { font-weight: 700; color: var(--pl-text-primary); font-variant-numeric: tabular-nums; transition: color .22s; }

/* STOCK BADGE */
.stock-badge {
  display: inline-flex; align-items: center;
  font-size: 12px; font-weight: 700; border-radius: 20px; padding: 3px 10px;
}
.stock-badge--ok  { background: rgba(16,185,129,.12); color: var(--pl-green); }
.stock-badge--low { background: rgba(245,158,11,.12);  color: var(--pl-amber); }
.stock-badge--oos { background: rgba(244,63,94,.12);   color: var(--pl-red);   }
html[data-theme="dark"] .stock-badge--ok  { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .stock-badge--low { background: rgba(245,158,11,.18); color: #FCD34D; }
html[data-theme="dark"] .stock-badge--oos { background: rgba(244,63,94,.18);  color: #F87171; }

/* ACTIONS */
.td-actions { text-align: right; }
.action-row { display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
.restock-btn {
  display: inline-flex; align-items: center; gap: 6px;
  height: 32px; padding: 0 12px;
  font-size: 12px; font-weight: 600; font-family: inherit;
  border: none; border-radius: 8px; cursor: pointer;
  background: rgba(16,185,129,.12); color: var(--pl-green);
  transition: background .15s, color .15s, transform .15s;
}
.restock-btn:hover { background: rgba(16,185,129,.22); transform: translateY(-1px); }
html[data-theme="dark"] .restock-btn { background: rgba(16,185,129,.18); }

.menu-btn {
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: none; color: var(--pl-text-faint); cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center; font-size: 14px;
  transition: background-color .15s, color .15s;
}
.menu-btn:hover, .menu-btn--active { background: var(--pl-surface-raised); color: var(--pl-text-primary); }

/* EMPTY */
.td-empty { padding: 56px 24px !important; border-bottom: none !important; text-align: center; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 12px; color: var(--pl-text-faint); }
.empty-state i { font-size: 36px; }
.empty-state p { font-size: 14px; font-weight: 600; color: var(--pl-text-muted); margin: 0; }

/* MODAL SHARED */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(0,0,0,.48); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.95) translateY(10px); }

/* RESTOCK MODAL */
.restock-modal {
  width: 100%; max-width: 420px;
  background: var(--pl-surface); border: 1px solid var(--pl-border);
  border-radius: 22px; box-shadow: var(--c-shadow-xl); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.restock-modal::before { content: ''; display: block; height: 4px; background: linear-gradient(90deg, var(--pl-green), #34d399); }

.restock-modal-header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px 14px; border-bottom: 1px solid var(--pl-border);
  transition: border-color .22s;
}
.restock-modal-icon {
  width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
  background: rgba(16,185,129,.12); color: var(--pl-green);
  display: flex; align-items: center; justify-content: center; font-size: 16px;
  border: 1px solid rgba(16,185,129,.25);
}
html[data-theme="dark"] .restock-modal-icon { background: rgba(16,185,129,.18); }
.restock-modal-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: .12em; color: var(--pl-green); text-transform: uppercase; margin: 0 0 2px; }
.restock-modal-title   { font-size: 15px; font-weight: 700; color: var(--pl-text-primary); margin: 0; transition: color .22s; }
.modal-close-btn {
  margin-left: auto; width: 30px; height: 30px; border: none; border-radius: 8px;
  background: var(--pl-surface-raised); color: var(--pl-text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 12px;
  transition: background .15s, color .15s;
}
.modal-close-btn:hover { background: var(--pl-border-strong); color: var(--pl-text-primary); }

.restock-modal-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }

.restock-product-chip {
  display: flex; align-items: center; gap: 12px;
  background: var(--pl-surface-raised); border: 1px solid var(--pl-border);
  border-radius: 12px; padding: 12px 14px;
  transition: background-color .22s, border-color .22s;
}
.restock-chip-avatar {
  width: 38px; height: 38px; border-radius: 9px; flex-shrink: 0;
  background: var(--pl-accent-soft); color: var(--pl-accent);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; overflow: hidden; border: 1px solid var(--pl-accent-border);
}
.restock-chip-img  { width: 100%; height: 100%; object-fit: cover; }
.restock-chip-name { font-size: 13.5px; font-weight: 700; color: var(--pl-text-primary); margin: 0 0 2px; transition: color .22s; }
.restock-chip-stock{ font-size: 12px; color: var(--pl-text-muted); margin: 0; }
.restock-chip-stock strong { color: var(--pl-green); }

.restock-field { display: flex; flex-direction: column; gap: 8px; }
.restock-label { font-size: 12.5px; font-weight: 600; color: var(--pl-text-secondary); transition: color .22s; }
.req { color: var(--pl-accent); }
.restock-hint  { font-size: 12px; color: var(--pl-text-faint); margin: 0; }
.restock-hint strong { color: var(--pl-green); }

.qty-row { display: flex; align-items: center; gap: 10px; }
.qty-btn {
  width: 40px; height: 40px; border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface); color: var(--pl-text-secondary); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;
  transition: border-color .15s, background-color .15s, color .15s;
}
.qty-btn:hover { border-color: var(--pl-accent-border); color: var(--pl-accent); background: var(--pl-accent-soft); }
.qty-input {
  flex: 1; height: 40px; text-align: center;
  border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface-raised); color: var(--pl-text-primary);
  font-size: 18px; font-weight: 700; font-family: inherit; outline: none;
  transition: border-color .18s, background-color .22s, color .22s;
}
.qty-input:focus { border-color: var(--pl-accent); box-shadow: 0 0 0 3px var(--pl-ring); }

.restock-footer { display: flex; gap: 10px; margin-top: 4px; }
.restock-footer .btn { flex: 1; justify-content: center; }

/* DELETE MODAL (same as Suppliers pattern) */
.del-modal {
  width: 100%; max-width: 400px;
  background: var(--pl-surface); border: 1px solid var(--pl-border);
  border-radius: 22px; box-shadow: var(--c-shadow-xl); overflow: hidden;
  display: flex; flex-direction: column;
  transition: background-color .22s, border-color .22s;
}
.del-modal::before { content: ''; display: block; height: 4px; background: linear-gradient(90deg, #f43f5e, #fb7185); }
.del-modal-icon-ring { display: flex; align-items: center; justify-content: center; padding: 28px 0 0; }
.del-modal-icon {
  width: 60px; height: 60px; border-radius: 16px;
  background: rgba(244,63,94,.10); border: 1.5px solid rgba(244,63,94,.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; color: #f43f5e; box-shadow: 0 0 0 8px rgba(244,63,94,.06);
}
html[data-theme="dark"] .del-modal-icon { background: rgba(244,63,94,.14); color: #F87171; box-shadow: 0 0 0 8px rgba(244,63,94,.08); }
.del-modal-body { padding: 18px 26px 22px; display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center; }
.del-modal-title { font-size: 18px; font-weight: 800; color: var(--pl-text-primary); letter-spacing: -.02em; margin: 0; transition: color .22s; }
.del-modal-desc  { font-size: 13px; line-height: 1.65; color: var(--pl-text-muted); margin: 0; }
.del-modal-name  { color: var(--pl-text-primary); font-weight: 700; }
.del-modal-chip  {
  display: flex; align-items: center; gap: 12px; width: 100%; margin-top: 6px;
  background: var(--pl-surface-raised); border: 1px solid var(--pl-border);
  border-radius: 12px; padding: 11px 14px; text-align: left;
  transition: background-color .22s, border-color .22s;
}
.del-modal-chip-avatar {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  background: rgba(244,63,94,.12); color: #f43f5e;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; border: 1px solid rgba(244,63,94,.22);
}
html[data-theme="dark"] .del-modal-chip-avatar { background: rgba(244,63,94,.18); color: #F87171; }
.del-modal-chip-info { flex: 1; min-width: 0; }
.del-modal-chip-name { font-size: 13px; font-weight: 700; color: var(--pl-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 2px; }
.del-modal-chip-meta { font-size: 11.5px; color: var(--pl-text-faint); margin: 0; }
.del-modal-footer { display: flex; gap: 10px; padding: 0 26px 26px; }
.del-modal-btn {
  flex: 1; height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 13.5px; font-weight: 600; font-family: 'Inter',system-ui,sans-serif;
  border-radius: 11px; cursor: pointer; transition: background-color .18s, border-color .18s, opacity .18s, transform .15s;
}
.del-modal-btn:active { transform: scale(.97); }
.del-modal-btn:disabled { opacity: .55; cursor: not-allowed; transform: none; }
.del-modal-btn--cancel { background: var(--pl-surface-raised); border: 1.5px solid var(--pl-border-strong); color: var(--pl-text-secondary); }
.del-modal-btn--cancel:hover:not(:disabled) { background: var(--pl-border); color: var(--pl-text-primary); }
.del-modal-btn--confirm { background: linear-gradient(135deg,#f43f5e,#e11d48); border: none; color: #fff; box-shadow: 0 2px 12px rgba(244,63,94,.35); }
.del-modal-btn--confirm:hover:not(:disabled) { filter: brightness(1.08); box-shadow: 0 4px 20px rgba(244,63,94,.50); transform: translateY(-1px); }

@media (max-width: 700px) {
  .prod-list-page { padding: 16px 16px 48px; }
  .header-actions { flex-direction: column; align-items: stretch; }
  .form-select    { width: 100%; }
}
</style>

<style>
/* TELEPORTED DROPDOWN — global */
.prod-dropdown {
  width: 176px;
  background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 13px;
  box-shadow: 0 8px 32px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06);
  z-index: 9999; padding: 5px; overflow: hidden;
}
html[data-theme="dark"] .prod-dropdown { background: #1E2130; border-color: #2A2D3E; box-shadow: 0 8px 40px rgba(0,0,0,.55); }

.prod-dropdown-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px; font-size: 13px; font-weight: 500;
  color: #374151; border-radius: 8px; text-decoration: none;
  background: none; border: none; width: 100%; cursor: pointer;
  font-family: 'Inter', system-ui, sans-serif; transition: background-color .15s, color .15s;
}
.prod-dropdown-item:hover { background: #F9FAFB; color: #111827; }
html[data-theme="dark"] .prod-dropdown-item       { color: #C4C8D6; }
html[data-theme="dark"] .prod-dropdown-item:hover { background: #222535; color: #E8EAF0; }
.prod-dropdown-item--danger       { color: #EF4444 !important; }
.prod-dropdown-item--danger:hover { background: rgba(244,63,94,.10) !important; }
html[data-theme="dark"] .prod-dropdown-item--danger:hover { background: rgba(244,63,94,.14) !important; color: #F87171 !important; }

.prod-dropdown-icon {
  width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 11.5px;
}
.prod-dropdown-icon--blue  { background: rgba(59,130,246,.12); color: #3b82f6; }
.prod-dropdown-icon--amber { background: rgba(245,158,11,.12);  color: #f59e0b; }
.prod-dropdown-icon--red   { background: rgba(244,63,94,.12);   color: #f43f5e; }
html[data-theme="dark"] .prod-dropdown-icon--blue  { background: rgba(59,130,246,.18); }
html[data-theme="dark"] .prod-dropdown-icon--amber { background: rgba(245,158,11,.18); }
html[data-theme="dark"] .prod-dropdown-icon--red   { background: rgba(244,63,94,.18); color: #F87171; }
.prod-dropdown-divider { height: 1px; background: #E5E7EB; margin: 3px 0; }
html[data-theme="dark"] .prod-dropdown-divider { background: #2A2D3E; }
</style>