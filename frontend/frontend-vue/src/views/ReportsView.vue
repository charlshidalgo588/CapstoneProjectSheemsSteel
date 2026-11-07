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

          <!-- Custom calendar picker (replaces the native date / month / year inputs) -->
          <div class="picker" ref="pickerRoot">
            <button
              ref="pickerTrigger"
              type="button"
              class="picker-trigger"
              :class="{ 'picker-trigger--open': pickerOpen }"
              aria-haspopup="dialog"
              :aria-expanded="pickerOpen"
              :aria-label="`Choose ${modeNoun}. Selected: ${pickerLabel}`"
              @click="togglePicker"
            >
              <i class="fa-solid fa-calendar-days picker-ico"></i>
              <span>{{ pickerLabel }}</span>
              <i class="fa-solid fa-chevron-down picker-caret"></i>
            </button>

            <Transition name="pop">
              <div v-if="pickerOpen" class="picker-pop" role="dialog" :aria-label="`Choose ${modeNoun}`">
                <!-- Header: ‹ title › -->
                <div class="pk-head">
                  <button type="button" class="pk-nav" :disabled="!pkCanPrev" :class="{ 'pk-nav--hide': pickerView === 'years' }" aria-label="Previous" @click="pkShift(-1)">
                    <i class="fa-solid fa-chevron-left"></i>
                  </button>
                  <button type="button" class="pk-title" :disabled="pickerView === 'years'" @click="pkUp">
                    {{ pkTitle }}
                    <i v-if="pickerView !== 'years'" class="fa-solid fa-chevron-down"></i>
                  </button>
                  <button type="button" class="pk-nav" :disabled="!pkCanNext" :class="{ 'pk-nav--hide': pickerView === 'years' }" aria-label="Next" @click="pkShift(1)">
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

        <!-- ── 3. HEATMAP (adapts: Day → week, Month → calendar, Year → months) ── -->
        <section class="card" aria-labelledby="rp-heat">
          <header class="card-head">
            <div>
              <h2 id="rp-heat" class="card-title">{{ heatTitle }}</h2>
              <p class="card-sub">{{ heatSub }}</p>
            </div>
            <div class="seg seg--sm" role="group" aria-label="Heatmap metric">
              <button type="button" class="seg-btn" :class="{ 'seg-btn--active': calMetric === 'total' }" :aria-pressed="calMetric === 'total'" @click="calMetric = 'total'">Sales</button>
              <button type="button" class="seg-btn" :class="{ 'seg-btn--active': calMetric === 'profit' }" :aria-pressed="calMetric === 'profit'" @click="calMetric = 'profit'">Profit</button>
            </div>
          </header>

          <p v-if="mode === 'day' && weekFailed" class="heat-note heat-note--warn">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Couldn't load the other days of this week. Use Refresh to try again.
          </p>

          <div class="heat-layout">
            <div class="heat" :class="[`heat--${mode}`, { 'heat--profit': calMetric === 'profit' }]">
              <template v-if="mode === 'month'">
                <span v-for="(w, i) in weekdays" :key="'w' + i" class="heat-dow">{{ w }}</span>
              </template>

              <template v-for="(c, i) in heatCells" :key="i">
                <span v-if="!c" class="heat-blank"></span>
                <button
                  v-else
                  type="button"
                  class="heat-cell"
                  :class="[`lv${c.level}`, { 'heat-cell--sel': heatActive && c.key === heatActive.key, 'heat-cell--today': c.today }]"
                  :disabled="c.future"
                  :aria-pressed="!!heatActive && c.key === heatActive.key"
                  :aria-label="`${c.title}: ₱${price(c.v)}`"
                  :title="`${c.title}: ₱${price(c.v)}`"
                  @click="selectHeat(c.key)"
                >
                  <template v-if="mode === 'day'">
                    <span class="hc-dow">{{ c.label }}</span>
                    <span class="hc-num">{{ c.num }}</span>
                    <span class="hc-amt">{{ c.future ? '' : '₱' + compact(c.v) }}</span>
                  </template>
                  <template v-else-if="mode === 'year'">
                    <span class="hc-month">{{ c.label }}</span>
                    <span class="hc-amt">{{ c.future ? '' : '₱' + compact(c.v) }}</span>
                  </template>
                  <template v-else>{{ c.label }}</template>
                </button>
              </template>

              <div class="heat-scale"><span>Less</span><i class="lv0"></i><i class="lv1"></i><i class="lv2"></i><i class="lv3"></i><i class="lv4"></i><span>More</span></div>
            </div>

            <div v-if="heatDetail" class="heat-day" aria-live="polite">
              <div class="heat-day-head">
                <h3 class="heat-day-title">{{ heatDetail.cell.title }}</h3>
                <span v-if="heatDetail.cell.today" class="chip chip--accent">{{ mode === 'year' ? 'This month' : 'Today' }}</span>
                <button v-if="canOpenHeat" type="button" class="link-btn" @click="openHeatCell">{{ openHeatLabel }}</button>
              </div>
              <p class="heat-day-value">₱{{ price(heatDetail.cell.total) }}</p>

              <div v-if="heatDetail.cell.total > 0" class="split-wrap">
                <div class="split-bar" role="img" :aria-label="`Profit ${heatDetail.margin} percent of sales`">
                  <span class="split-seg split-seg--profit" :style="{ width: (barsReady ? heatDetail.profitPct : 0) + '%' }" :title="`Profit ₱${price(heatDetail.cell.profit)}`"></span>
                  <span class="split-seg split-seg--cost" :style="{ width: (barsReady ? 100 - heatDetail.profitPct : 0) + '%' }" :title="`Cost ₱${price(heatDetail.cost)}`"></span>
                </div>
                <div class="split-legend"><span><i class="sl sl--profit"></i>Profit</span><span><i class="sl sl--cost"></i>Cost of goods</span></div>
              </div>
              <p v-else class="heat-muted">No sales in this {{ heatCellWord }}.</p>

              <dl class="facts">
                <div><dt>Profit</dt><dd class="pos">₱{{ price(heatDetail.cell.profit) }}</dd></div>
                <div><dt>Cost of goods</dt><dd>₱{{ price(heatDetail.cost) }}</dd></div>
                <div><dt>Margin</dt><dd>{{ heatDetail.margin }}%</dd></div>
                <div><dt>Share of {{ heatRangeWord }}</dt><dd>{{ heatDetail.share }}%</dd></div>
                <div><dt>Vs {{ heatCellWord }}ly average</dt><dd :class="heatDetail.vsAvg >= 0 ? 'pos' : 'neg'">{{ heatDetail.vsAvg >= 0 ? '+' : '' }}{{ heatDetail.vsAvg.toFixed(1) }}%</dd></div>
              </dl>
            </div>
          </div>

          <p v-if="!heatHasData && !loading" class="heat-note">
            <i class="fa-solid fa-calendar-xmark"></i>
            No sales recorded in this {{ heatRangeWord }}.
          </p>
        </section>

        <!-- ── 4. WHERE SALES COME FROM + INVENTORY ── -->
        <div class="content-grid">

          <section class="card" aria-labelledby="rp-mix">
            <header class="card-head">
              <div>
                <h2 id="rp-mix" class="card-title">Where sales come from</h2>
                <p class="card-sub">Share of {{ periodLabel || 'this period' }} {{ topMetric === 'qty' ? 'units sold' : 'sales' }} by product</p>
              </div>
              <div class="seg seg--sm" role="group" aria-label="Measure by">
                <button type="button" class="seg-btn" :class="{ 'seg-btn--active': topMetric === 'sales' }" :aria-pressed="topMetric === 'sales'" @click="setTopMetric('sales')">By sales</button>
                <button type="button" class="seg-btn" :class="{ 'seg-btn--active': topMetric === 'qty' }" :aria-pressed="topMetric === 'qty'" @click="setTopMetric('qty')">By units</button>
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
                      class="donut-seg" :class="{ 'is-on': focusSlice === i, 'is-dim': focusSlice !== null && focusSlice !== i }"
                      cx="100" cy="100" r="74" transform="rotate(-90 100 100)"
                      :stroke="mixDonut[i].color"
                      :stroke-dasharray="`${barsReady ? a.len : 0} ${a.C}`"
                      :stroke-dashoffset="a.offset"
                      tabindex="0" role="button"
                      :aria-label="`${mixDonut[i].name}, ${mixDonut[i].pct.toFixed(1)} percent`"
                      @mouseenter="hoverSlice = i" @mouseleave="hoverSlice = null"
                      @focus="hoverSlice = i" @blur="hoverSlice = null"
                      @click="pickSlice(i)" @keydown.enter.prevent="pickSlice(i)" @keydown.space.prevent="pickSlice(i)"
                    />
                  </svg>
                  <div class="donut-center" aria-live="polite">
                    <template v-if="centerRow">
                      <strong>{{ centerRow.pct.toFixed(1) }}%</strong>
                      <span class="dc-name">{{ centerRow.name }}</span>
                      <span class="dc-sub">{{ mixValueText(centerRow) }}</span>
                    </template>
                    <template v-else>
                      <strong>{{ topMetric === 'qty' ? formatNumber(mixBase) : '₱' + compact(mixBase) }}</strong>
                      <span class="dc-name">{{ topMetric === 'qty' ? 'Units sold' : 'Total sales' }}</span>
                      <span class="dc-sub">Hover or select a slice</span>
                    </template>
                  </div>
                </div>

                <ul class="mix-list" :class="{ 'mix-list--all': showAllMix && mixList.length > MIX_PREVIEW }">
                  <li v-for="(r, i) in visibleMix" :key="r.key + i" :class="{ 'mix-item--sel': mixSel === i }">
                    <button
                      type="button"
                      class="mix-row"
                      :class="{ 'mix-row--on': focusSlice !== null && focusSlice === sliceOf(i) && mixSel !== i }"
                      :aria-pressed="mixSel === i"
                      @click="pickMix(i)"
                      @mouseenter="hoverRow = i" @mouseleave="hoverRow = null"
                      @focus="hoverRow = i" @blur="hoverRow = null"
                    >
                      <span class="mix-top">
                        <i class="mix-dot" :style="{ background: r.color }"></i>
                        <span class="mix-name">{{ r.name }}</span>
                        <span class="mix-amt">{{ mixValueText(r) }}</span>
                        <span class="mix-pct">{{ r.pct.toFixed(1) }}%</span>
                      </span>
                      <span class="mix-bar"><span class="mix-bar-fill" :style="{ width: (barsReady ? r.pct : 0) + '%', background: r.color }"></span></span>
                    </button>
                    <dl v-if="mixSel === i" class="mix-detail">
                      <div><dt>Sales</dt><dd>₱{{ price(r.sales) }}</dd></div>
                      <template v-if="!r.isOther">
                        <div><dt>Units sold</dt><dd>{{ formatNumber(r.qty) }}</dd></div>
                        <div><dt>Average price</dt><dd>₱{{ price(r.qty ? r.sales / r.qty : 0) }}</dd></div>
                        <div v-if="r.category"><dt>Category</dt><dd>{{ r.category }}</dd></div>
                      </template>
                      <div v-else><dt>Includes</dt><dd>{{ r.isService ? 'Your share of extra services (worker pay excluded)' : 'Sales not tied to a listed product' }}</dd></div>
                    </dl>
                  </li>
                </ul>
              </div>

              <button
                v-if="mixList.length > MIX_PREVIEW"
                type="button"
                class="link-btn link-btn--block"
                @click="toggleShowAllMix"
              >
                {{ showAllMix ? 'Show fewer' : `Show all ${mixList.length} products` }}
              </button>
            </div>

            <p v-else class="empty">
              <i class="fa-solid fa-chart-pie"></i>
              No sales recorded for this period.
            </p>
          </section>

          <!-- Inventory: same low / out-of-stock rules as the Home dashboard -->
          <aside class="card" aria-labelledby="rp-inv">
            <header class="card-head">
              <div>
                <h2 id="rp-inv" class="card-title">Inventory</h2>
                <p class="card-sub">Current stock. Not affected by the period above.</p>
              </div>
            </header>

            <ul class="inv-list">
              <li
                v-for="box in inventorySummary"
                :key="invKey(box) || box.label"
                class="inv-row"
                :class="invTone(box) ? `inv-row--${invTone(box)}` : ''"
              >
                <component
                  :is="invLink(box) ? RouterLink : 'div'"
                  v-bind="invLink(box) ? { to: invLink(box) } : {}"
                  class="inv-link"
                >
                  <span class="inv-icon"><i class="fa-solid" :class="inventoryIcon(box)"></i></span>
                  <span class="inv-text">
                    <span class="inv-label">{{ box.label }}</span>
                    <span v-if="invHint(box)" class="inv-hint">{{ invHint(box) }}</span>
                  </span>
                  <strong class="inv-value">{{ formatNumber(box.value) }}</strong>
                  <i v-if="invLink(box)" class="fa-solid fa-chevron-right inv-go"></i>
                </component>
              </li>
            </ul>

            <div v-if="stockCoverage" class="coverage">
              <div class="coverage-head">
                <span>{{ formatNumber(stockCoverage.active) }} of {{ formatNumber(stockCoverage.total) }} items well stocked</span>
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

        <!-- Letterhead -->
        <header class="ps-head">
          <div class="ps-band"></div>
          <div class="ps-head-row">
            <div class="ps-brand">
              <span class="ps-logo">{{ brandInitials }}</span>
              <div>
                <p class="ps-brand-name">{{ BUSINESS_NAME }}</p>
                <p class="ps-brand-tag">{{ BUSINESS_TAGLINE }}</p>
              </div>
            </div>
            <dl class="ps-meta">
              <div><dt>Report no.</dt><dd>{{ reportRef }}</dd></div>
              <div><dt>Period</dt><dd>{{ periodLabel }}</dd></div>
              <div><dt>Generated</dt><dd>{{ printGeneratedAt }}</dd></div>
              <div><dt>Currency</dt><dd>Philippine Peso (₱)</dd></div>
            </dl>
          </div>
          <div class="ps-title-row">
            <div>
              <p class="ps-kicker">Sales &amp; inventory report</p>
              <h1 class="ps-title">{{ reportTypeLabel }}</h1>
            </div>
            <div class="ps-title-right">
              <p class="ps-period">{{ periodLabel }}</p>
              <span class="ps-chip" :class="isCurrentPeriod ? 'ps-chip--live' : 'ps-chip--closed'">{{ isCurrentPeriod ? 'In progress' : 'Closed period' }}</span>
            </div>
          </div>
        </header>

        <!-- 01 Executive summary -->
        <section class="ps-section">
          <h2 class="ps-h2"><span class="ps-num">01</span>Executive summary</h2>
          <p class="ps-lead">{{ execSummary }}</p>

          <div class="ps-hero">
            <div v-for="k in headlineKpis" :key="k.key" class="ps-hero-kpi" :class="`ps-kpi--${k.tone}`">
              <p class="ps-kpi-label">{{ k.label }}</p>
              <p class="ps-hero-value">{{ k.value }}</p>
              <p class="ps-kpi-sub">{{ k.sub }}</p>
            </div>
          </div>

          <div class="ps-kpis">
            <div v-for="k in detailKpis" :key="k.key" class="ps-kpi" :class="`ps-kpi--${k.tone}`">
              <p class="ps-kpi-label">{{ k.label }}</p>
              <p class="ps-kpi-value" :class="{ 'ps-kpi-value--text': k.text }">{{ k.value }}</p>
              <p class="ps-kpi-sub">{{ k.sub }}</p>
            </div>
          </div>

          <p v-if="trendPlain" class="ps-callout" :class="`ps-callout--${salesTrend.direction}`">
            <b>Sales trend</b> {{ trendPlain }}
          </p>
        </section>

        <!-- 02 Trend chart -->
        <section class="ps-section ps-section--keep">
          <h2 class="ps-h2"><span class="ps-num">02</span>{{ seriesWord }} sales and profit</h2>
          <div v-if="printChartImg && chartRows.length">
            <div class="ps-legend">
              <span><i class="ps-dot ps-dot--orange"></i>Sales</span>
              <span><i class="ps-dot ps-dot--green"></i>Profit</span>
            </div>
            <img :src="printChartImg" class="ps-chart" alt="Sales and profit trend chart" />
          </div>
          <p v-else class="ps-empty">No sales recorded for this period.</p>
        </section>

        <!-- 03 Top selling items -->
        <section class="ps-section">
          <h2 class="ps-h2"><span class="ps-num">03</span>Top selling items <small>ranked by {{ topMetric === 'sales' ? 'sales amount' : 'units sold' }}</small></h2>
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
              <tr v-for="item in printTop" :key="rowKey(item)">
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
              <tr v-if="printTopRest" class="ps-row--rest">
                <td></td>
                <td colspan="2">+ {{ printTopRest.count }} other {{ printTopRest.count === 1 ? 'product' : 'products' }}</td>
                <td class="num">{{ formatNumber(printTopRest.qty) }}</td>
                <td></td>
                <td class="num">₱{{ price(printTopRest.sales) }}</td>
                <td></td>
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

        <!-- 04 Breakdown -->
        <section class="ps-section">
          <h2 class="ps-h2"><span class="ps-num">04</span>{{ seriesWord }} breakdown <small>periods with sales only</small></h2>
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

        <!-- 05 Inventory -->
        <section class="ps-section ps-section--keep">
          <h2 class="ps-h2"><span class="ps-num">05</span>Inventory snapshot <small>current stock, not period-based</small></h2>
          <p v-if="stockAlert" class="ps-callout ps-callout--alert"><b>Action needed</b> {{ stockAlert }}</p>
          <div class="ps-inv">
            <div
              v-for="box in inventorySummary"
              :key="invKey(box) || box.label"
              class="ps-inv-box"
              :class="invTone(box) ? `ps-inv-box--${invTone(box)}` : ''"
            >
              <p>{{ box.label }}</p>
              <strong>{{ formatNumber(box.value) }}</strong>
            </div>
          </div>
          <div v-if="stockCoverage" class="ps-cover">
            <div class="ps-cover-head">
              <span>{{ formatNumber(stockCoverage.active) }} of {{ formatNumber(stockCoverage.total) }} items well stocked</span>
              <strong>{{ stockCoverage.pct }}%</strong>
            </div>
            <div class="ps-cover-track"><i :style="{ width: stockCoverage.pct + '%' }"></i></div>
          </div>
        </section>

        <!-- 06 Notes & sign-off -->
        <section class="ps-section ps-section--keep">
          <h2 class="ps-h2"><span class="ps-num">06</span>Notes &amp; approval</h2>
          <ul class="ps-notes">
            <li><b>Sales</b> are the shop's share: customer payments less the worker's pass-through pay on extra services.</li>
            <li><b>Profit</b> uses the price and cost recorded at the time of each sale, plus the shop's share of extra services.</li>
            <li><b>Inventory</b> is a snapshot of current stock and is not affected by the selected period.</li>
            <li v-if="isCurrentPeriod"><b>Partial figures.</b> This period is still in progress, so totals may change.</li>
          </ul>
          <div class="ps-sign">
            <div><span></span><p>Prepared by</p></div>
            <div><span></span><p>Reviewed by</p></div>
            <div><span></span><p>Date</p></div>
          </div>
        </section>

        <footer class="ps-end">
          <span>{{ BUSINESS_NAME }} · {{ reportTypeLabel }} · {{ periodLabel }} · Ref. {{ reportRef }}</span>
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
import { RouterLink } from 'vue-router'
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
const compact = (v) => Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 }).format(num(v))
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
const currentDayValue   = toDayStr(today)    // 'YYYY-MM-DD'
const currentMonthValue = toMonthStr(today)  // 'YYYY-MM'
const currentYear       = today.getFullYear()
const yearOptions       = Array.from({ length: 12 }, (_, i) => currentYear - i) // newest first, 12 = a clean 3 × 4 grid
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
  closePicker()
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

