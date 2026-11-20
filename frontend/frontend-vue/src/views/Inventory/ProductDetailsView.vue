<template>
  <Layout>
    <div class="prod-view-page">

      <!-- ── COMPACT PAGE HEADER (one row: back · title · edit) ── -->
      <div class="page-header">
        <div class="header-main">
          <RouterLink to="/products" class="btn btn--back" title="Back to the product list">
            <i class="fa-solid fa-arrow-left"></i> Back to Products
          </RouterLink>
          <div class="title-block">
            <p class="page-eyebrow">Inventory</p>
            <div class="title-row">
              <h1 class="page-title">{{ product.ProductName || 'Product Details' }}</h1>
              <span v-if="!loading" class="status-pill" :class="stockClass">
                <span class="status-dot"></span>{{ stockLabel }}
              </span>
              <span v-if="!loading && product.ProductName" class="sku-chip" :class="{ 'sku-chip--empty': !product.SKU }">
                {{ product.SKU || 'No SKU' }}
              </span>
            </div>
          </div>
        </div>
        <div class="header-actions" v-if="!loading">
          <RouterLink :to="`/products/${product.ProductID}/edit`" class="btn btn--primary">
            <i class="fa-solid fa-pen-to-square"></i> Edit Product
          </RouterLink>
        </div>
      </div>

      <!-- ── LOADING SKELETON (same three columns as the page) ── -->
      <div v-if="loading" class="view-layout">
        <div class="col col-left">
          <div class="detail-card skeleton-card skeleton-card--fill">
            <div class="skeleton-block"></div>
            <div class="skeleton-line skeleton-line--wide"></div>
            <div class="skeleton-line"></div>
            <div class="skeleton-line skeleton-line--narrow"></div>
          </div>
        </div>
        <div class="col col-main">
          <div class="skeleton-stats">
            <div v-for="n in 4" :key="n" class="detail-card skeleton-tile">
              <div class="skeleton-line skeleton-line--narrow"></div>
              <div class="skeleton-line skeleton-line--big"></div>
            </div>
          </div>
          <div class="detail-card skeleton-card skeleton-card--fill">
            <div class="skeleton-line skeleton-line--wide"></div>
            <div class="skeleton-line"></div>
            <div class="skeleton-line skeleton-line--narrow"></div>
          </div>
        </div>
        <div class="col col-side">
          <div class="detail-card skeleton-card skeleton-card--fill">
            <div class="skeleton-line skeleton-line--wide"></div>
            <div class="skeleton-line"></div>
          </div>
        </div>
      </div>

      <!-- ── CONTENT ──
           Wide screens: three columns that fill the screen height, so nothing scrolls.
           Narrow / very short screens: one normal scrolling column. -->
      <div v-else class="view-layout">

        <!-- ══ COLUMN 1: IMAGE + IDENTITY ══ -->
        <div class="col col-left">
          <div class="detail-card detail-card--image">
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

            <div class="identity-list">
              <div class="identity-row">
                <span class="identity-label">SKU</span>
                <span class="identity-val identity-val--mono">
                  {{ product.SKU || 'N/A' }}
                  <button v-if="product.SKU" type="button" class="copy-btn" :class="{ 'copy-btn--done': copied === 'sku' }" title="Copy SKU" @click="copyText(product.SKU, 'sku')">
                    <i class="fa-solid" :class="copied === 'sku' ? 'fa-check' : 'fa-copy'"></i>
                  </button>
                </span>
              </div>
              <div class="identity-row">
                <span class="identity-label">Barcode</span>
                <span class="identity-val identity-val--mono">
                  {{ product.Barcode || '—' }}
                  <button v-if="product.Barcode" type="button" class="copy-btn" :class="{ 'copy-btn--done': copied === 'barcode' }" title="Copy barcode" @click="copyText(product.Barcode, 'barcode')">
                    <i class="fa-solid" :class="copied === 'barcode' ? 'fa-check' : 'fa-copy'"></i>
                  </button>
                </span>
              </div>
              <div class="identity-row">
                <span class="identity-label">Category</span>
                <span class="identity-val">
                  <span class="category-pill">{{ product.category?.CategoryName || 'N/A' }}</span>
                </span>
              </div>
              <div class="identity-row">
                <span class="identity-label">Brand</span>
                <span class="identity-val">{{ product.Brand || '—' }}</span>
              </div>
              <div class="identity-row">
                <span class="identity-label">Unit</span>
                <span class="identity-val">{{ product.Unit || '—' }}</span>
              </div>
              <div class="identity-row">
                <span class="identity-label">Returnable</span>
                <span class="identity-val">
                  <span class="returnable-pill" :class="product.IsReturnable ? 'returnable-pill--yes' : 'returnable-pill--no'">
                    <i :class="product.IsReturnable ? 'fa-solid fa-rotate-left' : 'fa-solid fa-ban'"></i>
                    {{ product.IsReturnable ? 'Yes' : 'No' }}
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ══ COLUMN 2: AT A GLANCE + DETAILS + SIZES ══ -->
        <div class="col col-main">

          <div class="stat-grid">
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
              <span class="stat-sub">Alert at or below</span>
            </div>
            <div v-if="offcutPieces > 0" class="stat-tile">
              <span class="stat-label">Offcuts on hand</span>
              <span class="stat-value stat-value--amber">{{ offcutPieces }} <small>pc{{ offcutPieces > 1 ? 's' : '' }}</small></span>
              <span class="stat-sub">Tracked separately</span>
            </div>
          </div>

          <!-- Product details -->
          <div class="detail-card">
            <div class="detail-card-header">
              <div class="detail-card-icon detail-card-icon--blue"><i class="fa-solid fa-circle-info"></i></div>
              <h2 class="detail-card-title">Product Details</h2>
            </div>
            <div class="detail-card-body">
              <p
                class="desc-text"
                :class="{ 'desc-text--empty': !product.Description }"
                :title="product.Description || ''"
              >{{ product.Description || 'No description available.' }}</p>

              <div v-if="specTiles.length" class="attr-grid">
                <div v-for="a in specTiles" :key="a.label" class="attr-tile">
                  <span class="attr-label">{{ a.label }}</span>
                  <span class="attr-val">{{ a.value }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Sizes, prices & stock: takes the remaining height -->
          <div class="detail-card detail-card--fill">
            <div class="detail-card-header">
              <div class="detail-card-icon detail-card-icon--green"><i class="fa-solid fa-tags"></i></div>
              <h2 class="detail-card-title">Sizes, Prices &amp; Stock</h2>
              <span class="count-pill">{{ variants.length }} size{{ variants.length === 1 ? '' : 's' }}</span>
            </div>

            <div class="size-table-wrap">
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
        </div>

        <!-- ══ COLUMN 3: SUPPLIERS + OFFCUTS ══ -->
        <div class="col col-side">

          <div class="detail-card detail-card--fill">
            <div class="detail-card-header">
              <div class="detail-card-icon detail-card-icon--violet"><i class="fa-solid fa-truck-field"></i></div>
              <h2 class="detail-card-title">Suppliers</h2>
              <span v-if="product.suppliers?.length" class="count-pill">{{ product.suppliers.length }}</span>
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
              <p>No suppliers assigned.</p>
            </div>
          </div>

          <div v-if="remnants.length" class="detail-card detail-card--fill">
            <div class="detail-card-header" title="Offcuts are tracked separately from full-piece stock">
              <div class="detail-card-icon detail-card-icon--amber"><i class="fa-solid fa-scissors"></i></div>
              <h2 class="detail-card-title">Cut Offcuts</h2>
              <span class="count-pill count-pill--amber">{{ offcutPieces }} pc{{ offcutPieces > 1 ? 's' : '' }}</span>
            </div>
            <div class="size-table-wrap">
              <table class="size-table">
                <thead>
                  <tr>
                    <th>Size</th>
                    <th class="num">Length</th>
                    <th class="num">Pcs</th>
                    <th>Receipt</th>
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
  if (p.Material)    tiles.push({ label: 'Material',       value: p.Material })
  if (p.ProfileType) tiles.push({ label: 'Profile / Type', value: p.ProfileType })
  if (p.Color)       tiles.push({ label: 'Color',          value: p.Color })
  if (p.Weight)      tiles.push({ label: 'Weight',         value: `${p.Weight} ${p.WeightUnit || ''}`.trim() })
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
  --radius: 14px;

  /* ── SPACE TUNING (same bleed values as the Add / Edit / List pages) ── */
  --bleed-x: 30px;
  --bleed-y: 26px;
  --pv-gap: 14px;

  /* ── FIT-TO-SCREEN TUNING ──
     On wide screens the page is exactly as tall as the visible area, so nothing
     scrolls. The shell (Layout.vue) has a top bar and some bottom padding that the
     page must leave room for. If the browser still shows a page scrollbar, RAISE
     these numbers; if there is an empty strip under the page, LOWER them. */
  --shell-top: 64px;
  --shell-bottom: 32px;

  min-height: 100%;
  overflow-x: clip;
  background: var(--pv-bg);
  padding: 14px 22px 22px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--pv-gap);
  transition: background-color .22s ease;
}
.prod-view-page > * { min-width: 0; }

