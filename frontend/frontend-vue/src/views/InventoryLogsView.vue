<template>
  <Layout title="Inventory Logs">
    <div id="inventory-print-area" class="inv-logs">

      <!-- Print-only header -->
      <div class="print-only print-header">
        <div class="print-header-top">
          <span class="print-header-date">{{ printGeneratedAt }}</span>
          <span class="print-header-company">Sheem Steel Construction Company</span>
        </div>
        <h1 class="print-header-brand">Sheem Steel Construction Company</h1>
        <p class="print-header-sub">Inventory Logs</p>
        <p class="print-header-meta">Printed: {{ printGeneratedAt }}</p>
        <p class="print-header-meta">Filtered range: {{ filterLabel }}</p>
        <hr class="print-header-rule" />
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

          <div class="period-picker-wrap">
            <input v-if="periodMode === 'month'" id="inv-month" type="month" class="period-picker-input" v-model="selectedMonth" @change="applyDateFilter" />
            <input v-else id="inv-date" type="date" class="period-picker-input" v-model="selectedDate" @change="applyDateFilter" />
          </div>

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

          <!-- Leftover pieces on hand (current state — not date filtered) -->
          <div class="kpi-card kpi-card--wide kpi-card--clickable" role="button" tabindex="0"
            @click="scrollToRemnants" @keydown.enter="scrollToRemnants" @keydown.space.prevent="scrollToRemnants">
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap kpi-icon-wrap--blue"><i class="fa-solid fa-ruler-horizontal"></i></div>
              <div class="kpi-body">
                <p class="kpi-label">Leftover Pieces On Hand</p>
                <p class="kpi-value kpi-value--blue">{{ formatNumber(availableCount) }}</p>
                <div v-if="remnantTotalsByUnit.length" class="il-unit-chips il-unit-chips--tight">
                  <span v-for="u in remnantTotalsByUnit" :key="u.unit" class="il-unit-chip il-unit-chip--rem">
                    {{ lenText(u.total) }} {{ u.unit }} total
                  </span>
                </div>
                <p v-else class="kpi-hint">No leftovers on hand</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--blue"></div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="section">
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Latest</p>
              <h2 class="card-title">Recent Activity</h2>
              <p class="card-sub">Quantity is the number of whole pieces moved. For cut sales it also shows how much length was sold and how much was left over.</p>
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

      <!-- Leftover pieces (remnants) — plain table, not DataTables -->
      <div class="section" id="remnants-section">
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Cut tracking</p>
              <h2 class="card-title">Leftover Pieces</h2>
              <p class="card-sub">What is left of a piece after a cut, e.g. 18 in piece − 10 in sold = 8 in leftover. These are not counted as whole pieces in stock.</p>
            </div>
            <div class="rem-filters no-print" role="group" aria-label="Filter leftover pieces">
              <button v-for="f in REMNANT_FILTERS" :key="f.key" type="button"
                class="rem-filter" :class="{ 'rem-filter--on': remnantFilter === f.key }"
                @click="remnantFilter = f.key">
                {{ f.label }}
                <span class="rem-filter-count">{{ remnantCount(f.key) }}</span>
              </button>
            </div>
          </div>
          <div class="table-wrap">
            <table class="data-table data-table--plain data-table--fixed data-table--stack">
              <thead>
                <tr>
                  <th style="width:24%">Product</th>
                  <th style="width:14%">Leftover piece</th>
                  <th style="width:12%">Total length</th>
                  <th style="width:10%">Sale</th>
                  <th style="width:12%">Date</th>
                  <th style="width:10%">Status</th>
                  <th style="width:18%" class="no-print">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in visibleRemnants" :key="r.id">
                  <td data-label="Product">
                    <div class="cell-product">{{ r.ProductName }}</div>
                    <div class="cell-sku">
                      {{ r.SKU }}
                      <span v-if="r.ProductUnit" class="unit-pill">{{ unitLabel(r.ProductUnit) }}</span>
                    </div>
                    <div v-if="r.VariantLabel" class="cell-sku">Size: {{ r.VariantLabel }}</div>
                  </td>
                  <td data-label="Leftover piece" class="cell-positive">{{ r.qty }} × {{ lenText(r.length) }} {{ r.unit }}</td>
                  <td data-label="Total length" class="cell-muted">{{ lenText(r.qty * r.length) }} {{ r.unit }}</td>
                  <td data-label="Sale" class="cell-muted">{{ r.sale_id ? receiptNo(r.sale_id) : '—' }}</td>
                  <td data-label="Date" class="cell-date" :title="r.created_at">
                    <div class="cell-date-day">{{ dateOnly(r.created_at) }}</div>
                    <div class="cell-date-time">{{ timeOnly(r.created_at) }}</div>
                  </td>
                  <td data-label="Status">
                    <span class="status-pill"
                      :class="{
                        'status-pill--in':      r.status === 'available',
                        'status-pill--neutral': r.status === 'used',
                        'status-pill--out':     r.status === 'scrapped',
                      }">{{ r.status }}</span>
                  </td>
                  <td data-label="Action" class="no-print">
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
                  <td colspan="7" class="il-empty">{{ remnantEmptyText }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="remnantError" class="rem-error" role="alert">{{ remnantError }}</p>
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

      <!-- Breakdown modal -->
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
                <tr><th>Product</th><th>Movements</th><th>Quantity</th></tr>
              </thead>
              <tbody>
                <tr v-for="r in breakdownRows" :key="r.key">
                  <td>
                    <div class="cell-product">{{ r.name }}</div>
                    <div class="cell-sku">{{ r.sku }}</div>
                  </td>
                  <td class="cell-muted">{{ r.count }}</td>
                  <td :class="breakdown.type === 'stock_in' ? 'cell-positive' : 'cell-negative'">
                    {{ breakdown.type === 'stock_in' ? '+' : '-' }}{{ formatQty(r.total, r.unit) }}
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-else class="il-empty">Nothing recorded for this period.</p>
          </div>
        </div>
      </div>

      <!-- Print-only footer -->
      <div class="print-only print-footer">
        <span>Sheem Steel Construction Company &middot; Inventory Logs</span>
        <span>Confidential &mdash; Internal Use Only</span>
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
const remnants            = ref([])

// ── Period filter state ──
const periodMode = ref('month')

function currentMonthValue() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}
function currentDateValue() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
}

