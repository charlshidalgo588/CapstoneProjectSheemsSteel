<template>
  <Layout>
    <div class="prod-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Inventory</p>
          <h1 class="page-title">Add New Item</h1>
        </div>
        <RouterLink to="/products" class="btn btn--ghost">
          <i class="fa-solid fa-arrow-left"></i> Back to List
        </RouterLink>
      </div>

      <!-- ── ALERTS ── -->
      <transition name="alert-fade">
        <div v-if="errorMessage" class="alert alert--error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>{{ errorMessage }}</span>
          <button class="alert-close" @click="errorMessage = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>
      <transition name="alert-fade">
        <div v-if="successMessage" class="alert alert--success">
          <i class="fa-solid fa-circle-check"></i>
          <span>{{ successMessage }}</span>
        </div>
      </transition>

      <!-- ══ BASIC INFORMATION ══ -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-icon form-card-icon--blue">
            <i class="fa-solid fa-tag"></i>
          </div>
          <div>
            <h2 class="form-card-title">Basic Information</h2>
            <p class="form-card-sub">Name, SKU, unit and return policy.</p>
          </div>
        </div>
        <div class="form-card-body">
          <div class="form-grid-2">
            <!-- Left column -->
            <div class="form-stack">
              <div class="form-field">
                <label class="form-label">Product Name <span class="req">*</span></label>
                <div class="input-wrap">
                  <i class="fa-solid fa-cube input-icon"></i>
                  <input v-model="form.ProductName" type="text" class="form-input" placeholder="e.g. Corrugated Steel Sheet" required />
                </div>
              </div>

              <div class="form-field">
                <label class="form-label">SKU</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-barcode input-icon"></i>
                  <input :value="form.SKU" disabled class="form-input form-input--disabled" placeholder="Auto-generated" />
                </div>
                <p class="form-hint">SKU is automatically generated from the product name.</p>
              </div>

              <div class="form-field">
                <label class="form-label">Unit <span class="req">*</span></label>
                <div class="select-wrap">
                  <i class="fa-solid fa-scale-balanced input-icon"></i>
                  <select v-model="form.Unit" class="form-select" required>
                    <option value="">Select Unit</option>
                    <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                  </select>
                  <i class="fa-solid fa-chevron-down select-caret"></i>
                </div>
              </div>

              <label class="toggle-row">
                <div class="toggle-track" :class="{ 'toggle-track--on': form.IsReturnable }" @click="form.IsReturnable = !form.IsReturnable">
                  <div class="toggle-thumb" :class="{ 'toggle-thumb--on': form.IsReturnable }"></div>
                </div>
                <div>
                  <p class="toggle-label">Returnable Item</p>
                  <p class="toggle-sub">Allow this product to be returned after sale.</p>
                </div>
              </label>
            </div>

            <!-- Image upload -->
            <div class="form-field">
              <label class="form-label">Product Image</label>
              <div class="upload-zone" :class="{ 'upload-zone--has-image': preview }">
                <img v-if="preview" :src="preview" class="upload-preview" />
                <div v-else class="upload-placeholder">
                  <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                  <p class="upload-text">Click to upload or drag here</p>
                  <p class="upload-hint">PNG / JPG · Max 5 MB</p>
                </div>
                <input type="file" accept="image/*" class="upload-input" @change="uploadImage" />
              </div>
              <button v-if="preview" type="button" class="upload-remove" @click="preview = null; imageFile = null">
                <i class="fa-solid fa-xmark"></i> Remove image
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ CLASSIFICATION ══ -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-icon form-card-icon--violet">
            <i class="fa-solid fa-layer-group"></i>
          </div>
          <div>
            <h2 class="form-card-title">Classification</h2>
            <p class="form-card-sub">Category and supplier assignment.</p>
          </div>
        </div>
        <div class="form-card-body">
          <div class="form-grid-2">
            <div class="form-field">
              <label class="form-label">Category <span class="req">*</span></label>
              <div class="select-wrap">
                <i class="fa-solid fa-folder input-icon"></i>
                <select v-model="form.CategoryID" class="form-select" required>
                  <option value="">Select Category</option>
                  <option v-for="c in categories" :key="c.CategoryID" :value="c.CategoryID">{{ c.CategoryName }}</option>
                </select>
                <i class="fa-solid fa-chevron-down select-caret"></i>
              </div>
            </div>
            <div class="form-field">
              <label class="form-label">Supplier</label>
              <div class="select-wrap">
                <i class="fa-solid fa-truck-field input-icon"></i>
                <select v-model="form.SupplierID" class="form-select">
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
          <div class="form-card-icon form-card-icon--green">
            <i class="fa-solid fa-circle-info"></i>
          </div>
          <div>
            <h2 class="form-card-title">Product Details</h2>
            <p class="form-card-sub">Brand and description.</p>
          </div>
        </div>
        <div class="form-card-body">
          <div class="form-grid-2">
            <div class="form-field">
              <label class="form-label">Brand</label>
              <div class="input-wrap">
                <i class="fa-solid fa-registered input-icon"></i>
                <input v-model="form.Brand" type="text" class="form-input" placeholder="e.g. Tiger Steel" />
              </div>
            </div>
            <div class="form-field">
              <label class="form-label">Description</label>
              <textarea v-model="form.Description" class="form-textarea" rows="3" placeholder="Brief product description…"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ SPECIFICATIONS ══ -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-icon form-card-icon--amber">
            <i class="fa-solid fa-ruler-combined"></i>
          </div>
          <div>
            <h2 class="form-card-title">Specifications</h2>
            <p class="form-card-sub">Physical dimensions and material properties.</p>
          </div>
        </div>
        <div class="form-card-body">
          <div class="form-grid-3">
            <div class="form-field">
              <label class="form-label">Material</label>
              <input v-model="form.Material" type="text" class="form-input" placeholder="e.g. Galvanized Steel" />
            </div>
            <div class="form-field">
              <label class="form-label">Profile / Type</label>
              <input v-model="form.ProfileType" type="text" class="form-input" placeholder="e.g. Corrugated" />
            </div>
            <div class="form-field">
              <label class="form-label">Color</label>
              <input v-model="form.Color" type="text" class="form-input" placeholder="e.g. Red Oxide" />
            </div>

            <div class="form-field">
              <label class="form-label">Length</label>
              <div class="input-unit-row">
                <input v-model="form.Length" type="number" class="form-input" placeholder="0.00" />
                <div class="select-wrap select-wrap--unit">
                  <select v-model="form.LengthUnit" class="form-select">
                    <option value="ft">ft</option>
                    <option value="m">m</option>
                    <option value="cm">cm</option>
                    <option value="in">in</option>
                  </select>
                  <i class="fa-solid fa-chevron-down select-caret"></i>
                </div>
              </div>
            </div>

            <div class="form-field">
              <label class="form-label">Width</label>
              <div class="input-unit-row">
                <input v-model="form.Width" type="number" class="form-input" placeholder="0.00" />
                <div class="select-wrap select-wrap--unit">
                  <select v-model="form.WidthUnit" class="form-select">
                    <option value="ft">ft</option>
                    <option value="m">m</option>
                    <option value="cm">cm</option>
                    <option value="in">in</option>
                  </select>
                  <i class="fa-solid fa-chevron-down select-caret"></i>
                </div>
              </div>
            </div>

            <div class="form-field">
              <label class="form-label">
                Thickness / Gauge
                <span class="form-label-hint">mm or gauge</span>
              </label>
              <input v-model="form.Thickness" type="number" step="0.01" class="form-input" placeholder="e.g. 0.40" />
              <p class="form-hint">e.g. <strong>0.40</strong> mm or <strong>26</strong> gauge</p>
            </div>
          </div>

          <div class="form-field spec-weight-row">
            <label class="form-label">Weight</label>
            <div class="input-unit-row input-unit-row--sm">
              <input v-model="form.Weight" type="number" class="form-input" placeholder="0" />
              <div class="select-wrap select-wrap--unit">
                <select v-model="form.WeightUnit" class="form-select">
                  <option value="g">g</option>
                  <option value="kg">kg</option>
                  <option value="mL">mL</option>
                  <option value="L">L</option>
                </select>
                <i class="fa-solid fa-chevron-down select-caret"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ PRICING ══ -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-icon form-card-icon--orange">
            <i class="fa-solid fa-peso-sign"></i>
          </div>
          <div>
            <h2 class="form-card-title">Pricing</h2>
            <p class="form-card-sub">Selling and cost prices.</p>
          </div>
        </div>
        <div class="form-card-body">
          <div class="form-grid-2">
            <div class="form-field">
              <label class="form-label">Selling Price <span class="req">*</span></label>
              <div class="input-wrap input-wrap--prefix">
                <span class="input-prefix">₱</span>
                <input v-model="form.SellingPrice" type="number" step="0.01" class="form-input form-input--prefixed" placeholder="0.00" />
              </div>
            </div>
            <div class="form-field">
              <label class="form-label">Cost Price <span class="req">*</span></label>
              <div class="input-wrap input-wrap--prefix">
                <span class="input-prefix">₱</span>
                <input v-model="form.CostPrice" type="number" step="0.01" class="form-input form-input--prefixed" placeholder="0.00" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ INVENTORY ══ -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-icon form-card-icon--teal">
            <i class="fa-solid fa-boxes-stacked"></i>
          </div>
          <div>
            <h2 class="form-card-title">Inventory</h2>
            <p class="form-card-sub">Opening stock and reorder settings.</p>
          </div>
        </div>
        <div class="form-card-body">
          <div class="form-grid-2">
            <div class="form-field">
              <label class="form-label">Opening Stock</label>
              <input v-model="form.OpeningStock" type="number" class="form-input" placeholder="0" />
            </div>
            <div class="form-field">
              <label class="form-label">Reorder Level</label>
              <input v-model="form.ReorderLevel" type="number" class="form-input" placeholder="0" />
              <p class="form-hint">Alert will trigger when stock drops below this value.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ── FORM ACTIONS ── -->
      <div class="form-actions">
        <RouterLink to="/products" class="btn btn--ghost">
          <i class="fa-solid fa-xmark"></i> Cancel
        </RouterLink>
        <button @click="saveProduct" class="btn btn--primary" :disabled="saving">
          <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
          <i v-else class="fa-solid fa-floppy-disk"></i>
          {{ saving ? 'Saving…' : 'Save Product' }}
        </button>
      </div>

    </div>
  </Layout>
