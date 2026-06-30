<template>
  <Layout>
    <div class="pos-root">

      <!-- ══ LEFT — PRODUCT BROWSER ══ -->
      <div class="pos-left">

        <!-- Search + Filter Bar -->
        <div class="search-bar">
          <div class="search-field">
            <i class="fa-solid fa-magnifying-glass search-field-icon"></i>
            <input
              ref="searchInput"
              v-model="search"
              type="text"
              placeholder="Search products…"
              class="search-input"
            />
            <button v-if="search" @click="search = ''" class="search-clear">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <button
            class="scan-btn"
            :class="{ 'scan-btn--active': scannerActive }"
            @click="toggleScanner"
            title="Barcode Scanner"
          >
            <i class="fa-solid fa-barcode"></i>
            <span>{{ scannerActive ? 'Stop Scan' : 'Scan' }}</span>
          </button>

          <div class="filter-select-wrap">
            <i class="fa-solid fa-layer-group filter-icon"></i>
            <select v-model="selectedCategory" class="filter-select">
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat.CategoryID" :value="cat.CategoryID">
                {{ cat.CategoryName }}
              </option>
            </select>
            <i class="fa-solid fa-chevron-down filter-caret"></i>
          </div>
        </div>

        <!-- Scanner Banner -->
        <transition name="scanner-slide">
          <div v-if="scannerActive" class="scanner-banner">
            <div class="scanner-banner-inner">
              <div class="scanner-anim">
                <div class="scanner-frame">
                  <span class="scanner-corner scanner-corner--tl"></span>
                  <span class="scanner-corner scanner-corner--tr"></span>
                  <span class="scanner-corner scanner-corner--bl"></span>
                  <span class="scanner-corner scanner-corner--br"></span>
                  <div class="scanner-line"></div>
                </div>
              </div>
              <div class="scanner-text">
                <p class="scanner-title">Barcode Scanner Active</p>
                <p class="scanner-sub">Point your scanner or camera at a barcode</p>
                <div class="scanner-input-row">
                  <input
                    ref="barcodeInput"
                    v-model="barcodeBuffer"
                    @keydown="onBarcodeKey"
                    class="barcode-hidden-input"
                    placeholder="Scan or type barcode…"
                    autofocus
                  />
                  <p v-if="barcodeBuffer" class="barcode-preview">
                    <i class="fa-solid fa-barcode"></i> {{ barcodeBuffer }}
                  </p>
                  <p v-else class="barcode-hint">Waiting for scan…</p>
                </div>
              </div>
            </div>
          </div>
        </transition>

        <!-- Product Meta -->
        <div class="product-meta">
          <span class="product-count">{{ filteredProducts.length }} products</span>
          <span v-if="search" class="product-filter-tag">
            "{{ search }}"
            <button @click="search = ''"><i class="fa-solid fa-xmark"></i></button>
          </span>
        </div>

        <!-- Product Grid -->
        <div class="product-grid">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            class="product-card"
            :class="{ 'product-card--oos': product.stock <= 0 }"
            @click="product.stock > 0 && openQuantityModal(product)"
          >
            <div class="product-stock-badge" :class="stockBadgeClass(product)">
              {{ stockBadgeLabel(product) }}
            </div>
            <div class="product-img-wrap">
              <img v-if="product.image" :src="product.image" class="product-img" @error="onImgError(product)" />
              <div v-else class="product-img-placeholder"><i class="fa-solid fa-cube"></i></div>
            </div>
            <div class="product-info">
              <p class="product-name">{{ product.name }}</p>
              <p class="product-sku">{{ product.sku }}</p>
              <div class="product-footer">
                <span class="product-price">₱{{ product.price.toFixed(2) }}</span>
                <span class="product-stock-count">{{ product.stock }} left</span>
              </div>
            </div>
            <div class="product-add-overlay"><i class="fa-solid fa-plus"></i></div>
          </div>

          <div v-if="filteredProducts.length === 0" class="product-empty">
            <i class="fa-solid fa-box-open"></i>
            <p>No products found</p>
          </div>
        </div>
      </div>

      <!-- ══ RIGHT — CART ══ -->
      <div class="pos-right">

        <div class="cart-header">
          <div class="cart-header-top">
            <div>
              <p class="cart-eyebrow">Point of Sale</p>
              <h2 class="cart-title">Current Sale</h2>
            </div>
            <div class="cart-badge">
              {{ groupedCart.length }} item{{ groupedCart.length !== 1 ? 's' : '' }}
            </div>
          </div>
          <div class="customer-row">
            <div class="customer-field">
              <i class="fa-regular fa-user customer-icon"></i>
              <input
                v-model="customerName"
                type="text"
                placeholder="Customer name (required)"
                class="customer-input"
              />
            </div>
            <button @click="generateCustomerName" class="customer-gen-btn" title="Auto-generate">
              <i class="fa-solid fa-wand-magic-sparkles"></i>
            </button>
          </div>
        </div>

        <div class="cart-items">
          <div v-if="groupedCart.length === 0" class="cart-empty">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>Cart is empty</p>
            <span>Click a product to add it</span>
          </div>
          <div v-for="(item, idx) in groupedCart" :key="item.key" class="cart-item">
            <div class="cart-item-index">{{ idx + 1 }}</div>
            <div class="cart-item-body">
              <p class="cart-item-name">{{ item.name }}</p>
              <p class="cart-item-meta">{{ item.totalQty }} × ₱{{ item.price.toFixed(2) }}</p>
            </div>
            <div class="cart-item-right">
              <span class="cart-item-total">₱{{ (item.totalQty * item.price).toFixed(2) }}</span>
              <button @click="removeFromCart(item.key)" class="cart-item-del">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="cart-summary">
          <div class="summary-rows">
            <div class="summary-row">
              <span>Subtotal</span><span>₱{{ subtotal.toFixed(2) }}</span>
            </div>
            <div class="summary-row">
              <span>VAT (12%)</span><span>₱{{ vat.toFixed(2) }}</span>
            </div>
            <div class="summary-row">
              <span>Discount</span>
              <div class="discount-controls">
                <input v-model.number="discountValue" type="number" min="0" class="discount-input" />
                <div class="discount-type">
                  <button class="dtype-btn" :class="{ 'dtype-btn--active': discountType === '%' }" @click="discountType = '%'">%</button>
                  <button class="dtype-btn" :class="{ 'dtype-btn--active': discountType === 'PHP' }" @click="discountType = 'PHP'">₱</button>
                </div>
              </div>
            </div>
          </div>
          <div class="summary-total">
            <span>Total</span>
            <span class="summary-total-val">₱{{ total.toFixed(2) }}</span>
          </div>
          <button @click="openCashModal" class="pay-btn" :disabled="isProcessing || groupedCart.length === 0">
            <i v-if="!isProcessing" class="fa-solid fa-cash-register"></i>
            <i v-else class="fa-solid fa-spinner fa-spin"></i>
            {{ isProcessing ? 'Processing…' : 'Process Payment' }}
          </button>
          <button v-if="groupedCart.length > 0" @click="clearCart" class="clear-btn">
            <i class="fa-solid fa-trash"></i> Clear Cart
          </button>
        </div>
      </div>
    </div>

    <!-- ══ QUANTITY MODAL ══ -->
    <transition name="modal-fade">
      <div v-if="showQtyModal" class="modal-backdrop" @click.self="showQtyModal = false">
        <div class="modal">
          <div class="modal-header">
            <div>
              <p class="modal-eyebrow">Add to Cart</p>
              <h3 class="modal-title">{{ selectedProduct?.name }}</h3>
            </div>
            <button class="modal-close" @click="showQtyModal = false"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <div class="modal-body">
            <div class="modal-stats">
              <div class="modal-stat">
                <span class="modal-stat-label">Base Price</span>
                <span class="modal-stat-val">₱{{ selectedProduct?.price?.toFixed(2) }}</span>
              </div>
              <div class="modal-stat">
                <span class="modal-stat-label">Available</span>
                <span class="modal-stat-val" :class="{ 'val--red': availableStock < 5 }">{{ availableStock }}</span>
              </div>
              <div class="modal-stat">
                <span class="modal-stat-label">In Cart</span>
                <span class="modal-stat-val">{{ inCart(selectedProduct?.id) }}</span>
              </div>
            </div>
            <div class="modal-section">
              <p class="modal-section-label">Cut Dimensions <span class="modal-section-hint">(optional)</span></p>
              <div class="cut-grid">
                <div class="cut-field">
                  <label>Length (m)</label>
                  <input v-model.number="cutLength" type="number" min="0.1" step="0.1" placeholder="e.g. 2.5" />
                </div>
                <div class="cut-field">
                  <label>Width (m)</label>
                  <input v-model.number="cutWidth" type="number" min="0.1" step="0.1" placeholder="e.g. 1.2" />
                </div>
              </div>
              <div class="cut-result">
                <div class="cut-result-item">
                  <span>Area</span><strong>{{ cutArea.toFixed(2) }} sqm</strong>
                </div>
                <div class="cut-result-item cut-result-item--price">
                  <span>Adjusted Price</span><strong>₱{{ cutPrice.toFixed(2) }}</strong>
                </div>
              </div>
            </div>
            <div class="modal-section">
              <p class="modal-section-label">Quantity</p>
              <div class="qty-row">
                <button class="qty-btn" @click="qty = Math.max(1, qty - 1)"><i class="fa-solid fa-minus"></i></button>
                <input v-model.number="qty" type="number" min="1" class="qty-input" />
                <button class="qty-btn" @click="qty++"><i class="fa-solid fa-plus"></i></button>
              </div>
              <p class="qty-line-total">Line total: <strong>₱{{ (cutPrice * qty).toFixed(2) }}</strong></p>
            </div>
          </div>
          <div class="modal-footer">
            <button class="modal-cancel" @click="showQtyModal = false">Cancel</button>
            <button class="modal-confirm" @click="addToCart">
              <i class="fa-solid fa-cart-plus"></i> Add to Cart
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ══ CASH PAYMENT MODAL ══ -->
    <transition name="modal-fade">
      <div v-if="showCashModal" class="modal-backdrop" @click.self="showCashModal = false">
        <div class="modal modal--narrow">
          <div class="modal-header">
            <div>
              <p class="modal-eyebrow">Checkout</p>
              <h3 class="modal-title">Cash Payment</h3>
            </div>
            <button class="modal-close" @click="showCashModal = false"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <div class="modal-body">
            <div class="pay-summary">
              <div class="pay-summary-row"><span>Subtotal</span><span>₱{{ subtotal.toFixed(2) }}</span></div>
              <div class="pay-summary-row"><span>VAT (12%)</span><span>₱{{ vat.toFixed(2) }}</span></div>
              <div v-if="discountAmount > 0" class="pay-summary-row pay-summary-row--disc">
                <span>Discount</span><span>- ₱{{ discountAmount.toFixed(2) }}</span>
              </div>
              <div class="pay-summary-total"><span>Total Due</span><span>₱{{ total.toFixed(2) }}</span></div>
            </div>
            <div class="modal-section">
              <p class="modal-section-label">Amount Received</p>
              <div class="amount-input-wrap">
                <span class="amount-prefix">₱</span>
                <input
                  v-model.number="amountReceived"
                  type="number" min="0"
                  class="amount-input"
                  placeholder="0.00"
                  @focus="$event.target.select()"
                />
              </div>
              <div class="quick-amounts">
                <button v-for="amt in quickAmounts" :key="amt" class="quick-amt-btn" @click="amountReceived = amt">
                  ₱{{ amt.toLocaleString() }}
                </button>
              </div>
            </div>
            <div class="change-row" :class="{ 'change-row--ready': amountReceived >= total }">
              <div>
                <p class="change-label">Change</p>
                <p class="change-sub">{{ amountReceived >= total ? 'Ready to process' : 'Insufficient amount' }}</p>
              </div>
              <span class="change-val">₱{{ change.toFixed(2) }}</span>
            </div>
          </div>
          <div class="modal-footer">
            <button class="modal-cancel" @click="showCashModal = false">Cancel</button>
            <button class="modal-confirm" @click="completePayment" :disabled="isProcessing || amountReceived < total">
              <i v-if="isProcessing" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-check"></i>
              {{ isProcessing ? 'Processing…' : 'Complete Payment' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Layout>

  <!-- PRINT RECEIPT -->
  <div id="receipt">
    <h3 style="text-align:center;font-weight:bold;font-size:18px">SHEEM STEEL CONSTRUCTION</h3>
    <p style="text-align:center;font-size:12px">Roofing & Steel Supplies</p>
    <hr style="margin:8px 0"/>
    <p style="font-size:13px">Date: {{ new Date().toLocaleString() }}</p>
    <p style="font-size:13px">Customer: {{ customerName }}</p>
    <hr style="margin:8px 0"/>
    <div v-for="item in groupedCart" :key="item.key" style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
      <div><p style="font-weight:600">{{ item.name }}</p><p style="font-size:11px">{{ item.totalQty }} × ₱{{ item.price.toFixed(2) }}</p></div>
      <div>₱{{ (item.totalQty * item.price).toFixed(2) }}</div>
    </div>
    <hr style="margin:8px 0"/>
    <div style="display:flex;justify-content:space-between;font-size:13px"><span>Subtotal</span><span>₱{{ subtotal.toFixed(2) }}</span></div>
    <div style="display:flex;justify-content:space-between;font-size:13px"><span>VAT (12%)</span><span>₱{{ vat.toFixed(2) }}</span></div>
    <div style="display:flex;justify-content:space-between;font-size:13px"><span>Discount</span><span>- ₱{{ discountAmount.toFixed(2) }}</span></div>
    <hr style="margin:8px 0"/>
    <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:bold"><span>Total</span><span>₱{{ total.toFixed(2) }}</span></div>
    <div style="display:flex;justify-content:space-between;font-size:13px"><span>Cash</span><span>₱{{ amountReceived.toFixed(2) }}</span></div>
    <div style="display:flex;justify-content:space-between;font-size:13px"><span>Change</span><span>₱{{ change.toFixed(2) }}</span></div>
    <hr style="margin:8px 0"/>
    <p style="text-align:center;font-size:12px;margin-top:8px">Thank you for your purchase!</p>
    <p style="text-align:center;font-size:12px">Please come again.</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const products         = ref([])
const categories       = ref([])
const search           = ref('')
const selectedCategory = ref('')
const cart             = ref([])
const customerName     = ref('')
const showQtyModal     = ref(false)
const showCashModal    = ref(false)
const isProcessing     = ref(false)
const selectedProduct  = ref(null)
const qty              = ref(1)
const cutLength        = ref(1)
const cutWidth         = ref(1)
const discountType     = ref('%')
const discountValue    = ref(0)
const amountReceived   = ref(0)
const scannerActive    = ref(false)
const barcodeBuffer    = ref('')
const barcodeInput     = ref(null)
const searchInput      = ref(null)
let   barcodeTimer     = null

function toggleScanner() {
  scannerActive.value = !scannerActive.value
  barcodeBuffer.value = ''
  if (scannerActive.value) nextTick(() => barcodeInput.value?.focus())
}
function onBarcodeKey(e) {
  if (e.key === 'Enter') {
    e.preventDefault()
    const code = barcodeBuffer.value.trim()
    if (code) processBarcode(code)
    barcodeBuffer.value = ''
    return
  }
  clearTimeout(barcodeTimer)
  barcodeTimer = setTimeout(() => {
    const code = barcodeBuffer.value.trim()
    if (code.length >= 4) processBarcode(code)
    barcodeBuffer.value = ''
  }, 300)
}
function onGlobalKeydown() {
  if (!scannerActive.value) return
  if (document.activeElement !== barcodeInput.value) barcodeInput.value?.focus()
}
function processBarcode(code) {
  const found = products.value.find(p => p.sku === code || p.barcode === code)
  if (found) {
    if (found.stock > 0) { openQuantityModal(found); found._flash = true; setTimeout(() => { found._flash = false }, 800) }
    else alert(`"${found.name}" is out of stock.`)
  } else { alert(`No product found for barcode: ${code}`) }
}

onMounted(async () => {
  window.addEventListener('keydown', onGlobalKeydown)
  const [prodRes, catRes] = await Promise.all([api.get('/api/product-list'), api.get('/api/categories')])
  products.value = prodRes.data.map(p => ({
    id: p.ProductID, name: p.ProductName, sku: p.SKU,
    barcode: p.Barcode ?? p.SKU, price: Number(p.SellingPrice),
    stock: Number(p.Stock), image: p.ImageURL || null,
    category: p.CategoryID, _flash: false,
  }))
  categories.value = catRes.data.categories ?? catRes.data
})
onUnmounted(() => { window.removeEventListener('keydown', onGlobalKeydown); clearTimeout(barcodeTimer) })

function onImgError(product) { product.image = null }
function stockBadgeClass(p) { return p.stock <= 0 ? 'badge--oos' : p.stock <= 5 ? 'badge--low' : 'badge--ok' }
function stockBadgeLabel(p) { return p.stock <= 0 ? 'Out of Stock' : p.stock <= 5 ? 'Low Stock' : 'In Stock' }

const filteredProducts = computed(() =>
  products.value.filter(p =>
    (p.name.toLowerCase().includes(search.value.toLowerCase()) || p.sku.toLowerCase().includes(search.value.toLowerCase())) &&
    (!selectedCategory.value || p.category == selectedCategory.value)
  )
)

function openQuantityModal(product) {
  selectedProduct.value = product; qty.value = 1; cutLength.value = 1; cutWidth.value = 1; showQtyModal.value = true
}
const cutArea  = computed(() => cutLength.value * cutWidth.value)
const cutPrice = computed(() => selectedProduct.value ? selectedProduct.value.price * cutArea.value : 0)
function inCart(id) { return cart.value.filter(i => i.id === id).reduce((s, i) => s + i.qty, 0) }
const availableStock = computed(() => selectedProduct.value ? selectedProduct.value.stock - inCart(selectedProduct.value.id) : 0)

function addToCart() {
  const key = `${selectedProduct.value.id}-${cutLength.value}x${cutWidth.value}`
  const existing = cart.value.find(i => i.key === key)
  if (existing) { existing.qty += qty.value }
  else cart.value.push({ key, id: selectedProduct.value.id, name: `${selectedProduct.value.name} (${cutLength.value}×${cutWidth.value}m)`, price: cutPrice.value, qty: qty.value })
  showQtyModal.value = false
}

const groupedCart = computed(() => {
  const g = {}
  cart.value.forEach(i => { if (!g[i.key]) g[i.key] = { ...i, totalQty: 0 }; g[i.key].totalQty += i.qty })
  return Object.values(g)
})
function removeFromCart(key) { cart.value = cart.value.filter(i => i.key !== key) }
function clearCart() { if (confirm('Clear all items from the cart?')) cart.value = [] }

const subtotal       = computed(() => cart.value.reduce((s, i) => s + i.qty * i.price, 0))
const vat            = computed(() => subtotal.value * 0.12)
const discountAmount = computed(() => discountType.value === '%' ? subtotal.value * (discountValue.value / 100) : discountValue.value)
const total          = computed(() => subtotal.value + vat.value - discountAmount.value)
const change         = computed(() => Math.max(amountReceived.value - total.value, 0))
const quickAmounts   = computed(() => {
  const t = Math.ceil(total.value)
  return [Math.ceil(t/100)*100, Math.ceil(t/500)*500, Math.ceil(t/1000)*1000]
    .filter((v,i,a) => a.indexOf(v)===i && v>=t).slice(0,4)
})

function generateCustomerName() {
  const ts = new Date()
  customerName.value = `Walk-in ${ts.getMonth()+1}/${ts.getDate()} ${String(ts.getHours()).padStart(2,'0')}${String(ts.getMinutes()).padStart(2,'0')}`
}
function openCashModal() {
  if (!customerName.value.trim()) { alert('Please enter a customer name before processing payment.'); return }
  if (!cart.value.length) { alert('Cart is empty. Please add products first.'); return }
  amountReceived.value = 0; showCashModal.value = true
}
async function completePayment() {
  if (amountReceived.value < total.value) { alert('Amount received is insufficient.'); return }
  isProcessing.value = true
  try {
    await api.post('/api/sales/process', {
      total_amount: total.value, subtotal: subtotal.value,
      vat_amount: vat.value, discount_amount: discountAmount.value,
      discount_type: discountType.value, discount_value: discountValue.value,
      amount_paid: amountReceived.value, change_amount: change.value,
      customer_name: customerName.value,
      items: groupedCart.value.map(i => ({ product_id: i.id, quantity: i.totalQty, price: i.price, subtotal: i.totalQty * i.price })),
    })
    window.print()
    cart.value = []; customerName.value = ''; amountReceived.value = 0; discountValue.value = 0; showCashModal.value = false
  } catch (err) { alert('Payment failed. Please try again.'); console.error(err) }
  finally { isProcessing.value = false }
}
</script>

<style scoped>
/* ── TOKEN BRIDGE ── */
.pos-root {
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
  --p-shadow:         var(--c-shadow-sm);
  --p-shadow-md:      var(--c-shadow-md);
  --p-green:          #10b981;
  --p-red:            #f43f5e;
  --radius: 14px;

  display: flex;
  height: calc(100vh - 65px);
  background: var(--p-bg);
  overflow: hidden;
  font-family: 'Inter', system-ui, sans-serif;
  transition: background-color .22s ease;
}

/* ── LEFT PANEL ── */
.pos-left {
  flex: 1; display: flex; flex-direction: column;
  padding: 20px; overflow: hidden; gap: 14px; min-width: 0;
}

/* Search bar */
.search-bar { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.search-field { flex: 1; position: relative; display: flex; align-items: center; }
.search-field-icon {
  position: absolute; left: 12px;
  color: var(--p-text-faint); font-size: 12.5px; pointer-events: none;
}
.search-input {
  width: 100%; height: 40px;
  border: 1.5px solid var(--p-border-strong);
  border-radius: 10px; padding: 0 36px;
  font-size: 13px; font-family: inherit;
  background: var(--p-surface); color: var(--p-text-primary);
  outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.search-input:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); }
.search-input::placeholder { color: var(--p-text-faint); }
.search-clear {
  position: absolute; right: 10px;
  background: none; border: none;
  color: var(--p-text-faint); cursor: pointer; font-size: 12px; padding: 4px;
  transition: color .15s;
}
.search-clear:hover { color: var(--p-text-muted); }

/* Scan button */
.scan-btn {
  display: flex; align-items: center; gap: 6px;
  height: 40px; padding: 0 14px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: var(--p-surface); color: var(--p-text-muted);
  font-size: 12.5px; font-weight: 600; cursor: pointer; font-family: inherit;
  white-space: nowrap; flex-shrink: 0;
  transition: border-color .18s, background-color .18s, color .18s, box-shadow .18s;
}
.scan-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); background: var(--p-accent-soft); }
.scan-btn--active {
  background: var(--p-accent); border-color: var(--p-accent); color: #fff;
  box-shadow: 0 2px 8px rgba(234,88,12,.3);
}

