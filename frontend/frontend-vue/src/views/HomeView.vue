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
              <option value="yesterday">Yesterday</option>
              <option value="this_week">This Week</option>
              <option value="last_week">Last Week</option>
              <option value="this_month">This Month</option>
              <option value="last_month">Last Month</option>
              <option value="this_year">This Year</option>
              <option value="last_year">Last Year</option>
            </select>
            <i class="fa-solid fa-chevron-down range-caret"></i>
          </div>
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
              <p class="kpi-value">₱{{ formatNumber(monthlyTotal) }}</p>
              <p class="kpi-sub">Monthly Revenue</p>
            </div>
          </div>
          <div class="kpi-sparkline">
            <canvas id="sparklineChart"></canvas>
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
              <p class="kpi-value kpi-value--green">₱{{ formatNumber(totalProfit) }}</p>
              <p class="kpi-sub">Net Earnings</p>
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
              <p class="kpi-value kpi-value--blue">₱{{ formatNumber(todaySales) }}</p>
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
              <p class="kpi-value kpi-value--violet">{{ itemsSoldToday }}</p>
              <p class="kpi-sub">Products moved</p>
            </div>
          </div>
          <div class="kpi-accent-bar kpi-accent-bar--violet"></div>
        </div>

        <div class="kpi-card">
          <div class="kpi-card-inner">
            <div class="kpi-icon-wrap kpi-icon-wrap--red">
              <i class="fa-solid fa-ban"></i>
            </div>
            <div class="kpi-body">
              <p class="kpi-label">Void Transactions</p>
              <p class="kpi-value kpi-value--red">{{ voidTransactionsToday }}</p>
              <p class="kpi-sub">Cancelled orders</p>
            </div>
          </div>
          <div class="kpi-accent-bar kpi-accent-bar--red"></div>
        </div>

      </div>

      <!-- ── MAIN CHART ── -->
      <div class="card card--main">
        <div class="card-header">
          <div>
            <p class="card-eyebrow">Trend Analysis</p>
            <h2 class="card-title">Sales &amp; Profit Overview</h2>
          </div>
          <div class="legend-row">
            <span class="legend-chip legend-chip--blue">
              <span class="legend-dot"></span>Daily Sales
            </span>
            <span class="legend-chip legend-chip--green">
              <span class="legend-dot"></span>Daily Profit
            </span>
          </div>
        </div>
        <div class="chart-area chart-area--tall">
          <canvas id="monthlySalesChart"></canvas>
        </div>
      </div>

      <!-- ── SECOND ROW ── -->
      <div class="row-2">

        <!-- Donut -->
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Breakdown</p>
              <h2 class="card-title">Sales Activity</h2>
            </div>
          </div>
          <div class="chart-area chart-area--donut">
            <canvas id="salesActivityChart"></canvas>
            <div class="donut-center">
              <p class="donut-center-value">₱{{ formatNumber(todaySales) }}</p>
              <p class="donut-center-label">Today</p>
            </div>
          </div>
          <div class="donut-legend">
            <div class="donut-row">
              <span class="donut-dot donut-dot--blue"></span>
              <span class="donut-row-label">Today Sales</span>
              <strong>₱{{ formatNumber(todaySales) }}</strong>
            </div>
            <div class="donut-row">
              <span class="donut-dot donut-dot--green"></span>
              <span class="donut-row-label">Total Profit</span>
              <strong>₱{{ formatNumber(totalProfit) }}</strong>
            </div>
            <div class="donut-row">
              <span class="donut-dot donut-dot--red"></span>
              <span class="donut-row-label">Void Orders</span>
              <strong>{{ voidTransactionsToday }}</strong>
            </div>
          </div>
        </div>

        <!-- Inventory Radial Gauges -->
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

          <div class="inv-bar-wrap">
            <canvas id="inventoryChart"></canvas>
          </div>
        </div>

      </div>

      <!-- ── TOP SELLING ── -->
      <div class="card">
        <div class="card-header">
          <div>
            <p class="card-eyebrow">Performance</p>
            <h2 class="card-title">Top Selling Items</h2>
          </div>
          <div class="range-select-wrap">
            <i class="fa-regular fa-calendar range-icon"></i>
            <select v-model="selectedDateRange" @change="loadDashboard" class="range-select">
              <option value="today">Today</option>
              <option value="this_month">This Month</option>
              <option value="this_year">This Year</option>
            </select>
            <i class="fa-solid fa-chevron-down range-caret"></i>
          </div>
        </div>
        <div class="legend-row legend-row--inline">
          <span class="legend-chip legend-chip--indigo">
            <span class="legend-dot"></span>Total Sales (₱)
          </span>
          <span class="legend-chip legend-chip--amber">
            <span class="legend-dot"></span>Qty Sold
          </span>
        </div>
        <div class="chart-area chart-area--horizontal">
          <canvas id="topSellingChart"></canvas>
        </div>
      </div>

      <!-- ── FLOATING "GO TO POS" BUTTON ──
           Fixed to the bottom-left of the viewport, stays visible while
           scrolling. Quick shortcut to the POS page in addition to the
           sidebar link — handy when the owner/cashier is deep in the
           dashboard and wants to jump straight into a sale.
      -->
      <router-link to="/pos" class="pos-fab" aria-label="Go to POS">
        <span class="pos-fab-icon"><i class="fa-solid fa-cash-register"></i></span>
        <span class="pos-fab-label">Go to POS</span>
      </router-link>

    </div>
  </Layout>
