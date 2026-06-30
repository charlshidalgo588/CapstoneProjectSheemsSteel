<template>
  <Layout>
    <div class="prod-view-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div class="breadcrumb">
          <RouterLink to="/products" class="breadcrumb-link">
            <i class="fa-solid fa-arrow-left"></i> Products
          </RouterLink>
        </div>
        <div class="header-row">
          <div>
            <p class="page-eyebrow">Inventory</p>
            <h1 class="page-title">{{ product.ProductName || 'Product Details' }}</h1>
          </div>
          <div class="header-actions" v-if="!loading">
            <RouterLink to="/products" class="btn btn--ghost">
              <i class="fa-solid fa-list"></i> All Products
            </RouterLink>
            <RouterLink :to="`/products/${product.ProductID}/edit`" class="btn btn--primary">
              <i class="fa-solid fa-pen-to-square"></i> Edit Product
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- ── LOADING SKELETON ── -->
      <template v-if="loading">
        <div class="skeleton-grid-3">
          <div class="detail-card skeleton-card" v-for="n in 3" :key="n">
            <div class="skeleton-block skeleton-block--tall"></div>
            <div class="skeleton-line skeleton-line--wide" style="margin-top:14px"></div>
            <div class="skeleton-line"></div>
            <div class="skeleton-line skeleton-line--narrow"></div>
          </div>
        </div>
        <div class="detail-card skeleton-card" style="margin-top:0">
          <div class="skeleton-line skeleton-line--wide"></div>
          <div class="skeleton-grid-3" style="margin-top:14px">
            <div class="skeleton-line" v-for="n in 6" :key="n"></div>
          </div>
        </div>
      </template>

      <!-- ── CONTENT ── -->
      <template v-else>

        <!-- ── TOP 3-COLUMN GRID ── -->
        <div class="top-grid">

          <!-- LEFT: Image + identity -->
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
              </div>

              <!-- Stock badge overlay -->
              <div class="stock-overlay" :class="stockClass">{{ stockLabel }}</div>
            </div>

            <div class="identity-list">
              <div class="identity-row">
                <span class="identity-label">SKU</span>
                <span class="identity-val identity-val--mono">{{ product.SKU || 'N/A' }}</span>
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

          <!-- MIDDLE: Product details -->
          <div class="detail-card">
            <div class="detail-card-header">
              <div class="detail-card-icon detail-card-icon--blue">
                <i class="fa-solid fa-circle-info"></i>
              </div>
              <h2 class="detail-card-title">Product Details</h2>
            </div>

            <div class="detail-list">
              <div class="detail-row detail-row--full">
                <span class="detail-label">Description</span>
                <span class="detail-val detail-val--desc">
                  {{ product.Description || 'No description available.' }}
                </span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Weight</span>
                <span class="detail-val">
                  {{ product.Weight ? `${product.Weight} ${product.WeightUnit}` : '—' }}
                </span>
              </div>
            </div>
          </div>

          <!-- RIGHT: Pricing & Inventory -->
          <div class="detail-card">
            <div class="detail-card-header">
              <div class="detail-card-icon detail-card-icon--green">
                <i class="fa-solid fa-peso-sign"></i>
              </div>
              <h2 class="detail-card-title">Pricing & Inventory</h2>
            </div>

            <div class="detail-list">
              <!-- Selling price (featured) -->
              <div class="price-featured">
                <span class="price-featured-label">Selling Price</span>
                <span class="price-featured-val">₱{{ formatPrice(product.SellingPrice) }}</span>
              </div>

              <div class="detail-row">
                <span class="detail-label">Cost Price</span>
                <span class="detail-val">₱{{ formatPrice(product.CostPrice) }}</span>
              </div>

              <div class="detail-divider"></div>

              <div class="detail-row">
                <span class="detail-label">Current Stock</span>
                <span class="detail-val">
                  <span class="stock-count" :class="stockCountClass">
                    {{ product.inventory?.QuantityOnHand ?? 0 }} units
                  </span>
                  <span v-if="isLowStock" class="low-stock-badge">
                    <i class="fa-solid fa-triangle-exclamation"></i> Low Stock
                  </span>
                </span>
              </div>

              <div class="detail-row">
                <span class="detail-label">Reorder Level</span>
                <span class="detail-val">{{ product.inventory?.ReorderLevel ?? 0 }} units</span>
              </div>
            </div>
          </div>

        </div>

        <!-- ── SPECIFICATIONS ── -->
        <div class="detail-card">
          <div class="detail-card-header">
            <div class="detail-card-icon detail-card-icon--amber">
              <i class="fa-solid fa-ruler-combined"></i>
            </div>
            <h2 class="detail-card-title">Specifications</h2>
          </div>

          <div class="spec-grid">
            <div class="spec-item">
              <span class="spec-label">Material</span>
              <span class="spec-val">{{ product.Material || '—' }}</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Profile / Type</span>
              <span class="spec-val">{{ product.ProfileType || '—' }}</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Color</span>
              <span class="spec-val">{{ product.Color || '—' }}</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Length</span>
              <span class="spec-val">
                {{ product.Length ? `${product.Length} ${product.LengthUnit || ''}` : '—' }}
              </span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Width</span>
              <span class="spec-val">
                {{ product.Width ? `${product.Width} ${product.WidthUnit || ''}` : '—' }}
              </span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Thickness / Gauge</span>
              <span class="spec-val">{{ product.Thickness || '—' }}</span>
            </div>
          </div>
        </div>

        <!-- ── SUPPLIERS ── -->
        <div class="detail-card">
          <div class="detail-card-header">
            <div class="detail-card-icon detail-card-icon--violet">
              <i class="fa-solid fa-truck-field"></i>
            </div>
            <h2 class="detail-card-title">Supplier Information</h2>
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

      </template>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/axios'
