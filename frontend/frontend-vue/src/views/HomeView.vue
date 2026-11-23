<template>
  <Layout title="Home">
    <div class="dash">

      <!-- HEADER -->
      <div class="page-header">
        <div class="page-header-l">
          <h1 class="page-title">Dashboard</h1>
        </div>
        <div v-if="canTransactions" class="page-header-right">
          <router-link :to="{ name: 'sales-transactions' }" class="btn"><i class="fa-solid fa-receipt"></i>Transactions</router-link>
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
                  <span class="kpi-trend" :class="todayTrend.direction" tabindex="0" v-bind="tipBind(() => todayTrendTip())"><i :class="trendIcon(todayTrend)"></i>{{ todayTrend.text }}</span>
                  <span>vs yesterday</span>
                </p>
              </div>

              <!-- Decorative only: intentionally not interactive -->
              <div v-if="spark" class="spark" role="img" aria-label="Sales over the last 14 days">
                <svg viewBox="0 0 120 40" preserveAspectRatio="none" aria-hidden="true">
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
              <div class="goal-track" role="progressbar" tabindex="0" :aria-valuenow="Math.min(goalPct, 100)" aria-valuemin="0" aria-valuemax="100" v-bind="tipBind(() => goalTip())">
                <div class="goal-fill" :class="{ 'goal-fill--done': goalPct >= 100 }" :style="{ width: goalBarPct + '%' }"></div>
              </div>
              <p class="goal-sub">{{ goalText }}</p>
            </div>

            <dl class="mini-stats">
              <div class="ms" tabindex="0" v-bind="tipBind(() => statTip('tx'))"><dt><i class="fa-solid fa-receipt"></i>Transactions</dt><dd>{{ formatNumber(anim.tx) }}</dd></div>
              <div class="ms" tabindex="0" v-bind="tipBind(() => statTip('items'))"><dt><i class="fa-solid fa-box-open"></i>Items sold</dt><dd>{{ formatNumber(anim.items) }}</dd></div>
              <div class="ms" tabindex="0" v-bind="tipBind(() => statTip('avg'))"><dt><i class="fa-solid fa-tag"></i>Average sale</dt><dd>₱{{ price(anim.avg) }}</dd></div>
              <div class="ms" tabindex="0" v-bind="tipBind(() => statTip('todayProfit'))"><dt><i class="fa-solid fa-arrow-trend-up"></i>Profit</dt><dd class="green">₱{{ price(anim.todayProfit) }}</dd></div>
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
              <span class="kpi-trend" :class="monthBeat ? 'up' : 'flat'" tabindex="0" v-bind="tipBind(() => monthTrendTip())"><i :class="monthBeat ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-flag-checkered'"></i>{{ monthBeat ? 'Beat last month' : '₱' + price(monthGap) + ' to go' }}</span>
              <span>{{ monthBeat ? `last month in full was ₱${price(lastMonthTotal)}` : `to match last month in full (₱${price(lastMonthTotal)})` }}</span>
            </p>

            <div class="goal">
              <div class="goal-head"><span>Month progress</span><strong>{{ Math.round(monthProgress) }}%</strong></div>
              <div class="goal-track" role="progressbar" tabindex="0" :aria-valuenow="Math.round(monthProgress)" aria-valuemin="0" aria-valuemax="100" v-bind="tipBind(() => monthTip())">
                <div class="goal-fill goal-fill--blue" :style="{ width: monthBarPct + '%' }"></div>
              </div>
            </div>

            <dl class="mini-stats mini-stats--3">
              <div class="ms" tabindex="0" v-bind="tipBind(() => statTip('profit'))"><dt><i class="fa-solid fa-arrow-trend-up"></i>Profit</dt><dd class="green">₱{{ price(anim.profit) }}</dd></div>
              <div class="ms" tabindex="0" v-bind="tipBind(() => statTip('margin'))"><dt><i class="fa-solid fa-percent"></i>Margin</dt><dd>{{ anim.margin.toFixed(1) }}%</dd></div>
              <div class="ms" tabindex="0" v-bind="tipBind(() => statTip('best'))">
                <dt><i class="fa-solid fa-trophy"></i>Best day</dt>
                <dd>{{ bestDay ? '₱' + price(bestDay.total) : '—' }}</dd>
                <span class="ms-sub">{{ bestDay ? bestDayLabel : 'No sales yet' }}</span>
              </div>
            </dl>

          </section>
        </div>

        <!-- BUSIEST DAYS HEATMAP -->
        <section class="card" style="--d: 140ms" aria-labelledby="hm-cal">
          <header class="card-header">
            <div class="card-head-l">
              <span class="card-icon card-icon--violet"><i class="fa-solid fa-table-cells"></i></span>
              <div>
                <h2 id="hm-cal" class="card-title">Busiest days in {{ monthName }}</h2>
                <p class="card-sub">Darker days had {{ calMetric === 'tx' ? 'more transactions' : 'higher sales' }}. Hover or tap a day for its numbers.</p>
              </div>
            </div>
            <div class="seg" role="group" aria-label="Calendar metric">
              <button type="button" class="seg-btn" :class="{ 'seg-btn--active': calMetric === 'total' }" :aria-pressed="calMetric === 'total'" @click="calMetric = 'total'">Sales</button>
              <button type="button" class="seg-btn" :class="{ 'seg-btn--active': calMetric === 'tx' }" :aria-pressed="calMetric === 'tx'" @click="calMetric = 'tx'">Transactions</button>
            </div>
          </header>

          <div class="cal-layout">
            <div class="cal" :class="{ 'cal--tx': calMetric === 'tx' }">
              <span v-for="(w, i) in weekdays" :key="'w' + i" class="cal-dow">{{ w }}</span>
              <template v-for="(c, i) in calendar" :key="i">
                <span v-if="!c" class="cal-blank"></span>
                <button v-else type="button" class="cal-day" :style="{ '--i': i }" :class="[`lv${c.level}`, { 'cal-day--sel': c.key === activeRow.key, 'cal-day--today': c.key === todayKey, 'cal-day--peak': c.key === peakKey }]" :disabled="c.future" :aria-pressed="c.key === activeRow.key" :aria-label="`${c.d}: ${calValueText(c.v)}`" v-bind="tipBind(() => dayTip(c.key))" @click="selectDay(c.key)">{{ c.d }}</button>
              </template>
              <div class="cal-scale"><span>{{ calMetric === 'tx' ? 'Quieter' : 'Less' }}</span><i class="lv0"></i><i class="lv1"></i><i class="lv2"></i><i class="lv3"></i><i class="lv4"></i><span>{{ calMetric === 'tx' ? 'Busier' : 'More' }}</span></div>
            </div>

            <div class="day" aria-live="polite">
              <div class="day-head">
                <h3 class="day-title">{{ activeLabel }}</h3>
                <span v-if="activeIsToday" class="chip">Today</span>
                <span v-if="activeIsBusiest" class="chip chip--live">Busiest day</span>
                <button v-if="!activeIsToday" type="button" class="link-btn" @click="backToToday">Back to today</button>
              </div>
              <p class="day-value">{{ calMetric === 'tx' ? formatNumber(activeRow.tx) + (activeRow.tx === 1 ? ' transaction' : ' transactions') : '₱' + price(anim.day) }}</p>
              <div v-if="activeRow.total > 0" class="split-wrap">
                <div class="split-bar" role="img" :aria-label="`Profit ${activeMargin} percent of sales`">
                  <span class="split-seg split-seg--profit" tabindex="0" :style="{ width: (splitReady ? activeProfitPct : 0) + '%' }" v-bind="tipBind(() => splitTip('profit'))"></span>
                  <span class="split-seg split-seg--cost" tabindex="0" :style="{ width: (splitReady ? 100 - activeProfitPct : 0) + '%' }" v-bind="tipBind(() => splitTip('cost'))"></span>
                </div>
                <div class="split-legend"><span><i class="sl sl--profit"></i>Profit</span><span><i class="sl sl--cost"></i>Cost of goods</span></div>
              </div>
              <p v-else class="muted">No sales on this day.</p>
              <dl class="facts">
                <div><dt>Transactions</dt><dd>{{ formatNumber(activeRow.tx) }}</dd></div>
                <div><dt>Sales</dt><dd>₱{{ price(activeRow.total) }}</dd></div>
                <div><dt>Profit</dt><dd class="green">₱{{ price(activeRow.profit) }}</dd></div>
                <div><dt>Cost of goods</dt><dd>₱{{ price(activeCost) }}</dd></div>
                <div><dt>Margin</dt><dd>{{ activeMargin }}%</dd></div>
                <div><dt>Share of month {{ calMetric === 'tx' ? 'transactions' : 'sales' }}</dt><dd>{{ activeShare }}%</dd></div>
                <div><dt>Vs daily average</dt><dd :class="activeVsAvg >= 0 ? 'green' : 'red'">{{ activeVsAvg >= 0 ? '+' : '' }}{{ activeVsAvg.toFixed(1) }}%</dd></div>
              </dl>
            </div>
          </div>
        </section>

        <!-- SALES MIX + STOCK -->
        <div class="row-two">

          <section class="card card--mix" style="--d: 210ms" aria-labelledby="hm-mix">
            <header class="card-header">
              <div class="card-head-l">
                <span class="card-icon card-icon--green"><i class="fa-solid fa-chart-pie"></i></span>
                <div>
                  <h2 id="hm-mix" class="card-title">Where sales come from</h2>
                  <p class="card-sub">Share of {{ monthName }} {{ mixNoun }} by top {{ MIX_LIMIT }} products</p>
                </div>
              </div>
              <div class="seg" role="group" aria-label="Measure by">
                <button type="button" class="seg-btn" :class="{ 'seg-btn--active': mixMetric === 'sales' }" :aria-pressed="mixMetric === 'sales'" @click="setMixMetric('sales')">By sales</button>
                <button type="button" class="seg-btn" :class="{ 'seg-btn--active': mixMetric === 'profit' }" :aria-pressed="mixMetric === 'profit'" @click="setMixMetric('profit')">By profit</button>
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
                      v-bind="tipBind(() => mixTip(mixRows[i]), { tap: false, pointer: true })"
                      @mouseenter="hoverIdx = i" @mouseleave="hoverIdx = null"
                      @focus="hoverIdx = i" @blur="hoverIdx = null"
                      @click="pickMix(i)" @keydown.enter.prevent="pickMix(i)" @keydown.space.prevent="pickMix(i)"
                    />
                  </svg>
                  <div class="donut-center" aria-live="polite">
                    <template v-if="focusRow">
                      <strong>{{ focusRow.pct.toFixed(1) }}%</strong>
                      <span class="dc-name">{{ focusRow.name }}</span>
                      <span class="dc-sub">{{ mixValueText(focusRow) }}</span>
                    </template>
                    <template v-else>
                      <strong>{{ mixMetric === 'qty' ? formatNumber(mixTotal) : '₱' + compact(mixTotal) }}</strong>
                      <span class="dc-name">{{ mixTotalLabel }}</span>
                      <span class="dc-sub">Hover or select a slice</span>
                    </template>
                  </div>
                </div>

                <ul class="mix-list">
                  <li v-for="(r, i) in mixRows" :key="r.name + i" :class="{ 'mix-item--sel': mixIdx === i }">
                    <button type="button" class="mix-row" :class="{ 'mix-row--on': focusIdx === i && mixIdx !== i }" :aria-pressed="mixIdx === i" v-bind="tipBind(() => mixTip(r), { tap: false })" @click="pickMix(i)" @mouseenter="hoverIdx = i" @mouseleave="hoverIdx = null" @focus="hoverIdx = i" @blur="hoverIdx = null">
                      <span class="mix-top">
                        <i class="mix-dot" :style="{ background: r.color }"></i>
                        <span class="mix-name">{{ r.name }}</span>
                        <span class="mix-amt">{{ mixValueText(r) }}</span>
                        <span class="mix-pct">{{ r.pct.toFixed(1) }}%</span>
                      </span>
                      <span class="mix-bar"><span class="mix-bar-fill" :style="{ width: (mixReady ? r.pct : 0) + '%', background: r.color }"></span></span>
                    </button>
                    <dl v-if="mixIdx === i" class="mix-detail">
                      <div><dt>Sales</dt><dd>₱{{ price(r.sales) }}</dd></div>
                      <div v-if="r.profit !== null"><dt>Profit</dt><dd>₱{{ price(r.profit) }}</dd></div>
                      <template v-if="r.qty !== null">
                        <div><dt>Margin</dt><dd>{{ r.sales ? ((r.profit / r.sales) * 100).toFixed(1) : '0.0' }}%</dd></div>
                        <div><dt>Units sold</dt><dd>{{ formatNumber(r.qty) }}</dd></div>
                        <div><dt>Average price</dt><dd>₱{{ price(r.qty ? r.sales / r.qty : 0) }}</dd></div>
                      </template>
                      <div v-else><dt>Includes</dt><dd>All other products</dd></div>
                    </dl>
                  </li>
                </ul>
              </div>

              <!-- Full Reports shortcut: Administrators only.
                   Pinned to the bottom of the card so any extra height in the
                   row never shows up as an empty gap. -->
              <footer v-if="canReports" class="mix-foot">
                <router-link :to="{ name: 'reports' }" class="link-btn">See the full ranking in Reports<i class="fa-solid fa-arrow-right"></i></router-link>
              </footer>
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
                 for everyone else they render as plain tiles that show a short
                 explanation when clicked. -->
            <div class="tile-grid">
              <component :is="canProducts ? 'router-link' : 'div'" v-bind="tileProps(null, () => tileTip('hand'))" class="tile tile--first" :class="{ 'tile--static': !canProducts }">
                <span class="tile-top"><span class="tile-ico"><i class="fa-solid fa-cubes"></i></span><i v-if="canProducts" class="fa-solid fa-arrow-up-right-from-square tile-go"></i></span>
                <p class="tile-label">In hand</p>
                <p class="tile-value">{{ formatNumber(anim.inHand) }}</p>
                <p class="tile-sub">{{ formatNumber(inventorySummary.quantity_to_receive) }} incoming</p>
              </component>
              <component :is="canProducts ? 'router-link' : 'div'" v-bind="tileProps({ stockFilter: 'low' }, () => tileTip('low'))" class="tile" :class="{ 'tile--warn': lowStockCount > 0, 'tile--static': !canProducts }">
                <span class="tile-top"><span class="tile-ico"><i class="fa-solid fa-triangle-exclamation"></i></span><i v-if="canProducts" class="fa-solid fa-arrow-up-right-from-square tile-go"></i></span>
                <p class="tile-label">Low stock</p>
                <p class="tile-value">{{ formatNumber(anim.low) }}</p>
                <p class="tile-sub">{{ lowStockCount > 0 ? 'Reorder soon' : 'Nothing running low' }}</p>
              </component>
              <component :is="canProducts ? 'router-link' : 'div'" v-bind="tileProps({ stockFilter: 'out' }, () => tileTip('out'))" class="tile" :class="{ 'tile--alert': outOfStockCount > 0, 'tile--static': !canProducts }">
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
              <div class="goal-track" role="progressbar" tabindex="0" :aria-valuenow="stockCoverage.pct" aria-valuemin="0" aria-valuemax="100" v-bind="tipBind(() => coverageTip())">
                <div class="goal-fill goal-fill--done" :style="{ width: (barsReady ? stockCoverage.pct : 0) + '%' }"></div>
              </div>
            </div>

            <div v-if="canProducts || canInvLogs || canSuppliers" class="link-list">
              <router-link v-if="canProducts"  :to="{ name: 'products' }"><i class="fa-solid fa-boxes-stacked"></i>Manage products<i class="fa-solid fa-chevron-right"></i></router-link>
              <router-link v-if="canInvLogs"   :to="{ name: 'inventory-logs' }"><i class="fa-solid fa-clock-rotate-left"></i>Inventory logs<i class="fa-solid fa-chevron-right"></i></router-link>
              <router-link v-if="canSuppliers" :to="{ name: 'suppliers' }"><i class="fa-solid fa-truck"></i>Suppliers<i class="fa-solid fa-chevron-right"></i></router-link>
            </div>
          </section>
        </div>

      </div>

      <!-- SHARED TOOLTIP: one floating card reused by every bar, chart and stat.
           Appears on hover (desktop), on tap (touch) and on keyboard focus, placed
           neatly above the element it describes. -->
      <div
        class="tip"
        :class="{ 'tip--on': tip.show, 'tip--below': tip.below }"
        :style="{ left: tip.x + 'px', top: tip.y + 'px' }"
        role="tooltip"
        aria-hidden="true"
      >
        <p class="tip-title">
          <i v-if="tip.content.color" class="tip-dot" :style="{ background: tip.content.color }"></i>
          <span>{{ tip.content.title }}</span>
        </p>
        <dl v-if="tip.content.rows && tip.content.rows.length" class="tip-rows">
          <div v-for="(r, i) in tip.content.rows" :key="i" class="tip-row">
            <dt>{{ r.k }}</dt>
            <dd :class="r.tone ? 'tip-' + r.tone : ''">{{ r.v }}</dd>
          </div>
        </dl>
        <p v-if="tip.content.note" class="tip-note">{{ tip.content.note }}</p>
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

