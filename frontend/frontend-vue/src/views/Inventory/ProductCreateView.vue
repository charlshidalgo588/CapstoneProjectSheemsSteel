<template>
  <Layout>
    <div class="prod-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Inventory</p>
          <h1 class="page-title">Add New Item</h1>
          <p class="page-sub">Fill in the details, add at least one size, then save. Press <kbd class="kbd-hint">Ctrl</kbd> + <kbd class="kbd-hint">S</kbd> any time.</p>
        </div>
        <RouterLink to="/products" class="btn btn--ghost">
          <i class="fa-solid fa-arrow-left"></i> Back to List
        </RouterLink>
      </div>

      <!-- ── FLOATING TOASTS (always visible, even when scrolled) ── -->
      <div class="toast-stack" aria-live="polite">
        <transition name="alert-fade">
          <div v-if="errorMessage" class="alert alert--error" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ errorMessage }}</span>
            <button type="button" class="alert-close" @click="errorMessage = ''" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>
          </div>
        </transition>
        <transition name="alert-fade">
          <div v-if="successMessage" class="alert alert--success" role="status">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ successMessage }}</span>
          </div>
        </transition>
      </div>

      <!-- Wide screens: main column (left) + sticky side column (right).
           Narrow screens: both columns dissolve into one list, ordered by the .o-* classes. -->
      <form ref="formEl" class="form-layout" @submit.prevent="saveProduct" @keydown.enter="blockEnterSubmit">

        <div class="col-main">

          <!-- ══ BASIC INFORMATION ══ -->
          <div class="form-card o-basic">
            <div class="form-card-header">
              <div class="form-card-icon form-card-icon--blue">
                <i class="fa-solid fa-tag"></i>
              </div>
              <div>
                <h2 class="form-card-title">Basic Information</h2>
                <p class="form-card-sub">Name, SKU, barcode, unit and return policy.</p>
              </div>
            </div>
            <div class="form-card-body">
              <div class="form-grid-2">
                <div class="form-field">
                  <label class="form-label" for="c-name">Product Name <span class="req">*</span></label>
                  <div class="input-wrap">
                    <i class="fa-solid fa-cube input-icon"></i>
                    <input
                      id="c-name"
                      v-model="form.ProductName"
                      type="text"
                      class="form-input"
                      placeholder="e.g. Corrugated Steel Sheet"
                      required
                      @blur="generateSku"
                    />
                  </div>
                </div>

                <div class="form-field">
                  <label class="form-label" for="c-sku">SKU</label>
                  <div class="input-wrap">
                    <i class="fa-solid fa-barcode input-icon"></i>
                    <input id="c-sku" :value="form.SKU" disabled class="form-input form-input--disabled" placeholder="Auto-generated" />
                  </div>
                  <p class="form-hint">Generated automatically from the product name.</p>
                </div>

                <!-- ══ BARCODE ══ -->
                <div class="form-field">
                  <label class="form-label" for="c-barcode">Barcode</label>
                  <div class="input-wrap barcode-input-wrap">
                    <i class="fa-solid fa-barcode input-icon"></i>
                    <input
                      id="c-barcode"
                      v-model="form.Barcode"
                      type="text"
                      class="form-input barcode-input"
                      placeholder="Scan or type manually"
                    />
                    <button
                      type="button"
                      class="barcode-scan-btn"
                      @click="openBarcodeScanner"
                      title="Scan Barcode"
                      aria-label="Scan barcode with camera or USB scanner"
                    >
                      <i class="fa-solid fa-camera"></i>
                    </button>
                  </div>
                  <p v-if="checkingBarcode" class="form-hint">
                    <i class="fa-solid fa-spinner fa-spin"></i> Checking barcode…
                  </p>
                  <p v-else-if="barcodeDuplicate" class="form-hint form-hint--error">
                    <i class="fa-solid fa-triangle-exclamation"></i> This barcode is already in use by another product.
                  </p>
                </div>

                <div class="form-field">
                  <label class="form-label" for="c-unit">Unit <span class="req">*</span></label>
                  <div class="select-wrap">
                    <i class="fa-solid fa-scale-balanced input-icon"></i>
                    <select id="c-unit" v-model="form.Unit" class="form-select" required>
                      <option value="">Select Unit</option>
                      <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                    </select>
                    <i class="fa-solid fa-chevron-down select-caret"></i>
                  </div>
                </div>
              </div>

              <div class="toggle-row toggle-row--card" @click="form.IsReturnable = !form.IsReturnable">
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
          </div>

          <!-- ══ PRODUCT DETAILS ══ -->
          <div class="form-card o-details">
            <div class="form-card-header">
              <div class="form-card-icon form-card-icon--green">
                <i class="fa-solid fa-circle-info"></i>
              </div>
              <div>
                <h2 class="form-card-title">Product Details</h2>
                <p class="form-card-sub">Brand, description, material and finish.</p>
              </div>
            </div>
            <div class="form-card-body">
              <div class="form-grid-2">
                <div class="form-field">
                  <label class="form-label" for="c-brand">Brand</label>
                  <div class="input-wrap">
                    <i class="fa-solid fa-registered input-icon"></i>
                    <input id="c-brand" v-model="form.Brand" type="text" class="form-input" placeholder="e.g. Tiger Steel" />
                  </div>
                </div>
                <div class="form-field">
                  <label class="form-label" for="c-desc">Description</label>
                  <textarea id="c-desc" v-model="form.Description" class="form-textarea" rows="3" placeholder="Brief product description…"></textarea>
                </div>
              </div>

              <div class="form-grid-3 details-extra-row">
                <div class="form-field">
                  <label class="form-label" for="c-mat">Material</label>
                  <input id="c-mat" v-model="form.Material" type="text" class="form-input form-input--plain" placeholder="e.g. Galvanized Steel" />
                </div>
                <div class="form-field">
                  <label class="form-label" for="c-prof">Profile / Type</label>
                  <input id="c-prof" v-model="form.ProfileType" type="text" class="form-input form-input--plain" placeholder="e.g. Corrugated" />
                </div>
                <div class="form-field">
                  <label class="form-label" for="c-color">Color</label>
                  <input id="c-color" v-model="form.Color" type="text" class="form-input form-input--plain" placeholder="e.g. Red Oxide" />
                </div>
              </div>
            </div>
          </div>

          <!-- ══ SIZES, PRICES & STOCK ══ -->
          <div class="form-card o-sizes">
            <div class="form-card-header">
              <div class="form-card-icon form-card-icon--amber">
                <i class="fa-solid fa-ruler-combined"></i>
              </div>
              <div>
                <h2 class="form-card-title">Sizes, Prices &amp; Stock</h2>
                <p class="form-card-sub">Each size has its own specs, price and stock. POS shows these as a dropdown.</p>
              </div>
              <div class="form-card-tools">
                <button v-if="variants.length > 1" type="button" class="mini-btn" @click="toggleAllVariants">
                  <i class="fa-solid" :class="allCollapsed ? 'fa-expand' : 'fa-compress'"></i>
                  {{ allCollapsed ? 'Expand all' : 'Collapse all' }}
                </button>
                <span class="size-total-pill">{{ variants.length }} size{{ variants.length === 1 ? '' : 's' }}</span>
              </div>
            </div>
            <div class="form-card-body">
              <div class="variant-list">
                <div
                  v-for="(v, vi) in variants"
                  :id="`variant-${v.uid}`"
                  :key="v.uid"
                  class="variant-card"
                  :class="{ 'variant-card--collapsed': v.collapsed }"
                >
                  <div class="variant-head">
                    <button
                      type="button"
                      class="variant-toggle"
                      :aria-expanded="!v.collapsed"
                      :aria-label="v.collapsed ? 'Expand this size' : 'Collapse this size'"
                      @click="v.collapsed = !v.collapsed"
                    >
                      <i class="fa-solid fa-chevron-down" :class="{ 'is-collapsed': v.collapsed }"></i>
                    </button>
                    <span class="variant-badge">Size {{ vi + 1 }}</span>
                    <span class="variant-label" @click="v.collapsed = !v.collapsed">{{ variantLabel(v) }}</span>
                    <span v-if="v.collapsed" class="variant-mini">
                      ₱{{ money(v.SellingPrice) }} · {{ Number(v.OpeningStock) || 0 }} in stock
                    </span>
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

                  <div v-show="!v.collapsed" class="variant-body">
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

                    <!-- Prices -->
                    <div class="variant-section variant-prices-wrap">
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

                    <!-- Stock (per size) -->
                    <div class="form-grid-2 variant-section">
                      <div class="form-field">
                        <label class="form-label">Opening Stock</label>
                        <input
                          v-model="v.OpeningStock"
                          type="number"
                          min="0"
                          step="1"
                          class="form-input form-input--plain"
                          placeholder="0"
                        />
                      </div>
                      <div class="form-field">
                        <label class="form-label">Reorder Level</label>
                        <input
                          v-model="v.ReorderLevel"
                          type="number"
                          min="0"
                          step="1"
                          class="form-input form-input--plain"
                          placeholder="0"
                        />
                        <p class="form-hint">Alert when this size runs low.</p>
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
        </div>

        <div class="col-side">

          <!-- ══ PRODUCT IMAGE ══ -->
          <div class="form-card o-image">
            <div class="form-card-header">
              <div class="form-card-icon form-card-icon--rose">
                <i class="fa-solid fa-image"></i>
              </div>
              <div>
                <h2 class="form-card-title">Product Image</h2>
                <p class="form-card-sub">Shown in the list and at the POS.</p>
              </div>
            </div>
            <div class="form-card-body">
              <div
                class="upload-zone"
                :class="{ 'upload-zone--has-image': preview, 'upload-zone--drag': dragging }"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
              >
                <img v-if="preview" :src="preview" class="upload-preview" alt="Product image preview" />
                <div v-else class="upload-placeholder">
                  <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                  <p class="upload-text">Click to upload or drag here</p>
                  <p class="upload-hint">PNG / JPG · Max 5 MB</p>
                </div>
                <span v-if="preview" class="upload-change"><i class="fa-solid fa-camera"></i> Change</span>
                <input ref="fileInput" type="file" accept="image/*" class="upload-input" aria-label="Upload product image" @change="uploadImage" />
              </div>
              <button v-if="preview" type="button" class="upload-remove" @click="removeImage">
                <i class="fa-solid fa-xmark"></i> Remove image
              </button>
            </div>
          </div>

          <!-- ══ CLASSIFICATION ══ -->
          <div class="form-card o-class">
            <div class="form-card-header">
              <div class="form-card-icon form-card-icon--violet">
                <i class="fa-solid fa-layer-group"></i>
              </div>
              <div>
                <h2 class="form-card-title">Classification</h2>
                <p class="form-card-sub">Category and supplier.</p>
              </div>
            </div>
            <div class="form-card-body form-stack">
              <div class="form-field">
                <label class="form-label" for="c-cat">Category <span class="req">*</span></label>
                <div class="select-wrap">
                  <i class="fa-solid fa-folder input-icon"></i>
                  <select id="c-cat" v-model="form.CategoryID" class="form-select" required>
                    <option value="">Select Category</option>
                    <option v-for="c in categories" :key="c.CategoryID" :value="c.CategoryID">{{ c.CategoryName }}</option>
                  </select>
                  <i class="fa-solid fa-chevron-down select-caret"></i>
                </div>
              </div>
              <div class="form-field">
                <label class="form-label" for="c-sup">Supplier</label>
                <div class="select-wrap">
                  <i class="fa-solid fa-truck-field input-icon"></i>
                  <select id="c-sup" v-model="form.SupplierID" class="form-select">
                    <option value="">Select Supplier</option>
                    <option v-for="s in suppliers" :key="s.SupplierID" :value="s.SupplierID">{{ s.SupplierName }}</option>
                  </select>
                  <i class="fa-solid fa-chevron-down select-caret"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- ══ READY TO SAVE + INVENTORY SUMMARY ══ -->
          <div class="form-card o-summary">
            <div class="form-card-header">
              <div class="form-card-icon form-card-icon--teal">
                <i class="fa-solid fa-clipboard-check"></i>
              </div>
              <div>
                <h2 class="form-card-title">Ready to save?</h2>
                <p class="form-card-sub">Required details and stock totals.</p>
              </div>
            </div>
            <div class="form-card-body form-stack">
              <div class="ready">
                <div class="ready-head">
                  <span>{{ doneCount }} of {{ checklist.length }} required done</span>
                  <span>{{ readyPct }}%</span>
                </div>
                <div class="ready-bar" :class="{ 'ready-bar--full': readyPct === 100 }"><span :style="{ width: readyPct + '%' }"></span></div>
              </div>
              <ul class="checklist">
                <li v-for="c in checklist" :key="c.key" :class="{ 'is-done': c.done }">
                  <span class="check-dot"><i class="fa-solid fa-check"></i></span>
                  {{ c.label }}
                </li>
              </ul>

              <div class="summary-grid">
                <div class="summary-tile">
                  <span class="summary-label">Sizes</span>
                  <span class="summary-value">{{ variants.length }}</span>
                </div>
                <div class="summary-tile">
                  <span class="summary-label">Opening stock</span>
                  <span class="summary-value">{{ totalOpeningStock }} <small>units</small></span>
                </div>
                <div class="summary-tile">
                  <span class="summary-label">Value at cost</span>
                  <span class="summary-value">₱{{ money(stockCostValue) }}</span>
                </div>
                <div class="summary-tile">
                  <span class="summary-label">Value at selling</span>
                  <span class="summary-value">₱{{ money(stockRetailValue) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ══ FLOATING SAVE BAR (sticks to the bottom of the screen) ══ -->
        <div class="save-bar" :class="{ 'save-bar--dirty': dirty }">
          <div class="save-bar-status">
            <span class="save-dot" :class="{ 'save-dot--dirty': dirty }"></span>
            <div class="save-bar-text">
              <strong>{{ dirty ? 'Unsaved changes' : 'New product' }}</strong>
              <span class="save-bar-meta">{{ variants.length }} size{{ variants.length === 1 ? '' : 's' }} · {{ totalOpeningStock }} units opening stock · {{ doneCount }}/{{ checklist.length }} required done</span>
            </div>
          </div>
          <div class="save-bar-actions">
            <RouterLink to="/products" class="btn btn--ghost">
              <i class="fa-solid fa-xmark"></i> <span class="btn-text">Cancel</span>
            </RouterLink>
            <button type="submit" class="btn btn--primary" :disabled="saving" title="Save (Ctrl+S)">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              {{ saving ? 'Saving…' : 'Save Product' }}
              <kbd class="kbd">Ctrl S</kbd>
            </button>
          </div>
        </div>

      </form>

    </div>

    <!-- ══ BARCODE SCANNER MODAL (same USB + Camera pipeline/UX as POS.vue) ══ -->
    <transition name="alert-fade">
      <div v-if="showBarcodeScanner" class="scan-modal-backdrop" @click.self="closeBarcodeScanner" @keydown.esc="closeBarcodeScanner">
        <div class="scan-modal">
          <div class="scan-modal-header">
            <h3>Scan Barcode</h3>
            <button class="scan-modal-close" @click="closeBarcodeScanner" aria-label="Close scanner">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <div class="scan-modal-body">

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

            <template v-if="scanMode === 'usb'">
              <div
                class="usb-status"
                :class="{ 'usb-status--active': usbFocused, 'usb-status--receiving': usbReceiving }"
              >
                <span class="usb-status-dot"></span>
                <span class="usb-status-text">
                  {{ usbReceiving ? 'Receiving scan…' : usbFocused ? 'Ready — listening for scanner input' : 'Click the field below to activate' }}
                </span>
              </div>
              <input
                ref="barcodeInput"
                v-model="barcodeBuffer"
                @keydown="onBarcodeKey"
                @focus="usbFocused = true"
                @blur="usbFocused = false"
                class="barcode-hidden-input"
                placeholder="Scan or type barcode…"
                autofocus
              />
              <p v-if="barcodeBuffer" class="barcode-preview">
                <i class="fa-solid fa-barcode"></i> {{ barcodeBuffer }}
              </p>
              <transition name="feedback-fade" mode="out-in">
                <p v-if="scanFeedback" :key="scanFeedback.text" class="form-hint" :class="`form-hint--${scanFeedback.type}`">
                  <i class="fa-solid" :class="scanFeedback.type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
                  {{ scanFeedback.text }}
                </p>
                <p v-else class="form-hint">Waiting for scan…</p>
              </transition>
            </template>

            <template v-else>
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

              <p v-if="cameraStarting" class="form-hint"><i class="fa-solid fa-spinner fa-spin"></i> Starting camera…</p>
              <p v-else-if="cameraError" class="form-hint form-hint--error"><i class="fa-solid fa-triangle-exclamation"></i> {{ cameraError }}</p>
              <template v-else>
                <transition name="feedback-fade" mode="out-in">
                  <p v-if="scanFeedback" :key="scanFeedback.text" class="form-hint" :class="`form-hint--${scanFeedback.type}`">
                    <i class="fa-solid" :class="scanFeedback.type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
                    {{ scanFeedback.text }}
                  </p>
                  <p v-else class="form-hint">Point camera at a barcode…</p>
                </transition>
                <p v-if="cameraHint" class="form-hint form-hint--tip">
                  <i class="fa-solid fa-lightbulb"></i> {{ cameraHint }}
                </p>
              </template>
            </template>

          </div>
        </div>
      </div>
    </transition>

    <!-- ══ UNSAVED CHANGES CONFIRMATION ══
         Same look as the POS.vue "Clear this sale?" modal. Teleported to
         <body>, so its styles use --c-* tokens with inline fallbacks. -->
    <Teleport to="body">
    <transition name="modal-fade">
      <div v-if="showLeaveModal" class="modal-backdrop" @click.self="stayOnPage" @keydown.esc="stayOnPage">
        <div class="modal modal--confirm" role="alertdialog" aria-modal="true" aria-labelledby="leave-title" aria-describedby="leave-desc">
          <div class="confirm-body">
            <div class="confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h3 id="leave-title" class="confirm-title">Unsaved changes</h3>
            <p id="leave-desc" class="confirm-text">
              You have unsaved changes. Are you sure you want to leave?
            </p>
          </div>
          <div class="modal-footer confirm-footer">
            <button ref="stayBtn" type="button" class="modal-cancel" @click="stayOnPage">Stay</button>
            <button type="button" class="modal-confirm modal-confirm--danger" @click="confirmLeave">
              <i class="fa-solid fa-right-from-bracket"></i> Leave
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
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRouter, onBeforeRouteLeave } from 'vue-router'
// Camera barcode scanning — same library/pipeline already used in POS.vue.
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode'

const router = useRouter()

const formEl    = ref(null)
const fileInput = ref(null)

// NOTE: OpeningStock and ReorderLevel are no longer product-level fields.
// They now live on each size (see `variants` below).
const form = ref({
  ProductName: '', SKU: '', Barcode: '', Unit: '', IsReturnable: false,
  CategoryID: '', SupplierID: '', Brand: '', Description: '',
  Material: '', ProfileType: '', Color: '',
})

/* ────────────────────────────────────────────────────────────
   SIZES, PRICES & STOCK (variants)
   Each variant = a list of dynamic specs (type + value + unit)
   plus its own selling price, cost price, opening stock and
   reorder level. The label (e.g. "8 ft × 3 ft × 0.40 mm") is
   what POS shows in its dropdown.
   `collapsed` is only a screen state (folds the card), it is
   never saved and never counts as an unsaved change.
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
  uid: nextUid(),
  specs: [],
  SellingPrice: '',
  CostPrice: '',
  OpeningStock: '',
  ReorderLevel: '',
  collapsed: false,
})

const variants = ref([newVariant()])

// Sum of every size's opening stock — shown in the summary card + save bar.
const totalOpeningStock = computed(() =>
  variants.value.reduce((sum, v) => sum + (Number(v.OpeningStock) || 0), 0)
)
const stockCostValue = computed(() =>
  variants.value.reduce((sum, v) => sum + (Number(v.OpeningStock) || 0) * (Number(v.CostPrice) || 0), 0)
)
const stockRetailValue = computed(() =>
  variants.value.reduce((sum, v) => sum + (Number(v.OpeningStock) || 0) * (Number(v.SellingPrice) || 0), 0)
)
const money = n => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

/* "Ready to save?" checklist: the same required fields the form asks for. */
const sizesPriced = computed(() =>
  variants.value.length > 0 &&
  variants.value.every(v => Number(v.SellingPrice) > 0 && v.CostPrice !== '' && v.CostPrice !== null && Number(v.CostPrice) >= 0)
)
const checklist = computed(() => [
  { key: 'name',  label: 'Product name',                       done: !!String(form.value.ProductName).trim() },
  { key: 'unit',  label: 'Unit',                               done: !!form.value.Unit },
  { key: 'cat',   label: 'Category',                           done: !!form.value.CategoryID },
  { key: 'price', label: 'Selling & cost price on every size', done: sizesPriced.value },
])
const doneCount = computed(() => checklist.value.filter(c => c.done).length)
const readyPct  = computed(() => Math.round((doneCount.value / checklist.value.length) * 100))

/** Profit per unit + margin, shown under the price fields. */
function marginInfo(v) {
  const sell = Number(v.SellingPrice)
  if (v.SellingPrice === '' || v.SellingPrice === null || !(sell > 0)) return null
  if (v.CostPrice === '' || v.CostPrice === null) return null
  const diff = sell - Number(v.CostPrice)
  return {
    loss: diff < 0,
    amount: money(Math.abs(diff)),
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
/** Copies specs + prices into a new size (then just change the specs). */
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
function removeVariant(i)    { variants.value.splice(i, 1) }
function addSpec(v)          { v.specs.push(newSpec()) }
function removeSpec(v, i)    { v.specs.splice(i, 1) }
function onSpecTypeChange(s) { s.unit = specUnits(s.type)[0] }

const allCollapsed = computed(() => variants.value.length > 0 && variants.value.every(v => v.collapsed))
function toggleAllVariants() {
  const next = !allCollapsed.value
  variants.value.forEach(v => { v.collapsed = next })
}
/** Opens the card that has a problem and scrolls to it. */
function focusVariant(uid) {
  const v = variants.value.find(x => x.uid === uid)
  if (v) v.collapsed = false
  scrollToVariant(uid)
}

/** e.g. "8 ft × 3 ft × 0.40 mm" — shown live here and in the POS dropdown. */
function variantLabel(v) {
  const parts = v.specs
    .filter(s => s.value !== '' && s.value !== null)
    .map(s => `${Number(s.value)} ${s.unit === 'gauge' ? 'ga' : s.unit}`)
  return parts.length ? parts.join(' × ') : 'Standard'
}

/** Returns null when everything is fine, otherwise { message, uid } of the size to fix. */
function validateVariants() {
  const seen = new Set()
  for (let i = 0; i < variants.value.length; i++) {
    const v = variants.value[i]
    const n = i + 1
    const fail = message => ({ message, uid: v.uid })
    if (v.specs.some(s => s.value === '' || s.value === null)) return fail(`Size ${n}: fill in or remove empty specifications.`)
    if (v.SellingPrice === '' || Number(v.SellingPrice) <= 0)   return fail(`Size ${n}: enter a selling price.`)
    if (v.CostPrice === '' || Number(v.CostPrice) < 0)          return fail(`Size ${n}: enter a cost price.`)

    // Stock fields are optional (blank = 0) but must be whole numbers >= 0.
    const stock   = v.OpeningStock === '' || v.OpeningStock === null ? 0 : Number(v.OpeningStock)
    const reorder = v.ReorderLevel === '' || v.ReorderLevel === null ? 0 : Number(v.ReorderLevel)
    if (!Number.isInteger(stock) || stock < 0)     return fail(`Size ${n}: opening stock must be a whole number, 0 or more.`)
    if (!Number.isInteger(reorder) || reorder < 0) return fail(`Size ${n}: reorder level must be a whole number, 0 or more.`)

    const label = variantLabel(v)
    if (seen.has(label)) return fail(`Size ${n} duplicates another size ("${label}").`)
    seen.add(label)
  }
  return null
}

const preview      = ref(null)
const imageFile    = ref(null)
const dragging     = ref(false)
const units        = ['Piece', 'Box', 'Pack', 'Set', 'Roll', 'Kg', 'g', 'L', 'mL']
const categories   = ref([])
const suppliers    = ref([])
const errorMessage = ref('')
const successMessage = ref('')
const hasSaved     = ref(false)
const saving       = ref(false)
let toastTimer = null

// Errors fade out by themselves after a few seconds (they float on screen now).
function flashError(msg) {
  successMessage.value = ''
  errorMessage.value = msg
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { errorMessage.value = '' }, 6000)
}

// Snapshot covers the main form, the sizes AND the image, for the unsaved-changes prompt.
// Folding a size card (collapsed) is not a change, so it is left out.
// Baseline is set in onMounted (variants must exist first).
const formSnapshot = () => JSON.stringify({
  form: form.value,
  variants: variants.value.map(({ collapsed, ...rest }) => rest),
  image: !!imageFile.value,
})
const initialFormSnapshot = ref('')
const dirty = computed(() => initialFormSnapshot.value !== '' && formSnapshot() !== initialFormSnapshot.value)

/* ────────────────────────────────────────────────────────────
   SKU AUTO-GENERATION — fires once, on blur (when the person
   leaves the Product Name field), instead of on every keystroke
   or after every typing pause. This avoids firing an API call
   while the person is still actively typing the name.
   skuRequestId guards against a slow/stale response overwriting
   a newer one if the person edits the name, blurs, edits again,
   and blurs again in quick succession.
──────────────────────────────────────────────────────────── */
let skuRequestId = 0

async function generateSku() {
  const name = form.value.ProductName
  if (!name || name.length < 2) {
    form.value.SKU = ''
    return
  }
  const requestId = ++skuRequestId
  try {
    const res = await api.get(`/api/generate-sku/${encodeURIComponent(name)}`)
    // Only apply the result if this is still the most recent request —
    // otherwise a slower earlier response could overwrite a newer one.
    if (requestId === skuRequestId) {
      form.value.SKU = res.data.sku
    }
  } catch (e) {
    console.error('generateSku error:', e)
  }
}

/* ── IMAGE ── */
const MAX_IMAGE_MB = 5

function setImage(file) {
  if (!file) return
  if (!file.type.startsWith('image/')) { flashError('Please choose an image file (PNG or JPG).'); return }
  if (file.size > MAX_IMAGE_MB * 1024 * 1024) { flashError(`That image is larger than ${MAX_IMAGE_MB} MB.`); return }
  if (preview.value) URL.revokeObjectURL(preview.value)
  imageFile.value = file
  preview.value = URL.createObjectURL(file)
}
function uploadImage(e) {
  setImage(e.target.files?.[0])
  e.target.value = ''   // lets you pick the same file again later
}
function onDrop(e) {
  dragging.value = false
  setImage(e.dataTransfer?.files?.[0])
}
function removeImage() {
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = null
  imageFile.value = null
  if (fileInput.value) fileInput.value.value = ''
}

async function loadCategories() { const r = await api.get('/api/categories'); categories.value = r.data.categories }
async function loadSuppliers()  { const r = await api.get('/api/suppliers');  suppliers.value  = r.data.suppliers  }

/* Pressing Enter inside a text box must not submit the whole form
   (a USB scanner types Enter after every barcode). Buttons and the
   description box keep their normal Enter behaviour. */
function blockEnterSubmit(e) {
  if (e.target?.tagName === 'INPUT') e.preventDefault()
}

/* Ctrl+S / Cmd+S saves (runs the browser's required-field checks first) */
function onSaveShortcut(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
    e.preventDefault()
    if (showBarcodeScanner.value || showLeaveModal.value || saving.value) return
    formEl.value?.requestSubmit()
  }
}
function onBeforeUnload(e) {
  if (dirty.value && !hasSaved.value) { e.preventDefault(); e.returnValue = '' }
}

onMounted(async () => {
  window.addEventListener('keydown', onSaveShortcut)
  window.addEventListener('beforeunload', onBeforeUnload)
  try {
    await Promise.all([loadCategories(), loadSuppliers()])
  } catch (e) {
    console.error('load categories/suppliers error:', e)
  } finally {
    // Always set the baseline — otherwise a failed load would make every
    // navigation look like "unsaved changes".
    initialFormSnapshot.value = formSnapshot()
  }
})

/* ────────────────────────────────────────────────────────────
   UNSAVED-CHANGES MODAL
   Replaces window.confirm. onBeforeRouteLeave can return a Promise,
   so the router waits until the person picks "Stay" (false) or
   "Leave" (true).
──────────────────────────────────────────────────────────── */
const showLeaveModal = ref(false)
const stayBtn        = ref(null)
let leaveResolver    = null

function askLeaveConfirmation() {
  leaveResolver?.(false) // settle any stale prompt
  return new Promise(resolve => {
    leaveResolver = resolve
    showLeaveModal.value = true
    nextTick(() => stayBtn.value?.focus()) // safe default: Enter stays on the page
  })
}
function resolveLeave(answer) {
  showLeaveModal.value = false
  const resolve = leaveResolver
  leaveResolver = null
  resolve?.(answer)
}
const stayOnPage   = () => resolveLeave(false)
const confirmLeave = () => resolveLeave(true)

onBeforeRouteLeave(() => {
  if (hasSaved.value) return true
  if (dirty.value) return askLeaveConfirmation()
  return true
})

/* ────────────────────────────────────────────────────────────
   BARCODE SCANNER — USB (keyboard-wedge) + Camera, same dual
   pipeline and detection logic as POS.vue, scoped locally to
   this file (no shared component, per request).
──────────────────────────────────────────────────────────── */
const CAMERA_ELEMENT_ID = 'product-create-camera-scanner'
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
const FLASH_DURATION_MS = 900
const SCAN_DEBOUNCE_MS = 300
// Hardware (keyboard-wedge) scanners fire keystrokes only a few ms apart.
// Anything slower than this is treated as a human typing manually, who
// should never be auto-submitted — only Enter submits for them.
const SCANNER_KEY_INTERVAL_MS = 60
const SCANNER_MESSAGE_TIMEOUT_MS = 3000
const AUTO_CLOSE_DELAY_MS = 700

const showBarcodeScanner = ref(false)
const scanMode           = ref('usb') // 'usb' | 'camera'
const barcodeBuffer      = ref('')
const barcodeInput       = ref(null)
const usbFocused         = ref(false) // is the hidden scanner input currently focused?
const usbReceiving       = ref(false) // is a scanner-speed keystroke burst actively coming in?

const cameraStarting     = ref(false)
const cameraError        = ref('')
const cameraFlashVisible = ref(false)
const cameraHint         = ref('')

// Unified scan feedback for BOTH usb + camera modes: { type: 'success' | 'error', text }
const scanFeedback = ref(null)

const barcodeDuplicate = ref(false)
const checkingBarcode  = ref(false)

let html5QrCode = null
let cameraStopping = null // guards overlapping stop/start calls, same as POS.vue
let cameraFlashTimer  = null
let cameraHintTimer   = null
let scanFeedbackTimer = null
let barcodeCheckTimer = null
let barcodeDebounceTimer = null
let closeAfterScanTimer  = null
let usbReceivingTimer    = null
// Timestamps of the most recent USB-input keystrokes, used to tell a
// hardware scanner's burst-typing apart from a human typing manually.
let keyTimestamps = []

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

async function openBarcodeScanner() {
  showBarcodeScanner.value = true
  barcodeBuffer.value = ''
  scanFeedback.value = null
  cameraError.value = ''
  cameraHint.value = ''
  cameraFlashVisible.value = false
  usbFocused.value = false
  usbReceiving.value = false
  keyTimestamps = []
  await nextTick() // ensure the modal + inputs are fully mounted
  if (scanMode.value === 'usb') {
    barcodeInput.value?.focus()
  } else {
    await startCamera()
  }
}

async function setScanMode(mode) {
  if (scanMode.value === mode) return
  if (scanMode.value === 'camera') await stopCamera()
  scanMode.value = mode
  scanFeedback.value = null
  cameraHint.value = ''
  cameraError.value = ''
  if (!showBarcodeScanner.value) return
  if (mode === 'camera') {
    // The camera <div> only renders once scanMode === 'camera' (v-if),
    // so we must wait for Vue to actually flush that DOM update before
    // Html5Qrcode tries to attach to CAMERA_ELEMENT_ID — otherwise it
    // throws a generic "element not found" error that surfaces as an
    // unhelpful "Unable to access camera" message.
    await nextTick()
    await startCamera()
  } else {
    nextTick(() => barcodeInput.value?.focus())
  }
}

async function startCamera() {
  if (html5QrCode || cameraStarting.value) return
  // If a previous instance is still tearing down, wait for that before
  // attaching a new one to the same DOM node — same guard as POS.vue.
  if (cameraStopping) await cameraStopping.catch(() => {})

  cameraError.value = ''
  cameraStarting.value = true
  try {
    html5QrCode = new Html5Qrcode(CAMERA_ELEMENT_ID, {
      formatsToSupport: CAMERA_FORMATS,
      verbose: false,
    })
    await html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 280, height: 200 } },
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

/** Gentle troubleshooting tip if the camera has been active a while
 *  without a successful decode — same behavior as POS.vue. */
function scheduleCameraHint() {
  clearTimeout(cameraHintTimer)
  cameraHint.value = ''
  if (scanMode.value !== 'camera' || !showBarcodeScanner.value) return
  cameraHintTimer = setTimeout(() => {
    cameraHint.value = 'Having trouble? Hold the barcode flat and steady, fill the frame, and make sure there\u2019s good lighting.'
  }, 5000)
}

function showFeedback(type, text, duration = SCANNER_MESSAGE_TIMEOUT_MS) {
  scanFeedback.value = { type, text }
  clearTimeout(scanFeedbackTimer)
  scanFeedbackTimer = setTimeout(() => { scanFeedback.value = null }, duration)
}

/** Distinguishes a hardware (keyboard-wedge) scanner's rapid keystroke burst
 *  from a human typing the barcode by hand. Scanners fire characters only
 *  a few ms apart and are auto-submitted after a short pause. Manual typing
 *  is never auto-submitted — it always waits for Enter. */
function onBarcodeKey(e) {
  if (e.key === 'Enter') {
    e.preventDefault()
    clearTimeout(barcodeDebounceTimer)
    clearTimeout(usbReceivingTimer)
    usbReceiving.value = false
    keyTimestamps = []
    const code = barcodeBuffer.value.trim()
    barcodeBuffer.value = ''
    if (code) handleScannedCode(code)
    return
  }

  // Ignore modifier/navigation keys for timing purposes — only actual
  // content-changing keys should affect scanner-burst detection.
  if (e.key.length !== 1 && e.key !== 'Backspace') return

  clearTimeout(barcodeDebounceTimer)

  if (e.key === 'Backspace') {
    // Editing by hand is a clear signal this is manual entry — never
    // auto-submit; just let the person keep typing and press Enter.
    keyTimestamps = []
    clearTimeout(usbReceivingTimer)
    usbReceiving.value = false
    return
  }

  keyTimestamps.push(Date.now())
  if (keyTimestamps.length > 6) keyTimestamps.shift()

  const looksLikeScanner = keyTimestamps.length >= 3 &&
    keyTimestamps.slice(1).every((t, i) => t - keyTimestamps[i] <= SCANNER_KEY_INTERVAL_MS)

  if (looksLikeScanner) {
    // Visual confirmation that a hardware scanner burst is actively being
    // detected right now — clears itself if the burst stops without a scan.
    usbReceiving.value = true
    clearTimeout(usbReceivingTimer)
    usbReceivingTimer = setTimeout(() => { usbReceiving.value = false }, SCAN_DEBOUNCE_MS + 200)

    // Handles hardware scanners that don't send an Enter terminator: submit
    // shortly after the fast keystroke burst stops.
    barcodeDebounceTimer = setTimeout(() => {
      const code = barcodeBuffer.value.trim()
      if (code.length >= 4) {
        barcodeBuffer.value = ''
        keyTimestamps = []
        usbReceiving.value = false
        handleScannedCode(code)
      }
    }, SCAN_DEBOUNCE_MS)
  }
  // Otherwise this is a human typing manually — no auto-submit timer is
  // set, so the buffer is never cleared out from under them.
}

/** Keeps the hidden scanner input focused for hardware scanners, but never
 *  steals focus away from a field/button the user is deliberately using. */
function onGlobalKeydown() {
  if (!showBarcodeScanner.value || scanMode.value !== 'usb') return
  const active = document.activeElement
  const isBusyElsewhere = active && active !== barcodeInput.value &&
    ['SELECT', 'BUTTON'].includes(active.tagName)
  if (isBusyElsewhere) return
  if (active !== barcodeInput.value) barcodeInput.value?.focus()
}

/** Single entry point for ANY scan source (USB keyboard-wedge or camera).
 *  Fills the field, shows confirmation, then auto-closes the scanner —
 *  stopping the camera first if that's the active mode. */
function handleScannedCode(rawCode) {
  const code = rawCode.trim()
  if (!code) return

  form.value.Barcode = code
  checkBarcodeDuplicate(code)
  showFeedback('success', `Captured: ${code}`, 1500)

  if (scanMode.value === 'camera') {
    cameraFlashVisible.value = true
    clearTimeout(cameraFlashTimer)
    cameraFlashTimer = setTimeout(() => { cameraFlashVisible.value = false }, 850)
  }

  clearTimeout(closeAfterScanTimer)
  closeAfterScanTimer = setTimeout(async () => {
    await stopCamera()
    showBarcodeScanner.value = false
  }, scanMode.value === 'camera' ? FLASH_DURATION_MS : AUTO_CLOSE_DELAY_MS)
}

async function closeBarcodeScanner() {
  clearTimeout(closeAfterScanTimer)
  clearTimeout(cameraFlashTimer)
  clearTimeout(usbReceivingTimer)
  cameraFlashVisible.value = false
  usbFocused.value = false
  usbReceiving.value = false
  await stopCamera()
  showBarcodeScanner.value = false
  cameraError.value = ''
  barcodeBuffer.value = ''
  scanFeedback.value = null
}

/** Debounced duplicate check — reuses the existing barcode lookup endpoint
 *  (GET /api/products/barcode/{barcode}) instead of adding a new route.
 *  A 404 / "not found" response means no duplicate, which is the expected
 *  happy path when adding a genuinely new product. */
function checkBarcodeDuplicate(code) {
  clearTimeout(barcodeCheckTimer)
  barcodeDuplicate.value = false
  if (!code || !code.trim()) return
  barcodeCheckTimer = setTimeout(async () => {
    checkingBarcode.value = true
    try {
      const { data } = await api.get(`/api/products/barcode/${encodeURIComponent(code.trim())}`)
      barcodeDuplicate.value = !!(data?.success && data?.product)
    } catch (e) {
      barcodeDuplicate.value = false
    } finally {
      checkingBarcode.value = false
    }
  }, 400)
}

watch(() => form.value.Barcode, (code) => checkBarcodeDuplicate(code))

onMounted(() => {
  window.addEventListener('keydown', onGlobalKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onGlobalKeydown)
  window.removeEventListener('keydown', onSaveShortcut)
  window.removeEventListener('beforeunload', onBeforeUnload)
  clearTimeout(toastTimer)
  clearTimeout(barcodeCheckTimer)
  clearTimeout(barcodeDebounceTimer)
  clearTimeout(cameraFlashTimer)
  clearTimeout(cameraHintTimer)
  clearTimeout(scanFeedbackTimer)
  clearTimeout(closeAfterScanTimer)
  clearTimeout(usbReceivingTimer)
  if (preview.value) URL.revokeObjectURL(preview.value)
  stopCamera()
})

/* ────────────────────────────────────────────────────────────
   SAVE
──────────────────────────────────────────────────────────── */
async function saveProduct() {
  errorMessage.value = ''
  if (saving.value) return

  if (barcodeDuplicate.value) {
    flashError('This barcode is already assigned to another product. Please use a different barcode.')
    return
  }

  const problem = validateVariants()
  if (problem) {
    flashError(problem.message)
    focusVariant(problem.uid)   // opens the size that needs fixing and scrolls to it
    return
  }

  saving.value = true
  try {
    const fd = new FormData()
    for (const key in form.value) {
      fd.append(key, key === 'IsReturnable' ? (form.value[key] ? 1 : 0) : (form.value[key] ?? ''))
    }

    // Each size carries its own price AND its own stock.
    const payload = variants.value.map(v => ({
      Label: variantLabel(v),
      Specs: v.specs.map(s => ({ Type: s.type, Value: Number(s.value), Unit: s.unit })),
      SellingPrice: Number(v.SellingPrice),
      CostPrice: Number(v.CostPrice),
      OpeningStock: Number(v.OpeningStock) || 0,
      ReorderLevel: Number(v.ReorderLevel) || 0,
    }))
    fd.append('Variants', JSON.stringify(payload))
    // Backward-compat: code that still reads a single price gets the first size's price.
    fd.append('SellingPrice', payload[0].SellingPrice)
    fd.append('CostPrice', payload[0].CostPrice)

    if (imageFile.value) fd.append('Product_Image', imageFile.value)
    await api.post('/api/products', fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    hasSaved.value = true
    successMessage.value = 'Product saved successfully!'
    setTimeout(() => router.push('/products'), 800)
  } catch (err) {
    flashError(err?.response?.data?.message || 'Failed to save product.')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
/* ── TOKEN BRIDGE & PAGE SHELL ── */
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

  /* ── SPACE TUNING (same values as the Product List) ──
     The app shell (Layout.vue) adds roughly 64px of its own padding around every page.
     --bleed-* reclaims part of it on desktop, on the top AND on both sides.
     Lower a value (or set it to 0px) if content ever touches the sidebar or top bar. */
  --bleed-x: 30px;
  --bleed-y: 26px;
  --p-gap: 18px;

  min-height: 100%;
  overflow-x: clip;             /* the page never scrolls sideways (clip, so sticky keeps working) */
  background: var(--p-bg);
  padding: 16px 24px 40px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--p-gap);
  transition: background-color .22s ease;
}
.prod-page > * { min-width: 0; }

/* Desktop only: pull the page outward into the shell's padding (top, left AND right). */
@media (min-width: 1001px) {
  .prod-page { margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) 0; }
}

/* ── FORM LAYOUT ──
   Narrow screens: one column, cards ordered by the .o-* classes (the two column
   wrappers dissolve with display: contents).
   Wide screens: main column on the left, sticky side column on the right. */
.form-layout { display: flex; flex-direction: column; gap: var(--p-gap); min-width: 0; }
.col-main, .col-side { display: contents; }
.o-basic { order: 1; } .o-image { order: 2; } .o-class { order: 3; }
.o-details { order: 4; } .o-sizes { order: 5; } .o-summary { order: 6; }
.save-bar { order: 9; }

@media (min-width: 1360px) {
  .form-layout {
    display: grid; grid-template-columns: minmax(0, 1fr) 340px;
    gap: var(--p-gap) 20px; align-items: start;
  }
  .col-main { display: flex; flex-direction: column; gap: var(--p-gap); min-width: 0; grid-column: 1; grid-row: 1; }
  .col-side {
    display: flex; flex-direction: column; gap: var(--p-gap); grid-column: 2; grid-row: 1;
    position: sticky; top: 12px; align-self: start;
    max-height: calc(100vh - 110px); overflow-y: auto; overscroll-behavior: contain;
    padding: 3px; margin: -3px;
    scrollbar-width: thin;
  }
  .o-basic, .o-image, .o-class, .o-details, .o-sizes, .o-summary { order: 0; }
  .save-bar { grid-column: 1 / -1; grid-row: 2; }
  .col-side .form-card-header { padding: 14px 18px 12px; }
  .col-side .form-card-body { padding: 16px 18px; }
  .col-side .form-card-sub { display: none; }
}

/* PAGE HEADER */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--p-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--p-text-primary); letter-spacing: -.03em; margin: 0; transition: color .22s; }
.page-sub     { margin: 6px 0 0; font-size: 13px; color: var(--p-text-muted); }
.kbd-hint {
  font-family: inherit; font-size: 11px; font-weight: 700; padding: 1px 6px; border-radius: 5px;
  background: var(--p-surface-raised); border: 1px solid var(--p-border-strong); color: var(--p-text-secondary);
}