import Layout from '@/components/Layout.vue'

const route   = useRoute()
const product = ref({})
const loading = ref(true)

const formatPrice = val => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const imageUrl    = path => `http://localhost:8000/storage/${path}`

const isLowStock = computed(() => {
  const inv = product.value.inventory
  return inv && inv.QuantityOnHand <= inv.ReorderLevel
})

const stockClass = computed(() => {
  const qty = product.value.inventory?.QuantityOnHand ?? 0
  if (qty <= 0) return 'stock-overlay--oos'
  if (isLowStock.value) return 'stock-overlay--low'
  return 'stock-overlay--ok'
})

const stockLabel = computed(() => {
  const qty = product.value.inventory?.QuantityOnHand ?? 0
  if (qty <= 0) return 'Out of Stock'
  if (isLowStock.value) return 'Low Stock'
  return 'In Stock'
})

const stockCountClass = computed(() => {
  const qty = product.value.inventory?.QuantityOnHand ?? 0
  if (qty <= 0) return 'stock-count--oos'
  if (isLowStock.value) return 'stock-count--low'
  return 'stock-count--ok'
})

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
</script>

<style scoped>
/* ── TOKEN BRIDGE ── */
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
  --pv-green:          #10b981;
  --pv-red:            #f43f5e;
  --pv-blue:           #3b82f6;
  --pv-violet:         #6366f1;
  --pv-amber:          #f59e0b;
  --radius: 16px;

  min-height: 100%;
  background: var(--pv-bg);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 20px;
  transition: background-color .22s ease;
}

/* ── PAGE HEADER ── */
.breadcrumb { margin-bottom: 10px; }
.breadcrumb-link {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12.5px; font-weight: 600;
  color: var(--pv-text-muted); text-decoration: none; transition: color .15s;
}
.breadcrumb-link:hover { color: var(--pv-accent); }

.header-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--pv-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--pv-text-primary); letter-spacing: -.03em; margin: 0; transition: color .22s; }
.header-actions { display: flex; gap: 10px; align-items: center; flex-shrink: 0; flex-wrap: wrap; }

