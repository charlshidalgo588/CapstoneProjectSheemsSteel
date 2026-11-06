<template>
  <Layout>
    <div class="prod-list-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Inventory</p>
          <h1 class="page-title">Product List</h1>
          <p class="page-sub" v-if="!loading">
            {{ products.length }} product{{ products.length === 1 ? '' : 's' }}
            <template v-if="lowStockCount > 0">
              · <button type="button" class="link-btn link-btn--danger" @click="stockFilter = 'low'">{{ lowStockCount }} need restocking</button>
            </template>
          </p>
        </div>
        <RouterLink to="/products/create" class="btn btn--primary">
          <i class="fa-solid fa-plus"></i> Add Product
        </RouterLink>
      </div>

      <!-- ── ALERTS ── -->
      <transition name="alert-fade">
        <div v-if="successMessage" class="alert alert--success" role="status">
          <i class="fa-solid fa-circle-check"></i>
          <span>{{ successMessage }}</span>
          <button class="alert-close" @click="successMessage = ''" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>
      <transition name="alert-fade">
        <div v-if="errorMessage" class="alert alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>{{ errorMessage }}</span>
          <button v-if="loadFailed" class="link-btn" @click="reload">Try again</button>
          <button class="alert-close" @click="errorMessage = ''" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>

      <!-- ── TOOLBAR ── -->
      <div class="toolbar">
        <div class="toolbar-row">
          <!-- Search -->
          <div class="search-wrap">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input
              v-model="searchInput"
              type="search"
              class="search-input"
              placeholder="Search name, SKU, category or size…"
              aria-label="Search products"
            />
            <button v-if="searchInput" class="search-clear" @click="searchInput = ''" aria-label="Clear search">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Stock filter tabs -->
          <div class="stock-filter-toggle" role="tablist" aria-label="Filter by stock status">
            <button class="sf-btn" role="tab" :aria-selected="stockFilter === 'all'"
              :class="{ 'sf-btn--active': stockFilter === 'all' }" @click="stockFilter = 'all'">
              All <span class="sf-count sf-count--neutral">{{ products.length }}</span>
            </button>
            <button class="sf-btn sf-btn--ok" role="tab" :aria-selected="stockFilter === 'active'"
              :class="{ 'sf-btn--active': stockFilter === 'active' }" @click="stockFilter = 'active'">
              <i class="fa-solid fa-circle-check"></i> Active
              <span class="sf-count sf-count--ok">{{ activeCount }}</span>
            </button>
            <button class="sf-btn sf-btn--danger" role="tab" :aria-selected="stockFilter === 'low'"
              :class="{ 'sf-btn--active': stockFilter === 'low' }" @click="stockFilter = 'low'">
              <i class="fa-solid fa-triangle-exclamation"></i> Low stock
              <span v-if="lowStockCount > 0" class="sf-count">{{ lowStockCount }}</span>
            </button>
          </div>
        </div>

        <div class="toolbar-row toolbar-row--secondary">
          <div class="select-group">
            <label class="select-label" for="cat-select">Category</label>
            <div class="select-wrap">
              <i class="fa-solid fa-layer-group input-icon"></i>
              <select id="cat-select" v-model="selectedCategory" @change="handleCategoryChange" class="form-select">
                <option value="">All categories</option>
                <option v-for="cat in categories" :key="cat.CategoryID" :value="String(cat.CategoryID)">
                  {{ cat.CategoryName }}
                </option>
              </select>
              <i class="fa-solid fa-chevron-down select-caret"></i>
            </div>
          </div>

          <div class="select-group">
            <label class="select-label" for="prod-sort">Sort products</label>
            <div class="select-wrap">
              <i class="fa-solid fa-arrow-down-wide-short input-icon"></i>
              <select id="prod-sort" v-model="productSort" class="form-select">
                <option value="status">Active first, low stock last</option>
                <option value="attention">Needs restock first</option>
                <option value="name">Name (A–Z)</option>
                <option value="stock-desc">Most stock</option>
                <option value="stock-asc">Least stock</option>
              </select>
              <i class="fa-solid fa-chevron-down select-caret"></i>
            </div>
          </div>

          <div class="select-group">
            <label class="select-label" for="size-sort">Sort sizes</label>
            <div class="select-wrap">
              <i class="fa-solid fa-ruler input-icon"></i>
              <select id="size-sort" v-model="variantSort" class="form-select">
                <option value="active">Active first, then by size</option>
                <option value="size">By size only</option>
                <option value="low">Low stock first, then by size</option>
              </select>
              <i class="fa-solid fa-chevron-down select-caret"></i>
            </div>
          </div>

          <button v-if="hasActiveFilters" class="btn btn--ghost btn--sm reset-btn" @click="resetFilters">
            <i class="fa-solid fa-rotate-left"></i> Reset
          </button>
        </div>
      </div>

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
              <i class="fa-solid fa-trash-can"></i> Delete selected
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
                <th style="width:28%">Product</th>
                <th style="width:12%">Category</th>
                <th style="width:15%">Price</th>
                <th style="width:30%">Stock by size</th>
                <th style="width:15%; text-align:right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <!-- Loading skeleton -->
              <template v-if="loading">
                <tr v-for="n in 5" :key="'sk' + n" class="skeleton-row">
                  <td class="td-check"><span class="sk sk--box"></span></td>
                  <td><div class="cell-product"><span class="sk sk--avatar"></span><span class="sk sk--line"></span></div></td>
                  <td><span class="sk sk--line sk--short"></span></td>
                  <td><span class="sk sk--line sk--short"></span></td>
                  <td><span class="sk sk--line"></span></td>
                  <td><span class="sk sk--line sk--short" style="margin-left:auto"></span></td>
                </tr>
              </template>

              <template v-else>
                <tr
                  v-for="product in filteredProducts"
                  :key="product.ProductID"
                  :data-product-row="product.ProductID"
                  :class="{
                    'row-highlight': highlightedProductId === product.ProductID,
                    'row-selected': selectedIds.has(product.ProductID),
                    'row-low': isLowStock(product)
                  }"
                >
                  <td class="td-check">
                    <input
                      type="checkbox"
                      class="chk"
                      :checked="selectedIds.has(product.ProductID)"
                      @change="toggleSelect(product.ProductID)"
                      :aria-label="`Select ${product.ProductName}`"
                    />
                  </td>

                  <!-- Product -->
                  <td>
                    <div class="cell-product">
                      <div class="product-avatar">
                        <img v-if="product.Product_Image" :src="imagePath(product.Product_Image)" class="product-avatar-img" :alt="product.ProductName" />
                        <span v-else class="product-avatar-initials">{{ product.ProductName.substring(0,2).toUpperCase() }}</span>
                      </div>
                      <div class="product-info">
                        <RouterLink :to="`/products/${product.ProductID}`" class="product-name-link" :title="product.ProductName">
                          {{ product.ProductName }}
                        </RouterLink>
                        <span class="product-sku">{{ product.SKU }}</span>
                      </div>
                    </div>
                  </td>

                  <td class="cell-muted">{{ product.category?.CategoryName || '—' }}</td>

                  <!-- Price -->
                  <td class="cell-price">
                    {{ priceLabel(product) }}
                    <span
                      v-if="sizeCount(product) > 1"
                      class="size-count"
                      :title="sizesTooltip(product)"
                    >{{ narrowed(product) ? `${listedCount(product)} ${stockFilter === 'low' ? 'low' : 'active'} of ${sizeCount(product)} sizes` : `${sizeCount(product)} sizes` }}</span>
                  </td>

                  <!-- Stock -->
                  <td>
                    <div class="stock-cell">
                      <div class="stock-summary">
                        <!-- neutral: just the quantity, no status colour -->
                        <span class="stock-total" :title="`${totalStock(product)} units across ${Math.max(sizeCount(product), 1)} size(s)`">
                          <strong>{{ totalStock(product) }}</strong>
                          <span>{{ sizeCount(product) > 1 ? 'total units' : 'in stock' }}</span>
                        </span>
                        <!-- coloured: the status, kept visually separate from the quantity -->
                        <span class="stock-status" :class="stockClass(product)">
                          <i class="fa-solid" :class="stockIcon(product)"></i>
                          {{ stockLabel(product) }}
                        </span>
                      </div>

                      <ul v-if="sizeCount(product) > 1" class="size-stock-list">
                        <li
                          v-for="v in visibleVariants(product)"
                          :key="v.VariantID"
                          class="size-stock-item"
                          :class="variantClass(v)"
                          :title="variantTooltip(v)"
                        >
                          <span class="size-stock-label">{{ v.Label }}</span>
                          <span class="size-stock-qty">{{ variantStock(v) }}</span>
                        </li>
                      </ul>

                      <button
                        v-if="listedCount(product) > MAX_INLINE_SIZES"
                        type="button"
                        class="size-more"
                        @click="toggleExpand(product.ProductID)"
                      >
                        <template v-if="expandedIds.has(product.ProductID)">Show fewer sizes</template>
                        <template v-else>Show {{ listedCount(product) - MAX_INLINE_SIZES }} more size{{ listedCount(product) - MAX_INLINE_SIZES > 1 ? 's' : '' }}</template>
                      </button>
                    </div>
                  </td>

                  <!-- Actions -->
                  <td class="td-actions">
                    <div class="action-row">
                      <button
                        class="restock-btn"
                        :class="{ 'restock-btn--urgent': isLowStock(product) }"
                        @click="openRestockModal(product)"
                      >
                        <i class="fa-solid fa-boxes-stacked"></i> Restock
                      </button>
                      <button
                        :ref="setMenuButtonRef(product.ProductID)"
                        @click.stop="toggleMenu(product.ProductID)"
                        class="menu-btn"
                        :class="{ 'menu-btn--active': openMenuId === product.ProductID }"
                        :aria-label="`More actions for ${product.ProductName}`"
                        aria-haspopup="menu"
                      >
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="filteredProducts.length === 0">
                  <td colspan="6" class="td-empty">
                    <div class="empty-state">
                      <i :class="emptyIcon"></i>
                      <p>{{ emptyTitle }}</p>
                      <span class="empty-sub">{{ emptyHint }}</span>
                      <div class="empty-actions">
                        <button v-if="hasActiveFilters" class="btn btn--ghost btn--sm" @click="resetFilters">
                          <i class="fa-solid fa-rotate-left"></i> Clear filters
                        </button>
                        <RouterLink v-if="products.length === 0" to="/products/create" class="btn btn--primary btn--sm">
                          <i class="fa-solid fa-plus"></i> Add your first product
                        </RouterLink>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div v-if="!loading && products.length > 0" class="table-footer">
          Showing <strong>{{ filteredProducts.length }}</strong> of {{ products.length }} products
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
        role="menu"
      >
        <RouterLink :to="`/products/${currentProduct.ProductID}`" class="prod-dropdown-item" @click="closeMenu">
          <span class="prod-dropdown-icon prod-dropdown-icon--blue"><i class="fa-solid fa-eye"></i></span>
          View details
        </RouterLink>
        <RouterLink :to="`/products/${currentProduct.ProductID}/edit`" class="prod-dropdown-item" @click="closeMenu">
          <span class="prod-dropdown-icon prod-dropdown-icon--amber"><i class="fa-solid fa-pen-to-square"></i></span>
          Edit product
        </RouterLink>
        <div class="prod-dropdown-divider"></div>
        <button class="prod-dropdown-item prod-dropdown-item--danger" @click="promptDelete(currentProduct)">
          <span class="prod-dropdown-icon prod-dropdown-icon--red"><i class="fa-solid fa-trash"></i></span>
          Delete
        </button>
      </div>
    </Teleport>

    <!-- ── RESTOCK MODAL (one size at a time) ── -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="modalVisible" class="modal-backdrop" @click.self="closeRestockModal">
          <div class="restock-modal" role="dialog" aria-modal="true" aria-label="Restock product">
            <div class="restock-modal-header">
              <div class="restock-modal-icon">
                <i class="fa-solid fa-boxes-stacked"></i>
              </div>
              <div>
                <p class="restock-modal-eyebrow">Inventory</p>
                <h3 class="restock-modal-title">Restock product</h3>
              </div>
              <button class="modal-close-btn" @click="closeRestockModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="restock-modal-body">
              <div class="restock-product-chip">
                <div class="restock-chip-avatar">
                  <img v-if="modalProduct?.Product_Image" :src="imagePath(modalProduct.Product_Image)" class="restock-chip-img" />
                  <span v-else>{{ modalProduct?.ProductName?.substring(0,2).toUpperCase() }}</span>
                </div>
                <div>
                  <p class="restock-chip-name">{{ modalProduct?.ProductName }}</p>
                  <p class="restock-chip-stock">
                    <template v-if="modalVariants.length > 1">Total stock (all sizes): </template>
                    <template v-else>Current stock: </template>
                    <strong>{{ modalProduct ? totalStock(modalProduct) : 0 }}</strong>
                  </p>
                </div>
              </div>

              <form @submit.prevent="submitRestock">
                <!-- Size picker -->
                <div v-if="modalVariants.length > 1" class="restock-field restock-field--gap">
                  <label class="restock-label">Size to restock <span class="req">*</span></label>
                  <div class="size-pick">
                    <button
                      v-for="v in modalVariants"
                      :key="v.VariantID"
                      type="button"
                      class="size-pick-btn"
                      :class="[variantClass(v), { 'size-pick-btn--active': restockVariantId === v.VariantID }]"
                      @click="restockVariantId = v.VariantID"
                    >
                      <span class="size-pick-label">{{ v.Label }}</span>
                      <span class="size-pick-qty">{{ variantStock(v) }} in stock</span>
                    </button>
                  </div>
                </div>

                <div class="restock-field">
                  <label class="restock-label">Quantity to add <span class="req">*</span></label>
                  <div class="qty-row">
                    <button type="button" class="qty-btn" @click="restockQty = Math.max(1, (restockQty || 1) - 1)" aria-label="Decrease"><i class="fa-solid fa-minus"></i></button>
                    <input v-model.number="restockQty" type="number" min="1" required class="qty-input" />
                    <button type="button" class="qty-btn" @click="restockQty = (restockQty || 0) + 1" aria-label="Increase"><i class="fa-solid fa-plus"></i></button>
                  </div>
                  <div class="qty-quick">
                    <button v-for="n in [5, 10, 25, 50]" :key="n" type="button" class="qty-chip" @click="restockQty = (restockQty || 0) + n">+{{ n }}</button>
                  </div>
                  <p class="restock-hint">
                    <template v-if="modalVariant">Stock of <strong>{{ modalVariant.Label }}</strong> after restock:</template>
                    <template v-else>New stock after restock:</template>
                    <strong>{{ modalNewStock }}</strong>
                    <span v-if="modalStillLow" class="restock-warn">
                      <i class="fa-solid fa-triangle-exclamation"></i> still at or below reorder level ({{ modalThreshold }})
                    </span>
                  </p>
                </div>

                <div class="restock-footer">
                  <button type="button" class="btn btn--ghost" @click="closeRestockModal">Cancel</button>
                  <button type="submit" class="btn btn--green" :disabled="isRestocking">
                    <i v-if="isRestocking" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-boxes-stacked"></i>
                    {{ isRestocking ? 'Restocking…' : 'Confirm restock' }}
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
          <div class="del-modal" role="alertdialog" aria-modal="true" aria-label="Delete product">
            <div class="del-modal-icon-ring">
              <div class="del-modal-icon"><i class="fa-solid fa-trash-can"></i></div>
            </div>
            <div class="del-modal-body">
              <h3 class="del-modal-title">Delete product</h3>
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
                {{ deleting ? 'Deleting…' : 'Yes, delete' }}
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
          <div class="del-modal" role="alertdialog" aria-modal="true" aria-label="Delete products">
            <div class="del-modal-icon-ring">
              <div class="del-modal-icon"><i class="fa-solid fa-trash-can"></i></div>
            </div>
            <div class="del-modal-body">
              <h3 class="del-modal-title">Delete {{ selectedCount }} product{{ selectedCount > 1 ? 's' : '' }}</h3>
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
                {{ bulkDeleting ? 'Deleting…' : 'Yes, delete all' }}
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

