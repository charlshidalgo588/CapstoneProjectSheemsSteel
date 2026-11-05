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
          <!-- STOCK FILTER TOGGLE -->
          <div class="stock-filter-toggle">
            <button
              class="sf-btn"
              :class="{ 'sf-btn--active': stockFilter === 'all' }"
              @click="stockFilter = 'all'"
            >
              All Products
            </button>
            <button
              class="sf-btn sf-btn--danger"
              :class="{ 'sf-btn--active': stockFilter === 'low' }"
              @click="stockFilter = 'low'"
            >
              <i class="fa-solid fa-triangle-exclamation"></i>
              Low Stock
              <span v-if="lowStockCount > 0" class="sf-count">{{ lowStockCount }}</span>
            </button>
          </div>

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

      <!-- ── BULK ACTION BAR ── -->
      <transition name="alert-fade">
        <div v-if="selectedCount > 0" class="bulk-bar">
          <div class="bulk-bar-left">
            <i class="fa-solid fa-square-check"></i>
            <span><strong>{{ selectedCount }}</strong> product{{ selectedCount > 1 ? 's' : '' }} selected</span>
          </div>
          <div class="bulk-bar-actions">
            <button class="btn btn--ghost btn--sm" @click="clearSelection">
              <i class="fa-solid fa-xmark"></i> Clear
            </button>
            <button class="btn btn--danger btn--sm" @click="showBulkDeleteModal = true">
              <i class="fa-solid fa-trash-can"></i> Delete Selected
            </button>
          </div>
        </div>
      </transition>

      <!-- ── TABLE CARD ── -->
      <div class="table-card">
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="th-check">
                  <input
                    type="checkbox"
                    class="chk"
                    :checked="allVisibleSelected"
                    :indeterminate="someVisibleSelected"
                    :disabled="filteredProducts.length === 0"
                    @change="toggleSelectAll"
                    aria-label="Select all products"
                  />
                </th>
                <th style="width:25%">Product</th>
                <th style="width:13%">Category</th>
                <th style="width:12%">SKU</th>
                <th style="width:16%">Price</th>
                <th style="width:9%">Stock</th>
                <th style="width:19%; text-align:right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="product in filteredProducts"
                :key="product.ProductID"
                :data-product-row="product.ProductID"
                :class="{
                  'row-highlight': highlightedProductId === product.ProductID,
                  'row-selected': selectedIds.has(product.ProductID)
                }"
              >
                <!-- Select checkbox -->
                <td class="td-check">
                  <input
                    type="checkbox"
                    class="chk"
                    :checked="selectedIds.has(product.ProductID)"
                    @change="toggleSelect(product.ProductID)"
                    :aria-label="`Select ${product.ProductName}`"
                  />
                </td>

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

                <!-- Price: single price, or a range when sizes differ -->
                <td class="cell-price">
                  {{ priceLabel(product) }}
                  <span
                    v-if="sizeCount(product) > 1"
                    class="size-count"
                    :title="sizesTooltip(product)"
                  >{{ sizeCount(product) }} sizes</span>
                </td>

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
                <td colspan="7" class="td-empty">
                  <div class="empty-state">
                    <i :class="stockFilter === 'low' ? 'fa-solid fa-circle-check' : 'fa-solid fa-box-open'"></i>
                    <p>{{ stockFilter === 'low' ? 'No low-stock products right now.' : 'No products found' }}</p>
                    <RouterLink v-if="stockFilter !== 'low'" to="/products/create" class="btn btn--primary btn--sm">
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

    <!-- ── BULK DELETE MODAL ── -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="showBulkDeleteModal" class="modal-backdrop" @click.self="cancelBulkDelete">
          <div class="del-modal">
            <div class="del-modal-icon-ring">
              <div class="del-modal-icon"><i class="fa-solid fa-trash-can"></i></div>
            </div>
            <div class="del-modal-body">
              <h3 class="del-modal-title">Delete {{ selectedCount }} Product{{ selectedCount > 1 ? 's' : '' }}</h3>
              <p class="del-modal-desc">
                You are about to permanently delete
                <strong class="del-modal-name">{{ selectedCount }} selected product{{ selectedCount > 1 ? 's' : '' }}</strong>.
                This cannot be undone.
              </p>
            </div>
            <div class="del-modal-footer">
              <button class="del-modal-btn del-modal-btn--cancel" @click="cancelBulkDelete" :disabled="bulkDeleting">Cancel</button>
              <button class="del-modal-btn del-modal-btn--confirm" @click="confirmBulkDelete" :disabled="bulkDeleting">
                <i v-if="bulkDeleting" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-trash-can"></i>
                {{ bulkDeleting ? 'Deleting…' : 'Yes, Delete All' }}
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

