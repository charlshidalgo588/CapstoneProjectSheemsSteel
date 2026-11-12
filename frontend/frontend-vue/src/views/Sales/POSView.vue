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
              placeholder="Search products, SKU, size, or barcode…"
              class="search-input"
              aria-label="Search products"
            />
            <button v-if="search" @click="search = ''" class="search-clear" aria-label="Clear search">
              <i class="fa-solid fa-xmark"></i>
            </button>
            <kbd v-else class="search-kbd" aria-hidden="true">/</kbd>
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
            <select v-model="selectedCategory" class="filter-select" aria-label="Category">
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
                  Auto-add single-size products on scan
                </label>
              </div>
            </div>
          </div>
        </transition>

        <!-- Product Meta -->
        <div class="product-meta">
          <!-- Switch between whole products and leftover (cut) pieces -->
          <div class="view-tabs" role="tablist">
            <button type="button" role="tab" :aria-selected="view === 'products'" :class="{ 'is-on': view === 'products' }" @click="view = 'products'">Products</button>
            <button type="button" role="tab" :aria-selected="view === 'leftovers'" :class="{ 'is-on': view === 'leftovers' }" @click="view = 'leftovers'">
              <i class="fa-solid fa-ruler-horizontal"></i> Leftovers
              <span class="view-tabs-count">{{ remnants.length }}</span>
            </button>
          </div>

          <span v-if="view === 'products'" class="product-count">{{ filteredProducts.length }} product{{ filteredProducts.length === 1 ? '' : 's' }}</span>
          <span v-else class="product-count">{{ filteredRemnants.length }} leftover{{ filteredRemnants.length === 1 ? '' : 's' }}</span>
          <span v-if="search" class="product-filter-tag">
            "{{ search }}"
            <button @click="search = ''" aria-label="Clear search"><i class="fa-solid fa-xmark"></i></button>
          </span>
          <div class="meta-actions">
            <!-- Extra service (labour): visible on both tabs, with a count when services are in the sale -->
            <button
              type="button"
              class="meta-chip service-chip"
              @click="openServiceModal()"
              title="Add an extra service fee (installation, cutting, delivery…)"
            >
              <i class="fa-solid fa-screwdriver-wrench"></i>
              Add service
              <span v-if="services.length" class="service-chip-count">{{ services.length }}</span>
            </button>
            <button
              v-if="view === 'products'"
              type="button"
              class="meta-chip"
              :class="{ 'meta-chip--active': availableOnly }"
              :aria-pressed="availableOnly"
              @click="availableOnly = !availableOnly"
            >
              <i class="fa-solid" :class="availableOnly ? 'fa-circle-check' : 'fa-circle'"></i>
              Available only
            </button>
          </div>
        </div>

        <!-- Load error -->
        <div v-if="loadError" class="load-state load-state--error">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <p>{{ loadError }}</p>
          <button @click="loadCatalog" class="load-retry-btn">Retry</button>
        </div>

        <!-- Loading skeleton -->
        <div v-else-if="loading" class="product-grid" aria-busy="true">
          <div v-for="n in 12" :key="'sk' + n" class="product-card product-card--skeleton">
            <div class="sk sk--img"></div>
            <div class="sk sk--line"></div>
            <div class="sk sk--line sk--short"></div>
          </div>
        </div>

        <!-- Product Grid (scrollable — every card keeps its full details) -->
        <div v-else-if="view === 'products'" class="product-grid">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            class="product-card"
            :class="{ 'product-card--oos': !canAddProduct(product), 'product-card--flash': flashProductId === product.id }"
            :tabindex="canAddProduct(product) ? 0 : -1"
            role="button"
            :aria-disabled="!canAddProduct(product)"
            @click="canAddProduct(product) && openQuantityModal(product)"
            @keydown.enter.self="canAddProduct(product) && openQuantityModal(product)"
          >
            <div class="product-stock-badge" :class="stockBadgeClass(product)">
              {{ stockBadgeLabel(product) }}
            </div>
            <div class="product-img-wrap">
              <img v-if="product.image" :src="product.image" class="product-img" :alt="product.name" @error="onImgError(product)" />
              <div v-else class="product-img-placeholder"><i class="fa-solid fa-cube"></i></div>
            </div>
            <div class="pcard-info">
              <p class="pcard-name" :title="product.name || 'Unnamed product'">{{ product.name || 'Unnamed product' }}</p>
              <p class="pcard-sku">SKU: {{ product.sku || '—' }}</p>
              <p v-if="product.pricingType === 'sqm'" class="pcard-tag">Cut-to-size (sqm)</p>
              <p v-else-if="product.variants.length > 1" class="pcard-tag">
                {{ product.variants.length }} sizes · {{ sellableVariants(product).length }} available
              </p>
              <div class="pcard-footer">
                <span class="pcard-price">{{ cardPrice(product) }}</span>
                <span class="pcard-stock-count">{{ availableFor(product) }} left</span>
              </div>
            </div>
            <div class="product-add-overlay"><i class="fa-solid fa-plus"></i></div>
          </div>

          <div v-if="filteredProducts.length === 0" class="product-empty">
            <i class="fa-solid fa-box-open"></i>
            <p>No products found</p>
            <button v-if="search || selectedCategory || availableOnly" class="load-retry-btn" @click="resetFilters">Clear filters</button>
          </div>
        </div>

        <!-- Leftover pieces (what is left after a cut) — still sellable -->
        <div v-else class="product-grid">
          <div
            v-for="r in filteredRemnants"
            :key="r.id"
            class="product-card"
            :class="{ 'product-card--oos': remnantAvailable(r) <= 0 }"
            role="button"
            :tabindex="remnantAvailable(r) > 0 ? 0 : -1"
            :aria-disabled="remnantAvailable(r) <= 0"
            @click="addRemnantToCart(r)"
            @keydown.enter.self="addRemnantToCart(r)"
          >
            <div class="product-stock-badge badge--low">Leftover</div>
            <div class="product-img-wrap">
              <img v-if="remnantImage(r)" :src="remnantImage(r)" class="product-img" :alt="r.productName" />
              <div v-else class="product-img-placeholder"><i class="fa-solid fa-cube"></i></div>
            </div>
            <div class="pcard-info">
              <p class="pcard-name" :title="r.productName">{{ r.productName }}</p>
              <p class="pcard-sku">SKU: {{ remnantSku(r) || '—' }}</p>
              <p class="pcard-tag">{{ r.label ? r.label + ' · ' : '' }}{{ fmtLeft(r) }} piece</p>
              <div class="pcard-footer">
                <span class="pcard-price">{{ r.price != null ? '₱' + r.price.toFixed(2) : 'Set price' }}</span>
                <span class="pcard-stock-count">{{ remnantAvailable(r) }} left</span>
              </div>
            </div>
            <div class="product-add-overlay"><i class="fa-solid fa-plus"></i></div>
          </div>

          <!-- Empty state: shows a real error + Retry when the leftover request failed -->
          <div v-if="filteredRemnants.length === 0" class="product-empty">
            <template v-if="remnantError">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <p>{{ remnantError }}</p>
              <button class="load-retry-btn" @click="loadRemnants">Retry</button>
            </template>
            <template v-else>
              <i class="fa-solid fa-ruler-horizontal"></i>
              <p>No leftover pieces{{ search ? ' match your search' : ' on hand' }}</p>
            </template>
          </div>
        </div>
      </div>

      <!-- ══ RIGHT — CART ══
           On desktop/tablet (>768px) this sits beside the product grid.
           Below 768px it becomes a bottom-sheet drawer: closed by default,
           opened via the floating mobile-cart-bar, closed via the handle,
           the close button or the backdrop. -->
      <div class="pos-right" :class="{ 'cart-drawer--open': isMobile && cartOpen }">
        <div v-if="isMobile" class="cart-drawer-handle" aria-hidden="true"></div>

        <div class="cart-header">
          <div class="cart-header-top">
            <div class="cart-title-wrap">
              <h2 class="cart-title">Current Sale</h2>
              <span class="cart-badge">{{ cart.length }} item{{ cart.length !== 1 ? 's' : '' }}</span>
            </div>
            <div class="cart-header-top-right">
              <button v-if="isMobile" type="button" class="cart-svc-btn" @click="openServiceModal()" title="Add an extra service fee (labour)">
                <i class="fa-solid fa-screwdriver-wrench"></i> Service
              </button>
              <button v-if="cart.length > 0 || services.length > 0" type="button" class="cart-clear-link" @click="clearCart">
                <i class="fa-solid fa-trash-can"></i> Clear
              </button>
              <button v-if="isMobile" class="cart-drawer-close" @click="cartOpen = false" aria-label="Close cart">
                <i class="fa-solid fa-chevron-down"></i>
              </button>
            </div>
          </div>

          <!-- Customer name (required) with suggestions from saved customers -->
          <div class="customer-row">
            <div class="customer-field" ref="customerFieldRef" @focusout="onCustomerFocusOut">
              <i class="fa-regular fa-user customer-icon"></i>
              <input
                v-model="customerName"
                type="text"
                placeholder="Customer name (required)"
                class="customer-input"
                :class="{ 'customer-input--error': customerMissing }"
                autocomplete="off"
                @input="onCustomerInput"
                @focus="onCustomerFocus"
                @keydown.esc="showSuggestions = false"
              />

              <ul
                v-if="showSuggestions && customerSuggestions.length"
                class="customer-suggest"
                @mousedown="onSuggestMouseDown"
              >
                <li v-if="suggestionsTitle" class="customer-suggest-head">{{ suggestionsTitle }}</li>

                <!-- Search box: only shown in "Repeat buyers" mode -->
                <li v-if="suggestionsTitle" class="customer-suggest-search">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input
                    ref="repeatSearchInput"
                    v-model="repeatSearch"
                    type="text"
                    placeholder="Search by name or phone…"
                    autocomplete="off"
                    @keydown.esc.prevent="showSuggestions = false"
                    @keydown.enter.prevent="pickFirstVisible"
                  />
                  <button
                    v-if="repeatSearch"
                    type="button"
                    class="customer-suggest-search-clear"
                    aria-label="Clear search"
                    @click="repeatSearch = ''; repeatSearchInput?.focus()"
                  >
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </li>

                <li
                  v-for="c in visibleSuggestions"
                  :key="c.CustomerID"
                  @mousedown.prevent="pickCustomer(c)"
                >
                  <span class="customer-suggest-name">
                    {{ c.CustomerName }}
                    <em v-if="c.is_repeat" class="customer-suggest-tag">Repeat</em>
                    <em v-else class="customer-suggest-tag customer-suggest-tag--plain">{{ c.visits }} of {{ c.repeat_min }}</em>
                  </span>
                  <small>{{ c.ContactNumber || 'no phone' }} · {{ c.visits }} visit{{ c.visits === 1 ? '' : 's' }}</small>
                </li>

                <li v-if="!visibleSuggestions.length" class="customer-suggest-empty">
                  No customers match "{{ repeatSearch }}"
                </li>
              </ul>
            </div>
            <button
              @mousedown.prevent
              @click="toggleCustomerList"
              class="customer-gen-btn"
              title="Show repeat buyers"
              aria-label="Show repeat buyers"
            >
              <i class="fa-solid fa-users"></i>
            </button>
            <button @click="generateCustomerName" class="customer-gen-btn" title="Walk-in name (not tracked)" aria-label="Generate walk-in name">
              <i class="fa-solid fa-wand-magic-sparkles"></i>
            </button>
          </div>

          <!-- Shows which registered customer is picked, and how close they are to "repeat buyer" -->
          <div v-if="selectedCustomer" class="customer-row customer-row--status">
            <span class="customer-repeat-badge" :class="{ 'customer-repeat-badge--plain': !selectedCustomer.is_repeat }">
              {{ customerStatusLabel }}
            </span>
          </div>
          <div v-else-if="trackedNewName" class="customer-row customer-row--status">
            <span class="customer-repeat-badge customer-repeat-badge--plain">
              New customer · tracked by name
            </span>
          </div>
        </div>

        <!-- Cart lines — the ONLY part of the panel that scrolls -->
        <div class="cart-items">
          <div v-if="cart.length === 0 && services.length === 0" class="cart-empty">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>Cart is empty</p>
            <span>Tap a product to add it</span>
          </div>

          <div v-for="item in cart" :key="item.key" class="cart-item">
            <div class="cart-item-body">
              <p class="cart-item-name" :title="lineName(item)">{{ lineName(item) }}</p>
              <div class="cart-item-sub">
                <span class="cart-item-sub-price">
                  ₱{{ item.price.toFixed(2) }} ea
                  <s v-if="item.origPrice != null" class="cart-item-was">₱{{ item.origPrice.toFixed(2) }}</s>
                </span>
                <button
                  v-if="canAdjustLine(item)"
                  type="button"
                  class="cart-item-adjust"
                  :class="{ 'cart-item-adjust--on': item.origPrice != null }"
                  @click="openAdjustModal(item)"
                  :title="item.remnantId ? 'Change the price of this leftover' : 'Sell a cut piece or change the price'"
                  :aria-label="`Adjust line: ${item.name}`"
                >
                  <i class="fa-solid" :class="item.origPrice != null ? 'fa-circle-check' : 'fa-sliders'"></i>
                  {{ item.origPrice != null ? (item.cut ? 'Cut piece' : 'Custom price') : 'Adjust line' }}
                </button>
                <!-- Leftover lines have no sizes to re-pick, so no edit pencil -->
                <button v-if="item.origPrice == null && !item.remnantId" @click="editCartItem(item)" class="cart-item-edit" title="Edit sizes & quantity" aria-label="Edit line">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <button @click="removeFromCart(item.key)" class="cart-item-del" title="Remove line" aria-label="Remove line">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>
            </div>

            <div class="cart-item-right">
              <span class="cart-item-total">₱{{ (item.qty * item.price).toFixed(2) }}</span>
              <div class="cart-item-qty-mini">
                <button class="cart-item-qty-btn" @click="decrementLine(item)" title="Decrease" aria-label="Decrease quantity"><i class="fa-solid fa-minus"></i></button>
                <span class="cart-item-qty-val">{{ item.qty }}</span>
                <button class="cart-item-qty-btn" @click="incrementLine(item)" title="Increase" aria-label="Increase quantity"><i class="fa-solid fa-plus"></i></button>
              </div>
            </div>
          </div>

          <!-- Service fees (labour) — shown at the bottom of the scrolling list -->
          <div v-for="svc in services" :key="svc.id" class="cart-item cart-item--service">
            <div class="cart-item-body">
              <p class="cart-item-name" :title="svc.name"><i class="fa-solid fa-screwdriver-wrench svc-line-icon"></i>{{ svc.name }}</p>
              <div class="cart-item-sub">
                <span class="cart-item-sub-price">{{ svc.workerName ? `${svc.workerName} · ₱${svc.workerAmount.toFixed(2)}` : 'Service fee' }}</span>
                <button @click="openServiceModal(svc)" class="cart-item-edit" title="Edit service" aria-label="Edit service"><i class="fa-solid fa-pen"></i></button>
                <button @click="removeService(svc.id)" class="cart-item-del" title="Remove service" aria-label="Remove service"><i class="fa-solid fa-trash-can"></i></button>
              </div>
            </div>
            <div class="cart-item-right">
              <span class="cart-item-total">₱{{ svc.fee.toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <!-- Compact summary — Total and the pay button stay prominent -->
        <div class="cart-summary">
          <div class="summary-line">
            <span>Subtotal</span>
            <span>₱{{ subtotal.toFixed(2) }}</span>
          </div>

          <div class="summary-line">
            <span>
              Discount
              <em v-if="discountAmount > 0" class="summary-saving">− ₱{{ discountAmount.toFixed(2) }}</em>
            </span>
            <div class="discount-controls">
              <input v-model.number="discountValue" type="number" min="0" class="discount-input" aria-label="Discount value" />
              <div class="discount-type">
                <button class="dtype-btn" :class="{ 'dtype-btn--active': discountType === '%' }" @click="discountType = '%'">%</button>
                <button class="dtype-btn" :class="{ 'dtype-btn--active': discountType === 'PHP' }" @click="discountType = 'PHP'">₱</button>
              </div>
            </div>
          </div>

          <button
            type="button"
            class="summary-toggle"
            :class="{ 'summary-toggle--open': showTaxDetails }"
            :aria-expanded="showTaxDetails"
            @click="showTaxDetails = !showTaxDetails"
          >
            <span>VAT details</span>
            <i class="fa-solid fa-chevron-down"></i>
          </button>
          <div v-if="showTaxDetails" class="summary-tax">
            <div class="summary-line"><span>VATable Sales</span><span>₱{{ vatableSales.toFixed(2) }}</span></div>
            <div class="summary-line"><span>VAT (12% included)</span><span>₱{{ vat.toFixed(2) }}</span></div>
          </div>

          <div v-if="serviceTotal > 0" class="summary-line">
            <span>Service fee</span>
            <span>₱{{ serviceTotal.toFixed(2) }}</span>
          </div>

          <div class="summary-total">
            <span>Total</span>
            <span class="summary-total-val">₱{{ total.toFixed(2) }}</span>
          </div>

          <p v-if="checkoutError" class="checkout-error" role="alert">{{ checkoutError }}</p>

          <button @click="openCashModal" class="pay-btn" :disabled="isProcessing || (cart.length === 0 && services.length === 0) || total <= 0">
            <i v-if="!isProcessing" class="fa-solid fa-cash-register"></i>
            <i v-else class="fa-solid fa-spinner fa-spin"></i>
            {{ isProcessing ? 'Processing…' : 'Process Payment' }}
          </button>
        </div>
      </div>

      <!-- MOBILE CART DRAWER BACKDROP -->
      <transition name="fade">
        <div v-if="isMobile && cartOpen" class="cart-backdrop" @click="cartOpen = false" aria-hidden="true" />
      </transition>

      <!-- MOBILE CART BAR — persistent summary + drawer toggle -->
      <button v-if="isMobile && !cartOpen" class="mobile-cart-bar" @click="cartOpen = true">
        <span class="mobile-cart-bar-icon">
          <i class="fa-solid fa-cart-shopping"></i>
          <span v-if="cartCount" class="mobile-cart-bar-badge">{{ cartCount }}</span>
        </span>
        <span class="mobile-cart-bar-total">₱{{ total.toFixed(2) }}</span>
        <span class="mobile-cart-bar-cta">View Cart <i class="fa-solid fa-chevron-up"></i></span>
      </button>
    </div>

    <!-- ══ QUANTITY MODAL ══
         Teleported to <body>. Every rule inside this modal (and the cash
         modal below) references --c-* tokens directly with an inline
         fallback, since the --p-* bridge tokens only exist on .pos-root.

         One table: every size is a row with its own quantity stepper.
         Any row with a quantity above 0 is added to the cart together. -->
    <Teleport to="body">
    <transition name="modal-fade">
      <div v-if="showQtyModal" class="modal-backdrop" @click.self="closeQtyModal" @keydown.esc="closeQtyModal">
        <div class="modal modal--wide" role="dialog" aria-modal="true">

          <div class="modal-header">
            <div class="modal-head-left">
              <div class="modal-thumb">
                <img v-if="selectedProduct?.image" :src="selectedProduct.image" alt="" />
                <i v-else class="fa-solid fa-cube"></i>
              </div>
              <div>
                <p class="modal-eyebrow">{{ editingKey ? 'Edit Cart Line' : 'Add to Cart' }}</p>
                <h3 class="modal-title">{{ selectedProduct?.name }}</h3>
              </div>
            </div>
            <button class="modal-close" @click="closeQtyModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
          </div>

          <div class="modal-body">

            <!-- CUT DIMENSIONS (sqm products only) -->
            <div v-if="isSqm" class="modal-section">
              <p class="modal-section-label">Cut Dimensions <span class="modal-section-hint">({{ selectedProduct.minDimension }}m – {{ selectedProduct.maxDimension }}m, applies to all selected sizes)</span></p>
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
                <div class="cut-result-item"><span>Area</span><strong>{{ cutArea.toFixed(2) }} sqm</strong></div>
                <div class="cut-result-item cut-result-item--price"><span>Cut piece = price × area</span><strong>per size below</strong></div>
              </div>
            </div>

            <!-- SIZES + QUANTITIES -->
            <div class="modal-section">
              <div class="size-toolbar">
                <p class="modal-section-label">
                  {{ modalHasSizes ? 'Select sizes & quantities' : 'Quantity' }}
                  <span v-if="modalHasSizes" class="modal-section-hint">
                    {{ selectedProduct.variants.length }} sizes · {{ sellableVariantCount }} available
                  </span>
                </p>
                <button v-if="selectedRows.length" type="button" class="link-btn" @click="clearDraft">Clear all</button>
              </div>

              <div v-if="modalHasSizes && selectedProduct.variants.length > 6" class="variant-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input v-model="variantSearch" type="text" placeholder="Find a size…" aria-label="Find a size" />
                <button v-if="variantSearch" type="button" @click="variantSearch = ''" aria-label="Clear"><i class="fa-solid fa-xmark"></i></button>
              </div>

              <div class="vtable">
                <div class="vhead">
                  <span>{{ modalHasSizes ? 'Size' : 'Item' }}</span>
                  <span>Availability</span>
                  <span>Quantity</span>
                  <span>Amount</span>
                </div>

                <div
                  v-for="v in modalVariants" :key="rowKey(v)"
                  class="vrow"
                  :class="{
                    'vrow--active': rowQty(v) > 0,
                    'vrow--low': rowStatus(v) === 'low',
                    'vrow--oos': rowStatus(v) === 'oos',
                    'vrow--off': maxQtyFor(v) <= 0
                  }"
                >
                  <div class="vrow-main">
                    <span class="vrow-label">{{ v.label }}</span>
                    <span class="vrow-price">₱{{ unitPriceFor(v).toFixed(2) }}</span>
                  </div>

                  <span class="vrow-stock">{{ variantStockText(selectedProduct, v, editingKey) }}</span>

                  <div class="vstep">
                    <button type="button" class="vstep-btn" :disabled="rowQty(v) <= 0" @click="stepRow(v, -1)" aria-label="Decrease">
                      <i class="fa-solid fa-minus"></i>
                    </button>
                    <input
                      type="number" inputmode="numeric" min="0" :max="maxQtyFor(v)"
                      class="vstep-input" placeholder="0"
                      :value="rowQty(v) || ''"
                      :disabled="maxQtyFor(v) <= 0"
                      :aria-label="`Quantity for ${v.label}`"
                      @focus="$event.target.select()"
                      @input="onQtyInput(v, $event)"
                    />
                    <button type="button" class="vstep-btn" :disabled="rowQty(v) >= maxQtyFor(v)" @click="stepRow(v, 1)" aria-label="Increase">
                      <i class="fa-solid fa-plus"></i>
                    </button>
                  </div>

                  <span class="vrow-amt">{{ rowQty(v) > 0 ? '₱' + (rowQty(v) * unitPriceFor(v)).toFixed(2) : '—' }}</span>
                </div>

                <p v-if="!modalVariants.length" class="variant-none">No size matches "{{ variantSearch }}"</p>
              </div>

              <p v-if="modalError" class="modal-error"><i class="fa-solid fa-circle-exclamation"></i> {{ modalError }}</p>
            </div>
          </div>

          <div class="modal-footer modal-footer--summary">
            <div class="modal-summary">
              <span class="modal-summary-meta">
                {{ selectedRows.length }} size{{ selectedRows.length === 1 ? '' : 's' }} · {{ totalUnits }} unit{{ totalUnits === 1 ? '' : 's' }}
              </span>
              <strong class="modal-summary-total">₱{{ modalTotal.toFixed(2) }}</strong>
            </div>
            <button class="modal-cancel modal-cancel--fit" @click="closeQtyModal">Cancel</button>
            <button class="modal-confirm modal-confirm--fit" @click="addToCart" :disabled="!canSubmitModal">
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
        <div class="modal modal--narrow" role="dialog" aria-modal="true">
          <div class="modal-header">
            <div>
              <p class="modal-eyebrow">Checkout</p>
              <h3 class="modal-title">Cash Payment</h3>
            </div>
            <button class="modal-close" @click="closeCashModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <div class="modal-body">
            <div class="pay-summary">
              <div class="pay-summary-row"><span>Subtotal</span><span>₱{{ subtotal.toFixed(2) }}</span></div>
              <div v-if="discountAmount > 0" class="pay-summary-row pay-summary-row--disc">
                <span>Discount</span><span>- ₱{{ discountAmount.toFixed(2) }}</span>
              </div>
              <div class="pay-summary-row"><span>VATable Sales</span><span>₱{{ vatableSales.toFixed(2) }}</span></div>
              <div class="pay-summary-row"><span>VAT (12% Included)</span><span>₱{{ vat.toFixed(2) }}</span></div>
              <div v-if="serviceTotal > 0" class="pay-summary-row"><span>Service Fee</span><span>₱{{ serviceTotal.toFixed(2) }}</span></div>
              <div class="pay-summary-total"><span>Total Due</span><span>₱{{ total.toFixed(2) }}</span></div>
            </div>
            <div class="modal-section">
              <p class="modal-section-label">Amount Received</p>
              <div class="amount-input-wrap">
                <span class="amount-prefix">₱</span>
                <input
                  ref="amountInput"
                  v-model.number="amountReceived"
                  type="number" min="0"
                  class="amount-input"
                  placeholder="0.00"
                  @focus="$event.target.select()"
                />
              </div>
              <div class="quick-amounts">
                <button class="quick-amt-btn" @click="amountReceived = total">Exact</button>
                <button v-for="amt in quickAmounts" :key="amt" class="quick-amt-btn" @click="amountReceived = amt">
                  ₱{{ amt.toLocaleString() }}
                </button>
              </div>
            </div>
            <div class="change-row" :class="{ 'change-row--ready': amountReceivedNum >= total }">
              <div>
                <p class="change-label">Change</p>
                <p class="change-sub">
                  {{ amountReceivedNum >= total ? 'Ready to process' : `Short by ₱${(total - amountReceivedNum).toFixed(2)}` }}
                </p>
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

    <!-- ══ ADJUST LINE MODAL ══
         Wide two-column layout that fits without scrolling:
           left  = what is being cut / why the price changes
           right = price + summary
         Cut mode: the full piece length and its unit are read from the size the
         cashier already picked ("8 ft", "2.5 m", "1/2 in" …). The cashier only
         types the length being sold. If the size name has no readable length,
         the full length can still be typed once by hand. -->
    <Teleport to="body">
    <transition name="modal-fade">
      <div v-if="showAdjModal && adjItem" class="modal-backdrop" @click.self="closeAdjModal" @keydown.esc="closeAdjModal">
        <div class="modal modal--adjust" role="dialog" aria-modal="true" aria-labelledby="adj-title">

          <!-- Header: what is being adjusted -->
          <div class="adj-head">
            <div class="adj-head-icon"><i class="fa-solid fa-sliders"></i></div>
            <div class="adj-head-text">
              <p class="modal-eyebrow">Adjust line</p>
              <h3 id="adj-title" class="modal-title">{{ adjItem.name }}</h3>
            </div>
            <div class="adj-meta">
              <span v-if="adjSizeLabel" class="adj-chip"><i class="fa-solid fa-ruler"></i> {{ adjSizeLabel }}</span>
              <span class="adj-chip"><i class="fa-solid fa-layer-group"></i> Qty {{ adjItem.qty }}</span>
              <span class="adj-chip"><i class="fa-solid fa-tag"></i> {{ adjItem.remnantId ? 'Baseline' : 'List' }} ₱{{ adjListPrice.toFixed(2) }}</span>
            </div>
            <button class="modal-close" @click="closeAdjModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
          </div>

          <div class="modal-body adj-body">

            <!-- What do you want to do? (leftovers and sqm lines only get a custom price) -->
            <div v-if="!adjIsSqm && !adjItem.remnantId" class="adj-modes" role="tablist" aria-label="Adjustment type">
              <button type="button" role="tab" class="adj-mode" :class="{ 'is-active': adjMode === 'cut' }" :aria-selected="adjMode === 'cut'" @click="setAdjMode('cut')">
                <i class="fa-solid fa-scissors"></i>
                <span class="adj-mode-text"><strong>Sell a cut piece</strong><span>Charge by the length sold</span></span>
              </button>
              <button type="button" role="tab" class="adj-mode" :class="{ 'is-active': adjMode === 'price' }" :aria-selected="adjMode === 'price'" @click="setAdjMode('price')">
                <i class="fa-solid fa-tag"></i>
                <span class="adj-mode-text"><strong>Custom price</strong><span>Change the price per piece</span></span>
              </button>
            </div>

            <div class="adj-grid">

              <!-- LEFT COLUMN -->
              <div class="adj-col">

                <!-- CUT PIECE -->
                <template v-if="adjMode === 'cut'">
                  <section class="adj-card">
                    <header class="adj-card-head">
                      <h4 class="adj-card-title">Cut details</h4>
                    </header>
                    <div class="adj-card-body">
                      <div class="adj-split">

                        <!-- Full piece: automatic, fixed by the chosen size -->
                        <div class="adj-tile">
                          <div class="adj-tile-top">
                            <span class="adj-label">Full piece</span>
                            <span v-if="adjFullLocked" class="piece-badge" :title="`Read from size: ${adjSpec.source}`"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto</span>
                            <button v-else-if="adjSpec" type="button" class="link-btn" @click="relockFull">Use {{ fmtLen(adjSpec.length) }} {{ adjSpec.unit }}</button>
                          </div>

                          <template v-if="adjFullLocked">
                            <div class="adj-tile-value">{{ fmtLen(adjFull) }}<span>{{ adjUnit }}</span></div>
                            <p class="adj-tile-sub">
                              From size {{ adjSpec.source }}<template v-if="adjUnitPrice != null"> · ₱{{ adjUnitPrice.toFixed(2) }}/{{ adjUnit }}</template>
                              <button type="button" class="link-btn link-btn--inline" @click="unlockFull">Change</button>
                            </p>
                          </template>
                          <template v-else>
                            <div class="adj-input-wrap">
                              <input id="adj-full" ref="adjFullInput" v-model.number="adjFull" class="adj-input adj-input--sm" type="number" min="0" step="any" inputmode="decimal" placeholder="e.g. 8" aria-label="Full piece length" />
                              <span class="adj-suffix">{{ adjUnit }}</span>
                            </div>
                            <div class="svc-chips" role="group" aria-label="Length unit">
                              <button v-for="u in CUT_UNITS" :key="u" type="button" class="svc-chip" :class="{ 'svc-chip--on': adjUnit === u }" @click="adjUnit = u">{{ u }}</button>
                            </div>
                            <p class="adj-tile-sub">{{ adjSpec ? 'Entered by hand.' : "No length found in this size. Enter it once." }}</p>
                          </template>
                        </div>

                        <!-- Length sold: the only thing the cashier types -->
                        <div class="adj-tile adj-tile--input" :class="{ 'is-invalid': adjSoldError }">
                          <div class="adj-tile-top">
                            <label class="adj-label" for="adj-sold">Length to sell</label>
                          </div>
                          <div class="adj-input-wrap">
                            <input
                              id="adj-sold" ref="adjSoldInput" v-model.number="adjSold" class="adj-input"
                              type="number" min="0" step="any" inputmode="decimal"
                              :placeholder="adjFull > 0 ? `up to ${fmtLen(adjFull)}` : 'e.g. 3'"
                              @keydown.enter.prevent="applyAdjustment"
                            />
                            <span class="adj-suffix">{{ adjUnit }}</span>
                          </div>
                          <p class="adj-tile-sub">Type only what the customer needs.</p>
                        </div>
                      </div>

                      <div v-if="adjQuick.length" class="adj-quick">
                        <span class="adj-quick-label">Quick</span>
                        <div class="svc-chips" role="group" aria-label="Quick lengths">
                          <button v-for="q in adjQuick" :key="q.label" type="button" class="svc-chip" :class="{ 'svc-chip--on': Number(adjSold) === q.value }" @click="pickSold(q.value)">{{ q.label }}</button>
                        </div>
                      </div>

                      <p v-if="adjSoldError" class="modal-error"><i class="fa-solid fa-circle-exclamation"></i> {{ adjSoldError }}</p>

                      <!-- Visual split of the piece -->
                      <template v-if="adjBar">
                        <div class="piece-bar" role="img" :aria-label="`${fmtLen(adjSold)} ${adjUnit} sold, ${fmtLen(adjRemainder)} ${adjUnit} left over`">
                          <div class="piece-bar-sold" :style="{ width: adjBar.soldPct + '%' }">Sold {{ fmtLen(adjSold) }} {{ adjUnit }}</div>
                          <div class="piece-bar-left">Left {{ fmtLen(adjRemainder) }} {{ adjUnit }}</div>
                        </div>
                        <div class="adj-remainder">
                          <i class="fa-solid fa-circle-info"></i>
                          <span>
                            <strong>{{ fmtLen(adjRemainder) }} {{ adjUnit }}</strong> left over{{ adjItem.qty > 1 ? ' from each piece' : '' }}, saved under Leftovers for later sale.
                          </span>
                        </div>
                      </template>
                    </div>
                  </section>
                </template>

                <!-- CUSTOM PRICE REASON -->
                <section v-else class="adj-card">
                  <header class="adj-card-head">
                    <h4 class="adj-card-title">Reason{{ adjItem.remnantId ? ' (optional)' : '' }}</h4>
                  </header>
                  <div class="adj-card-body">
                    <div class="cut-field">
                      <label for="adj-reason" class="sr-only">Reason for the price change</label>
                      <input id="adj-reason" v-model="adjReason" type="text" maxlength="80" placeholder="Why is the price changing? e.g. Negotiated" autocomplete="off" />
                    </div>
                    <div class="svc-chips">
                      <button v-for="r in PRICE_REASONS" :key="r" type="button" class="svc-chip" :class="{ 'svc-chip--on': adjReason === r }" @click="adjReason = r">{{ r }}</button>
                    </div>
                  </div>
                </section>
              </div>

              <!-- RIGHT COLUMN -->
              <div class="adj-col">

                <!-- Pricing -->
                <section class="adj-card">
                  <header class="adj-card-head">
                    <h4 class="adj-card-title"><label for="adj-price">{{ adjMode === 'cut' ? 'Price for this cut' : 'New price' }} <span class="adj-card-unit">(₱ each)</span></label></h4>
                    <div class="adj-card-actions">
                      <button v-if="adjMode === 'cut' && adjSuggested != null && Number(adjPrice) !== adjSuggested" type="button" class="link-btn" @click="adjPrice = adjSuggested; adjTouched = false">
                        Use suggested ₱{{ adjSuggested.toFixed(2) }}
                      </button>
                      <button
                        v-if="adjItem.remnantId && adjItem.baseline != null && Number(adjPrice) !== adjItem.baseline"
                        type="button" class="link-btn"
                        @click="adjPrice = adjItem.baseline"
                      >
                        Use baseline ₱{{ adjItem.baseline.toFixed(2) }}
                      </button>
                    </div>
                  </header>
                  <div class="adj-card-body">
                    <div class="amount-input-wrap">
                      <span class="amount-prefix">₱</span>
                      <input id="adj-price" v-model.number="adjPrice" type="number" min="0" step="0.01" class="amount-input adj-price-input" placeholder="0.00" @input="adjTouched = true" @keydown.enter.prevent="applyAdjustment" />
                    </div>
                    <p v-if="adjMode === 'cut' && adjSuggested != null" class="adj-note">
                      Suggested = the same share of the list price ({{ Math.round((Number(adjSold) / Number(adjFull)) * 100) }}% of ₱{{ adjListPrice.toFixed(2) }}). Change it if you charge extra for cutting.
                    </p>
                    <p v-else-if="adjMode === 'cut'" class="adj-note">The price fills in automatically once you enter the length to sell.</p>
                    <p v-if="adjItem.remnantId && adjItem.baseline != null" class="adj-note">
                      Baseline = list price of the uncut piece minus what the cut was sold for. You can change it.
                    </p>
                  </div>
                </section>

                <!-- Summary -->
                <section class="adj-card">
                  <header class="adj-card-head">
                    <h4 class="adj-card-title">Summary</h4>
                    <div class="adj-card-actions">
                      <button v-if="adjItem.origPrice != null" type="button" class="link-btn" @click="resetAdjustment"><i class="fa-solid fa-rotate-left"></i> Reset to list price</button>
                    </div>
                  </header>
                  <div class="adj-card-body adj-rows">
                    <template v-if="adjMode === 'cut'">
                      <div class="adj-row"><span>Full piece list price</span><span>₱{{ adjListPrice.toFixed(2) }}</span></div>
                      <div class="adj-row"><span>Length sold</span><span>{{ adjSuggested != null ? `${fmtLen(adjSold)} of ${fmtLen(adjFull)} ${adjUnit}` : '—' }}</span></div>
                      <div class="adj-row"><span>Price for this cut</span><span>₱{{ adjPriceNum.toFixed(2) }}</span></div>
                    </template>
                    <template v-else>
                      <div class="adj-row"><span>{{ adjItem.remnantId ? 'Baseline price' : 'List price' }}</span><span>₱{{ adjListPrice.toFixed(2) }}</span></div>
                      <div class="adj-row"><span>New price</span><span>₱{{ adjPriceNum.toFixed(2) }}</span></div>
                      <div class="adj-row" :class="adjDiff < 0 ? 'is-down' : adjDiff > 0 ? 'is-up' : ''">
                        <span>Change</span>
                        <span>{{ adjDiff === 0 ? 'No change' : `${adjDiff > 0 ? '+' : '−'}₱${Math.abs(adjDiff).toFixed(2)} (${adjDiff > 0 ? '+' : '−'}${Math.abs(adjDiffPct)}%)` }}</span>
                      </div>
                    </template>
                    <div class="adj-row"><span>Quantity</span><span>{{ adjItem.qty }} {{ adjMode === 'cut' ? 'cut' : 'pc' }}{{ adjItem.qty === 1 ? '' : 's' }}</span></div>
                  </div>
                </section>
              </div>
            </div>

            <p v-if="adjError" class="modal-error"><i class="fa-solid fa-circle-exclamation"></i> {{ adjError }}</p>
          </div>

          <div class="modal-footer modal-footer--summary">
            <div class="modal-summary">
              <span class="modal-summary-meta">Line total</span>
              <strong class="modal-summary-total">₱{{ (adjPriceNum * adjItem.qty).toFixed(2) }}</strong>
            </div>
            <button class="modal-cancel modal-cancel--fit" @click="closeAdjModal">Cancel</button>
            <button class="modal-confirm modal-confirm--fit" @click="applyAdjustment" :disabled="adjMode === 'cut' && !adjCutReady"><i class="fa-solid fa-check"></i> Apply changes</button>
          </div>
        </div>
      </div>
    </transition>
    </Teleport>

    <!-- ══ EXTRA SERVICE MODAL ══
         Wide two-column layout that fits without scrolling:
           left  = the service and its fee
           right = the worker and their share
           bottom = who gets what -->
    <Teleport to="body">
    <transition name="modal-fade">
      <div v-if="showServiceModal" class="modal-backdrop" @click.self="closeServiceModal" @keydown.esc="closeServiceModal">
        <div class="modal modal--service" role="dialog" aria-modal="true">
          <div class="modal-header">
            <div>
              <p class="modal-eyebrow">{{ editingServiceId ? 'Edit Service' : 'Extra Service' }}</p>
              <h3 class="modal-title">Service fee</h3>
            </div>
            <button class="modal-close" @click="closeServiceModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
          </div>

          <div class="modal-body svc-body">
            <div class="svc-grid">

              <!-- LEFT: service + fee -->
              <div class="svc-col">
                <h4 class="svc-sec-title">Service</h4>

                <div class="cut-field" :class="{ 'has-error': svcFieldErr.name }">
                  <label for="svc-name">Service <span class="req">*</span></label>
                  <input id="svc-name" ref="svcNameInput" v-model="svcName" type="text" maxlength="80" placeholder="e.g. Roof installation" autocomplete="off" :aria-invalid="!!svcFieldErr.name" @keydown.enter.prevent="saveService" />
                  <p v-if="svcFieldErr.name" class="svc-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ svcFieldErr.name }}</p>
                </div>
                <div class="svc-chips">
                  <button v-for="p in SERVICE_PRESETS" :key="p" type="button" class="svc-chip" :class="{ 'svc-chip--on': svcName === p }" @click="svcName = p">{{ p }}</button>
                </div>

                <div class="cut-field" :class="{ 'has-error': svcFieldErr.fee }">
                  <label for="svc-fee">Service fee charged (₱) <span class="req">*</span></label>
                  <input id="svc-fee" v-model.number="svcFee" type="number" min="0" step="0.01" placeholder="0.00" :aria-invalid="!!svcFieldErr.fee" @keydown.enter.prevent="saveService" />
                  <p v-if="svcFieldErr.fee" class="svc-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ svcFieldErr.fee }}</p>
                </div>
              </div>

              <!-- RIGHT: worker + share -->
              <div class="svc-col">
                <h4 class="svc-sec-title">Worker</h4>

                <div class="cut-field" :class="{ 'has-error': svcFieldErr.worker }">
                  <label for="svc-worker">Worker</label>
                  <input id="svc-worker" v-model="svcWorker" type="text" maxlength="60" placeholder="Who did the work?" autocomplete="off" :aria-invalid="!!svcFieldErr.worker" @keydown.enter.prevent="saveService" />
                  <p v-if="svcFieldErr.worker" class="svc-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ svcFieldErr.worker }}</p>
                </div>

                <div class="svc-share">
                  <div class="size-toolbar">
                    <p class="modal-section-label">Worker's share</p>
                    <div class="svc-seg" role="group" aria-label="Share type">
                      <button type="button" :class="{ 'is-active': svcShareType === '%' }" @click="setShareType('%')">%</button>
                      <button type="button" :class="{ 'is-active': svcShareType === 'PHP' }" @click="setShareType('PHP')">₱</button>
                    </div>
                  </div>
                  <div class="svc-share-row">
                    <input id="svc-share" v-model.number="svcShareValue" type="number" min="0" :max="svcShareType === '%' ? 100 : undefined" class="svc-share-input" :class="{ 'has-error': svcFieldErr.share }" aria-label="Worker share" :aria-invalid="!!svcFieldErr.share" @keydown.enter.prevent="saveService" />
                    <div v-if="svcShareType === '%'" class="svc-chips">
                      <button v-for="n in [50, 60, 70, 80]" :key="n" type="button" class="svc-chip" :class="{ 'svc-chip--on': Number(svcShareValue) === n }" @click="svcShareValue = n">{{ n }}%</button>
                    </div>
                  </div>
                  <p v-if="svcFieldErr.share" class="svc-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ svcFieldErr.share }}</p>
                </div>
              </div>
            </div>

            <!-- Who gets what -->
            <div class="svc-stats">
              <div class="svc-stat">
                <span class="svc-stat-label">Customer pays</span>
                <strong class="svc-stat-value">₱{{ svcSplit.fee.toFixed(2) }}</strong>
              </div>
              <div class="svc-stat">
                <span class="svc-stat-label">Worker{{ svcWorker.trim() ? ` (${svcWorker.trim()})` : '' }}</span>
                <strong class="svc-stat-value">− ₱{{ svcSplit.worker.toFixed(2) }}</strong>
              </div>
              <div class="svc-stat svc-stat--shop">
                <span class="svc-stat-label">Shop keeps</span>
                <strong class="svc-stat-value">₱{{ svcSplit.owner.toFixed(2) }}</strong>
              </div>
            </div>
            <p class="svc-note">The customer's receipt shows the full fee. Only the shop's share counts toward sales and profit.</p>
          </div>

          <div v-if="svcError" class="svc-alert" role="alert">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>{{ svcError }}</span>
          </div>

          <div class="modal-footer">
            <button class="modal-cancel" @click="closeServiceModal">Cancel</button>
            <button class="modal-confirm" @click="saveService">
              <i class="fa-solid fa-check"></i> {{ editingServiceId ? 'Update service' : 'Add service' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
    </Teleport>

    <!-- ══ CLEAR CART CONFIRMATION ══ -->
    <Teleport to="body">
    <transition name="modal-fade">
      <div v-if="showClearModal" class="modal-backdrop" @click.self="closeClearModal" @keydown.esc="closeClearModal">
        <div class="modal modal--confirm" role="alertdialog" aria-modal="true" aria-labelledby="clear-title" aria-describedby="clear-desc">
          <div class="confirm-body">
            <div class="confirm-icon"><i class="fa-solid fa-trash-can"></i></div>
            <h3 id="clear-title" class="confirm-title">Clear this sale?</h3>
            <p id="clear-desc" class="confirm-text">
              {{ clearSummary }}
            </p>
          </div>
          <div class="modal-footer confirm-footer">
            <button ref="clearKeepBtn" type="button" class="modal-cancel" @click="closeClearModal">Keep items</button>
            <button type="button" class="modal-confirm modal-confirm--danger" @click="confirmClearCart">
              <i class="fa-solid fa-trash-can"></i> Clear cart
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

    <template v-if="receiptData.services?.length">
      <div class="r-divider"></div>
      <div class="r-items">
        <div v-for="(sv, i) in receiptData.services" :key="'sv' + i" class="r-item">
          <div class="r-item-name">{{ sv.name }}</div>
          <div class="r-item-line"><span>Service fee</span><span>₱{{ sv.fee.toFixed(2) }}</span></div>
        </div>
      </div>
    </template>

    <div class="r-divider"></div>

    <div class="r-totals">
      <div class="r-row"><span>Subtotal</span><span>₱{{ receiptData.subtotal.toFixed(2) }}</span></div>
      <div class="r-row"><span>Discount</span><span>- ₱{{ receiptData.discount.toFixed(2) }}</span></div>
      <div class="r-row"><span>VATable Sales</span><span>₱{{ receiptData.vatableSales.toFixed(2) }}</span></div>
      <div class="r-row"><span>VAT (12% Incl.)</span><span>₱{{ receiptData.vat.toFixed(2) }}</span></div>
      <div v-if="receiptData.serviceTotal > 0" class="r-row"><span>Service Fee</span><span>₱{{ receiptData.serviceTotal.toFixed(2) }}</span></div>
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
// Out of Stock last.
const STOCK_SORT_PRIORITY = { ok: 0, low: 1, oos: 2 }

// ── LOW-STOCK RULES (same thresholds as the server: StockService) ──
// A size is "low" at or below its own ReorderLevel, or at or below 5 when
// no reorder level was set. LOW is only a WARNING — it is NOT the same as
// out of stock.
const LOW_STOCK_DEFAULT = 5
// false = low-stock sizes show a warning badge but CAN STILL BE SOLD.
//         Only sizes that are completely out of stock (0 left) are blocked.
// true  = a LOW size cannot be selected or sold (must be restocked first).
const BLOCK_LOW_STOCK_SALES = false

// ── EXTRA SERVICES (labour) ──
// The customer pays the FULL service fee. The worker's share is a pass-through
// (paid out to the worker); only the shop's share is shop revenue/profit.
// Service fees are kept OUTSIDE the VAT computation of the products.
// Confirm the VAT treatment of labour with your accountant.
// ── CUT PIECES & CUSTOM PRICES ──
// A cut sells part of ONE whole piece. The whole piece leaves normal stock and the
// leftover length is recorded as a remnant by the server (not counted as a full piece).
// The units are what the cashier can pick by hand when a size name has no readable
// length. Detected units (ft / in / m / cm / mm) come from the size label itself.
// NOTE: if your server validates `cut.unit`, make sure it accepts every unit listed here.
const CUT_UNITS = ['ft', 'm', 'in', 'cm', 'mm']
const PRICE_REASONS = ['Negotiated', 'Bulk deal', 'Damaged', 'Remnant']
const REQUIRE_PRICE_REASON = true       // a custom price must say why (not required for leftover pieces)
const SERVICE_PRESETS = ['Installation', 'Cutting', 'Welding', 'Delivery']
const SERVICE_DEFAULT_WORKER_PCT = 70   // default worker share (%) — change to your usual split

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
 *  or the barcode-lookup endpoint into a single consistent shape.
 *
 *  SIZES, PRICES & STOCK: each product carries `variants` — the sizes
 *  entered in ProductCreate — each with its own selling price, its own
 *  stock and its own reorder level. The product's `stock` is the sum of
 *  its sizes (never the stale stored total). */
function normalizeProduct(p) {
  const pricingTypeRaw = (p.PricingType ?? p.pricing_type ?? 'piece').toString().toLowerCase()
  const variants = (p.variants ?? p.Variants ?? []).map(v => ({
    id: v.VariantID ?? v.id,
    label: v.Label ?? v.label ?? 'Standard',
    price: Number(v.SellingPrice ?? v.price ?? 0),
    stock: Number(v.Stock ?? v.stock ?? 0),
    reorderLevel: Number(v.ReorderLevel ?? v.reorderLevel ?? 0),
  }))
  return {
    id: p.ProductID ?? p.id,
    name: p.ProductName ?? p.name ?? '',
    sku: p.SKU ?? p.sku ?? '',
    barcode: p.Barcode ?? p.barcode ?? p.SKU ?? p.sku ?? '',
    variants,
    // Fallback price for legacy products that have no variants yet.
    price: Number(variants[0]?.price ?? p.SellingPrice ?? p.price ?? 0),
    stock: variants.length
      ? variants.reduce((s, v) => s + v.stock, 0)
      : Number(p.inventory?.QuantityOnHand ?? p.Stock ?? p.stock ?? 0),
    image: p.ImageURL ?? p.image ?? null,
    category: p.CategoryID ?? p.category ?? null,
    pricingType: pricingTypeRaw === 'sqm' ? 'sqm' : 'piece',
    minDimension: Number(p.MinDimension ?? MIN_DIM),
    maxDimension: Number(p.MaxDimension ?? MAX_DIM),
  }
}
/** Cart line key. Different sizes of the same product are separate lines. */
function buildKey(product, length, width, variantId = null) {
  const v = variantId != null ? `-v${variantId}` : ''
  if (product.pricingType === 'sqm') return `${product.id}${v}-${length}x${width}`
  return `${product.id}${v}-piece`
}
/** Price label for the product card: "From ₱X" when sizes differ in price. */
function cardPrice(p) {
  if (!p.variants?.length) return `₱${(p.price || 0).toFixed(2)}`
  const prices = p.variants.map(v => v.price)
  const min = Math.min(...prices)
  const max = Math.max(...prices)
  return min === max ? `₱${min.toFixed(2)}` : `From ₱${min.toFixed(2)}`
}

/* ── Size sorting (same rules as the inventory list) ──
   Reads the number out of labels ("8 ft", "10 ft", "1/2 in", "2 1/2 in")
   and knows named sizes, so "8" sorts before "10". */
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
  const ka = sizeKey(a.label), kb = sizeKey(b.label)
  if (ka !== kb) return ka < kb ? -1 : 1
  return String(a.label ?? '').localeCompare(String(b.label ?? ''), undefined, { numeric: true, sensitivity: 'base' })
}

/* ── Length + unit detection from a size label ──
   "8 ft" → 8 ft · "2.5m" → 2.5 m · "1/2 in" → 0.5 in · "2 1/2 in" → 2.5 in
   "10'" → 10 ft · "0.4mm x 8ft" → 8 ft (a bare thickness in mm is only used
   when nothing else is found) · "20 in × 50 mm" → 20 in.
   Returns { length, unit, text } or null when the label has no length. */
const UNIT_ALIASES = {
  ft: 'ft', feet: 'ft', foot: 'ft', "'": 'ft',
  in: 'in', inch: 'in', inches: 'in', '"': 'in',
  m: 'm', mtr: 'm', mtrs: 'm', meter: 'm', meters: 'm', metre: 'm', metres: 'm',
  cm: 'cm', mm: 'mm',
}
const LENGTH_PATTERN = /(\d+\s+\d+\s*\/\s*\d+|\d+\s*\/\s*\d+|\d+(?:[.,]\d+)?)\s*(feet|foot|ft|inches|inch|in|meters|meter|metres|metre|mtrs|mtr|cm|mm|m|'|")(?![a-z])/gi
function parseQuantity(s) {
  const t = String(s).trim().replace(',', '.')
  let m = t.match(/^(\d+)\s+(\d+)\s*\/\s*(\d+)$/)
  if (m) return Number(m[1]) + Number(m[2]) / Number(m[3])
  m = t.match(/^(\d+)\s*\/\s*(\d+)$/)
  if (m) return Number(m[1]) / Number(m[2])
  return Number(t)
}
function parseLengthSpec(text) {
  const re = new RegExp(LENGTH_PATTERN.source, 'gi')
  const src = String(text ?? '')
  const found = []
  let m
  while ((m = re.exec(src))) {
    const length = parseQuantity(m[1])
    const unit = UNIT_ALIASES[m[2].toLowerCase()]
    if (length > 0 && unit) found.push({ length: round2(length), unit, text: m[0].trim() })
  }
  if (!found.length) return null
  return found.find(f => f.unit !== 'mm') || found[0]
}

/* ────────────────────────────────────────────────────────────
   STATE
──────────────────────────────────────────────────────────── */
const products   = ref([])
const categories = ref([])
const loading    = ref(false)
const loadError  = ref('')

// Leftover (cut) pieces that can still be sold, and which tab is showing.
const remnants     = ref([])
const remnantError = ref('')       // set when /api/pos/remnants fails (so it isn't mistaken for "none on hand")
const view         = ref('products')   // 'products' | 'leftovers'

const search           = ref('')
const selectedCategory = ref('')
const availableOnly    = ref(false)   // hide products that cannot be sold right now

const cart         = ref([])          // unique-keyed, already-merged cart lines
const customerName = ref('')
const cashierName  = ref(localStorage.getItem('cashier_name') || 'Cashier')

// ── Customer tracking (optional): lets the system recognise repeat buyers ──
const selectedCustomer    = ref(null)   // existing customer picked from suggestions
const customerSuggestions = ref([])
const showSuggestions     = ref(false)
const suggestionsTitle    = ref('')     // "Repeat buyers" when the list is not a text search
let customerSearchSeq     = 0           // ignores out-of-date responses
let customerSearchTimer   = null
const repeatBuyersCache   = ref([])     // last repeat-buyer list, so the dropdown can open instantly
const repeatSearch        = ref('')     // search text inside the repeat-buyers dropdown
const repeatSearchInput   = ref(null)   // the search <input> inside the dropdown
const customerFieldRef    = ref(null)   // wrapper around name input + dropdown

const showQtyModal  = ref(false)
const showCashModal = ref(false)
const showClearModal = ref(false)
const clearKeepBtn  = ref(null)

// Extra services added to the sale
const services          = ref([])
const showServiceModal  = ref(false)
const editingServiceId  = ref(null)
const svcName           = ref('')
const svcFee            = ref(null)
const svcWorker         = ref('')
const svcShareType      = ref('%')    // '%' | 'PHP'
const svcShareValue     = ref(SERVICE_DEFAULT_WORKER_PCT)
const svcError          = ref('')
const svcFieldErr       = ref({})     // { name, fee, share, worker } — message per invalid field
const svcNameInput      = ref(null)

// Cut piece / custom price (edits one cart line)
const showAdjModal = ref(false)
const adjKey       = ref(null)
const adjMode      = ref('cut')        // 'cut' | 'price'
const adjFull      = ref(null)
const adjSold      = ref(null)
const adjUnit      = ref('ft')
const adjPrice     = ref(null)
const adjReason    = ref('')
const adjTouched   = ref(false)        // true once the cashier typed their own price
const adjError     = ref('')
const adjFullInput = ref(null)
const adjSoldInput = ref(null)
const adjSpec       = ref(null)        // { length, unit, source } read from the size label, or null
const adjFullLocked = ref(false)       // true = full length + unit come from the size (not typed)
const isProcessing  = ref(false)
const checkoutError = ref('')
const modalError    = ref('')

const selectedProduct   = ref(null)
const variantSearch     = ref('')     // "Find a size…" box (long size lists)
const draftQty          = ref({})     // { [sizeKey]: quantity } — one entry per size being added
const cutLength   = ref(1)
const cutWidth    = ref(1)
const editingKey  = ref(null)

const discountType  = ref('%')
const discountValue = ref(0)
const amountReceived = ref(0)
const amountInput    = ref(null)

const showTaxDetails = ref(false)     // "VAT details" fold in the cart summary

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

// A single checkout attempt shares one idempotency key across retries.
const checkoutIdempotencyKey = ref('')

const receiptData = ref(null)

const barcodeInput = ref(null)
const searchInput  = ref(null)

let barcodeTimer   = null
let flashTimer     = null
let lastScanCode = ''
let lastScanAt   = 0
let keyTimestamps = []

/* ────────────────────────────────────────────────────────────
   RESPONSIVE — MOBILE CART DRAWER
──────────────────────────────────────────────────────────── */
const POS_MOBILE_BREAKPOINT = 768
const isMobile = ref(window.innerWidth <= POS_MOBILE_BREAKPOINT)
const cartOpen = ref(false)

function onPosResize() {
  const mobile = window.innerWidth <= POS_MOBILE_BREAKPOINT
  if (mobile === isMobile.value) return
  isMobile.value = mobile
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
    await loadRemnants()
  } catch (e) {
    loadError.value = 'Failed to load products. Please check your connection and try again.'
    console.error('loadCatalog error:', e)
  } finally {
    loading.value = false
  }
}

/** Leftover pieces still on hand. A failure here must never block the normal catalog,
 *  but it IS reported (remnantError) so it can't be mistaken for "no leftovers". */
async function loadRemnants() {
  remnantError.value = ''
  try {
    const { data } = await api.get('/api/pos/remnants', { skipLoading: true })
    remnants.value = (data || []).map(r => ({
      id: r.RemnantID,
      productId: r.ProductID,
      variantId: r.VariantID ?? null,
      productName: r.ProductName,
      sku: r.SKU,
      image: r.ImageURL ?? null,
      label: r.VariantLabel || '',
      qty: Math.max(Number(r.Quantity) || 0, 1),   // older rows store 0 = one piece
      length: Number(r.Length) || 0,
      unit: r.Unit || '',
      // Baseline price: list price of the uncut piece minus what the cut sold for
      // (older leftovers: same share of the list price as the share of the piece left).
      // null = unknown -> the cashier types the price.
      price: r.suggested_price != null ? Number(r.suggested_price) : null,
    }))
  } catch (e) {
    remnants.value = []
    remnantError.value = e?.response?.status === 404
      ? 'Leftover service not found (404). Check the /api/pos/remnants route.'
      : 'Could not load leftover pieces.'
    console.error('loadRemnants error:', e)
  }
}

// Refresh every time the cashier opens the Leftovers tab, so changes made on the
// Inventory Logs page (used / scrapped / put back) show up without a full reload.
watch(view, v => { if (v === 'leftovers') loadRemnants() })

onMounted(async () => {
  window.addEventListener('keydown', onGlobalKeydown)
  window.addEventListener('resize', onPosResize)
  loadRepeatBuyers() // preload so the customer dropdown opens instantly
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
  clearTimeout(customerSearchTimer)
  stopCamera()
})

function onImgError(product) { product.image = null }

function resetFilters() {
  search.value = ''
  selectedCategory.value = ''
  availableOnly.value = false
}

/* ────────────────────────────────────────────────────────────
   STOCK — PER SIZE
   Every size has its own stock and reorder level. The cart is tracked
   per size too, so adding 3 of "20 in × 50 mm" never eats into the
   stock of "36 in × 40 mm".

   Status of a size:
     oos : nothing left (stock minus what is already in the cart is 0)
     low : stock is at/below its reorder level (or 5 if none was set)
     ok  : healthy
   LOW and OUT OF STOCK are different things:
     - low  = warning only. The size can still be sold while any units remain
              (unless BLOCK_LOW_STOCK_SALES is turned on).
     - oos  = nothing left. The size is disabled in the size picker and the
              product card is greyed out.

   Leftover (remnant) lines are NOT part of this: they come out of
   product_remnants on the server, never out of whole-piece stock.
──────────────────────────────────────────────────────────── */
const variantThreshold = v => (v.reorderLevel > 0 ? v.reorderLevel : LOW_STOCK_DEFAULT)
const hasVariants = p => (p.variants?.length ?? 0) > 0

// Cart quantity per product (all sizes) — used for legacy products with no sizes.
// Leftover lines are skipped: they don't use whole-piece stock.
const cartQtyByProduct = computed(() => {
  const m = new Map()
  cart.value.filter(i => !i.remnantId).forEach(i => m.set(i.id, (m.get(i.id) || 0) + i.qty))
  return m
})

/** Qty of ONE size already in the cart (optionally excluding the line being edited). */
function inCartVariant(productId, variantId, excludeKey = null) {
  if (productId == null) return 0
  return cart.value
    .filter(i => !i.remnantId && i.id === productId && (i.variantId ?? null) === (variantId ?? null) && i.key !== excludeKey)
    .reduce((s, i) => s + i.qty, 0)
}
function variantAvailable(product, v, excludeKey = null) {
  return Math.max(0, v.stock - inCartVariant(product.id, v.id, excludeKey))
}
function variantStatus(product, v, excludeKey = null) {
  if (variantAvailable(product, v, excludeKey) <= 0) return 'oos'
  return v.stock <= variantThreshold(v) ? 'low' : 'ok'
}
/** A size can be sold when it is in stock ('ok'), or 'low' (as long as
 *  low-stock sales are not blocked). Only 'oos' is never sellable. */
function isVariantSellable(product, v, excludeKey = null) {
  const s = variantStatus(product, v, excludeKey)
  if (s === 'oos') return false
  if (s === 'low') return !BLOCK_LOW_STOCK_SALES
  return true
}
function variantStockText(product, v, excludeKey = null) {
  const s = variantStatus(product, v, excludeKey)
  const a = variantAvailable(product, v, excludeKey)
  if (s === 'oos') return 'Out of stock'
  if (s === 'low') return BLOCK_LOW_STOCK_SALES ? `Low · ${a} left · unavailable` : `Low · ${a} left`
  return `${a} in stock`
}
const sellableVariants = p => p.variants.filter(v => isVariantSellable(p, v))

/** Units the cashier can still sell for this product. */
function availableFor(product) {
  if (hasVariants(product)) {
    return product.variants.reduce(
      (s, v) => s + (isVariantSellable(product, v) ? variantAvailable(product, v) : 0), 0
    )
  }
  return Math.max(0, product.stock - (cartQtyByProduct.value.get(product.id) || 0))
}
function canAddProduct(p) {
  return hasVariants(p) ? sellableVariants(p).length > 0 : availableFor(p) > 0
}
/** Status key ('ok' | 'low' | 'oos') shared by the badge AND the grid sort.
 *  - nothing left to sell                      → oos
 *  - some sizes healthy (others may be low)    → ok
 *  - every sellable size is low                → low (still sellable)
 *  When low-stock sales are blocked, a product whose only remaining
 *  stock is in low sizes shows 'low' but its card is disabled. */
function stockStatus(p) {
  if (hasVariants(p)) {
    const sellable = sellableVariants(p)
    if (!sellable.length) {
      return p.variants.some(v => variantAvailable(p, v) > 0) ? 'low' : 'oos'
    }
    return sellable.every(v => variantStatus(p, v) === 'low') ? 'low' : 'ok'
  }
  const avail = availableFor(p)
  return avail <= 0 ? 'oos' : avail <= LOW_STOCK_DEFAULT ? 'low' : 'ok'
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
   Grid order: In Stock first, then Low Stock, then Out of Stock
   (stable sort). Search also matches size labels (e.g. "8 ft").
──────────────────────────────────────────────────────────── */
const normalizedSearch = computed(() => search.value.trim().toLowerCase())
const filteredProducts = computed(() => {
  const q = normalizedSearch.value
  const cat = selectedCategory.value
  const list = products.value.filter(p => {
    if (cat && p.category != cat) return false
    if (availableOnly.value && !canAddProduct(p)) return false
    if (!q) return true
    return (
      p.name.toLowerCase().includes(q) ||
      p.sku.toLowerCase().includes(q) ||
      (p.barcode && p.barcode.toLowerCase().includes(q)) ||
      p.variants.some(v => v.label.toLowerCase().includes(q))
    )
  })
  return list.sort((a, b) => STOCK_SORT_PRIORITY[stockStatus(a)] - STOCK_SORT_PRIORITY[stockStatus(b)])
})

/* ────────────────────────────────────────────────────────────
   LEFTOVER PIECES (remnants)
   A leftover is what is left of a piece after a cut. It can be sold
   again, as its own cart line. Selling one never touches whole-piece stock.
──────────────────────────────────────────────────────────── */
const fmtLeft = r => `${round2(r.length)} ${r.unit}`.trim()

// A leftover is the same product, only a different size: show the same photo and SKU
// as the whole product (falls back to what the leftover endpoint returned).
const productOf = r => products.value.find(p => p.id === r.productId)
const remnantImage = r => productOf(r)?.image ?? r.image
const remnantSku = r => productOf(r)?.sku || r.sku

function remnantInCart(id) {
  return cart.value.filter(i => i.remnantId === id).reduce((s, i) => s + i.qty, 0)
}
function remnantAvailable(r) {
  return Math.max(0, r.qty - remnantInCart(r.id))
}

const filteredRemnants = computed(() => {
  const q = normalizedSearch.value
  const cat = selectedCategory.value
  const list = remnants.value.filter(r => {
    if (cat) {
      const prod = products.value.find(p => p.id === r.productId)
      if (prod && prod.category != cat) return false
    }
    if (!q) return true
    return (
      r.productName.toLowerCase().includes(q) ||
      (remnantSku(r) || '').toLowerCase().includes(q) ||
      r.label.toLowerCase().includes(q)
    )
  })
  // Sellable ones first
  return list.slice().sort((a, b) => (remnantAvailable(a) > 0 ? 0 : 1) - (remnantAvailable(b) > 0 ? 0 : 1))
})

function addRemnantToCart(r) {
  if (remnantAvailable(r) <= 0) return
  checkoutError.value = ''
  const existing = cart.value.find(i => i.remnantId === r.id)
  if (existing) { existing.qty += 1; return }

  cart.value.push({
    key: `rem-${r.id}`,
    id: r.productId,
    variantId: r.variantId,
    remnantId: r.id,
    remnantMax: r.qty,
    name: `${r.productName}${r.label ? ` – ${r.label}` : ''} (leftover ${fmtLeft(r)})`,
    price: r.price ?? 0,
    baseline: r.price,          // baseline price (uncut list price - cut price); the cashier can still change the price
    qty: 1,
    cutLength: null,
    cutWidth: null,
    pricingType: 'piece',
    origPrice: null,
    priceReason: null,
    cut: null,
  })
  // No suggested price for this leftover (older rows) → ask for one right away.
  if (r.price == null) openAdjustModal(cart.value[cart.value.length - 1])
}

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
  lastScanCode = ''
  lastScanAt = 0
  clearTimeout(barcodeTimer)
  if (scannerActive.value) {
    if (scanMode.value === 'usb') nextTick(() => barcodeInput.value?.focus())
    // Camera mode starts from onScannerBannerEnter() once the banner is open.
  } else {
    stopCamera()
  }
}
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
  if (mode === 'camera') startCamera()
  else nextTick(() => barcodeInput.value?.focus())
}
/** Distinguishes a hardware (keyboard-wedge) scanner's rapid keystroke burst
 *  from a human typing the barcode by hand. Manual typing is never
 *  auto-submitted — it always waits for Enter. */
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

  if (e.key.length !== 1 && e.key !== 'Backspace') return

  clearTimeout(barcodeTimer)

  if (e.key === 'Backspace') {
    keyTimestamps = []
    return
  }

  keyTimestamps.push(Date.now())
  if (keyTimestamps.length > 6) keyTimestamps.shift()

  const looksLikeScanner = keyTimestamps.length >= 3 &&
    keyTimestamps.slice(1).every((t, i) => t - keyTimestamps[i] <= SCANNER_KEY_INTERVAL_MS)

  if (looksLikeScanner) {
    barcodeTimer = setTimeout(() => {
      const code = barcodeBuffer.value.trim()
      if (code.length >= 4) {
        barcodeBuffer.value = ''
        keyTimestamps = []
        handleScannedCode(code)
      }
    }, SCAN_DEBOUNCE_MS)
  }
}
/** Global keys:
 *  - "/" jumps to the product search (unless the user is typing somewhere).
 *  - While the USB scanner is on, keeps the hidden scanner input focused,
 *    but never steals focus from a field in use or competes with a modal. */