const products         = ref([])
const categories       = ref([])
const selectedCategory = ref('')
const successMessage   = ref('')
const errorMessage     = ref('')
const loading          = ref(true)
const loadFailed       = ref(false)

/* search: `searchInput` is what the box shows, `searchQuery` is the lowercase
   term used for filtering. The global header search (?search=) feeds the same box. */
const searchInput = ref('')
const searchQuery = computed(() => searchInput.value.trim().toLowerCase())

/* stock filter — 'all' | 'active' | 'low' */
const stockFilter = ref('all')

/* ── remembered sort preferences ── */
const pref = (key, fallback) => {
  try { return localStorage.getItem(`prodlist_${key}`) || fallback } catch { return fallback }
}
const savePref = (key, val) => { try { localStorage.setItem(`prodlist_${key}`, val) } catch { /* ignore */ } }

/* product order: 'status' | 'attention' | 'name' | 'stock-desc' | 'stock-asc' */
const productSort = ref(pref('productSort', 'status'))
/* size order inside each product: 'active' | 'size' | 'low' */
const variantSort = ref(pref('variantSort', 'active'))
watch(productSort, v => savePref('productSort', v))
watch(variantSort, v => savePref('variantSort', v))

/* row highlight — set from a notification click, either:
   - ?highlightId=<ProductID>  (product_created notifications — direct ID match)
   - ?highlight=<ProductName>  (low_stock/out_of_stock notifications — name match)
   cleared automatically after a few seconds so it reads as a "flash". */