const selectedMonth = ref(currentMonthValue())
const selectedDate  = ref(currentDateValue())
const isFiltering   = ref(false)

const currentTopLimit = ref(10)

// Report actions (Copy / CSV / Print): 'idle' | 'copying' | 'copied' | 'error'
const copyState = ref('idle')
const printGeneratedAt = ref('')

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
   History rows written when a leftover is marked used / scrapped / put back.
   The length + unit are stored on the row; older rows are parsed from the note. */
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

/* ── LEFTOVER PIECES (remnants) ── */
const REMNANT_FILTERS = [
  { key: 'available', label: 'Available' },
  { key: 'used',      label: 'Used' },
  { key: 'scrapped',  label: 'Scrapped' },
  { key: 'all',       label: 'All' },
]
const remnantFilter  = ref('available')
const remnantBusyId  = ref(null)
const remnantError   = ref('')

const availableRemnants = computed(() => remnants.value.filter(r => r.status === 'available'))
const availableCount = computed(() => availableRemnants.value.reduce((s, r) => s + r.qty, 0))
const visibleRemnants = computed(() =>
  remnantFilter.value === 'all'
    ? remnants.value
    : remnants.value.filter(r => r.status === remnantFilter.value)
)
function remnantCount(key) {
  return key === 'all' ? remnants.value.length : remnants.value.filter(r => r.status === key).length
}
const remnantEmptyText = computed(() =>
  remnantFilter.value === 'available'
    ? 'No leftover pieces on hand.'
    : 'No leftover pieces with this status in this period.'
)

// Total leftover length on hand, per unit of length (ft / m / in).
const remnantTotalsByUnit = computed(() => {
  const map = new Map()
  availableRemnants.value.forEach(r => {
    const unit = r.unit || ''
    map.set(unit, (map.get(unit) || 0) + r.qty * r.length)
  })
  return [...map.entries()].map(([unit, total]) => ({ unit, total }))
})

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
    // Reload so the new history row shows in Recent Activity and Full History.
    // (Goes through applyDateFilter so DataTables is rebuilt safely.)
    await applyDateFilter()
  } catch (err) {
    console.error('Update leftover piece failed:', err)
    warning = err?.response?.data?.message || 'Could not update this leftover piece. Please try again.'
  } finally {
    remnantError.value = warning
    remnantBusyId.value = null
  }
}