/* ════════════════════════════════════════════════════════════
   CALENDAR PICKER  (custom popover: days → months → years)
════════════════════════════════════════════════════════════ */
const PK_WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']
const MONTH_NAMES = Array.from({ length: 12 }, (_, i) => new Date(2000, i, 1).toLocaleDateString('en-US', { month: 'long' }))
const MONTH_SHORT = Array.from({ length: 12 }, (_, i) => new Date(2000, i, 1).toLocaleDateString('en-US', { month: 'short' }))

const pickerOpen    = ref(false)
const pickerView    = ref('months') // 'days' | 'months' | 'years'
const browseYear    = ref(currentYear)
const browseMonth   = ref(today.getMonth()) // 0-11
const pickerRoot    = ref(null)
const pickerTrigger = ref(null)

const pickerLabel = computed(() => {
  if (mode.value === 'year') return String(selectedYear.value)
  if (mode.value === 'month') {
    const [y, m] = (selectedMonth.value || currentMonthValue).split('-').map(Number)
    return `${MONTH_NAMES[m - 1]} ${y}`
  }
  const d = new Date(`${selectedDay.value || currentDayValue}T00:00:00`)
  return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
})

function openPicker() {
  if (mode.value === 'day') {
    const [y, m] = (selectedDay.value || currentDayValue).split('-').map(Number)
    browseYear.value = y
    browseMonth.value = m - 1
    pickerView.value = 'days'
  } else if (mode.value === 'month') {
    browseYear.value = Number((selectedMonth.value || currentMonthValue).slice(0, 4))
    pickerView.value = 'months'
  } else {
    browseYear.value = selectedYear.value
    pickerView.value = 'years'
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
function onDocKey(e) {
  if (e.key === 'Escape' && pickerOpen.value) closePicker(true)
}

const pkTitle = computed(() => {
  if (pickerView.value === 'days')   return `${MONTH_NAMES[browseMonth.value]} ${browseYear.value}`
  if (pickerView.value === 'months') return String(browseYear.value)
  return `${minYear} – ${currentYear}`
})
const pkCanPrev = computed(() => {
  if (pickerView.value === 'days')   return browseYear.value * 12 + browseMonth.value > minYear * 12
  if (pickerView.value === 'months') return browseYear.value > minYear
  return false
})
const pkCanNext = computed(() => {
  if (pickerView.value === 'days')   return browseYear.value * 12 + browseMonth.value < currentYear * 12 + today.getMonth()
  if (pickerView.value === 'months') return browseYear.value < currentYear
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
      future: key > currentDayValue,
      today: key === currentDayValue,
      selected: mode.value === 'day' && key === selectedDay.value,
    })
  }
  return out
})
const pkMonthCells = computed(() =>
  MONTH_SHORT.map((label, m) => {
    const key = `${browseYear.value}-${pad(m + 1)}`
    const selected =
      mode.value === 'month' ? key === selectedMonth.value
      : mode.value === 'day' ? (selectedDay.value || '').startsWith(key)
      : false
    return {
      key, m, label,
      title: `${MONTH_NAMES[m]} ${browseYear.value}`,
      future: key > currentMonthValue,
      today: key === currentMonthValue,
      selected,
    }
  })
)
const pkYearCells = computed(() =>
  [...yearOptions].reverse().map((y) => ({
    y,
    today: y === currentYear,
    selected: mode.value === 'year' ? y === selectedYear.value : y === browseYear.value,
  }))
)

