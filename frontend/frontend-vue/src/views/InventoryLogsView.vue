<template>
  <Layout title="Inventory Logs">
    <div id="inventory-print-area" class="inv-logs">

      <!-- Print-only header -->
      <div class="print-only print-header">
        <div class="print-header-top">
          <span class="print-header-date">{{ new Date().toLocaleString() }}</span>
          <span class="print-header-company">Sheem Steel Construction Company</span>
        </div>
        <h1 class="print-header-brand">Sheem Steel Construction Company</h1>
        <p class="print-header-sub">Inventory Logs</p>
        <p class="print-header-meta">Printed: {{ new Date().toLocaleString() }}</p>
        <hr class="print-header-rule" />
      </div>

      <!-- PAGE HEADER -->
      <div class="page-header">
        <div class="page-header-left">
          <p class="page-eyebrow">Sales Intelligence</p>
          <h1 class="page-title">Inventory Logs</h1>
        </div>
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
              <div class="kpi-icon-wrap kpi-icon-wrap--orange">
                <i class="fa-solid fa-sliders"></i>
              </div>
              <div class="kpi-body">
                <p class="kpi-label">Total Adjustments</p>
                <p class="kpi-value">{{ summary.total_adjustments }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--orange"></div>
          </div>

          <div class="kpi-card">
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap kpi-icon-wrap--green">
                <i class="fa-solid fa-arrow-down-to-bracket"></i>
              </div>
              <div class="kpi-body">
                <p class="kpi-label">Total Stock In</p>
                <p class="kpi-value kpi-value--green">+{{ summary.total_stock_in }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--green"></div>
          </div>

          <div class="kpi-card kpi-card--alert">
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap kpi-icon-wrap--red">
                <i class="fa-solid fa-arrow-up-from-bracket"></i>
              </div>
              <div class="kpi-body">
                <p class="kpi-label">Total Stock Out</p>
                <p class="kpi-value kpi-value--red">-{{ summary.total_stock_out }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--red"></div>
          </div>

          <div class="kpi-card">
            <div class="kpi-card-inner">
              <div class="kpi-icon-wrap kpi-icon-wrap--violet">
                <i class="fa-solid fa-pen-to-square"></i>
              </div>
              <div class="kpi-body">
                <p class="kpi-label">Manual Adjustments</p>
                <p class="kpi-value kpi-value--violet">{{ summary.manual_adjustments }}</p>
              </div>
            </div>
            <div class="kpi-accent-bar kpi-accent-bar--violet"></div>
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
            </div>
          </div>
          <div class="table-wrap">
            <table id="recentActivityTable" class="data-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Type</th>
                  <th>Quantity</th>
                  <th>Date</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in recentActivity" :key="item.id">
                  <td>
                    <div class="cell-product">{{ item.ProductName }}</div>
                    <div class="cell-sku">{{ item.SKU }}</div>
                  </td>
                  <td>
                    <span class="status-pill"
                      :class="{
                        'status-pill--in':      item.type === 'stock_in',
                        'status-pill--out':     item.type === 'stock_out',
                        'status-pill--neutral': item.type !== 'stock_in' && item.type !== 'stock_out',
                      }">
                      {{ item.type }}
                    </span>
                  </td>
                  <td class="cell-muted">{{ item.quantity }}</td>
                  <td class="cell-faint">{{ item.created_at }}</td>
                  <td class="cell-muted">{{ item.notes }}</td>
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
            <table id="topAdjustedTable" class="data-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Adjustments</th>
                  <th>Total Stock In</th>
                  <th>Total Stock Out</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in topAdjustedProducts" :key="item.id">
                  <td>
                    <div class="cell-product">{{ item.ProductName }}</div>
                    <div class="cell-sku">{{ item.SKU }}</div>
                  </td>
                  <td class="cell-muted">{{ item.adjustment_count }}</td>
                  <td class="cell-positive">+{{ item.total_stock_in }}</td>
                  <td class="cell-negative">-{{ item.total_stock_out }}</td>
                </tr>
              </tbody>
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
            </div>
          </div>
          <div class="table-wrap">
            <table id="inventoryLogsTable" class="data-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Category</th>
                  <th>Type</th>
                  <th>Quantity</th>
                  <th>Date</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in inventoryLogs" :key="item.id">
                  <td>
                    <div class="cell-product">{{ item.ProductName }}</div>
                    <div class="cell-sku">{{ item.SKU }}</div>
                  </td>
                  <td>
                    <span class="category-pill">{{ item.CategoryName }}</span>
                  </td>
                  <td>
                    <span class="status-pill"
                      :class="{
                        'status-pill--in':      item.type === 'stock_in',
                        'status-pill--out':     item.type === 'stock_out',
                        'status-pill--neutral': item.type !== 'stock_in' && item.type !== 'stock_out',
                      }">
                      {{ item.type }}
                    </span>
                  </td>
                  <td class="cell-muted">{{ item.quantity }}</td>
                  <td class="cell-faint">{{ item.created_at }}</td>
                  <td class="cell-muted">{{ item.notes }}</td>
                </tr>
              </tbody>
            </table>
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
import { ref, onMounted, nextTick, onBeforeUnmount } from 'vue'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const summary = ref({
  total_adjustments: 0,
  total_stock_in: 0,
  total_stock_out: 0,
  manual_adjustments: 0,
})

const recentActivity      = ref([])
const topAdjustedProducts = ref([])
const inventoryLogs       = ref([])

let dtRecent = null
let dtTop    = null
let dtLogs   = null

import 'https://code.jquery.com/jquery-3.5.1.min.js'
import 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js'
import 'https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js'
import 'https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js'
import 'https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js'

async function loadInventoryLogs(topLimit = 10) {
  try {
    const res = await api.get('/api/inventory/logs', { params: { top_limit: topLimit } })
    summary.value             = res.data.summary
    recentActivity.value      = res.data.recent_activity
    topAdjustedProducts.value = res.data.top_adjusted_products
    inventoryLogs.value       = res.data.inventory_logs
  } catch (err) {
    console.error('Inventory logs API error:', err)
  }
}

function printNow() { window.print() }

onMounted(async () => {
  await loadInventoryLogs()
  await nextTick()

  setTimeout(() => {
    dtRecent = $('#recentActivityTable').DataTable({
      dom: '<"inv-dt-toolbar"<"inv-dt-toolbar-left"B><"inv-dt-toolbar-right"lf>>rtip',
      buttons: [
        { extend: 'copy', text: 'Copy',  className: 'dt-button' },
        { extend: 'csv',  text: 'CSV',   className: 'dt-button' },
        { text: 'Print',  className: 'dt-button dt-button--print', action: () => printNow() },
      ],
      order: [[4, 'desc']],
      pageLength: 25,
    })

    dtTop = $('#topAdjustedTable').DataTable({
      dom: '<"inv-dt-toolbar inv-dt-toolbar--right"lf>rtip',
      pageLength: 25,
    })

    dtTop.on('length.dt', async (e, settings, len) => {
      await loadInventoryLogs(len)
      await nextTick()
      dtTop.clear()
      dtTop.rows.add($('#topAdjustedTable tbody tr').toArray())
      dtTop.draw()
    })

    dtLogs = $('#inventoryLogsTable').DataTable({
      dom: '<"inv-dt-toolbar inv-dt-toolbar--right"lf>rtip',
      pageLength: 25,
    })
  }, 200)
})

onBeforeUnmount(() => {
  if (dtRecent) dtRecent.destroy()
  if (dtTop)    dtTop.destroy()
  if (dtLogs)   dtLogs.destroy()
})
</script>

<!-- ─── SCOPED: page layout & component styles ──────────── -->
<style scoped>
@import url('https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css');
@import url('https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css');

/* ── ROOT — all colors via global theme tokens ── */
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

  /* Semantic palette */
  --il-green:          #10b981;
  --il-blue:           #3b82f6;
  --il-violet:         #6366f1;
  --il-red:            #f43f5e;

  /* Icon bg — lightened in light, softened in dark */
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

/* Dark overrides — softer icon backgrounds */
html[data-theme="dark"] .inv-logs {
  --il-green-icon:   rgba(16,185,129,0.18);
  --il-blue-icon:    rgba(59,130,246,0.18);
  --il-violet-icon:  rgba(99,102,241,0.18);
  --il-red-icon:     rgba(244,63,94,0.18);
}

/* ── PAGE HEADER ── */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; }
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