const highlightedProductId = ref(null)
let highlightTimer = null
let messageTimer   = null

/* restock */
const modalVisible     = ref(false)
const modalProduct     = ref(null)
const restockQty       = ref(1)
const restockVariantId = ref(null)
const isRestocking     = ref(false)

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

/* per-size list: which products have their full size list open */
const MAX_INLINE_SIZES = 4
const expandedIds = ref(new Set())

const setMenuButtonRef = id => el => { if (el) menuButtonRefs.set(id, el); else menuButtonRefs.delete(id) }
const imagePath   = path => `http://127.0.0.1:8000/storage/${path}`
const formatPrice = v => Number(v||0).toLocaleString(undefined, { minimumFractionDigits:2 })

/* toasts */
function flashSuccess(msg) {
  errorMessage.value = ''
  successMessage.value = msg
  clearTimeout(messageTimer)
  messageTimer = setTimeout(() => { successMessage.value = '' }, 3000)
}
function flashError(msg, { sticky = false } = {}) {
  successMessage.value = ''
  errorMessage.value = msg
  clearTimeout(messageTimer)
  if (!sticky) messageTimer = setTimeout(() => { errorMessage.value = '' }, 5000)
}

/* ── SIZES, PRICES & STOCK ──
   /api/product-list returns, per product:
     variants: [{ VariantID, Label, SellingPrice, Stock, ReorderLevel }]
   Stock and ReorderLevel are PER SIZE. The product total is the sum of its sizes. */
const variantsOf  = p => p?.variants ?? []
const sizeCount   = p => variantsOf(p).length

function priceLabel(p) {
  const prices = variantsOf(p).map(v => Number(v.SellingPrice))
  if (!prices.length) return `₱${formatPrice(p.SellingPrice)}`
  const min = Math.min(...prices)
  const max = Math.max(...prices)
  return min === max
    ? `₱${formatPrice(min)}`
    : `₱${formatPrice(min)} – ₱${formatPrice(max)}`
}

const sizesTooltip = p =>
  variantsOf(p).map(v => `${v.Label} — ₱${formatPrice(v.SellingPrice)}`).join('\n')

/* ── STOCK HELPERS ── */
// Same rule the server uses for alerts (StockService::notifyIfLow):
// a size is "low" at or below its own ReorderLevel, or at or below 5
// when no reorder level was set.
const LOW_STOCK_DEFAULT = 5

