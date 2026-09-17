<template>
  <Layout title="Home">
    <div class="dash">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div class="page-header-left">
          <p class="page-eyebrow">Sales Intelligence</p>
          <h1 class="page-title">Dashboard</h1>
        </div>
        <div class="page-header-right">
          <div class="range-select-wrap">
            <i class="fa-regular fa-calendar range-icon"></i>
            <select v-model="selectedDateRange" @change="loadDashboard" class="range-select">
              <option value="today">Today</option>
              <option value="this_week">This Week</option>
              <option value="this_month">This Month</option>
            </select>
            <i class="fa-solid fa-chevron-down range-caret"></i>
          </div>
          <router-link to="/reports" class="full-report-link">
            Full Report<i class="fa-solid fa-arrow-right"></i>
          </router-link>
        </div>
      </div>

      <!-- ── KPI STRIP ── -->
      <div class="kpi-strip">

        <div class="kpi-card kpi-card--featured">
          <div class="kpi-card-inner">
            <div class="kpi-icon-wrap kpi-icon-wrap--orange">
              <i class="fa-solid fa-peso-sign"></i>
            </div>
            <div class="kpi-body">
              <p class="kpi-label">Total Sales</p>
              <p class="kpi-value">
                ₱{{ formatNumber(displayedMonthlyTotal) }}
                <span
                  v-if="salesTrend.available"
                  class="kpi-trend"
                  :class="salesTrend.direction"
                  :title="`vs ${comparisonLabel}`"
                >
                  <i :class="trendIcon"></i>{{ salesTrend.percent }}%
                </span>
              </p>
              <p class="kpi-sub">
                Monthly Revenue<span v-if="salesTrend.available"> · vs {{ comparisonLabel }}</span>
              </p>
            </div>
          </div>
          <div class="kpi-sparkline">
            <canvas id="sparklineChart" v-if="hasPeriodSales"></canvas>
            <div v-else class="sparkline-empty">
              <span class="sparkline-empty-line"></span>
              <span class="sparkline-empty-text">No data yet</span>
            </div>
          </div>
          <div class="kpi-accent-bar kpi-accent-bar--orange"></div>
        </div>

        <div class="kpi-card">
          <div class="kpi-card-inner">
            <div class="kpi-icon-wrap kpi-icon-wrap--green">
              <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="kpi-body">
              <p class="kpi-label">Total Profit</p>
              <p class="kpi-value kpi-value--green">₱{{ formatNumber(displayedTotalProfit) }}</p>
              <p class="kpi-sub">Net Earnings · {{ profitMarginPct }}% margin</p>
            </div>
          </div>
          <div class="kpi-accent-bar kpi-accent-bar--green"></div>
        </div>

        <div class="kpi-card">
          <div class="kpi-card-inner">
            <div class="kpi-icon-wrap kpi-icon-wrap--blue">
              <i class="fa-solid fa-bolt"></i>
            </div>
            <div class="kpi-body">
              <p class="kpi-label">Today's Sales</p>
              <p class="kpi-value kpi-value--blue">₱{{ formatNumber(displayedTodaySales) }}</p>
              <p class="kpi-sub">{{ transactionsToday }} transactions</p>
            </div>
          </div>
          <div class="kpi-accent-bar kpi-accent-bar--blue"></div>
        </div>

        <div class="kpi-card">
          <div class="kpi-card-inner">
            <div class="kpi-icon-wrap kpi-icon-wrap--violet">
              <i class="fa-solid fa-box-open"></i>
            </div>
            <div class="kpi-body">
              <p class="kpi-label">Items Sold Today</p>
              <p class="kpi-value kpi-value--violet">{{ formatNumber(displayedItemsSoldToday) }}</p>
              <p class="kpi-sub">Products moved</p>
            </div>
          </div>
          <div class="kpi-accent-bar kpi-accent-bar--violet"></div>
        </div>

      </div>

      <!-- ── MAIN CHART (simplified glance view — full trend lives on Reports) ── -->
      <div class="card card--main">
        <div class="card-header">
          <div>
            <p class="card-eyebrow">Trend</p>
            <h2 class="card-title">Sales &amp; Profit, {{ rangeLabel }}</h2>
          </div>
          <router-link to="/reports" class="card-header-link">
            Full trend &amp; export<i class="fa-solid fa-arrow-right"></i>
          </router-link>
        </div>
        <div class="chart-area chart-area--medium">
          <canvas id="monthlySalesChart" v-if="hasPeriodSales"></canvas>
          <div v-else class="chart-empty-state">
            <i class="fa-solid fa-chart-line chart-empty-icon"></i>
            <p class="chart-empty-title">No sales recorded yet</p>
            <p class="chart-empty-sub">This isn't loading — there's simply no sales data for {{ rangeLabel.toLowerCase() }} so far.</p>
          </div>
        </div>
      </div>

      <!-- ── SECOND ROW ── -->
      <div class="row-2">

        <!-- Today's Margin — a genuine whole split into two real parts
             (profit vs. cost of goods for today's sales), so a donut
             is the honest chart here, unlike the old "Today's Sales /
             Today's Profit / Items Sold" trio which weren't parts of
             one total. -->
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Snapshot</p>
              <h2 class="card-title">Today's Margin</h2>
            </div>
          </div>
          <div class="chart-area chart-area--donut">
            <template v-if="hasTodaySales">
              <canvas id="todayMarginChart"></canvas>
              <div class="donut-center">
                <p class="donut-center-value">{{ todaysMarginPct }}%</p>
                <p class="donut-center-label">Margin</p>
              </div>
            </template>
            <div v-else class="donut-empty-state">
              <div class="donut-empty-ring"></div>
              <div class="donut-empty-center">
                <i class="fa-regular fa-moon donut-empty-icon"></i>
                <p class="donut-empty-title">No sales yet</p>
                <p class="donut-empty-sub">today</p>
              </div>
            </div>
          </div>
          <div class="donut-legend">
            <div class="donut-row">
              <span class="donut-dot donut-dot--green"></span>
              <span class="donut-row-label">Profit</span>
              <strong>₱{{ formatNumber(todaysProfit) }}</strong>
            </div>
            <div class="donut-row">
              <span class="donut-dot donut-dot--orange"></span>
              <span class="donut-row-label">Cost of Goods</span>
              <strong>₱{{ formatNumber(todaysCost) }}</strong>
            </div>
          </div>
        </div>

        <!-- Inventory — gauges only (the bar chart below repeated the
             same four numbers, so it's been dropped in favor of one
             clear view) -->
        <div class="card card--wide">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Stock Status</p>
              <h2 class="card-title">Inventory Summary</h2>
            </div>
          </div>

          <div class="inv-grid">
            <div class="inv-gauge-card">
              <div class="inv-gauge">
                <canvas id="gaugeActive"></canvas>
                <div class="inv-gauge-center">
                  <span class="inv-gauge-num">{{ inventorySummary.active_items ?? 0 }}</span>
                </div>
              </div>
              <p class="inv-gauge-label">Active Items</p>
              <p class="inv-gauge-sub">of {{ inventorySummary.total_items ?? 0 }} groups</p>
            </div>

            <div class="inv-gauge-card">
              <div class="inv-gauge">
                <canvas id="gaugeStock"></canvas>
                <div class="inv-gauge-center">
                  <span class="inv-gauge-num">{{ inventorySummary.quantity_in_hand ?? 0 }}</span>
                </div>
              </div>
              <p class="inv-gauge-label">In Hand</p>
              <p class="inv-gauge-sub">{{ inventorySummary.quantity_to_receive ?? 0 }} incoming</p>
            </div>

            <div class="inv-gauge-card inv-gauge-card--alert">
              <div class="inv-gauge">
                <canvas id="gaugeLow"></canvas>
                <div class="inv-gauge-center">
                  <span class="inv-gauge-num inv-gauge-num--red">{{ inventorySummary.low_stock_items ?? 0 }}</span>
                </div>
              </div>
              <p class="inv-gauge-label">Low Stock</p>
              <p class="inv-gauge-sub inv-gauge-sub--red">Needs reorder</p>
            </div>
          </div>
        </div>

      </div>

      <!-- ── TOP SELLING (trimmed to top 5, no controls — full ranked
           breakdown with sort/pin/tooltip lives on Reports) ── -->
      <div class="card">
        <div class="card-header">
          <div>
            <p class="card-eyebrow">Performance</p>
            <h2 class="card-title">Top Selling Items</h2>
          </div>
          <router-link to="/reports" class="card-header-link">
            Full breakdown<i class="fa-solid fa-arrow-right"></i>
          </router-link>
        </div>

        <div class="top-list top-list--compact">
          <div
            v-for="item in rankedTopItems"
            :key="item.ProductName"
            class="top-row top-row--static"
            :class="`top-row--rank${item.rank <= 3 ? item.rank : 'default'}`"
            :style="{ transitionDelay: (item.rank - 1) * 40 + 'ms' }"
          >
            <div class="top-row-rank" :class="`top-row-rank--${item.rank <= 3 ? item.rank : 'default'}`">
              <i v-if="item.rank === 1" class="fa-solid fa-crown"></i>
              <span v-else>{{ item.rank }}</span>
            </div>

            <div class="top-row-main">
              <div class="top-row-head">
                <span class="top-row-name">{{ item.ProductName }}</span>
                <span class="top-row-value">₱{{ formatNumber(item.total_sales) }}</span>
              </div>
              <div class="top-row-bar-track">
                <div
                  class="top-row-bar-fill"
                  :class="`top-row-bar-fill--rank${item.rank <= 3 ? item.rank : 'default'}`"
                  :style="{ width: (barsReady ? item.barPct : 0) + '%' }"
                ></div>
              </div>
            </div>
          </div>

          <div v-if="!rankedTopItems.length" class="top-empty-state">
            <i class="fa-solid fa-box-open top-empty-icon"></i>
            <p class="top-empty-title">No sales recorded for this period yet</p>
            <p class="top-empty-sub">This list isn't stuck loading — items will show up here as soon as sales come in.</p>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
import Chart from 'chart.js/auto'
import api from '@/api/axios'
import Layout from '@/components/Layout.vue'

// Maps each date-range preset to the preset that represents "the period
// right before it," so the Total Sales trend badge can do a genuine
// period-over-period comparison. Only covers the presets actually in
// the picker now — deeper history (last month, last year, etc.) lives
// on the Reports page instead.
const PREVIOUS_PERIOD_MAP = {
  today: { key: 'yesterday', label: 'yesterday' },
  this_week: { key: 'last_week', label: 'last week' },
  this_month: { key: 'last_month', label: 'last month' },
}

const RANGE_LABELS = {
  today: 'Today',
  this_week: 'This Week',
  this_month: 'This Month',
}

/* ── CROSSHAIR PLUGIN ────────────────────────────────────
   Draws a soft dashed vertical guide at the hovered index so
   it's obvious which data point a tooltip belongs to.
──────────────────────────────────────────────────────────── */
const crosshairPlugin = {
  id: 'homeDashboardCrosshair',
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

export default {
  name: 'HomeView',
  components: { Layout },

  data() {
    return {
      selectedDateRange: 'this_month',
      todaySales: 0,
      itemsSoldToday: 0,
      transactionsToday: 0,
      inventorySummary: {},
      monthlySales: [],
      dailyProfit: [],
      topSellingItems: [],
      totalProfit: 0,
      monthlyTotal: 0,
      charts: {},
      isDark: false,
      themeObserver: null,

      // Animated "counting up" copies of the headline KPI numbers.
      displayedMonthlyTotal: 0,
      displayedTotalProfit: 0,
      displayedTodaySales: 0,
      displayedItemsSoldToday: 0,

      // Period-over-period comparison for the Total Sales trend badge.
      previousPeriodTotal: null,
      comparisonLabel: '',

      // Whether bars are allowed to be at their real width yet (toggled
      // false→true to trigger the "grow in" animation).
      barsReady: false,
    }
  },

  computed: {
    rangeLabel() {
      return RANGE_LABELS[this.selectedDateRange] || 'This Period'
    },

    // Whether the currently selected date range actually has any sales
    // or profit recorded. Drives the empty-state for the trend chart
    // and sparkline so a genuinely empty period (e.g. the first day of
    // a new month) reads as "no data yet" instead of looking like a
    // chart that's stuck loading.
    hasPeriodSales() {
      const salesTotal = this.monthlySales.reduce((sum, s) => sum + this.num(s.total), 0)
      const profitTotal = this.dailyProfit.reduce((sum, p) => sum + this.num(p.profit), 0)
      return salesTotal > 0 || profitTotal > 0
    },

    // Whether today specifically has any sales yet. Drives the empty
    // state for the Today's Margin donut — without this, zero sales
    // would fall back to a misleading 50/50 split instead of a clear
    // "no sales yet" indicator.
    hasTodaySales() {
      return this.num(this.todaySales) > 0
    },

    salesTrend() {
      if (this.previousPeriodTotal === null) {
        return { direction: 'flat', percent: '0.0', available: false }
      }
      const prev = this.num(this.previousPeriodTotal)
      const current = this.num(this.monthlyTotal)
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
    },

    trendIcon() {
      if (this.salesTrend.direction === 'up') return 'fa-solid fa-arrow-trend-up'
      if (this.salesTrend.direction === 'down') return 'fa-solid fa-arrow-trend-down'
      return 'fa-solid fa-minus'
    },

    profitMarginPct() {
      const total = this.num(this.monthlyTotal)
      if (!total) return '0.0'
      return ((this.num(this.totalProfit) / total) * 100).toFixed(1)
    },

    // Looks up today's entry in the normalized dailyProfit array so the
    // margin donut shows "Today's Profit" instead of the profit for
    // the whole selected date range.
    todaysProfit() {
      const todayKey = new Date().toLocaleDateString('en-CA') // 'YYYY-MM-DD', local time
      const row = this.dailyProfit.find((p) => (p.date || '').slice(0, 10) === todayKey)
      return row ? this.num(row.profit) : 0
    },

    // The other half of today's sales — what's left once profit is
    // taken out. Together with todaysProfit this is a genuine whole,
    // which is what makes a donut the right chart for it.
    todaysCost() {
      return Math.max(this.num(this.todaySales) - this.num(this.todaysProfit), 0)
    },

    todaysMarginPct() {
      const sales = this.num(this.todaySales)
      if (!sales) return '0.0'
      return ((this.num(this.todaysProfit) / sales) * 100).toFixed(1)
    },

    // Top 5 by sales only — the sort toggle, tooltip, and pin panel
    // live on the Reports page's full leaderboard instead.
    rankedTopItems() {
      const items = this.topSellingItems
        .map((i) => ({
          ...i,
          total_sales: this.num(i.total_sales),
          total_quantity: this.num(i.total_quantity),
        }))
        .sort((a, b) => b.total_sales - a.total_sales)
        .slice(0, 5)

      const maxVal = Math.max(...items.map((i) => i.total_sales), 1)

      return items.map((i, idx) => ({
        ...i,
        rank: idx + 1,
        barPct: Math.max((i.total_sales / maxVal) * 100, 2),
      }))
    },
  },

  mounted() {
    this.isDark = document.documentElement.getAttribute('data-theme') === 'dark'
    this.themeObserver = new MutationObserver(() => {
      this.isDark = document.documentElement.getAttribute('data-theme') === 'dark'
      this.rebuildAllCharts()
    })
    this.themeObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['data-theme'],
    })
    this.loadDashboard()
    this.animateBarsIn()
  },

  beforeUnmount() {
    if (this.themeObserver) this.themeObserver.disconnect()
  },

  methods: {
    async loadDashboard() {
      try {
        const [res] = await Promise.all([
          api.get('/api/dashboard', { params: { date_range: this.selectedDateRange } }),
          this.fetchPreviousPeriodTotal(),
        ])

        const prev = {
          monthlyTotal: this.displayedMonthlyTotal,
          totalProfit: this.displayedTotalProfit,
          todaySales: this.displayedTodaySales,
          itemsSoldToday: this.displayedItemsSoldToday,
        }

        Object.assign(this, res.data)
        this.normalizeProfitData()

        this.animateValue('displayedMonthlyTotal', prev.monthlyTotal, this.monthlyTotal)
        this.animateValue('displayedTotalProfit', prev.totalProfit, this.totalProfit)
        this.animateValue('displayedTodaySales', prev.todaySales, this.todaySales)
        this.animateValue('displayedItemsSoldToday', prev.itemsSoldToday, this.itemsSoldToday)

        this.$nextTick(() => {
          this.rebuildAllCharts()
          this.animateBarsIn()
        })
      } catch (err) {
        console.error('Dashboard load error:', err)
      }
    },

    async fetchPreviousPeriodTotal() {
      const mapping = PREVIOUS_PERIOD_MAP[this.selectedDateRange]
      if (!mapping) {
        this.previousPeriodTotal = null
        this.comparisonLabel = ''
        return
      }
      try {
        const res = await api.get('/api/dashboard', { params: { date_range: mapping.key } })
        this.previousPeriodTotal = this.num(res.data?.monthlyTotal)
        this.comparisonLabel = mapping.label
      } catch (err) {
        console.error('Previous period fetch error:', err)
        this.previousPeriodTotal = null
        this.comparisonLabel = ''
      }
    },

    rebuildAllCharts() {
      // Each chart is rendered independently — if one throws (bad data,
      // a plugin edge case, etc.) the rest still draw instead of the
      // whole dashboard going blank.
      const renderers = [
        this.renderMainChart,
        this.renderSparkline,
        this.renderTodayMarginChart,
        this.renderInventoryGauges,
      ]
      renderers.forEach((renderFn) => {
        try {
          renderFn.call(this)
        } catch (err) {
          console.error(`Chart render failed (${renderFn.name}):`, err)
        }
      })
    },

    normalizeProfitData() {
      const profitMap = {}
      this.dailyProfit.forEach((p) => { profitMap[p.date] = p.profit })
      this.dailyProfit = this.monthlySales.map((s) => ({
        date: s.date,
        profit: profitMap[s.date] ?? 0,
      }))
    },

    destroyChart(id) {
      if (this.charts[id]) {
        this.charts[id].destroy()
        delete this.charts[id]
      }
      // Safety net: if a previous render threw partway through (before
      // `this.charts[id]` got assigned), Chart.js may still have an
      // instance attached to this canvas internally. Without this,
      // the next render throws "Canvas is already in use".
      const el = document.getElementById(id)
      if (el) {
        const orphan = Chart.getChart(el)
        if (orphan) orphan.destroy()
      }
    },

    // Some API responses serialize decimals as strings (e.g. "1234.50").
    // Using "+" to sum those concatenates instead of adding, so every
    // arithmetic touchpoint should coerce through this first.
    num(v) {
      const n = Number(v)
      return Number.isFinite(n) ? n : 0
    },

    formatNumber(n) {
      return Math.round(this.num(n)).toLocaleString()
    },

    formatDate(date) {
      if (!date) return ''
      return new Date(date).toLocaleDateString('en-US', { month: 'short', day: '2-digit' })
    },

    // Eases a data() property from `from` to `to` over `duration` ms.
    animateValue(key, from, to, duration = 750) {
      const start = performance.now()
      const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1)
        const eased = 1 - Math.pow(1 - progress, 3)
        this[key] = from + (to - from) * eased
        if (progress < 1) requestAnimationFrame(tick)
        else this[key] = to
      }
      requestAnimationFrame(tick)
    },

    // Drops every leaderboard bar to 0 width, then flips them to their
    // real width on the next paint so the CSS width transition actually
    // has something to animate from.
    animateBarsIn() {
      this.barsReady = false
      this.$nextTick(() => {
        requestAnimationFrame(() => {
          this.barsReady = true
        })
      })
    },

    /* ── THEME HELPERS ─────────────────────────────────────
       All chart colors resolve through these helpers so a
       single isDark flag flips every chart at once.
    ─────────────────────────────────────────────────────── */
    th() {
      const dark = this.isDark
      return {
        gridColor:    dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)',
        tickColor:    dark ? '#8B90A8'                : '#A8A29E',
        tooltipBg:    dark ? '#1E2130'                : '#1C1917',
        tooltipTitle: dark ? '#8B90A8'                : '#A8A29E',
        tooltipBody:  dark ? '#E8EAF0'                : '#F5F5F4',
        tooltipBorder:dark ? 'rgba(255,255,255,0.08)' : 'rgba(255,255,255,0.06)',
      }
    },

    tooltip() {
      const t = this.th()
      return {
        enabled: true,
        backgroundColor: t.tooltipBg,
        titleColor: t.tooltipTitle,
        titleFont: { size: 11, weight: '600', family: 'Inter' },
        bodyColor: t.tooltipBody,
        bodyFont: { size: 12.5, weight: '600', family: 'Inter' },
        padding: 12,
        cornerRadius: 10,
        caretSize: 6,
        borderColor: t.tooltipBorder,
        borderWidth: 1,
        displayColors: true,
        boxWidth: 8,
        boxHeight: 8,
        boxPadding: 4,
      }
    },

    // Simplified vs. the Reports trend chart on purpose: no legend
    // toggle, no margin footer callout — just enough to see the shape
    // of the period at a glance. Full drill-down lives on Reports.
    // Guarded by hasPeriodSales in the template (the canvas isn't
    // rendered at all when there's no data), so `el` will simply be
    // null here and the function no-ops via the early return below.
    renderMainChart() {
      const el = document.getElementById('monthlySalesChart')
      if (!el) return
      this.destroyChart('monthlySalesChart')
      const t = this.th()

      const merged = this.monthlySales.map((sale) => {
        const profitRow = this.dailyProfit.find((p) => p.date === sale.date)
        return { date: sale.date, total: this.num(sale.total), profit: this.num(profitRow?.profit) }
      }).filter((d) => d.total > 0 || d.profit > 0)

      const labels = merged.map((d) => this.formatDate(d.date))
      const ctx = el.getContext('2d')

      const orangeGrad = ctx.createLinearGradient(0, 0, 0, 200)
      orangeGrad.addColorStop(0, 'rgba(234,88,12,0.24)')
      orangeGrad.addColorStop(1, 'rgba(234,88,12,0)')

      const greenGrad = ctx.createLinearGradient(0, 0, 0, 200)
      greenGrad.addColorStop(0, 'rgba(16,185,129,0.24)')
      greenGrad.addColorStop(1, 'rgba(16,185,129,0)')

      this.charts['monthlySalesChart'] = new Chart(el, {
        type: 'line',
        data: {
          labels,
          datasets: [
            {
              label: 'Daily Sales',
              data: merged.map((d) => d.total),
              borderColor: '#EA580C',
              backgroundColor: orangeGrad,
              tension: 0.42, fill: true,
              pointRadius: 0, pointHoverRadius: 5, pointHitRadius: 14,
              pointHoverBackgroundColor: '#EA580C',
              pointHoverBorderColor: '#fff',
              pointHoverBorderWidth: 2,
              borderWidth: 2.5,
            },
            {
              label: 'Daily Profit',
              data: merged.map((d) => d.profit),
              borderColor: '#10b981',
              backgroundColor: greenGrad,
              tension: 0.42, fill: true,
              pointRadius: 0, pointHoverRadius: 5, pointHitRadius: 14,
              pointHoverBackgroundColor: '#10b981',
              pointHoverBorderColor: '#fff',
              pointHoverBorderWidth: 2,
              borderWidth: 2.5,
            },
          ],
        },
        plugins: [crosshairPlugin],
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 800, easing: 'easeOutQuart' },
          interaction: { mode: 'index', intersect: false },
          onHover: this.hoverCursor,
          plugins: {
            legend: { display: false },
            tooltip: {
              ...this.tooltip(),
              callbacks: {
                title: (items) => {
                  const row = merged[items[0]?.dataIndex]
                  return row ? this.formatDate(row.date) : ''
                },
                label: (ctx) => ` ${ctx.dataset.label}: ₱${Number(ctx.raw).toLocaleString()}`,
              },
            },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: t.tickColor, font: { size: 11, family: 'Inter' }, maxRotation: 0 },
              border: { display: false },
            },
            y: {
              grid: { color: t.gridColor, drawBorder: false },
              ticks: {
                color: t.tickColor,
                font: { size: 11, family: 'Inter' },
                callback: (v) => '₱' + Number(v).toLocaleString(),
              },
              border: { display: false },
            },
          },
        },
      })
    },

    // Guarded by hasPeriodSales in the template — same reasoning as
    // renderMainChart above.
    renderSparkline() {
      const el = document.getElementById('sparklineChart')
      if (!el) return
      this.destroyChart('sparklineChart')

      const data = this.monthlySales.map((d) => this.num(d.total))
      const ctx = el.getContext('2d')

      const grad = ctx.createLinearGradient(0, 0, 0, 56)
      grad.addColorStop(0, 'rgba(234,88,12,0.32)')
      grad.addColorStop(1, 'rgba(234,88,12,0)')

      this.charts['sparklineChart'] = new Chart(el, {
        type: 'line',
        data: {
          labels: data.map((_, i) => i),
          datasets: [{
            data,
            borderColor: '#EA580C',
            backgroundColor: grad,
            tension: 0.4, fill: true,
            pointRadius: 0, pointHoverRadius: 4, pointHitRadius: 10,
            pointHoverBackgroundColor: '#EA580C',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 2,
            borderWidth: 2,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 700, easing: 'easeOutQuart' },
          plugins: {
            legend: { display: false },
            tooltip: {
              ...this.tooltip(),
              displayColors: false,
              callbacks: {
                title: (items) => this.formatDate(this.monthlySales[items[0]?.dataIndex]?.date),
                label: (ctx) => ` ₱${Number(ctx.raw).toLocaleString()}`,
              },
            },
          },
          scales: { x: { display: false }, y: { display: false } },
        },
      })
    },

    // Today's Sales split into two real parts — Profit and Cost of
    // Goods. Unlike the old "Today's Sales / Today's Profit / Items
    // Sold" trio, these two numbers genuinely sum to a whole, which is
    // what makes a donut the right chart for it (rather than the
    // misleading whole-implying slices the old design would've needed).
    // Guarded by hasTodaySales in the template: when today has no
    // sales yet, the canvas isn't rendered at all (avoiding the old
    // 0.01/0.01 fallback, which drew a misleading 50/50 split), and an
    // explicit "No sales yet" empty state is shown instead.
    renderTodayMarginChart() {
      const el = document.getElementById('todayMarginChart')
      if (!el) return
      this.destroyChart('todayMarginChart')

      const profitVal = this.num(this.todaysProfit) || 0.01
      const costVal = this.num(this.todaysCost) || 0.01

      this.charts['todayMarginChart'] = new Chart(el, {
        type: 'doughnut',
        data: {
          labels: ['Profit', 'Cost of Goods'],
          datasets: [{
            data: [profitVal, costVal],
            backgroundColor: ['#10b981', '#EA580C'],
            borderWidth: 0,
            hoverOffset: 8,
            hoverBorderWidth: 2,
            hoverBorderColor: this.isDark ? '#1E2130' : '#ffffff',
            spacing: 2,
            borderRadius: 6,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '78%',
          animation: { duration: 700, easing: 'easeOutQuart' },
          onHover: this.hoverCursor,
          plugins: {
            legend: { display: false },
            tooltip: {
              ...this.tooltip(),
              callbacks: {
                label: (ctx) => {
                  const val = this.num(ctx.raw)
                  const total = ctx.dataset.data.reduce((a, b) => this.num(a) + this.num(b), 0)
                  const pct = total ? ((val / total) * 100).toFixed(1) : '0.0'
                  return ` ${ctx.label}: ₱${val.toLocaleString()} (${pct}%)`
                },
              },
            },
          },
        },
      })
    },

    renderInventoryGauges() {
      const inv = this.inventorySummary
      const dark = this.isDark

      const makeGauge = (id, value, max, color, trackColor, extraLabel) => {
        const el = document.getElementById(id)
        if (!el) return
        this.destroyChart(id)
        const pct = max > 0 ? Math.min(value / max, 1) : 0
        this.charts[id] = new Chart(el, {
          type: 'doughnut',
          data: {
            labels: [extraLabel, 'Remaining'],
            datasets: [{
              data: [pct, 1 - pct],
              backgroundColor: [color, trackColor],
              borderWidth: 0,
              borderRadius: 8,
              hoverOffset: 4,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '74%',
            rotation: -90,
            circumference: 360,
            animation: { duration: 700, easing: 'easeOutQuart' },
            onHover: this.hoverCursor,
            plugins: {
              legend: { display: false },
              tooltip: {
                ...this.tooltip(),
                displayColors: false,
                filter: (item) => item.dataIndex === 0,
                callbacks: {
                  title: () => extraLabel,
                  label: () => ` ${this.formatNumber(value)} of ${this.formatNumber(max)} (${Math.round(pct * 100)}%)`,
                },
              },
            },
          },
        })
      }

      const totalItems = inv.total_items || 1
      const stockTotal = (inv.quantity_in_hand ?? 0) + (inv.quantity_to_receive ?? 0) || 1

      makeGauge('gaugeActive', inv.active_items ?? 0, totalItems,
        '#10b981', dark ? 'rgba(16,185,129,0.15)' : 'rgba(16,185,129,0.12)', 'Active Items')
      makeGauge('gaugeStock', inv.quantity_in_hand ?? 0, stockTotal,
        '#6366f1', dark ? 'rgba(99,102,241,0.15)' : 'rgba(99,102,241,0.12)', 'In Hand')
      makeGauge('gaugeLow', inv.low_stock_items ?? 0, totalItems,
        '#f43f5e', dark ? 'rgba(244,63,94,0.15)' : 'rgba(244,63,94,0.12)', 'Low Stock')
    },

    // Shared onHover so every interactive chart shows a pointer cursor
    // over hoverable elements — a small cue that data is inspectable.
    hoverCursor(evt, elements) {
      try {
        if (evt?.native?.target) {
          evt.native.target.style.cursor = elements.length ? 'pointer' : 'default'
        }
      } catch (e) {
        /* no-op */
      }
    },
  },
}
</script>

<style scoped>
/* ─── ROOT ─────────────────────────────────────────────── */
.dash {
  /* Semantic color tokens — resolve from global theme.css */
  --d-bg:             var(--c-bg);
  --d-surface:        var(--c-surface);
  --d-surface-raised: var(--c-surface-raised);
  --d-surface-sunken: var(--c-surface-sunken);
  --d-border:         var(--c-border);
  --d-border-strong:  var(--c-border-strong);
  --d-text-primary:   var(--c-text-primary);
  --d-text-secondary: var(--c-text-secondary);
  --d-text-muted:     var(--c-text-muted);
  --d-text-faint:     var(--c-text-faint);
  --d-accent:         var(--c-accent);
  --d-accent-soft:    var(--c-accent-soft);
  --d-accent-border:  var(--c-accent-border);
  --d-shadow-sm:      var(--c-shadow-sm);
  --d-shadow-md:      var(--c-shadow-md);

  /* Semantic chart palette — consistent in both modes */
  --d-green:          #10b981;
  --d-green-soft:     rgba(16,185,129,0.12);
  --d-blue:           #3b82f6;
  --d-blue-soft:      rgba(59,130,246,0.10);
  --d-violet:         #6366f1;
  --d-violet-soft:    rgba(99,102,241,0.12);
  --d-red:            #f43f5e;
  --d-red-soft:       rgba(244,63,94,0.12);
  --d-amber:          #f59e0b;

  /* Dark-mode softened icon backgrounds */
  --d-green-icon:     #D1FAE5;
  --d-blue-icon:      #DBEAFE;
  --d-violet-icon:    #E0E7FF;
  --d-red-icon:       #FFE4E6;

  --radius: 16px;

  min-height: 100%;
  background: var(--d-bg);
  padding: 28px 28px 56px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex;
  flex-direction: column;
  gap: 20px;
  transition: background-color 0.22s ease;
}

/* Dark mode overrides for icon bg — less saturated on dark */
html[data-theme="dark"] .dash {
  --d-green-icon:   rgba(16,185,129,0.18);
  --d-blue-icon:    rgba(59,130,246,0.18);
  --d-violet-icon:  rgba(99,102,241,0.18);
  --d-red-icon:     rgba(244,63,94,0.18);
  --d-green-soft:   rgba(16,185,129,0.16);
  --d-blue-soft:    rgba(59,130,246,0.14);
  --d-violet-soft:  rgba(99,102,241,0.16);
  --d-red-soft:     rgba(244,63,94,0.16);
}

/* ─── PAGE HEADER ──────────────────────────────────────── */
.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}
.page-eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .12em;
  color: var(--d-accent);
  text-transform: uppercase;
  margin: 0 0 5px;
}
.page-title {
  font-size: 26px;
  font-weight: 800;
  color: var(--d-text-primary);
  letter-spacing: -.03em;
  margin: 0;
}
.page-header-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Link out to the full report — the "escape hatch" from the glance
   view into the detailed one */
.full-report-link {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 12.5px;
  font-weight: 700;
  color: var(--d-accent);
  background: var(--d-accent-soft);
  border: 1px solid var(--d-accent-border);
  border-radius: 10px;
  padding: 8px 14px;
  text-decoration: none;
  transition: background-color .16s ease, transform .12s ease;
}
.full-report-link i { font-size: 10px; transition: transform .16s ease; }
.full-report-link:hover { transform: translateY(-1px); }
.full-report-link:hover i { transform: translateX(2px); }

.card-header-link {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 700;
  color: var(--d-accent);
  text-decoration: none;
  white-space: nowrap;
  transition: opacity .16s ease;
}
.card-header-link i { font-size: 9px; transition: transform .16s ease; }
.card-header-link:hover { opacity: .75; }
.card-header-link:hover i { transform: translateX(2px); }

/* ─── DATE SELECT ──────────────────────────────────────── */
.range-select-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.range-icon {
  position: absolute;
  left: 11px;
  color: var(--d-text-muted);
  font-size: 12px;
  pointer-events: none;
}
.range-caret {
  position: absolute;
  right: 10px;
  color: var(--d-text-muted);
  font-size: 9px;
  pointer-events: none;
}
.range-select {
  appearance: none;
  -webkit-appearance: none;
  background: var(--d-surface);
  border: 1.5px solid var(--d-border-strong);
  border-radius: 10px;
  padding: 8px 32px 8px 30px;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--d-text-secondary);
  cursor: pointer;
  transition: border-color .18s ease, box-shadow .18s ease, background-color 0.22s ease, color 0.22s ease;
  font-family: inherit;
  letter-spacing: .01em;
}
.range-select:hover { border-color: var(--d-accent-border); }
.range-select:focus { outline: none; border-color: var(--d-accent); box-shadow: 0 0 0 3px var(--c-accent-ring); }

