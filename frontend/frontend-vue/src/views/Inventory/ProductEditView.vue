<template>
  <Layout>
    <div class="prod-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Inventory</p>
          <h1 class="page-title">Edit Product</h1>
          <p v-if="!pageLoading && form.ProductName" class="page-sub">
            {{ form.ProductName }}
            <span class="sku-chip" :class="{ 'sku-chip--empty': !form.SKU }">{{ form.SKU || 'No SKU yet' }}</span>
          </p>
        </div>
        <RouterLink to="/products" class="btn btn--ghost">
          <i class="fa-solid fa-arrow-left"></i> Back to List
        </RouterLink>
      </div>

      <!-- ── FLOATING TOASTS (always visible, even when scrolled) ── -->
      <div class="toast-stack" aria-live="polite">
        <transition name="alert-fade">
          <div v-if="error" class="alert alert--error" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ error }}</span>
            <button type="button" class="alert-close" @click="error = ''" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>
          </div>
        </transition>
        <transition name="alert-fade">
          <div v-if="success" class="alert alert--success" role="status">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ success }}</span>
          </div>
        </transition>
      </div>

      <!-- ── SKELETON ── -->
      <template v-if="pageLoading">
        <div v-for="n in 5" :key="n" class="form-card skeleton-card">
          <div class="skeleton-header">
            <div class="skeleton-icon"></div>
            <div class="skeleton-lines">
              <div class="skeleton-line skeleton-line--wide"></div>
              <div class="skeleton-line skeleton-line--narrow"></div>
            </div>
          </div>
          <div class="skeleton-body">
            <div class="skeleton-grid">
              <div class="skeleton-field"></div>
              <div class="skeleton-field"></div>
            </div>
          </div>
        </div>
      </template>

      <!-- ── FORM ── -->
      <form v-else ref="formEl" @submit.prevent="updateProduct">

        <!-- ══ BASIC INFORMATION ══ -->
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-icon form-card-icon--blue"><i class="fa-solid fa-tag"></i></div>
            <div>
              <h2 class="form-card-title">Basic Information</h2>
              <p class="form-card-sub">Name, SKU, barcode, unit and return policy.</p>
            </div>
          </div>
          <div class="form-card-body">
            <div class="form-grid-2">
              <div class="form-stack">
                <div class="form-field">
                  <label class="form-label" for="f-name">Product Name <span class="req">*</span></label>
                  <div class="input-wrap">
                    <i class="fa-solid fa-cube input-icon"></i>
                    <input id="f-name" v-model="form.ProductName" type="text" class="form-input" required />
                  </div>
                </div>
                <div class="form-field">
                  <label class="form-label" for="f-sku">SKU</label>
                  <div class="input-wrap">
                    <i class="fa-solid fa-barcode input-icon"></i>
                    <input
                      id="f-sku"
                      :value="form.SKU"
                      disabled
                      class="form-input form-input--disabled"
                      :placeholder="skuPlaceholder"
                    />
                    <i v-if="form.SKU" class="fa-solid fa-lock input-lock" title="SKU cannot be changed"></i>
                  </div>
                  <p v-if="!form.SKU" class="form-hint">
                    No SKU on file. One will be generated automatically when you save.
                  </p>
                </div>
                <div class="form-field">
                  <label class="form-label" for="f-barcode">Barcode</label>
                  <div class="input-wrap">
                    <i class="fa-solid fa-barcode input-icon"></i>
                    <input id="f-barcode" v-model="form.Barcode" type="text" class="form-input" placeholder="Scan or type manually" />
                  </div>
                </div>
                <div class="form-field">
                  <label class="form-label" for="f-unit">Unit <span class="req">*</span></label>
                  <div class="select-wrap">
                    <i class="fa-solid fa-scale-balanced input-icon"></i>
                    <select id="f-unit" v-model="form.Unit" class="form-select" required>
                      <option value="">Select Unit</option>
                      <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                    </select>
                    <i class="fa-solid fa-chevron-down select-caret"></i>
                  </div>
                </div>
                <div class="toggle-row" @click="form.IsReturnable = !form.IsReturnable">
                  <button
                    type="button"
                    role="switch"
                    class="toggle-track"
                    :class="{ 'toggle-track--on': form.IsReturnable }"
                    :aria-checked="form.IsReturnable"
                    aria-label="Returnable item"
                    @click.stop="form.IsReturnable = !form.IsReturnable"
                  >
                    <span class="toggle-thumb" :class="{ 'toggle-thumb--on': form.IsReturnable }"></span>
                  </button>
                  <div>
                    <p class="toggle-label">Returnable Item</p>
                    <p class="toggle-sub">Allow this product to be returned after sale.</p>
                  </div>
                </div>
              </div>

              <!-- Image -->
              <div class="form-field">
                <label class="form-label">Product Image</label>
                <div
                  class="upload-zone"
                  :class="{ 'upload-zone--has-image': existingImage || previewImage, 'upload-zone--drag': dragging }"
                  @dragover.prevent="dragging = true"
                  @dragleave.prevent="dragging = false"
                  @drop.prevent="onDrop"
                >
                  <img v-if="previewImage" :src="previewImage" class="upload-preview" alt="New product image preview" />
                  <img v-else-if="existingImage" :src="existingImage" class="upload-preview" alt="Current product image" />
                  <div v-else class="upload-placeholder">
                    <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                    <p class="upload-text">Click or drop an image here</p>
                    <p class="upload-hint">PNG / JPG · Max 5 MB</p>
                  </div>
                  <span v-if="existingImage || previewImage" class="upload-change"><i class="fa-solid fa-camera"></i> Change</span>
                  <input ref="fileInput" type="file" accept="image/*" class="upload-input" aria-label="Upload product image" @change="handleImageUpload" />
                </div>
                <button v-if="previewImage" type="button" class="upload-remove" @click="discardNewImage">
                  <i class="fa-solid fa-rotate-left"></i> Discard new image
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ══ CLASSIFICATION ══ -->
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-icon form-card-icon--violet"><i class="fa-solid fa-layer-group"></i></div>
            <div>
              <h2 class="form-card-title">Classification</h2>
              <p class="form-card-sub">Category and supplier assignment.</p>
            </div>
          </div>
          <div class="form-card-body">
            <div class="form-grid-2">
              <div class="form-field">
                <label class="form-label" for="f-cat">Category <span class="req">*</span></label>
                <div class="select-wrap">
                  <i class="fa-solid fa-folder input-icon"></i>
                  <select id="f-cat" v-model="form.CategoryID" class="form-select" required>
                    <option value="">Select Category</option>
                    <option v-for="c in categories" :key="c.CategoryID" :value="c.CategoryID">{{ c.CategoryName }}</option>
                  </select>
                  <i class="fa-solid fa-chevron-down select-caret"></i>
                </div>
              </div>
              <div class="form-field">
                <label class="form-label" for="f-sup">Supplier</label>
                <div class="select-wrap">
                  <i class="fa-solid fa-truck-field input-icon"></i>
                  <select id="f-sup" v-model="form.SupplierID" class="form-select">
                    <option value="">Select Supplier</option>
                    <option v-for="s in suppliers" :key="s.SupplierID" :value="s.SupplierID">{{ s.SupplierName }}</option>
                  </select>
                  <i class="fa-solid fa-chevron-down select-caret"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ══ PRODUCT DETAILS ══ -->
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-icon form-card-icon--green"><i class="fa-solid fa-circle-info"></i></div>
            <div>
              <h2 class="form-card-title">Product Details</h2>
              <p class="form-card-sub">Brand, description, material and finish.</p>
            </div>
          </div>
          <div class="form-card-body">
            <div class="form-grid-2">
              <div class="form-field">
                <label class="form-label" for="f-brand">Brand</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-registered input-icon"></i>
                  <input id="f-brand" v-model="form.Brand" type="text" class="form-input" placeholder="e.g. Tiger Steel" />
                </div>
              </div>
              <div class="form-field">
                <label class="form-label" for="f-desc">Description</label>
                <textarea id="f-desc" v-model="form.Description" class="form-textarea" rows="3" placeholder="Brief product description…"></textarea>
              </div>
            </div>

            <div class="form-grid-3 details-extra-row">
              <div class="form-field">
                <label class="form-label" for="f-mat">Material</label>
                <input id="f-mat" v-model="form.Material" type="text" class="form-input form-input--plain" placeholder="e.g. Galvanized Steel" />
              </div>
              <div class="form-field">
                <label class="form-label" for="f-prof">Profile / Type</label>
                <input id="f-prof" v-model="form.ProfileType" type="text" class="form-input form-input--plain" placeholder="e.g. Corrugated" />
              </div>
              <div class="form-field">
                <label class="form-label" for="f-color">Color</label>
                <input id="f-color" v-model="form.Color" type="text" class="form-input form-input--plain" placeholder="e.g. Red Oxide" />
              </div>
            </div>
          </div>
        </div>

        <!-- ══ SIZES, PRICES & STOCK ══ -->
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-icon form-card-icon--amber">
              <i class="fa-solid fa-ruler-combined"></i>
            </div>
            <div>
              <h2 class="form-card-title">Sizes, Prices &amp; Stock</h2>
              <p class="form-card-sub">Each size has its own specs, price, stock and reorder level. POS shows these per size.</p>
            </div>
            <span class="size-total-pill">{{ variants.length }} size{{ variants.length === 1 ? '' : 's' }}</span>
          </div>
          <div class="form-card-body">
            <div class="variant-list">
              <div v-for="(v, vi) in variants" :id="`variant-${v.uid}`" :key="v.uid" class="variant-card">
                <div class="variant-head">
                  <span class="variant-badge">Size {{ vi + 1 }}</span>
                  <span v-if="!v.VariantID" class="variant-new">New</span>
                  <span class="variant-label">{{ variantLabel(v) }}</span>
                  <button type="button" class="variant-action" title="Duplicate this size" @click="duplicateVariant(vi)">
                    <i class="fa-solid fa-clone"></i>
                  </button>
                  <button
                    v-if="variants.length > 1"
                    type="button"
                    class="variant-action variant-action--danger"
                    title="Remove this size"
                    @click="removeVariant(vi)"
                  >
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>

                <div class="spec-rows">
                  <div v-for="(s, si) in v.specs" :key="s.uid" class="spec-row">
                    <div class="select-wrap">
                      <select v-model="s.type" class="form-select form-select--plain" aria-label="Specification type" @change="onSpecTypeChange(s)">
                        <option v-for="t in SPEC_TYPES" :key="t.key" :value="t.key">{{ t.label }}</option>
                      </select>
                      <i class="fa-solid fa-chevron-down select-caret"></i>
                    </div>
                    <input
                      v-model="s.value"
                      type="number"
                      step="0.01"
                      class="form-input form-input--plain"
                      placeholder="Value"
                      aria-label="Specification value"
                    />
                    <div class="select-wrap">
                      <select v-model="s.unit" class="form-select form-select--plain" aria-label="Specification unit">
                        <option v-for="u in specUnits(s.type)" :key="u" :value="u">{{ u }}</option>
                      </select>
                      <i class="fa-solid fa-chevron-down select-caret"></i>
                    </div>
                    <button type="button" class="spec-remove" title="Remove specification" @click="removeSpec(v, si)">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                  </div>

                  <button type="button" class="add-spec-btn" @click="addSpec(v)">
                    <i class="fa-solid fa-plus"></i> Add specification
                  </button>
                </div>

                <div class="variant-prices-wrap">
                  <div class="form-grid-2">
                    <div class="form-field">
                      <label class="form-label">Selling Price <span class="req">*</span></label>
                      <div class="input-wrap input-wrap--prefix">
                        <span class="input-prefix">₱</span>
                        <input v-model="v.SellingPrice" type="number" step="0.01" min="0" class="form-input form-input--prefixed" placeholder="0.00" />
                      </div>
                    </div>
                    <div class="form-field">
                      <label class="form-label">Cost Price <span class="req">*</span></label>
                      <div class="input-wrap input-wrap--prefix">
                        <span class="input-prefix">₱</span>
                        <input v-model="v.CostPrice" type="number" step="0.01" min="0" class="form-input form-input--prefixed" placeholder="0.00" />
                      </div>
                    </div>
                  </div>
                  <p v-if="marginInfo(v)" class="margin-hint" :class="{ 'margin-hint--loss': marginInfo(v).loss }">
                    <i class="fa-solid" :class="marginInfo(v).loss ? 'fa-triangle-exclamation' : 'fa-arrow-trend-up'"></i>
                    <template v-if="marginInfo(v).loss">Selling below cost: loses ₱{{ marginInfo(v).amount }} per unit.</template>
                    <template v-else>Profit ₱{{ marginInfo(v).amount }} per unit · {{ marginInfo(v).pct }}% margin</template>
                  </p>
                </div>

                <!-- Stock for THIS size -->
                <div class="variant-stock">
                  <p class="variant-stock-title"><i class="fa-solid fa-boxes-stacked"></i> Stock for this size</p>
                  <div class="form-grid-3">
                    <template v-if="v.VariantID">
                      <div class="form-field">
                        <label class="form-label">Current stock</label>
                        <div class="stock-readout" :class="stockClass(v)">{{ v.Stock }}</div>
                      </div>
                      <div class="form-field">
                        <label class="form-label">Adjust stock (+/−)</label>
                        <input v-model.number="v.StockAdjustment" type="number" step="1" class="form-input form-input--plain" placeholder="0" />
                        <p class="form-hint">New stock: <strong>{{ newStock(v) }}</strong></p>
                      </div>
                    </template>
                    <div v-else class="form-field">
                      <label class="form-label">Opening stock</label>
                      <input v-model.number="v.OpeningStock" type="number" min="0" step="1" class="form-input form-input--plain" placeholder="0" />
                    </div>
                    <div class="form-field">
                      <label class="form-label">Reorder level</label>
                      <input v-model.number="v.ReorderLevel" type="number" min="0" step="1" class="form-input form-input--plain" placeholder="5" />
                      <p class="form-hint">Low-stock alert at or below this.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <button type="button" class="btn btn--ghost add-variant-btn" @click="addVariant">
              <i class="fa-solid fa-plus"></i> Add Size &amp; Price
            </button>
          </div>
        </div>

        <!-- ══ FLOATING SAVE BAR (sticks to the bottom of the screen) ══ -->
        <div class="save-bar" :class="{ 'save-bar--dirty': dirty }">
          <div class="save-bar-status">
            <span class="save-dot" :class="{ 'save-dot--dirty': dirty }"></span>
            <div class="save-bar-text">
              <strong>{{ dirty ? 'Unsaved changes' : 'No changes yet' }}</strong>
              <span class="save-bar-meta">{{ variants.length }} size{{ variants.length === 1 ? '' : 's' }} · {{ totalAfter }} units in stock after saving</span>
            </div>
          </div>
          <div class="save-bar-actions">
            <RouterLink to="/products" class="btn btn--ghost">
              <i class="fa-solid fa-xmark"></i> <span class="btn-text">Cancel</span>
            </RouterLink>
            <button type="submit" class="btn btn--primary" :disabled="loading" title="Save (Ctrl+S)">
              <i v-if="loading" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              {{ loading ? 'Saving…' : 'Save Changes' }}
              <kbd class="kbd">Ctrl S</kbd>
            </button>
          </div>
        </div>

      </form>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'

