<template>
  <Layout title="Reports">
    <div id="reports-print-area" class="reports">

      <!-- Print-only letterhead — invisible on screen, shown only when printing -->
      <div class="print-header">
        <div class="print-header-brand">
          <p class="print-brand-name">Sales Intelligence</p>
          <h1 class="print-report-title">Sales Report</h1>
        </div>
        <div class="print-header-meta">
          <p><span>Period</span>{{ periodLabel }}</p>
          <p><span>Generated</span>{{ printGeneratedAt }}</p>
        </div>
      </div>

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div class="page-header-left">
          <p class="page-eyebrow">Sales Intelligence</p>
          <h1 class="page-title">Reports</h1>
        </div>

        <!-- Unified period control: Month / Specific Date toggle + one picker -->
        <div class="period-control">
          <div class="period-mode-switch">
            <button
              type="button"
              class="period-mode-btn"
              :class="{ 'period-mode-btn--active': mode === 'month' }"
              @click="setMode('month')"
            >
              By Month
            </button>
            <button
              type="button"
              class="period-mode-btn"
              :class="{ 'period-mode-btn--active': mode === 'day' }"
              @click="setMode('day')"
            >
              Specific Date
            </button>
          </div>

          <input
            v-if="mode === 'month'"
            type="month"
            class="period-picker-input"
            v-model="selectedMonth"
            :max="currentMonthValue"
            @change="onMonthChange"
          />
          <input
            v-else
            type="date"
            class="period-picker-input"
            v-model="selectedDay"
            :max="currentDayValue"
            @change="onDayChange"
          />
        </div>
      </div>

      <!-- REPORT ACTIONS — Copy / CSV / Print the whole report -->
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

      <!-- SALES ACTIVITY -->
      <div class="section">
        <div class="section-header">
          <p class="card-eyebrow">Overview</p>
          <h2 class="section-title">Sales Activity &mdash; {{ periodLabel }}</h2>
        </div>
        <div class="kpi-strip">
          <div
            v-for="(card, idx) in salesActivityDisplay"
            :key="card.label"
            class="kpi-card"
            :class="kpiAccent(idx).cardClass"
          >
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap" :class="kpiAccent(idx).iconClass">
                <i class="fa-solid" :class="kpiAccent(idx).icon"></i>
              </div>
              <div class="kpi-body">
                <p class="kpi-label">{{ card.label }}</p>
                <p class="kpi-value" :class="kpiAccent(idx).valueClass">{{ formatKpiValue(card) }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar" :class="kpiAccent(idx).barClass"></div>
          </div>
        </div>
      </div>

      <!-- INVENTORY SUMMARY -->
      <div class="section">
        <div class="section-header">
          <p class="card-eyebrow">Stock Status</p>
          <h2 class="section-title">Inventory Summary</h2>
        </div>
        <div class="kpi-strip">
          <div
            v-for="box in inventoryDisplay"
            :key="box.label"
            class="kpi-card"
            :class="box.isRed ? 'kpi-card--alert' : ''"
          >
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap" :class="box.isRed ? 'kpi-icon-wrap--red' : 'kpi-icon-wrap--violet'">
                <i class="fa-solid" :class="box.isRed ? 'fa-triangle-exclamation' : 'fa-boxes-stacked'"></i>
              </div>
              <div class="kpi-body">
                <p class="kpi-label">{{ box.label }}</p>
                <p class="kpi-value" :class="box.isRed ? 'kpi-value--red' : 'kpi-value--violet'">{{ formatNumber(box.num) }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar" :class="box.isRed ? 'kpi-accent-bar--red' : 'kpi-accent-bar--violet'"></div>
          </div>
        </div>
      </div>

      <!-- TOP SELLING ITEMS — now a ranked bar-list, matching Home dashboard -->
      <div class="section">
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Performance</p>
              <h2 class="card-title">Top Selling Items &mdash; {{ periodLabel }}</h2>
            </div>
            <div class="ts-controls">
              <div class="metric-toggle">
                <button
                  type="button"
                  class="metric-toggle-btn"
                  :class="{ 'metric-toggle-btn--active': topMetric === 'sales' }"
                  @click="setTopMetric('sales')"
                >
                  <i class="fa-solid fa-peso-sign"></i>Sales
                </button>
                <button
                  type="button"
                  class="metric-toggle-btn"
                  :class="{ 'metric-toggle-btn--active': topMetric === 'qty' }"
                  @click="setTopMetric('qty')"
                >
                  <i class="fa-solid fa-box"></i>Qty
                </button>
              </div>
            </div>
          </div>
          <p class="chart-hint">Hover an item for the full breakdown · Click to pin it below</p>

          <div class="top-list">
            <div
              v-for="item in rankedTopItems"
              :key="item.ProductName"
              class="top-row"
              :class="[
                `top-row--rank${item.rank <= 3 ? item.rank : 'default'}`,
                { 'top-row--pinned': selectedTopItem && selectedTopItem.ProductName === item.ProductName },
              ]"
              :style="{ transitionDelay: (item.rank - 1) * 40 + 'ms' }"
              @click="toggleSelectedTopItem(item)"
            >
              <div class="top-row-rank" :class="`top-row-rank--${item.rank <= 3 ? item.rank : 'default'}`">
                <i v-if="item.rank === 1" class="fa-solid fa-crown"></i>
                <span v-else>{{ item.rank }}</span>
              </div>

              <div class="top-row-main">
                <div class="top-row-head">
                  <span class="top-row-name">{{ item.ProductName }}</span>
                  <span class="top-row-value">
                    {{ topMetric === 'sales' ? '₱' + formatNumber(item.total_sales) : formatNumber(item.total_quantity) + ' units' }}
                  </span>
                </div>
                <div class="top-row-bar-track">
                  <div
                    class="top-row-bar-fill"
                    :class="`top-row-bar-fill--rank${item.rank <= 3 ? item.rank : 'default'}`"
                    :style="{ width: (barsReady ? item.barPct : 0) + '%' }"
                  ></div>
                </div>
              </div>

              <div class="top-row-tooltip">
                <div class="trt-row"><span>Total Sales</span><strong>₱{{ formatNumber(item.total_sales) }}</strong></div>
                <div class="trt-row"><span>Qty Sold</span><strong>{{ formatNumber(item.total_quantity) }}</strong></div>
                <div class="trt-row"><span>Avg. Price</span><strong>₱{{ formatNumber(avgPrice(item)) }}</strong></div>
                <div class="trt-row"><span>Category</span><strong>{{ item.CategoryName || '—' }}</strong></div>
              </div>
            </div>

            <p v-if="!rankedTopItems.length" class="top-empty">No sales recorded for this period yet.</p>
          </div>

          <transition name="fade">
            <div v-if="selectedTopItem" class="top-item-detail">
              <button class="tid-close" @click="selectedTopItem = null" aria-label="Close details">
                <i class="fa-solid fa-xmark"></i>
              </button>
              <div class="tid-row tid-row--head">
                <span class="tid-rank">#{{ selectedTopItem.rank }}</span>
                <strong class="tid-name">{{ selectedTopItem.ProductName }}</strong>
              </div>
              <div class="tid-stats">
                <div class="tid-stat">
                  <span class="tid-label">Total Sales</span>
                  <strong>₱{{ formatNumber(selectedTopItem.total_sales) }}</strong>
                </div>
                <div class="tid-stat">
                  <span class="tid-label">Qty Sold</span>
                  <strong>{{ formatNumber(selectedTopItem.total_quantity) }}</strong>
                </div>
                <div class="tid-stat">
                  <span class="tid-label">Avg. Price</span>
                  <strong>₱{{ formatNumber(avgPrice(selectedTopItem)) }}</strong>
                </div>
              </div>
            </div>
          </transition>
        </div>
      </div>

      <!-- SALES CHART -->
      <div class="section">
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Trend Analysis</p>
              <h2 class="card-title">
                {{ mode === 'month' ? 'Daily Sales' : 'Hourly Sales' }} &mdash; {{ periodLabel }}
              </h2>
            </div>
            <div class="legend-row">
              <span
                class="legend-chip legend-chip--orange"
                :class="{ 'legend-chip--off': legendState[0] }"
                @click="toggleDataset(0)"
              >
                <span class="legend-dot"></span>{{ mode === 'month' ? 'Daily Sales' : 'Hourly Sales' }}
              </span>
              <span
                class="legend-chip legend-chip--green"
                :class="{ 'legend-chip--off': legendState[1] }"
                @click="toggleDataset(1)"
              >
                <span class="legend-dot"></span>{{ mode === 'month' ? 'Daily Profit' : 'Hourly Profit' }}
              </span>
            </div>
          </div>

          <div class="trend-totals">
            <div class="monthly-total">
              <span class="monthly-total-label">
                Total Sales<span v-if="salesTrend.available"> · vs {{ comparisonLabel }}</span>
              </span>
              <span class="monthly-total-value">
                ₱{{ price(displayedMonthlyTotal) }}
                <span
                  v-if="salesTrend.available"
                  class="trend-badge"
                  :class="salesTrend.direction"
                  :title="`vs ${comparisonLabel}`"
                >
                  <i :class="trendIcon"></i>{{ salesTrend.percent }}%
                </span>
              </span>
            </div>
            <div class="monthly-total">
              <span class="monthly-total-label">Total Profit · {{ profitMarginPct }}% margin</span>
              <span class="monthly-total-value monthly-total-value--green">₱{{ price(displayedMonthlyProfit) }}</span>
            </div>
          </div>

          <p class="chart-hint">Hover the chart for exact values, date, and margin · Click a legend chip to isolate a series</p>
          <div class="chart-area chart-area--tall">
            <canvas id="monthlySalesChart"></canvas>
          </div>
        </div>
      </div>

    </div>
  </Layout>
</template>

<script>
// Module-level (shared across instances, executed once) — same pattern
// as the Home dashboard: a uniquely namespaced plugin id avoids
// colliding with any other same-named plugin elsewhere in the app, and
// every hook is wrapped in try/catch so a decorative overlay can never
// crash the chart's render loop.
const reportsCrosshairPlugin = {
  id: 'reportsCrosshair',
  afterDraw(chart) {
    try {
      const active = typeof chart.getActiveElements === 'function' ? chart.getActiveElements() : []
      if (!active || !active.length) return
      const { ctx, chartArea } = chart
      const x = active[0].element.x
      ctx.save()
      ctx.beginPath()
      ctx.setLineDash([4, 4])
      ctx.lineWidth = 1
      ctx.strokeStyle = 'rgba(148,163,184,0.55)'
      ctx.moveTo(x, chartArea.top)
      ctx.lineTo(x, chartArea.bottom)
      ctx.stroke()
      ctx.restore()
    } catch (e) {
      /* no-op */
    }
  },
}

export default { name: 'ReportsView' }
</script>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import Layout from '@/components/Layout.vue'
import Chart from 'chart.js/auto'
import api from '@/api/axios'

/* ── mode + period state ── */
// "month" or "day" — a single source of truth for which lens the whole
// page is viewed through. Everything (KPIs, Top Selling, chart) reflects
// whichever mode is active; there is only ever ONE picker visible at a
// time, matching whichever mode button is selected.
const mode = ref('month')

function currentMonthStr() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}
function currentDayStr() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const currentMonthValue = currentMonthStr() // <input type="month"> max= — no picking a future month
const currentDayValue   = currentDayStr()   // <input type="date">  max= — no picking a future day