/* ── SECTION ── */
.section        { display: flex; flex-direction: column; gap: 14px; }
.section-header { display: flex; flex-direction: column; }
.section-title  {
  font-size: 17px; font-weight: 700;
  color: var(--il-text-primary);
  margin: 0; letter-spacing: -.01em;
  transition: color 0.22s ease;
}
.card-eyebrow {
  font-size: 10.5px; font-weight: 700; letter-spacing: .12em;
  color: var(--il-accent); text-transform: uppercase; margin: 0 0 4px;
}

/* ── KPI STRIP ── */
.kpi-strip { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; }

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

.kpi-card--featured {
  background: var(--il-accent-soft);
  border-color: var(--il-accent-border);
}
.kpi-card--alert {
  background: rgba(244,63,94,0.08);
  border-color: rgba(244,63,94,0.25);
}

/* Dark alert card override */
html[data-theme="dark"] .kpi-card--alert {
  background: rgba(244,63,94,0.10);
  border-color: rgba(244,63,94,0.28);
}

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
  font-size: 11px; font-weight: 600;
  color: var(--il-text-muted);
  text-transform: uppercase; letter-spacing: .07em;
}
.kpi-value {
  font-size: 23px; font-weight: 800;
  color: var(--il-text-primary);
  letter-spacing: -.04em; line-height: 1.1;
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
  font-size: 15.5px; font-weight: 700;
  color: var(--il-text-primary);
  margin: 0; letter-spacing: -.01em;
  transition: color 0.22s ease;
}