/* ── BUTTONS ── */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn--ghost   { background: var(--pv-surface); border-color: var(--pv-border-strong); color: var(--pv-text-secondary); }
.btn--ghost:hover { background: var(--pv-surface-raised); border-color: var(--pv-accent-border); color: var(--pv-text-primary); }
.btn--primary {
  background: linear-gradient(135deg, var(--pv-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }

/* ── SKELETON ── */
.skeleton-grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; }
.skeleton-card   { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
.skeleton-block  { border-radius: 12px; background: var(--pv-border); animation: shimmer 1.4s infinite; }
.skeleton-block--tall { height: 200px; }
.skeleton-line   { height: 13px; border-radius: 6px; background: var(--pv-border); animation: shimmer 1.4s infinite; width: 80%; }
.skeleton-line--wide   { width: 100%; }
.skeleton-line--narrow { width: 50%; }
@keyframes shimmer { 0%,100%{opacity:.6} 50%{opacity:.3} }

/* ── DETAIL CARDS ── */
.top-grid { display: grid; grid-template-columns: 280px 1fr 1fr; gap: 20px; align-items: start; }

.detail-card {
  background: var(--pv-surface); border: 1px solid var(--pv-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}

.detail-card-header {
  display: flex; align-items: center; gap: 12px;
  padding: 16px 20px 12px; border-bottom: 1px solid var(--pv-border);
  transition: border-color .22s;
}
.detail-card-icon {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
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
.detail-card-title { font-size: 14.5px; font-weight: 700; color: var(--pv-text-primary); margin: 0; transition: color .22s; }

/* ── LEFT IMAGE CARD ── */
.product-image-wrap {
  position: relative; width: 100%;
  aspect-ratio: 1; overflow: hidden;
  background: var(--pv-surface-sunken);
  transition: background-color .22s;
}
.product-image { width: 100%; height: 100%; object-fit: cover; display: block; }
.product-image-placeholder {
  width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
  font-size: 48px; color: var(--pv-text-faint);
}

/* Stock overlay badge */
.stock-overlay {
  position: absolute; top: 12px; right: 12px;
  font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
  border-radius: 20px; padding: 4px 10px;
}
.stock-overlay--ok  { background: rgba(16,185,129,.85); color: #fff; }
.stock-overlay--low { background: rgba(245,158,11,.90); color: #fff; }
.stock-overlay--oos { background: rgba(244,63,94,.88);  color: #fff; }

/* Identity list (below image) */
.identity-list { padding: 14px 16px; display: flex; flex-direction: column; gap: 10px; }
.identity-row  { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
.identity-label { font-size: 11.5px; font-weight: 600; color: var(--pv-text-faint); text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
.identity-val   { font-size: 13px; font-weight: 600; color: var(--pv-text-primary); text-align: right; transition: color .22s; }
.identity-val--mono { font-variant-numeric: tabular-nums; font-family: 'SF Mono','Fira Code',monospace; font-size: 12px; }

.category-pill {
  display: inline-flex; align-items: center;
  background: rgba(99,102,241,.12); color: var(--pv-violet);
  font-size: 11.5px; font-weight: 600; border-radius: 20px; padding: 2px 9px;
  transition: background .22s;
}
html[data-theme="dark"] .category-pill { background: rgba(99,102,241,.20); color: #A5B4FC; }

.returnable-pill {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11.5px; font-weight: 600; border-radius: 20px; padding: 2px 9px;
}
.returnable-pill--yes { background: rgba(16,185,129,.12); color: var(--pv-green); }
.returnable-pill--no  { background: rgba(244,63,94,.10);  color: var(--pv-red);  }
html[data-theme="dark"] .returnable-pill--yes { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .returnable-pill--no  { background: rgba(244,63,94,.16);  color: #F87171; }

/* ── DETAIL LIST ── */
.detail-list { padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; }
.detail-row  { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
.detail-row--full { flex-direction: column; gap: 6px; }
.detail-label { font-size: 11.5px; font-weight: 600; color: var(--pv-text-faint); text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; flex-shrink: 0; }
.detail-val   { font-size: 13.5px; font-weight: 500; color: var(--pv-text-primary); text-align: right; transition: color .22s; }
.detail-val--desc { text-align: left; line-height: 1.65; color: var(--pv-text-secondary); font-size: 13px; }

/* Pricing featured */
.price-featured {
  display: flex; align-items: center; justify-content: space-between;
  background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.20);
  border-radius: 12px; padding: 12px 14px;
  transition: background .22s, border-color .22s;
}
html[data-theme="dark"] .price-featured { background: rgba(16,185,129,.12); border-color: rgba(16,185,129,.25); }
.price-featured-label { font-size: 12px; font-weight: 600; color: var(--pv-text-muted); text-transform: uppercase; letter-spacing: .07em; }
.price-featured-val   { font-size: 20px; font-weight: 800; color: var(--pv-green); letter-spacing: -.03em; }

.detail-divider { height: 1px; background: var(--pv-border); margin: 2px 0; transition: background .22s; }

.stock-count     { font-size: 13.5px; font-weight: 700; }
.stock-count--ok  { color: var(--pv-green); }
.stock-count--low { color: var(--pv-amber); }
.stock-count--oos { color: var(--pv-red);   }
html[data-theme="dark"] .stock-count--ok  { color: #4ADE80; }
html[data-theme="dark"] .stock-count--low { color: #FCD34D; }
html[data-theme="dark"] .stock-count--oos { color: #F87171; }

.low-stock-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 700;
  background: rgba(244,63,94,.10); color: var(--pv-red);
  border-radius: 20px; padding: 2px 8px; margin-left: 6px;
}
html[data-theme="dark"] .low-stock-badge { background: rgba(244,63,94,.16); color: #F87171; }

/* ── SPEC GRID ── */
.spec-grid {
  display: grid; grid-template-columns: repeat(3,1fr);
  gap: 0; padding: 8px 0;
}
.spec-item {
  padding: 14px 20px; display: flex; flex-direction: column; gap: 5px;
  border-bottom: 1px solid var(--pv-border); border-right: 1px solid var(--pv-border);
  transition: border-color .22s;
}
.spec-item:nth-child(3n) { border-right: none; }
.spec-item:nth-child(n+4) { border-bottom: none; }
.spec-label { font-size: 11px; font-weight: 700; color: var(--pv-text-faint); text-transform: uppercase; letter-spacing: .07em; }
.spec-val   { font-size: 14px; font-weight: 600; color: var(--pv-text-primary); transition: color .22s; }

/* ── SUPPLIER GRID ── */
.supplier-grid { padding: 16px 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(240px,1fr)); gap: 14px; }

.supplier-card {
  display: flex; align-items: flex-start; gap: 12px;
  background: var(--pv-surface-raised); border: 1px solid var(--pv-border);
  border-radius: 12px; padding: 14px;
  transition: background-color .22s, border-color .22s;
}
.supplier-card:hover { border-color: var(--pv-accent-border); }

.supplier-avatar {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  background: var(--pv-accent-soft); color: var(--pv-accent);
  border: 1px solid var(--pv-accent-border);
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 800;
  transition: background-color .22s, border-color .22s;
}
.supplier-info  { flex: 1; min-width: 0; }
.supplier-name  { font-size: 13.5px; font-weight: 700; color: var(--pv-text-primary); margin: 0 0 8px; transition: color .22s; }
.supplier-meta  { display: flex; flex-direction: column; gap: 4px; }
.supplier-meta-row {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--pv-text-muted);
}
.supplier-meta-row i { font-size: 10px; color: var(--pv-text-faint); flex-shrink: 0; }

.no-suppliers {
  padding: 32px 20px; display: flex; flex-direction: column;
  align-items: center; gap: 10px; text-align: center; color: var(--pv-text-faint);
}
.no-suppliers i { font-size: 28px; }
.no-suppliers p { font-size: 13.5px; color: var(--pv-text-muted); margin: 0; }

/* ── RESPONSIVE ── */
@media (max-width: 1100px) {
  .top-grid    { grid-template-columns: 240px 1fr 1fr; }
  .spec-grid   { grid-template-columns: repeat(2,1fr); }
  .spec-item:nth-child(3n)  { border-right: 1px solid var(--pv-border); }
  .spec-item:nth-child(2n)  { border-right: none; }
  .spec-item:nth-child(n+5) { border-bottom: none; }
  .spec-item:nth-child(n+3):nth-child(-n+4) { border-bottom: 1px solid var(--pv-border); }
}
@media (max-width: 800px) {
  .prod-view-page { padding: 16px 16px 48px; }
  .top-grid { grid-template-columns: 1fr; }
  .skeleton-grid-3 { grid-template-columns: 1fr; }
  .spec-grid { grid-template-columns: repeat(2,1fr); }
  .header-row { flex-direction: column; }
  .header-actions { width: 100%; }
  .btn { flex: 1; justify-content: center; }
}
@media (max-width: 500px) {
  .spec-grid { grid-template-columns: 1fr; }
  .spec-item { border-right: none !important; border-bottom: 1px solid var(--pv-border) !important; }
  .spec-item:last-child { border-bottom: none !important; }
}
</style>