/* Desktop only: pull the page outward into the shell's padding (top, left AND right). */
@media (min-width: 1001px) {
  .prod-view-page { margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) 0; }
}

/* ── PAGE HEADER (compact, one row) ── */
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-shrink: 0; }
.header-main { display: flex; align-items: center; gap: 16px; min-width: 0; }
.title-block { min-width: 0; padding-left: 16px; border-left: 1px solid var(--pv-border-strong); }
.page-eyebrow { font-size: 10.5px; font-weight: 700; letter-spacing: .12em; color: var(--pv-accent); text-transform: uppercase; margin: 0 0 2px; }
.title-row  { display: flex; align-items: center; gap: 10px; min-width: 0; flex-wrap: wrap; }
.page-title {
  font-size: 22px; font-weight: 800; color: var(--pv-text-primary); letter-spacing: -.03em; margin: 0;
  transition: color .22s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;
}
.sku-chip {
  font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px;
  background: var(--pv-surface-raised); border: 1px solid var(--pv-border); color: var(--pv-text-secondary);
  font-variant-numeric: tabular-nums; white-space: nowrap;
}
.sku-chip--empty { color: #d97706; border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.10); }
.header-actions { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }

.status-pill {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 700; letter-spacing: .03em;
  border-radius: 20px; padding: 3px 10px; white-space: nowrap;
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
  border-radius: 10px; padding: 9px 16px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none; white-space: nowrap;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pv-ring); }