async function pkPickDay(key) {
  selectedDay.value = key
  closePicker(true)
  await loadReports()
}
async function pkPickMonth(m) {
  if (mode.value === 'day') {
    // Day mode: choosing a month just opens its days.
    browseMonth.value = m
    pickerView.value = 'days'
    return
  }
  selectedMonth.value = `${browseYear.value}-${pad(m + 1)}`
  closePicker(true)
  await loadReports()
}
async function pkPickYear(y) {
  if (mode.value === 'year') {
    selectedYear.value = y
    closePicker(true)
    await loadReports()
    return
  }
  // Day / Month mode: choosing a year opens its months.
  browseYear.value = y
  if (mode.value === 'day' && y === currentYear && browseMonth.value > today.getMonth()) browseMonth.value = today.getMonth()
  pickerView.value = 'months'
}
async function pkGoCurrent() {
  closePicker(true)
  await goCurrent()
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
  { key: 'quantity', label: 'Quantity in Hand',   value: 0 },
  { key: 'low',      label: 'Low Stock Items',    value: 0 },
  { key: 'out',      label: 'Out of Stock Items', value: 0 },
  { key: 'total',    label: 'Total Items',        value: 0 },
  { key: 'active',   label: 'Active Items',       value: 0 },
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

// Best seller by sales amount (independent of the metric toggle).
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

/* ── Inventory card (same low / out rules as the Home dashboard) ── */
const INV_KEY_BY_LABEL = {
  'Quantity in Hand': 'quantity',
  'Low Stock Items': 'low',
  'Out of Stock Items': 'out',
  'Total Items': 'total',
  'Active Items': 'active',
}
const INVENTORY_ICONS = {
  quantity: 'fa-boxes-stacked',
  low: 'fa-triangle-exclamation',
  out: 'fa-circle-xmark',
  total: 'fa-tags',
  active: 'fa-circle-check',
}
const invKey = (box) => box.key || INV_KEY_BY_LABEL[box.label] || ''
const inventoryIcon = (box) => INVENTORY_ICONS[invKey(box)] || 'fa-box'

// 'warn' = low (above zero but at / under the reorder level), 'alert' = out (zero). Only when there is something to flag.
const invTone = (box) => {
  const k = invKey(box)
  if (k === 'low' && num(box.value) > 0) return 'warn'
  if (k === 'out' && num(box.value) > 0) return 'alert'
  return ''
}
const invHint = (box) => {
  const k = invKey(box)
  if (k === 'low') return num(box.value) > 0 ? 'Reorder soon' : 'Nothing running low'
  if (k === 'out') return num(box.value) > 0 ? 'Restock now' : 'All available'
  return ''
}
// Same destinations as the Home dashboard tiles.
const invLink = (box) => {
  const k = invKey(box)
  if (k === 'quantity') return { name: 'products' }
  if (k === 'low' && num(box.value) > 0) return { name: 'products', query: { stockFilter: 'low' } }
  if (k === 'out' && num(box.value) > 0) return { name: 'products', query: { stockFilter: 'out' } }
  return null
}

const stockCoverage = computed(() => {
  const total  = inventorySummary.value.find((b) => invKey(b) === 'total')
  const active = inventorySummary.value.find((b) => invKey(b) === 'active')
  if (!total || !active || !num(total.value)) return null
  const pct = Math.min(100, Math.round((num(active.value) / num(total.value)) * 100))
  return { total: num(total.value), active: num(active.value), pct }
})

/* ── Product ranking (feeds the print table, Copy, CSV and the sales mix) ── */
const topMetric = ref('sales')
const barsReady = ref(false)

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
const topTotals = computed(() => ({
  qty:   rankedTopItems.value.reduce((s, i) => s + i.total_quantity, 0),
  sales: rankedTopItems.value.reduce((s, i) => s + i.total_sales, 0),
}))

const avgPrice = (item) => (item && item.total_quantity ? item.total_sales / item.total_quantity : 0)

/* ── Print-only helpers ── */
const PRINT_TOP_LIMIT = 15
const brandInitials = BUSINESS_NAME.split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()

// Document reference, e.g. SR-M-202611 / SR-D-20261108 / SR-Y-2026
const reportRef = computed(() => {
  const code = { day: 'D', month: 'M', year: 'Y' }[mode.value]
  const key =
    mode.value === 'year' ? String(selectedYear.value)
    : mode.value === 'month' ? String(selectedMonth.value || '').replace('-', '')
    : String(selectedDay.value || '').replace(/-/g, '')
  return `SR-${code}-${key}`
})

const headlineKpis = computed(() => printKpis.value.slice(0, 3))
const detailKpis   = computed(() => printKpis.value.slice(3))

// Long product lists are capped on paper; the rest collapse into one row (totals still cover everything).
const printTop = computed(() => rankedTopItems.value.slice(0, PRINT_TOP_LIMIT))
const printTopRest = computed(() => {
  const rest = rankedTopItems.value.slice(PRINT_TOP_LIMIT)
  if (!rest.length) return null
  return {
    count: rest.length,
    qty: rest.reduce((a, i) => a + i.total_quantity, 0),
    sales: rest.reduce((a, i) => a + i.total_sales, 0),
  }
})

const stockAlert = computed(() => {
  const low = num(inventorySummary.value.find((b) => invKey(b) === 'low')?.value)
  const out = num(inventorySummary.value.find((b) => invKey(b) === 'out')?.value)
  const plural = (n) => (n === 1 ? 'item is' : 'items are')
  if (out > 0 && low > 0) return `${formatNumber(out)} ${plural(out)} out of stock and ${formatNumber(low)} more ${low === 1 ? 'is' : 'are'} running low. Restock and reorder soon.`
  if (out > 0) return `${formatNumber(out)} ${plural(out)} out of stock. Restock as soon as possible.`
  if (low > 0) return `${formatNumber(low)} ${plural(low)} running low. Reorder soon to avoid running out.`
  return ''
})

// A short, plain-English paragraph that opens the report.
const execSummary = computed(() => {
  const label = periodLabel.value || 'the selected period'
  if (!txCount.value && !num(salesTotal.value)) {
    return `No sales were recorded for ${label}.${isCurrentPeriod.value ? ' This period is still in progress.' : ''}`
  }
  const parts = [
    `For ${label}, the shop recorded ${peso(salesTotal.value)} in sales across ${formatNumber(txCount.value)} ${txCount.value === 1 ? 'transaction' : 'transactions'} (${formatNumber(itemsSold.value)} items sold), earning ${peso(profitTotal.value)} in profit at a ${profitMarginPct.value}% margin.`,
  ]
  const t = salesTrend.value
  if (t.available && !t.isNew && t.direction !== 'flat') {
    parts.push(`Sales were ${t.direction === 'up' ? 'up' : 'down'} ${t.percent}% compared with ${comparisonLabel.value}.`)
  } else if (t.available && t.isNew) {
    parts.push(`There were no sales in ${comparisonLabel.value} to compare against.`)
  } else if (t.available) {
    parts.push(`Sales were level with ${comparisonLabel.value}.`)
  }
  if (peak.value) parts.push(`${peakWord.value}: ${peak.value.title} (${peso(peak.value.total)}).`)
  if (bestSeller.value) parts.push(`Top product: ${bestSeller.value.ProductName}, ${bestSeller.value.sharePct}% of sales.`)
  if (isCurrentPeriod.value) parts.push('Figures are partial because the period is still in progress.')
  return parts.join(' ')
})

// Bars start at 0 width and grow to their real width on the next paint.
function animateBarsIn() {
  barsReady.value = false
  nextTick(() => requestAnimationFrame(() => { barsReady.value = true }))
}

/* ════════════════════════════════════════════════════════════
   WHERE SALES COME FROM  (donut + full product list)
   The donut shows the top MIX_SLICES products plus one grey
   "Other" slice. The list beside it shows MIX_PREVIEW products and
   expands (scrollable) to every product in the period.
════════════════════════════════════════════════════════════ */
const MIX_SLICES  = 7
const MIX_PREVIEW = 8
const MIX_PALETTE = ['#EA580C', '#6366F1', '#10B981', '#3B82F6', '#F59E0B', '#EC4899', '#14B8A6']
const MIX_OTHER   = '#94A3B8'

const mixSel      = ref(null) // index in mixList
const hoverSlice  = ref(null) // index in mixDonut
const hoverRow    = ref(null) // index in mixList
const showAllMix  = ref(false)

// Owner's share of extra services (API: service_sales). null = the API didn't send it.
const serviceSales = ref(null)

const mixBase = computed(() => {
  const byQty = topMetric.value === 'qty'
  const sum = rankedTopItems.value.reduce((a, i) => a + (byQty ? i.total_quantity : i.total_sales), 0)
  return byQty ? sum : Math.max(num(salesTotal.value), sum + num(serviceSales.value))
})

const mixList = computed(() => {
  const byQty = topMetric.value === 'qty'
  const base  = mixBase.value || 1
  const valueOf = (i) => (byQty ? i.total_quantity : i.total_sales)

  const rows = rankedTopItems.value
    .filter((i) => valueOf(i) > 0)
    .map((i, idx) => ({
      key: String(rowKey(i)),
      name: i.ProductName,
      category: i.CategoryName || '',
      sales: i.total_sales,
      qty: i.total_quantity,
      value: valueOf(i),
      pct: (valueOf(i) / base) * 100,
      color: idx < MIX_SLICES ? MIX_PALETTE[idx] : MIX_OTHER,
      rank: idx + 1,
      isOther: false,
    }))

  if (!byQty) {
    // Shop's share of extra services (worker pay is already excluded by the API).
    const svc = num(serviceSales.value)
    if (svc > 0.5) {
      rows.push({ key: '__services', name: 'Extra services', category: '', sales: svc, qty: null, value: svc, pct: (svc / base) * 100, color: '#8B5CF6', rank: null, isOther: true, isService: true })
    }
    // Anything still unexplained (rounding, adjustments) stays visible instead of being hidden.
    const rest = base - rows.reduce((a, r) => a + r.value, 0)
    if (rest > 0.5) {
      // Older API without service_sales: the only non-product money in sales is the
      // owner's share of extra services, so the remainder is labelled as that.
      const legacy = serviceSales.value === null
      rows.push({
        key: legacy ? '__services' : '__other',
        name: legacy ? 'Extra services' : 'Other sales',
        category: '', sales: rest, qty: null, value: rest, pct: (rest / base) * 100,
        color: legacy ? '#8B5CF6' : MIX_OTHER, rank: null, isOther: true, isService: legacy,
      })
    }
  }
  return rows
})

const hasMix = computed(() => mixList.value.some((r) => r.pct > 0))

const mixDonut = computed(() => {
  const list = mixList.value
  const rows = list.slice(0, MIX_SLICES).map((r) => ({ ...r }))
  const tail = list.slice(MIX_SLICES)
  if (tail.length) {
    const products = tail.filter((t) => !t.isOther).length
    rows.push({
      key: '__rest',
      name: products ? 'Other products' : tail.map((t) => t.name).join(' & '),
      category: '',
      sales: tail.reduce((a, r) => a + r.sales, 0),
      qty: topMetric.value === 'qty' ? tail.reduce((a, r) => a + (r.qty || 0), 0) : null,
      value: tail.reduce((a, r) => a + r.value, 0),
      pct: tail.reduce((a, r) => a + r.pct, 0),
      color: MIX_OTHER,
      rank: null,
      isOther: true,
    })
  }
  return rows
})

const mixArcs = computed(() => {
  const C = 2 * Math.PI * 74
  const gap = mixDonut.value.length > 1 ? 3 : 0
  let acc = 0
  return mixDonut.value.map((r) => {
    const full = (r.pct / 100) * C
    const arc = { len: Math.max(full - gap, 0.5), offset: -acc, C }
    acc += full
    return arc
  })
})

// Which donut slice a list row belongs to (rows past the top slices share "Other").
function sliceOf(listIdx) {
  if (listIdx === null || listIdx === undefined) return null
  if (listIdx < MIX_SLICES) return listIdx
  return mixDonut.value.length > MIX_SLICES ? MIX_SLICES : null
}

const focusSlice = computed(() => {
  if (hoverSlice.value !== null) return hoverSlice.value
  if (hoverRow.value !== null) return sliceOf(hoverRow.value)
  return sliceOf(mixSel.value)
})
const centerRow = computed(() => {
  if (hoverSlice.value !== null) return mixDonut.value[hoverSlice.value] || null
  const li = hoverRow.value ?? mixSel.value
  return li === null || li === undefined ? null : mixList.value[li] || null
})

const visibleMix = computed(() => (showAllMix.value ? mixList.value : mixList.value.slice(0, MIX_PREVIEW)))

const mixValueText = (r) => (topMetric.value === 'qty' ? formatNumber(r.value) + ' units' : '₱' + price(r.value))

const mixInsight = computed(() => {
  const lead = mixList.value.find((r) => !r.isOther && r.rank === 1)
  if (!lead) return ''
  return topMetric.value === 'qty'
    ? `${lead.name} is ${lead.pct.toFixed(1)}% of the units sold in ${periodLabel.value || 'this period'}.`
    : `${lead.name} brings in ${lead.pct.toFixed(1)}% of ${periodLabel.value || 'this period'} sales.`
})
const mixConcentrated = computed(() => {
  const lead = mixList.value.find((r) => !r.isOther && r.rank === 1)
  return topMetric.value === 'sales' && !!lead && lead.pct >= 60
})

function pickMix(i) { mixSel.value = mixSel.value === i ? null : i }
function pickSlice(i) {
  // The grey "Other" slice opens the whole list instead of selecting one product.
  if (i >= MIX_SLICES) { showAllMix.value = true; return }
  pickMix(i)
}
function toggleShowAllMix() {
  showAllMix.value = !showAllMix.value
  if (!showAllMix.value && mixSel.value !== null && mixSel.value >= MIX_PREVIEW) mixSel.value = null
}
function setTopMetric(metric) {
  if (topMetric.value === metric) return
  topMetric.value = metric
  mixSel.value = null
  hoverSlice.value = null
  hoverRow.value = null
  animateBarsIn()
}

/* ════════════════════════════════════════════════════════════
   HEATMAP  (adapts to the period: Day → week, Month → calendar, Year → months)
════════════════════════════════════════════════════════════ */
const weekdays   = ['S', 'M', 'T', 'W', 'T', 'F', 'S']
const calMetric  = ref('total') // 'total' | 'profit'
const heatSelKey = ref(null)
const weekMap    = ref({})      // 'YYYY-MM-DD' -> { total, profit }  (Day mode only)
const weekFailed = ref(false)

// Sunday-start week that contains the given YYYY-MM-DD.
function weekStartOf(dayStr) {
  const d = new Date(`${dayStr}T00:00:00`)
  d.setDate(d.getDate() - d.getDay())
  return d
}

// Day mode only has hourly data, so the week strip pulls daily totals from
// the month report(s) the week touches (two months when it crosses a boundary).
async function fetchWeekMap() {
  try {
    const start = weekStartOf(selectedDay.value)
    const end = new Date(start)
    end.setDate(end.getDate() + 6)

    const months = new Map()
    ;[start, end].forEach((d) => months.set(toMonthStr(d), { year: d.getFullYear(), month: d.getMonth() + 1 }))

    const results = await Promise.all(
      [...months.values()].map((p) => api.get('/api/reports', { params: { mode: 'month', ...p, include_sales: 0 } }))
    )

    const map = {}
    results.forEach((res) => {
      const d = res.data || {}
      const profit = {}
      ;(d.monthly_profit ?? d.daily_profit ?? []).forEach((p) => {
        profit[String(p.date || '').slice(0, 10)] = num(p.profit ?? p.total)
      })
      ;(d.monthly_sales ?? []).forEach((s) => {
        const k = String(s.date || '').slice(0, 10)
        map[k] = { total: num(s.total), profit: profit[k] ?? num(s.profit) }
      })
    })
    return map
  } catch (err) {
    console.error('Week heatmap fetch error:', err)
    return null
  }
}

// Month / year buckets come straight from the main report.
const rowMap = computed(() => {
  const len = mode.value === 'year' ? 7 : 10
  const m = {}
  chartRows.value.forEach((r) => { m[String(r.date).slice(0, len)] = r })
  return m
})

const heatCells = computed(() => {
  const out = []

  if (mode.value === 'year') {
    const y = selectedYear.value
    for (let mo = 1; mo <= 12; mo++) {
      const key = `${y}-${pad(mo)}`
      const d = new Date(y, mo - 1, 1)
      const r = rowMap.value[key]
      out.push({
        key,
        label: d.toLocaleDateString('en-US', { month: 'short' }),
        title: d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }),
        total: r ? r.total : 0,
        profit: r ? r.profit : 0,
        future: key > currentMonthValue,
        today: key === currentMonthValue,
      })
    }
  } else if (mode.value === 'month') {
    if (!selectedMonth.value) return []
    const [yy, mm] = selectedMonth.value.split('-').map(Number)
    const count = new Date(yy, mm, 0).getDate()
    const lead = new Date(yy, mm - 1, 1).getDay()
    for (let i = 0; i < lead; i++) out.push(null)
    for (let d = 1; d <= count; d++) {
      const key = `${yy}-${pad(mm)}-${pad(d)}`
      const dt = new Date(yy, mm - 1, d)
      const r = rowMap.value[key]
      out.push({
        key,
        label: String(d),
        title: dt.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }),
        total: r ? r.total : 0,
        profit: r ? r.profit : 0,
        future: key > currentDayValue,
        today: key === currentDayValue,
      })
    }
  } else {
    if (!selectedDay.value) return []
    const start = weekStartOf(selectedDay.value)
    for (let i = 0; i < 7; i++) {
      const dt = new Date(start)
      dt.setDate(start.getDate() + i)
      const key = toDayStr(dt)
      const r = weekMap.value[key]
      out.push({
        key,
        label: dt.toLocaleDateString('en-US', { weekday: 'short' }),
        num: String(dt.getDate()),
        title: dt.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }),
        total: r ? r.total : 0,
        profit: r ? r.profit : 0,
        future: key > currentDayValue,
        today: key === currentDayValue,
      })
    }
  }

  const metric = calMetric.value
  const valueOf = (c) => (metric === 'profit' ? c.profit : c.total)
  const max = Math.max(0, ...out.filter(Boolean).map(valueOf))
  return out.map((c) => {
    if (!c) return null
    const v = valueOf(c)
    const ratio = max > 0 ? v / max : 0
    return { ...c, v, level: v <= 0 ? 0 : ratio > 0.75 ? 4 : ratio > 0.5 ? 3 : ratio > 0.25 ? 2 : 1 }
  })
})