const selectedMonth = ref(currentMonthStr())
const selectedDay   = ref(currentDayStr())

const periodLabel = ref('') // human-readable label from the API, e.g. "June 2026" or "June 14, 2026"

// Timestamp shown in the print-only letterhead — computed fresh each
// time printReport() is invoked so it reflects when the report was
// actually generated, not when the page first loaded.
const printGeneratedAt = ref('')

/* ── reactive data (shared shape across both modes) ── */
// "Raw" copies hold exactly what the API sends (label + pre-formatted
// value, e.g. "₱12,345.00") — used for Copy/CSV export, where the
// original formatting should be reproduced verbatim.
const salesActivityRaw = ref([
  { label: "This Month's Sales",      value: '₱0.00' },
  { label: 'Items Sold This Month',   value: 0 },
  { label: 'Transactions This Month', value: 0 },
  { label: 'Monthly Profit',          value: '₱0.00' },
])
const inventorySummaryRaw = ref([
  { label: 'Quantity in Hand', value: 0 },
  { label: 'Low Stock Items',  value: 0, isRed: true },
  { label: 'Total Items',      value: 0 },
  { label: 'Active Items',     value: 0 },
])

// "Display" copies drive the KPI cards on screen. Each entry holds a
// plain numeric `num` that eases from its old value to the new one
// whenever the period changes (see animateListTo), so the cards stay
// perfectly still — only the digits move, the same way Home's headline
// KPIs behave — instead of the whole card fading/shifting.
const salesActivityDisplay = ref([
  { label: "This Month's Sales",      format: 'currency', num: 0 },
  { label: 'Items Sold This Month',   format: 'number',   num: 0 },
  { label: 'Transactions This Month', format: 'number',   num: 0 },
  { label: 'Monthly Profit',          format: 'currency', num: 0 },
])
const inventoryDisplay = ref([
  { label: 'Quantity in Hand', isRed: false, num: 0 },
  { label: 'Low Stock Items',  isRed: true,  num: 0 },
  { label: 'Total Items',      isRed: false, num: 0 },
  { label: 'Active Items',     isRed: false, num: 0 },
])

const topSellingItems = ref([])
const monthlySales    = ref([])
const monthlyTotal    = ref(0)

// Profit series for the trend chart's second line. Tries a couple of
// likely field names since this endpoint's payload shape wasn't fully
// documented for profit-by-day — falls back to an empty series (profit
// line just renders flat at 0) rather than breaking anything.
const monthlyProfitSeries = ref([])

// Merges monthlySales + monthlyProfitSeries by date into one row set,
// the same way the Home dashboard merges its sales/profit arrays.
const chartRows = computed(() => {
  const profitMap = {}
  monthlyProfitSeries.value.forEach((p) => { profitMap[p.date] = num(p.profit ?? p.total) })
  return monthlySales.value.map((s) => ({
    date: s.date,
    total: num(s.total),
    profit: profitMap[s.date] ?? num(s.profit) ?? 0,
  }))
})

const monthlyProfitTotal = computed(() => chartRows.value.reduce((s, r) => s + r.profit, 0))

const profitMarginPct = computed(() => {
  const total = num(monthlyTotal.value)
  if (!total) return '0.0'
  return ((monthlyProfitTotal.value / total) * 100).toFixed(1)
})

// Animated "counting up" copy of monthlyProfitTotal — same treatment as
// displayedMonthlyTotal below.
const displayedMonthlyProfit = ref(0)

// Which trend-chart datasets are currently hidden via the legend chips
// (keyed by dataset index) — mirrors Home's toggleDataset legend state.
const legendState = ref({})

// Animated "counting up" copy of monthlyTotal, so switching periods
// feels alive instead of the number just snapping to place.
const displayedMonthlyTotal = ref(0)

// Period-over-period comparison for the Total Sales trend badge.
// null means "no comparison available yet."
const previousPeriodTotal = ref(null)
const comparisonLabel     = ref('')

// Top Selling leaderboard: which metric drives the bar/ranking, the
// item currently pinned by a click, and whether bars are allowed to be
// at their real width yet (toggled false→true to trigger the "grow in"
// animation — mirrors the Home dashboard leaderboard).
const topMetric       = ref('sales')
const barsReady       = ref(false)
const selectedTopItem = ref(null)