/* Filter */
.filter-select-wrap { position: relative; display: flex; align-items: center; flex-shrink: 0; }
.filter-icon  { position: absolute; left: 10px; font-size: 11px; color: var(--p-text-faint); pointer-events: none; }
.filter-caret { position: absolute; right: 9px;  font-size: 9px;  color: var(--p-text-faint); pointer-events: none; }
.filter-select {
  appearance: none; -webkit-appearance: none;
  height: 40px; padding: 0 28px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: var(--p-surface); color: var(--p-text-secondary);
  font-size: 12.5px; font-weight: 600; cursor: pointer; font-family: inherit;
  outline: none;
  transition: border-color .18s, background-color .22s, color .22s;
}
.filter-select:focus { border-color: var(--p-accent); }

/* Scanner banner */
.scanner-banner {
  background: var(--p-surface);
  border: 1.5px solid var(--p-accent-border);
  border-radius: var(--radius); overflow: hidden; flex-shrink: 0;
  transition: background-color .22s, border-color .22s;
}
.scanner-banner-inner { display: flex; align-items: center; gap: 20px; padding: 16px 20px; }
.scanner-frame { width: 56px; height: 56px; position: relative; display: flex; align-items: center; justify-content: center; }
.scanner-corner { position: absolute; width: 12px; height: 12px; border-color: var(--p-accent); border-style: solid; }
.scanner-corner--tl { top:0;    left:0;  border-width: 2px 0 0 2px; }
.scanner-corner--tr { top:0;    right:0; border-width: 2px 2px 0 0; }
.scanner-corner--bl { bottom:0; left:0;  border-width: 0 0 2px 2px; }
.scanner-corner--br { bottom:0; right:0; border-width: 0 2px 2px 0; }
.scanner-line {
  width: 40px; height: 2px;
  background: linear-gradient(90deg, transparent, var(--p-accent), transparent);
  animation: scan-anim 1.4s ease-in-out infinite;
}
@keyframes scan-anim { 0%{transform:translateY(-18px);opacity:.4} 50%{opacity:1} 100%{transform:translateY(18px);opacity:.4} }
.scanner-text { flex: 1; }
.scanner-title { font-size: 13.5px; font-weight: 700; color: var(--p-text-primary); margin: 0 0 2px; }
.scanner-sub   { font-size: 12px; color: var(--p-text-muted); margin: 0 0 8px; }
.scanner-input-row { display: flex; align-items: center; gap: 10px; }
.barcode-hidden-input {
  width: 200px; height: 34px;
  border: 1.5px solid var(--p-border-strong); border-radius: 8px;
  padding: 0 12px; font-size: 12.5px; font-family: inherit;
  color: var(--p-text-primary); background: var(--p-surface-sunken);
  outline: none; transition: border-color .18s, background-color .22s;
}
.barcode-hidden-input:focus { border-color: var(--p-accent); background: var(--p-surface); }
.barcode-hidden-input::placeholder { color: var(--p-text-faint); }
.barcode-preview { font-size: 12.5px; font-weight: 600; color: var(--p-accent); display: flex; align-items: center; gap: 6px; margin: 0; }
.barcode-hint    { font-size: 12px; color: var(--p-text-faint); font-style: italic; margin: 0; }