/* stock filter — 'all' | 'low' */
const stockFilter = ref('all')

/* row highlight — set from a notification click, either:
   - ?highlightId=<ProductID>  (product_created notifications — direct ID match)
   - ?highlight=<ProductName>  (low_stock/out_of_stock notifications — name match)
   cleared automatically after a few seconds so it reads as a "flash",
   not a permanent state. */
const highlightedProductId = ref(null)
let highlightTimer = null

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

/* bulk selection */
const selectedIds         = ref(new Set())
const showBulkDeleteModal = ref(false)
const bulkDeleting        = ref(false)

const setMenuButtonRef = id => el => { if (el) menuButtonRefs.set(id, el); else menuButtonRefs.delete(id) }
const imagePath   = path => `http://127.0.0.1:8000/storage/${path}`
const formatPrice = v => Number(v||0).toLocaleString(undefined, { minimumFractionDigits:2 })

/* ── SIZES & PRICES ──
   /api/product-list returns `variants: [{ VariantID, Label, SellingPrice }]`
   on every product. The Price column shows one price, or a range when the
   sizes are priced differently, plus a "N sizes" tag with a hover list. */
const sizeCount = p => p.variants?.length ?? 0

function priceLabel(p) {
  const prices = (p.variants ?? []).map(v => Number(v.SellingPrice))
  if (!prices.length) return `₱${formatPrice(p.SellingPrice)}`
  const min = Math.min(...prices)
  const max = Math.max(...prices)
  return min === max
    ? `₱${formatPrice(min)}`
    : `₱${formatPrice(min)} – ₱${formatPrice(max)}`
}

const sizesTooltip = p =>
  (p.variants ?? []).map(v => `${v.Label} — ₱${formatPrice(v.SellingPrice)}`).join('\n')

// "Low stock" is per-product, driven by each product's own ReorderLevel
// (set on the product itself) rather than one fixed number for the whole
// catalog — a box of nails and a roofing sheet can have very different
// reorder points. Both formatProduct() paths on the backend (productList +
// findByBarcode) already return ReorderLevel inside `inventory`.
const stockClass = p => {
  const qty     = p.inventory?.QuantityOnHand ?? 0
  const reorder = p.inventory?.ReorderLevel ?? 0
  if (qty <= 0)        return 'stock-badge--oos'
  if (qty <= reorder)  return 'stock-badge--low'
  return 'stock-badge--ok'
}
// Anything at or below its own reorder level — including out-of-stock —
// needs restocking attention, so the "Low Stock" filter groups both together.
const isLowStock = p => {
  const qty     = p.inventory?.QuantityOnHand ?? 0
  const reorder = p.inventory?.ReorderLevel ?? 0
  return qty <= reorder
}

async function loadProducts()   { const r = await api.get('/api/product-list'); products.value = r.data }
async function loadCategories() { const r = await api.get('/api/categories');   categories.value = r.data.categories }

/** Called once products are loaded, when the page was opened from a
 *  notification click. Handles two arrival paths:
 *
 *  - product_created notifications carry ?highlightId=<ProductID> —
 *    notifiable_id is populated on these (see ProductController@store),
 *    so we match directly against ProductID. This is the preferred,
 *    unambiguous path.
 *
 *  - low_stock/out_of_stock notifications carry ?highlight=<ProductName>
 *    instead (they predate notifiable_id being populated), so we fall
 *    back to a case-insensitive name match extracted from the
 *    notification's description text.
 *
 *  highlightId is checked first; if present and it matches, we don't
 *  bother with the name fallback. Silently does nothing if no match is
 *  found (e.g. the product was renamed or deleted since the alert fired). */