// Report actions (Copy / CSV / Print): 'idle' | 'copying' | 'copied' | 'error'
const copyState = ref('idle')

let chartInstance  = null
let themeObserver  = null

/* ── accent map ── */
function kpiAccent(idx) {
  return [
    { icon: 'fa-peso-sign',      iconClass: 'kpi-icon-wrap--orange', valueClass: '',                  barClass: 'kpi-accent-bar--orange', cardClass: 'kpi-card--featured' },
    { icon: 'fa-box-open',       iconClass: 'kpi-icon-wrap--violet', valueClass: 'kpi-value--violet', barClass: 'kpi-accent-bar--violet', cardClass: '' },
    { icon: 'fa-receipt',        iconClass: 'kpi-icon-wrap--blue',   valueClass: 'kpi-value--blue',   barClass: 'kpi-accent-bar--blue',   cardClass: '' },
    { icon: 'fa-arrow-trend-up', iconClass: 'kpi-icon-wrap--green',  valueClass: 'kpi-value--green',  barClass: 'kpi-accent-bar--green',  cardClass: '' },
  ][idx % 4]
}

/* ── numeric helpers ── */
// The API can send currency figures as strings (e.g. "1234.50"). Using
// "+" or "-" directly on those either concatenates or produces NaN —
// every arithmetic touchpoint below routes through this first.
function num(v) {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

// Eases a ref from `from` to `to` over `duration` ms.
function animateValue(targetRef, from, to, duration = 750) {
  const start = performance.now()
  const tick = (now) => {
    const progress = Math.min((now - start) / duration, 1)
    const eased = 1 - Math.pow(1 - progress, 3)
    targetRef.value = from + (to - from) * eased
    if (progress < 1) requestAnimationFrame(tick)
    else targetRef.value = to
  }
  requestAnimationFrame(tick)
}

/* ── KPI display helpers ──
   Sales Activity arrives pre-formatted from the API (e.g. "₱12,345.00").
   To animate it we pull the raw number back out, ease it, then
   reformat for display — same visual result, just alive in between. */
function parseDisplayNumber(v) {
  if (typeof v === 'number') return v
  const cleaned = String(v ?? '').replace(/[^\d.-]/g, '')
  const n = Number(cleaned)
  return Number.isFinite(n) ? n : 0
}
function isCurrencyString(v) {
  return typeof v === 'string' && v.trim().startsWith('₱')
}
function formatKpiValue(card) {
  return card.format === 'currency' ? '₱' + price(card.num) : formatNumber(card.num)
}

// Eases every entry in a reactive list of {..., num} objects from its
// current value to a new target in lockstep — used for both KPI strips
// so the cards themselves never re-render, only the numbers inside them.
function animateListTo(displayArray, toValues, duration = 750) {
  const start = performance.now()
  const fromValues = displayArray.map((item) => item.num)
  const tick = (now) => {
    const progress = Math.min((now - start) / duration, 1)
    const eased = 1 - Math.pow(1 - progress, 3)
    for (let i = 0; i < displayArray.length; i++) {
      displayArray[i].num = fromValues[i] + (toValues[i] - fromValues[i]) * eased
    }
    if (progress < 1) requestAnimationFrame(tick)
    else {
      for (let i = 0; i < displayArray.length; i++) displayArray[i].num = toValues[i]
    }
  }
  requestAnimationFrame(tick)
}

// Rebuilds salesActivityDisplay to match the labels/order the API just
// sent, carrying over each card's current animated number as the
// starting point (matched by label) so a period switch eases smoothly
// rather than snapping — then animates every entry to its new target.
function updateSalesActivityDisplay(items) {
  const prevByLabel = {}
  salesActivityDisplay.value.forEach((item) => { prevByLabel[item.label] = item })

  const targets = items.map((item) => ({
    label: item.label,
    format: isCurrencyString(item.value) ? 'currency' : 'number',
    to: parseDisplayNumber(item.value),
  }))

  salesActivityDisplay.value = targets.map((t) => ({
    label: t.label,
    format: t.format,
    num: prevByLabel[t.label]?.num ?? t.to,
  }))

  animateListTo(salesActivityDisplay.value, targets.map((t) => t.to))
}

// Same idea for the Inventory Summary boxes, whose values are already
// plain numbers from the API (no currency parsing needed).
function updateInventoryDisplay(items) {
  const prevByLabel = {}
  inventoryDisplay.value.forEach((item) => { prevByLabel[item.label] = item })

  const targets = items.map((item) => ({
    label: item.label,
    isRed: !!item.isRed,
    to: num(item.value),
  }))

  inventoryDisplay.value = targets.map((t) => ({
    label: t.label,
    isRed: t.isRed,
    num: prevByLabel[t.label]?.num ?? t.to,
  }))

  animateListTo(inventoryDisplay.value, targets.map((t) => t.to))
}

/* ── period-over-period comparison ── */
// Works out the request params + a human label for "the period right
// before the one currently selected": the previous calendar month in
// Month mode, the previous calendar day in Day mode. Because both modes
// use an arbitrary date picker (not a fixed preset list), this is
// well-defined no matter which month/day is currently selected.
function previousParams() {
  if (mode.value === 'month') {
    const [year, month] = selectedMonth.value.split('-').map(Number)
    let py = year, pm = month - 1
    if (pm < 1) { pm = 12; py -= 1 }
    const label = new Date(py, pm - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
    return { params: { mode: 'month', year: py, month: pm }, label }
  }
  const d = new Date(`${selectedDay.value}T00:00:00`)
  d.setDate(d.getDate() - 1)
  const prevDateStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  const label = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  return { params: { mode: 'day', date: prevDateStr }, label }
}

async function fetchPreviousPeriodTotal() {
  try {
    const { params, label } = previousParams()
    const res = await api.get('/api/reports', { params })
    previousPeriodTotal.value = num(res.data?.monthly_total)
    comparisonLabel.value = label
  } catch (err) {
    console.error('Previous period fetch error:', err)
    previousPeriodTotal.value = null
    comparisonLabel.value = ''
  }
}

const salesTrend = computed(() => {
  if (previousPeriodTotal.value === null) {
    return { direction: 'flat', percent: '0.0', available: false }
  }
  const prev = num(previousPeriodTotal.value)
  const current = num(monthlyTotal.value)
  if (!prev) {
    return { direction: current > 0 ? 'up' : 'flat', percent: '0.0', available: true }
  }
  const change = ((current - prev) / prev) * 100
  if (!Number.isFinite(change)) return { direction: 'flat', percent: '0.0', available: true }
  return {
    direction: change > 0.5 ? 'up' : change < -0.5 ? 'down' : 'flat',
    percent: Math.abs(change).toFixed(1),
    available: true,
  }
})

const trendIcon = computed(() => {
  if (salesTrend.value.direction === 'up') return 'fa-solid fa-arrow-trend-up'
  if (salesTrend.value.direction === 'down') return 'fa-solid fa-arrow-trend-down'
  return 'fa-solid fa-minus'
})

/* ── Top Selling leaderboard (mirrors Home dashboard) ── */
// Builds the ranked leaderboard rows: sorts by whichever metric is
// active, caps at 8, and precomputes the bar width (relative to the
// leader) and each item's share of the displayed total so the template
// stays free of per-render math.
const rankedTopItems = computed(() => {
  const metric = topMetric.value
  const items = topSellingItems.value
    .map((i) => ({
      ...i,
      total_sales: num(i.total_sales),
      total_quantity: num(i.total_quantity),
    }))
    .sort((a, b) => (metric === 'sales' ? b.total_sales - a.total_sales : b.total_quantity - a.total_quantity))
    .slice(0, 8)

  const valueOf = (i) => (metric === 'sales' ? i.total_sales : i.total_quantity)
  const maxVal = Math.max(...items.map(valueOf), 1)
  const totalVal = items.reduce((s, i) => s + valueOf(i), 0) || 1

  return items.map((i, idx) => ({
    ...i,
    rank: idx + 1,
    barPct: Math.max((valueOf(i) / maxVal) * 100, 2),
    sharePct: ((valueOf(i) / totalVal) * 100).toFixed(1),
  }))
})

function formatNumber(n) {
  return Math.round(num(n)).toLocaleString()
}

function avgPrice(item) {
  if (!item || !item.total_quantity) return 0
  return item.total_sales / item.total_quantity
}

// Drops every leaderboard bar to 0 width, then flips them to their real
// width on the next paint so the CSS width transition actually has
// something to animate from — without this they'd just appear at full
// size with no motion.
function animateBarsIn() {
  barsReady.value = false
  nextTick(() => {
    requestAnimationFrame(() => {
      barsReady.value = true
    })
  })
}

// Switches which metric ranks/sizes the leaderboard and replays the bar
// "grow in" animation so the change reads clearly.
function setTopMetric(metric) {
  if (topMetric.value === metric) return
  topMetric.value = metric
  selectedTopItem.value = null
  animateBarsIn()
}

// Clicking a pinned row again un-pins it; clicking a different row
// swaps the pin.
function toggleSelectedTopItem(item) {
  selectedTopItem.value = selectedTopItem.value?.ProductName === item.ProductName ? null : item
}

/* ── report actions: Copy / CSV / Print ── */
// Renders a small plain-text/table snapshot of everything currently on
// screen (KPIs, inventory, top selling, and the trend series) so the
// person can paste it into an email, chat, or spreadsheet without
// needing to screenshot the page.
function buildReportText() {
  const lines = []
  lines.push(`Sales Report — ${periodLabel.value || ''}`)
  lines.push('')

  lines.push('Sales Activity')
  salesActivityRaw.value.forEach((item) => lines.push(`  ${item.label}: ${item.value}`))
  lines.push('')

  lines.push('Inventory Summary')
  inventorySummaryRaw.value.forEach((item) => lines.push(`  ${item.label}: ${item.value}`))
  lines.push('')

  lines.push('Top Selling Items')
  if (rankedTopItems.value.length) {
    rankedTopItems.value.forEach((item) => {
      lines.push(
        `  ${item.rank}. ${item.ProductName} — ₱${formatNumber(item.total_sales)} sales, ${formatNumber(item.total_quantity)} units${item.CategoryName ? ` (${item.CategoryName})` : ''}`
      )
    })
  } else {
    lines.push('  No sales recorded for this period.')
  }
  lines.push('')

  lines.push(mode.value === 'month' ? 'Daily Sales' : 'Hourly Sales')
  chartRows.value.forEach((row) => {
    lines.push(`  ${row.date}: Sales ₱${formatNumber(row.total)} · Profit ₱${formatNumber(row.profit)}`)
  })
  lines.push('')

  lines.push(`Total Sales This Period: ₱${price(monthlyTotal.value)}`)
  if (salesTrend.value.available) {
    lines.push(`vs ${comparisonLabel.value}: ${salesTrend.value.direction === 'up' ? '+' : salesTrend.value.direction === 'down' ? '-' : ''}${salesTrend.value.percent}%`)
  }
  lines.push(`Total Profit This Period: ₱${price(monthlyProfitTotal.value)} (${profitMarginPct.value}% margin)`)

  return lines.join('\n')
}

// Copies the same snapshot as CSV, but as a CSV file download instead
// of clipboard text — handy for opening straight in Excel/Sheets.
function csvEscape(value) {
  const s = String(value ?? '')
  return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s
}

function buildReportCSV() {
  const rows = []
  rows.push(['Sales Report', periodLabel.value || ''])
  rows.push([])

  rows.push(['Sales Activity'])
  salesActivityRaw.value.forEach((item) => rows.push([item.label, item.value]))
  rows.push([])

  rows.push(['Inventory Summary'])
  inventorySummaryRaw.value.forEach((item) => rows.push([item.label, item.value]))
  rows.push([])

  rows.push(['Top Selling Items'])
  rows.push(['Rank', 'Product', 'Category', 'Total Sales', 'Qty Sold', 'Avg. Price'])
  rankedTopItems.value.forEach((item) => {
    rows.push([item.rank, item.ProductName, item.CategoryName || '', item.total_sales, item.total_quantity, avgPrice(item).toFixed(2)])
  })
  rows.push([])

  rows.push([mode.value === 'month' ? 'Daily Sales' : 'Hourly Sales'])
  rows.push(['Date', 'Sales', 'Profit'])
  chartRows.value.forEach((row) => rows.push([row.date, row.total, row.profit]))
  rows.push([])

  rows.push(['Total Sales This Period', num(monthlyTotal.value)])
  if (salesTrend.value.available) {
    rows.push([`vs ${comparisonLabel.value}`, `${salesTrend.value.direction === 'up' ? '+' : salesTrend.value.direction === 'down' ? '-' : ''}${salesTrend.value.percent}%`])
  }
  rows.push(['Total Profit This Period', monthlyProfitTotal.value])
  rows.push(['Profit Margin', `${profitMarginPct.value}%`])

  return rows.map((r) => r.map(csvEscape).join(',')).join('\n')
}

function reportFileSlug() {
  const base = periodLabel.value || (mode.value === 'month' ? selectedMonth.value : selectedDay.value)
  return `sales-report-${String(base).replace(/[^a-z0-9]+/gi, '-').toLowerCase()}`
}

async function copyReport() {
  if (copyState.value === 'copying') return
  copyState.value = 'copying'
  try {
    const text = buildReportText()
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(text)
    } else {
      // Fallback for browsers/contexts without the async Clipboard API
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

/* ── API ── */
// FIX: previously hardcoded to December 2025 with no way to change it.
// Now a single endpoint (?mode=month|day) drives every section of the
// page — KPIs, Top Selling Items, and the chart — based on whichever
// mode + period is currently selected.
async function loadReports() {
  try {
    const params = mode.value === 'month'
      ? (() => {
          const [year, month] = selectedMonth.value.split('-').map(Number)
          return { mode: 'month', year, month }
        })()
      : { mode: 'day', date: selectedDay.value }

    const prevDisplayed = displayedMonthlyTotal.value
    const prevDisplayedProfit = displayedMonthlyProfit.value

    // Main period + comparison period load in parallel so the trend
    // badge doesn't add extra wait time on top of the normal fetch.
    const [res] = await Promise.all([
      api.get('/api/reports', { params }),
      fetchPreviousPeriodTotal(),
    ])

    salesActivityRaw.value      = res.data.sales_activity    ?? salesActivityRaw.value
    inventorySummaryRaw.value   = res.data.inventory_summary ?? inventorySummaryRaw.value
    updateSalesActivityDisplay(salesActivityRaw.value)
    updateInventoryDisplay(inventorySummaryRaw.value)
    topSellingItems.value       = res.data.top_selling       ?? []
    monthlySales.value          = res.data.monthly_sales     ?? []
    monthlyProfitSeries.value   = res.data.monthly_profit ?? res.data.daily_profit ?? []
    monthlyTotal.value          = res.data.monthly_total     ?? 0
    periodLabel.value           = res.data.selected_period?.label ?? ''

    selectedTopItem.value = null

    animateValue(displayedMonthlyTotal, prevDisplayed, num(monthlyTotal.value))
    animateValue(displayedMonthlyProfit, prevDisplayedProfit, monthlyProfitTotal.value)

    buildChart()

    await nextTick()
    animateBarsIn()
  } catch (err) {
    console.error('Reports API error:', err)
  }
}

async function setMode(next) {
  if (mode.value === next) return
  mode.value = next
  await loadReports()
}

async function onMonthChange() {
  if (!selectedMonth.value) return
  await loadReports()
}

async function onDayChange() {
  if (!selectedDay.value) return
  await loadReports()
}

const price = (v) => num(v).toLocaleString('en-PH', { minimumFractionDigits: 2 })

/* ── theme helpers ── */
function isDark() {
  return document.documentElement.getAttribute('data-theme') === 'dark'
}
function chartColors() {
  const dark = isDark()
  return {
    grid:     dark ? 'rgba(255,255,255,0.05)' : 'rgba(168,162,158,0.10)',
    tick:     dark ? '#8B90A8'                : '#A8A29E',
    ttBg:     dark ? '#1E2130'                : '#1C1917',
    ttTitle:  dark ? '#8B90A8'                : '#A8A29E',
    ttBody:   dark ? '#E8EAF0'                : '#F5F5F4',
    ttBorder: dark ? 'rgba(255,255,255,0.08)' : 'rgba(255,255,255,0.06)',
  }
}

// Formats an x-axis point label. Hourly buckets sometimes arrive as
// bare "HH:MM" strings (not a parseable date) — left as-is. Everything
// else is parsed and formatted per the active mode.
function formatPointLabel(raw) {
  if (!raw) return ''
  if (/^\d{1,2}:\d{2}$/.test(String(raw))) return raw
  const d = new Date(raw)
  if (Number.isNaN(d.getTime())) return String(raw)
  return mode.value === 'month'
    ? d.toLocaleDateString('en-US', { month: 'short', day: '2-digit' })
    : d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

// Shared onHover so the chart shows a pointer cursor over hoverable
// points — a small cue that the data underneath is inspectable.
function hoverCursor(evt, elements) {
  try {
    if (evt?.native?.target) {
      evt.native.target.style.cursor = elements.length ? 'pointer' : 'default'
    }
  } catch (e) {
    /* no-op */
  }
}

// Shows/hides a trend-chart dataset on click, so the legend chips
// double as real controls instead of static labels — same pattern as
// the Home dashboard's toggleDataset.
function toggleDataset(datasetIndex) {
  if (!chartInstance) return
  const meta = chartInstance.getDatasetMeta(datasetIndex)
  meta.hidden = meta.hidden === null ? !chartInstance.isDatasetVisible(datasetIndex) : !meta.hidden
  chartInstance.update()
  legendState.value = { ...legendState.value, [datasetIndex]: meta.hidden }
}

/* ── chart ── */
function buildChart() {
  const el = document.getElementById('monthlySalesChart')
  if (!el) return

  try {
    // Safety net: destroy both our tracked instance and any orphaned
    // Chart.js instance still attached to this canvas internally (can
    // happen if a previous build threw partway through and never
    // reached the `chartInstance = new Chart(...)` assignment).
    if (chartInstance) { chartInstance.destroy(); chartInstance = null }
    const orphan = Chart.getChart(el)
    if (orphan) orphan.destroy()
    legendState.value = {}

    const c   = chartColors()
    const ctx = el.getContext('2d')
    const salesGradient = ctx.createLinearGradient(0, 0, 0, 280)
    salesGradient.addColorStop(0, 'rgba(234,88,12,0.24)')
    salesGradient.addColorStop(1, 'rgba(234,88,12,0)')

    const profitGradient = ctx.createLinearGradient(0, 0, 0, 280)
    profitGradient.addColorStop(0, 'rgba(16,185,129,0.22)')
    profitGradient.addColorStop(1, 'rgba(16,185,129,0)')

    const rows  = chartRows.value
    const total = num(monthlyTotal.value)
    const salesLabel  = mode.value === 'month' ? 'Daily Sales'  : 'Hourly Sales'
    const profitLabel = mode.value === 'month' ? 'Daily Profit' : 'Hourly Profit'

    chartInstance = new Chart(el, {
      type: 'line',
      data: {
        labels: rows.map((x) => formatPointLabel(x.date)),
        datasets: [
          {
            label: salesLabel,
            data:  rows.map((x) => x.total),
            borderColor: '#EA580C',
            backgroundColor: salesGradient,
            borderWidth: 2.5, tension: 0.42, fill: true,
            pointRadius: 0, pointHoverRadius: 6, pointHitRadius: 14,
            pointHoverBackgroundColor: '#EA580C',
            pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
          },
          {
            label: profitLabel,
            data:  rows.map((x) => x.profit),
            borderColor: '#10b981',
            backgroundColor: profitGradient,
            borderWidth: 2.5, tension: 0.42, fill: true,
            pointRadius: 0, pointHoverRadius: 6, pointHitRadius: 14,
            pointHoverBackgroundColor: '#10b981',
            pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
          },
        ],
      },
      plugins: [reportsCrosshairPlugin],
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 800, easing: 'easeOutQuart' },
        interaction: { mode: 'index', intersect: false },
        onHover: hoverCursor,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: c.ttBg,
            titleColor: c.ttTitle,
            titleFont: { size: 11, weight: '600', family: 'Inter' },
            bodyColor: c.ttBody,
            bodyFont: { size: 12.5, weight: '600', family: 'Inter' },
            footerColor: c.ttTitle,
            footerFont: { size: 10.5, weight: '500', family: 'Inter' },
            footerMarginTop: 6,
            padding: 12, cornerRadius: 10, caretSize: 6,
            borderColor: c.ttBorder, borderWidth: 1,
            displayColors: true,
            boxWidth: 8, boxHeight: 8, boxPadding: 4,
            callbacks: {
              title: (items) => formatPointLabel(rows[items[0]?.dataIndex]?.date),
              label: (ctx) => ` ${ctx.dataset.label}: ₱${Number(ctx.raw).toLocaleString()}`,
              footer: (items) => {
                const idx = items[0]?.dataIndex
                const row = rows[idx]
                if (!row) return ''
                const lines = []
                if (total) lines.push(`${((row.total / total) * 100).toFixed(1)}% of period sales`)
                if (row.total) lines.push(`Margin: ${((row.profit / row.total) * 100).toFixed(1)}%`)
                return lines
              },
            },
          },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: c.tick, font: { size: 11, family: 'Inter' }, maxRotation: 0 },
            border: { display: false },
          },
          y: {
            grid: { color: c.grid, drawBorder: false },
            ticks: { color: c.tick, font: { size: 11, family: 'Inter' }, callback: (v) => '₱' + Number(v).toLocaleString() },
            border: { display: false },
          },
        },
      },
    })
  } catch (err) {
    console.error('Chart render failed:', err)
  }
}