/* ─── KPI STRIP ────────────────────────────────────────── */
.kpi-strip {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

.kpi-card {
  background: var(--d-surface);
  border-radius: var(--radius);
  border: 1px solid var(--d-border);
  box-shadow: var(--d-shadow-sm);
  padding: 20px 20px 16px;
  position: relative;
  overflow: hidden;
  transition: transform .2s ease, box-shadow .2s ease, background-color 0.22s ease, border-color 0.22s ease;
  display: flex;
  flex-direction: column;
}
.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--d-shadow-md);
}

.kpi-card--featured {
  background: var(--d-accent-soft);
  border-color: var(--d-accent-border);
}

.kpi-card-inner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.kpi-icon-wrap {
  width: 38px; height: 38px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
  flex-shrink: 0;
  transition: background-color 0.22s ease;
}
.kpi-icon-wrap--orange { background: var(--d-accent-soft);   color: var(--d-accent); }
.kpi-icon-wrap--green  { background: var(--d-green-icon);    color: var(--d-green); }
.kpi-icon-wrap--blue   { background: var(--d-blue-icon);     color: var(--d-blue); }
.kpi-icon-wrap--violet { background: var(--d-violet-icon);   color: var(--d-violet); }
.kpi-icon-wrap--red    { background: var(--d-red-icon);      color: var(--d-red); }