const variantStock     = v => Number(v?.Stock ?? 0)
const variantThreshold = v => (Number(v?.ReorderLevel) > 0 ? Number(v.ReorderLevel) : LOW_STOCK_DEFAULT)

// 0 = active (healthy) · 1 = low · 2 = out of stock
const variantRank = v => {
  const qty = variantStock(v)
  if (qty <= 0) return 2
  return qty <= variantThreshold(v) ? 1 : 0
}
const RANK_CLASS = ['stock-badge--ok', 'stock-badge--low', 'stock-badge--oos']
const variantClass = v => RANK_CLASS[variantRank(v)]

const variantTooltip = v =>
  `${v.Label}: ${variantStock(v)} in stock · reorder level ${Number(v.ReorderLevel) || 0}`

const hasVariantStock = p =>
  variantsOf(p).length > 0 && variantsOf(p).some(v => v.Stock !== undefined && v.Stock !== null)

const totalStock = p =>
  hasVariantStock(p)
    ? variantsOf(p).reduce((s, v) => s + variantStock(v), 0)
    : (p.inventory?.QuantityOnHand ?? 0)

// Product rank: 0 = every size healthy · 1 = at least one size low · 2 = nothing left
const productRank = p => {
  const total = totalStock(p)
  if (total <= 0) return 2
  if (hasVariantStock(p)) return variantsOf(p).some(v => variantRank(v) > 0) ? 1 : 0
  return total <= (p.inventory?.ReorderLevel ?? 0) ? 1 : 0
}

const stockClass = p => RANK_CLASS[productRank(p)]
const stockIcon  = p => ['fa-circle-check', 'fa-triangle-exclamation', 'fa-circle-xmark'][productRank(p)]
const stockLabel = p => {
  const rank = productRank(p)
  const multi = sizeCount(p) > 1
  if (rank === 0) return multi ? 'All sizes OK' : 'In stock'
  if (rank === 2) return 'Out of stock'
  if (!hasVariantStock(p) || !multi) return 'Low stock'
  const n = variantsOf(p).filter(v => variantRank(v) > 0).length
  return `${n} size${n > 1 ? 's' : ''} to restock`
}

// Anything with a size at/below its reorder level — including out-of-stock —
// needs restocking attention, so "Low stock" groups them together.
const isLowStock = p => productRank(p) > 0

/* ── SIZE SORTING ──
   Reads numbers out of labels ("8 ft", "10 ft", "1/2 in", "2 1/2 in") and
   knows common named sizes (XS … XXXL), so "8" sorts before "10". */
const NAMED_SIZES = { xxs:0, xs:1, s:2, small:2, m:3, medium:3, l:4, large:4, xl:5, xxl:6, xxxl:7 }

function sizeKey(label) {
  const s = String(label ?? '').trim().toLowerCase()
  if (s in NAMED_SIZES) return NAMED_SIZES[s]
  const mixed = s.match(/(\d+)\s+(\d+)\s*\/\s*(\d+)/)
  if (mixed) return Number(mixed[1]) + Number(mixed[2]) / Number(mixed[3])
  const frac = s.match(/(\d+)\s*\/\s*(\d+)/)
  if (frac) return Number(frac[1]) / Number(frac[2])
  const num = s.match(/\d+(?:\.\d+)?/)
  return num ? Number(num[0]) : Infinity
}

function compareSize(a, b) {
  const ka = sizeKey(a.Label), kb = sizeKey(b.Label)
  if (ka !== kb) return ka < kb ? -1 : 1
  return String(a.Label ?? '').localeCompare(String(b.Label ?? ''), undefined, { numeric: true, sensitivity: 'base' })
}

function compareVariants(a, b) {
  if (variantSort.value === 'size') return compareSize(a, b)
  const ra = variantRank(a), rb = variantRank(b)
  if (ra !== rb) {
    // 'active' → healthy first, out-of-stock last; 'low' → the reverse
    return variantSort.value === 'low' ? rb - ra : ra - rb
  }
  return compareSize(a, b)
}

const sortedVariants = p => [...variantsOf(p)].sort(compareVariants)

/* per-size list in the Stock column.
   While "Low stock" is on, only the LOW (and out-of-stock) sizes are listed. */
const lowOnly    = p => stockFilter.value === 'low'    && hasVariantStock(p)
const activeOnly = p => stockFilter.value === 'active' && hasVariantStock(p)

// A product counts as "active" when at least one of its sizes is healthy,
// so a product with 3 healthy sizes and 1 low size shows up under BOTH
// "Active" (with its healthy sizes) and "Low stock" (with its low size).
const hasActiveStock = p =>
  hasVariantStock(p)
    ? variantsOf(p).some(v => variantRank(v) === 0)
    : productRank(p) === 0

const listedVariants = p => {
  const all = sortedVariants(p)
  if (lowOnly(p))    return all.filter(v => variantRank(v) > 0)
  if (activeOnly(p)) return all.filter(v => variantRank(v) === 0)
  return all
}
// true when the size list is narrowed by the Active / Low stock tab
const narrowed = p => lowOnly(p) || activeOnly(p)
const listedCount = p => listedVariants(p).length

const visibleVariants = p =>
  expandedIds.value.has(p.ProductID) ? listedVariants(p) : listedVariants(p).slice(0, MAX_INLINE_SIZES)

function toggleExpand(id) {
  const next = new Set(expandedIds.value)
  next.has(id) ? next.delete(id) : next.add(id)
  expandedIds.value = next
}

/* ── LOADING ── */
async function loadProducts() {
  try {
    const r = await api.get('/api/product-list')
    products.value = r.data
    loadFailed.value = false
  } catch (err) {
    loadFailed.value = true
    flashError(err?.response?.data?.message || 'Could not load products.', { sticky: true })
  }
}
async function loadCategories() {
  try { const r = await api.get('/api/categories'); categories.value = r.data.categories } catch { /* non-critical */ }
}
async function reload() {
  errorMessage.value = ''
  loading.value = true
  await loadProducts()
  loading.value = false
}

/** Called once products are loaded, when the page was opened from a
 *  notification click. product_created → ?highlightId=<ProductID>;
 *  low_stock/out_of_stock → ?highlight=<ProductName> (also accepts "Product (Size)").
 *  Silently does nothing if no match is found. */