onMounted(async () => {
  await loadReports()

  /* Re-build chart on theme toggle */
  themeObserver = new MutationObserver(buildChart)
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] })
})

onBeforeUnmount(() => {
  if (themeObserver) themeObserver.disconnect()
  if (chartInstance) chartInstance.destroy()
})
</script>

<!-- ══════════════════════════════════════════════════════
     SCOPED styles — Vue component layout only.
══════════════════════════════════════════════════════ -->
<style scoped>
.reports {
  /* ── local aliases → global tokens ── */
  --rp-bg:             var(--c-bg);
  --rp-surface:        var(--c-surface);
  --rp-surface-raised: var(--c-surface-raised);
  --rp-surface-sunken: var(--c-surface-sunken);
  --rp-border:         var(--c-border);
  --rp-border-strong:  var(--c-border-strong);
  --rp-text-primary:   var(--c-text-primary);
  --rp-text-secondary: var(--c-text-secondary);
  --rp-text-muted:     var(--c-text-muted);
  --rp-text-faint:     var(--c-text-faint);
  --rp-accent:         var(--c-accent);
  --rp-accent-soft:    var(--c-accent-soft);
  --rp-accent-border:  var(--c-accent-border);
  --rp-shadow:         var(--c-shadow-sm);
  --rp-shadow-lg:      var(--c-shadow-md);

  --rp-green:       #10b981;
  --rp-green-soft:  rgba(16,185,129,0.12);
  --rp-blue:        #3b82f6;
  --rp-violet:      #6366f1;
  --rp-red:         #f43f5e;
  --rp-red-soft:    rgba(244,63,94,0.12);

  /* icon bg — light mode pastels */
  --rp-green-icon:  #D1FAE5;
  --rp-blue-icon:   #DBEAFE;
  --rp-violet-icon: #E0E7FF;
  --rp-red-icon:    #FFE4E6;

  --radius: 16px;

  min-height: 100%;
  background: var(--rp-bg);
  padding: 28px 28px 56px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 28px;
  transition: background-color 0.22s ease;
}

