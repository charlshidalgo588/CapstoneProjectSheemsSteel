<template>
  <div class="lw">

    <!-- NAVBAR -->
    <header class="lw-nav">
      <div class="lw-nav-inner">
        <div class="lw-brand">
          <img src="@/assets/sheems_logo.png" alt="Shem Steel logo" class="lw-logo-img" />
          <div class="lw-brand-text">
            <div class="lw-brand-name">Shem Steel</div>
            <div class="lw-brand-sub">Construction Company</div>
          </div>
        </div>
        <div class="lw-nav-right">
          <nav class="lw-nav-links" aria-label="Page sections">
            <button type="button" :class="{ 'lw-nav-active': activeSection === 'lw-catalog' }" :aria-current="activeSection === 'lw-catalog' ? 'true' : undefined" @click="scrollToId('lw-catalog')">Products</button>
            <button type="button" :class="{ 'lw-nav-active': activeSection === 'lw-how' }" :aria-current="activeSection === 'lw-how' ? 'true' : undefined" @click="scrollToId('lw-how')">How to order</button>
            <button type="button" :class="{ 'lw-nav-active': activeSection === 'lw-visit' }" :aria-current="activeSection === 'lw-visit' ? 'true' : undefined" @click="scrollToId('lw-visit')">Visit us</button>
          </nav>
          <span class="lw-nav-hint">Inventory &amp; Sales Management System</span>

          <div class="lw-settings-wrap">
            <!-- Low-key icon-only appearance button -->
            <button
              class="lw-appearance-btn"
              :class="{ 'lw-appearance-btn--open': showThemeMenu }"
              @click.stop="showThemeMenu = !showThemeMenu"
              aria-label="Appearance settings"
              title="Appearance"
              :aria-expanded="showThemeMenu"
            >
              <i class="fa-solid fa-gear" aria-hidden="true"></i>
            </button>

            <transition name="lw-dropdown">
              <div v-if="showThemeMenu" class="lw-settings-menu" @click.stop>
                <div class="lw-settings-menu-head">
                  <span class="lw-settings-menu-icon"><i class="fa-solid fa-moon"></i></span>
                  <div>
                    <p class="lw-settings-menu-title">Appearance</p>
                    <p class="lw-settings-menu-sub">How this device looks</p>
                  </div>
                </div>
                <div class="lw-settings-menu-row">
                  <div>
                    <p class="lw-settings-menu-label">Dark Mode</p>
                    <p class="lw-settings-menu-value">{{ darkMode ? 'On' : 'Off' }}</p>
                  </div>
                  <button
                    class="lw-theme-toggle"
                    @click="toggleDark"
                    :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                  >
                    <span class="lw-pill-track" :class="{ 'lw-pill-track--dark': darkMode }">
                      <span class="lw-pill-icon lw-pill-icon--sun" :class="{ 'lw-pill-icon--active': !darkMode }">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                          <circle cx="12" cy="12" r="4"/>
                          <line x1="12" y1="2"  x2="12" y2="5"/>
                          <line x1="12" y1="19" x2="12" y2="22"/>
                          <line x1="4.22" y1="4.22"   x2="6.34" y2="6.34"/>
                          <line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/>
                          <line x1="2"  y1="12" x2="5"  y2="12"/>
                          <line x1="19" y1="12" x2="22" y2="12"/>
                          <line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/>
                          <line x1="17.66" y1="6.34"  x2="19.78" y2="4.22"/>
                        </svg>
                      </span>
                      <span class="lw-pill-thumb" :class="{ 'lw-pill-thumb--right': darkMode }"></span>
                      <span class="lw-pill-icon lw-pill-icon--moon" :class="{ 'lw-pill-icon--active': darkMode }">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                      </span>
                    </span>
                  </button>
                </div>
              </div>
            </transition>
          </div>

          <!-- Opens the login form in a modal -->
          <button ref="loginBtnEl" class="lw-signin-btn" @click="openLoginModal">
            <i class="fa-solid fa-lock" aria-hidden="true"></i>
            <span>Login</span>
          </button>
        </div>
      </div>
    </header>

    <!-- SCROLLABLE PAGE BODY -->
    <main ref="pageEl" class="lw-page" @scroll.passive="onPageScroll">

      <!-- HERO -->
      <section class="lw-hero">
        <div class="lw-hero-bg" aria-hidden="true">
          <img
            src="@/assets/roofing-materials.png"
            alt=""
            class="lw-hero-roof-img"
          />
        </div>
        <div class="lw-hero-fade" aria-hidden="true"></div>

        <div class="lw-hero-inner">
          <div class="lw-hero-text">
            <div class="lw-eyebrow">
              <span class="lw-eyebrow-bar"></span>
              IMS · SMS Platform · Est. 2024
            </div>

            <h1 class="lw-h1">
              Quality steel &amp;<br />
              <em class="lw-h1-em">construction supplies</em><br />
              you can trust.
            </h1>

            <p class="lw-tagline">
              Browse our available products and prices. Built for durability.
              Made for every project.
            </p>

            <div class="lw-hero-actions">
              <button type="button" class="lw-cta lw-cta--primary" @click="scrollToId('lw-catalog')">
                <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i> Browse products
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- QUICK INFO STRIP -->
      <div class="lw-strip">
        <div class="lw-strip-inner">
          <span v-if="openNow !== null" class="lw-strip-item">
            <span class="lw-open-dot" :class="openNow ? 'lw-open-dot--open' : 'lw-open-dot--closed'"></span>
            <strong>{{ openNow ? 'Open now' : 'Closed now' }}</strong>
            <span class="lw-strip-muted">Mon – Sat, 7:00 AM – 5:00 PM</span>
          </span>
          <span class="lw-strip-item">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            Butuan City, Agusan del Norte
          </span>
          <span v-if="shopProducts.length" class="lw-strip-item">
            <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
            {{ shopProducts.length }} products in stock list
          </span>
        </div>
      </div>

      <!-- LOAD / ERROR STATES -->
      <section v-if="shopError || shopLoading" class="lw-block">
        <div v-if="shopError" class="lw-shop-state lw-shop-state--error">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <p>{{ shopError }}</p>
          <button @click="loadShopCatalog" class="lw-shop-retry">Retry</button>
        </div>
        <div v-else class="lw-skel-wrap" aria-busy="true" aria-label="Loading products">
          <div class="lw-shop-grid lw-shop-grid--groups">
            <div v-for="n in 8" :key="n" class="lw-shop-card lw-skel">
              <div class="lw-skel-img"></div>
              <div class="lw-skel-line lw-skel-line--w80"></div>
              <div class="lw-skel-line lw-skel-line--w50"></div>
              <div class="lw-skel-line lw-skel-line--w65"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- BEST SELLERS — hidden while the visitor is searching -->
      <section
        v-if="!shopLoading && !shopError && !normalizedShopSearch && displayedBestSellers.length"
        class="lw-block lw-block--tight"
      >
        <div class="lw-sec-head">
          <h2 class="lw-shop-title">
            <span class="lw-title-bar"></span>{{ bestSellersAreReal ? 'Best Sellers' : 'Featured Products' }}
          </h2>
          <p class="lw-shop-sub">
            {{ bestSellersAreReal ? 'The materials our customers buy the most.' : 'Handpicked from what we have in stock right now.' }}
          </p>
        </div>

        <div class="lw-shop-carousel">
          <button
            type="button"
            class="lw-shop-nav lw-shop-nav--prev"
            :class="{ 'lw-shop-nav--hidden': !bsScroll.canPrev }"
            :disabled="!bsScroll.canPrev"
            @click="scrollBestSellers(-1)"
            aria-label="Scroll to previous products"
          >
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
          </button>

          <div ref="bsEl" class="lw-shop-grid lw-shop-grid--slider" @scroll="updateBsScroll">
            <div
              v-for="(product, i) in displayedBestSellers"
              :key="product.id"
              class="lw-shop-card lw-shop-card--click"
              tabindex="0"
              role="button"
              @click="openProduct(product)"
              @keydown.enter="openProduct(product)"
            >
              <div class="lw-shop-card-img" :class="{ 'lw-shop-card-img--photo': product.image }">
                <span class="lw-shop-stock-pill">
                  <span class="lw-shop-dot" :class="`lw-shop-dot--${shopStockStatus(product)}`"></span>
                  {{ shopStockShort(product) }}
                </span>
                <span v-if="bestSellersAreReal" class="lw-shop-rank">#{{ i + 1 }}</span>
                <img v-if="product.image" :src="product.image" :alt="product.name" @error="onShopImgError(product)" />
                <div v-else class="lw-shop-card-img-placeholder"><i class="fa-solid fa-cube"></i></div>
              </div>
              <div class="lw-shop-card-body">
                <p class="lw-shop-card-name" :title="product.name">{{ product.name }}</p>
                <p v-if="product.variants.length" class="lw-shop-card-sizes">
                  {{ product.variants.length }} size{{ product.variants.length === 1 ? '' : 's' }} available<template v-if="hasPriceRange(product)"> · price varies</template>
                </p>
                <p class="lw-shop-card-avail" :class="`lw-shop-card-avail--${shopStockStatus(product)}`">
                  <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
                  {{ availableText(product) }}
                </p>
                <p class="lw-shop-card-price">
                  <span v-if="priceLabel(product)" class="lw-shop-price-from">{{ priceLabel(product) }}</span>
                  <span class="lw-shop-price-currency">₱</span>{{ product.price.toFixed(2) }}
                  <span class="lw-shop-price-unit">{{ priceUnit(product) }}</span>
                </p>
                <span class="lw-shop-card-cta">
                  {{ ctaLabel(product) }}
                  <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </span>
              </div>
            </div>
          </div>

          <button
            type="button"
            class="lw-shop-nav lw-shop-nav--next"
            :class="{ 'lw-shop-nav--hidden': !bsScroll.canNext }"
            :disabled="!bsScroll.canNext"
            @click="scrollBestSellers(1)"
            aria-label="Scroll to next products"
          >
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          </button>
        </div>
      </section>

      <!-- ALL PRODUCTS — one flat list, ordered by category, filtered by the dropdown -->
      <section v-if="!shopLoading && !shopError" id="lw-catalog" class="lw-block lw-catalog">
        <div class="lw-sec-head">
          <h2 class="lw-shop-title"><span class="lw-title-bar"></span>All Products</h2>
          <p class="lw-shop-sub">Tap a product to see its available sizes. Prices are subject to change — please call to confirm availability.</p>
          <ul class="lw-legend" aria-label="Stock status guide">
            <li><span class="lw-shop-dot lw-shop-dot--ok"></span><strong>In Stock</strong> plenty available</li>
            <li><span class="lw-shop-dot lw-shop-dot--low"></span><strong>Low Stock</strong> running low, call to reserve</li>
            <li><span class="lw-shop-dot lw-shop-dot--oos"></span><strong>Out of Stock</strong> none available right now</li>
          </ul>
        </div>

        <div class="lw-catalog-bar">
          <div class="lw-shop-toolbar">
            <div class="lw-shop-search">
              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
              <input v-model="shopSearch" type="text" placeholder="Search products, size, or SKU…" />
            </div>
            <div class="lw-dd" ref="catWrap">
              <button
                type="button"
                class="lw-dd-trigger"
                :class="{ 'lw-dd-trigger--open': catOpen, 'lw-dd-trigger--active': selectedCategory !== 'all' }"
                aria-haspopup="listbox"
                :aria-expanded="catOpen"
                aria-controls="lw-dd-list"
                @click="toggleCat"
                @keydown="onCatKeydown"
              >
                <i class="fa-solid fa-layer-group lw-dd-lead" aria-hidden="true"></i>
                <span class="lw-dd-value">{{ selectedCategoryLabel }}</span>
                <span class="lw-dd-badge">{{ selectedCategoryCount }}</span>
                <i class="fa-solid fa-chevron-down lw-dd-chevron" aria-hidden="true"></i>
              </button>

              <transition name="lw-dropdown">
                <ul v-if="catOpen" id="lw-dd-list" ref="catListEl" class="lw-dd-panel" role="listbox" aria-label="Filter by category">
                  <template v-for="(c, i) in categoryChoices" :key="c.value">
                    <li v-if="i === 1" class="lw-dd-sep" role="separator"></li>
                    <li
                      class="lw-dd-opt"
                      :class="{ 'lw-dd-opt--selected': selectedCategory === c.value }"
                      role="option"
                      :aria-selected="selectedCategory === c.value"
                      :data-active="i === catActive ? 'true' : 'false'"
                      @click="pickCategory(c.value)"
                      @mousemove="catActive = i"
                    >
                      <span class="lw-dd-opt-name">{{ c.name }}</span>
                      <span class="lw-dd-opt-count">{{ c.count }}</span>
                      <i class="fa-solid fa-check lw-dd-check" :class="{ 'lw-dd-check--on': selectedCategory === c.value }" aria-hidden="true"></i>
                    </li>
                  </template>
                </ul>
              </transition>
            </div>
            <button v-if="shopSearch || selectedCategory !== 'all'" type="button" class="lw-clear-btn" @click="clearFilters">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
            </button>
          </div>
          <p class="lw-shop-count">
            {{ filteredProducts.length }} product{{ filteredProducts.length === 1 ? '' : 's' }} found
          </p>
        </div>

        <div v-if="filteredProducts.length === 0" class="lw-shop-empty">
          <i class="fa-solid fa-box-open"></i>
          <p>No products found</p>
          <button v-if="shopSearch || selectedCategory !== 'all'" type="button" class="lw-shop-retry" @click="clearFilters">Clear filters</button>
        </div>

        <div class="lw-shop-grid lw-shop-grid--groups">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            class="lw-shop-card lw-shop-card--click"
            :class="{ 'lw-shop-card--oos': shopStockStatus(product) === 'oos' }"
            tabindex="0"
            role="button"
            @click="openProduct(product)"
            @keydown.enter="openProduct(product)"
          >
            <div class="lw-shop-card-img" :class="{ 'lw-shop-card-img--photo': product.image }">
              <span class="lw-shop-stock-pill">
                <span class="lw-shop-dot" :class="`lw-shop-dot--${shopStockStatus(product)}`"></span>
                {{ shopStockShort(product) }}
              </span>
              <img v-if="product.image" :src="product.image" :alt="product.name" @error="onShopImgError(product)" />
              <div v-else class="lw-shop-card-img-placeholder"><i class="fa-solid fa-cube"></i></div>
            </div>
            <div class="lw-shop-card-body">
              <p class="lw-shop-card-name" :title="product.name">{{ product.name }}</p>
              <p v-if="product.variants.length" class="lw-shop-card-sizes">
                {{ product.variants.length }} size{{ product.variants.length === 1 ? '' : 's' }} available<template v-if="hasPriceRange(product)"> · price varies</template>
              </p>
              <p class="lw-shop-card-avail" :class="`lw-shop-card-avail--${shopStockStatus(product)}`">
                <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
                {{ availableText(product) }}
              </p>
              <p class="lw-shop-card-price">
                <span v-if="priceLabel(product)" class="lw-shop-price-from">{{ priceLabel(product) }}</span>
                <span class="lw-shop-price-currency">₱</span>{{ product.price.toFixed(2) }}
                <span class="lw-shop-price-unit">{{ priceUnit(product) }}</span>
              </p>
              <span class="lw-shop-card-cta">
                {{ ctaLabel(product) }}
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- HOW TO ORDER -->
      <section v-if="!shopLoading && !shopError" id="lw-how" class="lw-block lw-block--tight">
        <div class="lw-sec-head">
          <h2 class="lw-shop-title"><span class="lw-title-bar"></span>How to Order</h2>
          <p class="lw-shop-sub">Three simple steps.</p>
        </div>
        <div class="lw-steps">
          <div class="lw-step">
            <span class="lw-step-num">1</span>
            <div>
              <p class="lw-step-title">Browse &amp; note your items</p>
              <p class="lw-step-text">Pick the materials, sizes and quantities you need from the list.</p>
            </div>
          </div>
          <div class="lw-step">
            <span class="lw-step-num">2</span>
            <div>
              <p class="lw-step-title">Confirm price &amp; stock</p>
              <p class="lw-step-text">Call or email us to confirm availability before you come.</p>
            </div>
          </div>
          <div class="lw-step">
            <span class="lw-step-num">3</span>
            <div>
              <p class="lw-step-title">Visit our store</p>
              <p class="lw-step-text">Drop by our shop in Butuan City — directions below.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- VISIT US / MAP -->
      <section id="lw-visit" class="lw-visit">
        <div class="lw-visit-head">
          <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
          <div>
            <h3 class="lw-visit-title">Visit Shem Steel Construction Company</h3>
            <p class="lw-visit-sub">We're here to support your projects with quality materials and reliable service.</p>
          </div>
        </div>

        <div class="lw-visit-grid">
          <div class="lw-visit-map">
            <iframe
              :src="mapEmbedSrc"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              title="Shem Steel Construction Company location"
            ></iframe>
          </div>

          <div class="lw-visit-info">
            <div class="lw-visit-row">
              <span class="lw-visit-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>
              <div>
                <p class="lw-visit-label">Address</p>
                <p class="lw-visit-value">{{ SHOP_ADDRESS }}</p>
              </div>
            </div>
            <div class="lw-visit-row">
              <span class="lw-visit-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
              <div>
                <p class="lw-visit-label">Phone</p>
                <p class="lw-visit-value">{{ SHOP_PHONE }}</p>
              </div>
            </div>
            <div class="lw-visit-row">
              <span class="lw-visit-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
              <div>
                <p class="lw-visit-label">Email</p>
                <p class="lw-visit-value">support@sheems.example</p>
              </div>
            </div>
            <div class="lw-visit-row">
              <span class="lw-visit-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
              <div>
                <p class="lw-visit-label">Business Hours</p>
                <p class="lw-visit-value">Mon – Sat, 7:00 AM – 5:00 PM<br />Sunday: Closed</p>
              </div>
            </div>

            <a :href="directionsUrl" target="_blank" rel="noopener" class="lw-visit-directions">
              <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>
              Get Directions
            </a>
          </div>

          <div class="lw-visit-photo">
            <img
              src="@/assets/store-front.png"
              alt="Shem Steel Construction Supply storefront"
              class="lw-visit-photo-img"
            />
            <div class="lw-visit-photo-brand">
              <span class="lw-visit-photo-name">SHEM STEEL</span>
              <span class="lw-visit-photo-sub">Construction Supply</span>
            </div>
          </div>
        </div>
      </section>

      <!-- FOOTER (scrolls with the page so it never covers products on phones) -->
    <footer class="lw-footer">
      <span>© {{ year }} Shem Steel Construction Company. All rights reserved.</span>
      <div class="lw-footer-right">
        <span>support@sheems.example</span>
        <span class="lw-sep" aria-hidden="true">·</span>
        <span>{{ SHOP_PHONE }}</span>
      </div>
    </footer>

    </main>

    <!-- BACK TO TOP -->
    <transition name="fade">
      <button v-if="showTop" type="button" class="lw-top-btn" @click="scrollToTop" aria-label="Back to top">
        <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
      </button>
    </transition>

    <!-- PRODUCT DETAIL MODAL — wide, two panels: product info left, every size right -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="selectedProduct" class="lw-modal-backdrop" @click.self="selectedProduct = null">
          <div class="lw-modal lw-product-modal" role="dialog" aria-modal="true" aria-labelledby="lw-prod-title">
            <button type="button" class="lw-login-modal-close lw-pm-close" @click="selectedProduct = null" aria-label="Close">
              <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="lw-pm-layout">
              <!-- LEFT: product summary -->
              <aside class="lw-pm-side">
                <div class="lw-pm-img" :class="{ 'lw-pm-img--empty': !selectedProduct.image }">
                  <img v-if="selectedProduct.image" :src="selectedProduct.image" :alt="selectedProduct.name" />
                  <i v-else class="fa-solid fa-cube" aria-hidden="true"></i>
                </div>

                <p class="lw-pm-cat">{{ selectedProduct.categoryName }}</p>
                <h2 id="lw-prod-title" class="lw-pm-title">{{ selectedProduct.name }}</h2>
                <p class="lw-pm-sku">
                  <i class="fa-solid fa-barcode" aria-hidden="true"></i>
                  {{ selectedProduct.sku || selectedProduct.id }}
                </p>

                <!-- Total available (all sizes) -->
                <div class="lw-pm-stock" :class="`lw-pm-stock--${shopStockStatus(selectedProduct)}`">
                  <span class="lw-shop-dot" :class="`lw-shop-dot--${shopStockStatus(selectedProduct)}`"></span>
                  <span class="lw-pm-stock-text">
                    <strong>{{ availableText(selectedProduct) }}</strong>
                    <small v-if="selectedProduct.variants.length > 1">total across all sizes</small>
                  </span>
                </div>
                <p v-if="shopStockStatus(selectedProduct) === 'low'" class="lw-pm-lowhint">
                  <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                  {{ LOW_STOCK_HINT }}
                </p>

                <div class="lw-pm-from-wrap">
                  <span class="lw-pm-from-label">
                    {{ priceLabel(selectedProduct) || 'Price' }}
                  </span>
                  <p class="lw-pm-from">
                    <span class="lw-shop-price-currency">₱</span>{{ selectedProduct.price.toFixed(2) }}
                    <span class="lw-shop-price-unit">{{ priceUnit(selectedProduct) }}</span>
                  </p>
                  <p v-if="hasPriceRange(selectedProduct)" class="lw-pm-vary">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    Price depends on the size. See each size's price in the list.
                  </p>
                </div>

                <p class="lw-pm-note">Prices and stock are subject to change. Please call {{ SHOP_PHONE }} to confirm availability.</p>
              </aside>

              <!-- RIGHT: available sizes -->
              <section class="lw-pm-main">
                <div class="lw-pm-main-head">
                  <div class="lw-pm-main-title">
                    <p class="lw-pm-head">Available sizes &amp; prices</p>
                    <p v-if="selectedProduct.variants.length" class="lw-pm-count">
                      <span class="lw-shop-dot lw-shop-dot--ok"></span>
                      {{ inStockSizes(selectedProduct) }} of {{ selectedProduct.variants.length }} sizes in stock
                    </p>
                  </div>

                  <div v-if="selectedProduct.variants.length > 6" class="lw-pm-tools">
                    <div class="lw-pm-search">
                      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                      <input v-model="sizeSearch" type="text" placeholder="Find a size…" aria-label="Find a size" />
                    </div>
                    <label class="lw-pm-toggle">
                      <input type="checkbox" v-model="onlyInStock" />
                      <span>In stock only</span>
                    </label>
                  </div>
                </div>

                <ul v-if="selectedProduct.variants.length" class="lw-legend lw-legend--modal" aria-label="Stock status guide">
                  <li><span class="lw-shop-dot lw-shop-dot--ok"></span><strong>In Stock</strong> plenty available</li>
                  <li><span class="lw-shop-dot lw-shop-dot--low"></span><strong>Low Stock</strong> running low</li>
                  <li><span class="lw-shop-dot lw-shop-dot--oos"></span><strong>Out of Stock</strong></li>
                </ul>

                <div v-if="selectedProduct.variants.length" class="lw-pm-list">
                  <div
                    v-for="v in visibleVariants"
                    :key="v.id"
                    class="lw-pm-row"
                    :class="`lw-pm-row--${stockStatusOf(v)}`"
                  >
                    <div class="lw-pm-label" :title="v.label">{{ v.label }}</div>
                    <div class="lw-pm-price">₱{{ v.price.toFixed(2) }}</div>
                    <div class="lw-pm-status">
                      <span class="lw-shop-dot" :class="`lw-shop-dot--${stockStatusOf(v)}`"></span>
                      {{ stockLabelOf(v) }}
                    </div>
                    <div v-if="stockStatusOf(v) === 'low'" class="lw-pm-rowhint">
                      <i class="fa-solid fa-phone" aria-hidden="true"></i> Call to reserve
                    </div>
                  </div>

                  <div v-if="visibleVariants.length === 0" class="lw-pm-empty">
                    <i class="fa-solid fa-ruler-combined" aria-hidden="true"></i>
                    <p>No sizes match your filter.</p>
                    <button type="button" class="lw-shop-retry" @click="sizeSearch = ''; onlyInStock = false">Reset</button>
                  </div>
                </div>
                <div v-else class="lw-pm-list">
                  <div class="lw-pm-row" :class="`lw-pm-row--${shopStockStatus(selectedProduct)}`">
                    <div class="lw-pm-label">Standard</div>
                    <div class="lw-pm-price">₱{{ selectedProduct.price.toFixed(2) }}</div>
                    <div class="lw-pm-status">
                      <span class="lw-shop-dot" :class="`lw-shop-dot--${shopStockStatus(selectedProduct)}`"></span>
                      {{ shopStockLabel(selectedProduct) }}
                    </div>
                  </div>
                </div>
              </section>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- LOGIN MODAL -->
    <Teleport to="body">
      <transition name="fade">
        <div
          v-if="showLoginModal"
          ref="loginModalBackdrop"
          class="lw-modal-backdrop"
          tabindex="-1"
          @click.self="closeLoginModal"
          @keydown.esc="closeLoginModal"
          @keydown="onLoginKeydown"
        >
          <div class="lw-card lw-login-card" role="dialog" aria-modal="true" aria-labelledby="lw-login-title">
            <button type="button" class="lw-login-modal-close" @click="closeLoginModal" aria-label="Close">
              <i class="fa-solid fa-xmark"></i>
            </button>

            <!-- Card header -->
            <div class="lw-card-head">
              <div class="lw-card-brand">
                <img src="@/assets/cutout_logo.png" alt="" class="lw-card-logo-img" aria-hidden="true" />
                <div>
                  <div class="lw-card-title" id="lw-login-title">Shem Steel</div>
                  <div class="lw-card-title-sub">Management Portal</div>
                </div>
              </div>
            </div>

            <!-- Divider label -->
            <div class="lw-card-divider">
              <span>Sign in to your account</span>
            </div>

            <!-- Card body -->
            <div class="lw-card-body">

              <Transition name="lw-shake">
                <div v-if="error" class="lw-error" role="alert">
                  <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                  {{ error }}
                </div>
              </Transition>

              <form @submit.prevent="submitLogin" class="lw-form" novalidate>

                <div class="lw-field">
                  <label for="lw-email" class="lw-lbl">Email address</label>
                  <div class="lw-input-wrap">
                    <i class="fa-solid fa-envelope lw-ico" aria-hidden="true"></i>
                    <input
                      id="lw-email"
                      ref="emailInputEl"
                      v-model="email"
                      type="email"
                      required
                      placeholder="you@sheems.example"
                      class="lw-inp"
                      :class="{ 'lw-inp--error': fieldErrors.email }"
                      autocomplete="email"
                      :aria-invalid="!!fieldErrors.email"
                      :aria-describedby="fieldErrors.email ? 'lw-email-error' : undefined"
                      @blur="validateEmail"
                      @input="fieldErrors.email = ''"
                    />
                  </div>
                  <p v-if="fieldErrors.email" id="lw-email-error" class="lw-field-error">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    {{ fieldErrors.email }}
                  </p>
                </div>

                <!--
                  UX FIX: the "Forgot password?" button now comes AFTER the password
                  input in the DOM (it is positioned visually in the top-right of the
                  field with CSS). Previously it sat before the input, so pressing Tab
                  from the email field landed on "Forgot password?" instead of the
                  password field.
                -->
                <div class="lw-field lw-field--pass">
                  <label for="lw-pass" class="lw-lbl">Password</label>
                  <div class="lw-input-wrap">
                    <i class="fa-solid fa-lock lw-ico" aria-hidden="true"></i>
                    <input
                      id="lw-pass"
                      :type="showPassword ? 'text' : 'password'"
                      v-model="password"
                      placeholder="••••••••"
                      class="lw-inp"
                      :class="{ 'lw-inp--error': fieldErrors.password }"
                      autocomplete="current-password"
                      :aria-invalid="!!fieldErrors.password"
                      :aria-describedby="fieldErrors.password ? 'lw-pass-error' : undefined"
                      @blur="validatePassword"
                      @input="fieldErrors.password = ''"
                    />
                    <!-- UX FIX: tabindex="-1" so Tab goes straight past the eye icon. Still clickable. -->
                    <button
                      type="button"
                      class="lw-eye"
                      tabindex="-1"
                      @click="showPassword = !showPassword"
                      :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    >
                      <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" aria-hidden="true"></i>
                    </button>
                  </div>
                  <p v-if="fieldErrors.password" id="lw-pass-error" class="lw-field-error">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    {{ fieldErrors.password }}
                  </p>

                  <button type="button" class="lw-forgot" @click="showForgotHelp = true">Forgot password?</button>
                </div>

                <label class="lw-remember">
                  <input type="checkbox" v-model="remember" class="lw-chk" />
                  <span>Keep me signed in for 30 days</span>
                </label>

                <button type="submit" class="lw-btn" :disabled="loading">
                  <i v-if="loading" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                  <i v-else class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
                  <span>{{ loading ? 'Signing in…' : 'Sign in' }}</span>
                </button>

              </form>
            </div>

            <!-- Card footer -->
            <div class="lw-card-foot">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              Need access? Contact your system administrator.
            </div>

          </div>
        </div>
      </transition>
    </Teleport>

    <!-- FORGOT-PASSWORD HELP MODAL — layered above the login modal -->
    <transition name="fade">
      <div v-if="showForgotHelp" class="lw-modal-backdrop lw-modal-backdrop--forgot" @click.self="showForgotHelp = false">
        <div class="lw-modal" role="dialog" aria-modal="true" aria-labelledby="lw-forgot-title">
          <div class="lw-modal-icon">
            <i class="fa-solid fa-key" aria-hidden="true"></i>
          </div>
          <h2 id="lw-forgot-title" class="lw-modal-title">Password resets go through your administrator</h2>
          <p class="lw-modal-desc">
            This system doesn't have a self-service reset yet. Reach out and they'll get you back in.
          </p>
          <div class="lw-modal-contact">
            <a href="mailto:support@sheems.example" class="lw-modal-contact-row">
              <i class="fa-solid fa-envelope" aria-hidden="true"></i>
              support@sheems.example
            </a>
            <a :href="`tel:${SHOP_PHONE_TEL}`" class="lw-modal-contact-row">
              <i class="fa-solid fa-phone" aria-hidden="true"></i>
              {{ SHOP_PHONE }}
            </a>
          </div>
          <button type="button" class="lw-modal-close" @click="showForgotHelp = false">Got it</button>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useDarkMode } from '@/composables/useDarkMode'
