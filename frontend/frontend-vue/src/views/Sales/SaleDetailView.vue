<template>
  <Layout>
    <div class="rc-page">

      <!-- ── BREADCRUMB ── -->
      <nav class="rc-crumbs" aria-label="Breadcrumb">
        <RouterLink to="/sales-transaction">Sales receipts</RouterLink>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span aria-current="page">{{ receiptNo }}</span>
      </nav>

      <!-- ── LOADING ── -->
      <div v-if="loading" class="rc-grid" aria-busy="true" aria-live="polite">
        <div class="rc-doc rc-skel-block">
          <div class="rc-skel rc-skel--title"></div>
          <div class="rc-skel" v-for="n in 5" :key="n"></div>
        </div>
        <div class="rc-card rc-skel-block">
          <div class="rc-skel rc-skel--title"></div>
          <div class="rc-skel" v-for="n in 3" :key="n"></div>
        </div>
      </div>

      <!-- ── ERROR ── -->
      <div v-else-if="error" class="rc-state" role="alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <p>{{ error }}</p>
        <div class="rc-state-actions">
          <button v-if="canRetry" class="rc-btn rc-btn--ghost" @click="load">Try again</button>
          <RouterLink to="/sales-transaction" class="rc-btn rc-btn--ghost">Back to sales</RouterLink>
        </div>
      </div>

      <!-- ── CONTENT ── -->
      <div v-else-if="sale" class="rc-grid">

        <!-- RECEIPT DOCUMENT (customer-facing: NO worker, NO percentages) -->
        <article class="rc-doc" aria-label="Receipt">
          <header class="rc-letterhead">
            <div>
              <p class="rc-company">Sheem Steel Construction</p>
              <p class="rc-tagline">Roofing &amp; Steel Supplies</p>
            </div>
            <div class="rc-docid">
              <p class="rc-docid-label">Sales receipt</p>
              <h1 class="rc-docid-no">{{ receiptNo }}</h1>
            </div>
          </header>

          <dl class="rc-meta">
            <div><dt>Date</dt><dd>{{ dateText || '—' }}</dd></div>
            <div><dt>Customer</dt><dd>{{ customerName }}</dd></div>
            <div><dt>Cashier</dt><dd>{{ sale.clerk?.name || 'Admin' }}</dd></div>
            <div><dt>Payment</dt><dd>{{ sale.PaymentMethod || 'Cash' }}</dd></div>
          </dl>

          <div class="rc-table-wrap">
            <table class="rc-table">
              <thead>
                <tr>
                  <th scope="col">Item</th>
                  <th scope="col" class="r">Qty</th>
                  <th scope="col" class="r">Unit price</th>
                  <th scope="col" class="r">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(it, i) in items" :key="it.SalesItemID ?? it.id ?? i">
                  <td data-label="Item">
                    <span class="rc-item-name">{{ productName(it) }}</span>
                    <span v-if="it.VariantLabel" class="rc-size">{{ it.VariantLabel }}</span>
                  </td>
                  <td data-label="Qty" class="r">{{ it.Quantity }}</td>
                  <td data-label="Unit price" class="r">₱{{ money(it.PriceAtSale) }}</td>
                  <td data-label="Amount" class="r rc-amount">₱{{ money(lineTotal(it)) }}</td>
                </tr>

                <!-- Extra services: description + full fee only -->
                <tr v-for="(sv, i) in services" :key="'sv' + i" class="rc-svc-row">
                  <td data-label="Item">
                    <span class="rc-item-name">{{ sv.Description }}</span>
                    <span class="rc-size">Service</span>
                  </td>
                  <td data-label="Qty" class="r">1</td>
                  <td data-label="Unit price" class="r">₱{{ money(sv.Fee) }}</td>
                  <td data-label="Amount" class="r rc-amount">₱{{ money(sv.Fee) }}</td>
                </tr>

                <tr v-if="!items.length && !services.length">
                  <td colspan="4" class="rc-empty">No items were recorded on this sale.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="rc-totals">
            <div class="rc-line"><span>Subtotal</span><span>₱{{ money(subtotal) }}</span></div>
            <div v-if="discount > 0" class="rc-line rc-line--disc">
              <span>Discount</span><span>− ₱{{ money(discount) }}</span>
            </div>
            <div class="rc-line"><span>VATable sales</span><span>₱{{ money(vatable) }}</span></div>
            <div class="rc-line"><span>VAT (12% included)</span><span>₱{{ money(vat) }}</span></div>
            <div v-if="serviceTotal > 0" class="rc-line"><span>Service fee</span><span>₱{{ money(serviceTotal) }}</span></div>
            <div class="rc-line rc-line--total"><span>Total</span><span>₱{{ money(total) }}</span></div>
            <div class="rc-line rc-line--paid"><span>Cash received</span><span>₱{{ money(sale.AmountPaid) }}</span></div>
            <div class="rc-line rc-line--paid"><span>Change</span><span>₱{{ money(change) }}</span></div>
          </div>

          <footer class="rc-note">Prices include 12% VAT. Thank you for your purchase.</footer>
        </article>

        <!-- SIDE -->
        <aside class="rc-side">
          <section class="rc-card" aria-label="Payment summary">
            <span class="rc-pill" :class="statusClass">{{ statusText }}</span>
            <p class="rc-hero-label">Total amount</p>
            <p class="rc-hero-amount">₱{{ money(total) }}</p>

            <dl class="rc-facts">
              <div><dt>Items</dt><dd>{{ totalQty }} unit{{ totalQty === 1 ? '' : 's' }} · {{ items.length }} line{{ items.length === 1 ? '' : 's' }}</dd></div>
              <div v-if="services.length"><dt>Services</dt><dd>{{ services.length }} · ₱{{ money(serviceTotal) }}</dd></div>
              <div><dt>Cash received</dt><dd>₱{{ money(sale.AmountPaid) }}</dd></div>
              <div><dt>Change given</dt><dd>₱{{ money(change) }}</dd></div>
            </dl>

            <button class="rc-btn rc-btn--primary" @click="printReceipt">
              <i class="fa-solid fa-print"></i> Print receipt
            </button>
            <RouterLink to="/sales-transaction" class="rc-btn rc-btn--ghost">
              <i class="fa-solid fa-arrow-left"></i> Back to sales
            </RouterLink>
          </section>

          <!-- STAFF ONLY — lives outside both receipts and is hidden when printing -->
          <section v-if="services.length || cuts.length" class="rc-card rc-internal" aria-label="Staff only">
            <p class="rc-internal-tag"><i class="fa-solid fa-lock"></i> Staff only · never printed</p>

            <template v-if="services.length">
              <h2 class="rc-internal-title">Service breakdown</h2>
              <div v-for="(sv, i) in services" :key="'ib' + i" class="rc-split">
                <p class="rc-split-name">{{ sv.Description }} <span>₱{{ money(sv.Fee) }}</span></p>
                <div class="rc-split-row">
                  <span>Worker: {{ sv.WorkerName }}</span>
                  <span>₱{{ money(sv.WorkerAmount) }} ({{ pct(sv.WorkerPct) }})</span>
                </div>
                <div class="rc-split-row rc-split-row--owner">
                  <span>Owner</span>
                  <span>₱{{ money(sv.OwnerAmount) }} ({{ pct(sv.OwnerPct) }})</span>
                </div>
              </div>
              <div class="rc-split-total">
                <div class="rc-split-row"><span>Total worker payout</span><span>₱{{ money(serviceWorker) }}</span></div>
                <div class="rc-split-row rc-split-row--owner"><span>Total owner share</span><span>₱{{ money(serviceOwner) }}</span></div>
              </div>
            </template>

            <template v-if="cuts.length">
              <h2 class="rc-internal-title">Cut pieces</h2>
              <p v-for="(c, i) in cuts" :key="'ct' + i" class="rc-cut">
                <i class="fa-solid fa-scissors"></i>
                {{ c.ProductName }}<template v-if="c.VariantLabel"> – {{ c.VariantLabel }}</template>:
                {{ c.quantity }} pc, cut {{ lenText(c.cut_sold_length) }} of {{ lenText(c.cut_full_length) }} {{ c.cut_unit }},
                {{ lenText(c.cut_remaining_length) }} {{ c.cut_unit }} left
              </p>
            </template>
          </section>
        </aside>
      </div>
    </div>
  </Layout>

  <!-- ── PRINT RECEIPT (80mm thermal) — customer copy: description + fee only ── -->
  <div id="sale-receipt" v-if="sale">
    <div class="r-store">SHEEM STEEL CONSTRUCTION</div>
    <div class="r-tagline">Roofing &amp; Steel Supplies</div>
    <div class="r-divider"></div>
    <div class="r-row"><span>Receipt #</span><span>{{ receiptNo }}</span></div>
    <div class="r-row"><span>Date</span><span>{{ dateText }}</span></div>
    <div class="r-row"><span>Customer</span><span>{{ customerName }}</span></div>
    <div class="r-row"><span>Cashier</span><span>{{ sale.clerk?.name || 'Admin' }}</span></div>
    <div class="r-divider"></div>
    <div v-for="(it, i) in items" :key="i" class="r-item">
      <div class="r-item-name">
        {{ productName(it) }}<span v-if="it.VariantLabel"> – {{ it.VariantLabel }}</span>
      </div>
      <div class="r-row">
        <span>{{ it.Quantity }} × ₱{{ money(it.PriceAtSale) }}</span>
        <span>₱{{ money(lineTotal(it)) }}</span>
      </div>
    </div>
    <template v-if="services.length">
      <div class="r-divider"></div>
      <div v-for="(sv, i) in services" :key="'psv' + i" class="r-item">
        <div class="r-item-name">{{ sv.Description }}</div>
        <div class="r-row"><span>Service fee</span><span>₱{{ money(sv.Fee) }}</span></div>
      </div>
    </template>
    <div class="r-divider"></div>
    <div class="r-row"><span>Subtotal</span><span>₱{{ money(subtotal) }}</span></div>
    <div class="r-row" v-if="discount > 0"><span>Discount</span><span>- ₱{{ money(discount) }}</span></div>
    <div class="r-row"><span>VATable Sales</span><span>₱{{ money(vatable) }}</span></div>
    <div class="r-row"><span>VAT (12% Incl.)</span><span>₱{{ money(vat) }}</span></div>
    <div class="r-row" v-if="serviceTotal > 0"><span>Service Fee</span><span>₱{{ money(serviceTotal) }}</span></div>
    <div class="r-divider r-divider--solid"></div>
    <div class="r-row r-grand"><span>TOTAL</span><span>₱{{ money(total) }}</span></div>
    <div class="r-divider"></div>
    <div class="r-row"><span>Cash</span><span>₱{{ money(sale.AmountPaid) }}</span></div>
    <div class="r-row"><span>Change</span><span>₱{{ money(change) }}</span></div>
    <div class="r-divider"></div>
    <div class="r-footer"><p>Thank you for your purchase!</p><p>Please come again.</p></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