</template>

<script setup>
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'
import { ref, onMounted, watch } from 'vue'
import { useRouter, onBeforeRouteLeave } from 'vue-router'

const router = useRouter()

const form = ref({
  ProductName: '', SKU: '', Unit: '', IsReturnable: false,
  CategoryID: '', SupplierID: '', Brand: '', Description: '',
  Material: '', ProfileType: '', Color: '',
  Length: null, LengthUnit: 'ft', Width: null, WidthUnit: 'ft',
  Thickness: null, Weight: 0, WeightUnit: 'g',
  SellingPrice: '', CostPrice: '', OpeningStock: '', ReorderLevel: '',
})

const initialFormSnapshot = ref(JSON.stringify(form.value))
const preview      = ref(null)
const imageFile    = ref(null)
const units        = ['Piece', 'Box', 'Pack', 'Set', 'Roll', 'Kg', 'g', 'L', 'mL']
const categories   = ref([])
const suppliers    = ref([])
const errorMessage = ref('')
const successMessage = ref('')
const hasSaved     = ref(false)
const saving       = ref(false)

watch(() => form.value.ProductName, async (name) => {
  if (!name || name.length < 2) { form.value.SKU = ''; return }
  const res = await api.get(`/api/generate-sku/${encodeURIComponent(name)}`)
  form.value.SKU = res.data.sku
})