function onGlobalKeydown(e) {
  if (showQtyModal.value || showCashModal.value || showClearModal.value || showServiceModal.value || showAdjModal.value) return
  const active = document.activeElement
  const typingElsewhere = active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName)

  if (e?.key === '/' && !typingElsewhere && !e.ctrlKey && !e.metaKey && !e.altKey) {
    e.preventDefault()
    searchInput.value?.focus()
    return
  }

  if (!scannerActive.value || scanMode.value !== 'usb') return
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
function showScanSuccess(name) {
  showFeedback('success', `Found: ${name}`, 1500)
  if (scanMode.value === 'camera') {
    cameraFlashVisible.value = true
    clearTimeout(cameraFlashTimer)
    cameraFlashTimer = setTimeout(() => { cameraFlashVisible.value = false }, 850)
  }
  scheduleCameraHint()
}
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

/** Single entry point for ANY scan source (USB keyboard-wedge or camera). */
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
  if (!canAddProduct(product)) {
    // Only reachable when the product is out of stock — or, when
    // BLOCK_LOW_STOCK_SALES is on, when only low sizes remain.
    const lowOnly = BLOCK_LOW_STOCK_SALES && hasVariants(product) &&
      product.variants.some(v => variantAvailable(product, v) > 0)
    showScannerMessage(
      lowOnly
        ? `${product.name} is low on stock and cannot be sold. Restock it first.`
        : `${product.name} is out of stock.`
    )
    return
  }
  showScanSuccess(product.name)
  // A product with several sizes is ambiguous — the cashier must pick the
  // size, so only single-size products are eligible for one-tap auto-add.
  const singleSize = (product.variants?.length ?? 0) <= 1
  if (autoAddOnScan.value && product.pricingType !== 'sqm' && singleSize) {
    quickAddOne(product)
  } else {
    openQuantityModal(product)
  }
}
function quickAddOne(product) {
  const variant = hasVariants(product) ? product.variants.find(v => isVariantSellable(product, v)) : null
  if (hasVariants(product) ? !variant : availableFor(product) <= 0) {
    showScannerMessage(`Not enough stock for ${product.name}.`)
    return
  }
  const key = buildKey(product, 1, 1, variant?.id ?? null)
  const existing = cart.value.find(i => i.key === key)
  if (existing) existing.qty += 1
  else cart.value.push({
    key,
    id: product.id,
    variantId: variant?.id ?? null,
    name: variant ? `${product.name} – ${variant.label}` : product.name,
    price: variant?.price ?? product.price,
    qty: 1,
    cutLength: null,
    cutWidth: null,
    pricingType: product.pricingType,
  })
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
      (decodedText) => handleScannedCode(decodedText),
      () => { /* per-frame "not found" noise, ignore */ }
    )
    scheduleCameraHint()
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
  html5QrCode = null
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
   QUANTITY MODAL — MULTI-SIZE SELECTION / CUT-SIZE PRICING

   Every size (or the single "Standard" row for products without sizes)
   is one row with its own quantity. All rows with a quantity above 0
   are added to the cart together, one cart line per size.