/* The router passes :id as a prop (props: true); fall back to the route. */
const props = defineProps({ id: { type: [String, Number], default: null } })
const route = useRoute()
const saleId = computed(() => props.id ?? route.params.id)

const sale     = ref(null)
const loading  = ref(true)
const error    = ref('')
const canRetry = ref(true)
let requestSeq = 0 // ignores out-of-date responses when the id changes quickly

/* ── helpers ── */
const VAT_DIVISOR = 1.12
const num    = v => Number(v) || 0
const round2 = n => Math.round((Number(n) || 0) * 100) / 100
const money  = v => num(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const pct    = v => `${Number(num(v).toFixed(1))}%`
const lenText = n => num(n).toLocaleString('en-US', { maximumFractionDigits: 3 })

/* ── load ── */
async function load() {
  const seq = ++requestSeq
  loading.value = true
  error.value = ''
  canRetry.value = true
  try {
    const { data } = await api.get(`/api/sales/${saleId.value}`)
    if (seq !== requestSeq) return
    sale.value = data
  } catch (e) {
    if (seq !== requestSeq) return
    sale.value = null
    const status = e?.response?.status
    if (status === 404) {
      error.value = `Receipt ${receiptNo.value} was not found. It may have been deleted.`
      canRetry.value = false
    } else if (status === 401) {
      error.value = 'Your session expired. Log in again to view this receipt.'
      canRetry.value = false
    } else {
      error.value = 'We couldn’t load this receipt. Check your connection and try again.'
    }
  } finally {
    if (seq === requestSeq) loading.value = false
  }
}
onMounted(load)
watch(saleId, load)

/* ── derived data ── */
const items       = computed(() => sale.value?.sales_items ?? sale.value?.salesItems ?? [])
const totalQty    = computed(() => items.value.reduce((s, it) => s + num(it.Quantity), 0))
const lineTotal   = it => num(it.Subtotal) || num(it.Quantity) * num(it.PriceAtSale)
const productName = it => it.product?.ProductName || `Product #${it.ProductID} (removed)`

const receiptNo = computed(() =>
  sale.value?.receipt_number || `SR-${String(saleId.value ?? '').padStart(5, '0')}`
)
const customerName = computed(() => sale.value?.customer?.CustomerCode || 'Walk-in')

const dateText = computed(() => {
  const d = sale.value?.SaleDate ? new Date(sale.value.SaleDate) : null
  if (!d || Number.isNaN(d.getTime())) return ''
  return d.toLocaleString('en-PH', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: 'numeric', minute: '2-digit',
  })
})