import api from '@/api/axios'

const auth   = useAuthStore()
const router = useRouter()

const year         = new Date().getFullYear()
const email        = ref('')
const password     = ref('')
const remember     = ref(false)
const showPassword = ref(false)
const loading      = ref(false)

/* Forgot-password help modal */
const showForgotHelp = ref(false)

/*
 * `error` is reserved for problems that aren't tied to one specific input:
 * network failure, server errors, a disabled/locked account, rate limiting.
 * Anything about a single field lives in `fieldErrors` and renders right
 * under that input instead.
 */
const error       = ref('')
const fieldErrors = ref({ email: '', password: '' })

const { darkMode, toggleDark, initDarkMode } = useDarkMode()
onMounted(initDarkMode)

/* Settings-gear dropdown that houses the Appearance toggle */
const showThemeMenu = ref(false)
function closeThemeMenuOnOutsideClick() {
  showThemeMenu.value = false
}
onMounted(() => window.addEventListener('click', closeThemeMenuOnOutsideClick))
onBeforeUnmount(() => window.removeEventListener('click', closeThemeMenuOnOutsideClick))

/* ─── PRODUCT DETAIL MODAL ───────────────── */
const selectedProduct = ref<ShopProduct | null>(null)
const sizeSearch      = ref('')
const onlyInStock     = ref(false)

function openProduct(p: ShopProduct) { selectedProduct.value = p }

// Start every product with a clean size filter.
watch(selectedProduct, () => {
  sizeSearch.value = ''
  onlyInStock.value = false
})

// Sizes shown in the modal after the optional search / in-stock filter.
const visibleVariants = computed<ShopVariant[]>(() => {
  const p = selectedProduct.value
  if (!p) return []
  const q = sizeSearch.value.trim().toLowerCase()
  return p.variants.filter(v =>
    (!onlyInStock.value || v.stock > 0) &&
    (!q || v.label.toLowerCase().includes(q))
  )
})

/* ─── LOGIN MODAL ────────────────────────── */
const showLoginModal     = ref(false)
const loginModalBackdrop = ref<HTMLElement | null>(null)
const emailInputEl       = ref<HTMLInputElement | null>(null)
const loginBtnEl         = ref<HTMLElement | null>(null)

function openLoginModal() {
  error.value = ''
  fieldErrors.value = { email: '', password: '' }
  showLoginModal.value = true
  // UX FIX: focus the email field right away so the user can start typing.
  // Escape still works because the keydown bubbles up to the backdrop.
  nextTick(() => {
    if (emailInputEl.value) emailInputEl.value.focus()
    else loginModalBackdrop.value?.focus()
  })
}
function closeLoginModal() {
  if (loading.value) return // don't let people dismiss mid-submit
  showLoginModal.value = false
  // UX FIX: hand focus back to the Login button that opened the modal.
  nextTick(() => loginBtnEl.value?.focus())
}

/*
 * UX FIX: keep Tab / Shift+Tab inside the login card while it is open,
 * so focus never escapes to the page behind the modal.
 */
function onLoginKeydown(e: KeyboardEvent) {
  if (e.key !== 'Tab' || showForgotHelp.value) return
  const card = loginModalBackdrop.value?.querySelector<HTMLElement>('.lw-login-card')
  if (!card) return

  const focusables = Array.from(
    card.querySelectorAll<HTMLElement>('button, input, a[href]')
  ).filter(el => !el.hasAttribute('disabled') && el.tabIndex >= 0)
  if (!focusables.length) return

  const first  = focusables[0]
  const last   = focusables[focusables.length - 1]
  const active = document.activeElement as HTMLElement | null

  if (e.shiftKey && (active === first || active === loginModalBackdrop.value)) {
    e.preventDefault()
    last?.focus()
  } else if (!e.shiftKey && active === last) {
    e.preventDefault()
    first?.focus()
  }
}

