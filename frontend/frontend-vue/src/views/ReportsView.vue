<template>
  <Layout title="Reports">
    <div id="reports-print-area" class="reports">

      <!-- ── PAGE HEADER: title + export actions ── -->
      <header class="page-header">
        <div class="page-heading">
          <h1 class="page-title">Reports</h1>
          <p class="page-sub">
            <span>{{ periodLabel || 'Loading…' }}</span>
            <span v-if="isCurrentPeriod" class="chip chip--live">In progress</span>
          </p>
        </div>

        <div class="report-toolbar" role="group" aria-label="Export this report">
          <button type="button" class="rt-btn" @click="copyReport" :disabled="copyState === 'copying' || loading">
            <i class="fa-solid" :class="copyState === 'copied' ? 'fa-check' : copyState === 'error' ? 'fa-triangle-exclamation' : 'fa-copy'"></i>
            {{ copyState === 'copied' ? 'Copied' : copyState === 'error' ? 'Copy failed' : 'Copy' }}
          </button>
          <button type="button" class="rt-btn" @click="exportCSV" :disabled="loading">
            <i class="fa-solid" :class="csvState === 'done' ? 'fa-check' : 'fa-file-csv'"></i>
            {{ csvState === 'done' ? 'Saved' : 'CSV' }}
          </button>
          <button type="button" class="rt-btn rt-btn--primary" @click="printReport" :disabled="loading || printing">
            <i class="fa-solid" :class="printing ? 'fa-spinner fa-spin' : 'fa-print'"></i>
            {{ printing ? 'Preparing…' : 'Print' }}
          </button>
        </div>
      </header>

      <!-- ── PERIOD BAR: group by + navigate ── -->
      <div class="period-bar" role="group" aria-label="Report period">
        <div class="seg" role="group" aria-label="Group report by">
          <button
            v-for="m in MODES"
            :key="m.key"
            type="button"
            class="seg-btn"
            :class="{ 'seg-btn--active': mode === m.key }"
            :aria-pressed="mode === m.key"
            @click="setMode(m.key)"
          >
            {{ m.label }}
          </button>
        </div>

        <div class="period-nav">
          <button
            type="button"
            class="icon-btn"
            :disabled="!canGoPrev"
            :aria-label="`Previous ${modeNoun}`"
            @click="shiftPeriod(-1)"
          >
            <i class="fa-solid fa-chevron-left"></i>
          </button>

          <input
            v-if="mode === 'month'"
            type="month"
            class="period-picker-input"
            aria-label="Select month"
            v-model="selectedMonth"
            :max="currentMonthValue"
            @change="onPickerChange"
          />
          <select
            v-else-if="mode === 'year'"
            class="period-picker-input"
            aria-label="Select year"
            v-model.number="selectedYear"
            @change="onPickerChange"
          >
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
          <input
            v-else
            type="date"
            class="period-picker-input"
            aria-label="Select date"
            v-model="selectedDay"
            :max="currentDayValue"
            @change="onPickerChange"
          />

          <button
            type="button"
            class="icon-btn"
            :disabled="!canGoNext"
            :aria-label="`Next ${modeNoun}`"
            @click="shiftPeriod(1)"
          >
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>

        <button v-if="!isCurrentPeriod" type="button" class="link-btn" @click="goCurrent">{{ currentLabel }}</button>

        <div class="period-status" aria-live="polite">
          <span v-if="loading" class="period-status-text period-status-text--busy">
            <i class="fa-solid fa-spinner fa-spin"></i> Updating…
          </span>
          <span v-else-if="lastUpdated" class="period-status-text">Updated {{ lastUpdated }}</span>
          <button
            type="button"
            class="icon-btn icon-btn--sm"
            aria-label="Refresh report"
            title="Refresh"
            :disabled="loading"
            @click="loadReports"
          >
            <i class="fa-solid fa-rotate"></i>
          </button>
        </div>
      </div>

      <!-- ── ERROR NOTICE ── -->
      <div v-if="loadError" class="notice" role="alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>{{ loadError }}</span>
        <button type="button" class="link-btn" @click="loadReports">Try again</button>
      </div>

      <div class="report-body" :class="{ 'is-loading': loading }" :aria-busy="loading">

        <!-- ── 1. SUMMARY ── -->
        <section class="section" aria-labelledby="rp-summary">
          <h2 id="rp-summary" class="section-title">Summary</h2>

          <div class="summary-grid">
            <!-- Hero: sales + profit + margin -->
            <article class="stat stat--hero">
              <div class="hero-cols">
                <div class="hero-col">
                  <p class="stat-label">Total sales</p>
                  <p class="stat-value stat-value--xl">₱{{ price(animated.sales) }}</p>
                  <p v-if="salesTrend.available" class="trend-line">
                    <span class="trend-badge" :class="salesTrend.direction">
                      <i :class="trendIcon"></i>{{ salesTrend.text }}
                    </span>
                    <span>vs {{ comparisonLabel }}</span>
                  </p>
                  <p v-else class="stat-note">No earlier period to compare.</p>
                </div>

                <div class="hero-col hero-col--profit">
                  <p class="stat-label">Profit</p>
                  <p class="stat-value stat-value--xl stat-value--green">₱{{ price(animated.profit) }}</p>
                  <p class="stat-note">{{ profitMarginPct }}% of sales</p>
                </div>
              </div>

              <div class="margin-meter">
                <div class="margin-head">
                  <span>Profit margin</span>
                  <strong>{{ profitMarginPct }}%</strong>
                </div>
                <div class="margin-track" role="img" :aria-label="`Profit margin ${profitMarginPct} percent`">
                  <div class="margin-fill" :style="{ width: marginBarPct + '%' }"></div>
                </div>
              </div>
            </article>

            <!-- Secondary KPIs -->
            <article v-for="s in secondaryStats" :key="s.key" class="stat">
              <span class="stat-icon" :class="`stat-icon--${s.tone}`"><i class="fa-solid" :class="s.icon"></i></span>
              <div class="stat-body">
                <p class="stat-label">{{ s.label }}</p>
                <p class="stat-value" :class="{ 'stat-value--text': s.text }" :title="s.value">{{ s.value }}</p>
                <p class="stat-sub" :title="s.sub">{{ s.sub }}</p>
              </div>
            </article>
          </div>
        </section>

        <!-- ── 2. TREND ── -->
        <section class="card" aria-labelledby="rp-trend">
          <header class="card-head">
            <div>
              <h2 id="rp-trend" class="card-title">{{ seriesWord }} sales and profit</h2>
              <p class="card-sub">Hover for exact values and margin. Click a legend item to hide it.</p>
            </div>
            <div class="legend-row">
              <button
                type="button"
                class="legend-chip legend-chip--orange"
                :class="{ 'legend-chip--off': legendState[0] }"
                :aria-pressed="!legendState[0]"
                @click="toggleDataset(0)"
              >
                <span class="legend-dot"></span>Sales
              </button>
              <button
                type="button"
                class="legend-chip legend-chip--green"
                :class="{ 'legend-chip--off': legendState[1] }"
                :aria-pressed="!legendState[1]"
                @click="toggleDataset(1)"
              >
                <span class="legend-dot"></span>Profit
              </button>
            </div>
          </header>

          <div class="chart-area">
            <canvas id="salesTrendChart"></canvas>
            <p v-if="!chartRows.length && !loading" class="chart-empty">
              <i class="fa-solid fa-chart-line"></i>
              No sales recorded for this period.
            </p>
          </div>
        </section>

        <!-- ── 3. TOP SELLERS + INVENTORY ── -->
        <div class="content-grid">

          <section class="card" aria-labelledby="rp-top">
            <header class="card-head">
              <div>
                <h2 id="rp-top" class="card-title">Top sellers</h2>
                <p class="card-sub">Ranked by {{ topMetric === 'sales' ? 'sales amount' : 'units sold' }}. Select an item for details.</p>
              </div>
              <div class="seg seg--sm ts-controls" role="group" aria-label="Rank by">
                <button type="button" class="seg-btn" :class="{ 'seg-btn--active': topMetric === 'sales' }" :aria-pressed="topMetric === 'sales'" @click="setTopMetric('sales')">Sales</button>
                <button type="button" class="seg-btn" :class="{ 'seg-btn--active': topMetric === 'qty' }" :aria-pressed="topMetric === 'qty'" @click="setTopMetric('qty')">Quantity</button>
              </div>
            </header>

            <ol v-if="visibleTopItems.length" class="top-list">
              <li
                v-for="item in visibleTopItems"
                :key="rowKey(item)"
                class="top-row"
                :class="{ 'top-row--open': openKey === rowKey(item) }"
              >
                <button
                  type="button"
                  class="top-row-btn"
                  :aria-expanded="openKey === rowKey(item)"
                  @click="toggleOpen(item)"
                >
                  <span class="top-rank" :class="{ 'top-rank--lead': item.rank === 1 }">{{ item.rank }}</span>
                  <span class="top-main">
                    <span class="top-head">
                      <span class="top-name">{{ item.ProductName }}</span>
                      <span class="top-value">
                        {{ topMetric === 'sales' ? '₱' + price(item.total_sales) : formatNumber(item.total_quantity) + ' units' }}
                      </span>
                    </span>
                    <span class="top-bar">
                      <span
                        class="top-bar-fill"
                        :class="{ 'top-bar-fill--lead': item.rank === 1 }"
                        :style="{ width: (barsReady ? item.barPct : 0) + '%' }"
                      ></span>
                    </span>
                  </span>
                  <span class="top-share">{{ item.sharePct }}%</span>
                  <i class="fa-solid fa-chevron-down top-chevron"></i>
                </button>

                <dl class="top-detail">
                  <div class="td-item"><dt>Total sales</dt><dd>₱{{ price(item.total_sales) }}</dd></div>
                  <div class="td-item"><dt>Quantity sold</dt><dd>{{ formatNumber(item.total_quantity) }}</dd></div>
                  <div class="td-item"><dt>Average price</dt><dd>₱{{ price(avgPrice(item)) }}</dd></div>
                  <div class="td-item"><dt>Category</dt><dd>{{ item.CategoryName || '—' }}</dd></div>
                </dl>
              </li>
            </ol>
            <p v-else class="empty">
              <i class="fa-solid fa-box-open"></i>
              No sales recorded for this period.
            </p>

            <button
              v-if="rankedTopItems.length > TOP_PREVIEW"
              type="button"
              class="link-btn link-btn--block"
              @click="toggleShowAll"
            >
              {{ showAllTop ? 'Show fewer' : `Show all ${rankedTopItems.length} items` }}
            </button>
          </section>

          <aside class="card" aria-labelledby="rp-inv">
            <header class="card-head">
              <div>
                <h2 id="rp-inv" class="card-title">Inventory</h2>
                <p class="card-sub">Current stock. Not affected by the period above.</p>
              </div>
            </header>

            <ul class="inv-list">
              <li
                v-for="(box, idx) in inventorySummary"
                :key="box.label"
                class="inv-row"
                :class="{ 'inv-row--alert': box.isRed && num(box.value) > 0 }"
              >
                <span class="inv-icon"><i class="fa-solid" :class="inventoryIcon(box, idx)"></i></span>
                <span class="inv-label">{{ box.label }}</span>
                <strong class="inv-value">{{ formatNumber(box.value) }}</strong>
              </li>
            </ul>

            <div v-if="stockCoverage" class="coverage">
              <div class="coverage-head">
                <span>{{ formatNumber(stockCoverage.active) }} of {{ formatNumber(stockCoverage.total) }} items in stock</span>
                <strong>{{ stockCoverage.pct }}%</strong>
              </div>
              <div class="coverage-track"><div class="coverage-fill" :style="{ width: stockCoverage.pct + '%' }"></div></div>
            </div>
          </aside>
        </div>

      </div>

      <!-- ══════════════════════════════════════════════════════
           PRINT SHEET — hidden on screen, the only thing printed.
      ══════════════════════════════════════════════════════ -->
      <div class="print-sheet" aria-hidden="true">

        <header class="ps-head">
          <div class="ps-band"></div>
          <div class="ps-head-row">
            <div class="ps-brand">
              <p class="ps-brand-name">{{ BUSINESS_NAME }}</p>
              <p class="ps-brand-tag">{{ BUSINESS_TAGLINE }}</p>
            </div>
            <div class="ps-meta">
              <p><span>Report</span>{{ reportTypeLabel }}</p>
              <p><span>Period</span>{{ periodLabel }}</p>
              <p><span>Generated</span>{{ printGeneratedAt }}</p>
              <p><span>Currency</span>Philippine Peso (₱)</p>
            </div>
          </div>
          <div class="ps-title-row">
            <h1 class="ps-title">{{ reportTypeLabel }}</h1>
            <p class="ps-period">
              {{ periodLabel }}
              <span v-if="isCurrentPeriod" class="ps-chip">In progress</span>
            </p>
          </div>
        </header>

        <section class="ps-section">
          <h2 class="ps-h2">Key performance indicators</h2>
          <div class="ps-kpis">
            <div v-for="k in printKpis" :key="k.key" class="ps-kpi" :class="`ps-kpi--${k.tone}`">
              <p class="ps-kpi-label">{{ k.label }}</p>
              <p class="ps-kpi-value" :class="{ 'ps-kpi-value--text': k.text }">{{ k.value }}</p>
              <p class="ps-kpi-sub">{{ k.sub }}</p>
            </div>
          </div>
          <p v-if="trendPlain" class="ps-trend" :class="`ps-trend--${salesTrend.direction}`">
            Sales trend: <strong>{{ trendPlain }}</strong>
          </p>
        </section>

        <section class="ps-section ps-section--keep">
          <h2 class="ps-h2">{{ seriesWord }} sales and profit</h2>
          <div v-if="printChartImg && chartRows.length">
            <div class="ps-legend">
              <span><i class="ps-dot ps-dot--orange"></i>Sales</span>
              <span><i class="ps-dot ps-dot--green"></i>Profit</span>
            </div>
            <img :src="printChartImg" class="ps-chart" alt="Sales and profit trend chart" />
          </div>
          <p v-else class="ps-empty">No sales recorded for this period.</p>
        </section>

        <section class="ps-section">
          <h2 class="ps-h2">Top selling items <small>ranked by {{ topMetric === 'sales' ? 'sales amount' : 'units sold' }}</small></h2>
          <table v-if="rankedTopItems.length" class="ps-table">
            <thead>
              <tr>
                <th class="c-rank">#</th>
                <th>Product</th>
                <th>Category</th>
                <th class="num">Qty</th>
                <th class="num">Avg. price</th>
                <th class="num">Sales</th>
                <th class="c-share">Share</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in rankedTopItems" :key="rowKey(item)">
                <td class="c-rank"><span class="ps-rank" :class="{ 'ps-rank--lead': item.rank === 1 }">{{ item.rank }}</span></td>
                <td class="ps-strong">{{ item.ProductName }}</td>
                <td>{{ item.CategoryName || '—' }}</td>
                <td class="num">{{ formatNumber(item.total_quantity) }}</td>
                <td class="num">₱{{ price(avgPrice(item)) }}</td>
                <td class="num ps-strong">₱{{ price(item.total_sales) }}</td>
                <td class="c-share">
                  <span class="ps-share">
                    <span class="ps-share-bar"><i :class="{ 'ps-share-bar--lead': item.rank === 1 }" :style="{ width: item.barPct + '%' }"></i></span>
                    <b>{{ item.sharePct }}%</b>
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td></td>
                <td colspan="2">Total</td>
                <td class="num">{{ formatNumber(topTotals.qty) }}</td>
                <td></td>
                <td class="num">₱{{ price(topTotals.sales) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
          <p v-else class="ps-empty">No sales recorded for this period.</p>
        </section>

        <section class="ps-section">
          <h2 class="ps-h2">{{ seriesWord }} breakdown <small>periods with sales only</small></h2>
          <table v-if="breakdownRows.length" class="ps-table">
            <thead>
              <tr>
                <th>{{ bucketWord }}</th>
                <th class="num">Sales</th>
                <th class="num">Profit</th>
                <th class="num">Margin</th>
                <th class="num">% of period sales</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in breakdownRows" :key="row.date">
                <td class="ps-strong">{{ formatPointTitle(row.date) }}</td>
                <td class="num">₱{{ price(row.total) }}</td>
                <td class="num">₱{{ price(row.profit) }}</td>
                <td class="num" :class="row.profit >= 0 ? 'ps-pos' : 'ps-neg'">{{ rowMargin(row) }}%</td>
                <td class="num">{{ rowShare(row) }}%</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td>Total</td>
                <td class="num">₱{{ price(salesTotal) }}</td>
                <td class="num">₱{{ price(profitTotal) }}</td>
                <td class="num">{{ profitMarginPct }}%</td>
                <td class="num">100%</td>
              </tr>
            </tfoot>
          </table>
          <p v-else class="ps-empty">No sales recorded for this period.</p>
        </section>

        <section class="ps-section ps-section--keep">
          <h2 class="ps-h2">Inventory snapshot <small>current stock, not period-based</small></h2>
          <div class="ps-inv">
            <div
              v-for="box in inventorySummary"
              :key="box.label"
              class="ps-inv-box"
              :class="{ 'ps-inv-box--alert': box.isRed && num(box.value) > 0 }"
            >
              <p>{{ box.label }}</p>
              <strong>{{ formatNumber(box.value) }}</strong>
            </div>
          </div>
          <div v-if="stockCoverage" class="ps-cover">
            <div class="ps-cover-head">
              <span>{{ formatNumber(stockCoverage.active) }} of {{ formatNumber(stockCoverage.total) }} items in stock</span>
              <strong>{{ stockCoverage.pct }}%</strong>
            </div>
            <div class="ps-cover-track"><i :style="{ width: stockCoverage.pct + '%' }"></i></div>
          </div>
        </section>

        <footer class="ps-end">
          <span>{{ BUSINESS_NAME }} · {{ reportTypeLabel }} · {{ periodLabel }}</span>
          <span>Generated {{ printGeneratedAt }}</span>
        </footer>
      </div>

    </div>
  </Layout>
</template>

<script>
// Module-level (executed once): uniquely namespaced plugin id so it can't
// collide with another chart plugin elsewhere in the app. Wrapped in
// try/catch so a decorative overlay can never break the chart render loop.
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
import { ref, reactive, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import Layout from '@/components/Layout.vue'
import Chart from 'chart.js/auto'
import api from '@/api/axios'

/* ════════════════════════════════════════════════════════════
   BRANDING  (shown on the printout, Copy and CSV)
════════════════════════════════════════════════════════════ */
const BUSINESS_NAME    = 'Sheem Steel'
const BUSINESS_TAGLINE = 'POS & Inventory'

/* ════════════════════════════════════════════════════════════
   HELPERS
════════════════════════════════════════════════════════════ */
function num(v) {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}
const price = (v) => num(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const peso = (v) => '₱' + price(v)
const formatNumber = (n) => Math.round(num(n)).toLocaleString('en-US')
const round2 = (v) => Math.round(num(v) * 100) / 100

const pad = (n) => String(n).padStart(2, '0')
const toDayStr = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
const toMonthStr = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}`

// "₱12,345.00" -> 12345 ; plain numbers pass straight through.
function parseDisplayNumber(v) {
  if (typeof v === 'number') return v
  const n = Number(String(v ?? '').replace(/[^\d.-]/g, ''))
  return Number.isFinite(n) ? n : 0
}

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches

const formatStamp = (d = new Date()) => d.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })

/* ════════════════════════════════════════════════════════════
   PERIOD STATE  (Day / Month / Year)
════════════════════════════════════════════════════════════ */
const MODES = [
  { key: 'day',   label: 'Day' },
  { key: 'month', label: 'Month' },
  { key: 'year',  label: 'Year' },
]

const today             = new Date()
const currentDayValue   = toDayStr(today)    // <input type="date">  max
const currentMonthValue = toMonthStr(today)  // <input type="month"> max
const currentYear       = today.getFullYear()
const yearOptions       = Array.from({ length: 10 }, (_, i) => currentYear - i)
const minYear           = yearOptions[yearOptions.length - 1]

const mode          = ref('month')
const selectedDay   = ref(currentDayValue)
const selectedMonth = ref(currentMonthValue)
const selectedYear  = ref(currentYear)

const periodLabel      = ref('')
const printGeneratedAt = ref('')
const lastUpdated      = ref('')

const modeNoun        = computed(() => ({ day: 'day', month: 'month', year: 'year' }[mode.value]))
const currentLabel    = computed(() => ({ day: 'Today', month: 'This month', year: 'This year' }[mode.value]))
const seriesWord      = computed(() => ({ day: 'Hourly', month: 'Daily', year: 'Monthly' }[mode.value]))
const bucketWord      = computed(() => ({ day: 'Hour', month: 'Day', year: 'Month' }[mode.value]))
const peakWord        = computed(() => ({ day: 'Peak hour', month: 'Best day', year: 'Best month' }[mode.value]))
const reportTypeLabel = computed(() => ({ day: 'Daily Sales Report', month: 'Monthly Sales Report', year: 'Annual Sales Report' }[mode.value]))

const canGoNext = computed(() => {
  if (mode.value === 'year')  return selectedYear.value < currentYear
  if (mode.value === 'month') return !!selectedMonth.value && selectedMonth.value < currentMonthValue
  return !!selectedDay.value && selectedDay.value < currentDayValue
})
const canGoPrev = computed(() => {
  if (mode.value === 'year')  return selectedYear.value > minYear
  if (mode.value === 'month') return !!selectedMonth.value
  return !!selectedDay.value
})
const isCurrentPeriod = computed(() => !canGoNext.value)

// Request params + a human label for the selected period (offset 0) or the
// one right before it (offset -1), whichever mode is active.
function periodParams(offset = 0) {
  if (mode.value === 'year') {
    const y = selectedYear.value + offset
    return { params: { mode: 'year', year: y }, label: String(y) }
  }
  if (mode.value === 'month') {
    const [yy, mm] = selectedMonth.value.split('-').map(Number)
    const d = new Date(yy, mm - 1 + offset, 1)
    return {
      params: { mode: 'month', year: d.getFullYear(), month: d.getMonth() + 1 },
      label: d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }),
    }
  }
  const d = new Date(`${selectedDay.value}T00:00:00`)
  d.setDate(d.getDate() + offset)
  return {
    params: { mode: 'day', date: toDayStr(d) },
    label: d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
  }
}

// Keeps the three pickers pointing at the same moment in time when the
// person switches Day / Month / Year, instead of jumping back to "now".
function syncPickers(from) {
  if (from === 'day' && selectedDay.value) {
    selectedMonth.value = selectedDay.value.slice(0, 7)
    selectedYear.value  = Number(selectedDay.value.slice(0, 4))
  } else if (from === 'month' && selectedMonth.value) {
    selectedYear.value = Number(selectedMonth.value.slice(0, 4))
    selectedDay.value  = selectedMonth.value === currentMonthValue ? currentDayValue : `${selectedMonth.value}-01`
  } else if (from === 'year') {
    const y = selectedYear.value
    selectedMonth.value = y === currentYear ? currentMonthValue : `${y}-01`
    selectedDay.value   = y === currentYear ? currentDayValue   : `${y}-01-01`
  }
}

async function setMode(next) {
  if (mode.value === next) return
  syncPickers(mode.value)
  mode.value = next
  await loadReports()
}

async function shiftPeriod(delta) {
  if (delta > 0 && !canGoNext.value) return
  if (delta < 0 && !canGoPrev.value) return

  if (mode.value === 'year') {
    selectedYear.value += delta
  } else if (mode.value === 'month') {
    const [yy, mm] = selectedMonth.value.split('-').map(Number)
    selectedMonth.value = toMonthStr(new Date(yy, mm - 1 + delta, 1))
  } else {
    const d = new Date(`${selectedDay.value}T00:00:00`)
    d.setDate(d.getDate() + delta)
    selectedDay.value = toDayStr(d)
  }
  await loadReports()
}

async function goCurrent() {
  selectedDay.value   = currentDayValue
  selectedMonth.value = currentMonthValue
  selectedYear.value  = currentYear
  await loadReports()
}

async function onPickerChange() {
  // A cleared <input> yields '' — ignore it rather than request garbage.
  if (mode.value === 'month' && !selectedMonth.value) return
  if (mode.value === 'day' && !selectedDay.value) return
  await loadReports()
}

/* ════════════════════════════════════════════════════════════
   REPORT DATA
════════════════════════════════════════════════════════════ */
const loading   = ref(false)
const loadError = ref('')

// Raw API copies — used verbatim by Copy / CSV.
const salesActivityRaw = ref([
  { label: 'Sales',        value: '₱0.00' },
  { label: 'Items sold',   value: 0 },
  { label: 'Transactions', value: 0 },
  { label: 'Profit',       value: '₱0.00' },
])
const inventorySummary = ref([
  { label: 'Quantity in Hand', value: 0 },
  { label: 'Low Stock Items',  value: 0, isRed: true },
  { label: 'Total Items',      value: 0 },
  { label: 'Active Items',     value: 0 },
])

const topSellingItems      = ref([])
const monthlySales         = ref([])
const monthlyProfitSeries  = ref([])
const salesTotal           = ref(0)
const profitTotal          = ref(0)
const itemsSold            = ref(0)
const txCount              = ref(0)

const previousTotal   = ref(null) // null = no comparison available
const comparisonLabel = ref('')

// Animated "counting" numbers for the summary cards.
const animated = reactive({ sales: 0, profit: 0, items: 0, tx: 0 })
let animFrame   = 0
let animTargets = null

function animateTo(targets, duration = 700) {
  cancelAnimationFrame(animFrame)
  animTargets = { ...targets }

  if (prefersReducedMotion()) {
    Object.assign(animated, animTargets)
    return
  }

  const from  = { ...animated }
  const start = performance.now()
  const tick = (now) => {
    const p = Math.min((now - start) / duration, 1)
    if (p >= 1) {
      Object.assign(animated, animTargets)
      return
    }
    const e = 1 - Math.pow(1 - p, 3)
    for (const k of Object.keys(animTargets)) {
      animated[k] = from[k] + (animTargets[k] - from[k]) * e
    }
    animFrame = requestAnimationFrame(tick)
  }
  animFrame = requestAnimationFrame(tick)
}

// Jump straight to the final values (used before printing).
function snapAnimation() {
  cancelAnimationFrame(animFrame)
  if (animTargets) Object.assign(animated, animTargets)
}

/* ── Derived KPIs ── */
const avgTransaction = computed(() => (txCount.value ? num(salesTotal.value) / txCount.value : 0))
const basketSize = computed(() =>
  txCount.value ? (itemsSold.value / txCount.value).toFixed(1).replace(/\.0$/, '') : '0'
)

const profitMarginPct = computed(() => {
  const total = num(salesTotal.value)
  return total ? ((num(profitTotal.value) / total) * 100).toFixed(1) : '0.0'
})
const marginBarPct = computed(() => Math.min(100, Math.max(0, Number(profitMarginPct.value))))

const salesTrend = computed(() => {
  if (previousTotal.value === null) return { direction: 'flat', percent: '0.0', text: '', available: false, isNew: false }
  const prev = num(previousTotal.value)
  const current = num(salesTotal.value)
  // Nothing to compare against: a % change would be meaningless.
  if (!prev) {
    return current > 0
      ? { direction: 'up', percent: null, text: 'New', available: true, isNew: true }
      : { direction: 'flat', percent: '0.0', text: '0.0%', available: true, isNew: false }
  }
  const change = ((current - prev) / prev) * 100
  if (!Number.isFinite(change)) return { direction: 'flat', percent: '0.0', text: '0.0%', available: true, isNew: false }
  const percent = Math.abs(change).toFixed(1)
  return {
    direction: change > 0.5 ? 'up' : change < -0.5 ? 'down' : 'flat',
    percent,
    text: `${percent}%`,
    available: true,
    isNew: false,
  }
})
const trendIcon = computed(() =>
  salesTrend.value.direction === 'up' ? 'fa-solid fa-arrow-trend-up'
  : salesTrend.value.direction === 'down' ? 'fa-solid fa-arrow-trend-down'
  : 'fa-solid fa-minus'
)
// Plain-text trend for Copy / CSV / Print.
const trendPlain = computed(() => {
  const t = salesTrend.value
  if (!t.available) return ''
  if (t.isNew) return `New sales (none recorded in ${comparisonLabel.value})`
  const sym = t.direction === 'up' ? '▲' : t.direction === 'down' ? '▼' : '▬'
  return `${sym} ${t.percent}% vs ${comparisonLabel.value}`
})

// Sales + profit merged by bucket (hour / day / month).
const chartRows = computed(() => {
  const profitMap = {}
  monthlyProfitSeries.value.forEach((p) => { profitMap[p.date] = num(p.profit ?? p.total) })
  return monthlySales.value.map((s) => ({
    date: s.date,
    total: num(s.total),
    profit: profitMap[s.date] ?? num(s.profit),
  }))
})
// Only buckets that actually had activity (keeps printed/copied tables tidy).
const breakdownRows = computed(() => chartRows.value.filter((r) => r.total || r.profit))
const rowMargin = (r) => (r.total ? ((r.profit / r.total) * 100).toFixed(1) : '0.0')
const rowShare  = (r) => (num(salesTotal.value) ? ((r.total / num(salesTotal.value)) * 100).toFixed(1) : '0.0')

// Highest-selling bucket: peak hour / best day / best month.
const peak = computed(() => {
  let best = null
  for (const r of chartRows.value) {
    if (r.total > 0 && (!best || r.total > best.total)) best = r
  }
  return best ? { label: formatPointLabel(best.date), title: formatPointTitle(best.date), total: best.total } : null
})

// Best seller by sales amount (independent of the Top sellers toggle).
const bestSeller = computed(() => {
  const list = topSellingItems.value.map((i) => ({
    ...i, total_sales: num(i.total_sales), total_quantity: num(i.total_quantity),
  }))
  if (!list.length) return null
  const top = list.reduce((a, b) => (b.total_sales > a.total_sales ? b : a))
  if (top.total_sales <= 0) return null
  const total = num(salesTotal.value)
  const sharePct = total ? Math.min(100, (top.total_sales / total) * 100).toFixed(1) : '0.0'
  return { ...top, sharePct }
})

const secondaryStats = computed(() => {
  const hasTx = txCount.value > 0
  const p = peak.value
  const b = bestSeller.value
  return [
    {
      key: 'items', label: 'Items sold', icon: 'fa-box-open', tone: 'violet',
      value: formatNumber(animated.items),
      sub: hasTx ? `${basketSize.value} items per transaction` : 'No transactions yet',
    },
    {
      key: 'tx', label: 'Transactions', icon: 'fa-receipt', tone: 'blue',
      value: formatNumber(animated.tx),
      sub: hasTx ? `${peso(avgTransaction.value)} average sale` : 'No transactions yet',
    },
    {
      key: 'peak', label: peakWord.value, tone: 'orange', text: true,
      icon: mode.value === 'day' ? 'fa-clock' : 'fa-fire',
      value: p ? p.label : '—',
      sub: p ? `${peso(p.total)} in sales` : 'No sales yet',
    },
    {
      key: 'best', label: 'Best seller', icon: 'fa-trophy', tone: 'green', text: true,
      value: b ? b.ProductName : '—',
      sub: b ? `${formatNumber(b.total_quantity)} units · ${b.sharePct}% of sales` : 'No sales yet',
    },
  ]
})

// Eight tiles for the printed "Key performance indicators" block.
const printKpis = computed(() => {
  const p = peak.value
  const b = bestSeller.value
  return [
    { key: 'sales',  label: 'Total sales',            value: peso(salesTotal.value),   sub: 'Gross sales for the period', tone: 'orange' },
    { key: 'profit', label: 'Profit',                 value: peso(profitTotal.value),  sub: `${profitMarginPct.value}% of sales`, tone: 'green' },
    { key: 'margin', label: 'Profit margin',          value: `${profitMarginPct.value}%`, sub: 'Profit ÷ total sales', tone: 'teal' },
    { key: 'avg',    label: 'Avg. per transaction',   value: peso(avgTransaction.value), sub: `${basketSize.value} items per transaction`, tone: 'amber' },
    { key: 'items',  label: 'Items sold',             value: formatNumber(itemsSold.value), sub: 'Units across all sales', tone: 'violet' },
    { key: 'tx',     label: 'Transactions',           value: formatNumber(txCount.value),   sub: 'Completed sales', tone: 'blue' },
    { key: 'peak',   label: peakWord.value,           value: p ? p.label : '—', sub: p ? `${peso(p.total)} in sales` : 'No sales yet', tone: 'rose', text: true },
    { key: 'best',   label: 'Best seller',            value: b ? b.ProductName : '—', sub: b ? `${formatNumber(b.total_quantity)} units · ${b.sharePct}% of sales` : 'No sales yet', tone: 'cyan', text: true },
  ]
})

/* ── Inventory card ── */
const INVENTORY_ICONS = ['fa-boxes-stacked', 'fa-triangle-exclamation', 'fa-tags', 'fa-circle-check']
const inventoryIcon = (box, idx) => (box.isRed ? 'fa-triangle-exclamation' : INVENTORY_ICONS[idx % INVENTORY_ICONS.length])

const stockCoverage = computed(() => {
  const total  = inventorySummary.value.find((b) => b.label === 'Total Items')
  const active = inventorySummary.value.find((b) => b.label === 'Active Items')
  if (!total || !active || !num(total.value)) return null
  const pct = Math.min(100, Math.round((num(active.value) / num(total.value)) * 100))
  return { total: num(total.value), active: num(active.value), pct }
})

/* ── Top sellers ── */
const TOP_PREVIEW = 8
const topMetric   = ref('sales')
const showAllTop  = ref(false)
const barsReady   = ref(false)
const openKey     = ref(null)

const rowKey = (item) => item.ProductID ?? item.ProductName

const rankedTopItems = computed(() => {
  const metric  = topMetric.value
  const valueOf = (i) => (metric === 'sales' ? i.total_sales : i.total_quantity)

  const items = topSellingItems.value
    .map((i) => ({ ...i, total_sales: num(i.total_sales), total_quantity: num(i.total_quantity) }))
    .sort((a, b) => valueOf(b) - valueOf(a))

  const maxVal   = Math.max(...items.map(valueOf), 1)
  const totalVal = items.reduce((s, i) => s + valueOf(i), 0) || 1

  return items.map((i, idx) => ({
    ...i,
    rank: idx + 1,
    barPct: Math.max((valueOf(i) / maxVal) * 100, 2),
    sharePct: ((valueOf(i) / totalVal) * 100).toFixed(1),
  }))
})
const visibleTopItems = computed(() =>
  showAllTop.value ? rankedTopItems.value : rankedTopItems.value.slice(0, TOP_PREVIEW)
)
const topTotals = computed(() => ({
  qty:   rankedTopItems.value.reduce((s, i) => s + i.total_quantity, 0),
  sales: rankedTopItems.value.reduce((s, i) => s + i.total_sales, 0),
}))

const avgPrice = (item) => (item && item.total_quantity ? item.total_sales / item.total_quantity : 0)

// Bars start at 0 width and grow to their real width on the next paint.
function animateBarsIn() {
  barsReady.value = false
  nextTick(() => requestAnimationFrame(() => { barsReady.value = true }))
}
function setTopMetric(metric) {
  if (topMetric.value === metric) return
  topMetric.value = metric
  openKey.value = null
  animateBarsIn()
}
function toggleOpen(item) {
  const key = rowKey(item)
  openKey.value = openKey.value === key ? null : key
}
function toggleShowAll() {
  showAllTop.value = !showAllTop.value
  animateBarsIn()
}

/* ════════════════════════════════════════════════════════════
   API
════════════════════════════════════════════════════════════ */
// Guards against out-of-order responses when the person clicks quickly.
let requestSeq = 0

// Lightweight call: only the previous period's total, for the trend badge.
async function fetchPrevious() {
  try {
    const { params, label } = periodParams(-1)
    const res = await api.get('/api/reports', { params: { ...params, totals_only: 1 } })
    return { total: num(res.data?.monthly_total), label }
  } catch (err) {
    console.error('Previous period fetch error:', err)
    return null
  }
}

async function loadReports() {
  const seq = ++requestSeq
  loading.value = true
  loadError.value = ''

  try {
    const { params, label } = periodParams(0)

    const [res, prev] = await Promise.all([
      api.get('/api/reports', { params: { ...params, include_sales: 0 } }),
      fetchPrevious(),
    ])
    if (seq !== requestSeq) return

    const d = res.data
    salesActivityRaw.value     = d.sales_activity    ?? salesActivityRaw.value
    inventorySummary.value     = d.inventory_summary ?? inventorySummary.value
    topSellingItems.value      = d.top_selling       ?? []
    monthlySales.value         = d.monthly_sales     ?? []
    monthlyProfitSeries.value  = d.monthly_profit    ?? d.daily_profit ?? []
    salesTotal.value           = num(d.monthly_total)
    periodLabel.value          = d.selected_period?.label ?? label

    previousTotal.value   = prev ? prev.total : null
    comparisonLabel.value = prev ? prev.label : ''

    // Summary cards are index-based because the API labels change per mode.
    const act = salesActivityRaw.value
    itemsSold.value   = parseDisplayNumber(act[1]?.value)
    txCount.value     = parseDisplayNumber(act[2]?.value)
    profitTotal.value = parseDisplayNumber(act[3]?.value)

    animateTo({
      sales: salesTotal.value,
      profit: profitTotal.value,
      items: itemsSold.value,
      tx: txCount.value,
    })

    lastUpdated.value = new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })
    openKey.value = null
    buildChart()
    await nextTick()
    animateBarsIn()
  } catch (err) {
    if (seq !== requestSeq) return
    console.error('Reports API error:', err)
    loadError.value = err?.response?.data?.message || 'Could not load this report. Check your connection and try again.'
  } finally {
    if (seq === requestSeq) loading.value = false
  }
}

/* ════════════════════════════════════════════════════════════
   COPY  (organised plain-text report)
════════════════════════════════════════════════════════════ */
const LINE_W = 64
const heavyRule = () => '═'.repeat(LINE_W)
const lightRule = () => '─'.repeat(LINE_W)
const kv = (label, value, w = 26) => `  ${String(label).padEnd(w, ' ')}${value}`
const section = (title) => ['', title.toUpperCase(), lightRule()]
const clip = (s, n) => (String(s).length > n ? String(s).slice(0, n - 1) + '…' : String(s))

function buildReportText() {
  const L = []
  const generated = formatStamp()
  const status = isCurrentPeriod.value ? ' (in progress)' : ''

  /* Header */
  L.push(heavyRule())
  L.push(`${BUSINESS_NAME.toUpperCase()} — ${BUSINESS_TAGLINE}`)
  L.push(reportTypeLabel.value.toUpperCase())
  L.push(heavyRule())
  L.push(kv('Period', `${periodLabel.value || ''}${status}`, 12))
  L.push(kv('Generated', generated, 12))
  L.push(kv('Currency', 'Philippine Peso (₱)', 12))

  /* Executive summary */
  L.push(...section('Executive summary'))
  L.push(kv('Total sales', peso(salesTotal.value)))
  L.push(kv('Profit', `${peso(profitTotal.value)}  (${profitMarginPct.value}% margin)`))
  if (trendPlain.value) L.push(kv('Sales trend', trendPlain.value))
  L.push(kv('Items sold', formatNumber(itemsSold.value)))
  L.push(kv('Transactions', formatNumber(txCount.value)))
  L.push(kv('Avg. per transaction', peso(avgTransaction.value)))
  L.push(kv('Items per transaction', basketSize.value))
  if (peak.value) L.push(kv(peakWord.value, `${peak.value.title}  (${peso(peak.value.total)})`))
  if (bestSeller.value) L.push(kv('Best seller', `${bestSeller.value.ProductName}  (${bestSeller.value.sharePct}% of sales)`))

  /* Top sellers */
  L.push(...section(`Top selling items — by ${topMetric.value === 'sales' ? 'sales amount' : 'units sold'}`))
  if (rankedTopItems.value.length) {
    const items = rankedTopItems.value
    const nameW = Math.min(30, Math.max(7, ...items.map((i) => String(i.ProductName).length)))
    const qtyW  = Math.max(3, ...items.map((i) => formatNumber(i.total_quantity).length))
    const salW  = Math.max(5, ...items.map((i) => peso(i.total_sales).length))
    L.push(`  ${'#'.padStart(3)}  ${'Product'.padEnd(nameW)}  ${'Qty'.padStart(qtyW)}  ${'Sales'.padStart(salW)}  Share`)
    items.forEach((i) => {
      L.push(
        `  ${String(i.rank).padStart(3)}  ${clip(i.ProductName, nameW).padEnd(nameW)}  ${formatNumber(i.total_quantity).padStart(qtyW)}  ${peso(i.total_sales).padStart(salW)}  ${i.sharePct}%`
      )
    })
    L.push(`  ${' '.repeat(3)}  ${'TOTAL'.padEnd(nameW)}  ${formatNumber(topTotals.value.qty).padStart(qtyW)}  ${peso(topTotals.value.sales).padStart(salW)}`)
  } else {
    L.push('  No sales recorded for this period.')
  }

  /* Breakdown */
  L.push(...section(`${seriesWord.value} breakdown`))
  if (breakdownRows.value.length) {
    const rows  = breakdownRows.value
    const perW  = Math.max(bucketWord.value.length, ...rows.map((r) => formatPointTitle(r.date).length))
    const salW  = Math.max(5, ...rows.map((r) => peso(r.total).length), peso(salesTotal.value).length)
    const proW  = Math.max(6, ...rows.map((r) => peso(r.profit).length), peso(profitTotal.value).length)
    L.push(`  ${bucketWord.value.padEnd(perW)}  ${'Sales'.padStart(salW)}  ${'Profit'.padStart(proW)}  Margin`)
    rows.forEach((r) => {
      L.push(`  ${formatPointTitle(r.date).padEnd(perW)}  ${peso(r.total).padStart(salW)}  ${peso(r.profit).padStart(proW)}  ${rowMargin(r)}%`)
    })
    L.push(`  ${'TOTAL'.padEnd(perW)}  ${peso(salesTotal.value).padStart(salW)}  ${peso(profitTotal.value).padStart(proW)}  ${profitMarginPct.value}%`)
  } else {
    L.push('  No sales recorded for this period.')
  }

  /* Inventory */
  L.push(...section('Inventory snapshot (current stock)'))
  inventorySummary.value.forEach((item) => {
    const flag = item.isRed && num(item.value) > 0 ? '   ⚠ needs attention' : ''
    L.push(kv(item.label, `${formatNumber(item.value)}${flag}`))
  })
  if (stockCoverage.value) {
    L.push(kv('Stock coverage', `${stockCoverage.value.active} of ${stockCoverage.value.total} items (${stockCoverage.value.pct}%)`))
  }

  L.push('')
  L.push(heavyRule())
  L.push(`End of report · ${BUSINESS_NAME} · ${generated}`)

  return L.join('\n')
}

/* ════════════════════════════════════════════════════════════
   CSV  (sectioned, spreadsheet-friendly)
════════════════════════════════════════════════════════════ */
// Quotes when needed, and neutralises spreadsheet-formula injection
// (a product named "=HYPERLINK(...)" would otherwise execute in Excel).
function csvEscape(value) {
  let s = value === null || value === undefined ? '' : String(value)
  if (typeof value === 'string' && /^[=+\-@\t\r]/.test(s)) s = "'" + s
  return /[",\r\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s
}

function buildReportCSV() {
  const rows = []
  const blank = () => rows.push([])
  const head  = (title) => { blank(); rows.push([title.toUpperCase()]) }

  /* Report information */
  rows.push([`${BUSINESS_NAME} — ${reportTypeLabel.value}`])
  rows.push(['Report information'])
  rows.push(['Business', BUSINESS_NAME])
  rows.push(['Report type', reportTypeLabel.value])
  rows.push(['Period', periodLabel.value || ''])
  rows.push(['Status', isCurrentPeriod.value ? 'In progress' : 'Closed'])
  rows.push(['Grouped by', bucketWord.value])
  rows.push(['Generated', formatStamp()])
  rows.push(['Currency', 'PHP (Philippine Peso)'])

  /* Summary */
  head('Summary')
  rows.push(['Metric', 'Value', 'Notes'])
  rows.push(['Total sales (PHP)', round2(salesTotal.value), trendPlain.value])
  rows.push(['Profit (PHP)', round2(profitTotal.value), ''])
  rows.push(['Profit margin (%)', Number(profitMarginPct.value), 'Profit ÷ total sales'])
  rows.push(['Items sold', Math.round(itemsSold.value), ''])
  rows.push(['Transactions', Math.round(txCount.value), ''])
  rows.push(['Avg. per transaction (PHP)', round2(avgTransaction.value), ''])
  rows.push(['Items per transaction', Number(basketSize.value), ''])
  rows.push([peakWord.value, peak.value ? peak.value.title : '', peak.value ? `PHP ${price(peak.value.total)} in sales` : ''])
  rows.push(['Best seller', bestSeller.value ? bestSeller.value.ProductName : '', bestSeller.value ? `${bestSeller.value.sharePct}% of sales` : ''])

  /* Top sellers */
  head(`Top selling items (ranked by ${topMetric.value === 'sales' ? 'sales amount' : 'units sold'})`)
  rows.push(['Rank', 'Product', 'Category', 'Quantity sold', 'Avg. price (PHP)', 'Total sales (PHP)', 'Share (%)'])
  rankedTopItems.value.forEach((i) => {
    rows.push([i.rank, i.ProductName, i.CategoryName || '', i.total_quantity, round2(avgPrice(i)), round2(i.total_sales), Number(i.sharePct)])
  })
  if (rankedTopItems.value.length) {
    rows.push(['', 'TOTAL', '', topTotals.value.qty, '', round2(topTotals.value.sales), ''])
  }

  /* Breakdown */
  head(`${seriesWord.value} breakdown`)
  rows.push([bucketWord.value, 'Sales (PHP)', 'Profit (PHP)', 'Margin (%)', 'Share of period sales (%)'])
  chartRows.value.forEach((r) => {
    rows.push([formatPointTitle(r.date), round2(r.total), round2(r.profit), Number(rowMargin(r)), Number(rowShare(r))])
  })
  if (chartRows.value.length) {
    rows.push(['TOTAL', round2(salesTotal.value), round2(profitTotal.value), Number(profitMarginPct.value), 100])
  }

  /* Inventory */
  head('Inventory snapshot (current stock)')
  rows.push(['Metric', 'Value'])
  inventorySummary.value.forEach((item) => rows.push([item.label, item.value]))
  if (stockCoverage.value) rows.push(['Stock coverage (%)', stockCoverage.value.pct])

  blank()
  rows.push(['End of report'])

  return rows.map((r) => r.map(csvEscape).join(',')).join('\r\n')
}

function reportFileSlug() {
  const fallback = mode.value === 'year' ? selectedYear.value : mode.value === 'month' ? selectedMonth.value : selectedDay.value
  const base = periodLabel.value || fallback
  const biz = BUSINESS_NAME.replace(/[^a-z0-9]+/gi, '-').toLowerCase()
  return `${biz}-sales-report-${String(base).replace(/[^a-z0-9]+/gi, '-').toLowerCase()}`
}

const copyState = ref('idle') // 'idle' | 'copying' | 'copied' | 'error'
const csvState  = ref('idle') // 'idle' | 'done'

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
    // BOM so Excel reads ₱ / — / ▲ correctly as UTF-8.
    const blob = new Blob(['\uFEFF' + buildReportCSV()], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${reportFileSlug()}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
    csvState.value = 'done'
    setTimeout(() => { csvState.value = 'idle' }, 1800)
  } catch (err) {
    console.error('CSV export failed:', err)
  }
}

/* ════════════════════════════════════════════════════════════
   CHART
════════════════════════════════════════════════════════════ */
let chartInstance = null
let themeObserver = null
const legendState = ref({})

const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark'
function chartColors(forceLight = false) {
  const dark = !forceLight && isDark()
  return {
    grid:     dark ? 'rgba(255,255,255,0.05)' : forceLight ? '#E2E8F0' : 'rgba(168,162,158,0.12)',
    tick:     dark ? '#8B90A8'                : forceLight ? '#475569' : '#78716C',
    ttBg:     dark ? '#1E2130'                : '#1C1917',
    ttTitle:  dark ? '#8B90A8'                : '#A8A29E',
    ttBody:   dark ? '#E8EAF0'                : '#F5F5F4',
    ttBorder: dark ? 'rgba(255,255,255,0.08)' : 'rgba(255,255,255,0.06)',
  }
}

// Bucket keys arrive as "HH:00" (day), "YYYY-MM-DD" (month) or "YYYY-MM" (year).
function parseBucket(raw) {
  const s = String(raw ?? '')
  let m = s.match(/^(\d{1,2}):\d{2}$/)
  if (m) return { kind: 'hour', date: new Date(2000, 0, 1, Number(m[1])) }
  m = s.match(/^(\d{4})-(\d{2})-(\d{2})/)
  if (m) return { kind: 'day', date: new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3])) }
  m = s.match(/^(\d{4})-(\d{2})$/)
  if (m) return { kind: 'month', date: new Date(Number(m[1]), Number(m[2]) - 1, 1) }
  return null
}

function formatPointLabel(raw) {
  const b = parseBucket(raw)
  if (!b) return String(raw ?? '')
  if (b.kind === 'hour')  return b.date.toLocaleTimeString('en-US', { hour: 'numeric' })
  if (b.kind === 'month') return b.date.toLocaleDateString('en-US', { month: 'short' })
  return b.date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
function formatPointTitle(raw) {
  const b = parseBucket(raw)
  if (!b) return String(raw ?? '')
  if (b.kind === 'hour')  return b.date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
  if (b.kind === 'month') return b.date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
  return b.date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })
}

function hoverCursor(evt, elements) {
  try {
    if (evt?.native?.target) evt.native.target.style.cursor = elements.length ? 'pointer' : 'default'
  } catch (e) { /* no-op */ }
}

function toggleDataset(datasetIndex) {
  if (!chartInstance) return
  const meta = chartInstance.getDatasetMeta(datasetIndex)
  meta.hidden = meta.hidden === null ? !chartInstance.isDatasetVisible(datasetIndex) : !meta.hidden
  chartInstance.update()
  legendState.value = { ...legendState.value, [datasetIndex]: meta.hidden }
}

// One config for both the live chart and the print snapshot.
//   print: static, no tooltip, light palette, fixed size (rendered off-screen)
function makeChartConfig(ctx, { print = false } = {}) {
  const c = chartColors(print)
  const gh = print ? 300 : 280
  const salesGradient = ctx.createLinearGradient(0, 0, 0, gh)
  salesGradient.addColorStop(0, 'rgba(234,88,12,0.24)')
  salesGradient.addColorStop(1, 'rgba(234,88,12,0)')
  const profitGradient = ctx.createLinearGradient(0, 0, 0, gh)
  profitGradient.addColorStop(0, 'rgba(16,185,129,0.22)')
  profitGradient.addColorStop(1, 'rgba(16,185,129,0)')

  const rows  = chartRows.value
  const total = num(salesTotal.value)
  // With only a handful of points, visible markers read better than a bare line.
  const dotRadius = rows.length <= 24 ? 3 : 0

  const tooltip = print
    ? { enabled: false }
    : {
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
        displayColors: true, boxWidth: 8, boxHeight: 8, boxPadding: 4,
        callbacks: {
          title: (items) => formatPointTitle(rows[items[0]?.dataIndex]?.date),
          label: (tt) => ` ${tt.dataset.label}: ₱${price(tt.raw)}`,
          footer: (items) => {
            const row = rows[items[0]?.dataIndex]
            if (!row) return ''
            const lines = []
            if (total) lines.push(`${((row.total / total) * 100).toFixed(1)}% of period sales`)
            if (row.total) lines.push(`Margin: ${((row.profit / row.total) * 100).toFixed(1)}%`)
            return lines
          },
        },
      }

  const options = {
    responsive: !print,
    maintainAspectRatio: false,
    animation: print || prefersReducedMotion() ? false : { duration: 800, easing: 'easeOutQuart' },
    interaction: { mode: 'index', intersect: false },
    plugins: { legend: { display: false }, tooltip },
    scales: {
      x: {
        grid: { display: false },
        ticks: { color: c.tick, font: { size: 11, family: 'Inter' }, maxRotation: 0, autoSkipPadding: 12 },
        border: { display: false },
      },
      y: {
        beginAtZero: true,
        grid: { color: c.grid },
        ticks: { color: c.tick, font: { size: 11, family: 'Inter' }, callback: (v) => '₱' + Number(v).toLocaleString('en-US') },
        border: { display: false },
      },
    },
  }
  if (print) options.devicePixelRatio = 2
  else options.onHover = hoverCursor

  const line = (label, key, color, gradient) => ({
    label,
    data: rows.map((x) => x[key]),
    borderColor: color,
    backgroundColor: gradient,
    borderWidth: 2.5, tension: 0.4, fill: true,
    pointRadius: dotRadius, pointBackgroundColor: color,
    pointHoverRadius: 6, pointHitRadius: 14,
    pointHoverBackgroundColor: color,
    pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
  })

  return {
    type: 'line',
    data: {
      labels: rows.map((x) => formatPointLabel(x.date)),
      datasets: [
        line('Sales', 'total', '#EA580C', salesGradient),
        line('Profit', 'profit', '#10b981', profitGradient),
      ],
    },
    plugins: print ? [] : [reportsCrosshairPlugin],
    options,
  }
}

function buildChart() {
  const el = document.getElementById('salesTrendChart')
  if (!el) return

  try {
    if (chartInstance) { chartInstance.destroy(); chartInstance = null }
    const orphan = Chart.getChart(el)
    if (orphan) orphan.destroy()
    legendState.value = {}

    chartInstance = new Chart(el, makeChartConfig(el.getContext('2d')))
  } catch (err) {
    console.error('Chart render failed:', err)
  }
}

/* ════════════════════════════════════════════════════════════
   PRINT
   The printout is a dedicated sheet (see .print-sheet). The chart is
   rendered off-screen in a light palette and handed over as an image,
   so it's always legible on paper — even when the app is in dark mode.
════════════════════════════════════════════════════════════ */
const printing      = ref(false)
const printChartImg = ref('')

async function captureChartForPrint() {
  if (!chartRows.value.length) return ''

  const holder = document.createElement('div')
  holder.style.cssText = 'position:fixed;left:-10000px;top:0;width:720px;height:300px;pointer-events:none;'
  const canvas = document.createElement('canvas')
  canvas.width = 720
  canvas.height = 300
  holder.appendChild(canvas)
  document.body.appendChild(holder)

  let img = ''
  let tmp = null
  try {
    tmp = new Chart(canvas, makeChartConfig(canvas.getContext('2d'), { print: true }))
    await new Promise((r) => requestAnimationFrame(r))
    img = tmp.toBase64Image('image/png', 1)
  } catch (err) {
    console.error('Print chart capture failed:', err)
  } finally {
    if (tmp) tmp.destroy()
    holder.remove()
  }
  return img
}

async function printReport() {
  if (printing.value) return
  printing.value = true
  try {
    snapAnimation()
    barsReady.value = true
    printGeneratedAt.value = formatStamp()
    printChartImg.value = await captureChartForPrint()
    await nextTick()
    // One extra frame so the image is decoded before the print dialog opens.
    await new Promise((r) => requestAnimationFrame(r))
    window.print()
  } finally {
    printing.value = false
  }
}

onMounted(async () => {
  await loadReports()
  // Rebuild the chart when the app theme toggles so its colours follow.
  themeObserver = new MutationObserver(() => buildChart())
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] })
})

onBeforeUnmount(() => {
  requestSeq++
  cancelAnimationFrame(animFrame)
  if (themeObserver) themeObserver.disconnect()
  if (chartInstance) chartInstance.destroy()
})
</script>

<!-- ══════════════════════════════════════════════════════
     SCOPED styles — component layout only.
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

  --rp-green-icon:  #D1FAE5;
  --rp-blue-icon:   #DBEAFE;
  --rp-violet-icon: #E0E7FF;
  --rp-red-icon:    #FFE4E6;

  --radius: 16px;

  min-height: 100%;
  background: var(--rp-bg);
  padding: 28px 28px 56px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 18px;
  transition: background-color 0.22s ease;
}

html[data-theme="dark"] .reports {
  --rp-green-icon:  rgba(16,185,129,0.18);
  --rp-blue-icon:   rgba(59,130,246,0.18);
  --rp-violet-icon: rgba(99,102,241,0.18);
  --rp-red-icon:    rgba(244,63,94,0.18);
  --rp-green-soft:  rgba(16,185,129,0.16);
  --rp-red-soft:    rgba(244,63,94,0.16);
}

/* Visible keyboard focus everywhere */
.reports button:focus-visible,
.reports select:focus-visible,
.reports input:focus-visible { outline: 2px solid var(--rp-accent); outline-offset: 2px; }

/* Print sheet — hidden on screen */
.print-sheet { display: none; }

/* ── PAGE HEADER ── */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px; }
.page-title  { font-size: 26px; font-weight: 800; color: var(--rp-text-primary); letter-spacing: -.03em; margin: 0; }
.page-sub    { display: flex; align-items: center; gap: 8px; margin: 6px 0 0; font-size: 14px; font-weight: 600; color: var(--rp-text-secondary); }
.chip { font-size: 11.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }
.chip--live { color: var(--rp-green); background: var(--rp-green-soft); }

.report-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.rt-btn {
  display: flex; align-items: center; gap: 8px;
  border: 1px solid var(--rp-border-strong);
  background: var(--rp-surface-sunken);
  color: var(--rp-text-secondary);
  font-family: inherit; font-size: 13px; font-weight: 600;
  padding: 8px 15px; border-radius: 11px; cursor: pointer;
  transition: background-color .16s ease, border-color .16s ease, color .16s ease;
}
.rt-btn i { font-size: 12px; }
.rt-btn:hover:not(:disabled) { background: var(--rp-surface-raised); border-color: var(--rp-accent-border); color: var(--rp-text-primary); }
.rt-btn:disabled { opacity: .6; cursor: default; }
.rt-btn--primary { background: var(--rp-accent); border-color: var(--rp-accent); color: #fff; }
.rt-btn--primary:hover:not(:disabled) { background: #FB923C; border-color: #FB923C; color: #fff; }

/* ── PERIOD BAR ── */
.period-bar {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  padding: 10px 12px;
  background: var(--rp-surface);
  border: 1px solid var(--rp-border);
  border-radius: 14px;
  box-shadow: var(--rp-shadow);
}
.period-nav { display: flex; align-items: center; gap: 6px; }
.period-status { margin-left: auto; display: flex; align-items: center; gap: 10px; }
.period-status-text { font-size: 12px; font-weight: 600; color: var(--rp-text-muted); white-space: nowrap; }
.period-status-text--busy { color: var(--rp-accent); }

.seg {
  display: inline-flex; gap: 2px; padding: 3px;
  background: var(--rp-surface-sunken);
  border: 1px solid var(--rp-border);
  border-radius: 11px;
}
.seg-btn {
  border: none; background: transparent; cursor: pointer;
  font-family: inherit; font-size: 12.5px; font-weight: 600;
  color: var(--rp-text-muted);
  padding: 7px 14px; border-radius: 8px; white-space: nowrap;
  transition: background-color .16s ease, color .16s ease;
}
.seg-btn:hover:not(.seg-btn--active) { color: var(--rp-text-primary); }
.seg-btn--active { background: var(--rp-accent); color: #fff; }
.seg--sm .seg-btn { padding: 5px 12px; font-size: 12px; }

.icon-btn {
  width: 36px; height: 36px; border-radius: 10px; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  border: 1px solid var(--rp-border-strong);
  background: var(--rp-surface-sunken);
  color: var(--rp-text-secondary);
  font-size: 12px;
  transition: background-color .16s ease, border-color .16s ease, color .16s ease;
}
.icon-btn--sm { width: 32px; height: 32px; border-radius: 9px; }
.icon-btn:hover:not(:disabled) { border-color: var(--rp-accent-border); color: var(--rp-text-primary); background: var(--rp-surface-raised); }
.icon-btn:disabled { opacity: .4; cursor: not-allowed; }

.period-picker-input {
  border: 1.5px solid var(--rp-border-strong);
  background: var(--rp-surface);
  border-radius: 11px;
  font-family: inherit; font-size: 13.5px; font-weight: 600;
  color: var(--rp-text-primary);
  cursor: pointer;
  padding: 7px 12px; min-width: 150px;
  transition: border-color .15s ease;
}
.period-picker-input:hover,
.period-picker-input:focus { border-color: var(--rp-accent-border); }
.period-picker-input::-webkit-calendar-picker-indicator { cursor: pointer; border-radius: 4px; padding: 2px; }
html[data-theme="dark"] .period-picker-input::-webkit-calendar-picker-indicator { filter: invert(1) brightness(1.6); }

.link-btn {
  border: none; background: transparent; cursor: pointer;
  font-family: inherit; font-size: 13px; font-weight: 700;
  color: var(--rp-accent); padding: 6px 8px; border-radius: 8px;
}
.link-btn:hover { background: var(--rp-accent-soft); }
.link-btn--block { display: block; width: 100%; margin-top: 12px; text-align: center; }

/* ── NOTICE ── */
.notice {
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  padding: 12px 14px; border-radius: 12px;
  font-size: 13px; font-weight: 600; color: var(--rp-red);
  background: var(--rp-red-soft);
  border: 1px solid rgba(244,63,94,0.28);
}
.notice span { flex: 1; min-width: 200px; }

/* ── BODY ── */
.report-body { display: flex; flex-direction: column; gap: 20px; transition: opacity .15s ease; }
.report-body.is-loading { opacity: .55; pointer-events: none; }

.section { display: flex; flex-direction: column; gap: 12px; }
.section-title { font-size: 17px; font-weight: 700; color: var(--rp-text-primary); margin: 0; letter-spacing: -.01em; }

/* ── SUMMARY ──
   4-column grid: the hero card spans 2 columns × 2 rows on the left and the
   four KPI cards tile 2 × 2 beside it. Every card is a size container so the
   numbers scale with their card and always stay on ONE line. */
.summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }

.stat {
  display: flex; align-items: center; gap: 14px;
  background: var(--rp-surface);
  border: 1px solid var(--rp-border);
  border-radius: var(--radius);
  box-shadow: var(--rp-shadow);
  padding: 18px 20px;
  min-width: 0;
}
.stat--hero {
  grid-column: span 2; grid-row: span 2;
  flex-direction: column; align-items: stretch; justify-content: space-between; gap: 20px;
  background: var(--rp-accent-soft);
  border-color: var(--rp-accent-border);
  padding: 22px 24px;
}
.hero-cols { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 24px; }
.hero-col  { display: flex; flex-direction: column; gap: 6px; min-width: 0; container-type: inline-size; }
.hero-col--profit { padding-left: 24px; border-left: 1px solid var(--rp-accent-border); }

.stat-icon {
  width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 16px;
}
.stat-icon--orange { background: var(--rp-accent-soft); color: var(--rp-accent); }
.stat-icon--blue   { background: var(--rp-blue-icon);   color: var(--rp-blue); }
.stat-icon--violet { background: var(--rp-violet-icon); color: var(--rp-violet); }
.stat-icon--green  { background: var(--rp-green-icon);  color: var(--rp-green); }

.stat-body  { flex: 1; display: flex; flex-direction: column; gap: 3px; min-width: 0; container-type: inline-size; }
.stat-label { margin: 0; font-size: 12.5px; font-weight: 600; color: var(--rp-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* One line, always. The font scales with the card width (cqw) and any
   extreme overflow falls back to an ellipsis (full value is in the title). */
.stat-value {
  margin: 0; font-size: 22px; font-weight: 800; color: var(--rp-text-primary);
  letter-spacing: -.03em; line-height: 1.15;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  font-variant-numeric: tabular-nums;
}
.stat-body .stat-value { font-size: clamp(17px, 10cqw, 24px); }
.stat-value--text { font-size: 17px; letter-spacing: -.015em; }
.stat-body .stat-value--text { font-size: clamp(15px, 7.5cqw, 19px); }
.hero-col .stat-value--xl { font-size: clamp(20px, 10.5cqw, 36px); color: var(--rp-accent); }
.hero-col .stat-value--green { color: var(--rp-green); }
.stat-sub  { margin: 0; font-size: 12px; color: var(--rp-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.stat-note { margin: 0; font-size: 12px; color: var(--rp-text-muted); }

.trend-line { display: flex; align-items: center; gap: 8px; margin: 2px 0 0; font-size: 12.5px; color: var(--rp-text-secondary); font-weight: 600; flex-wrap: wrap; }
.trend-badge {
  font-size: 11.5px; font-weight: 700; padding: 2px 8px; border-radius: 20px;
  display: inline-flex; align-items: center; gap: 4px;
}
.trend-badge i { font-size: 10px; }
.trend-badge.up   { color: var(--rp-green); background: var(--rp-green-soft); }
.trend-badge.down { color: var(--rp-red);   background: var(--rp-red-soft); }
.trend-badge.flat { color: var(--rp-text-muted); background: var(--rp-surface-raised); }

.margin-meter { display: flex; flex-direction: column; gap: 8px; padding-top: 16px; border-top: 1px solid var(--rp-accent-border); }
.margin-head { display: flex; align-items: baseline; justify-content: space-between; font-size: 12.5px; font-weight: 600; color: var(--rp-text-muted); }
.margin-head strong { font-size: 14px; font-weight: 800; color: var(--rp-text-primary); font-variant-numeric: tabular-nums; }
.margin-track { height: 8px; border-radius: 8px; background: var(--rp-surface-sunken); overflow: hidden; }
.margin-fill { height: 100%; border-radius: 8px; background: var(--rp-green); transition: width .7s cubic-bezier(.16, 1, .3, 1); }

/* ── CARDS ── */
.card {
  background: var(--rp-surface);
  border: 1px solid var(--rp-border);
  border-radius: var(--radius);
  box-shadow: var(--rp-shadow);
  padding: 22px 24px 20px;
  min-width: 0;
}
.card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.card-title { font-size: 16px; font-weight: 700; color: var(--rp-text-primary); margin: 0; letter-spacing: -.01em; }
.card-sub   { margin: 4px 0 0; font-size: 12.5px; color: var(--rp-text-muted); line-height: 1.45; }

/* ── TREND CHART ── */
.legend-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.legend-chip {
  display: flex; align-items: center; gap: 6px;
  font-family: inherit; font-size: 12px; font-weight: 600;
  color: var(--rp-text-secondary);
  background: var(--rp-surface-raised);
  border: 1px solid var(--rp-border);
  border-radius: 20px; padding: 5px 12px 5px 9px;
  cursor: pointer;
  transition: opacity .18s ease, border-color .18s ease;
}
.legend-chip:hover { border-color: var(--rp-accent-border); }
.legend-chip--off { opacity: .45; }
.legend-dot { width: 8px; height: 8px; border-radius: 50%; }
.legend-chip--orange .legend-dot { background: var(--rp-accent); }
.legend-chip--green .legend-dot  { background: var(--rp-green); }

.chart-area { position: relative; width: 100%; height: 300px; }
.chart-empty {
  position: absolute; inset: 0; margin: 0;
  display: flex; flex-direction: column; gap: 10px; align-items: center; justify-content: center;
  font-size: 13px; color: var(--rp-text-muted); pointer-events: none;
}
.chart-empty i { font-size: 22px; color: var(--rp-text-faint); }

/* ── TOP SELLERS + INVENTORY ── */
.content-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 20px; align-items: start; }

.top-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.top-row {
  background: var(--rp-surface-raised);
  border: 1px solid var(--rp-border);
  border-radius: 12px;
  overflow: hidden;
  transition: border-color .16s ease, background-color .16s ease;
}
.top-row:hover { border-color: var(--rp-accent-border); }
.top-row--open { background: var(--rp-accent-soft); border-color: var(--rp-accent-border); }

.top-row-btn {
  display: flex; align-items: center; gap: 14px; width: 100%;
  padding: 12px 14px; border: none; background: transparent;
  font: inherit; color: inherit; text-align: left; cursor: pointer;
}
.top-rank {
  width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800;
  background: var(--rp-surface-sunken); color: var(--rp-text-secondary);
}
.top-rank--lead { background: var(--rp-accent); color: #fff; }

.top-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 7px; }
.top-head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; }
.top-name { font-size: 13.5px; font-weight: 600; color: var(--rp-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-value { font-size: 13px; font-weight: 700; color: var(--rp-text-secondary); flex-shrink: 0; white-space: nowrap; font-variant-numeric: tabular-nums; }

.top-bar { display: block; height: 6px; border-radius: 6px; background: var(--rp-surface-sunken); overflow: hidden; }
.top-bar-fill {
  display: block; height: 100%; width: 0; border-radius: 6px;
  background: var(--rp-violet); opacity: .55;
  transition: width .8s cubic-bezier(.16, 1, .3, 1);
}
.top-bar-fill--lead { background: var(--rp-accent); opacity: 1; }

.top-share { width: 46px; flex-shrink: 0; text-align: right; font-size: 12px; font-weight: 600; color: var(--rp-text-muted); font-variant-numeric: tabular-nums; }
.top-chevron { font-size: 10px; color: var(--rp-text-faint); transition: transform .18s ease; }
.top-row--open .top-chevron { transform: rotate(180deg); }

.top-detail {
  display: none; margin: 0; padding: 2px 14px 14px 56px;
  grid-template-columns: repeat(4, 1fr); gap: 10px;
}
.top-row--open .top-detail { display: grid; }
.td-item { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.td-item dt { font-size: 11.5px; color: var(--rp-text-muted); }
.td-item dd { margin: 0; font-size: 13.5px; font-weight: 700; color: var(--rp-text-primary); overflow-wrap: anywhere; }

.empty {
  margin: 0; padding: 32px 12px;
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  text-align: center; font-size: 13px; color: var(--rp-text-muted);
}
.empty i { font-size: 22px; color: var(--rp-text-faint); }

/* Inventory */
.inv-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
.inv-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--rp-border); }
.inv-row:last-child { border-bottom: none; }
.inv-icon {
  width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 13px;
  background: var(--rp-violet-icon); color: var(--rp-violet);
}
.inv-label { flex: 1; font-size: 13.5px; font-weight: 600; color: var(--rp-text-secondary); }
.inv-value { font-size: 17px; font-weight: 800; color: var(--rp-text-primary); letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
.inv-row--alert .inv-icon  { background: var(--rp-red-icon); color: var(--rp-red); }
.inv-row--alert .inv-value { color: var(--rp-red); }

.coverage { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--rp-border); }
.coverage-head { display: flex; justify-content: space-between; gap: 8px; font-size: 12.5px; color: var(--rp-text-muted); margin-bottom: 8px; }
.coverage-head strong { color: var(--rp-text-primary); font-weight: 700; }
.coverage-track { height: 6px; border-radius: 6px; background: var(--rp-surface-sunken); overflow: hidden; }
.coverage-fill { height: 100%; border-radius: 6px; background: var(--rp-green); transition: width .6s ease; }

/* ── RESPONSIVE ── */
@media (max-width: 1100px) {
  .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .stat--hero { grid-column: 1 / -1; grid-row: auto; }
  .content-grid { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 700px) {
  .reports { padding: 16px 16px 40px; }
  .page-header { flex-direction: column; align-items: stretch; }
  .report-toolbar { justify-content: stretch; }
  .rt-btn { flex: 1; justify-content: center; }
  .period-bar { flex-direction: column; align-items: stretch; }
  .seg { width: 100%; }
  .seg-btn { flex: 1; text-align: center; }
  .period-nav { width: 100%; }
  .period-picker-input { flex: 1; min-width: 0; }
  .period-status { margin-left: 0; justify-content: space-between; }
  .stat--hero { padding: 18px 16px; }
  .hero-cols { gap: 14px; }
  .hero-col--profit { padding-left: 14px; }
  /* KPI cards: icon above the text so the numbers get the full width */
  .stat:not(.stat--hero) { flex-direction: column; align-items: flex-start; gap: 10px; padding: 16px; }
  .stat:not(.stat--hero) .stat-body { width: 100%; flex: none; }
  .card { padding: 18px 16px 16px; }
  .chart-area { height: 240px; }
  .top-share { display: none; }
  .top-detail { grid-template-columns: 1fr 1fr; padding-left: 14px; }
}
@media (prefers-reduced-motion: reduce) {
  .reports * { transition: none !important; }
}
</style>

<!-- ══════════════════════════════════════════════════════
     GLOBAL (unscoped) — print rules only.
     Prints ONLY .print-sheet: a purpose-built, always-light,
     colour-coded report (not a restyled copy of the screen UI).
══════════════════════════════════════════════════════ -->
<style>
@media print {
  @page {
    size: auto;
    margin: 13mm 13mm 16mm;
    @bottom-right {
      content: "Page " counter(page) " of " counter(pages);
      font: 600 8.5px 'Inter', Arial, sans-serif;
      color: #64748b;
    }
  }

  /* The app shell often fixes html/body height with overflow hidden so the
     sidebar layout can scroll internally; print would then only capture the
     visible viewport. Force natural height/overflow so the whole report prints. */
  html, body { height: auto !important; overflow: visible !important; background: #fff !important; }
  body * { overflow: visible !important; }

  body * { visibility: hidden !important; }
  #reports-print-area,
  #reports-print-area * { visibility: visible !important; }

  /* Pull the report out of the app shell's flow so hidden sidebar/nav boxes
     can't squeeze it into a corner of the page. */
  #reports-print-area {
    position: absolute !important;
    top: 0 !important; left: 0 !important;
    width: 100% !important; max-width: 100% !important;
    padding: 0 !important; margin: 0 !important;
    background: #fff !important;
    display: block !important;
    font-family: 'Inter', Arial, Helvetica, sans-serif !important;
    color: #0f172a !important;
  }

  /* Hide every interactive piece; show only the print sheet */
  #reports-print-area > :not(.print-sheet) { display: none !important; }
  #reports-print-area .print-sheet { display: block !important; }

  .print-sheet,
  .print-sheet * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    box-sizing: border-box;
  }
  .print-sheet { font-size: 10.5px; line-height: 1.45; color: #0f172a; }
  .print-sheet p, .print-sheet h1, .print-sheet h2 { margin: 0; }

  /* ── Letterhead ── */
  .ps-band { height: 9px; border-radius: 2px; background: linear-gradient(90deg, #EA580C 0%, #F59E0B 60%, #10b981 100%); }
  .ps-head-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; padding: 14px 0 12px; }
  .ps-brand-name { font-size: 20px; font-weight: 800; letter-spacing: -.02em; color: #EA580C; }
  .ps-brand-tag  { margin-top: 2px; font-size: 10px; font-weight: 600; color: #64748b; }
  .ps-meta { display: flex; flex-direction: column; gap: 3px; text-align: right; font-size: 10.5px; color: #334155; }
  .ps-meta span {
    display: inline-block; min-width: 64px; margin-right: 8px;
    font-size: 8.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8;
  }
  .ps-title-row {
    display: flex; align-items: flex-end; justify-content: space-between; gap: 16px;
    padding: 12px 16px; border-radius: 8px;
    background: #0f172a; color: #fff;
  }
  .ps-title  { font-size: 20px; font-weight: 800; letter-spacing: -.02em; color: #fff; }
  .ps-period { font-size: 13px; font-weight: 600; color: #fdba74; }
  .ps-chip {
    margin-left: 8px; padding: 2px 8px; border-radius: 10px;
    font-size: 9px; font-weight: 700; color: #064e3b; background: #6ee7b7; vertical-align: middle;
  }

  /* ── Sections ── */
  .ps-section { margin-top: 20px; }
  .ps-section--keep { break-inside: avoid; page-break-inside: avoid; }
  .ps-h2 {
    display: flex; align-items: baseline; gap: 8px;
    padding: 0 0 6px 10px; margin-bottom: 10px;
    font-size: 13px; font-weight: 800; letter-spacing: -.01em; color: #0f172a;
    border-left: 4px solid #EA580C; border-bottom: 1px solid #e2e8f0;
    break-after: avoid; page-break-after: avoid;
  }
  .ps-h2 small { font-size: 9.5px; font-weight: 600; color: #94a3b8; }
  .ps-empty { padding: 14px; text-align: center; color: #94a3b8; border: 1px dashed #cbd5e1; border-radius: 6px; }

  /* ── KPI tiles ── */
  .ps-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
  .ps-kpi {
    --t: #EA580C; --ts: #FFF1E8;
    padding: 9px 11px 10px; border-radius: 6px;
    background: var(--ts); border: 1px solid var(--ts); border-top: 3px solid var(--t);
    break-inside: avoid; page-break-inside: avoid;
  }
  .ps-kpi--orange { --t: #EA580C; --ts: #FFF1E8; }
  .ps-kpi--green  { --t: #059669; --ts: #E7F8F0; }
  .ps-kpi--teal   { --t: #0D9488; --ts: #E4F6F4; }
  .ps-kpi--amber  { --t: #D97706; --ts: #FEF3E2; }
  .ps-kpi--violet { --t: #6D5BD0; --ts: #EFEDFC; }
  .ps-kpi--blue   { --t: #2563EB; --ts: #E8F0FE; }
  .ps-kpi--rose   { --t: #DB2777; --ts: #FDEBF3; }
  .ps-kpi--cyan   { --t: #0891B2; --ts: #E3F5F9; }
  .ps-kpi-label { font-size: 8.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--t); }
  .ps-kpi-value { margin-top: 3px !important; font-size: 16px; font-weight: 800; letter-spacing: -.02em; color: #0f172a; white-space: nowrap; }
  .ps-kpi-value--text { font-size: 12.5px; white-space: normal; overflow-wrap: anywhere; line-height: 1.25; letter-spacing: -.01em; }
  .ps-kpi-sub { margin-top: 2px !important; font-size: 9px; color: #64748b; }
  .ps-trend { margin-top: 10px !important; padding: 7px 12px; border-radius: 6px; font-size: 10.5px; border-left: 4px solid; }
  .ps-trend--up   { color: #065f46; background: #E7F8F0; border-color: #059669; }
  .ps-trend--down { color: #9f1239; background: #FFE4E6; border-color: #e11d48; }
  .ps-trend--flat { color: #475569; background: #f1f5f9; border-color: #94a3b8; }

  /* ── Chart ── */
  .ps-legend { display: flex; gap: 14px; margin-bottom: 6px; font-size: 9.5px; font-weight: 600; color: #475569; }
  .ps-legend span { display: inline-flex; align-items: center; gap: 5px; }
  .ps-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
  .ps-dot--orange { background: #EA580C; }
  .ps-dot--green  { background: #10b981; }
  .ps-chart { display: block; width: 100%; height: auto; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px; }

  /* ── Tables ── */
  .ps-table { width: 100%; border-collapse: collapse; font-size: 10px; }
  .ps-table thead { display: table-header-group; }
  .ps-table tfoot { display: table-footer-group; }
  .ps-table tr { break-inside: avoid; page-break-inside: avoid; }
  .ps-table th {
    padding: 7px 8px; text-align: left;
    font-size: 8.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
    color: #fff; background: #0f172a;
  }
  .ps-table th:first-child { border-top-left-radius: 5px; }
  .ps-table th:last-child  { border-top-right-radius: 5px; }
  .ps-table td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
  .ps-table tbody tr:nth-child(even) td { background: #f8fafc; }
  .ps-table .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
  .ps-table .c-rank { width: 30px; text-align: center; }
  .ps-table .c-share { width: 120px; }
  .ps-table tfoot td {
    padding: 7px 8px; font-weight: 800; color: #0f172a;
    background: #FFF1E8; border-top: 2px solid #EA580C; border-bottom: none;
  }
  .ps-strong { font-weight: 700; color: #0f172a; }
  .ps-pos { color: #047857; font-weight: 700; }
  .ps-neg { color: #be123c; font-weight: 700; }

  .ps-rank {
    display: inline-flex; align-items: center; justify-content: center;
    width: 18px; height: 18px; border-radius: 50%;
    font-size: 9px; font-weight: 800; color: #334155; background: #e2e8f0;
  }
  .ps-rank--lead { color: #fff; background: #EA580C; }
  .ps-share { display: flex; align-items: center; gap: 6px; }
  .ps-share-bar { flex: 1; height: 5px; border-radius: 5px; background: #e2e8f0; display: block; }
  .ps-share-bar i { display: block; height: 100%; border-radius: 5px; background: #6D5BD0; }
  .ps-share-bar i.ps-share-bar--lead { background: #EA580C; }
  .ps-share b { width: 34px; text-align: right; font-size: 9.5px; color: #475569; font-variant-numeric: tabular-nums; }

  /* ── Inventory ── */
  .ps-inv { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
  .ps-inv-box { padding: 9px 11px; border-radius: 6px; background: #EFEDFC; border-top: 3px solid #6D5BD0; }
  .ps-inv-box p { font-size: 8.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #6D5BD0; }
  .ps-inv-box strong { display: block; margin-top: 3px; font-size: 16px; font-weight: 800; color: #0f172a; }
  .ps-inv-box--alert { background: #FFE4E6; border-top-color: #e11d48; }
  .ps-inv-box--alert p, .ps-inv-box--alert strong { color: #be123c; }
  .ps-cover { margin-top: 10px; }
  .ps-cover-head { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 9.5px; color: #64748b; }
  .ps-cover-head strong { color: #0f172a; }
  .ps-cover-track { height: 6px; border-radius: 6px; background: #e2e8f0; }
  .ps-cover-track i { display: block; height: 100%; border-radius: 6px; background: #10b981; }

  /* ── End of report ── */
  .ps-end {
    display: flex; justify-content: space-between; gap: 12px;
    margin-top: 24px; padding-top: 8px;
    font-size: 8.5px; color: #94a3b8; border-top: 2px solid #EA580C;
  }
}
</style>