<template>
  <Layout>
    <div class="prod-list-page">

      <!-- ═════════════ 1. PAGE HEADER ═════════════ -->
      <header class="page-header">
        <div>
          <p class="page-eyebrow">Inventory</p>
          <h1 class="page-title">{{ view === 'cuts' ? 'Cut Tracking' : 'Product List' }}</h1>
          <p class="page-sub">
            {{ view === 'cuts'
              ? 'Leftover pieces from cut sales. Mark them used, scrap them or put them back.'
              : 'Stock by size, restocking and product management.' }}
          </p>
        </div>
        <RouterLink to="/products/create" class="btn btn--primary">
          <i class="fa-solid fa-plus"></i> Add Product
        </RouterLink>
      </header>

      <!-- ═════════════ 2. VIEW SWITCH (Products / Cut tracking) ═════════════ -->
      <nav class="view-tabs" role="tablist" aria-label="Inventory views">
        <button type="button" role="tab" class="view-tab" :class="{ 'view-tab--on': view === 'products' }"
          :aria-selected="view === 'products'" @click="setView('products')">
          <i class="fa-solid fa-boxes-stacked"></i>
          <span>Products</span>
          <span class="vt-count">{{ products.length }}</span>
        </button>
        <button type="button" role="tab" class="view-tab" :class="{ 'view-tab--on': view === 'cuts' }"
          :aria-selected="view === 'cuts'" @click="setView('cuts')">
          <i class="fa-solid fa-scissors"></i>
          <span>Cut tracking</span>
          <span class="vt-count" :class="{ 'vt-count--blue': availableCount > 0 }">{{ availableCount }}</span>
        </button>
      </nav>

      <!-- ═════════════ 3. ALERTS ═════════════ -->
      <transition name="alert-fade">
        <div v-if="successMessage" class="alert alert--success" role="status">
          <i class="fa-solid fa-circle-check"></i>
          <span>{{ successMessage }}</span>
          <button class="alert-close" @click="successMessage = ''" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>
      <transition name="alert-fade">
        <div v-if="errorMessage" class="alert alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>{{ errorMessage }}</span>
          <button v-if="loadFailed" class="link-btn" @click="reload">Try again</button>
          <button class="alert-close" @click="errorMessage = ''" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>

      <!-- ════════════════════════════════════════════════════════
           PRODUCTS VIEW
      ═════════════════════════════════════════════════════════ -->
      <template v-if="view === 'products'">

        <!-- Summary strip -->
        <section v-if="!loading" class="kpi-strip" aria-label="Inventory summary">
          <button type="button" class="kpi-card" @click="resetFilters">
            <span class="kpi-icon kpi-icon--accent"><i class="fa-solid fa-boxes-stacked"></i></span>
            <span class="kpi-body">
              <span class="kpi-label">Products</span>
              <span class="kpi-value">{{ products.length }}</span>
              <span class="kpi-hint">{{ totalSizes }} sizes tracked</span>
            </span>
          </button>

          <!-- LOW STOCK: above 0 but at/below the reorder level -->
          <button type="button" class="kpi-card" :class="{ 'kpi-card--warn': lowStockCount > 0 }" @click="stockFilter = 'low'">
            <span class="kpi-icon" :class="lowStockCount > 0 ? 'kpi-icon--amber' : 'kpi-icon--green'">
              <i class="fa-solid" :class="lowStockCount > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check'"></i>
            </span>
            <span class="kpi-body">
              <span class="kpi-label">Low stock</span>
              <span class="kpi-value">{{ lowStockCount }}</span>
              <span class="kpi-hint">
                {{ lowStockCount === 0 ? 'No size is running low' : 'Sizes at or below their reorder level' }}
              </span>
            </span>
          </button>

          <!-- OUT OF STOCK: exactly 0 -->
          <button type="button" class="kpi-card" :class="{ 'kpi-card--alert': outOfStockCount > 0 }" @click="stockFilter = 'out'">
            <span class="kpi-icon" :class="outOfStockCount > 0 ? 'kpi-icon--red' : 'kpi-icon--green'">
              <i class="fa-solid" :class="outOfStockCount > 0 ? 'fa-circle-xmark' : 'fa-circle-check'"></i>
            </span>
            <span class="kpi-body">
              <span class="kpi-label">Out of stock</span>
              <span class="kpi-value">{{ outOfStockCount }}</span>
              <span class="kpi-hint">
                {{ outOfStockCount === 0 ? 'Every size has stock' : 'Sizes with 0 left' }}
              </span>
            </span>
          </button>

          <button type="button" class="kpi-card" @click="openCuts()">
            <span class="kpi-icon kpi-icon--blue"><i class="fa-solid fa-scissors"></i></span>
            <span class="kpi-body">
              <span class="kpi-label">Leftover pieces</span>
              <span class="kpi-value">{{ availableCount }}</span>
              <span class="kpi-hint">
                <template v-if="remnantTotalsByUnit.length">
                  {{ remnantTotalsByUnit.map(u => `${lenText(u.total)} ${u.unit}`).join(' · ') }} on hand
                </template>
                <template v-else>None on hand</template>
              </span>
            </span>
          </button>
        </section>

        <!-- Toolbar -->
        <section class="toolbar" aria-label="Search and filters">
          <div class="toolbar-row">
            <div class="search-wrap">
              <i class="fa-solid fa-magnifying-glass search-icon"></i>
              <input
                v-model="searchInput"
                type="search"
                class="search-input"
                placeholder="Search name, SKU, category or size…"
                aria-label="Search products"
              />
              <button v-if="searchInput" class="search-clear" @click="searchInput = ''" aria-label="Clear search">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>

            <!-- The badges below count SIZES (every size is its own item) -->
            <div class="stock-filter-toggle" role="tablist" aria-label="Filter by stock status">
              <button class="sf-btn" role="tab" :aria-selected="stockFilter === 'all'"
                :title="`${products.length} products · ${totalSizes} sizes`"
                :class="{ 'sf-btn--active': stockFilter === 'all' }" @click="stockFilter = 'all'">
                All <span class="sf-count sf-count--neutral">{{ totalSizes }}</span>
              </button>
              <button class="sf-btn sf-btn--ok" role="tab" :aria-selected="stockFilter === 'active'"
                :title="`${activeCount} sizes above their reorder level`"
                :class="{ 'sf-btn--active': stockFilter === 'active' }" @click="stockFilter = 'active'">
                <i class="fa-solid fa-circle-check"></i> Active
                <span class="sf-count sf-count--ok">{{ activeCount }}</span>
              </button>
              <button class="sf-btn sf-btn--warn" role="tab" :aria-selected="stockFilter === 'low'"
                :title="`${lowStockCount} sizes at or below their reorder level (still in stock)`"
                :class="{ 'sf-btn--active': stockFilter === 'low' }" @click="stockFilter = 'low'">
                <i class="fa-solid fa-triangle-exclamation"></i> Low stock
                <span v-if="lowStockCount > 0" class="sf-count sf-count--warn">{{ lowStockCount }}</span>
              </button>
              <button class="sf-btn sf-btn--danger" role="tab" :aria-selected="stockFilter === 'out'"
                :title="`${outOfStockCount} sizes with 0 in stock`"
                :class="{ 'sf-btn--active': stockFilter === 'out' }" @click="stockFilter = 'out'">
                <i class="fa-solid fa-circle-xmark"></i> Out of stock
                <span v-if="outOfStockCount > 0" class="sf-count">{{ outOfStockCount }}</span>
              </button>
            </div>
          </div>

          <div class="toolbar-row toolbar-row--secondary">
            <div class="select-group">
              <label class="select-label" for="cat-select">Category</label>
              <div class="select-wrap">
                <i class="fa-solid fa-layer-group input-icon"></i>
                <select id="cat-select" v-model="selectedCategory" @change="handleCategoryChange" class="form-select">
                  <option value="">All categories</option>
                  <option v-for="cat in categories" :key="cat.CategoryID" :value="String(cat.CategoryID)">
                    {{ cat.CategoryName }}
                  </option>
                </select>
                <i class="fa-solid fa-chevron-down select-caret"></i>
              </div>
            </div>

            <div class="select-group">
              <label class="select-label" for="prod-sort">Sort products</label>
              <div class="select-wrap">
                <i class="fa-solid fa-arrow-down-wide-short input-icon"></i>
                <select id="prod-sort" v-model="productSort" class="form-select">
                  <option value="status">Healthy first, low stock last</option>
                  <option value="attention">Needs restock first</option>
                  <option value="name">Name (A–Z)</option>
                  <option value="stock-desc">Most stock</option>
                  <option value="stock-asc">Least stock</option>
                </select>
                <i class="fa-solid fa-chevron-down select-caret"></i>
              </div>
            </div>

            <div class="select-group">
              <label class="select-label" for="size-sort">Sort sizes</label>
              <div class="select-wrap">
                <i class="fa-solid fa-ruler input-icon"></i>
                <select id="size-sort" v-model="variantSort" class="form-select">
                  <option value="active">Healthy first, then by size</option>
                  <option value="size">By size only</option>
                  <option value="low">Low stock first, then by size</option>
                </select>
                <i class="fa-solid fa-chevron-down select-caret"></i>
              </div>
            </div>

            <div class="toolbar-end">
              <button v-if="expandableCount > 0" type="button" class="btn btn--ghost btn--sm tool-btn" @click="toggleAllSizes">
                <i class="fa-solid" :class="allSizesOpen ? 'fa-compress' : 'fa-expand'"></i>
                {{ allSizesOpen ? 'Hide all sizes' : 'Show all sizes' }}
              </button>
              <button v-if="hasActiveFilters" class="btn btn--ghost btn--sm tool-btn" @click="resetFilters">
                <i class="fa-solid fa-rotate-left"></i> Reset
              </button>
            </div>
          </div>
        </section>

        <!-- Bulk action bar -->
        <transition name="alert-fade">
          <div v-if="selectedCount > 0" class="bulk-bar">
            <div class="bulk-bar-left">
              <i class="fa-solid fa-square-check"></i>
              <span><strong>{{ selectedCount }}</strong> product{{ selectedCount > 1 ? 's' : '' }} selected</span>
            </div>
            <div class="bulk-bar-actions">
              <button class="btn btn--ghost btn--sm" @click="clearSelection">
                <i class="fa-solid fa-xmark"></i> Clear
              </button>
              <button class="btn btn--danger btn--sm" @click="showBulkDeleteModal = true">
                <i class="fa-solid fa-trash-can"></i> Delete selected
              </button>
            </div>
          </div>
        </transition>

        <!-- Product table -->
        <section class="table-card">
          <table class="data-table prod-table">
            <colgroup>
              <col style="width:52px" />
              <col style="width:32%" />
              <col style="width:15%" />
              <col />
              <col style="width:24%" />
            </colgroup>
            <thead>
              <tr>
                <th class="th-check">
                  <input
                    type="checkbox"
                    class="chk"
                    :checked="allVisibleSelected"
                    :indeterminate="someVisibleSelected"
                    :disabled="filteredProducts.length === 0"
                    @change="toggleSelectAll"
                    aria-label="Select all products"
                  />
                </th>
                <th>Product</th>
                <th>Price</th>
                <th>Stock</th>
                <th class="th-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <!-- Loading skeleton -->
              <template v-if="loading">
                <tr v-for="n in 5" :key="'sk' + n" class="skeleton-row">
                  <td class="td-check"><span class="sk sk--box"></span></td>
                  <td><div class="cell-product"><span class="sk sk--avatar"></span><span class="sk sk--line"></span></div></td>
                  <td><span class="sk sk--line sk--short"></span></td>
                  <td><span class="sk sk--line"></span></td>
                  <td><span class="sk sk--line sk--short" style="margin-left:auto"></span></td>
                </tr>
              </template>

              <template v-else>
                <template v-for="product in filteredProducts" :key="product.ProductID">
                  <tr
                    class="prod-row"
                    :data-product-row="product.ProductID"
                    :class="{
                      'row-highlight': highlightedProductId === product.ProductID,
                      'row-selected': selectedIds.has(product.ProductID),
                      'row-low': productRank(product) === 1,
                      'row-out': productRank(product) === 2,
                      'row-open': isExpanded(product)
                    }"
                  >
                    <td class="td-check">
                      <input
                        type="checkbox"
                        class="chk"
                        :checked="selectedIds.has(product.ProductID)"
                        @change="toggleSelect(product.ProductID)"
                        :aria-label="`Select ${product.ProductName}`"
                      />
                    </td>

                    <!-- Product -->
                    <td data-label="Product">
                      <div class="cell-product">
                        <div class="product-avatar">
                          <img v-if="product.Product_Image" :src="imagePath(product.Product_Image)" class="product-avatar-img" :alt="product.ProductName" />
                          <span v-else class="product-avatar-initials">{{ product.ProductName.substring(0,2).toUpperCase() }}</span>
                        </div>
                        <div class="product-info">
                          <RouterLink :to="`/products/${product.ProductID}`" class="product-name-link" :title="product.ProductName">
                            {{ product.ProductName }}
                          </RouterLink>
                          <span class="product-sku">
                            {{ product.SKU }}
                            <span v-if="product.category?.CategoryName" class="product-cat">{{ product.category.CategoryName }}</span>
                          </span>
                        </div>
                      </div>
                    </td>

                    <!-- Price -->
                    <td data-label="Price" class="cell-price" :title="sizeCount(product) > 1 ? sizesTooltip(product) : ''">
                      {{ priceLabel(product) }}
                    </td>

                    <!-- Stock -->
                    <td data-label="Stock">
                      <div class="stock-cell">
                        <div class="stock-summary">
                          <span class="stock-total" :title="`${totalStock(product)} units across ${Math.max(sizeCount(product), 1)} size(s)`">
                            <strong>{{ totalStock(product) }}</strong>
                            <span>{{ sizeCount(product) > 1 ? 'total units' : 'in stock' }}</span>
                          </span>
                          <span class="stock-status" :class="stockClass(product)">
                            <i class="fa-solid" :class="stockIcon(product)"></i>
                            {{ stockLabel(product) }}
                          </span>
                        </div>

                        <!-- Size health: one bar + counts, so the row stays calm until sizes are opened -->
                        <div v-if="sizeCount(product) > 1" class="size-health" aria-hidden="true">
                          <span class="health-bar">
                            <span v-if="sizeBreakdown(product).ok"  class="hb hb--ok"  :style="{ flexGrow: sizeBreakdown(product).ok }"></span>
                            <span v-if="sizeBreakdown(product).low" class="hb hb--low" :style="{ flexGrow: sizeBreakdown(product).low }"></span>
                            <span v-if="sizeBreakdown(product).out" class="hb hb--oos" :style="{ flexGrow: sizeBreakdown(product).out }"></span>
                          </span>
                          <span class="health-text">
                            <span v-if="sizeBreakdown(product).ok"  class="legend-item legend-item--ok">{{ sizeBreakdown(product).ok }} OK</span>
                            <span v-if="sizeBreakdown(product).low" class="legend-item legend-item--low">{{ sizeBreakdown(product).low }} low</span>
                            <span v-if="sizeBreakdown(product).out" class="legend-item legend-item--oos">{{ sizeBreakdown(product).out }} out</span>
                          </span>
                        </div>
                      </div>
                    </td>

                    <!-- Actions -->
                    <td class="td-actions">
                      <div class="action-row">
                        <button
                          v-if="sizeCount(product) > 1"
                          type="button"
                          class="sizes-btn"
                          :class="{ 'sizes-btn--open': isExpanded(product) }"
                          :aria-expanded="isExpanded(product)"
                          :aria-controls="`sizes-${product.ProductID}`"
                          @click="toggleExpand(product.ProductID)"
                        >
                          <i class="fa-solid fa-chevron-down"></i>
                          <span class="sizes-btn-text">{{ isExpanded(product) ? 'Hide sizes' : 'View sizes' }}</span>
                          <span class="sizes-btn-count">{{ narrowed(product) ? `${listedCount(product)}/${sizeCount(product)}` : sizeCount(product) }}</span>
                        </button>
                        <button
                          class="restock-btn"
                          @click="openRestockModal(product)"
                        >
                          <i class="fa-solid fa-plus"></i> Restock
                        </button>
                        <button
                          :ref="setMenuButtonRef(product.ProductID)"
                          @click.stop="toggleMenu(product.ProductID)"
                          class="menu-btn"
                          :class="{ 'menu-btn--active': openMenuId === product.ProductID }"
                          :aria-label="`More actions for ${product.ProductName}`"
                          aria-haspopup="menu"
                        >
                          <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <!-- Sizes panel: only rendered when the button above is pressed -->
                  <tr v-if="isExpanded(product)" class="sizes-row" :class="{ 'row-highlight': highlightedProductId === product.ProductID }">
                    <td colspan="5" class="sizes-cell">
                      <div class="sizes-panel" :id="`sizes-${product.ProductID}`">
                        <p class="sizes-panel-head">
                          <span>
                            <strong>{{ listedCount(product) }}</strong>
                            {{ narrowed(product) ? STOCK_WORD[stockFilter] : '' }}
                            size{{ listedCount(product) === 1 ? '' : 's' }}
                          </span>
                          <span class="sizes-panel-hint">Click a size to restock it</span>
                        </p>
                        <ul class="size-grid" aria-label="Stock by size">
                          <li v-for="v in listedVariants(product)" :key="v.VariantID">
                            <button
                              type="button"
                              class="size-tile"
                              :class="variantClass(v)"
                              :style="{ '--fill': variantFill(v) + '%' }"
                              :title="`${variantTooltip(v)} — click to restock`"
                              :aria-label="`${v.Label}: ${variantStock(v)} in stock. Restock this size`"
                              @click="openRestockModal(product, v.VariantID)"
                            >
                              <span class="size-tile-top">
                                <span class="size-tile-label">{{ v.Label }}</span>
                                <i class="fa-solid fa-plus size-tile-plus" aria-hidden="true"></i>
                              </span>
                              <span class="size-tile-row">
                                <span class="size-tile-qty">{{ variantStock(v) }}</span>
                                <span v-if="variantRank(v) > 0" class="size-tile-tag">{{ variantRank(v) === 2 ? 'Out' : 'Low' }}</span>
                              </span>
                              <span class="size-tile-bar"><span></span></span>
                              <span class="size-tile-price">₱{{ formatPrice(v.SellingPrice) }}</span>
                            </button>
                          </li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                </template>

                <!-- Empty state -->
                <tr v-if="filteredProducts.length === 0">
                  <td colspan="5" class="td-empty">
                    <div class="empty-state">
                      <i :class="emptyIcon"></i>
                      <p>{{ emptyTitle }}</p>
                      <span class="empty-sub">{{ emptyHint }}</span>
                      <div class="empty-actions">
                        <button v-if="hasActiveFilters" class="btn btn--ghost btn--sm" @click="resetFilters">
                          <i class="fa-solid fa-rotate-left"></i> Clear filters
                        </button>
                        <RouterLink v-if="products.length === 0" to="/products/create" class="btn btn--primary btn--sm">
                          <i class="fa-solid fa-plus"></i> Add your first product
                        </RouterLink>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>

          <div v-if="!loading && products.length > 0" class="table-footer">
            Showing <strong>{{ filteredProducts.length }}</strong> of {{ products.length }} products
            <template v-if="stockFilter === 'low'"> · <strong>{{ visibleSizeCount }}</strong> size{{ visibleSizeCount === 1 ? '' : 's' }} running low</template>
            <template v-else-if="stockFilter === 'out'"> · <strong>{{ visibleSizeCount }}</strong> size{{ visibleSizeCount === 1 ? '' : 's' }} out of stock</template>
            <template v-else-if="stockFilter === 'active'"> · <strong>{{ visibleSizeCount }}</strong> active size{{ visibleSizeCount === 1 ? '' : 's' }}</template>
          </div>
        </section>
      </template>

      <!-- ════════════════════════════════════════════════════════
           CUT TRACKING VIEW
      ═════════════════════════════════════════════════════════ -->
      <template v-else>

        <!-- Stat cards double as filters -->
        <section class="cut-stats" aria-label="Leftover summary">
          <button type="button" class="cut-stat" :class="{ 'cut-stat--on': remnantFilter === 'available' }" @click="remnantFilter = 'available'">
            <span class="kpi-icon kpi-icon--blue"><i class="fa-solid fa-ruler-horizontal"></i></span>
            <span class="kpi-body">
              <span class="kpi-label">Available</span>
              <span class="kpi-value">{{ availableCount }}</span>
              <span class="kpi-hint">
                <template v-if="remnantTotalsByUnit.length">{{ remnantTotalsByUnit.map(u => `${lenText(u.total)} ${u.unit}`).join(' · ') }} on hand</template>
                <template v-else>None on hand</template>
              </span>
            </span>
          </button>
          <button type="button" class="cut-stat" :class="{ 'cut-stat--on': remnantFilter === 'used' }" @click="remnantFilter = 'used'">
            <span class="kpi-icon kpi-icon--green"><i class="fa-solid fa-check"></i></span>
            <span class="kpi-body">
              <span class="kpi-label">Used</span>
              <span class="kpi-value">{{ remnantCount('used') }}</span>
              <span class="kpi-hint">Sold or put to use</span>
            </span>
          </button>
          <button type="button" class="cut-stat" :class="{ 'cut-stat--on': remnantFilter === 'scrapped' }" @click="remnantFilter = 'scrapped'">
            <span class="kpi-icon kpi-icon--red"><i class="fa-solid fa-trash-can"></i></span>
            <span class="kpi-body">
              <span class="kpi-label">Scrapped</span>
              <span class="kpi-value">{{ remnantCount('scrapped') }}</span>
              <span class="kpi-hint">Thrown away</span>
            </span>
          </button>
        </section>

        <section class="toolbar" aria-label="Search and filter leftover pieces">
          <div class="toolbar-row">
            <div class="search-wrap">
              <i class="fa-solid fa-magnifying-glass search-icon"></i>
              <input
                v-model="remnantSearch"
                type="search"
                class="search-input"
                placeholder="Search product, SKU, size or receipt…"
                aria-label="Search leftover pieces"
              />
              <button v-if="remnantSearch" class="search-clear" @click="remnantSearch = ''" aria-label="Clear search">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>

            <div class="stock-filter-toggle" role="group" aria-label="Filter leftover pieces">
              <button v-for="f in REMNANT_FILTERS" :key="f.key" type="button" class="sf-btn"
                :class="{ 'sf-btn--active': remnantFilter === f.key }" @click="remnantFilter = f.key">
                {{ f.label }}
                <span class="sf-count sf-count--neutral">{{ remnantCount(f.key) }}</span>
              </button>
            </div>
          </div>
        </section>

        <section class="table-card">
          <table class="data-table rem-table">
            <colgroup>
              <col style="width:27%" />
              <col style="width:15%" />
              <col style="width:13%" />
              <col style="width:10%" />
              <col style="width:13%" />
              <col style="width:9%" />
              <col />
            </colgroup>
            <thead>
              <tr>
                <th>Product</th>
                <th>Leftover piece</th>
                <th>Total length</th>
                <th>Sale</th>
                <th>Date</th>
                <th>Status</th>
                <th class="th-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in visibleRemnants" :key="r.id" class="rem-row">
                <td data-label="Product">
                  <div class="rem-name">{{ r.ProductName }}</div>
                  <div class="rem-meta">
                    {{ r.SKU }}
                    <span v-if="r.ProductUnit" class="rem-unit-pill">{{ remUnitLabel(r.ProductUnit) }}</span>
                  </div>
                  <div v-if="r.VariantLabel" class="rem-size">Size: {{ r.VariantLabel }}</div>
                </td>
                <td data-label="Leftover piece" class="rem-strong">{{ r.qty }} × {{ lenText(r.length) }} {{ r.unit }}</td>
                <td data-label="Total length" class="rem-muted">{{ lenText(r.qty * r.length) }} {{ r.unit }}</td>
                <td data-label="Sale" class="rem-muted">{{ r.sale_id ? receiptNo(r.sale_id) : '—' }}</td>
                <td data-label="Date" :title="r.created_at">
                  <div class="rem-date-day">{{ dateOnly(r.created_at) }}</div>
                  <div class="rem-date-time">{{ timeOnly(r.created_at) }}</div>
                </td>
                <td data-label="Status">
                  <span class="rem-status" :class="'rem-status--' + r.status">{{ r.status }}</span>
                </td>
                <td data-label="Action" class="td-rem-action">
                  <div class="rem-actions">
                    <template v-if="r.status === 'available'">
                      <button type="button" class="rem-btn" :disabled="remnantBusyId === r.id" @click="setRemnantStatus(r, 'used')">Mark used</button>
                      <button type="button" class="rem-btn rem-btn--danger" :disabled="remnantBusyId === r.id" @click="setRemnantStatus(r, 'scrapped')">Scrap</button>
                    </template>
                    <button v-else type="button" class="rem-btn" :disabled="remnantBusyId === r.id" @click="setRemnantStatus(r, 'available')">Put back</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!visibleRemnants.length">
                <td colspan="7" class="rem-empty">
                  <i class="fa-solid fa-ruler-horizontal"></i>
                  <span>{{ remnantEmptyText }}</span>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="remnantError" class="rem-error" role="alert">{{ remnantError }}</p>
          <div class="table-footer">
            What is left of a piece after a cut, e.g. 18 in piece − 10 in sold = 8 in leftover. Leftovers are not counted as whole pieces in stock.
          </div>
        </section>
      </template>

    </div>

    <!-- ═════════════ ROW DROPDOWN ═════════════ -->
    <Teleport to="body">
      <div
        v-if="openMenuId && currentProduct"
        ref="dropdownRef"
        :style="dropdownStyle"
        class="prod-dropdown"
        role="menu"
      >
        <RouterLink :to="`/products/${currentProduct.ProductID}`" class="prod-dropdown-item" @click="closeMenu">
          <span class="prod-dropdown-icon prod-dropdown-icon--blue"><i class="fa-solid fa-eye"></i></span>
          View details
        </RouterLink>
        <RouterLink :to="`/products/${currentProduct.ProductID}/edit`" class="prod-dropdown-item" @click="closeMenu">
          <span class="prod-dropdown-icon prod-dropdown-icon--amber"><i class="fa-solid fa-pen-to-square"></i></span>
          Edit product
        </RouterLink>
        <div class="prod-dropdown-divider"></div>
        <button class="prod-dropdown-item prod-dropdown-item--danger" @click="promptDelete(currentProduct)">
          <span class="prod-dropdown-icon prod-dropdown-icon--red"><i class="fa-solid fa-trash"></i></span>
          Delete
        </button>
      </div>
    </Teleport>

    <!-- ═════════════ RESTOCK MODAL (one size at a time) ═════════════ -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="modalVisible" class="modal-backdrop" @click.self="closeRestockModal">
          <div class="restock-modal" role="dialog" aria-modal="true" aria-label="Restock product">
            <div class="restock-modal-header">
              <div class="restock-modal-icon">
                <i class="fa-solid fa-boxes-stacked"></i>
              </div>
              <div>
                <p class="restock-modal-eyebrow">Inventory</p>
                <h3 class="restock-modal-title">Restock product</h3>
              </div>
              <button class="modal-close-btn" @click="closeRestockModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="restock-modal-body">
              <div class="restock-product-chip">
                <div class="restock-chip-avatar">
                  <img v-if="modalProduct?.Product_Image" :src="imagePath(modalProduct.Product_Image)" class="restock-chip-img" />
                  <span v-else>{{ modalProduct?.ProductName?.substring(0,2).toUpperCase() }}</span>
                </div>
                <div>
                  <p class="restock-chip-name">{{ modalProduct?.ProductName }}</p>
                  <p class="restock-chip-stock">
                    <template v-if="modalVariants.length > 1">Total stock (all sizes): </template>
                    <template v-else>Current stock: </template>
                    <strong>{{ modalProduct ? totalStock(modalProduct) : 0 }}</strong>
                  </p>
                </div>
              </div>

              <form class="restock-form" @submit.prevent="submitRestock">
                <!-- Size picker: the ONLY scrolling area of the modal -->
                <div v-if="modalVariants.length > 1" class="restock-field restock-field--sizes">
                  <div class="restock-label-row">
                    <label class="restock-label">Size to restock <span class="req">*</span></label>
                    <span class="size-pick-legend" aria-hidden="true">
                      <span class="legend-item legend-item--ok">In stock</span>
                      <span class="legend-item legend-item--low">Low</span>
                      <span class="legend-item legend-item--oos">Out</span>
                    </span>
                  </div>
                  <div class="size-pick" role="listbox" aria-label="Sizes">
                    <button
                      v-for="v in modalVariants"
                      :key="v.VariantID"
                      type="button"
                      role="option"
                      :aria-selected="restockVariantId === v.VariantID"
                      class="size-pick-btn"
                      :class="[variantClass(v), { 'size-pick-btn--active': restockVariantId === v.VariantID }]"
                      @click="restockVariantId = v.VariantID"
                    >
                      <i v-if="restockVariantId === v.VariantID" class="fa-solid fa-circle-check size-pick-check" aria-hidden="true"></i>
                      <span class="size-pick-label">{{ v.Label }}</span>
                      <span class="size-pick-qty">{{ variantStock(v) }} in stock</span>
                    </button>
                  </div>
                </div>

                <div class="restock-field restock-field--qty">
                  <label class="restock-label">Quantity to add <span class="req">*</span></label>
                  <div class="qty-row">
                    <button type="button" class="qty-btn" @click="restockQty = Math.max(1, (restockQty || 1) - 1)" aria-label="Decrease"><i class="fa-solid fa-minus"></i></button>
                    <input v-model.number="restockQty" type="number" min="1" required class="qty-input" />
                    <button type="button" class="qty-btn" @click="restockQty = (restockQty || 0) + 1" aria-label="Increase"><i class="fa-solid fa-plus"></i></button>
                  </div>
                  <div class="qty-quick">
                    <button v-for="n in [5, 10, 25, 50]" :key="n" type="button" class="qty-chip" @click="restockQty = (restockQty || 0) + n">+{{ n }}</button>
                  </div>
                  <p class="restock-hint">
                    <template v-if="modalVariant">Stock of <strong>{{ modalVariant.Label }}</strong> after restock:</template>
                    <template v-else>New stock after restock:</template>
                    <strong>{{ modalNewStock }}</strong>
                    <span v-if="modalStillLow" class="restock-warn">
                      <i class="fa-solid fa-triangle-exclamation"></i> still at or below reorder level ({{ modalThreshold }})
                    </span>
                  </p>
                </div>

                <div class="restock-footer">
                  <button type="button" class="btn btn--ghost" @click="closeRestockModal">Cancel</button>
                  <button type="submit" class="btn btn--green" :disabled="isRestocking">
                    <i v-if="isRestocking" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-boxes-stacked"></i>
                    {{ isRestocking ? 'Restocking…' : 'Confirm restock' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- ═════════════ DELETE MODAL ═════════════ -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="showDeleteModal" class="modal-backdrop" @click.self="cancelDelete">
          <div class="del-modal" role="alertdialog" aria-modal="true" aria-label="Delete product">
            <div class="del-modal-icon-ring">
              <div class="del-modal-icon"><i class="fa-solid fa-trash-can"></i></div>
            </div>
            <div class="del-modal-body">
              <h3 class="del-modal-title">Delete product</h3>
              <p class="del-modal-desc">
                You are about to permanently delete
                <strong class="del-modal-name">{{ productToDelete?.ProductName }}</strong>.
                This cannot be undone.
              </p>
              <div class="del-modal-chip">
                <div class="del-modal-chip-avatar">
                  {{ productToDelete?.ProductName?.substring(0,2).toUpperCase() }}
                </div>
                <div class="del-modal-chip-info">
                  <p class="del-modal-chip-name">{{ productToDelete?.ProductName }}</p>
                  <p class="del-modal-chip-meta">SKU: {{ productToDelete?.SKU }}</p>
                </div>
              </div>
            </div>
            <div class="del-modal-footer">
              <button class="del-modal-btn del-modal-btn--cancel" @click="cancelDelete" :disabled="deleting">Cancel</button>
              <button class="del-modal-btn del-modal-btn--confirm" @click="confirmDelete" :disabled="deleting">
                <i v-if="deleting" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-trash-can"></i>
                {{ deleting ? 'Deleting…' : 'Yes, delete' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- ═════════════ BULK DELETE MODAL ═════════════ -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="showBulkDeleteModal" class="modal-backdrop" @click.self="cancelBulkDelete">
          <div class="del-modal" role="alertdialog" aria-modal="true" aria-label="Delete products">
            <div class="del-modal-icon-ring">
              <div class="del-modal-icon"><i class="fa-solid fa-trash-can"></i></div>
            </div>
            <div class="del-modal-body">
              <h3 class="del-modal-title">Delete {{ selectedCount }} product{{ selectedCount > 1 ? 's' : '' }}</h3>
              <p class="del-modal-desc">
                You are about to permanently delete
                <strong class="del-modal-name">{{ selectedCount }} selected product{{ selectedCount > 1 ? 's' : '' }}</strong>.
                This cannot be undone.
              </p>
            </div>
            <div class="del-modal-footer">
              <button class="del-modal-btn del-modal-btn--cancel" @click="cancelBulkDelete" :disabled="bulkDeleting">Cancel</button>
              <button class="del-modal-btn del-modal-btn--confirm" @click="confirmBulkDelete" :disabled="bulkDeleting">
                <i v-if="bulkDeleting" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-trash-can"></i>
                {{ bulkDeleting ? 'Deleting…' : 'Yes, delete all' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </Layout>
</template>

<script setup>
/* ════════════════════════════════════════════════════════════════
   PRODUCT LIST — sections
     1. Imports & constants
     2. State
     3. Small helpers (formatting, toasts, preferences)
     4. Sizes, prices & stock rules
     5. Size sorting & the per-size panel
     6. Cut tracking (leftover pieces)
     7. Data loading
     8. Filtering, sorting & summary numbers
     9. Bulk selection
    10. Row dropdown
    11. Restock (one size at a time)
    12. Delete (single)
    13. Lifecycle & watchers
════════════════════════════════════════════════════════════════ */

/* ── 1. IMPORTS & CONSTANTS ───────────────────────────────────── */
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'
import { ref, onMounted, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route  = useRoute()
const router = useRouter()

const LOW_STOCK_DEFAULT = 5   // same fallback the server uses (StockService)

/* ── 2. STATE ─────────────────────────────────────────────────── */
// data
const products   = ref([])
const categories = ref([])
const remnants   = ref([])

// which screen is showing: 'products' | 'cuts'  (kept in the URL as ?view=cuts)
const view = ref('products')

// page status
const loading        = ref(true)
const loadFailed     = ref(false)
const successMessage = ref('')
const errorMessage   = ref('')
let messageTimer     = null

// search & filters. `searchInput` is what the box shows; `searchQuery` is the
// lowercase term used for filtering. The header search (?search=) feeds the same box.
const searchInput      = ref('')
const searchQuery      = computed(() => searchInput.value.trim().toLowerCase())
const selectedCategory = ref('')
const stockFilter      = ref('all')   // 'all' | 'active' | 'low' | 'out'
const STOCK_WORD       = { low: 'low', out: 'out of stock', active: 'active' }

// remembered sort preferences
const pref = (key, fallback) => {
  try { return localStorage.getItem(`prodlist_${key}`) || fallback } catch { return fallback }
}
const savePref = (key, val) => { try { localStorage.setItem(`prodlist_${key}`, val) } catch { /* ignore */ } }

const productSort = ref(pref('productSort', 'status'))  // 'status' | 'attention' | 'name' | 'stock-desc' | 'stock-asc'
const variantSort = ref(pref('variantSort', 'active'))  // 'active' | 'size' | 'low'

// notification highlight (?highlightId= or ?highlight=) — flashes a row for a few seconds
const highlightedProductId = ref(null)
let highlightTimer = null

// which products have their size panel open (closed by default)
const expandedIds = ref(new Set())

// bulk selection
const selectedIds         = ref(new Set())
const showBulkDeleteModal = ref(false)
const bulkDeleting        = ref(false)

// row dropdown
const openMenuId     = ref(null)
const dropdownRef    = ref(null)
const dropdownStyle  = ref({ position: 'fixed', top: '0px', left: '0px' })
const menuButtonRefs = new Map()

// restock modal
const modalVisible     = ref(false)
const modalProduct     = ref(null)
const restockQty       = ref(1)
const restockVariantId = ref(null)
const isRestocking     = ref(false)

// single delete modal
const showDeleteModal = ref(false)
const productToDelete = ref(null)
const deleting        = ref(false)

// cut tracking
const remnantFilter = ref('available')
const remnantSearch = ref('')
const remnantBusyId = ref(null)
const remnantError  = ref('')

/* ── 3. SMALL HELPERS ─────────────────────────────────────────── */
const setMenuButtonRef = id => el => { if (el) menuButtonRefs.set(id, el); else menuButtonRefs.delete(id) }
const imagePath   = path => `http://127.0.0.1:8000/storage/${path}`
const formatPrice = v => Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const numOf       = v => { const n = Number(v); return Number.isFinite(n) ? n : 0 }
const lenText     = n => numOf(n).toLocaleString('en-US', { maximumFractionDigits: 3 })
const receiptNo   = id => `SR-${String(id).padStart(5, '0')}`

function flashSuccess(msg) {
  errorMessage.value = ''
  successMessage.value = msg
  clearTimeout(messageTimer)
  messageTimer = setTimeout(() => { successMessage.value = '' }, 3000)
}
function flashError(msg, { sticky = false } = {}) {
  successMessage.value = ''
  errorMessage.value = msg
  clearTimeout(messageTimer)
  if (!sticky) messageTimer = setTimeout(() => { errorMessage.value = '' }, 5000)
}

// The backend sends naive local datetime strings — parse the parts directly.
function parseLocal(raw) {
  const match = String(raw ?? '').match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/)
  if (!match) return null
  const [, year, month, day, hour, minute, second] = match.map(Number)
  return new Date(year, month - 1, day, hour, minute, second)
}
function dateOnly(raw) {
  if (!raw) return ''
  const d = parseLocal(raw)
  return d ? d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : String(raw)
}
function timeOnly(raw) {
  const d = parseLocal(raw)
  return d ? d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : ''
}

/* ── VIEW SWITCH (Products / Cut tracking) ── */
function setView(next) {
  view.value = next
  closeMenu()
  router.replace({ query: { ...route.query, view: next === 'cuts' ? 'cuts' : undefined } })
}
function openCuts() {
  remnantFilter.value = 'available'
  setView('cuts')
}

/* ── 4. SIZES, PRICES & STOCK ─────────────────────────────────────
   /api/product-list returns, per product:
     variants: [{ VariantID, Label, SellingPrice, Stock, ReorderLevel }]
   Stock and ReorderLevel are PER SIZE. The product total is the sum of its sizes.

   THREE SEPARATE STATES (rank):
     0 = healthy      → Stock is above the reorder level
     1 = LOW          → Stock is above 0 but at/below its ReorderLevel (or 5 when none is set)
     2 = OUT OF STOCK → Stock is 0
   Same rule as the server (StockService::notifyIfLow and HomeController). */
const variantsOf = p => p?.variants ?? []
const sizeCount  = p => variantsOf(p).length

function priceLabel(p) {
  const prices = variantsOf(p).map(v => Number(v.SellingPrice))
  if (!prices.length) return `₱${formatPrice(p.SellingPrice)}`
  const min = Math.min(...prices)
  const max = Math.max(...prices)
  return min === max ? `₱${formatPrice(min)}` : `₱${formatPrice(min)} – ₱${formatPrice(max)}`
}
const sizesTooltip = p =>
  variantsOf(p).map(v => `${v.Label} — ₱${formatPrice(v.SellingPrice)}`).join('\n')

const variantStock     = v => Number(v?.Stock ?? 0)
const variantThreshold = v => (Number(v?.ReorderLevel) > 0 ? Number(v.ReorderLevel) : LOW_STOCK_DEFAULT)

// 0 = healthy · 1 = low (above 0, at/below reorder level) · 2 = out of stock (0)
const variantRank = v => {
  const qty = variantStock(v)
  if (qty <= 0) return 2
  return qty <= variantThreshold(v) ? 1 : 0
}
const RANK_CLASS   = ['tone--ok', 'tone--low', 'tone--oos']
const variantClass = v => RANK_CLASS[variantRank(v)]

// How full the little bar on a size tile is: 3x the reorder level counts as "full".
const variantFill = v => Math.max(4, Math.min(100, Math.round((variantStock(v) / (variantThreshold(v) * 3)) * 100)))

const variantTooltip = v =>
  `${v.Label}: ${variantStock(v)} in stock · reorder level ${Number(v.ReorderLevel) || 0} · ₱${formatPrice(v.SellingPrice)}`

const hasVariantStock = p =>
  variantsOf(p).length > 0 && variantsOf(p).some(v => v.Stock !== undefined && v.Stock !== null)

const totalStock = p =>
  hasVariantStock(p)
    ? variantsOf(p).reduce((s, v) => s + variantStock(v), 0)
    : (p.inventory?.QuantityOnHand ?? 0)

// Product rank: 0 = every size healthy · 1 = at least one size low or out · 2 = nothing left at all
const productRank = p => {
  const total = totalStock(p)
  if (total <= 0) return 2
  if (hasVariantStock(p)) return variantsOf(p).some(v => variantRank(v) > 0) ? 1 : 0
  return total <= (p.inventory?.ReorderLevel ?? 0) ? 1 : 0
}

const stockClass = p => RANK_CLASS[productRank(p)]
const stockIcon  = p => ['fa-circle-check', 'fa-triangle-exclamation', 'fa-circle-xmark'][productRank(p)]

// Counts of healthy / low / out sizes for the health bar.
const sizeBreakdown = p => {
  const out = { ok: 0, low: 0, out: 0 }
  variantsOf(p).forEach(v => {
    const r = variantRank(v)
    if (r === 0) out.ok++
    else if (r === 1) out.low++
    else out.out++
  })
  return out
}

const stockLabel = p => {
  const rank  = productRank(p)
  const multi = sizeCount(p) > 1
  if (rank === 0) return multi ? 'All sizes OK' : 'In stock'
  if (rank === 2) return 'Out of stock'
  if (!hasVariantStock(p) || !multi) return 'Low stock'
  // mixed product: say exactly how many sizes are out and how many are low
  const b = sizeBreakdown(p)
  const parts = []
  if (b.out) parts.push(`${b.out} out`)
  if (b.low) parts.push(`${b.low} low`)
  return parts.join(' · ')
}

/* Every SIZE counts as its own item (a product with 12 sizes = 12 items).
   A product that has no sizes at all counts as ONE item, using its own stock.
   Returns the rank (0 ok · 1 low · 2 out) of each item of the product. */
const unitRanks = p =>
  hasVariantStock(p) ? variantsOf(p).map(variantRank) : [productRank(p)]

// Anything that needs attention (low OR out). Used for row colouring / sorting.
const isLowStock = p => productRank(p) > 0

// LOW tab → product has at least one size that is low (above 0, at/below reorder level)
// OUT tab → product has at least one size at 0
const hasLowSize = p => unitRanks(p).includes(1)
const hasOutSize = p => unitRanks(p).includes(2)

// A product is "active" when at least one size is healthy, so a product with
// 3 healthy sizes and 1 low size shows under BOTH "Active" and "Low stock".
const hasActiveStock = p =>
  hasVariantStock(p) ? variantsOf(p).some(v => variantRank(v) === 0) : productRank(p) === 0

/* ── 5. SIZE SORTING & THE PER-SIZE PANEL ─────────────────────────
   Reads numbers out of labels ("8 ft", "1/2 in", "2 1/2 in") and knows named
   sizes (XS … XXXL), so "8" sorts before "10". */
const NAMED_SIZES = { xxs: 0, xs: 1, s: 2, small: 2, m: 3, medium: 3, l: 4, large: 4, xl: 5, xxl: 6, xxxl: 7 }

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
  const ka = sizeKey(a.Label), kb = sizeKey(b.Label)
  if (ka !== kb) return ka < kb ? -1 : 1
  return String(a.Label ?? '').localeCompare(String(b.Label ?? ''), undefined, { numeric: true, sensitivity: 'base' })
}

function compareVariants(a, b) {
  if (variantSort.value === 'size') return compareSize(a, b)
  const ra = variantRank(a), rb = variantRank(b)
  if (ra !== rb) return variantSort.value === 'low' ? rb - ra : ra - rb   // 'low' puts the worst first
  return compareSize(a, b)
}

const sortedVariants = p => [...variantsOf(p)].sort(compareVariants)

// While a stock tab is on, only the matching sizes are listed in the panel.
const lowOnly    = p => stockFilter.value === 'low'    && hasVariantStock(p)
const outOnly    = p => stockFilter.value === 'out'    && hasVariantStock(p)
const activeOnly = p => stockFilter.value === 'active' && hasVariantStock(p)
const narrowed   = p => lowOnly(p) || outOnly(p) || activeOnly(p)

const listedVariants = p => {
  const all = sortedVariants(p)
  if (lowOnly(p))    return all.filter(v => variantRank(v) === 1)
  if (outOnly(p))    return all.filter(v => variantRank(v) === 2)
  if (activeOnly(p)) return all.filter(v => variantRank(v) === 0)
  return all
}
const listedCount = p => listedVariants(p).length

// Size panel open/closed. Only products with more than one size have a panel.
const isExpanded = p => sizeCount(p) > 1 && expandedIds.value.has(p.ProductID)

function toggleExpand(id) {
  const next = new Set(expandedIds.value)
  next.has(id) ? next.delete(id) : next.add(id)
  expandedIds.value = next
}

// "Show all sizes" / "Hide all sizes" acts on the products currently on screen.
const expandableProducts = computed(() => filteredProducts.value.filter(p => sizeCount(p) > 1))
const expandableCount    = computed(() => expandableProducts.value.length)
const allSizesOpen       = computed(() =>
  expandableCount.value > 0 && expandableProducts.value.every(p => expandedIds.value.has(p.ProductID))
)
function toggleAllSizes() {
  const next = new Set(expandedIds.value)
  if (allSizesOpen.value) expandableProducts.value.forEach(p => next.delete(p.ProductID))
  else                    expandableProducts.value.forEach(p => next.add(p.ProductID))
  expandedIds.value = next
}

/* ── 6. CUT TRACKING (leftover pieces) ────────────────────────────
   GET   /api/inventory/remnants        → every leftover (available / used / scrapped)
   PATCH /api/inventory/remnants/{id}   → change status (also writes the history
                                           row shown on the Inventory Logs page) */
const REMNANT_FILTERS = [
  { key: 'available', label: 'Available' },
  { key: 'used',      label: 'Used' },
  { key: 'scrapped',  label: 'Scrapped' },
  { key: 'all',       label: 'All' },
]
const REM_UNIT_LABELS = { pc: 'pcs', pcs: 'pcs', piece: 'pcs', pieces: 'pcs', feet: 'ft', meter: 'm', meters: 'm' }
function remUnitLabel(raw) {
  const key = String(raw ?? '').trim().toLowerCase()
  return key ? (REM_UNIT_LABELS[key] ?? String(raw).trim()) : ''
}

const availableRemnants = computed(() => remnants.value.filter(r => r.status === 'available'))
const availableCount    = computed(() => availableRemnants.value.reduce((s, r) => s + r.qty, 0))

// Tab counts ignore the search box so they never shrink to themselves.
const remnantCount = key =>
  key === 'all' ? remnants.value.length : remnants.value.filter(r => r.status === key).length

const visibleRemnants = computed(() => {
  const q = remnantSearch.value.trim().toLowerCase()
  return remnants.value.filter(r => {
    if (remnantFilter.value !== 'all' && r.status !== remnantFilter.value) return false
    if (!q) return true
    return [r.ProductName, r.SKU, r.VariantLabel, r.sale_id ? receiptNo(r.sale_id) : '']
      .join(' ').toLowerCase().includes(q)
  })
})

const remnantEmptyText = computed(() => {
  if (remnantSearch.value.trim()) return 'No leftover pieces match your search.'
  return remnantFilter.value === 'available' ? 'No leftover pieces on hand.' : 'No leftover pieces with this status.'
})

// Total leftover length on hand, per unit of length (ft / m / in).
const remnantTotalsByUnit = computed(() => {
  const map = new Map()
  availableRemnants.value.forEach(r => {
    const unit = r.unit || ''
    map.set(unit, (map.get(unit) || 0) + r.qty * r.length)
  })
  return [...map.entries()].map(([unit, total]) => ({ unit, total }))
})

async function loadRemnants() {
  try {
    const { data } = await api.get('/api/inventory/remnants')
    const list = Array.isArray(data) ? data : (data?.remnants ?? [])
    remnants.value = list.map(r => ({
      ...r,
      id:      r.RemnantID,
      status:  r.Status,
      qty:     Math.max(numOf(r.Quantity), 1),   // older rows may store 0 = one piece
      length:  numOf(r.Length),
      unit:    r.Unit || '',
      sale_id: r.SaleID,
    }))
  } catch (err) {
    console.error('Load leftover pieces failed:', err)
    remnants.value = []
  }
}

async function setRemnantStatus(r, status) {
  if (remnantBusyId.value) return
  remnantBusyId.value = r.id
  remnantError.value = ''
  let warning = ''
  try {
    const { data } = await api.patch(`/api/inventory/remnants/${r.id}`, { status })
    r.status = status
    if (data && data.logged === false) {
      warning = 'The leftover was updated, but its history entry could not be saved. Ask the developer to check the inventory_logs "type" column.'
      if (data.log_error) console.warn('Leftover history row failed:', data.log_error)
    }
    await loadRemnants()
  } catch (err) {
    console.error('Update leftover piece failed:', err)
    warning = err?.response?.data?.message || 'Could not update this leftover piece. Please try again.'
  } finally {
    remnantError.value = warning
    remnantBusyId.value = null
  }
}

/* ── 7. DATA LOADING ──────────────────────────────────────────── */
async function loadProducts() {
  try {
    const r = await api.get('/api/product-list')
    products.value = r.data
    loadFailed.value = false
  } catch (err) {
    loadFailed.value = true
    flashError(err?.response?.data?.message || 'Could not load products.', { sticky: true })
  }
}
async function loadCategories() {
  try { const r = await api.get('/api/categories'); categories.value = r.data.categories } catch { /* non-critical */ }
}
async function reload() {
  errorMessage.value = ''
  loading.value = true
  await Promise.all([loadProducts(), loadRemnants()])
  loading.value = false
}

/* Called once products are loaded when the page was opened from a notification.
   product_created → ?highlightId=<ProductID>;
   low_stock / out_of_stock → ?highlight=<ProductName> (also accepts "Product (Size)").
   Silently does nothing when nothing matches. */
function applyHighlightFromQuery() {
  const idParam   = route.query.highlightId
  const nameParam = route.query.highlight

  let match = null
  if (idParam) match = products.value.find(p => String(p.ProductID) === String(idParam))
  if (!match && nameParam) {
    const wanted = String(nameParam).toLowerCase()
    match = products.value.find(p => {
      const pn = p.ProductName?.toLowerCase()
      return pn && (pn === wanted || wanted.startsWith(pn + ' ('))
    })
  }
  if (!match) return

  highlightedProductId.value = match.ProductID
  if (sizeCount(match) > 1) {
    expandedIds.value = new Set([...expandedIds.value, match.ProductID])
  }

  nextTick(() => {
    const el = document.querySelector(`[data-product-row="${match.ProductID}"]`)
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  })

  if (highlightTimer) clearTimeout(highlightTimer)
  highlightTimer = setTimeout(() => { highlightedProductId.value = null }, 3000)
}

/* ── 8. FILTERING, SORTING & SUMMARY NUMBERS ──────────────────────
   Product order — 'status': healthy → low → out · 'attention': out → low → healthy ·
   others: name / total stock. Ties always fall back to name. */
const byName = (a, b) =>
  String(a.ProductName ?? '').localeCompare(String(b.ProductName ?? ''), undefined, { numeric: true, sensitivity: 'base' })

function compareProducts(a, b) {
  switch (productSort.value) {
    case 'attention':  return (productRank(b) - productRank(a)) || byName(a, b)
    case 'name':       return byName(a, b)
    case 'stock-desc': return (totalStock(b) - totalStock(a)) || byName(a, b)
    case 'stock-asc':  return (totalStock(a) - totalStock(b)) || byName(a, b)
    case 'status':
    default:           return (productRank(a) - productRank(b)) || byName(a, b)
  }
}

// Search also matches size labels (e.g. "8 ft") so a product can be found by its size.
const filteredProducts = computed(() =>
  products.value
    .filter(p => {
      const matchSearch = !searchQuery.value || [
        p.ProductName,
        p.SKU,
        p.category?.CategoryName,
        ...variantsOf(p).map(v => v.Label),
      ].join(' ').toLowerCase().includes(searchQuery.value)
      const matchCategory = !selectedCategory.value || String(p.CategoryID) === selectedCategory.value
      const matchStock =
        stockFilter.value === 'all' ||
        (stockFilter.value === 'low'    && hasLowSize(p)) ||
        (stockFilter.value === 'out'    && hasOutSize(p)) ||
        (stockFilter.value === 'active' && hasActiveStock(p))
      return matchSearch && matchCategory && matchStock
    })
    .sort(compareProducts)
)
const currentProduct = computed(() => filteredProducts.value.find(p => p.ProductID === openMenuId.value))

/* Summary numbers count SIZES, not products: every size is its own item.
   Counts come off the full list so tab badges never shrink to themselves.
     lowStockCount   = sizes above 0 but at/below reorder level
     outOfStockCount = sizes at 0
     activeCount     = healthy sizes
     totalSizes      = every size  (= active + low + out) */
const allRanks        = computed(() => products.value.flatMap(unitRanks))
const lowStockCount   = computed(() => allRanks.value.filter(r => r === 1).length)
const outOfStockCount = computed(() => allRanks.value.filter(r => r === 2).length)
const activeCount     = computed(() => allRanks.value.filter(r => r === 0).length)
const totalSizes      = computed(() => allRanks.value.length)

// Sizes inside the rows currently on screen that match the active stock tab (used in the table footer).
const visibleSizeCount = computed(() =>
  filteredProducts.value.reduce((sum, p) => {
    const ranks = unitRanks(p)
    if (stockFilter.value === 'low')    return sum + ranks.filter(r => r === 1).length
    if (stockFilter.value === 'out')    return sum + ranks.filter(r => r === 2).length
    if (stockFilter.value === 'active') return sum + ranks.filter(r => r === 0).length
    return sum + ranks.length
  }, 0)
)

const hasActiveFilters = computed(() =>
  !!searchInput.value || !!selectedCategory.value || stockFilter.value !== 'all'
)
function resetFilters() {
  searchInput.value = ''
  selectedCategory.value = ''
  stockFilter.value = 'all'
  if (route.query.search) router.replace({ query: { ...route.query, search: undefined } })
}

const noExtraFilters = () => !searchInput.value && !selectedCategory.value

const emptyIcon = computed(() =>
  products.value.length === 0 ? 'fa-solid fa-box-open'
  : (stockFilter.value === 'low' || stockFilter.value === 'out') && noExtraFilters() ? 'fa-solid fa-circle-check'
  : 'fa-solid fa-magnifying-glass')
const emptyTitle = computed(() =>
  products.value.length === 0 ? 'No products yet'
  : stockFilter.value === 'low' && noExtraFilters() ? 'Nothing is running low'
  : stockFilter.value === 'out' && noExtraFilters() ? 'Nothing is out of stock'
  : 'No products match your filters')
const emptyHint = computed(() =>
  products.value.length === 0 ? 'Add a product to start tracking its stock.'
  : stockFilter.value === 'low' && noExtraFilters() ? 'No size is at or below its reorder level.'
  : stockFilter.value === 'out' && noExtraFilters() ? 'Every size has at least 1 in stock.'
  : 'Try a different search or clear the filters.')

/* ── 9. BULK SELECTION ────────────────────────────────────────── */
const selectedCount = computed(() => selectedIds.value.size)

const allVisibleSelected = computed(() =>
  filteredProducts.value.length > 0 &&
  filteredProducts.value.every(p => selectedIds.value.has(p.ProductID))
)
const someVisibleSelected = computed(() =>
  !allVisibleSelected.value &&
  filteredProducts.value.some(p => selectedIds.value.has(p.ProductID))
)

function toggleSelect(id) {
  const next = new Set(selectedIds.value)
  next.has(id) ? next.delete(id) : next.add(id)
  selectedIds.value = next
}

// Only touches what is currently visible, so "select all" never grabs hidden rows.
function toggleSelectAll() {
  const next = new Set(selectedIds.value)
  if (allVisibleSelected.value) filteredProducts.value.forEach(p => next.delete(p.ProductID))
  else                          filteredProducts.value.forEach(p => next.add(p.ProductID))
  selectedIds.value = next
}

function clearSelection() { selectedIds.value = new Set() }
function cancelBulkDelete() { if (!bulkDeleting.value) showBulkDeleteModal.value = false }

async function confirmBulkDelete() {
  if (bulkDeleting.value || selectedIds.value.size === 0) return
  bulkDeleting.value = true
  const ids = [...selectedIds.value]
  try {
    const results = await Promise.allSettled(
      ids.map(id => api.delete(`/api/products/${id}`, { withCredentials: true }))
    )
    const failed  = results.filter(r => r.status === 'rejected').length
    const deleted = ids.length - failed

    if (failed) flashError(`${deleted} deleted, ${failed} failed. Please try the failed ones again.`)
    else flashSuccess(`${deleted} product${deleted > 1 ? 's' : ''} deleted.`)

    showBulkDeleteModal.value = false
    clearSelection()
    await Promise.all([loadProducts(), loadRemnants()])
  } finally {
    bulkDeleting.value = false
  }
}

/* ── 10. ROW DROPDOWN ─────────────────────────────────────────── */
function toggleMenu(id) {
  if (openMenuId.value === id) { closeMenu(); return }
  openMenuId.value = id
  nextTick(() => {
    const btn = menuButtonRefs.get(id); if (!btn) return
    const rect = btn.getBoundingClientRect()
    const menuH = 150
    const openUp = rect.bottom + menuH + 16 > window.innerHeight
    dropdownStyle.value = {
      position: 'fixed',
      top: openUp ? `${rect.top - menuH - 8}px` : `${rect.bottom + 8}px`,
      left: `${Math.max(8, rect.right - 176)}px`,
    }
  })
}
function closeMenu() { openMenuId.value = null }
function handleCategoryChange() { closeMenu() }

function onClickOutside(e) {
  if (!openMenuId.value) return
  const dd = dropdownRef.value, btn = menuButtonRefs.get(openMenuId.value)
  if (dd && !dd.contains(e.target) && btn && !btn.contains(e.target)) closeMenu()
}
function onKeydown(e) {
  if (e.key === 'Escape') { closeMenu(); closeRestockModal(); cancelDelete(); cancelBulkDelete() }
}

/* ── 11. RESTOCK (one size at a time) ─────────────────────────── */
const modalVariants = computed(() => sortedVariants(modalProduct.value))
const modalVariant  = computed(() =>
  modalVariants.value.find(v => v.VariantID === restockVariantId.value) ?? null
)
const modalCurrentStock = computed(() =>
  modalVariant.value ? variantStock(modalVariant.value) : (modalProduct.value ? totalStock(modalProduct.value) : 0)
)
const modalNewStock  = computed(() => modalCurrentStock.value + (Number(restockQty.value) || 0))
const modalThreshold = computed(() =>
  modalVariant.value ? variantThreshold(modalVariant.value) : (modalProduct.value?.inventory?.ReorderLevel ?? 0)
)
const modalStillLow = computed(() => modalNewStock.value <= modalThreshold.value)

// `preferredVariantId` comes from clicking a size tile; otherwise the size with the least stock is pre-selected.
function openRestockModal(product, preferredVariantId = null) {
  modalProduct.value = product
  restockQty.value = 1
  const vs = variantsOf(product)
  const preferred = preferredVariantId && vs.find(v => v.VariantID === preferredVariantId)
  restockVariantId.value = preferred
    ? preferred.VariantID
    : vs.length
      ? vs.reduce((a, b) => (variantStock(b) < variantStock(a) ? b : a)).VariantID
      : null
  modalVisible.value = true
  // the size list scrolls, so make sure the chosen size is visible when the modal opens
  nextTick(() => document.querySelector('.size-pick-btn--active')?.scrollIntoView({ block: 'nearest' }))
}
function closeRestockModal() {
  modalVisible.value = false
  modalProduct.value = null
  restockQty.value = 1
  restockVariantId.value = null
}

async function submitRestock() {
  if (!modalProduct.value || !restockQty.value || restockQty.value < 1 || isRestocking.value) return
  if (modalVariants.value.length && !restockVariantId.value) {
    flashError('Choose which size to restock.')
    return
  }
  isRestocking.value = true
  try {
    const payload = { quantity: Number(restockQty.value) }
    if (restockVariantId.value) payload.variant_id = restockVariantId.value

    await api.post(`/api/products/${modalProduct.value.ProductID}/restock`, payload, { withCredentials: true })

    const label = modalVariant.value && modalVariants.value.length > 1 ? ` (${modalVariant.value.Label})` : ''
    flashSuccess(`Added ${payload.quantity} to ${modalProduct.value.ProductName}${label}.`)
    closeRestockModal()
    await loadProducts()
  } catch (err) {
    flashError(err?.response?.data?.message || 'Could not restock this product.')
  } finally {
    isRestocking.value = false
  }
}

/* ── 12. DELETE (single) ──────────────────────────────────────── */
function promptDelete(product) { closeMenu(); productToDelete.value = product; showDeleteModal.value = true }
function cancelDelete() { if (deleting.value) return; showDeleteModal.value = false; productToDelete.value = null }

async function confirmDelete() {
  if (!productToDelete.value || deleting.value) return
  deleting.value = true
  try {
    await api.delete(`/api/products/${productToDelete.value.ProductID}`, { withCredentials: true })
    flashSuccess(`"${productToDelete.value.ProductName}" deleted.`)
    if (selectedIds.value.has(productToDelete.value.ProductID)) {
      const next = new Set(selectedIds.value); next.delete(productToDelete.value.ProductID); selectedIds.value = next
    }
    showDeleteModal.value = false
    productToDelete.value = null
    await Promise.all([loadProducts(), loadRemnants()])
  } catch (err) {
    flashError(err?.response?.data?.message || `Delete failed (${err?.response?.status})`)
  } finally {
    deleting.value = false
  }
}

/* ── 13. LIFECYCLE & WATCHERS ─────────────────────────────────── */
watch(productSort, v => savePref('productSort', v))
watch(variantSort, v => savePref('variantSort', v))
watch(() => route.query.search, v => { searchInput.value = v || '' }, { immediate: true })
watch(() => route.query.view, v => { view.value = v === 'cuts' ? 'cuts' : 'products' }, { immediate: true })
watch(selectedCategory, nc => { router.replace({ query: { ...route.query, category: nc || undefined } }) })
// Changing a filter clears the selection so you can never delete rows that are no longer on screen.
watch([stockFilter, selectedCategory, searchQuery], clearSelection)

onMounted(async () => {
  if (['low', 'out'].includes(route.query.stockFilter)) stockFilter.value = route.query.stockFilter
  if (route.query.category) selectedCategory.value = String(route.query.category)

  document.addEventListener('click', onClickOutside)
  document.addEventListener('keydown', onKeydown)

  loadCategories()
  loadRemnants()            // runs alongside the product load; it never blocks the table
  await loadProducts()
  loading.value = false

  applyHighlightFromQuery()
})

onBeforeUnmount(() => {
  clearTimeout(highlightTimer)
  clearTimeout(messageTimer)
  document.removeEventListener('click', onClickOutside)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   STYLE SECTIONS
     A. Tokens & page shell      F. Product table
     B. Header, tabs & KPIs      G. Size panel & tiles
     C. Toolbar                  H. Cut tracking
     D. Buttons & alerts         I. Modals
     E. Bulk bar                 J. Responsive (no horizontal scroll) / motion
════════════════════════════════════════════════════════════════ */

/* ── A. TOKENS & PAGE SHELL ── */
.prod-list-page {
  --pl-bg:             var(--c-bg);
  --pl-surface:        var(--c-surface);
  --pl-surface-raised: var(--c-surface-raised);
  --pl-border:         var(--c-border);
  --pl-border-strong:  var(--c-border-strong);
  --pl-text-primary:   var(--c-text-primary);
  --pl-text-secondary: var(--c-text-secondary);
  --pl-text-muted:     var(--c-text-muted);
  --pl-text-faint:     var(--c-text-faint);
  --pl-accent:         var(--c-accent);
  --pl-accent-soft:    var(--c-accent-soft);
  --pl-accent-border:  var(--c-accent-border);
  --pl-ring:           var(--c-accent-ring);
  --pl-green:          #10b981;
  --pl-red:            #f43f5e;
  --pl-blue:           #3b82f6;
  --pl-amber:          #f59e0b;
  --radius: 16px;

  min-height: 100%;
  max-width: 100%;
  overflow-x: hidden;           /* the page itself never scrolls sideways */
  background: var(--pl-bg);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 18px;
  transition: background-color .22s;
}
.prod-list-page > * { min-width: 0; }

/* ── B. HEADER, TABS & KPIs ── */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--pl-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--pl-text-primary); letter-spacing: -.03em; margin: 0; transition: color .22s; }
.page-sub     { margin: 6px 0 0; font-size: 13px; color: var(--pl-text-muted); }

/* Products / Cut tracking switch */
.view-tabs {
  display: flex; gap: 4px;
  border-bottom: 1px solid var(--pl-border);
}
.view-tab {
  position: relative; display: inline-flex; align-items: center; gap: 9px;
  padding: 11px 16px 13px; border: none; background: none; cursor: pointer;
  font-family: inherit; font-size: 13.5px; font-weight: 600; color: var(--pl-text-muted);
  border-radius: 10px 10px 0 0;
  transition: color .16s, background-color .16s;
}
.view-tab i { font-size: 13px; }
.view-tab:hover { color: var(--pl-text-primary); background: var(--pl-surface-raised); }
.view-tab:focus-visible { outline: 2px solid var(--pl-accent); outline-offset: -2px; }
.view-tab--on { color: var(--pl-accent); }
.view-tab--on::after {
  content: ''; position: absolute; left: 10px; right: 10px; bottom: -1px; height: 3px;
  border-radius: 3px 3px 0 0; background: var(--pl-accent);
}
.vt-count {
  min-width: 22px; height: 20px; padding: 0 7px; border-radius: 20px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700;
  background: var(--pl-border); color: var(--pl-text-secondary);
}
.view-tab--on .vt-count { background: var(--pl-accent-soft); color: var(--pl-accent); }
.vt-count--blue { background: rgba(59,130,246,.14); color: var(--pl-blue); }

.link-btn {
  background: none; border: none; padding: 0; cursor: pointer;
  font: inherit; font-weight: 600; color: var(--pl-accent);
}
.link-btn:hover { text-decoration: underline; }

/* Products view has 4 cards (Products, Low, Out, Leftovers); cut view has 3 */
.kpi-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.cut-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.kpi-card, .cut-stat {
  display: flex; align-items: center; gap: 14px; text-align: left;
  padding: 16px 18px; cursor: pointer; font-family: inherit; min-width: 0;
  background: var(--pl-surface); border: 1px solid var(--pl-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm);
  transition: transform .18s, box-shadow .18s, border-color .18s, background-color .22s;
}
.kpi-card:hover, .cut-stat:hover { transform: translateY(-2px); border-color: var(--pl-accent-border); box-shadow: var(--c-shadow-md); }
.kpi-card:focus-visible, .cut-stat:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }
.kpi-card--alert { background: rgba(244,63,94,.06); border-color: rgba(244,63,94,.28); }
.kpi-card--warn  { background: rgba(245,158,11,.07); border-color: rgba(245,158,11,.35); }
.cut-stat--on { border-color: var(--pl-accent); box-shadow: 0 0 0 3px var(--pl-ring); }
.kpi-icon {
  width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 16px;
}
.kpi-icon--accent { background: var(--pl-accent-soft); color: var(--pl-accent); }
.kpi-icon--green  { background: rgba(16,185,129,.14); color: var(--pl-green); }
.kpi-icon--red    { background: rgba(244,63,94,.14);  color: var(--pl-red); }
.kpi-icon--amber  { background: rgba(245,158,11,.16); color: var(--pl-amber); }
.kpi-icon--blue   { background: rgba(59,130,246,.14); color: var(--pl-blue); }
.kpi-body  { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.kpi-label { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--pl-text-muted); }
.kpi-value { font-size: 24px; font-weight: 800; letter-spacing: -.03em; line-height: 1.1; color: var(--pl-text-primary); }
.kpi-hint  { font-size: 12px; color: var(--pl-text-muted); overflow-wrap: anywhere; }

/* ── C. TOOLBAR ── */
.toolbar {
  display: flex; flex-direction: column; gap: 12px;
  background: var(--pl-surface); border: 1px solid var(--pl-border);
  border-radius: var(--radius); padding: 14px 16px;
  box-shadow: var(--c-shadow-sm);
}
.toolbar-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.toolbar-row--secondary { padding-top: 12px; border-top: 1px solid var(--pl-border); align-items: flex-end; }
.toolbar-end { display: flex; gap: 8px; margin-left: auto; flex-wrap: wrap; }
.tool-btn { height: 38px; }

.search-wrap { position: relative; flex: 1 1 260px; min-width: 0; display: flex; align-items: center; }
.search-icon { position: absolute; left: 13px; font-size: 12px; color: var(--pl-text-faint); pointer-events: none; }
.search-input {
  width: 100%; height: 40px; padding: 0 36px;
  border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface-raised); color: var(--pl-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s;
  -webkit-appearance: none; appearance: none;
}
.search-input::placeholder { color: var(--pl-text-faint); }
.search-input:focus { border-color: var(--pl-accent); box-shadow: 0 0 0 3px var(--pl-ring); }
.search-input::-webkit-search-cancel-button { display: none; }
.search-clear {
  position: absolute; right: 8px; width: 24px; height: 24px; border: none; border-radius: 6px;
  background: none; color: var(--pl-text-faint); cursor: pointer; font-size: 11px;
  display: flex; align-items: center; justify-content: center;
}
.search-clear:hover { background: var(--pl-border); color: var(--pl-text-primary); }

.stock-filter-toggle {
  display: flex; align-items: center; max-width: 100%;
  border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  overflow: hidden; height: 40px; flex-shrink: 0;
}
.sf-btn {
  display: inline-flex; align-items: center; gap: 6px;
  height: 100%; padding: 0 14px;
  border: none; background: var(--pl-surface); color: var(--pl-text-muted);
  font-size: 12.5px; font-weight: 600; font-family: inherit; cursor: pointer;
  transition: background-color .15s, color .15s; white-space: nowrap;
}
.sf-btn + .sf-btn { border-left: 1.5px solid var(--pl-border-strong); }
.sf-btn:hover { background: var(--pl-surface-raised); color: var(--pl-text-primary); }
.sf-btn:focus-visible { outline: none; box-shadow: inset 0 0 0 2px var(--pl-accent); }
.sf-btn--active { background: var(--pl-accent-soft); color: var(--pl-accent); }
.sf-btn--ok.sf-btn--active { background: rgba(16,185,129,.12); color: var(--pl-green); }
.sf-btn--warn.sf-btn--active { background: rgba(245,158,11,.14); color: #b45309; }
.sf-btn--danger.sf-btn--active { background: rgba(244,63,94,.12); color: var(--pl-red); }
html[data-theme="dark"] .sf-btn--ok.sf-btn--active { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .sf-btn--warn.sf-btn--active { background: rgba(245,158,11,.20); color: #FBBF24; }
html[data-theme="dark"] .sf-btn--danger.sf-btn--active { background: rgba(244,63,94,.18); color: #F87171; }
.sf-count {
  min-width: 20px; height: 18px; padding: 0 6px;
  border-radius: 20px; font-size: 10.5px; font-weight: 700;
  display: inline-flex; align-items: center; justify-content: center;
  background: var(--pl-red); color: #fff;
}
.sf-count--neutral { background: var(--pl-border); color: var(--pl-text-secondary); }
.sf-count--ok { background: rgba(16,185,129,.18); color: var(--pl-green); }
.sf-count--warn { background: rgba(245,158,11,.20); color: #b45309; }
html[data-theme="dark"] .sf-count--ok { color: #4ADE80; }
html[data-theme="dark"] .sf-count--warn { color: #FBBF24; }

.select-group { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.select-label { font-size: 11.5px; font-weight: 600; color: var(--pl-text-muted); padding-left: 2px; }
.select-wrap  { position: relative; display: flex; align-items: center; }
.input-icon   { position: absolute; left: 11px; font-size: 11px; color: var(--pl-text-faint); pointer-events: none; z-index: 1; }
.select-caret { position: absolute; right: 10px; font-size: 9px; color: var(--pl-text-faint); pointer-events: none; }
.form-select {
  height: 38px; padding: 0 28px 0 30px; width: 190px; max-width: 100%;
  border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface); color: var(--pl-text-secondary);
  font-size: 13px; font-weight: 600; font-family: inherit;
  outline: none; appearance: none; -webkit-appearance: none; cursor: pointer;
  text-overflow: ellipsis;
  transition: border-color .18s, background-color .22s, color .22s;
}
.form-select:focus { border-color: var(--pl-accent); box-shadow: 0 0 0 3px var(--pl-ring); }

/* ── D. BUTTONS & ALERTS ── */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }
.btn--primary {
  background: linear-gradient(135deg, var(--pl-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--ghost { background: var(--pl-surface); border-color: var(--pl-border-strong); color: var(--pl-text-secondary); }
.btn--ghost:hover { background: var(--pl-surface-raised); border-color: var(--pl-accent-border); color: var(--pl-text-primary); }
.btn--green {
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff; border: none; box-shadow: 0 2px 8px rgba(16,185,129,.30);
}
.btn--green:hover { filter: brightness(1.08); transform: translateY(-1px); }
.btn--green:disabled { opacity: .6; cursor: not-allowed; filter: none; transform: none; }
.btn--danger {
  background: linear-gradient(135deg, #f43f5e, #e11d48);
  color: #fff; border: none; box-shadow: 0 2px 8px rgba(244,63,94,.30);
}
.btn--danger:hover { filter: brightness(1.08); transform: translateY(-1px); }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }

.alert { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 12px; font-size: 13.5px; font-weight: 500; border: 1px solid transparent; }
.alert--success { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); color: var(--pl-green); }
.alert--error   { background: rgba(244,63,94,.10);  border-color: rgba(244,63,94,.25);  color: var(--pl-red); }
html[data-theme="dark"] .alert--success { background: rgba(16,185,129,.14); color: #4ADE80; }
html[data-theme="dark"] .alert--error   { background: rgba(244,63,94,.14);  color: #F87171; }
.alert-close { background: none; border: none; cursor: pointer; color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 2px; transition: opacity .15s; }
.alert-close:hover { opacity: 1; }
.alert .link-btn { color: inherit; text-decoration: underline; margin-left: auto; }
.alert .link-btn + .alert-close { margin-left: 4px; }
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── E. BULK BAR ── */
.bulk-bar {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  padding: 10px 16px; border-radius: 12px;
  background: var(--pl-accent-soft); border: 1px solid var(--pl-accent-border);
  color: var(--pl-text-primary); font-size: 13.5px;
}
.bulk-bar-left { display: flex; align-items: center; gap: 10px; }
.bulk-bar-left i { color: var(--pl-accent); font-size: 15px; }
.bulk-bar-actions { display: flex; align-items: center; gap: 8px; }

/* ── F. PRODUCT TABLE ──
   Fixed layout + wrapping text: every column always fits, no horizontal scroll. */
.table-card { background: var(--pl-surface); border: 1px solid var(--pl-border); border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden; transition: background-color .22s, border-color .22s; }
.table-footer {
  padding: 11px 18px; border-top: 1px solid var(--pl-border);
  font-size: 12px; color: var(--pl-text-muted); background: var(--pl-surface-raised);
}
.table-footer strong { color: var(--pl-text-primary); }

.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; table-layout: fixed; }
.data-table thead th {
  background: var(--pl-surface-raised); color: var(--pl-text-faint);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--pl-border);
  transition: background-color .22s, color .22s, border-color .22s;
}
.data-table thead th.th-right { text-align: right; }
.data-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--pl-border); vertical-align: top; overflow-wrap: anywhere; transition: background-color .15s, border-color .22s; }
.data-table tbody tr:hover td { background: var(--pl-surface-raised); }
.data-table tbody tr:last-child td { border-bottom: none; }
.data-table tbody td.td-check, .data-table tbody td.td-actions { padding-top: 16px; }

.data-table thead th.th-check,
.data-table tbody td.td-check { padding-right: 0; }
.chk {
  appearance: none; -webkit-appearance: none;
  width: 20px; height: 20px; margin: 0; cursor: pointer;
  border: 1.5px solid var(--pl-border-strong); border-radius: 6px;
  background: var(--pl-surface);
  display: inline-grid; place-content: center; vertical-align: middle;
  transition: background-color .15s, border-color .15s, box-shadow .15s;
}
.chk:hover:not(:disabled) { border-color: var(--pl-accent); }
.chk:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }
.chk:disabled { opacity: .5; cursor: not-allowed; }
.chk:checked, .chk:indeterminate { background-color: var(--pl-accent); border-color: var(--pl-accent); }
.chk:checked {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3.5 8.5l3 3 6-7'/%3E%3C/svg%3E");
  background-size: 14px; background-position: center; background-repeat: no-repeat;
}
.chk:indeterminate {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round'%3E%3Cpath d='M4 8h8'/%3E%3C/svg%3E");
  background-size: 14px; background-position: center; background-repeat: no-repeat;
}

/* row states: amber edge = some size is low, red edge = nothing left */
.data-table tbody tr.row-selected td { background: var(--pl-accent-soft); }
.data-table tbody tr.row-low td:first-child { box-shadow: inset 3px 0 0 var(--pl-amber); }
.data-table tbody tr.row-out td:first-child { box-shadow: inset 3px 0 0 var(--pl-red); }
.data-table tbody tr.row-open td { border-bottom-color: transparent; }

.row-highlight td {
  background: rgba(16,185,129,.12) !important;
  animation: row-pulse 1.6s ease-in-out 1;
}
@keyframes row-pulse {
  0%   { background: rgba(16,185,129,.12); }
  50%  { background: rgba(16,185,129,.24); }
  100% { background: rgba(16,185,129,.12); }
}
html[data-theme="dark"] .row-highlight td { background: rgba(16,185,129,.16) !important; }

.sk { display: block; border-radius: 6px; background: var(--pl-border); animation: sk-pulse 1.3s ease-in-out infinite; }
.sk--box    { width: 20px; height: 20px; }
.sk--avatar { width: 42px; height: 42px; border-radius: 10px; flex-shrink: 0; }
.sk--line   { height: 12px; width: 80%; }
.sk--short  { width: 50%; }
@keyframes sk-pulse { 0%,100% { opacity: .55; } 50% { opacity: 1; } }

.cell-product { display: flex; align-items: center; gap: 12px; min-width: 0; }
.product-avatar {
  width: 42px; height: 42px; border-radius: 10px; flex-shrink: 0;
  background: var(--pl-surface-raised); border: 1px solid var(--pl-border);
  display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.product-avatar-img      { width: 100%; height: 100%; object-fit: cover; }
.product-avatar-initials { font-size: 13px; font-weight: 800; color: var(--pl-accent); }
.product-info      { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.product-name-link { font-weight: 700; color: var(--pl-text-primary); text-decoration: none; font-size: 13.5px; line-height: 1.3; overflow-wrap: anywhere; }
.product-name-link:hover { color: var(--pl-accent); text-decoration: underline; }
.product-sku       { font-size: 11.5px; color: var(--pl-text-faint); font-variant-numeric: tabular-nums; display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
.product-cat {
  font-size: 10.5px; font-weight: 600; padding: 1px 8px; border-radius: 20px;
  background: var(--pl-surface-raised); border: 1px solid var(--pl-border); color: var(--pl-text-muted);
}
.cell-price { font-weight: 700; color: var(--pl-text-primary); font-variant-numeric: tabular-nums; line-height: 1.3; padding-top: 16px !important; }

/* stock summary: quantity (neutral) and status (coloured) stay separate */
.stock-cell { display: flex; flex-direction: column; align-items: flex-start; gap: 9px; min-width: 0; width: 100%; }
.stock-summary { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
.stock-total { display: inline-flex; align-items: baseline; gap: 5px; font-variant-numeric: tabular-nums; color: var(--pl-text-muted); font-size: 12px; }
.stock-total strong { font-size: 16px; font-weight: 800; color: var(--pl-text-primary); }
.stock-status {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 11.5px; font-weight: 700; border-radius: 6px; padding: 3px 9px; border: 1px solid transparent;
}
.stock-status i { font-size: 10.5px; }
.stock-status.tone--ok  { background: rgba(16,185,129,.12); color: var(--pl-green); border-color: rgba(16,185,129,.25); }
.stock-status.tone--low { background: rgba(245,158,11,.12); color: #b45309;         border-color: rgba(245,158,11,.35); }
.stock-status.tone--oos { background: rgba(244,63,94,.12);  color: var(--pl-red);   border-color: rgba(244,63,94,.30); }
html[data-theme="dark"] .stock-status.tone--ok  { background: rgba(16,185,129,.16); color: #4ADE80; }
html[data-theme="dark"] .stock-status.tone--low { background: rgba(245,158,11,.16); color: #FBBF24; border-color: rgba(245,158,11,.40); }
html[data-theme="dark"] .stock-status.tone--oos { background: rgba(244,63,94,.18);  color: #F87171; }

/* size health: one slim bar + counts (replaces the always-visible tile wall) */
.size-health { display: flex; align-items: center; flex-wrap: wrap; gap: 8px 12px; width: 100%; }
.health-bar {
  display: flex; gap: 2px; flex: 1 1 90px; max-width: 160px; height: 6px;
  border-radius: 6px; overflow: hidden; background: var(--pl-border);
}
.hb { display: block; flex-basis: 0; min-width: 6px; }
.hb--ok  { background: var(--pl-green); }
.hb--low { background: var(--pl-amber); }
.hb--oos { background: var(--pl-red); }
.health-text { display: inline-flex; flex-wrap: wrap; gap: 4px 12px; font-size: 11.5px; font-weight: 600; color: var(--pl-text-muted); }
.legend-item { display: inline-flex; align-items: center; gap: 6px; }
.legend-item::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
.legend-item--ok  { color: var(--pl-green); }
.legend-item--low { color: #d97706; }
.legend-item--oos { color: var(--pl-red); }
html[data-theme="dark"] .legend-item--ok  { color: #4ADE80; }
html[data-theme="dark"] .legend-item--low { color: #FBBF24; }
html[data-theme="dark"] .legend-item--oos { color: #F87171; }

/* actions column: sizes button, restock, menu */
.td-actions { text-align: right; }
.action-row { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 6px; }

.sizes-btn {
  display: inline-flex; align-items: center; gap: 6px;
  height: 32px; padding: 0 10px; cursor: pointer;
  font-family: inherit; font-size: 12px; font-weight: 600;
  color: var(--pl-text-secondary); background: var(--pl-surface);
  border: 1.5px solid var(--pl-border-strong); border-radius: 8px;
  transition: background-color .15s, border-color .15s, color .15s;
}
.sizes-btn:hover { border-color: var(--pl-accent-border); color: var(--pl-accent); background: var(--pl-accent-soft); }
.sizes-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }
.sizes-btn i { font-size: 9.5px; transition: transform .2s; }
.sizes-btn--open { border-color: var(--pl-accent); color: var(--pl-accent); background: var(--pl-accent-soft); }
.sizes-btn--open i { transform: rotate(180deg); }
.sizes-btn-count {
  min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 10.5px; font-weight: 700; background: var(--pl-border); color: var(--pl-text-secondary);
}
.sizes-btn--open .sizes-btn-count { background: var(--pl-accent); color: #fff; }

.restock-btn {
  display: inline-flex; align-items: center; gap: 6px;
  height: 32px; padding: 0 14px;
  font-size: 12px; font-weight: 600; font-family: inherit;
  border: none; border-radius: 8px; cursor: pointer;
  background: linear-gradient(135deg, #10b981, #059669); color: #fff;
  box-shadow: 0 2px 8px rgba(16,185,129,.30);
  transition: filter .15s, transform .15s, box-shadow .15s;
}
.restock-btn:hover { filter: brightness(1.08); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,.40); }
.restock-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }

.menu-btn {
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: none; color: var(--pl-text-faint); cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center; font-size: 14px;
  transition: background-color .15s, color .15s;
}
.menu-btn:hover, .menu-btn--active { background: var(--pl-surface-raised); color: var(--pl-text-primary); }
.menu-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }

.td-empty { padding: 56px 24px !important; border-bottom: none !important; text-align: center; vertical-align: middle !important; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--pl-text-faint); }
.empty-state i { font-size: 36px; margin-bottom: 4px; }
.empty-state p { font-size: 14.5px; font-weight: 700; color: var(--pl-text-secondary); margin: 0; }
.empty-sub { font-size: 12.5px; color: var(--pl-text-muted); }
.empty-actions { display: flex; gap: 8px; margin-top: 8px; }

/* ── G. SIZE PANEL & TILES ──
   Opens under its product row when "View sizes" is pressed.
   Colour = status (green healthy · amber low · red out). Click a tile = restock that size. */
.data-table tbody tr.sizes-row:hover td { background: transparent; }
.data-table tbody td.sizes-cell { padding: 0 16px 16px 16px; vertical-align: top; }
.sizes-panel {
  background: var(--pl-surface-raised); border: 1px solid var(--pl-border);
  border-radius: 12px; padding: 12px 14px 14px;
  animation: panel-in .18s ease-out;
}
@keyframes panel-in { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
.sizes-panel-head {
  display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
  margin: 0 0 10px; font-size: 12px; font-weight: 600; color: var(--pl-text-secondary);
}
.sizes-panel-head strong { color: var(--pl-text-primary); }
.sizes-panel-hint { font-size: 11.5px; font-weight: 500; color: var(--pl-text-faint); }

.size-grid {
  list-style: none; margin: 0; padding: 0; width: 100%;
  display: grid; grid-template-columns: repeat(auto-fill, minmax(124px, 1fr)); gap: 8px;
}
.size-grid > li { min-width: 0; }
.size-tile {
  --tile-accent: var(--pl-green);
  --tile-text: var(--pl-text-primary);
  position: relative; width: 100%;
  display: flex; flex-direction: column; gap: 4px; text-align: left;
  padding: 9px 11px 10px;
  border: 1px solid var(--pl-border); border-left: 4px solid var(--tile-accent); border-radius: 10px;
  background: var(--pl-surface); color: inherit; font-family: inherit; cursor: pointer;
  transition: transform .15s, box-shadow .15s, border-color .15s, background-color .15s;
}
.size-tile:hover { transform: translateY(-1px); box-shadow: var(--c-shadow-sm); border-color: var(--tile-accent); }
.size-tile:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }
.size-tile.tone--low { --tile-accent: #f59e0b; --tile-text: #b45309; background: rgba(245,158,11,.07); }
.size-tile.tone--oos { --tile-accent: var(--pl-red); --tile-text: var(--pl-red); background: rgba(244,63,94,.08); }
html[data-theme="dark"] .size-tile.tone--low { --tile-text: #FBBF24; }
html[data-theme="dark"] .size-tile.tone--oos { --tile-text: #F87171; }

.size-tile-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 6px; }
.size-tile-label { font-size: 12.5px; font-weight: 700; line-height: 1.25; color: var(--pl-text-primary); overflow-wrap: anywhere; }
.size-tile-plus {
  flex-shrink: 0; font-size: 10px; margin-top: 3px; color: var(--tile-accent);
  opacity: 0; transition: opacity .15s;
}
.size-tile:hover .size-tile-plus, .size-tile:focus-visible .size-tile-plus { opacity: 1; }

.size-tile-row { display: flex; align-items: baseline; gap: 8px; }
.size-tile-qty { font-size: 20px; font-weight: 800; line-height: 1; letter-spacing: -.02em; color: var(--tile-text); font-variant-numeric: tabular-nums; }
.size-tile-tag {
  font-size: 10px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
  border-radius: 20px; padding: 2px 7px; color: #fff; background: var(--tile-accent);
}

.size-tile-bar { display: block; height: 4px; border-radius: 4px; background: var(--pl-border); overflow: hidden; }
.size-tile-bar > span { display: block; height: 100%; width: var(--fill, 0%); border-radius: 4px; background: var(--tile-accent); }
.size-tile-price { font-size: 11px; font-weight: 600; color: var(--pl-text-faint); font-variant-numeric: tabular-nums; }

/* ── H. CUT TRACKING ── */
.rem-name  { font-weight: 700; color: var(--pl-text-primary); font-size: 13.5px; overflow-wrap: anywhere; }
.rem-meta  { font-size: 11px; color: var(--pl-text-faint); margin-top: 2px; }
.rem-size  {
  display: inline-block; margin-top: 5px; padding: 2px 9px; border-radius: 20px;
  font-size: 11.5px; font-weight: 700; color: var(--pl-accent);
  background: var(--pl-accent-soft); border: 1px solid var(--pl-accent-border);
}
.rem-unit-pill {
  display: inline-flex; align-items: center; margin-left: 6px; padding: 1px 7px;
  font-size: 10px; font-weight: 700; border-radius: 20px;
  background: var(--pl-border); color: var(--pl-text-secondary); vertical-align: 1px;
}
.rem-strong { font-weight: 700; color: var(--pl-text-primary); font-variant-numeric: tabular-nums; }
.rem-muted  { color: var(--pl-text-secondary); font-variant-numeric: tabular-nums; }
.rem-date-day  { font-size: 12.5px; font-weight: 600; color: var(--pl-text-secondary); }
.rem-date-time { font-size: 11.5px; color: var(--pl-text-muted); margin-top: 2px; }

.rem-status { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; text-transform: capitalize; border-radius: 20px; padding: 4px 11px; white-space: nowrap; }
.rem-status--available { background: rgba(16,185,129,.14); color: #047857; }
.rem-status--used      { background: rgba(120,113,108,.16); color: var(--pl-text-secondary); }
.rem-status--scrapped  { background: rgba(244,63,94,.14); color: #BE123C; }
html[data-theme="dark"] .rem-status--available { background: rgba(16,185,129,.16); color: #4ADE80; }
html[data-theme="dark"] .rem-status--scrapped  { background: rgba(244,63,94,.16); color: #F87171; }

.td-rem-action { text-align: right; }
.rem-actions { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
.rem-btn {
  border: 1px solid var(--pl-border-strong); background: var(--pl-surface-raised);
  color: var(--pl-text-secondary); cursor: pointer;
  font-family: inherit; font-size: 12px; font-weight: 600;
  padding: 5px 11px; border-radius: 8px;
  transition: background-color .15s, border-color .15s, color .15s;
}
.rem-btn:hover:not(:disabled) { border-color: var(--pl-accent-border); color: var(--pl-accent); background: var(--pl-accent-soft); }
.rem-btn--danger:hover:not(:disabled) { border-color: rgba(244,63,94,.4); color: var(--pl-red); background: rgba(244,63,94,.10); }
.rem-btn:disabled { opacity: .5; cursor: default; }
.rem-btn:focus-visible { outline: 2px solid var(--pl-accent); outline-offset: 2px; }

.rem-empty { padding: 40px 16px !important; text-align: center; color: var(--pl-text-muted); font-size: 13.5px; }
.rem-empty i { display: block; margin: 0 auto 8px; font-size: 22px; color: var(--pl-text-faint); }
.rem-error { margin: 0; padding: 12px 22px 16px; font-size: 12.5px; font-weight: 600; color: var(--pl-red); border-top: 1px solid var(--pl-border); }

/* ── I. MODALS ── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(15, 23, 42, .70);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
html[data-theme="dark"] .modal-backdrop { background: rgba(0, 0, 0, .82); }
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.95) translateY(10px); }

/* restock modal */
.restock-modal {
  width: 100%; max-width: 480px; max-height: calc(100vh - 40px); overflow: hidden;
  display: flex; flex-direction: column;
  background: #FFFFFF; border: 1px solid #E5E7EB;
  border-radius: 22px; box-shadow: 0 24px 64px rgba(0,0,0,.35);
}
html[data-theme="dark"] .restock-modal { background: #1E2130; border-color: #2A2D3E; box-shadow: 0 24px 64px rgba(0,0,0,.6); }
.restock-modal::before { content: ''; display: block; flex-shrink: 0; height: 4px; background: linear-gradient(90deg, var(--pl-green), #34d399); }
.restock-modal-header { flex-shrink: 0; display: flex; align-items: center; gap: 12px; padding: 16px 20px 12px; border-bottom: 1px solid var(--pl-border); }
.restock-modal-icon {
  width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
  background: rgba(16,185,129,.12); color: var(--pl-green);
  display: flex; align-items: center; justify-content: center; font-size: 16px;
  border: 1px solid rgba(16,185,129,.25);
}
html[data-theme="dark"] .restock-modal-icon { background: rgba(16,185,129,.18); }
.restock-modal-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: .12em; color: var(--pl-green); text-transform: uppercase; margin: 0 0 2px; }
.restock-modal-title   { font-size: 15px; font-weight: 700; color: var(--pl-text-primary); margin: 0; }
.modal-close-btn {
  margin-left: auto; width: 30px; height: 30px; border: none; border-radius: 8px;
  background: var(--pl-surface-raised); color: var(--pl-text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 12px;
  transition: background .15s, color .15s;
}
.modal-close-btn:hover { background: var(--pl-border-strong); color: var(--pl-text-primary); }
/* the body only scrolls as a last resort on very short screens */
.restock-modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 16px 20px 20px; display: flex; flex-direction: column; gap: 14px; }
.restock-form { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; gap: 14px; }

.restock-product-chip { flex-shrink: 0; display: flex; align-items: center; gap: 12px; background: var(--pl-surface-raised); border: 1px solid var(--pl-border); border-radius: 12px; padding: 12px 14px; }
.restock-chip-avatar {
  width: 38px; height: 38px; border-radius: 9px; flex-shrink: 0;
  background: var(--pl-accent-soft); color: var(--pl-accent);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; overflow: hidden; border: 1px solid var(--pl-accent-border);
}
.restock-chip-img   { width: 100%; height: 100%; object-fit: cover; }
.restock-chip-name  { font-size: 13.5px; font-weight: 700; color: var(--pl-text-primary); margin: 0 0 2px; }
.restock-chip-stock { font-size: 12px; color: var(--pl-text-muted); margin: 0; }
.restock-chip-stock strong { color: var(--pl-green); }

.restock-field { display: flex; flex-direction: column; gap: 8px; }
.restock-field--qty { flex-shrink: 0; }
.restock-field--sizes { flex: 1 1 auto; min-height: 0; }
.restock-label-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 4px 12px; flex-shrink: 0; }
.size-pick-legend { display: inline-flex; flex-wrap: wrap; gap: 2px 12px; font-size: 11.5px; font-weight: 600; }
.restock-label { font-size: 12.5px; font-weight: 600; color: var(--pl-text-secondary); }
.req { color: var(--pl-accent); }
.restock-hint { font-size: 12px; color: var(--pl-text-faint); margin: 0; display: flex; flex-wrap: wrap; align-items: center; gap: 4px; }
.restock-hint strong { color: var(--pl-green); }
.restock-warn { color: var(--pl-amber); font-weight: 600; margin-left: 4px; }

/* size picker inside the modal
   green = has stock · amber = low · red = out. Strong tint + border so the status reads at a glance. */
.size-pick {
  flex: 1 1 auto; min-height: 96px; overflow-y: auto; overscroll-behavior: contain;
  display: grid; grid-template-columns: repeat(auto-fill, minmax(128px, 1fr)); gap: 8px; align-content: start;
  padding: 4px;
}
.size-pick-btn {
  --sp-accent: #10b981; --sp-bg: rgba(16,185,129,.13); --sp-border: rgba(16,185,129,.45); --sp-text: #047857;
  position: relative;
  display: flex; flex-direction: column; align-items: flex-start; gap: 2px;
  padding: 9px 12px; border-radius: 10px; cursor: pointer; text-align: left;
  border: 1.5px solid var(--sp-border); border-left: 5px solid var(--sp-accent);
  background: var(--sp-bg); color: var(--pl-text-primary); font-family: inherit;
  transition: border-color .15s, box-shadow .15s, transform .12s, filter .15s;
}
.size-pick-btn.tone--low { --sp-accent: #f59e0b; --sp-bg: rgba(245,158,11,.17); --sp-border: rgba(245,158,11,.55); --sp-text: #b45309; }
.size-pick-btn.tone--oos { --sp-accent: #f43f5e; --sp-bg: rgba(244,63,94,.15);  --sp-border: rgba(244,63,94,.50);  --sp-text: #be123c; }
html[data-theme="dark"] .size-pick-btn { --sp-bg: rgba(16,185,129,.18); --sp-text: #4ADE80; }
html[data-theme="dark"] .size-pick-btn.tone--low { --sp-bg: rgba(245,158,11,.20); --sp-text: #FBBF24; }
html[data-theme="dark"] .size-pick-btn.tone--oos { --sp-bg: rgba(244,63,94,.22);  --sp-text: #F87171; }

.size-pick-btn:hover { filter: brightness(.97); transform: translateY(-1px); }
.size-pick-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pl-ring); }
.size-pick-label { font-size: 13px; font-weight: 700; padding-right: 16px; }
.size-pick-qty   { font-size: 11.5px; font-weight: 700; color: var(--sp-text); }

/* selected size: accent ring + check mark, status colours stay visible */
.size-pick-btn--active {
  border-top-color: var(--pl-accent); border-right-color: var(--pl-accent); border-bottom-color: var(--pl-accent);
  box-shadow: 0 0 0 3px var(--pl-ring);
}
.size-pick-check { position: absolute; top: 8px; right: 8px; font-size: 14px; color: var(--pl-accent); }

.qty-row { display: flex; align-items: center; gap: 10px; }
.qty-btn {
  width: 40px; height: 40px; border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface); color: var(--pl-text-secondary); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;
  transition: border-color .15s, background-color .15s, color .15s;
}
.qty-btn:hover { border-color: var(--pl-accent-border); color: var(--pl-accent); background: var(--pl-accent-soft); }
.qty-input {
  flex: 1; height: 40px; text-align: center; min-width: 0;
  border: 1.5px solid var(--pl-border-strong); border-radius: 10px;
  background: var(--pl-surface-raised); color: var(--pl-text-primary);
  font-size: 18px; font-weight: 700; font-family: inherit; outline: none;
  transition: border-color .18s, background-color .22s, color .22s;
}
.qty-input:focus { border-color: var(--pl-accent); box-shadow: 0 0 0 3px var(--pl-ring); }
.qty-quick { display: flex; gap: 6px; }
.qty-chip {
  flex: 1; height: 30px; border-radius: 8px; cursor: pointer;
  border: 1.5px solid var(--pl-border-strong); background: var(--pl-surface);
  color: var(--pl-text-secondary); font-size: 12px; font-weight: 700; font-family: inherit;
  transition: border-color .15s, background-color .15s, color .15s;
}
.qty-chip:hover { border-color: var(--pl-accent-border); color: var(--pl-accent); background: var(--pl-accent-soft); }
.restock-footer { flex-shrink: 0; display: flex; gap: 10px; margin-top: 2px; }
.restock-footer .btn { flex: 1; justify-content: center; }

/* delete modals (single + bulk) */
.del-modal {
  width: 100%; max-width: 400px;
  background: #FFFFFF; border: 1px solid #E5E7EB;
  border-radius: 22px; box-shadow: 0 24px 64px rgba(0,0,0,.35); overflow: hidden;
  display: flex; flex-direction: column;
}
html[data-theme="dark"] .del-modal { background: #1E2130; border-color: #2A2D3E; box-shadow: 0 24px 64px rgba(0,0,0,.6); }
.del-modal::before { content: ''; display: block; height: 4px; background: linear-gradient(90deg, #f43f5e, #fb7185); }
.del-modal-icon-ring { display: flex; align-items: center; justify-content: center; padding: 28px 0 0; }
.del-modal-icon {
  width: 60px; height: 60px; border-radius: 16px;
  background: rgba(244,63,94,.10); border: 1.5px solid rgba(244,63,94,.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; color: #f43f5e; box-shadow: 0 0 0 8px rgba(244,63,94,.06);
}
html[data-theme="dark"] .del-modal-icon { background: rgba(244,63,94,.14); color: #F87171; box-shadow: 0 0 0 8px rgba(244,63,94,.08); }
.del-modal-body  { padding: 18px 26px 22px; display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center; }
.del-modal-title { font-size: 18px; font-weight: 800; color: var(--pl-text-primary); letter-spacing: -.02em; margin: 0; }
.del-modal-desc  { font-size: 13px; line-height: 1.65; color: var(--pl-text-muted); margin: 0; }
.del-modal-name  { color: var(--pl-text-primary); font-weight: 700; }
.del-modal-chip  {
  display: flex; align-items: center; gap: 12px; width: 100%; margin-top: 6px;
  background: var(--pl-surface-raised); border: 1px solid var(--pl-border);
  border-radius: 12px; padding: 11px 14px; text-align: left;
}
.del-modal-chip-avatar {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  background: rgba(244,63,94,.12); color: #f43f5e;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; border: 1px solid rgba(244,63,94,.22);
}
html[data-theme="dark"] .del-modal-chip-avatar { background: rgba(244,63,94,.18); color: #F87171; }
.del-modal-chip-info { flex: 1; min-width: 0; }
.del-modal-chip-name { font-size: 13px; font-weight: 700; color: var(--pl-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 2px; }
.del-modal-chip-meta { font-size: 11.5px; color: var(--pl-text-faint); margin: 0; }
.del-modal-footer { display: flex; gap: 10px; padding: 0 26px 26px; }
.del-modal-btn {
  flex: 1; height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 13.5px; font-weight: 600; font-family: 'Inter', system-ui, sans-serif;
  border-radius: 11px; cursor: pointer; transition: background-color .18s, border-color .18s, opacity .18s, transform .15s;
}
.del-modal-btn:active { transform: scale(.97); }
.del-modal-btn:disabled { opacity: .55; cursor: not-allowed; transform: none; }
.del-modal-btn--cancel { background: var(--pl-surface-raised); border: 1.5px solid var(--pl-border-strong); color: var(--pl-text-secondary); }
.del-modal-btn--cancel:hover:not(:disabled) { background: var(--pl-border); color: var(--pl-text-primary); }
.del-modal-btn--confirm { background: linear-gradient(135deg, #f43f5e, #e11d48); border: none; color: #fff; box-shadow: 0 2px 12px rgba(244,63,94,.35); }
.del-modal-btn--confirm:hover:not(:disabled) { filter: brightness(1.08); box-shadow: 0 4px 20px rgba(244,63,94,.50); transform: translateY(-1px); }

/* ── J. RESPONSIVE (no horizontal scroll at any width) & REDUCED MOTION ── */

/* Mid widths: tighten the actions so the three buttons never overflow */
@media (max-width: 1500px) {
  .sizes-btn-text { display: none; }
  .sizes-btn { padding: 0 9px; }
}

/* 4 stock cards: go 2 x 2 before they get cramped */
@media (max-width: 1300px) {
  .kpi-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 1000px) {
  .kpi-strip, .cut-stats { grid-template-columns: 1fr; }
}

/* Narrow screens: every table row becomes a labelled card */
@media (max-width: 860px) {
  .prod-table, .prod-table tbody, .prod-table tr, .prod-table td,
  .rem-table,  .rem-table tbody,  .rem-table tr,  .rem-table td { display: block; width: 100%; }
  .prod-table thead, .rem-table thead { display: none; }
  .prod-table colgroup, .rem-table colgroup { display: none; }

  .prod-table tbody tr.prod-row,
  .rem-table  tbody tr.rem-row {
    position: relative; margin: 0; padding: 12px 6px 8px;
    border-bottom: 1px solid var(--pl-border);
  }
  .prod-table tbody tr.row-open { border-bottom: none; }
  .data-table tbody td { border-bottom: none; padding: 8px 14px; }
  .data-table tbody tr:hover td { background: transparent; }
  .data-table tbody td[data-label]::before {
    content: attr(data-label);
    display: block; margin-bottom: 4px;
    font-size: 10.5px; font-weight: 700; letter-spacing: .07em;
    text-transform: uppercase; color: var(--pl-text-faint);
  }
  .data-table tbody td[data-label="Product"]::before { display: none; }
  .data-table tbody td.td-check { position: absolute; top: 14px; right: 6px; width: auto; padding: 0 14px; z-index: 1; }
  .data-table tbody td.td-actions, .data-table tbody td.td-rem-action { text-align: left; }
  .action-row, .rem-actions { justify-content: flex-start; }
  .sizes-btn-text { display: inline; }
  .data-table tbody td.sizes-cell { padding: 0 12px 14px; }
  .data-table tbody tr.sizes-row { border-bottom: 1px solid var(--pl-border); }
}

@media (max-width: 700px) {
  .prod-list-page { padding: 16px 16px 48px; }
  .page-header .btn { width: 100%; justify-content: center; }
  .view-tab { flex: 1; justify-content: center; padding: 11px 8px 13px; }
  .toolbar-row { flex-direction: column; align-items: stretch; }
  .search-wrap { flex-basis: auto; }
  .stock-filter-toggle { width: 100%; }
  .sf-btn { flex: 1; justify-content: center; padding: 0 6px; }
  .select-group { width: 100%; }
  .form-select { width: 100%; }
  .toolbar-end { margin-left: 0; }
  .toolbar-end .btn { flex: 1; justify-content: center; }
}
@media (prefers-reduced-motion: reduce) {
  .sk, .row-highlight td, .sizes-panel { animation: none; }
  .kpi-card, .cut-stat, .size-tile, .btn, .restock-btn, .sizes-btn i { transition: none; }
  .kpi-card:hover, .cut-stat:hover, .size-tile:hover { transform: none; }
  .modal-fade-enter-active, .modal-fade-leave-active,
  .alert-fade-enter-active, .alert-fade-leave-active { transition: none; }
}
</style>

<style>
/* TELEPORTED DROPDOWN — global (it renders outside the page element) */
.prod-dropdown {
  width: 176px;
  background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 13px;
  box-shadow: 0 8px 32px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06);
  z-index: 9999; padding: 5px; overflow: hidden;
}
html[data-theme="dark"] .prod-dropdown { background: #1E2130; border-color: #2A2D3E; box-shadow: 0 8px 40px rgba(0,0,0,.55); }

.prod-dropdown-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px; font-size: 13px; font-weight: 500;
  color: #374151; border-radius: 8px; text-decoration: none;
  background: none; border: none; width: 100%; cursor: pointer;
  font-family: 'Inter', system-ui, sans-serif; transition: background-color .15s, color .15s;
}
.prod-dropdown-item:hover { background: #F9FAFB; color: #111827; }
html[data-theme="dark"] .prod-dropdown-item       { color: #C4C8D6; }
html[data-theme="dark"] .prod-dropdown-item:hover { background: #222535; color: #E8EAF0; }
.prod-dropdown-item--danger       { color: #EF4444 !important; }
.prod-dropdown-item--danger:hover { background: rgba(244,63,94,.10) !important; }
html[data-theme="dark"] .prod-dropdown-item--danger:hover { background: rgba(244,63,94,.14) !important; color: #F87171 !important; }

.prod-dropdown-icon {
  width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 11.5px;
}
.prod-dropdown-icon--blue  { background: rgba(59,130,246,.12); color: #3b82f6; }
.prod-dropdown-icon--amber { background: rgba(245,158,11,.12);  color: #f59e0b; }
.prod-dropdown-icon--red   { background: rgba(244,63,94,.12);   color: #f43f5e; }
html[data-theme="dark"] .prod-dropdown-icon--blue  { background: rgba(59,130,246,.18); }
html[data-theme="dark"] .prod-dropdown-icon--amber { background: rgba(245,158,11,.18); }
html[data-theme="dark"] .prod-dropdown-icon--red   { background: rgba(244,63,94,.18); color: #F87171; }
.prod-dropdown-divider { height: 1px; background: #E5E7EB; margin: 3px 0; }
html[data-theme="dark"] .prod-dropdown-divider { background: #2A2D3E; }
</style>