function applyHighlightFromQuery() {
  const idParam   = route.query.highlightId
  const nameParam = route.query.highlight

  let match = null
  if (idParam) {
    match = products.value.find(p => String(p.ProductID) === String(idParam))
  }
  if (!match && nameParam) {
    const wanted = String(nameParam).toLowerCase()
    match = products.value.find(p => {
      const pn = p.ProductName?.toLowerCase()
      return pn && (pn === wanted || wanted.startsWith(pn + ' ('))
    })
  }
  if (!match) return

  highlightedProductId.value = match.ProductID

  if (listedCount(match) > MAX_INLINE_SIZES) {
    expandedIds.value = new Set([...expandedIds.value, match.ProductID])
  }

  nextTick(() => {
    const el = document.querySelector(`[data-product-row="${match.ProductID}"]`)
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  })

  if (highlightTimer) clearTimeout(highlightTimer)
  highlightTimer = setTimeout(() => { highlightedProductId.value = null }, 3000)
}

onMounted(async () => {
  if (route.query.stockFilter === 'low') stockFilter.value = 'low'
  if (route.query.category) selectedCategory.value = String(route.query.category)

  document.addEventListener('click', onClickOutside)
  document.addEventListener('keydown', onKeydown)

  loadCategories()
  await loadProducts()
  loading.value = false

  applyHighlightFromQuery()
})

onBeforeUnmount(() => {
  clearTimeout(highlightTimer)
  clearTimeout(messageTimer)
  document.removeEventListener('click', onClickOutside)
  document.removeEventListener('keydown', onKeydown)
})

watch(() => route.query.search, v => { searchInput.value = v || '' }, { immediate: true })
watch(selectedCategory, nc => { router.replace({ query: { ...route.query, category: nc || undefined } }) })

/* ── PRODUCT ORDER ──
   'status'    : healthy → low → out of stock          (default)
   'attention' : out of stock → low → healthy
   others      : name / total stock
   Ties always fall back to name so the order is predictable. */
const byName = (a, b) => String(a.ProductName ?? '').localeCompare(String(b.ProductName ?? ''), undefined, { numeric: true, sensitivity: 'base' })

function compareProducts(a, b) {
  switch (productSort.value) {
    case 'attention': return (productRank(b) - productRank(a)) || byName(a, b)
    case 'name':      return byName(a, b)
    case 'stock-desc':return (totalStock(b) - totalStock(a)) || byName(a, b)
    case 'stock-asc': return (totalStock(a) - totalStock(b)) || byName(a, b)
    case 'status':
    default:          return (productRank(a) - productRank(b)) || byName(a, b)
  }
}

// Search also matches size labels (e.g. "8 ft") so a product can be found by its size.
const filteredProducts = computed(() =>
  products.value
    .filter(p => {
      const ms = !searchQuery.value || [
        p.ProductName,
        p.SKU,
        p.category?.CategoryName,
        ...variantsOf(p).map(v => v.Label),
      ].join(' ').toLowerCase().includes(searchQuery.value)
      const mc = !selectedCategory.value || String(p.CategoryID) === selectedCategory.value
      const mf =
        stockFilter.value === 'all' ||
        (stockFilter.value === 'low'    && isLowStock(p)) ||
        (stockFilter.value === 'active' && hasActiveStock(p))
      return ms && mc && mf
    })
    .sort(compareProducts)
)
const currentProduct = computed(() => filteredProducts.value.find(p => p.ProductID === openMenuId.value))

// Counts come off the full list so the tab badges never shrink to themselves.
const lowStockCount = computed(() => products.value.filter(isLowStock).length)
const activeCount   = computed(() => products.value.filter(hasActiveStock).length)

/* filters / empty states */
const hasActiveFilters = computed(() =>
  !!searchInput.value || !!selectedCategory.value || stockFilter.value !== 'all'
)
function resetFilters() {
  searchInput.value = ''
  selectedCategory.value = ''
  stockFilter.value = 'all'
  if (route.query.search) router.replace({ query: { ...route.query, search: undefined } })
}

const emptyIcon  = computed(() =>
  products.value.length === 0 ? 'fa-solid fa-box-open'
  : stockFilter.value === 'low' && !searchInput.value && !selectedCategory.value ? 'fa-solid fa-circle-check'
  : 'fa-solid fa-magnifying-glass')
const emptyTitle = computed(() =>
  products.value.length === 0 ? 'No products yet'
  : stockFilter.value === 'low' && !searchInput.value && !selectedCategory.value ? 'Nothing needs restocking'
  : 'No products match your filters')
const emptyHint  = computed(() =>
  products.value.length === 0 ? 'Add a product to start tracking its stock.'
  : stockFilter.value === 'low' && !searchInput.value && !selectedCategory.value ? 'Every size is above its reorder level.'
  : 'Try a different search or clear the filters.')

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

// Selects/deselects only what's currently visible, so "select all" never grabs hidden rows.
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

// Changing a filter clears the selection so you can never delete rows that are no longer on screen.
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

    if (failed) flashError(`${deleted} deleted, ${failed} failed. Please try the failed ones again.`)
    else flashSuccess(`${deleted} product${deleted > 1 ? 's' : ''} deleted.`)

    showBulkDeleteModal.value = false
    clearSelection()
    await loadProducts()
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
    const menuH = 150
    const openUp = rect.bottom + menuH + 16 > window.innerHeight
    dropdownStyle.value = {
      position: 'fixed',
      top: openUp ? `${rect.top - menuH - 8}px` : `${rect.bottom + 8}px`,
      left: `${Math.max(8, rect.right - 176)}px`
    }
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

/* ── Restock (one size at a time) ── */
const modalVariants = computed(() => sortedVariants(modalProduct.value))
const modalVariant  = computed(() =>
  modalVariants.value.find(v => v.VariantID === restockVariantId.value) ?? null
)
const modalCurrentStock = computed(() =>
  modalVariant.value ? variantStock(modalVariant.value) : (modalProduct.value ? totalStock(modalProduct.value) : 0)
)
const modalNewStock = computed(() => modalCurrentStock.value + (Number(restockQty.value) || 0))
const modalThreshold = computed(() =>
  modalVariant.value ? variantThreshold(modalVariant.value) : (modalProduct.value?.inventory?.ReorderLevel ?? 0)
)
const modalStillLow = computed(() => modalNewStock.value <= modalThreshold.value)

function openRestockModal(product) {
  modalProduct.value = product
  restockQty.value = 1
  // Pre-select the size with the LEAST stock — usually the one that needs restocking.
  const vs = variantsOf(product)
  restockVariantId.value = vs.length
    ? vs.reduce((a, b) => (variantStock(b) < variantStock(a) ? b : a)).VariantID
    : null
  modalVisible.value = true
}
function closeRestockModal() {
  modalVisible.value = false
  modalProduct.value = null
  restockQty.value = 1
  restockVariantId.value = null
}

