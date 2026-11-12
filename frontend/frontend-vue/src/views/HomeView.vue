<template>
  <Layout title="Home">
    <div class="dash">

      <!-- HEADER -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Dashboard</h1>
          <p class="page-sub">
            <span>{{ todayLabel }}</span>
            <span v-if="loading" class="status status--busy"><i class="fa-solid fa-spinner fa-spin"></i> Updating…</span>
            <span v-else-if="lastUpdated" class="status"><i class="live-dot"></i>Updated {{ lastUpdated }}</span>
          </p>
        </div>
        <div class="page-header-right">
          <router-link v-if="canTransactions" :to="{ name: 'sales-transactions' }" class="btn"><i class="fa-solid fa-receipt"></i>Transactions</router-link>
          <button type="button" class="btn btn--primary" :disabled="loading" aria-label="Refresh dashboard" @click="loadDashboard()">
            <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>Refresh
          </button>
        </div>
      </div>

      <!-- NOTICES (slim one-line bars with a colored edge) -->
      <div v-if="loadError || outOfStockCount > 0 || lowStockCount > 0" class="notices">
        <div v-if="loadError" class="notice notice--error" role="alert">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span>{{ loadError }}</span>
          <button type="button" class="notice-btn" @click="loadDashboard()">Try again</button>
        </div>
        <div v-if="outOfStockCount > 0" class="notice notice--error" role="status">
          <i class="fa-solid fa-circle-xmark"></i>
          <span><strong>{{ outOfStockCount }} {{ outOfStockCount === 1 ? 'item is' : 'items are' }} out of stock.</strong> Customers can't buy {{ outOfStockCount === 1 ? 'it' : 'them' }} until restocked.</span>
          <router-link v-if="canProducts" :to="{ name: 'products', query: { stockFilter: 'out' } }" class="notice-btn">View out of stock</router-link>
        </div>
        <div v-if="lowStockCount > 0" class="notice notice--warn" role="status">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span><strong>{{ lowStockCount }} {{ lowStockCount === 1 ? 'item is' : 'items are' }} low on stock.</strong> Reorder soon to avoid running out.</span>
          <router-link v-if="canProducts" :to="{ name: 'products', query: { stockFilter: 'low' } }" class="notice-btn">View low stock</router-link>
        </div>
      </div>

      <!-- SKELETON -->
      <div v-if="firstLoad" class="dash-body" aria-busy="true" aria-label="Loading dashboard">
        <div class="row-two">
          <div class="card sk-card"><div class="sk sk-line"></div><div class="sk sk-big"></div><div class="sk sk-bar"></div><div class="sk sk-block"></div></div>
          <div class="card sk-card"><div class="sk sk-line"></div><div class="sk sk-big"></div><div class="sk sk-bar"></div><div class="sk sk-block"></div></div>
        </div>
        <div class="card sk-card"><div class="sk sk-line"></div><div class="sk sk-block sk-tall"></div></div>
      </div>

      <div v-else class="dash-body" :class="{ 'is-loading': loading }" :aria-busy="loading">

        <!-- TODAY + MONTH PACE -->
        <div class="row-two">

          <section class="card card--hero" style="--d: 0ms" aria-labelledby="hm-today">
            <header class="card-header">
              <div class="card-head-l">
                <span class="card-icon"><i class="fa-solid fa-sun"></i></span>
                <div>
                  <h2 id="hm-today" class="card-title">Today</h2>
                  <p class="card-sub">Sales so far</p>
                </div>
              </div>
              <div class="seg" role="group" aria-label="Daily target">
                <button v-for="t in targetModes" :key="t.key" type="button" class="seg-btn" :class="{ 'seg-btn--active': targetMode === t.key }" :aria-pressed="targetMode === t.key" @click="setTargetMode(t.key)">{{ t.label }}</button>
              </div>
            </header>

            <div class="hero-row">
              <div class="hero-main">
                <p class="big-value">₱{{ price(anim.today) }}</p>
                <p v-if="todayTrend.available" class="trend-line">
                  <span class="kpi-trend" :class="todayTrend.direction" :title="`Yesterday: ₱${price(yesterdayTotal)}`"><i :class="trendIcon(todayTrend)"></i>{{ todayTrend.text }}</span>
                  <span>vs yesterday</span>
                </p>
              </div>

              <div v-if="spark" class="spark" role="img" aria-label="Sales over the last 14 days">
                <svg viewBox="0 0 120 40" preserveAspectRatio="none">
                  <defs>
                    <linearGradient id="hm-spark-fill" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" style="stop-color: var(--d-accent); stop-opacity: .28" />
                      <stop offset="100%" style="stop-color: var(--d-accent); stop-opacity: 0" />
                    </linearGradient>
                  </defs>
                  <path class="spark-area" :class="{ 'is-on': barsReady }" :d="spark.area" fill="url(#hm-spark-fill)" />
                  <path class="spark-line" :class="{ 'is-on': barsReady }" :d="spark.line" pathLength="1" />
                </svg>
                <span class="spark-cap">Last {{ spark.days }} days</span>
              </div>
            </div>

            <div class="goal">
              <div class="goal-head">
                <span>{{ goalLabel }}</span>
                <label v-if="targetMode === 'custom'" class="goal-input">₱<input type="number" min="0" step="100" inputmode="decimal" v-model.number="customTarget" aria-label="Custom daily target" @change="saveTarget" /></label>
                <strong v-else-if="target > 0">₱{{ price(target) }}</strong>
              </div>
              <div class="goal-track" role="progressbar" :aria-valuenow="Math.min(goalPct, 100)" aria-valuemin="0" aria-valuemax="100">
                <div class="goal-fill" :class="{ 'goal-fill--done': goalPct >= 100 }" :style="{ width: goalBarPct + '%' }"></div>
              </div>
              <p class="goal-sub">{{ goalText }}</p>
            </div>

            <dl class="mini-stats">
              <div class="ms"><dt><i class="fa-solid fa-receipt"></i>Transactions</dt><dd>{{ formatNumber(anim.tx) }}</dd></div>
              <div class="ms"><dt><i class="fa-solid fa-box-open"></i>Items sold</dt><dd>{{ formatNumber(anim.items) }}</dd></div>
              <div class="ms"><dt><i class="fa-solid fa-tag"></i>Average sale</dt><dd>₱{{ price(anim.avg) }}</dd></div>
              <div class="ms"><dt><i class="fa-solid fa-arrow-trend-up"></i>Profit</dt><dd class="green">₱{{ price(anim.todayProfit) }}</dd></div>
            </dl>
          </section>

          <section class="card" style="--d: 70ms" aria-labelledby="hm-month">
            <header class="card-header">
              <div class="card-head-l">
                <span class="card-icon card-icon--blue"><i class="fa-solid fa-calendar-days"></i></span>
                <div>
                  <h2 id="hm-month" class="card-title">{{ monthName }} so far</h2>
                  <p class="card-sub">Day {{ dayOfMonth }} of {{ daysInMonth }}</p>
                </div>
              </div>
            </header>

            <p class="big-value big-value--sm">₱{{ price(anim.month) }}</p>
            <p v-if="lastMonthTotal" class="trend-line">
              <span class="kpi-trend" :class="monthBeat ? 'up' : 'flat'" :title="`Last month in full: ₱${price(lastMonthTotal)}`"><i :class="monthBeat ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-flag-checkered'"></i>{{ monthBeat ? 'Beat last month' : '₱' + price(monthGap) + ' to go' }}</span>
              <span>{{ monthBeat ? `last month in full was ₱${price(lastMonthTotal)}` : `to match last month in full (₱${price(lastMonthTotal)})` }}</span>
            </p>

            <div class="goal">
              <div class="goal-head"><span>Month progress</span><strong>{{ Math.round(monthProgress) }}%</strong></div>
              <div class="goal-track"><div class="goal-fill goal-fill--blue" :style="{ width: monthBarPct + '%' }"></div></div>
            </div>

            <dl class="mini-stats mini-stats--3">
              <div class="ms"><dt><i class="fa-solid fa-arrow-trend-up"></i>Profit</dt><dd class="green">₱{{ price(anim.profit) }}</dd></div>
              <div class="ms"><dt><i class="fa-solid fa-percent"></i>Margin</dt><dd>{{ anim.margin.toFixed(1) }}%</dd></div>
              <div class="ms">
                <dt><i class="fa-solid fa-trophy"></i>Best day</dt>
                <dd>{{ bestDay ? '₱' + price(bestDay.total) : '—' }}</dd>
                <span class="ms-sub">{{ bestDay ? bestDayLabel : 'No sales yet' }}</span>
              </div>
            </dl>

          </section>
        </div>

        <!-- CALENDAR -->
        <section class="card" style="--d: 140ms" aria-labelledby="hm-cal">
          <header class="card-header">
            <div class="card-head-l">
              <span class="card-icon card-icon--violet"><i class="fa-solid fa-table-cells"></i></span>
              <div>
                <h2 id="hm-cal" class="card-title">{{ monthName }} at a glance</h2>
                <p class="card-sub">Darker days sold more. Select a day for its numbers.</p>
              </div>
            </div>
            <div class="seg" role="group" aria-label="Calendar metric">
              <button type="button" class="seg-btn" :class="{ 'seg-btn--active': calMetric === 'total' }" :aria-pressed="calMetric === 'total'" @click="calMetric = 'total'">Sales</button>
              <button type="button" class="seg-btn" :class="{ 'seg-btn--active': calMetric === 'profit' }" :aria-pressed="calMetric === 'profit'" @click="calMetric = 'profit'">Profit</button>
            </div>
          </header>

          <div class="cal-layout">
            <div class="cal" :class="{ 'cal--profit': calMetric === 'profit' }">
              <span v-for="(w, i) in weekdays" :key="'w' + i" class="cal-dow">{{ w }}</span>
              <template v-for="(c, i) in calendar" :key="i">
                <span v-if="!c" class="cal-blank"></span>
                <button v-else type="button" class="cal-day" :style="{ '--i': i }" :class="[`lv${c.level}`, { 'cal-day--sel': c.key === activeRow.key, 'cal-day--today': c.key === todayKey }]" :disabled="c.future" :aria-pressed="c.key === activeRow.key" :aria-label="`${c.d}: ₱${price(c.v)}`" :title="`₱${price(c.v)}`" @click="selectDay(c.key)">{{ c.d }}</button>
              </template>
              <div class="cal-scale"><span>Less</span><i class="lv0"></i><i class="lv1"></i><i class="lv2"></i><i class="lv3"></i><i class="lv4"></i><span>More</span></div>
            </div>

            <div class="day" aria-live="polite">
              <div class="day-head">
                <h3 class="day-title">{{ activeLabel }}</h3>
                <span v-if="activeIsToday" class="chip">Today</span>
                <button v-else type="button" class="link-btn" @click="backToToday">Back to today</button>
              </div>
              <p class="day-value">₱{{ price(anim.day) }}</p>
              <div v-if="activeRow.total > 0" class="split-wrap">
                <div class="split-bar" role="img" :aria-label="`Profit ${activeMargin} percent of sales`">
                  <span class="split-seg split-seg--profit" :style="{ width: (splitReady ? activeProfitPct : 0) + '%' }" :title="`Profit ₱${price(activeRow.profit)}`"></span>
                  <span class="split-seg split-seg--cost" :style="{ width: (splitReady ? 100 - activeProfitPct : 0) + '%' }" :title="`Cost ₱${price(activeCost)}`"></span>
                </div>
                <div class="split-legend"><span><i class="sl sl--profit"></i>Profit</span><span><i class="sl sl--cost"></i>Cost of goods</span></div>
              </div>
              <p v-else class="muted">No sales on this day.</p>
              <dl class="facts">
                <div><dt>Profit</dt><dd class="green">₱{{ price(activeRow.profit) }}</dd></div>
                <div><dt>Cost of goods</dt><dd>₱{{ price(activeCost) }}</dd></div>
                <div><dt>Margin</dt><dd>{{ activeMargin }}%</dd></div>
                <div><dt>Share of month</dt><dd>{{ activeShare }}%</dd></div>
                <div><dt>Vs daily average</dt><dd :class="activeVsAvg >= 0 ? 'green' : 'red'">{{ activeVsAvg >= 0 ? '+' : '' }}{{ activeVsAvg.toFixed(1) }}%</dd></div>
              </dl>
            </div>
          </div>
        </section>

        <!-- SALES MIX + STOCK -->
        <div class="row-two">

          <section class="card" style="--d: 210ms" aria-labelledby="hm-mix">
            <header class="card-header">
              <div class="card-head-l">
                <span class="card-icon card-icon--green"><i class="fa-solid fa-chart-pie"></i></span>
                <div>
                  <h2 id="hm-mix" class="card-title">Where sales come from</h2>
                  <p class="card-sub">Share of {{ monthName }} {{ mixMetric === 'qty' ? 'units sold' : 'sales' }} by product</p>
                </div>
              </div>
              <div class="seg" role="group" aria-label="Measure by">
                <button type="button" class="seg-btn" :class="{ 'seg-btn--active': mixMetric === 'sales' }" :aria-pressed="mixMetric === 'sales'" @click="setMixMetric('sales')">By sales</button>
                <button type="button" class="seg-btn" :class="{ 'seg-btn--active': mixMetric === 'qty' }" :aria-pressed="mixMetric === 'qty'" @click="setMixMetric('qty')">By units</button>
              </div>
            </header>

            <div v-if="hasMix" class="mix">
              <p class="mix-insight">
                <i class="fa-solid fa-lightbulb"></i>
                <span>{{ mixInsight }}<template v-if="mixConcentrated"> Most of your sales rely on this product, so keep it well stocked.</template></span>
              </p>

              <div class="mix-body">
                <div class="donut">
                  <svg viewBox="0 0 200 200" role="group" aria-label="Share of sales by product">
                    <circle class="donut-track" cx="100" cy="100" r="74" />
                    <circle
                      v-for="(a, i) in mixArcs" :key="'arc' + i"
                      class="donut-seg" :class="{ 'is-on': focusIdx === i, 'is-dim': focusIdx !== null && focusIdx !== i }"
                      cx="100" cy="100" r="74" transform="rotate(-90 100 100)"
                      :stroke="mixRows[i].color"
                      :stroke-dasharray="`${mixReady ? a.len : 0} ${a.C}`"
                      :stroke-dashoffset="a.offset"
                      tabindex="0" role="button"
                      :aria-label="`${mixRows[i].name}, ${mixRows[i].pct.toFixed(1)} percent`"
                      @mouseenter="hoverIdx = i" @mouseleave="hoverIdx = null"
                      @focus="hoverIdx = i" @blur="hoverIdx = null"
                      @click="pickMix(i)" @keydown.enter.prevent="pickMix(i)" @keydown.space.prevent="pickMix(i)"
                    />
                  </svg>
                  <div class="donut-center" aria-live="polite">
                    <template v-if="focusRow">
                      <strong>{{ focusRow.pct.toFixed(1) }}%</strong>
                      <span class="dc-name">{{ focusRow.name }}</span>
                      <span class="dc-sub">{{ mixMetric === 'qty' ? formatNumber(focusRow.qty) + ' units' : '₱' + price(focusRow.sales) }}</span>
                    </template>
                    <template v-else>
                      <strong>{{ mixMetric === 'qty' ? formatNumber(mixTotal) : '₱' + compact(mixTotal) }}</strong>
                      <span class="dc-name">{{ mixMetric === 'qty' ? 'Units sold' : 'Total sales' }}</span>
                      <span class="dc-sub">Hover or select a slice</span>
                    </template>
                  </div>
                </div>

                <ul class="mix-list">
                  <li v-for="(r, i) in mixRows" :key="r.name + i" :class="{ 'mix-item--sel': mixIdx === i }">
                    <button type="button" class="mix-row" :class="{ 'mix-row--on': focusIdx === i && mixIdx !== i }" :aria-pressed="mixIdx === i" @click="pickMix(i)" @mouseenter="hoverIdx = i" @mouseleave="hoverIdx = null" @focus="hoverIdx = i" @blur="hoverIdx = null">
                      <span class="mix-top">
                        <i class="mix-dot" :style="{ background: r.color }"></i>
                        <span class="mix-name">{{ r.name }}</span>
                        <span class="mix-amt">{{ mixMetric === 'qty' ? formatNumber(r.qty) + ' units' : '₱' + price(r.sales) }}</span>
                        <span class="mix-pct">{{ r.pct.toFixed(1) }}%</span>
                      </span>
                      <span class="mix-bar"><span class="mix-bar-fill" :style="{ width: (mixReady ? r.pct : 0) + '%', background: r.color }"></span></span>
                    </button>
                    <dl v-if="mixIdx === i" class="mix-detail">
                      <div><dt>Sales</dt><dd>₱{{ price(r.sales) }}</dd></div>
                      <template v-if="r.qty !== null">
                        <div><dt>Units sold</dt><dd>{{ formatNumber(r.qty) }}</dd></div>
                        <div><dt>Average price</dt><dd>₱{{ price(r.qty ? r.sales / r.qty : 0) }}</dd></div>
                      </template>
                      <div v-else><dt>Includes</dt><dd>All other products</dd></div>
                    </dl>
                  </li>
                </ul>
              </div>

              <!-- Full Reports shortcut: Administrators only -->
              <router-link v-if="canReports" :to="{ name: 'reports' }" class="link-btn mix-link">See the full ranking in Reports<i class="fa-solid fa-arrow-right"></i></router-link>
            </div>

            <div v-else class="empty">
              <span class="empty-ico"><i class="fa-solid fa-chart-pie"></i></span>
              <p class="empty-title">No sales this month yet</p>
              <p class="empty-sub">Product shares will appear after your first sale.</p>
            </div>
          </section>

          <section class="card" style="--d: 280ms" aria-labelledby="hm-stock">
            <header class="card-header">
              <div class="card-head-l">
                <span class="card-icon card-icon--amber"><i class="fa-solid fa-boxes-stacked"></i></span>
                <div>
                  <h2 id="hm-stock" class="card-title">Needs attention</h2>
                  <p class="card-sub">Stock on the shelf right now</p>
                </div>
              </div>
            </header>

            <!-- Tiles are links only for roles that can open the Product List;
                 for everyone else they render as plain (non-clickable) tiles. -->
            <div class="tile-grid">
              <component :is="canProducts ? 'router-link' : 'div'" v-bind="tileProps()" class="tile" :class="{ 'tile--static': !canProducts }">
                <span class="tile-top"><span class="tile-ico"><i class="fa-solid fa-cubes"></i></span><i v-if="canProducts" class="fa-solid fa-arrow-up-right-from-square tile-go"></i></span>
                <p class="tile-label">In hand</p>
                <p class="tile-value">{{ formatNumber(anim.inHand) }}</p>
                <p class="tile-sub">{{ formatNumber(inventorySummary.quantity_to_receive) }} incoming</p>
              </component>
              <component :is="canProducts ? 'router-link' : 'div'" v-bind="tileProps({ stockFilter: 'low' })" class="tile" :class="{ 'tile--warn': lowStockCount > 0, 'tile--static': !canProducts }">
                <span class="tile-top"><span class="tile-ico"><i class="fa-solid fa-triangle-exclamation"></i></span><i v-if="canProducts" class="fa-solid fa-arrow-up-right-from-square tile-go"></i></span>
                <p class="tile-label">Low stock</p>
                <p class="tile-value">{{ formatNumber(anim.low) }}</p>
                <p class="tile-sub">{{ lowStockCount > 0 ? 'Reorder soon' : 'Nothing running low' }}</p>
              </component>
              <component :is="canProducts ? 'router-link' : 'div'" v-bind="tileProps({ stockFilter: 'out' })" class="tile" :class="{ 'tile--alert': outOfStockCount > 0, 'tile--static': !canProducts }">
                <span class="tile-top"><span class="tile-ico"><i class="fa-solid fa-circle-xmark"></i></span><i v-if="canProducts" class="fa-solid fa-arrow-up-right-from-square tile-go"></i></span>
                <p class="tile-label">Out of stock</p>
                <p class="tile-value">{{ formatNumber(anim.out) }}</p>
                <p class="tile-sub">{{ outOfStockCount > 0 ? 'Restock now' : 'All available' }}</p>
              </component>
            </div>

            <div v-if="stockCoverage" class="coverage">
              <div class="coverage-head">
                <span>{{ formatNumber(stockCoverage.active) }} of {{ formatNumber(stockCoverage.total) }} items active</span>
                <strong>{{ stockCoverage.pct }}%</strong>
              </div>
              <div class="goal-track"><div class="goal-fill goal-fill--done" :style="{ width: (barsReady ? stockCoverage.pct : 0) + '%' }"></div></div>
            </div>

            <div v-if="canProducts || canInvLogs || canSuppliers" class="link-list">
              <router-link v-if="canProducts"  :to="{ name: 'products' }"><i class="fa-solid fa-boxes-stacked"></i>Manage products<i class="fa-solid fa-chevron-right"></i></router-link>
              <router-link v-if="canInvLogs"   :to="{ name: 'inventory-logs' }"><i class="fa-solid fa-clock-rotate-left"></i>Inventory logs<i class="fa-solid fa-chevron-right"></i></router-link>
              <router-link v-if="canSuppliers" :to="{ name: 'suppliers' }"><i class="fa-solid fa-truck"></i>Suppliers<i class="fa-solid fa-chevron-right"></i></router-link>
            </div>
          </section>
        </div>

      </div>
    </div>
  </Layout>
