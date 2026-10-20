<template>
  <div class="lw">

    <!-- NAVBAR -->
    <header class="lw-nav">
      <div class="lw-nav-inner">
        <div class="lw-brand">
          <img src="@/assets/cutout_logo.png" alt="Shem Steel logo" class="lw-logo-img" />
          <div class="lw-brand-text">
            <div class="lw-brand-name">Shem Steel</div>
            <div class="lw-brand-sub">Construction Company</div>
          </div>
        </div>
        <div class="lw-nav-right">
          <span class="lw-nav-hint">Inventory &amp; Sales Management System</span>

          <div class="lw-settings-wrap">
            <button
              class="lw-appearance-btn"
              :class="{ 'lw-appearance-btn--open': showThemeMenu }"
              @click.stop="showThemeMenu = !showThemeMenu"
              aria-label="Appearance settings"
              :aria-expanded="showThemeMenu"
            >
              <i class="fa-solid fa-gear" aria-hidden="true"></i>
              <span>Appearance</span>
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
          <button class="lw-signin-btn" @click="openLoginModal">
            <i class="fa-solid fa-lock" aria-hidden="true"></i>
            <span>Login</span>
          </button>
        </div>
      </div>
    </header>

    <!-- SCROLLABLE PAGE BODY -->
    <main class="lw-page">

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
          </div>
        </div>
      </section>

      <!-- QUICK INFO STRIP — answers "is this a real, open shop?" right away -->
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
          <a :href="`tel:${SHOP_PHONE_TEL}`" class="lw-strip-item lw-strip-link">
            <i class="fa-solid fa-phone" aria-hidden="true"></i>
            Call to order: {{ SHOP_PHONE }}
          </a>
        </div>
      </div>

      <!-- LOAD / ERROR STATES -->
      <section v-if="shopError || shopLoading" class="lw-block">
        <div v-if="shopError" class="lw-shop-state lw-shop-state--error">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <p>{{ shopError }}</p>
          <button @click="loadShopCatalog" class="lw-shop-retry">Retry</button>
        </div>
        <div v-else class="lw-shop-state">
          <i class="fa-solid fa-spinner fa-spin"></i>
          <p>Loading products…</p>
        </div>
      </section>

      <!-- BEST SELLERS — one highlighted row. Hidden while the visitor is
           searching/filtering so results aren't buried under it. -->
      <section
        v-if="!shopLoading && !shopError && !isFiltering && displayedBestSellers.length"
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
              class="lw-shop-card"
            >
              <div class="lw-shop-card-img" :class="{ 'lw-shop-card-img--photo': product.image }">
                <span class="lw-shop-stock-pill">
                  <span class="lw-shop-dot" :class="`lw-shop-dot--${shopStockStatus(product)}`"></span>
                  {{ shopStockLabel(product) }}
                </span>
                <span v-if="bestSellersAreReal" class="lw-shop-rank">#{{ i + 1 }}</span>
                <img v-if="product.image" :src="product.image" :alt="product.name" @error="onShopImgError(product)" />
                <div v-else class="lw-shop-card-img-placeholder"><i :class="categoryIcon(shopCategoryName(product))"></i></div>
              </div>
              <div class="lw-shop-card-body">
                <p v-if="shopCategoryName(product)" class="lw-shop-card-cat">{{ shopCategoryName(product) }}</p>
                <p class="lw-shop-card-name" :title="product.name">{{ product.name }}</p>
                <p class="lw-shop-card-sku"><i class="fa-solid fa-barcode" aria-hidden="true"></i>{{ product.id }}</p>
                <p class="lw-shop-card-price">
                  <span class="lw-shop-price-currency">₱</span>{{ product.price.toFixed(2) }}
                  <span class="lw-shop-price-unit">{{ priceUnit(product) }}</span>
                </p>
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

      <!-- SHOP BY CATEGORY -->
      <section
        v-if="!shopLoading && !shopError && categoryChips.length"
        class="lw-block lw-block--tight"
      >
        <div class="lw-sec-head">
          <h2 class="lw-shop-title"><span class="lw-title-bar"></span>Shop by Category</h2>
          <p class="lw-shop-sub">Jump straight to what you need.</p>
        </div>
        <div class="lw-chips">
          <button
            type="button"
            class="lw-chip"
            :class="{ 'lw-chip--active': shopCategory === '' }"
            @click="selectCategory('')"
          >
            <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
            All
            <span class="lw-chip-count">{{ shopProducts.length }}</span>
          </button>
          <button
            v-for="cat in categoryChips"
            :key="cat.CategoryID"
            type="button"
            class="lw-chip"
            :class="{ 'lw-chip--active': String(shopCategory) === String(cat.CategoryID) }"
            @click="selectCategory(cat.CategoryID)"
          >
            <i :class="categoryIcon(cat.CategoryName)" aria-hidden="true"></i>
            {{ cat.CategoryName }}
            <span class="lw-chip-count">{{ cat.count }}</span>
          </button>
        </div>
      </section>

      <!-- ALL PRODUCTS — grouped by category, with a sticky search bar -->
      <section
        v-if="!shopLoading && !shopError"
        ref="catalogEl"
        class="lw-block lw-catalog"
      >
        <div class="lw-sec-head">
          <h2 class="lw-shop-title"><span class="lw-title-bar"></span>All Products</h2>
          <p class="lw-shop-sub">Prices are subject to change — please call to confirm availability.</p>
        </div>

        <div class="lw-catalog-bar">
          <div class="lw-shop-toolbar">
            <div class="lw-shop-search">
              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
              <input v-model="shopSearch" type="text" placeholder="Search products, SKU, or barcode…" />
            </div>
            <div class="lw-shop-filter">
              <select v-model="shopCategory">
                <option value="">All Categories</option>
                <option v-for="cat in shopCategories" :key="cat.CategoryID" :value="cat.CategoryID">
                  {{ cat.CategoryName }}
                </option>
              </select>
              <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </div>
            <button v-if="isFiltering" type="button" class="lw-clear-btn" @click="clearFilters">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
            </button>
          </div>
          <p class="lw-shop-count">
            {{ filteredShopProducts.length }} product{{ filteredShopProducts.length === 1 ? '' : 's' }} found
          </p>
        </div>

        <div v-if="filteredShopProducts.length === 0" class="lw-shop-empty">
          <i class="fa-solid fa-box-open"></i>
          <p>No products found</p>
          <button v-if="isFiltering" type="button" class="lw-shop-retry" @click="clearFilters">Clear filters</button>
        </div>

        <div v-for="group in groupedProducts" :key="group.key" class="lw-group">
          <div class="lw-group-head">
            <h3 class="lw-group-title">
              <span class="lw-group-icon"><i :class="categoryIcon(group.name)" aria-hidden="true"></i></span>
              {{ group.name }}
              <span class="lw-group-count">{{ group.items.length }}</span>
            </h3>
            <button
              v-if="group.items.length > GROUP_PREVIEW && !isFiltering"
              type="button"
              class="lw-group-more"
              @click="toggleGroup(group.key)"
            >
              {{ expandedGroups.has(group.key) ? 'Show less' : `Show all ${group.items.length}` }}
              <i class="fa-solid" :class="expandedGroups.has(group.key) ? 'fa-chevron-up' : 'fa-chevron-down'" aria-hidden="true"></i>
            </button>
          </div>

          <div class="lw-shop-grid lw-shop-grid--groups">
            <div
              v-for="product in visibleItems(group)"
              :key="product.id"
              class="lw-shop-card"
              :class="{ 'lw-shop-card--oos': shopStockStatus(product) === 'oos' }"
            >
              <div class="lw-shop-card-img" :class="{ 'lw-shop-card-img--photo': product.image }">
                <span class="lw-shop-stock-pill">
                  <span class="lw-shop-dot" :class="`lw-shop-dot--${shopStockStatus(product)}`"></span>
                  {{ shopStockLabel(product) }}
                </span>
                <img v-if="product.image" :src="product.image" :alt="product.name" @error="onShopImgError(product)" />
                <div v-else class="lw-shop-card-img-placeholder"><i :class="categoryIcon(group.name)"></i></div>
              </div>
              <div class="lw-shop-card-body">
                <p class="lw-shop-card-name" :title="product.name">{{ product.name }}</p>
                <p class="lw-shop-card-sku"><i class="fa-solid fa-barcode" aria-hidden="true"></i>{{ product.id }}</p>
                <p class="lw-shop-card-price">
                  <span class="lw-shop-price-currency">₱</span>{{ product.price.toFixed(2) }}
                  <span class="lw-shop-price-unit">{{ priceUnit(product) }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- HOW TO ORDER -->
      <section v-if="!shopLoading && !shopError" class="lw-block lw-block--tight">
        <div class="lw-sec-head">
          <h2 class="lw-shop-title"><span class="lw-title-bar"></span>How to Order</h2>
          <p class="lw-shop-sub">Three simple steps.</p>
        </div>
        <div class="lw-steps">
          <div class="lw-step">
            <span class="lw-step-num">1</span>
            <div>
              <p class="lw-step-title">Browse &amp; note your items</p>
              <p class="lw-step-text">Pick the materials and quantities you need from the list.</p>
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
      <section class="lw-visit">
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

    </main>

    <!-- FOOTER -->
    <footer class="lw-footer">
      <span>© {{ year }} Shem Steel Construction Company. All rights reserved.</span>
      <div class="lw-footer-right">
        <span>support@sheems.example</span>
        <span class="lw-sep" aria-hidden="true">·</span>
        <span>{{ SHOP_PHONE }}</span>
      </div>
    </footer>

    <!-- LOGIN MODAL — original card, form, and all validation / error-handling
         logic is unchanged; it lives behind the "Login" button. -->
    <Teleport to="body">
      <transition name="fade">
        <div
          v-if="showLoginModal"
          ref="loginModalBackdrop"
          class="lw-modal-backdrop"
          tabindex="-1"
          @click.self="closeLoginModal"
          @keydown.esc="closeLoginModal"
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

                <div class="lw-field">
                  <div class="lw-lbl-row">
                    <label for="lw-pass" class="lw-lbl">Password</label>
                    <button type="button" class="lw-forgot" @click="showForgotHelp = true">Forgot password?</button>
                  </div>
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
                    <button
                      type="button"
                      class="lw-eye"
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

/* ─── LOGIN MODAL ────────────────────────── */
const showLoginModal    = ref(false)
const loginModalBackdrop = ref<HTMLElement | null>(null)

function openLoginModal() {
  error.value = ''
  fieldErrors.value = { email: '', password: '' }
  showLoginModal.value = true
  // Focus the backdrop so a bare Escape (no prior click) closes it too.
  nextTick(() => loginModalBackdrop.value?.focus())
}
function closeLoginModal() {
  if (loading.value) return // don't let people dismiss mid-submit
  showLoginModal.value = false
}
// Belt-and-suspenders Escape handling: works even if focus landed on an
// input inside the modal rather than the backdrop itself.
function onGlobalKeydown(e: KeyboardEvent) {
  if (e.key !== 'Escape') return
  if (showForgotHelp.value) { showForgotHelp.value = false; return }
  if (showLoginModal.value) { closeLoginModal(); return }
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
// visitor's, so it's correct for someone browsing from abroad too.
// Mon–Sat, 7:00 AM – 5:00 PM; Sunday closed.
const openNow = ref<boolean | null>(null)
function computeOpenNow() {
  const manila = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Manila' }))
  const day  = manila.getDay()
  const mins = manila.getHours() * 60 + manila.getMinutes()
  openNow.value = day !== 0 && mins >= 7 * 60 && mins < 17 * 60
}
onMounted(computeOpenNow)

/* ─── PUBLIC PRODUCT CATALOG ─────────────────────────────────
 * Uses the SAME `api` axios instance as POS.vue (baseURL already
 * points at the Laravel API host, e.g. :8000, instead of the Vue dev
 * server's own origin — a plain relative fetch() doesn't know that and
 * silently hits the SPA's index.html instead of the backend).
 * `/product-list` and `/categories` both sit in the "PUBLIC API
 * ROUTES (NO AUTH REQUIRED)" block in routes/api.php with no
 * auth:sanctum middleware, so calling them unauthenticated is safe —
 * they won't trip any 401-redirect interceptor the way a protected
 * route would.
 * ────────────────────────────────────────────────────────── */
interface ShopProduct {
  id: number | string
  name: string
  price: number
  stock: number
  image: string | null
  category: number | string | null
  pricingType: 'sqm' | 'piece'
  featured: boolean
}
interface ShopCategory {
  CategoryID: number | string
  CategoryName: string
  [key: string]: unknown
}

const GROUP_PREVIEW     = 8  // products shown per category before "Show all"
const BEST_SELLER_COUNT = 6

const shopProducts   = ref<ShopProduct[]>([])
const shopCategories = ref<ShopCategory[]>([])
const shopLoading    = ref(false)
const shopError      = ref('')
const shopSearch     = ref('')
const shopCategory   = ref<string | number>('')

function normalizeShopProduct(p: any): ShopProduct {
  const pricingTypeRaw = (p.PricingType ?? p.pricing_type ?? 'piece').toString().toLowerCase()
  return {
    id: p.ProductID ?? p.id,
    name: p.ProductName ?? p.name ?? 'Unnamed product',
    price: Number(p.SellingPrice ?? p.price ?? 0),
    stock: Number(p.inventory?.QuantityOnHand ?? p.Stock ?? p.stock ?? 0),
    image: p.ImageURL ?? p.image ?? null,
    category: p.CategoryID ?? p.category ?? null,
    pricingType: pricingTypeRaw === 'sqm' ? 'sqm' : 'piece',
    // Optional manual flag: if the backend ever sends one, featured products
    // are used as the fallback for the top row when there's no sales data.
    featured: Boolean(p.IsFeatured ?? p.is_featured ?? p.featured ?? false),
  }
}

async function loadShopCatalog() {
  shopLoading.value = true
  shopError.value = ''
  try {
    const [prodRes, catRes] = await Promise.all([
      api.get('/api/product-list'),
      api.get('/api/categories'),
    ])
    shopProducts.value = (prodRes.data || []).map(normalizeShopProduct)
    shopCategories.value = catRes.data?.categories ?? catRes.data ?? []
  } catch (e) {
    shopError.value = 'Unable to load products right now. Please try again shortly.'
    console.error('loadShopCatalog error:', e)
  } finally {
    shopLoading.value = false
  }
}
onMounted(loadShopCatalog)

function onShopImgError(product: ShopProduct) { product.image = null }

// Resolves a product's category id to its display name.
function shopCategoryName(product: ShopProduct): string | null {
  const cat = shopCategories.value.find(c => String(c.CategoryID) === String(product.category))
  return cat ? cat.CategoryName : null
}

// Customers see a status, not an exact count, so the storefront doesn't hand
// out precise inventory levels. "Low" is shown as "Few left" because
// "Low Stock" is internal inventory language.
function shopStockStatus(p: ShopProduct): 'ok' | 'low' | 'oos' {
  return p.stock <= 0 ? 'oos' : p.stock <= 5 ? 'low' : 'ok'
}
function shopStockLabel(p: ShopProduct) {
  const s = shopStockStatus(p)
  return s === 'oos' ? 'Out of Stock' : s === 'low' ? 'Few left' : 'In Stock'
}
function priceUnit(p: ShopProduct) {
  return p.pricingType === 'sqm' ? '/ sqm' : '/ piece'
}

// Category icon, used for chips, group headings and image placeholders.
// Matched by keyword so new categories still get something sensible.
function categoryIcon(name: string | null): string {
  const n = (name || '').toLowerCase()
  if (n.includes('roof'))                                          return 'fa-solid fa-house-chimney'
  if (n.includes('fasten') || n.includes('screw') || n.includes('nail')) return 'fa-solid fa-screwdriver-wrench'
  if (n.includes('insul'))                                         return 'fa-solid fa-temperature-low'
  if (n.includes('seal') || n.includes('adhes'))                   return 'fa-solid fa-fill-drip'
  if (n.includes('struct') || n.includes('steel') || n.includes('purlin')) return 'fa-solid fa-industry'
  if (n.includes('poly') || n.includes('panel'))                   return 'fa-solid fa-border-all'
  return 'fa-solid fa-cube'
}

/* ─── SEARCH / FILTER ────────────────────────────────────── */
const normalizedShopSearch = computed(() => shopSearch.value.trim().toLowerCase())
const isFiltering = computed(() => !!normalizedShopSearch.value || shopCategory.value !== '')

const filteredShopProducts = computed(() => {
  const q = normalizedShopSearch.value
  const cat = shopCategory.value
  return shopProducts.value.filter(p => {
    if (cat !== '' && String(p.category) !== String(cat)) return false
    if (!q) return true
    return p.name.toLowerCase().includes(q) || String(p.id).toLowerCase().includes(q)
  })
})

function clearFilters() {
  shopSearch.value = ''
  shopCategory.value = ''
}

/* ─── CATEGORY CHIPS ─────────────────────────────────────── */
const catalogEl = ref<HTMLElement | null>(null)

const categoryChips = computed(() =>
  shopCategories.value
    .map(c => ({
      ...c,
      count: shopProducts.value.filter(p => String(p.category) === String(c.CategoryID)).length,
    }))
    .filter(c => c.count > 0)
)

function selectCategory(id: string | number) {
  shopCategory.value = id
  nextTick(() => catalogEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
}

/* ─── GROUPED PRODUCT LIST ───────────────────────────────── */
interface ProductGroup { key: string; name: string; items: ShopProduct[] }

const stockRank = (p: ShopProduct) => ({ ok: 0, low: 1, oos: 2 }[shopStockStatus(p)])

const groupedProducts = computed<ProductGroup[]>(() => {
  const order = new Map(shopCategories.value.map((c, i) => [String(c.CategoryID), i]))
  const map = new Map<string, ProductGroup>()
  for (const p of filteredShopProducts.value) {
    const key = p.category == null ? 'other' : String(p.category)
    if (!map.has(key)) map.set(key, { key, name: shopCategoryName(p) ?? 'Other', items: [] })
    map.get(key)!.items.push(p)
  }
  const groups = [...map.values()]
  // Out-of-stock items sink to the end of each group (sort is stable).
  groups.forEach(g => g.items.sort((a, b) => stockRank(a) - stockRank(b)))
  groups.sort((a, b) => (order.get(a.key) ?? 999) - (order.get(b.key) ?? 999))
  return groups
})

const expandedGroups = ref<Set<string>>(new Set())
function toggleGroup(key: string) {
  const next = new Set(expandedGroups.value)
  next.has(key) ? next.delete(key) : next.add(key)
  expandedGroups.value = next
}
// While searching/filtering every match is shown; otherwise each category
// shows its first few items with a "Show all" toggle.
function visibleItems(group: ProductGroup) {
  if (isFiltering.value || expandedGroups.value.has(group.key)) return group.items
  return group.items.slice(0, GROUP_PREVIEW)
}

/* ─── BEST SELLERS ───────────────────────────────────────────
 * Preferred source: GET /api/public/best-sellers, returning an array of
 * products (same shape as /api/product-list) ranked by quantity sold in
 * the last 30–90 days. Until that endpoint exists (or if it returns fewer
 * than 3 items) the top row falls back to products flagged as featured,
 * and then to the newest in-stock products, and is labelled
 * "Featured Products" instead of "Best Sellers" so it never claims
 * sales data it doesn't have. Rank badges (#1, #2…) only show for real
 * best sellers.
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
    bestSellersApi.value = [] // endpoint not available yet — use the fallback
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

/* ─── LOCATION / MAP ─────────────────────────────────────────
 * Shem Steel Construction Company — 849 Ochoa Ave, Butuan City,
 * Agusan del Norte. Uses the no-API-key "output=embed" form of Google
 * Maps for the iframe, and a clean maps.google.com deep link (rather
 * than the long tracking URL from Google's share sheet) for Directions.
 * ────────────────────────────────────────────────────────── */
const SHOP_ADDRESS = '849 Ochoa Ave, Butuan City, Agusan del Norte'
const SHOP_LAT = 8.9565024
const SHOP_LNG = 125.5325086
const mapEmbedSrc = `https://www.google.com/maps?q=SHEM+STEEL+CONSTRUCTION+SUPPLY@${SHOP_LAT},${SHOP_LNG}&output=embed`
const directionsUrl = `https://www.google.com/maps/dir/?api=1&destination=${SHOP_LAT},${SHOP_LNG}`
</script>

<!--
  Modals (login, forgot-password) render via <Teleport to="body">,
  which moves them OUTSIDE the .lw wrapper div at the DOM level. The color
  tokens below are defined as CSS custom properties scoped to .lw, so a
  teleported node can't see them — background: var(--lw-card-bg) resolves to
  nothing there and the "solid" modal background silently becomes transparent.
  This small global (unscoped) block re-declares just the tokens the modals
  need on :root / html[data-theme="dark"], so they resolve correctly no
  matter where in the DOM the node actually lives.
-->
<style>
:root {
  --lw-accent:       #F07020;
  --lw-accent-dark:  #C85810;
  --lw-accent-light: #FF8A3D;

  --lw-card-bg:          #FFFFFF;
  --lw-card-border:      #E5DACA;
  --lw-card-head-bg1:    #FCFBF9;
  --lw-card-head-bg2:    #FAFAF8;
  --lw-card-head-border: #EDE6D8;
  --lw-card-foot-bg:     #FAFAF8;

  --lw-border-soft: #EDE6D8;

  --lw-text-primary:   #14100A;
  --lw-text-strong:    #170D02;
  --lw-text-label:     #5C3818;
  --lw-text-secondary: #7A5030;
  --lw-text-faint:     #A08060;
  --lw-text-faint-3:   #C4A882;
  --lw-text-faint-4:   #C0A880;

  --lw-input-bg:       #FDFCFA;
  --lw-input-bg-focus: #FFFFFF;
  --lw-input-border:   #DDD0BC;
  --lw-input-border-h: #BEA07A;
  --lw-placeholder:    #D0B898;
  --lw-icon-muted:     #C8A882;

  --lw-error-bg:     #FFF4F0;
  --lw-error-border: #FDCFB8;
  --lw-error-text:   #8A2800;

  --lw-backdrop: #1C130A;
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

  --lw-bg:           #F4F2ED;
  --lw-nav-bg:       #FFFFFF;
  --lw-nav-border:   #E5DACA;
  --lw-hero-bg:      #F8F7F4;

  --lw-card-bg:        #FFFFFF;
  --lw-card-border:    #E5DACA;
  --lw-card-head-bg1:  #FCFBF9;
  --lw-card-head-bg2:  #FAFAF8;
  --lw-card-head-border: #EDE6D8;
  --lw-card-foot-bg:   #FAFAF8;

  --lw-border:        #E4DDD3;
  --lw-border-soft:   #EDE6D8;
  --lw-feat-border:   #EAE0D0;
  --lw-feat-border-h: #D4A070;

  --lw-text-primary:  #14100A;
  --lw-text-strong:   #170D02;
  --lw-text-label:    #5C3818;
  --lw-text-secondary:#7A5030;
  --lw-text-muted:    #9A6840;
  --lw-text-faint:    #A08060;
  --lw-text-faint-2:  #B8A080;
  --lw-text-faint-3:  #C4A882;
  --lw-text-faint-4:  #C0A880;

  --lw-input-bg:        #FDFCFA;
  --lw-input-bg-focus:  #FFFFFF;
  --lw-input-border:    #DDD0BC;
  --lw-input-border-h:  #BEA07A;
  --lw-placeholder:     #D0B898;
  --lw-icon-muted:      #C8A882;

  --lw-footer-bg:     #FFFFFF;
  --lw-footer-border: #E8E0D4;

  --lw-fade-1:      rgba(248, 247, 244, 0.97);
  --lw-fade-2:      rgba(248, 247, 244, 0.55);
  --lw-fade-3:      rgba(240, 112, 32, 0.05);

  --lw-glass-bg:     rgba(255, 255, 255, 0.90);
  --lw-glass-border: rgba(255, 255, 255, 0.7);
  --lw-glass-shadow: rgba(20, 10, 5, 0.13);

  --lw-error-bg:     #FFF4F0;
  --lw-error-border: #FDCFB8;
  --lw-error-text:   #8A2800;

  --lw-backdrop: #1C130A;

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
  box-shadow: 0 1px 0 rgba(80, 40, 10, 0.02);
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
.lw-logo-img { width: 34px; height: 34px; object-fit: contain; flex-shrink: 0; }
.lw-brand-text { display: flex; flex-direction: column; gap: 1px; }
.lw-brand-name { font-size: 15px; font-weight: 700; color: var(--lw-text-primary); letter-spacing: -0.01em; line-height: 1.2; }
.lw-brand-sub { font-size: 9px; color: var(--lw-accent); font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; line-height: 1.3; }
.lw-nav-right { display: flex; align-items: center; gap: 12px; }
.lw-nav-hint { font-size: 12px; color: var(--lw-text-faint-2); font-weight: 500; }

/* Appearance button (was gear-only icon) */
.lw-settings-wrap { position: relative; flex-shrink: 0; }
.lw-appearance-btn {
  display: flex; align-items: center; gap: 7px;
  height: 34px; padding: 0 13px;
  border: 1.5px solid var(--lw-input-border);
  border-radius: 9px;
  background: var(--lw-input-bg);
  color: var(--lw-text-secondary);
  font-family: inherit; font-size: 12px; font-weight: 600;
  cursor: pointer;
  transition: border-color 0.18s ease, background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
}
.lw-appearance-btn i { font-size: 12px; color: var(--lw-text-faint); }
.lw-appearance-btn:hover { border-color: var(--lw-input-border-h); color: var(--lw-accent); }
.lw-appearance-btn:hover i { color: var(--lw-accent); }
.lw-appearance-btn--open {
  border-color: var(--lw-accent);
  color: var(--lw-accent);
  background: var(--lw-input-bg-focus);
  box-shadow: 0 0 0 3px rgba(240, 112, 32, 0.14);
}
.lw-appearance-btn--open i { color: var(--lw-accent); }

.lw-settings-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 240px;
  background: var(--lw-card-bg);
  border: 1px solid var(--lw-card-border);
  border-radius: 14px;
  box-shadow: 0 4px 14px rgba(20, 10, 5, 0.10), 0 16px 40px rgba(20, 10, 5, 0.16);
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
.lw-pill-track--dark { background: #171310; border-color: rgba(240, 112, 32, 0.35); box-shadow: 0 0 8px rgba(240, 112, 32, 0.12); }
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
/* Real product photo (roofing sheets + purlins) filling the hero's
   right side. .lw-hero-fade (below) sits above this and fades it into
   the page background toward the left, where the headline text sits,
   the same way the reference layout blends its photo into white. */
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

/* ─── CATEGORY CHIPS ─────────────────────── */
.lw-chips { display: flex; flex-wrap: wrap; gap: 9px; }
.lw-chip {
  display: inline-flex; align-items: center; gap: 8px;
  height: 36px; padding: 0 14px;
  border: 1.5px solid var(--lw-input-border); border-radius: 999px;
  background: var(--lw-card-bg); color: var(--lw-text-secondary);
  font-family: inherit; font-size: 12.5px; font-weight: 600;
  cursor: pointer;
  transition: border-color 0.15s, background-color 0.15s, color 0.15s, transform 0.15s;
}
.lw-chip i { font-size: 11px; color: var(--lw-accent); }
.lw-chip:hover { border-color: var(--lw-accent); color: var(--lw-accent); transform: translateY(-1px); }
.lw-chip-count {
  font-size: 10.5px; font-weight: 700;
  padding: 1px 7px; border-radius: 999px;
  background: var(--lw-input-bg); color: var(--lw-text-faint);
}
.lw-chip--active { background: var(--lw-accent); border-color: var(--lw-accent); color: #fff; }
.lw-chip--active i { color: #fff; }
.lw-chip--active:hover { color: #fff; }
.lw-chip--active .lw-chip-count { background: rgba(255, 255, 255, 0.24); color: #fff; }

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
.lw-shop-filter { position: relative; display: flex; align-items: center; flex-shrink: 0; }
.lw-shop-filter select {
  appearance: none; -webkit-appearance: none;
  height: 38px; padding: 0 30px 0 12px;
  border: 1.5px solid var(--lw-input-border); border-radius: 9px;
  background: var(--lw-input-bg); color: var(--lw-text-secondary);
  font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer; outline: none;
  transition: border-color 0.15s, background-color 0.22s, color 0.22s;
}
.lw-shop-filter select:focus { border-color: var(--lw-accent); }
.lw-shop-filter i { position: absolute; right: 10px; font-size: 9px; color: var(--lw-icon-muted); pointer-events: none; }
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

/* ─── CATEGORY GROUPS ────────────────────── */
.lw-group { margin-bottom: 30px; }
.lw-group-head {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; margin-bottom: 12px;
}
.lw-group-title {
  display: flex; align-items: center; gap: 10px;
  font-family: 'Space Grotesk', 'Inter', system-ui, sans-serif;
  font-size: 16px; font-weight: 700; color: var(--lw-text-primary);
  letter-spacing: -0.01em;
}
.lw-group-icon {
  width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(240, 112, 32, 0.12); color: var(--lw-accent); font-size: 12px;
}
.lw-group-count {
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 11px; font-weight: 700;
  padding: 2px 8px; border-radius: 999px;
  background: var(--lw-card-bg); border: 1px solid var(--lw-border-soft);
  color: var(--lw-text-faint);
}
.lw-group-more {
  display: inline-flex; align-items: center; gap: 7px;
  background: none; border: none; cursor: pointer;
  font-family: inherit; font-size: 12px; font-weight: 700; color: var(--lw-accent);
  padding: 6px 8px; border-radius: 8px; white-space: nowrap;
  transition: background-color 0.15s;
}
.lw-group-more:hover { background: rgba(240, 112, 32, 0.10); }
.lw-group-more i { font-size: 9px; }

/* ─── PRODUCT CARDS ──────────────────────── */
.lw-shop-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 16px;
  margin-bottom: 0;
}
.lw-shop-grid.lw-shop-grid--groups {
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
}
.lw-shop-card {
  position: relative;
  background: var(--lw-card-bg);
  border: 1.5px solid var(--lw-feat-border);
  border-radius: 16px;
  overflow: hidden;
  display: flex; flex-direction: column;
  box-shadow: 0 1px 2px rgba(80, 40, 10, 0.03);
  transition: border-color 0.18s, box-shadow 0.18s, transform 0.18s, background-color 0.22s;
}
.lw-shop-card:hover {
  border-color: var(--lw-feat-border-h);
  box-shadow: 0 10px 24px rgba(240, 112, 32, 0.16), 0 2px 8px rgba(20, 10, 5, 0.06);
  transform: translateY(-4px);
}
.lw-shop-card--oos { opacity: 0.62; }
.lw-shop-card--oos:hover { transform: none; box-shadow: 0 1px 2px rgba(80, 40, 10, 0.03); border-color: var(--lw-feat-border); }

/* One fixed image shape for every card, so rows line up whether or not a
   product has a photo. Photos are contained (not cropped) on white. */
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

/* Floating stock badge on the image */
.lw-shop-stock-pill {
  position: absolute; top: 8px; left: 8px; z-index: 2;
  display: flex; align-items: center; gap: 5px;
  padding: 4px 9px 4px 7px;
  border-radius: 999px;
  background: rgba(18, 14, 8, 0.72);
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
  font-size: 9px; font-weight: 700; letter-spacing: 0.02em; color: #fff;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
}
.lw-shop-stock-pill .lw-shop-dot { width: 6px; height: 6px; flex-shrink: 0; }

/* Rank badge for real best sellers */
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
.lw-shop-card-cat {
  font-size: 9px; font-weight: 700; color: var(--lw-accent);
  text-transform: uppercase; letter-spacing: 0.07em;
}
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
.lw-shop-card-price {
  display: flex; align-items: baseline; gap: 3px;
  font-size: 16px; font-weight: 800; color: var(--lw-accent); margin-top: 5px;
  padding-top: 7px; border-top: 1px dashed var(--lw-border-soft);
}
.lw-shop-price-currency { font-size: 11px; font-weight: 700; }
.lw-shop-price-unit { font-size: 10px; font-weight: 600; color: var(--lw-text-faint); }
.lw-shop-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.lw-shop-dot--ok  { background: #10b981; }
.lw-shop-dot--low { background: #f97316; }
.lw-shop-dot--oos { background: #f43f5e; }
.lw-shop-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 0; gap: 10px; color: var(--lw-text-faint); font-size: 12.5px; }
.lw-shop-empty i { font-size: 30px; }

/* ─── BEST SELLERS CAROUSEL ──────────────── *
 * A single horizontally-scrolling row with an always-visible nav button
 * on each end (each hides itself when there's nothing left to scroll to).
 * ─────────────────────────────────────────── */
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
  box-shadow: 0 4px 14px rgba(20, 10, 5, 0.14);
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
  background: #171310;
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
.lw-visit-photo-sub { font-size: 10.5px; font-weight: 600; color: #F4EDE3; text-transform: uppercase; letter-spacing: 0.1em; }

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
  box-shadow: 0 1px 2px rgba(80, 40, 10, 0.04), 0 8px 24px rgba(80, 40, 10, 0.08), 0 24px 64px rgba(80, 40, 10, 0.08);
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
.lw-forgot { font-size: 11px; font-weight: 600; color: var(--lw-accent); background: none; border: none; cursor: pointer; font-family: inherit; padding: 0; transition: opacity 0.15s; }
.lw-forgot:hover { opacity: 0.65; }
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
.lw-inp--error:focus { border-color: var(--lw-error-text); box-shadow: 0 0 0 3.5px rgba(138, 40, 0, 0.13); }
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
  /* Transparent backdrop — no dimming overlay behind the modal.
     A light blur keeps the modal readable against busy page content
     without reintroducing a solid/tinted background. Set to `none`
     if you want zero visual effect at all. */
  background: transparent;
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
  z-index: 200; display: flex; align-items: center; justify-content: center; padding: 16px;
}
.lw-modal-backdrop:focus { outline: none; }
.lw-modal-backdrop--forgot { z-index: 260; }
.lw-modal {
  width: 100%; max-width: 360px; background: var(--lw-card-bg); border: 1px solid var(--lw-card-border);
  border-radius: 18px; padding: 28px 26px 24px; text-align: center;
  box-shadow: 0 24px 64px rgba(20, 10, 5, 0.22);
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
}
@media (max-width: 480px) {
  .lw-nav-inner { padding: 0 16px; gap: 8px; }
  .lw-nav-right { gap: 8px; }
  .lw-appearance-btn span { display: none; }
  .lw-appearance-btn { width: 34px; padding: 0; justify-content: center; }
  .lw-signin-btn span { display: none; }
  .lw-signin-btn { width: 34px; height: 34px; padding: 0; justify-content: center; }
  .lw-card-head, .lw-card-body, .lw-card-foot, .lw-card-divider { padding-left: 16px; padding-right: 16px; }
}
</style>