/* icon bg — dark mode */
html[data-theme="dark"] .reports {
  --rp-green-icon:  rgba(16,185,129,0.18);
  --rp-blue-icon:   rgba(59,130,246,0.18);
  --rp-violet-icon: rgba(99,102,241,0.18);
  --rp-red-icon:    rgba(244,63,94,0.18);
  --rp-green-soft:  rgba(16,185,129,0.16);
  --rp-red-soft:    rgba(244,63,94,0.16);
}

/* PRINT-ONLY LETTERHEAD — hidden on screen, shown at the top of the
   printed page only (see @media print below). */
.print-header { display: none; }

/* PAGE HEADER */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--rp-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--rp-text-primary); letter-spacing: -.03em; margin: 0; transition: color 0.22s ease; }

/* REPORT ACTIONS — Copy / CSV / Print toolbar */
.report-toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}
.rt-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid var(--rp-border-strong);
  background: var(--rp-surface-sunken);
  color: var(--rp-text-secondary);
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 13px;
  font-weight: 600;
  padding: 9px 16px;
  border-radius: 11px;
  cursor: pointer;
  transition: background-color .16s ease, border-color .16s ease, color .16s ease, transform .12s ease, box-shadow .16s ease;
}
.rt-btn i { font-size: 12px; }
.rt-btn:hover:not(:disabled) {
  background: var(--rp-surface-raised);
  border-color: var(--rp-accent-border);
  color: var(--rp-text-primary);
  transform: translateY(-1px);
}
.rt-btn:disabled { opacity: .65; cursor: default; }
.rt-btn--primary {
  background: var(--rp-accent);
  border-color: var(--rp-accent);
  color: #fff;
  box-shadow: 0 2px 8px rgba(234,88,12,0.28);
}
.rt-btn--primary:hover {
  background: #FB923C;
  border-color: #FB923C;
  color: #fff;
}