function uploadImage(e) {
  const file = e.target.files[0]
  if (!file) return
  imageFile.value = file
  preview.value = URL.createObjectURL(file)
}

async function loadCategories() { const r = await api.get('/api/categories'); categories.value = r.data.categories }
async function loadSuppliers()  { const r = await api.get('/api/suppliers');  suppliers.value  = r.data.suppliers  }

onMounted(async () => {
  await Promise.all([loadCategories(), loadSuppliers()])
  initialFormSnapshot.value = JSON.stringify(form.value)
})

onBeforeRouteLeave(() => {
  if (hasSaved.value) return true
  const current = JSON.stringify(form.value)
  if (current !== initialFormSnapshot.value) {
    return window.confirm('You have unsaved changes. Are you sure you want to leave?')
  }
  return true
})

async function saveProduct() {
  errorMessage.value = ''; saving.value = true
  try {
    const fd = new FormData()
    for (const key in form.value) {
      fd.append(key, key === 'IsReturnable' ? (form.value[key] ? 1 : 0) : (form.value[key] ?? ''))
    }
    if (imageFile.value) fd.append('Product_Image', imageFile.value)
    await api.post('/api/products', fd)
    hasSaved.value = true
    successMessage.value = 'Product saved successfully!'
    setTimeout(() => router.push('/products'), 800)
  } catch (err) {
    errorMessage.value = err?.response?.data?.message || 'Failed to save product.'
  } finally {
    saving.value = false
  }
}
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
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 20px;
  transition: background-color .22s ease;
}