/* BUTTONS */
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
.form-card-icon--teal   { background: rgba(20,184,166,.12); color: #14b8a6; }
.form-card-icon--rose   { background: rgba(244,63,94,.12);  color: #f43f5e; }
html[data-theme="dark"] .form-card-icon--blue   { background: rgba(59,130,246,.18); }
html[data-theme="dark"] .form-card-icon--violet { background: rgba(99,102,241,.18); }
html[data-theme="dark"] .form-card-icon--green  { background: rgba(16,185,129,.18); }
html[data-theme="dark"] .form-card-icon--amber  { background: rgba(245,158,11,.18); }
html[data-theme="dark"] .form-card-icon--teal   { background: rgba(20,184,166,.18); }
html[data-theme="dark"] .form-card-icon--rose   { background: rgba(244,63,94,.18); }

.form-card-title { font-size: 15px; font-weight: 700; color: var(--p-text-primary); margin: 0 0 2px; transition: color .22s; }
.form-card-sub   { font-size: 12.5px; color: var(--p-text-muted); margin: 0; }
.form-card-body  { padding: 22px 24px; }
.form-card-tools { margin-left: auto; display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.size-total-pill {
  flex-shrink: 0; font-size: 11.5px; font-weight: 700;
  padding: 4px 12px; border-radius: 20px; background: var(--p-accent-soft); color: var(--p-accent);
}
.mini-btn {
  display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 11px; cursor: pointer;
  font-family: inherit; font-size: 11.5px; font-weight: 600;
  color: var(--p-text-secondary); background: var(--p-surface);
  border: 1.5px solid var(--p-border-strong); border-radius: 8px;
  transition: border-color .15s, color .15s, background-color .15s;
}
.mini-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); background: var(--p-accent-soft); }
.mini-btn i { font-size: 10px; }

/* GRIDS */
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 20px; }
.form-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.form-stack  { display: flex; flex-direction: column; gap: 16px; }
.details-extra-row { margin-top: 18px; }