.fade-enter-active, .fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-4px); }

/* PERIOD CONTROL — mode switch + single picker, side by side */
.period-control {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.period-mode-switch {
  display: flex;
  background: var(--rp-surface-sunken);
  border: 1px solid var(--rp-border);
  border-radius: 11px;
  padding: 3px;
  gap: 2px;
}
.period-mode-btn {
  border: none;
  background: transparent;
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--rp-text-muted);
  padding: 7px 14px;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color .18s ease, color .18s ease;
  white-space: nowrap;
}
.period-mode-btn:hover { color: var(--rp-text-secondary); }
.period-mode-btn--active {
  background: var(--rp-accent);
  color: #fff;
}
.period-mode-btn--active:hover { color: #fff; }

.period-picker-input {
  border: 1.5px solid var(--rp-border-strong);
  background: var(--rp-surface);
  border-radius: 11px;
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--rp-text-primary);
  outline: none;
  cursor: pointer;
  padding: 8px 12px;
  box-shadow: var(--rp-shadow);
  transition: border-color .15s ease;
}
.period-picker-input:hover,
.period-picker-input:focus { border-color: var(--rp-accent-border); }
.period-picker-input::-webkit-calendar-picker-indicator {
  cursor: pointer;
  border-radius: 4px;
  padding: 2px;
}
html[data-theme="dark"] .period-picker-input::-webkit-calendar-picker-indicator {
  filter: invert(1) brightness(1.6);
}

/* SECTIONS */
.section        { display: flex; flex-direction: column; gap: 14px; }
.section-header { display: flex; flex-direction: column; }
.section-title  { font-size: 17px; font-weight: 700; color: var(--rp-text-primary); margin: 0; letter-spacing: -.01em; transition: color 0.22s ease; }
.card-eyebrow   { font-size: 10.5px; font-weight: 700; letter-spacing: .12em; color: var(--rp-accent); text-transform: uppercase; margin: 0 0 4px; }

/* KPI STRIP */
.kpi-strip { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; }

.kpi-card {
  background: var(--rp-surface);
  border-radius: var(--radius);
  border: 1px solid var(--rp-border);
  box-shadow: var(--rp-shadow);
  padding: 20px 20px 16px;
  position: relative; overflow: hidden;
  transition: transform .2s ease, box-shadow .2s ease, background-color 0.22s ease, border-color 0.22s ease;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: var(--rp-shadow-lg); }
.kpi-card--featured { background: var(--rp-accent-soft); border-color: var(--rp-accent-border); }
.kpi-card--alert    { background: rgba(244,63,94,0.07); border-color: rgba(244,63,94,0.22); }
html[data-theme="dark"] .kpi-card--alert { background: rgba(244,63,94,0.12); border-color: rgba(244,63,94,0.30); }

.kpi-card-inner { display: flex; align-items: flex-start; gap: 12px; }

.kpi-icon-wrap {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; flex-shrink: 0;
  transition: background-color 0.22s ease;
}
.kpi-icon-wrap--orange { background: var(--rp-accent-soft);  color: var(--rp-accent); }
.kpi-icon-wrap--green  { background: var(--rp-green-icon);   color: var(--rp-green); }
.kpi-icon-wrap--blue   { background: var(--rp-blue-icon);    color: var(--rp-blue); }
.kpi-icon-wrap--violet { background: var(--rp-violet-icon);  color: var(--rp-violet); }
.kpi-icon-wrap--red    { background: var(--rp-red-icon);     color: var(--rp-red); }