// Escape closes the top-most modal first.
function onGlobalKeydown(e: KeyboardEvent) {
  if (e.key !== 'Escape') return
  if (catOpen.value) { catOpen.value = false; return }
  if (showForgotHelp.value) { showForgotHelp.value = false; return }
  if (showLoginModal.value) { closeLoginModal(); return }
  if (selectedProduct.value) { selectedProduct.value = null; return }
}
onMounted(() => window.addEventListener('keydown', onGlobalKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onGlobalKeydown))

/* ─── VALIDATION ─────────────────────────── */
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

function validateEmail() {
  if (!email.value.trim()) {
    fieldErrors.value.email = 'Enter your email address.'
  } else if (!EMAIL_RE.test(email.value.trim())) {
    fieldErrors.value.email = 'That email address doesn’t look right.'
  } else {
    fieldErrors.value.email = ''
  }
  return !fieldErrors.value.email
}

function validatePassword() {
  if (!password.value) {
    fieldErrors.value.password = 'Enter your password.'
  } else {
    fieldErrors.value.password = ''
  }
  return !fieldErrors.value.password
}

function validateForm() {
  const emailOk    = validateEmail()
  const passwordOk = validatePassword()
  return emailOk && passwordOk
}

/* ─── SUBMIT ─────────────────────────────── */
async function submitLogin() {
  error.value = ''
  fieldErrors.value = { email: '', password: '' }

  if (!validateForm()) return

  loading.value = true
  try {
    await auth.login({ email: email.value.trim(), password: password.value, remember: remember.value })
    if (auth.user?.must_change_password) {
      router.push('/set-password')
    } else {
      router.push('/home')
    }
  } catch (err: any) {
    applyLoginError(err)
  } finally {
    loading.value = false
  }
}

const GENERIC_SERVER_MESSAGES = [
  'validation failed',
  'bad request',
  'invalid request',
  'request failed',
  'error',
  'failed',
  'unknown error',
  'something went wrong',
]

function isGenericServerMessage(msg?: string | null): boolean {
  if (!msg) return true
  const normalized = msg.trim().toLowerCase().replace(/\.+$/, '')
  return GENERIC_SERVER_MESSAGES.includes(normalized)
}

function applyLoginError(err: any) {
  if (!err?.response) {
    error.value = 'Can’t reach the server. Check your internet connection and try again.'
    return
  }

  const status      = err.response.status
  const data        = err.response.data ?? {}
  const rawMsg      = data.message as string | undefined
  const code        = (data.code || data.error_code || data.errorCode) as string | undefined
  const field       = (data.field) as string | undefined
  const usableMsg   = !isGenericServerMessage(rawMsg) ? rawMsg : undefined

  if (code === 'INVALID_PASSWORD' || field === 'password') {
    fieldErrors.value.password = usableMsg || 'Incorrect password.'
    return
  }
  if (code === 'ACCOUNT_NOT_FOUND' || code === 'USER_NOT_FOUND' || field === 'email') {
    fieldErrors.value.email = usableMsg || 'No account found with that email.'
    return
  }
  if (code === 'ACCOUNT_DISABLED' || code === 'ACCOUNT_LOCKED') {
    error.value = usableMsg || 'This account has been disabled. Contact your administrator.'
    return
  }

  switch (status) {
    case 400:
    case 422:
      error.value = usableMsg || 'Incorrect email or password.'
      break
    case 401:
      error.value = usableMsg || 'Incorrect email or password.'
      break
    case 403:
      error.value = usableMsg || 'This account has been disabled. Contact your administrator.'
      break
    case 404:
      error.value = usableMsg || 'Incorrect email or password.'
      break
    case 423:
      error.value = usableMsg || 'This account is temporarily locked after too many attempts. Try again later.'
      break
    case 429:
      error.value = usableMsg || 'Too many attempts. Please wait a moment and try again.'
      break
    default:
      if (status >= 500) {
        error.value = 'Something went wrong on our end. Please try again shortly.'
      } else {
        error.value = usableMsg || 'Login failed. Please try again.'
      }
  }
}

/* ─── SHOP CONTACT / HOURS ───────────────────────────────── */
const SHOP_PHONE     = '+63 912 345 6789'
const SHOP_PHONE_TEL = '+639123456789'

// "Open now" is computed in the shop's own timezone (Philippines), not the
// visitor's. Mon–Sat, 7:00 AM – 5:00 PM; Sunday closed.
const openNow = ref<boolean | null>(null)
function computeOpenNow() {
  const manila = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Manila' }))
  const day  = manila.getDay()
  const mins = manila.getHours() * 60 + manila.getMinutes()
  openNow.value = day !== 0 && mins >= 7 * 60 && mins < 17 * 60
}
onMounted(computeOpenNow)

/* ─── PUBLIC PRODUCT CATALOG ─────────────────────────────────
 * Loaded from the PUBLIC routes (/api/public/product-list and
 * /api/public/best-sellers). They sit outside auth:sanctum, so visitors
 * who aren't signed in can read them. Cost prices are never sent.
 * Stock numbers (per size + product total) ARE sent so the shop can
 * show how many units are available.
 * ────────────────────────────────────────────────────────── */
interface ShopVariant {
  id: number | string
  label: string
  price: number
  stock: number            // exact units available for this size
  status?: string          // 'ok' | 'low' | 'oos' — decided by the server from the reorder level
}
interface ShopProduct {
  id: number | string
  sku: string              // real SKU (e.g. TIG-00001); empty if not sent
  name: string
  price: number            // lowest size price when the product has sizes
  stock: number            // product total = units available across all sizes
  status?: string          // 'ok' | 'low' | 'oos' — decided by the server from the reorder level
  image: string | null
  category: number | string | null
  categoryName: string     // "Uncategorized" when the API sends none
  pricingType: 'sqm' | 'piece'
  featured: boolean
  variants: ShopVariant[]
}

const BEST_SELLER_COUNT = 6
const UNCATEGORIZED = 'Uncategorized'

const shopProducts     = ref<ShopProduct[]>([])
const shopLoading      = ref(false)
const shopError        = ref('')
const shopSearch       = ref('')
const selectedCategory = ref<string>('all')

function normalizeShopProduct(p: any): ShopProduct {
  const pricingTypeRaw = (p.PricingType ?? p.pricing_type ?? 'piece').toString().toLowerCase()

  const variants: ShopVariant[] = (p.variants ?? []).map((v: any) => ({
    id: v.VariantID ?? v.id,
    label: v.Label ?? v.label ?? 'Standard',
    price: Number(v.SellingPrice ?? v.price ?? 0),
    stock: Math.max(0, Number(v.Stock ?? v.QuantityOnHand ?? 0)),
    status: v.StockStatus ?? v.stock_status,
  }))

  const basePrice = Number(p.SellingPrice ?? p.price ?? 0)
  const minVariantPrice = variants.length ? Math.min(...variants.map(v => v.price)) : basePrice

  const categoryName =
    String(p.CategoryName ?? p.category?.CategoryName ?? p.categoryName ?? '').trim() || UNCATEGORIZED

  return {
    id: p.ProductID ?? p.id,
    sku: String(p.SKU ?? p.sku ?? '').trim(),
    name: p.ProductName ?? p.name ?? 'Unnamed product',
    price: minVariantPrice,
    stock: Math.max(0, Number(p.inventory?.QuantityOnHand ?? p.Stock ?? p.stock ?? 0)),
    status: p.StockStatus ?? p.stock_status,
    image: p.ImageURL ?? p.image ?? null,
    category: p.CategoryID ?? (typeof p.category === 'object' ? null : p.category) ?? null,
    categoryName,
    pricingType: pricingTypeRaw === 'sqm' ? 'sqm' : 'piece',
    featured: Boolean(p.IsFeatured ?? p.is_featured ?? p.featured ?? false),
    variants,
  }
}

async function loadShopCatalog() {
  shopLoading.value = true
  shopError.value = ''
  try {
    const res = await api.get('/api/public/product-list')
    shopProducts.value = (res.data || []).map(normalizeShopProduct)
  } catch (e) {
    shopError.value = 'Unable to load products right now. Please try again shortly.'
    console.error('loadShopCatalog error:', e)
  } finally {
    shopLoading.value = false
  }
}
onMounted(loadShopCatalog)

function onShopImgError(product: ShopProduct) { product.image = null }

// Status (ok / low / oos) comes from the server, which compares the quantity
// against the item's REORDER LEVEL. The exact number of units is shown too.
function formatQty(n: number) {
  return Math.max(0, Math.floor(n)).toLocaleString('en-US')
}
type StockStatus = 'ok' | 'low' | 'oos'
// Works for a product or a size: anything with { stock, status }.
function stockStatusOf(item: { stock: number; status?: string }): StockStatus {
  if (item.stock <= 0) return 'oos'
  return item.status === 'low' ? 'low' : 'ok'
}
// Short label with the number, e.g. "In Stock · 24", "Low Stock · 3 left".
function stockLabelOf(item: { stock: number; status?: string }) {
  const s = stockStatusOf(item)
  if (s === 'oos') return 'Out of Stock'
  if (s === 'low') return `Low Stock · ${formatQty(item.stock)} left`
  return `In Stock · ${formatQty(item.stock)}`
}
// Plain sentence for the card / modal, e.g. "24 available", "Low stock · only 3 left".
function availableText(item: { stock: number; status?: string }) {
  const s = stockStatusOf(item)
  if (s === 'oos') return 'Out of stock'
  if (s === 'low') return `Low stock · only ${formatQty(item.stock)} left`
  return `${formatQty(item.stock)} available`
}
// Extra explanation shown only for low-stock items.
const LOW_STOCK_HINT = 'Running low. Call us to reserve before it runs out.'
const shopStockStatus = (p: ShopProduct) => stockStatusOf(p)
const shopStockLabel  = (p: ShopProduct) => stockLabelOf(p)
// Status word only (the count is shown on the "available" line below it).
const shopStockShort  = (p: ShopProduct) => {
  const st = stockStatusOf(p)
  return st === 'oos' ? 'Out of Stock' : st === 'low' ? 'Low Stock' : 'In Stock'
}

// True when the product's sizes have different prices (shows "From ₱…").
function hasPriceRange(p: ShopProduct) {
  return new Set(p.variants.map(v => v.price)).size > 1
}
// Context label shown above the price so customers don't assume every size costs the same.
function priceLabel(p: ShopProduct): string {
  if (p.variants.length > 1 && hasPriceRange(p)) return 'Starts at'
  return ''
}
function priceUnit(p: ShopProduct) {
  return p.pricingType === 'sqm' ? '/ sqm' : '/ piece'
}
// Small call-to-action on each card so visitors know it opens the size list.
function ctaLabel(p: ShopProduct) {
  return p.variants.length > 1 ? 'View all sizes' : 'View details'
}
function inStockSizes(p: ShopProduct) {
  return p.variants.filter(v => v.stock > 0).length
}

/* ─── SEARCH + CATEGORY ORDER ────────────────────────────── */
const normalizedShopSearch = computed(() => shopSearch.value.trim().toLowerCase())

function compareCategoryNames(a: string, b: string) {
  // "Uncategorized" always last, everything else A–Z.
  if (a === UNCATEGORIZED && b !== UNCATEGORIZED) return 1
  if (b === UNCATEGORIZED && a !== UNCATEGORIZED) return -1
  return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' })
}

// Dropdown options: every category that has products, with its product count.
const categoryOptions = computed(() => {
  const counts = new Map<string, number>()
  for (const p of shopProducts.value) {
    counts.set(p.categoryName, (counts.get(p.categoryName) ?? 0) + 1)
  }
  return [...counts.entries()]
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => compareCategoryNames(a.name, b.name))
})

// If the chosen category disappears after a reload, fall back to "all".
watch(categoryOptions, (opts) => {
  if (selectedCategory.value !== 'all' && !opts.some(o => o.name === selectedCategory.value)) {
    selectedCategory.value = 'all'
  }
})

// One flat list. Ordered by category (A–Z, "Uncategorized" last), then
// out-of-stock sinks to the bottom of its category, then name A–Z.
const filteredProducts = computed(() => {
  const q = normalizedShopSearch.value
  const cat = selectedCategory.value

  return shopProducts.value
    .filter(p =>
      (cat === 'all' || p.categoryName === cat) &&
      (
        !q ||
        p.name.toLowerCase().includes(q) ||
        String(p.id).toLowerCase().includes(q) ||
        p.sku.toLowerCase().includes(q) ||
        p.categoryName.toLowerCase().includes(q) ||
        p.variants.some(v => v.label.toLowerCase().includes(q))   // also search by size
      )
    )
    .sort((a, b) =>
      compareCategoryNames(a.categoryName, b.categoryName) ||
      Number(a.stock <= 0) - Number(b.stock <= 0) ||
      a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' })
    )
})

function clearFilters() {
  shopSearch.value = ''
  selectedCategory.value = 'all'
}

/* ─── NAV: smooth scroll + back-to-top ───────────────────── */
const pageEl  = ref<HTMLElement | null>(null)
const showTop = ref(false)
function scrollToId(id: string) {
  activeSection.value = id
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
function scrollToTop() { pageEl.value?.scrollTo({ top: 0, behavior: 'smooth' }) }
// The nav (with its section links) sits outside the scrolling area, so it is always
// visible. Scroll-spy highlights the section the visitor is currently reading.
const SECTION_IDS = ['lw-catalog', 'lw-how', 'lw-visit']
const activeSection = ref('')
function updateActiveSection() {
  const root = pageEl.value
  if (!root) return
  const rootTop = root.getBoundingClientRect().top
  let current = ''
  for (const id of SECTION_IDS) {
    const el = document.getElementById(id)
    if (el && el.getBoundingClientRect().top - rootTop <= 140) current = id
  }
  if (root.scrollTop + root.clientHeight >= root.scrollHeight - 4) current = 'lw-visit'
  activeSection.value = current
}
function onPageScroll() {
  showTop.value = (pageEl.value?.scrollTop ?? 0) > 600
  updateActiveSection()
}

/* ─── CUSTOM CATEGORY DROPDOWN ───────────────────────────────
 * Replaces the native <select> (whose open list can't be styled).
 * Keyboard: ↑ ↓ Home End to move, Enter / Space to choose, Esc / Tab to close.
 * ────────────────────────────────────────────────────────── */
const catOpen   = ref(false)
const catActive = ref(0)
const catWrap   = ref<HTMLElement | null>(null)
const catListEl = ref<HTMLElement | null>(null)

const categoryChoices = computed(() => [
  { value: 'all', name: 'All categories', count: shopProducts.value.length },
  ...categoryOptions.value.map(c => ({ value: c.name, name: c.name, count: c.count })),
])
const selectedCategoryLabel = computed(() =>
  selectedCategory.value === 'all' ? 'All categories' : selectedCategory.value
)
const selectedCategoryCount = computed(() =>
  categoryChoices.value.find(c => c.value === selectedCategory.value)?.count ?? 0
)

function scrollActiveIntoView() {
  catListEl.value?.querySelector<HTMLElement>('[data-active="true"]')?.scrollIntoView({ block: 'nearest' })
}
function openCat() {
  catActive.value = Math.max(0, categoryChoices.value.findIndex(c => c.value === selectedCategory.value))
  catOpen.value = true
  nextTick(scrollActiveIntoView)
}
function closeCat() { catOpen.value = false }
function toggleCat() { catOpen.value ? closeCat() : openCat() }
function pickCategory(value: string) {
  selectedCategory.value = value
  closeCat()
}
function onCatKeydown(e: KeyboardEvent) {
  const last = categoryChoices.value.length - 1
  if (!catOpen.value) {
    if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) { e.preventDefault(); openCat() }
    return
  }
  switch (e.key) {
    case 'ArrowDown': e.preventDefault(); catActive.value = catActive.value >= last ? 0 : catActive.value + 1; nextTick(scrollActiveIntoView); break
    case 'ArrowUp':   e.preventDefault(); catActive.value = catActive.value <= 0 ? last : catActive.value - 1; nextTick(scrollActiveIntoView); break
    case 'Home':      e.preventDefault(); catActive.value = 0; nextTick(scrollActiveIntoView); break
    case 'End':       e.preventDefault(); catActive.value = last; nextTick(scrollActiveIntoView); break
    case 'Enter':
    case ' ': {
      e.preventDefault()
      const choice = categoryChoices.value[catActive.value]
      if (choice) pickCategory(choice.value)
      break
    }
    case 'Escape':    e.preventDefault(); e.stopPropagation(); closeCat(); break
    case 'Tab':       closeCat(); break
  }
}
function onCatOutsideClick(e: MouseEvent) {
  if (catOpen.value && catWrap.value && !catWrap.value.contains(e.target as Node)) closeCat()
}
onMounted(() => window.addEventListener('click', onCatOutsideClick))
onBeforeUnmount(() => window.removeEventListener('click', onCatOutsideClick))

/* ─── BEST SELLERS ───────────────────────────────────────────
 * Preferred source: GET /api/public/best-sellers (ranked by quantity sold
 * in the last 90 days). If it returns fewer than 3 items the top row falls
 * back to featured / newest in-stock products and is labelled
 * "Featured Products" so it never claims sales data it doesn't have.
 * ────────────────────────────────────────────────────────── */