/* ── TABLE ── */
.table-wrap { overflow-x: auto; }

.data-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13.5px;
}
.data-table tbody td {
  padding: 12px 16px;
  color: var(--il-text-secondary);
  border-bottom: 1px solid var(--il-border);
  white-space: nowrap;
  transition: color 0.22s ease, border-color 0.22s ease;
}
.data-table tbody tr:hover td   { background: var(--il-surface-raised); }
.data-table tbody tr:last-child td { border-bottom: none; }

.cell-product  { font-weight: 600; color: var(--il-text-primary); font-size: 13.5px; transition: color 0.22s ease; }
.cell-sku      { font-size: 11px; color: var(--il-text-faint); margin-top: 2px; }
.cell-muted    { color: var(--il-text-secondary); }
.cell-faint    { color: var(--il-text-muted); font-size: 12.5px; }
.cell-positive { color: var(--il-green); font-weight: 700; }
.cell-negative { color: var(--il-red);   font-weight: 700; }

/* ── STATUS PILLS ── */
.status-pill {
  display: inline-flex;
  font-size: 11px; font-weight: 700;
  text-transform: capitalize; letter-spacing: .02em;
  border-radius: 20px; padding: 4px 12px;
}
/* Light mode pills */
.status-pill--in      { background: #D1FAE5; color: #047857; }
.status-pill--out     { background: #FFE4E6; color: #BE123C; }
.status-pill--neutral { background: #FEF3C7; color: #92400E; }

/* Dark mode pills — softer backgrounds */
html[data-theme="dark"] .status-pill--in      { background: rgba(16,185,129,0.16); color: #4ADE80; }
html[data-theme="dark"] .status-pill--out     { background: rgba(244,63,94,0.16);  color: #F87171; }
html[data-theme="dark"] .status-pill--neutral { background: rgba(245,158,11,0.16); color: #FCD34D; }

.category-pill {
  display: inline-flex;
  font-size: 11px; font-weight: 600;
  color: var(--il-violet);
  border-radius: 20px; padding: 3px 10px;
}
/* Light */
.category-pill { background: #E0E7FF; }
/* Dark */
html[data-theme="dark"] .category-pill { background: rgba(99,102,241,0.18); color: #A5B4FC; }

.print-only { display: none; }
.print-header,
.print-footer { display: none; }

@media (max-width: 1100px) { .kpi-strip { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 700px)  { .inv-logs  { padding: 16px 16px 40px; } .kpi-strip { grid-template-columns: 1fr; } }

/* ════════════════════════════════════════════════════════════
   PRINT MODE
   Goal: reproduce the on-screen card / KPI / pill design exactly
   as the user sees it on the dashboard, but locked to light-mode
   hex values (never the dark --c-* theme tokens, never bare
   browser print defaults). Same shapes, same accent color,
   same colored badges — just paper-ready.
════════════════════════════════════════════════════════════ */
@media print {

  .inv-logs {
    background: #ffffff !important;
    padding: 0 !important;
    gap: 18px !important;
    font-family: 'Inter', Arial, sans-serif !important;
  }

  /* ---- print-only header block ---- */
  .print-header {
    display: block !important;
    margin-bottom: 4px;
  }
  .print-header-top {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    color: #A8A29E;
    margin-bottom: 14px;
  }
  .print-header-brand {
    font-size: 21px;
    font-weight: 800;
    color: #1C1917;
    margin: 0 0 2px;
    letter-spacing: -.02em;
  }
  .print-header-sub {
    font-size: 13px;
    font-weight: 600;
    color: #57534E;
    margin: 0 0 4px;
  }
  .print-header-meta {
    font-size: 11px;
    color: #A8A29E;
    margin: 0 0 14px;
  }
  .print-header-rule {
    border: none;
    border-top: 2px solid #EA580C;
    margin: 0 0 18px;
  }

  /* ---- page header / section labels: keep brand voice, lock colors ---- */
  .page-header { margin-bottom: 4px; }
  .page-eyebrow {
    color: #EA580C !important;
    font-size: 10px !important;
  }
  .page-title {
    color: #1C1917 !important;
    font-size: 20px !important;
  }
  .card-eyebrow {
    color: #EA580C !important;
    font-size: 9.5px !important;
  }
  .section-title {
    color: #1C1917 !important;
    font-size: 14.5px !important;
  }
  .card-title {
    color: #1C1917 !important;
    font-size: 13.5px !important;
  }

  /* ---- KPI cards: same shapes as the dashboard, light surfaces ---- */
  .kpi-strip {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 10px !important;
  }
  .kpi-card {
    background: #FAFAF9 !important;
    border: 1px solid #E7E0D8 !important;
    box-shadow: none !important;
    border-radius: 12px !important;
    padding: 12px 14px 10px !important;
    break-inside: avoid;
  }
  .kpi-card--featured {
    background: #FFF7ED !important;
    border-color: #FED7AA !important;
  }
  .kpi-card--alert {
    background: #FFF1F2 !important;
    border-color: #FECDD3 !important;
  }
  .kpi-icon-wrap {
    width: 30px !important;
    height: 30px !important;
    border-radius: 9px !important;
    font-size: 12px !important;
  }
  .kpi-icon-wrap--orange { background: #FFEDD5 !important; color: #EA580C !important; }
  .kpi-icon-wrap--green  { background: #D1FAE5 !important; color: #10B981 !important; }
  .kpi-icon-wrap--violet { background: #E0E7FF !important; color: #6366F1 !important; }
  .kpi-icon-wrap--red    { background: #FFE4E6 !important; color: #F43F5E !important; }
  .kpi-label {
    color: #A8A29E !important;
    font-size: 9px !important;
  }
  .kpi-value {
    color: #1C1917 !important;
    font-size: 17px !important;
  }
  .kpi-value--green  { color: #10B981 !important; }
  .kpi-value--violet { color: #6366F1 !important; }
  .kpi-value--red    { color: #F43F5E !important; }
  .kpi-accent-bar { height: 2px !important; }
  .kpi-accent-bar--orange { background: #EA580C !important; }
  .kpi-accent-bar--green  { background: #10B981 !important; }
  .kpi-accent-bar--violet { background: #6366F1 !important; }
  .kpi-accent-bar--red    { background: #F43F5E !important; }

  /* ---- cards housing tables: light surface, soft border, no shadow ---- */
  .card {
    background: #ffffff !important;
    border: 1px solid #EDE8E2 !important;
    box-shadow: none !important;
    border-radius: 12px !important;
    padding: 14px 16px 12px !important;
    break-inside: avoid-page;
  }
  .card-header { margin-bottom: 10px !important; }

  /* ---- tables: crisp, legible, same data hierarchy ---- */
  .data-table { font-size: 10.5px !important; }
  .data-table thead th {
    background: #FAFAF9 !important;
    color: #78716C !important;
    font-size: 9px !important;
    padding: 7px 10px !important;
    border-bottom: 1px solid #EDE8E2 !important;
  }
  .data-table tbody td {
    padding: 6px 10px !important;
    border-bottom: 1px solid #F2EEE9 !important;
    color: #44403C !important;
    white-space: normal !important;
  }
  .data-table tbody tr:nth-child(even) td { background: #FCFBFA !important; }

  .cell-product  { color: #1C1917 !important; font-size: 10.5px !important; }
  .cell-sku      { color: #A8A29E !important; font-size: 8.5px !important; }
  .cell-faint    { color: #A8A29E !important; font-size: 9.5px !important; }
  .cell-muted    { color: #57534E !important; }
  .cell-positive { color: #10B981 !important; }
  .cell-negative { color: #F43F5E !important; }

  /* ---- status + category pills: keep the real colored-pill look ---- */
  .status-pill {
    font-size: 9px !important;
    padding: 2px 9px !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .status-pill--in      { background: #D1FAE5 !important; color: #047857 !important; }
  .status-pill--out     { background: #FFE4E6 !important; color: #BE123C !important; }
  .status-pill--neutral { background: #FEF3C7 !important; color: #92400E !important; }

  .category-pill {
    font-size: 9px !important;
    padding: 2px 8px !important;
    background: #E0E7FF !important;
    color: #6366F1 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* ---- print-only footer, repeats per page ---- */
  .print-footer {
    display: flex !important;
    justify-content: space-between;
    font-size: 8.5px;
    color: #A8A29E;
    border-top: 1px solid #EDE8E2;
    padding-top: 8px;
    margin-top: 6px;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
  }

  .section { break-inside: avoid-page; margin-bottom: 4px !important; }
}
</style>

<!--
  Global (unscoped) DataTables overrides.
  Namespaced under .inv-logs so they don't bleed to other pages.
  Dark mode variants target html[data-theme="dark"] .inv-logs.
-->
<style>
/* ── KILL DataTables background-image sort icons ── */
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

/* Custom single arrow via ::after */
.inv-logs table.dataTable thead th.sorting::after,
.inv-logs table.dataTable thead th.sorting_asc::after,
.inv-logs table.dataTable thead th.sorting_desc::after {
  font-family: 'Font Awesome 6 Free', 'FontAwesome', sans-serif;
  font-weight: 900;
  font-size: 9px;
  position: absolute;
  right: 10px; top: 50%;
  transform: translateY(-50%);
  line-height: 1;
  content: '\f0dc';
  color: #D6CFC8;
}
.inv-logs table.dataTable thead th.sorting_asc::after  { content: '\f0de'; color: #EA580C; }
.inv-logs table.dataTable thead th.sorting_desc::after { content: '\f0dd'; color: #EA580C; }

/* Dark sort arrow colors */
html[data-theme="dark"] .inv-logs table.dataTable thead th.sorting::after       { color: #555B72; }
html[data-theme="dark"] .inv-logs table.dataTable thead th.sorting_asc::after,
html[data-theme="dark"] .inv-logs table.dataTable thead th.sorting_desc::after  { color: #FB923C; }

/* ── thead th ── */
.inv-logs table.dataTable thead th {
  background:       #FAFAF9 !important;
  color:            #78716C !important;
  font-size:        11px !important;
  font-weight:      700 !important;
  text-transform:   uppercase !important;
  letter-spacing:   .07em !important;
  text-align:       left !important;
  padding:          12px 16px !important;
  border-bottom:    1px solid #F5F0EB !important;
  white-space:      nowrap;
  border-top:       none !important;
}
.inv-logs table.dataTable thead th:first-child { border-radius: 10px 0 0 0; }
.inv-logs table.dataTable thead th:last-child  { border-radius: 0 10px 0 0; }

/* Dark thead */
html[data-theme="dark"] .inv-logs table.dataTable thead th {
  background:    #222535 !important;
  color:         #8B90A8 !important;
  border-bottom: 1px solid #2A2D3E !important;
}

/* ── tbody rows ── */
.inv-logs table.dataTable tbody tr { background: transparent !important; }
.inv-logs table.dataTable tbody tr:hover { background: transparent !important; } /* handled by scoped rule */

/* Dark tbody text */
html[data-theme="dark"] .inv-logs table.dataTable tbody td {
  color: #C4C8D6 !important;
  border-bottom-color: #2A2D3E !important;
}
html[data-theme="dark"] .inv-logs table.dataTable tbody tr:hover td {
  background: #222535 !important;
}

/* Remove DataTables border on wrapper */
.inv-logs table.dataTable.no-footer { border-bottom: none !important; }

/* ── Toolbar ── */
.inv-logs .inv-dt-toolbar {
  display: flex; align-items: center;
  justify-content: space-between;
  gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
}
.inv-logs .inv-dt-toolbar--right { justify-content: flex-end; }
.inv-logs .inv-dt-toolbar-left  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.inv-logs .inv-dt-toolbar-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

/* ── Buttons ── */
.inv-logs .dt-buttons { display: flex; gap: 8px; flex-wrap: wrap; }

.inv-logs .dt-button {
  background:    #FAFAF9 !important;
  color:         #44403C !important;
  border:        1.5px solid #E7E0D8 !important;
  padding:       7px 14px !important;
  border-radius: 10px !important;
  font-size:     12.5px !important;
  font-weight:   600 !important;
  font-family:   'Inter', system-ui, sans-serif !important;
  margin-right:  0 !important;
  cursor:        pointer;
  transition:    border-color .18s, color .18s, background .18s;
  box-shadow:    none !important;
  line-height:   1.4 !important;
}
.inv-logs .dt-button:hover {
  border-color: #FDBA74 !important;
  color:        #EA580C !important;
  background:   #FFF7ED !important;
}
.inv-logs .dt-button.dt-button--print {
  background:   #EA580C !important;
  border-color: #EA580C !important;
  color:        #fff !important;
}
.inv-logs .dt-button.dt-button--print:hover {
  background:   #C2410C !important;
  border-color: #C2410C !important;
}

/* Dark buttons */
html[data-theme="dark"] .inv-logs .dt-button {
  background:   #222535 !important;
  color:        #C4C8D6 !important;
  border-color: #2A2D3E !important;
}
html[data-theme="dark"] .inv-logs .dt-button:hover {
  border-color: rgba(251,146,60,0.40) !important;
  color:        #FB923C !important;
  background:   rgba(251,146,60,0.10) !important;
}
html[data-theme="dark"] .inv-logs .dt-button.dt-button--print {
  background:   #FB923C !important;
  border-color: #FB923C !important;
  color:        #0F1117 !important;
}
html[data-theme="dark"] .inv-logs .dt-button.dt-button--print:hover {
  background:   #EA580C !important;
  border-color: #EA580C !important;
}

/* ── Length + Search ── */
.inv-logs .dataTables_filter,
.inv-logs .dataTables_length { display: flex; align-items: center; }

.inv-logs .dataTables_filter label,
.inv-logs .dataTables_length label {
  font-size:   12.5px;
  font-weight: 600;
  color:       #78716C;
  display:     flex;
  align-items: center;
  gap:         6px;
  margin-bottom: 0;
  white-space: nowrap;
}
html[data-theme="dark"] .inv-logs .dataTables_filter label,
html[data-theme="dark"] .inv-logs .dataTables_length label { color: #8B90A8 !important; }

.inv-logs .dataTables_filter input,
.inv-logs .dataTables_length select {
  border:      1.5px solid #E7E0D8 !important;
  border-radius: 10px !important;
  padding:     6px 10px !important;
  font-size:   12.5px !important;
  font-family: 'Inter', system-ui, sans-serif !important;
  color:       #44403C !important;
  background:  #fff !important;
  margin:      0 !important;
  outline:     none;
  transition:  border-color .18s, box-shadow .18s, background-color 0.22s, color 0.22s;
}
.inv-logs .dataTables_filter input:focus,
.inv-logs .dataTables_length select:focus {
  border-color: #EA580C !important;
  box-shadow:   0 0 0 3px rgba(234,88,12,.10) !important;
}

/* Dark inputs */
html[data-theme="dark"] .inv-logs .dataTables_filter input,
html[data-theme="dark"] .inv-logs .dataTables_length select {
  background:   #13151E !important;
  color:        #E8EAF0 !important;
  border-color: #2A2D3E !important;
}
html[data-theme="dark"] .inv-logs .dataTables_filter input:focus,
html[data-theme="dark"] .inv-logs .dataTables_length select:focus {
  border-color: #FB923C !important;
  box-shadow:   0 0 0 3px rgba(251,146,60,.12) !important;
}

/* ── Info ── */
.inv-logs .dataTables_info {
  color:       #78716C !important;
  font-size:   12.5px !important;
  padding-top: 12px !important;
}
html[data-theme="dark"] .inv-logs .dataTables_info { color: #8B90A8 !important; }

/* ── Pagination ── */
.inv-logs .dataTables_paginate {
  padding-top: 12px !important;
  display:     flex;
  gap:         4px;
  flex-wrap:   wrap;
}
.inv-logs .dataTables_paginate .paginate_button {
  border:        1.5px solid #E7E0D8 !important;
  border-radius: 8px !important;
  padding:       5px 11px !important;
  margin:        0 !important;
  font-size:     12.5px !important;
  color:         #44403C !important;
  cursor:        pointer;
  background:    #fff !important;
  line-height:   1.4 !important;
  transition:    background .15s, border-color .15s, color .15s;
}
.inv-logs .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
  background:   #FFF7ED !important;
  border-color: #FDBA74 !important;
  color:        #EA580C !important;
}
.inv-logs .dataTables_paginate .paginate_button.current {
  background:   #EA580C !important;
  border-color: #EA580C !important;
  color:        #fff !important;
}
.inv-logs .dataTables_paginate .paginate_button.disabled { opacity: .4; cursor: not-allowed; }

/* Dark pagination */
html[data-theme="dark"] .inv-logs .dataTables_paginate .paginate_button {
  background:   #1A1D27 !important;
  color:        #C4C8D6 !important;
  border-color: #2A2D3E !important;
}
html[data-theme="dark"] .inv-logs .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
  background:   rgba(251,146,60,0.10) !important;
  border-color: rgba(251,146,60,0.30) !important;
  color:        #FB923C !important;
}
html[data-theme="dark"] .inv-logs .dataTables_paginate .paginate_button.current {
  background:   #FB923C !important;
  border-color: #FB923C !important;
  color:        #0F1117 !important;
}

/* ── PRINT — hide app chrome around the report, keep only the report ── */
@media print {
  body * { visibility: hidden !important; }
  #inventory-print-area,
  #inventory-print-area * { visibility: visible !important; }
  #inventory-print-area {
    position: absolute; left: 0; top: 0;
    width: 100%; padding: 14px 18px 50px; background: #fff;
  }
  .print-only { display: block !important; }
  .dt-buttons, .dataTables_filter, .dataTables_length,
  .dataTables_paginate, .dataTables_info { display: none !important; }
  thead { display: table-header-group !important; }
  tr, td, th { page-break-inside: avoid !important; }
}
</style>