async function submitRestock() {
  if (!modalProduct.value || !restockQty.value || restockQty.value < 1 || isRestocking.value) return
  if (modalVariants.value.length && !restockVariantId.value) {
    flashError('Choose which size to restock.')
    return
  }
  isRestocking.value = true
  try {
    const payload = { quantity: Number(restockQty.value) }
    if (restockVariantId.value) payload.variant_id = restockVariantId.value

    await api.post(`/api/products/${modalProduct.value.ProductID}/restock`, payload, { withCredentials: true })

    const label = modalVariant.value && modalVariants.value.length > 1 ? ` (${modalVariant.value.Label})` : ''
    const name  = modalProduct.value.ProductName
    flashSuccess(`Added ${payload.quantity} to ${name}${label}.`)
    closeRestockModal(); await loadProducts()
  } catch (err) { flashError(err?.response?.data?.message || 'Could not restock this product.') }
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
    flashSuccess(`"${productToDelete.value.ProductName}" deleted.`)
    if (selectedIds.value.has(productToDelete.value.ProductID)) {
      const next = new Set(selectedIds.value); next.delete(productToDelete.value.ProductID); selectedIds.value = next
    }
    showDeleteModal.value = false; productToDelete.value = null
    await loadProducts()
  } catch (err) { flashError(err?.response?.data?.message || `Delete failed (${err?.response?.status})`) }
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
  display: flex; flex-direction: column; gap: 18px;
  transition: background-color .22s;
}