const route     = useRoute()
const router    = useRouter()
const productId = route.params.id

const product    = ref(null)
const categories = ref([])
const suppliers  = ref([])
const units      = ['Piece', 'Box', 'Pack', 'Set', 'Roll', 'Kg', 'g', 'L', 'mL']

const formEl    = ref(null)
const fileInput = ref(null)

// Price, dimension AND stock fields now live per size in the
// "Sizes, Prices & Stock" section (variants).
const form = ref({
  ProductName: '', SKU: '', Barcode: '', Unit: '', IsReturnable: false,
  CategoryID: '', SupplierID: '', Brand: '', Description: '',
  Material: '', ProfileType: '', Color: '',
})

/* When the product has no SKU, show an example built from its name
   (e.g. "Tiger Steel" → TIG-00001). The real one is generated by the
   server when you save. */
const skuPlaceholder = computed(() => {
  const letters = (form.value.ProductName || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 3)
  const prefix  = (letters || 'XXX').padEnd(3, 'X')
  return `e.g. ${prefix}-00001 (auto-generated on save)`
})

/* ────────────────────────────────────────────────────────────
   SIZES, PRICES & STOCK (variants) — same model as ProductCreate.vue.
   Each variant = dynamic specs (type + value + unit), its own
   selling and cost price, and its own stock:
     - existing size : Stock (read-only) + StockAdjustment (+/-)
     - new size      : OpeningStock
     - both          : ReorderLevel
   VariantID is kept for existing sizes so the backend updates them in
   place instead of recreating them.
──────────────────────────────────────────────────────────── */
const SPEC_TYPES = [
  { key: 'Length',    label: 'Length',    units: ['ft', 'm', 'cm', 'in', 'mm'] },
  { key: 'Width',     label: 'Width',     units: ['ft', 'm', 'cm', 'in', 'mm'] },
  { key: 'Thickness', label: 'Thickness', units: ['mm', 'in', 'gauge'] },
  { key: 'Diameter',  label: 'Diameter',  units: ['mm', 'in', 'cm'] },
  { key: 'Weight',    label: 'Weight',    units: ['kg', 'g', 'lb'] },
]