const heatReal    = computed(() => heatCells.value.filter(Boolean))
const heatHasData = computed(() => heatReal.value.some((c) => c.total > 0 || c.profit > 0))
const heatSum     = computed(() => heatReal.value.reduce((a, c) => a + c.total, 0))

const heatRangeWord = computed(() => ({ day: 'week', month: 'month', year: 'year' }[mode.value]))
const heatCellWord  = computed(() => (mode.value === 'year' ? 'month' : 'day'))

const heatTitle = computed(() => {
  if (mode.value === 'day') return 'Week at a glance'
  return `${periodLabel.value || (mode.value === 'year' ? 'Year' : 'Month')} at a glance`
})
const heatSub = computed(() => {
  const what = mode.value === 'year' ? 'months' : 'days'
  const pick = `Select a ${heatCellWord.value} for its numbers.`
  if (mode.value === 'day' && selectedDay.value) {
    const s = weekStartOf(selectedDay.value)
    const e = new Date(s)
    e.setDate(e.getDate() + 6)
    const f = (d) => d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    return `${f(s)} – ${f(e)}. Darker ${what} sold more. ${pick}`
  }
  return `Darker ${what} sold more. ${pick}`
})

const heatActive = computed(() => {
  const real = heatReal.value
  if (!real.length) return null
  const byKey = (k) => real.find((c) => c.key === k)
  let c = heatSelKey.value ? byKey(heatSelKey.value) : null
  if (!c && mode.value === 'day') c = byKey(selectedDay.value)
  if (!c) {
    // Otherwise open on the strongest cell so the panel is never empty.
    c = real.reduce((best, r) => (r.total > 0 && (!best || r.total > best.total) ? r : best), null)
  }
  return c || real[0]
})