/* Product meta */
.product-meta   { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.product-count  { font-size: 12px; font-weight: 600; color: var(--p-text-faint); text-transform: uppercase; letter-spacing: .07em; }
.product-filter-tag {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 600; color: var(--p-accent);
  background: var(--p-accent-soft); border: 1px solid var(--p-accent-border);
  border-radius: 20px; padding: 2px 8px;
}
.product-filter-tag button { background: none; border: none; cursor: pointer; color: inherit; font-size: 10px; padding: 0; }

/* Product grid */
.product-grid {
  flex: 1; overflow-y: auto;
  display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 12px; align-content: start; padding-right: 4px;
  scrollbar-width: thin; scrollbar-color: var(--p-border) transparent;
}
.product-grid::-webkit-scrollbar { width: 4px; }
.product-grid::-webkit-scrollbar-thumb { background: var(--p-border); border-radius: 4px; }

.product-card {
  background: var(--p-surface);
  border: 1.5px solid var(--p-border); border-radius: var(--radius);
  padding: 12px; cursor: pointer; position: relative; overflow: hidden;
  box-shadow: var(--p-shadow);
  transition: transform .18s, box-shadow .18s, border-color .18s, background-color .22s;
}
.product-card:hover { transform: translateY(-2px); box-shadow: var(--p-shadow-md); border-color: var(--p-accent); }
.product-card--oos  { opacity: .55; cursor: not-allowed; }
.product-card--oos:hover { transform: none; box-shadow: var(--p-shadow); border-color: var(--p-border); }

.product-stock-badge {
  position: absolute; top: 8px; right: 8px;
  font-size: 9.5px; font-weight: 700; padding: 2px 7px;
  border-radius: 20px; letter-spacing: .04em; text-transform: uppercase;
}
.badge--ok  { background: rgba(16,185,129,.15);  color: var(--p-green); }
.badge--low { background: var(--p-accent-soft);   color: var(--p-accent); }
.badge--oos { background: rgba(244,63,94,.14);    color: var(--p-red); }

.product-img-wrap {
  width: 100%; aspect-ratio: 1; border-radius: 10px;
  background: var(--p-surface-sunken); overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 10px; transition: background-color .22s;
}
.product-img { width: 100%; height: 100%; object-fit: contain; }
.product-img-placeholder { color: var(--p-text-faint); font-size: 28px; }

.product-name  { font-size: 12.5px; font-weight: 600; color: var(--p-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 2px; transition: color .22s; }
.product-sku   { font-size: 10.5px; color: var(--p-text-faint); margin: 0 0 8px; }
.product-footer { display: flex; align-items: center; justify-content: space-between; }
.product-price       { font-size: 13.5px; font-weight: 800; color: var(--p-accent); }
.product-stock-count { font-size: 10.5px; color: var(--p-text-faint); }

.product-add-overlay {
  position: absolute; inset: 0;
  background: rgba(234,88,12,.08); display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity .18s; font-size: 22px; color: var(--p-accent);
  border-radius: calc(var(--radius) - 1px);
}
.product-card:not(.product-card--oos):hover .product-add-overlay { opacity: 1; }

.product-empty {
  grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 60px 0; color: var(--p-text-faint); gap: 10px; font-size: 13px;
}
.product-empty i { font-size: 36px; }

/* ── RIGHT PANEL ── */
.pos-right {
  width: 340px; min-width: 340px;
  background: var(--p-surface);
  border-left: 1px solid var(--p-border);
  display: flex; flex-direction: column; overflow: hidden;
  transition: background-color .22s, border-color .22s;
}

/* Cart header */
.cart-header { padding: 18px 20px 14px; border-bottom: 1px solid var(--p-border); flex-shrink: 0; transition: border-color .22s; }
.cart-header-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
.cart-eyebrow { font-size: 10.5px; font-weight: 700; color: var(--p-accent); text-transform: uppercase; letter-spacing: .1em; margin: 0 0 3px; }
.cart-title   { font-size: 17px; font-weight: 800; color: var(--p-text-primary); margin: 0; letter-spacing: -.02em; transition: color .22s; }
.cart-badge   {
  font-size: 11px; font-weight: 700;
  background: var(--p-accent-soft); color: var(--p-accent);
  border: 1px solid var(--p-accent-border); border-radius: 20px; padding: 3px 10px;
}

.customer-row   { display: flex; align-items: center; gap: 8px; }
.customer-field { flex: 1; position: relative; display: flex; align-items: center; }
.customer-icon  { position: absolute; left: 11px; color: var(--p-text-faint); font-size: 12px; pointer-events: none; }
.customer-input {
  width: 100%; height: 38px;
  border: 1.5px solid var(--p-border-strong); border-radius: 9px;
  padding: 0 12px 0 32px; font-size: 13px; font-family: inherit;
  color: var(--p-text-primary); background: var(--p-surface-sunken);
  outline: none; transition: border-color .18s, background-color .22s, color .22s;
}
.customer-input:focus { border-color: var(--p-accent); background: var(--p-surface); }
.customer-input::placeholder { color: var(--p-text-faint); }
.customer-gen-btn {
  width: 38px; height: 38px;
  border: 1.5px solid var(--p-border-strong); border-radius: 9px;
  background: var(--p-surface-sunken); color: var(--p-text-muted);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  font-size: 13px; flex-shrink: 0;
  transition: border-color .18s, background-color .18s, color .18s;
}
.customer-gen-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); background: var(--p-accent-soft); }