const statusText  = computed(() => sale.value?.Status || 'Paid')
const statusClass = computed(() => {
  const s = String(statusText.value).toLowerCase()
  if (s === 'paid') return 'rc-pill--ok'
  if (['refunded', 'cancelled', 'void'].includes(s)) return 'rc-pill--bad'
  return 'rc-pill--warn'
})

/* TotalAmount = products (after discount, VAT-inclusive) + FULL service fees.
   VAT only applies to the product part, same as the POS. */
const services      = computed(() => sale.value?.services ?? [])
const cuts          = computed(() => sale.value?.cuts ?? [])
const serviceTotal  = computed(() => round2(services.value.reduce((s, x) => s + num(x.Fee), 0)))
const serviceWorker = computed(() => round2(services.value.reduce((s, x) => s + num(x.WorkerAmount), 0)))
const serviceOwner  = computed(() => round2(serviceTotal.value - serviceWorker.value))

const subtotal      = computed(() => round2(items.value.reduce((s, it) => s + lineTotal(it), 0)))
const discount      = computed(() => num(sale.value?.DiscountAmount))
const total         = computed(() => num(sale.value?.TotalAmount))
const productsGross = computed(() => round2(Math.max(total.value - serviceTotal.value, 0)))
const vatable       = computed(() => round2(productsGross.value / VAT_DIVISOR))
const vat           = computed(() => round2(productsGross.value - vatable.value))
const change        = computed(() => round2(Math.max(num(sale.value?.AmountPaid) - total.value, 0)))