// How many named products the "Where sales come from" card shows.
// Everything else is grouped into one grey "Other items" slice.
const MIX_LIMIT = 3

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches

export default {
  name: 'HomeView',
  components: { Layout },

  data() {
    return {
      MIX_LIMIT,
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
      calMetric: 'total', // heatmap shading: 'total' (sales) | 'tx' (transactions)
      selectedKey: null,
      weekdays: ['S', 'M', 'T', 'W', 'T', 'F', 'S'],

      mixMetric: 'sales', // donut measure: 'sales' | 'profit' | 'qty'
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

      // Shared tooltip state
      tip: { show: false, x: 0, y: 0, below: false, content: { title: '', rows: [], note: '', color: '' } },

      firstLoad: true,
      loading: false,
      loadError: '',
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

    /* days (sales, profit and number of transactions per day) */
    monthRows() {
      const profitMap = {}
      this.dailyProfit.forEach((p) => { profitMap[String(p.date || '').slice(0, 10)] = this.num(p.profit) })
      return this.monthlySales.map((s) => {
        const key = String(s.date || '').slice(0, 10)
        return { key, total: this.num(s.total), profit: profitMap[key] ?? 0, tx: this.num(s.transactions) }
      })
    },
    /* Average of all days that had sales this month. */
    monthAvg() {
      const rows = this.monthRows.filter((r) => r.total > 0)
      return rows.length ? rows.reduce((a, r) => a + r.total, 0) / rows.length : 0
    },
    /* Average of the heatmap's active metric (sales or transactions) over days that had any. */
    calAvg() {
      const k = this.calMetric
      const rows = this.monthRows.filter((r) => r[k] > 0)
      return rows.length ? rows.reduce((a, r) => a + r[k], 0) / rows.length : 0
    },
    /* Busiest day by the active heatmap metric (gets the ★). */
    peakKey() {
      const k = this.calMetric
      let best = null
      for (const r of this.monthRows) {
        if (r[k] > 0 && (!best || r[k] > best[k])) best = r
      }
      return best ? best.key : null
    },

    /* sparkline — daily sales for up to the last 14 days of this month (design only) */
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
      return this.monthRows.find((r) => r.key === key) || { key, total: 0, profit: 0, tx: 0 }
    },
    activeIsToday() { return this.activeRow.key === this.todayKey },
    activeIsBusiest() {
      const k = this.calMetric
      return this.peakKey === this.activeRow.key && this.monthRows.filter((r) => r[k] > 0).length > 1
    },
    activeLabel() { return new Date(this.activeRow.key + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }) },
    activeCost() { return Math.max(this.activeRow.total - this.activeRow.profit, 0) },
    activeMargin() { const r = this.activeRow; return r.total ? ((r.profit / r.total) * 100).toFixed(1) : '0.0' },
    activeProfitPct() { const r = this.activeRow; return r.total ? Math.min(100, Math.max(0, (r.profit / r.total) * 100)) : 0 },
    /* Share of the month for whichever metric the heatmap is showing. */
    activeShare() {
      const k = this.calMetric
      const t = this.monthRows.reduce((a, r) => a + r[k], 0)
      return t ? ((this.activeRow[k] / t) * 100).toFixed(1) : '0.0'
    },
    activeVsAvg() {
      const avg = this.calAvg
      return avg ? ((this.activeRow[this.calMetric] - avg) / avg) * 100 : 0
    },

    /* sales mix (top MIX_LIMIT products by the chosen measure + one grey "Other items" slice) */
    mixRows() {
      const palette = ['#EA580C', '#6366F1', '#10B981']
      const m = this.mixMetric
      const val = (r) => (m === 'qty' ? r.qty : m === 'profit' ? r.profit : r.sales)
      const items = this.topSellingItems
        .map((i) => ({
          name: i.ProductName,
          sales: this.num(i.total_sales),
          qty: this.num(i.total_quantity),
          profit: this.num(i.total_profit),
        }))
        // A product sold at a loss has no share of the profit, so it stays out of that view.
        .filter((r) => m !== 'profit' || r.profit > 0)
        .sort((a, b) => val(b) - val(a))
        .slice(0, MIX_LIMIT)
        .map((r, idx) => ({ ...r, color: palette[idx], rank: idx + 1 }))

      if (m !== 'qty') {
        // Whatever the top products don't explain (other products + the shop's share of services).
        const otherSales = Math.max(this.num(this.monthlyTotal) - items.reduce((a, r) => a + r.sales, 0), 0)
        const otherProfit = Math.max(this.num(this.totalProfit) - items.reduce((a, r) => a + r.profit, 0), 0)
        const otherVal = m === 'profit' ? otherProfit : otherSales
        if (otherVal > 0.5) items.push({ name: 'Other items', sales: otherSales, profit: otherProfit, qty: null, color: '#94A3B8', rank: null })
      }
      const base = items.reduce((a, r) => a + val(r), 0) || 1
      return items.map((r) => ({ ...r, pct: (val(r) / base) * 100 }))
    },
    hasMix() { return this.mixRows.some((r) => r.pct > 0) },
    mixTotal() { return this.mixRows.reduce((a, r) => a + this.mixVal(r), 0) },
    mixNoun() { return { sales: 'sales', qty: 'units sold', profit: 'profit' }[this.mixMetric] },
    mixTotalLabel() { return { sales: 'Total sales', qty: 'Units sold', profit: 'Total profit' }[this.mixMetric] },
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
      if (this.mixMetric === 'qty') return `${lead.name} is ${lead.pct.toFixed(1)}% of the units sold among your top products.`
      if (this.mixMetric === 'profit') return `${lead.name} earns ${lead.pct.toFixed(1)}% of ${this.monthName} profit.`
      return `${lead.name} brings in ${lead.pct.toFixed(1)}% of ${this.monthName} sales.`
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

    // Tooltip housekeeping: close when tapping elsewhere or scrolling.
    this._onDocPointer = (e) => {
      const t = e.target
      if (!(t && t.closest && t.closest('[data-dash-tip]'))) this.tipClose()
    }
    this._onScroll = () => this.tipClose()
    document.addEventListener('pointerdown', this._onDocPointer, true)
    window.addEventListener('scroll', this._onScroll, { passive: true, capture: true })

    this.loadDashboard()
  },

  beforeUnmount() {
    this.requestSeq++
    cancelAnimationFrame(this.raf)
    cancelAnimationFrame(this.dayRaf)
    document.removeEventListener('pointerdown', this._onDocPointer, true)
    window.removeEventListener('scroll', this._onScroll, { passive: true, capture: true })
  },

  methods: {
    num(v) { const n = Number(v); return Number.isFinite(n) ? n : 0 },
    formatNumber(n) { return Math.round(this.num(n)).toLocaleString('en-US') },
    price(v) { return this.num(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
    money(v) { return '₱' + this.price(v) },
    compact(v) { return Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 }).format(this.num(v)) },

    /* Heatmap + donut value helpers */
    calValueText(v) { return this.calMetric === 'tx' ? `${this.formatNumber(v)} transactions` : this.money(v) },
    mixVal(r) { return this.mixMetric === 'qty' ? r.qty : this.mixMetric === 'profit' ? r.profit : r.sales },
    mixValueText(r) { return this.mixMetric === 'qty' ? this.formatNumber(r.qty) + ' units' : this.money(this.mixVal(r)) },

    /* Props for a stock tile: a router-link target when the role may open
       the Product List, nothing (so it stays a plain div) when it may not.
       Tooltip handlers are merged in here because an element can only take
       one v-bind object. */
    tileProps(query, getter) {
      const link = this.canProducts ? { to: { name: 'products', ...(query ? { query } : {}) } } : {}
      return Object.assign({}, link, getter ? this.tipBind(getter, { tap: false }) : {})
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

    /* ── TOOLTIP ENGINE (hover to open) ─────────────────────
       tipBind(getter) returns the event handlers to spread onto any element
       with v-bind. `getter` builds the tooltip content lazily, only when the
       tooltip is actually shown, so it always reflects the latest data.
         • mouse / pen : shows on hover, anchored neatly above the element
                         (flips below it when there is no room above)
         • touch       : shows on tap, hides after a few seconds or on next tap
         • keyboard    : shows on focus (only for keyboard focus)
       Options:
         { pointer: true } anchor to the cursor instead of the element
                           (used for donut slices, whose box is the whole ring)
         { tap: false }    skip the tap behaviour (element already does
                           something on tap, e.g. a link)
       The attribute is named data-dash-tip on purpose: a plain `data-tip`
       is picked up by other tooltip scripts and produced a duplicate popup. */
    tipBind(getter, opts = {}) {
      const tap = opts.tap !== false
      const pointer = opts.pointer === true
      return {
        'data-dash-tip': '',
        onPointerenter: (e) => this.tipShow(e, getter, pointer),
        onPointermove: (e) => { if (pointer) this.tipMove(e) },
        onPointerleave: (e) => this.tipHide(e),
        onFocus: (e) => this.tipFocus(e, getter),
        onBlur: () => this.tipClose(),
        onClick: (e) => { if (tap) this.tipTap(e, getter, pointer) },
      }
    },
    tipClampX(x) {
      const vw = window.innerWidth || 360
      const half = 128
      return Math.min(Math.max(x, half + 6), Math.max(vw - half - 6, half + 6))
    },
    /* Centered above the element, or below it when it sits near the top. */
    tipAnchor(el) {
      if (!el || !el.getBoundingClientRect) return
      const r = el.getBoundingClientRect()
      this.tip.below = r.top < 190
      this.tip.x = this.tipClampX(r.left + r.width / 2)
      this.tip.y = this.tip.below ? r.bottom + 10 : r.top - 10
    },
    /* Follows the cursor (above it, or below when near the top). */
    tipPointer(x, y) {
      this.tip.below = y < 170
      this.tip.x = this.tipClampX(x)
      this.tip.y = this.tip.below ? y + 20 : y - 14
    },
    tipSet(getter) {
      const c = getter() || {}
      this.tip.content = { title: c.title || '', rows: c.rows || [], note: c.note || '', color: c.color || '' }
      this.tip.show = true
    },
    tipShow(e, getter, pointer) {
      if (e.pointerType === 'touch') return
      clearTimeout(this._tipTimer)
      this.tipSet(getter)
      if (pointer) this.tipPointer(e.clientX, e.clientY)
      else this.tipAnchor(e.currentTarget)
    },
    tipMove(e) {
      if (e.pointerType === 'touch' || !this.tip.show) return
      this.tipPointer(e.clientX, e.clientY)
    },
    tipHide(e) {
      if (e && e.pointerType === 'touch') return
      this.tipClose()
    },
    tipClose() {
      clearTimeout(this._tipTimer)
      this.tip.show = false
    },
    tipFocus(e, getter) {
      const el = e.currentTarget
      if (!el || !el.matches || !el.matches(':focus-visible')) return
      this.tipSet(getter)
      this.tipAnchor(el)
    },
    tipTap(e, getter, pointer) {
      if (e.pointerType !== 'touch' && e.pointerType !== 'pen') return
      clearTimeout(this._tipTimer)
      this.tipSet(getter)
      if (pointer) this.tipPointer(e.clientX, e.clientY)
      else this.tipAnchor(e.currentTarget)
      this._tipTimer = setTimeout(() => this.tipClose(), 3200)
    },

    /* ── TOOLTIP CONTENT ─────────────────────────────────── */
    goalTip() {
      const t = this.target
      const labels = { yesterday: 'Yesterday', average: 'Daily average', custom: 'Custom target' }
      const rows = [{ k: 'Sales today', v: this.money(this.todaySales) }]
      if (t > 0) {
        const diff = this.num(this.todaySales) - t
        rows.push({ k: labels[this.targetMode], v: this.money(t) })
        rows.push({ k: 'Progress', v: this.goalPct + '%' })
        rows.push({ k: diff >= 0 ? 'Past target' : 'Still needed', v: this.money(Math.abs(diff)), tone: diff >= 0 ? 'good' : 'warn' })
      } else {
        rows.push({ k: 'Target', v: 'Not set' })
      }
      return { title: 'Daily target', rows }
    },
    monthTip() {
      const avg = this.dayOfMonth ? this.num(this.monthlyTotal) / this.dayOfMonth : 0
      const rows = [
        { k: 'Days passed', v: `${this.dayOfMonth} of ${this.daysInMonth}` },
        { k: 'Days left', v: String(this.daysInMonth - this.dayOfMonth) },
        { k: 'Sales so far', v: this.money(this.monthlyTotal) },
        { k: 'Average per day', v: this.money(avg) },
      ]
      return { title: `${this.monthName} progress`, rows, note: 'The bar shows how much of the month has passed.' }
    },
    todayTrendTip() {
      const diff = this.num(this.todaySales) - this.num(this.yesterdayTotal)
      return {
        title: 'Today vs yesterday',
        rows: [
          { k: 'Today', v: this.money(this.todaySales) },
          { k: 'Yesterday', v: this.money(this.yesterdayTotal) },
          { k: diff >= 0 ? 'Ahead by' : 'Behind by', v: this.money(Math.abs(diff)), tone: diff >= 0 ? 'good' : 'bad' },
        ],
      }
    },
    monthTrendTip() {
      const diff = this.num(this.monthlyTotal) - this.num(this.lastMonthTotal)
      return {
        title: 'This month vs last month',
        rows: [
          { k: `${this.monthName} so far`, v: this.money(this.monthlyTotal) },
          { k: 'Last month in full', v: this.money(this.lastMonthTotal) },
          { k: diff >= 0 ? 'Ahead by' : 'To go', v: this.money(Math.abs(diff)), tone: diff >= 0 ? 'good' : 'warn' },
        ],
      }
    },
    statTip(key) {
      const today = this.num(this.todaySales)
      const marginToday = today ? ((this.todaysProfit / today) * 100).toFixed(1) : '0.0'
      switch (key) {
        case 'tx':
          return { title: 'Transactions today', rows: [
            { k: 'Completed sales', v: this.formatNumber(this.transactionsToday) },
            { k: 'Items per sale', v: this.transactionsToday ? (this.num(this.itemsSoldToday) / this.num(this.transactionsToday)).toFixed(1) : '0.0' },
          ] }
        case 'items':
          return { title: 'Items sold today', rows: [
            { k: 'Units sold', v: this.formatNumber(this.itemsSoldToday) },
            { k: 'Transactions', v: this.formatNumber(this.transactionsToday) },
          ] }
        case 'avg':
          return { title: 'Average sale', note: 'Sales today divided by transactions.', rows: [
            { k: 'Average sale', v: this.money(this.avgTodaySale) },
            { k: 'Sales today', v: this.money(this.todaySales) },
            { k: 'Transactions', v: this.formatNumber(this.transactionsToday) },
          ] }
        case 'todayProfit':
          return { title: 'Profit today', rows: [
            { k: 'Profit', v: this.money(this.todaysProfit), tone: 'good' },
            { k: 'Sales', v: this.money(this.todaySales) },
            { k: 'Margin', v: marginToday + '%' },
          ] }
        case 'profit':
          return { title: `${this.monthName} profit`, rows: [
            { k: 'Profit', v: this.money(this.totalProfit), tone: 'good' },
            { k: 'Sales so far', v: this.money(this.monthlyTotal) },
            { k: 'Margin', v: this.monthMarginPct + '%' },
          ] }
        case 'margin':
          return { title: 'Profit margin', note: 'Profit divided by sales for the month so far.', rows: [
            { k: 'Margin', v: this.monthMarginPct + '%' },
            { k: 'Profit', v: this.money(this.totalProfit), tone: 'good' },
            { k: 'Sales', v: this.money(this.monthlyTotal) },
          ] }
        case 'best': {
          const d = this.bestDay
          if (!d) return { title: 'Best day', note: 'No sales recorded yet this month.' }
          const share = this.num(this.monthlyTotal) ? ((d.total / this.num(this.monthlyTotal)) * 100).toFixed(1) : '0.0'
          return { title: 'Best day of the month', rows: [
            { k: 'Date', v: this.bestDayLabel },
            { k: 'Sales', v: this.money(d.total) },
            { k: 'Profit', v: this.money(d.profit), tone: 'good' },
            { k: 'Share of month', v: share + '%' },
          ] }
        }
        default:
          return { title: '' }
      }
    },
    dayTip(key) {
      const r = this.monthRows.find((x) => x.key === key) || { total: 0, profit: 0, tx: 0 }
      const title = new Date(key + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' })
      if (!(r.total > 0 || r.tx > 0)) return { title, note: 'No sales on this day.' }
      const margin = r.total ? ((r.profit / r.total) * 100).toFixed(1) : '0.0'
      const avg = this.calAvg
      const vs = avg ? ((r[this.calMetric] - avg) / avg) * 100 : 0
      return { title, rows: [
        { k: 'Transactions', v: this.formatNumber(r.tx) },
        { k: 'Sales', v: this.money(r.total) },
        { k: 'Profit', v: this.money(r.profit), tone: 'good' },
        { k: 'Margin', v: margin + '%' },
        { k: 'Vs daily average', v: `${vs >= 0 ? '+' : ''}${vs.toFixed(1)}%`, tone: vs >= 0 ? 'good' : 'bad' },
      ] }
    },
    splitTip(kind) {
      const r = this.activeRow
      const isProfit = kind === 'profit'
      const value = isProfit ? r.profit : this.activeCost
      const pct = r.total ? (value / r.total) * 100 : 0
      return {
        title: isProfit ? 'Profit' : 'Cost of goods',
        color: isProfit ? '#10b981' : '#EA580C',
        rows: [
          { k: 'Amount', v: this.money(value) },
          { k: 'Share of sales', v: pct.toFixed(1) + '%' },
          { k: 'Total sales', v: this.money(r.total) },
        ],
      }
    },
    mixTip(r) {
      if (!r) return { title: '' }
      const shareLabel = { sales: 'Share of sales', qty: 'Share of units', profit: 'Share of profit' }[this.mixMetric]
      const rows = [{ k: shareLabel, v: r.pct.toFixed(1) + '%' }, { k: 'Sales', v: this.money(r.sales) }]
      if (r.profit !== null && r.profit !== undefined) {
        rows.push({ k: 'Profit', v: this.money(r.profit), tone: r.profit >= 0 ? 'good' : 'bad' })
      }
      if (r.qty !== null) {
        if (r.sales) rows.push({ k: 'Margin', v: ((r.profit / r.sales) * 100).toFixed(1) + '%' })
        rows.push({ k: 'Units sold', v: this.formatNumber(r.qty) })
        rows.push({ k: 'Average price', v: this.money(r.qty ? r.sales / r.qty : 0) })
      } else {
        rows.push({ k: 'Includes', v: 'All other products' })
      }
      if (r.rank) rows.unshift({ k: 'Rank', v: '#' + r.rank })
      return { title: r.name, color: r.color, rows }
    },
    coverageTip() {
      const c = this.stockCoverage
      if (!c) return { title: '' }
      return { title: 'Item coverage', note: 'Active items are the ones available to sell.', rows: [
        { k: 'Active', v: this.formatNumber(c.active), tone: 'good' },
        { k: 'Inactive', v: this.formatNumber(Math.max(c.total - c.active, 0)) },
        { k: 'Total items', v: this.formatNumber(c.total) },
        { k: 'Active share', v: c.pct + '%' },
      ] }
    },
    tileTip(kind) {
      const hint = this.canProducts ? 'Select to open the product list.' : ''
      if (kind === 'hand') {
        return { title: 'Stock in hand', note: hint, rows: [
          { k: 'On the shelf', v: this.formatNumber(this.inventorySummary.quantity_in_hand) },
          { k: 'Incoming', v: this.formatNumber(this.inventorySummary.quantity_to_receive) },
        ] }
      }
      if (kind === 'low') {
        return { title: 'Low stock', note: ('Still available, but at or below the reorder level. ' + hint).trim(), rows: [
          { k: 'Items', v: this.formatNumber(this.lowStockCount), tone: this.lowStockCount > 0 ? 'warn' : '' },
        ] }
      }
      return { title: 'Out of stock', note: ('Cannot be sold until restocked. ' + hint).trim(), rows: [
        { k: 'Items', v: this.formatNumber(this.outOfStockCount), tone: this.outOfStockCount > 0 ? 'bad' : '' },
      ] }
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
      this.tipClose()
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
     The app shell (Layout.vue) adds its own padding around every page.
     --bleed-x / --bleed-y let this page reclaim part of that padding
     (left, right and top) on EVERY screen size. Each breakpoint below
     sets its own values. Raise a value to push content closer to the
     edge; lower it (or set 0px) if content ever touches the sidebar,
     top bar or screen edge. */
  --bleed-x: 30px;
  --bleed-y: 26px;
  --d-gap: 16px;
  --d-pad-x: 24px;

  min-height: 100%; background: var(--d-bg);
  padding: 16px max(var(--d-pad-x), env(safe-area-inset-right)) 40px max(var(--d-pad-x), env(safe-area-inset-left));
  margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) 0;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--d-gap);
  -webkit-font-smoothing: antialiased;
  -webkit-tap-highlight-color: transparent;
}
html[data-theme="dark"] .dash {
  --d-green-soft: rgba(16,185,129,.16); --d-blue-soft: rgba(59,130,246,.18); --d-violet-soft: rgba(99,102,241,.2);
  --d-red-soft: rgba(244,63,94,.16); --d-amber-soft: rgba(245,158,11,.16);
  --d-shadow: 0 1px 2px rgba(0,0,0,.3), 0 12px 32px -14px rgba(0,0,0,.55);
  --d-shadow-hover: 0 2px 4px rgba(0,0,0,.35), 0 20px 44px -16px rgba(0,0,0,.7);
  --d-shine: rgba(255,255,255,.07);
}
.dash button:focus-visible, .dash a:focus-visible, .dash input:focus-visible, .dash [tabindex]:focus-visible { outline: 2px solid var(--d-accent); outline-offset: 2px; }
.dash-body { display: flex; flex-direction: column; gap: var(--d-gap); transition: opacity .2s ease; }
.dash-body.is-loading { opacity: .7; }
.green { color: var(--d-green); }
.red { color: var(--d-red); }
.muted { margin: 12px 0 0; font-size: 12.5px; color: var(--d-muted); }

/* ── header ── */
.page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.page-header-l { min-width: 0; }
.page-title { font-size: 26px; font-weight: 800; color: var(--d-text); letter-spacing: -.03em; margin: 0; line-height: 1.15; }
.page-header-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 16px;
  font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; text-decoration: none;
  color: var(--d-text-2); background: var(--d-surface); border: 1px solid var(--d-border-strong); border-radius: 10px;
  box-shadow: 0 1px 2px rgba(16,24,40,.05);
  transition: border-color .15s ease, color .15s ease, transform .15s ease, box-shadow .15s ease, filter .15s ease;
}
.btn:active:not(:disabled) { transform: translateY(0) scale(.98); }
.btn:disabled { opacity: .55; cursor: default; }
.btn i { font-size: 11px; }
.btn--primary { background: var(--d-accent); border-color: var(--d-accent); color: #fff; box-shadow: 0 1px 2px rgba(16,24,40,.12), inset 0 1px 0 rgba(255,255,255,.18); }

.link-btn { display: inline-flex; align-items: center; gap: 6px; border: none; background: transparent; cursor: pointer; font: inherit; font-size: 12.5px; font-weight: 700; color: var(--d-accent); padding: 5px 9px; border-radius: 8px; text-decoration: none; white-space: nowrap; transition: background-color .15s ease; }
.link-btn i { font-size: 9px; transition: transform .2s var(--d-ease); }

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
.chip { font-size: 10.5px; font-weight: 700; letter-spacing: .02em; color: var(--d-accent); background: var(--d-accent-soft); border: 1px solid var(--d-accent-border); padding: 2px 10px; border-radius: 20px; white-space: nowrap; }
.chip--live { color: var(--d-green); background: var(--d-green-soft); border-color: rgba(16,185,129,.28); }

/* ── segmented control ── */
.seg { display: inline-flex; gap: 2px; padding: 3px; background: var(--d-sunken); border: 1px solid var(--d-border); border-radius: 11px; }
.seg-btn { border: none; background: transparent; cursor: pointer; font: inherit; font-size: 12px; font-weight: 600; color: var(--d-muted); padding: 6px 12px; border-radius: 8px; white-space: nowrap; transition: background-color .2s ease, color .2s ease, box-shadow .2s ease; }
.seg-btn--active { background: var(--d-surface); color: var(--d-text); box-shadow: 0 1px 3px rgba(16,24,40,.16), 0 0 0 1px var(--d-border); }

/* ── trend + big numbers ── */
.kpi-trend { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; cursor: default; }
.kpi-trend i { font-size: 9.5px; }
.kpi-trend.up { color: var(--d-green); background: var(--d-green-soft); }
.kpi-trend.down { color: var(--d-red); background: var(--d-red-soft); }
.kpi-trend.flat { color: var(--d-muted); background: var(--d-raised); }
.trend-line { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 10px 0 0; font-size: 12.5px; color: var(--d-muted); }
.big-value { margin: 0; font-size: clamp(32px, 4.2vw, 46px); font-weight: 800; color: var(--d-text); letter-spacing: -.04em; line-height: 1.02; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
.big-value--sm { font-size: clamp(28px, 3.2vw, 36px); }

.hero-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
.hero-main { min-width: 0; }

/* sparkline (decorative only) */
.spark { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; pointer-events: none; user-select: none; }
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
/* The track is the click/tap target: padded a little so thin bars are easy to hit */
.goal-track { position: relative; height: 10px; border-radius: 10px; background: var(--d-sunken); box-shadow: inset 0 1px 2px rgba(16,24,40,.08); overflow: hidden; cursor: default; touch-action: manipulation; transition: box-shadow .2s ease, transform .2s var(--d-ease); }
.goal-track::after { content: ''; position: absolute; inset: -8px 0; } /* bigger invisible hit area (clipped by overflow, so keep track tall enough on touch) */
.goal-fill {
  height: 100%; border-radius: 10px;
  background: linear-gradient(90deg, var(--d-accent), color-mix(in srgb, var(--d-accent) 70%, #fbbf24));
  box-shadow: 0 0 12px -2px color-mix(in srgb, var(--d-accent) 60%, transparent);
  transition: width 1.1s var(--d-ease), filter .2s ease;
}
.goal-fill--done { background: linear-gradient(90deg, #059669, var(--d-green)); box-shadow: 0 0 12px -2px rgba(16,185,129,.55); }
.goal-fill--blue { background: linear-gradient(90deg, #2563eb, var(--d-blue)); box-shadow: 0 0 12px -2px rgba(59,130,246,.55); }
.goal-sub { margin: 8px 0 0; font-size: 12px; color: var(--d-muted); }

/* ── mini stats ── */
.mini-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(118px, 1fr)); gap: 10px; margin: 20px 0 0; }
.mini-stats--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.ms { padding: 12px 14px; background: var(--d-raised); border: 1px solid var(--d-border); border-radius: 12px; min-width: 0; cursor: default; transition: border-color .2s ease, transform .2s var(--d-ease); }
.mini-stats dt { display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 500; color: var(--d-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-stats dt i { font-size: 10px; color: var(--d-faint); }
.mini-stats dd { margin: 5px 0 0; font-size: 16px; font-weight: 700; color: var(--d-text); letter-spacing: -.01em; font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-stats dd.green { color: var(--d-green); }
.ms-sub { display: block; margin-top: 3px; font-size: 11.5px; color: var(--d-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── busiest days heatmap (sizes match the Reports page) ── */
.cal-layout { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); gap: 22px; align-items: start; }
.cal { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; --lv: 234,88,12; }
/* Transactions view is blue so it reads as a different measure from sales (orange) */
.cal--tx { --lv: 59,130,246; }
.cal-dow { text-align: center; font-size: 11px; font-weight: 600; letter-spacing: .04em; color: var(--d-faint); padding-bottom: 4px; }
.cal-day {
  aspect-ratio: 1.4; border: 1px solid transparent; border-radius: 10px; cursor: pointer;
  font: inherit; font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums; color: var(--d-text-2);
  touch-action: manipulation;
  transition: transform .15s var(--d-ease), border-color .15s ease, box-shadow .15s ease, background-color .3s ease;
  /* staggered entrance; `backwards` so it never overrides the hover transform afterwards */
  animation: cal-pop .5s var(--d-ease) backwards;
  animation-delay: calc(var(--i, 0) * 16ms + 200ms);
}
@keyframes cal-pop { from { opacity: 0; transform: scale(.6); } to { opacity: 1; transform: scale(1); } }
.cal-day:disabled { cursor: default; opacity: .35; }
.cal-day--today { border-color: var(--d-text-2); }
.cal-day--sel { border-color: rgb(var(--lv)); box-shadow: 0 0 0 3px rgba(var(--lv), .28); color: var(--d-text); }
/* ★ marks the busiest day for the metric that is selected */
.cal-day--peak { position: relative; }
.cal-day--peak::after { content: '★'; position: absolute; top: 3px; right: 5px; font-size: 10px; line-height: 1; color: currentColor; }
.lv0 { background: var(--d-sunken); }
.lv1 { background: rgba(var(--lv), .16); }
.lv2 { background: rgba(var(--lv), .34); }
.lv3 { background: rgba(var(--lv), .58); color: var(--d-text); }
.lv4 { background: rgba(var(--lv), .88); color: #fff; }
.cal-scale { grid-column: 1 / -1; display: flex; align-items: center; justify-content: flex-end; gap: 4px; margin-top: 8px; font-size: 11px; color: var(--d-muted); }
.cal-scale i { width: 14px; height: 14px; border-radius: 4px; }
.cal-scale span:first-child { margin-right: 3px; }
.cal-scale span:last-child { margin-left: 3px; }

.day { padding: 16px; background: var(--d-raised); border: 1px solid var(--d-border); border-radius: 16px; min-width: 0; }
.day-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; min-height: 28px; }
.day-title { margin: 0; font-size: 13.5px; font-weight: 700; color: var(--d-text); }
.day-head .link-btn { margin-left: auto; }
.day-value { margin: 6px 0 12px; font-size: 24px; font-weight: 800; color: var(--d-text); letter-spacing: -.035em; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
.split-bar { display: flex; gap: 3px; height: 10px; border-radius: 10px; overflow: hidden; }
.split-seg { display: block; height: 100%; cursor: default; transition: width 1s var(--d-ease), filter .2s ease; touch-action: manipulation; }
.split-seg--profit { background: linear-gradient(90deg, #059669, var(--d-green)); border-radius: 10px 3px 3px 10px; }
.split-seg--cost { background: linear-gradient(90deg, var(--d-accent), color-mix(in srgb, var(--d-accent) 75%, #fbbf24)); border-radius: 3px 10px 10px 3px; }
.split-legend { display: flex; gap: 16px; margin-top: 9px; font-size: 11.5px; color: var(--d-muted); }
.split-legend span { display: inline-flex; align-items: center; gap: 6px; }
.sl { width: 8px; height: 8px; border-radius: 50%; }
.sl--profit { background: var(--d-green); }
.sl--cost { background: var(--d-accent); }
.facts { margin: 12px 0 0; border-top: 1px solid var(--d-border); }
.facts > div { display: flex; justify-content: space-between; gap: 10px; padding: 7px 0; border-bottom: 1px solid var(--d-border); }
.facts > div:last-child { border-bottom: none; padding-bottom: 0; }
.facts dt { font-size: 12px; color: var(--d-muted); }
.facts dd { margin: 0; font-size: 12.5px; font-weight: 700; color: var(--d-text); font-variant-numeric: tabular-nums; text-align: right; }

/* ── sales mix ── */
/* The card is a column that fills the row height. The donut + list centre
   in whatever space is free and the Reports link is pinned to the bottom,
   so a taller neighbour (Needs attention) never leaves an empty gap. */
.card--mix { display: flex; flex-direction: column; }
.mix { container-type: inline-size; display: flex; flex-direction: column; gap: 18px; flex: 1 1 auto; }
.mix-insight { display: flex; align-items: flex-start; gap: 10px; margin: 0; padding: 12px 14px; border-radius: 12px; background: var(--d-accent-soft); border: 1px solid var(--d-accent-border); font-size: 12.5px; line-height: 1.55; color: var(--d-text-2); }
.mix-insight i { margin-top: 2px; font-size: 12px; color: var(--d-accent); }
.mix-body { flex: 1 1 auto; display: grid; grid-template-columns: 200px minmax(0, 1fr); gap: 24px; align-items: center; align-content: center; }
@container (max-width: 520px) { .mix-body { grid-template-columns: minmax(0, 1fr); } .donut { margin: 0 auto; width: min(240px, 100%); } }

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
.mix-row { display: flex; flex-direction: column; gap: 9px; width: 100%; padding: 11px 13px; border: none; border-radius: 12px; background: transparent; font: inherit; color: inherit; text-align: left; cursor: pointer; touch-action: manipulation; transition: background-color .2s ease; }
.mix-row--on { background: var(--d-raised); }
.mix-top { display: flex; align-items: center; gap: 10px; min-width: 0; }
.mix-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 3px var(--d-surface); }
.mix-name { flex: 1; min-width: 0; font-size: 13px; font-weight: 600; color: var(--d-text); overflow-wrap: anywhere; }
.mix-amt { font-size: 12.5px; color: var(--d-muted); white-space: nowrap; font-variant-numeric: tabular-nums; }
.mix-pct { width: 48px; text-align: right; font-size: 12.5px; font-weight: 700; color: var(--d-text-2); font-variant-numeric: tabular-nums; }
.mix-bar { display: block; height: 6px; border-radius: 6px; background: var(--d-sunken); overflow: hidden; }
.mix-bar-fill { display: block; height: 100%; border-radius: 6px; transition: width 1s var(--d-ease); }
.mix-detail { display: grid; grid-template-columns: repeat(auto-fit, minmax(92px, 1fr)); gap: 10px; margin: 0; padding: 2px 13px 13px 33px; }
.mix-detail dt { font-size: 11.5px; color: var(--d-muted); }
.mix-detail dd { margin: 2px 0 0; font-size: 13px; font-weight: 700; color: var(--d-text); font-variant-numeric: tabular-nums; }
.mix-foot { margin-top: auto; padding-top: 14px; border-top: 1px solid var(--d-border); }

.empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; text-align: center; padding: 32px 12px; }
.card--mix .empty { flex: 1 1 auto; }
.empty-ico { width: 52px; height: 52px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--d-faint); background: var(--d-sunken); margin-bottom: 6px; }
.empty-title { margin: 0; font-size: 14px; font-weight: 700; color: var(--d-text-2); }
.empty-sub { margin: 0; font-size: 12.5px; color: var(--d-muted); max-width: 300px; line-height: 1.5; }

/* ── stock ── */
.tile-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.tile { display: block; text-decoration: none; background: var(--d-raised); border: 1px solid var(--d-border); border-radius: 14px; padding: 14px 16px 16px; transition: border-color .2s ease, transform .25s var(--d-ease), box-shadow .25s ease; }
/* Plain version (roles without Product List access): no lift, click shows a short note */
.tile--static, .tile--static:hover { cursor: default; transform: none; box-shadow: none; border-color: var(--d-border); }
.tile--warn.tile--static:hover { border-color: rgba(245,158,11,.35); }
.tile--alert.tile--static:hover { border-color: rgba(244,63,94,.28); }
.tile-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.tile-ico { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: var(--d-violet); background: var(--d-violet-soft); }
.tile-go { font-size: 10px; color: var(--d-faint); opacity: 0; transform: translate(-3px, 3px); transition: opacity .2s ease, transform .2s var(--d-ease); }
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
.link-list a i:first-child { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: var(--d-sunken); color: var(--d-muted); font-size: 11.5px; transition: background-color .15s ease, color .15s ease; }
.link-list a i:last-child { margin-left: auto; font-size: 9px; color: var(--d-faint); transition: transform .2s var(--d-ease); }

/* ── shared tooltip (light by default, dark in dark mode) ── */
.tip {
  /* light mode tokens */
  --tip-bg: rgba(255, 255, 255, .98);
  --tip-text: #0f172a;
  --tip-label: #64748b;
  --tip-note: #475569;
  --tip-border: var(--c-border-strong, #e2e8f0);
  --tip-divider: rgba(15, 23, 42, .09);
  --tip-dot-ring: rgba(15, 23, 42, .12);
  --tip-good: #059669;
  --tip-warn: #b45309;
  --tip-bad: #e11d48;
  --tip-shadow: 0 12px 32px -10px rgba(15, 23, 42, .28), 0 2px 6px rgba(15, 23, 42, .08);

  position: fixed; z-index: 80; pointer-events: none;
  width: max-content; min-width: 150px; max-width: min(250px, calc(100vw - 16px));
  padding: 10px 12px 11px; border-radius: 12px;
  background: var(--tip-bg); color: var(--tip-text);
  border: 1px solid var(--tip-border);
  box-shadow: var(--tip-shadow);
  -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
  opacity: 0; transform: translate(-50%, -100%) translateY(4px);
  transition: opacity .14s ease, transform .14s ease;
  font-size: 12px; line-height: 1.4;
}
/* dark mode tokens */
html[data-theme="dark"] .tip {
  --tip-bg: rgba(15, 23, 42, .96);
  --tip-text: #f8fafc;
  --tip-label: #94a3b8;
  --tip-note: #cbd5e1;
  --tip-border: rgba(255, 255, 255, .08);
  --tip-divider: rgba(255, 255, 255, .12);
  --tip-dot-ring: rgba(255, 255, 255, .14);
  --tip-good: #34d399;
  --tip-warn: #fbbf24;
  --tip-bad: #fb7185;
  --tip-shadow: 0 12px 32px -8px rgba(2, 6, 23, .5), 0 2px 6px rgba(2, 6, 23, .25);
}
.tip--on { opacity: 1; transform: translate(-50%, -100%); }
.tip--below { transform: translate(-50%, 0) translateY(-4px); }
.tip--below.tip--on { transform: translate(-50%, 0); }
.tip-title { display: flex; align-items: center; gap: 7px; margin: 0; font-size: 12.5px; font-weight: 700; letter-spacing: -.005em; overflow-wrap: anywhere; }
.tip-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 2px var(--tip-dot-ring); }
.tip-rows { margin: 8px 0 0; padding-top: 8px; border-top: 1px solid var(--tip-divider); display: flex; flex-direction: column; gap: 5px; }
.tip-row { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; }
.tip-row dt { color: var(--tip-label); font-weight: 500; }
.tip-row dd { margin: 0; font-weight: 700; font-variant-numeric: tabular-nums; text-align: right; overflow-wrap: anywhere; }
.tip-good { color: var(--tip-good); }
.tip-warn { color: var(--tip-warn); }
.tip-bad { color: var(--tip-bad); }
.tip-note { margin: 8px 0 0; color: var(--tip-note); font-size: 11.5px; line-height: 1.45; }
.tip-title + .tip-note { margin-top: 5px; }

/* ── skeleton ── */
.sk-card { display: flex; flex-direction: column; gap: 16px; animation: none; }
.sk { background: linear-gradient(90deg, var(--d-sunken) 25%, var(--d-raised) 50%, var(--d-sunken) 75%); background-size: 200% 100%; animation: sk 1.4s ease-in-out infinite; border-radius: 10px; }
@keyframes sk { to { background-position: -200% 0; } }
.sk-line { height: 14px; width: 35%; }
.sk-big { height: 44px; width: 55%; }
.sk-bar { height: 10px; border-radius: 10px; }
.sk-block { height: 90px; border-radius: 14px; }
.sk-tall { height: 240px; }

/* ── hover effects: only on devices that really hover (no sticky hover on touch) ── */
@media (hover: hover) and (pointer: fine) {
  .btn:hover:not(:disabled) { border-color: var(--d-accent-border); color: var(--d-text); transform: translateY(-1px); box-shadow: 0 4px 12px -4px rgba(16,24,40,.18); }
  .btn--primary:hover:not(:disabled) { color: #fff; border-color: var(--d-accent); filter: brightness(1.07); }
  .link-btn:hover { background: var(--d-accent-soft); }
  .link-btn:hover i { transform: translateX(3px); }
  .notice-btn:hover { background: var(--d-accent-soft); }
  .card:hover { box-shadow: var(--d-shadow-hover); border-color: var(--d-border-strong); }
  .seg-btn:hover:not(.seg-btn--active) { color: var(--d-text); }
  .ms:hover { border-color: var(--d-accent-border); transform: translateY(-2px); }
  .goal-track:hover { box-shadow: inset 0 1px 2px rgba(16,24,40,.08), 0 0 0 3px var(--d-accent-soft); }
  .goal-track:hover .goal-fill { filter: brightness(1.08); }
  .cal-day:hover:not(:disabled) { transform: scale(1.1); border-color: rgb(var(--lv)); box-shadow: 0 6px 14px -6px rgba(var(--lv), .6); z-index: 1; }
  .split-seg:hover { filter: brightness(1.12); }
  .mix-row:hover { background: var(--d-raised); }
  .tile:hover:not(.tile--static) { border-color: var(--d-accent-border); transform: translateY(-3px); box-shadow: 0 12px 24px -14px rgba(16,24,40,.28); }
  .tile:hover .tile-go { opacity: 1; transform: translate(0, 0); }
  .link-list a:hover { background: var(--d-raised); color: var(--d-accent); }
  .link-list a:hover i:first-child { background: var(--d-accent-soft); color: var(--d-accent); }
  .link-list a:hover i:last-child { transform: translateX(3px); color: var(--d-accent); }
}

/* ── responsive ──
   --bleed-x / --bleed-y reclaim the shell's padding at every size, and
   --d-pad-x is the page's own inner side padding. */

/* Large screens: more breathing room and slightly roomier cards */
@media (min-width: 1500px) {
  .dash { --d-gap: 20px; --d-pad-x: 32px; padding-bottom: 48px; }
  .card { padding: 24px 26px; }
}

/* Tablets and below: single column layout */
@media (max-width: 1000px) {
  .dash { --bleed-x: 22px; --bleed-y: 20px; }
  .row-two, .cal-layout { grid-template-columns: minmax(0, 1fr); }
  .cal-layout { gap: 20px; }
  /* with the full width available, keep the product donut and list side by side */
  .mix-body { grid-template-columns: 200px minmax(0, 1fr); }
}
@media (max-width: 900px) {
  .dash { --d-pad-x: 16px; --d-gap: 14px; padding-top: 12px; padding-bottom: 32px; }
}
@media (max-width: 860px) {
  /* Stacked, the calendar would otherwise get huge cells */
  .cal-day { aspect-ratio: auto; min-height: 44px; }
}

/* Phones */
@media (max-width: 600px) {
  .dash { --bleed-x: 16px; --bleed-y: 16px; --d-pad-x: 12px; --d-gap: 12px; padding-top: 10px; }

  .card { padding: 16px 14px; border-radius: 16px; }
  .card::before { left: 16px; right: 16px; }
  .card-header { gap: 12px; margin-bottom: 14px; }
  .card-head-l { width: 100%; }

  /* header: title on top, full-width 44px touch button below */
  .page-header { flex-direction: column; align-items: stretch; gap: 12px; }
  .page-title { font-size: 24px; }
  .page-header-right { display: grid; grid-template-columns: repeat(auto-fit, minmax(0, 1fr)); gap: 8px; }
  .btn { height: 44px; font-size: 13px; }

  /* notices: message on top, action button spans the full width */
  .notice { flex-wrap: wrap; padding: 8px 10px 8px 12px; }
  .notice > span { flex-basis: calc(100% - 40px); }
  .notice-btn { flex-basis: 100%; text-align: center; padding: 9px 12px; }

  /* segmented controls fill the card width and are easy to tap */
  .seg { width: 100%; }
  .seg-btn { flex: 1; padding: 9px 8px; font-size: 12.5px; }

  /* hero: number first, then the decorative line spans the full width */
  .hero-row { flex-direction: column; align-items: stretch; gap: 14px; }
  .spark { align-items: stretch; width: 100%; }
  .spark svg { width: 100%; height: 56px; }
  .spark-cap { align-self: flex-end; }

  .goal { margin-top: 18px; }
  .goal-track { height: 12px; }
  .goal-input input { width: 100px; font-size: 16px; } /* 16px stops iOS zooming into the field */

  .mini-stats, .mini-stats--3 { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-top: 16px; }
  .ms { padding: 11px 12px; }
  .mini-stats--3 .ms:last-child { grid-column: 1 / -1; }

  /* heatmap: compact tap targets across the whole width */
  .cal { gap: 5px; }
  .cal-day { aspect-ratio: auto; min-height: 44px; border-radius: 9px; font-size: 12.5px; }
  .day { padding: 14px 12px; }
  .day-value { font-size: 21px; }
  .split-bar { height: 12px; }

  /* sales mix: donut centered, list underneath */
  .mix { gap: 14px; }
  .mix-body { grid-template-columns: minmax(0, 1fr); gap: 16px; }
  .donut { margin: 0 auto; width: min(230px, 100%); }
  .mix-row { padding: 12px 10px; }
  .mix-detail { padding: 2px 10px 12px 30px; }

  /* stock: "In hand" spans the top, the two alerts sit side by side */
  .tile-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .tile--first { grid-column: 1 / -1; }
  .tile { padding: 12px 14px 14px; }
  .coverage { padding: 12px 14px; }
  .link-list a { padding: 13px 10px; }

  /* tooltip a touch narrower so it never crowds the screen edge */
  .tip { min-width: 140px; max-width: calc(100vw - 20px); }
}

/* Very small phones */
@media (max-width: 360px) {
  .dash { --d-pad-x: 10px; }
  .card { padding: 14px 12px; }
  .big-value { font-size: 30px; }
  .cal { gap: 4px; }
  .mix-amt { display: none; }
  .day-value { font-size: 19px; }
}

@media (prefers-reduced-motion: reduce) {
  .dash * { transition: none !important; animation: none !important; }
  .spark-line { stroke-dashoffset: 0; }
  .spark-area { opacity: 1; }
}
</style>