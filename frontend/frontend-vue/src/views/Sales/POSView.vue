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
              placeholder="Search products, SKU, or barcode…"
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
        <transition name="scanner-slide" @after-enter="onScannerBannerEnter">
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

                <!-- Mode toggle: USB (keyboard-wedge) vs Camera -->
                <div class="scanner-mode-toggle">
                  <button
                    class="smode-btn"
                    :class="{ 'smode-btn--active': scanMode === 'usb' }"
                    @click="setScanMode('usb')"
                  >
                    <i class="fa-solid fa-keyboard"></i> USB Scanner
                  </button>
                  <button
                    class="smode-btn"
                    :class="{ 'smode-btn--active': scanMode === 'camera' }"
                    @click="setScanMode('camera')"
                  >
                    <i class="fa-solid fa-camera"></i> Camera
                  </button>
                </div>

                <div class="scanner-input-row">
                  <template v-if="scanMode === 'usb'">
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
                    <transition name="feedback-fade" mode="out-in">
                      <p v-if="scanFeedback" :key="scanFeedback.text" class="barcode-hint" :class="`barcode-hint--${scanFeedback.type}`">
                        <i class="fa-solid" :class="scanFeedback.type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
                        {{ scanFeedback.text }}
                      </p>
                      <p v-else class="barcode-hint">Waiting for scan…</p>
                    </transition>
                  </template>

                  <template v-else>
                    <div class="camera-scan-wrap">
                      <div class="camera-scan-box-outer">
                        <div :id="CAMERA_ELEMENT_ID" class="camera-scan-box"></div>

                        <!-- Success flash: unmistakable confirmation a scan registered -->
                        <transition name="flash-pop">
                          <div v-if="cameraFlashVisible" class="camera-flash-overlay">
                            <div class="camera-flash-check"><i class="fa-solid fa-check"></i></div>
                          </div>
                        </transition>

                        <!-- Idle reticle: shows the camera is live and looking -->
                        <div v-if="!cameraStarting && !cameraError && !cameraFlashVisible" class="camera-reticle">
                          <span class="camera-reticle-corner camera-reticle-corner--tl"></span>
                          <span class="camera-reticle-corner camera-reticle-corner--tr"></span>
                          <span class="camera-reticle-corner camera-reticle-corner--bl"></span>
                          <span class="camera-reticle-corner camera-reticle-corner--br"></span>
                        </div>
                      </div>

                      <p v-if="cameraStarting" class="barcode-hint"><i class="fa-solid fa-spinner fa-spin"></i> Starting camera…</p>
                      <p v-else-if="cameraError" class="barcode-hint barcode-hint--error"><i class="fa-solid fa-triangle-exclamation"></i> {{ cameraError }}</p>
                      <template v-else>
                        <transition name="feedback-fade" mode="out-in">
                          <p v-if="scanFeedback" :key="scanFeedback.text" class="barcode-hint" :class="`barcode-hint--${scanFeedback.type}`">
                            <i class="fa-solid" :class="scanFeedback.type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
                            {{ scanFeedback.text }}
                          </p>
                          <p v-else class="barcode-hint">Point camera at a barcode…</p>
                        </transition>
                        <p v-if="cameraHint" class="barcode-hint barcode-hint--tip">
                          <i class="fa-solid fa-lightbulb"></i> {{ cameraHint }}
                        </p>
                      </template>
                    </div>
                  </template>
                </div>
                <label class="scanner-autoadd">
                  <input type="checkbox" v-model="autoAddOnScan" />
                  Auto-add single-unit products on scan
                </label>
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

        <!-- Load error -->
        <div v-if="loadError" class="load-state load-state--error">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <p>{{ loadError }}</p>
          <button @click="loadCatalog" class="load-retry-btn">Retry</button>
        </div>

        <!-- Loading -->
        <div v-else-if="loading" class="load-state">
          <i class="fa-solid fa-spinner fa-spin"></i>
          <p>Loading products…</p>
        </div>

        <!-- Product Grid (scrollable — every card keeps its full details) -->
        <div v-else class="product-grid">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            class="product-card"
            :class="{ 'product-card--oos': !canAddProduct(product), 'product-card--flash': flashProductId === product.id }"
            @click="canAddProduct(product) && openQuantityModal(product)"
          >
            <div class="product-stock-badge" :class="stockBadgeClass(product)">
              {{ stockBadgeLabel(product) }}
            </div>
            <div class="product-img-wrap">
              <img v-if="product.image" :src="product.image" class="product-img" @error="onImgError(product)" />
              <div v-else class="product-img-placeholder"><i class="fa-solid fa-cube"></i></div>
            </div>
            <div class="pcard-info">
              <p class="pcard-name" :title="product.name || 'Unnamed product'">{{ product.name || 'Unnamed product' }}</p>
              <p class="pcard-sku">SKU: {{ product.sku || '—' }}</p>
              <p v-if="product.pricingType === 'sqm'" class="pcard-tag">Cut-to-size (sqm)</p>
              <div class="pcard-footer">
                <span class="pcard-price">₱{{ (product.price || 0).toFixed(2) }}</span>
                <span class="pcard-stock-count">{{ availableFor(product) }} left</span>
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

      <!-- ══ RIGHT — CART ══
           On desktop/tablet (>768px) this sits beside the product grid as
           normal. Below 768px it becomes a bottom-sheet drawer (see
           .pos-right media query): closed by default and hidden below the
           viewport, opened via the floating mobile-cart-bar, and closed via
           the handle/close button or the backdrop. -->
      <div class="pos-right" :class="{ 'cart-drawer--open': isMobile && cartOpen }">
        <!-- Drag-handle affordance — mobile bottom-sheet only -->
        <div v-if="isMobile" class="cart-drawer-handle" aria-hidden="true"></div>

        <div class="cart-header">
          <div class="cart-header-top">
            <div>
              <p class="cart-eyebrow">Point of Sale</p>
              <h2 class="cart-title">Current Sale</h2>
            </div>
            <div class="cart-header-top-right">
              <div class="cart-badge">
                {{ cart.length }} item{{ cart.length !== 1 ? 's' : '' }}
              </div>
              <button v-if="isMobile" class="cart-drawer-close" @click="cartOpen = false" aria-label="Close cart">
                <i class="fa-solid fa-chevron-down"></i>
              </button>
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
          <div v-if="cart.length === 0" class="cart-empty">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>Cart is empty</p>
            <span>Click a product to add it</span>
          </div>
          <div v-for="(item, idx) in cart" :key="item.key" class="cart-item">
            <div class="cart-item-index">{{ idx + 1 }}</div>
            <div class="cart-item-body">
              <p class="cart-item-name">{{ item.name }}</p>
              <p class="cart-item-meta">{{ item.qty }} × ₱{{ item.price.toFixed(2) }}</p>
            </div>
            <div class="cart-item-qty-mini">
              <button class="cart-item-qty-btn" @click="decrementLine(item)" title="Decrease"><i class="fa-solid fa-minus"></i></button>
              <span class="cart-item-qty-val">{{ item.qty }}</span>
              <button class="cart-item-qty-btn" @click="incrementLine(item)" title="Increase"><i class="fa-solid fa-plus"></i></button>
            </div>
            <div class="cart-item-right">
              <span class="cart-item-total">₱{{ (item.qty * item.price).toFixed(2) }}</span>
              <button @click="editCartItem(item)" class="cart-item-edit" title="Edit">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button @click="removeFromCart(item.key)" class="cart-item-del" title="Remove line">
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
              <span>Discount</span>
              <div class="discount-controls">
                <input v-model.number="discountValue" type="number" min="0" class="discount-input" />
                <div class="discount-type">
                  <button class="dtype-btn" :class="{ 'dtype-btn--active': discountType === '%' }" @click="discountType = '%'">%</button>
                  <button class="dtype-btn" :class="{ 'dtype-btn--active': discountType === 'PHP' }" @click="discountType = 'PHP'">₱</button>
                </div>
              </div>
            </div>
            <div class="summary-row">
              <span>VATable Sales</span><span>₱{{ vatableSales.toFixed(2) }}</span>
            </div>
            <div class="summary-row">
              <span>VAT (12% Included)</span><span>₱{{ vat.toFixed(2) }}</span>
            </div>
          </div>
          <div class="summary-total">
            <span>Total</span>
            <span class="summary-total-val">₱{{ total.toFixed(2) }}</span>
          </div>
          <p v-if="checkoutError" class="checkout-error">{{ checkoutError }}</p>
          <button @click="openCashModal" class="pay-btn" :disabled="isProcessing || cart.length === 0 || total <= 0">
            <i v-if="!isProcessing" class="fa-solid fa-cash-register"></i>
            <i v-else class="fa-solid fa-spinner fa-spin"></i>
            {{ isProcessing ? 'Processing…' : 'Process Payment' }}
          </button>
          <button v-if="cart.length > 0" @click="clearCart" class="clear-btn">
            <i class="fa-solid fa-trash"></i> Clear Cart
          </button>
        </div>
      </div>

      <!-- MOBILE CART DRAWER BACKDROP -->
      <transition name="fade">
        <div v-if="isMobile && cartOpen" class="cart-backdrop" @click="cartOpen = false" aria-hidden="true" />
      </transition>

      <!-- MOBILE CART BAR — persistent summary + drawer toggle, only
           rendered below the stacked-layout breakpoint. Hidden while the
           drawer itself is open (the drawer's own close button/backdrop
           take over at that point). -->
      <button v-if="isMobile && !cartOpen" class="mobile-cart-bar" @click="cartOpen = true">
        <span class="mobile-cart-bar-icon">
          <i class="fa-solid fa-cart-shopping"></i>
          <span v-if="cart.length" class="mobile-cart-bar-badge">{{ cart.length }}</span>
        </span>
        <span class="mobile-cart-bar-total">₱{{ total.toFixed(2) }}</span>
        <span class="mobile-cart-bar-cta">View Cart <i class="fa-solid fa-chevron-up"></i></span>
      </button>
    </div>

    <!-- ══ QUANTITY MODAL ══
         Teleported to <body> so it can never inherit opacity/filter/transform
         from an ancestor (e.g. Layout's <main> wrapper). Scoped styles still
         apply to teleported content, BUT this element is no longer a
         descendant of .pos-root in the real DOM once teleported — so any
         CSS custom property only *defined* on .pos-root (the --p-* bridge
         tokens) is undefined here. Every rule inside this modal (and the
         cash modal below) references --c-* tokens directly with an inline
         fallback instead, so it never depends on .pos-root being an
         ancestor. See the style block for the full explanation. -->
    <Teleport to="body">
    <transition name="modal-fade">
      <div v-if="showQtyModal" class="modal-backdrop" @click.self="closeQtyModal" @keydown.esc="closeQtyModal">
        <div class="modal">
          <div class="modal-header">
            <div>
              <p class="modal-eyebrow">{{ editingKey ? 'Edit Cart Line' : 'Add to Cart' }}</p>
              <h3 class="modal-title">{{ selectedProduct?.name }}</h3>
            </div>
            <button class="modal-close" @click="closeQtyModal"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <div class="modal-body">
            <div class="modal-product-card">
              <div class="modal-product-img">
                <img v-if="selectedProduct?.image" :src="selectedProduct.image" alt="" />
                <div v-else class="modal-product-img-placeholder"><i class="fa-solid fa-cube"></i></div>
              </div>
              <div class="modal-product-info">
                <p class="modal-product-name">{{ selectedProduct?.name }}</p>
                <p class="modal-product-price">₱{{ selectedProduct?.price?.toFixed(2) }} <span>base price</span></p>
              </div>
            </div>

            <div class="modal-stats modal-stats--2col">
              <div class="modal-stat modal-stat--icon">
                <span class="modal-stat-icon" :class="{ 'modal-stat-icon--red': availableStock < 5 }">
                  <i class="fa-solid fa-boxes-stacked"></i>
                </span>
                <div>
                  <span class="modal-stat-label">Available</span>
                  <span class="modal-stat-val" :class="{ 'val--red': availableStock < 5, 'val--amber': availableStock >= 5 && availableStock < 15 }">{{ availableStock }}</span>
                </div>
              </div>
              <div class="modal-stat modal-stat--icon">
                <span class="modal-stat-icon"><i class="fa-solid fa-cart-shopping"></i></span>
                <div>
                  <span class="modal-stat-label">Already in Cart</span>
                  <span class="modal-stat-val">{{ inCart(selectedProduct?.id, editingKey) }}</span>
                </div>
              </div>
            </div>

            <div v-if="isSqm" class="modal-section">
              <p class="modal-section-label">Cut Dimensions <span class="modal-section-hint">({{ selectedProduct.minDimension }}m – {{ selectedProduct.maxDimension }}m)</span></p>
              <div class="cut-grid">
                <div class="cut-field">
                  <label>Length (m)</label>
                  <input v-model.number="cutLength" type="number" :min="selectedProduct.minDimension" :max="selectedProduct.maxDimension" step="0.1" placeholder="e.g. 2.5" />
                </div>
                <div class="cut-field">
                  <label>Width (m)</label>
                  <input v-model.number="cutWidth" type="number" :min="selectedProduct.minDimension" :max="selectedProduct.maxDimension" step="0.1" placeholder="e.g. 1.2" />
                </div>
              </div>
              <div class="cut-result">
                <div class="cut-result-item">
                  <span>Area</span><strong>{{ cutArea.toFixed(2) }} sqm</strong>
                </div>
                <div class="cut-result-item cut-result-item--price">
                  <span>Adjusted Price</span><strong>₱{{ unitPrice.toFixed(2) }}</strong>
                </div>
              </div>
            </div>

            <div class="modal-section">
              <p class="modal-section-label">Quantity</p>
              <div class="qty-row">
                <button class="qty-btn" @click="qty = Math.max(1, qty - 1)"><i class="fa-solid fa-minus"></i></button>
                <input v-model.number="qty" type="number" min="1" :max="availableStock" class="qty-input" />
                <button class="qty-btn" @click="qty = Math.min(availableStock, qty + 1)"><i class="fa-solid fa-plus"></i></button>
              </div>
              <p v-if="modalError" class="modal-error"><i class="fa-solid fa-circle-exclamation"></i> {{ modalError }}</p>
            </div>
          </div>

          <div class="modal-line-total-bar">
            <span>Line Total</span>
            <strong>₱{{ (unitPrice * qty).toFixed(2) }}</strong>
          </div>

          <div class="modal-footer">
            <button class="modal-cancel" @click="closeQtyModal">Cancel</button>
            <button class="modal-confirm" @click="addToCart" :disabled="availableStock <= 0">
              <i class="fa-solid fa-cart-plus"></i> {{ editingKey ? 'Update Line' : 'Add to Cart' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
    </Teleport>

    <!-- ══ CASH PAYMENT MODAL ══ (also teleported — see note above) -->
    <Teleport to="body">
    <transition name="modal-fade">
      <div v-if="showCashModal" class="modal-backdrop" @click.self="closeCashModal" @keydown.esc="closeCashModal" @keydown.enter="completePayment">
        <div class="modal modal--narrow">
          <div class="modal-header">
            <div>
              <p class="modal-eyebrow">Checkout</p>
              <h3 class="modal-title">Cash Payment</h3>
            </div>
            <button class="modal-close" @click="closeCashModal"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <div class="modal-body">
            <div class="pay-summary">
              <div class="pay-summary-row"><span>Subtotal</span><span>₱{{ subtotal.toFixed(2) }}</span></div>
              <div v-if="discountAmount > 0" class="pay-summary-row pay-summary-row--disc">
                <span>Discount</span><span>- ₱{{ discountAmount.toFixed(2) }}</span>
              </div>
              <div class="pay-summary-row"><span>VATable Sales</span><span>₱{{ vatableSales.toFixed(2) }}</span></div>
              <div class="pay-summary-row"><span>VAT (12% Included)</span><span>₱{{ vat.toFixed(2) }}</span></div>
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
            <div class="change-row" :class="{ 'change-row--ready': amountReceivedNum >= total }">
              <div>
                <p class="change-label">Change</p>
                <p class="change-sub">{{ amountReceivedNum >= total ? 'Ready to process' : 'Insufficient amount' }}</p>
              </div>
              <span class="change-val">₱{{ change.toFixed(2) }}</span>
            </div>
            <p v-if="checkoutError" class="modal-error">{{ checkoutError }}</p>
          </div>
          <div class="modal-footer">
            <button class="modal-cancel" @click="closeCashModal">Cancel</button>
            <button class="modal-confirm" @click="completePayment" :disabled="isProcessing || amountReceivedNum < total">
              <i v-if="isProcessing" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-check"></i>
              {{ isProcessing ? 'Processing…' : 'Complete Payment' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
    </Teleport>
  </Layout>

  <!-- PRINT RECEIPT -->
  <div id="receipt" v-if="receiptData">
    <div class="r-store">SHEEM STEEL CONSTRUCTION</div>
    <div class="r-tagline">Roofing &amp; Steel Supplies</div>

    <div class="r-divider"></div>

    <div class="r-meta">
      <div class="r-meta-row"><span>Receipt #</span><span>{{ receiptData.receiptNumber }}</span></div>
      <div class="r-meta-row"><span>Date</span><span>{{ receiptData.date }}</span></div>
      <div class="r-meta-row"><span>Customer</span><span>{{ receiptData.customer }}</span></div>
      <div class="r-meta-row"><span>Cashier</span><span>{{ receiptData.cashier }}</span></div>
    </div>

    <div class="r-divider"></div>

    <div class="r-items">
      <div v-for="(item, idx) in receiptData.items" :key="idx" class="r-item">
        <div class="r-item-name">{{ item.name }}</div>
        <div class="r-item-line">
          <span>{{ item.qty }} × ₱{{ item.price.toFixed(2) }}</span>
          <span>₱{{ item.total.toFixed(2) }}</span>
        </div>
      </div>
    </div>

    <div class="r-divider"></div>

    <div class="r-totals">
      <div class="r-row"><span>Subtotal</span><span>₱{{ receiptData.subtotal.toFixed(2) }}</span></div>
      <div class="r-row"><span>Discount</span><span>- ₱{{ receiptData.discount.toFixed(2) }}</span></div>
      <div class="r-row"><span>VATable Sales</span><span>₱{{ receiptData.vatableSales.toFixed(2) }}</span></div>
      <div class="r-row"><span>VAT (12% Incl.)</span><span>₱{{ receiptData.vat.toFixed(2) }}</span></div>
    </div>

    <div class="r-divider r-divider--solid"></div>

    <div class="r-grand-total">
      <span>TOTAL</span><span>₱{{ receiptData.total.toFixed(2) }}</span>
    </div>

    <div class="r-divider"></div>

    <div class="r-payment">
      <div class="r-row"><span>Cash</span><span>₱{{ receiptData.cash.toFixed(2) }}</span></div>
      <div class="r-row"><span>Change</span><span>₱{{ receiptData.change.toFixed(2) }}</span></div>
    </div>

    <div class="r-divider"></div>

    <div class="r-footer">
      <p>Thank you for your purchase!</p>
      <p>Please come again.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'
// Camera barcode scanning. `npm install html5-qrcode`
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode'

/* ────────────────────────────────────────────────────────────
   CONSTANTS
──────────────────────────────────────────────────────────── */
// Prices are VAT-INCLUSIVE (already contain 12% Philippine VAT).
// VAT is therefore extracted from the gross amount, never added on top:
//   Gross Amount = Subtotal - Discount
//   VATable Sales = Gross Amount / 1.12
//   VAT           = Gross Amount - VATable Sales
//   Total         = Gross Amount
const VAT_RATE = 0.12
const VAT_DIVISOR = 1 + VAT_RATE // 1.12
const MIN_DIM = 0.1
const MAX_DIM = 50
const BARCODE_DUPLICATE_WINDOW_MS = 1200
const SCAN_DEBOUNCE_MS = 300
// Hardware (keyboard-wedge) scanners fire keystrokes only a few ms apart.
// Anything slower than this is treated as a human typing manually, who
// should never be auto-submitted — only Enter submits for them.
const SCANNER_KEY_INTERVAL_MS = 60
const SCANNER_MESSAGE_TIMEOUT_MS = 3000
const FLASH_DURATION_MS = 900
const CAMERA_ELEMENT_ID = 'pos-camera-scanner'
const CAMERA_FORMATS = [
  Html5QrcodeSupportedFormats.QR_CODE,
  Html5QrcodeSupportedFormats.EAN_13,
  Html5QrcodeSupportedFormats.EAN_8,
  Html5QrcodeSupportedFormats.UPC_A,
  Html5QrcodeSupportedFormats.UPC_E,
  Html5QrcodeSupportedFormats.CODE_128,
  Html5QrcodeSupportedFormats.CODE_39,
  Html5QrcodeSupportedFormats.CODABAR,
  Html5QrcodeSupportedFormats.ITF,
]
// Sort priority for the product grid: In Stock first, Low Stock next,
// Out of Stock last. Keyed to the same thresholds used by
// stockBadgeClass/stockBadgeLabel so the badge shown always matches the
// section the card is sorted into.
const STOCK_SORT_PRIORITY = { ok: 0, low: 1, oos: 2 }

/* ────────────────────────────────────────────────────────────
   HELPERS
──────────────────────────────────────────────────────────── */
function clamp(val, min, max) {
  const n = Number(val)
  if (Number.isNaN(n)) return min
  return Math.min(Math.max(n, min), max)
}
function round2(n) {
  return Math.round((Number(n) || 0) * 100) / 100
}
/** Normalizes product records coming from either the product-list endpoint
 *  or the barcode-lookup endpoint into a single consistent shape. */
function normalizeProduct(p) {
  const pricingTypeRaw = (p.PricingType ?? p.pricing_type ?? 'piece').toString().toLowerCase()
  return {
    id: p.ProductID ?? p.id,
    name: p.ProductName ?? p.name ?? '',
    sku: p.SKU ?? p.sku ?? '',
    barcode: p.Barcode ?? p.barcode ?? p.SKU ?? p.sku ?? '',
    price: Number(p.SellingPrice ?? p.price ?? 0),
    stock: Number(p.inventory?.QuantityOnHand ?? p.Stock ?? p.stock ?? 0),
    image: p.ImageURL ?? p.image ?? null,
    category: p.CategoryID ?? p.category ?? null,
    pricingType: pricingTypeRaw === 'sqm' ? 'sqm' : 'piece',
    minDimension: Number(p.MinDimension ?? MIN_DIM),
    maxDimension: Number(p.MaxDimension ?? MAX_DIM),
  }
}
function buildKey(product, length, width) {
  if (product.pricingType === 'sqm') return `${product.id}-${length}x${width}`
  return `${product.id}-piece`
}

/* ────────────────────────────────────────────────────────────
   STATE
──────────────────────────────────────────────────────────── */
const products   = ref([])
const categories = ref([])
const loading    = ref(false)
const loadError  = ref('')

const search           = ref('')
const selectedCategory = ref('')

const cart         = ref([])          // unique-keyed, already-merged cart lines
const customerName = ref('')
const cashierName  = ref(localStorage.getItem('cashier_name') || 'Cashier')

const showQtyModal  = ref(false)
const showCashModal = ref(false)
const isProcessing  = ref(false)
const checkoutError = ref('')
const modalError    = ref('')

const selectedProduct = ref(null)
const qty        = ref(1)
const cutLength   = ref(1)
const cutWidth    = ref(1)
const editingKey  = ref(null)

const discountType  = ref('%')
const discountValue = ref(0)
const amountReceived = ref(0)

const scannerActive  = ref(false)
const autoAddOnScan  = ref(false)
const barcodeBuffer  = ref('')
const flashProductId = ref(null)

// Unified scan feedback for BOTH usb + camera modes: { type: 'success' | 'error', text }
const scanFeedback      = ref(null)
const cameraFlashVisible = ref(false)
const cameraHint        = ref('')
let scanFeedbackTimer   = null
let cameraFlashTimer    = null
let cameraHintTimer     = null

// Camera scanning mode
const scanMode       = ref('usb') // 'usb' | 'camera'
const cameraStarting = ref(false)
const cameraError    = ref('')
let html5QrCode = null
let cameraStopping = null // Promise guarding overlapping stop/start calls

// A single checkout attempt (open cash modal -> pay) shares one idempotency
// key across retries, so a resubmitted request can be deduped server-side
// instead of creating a duplicate sale.
const checkoutIdempotencyKey = ref('')

const receiptData = ref(null)

const barcodeInput = ref(null)
const searchInput  = ref(null)

let barcodeTimer   = null
let flashTimer     = null
let lastScanCode = ''
let lastScanAt   = 0
// Timestamps of the most recent USB-input keystrokes, used to tell a
// hardware scanner's burst-typing apart from a human typing manually.
let keyTimestamps = []

/* ────────────────────────────────────────────────────────────
   RESPONSIVE — MOBILE CART DRAWER
   Below this width the cart panel (.pos-right) leaves the side-by-side
   layout and becomes a bottom-sheet drawer (see the media query in the
   style block), toggled open/closed by `cartOpen`. A floating summary
   bar stays on screen whenever the drawer is closed so the running
   total and item count are never more than a tap away.
──────────────────────────────────────────────────────────── */
const POS_MOBILE_BREAKPOINT = 768
const isMobile = ref(window.innerWidth <= POS_MOBILE_BREAKPOINT)
const cartOpen = ref(false)

function onPosResize() {
  const mobile = window.innerWidth <= POS_MOBILE_BREAKPOINT
  if (mobile === isMobile.value) return
  isMobile.value = mobile
  // The drawer concept only exists on mobile — once back to a side-by-side
  // width, drop any open/closed drawer state so it doesn't linger.
  if (!mobile) cartOpen.value = false
}

/* ────────────────────────────────────────────────────────────
   CATALOG LOADING
──────────────────────────────────────────────────────────── */
async function loadCatalog() {
  loading.value = true
  loadError.value = ''
  try {
    const [prodRes, catRes] = await Promise.all([
      api.get('/api/product-list'),
      api.get('/api/categories'),
    ])
    products.value = (prodRes.data || []).map(normalizeProduct)
    categories.value = catRes.data?.categories ?? catRes.data ?? []
  } catch (e) {
    loadError.value = 'Failed to load products. Please check your connection and try again.'
    console.error('loadCatalog error:', e)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  window.addEventListener('keydown', onGlobalKeydown)
  window.addEventListener('resize', onPosResize)
  await loadCatalog()
  nextTick(() => searchInput.value?.focus())
})
onUnmounted(() => {
  window.removeEventListener('keydown', onGlobalKeydown)
  window.removeEventListener('resize', onPosResize)
  clearTimeout(barcodeTimer)
  clearTimeout(flashTimer)
  clearTimeout(scanFeedbackTimer)
  clearTimeout(cameraFlashTimer)
  clearTimeout(cameraHintTimer)
  stopCamera()
})

function onImgError(product) { product.image = null }

// Precompute cart quantities per product once per cart mutation, instead of
// re-scanning the whole cart array for every product card on every render
// (availableFor/canAddProduct/stockBadge* were each doing an O(n) scan).
const cartQtyByProduct = computed(() => {
  const m = new Map()
  cart.value.forEach(i => m.set(i.id, (m.get(i.id) || 0) + i.qty))
  return m
})
function availableFor(product) {
  return Math.max(0, product.stock - (cartQtyByProduct.value.get(product.id) || 0))
}
function canAddProduct(product) { return availableFor(product) > 0 }
/** Returns the stock-status key ('ok' | 'low' | 'oos') shared by the badge
 *  class/label AND the grid sort order, so the two can never disagree. */
function stockStatus(p) {
  const avail = availableFor(p)
  return avail <= 0 ? 'oos' : avail <= 5 ? 'low' : 'ok'
}
function stockBadgeClass(p) {
  return `badge--${stockStatus(p)}`
}
function stockBadgeLabel(p) {
  const status = stockStatus(p)
  return status === 'oos' ? 'Out of Stock' : status === 'low' ? 'Low Stock' : 'In Stock'
}

/* ────────────────────────────────────────────────────────────
   SEARCH / FILTER / SORT
   Grid order: In Stock products first, then Low Stock, then Out of
   Stock — using a stable sort so products within the same status keep
   their original relative order (alphabetical/catalog order, etc.).
──────────────────────────────────────────────────────────── */
const normalizedSearch = computed(() => search.value.trim().toLowerCase())
const filteredProducts = computed(() => {
  const q = normalizedSearch.value
  const cat = selectedCategory.value
  const list = (!q && !cat)
    ? products.value.slice()
    : products.value.filter(p => {
        if (cat && p.category != cat) return false
        if (!q) return true
        return (
          p.name.toLowerCase().includes(q) ||
          p.sku.toLowerCase().includes(q) ||
          (p.barcode && p.barcode.toLowerCase().includes(q))
        )
      })
  return list.sort((a, b) => STOCK_SORT_PRIORITY[stockStatus(a)] - STOCK_SORT_PRIORITY[stockStatus(b)])
})

/* ────────────────────────────────────────────────────────────
   BARCODE SCANNER (shared pipeline for USB + camera)
──────────────────────────────────────────────────────────── */
function toggleScanner() {
  scannerActive.value = !scannerActive.value
  barcodeBuffer.value = ''
  scanFeedback.value = null
  cameraFlashVisible.value = false
  cameraHint.value = ''
  cameraError.value = ''
  keyTimestamps = []
  // Reset dedup window so toggling the scanner off/on doesn't cause a
  // legitimate re-scan of the same code to be dropped as a "duplicate".
  lastScanCode = ''
  lastScanAt = 0
  clearTimeout(barcodeTimer)
  if (scannerActive.value) {
    if (scanMode.value === 'usb') nextTick(() => barcodeInput.value?.focus())
    // Camera mode: don't start here — the scanner banner is still animating
    // open. onScannerBannerEnter() kicks off the camera once that finishes,
    // so html5-qrcode attaches to a fully-mounted, fully-sized element.
  } else {
    stopCamera()
  }
}
/** Fires once the scanner banner's open transition finishes. Starting the
 *  camera only at this point (instead of on nextTick, while the banner is
 *  still mid max-height animation) avoids handing html5-qrcode a container
 *  that hasn't settled into its final layout yet. */
function onScannerBannerEnter() {
  if (scannerActive.value && scanMode.value === 'camera') startCamera()
}
async function setScanMode(mode) {
  if (scanMode.value === mode) return
  if (scanMode.value === 'camera') await stopCamera()
  scanMode.value = mode
  scanFeedback.value = null
  cameraHint.value = ''
  cameraError.value = ''
  if (!scannerActive.value) return
  // Banner is already open here (no transition fires), so it's safe to
  // start immediately — the container is already fully mounted.
  if (mode === 'camera') startCamera()
  else nextTick(() => barcodeInput.value?.focus())
}
/** Distinguishes a hardware (keyboard-wedge) scanner's rapid keystroke burst
 *  from a human typing the barcode by hand. Scanners fire characters only
 *  a few ms apart and are auto-submitted after a short pause. Manual typing
 *  is never auto-submitted — it always waits for Enter — so a normal typing
 *  pause can no longer wipe out a half-typed code. */
function onBarcodeKey(e) {
  if (e.key === 'Enter') {
    e.preventDefault()
    clearTimeout(barcodeTimer)
    keyTimestamps = []
    const code = barcodeBuffer.value.trim()
    barcodeBuffer.value = ''
    if (code) handleScannedCode(code)
    return
  }

  // Ignore modifier/navigation keys (Shift, Tab, ArrowLeft, ...) for timing
  // purposes — only actual content-changing keys should affect detection.
  if (e.key.length !== 1 && e.key !== 'Backspace') return

  clearTimeout(barcodeTimer)

  if (e.key === 'Backspace') {
    // Editing by hand is a clear signal this is manual entry — never
    // auto-submit; just let the person keep typing and press Enter.
    keyTimestamps = []
    return
  }

  keyTimestamps.push(Date.now())
  if (keyTimestamps.length > 6) keyTimestamps.shift()

  const looksLikeScanner = keyTimestamps.length >= 3 &&
    keyTimestamps.slice(1).every((t, i) => t - keyTimestamps[i] <= SCANNER_KEY_INTERVAL_MS)

  if (looksLikeScanner) {
    // Handles hardware scanners that don't send an Enter terminator: submit
    // shortly after the fast keystroke burst stops.
    barcodeTimer = setTimeout(() => {
      const code = barcodeBuffer.value.trim()
      if (code.length >= 4) {
        barcodeBuffer.value = ''
        keyTimestamps = []
        handleScannedCode(code)
      }
    }, SCAN_DEBOUNCE_MS)
  }
  // Otherwise this is a human typing manually — no auto-submit timer is
  // set, so the buffer is never cleared out from under them.
}
/** Keeps the hidden scanner input focused for hardware scanners, but never
 *  steals focus away from a field/button the user is deliberately using,
 *  and never competes with an open modal. */
function onGlobalKeydown() {
  if (!scannerActive.value || scanMode.value !== 'usb') return
  if (showQtyModal.value || showCashModal.value) return
  const active = document.activeElement
  const isBusyElsewhere = active && active !== barcodeInput.value &&
    ['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON'].includes(active.tagName)
  if (isBusyElsewhere) return
  if (active !== barcodeInput.value) barcodeInput.value?.focus()
}
function showFeedback(type, text, duration = SCANNER_MESSAGE_TIMEOUT_MS) {
  scanFeedback.value = { type, text }
  clearTimeout(scanFeedbackTimer)
  scanFeedbackTimer = setTimeout(() => { scanFeedback.value = null }, duration)
}
function showScannerMessage(msg) {
  showFeedback('error', msg)
}
/** Fires on every SUCCESSFUL find — regardless of scan source. Shows a
 *  green confirmation bubble, and (camera mode) a checkmark flash over
 *  the video feed so the person gets unmistakable visual confirmation
 *  the scan registered before the modal/cart even updates. */
function showScanSuccess(name) {
  showFeedback('success', `Found: ${name}`, 1500)
  if (scanMode.value === 'camera') {
    cameraFlashVisible.value = true
    clearTimeout(cameraFlashTimer)
    cameraFlashTimer = setTimeout(() => { cameraFlashVisible.value = false }, 850)
  }
  // A successful decode proves the camera CAN read barcodes fine right
  // now, so clear/reset the "having trouble?" hint countdown.
  scheduleCameraHint()
}
/** Shows a gentle troubleshooting tip if the camera has been active for a
 *  while without successfully decoding anything — covers blur, bad
 *  lighting, wrong distance, etc. without needing to detect the specific
 *  cause (html5-qrcode's per-frame errors are too noisy/generic for that). */
function scheduleCameraHint() {
  clearTimeout(cameraHintTimer)
  cameraHint.value = ''
  if (scanMode.value !== 'camera' || !scannerActive.value) return
  cameraHintTimer = setTimeout(() => {
    cameraHint.value = 'Having trouble? Hold the barcode flat and steady, fill the frame, and make sure there\u2019s good lighting.'
  }, 5000)
}
function findProductLocal(code) {
  const c = code.toLowerCase()
  return products.value.find(p =>
    (p.barcode && p.barcode.toLowerCase() === c) || (p.sku && p.sku.toLowerCase() === c)
  )
}
function triggerFlash(id) {
  flashProductId.value = id
  clearTimeout(flashTimer)
  flashTimer = setTimeout(() => { flashProductId.value = null }, FLASH_DURATION_MS)
}

/** Single entry point for ANY scan source (USB keyboard-wedge or camera).
 *  Handles duplicate-scan suppression, then defers to lookupAndHandle. */
function handleScannedCode(rawCode) {
  const code = rawCode.trim()
  if (!code) return
  const now = Date.now()
  if (code === lastScanCode && (now - lastScanAt) < BARCODE_DUPLICATE_WINDOW_MS) return
  lastScanCode = code
  lastScanAt = now
  lookupAndHandle(code)
}
async function lookupAndHandle(code) {
  const local = findProductLocal(code)
  if (local) {
    triggerFlash(local.id)
    handleFoundProduct(local)
    return
  }
  try {
    const { data } = await api.get(`/api/products/barcode/${encodeURIComponent(code)}`)
    if (!data?.success || !data.product) {
      showScannerMessage('Product not found.')
      return
    }
    const product = normalizeProduct(data.product)
    products.value.push(product)
    triggerFlash(product.id)
    handleFoundProduct(product)
  } catch (e) {
    showScannerMessage('Product not found.')
    console.error('barcode lookup error:', e)
  }
}
function handleFoundProduct(product) {
  if (availableFor(product) <= 0) {
    showScannerMessage(`${product.name} is out of stock.`)
    return
  }
  showScanSuccess(product.name)
  if (autoAddOnScan.value && product.pricingType !== 'sqm') {
    quickAddOne(product)
  } else {
    openQuantityModal(product)
  }
}
function quickAddOne(product) {
  if (availableFor(product) <= 0) {
    showScannerMessage(`Not enough stock for ${product.name}.`)
    return
  }
  const key = buildKey(product, 1, 1)
  const existing = cart.value.find(i => i.key === key)
  if (existing) existing.qty += 1
  else cart.value.push({ key, id: product.id, name: product.name, price: product.price, qty: 1, cutLength: null, cutWidth: null, pricingType: product.pricingType })
}

/* ── Camera scanning (html5-qrcode), reusing handleScannedCode ── */
function describeCameraError(e) {
  const name = e?.name || ''
  if (name === 'NotAllowedError') return 'Camera access was denied. Allow camera permission in your browser and try again.'
  if (name === 'NotFoundError') return 'No camera was found on this device.'
  if (name === 'NotReadableError') return 'Camera is already in use by another application.'
  if (name === 'OverconstrainedError') return 'No matching camera could be found on this device.'
  const msg = String(e?.message || e || '').toLowerCase()
  if (msg.includes('secure') || msg.includes('https')) return 'Camera access requires HTTPS (or localhost).'
  return 'Unable to access camera. Check permissions and try again.'
}
async function startCamera() {
  if (html5QrCode || cameraStarting.value) return
  // If a previous camera instance is still tearing down, wait for that to
  // finish before attaching a new one to the same DOM node — starting a
  // new instance mid-teardown was a real source of "camera doesn't scan".
  if (cameraStopping) await cameraStopping.catch(() => {})

  cameraError.value = ''
  cameraStarting.value = true
  try {
    await nextTick() // ensure the #pos-camera-scanner element exists
    html5QrCode = new Html5Qrcode(CAMERA_ELEMENT_ID, {
      formatsToSupport: CAMERA_FORMATS,
      verbose: false,
    })
    await html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 640, height: 480 } },
      (decodedText) => handleScannedCode(decodedText), // same pipeline as USB scans
      () => { /* per-frame "not found" noise, ignore */ }
    )
    scheduleCameraHint() // start the "having trouble?" countdown once live
  } catch (e) {
    cameraError.value = describeCameraError(e)
    console.error('camera start error:', e)
    html5QrCode = null
  } finally {
    cameraStarting.value = false
  }
}
async function stopCamera() {
  clearTimeout(cameraHintTimer)
  cameraHint.value = ''
  if (!html5QrCode) return
  const instance = html5QrCode
  html5QrCode = null // clear immediately so overlapping calls can't double-stop
  cameraStopping = (async () => {
    try {
      await instance.stop()
      instance.clear()
    } catch (e) {
      console.error('camera stop error:', e)
    }
  })()
  await cameraStopping
  cameraStopping = null
}

/* ────────────────────────────────────────────────────────────
   QUANTITY MODAL / CUT-SIZE PRICING
──────────────────────────────────────────────────────────── */
function openQuantityModal(product) {
  selectedProduct.value = product
  editingKey.value = null
  modalError.value = ''
  qty.value = 1
  cutLength.value = product.minDimension || 1
  cutWidth.value = product.minDimension || 1
  showQtyModal.value = true
}
function closeQtyModal() {
  showQtyModal.value = false
  modalError.value = ''
  editingKey.value = null
}
function editCartItem(item) {
  const product = products.value.find(p => p.id === item.id) || {
    id: item.id,
    name: item.name,
    price: item.pricingType === 'sqm' && item.cutLength && item.cutWidth
      ? round2(item.price / (item.cutLength * item.cutWidth))
      : item.price,
    stock: item.qty,
    pricingType: item.pricingType,
    minDimension: MIN_DIM,
    maxDimension: MAX_DIM,
  }
  selectedProduct.value = product
  cutLength.value = item.cutLength || product.minDimension || 1
  cutWidth.value = item.cutWidth || product.minDimension || 1
  qty.value = item.qty
  editingKey.value = item.key
  modalError.value = ''
  showQtyModal.value = true
}

const isSqm = computed(() => selectedProduct.value?.pricingType === 'sqm')
const cutArea = computed(() => {
  if (!selectedProduct.value || !isSqm.value) return 0
  const min = selectedProduct.value.minDimension ?? MIN_DIM
  const max = selectedProduct.value.maxDimension ?? MAX_DIM
  return round2(clamp(cutLength.value, min, max) * clamp(cutWidth.value, min, max))
})
const unitPrice = computed(() => {
  if (!selectedProduct.value) return 0
  return isSqm.value ? round2(selectedProduct.value.price * cutArea.value) : selectedProduct.value.price
})
/** Quantity of a product currently committed elsewhere in the cart,
 *  optionally excluding one line (used while editing that line). */
function inCart(id, excludeKey = null) {
  if (id == null) return 0
  return cart.value
    .filter(i => i.id === id && i.key !== excludeKey)
    .reduce((s, i) => s + i.qty, 0)
}
const availableStock = computed(() => {
  if (!selectedProduct.value) return 0
  return Math.max(0, selectedProduct.value.stock - inCart(selectedProduct.value.id, editingKey.value))
})

// Clamp cut dimensions to the product's allowed range.
watch([cutLength, cutWidth], () => {
  if (!selectedProduct.value || !isSqm.value) return
  const min = selectedProduct.value.minDimension ?? MIN_DIM
  const max = selectedProduct.value.maxDimension ?? MAX_DIM
  const cl = clamp(cutLength.value, min, max)
  const cw = clamp(cutWidth.value, min, max)
  if (cl !== cutLength.value) cutLength.value = cl
  if (cw !== cutWidth.value) cutWidth.value = cw
})
// Clamp quantity to [1, availableStock].
watch(qty, (v) => {
  const max = Math.max(availableStock.value, 1)
  const n = clamp(Math.trunc(Number(v) || 1), 1, max)
  if (n !== v) qty.value = n
})

function addToCart() {
  if (!selectedProduct.value) return
  modalError.value = ''

  const requestedQty = clamp(Math.trunc(qty.value) || 1, 1, 999999)
  const maxAllowed = availableStock.value

  if (maxAllowed <= 0) {
    modalError.value = 'No stock available for this item.'
    return
  }
  if (requestedQty > maxAllowed) {
    modalError.value = `Only ${maxAllowed} unit(s) available.`
    return
  }

  const key = buildKey(selectedProduct.value, cutLength.value, cutWidth.value)
  const dimensionLabel = isSqm.value ? ` (${cutLength.value}×${cutWidth.value}m)` : ''

  // If editing and the key changed (dimensions changed), remove the old line first.
  if (editingKey.value && editingKey.value !== key) {
    cart.value = cart.value.filter(i => i.key !== editingKey.value)
  }

  const existing = cart.value.find(i => i.key === key)
  if (existing) {
    // Overwrite the quantity only when we're editing THIS SAME line.
    // If we're editing a different line whose new dimensions happen to
    // match an already-existing line, we're merging into it — so add,
    // don't clobber the quantity that was already there.
    existing.qty = (editingKey.value === key) ? requestedQty : existing.qty + requestedQty
    existing.price = unitPrice.value
  } else {
    cart.value.push({
      key,
      id: selectedProduct.value.id,
      name: `${selectedProduct.value.name}${dimensionLabel}`,
      price: unitPrice.value,
      qty: requestedQty,
      cutLength: isSqm.value ? cutLength.value : null,
      cutWidth: isSqm.value ? cutWidth.value : null,
      pricingType: selectedProduct.value.pricingType,
    })
  }

  closeQtyModal()
}

/* ────────────────────────────────────────────────────────────
   CART
──────────────────────────────────────────────────────────── */
function incrementLine(item) {
  const product = products.value.find(p => p.id === item.id)
  const stock = product ? product.stock : Infinity
  const usedByOthers = inCart(item.id, item.key)
  if (item.qty + usedByOthers + 1 > stock) {
    checkoutError.value = `Not enough stock to add another unit of ${item.name}.`
    return
  }
  item.qty += 1
}
function decrementLine(item) {
  if (item.qty <= 1) { removeFromCart(item.key); return }
  item.qty -= 1
}
function removeFromCart(key) { cart.value = cart.value.filter(i => i.key !== key) }
function clearCart() { if (confirm('Clear all items from the cart?')) cart.value = [] }

/* ────────────────────────────────────────────────────────────
   TOTALS — VAT-INCLUSIVE PRICING
   Product prices already include 12% VAT. VAT is EXTRACTED, never added:
     Gross Amount  = Subtotal - Discount
     VATable Sales = Gross Amount / 1.12
     VAT           = Gross Amount - VATable Sales
     Total         = Gross Amount
──────────────────────────────────────────────────────────── */
const subtotal = computed(() => round2(cart.value.reduce((s, i) => s + i.qty * i.price, 0)))

function clampDiscount() {
  if (discountValue.value < 0 || Number.isNaN(discountValue.value)) discountValue.value = 0
  if (discountType.value === '%') {
    if (discountValue.value > 100) discountValue.value = 100
  } else if (discountValue.value > subtotal.value) {
    discountValue.value = round2(subtotal.value)
  }
}
watch([discountType, subtotal, discountValue], clampDiscount)

const discountAmount = computed(() => {
  const raw = discountType.value === '%'
    ? subtotal.value * (discountValue.value / 100)
    : discountValue.value
  return round2(Math.min(Math.max(raw, 0), subtotal.value))
})

// Gross Amount = Subtotal - Discount (discount is applied BEFORE VAT is extracted)
const grossAmount = computed(() => round2(Math.max(subtotal.value - discountAmount.value, 0)))
// VATable Sales = Gross Amount / 1.12
const vatableSales = computed(() => round2(grossAmount.value / VAT_DIVISOR))
// VAT = Gross Amount - VATable Sales (the 12% already baked into the price)
const vat = computed(() => round2(grossAmount.value - vatableSales.value))
// Total = Gross Amount (VAT is included, not added on top)
const total = computed(() => grossAmount.value)

// Guard against NaN explicitly (e.g. amountReceived cleared to '' in the
// input) rather than relying on implicit '' -> 0 coercion in arithmetic.
const amountReceivedNum = computed(() => {
  const n = Number(amountReceived.value)
  return Number.isFinite(n) ? n : 0
})
const change = computed(() => round2(Math.max(amountReceivedNum.value - total.value, 0)))
const quickAmounts = computed(() => {
  const t = Math.ceil(total.value)
  if (t <= 0) return []
  const candidates = new Set([t])
  ;[100, 500, 1000].forEach(denom => {
    const v = Math.ceil(t / denom) * denom
    if (v >= t) candidates.add(v)
  })
  return Array.from(candidates).sort((a, b) => a - b).slice(0, 4)
})

function generateCustomerName() {
  const ts = new Date()
  customerName.value = `Walk-in ${ts.getMonth() + 1}/${ts.getDate()} ${String(ts.getHours()).padStart(2, '0')}${String(ts.getMinutes()).padStart(2, '0')}`
}

/* ────────────────────────────────────────────────────────────
   CHECKOUT
──────────────────────────────────────────────────────────── */
function verifyStockBeforeCheckout() {
  const totals = {}
  cart.value.forEach(i => { totals[i.id] = (totals[i.id] || 0) + i.qty })
  for (const id in totals) {
    const product = products.value.find(p => p.id == id)
    if (!product) continue // product only known via a fresh scan; server will validate
    if (totals[id] > product.stock) {
      return `Not enough stock for ${product.name}. Only ${product.stock} available.`
    }
  }
  return ''
}

function openCashModal() {
  checkoutError.value = ''
  if (!customerName.value.trim()) { checkoutError.value = 'Please enter a customer name before processing payment.'; return }
  if (!cart.value.length) { checkoutError.value = 'Cart is empty. Please add products first.'; return }
  if (total.value <= 0) { checkoutError.value = 'Total must be greater than zero.'; return }
  const stockIssue = verifyStockBeforeCheckout()
  if (stockIssue) { checkoutError.value = stockIssue; return }
  amountReceived.value = 0
  // Fresh idempotency key per checkout attempt; reused across internal
  // retries of THIS attempt so the backend can dedupe a resubmitted request.
  checkoutIdempotencyKey.value = (crypto.randomUUID
    ? crypto.randomUUID()
    : `${Date.now()}-${Math.random().toString(36).slice(2)}`)
  showCashModal.value = true
}
function closeCashModal() {
  if (isProcessing.value) return
  showCashModal.value = false
}

async function postSaleWithRetry(payload, attempts = 2) {
  let lastErr
  for (let i = 0; i <= attempts; i++) {
    try {
      return await api.post('/api/sales/process', payload)
    } catch (err) {
      lastErr = err
      const status = err?.response?.status
      // Don't retry validation / client errors — only transient/network/server errors.
      if (status && status < 500) throw err
      if (i < attempts) await new Promise(r => setTimeout(r, 400 * (i + 1)))
    }
  }
  throw lastErr
}

async function completePayment() {
  if (isProcessing.value) return
  if (amountReceivedNum.value < total.value) { checkoutError.value = 'Amount received is insufficient.'; return }

  const stockIssue = verifyStockBeforeCheckout()
  if (stockIssue) { checkoutError.value = stockIssue; showCashModal.value = false; return }

  isProcessing.value = true
  checkoutError.value = ''
  try {
    const payload = {
      total_amount: total.value,
      subtotal: subtotal.value,
      vatable_sales: vatableSales.value,
      vat_amount: vat.value,
      discount_amount: discountAmount.value,
      discount_type: discountType.value,
      discount_value: discountValue.value,
      amount_paid: amountReceivedNum.value,
      change_amount: change.value,
      customer_name: customerName.value.trim(),
      idempotency_key: checkoutIdempotencyKey.value,
      items: cart.value.map(i => ({
        product_id: i.id,
        quantity: i.qty,
        price: i.price,
        subtotal: round2(i.qty * i.price),
      })),
    }
    const { data } = await postSaleWithRetry(payload)

    // Snapshot everything the receipt needs BEFORE we clear state, so printing
    // never races against the cart/customer fields being reset.
    receiptData.value = {
      receiptNumber: data?.receipt_number || data?.sale_id || `SSC-${Date.now().toString(36).toUpperCase()}`,
      date: new Date().toLocaleString(),
      customer: customerName.value.trim(),
      cashier: cashierName.value,
      items: cart.value.map(i => ({ name: i.name, qty: i.qty, price: i.price, total: round2(i.qty * i.price) })),
      subtotal: subtotal.value,
      discount: discountAmount.value,
      vatableSales: vatableSales.value,
      vat: vat.value,
      total: total.value,
      cash: amountReceivedNum.value,
      change: change.value,
    }
    await nextTick()
    window.print()

    cart.value = []
    customerName.value = ''
    amountReceived.value = 0
    discountValue.value = 0
    checkoutIdempotencyKey.value = ''
    showCashModal.value = false
    cartOpen.value = false // sale's done — collapse the mobile drawer back to the summary bar

    await loadCatalog() // refresh stock after a successful sale

    // Tell Layout.vue to re-fetch notifications right now instead of
    // waiting for its 30s poll — a checkout can just have pushed an item
    // into low-stock/out-of-stock territory, and that should show up in
    // the bell immediately, not up to 30 seconds later.
    window.dispatchEvent(new CustomEvent('notifications:refresh'))
  } catch (err) {
    checkoutError.value = err?.response?.data?.message || 'Payment failed. Please try again.'
    console.error('completePayment error:', err)
  } finally {
    isProcessing.value = false
  }
}
</script>

<style scoped>
/* ── TOKEN BRIDGE ──
   Used by everything in this file that is NOT teleported (the product
   grid, cart panel, scanner UI, etc.) — those elements remain real
   descendants of .pos-root, so this bridge reaches them fine.
   IMPORTANT: the two modals below (Add to Cart / Cash Payment) are each
   wrapped in <Teleport to="body">. Once teleported, they are moved to be
   direct children of <body> in the ACTUAL DOM — CSS custom properties
   inherit through the real rendered tree, not the template's visual
   nesting, so none of the --p-* variables defined here reach anything
   inside a teleported subtree. This was confirmed via devtools: on the
   live modal, `background-color` computed to `rgba(0,0,0,0)` (the
   browser's own default) and `border-style` computed to `none` — proof
   that `var(--p-surface-overlay)` etc. resolved to nothing there, making
   the whole declaration invalid rather than merely "too transparent."
   Every rule scoped to `.modal-backdrop` / `.modal` and its descendants
   therefore references `--c-*` tokens directly (with the same inline
   fallback values) instead of the `--p-*` bridge, so it never depends on
   `.pos-root` being an ancestor. See that block further down for the fix.

   NOTE ON .pcard-* TEXT COLORS: for a while, five rules below
   (.pcard-name, .pcard-sku, .pcard-price, .pcard-stock-count, and
   .product-img-placeholder) hardcoded literal dark-mode hex colors
   (e.g. #f1f5f9, #94a3b8) instead of going through this --p-* bridge
   like every other rule in this file. Since those literals were tuned
   for a dark card background, they read fine in dark mode but had poor
   contrast against the light-mode white card background. They've been
   switched to the same --p-text-primary / --p-text-faint / --p-accent
   bridge variables everything else here uses, so they now correctly
   flip with the active theme instead of being stuck on dark-mode
   values. */
.pos-root {
  --p-bg:             var(--c-bg, #0f172a);
  --p-surface:         var(--c-surface, #1e293b);
  --p-surface-overlay: var(--c-surface-overlay, #1e293b);
  --p-surface-raised:  var(--c-surface-raised, #26364b);
  --p-surface-sunken: var(--c-surface-sunken, #0b1524);
  --p-border:         var(--c-border, #334155);
  --p-border-strong:  var(--c-border-strong, #47566b);
  --p-text-primary:   var(--c-text-primary, #f1f5f9);
  --p-text-secondary: var(--c-text-secondary, #cbd5e1);
  --p-text-muted:     var(--c-text-muted, #94a3b8);
  --p-text-faint:     var(--c-text-faint, #64748b);
  --p-accent:         var(--c-accent, #f97316);
  --p-accent-soft:    var(--c-accent-soft, rgba(249,115,22,.15));
  --p-accent-border:  var(--c-accent-border, rgba(249,115,22,.35));
  --p-ring:           var(--c-accent-ring, rgba(249,115,22,.25));
  --p-shadow:         var(--c-shadow-sm, 0 1px 3px rgba(0,0,0,.3));
  --p-shadow-md:      var(--c-shadow-md, 0 6px 16px rgba(0,0,0,.35));
  --p-green:          #10b981;
  --p-red:            #f43f5e;
  --radius: 14px;

  box-sizing: border-box;
  margin: -28px;
  display: flex;
  height: calc(100vh - 65px);
  background: var(--p-bg);
  overflow: hidden;
  font-family: 'Inter', system-ui, sans-serif;
  transition: background-color .22s ease;
  position: relative; /* anchors the mobile cart bar/backdrop, which are
                          position:fixed and just need this in the tree
                          for stacking purposes, not for positioning */
}

/* ── LEFT PANEL ── */
.pos-left {
  flex: 1; display: flex; flex-direction: column;
  padding: 16px 20px; overflow: hidden; gap: 10px; min-width: 0;
  min-height: 0;
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
.scanner-text { flex: 1; min-width: 0; }
.scanner-title { font-size: 13.5px; font-weight: 700; color: var(--p-text-primary); margin: 0 0 2px; }
.scanner-sub   { font-size: 12px; color: var(--p-text-muted); margin: 0 0 8px; }

.scanner-mode-toggle { display: inline-flex; gap: 6px; margin-bottom: 8px; flex-wrap: wrap; }
.smode-btn {
  display: flex; align-items: center; gap: 5px;
  height: 26px; padding: 0 10px;
  border: 1.5px solid var(--p-border-strong); border-radius: 20px;
  background: var(--p-surface); color: var(--p-text-muted);
  font-size: 11px; font-weight: 600; font-family: inherit; cursor: pointer;
  transition: border-color .15s, background-color .15s, color .15s;
}
.smode-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); }
.smode-btn--active { background: var(--p-accent); border-color: var(--p-accent); color: #fff; }

.scanner-input-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.barcode-hidden-input {
  width: 200px; max-width: 100%; height: 34px;
  border: 1.5px solid var(--p-border-strong); border-radius: 8px;
  padding: 0 12px; font-size: 12.5px; font-family: inherit;
  color: var(--p-text-primary); background: var(--p-surface-sunken);
  outline: none; transition: border-color .18s, background-color .22s;
}
.barcode-hidden-input:focus { border-color: var(--p-accent); background: var(--p-surface); }
.barcode-hidden-input::placeholder { color: var(--p-text-faint); }
.barcode-preview { font-size: 12.5px; font-weight: 600; color: var(--p-accent); display: flex; align-items: center; gap: 6px; margin: 0; }
.barcode-hint    { font-size: 12px; color: var(--p-text-faint); font-style: italic; margin: 0; display: flex; align-items: center; gap: 6px; }
.barcode-hint--success { color: var(--p-green); font-style: normal; font-weight: 600; }
.barcode-hint--error   { color: var(--p-red);   font-style: normal; font-weight: 600; }
.barcode-hint--tip     { color: var(--p-text-faint); font-style: italic; margin-top: 4px; }
.feedback-fade-enter-active, .feedback-fade-leave-active { transition: opacity .18s ease; }
.feedback-fade-enter-from, .feedback-fade-leave-to { opacity: 0; }
.scanner-autoadd { display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--p-text-muted); margin-top: 8px; cursor: pointer; }
.scanner-autoadd input { accent-color: var(--p-accent); }

.camera-scan-wrap { display: flex; flex-direction: column; gap: 6px; }
.camera-scan-box-outer { position: relative; width: 220px; max-width: 100%; }
.camera-scan-box {
  width: 220px; max-width: 100%; min-height: 140px;
  border: 1.5px solid var(--p-border-strong); border-radius: 8px;
  overflow: hidden; background: var(--p-surface-sunken);
}
.camera-scan-box :deep(video) { border-radius: 8px; }

/* ── Camera scan feedback ── */
.camera-flash-overlay {
  position: absolute; inset: 0;
  background: rgba(16,185,129,.30);
  display: flex; align-items: center; justify-content: center;
  border-radius: 8px;
}
.camera-flash-check {
  width: 48px; height: 48px; border-radius: 50%;
  background: var(--p-green); color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; box-shadow: 0 4px 14px rgba(16,185,129,.45);
}
.flash-pop-enter-active { transition: transform .22s cubic-bezier(.34,1.56,.64,1), opacity .18s ease; }
.flash-pop-leave-active { transition: opacity .25s ease; }
.flash-pop-enter-from   { opacity: 0; transform: scale(.6); }
.flash-pop-leave-to     { opacity: 0; }

.camera-reticle { position: absolute; inset: 14px; pointer-events: none; }
.camera-reticle-corner { position: absolute; width: 16px; height: 16px; border-color: rgba(255,255,255,.85); border-style: solid; }
.camera-reticle-corner--tl { top:0;    left:0;  border-width: 2px 0 0 2px; }
.camera-reticle-corner--tr { top:0;    right:0; border-width: 2px 2px 0 0; }
.camera-reticle-corner--bl { bottom:0; left:0;  border-width: 0 0 2px 2px; }
.camera-reticle-corner--br { bottom:0; right:0; border-width: 0 2px 2px 0; }

/* Product meta */
.product-meta   { display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap; }
.product-count  { font-size: 12px; font-weight: 600; color: var(--p-text-faint); text-transform: uppercase; letter-spacing: .07em; }
.product-filter-tag {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 600; color: var(--p-accent);
  background: var(--p-accent-soft); border: 1px solid var(--p-accent-border);
  border-radius: 20px; padding: 2px 8px;
}
.product-filter-tag button { background: none; border: none; cursor: pointer; color: inherit; font-size: 10px; padding: 0; }

/* Load state */
.load-state {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 10px; color: var(--p-text-faint); font-size: 13px;
}
.load-state i { font-size: 28px; }
.load-state--error { color: var(--p-red); }
.load-retry-btn {
  border: 1.5px solid var(--p-border-strong); border-radius: 8px;
  background: var(--p-surface); padding: 6px 14px; font-size: 12.5px; font-weight: 600;
  color: var(--p-text-secondary); cursor: pointer; font-family: inherit;
}
.load-retry-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); }

/* Product grid */
.product-grid {
  flex: 1; min-height: 0; overflow-y: scroll;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(clamp(110px, 10vw, 132px), 1fr));
  gap: 10px; align-content: start; padding-right: 6px; padding-bottom: 8px;
  scrollbar-width: auto; scrollbar-color: var(--p-border-strong) var(--p-surface-sunken);
}
.product-grid::-webkit-scrollbar { width: 10px; }
.product-grid::-webkit-scrollbar-thumb {
  background: var(--p-border-strong);
  border-radius: 6px;
  border: 2px solid var(--p-surface-sunken);
}
.product-grid::-webkit-scrollbar-thumb:hover { background: var(--p-text-faint); }
.product-grid::-webkit-scrollbar-track { background: var(--p-surface-sunken); border-radius: 6px; }

.product-card {
  background: var(--p-surface);
  border: 1.5px solid var(--p-border); border-radius: 10px;
  padding: 8px; cursor: pointer; position: relative;
  overflow: visible;
  min-height: 228px;
  box-shadow: var(--p-shadow);
  display: flex; flex-direction: column;
  transition: transform .18s, box-shadow .18s, border-color .18s, background-color .22s;
}
.product-card:hover { transform: translateY(-2px); box-shadow: var(--p-shadow-md); border-color: var(--p-accent); }
.product-card--oos  { opacity: .55; cursor: not-allowed; }
.product-card--oos:hover { transform: none; box-shadow: var(--p-shadow); border-color: var(--p-border); }
.product-card--flash { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); }

.product-stock-badge {
  position: absolute; top: 7px; right: 7px;
  font-size: 8px; font-weight: 700; padding: 3px 6px;
  border-radius: 20px; letter-spacing: .02em; text-transform: uppercase;
  z-index: 1;
}
.badge--ok  { background: rgba(16,185,129,.15); color: #10b981; }
.badge--low { background: rgba(249,115,22,.15); color: #f97316; }
.badge--oos { background: rgba(244,63,94,.14);  color: #f43f5e; }

.product-img-wrap {
  width: 100%; height: 96px; flex-shrink: 0; border-radius: 7px;
  background: var(--p-surface-sunken); overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 6px; transition: background-color .22s;
}
.product-img { width: 100%; height: 100%; object-fit: contain; }
/* Was hardcoded #64748b (a dark-mode-only gray) — switched to the
   --p-text-faint bridge so the placeholder icon color flips with the
   active theme instead of staying fixed to the dark-mode value. */
.product-img-placeholder { color: var(--p-text-faint); font-size: 22px; }

.pcard-info    { flex: 1; min-height: 78px; display: flex; flex-direction: column; justify-content: flex-end; gap: 3px; }
/* Was hardcoded #f1f5f9 (near-white, tuned for dark cards) — this had
   almost no contrast against a light-mode white card. Switched to
   --p-text-primary so it resolves to the correct dark-on-light /
   light-on-dark color depending on the active theme. */
.pcard-name {
  font-size: 12.5px; font-weight: 700; color: var(--p-text-primary);
  margin: 0; line-height: 1.3;
  display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden; word-break: break-word;
  min-height: 32.5px;
}
/* Was hardcoded #94a3b8 — switched to --p-text-faint for the same
   theme-contrast reason as .pcard-name above. */
.pcard-sku { font-size: 10px; color: var(--p-text-faint); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pcard-tag {
  font-size: 9px; font-weight: 700; color: var(--p-accent);
  background: var(--p-accent-soft); border: 1px solid var(--p-accent-border);
  border-radius: 20px; padding: 1px 6px; margin: 2px 0 0; display: inline-block; width: fit-content;
}
.pcard-footer  { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
/* Was hardcoded #fb923c (a lighter dark-mode-tuned orange) — switched to
   --p-accent so the price always uses the theme's actual accent color
   (which already has good contrast in both modes) instead of a fixed
   shade picked for dark backgrounds. */
.pcard-price       { font-size: 14px; font-weight: 800; color: var(--p-accent); }
/* Was hardcoded #94a3b8 — same fix as .pcard-sku above. */
.pcard-stock-count { font-size: 10px; font-weight: 600; color: var(--p-text-faint); }

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

.cart-drawer-handle {
  width: 36px; height: 4px; border-radius: 3px;
  background: var(--p-border-strong);
  margin: 10px auto 0;
  flex-shrink: 0;
}

/* Cart header */
.cart-header { padding: 18px 20px 14px; border-bottom: 1px solid var(--p-border); flex-shrink: 0; transition: border-color .22s; }
.cart-header-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
.cart-header-top-right { display: flex; align-items: center; gap: 8px; }
.cart-eyebrow { font-size: 10.5px; font-weight: 700; color: var(--p-accent); text-transform: uppercase; letter-spacing: .1em; margin: 0 0 3px; }
.cart-title   { font-size: 17px; font-weight: 800; color: var(--p-text-primary); margin: 0; letter-spacing: -.02em; transition: color .22s; }
.cart-badge   {
  font-size: 11px; font-weight: 700;
  background: var(--p-accent-soft); color: var(--p-accent);
  border: 1px solid var(--p-accent-border); border-radius: 20px; padding: 3px 10px;
}
.cart-drawer-close {
  width: 30px; height: 30px; border: none; border-radius: 8px;
  background: var(--p-surface-raised); color: var(--p-text-muted);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 12px; flex-shrink: 0;
  transition: background .15s, color .15s;
}
.cart-drawer-close:hover { background: var(--p-border-strong); color: var(--p-text-primary); }

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
  flex: 1; min-height: 0; overflow-y: auto; padding: 14px 20px;
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
.cart-item-qty-mini { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
.cart-item-qty-btn {
  width: 22px; height: 22px; border: 1px solid var(--p-border-strong); border-radius: 6px;
  background: var(--p-surface); color: var(--p-text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 9.5px;
  transition: border-color .15s, color .15s, background .15s;
}
.cart-item-qty-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); background: var(--p-accent-soft); }
.cart-item-qty-val { font-size: 12px; font-weight: 700; color: var(--p-text-primary); width: 18px; text-align: center; }
.cart-item-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.cart-item-total { font-size: 13px; font-weight: 700; color: var(--p-text-primary); }
.cart-item-edit, .cart-item-del {
  width: 26px; height: 26px; border: none; border-radius: 7px;
  background: none; color: var(--p-text-faint); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 11px;
  transition: background .15s, color .15s;
}
.cart-item-edit:hover { background: var(--p-accent-soft); color: var(--p-accent); }
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

.checkout-error { font-size: 12px; font-weight: 600; color: var(--p-red); margin: 0 0 10px; text-align: center; }

.pay-btn {
  width: 100%; height: 46px; border: none; border-radius: 11px;
  background: linear-gradient(135deg, var(--p-accent) 0%, var(--c-accent-deep, #c2410c) 100%);
  color: #fff; font-size: 14px; font-weight: 700; font-family: inherit;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  box-shadow: var(--c-shadow-accent, 0 4px 14px rgba(249,115,22,.35)); margin-bottom: 8px;
  transition: opacity .18s, transform .18s, box-shadow .18s;
}
.pay-btn:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); box-shadow: var(--c-shadow-accent-h, 0 6px 20px rgba(249,115,22,.45)); }
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

/* ── MOBILE CART DRAWER CHROME ── */
.cart-backdrop {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 380;
}

.mobile-cart-bar {
  position: fixed;
  left: 12px; right: 12px;
  bottom: calc(12px + env(safe-area-inset-bottom, 0px));
  z-index: 390;
  display: flex; align-items: center; gap: 12px;
  height: 56px; padding: 0 16px 0 14px;
  border: none; border-radius: 16px;
  background: linear-gradient(135deg, var(--p-accent) 0%, var(--c-accent-deep, #c2410c) 100%);
  color: #fff; font-family: inherit; cursor: pointer;
  box-shadow: 0 10px 28px rgba(0,0,0,.4), var(--c-shadow-accent, 0 4px 14px rgba(249,115,22,.35));
}
.mobile-cart-bar-icon { position: relative; font-size: 16px; display: flex; align-items: center; justify-content: center; }
.mobile-cart-bar-badge {
  position: absolute; top: -8px; right: -10px;
  min-width: 16px; height: 16px; padding: 0 4px;
  border-radius: 999px; background: #fff; color: var(--p-accent);
  font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center;
}
.mobile-cart-bar-total { font-size: 15px; font-weight: 800; letter-spacing: -.02em; }
.mobile-cart-bar-cta { margin-left: auto; font-size: 12.5px; font-weight: 700; display: flex; align-items: center; gap: 6px; opacity: .95; }

/* ── MODALS ──
   These live inside <Teleport to="body">, so they render OUTSIDE
   .pos-root in the actual DOM tree. CSS custom properties inherit
   through the real rendered tree, not the template's visual nesting —
   so the --p-* bridge tokens defined on .pos-root do NOT reach
   anything here. Every rule below uses --c-* directly with an inline
   fallback instead of --p-*, so it never depends on .pos-root being
   an ancestor.

   CONFIRMED VIA DEVTOOLS: on the live teleported .modal, computed
   background-color was rgba(0,0,0,0) (the browser's own initial
   value) and computed border-style was `none` on all sides — proof
   that var(--p-surface-overlay) / var(--p-border) resolved to nothing
   at all in that context, which invalidates the whole declaration
   they're used in (background-color falls back to transparent,
   border falls back to none/currentColor). This is NOT an alpha-
   channel/transparency problem and NOT a specificity/cascade problem
   — it's an undefined-custom-property problem, caused specifically by
   Teleport moving this subtree outside where --p-* is defined. */
.modal-backdrop {
  position: fixed; inset: 0;
  background: var(--c-overlay, rgba(0,0,0,.6)); backdrop-filter: blur(6px);
  z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal {
  position: relative;
  isolation: isolate;
  opacity: 1 !important;
  backdrop-filter: none !important;
  background-color: var(--c-surface-overlay, #1e293b) !important;
  border-radius: 20px;
  width: 100%; max-width: 480px;
  max-height: calc(100vh - 40px);
  display: flex; flex-direction: column;
  box-shadow: var(--c-shadow-xl, 0 24px 60px rgba(0,0,0,.5)); overflow: hidden;
  border: 1px solid var(--c-border, #334155);
  transition: background-color .22s, border-color .22s;
}
.modal--narrow { max-width: 380px; }

.modal-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 20px 22px 16px; border-bottom: 1px solid var(--c-border, #334155);
  transition: border-color .22s; flex-shrink: 0;
}
.modal-eyebrow { font-size: 10.5px; font-weight: 700; color: var(--c-accent, #f97316); text-transform: uppercase; letter-spacing: .1em; margin: 0 0 3px; }
.modal-title   { font-size: 16px; font-weight: 700; color: var(--c-text-primary, #f1f5f9); margin: 0; transition: color .22s; }
.modal-close {
  width: 30px; height: 30px; border: none;
  background: var(--c-surface-raised, #26364b); border-radius: 8px;
  color: var(--c-text-muted, #94a3b8); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 12px;
  transition: background .15s, color .15s;
}
.modal-close:hover { background: var(--c-border-strong, #47566b); color: var(--c-text-primary, #f1f5f9); }

.modal-body { padding: 20px 22px; display: flex; flex-direction: column; gap: 18px; overflow-y: auto; min-height: 0; }

/* ── Add to Cart modal: product header card ── */
.modal-product-card {
  display: flex; align-items: center; gap: 14px;
  background-color: var(--c-surface-raised, #26364b) !important;
  opacity: 1 !important;
  border: 1px solid var(--c-border, #334155);
  border-radius: 14px; padding: 12px;
  transition: background-color .22s, border-color .22s;
}
.modal-product-img {
  width: 56px; height: 56px; border-radius: 10px; flex-shrink: 0;
  background: var(--c-surface-sunken, #0b1524); overflow: hidden;
  display: flex; align-items: center; justify-content: center;
}
.modal-product-img img { width: 100%; height: 100%; object-fit: contain; }
.modal-product-img-placeholder { color: var(--c-text-faint, #64748b); font-size: 20px; }
.modal-product-info { min-width: 0; }
.modal-product-name {
  font-size: 15px; font-weight: 700; color: var(--c-text-primary, #f1f5f9);
  margin: 0 0 3px; line-height: 1.3;
}
.modal-product-price { font-size: 13px; font-weight: 700; color: var(--c-accent, #f97316); margin: 0; }
.modal-product-price span { font-weight: 500; color: var(--c-text-faint, #64748b); margin-left: 4px; }

.modal-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
.modal-stats--2col { grid-template-columns: 1fr 1fr; }
.modal-stat {
  background-color: var(--c-surface-raised, #26364b) !important;
  opacity: 1 !important;
  border: 1px solid var(--c-border, #334155);
  border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px;
  transition: background-color .22s, border-color .22s;
}
.modal-stat--icon { display: flex; align-items: center; gap: 10px; }
.modal-stat-icon {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  background: var(--c-accent-soft, rgba(249,115,22,.15)); color: var(--c-accent, #f97316);
  display: flex; align-items: center; justify-content: center; font-size: 13px;
}
.modal-stat-icon--red { background: rgba(244,63,94,.14); color: var(--c-danger, #f43f5e); }
.modal-stat-label { font-size: 10.5px; font-weight: 600; color: var(--c-text-faint, #64748b); text-transform: uppercase; letter-spacing: .06em; }
.modal-stat-val   { font-size: 15px; font-weight: 700; color: var(--c-text-primary, #f1f5f9); }
.val--red { color: #f43f5e !important; }
.val--amber { color: #f59e0b !important; }

.modal-section { display: flex; flex-direction: column; gap: 10px; }
.modal-section-label { font-size: 13px; font-weight: 700; color: var(--c-text-secondary, #cbd5e1); transition: color .22s; }
.modal-section-hint  { font-weight: 400; color: var(--c-text-faint, #64748b); font-size: 11.5px; }
.modal-error { font-size: 12px; font-weight: 600; color: var(--c-danger, #f43f5e); margin: 0; display: flex; align-items: center; gap: 6px; }

.cut-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cut-field { display: flex; flex-direction: column; gap: 5px; }
.cut-field label { font-size: 11.5px; font-weight: 600; color: var(--c-text-muted, #94a3b8); }
.cut-field input {
  height: 38px; border: 1.5px solid var(--c-border-strong, #47566b); border-radius: 8px;
  padding: 0 12px; font-size: 13px; font-family: inherit;
  color: var(--c-text-primary, #f1f5f9); background: var(--c-surface-sunken, #0b1524);
  outline: none; transition: border-color .15s, background-color .22s, color .22s;
}
.cut-field input:focus { border-color: var(--c-accent, #f97316); background: var(--c-surface, #1e293b); }
.cut-field input::placeholder { color: var(--c-text-faint, #64748b); }

.cut-result { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cut-result-item {
  background-color: var(--c-surface-raised, #26364b) !important;
  opacity: 1 !important;
  border: 1px solid var(--c-border, #334155);
  border-radius: 8px; padding: 8px 12px; display: flex; flex-direction: column; gap: 3px;
  transition: background-color .22s, border-color .22s;
}
.cut-result-item span   { font-size: 10.5px; font-weight: 600; color: var(--c-text-faint, #64748b); text-transform: uppercase; letter-spacing: .06em; }
.cut-result-item strong { font-size: 13.5px; font-weight: 700; color: var(--c-text-primary, #f1f5f9); }
.cut-result-item--price strong { color: var(--c-accent, #f97316); }

.qty-row { display: flex; align-items: center; gap: 10px; }
.qty-btn {
  width: 38px; height: 38px;
  border: 1.5px solid var(--c-border-strong, #47566b); border-radius: 9px;
  background: var(--c-surface, #1e293b); color: var(--c-text-secondary, #cbd5e1);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  font-size: 12px; flex-shrink: 0;
  transition: border-color .15s, background-color .15s, color .15s;
}
.qty-btn:hover { border-color: var(--c-accent-border, rgba(249,115,22,.35)); color: var(--c-accent, #f97316); background: var(--c-accent-soft, rgba(249,115,22,.15)); }
.qty-input {
  width: 70px; height: 38px; border: 1.5px solid var(--c-border-strong, #47566b); border-radius: 9px;
  text-align: center; font-size: 16px; font-weight: 700; font-family: inherit;
  color: var(--c-text-primary, #f1f5f9); background: var(--c-surface-sunken, #0b1524);
  outline: none; transition: border-color .15s, background-color .22s, color .22s;
}
.qty-input:focus { border-color: var(--c-accent, #f97316); background: var(--c-surface, #1e293b); }

/* Sticky, unmissable line total between body and footer */
.modal-line-total-bar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 22px; margin: 0; flex-shrink: 0;
  background: var(--c-accent-soft, rgba(249,115,22,.15)); border-top: 1px solid var(--c-accent-border, rgba(249,115,22,.35));
  transition: background-color .22s, border-color .22s;
}
.modal-line-total-bar span   { font-size: 12.5px; font-weight: 700; color: var(--c-accent, #f97316); text-transform: uppercase; letter-spacing: .05em; }
.modal-line-total-bar strong { font-size: 19px; font-weight: 800; color: var(--c-accent, #f97316); letter-spacing: -.02em; }

.pay-summary {
  background-color: var(--c-surface-raised, #26364b) !important;
  opacity: 1 !important;
  border: 1px solid var(--c-border, #334155);
  border-radius: 12px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px;
  transition: background-color .22s, border-color .22s;
}
.pay-summary-row { display: flex; justify-content: space-between; font-size: 12.5px; color: var(--c-text-muted, #94a3b8); }
.pay-summary-row > span:last-child { font-weight: 600; color: var(--c-text-secondary, #cbd5e1); }
.pay-summary-row--disc > span:last-child { color: #10b981; }
.pay-summary-total {
  display: flex; justify-content: space-between;
  border-top: 1px solid var(--c-border-strong, #47566b); padding-top: 10px; margin-top: 4px;
  font-weight: 700; font-size: 14px; color: var(--c-text-primary, #f1f5f9);
  transition: border-color .22s;
}

.amount-input-wrap { position: relative; display: flex; align-items: center; }
.amount-prefix {
  position: absolute; left: 14px; font-size: 16px; font-weight: 700;
  color: var(--c-text-faint, #64748b); pointer-events: none;
}
.amount-input {
  width: 100%; height: 52px;
  border: 2px solid var(--c-border-strong, #47566b); border-radius: 12px;
  padding: 0 16px 0 34px;
  font-size: 22px; font-weight: 800; font-family: inherit;
  color: var(--c-text-primary, #f1f5f9); background: var(--c-surface-sunken, #0b1524);
  letter-spacing: -.02em; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.amount-input:focus { border-color: var(--c-accent, #f97316); box-shadow: 0 0 0 3px var(--c-accent-ring, rgba(249,115,22,.25)); background: var(--c-surface, #1e293b); }
.amount-input::placeholder { color: var(--c-text-faint, #64748b); }

.quick-amounts { display: flex; gap: 8px; flex-wrap: wrap; }
.quick-amt-btn {
  flex: 1; min-width: 70px; height: 34px;
  border: 1.5px solid var(--c-border-strong, #47566b); border-radius: 8px;
  background: var(--c-surface-raised, #26364b); font-size: 12.5px; font-weight: 600;
  color: var(--c-text-secondary, #cbd5e1); cursor: pointer; font-family: inherit;
  transition: border-color .15s, background-color .15s, color .15s;
}
.quick-amt-btn:hover { border-color: var(--c-accent-border, rgba(249,115,22,.35)); color: var(--c-accent, #f97316); background: var(--c-accent-soft, rgba(249,115,22,.15)); }

.change-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px; border-radius: 12px;
  background: rgba(244,63,94,.09); border: 1px solid rgba(244,63,94,.25);
  transition: background .2s, border-color .2s;
}
.change-row--ready { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.30); }
.change-label { font-size: 13px; font-weight: 700; color: var(--c-text-secondary, #cbd5e1); margin: 0 0 2px; }
.change-sub   { font-size: 11px; color: var(--c-text-muted, #94a3b8); margin: 0; }
.change-val   { font-size: 22px; font-weight: 800; letter-spacing: -.04em; color: var(--c-text-primary, #f1f5f9); }

.modal-footer {
  display: flex; gap: 10px;
  padding: 14px 22px 20px; border-top: 1px solid var(--c-border, #334155);
  transition: border-color .22s; flex-shrink: 0;
}
.modal-cancel {
  flex: 1; height: 42px; border: 1.5px solid var(--c-border-strong, #47566b); border-radius: 10px;
  background: none; color: var(--c-text-muted, #94a3b8); font-size: 13.5px; font-weight: 600;
  font-family: inherit; cursor: pointer;
  transition: background .15s, border-color .15s, color .15s;
}
.modal-cancel:hover { background: var(--c-surface-raised, #26364b); color: var(--c-text-primary, #f1f5f9); }
.modal-confirm {
  flex: 2; height: 42px; border: none; border-radius: 10px;
  background: linear-gradient(135deg, var(--c-accent, #f97316), var(--c-accent-deep, #c2410c));
  color: #fff; font-size: 13.5px; font-weight: 700; font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  box-shadow: var(--c-shadow-accent, 0 4px 14px rgba(249,115,22,.35));
  transition: opacity .15s, transform .15s, box-shadow .15s;
}
.modal-confirm:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); box-shadow: var(--c-shadow-accent-h, 0 6px 20px rgba(249,115,22,.45)); }
.modal-confirm:disabled { opacity: .45; cursor: not-allowed; transform: none; box-shadow: none; }

/* Transitions */
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .16s ease, transform .16s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.97) translateY(8px); }

.scanner-slide-enter-active { transition: opacity .2s ease, max-height .24s ease; max-height: 320px; overflow: hidden; }
.scanner-slide-leave-active { transition: opacity .16s ease, max-height .2s ease; overflow: hidden; }
.scanner-slide-enter-from, .scanner-slide-leave-to { opacity: 0; max-height: 0; }

.fade-enter-active, .fade-leave-active { transition: opacity .22s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* ═══════════════════════════════════════════════════════
   RESPONSIVE — TABLET (≤1024px)
   Matches Layout's page-content padding at this width (22px) so the
   negative margin that pulls .pos-root flush to the viewport edges
   stays exact, and trims the cart column so more of the grid stays
   visible before the layout stacks entirely at 768px.
════════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .pos-root { margin: -22px; }
  .pos-right { width: 300px; min-width: 300px; }
  .product-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
}

/* ═══════════════════════════════════════════════════════
   RESPONSIVE — MOBILE (≤768px)
   Product grid and cart stop sharing the screen side-by-side. The grid
   takes the full width and scrolls with the page; the cart becomes a
   bottom-sheet drawer (closed by default, full-width, slides up from
   under the mobile-cart-bar) instead of a fixed side column.
════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
  .pos-root {
    flex-direction: column;
    height: auto;
    min-height: 0;
    overflow: visible;
    margin: -16px; /* matches Layout's mobile page-content padding */
  }

  .pos-left {
    overflow: visible;
    padding: 14px 14px 92px; /* bottom room so the mobile-cart-bar never covers the last row */
  }

  .product-grid {
    overflow-y: visible;
    grid-template-columns: repeat(auto-fill, minmax(112px, 1fr));
  }

  .search-bar { flex-wrap: wrap; row-gap: 8px; }
  .search-field { flex: 1 1 100%; }
  .scan-btn { flex: 1; justify-content: center; }
  .filter-select-wrap { flex: 1; }
  .filter-select { width: 100%; }

  .scanner-banner-inner { flex-direction: column; align-items: flex-start; gap: 12px; }

  .pos-right {
    position: fixed;
    left: 0; right: 0; bottom: 0;
    width: 100%; min-width: 0; max-width: 100%;
    max-height: 85vh;
    border-left: none;
    border-top: 1px solid var(--p-border);
    border-radius: 20px 20px 0 0;
    transform: translateY(100%);
    transition: transform .3s cubic-bezier(.4,0,.2,1);
    z-index: 400;
    box-shadow: 0 -10px 34px rgba(0,0,0,.4);
    padding-bottom: env(safe-area-inset-bottom, 0px);
  }
  .pos-right.cart-drawer--open { transform: translateY(0); }
}

@media (max-width: 420px) {
  .modal-header { padding: 16px 18px; }
  .modal-body   { padding: 16px 18px; }
  .modal-footer { padding: 12px 18px 18px; }
  .modal-stats, .cut-grid { grid-template-columns: 1fr; }
}
</style>

<style>
#receipt { display: none; }

@media print {
  @page { margin: 6mm; }

  body * {
    visibility: hidden !important;
  }

  .pos-root {
    display: none !important;
  }

  #receipt, #receipt * {
    visibility: visible !important;
  }

  #receipt {
    display: block !important;
    position: absolute;
    left: 0; top: 0;
    width: 302px; /* ~80mm thermal roll */
    padding: 14px 16px 20px;
    background: #fff; color: #000;
    font-family: 'Courier New', 'Consolas', monospace;
    font-size: 11.5px; line-height: 1.5;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }

  .r-store {
    text-align: center; font-size: 16px; font-weight: 700;
    letter-spacing: .04em; text-transform: uppercase;
  }
  .r-tagline {
    text-align: center; font-size: 10.5px; margin-top: 2px;
  }

  .r-divider {
    border-top: 1px dashed #000; margin: 9px 0;
  }
  .r-divider--solid { border-top: 1px solid #000; }

  .r-meta-row, .r-row {
    display: flex; justify-content: space-between; gap: 8px;
    font-size: 11px; margin: 2px 0;
  }
  .r-meta-row > span:first-child { color: #333; }
  .r-meta-row > span:last-child { font-weight: 600; text-align: right; }

  .r-items { display: flex; flex-direction: column; gap: 7px; }
  .r-item-name {
    font-weight: 700; font-size: 11.5px; text-transform: uppercase;
  }
  .r-item-line {
    display: flex; justify-content: space-between; font-size: 11px; color: #333;
  }

  .r-totals { display: flex; flex-direction: column; }
  .r-row > span:last-child { font-weight: 600; }

  .r-grand-total {
    display: flex; justify-content: space-between;
    font-size: 15px; font-weight: 800; letter-spacing: .02em;
    margin: 4px 0;
  }

  .r-footer {
    text-align: center; margin-top: 4px;
  }
  .r-footer p { margin: 2px 0; font-size: 11px; }
}
</style>