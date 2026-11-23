<template>
  <Layout>
    <div class="prod-view-page">

      <!-- ── PAGE HEADER (one row: INVENTORY / Product Details) ── -->
      <div class="page-header">
        <div class="page-heading">
          <div class="page-title-row">
            <span class="page-eyebrow">Inventory</span>
            <span class="page-crumb-sep" aria-hidden="true">/</span>
            <h1 class="page-title">Product Details</h1>
          </div>
          <p v-if="!loading && product.ProductName" class="page-sub">
            <strong class="page-sub-name">{{ product.ProductName }}</strong>
            <span class="status-pill" :class="stockClass">
              <span class="status-dot"></span>{{ stockLabel }}
            </span>
            <span class="sku-chip" :class="{ 'sku-chip--empty': !product.SKU }">{{ product.SKU || 'No SKU' }}</span>
          </p>
        </div>
      </div>

      <!-- ── LOADING SKELETON ── -->
      <div v-if="loading" class="skeleton-layout">
        <div class="skeleton-main">
          <div class="skeleton-stats">
            <div v-for="n in 3" :key="n" class="detail-card skeleton-tile">
              <div class="skeleton-line skeleton-line--narrow"></div>
              <div class="skeleton-line skeleton-line--big"></div>
            </div>
          </div>
          <div v-for="n in 2" :key="'m' + n" class="detail-card skeleton-card">
            <div class="skeleton-line skeleton-line--wide"></div>
            <div class="skeleton-line"></div>
            <div class="skeleton-line skeleton-line--narrow"></div>
          </div>
        </div>
        <div class="skeleton-side">
          <div class="detail-card skeleton-card">
            <div class="skeleton-block"></div>
            <div class="skeleton-line skeleton-line--wide"></div>
            <div class="skeleton-line"></div>
          </div>
        </div>
      </div>

      <!-- ── CONTENT ──
           Wide screens: main column (left) + side column (right).
           Narrow screens: both columns dissolve into one list, ordered by the .o-* classes. -->
      <div v-else class="view-layout">

        <!-- ═════════ MAIN COLUMN: stats, sizes, offcuts ═════════ -->
        <div class="col-main">

          <!-- ══ AT A GLANCE ══ -->
          <div class="stat-grid o-stats">
            <div class="stat-tile stat-tile--price">
              <span class="stat-label">{{ variants.length > 1 ? 'Price range' : 'Selling price' }}</span>
              <span class="stat-value stat-value--green">{{ priceLabel }}</span>
              <span class="stat-sub">{{ variants.length || 1 }} size{{ (variants.length || 1) === 1 ? '' : 's' }}</span>
            </div>
            <div class="stat-tile">
              <span class="stat-label">Current stock</span>
              <span class="stat-value" :class="stockCountClass">{{ totalStock }} <small>units</small></span>
              <span class="stat-sub">
                <span v-if="isLowStock" class="low-stock-badge"><i class="fa-solid fa-triangle-exclamation"></i> Low stock</span>
                <template v-else>{{ stockLabel }}</template>
              </span>
            </div>
            <div class="stat-tile">
              <span class="stat-label">Reorder level</span>
              <span class="stat-value">{{ product.inventory?.ReorderLevel ?? 0 }} <small>units</small></span>
              <span class="stat-sub">Alert at or below this</span>
            </div>
            <div v-if="offcutPieces > 0" class="stat-tile">
              <span class="stat-label">Offcuts on hand</span>
              <span class="stat-value stat-value--amber">{{ offcutPieces }} <small>piece{{ offcutPieces > 1 ? 's' : '' }}</small></span>
              <span class="stat-sub">Tracked separately</span>
            </div>
          </div>

          <!-- ══ SIZES, PRICES & STOCK ══ -->
          <div class="detail-card o-sizes">
            <div class="detail-card-header">
              <div class="detail-card-icon detail-card-icon--green">
                <i class="fa-solid fa-tags"></i>
              </div>
              <div class="detail-card-heading">
                <h2 class="detail-card-title">Sizes, Prices &amp; Stock</h2>
                <p class="detail-card-sub">Every size with its price, margin and stock.</p>
              </div>
              <span class="count-pill">{{ variants.length }} size{{ variants.length === 1 ? '' : 's' }}</span>
            </div>

            <div class="size-table-wrap size-table-wrap--sizes">
              <table class="size-table">
                <thead>
                  <tr>
                    <th>Size</th>
                    <th class="num">Selling price</th>
                    <th class="num">Cost price</th>
                    <th class="num">Margin</th>
                    <th class="num">Stock</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="v in variants" :key="v.VariantID">
                    <td class="size-name">{{ v.Label }}</td>
                    <td class="num">₱{{ formatPrice(v.SellingPrice) }}</td>
                    <td class="num">₱{{ formatPrice(v.CostPrice) }}</td>
                    <td class="num">
                      <span class="margin-pill" :class="{ 'margin-pill--loss': Number(margin(v)) < 0 }">{{ margin(v) }}%</span>
                    </td>
                    <td class="num">
                      <span v-if="v.Stock !== undefined && v.Stock !== null" class="stock-chip" :class="variantStockClass(v)">
                        {{ v.Stock }}
                      </span>
                      <span v-else class="size-dash">—</span>
                    </td>
                  </tr>
                  <tr v-if="!variants.length">
                    <td colspan="5" class="size-empty">No sizes set for this product.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ══ CUT OFFCUTS ON HAND ══ -->
          <div class="detail-card o-offcuts" v-if="remnants.length">
            <div class="detail-card-header">
              <div class="detail-card-icon detail-card-icon--amber">
                <i class="fa-solid fa-scissors"></i>
              </div>
              <div class="detail-card-heading">
                <h2 class="detail-card-title">Cut Offcuts on Hand</h2>
                <p class="detail-card-sub">Leftover lengths from cut pieces. Tracked separately from whole-piece stock.</p>
              </div>
              <span class="count-pill count-pill--amber">{{ offcutPieces }} piece{{ offcutPieces > 1 ? 's' : '' }}</span>
            </div>

            <div class="size-table-wrap size-table-wrap--offcuts">
              <table class="size-table">
                <thead>
                  <tr>
                    <th>Size</th>
                    <th class="num">Length each</th>
                    <th class="num">Pieces</th>
                    <th>From receipt</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in remnants" :key="r.RemnantID">
                    <td class="size-name">{{ r.VariantLabel || '—' }}</td>
                    <td class="num">{{ Number(r.Length) }} {{ r.Unit }}</td>
                    <td class="num">{{ r.Quantity }}</td>
                    <td class="mono">SR-{{ String(r.SaleID).padStart(5, '0') }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ═════════ SIDE COLUMN: product card, details, suppliers ═════════ -->
        <div class="col-side">

          <!-- ══ PRODUCT CARD (thumbnail + codes + quick facts) ══ -->
          <div class="detail-card o-product">
            <div class="product-top">
              <div class="product-image-wrap">
                <img
                  v-if="product.Product_Image"
                  :src="imageUrl(product.Product_Image)"
                  :alt="product.ProductName"
                  class="product-image"
                />
                <div v-else class="product-image-placeholder">
                  <i class="fa-solid fa-cube"></i>
                  <span>No image</span>
                </div>
              </div>

              <div class="product-codes">
                <div class="code-block">
                  <span class="identity-label">SKU</span>
                  <span class="identity-val identity-val--mono">
                    <span class="code-text">{{ product.SKU || 'N/A' }}</span>
                    <button v-if="product.SKU" type="button" class="copy-btn" :class="{ 'copy-btn--done': copied === 'sku' }" title="Copy SKU" @click="copyText(product.SKU, 'sku')">
                      <i class="fa-solid" :class="copied === 'sku' ? 'fa-check' : 'fa-copy'"></i>
                    </button>
                  </span>
                </div>
                <div class="code-block">
                  <span class="identity-label">Barcode</span>
                  <span class="identity-val identity-val--mono">
                    <span class="code-text">{{ product.Barcode || '—' }}</span>
                    <button v-if="product.Barcode" type="button" class="copy-btn" :class="{ 'copy-btn--done': copied === 'barcode' }" title="Copy barcode" @click="copyText(product.Barcode, 'barcode')">
                      <i class="fa-solid" :class="copied === 'barcode' ? 'fa-check' : 'fa-copy'"></i>
                    </button>
                  </span>
                </div>
              </div>
            </div>

            <div class="fact-grid">
              <div class="fact-tile">
                <span class="identity-label">Category</span>
                <span class="fact-val"><span class="category-pill">{{ product.category?.CategoryName || 'N/A' }}</span></span>
              </div>
              <div class="fact-tile">
                <span class="identity-label">Brand</span>
                <span class="fact-val">{{ product.Brand || '—' }}</span>
              </div>
              <div class="fact-tile">
                <span class="identity-label">Unit</span>
                <span class="fact-val">{{ product.Unit || '—' }}</span>
              </div>
              <div class="fact-tile">
                <span class="identity-label">Returnable</span>
                <span class="fact-val">
                  <span class="returnable-pill" :class="product.IsReturnable ? 'returnable-pill--yes' : 'returnable-pill--no'">
                    <i :class="product.IsReturnable ? 'fa-solid fa-rotate-left' : 'fa-solid fa-ban'"></i>
                    {{ product.IsReturnable ? 'Yes' : 'No' }}
                  </span>
                </span>
              </div>
            </div>
          </div>

          <!-- ══ PRODUCT DETAILS ══ -->
          <div class="detail-card o-details">
            <div class="detail-card-header">
              <div class="detail-card-icon detail-card-icon--blue">
                <i class="fa-solid fa-circle-info"></i>
              </div>
              <div class="detail-card-heading">
                <h2 class="detail-card-title">Product Details</h2>
                <p class="detail-card-sub">Description, material and finish.</p>
              </div>
            </div>
            <div class="detail-card-body">
              <p class="desc-text" :class="{ 'desc-text--empty': !product.Description }">
                {{ product.Description || 'No description available.' }}
              </p>

              <div v-if="specTiles.length" class="attr-grid">
                <div v-for="a in specTiles" :key="a.label" class="attr-tile">
                  <span class="attr-label">{{ a.label }}</span>
                  <span class="attr-val">{{ a.value }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- ══ SUPPLIERS ══ -->
          <div class="detail-card o-suppliers">
            <div class="detail-card-header">
              <div class="detail-card-icon detail-card-icon--violet">
                <i class="fa-solid fa-truck-field"></i>
              </div>
              <div class="detail-card-heading">
                <h2 class="detail-card-title">Supplier Information</h2>
                <p class="detail-card-sub">Who this product is bought from.</p>
              </div>
              <span v-if="product.suppliers?.length" class="count-pill count-pill--violet">{{ product.suppliers.length }}</span>
            </div>

            <div v-if="product.suppliers?.length" class="supplier-grid">
              <div v-for="s in product.suppliers" :key="s.SupplierID" class="supplier-card">
                <div class="supplier-avatar">{{ s.SupplierName.charAt(0).toUpperCase() }}</div>
                <div class="supplier-info">
                  <p class="supplier-name">{{ s.SupplierName }}</p>
                  <div class="supplier-meta">
                    <span v-if="s.ContactNumber" class="supplier-meta-row">
                      <i class="fa-solid fa-phone"></i> {{ s.ContactNumber }}
                    </span>
                    <span v-if="s.Email" class="supplier-meta-row">
                      <i class="fa-regular fa-envelope"></i> {{ s.Email }}
                    </span>
                    <span v-if="s.Address" class="supplier-meta-row">
                      <i class="fa-solid fa-location-dot"></i> {{ s.Address }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="no-suppliers">
              <i class="fa-solid fa-truck-field"></i>
              <p>No suppliers assigned to this product.</p>
            </div>
          </div>
        </div>

      </div>

      <!-- ══ FLOATING ACTION BAR (pinned to the bottom of the screen while scrolling) ══ -->
      <div class="action-bar">
        <div class="action-bar-status">
          <i class="fa-solid fa-cube action-bar-icon"></i>
          <div class="action-bar-text">
            <strong>{{ loading ? 'Loading product…' : (product.ProductName || 'Product') }}</strong>
            <span v-if="!loading" class="action-bar-meta">{{ variants.length }} size{{ variants.length === 1 ? '' : 's' }} · {{ totalStock }} units in stock</span>
          </div>
        </div>
        <div class="action-bar-actions">
          <RouterLink to="/products" class="btn btn--ghost" title="Back to the product list">
            <i class="fa-solid fa-arrow-left"></i> <span class="btn-text">Back to Products</span>
          </RouterLink>
          <RouterLink v-if="!loading && product.ProductID" :to="`/products/${product.ProductID}/edit`" class="btn btn--primary">
            <i class="fa-solid fa-pen-to-square"></i> Edit Product
          </RouterLink>
        </div>
      </div>

    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/axios'
import Layout from '@/components/Layout.vue'

const route   = useRoute()
const product = ref({})
const loading = ref(true)

const formatPrice = val => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const imageUrl = path => {
  if (!path) return ''
  const cleanPath = path.replace(/^\/+/, '')

  return import.meta.env.DEV
    ? `http://localhost:8000/storage/${cleanPath}`
    : `/storage/${cleanPath}`
}

/* ── COPY TO CLIPBOARD (SKU / barcode) ── */
const copied = ref('')
let copiedTimer = null
async function copyText(text, key) {
  try {
    await navigator.clipboard.writeText(String(text))
    copied.value = key
    clearTimeout(copiedTimer)
    copiedTimer = setTimeout(() => { copied.value = '' }, 1500)
  } catch (e) {
    console.error('copy failed:', e)
  }
}

/* ── SIZES & PRICES ── */
const variants = computed(() => product.value.variants ?? [])

/* ── OFFCUTS (leftover lengths from cut pieces sold at the POS) ── */
const remnants     = computed(() => product.value.remnants ?? [])
const offcutPieces = computed(() => remnants.value.reduce((s, r) => s + Number(r.Quantity || 0), 0))

const priceLabel = computed(() => {
  const prices = variants.value.map(v => Number(v.SellingPrice))
  if (!prices.length) return `₱${formatPrice(product.value.SellingPrice)}`
  const min = Math.min(...prices)
  const max = Math.max(...prices)
  return min === max
    ? `₱${formatPrice(min)}`
    : `₱${formatPrice(min)} – ₱${formatPrice(max)}`
})

const margin = v =>
  v.SellingPrice > 0
    ? (((v.SellingPrice - v.CostPrice) / v.SellingPrice) * 100).toFixed(1)
    : '0.0'

/* Optional detail attributes shown as small tiles (only the ones that have a value). */
const specTiles = computed(() => {
  const p = product.value
  const tiles = []
  if (p.Material)    tiles.push({ label: 'Material',      value: p.Material })
  if (p.ProfileType) tiles.push({ label: 'Profile / Type', value: p.ProfileType })
  if (p.Color)       tiles.push({ label: 'Color',         value: p.Color })
  if (p.Weight)      tiles.push({ label: 'Weight',        value: `${p.Weight} ${p.WeightUnit || ''}`.trim() })
  return tiles
})

const totalStock = computed(() => product.value.inventory?.QuantityOnHand ?? 0)

const isLowStock = computed(() => {
  const inv = product.value.inventory
  return !!inv && inv.QuantityOnHand > 0 && inv.QuantityOnHand <= inv.ReorderLevel
})

const stockClass = computed(() => {
  const qty = product.value.inventory?.QuantityOnHand ?? 0
  if (qty <= 0) return 'status-pill--oos'
  if (isLowStock.value) return 'status-pill--low'
  return 'status-pill--ok'
})

const stockLabel = computed(() => {
  const qty = product.value.inventory?.QuantityOnHand ?? 0
  if (qty <= 0) return 'Out of Stock'
  if (isLowStock.value) return 'Low Stock'
  return 'In Stock'
})

const stockCountClass = computed(() => {
  const qty = product.value.inventory?.QuantityOnHand ?? 0
  if (qty <= 0) return 'stat-value--red'
  if (isLowStock.value) return 'stat-value--amber'
  return 'stat-value--green'
})

/** Per-size stock colour: out of stock / at or below its reorder level / healthy. */
function variantStockClass(v) {
  const s = Number(v.Stock) || 0
  const t = Number(v.ReorderLevel) > 0 ? Number(v.ReorderLevel) : 0
  if (s <= 0) return 'stock-chip--oos'
  if (t > 0 && s <= t) return 'stock-chip--low'
  return 'stock-chip--ok'
}

async function loadProduct() {
  try {
    loading.value = true
    const res = await api.get(`/api/products/${route.params.id}`)
    product.value = res.data
  } catch (err) {
    console.error('Failed to load product:', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadProduct)
onBeforeUnmount(() => clearTimeout(copiedTimer))
</script>

<style scoped>
/* ── TOKEN BRIDGE & PAGE SHELL ── */
.prod-view-page {
  --pv-bg:             var(--c-bg);
  --pv-surface:        var(--c-surface);
  --pv-surface-raised: var(--c-surface-raised);
  --pv-surface-sunken: var(--c-surface-sunken);
  --pv-border:         var(--c-border);
  --pv-border-strong:  var(--c-border-strong);
  --pv-text-primary:   var(--c-text-primary);
  --pv-text-secondary: var(--c-text-secondary);
  --pv-text-muted:     var(--c-text-muted);
  --pv-text-faint:     var(--c-text-faint);
  --pv-accent:         var(--c-accent);
  --pv-accent-soft:    var(--c-accent-soft);
  --pv-accent-border:  var(--c-accent-border);
  --pv-ring:           var(--c-accent-ring);
  --pv-green:          #10b981;
  --pv-red:            #f43f5e;
  --pv-blue:           #3b82f6;
  --pv-violet:         #6366f1;
  --pv-amber:          #f59e0b;
  --radius: 16px;

  /* ── SPACE TUNING (same values as the Add / Edit / List pages) ──
     --bleed-*: reclaims part of the app shell's padding on desktop.
     Lower a value (or set it to 0px) if content ever touches the sidebar or top bar. */
  --bleed-x: 30px;
  --bleed-y: 30px;
  --pv-gap: 14px;

  min-height: 100%;
  overflow-x: clip;             /* the page never scrolls sideways (clip, so sticky keeps working) */
  background: var(--pv-bg);
  padding: 8px 24px 16px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--pv-gap);
  transition: background-color .22s ease;
}
.prod-view-page > * { min-width: 0; }

/* Desktop only: pull the page outward into the shell's padding (top, left AND right). */
@media (min-width: 1001px) {
  .prod-view-page { margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) 0; }
}

/* ── PAGE HEADER ── */
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-heading { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.page-title-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: 4px 10px; min-width: 0; }
.page-eyebrow { font-size: 16px; font-weight: 700; letter-spacing: .12em; color: var(--pv-accent); text-transform: uppercase; line-height: 1.2; margin: 0; }
.page-crumb-sep { font-size: 16px; font-weight: 300; color: var(--pv-text-faint); line-height: 1.2; }
.page-title { font-size: 16px; font-weight: 800; color: var(--pv-text-primary); letter-spacing: -.02em; line-height: 1.2; margin: 0; transition: color .22s; }
.page-sub   { margin: 2px 0 0; font-size: 13px; color: var(--pv-text-muted); display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
.page-sub-name { font-size: 13.5px; font-weight: 700; color: var(--pv-text-primary); overflow-wrap: anywhere; }
.sku-chip {
  font-size: 11.5px; font-weight: 700; padding: 2px 10px; border-radius: 20px;
  background: var(--pv-surface-raised); border: 1px solid var(--pv-border); color: var(--pv-text-secondary);
  font-variant-numeric: tabular-nums;
}
.sku-chip--empty { color: #d97706; border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.10); }

.status-pill {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 700; letter-spacing: .03em;
  border-radius: 20px; padding: 2px 10px; white-space: nowrap;
}
.status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
.status-pill--ok  { background: rgba(16,185,129,.12); color: #059669; }
.status-pill--low { background: rgba(245,158,11,.14); color: #d97706; }
.status-pill--oos { background: rgba(244,63,94,.12);  color: #e11d48; }
html[data-theme="dark"] .status-pill--ok  { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .status-pill--low { background: rgba(245,158,11,.18); color: #FCD34D; }
html[data-theme="dark"] .status-pill--oos { background: rgba(244,63,94,.16);  color: #F87171; }

/* ── BUTTONS ── */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pv-ring); }
.btn--ghost   { background: var(--pv-surface); border-color: var(--pv-border-strong); color: var(--pv-text-secondary); }
.btn--ghost:hover { background: var(--pv-surface-raised); border-color: var(--pv-accent-border); color: var(--pv-text-primary); }
.btn--primary {
  background: linear-gradient(135deg, var(--pv-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }

/* ── FLOATING ACTION BAR ──
   Sticks to the bottom of the screen while you scroll (same behaviour as the Edit page's
   save bar). margin-top:auto keeps it at the very bottom even when the page is short. */
.action-bar {
  position: sticky; bottom: 14px; z-index: 30; order: 9;
  margin-top: auto;
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  padding: 10px 14px 10px 18px; border-radius: 16px;
  background: var(--pv-surface); border: 1px solid var(--pv-border-strong);
  box-shadow: 0 12px 34px rgba(0,0,0,.18), 0 2px 8px rgba(0,0,0,.08);
}
.action-bar-status { display: flex; align-items: center; gap: 12px; min-width: 0; }
.action-bar-icon {
  width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 14px;
  background: var(--pv-accent-soft); color: var(--pv-accent);
}
.action-bar-text { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.action-bar-text strong { font-size: 13.5px; font-weight: 700; color: var(--pv-text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.action-bar-meta { font-size: 12px; color: var(--pv-text-muted); }
.action-bar-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

/* ── CONTENT LAYOUT ──
   Narrow screens: one column, cards ordered by the .o-* classes (the two column
   wrappers dissolve with display: contents).
   Wide screens: main column on the left, side column on the right. */
.view-layout { display: flex; flex-direction: column; gap: var(--pv-gap); min-width: 0; }
.col-main, .col-side { display: contents; }
.o-stats { order: 1; } .o-product { order: 2; } .o-details { order: 3; }
.o-sizes { order: 4; } .o-offcuts { order: 5; } .o-suppliers { order: 6; }

@media (min-width: 1100px) {
  .view-layout {
    display: grid; grid-template-columns: minmax(0, 1fr) clamp(300px, 28vw, 360px);
    gap: var(--pv-gap) 20px; align-items: start;
  }
  .col-main, .col-side {
    display: flex; flex-direction: column; gap: var(--pv-gap); min-width: 0; grid-row: 1;
  }
  .col-main { grid-column: 1; }
  .col-side { grid-column: 2; }
  .o-stats, .o-product, .o-details, .o-sizes, .o-offcuts, .o-suppliers { order: 0; }
}

/* ── SKELETON ── */
.skeleton-layout { display: flex; flex-direction: column; gap: var(--pv-gap); }
.skeleton-main, .skeleton-side { display: flex; flex-direction: column; gap: var(--pv-gap); min-width: 0; }
@media (min-width: 1100px) {
  .skeleton-layout { display: grid; grid-template-columns: minmax(0, 1fr) clamp(300px, 28vw, 360px); gap: 20px; align-items: start; }
}
.skeleton-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; }
.skeleton-tile  { padding: 16px; display: flex; flex-direction: column; gap: 12px; }
.skeleton-card  { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
.skeleton-block { height: 120px; border-radius: 12px; background: var(--pv-border); animation: shimmer 1.4s infinite; }
.skeleton-line  { height: 13px; border-radius: 6px; background: var(--pv-border); animation: shimmer 1.4s infinite; width: 80%; }
.skeleton-line--wide   { width: 100%; }
.skeleton-line--narrow { width: 50%; }
.skeleton-line--big    { height: 22px; width: 70%; }
@keyframes shimmer { 0%,100%{opacity:.6} 50%{opacity:.3} }

/* ── DETAIL CARDS ── */
.detail-card {
  background: var(--pv-surface); border: 1px solid var(--pv-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.detail-card-header {
  display: flex; align-items: center; gap: 14px;
  padding: 14px 22px 12px; border-bottom: 1px solid var(--pv-border);
  transition: border-color .22s;
}
.detail-card-heading { min-width: 0; }
.detail-card-icon {
  width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.detail-card-icon--blue   { background: rgba(59,130,246,.12); color: var(--pv-blue);   }
.detail-card-icon--green  { background: rgba(16,185,129,.12); color: var(--pv-green);  }
.detail-card-icon--amber  { background: rgba(245,158,11,.12); color: var(--pv-amber);  }
.detail-card-icon--violet { background: rgba(99,102,241,.12); color: var(--pv-violet); }
html[data-theme="dark"] .detail-card-icon--blue   { background: rgba(59,130,246,.18); }
html[data-theme="dark"] .detail-card-icon--green  { background: rgba(16,185,129,.18); }
html[data-theme="dark"] .detail-card-icon--amber  { background: rgba(245,158,11,.18); }
html[data-theme="dark"] .detail-card-icon--violet { background: rgba(99,102,241,.18); }
.detail-card-title { font-size: 15px; font-weight: 700; color: var(--pv-text-primary); margin: 0 0 2px; transition: color .22s; }
.detail-card-sub   { font-size: 12.5px; color: var(--pv-text-muted); margin: 0; }
.detail-card-body  { padding: 16px 22px 18px; display: flex; flex-direction: column; gap: 14px; }
.count-pill {
  margin-left: auto; flex-shrink: 0; font-size: 11.5px; font-weight: 700;
  padding: 4px 12px; border-radius: 20px; background: var(--pv-accent-soft); color: var(--pv-accent);
}
.count-pill--amber  { background: rgba(245,158,11,.14); color: #d97706; }
.count-pill--violet { background: rgba(99,102,241,.12); color: var(--pv-violet); }
html[data-theme="dark"] .count-pill--amber  { color: #FCD34D; }
html[data-theme="dark"] .count-pill--violet { background: rgba(99,102,241,.20); color: #A5B4FC; }

/* ── AT A GLANCE ── */
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; }
.stat-tile {
  display: flex; flex-direction: column; gap: 4px; padding: 14px 18px; min-width: 0;
  background: var(--pv-surface); border: 1px solid var(--pv-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm);
  transition: background-color .22s, border-color .22s;
}
.stat-tile--price {
  background: linear-gradient(rgba(16,185,129,.07), rgba(16,185,129,.07)), var(--pv-surface);
  border-color: rgba(16,185,129,.28);
}
.stat-label { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--pv-text-muted); }
.stat-value {
  font-size: 22px; font-weight: 800; color: var(--pv-text-primary); letter-spacing: -.03em;
  font-variant-numeric: tabular-nums; overflow-wrap: anywhere; line-height: 1.15;
}
.stat-value small { font-size: 12px; font-weight: 600; color: var(--pv-text-faint); letter-spacing: 0; }
.stat-value--green { color: var(--pv-green); }
.stat-value--amber { color: var(--pv-amber); }
.stat-value--red   { color: var(--pv-red); }
html[data-theme="dark"] .stat-value--green { color: #4ADE80; }
html[data-theme="dark"] .stat-value--amber { color: #FCD34D; }
html[data-theme="dark"] .stat-value--red   { color: #F87171; }
.stat-sub { font-size: 12px; color: var(--pv-text-faint); display: flex; align-items: center; min-height: 18px; }
.low-stock-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 700;
  background: rgba(245,158,11,.14); color: #d97706;
  border-radius: 20px; padding: 2px 8px;
}
html[data-theme="dark"] .low-stock-badge { background: rgba(245,158,11,.18); color: #FCD34D; }

/* ── PRODUCT CARD (thumbnail + codes + quick facts) ── */
.product-top { display: flex; align-items: center; gap: 14px; padding: 14px 16px 10px; }
.product-image-wrap {
  position: relative; width: 92px; height: 92px; flex-shrink: 0; overflow: hidden;
  border-radius: 12px; background: var(--pv-surface-sunken); border: 1px solid var(--pv-border);
  transition: background-color .22s;
}
.product-image { width: 100%; height: 100%; object-fit: cover; display: block; }
.product-image-placeholder {
  width: 100%; height: 100%; display: flex; flex-direction: column; gap: 4px;
  align-items: center; justify-content: center;
  font-size: 24px; color: var(--pv-text-faint);
}
.product-image-placeholder span { font-size: 10.5px; font-weight: 600; }

.product-codes { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 10px; }
.code-block { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.identity-label { font-size: 10.5px; font-weight: 700; color: var(--pv-text-faint); text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
.identity-val   { font-size: 13px; font-weight: 600; color: var(--pv-text-primary); display: inline-flex; align-items: center; gap: 6px; min-width: 0; }
.identity-val--mono { font-variant-numeric: tabular-nums; font-family: 'SF Mono','Fira Code',monospace; font-size: 12px; }
.code-text { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }

.copy-btn {
  width: 22px; height: 22px; flex-shrink: 0; border: none; border-radius: 7px; cursor: pointer;
  background: transparent; color: var(--pv-text-faint); font-size: 11px;
  display: inline-flex; align-items: center; justify-content: center;
  transition: background-color .15s, color .15s;
}
.copy-btn:hover { background: var(--pv-accent-soft); color: var(--pv-accent); }
.copy-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pv-ring); }
.copy-btn--done, .copy-btn--done:hover { background: rgba(16,185,129,.14); color: var(--pv-green); }

.fact-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; padding: 0 16px 14px; }
.fact-tile {
  display: flex; flex-direction: column; gap: 4px; padding: 8px 12px; min-width: 0;
  background: var(--pv-surface-sunken); border: 1px solid var(--pv-border); border-radius: 10px;
}
.fact-val { font-size: 13px; font-weight: 600; color: var(--pv-text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.category-pill {
  display: inline-flex; align-items: center; max-width: 100%;
  background: rgba(99,102,241,.12); color: var(--pv-violet);
  font-size: 11.5px; font-weight: 600; border-radius: 20px; padding: 1px 9px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  transition: background .22s;
}
html[data-theme="dark"] .category-pill { background: rgba(99,102,241,.20); color: #A5B4FC; }

.returnable-pill {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11.5px; font-weight: 600; border-radius: 20px; padding: 1px 9px;
}
.returnable-pill--yes { background: rgba(16,185,129,.12); color: var(--pv-green); }
.returnable-pill--no  { background: rgba(244,63,94,.10);  color: var(--pv-red);  }
html[data-theme="dark"] .returnable-pill--yes { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .returnable-pill--no  { background: rgba(244,63,94,.16);  color: #F87171; }

/* ── PRODUCT DETAILS ── */
.desc-text { margin: 0; font-size: 13.5px; line-height: 1.7; color: var(--pv-text-secondary); white-space: pre-line; }
.desc-text--empty { color: var(--pv-text-faint); font-style: italic; }
.attr-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; padding-top: 14px; border-top: 1px dashed var(--pv-border-strong); }
.attr-tile {
  display: flex; flex-direction: column; gap: 3px; padding: 8px 12px; min-width: 0;
  background: var(--pv-surface-sunken); border: 1px solid var(--pv-border); border-radius: 12px;
}
.attr-label { font-size: 10.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--pv-text-muted); }
.attr-val   { font-size: 13.5px; font-weight: 600; color: var(--pv-text-primary); overflow-wrap: anywhere; }

/* ── SIZES / OFFCUTS TABLES ──
   The card grows with its rows; after about 6 rows the table scrolls inside the card
   and the header row stays pinned. */
.size-table-wrap { overflow: auto; padding: 4px 10px 12px; scrollbar-width: thin; }
.size-table-wrap--sizes   { max-height: 400px; }
.size-table-wrap--offcuts { max-height: 260px; }
.size-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.size-table th {
  text-align: left; padding: 10px 12px;
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  color: var(--pv-text-faint); border-bottom: 1px solid var(--pv-border);
  white-space: nowrap;
  position: sticky; top: 0; z-index: 1; background: var(--pv-surface);
}
.size-table td {
  padding: 11px 12px; color: var(--pv-text-primary);
  border-bottom: 1px solid var(--pv-border);
  font-variant-numeric: tabular-nums;
  transition: border-color .22s;
}
.size-table .num { text-align: right; }
.size-table th.num { text-align: right; }
.size-table tbody tr:last-child td { border-bottom: none; }
.size-table tbody tr:hover td { background: var(--pv-surface-raised); }
.size-name { font-weight: 700; }
.size-empty { text-align: center; color: var(--pv-text-faint); }
.size-dash { color: var(--pv-text-faint); }
.mono { font-family: 'SF Mono','Fira Code',monospace; font-size: 12.5px; }

.margin-pill {
  display: inline-block; padding: 2px 9px; border-radius: 20px;
  font-size: 11.5px; font-weight: 700;
  background: rgba(16,185,129,.12); color: var(--pv-green);
}
.margin-pill--loss { background: rgba(244,63,94,.12); color: var(--pv-red); }
html[data-theme="dark"] .margin-pill { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .margin-pill--loss { background: rgba(244,63,94,.16); color: #F87171; }

.stock-chip {
  display: inline-block; min-width: 34px; text-align: center;
  padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 800;
}
.stock-chip--ok  { background: rgba(16,185,129,.12); color: #059669; }
.stock-chip--low { background: rgba(245,158,11,.14); color: #d97706; }
.stock-chip--oos { background: rgba(244,63,94,.12);  color: #e11d48; }
html[data-theme="dark"] .stock-chip--ok  { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .stock-chip--low { background: rgba(245,158,11,.18); color: #FCD34D; }
html[data-theme="dark"] .stock-chip--oos { background: rgba(244,63,94,.16);  color: #F87171; }

/* ── SUPPLIERS (list scrolls inside the card when there are many) ── */
.supplier-grid { padding: 14px 16px; display: flex; flex-direction: column; gap: 10px; max-height: 340px; overflow-y: auto; scrollbar-width: thin; }
.supplier-card {
  display: flex; align-items: flex-start; gap: 12px;
  background: var(--pv-surface-sunken); border: 1px solid var(--pv-border);
  border-radius: 12px; padding: 12px;
  transition: background-color .22s, border-color .22s;
}
.supplier-card:hover { border-color: var(--pv-accent-border); }
.supplier-avatar {
  width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
  background: var(--pv-accent-soft); color: var(--pv-accent);
  border: 1px solid var(--pv-accent-border);
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 800;
  transition: background-color .22s, border-color .22s;
}
.supplier-info  { flex: 1; min-width: 0; }
.supplier-name  { font-size: 13.5px; font-weight: 700; color: var(--pv-text-primary); margin: 0 0 6px; transition: color .22s; overflow-wrap: anywhere; }
.supplier-meta  { display: flex; flex-direction: column; gap: 3px; }
.supplier-meta-row {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--pv-text-muted); overflow-wrap: anywhere;
}
.supplier-meta-row i { font-size: 10px; color: var(--pv-text-faint); flex-shrink: 0; }

.no-suppliers {
  padding: 24px 20px; display: flex; flex-direction: column;
  align-items: center; gap: 10px; text-align: center; color: var(--pv-text-faint);
}
.no-suppliers i { font-size: 26px; }
.no-suppliers p { font-size: 13.5px; color: var(--pv-text-muted); margin: 0; }

/* ── RESPONSIVE ── */
@media (max-width: 1099px) and (min-width: 801px) {
  .supplier-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
  .attr-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
@media (max-width: 900px) {
  .prod-view-page { --pv-gap: 12px; }
}
@media (max-width: 800px) {
  .prod-view-page { padding: 8px 16px 16px; }
  .page-eyebrow, .page-title, .page-crumb-sep { font-size: 14px; }
  .detail-card-header { flex-wrap: wrap; padding: 14px 16px 12px; }
  .detail-card-body { padding: 16px; }
  .count-pill { margin-left: 0; }
  .action-bar { bottom: 10px; padding: 10px; border-radius: 14px; }
  .action-bar-status { display: none; }
  .action-bar-actions { width: 100%; }
  .action-bar-actions .btn { flex: 1; justify-content: center; }
}
@media (prefers-reduced-motion: reduce) {
  .skeleton-block, .skeleton-line { animation: none; }
}
</style>