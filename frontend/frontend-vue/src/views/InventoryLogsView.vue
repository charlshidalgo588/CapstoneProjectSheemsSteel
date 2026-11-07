<template>
  <Layout title="Inventory Logs">
    <div id="inventory-print-area" class="inv-logs">

      <!-- ══════════════ PRINT-ONLY LETTERHEAD ══════════════ -->
      <div class="print-only print-header">
        <div class="ph-band"></div>
        <div class="ph-row">
          <div class="ph-brand">
            <span class="ph-logo">SS</span>
            <div>
              <p class="ph-name">Sheem Steel Construction Company</p>
              <p class="ph-tag">Inventory Management</p>
            </div>
          </div>
          <dl class="ph-meta">
            <div><dt>Report no.</dt><dd>{{ reportRef }}</dd></div>
            <div><dt>Period</dt><dd>{{ filterLabel }}</dd></div>
            <div><dt>Generated</dt><dd>{{ printGeneratedAt }}</dd></div>
            <div><dt>Records</dt><dd>{{ formatNumber(inventoryLogs.length) }} movements</dd></div>
          </dl>
        </div>
        <div class="ph-title-row">
          <div>
            <p class="ph-kicker">Stock movement report</p>
            <h1 class="ph-title">Inventory Logs</h1>
          </div>
          <div class="ph-title-right">
            <p class="ph-period">{{ filterLabel }}</p>
            <span class="ph-chip" :class="isCurrentPeriod ? 'ph-chip--live' : 'ph-chip--closed'">{{ isCurrentPeriod ? 'In progress' : 'Closed period' }}</span>
          </div>
        </div>
        <p class="ph-lead">{{ execSummary }}</p>
      </div>

      <!-- PAGE HEADER -->
      <div class="page-header">
        <div class="page-header-left">
          <p class="page-eyebrow">Sales Intelligence</p>
          <h1 class="page-title">Inventory Logs</h1>
          <p class="page-sub">Showing <strong>{{ filterLabel }}</strong></p>
        </div>

        <div class="period-control">
          <div class="period-mode-toggle">
            <button type="button" class="period-mode-seg" :class="{ 'period-mode-seg--active': periodMode === 'month' }" @click="setPeriodMode('month')">By Month</button>
            <button type="button" class="period-mode-seg" :class="{ 'period-mode-seg--active': periodMode === 'date' }" @click="setPeriodMode('date')">Specific Date</button>
          </div>

          <div class="period-nav">
            <button type="button" class="icon-btn" :disabled="!canGoPrev || isFiltering" :aria-label="`Previous ${periodNoun}`" @click="shiftPeriod(-1)">
              <i class="fa-solid fa-chevron-left"></i>
            </button>

            <!-- Custom calendar picker (replaces the native month / date inputs) -->
            <div class="picker" ref="pickerRoot">
              <button
                ref="pickerTrigger"
                type="button"
                class="picker-trigger"
                :class="{ 'picker-trigger--open': pickerOpen }"
                aria-haspopup="dialog"
                :aria-expanded="pickerOpen"
                :aria-label="`Choose ${periodNoun}. Selected: ${pickerLabel}`"
                @click="togglePicker"
              >
                <i class="fa-solid fa-calendar-days picker-ico"></i>
                <span>{{ pickerLabel }}</span>
                <i class="fa-solid fa-chevron-down picker-caret"></i>
              </button>

              <Transition name="pop">
                <div v-if="pickerOpen" class="picker-pop" role="dialog" :aria-label="`Choose ${periodNoun}`">
                  <div class="pk-head">
                    <button type="button" class="pk-nav" :class="{ 'pk-nav--hide': pickerView === 'years' }" :disabled="!pkCanPrev" aria-label="Previous" @click="pkShift(-1)">
                      <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button type="button" class="pk-title" :disabled="pickerView === 'years'" @click="pkUp">
                      {{ pkTitle }}
                      <i v-if="pickerView !== 'years'" class="fa-solid fa-chevron-down"></i>
                    </button>
                    <button type="button" class="pk-nav" :class="{ 'pk-nav--hide': pickerView === 'years' }" :disabled="!pkCanNext" aria-label="Next" @click="pkShift(1)">
                      <i class="fa-solid fa-chevron-right"></i>
                    </button>
                  </div>

                  <!-- Days -->
                  <div v-if="pickerView === 'days'" class="pk-days">
                    <span v-for="w in PK_WEEKDAYS" :key="w" class="pk-dow">{{ w }}</span>
                    <template v-for="(c, i) in pkDayCells" :key="i">
                      <span v-if="!c" class="pk-blank"></span>
                      <button
                        v-else
                        type="button"
                        class="pk-day"
                        :class="{ 'pk-on': c.selected, 'pk-today': c.today }"
                        :disabled="c.future"
                        :aria-pressed="c.selected"
                        :aria-label="c.title"
                        @click="pkPickDay(c.key)"
                      >{{ c.d }}</button>
                    </template>
                  </div>

                  <!-- Months -->
                  <div v-else-if="pickerView === 'months'" class="pk-grid">
                    <button
                      v-for="c in pkMonthCells"
                      :key="c.key"
                      type="button"
                      class="pk-cell"
                      :class="{ 'pk-on': c.selected, 'pk-today': c.today }"
                      :disabled="c.future"
                      :aria-pressed="c.selected"
                      :aria-label="c.title"
                      @click="pkPickMonth(c.m)"
                    >{{ c.label }}</button>
                  </div>

                  <!-- Years -->
                  <div v-else class="pk-grid">
                    <button
                      v-for="c in pkYearCells"
                      :key="c.y"
                      type="button"
                      class="pk-cell"
                      :class="{ 'pk-on': c.selected, 'pk-today': c.today }"
                      :aria-pressed="c.selected"
                      @click="pkPickYear(c.y)"
                    >{{ c.y }}</button>
                  </div>

                  <div class="pk-foot">
                    <button type="button" class="pk-link pk-link--muted" @click="closePicker(true)">Close</button>
                    <button type="button" class="pk-link" :disabled="isCurrentPeriod" @click="pkGoCurrent">
                      <i class="fa-solid fa-location-crosshairs"></i>{{ currentLabel }}
                    </button>
                  </div>
                </div>
              </Transition>
            </div>

            <button type="button" class="icon-btn" :disabled="!canGoNext || isFiltering" :aria-label="`Next ${periodNoun}`" @click="shiftPeriod(1)">
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>

          <button v-if="!isCurrentPeriod" type="button" class="link-btn" :disabled="isFiltering" @click="goCurrent">{{ currentLabel }}</button>

          <span class="date-filter-loading" v-if="isFiltering">
            <i class="fa-solid fa-spinner fa-spin"></i>
          </span>
        </div>
      </div>

      <!-- REPORT ACTIONS -->
      <div class="report-toolbar">
        <button type="button" class="rt-btn" @click="copyReport" :disabled="copyState === 'copying'">
          <i class="fa-solid" :class="copyState === 'copied' ? 'fa-check' : copyState === 'error' ? 'fa-triangle-exclamation' : 'fa-copy'"></i>
          {{ copyState === 'copied' ? 'Copied!' : copyState === 'error' ? 'Copy failed' : 'Copy' }}
        </button>
        <button type="button" class="rt-btn" @click="exportCSV">
          <i class="fa-solid fa-file-csv"></i>CSV
        </button>
        <button type="button" class="rt-btn rt-btn--primary" @click="printReport">
          <i class="fa-solid fa-print"></i>Print
        </button>
      </div>

      <!-- Summary Cards -->
      <div class="section">
        <div class="section-header">
          <p class="card-eyebrow">Overview</p>
          <h2 class="section-title">Inventory Activity Summary</h2>
        </div>

        <div class="kpi-strip">
          <div class="kpi-card kpi-card--featured">
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap kpi-icon-wrap--orange"><i class="fa-solid fa-sliders"></i></div>
              <div class="kpi-body">
                <p class="kpi-label">Total Adjustments</p>
                <p class="kpi-value">{{ formatNumber(summaryDisplay.total_adjustments) }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--orange"></div>
          </div>

          <div class="kpi-card kpi-card--clickable" role="button" tabindex="0"
            @click="openBreakdown('stock_in')" @keydown.enter="openBreakdown('stock_in')" @keydown.space.prevent="openBreakdown('stock_in')">
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap kpi-icon-wrap--green"><i class="fa-solid fa-download"></i></div>
              <div class="kpi-body">
                <p class="kpi-label">Total Stock In</p>
                <p class="kpi-value kpi-value--green">+{{ formatNumber(summaryDisplay.total_stock_in) }}</p>
                <p class="kpi-hint">View breakdown</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--green"></div>
          </div>

          <div class="kpi-card kpi-card--alert kpi-card--clickable" role="button" tabindex="0"
            @click="openBreakdown('stock_out')" @keydown.enter="openBreakdown('stock_out')" @keydown.space.prevent="openBreakdown('stock_out')">
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap kpi-icon-wrap--red"><i class="fa-solid fa-arrow-up-from-bracket"></i></div>
              <div class="kpi-body">
                <p class="kpi-label">Total Stock Out</p>
                <p class="kpi-value kpi-value--red">-{{ formatNumber(summaryDisplay.total_stock_out) }}</p>
                <p class="kpi-hint">View breakdown</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--red"></div>
          </div>

          <div class="kpi-card">
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap kpi-icon-wrap--violet"><i class="fa-solid fa-pen-to-square"></i></div>
              <div class="kpi-body">
                <p class="kpi-label">Manual Adjustments</p>
                <p class="kpi-value kpi-value--violet">{{ formatNumber(summaryDisplay.manual_adjustments) }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--violet"></div>
          </div>

          <div class="kpi-card">
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap kpi-icon-wrap--orange"><i class="fa-solid fa-scissors"></i></div>
              <div class="kpi-body">
                <p class="kpi-label">Cut Pieces Sold</p>
                <p class="kpi-value">{{ formatNumber(summaryDisplay.cut_count) }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--orange"></div>
          </div>

          <div class="kpi-card">
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap kpi-icon-wrap--green"><i class="fa-solid fa-screwdriver-wrench"></i></div>
              <div class="kpi-body">
                <p class="kpi-label">Service Fees</p>
                <p class="kpi-value kpi-value--green">{{ peso(summaryDisplay.service_revenue) }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--green"></div>
          </div>
        </div>
      </div>

      <!-- Recent Activity (screen only: the printed report lists every movement in the Full History section) -->
      <div class="section section--screen">
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Latest</p>
              <h2 class="card-title">Recent Activity</h2>
              <p class="card-sub">Quantity is the number of whole pieces moved. For cut sales it also shows how much length was sold and how much was left over. Leftover pieces themselves are managed on the Inventory page.</p>
            </div>
          </div>
          <div class="table-wrap">
            <table id="recentActivityTable" class="data-table data-table--fixed data-table--stack">
              <thead>
                <tr>
                  <th style="width:27%">Product</th>
                  <th style="width:15%">Type</th>
                  <th style="width:24%">Quantity</th>
                  <th style="width:12%">Date</th>
                  <th style="width:22%">Notes</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in recentActivity" :key="item.id">
                  <td data-label="Product">
                    <div class="cell-product">{{ item.ProductName }}</div>
                    <div class="cell-sku">
                      {{ item.SKU }}
                      <span v-if="item.Unit" class="unit-pill">{{ unitLabel(item.Unit) }}</span>
                    </div>
                    <div v-if="item.VariantLabel" class="cell-sku">Size: {{ item.VariantLabel }}</div>
                  </td>
                  <td data-label="Type">
                    <div class="pill-stack">
                      <span class="status-pill" :class="'status-pill--' + item.type_pill.tone">
                        {{ item.type_pill.label }}
                      </span>
                      <span v-if="isLeftoverUsed(item)" class="status-pill status-pill--rem">
                        <i class="fa-solid fa-ruler-horizontal"></i> Leftover used
                      </span>
                      <span v-if="isNewProduct(item)" class="status-pill status-pill--new">New</span>
                      <span v-if="item.is_cut" class="status-pill status-pill--cut"><i class="fa-solid fa-scissors"></i> Cut</span>
                    </div>
                  </td>
                  <td data-label="Quantity" class="cell-qty" :data-order="num(item.quantity)">
                    <div class="qty-main" :class="'qty-main--' + item.qty_info.tone">{{ item.qty_info.main }}</div>
                    <div v-if="item.qty_info.caption" class="qty-caption">{{ item.qty_info.caption }}</div>
                    <dl v-if="item.qty_info.details.length" class="qty-details">
                      <div v-for="d in item.qty_info.details" :key="d.label" class="qty-row">
                        <dt>{{ d.label }}</dt>
                        <dd>{{ d.value }}</dd>
                      </div>
                    </dl>
                  </td>
                  <td data-label="Date" class="cell-date" :data-order="item.created_at" :title="item.created_at">
                    <div class="cell-date-day">{{ dateOnly(item.created_at) }}</div>
                    <div class="cell-date-time">{{ timeOnly(item.created_at) }}</div>
                  </td>
                  <td data-label="Notes" class="cell-muted cell-notes">{{ item.notes }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Top Adjusted Products -->
      <div class="section">
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Performance</p>
              <h2 class="card-title">Top Adjusted Products</h2>
            </div>
          </div>
          <div class="table-wrap">
            <table id="topAdjustedTable" class="data-table data-table--fixed data-table--stack">
              <thead>
                <tr>
                  <th style="width:40%">Product</th>
                  <th style="width:16%">Adjustments</th>
                  <th style="width:22%">Total Stock In</th>
                  <th style="width:22%">Total Stock Out</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in topAdjustedProducts" :key="item.ProductID ?? item.id">
                  <td data-label="Product">
                    <div class="cell-product">{{ item.ProductName }}</div>
                    <div class="cell-sku">
                      {{ item.SKU }}
                      <span v-if="item.Unit" class="unit-pill">{{ unitLabel(item.Unit) }}</span>
                    </div>
                  </td>
                  <td data-label="Adjustments" class="cell-muted">{{ item.adjustment_count }}</td>
                  <td data-label="Total Stock In" class="cell-positive" :data-order="num(item.total_stock_in)">+{{ formatQty(item.total_stock_in, item.Unit) }}</td>
                  <td data-label="Total Stock Out" class="cell-negative" :data-order="num(item.total_stock_out)">-{{ formatQty(item.total_stock_out, item.Unit) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Extra Services (labour) — plain table, not DataTables -->
      <div class="section">
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Labour</p>
              <h2 class="card-title">Extra Services</h2>
            </div>
          </div>
          <div class="table-wrap">
            <table class="data-table data-table--plain data-table--fixed data-table--stack">
              <thead>
                <tr>
                  <th style="width:11%">Date</th>
                  <th style="width:10%">Receipt</th>
                  <th style="width:22%">Service</th>
                  <th style="width:11%">Fee</th>
                  <th style="width:14%">Worker</th>
                  <th style="width:16%">Worker share</th>
                  <th style="width:16%">Owner share</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(s, i) in services" :key="s.SaleID + '-' + i">
                  <td data-label="Date" class="cell-faint">{{ s.SaleDate }}</td>
                  <td data-label="Receipt" class="cell-muted">{{ receiptNo(s.SaleID) }}</td>
                  <td data-label="Service" class="cell-product">{{ s.Description }}</td>
                  <td data-label="Fee" class="cell-muted">{{ peso(s.Fee) }}</td>
                  <td data-label="Worker" class="cell-muted">{{ s.WorkerName }}</td>
                  <td data-label="Worker share" class="cell-negative">{{ peso(s.WorkerAmount) }} <span class="cell-faint">({{ pct(s.WorkerPct) }})</span></td>
                  <td data-label="Owner share" class="cell-positive">{{ peso(s.OwnerAmount) }} <span class="cell-faint">({{ pct(s.OwnerPct) }})</span></td>
                </tr>
                <tr v-if="!services.length">
                  <td colspan="7" class="il-empty">No extra services in this period.</td>
                </tr>
              </tbody>
              <tfoot v-if="services.length">
                <tr class="svc-total">
                  <td colspan="3">Total</td>
                  <td>{{ peso(serviceTotals.fee) }}</td>
                  <td></td>
                  <td>{{ peso(serviceTotals.worker) }}</td>
                  <td>{{ peso(serviceTotals.owner) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Full Inventory Logs -->
      <div class="section">
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Full History</p>
              <h2 class="card-title">Inventory Logs</h2>
              <p class="card-sub">Every stock movement in this period, including cut sales and leftover updates.</p>
            </div>
          </div>
          <div class="table-wrap">
            <table id="inventoryLogsTable" class="data-table data-table--fixed data-table--stack">
              <thead>
                <tr>
                  <th style="width:23%">Product</th>
                  <th style="width:10%">Category</th>
                  <th style="width:13%">Type</th>
                  <th style="width:21%">Quantity</th>
                  <th style="width:11%">Date</th>
                  <th style="width:22%">Notes</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in inventoryLogs" :key="item.id">
                  <td data-label="Product">
                    <div class="cell-product">{{ item.ProductName }}</div>
                    <div class="cell-sku">
                      {{ item.SKU }}
                      <span v-if="item.Unit" class="unit-pill">{{ unitLabel(item.Unit) }}</span>
                    </div>
                    <div v-if="item.VariantLabel" class="cell-sku">Size: {{ item.VariantLabel }}</div>
                  </td>
                  <td data-label="Category"><span class="category-pill">{{ item.CategoryName }}</span></td>
                  <td data-label="Type">
                    <div class="pill-stack">
                      <span class="status-pill" :class="'status-pill--' + item.type_pill.tone">
                        {{ item.type_pill.label }}
                      </span>
                      <span v-if="isLeftoverUsed(item)" class="status-pill status-pill--rem">
                        <i class="fa-solid fa-ruler-horizontal"></i> Leftover used
                      </span>
                      <span v-if="isNewProduct(item)" class="status-pill status-pill--new">New</span>
                      <span v-if="item.is_cut" class="status-pill status-pill--cut"><i class="fa-solid fa-scissors"></i> Cut</span>
                    </div>
                  </td>
                  <td data-label="Quantity" class="cell-qty" :data-order="num(item.quantity)">
                    <div class="qty-main" :class="'qty-main--' + item.qty_info.tone">{{ item.qty_info.main }}</div>
                    <div v-if="item.qty_info.caption" class="qty-caption">{{ item.qty_info.caption }}</div>
                    <dl v-if="item.qty_info.details.length" class="qty-details">
                      <div v-for="d in item.qty_info.details" :key="d.label" class="qty-row">
                        <dt>{{ d.label }}</dt>
                        <dd>{{ d.value }}</dd>
                      </div>
                    </dl>
                  </td>
                  <td data-label="Date" class="cell-date" :data-order="item.created_at" :title="item.created_at">
                    <div class="cell-date-day">{{ dateOnly(item.created_at) }}</div>
                    <div class="cell-date-time">{{ timeOnly(item.created_at) }}</div>
                  </td>
                  <td data-label="Notes" class="cell-muted cell-notes">{{ item.notes }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Breakdown modal (product total + one line per size) -->
      <div v-if="breakdown.open" class="il-modal-backdrop" @click.self="closeBreakdown">
        <div class="il-modal" role="dialog" aria-modal="true" :aria-label="breakdownTitle">
          <div class="il-modal-head">
            <div>
              <p class="card-eyebrow">{{ filterLabel }}</p>
              <h3 class="il-modal-title">{{ breakdownTitle }}</h3>
            </div>
            <button type="button" class="il-modal-close" @click="closeBreakdown" aria-label="Close">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="il-unit-chips" v-if="breakdownByUnit.length">
            <span v-for="u in breakdownByUnit" :key="u.unit" class="il-unit-chip"
              :class="breakdown.type === 'stock_in' ? 'il-unit-chip--in' : 'il-unit-chip--out'">{{ formatQty(u.total, u.unit) }}</span>
          </div>

          <div class="il-modal-body">
            <table class="il-break-table" v-if="breakdownRows.length">
              <thead>
                <tr><th>Product / Size</th><th>Movements</th><th>Quantity</th></tr>
              </thead>
              <tbody>
                <template v-for="r in breakdownRows" :key="r.key">
                  <!-- Product total -->
                  <tr :class="{ 'il-prod-row--has-sizes': r.variants.length }">
                    <td>
                      <div class="cell-product">{{ r.name }}</div>
                      <div class="cell-sku">{{ r.sku }}</div>
                    </td>
                    <td class="cell-muted">{{ r.count }}</td>
                    <td :class="breakdown.type === 'stock_in' ? 'cell-positive' : 'cell-negative'">
                      {{ breakdown.type === 'stock_in' ? '+' : '-' }}{{ formatQty(r.total, r.unit) }}
                    </td>
                  </tr>

                  <!-- One line per size -->
                  <tr v-for="v in r.variants" :key="r.key + '|' + v.key" class="il-var-row">
                    <td>
                      <div class="il-var-name">
                        <span class="il-var-dot"></span>
                        <span>{{ v.label }}</span>
                        <span v-if="v.price != null" class="il-var-price">{{ peso(v.price) }}</span>
                      </div>
                      <div v-if="v.cutCount" class="il-var-note">
                        <i class="fa-solid fa-scissors"></i> {{ v.cutCount }} cut sale{{ v.cutCount > 1 ? 's' : '' }}
                      </div>
                    </td>
                    <td class="cell-muted">{{ v.count }}</td>
                    <td :class="breakdown.type === 'stock_in' ? 'cell-positive' : 'cell-negative'">
                      {{ breakdown.type === 'stock_in' ? '+' : '-' }}{{ formatQty(v.total, r.unit) }}
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
            <p v-else class="il-empty">Nothing recorded for this period.</p>
          </div>
        </div>
      </div>

      <!-- ══════════════ PRINT-ONLY NOTES, SIGN-OFF & END ══════════════ -->
      <div class="print-only print-notes">
        <p class="pn-title">Notes &amp; approval</p>
        <ul>
          <li><b>Quantities</b> are recorded per movement. Cut sales also show the length sold, the leftover length and the full piece length.</li>
          <li><b>Leftover used</b> is counted as a stock out of that leftover piece.</li>
          <li><b>Extra services</b> are split between the worker's pass-through share and the owner's share.</li>
          <li><b>Times</b> are shown in local time (Asia/Manila).</li>
          <li v-if="isCurrentPeriod"><b>Partial figures.</b> This period is still in progress, so totals may change.</li>
        </ul>
        <div class="pn-sign">
          <div><span></span><p>Prepared by</p></div>
          <div><span></span><p>Reviewed by</p></div>
          <div><span></span><p>Date</p></div>
        </div>
      </div>

      <div class="print-only print-end">
        <span>Sheem Steel Construction Company · Inventory Logs · {{ filterLabel }} · Ref. {{ reportRef }}</span>
        <span>Generated {{ printGeneratedAt }}</span>
      </div>

    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, onBeforeUnmount } from 'vue'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const summary = ref({
  total_adjustments: 0,
  total_stock_in: 0,
  total_stock_out: 0,
  manual_adjustments: 0,
  cut_count: 0,
  service_count: 0,
  service_revenue: 0,
  worker_payout: 0,
  owner_share: 0,
  stock_in_by_unit: [],
  stock_out_by_unit: [],
})

// Animated copy of `summary` that drives the KPI cards on screen.
const summaryDisplay = ref({
  total_adjustments: 0,
  total_stock_in: 0,
  total_stock_out: 0,
  manual_adjustments: 0,
  cut_count: 0,
  service_revenue: 0,
})

const recentActivity      = ref([])
const topAdjustedProducts = ref([])
const inventoryLogs       = ref([])
const services            = ref([])

/* ════════════════════════════════════════════════════════════
   PERIOD FILTER + CALENDAR PICKER
════════════════════════════════════════════════════════════ */
const periodMode = ref('month') // 'month' | 'date'

const pad = (n) => String(n).padStart(2, '0')
function currentMonthValue() {
  const now = new Date()
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}`
}
function currentDateValue() {
  const now = new Date()
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`
}
const toDayStr = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
const toMonthStr = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}`

const todayDate  = new Date()
const CUR_MONTH  = currentMonthValue()
const CUR_DATE   = currentDateValue()
const CUR_YEAR   = todayDate.getFullYear()
const yearOptions = Array.from({ length: 12 }, (_, i) => CUR_YEAR - i) // newest first (clean 3 × 4 grid)
const MIN_YEAR   = yearOptions[yearOptions.length - 1]

const selectedMonth = ref(CUR_MONTH)
const selectedDate  = ref(CUR_DATE)
const isFiltering   = ref(false)

const currentTopLimit = ref(10)

// Report actions (Copy / CSV / Print): 'idle' | 'copying' | 'copied' | 'error'
const copyState = ref('idle')
const printGeneratedAt = ref('')

const periodNoun   = computed(() => (periodMode.value === 'month' ? 'month' : 'day'))
const currentLabel = computed(() => (periodMode.value === 'month' ? 'This month' : 'Today'))

const isCurrentPeriod = computed(() =>
  periodMode.value === 'month' ? selectedMonth.value >= CUR_MONTH : selectedDate.value >= CUR_DATE
)
const canGoNext = computed(() => !isCurrentPeriod.value)
const canGoPrev = computed(() =>
  periodMode.value === 'month' ? selectedMonth.value > `${MIN_YEAR}-01` : selectedDate.value > `${MIN_YEAR}-01-01`
)

/* ── calendar popover (days → months → years) ── */
const PK_WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']
const MONTH_NAMES = Array.from({ length: 12 }, (_, i) => new Date(2000, i, 1).toLocaleDateString('en-US', { month: 'long' }))
const MONTH_SHORT = Array.from({ length: 12 }, (_, i) => new Date(2000, i, 1).toLocaleDateString('en-US', { month: 'short' }))

const pickerOpen    = ref(false)
const pickerView    = ref('months') // 'days' | 'months' | 'years'
const browseYear    = ref(CUR_YEAR)
const browseMonth   = ref(todayDate.getMonth()) // 0-11
const pickerRoot    = ref(null)
const pickerTrigger = ref(null)

const pickerLabel = computed(() => {
  if (periodMode.value === 'month') {
    const [y, m] = (selectedMonth.value || CUR_MONTH).split('-').map(Number)
    return `${MONTH_NAMES[m - 1]} ${y}`
  }
  const d = new Date(`${selectedDate.value || CUR_DATE}T00:00:00`)
  return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
})

function openPicker() {
  if (periodMode.value === 'date') {
    const [y, m] = (selectedDate.value || CUR_DATE).split('-').map(Number)
    browseYear.value = y
    browseMonth.value = m - 1
    pickerView.value = 'days'
  } else {
    browseYear.value = Number((selectedMonth.value || CUR_MONTH).slice(0, 4))
    pickerView.value = 'months'
  }
  pickerOpen.value = true
}
function closePicker(refocus = false) {
  if (!pickerOpen.value) return
  pickerOpen.value = false
  if (refocus) nextTick(() => pickerTrigger.value && pickerTrigger.value.focus())
}
function togglePicker() { pickerOpen.value ? closePicker() : openPicker() }

function onDocPointer(e) {
  if (pickerOpen.value && pickerRoot.value && !pickerRoot.value.contains(e.target)) pickerOpen.value = false
}

const pkTitle = computed(() => {
  if (pickerView.value === 'days')   return `${MONTH_NAMES[browseMonth.value]} ${browseYear.value}`
  if (pickerView.value === 'months') return String(browseYear.value)
  return `${MIN_YEAR} – ${CUR_YEAR}`
})
const pkCanPrev = computed(() => {
  if (pickerView.value === 'days')   return browseYear.value * 12 + browseMonth.value > MIN_YEAR * 12
  if (pickerView.value === 'months') return browseYear.value > MIN_YEAR
  return false
})
const pkCanNext = computed(() => {
  if (pickerView.value === 'days')   return browseYear.value * 12 + browseMonth.value < CUR_YEAR * 12 + todayDate.getMonth()
  if (pickerView.value === 'months') return browseYear.value < CUR_YEAR
  return false
})
function pkShift(delta) {
  if (delta < 0 && !pkCanPrev.value) return
  if (delta > 0 && !pkCanNext.value) return
  if (pickerView.value === 'days') {
    const d = new Date(browseYear.value, browseMonth.value + delta, 1)
    browseYear.value = d.getFullYear()
    browseMonth.value = d.getMonth()
  } else if (pickerView.value === 'months') {
    browseYear.value += delta
  }
}
// Clicking the title zooms out: days → months → years.
function pkUp() {
  if (pickerView.value === 'days') pickerView.value = 'months'
  else if (pickerView.value === 'months') pickerView.value = 'years'
}

const pkDayCells = computed(() => {
  const y = browseYear.value
  const m = browseMonth.value
  const count = new Date(y, m + 1, 0).getDate()
  const lead = new Date(y, m, 1).getDay()
  const out = Array(lead).fill(null)
  for (let d = 1; d <= count; d++) {
    const key = `${y}-${pad(m + 1)}-${pad(d)}`
    out.push({
      key, d,
      title: new Date(y, m, d).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }),
      future: key > CUR_DATE,
      today: key === CUR_DATE,
      selected: periodMode.value === 'date' && key === selectedDate.value,
    })
  }
  return out
})
const pkMonthCells = computed(() =>
  MONTH_SHORT.map((label, m) => {
    const key = `${browseYear.value}-${pad(m + 1)}`
    const selected = periodMode.value === 'month'
      ? key === selectedMonth.value
      : (selectedDate.value || '').startsWith(key)
    return {
      key, m, label,
      title: `${MONTH_NAMES[m]} ${browseYear.value}`,
      future: key > CUR_MONTH,
      today: key === CUR_MONTH,
      selected,
    }
  })
)
const pkYearCells = computed(() =>
  [...yearOptions].reverse().map((y) => ({
    y,
    today: y === CUR_YEAR,
    selected: y === browseYear.value,
  }))
)

async function pkPickDay(key) {
  selectedDate.value = key
  selectedMonth.value = key.slice(0, 7)
  closePicker(true)
  await applyDateFilter()
}
async function pkPickMonth(m) {
  if (periodMode.value === 'date') {
    // Specific-date mode: choosing a month just opens its days.
    browseMonth.value = m
    pickerView.value = 'days'
    return
  }
  selectedMonth.value = `${browseYear.value}-${pad(m + 1)}`
  closePicker(true)
  await applyDateFilter()
}
function pkPickYear(y) {
  browseYear.value = y
  if (y === CUR_YEAR && browseMonth.value > todayDate.getMonth()) browseMonth.value = todayDate.getMonth()
  pickerView.value = 'months'
}
async function pkGoCurrent() {
  closePicker(true)
  await goCurrent()
}

async function shiftPeriod(delta) {
  if (isFiltering.value) return
  if (delta > 0 && !canGoNext.value) return
  if (delta < 0 && !canGoPrev.value) return

  if (periodMode.value === 'month') {
    const [yy, mm] = selectedMonth.value.split('-').map(Number)
    selectedMonth.value = toMonthStr(new Date(yy, mm - 1 + delta, 1))
  } else {
    const d = new Date(`${selectedDate.value}T00:00:00`)
    d.setDate(d.getDate() + delta)
    selectedDate.value = toDayStr(d)
    selectedMonth.value = selectedDate.value.slice(0, 7)
  }
  await applyDateFilter()
}

async function goCurrent() {
  selectedMonth.value = CUR_MONTH
  selectedDate.value = CUR_DATE
  await applyDateFilter()
}

/* ════════════════════════════════════════════════════════════
   FORMATTING HELPERS
════════════════════════════════════════════════════════════ */
function num(v) {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}
function formatNumber(v) {
  return Math.round(num(v)).toLocaleString()
}
const peso = v => '₱' + num(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const pct  = v => `${Number(num(v).toFixed(1))}%`
const receiptNo = id => `SR-${String(id).padStart(5, '0')}`
const lenText = n => num(n).toLocaleString('en-US', { maximumFractionDigits: 3 })

/* ── CUT PIECES (text form, used by Copy / CSV) ──
   "Cut 10 in from 18 in · 8 in left". Older cut sales were logged before the
   lengths were recorded, so they only say "Cut piece". */
function cutText(item) {
  if (!item?.is_cut) return ''
  if (item.cut_full_length == null || item.cut_sold_length == null) return 'Cut piece · lengths not recorded'
  const u = item.cut_unit ? ' ' + item.cut_unit : ''
  const left = item.cut_remaining_length ?? (num(item.cut_full_length) - num(item.cut_sold_length))
  return `Cut ${lenText(item.cut_sold_length)}${u} from ${lenText(item.cut_full_length)}${u} · ${lenText(left)}${u} left`
}

/* ── LEFTOVER LOG ROWS ──
   History rows written when a leftover is marked used / scrapped / put back
   (done on the Inventory page). The length + unit are stored on the row;
   older rows are parsed from the note. */
function isRemnantLog(item) {
  return String(item?.type ?? '').startsWith('remnant_')
}

function remnantParts(item) {
  let len  = item.cut_remaining_length
  let unit = item.cut_unit
  if (len == null || !unit) {
    const m = String(item.notes ?? '').match(/Leftover\s+([\d.,]+)\s*([^\s(]*)/)
    if (m) {
      if (len == null) len = m[1].replace(/,/g, '')
      if (!unit) unit = m[2]
    }
  }
  return { len, unit: unit || '' }
}

function remnantText(item) {
  if (!isRemnantLog(item)) return ''
  const { len, unit } = remnantParts(item)
  const src = item.SaleID ? ` · from ${receiptNo(item.SaleID)}` : ''
  if (len == null) return `Leftover piece${src}`
  return `${lenText(len)}${unit ? ' ' + unit : ''} leftover${src}`
}

/* ── QUANTITY CELL ──
   One structured object per log row so the table can show exact numbers:
   - normal rows:   "+50 pcs" / "-3 pcs"
   - cut sales:     "-1 pc" + Sold / Left over / Full piece lengths
   - leftover rows: "-11 in" (used) + source receipt                           */
function qtyInfo(item) {
  const tone = item.type === 'stock_in' ? 'in' : item.type === 'stock_out' ? 'out' : 'neutral'

  if (isRemnantLog(item)) {
    const kind = String(item.type).slice(8)
    const { len, unit } = remnantParts(item)
    const details = []
    if (item.SaleID) details.push({ label: 'From sale', value: receiptNo(item.SaleID) })
    // "Leftover used" is a stock out of that leftover, so it is shown as a minus.
    const sign = kind === 'used' ? '-' : ''
    return {
      tone: kind === 'used' || kind === 'scrapped' ? 'out' : kind === 'available' ? 'in' : 'neutral',
      main: len == null ? 'Leftover piece' : `${sign}${lenText(len)}${unit ? ' ' + unit : ''}`,
      caption: kind === 'used' ? 'Leftover marked used'
             : kind === 'scrapped' ? 'Leftover scrapped'
             : kind === 'available' ? 'Leftover put back' : 'Leftover update',
      details,
    }
  }

  const main = num(item.quantity) === 0
    ? '0'
    : formatQty(item.quantity, item.Unit, { signed: true, type: item.type })

  if (item.is_cut) {
    if (item.cut_full_length == null || item.cut_sold_length == null) {
      return { tone, main, caption: 'Cut sale', details: [{ label: 'Lengths', value: 'not recorded' }] }
    }
    const u = item.cut_unit ? ' ' + item.cut_unit : ''
    const left = item.cut_remaining_length ?? (num(item.cut_full_length) - num(item.cut_sold_length))
    return {
      tone,
      main,
      caption: 'Cut sale',
      details: [
        { label: 'Sold',       value: `${lenText(item.cut_sold_length)}${u}` },
        { label: 'Left over',  value: `${lenText(left)}${u}` },
        { label: 'Full piece', value: `${lenText(item.cut_full_length)}${u}` },
      ],
    }
  }

  return { tone, main, caption: '', details: [] }
}

// "Leftover used" counts as a stock out, so it is grouped under the Stock out type.
const isLeftoverUsed = (item) => item?.type === 'remnant_used'

function typePill(item) {
  if (isLeftoverUsed(item)) return { label: typeText('stock_out'), tone: 'out' }
  const tone = item.type === 'stock_in' ? 'in' : item.type === 'stock_out' ? 'out' : 'neutral'
  return { label: typeText(item.type), tone }
}

const withQty = (row) => ({ ...row, qty_info: qtyInfo(row), type_pill: typePill(row) })

/* ── EXTRA SERVICES totals ── */
const serviceTotals = computed(() => ({
  fee:    services.value.reduce((s, r) => s + num(r.Fee), 0),
  worker: services.value.reduce((s, r) => s + num(r.WorkerAmount), 0),
  owner:  services.value.reduce((s, r) => s + num(r.OwnerAmount), 0),
}))

/* ── UNITS ── */
const UNIT_LABELS = {
  pc: 'pcs', pcs: 'pcs', piece: 'pcs', pieces: 'pcs',
  kg: 'kg', kgs: 'kg', kilogram: 'kg', kilograms: 'kg',
  g: 'g', gram: 'g', grams: 'g',
  ton: 'ton', tons: 'ton', tonne: 'ton',
  m: 'm', meter: 'm', meters: 'm', metre: 'm', metres: 'm',
  cm: 'cm', mm: 'mm', ft: 'ft', feet: 'ft',
  l: 'L', liter: 'L', liters: 'L', litre: 'L', litres: 'L',
  box: 'box', boxes: 'box',
  bag: 'bag', bags: 'bag',
  roll: 'roll', rolls: 'roll',
  set: 'set', sets: 'set',
  sheet: 'sheet', sheets: 'sheet',
  bundle: 'bundle', bundles: 'bundle',
  pail: 'pail', pails: 'pail',
  can: 'can', cans: 'can',
}

function unitLabel(raw, qty = 2) {
  const key = String(raw ?? '').trim().toLowerCase()
  if (!key) return ''
  const label = UNIT_LABELS[key] ?? String(raw).trim()
  if (label === 'pcs' && Math.abs(num(qty)) === 1) return 'pc'
  return label
}

function formatQty(value, unit, { signed = false, type = null } = {}) {
  const n = num(value)
  const text = Math.abs(n).toLocaleString('en-US', { maximumFractionDigits: 3 })
  let sign = ''
  if (signed) {
    if (type === 'stock_in') sign = '+'
    else if (type === 'stock_out') sign = '-'
    else sign = n < 0 ? '-' : ''
  }
  const u = unitLabel(unit, n)
  return `${sign}${text}${u ? ' ' + u : ''}`
}

// Plain-text quantity (used by Copy). Leftover status changes move no whole pieces.
function logQty(item) {
  if (num(item.quantity) === 0) return '—'
  return formatQty(item.quantity, item.Unit, { signed: true, type: item.type })
}

/** "remnant_used" -> "Leftover used", "stock_out" -> "stock out" (CSS capitalises it). */
function typeText(t) {
  const v = String(t ?? '')
  return v.startsWith('remnant_') ? 'Leftover ' + v.slice(8) : v.replace(/_/g, ' ')
}

function hasUnitTotals(list) {
  return Array.isArray(list) && list.length > 0
}

function totalsText(list, fallbackTotal, sign) {
  if (hasUnitTotals(list)) {
    return list.map((u) => `${sign}${formatQty(u.total, u.unit)}`).join(', ')
  }
  return `${sign}${fallbackTotal}`
}

/* ── KPI BREAKDOWN (click Stock In / Stock Out) ──
   Each product shows its total, then one line per size (VariantLabel). */
const breakdown = ref({ open: false, type: 'stock_in' })

function openBreakdown(type) { breakdown.value = { open: true, type } }
function closeBreakdown() { breakdown.value.open = false }
function onEscKey(e) {
  if (e.key !== 'Escape') return
  if (pickerOpen.value) closePicker(true)
  else if (breakdown.value.open) closeBreakdown()
}

const breakdownTitle = computed(() =>
  breakdown.value.type === 'stock_in' ? 'Stock In breakdown' : 'Stock Out breakdown'
)

const breakdownRows = computed(() => {
  const map = new Map()

  inventoryLogs.value
    .filter((l) => l.type === breakdown.value.type)
    .forEach((l) => {
      const unit = unitLabel(l.Unit, 2)
      const key  = `${l.ProductID ?? l.ProductName}|${l.SKU}|${unit}`
      const qty  = Math.abs(num(l.quantity))

      if (!map.has(key)) {
        map.set(key, { key, name: l.ProductName, sku: l.SKU, unit, count: 0, total: 0, variants: new Map() })
      }
      const row = map.get(key)
      row.count += 1
      row.total += qty

      // Group by size. Logs without a size are not listed separately.
      const label = String(l.VariantLabel ?? '').trim()
      if (!label) return

      const vKey = `${l.VariantID ?? ''}|${label}`
      if (!row.variants.has(vKey)) {
        row.variants.set(vKey, { key: vKey, label, price: null, count: 0, total: 0, cutCount: 0 })
      }
      const v = row.variants.get(vKey)
      v.count += 1
      v.total += qty
      if (l.is_cut) v.cutCount += 1
      if (l.VariantPrice != null && v.price == null) v.price = l.VariantPrice
    })

  return [...map.values()]
    .map((r) => ({
      ...r,
      variants: [...r.variants.values()].sort((a, b) => b.total - a.total),
    }))
    .sort((a, b) => b.total - a.total)
})

const breakdownByUnit = computed(() => {
  const map = new Map()
  breakdownRows.value.forEach((r) => {
    map.set(r.unit, (map.get(r.unit) || 0) + r.total)
  })
  return [...map.entries()].map(([unit, total]) => ({ unit, total }))
})

function animateSummaryTo(target, duration = 750) {
  const start = performance.now()
  const from = { ...summaryDisplay.value }
  const keys = Object.keys(summaryDisplay.value)
  const tick = (now) => {
    const progress = Math.min((now - start) / duration, 1)
    const eased = 1 - Math.pow(1 - progress, 3)
    keys.forEach((key) => {
      const f = num(from[key])
      const t = num(target[key])
      summaryDisplay.value[key] = f + (t - f) * eased
    })
    if (progress < 1) {
      requestAnimationFrame(tick)
    } else {
      keys.forEach((key) => { summaryDisplay.value[key] = num(target[key]) })
    }
  }
  requestAnimationFrame(tick)
}

function computeRange() {
  if (periodMode.value === 'month') {
    const [year, month] = selectedMonth.value.split('-').map(Number)
    const from = `${selectedMonth.value}-01`
    const lastDay = new Date(year, month, 0).getDate()
    const to = `${selectedMonth.value}-${pad(lastDay)}`
    return { from, to }
  }
  return { from: selectedDate.value, to: selectedDate.value }
}

const filterLabel = computed(() => {
  if (periodMode.value === 'month') {
    if (!selectedMonth.value) return 'All Time'
    const [year, month] = selectedMonth.value.split('-').map(Number)
    return new Date(year, month - 1, 1).toLocaleString('en-US', { month: 'long', year: 'numeric' })
  }
  if (!selectedDate.value) return 'All Time'
  return new Date(`${selectedDate.value}T00:00:00`).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
})

/* ── PRINT: document reference + opening paragraph ── */
const reportRef = computed(() => {
  const key = periodMode.value === 'month'
    ? String(selectedMonth.value || '').replace('-', '')
    : String(selectedDate.value || '').replace(/-/g, '')
  return `IL-${periodMode.value === 'month' ? 'M' : 'D'}-${key}`
})

const execSummary = computed(() => {
  const s = summary.value
  const n = num(s.total_adjustments)
  if (!n && !services.value.length) {
    return `No inventory movements were recorded for ${filterLabel.value}.${isCurrentPeriod.value ? ' This period is still in progress.' : ''}`
  }
  const parts = [
    `For ${filterLabel.value}, ${formatNumber(n)} inventory ${n === 1 ? 'adjustment was' : 'adjustments were'} logged. Stock in totalled ${totalsText(s.stock_in_by_unit, formatNumber(s.total_stock_in), '+')} and stock out totalled ${totalsText(s.stock_out_by_unit, formatNumber(s.total_stock_out), '-')}.`,
  ]
  if (num(s.manual_adjustments)) parts.push(`${formatNumber(s.manual_adjustments)} ${num(s.manual_adjustments) === 1 ? 'was a manual adjustment' : 'were manual adjustments'}.`)
  if (num(s.cut_count)) parts.push(`${formatNumber(s.cut_count)} cut ${num(s.cut_count) === 1 ? 'piece was' : 'pieces were'} sold.`)
  if (num(s.service_revenue) > 0) parts.push(`Extra services brought in ${peso(s.service_revenue)} (owner share ${peso(s.owner_share)}, worker payout ${peso(s.worker_payout)}).`)
  const top = topAdjustedProducts.value[0]
  if (top) parts.push(`Most active product: ${top.ProductName} (${top.adjustment_count} ${num(top.adjustment_count) === 1 ? 'adjustment' : 'adjustments'}).`)
  if (isCurrentPeriod.value) parts.push('Figures are partial because the period is still in progress.')
  return parts.join(' ')
})

// ── DataTables instances ──
let dtRecent = null
let dtTop    = null
let dtLogs   = null

import 'https://code.jquery.com/jquery-3.5.1.min.js'
import 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js'

// A row logged when a product is first created (opening stock).
function isNewProduct(item) {
  return item?.notes === 'New product added'
}

// Backend sends naive local (Asia/Manila) datetime strings — parse the parts directly.
function parseLocal(raw) {
  const match = String(raw ?? '').match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/)
  if (!match) return null
  const [, year, month, day, hour, minute, second] = match.map(Number)
  return new Date(year, month - 1, day, hour, minute, second)
}

function formatLocalDate(raw) {
  if (!raw) return ''
  const d = parseLocal(raw)
  if (!d) return raw
  return d.toLocaleString('en-US', {
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
  })
}

// Split date / time so the Date column stays narrow ("Nov 6, 2026" over "3:45 PM").
function dateOnly(raw) {
  if (!raw) return ''
  const d = parseLocal(raw)
  return d ? d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : String(raw)
}
function timeOnly(raw) {
  const d = parseLocal(raw)
  return d ? d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : ''
}

async function loadInventoryLogs({ topLimit = 10, dateFromVal = null, dateToVal = null } = {}) {
  try {
    const res = await api.get('/api/inventory/logs', {
      params: {
        top_limit: topLimit,
        date_from: dateFromVal || undefined,
        date_to:   dateToVal   || undefined,
      },
    })
    summary.value = {
      stock_in_by_unit: [],
      stock_out_by_unit: [],
      ...res.data.summary,
    }
    recentActivity.value      = (res.data.recent_activity ?? []).map(withQty)
    topAdjustedProducts.value = res.data.top_adjusted_products
    inventoryLogs.value       = (res.data.inventory_logs ?? []).map(withQty)
    services.value            = res.data.services ?? []

    animateSummaryTo(summary.value)
  } catch (err) {
    console.error('Inventory logs API error:', err)
  }
}

/* ── report actions: Copy / CSV / Print ── */
function buildReportText() {
  const lines = []
  lines.push(`Inventory Logs — ${filterLabel.value}`)
  lines.push('')

  lines.push('Inventory Activity Summary')
  lines.push(`  Total Adjustments: ${summary.value.total_adjustments}`)
  lines.push(`  Total Stock In: ${totalsText(summary.value.stock_in_by_unit, summary.value.total_stock_in, '+')}`)
  lines.push(`  Total Stock Out: ${totalsText(summary.value.stock_out_by_unit, summary.value.total_stock_out, '-')}`)
  lines.push(`  Manual Adjustments: ${summary.value.manual_adjustments}`)
  lines.push(`  Cut Pieces Sold: ${summary.value.cut_count}`)
  lines.push(`  Service Fees: ${peso(summary.value.service_revenue)} (worker ${peso(summary.value.worker_payout)}, owner ${peso(summary.value.owner_share)})`)
  lines.push('')

  lines.push('Recent Activity')
  if (recentActivity.value.length) {
    recentActivity.value.forEach((item) => {
      lines.push(
        `  ${item.ProductName} (${item.SKU})${item.VariantLabel ? ' – ' + item.VariantLabel : ''} — ${item.type}${isNewProduct(item) ? ' [New]' : ''}${item.is_cut ? ' [' + cutText(item) + ']' : ''}${isRemnantLog(item) ? ' [' + remnantText(item) + ']' : ''}, qty ${logQty(item)}, ${formatLocalDate(item.created_at)}${item.notes ? ` — ${item.notes}` : ''}`
      )
    })
  } else {
    lines.push('  No recent activity recorded.')
  }
  lines.push('')

  lines.push('Top Adjusted Products')
  if (topAdjustedProducts.value.length) {
    topAdjustedProducts.value.forEach((item) => {
      lines.push(
        `  ${item.ProductName} (${item.SKU}) — ${item.adjustment_count} adjustments, +${formatQty(item.total_stock_in, item.Unit)} in / -${formatQty(item.total_stock_out, item.Unit)} out`
      )
    })
  } else {
    lines.push('  No adjustment data recorded.')
  }
  lines.push('')

  lines.push('Extra Services')
  if (services.value.length) {
    services.value.forEach((s) => {
      lines.push(
        `  ${receiptNo(s.SaleID)} — ${s.Description}: ${peso(s.Fee)} | worker ${s.WorkerName} ${peso(s.WorkerAmount)} (${pct(s.WorkerPct)}) | owner ${peso(s.OwnerAmount)} (${pct(s.OwnerPct)})`
      )
    })
  } else {
    lines.push('  No extra services recorded.')
  }
  lines.push('')

  lines.push('Full Inventory Logs')
  if (inventoryLogs.value.length) {
    inventoryLogs.value.forEach((item) => {
      lines.push(
        `  ${item.ProductName} (${item.SKU})${item.VariantLabel ? ' – ' + item.VariantLabel : ''} — ${item.CategoryName}, ${item.type}${isNewProduct(item) ? ' [New]' : ''}${item.is_cut ? ' [' + cutText(item) + ']' : ''}${isRemnantLog(item) ? ' [' + remnantText(item) + ']' : ''}, qty ${logQty(item)}, ${formatLocalDate(item.created_at)}${item.notes ? ` — ${item.notes}` : ''}`
      )
    })
  } else {
    lines.push('  No logs recorded.')
  }

  return lines.join('\n')
}

function csvEscape(value) {
  const s = String(value ?? '')
  return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s
}

function buildReportCSV() {
  const rows = []
  rows.push(['Inventory Logs', filterLabel.value])
  rows.push([])

  rows.push(['Inventory Activity Summary'])
  rows.push(['Total Adjustments', summary.value.total_adjustments])
  if (hasUnitTotals(summary.value.stock_in_by_unit)) {
    summary.value.stock_in_by_unit.forEach((u) => rows.push(['Total Stock In', u.total, unitLabel(u.unit)]))
  } else {
    rows.push(['Total Stock In', summary.value.total_stock_in])
  }
  if (hasUnitTotals(summary.value.stock_out_by_unit)) {
    summary.value.stock_out_by_unit.forEach((u) => rows.push(['Total Stock Out', u.total, unitLabel(u.unit)]))
  } else {
    rows.push(['Total Stock Out', summary.value.total_stock_out])
  }
  rows.push(['Manual Adjustments', summary.value.manual_adjustments])
  rows.push(['Cut Pieces Sold', summary.value.cut_count])
  rows.push(['Service Fees', summary.value.service_revenue])
  rows.push(['Worker Payout', summary.value.worker_payout])
  rows.push(['Owner Share', summary.value.owner_share])
  rows.push([])

  rows.push(['Recent Activity'])
  rows.push(['Product', 'SKU', 'Size', 'Type', 'New', 'Quantity', 'Unit', 'Cut', 'Leftover', 'Date', 'Notes'])
  recentActivity.value.forEach((item) => {
    rows.push([item.ProductName, item.SKU, item.VariantLabel || '', item.type, isNewProduct(item) ? 'Yes' : '', item.quantity, unitLabel(item.Unit, item.quantity), cutText(item), remnantText(item), formatLocalDate(item.created_at), item.notes || ''])
  })
  rows.push([])

  rows.push(['Top Adjusted Products'])
  rows.push(['Product', 'SKU', 'Unit', 'Adjustments', 'Total Stock In', 'Total Stock Out'])
  topAdjustedProducts.value.forEach((item) => {
    rows.push([item.ProductName, item.SKU, unitLabel(item.Unit), item.adjustment_count, item.total_stock_in, item.total_stock_out])
  })
  rows.push([])

  rows.push(['Extra Services'])
  rows.push(['Date', 'Receipt', 'Service', 'Fee', 'Worker', 'Worker Amount', 'Worker %', 'Owner Amount', 'Owner %'])
  services.value.forEach((s) => {
    rows.push([s.SaleDate, receiptNo(s.SaleID), s.Description, s.Fee, s.WorkerName, s.WorkerAmount, s.WorkerPct, s.OwnerAmount, s.OwnerPct])
  })
  rows.push([])

  rows.push(['Full Inventory Logs'])
  rows.push(['Product', 'SKU', 'Size', 'Category', 'Type', 'New', 'Quantity', 'Unit', 'Cut', 'Leftover', 'Date', 'Notes'])
  inventoryLogs.value.forEach((item) => {
    rows.push([item.ProductName, item.SKU, item.VariantLabel || '', item.CategoryName, item.type, isNewProduct(item) ? 'Yes' : '', item.quantity, unitLabel(item.Unit, item.quantity), cutText(item), remnantText(item), formatLocalDate(item.created_at), item.notes || ''])
  })

  return rows.map((r) => r.map(csvEscape).join(',')).join('\n')
}

function reportFileSlug() {
  const base = filterLabel.value || (periodMode.value === 'month' ? selectedMonth.value : selectedDate.value)
  return `inventory-logs-${String(base).replace(/[^a-z0-9]+/gi, '-').toLowerCase()}`
}

async function copyReport() {
  if (copyState.value === 'copying') return
  copyState.value = 'copying'
  try {
    const text = buildReportText()
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(text)
    } else {
      const textarea = document.createElement('textarea')
      textarea.value = text
      textarea.style.position = 'fixed'
      textarea.style.opacity = '0'
      document.body.appendChild(textarea)
      textarea.select()
      document.execCommand('copy')
      document.body.removeChild(textarea)
    }
    copyState.value = 'copied'
  } catch (err) {
    console.error('Copy report failed:', err)
    copyState.value = 'error'
  } finally {
    setTimeout(() => { copyState.value = 'idle' }, 1800)
  }
}

function exportCSV() {
  try {
    const csv = buildReportCSV()
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${reportFileSlug()}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('CSV export failed:', err)
  }
}

/* ── PRINT ──
   The Full History table is paginated on screen (25 rows). For paper it is
   switched to "show all" first so nothing is cut off, then put back after
   the print dialog closes. */
let printRestoreLen = null

async function printReport() {
  closePicker()
  closeBreakdown()
  printGeneratedAt.value = new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })

  if (dtLogs) {
    printRestoreLen = dtLogs.page.len()
    dtLogs.page.len(-1).draw(false)
  }
  await nextTick()
  // One extra frame so the expanded rows are painted before the dialog opens.
  await new Promise((r) => requestAnimationFrame(r))
  window.print()
}

function restoreAfterPrint() {
  if (dtLogs && printRestoreLen) dtLogs.page.len(printRestoreLen).draw(false)
  printRestoreLen = null
}

/* ────────────────────────────────────────────────────────────
   DataTables + Vue: never patch a live DataTable against
   Vue-rendered rows. Destroy BEFORE data changes, let Vue
   re-render, then create fresh instances.

   autoWidth is off so the column widths set in the template
   are respected and the tables fit without sideways scrolling.
──────────────────────────────────────────────────────────── */
function initRecentTable() {
  dtRecent = $('#recentActivityTable').DataTable({
    dom: '<"inv-dt-toolbar inv-dt-toolbar--right"lf>rtip',
    order: [],
    pageLength: 25,
    autoWidth: false,
  })
}

function initTopTable() {
  dtTop = $('#topAdjustedTable').DataTable({
    dom: '<"inv-dt-toolbar inv-dt-toolbar--right"lf>rtip',
    order: [],
    pageLength: 25,
    autoWidth: false,
  })

  dtTop.on('length.dt', async (e, settings, len) => {
    currentTopLimit.value = len
    const { from, to } = computeRange()

    dtTop.destroy()
    dtTop = null

    await loadInventoryLogs({ topLimit: len, dateFromVal: from, dateToVal: to })
    await nextTick()
    initTopTable()
  })
}

function initLogsTable() {
  dtLogs = $('#inventoryLogsTable').DataTable({
    dom: '<"inv-dt-toolbar inv-dt-toolbar--right"lf>rtip',
    pageLength: 25,
    order: [],
    autoWidth: false,
  })
}

function initAllTables() {
  initRecentTable()
  initTopTable()
  initLogsTable()
}

function destroyAllTables() {
  if (dtRecent) { dtRecent.destroy(); dtRecent = null }
  if (dtTop)    { dtTop.destroy();    dtTop = null }
  if (dtLogs)   { dtLogs.destroy();   dtLogs = null }
}

async function applyDateFilter() {
  if (isFiltering.value) return
  isFiltering.value = true
  try {
    destroyAllTables()

    const { from, to } = computeRange()
    await loadInventoryLogs({
      topLimit:    currentTopLimit.value,
      dateFromVal: from,
      dateToVal:   to,
    })

    await nextTick()
    initAllTables()
  } finally {
    isFiltering.value = false
  }
}

// Keeps both pickers on the same moment when switching By Month / Specific Date.
function setPeriodMode(mode) {
  if (periodMode.value === mode) return
  closePicker()
  if (mode === 'month') {
    selectedMonth.value = (selectedDate.value || CUR_DATE).slice(0, 7)
  } else {
    selectedDate.value = selectedMonth.value === CUR_MONTH ? CUR_DATE : `${selectedMonth.value}-01`
  }
  periodMode.value = mode
  applyDateFilter()
}

onMounted(async () => {
  window.addEventListener('keydown', onEscKey)
  window.addEventListener('afterprint', restoreAfterPrint)
  document.addEventListener('pointerdown', onDocPointer)
  const { from, to } = computeRange()
  await loadInventoryLogs({ dateFromVal: from, dateToVal: to })
  await nextTick()

  setTimeout(() => {
    initAllTables()
  }, 200)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onEscKey)
  window.removeEventListener('afterprint', restoreAfterPrint)
  document.removeEventListener('pointerdown', onDocPointer)
  destroyAllTables()
})
</script>

<!-- ─── SCOPED: page layout & component styles ──────────── -->
<style scoped>
@import url('https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css');

.inv-logs {
  --il-bg:             var(--c-bg);
  --il-surface:        var(--c-surface);
  --il-surface-raised: var(--c-surface-raised);
  --il-surface-sunken: var(--c-surface-sunken);
  --il-border:         var(--c-border);
  --il-border-strong:  var(--c-border-strong);
  --il-text-primary:   var(--c-text-primary);
  --il-text-secondary: var(--c-text-secondary);
  --il-text-muted:     var(--c-text-muted);
  --il-text-faint:     var(--c-text-faint);
  --il-accent:         var(--c-accent);
  --il-accent-soft:    var(--c-accent-soft);
  --il-accent-border:  var(--c-accent-border);
  --il-shadow:         var(--c-shadow-sm);
  --il-shadow-lg:      var(--c-shadow-md);

  --il-green:          #10b981;
  --il-blue:           #3b82f6;
  --il-violet:         #6366f1;
  --il-red:            #f43f5e;
  --il-amber:          #B45309;

  --il-green-icon:     #D1FAE5;
  --il-blue-icon:      #DBEAFE;
  --il-violet-icon:    #E0E7FF;
  --il-red-icon:       #FFE4E6;

  --radius: 16px;
  --il-ease: cubic-bezier(.16, 1, .3, 1);

  min-height: 100%;
  background: var(--il-bg);
  padding: 28px 28px 56px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex;
  flex-direction: column;
  gap: 28px;
  transition: background-color 0.22s ease;
}

html[data-theme="dark"] .inv-logs {
  --il-amber:        #FCD34D;
  --il-green-icon:   rgba(16,185,129,0.18);
  --il-blue-icon:    rgba(59,130,246,0.18);
  --il-violet-icon:  rgba(99,102,241,0.18);
  --il-red-icon:     rgba(244,63,94,0.18);
}

.inv-logs button:focus-visible,
.inv-logs a:focus-visible { outline: 2px solid var(--il-accent); outline-offset: 2px; }

/* ── PAGE HEADER ── */
.page-header { position: relative; z-index: 5; display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px; }
.page-eyebrow {
  font-size: 11px; font-weight: 700; letter-spacing: .12em;
  color: var(--il-accent); text-transform: uppercase; margin: 0 0 5px;
}
.page-title {
  font-size: 26px; font-weight: 800;
  color: var(--il-text-primary);
  letter-spacing: -.03em; margin: 0;
  transition: color 0.22s ease;
}
.page-sub { margin: 4px 0 0; font-size: 13px; color: var(--il-text-muted); }
.page-sub strong { color: var(--il-text-secondary); font-weight: 700; }

/* ── REPORT ACTIONS ── */
.report-toolbar {
  display: flex; align-items: center; justify-content: flex-end;
  gap: 10px; flex-wrap: wrap;
}
.rt-btn {
  display: flex; align-items: center; gap: 8px;
  border: 1px solid var(--il-border-strong);
  background: var(--il-surface-sunken);
  color: var(--il-text-secondary);
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 13px; font-weight: 600;
  padding: 9px 16px; border-radius: 11px; cursor: pointer;
  transition: background-color .16s ease, border-color .16s ease, color .16s ease, transform .12s ease, box-shadow .16s ease;
}
.rt-btn i { font-size: 12px; }
.rt-btn:hover:not(:disabled) {
  background: var(--il-surface-raised);
  border-color: var(--il-accent-border);
  color: var(--il-text-primary);
  transform: translateY(-1px);
}
.rt-btn:disabled { opacity: .65; cursor: default; }
.rt-btn--primary {
  background: var(--il-accent); border-color: var(--il-accent); color: #fff;
  box-shadow: 0 2px 8px rgba(234,88,12,0.28);
}
.rt-btn--primary:hover { background: #FB923C; border-color: #FB923C; color: #fff; }

/* ── PERIOD CONTROL ── */
.period-control { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.period-mode-toggle {
  display: inline-flex; align-items: center;
  background: var(--il-surface-sunken);
  border: 1px solid var(--il-border);
  border-radius: 12px; padding: 3px; gap: 2px;
}
.period-mode-seg {
  display: inline-flex; align-items: center; gap: 6px;
  border: none; background: transparent;
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 12.5px; font-weight: 600;
  color: var(--il-text-muted);
  padding: 8px 16px; border-radius: 9px; cursor: pointer;
  transition: background-color .18s ease, color .18s ease;
  white-space: nowrap;
}
.period-mode-seg:hover { color: var(--il-text-secondary); }
.period-mode-seg--active { background: var(--il-accent); color: #fff; }
.period-mode-seg--active:hover { color: #fff; }

.period-nav { display: flex; align-items: center; gap: 6px; }
.icon-btn {
  width: 36px; height: 36px; border-radius: 10px; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  border: 1px solid var(--il-border-strong);
  background: var(--il-surface-sunken);
  color: var(--il-text-secondary);
  font-size: 12px;
  transition: background-color .16s ease, border-color .16s ease, color .16s ease;
}
.icon-btn:hover:not(:disabled) { border-color: var(--il-accent-border); color: var(--il-text-primary); background: var(--il-surface-raised); }
.icon-btn:disabled { opacity: .4; cursor: not-allowed; }

.link-btn {
  border: none; background: transparent; cursor: pointer;
  font-family: inherit; font-size: 13px; font-weight: 700;
  color: var(--il-accent); padding: 6px 8px; border-radius: 8px;
}
.link-btn:hover:not(:disabled) { background: var(--il-accent-soft); }
.link-btn:disabled { opacity: .5; cursor: default; }

.date-filter-loading { font-size: 12px; color: var(--il-accent); display: flex; align-items: center; gap: 6px; }

/* ── CALENDAR PICKER ── */
.picker { position: relative; }
.picker-trigger {
  display: inline-flex; align-items: center; gap: 10px;
  height: 36px; min-width: 200px; padding: 0 12px 0 13px;
  border: 1.5px solid var(--il-border-strong);
  background: var(--il-surface);
  border-radius: 11px;
  font-family: inherit; font-size: 13.5px; font-weight: 700;
  color: var(--il-text-primary);
  cursor: pointer;
  transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
}
.picker-trigger > span { flex: 1; text-align: left; white-space: nowrap; }
.picker-ico { font-size: 13px; color: var(--il-accent); }
.picker-caret { font-size: 10px; color: var(--il-text-muted); transition: transform .22s var(--il-ease); }
.picker-trigger:hover { border-color: var(--il-accent-border); background: var(--il-surface-raised); }
.picker-trigger--open { border-color: var(--il-accent); box-shadow: 0 0 0 3px var(--il-accent-soft); }
.picker-trigger--open .picker-caret { transform: rotate(180deg); }

.picker-pop {
  position: absolute; top: calc(100% + 8px); right: 0; z-index: 40;
  width: 300px; max-width: calc(100vw - 32px);
  padding: 14px;
  background: var(--il-surface);
  border: 1px solid var(--il-border-strong);
  border-radius: 18px;
  box-shadow: 0 28px 56px -18px rgba(0,0,0,.5), 0 4px 14px rgba(0,0,0,.14);
  transform-origin: top right;
}
.pop-enter-active, .pop-leave-active { transition: opacity .16s ease, transform .2s var(--il-ease); }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateY(-6px) scale(.97); }

.pk-head { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 12px; }
.pk-nav {
  width: 32px; height: 32px; flex-shrink: 0; border-radius: 10px; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  border: 1px solid transparent; background: var(--il-surface-sunken);
  color: var(--il-text-secondary); font-size: 11px;
  transition: background-color .15s ease, color .15s ease, border-color .15s ease;
}
.pk-nav:hover:not(:disabled) { background: var(--il-accent-soft); color: var(--il-accent); border-color: var(--il-accent-border); }
.pk-nav:disabled { opacity: .35; cursor: not-allowed; }
.pk-nav--hide { visibility: hidden; }
.pk-title {
  flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  height: 32px; border: none; border-radius: 10px; background: transparent; cursor: pointer;
  font: inherit; font-size: 14px; font-weight: 800; letter-spacing: -.01em;
  color: var(--il-text-primary);
  transition: background-color .15s ease;
}
.pk-title i { font-size: 9px; color: var(--il-text-muted); }
.pk-title:hover:not(:disabled) { background: var(--il-surface-raised); }
.pk-title:disabled { cursor: default; }

.pk-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 3px; }
.pk-dow { text-align: center; padding: 4px 0 6px; font-size: 10.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--il-text-faint); }
.pk-blank { display: block; }
.pk-day {
  position: relative; aspect-ratio: 1; min-width: 0; padding: 0;
  border: 1px solid transparent; border-radius: 10px; background: transparent; cursor: pointer;
  font: inherit; font-size: 13px; font-weight: 600; font-variant-numeric: tabular-nums;
  color: var(--il-text-primary);
  transition: background-color .14s ease, color .14s ease, border-color .14s ease, transform .14s var(--il-ease);
}
.pk-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; }
.pk-cell {
  position: relative; height: 46px; padding: 0;
  border: 1px solid transparent; border-radius: 12px; background: transparent; cursor: pointer;
  font: inherit; font-size: 13.5px; font-weight: 700; font-variant-numeric: tabular-nums;
  color: var(--il-text-primary);
  transition: background-color .14s ease, color .14s ease, border-color .14s ease, transform .14s var(--il-ease);
}
.pk-day:hover:not(:disabled):not(.pk-on),
.pk-cell:hover:not(:disabled):not(.pk-on) { background: var(--il-accent-soft); color: var(--il-accent); transform: scale(1.05); }
.pk-day:disabled, .pk-cell:disabled { opacity: .28; cursor: not-allowed; }
.pk-today { border-color: var(--il-accent-border); color: var(--il-accent); }
.pk-today::after {
  content: ''; position: absolute; bottom: 5px; left: 50%; width: 4px; height: 4px; margin-left: -2px;
  border-radius: 50%; background: var(--il-accent);
}
.pk-on { background: var(--il-accent); border-color: var(--il-accent); color: #fff; box-shadow: 0 6px 14px -6px var(--il-accent); }
.pk-on::after { background: #fff; }

.pk-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--il-border); }
.pk-link {
  display: inline-flex; align-items: center; gap: 7px; border: none; background: transparent; cursor: pointer;
  font: inherit; font-size: 12.5px; font-weight: 700; color: var(--il-accent);
  padding: 6px 9px; border-radius: 8px;
  transition: background-color .15s ease;
}
.pk-link i { font-size: 11px; }
.pk-link:hover:not(:disabled) { background: var(--il-accent-soft); }
.pk-link:disabled { opacity: .4; cursor: default; }
.pk-link--muted { color: var(--il-text-muted); }
.pk-link--muted:hover:not(:disabled) { background: var(--il-surface-raised); color: var(--il-text-primary); }

/* ── SECTION ── */
.section        { display: flex; flex-direction: column; gap: 14px; }
.section-header { display: flex; flex-direction: column; }
.section-title  {
  font-size: 17px; font-weight: 700; color: var(--il-text-primary);
  margin: 0; letter-spacing: -.01em; transition: color 0.22s ease;
}
.card-eyebrow {
  font-size: 10.5px; font-weight: 700; letter-spacing: .12em;
  color: var(--il-accent); text-transform: uppercase; margin: 0 0 4px;
}

/* ── KPI STRIP ── */
.kpi-strip { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; }

.kpi-card {
  background: var(--il-surface);
  border-radius: var(--radius);
  border: 1px solid var(--il-border);
  box-shadow: var(--il-shadow);
  padding: 20px 20px 16px;
  position: relative; overflow: hidden;
  transition: transform .2s ease, box-shadow .2s ease, background-color 0.22s ease, border-color 0.22s ease;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: var(--il-shadow-lg); }

.kpi-card--featured { background: var(--il-accent-soft); border-color: var(--il-accent-border); }
.kpi-card--alert { background: rgba(244,63,94,0.08); border-color: rgba(244,63,94,0.25); }
html[data-theme="dark"] .kpi-card--alert { background: rgba(244,63,94,0.10); border-color: rgba(244,63,94,0.28); }

.kpi-card-inner { display: flex; align-items: flex-start; gap: 12px; }

.kpi-icon-wrap {
  width: 40px; height: 40px; border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; flex-shrink: 0;
  transition: background-color 0.22s ease;
}
.kpi-icon-wrap--orange { background: var(--il-accent-soft);  color: var(--il-accent); }
.kpi-icon-wrap--green  { background: var(--il-green-icon);   color: var(--il-green); }
.kpi-icon-wrap--blue   { background: var(--il-blue-icon);    color: var(--il-blue); }
.kpi-icon-wrap--violet { background: var(--il-violet-icon);  color: var(--il-violet); }
.kpi-icon-wrap--red    { background: var(--il-red-icon);     color: var(--il-red); }

.kpi-body  { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.kpi-label {
  font-size: 11px; font-weight: 600; color: var(--il-text-muted);
  text-transform: uppercase; letter-spacing: .07em;
}
.kpi-value {
  font-size: 23px; font-weight: 800; color: var(--il-text-primary);
  letter-spacing: -.04em; line-height: 1.1; margin: 0;
  transition: color 0.22s ease;
}
.kpi-value--green  { color: var(--il-green); }
.kpi-value--blue   { color: var(--il-blue); }
.kpi-value--violet { color: var(--il-violet); }
.kpi-value--red    { color: var(--il-red); }

.kpi-accent-bar {
  position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
  border-radius: 0 0 var(--radius) var(--radius);
}
.kpi-accent-bar--orange { background: linear-gradient(90deg, var(--il-accent), #FB923C); }
.kpi-accent-bar--green  { background: var(--il-green); }
.kpi-accent-bar--blue   { background: var(--il-blue); }
.kpi-accent-bar--violet { background: var(--il-violet); }
.kpi-accent-bar--red    { background: var(--il-red); }

.kpi-card--clickable { cursor: pointer; }
.kpi-hint {
  margin: 2px 0 0; font-size: 11px; font-weight: 600;
  color: var(--il-text-faint); transition: color .16s ease;
}
.kpi-card--clickable:hover .kpi-hint { color: var(--il-text-secondary); }

/* ── BREAKDOWN MODAL ── */
.il-modal-backdrop {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(8,10,16,.55);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.il-modal {
  width: min(640px, 100%);
  max-height: min(80vh, 640px);
  display: flex; flex-direction: column; gap: 14px;
  background: var(--il-surface);
  border: 1px solid var(--il-border);
  border-radius: var(--radius);
  box-shadow: var(--il-shadow-lg);
  padding: 22px 24px 18px;
}
.il-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.il-modal-title { margin: 0; font-size: 17px; font-weight: 700; color: var(--il-text-primary); letter-spacing: -.01em; }
.il-modal-close {
  border: 1px solid var(--il-border-strong);
  background: var(--il-surface-sunken);
  color: var(--il-text-secondary);
  width: 32px; height: 32px; border-radius: 9px; cursor: pointer;
}
.il-modal-close:hover { color: var(--il-text-primary); border-color: var(--il-accent-border); }

.il-unit-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.il-unit-chip { font-size: 12.5px; font-weight: 700; border-radius: 20px; padding: 4px 12px; }
.il-unit-chip--in  { background: var(--il-green-icon); color: var(--il-green); }
.il-unit-chip--out { background: var(--il-red-icon);   color: var(--il-red); }

.il-modal-body { overflow-y: auto; min-height: 0; }
.il-break-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.il-break-table th {
  text-align: left; padding: 8px 12px;
  font-size: 11px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
  color: var(--il-text-muted);
  border-bottom: 1px solid var(--il-border);
  position: sticky; top: 0; background: var(--il-surface);
}
.il-break-table td { padding: 10px 12px; border-bottom: 1px solid var(--il-border); }
.il-break-table tbody tr:last-child td { border-bottom: none; }
.il-empty { color: var(--il-text-muted); font-size: 13.5px; margin: 8px 0; text-align: center; }

/* ── BREAKDOWN: per-size lines ── */
.il-break-table .il-prod-row--has-sizes td { border-bottom: none; padding-bottom: 4px; }

.il-var-row td {
  padding: 6px 12px 6px 28px;
  font-size: 13px;
  background: var(--il-surface-sunken);
  border-bottom: 1px solid var(--il-border);
}
.il-var-name {
  display: flex; align-items: center; gap: 8px;
  font-weight: 600; color: var(--il-text-secondary);
}
.il-var-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--il-accent); flex-shrink: 0;
}
.il-var-price {
  font-size: 11px; font-weight: 700;
  padding: 1px 8px; border-radius: 20px;
  background: var(--il-accent-soft); color: var(--il-accent);
}
.il-var-note {
  margin: 2px 0 0 14px; font-size: 11px; color: var(--il-text-faint);
}
.il-var-note i { font-size: 9.5px; margin-right: 3px; }

/* ── CARDS ── */
.card {
  background: var(--il-surface);
  border-radius: var(--radius);
  border: 1px solid var(--il-border);
  box-shadow: var(--il-shadow);
  padding: 22px 24px 20px;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.card-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 18px; gap: 12px; flex-wrap: wrap;
}
.card-title {
  font-size: 15.5px; font-weight: 700; color: var(--il-text-primary);
  margin: 0; letter-spacing: -.01em; transition: color 0.22s ease;
}
.card-sub {
  margin: 6px 0 0; max-width: 620px;
  font-size: 12.5px; line-height: 1.5; color: var(--il-text-muted);
}

/* ── TABLE ──
   Tables use a fixed layout with percentage column widths and wrapping
   text, so every column is visible without sideways scrolling. */
.table-wrap { overflow-x: auto; }

.data-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13.5px; }
.data-table--fixed { table-layout: fixed; }

.data-table tbody td {
  padding: 12px 14px;
  vertical-align: top;
  color: var(--il-text-secondary);
  border-bottom: 1px solid var(--il-border);
  white-space: normal;
  overflow-wrap: anywhere;
  transition: color 0.22s ease, border-color 0.22s ease;
}
.data-table tbody tr:hover td   { background: var(--il-surface-raised); }
.data-table tbody tr:last-child td { border-bottom: none; }

.data-table tbody td.cell-notes { font-size: 12.5px; line-height: 1.5; }

/* plain (non-DataTables) table needs its own header styling */
.data-table--plain thead th {
  background: var(--il-surface-raised); color: var(--il-text-muted);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  text-align: left; padding: 12px 14px; border-bottom: 1px solid var(--il-border);
}

.cell-product  { font-weight: 600; color: var(--il-text-primary); font-size: 13.5px; transition: color 0.22s ease; }
.cell-sku      { font-size: 11px; color: var(--il-text-faint); margin-top: 2px; }
.cell-muted    { color: var(--il-text-secondary); }
.cell-faint    { color: var(--il-text-muted); font-size: 12.5px; }
.cell-positive { color: var(--il-green); font-weight: 700; }
.cell-negative { color: var(--il-red);   font-weight: 700; }

.cell-date-day  { font-size: 12.5px; font-weight: 600; color: var(--il-text-secondary); }
.cell-date-time { font-size: 11.5px; color: var(--il-text-muted); margin-top: 2px; }

.svc-total td {
  font-weight: 800; color: var(--il-text-primary);
  padding: 12px 14px; border-top: 1px solid var(--il-border-strong);
}

/* ── QUANTITY CELL ── */
.qty-main {
  font-size: 15px; font-weight: 800; letter-spacing: -.02em; line-height: 1.2;
  color: var(--il-text-primary);
}
.qty-main--in      { color: var(--il-green); }
.qty-main--out     { color: var(--il-red); }
.qty-main--neutral { color: var(--il-amber); }
.qty-caption {
  margin-top: 2px; font-size: 11px; font-weight: 600; color: var(--il-text-faint);
}
.qty-details {
  margin: 8px 0 0; padding: 6px 10px;
  display: flex; flex-direction: column; gap: 3px;
  background: var(--il-surface-sunken);
  border-left: 3px solid var(--il-accent-border);
  border-radius: 0 8px 8px 0;
}
.qty-row { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; font-size: 12px; }
.qty-row dt { margin: 0; font-weight: 600; color: var(--il-text-muted); }
.qty-row dd { margin: 0; font-weight: 700; color: var(--il-text-primary); white-space: nowrap; }

/* ── UNIT PILL ── */
.unit-pill {
  display: inline-flex; align-items: center;
  margin-left: 6px; padding: 1px 7px;
  font-size: 10px; font-weight: 700; letter-spacing: .02em;
  border-radius: 20px; background: #F5F5F4; color: #57534E; vertical-align: 1px;
}
html[data-theme="dark"] .unit-pill { background: rgba(255,255,255,0.08); color: #C4C8D6; }

/* ── STATUS PILLS ── */
.pill-stack { display: flex; flex-wrap: wrap; gap: 6px; align-items: flex-start; }
.status-pill {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 700;
  text-transform: capitalize; letter-spacing: .02em;
  border-radius: 20px; padding: 4px 11px;
  white-space: nowrap;
}
.status-pill i { font-size: 9.5px; }
.status-pill--in      { background: #D1FAE5; color: #047857; }
.status-pill--out     { background: #FFE4E6; color: #BE123C; }
.status-pill--neutral { background: #FEF3C7; color: #92400E; }
.status-pill--new     { background: #FEF3C7; color: #92400E; text-transform: none; }
.status-pill--cut     { background: #FFEDD5; color: #C2410C; text-transform: none; }
.status-pill--rem     { background: #DBEAFE; color: #1D4ED8; text-transform: none; }

html[data-theme="dark"] .status-pill--in      { background: rgba(16,185,129,0.16); color: #4ADE80; }
html[data-theme="dark"] .status-pill--out     { background: rgba(244,63,94,0.16);  color: #F87171; }
html[data-theme="dark"] .status-pill--neutral { background: rgba(245,158,11,0.16); color: #FCD34D; }
html[data-theme="dark"] .status-pill--new     { background: rgba(245,158,11,0.18); color: #FCD34D; }
html[data-theme="dark"] .status-pill--cut     { background: rgba(251,146,60,0.18); color: #FDBA74; }
html[data-theme="dark"] .status-pill--rem     { background: rgba(59,130,246,0.18); color: #93C5FD; }

.category-pill {
  display: inline-flex; font-size: 11px; font-weight: 600;
  color: var(--il-violet); border-radius: 20px; padding: 3px 10px;
  background: #E0E7FF;
}
html[data-theme="dark"] .category-pill { background: rgba(99,102,241,0.18); color: #A5B4FC; }

/* Print-only blocks are hidden on screen */
.print-only { display: none; }

/* Screen-only breakpoints (kept off the print media so the report never collapses into mobile cards) */
@media screen and (max-width: 1100px) { .kpi-strip { grid-template-columns: repeat(2,1fr); } }

/* ── NARROW SCREENS: each table row becomes a labelled card ── */
@media screen and (max-width: 900px) {
  .data-table--stack,
  .data-table--stack tbody,
  .data-table--stack tfoot,
  .data-table--stack tr,
  .data-table--stack td { display: block; width: 100%; }
  .data-table--stack { table-layout: auto; }
  .data-table--stack thead { display: none; }
  .data-table--stack tbody tr {
    border: 1px solid var(--il-border); border-radius: 12px;
    padding: 6px 4px; margin-bottom: 12px;
  }
  .data-table--stack tbody td {
    border-bottom: none; padding: 8px 12px;
  }
  .data-table--stack tbody td[data-label]::before {
    content: attr(data-label);
    display: block; margin-bottom: 4px;
    font-size: 10.5px; font-weight: 700; letter-spacing: .07em;
    text-transform: uppercase; color: var(--il-text-faint);
  }
  .data-table--stack tbody tr:hover td { background: transparent; }
  .data-table--stack tfoot tr { display: flex; flex-wrap: wrap; gap: 12px; padding: 8px 12px; }
  .data-table--stack tfoot td { width: auto; padding: 0; }
}

@media screen and (max-width: 700px)  {
  .inv-logs  { padding: 16px 16px 40px; }
  .card { padding: 18px 16px 16px; }
  .kpi-strip { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; align-items: stretch; }
  .period-control { flex-direction: column; align-items: stretch; }
  .period-mode-toggle { justify-content: stretch; }
  .period-mode-seg { flex: 1; text-align: center; justify-content: center; }
  .period-nav { width: 100%; }
  .picker { flex: 1; min-width: 0; }
  .picker-trigger { width: 100%; min-width: 0; }
  .picker-pop { left: 0; right: 0; width: auto; transform-origin: top center; }
  .report-toolbar { justify-content: stretch; }
  .rt-btn { flex: 1; justify-content: center; }
}

@media (prefers-reduced-motion: reduce) {
  .kpi-card, .rt-btn, .picker-caret, .pk-day, .pk-cell { transition: none; }
  .kpi-card:hover, .rt-btn:hover:not(:disabled) { transform: none; }
  .pop-enter-active, .pop-leave-active { transition: none; }
}

/* ════════════════ PRINT MODE (scoped) ════════════════
   The report is always printed on white, whatever theme the app is in:
   the colour tokens are reset to light values first, so every component
   that uses them (text, borders, pills) prints correctly.            */
@media print {

  .inv-logs,
  html[data-theme="dark"] .inv-logs {
    --il-bg: #ffffff;
    --il-surface: #ffffff;
    --il-surface-raised: #f8fafc;
    --il-surface-sunken: #f8fafc;
    --il-border: #e2e8f0;
    --il-border-strong: #cbd5e1;
    --il-text-primary: #0f172a;
    --il-text-secondary: #334155;
    --il-text-muted: #64748b;
    --il-text-faint: #94a3b8;
    --il-accent: #EA580C;
    --il-accent-soft: #FFF1E8;
    --il-accent-border: #FDBA74;
    --il-amber: #B45309;
    --il-green-icon: #D1FAE5;
    --il-blue-icon: #DBEAFE;
    --il-violet-icon: #E0E7FF;
    --il-red-icon: #FFE4E6;

    background: #ffffff !important;
    padding: 0 !important;
    gap: 16px !important;
    counter-reset: ps;
    font-family: 'Inter', Arial, sans-serif !important;
    font-size: 10.5px;
    color: #0f172a;
  }

  .inv-logs,
  .inv-logs * {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* Hide everything interactive. The letterhead replaces the screen header. */
  .page-header,
  .period-control,
  .report-toolbar,
  .section--screen,
  .kpi-hint,
  .card-eyebrow,
  .il-modal-backdrop { display: none !important; }

  /* ── Letterhead ── */
  .print-header { display: block !important; margin-bottom: 4px; }
  .print-header p, .print-header h1, .print-header dl, .print-header dd { margin: 0; }
  .ph-band { height: 6px; border-radius: 3px; background: linear-gradient(90deg, #0f172a 0%, #0f172a 55%, #EA580C 55%, #F59E0B 100%); }
  .ph-row { display: flex !important; justify-content: space-between; align-items: center; gap: 24px; padding: 14px 0; }
  .ph-brand { display: flex; align-items: center; gap: 12px; }
  .ph-logo {
    display: inline-flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; border-radius: 9px;
    font-size: 15px; font-weight: 800; letter-spacing: .02em;
    color: #fff; background: #EA580C;
  }
  .ph-name { font-size: 16px; font-weight: 800; letter-spacing: -.02em; color: #0f172a; line-height: 1.15; }
  .ph-tag  { margin-top: 3px !important; font-size: 9.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
  .ph-meta { display: grid; grid-template-columns: auto auto; column-gap: 20px; row-gap: 3px; padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
  .ph-meta > div { display: contents; }
  .ph-meta dt { font-size: 8px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; align-self: center; }
  .ph-meta dd { font-size: 10px; font-weight: 600; color: #0f172a; text-align: right; white-space: nowrap; }

  .ph-title-row {
    display: flex !important; align-items: center; justify-content: space-between; gap: 16px;
    padding: 14px 18px; border-radius: 8px; border-left: 5px solid #EA580C;
    background: #0f172a; color: #fff;
  }
  .ph-kicker { font-size: 8.5px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #fdba74; }
  .ph-title  { margin-top: 3px !important; font-size: 21px; font-weight: 800; letter-spacing: -.02em; color: #fff; line-height: 1.15; }
  .ph-title-right { text-align: right; }
  .ph-period { font-size: 14px; font-weight: 700; color: #fff; }
  .ph-chip {
    display: inline-block; margin-top: 5px; padding: 2px 9px; border-radius: 10px;
    font-size: 8.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
  }
  .ph-chip--live   { color: #064e3b; background: #6ee7b7; }
  .ph-chip--closed { color: #334155; background: #e2e8f0; }
  .ph-lead {
    margin-top: 14px !important; padding: 10px 14px;
    border-left: 3px solid #EA580C; border-radius: 0 6px 6px 0; background: #f8fafc;
    font-size: 10.5px; line-height: 1.6; color: #334155;
  }

  /* ── Numbered section headings (01, 02, …) ── */
  .section { gap: 10px !important; margin-bottom: 2px !important; }
  .section-header { margin: 0 !important; }
  .section-title,
  .card-title {
    display: flex !important; align-items: center; gap: 9px;
    counter-increment: ps;
    margin: 0 0 10px !important; padding-bottom: 7px;
    font-size: 13px !important; font-weight: 800; letter-spacing: -.01em;
    color: #0f172a !important; border-bottom: 1.5px solid #0f172a;
    break-after: avoid; page-break-after: avoid;
  }
  .section-title::before,
  .card-title::before {
    content: counter(ps, decimal-leading-zero);
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 22px; height: 20px; padding: 0 5px; border-radius: 5px;
    font-size: 9px; font-weight: 800; letter-spacing: .04em; color: #fff; background: #EA580C;
  }
  .card-sub { margin: -4px 0 10px !important; max-width: none !important; color: #64748b !important; font-size: 9.5px !important; }

  /* ── KPI tiles ── */
  .kpi-strip { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 9px !important; }
  .kpi-card {
    background: #ffffff !important; border: 1px solid #e2e8f0 !important; border-left: 3px solid #EA580C !important;
    box-shadow: none !important; border-radius: 6px !important;
    padding: 10px 12px 9px !important; break-inside: avoid; page-break-inside: avoid;
  }
  .kpi-card--featured { background: #FFF1E8 !important; border-color: #FED7AA !important; border-left-color: #EA580C !important; }
  .kpi-card--alert { background: #FFF1F2 !important; border-color: #FECDD3 !important; border-left-color: #e11d48 !important; }
  .kpi-card-inner { gap: 9px !important; }
  .kpi-icon-wrap { width: 26px !important; height: 26px !important; border-radius: 7px !important; font-size: 11px !important; }
  .kpi-icon-wrap--orange { background: #FFEDD5 !important; color: #EA580C !important; }
  .kpi-icon-wrap--green  { background: #D1FAE5 !important; color: #059669 !important; }
  .kpi-icon-wrap--blue   { background: #DBEAFE !important; color: #2563EB !important; }
  .kpi-icon-wrap--violet { background: #E0E7FF !important; color: #6366F1 !important; }
  .kpi-icon-wrap--red    { background: #FFE4E6 !important; color: #e11d48 !important; }
  .kpi-label { color: #64748b !important; font-size: 8px !important; font-weight: 800 !important; letter-spacing: .07em !important; }
  .kpi-value { color: #0f172a !important; font-size: 17px !important; }
  .kpi-value--green  { color: #047857 !important; }
  .kpi-value--blue   { color: #2563EB !important; }
  .kpi-value--violet { color: #6366F1 !important; }
  .kpi-value--red    { color: #be123c !important; }
  .kpi-accent-bar { display: none !important; }

  /* ── Cards become flat sections ── */
  .card {
    background: #ffffff !important; border: none !important;
    box-shadow: none !important; border-radius: 0 !important;
    padding: 0 !important;
  }
  .card-header { display: block !important; margin-bottom: 0 !important; }
  .table-wrap { overflow: visible !important; }

  /* ── Table cell content ── */
  .data-table { font-size: 10px !important; }
  .cell-product  { color: #0f172a !important; font-size: 10.5px !important; }
  .cell-sku      { color: #94a3b8 !important; font-size: 8.5px !important; }
  .cell-faint    { color: #94a3b8 !important; font-size: 9.5px !important; }
  .cell-date-day  { color: #334155 !important; font-size: 9.5px !important; }
  .cell-date-time { color: #94a3b8 !important; font-size: 8.5px !important; }
  .cell-notes { font-size: 9.5px !important; }
  .il-empty { color: #94a3b8 !important; padding: 10px 0; }

  .qty-main { font-size: 11.5px !important; }
  .qty-main--in      { color: #047857 !important; }
  .qty-main--out     { color: #be123c !important; }
  .qty-main--neutral { color: #B45309 !important; }
  .qty-caption { color: #94a3b8 !important; font-size: 8.5px !important; }
  .qty-details {
    background: #f8fafc !important; border-left: 2px solid #FED7AA !important;
    padding: 3px 7px !important; margin-top: 4px !important;
  }
  .qty-row { font-size: 9px !important; }
  .qty-row dt { color: #64748b !important; }
  .qty-row dd { color: #0f172a !important; }

  .status-pill { font-size: 8.5px !important; padding: 2px 8px !important; }
  .status-pill--in      { background: #D1FAE5 !important; color: #047857 !important; }
  .status-pill--out     { background: #FFE4E6 !important; color: #BE123C !important; }
  .status-pill--neutral { background: #FEF3C7 !important; color: #92400E !important; }
  .status-pill--new { background: #FEF3C7 !important; color: #92400E !important; }
  .status-pill--cut { background: #FFEDD5 !important; color: #C2410C !important; }
  .status-pill--rem { background: #DBEAFE !important; color: #1D4ED8 !important; }

  .unit-pill { font-size: 8px !important; padding: 1px 6px !important; background: #F1F5F9 !important; color: #475569 !important; }
  .category-pill { font-size: 8.5px !important; padding: 2px 8px !important; background: #E0E7FF !important; color: #4F46E5 !important; }

  /* ── Notes, sign-off, end of report ── */
  .print-notes { display: block !important; margin-top: 8px; break-inside: avoid; page-break-inside: avoid; }
  .pn-title {
    margin: 0 0 8px !important; padding-bottom: 6px;
    font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
    color: #0f172a; border-bottom: 1.5px solid #0f172a;
  }
  .print-notes ul { margin: 0; padding: 0 0 0 16px; list-style: disc; font-size: 9.5px; line-height: 1.6; color: #475569; }
  .print-notes li { margin-bottom: 2px; }
  .print-notes b { color: #0f172a; }
  .pn-sign { display: grid; grid-template-columns: 1.3fr 1.3fr .8fr; gap: 28px; margin-top: 30px; }
  .pn-sign span { display: block; height: 22px; border-bottom: 1px solid #0f172a; }
  .pn-sign p { margin: 4px 0 0 !important; font-size: 8.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }

  .print-end {
    display: flex !important; justify-content: space-between; gap: 12px;
    margin-top: 6px; padding-top: 8px;
    font-size: 8.5px; color: #94a3b8; border-top: 2px solid #0f172a;
  }
}
</style>

<!-- Global (unscoped) DataTables overrides, namespaced under .inv-logs -->
<style>
.inv-logs table.dataTable thead th.sorting,
.inv-logs table.dataTable thead th.sorting_asc,
.inv-logs table.dataTable thead th.sorting_desc,
.inv-logs table.dataTable thead th.sorting_asc_disabled,
.inv-logs table.dataTable thead th.sorting_desc_disabled {
  background-image: none !important;
  background-repeat: no-repeat !important;
  background-position: right center !important;
  background-size: 0 !important;
  cursor: pointer;
  position: relative;
  padding-right: 28px !important;
}

.inv-logs table.dataTable thead th.sorting::after,
.inv-logs table.dataTable thead th.sorting_asc::after,
.inv-logs table.dataTable thead th.sorting_desc::after {
  font-family: 'Font Awesome 6 Free', 'FontAwesome', sans-serif;
  font-weight: 900; font-size: 9px;
  position: absolute; right: 10px; top: 50%;
  transform: translateY(-50%); line-height: 1;
  content: '\f0dc'; color: #D6CFC8;
}
.inv-logs table.dataTable thead th.sorting_asc::after  { content: '\f0de'; color: #EA580C; }
.inv-logs table.dataTable thead th.sorting_desc::after { content: '\f0dd'; color: #EA580C; }

html[data-theme="dark"] .inv-logs table.dataTable thead th.sorting::after       { color: #555B72; }
html[data-theme="dark"] .inv-logs table.dataTable thead th.sorting_asc::after,
html[data-theme="dark"] .inv-logs table.dataTable thead th.sorting_desc::after  { color: #FB923C; }

.inv-logs table.dataTable thead th {
  background: #FAFAF9 !important; color: #78716C !important;
  font-size: 11px !important; font-weight: 700 !important;
  text-transform: uppercase !important; letter-spacing: .07em !important;
  text-align: left !important; padding: 12px 14px !important;
  border-bottom: 1px solid #F5F0EB !important;
  white-space: nowrap; border-top: none !important;
}
.inv-logs table.dataTable thead th:first-child { border-radius: 10px 0 0 0; }
.inv-logs table.dataTable thead th:last-child  { border-radius: 0 10px 0 0; }

html[data-theme="dark"] .inv-logs table.dataTable thead th {
  background: #222535 !important; color: #8B90A8 !important;
  border-bottom: 1px solid #2A2D3E !important;
}

.inv-logs table.dataTable tbody tr { background: transparent !important; }
.inv-logs table.dataTable tbody tr:hover { background: transparent !important; }

html[data-theme="dark"] .inv-logs table.dataTable tbody td {
  color: #C4C8D6 !important; border-bottom-color: #2A2D3E !important;
}
html[data-theme="dark"] .inv-logs table.dataTable tbody tr:hover td { background: #222535 !important; }

.inv-logs table.dataTable.no-footer { border-bottom: none !important; }

.inv-logs .inv-dt-toolbar {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
}
.inv-logs .inv-dt-toolbar--right { justify-content: flex-end; }
.inv-logs .inv-dt-toolbar-left  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.inv-logs .inv-dt-toolbar-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

.inv-logs .dataTables_filter,
.inv-logs .dataTables_length { display: flex; align-items: center; }

.inv-logs .dataTables_filter label,
.inv-logs .dataTables_length label {
  font-size: 12.5px; font-weight: 600; color: #78716C;
  display: flex; align-items: center; gap: 6px;
  margin-bottom: 0; white-space: nowrap;
}
html[data-theme="dark"] .inv-logs .dataTables_filter label,
html[data-theme="dark"] .inv-logs .dataTables_length label { color: #8B90A8 !important; }

.inv-logs .dataTables_filter input,
.inv-logs .dataTables_length select {
  border: 1.5px solid #E7E0D8 !important; border-radius: 10px !important;
  padding: 6px 10px !important; font-size: 12.5px !important;
  font-family: 'Inter', system-ui, sans-serif !important;
  color: #44403C !important; background: #fff !important;
  margin: 0 !important; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color 0.22s, color 0.22s;
}
.inv-logs .dataTables_filter input:focus,
.inv-logs .dataTables_length select:focus {
  border-color: #EA580C !important;
  box-shadow: 0 0 0 3px rgba(234,88,12,.10) !important;
}

html[data-theme="dark"] .inv-logs .dataTables_filter input,
html[data-theme="dark"] .inv-logs .dataTables_length select {
  background: #13151E !important; color: #E8EAF0 !important; border-color: #2A2D3E !important;
}
html[data-theme="dark"] .inv-logs .dataTables_filter input:focus,
html[data-theme="dark"] .inv-logs .dataTables_length select:focus {
  border-color: #FB923C !important; box-shadow: 0 0 0 3px rgba(251,146,60,.12) !important;
}

.inv-logs .dataTables_info { color: #78716C !important; font-size: 12.5px !important; padding-top: 12px !important; }
html[data-theme="dark"] .inv-logs .dataTables_info { color: #8B90A8 !important; }

.inv-logs .dataTables_paginate { padding-top: 12px !important; display: flex; gap: 4px; flex-wrap: wrap; }
.inv-logs .dataTables_paginate .paginate_button {
  border: 1.5px solid #E7E0D8 !important; border-radius: 8px !important;
  padding: 5px 11px !important; margin: 0 !important;
  font-size: 12.5px !important; color: #44403C !important;
  cursor: pointer; background: #fff !important; line-height: 1.4 !important;
  transition: background .15s, border-color .15s, color .15s;
}
.inv-logs .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
  background: #FFF7ED !important; border-color: #FDBA74 !important; color: #EA580C !important;
}
.inv-logs .dataTables_paginate .paginate_button.current {
  background: #EA580C !important; border-color: #EA580C !important; color: #fff !important;
}
.inv-logs .dataTables_paginate .paginate_button.disabled { opacity: .4; cursor: not-allowed; }

html[data-theme="dark"] .inv-logs .dataTables_paginate .paginate_button {
  background: #1A1D27 !important; color: #C4C8D6 !important; border-color: #2A2D3E !important;
}
html[data-theme="dark"] .inv-logs .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
  background: rgba(251,146,60,0.10) !important; border-color: rgba(251,146,60,0.30) !important; color: #FB923C !important;
}
html[data-theme="dark"] .inv-logs .dataTables_paginate .paginate_button.current {
  background: #FB923C !important; border-color: #FB923C !important; color: #0F1117 !important;
}

/* Narrow screens: DataTables controls stack and the sort header row is hidden */
@media screen and (max-width: 900px) {
  .inv-logs .inv-dt-toolbar--right { justify-content: space-between; }
  .inv-logs .dataTables_paginate { justify-content: center; }
}

/* ══════════════════════════════════════════════════════
   GLOBAL PRINT — page setup + table styling.
   Selectors start with `html #inventory-print-area` so they always
   beat the DataTables / dark-theme rules above (id specificity).
══════════════════════════════════════════════════════ */
@media print {
  @page {
    size: auto;
    margin: 17mm 14mm 18mm;
    @top-left     { content: "Sheem Steel Construction Company"; font: 600 8px 'Inter', Arial, sans-serif; color: #94a3b8; }
    @top-right    { content: "Inventory Logs"; font: 600 8px 'Inter', Arial, sans-serif; color: #94a3b8; }
    @bottom-left  { content: "Confidential · For internal use only"; font: 600 8px 'Inter', Arial, sans-serif; color: #94a3b8; }
    @bottom-right { content: "Page " counter(page) " of " counter(pages); font: 700 8.5px 'Inter', Arial, sans-serif; color: #475569; }
  }
  /* The letterhead already carries the branding on page 1 */
  @page :first {
    @top-left  { content: none; }
    @top-right { content: none; }
  }

  /* The app shell often fixes html/body height with overflow hidden so the
     sidebar layout can scroll internally; print would then only capture the
     visible viewport. Force natural height so the whole report prints. */
  html, body { height: auto !important; overflow: visible !important; background: #fff !important; }
  body * { overflow: visible !important; }

  body * { visibility: hidden !important; }
  #inventory-print-area,
  #inventory-print-area * { visibility: visible !important; }
  #inventory-print-area {
    position: absolute !important;
    top: 0 !important; left: 0 !important;
    width: 100% !important; max-width: 100% !important;
    margin: 0 !important; padding: 0 !important;
    background: #fff !important;
    display: flex !important;
  }

  /* DataTables chrome is never printed */
  .dataTables_filter, .dataTables_length,
  .dataTables_paginate, .dataTables_info { display: none !important; }

  /* Tables */
  html #inventory-print-area table { width: 100% !important; border-collapse: collapse !important; }
  html #inventory-print-area thead { display: table-header-group !important; }
  html #inventory-print-area tfoot { display: table-footer-group !important; }
  html #inventory-print-area tr { break-inside: avoid !important; page-break-inside: avoid !important; }

  html #inventory-print-area .data-table thead th,
  html #inventory-print-area table.dataTable thead th {
    background: #0f172a !important; color: #ffffff !important;
    font-size: 8px !important; font-weight: 800 !important; letter-spacing: .07em !important;
    text-transform: uppercase !important; text-align: left !important;
    padding: 7px 8px !important; border: none !important; border-radius: 0 !important;
    white-space: normal !important;
  }
  html #inventory-print-area table.dataTable thead th::after,
  html #inventory-print-area table.dataTable thead th::before { display: none !important; content: none !important; }

  html #inventory-print-area .data-table tbody td {
    padding: 6px 8px !important; border-bottom: 1px solid #e2e8f0 !important;
    background: transparent !important; color: #334155 !important;
    vertical-align: top !important;
  }
  html #inventory-print-area .data-table tbody tr:nth-child(even) td { background: #f8fafc !important; }
  html #inventory-print-area .data-table tbody td.cell-muted    { color: #475569 !important; }
  html #inventory-print-area .data-table tbody td.cell-faint    { color: #94a3b8 !important; }
  html #inventory-print-area .data-table tbody td.cell-positive { color: #047857 !important; font-weight: 700 !important; }
  html #inventory-print-area .data-table tbody td.cell-negative { color: #be123c !important; font-weight: 700 !important; }
  html #inventory-print-area .data-table tbody td.cell-product  { color: #0f172a !important; }

  html #inventory-print-area .data-table .svc-total td {
    background: #f1f5f9 !important; color: #0f172a !important;
    font-weight: 800 !important; padding: 8px 8px !important;
    border-top: 2px solid #0f172a !important; border-bottom: none !important;
  }
}
</style>