const heatDetail = computed(() => {
  const c = heatActive.value
  if (!c) return null
  const withSales = heatReal.value.filter((r) => r.total > 0)
  const avg = withSales.length ? withSales.reduce((a, r) => a + r.total, 0) / withSales.length : 0
  return {
    cell: c,
    cost: Math.max(c.total - c.profit, 0),
    margin: c.total ? ((c.profit / c.total) * 100).toFixed(1) : '0.0',
    profitPct: c.total ? Math.min(100, Math.max(0, (c.profit / c.total) * 100)) : 0,
    share: heatSum.value ? ((c.total / heatSum.value) * 100).toFixed(1) : '0.0',
    vsAvg: avg ? ((c.total - avg) / avg) * 100 : 0,
  }
})

// Drill down: month → that day's report, year → that month's report.
const canOpenHeat = computed(() => {
  const c = heatActive.value
  if (!c || c.future) return false
  return mode.value === 'day' ? c.key !== selectedDay.value : true
})
const openHeatLabel = computed(() => (mode.value === 'year' ? 'Open this month' : 'Open this day'))

function selectHeat(key) { heatSelKey.value = key }

async function openHeatCell() {
  const c = heatActive.value
  if (!c) return
  if (mode.value === 'year') {
    selectedMonth.value = c.key
    syncPickers('month')
    mode.value = 'month'
  } else {
    selectedDay.value = c.key
    syncPickers('day')
    mode.value = 'day'
  }
  await loadReports()
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

    const [res, prev, week] = await Promise.all([
      api.get('/api/reports', { params: { ...params, include_sales: 0 } }),
      fetchPrevious(),
      mode.value === 'day' ? fetchWeekMap() : Promise.resolve({}),
    ])
    if (seq !== requestSeq) return

    const d = res.data
    salesActivityRaw.value     = d.sales_activity    ?? salesActivityRaw.value
    inventorySummary.value     = d.inventory_summary ?? inventorySummary.value
    topSellingItems.value      = d.top_selling       ?? []
    monthlySales.value         = d.monthly_sales     ?? []
    monthlyProfitSeries.value  = d.monthly_profit    ?? d.daily_profit ?? []
    salesTotal.value           = num(d.monthly_total)
    serviceSales.value         = d.service_sales === undefined || d.service_sales === null ? null : num(d.service_sales)
    periodLabel.value          = d.selected_period?.label ?? label

    weekMap.value    = week || {}
    weekFailed.value = week === null

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
    mixSel.value = null
    hoverSlice.value = null
    hoverRow.value = null
    heatSelKey.value = null
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
    const tone = invTone(item)
    const flag = tone === 'alert' ? '   ⚠ restock now' : tone === 'warn' ? '   ⚠ reorder soon' : ''
    L.push(kv(item.label, `${formatNumber(item.value)}${flag}`))
  })
  if (stockCoverage.value) {
    L.push(kv('Well stocked', `${stockCoverage.value.active} of ${stockCoverage.value.total} items (${stockCoverage.value.pct}%)`))
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
  if (stockCoverage.value) rows.push(['Well stocked (%)', stockCoverage.value.pct])

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
    closePicker()
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
  document.addEventListener('pointerdown', onDocPointer)
  document.addEventListener('keydown', onDocKey)
  await loadReports()
  // Rebuild the chart when the app theme toggles so its colours follow.
  themeObserver = new MutationObserver(() => buildChart())
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] })
})

onBeforeUnmount(() => {
  requestSeq++
  cancelAnimationFrame(animFrame)
  document.removeEventListener('pointerdown', onDocPointer)
  document.removeEventListener('keydown', onDocKey)
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
  --rp-amber:       #f59e0b;
  --rp-amber-soft:  rgba(245,158,11,0.14);

  --rp-green-icon:  #D1FAE5;
  --rp-blue-icon:   #DBEAFE;
  --rp-violet-icon: #E0E7FF;
  --rp-red-icon:    #FFE4E6;
  --rp-amber-icon:  #FEF3C7;

  --rp-ease: cubic-bezier(.16, 1, .3, 1);
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
  --rp-amber-icon:  rgba(245,158,11,0.18);
  --rp-green-soft:  rgba(16,185,129,0.16);
  --rp-red-soft:    rgba(244,63,94,0.16);
  --rp-amber-soft:  rgba(245,158,11,0.16);
}

/* Visible keyboard focus everywhere */
.reports button:focus-visible,
.reports select:focus-visible,
.reports a:focus-visible,
.reports input:focus-visible { outline: 2px solid var(--rp-accent); outline-offset: 2px; }

/* Print sheet — hidden on screen */
.print-sheet { display: none; }

/* ── PAGE HEADER ── */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px; }
.page-title  { font-size: 26px; font-weight: 800; color: var(--rp-text-primary); letter-spacing: -.03em; margin: 0; }
.page-sub    { display: flex; align-items: center; gap: 8px; margin: 6px 0 0; font-size: 14px; font-weight: 600; color: var(--rp-text-secondary); }
.chip { font-size: 11.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }
.chip--live { color: var(--rp-green); background: var(--rp-green-soft); }
.chip--accent { color: var(--rp-accent); background: var(--rp-accent-soft); border: 1px solid var(--rp-accent-border); font-size: 10.5px; padding: 2px 10px; }

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
  position: relative; z-index: 5;
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