function scrollToRemnants() {
  document.getElementById('remnants-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

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

/* ── KPI BREAKDOWN (click Stock In / Stock Out) ── */
const breakdown = ref({ open: false, type: 'stock_in' })

function openBreakdown(type) { breakdown.value = { open: true, type } }
function closeBreakdown() { breakdown.value.open = false }
function onEscKey(e) { if (e.key === 'Escape' && breakdown.value.open) closeBreakdown() }

const breakdownTitle = computed(() =>
  breakdown.value.type === 'stock_in' ? 'Stock In breakdown' : 'Stock Out breakdown'
)

const breakdownRows = computed(() => {
  const map = new Map()
  inventoryLogs.value
    .filter((l) => l.type === breakdown.value.type)
    .forEach((l) => {
      const unit = unitLabel(l.Unit, 2)
      const key = `${l.ProductID ?? l.ProductName}|${l.SKU}|${unit}`
      if (!map.has(key)) {
        map.set(key, { key, name: l.ProductName, sku: l.SKU, unit, count: 0, total: 0 })
      }
      const row = map.get(key)
      row.count += 1
      row.total += Math.abs(num(l.quantity))
    })
  return [...map.values()].sort((a, b) => b.total - a.total)
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
    const to = `${selectedMonth.value}-${String(lastDay).padStart(2, '0')}`
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
  return selectedDate.value
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
    remnants.value            = (res.data.remnants ?? []).map(r => ({
      ...r,
      id:     r.RemnantID,
      status: r.Status,
      qty:    Math.max(num(r.Quantity), 1),
      length: num(r.Length),
      unit:   r.Unit || '',
      sale_id: r.SaleID,
    }))

    animateSummaryTo(summary.value)
  } catch (err) {
    console.error('Inventory logs API error:', err)
  }
}

/* ── report actions: Copy / CSV / Print ── */
function remnantLine(r) {
  return `  ${r.ProductName} (${r.SKU})${r.VariantLabel ? ' – ' + r.VariantLabel : ''} — ${r.qty} × ${lenText(r.length)} ${r.unit} leftover [${r.status}]${r.sale_id ? ', ' + receiptNo(r.sale_id) : ''}, ${formatLocalDate(r.created_at)}`
}

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
  lines.push(`  Leftover Pieces On Hand: ${availableCount.value}${remnantTotalsByUnit.value.length ? ' (' + remnantTotalsByUnit.value.map(u => `${lenText(u.total)} ${u.unit}`).join(', ') + ')' : ''}`)
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

  lines.push('Leftover Pieces')
  if (remnants.value.length) {
    remnants.value.forEach((r) => lines.push(remnantLine(r)))
  } else {
    lines.push('  No leftover pieces recorded.')
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
  rows.push(['Leftover Pieces On Hand', availableCount.value])
  remnantTotalsByUnit.value.forEach((u) => rows.push(['Leftover Length On Hand', u.total, u.unit]))
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

  rows.push(['Leftover Pieces'])
  rows.push(['Product', 'SKU', 'Size', 'Pieces', 'Length Each', 'Length Unit', 'Status', 'Sale', 'Date'])
  remnants.value.forEach((r) => {
    rows.push([r.ProductName, r.SKU, r.VariantLabel || '', r.qty, r.length, r.unit, r.status, r.sale_id ? receiptNo(r.sale_id) : '', formatLocalDate(r.created_at)])
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
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
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

function printReport() {
  printGeneratedAt.value = new Date().toLocaleString('en-PH', {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
  nextTick(() => window.print())
}

/* ────────────────────────────────────────────────────────────
   DataTables + Vue: never patch a live DataTable against
   Vue-rendered rows. Destroy BEFORE data changes, let Vue
   re-render, then create fresh instances.
   (The leftover-pieces table is a plain table on purpose, so
   changing a status never needs a DataTables rebuild.)

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
  isFiltering.value = true
  destroyAllTables()

  const { from, to } = computeRange()
  await loadInventoryLogs({
    topLimit:    currentTopLimit.value,
    dateFromVal: from,
    dateToVal:   to,
  })

  await nextTick()
  initAllTables()

  isFiltering.value = false
}

function setPeriodMode(mode) {
  if (periodMode.value === mode) return
  periodMode.value = mode
  applyDateFilter()
}

onMounted(async () => {
  window.addEventListener('keydown', onEscKey)
  const { from, to } = computeRange()
  await loadInventoryLogs({ dateFromVal: from, dateToVal: to })
  await nextTick()

  setTimeout(() => {
    initAllTables()
  }, 200)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onEscKey)
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

/* ── PAGE HEADER ── */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px; }
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
.rt-btn:focus-visible { outline: 2px solid var(--il-accent); outline-offset: 2px; }
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
.period-mode-seg:focus-visible { outline: 2px solid var(--il-accent); outline-offset: 2px; }
.period-mode-seg--active { background: var(--il-accent); color: #fff; }
.period-mode-seg--active:hover { color: #fff; }

.period-picker-wrap { display: flex; align-items: center; }
.period-picker-input {
  border: 1.5px solid var(--il-border-strong);
  background: var(--il-surface);
  border-radius: 11px;
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 13.5px; font-weight: 600;
  color: var(--il-text-primary);
  outline: none; cursor: pointer;
  padding: 8px 12px;
  box-shadow: var(--il-shadow);
  transition: border-color .15s ease;
}
.period-picker-input:hover,
.period-picker-input:focus { border-color: var(--il-accent-border); }
.period-picker-input::-webkit-calendar-picker-indicator { cursor: pointer; border-radius: 4px; padding: 2px; }
html[data-theme="dark"] .period-picker-input::-webkit-calendar-picker-indicator { filter: invert(1) brightness(1.6); }

.date-filter-loading { font-size: 12px; color: var(--il-accent); display: flex; align-items: center; gap: 6px; }

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
.kpi-card--wide { grid-column: 1 / -1; }

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
.kpi-card--clickable:focus-visible { outline: 2px solid var(--il-accent); outline-offset: 2px; }
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
.il-modal-close:focus-visible { outline: 2px solid var(--il-accent); outline-offset: 2px; }

.il-unit-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.il-unit-chips--tight { margin-top: 4px; }
.il-unit-chip { font-size: 12.5px; font-weight: 700; border-radius: 20px; padding: 4px 12px; }
.il-unit-chip--in  { background: var(--il-green-icon); color: var(--il-green); }
.il-unit-chip--out { background: var(--il-red-icon);   color: var(--il-red); }
.il-unit-chip--rem { background: var(--il-blue-icon);  color: var(--il-blue); }

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

/* ── LEFTOVER PIECES ── */
.rem-filters { display: inline-flex; gap: 4px; padding: 3px; border: 1px solid var(--il-border); border-radius: 12px; background: var(--il-surface-sunken); }
.rem-filter {
  display: inline-flex; align-items: center; gap: 6px;
  border: none; background: transparent; cursor: pointer;
  font-family: 'Inter', system-ui, sans-serif; font-size: 12.5px; font-weight: 600;
  color: var(--il-text-muted); padding: 7px 12px; border-radius: 9px;
  transition: background-color .16s ease, color .16s ease;
}
.rem-filter:hover { color: var(--il-text-secondary); }
.rem-filter--on { background: var(--il-accent); color: #fff; }
.rem-filter--on:hover { color: #fff; }
.rem-filter:focus-visible { outline: 2px solid var(--il-accent); outline-offset: 2px; }
.rem-filter-count {
  min-width: 18px; padding: 0 5px; text-align: center;
  font-size: 10.5px; font-weight: 700; line-height: 18px; border-radius: 9px;
  background: rgba(127,127,127,.18);
}

.rem-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.rem-btn {
  border: 1px solid var(--il-border-strong); background: var(--il-surface-sunken);
  color: var(--il-text-secondary); cursor: pointer;
  font-family: 'Inter', system-ui, sans-serif; font-size: 12px; font-weight: 600;
  padding: 5px 11px; border-radius: 8px;
  transition: background-color .15s ease, border-color .15s ease, color .15s ease;
}
.rem-btn:hover:not(:disabled) { border-color: var(--il-accent-border); color: var(--il-accent); background: var(--il-accent-soft); }
.rem-btn--danger:hover:not(:disabled) { border-color: rgba(244,63,94,.4); color: var(--il-red); background: rgba(244,63,94,.10); }
.rem-btn:disabled { opacity: .5; cursor: default; }
.rem-btn:focus-visible { outline: 2px solid var(--il-accent); outline-offset: 2px; }
.rem-error { margin: 12px 0 0; font-size: 12.5px; font-weight: 600; color: var(--il-red); }

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

.print-only { display: none; }
.print-header,
.print-footer { display: none; }

@media (max-width: 1100px) { .kpi-strip { grid-template-columns: repeat(2,1fr); } }

/* ── NARROW SCREENS: each table row becomes a labelled card ── */
@media (max-width: 900px) {
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

@media (max-width: 700px)  {
  .inv-logs  { padding: 16px 16px 40px; }
  .card { padding: 18px 16px 16px; }
  .kpi-strip { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; align-items: stretch; }
  .period-control { flex-direction: column; align-items: stretch; }
  .period-mode-toggle { justify-content: stretch; }
  .period-mode-seg { flex: 1; text-align: center; justify-content: center; }
  .period-picker-wrap { justify-content: stretch; }
  .period-picker-input { width: 100%; }
  .report-toolbar { justify-content: stretch; }
  .rt-btn { flex: 1; justify-content: center; }
  .rem-filters { width: 100%; overflow-x: auto; }
}

@media (prefers-reduced-motion: reduce) {
  .kpi-card, .rt-btn, .rem-btn, .rem-filter { transition: none; }
  .kpi-card:hover, .rt-btn:hover:not(:disabled) { transform: none; }
}

/* ════════════════ PRINT MODE ════════════════ */
@media print {

  .inv-logs {
    background: #ffffff !important;
    padding: 0 !important;
    gap: 18px !important;
    font-family: 'Inter', Arial, sans-serif !important;
  }

  .period-control,
  .report-toolbar,
  .no-print,
  .page-sub,
  .il-modal-backdrop { display: none !important; }

  .print-header { display: block !important; margin-bottom: 4px; }
  .print-header-top {
    display: flex; justify-content: space-between;
    font-size: 10px; color: #A8A29E; margin-bottom: 14px;
  }
  .print-header-brand { font-size: 21px; font-weight: 800; color: #1C1917; margin: 0 0 2px; letter-spacing: -.02em; }
  .print-header-sub { font-size: 13px; font-weight: 600; color: #57534E; margin: 0 0 4px; }
  .print-header-meta { font-size: 11px; color: #A8A29E; margin: 0 0 4px; }
  .print-header-meta:last-of-type { margin: 0 0 14px; }
  .print-header-rule { border: none; border-top: 2px solid #EA580C; margin: 0 0 18px; }

  .page-header { margin-bottom: 4px; }
  .page-eyebrow { color: #EA580C !important; font-size: 10px !important; }
  .page-title { color: #1C1917 !important; font-size: 20px !important; }
  .card-eyebrow { color: #EA580C !important; font-size: 9.5px !important; }
  .section-title { color: #1C1917 !important; font-size: 14.5px !important; }
  .card-title { color: #1C1917 !important; font-size: 13.5px !important; }
  .card-sub { color: #78716C !important; font-size: 9.5px !important; }

  .kpi-strip { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 10px !important; }
  .kpi-card {
    background: #FAFAF9 !important; border: 1px solid #E7E0D8 !important;
    box-shadow: none !important; border-radius: 12px !important;
    padding: 12px 14px 10px !important; break-inside: avoid;
  }
  .kpi-card--featured { background: #FFF7ED !important; border-color: #FED7AA !important; }
  .kpi-card--alert { background: #FFF1F2 !important; border-color: #FECDD3 !important; }
  .kpi-hint { display: none !important; }
  .kpi-icon-wrap { width: 30px !important; height: 30px !important; border-radius: 9px !important; font-size: 12px !important; }
  .kpi-icon-wrap--orange { background: #FFEDD5 !important; color: #EA580C !important; }
  .kpi-icon-wrap--green  { background: #D1FAE5 !important; color: #10B981 !important; }
  .kpi-icon-wrap--blue   { background: #DBEAFE !important; color: #3B82F6 !important; }
  .kpi-icon-wrap--violet { background: #E0E7FF !important; color: #6366F1 !important; }
  .kpi-icon-wrap--red    { background: #FFE4E6 !important; color: #F43F5E !important; }
  .kpi-label { color: #A8A29E !important; font-size: 9px !important; }
  .kpi-value { color: #1C1917 !important; font-size: 17px !important; }
  .kpi-value--green  { color: #10B981 !important; }
  .kpi-value--blue   { color: #3B82F6 !important; }
  .kpi-value--violet { color: #6366F1 !important; }
  .kpi-value--red    { color: #F43F5E !important; }
  .il-unit-chip--rem { background: #DBEAFE !important; color: #3B82F6 !important; font-size: 9.5px !important; padding: 2px 8px !important; }
  .kpi-accent-bar { height: 2px !important; }
  .kpi-accent-bar--orange { background: #EA580C !important; }
  .kpi-accent-bar--green  { background: #10B981 !important; }
  .kpi-accent-bar--blue   { background: #3B82F6 !important; }
  .kpi-accent-bar--violet { background: #6366F1 !important; }
  .kpi-accent-bar--red    { background: #F43F5E !important; }

  .card {
    background: #ffffff !important; border: 1px solid #EDE8E2 !important;
    box-shadow: none !important; border-radius: 12px !important;
    padding: 14px 16px 12px !important; break-inside: avoid-page;
  }
  .card-header { margin-bottom: 10px !important; }

  .data-table { font-size: 10.5px !important; }
  .data-table thead th,
  .data-table--plain thead th {
    background: #FAFAF9 !important; color: #78716C !important;
    font-size: 9px !important; padding: 7px 10px !important;
    border-bottom: 1px solid #EDE8E2 !important;
  }
  .data-table tbody td {
    padding: 6px 10px !important; border-bottom: 1px solid #F2EEE9 !important;
    color: #44403C !important; white-space: normal !important;
  }
  .data-table tbody tr:nth-child(even) td { background: #FCFBFA !important; }
  .svc-total td { color: #1C1917 !important; border-top: 1px solid #EDE8E2 !important; }

  .cell-product  { color: #1C1917 !important; font-size: 10.5px !important; }
  .cell-sku      { color: #A8A29E !important; font-size: 8.5px !important; }
  .cell-faint    { color: #A8A29E !important; font-size: 9.5px !important; }
  .cell-muted    { color: #57534E !important; }
  .cell-positive { color: #10B981 !important; }
  .cell-negative { color: #F43F5E !important; }
  .cell-date-day  { color: #57534E !important; font-size: 9.5px !important; }
  .cell-date-time { color: #A8A29E !important; font-size: 8.5px !important; }

  .qty-main { font-size: 11.5px !important; }
  .qty-main--in      { color: #10B981 !important; }
  .qty-main--out     { color: #F43F5E !important; }
  .qty-main--neutral { color: #B45309 !important; }
  .qty-caption { color: #A8A29E !important; font-size: 8.5px !important; }
  .qty-details {
    background: #FAFAF9 !important; border-left: 2px solid #FED7AA !important;
    padding: 3px 7px !important; margin-top: 4px !important;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  .qty-row { font-size: 9px !important; }
  .qty-row dt { color: #78716C !important; }
  .qty-row dd { color: #1C1917 !important; }

  .status-pill {
    font-size: 9px !important; padding: 2px 9px !important;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  .status-pill--in      { background: #D1FAE5 !important; color: #047857 !important; }
  .status-pill--out     { background: #FFE4E6 !important; color: #BE123C !important; }
  .status-pill--neutral { background: #FEF3C7 !important; color: #92400E !important; }
  .status-pill--new { background: #FEF3C7 !important; color: #92400E !important; }
  .status-pill--cut { background: #FFEDD5 !important; color: #C2410C !important; }
  .status-pill--rem { background: #DBEAFE !important; color: #1D4ED8 !important; }

  .unit-pill {
    font-size: 8px !important; padding: 1px 6px !important;
    background: #F5F5F4 !important; color: #57534E !important;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }

  .category-pill {
    font-size: 9px !important; padding: 2px 8px !important;
    background: #E0E7FF !important; color: #6366F1 !important;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }

  .print-footer {
    display: flex !important; justify-content: space-between;
    font-size: 8.5px; color: #A8A29E;
    border-top: 1px solid #EDE8E2; padding-top: 8px; margin-top: 6px;
    position: fixed; bottom: 0; left: 0; right: 0;
  }

  .section { break-inside: avoid-page; margin-bottom: 4px !important; }
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
@media (max-width: 900px) {
  .inv-logs .inv-dt-toolbar--right { justify-content: space-between; }
  .inv-logs .dataTables_paginate { justify-content: center; }
}

@media print {
  body * { visibility: hidden !important; }
  #inventory-print-area,
  #inventory-print-area * { visibility: visible !important; }
  #inventory-print-area {
    position: absolute; left: 0; top: 0;
    width: 100%; padding: 14px 18px 50px; background: #fff;
  }
  .print-only { display: block !important; }
  .dataTables_filter, .dataTables_length,
  .dataTables_paginate, .dataTables_info { display: none !important; }
  thead { display: table-header-group !important; }
  tr, td, th { page-break-inside: avoid !important; }
}
</style>