/* Cart items */
.cart-items {
  flex: 1; overflow-y: auto; padding: 14px 20px;
  display: flex; flex-direction: column; gap: 8px;
  scrollbar-width: thin; scrollbar-color: var(--p-border) transparent;
}
.cart-items::-webkit-scrollbar { width: 3px; }
.cart-items::-webkit-scrollbar-thumb { background: var(--p-border); }

.cart-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 40px 0; color: var(--p-text-faint); gap: 8px; text-align: center;
}
.cart-empty i    { font-size: 32px; }
.cart-empty p    { font-size: 13.5px; font-weight: 600; margin: 0; color: var(--p-text-muted); }
.cart-empty span { font-size: 12px; }

.cart-item {
  display: flex; align-items: center; gap: 10px;
  background: var(--p-surface-raised); border: 1px solid var(--p-border);
  border-radius: 10px; padding: 10px 12px;
  transition: border-color .15s, background-color .22s;
}
.cart-item:hover { border-color: var(--p-border-strong); }
.cart-item-index {
  width: 22px; height: 22px; border-radius: 6px;
  background: var(--p-accent-soft); color: var(--p-accent);
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.cart-item-body  { flex: 1; min-width: 0; }
.cart-item-name  { font-size: 12.5px; font-weight: 600; color: var(--p-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 2px; }
.cart-item-meta  { font-size: 11.5px; color: var(--p-text-faint); margin: 0; }
.cart-item-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.cart-item-total { font-size: 13px; font-weight: 700; color: var(--p-text-primary); }
.cart-item-del {
  width: 28px; height: 28px; border: none; border-radius: 7px;
  background: none; color: var(--p-text-faint); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 12px;
  transition: background .15s, color .15s;
}
.cart-item-del:hover { background: rgba(244,63,94,.12); color: var(--p-red); }

/* Summary */
.cart-summary {
  border-top: 1px solid var(--p-border); padding: 16px 20px 20px;
  flex-shrink: 0; background: var(--p-surface-raised);
  transition: background-color .22s, border-color .22s;
}
.summary-rows { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
.summary-row  { display: flex; align-items: center; justify-content: space-between; font-size: 12.5px; color: var(--p-text-muted); }
.summary-row > span:first-child { font-weight: 500; }
.summary-row > span:last-child  { font-weight: 600; color: var(--p-text-secondary); }

.discount-controls { display: flex; align-items: center; gap: 6px; }
.discount-input {
  width: 64px; height: 30px;
  border: 1.5px solid var(--p-border-strong); border-radius: 7px;
  text-align: right; padding: 0 8px; font-size: 12.5px; font-family: inherit;
  color: var(--p-text-primary); background: var(--p-surface);
  outline: none; transition: border-color .15s, background-color .22s, color .22s;
}
.discount-input:focus { border-color: var(--p-accent); }
.discount-type { display: flex; border: 1.5px solid var(--p-border-strong); border-radius: 7px; overflow: hidden; }
.dtype-btn {
  width: 28px; height: 30px; border: none; background: none; cursor: pointer;
  font-size: 11.5px; font-weight: 600; color: var(--p-text-muted); font-family: inherit;
  transition: background .15s, color .15s;
}
.dtype-btn:first-child { border-right: 1px solid var(--p-border-strong); }
.dtype-btn--active { background: var(--p-accent-soft); color: var(--p-accent); }

.summary-total {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 0 14px; border-top: 1px solid var(--p-border-strong);
  transition: border-color .22s;
}
.summary-total > span:first-child { font-size: 13px; font-weight: 600; color: var(--p-text-secondary); }
.summary-total-val { font-size: 22px; font-weight: 800; color: var(--p-text-primary); letter-spacing: -.04em; }

.pay-btn {
  width: 100%; height: 46px; border: none; border-radius: 11px;
  background: linear-gradient(135deg, var(--p-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; font-size: 14px; font-weight: 700; font-family: inherit;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  box-shadow: var(--c-shadow-accent); margin-bottom: 8px;
  transition: opacity .18s, transform .18s, box-shadow .18s;
}
.pay-btn:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); box-shadow: var(--c-shadow-accent-h); }
.pay-btn:disabled { opacity: .45; cursor: not-allowed; transform: none; }

.clear-btn {
  width: 100%; height: 36px;
  border: 1.5px solid var(--p-border-strong); border-radius: 9px;
  background: none; color: var(--p-text-muted);
  font-size: 12.5px; font-weight: 600; font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: background .15s, color .15s, border-color .15s;
}
.clear-btn:hover { background: rgba(244,63,94,.10); color: var(--p-red); border-color: rgba(244,63,94,.30); }

/* ── MODALS ── */
.modal-backdrop {
  position: fixed; inset: 0;
  background: var(--c-overlay); backdrop-filter: blur(6px);
  z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal {
  background: var(--p-surface); border-radius: 20px;
  width: 100%; max-width: 480px;
  box-shadow: var(--c-shadow-xl); overflow: hidden;
  border: 1px solid var(--p-border);
  transition: background-color .22s, border-color .22s;
}
.modal--narrow { max-width: 380px; }

.modal-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 20px 22px 16px; border-bottom: 1px solid var(--p-border);
  transition: border-color .22s;
}
.modal-eyebrow { font-size: 10.5px; font-weight: 700; color: var(--p-accent); text-transform: uppercase; letter-spacing: .1em; margin: 0 0 3px; }
.modal-title   { font-size: 16px; font-weight: 700; color: var(--p-text-primary); margin: 0; transition: color .22s; }
.modal-close {
  width: 30px; height: 30px; border: none;
  background: var(--p-surface-raised); border-radius: 8px;
  color: var(--p-text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 12px;
  transition: background .15s, color .15s;
}
.modal-close:hover { background: var(--p-border-strong); color: var(--p-text-primary); }

.modal-body { padding: 20px 22px; display: flex; flex-direction: column; gap: 18px; }

.modal-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
.modal-stat  {
  background: var(--p-surface-raised); border: 1px solid var(--p-border);
  border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px;
  transition: background-color .22s, border-color .22s;
}
.modal-stat-label { font-size: 10.5px; font-weight: 600; color: var(--p-text-faint); text-transform: uppercase; letter-spacing: .06em; }
.modal-stat-val   { font-size: 15px; font-weight: 700; color: var(--p-text-primary); transition: color .22s; }
.val--red { color: var(--p-red) !important; }

.modal-section { display: flex; flex-direction: column; gap: 10px; }
.modal-section-label { font-size: 13px; font-weight: 700; color: var(--p-text-secondary); transition: color .22s; }
.modal-section-hint  { font-weight: 400; color: var(--p-text-faint); font-size: 11.5px; }

.cut-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cut-field { display: flex; flex-direction: column; gap: 5px; }
.cut-field label { font-size: 11.5px; font-weight: 600; color: var(--p-text-muted); }
.cut-field input {
  height: 38px; border: 1.5px solid var(--p-border-strong); border-radius: 8px;
  padding: 0 12px; font-size: 13px; font-family: inherit;
  color: var(--p-text-primary); background: var(--p-surface-sunken);
  outline: none; transition: border-color .15s, background-color .22s, color .22s;
}
.cut-field input:focus { border-color: var(--p-accent); background: var(--p-surface); }
.cut-field input::placeholder { color: var(--p-text-faint); }

.cut-result { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cut-result-item {
  background: var(--p-surface-raised); border: 1px solid var(--p-border);
  border-radius: 8px; padding: 8px 12px; display: flex; flex-direction: column; gap: 3px;
  transition: background-color .22s, border-color .22s;
}
.cut-result-item span   { font-size: 10.5px; font-weight: 600; color: var(--p-text-faint); text-transform: uppercase; letter-spacing: .06em; }
.cut-result-item strong { font-size: 13.5px; font-weight: 700; color: var(--p-text-primary); }
.cut-result-item--price strong { color: var(--p-accent); }

.qty-row { display: flex; align-items: center; gap: 10px; }
.qty-btn {
  width: 38px; height: 38px;
  border: 1.5px solid var(--p-border-strong); border-radius: 9px;
  background: var(--p-surface); color: var(--p-text-secondary);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  font-size: 12px; flex-shrink: 0;
  transition: border-color .15s, background-color .15s, color .15s;
}
.qty-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); background: var(--p-accent-soft); }
.qty-input {
  width: 70px; height: 38px; border: 1.5px solid var(--p-border-strong); border-radius: 9px;
  text-align: center; font-size: 16px; font-weight: 700; font-family: inherit;
  color: var(--p-text-primary); background: var(--p-surface-sunken);
  outline: none; transition: border-color .15s, background-color .22s, color .22s;
}
.qty-input:focus { border-color: var(--p-accent); background: var(--p-surface); }
.qty-line-total { font-size: 12.5px; color: var(--p-text-muted); margin: 0; }
.qty-line-total strong { color: var(--p-accent); }

.pay-summary {
  background: var(--p-surface-raised); border: 1px solid var(--p-border);
  border-radius: 12px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px;
  transition: background-color .22s, border-color .22s;
}
.pay-summary-row { display: flex; justify-content: space-between; font-size: 12.5px; color: var(--p-text-muted); }
.pay-summary-row > span:last-child { font-weight: 600; color: var(--p-text-secondary); }
.pay-summary-row--disc > span:last-child { color: var(--p-green); }
.pay-summary-total {
  display: flex; justify-content: space-between;
  border-top: 1px solid var(--p-border-strong); padding-top: 10px; margin-top: 4px;
  font-weight: 700; font-size: 14px; color: var(--p-text-primary);
  transition: border-color .22s;
}

.amount-input-wrap { position: relative; display: flex; align-items: center; }
.amount-prefix {
  position: absolute; left: 14px; font-size: 16px; font-weight: 700;
  color: var(--p-text-faint); pointer-events: none;
}
.amount-input {
  width: 100%; height: 52px;
  border: 2px solid var(--p-border-strong); border-radius: 12px;
  padding: 0 16px 0 34px;
  font-size: 22px; font-weight: 800; font-family: inherit;
  color: var(--p-text-primary); background: var(--p-surface-sunken);
  letter-spacing: -.02em; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.amount-input:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); background: var(--p-surface); }
.amount-input::placeholder { color: var(--p-text-faint); }

.quick-amounts { display: flex; gap: 8px; flex-wrap: wrap; }
.quick-amt-btn {
  flex: 1; min-width: 70px; height: 34px;
  border: 1.5px solid var(--p-border-strong); border-radius: 8px;
  background: var(--p-surface-raised); font-size: 12.5px; font-weight: 600;
  color: var(--p-text-secondary); cursor: pointer; font-family: inherit;
  transition: border-color .15s, background-color .15s, color .15s;
}
.quick-amt-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); background: var(--p-accent-soft); }