.kpi-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.kpi-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--d-text-muted);
  text-transform: uppercase;
  letter-spacing: .07em;
  white-space: nowrap;
}
.kpi-value {
  font-size: 22px;
  font-weight: 800;
  color: var(--d-text-primary);
  letter-spacing: -.04em;
  line-height: 1.1;
  margin-top: 4px;
  transition: color 0.22s ease;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}
.kpi-value--green  { color: var(--d-green); }
.kpi-value--blue   { color: var(--d-blue); }
.kpi-value--violet { color: var(--d-violet); }
.kpi-value--red    { color: var(--d-red); }

.kpi-sub {
  font-size: 11px;
  color: var(--d-text-faint);
  margin-top: 3px;
}

/* Momentum badge next to the Total Sales figure */
.kpi-trend {
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
.kpi-trend i { font-size: 9.5px; }
.kpi-trend.up   { color: var(--d-green); background: var(--d-green-soft); }
.kpi-trend.down { color: var(--d-red);   background: var(--d-red-soft); }
.kpi-trend.flat { color: var(--d-text-muted); background: var(--d-surface-raised); }

.kpi-sparkline { position: relative; height: 40px; margin-top: 14px; }

/* Empty state for the sparkline — a faint dashed line with a small
   pill label, so a period with no sales yet reads as "no data" at a
   glance instead of an unexplained blank strip. */
.sparkline-empty {
  position: relative;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.sparkline-empty-line {
  position: absolute;
  left: 0;
  right: 0;
  top: 50%;
  border-top: 1.5px dashed var(--d-border-strong);
}
.sparkline-empty-text {
  position: relative;
  background: var(--d-surface);
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 9px;
  font-weight: 700;
  color: var(--d-text-faint);
  letter-spacing: .02em;
  z-index: 1;
}

.kpi-accent-bar {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 3px;
  border-radius: 0 0 var(--radius) var(--radius);
}
.kpi-accent-bar--orange { background: linear-gradient(90deg, var(--d-accent), #FB923C); }
.kpi-accent-bar--green  { background: var(--d-green); }
.kpi-accent-bar--blue   { background: var(--d-blue); }
.kpi-accent-bar--violet { background: var(--d-violet); }
.kpi-accent-bar--red    { background: var(--d-red); }

/* ─── CARDS ────────────────────────────────────────────── */
.card {
  background: var(--d-surface);
  border-radius: var(--radius);
  border: 1px solid var(--d-border);
  box-shadow: var(--d-shadow-sm);
  padding: 22px 24px 20px;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.card--wide { min-width: 0; }

/* ─── CARD HEADER ──────────────────────────────────────── */
.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 12px;
  gap: 12px;
  flex-wrap: wrap;
}
.card-eyebrow {
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: .12em;
  color: var(--d-accent);
  text-transform: uppercase;
  margin: 0 0 4px;
}
.card-title {
  font-size: 15.5px;
  font-weight: 700;
  color: var(--d-text-primary);
  margin: 0;
  letter-spacing: -.01em;
  transition: color 0.22s ease;
}

/* ─── CHART AREAS ──────────────────────────────────────── */
.chart-area { position: relative; width: 100%; max-width: 100%; }
.chart-area canvas { max-width: 100%; }
.chart-area--medium { height: 190px; }
.chart-area--donut {
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.donut-center {
  position: absolute;
  text-align: center;
  pointer-events: none;
}
.donut-center-value {
  font-size: 18px;
  font-weight: 800;
  color: var(--d-text-primary);
  letter-spacing: -.03em;
  margin: 0;
  transition: color 0.22s ease;
}
.donut-center-label {
  font-size: 10.5px;
  font-weight: 600;
  color: var(--d-text-muted);
  text-transform: uppercase;
  letter-spacing: .08em;
  margin: 2px 0 0;
}

/* Empty state for the main trend chart — a clear "nothing recorded
   yet" message rather than a blank canvas, which otherwise looks
   indistinguishable from data still loading. */
.chart-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
  gap: 5px;
  padding: 0 24px;
}
.chart-empty-icon {
  font-size: 22px;
  color: var(--d-text-muted);
  opacity: .55;
  margin-bottom: 3px;
}
.chart-empty-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--d-text-secondary);
  margin: 0;
}
.chart-empty-sub {
  font-size: 11.5px;
  color: var(--d-text-faint);
  margin: 0;
  max-width: 260px;
  line-height: 1.5;
}

/* Empty state for the Today's Margin donut — a dashed ring in place
   of the chart, so zero sales never gets rendered as a misleading
   50/50 split. */
.donut-empty-state {
  position: relative;
  width: 150px;
  height: 150px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.donut-empty-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 14px dashed var(--d-border);
}
.donut-empty-center { text-align: center; }
.donut-empty-icon {
  font-size: 15px;
  color: var(--d-text-faint);
  margin-bottom: 4px;
  display: block;
}
.donut-empty-title {
  font-size: 12.5px;
  font-weight: 700;
  color: var(--d-text-secondary);
  margin: 0;
}
.donut-empty-sub {
  font-size: 10.5px;
  color: var(--d-text-faint);
  margin: 2px 0 0;
}

/* ─── SECOND ROW ───────────────────────────────────────── */
.row-2 {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 20px;
}

/* ─── DONUT LEGEND ─────────────────────────────────────── */
.donut-legend {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  border-top: 1px solid var(--d-border);
  padding-top: 14px;
  transition: border-color 0.22s ease;
}
.donut-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12.5px;
  color: var(--d-text-muted);
}
.donut-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.donut-dot--green  { background: var(--d-green); }
.donut-dot--orange { background: var(--d-accent); }

.donut-row-label { flex: 1; }
.donut-row strong {
  font-weight: 700;
  color: var(--d-text-primary);
  font-size: 12.5px;
  transition: color 0.22s ease;
}

/* ─── INVENTORY GAUGES ─────────────────────────────────── */
.inv-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
.inv-gauge-card {
  background: var(--d-surface-raised);
  border: 1px solid var(--d-border);
  border-radius: 14px;
  padding: 16px 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  transition: background-color 0.22s ease, border-color 0.22s ease, transform .18s ease;
}
.inv-gauge-card:hover { transform: translateY(-2px); }
.inv-gauge-card--alert {
  background: var(--d-red-soft);
  border-color: var(--d-red);
  border-color: rgba(244,63,94,0.28);
}
.inv-gauge { position: relative; width: 76px; height: 76px; }
.inv-gauge-center {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
.inv-gauge-num {
  font-size: 18px;
  font-weight: 800;
  color: var(--d-text-primary);
  letter-spacing: -.03em;
  transition: color 0.22s ease;
}
.inv-gauge-num--red { color: var(--d-red); }
.inv-gauge-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--d-text-secondary);
  margin-top: 4px;
  transition: color 0.22s ease;
}
.inv-gauge-sub {
  font-size: 10.5px;
  color: var(--d-text-faint);
  transition: color 0.22s ease;
}
.inv-gauge-sub--red { color: var(--d-red); font-weight: 600; }