let uidCounter = 0
const nextUid = () => ++uidCounter

const specUnits = (type) => SPEC_TYPES.find(t => t.key === type)?.units ?? []
const newSpec = (type = 'Length') => ({ uid: nextUid(), type, value: '', unit: specUnits(type)[0] })
const newVariant = () => ({
  uid: nextUid(), VariantID: null, specs: [], SellingPrice: '', CostPrice: '',
  Stock: 0, ReorderLevel: '', OpeningStock: '', StockAdjustment: '',
})

const variants = ref([newVariant()])

const adjOf    = v => Number(v.StockAdjustment) || 0
const newStock = v => v.VariantID ? (Number(v.Stock) || 0) + adjOf(v) : (Number(v.OpeningStock) || 0)
const totalAfter = computed(() => variants.value.reduce((sum, v) => sum + newStock(v), 0))
const stockClass = v => {
  const s = Number(v.Stock) || 0
  const t = Number(v.ReorderLevel) > 0 ? Number(v.ReorderLevel) : 5
  return s <= 0 ? 'stock-readout--oos' : s <= t ? 'stock-readout--low' : 'stock-readout--ok'
}

/** Profit per unit + margin, shown under the price fields. */
function marginInfo(v) {
  const sell = Number(v.SellingPrice)
  if (v.SellingPrice === '' || v.SellingPrice === null || !(sell > 0)) return null
  if (v.CostPrice === '' || v.CostPrice === null) return null
  const diff = sell - Number(v.CostPrice)
  return {
    loss: diff < 0,
    amount: Math.abs(diff).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
    pct: ((diff / sell) * 100).toFixed(1),
  }
}