function applyHighlightFromQuery() {
  const idParam   = route.query.highlightId
  const nameParam = route.query.highlight

  let match = null
  if (idParam) {
    match = products.value.find(p => String(p.ProductID) === String(idParam))
  }
  if (!match && nameParam) {
    match = products.value.find(
      p => p.ProductName?.toLowerCase() === String(nameParam).toLowerCase()
    )
  }
  if (!match) return

  highlightedProductId.value = match.ProductID

  nextTick(() => {
    const el = document.querySelector(`[data-product-row="${match.ProductID}"]`)
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  })

  if (highlightTimer) clearTimeout(highlightTimer)
  highlightTimer = setTimeout(() => { highlightedProductId.value = null }, 3000)
}

onMounted(async () => {
  // Coming from a low-stock notification: force the Low Stock filter on
  // before the list even renders, so the row we're about to highlight
  // is guaranteed to be visible.
  if (route.query.stockFilter === 'low') stockFilter.value = 'low'
  if (route.query.category) selectedCategory.value = String(route.query.category)

  await loadProducts()
  loadCategories()

  applyHighlightFromQuery()
})

onBeforeUnmount(() => { if (highlightTimer) clearTimeout(highlightTimer) })

watch(() => route.query.search, v => { searchQuery.value = (v || '').toLowerCase() }, { immediate: true })
watch(selectedCategory, nc => { router.replace({ query: { ...route.query, category: nc || undefined } }) })

// Search also matches size labels (e.g. "8 ft") so a product can be found by its size.
const filteredProducts = computed(() =>
  products.value.filter(p => {
    const ms = !searchQuery.value || [
      p.ProductName,
      p.SKU,
      p.category?.CategoryName,
      ...(p.variants ?? []).map(v => v.Label),
    ].join(' ').toLowerCase().includes(searchQuery.value)
    const mc = !selectedCategory.value || String(p.CategoryID) === selectedCategory.value
    const mf = stockFilter.value !== 'low' || isLowStock(p)
    return ms && mc && mf
  })
)
const currentProduct = computed(() => filteredProducts.value.find(p => p.ProductID === openMenuId.value))

// Computed off the full product list (not filteredProducts), so the
// count badge on the "Low Stock" button doesn't shrink to itself once
// that filter is active — it always reflects the true total needing attention.
const lowStockCount = computed(() => products.value.filter(isLowStock).length)

/* ── Bulk selection ── */
const selectedCount = computed(() => selectedIds.value.size)

const allVisibleSelected = computed(() =>
  filteredProducts.value.length > 0 &&
  filteredProducts.value.every(p => selectedIds.value.has(p.ProductID))
)
const someVisibleSelected = computed(() =>
  !allVisibleSelected.value &&
  filteredProducts.value.some(p => selectedIds.value.has(p.ProductID))
)

function toggleSelect(id) {
  const next = new Set(selectedIds.value)
  next.has(id) ? next.delete(id) : next.add(id)
  selectedIds.value = next
}

// Selects/deselects only what's currently visible (respects search,
// category and stock filters), so "select all" never grabs hidden rows.
function toggleSelectAll() {
  const next = new Set(selectedIds.value)
  if (allVisibleSelected.value) {
    filteredProducts.value.forEach(p => next.delete(p.ProductID))
  } else {
    filteredProducts.value.forEach(p => next.add(p.ProductID))
  }
  selectedIds.value = next
}

function clearSelection() { selectedIds.value = new Set() }

// Changing a filter clears the selection so you can never delete
// rows that are no longer on screen.
watch([stockFilter, selectedCategory, searchQuery], clearSelection)

function cancelBulkDelete() { if (!bulkDeleting.value) showBulkDeleteModal.value = false }

async function confirmBulkDelete() {
  if (bulkDeleting.value || selectedIds.value.size === 0) return
  bulkDeleting.value = true
  const ids = [...selectedIds.value]
  try {
    const results = await Promise.allSettled(
      ids.map(id => api.delete(`/api/products/${id}`, { withCredentials: true }))
    )
    const failed  = results.filter(r => r.status === 'rejected').length
    const deleted = ids.length - failed

    successMessage.value = failed
      ? `${deleted} deleted, ${failed} failed.`
      : `${deleted} product${deleted > 1 ? 's' : ''} deleted successfully.`

    showBulkDeleteModal.value = false
    clearSelection()
    await loadProducts()
    setTimeout(() => successMessage.value = '', 2500)
  } finally {
    bulkDeleting.value = false
  }
}