</template>

<script>
import api from '@/api/axios'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { canAccess } from '@/utils/permissions'

// Protected route (auth:sanctum). See routes/api.php.
const DASHBOARD_URL = '/api/dashboard'
const TARGET_KEY = 'home.target'
const pad = (n) => String(n).padStart(2, '0')

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches

export default {
  name: 'HomeView',
  components: { Layout },

  data() {
    return {
      todaySales: 0,
      itemsSoldToday: 0,
      transactionsToday: 0,
      inventorySummary: {},
      monthlySales: [],
      dailyProfit: [],
      topSellingItems: [],
      totalProfit: 0,
      monthlyTotal: 0,
      yesterdayTotal: null,
      lastMonthTotal: null,

      targetModes: [
        { key: 'yesterday', label: 'Yesterday' },
        { key: 'average', label: 'Average' },
        { key: 'custom', label: 'Custom' },
      ],
      targetMode: 'yesterday',
      customTarget: 0,
      calMetric: 'total',
      selectedKey: null,
      weekdays: ['S', 'M', 'T', 'W', 'T', 'F', 'S'],

      mixMetric: 'sales',
      mixIdx: null,
      hoverIdx: null,
      mixReady: false,

      // Entrance animation state: counting numbers + progress bars that
      // start at 0 and grow to their real value once data has loaded.
      anim: {
        today: 0, month: 0, tx: 0, items: 0, avg: 0, todayProfit: 0,
        profit: 0, margin: 0, inHand: 0, low: 0, out: 0, day: 0,
      },
      barsReady: false,
      splitReady: false,
      raf: 0,
      dayRaf: 0,

      firstLoad: true,
      loading: false,
      loadError: '',
      lastUpdated: '',
      requestSeq: 0,
    }
  },

  computed: {
    /* ── ROLE-BASED LINKS ──────────────────────────────────
       Every role lands on this page, so each shortcut is checked
       against the signed-in user's role (see utils/permissions.js). */
    role() { return useAuthStore().user?.role || '' },
    canReports()      { return canAccess(this.role, '/reports') },
    canProducts()     { return canAccess(this.role, '/products') },
    canTransactions() { return canAccess(this.role, '/sales-transaction') },
    canSuppliers()    { return canAccess(this.role, '/suppliers') },
    canInvLogs()      { return canAccess(this.role, '/inventory-logs') },

    todayKey() { return new Date().toLocaleDateString('en-CA') },
    todayLabel() { return new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }) },
    monthName() { return new Date().toLocaleDateString('en-US', { month: 'long' }) },
    dayOfMonth() { return new Date().getDate() },
    daysInMonth() { const n = new Date(); return new Date(n.getFullYear(), n.getMonth() + 1, 0).getDate() },
    monthProgress() { return (this.dayOfMonth / this.daysInMonth) * 100 },
    /* The single best day of the month so far. */
    bestDay() {
      const rows = this.monthRows.filter((r) => r.total > 0)
      return rows.length ? rows.reduce((a, r) => (r.total > a.total ? r : a)) : null
    },
    bestDayLabel() {
      const d = this.bestDay
      if (!d) return ''
      return d.key === this.todayKey
        ? 'Today'
        : new Date(d.key + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })
    },

    /* Month so far vs last month in full — plain numbers, no forecast. */
    monthGap() { return Math.max(this.num(this.lastMonthTotal) - this.num(this.monthlyTotal), 0) },
    monthBeat() { return this.num(this.lastMonthTotal) > 0 && this.num(this.monthlyTotal) >= this.num(this.lastMonthTotal) },

    /* LOW = above 0 but at/below reorder level. OUT = exactly 0. They never overlap. */
    lowStockCount() { return this.num(this.inventorySummary.low_stock_items) },
    outOfStockCount() { return this.num(this.inventorySummary.out_of_stock_items) },
    avgTodaySale() { return this.transactionsToday ? this.num(this.todaySales) / this.num(this.transactionsToday) : 0 },
    monthMarginPct() {
      const t = this.num(this.monthlyTotal)
      return t ? ((this.num(this.totalProfit) / t) * 100).toFixed(1) : '0.0'
    },
    todayTrend() { return this.makeTrend(this.todaySales, this.yesterdayTotal) },

    /* daily target */
    avgDaily() {
      const rows = this.monthRows.filter((r) => r.key !== this.todayKey && r.total > 0)
      return rows.length ? rows.reduce((a, r) => a + r.total, 0) / rows.length : 0
    },
    target() {
      if (this.targetMode === 'custom') return this.num(this.customTarget)
      if (this.targetMode === 'average') return this.avgDaily
      return this.num(this.yesterdayTotal)
    },
    goalPct() { return this.target ? Math.round((this.num(this.todaySales) / this.target) * 100) : 0 },
    goalBarPct() { return this.barsReady ? Math.min(this.goalPct, 100) : 0 },
    monthBarPct() { return this.barsReady ? this.monthProgress : 0 },
    goalLabel() {
      const base = { yesterday: 'Match yesterday', average: 'Match your daily average', custom: 'Daily target' }[this.targetMode]
      return this.goalPct >= 100 ? 'Target reached' : base
    },
    goalText() {
      if (!this.target) return this.targetMode === 'custom' ? 'Enter an amount to track your progress.' : 'Not enough earlier sales to set a target.'
      const diff = this.num(this.todaySales) - this.target
      return diff >= 0 ? `₱${this.price(diff)} past the target (${this.goalPct}%)` : `₱${this.price(-diff)} to go (${this.goalPct}%)`
    },

    todaysProfit() {
      const row = this.dailyProfit.find((p) => String(p.date || '').slice(0, 10) === this.todayKey)
      return row ? this.num(row.profit) : 0
    },

    /* days */
    monthRows() {
      const profitMap = {}
      this.dailyProfit.forEach((p) => { profitMap[String(p.date || '').slice(0, 10)] = this.num(p.profit) })
      return this.monthlySales.map((s) => {
        const key = String(s.date || '').slice(0, 10)
        return { key, total: this.num(s.total), profit: profitMap[key] ?? 0 }
      })
    },

    /* sparkline — daily sales for up to the last 14 days of this month */
    spark() {
      const now = new Date()
      const y = now.getFullYear()
      const m = now.getMonth()
      const end = now.getDate()
      const start = Math.max(1, end - 13)
      const map = {}
      this.monthRows.forEach((r) => { map[r.key] = r.total })
      const values = []
      for (let d = start; d <= end; d++) values.push(map[`${y}-${pad(m + 1)}-${pad(d)}`] || 0)
      if (values.length < 2 || !values.some((v) => v > 0)) return null

      const W = 120, H = 40, P = 4
      const max = Math.max(...values, 1)
      const pts = values.map((v, i) => [(i / (values.length - 1)) * W, H - P - (v / max) * (H - P * 2)])
      const line = 'M' + pts.map((p) => `${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' L')
      return { line, area: `${line} L${W} ${H} L0 ${H} Z`, days: values.length }
    },

    calendar() {
      const now = new Date()
      const y = now.getFullYear()
      const m = now.getMonth()
      const map = {}
      this.monthRows.forEach((r) => { map[r.key] = r })
      const max = Math.max(...this.monthRows.map((r) => r[this.calMetric]), 0)
      const cells = Array(new Date(y, m, 1).getDay()).fill(null)
      for (let d = 1; d <= this.daysInMonth; d++) {
        const key = `${y}-${pad(m + 1)}-${pad(d)}`
        const v = map[key] ? map[key][this.calMetric] : 0
        const ratio = max ? v / max : 0
        cells.push({ d, key, v, future: d > now.getDate(), level: v <= 0 ? 0 : ratio > 0.75 ? 4 : ratio > 0.5 ? 3 : ratio > 0.25 ? 2 : 1 })
      }
      return cells
    },
    activeRow() {
      const key = this.selectedKey ?? this.todayKey
      return this.monthRows.find((r) => r.key === key) || { key, total: 0, profit: 0 }
    },
    activeIsToday() { return this.activeRow.key === this.todayKey },
    activeLabel() { return new Date(this.activeRow.key + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }) },
    activeCost() { return Math.max(this.activeRow.total - this.activeRow.profit, 0) },
    activeMargin() { const r = this.activeRow; return r.total ? ((r.profit / r.total) * 100).toFixed(1) : '0.0' },
    activeProfitPct() { const r = this.activeRow; return r.total ? Math.min(100, Math.max(0, (r.profit / r.total) * 100)) : 0 },
    activeShare() { const t = this.num(this.monthlyTotal); return t ? ((this.activeRow.total / t) * 100).toFixed(1) : '0.0' },
    activeVsAvg() {
      const rows = this.monthRows.filter((r) => r.total > 0)
      const avg = rows.length ? rows.reduce((a, r) => a + r.total, 0) / rows.length : 0
      return avg ? ((this.activeRow.total - avg) / avg) * 100 : 0
    },

    /* sales mix */
    mixRows() {
      const palette = ['#EA580C', '#6366F1', '#10B981', '#3B82F6', '#F59E0B']
      const byQty = this.mixMetric === 'qty'
      const items = this.topSellingItems
        .map((i) => ({ name: i.ProductName, sales: this.num(i.total_sales), qty: this.num(i.total_quantity) }))
        .sort((a, b) => (byQty ? b.qty - a.qty : b.sales - a.sales))
        .slice(0, 5)
        .map((r, idx) => ({ ...r, color: palette[idx], rank: idx + 1 }))
      if (!byQty) {
        const other = Math.max(this.num(this.monthlyTotal) - items.reduce((a, r) => a + r.sales, 0), 0)
        if (other > 0.5) items.push({ name: 'Other items', sales: other, qty: null, color: '#94A3B8', rank: null })
      }
      const val = (r) => (byQty ? r.qty : r.sales)
      const base = items.reduce((a, r) => a + val(r), 0) || 1
      return items.map((r) => ({ ...r, pct: (val(r) / base) * 100 }))
    },
    hasMix() { return this.mixRows.some((r) => r.pct > 0) },
    mixTotal() { return this.mixRows.reduce((a, r) => a + (this.mixMetric === 'qty' ? r.qty : r.sales), 0) },
    focusIdx() { return this.hoverIdx ?? this.mixIdx },
    focusRow() { return this.focusIdx === null ? null : this.mixRows[this.focusIdx] || null },
    mixArcs() {
      const C = 2 * Math.PI * 74
      const gap = this.mixRows.length > 1 ? 3 : 0
      let acc = 0
      return this.mixRows.map((r) => {
        const full = (r.pct / 100) * C
        const arc = { len: Math.max(full - gap, 0.5), offset: -acc, C }
        acc += full
        return arc
      })
    },
    mixInsight() {
      const lead = this.mixRows.find((r) => r.rank === 1)
      if (!lead) return ''
      return this.mixMetric === 'qty'
        ? `${lead.name} is ${lead.pct.toFixed(1)}% of the units sold among your top products.`
        : `${lead.name} brings in ${lead.pct.toFixed(1)}% of ${this.monthName} sales.`
    },
    mixConcentrated() {
      const lead = this.mixRows.find((r) => r.rank === 1)
      return this.mixMetric === 'sales' && !!lead && lead.pct >= 60
    },

    stockCoverage() {
      const total = this.num(this.inventorySummary.total_items)
      const active = this.num(this.inventorySummary.active_items)
      return total ? { total, active, pct: Math.min(100, Math.round((active / total) * 100)) } : null
    },
  },

  mounted() {
    try {
      const saved = JSON.parse(localStorage.getItem(TARGET_KEY) || '{}')
      if (['yesterday', 'average', 'custom'].includes(saved.mode)) this.targetMode = saved.mode
      this.customTarget = this.num(saved.custom)
    } catch (e) { /* no-op */ }
    this.loadDashboard()
  },

  beforeUnmount() {
    this.requestSeq++
    cancelAnimationFrame(this.raf)
    cancelAnimationFrame(this.dayRaf)
  },

  methods: {
    num(v) { const n = Number(v); return Number.isFinite(n) ? n : 0 },
    formatNumber(n) { return Math.round(this.num(n)).toLocaleString('en-US') },
    price(v) { return this.num(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
    compact(v) { return Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 }).format(this.num(v)) },

    /* Props for a stock tile: a router-link target when the role may open
       the Product List, nothing (so it stays a plain div) when it may not. */
    tileProps(query) {
      return this.canProducts ? { to: { name: 'products', ...(query ? { query } : {}) } } : {}
    },

    makeTrend(current, previous) {
      if (previous === null || previous === undefined) return { direction: 'flat', text: '', available: false }
      const prev = this.num(previous)
      const cur = this.num(current)
      if (!prev) return cur > 0 ? { direction: 'up', text: 'New', available: true } : { direction: 'flat', text: '0.0%', available: true }
      const change = ((cur - prev) / prev) * 100
      return { direction: change > 0.5 ? 'up' : change < -0.5 ? 'down' : 'flat', text: `${Math.abs(change).toFixed(1)}%`, available: true }
    },
    trendIcon(t) {
      return t.direction === 'up' ? 'fa-solid fa-arrow-trend-up' : t.direction === 'down' ? 'fa-solid fa-arrow-trend-down' : 'fa-solid fa-minus'
    },

    setTargetMode(mode) { this.targetMode = mode; this.saveTarget() },
    saveTarget() {
      if (!(this.customTarget >= 0)) this.customTarget = 0
      try { localStorage.setItem(TARGET_KEY, JSON.stringify({ mode: this.targetMode, custom: this.customTarget })) } catch (e) { /* no-op */ }
    },

    /* day selection — count the amount up and regrow the split bar */
    selectDay(key) {
      this.selectedKey = key === this.todayKey ? null : key
      this.animateDay()
    },
    backToToday() {
      this.selectedKey = null
      this.animateDay()
    },
    animateDay() {
      this.animateTo({ day: this.activeRow.total }, 500, 'dayRaf')
      this.grow('splitReady')
    },

    /* sales mix */
    pickMix(i) { this.mixIdx = this.mixIdx === i ? null : i },
    setMixMetric(m) {
      if (this.mixMetric === m) return
      this.mixMetric = m
      this.mixIdx = null
      this.hoverIdx = null
      this.animateMix()
    },
    animateMix() {
      this.grow('mixReady')
    },

    /* ── ENTRANCE ANIMATION ─────────────────────────────────
       Counts the given numbers from their current value to the target
       with an ease-out curve. `rafKey` lets the day-picker run its own
       tween without cancelling the main one. Honors reduced motion. */
    animateTo(targets, duration = 800, rafKey = 'raf') {
      cancelAnimationFrame(this[rafKey])

      if (prefersReducedMotion()) {
        Object.assign(this.anim, targets)
        return
      }

      const from = {}
      Object.keys(targets).forEach((k) => { from[k] = this.anim[k] })
      const start = performance.now()

      const tick = (now) => {
        const p = Math.min((now - start) / duration, 1)
        if (p >= 1) {
          Object.assign(this.anim, targets)
          return
        }
        const e = 1 - Math.pow(1 - p, 3)
        Object.keys(targets).forEach((k) => {
          this.anim[k] = from[k] + (targets[k] - from[k]) * e
        })
        this[rafKey] = requestAnimationFrame(tick)
      }
      this[rafKey] = requestAnimationFrame(tick)
    },

    /* Resets a flag to false, waits for the DOM to render that 0-width
       state, then flips it to true so the CSS transition actually plays. */
    async grow(flag) {
      this[flag] = false
      await this.$nextTick()
      void document.body.offsetHeight
      requestAnimationFrame(() => requestAnimationFrame(() => { this[flag] = true }))
    },

    animateBars() {
      this.grow('barsReady')
      this.grow('splitReady')
    },

    animateAll() {
      this.animateTo({
        today: this.num(this.todaySales),
        month: this.num(this.monthlyTotal),
        tx: this.num(this.transactionsToday),
        items: this.num(this.itemsSoldToday),
        avg: this.avgTodaySale,
        todayProfit: this.todaysProfit,
        profit: this.num(this.totalProfit),
        margin: Number(this.monthMarginPct),
        inHand: this.num(this.inventorySummary.quantity_in_hand),
        low: this.lowStockCount,
        out: this.outOfStockCount,
        day: this.activeRow.total,
      })
      this.animateBars()
      this.animateMix()
    },

    /* data */
    async fetchTotal(range) {
      try {
        const res = await api.get(DASHBOARD_URL, { params: { date_range: range } })
        return this.num(res.data?.monthlyTotal)
      } catch (err) {
        console.error(`Comparison fetch failed (${range}):`, err)
        return null
      }
    },
    async loadDashboard() {
      const seq = ++this.requestSeq
      this.loading = true
      this.loadError = ''
      try {
        const [res, yesterday, lastMonth] = await Promise.all([
          api.get(DASHBOARD_URL, { params: { date_range: 'this_month' } }),
          this.fetchTotal('yesterday'),
          this.fetchTotal('last_month'),
        ])
        if (seq !== this.requestSeq) return
        Object.assign(this, res.data)
        this.yesterdayTotal = yesterday
        this.lastMonthTotal = lastMonth
        this.barsReady = false      // bars mount at 0 width…
        this.splitReady = false
        this.mixReady = false
        this.firstLoad = false
        this.lastUpdated = new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })
        await this.$nextTick()
        if (seq !== this.requestSeq) return
        this.animateAll()           // …then numbers count up and bars grow
      } catch (err) {
        if (seq !== this.requestSeq) return
        console.error('Dashboard load error:', err)
        this.loadError = err?.response?.data?.message || 'Could not load the dashboard. Check your connection and try again.'
        this.firstLoad = false
      } finally {
        if (seq === this.requestSeq) this.loading = false
      }
    },
  },
}
</script>

<style scoped>
.dash {
  --d-bg: var(--c-bg); --d-surface: var(--c-surface); --d-raised: var(--c-surface-raised); --d-sunken: var(--c-surface-sunken);
  --d-border: var(--c-border); --d-border-strong: var(--c-border-strong);
  --d-text: var(--c-text-primary); --d-text-2: var(--c-text-secondary); --d-muted: var(--c-text-muted); --d-faint: var(--c-text-faint);
  --d-accent: var(--c-accent); --d-accent-soft: var(--c-accent-soft); --d-accent-border: var(--c-accent-border);
  --d-green: #10b981; --d-green-soft: rgba(16,185,129,.12);
  --d-blue: #3b82f6; --d-blue-soft: rgba(59,130,246,.12);
  --d-violet: #6366f1; --d-violet-soft: rgba(99,102,241,.12);
  --d-red: #f43f5e; --d-red-soft: rgba(244,63,94,.12);
  --d-amber: #f59e0b; --d-amber-soft: rgba(245,158,11,.14);

  /* premium surface tokens */
  --d-shadow: 0 1px 2px rgba(16,24,40,.04), 0 10px 28px -14px rgba(16,24,40,.14);
  --d-shadow-hover: 0 2px 4px rgba(16,24,40,.05), 0 18px 40px -16px rgba(16,24,40,.22);
  --d-shine: rgba(255,255,255,.7);
  --d-ease: cubic-bezier(.16, 1, .3, 1);

  /* ── SPACE TUNING ──────────────────────────────────────────
     The app shell (Layout.vue) adds roughly 64px of its own padding around
     every page. --bleed-* lets this page reclaim part of it on desktop.
     Raise a value to push the content closer to the edge, lower it (or set
     it to 0px) if content ever touches the sidebar or top bar. */
  --bleed-x: 30px;
  --bleed-y: 26px;
  --d-gap: 16px;

  min-height: 100%; background: var(--d-bg); padding: 16px 24px 40px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--d-gap);
  -webkit-font-smoothing: antialiased;
}
html[data-theme="dark"] .dash {
  --d-green-soft: rgba(16,185,129,.16); --d-blue-soft: rgba(59,130,246,.18); --d-violet-soft: rgba(99,102,241,.2);
  --d-red-soft: rgba(244,63,94,.16); --d-amber-soft: rgba(245,158,11,.16);
  --d-shadow: 0 1px 2px rgba(0,0,0,.3), 0 12px 32px -14px rgba(0,0,0,.55);
  --d-shadow-hover: 0 2px 4px rgba(0,0,0,.35), 0 20px 44px -16px rgba(0,0,0,.7);
  --d-shine: rgba(255,255,255,.07);
}
.dash button:focus-visible, .dash a:focus-visible, .dash input:focus-visible { outline: 2px solid var(--d-accent); outline-offset: 2px; }
.dash-body { display: flex; flex-direction: column; gap: var(--d-gap); transition: opacity .2s ease; }
.dash-body.is-loading { opacity: .7; }
.green { color: var(--d-green); }
.red { color: var(--d-red); }
.muted { margin: 12px 0 0; font-size: 12.5px; color: var(--d-muted); }

/* ── header ── */
.page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.page-title { font-size: 26px; font-weight: 800; color: var(--d-text); letter-spacing: -.03em; margin: 0; line-height: 1.15; }
.page-sub { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 4px 0 0; font-size: 13.5px; font-weight: 500; color: var(--d-text-2); }
.status { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; color: var(--d-muted); padding: 2px 10px 2px 8px; background: var(--d-surface); border: 1px solid var(--d-border); border-radius: 999px; }
.status--busy { color: var(--d-accent); font-weight: 600; padding-left: 10px; }
.live-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--d-green); box-shadow: 0 0 0 0 rgba(16,185,129,.5); animation: pulse 2.2s ease-out infinite; }
@keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,.45); } 70%, 100% { box-shadow: 0 0 0 7px rgba(16,185,129,0); } }
.page-header-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 16px;
  font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; text-decoration: none;
  color: var(--d-text-2); background: var(--d-surface); border: 1px solid var(--d-border-strong); border-radius: 10px;
  box-shadow: 0 1px 2px rgba(16,24,40,.05);
  transition: border-color .15s ease, color .15s ease, transform .15s ease, box-shadow .15s ease, filter .15s ease;
}
.btn:hover:not(:disabled) { border-color: var(--d-accent-border); color: var(--d-text); transform: translateY(-1px); box-shadow: 0 4px 12px -4px rgba(16,24,40,.18); }
.btn:active:not(:disabled) { transform: translateY(0); }
.btn:disabled { opacity: .55; cursor: default; }
.btn i { font-size: 11px; }
.btn--primary { background: var(--d-accent); border-color: var(--d-accent); color: #fff; box-shadow: 0 1px 2px rgba(16,24,40,.12), inset 0 1px 0 rgba(255,255,255,.18); }
.btn--primary:hover:not(:disabled) { color: #fff; border-color: var(--d-accent); filter: brightness(1.07); }

.link-btn { display: inline-flex; align-items: center; gap: 6px; border: none; background: transparent; cursor: pointer; font: inherit; font-size: 12.5px; font-weight: 700; color: var(--d-accent); padding: 5px 9px; border-radius: 8px; text-decoration: none; white-space: nowrap; transition: background-color .15s ease; }
.link-btn:hover { background: var(--d-accent-soft); }
.link-btn i { font-size: 9px; transition: transform .2s var(--d-ease); }
.link-btn:hover i { transform: translateX(3px); }

/* ── notices: slim one-line bars, color-coded with a bold left edge ── */
.notices { display: flex; flex-direction: column; gap: 6px; }
.notice {
  display: flex; align-items: center; gap: 10px; min-height: 38px; padding: 5px 8px 5px 12px;
  border-radius: 10px; border-left-width: 4px; font-size: 12.5px; line-height: 1.35;
  animation: rise .5s var(--d-ease) backwards;
}
.notice > span { flex: 1; min-width: 0; color: var(--d-text-2); }
.notice strong { color: var(--d-text); font-weight: 700; }
.notice > i { width: 22px; height: 22px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }
.notice--error { background: var(--d-red-soft); border: 1px solid rgba(244,63,94,.28); border-left: 4px solid var(--d-red); }
.notice--error > i { color: var(--d-red); background: rgba(244,63,94,.16); }
.notice--warn { background: var(--d-amber-soft); border: 1px solid rgba(245,158,11,.32); border-left: 4px solid var(--d-amber); }
.notice--warn > i { color: var(--d-amber); background: rgba(245,158,11,.18); }
.notice-btn { flex-shrink: 0; border: 1px solid var(--d-accent-border); background: transparent; cursor: pointer; font: inherit; font-size: 12px; font-weight: 700; color: var(--d-accent); padding: 4px 12px; border-radius: 8px; text-decoration: none; white-space: nowrap; transition: background-color .15s ease; }
.notice-btn:hover { background: var(--d-accent-soft); }

/* ── cards ── */
.card {
  position: relative; min-width: 0; padding: 20px 22px;
  background: var(--d-surface);
  border: 1px solid var(--d-border); border-radius: 18px;
  box-shadow: var(--d-shadow);
  transition: box-shadow .3s var(--d-ease), border-color .3s ease, transform .3s var(--d-ease);
  animation: rise .7s var(--d-ease) backwards;
  animation-delay: var(--d, 0ms);
}
.card::before {
  /* hairline highlight along the top edge */
  content: ''; position: absolute; top: 0; left: 24px; right: 24px; height: 1px; pointer-events: none;
  background: linear-gradient(90deg, transparent, var(--d-shine), transparent);
}
.card:hover { box-shadow: var(--d-shadow-hover); border-color: var(--d-border-strong); }
@keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }

.card--hero { overflow: hidden; }
.card--hero::after {
  /* soft accent glow in the corner */
  content: ''; position: absolute; top: -90px; right: -70px; width: 280px; height: 280px; pointer-events: none;
  background: radial-gradient(circle, color-mix(in srgb, var(--d-accent) 16%, transparent) 0%, transparent 68%);
}
.card--hero > * { position: relative; z-index: 1; }

.card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.card-head-l { display: flex; align-items: center; gap: 12px; min-width: 0; }
.card-icon {
  width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 15px;
  color: var(--d-accent); background: var(--d-accent-soft);
  box-shadow: inset 0 0 0 1px var(--d-accent-border);
}
.card-icon--blue   { color: var(--d-blue);   background: var(--d-blue-soft);   box-shadow: inset 0 0 0 1px rgba(59,130,246,.22); }
.card-icon--violet { color: var(--d-violet); background: var(--d-violet-soft); box-shadow: inset 0 0 0 1px rgba(99,102,241,.24); }
.card-icon--green  { color: var(--d-green);  background: var(--d-green-soft);  box-shadow: inset 0 0 0 1px rgba(16,185,129,.24); }
.card-icon--amber  { color: var(--d-amber);  background: var(--d-amber-soft);  box-shadow: inset 0 0 0 1px rgba(245,158,11,.28); }
.card-title { font-size: 15.5px; font-weight: 700; color: var(--d-text); margin: 0; letter-spacing: -.015em; }
.card-sub { margin: 3px 0 0; font-size: 12.5px; color: var(--d-muted); }
.row-two { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: var(--d-gap); align-items: stretch; }
.chip { font-size: 10.5px; font-weight: 700; letter-spacing: .02em; color: var(--d-accent); background: var(--d-accent-soft); border: 1px solid var(--d-accent-border); padding: 2px 10px; border-radius: 20px; }

/* ── segmented control ── */
.seg { display: inline-flex; gap: 2px; padding: 3px; background: var(--d-sunken); border: 1px solid var(--d-border); border-radius: 11px; }
.seg-btn { border: none; background: transparent; cursor: pointer; font: inherit; font-size: 12px; font-weight: 600; color: var(--d-muted); padding: 6px 12px; border-radius: 8px; transition: background-color .2s ease, color .2s ease, box-shadow .2s ease; }
.seg-btn:hover:not(.seg-btn--active) { color: var(--d-text); }
.seg-btn--active { background: var(--d-surface); color: var(--d-text); box-shadow: 0 1px 3px rgba(16,24,40,.16), 0 0 0 1px var(--d-border); }

/* ── trend + big numbers ── */
.kpi-trend { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; cursor: help; }
.kpi-trend i { font-size: 9.5px; }
.kpi-trend.up { color: var(--d-green); background: var(--d-green-soft); }
.kpi-trend.down { color: var(--d-red); background: var(--d-red-soft); }
.kpi-trend.flat { color: var(--d-muted); background: var(--d-raised); }
.trend-line { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 10px 0 0; font-size: 12.5px; color: var(--d-muted); }
.big-value { margin: 0; font-size: clamp(32px, 4.2vw, 46px); font-weight: 800; color: var(--d-text); letter-spacing: -.04em; line-height: 1.02; font-variant-numeric: tabular-nums; }
.big-value--sm { font-size: clamp(28px, 3.2vw, 36px); }

.hero-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
.hero-main { min-width: 0; }

/* sparkline */
.spark { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.spark svg { width: 150px; height: 46px; overflow: visible; }
.spark-line { fill: none; stroke: var(--d-accent); stroke-width: 2.25; stroke-linecap: round; stroke-linejoin: round; vector-effect: non-scaling-stroke; stroke-dasharray: 1; stroke-dashoffset: 1; transition: stroke-dashoffset 1.4s var(--d-ease) .15s; }
.spark-line.is-on { stroke-dashoffset: 0; }
.spark-area { opacity: 0; transition: opacity 1s ease .5s; }
.spark-area.is-on { opacity: 1; }
.spark-cap { font-size: 11px; font-weight: 500; color: var(--d-faint); }

/* ── progress ── */
.goal { margin-top: 20px; }
.goal-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; min-height: 28px; font-size: 12.5px; font-weight: 600; color: var(--d-text-2); margin-bottom: 8px; }
.goal-head strong { font-weight: 700; color: var(--d-text); font-variant-numeric: tabular-nums; }
.goal-input { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border: 1px solid var(--d-border-strong); border-radius: 9px; background: var(--d-sunken); color: var(--d-muted); font-weight: 700; transition: border-color .15s ease, box-shadow .15s ease; }
.goal-input:focus-within { border-color: var(--d-accent); box-shadow: 0 0 0 3px var(--d-accent-soft); }
.goal-input input { width: 90px; border: none; background: transparent; font: inherit; font-weight: 700; color: var(--d-text); text-align: right; outline: none; }
.goal-track { height: 10px; border-radius: 10px; background: var(--d-sunken); box-shadow: inset 0 1px 2px rgba(16,24,40,.08); overflow: hidden; }
.goal-fill {
  height: 100%; border-radius: 10px;
  background: linear-gradient(90deg, var(--d-accent), color-mix(in srgb, var(--d-accent) 70%, #fbbf24));
  box-shadow: 0 0 12px -2px color-mix(in srgb, var(--d-accent) 60%, transparent);
  transition: width 1.1s var(--d-ease);
}
.goal-fill--done { background: linear-gradient(90deg, #059669, var(--d-green)); box-shadow: 0 0 12px -2px rgba(16,185,129,.55); }
.goal-fill--blue { background: linear-gradient(90deg, #2563eb, var(--d-blue)); box-shadow: 0 0 12px -2px rgba(59,130,246,.55); }
.goal-sub { margin: 8px 0 0; font-size: 12px; color: var(--d-muted); }

/* ── mini stats ── */
.mini-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(118px, 1fr)); gap: 10px; margin: 20px 0 0; }
.mini-stats--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.ms { padding: 12px 14px; background: var(--d-raised); border: 1px solid var(--d-border); border-radius: 12px; min-width: 0; transition: border-color .2s ease, transform .2s var(--d-ease); }
.ms:hover { border-color: var(--d-accent-border); transform: translateY(-2px); }
.mini-stats dt { display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 500; color: var(--d-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-stats dt i { font-size: 10px; color: var(--d-faint); }
.mini-stats dd { margin: 5px 0 0; font-size: 16px; font-weight: 700; color: var(--d-text); letter-spacing: -.01em; font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-stats dd.green { color: var(--d-green); }
.ms-sub { display: block; margin-top: 3px; font-size: 11.5px; color: var(--d-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }


/* ── calendar ── */
.cal-layout { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); gap: 28px; align-items: start; }
.cal { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 7px; --lv: 234,88,12; }
.cal--profit { --lv: 16,185,129; }
.cal-dow { text-align: center; font-size: 11px; font-weight: 600; letter-spacing: .04em; color: var(--d-faint); padding-bottom: 4px; }
.cal-day {
  aspect-ratio: 1.15; border: 1px solid transparent; border-radius: 10px; cursor: pointer;
  font: inherit; font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums; color: var(--d-text-2);
  transition: transform .15s var(--d-ease), border-color .15s ease, box-shadow .15s ease, background-color .3s ease;
  /* staggered entrance; `backwards` so it never overrides the hover transform afterwards */
  animation: cal-pop .5s var(--d-ease) backwards;
  animation-delay: calc(var(--i, 0) * 16ms + 200ms);
}
@keyframes cal-pop { from { opacity: 0; transform: scale(.6); } to { opacity: 1; transform: scale(1); } }
.cal-day:hover:not(:disabled) { transform: scale(1.1); border-color: rgb(var(--lv)); box-shadow: 0 6px 14px -6px rgba(var(--lv), .6); z-index: 1; }
.cal-day:disabled { cursor: default; opacity: .35; }
.cal-day--today { border-color: var(--d-text-2); }
.cal-day--sel { border-color: rgb(var(--lv)); box-shadow: 0 0 0 3px rgba(var(--lv), .28); color: var(--d-text); }
.lv0 { background: var(--d-sunken); }
.lv1 { background: rgba(var(--lv), .16); }
.lv2 { background: rgba(var(--lv), .34); }
.lv3 { background: rgba(var(--lv), .58); color: var(--d-text); }
.lv4 { background: rgba(var(--lv), .88); color: #fff; }
.cal-scale { grid-column: 1 / -1; display: flex; align-items: center; justify-content: flex-end; gap: 4px; margin-top: 8px; font-size: 11px; color: var(--d-muted); }
.cal-scale i { width: 14px; height: 14px; border-radius: 4px; }
.cal-scale span:first-child { margin-right: 3px; }
.cal-scale span:last-child { margin-left: 3px; }

.day { padding: 20px; background: var(--d-raised); border: 1px solid var(--d-border); border-radius: 16px; }
.day-head { display: flex; align-items: center; gap: 10px; min-height: 28px; }
.day-title { margin: 0; font-size: 13.5px; font-weight: 700; color: var(--d-text); }
.day-head .link-btn { margin-left: auto; }
.day-value { margin: 8px 0 14px; font-size: 30px; font-weight: 800; color: var(--d-text); letter-spacing: -.035em; font-variant-numeric: tabular-nums; }
.split-bar { display: flex; gap: 3px; height: 10px; border-radius: 10px; overflow: hidden; }
.split-seg { display: block; height: 100%; cursor: help; transition: width 1s var(--d-ease); }
.split-seg--profit { background: linear-gradient(90deg, #059669, var(--d-green)); border-radius: 10px 3px 3px 10px; }
.split-seg--cost { background: linear-gradient(90deg, var(--d-accent), color-mix(in srgb, var(--d-accent) 75%, #fbbf24)); border-radius: 3px 10px 10px 3px; }
.split-legend { display: flex; gap: 16px; margin-top: 9px; font-size: 11.5px; color: var(--d-muted); }
.split-legend span { display: inline-flex; align-items: center; gap: 6px; }
.sl { width: 8px; height: 8px; border-radius: 50%; }
.sl--profit { background: var(--d-green); }
.sl--cost { background: var(--d-accent); }
.facts { margin: 16px 0 0; border-top: 1px solid var(--d-border); }
.facts > div { display: flex; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--d-border); }
.facts > div:last-child { border-bottom: none; padding-bottom: 0; }
.facts dt { font-size: 12.5px; color: var(--d-muted); }
.facts dd { margin: 0; font-size: 13px; font-weight: 700; color: var(--d-text); font-variant-numeric: tabular-nums; }

/* ── sales mix ── */
.mix { container-type: inline-size; display: flex; flex-direction: column; gap: 18px; }
.mix-insight { display: flex; align-items: flex-start; gap: 10px; margin: 0; padding: 12px 14px; border-radius: 12px; background: var(--d-accent-soft); border: 1px solid var(--d-accent-border); font-size: 12.5px; line-height: 1.55; color: var(--d-text-2); }
.mix-insight i { margin-top: 2px; font-size: 12px; color: var(--d-accent); }
.mix-body { display: grid; grid-template-columns: 200px minmax(0, 1fr); gap: 24px; align-items: center; }
@container (max-width: 520px) { .mix-body { grid-template-columns: minmax(0, 1fr); } .donut { margin: 0 auto; } }

.donut { position: relative; width: 200px; max-width: 100%; aspect-ratio: 1; }
.donut svg { display: block; width: 100%; height: 100%; overflow: visible; filter: drop-shadow(0 6px 14px rgba(16,24,40,.12)); }
.donut-track { fill: none; stroke: var(--d-sunken); stroke-width: 22; }
.donut-seg { fill: none; stroke-width: 22; stroke-linecap: butt; cursor: pointer; outline: none; transition: stroke-dasharray 1s var(--d-ease), stroke-width .2s ease, opacity .2s ease; }
.donut-seg.is-on { stroke-width: 28; }
.donut-seg.is-dim { opacity: .28; }
.donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; padding: 0 46px; text-align: center; pointer-events: none; }
.donut-center strong { font-size: 22px; font-weight: 800; color: var(--d-text); letter-spacing: -.03em; font-variant-numeric: tabular-nums; }
.dc-name { max-width: 100%; font-size: 12px; font-weight: 600; color: var(--d-text-2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dc-sub { font-size: 11px; color: var(--d-muted); line-height: 1.3; }

.mix-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.mix-list li { border: 1px solid transparent; border-radius: 12px; transition: background-color .2s ease, border-color .2s ease; }
.mix-list li.mix-item--sel { background: var(--d-accent-soft); border-color: var(--d-accent-border); }
.mix-row { display: flex; flex-direction: column; gap: 9px; width: 100%; padding: 11px 13px; border: none; border-radius: 12px; background: transparent; font: inherit; color: inherit; text-align: left; cursor: pointer; transition: background-color .2s ease; }
.mix-row:hover { background: var(--d-raised); }
.mix-row--on { background: var(--d-raised); }
.mix-top { display: flex; align-items: center; gap: 10px; min-width: 0; }
.mix-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 3px var(--d-surface); }
.mix-name { flex: 1; min-width: 0; font-size: 13px; font-weight: 600; color: var(--d-text); overflow-wrap: anywhere; }
.mix-amt { font-size: 12.5px; color: var(--d-muted); white-space: nowrap; font-variant-numeric: tabular-nums; }
.mix-pct { width: 48px; text-align: right; font-size: 12.5px; font-weight: 700; color: var(--d-text-2); font-variant-numeric: tabular-nums; }
.mix-bar { display: block; height: 6px; border-radius: 6px; background: var(--d-sunken); overflow: hidden; }
.mix-bar-fill { display: block; height: 100%; border-radius: 6px; transition: width 1s var(--d-ease); }
.mix-detail { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin: 0; padding: 2px 13px 13px 33px; }
.mix-detail dt { font-size: 11.5px; color: var(--d-muted); }
.mix-detail dd { margin: 2px 0 0; font-size: 13px; font-weight: 700; color: var(--d-text); font-variant-numeric: tabular-nums; }
.mix-link { align-self: flex-start; }

.empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; text-align: center; padding: 32px 12px; }
.empty-ico { width: 52px; height: 52px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--d-faint); background: var(--d-sunken); margin-bottom: 6px; }
.empty-title { margin: 0; font-size: 14px; font-weight: 700; color: var(--d-text-2); }
.empty-sub { margin: 0; font-size: 12.5px; color: var(--d-muted); max-width: 300px; line-height: 1.5; }

/* ── stock ── */
.tile-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.tile { display: block; text-decoration: none; background: var(--d-raised); border: 1px solid var(--d-border); border-radius: 14px; padding: 14px 16px 16px; transition: border-color .2s ease, transform .25s var(--d-ease), box-shadow .25s ease; }
.tile:hover { border-color: var(--d-accent-border); transform: translateY(-3px); box-shadow: 0 12px 24px -14px rgba(16,24,40,.28); }
/* Non-clickable version (roles without Product List access): no lift, default cursor */
.tile--static, .tile--static:hover { cursor: default; transform: none; box-shadow: none; border-color: var(--d-border); }
.tile--warn.tile--static:hover { border-color: rgba(245,158,11,.35); }
.tile--alert.tile--static:hover { border-color: rgba(244,63,94,.28); }
.tile-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.tile-ico { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: var(--d-violet); background: var(--d-violet-soft); }
.tile-go { font-size: 10px; color: var(--d-faint); opacity: 0; transform: translate(-3px, 3px); transition: opacity .2s ease, transform .2s var(--d-ease); }
.tile:hover .tile-go { opacity: 1; transform: translate(0, 0); }
.tile-label { margin: 0; font-size: 12.5px; font-weight: 600; color: var(--d-muted); }
.tile-value { margin: 4px 0 2px; font-size: 28px; font-weight: 800; color: var(--d-text); letter-spacing: -.03em; font-variant-numeric: tabular-nums; }
.tile-sub { margin: 0; font-size: 12px; color: var(--d-muted); }
.tile--warn { background: var(--d-amber-soft); border-color: rgba(245,158,11,.35); }
.tile--warn .tile-ico { color: var(--d-amber); background: rgba(245,158,11,.18); }
.tile--warn .tile-value, .tile--warn .tile-sub { color: #b45309; }
html[data-theme="dark"] .tile--warn .tile-value,
html[data-theme="dark"] .tile--warn .tile-sub { color: #FBBF24; }
.tile--alert { background: var(--d-red-soft); border-color: rgba(244,63,94,.28); }
.tile--alert .tile-ico { color: var(--d-red); background: rgba(244,63,94,.16); }
.tile--alert .tile-value, .tile--alert .tile-sub { color: var(--d-red); }
.coverage { margin-top: 16px; padding: 14px 16px; background: var(--d-raised); border: 1px solid var(--d-border); border-radius: 14px; }
.coverage-head { display: flex; justify-content: space-between; gap: 8px; font-size: 12.5px; color: var(--d-muted); margin-bottom: 10px; }
.coverage-head strong { color: var(--d-text); font-weight: 700; }
.link-list { display: flex; flex-direction: column; gap: 2px; margin-top: 12px; }
.link-list a { display: flex; align-items: center; gap: 12px; padding: 11px 10px; border-radius: 10px; font-size: 13px; font-weight: 600; color: var(--d-text-2); text-decoration: none; transition: background-color .15s ease, color .15s ease; }
.link-list a:hover { background: var(--d-raised); color: var(--d-accent); }
.link-list a i:first-child { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: var(--d-sunken); color: var(--d-muted); font-size: 11.5px; transition: background-color .15s ease, color .15s ease; }
.link-list a:hover i:first-child { background: var(--d-accent-soft); color: var(--d-accent); }
.link-list a i:last-child { margin-left: auto; font-size: 9px; color: var(--d-faint); transition: transform .2s var(--d-ease); }
.link-list a:hover i:last-child { transform: translateX(3px); color: var(--d-accent); }

/* ── skeleton ── */
.sk-card { display: flex; flex-direction: column; gap: 16px; animation: none; }
.sk { background: linear-gradient(90deg, var(--d-sunken) 25%, var(--d-raised) 50%, var(--d-sunken) 75%); background-size: 200% 100%; animation: sk 1.4s ease-in-out infinite; border-radius: 10px; }
@keyframes sk { to { background-position: -200% 0; } }
.sk-line { height: 14px; width: 35%; }
.sk-big { height: 44px; width: 55%; }
.sk-bar { height: 10px; border-radius: 10px; }
.sk-block { height: 90px; border-radius: 14px; }
.sk-tall { height: 240px; }

/* ── responsive ── */
/* Desktop only: pull the page outward into the shell's padding (top, left, right) */
@media (min-width: 1001px) {
  .dash { margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) 0; }
}
@media (max-width: 1000px) { .row-two, .cal-layout { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 900px) { .dash { padding: 12px 14px 32px; --d-gap: 14px; } }
@media (max-width: 600px) {
  .card { padding: 18px 16px; border-radius: 16px; }
  .page-header { flex-direction: column; align-items: stretch; }
  .page-title { font-size: 24px; }
  .notice { flex-wrap: wrap; }
  .mini-stats, .mini-stats--3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .spark { align-items: flex-start; }
  .day { padding: 16px; }
  .tile-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (prefers-reduced-motion: reduce) {
  .dash * { transition: none !important; animation: none !important; }
  .spark-line { stroke-dashoffset: 0; }
  .spark-area { opacity: 1; }
}
</style>