function scrollToVariant(uid) {
  nextTick(() => document.getElementById(`variant-${uid}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' }))
}
function addVariant() {
  const v = newVariant()
  variants.value.push(v)
  scrollToVariant(v.uid)
}
/** Copies specs + prices into a brand-new size (change the specs, then save). */
function duplicateVariant(i) {
  const src = variants.value[i]
  const copy = {
    ...newVariant(),
    specs: src.specs.map(s => ({ ...s, uid: nextUid() })),
    SellingPrice: src.SellingPrice,
    CostPrice: src.CostPrice,
    ReorderLevel: src.ReorderLevel,
  }
  variants.value.splice(i + 1, 0, copy)
  scrollToVariant(copy.uid)
}
function removeVariant(i) {
  const v = variants.value[i]
  if (v.VariantID && Number(v.Stock) > 0) {
    flashError(`"${variantLabel(v)}" still has ${v.Stock} in stock. Adjust it to 0 before removing the size.`)
    return
  }
  variants.value.splice(i, 1)
}
function addSpec(v)          { v.specs.push(newSpec()) }
function removeSpec(v, i)    { v.specs.splice(i, 1) }
function onSpecTypeChange(s) { s.unit = specUnits(s.type)[0] }

/** e.g. "8 ft × 3 ft × 0.40 mm" — shown live here and in the POS. */
function variantLabel(v) {
  const parts = v.specs
    .filter(s => s.value !== '' && s.value !== null)
    .map(s => `${Number(s.value)} ${s.unit === 'gauge' ? 'ga' : s.unit}`)
  return parts.length ? parts.join(' × ') : 'Standard'
}

function validateVariants() {
  if (!variants.value.length) return 'Add at least one size and price.'
  const seen = new Set()
  const isInt = x => x === '' || x === null || Number.isInteger(Number(x))
  for (let i = 0; i < variants.value.length; i++) {
    const v = variants.value[i]
    const n = i + 1
    if (v.specs.some(s => s.value === '' || s.value === null)) return `Size ${n}: fill in or remove empty specifications.`
    if (v.SellingPrice === '' || v.SellingPrice === null || Number(v.SellingPrice) <= 0) return `Size ${n}: enter a selling price.`
    if (v.CostPrice === '' || v.CostPrice === null || Number(v.CostPrice) < 0)           return `Size ${n}: enter a cost price.`

    if (!isInt(v.ReorderLevel) || Number(v.ReorderLevel) < 0) return `Size ${n}: reorder level must be a whole number, 0 or more.`
    if (v.VariantID) {
      if (!isInt(v.StockAdjustment)) return `Size ${n}: stock adjustment must be a whole number.`
      if (newStock(v) < 0)           return `Size ${n}: stock cannot go below 0.`
    } else if (!isInt(v.OpeningStock) || Number(v.OpeningStock) < 0) {
      return `Size ${n}: opening stock must be a whole number, 0 or more.`
    }

    const label = variantLabel(v)
    if (seen.has(label)) return `Size ${n} duplicates another size ("${label}").`
    seen.add(label)
  }
  return ''
}

/* ── STATUS / TOASTS ── */
const success      = ref('')
const error        = ref('')
const loading      = ref(false)
const pageLoading  = ref(true)
const existingImage = ref(null)
const newImageFile  = ref(null)
const previewImage  = ref(null)
const dragging      = ref(false)
let toastTimer = null

function flashError(msg) {
  success.value = ''
  error.value = msg
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { error.value = '' }, 6000)
}

/* ── IMAGE ── */
const MAX_IMAGE_MB = 5

function setImage(file) {
  if (!file) return
  if (!file.type.startsWith('image/')) { flashError('Please choose an image file (PNG or JPG).'); return }
  if (file.size > MAX_IMAGE_MB * 1024 * 1024) { flashError(`That image is larger than ${MAX_IMAGE_MB} MB.`); return }
  if (previewImage.value) URL.revokeObjectURL(previewImage.value)
  newImageFile.value = file
  previewImage.value = URL.createObjectURL(file)
}
function handleImageUpload(e) {
  setImage(e.target.files?.[0])
  e.target.value = ''   // lets you pick the same file again later
}
function onDrop(e) {
  dragging.value = false
  setImage(e.dataTransfer?.files?.[0])
}
/** Throws away a newly picked image and goes back to the saved one. */
function discardNewImage() {
  if (previewImage.value) URL.revokeObjectURL(previewImage.value)
  previewImage.value = null
  newImageFile.value = null
  if (fileInput.value) fileInput.value.value = ''
}

/* ── UNSAVED-CHANGES TRACKING ── */
const snapshot = () => JSON.stringify({
  form: form.value,
  image: !!newImageFile.value,
  variants: variants.value.map(v => ({
    id: v.VariantID,
    specs: v.specs.map(s => [s.type, String(s.value), s.unit]),
    sell: String(v.SellingPrice), cost: String(v.CostPrice),
    reorder: String(v.ReorderLevel), opening: String(v.OpeningStock), adj: String(v.StockAdjustment),
  })),
})
const baseline = ref('')
const dirty = computed(() => baseline.value !== '' && snapshot() !== baseline.value)

onBeforeRouteLeave(() => {
  if (dirty.value && !window.confirm('You have unsaved changes. Leave without saving?')) return false
})
function onBeforeUnload(e) {
  if (dirty.value) { e.preventDefault(); e.returnValue = '' }
}
// Ctrl+S / Cmd+S saves (runs the browser's required-field checks first)
function onKeydown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
    e.preventDefault()
    if (!loading.value && !pageLoading.value) formEl.value?.requestSubmit()
  }
}

/* ── LOAD ── */
async function loadData() {
  try {
    const [p, cats, sups] = await Promise.all([
      api.get(`/api/products/${productId}`),
      api.get('/api/categories'),
      api.get('/api/suppliers'),
    ])
    product.value      = p.data
    categories.value   = cats.data.categories
    suppliers.value    = sups.data.suppliers

    Object.assign(form.value, {
      ProductName: p.data.ProductName, SKU: p.data.SKU ?? '',
      Barcode: p.data.Barcode ?? '',
      Unit: p.data.Unit, IsReturnable: p.data.IsReturnable,
      CategoryID: p.data.category?.CategoryID ?? '',
      SupplierID: p.data.suppliers?.[0]?.SupplierID ?? '',
      Brand: p.data.Brand ?? '', Description: p.data.Description ?? '',
      Material: p.data.Material ?? '', ProfileType: p.data.ProfileType ?? '', Color: p.data.Color ?? '',
    })

    // Load existing sizes. Products created before sizes existed arrive
    // with a single "Standard" size (created by the migration backfill).
    const loaded = (p.data.variants ?? []).map(v => ({
      uid: nextUid(),
      VariantID: v.VariantID,
      specs: (v.Specs ?? []).map(s => ({
        uid: nextUid(),
        type: s.Type,
        value: s.Value,
        unit: s.Unit,
      })),
      SellingPrice: v.SellingPrice,
      CostPrice: v.CostPrice,
      Stock: v.Stock ?? 0,
      ReorderLevel: v.ReorderLevel ?? 0,
      OpeningStock: '',
      StockAdjustment: '',
    }))
    variants.value = loaded.length
      ? loaded
      : [{ ...newVariant(), SellingPrice: p.data.SellingPrice ?? '', CostPrice: p.data.CostPrice ?? '' }]

    existingImage.value = p.data.ImageURL
    baseline.value = snapshot()
  } catch {
    flashError('Failed to load product data.')
  } finally {
    pageLoading.value = false
  }
}

/* ── SAVE ── */
async function updateProduct() {
  success.value = ''; error.value = ''
  if (loading.value) return

  const variantError = validateVariants()
  if (variantError) { flashError(variantError); return }

  loading.value = true
  try {
    const fd = new FormData()
    fd.append('IsReturnable', form.value.IsReturnable ? 1 : 0)
    for (const key in form.value) {
      // SKU is locked: the server keeps the stored one (or generates one if missing).
      if (key === 'IsReturnable' || key === 'SKU') continue
      fd.append(key, form.value[key] ?? '')
    }

    const payload = variants.value.map(v => ({
      VariantID: v.VariantID,
      Label: variantLabel(v),
      Specs: v.specs.map(s => ({ Type: s.type, Value: Number(s.value), Unit: s.unit })),
      SellingPrice: Number(v.SellingPrice),
      CostPrice: Number(v.CostPrice),
      ReorderLevel: (v.ReorderLevel === '' || v.ReorderLevel == null) ? null : Number(v.ReorderLevel),
      OpeningStock: v.VariantID ? 0 : (Number(v.OpeningStock) || 0),
      StockAdjustment: v.VariantID ? adjOf(v) : 0,
    }))
    fd.append('Variants', JSON.stringify(payload))
    // Backward-compat: code that still reads a single price gets the first size's price.
    fd.append('SellingPrice', payload[0].SellingPrice)
    fd.append('CostPrice', payload[0].CostPrice)

    if (newImageFile.value) fd.append('Product_Image', newImageFile.value)
    fd.append('_method', 'PUT')
    await api.post(`/api/products/${productId}`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    baseline.value = snapshot()   // saved → nothing is "unsaved" any more
    success.value = 'Product updated successfully!'
    setTimeout(() => router.push('/products'), 800)
  } catch (err) {
    flashError(err?.response?.data?.message || 'Update failed. Please check your inputs.')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('beforeunload', onBeforeUnload)
})
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('beforeunload', onBeforeUnload)
  clearTimeout(toastTimer)
  if (previewImage.value) URL.revokeObjectURL(previewImage.value)
})
</script>

<style scoped>
/* ── TOKEN BRIDGE ── */
.prod-page {
  --p-bg:             var(--c-bg);
  --p-surface:        var(--c-surface);
  --p-surface-raised: var(--c-surface-raised);
  --p-surface-sunken: var(--c-surface-sunken);
  --p-border:         var(--c-border);
  --p-border-strong:  var(--c-border-strong);
  --p-text-primary:   var(--c-text-primary);
  --p-text-secondary: var(--c-text-secondary);
  --p-text-muted:     var(--c-text-muted);
  --p-text-faint:     var(--c-text-faint);
  --p-accent:         var(--c-accent);
  --p-accent-soft:    var(--c-accent-soft);
  --p-accent-border:  var(--c-accent-border);
  --p-ring:           var(--c-accent-ring);
  --radius: 16px;

  min-height: 100%;
  background: var(--p-bg);
  padding: 28px 28px 32px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 20px;
  transition: background-color .22s ease;
}

/* Cards inside the <form> need the same vertical rhythm as the page */
.prod-page form { display: flex; flex-direction: column; gap: 20px; }

.page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--p-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--p-text-primary); letter-spacing: -.03em; margin: 0; transition: color .22s; }
.page-sub     { margin: 6px 0 0; font-size: 13.5px; color: var(--p-text-muted); display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
.sku-chip {
  font-size: 11.5px; font-weight: 700; padding: 2px 10px; border-radius: 20px;
  background: var(--p-surface-raised); border: 1px solid var(--p-border); color: var(--p-text-secondary);
  font-variant-numeric: tabular-nums;
}
.sku-chip--empty { color: #d97706; border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.10); }

.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--p-ring); }
.btn--ghost   { background: var(--p-surface); border-color: var(--p-border-strong); color: var(--p-text-secondary); }
.btn--ghost:hover { background: var(--p-surface-raised); border-color: var(--p-accent-border); color: var(--p-text-primary); }
.btn--primary {
  background: linear-gradient(135deg, var(--p-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--primary:disabled { opacity: .6; cursor: not-allowed; filter: none; transform: none; box-shadow: none; }
.kbd {
  font-family: inherit; font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 5px;
  background: rgba(255,255,255,.22); color: #fff; margin-left: 2px;
}

/* TOASTS: fixed to the screen so they are never scrolled out of view */
.toast-stack {
  position: fixed; top: 16px; right: 16px; z-index: 10001;
  display: flex; flex-direction: column; gap: 10px;
  width: min(420px, calc(100vw - 32px)); pointer-events: none;
}
.alert {
  display: flex; align-items: center; gap: 10px; pointer-events: auto;
  padding: 12px 16px; border-radius: 12px;
  font-size: 13.5px; font-weight: 500; border: 1px solid transparent;
  box-shadow: 0 10px 30px rgba(0,0,0,.18);
}
.alert--success {
  background: linear-gradient(rgba(16,185,129,.12), rgba(16,185,129,.12)), var(--p-surface);
  border-color: rgba(16,185,129,.35); color: #059669;
}
.alert--error {
  background: linear-gradient(rgba(244,63,94,.10), rgba(244,63,94,.10)), var(--p-surface);
  border-color: rgba(244,63,94,.35); color: #e11d48;
}
html[data-theme="dark"] .alert--success { color: #4ADE80; }
html[data-theme="dark"] .alert--error   { color: #F87171; }
.alert-close { background: none; border: none; cursor: pointer; color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 2px; transition: opacity .15s; }
.alert-close:hover { opacity: 1; }
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-8px); }

/* SKELETON */
.skeleton-card { padding: 24px; }
.skeleton-header { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
.skeleton-icon   { width: 40px; height: 40px; border-radius: 11px; background: var(--p-border); animation: shimmer 1.4s infinite; flex-shrink: 0; }
.skeleton-lines  { display: flex; flex-direction: column; gap: 8px; }
.skeleton-line   { height: 12px; border-radius: 6px; background: var(--p-border); animation: shimmer 1.4s infinite; }
.skeleton-line--wide   { width: 160px; }
.skeleton-line--narrow { width: 100px; }
.skeleton-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.skeleton-field { height: 42px; border-radius: 10px; background: var(--p-border); animation: shimmer 1.4s infinite; }
@keyframes shimmer { 0%,100%{opacity:.6} 50%{opacity:.3} }

/* FORM CARD */
.form-card {
  background: var(--p-surface); border: 1px solid var(--p-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden;
  transition: background-color .22s, border-color .22s;
  margin-bottom: 0;
}
.form-card-header {
  display: flex; align-items: center; gap: 14px;
  padding: 18px 24px 14px; border-bottom: 1px solid var(--p-border);
  transition: border-color .22s;
}
.form-card-icon {
  width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 16px;
}
.form-card-icon--blue   { background: rgba(59,130,246,.12); color: #3b82f6; }
.form-card-icon--violet { background: rgba(99,102,241,.12); color: #6366f1; }
.form-card-icon--green  { background: rgba(16,185,129,.12); color: #10b981; }
.form-card-icon--amber  { background: rgba(245,158,11,.12); color: #f59e0b; }
html[data-theme="dark"] .form-card-icon--blue   { background: rgba(59,130,246,.18); }
html[data-theme="dark"] .form-card-icon--violet { background: rgba(99,102,241,.18); }
html[data-theme="dark"] .form-card-icon--green  { background: rgba(16,185,129,.18); }
html[data-theme="dark"] .form-card-icon--amber  { background: rgba(245,158,11,.18); }
.form-card-title { font-size: 15px; font-weight: 700; color: var(--p-text-primary); margin: 0 0 2px; transition: color .22s; }
.form-card-sub   { font-size: 12.5px; color: var(--p-text-muted); margin: 0; }
.form-card-body  { padding: 22px 24px; }
.size-total-pill {
  margin-left: auto; flex-shrink: 0; font-size: 11.5px; font-weight: 700;
  padding: 4px 12px; border-radius: 20px; background: var(--p-accent-soft); color: var(--p-accent);
}

.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.form-stack  { display: flex; flex-direction: column; gap: 16px; }
.details-extra-row { margin-top: 18px; }
.form-field  { display: flex; flex-direction: column; gap: 7px; }
.form-label  { font-size: 12.5px; font-weight: 600; color: var(--p-text-secondary); letter-spacing: .01em; transition: color .22s; }
.req  { color: var(--p-accent); }
.form-hint { font-size: 11.5px; color: var(--p-text-faint); margin: 0; }
.form-hint strong { color: var(--p-text-secondary); }

.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon {
  position: absolute; left: 13px; font-size: 12px;
  color: var(--p-text-faint); pointer-events: none; z-index: 1; transition: color .22s;
}
.input-lock { position: absolute; right: 13px; font-size: 11px; color: var(--p-text-faint); pointer-events: none; }
.input-prefix {
  position: absolute; left: 13px; font-size: 14px; font-weight: 700;
  color: var(--p-text-faint); pointer-events: none; z-index: 1;
}
.form-input {
  width: 100%; height: 42px; padding: 0 14px 0 36px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: var(--p-surface-sunken); color: var(--p-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-input:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); background: var(--p-surface); }
.input-wrap:focus-within .input-icon { color: var(--p-accent); }
.form-input::placeholder { color: var(--p-text-faint); }
.form-input--disabled { background: var(--p-surface-raised); color: var(--p-text-faint); cursor: not-allowed; padding-right: 34px; }
.form-input--prefixed { padding-left: 30px; }
.form-input--plain { padding-left: 14px; }

.form-textarea {
  width: 100%; padding: 10px 14px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: var(--p-surface-sunken); color: var(--p-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none; resize: vertical; line-height: 1.6;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-textarea:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); background: var(--p-surface); }
.form-textarea::placeholder { color: var(--p-text-faint); }

.select-wrap { position: relative; display: flex; align-items: center; }
.form-select {
  width: 100%; height: 42px; padding: 0 32px 0 36px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: var(--p-surface-sunken); color: var(--p-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  appearance: none; -webkit-appearance: none; cursor: pointer;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-select:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); background: var(--p-surface); }
.form-select--plain { padding-left: 12px; }
.select-caret { position: absolute; right: 10px; font-size: 9px; color: var(--p-text-faint); pointer-events: none; }

.toggle-row { display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none; }
.toggle-track {
  width: 44px; height: 24px; border-radius: 999px; flex-shrink: 0; padding: 0; border: none;
  background: var(--p-border-strong); transition: background-color .22s ease;
  position: relative; cursor: pointer;
}
.toggle-track:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--p-ring); }
.toggle-track--on { background: var(--p-accent); }
.toggle-thumb {
  position: absolute; top: 3px; left: 3px;
  width: 18px; height: 18px; border-radius: 50%;
  background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.18);
  transition: transform .22s cubic-bezier(.4,0,.2,1);
}
.toggle-thumb--on { transform: translateX(20px); }
.toggle-label { font-size: 13.5px; font-weight: 600; color: var(--p-text-primary); margin: 0 0 1px; transition: color .22s; }
.toggle-sub   { font-size: 12px; color: var(--p-text-faint); margin: 0; }

.upload-zone {
  position: relative; width: 100%; min-height: 180px;
  border: 2px dashed var(--p-border-strong); border-radius: 14px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  overflow: hidden; cursor: pointer; background: var(--p-surface-sunken);
  transition: border-color .18s, background-color .22s, transform .15s;
}
.upload-zone:hover, .upload-zone--drag { border-color: var(--p-accent); background: var(--p-accent-soft); }
.upload-zone--drag { transform: scale(1.01); }
.upload-zone--has-image { border-style: solid; border-color: var(--p-border); }
.upload-preview { max-height: 160px; max-width: 100%; object-fit: contain; border-radius: 8px; }
.upload-placeholder { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 24px; text-align: center; }
.upload-icon { font-size: 32px; color: var(--p-text-faint); }
.upload-text { font-size: 13px; font-weight: 600; color: var(--p-text-muted); margin: 0; }
.upload-hint { font-size: 11.5px; color: var(--p-text-faint); margin: 0; }
.upload-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.upload-change {
  position: absolute; right: 10px; bottom: 10px; pointer-events: none;
  display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700;
  padding: 5px 11px; border-radius: 20px; background: rgba(15,23,42,.72); color: #fff;
  opacity: 0; transition: opacity .15s;
}
.upload-zone:hover .upload-change { opacity: 1; }
.upload-remove {
  align-self: flex-start; margin-top: 6px;
  background: none; border: none; cursor: pointer;
  font-size: 12px; font-weight: 600; color: var(--p-text-muted); font-family: inherit;
  display: flex; align-items: center; gap: 5px; padding: 2px 0; transition: color .15s;
}
.upload-remove:hover { color: var(--p-accent); }

/* SIZES, PRICES & STOCK */
.variant-list { display: flex; flex-direction: column; gap: 16px; }
.variant-card {
  border: 1.5px solid var(--p-border); border-radius: 14px;
  background: var(--p-surface-sunken); padding: 16px;
  display: flex; flex-direction: column; gap: 14px; scroll-margin: 90px;
}
.variant-head { display: flex; align-items: center; gap: 10px; }
.variant-badge {
  font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
  padding: 3px 10px; border-radius: 999px;
  background: var(--p-accent-soft); color: var(--p-accent);
}
.variant-new {
  font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
  padding: 3px 8px; border-radius: 999px; background: rgba(16,185,129,.14); color: #059669;
}
.variant-label { font-size: 13.5px; font-weight: 600; color: var(--p-text-primary); flex: 1; min-width: 0; overflow-wrap: anywhere; }
.variant-action, .spec-remove {
  width: 30px; height: 30px; flex-shrink: 0; border: none; border-radius: 8px;
  background: transparent; color: var(--p-text-faint); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 13px;
  transition: background-color .15s, color .15s;
}
.variant-action:hover { background: var(--p-accent-soft); color: var(--p-accent); }
.variant-action--danger:hover, .spec-remove:hover { background: rgba(244,63,94,.12); color: #f43f5e; }

.spec-rows { display: flex; flex-direction: column; gap: 8px; }
.spec-row  { display: grid; grid-template-columns: 1.3fr 1fr .9fr auto; gap: 8px; align-items: center; }
.add-spec-btn {
  align-self: flex-start; display: inline-flex; align-items: center; gap: 6px;
  height: 34px; padding: 0 14px; border-radius: 10px; cursor: pointer;
  border: 1.5px dashed var(--p-border-strong); background: transparent;
  color: var(--p-text-muted); font-size: 12.5px; font-weight: 600; font-family: inherit;
  transition: border-color .15s, color .15s, background-color .15s;
}
.add-spec-btn:hover { border-color: var(--p-accent); color: var(--p-accent); background: var(--p-accent-soft); }
.variant-prices-wrap { padding-top: 14px; border-top: 1px dashed var(--p-border-strong); display: flex; flex-direction: column; gap: 10px; }
.margin-hint {
  margin: 0; display: inline-flex; align-items: center; gap: 7px; align-self: flex-start;
  font-size: 12px; font-weight: 600; padding: 4px 11px; border-radius: 20px;
  background: rgba(16,185,129,.12); color: #059669;
}
.margin-hint--loss { background: rgba(244,63,94,.12); color: #e11d48; }
html[data-theme="dark"] .margin-hint { color: #4ADE80; }
html[data-theme="dark"] .margin-hint--loss { color: #F87171; }
.add-variant-btn { margin-top: 16px; width: 100%; justify-content: center; border-style: dashed; }

/* per-size stock block */
.variant-stock { padding-top: 14px; border-top: 1px dashed var(--p-border-strong); }
.variant-stock-title { margin: 0 0 10px; font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--p-text-muted); display: flex; align-items: center; gap: 7px; }
.stock-readout { height: 42px; display: flex; align-items: center; padding: 0 14px; border-radius: 10px; font-size: 15px; font-weight: 800; border: 1.5px solid var(--p-border); background: var(--p-surface-raised); font-variant-numeric: tabular-nums; }
.stock-readout--ok  { color: #10b981; }
.stock-readout--low { color: #f59e0b; }
.stock-readout--oos { color: #f43f5e; }

/* FLOATING SAVE BAR — sticks to the bottom of the screen while the form is on screen */
.save-bar {
  position: sticky; bottom: 16px; z-index: 30;
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  padding: 12px 14px 12px 18px; border-radius: 16px;
  background: var(--p-surface); border: 1px solid var(--p-border-strong);
  box-shadow: 0 12px 34px rgba(0,0,0,.18), 0 2px 8px rgba(0,0,0,.08);
  transition: border-color .2s, box-shadow .2s;
}
.save-bar--dirty { border-color: var(--p-accent); box-shadow: 0 12px 34px rgba(0,0,0,.20), 0 0 0 3px var(--p-ring); }
.save-bar-status { display: flex; align-items: center; gap: 12px; min-width: 0; }
.save-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; background: var(--p-border-strong); }
.save-dot--dirty { background: #f59e0b; box-shadow: 0 0 0 4px rgba(245,158,11,.22); animation: dot-pulse 1.6s ease-in-out infinite; }
@keyframes dot-pulse { 0%,100% { box-shadow: 0 0 0 3px rgba(245,158,11,.22); } 50% { box-shadow: 0 0 0 6px rgba(245,158,11,.10); } }
.save-bar-text { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.save-bar-text strong { font-size: 13.5px; font-weight: 700; color: var(--p-text-primary); }
.save-bar-meta { font-size: 12px; color: var(--p-text-muted); }
.save-bar-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

@media (max-width: 800px) {
  .prod-page { padding: 16px 16px 24px; }
  .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
  .spec-row { grid-template-columns: 1fr 1fr; }
  .spec-row .spec-remove { grid-column: 2; justify-self: end; }
  .skeleton-grid { grid-template-columns: 1fr; }
  .save-bar { bottom: 10px; padding: 10px; border-radius: 14px; }
  .save-bar-status { display: none; }
  .save-bar-actions { width: 100%; }
  .save-bar-actions .btn { flex: 1; justify-content: center; }
  .save-bar-actions .btn--ghost { flex: 0 0 auto; }
  .kbd { display: none; }
}
@media (prefers-reduced-motion: reduce) {
  .save-dot--dirty, .skeleton-icon, .skeleton-line, .skeleton-field { animation: none; }
  .alert-fade-enter-active, .alert-fade-leave-active { transition: none; }
}
</style>