──────────────────────────────────────────────────────────── */
const rowKey = v => v.id ?? 'base'

function openQuantityModal(product) {
  selectedProduct.value = product
  variantSearch.value = ''
  draftQty.value = {}
  editingKey.value = null
  modalError.value = ''
  cutLength.value = product.minDimension || 1
  cutWidth.value = product.minDimension || 1
  showQtyModal.value = true
  // Only one thing to pick (e.g. after a barcode scan)? Pre-fill 1 for speed.
  const rows = modalRowsAll.value
  if (rows.length === 1 && maxQtyFor(rows[0]) > 0) setRowQty(rows[0], 1)
}
function closeQtyModal() {
  showQtyModal.value = false
  modalError.value = ''
  editingKey.value = null
  variantSearch.value = ''
  draftQty.value = {}
}
function editCartItem(item) {
  const unit = item.pricingType === 'sqm' && item.cutLength && item.cutWidth
    ? round2(item.price / (item.cutLength * item.cutWidth))
    : item.price
  const product = products.value.find(p => p.id === item.id) || {
    id: item.id,
    name: item.name,
    variants: item.variantId != null
      ? [{ id: item.variantId, label: 'Selected size', price: unit, stock: item.qty, reorderLevel: 0 }]
      : [],
    price: unit,
    stock: item.qty,
    pricingType: item.pricingType,
    minDimension: MIN_DIM,
    maxDimension: MAX_DIM,
  }
  selectedProduct.value = product
  variantSearch.value = ''
  cutLength.value = item.cutLength || product.minDimension || 1
  cutWidth.value = item.cutWidth || product.minDimension || 1
  editingKey.value = item.key
  draftQty.value = { [item.variantId ?? 'base']: item.qty }
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

const modalHasSizes = computed(() => (selectedProduct.value?.variants?.length ?? 0) > 0)

/** All rows for the open product. Products without sizes get one "Standard" row. */
const modalRowsAll = computed(() => {
  const p = selectedProduct.value
  if (!p) return []
  if (p.variants?.length) return p.variants
  return [{ id: null, label: 'Standard', price: p.price, stock: p.stock, reorderLevel: 0 }]
})

/** Rows as displayed: filtered by "Find a size", sellable first, then by size. */
const modalVariants = computed(() => {
  const p = selectedProduct.value
  if (!p) return []
  const needle = variantSearch.value.trim().toLowerCase()
  const list = modalRowsAll.value.filter(v => !needle || v.label.toLowerCase().includes(needle))
  return list.slice().sort((a, b) => {
    const sa = isVariantSellable(p, a, editingKey.value) ? 0 : 1
    const sb = isVariantSellable(p, b, editingKey.value) ? 0 : 1
    return sa - sb || compareSize(a, b)
  })
})
const sellableVariantCount = computed(() => {
  const p = selectedProduct.value
  return p ? modalRowsAll.value.filter(v => isVariantSellable(p, v, editingKey.value)).length : 0
})

function rowStatus(v) { return variantStatus(selectedProduct.value, v, editingKey.value) }
function rowQty(v) { return draftQty.value[rowKey(v)] || 0 }
/** Most the cashier can still add for this size (0 when it cannot be sold). */
function maxQtyFor(v) {
  const p = selectedProduct.value
  return p && isVariantSellable(p, v, editingKey.value) ? variantAvailable(p, v, editingKey.value) : 0
}
function setRowQty(v, raw) {
  const n = clamp(Math.trunc(Number(raw) || 0), 0, maxQtyFor(v))
  draftQty.value = { ...draftQty.value, [rowKey(v)]: n }
  modalError.value = ''
  return n
}
function stepRow(v, delta) { setRowQty(v, rowQty(v) + delta) }
function onQtyInput(v, e) {
  const raw = e.target.value
  if (raw === '') { setRowQty(v, 0); return }
  const n = setRowQty(v, raw)
  if (String(n) !== raw) e.target.value = n   // show the clamped value
}
function clearDraft() { draftQty.value = {} }

/** Unit price of a size (sqm products: size price × cut area). */
function unitPriceFor(v) {
  return isSqm.value ? round2(v.price * cutArea.value) : v.price
}

const selectedRows = computed(() => modalRowsAll.value.filter(v => rowQty(v) > 0))
const totalUnits = computed(() => selectedRows.value.reduce((s, v) => s + rowQty(v), 0))
const modalTotal = computed(() => round2(selectedRows.value.reduce((s, v) => s + rowQty(v) * unitPriceFor(v), 0)))
const canSubmitModal = computed(() => selectedRows.value.length > 0)

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

function addToCart() {
  const p = selectedProduct.value
  if (!p) return
  modalError.value = ''

  const rows = selectedRows.value
  if (!rows.length) { modalError.value = 'Enter a quantity for at least one size.'; return }

  for (const v of rows) {
    const max = maxQtyFor(v)
    if (rowQty(v) > max) {
      modalError.value = `Only ${max} unit(s) available for ${v.label}.`
      return
    }
  }

  // Editing replaces the old line; every selected size then becomes its own line.
  if (editingKey.value) cart.value = cart.value.filter(i => i.key !== editingKey.value)

  const hasSizes = !!p.variants?.length
  const dimensionLabel = isSqm.value ? ` (${cutLength.value}×${cutWidth.value}m)` : ''

  rows.forEach(v => {
    const variantId = hasSizes ? v.id : null
    const key = buildKey(p, cutLength.value, cutWidth.value, variantId)
    const price = unitPriceFor(v)
    const q = rowQty(v)
    const existing = cart.value.find(i => i.key === key)
    if (existing) {
      existing.qty += q
      existing.price = price
    } else {
      cart.value.push({
        key,
        id: p.id,
        variantId,
        name: `${p.name}${hasSizes ? ` – ${v.label}` : ''}${dimensionLabel}`,
        price,
        qty: q,
        cutLength: isSqm.value ? cutLength.value : null,
        cutWidth: isSqm.value ? cutWidth.value : null,
        pricingType: p.pricingType,
      })
    }
  })

  closeQtyModal()
}

/* ────────────────────────────────────────────────────────────
   CART
──────────────────────────────────────────────────────────── */
function incrementLine(item) {
  // Leftover line: limited by how many of THAT leftover exist, not by whole-piece stock.
  if (item.remnantId) {
    const r = remnants.value.find(x => x.id === item.remnantId)
    const max = r ? r.qty : (item.remnantMax || item.qty)
    if (remnantInCart(item.remnantId) + 1 > max) {
      checkoutError.value = `Only ${max} of that leftover piece available.`
      return
    }
    checkoutError.value = ''
    item.qty += 1
    return
  }

  const product = products.value.find(p => p.id === item.id)
  const variant = product?.variants?.find(v => v.id === item.variantId)
  const stock = variant ? variant.stock : (product ? product.stock : Infinity)
  const usedByOthers = inCartVariant(item.id, item.variantId, item.key)
  if (item.qty + usedByOthers + 1 > stock) {
    checkoutError.value = `Not enough stock to add another unit of ${item.name}.`
    return
  }
  checkoutError.value = ''
  item.qty += 1
}
function decrementLine(item) {
  if (item.qty <= 1) { removeFromCart(item.key); return }
  item.qty -= 1
}
function removeFromCart(key) { cart.value = cart.value.filter(i => i.key !== key) }
const cartUnits = computed(() => cart.value.reduce((s, i) => s + i.qty, 0))
function clearCart() {
  if (!cart.value.length && !services.value.length) return
  showClearModal.value = true
  nextTick(() => clearKeepBtn.value?.focus())   // safe default: Enter keeps the items
}
function closeClearModal() { showClearModal.value = false }
function confirmClearCart() {
  cart.value = []
  services.value = []
  checkoutError.value = ''
  showClearModal.value = false
}
const clearSummary = computed(() => {
  const parts = []
  const n = cart.value.length
  if (n) parts.push(`${n} item${n === 1 ? '' : 's'} (${cartUnits.value} unit${cartUnits.value === 1 ? '' : 's'})`)
  const m = services.value.length
  if (m) parts.push(`${m} service fee${m === 1 ? '' : 's'}`)
  const amt = round2(subtotal.value + serviceTotal.value)
  return `This removes ${parts.join(' and ')} (₱${amt.toFixed(2)}) from the sale. This can't be undone.`
})

/* ────────────────────────────────────────────────────────────
   EXTRA SERVICES
   fee           = what the customer pays (printed on the receipt)
   workerAmount  = the worker's cut (pass-through, NOT shop revenue)
   ownerAmount   = fee - workerAmount (the only part that is shop sales/profit)
──────────────────────────────────────────────────────────── */
function splitFee(fee, type, value) {
  const f = round2(Math.max(Number(fee) || 0, 0))
  const v = Math.max(Number(value) || 0, 0)
  const raw = type === '%' ? f * (Math.min(v, 100) / 100) : v
  const worker = round2(Math.min(raw, f))
  return { fee: f, worker, owner: round2(f - worker) }
}
const svcSplit = computed(() => splitFee(svcFee.value, svcShareType.value, svcShareValue.value))

const serviceTotal       = computed(() => round2(services.value.reduce((s, x) => s + x.fee, 0)))
const serviceWorkerTotal = computed(() => round2(services.value.reduce((s, x) => s + x.workerAmount, 0)))
const serviceOwnerTotal  = computed(() => round2(services.value.reduce((s, x) => s + x.ownerAmount, 0)))
const cartCount          = computed(() => cart.value.length + services.value.length)

function openServiceModal(service = null) {
  svcError.value = ''
  svcFieldErr.value = {}
  if (service) {
    editingServiceId.value = service.id
    svcName.value = service.name
    svcFee.value = service.fee
    svcWorker.value = service.workerName
    svcShareType.value = service.shareType
    svcShareValue.value = service.shareValue
  } else {
    editingServiceId.value = null
    svcName.value = ''
    svcFee.value = null
    svcWorker.value = ''
    svcShareType.value = '%'
    svcShareValue.value = SERVICE_DEFAULT_WORKER_PCT
  }
  showServiceModal.value = true
  nextTick(() => svcNameInput.value?.focus())
}
function closeServiceModal() {
  showServiceModal.value = false
  svcError.value = ''
  svcFieldErr.value = {}
  editingServiceId.value = null
}
function setShareType(type) {
  if (svcShareType.value === type) return
  svcShareType.value = type
  svcShareValue.value = type === '%' ? SERVICE_DEFAULT_WORKER_PCT : 0
}
const SVC_FIELD_LABELS = { name: 'service name', fee: 'service fee', share: "worker's share", worker: 'worker' }
const SVC_FIELD_IDS    = { name: 'svc-name', fee: 'svc-fee', share: 'svc-share', worker: 'svc-worker' }
/** Checks every field, shows a message under each bad one plus a banner above the buttons.
 *  Returns the keys of the invalid fields (empty = all good). */
function validateService() {
  const errs = {}
  const { fee, worker } = svcSplit.value
  if (!svcName.value.trim()) errs.name = 'Enter the service name.'
  if (!(fee > 0)) errs.fee = 'Enter a service fee greater than zero.'
  else if (svcShareType.value === 'PHP' && Number(svcShareValue.value) > fee) errs.share = "Worker's share can't be more than the service fee."
  if (worker > 0 && !svcWorker.value.trim()) errs.worker = 'Enter the worker who earns this share.'
  svcFieldErr.value = errs
  const keys = Object.keys(errs)
  svcError.value = !keys.length ? ''
    : keys.length === 1 ? errs[keys[0]]
    : `Please fix ${keys.length} things before adding: ${keys.map(k => SVC_FIELD_LABELS[k]).join(', ')}.`
  return keys
}
// Once a message is showing, keep it in step with what the cashier types (it disappears as they fix it).
watch([svcName, svcFee, svcWorker, svcShareValue, svcShareType], () => { if (svcError.value) validateService() })

function saveService() {
  const bad = validateService()
  if (bad.length) {
    nextTick(() => document.getElementById(SVC_FIELD_IDS[bad[0]])?.focus())
    return
  }
  const name = svcName.value.trim()
  const { fee, worker, owner } = svcSplit.value

  const entry = {
    id: editingServiceId.value ?? `svc-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
    name,
    fee,
    workerName: svcWorker.value.trim(),
    shareType: svcShareType.value,
    shareValue: Math.max(Number(svcShareValue.value) || 0, 0),
    workerAmount: worker,
    ownerAmount: owner,
  }
  const idx = services.value.findIndex(x => x.id === entry.id)
  if (idx >= 0) services.value.splice(idx, 1, entry)
  else services.value.push(entry)
  closeServiceModal()
}
function removeService(id) { services.value = services.value.filter(x => x.id !== id) }

/* ────────────────────────────────────────────────────────────
   CUT PIECE / CUSTOM PRICE (per cart line)
   item.origPrice  = list price before the change (null = line not adjusted)
   item.priceReason, item.cut = { fullLength, soldLength, unit } | null
   A cut line always consumes WHOLE pieces from stock (qty = pieces cut).
   Leftover lines (item.remnantId) can only get a custom price.

   AUTOMATIC FULL LENGTH + UNIT
   The size the cashier already chose ("8 ft", "2.5 m" …) tells us the full
   piece length and its unit, so the cashier only types the length to sell.
   When the size name has no readable length, the full length is typed once
   by hand (with a unit picker).
──────────────────────────────────────────────────────────── */
function fmtLen(n) { return String(round2(n)) }
function lineName(i) {
  return i.cut ? `${i.name} (cut ${fmtLen(i.cut.soldLength)} ${i.cut.unit} of ${fmtLen(i.cut.fullLength)} ${i.cut.unit})` : i.name
}
/** Size labels that mean "no specification" (the default size every plain product gets). */
const NO_SPEC_LABELS = new Set(['', 'standard', 'default', 'regular', 'std', 'none', 'n/a', 'na', '-', '—', 'selected size'])
const hasSpecLabel = label => !NO_SPEC_LABELS.has(String(label ?? '').trim().toLowerCase())
/** "Adjust line" (cut piece / custom price) is only offered for lines that carry a real
 *  specification, e.g. "16 in × 40 mm" or "8 ft". Plain products and products whose only
 *  size is "Standard" never show it. Exceptions that keep the button:
 *   - leftover lines (they may need a price set)
 *   - lines that were already adjusted (so the change can still be edited or reset) */
function canAdjustLine(item) {
  if (!item) return false
  if (item.remnantId || item.origPrice != null) return true
  if (item.variantId == null) return false
  const product = products.value.find(p => p.id === item.id)
  const variant = product?.variants?.find(v => v.id === item.variantId)
  return hasSpecLabel(variant?.label)
}

/** Reads { length, unit, source } from the size (variant) label of a cart line. */
function specForItem(item) {
  if (!item || item.remnantId) return null
  const product = products.value.find(p => p.id === item.id)
  const variant = product?.variants?.find(v => v.id === item.variantId)
  const label = variant?.label
  if (!label) return null
  const spec = parseLengthSpec(label)
  return spec ? { length: spec.length, unit: spec.unit, source: label } : null
}

const adjItem      = computed(() => cart.value.find(i => i.key === adjKey.value) || null)
const adjIsSqm     = computed(() => adjItem.value?.pricingType === 'sqm')
const adjListPrice = computed(() => adjItem.value ? (adjItem.value.origPrice ?? adjItem.value.price) : 0)
const adjSuggested = computed(() => {
  const f = Number(adjFull.value), sd = Number(adjSold.value)
  if (!(f > 0) || !(sd > 0) || sd >= f) return null
  return round2(adjListPrice.value * sd / f)
})
const adjRemainder = computed(() => adjSuggested.value == null ? null : round2(Number(adjFull.value) - Number(adjSold.value)))
/** True once a valid full length + length sold are set (enables Apply in cut mode). */
const adjCutReady  = computed(() => adjSuggested.value != null)
/** List price per unit of length, e.g. "₱62.50 per ft". */
const adjUnitPrice = computed(() => {
  const f = Number(adjFull.value)
  return f > 0 && adjListPrice.value > 0 ? round2(adjListPrice.value / f) : null
})
/** The size (variant) label of the line being adjusted, shown as a chip in the header. */
const adjSizeLabel = computed(() => {
  const item = adjItem.value
  if (!item || item.remnantId) return ''
  const product = products.value.find(p => p.id === item.id)
  return product?.variants?.find(v => v.id === item.variantId)?.label || ''
})
const adjPriceNum  = computed(() => round2(Number(adjPrice.value) || 0))
const adjDiff      = computed(() => round2(adjPriceNum.value - adjListPrice.value))
const adjDiffPct   = computed(() => adjListPrice.value > 0 ? Math.round((adjDiff.value / adjListPrice.value) * 100) : 0)
/** Live message under the "Length to sell" field. */
const adjSoldError = computed(() => {
  const f = Number(adjFull.value), sd = Number(adjSold.value)
  if (!(sd > 0)) return ''
  if (f > 0 && sd >= f) return `Must be shorter than the full ${fmtLen(f)} ${adjUnit.value} piece. Sell a whole piece as a normal item.`
  return ''
})
/** One-tap lengths: quarter, half and three-quarters of the full piece. */
const adjQuick = computed(() => {
  const f = Number(adjFull.value)
  if (!(f > 0)) return []
  const seen = new Set()
  return [0.25, 0.5, 0.75]
    .map(r => round2(f * r))
    .filter(v => v > 0 && v < f && !seen.has(v) && seen.add(v))
    .map(v => ({ value: v, label: `${fmtLen(v)} ${adjUnit.value}` }))
})
/** Proportions for the sold / leftover bar (kept readable at the extremes). */
const adjBar = computed(() => {
  if (adjSuggested.value == null) return null
  const pct = (Number(adjSold.value) / Number(adjFull.value)) * 100
  return { soldPct: Math.min(Math.max(pct, 22), 78) }
})

// Keep the price following the lengths until the cashier types their own price.
watch([adjFull, adjSold], () => {
  if (adjMode.value === 'cut' && !adjTouched.value && adjSuggested.value != null) adjPrice.value = adjSuggested.value
})

function pickSold(v) {
  adjSold.value = v
  nextTick(() => adjSoldInput.value?.focus())
}
function unlockFull() {
  adjFullLocked.value = false
  nextTick(() => adjFullInput.value?.focus())
}
function relockFull() {
  if (!adjSpec.value) return
  adjFull.value = adjSpec.value.length
  adjUnit.value = adjSpec.value.unit
  adjFullLocked.value = true
  nextTick(() => adjSoldInput.value?.focus())
}
function focusAdjField() {
  if (!showAdjModal.value || adjMode.value !== 'cut') return
  const el = adjFullLocked.value ? adjSoldInput.value : adjFullInput.value
  el?.focus()
}

function openAdjustModal(item) {
  if (!canAdjustLine(item)) return
  adjKey.value = item.key
  adjError.value = ''
  adjTouched.value = false
  adjReason.value = item.cut ? '' : (item.priceReason || '')

  // Full length + unit straight from the chosen size.
  const spec = specForItem(item)
  adjSpec.value = spec
  adjSold.value = null
  if (spec) {
    adjFull.value = spec.length
    adjUnit.value = spec.unit
    adjFullLocked.value = true
  } else {
    adjFull.value = null
    adjUnit.value = 'ft'
    adjFullLocked.value = false
  }

  if (item.cut) {
    adjMode.value = 'cut'
    adjFull.value = item.cut.fullLength
    adjSold.value = item.cut.soldLength
    adjUnit.value = item.cut.unit
    adjPrice.value = item.price
    adjTouched.value = true
    // Stay on "auto" only when the saved cut still matches the size.
    adjFullLocked.value = !!spec && spec.unit === item.cut.unit && spec.length === item.cut.fullLength
  } else if (item.origPrice != null || item.pricingType === 'sqm' || item.remnantId) {
    adjMode.value = 'price'
    adjPrice.value = item.price > 0 ? item.price : null
  } else {
    adjMode.value = 'cut'
    adjPrice.value = null
  }
  showAdjModal.value = true
  nextTick(focusAdjField)
  setTimeout(focusAdjField, 80)
}
function closeAdjModal() { showAdjModal.value = false; adjError.value = ''; adjKey.value = null }
function setAdjMode(mode) {
  if (adjMode.value === mode) return
  adjMode.value = mode
  adjError.value = ''
  adjTouched.value = false
  const item = adjItem.value
  if (mode === 'price') adjPrice.value = item ? item.price : null
  else {
    adjPrice.value = adjSuggested.value
    nextTick(focusAdjField)
  }
}
/** Removes the adjustment and merges back into the normal line when one exists. */
function clearLineAdjustment(item) {
  if (item.origPrice != null) item.price = item.origPrice
  item.origPrice = null
  item.priceReason = null
  item.cut = null
  const base = String(item.key).replace(/~adj-.*$/, '')
  if (base === item.key) return
  const twin = cart.value.find(i => i !== item && i.key === base)
  if (twin) { twin.qty += item.qty; cart.value = cart.value.filter(i => i !== item) }
  else item.key = base
}
function resetAdjustment() {
  const item = adjItem.value
  if (item) clearLineAdjustment(item)
  closeAdjModal()
}
function applyAdjustment() {
  const item = adjItem.value
  if (!item) return
  adjError.value = ''
  const price = round2(Number(adjPrice.value) || 0)

  let cut = null
  let reason = adjReason.value.trim()
  if (adjMode.value === 'cut') {
    const f = Number(adjFull.value), sd = Number(adjSold.value)
    if (!(f > 0)) { adjError.value = 'Enter the full piece length.'; return }
    if (!(sd > 0)) { adjError.value = 'Enter the length to sell.'; return }
    if (sd >= f) { adjError.value = 'Length sold must be shorter than the full piece. Sell a whole piece as a normal item.'; return }
    if (price <= 0) { adjError.value = 'Enter a price greater than zero.'; return }
    cut = { fullLength: round2(f), soldLength: round2(sd), unit: adjUnit.value }
    reason = reason || 'Cut piece'
  } else {
    if (price <= 0) { adjError.value = 'Enter a price greater than zero.'; return }
    if (REQUIRE_PRICE_REASON && !reason && !item.remnantId) {
      adjError.value = 'Add a reason for the price change.'
      return
    }
  }

  const orig = adjListPrice.value
  if (!cut && price === orig) { clearLineAdjustment(item); closeAdjModal(); return }

  item.origPrice = orig
  item.price = price
  item.priceReason = reason || null
  item.cut = cut
  // Adjusted lines get their own key so they never merge with a normal line.
  if (!String(item.key).includes('~adj-')) item.key = `${item.key}~adj-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`
  closeAdjModal()
}

/* ────────────────────────────────────────────────────────────
   TOTALS — VAT-INCLUSIVE PRICING
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

const grossAmount = computed(() => round2(Math.max(subtotal.value - discountAmount.value, 0)))
const vatableSales = computed(() => round2(grossAmount.value / VAT_DIVISOR))
const vat = computed(() => round2(grossAmount.value - vatableSales.value))
// Customer pays products (VAT-inclusive, after discount) + full service fees.
const total = computed(() => round2(grossAmount.value + serviceTotal.value))

const amountReceivedNum = computed(() => {
  const n = Number(amountReceived.value)
  return Number.isFinite(n) ? n : 0
})
const change = computed(() => round2(Math.max(amountReceivedNum.value - total.value, 0)))
const quickAmounts = computed(() => {
  const t = Math.ceil(total.value)
  if (t <= 0) return []
  const candidates = new Set()
  ;[100, 500, 1000].forEach(denom => {
    const v = Math.ceil(t / denom) * denom
    if (v >= t && v !== total.value) candidates.add(v)
  })
  return Array.from(candidates).sort((a, b) => a - b).slice(0, 3)
})

/* ────────────────────────────────────────────────────────────
   CUSTOMER (optional tracking for repeat buyers)
──────────────────────────────────────────────────────────── */
async function fetchCustomers(q, { silent = false } = {}) {
  const seq = ++customerSearchSeq
  try {
    const { data } = await api.get('/api/customers', {
      params: { search: q || undefined },
      skipLoading: true,
    })
    if (!q) repeatBuyersCache.value = data
    if (seq !== customerSearchSeq) return
    if (silent && !showSuggestions.value) return
    customerSuggestions.value = data
    suggestionsTitle.value = q ? '' : 'Repeat buyers'
    showSuggestions.value = data.length > 0
  } catch (e) {
    console.error('customer search error:', e)
  }
}

async function loadRepeatBuyers() {
  try {
    const { data } = await api.get('/api/customers', { skipLoading: true })
    repeatBuyersCache.value = data
  } catch (e) {
    console.error('repeat buyers preload error:', e)
  }
}

const visibleSuggestions = computed(() => {
  if (!suggestionsTitle.value) return customerSuggestions.value
  const q = repeatSearch.value.trim().toLowerCase()
  if (!q) return customerSuggestions.value
  const qDigits = q.replace(/\D/g, '')
  return customerSuggestions.value.filter(c => {
    const name = (c.CustomerName || '').toLowerCase()
    const phone = (c.ContactNumber || '').toLowerCase()
    const phoneDigits = phone.replace(/\D/g, '')
    return name.includes(q) || phone.includes(q) || (qDigits && phoneDigits.includes(qDigits))
  })
})

function pickFirstVisible() {
  const first = visibleSuggestions.value[0]
  if (first) pickCustomer(first)
}

function onCustomerFocusOut(e) {
  const next = e.relatedTarget
  if (next && customerFieldRef.value?.contains(next)) return
  showSuggestions.value = false
}

function onSuggestMouseDown(e) {
  if (e.target.closest('.customer-suggest-search')) return
  e.preventDefault()
}

watch(showSuggestions, (open) => {
  if (!open) repeatSearch.value = ''
})

function showRepeatBuyers() {
  repeatSearch.value = ''
  if (repeatBuyersCache.value.length) {
    customerSuggestions.value = repeatBuyersCache.value
    suggestionsTitle.value = 'Repeat buyers'
    showSuggestions.value = true
    fetchCustomers('', { silent: true })
  } else {
    fetchCustomers('')
  }
}

function onCustomerInput() {
  selectedCustomer.value = null
  clearTimeout(customerSearchTimer)
  const q = customerName.value.trim()
  if (!q) { showRepeatBuyers(); return }
  customerSearchTimer = setTimeout(() => fetchCustomers(q), 150)
}

function onCustomerFocus() {
  if (selectedCustomer.value) return
  const q = customerName.value.trim()
  if (!q || /^walk-in\b/i.test(q)) showRepeatBuyers()
  else fetchCustomers(q)
}

function toggleCustomerList() {
  if (showSuggestions.value) { showSuggestions.value = false; return }
  showRepeatBuyers()
  nextTick(() => repeatSearchInput.value?.focus())
}

function pickCustomer(c) {
  selectedCustomer.value = c
  customerName.value = c.CustomerName
  customerSuggestions.value = []
  showSuggestions.value = false
}

const trackedNewName = computed(() => {
  const n = customerName.value.trim()
  return n.length >= 2 && !/^walk-in\b/i.test(n) && !selectedCustomer.value
})

const customerStatusLabel = computed(() => {
  const c = selectedCustomer.value
  if (!c) return ''
  const n = Number(c.visits) || 0
  const need = Number(c.repeat_min) || 10
  return c.is_repeat
    ? `Repeat buyer · ${n} purchases`
    : `Tracking · ${n} of ${need} purchases`
})

function generateCustomerName() {
  selectedCustomer.value = null
  const ts = new Date()
  customerName.value = `Walk-in ${ts.getMonth() + 1}/${ts.getDate()} ${String(ts.getHours()).padStart(2, '0')}${String(ts.getMinutes()).padStart(2, '0')}`
}

/* ── Cart panel UI ── */
// Turns the customer field red when payment was attempted without a name.
const customerMissing = computed(() => !!checkoutError.value && !customerName.value.trim())

// Clears that error as soon as a name is typed or picked.
watch(customerName, (v) => {
  if (v.trim() && /customer name/i.test(checkoutError.value)) checkoutError.value = ''
})

/* ────────────────────────────────────────────────────────────
   CHECKOUT
──────────────────────────────────────────────────────────── */
/** Per-size stock check: totals the cart PER SIZE and compares each
 *  against that size's own stock. Only a real shortage (asking for more
 *  than is on hand) blocks checkout — a LOW size is still sellable.
 *  Leftover lines are checked separately against the leftover piece
 *  they come from. */
function verifyStockBeforeCheckout() {
  // 1) Normal (whole-piece) lines
  const totals = new Map()
  cart.value.filter(i => !i.remnantId).forEach(i => {
    const k = `${i.id}:${i.variantId ?? ''}`
    totals.set(k, { id: i.id, variantId: i.variantId, qty: (totals.get(k)?.qty || 0) + i.qty })
  })
  for (const t of totals.values()) {
    const product = products.value.find(p => p.id == t.id)
    if (!product) continue // only known via a fresh scan; server will validate
    const variant = product.variants?.find(v => v.id === t.variantId)
    const stock = variant ? variant.stock : product.stock
    if (t.qty > stock) {
      const name = variant ? `${product.name} – ${variant.label}` : product.name
      return stock <= 0
        ? `${name} is out of stock. Remove it from the cart.`
        : `Not enough stock for ${name}. Only ${stock} available.`
    }
    // Low-stock sizes are only blocked when BLOCK_LOW_STOCK_SALES is on.
    if (BLOCK_LOW_STOCK_SALES && variant && variantThreshold(variant) >= variant.stock && variant.stock > 0) {
      return `${product.name} – ${variant.label} is low on stock and cannot be sold. Remove it from the cart.`
    }
  }

  // 2) Leftover lines
  const remTotals = new Map()
  cart.value.filter(i => i.remnantId).forEach(i =>
    remTotals.set(i.remnantId, (remTotals.get(i.remnantId) || 0) + i.qty))
  for (const [id, q] of remTotals) {
    const r = remnants.value.find(x => x.id === id)
    if (!r) return 'A leftover piece in the cart is no longer available. Remove it and refresh.'
    if (q > r.qty) return `Only ${r.qty} of the ${r.productName} leftover (${fmtLeft(r)}) available.`
  }
  return ''
}

function openCashModal() {
  checkoutError.value = ''
  if (!customerName.value.trim()) { checkoutError.value = 'Please enter a customer name before processing payment.'; return }
  if (!cart.value.length && !services.value.length) { checkoutError.value = 'Cart is empty. Please add a product or service first.'; return }
  if (total.value <= 0) { checkoutError.value = 'Total must be greater than zero.'; return }
  // A leftover without a known price must be priced by the cashier first.
  const unpriced = cart.value.find(i => !(i.price > 0))
  if (unpriced) { checkoutError.value = `Set a price for ${unpriced.name}.`; return }
  const stockIssue = verifyStockBeforeCheckout()
  if (stockIssue) { checkoutError.value = stockIssue; return }
  amountReceived.value = 0
  checkoutIdempotencyKey.value = (crypto.randomUUID
    ? crypto.randomUUID()
    : `${Date.now()}-${Math.random().toString(36).slice(2)}`)
  showCashModal.value = true
  nextTick(() => amountInput.value?.focus())
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
      customer_id: selectedCustomer.value?.CustomerID ?? null,
      idempotency_key: checkoutIdempotencyKey.value,
      // total_amount above = products (after discount) + service_total, i.e. what the customer pays.
      service_total: serviceTotal.value,
      service_worker_total: serviceWorkerTotal.value,  // pass-through to workers, NOT shop revenue
      service_owner_total: serviceOwnerTotal.value,    // the only service amount that counts as shop sales/profit
      services: services.value.map(x => ({
        description: x.name,
        fee: x.fee,
        worker_name: x.workerName || null,
        worker_share_type: x.shareType === '%' ? 'percent' : 'amount',
        worker_share_value: x.shareValue,
        worker_amount: x.workerAmount,
        owner_amount: x.ownerAmount,
      })),
      items: cart.value.map(i => ({
        product_id: i.id,
        variant_id: i.variantId ?? null, // which size was sold (server re-checks its price + stock)
        remnant_id: i.remnantId ?? null, // set when this line is a leftover piece (sold from product_remnants)
        quantity: i.qty,
        price: i.price,
        subtotal: round2(i.qty * i.price),
        // Price changes / cut pieces. The server must accept (and ideally permission-check) these.
        price_override: i.origPrice != null && i.origPrice !== i.price,
        original_price: i.origPrice ?? i.price,   // for a cut: the list price of the uncut piece (used for the leftover's baseline)
        price_reason: i.priceReason || null,
        // qty = whole pieces consumed from stock; the leftover of each piece becomes a remnant.
        cut: i.cut ? {
          full_length: i.cut.fullLength,
          sold_length: i.cut.soldLength,
          remainder_length: round2(i.cut.fullLength - i.cut.soldLength),
          unit: i.cut.unit,
        } : null,
      })),
    }
    const { data } = await postSaleWithRetry(payload)

    // Snapshot everything the receipt needs BEFORE we clear state.
    receiptData.value = {
      receiptNumber: data?.receipt_number || data?.sale_id || `SSC-${Date.now().toString(36).toUpperCase()}`,
      date: new Date().toLocaleString(),
      customer: customerName.value.trim(),
      cashier: cashierName.value,
      items: cart.value.map(i => ({ name: lineName(i), qty: i.qty, price: i.price, total: round2(i.qty * i.price) })),
      services: services.value.map(x => ({ name: x.name, fee: x.fee })),   // full fee only — the split is never printed
      serviceTotal: serviceTotal.value,
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
    services.value = []
    customerName.value = ''
    selectedCustomer.value = null
    customerSuggestions.value = []
    amountReceived.value = 0
    discountValue.value = 0
    checkoutIdempotencyKey.value = ''
    showCashModal.value = false
    cartOpen.value = false

    await loadCatalog() // refresh per-size stock AND leftover pieces after a successful sale
    loadRepeatBuyers()
    nextTick(() => searchInput.value?.focus())

    // Tell Layout.vue to re-fetch notifications right now.
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
   Used by everything NOT teleported. The two modals are teleported to
   <body>, so they reference --c-* tokens directly with fallbacks. */
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
  position: relative;
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
  border-radius: 10px; padding: 0 40px 0 36px;
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
.search-kbd {
  position: absolute; right: 10px;
  min-width: 20px; height: 20px; padding: 0 5px;
  display: inline-flex; align-items: center; justify-content: center;
  border: 1px solid var(--p-border-strong); border-radius: 5px;
  font: 600 11px/1 'Inter', system-ui, sans-serif; color: var(--p-text-faint);
  background: var(--p-surface-sunken); pointer-events: none;
}

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
.product-count  { font-size: 12px; font-weight: 600; color: var(--p-text-faint); text-transform: uppercase; letter-spacing: .07em; margin-right: 4px; }
.product-filter-tag {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 600; color: var(--p-accent);
  background: var(--p-accent-soft); border: 1px solid var(--p-accent-border);
  border-radius: 20px; padding: 2px 8px;
}
.product-filter-tag button { background: none; border: none; cursor: pointer; color: inherit; font-size: 10px; padding: 0; }
.meta-chip {
  display: inline-flex; align-items: center; gap: 6px;
  height: 26px; padding: 0 11px;
  border: 1.5px solid var(--p-border-strong); border-radius: 20px;
  background: var(--p-surface); color: var(--p-text-muted);
  font-size: 11.5px; font-weight: 600; font-family: inherit; cursor: pointer;
  transition: border-color .15s, background-color .15s, color .15s;
}
.meta-chip i { font-size: 10px; }
.meta-chip:hover { border-color: var(--p-accent-border); color: var(--p-text-primary); }
.meta-chip--active { background: var(--p-accent-soft); border-color: var(--p-accent-border); color: var(--p-accent); }
.meta-chip:focus-visible, .scan-btn:focus-visible, .smode-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--p-ring); }
.meta-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; flex-wrap: wrap; }
/* Add service: neutral (grey) on purpose, not orange */
.service-chip:hover { border-color: var(--p-text-muted); color: var(--p-text-primary); background: var(--p-surface-raised); }
.service-chip-count {
  min-width: 16px; height: 16px; padding: 0 5px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  background: var(--p-text-secondary); color: var(--p-surface);
  font-size: 10px; font-weight: 800;
}

/* Products / Leftovers tab switch */
.view-tabs {
  display: inline-flex; gap: 4px; padding: 3px;
  border: 1px solid var(--p-border); border-radius: 10px;
  background: var(--p-surface-sunken);
}
.view-tabs button {
  display: inline-flex; align-items: center; gap: 6px;
  height: 26px; padding: 0 12px;
  border: none; border-radius: 7px; background: none; cursor: pointer;
  font: 600 12px 'Inter', system-ui, sans-serif; color: var(--p-text-muted);
  transition: background-color .15s, color .15s;
}
.view-tabs button:hover { color: var(--p-text-primary); }
.view-tabs button.is-on { background: var(--p-accent); color: #fff; }
.view-tabs button:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--p-ring); }
.view-tabs-count {
  min-width: 18px; padding: 0 6px; text-align: center;
  font-size: 10.5px; font-weight: 700; line-height: 18px; border-radius: 9px;
  background: rgba(127,127,127,.22);
}

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
.product-card:focus-visible { outline: none; border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); }
.product-card--oos  { opacity: .55; cursor: not-allowed; }
.product-card--oos:hover { transform: none; box-shadow: var(--p-shadow); border-color: var(--p-border); }
.product-card--flash { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); }
.product-card--skeleton { cursor: default; gap: 8px; pointer-events: none; }
.product-card--skeleton:hover { transform: none; border-color: var(--p-border); box-shadow: var(--p-shadow); }

.sk { border-radius: 6px; background: var(--p-border); animation: sk-pulse 1.3s ease-in-out infinite; }
.sk--img { height: 96px; border-radius: 7px; }
.sk--line { height: 11px; width: 90%; }
.sk--short { width: 55%; }
@keyframes sk-pulse { 0%,100% { opacity: .5; } 50% { opacity: 1; } }

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
.product-img-placeholder { color: var(--p-text-faint); font-size: 22px; }

.pcard-info    { flex: 1; min-height: 78px; display: flex; flex-direction: column; justify-content: flex-end; gap: 3px; }
.pcard-name {
  font-size: 12.5px; font-weight: 700; color: var(--p-text-primary);
  margin: 0; line-height: 1.3;
  display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden; word-break: break-word;
  min-height: 32.5px;
}
.pcard-sku { font-size: 10px; color: var(--p-text-faint); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pcard-tag {
  font-size: 9px; font-weight: 700; color: var(--p-accent);
  background: var(--p-accent-soft); border: 1px solid var(--p-accent-border);
  border-radius: 20px; padding: 1px 6px; margin: 2px 0 0; display: inline-block; width: fit-content;
}
.pcard-footer  { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
.pcard-price       { font-size: 14px; font-weight: 800; color: var(--p-accent); }
.pcard-stock-count { font-size: 10px; font-weight: 600; color: var(--p-text-faint); }

.product-add-overlay {
  position: absolute; inset: 0;
  background: rgba(234,88,12,.08); display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity .18s; font-size: 22px; color: var(--p-accent);
  border-radius: calc(var(--radius) - 1px); pointer-events: none;
}
.product-card:not(.product-card--oos):hover .product-add-overlay { opacity: 1; }

.product-empty {
  grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 60px 0; color: var(--p-text-faint); gap: 10px; font-size: 13px;
}
.product-empty i { font-size: 36px; }

/* ── RIGHT PANEL ──
   Header (title + customer) and summary stay pinned;
   only .cart-items scrolls. */
.pos-right {
  width: 352px; min-width: 352px;
  background: var(--p-surface);
  border-left: 1px solid var(--p-border);
  display: flex; flex-direction: column;
  overflow: hidden;
  transition: background-color .22s, border-color .22s;
}

.cart-drawer-handle {
  width: 36px; height: 4px; border-radius: 3px;
  background: var(--p-border-strong);
  margin: 10px auto 0;
  flex-shrink: 0;
}

/* Cart header — one compact title row */
.cart-header {
  position: relative; z-index: 5;  /* customer dropdown floats above the list */
  padding: 10px 14px 8px; border-bottom: 1px solid var(--p-border);
  flex-shrink: 0; background: var(--p-surface);
  transition: border-color .22s, background-color .22s;
}
.cart-header-top { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; row-gap: 6px; margin-bottom: 8px; }
.cart-title-wrap { display: flex; align-items: center; gap: 10px; min-width: 0; }
.cart-header-top-right { display: flex; align-items: center; gap: 6px; }
.cart-title   { font-size: 16px; font-weight: 800; color: var(--p-text-primary); margin: 0; letter-spacing: -.02em; transition: color .22s; }
.cart-badge   {
  font-size: 11px; font-weight: 700;
  background: var(--p-accent-soft); color: var(--p-accent);
  border: 1px solid var(--p-accent-border); border-radius: 20px; padding: 2px 9px;
}
.cart-clear-link {
  display: inline-flex; align-items: center; gap: 5px;
  height: 28px; padding: 0 9px; border: none; border-radius: 7px;
  background: none; color: var(--p-text-faint);
  font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer;
  transition: background .15s, color .15s;
}
.cart-clear-link:hover { background: rgba(244,63,94,.10); color: var(--p-red); }
.cart-svc-btn {
  display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 10px;
  border: 1.5px dashed var(--p-border-strong); border-radius: 8px; background: none; cursor: pointer;
  color: var(--p-text-muted); font: 600 12px 'Inter', system-ui, sans-serif; white-space: nowrap;
  transition: border-color .15s, color .15s, background-color .15s;
}
.cart-svc-btn i { font-size: 11px; }
.cart-svc-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); background: var(--p-accent-soft); }
.cart-svc-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--p-ring); }
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
  width: 100%; height: 34px;
  border: 1.5px solid var(--p-border-strong); border-radius: 9px;
  padding: 0 12px 0 32px; font-size: 13px; font-family: inherit;
  color: var(--p-text-primary); background: var(--p-surface-sunken);
  outline: none; transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.customer-input:focus { border-color: var(--p-accent); background: var(--p-surface); }
.customer-input::placeholder { color: var(--p-text-faint); }
.customer-input--error { border-color: var(--p-red); box-shadow: 0 0 0 3px rgba(244,63,94,.18); }
.customer-gen-btn {
  width: 34px; height: 34px;
  border: 1.5px solid var(--p-border-strong); border-radius: 9px;
  background: var(--p-surface-sunken); color: var(--p-text-muted);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  font-size: 13px; flex-shrink: 0;
  transition: border-color .18s, background-color .18s, color .18s;
}
.customer-gen-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); background: var(--p-accent-soft); }

/* Customer suggestions + repeat badge */
.customer-row--status { margin-top: 6px; }
.customer-suggest {
  position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 20;
  margin: 0; padding: 4px; list-style: none;
  max-height: 220px; overflow-y: auto;
  background: var(--p-surface-raised);
  border: 1px solid var(--p-border-strong);
  border-radius: 10px; box-shadow: var(--p-shadow-md);
}
.customer-suggest li {
  display: flex; flex-direction: column; gap: 2px;
  padding: 8px 10px; border-radius: 7px; cursor: pointer;
  font-size: 12.5px; font-weight: 600; color: var(--p-text-primary);
}
.customer-suggest li:hover { background: var(--p-accent-soft); }
.customer-suggest small { font-size: 11px; font-weight: 500; color: var(--p-text-faint); }
.customer-suggest-head {
  padding: 6px 10px 4px; cursor: default;
  font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
  color: var(--p-text-faint);
}
.customer-suggest-head:hover { background: none; }

/* Search box inside the repeat-buyers dropdown */
.customer-suggest li.customer-suggest-search {
  position: sticky; top: -4px; z-index: 1;
  flex-direction: row; align-items: center; gap: 8px;
  margin: 0 0 4px; padding: 4px 8px;
  cursor: default;
  background: var(--p-surface-raised);
  border: 1.5px solid var(--p-border-strong);
  border-radius: 8px;
  transition: border-color .15s;
}
.customer-suggest li.customer-suggest-search:hover { background: var(--p-surface-raised); }
.customer-suggest li.customer-suggest-search:focus-within {
  border-color: var(--p-accent);
  box-shadow: 0 0 0 3px var(--p-ring);
}
.customer-suggest-search > i { font-size: 11px; color: var(--p-text-faint); flex-shrink: 0; }
.customer-suggest-search input {
  flex: 1; min-width: 0; height: 26px;
  border: none; outline: none; background: transparent;
  font-size: 12.5px; font-family: inherit; font-weight: 500;
  color: var(--p-text-primary);
}
.customer-suggest-search input::placeholder { color: var(--p-text-faint); }
.customer-suggest-search-clear {
  border: none; background: none; padding: 2px 4px;
  color: var(--p-text-faint); cursor: pointer; font-size: 11px;
  transition: color .15s;
}
.customer-suggest-search-clear:hover { color: var(--p-text-muted); }

.customer-suggest li.customer-suggest-empty {
  cursor: default; align-items: center;
  padding: 14px 10px; font-size: 12px; font-weight: 500;
  color: var(--p-text-faint);
}
.customer-suggest li.customer-suggest-empty:hover { background: none; }

.customer-suggest-name { display: flex; align-items: center; gap: 6px; }
.customer-suggest-tag {
  font-style: normal; font-size: 9.5px; font-weight: 700;
  padding: 1px 7px; border-radius: 20px;
  background: var(--p-accent-soft); color: var(--p-accent);
  border: 1px solid var(--p-accent-border);
}
.customer-suggest-tag--plain {
  background: var(--p-surface-sunken); color: var(--p-text-muted);
  border-color: var(--p-border-strong);
}
.customer-repeat-badge {
  font-size: 10.5px; font-weight: 700; white-space: nowrap;
  padding: 2px 9px; border-radius: 20px;
  background: var(--p-accent-soft); color: var(--p-accent);
  border: 1px solid var(--p-accent-border);
}
.customer-repeat-badge--plain {
  background: var(--p-surface-sunken); color: var(--p-text-muted);
  border-color: var(--p-border-strong);
}

/* Cart items — the only scrolling area of the right panel */
.cart-items {
  flex: 1 1 0; min-height: 0; overflow-y: auto; padding: 8px 10px;
  display: flex; flex-direction: column; gap: 4px;
  scrollbar-width: thin; scrollbar-color: var(--p-border) transparent;
}
.cart-items::-webkit-scrollbar { width: 4px; }
.cart-items::-webkit-scrollbar-thumb { background: var(--p-border); border-radius: 4px; }

.cart-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 40px 0; color: var(--p-text-faint); gap: 8px; text-align: center;
}
.cart-empty i    { font-size: 32px; }
.cart-empty p    { font-size: 13.5px; font-weight: 600; margin: 0; color: var(--p-text-muted); }
.cart-empty span { font-size: 12px; }

.cart-item {
  display: flex; align-items: flex-start; gap: 8px;
  background: var(--p-surface-raised); border: 1px solid var(--p-border);
  border-radius: 10px; padding: 6px 10px; flex-shrink: 0;
  transition: border-color .15s, background-color .22s;
}
.cart-item:hover { border-color: var(--p-border-strong); }
.cart-item-body  { flex: 1; min-width: 0; }
.cart-item-name {
  font-size: 12.5px; font-weight: 600; color: var(--p-text-primary);
  margin: 0 0 3px; line-height: 1.25; white-space: normal; overflow: hidden; word-break: break-word;
  display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;
}
.cart-item-sub { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; font-size: 11.5px; color: var(--p-text-faint); min-height: 22px; }
.cart-item-sub-price { margin-right: 2px; font-variant-numeric: tabular-nums; white-space: nowrap; }
.cart-item-was { margin-left: 4px; font-size: 10.5px; color: var(--p-text-faint); opacity: .8; }
.cart-item-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.cart-item-total { font-size: 13px; font-weight: 700; color: var(--p-text-primary); font-variant-numeric: tabular-nums; }
.cart-item-qty-mini { display: flex; align-items: center; gap: 4px; }
.cart-item-qty-btn {
  width: 22px; height: 22px; border: 1px solid var(--p-border-strong); border-radius: 6px;
  background: var(--p-surface); color: var(--p-text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 9.5px;
  transition: border-color .15s, color .15s, background .15s;
}
.cart-item-qty-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); background: var(--p-accent-soft); }
.cart-item-qty-val { font-size: 12px; font-weight: 700; color: var(--p-text-primary); width: 20px; text-align: center; font-variant-numeric: tabular-nums; }
.cart-item-edit, .cart-item-del {
  width: 22px; height: 22px; border: none; border-radius: 6px;
  background: none; color: var(--p-text-faint); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 10.5px;
  transition: background .15s, color .15s;
}
.cart-item-edit:hover { background: var(--p-accent-soft); color: var(--p-accent); }
.cart-item-del:hover { background: rgba(244,63,94,.12); color: var(--p-red); }

/* Summary — compact, pinned to the bottom */
.cart-summary {
  flex-shrink: 0;
  border-top: 1px solid var(--p-border); padding: 6px 14px 12px;
  background: var(--p-surface-raised);
  transition: background-color .22s, border-color .22s;
}
.summary-line {
  display: flex; align-items: center; justify-content: space-between;
  min-height: 24px; font-size: 12px; color: var(--p-text-muted);
}
.summary-line > span:first-child { font-weight: 500; }
.summary-line > span:last-child { font-weight: 600; color: var(--p-text-secondary); font-variant-numeric: tabular-nums; }
.summary-saving { font-style: normal; font-weight: 700; font-size: 11.5px; color: var(--p-green); margin-left: 6px; }

.discount-controls { display: flex; align-items: center; gap: 6px; }
.discount-input {
  width: 56px; height: 26px;
  border: 1.5px solid var(--p-border-strong); border-radius: 7px;
  text-align: right; padding: 0 8px; font-size: 12px; font-family: inherit;
  color: var(--p-text-primary); background: var(--p-surface);
  outline: none; transition: border-color .15s, background-color .22s, color .22s;
}
.discount-input:focus { border-color: var(--p-accent); }
.discount-type { display: flex; border: 1.5px solid var(--p-border-strong); border-radius: 7px; overflow: hidden; }
.dtype-btn {
  width: 26px; height: 26px; border: none; background: none; cursor: pointer;
  font-size: 11.5px; font-weight: 600; color: var(--p-text-muted); font-family: inherit;
  transition: background .15s, color .15s;
}
.dtype-btn:first-child { border-right: 1px solid var(--p-border-strong); }
.dtype-btn--active { background: var(--p-accent-soft); color: var(--p-accent); }

.summary-toggle {
  display: flex; align-items: center; justify-content: space-between; width: 100%;
  padding: 2px 0; border: none; background: none; cursor: pointer;
  font: inherit; font-size: 12px; font-weight: 500; color: var(--p-text-faint);
  transition: color .15s;
}
.summary-toggle:hover { color: var(--p-text-secondary); }
.summary-toggle i { font-size: 9px; transition: transform .18s; }
.summary-toggle--open i { transform: rotate(180deg); }
.summary-tax { margin: 2px 0 4px; padding-left: 10px; border-left: 2px solid var(--p-border-strong); }
.summary-tax .summary-line { min-height: 22px; font-size: 12px; }

.summary-total {
  display: flex; align-items: center; justify-content: space-between;
  padding: 6px 0 8px; margin-top: 2px; border-top: 1px solid var(--p-border-strong);
  transition: border-color .22s;
}
.summary-total > span:first-child { font-size: 13px; font-weight: 600; color: var(--p-text-secondary); }
.summary-total-val { font-size: 19px; font-weight: 800; color: var(--p-text-primary); letter-spacing: -.03em; font-variant-numeric: tabular-nums; }

.checkout-error { font-size: 12px; font-weight: 600; color: var(--p-red); margin: 0 0 10px; text-align: center; }

.pay-btn {
  width: 100%; height: 40px; border: none; border-radius: 11px;
  background: linear-gradient(135deg, var(--p-accent) 0%, var(--c-accent-deep, #c2410c) 100%);
  color: #fff; font-size: 13.5px; font-weight: 700; font-family: inherit;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  box-shadow: 0 2px 8px rgba(249,115,22,.25);
  transition: opacity .18s, transform .18s, box-shadow .18s;
}
.pay-btn:hover:not(:disabled) { opacity: .94; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(249,115,22,.35); }
.pay-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--p-ring); }
/* Disabled = clearly inactive, not a dimmed orange block */
.pay-btn:disabled {
  cursor: not-allowed; transform: none; box-shadow: none;
  background: var(--p-surface-sunken); color: var(--p-text-faint);
  border: 1.5px solid var(--p-border-strong);
}

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
   Teleported to <body>: use --c-* directly with inline fallbacks. */
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
/* Wide modal for the multi-size table */
.modal--wide { max-width: 720px; }
.modal--confirm { max-width: 400px; }
.modal--service { max-width: 780px; }
.modal--adjust  { max-width: 480px; }

/* ── Adjust line modal ──
   Wide, compact, two columns, no inner scrolling on normal screens.
   NOTE: rules that must beat the generic .modal-body / .amount-input
   (declared further down) use doubled selectors. */
.modal--adjust { max-width: 880px; }
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }

.adj-head {
  display: flex; align-items: center; gap: 12px; flex-shrink: 0;
  padding: 14px 20px; border-bottom: 1px solid var(--c-border, #334155);
  background: linear-gradient(180deg, var(--c-surface-raised, #26364b) 0%, transparent 100%);
}
.adj-head-icon {
  width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 14px;
  color: var(--c-accent, #f97316); background: var(--c-accent-soft, rgba(249,115,22,.15));
  border: 1px solid var(--c-accent-border, rgba(249,115,22,.35));
}
.adj-head-text { flex: 1; min-width: 0; }
.adj-head .modal-eyebrow { font-size: 10px; margin-bottom: 1px; }
.adj-head .modal-title { font-size: 15.5px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.adj-meta { display: flex; flex-wrap: nowrap; gap: 6px; flex-shrink: 0; }
.adj-chip {
  display: inline-flex; align-items: center; gap: 6px; height: 24px; padding: 0 10px; border-radius: 20px;
  font-size: 11px; font-weight: 600; color: var(--c-text-secondary, #cbd5e1); white-space: nowrap;
  background: var(--c-surface-sunken, #0b1524); border: 1px solid var(--c-border, #334155);
  font-variant-numeric: tabular-nums;
}
.adj-chip i { font-size: 10px; color: var(--c-text-faint, #64748b); }

.modal-body.adj-body {
  flex: 0 0 auto; overflow: visible; min-height: auto;
  padding: 14px 20px 16px; gap: 12px;
}

/* Mode switch */
.adj-modes { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.adj-mode {
  display: flex; align-items: center; gap: 10px; text-align: left;
  padding: 8px 12px; border-radius: 10px; cursor: pointer; font-family: inherit;
  border: 1.5px solid var(--c-border-strong, #47566b); background: var(--c-surface, #1e293b);
  color: var(--c-text-muted, #94a3b8);
  transition: border-color .15s, background-color .15s, color .15s;
}
.adj-mode > i { font-size: 13px; }
.adj-mode-text { display: flex; flex-direction: column; gap: 0; min-width: 0; }
.adj-mode-text strong { font-size: 12.5px; font-weight: 700; color: var(--c-text-primary, #f1f5f9); }
.adj-mode-text span { font-size: 11px; line-height: 1.35; }
.adj-mode:hover { border-color: var(--c-accent-border, rgba(249,115,22,.35)); }
.adj-mode.is-active { border-color: var(--c-accent, #f97316); background: var(--c-accent-soft, rgba(249,115,22,.15)); color: var(--c-accent, #f97316); }
.adj-mode.is-active .adj-mode-text strong { color: var(--c-accent, #f97316); }
.adj-mode:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--c-accent-ring, rgba(249,115,22,.25)); }

/* Two columns */
.adj-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: 12px; align-items: start; }
.adj-col { display: flex; flex-direction: column; gap: 12px; min-width: 0; }

/* Section cards (never shrink: that is what squashed them before) */
.adj-card {
  flex-shrink: 0; border: 1px solid var(--c-border, #334155); border-radius: 12px; overflow: hidden;
  background: var(--c-surface-sunken, #0b1524);
}
.adj-card-head {
  display: flex; align-items: center; justify-content: space-between; gap: 10px; min-height: 34px;
  padding: 4px 8px 4px 12px; background: var(--c-surface-raised, #26364b);
  border-bottom: 1px solid var(--c-border, #334155);
}
.adj-card-title { margin: 0; font-size: 12px; font-weight: 700; color: var(--c-text-secondary, #cbd5e1); }
.adj-card-title label { cursor: pointer; }
.adj-card-unit { font-weight: 500; color: var(--c-text-faint, #64748b); }
.adj-card-actions { display: flex; align-items: center; gap: 2px; flex-wrap: wrap; justify-content: flex-end; }
.adj-card-head .link-btn { padding: 3px 7px; font-size: 11.5px; }
.adj-card-body { padding: 12px; display: flex; flex-direction: column; gap: 10px; }

/* Full piece / length to sell tiles */
.adj-split { display: grid; grid-template-columns: 1fr 1.2fr; gap: 10px; }
.adj-tile {
  display: flex; flex-direction: column; gap: 6px; min-width: 0;
  padding: 9px 11px; border-radius: 10px;
  background: var(--c-surface, #1e293b); border: 1.5px solid var(--c-border, #334155);
  transition: border-color .15s, box-shadow .15s;
}
.adj-tile--input { border-color: var(--c-border-strong, #47566b); }
.adj-tile--input:focus-within { border-color: var(--c-accent, #f97316); box-shadow: 0 0 0 3px var(--c-accent-ring, rgba(249,115,22,.25)); }
.adj-tile--input.is-invalid { border-color: var(--c-danger, #f43f5e); box-shadow: 0 0 0 3px rgba(244,63,94,.18); }
.adj-tile-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; min-height: 22px; }
.adj-label { font-size: 11.5px; font-weight: 600; color: var(--c-text-muted, #94a3b8); white-space: nowrap; }
.adj-tile-value {
  display: flex; align-items: baseline; gap: 5px;
  font-size: 26px; font-weight: 800; letter-spacing: -.02em; line-height: 1.1;
  color: var(--c-text-primary, #f1f5f9); font-variant-numeric: tabular-nums;
}
.adj-tile-value span { font-size: 13px; font-weight: 700; letter-spacing: 0; color: var(--c-text-faint, #64748b); }
.adj-tile-sub { margin: 0; font-size: 10.5px; line-height: 1.5; color: var(--c-text-faint, #64748b); }
.link-btn--inline { padding: 0 4px; margin-left: 2px; font-size: 10.5px; text-decoration: underline; }

.piece-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 2px 8px; border-radius: 20px; font-size: 10.5px; font-weight: 700; color: #10b981;
  background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.3); white-space: nowrap;
}
.piece-badge i { font-size: 9px; }

/* Length inputs */
.adj-input-wrap { position: relative; display: flex; align-items: center; }
.adj-input {
  width: 100%; height: 38px; padding: 0 38px 0 2px; border: none; background: transparent; outline: none;
  font: 800 24px 'Inter', system-ui, sans-serif; letter-spacing: -.02em; color: var(--c-text-primary, #f1f5f9);
  font-variant-numeric: tabular-nums; -moz-appearance: textfield; appearance: textfield;
}
.adj-input::-webkit-outer-spin-button, .adj-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.adj-input::placeholder { color: var(--c-text-faint, #64748b); font-weight: 600; font-size: 14px; letter-spacing: 0; }
.adj-input.adj-input--sm {
  height: 34px; padding: 0 34px 0 10px; font-size: 15px;
  border: 1.5px solid var(--c-border-strong, #47566b); border-radius: 8px; background: var(--c-surface-sunken, #0b1524);
  transition: border-color .15s;
}
.adj-input.adj-input--sm:focus { border-color: var(--c-accent, #f97316); }
.adj-suffix { position: absolute; right: 4px; pointer-events: none; font-size: 13px; font-weight: 700; color: var(--c-text-faint, #64748b); }
.adj-input--sm + .adj-suffix { right: 11px; font-size: 12px; }

.adj-quick { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.adj-quick-label { font-size: 11.5px; font-weight: 600; color: var(--c-text-muted, #94a3b8); }
.adj-card .svc-chip { height: 26px; padding: 0 10px; font-size: 11.5px; }

/* Sold / leftover bar */
.piece-bar {
  display: flex; height: 30px; border-radius: 8px; overflow: hidden;
  border: 1px solid var(--c-border-strong, #47566b); font-size: 11px; font-weight: 700;
}
.piece-bar-sold, .piece-bar-left {
  display: flex; align-items: center; justify-content: center;
  padding: 0 8px; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.piece-bar-sold {
  flex: none; transition: width .2s ease;
  background: var(--c-accent-soft, rgba(249,115,22,.15)); color: var(--c-accent, #f97316);
  box-shadow: inset -2px 0 0 var(--c-accent, #f97316);
}
.piece-bar-left { flex: 1; background: rgba(16,185,129,.12); color: #10b981; }
.adj-remainder {
  display: flex; align-items: flex-start; gap: 8px; padding: 8px 10px; border-radius: 8px;
  background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.25);
  font-size: 11.5px; line-height: 1.45; color: var(--c-text-secondary, #cbd5e1);
}
.adj-remainder i { margin-top: 2px; color: #10b981; }
.adj-remainder strong { color: #10b981; }

/* Pricing + summary */
.amount-input.adj-price-input { height: 42px; font-size: 18px; border-radius: 10px; padding-left: 32px; }
.adj-note { margin: 0; font-size: 11px; line-height: 1.5; color: var(--c-text-faint, #64748b); }
.adj-card-body.adj-rows { gap: 0; padding: 2px 12px; }
.adj-row {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 7px 0;
  font-size: 12px; color: var(--c-text-muted, #94a3b8); border-bottom: 1px dashed var(--c-border, #334155);
}
.adj-row:last-child { border-bottom: none; }
.adj-row > span:last-child { font-weight: 700; color: var(--c-text-primary, #f1f5f9); font-variant-numeric: tabular-nums; text-align: right; }
.adj-row.is-down > span:last-child { color: #10b981; }
.adj-row.is-up > span:last-child { color: #f59e0b; }

/* Compact footer inside this modal */
.modal--adjust .modal-footer { padding: 12px 20px 16px; }
.modal--adjust .modal-summary-total { font-size: 19px; }

/* Narrower screens: stack the columns; only here may the body scroll */
@media (max-width: 820px) {
  .modal-body.adj-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
  .adj-grid { grid-template-columns: 1fr; }
  .adj-meta { display: none; }
}
@media (max-width: 520px) {
  .adj-split, .adj-modes { grid-template-columns: 1fr; }
  .adj-head { padding: 12px 16px; }
}
/* Very short screens: allow the body to scroll rather than clip */
@media (max-height: 640px) {
  .modal-body.adj-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
}

/* Cut piece / custom price — cart line */
.cart-item-adjust {
  display: inline-flex; align-items: center; gap: 5px; height: 22px; padding: 0 9px;
  border: 1.5px solid var(--p-accent-border); border-radius: 20px; cursor: pointer;
  background: var(--p-accent-soft); color: var(--p-accent);
  font: 700 11px 'Inter', system-ui, sans-serif; white-space: nowrap;
  transition: background-color .15s, color .15s, border-color .15s, box-shadow .15s;
}
.cart-item-adjust i { font-size: 10px; }
.cart-item-adjust:hover { background: var(--p-accent); border-color: var(--p-accent); color: #fff; }
.cart-item-adjust:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--p-ring); }
.cart-item-adjust--on { background: var(--p-accent); border-color: var(--p-accent); color: #fff; }
.cart-item-adjust--on:hover { opacity: .92; }

/* Extra service — modal */
.svc-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.svc-chip {
  height: 28px; padding: 0 11px; border-radius: 20px; cursor: pointer;
  border: 1.5px solid var(--c-border-strong, #47566b); background: var(--c-surface, #1e293b);
  color: var(--c-text-secondary, #cbd5e1); font: 600 12px 'Inter', system-ui, sans-serif;
  transition: border-color .15s, color .15s, background-color .15s;
}
.svc-chip:hover, .svc-chip--on { border-color: var(--c-accent-border, rgba(249,115,22,.35)); color: var(--c-accent, #f97316); background: var(--c-accent-soft, rgba(249,115,22,.15)); }
.svc-chip:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--c-accent-ring, rgba(249,115,22,.25)); }
.svc-seg { display: inline-flex; border: 1.5px solid var(--c-border-strong, #47566b); border-radius: 8px; overflow: hidden; }
.svc-seg button {
  width: 36px; height: 28px; border: none; background: none; cursor: pointer;
  font: 700 12px 'Inter', system-ui, sans-serif; color: var(--c-text-muted, #94a3b8);
  transition: background-color .15s, color .15s;
}
.svc-seg button + button { border-left: 1px solid var(--c-border-strong, #47566b); }
.svc-seg button.is-active { background: var(--c-accent-soft, rgba(249,115,22,.15)); color: var(--c-accent, #f97316); }
.svc-share-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.svc-share-input {
  width: 92px; height: 36px; border-radius: 8px; padding: 0 10px; text-align: right;
  border: 1.5px solid var(--c-border-strong, #47566b); background: var(--c-surface-sunken, #0b1524);
  color: var(--c-text-primary, #f1f5f9); font: 700 14px 'Inter', system-ui, sans-serif; outline: none;
  transition: border-color .15s;
}
.svc-share-input:focus { border-color: var(--c-accent, #f97316); }
.svc-share-input.has-error { border-color: var(--c-danger, #f43f5e); box-shadow: 0 0 0 3px rgba(244,63,94,.15); }
.req { color: var(--c-danger, #f43f5e); font-weight: 700; }
.cut-field.has-error input { border-color: var(--c-danger, #f43f5e); box-shadow: 0 0 0 3px rgba(244,63,94,.15); }
.svc-field-error { margin: 0; display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; color: var(--c-danger, #f43f5e); }
.svc-alert {
  display: flex; align-items: center; gap: 10px; flex-shrink: 0;
  margin: 0 22px 2px; padding: 10px 12px; border-radius: 10px;
  background: rgba(244,63,94,.10); border: 1px solid rgba(244,63,94,.40);
  color: var(--c-danger, #f43f5e); font-size: 12.5px; font-weight: 600; line-height: 1.4;
  animation: svc-alert-in .25s ease;
}
.svc-alert i { font-size: 14px; flex-shrink: 0; }
@keyframes svc-alert-in { 0% { opacity: 0; transform: translateY(4px); } 60% { transform: translateX(-3px); } 80% { transform: translateX(3px); } 100% { opacity: 1; transform: none; } }
.svc-owner-val { color: #10b981; font-weight: 800; }

/* Extra service modal: fixed layout (no inner scrolling on normal screens).
   Doubled selector so it beats the generic .modal-body declared further down. */
.modal-body.svc-body { flex: 0 0 auto; overflow: visible; min-height: auto; padding: 16px 22px 14px; gap: 14px; }
.svc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
.svc-col { display: flex; flex-direction: column; gap: 12px; min-width: 0; }
.svc-sec-title { margin: 0; padding-bottom: 6px; font-size: 12px; font-weight: 700; color: var(--c-text-secondary, #cbd5e1); border-bottom: 1px solid var(--c-border, #334155); }
.svc-share { display: flex; flex-direction: column; gap: 8px; }
.svc-body .svc-note { margin: 0; }
.svc-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.svc-stat {
  display: flex; flex-direction: column; gap: 2px; min-width: 0; padding: 9px 12px; border-radius: 10px;
  background-color: var(--c-surface-raised, #26364b); border: 1px solid var(--c-border, #334155);
}
.svc-stat-label { font-size: 11px; font-weight: 600; color: var(--c-text-muted, #94a3b8); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.svc-stat-value { font-size: 17px; font-weight: 800; letter-spacing: -.01em; color: var(--c-text-primary, #f1f5f9); font-variant-numeric: tabular-nums; }
.svc-stat--shop { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.30); }
.svc-stat--shop .svc-stat-value { color: #10b981; }
@media (max-width: 760px) {
  .modal-body.svc-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
  .svc-grid { grid-template-columns: 1fr; gap: 14px; }
}
@media (max-width: 520px) { .svc-stats { grid-template-columns: 1fr; } }
@media (max-height: 600px) { .modal-body.svc-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; } }
.svc-note { margin: -8px 0 0; font-size: 11.5px; line-height: 1.5; color: var(--c-text-faint, #64748b); }

/* Extra service — cart panel */
.cart-service-bar { flex-shrink: 0; padding: 0 12px 10px; }
.cart-service-btn {
  width: 100%; height: 36px; display: flex; align-items: center; justify-content: center; gap: 8px;
  border: 1.5px dashed var(--p-border-strong); border-radius: 10px; background: none; cursor: pointer;
  color: var(--p-text-muted); font: 600 12.5px 'Inter', system-ui, sans-serif;
  transition: border-color .15s, color .15s, background-color .15s;
}
.cart-service-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); background: var(--p-accent-soft); }
.cart-service-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--p-ring); }
.cart-item--service { border-left: 3px solid var(--p-accent); }
.svc-line-icon { margin-right: 6px; font-size: 10.5px; color: var(--p-accent); }

/* Clear-cart confirmation */
.confirm-body { padding: 28px 24px 8px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px; }
.confirm-icon {
  width: 52px; height: 52px; border-radius: 50%;
  background: rgba(244,63,94,.14); color: #f43f5e; border: 1px solid rgba(244,63,94,.3);
  display: flex; align-items: center; justify-content: center; font-size: 20px;
}
.confirm-title { margin: 4px 0 0; font-size: 17px; font-weight: 800; color: var(--c-text-primary, #f1f5f9); letter-spacing: -.01em; }
.confirm-text  { margin: 0; font-size: 13px; line-height: 1.55; color: var(--c-text-muted, #94a3b8); max-width: 320px; }
.confirm-footer { border-top: none; padding-top: 18px; }
.confirm-footer .modal-cancel, .confirm-footer .modal-confirm { flex: 1; }
.modal-confirm--danger {
  background: linear-gradient(135deg, #f43f5e, #be123c);
  box-shadow: 0 4px 14px rgba(244,63,94,.35);
}
.modal-confirm--danger:hover:not(:disabled) { box-shadow: 0 6px 20px rgba(244,63,94,.45); }
.modal-cancel:focus-visible, .modal-confirm:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--c-accent-ring, rgba(249,115,22,.25)); }

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

/* Header: thumbnail + title */
.modal-head-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
.modal-thumb {
  width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0; overflow: hidden;
  background: var(--c-surface-sunken, #0b1524); border: 1px solid var(--c-border, #334155);
  display: flex; align-items: center; justify-content: center;
  color: var(--c-text-faint, #64748b); font-size: 16px;
}
.modal-thumb img { width: 100%; height: 100%; object-fit: contain; }

.size-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.link-btn {
  border: none; background: none; cursor: pointer; padding: 4px 8px; border-radius: 6px;
  font: 600 12px 'Inter', system-ui, sans-serif; color: var(--c-text-muted, #94a3b8);
  transition: color .15s, background-color .15s;
}
.link-btn:hover { color: var(--c-accent, #f97316); background: var(--c-accent-soft, rgba(249,115,22,.15)); }
.link-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--c-accent-ring, rgba(249,115,22,.25)); }

/* "Find a size" box for long size lists */
.variant-search {
  display: flex; align-items: center; gap: 8px;
  height: 36px; padding: 0 12px;
  border: 1.5px solid var(--c-border-strong, #47566b); border-radius: 9px;
  background: var(--c-surface-sunken, #0b1524);
  transition: border-color .15s;
}
.variant-search:focus-within { border-color: var(--c-accent, #f97316); }
.variant-search i { font-size: 11px; color: var(--c-text-faint, #64748b); }
.variant-search input {
  flex: 1; min-width: 0; border: none; outline: none; background: transparent;
  font: 500 13px 'Inter', system-ui, sans-serif; color: var(--c-text-primary, #f1f5f9);
}
.variant-search input::placeholder { color: var(--c-text-faint, #64748b); }
.variant-search button { border: none; background: none; cursor: pointer; color: var(--c-text-faint, #64748b); font-size: 11px; padding: 2px; }
.variant-none { margin: 8px 0; text-align: center; font-size: 12.5px; color: var(--c-text-faint, #64748b); }

/* Size table */
.vtable {
  border: 1px solid var(--c-border, #334155); border-radius: 12px; overflow: hidden;
  background: var(--c-surface-sunken, #0b1524);
}
.vhead, .vrow {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 170px 124px 92px;
  grid-template-areas: "main stock step amt";
  align-items: center; column-gap: 12px; padding: 0 14px;
}
.vhead {
  height: 34px; background: var(--c-surface-raised, #26364b);
  border-bottom: 1px solid var(--c-border, #334155);
  font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  color: var(--c-text-faint, #64748b);
}
.vhead > :nth-child(3) { text-align: center; }
.vhead > :nth-child(4) { text-align: right; }

.vrow {
  min-height: 58px; padding-top: 8px; padding-bottom: 8px;
  border-bottom: 1px solid var(--c-border, #334155);
  transition: background-color .15s, box-shadow .15s;
}
.vrow:last-of-type { border-bottom: none; }
.vrow--active { background: var(--c-accent-soft, rgba(249,115,22,.15)); box-shadow: inset 3px 0 0 var(--c-accent, #f97316); }
.vrow--off .vrow-main, .vrow--off .vrow-amt { opacity: .5; }

.vrow-main { grid-area: main; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.vrow-label {
  font-size: 13.5px; font-weight: 700; color: var(--c-text-primary, #f1f5f9);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.vrow-price { font-size: 12px; font-weight: 700; color: var(--c-accent, #f97316); font-variant-numeric: tabular-nums; }

.vrow-stock {
  grid-area: stock; justify-self: start;
  font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px; white-space: nowrap;
  background: rgba(16,185,129,.14); color: #10b981;
}
.vrow--low .vrow-stock { background: rgba(245,158,11,.16); color: #f59e0b; }
.vrow--oos .vrow-stock { background: rgba(244,63,94,.14);  color: #f43f5e; }

.vstep { grid-area: step; display: flex; align-items: center; justify-content: center; gap: 4px; }
.vstep-btn {
  width: 28px; height: 28px; border-radius: 7px; cursor: pointer; flex-shrink: 0;
  border: 1.5px solid var(--c-border-strong, #47566b);
  background: var(--c-surface, #1e293b); color: var(--c-text-secondary, #cbd5e1);
  display: flex; align-items: center; justify-content: center; font-size: 10px;
  transition: border-color .15s, color .15s, background-color .15s;
}
.vstep-btn:hover:not(:disabled) { border-color: var(--c-accent-border, rgba(249,115,22,.35)); color: var(--c-accent, #f97316); background: var(--c-accent-soft, rgba(249,115,22,.15)); }
.vstep-btn:disabled { opacity: .35; cursor: not-allowed; }
.vstep-input {
  width: 48px; height: 28px; border-radius: 7px; text-align: center;
  border: 1.5px solid var(--c-border-strong, #47566b);
  background: var(--c-surface, #1e293b); color: var(--c-text-primary, #f1f5f9);
  font: 700 13px 'Inter', system-ui, sans-serif; outline: none;
  -moz-appearance: textfield; appearance: textfield;
  transition: border-color .15s;
}
.vstep-input::-webkit-outer-spin-button, .vstep-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.vstep-input:focus { border-color: var(--c-accent, #f97316); }
.vstep-input:disabled { opacity: .4; cursor: not-allowed; }
.vstep-input::placeholder { color: var(--c-text-faint, #64748b); }

.vrow-amt {
  grid-area: amt; text-align: right;
  font-size: 13px; font-weight: 800; color: var(--c-text-primary, #f1f5f9); font-variant-numeric: tabular-nums;
}
.vrow:not(.vrow--active) .vrow-amt { color: var(--c-text-faint, #64748b); font-weight: 600; }

/* Footer: running summary + actions */
.modal-footer--summary { align-items: center; }
.modal-summary { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.modal-summary-meta { font-size: 11.5px; font-weight: 600; color: var(--c-text-muted, #94a3b8); }
.modal-summary-total { font-size: 20px; font-weight: 800; letter-spacing: -.02em; color: var(--c-accent, #f97316); font-variant-numeric: tabular-nums; }
.modal-cancel--fit  { flex: 0 0 auto; padding: 0 20px; }
.modal-confirm--fit { flex: 0 0 auto; padding: 0 22px; min-width: 160px; }

.modal-section { display: flex; flex-direction: column; gap: 10px; }
.modal-section-label { font-size: 13px; font-weight: 700; color: var(--c-text-secondary, #cbd5e1); transition: color .22s; display: flex; align-items: center; flex-wrap: wrap; }
.modal-section-hint  { font-weight: 400; color: var(--c-text-faint, #64748b); font-size: 11.5px; margin-left: 6px; }
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

@media (prefers-reduced-motion: reduce) {
  .sk, .scanner-line, .svc-alert { animation: none; }
  .piece-bar-sold { transition: none; }
  .modal-fade-enter-active, .modal-fade-leave-active,
  .scanner-slide-enter-active, .scanner-slide-leave-active,
  .fade-enter-active, .fade-leave-active { transition: none; }
}

/* ═══ RESPONSIVE — TABLET (≤1024px) ═══ */
@media (max-width: 1024px) {
  .pos-root { margin: -22px; }
  .pos-right { width: 320px; min-width: 320px; }
  .product-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
}

/* ═══ RESPONSIVE — MOBILE (≤768px) ═══ */
@media (max-width: 768px) {
  .pos-root {
    flex-direction: column;
    height: auto;
    min-height: 0;
    overflow: visible;
    margin: -16px;
  }

  .pos-left {
    overflow: visible;
    padding: 14px 14px 92px;
  }

  .product-grid {
    overflow-y: visible;
    grid-template-columns: repeat(auto-fill, minmax(112px, 1fr));
  }

  .search-bar { flex-wrap: wrap; row-gap: 8px; }
  .search-field { flex: 1 1 100%; }
  .search-kbd { display: none; }
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
  /* drawer height is content-driven here, so the list needs a floor */
  .cart-items { flex: 1 1 auto; min-height: 220px; }
}

/* Narrow screens: each size becomes a two-line card */
@media (max-width: 640px) {
  .vhead { display: none; }
  .vrow {
    grid-template-columns: minmax(0, 1fr) auto;
    grid-template-areas: "main step" "stock amt";
    row-gap: 8px; padding: 10px 12px;
  }
  .modal-footer--summary { flex-wrap: wrap; }
  .modal-summary { flex: 1 1 100%; flex-direction: row; align-items: baseline; justify-content: space-between; }
  .modal-cancel--fit, .modal-confirm--fit { flex: 1; }
}

@media (max-width: 420px) {
  .modal-header { padding: 16px 18px; }
  .modal-body   { padding: 16px 18px; }
  .modal-footer { padding: 12px 18px 18px; }
  .cut-grid { grid-template-columns: 1fr; }
  .piece-card-value { font-size: 21px; }
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