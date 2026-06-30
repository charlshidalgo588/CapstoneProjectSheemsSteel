<template>
  <Layout title="Reports">
    <div id="reports-print-area" class="reports">

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

      <!-- SALES ACTIVITY -->
      <div class="section">
        <div class="section-header">
          <p class="card-eyebrow">Overview</p>
          <h2 class="section-title">Sales Activity &mdash; {{ periodLabel }}</h2>
        </div>
        <div class="kpi-strip">
          <div
            v-for="(card, idx) in salesActivity"
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
                <p class="kpi-value" :class="kpiAccent(idx).valueClass">{{ card.value }}</p>
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
        <!--
          FIX: removed "Quantity to Receive" — it was hardcoded to 0 with
          no backing query, so it could never reflect anything real.
          Grid is now 4 boxes instead of 5.
        -->
        <div class="kpi-strip">
          <div
            v-for="box in inventorySummaryBoxes"
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
                <p class="kpi-value" :class="box.isRed ? 'kpi-value--red' : 'kpi-value--violet'">{{ box.value }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar" :class="box.isRed ? 'kpi-accent-bar--red' : 'kpi-accent-bar--violet'"></div>
          </div>
        </div>
      </div>

      <!-- TOP SELLING ITEMS -->
      <div class="section">
        <div class="card">
          <div class="card-header">
            <div>
              <p class="card-eyebrow">Performance</p>
              <h2 class="card-title">Top Selling Items &mdash; {{ periodLabel }}</h2>
            </div>
            <div class="export-actions">
              <button id="copyBtn"  class="export-btn"><i class="fa-regular fa-copy"></i> Copy</button>
              <button id="csvBtn"   class="export-btn"><i class="fa-solid fa-file-csv"></i> CSV</button>
              <button id="printBtn" class="export-btn export-btn--primary"><i class="fa-solid fa-print"></i> Print</button>
            </div>
          </div>

          <!-- DataTables length + search mount point -->
          <div class="table-controls" id="datatableControls"></div>

          <div class="table-wrap">
            <table id="topSellingItemsTable" class="data-table">
              <thead>
                <tr>
                  <th>Product Name</th>
                  <th>Quantity Sold</th>
                  <th>Total Sales</th>
                  <th>Category</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in topSellingItems" :key="item.id">
                  <td class="cell-product">{{ item.ProductName }}</td>
                  <td class="cell-muted">{{ item.total_quantity }}</td>
                  <td class="cell-sales">₱{{ price(item.total_sales) }}</td>
                  <td><span class="category-pill">{{ item.CategoryName }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
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
            <div class="monthly-total">
              <span class="monthly-total-label">Total Sales This Period</span>
              <span class="monthly-total-value">₱{{ price(monthlyTotal) }}</span>
            </div>
          </div>
          <div class="chart-area chart-area--tall">
            <canvas id="monthlySalesChart"></canvas>
          </div>
        </div>
      </div>

    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import Layout from '@/components/Layout.vue'
import Chart from 'chart.js/auto'
import api from '@/api/axios'

import 'https://code.jquery.com/jquery-3.5.1.min.js'
import 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js'
import 'https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js'
import 'https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js'
import 'https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js'
import 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js'

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

/* ── reactive data (shared shape across both modes) ── */
const salesActivity = ref([
  { label: "This Month's Sales",      value: '₱0.00' },
  { label: 'Items Sold This Month',   value: 0 },
  { label: 'Transactions This Month', value: 0 },
  { label: 'Monthly Profit',          value: '₱0.00' },
])
const inventorySummaryBoxes = ref([
  { label: 'Quantity in Hand', value: 0 },
  { label: 'Low Stock Items',  value: 0, isRed: true },
  { label: 'Total Items',      value: 0 },
  { label: 'Active Items',     value: 0 },
])
const topSellingItems = ref([])
const monthlySales    = ref([])
const monthlyTotal    = ref(0)

let chartInstance  = null
let themeObserver  = null
let dtTable        = null

/* ── accent map ── */
function kpiAccent(idx) {
  return [
    { icon: 'fa-peso-sign',      iconClass: 'kpi-icon-wrap--orange', valueClass: '',                  barClass: 'kpi-accent-bar--orange', cardClass: 'kpi-card--featured' },
    { icon: 'fa-box-open',       iconClass: 'kpi-icon-wrap--violet', valueClass: 'kpi-value--violet', barClass: 'kpi-accent-bar--violet', cardClass: '' },
    { icon: 'fa-receipt',        iconClass: 'kpi-icon-wrap--blue',   valueClass: 'kpi-value--blue',   barClass: 'kpi-accent-bar--blue',   cardClass: '' },
    { icon: 'fa-arrow-trend-up', iconClass: 'kpi-icon-wrap--green',  valueClass: 'kpi-value--green',  barClass: 'kpi-accent-bar--green',  cardClass: '' },
  ][idx % 4]
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

    const res = await api.get('/api/reports', { params })

    salesActivity.value         = res.data.sales_activity    ?? salesActivity.value
    inventorySummaryBoxes.value = res.data.inventory_summary ?? inventorySummaryBoxes.value
    topSellingItems.value       = res.data.top_selling       ?? []
    monthlySales.value          = res.data.monthly_sales     ?? []
    monthlyTotal.value          = res.data.monthly_total     ?? 0
    periodLabel.value           = res.data.selected_period?.label ?? ''

    buildChart()
    refreshDataTable()
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

const price = (v) => Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

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

/* ── chart ── */
function buildChart() {
  const el = document.getElementById('monthlySalesChart')
  if (!el) return
  if (chartInstance) { chartInstance.destroy(); chartInstance = null }

  const c   = chartColors()
  const ctx = el.getContext('2d')
  const gradient = ctx.createLinearGradient(0, 0, 0, 280)
  gradient.addColorStop(0, 'rgba(234,88,12,0.22)')
  gradient.addColorStop(1, 'rgba(234,88,12,0)')

  chartInstance = new Chart(el, {
    type: 'line',
    data: {
      labels: monthlySales.value.map((x) => x.date),
      datasets: [{
        label: mode.value === 'month' ? 'Daily Sales' : 'Hourly Sales',
        data:  monthlySales.value.map((x) => x.total),
        borderColor: '#EA580C',
        backgroundColor: gradient,
        borderWidth: 2.5, tension: 0.42, fill: true,
        pointRadius: 0, pointHoverRadius: 6,
        pointHoverBackgroundColor: '#EA580C',
        pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: c.ttBg, titleColor: c.ttTitle,
          bodyColor: c.ttBody, padding: 12, cornerRadius: 10,
          borderColor: c.ttBorder, borderWidth: 1,
          callbacks: { label: (ctx) => ` ₱${Number(ctx.raw).toLocaleString()}` },
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
}

/* ── DataTable rebuild (row set changes every time the period changes) ── */
function refreshDataTable() {
  if ($.fn.DataTable.isDataTable('#topSellingItemsTable')) {
    dtTable.destroy()
  }
  dtTable = $('#topSellingItemsTable').DataTable({
    paging: true, searching: true, info: true,
    order: [], dom: 'lftip',
    buttons: ['copy', 'csv', 'print'],
  })

  const controls = $('#datatableControls')
  controls.empty()
  controls.append($('#topSellingItemsTable_length'))
  controls.append($('#topSellingItemsTable_filter'))
}

onMounted(async () => {
  await loadReports()

  document.getElementById('copyBtn').onclick  = () => dtTable.button('.buttons-copy').trigger()
  document.getElementById('csvBtn').onclick   = () => dtTable.button('.buttons-csv').trigger()
  document.getElementById('printBtn').onclick = () => {
    dtTable.page.len(-1).draw()
    setTimeout(() => window.print(), 200)
    setTimeout(() => dtTable.page.len(10).draw(), 1000)
  }

  /* Re-build chart on theme toggle */
  themeObserver = new MutationObserver(buildChart)
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] })
})

onBeforeUnmount(() => {
  if (themeObserver) themeObserver.disconnect()
  if (chartInstance) chartInstance.destroy()
  if (dtTable) dtTable.destroy()
})
</script>

<!-- ══════════════════════════════════════════════════════
     SCOPED styles — Vue component layout only.
     DataTables elements are NOT in Vue's scope so they
     cannot be reliably reached here. Use global block below.
══════════════════════════════════════════════════════ -->
<style scoped>
@import url('https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css');
@import url('https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css');

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
  --rp-blue:        #3b82f6;
  --rp-violet:      #6366f1;
  --rp-red:         #f43f5e;

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
}

/* PAGE HEADER */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--rp-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--rp-text-primary); letter-spacing: -.03em; margin: 0; transition: color 0.22s ease; }

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
  margin-bottom: 18px; gap: 12px; flex-wrap: wrap;
}
.card-title { font-size: 15.5px; font-weight: 700; color: var(--rp-text-primary); margin: 0; letter-spacing: -.01em; transition: color 0.22s ease; }

/* EXPORT BUTTONS */
.export-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.export-btn {
  display: flex; align-items: center; gap: 6px;
  font-size: 12.5px; font-weight: 600;
  color: var(--rp-text-secondary);
  background: var(--rp-surface-raised);
  border: 1.5px solid var(--rp-border-strong);
  border-radius: 10px; padding: 8px 14px;
  cursor: pointer; font-family: inherit;
  transition: border-color .18s, background-color .18s, color .18s;
}
.export-btn:hover { border-color: var(--rp-accent-border); color: var(--rp-accent); background: var(--rp-accent-soft); }
.export-btn--primary { background: var(--rp-accent); border-color: var(--rp-accent); color: #fff; }
.export-btn--primary:hover { filter: brightness(0.90); color: #fff; }

/* TABLE CONTROLS (length + search mount) */
.table-controls {
  display: flex; gap: 16px; align-items: center;
  justify-content: flex-end; margin-bottom: 14px; flex-wrap: wrap;
}

/* TABLE WRAP */
.table-wrap { overflow-x: auto; }

.data-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13.5px; }

/* Vue-rendered tbody cells (scoped) */
.data-table tbody td {
  padding: 12px 16px;
  color: var(--rp-text-secondary);
  border-bottom: 1px solid var(--rp-border);
  transition: color 0.22s ease, border-color 0.22s ease;
}
.data-table tbody tr:last-child td { border-bottom: none; }

.cell-product { font-weight: 600; color: var(--rp-text-primary); transition: color 0.22s ease; }
.cell-muted   { color: var(--rp-text-muted); }
.cell-sales   { font-weight: 700; color: var(--rp-accent); }

.category-pill {
  display: inline-flex; font-size: 11px; font-weight: 600;
  border-radius: 20px; padding: 3px 10px;
  background: #E0E7FF; color: var(--rp-violet);
}
html[data-theme="dark"] .category-pill { background: rgba(99,102,241,0.18); color: #A5B4FC; }

/* MONTHLY TOTAL */
.monthly-total { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
.monthly-total-label { font-size: 11px; font-weight: 600; color: var(--rp-text-muted); text-transform: uppercase; letter-spacing: .07em; }
.monthly-total-value { font-size: 22px; font-weight: 800; color: var(--rp-accent); letter-spacing: -.03em; }

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
  .export-actions { flex-wrap: wrap; }
  .page-header { flex-direction: column; align-items: stretch; }
  .period-control { flex-direction: column; align-items: stretch; }
  .period-mode-switch { justify-content: stretch; }
  .period-mode-btn { flex: 1; text-align: center; }
}
</style>

<!-- ══════════════════════════════════════════════════════
     GLOBAL (unscoped) — DataTables overrides ONLY.
     Must be global because DataTables injects DOM after mount
     and Vue's scoped attribute hash is not applied to it.
     Namespaced under #reports-print-area / .reports
     to avoid polluting other pages.
══════════════════════════════════════════════════════ -->
<style>

/* ══════════════════════════════════════════════════════
   1. THEAD — headers + sort icons
══════════════════════════════════════════════════════ */
.reports table.dataTable thead th {
  background:     #FAFAF9 !important;
  color:          #78716C !important;
  font-size:      11px !important;
  font-weight:    700 !important;
  text-transform: uppercase !important;
  letter-spacing: .07em !important;
  text-align:     left !important;
  padding:        12px 16px !important;
  border-bottom:  1px solid #F5F0EB !important;
  border-top:     none !important;
  white-space:    nowrap;
  position:       relative;
  padding-right:  28px !important;
}
.reports table.dataTable thead th:first-child { border-radius: 10px 0 0 0 !important; }
.reports table.dataTable thead th:last-child  { border-radius: 0 10px 0 0 !important; }

/* kill default DataTables sort bg-image */
.reports table.dataTable thead th.sorting,
.reports table.dataTable thead th.sorting_asc,
.reports table.dataTable thead th.sorting_desc,
.reports table.dataTable thead th.sorting_asc_disabled,
.reports table.dataTable thead th.sorting_desc_disabled {
  background-image:    none !important;
  background-size:     0 !important;
  background-repeat:   no-repeat !important;
}

/* custom FA sort arrow */
.reports table.dataTable thead th.sorting::after,
.reports table.dataTable thead th.sorting_asc::after,
.reports table.dataTable thead th.sorting_desc::after {
  font-family: 'Font Awesome 6 Free', 'FontAwesome', sans-serif;
  font-weight: 900; font-size: 9px;
  position: absolute; right: 10px; top: 50%;
  transform: translateY(-50%); line-height: 1;
  content: '\f0dc'; color: #D6CFC8;
}
.reports table.dataTable thead th.sorting_asc::after  { content: '\f0de'; color: #EA580C; }
.reports table.dataTable thead th.sorting_desc::after { content: '\f0dd'; color: #EA580C; }

/* DARK thead */
html[data-theme="dark"] .reports table.dataTable thead th {
  background:    #222535 !important;
  color:         #8B90A8 !important;
  border-bottom: 1px solid #2A2D3E !important;
}
html[data-theme="dark"] .reports table.dataTable thead th.sorting::after       { color: #555B72; }
html[data-theme="dark"] .reports table.dataTable thead th.sorting_asc::after,
html[data-theme="dark"] .reports table.dataTable thead th.sorting_desc::after  { color: #FB923C; }

/* ══════════════════════════════════════════════════════
   2. TBODY — ALL rows including "No data" empty row
      Must use very high specificity with !important
      because DataTables sets inline styles on <tr>/<td>
══════════════════════════════════════════════════════ */
.reports table.dataTable tbody tr,
.reports table.dataTable tbody tr.odd,
.reports table.dataTable tbody tr.even {
  background-color: transparent !important;
}
.reports table.dataTable tbody td {
  background-color: transparent !important;
  color: #44403C !important;
  border-bottom: 1px solid #F5F0EB !important;
  padding: 12px 16px !important;
  vertical-align: middle !important;
}
/* "No data available in table" empty cell */
.reports table.dataTable tbody td.dataTables_empty {
  background-color: transparent !important;
  color: #A8A29E !important;
  text-align: center !important;
  font-size: 13px !important;
  padding: 32px 16px !important;
}
.reports table.dataTable tbody tr:hover td,
.reports table.dataTable tbody tr.odd:hover td,
.reports table.dataTable tbody tr.even:hover td {
  background-color: #FAFAF9 !important;
}

/* DARK tbody */
html[data-theme="dark"] .reports table.dataTable tbody tr,
html[data-theme="dark"] .reports table.dataTable tbody tr.odd,
html[data-theme="dark"] .reports table.dataTable tbody tr.even {
  background-color: transparent !important;
}
html[data-theme="dark"] .reports table.dataTable tbody td {
  background-color: transparent !important;
  color: #C4C8D6 !important;
  border-bottom: 1px solid #2A2D3E !important;
}
html[data-theme="dark"] .reports table.dataTable tbody td.dataTables_empty {
  background-color: transparent !important;
  color: #555B72 !important;
}
html[data-theme="dark"] .reports table.dataTable tbody tr:hover td,
html[data-theme="dark"] .reports table.dataTable tbody tr.odd:hover td,
html[data-theme="dark"] .reports table.dataTable tbody tr.even:hover td {
  background-color: #222535 !important;
}

/* table wrapper borders */
.reports table.dataTable.no-footer { border-bottom: none !important; }
.reports .dataTables_wrapper        { background: transparent !important; }
.reports .dataTables_scroll div.dataTables_scrollBody { border-top: none !important; }

/* ══════════════════════════════════════════════════════
   3. LENGTH + SEARCH CONTROLS
══════════════════════════════════════════════════════ */
.reports .dataTables_length,
.reports .dataTables_filter {
  display: inline-flex !important;
  align-items: center !important;
}
.reports .dataTables_length label,
.reports .dataTables_filter label {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
  font-size: 12.5px !important;
  font-weight: 600 !important;
  color: #78716C !important;
  margin-bottom: 0 !important;
  white-space: nowrap !important;
}
.reports .dataTables_length select,
.reports .dataTables_filter input {
  border: 1.5px solid #E7E0D8 !important;
  border-radius: 10px !important;
  padding: 6px 10px !important;
  font-size: 12.5px !important;
  font-family: 'Inter', system-ui, sans-serif !important;
  font-weight: 500 !important;
  color: #44403C !important;
  background-color: #FFFFFF !important;
  margin: 0 !important;
  outline: none !important;
  box-shadow: none !important;
  transition: border-color .18s ease, box-shadow .18s ease !important;
  -webkit-appearance: auto !important;
  appearance: auto !important;
}
.reports .dataTables_length select:focus,
.reports .dataTables_filter input:focus {
  border-color: #EA580C !important;
  box-shadow: 0 0 0 3px rgba(234,88,12,.10) !important;
}
.reports .dataTables_filter input::placeholder { color: #C4B5A0 !important; }

/* DARK controls */
html[data-theme="dark"] .reports .dataTables_length label,
html[data-theme="dark"] .reports .dataTables_filter label { color: #8B90A8 !important; }

html[data-theme="dark"] .reports .dataTables_length select,
html[data-theme="dark"] .reports .dataTables_filter input {
  background-color: #13151E !important;
  color:            #E8EAF0 !important;
  border-color:     #2A2D3E !important;
}
html[data-theme="dark"] .reports .dataTables_length select:focus,
html[data-theme="dark"] .reports .dataTables_filter input:focus {
  border-color: #FB923C !important;
  box-shadow:   0 0 0 3px rgba(251,146,60,.12) !important;
}
html[data-theme="dark"] .reports .dataTables_filter input::placeholder { color: #454A60 !important; }

/* ══════════════════════════════════════════════════════
   4. INFO TEXT
══════════════════════════════════════════════════════ */
.reports .dataTables_info {
  font-size:   12.5px !important;
  color:       #78716C !important;
  padding-top: 12px !important;
}
html[data-theme="dark"] .reports .dataTables_info { color: #8B90A8 !important; }

/* ══════════════════════════════════════════════════════
   5. PAGINATION
══════════════════════════════════════════════════════ */
.reports .dataTables_paginate {
  padding-top: 12px !important;
  display: flex !important;
  gap: 4px !important;
  flex-wrap: wrap !important;
  justify-content: flex-end !important;
}
.reports .dataTables_paginate .paginate_button {
  display: inline-block !important;
  border: 1.5px solid #E7E0D8 !important;
  border-radius: 8px !important;
  padding: 5px 12px !important;
  margin: 0 !important;
  font-size: 12.5px !important;
  font-family: 'Inter', system-ui, sans-serif !important;
  color: #44403C !important;
  background-color: #FFFFFF !important;
  cursor: pointer !important;
  line-height: 1.5 !important;
  transition: background .15s, border-color .15s, color .15s !important;
  box-shadow: none !important;
}
.reports .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
  background-color: #FFF7ED !important;
  border-color:     #FDBA74 !important;
  color:            #EA580C !important;
}
.reports .dataTables_paginate .paginate_button.current,
.reports .dataTables_paginate .paginate_button.current:hover {
  background-color: #EA580C !important;
  border-color:     #EA580C !important;
  color:            #fff !important;
  box-shadow:       0 2px 8px rgba(234,88,12,.30) !important;
}
.reports .dataTables_paginate .paginate_button.disabled,
.reports .dataTables_paginate .paginate_button.disabled:hover {
  opacity: 0.4 !important;
  cursor: not-allowed !important;
  background-color: #FFFFFF !important;
  border-color: #E7E0D8 !important;
  color: #44403C !important;
}

/* DARK pagination */
html[data-theme="dark"] .reports .dataTables_paginate .paginate_button {
  background-color: #1A1D27 !important;
  color:            #C4C8D6 !important;
  border-color:     #2A2D3E !important;
}
html[data-theme="dark"] .reports .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
  background-color: rgba(251,146,60,0.10) !important;
  border-color:     rgba(251,146,60,0.30) !important;
  color:            #FB923C !important;
}
html[data-theme="dark"] .reports .dataTables_paginate .paginate_button.current,
html[data-theme="dark"] .reports .dataTables_paginate .paginate_button.current:hover {
  background-color: #FB923C !important;
  border-color:     #FB923C !important;
  color:            #0F1117 !important;
  box-shadow:       0 2px 12px rgba(251,146,60,.40) !important;
}
html[data-theme="dark"] .reports .dataTables_paginate .paginate_button.disabled,
html[data-theme="dark"] .reports .dataTables_paginate .paginate_button.disabled:hover {
  background-color: #1A1D27 !important;
  border-color:     #2A2D3E !important;
  color:            #555B72 !important;
}

/* hide internal DT button bar (we use our own) */
.reports div.dt-buttons { display: none !important; }

/* ══════════════════════════════════════════════════════
   PRINT
══════════════════════════════════════════════════════ */
@media print {
  body * { visibility: hidden !important; }
  #reports-print-area,
  #reports-print-area * { visibility: visible !important; }
  #reports-print-area {
    position: absolute; left: 0; top: 0;
    width: 100%; padding: 20px; background: white;
  }
  #datatableControls, .export-actions, .period-control { display: none !important; }
  thead  { display: table-header-group !important; }
  tr     { page-break-inside: avoid !important; }
  canvas { max-width: 100% !important; }
}
</style>