const bestSellersApi = ref<ShopProduct[]>([])

async function loadBestSellers() {
  try {
    const { data } = await api.get('/api/public/best-sellers')
    const list = Array.isArray(data) ? data : (data?.products ?? data?.data ?? [])
    bestSellersApi.value = list
      .map(normalizeShopProduct)
      .filter((p: ShopProduct) => p.stock > 0)
      .slice(0, BEST_SELLER_COUNT)
  } catch {
    bestSellersApi.value = [] // endpoint not available — use the fallback
  }
}
onMounted(loadBestSellers)

const bestSellersAreReal = computed(() => bestSellersApi.value.length >= 3)

const displayedBestSellers = computed<ShopProduct[]>(() => {
  if (bestSellersAreReal.value) return bestSellersApi.value
  const inStock = shopProducts.value.filter(p => p.stock > 0)
  const featured = inStock.filter(p => p.featured)
  if (featured.length) return featured.slice(0, BEST_SELLER_COUNT)
  return [...inStock]
    .sort((a, b) => Number(b.id) - Number(a.id))
    .slice(0, BEST_SELLER_COUNT)
})

/* Best-sellers carousel scrolling */
const bsEl = ref<HTMLElement | null>(null)
const bsScroll = ref({ canPrev: false, canNext: false })

function updateBsScroll() {
  const el = bsEl.value
  if (!el) { bsScroll.value = { canPrev: false, canNext: false }; return }
  bsScroll.value = {
    canPrev: el.scrollLeft > 4,
    canNext: el.scrollLeft < el.scrollWidth - el.clientWidth - 4,
  }
}
function scrollBestSellers(direction: number) {
  const el = bsEl.value
  if (!el) return
  el.scrollBy({ left: direction * el.clientWidth * 0.85, behavior: 'smooth' })
}
watch(displayedBestSellers, () => { nextTick(updateBsScroll) }, { flush: 'post' })
onMounted(() => {
  nextTick(updateBsScroll)
  window.addEventListener('resize', updateBsScroll)
})
onBeforeUnmount(() => window.removeEventListener('resize', updateBsScroll))

/* ─── LOCATION / MAP ─────────────────────────────────────── */
const SHOP_ADDRESS = '849 Ochoa Ave, Butuan City, Agusan del Norte'
const SHOP_LAT = 8.9565024
const SHOP_LNG = 125.5325086
const mapEmbedSrc = `https://www.google.com/maps?q=SHEM+STEEL+CONSTRUCTION+SUPPLY@${SHOP_LAT},${SHOP_LNG}&output=embed`
const directionsUrl = `https://www.google.com/maps/dir/?api=1&destination=${SHOP_LAT},${SHOP_LNG}`
</script>

<!--
  Modals render via <Teleport to="body">, outside the .lw wrapper, so the
  color tokens they need are re-declared here on :root / html[data-theme="dark"].
-->
<style>
:root {
  --lw-accent:       #F07020;
  --lw-accent-dark:  #C85810;
  --lw-accent-light: #FF8A3D;

  --lw-card-bg:          #FFFFFF;
  --lw-card-border:      #E2E8F0;
  --lw-card-head-bg1:    #FAFBFC;
  --lw-card-head-bg2:    #F8FAFC;
  --lw-card-head-border: #E8EDF3;
  --lw-card-foot-bg:     #F8FAFC;

  --lw-border-soft: #E8EDF3;

  --lw-text-primary:   #0F172A;
  --lw-text-strong:    #0F172A;
  --lw-text-label:     #334155;
  --lw-text-secondary: #475569;
  --lw-text-faint:     #6B7C93;
  --lw-text-faint-3:   #94A3B8;
  --lw-text-faint-4:   #8493A8;

  --lw-input-bg:       #F8FAFC;
  --lw-input-bg-focus: #FFFFFF;
  --lw-input-border:   #CBD5E1;
  --lw-input-border-h: #94A3B8;
  --lw-placeholder:    #94A3B8;
  --lw-icon-muted:     #94A3B8;

  --lw-error-bg:     #FEF2F2;
  --lw-error-border: #FECACA;
  --lw-error-text:   #B91C1C;

  --lw-backdrop: #0F172A;
}

html[data-theme="dark"] {
  --lw-card-bg:          #1E293B;
  --lw-card-border:      #334155;
  --lw-card-head-bg1:    #26364B;
  --lw-card-head-bg2:    #1E293B;
  --lw-card-head-border: #334155;
  --lw-card-foot-bg:     #1E293B;

  --lw-border-soft: #334155;

  --lw-text-primary:   #F1F5F9;
  --lw-text-strong:    #F8FAFC;
  --lw-text-label:     #CBD5E1;
  --lw-text-secondary: #CBD5E1;
  --lw-text-faint:     #64748B;
  --lw-text-faint-3:   #64748B;
  --lw-text-faint-4:   #64748B;

  --lw-input-bg:       #0B1524;
  --lw-input-bg-focus: #1E293B;
  --lw-input-border:   #334155;
  --lw-input-border-h: #47566B;
  --lw-placeholder:    #64748B;
  --lw-icon-muted:     #64748B;

  --lw-error-bg:     rgba(244, 63, 94, 0.14);
  --lw-error-border: rgba(244, 63, 94, 0.32);
  --lw-error-text:   #FB7185;

  --lw-backdrop: #05080F;
}
</style>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700;800&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ─── TOKENS ─────────────────────────────── */
.lw {
  --lw-accent:       #F07020;
  --lw-accent-dark:  #C85810;
  --lw-accent-light: #FF8A3D;
  --lw-steel:        #3D7FC1;

  --lw-bg:           #F3F5F8;
  --lw-nav-bg:       #FFFFFF;
  --lw-nav-border:   #E2E8F0;
  --lw-hero-bg:      #F8FAFC;

  --lw-card-bg:        #FFFFFF;
  --lw-card-border:    #E2E8F0;
  --lw-card-head-bg1:  #FAFBFC;
  --lw-card-head-bg2:  #F8FAFC;
  --lw-card-head-border: #E8EDF3;
  --lw-card-foot-bg:   #F8FAFC;

  --lw-border:        #E2E8F0;
  --lw-border-soft:   #E8EDF3;
  --lw-feat-border:   #E2E8F0;
  --lw-feat-border-h: #F0B589;

  --lw-text-primary:  #0F172A;
  --lw-text-strong:   #0F172A;
  --lw-text-label:    #334155;
  --lw-text-secondary:#475569;
  --lw-text-muted:    #64748B;
  --lw-text-faint:    #6B7C93;
  --lw-text-faint-2:  #94A3B8;
  --lw-text-faint-3:  #94A3B8;
  --lw-text-faint-4:  #8493A8;

  --lw-input-bg:        #F8FAFC;
  --lw-input-bg-focus:  #FFFFFF;
  --lw-input-border:    #CBD5E1;
  --lw-input-border-h:  #94A3B8;
  --lw-placeholder:     #94A3B8;
  --lw-icon-muted:      #94A3B8;

  --lw-footer-bg:     #FFFFFF;
  --lw-footer-border: #E2E8F0;

  --lw-fade-1:      rgba(248, 247, 244, 0.97);
  --lw-fade-2:      rgba(248, 247, 244, 0.55);
  --lw-fade-3:      rgba(240, 112, 32, 0.05);

  --lw-glass-bg:     rgba(255, 255, 255, 0.90);
  --lw-glass-border: rgba(255, 255, 255, 0.7);
  --lw-glass-shadow: rgba(15, 23, 42, 0.13);

  --lw-error-bg:     #FEF2F2;
  --lw-error-border: #FECACA;
  --lw-error-text:   #B91C1C;

  --lw-backdrop: #0F172A;

  display: flex;
  flex-direction: column;
  width: 100vw;
  height: 100vh;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  background: var(--lw-bg);
  overflow: hidden;
  color: var(--lw-text-primary);
  transition: background-color 0.22s ease, color 0.22s ease;
}

html[data-theme="dark"] .lw {
  --lw-bg:           #0F172A;
  --lw-nav-bg:       #1E293B;
  --lw-nav-border:   #334155;
  --lw-hero-bg:      #0B1524;

  --lw-card-bg:        #1E293B;
  --lw-card-border:    #334155;
  --lw-card-head-bg1:  #26364B;
  --lw-card-head-bg2:  #1E293B;
  --lw-card-head-border: #334155;
  --lw-card-foot-bg:   #1E293B;

  --lw-border:        #334155;
  --lw-border-soft:   #334155;
  --lw-feat-border:   #334155;
  --lw-feat-border-h: #47566B;

  --lw-text-primary:  #F1F5F9;
  --lw-text-strong:   #F8FAFC;
  --lw-text-label:    #CBD5E1;
  --lw-text-secondary:#CBD5E1;
  --lw-text-muted:    #94A3B8;
  --lw-text-faint:    #64748B;
  --lw-text-faint-2:  #64748B;
  --lw-text-faint-3:  #64748B;
  --lw-text-faint-4:  #64748B;

  --lw-input-bg:        #0B1524;
  --lw-input-bg-focus:  #1E293B;
  --lw-input-border:    #334155;
  --lw-input-border-h:  #47566B;
  --lw-placeholder:     #64748B;
  --lw-icon-muted:      #64748B;

  --lw-footer-bg:     #1E293B;
  --lw-footer-border: #334155;

  --lw-fade-1:      rgba(15, 23, 42, 0.97);
  --lw-fade-2:      rgba(15, 23, 42, 0.6);
  --lw-fade-3:      rgba(240, 112, 32, 0.07);

  --lw-glass-bg:     rgba(30, 41, 59, 0.82);
  --lw-glass-border: rgba(255, 255, 255, 0.08);
  --lw-glass-shadow: rgba(0, 0, 0, 0.35);

  --lw-error-bg:     rgba(244, 63, 94, 0.14);
  --lw-error-border: rgba(244, 63, 94, 0.32);
  --lw-error-text:   #FB7185;

  --lw-backdrop: #05080F;
}

/* ─── NAVBAR ─────────────────────────────── */
.lw-nav {
  position: relative;
  flex-shrink: 0;
  height: 56px;
  background: var(--lw-nav-bg);
  border-bottom: 1px solid var(--lw-nav-border);
  z-index: 30;
  box-shadow: 0 1px 0 rgba(15, 23, 42, 0.02);
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.lw-nav-inner {
  height: 100%;
  padding: 0 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.lw-brand { display: flex; align-items: center; gap: 10px; }
.lw-logo-img { width: 34px; height: 34px; object-fit: fill; flex-shrink: 0; border: solid rgb(255, 255, 255) 1px; border-radius: 50px;}
.lw-brand-text { display: flex; flex-direction: column; gap: 1px; }
.lw-brand-name { font-size: 15px; font-weight: 700; color: var(--lw-text-primary); letter-spacing: -0.01em; line-height: 1.2; }
.lw-brand-sub { font-size: 9px; color: var(--lw-accent); font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; line-height: 1.3; }
.lw-nav-right { display: flex; align-items: center; gap: 10px; }
.lw-nav-hint { font-size: 12px; color: var(--lw-text-faint-2); font-weight: 500; margin-right: 4px; }

/* Appearance — quiet icon-only button */
.lw-settings-wrap { position: relative; flex-shrink: 0; }
.lw-appearance-btn {
  display: flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; padding: 0;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--lw-text-faint);
  cursor: pointer;
  opacity: 0.75;
  transition: background-color 0.18s ease, color 0.18s ease, opacity 0.18s ease;
}
.lw-appearance-btn i { font-size: 14px; transition: transform 0.35s ease; }
.lw-appearance-btn:hover,
.lw-appearance-btn:focus-visible {
  opacity: 1;
  background: var(--lw-input-bg);
  color: var(--lw-text-secondary);
}
.lw-appearance-btn:hover i { transform: rotate(45deg); }
.lw-appearance-btn:focus-visible { outline: 2px solid rgba(240, 112, 32, 0.4); outline-offset: 1px; }
.lw-appearance-btn--open {
  opacity: 1;
  background: var(--lw-input-bg);
  color: var(--lw-accent);
}

.lw-settings-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 240px;
  background: var(--lw-card-bg);
  border: 1px solid var(--lw-card-border);
  border-radius: 14px;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.10), 0 16px 40px rgba(15, 23, 42, 0.16);
  padding: 14px;
  z-index: 50;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.lw-settings-menu-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.lw-settings-menu-icon {
  width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(129, 140, 248, 0.14); color: #818CF8; font-size: 12px;
}
.lw-settings-menu-title { font-size: 12.5px; font-weight: 700; color: var(--lw-text-primary); line-height: 1.3; }
.lw-settings-menu-sub   { font-size: 10.5px; color: var(--lw-text-faint); margin-top: 1px; }
.lw-settings-menu-row {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding-top: 12px; border-top: 1px solid var(--lw-border-soft);
}
.lw-settings-menu-label { font-size: 12px; font-weight: 600; color: var(--lw-text-primary); }
.lw-settings-menu-value { font-size: 10.5px; color: var(--lw-text-faint); margin-top: 1px; }
.lw-dropdown-enter-active { transition: opacity 0.16s ease, transform 0.16s ease; }
.lw-dropdown-leave-active { transition: opacity 0.12s ease, transform 0.12s ease; }
.lw-dropdown-enter-from, .lw-dropdown-leave-to { opacity: 0; transform: translateY(-6px) scale(0.97); }