</template>

<script>
import Chart from 'chart.js/auto'
import api from '@/api/axios'
import Layout from '@/components/Layout.vue'

export default {
  name: 'HomeView',
  components: { Layout },

  data() {
    return {
      selectedDateRange: 'this_month',
      todaySales: 0,
      itemsSoldToday: 0,
      transactionsToday: 0,
      voidTransactionsToday: 0,
      inventorySummary: {},
      monthlySales: [],
      dailyProfit: [],
      topSellingItems: [],
      totalProfit: 0,
      monthlyTotal: 0,
      charts: {},
      isDark: false,
      themeObserver: null,
    }
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
  },

  beforeUnmount() {
    if (this.themeObserver) this.themeObserver.disconnect()
  },

  methods: {
    async loadDashboard() {
      try {
        const res = await api.get('/api/dashboard', {
          params: { date_range: this.selectedDateRange },
        })
        Object.assign(this, res.data)
        this.normalizeProfitData()
        this.$nextTick(() => this.rebuildAllCharts())
      } catch (err) {
        console.error('Dashboard load error:', err)
      }
    },

    rebuildAllCharts() {
      this.renderMainChart()
      this.renderSparkline()
      this.renderSalesActivityChart()
      this.renderInventoryGauges()
      this.renderInventoryChart()
      this.renderTopSellingChart()
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
    },

    formatNumber(n) {
      return Number(n || 0).toLocaleString()
    },

    formatDate(date) {
      if (!date) return ''
      return new Date(date).toLocaleDateString('en-US', { month: 'short', day: '2-digit' })
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
        labelColor:   dark ? '#8B90A8'                : '#78716C',
        tooltipBg:    dark ? '#1E2130'                : '#1C1917',
        tooltipTitle: dark ? '#8B90A8'                : '#A8A29E',
        tooltipBody:  dark ? '#E8EAF0'                : '#F5F5F4',
        tooltipBorder:dark ? 'rgba(255,255,255,0.08)' : 'rgba(255,255,255,0.06)',
      }
    },

    tooltip() {
      const t = this.th()
      return {
        backgroundColor: t.tooltipBg,
        titleColor:      t.tooltipTitle,
        bodyColor:       t.tooltipBody,
        padding:         12,
        cornerRadius:    10,
        borderColor:     t.tooltipBorder,
        borderWidth:     1,
        displayColors:   true,
        boxWidth:        8,
        boxHeight:       8,
        boxPadding:      4,
      }
    },

    renderMainChart() {
      const el = document.getElementById('monthlySalesChart')
      if (!el) return
      this.destroyChart('monthlySalesChart')
      const t = this.th()

      const merged = this.monthlySales.map((sale) => {
        const profitRow = this.dailyProfit.find((p) => p.date === sale.date)
        return { date: sale.date, total: sale.total || 0, profit: profitRow?.profit || 0 }
      }).filter((d) => d.total > 0 || d.profit > 0)

      const labels = merged.map((d) => this.formatDate(d.date))
      const ctx = el.getContext('2d')

      const blueGrad = ctx.createLinearGradient(0, 0, 0, 280)
      blueGrad.addColorStop(0, 'rgba(59,130,246,0.22)')
      blueGrad.addColorStop(1, 'rgba(59,130,246,0)')

      const greenGrad = ctx.createLinearGradient(0, 0, 0, 280)
      greenGrad.addColorStop(0, 'rgba(16,185,129,0.22)')
      greenGrad.addColorStop(1, 'rgba(16,185,129,0)')

      this.charts['monthlySalesChart'] = new Chart(el, {
        type: 'line',
        data: {
          labels,
          datasets: [
            {
              label: 'Daily Sales',
              data: merged.map((d) => d.total),
              borderColor: '#3b82f6',
              backgroundColor: blueGrad,
              tension: 0.42, fill: true,
              pointRadius: 0, pointHoverRadius: 6,
              pointHoverBackgroundColor: '#3b82f6',
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
              pointRadius: 0, pointHoverRadius: 6,
              pointHoverBackgroundColor: '#10b981',
              pointHoverBorderColor: '#fff',
              pointHoverBorderWidth: 2,
              borderWidth: 2.5,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { display: false },
            tooltip: {
              ...this.tooltip(),
              callbacks: { label: (ctx) => ` ₱${Number(ctx.raw).toLocaleString()}` },
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

    renderSparkline() {
      const el = document.getElementById('sparklineChart')
      if (!el) return
      this.destroyChart('sparklineChart')

      const data = this.monthlySales.map((d) => d.total || 0)
      const ctx = el.getContext('2d')

      const grad = ctx.createLinearGradient(0, 0, 0, 56)
      grad.addColorStop(0, 'rgba(234,88,12,0.30)')
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
            pointRadius: 0,
            borderWidth: 2,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { enabled: false } },
          scales: { x: { display: false }, y: { display: false } },
        },
      })
    },

    renderSalesActivityChart() {
      const el = document.getElementById('salesActivityChart')
      if (!el) return
      this.destroyChart('salesActivityChart')

      this.charts['salesActivityChart'] = new Chart(el, {
        type: 'doughnut',
        data: {
          labels: ['Today Sales', 'Total Profit', 'Void Orders'],
          datasets: [{
            data: [
              this.todaySales || 0.01,
              this.totalProfit || 0.01,
              this.voidTransactionsToday || 0.01,
            ],
            backgroundColor: ['#3b82f6', '#10b981', '#f43f5e'],
            borderWidth: 0,
            hoverOffset: 6,
            spacing: 2,
            borderRadius: 6,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '78%',
          plugins: {
            legend: { display: false },
            tooltip: { ...this.tooltip() },
          },
        },
      })
    },

    renderInventoryGauges() {
      const inv = this.inventorySummary

      const makeGauge = (id, value, max, color, trackColor) => {
        const el = document.getElementById(id)
        if (!el) return
        this.destroyChart(id)
        const pct = max > 0 ? Math.min(value / max, 1) : 0
        this.charts[id] = new Chart(el, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: [pct, 1 - pct],
              backgroundColor: [color, trackColor],
              borderWidth: 0,
              borderRadius: 8,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '74%',
            rotation: -90,
            circumference: 360,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
          },
        })
      }

      const totalItems = inv.total_items || 1
      const dark = this.isDark

      makeGauge('gaugeActive', inv.active_items ?? 0, totalItems,
        '#10b981', dark ? 'rgba(16,185,129,0.15)' : 'rgba(16,185,129,0.12)')
      makeGauge('gaugeStock',  inv.quantity_in_hand ?? 0, (inv.quantity_in_hand ?? 0) + (inv.quantity_to_receive ?? 0) || 1,
        '#6366f1', dark ? 'rgba(99,102,241,0.15)' : 'rgba(99,102,241,0.12)')
      makeGauge('gaugeLow',    inv.low_stock_items ?? 0, totalItems || 1,
        '#f43f5e', dark ? 'rgba(244,63,94,0.15)' : 'rgba(244,63,94,0.12)')
    },

    renderInventoryChart() {
      const el = document.getElementById('inventoryChart')
      if (!el) return
      this.destroyChart('inventoryChart')
      const t = this.th()
      const inv = this.inventorySummary
      const ctx = el.getContext('2d')

      const makeGrad = (c1, c2) => {
        const g = ctx.createLinearGradient(0, 0, 220, 0)
        g.addColorStop(0, c1); g.addColorStop(1, c2)
        return g
      }

      this.charts['inventoryChart'] = new Chart(el, {
        type: 'bar',
        data: {
          labels: ['In Hand', 'To Receive', 'Active', 'Low Stock'],
          datasets: [{
            data: [
              inv.quantity_in_hand ?? 0,
              inv.quantity_to_receive ?? 0,
              inv.active_items ?? 0,
              inv.low_stock_items ?? 0,
            ],
            backgroundColor: [
              makeGrad('rgba(99,102,241,0.55)',  'rgba(99,102,241,0.90)'),
              makeGrad('rgba(245,158,11,0.55)',  'rgba(245,158,11,0.90)'),
              makeGrad('rgba(16,185,129,0.55)',  'rgba(16,185,129,0.90)'),
              makeGrad('rgba(244,63,94,0.55)',   'rgba(244,63,94,0.90)'),
            ],
            borderRadius: 8,
            borderSkipped: false,
            barThickness: 22,
          }],
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { ...this.tooltip() } },
          scales: {
            x: {
              grid: { color: t.gridColor },
              ticks: { color: t.tickColor, font: { size: 11, family: 'Inter' } },
              border: { display: false },
            },
            y: {
              grid: { display: false },
              ticks: { color: t.labelColor, font: { size: 12, family: 'Inter', weight: '600' } },
              border: { display: false },
            },
          },
        },
      })
    },

    renderTopSellingChart() {
      const el = document.getElementById('topSellingChart')
      if (!el) return
      this.destroyChart('topSellingChart')
      const t = this.th()

      const items = this.topSellingItems.slice(0, 8).reverse()
      const ctx = el.getContext('2d')

      const indigoGrad = ctx.createLinearGradient(0, 0, 400, 0)
      indigoGrad.addColorStop(0, 'rgba(99,102,241,0.55)')
      indigoGrad.addColorStop(1, 'rgba(99,102,241,0.90)')

      const amberGrad = ctx.createLinearGradient(0, 0, 400, 0)
      amberGrad.addColorStop(0, 'rgba(245,158,11,0.55)')
      amberGrad.addColorStop(1, 'rgba(245,158,11,0.90)')

      this.charts['topSellingChart'] = new Chart(el, {
        type: 'bar',
        data: {
          labels: items.map((i) => i.ProductName),
          datasets: [
            {
              label: 'Total Sales (₱)',
              data: items.map((i) => i.total_sales),
              backgroundColor: indigoGrad,
              borderRadius: 8, borderSkipped: false,
              xAxisID: 'xSales',
              barPercentage: 0.6, categoryPercentage: 0.75,
            },
            {
              label: 'Qty Sold',
              data: items.map((i) => i.total_quantity),
              backgroundColor: amberGrad,
              borderRadius: 8, borderSkipped: false,
              xAxisID: 'xQty',
              barPercentage: 0.6, categoryPercentage: 0.75,
            },
          ],
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { display: false },
            tooltip: {
              ...this.tooltip(),
              callbacks: {
                label: (ctx) => {
                  if (ctx.datasetIndex === 0) return ` ₱${Number(ctx.raw).toLocaleString()}`
                  return ` ${Number(ctx.raw).toLocaleString()} units`
                },
              },
            },
          },
          scales: {
            y: {
              grid: { display: false },
              ticks: { color: t.labelColor, font: { size: 12, family: 'Inter', weight: '600' } },
              border: { display: false },
            },
            xSales: {
              position: 'bottom',
              grid: { color: t.gridColor },
              ticks: {
                color: t.tickColor,
                font: { size: 11, family: 'Inter' },
                callback: (v) => '₱' + Number(v).toLocaleString(),
              },
              border: { display: false },
            },
            xQty: { display: false },
          },
        },
      })
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
  grid-template-columns: repeat(5, 1fr);
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

/* Icon wraps — color stays vivid, bg is mode-aware */
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

.kpi-sparkline { position: relative; height: 40px; margin-top: 14px; }

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
/* FIX: card--wide is a CSS Grid item (inside .row-2's grid-template-
   columns: 1fr 2fr), but grid items default to min-width: auto — meaning
   they refuse to shrink below their content's natural width. The
   Chart.js canvas inside this card (#inventoryChart) sets its own pixel
   width based on its container's size before any CSS constraint kicks
   in, which was forcing this card — and the whole .row-2 grid row, and
   therefore the page — wider than the viewport, causing the right-edge
   overflow/clipping seen on screen. min-width: 0 lets the grid item (and
   everything inside it) shrink to fit its actual allotted column width. */
.card--wide { min-width: 0; }

/* ─── CARD HEADER ──────────────────────────────────────── */
.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 20px;
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

/* ─── LEGEND ───────────────────────────────────────────── */
.legend-row { display: flex; align-items: center; gap: 8px; }
.legend-row--inline { margin-bottom: 16px; }
.legend-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 500;
  color: var(--d-text-muted);
  background: var(--d-surface-raised);
  border: 1px solid var(--d-border);
  border-radius: 20px;
  padding: 4px 10px 4px 8px;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.legend-dot { width: 8px; height: 8px; border-radius: 50%; }
.legend-chip--blue .legend-dot   { background: var(--d-blue); }
.legend-chip--green .legend-dot  { background: var(--d-green); }
.legend-chip--indigo .legend-dot { background: var(--d-violet); }
.legend-chip--amber .legend-dot  { background: var(--d-amber); }

/* ─── CHART AREAS ──────────────────────────────────────── */
.chart-area { position: relative; width: 100%; max-width: 100%; }
.chart-area canvas { max-width: 100%; }
.chart-area--tall       { height: 270px; }
.chart-area--horizontal { height: 320px; }
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
  font-size: 15px;
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
.donut-dot--blue  { background: var(--d-blue); }
.donut-dot--green { background: var(--d-green); }
.donut-dot--red   { background: var(--d-red); }

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
  margin-bottom: 20px;
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
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
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

/* ─── INVENTORY HORIZONTAL BAR ─────────────────────────── */
.inv-bar-wrap {
  position: relative;
  min-width: 0;
  height: 180px;
  border-top: 1px solid var(--d-border);
  padding-top: 18px;
  transition: border-color 0.22s ease;
}

/* ─── FLOATING "GO TO POS" BUTTON ──────────────────────── */
.pos-fab {
  position: fixed;
  left: 24px;
  bottom: 24px;
  z-index: 40;
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--d-accent);
  color: #fff;
  text-decoration: none;
  padding: 13px 20px 13px 16px;
  border-radius: 999px;
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 13.5px;
  font-weight: 700;
  letter-spacing: -.01em;
  box-shadow: 0 6px 20px rgba(234,88,12,0.35), 0 2px 6px rgba(0,0,0,0.12);
  transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease;
}
.pos-fab:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 26px rgba(234,88,12,0.42), 0 3px 8px rgba(0,0,0,0.16);
  background: #C2410C;
  color: #fff;
}
.pos-fab:active { transform: translateY(0); }

.pos-fab-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: rgba(255,255,255,0.18);
  font-size: 13px;
  flex-shrink: 0;
}
.pos-fab-label { white-space: nowrap; }

/* On small screens, shrink to an icon-only circular button so it
   doesn't crowd a narrow viewport */
@media (max-width: 560px) {
  .pos-fab {
    left: 16px;
    bottom: 16px;
    padding: 14px;
    border-radius: 50%;
  }
  .pos-fab-label { display: none; }
  .pos-fab-icon { width: 20px; height: 20px; background: transparent; }
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
}
</style>