function printReceipt() { window.print() }
</script>

<style scoped>
.rc-page {
  padding: 24px 28px 64px;
  display: flex; flex-direction: column; gap: 18px;
  max-width: 1120px;
  font-family: 'Inter', system-ui, sans-serif;
}

/* ── breadcrumb ── */
.rc-crumbs { display: flex; align-items: center; gap: 9px; font-size: 13px; color: var(--c-text-muted, #94a3b8); }
.rc-crumbs a { color: var(--c-text-muted, #94a3b8); text-decoration: none; font-weight: 500; transition: color .15s; }
.rc-crumbs a:hover { color: var(--c-accent, #f97316); }
.rc-crumbs i { font-size: 9px; color: var(--c-text-faint, #64748b); }
.rc-crumbs span { color: var(--c-text-primary, #f1f5f9); font-weight: 600; font-variant-numeric: tabular-nums; }

/* ── layout ── */
.rc-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; align-items: start; }
.rc-side { position: sticky; top: 16px; display: flex; flex-direction: column; gap: 16px; }

/* ── receipt document ── */
.rc-doc {
  background: var(--c-surface, #1e293b);
  border: 1px solid var(--c-border, #334155);
  border-top: 3px solid var(--c-accent, #f97316);
  border-radius: 14px;
  box-shadow: var(--c-shadow-sm, 0 1px 3px rgba(0,0,0,.3));
  padding: 28px 32px 22px;
  display: flex; flex-direction: column; gap: 24px;
}
.rc-letterhead {
  display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap;
  padding-bottom: 22px; border-bottom: 1px solid var(--c-border, #334155);
}
.rc-company { margin: 0; font-size: 18px; font-weight: 800; letter-spacing: -.01em; color: var(--c-text-primary, #f1f5f9); }
.rc-tagline { margin: 3px 0 0; font-size: 13px; color: var(--c-text-muted, #94a3b8); }
.rc-docid { text-align: right; }
.rc-docid-label { margin: 0 0 3px; font-size: 13px; color: var(--c-text-muted, #94a3b8); }
.rc-docid-no {
  margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -.03em;
  color: var(--c-text-primary, #f1f5f9); font-variant-numeric: tabular-nums;
}

.rc-meta {
  margin: 0; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px;
}
.rc-meta dt { font-size: 12px; color: var(--c-text-faint, #64748b); margin-bottom: 4px; }
.rc-meta dd { margin: 0; font-size: 14px; font-weight: 600; color: var(--c-text-primary, #f1f5f9); word-break: break-word; }

/* ── items ── */
.rc-table-wrap { overflow-x: auto; border: 1px solid var(--c-border, #334155); border-radius: 10px; }
.rc-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.rc-table th {
  text-align: left; padding: 11px 16px; font-size: 12px; font-weight: 600; white-space: nowrap;
  color: var(--c-text-muted, #94a3b8); background: var(--c-surface-raised, #26364b);
}
.rc-table td {
  padding: 14px 16px; border-top: 1px solid var(--c-border, #334155);
  color: var(--c-text-secondary, #cbd5e1); vertical-align: middle;
}
.rc-table .r { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.rc-item-name { font-weight: 600; color: var(--c-text-primary, #f1f5f9); }
.rc-size {
  display: inline-block; margin-left: 8px; padding: 1px 9px; border-radius: 20px;
  font-size: 11.5px; font-weight: 700;
  background: var(--c-accent-soft, rgba(249,115,22,.15)); color: var(--c-accent, #f97316);
  border: 1px solid var(--c-accent-border, rgba(249,115,22,.35));
}
.rc-amount { font-weight: 700; color: var(--c-text-primary, #f1f5f9) !important; }
.rc-empty { text-align: center; padding: 32px 16px !important; color: var(--c-text-faint, #64748b) !important; }
.rc-svc-row td { background: var(--c-surface-raised, #26364b); }

/* ── totals ── */
.rc-totals { align-self: flex-end; width: 100%; max-width: 340px; display: flex; flex-direction: column; gap: 9px; }
.rc-line { display: flex; justify-content: space-between; gap: 16px; font-size: 13.5px; color: var(--c-text-muted, #94a3b8); }
.rc-line span:last-child { font-weight: 600; color: var(--c-text-secondary, #cbd5e1); font-variant-numeric: tabular-nums; }
.rc-line--disc span:last-child { color: #10b981; }
.rc-line--total {
  margin-top: 4px; padding: 12px 0; font-size: 16px;
  border-top: 1px solid var(--c-border-strong, #47566b);
  border-bottom: 1px solid var(--c-border-strong, #47566b);
}
.rc-line--total span { font-weight: 800 !important; color: var(--c-text-primary, #f1f5f9) !important; }
.rc-line--paid { font-size: 13px; }
.rc-note {
  padding-top: 16px; border-top: 1px dashed var(--c-border, #334155);
  text-align: center; font-size: 12.5px; color: var(--c-text-faint, #64748b);
}

/* ── side card ── */
.rc-card {
  background: var(--c-surface, #1e293b);
  border: 1px solid var(--c-border, #334155);
  border-radius: 14px; padding: 22px;
  box-shadow: var(--c-shadow-sm, 0 1px 3px rgba(0,0,0,.3));
  display: flex; flex-direction: column; gap: 10px;
}
.rc-pill {
  align-self: flex-start; font-size: 12px; font-weight: 700;
  padding: 4px 12px; border-radius: 20px; border: 1px solid transparent;
}
.rc-pill--ok   { background: rgba(16,185,129,.14); color: #10b981; border-color: rgba(16,185,129,.28); }
.rc-pill--warn { background: rgba(245,158,11,.14); color: #f59e0b; border-color: rgba(245,158,11,.28); }
.rc-pill--bad  { background: rgba(244,63,94,.14);  color: #f43f5e; border-color: rgba(244,63,94,.28); }
.rc-hero-label { margin: 8px 0 0; font-size: 13px; color: var(--c-text-muted, #94a3b8); }
.rc-hero-amount {
  margin: 0 0 6px; font-size: 32px; font-weight: 800; letter-spacing: -.04em; line-height: 1.1;
  color: var(--c-text-primary, #f1f5f9); font-variant-numeric: tabular-nums;
}
.rc-facts { margin: 0 0 8px; display: flex; flex-direction: column; border-top: 1px solid var(--c-border, #334155); }
.rc-facts > div {
  display: flex; justify-content: space-between; gap: 12px; padding: 11px 0;
  border-bottom: 1px solid var(--c-border, #334155); font-size: 13px;
}
.rc-facts dt { color: var(--c-text-muted, #94a3b8); }
.rc-facts dd { margin: 0; font-weight: 600; color: var(--c-text-primary, #f1f5f9); text-align: right; font-variant-numeric: tabular-nums; }

/* ── staff-only card ── */
.rc-internal { border-style: dashed; }
.rc-internal-tag { margin: 0; font-size: 11.5px; font-weight: 700; color: var(--c-text-faint, #64748b); text-transform: uppercase; letter-spacing: .06em; }
.rc-internal-title { margin: 6px 0 0; font-size: 14px; font-weight: 700; color: var(--c-text-primary, #f1f5f9); }
.rc-split { padding: 10px 0; border-bottom: 1px solid var(--c-border, #334155); display: flex; flex-direction: column; gap: 4px; }
.rc-split-name { margin: 0; display: flex; justify-content: space-between; gap: 10px; font-size: 13px; font-weight: 700; color: var(--c-text-primary, #f1f5f9); }
.rc-split-row { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; color: var(--c-text-muted, #94a3b8); font-variant-numeric: tabular-nums; }
.rc-split-row--owner span:last-child { color: #10b981; font-weight: 700; }
.rc-split-total { display: flex; flex-direction: column; gap: 4px; padding-top: 6px; }
.rc-cut { margin: 0; font-size: 12.5px; line-height: 1.5; color: var(--c-text-secondary, #cbd5e1); }
.rc-cut i { color: var(--c-accent, #f97316); margin-right: 6px; }

/* ── buttons ── */
.rc-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  height: 42px; padding: 0 16px; width: 100%;
  font-size: 13.5px; font-weight: 600; font-family: inherit; text-decoration: none;
  border-radius: 10px; border: 1.5px solid transparent; cursor: pointer;
  transition: filter .15s, border-color .15s, color .15s, transform .15s;
}
.rc-btn:active { transform: scale(.98); }
.rc-btn:focus-visible, .rc-crumbs a:focus-visible { outline: 2px solid var(--c-accent, #f97316); outline-offset: 2px; }
.rc-btn--primary {
  background: linear-gradient(135deg, var(--c-accent, #f97316), var(--c-accent-deep, #c2410c));
  color: #fff; box-shadow: var(--c-shadow-accent, 0 4px 14px rgba(249,115,22,.35));
}
.rc-btn--primary:hover { filter: brightness(1.08); }
.rc-btn--ghost {
  background: transparent; border-color: var(--c-border-strong, #47566b); color: var(--c-text-secondary, #cbd5e1);
}
.rc-btn--ghost:hover { border-color: var(--c-accent-border, rgba(249,115,22,.35)); color: var(--c-accent, #f97316); }

/* ── states ── */
.rc-state {
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  padding: 56px 20px; text-align: center; color: var(--c-text-muted, #94a3b8);
  background: var(--c-surface, #1e293b); border: 1px solid var(--c-border, #334155); border-radius: 14px;
}
.rc-state i { font-size: 30px; color: #f43f5e; }
.rc-state p { margin: 0; font-size: 14px; max-width: 420px; line-height: 1.5; }
.rc-state-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-top: 6px; }
.rc-state-actions .rc-btn { width: auto; }

.rc-skel-block { padding: 24px; display: flex; flex-direction: column; gap: 14px; }
.rc-card.rc-skel-block { border-radius: 14px; }
.rc-skel {
  height: 16px; border-radius: 8px;
  background: linear-gradient(90deg, var(--c-surface-raised, #26364b) 25%, var(--c-border, #334155) 50%, var(--c-surface-raised, #26364b) 75%);
  background-size: 200% 100%; animation: rc-shimmer 1.4s linear infinite;
}
.rc-skel--title { height: 28px; width: 40%; }
@keyframes rc-shimmer { to { background-position: -200% 0; } }

/* ── responsive ── */
@media (max-width: 960px) {
  .rc-grid { grid-template-columns: 1fr; }
  .rc-side { position: static; order: -1; }
}
@media (max-width: 640px) {
  .rc-page { padding: 16px 16px 48px; }
  .rc-doc { padding: 20px 18px 18px; }
  .rc-letterhead { flex-direction: column; }
  .rc-docid { text-align: left; }
  .rc-meta { grid-template-columns: 1fr 1fr; }
  .rc-totals { max-width: none; }

  /* Items become stacked rows instead of a squeezed table */
  .rc-table thead { display: none; }
  .rc-table, .rc-table tbody, .rc-table tr, .rc-table td { display: block; width: 100%; }
  .rc-table tbody tr { padding: 12px 16px; border-top: 1px solid var(--c-border, #334155); }
  .rc-table tbody tr:first-child { border-top: none; }
  .rc-table td { border: none; padding: 3px 0; display: flex; justify-content: space-between; gap: 12px; }
  .rc-table td::before { content: attr(data-label); color: var(--c-text-faint, #64748b); font-size: 12px; }
  .rc-table td[data-label="Item"] { padding-bottom: 6px; display: block; }
  .rc-table td[data-label="Item"]::before { content: none; }
}

@media (prefers-reduced-motion: reduce) {
  .rc-skel { animation: none; }
  .rc-btn { transition: none; }
}

/* Staff-only data must never reach paper */
@media print { .rc-internal { display: none !important; } }
</style>

<style>
/* Print receipt: hidden on screen, the only thing visible when printing. */
#sale-receipt { display: none; }

@media print {
  @page { margin: 6mm; }
  body * { visibility: hidden !important; }
  #sale-receipt, #sale-receipt * { visibility: visible !important; }
  #sale-receipt {
    display: block !important; position: absolute; left: 0; top: 0;
    width: 302px; padding: 14px 16px 20px; background: #fff; color: #000;
    font-family: 'Courier New', Consolas, monospace; font-size: 11.5px; line-height: 1.5;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  .r-store { text-align: center; font-size: 16px; font-weight: 700; letter-spacing: .04em; }
  .r-tagline { text-align: center; font-size: 10.5px; margin-top: 2px; }
  .r-divider { border-top: 1px dashed #000; margin: 9px 0; }
  .r-divider--solid { border-top: 1px solid #000; }
  .r-row { display: flex; justify-content: space-between; gap: 8px; margin: 2px 0; }
  .r-row > span:last-child { text-align: right; }
  .r-item-name { font-weight: 700; text-transform: uppercase; margin-top: 6px; }
  .r-grand { font-size: 15px; font-weight: 800; }
  .r-footer { text-align: center; }
  .r-footer p { margin: 2px 0; }
}
</style>