.change-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px; border-radius: 12px;
  background: rgba(244,63,94,.09); border: 1px solid rgba(244,63,94,.25);
  transition: background .2s, border-color .2s;
}
.change-row--ready { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.30); }
.change-label { font-size: 13px; font-weight: 700; color: var(--p-text-secondary); margin: 0 0 2px; }
.change-sub   { font-size: 11px; color: var(--p-text-muted); margin: 0; }
.change-val   { font-size: 22px; font-weight: 800; letter-spacing: -.04em; color: var(--p-text-primary); }

.modal-footer {
  display: flex; gap: 10px;
  padding: 14px 22px 20px; border-top: 1px solid var(--p-border);
  transition: border-color .22s;
}
.modal-cancel {
  flex: 1; height: 42px; border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: none; color: var(--p-text-muted); font-size: 13.5px; font-weight: 600;
  font-family: inherit; cursor: pointer;
  transition: background .15s, border-color .15s, color .15s;
}
.modal-cancel:hover { background: var(--p-surface-raised); color: var(--p-text-primary); }
.modal-confirm {
  flex: 2; height: 42px; border: none; border-radius: 10px;
  background: linear-gradient(135deg, var(--p-accent), var(--c-accent-deep));
  color: #fff; font-size: 13.5px; font-weight: 700; font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  box-shadow: var(--c-shadow-accent);
  transition: opacity .15s, transform .15s, box-shadow .15s;
}
.modal-confirm:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); box-shadow: var(--c-shadow-accent-h); }
.modal-confirm:disabled { opacity: .45; cursor: not-allowed; transform: none; box-shadow: none; }

/* Transitions */
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .16s ease, transform .16s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.97) translateY(8px); }

.scanner-slide-enter-active { transition: opacity .2s ease, max-height .24s ease; max-height: 200px; overflow: hidden; }
.scanner-slide-leave-active { transition: opacity .16s ease, max-height .2s ease; overflow: hidden; }
.scanner-slide-enter-from, .scanner-slide-leave-to { opacity: 0; max-height: 0; }
</style>

<style>
#receipt { display: none; }
@media print {
  body * { visibility: hidden !important; }
  #receipt, #receipt * { visibility: visible !important; }
  #receipt {
    display: block !important; position: absolute; left: 0; top: 0;
    width: 280px; padding: 10px; background: white; font-size: 14px; line-height: 18px;
  }
}
</style>