/* ── CALENDAR PICKER ── */
.picker { position: relative; }
.picker-trigger {
  display: inline-flex; align-items: center; gap: 10px;
  height: 36px; min-width: 200px; padding: 0 12px 0 13px;
  border: 1.5px solid var(--rp-border-strong);
  background: var(--rp-surface);
  border-radius: 11px;
  font-family: inherit; font-size: 13.5px; font-weight: 700;
  color: var(--rp-text-primary);
  cursor: pointer;
  transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
}
.picker-trigger > span { flex: 1; text-align: left; white-space: nowrap; }
.picker-ico { font-size: 13px; color: var(--rp-accent); }
.picker-caret { font-size: 10px; color: var(--rp-text-muted); transition: transform .22s var(--rp-ease); }
.picker-trigger:hover { border-color: var(--rp-accent-border); background: var(--rp-surface-raised); }
.picker-trigger--open { border-color: var(--rp-accent); box-shadow: 0 0 0 3px var(--rp-accent-soft); }
.picker-trigger--open .picker-caret { transform: rotate(180deg); }

.picker-pop {
  position: absolute; top: calc(100% + 8px); left: 0; z-index: 40;
  width: 300px; max-width: calc(100vw - 32px);
  padding: 14px;
  background: var(--rp-surface);
  border: 1px solid var(--rp-border-strong);
  border-radius: 18px;
  box-shadow: 0 28px 56px -18px rgba(0,0,0,.5), 0 4px 14px rgba(0,0,0,.14);
  transform-origin: top left;
}
.pop-enter-active, .pop-leave-active { transition: opacity .16s ease, transform .2s var(--rp-ease); }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateY(-6px) scale(.97); }

.pk-head { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 12px; }
.pk-nav {
  width: 32px; height: 32px; flex-shrink: 0; border-radius: 10px; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  border: 1px solid transparent; background: var(--rp-surface-sunken);
  color: var(--rp-text-secondary); font-size: 11px;
  transition: background-color .15s ease, color .15s ease, border-color .15s ease;
}
.pk-nav:hover:not(:disabled) { background: var(--rp-accent-soft); color: var(--rp-accent); border-color: var(--rp-accent-border); }
.pk-nav:disabled { opacity: .35; cursor: not-allowed; }
.pk-nav--hide { visibility: hidden; }
.pk-title {
  flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  height: 32px; border: none; border-radius: 10px; background: transparent; cursor: pointer;
  font: inherit; font-size: 14px; font-weight: 800; letter-spacing: -.01em;
  color: var(--rp-text-primary);
  transition: background-color .15s ease;
}
.pk-title i { font-size: 9px; color: var(--rp-text-muted); }
.pk-title:hover:not(:disabled) { background: var(--rp-surface-raised); }
.pk-title:disabled { cursor: default; }

.pk-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 3px; }
.pk-dow { text-align: center; padding: 4px 0 6px; font-size: 10.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--rp-text-faint); }
.pk-blank { display: block; }
.pk-day {
  position: relative; aspect-ratio: 1; min-width: 0; padding: 0;
  border: 1px solid transparent; border-radius: 10px; background: transparent; cursor: pointer;
  font: inherit; font-size: 13px; font-weight: 600; font-variant-numeric: tabular-nums;
  color: var(--rp-text-primary);
  transition: background-color .14s ease, color .14s ease, border-color .14s ease, transform .14s var(--rp-ease);
}

.pk-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; }
.pk-cell {
  position: relative; height: 46px; padding: 0;
  border: 1px solid transparent; border-radius: 12px; background: transparent; cursor: pointer;
  font: inherit; font-size: 13.5px; font-weight: 700; font-variant-numeric: tabular-nums;
  color: var(--rp-text-primary);
  transition: background-color .14s ease, color .14s ease, border-color .14s ease, transform .14s var(--rp-ease);
}

.pk-day:hover:not(:disabled):not(.pk-on),
.pk-cell:hover:not(:disabled):not(.pk-on) { background: var(--rp-accent-soft); color: var(--rp-accent); transform: scale(1.05); }
.pk-day:disabled, .pk-cell:disabled { opacity: .28; cursor: not-allowed; }
.pk-today { border-color: var(--rp-accent-border); color: var(--rp-accent); }
.pk-today::after {
  content: ''; position: absolute; bottom: 5px; left: 50%; width: 4px; height: 4px; margin-left: -2px;
  border-radius: 50%; background: var(--rp-accent);
}
.pk-on { background: var(--rp-accent); border-color: var(--rp-accent); color: #fff; box-shadow: 0 6px 14px -6px var(--rp-accent); }
.pk-on::after { background: #fff; }

.pk-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--rp-border); }
.pk-link {
  display: inline-flex; align-items: center; gap: 7px; border: none; background: transparent; cursor: pointer;
  font: inherit; font-size: 12.5px; font-weight: 700; color: var(--rp-accent);
  padding: 6px 9px; border-radius: 8px;
  transition: background-color .15s ease;
}
.pk-link i { font-size: 11px; }
.pk-link:hover:not(:disabled) { background: var(--rp-accent-soft); }
.pk-link:disabled { opacity: .4; cursor: default; }
.pk-link--muted { color: var(--rp-text-muted); }
.pk-link--muted:hover:not(:disabled) { background: var(--rp-surface-raised); color: var(--rp-text-primary); }

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

/* ── HEATMAP ── */
.heat-layout { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); gap: 28px; align-items: start; }

.heat { --lv: 234,88,12; display: grid; gap: 7px; min-width: 0; }
.heat--profit { --lv: 16,185,129; }
.heat--month, .heat--day { grid-template-columns: repeat(7, minmax(0, 1fr)); }
.heat--year { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }

.heat-dow { text-align: center; font-size: 11px; font-weight: 600; letter-spacing: .04em; color: var(--rp-text-faint); padding-bottom: 4px; }

.heat-cell {
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;
  min-width: 0; padding: 4px 2px;
  border: 1px solid transparent; border-radius: 10px; cursor: pointer;
  font: inherit; font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums;
  color: var(--rp-text-secondary);
  transition: transform .15s var(--rp-ease), border-color .15s ease, box-shadow .15s ease, background-color .3s ease;
}
.heat--month .heat-cell { aspect-ratio: 1.15; }
.heat--day .heat-cell   { min-height: 124px; gap: 4px; }
.heat--year .heat-cell  { min-height: 88px; gap: 4px; }
.heat-cell:hover:not(:disabled) { transform: scale(1.06); border-color: rgb(var(--lv)); box-shadow: 0 6px 14px -6px rgba(var(--lv), .6); z-index: 1; }
.heat-cell:disabled { cursor: default; opacity: .35; }
.heat-cell--today { border-color: var(--rp-text-secondary); }
.heat-cell--sel { border-color: rgb(var(--lv)); box-shadow: 0 0 0 3px rgba(var(--lv), .28); color: var(--rp-text-primary); }

.hc-dow   { font-size: 11px; font-weight: 600; letter-spacing: .03em; opacity: .85; }
.hc-num   { font-size: 24px; font-weight: 800; letter-spacing: -.03em; line-height: 1; }
.hc-month { font-size: 14px; font-weight: 700; letter-spacing: -.01em; }
.hc-amt   { font-size: 11.5px; font-weight: 600; opacity: .9; white-space: nowrap; }

.lv0 { background: var(--rp-surface-sunken); }
.lv1 { background: rgba(var(--lv), .16); }
.lv2 { background: rgba(var(--lv), .34); }
.lv3 { background: rgba(var(--lv), .58); color: var(--rp-text-primary); }
.lv4 { background: rgba(var(--lv), .88); color: #fff; }

.heat-scale { grid-column: 1 / -1; display: flex; align-items: center; justify-content: flex-end; gap: 4px; margin-top: 8px; font-size: 11px; color: var(--rp-text-muted); }
.heat-scale i { width: 14px; height: 14px; border-radius: 4px; display: block; }
.heat-scale span:first-child { margin-right: 3px; }
.heat-scale span:last-child { margin-left: 3px; }

.heat-day { padding: 20px; background: var(--rp-surface-raised); border: 1px solid var(--rp-border); border-radius: 16px; min-width: 0; }
.heat-day-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; min-height: 28px; }
.heat-day-title { margin: 0; font-size: 13.5px; font-weight: 700; color: var(--rp-text-primary); }
.heat-day-head .link-btn { margin-left: auto; font-size: 12.5px; }
.heat-day-value { margin: 8px 0 14px; font-size: 30px; font-weight: 800; color: var(--rp-text-primary); letter-spacing: -.035em; font-variant-numeric: tabular-nums; }
.heat-muted { margin: 0; font-size: 12.5px; color: var(--rp-text-muted); }