/* ─── TOP SELLING — COMPACT LIST (no hover tooltip / pin panel; just
   the ranked bars for a fast read) ─────────────────────────────── */
.top-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.top-row {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  background: var(--d-surface-raised);
  border: 1px solid var(--d-border);
  border-radius: 12px;
  transition: background-color .18s ease, border-color .18s ease;
}
.top-row--static { cursor: default; }
/* Subtle tint per rank tier, kept faint so the row list still reads as
   one cohesive set rather than three different card styles */
.top-row--rank1 { background: linear-gradient(90deg, rgba(245,158,11,0.08), var(--d-surface-raised) 55%); }
.top-row--rank2 { background: linear-gradient(90deg, rgba(148,163,184,0.10), var(--d-surface-raised) 55%); }
.top-row--rank3 { background: linear-gradient(90deg, rgba(194,112,61,0.10), var(--d-surface-raised) 55%); }

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
  background: var(--d-surface-sunken);
  color: var(--d-text-secondary);
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
  color: var(--d-text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.top-row-value {
  font-size: 12.5px;
  font-weight: 700;
  color: var(--d-text-secondary);
  flex-shrink: 0;
  white-space: nowrap;
}

.top-row-bar-track {
  position: relative;
  height: 7px;
  border-radius: 6px;
  background: var(--d-surface-sunken);
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

/* Empty state for the Top Selling list — explains why the list is
   blank rather than leaving the user guessing whether it's loading. */
.top-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 5px;
  padding: 32px 20px;
}
.top-empty-icon {
  font-size: 20px;
  color: var(--d-text-muted);
  opacity: .55;
  margin-bottom: 3px;
}
.top-empty-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--d-text-secondary);
  margin: 0;
}
.top-empty-sub {
  font-size: 11.5px;
  color: var(--d-text-faint);
  margin: 0;
  max-width: 300px;
  line-height: 1.5;
}

/* ─── RESPONSIVE ───────────────────────────────────────── */
@media (max-width: 1200px) { .kpi-strip { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 900px) {
  .row-2     { grid-template-columns: 1fr; }
  .kpi-strip { grid-template-columns: repeat(2, 1fr); }
  .inv-grid  { grid-template-columns: repeat(3, 1fr); }
  .dash      { padding: 16px 16px 40px; }
}
@media (max-width: 500px) {
  .kpi-strip { grid-template-columns: 1fr; }
  .kpi-value { font-size: 20px; }
  .inv-grid  { grid-template-columns: 1fr; }
  .top-row-head { flex-direction: column; align-items: flex-start; gap: 2px; }
  .page-header-right { width: 100%; justify-content: space-between; }
}
</style>