.kpi-body  { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.kpi-label { font-size: 11px; font-weight: 600; color: var(--rp-text-muted); text-transform: uppercase; letter-spacing: .07em; }
.kpi-value { font-size: 22px; font-weight: 800; color: var(--rp-text-primary); letter-spacing: -.04em; line-height: 1.1; transition: color 0.22s ease; }
.kpi-value--green  { color: var(--rp-green); }
.kpi-value--blue   { color: var(--rp-blue); }
.kpi-value--violet { color: var(--rp-violet); }
.kpi-value--red    { color: var(--rp-red); }

.kpi-accent-bar {
  position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
  border-radius: 0 0 var(--radius) var(--radius);
}
.kpi-accent-bar--orange { background: linear-gradient(90deg, var(--rp-accent), #FB923C); }
.kpi-accent-bar--green  { background: var(--rp-green); }
.kpi-accent-bar--blue   { background: var(--rp-blue); }
.kpi-accent-bar--violet { background: var(--rp-violet); }
.kpi-accent-bar--red    { background: var(--rp-red); }

/* CARDS */
.card {
  background: var(--rp-surface);
  border-radius: var(--radius);
  border: 1px solid var(--rp-border);
  box-shadow: var(--rp-shadow);
  padding: 22px 24px 20px;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.card-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 8px; gap: 12px; flex-wrap: wrap;
}
.card-title { font-size: 15.5px; font-weight: 700; color: var(--rp-text-primary); margin: 0; letter-spacing: -.01em; transition: color 0.22s ease; }

/* Small caption telling people the chart/list responds to hover */
.chart-hint {
  font-size: 11px;
  color: var(--rp-text-faint);
  margin: 10px 0 16px;
}

/* ── TREND CHART — LEGEND ── */
.legend-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.legend-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 500;
  color: var(--rp-text-muted);
  background: var(--rp-surface-raised);
  border: 1px solid var(--rp-border);
  border-radius: 20px;
  padding: 4px 10px 4px 8px;
  cursor: pointer;
  user-select: none;
  transition: background-color .18s ease, border-color .18s ease, opacity .18s ease, transform .12s ease;
}
.legend-chip:hover { transform: translateY(-1px); border-color: var(--rp-accent-border); }
.legend-chip--off { opacity: .42; }
.legend-dot { width: 8px; height: 8px; border-radius: 50%; }
.legend-chip--orange .legend-dot { background: var(--rp-accent); }
.legend-chip--green .legend-dot  { background: var(--rp-green); }

/* ── TREND CHART — TOTALS ROW (Sales + Profit side by side) ── */
.trend-totals {
  display: flex;
  justify-content: flex-end;
  gap: 28px;
  flex-wrap: wrap;
  margin-bottom: 4px;
}
.monthly-total-value--green { color: var(--rp-green); }

/* ── TOP SELLING — METRIC TOGGLE ── */
.ts-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.metric-toggle {
  display: flex;
  gap: 2px;
  background: var(--rp-surface-sunken);
  border: 1px solid var(--rp-border);
  border-radius: 10px;
  padding: 3px;
}
.metric-toggle-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  border: none;
  background: transparent;
  color: var(--rp-text-muted);
  font-family: inherit;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: -.01em;
  padding: 6px 13px;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color .16s ease, color .16s ease;
}
.metric-toggle-btn i { font-size: 9.5px; }
.metric-toggle-btn:hover:not(.metric-toggle-btn--active) { color: var(--rp-text-primary); }
.metric-toggle-btn--active {
  background: var(--rp-accent);
  color: #fff;
  box-shadow: 0 2px 8px rgba(234,88,12,0.28);
}

/* ── TOP SELLING — RANKED LEADERBOARD ── */
.top-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.top-row {
  position: relative;
  /* Base stacking level for every row — bumped on hover (below) so the
     hovered row's downward tooltip paints above the row that follows
     it in the DOM, instead of being covered by it. */
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  background: var(--rp-surface-raised);
  border: 1px solid var(--rp-border);
  border-radius: 12px;
  cursor: pointer;
  transition: background-color .18s ease, border-color .18s ease, transform .16s ease, box-shadow .16s ease;
}
.top-row:hover {
  transform: translateY(-1px);
  border-color: var(--rp-accent-border);
  box-shadow: var(--rp-shadow);
  z-index: 30;
}
.top-row--pinned {
  background: var(--rp-accent-soft);
  border-color: var(--rp-accent);
}
/* Subtle tint per rank tier, kept faint so the row list still reads as
   one cohesive set rather than three different card styles */
.top-row--rank1 { background: linear-gradient(90deg, rgba(245,158,11,0.08), var(--rp-surface-raised) 55%); }
.top-row--rank2 { background: linear-gradient(90deg, rgba(148,163,184,0.10), var(--rp-surface-raised) 55%); }
.top-row--rank3 { background: linear-gradient(90deg, rgba(194,112,61,0.10), var(--rp-surface-raised) 55%); }

.top-row-rank {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  background: var(--rp-surface-sunken);
  color: var(--rp-text-secondary);
}
.top-row-rank--1 {
  background: linear-gradient(135deg, #FCD34D, #F59E0B);
  color: #78350F;
  box-shadow: 0 0 0 3px rgba(245,158,11,0.18);
  font-size: 12.5px;
}
.top-row-rank--2 {
  background: linear-gradient(135deg, #E2E8F0, #94A3B8);
  color: #334155;
}
.top-row-rank--3 {
  background: linear-gradient(135deg, #FDBA74, #C2703D);
  color: #431407;
}

.top-row-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 7px; }
.top-row-head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; }
.top-row-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--rp-text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.top-row-value {
  font-size: 12.5px;
  font-weight: 700;
  color: var(--rp-text-secondary);
  flex-shrink: 0;
  white-space: nowrap;
}

.top-row-bar-track {
  position: relative;
  height: 7px;
  border-radius: 6px;
  background: var(--rp-surface-sunken);
  overflow: hidden;
}
.top-row-bar-fill {
  position: absolute;
  inset: 0;
  width: 0;
  border-radius: 6px;
  transition: width .8s cubic-bezier(.16, 1, .3, 1);
}
.top-row-bar-fill--rank1   { background: linear-gradient(90deg, #F59E0B, #FCD34D); }
.top-row-bar-fill--rank2   { background: linear-gradient(90deg, #94A3B8, #CBD5E1); }
.top-row-bar-fill--rank3   { background: linear-gradient(90deg, #C2703D, #FDBA74); }
.top-row-bar-fill--default { background: linear-gradient(90deg, #6366F1, #818CF8); }

/* Rich hover tooltip anchored to the row. Positioned BELOW the row.
   Because a later sibling in the DOM otherwise paints over an earlier
   row's absolutely-positioned children, .top-row gets a low base
   z-index and bumps itself above its siblings on :hover — so the
   downward-opening tooltip is never hidden under the next row. */
.top-row-tooltip {
  position: absolute;
  left: 14px;
  right: 14px;
  top: calc(100% + 8px);
  z-index: 20;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px 18px;
  background: var(--rp-surface);
  border: 1px solid var(--rp-border);
  border-radius: 10px;
  box-shadow: var(--rp-shadow-lg);
  padding: 10px 12px;
  opacity: 0;
  transform: translateY(-4px);
  pointer-events: none;
  transition: opacity .15s ease, transform .15s ease;
}
.top-row:hover .top-row-tooltip { opacity: 1; transform: translateY(0); }
.trt-row { display: flex; justify-content: space-between; gap: 8px; font-size: 11.5px; color: var(--rp-text-muted); }
.trt-row strong { color: var(--rp-text-primary); font-weight: 700; }

.top-empty {
  text-align: center;
  padding: 28px 12px;
  font-size: 12.5px;
  color: var(--rp-text-faint);
}

.top-item-detail {
  position: relative;
  margin-top: 16px;
  padding: 14px 44px 14px 16px;
  background: var(--rp-surface-raised);
  border: 1px solid var(--rp-accent-border);
  border-radius: 12px;
}
.tid-close {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 24px;
  height: 24px;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: var(--rp-text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  transition: background-color .15s ease, color .15s ease;
}
.tid-close:hover { background: var(--rp-border); color: var(--rp-text-primary); }
.tid-row--head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.tid-rank {
  font-size: 11px;
  font-weight: 800;
  color: var(--rp-accent);
  background: var(--rp-accent-soft);
  border-radius: 8px;
  padding: 2px 7px;
}
.tid-name { font-size: 13.5px; color: var(--rp-text-primary); }
.tid-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}
.tid-stat {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.tid-stat strong { font-size: 13.5px; color: var(--rp-text-primary); }
.tid-label {
  font-size: 10.5px;
  color: var(--rp-text-faint);
  text-transform: uppercase;
  letter-spacing: .06em;
}

/* MONTHLY TOTAL */
.monthly-total { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
.monthly-total-label { font-size: 11px; font-weight: 600; color: var(--rp-text-muted); text-transform: uppercase; letter-spacing: .07em; }
.monthly-total-value {
  font-size: 22px;
  font-weight: 800;
  color: var(--rp-accent);
  letter-spacing: -.03em;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Momentum badge next to the period total */
.trend-badge {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: -.01em;
  padding: 2px 8px;
  border-radius: 20px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  cursor: default;
}
.trend-badge i { font-size: 9.5px; }
.trend-badge.up   { color: var(--rp-green); background: var(--rp-green-soft); }
.trend-badge.down { color: var(--rp-red);   background: var(--rp-red-soft); }
.trend-badge.flat { color: var(--rp-text-muted); background: var(--rp-surface-raised); }

/* CHART */
.chart-area { position: relative; width: 100%; }
.chart-area--tall { height: 280px; }

/* RESPONSIVE */
@media (max-width: 1100px) { .kpi-strip { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 700px) {
  .reports { padding: 16px 16px 40px; }
  .kpi-strip { grid-template-columns: 1fr; }
  .card-header { flex-direction: column; align-items: stretch; }
  .monthly-total { align-items: flex-start; }
  .trend-totals { justify-content: flex-start; gap: 16px; }
  .page-header { flex-direction: column; align-items: stretch; }
  .period-control { flex-direction: column; align-items: stretch; }
  .period-mode-switch { justify-content: stretch; }
  .period-mode-btn { flex: 1; text-align: center; }
  .report-toolbar { justify-content: stretch; }
  .rt-btn { flex: 1; justify-content: center; }
  .top-row-tooltip { display: none; }
  .tid-stats { grid-template-columns: 1fr; }
}
</style>

<!-- ══════════════════════════════════════════════════════
     GLOBAL (unscoped) — print rules only.
══════════════════════════════════════════════════════ -->
<style>
@media print {
  @page {
    size: auto;
    margin: 16mm 18mm;
  }

  /* The app shell often gives html/body (or a scroll wrapper) a fixed
     height with overflow hidden so the sidebar layout can scroll
     internally. That's invisible on screen but means print only
     captures whatever fit in the viewport — everything below the fold
     got silently cut. Forcing every element back to natural height/
     overflow here is what makes the rest of the page actually print. */
  html, body {
    height: auto !important;
    overflow: visible !important;
    background: #ffffff !important;
  }
  body * {
    overflow: visible !important;
  }

  body * { visibility: hidden !important; }
  #reports-print-area,
  #reports-print-area * { visibility: visible !important; }

  /* visibility:hidden keeps every hidden element's layout box in place
     (sidebar, nav, app shell columns, etc. still reserve their normal
     width/height even though invisible) — that's what was squeezing
     the report into a small corner of the page. Taking the report out
     of that flow with position:absolute means it no longer cares how
     much space its hidden siblings claim, and can use the full sheet. */
  #reports-print-area {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    background: #ffffff !important;
    display: block !important;
    gap: 0 !important;
    font-family: 'Inter', Arial, Helvetica, sans-serif !important;
    color: #1a1a1a !important;
  }

  /* Interactive-only chrome has no place on paper */
  .period-control,
  .ts-controls,
  .report-toolbar,
  .chart-hint,
  .page-header,
  .legend-row {
    display: none !important;
  }

  /* ── LETTERHEAD ── */
  .print-header {
    display: flex !important;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    padding-bottom: 18px;
    margin-bottom: 28px;
    border-bottom: 3px solid #1a1a1a;
  }
  .print-header-brand { display: flex; flex-direction: column; gap: 5px; }
  .print-brand-name {
    margin: 0;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: #EA580C !important;
  }
  .print-report-title {
    margin: 0;
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -.02em;
    color: #111 !important;
  }
  .print-header-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    text-align: right;
  }
  .print-header-meta p {
    margin: 0;
    font-size: 12px;
    color: #444 !important;
  }
  .print-header-meta span {
    display: inline-block;
    min-width: 66px;
    margin-right: 6px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-size: 9.5px;
    color: #888 !important;
  }

  /* ── DOCUMENT FLOW ── */
  /* Give each major block real breathing room and let sections start on
     a fresh page rather than splitting mid-way whenever possible. */
  .section { margin-bottom: 32px; page-break-inside: avoid; break-inside: avoid; }
  .section:last-child { margin-bottom: 0; }
  .section-header { margin-bottom: 14px; }
  .card-eyebrow {
    font-size: 10px !important;
    letter-spacing: .12em;
    color: #888 !important;
  }
  .section-title, .card-title {
    color: #111 !important;
    font-size: 16px !important;
    border-bottom: 1px solid #ddd;
    padding-bottom: 8px;
  }

  /* Let sections flow naturally so page 1 is actually used instead of
     leaving it mostly blank — .card already has page-break-inside:
     avoid below, so the chart still won't be sliced across two pages,
     it'll just start wherever it naturally fits. */

  /* ── CARDS / KPI TILES — flatten to a clean, borderable print look ── */
  .card, .kpi-card {
    background: #fff !important;
    box-shadow: none !important;
    border: 1px solid #ddd !important;
    border-radius: 6px !important;
  }
  .kpi-card:hover { transform: none !important; }
  .kpi-strip { grid-template-columns: repeat(4, 1fr) !important; gap: 16px !important; }
  .kpi-card { padding: 18px 18px 16px !important; }
  .kpi-accent-bar { display: none !important; }
  .kpi-icon-wrap { width: 32px !important; height: 32px !important; font-size: 13px !important; border: 1px solid #e5e5e5; }
  .kpi-value { font-size: 20px !important; color: #111 !important; }
  .kpi-label { font-size: 9.5px !important; color: #888 !important; }
  .kpi-card--featured, .kpi-card--alert { background: #fff !important; }
  .card { padding: 20px 22px 18px !important; }

  /* ── TOP SELLING — read as a plain ranked table on paper ── */
  .top-list { gap: 4px !important; }

  /* .top-row is a flex row on screen (rank | name+bar | tooltip). On
     paper the "tooltip" becomes a permanent breakdown line, and a flex
     row with no wrap would squeeze it into the same line as the name,
     which is what produced the overlapping/garbled text. flex-wrap
     lets it drop to its own full-width line underneath instead. */
  .top-row {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    background: #fff !important;
    border: none !important;
    border-bottom: 1px solid #eee !important;
    border-radius: 0 !important;
    padding: 10px 2px !important;
    transform: none !important;
    box-shadow: none !important;
  }
  .top-row:first-child { border-top: 1px solid #eee !important; }
  .top-row-rank {
    background: #f3f3f3 !important;
    color: #333 !important;
    box-shadow: none !important;
    width: 24px !important;
    height: 24px !important;
    font-size: 11px !important;
  }
  .top-row-rank--1, .top-row-rank--2, .top-row-rank--3 { background: #f3f3f3 !important; color: #333 !important; }
  .top-row-main { flex: 1 1 auto !important; min-width: 0; }
  .top-row-bar-track { display: none !important; }
  .top-row-name { color: #111 !important; font-size: 12.5px !important; }
  .top-row-value { color: #333 !important; font-size: 12.5px !important; }

  /* Hover doesn't exist on paper — always show the breakdown as its
     own full-width line under the row, indented to align under the
     product name, instead of a floating hover card. */
  .top-row-tooltip {
    position: static !important;
    opacity: 1 !important;
    transform: none !important;
    pointer-events: none !important;
    box-shadow: none !important;
    border: none !important;
    background: transparent !important;
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    flex: 1 1 100% !important;
    order: 3;
    width: auto !important;
    margin: 6px 0 0 34px !important;
    padding: 6px 0 0 !important;
    border-top: 1px dashed #ddd !important;
  }
  .trt-row { color: #888 !important; font-size: 10.5px !important; }
  .trt-row strong { color: #222 !important; font-size: 11px !important; }
  .top-item-detail { display: none !important; }

  /* ── TREND CHART ── */
  .trend-totals { justify-content: flex-start !important; gap: 40px !important; margin-bottom: 14px !important; }
  .monthly-total { align-items: flex-start !important; }
  .monthly-total-value { color: #111 !important; font-size: 24px !important; }
  .monthly-total-value--green { color: #0f766e !important; }
  .trend-badge { border: 1px solid currentColor; background: transparent !important; }
  .chart-area--tall { height: 320px !important; }
  canvas { max-width: 100% !important; }

  /* ── FOOTER — page numbers via @page counters ── */
  @page {
    @bottom-center {
      content: "Page " counter(page) " of " counter(pages);
      font-size: 9px;
      color: #999;
    }
  }
}
</style>