.split-bar { display: flex; gap: 3px; height: 10px; border-radius: 10px; overflow: hidden; }
.split-seg { display: block; height: 100%; cursor: help; transition: width 1s var(--rp-ease); }
.split-seg--profit { background: linear-gradient(90deg, #059669, var(--rp-green)); border-radius: 10px 3px 3px 10px; }
.split-seg--cost { background: linear-gradient(90deg, var(--rp-accent), #FB923C); border-radius: 3px 10px 10px 3px; }
.split-legend { display: flex; gap: 16px; margin-top: 9px; font-size: 11.5px; color: var(--rp-text-muted); }
.split-legend span { display: inline-flex; align-items: center; gap: 6px; }
.sl { width: 8px; height: 8px; border-radius: 50%; display: block; }
.sl--profit { background: var(--rp-green); }
.sl--cost { background: var(--rp-accent); }

.facts { margin: 16px 0 0; border-top: 1px solid var(--rp-border); }
.facts > div { display: flex; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--rp-border); }
.facts > div:last-child { border-bottom: none; padding-bottom: 0; }
.facts dt { font-size: 12.5px; color: var(--rp-text-muted); }
.facts dd { margin: 0; font-size: 13px; font-weight: 700; color: var(--rp-text-primary); font-variant-numeric: tabular-nums; }
.facts dd.pos { color: var(--rp-green); }
.facts dd.neg { color: var(--rp-red); }

.heat-note { display: flex; align-items: center; gap: 8px; margin: 14px 0 0; font-size: 12.5px; color: var(--rp-text-muted); }
.heat-note--warn { margin: 0 0 14px; padding: 10px 12px; border-radius: 10px; color: var(--rp-amber); background: var(--rp-amber-soft); font-weight: 600; }

/* ── WHERE SALES COME FROM + INVENTORY ── */
.content-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 20px; align-items: start; }

.mix { container-type: inline-size; display: flex; flex-direction: column; gap: 18px; }
.mix-insight { display: flex; align-items: flex-start; gap: 10px; margin: 0; padding: 12px 14px; border-radius: 12px; background: var(--rp-accent-soft); border: 1px solid var(--rp-accent-border); font-size: 12.5px; line-height: 1.55; color: var(--rp-text-secondary); }
.mix-insight i { margin-top: 2px; font-size: 12px; color: var(--rp-accent); }
.mix-body { display: grid; grid-template-columns: 200px minmax(0, 1fr); gap: 24px; align-items: start; }
@container (max-width: 520px) { .mix-body { grid-template-columns: minmax(0, 1fr); } .donut { margin: 0 auto; } }

.donut { position: sticky; top: 16px; width: 200px; max-width: 100%; aspect-ratio: 1; }
.donut svg { display: block; width: 100%; height: 100%; overflow: visible; filter: drop-shadow(0 6px 14px rgba(16,24,40,.12)); }
.donut-track { fill: none; stroke: var(--rp-surface-sunken); stroke-width: 22; }
.donut-seg { fill: none; stroke-width: 22; stroke-linecap: butt; cursor: pointer; outline: none; transition: stroke-dasharray 1s var(--rp-ease), stroke-width .2s ease, opacity .2s ease; }
.donut-seg:focus-visible { stroke-width: 28; }
.donut-seg.is-on { stroke-width: 28; }
.donut-seg.is-dim { opacity: .28; }
.donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; padding: 0 46px; text-align: center; pointer-events: none; }
.donut-center strong { font-size: 22px; font-weight: 800; color: var(--rp-text-primary); letter-spacing: -.03em; font-variant-numeric: tabular-nums; }
.dc-name { max-width: 100%; font-size: 12px; font-weight: 600; color: var(--rp-text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dc-sub { font-size: 11px; color: var(--rp-text-muted); line-height: 1.3; }

.mix-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.mix-list--all { max-height: 460px; overflow-y: auto; padding-right: 4px; scrollbar-width: thin; }
.mix-list li { border: 1px solid transparent; border-radius: 12px; transition: background-color .2s ease, border-color .2s ease; }
.mix-list li.mix-item--sel { background: var(--rp-accent-soft); border-color: var(--rp-accent-border); }
.mix-row { display: flex; flex-direction: column; gap: 9px; width: 100%; padding: 11px 13px; border: none; border-radius: 12px; background: transparent; font: inherit; color: inherit; text-align: left; cursor: pointer; transition: background-color .2s ease; }
.mix-row:hover { background: var(--rp-surface-raised); }
.mix-row--on { background: var(--rp-surface-raised); }
.mix-top { display: flex; align-items: center; gap: 10px; min-width: 0; }
.mix-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 3px var(--rp-surface); }
.mix-name { flex: 1; min-width: 0; font-size: 13px; font-weight: 600; color: var(--rp-text-primary); overflow-wrap: anywhere; }
.mix-amt { font-size: 12.5px; color: var(--rp-text-muted); white-space: nowrap; font-variant-numeric: tabular-nums; }
.mix-pct { width: 48px; text-align: right; font-size: 12.5px; font-weight: 700; color: var(--rp-text-secondary); font-variant-numeric: tabular-nums; }
.mix-bar { display: block; height: 6px; border-radius: 6px; background: var(--rp-surface-sunken); overflow: hidden; }
.mix-bar-fill { display: block; height: 100%; width: 0; border-radius: 6px; transition: width 1s var(--rp-ease); }
.mix-detail { display: grid; grid-template-columns: repeat(auto-fit, minmax(96px, 1fr)); gap: 10px; margin: 0; padding: 2px 13px 13px 33px; }
.mix-detail dt { font-size: 11.5px; color: var(--rp-text-muted); }
.mix-detail dd { margin: 2px 0 0; font-size: 13px; font-weight: 700; color: var(--rp-text-primary); font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }

.empty {
  margin: 0; padding: 32px 12px;
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  text-align: center; font-size: 13px; color: var(--rp-text-muted);
}
.empty i { font-size: 22px; color: var(--rp-text-faint); }

/* Inventory */
.inv-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
.inv-row { border-bottom: 1px solid var(--rp-border); }
.inv-row:last-child { border-bottom: none; }
.inv-link {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 6px; margin: 0 -6px; border-radius: 10px;
  color: inherit; text-decoration: none;
  transition: background-color .15s ease;
}
a.inv-link { cursor: pointer; }
a.inv-link:hover { background: var(--rp-surface-raised); }
.inv-icon {
  width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 13px;
  background: var(--rp-violet-icon); color: var(--rp-violet);
}
.inv-text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.inv-label { font-size: 13.5px; font-weight: 600; color: var(--rp-text-secondary); }
.inv-hint { font-size: 11.5px; font-weight: 600; color: var(--rp-text-muted); }
.inv-value { font-size: 17px; font-weight: 800; color: var(--rp-text-primary); letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
.inv-go { font-size: 9px; color: var(--rp-text-faint); transition: transform .2s var(--rp-ease), color .15s ease; }
a.inv-link:hover .inv-go { transform: translateX(3px); color: var(--rp-accent); }

.inv-row--warn .inv-icon  { background: var(--rp-amber-icon); color: var(--rp-amber); }
.inv-row--warn .inv-value,
.inv-row--warn .inv-hint  { color: var(--rp-amber); }
.inv-row--alert .inv-icon  { background: var(--rp-red-icon); color: var(--rp-red); }
.inv-row--alert .inv-value,
.inv-row--alert .inv-hint  { color: var(--rp-red); }

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
  .heat-layout { grid-template-columns: minmax(0, 1fr); gap: 20px; }
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
  .picker { flex: 1; min-width: 0; }
  .picker-trigger { width: 100%; min-width: 0; }
  .picker-pop { width: auto; right: 0; }
  .period-status { margin-left: 0; justify-content: space-between; }
  .stat--hero { padding: 18px 16px; }
  .hero-cols { gap: 14px; }
  .hero-col--profit { padding-left: 14px; }
  /* KPI cards: icon above the text so the numbers get the full width */
  .stat:not(.stat--hero) { flex-direction: column; align-items: flex-start; gap: 10px; padding: 16px; }
  .stat:not(.stat--hero) .stat-body { width: 100%; flex: none; }
  .card { padding: 18px 16px 16px; }
  .chart-area { height: 240px; }
  .heat { gap: 5px; }
  .heat--day .heat-cell { min-height: 92px; }
  .hc-num { font-size: 18px; }
  .hc-amt { font-size: 10px; }
  .heat--year { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .heat-day { padding: 16px; }
  .mix-amt { display: none; }
  .donut { position: static; }
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
    margin: 17mm 14mm 18mm;
    @top-left     { content: "Sheem Steel · POS & Inventory"; font: 600 8px 'Inter', Arial, sans-serif; color: #94a3b8; }
    @top-right    { content: "Sales & Inventory Report"; font: 600 8px 'Inter', Arial, sans-serif; color: #94a3b8; }
    @bottom-left  { content: "Confidential · For internal use"; font: 600 8px 'Inter', Arial, sans-serif; color: #94a3b8; }
    @bottom-right { content: "Page " counter(page) " of " counter(pages); font: 700 8.5px 'Inter', Arial, sans-serif; color: #475569; }
  }
  /* The letterhead already carries the branding on page 1 */
  @page :first {
    @top-left  { content: none; }
    @top-right { content: none; }
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
  .print-sheet { font-size: 10.5px; line-height: 1.5; color: #0f172a; }
  .print-sheet p, .print-sheet h1, .print-sheet h2, .print-sheet dl, .print-sheet dd, .print-sheet ul { margin: 0; }
  .print-sheet b { font-weight: 700; }

  /* ── Letterhead ── */
  .ps-band { height: 6px; border-radius: 3px; background: linear-gradient(90deg, #0f172a 0%, #0f172a 55%, #EA580C 55%, #F59E0B 100%); }
  .ps-head-row { display: flex; justify-content: space-between; align-items: center; gap: 24px; padding: 14px 0 14px; }
  .ps-brand { display: flex; align-items: center; gap: 12px; }
  .ps-logo {
    display: inline-flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; border-radius: 9px;
    font-size: 15px; font-weight: 800; letter-spacing: .02em;
    color: #fff; background: #EA580C;
  }
  .ps-brand-name { font-size: 19px; font-weight: 800; letter-spacing: -.02em; color: #0f172a; line-height: 1.1; }
  .ps-brand-tag  { margin-top: 3px !important; font-size: 9.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }

  .ps-meta { display: grid; grid-template-columns: auto auto; column-gap: 20px; row-gap: 3px; padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
  .ps-meta > div { display: contents; }
  .ps-meta dt { font-size: 8px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; align-self: center; }
  .ps-meta dd { font-size: 10px; font-weight: 600; color: #0f172a; text-align: right; white-space: nowrap; }

  .ps-title-row {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 14px 18px; border-radius: 8px; border-left: 5px solid #EA580C;
    background: #0f172a; color: #fff;
  }
  .ps-kicker { font-size: 8.5px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #fdba74; }
  .ps-title  { margin-top: 3px !important; font-size: 21px; font-weight: 800; letter-spacing: -.02em; color: #fff; line-height: 1.15; }
  .ps-title-right { text-align: right; }
  .ps-period { font-size: 14px; font-weight: 700; color: #fff; }
  .ps-chip {
    display: inline-block; margin-top: 5px; padding: 2px 9px; border-radius: 10px;
    font-size: 8.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
  }
  .ps-chip--live   { color: #064e3b; background: #6ee7b7; }
  .ps-chip--closed { color: #334155; background: #e2e8f0; }

  /* ── Sections ── */
  .ps-section { margin-top: 22px; }
  .ps-section--keep { break-inside: avoid; page-break-inside: avoid; }
  .ps-h2 {
    display: flex; align-items: center; gap: 9px;
    padding-bottom: 7px; margin-bottom: 11px;
    font-size: 13px; font-weight: 800; letter-spacing: -.01em; color: #0f172a;
    border-bottom: 1.5px solid #0f172a;
    break-after: avoid; page-break-after: avoid;
  }
  .ps-h2 small { margin-left: auto; font-size: 9px; font-weight: 600; color: #94a3b8; }
  .ps-num {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 22px; height: 20px; padding: 0 5px; border-radius: 5px;
    font-size: 9px; font-weight: 800; letter-spacing: .04em; color: #fff; background: #EA580C;
  }
  .ps-empty { padding: 14px; text-align: center; color: #94a3b8; border: 1px dashed #cbd5e1; border-radius: 6px; }
  .ps-lead { margin-bottom: 12px !important; padding: 10px 14px; border-left: 3px solid #EA580C; border-radius: 0 6px 6px 0; background: #f8fafc; font-size: 10.5px; line-height: 1.6; color: #334155; }

  /* ── KPI tiles ── */
  .ps-hero { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; }
  .ps-kpis { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-top: 9px; }
  .ps-hero-kpi, .ps-kpi {
    --t: #EA580C; --ts: #FFF1E8;
    break-inside: avoid; page-break-inside: avoid;
  }
  .ps-hero-kpi { padding: 12px 14px 13px; border-radius: 8px; background: var(--ts); border-top: 3px solid var(--t); }
  .ps-kpi { padding: 8px 10px 9px; border-radius: 6px; background: #fff; border: 1px solid #e2e8f0; border-left: 3px solid var(--t); }
  .ps-kpi--orange { --t: #EA580C; --ts: #FFF1E8; }
  .ps-kpi--green  { --t: #059669; --ts: #E7F8F0; }
  .ps-kpi--teal   { --t: #0D9488; --ts: #E4F6F4; }
  .ps-kpi--amber  { --t: #D97706; --ts: #FEF3E2; }
  .ps-kpi--violet { --t: #6D5BD0; --ts: #EFEDFC; }
  .ps-kpi--blue   { --t: #2563EB; --ts: #E8F0FE; }
  .ps-kpi--rose   { --t: #DB2777; --ts: #FDEBF3; }
  .ps-kpi--cyan   { --t: #0891B2; --ts: #E3F5F9; }
  .ps-kpi-label { font-size: 8px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--t); }
  .ps-hero-value { margin-top: 4px !important; font-size: 22px; font-weight: 800; letter-spacing: -.03em; color: #0f172a; white-space: nowrap; line-height: 1.15; }
  .ps-kpi-value { margin-top: 3px !important; font-size: 13.5px; font-weight: 800; letter-spacing: -.02em; color: #0f172a; white-space: nowrap; }
  .ps-kpi-value--text { font-size: 11px; white-space: normal; overflow-wrap: anywhere; line-height: 1.25; letter-spacing: -.01em; }
  .ps-kpi-sub { margin-top: 2px !important; font-size: 8.5px; color: #64748b; line-height: 1.35; }

  /* ── Callouts ── */
  .ps-callout { margin-top: 10px !important; padding: 8px 12px; border-radius: 6px; font-size: 10px; border-left: 4px solid; break-inside: avoid; }
  .ps-callout b { margin-right: 6px; font-size: 8.5px; letter-spacing: .07em; text-transform: uppercase; }
  .ps-callout--up    { color: #065f46; background: #E7F8F0; border-color: #059669; }
  .ps-callout--down  { color: #9f1239; background: #FFE4E6; border-color: #e11d48; }
  .ps-callout--flat  { color: #475569; background: #f1f5f9; border-color: #94a3b8; }
  .ps-callout--alert { margin: 0 0 10px !important; color: #9a3412; background: #FFF4E5; border-color: #EA580C; }

  /* ── Chart ── */
  .ps-legend { display: flex; gap: 14px; margin-bottom: 6px; font-size: 9.5px; font-weight: 600; color: #475569; }
  .ps-legend span { display: inline-flex; align-items: center; gap: 5px; }
  .ps-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
  .ps-dot--orange { background: #EA580C; }
  .ps-dot--green  { background: #10b981; }
  .ps-chart { display: block; width: 100%; height: auto; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; }

  /* ── Tables ── */
  .ps-table { width: 100%; border-collapse: collapse; font-size: 10px; }
  .ps-table thead { display: table-header-group; }
  .ps-table tfoot { display: table-footer-group; }
  .ps-table tr { break-inside: avoid; page-break-inside: avoid; }
  .ps-table th {
    padding: 7px 8px; text-align: left;
    font-size: 8px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase;
    color: #fff; background: #0f172a;
  }
  .ps-table th:first-child { border-top-left-radius: 5px; }
  .ps-table th:last-child  { border-top-right-radius: 5px; }
  .ps-table th.num { text-align: right; }
  .ps-table td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
  .ps-table tbody tr:nth-child(even) td { background: #f8fafc; }
  .ps-table .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
  .ps-table .c-rank { width: 30px; text-align: center; }
  .ps-table .c-share { width: 120px; }
  .ps-table .ps-row--rest td { color: #64748b; font-style: italic; background: #fff !important; border-bottom: 1px dashed #cbd5e1; }
  .ps-table tfoot td {
    padding: 8px 8px; font-weight: 800; color: #0f172a;
    background: #f1f5f9; border-top: 2px solid #0f172a; border-bottom: none;
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
  .ps-share-bar i { display: block; height: 100%; border-radius: 5px; background: #0f172a; }
  .ps-share-bar i.ps-share-bar--lead { background: #EA580C; }
  .ps-share b { width: 34px; text-align: right; font-size: 9.5px; color: #475569; font-variant-numeric: tabular-nums; }

  /* ── Inventory ── */
  .ps-inv { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
  .ps-inv-box { padding: 9px 11px; border-radius: 6px; background: #fff; border: 1px solid #e2e8f0; border-top: 3px solid #6D5BD0; }
  .ps-inv-box p { font-size: 8px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #6D5BD0; }
  .ps-inv-box strong { display: block; margin-top: 3px; font-size: 17px; font-weight: 800; letter-spacing: -.02em; color: #0f172a; }
  .ps-inv-box--warn  { background: #FEF3E2; border-color: #FCD9A8; border-top-color: #D97706; }
  .ps-inv-box--warn p, .ps-inv-box--warn strong { color: #B45309; }
  .ps-inv-box--alert { background: #FFE4E6; border-color: #FBB6C0; border-top-color: #e11d48; }
  .ps-inv-box--alert p, .ps-inv-box--alert strong { color: #be123c; }
  .ps-cover { margin-top: 11px; }
  .ps-cover-head { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 9.5px; color: #64748b; }
  .ps-cover-head strong { color: #0f172a; }
  .ps-cover-track { height: 6px; border-radius: 6px; background: #e2e8f0; }
  .ps-cover-track i { display: block; height: 100%; border-radius: 6px; background: #10b981; }

  /* ── Notes & sign-off ── */
  .ps-notes { padding: 0 0 0 16px; font-size: 9.5px; line-height: 1.6; color: #475569; list-style: disc; }
  .ps-notes li { margin-bottom: 2px; }
  .ps-notes b { color: #0f172a; }
  .ps-sign { display: grid; grid-template-columns: 1.3fr 1.3fr 0.8fr; gap: 28px; margin-top: 30px; }
  .ps-sign span { display: block; height: 22px; border-bottom: 1px solid #0f172a; }
  .ps-sign p { margin-top: 4px !important; font-size: 8.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }

  /* ── End of report ── */
  .ps-end {
    display: flex; justify-content: space-between; gap: 12px;
    margin-top: 24px; padding-top: 8px;
    font-size: 8.5px; color: #94a3b8; border-top: 2px solid #0f172a;
  }
}
</style>