/* ── Row dropdown ── */
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
function onKeydown(e) {
  if (e.key === 'Escape') { closeMenu(); closeRestockModal(); cancelDelete(); cancelBulkDelete() }
}
document.addEventListener('click', onClickOutside)
document.addEventListener('keydown', onKeydown)
onBeforeUnmount(() => { document.removeEventListener('click', onClickOutside); document.removeEventListener('keydown', onKeydown) })

/* ── Restock ── */
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

/* ── Single delete ── */
function promptDelete(product) { closeMenu(); productToDelete.value = product; showDeleteModal.value = true }
function cancelDelete() { if (deleting.value) return; showDeleteModal.value = false; productToDelete.value = null }

async function confirmDelete() {
  if (!productToDelete.value || deleting.value) return
  deleting.value = true
  try {
    await api.delete(`/api/products/${productToDelete.value.ProductID}`, { withCredentials: true })
    successMessage.value = `"${productToDelete.value.ProductName}" deleted successfully.`
    // keep selection in sync if the deleted row was ticked
    if (selectedIds.value.has(productToDelete.value.ProductID)) {
      const next = new Set(selectedIds.value); next.delete(productToDelete.value.ProductID); selectedIds.value = next
    }
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

/* STOCK FILTER TOGGLE */
.stock-filter-toggle {
  display: flex; align-items: center;
  border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  overflow: hidden; height: 40px; flex-shrink: 0;
}
.sf-btn {
  display: inline-flex; align-items: center; gap: 6px;
  height: 100%; padding: 0 14px;
  border: none; background: var(--pl-surface); color: var(--pl-text-muted);
  font-size: 12.5px; font-weight: 600; font-family: inherit; cursor: pointer;
  transition: background-color .15s, color .15s;
  white-space: nowrap;
}
.sf-btn:first-child { border-right: 1.5px solid var(--pl-border-strong); }
.sf-btn:hover { background: var(--pl-surface-raised); color: var(--pl-text-primary); }
.sf-btn--active { background: var(--pl-accent-soft); color: var(--pl-accent); }
.sf-btn--danger.sf-btn--active { background: rgba(244,63,94,.12); color: var(--pl-red); }
html[data-theme="dark"] .sf-btn--danger.sf-btn--active { background: rgba(244,63,94,.18); color: #F87171; }
.sf-count {
  min-width: 18px; height: 18px; padding: 0 5px;
  border-radius: 20px; font-size: 10.5px; font-weight: 700;
  display: inline-flex; align-items: center; justify-content: center;
  background: var(--pl-red); color: #fff;
}

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
.btn--danger {
  background: linear-gradient(135deg, #f43f5e, #e11d48);
  color: #fff; border: none; box-shadow: 0 2px 8px rgba(244,63,94,.30);
}
.btn--danger:hover { filter: brightness(1.08); transform: translateY(-1px); }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }

/* ALERT */
.alert { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 12px; font-size: 13.5px; font-weight: 500; border: 1px solid transparent; }
.alert--success { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); color: var(--pl-green); }
html[data-theme="dark"] .alert--success { background: rgba(16,185,129,.14); color: #4ADE80; }
.alert-close { background: none; border: none; cursor: pointer; color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 2px; transition: opacity .15s; }
.alert-close:hover { opacity: 1; }
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* BULK ACTION BAR */
.bulk-bar {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  padding: 10px 16px; border-radius: 12px;
  background: var(--pl-accent-soft); border: 1px solid var(--pl-accent-border);
  color: var(--pl-text-primary); font-size: 13.5px;
}
.bulk-bar-left { display: flex; align-items: center; gap: 10px; }
.bulk-bar-left i { color: var(--pl-accent); font-size: 15px; }
.bulk-bar-actions { display: flex; align-items: center; gap: 8px; }

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

/* CHECKBOX */
.data-table thead th.th-check,
.data-table tbody td.td-check { width: 52px; padding-right: 0; }
.chk {
  appearance: none; -webkit-appearance: none;
  width: 20px; height: 20px; margin: 0; cursor: pointer;
  border: 1.5px solid var(--pl-border-strong); border-radius: 6px;
  background: var(--pl-surface);
  display: inline-grid; place-content: center; vertical-align: middle;
  transition: background-color .15s, border-color .15s, box-shadow .15s;
}
.chk:hover:not(:disabled) { border-color: var(--pl-accent); }
.chk:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }
.chk:disabled { opacity: .5; cursor: not-allowed; }
.chk:checked, .chk:indeterminate {
  background-color: var(--pl-accent); border-color: var(--pl-accent);
}
.chk:checked {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3.5 8.5l3 3 6-7'/%3E%3C/svg%3E");
  background-size: 14px; background-position: center; background-repeat: no-repeat;
}
.chk:indeterminate {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round'%3E%3Cpath d='M4 8h8'/%3E%3C/svg%3E");
  background-size: 14px; background-position: center; background-repeat: no-repeat;
}

/* SELECTED ROW */
.data-table tbody tr.row-selected td { background: var(--pl-accent-soft); }

/* ROW HIGHLIGHT — flashed briefly when arriving from a low-stock or
   product-created notification click, so the exact product the alert
   referred to is unmistakable in the (possibly long) filtered list.
   Green rather than red — the highlight just means "here's the row the
   notification pointed to," not a warning. */
.row-highlight td {
  background: rgba(16,185,129,.12) !important;
  animation: row-pulse 1.6s ease-in-out 1;
}
@keyframes row-pulse {
  0%   { background: rgba(16,185,129,.12); }
  50%  { background: rgba(16,185,129,.24); }
  100% { background: rgba(16,185,129,.12); }
}
html[data-theme="dark"] .row-highlight td { background: rgba(16,185,129,.16) !important; }

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
.cell-price       { font-weight: 700; color: var(--pl-text-primary); font-variant-numeric: tabular-nums; line-height: 1.3; transition: color .22s; }

/* SIZE COUNT TAG (under the price when a product has several sizes) */
.size-count {
  display: table; margin-top: 3px;
  font-size: 10.5px; font-weight: 700; font-variant-numeric: normal;
  color: var(--pl-accent); background: var(--pl-accent-soft);
  border: 1px solid var(--pl-accent-border);
  border-radius: 20px; padding: 1px 7px;
  cursor: help;
}

/* STOCK BADGE — low stock is RED (not amber) so it reads as a clear
   warning next to green "In Stock". Out-of-stock stays a deeper/bolder
   red so the two remain distinct at a glance. */
.stock-badge {
  display: inline-flex; align-items: center;
  font-size: 12px; font-weight: 700; border-radius: 20px; padding: 3px 10px;
}
.stock-badge--ok  { background: rgba(16,185,129,.12); color: var(--pl-green); }
.stock-badge--low { background: rgba(244,63,94,.10);  color: var(--pl-red); border: 1px solid rgba(244,63,94,.22); }
.stock-badge--oos { background: rgba(244,63,94,.16);  color: var(--pl-red); font-weight: 800; }
html[data-theme="dark"] .stock-badge--ok  { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .stock-badge--low { background: rgba(244,63,94,.14);  color: #F87171; border-color: rgba(244,63,94,.30); }
html[data-theme="dark"] .stock-badge--oos { background: rgba(244,63,94,.22);  color: #F87171; }

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

/* MODAL SHARED — solid, opaque overlay; color adapts to theme. */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(15, 23, 42, .70);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
html[data-theme="dark"] .modal-backdrop {
  background: rgba(0, 0, 0, .82);
}
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.95) translateY(10px); }

/* RESTOCK MODAL */
.restock-modal {
  width: 100%; max-width: 420px;
  background: #FFFFFF; border: 1px solid #E5E7EB;
  border-radius: 22px; box-shadow: 0 24px 64px rgba(0,0,0,.35); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
html[data-theme="dark"] .restock-modal {
  background: #1E2130; border-color: #2A2D3E;
  box-shadow: 0 24px 64px rgba(0,0,0,.6);
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

/* DELETE MODAL (single + bulk share this) */
.del-modal {
  width: 100%; max-width: 400px;
  background: #FFFFFF; border: 1px solid #E5E7EB;
  border-radius: 22px; box-shadow: 0 24px 64px rgba(0,0,0,.35); overflow: hidden;
  display: flex; flex-direction: column;
  transition: background-color .22s, border-color .22s;
}
html[data-theme="dark"] .del-modal {
  background: #1E2130; border-color: #2A2D3E;
  box-shadow: 0 24px 64px rgba(0,0,0,.6);
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
  .stock-filter-toggle { width: 100%; }
  .sf-btn { flex: 1; justify-content: center; }
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