<script setup>
import { ref, computed } from 'vue'
import Layout from '@/components/Layout.vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const sale = ref({
  SaleID: route.params.id,
  SaleDate: '2024-02-12 14:30',
  PaymentMethod: 'Cash',
  CustomerName: 'Walk-in Customer',
  DiscountAmount: 20.0,
  AmountPaid: 900.0,
  clerk: { name: 'System' },
  items: [
    { ProductName: 'Steel Bar 10mm',    Quantity: 2, Price: 350 },
    { ProductName: 'Roof Sheet (Red)',  Quantity: 1, Price: 260 },
  ],
})

const subtotal = computed(() => sale.value.items.reduce((s, i) => s + i.Price * i.Quantity, 0))
const vat      = computed(() => subtotal.value * 0.12)
const total    = computed(() => subtotal.value + vat.value - sale.value.DiscountAmount)
const change   = computed(() => sale.value.AmountPaid - total.value)
const fmt      = n => `₱${Number(n).toFixed(2)}`
const saleRef  = computed(() => `SR-${String(sale.value.SaleID).padStart(5, '0')}`)

function printReceipt() { window.print() }
</script>

<template>
  <Layout>
    <div class="receipt-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div>
          <div class="breadcrumb">
            <RouterLink to="/sales-transaction" class="breadcrumb-link">
              <i class="fa-solid fa-arrow-left"></i> Back to Sales
            </RouterLink>
          </div>
          <p class="page-eyebrow">Transaction Record</p>
          <h1 class="page-title">Sales Receipt</h1>
          <p class="page-ref">{{ saleRef }}</p>
        </div>
        <div class="header-actions no-print">
          <button class="btn btn--ghost" @click="$router.push('/sales-transaction')">
            <i class="fa-solid fa-list"></i> All Transactions
          </button>
          <button class="btn btn--primary" @click="printReceipt">
            <i class="fa-solid fa-print"></i> Print Receipt
          </button>
        </div>
      </div>

      <!-- ── STATUS BANNER ── -->
      <div class="status-banner">
        <div class="status-pill status-pill--paid">
          <i class="fa-solid fa-circle-check"></i> PAID
        </div>
        <span class="status-method">
          <i class="fa-solid fa-money-bill-wave"></i> {{ sale.PaymentMethod }}
        </span>
        <span class="status-date">
          <i class="fa-regular fa-calendar"></i> {{ sale.SaleDate }}
        </span>
      </div>

      <!-- ── MAIN GRID ── -->
      <div class="receipt-grid">

        <!-- Sale Info -->
        <div class="receipt-card">
          <div class="receipt-card-header">
            <div class="receipt-card-icon receipt-card-icon--blue">
              <i class="fa-solid fa-user"></i>
            </div>
            <div>
              <p class="receipt-card-eyebrow">Customer</p>
              <h3 class="receipt-card-title">Sale Information</h3>
            </div>
          </div>
          <div class="info-list">
            <div class="info-row">
              <span class="info-label">Customer</span>
              <span class="info-val">{{ sale.CustomerName }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Date</span>
              <span class="info-val">{{ sale.SaleDate }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Payment</span>
              <span class="info-val">{{ sale.PaymentMethod }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Status</span>
              <span class="info-val"><span class="pill pill--green">PAID</span></span>
            </div>
          </div>
        </div>

        <!-- Transaction Summary -->
        <div class="receipt-card">
          <div class="receipt-card-header">
            <div class="receipt-card-icon receipt-card-icon--orange">
              <i class="fa-solid fa-peso-sign"></i>
            </div>
            <div>
              <p class="receipt-card-eyebrow">Financials</p>
              <h3 class="receipt-card-title">Transaction Summary</h3>
            </div>
          </div>
          <div class="summary-list">
            <div class="summary-row"><span>Subtotal</span><span>{{ fmt(subtotal) }}</span></div>
            <div class="summary-row"><span>VAT (12%)</span><span>{{ fmt(vat) }}</span></div>
            <div class="summary-row summary-row--disc"><span>Discount</span><span>- {{ fmt(sale.DiscountAmount) }}</span></div>
            <div class="summary-divider"></div>
            <div class="summary-row summary-row--total"><span>Total</span><span>{{ fmt(total) }}</span></div>
            <div class="summary-row"><span>Amount Paid</span><span>{{ fmt(sale.AmountPaid) }}</span></div>
            <div class="summary-row summary-row--change"><span>Change</span><span>{{ fmt(change) }}</span></div>
          </div>
        </div>

      </div>

      <!-- ── ITEMS TABLE ── -->
      <div class="receipt-card receipt-card--full">
        <div class="receipt-card-header">
          <div class="receipt-card-icon receipt-card-icon--violet">
            <i class="fa-solid fa-box-open"></i>
          </div>
          <div>
            <p class="receipt-card-eyebrow">Products</p>
            <h3 class="receipt-card-title">Items Purchased</h3>
          </div>
          <div class="item-count-badge">{{ sale.items.length }} item{{ sale.items.length !== 1 ? 's' : '' }}</div>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, i) in sale.items" :key="item.ProductName">
                <td class="cell-num">{{ i + 1 }}</td>
                <td class="cell-product">{{ item.ProductName }}</td>
                <td class="cell-price">{{ fmt(item.Price) }}</td>
                <td class="cell-qty">{{ item.Quantity }}</td>
                <td class="cell-total">{{ fmt(item.Price * item.Quantity) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="4" class="tfoot-label">Grand Total</td>
                <td class="tfoot-val">{{ fmt(total) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ── TRANSACTION DETAILS ── -->
      <div class="receipt-card receipt-card--full">
        <div class="receipt-card-header">
          <div class="receipt-card-icon receipt-card-icon--green">
            <i class="fa-solid fa-circle-info"></i>
          </div>
          <div>
            <p class="receipt-card-eyebrow">Audit</p>
            <h3 class="receipt-card-title">Transaction Details</h3>
          </div>
        </div>
        <div class="info-grid">
          <div class="info-row"><span class="info-label">Transaction ID</span><span class="info-val info-val--mono">{{ sale.SaleID }}</span></div>
          <div class="info-row"><span class="info-label">Reference</span><span class="info-val info-val--mono">{{ saleRef }}</span></div>
          <div class="info-row"><span class="info-label">Processed By</span><span class="info-val">{{ sale.clerk.name }}</span></div>
          <div class="info-row"><span class="info-label">Created At</span><span class="info-val">{{ sale.SaleDate }}</span></div>
          <div class="info-row"><span class="info-label">Last Updated</span><span class="info-val">{{ sale.SaleDate }}</span></div>
          <div class="info-row"><span class="info-label">Payment Method</span><span class="info-val">{{ sale.PaymentMethod }}</span></div>
        </div>
      </div>

    </div>
  </Layout>
</template>

<style scoped>
/* ── TOKEN BRIDGE ── */
.receipt-page {
  --r-bg:             var(--c-bg);
  --r-surface:        var(--c-surface);
  --r-surface-raised: var(--c-surface-raised);
  --r-border:         var(--c-border);
  --r-border-strong:  var(--c-border-strong);
  --r-text-primary:   var(--c-text-primary);
  --r-text-secondary: var(--c-text-secondary);
  --r-text-muted:     var(--c-text-muted);
  --r-text-faint:     var(--c-text-faint);
  --r-accent:         var(--c-accent);
  --r-accent-soft:    var(--c-accent-soft);
  --r-accent-border:  var(--c-accent-border);
  --r-shadow:         var(--c-shadow-sm);
  --r-shadow-md:      var(--c-shadow-md);
  --r-green:          #10b981;
  --r-blue:           #3b82f6;
  --r-violet:         #6366f1;
  --radius: 16px;

  min-height: 100%;
  background: var(--r-bg);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 20px;
  max-width: 960px; margin: 0 auto;
  transition: background-color .22s ease;
}

/* PAGE HEADER */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.breadcrumb { margin-bottom: 10px; }
.breadcrumb-link {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12.5px; font-weight: 600; color: var(--r-text-muted);
  text-decoration: none; transition: color .15s;
}
.breadcrumb-link:hover { color: var(--r-accent); }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--r-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--r-text-primary); letter-spacing: -.03em; margin: 0 0 4px; transition: color .22s; }
.page-ref     { font-size: 13px; color: var(--r-text-faint); margin: 0; font-variant-numeric: tabular-nums; }

.header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 9px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background-color .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn--ghost {
  background: var(--r-surface); border-color: var(--r-border-strong); color: var(--r-text-secondary);
}
.btn--ghost:hover { background: var(--r-surface-raised); border-color: var(--r-accent-border); color: var(--r-text-primary); }
.btn--primary {
  background: linear-gradient(135deg, var(--r-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }

/* STATUS BANNER */
.status-banner {
  display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
  background: var(--r-surface); border: 1px solid var(--r-border);
  border-radius: 12px; padding: 12px 18px; box-shadow: var(--r-shadow);
  transition: background-color .22s, border-color .22s;
}
.status-pill {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 700; border-radius: 20px; padding: 4px 12px;
  letter-spacing: .04em;
}
.status-pill--paid { background: rgba(16,185,129,.14); color: var(--r-green); }
html[data-theme="dark"] .status-pill--paid { background: rgba(16,185,129,.18); color: #4ADE80; }
.status-method, .status-date {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; color: var(--r-text-muted); font-weight: 500;
}

/* GRID */
.receipt-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* CARDS */
.receipt-card {
  background: var(--r-surface); border: 1px solid var(--r-border);
  border-radius: var(--radius); box-shadow: var(--r-shadow); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.receipt-card--full { grid-column: 1 / -1; }

.receipt-card-header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px 14px; border-bottom: 1px solid var(--r-border);
  transition: border-color .22s;
}
.receipt-card-icon {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.receipt-card-icon--blue   { background: rgba(59,130,246,.12); color: var(--r-blue);   }
.receipt-card-icon--orange { background: var(--r-accent-soft); color: var(--r-accent);  }
.receipt-card-icon--violet { background: rgba(99,102,241,.12); color: var(--r-violet);  }
.receipt-card-icon--green  { background: rgba(16,185,129,.12); color: var(--r-green);   }
.receipt-card-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: .12em; color: var(--r-accent); text-transform: uppercase; margin: 0 0 2px; }
.receipt-card-title   { font-size: 14.5px; font-weight: 700; color: var(--r-text-primary); margin: 0; transition: color .22s; }
.item-count-badge {
  margin-left: auto; font-size: 11px; font-weight: 700;
  background: var(--r-accent-soft); color: var(--r-accent);
  border: 1px solid var(--r-accent-border); border-radius: 20px; padding: 3px 10px;
}

/* INFO LIST */
.info-list { padding: 14px 20px; display: flex; flex-direction: column; gap: 10px; }
.info-grid { padding: 14px 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px 24px; }
.info-row  { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.info-label { font-size: 12px; font-weight: 600; color: var(--r-text-faint); text-transform: uppercase; letter-spacing: .06em; }
.info-val   { font-size: 13.5px; font-weight: 600; color: var(--r-text-primary); transition: color .22s; }
.info-val--mono { font-variant-numeric: tabular-nums; font-family: 'SF Mono', 'Fira Code', monospace; font-size: 12.5px; }

/* SUMMARY LIST */
.summary-list { padding: 14px 20px; display: flex; flex-direction: column; gap: 8px; }
.summary-row  { display: flex; justify-content: space-between; font-size: 13px; color: var(--r-text-muted); }
.summary-row > span:last-child  { font-weight: 600; color: var(--r-text-secondary); }
.summary-row--disc > span:last-child  { color: var(--r-green); }
.summary-row--total {
  font-size: 15px; font-weight: 800; color: var(--r-text-primary);
  padding: 10px 0 8px; margin-top: 4px;
  border-top: 1px solid var(--r-border-strong);
  transition: border-color .22s;
}
.summary-row--total > span:last-child { color: var(--r-accent); font-size: 16px; }
.summary-row--change > span:last-child { color: var(--r-green); font-weight: 700; }
.summary-divider { height: 1px; background: var(--r-border); margin: 2px 0; }

/* PILLS */
.pill { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; border-radius: 20px; padding: 3px 10px; letter-spacing: .04em; }
.pill--green { background: rgba(16,185,129,.14); color: var(--r-green); }
html[data-theme="dark"] .pill--green { background: rgba(16,185,129,.18); color: #4ADE80; }

/* TABLE */
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.data-table thead th {
  background: var(--r-surface-raised); color: var(--r-text-faint);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  text-align: left; padding: 11px 16px; border-bottom: 1px solid var(--r-border);
  white-space: nowrap; transition: background-color .22s, color .22s, border-color .22s;
}
.data-table tbody td {
  padding: 13px 16px; color: var(--r-text-secondary);
  border-bottom: 1px solid var(--r-border);
  transition: color .22s, border-color .22s, background-color .15s;
}
.data-table tbody tr:hover td { background: var(--r-surface-raised); }
.data-table tbody tr:last-child td { border-bottom: none; }
.data-table tfoot td {
  padding: 13px 16px; font-weight: 700;
  border-top: 2px solid var(--r-border-strong);
  transition: border-color .22s;
}
.cell-num     { color: var(--r-text-faint); font-size: 12px; width: 40px; }
.cell-product { font-weight: 600; color: var(--r-text-primary); }
.cell-price   { font-variant-numeric: tabular-nums; color: var(--r-text-muted); }
.cell-qty     { text-align: center; color: var(--r-text-secondary); font-weight: 600; }
.cell-total   { font-weight: 700; color: var(--r-accent); font-variant-numeric: tabular-nums; }
.tfoot-label  { color: var(--r-text-secondary); font-size: 13px; text-align: right; }
.tfoot-val    { color: var(--r-accent); font-size: 16px; font-weight: 800; font-variant-numeric: tabular-nums; }

/* RESPONSIVE */
@media (max-width: 700px) {
  .receipt-page { padding: 16px 16px 48px; }
  .receipt-grid { grid-template-columns: 1fr; }
  .info-grid    { grid-template-columns: 1fr; }
  .page-header  { flex-direction: column; }
}
</style>

<style>
@media print {
  body * { visibility: hidden !important; }
  .receipt-page, .receipt-page * { visibility: visible !important; }
  .receipt-page {
    position: absolute; left: 0; top: 0; width: 100%; padding: 20px; background: white;
  }
  .no-print, .header-actions, .btn { display: none !important; }
  .receipt-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
  thead { display: table-header-group !important; }
  tr { page-break-inside: avoid !important; }
}
</style>