/* FIELDS */
.form-field { display: flex; flex-direction: column; gap: 7px; min-width: 0; }
.form-label { font-size: 12.5px; font-weight: 600; color: var(--p-text-secondary); letter-spacing: .01em; transition: color .22s; }
.req { color: var(--p-accent); }
.form-hint { font-size: 11.5px; color: var(--p-text-faint); margin: 0; }
.form-hint--error { color: #f43f5e; font-weight: 600; display: flex; align-items: center; gap: 5px; }

/* INPUTS */
.input-wrap { position: relative; display: flex; align-items: center; }
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
.form-input:hover:not(:disabled):not(:focus) { border-color: var(--p-accent-border); }
.form-input:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); background: var(--p-surface); }
.form-input:focus ~ .input-icon,
.input-wrap:focus-within .input-icon { color: var(--p-accent); }
.form-input::placeholder { color: var(--p-text-faint); }
.form-input--disabled { background: var(--p-surface-raised); color: var(--p-text-faint); cursor: not-allowed; }
.form-input--prefixed { padding-left: 30px; }
.form-input--plain { padding-left: 14px; }

/* BARCODE FIELD */
.barcode-input-wrap { position: relative; }
.barcode-input { padding-right: 44px; }
.barcode-scan-btn {
  position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
  width: 30px; height: 30px; border: none; border-radius: 8px;
  background: var(--p-accent-soft); color: var(--p-accent);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  font-size: 13px;
  transition: background-color .15s, color .15s;
}
.barcode-scan-btn:hover { background: var(--p-accent); color: #fff; }

/* TEXTAREA */
.form-textarea {
  width: 100%; padding: 10px 14px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: var(--p-surface-sunken); color: var(--p-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none; resize: vertical; line-height: 1.6;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-textarea:hover:not(:focus) { border-color: var(--p-accent-border); }
.form-textarea:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); background: var(--p-surface); }
.form-textarea::placeholder { color: var(--p-text-faint); }

/* SELECT */
.select-wrap { position: relative; display: flex; align-items: center; }
.form-select {
  width: 100%; height: 42px;
  padding: 0 32px 0 36px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  background: var(--p-surface-sunken); color: var(--p-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  appearance: none; -webkit-appearance: none; cursor: pointer;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-select:hover:not(:focus) { border-color: var(--p-accent-border); }
.form-select:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-ring); background: var(--p-surface); }
.form-select--plain { padding-left: 12px; }
.select-caret {
  position: absolute; right: 10px; font-size: 9px;
  color: var(--p-text-faint); pointer-events: none;
}

/* TOGGLE */
.toggle-row { display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none; }
.toggle-row--card {
  margin-top: 18px; padding: 12px 14px; border-radius: 12px;
  background: var(--p-surface-sunken); border: 1px solid var(--p-border);
  transition: border-color .15s;
}
.toggle-row--card:hover { border-color: var(--p-accent-border); }
.toggle-track {
  width: 44px; height: 24px; border-radius: 999px; flex-shrink: 0; padding: 0; border: none;
  background: var(--p-border-strong);
  transition: background-color .22s ease;
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

/* IMAGE UPLOAD */
.upload-zone {
  position: relative; width: 100%; min-height: 200px;
  border: 2px dashed var(--p-border-strong); border-radius: 14px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  overflow: hidden; cursor: pointer;
  background: var(--p-surface-sunken);
  transition: border-color .18s, background-color .22s, transform .15s;
}
.upload-zone:hover, .upload-zone--drag { border-color: var(--p-accent); background: var(--p-accent-soft); }
.upload-zone--drag { transform: scale(1.01); }
.upload-zone--has-image { border-style: solid; border-color: var(--p-border); }
.upload-preview { max-height: 200px; max-width: 100%; object-fit: contain; border-radius: 8px; }
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
  margin-top: 10px;
  background: none; border: none; cursor: pointer;
  font-size: 12px; font-weight: 600; color: #f43f5e; font-family: inherit;
  display: flex; align-items: center; gap: 5px; padding: 2px 0;
  transition: opacity .15s;
}
.upload-remove:hover { opacity: .7; }

/* SIZES, PRICES & STOCK */
.variant-list { display: flex; flex-direction: column; gap: 14px; }
.variant-card {
  border: 1.5px solid var(--p-border); border-radius: 14px;
  background: var(--p-surface-sunken); padding: 14px 16px 16px;
  display: flex; flex-direction: column; gap: 14px; scroll-margin: 90px;
  transition: border-color .15s;
}
.variant-card:focus-within { border-color: var(--p-accent-border); }
.variant-card--collapsed { padding-top: 10px; padding-bottom: 10px; gap: 0; }
.variant-head { display: flex; align-items: center; gap: 10px; }
.variant-toggle {
  width: 28px; height: 28px; flex-shrink: 0; border: none; border-radius: 8px;
  background: transparent; color: var(--p-text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 11px;
  transition: background-color .15s, color .15s;
}
.variant-toggle:hover { background: var(--p-accent-soft); color: var(--p-accent); }
.variant-toggle:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--p-ring); }
.variant-toggle i { transition: transform .2s; }
.variant-toggle i.is-collapsed { transform: rotate(-90deg); }
.variant-badge {
  font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
  padding: 3px 10px; border-radius: 999px; white-space: nowrap;
  background: var(--p-accent-soft); color: var(--p-accent);
}
.variant-label { font-size: 13.5px; font-weight: 600; color: var(--p-text-primary); flex: 1; min-width: 0; overflow-wrap: anywhere; cursor: pointer; }
.variant-mini { font-size: 12px; font-weight: 600; color: var(--p-text-muted); white-space: nowrap; font-variant-numeric: tabular-nums; }
.variant-body { display: flex; flex-direction: column; gap: 14px; }
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
/* Price row and stock row inside a size card share the same divider style. */
.variant-section { padding-top: 14px; border-top: 1px dashed var(--p-border-strong); }
.variant-prices-wrap { display: flex; flex-direction: column; gap: 10px; }
.margin-hint {
  margin: 0; display: inline-flex; align-items: center; gap: 7px; align-self: flex-start;
  font-size: 12px; font-weight: 600; padding: 4px 11px; border-radius: 20px;
  background: rgba(16,185,129,.12); color: #059669;
}
.margin-hint--loss { background: rgba(244,63,94,.12); color: #e11d48; }
html[data-theme="dark"] .margin-hint { color: #4ADE80; }
html[data-theme="dark"] .margin-hint--loss { color: #F87171; }
.add-variant-btn { margin-top: 16px; width: 100%; justify-content: center; border-style: dashed; }

/* READY-TO-SAVE CHECKLIST */
.ready { display: flex; flex-direction: column; gap: 8px; }
.ready-head { display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; color: var(--p-text-secondary); }
.ready-bar { height: 6px; border-radius: 6px; background: var(--p-border); overflow: hidden; }
.ready-bar span { display: block; height: 100%; border-radius: 6px; background: var(--p-accent); transition: width .3s ease, background-color .3s; }
.ready-bar--full span { background: #10b981; }
.checklist { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 9px; }
.checklist li { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--p-text-muted); transition: color .2s; }
.check-dot {
  width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0;
  border: 1.5px solid var(--p-border-strong); color: transparent; font-size: 9px;
  display: flex; align-items: center; justify-content: center;
  transition: background-color .2s, border-color .2s, color .2s;
}
.checklist li.is-done { color: var(--p-text-primary); }
.checklist li.is-done .check-dot { background: #10b981; border-color: #10b981; color: #fff; }

/* INVENTORY SUMMARY */
.summary-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; padding-top: 14px; border-top: 1px dashed var(--p-border-strong); }
.summary-tile {
  display: flex; flex-direction: column; gap: 4px; padding: 10px 12px; min-width: 0;
  background: var(--p-surface-sunken); border: 1px solid var(--p-border); border-radius: 12px;
}
.summary-label { font-size: 10.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--p-text-muted); }
.summary-value { font-size: 16px; font-weight: 800; color: var(--p-text-primary); letter-spacing: -.02em; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
.summary-value small { font-size: 11.5px; font-weight: 600; color: var(--p-text-faint); }

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

/* BARCODE SCANNER MODAL */
.scan-modal-backdrop {
  position: fixed; inset: 0;
  background: var(--c-overlay); backdrop-filter: blur(6px);
  z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;
}
.scan-modal {
  background: var(--p-surface); border: 1px solid var(--p-border);
  border-radius: 16px; width: 100%; max-width: 360px; overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.scan-modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 18px; border-bottom: 1px solid var(--p-border);
  transition: border-color .22s;
}
.scan-modal-header h3 { font-size: 15px; font-weight: 700; color: var(--p-text-primary); margin: 0; }
.scan-modal-close {
  width: 28px; height: 28px; border: none; border-radius: 8px;
  background: var(--p-surface-raised); color: var(--p-text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 12px;
  transition: background-color .15s, color .15s;
}
.scan-modal-close:hover { background: var(--p-border-strong); color: var(--p-text-primary); }
.scan-modal-body { padding: 18px; display: flex; flex-direction: column; gap: 10px; align-items: center; }
.form-hint--tip { font-style: italic; margin-top: 2px; }
.form-hint--success { color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 6px; }

/* Mode toggle: USB vs Camera — same as POS.vue */
.scanner-mode-toggle { display: inline-flex; gap: 6px; margin-bottom: 2px; }
.smode-btn {
  display: flex; align-items: center; gap: 5px;
  height: 28px; padding: 0 12px;
  border: 1.5px solid var(--p-border-strong); border-radius: 20px;
  background: var(--p-surface); color: var(--p-text-muted);
  font-size: 11.5px; font-weight: 600; font-family: inherit; cursor: pointer;
  transition: border-color .15s, background-color .15s, color .15s;
}
.smode-btn:hover { border-color: var(--p-accent-border); color: var(--p-accent); }
.smode-btn--active { background: var(--p-accent); border-color: var(--p-accent); color: #fff; }

/* USB (keyboard-wedge) input */
.usb-status {
  display: flex; align-items: center; gap: 8px;
  font-size: 12px; font-weight: 600; color: var(--p-text-faint);
  transition: color .18s ease;
}
.usb-status-dot {
  width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
  background: var(--p-text-faint);
  transition: background-color .18s ease;
}
.usb-status--active { color: var(--p-text-muted); }
.usb-status--active .usb-status-dot {
  background: #10b981;
  box-shadow: 0 0 0 0 rgba(16,185,129,.5);
  animation: usb-pulse 1.8s ease-in-out infinite;
}
.usb-status--receiving { color: var(--p-accent); }
.usb-status--receiving .usb-status-dot {
  background: var(--p-accent);
  box-shadow: 0 0 0 0 rgba(234,88,12,.5);
  animation: usb-pulse-fast 0.5s ease-in-out infinite;
}
@keyframes usb-pulse {
  0%   { box-shadow: 0 0 0 0 rgba(16,185,129,.45); }
  70%  { box-shadow: 0 0 0 6px rgba(16,185,129,0); }
  100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
}
@keyframes usb-pulse-fast {
  0%   { box-shadow: 0 0 0 0 rgba(234,88,12,.55); transform: scale(1); }
  50%  { box-shadow: 0 0 0 5px rgba(234,88,12,0); transform: scale(1.15); }
  100% { box-shadow: 0 0 0 0 rgba(234,88,12,0); transform: scale(1); }
}

.barcode-hidden-input {
  width: 100%; height: 42px;
  border: 1.5px solid var(--p-border-strong); border-radius: 10px;
  padding: 0 14px; font-size: 14px; font-family: inherit;
  color: var(--p-text-primary); background: var(--p-surface-sunken);
  outline: none; text-align: center;
  transition: border-color .18s, background-color .22s;
}
.barcode-hidden-input:focus { border-color: var(--p-accent); background: var(--p-surface); }
.barcode-hidden-input::placeholder { color: var(--p-text-faint); }
.barcode-preview {
  font-size: 13px; font-weight: 600; color: var(--p-accent);
  display: flex; align-items: center; gap: 6px; margin: 0;
}
.feedback-fade-enter-active, .feedback-fade-leave-active { transition: opacity .18s ease; }
.feedback-fade-enter-from, .feedback-fade-leave-to { opacity: 0; }

/* Camera box + reticle + flash — same visuals/behavior as POS.vue */
.camera-scan-box-outer { position: relative; width: 100%; }
.camera-scan-box {
  width: 100%; min-height: 220px; border-radius: 10px; overflow: hidden;
  background: var(--p-surface-sunken); border: 1.5px solid var(--p-border-strong);
}
.camera-scan-box :deep(video) { border-radius: 10px; }

.camera-flash-overlay {
  position: absolute; inset: 0;
  background: rgba(16,185,129,.30);
  display: flex; align-items: center; justify-content: center;
  border-radius: 10px;
}
.camera-flash-check {
  width: 48px; height: 48px; border-radius: 50%;
  background: #10b981; color: #fff;
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

/* UNSAVED-CHANGES CONFIRMATION (same look as POS.vue "Clear this sale?")
   Teleported to <body>, so it uses --c-* tokens directly with fallbacks. */
.modal-backdrop {
  position: fixed; inset: 0;
  background: var(--c-overlay, rgba(0,0,0,.6)); backdrop-filter: blur(6px);
  z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal {
  position: relative; isolation: isolate;
  background-color: var(--c-surface-overlay, #1e293b);
  border: 1px solid var(--c-border, #334155);
  border-radius: 20px; width: 100%; max-width: 480px;
  max-height: calc(100vh - 40px);
  display: flex; flex-direction: column; overflow: hidden;
  box-shadow: var(--c-shadow-xl, 0 24px 60px rgba(0,0,0,.5));
}
.modal--confirm { max-width: 400px; }

.confirm-body { padding: 28px 24px 8px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px; }
.confirm-icon {
  width: 52px; height: 52px; border-radius: 50%;
  background: rgba(244,63,94,.14); color: #f43f5e; border: 1px solid rgba(244,63,94,.3);
  display: flex; align-items: center; justify-content: center; font-size: 20px;
}
.confirm-title { margin: 4px 0 0; font-size: 17px; font-weight: 800; color: var(--c-text-primary, #f1f5f9); letter-spacing: -.01em; }
.confirm-text  { margin: 0; font-size: 13px; line-height: 1.55; color: var(--c-text-muted, #94a3b8); max-width: 320px; }

.modal-footer {
  display: flex; gap: 10px; flex-shrink: 0;
  padding: 14px 22px 20px; border-top: 1px solid var(--c-border, #334155);
}
.confirm-footer { border-top: none; padding-top: 18px; }
.confirm-footer .modal-cancel, .confirm-footer .modal-confirm { flex: 1; }

.modal-cancel {
  height: 42px; border: 1.5px solid var(--c-border-strong, #47566b); border-radius: 10px;
  background: none; color: var(--c-text-muted, #94a3b8); font-size: 13.5px; font-weight: 600;
  font-family: inherit; cursor: pointer;
  transition: background .15s, border-color .15s, color .15s;
}
.modal-cancel:hover { background: var(--c-surface-raised, #26364b); color: var(--c-text-primary, #f1f5f9); }
.modal-confirm {
  height: 42px; border: none; border-radius: 10px;
  background: linear-gradient(135deg, var(--c-accent, #f97316), var(--c-accent-deep, #c2410c));
  color: #fff; font-size: 13.5px; font-weight: 700; font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: opacity .15s, transform .15s, box-shadow .15s;
}
.modal-confirm--danger {
  background: linear-gradient(135deg, #f43f5e, #be123c);
  box-shadow: 0 4px 14px rgba(244,63,94,.35);
}
.modal-confirm--danger:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(244,63,94,.45); }
.modal-cancel:focus-visible, .modal-confirm:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--c-accent-ring, rgba(249,115,22,.25)); }

.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .16s ease, transform .16s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.97) translateY(8px); }

/* RESPONSIVE */
@media (max-width: 1359px) and (min-width: 801px) {
  .summary-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
@media (max-width: 900px) {
  .prod-page { padding: 12px 14px 32px; --p-gap: 14px; }
}
@media (max-width: 800px) {
  .prod-page { padding: 16px 16px 24px; }
  .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
  .spec-row { grid-template-columns: 1fr 1fr; }
  .spec-row .spec-remove { grid-column: 2; justify-self: end; }
  .form-card-header { flex-wrap: wrap; padding: 16px 16px 12px; }
  .form-card-body { padding: 18px 16px; }
  .form-card-tools { margin-left: 0; width: 100%; justify-content: space-between; }
  .variant-mini { display: none; }
  .save-bar { bottom: 10px; padding: 10px; border-radius: 14px; }
  .save-bar-status { display: none; }
  .save-bar-actions { width: 100%; }
  .save-bar-actions .btn { flex: 1; justify-content: center; }
  .save-bar-actions .btn--ghost { flex: 0 0 auto; }
  .kbd, .page-sub { display: none; }
}
@media (prefers-reduced-motion: reduce) {
  .save-dot--dirty { animation: none; }
  .variant-toggle i, .ready-bar span { transition: none; }
  .alert-fade-enter-active, .alert-fade-leave-active { transition: none; }
}
</style>