.btn--primary {
  background: linear-gradient(135deg, var(--pv-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }

/* Back button: smaller, but still bordered and accent-tinted so it is easy to spot. */
.btn--back {
  gap: 7px; padding: 6px 12px 6px 7px; font-size: 12.5px;
  background: var(--pv-surface); border-color: var(--pv-border-strong); color: var(--pv-text-primary);
  box-shadow: var(--c-shadow-sm); flex-shrink: 0;
}
.btn--back i {
  width: 20px; height: 20px; border-radius: 6px; font-size: 10px;
  display: inline-flex; align-items: center; justify-content: center;
  background: var(--pv-accent-soft); color: var(--pv-accent);
  transition: transform .18s, background-color .18s, color .18s;
}
.btn--back:hover { border-color: var(--pv-accent); color: var(--pv-accent); background: var(--pv-accent-soft); }
.btn--back:hover i { transform: translateX(-2px); background: var(--pv-accent); color: #fff; }

/* ── LAYOUT ──
   Default (narrow or very short screens): one normal scrolling column.
   Wide, tall-enough screens: the page is exactly the visible height and the three
   columns share it, so nothing scrolls. */
.view-layout { display: flex; flex-direction: column; gap: var(--pv-gap); min-width: 0; }
.col { display: flex; flex-direction: column; gap: var(--pv-gap); min-width: 0; }

@media (min-width: 1101px) and (min-height: 561px) {
  .prod-view-page {
    height: calc(100dvh - var(--shell-top) - var(--shell-bottom) + var(--bleed-y));
    min-height: 0;
    overflow: hidden;
  }
  .view-layout {
    flex: 1 1 0; min-height: 0;
    display: grid; grid-template-columns: 250px minmax(0, 1fr) 290px;
    gap: var(--pv-gap); align-items: stretch;
  }
  .col { min-height: 0; }
  .detail-card--fill { flex: 1 1 0; min-height: 0; display: flex; flex-direction: column; }
  .detail-card--image { flex: 1 1 0; min-height: 0; display: flex; flex-direction: column; }
  .detail-card--image .product-image-wrap { flex: 1 1 0; min-height: 90px; aspect-ratio: auto; max-height: none; }
  .detail-card--image .identity-list { flex-shrink: 0; }
  .size-table-wrap { flex: 1 1 0; min-height: 0; overflow: auto; }
  .supplier-grid { flex: 1 1 0; min-height: 0; overflow: auto; }
}
@media (min-width: 1500px) and (min-height: 561px) {
  .view-layout { grid-template-columns: 290px minmax(0, 1fr) 330px; }
}

/* ── SKELETON ── */
.skeleton-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
.skeleton-tile  { padding: 14px; display: flex; flex-direction: column; gap: 10px; }
.skeleton-card  { padding: 18px; display: flex; flex-direction: column; gap: 12px; }
.skeleton-card--fill { flex: 1 1 0; }
.skeleton-block { height: 180px; border-radius: 12px; background: var(--pv-border); animation: shimmer 1.4s infinite; }
.skeleton-line  { height: 12px; border-radius: 6px; background: var(--pv-border); animation: shimmer 1.4s infinite; width: 80%; }
.skeleton-line--wide   { width: 100%; }
.skeleton-line--narrow { width: 50%; }
.skeleton-line--big    { height: 20px; width: 70%; }
@keyframes shimmer { 0%,100%{opacity:.6} 50%{opacity:.3} }

/* ── DETAIL CARDS ── */
.detail-card {
  background: var(--pv-surface); border: 1px solid var(--pv-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.detail-card-header {
  display: flex; align-items: center; gap: 10px; flex-shrink: 0;
  padding: 10px 16px; border-bottom: 1px solid var(--pv-border);
  transition: border-color .22s;
}
.detail-card-icon {
  width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 13px;
}
.detail-card-icon--blue   { background: rgba(59,130,246,.12); color: var(--pv-blue);   }
.detail-card-icon--green  { background: rgba(16,185,129,.12); color: var(--pv-green);  }
.detail-card-icon--amber  { background: rgba(245,158,11,.12); color: var(--pv-amber);  }
.detail-card-icon--violet { background: rgba(99,102,241,.12); color: var(--pv-violet); }
html[data-theme="dark"] .detail-card-icon--blue   { background: rgba(59,130,246,.18); }
html[data-theme="dark"] .detail-card-icon--green  { background: rgba(16,185,129,.18); }
html[data-theme="dark"] .detail-card-icon--amber  { background: rgba(245,158,11,.18); }
html[data-theme="dark"] .detail-card-icon--violet { background: rgba(99,102,241,.18); }
.detail-card-title { font-size: 14px; font-weight: 700; color: var(--pv-text-primary); margin: 0; transition: color .22s; }
.detail-card-body  { padding: 12px 16px; display: flex; flex-direction: column; gap: 10px; }
.count-pill {
  margin-left: auto; flex-shrink: 0; font-size: 11px; font-weight: 700;
  padding: 3px 10px; border-radius: 20px; background: var(--pv-accent-soft); color: var(--pv-accent);
}
.count-pill--amber { background: rgba(245,158,11,.14); color: #d97706; }
html[data-theme="dark"] .count-pill--amber { color: #FCD34D; }

/* ── AT A GLANCE ── */
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; flex-shrink: 0; }
.stat-tile {
  display: flex; flex-direction: column; gap: 3px; padding: 10px 14px; min-width: 0;
  background: var(--pv-surface); border: 1px solid var(--pv-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm);
  transition: background-color .22s, border-color .22s;
}
.stat-tile--price {
  background: linear-gradient(rgba(16,185,129,.07), rgba(16,185,129,.07)), var(--pv-surface);
  border-color: rgba(16,185,129,.28);
}
.stat-label { font-size: 10.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--pv-text-muted); }
.stat-value {
  font-size: 19px; font-weight: 800; color: var(--pv-text-primary); letter-spacing: -.03em;
  font-variant-numeric: tabular-nums; overflow-wrap: anywhere; line-height: 1.15;
}
.stat-value small { font-size: 11.5px; font-weight: 600; color: var(--pv-text-faint); letter-spacing: 0; }
.stat-value--green { color: var(--pv-green); }
.stat-value--amber { color: var(--pv-amber); }
.stat-value--red   { color: var(--pv-red); }
html[data-theme="dark"] .stat-value--green { color: #4ADE80; }
html[data-theme="dark"] .stat-value--amber { color: #FCD34D; }
html[data-theme="dark"] .stat-value--red   { color: #F87171; }
.stat-sub { font-size: 11.5px; color: var(--pv-text-faint); display: flex; align-items: center; min-height: 16px; }
.low-stock-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10.5px; font-weight: 700;
  background: rgba(245,158,11,.14); color: #d97706;
  border-radius: 20px; padding: 1px 8px;
}
html[data-theme="dark"] .low-stock-badge { background: rgba(245,158,11,.18); color: #FCD34D; }

/* ── PRODUCT DETAILS ── */
.desc-text {
  margin: 0; font-size: 13px; line-height: 1.55; color: var(--pv-text-secondary);
  display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
}
.desc-text--empty { color: var(--pv-text-faint); font-style: italic; }
.attr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; }
.attr-tile {
  display: flex; flex-direction: column; gap: 2px; padding: 7px 12px; min-width: 0;
  background: var(--pv-surface-sunken); border: 1px solid var(--pv-border); border-radius: 10px;
}
.attr-label { font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--pv-text-muted); }
.attr-val   { font-size: 13px; font-weight: 600; color: var(--pv-text-primary); overflow-wrap: anywhere; }

/* ── IMAGE + IDENTITY ── */
.product-image-wrap {
  position: relative; width: 100%;
  aspect-ratio: 1; max-height: 280px; overflow: hidden;
  background: var(--pv-surface-sunken);
  border-bottom: 1px solid var(--pv-border);
  transition: background-color .22s;
}
.product-image { width: 100%; height: 100%; object-fit: cover; display: block; }
.product-image-placeholder {
  width: 100%; height: 100%; display: flex; flex-direction: column; gap: 6px;
  align-items: center; justify-content: center;
  font-size: 38px; color: var(--pv-text-faint);
}
.product-image-placeholder span { font-size: 11.5px; font-weight: 600; }

.identity-list { padding: 4px 16px 6px; display: flex; flex-direction: column; }
.identity-row  {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 7px 0; border-bottom: 1px dashed var(--pv-border);
}
.identity-row:last-child { border-bottom: none; }
.identity-label { font-size: 10.5px; font-weight: 600; color: var(--pv-text-faint); text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
.identity-val   { font-size: 12.5px; font-weight: 600; color: var(--pv-text-primary); text-align: right; display: inline-flex; align-items: center; gap: 5px; transition: color .22s; overflow-wrap: anywhere; min-width: 0; }
.identity-val--mono { font-variant-numeric: tabular-nums; font-family: 'SF Mono','Fira Code',monospace; font-size: 11.5px; }

.copy-btn {
  width: 22px; height: 22px; flex-shrink: 0; border: none; border-radius: 6px; cursor: pointer;
  background: transparent; color: var(--pv-text-faint); font-size: 10.5px;
  display: inline-flex; align-items: center; justify-content: center;
  transition: background-color .15s, color .15s;
}
.copy-btn:hover { background: var(--pv-accent-soft); color: var(--pv-accent); }
.copy-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pv-ring); }
.copy-btn--done, .copy-btn--done:hover { background: rgba(16,185,129,.14); color: var(--pv-green); }

.category-pill {
  display: inline-flex; align-items: center;
  background: rgba(99,102,241,.12); color: var(--pv-violet);
  font-size: 11px; font-weight: 600; border-radius: 20px; padding: 2px 9px;
  transition: background .22s;
}
html[data-theme="dark"] .category-pill { background: rgba(99,102,241,.20); color: #A5B4FC; }

.returnable-pill {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 600; border-radius: 20px; padding: 2px 9px;
}
.returnable-pill--yes { background: rgba(16,185,129,.12); color: var(--pv-green); }
.returnable-pill--no  { background: rgba(244,63,94,.10);  color: var(--pv-red);  }
html[data-theme="dark"] .returnable-pill--yes { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .returnable-pill--no  { background: rgba(244,63,94,.16);  color: #F87171; }

/* ── SIZES / OFFCUTS TABLES ── */
.size-table-wrap { overflow-x: auto; padding: 0 8px 6px; }
.size-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.size-table th {
  position: sticky; top: 0; z-index: 1; background: var(--pv-surface);
  text-align: left; padding: 9px 10px;
  font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  color: var(--pv-text-faint); border-bottom: 1px solid var(--pv-border);
  white-space: nowrap;
}
.size-table td {
  padding: 8px 10px; color: var(--pv-text-primary);
  border-bottom: 1px solid var(--pv-border);
  font-variant-numeric: tabular-nums;
  transition: border-color .22s;
}
.size-table .num { text-align: right; }
.size-table tbody tr:last-child td { border-bottom: none; }
.size-table tbody tr:hover td { background: var(--pv-surface-raised); }
.size-name { font-weight: 700; }
.size-empty { text-align: center; color: var(--pv-text-faint); }
.size-dash { color: var(--pv-text-faint); }
.mono { font-family: 'SF Mono','Fira Code',monospace; font-size: 12px; }

.margin-pill {
  display: inline-block; padding: 2px 8px; border-radius: 20px;
  font-size: 11px; font-weight: 700;
  background: rgba(16,185,129,.12); color: var(--pv-green);
}
.margin-pill--loss { background: rgba(244,63,94,.12); color: var(--pv-red); }
html[data-theme="dark"] .margin-pill { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .margin-pill--loss { background: rgba(244,63,94,.16); color: #F87171; }

.stock-chip {
  display: inline-block; min-width: 32px; text-align: center;
  padding: 2px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 800;
}
.stock-chip--ok  { background: rgba(16,185,129,.12); color: #059669; }
.stock-chip--low { background: rgba(245,158,11,.14); color: #d97706; }
.stock-chip--oos { background: rgba(244,63,94,.12);  color: #e11d48; }
html[data-theme="dark"] .stock-chip--ok  { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .stock-chip--low { background: rgba(245,158,11,.18); color: #FCD34D; }
html[data-theme="dark"] .stock-chip--oos { background: rgba(244,63,94,.16);  color: #F87171; }

/* ── SUPPLIERS ── */
.supplier-grid { padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; }
.supplier-card {
  display: flex; align-items: flex-start; gap: 10px;
  background: var(--pv-surface-sunken); border: 1px solid var(--pv-border);
  border-radius: 12px; padding: 10px 12px;
  transition: background-color .22s, border-color .22s;
}
.supplier-card:hover { border-color: var(--pv-accent-border); }
.supplier-avatar {
  width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
  background: var(--pv-accent-soft); color: var(--pv-accent);
  border: 1px solid var(--pv-accent-border);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 800;
  transition: background-color .22s, border-color .22s;
}
.supplier-info  { flex: 1; min-width: 0; }
.supplier-name  { font-size: 13px; font-weight: 700; color: var(--pv-text-primary); margin: 0 0 5px; transition: color .22s; overflow-wrap: anywhere; }
.supplier-meta  { display: flex; flex-direction: column; gap: 3px; }
.supplier-meta-row {
  display: flex; align-items: center; gap: 6px;
  font-size: 11.5px; color: var(--pv-text-muted); overflow-wrap: anywhere;
}
.supplier-meta-row i { font-size: 10px; color: var(--pv-text-faint); flex-shrink: 0; }

.no-suppliers {
  padding: 18px 16px; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 8px; text-align: center; color: var(--pv-text-faint);
}
.detail-card--fill > .no-suppliers { flex: 1 1 auto; }
.no-suppliers i { font-size: 22px; }
.no-suppliers p { font-size: 13px; color: var(--pv-text-muted); margin: 0; }

/* ── RESPONSIVE (normal scrolling page) ── */
@media (max-width: 1100px) {
  .prod-view-page { --pv-gap: 14px; padding: 12px 14px 32px; }
}
@media (max-width: 800px) {
  .prod-view-page { padding: 14px 14px 28px; }
  .page-header { flex-direction: column; align-items: stretch; }
  .header-main { flex-direction: column; align-items: flex-start; gap: 12px; }
  .title-block { padding-left: 0; border-left: none; }
  .page-title { white-space: normal; }
  .header-actions .btn { flex: 1; justify-content: center; }
  .supplier-grid { padding-left: 12px; padding-right: 12px; }
}
@media (prefers-reduced-motion: reduce) {
  .skeleton-block, .skeleton-line { animation: none; }
  .btn--back i { transition: none; }
}
</style>