/* PAGE HEADER */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--p-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--p-text-primary); letter-spacing: -.03em; margin: 0; transition: color .22s; }

/* BUTTONS */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn--ghost   { background: var(--p-surface); border-color: var(--p-border-strong); color: var(--p-text-secondary); }
.btn--ghost:hover { background: var(--p-surface-raised); border-color: var(--p-accent-border); color: var(--p-text-primary); }
.btn--primary {
  background: linear-gradient(135deg, var(--p-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--primary:disabled { opacity: .6; cursor: not-allowed; filter: none; transform: none; box-shadow: none; }

/* ALERTS */
.alert {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-radius: 12px;
  font-size: 13.5px; font-weight: 500; border: 1px solid transparent;
}
.alert--success { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); color: #10b981; }
.alert--error   { background: rgba(244,63,94,.09);  border-color: rgba(244,63,94,.22);  color: #f43f5e; }
html[data-theme="dark"] .alert--success { background: rgba(16,185,129,.14); color: #4ADE80; }
html[data-theme="dark"] .alert--error   { background: rgba(244,63,94,.14);  color: #F87171; }
.alert-close { background: none; border: none; cursor: pointer; color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 2px; transition: opacity .15s; }
.alert-close:hover { opacity: 1; }
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* FORM CARD */
.form-card {
  background: var(--p-surface); border: 1px solid var(--p-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden;
  transition: background-color .22s, border-color .22s;
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
.form-card-icon--orange { background: var(--p-accent-soft); color: var(--p-accent); }
.form-card-icon--teal   { background: rgba(20,184,166,.12); color: #14b8a6; }
html[data-theme="dark"] .form-card-icon--blue   { background: rgba(59,130,246,.18); }
html[data-theme="dark"] .form-card-icon--violet { background: rgba(99,102,241,.18); }
html[data-theme="dark"] .form-card-icon--green  { background: rgba(16,185,129,.18); }
html[data-theme="dark"] .form-card-icon--amber  { background: rgba(245,158,11,.18); }
html[data-theme="dark"] .form-card-icon--teal   { background: rgba(20,184,166,.18); }

.form-card-title { font-size: 15px; font-weight: 700; color: var(--p-text-primary); margin: 0 0 2px; transition: color .22s; }
.form-card-sub   { font-size: 12.5px; color: var(--p-text-muted); margin: 0; }
.form-card-body  { padding: 22px 24px; }

/* GRIDS */
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.form-stack  { display: flex; flex-direction: column; gap: 16px; }

/* FIELDS */
.form-field { display: flex; flex-direction: column; gap: 7px; }
.form-label { font-size: 12.5px; font-weight: 600; color: var(--p-text-secondary); letter-spacing: .01em; transition: color .22s; }
.form-label-hint { font-weight: 400; font-size: 11px; color: var(--p-text-faint); margin-left: 4px; }
.req { color: var(--p-accent); }
.form-hint { font-size: 11.5px; color: var(--p-text-faint); margin: 0; }

/* INPUTS */
.input-wrap { position: relative; display: flex; align-items: center; }
.input-wrap--prefix { }
.input-icon {
  position: absolute; left: 13px; font-size: 12px;
  color: var(--p-text-faint); pointer-events: none; z-index: 1; transition: color .22s;
}
.input-prefix {
  position: absolute; left: 13px; font-size: 14px; font-weight: 700;
  color: var(--p-text-faint); pointer-events: none; z-index: 1;
}
.form-input {
  width: 100%; height: 42px;
  padding: 0 14px 0 36px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: var(--p-surface-sunken); color: var(--p-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-input:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); background: var(--p-surface); }
.form-input:focus ~ .input-icon,
.input-wrap:focus-within .input-icon { color: var(--p-accent); }
.form-input::placeholder { color: var(--p-text-faint); }
.form-input--disabled { background: var(--p-surface-raised); color: var(--p-text-faint); cursor: not-allowed; }
.form-input--prefixed { padding-left: 30px; }

/* TEXTAREA */
.form-textarea {
  width: 100%; padding: 10px 14px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: var(--p-surface-sunken); color: var(--p-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none; resize: vertical; line-height: 1.6;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-textarea:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); background: var(--p-surface); }
.form-textarea::placeholder { color: var(--p-text-faint); }

/* SELECT */
.select-wrap { position: relative; display: flex; align-items: center; }
.select-wrap--unit { flex-shrink: 0; width: 80px; }
.form-select {
  width: 100%; height: 42px;
  padding: 0 32px 0 36px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: var(--p-surface-sunken); color: var(--p-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  appearance: none; -webkit-appearance: none; cursor: pointer;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-select:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); background: var(--p-surface); }
.select-caret {
  position: absolute; right: 10px; font-size: 9px;
  color: var(--p-text-faint); pointer-events: none;
}
.select-wrap--unit .form-select { padding-left: 12px; }

/* INPUT + UNIT ROW */
.input-unit-row { display: flex; gap: 8px; }
.input-unit-row .form-input { flex: 1; }
.input-unit-row--sm { width: 240px; }
.spec-weight-row { margin-top: 18px; }

/* TOGGLE */
.toggle-row { display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none; }
.toggle-track {
  width: 44px; height: 24px; border-radius: 999px; flex-shrink: 0;
  background: var(--p-border-strong);
  transition: background-color .22s ease;
  position: relative; cursor: pointer;
}
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

/* IMAGE UPLOAD */
.upload-zone {
  position: relative; width: 100%; min-height: 180px;
  border: 2px dashed var(--p-border-strong); border-radius: 14px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  overflow: hidden; cursor: pointer;
  background: var(--p-surface-sunken);
  transition: border-color .18s, background-color .22s;
}
.upload-zone:hover { border-color: var(--p-accent-border); background: var(--p-accent-soft); }
.upload-zone--has-image { border-style: solid; border-color: var(--p-border); }
.upload-preview { max-height: 160px; max-width: 100%; object-fit: contain; border-radius: 8px; }
.upload-placeholder { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 24px; text-align: center; }
.upload-icon { font-size: 32px; color: var(--p-text-faint); }
.upload-text { font-size: 13px; font-weight: 600; color: var(--p-text-muted); margin: 0; }
.upload-hint { font-size: 11.5px; color: var(--p-text-faint); margin: 0; }
.upload-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.upload-remove {
  align-self: flex-start; margin-top: 6px;
  background: none; border: none; cursor: pointer;
  font-size: 12px; font-weight: 600; color: #f43f5e; font-family: inherit;
  display: flex; align-items: center; gap: 5px; padding: 2px 0;
  transition: opacity .15s;
}
.upload-remove:hover { opacity: .7; }

/* FORM ACTIONS */
.form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 4px; }

/* RESPONSIVE */
@media (max-width: 800px) {
  .prod-page { padding: 16px 16px 48px; }
  .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
  .input-unit-row--sm { width: 100%; }
  .form-actions { flex-direction: column-reverse; align-items: stretch; }
  .btn { justify-content: center; }
}
</style>