/* HEADER */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--pl-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--pl-text-primary); letter-spacing: -.03em; margin: 0; transition: color .22s; }
.page-sub     { margin: 6px 0 0; font-size: 13px; color: var(--pl-text-muted); }
.link-btn {
  background: none; border: none; padding: 0; cursor: pointer;
  font: inherit; font-weight: 600; color: var(--pl-accent);
}
.link-btn:hover { text-decoration: underline; }
.link-btn--danger { color: var(--pl-red); }
html[data-theme="dark"] .link-btn--danger { color: #F87171; }

/* TOOLBAR */
.toolbar {
  display: flex; flex-direction: column; gap: 12px;
  background: var(--pl-surface); border: 1px solid var(--pl-border);
  border-radius: var(--radius); padding: 14px 16px;
  box-shadow: var(--c-shadow-sm);
}
.toolbar-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.toolbar-row--secondary {
  padding-top: 12px; border-top: 1px solid var(--pl-border);
  align-items: flex-end;
}

/* SEARCH */
.search-wrap { position: relative; flex: 1 1 260px; min-width: 200px; display: flex; align-items: center; }
.search-icon { position: absolute; left: 13px; font-size: 12px; color: var(--pl-text-faint); pointer-events: none; }
.search-input {
  width: 100%; height: 40px; padding: 0 36px 0 36px;
  border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface-raised); color: var(--pl-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s;
  -webkit-appearance: none; appearance: none;
}
.search-input::placeholder { color: var(--pl-text-faint); }
.search-input:focus { border-color: var(--pl-accent); box-shadow: 0 0 0 3px var(--pl-ring); }
.search-input::-webkit-search-cancel-button { display: none; }
.search-clear {
  position: absolute; right: 8px; width: 24px; height: 24px; border: none; border-radius: 6px;
  background: none; color: var(--pl-text-faint); cursor: pointer; font-size: 11px;
  display: flex; align-items: center; justify-content: center;
}
.search-clear:hover { background: var(--pl-border); color: var(--pl-text-primary); }

/* STOCK FILTER TABS */
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
.sf-btn + .sf-btn { border-left: 1.5px solid var(--pl-border-strong); }
.sf-btn:hover { background: var(--pl-surface-raised); color: var(--pl-text-primary); }
.sf-btn:focus-visible { outline: none; box-shadow: inset 0 0 0 2px var(--pl-accent); }
.sf-btn--active { background: var(--pl-accent-soft); color: var(--pl-accent); }
.sf-btn--ok.sf-btn--active { background: rgba(16,185,129,.12); color: var(--pl-green); }
.sf-btn--danger.sf-btn--active { background: rgba(244,63,94,.12); color: var(--pl-red); }
html[data-theme="dark"] .sf-btn--ok.sf-btn--active { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .sf-btn--danger.sf-btn--active { background: rgba(244,63,94,.18); color: #F87171; }
.sf-count {
  min-width: 20px; height: 18px; padding: 0 6px;
  border-radius: 20px; font-size: 10.5px; font-weight: 700;
  display: inline-flex; align-items: center; justify-content: center;
  background: var(--pl-red); color: #fff;
}
.sf-count--neutral { background: var(--pl-border); color: var(--pl-text-secondary); }
.sf-count--ok { background: rgba(16,185,129,.18); color: var(--pl-green); }
html[data-theme="dark"] .sf-count--ok { color: #4ADE80; }

/* SELECTS */
.select-group { display: flex; flex-direction: column; gap: 5px; }
.select-label { font-size: 11.5px; font-weight: 600; color: var(--pl-text-muted); padding-left: 2px; }
.select-wrap { position: relative; display: flex; align-items: center; }
.input-icon  { position: absolute; left: 11px; font-size: 11px; color: var(--pl-text-faint); pointer-events: none; z-index: 1; }
.select-caret{ position: absolute; right: 10px; font-size: 9px; color: var(--pl-text-faint); pointer-events: none; }
.form-select {
  height: 38px; padding: 0 30px 0 30px; min-width: 190px;
  border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface); color: var(--pl-text-secondary);
  font-size: 13px; font-weight: 600; font-family: inherit;
  outline: none; appearance: none; -webkit-appearance: none; cursor: pointer;
  transition: border-color .18s, background-color .22s, color .22s;
}
.form-select:focus { border-color: var(--pl-accent); box-shadow: 0 0 0 3px var(--pl-ring); }
.reset-btn { height: 38px; margin-left: auto; }

/* BUTTONS */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }
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
.alert--error   { background: rgba(244,63,94,.10);  border-color: rgba(244,63,94,.25);  color: var(--pl-red); }
html[data-theme="dark"] .alert--success { background: rgba(16,185,129,.14); color: #4ADE80; }
html[data-theme="dark"] .alert--error   { background: rgba(244,63,94,.14);  color: #F87171; }
.alert-close { background: none; border: none; cursor: pointer; color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 2px; transition: opacity .15s; }
.alert-close:hover { opacity: 1; }
.alert .link-btn { color: inherit; text-decoration: underline; margin-left: auto; }
.alert .link-btn + .alert-close { margin-left: 4px; }
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
.table-footer {
  padding: 11px 18px; border-top: 1px solid var(--pl-border);
  font-size: 12px; color: var(--pl-text-muted); background: var(--pl-surface-raised);
}
.table-footer strong { color: var(--pl-text-primary); }

.data-table { width: 100%; min-width: 880px; border-collapse: collapse; font-size: 13.5px; table-layout: fixed; }
.data-table thead th {
  background: var(--pl-surface-raised); color: var(--pl-text-faint);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--pl-border);
  white-space: nowrap; transition: background-color .22s, color .22s, border-color .22s;
}
.data-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--pl-border); vertical-align: top; transition: background-color .15s, border-color .22s; }
.data-table tbody tr:hover td { background: var(--pl-surface-raised); }
.data-table tbody tr:last-child td { border-bottom: none; }
/* top-align name/price/actions so tall size lists don't float them mid-row */
.data-table tbody td.td-check, .data-table tbody td.td-actions { padding-top: 16px; }

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

/* ROW STATES */
.data-table tbody tr.row-selected td { background: var(--pl-accent-soft); }
/* red edge on rows that need restocking — easy to spot while scrolling */
.data-table tbody tr.row-low td:first-child { box-shadow: inset 3px 0 0 var(--pl-red); }

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

/* SKELETON */
.sk { display: block; border-radius: 6px; background: var(--pl-border); animation: sk-pulse 1.3s ease-in-out infinite; }
.sk--box    { width: 20px; height: 20px; }
.sk--avatar { width: 42px; height: 42px; border-radius: 10px; flex-shrink: 0; }
.sk--line   { height: 12px; width: 80%; }
.sk--short  { width: 50%; }
@keyframes sk-pulse { 0%,100% { opacity: .55; } 50% { opacity: 1; } }

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
.product-sku      { font-size: 11.5px; color: var(--pl-text-faint); font-variant-numeric: tabular-nums; }
.cell-muted       { color: var(--pl-text-muted); transition: color .22s; padding-top: 22px !important; }
.cell-price       { font-weight: 700; color: var(--pl-text-primary); font-variant-numeric: tabular-nums; line-height: 1.3; transition: color .22s; padding-top: 16px !important; }

/* SIZE COUNT TAG */
.size-count {
  display: table; margin-top: 4px;
  font-size: 10.5px; font-weight: 700; font-variant-numeric: normal;
  color: var(--pl-accent); background: var(--pl-accent-soft);
  border: 1px solid var(--pl-accent-border);
  border-radius: 20px; padding: 1px 7px;
  cursor: help;
}

/* STOCK SUMMARY — quantity (neutral) and status (coloured) are separate */
.stock-summary { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
.stock-total {
  display: inline-flex; align-items: baseline; gap: 5px;
  font-variant-numeric: tabular-nums; color: var(--pl-text-muted); font-size: 12px;
}
.stock-total strong { font-size: 15px; font-weight: 800; color: var(--pl-text-primary); }
.stock-status {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 11.5px; font-weight: 700; border-radius: 6px; padding: 3px 9px;
}
.stock-status i { font-size: 10.5px; }
.stock-status.stock-badge--ok  { background: rgba(16,185,129,.12); color: var(--pl-green); border: 1px solid rgba(16,185,129,.25); }
.stock-status.stock-badge--low { background: rgba(245,158,11,.12); color: #b45309; border: 1px solid rgba(245,158,11,.35); }
.stock-status.stock-badge--oos { background: rgba(244,63,94,.12);  color: var(--pl-red); border: 1px solid rgba(244,63,94,.30); }
html[data-theme="dark"] .stock-status.stock-badge--ok  { background: rgba(16,185,129,.16); color: #4ADE80; }
html[data-theme="dark"] .stock-status.stock-badge--low { background: rgba(245,158,11,.16); color: #FBBF24; border-color: rgba(245,158,11,.40); }
html[data-theme="dark"] .stock-status.stock-badge--oos { background: rgba(244,63,94,.18);  color: #F87171; }

/* STOCK BADGE */
.stock-badge {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12.5px; font-weight: 700; border-radius: 20px; padding: 4px 11px;
  font-variant-numeric: tabular-nums;
}
.stock-badge i { font-size: 11px; }
.stock-badge-sub { font-size: 10.5px; font-weight: 600; opacity: .75; }
.stock-badge-status { padding-left: 6px; border-left: 1px solid currentColor; }
.stock-badge--ok  { background: rgba(16,185,129,.12); color: var(--pl-green); }
.stock-badge--low { background: rgba(244,63,94,.10);  color: var(--pl-red); border: 1px solid rgba(244,63,94,.22); }
.stock-badge--oos { background: rgba(244,63,94,.16);  color: var(--pl-red); font-weight: 800; }
html[data-theme="dark"] .stock-badge--ok  { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .stock-badge--low { background: rgba(244,63,94,.14);  color: #F87171; border-color: rgba(244,63,94,.30); }
html[data-theme="dark"] .stock-badge--oos { background: rgba(244,63,94,.22);  color: #F87171; }

/* STOCK CELL — sizes as a compact two-column grid of chips */
.stock-cell { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; min-width: 0; width: 100%; }
.size-stock-list {
  list-style: none; margin: 0; padding: 0;
  display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 5px;
  width: 100%; max-width: 280px;
}
.size-stock-item {
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
  font-size: 11.5px; line-height: 1.3; min-width: 0;
  padding: 3px 4px 3px 9px; border-radius: 8px;
  border: 1px solid var(--pl-border);
  background: var(--pl-surface);
}
.size-stock-label {
  color: var(--pl-text-secondary); font-weight: 600; min-width: 0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.size-stock-qty {
  flex-shrink: 0; min-width: 28px; text-align: center;
  font-size: 11px; font-weight: 700; border-radius: 20px; padding: 1px 8px;
  font-variant-numeric: tabular-nums;
}
.size-stock-item.stock-badge--ok  { background: var(--pl-surface); color: inherit; }
.size-stock-item.stock-badge--ok  .size-stock-qty { background: rgba(16,185,129,.14); color: var(--pl-green); }
.size-stock-item.stock-badge--low { background: rgba(244,63,94,.06); border-color: rgba(244,63,94,.28); color: inherit; }
.size-stock-item.stock-badge--low .size-stock-qty { background: rgba(244,63,94,.14); color: var(--pl-red); }
.size-stock-item.stock-badge--oos { background: rgba(244,63,94,.10); border-color: rgba(244,63,94,.35); color: inherit; }
.size-stock-item.stock-badge--oos .size-stock-qty { background: rgba(244,63,94,.22); color: var(--pl-red); font-weight: 800; }
html[data-theme="dark"] .size-stock-item.stock-badge--ok  .size-stock-qty { color: #4ADE80; }
html[data-theme="dark"] .size-stock-item.stock-badge--low .size-stock-qty,
html[data-theme="dark"] .size-stock-item.stock-badge--oos .size-stock-qty { color: #F87171; }
.size-more {
  background: none; border: none; padding: 0; cursor: pointer;
  font-size: 11.5px; font-weight: 600; font-family: inherit; color: var(--pl-accent);
}
.size-more:hover { text-decoration: underline; }

/* ACTIONS */
.td-actions { text-align: right; }
.action-row { display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
.restock-btn {
  display: inline-flex; align-items: center; gap: 6px;
  height: 32px; padding: 0 12px;
  font-size: 12px; font-weight: 600; font-family: inherit;
  border: 1.5px solid transparent; border-radius: 8px; cursor: pointer;
  background: rgba(16,185,129,.12); color: var(--pl-green);
  transition: background .15s, color .15s, transform .15s;
}
.restock-btn:hover { background: rgba(16,185,129,.22); transform: translateY(-1px); }
.restock-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }
html[data-theme="dark"] .restock-btn { background: rgba(16,185,129,.18); }
/* products that need restocking get a solid button so the next action is obvious */
.restock-btn--urgent { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 2px 8px rgba(16,185,129,.30); }
.restock-btn--urgent:hover { background: linear-gradient(135deg, #10b981, #059669); filter: brightness(1.08); }
html[data-theme="dark"] .restock-btn--urgent { background: linear-gradient(135deg, #10b981, #059669); }

.menu-btn {
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: none; color: var(--pl-text-faint); cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center; font-size: 14px;
  transition: background-color .15s, color .15s;
}
.menu-btn:hover, .menu-btn--active { background: var(--pl-surface-raised); color: var(--pl-text-primary); }
.menu-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }

/* EMPTY */
.td-empty { padding: 56px 24px !important; border-bottom: none !important; text-align: center; vertical-align: middle !important; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--pl-text-faint); }
.empty-state i { font-size: 36px; margin-bottom: 4px; }
.empty-state p { font-size: 14.5px; font-weight: 700; color: var(--pl-text-secondary); margin: 0; }
.empty-sub { font-size: 12.5px; color: var(--pl-text-muted); }
.empty-actions { display: flex; gap: 8px; margin-top: 8px; }

/* MODAL SHARED */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(15, 23, 42, .70);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
html[data-theme="dark"] .modal-backdrop { background: rgba(0, 0, 0, .82); }
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.95) translateY(10px); }

/* RESTOCK MODAL */
.restock-modal {
  width: 100%; max-width: 440px; max-height: calc(100vh - 40px); overflow-y: auto;
  background: #FFFFFF; border: 1px solid #E5E7EB;
  border-radius: 22px; box-shadow: 0 24px 64px rgba(0,0,0,.35);
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
.restock-field--gap { margin-bottom: 16px; }
.restock-label { font-size: 12.5px; font-weight: 600; color: var(--pl-text-secondary); transition: color .22s; }
.req { color: var(--pl-accent); }
.restock-hint  { font-size: 12px; color: var(--pl-text-faint); margin: 0; display: flex; flex-wrap: wrap; align-items: center; gap: 4px; }
.restock-hint strong { color: var(--pl-green); }
.restock-warn { color: var(--pl-amber); font-weight: 600; margin-left: 4px; }

/* size picker (buttons, sorted like the table) */
.size-pick { display: grid; grid-template-columns: repeat(auto-fill, minmax(118px, 1fr)); gap: 8px; max-height: 190px; overflow-y: auto; padding: 2px; }
.size-pick-btn {
  display: flex; flex-direction: column; align-items: flex-start; gap: 2px;
  padding: 8px 11px; border-radius: 10px; cursor: pointer; text-align: left;
  border: 1.5px solid var(--pl-border-strong); background: var(--pl-surface-raised);
  font-family: inherit; color: var(--pl-text-primary);
  transition: border-color .15s, background-color .15s, box-shadow .15s;
}
.size-pick-btn:hover { border-color: var(--pl-accent-border); }
.size-pick-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }
.size-pick-label { font-size: 13px; font-weight: 700; }
.size-pick-qty   { font-size: 11px; font-weight: 600; color: var(--pl-green); }
.size-pick-btn.stock-badge--low .size-pick-qty,
.size-pick-btn.stock-badge--oos .size-pick-qty { color: var(--pl-red); }
.size-pick-btn.stock-badge--low,
.size-pick-btn.stock-badge--oos { background: rgba(244,63,94,.06); border-color: rgba(244,63,94,.28); }
.size-pick-btn--active, .size-pick-btn--active.stock-badge--low, .size-pick-btn--active.stock-badge--oos {
  border-color: var(--pl-accent); background: var(--pl-accent-soft); box-shadow: 0 0 0 3px var(--pl-ring);
}
html[data-theme="dark"] .size-pick-btn.stock-badge--low .size-pick-qty,
html[data-theme="dark"] .size-pick-btn.stock-badge--oos .size-pick-qty { color: #F87171; }
html[data-theme="dark"] .size-pick-qty { color: #4ADE80; }

.qty-row { display: flex; align-items: center; gap: 10px; }
.qty-btn {
  width: 40px; height: 40px; border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface); color: var(--pl-text-secondary); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;
  transition: border-color .15s, background-color .15s, color .15s;
}
.qty-btn:hover { border-color: var(--pl-accent-border); color: var(--pl-accent); background: var(--pl-accent-soft); }
.qty-input {
  flex: 1; height: 40px; text-align: center; min-width: 0;
  border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface-raised); color: var(--pl-text-primary);
  font-size: 18px; font-weight: 700; font-family: inherit; outline: none;
  transition: border-color .18s, background-color .22s, color .22s;
}
.qty-input:focus { border-color: var(--pl-accent); box-shadow: 0 0 0 3px var(--pl-ring); }
.qty-quick { display: flex; gap: 6px; }
.qty-chip {
  flex: 1; height: 30px; border-radius: 8px; cursor: pointer;
  border: 1.5px solid var(--pl-border-strong); background: var(--pl-surface);
  color: var(--pl-text-secondary); font-size: 12px; font-weight: 700; font-family: inherit;
  transition: border-color .15s, background-color .15s, color .15s;
}
.qty-chip:hover { border-color: var(--pl-accent-border); color: var(--pl-accent); background: var(--pl-accent-soft); }

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

@media (prefers-reduced-motion: reduce) {
  .sk, .row-highlight td { animation: none; }
  .modal-fade-enter-active, .modal-fade-leave-active,
  .alert-fade-enter-active, .alert-fade-leave-active { transition: none; }
}

@media (max-width: 700px) {
  .prod-list-page { padding: 16px 16px 48px; }
  .page-header .btn { width: 100%; justify-content: center; }
  .toolbar-row { flex-direction: column; align-items: stretch; }
  .search-wrap { flex-basis: auto; }
  .stock-filter-toggle { width: 100%; }
  .sf-btn { flex: 1; justify-content: center; padding: 0 8px; }
  .select-group { width: 100%; }
  .form-select { width: 100%; }
  .reset-btn { margin-left: 0; justify-content: center; }
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