.lw-theme-toggle { background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; outline: none; flex-shrink: 0; }
.lw-theme-toggle:focus-visible .lw-pill-track { box-shadow: 0 0 0 3px rgba(240, 112, 32, 0.22), 0 0 0 1px var(--lw-accent); }
.lw-pill-track {
  position: relative; display: flex; align-items: center; justify-content: space-between;
  width: 56px; height: 26px; border-radius: 999px;
  background: var(--lw-input-bg); border: 1.5px solid var(--lw-input-border); padding: 0 3px;
  transition: background-color 0.26s ease, border-color 0.26s ease, box-shadow 0.18s ease;
}
.lw-pill-track--dark { background: #0F172A; border-color: rgba(240, 112, 32, 0.35); box-shadow: 0 0 8px rgba(240, 112, 32, 0.12); }
.lw-pill-track:hover { border-color: var(--lw-input-border-h); }
.lw-pill-icon { display: flex; align-items: center; justify-content: center; width: 15px; height: 15px; border-radius: 50%; z-index: 1; color: var(--lw-icon-muted); transition: color 0.22s ease; flex-shrink: 0; }
.lw-pill-icon--active.lw-pill-icon--sun  { color: #F59E0B; }
.lw-pill-icon--active.lw-pill-icon--moon { color: #A6B4E8; }
.lw-pill-thumb {
  position: absolute; top: 2.5px; left: 2.5px; width: 19px; height: 19px; border-radius: 50%;
  background: #FFFFFF; box-shadow: 0 1px 4px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.10);
  transition: transform 0.26s cubic-bezier(.4,0,.2,1), background-color 0.26s ease; z-index: 2;
}
.lw-pill-thumb--right { transform: translateX(28px); background: var(--lw-accent); box-shadow: 0 1px 4px rgba(0,0,0,0.28), 0 0 8px rgba(240,112,32,0.40); }

/* Login nav button */
.lw-signin-btn {
  display: flex; align-items: center; gap: 8px;
  height: 34px; padding: 0 17px;
  border: none; border-radius: 9px;
  background: linear-gradient(135deg, #F58330 0%, #F07020 45%, #D9640F 100%);
  color: #fff; font-family: inherit; font-size: 12.5px; font-weight: 700;
  cursor: pointer; letter-spacing: 0.01em; flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(240, 112, 32, 0.28);
  transition: transform 0.13s, box-shadow 0.13s, opacity 0.13s;
}
.lw-signin-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(240, 112, 32, 0.4); }
.lw-signin-btn:active { transform: translateY(0); }
.lw-signin-btn i { font-size: 11px; }

/* ─── PAGE (scrollable body) ─────────────── */
.lw-page {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: var(--lw-input-border-h) transparent;
}
.lw-page::-webkit-scrollbar { width: 9px; }
.lw-page::-webkit-scrollbar-thumb { background: var(--lw-input-border-h); border-radius: 6px; }

/* ─── HERO ───────────────────────────────── */
.lw-hero {
  position: relative;
  overflow: hidden;
  background: var(--lw-hero-bg);
  border-bottom: 1px solid var(--lw-border);
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.lw-hero-bg { position: absolute; inset: 0; overflow: hidden; }
.lw-hero-roof-img {
  position: absolute;
  inset: -3%;
  width: 106%;
  height: 106%;
  object-fit: cover;
  object-position: 78% 45%;
  opacity: 0.9;
  filter: blur(6px);
}
.lw-hero-fade {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 70% 90% at 10% 45%, var(--lw-fade-1) 0%, var(--lw-fade-2) 55%, transparent 100%),
    radial-gradient(ellipse 50% 40% at 78% 30%, var(--lw-fade-3) 0%, transparent 70%);
  pointer-events: none;
}
.lw-hero-inner {
  position: relative;
  z-index: 2;
  max-width: 1180px;
  margin: 0 auto;
  padding: 36px 32px 28px;
}
.lw-eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: 10px; font-weight: 700; color: var(--lw-accent);
  text-transform: uppercase; letter-spacing: 0.18em; margin-bottom: 18px;
}
.lw-eyebrow-bar { display: block; width: 18px; height: 2px; background: linear-gradient(90deg, var(--lw-accent), var(--lw-accent-light)); border-radius: 2px; }
.lw-h1 {
  font-family: 'Space Grotesk', 'Inter', system-ui, sans-serif;
  font-size: clamp(30px, 3.4vw, 46px);
  font-weight: 700; line-height: 1.08; color: var(--lw-text-primary);
  letter-spacing: -0.03em; margin-bottom: 16px;
  transition: color 0.22s ease;
}
.lw-h1-em {
  font-style: normal;
  color: var(--lw-accent);
}
.lw-tagline { font-size: 14px; line-height: 1.75; color: var(--lw-text-secondary); max-width: 420px; }

/* ─── QUICK INFO STRIP ───────────────────── */
.lw-strip {
  background: var(--lw-card-bg);
  border-bottom: 1px solid var(--lw-border);
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.lw-strip-inner {
  max-width: 1180px;
  margin: 0 auto;
  padding: 11px 32px;
  display: flex; flex-wrap: wrap; align-items: center;
  gap: 8px 28px;
  font-size: 12px; color: var(--lw-text-secondary);
}
.lw-strip-item { display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; }
.lw-strip-item i { color: var(--lw-accent); font-size: 11px; }
.lw-strip-item strong { color: var(--lw-text-primary); font-weight: 700; }
.lw-strip-muted { color: var(--lw-text-faint); }
.lw-strip-link { text-decoration: none; font-weight: 600; color: var(--lw-text-primary); transition: color 0.15s; }
.lw-strip-link:hover { color: var(--lw-accent); }
.lw-open-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.lw-open-dot--open   { background: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); }
.lw-open-dot--closed { background: #f43f5e; box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.2); }

/* ─── CONTENT BLOCKS ─────────────────────── */
.lw-block {
  max-width: 1180px;
  margin: 0 auto;
  padding: 26px 32px 8px;
}
.lw-block--tight { padding-top: 22px; }

.lw-sec-head { margin-bottom: 16px; }
.lw-shop-title {
  display: flex; align-items: center; gap: 10px;
  font-family: 'Space Grotesk', 'Inter', system-ui, sans-serif;
  font-size: 21px; font-weight: 700; color: var(--lw-text-primary);
  letter-spacing: -0.02em; margin-bottom: 4px;
}
.lw-title-bar { display: inline-block; width: 4px; height: 18px; border-radius: 3px; background: var(--lw-accent); }
.lw-shop-sub { font-size: 12.5px; color: var(--lw-text-secondary); padding-left: 14px; }

/* ─── CATALOG TOOLBAR (sticky) ───────────── */
.lw-catalog-bar {
  position: sticky; top: 0; z-index: 8;
  display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
  gap: 10px 16px;
  padding: 10px 0;
  margin-bottom: 18px;
  background: var(--lw-bg);
  border-bottom: 1px solid var(--lw-border-soft);
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.lw-shop-toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.lw-shop-search { position: relative; width: 300px; max-width: 100%; display: flex; align-items: center; }
.lw-shop-search i { position: absolute; left: 12px; font-size: 11px; color: var(--lw-icon-muted); pointer-events: none; }
.lw-shop-search input {
  width: 100%; height: 38px;
  border: 1.5px solid var(--lw-input-border); border-radius: 9px;
  padding: 0 12px 0 34px; font-size: 12.5px; font-family: inherit;
  color: var(--lw-text-primary); background: var(--lw-input-bg); outline: none;
  transition: border-color 0.15s, background-color 0.22s, color 0.22s;
}
.lw-shop-search input:focus { border-color: var(--lw-accent); background: var(--lw-input-bg-focus); }
.lw-shop-search input::placeholder { color: var(--lw-placeholder); }
/* ─── CATEGORY DROPDOWN (custom) ─────────── */
.lw-dd { position: relative; flex-shrink: 0; }
.lw-dd-trigger {
  display: flex; align-items: center; gap: 9px;
  height: 38px; min-width: 220px; padding: 0 11px 0 12px;
  border: 1.5px solid var(--lw-input-border); border-radius: 9px;
  background: var(--lw-input-bg); color: var(--lw-text-primary);
  font-family: inherit; font-size: 12px; font-weight: 600; text-align: left;
  cursor: pointer; outline: none;
  transition: border-color 0.15s, background-color 0.15s, box-shadow 0.15s;
}
.lw-dd-trigger:hover { border-color: var(--lw-input-border-h); background: var(--lw-input-bg-focus); }
.lw-dd-trigger--active { border-color: rgba(240, 112, 32, 0.55); }
.lw-dd-trigger--open,
.lw-dd-trigger:focus-visible {
  border-color: var(--lw-accent);
  background: var(--lw-input-bg-focus);
  box-shadow: 0 0 0 3px rgba(240, 112, 32, 0.14);
}
.lw-dd-lead { font-size: 11px; color: var(--lw-icon-muted); transition: color 0.15s; }
.lw-dd-trigger--active .lw-dd-lead,
.lw-dd-trigger--open .lw-dd-lead { color: var(--lw-accent); }
.lw-dd-value { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lw-dd-badge {
  min-width: 22px; height: 20px; padding: 0 7px;
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: 999px; background: rgba(240, 112, 32, 0.12);
  color: var(--lw-accent); font-size: 10.5px; font-weight: 800;
}
.lw-dd-chevron { font-size: 9px; color: var(--lw-icon-muted); transition: transform 0.2s ease, color 0.15s; }
.lw-dd-trigger--open .lw-dd-chevron { transform: rotate(180deg); color: var(--lw-accent); }

.lw-dd-panel {
  position: absolute; top: calc(100% + 6px); left: 0; z-index: 40;
  min-width: 100%; width: max-content; max-width: 320px; max-height: 300px;
  overflow-y: auto; list-style: none; padding: 6px;
  background: var(--lw-card-bg); border: 1px solid var(--lw-card-border);
  border-radius: 12px; transform-origin: top left;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.10), 0 16px 40px rgba(15, 23, 42, 0.16);
  scrollbar-width: thin; scrollbar-color: var(--lw-input-border-h) transparent;
}
.lw-dd-opt {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 10px; border-radius: 8px;
  font-size: 12.5px; font-weight: 600; color: var(--lw-text-secondary);
  cursor: pointer; user-select: none;
}
.lw-dd-opt[data-active="true"] { background: rgba(240, 112, 32, 0.10); color: var(--lw-text-primary); }
.lw-dd-opt--selected { color: var(--lw-accent); font-weight: 700; }
.lw-dd-opt-name { flex: 1; min-width: 0; }
.lw-dd-opt-count { min-width: 18px; text-align: right; font-size: 10.5px; font-weight: 700; color: var(--lw-text-faint); }
.lw-dd-check { width: 12px; font-size: 10px; color: var(--lw-accent); opacity: 0; }
.lw-dd-check--on { opacity: 1; }
.lw-dd-sep { height: 1px; margin: 5px 4px; background: var(--lw-border-soft); list-style: none; }

.lw-clear-btn {
  display: inline-flex; align-items: center; gap: 6px;
  height: 38px; padding: 0 13px;
  border: 1.5px solid var(--lw-input-border); border-radius: 9px;
  background: var(--lw-input-bg); color: var(--lw-text-secondary);
  font-family: inherit; font-size: 12px; font-weight: 600; cursor: pointer;
  transition: border-color 0.15s, color 0.15s;
}
.lw-clear-btn:hover { border-color: var(--lw-accent); color: var(--lw-accent); }
.lw-shop-count { font-size: 11.5px; color: var(--lw-text-faint); }

.lw-shop-state { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 40px 0; color: var(--lw-text-faint); font-size: 12.5px; }
.lw-shop-state i { font-size: 24px; }
.lw-shop-state--error { color: var(--lw-error-text); }
.lw-shop-retry {
  border: 1.5px solid var(--lw-input-border); border-radius: 8px; background: var(--lw-input-bg);
  padding: 6px 14px; font-size: 12px; font-weight: 600; color: var(--lw-text-secondary);
  cursor: pointer; font-family: inherit; transition: border-color 0.15s, color 0.15s;
}
.lw-shop-retry:hover { border-color: var(--lw-accent); color: var(--lw-accent); }

/* ─── PRODUCT CARDS ──────────────────────── */
.lw-shop-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 16px;
  margin-bottom: 0;
}
.lw-shop-grid.lw-shop-grid--groups {
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  margin-bottom: 30px;
}
.lw-shop-card {
  position: relative;
  background: var(--lw-card-bg);
  border: 1.5px solid var(--lw-feat-border);
  border-radius: 16px;
  overflow: hidden;
  display: flex; flex-direction: column;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
  transition: border-color 0.18s, box-shadow 0.18s, transform 0.18s, background-color 0.22s;
}
.lw-shop-card:hover {
  border-color: var(--lw-feat-border-h);
  box-shadow: 0 10px 24px rgba(240, 112, 32, 0.16), 0 2px 8px rgba(15, 23, 42, 0.06);
  transform: translateY(-4px);
}
.lw-shop-card--click { cursor: pointer; }
.lw-shop-card--click:focus-visible { outline: 3px solid rgba(240, 112, 32, 0.4); outline-offset: 2px; }
.lw-shop-card--oos { opacity: 0.62; }
.lw-shop-card--oos:hover { transform: none; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03); border-color: var(--lw-feat-border); }

.lw-shop-card-img {
  position: relative;
  width: 100%; aspect-ratio: 1 / 0.82;
  background: var(--lw-input-bg);
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; transition: background-color 0.22s;
}
.lw-shop-card-img--photo { background: #FFFFFF; }
.lw-shop-card-img img { width: 100%; height: 100%; object-fit: contain; padding: 6px; transition: transform 0.35s ease; }
.lw-shop-card:hover .lw-shop-card-img img { transform: scale(1.06); }
.lw-shop-card-img-placeholder { color: var(--lw-icon-muted); font-size: 28px; opacity: 0.8; }

.lw-shop-stock-pill {
  position: absolute; top: 8px; left: 8px; z-index: 2;
  display: flex; align-items: center; gap: 5px;
  padding: 4px 9px 4px 7px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.72);
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
  font-size: 9px; font-weight: 700; letter-spacing: 0.02em; color: #fff;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
}
.lw-shop-stock-pill .lw-shop-dot { width: 6px; height: 6px; flex-shrink: 0; }

.lw-shop-rank {
  position: absolute; top: 8px; right: 8px; z-index: 2;
  min-width: 26px; height: 22px; padding: 0 7px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 999px;
  background: linear-gradient(135deg, #F58330, #D9640F);
  color: #fff; font-size: 10.5px; font-weight: 800;
  box-shadow: 0 2px 8px rgba(240, 112, 32, 0.4);
}

.lw-shop-card-body { padding: 12px 13px 14px; display: flex; flex-direction: column; gap: 3px; }
.lw-shop-card-name {
  font-size: 12.5px; font-weight: 700; color: var(--lw-text-strong); line-height: 1.3;
  display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden; word-break: break-word; min-height: 32px;
}
.lw-shop-card-sku {
  display: flex; align-items: center; gap: 5px;
  font-size: 10px; color: var(--lw-text-faint);
}
.lw-shop-card-sku i { font-size: 9px; }
.lw-shop-card-sizes { font-size: 10.5px; font-weight: 600; color: var(--lw-text-secondary); }

/* Available quantity line on each card */
.lw-shop-card-avail {
  display: flex; align-items: center; gap: 5px;
  font-size: 10.5px; font-weight: 700;
}
.lw-shop-card-avail i { font-size: 9.5px; }
.lw-shop-card-avail--ok  { color: #059669; }
.lw-shop-card-avail--low { color: #EA580C; }
.lw-shop-card-avail--oos { color: #E11D48; }
html[data-theme="dark"] .lw-shop-card-avail--ok  { color: #34D399; }
html[data-theme="dark"] .lw-shop-card-avail--low { color: #FB923C; }
html[data-theme="dark"] .lw-shop-card-avail--oos { color: #FB7185; }

.lw-shop-card-price {
  display: flex; align-items: baseline; gap: 3px;
  font-size: 16px; font-weight: 800; color: var(--lw-accent); margin-top: 5px;
  padding-top: 7px; border-top: 1px dashed var(--lw-border-soft);
}
.lw-shop-price-from { font-size: 10px; font-weight: 600; color: var(--lw-text-faint); }
/* "Starts at" sits on its own line above the price on cards */
.lw-shop-card-price { flex-wrap: wrap; }
.lw-shop-card-price .lw-shop-price-from {
  flex: 0 0 100%;
  font-size: 9.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
  color: var(--lw-text-secondary);
}
.lw-shop-price-currency { font-size: 11px; font-weight: 700; }
.lw-shop-price-unit { font-size: 10px; font-weight: 600; color: var(--lw-text-faint); }
.lw-shop-card-cta {
  display: flex; align-items: center; justify-content: space-between;
  margin-top: 9px; padding: 7px 10px; border-radius: 8px;
  background: rgba(240, 112, 32, 0.08); color: var(--lw-accent);
  font-size: 10.5px; font-weight: 700;
  transition: background-color 0.18s, color 0.18s;
}
.lw-shop-card-cta i { font-size: 9px; transition: transform 0.18s; }
.lw-shop-card:hover .lw-shop-card-cta { background: var(--lw-accent); color: #fff; }
.lw-shop-card:hover .lw-shop-card-cta i { transform: translateX(3px); }
.lw-shop-card--oos:hover .lw-shop-card-cta { background: rgba(240, 112, 32, 0.08); color: var(--lw-accent); }
.lw-shop-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.lw-shop-dot--ok  { background: #10b981; }
.lw-shop-dot--low { background: #f97316; }
.lw-shop-dot--oos { background: #f43f5e; }
.lw-shop-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 0; gap: 10px; color: var(--lw-text-faint); font-size: 12.5px; }
.lw-shop-empty i { font-size: 30px; }

/* ─── BEST SELLERS CAROUSEL ──────────────── */
.lw-shop-carousel { position: relative; }
.lw-shop-grid--slider {
  display: flex;
  flex-wrap: nowrap;
  overflow-x: auto;
  overflow-y: visible;
  scroll-snap-type: x proximity;
  scroll-behavior: smooth;
  gap: 16px;
  padding: 6px 4px 14px;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.lw-shop-grid--slider::-webkit-scrollbar { display: none; height: 0; }
.lw-shop-grid--slider .lw-shop-card {
  flex: 0 0 176px;
  scroll-snap-align: start;
}

.lw-shop-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 6;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1.5px solid var(--lw-input-border);
  background: rgba(255, 255, 255, 0.55);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--lw-text-secondary);
  font-size: 12px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.14);
  opacity: 1;
  transition: opacity 0.18s ease, background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease, transform 0.15s ease;
}
.lw-shop-nav:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.88);
  border-color: var(--lw-accent);
  color: var(--lw-accent);
  transform: translateY(-50%) scale(1.06);
}
.lw-shop-nav:disabled,
.lw-shop-nav--hidden {
  opacity: 0 !important;
  pointer-events: none;
}
.lw-shop-nav--prev { left: -6px; }
.lw-shop-nav--next { right: -6px; }
html[data-theme="dark"] .lw-shop-nav {
  background: rgba(30, 41, 59, 0.55);
  border-color: var(--lw-input-border);
  color: var(--lw-text-secondary);
}
html[data-theme="dark"] .lw-shop-nav:hover:not(:disabled) {
  background: rgba(30, 41, 59, 0.88);
  border-color: var(--lw-accent);
  color: var(--lw-accent-light);
}

/* ─── HOW TO ORDER ───────────────────────── */
.lw-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 8px; }
.lw-step {
  display: flex; align-items: flex-start; gap: 13px;
  padding: 16px 16px 15px;
  background: var(--lw-card-bg);
  border: 1px solid var(--lw-card-border);
  border-radius: 14px;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.lw-step-num {
  width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #F58330, #D9640F);
  color: #fff; font-size: 13px; font-weight: 800;
  box-shadow: 0 2px 8px rgba(240, 112, 32, 0.3);
}
.lw-step-title { font-size: 13px; font-weight: 700; color: var(--lw-text-strong); margin-bottom: 3px; }
.lw-step-text  { font-size: 12px; line-height: 1.55; color: var(--lw-text-secondary); }

/* ─── VISIT US ───────────────────────────── */
.lw-visit {
  max-width: 1180px;
  margin: 0 auto;
  padding: 30px 32px 44px;
}
.lw-visit-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 18px; }
.lw-visit-head > i { font-size: 16px; color: var(--lw-accent); margin-top: 3px; }
.lw-visit-title {
  font-family: 'Space Grotesk', 'Inter', system-ui, sans-serif;
  font-size: 18px; font-weight: 700; color: var(--lw-text-primary);
  letter-spacing: -0.01em; margin-bottom: 3px;
}
.lw-visit-sub { font-size: 12px; color: var(--lw-text-secondary); }

.lw-visit-grid {
  display: grid;
  grid-template-columns: 1.35fr 1fr 0.95fr;
  gap: 18px;
  align-items: stretch;
}
.lw-visit-map {
  border-radius: 14px; overflow: hidden;
  border: 1px solid var(--lw-card-border);
  min-height: 260px;
}
.lw-visit-map iframe { width: 100%; height: 100%; border: 0; display: block; }

.lw-visit-info {
  background: var(--lw-card-bg);
  border: 1px solid var(--lw-card-border);
  border-radius: 14px;
  padding: 18px 18px 16px;
  display: flex; flex-direction: column; gap: 14px;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.lw-visit-row { display: flex; align-items: flex-start; gap: 11px; }
.lw-visit-icon {
  width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(145deg, #FFF3E8, #FDE6D0);
  border: 1px solid #FAD8B4;
  color: var(--lw-accent); font-size: 12px;
}
html[data-theme="dark"] .lw-visit-icon { background: linear-gradient(145deg, rgba(240,112,32,0.20), rgba(240,112,32,0.10)); border-color: rgba(240,112,32,0.35); }
.lw-visit-label { font-size: 9.5px; font-weight: 700; color: var(--lw-text-faint); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px; }
.lw-visit-value { font-size: 12.5px; font-weight: 600; color: var(--lw-text-strong); line-height: 1.5; }
.lw-visit-directions {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  margin-top: auto;
  height: 40px; border-radius: 10px;
  background: var(--lw-input-bg); border: 1.5px solid var(--lw-input-border);
  color: var(--lw-text-primary); font-size: 12.5px; font-weight: 700; text-decoration: none;
  transition: border-color 0.15s, background-color 0.15s, color 0.15s;
}
.lw-visit-directions:hover { border-color: var(--lw-accent); color: var(--lw-accent); background: var(--lw-input-bg-focus); }
.lw-visit-directions i { color: var(--lw-accent); }

.lw-visit-photo {
  position: relative;
  height: 100%; min-height: 260px;
  border-radius: 14px; overflow: hidden;
  border: 1px solid var(--lw-card-border);
  background: #0F172A;
}
.lw-visit-photo-img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover; object-position: center;
  display: block;
}
.lw-visit-photo-brand {
  position: absolute; left: 0; right: 0; bottom: 0; z-index: 1;
  padding: 16px 18px;
  background: linear-gradient(0deg, rgba(10,8,6,0.90) 0%, rgba(10,8,6,0.45) 65%, transparent 100%);
  display: flex; flex-direction: column; gap: 2px;
}
.lw-visit-photo-name {
  font-family: 'Space Grotesk', 'Inter', system-ui, sans-serif;
  font-size: 18px; font-weight: 700; color: var(--lw-accent-light); letter-spacing: -0.01em;
}
.lw-visit-photo-sub { font-size: 10.5px; font-weight: 600; color: #F1F5F9; text-transform: uppercase; letter-spacing: 0.1em; }

@media (max-width: 980px) {
  .lw-visit-grid { grid-template-columns: 1fr 1fr; }
  .lw-visit-photo { grid-column: 1 / -1; }
  .lw-steps { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .lw-visit-grid { grid-template-columns: 1fr; }
}

/* ─── CARD (login modal) ─────────────────── */
.lw-card {
  width: 100%; max-width: 392px;
  background: var(--lw-card-bg);
  border-radius: 20px; border: 1px solid var(--lw-card-border);
  overflow: hidden; position: relative; z-index: 1;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.08), 0 24px 64px rgba(15, 23, 42, 0.08);
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.lw-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--lw-accent-dark) 0%, var(--lw-accent-light) 50%, var(--lw-accent-dark) 100%);
}
.lw-login-card { max-height: calc(100vh - 40px); overflow-y: auto; }
.lw-login-modal-close {
  position: absolute; top: 12px; right: 12px; z-index: 2;
  width: 28px; height: 28px; border: none; border-radius: 8px;
  background: var(--lw-input-bg); color: var(--lw-text-faint);
  display: flex; align-items: center; justify-content: center; font-size: 11px;
  cursor: pointer; transition: background-color 0.15s, color 0.15s;
}
.lw-login-modal-close:hover { background: var(--lw-input-bg-focus); color: var(--lw-accent); }
.lw-card-head {
  padding: 18px 22px 15px;
  background: linear-gradient(180deg, var(--lw-card-head-bg1), var(--lw-card-head-bg2));
  border-bottom: 1px solid var(--lw-card-head-border);
  display: flex; align-items: center; justify-content: space-between;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.lw-card-brand { display: flex; align-items: center; gap: 10px; }
.lw-card-logo-img { width: 38px; height: 38px; object-fit: contain; flex-shrink: 0; }
.lw-card-title { font-size: 14px; font-weight: 700; color: var(--lw-text-primary); letter-spacing: -0.01em; line-height: 1.25; }
.lw-card-title-sub { font-size: 10.5px; color: var(--lw-text-faint); margin-top: 2px; }
.lw-card-divider { display: flex; align-items: center; padding: 0 22px; margin-top: 18px; }
.lw-card-divider::before, .lw-card-divider::after { content: ''; flex: 1; height: 1px; background: var(--lw-border-soft); }
.lw-card-divider span { font-size: 9.5px; font-weight: 700; color: var(--lw-text-faint-3); text-transform: uppercase; letter-spacing: 0.12em; padding: 0 12px; white-space: nowrap; }
.lw-card-body { padding: 16px 22px 20px; }
.lw-error {
  display: flex; align-items: center; gap: 7px;
  padding: 10px 13px; background: var(--lw-error-bg); border: 1px solid var(--lw-error-border);
  border-left: 3px solid var(--lw-accent); border-radius: 10px;
  font-size: 12px; color: var(--lw-error-text); margin-bottom: 13px;
  transition: background-color 0.22s ease, border-color 0.22s ease, color 0.22s ease;
}
.lw-shake-enter-active { animation: lw-shake 0.36s ease; }
@keyframes lw-shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
.lw-field-error { display: flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 500; color: var(--lw-error-text); margin-top: 4px; padding-left: 1px; }
.lw-field-error i { font-size: 9.5px; }
.lw-form { display: flex; flex-direction: column; gap: 12px; }
.lw-field { display: flex; flex-direction: column; gap: 5px; }
.lw-lbl-row { display: flex; align-items: center; justify-content: space-between; }
.lw-lbl { font-size: 9px; font-weight: 700; color: var(--lw-text-label); text-transform: uppercase; letter-spacing: 0.12em; }

/*
 * UX FIX: "Forgot password?" lives after the password input in the DOM (so Tab
 * goes email → password first), but is pinned visually to the top-right of the
 * password field so the layout looks exactly as before.
 */
.lw-field--pass { position: relative; }
.lw-forgot {
  position: absolute; top: -1px; right: 0;
  font-size: 11px; font-weight: 600; color: var(--lw-accent);
  background: none; border: none; cursor: pointer; font-family: inherit;
  padding: 0; line-height: 1.2; border-radius: 4px;
  transition: opacity 0.15s;
}
.lw-forgot:hover { opacity: 0.65; }
.lw-forgot:focus-visible { outline: 2px solid rgba(240, 112, 32, 0.45); outline-offset: 2px; }

.lw-input-wrap { position: relative; display: flex; align-items: center; }
.lw-ico { position: absolute; left: 0; width: 38px; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 11px; color: var(--lw-icon-muted); pointer-events: none; z-index: 1; }
.lw-inp {
  width: 100%; height: 41px; border: 1.5px solid var(--lw-input-border); border-radius: 10px;
  padding: 0 38px 0 36px; font-size: 13px; color: var(--lw-text-primary); background: var(--lw-input-bg);
  outline: none; font-family: inherit;
  transition: border-color 0.13s, box-shadow 0.13s, background-color 0.13s, color 0.22s ease;
}
.lw-inp::placeholder { color: var(--lw-placeholder); }
.lw-inp:hover { border-color: var(--lw-input-border-h); background: var(--lw-input-bg-focus); }
.lw-inp:focus { border-color: var(--lw-accent); background: var(--lw-input-bg-focus); box-shadow: 0 0 0 3.5px rgba(240, 112, 32, 0.13); }
.lw-inp--error { border-color: var(--lw-error-text); }
.lw-inp--error:focus { border-color: var(--lw-error-text); box-shadow: 0 0 0 3.5px rgba(185, 28, 28, 0.13); }
.lw-eye { position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: var(--lw-icon-muted); font-size: 12px; padding: 4px; transition: color 0.13s; line-height: 1; }
.lw-eye:hover { color: var(--lw-accent); }
.lw-remember { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--lw-text-secondary); cursor: pointer; user-select: none; }
.lw-chk { width: 14px; height: 14px; accent-color: var(--lw-accent); cursor: pointer; flex-shrink: 0; }
.lw-btn {
  position: relative; display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%; height: 43px; border: none; border-radius: 11px; cursor: pointer;
  font-family: inherit; font-size: 13px; font-weight: 700; letter-spacing: 0.015em; color: #FFFFFF;
  background: linear-gradient(135deg, #F58330 0%, #F07020 45%, #D9640F 100%);
  overflow: hidden; transition: transform 0.1s, box-shadow 0.13s, opacity 0.13s;
  box-shadow: 0 1px 2px rgba(120, 50, 0, 0.14), 0 4px 14px rgba(240, 112, 32, 0.34), inset 0 1px 0 rgba(255, 255, 255, 0.22);
  margin-top: 2px;
}
.lw-btn::after {
  content: ''; position: absolute; top: 0; left: -60%; width: 40%; height: 100%;
  background: linear-gradient(115deg, transparent, rgba(255, 255, 255, 0.35), transparent);
  transform: skewX(-18deg); transition: left 0.55s ease;
}
.lw-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(120, 50, 0, 0.18), 0 8px 22px rgba(240, 112, 32, 0.42), inset 0 1px 0 rgba(255, 255, 255, 0.2); }
.lw-btn:hover:not(:disabled)::after { left: 130%; }
.lw-btn:active:not(:disabled) { transform: translateY(0); }
.lw-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.lw-btn:focus-visible { outline: 3px solid rgba(240, 112, 32, 0.4); outline-offset: 2px; }
.lw-card-foot {
  padding: 11px 22px 13px; background: var(--lw-card-foot-bg); border-top: 1px solid var(--lw-card-head-border);
  font-size: 10.5px; color: var(--lw-text-faint-4); text-align: center;
  display: flex; align-items: center; justify-content: center; gap: 5px;
  transition: background-color 0.22s ease, border-color 0.22s ease, color 0.22s ease;
}
.lw-card-foot i { font-size: 10px; }

/* ─── FOOTER ─────────────────────────────── */
.lw-footer {
  flex-shrink: 0; height: 40px;
  background: var(--lw-footer-bg); border-top: 1px solid var(--lw-footer-border);
  padding: 0 28px; display: flex; align-items: center; justify-content: space-between;
  font-size: 10.5px; color: var(--lw-text-faint-4);
  transition: background-color 0.22s ease, border-color 0.22s ease, color 0.22s ease;
}
.lw-footer-right { display: flex; align-items: center; gap: 8px; }
.lw-sep { opacity: 0.35; }

/* ─── MODALS ─────────────────────────────── */
.lw-modal-backdrop {
  position: fixed; inset: 0;
  /* Neutral slate tint (no brown). Tweak the alpha to make it lighter/darker. */
  background: rgba(15, 23, 42, 0.38);
  backdrop-filter: blur(6px) saturate(0.9);
  -webkit-backdrop-filter: blur(6px) saturate(0.9);
  z-index: 200; display: flex; align-items: center; justify-content: center; padding: 16px;
}
html[data-theme="dark"] .lw-modal-backdrop {
  background: rgba(2, 6, 14, 0.58);
}
.lw-modal-backdrop:focus { outline: none; }
.lw-modal-backdrop--forgot { z-index: 260; }
.lw-modal {
  width: 100%; max-width: 360px; background: var(--lw-card-bg); border: 1px solid var(--lw-card-border);
  border-radius: 18px; padding: 28px 26px 24px; text-align: center;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.22);
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.lw-modal-icon {
  width: 46px; height: 46px; border-radius: 13px; margin: 0 auto 14px;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(145deg, #FFF3E8, #FDE6D0); border: 1px solid #FAD8B4;
  color: var(--lw-accent); font-size: 17px;
}
html[data-theme="dark"] .lw-modal-icon { background: linear-gradient(145deg, rgba(240,112,32,0.20), rgba(240,112,32,0.10)); border-color: rgba(240,112,32,0.35); }
.lw-modal-title { font-size: 15px; font-weight: 700; color: var(--lw-text-primary); line-height: 1.35; margin-bottom: 8px; }
.lw-modal-desc { font-size: 12px; line-height: 1.6; color: var(--lw-text-secondary); margin-bottom: 18px; }
.lw-modal-contact { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
.lw-modal-contact-row {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 9px 12px; border-radius: 10px; background: var(--lw-input-bg); border: 1.5px solid var(--lw-input-border);
  font-size: 12.5px; font-weight: 600; color: var(--lw-text-primary); text-decoration: none;
  transition: border-color 0.15s ease, background-color 0.15s ease;
}
.lw-modal-contact-row:hover { border-color: var(--lw-accent); background: var(--lw-input-bg-focus); }
.lw-modal-contact-row i { color: var(--lw-accent); font-size: 11px; }
.lw-modal-close {
  width: 100%; height: 40px; border: 1.5px solid var(--lw-input-border); border-radius: 10px;
  background: var(--lw-input-bg); color: var(--lw-text-secondary); font-size: 12.5px; font-weight: 700;
  font-family: inherit; cursor: pointer; transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.lw-modal-close:hover { background: var(--lw-input-bg-focus); border-color: var(--lw-input-border-h); color: var(--lw-text-primary); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* ─── PRODUCT DETAIL MODAL (wide, two panels) ───────────────
 * NOTE: this block must come AFTER the generic `.lw-modal` rule and uses the
 * doubled selector `.lw-modal.lw-product-modal` so its width can never be
 * overridden by the 360px `.lw-modal` default (that was the original bug).
 * ────────────────────────────────────────────────────────── */
.lw-modal.lw-product-modal {
  position: relative;
  width: 100%;
  max-width: 1080px;
  height: min(640px, calc(100vh - 32px));
  padding: 0;
  text-align: left;
  overflow: hidden;
  border-radius: 20px;
}
.lw-modal.lw-product-modal::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; z-index: 3;
  background: linear-gradient(90deg, var(--lw-accent-dark) 0%, var(--lw-accent-light) 50%, var(--lw-accent-dark) 100%);
}
.lw-pm-close { top: 16px; right: 16px; width: 32px; height: 32px; font-size: 13px; border-radius: 10px; z-index: 4; border: 1px solid var(--lw-border-soft); }

.lw-pm-layout {
  display: grid;
  grid-template-columns: 320px minmax(0, 1fr);
  height: 100%;
}

/* Left: product summary */
.lw-pm-side {
  display: flex; flex-direction: column; gap: 4px;
  padding: 28px 24px 22px;
  background: var(--lw-input-bg);
  border-right: 1px solid var(--lw-border-soft);
  overflow-y: auto;
  min-height: 0;
  scrollbar-width: thin; scrollbar-color: var(--lw-input-border-h) transparent;
}
.lw-pm-img {
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 1 / 0.85; margin-bottom: 14px; overflow: hidden;
  background: #fff; border: 1px solid var(--lw-border-soft); border-radius: 14px;
  flex-shrink: 0;
}
.lw-pm-img img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
.lw-pm-img--empty { background: var(--lw-card-bg); color: var(--lw-icon-muted); font-size: 34px; }
.lw-pm-cat { font-size: 10px; font-weight: 700; color: var(--lw-accent); text-transform: uppercase; letter-spacing: 0.12em; }
.lw-pm-title { font-size: 20px; font-weight: 700; color: var(--lw-text-primary); line-height: 1.25; letter-spacing: -0.01em; }
.lw-pm-sku { display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--lw-text-faint); margin: 2px 0 10px; }

/* Total available (product-level) */
.lw-pm-stock {
  display: flex; align-items: center; gap: 9px;
  padding: 9px 12px; margin-bottom: 6px;
  border-radius: 10px;
  background: var(--lw-card-bg);
  border: 1.5px solid var(--lw-border-soft);
}
.lw-pm-stock .lw-shop-dot { width: 8px; height: 8px; }
.lw-pm-stock-text { display: flex; flex-direction: column; line-height: 1.3; }
.lw-pm-stock-text strong { font-size: 13px; font-weight: 800; color: var(--lw-text-strong); }
.lw-pm-stock-text small { font-size: 10.5px; font-weight: 600; color: var(--lw-text-faint); }
.lw-pm-stock--ok  { border-color: rgba(16, 185, 129, 0.35); }
.lw-pm-stock--low { border-color: rgba(249, 115, 22, 0.45); }
.lw-pm-stock--oos { border-color: rgba(244, 63, 94, 0.40); }

.lw-pm-from-wrap {
  margin-top: auto;
  padding-top: 14px; border-top: 1px dashed var(--lw-border-soft);
}
.lw-pm-from-label { font-size: 10px; font-weight: 700; color: var(--lw-text-faint); text-transform: uppercase; letter-spacing: 0.1em; }
.lw-pm-from {
  display: flex; align-items: baseline; gap: 4px;
  font-size: 28px; font-weight: 800; color: var(--lw-accent); line-height: 1.2;
}
.lw-pm-from .lw-shop-price-currency { font-size: 16px; }
.lw-pm-from .lw-shop-price-unit { font-size: 12px; }
.lw-pm-vary { display: flex; align-items: flex-start; gap: 6px; margin-top: 6px; font-size: 11px; line-height: 1.45; color: var(--lw-text-secondary); }
.lw-pm-vary i { margin-top: 2px; color: var(--lw-accent); font-size: 10px; }
.lw-pm-call { margin-top: 14px; text-decoration: none; flex-shrink: 0; background: var(--lw-card-bg); }
.lw-pm-note { margin-top: 10px; font-size: 10.5px; line-height: 1.5; color: var(--lw-text-faint); text-align: center; }

/* Right: sizes */
.lw-pm-main {
  display: flex; flex-direction: column;
  min-width: 0; min-height: 0;
  padding: 26px 26px 22px;
}
.lw-pm-main-head {
  display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap;
  gap: 12px 16px;
  padding-right: 44px;            /* keeps clear of the close button */
  margin-bottom: 16px; padding-bottom: 14px;
  border-bottom: 1px solid var(--lw-border-soft);
}
.lw-pm-head { font-size: 15px; font-weight: 700; color: var(--lw-text-primary); letter-spacing: -0.01em; }
.lw-pm-count { display: flex; align-items: center; gap: 6px; margin-top: 3px; font-size: 11.5px; font-weight: 600; color: var(--lw-text-secondary); }

.lw-pm-tools { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.lw-pm-search { position: relative; display: flex; align-items: center; width: 190px; }
.lw-pm-search i { position: absolute; left: 11px; font-size: 11px; color: var(--lw-icon-muted); pointer-events: none; }
.lw-pm-search input {
  width: 100%; height: 34px;
  border: 1.5px solid var(--lw-input-border); border-radius: 9px;
  padding: 0 10px 0 31px; font-size: 12px; font-family: inherit;
  color: var(--lw-text-primary); background: var(--lw-input-bg); outline: none;
  transition: border-color 0.15s, background-color 0.15s, box-shadow 0.15s;
}
.lw-pm-search input:focus { border-color: var(--lw-accent); background: var(--lw-input-bg-focus); box-shadow: 0 0 0 3px rgba(240, 112, 32, 0.12); }
.lw-pm-search input::placeholder { color: var(--lw-placeholder); }
.lw-pm-toggle { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: var(--lw-text-secondary); cursor: pointer; user-select: none; white-space: nowrap; }
.lw-pm-toggle input { width: 14px; height: 14px; accent-color: var(--lw-accent); cursor: pointer; }

.lw-pm-list {
  flex: 1; min-height: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  grid-auto-rows: min-content;
  gap: 10px;
  overflow-y: auto;
  padding: 2px 6px 4px 2px;
  scrollbar-width: thin; scrollbar-color: var(--lw-input-border-h) transparent;
}
.lw-pm-list::-webkit-scrollbar { width: 8px; }
.lw-pm-list::-webkit-scrollbar-thumb { background: var(--lw-input-border-h); border-radius: 6px; }

.lw-pm-row {
  display: grid; grid-template-columns: minmax(0, 1fr) auto;
  grid-template-areas: "label price" "status status";
  align-items: baseline; gap: 6px 10px;
  padding: 12px 14px;
  border: 1.5px solid var(--lw-border-soft); border-radius: 12px;
  background: var(--lw-card-bg);
  transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
}
.lw-pm-row:hover { border-color: var(--lw-feat-border-h, #F0B589); box-shadow: 0 4px 14px rgba(240, 112, 32, 0.12); transform: translateY(-1px); }
.lw-pm-row--oos { opacity: 0.55; background: var(--lw-input-bg); }
.lw-pm-row--oos:hover { transform: none; box-shadow: none; border-color: var(--lw-border-soft); }
.lw-pm-row--low { border-color: rgba(249, 115, 22, 0.4); background: rgba(249, 115, 22, 0.06); }
.lw-pm-row--low .lw-pm-status { color: #EA580C; font-weight: 700; }
html[data-theme="dark"] .lw-pm-row--low .lw-pm-status { color: #FB923C; }
.lw-pm-rowhint {
  grid-column: 1 / -1;
  display: flex; align-items: center; gap: 6px;
  font-size: 10.5px; font-weight: 600; color: var(--lw-text-faint);
}
.lw-pm-rowhint i { font-size: 9px; color: #EA580C; }

/* Stock status legend */
.lw-legend {
  list-style: none;
  display: flex; flex-wrap: wrap; gap: 6px 18px;
  margin: 10px 0 0 14px;
  font-size: 11px; color: var(--lw-text-secondary);
}
.lw-legend li { display: inline-flex; align-items: center; gap: 6px; }
.lw-legend strong { color: var(--lw-text-primary); font-weight: 700; }
.lw-legend--modal { margin: 0 0 12px 2px; }

.lw-pm-lowhint {
  display: flex; align-items: flex-start; gap: 7px;
  margin: 2px 0 8px; padding: 8px 10px;
  border-radius: 9px;
  background: rgba(249, 115, 22, 0.10);
  border: 1px solid rgba(249, 115, 22, 0.35);
  font-size: 11px; line-height: 1.45; font-weight: 600; color: #C2410C;
}
.lw-pm-lowhint i { margin-top: 2px; font-size: 10px; }
html[data-theme="dark"] .lw-pm-lowhint { color: #FDBA74; }
.lw-pm-label {
  grid-area: label; min-width: 0;
  font-size: 13px; font-weight: 700; color: var(--lw-text-strong); line-height: 1.3;
  overflow-wrap: anywhere;
}
.lw-pm-price { grid-area: price; font-size: 15px; font-weight: 800; color: var(--lw-accent); white-space: nowrap; }
.lw-pm-status {
  grid-area: status;
  display: flex; align-items: center; gap: 6px;
  padding-top: 7px; border-top: 1px dashed var(--lw-border-soft);
  font-size: 11px; font-weight: 600; color: var(--lw-text-secondary);
}
.lw-pm-empty {
  grid-column: 1 / -1;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
  padding: 40px 0; color: var(--lw-text-faint); font-size: 12.5px;
}
.lw-pm-empty i { font-size: 26px; }

@media (max-width: 860px) {
  .lw-pm-layout { grid-template-columns: 270px minmax(0, 1fr); }
  .lw-pm-list { grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); }
}
/* Phones / narrow: single column, the whole modal scrolls */
@media (max-width: 720px) {
  .lw-modal.lw-product-modal { height: auto; max-height: calc(100vh - 24px); overflow-y: auto; }
  .lw-pm-layout { grid-template-columns: 1fr; height: auto; }
  .lw-pm-side { border-right: none; border-bottom: 1px solid var(--lw-border-soft); overflow: visible; padding: 22px 18px 18px; }
  .lw-pm-img { aspect-ratio: auto; height: 190px; }
  .lw-pm-from-wrap { margin-top: 6px; }
  .lw-pm-main { padding: 18px; }
  .lw-pm-list { overflow: visible; grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .lw-pm-search { width: 100%; }
  .lw-pm-tools { width: 100%; }
  .lw-pm-search { flex: 1; }
}
@media (max-width: 420px) {
  .lw-pm-list { grid-template-columns: 1fr; }
}

/* ─── UI POLISH ──────────────────────────── */
.lw-nav-links { display: flex; align-items: center; gap: 2px; margin-right: 6px; }
.lw-nav-links button {
  border: none; background: transparent; cursor: pointer;
  padding: 7px 11px; border-radius: 8px;
  font-family: inherit; font-size: 12.5px; font-weight: 600; color: var(--lw-text-secondary);
  transition: background-color 0.15s, color 0.15s;
}
.lw-nav-links button:hover { background: var(--lw-input-bg); color: var(--lw-accent); }
.lw-nav-links button:focus-visible { outline: 2px solid rgba(240, 112, 32, 0.45); outline-offset: 1px; }
@media (max-width: 1100px) { .lw-nav-hint { display: none; } }
@media (max-width: 760px)  { .lw-nav-links { display: none; } }

.lw-hero-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
.lw-cta {
  display: inline-flex; align-items: center; gap: 8px;
  height: 42px; padding: 0 20px; border-radius: 11px;
  font-family: inherit; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer;
  transition: transform 0.13s, box-shadow 0.13s, border-color 0.15s, color 0.15s, background-color 0.15s;
}
.lw-cta i { font-size: 12px; }
.lw-cta--primary {
  border: none; color: #fff;
  background: linear-gradient(135deg, #F58330 0%, #F07020 45%, #D9640F 100%);
  box-shadow: 0 4px 14px rgba(240, 112, 32, 0.34);
}
.lw-cta--primary:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(240, 112, 32, 0.42); }
.lw-cta--ghost {
  border: 1.5px solid var(--lw-input-border); color: var(--lw-text-primary);
  background: var(--lw-glass-bg); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
}
.lw-cta--ghost:hover { border-color: var(--lw-accent); color: var(--lw-accent); }
.lw-cta:focus-visible { outline: 3px solid rgba(240, 112, 32, 0.4); outline-offset: 2px; }

/* Loading skeleton */
.lw-skel-wrap { padding-bottom: 8px; }
.lw-skel { padding-bottom: 14px; gap: 9px; pointer-events: none; }
.lw-skel:hover { transform: none; box-shadow: none; border-color: var(--lw-feat-border); }
.lw-skel-img, .lw-skel-line {
  background: linear-gradient(90deg, var(--lw-input-bg) 25%, var(--lw-border-soft) 50%, var(--lw-input-bg) 75%);
  background-size: 200% 100%;
  animation: lw-shimmer 1.3s linear infinite;
}
.lw-skel-img { width: 100%; aspect-ratio: 1 / 0.82; }
.lw-skel-line { height: 11px; border-radius: 6px; margin: 0 13px; }
.lw-skel-line--w80 { width: 80%; } .lw-skel-line--w65 { width: 65%; } .lw-skel-line--w50 { width: 50%; }
@keyframes lw-shimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }

/* Back to top */
.lw-top-btn {
  position: fixed; right: 22px; bottom: 58px; z-index: 20;
  width: 42px; height: 42px; border-radius: 50%;
  border: 1.5px solid var(--lw-input-border); background: var(--lw-card-bg); color: var(--lw-text-secondary);
  display: flex; align-items: center; justify-content: center; font-size: 13px; cursor: pointer;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.18);
  transition: transform 0.15s, color 0.15s, border-color 0.15s;
}
.lw-top-btn:hover { transform: translateY(-2px); color: var(--lw-accent); border-color: var(--lw-accent); }

/* Respect reduced-motion preferences */
@media (prefers-reduced-motion: reduce) {
  .lw *, .lw *::before, .lw *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
}

/* ─── RESPONSIVE ─────────────────────────── */
@media (max-width: 640px) {
  .lw { height: auto; overflow: auto; }
  .lw-page { overflow: visible; }
  .lw-nav-hint { display: none; }
  .lw-hero-inner { padding: 28px 20px 22px; }
  .lw-block, .lw-visit { padding-left: 20px; padding-right: 20px; }
  .lw-strip-inner { padding-left: 20px; padding-right: 20px; }
  .lw-footer { flex-direction: column; height: auto; gap: 4px; padding: 10px 20px; text-align: center; }
  .lw-shop-nav { width: 32px; height: 32px; font-size: 11px; }
  .lw-shop-nav--prev { left: -2px; }
  .lw-shop-nav--next { right: -2px; }
  .lw-shop-grid--slider .lw-shop-card { flex-basis: 150px; }
  .lw-shop-search { width: 100%; }
  .lw-shop-toolbar { width: 100%; }
  .lw-dd { width: 100%; }
  .lw-dd-trigger { width: 100%; }
  .lw-dd-panel { max-width: 100%; width: 100%; }
}
@media (max-width: 480px) {
  .lw-nav-inner { padding: 0 16px; gap: 8px; }
  .lw-nav-right { gap: 6px; }
  .lw-signin-btn span { display: none; }
  .lw-signin-btn { width: 34px; height: 34px; padding: 0; justify-content: center; }
  .lw-card-head, .lw-card-body, .lw-card-foot, .lw-card-divider { padding-left: 16px; padding-right: 16px; }
}

/* ════════════════════════════════════════════════════════════════
 * UI / UX IMPROVEMENTS — compact cards + full responsive layer
 * Everything below intentionally overrides the rules above.
 * ════════════════════════════════════════════════════════════════ */

/* ─── 0. SHELL ───────────────────────────── */
.lw {
  width: 100%;                 /* 100vw caused a horizontal scrollbar */
  height: 100vh;
  height: 100dvh;              /* correct height on mobile browsers */
}
.lw-hero-inner,
.lw-strip-inner,
.lw-block,
.lw-visit { max-width: 1240px; }

/* Footer now lives inside the scrolling page (never covers products) */
.lw-footer {
  height: auto; min-height: 44px;
  padding: 12px 28px;
  gap: 4px 16px; flex-wrap: wrap;
}
.lw-top-btn { bottom: 22px; }

/* ─── 1. COMPACT PRODUCT CARDS ───────────── */
.lw-shop-grid.lw-shop-grid--groups {
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 12px;
}
.lw-shop-card { border-radius: 12px; }
.lw-shop-card-img { aspect-ratio: 1 / 0.62; }
.lw-shop-card-img img { padding: 4px; }
.lw-shop-stock-pill { top: 6px; left: 6px; padding: 3px 8px 3px 6px; font-size: 9px; }
.lw-shop-rank { top: 6px; right: 6px; height: 20px; min-width: 24px; font-size: 10px; }

.lw-shop-card-body { padding: 8px 10px 9px; gap: 1px; }
.lw-shop-card-name { font-size: 12px; line-height: 1.25; min-height: 30px; }
.lw-shop-card-sizes { font-size: 10px; line-height: 1.3; }
.lw-shop-card-avail { font-size: 10px; line-height: 1.3; }
.lw-shop-card-price { font-size: 14.5px; margin-top: 3px; padding-top: 5px; }
.lw-shop-card-price .lw-shop-price-from { font-size: 8.5px; }
.lw-shop-price-currency { font-size: 10.5px; }
.lw-shop-price-unit { font-size: 9.5px; }

/* "View all sizes" stays, but as a slim text link instead of a button */
.lw-shop-card-cta {
  margin-top: 4px; padding: 0; border-radius: 0;
  background: none; color: var(--lw-accent);
  justify-content: flex-start; gap: 5px;
  font-size: 10px;
}
.lw-shop-card:hover .lw-shop-card-cta,
.lw-shop-card--oos:hover .lw-shop-card-cta { background: none; color: var(--lw-accent); text-decoration: underline; }

/* Best-sellers carousel */
.lw-shop-grid--slider { gap: 12px; }
.lw-shop-grid--slider .lw-shop-card { flex: 0 0 144px; }

/* ─── 2. SECTION RHYTHM ──────────────────── */
.lw-block { padding-top: 22px; }
.lw-shop-title { font-size: 20px; }
.lw-catalog-bar { box-shadow: 0 6px 12px -10px rgba(15, 23, 42, 0.25); }
.lw-shop-search { flex: 1 1 260px; max-width: 340px; }

/* ─── 3. TABLET (≤ 980px) ────────────────── */
@media (max-width: 980px) {
  .lw-hero-inner { padding: 28px 24px 22px; }
  .lw-block, .lw-visit, .lw-strip-inner { padding-left: 24px; padding-right: 24px; }
  .lw-steps { grid-template-columns: repeat(3, 1fr); gap: 10px; }
  .lw-step { flex-direction: column; gap: 9px; }
  .lw-footer { padding-left: 24px; padding-right: 24px; }
}

/* ─── 4. NAVBAR ON SMALL SCREENS (≤ 760px) ─ */
@media (max-width: 760px) {
  .lw-nav { height: auto; }
  .lw-nav-inner {
    height: auto;
    padding: 8px 14px 6px;
    flex-wrap: wrap;
    row-gap: 6px;
    justify-content: flex-start;
  }
  .lw-nav-right { display: contents; }     /* children join .lw-nav-inner's flex row */
  .lw-brand { order: 1; margin-right: auto; min-width: 0; }
  .lw-logo-img { width: 32px; height: 32px; }
  .lw-settings-wrap { order: 2; }
  .lw-signin-btn {
    order: 3; margin-left: 2px;
    width: auto; height: 36px; padding: 0 14px;
    justify-content: center; gap: 7px; white-space: nowrap;
  }
  .lw-signin-btn span { display: inline; }   /* always show the "Login" label */

  /* Section links: slim pills that stay visible while scrolling */
  .lw-nav-links {
    order: 4;
    display: flex;
    flex: 0 0 100%;
    margin: 0;
    gap: 6px;
    overflow-x: auto;
    scrollbar-width: none;
    padding-bottom: 4px;
  }
  .lw-nav-links::-webkit-scrollbar { display: none; }
  .lw-nav-links button {
    flex: 1 1 0;
    white-space: nowrap;
    text-align: center;
    padding: 6px 10px;
    font-size: 12px;
    border-radius: 999px;
    background: var(--lw-input-bg);
    border: 1px solid var(--lw-border-soft);
  }
}

/* ─── 5. PHONES (≤ 640px) ────────────────── */
@media (max-width: 640px) {
  /* Fixed shell: nav on top, page scrolls underneath */
  .lw { height: 100dvh; overflow: hidden; }
  .lw-page { overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch; overscroll-behavior-y: contain; }

  /* Hero — compact */
  .lw-hero-inner { padding: 18px 16px 16px; }
  .lw-eyebrow { margin-bottom: 8px; font-size: 9px; letter-spacing: 0.12em; }
  .lw-h1 { font-size: clamp(24px, 7vw, 30px); line-height: 1.1; margin-bottom: 8px; }
  .lw-tagline { font-size: 12.5px; line-height: 1.55; max-width: 100%; }
  .lw-hero-actions { margin-top: 12px; }
  .lw-cta { width: 100%; justify-content: center; height: 42px; }
  .lw-hero-roof-img { object-position: 65% 45%; }

  /* Info strip wraps into a tidy 2-line block */
  .lw-strip-inner { flex-wrap: wrap; gap: 4px 16px; padding: 8px 16px; font-size: 11.5px; overflow: visible; }
  .lw-strip-item { white-space: normal; }

  .lw-block, .lw-visit { padding-left: 16px; padding-right: 16px; }
  .lw-block { padding-top: 16px; }
  .lw-sec-head { margin-bottom: 12px; }
  .lw-shop-title { font-size: 18px; }
  .lw-shop-sub { font-size: 12px; padding-left: 14px; }
  .lw-legend { gap: 4px 14px; margin: 8px 0 0 14px; font-size: 10.5px; }

  /* Catalog toolbar: search on top, filter + clear below, not sticky */
  .lw-catalog-bar { position: static; box-shadow: none; padding: 6px 0 8px; margin-bottom: 10px; border-bottom: 0; background: transparent; }
  .lw-shop-toolbar { display: grid; grid-template-columns: 1fr auto; gap: 8px; width: 100%; }
  .lw-shop-search { grid-column: 1 / -1; width: 100%; max-width: none; flex: none; }
  .lw-dd { width: 100%; min-width: 0; }
  .lw-dd:last-child { grid-column: 1 / -1; }
  .lw-dd-trigger { width: 100%; min-width: 0; height: 44px; }
  .lw-shop-search input { height: 44px; font-size: 16px; }   /* 16px stops iOS zoom */
  .lw-clear-btn { height: 44px; }
  .lw-dd-panel { width: 100%; max-width: 100%; max-height: 50dvh; }
  .lw-dd-opt { padding: 12px 10px; }

  /* ALL PRODUCTS → compact horizontal list cards (photo left, details right) */
  .lw-shop-grid.lw-shop-grid--groups { grid-template-columns: 1fr; gap: 8px; margin-bottom: 20px; }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel) {
    flex-direction: row; align-items: stretch;
    min-height: 92px; padding-right: 24px;
  }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel)::after {
    content: '›'; position: absolute; right: 9px; top: 50%; transform: translateY(-52%);
    font-size: 22px; line-height: 1; color: var(--lw-icon-muted);
  }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel) .lw-shop-card-img {
    flex: 0 0 92px; width: 92px; aspect-ratio: auto; min-height: 92px;
    border-right: 1px solid var(--lw-border-soft);
  }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel) .lw-shop-stock-pill { top: 4px; left: 4px; padding: 2px 6px 2px 5px; font-size: 8px; gap: 4px; }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel) .lw-shop-card-body { flex: 1; min-width: 0; padding: 8px 6px 8px 11px; justify-content: center; gap: 2px; }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel) .lw-shop-card-name { min-height: 0; font-size: 12.5px; }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel) .lw-shop-card-price { margin-top: 1px; padding-top: 0; border-top: 0; font-size: 14px; }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel) .lw-shop-card-price .lw-shop-price-from {
    flex: 0 0 auto; margin-right: 2px; text-transform: none; letter-spacing: 0; font-size: 10px; font-weight: 600; color: var(--lw-text-faint);
  }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel) .lw-shop-card-cta { display: none; }   /* whole card is tappable (›) */

  /* Loading skeleton keeps a 2-column look */
  .lw-skel-wrap .lw-shop-grid.lw-shop-grid--groups { grid-template-columns: repeat(2, minmax(0, 1fr)); }

  /* Best sellers carousel: slightly narrower, swipe edge-to-edge */
  .lw-shop-grid--slider .lw-shop-card { flex-basis: 132px; }
  .lw-shop-grid--slider { margin-right: -16px; padding-right: 16px; }

  /* How to order */
  .lw-steps { grid-template-columns: 1fr; gap: 8px; }
  .lw-step { flex-direction: row; padding: 12px 14px; }

  /* Visit */
  .lw-visit { padding-top: 20px; padding-bottom: 20px; }
  .lw-visit-map { min-height: 220px; }
  .lw-visit-photo { min-height: 200px; }
  .lw-visit-directions { height: 44px; }

  /* Footer (scrolls with content) */
  .lw-footer { flex-direction: column; justify-content: center; text-align: center; padding: 12px 16px calc(12px + env(safe-area-inset-bottom)); }
  .lw-footer-right { justify-content: center; }

  .lw-top-btn { right: 14px; bottom: calc(16px + env(safe-area-inset-bottom)); width: 40px; height: 40px; }

  /* Login modal */
  .lw-modal-backdrop { padding: 12px; }
  .lw-login-card { max-height: calc(100dvh - 24px); }
  .lw-inp { height: 46px; font-size: 16px; }
  .lw-btn { height: 46px; }
  .lw-eye { padding: 8px; }
}

/* ─── 6. PRODUCT MODAL → BOTTOM SHEET (≤ 720px) ─ */
@media (max-width: 720px) {
  .lw-modal-backdrop:has(.lw-product-modal) { align-items: flex-end; padding: 0; }

  .lw-modal.lw-product-modal {
    max-width: none;
    height: auto;
    max-height: 92dvh;
    overflow: hidden;                         /* inner layout scrolls instead */
    border-radius: 20px 20px 0 0;
    border-bottom: 0;
  }
  .lw-pm-layout {
    height: auto;
    max-height: 92dvh;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding-bottom: env(safe-area-inset-bottom);
  }
  .lw-pm-close { top: 12px; right: 12px; width: 36px; height: 36px; background: var(--lw-card-bg); box-shadow: 0 2px 8px rgba(15,23,42,.18); }

  .lw-pm-side { padding: 18px 16px 14px; }
  .lw-pm-img { height: 150px; margin-bottom: 10px; }
  .lw-pm-title { font-size: 18px; padding-right: 36px; }
  .lw-pm-from { font-size: 24px; }
  .lw-pm-main { padding: 14px 16px 20px; }
  .lw-pm-main-head { padding-right: 0; margin-bottom: 12px; padding-bottom: 10px; }
  .lw-pm-search input { height: 42px; font-size: 16px; }
  .lw-pm-list { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
  .lw-pm-row { padding: 10px 12px; }
}
@media (max-width: 560px) {
  .lw-pm-list { grid-template-columns: 1fr; }
}

/* ─── 7. VERY SMALL PHONES (≤ 380px) ─────── */
@media (max-width: 380px) {
  .lw-brand-sub { display: none; }
  .lw-brand-name { font-size: 14px; }
  .lw-signin-btn { padding: 0 12px; }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel) .lw-shop-card-img { flex-basis: 80px; width: 80px; min-height: 80px; }
  .lw-shop-grid--groups .lw-shop-card:not(.lw-skel) { min-height: 80px; }
}

/* ─── 8. LARGE SCREENS (≥ 1440px) ────────── */
@media (min-width: 1440px) {
  .lw-hero-inner { padding-top: 44px; padding-bottom: 34px; }
  .lw-shop-grid.lw-shop-grid--groups { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
}

/* ─── 9. SHORT / LANDSCAPE PHONES ────────── */
@media (max-height: 480px) and (orientation: landscape) {
  .lw-hero-inner { padding-top: 14px; padding-bottom: 12px; }
  .lw-h1 { font-size: 24px; margin-bottom: 6px; }
  .lw-tagline { display: none; }
  .lw-hero-actions { margin-top: 8px; }
  .lw-cta { width: auto; height: 38px; }
  .lw-modal.lw-product-modal { max-height: calc(100dvh - 12px); }
}

/* ─── 10. TOUCH DEVICES ──────────────────── */
@media (hover: none) {
  .lw-shop-card:hover { transform: none; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03); border-color: var(--lw-feat-border); }
  .lw-shop-card:hover .lw-shop-card-img img { transform: none; }
  .lw-shop-card:active { border-color: var(--lw-feat-border-h); background: var(--lw-input-bg); }
  .lw-pm-row:hover { transform: none; box-shadow: none; }
  .lw-shop-nav { display: none; }                              /* swipe instead of arrows */
  .lw-shop-grid--slider { scroll-snap-type: x mandatory; }
}

/* Notched phones */
@supports (padding: env(safe-area-inset-left)) {
  .lw-nav-inner { padding-left: max(14px, env(safe-area-inset-left)); padding-right: max(14px, env(safe-area-inset-right)); }
}

/* ─── ALWAYS-VISIBLE SECTION LINKS + ACTIVE STATE ─ */
.lw-nav { position: sticky; top: 0; }           /* stays on top on every device */
.lw-nav-links button.lw-nav-active {
  color: var(--lw-accent);
  background: rgba(240, 112, 32, 0.12);
}
@media (max-width: 760px) {
  .lw-nav-links button.lw-nav-active { border-color: rgba(240, 112, 32, 0.55); }
  .lw-nav-inner { padding-top: 6px; }
}

/* ════════════════════════════════════════════════════════════════
 * PRODUCT POPUP — compact summary so the SIZES list is seen first
 * ════════════════════════════════════════════════════════════════ */

/* Tablet / small laptop: slimmer image */
@media (min-width: 721px) {
  .lw-pm-img { aspect-ratio: 1 / 0.68; margin-bottom: 10px; }
  .lw-pm-side { padding: 24px 22px 18px; }
  .lw-pm-title { font-size: 18px; }
  .lw-pm-from { font-size: 26px; }
}

/* Phones: product summary becomes a small header strip */
@media (max-width: 720px) {
  .lw-pm-side {
    display: grid;
    grid-template-columns: 76px minmax(0, 1fr);
    grid-auto-rows: min-content;
    column-gap: 12px; row-gap: 0;
    align-items: start;
    padding: 14px 14px 12px;
  }
  .lw-pm-img {
    grid-column: 1; grid-row: 1 / span 3;
    width: 76px; height: 76px; margin: 0; border-radius: 10px;
    aspect-ratio: auto;
  }
  .lw-pm-img img { padding: 3px; }
  .lw-pm-cat   { grid-column: 2; font-size: 9.5px; padding-right: 40px; align-self: end; }
  .lw-pm-title { grid-column: 2; font-size: 15.5px; line-height: 1.2; padding-right: 40px; }
  .lw-pm-sku   { grid-column: 2; margin: 2px 0 0; font-size: 10.5px; align-self: start; }

  /* stock + price share the width under the header */
  .lw-pm-stock {
    grid-column: 1 / -1; margin: 10px 0 0; padding: 6px 10px; gap: 8px;
  }
  .lw-pm-stock .lw-shop-dot { width: 7px; height: 7px; }
  .lw-pm-stock-text { flex-direction: row; align-items: baseline; flex-wrap: wrap; gap: 0 6px; }
  .lw-pm-stock-text strong { font-size: 12.5px; }
  .lw-pm-stock-text small { font-size: 10.5px; }
  .lw-pm-lowhint { grid-column: 1 / -1; margin: 6px 0 0; padding: 6px 9px; font-size: 10.5px; }

  .lw-pm-from-wrap {
    grid-column: 1 / -1; margin-top: 8px; padding-top: 8px;
    display: flex; flex-wrap: wrap; align-items: baseline; gap: 2px 8px;
  }
  .lw-pm-from-label { font-size: 9.5px; }
  .lw-pm-from { font-size: 22px; line-height: 1.1; }
  .lw-pm-from .lw-shop-price-currency { font-size: 13px; }
  .lw-pm-from .lw-shop-price-unit { font-size: 11px; }
  .lw-pm-vary { flex: 0 0 100%; margin-top: 2px; font-size: 10.5px; line-height: 1.35; }
  .lw-pm-note { grid-column: 1 / -1; margin-top: 6px; font-size: 9.5px; line-height: 1.4; text-align: left; }

  /* Sizes: header stays pinned while the list scrolls, rows are tighter */
  .lw-pm-main { padding: 0 14px 18px; }
  .lw-pm-main-head {
    position: sticky; top: 0; z-index: 3;
    background: var(--lw-card-bg);
    margin: 0 -14px 10px; padding: 12px 14px 10px;
    box-shadow: 0 6px 10px -10px rgba(15, 23, 42, 0.5);
  }
  .lw-pm-head { font-size: 14.5px; }
  .lw-pm-count { font-size: 11px; }
  .lw-legend--modal { margin: 0 0 8px; gap: 4px 12px; font-size: 10px; }
  .lw-pm-list { gap: 6px; }
  .lw-pm-row { padding: 8px 11px; gap: 4px 10px; border-radius: 10px; }
  .lw-pm-label { font-size: 12.5px; }
  .lw-pm-price { font-size: 14px; }
  .lw-pm-status { padding-top: 5px; font-size: 10.5px; }
}

/* Very small phones */
@media (max-width: 380px) {
  .lw-pm-side { grid-template-columns: 64px minmax(0, 1fr); column-gap: 10px; }
  .lw-pm-img { width: 64px; height: 64px; }
  .lw-pm-title { font-size: 14.5px; }
  .lw-pm-from { font-size: 20px; }
}

/* Short screens (landscape phones): drop the photo, keep everything else */
@media (max-height: 520px) {
  .lw-modal.lw-product-modal { height: calc(100dvh - 12px); }
  .lw-pm-img { display: none; }
  .lw-pm-side { padding-top: 18px; }
}
@media (max-height: 520px) and (max-width: 720px) {
  .lw-pm-side { grid-template-columns: minmax(0, 1fr); }
  .lw-pm-cat, .lw-pm-title, .lw-pm-sku { grid-column: 1; }
}

/* ─── PRODUCT POPUP: 2 sizes per row on phones ─ */
@media (max-width: 720px) {
  .lw-pm-list { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
  .lw-pm-row {
    grid-template-columns: minmax(0, 1fr);
    grid-template-areas: "label" "price" "status";
    gap: 1px;
    padding: 9px 10px;
  }
  .lw-pm-label { font-size: 12.5px; line-height: 1.25; }
  .lw-pm-price { font-size: 15px; margin-top: 1px; }
  .lw-pm-status { margin-top: 4px; padding-top: 5px; font-size: 10px; line-height: 1.3; }
  .lw-pm-rowhint { font-size: 10px; }
}

/* ─── PRODUCT POPUP: smaller "Find a size" box ─ */
.lw-pm-search input { font-size: 13px; height: 36px; }
.lw-pm-search input::placeholder { font-size: 13px; }
@media (max-width: 720px) {
  .lw-pm-search input { font-size: 13px; height: 38px; }
}

/* ─── STOCK COLOURS ON EVERY SIZE: green = in stock, orange = low, red = out ─ */
.lw-pm-row--ok  { border-color: rgba(16, 185, 129, 0.40); background: rgba(16, 185, 129, 0.06); }
.lw-pm-row--ok  .lw-pm-status { color: #059669; font-weight: 700; }
.lw-pm-row--ok:hover  { border-color: rgba(16, 185, 129, 0.70); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.14); }

.lw-pm-row--low { border-color: rgba(249, 115, 22, 0.50); background: rgba(249, 115, 22, 0.08); }
.lw-pm-row--low:hover { border-color: rgba(249, 115, 22, 0.75); box-shadow: 0 4px 14px rgba(249, 115, 22, 0.16); }

.lw-pm-row--oos { opacity: 1; border-color: rgba(244, 63, 94, 0.45); background: rgba(244, 63, 94, 0.07); }
.lw-pm-row--oos .lw-pm-label,
.lw-pm-row--oos .lw-pm-price { opacity: 0.6; }
.lw-pm-row--oos .lw-pm-status { color: #E11D48; font-weight: 700; }
.lw-pm-row--oos:hover { transform: none; box-shadow: none; border-color: rgba(244, 63, 94, 0.45); }

html[data-theme="dark"] .lw-pm-row--ok  .lw-pm-status { color: #34D399; }
html[data-theme="dark"] .lw-pm-row--oos .lw-pm-status { color: #FB7185; }

/* ─── PHONES: calmer input sizes (login, search, filters) ─ */
@media (max-width: 640px) {
  /* Login form */
  .lw-inp { height: 42px; font-size: 13px; padding-left: 36px; }
  .lw-inp::placeholder { font-size: 13px; }
  .lw-btn { height: 44px; font-size: 13px; }
  .lw-remember { font-size: 12px; }

  /* Catalog search + category filter */
  .lw-shop-search input { height: 42px; font-size: 13px; }
  .lw-shop-search input::placeholder { font-size: 13px; }
  .lw-dd-trigger { height: 42px; font-size: 12.5px; }
  .lw-clear-btn { height: 42px; font-size: 12px; }
  .lw-dd-opt { font-size: 12.5px; }
}
</style>