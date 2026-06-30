<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Layout from '@/components/Layout.vue'

const route  = useRoute()
const router = useRouter()

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})
api.interceptors.request.use((config) => {
  const match = document.cookie.match(/(^| )XSRF-TOKEN=([^;]+)/)
  if (match) config.headers['X-XSRF-TOKEN'] = decodeURIComponent(match[2])
  return config
})

const products      = ref([])
const product       = ref({})
const transactions  = ref([])
const history       = ref([])

const summary = ref({ total_sales: 0, total_returns: 0, units_sold: 0, units_returned: 0 })

const productSearch     = ref('')
const transactionSearch = ref('')
const activeTab          = ref('transactions')
const sortBy             = ref('date_desc')

const showModal  = ref(false)
const modalData  = ref(null)
const loading    = ref(true)

const fetchProducts     = async () => { const { data } = await api.get('/products'); products.value = data }
const fetchProduct      = async () => { const { data } = await api.get(`/products/${route.params.id}`); product.value = data }
const fetchTransactions = async () => {
  const { data } = await api.get(`/products/${route.params.id}/transactions`, { params: { search: transactionSearch.value, sort: sortBy.value } })
  transactions.value = data.transactions.data
}
const fetchSummary = async () => { const { data } = await api.get(`/products/${route.params.id}/summary`); summary.value = data }
const fetchHistory = async () => { const { data } = await api.get(`/products/${route.params.id}/history`); history.value = data.data }

const filteredProducts = computed(() => {
  if (!productSearch.value.trim()) return products.value
  const term = productSearch.value.toLowerCase()
  return products.value.filter(p => (p.ProductName ?? '').toLowerCase().includes(term) || (p.SKU ?? '').toLowerCase().includes(term))
})

watch([sortBy, transactionSearch], fetchTransactions)
watch(() => route.params.id, async () => {
  loading.value = true
  await fetchProduct(); await fetchTransactions(); await fetchSummary(); await fetchHistory()
  loading.value = false
})

function openModal(t) { modalData.value = t; showModal.value = true }
function closeModal()  { showModal.value = false }

function typeBadgeClass(type) {
  const t = (type || '').toLowerCase()
  if (t.includes('return')) return 'type-badge--return'
  if (t.includes('sale'))   return 'type-badge--sale'
  return 'type-badge--neutral'
}

onMounted(async () => {
  await fetchProducts(); await fetchProduct()
  await fetchTransactions(); await fetchSummary(); await fetchHistory()
  loading.value = false
})
</script>

<template>
  <Layout>
    <div class="pt-root">

      <!-- ══ LEFT SIDEBAR — PRODUCT LIST ══ -->
      <aside class="pt-sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title">All Items</h2>
          <RouterLink to="/products/create" class="sidebar-add-btn">
            <i class="fa-solid fa-plus"></i> New
          </RouterLink>
        </div>

        <div class="sidebar-search">
          <i class="fa-solid fa-magnifying-glass search-icon"></i>
          <input v-model="productSearch" placeholder="Search products…" class="search-input" />
        </div>

        <ul class="sidebar-list">
          <li
            v-for="item in filteredProducts"
            :key="item.ProductID"
            @click="router.push(`/products/${item.ProductID}/transactions`)"
            class="sidebar-item"
            :class="{ 'sidebar-item--active': String(item.ProductID) === String(route.params.id) }"
          >
            <div class="sidebar-item-main">
              <p class="sidebar-item-name">{{ item.ProductName }}</p>
              <span class="sidebar-item-sku">SKU: {{ item.SKU }}</span>
              <div class="sidebar-item-stock">Stock: {{ item.Stock }}</div>
            </div>
            <span class="sidebar-item-price">₱{{ item.SellingPrice }}</span>
          </li>
        </ul>
      </aside>

      <!-- ══ RIGHT PANEL ══ -->
      <div class="pt-main">

        <!-- HEADER -->
        <header class="pt-header">
          <div>
            <h1 class="pt-title">{{ product.ProductName || 'Product Transactions' }}</h1>
            <p class="pt-subtitle">Inventory &amp; Sales Transactions</p>
          </div>
          <div class="pt-header-actions">
            <RouterLink to="/products" class="btn btn--ghost">
              <i class="fa-solid fa-arrow-left"></i> Back to Products
            </RouterLink>
            <RouterLink to="/products/create" class="btn btn--primary">
              <i class="fa-solid fa-plus"></i> Add Product
            </RouterLink>
          </div>
        </header>

        <!-- BODY -->
        <main class="pt-body">

          <div class="search-wrap">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input v-model="transactionSearch" placeholder="Search transactions…" class="search-input search-input--wide" />
          </div>

          <!-- SUMMARY -->
          <div class="summary-grid">
            <div class="summary-card">
              <div class="summary-icon summary-icon--green"><i class="fa-solid fa-peso-sign"></i></div>
              <div>
                <p class="summary-label">Total Sales</p>
                <p class="summary-val">₱{{ Number(summary.total_sales).toLocaleString() }}</p>
              </div>
            </div>
            <div class="summary-card">
              <div class="summary-icon summary-icon--red"><i class="fa-solid fa-rotate-left"></i></div>
              <div>
                <p class="summary-label">Total Returns</p>
                <p class="summary-val">₱{{ Number(summary.total_returns).toLocaleString() }}</p>
              </div>
            </div>
            <div class="summary-card">
              <div class="summary-icon summary-icon--blue"><i class="fa-solid fa-box-open"></i></div>
              <div>
                <p class="summary-label">Units Sold</p>
                <p class="summary-val">{{ summary.units_sold }}</p>
              </div>
            </div>
            <div class="summary-card">
              <div class="summary-icon summary-icon--amber"><i class="fa-solid fa-rotate"></i></div>
              <div>
                <p class="summary-label">Units Returned</p>
                <p class="summary-val">{{ summary.units_returned }}</p>
              </div>
            </div>
          </div>

          <!-- TABS -->
          <div class="tabs-row">
            <button class="tab-btn" :class="{ 'tab-btn--active': activeTab === 'transactions' }" @click="activeTab = 'transactions'">
              Transactions
            </button>
            <button class="tab-btn" :class="{ 'tab-btn--active': activeTab === 'history' }" @click="activeTab = 'history'">
              History
            </button>
          </div>

          <!-- TRANSACTIONS TABLE -->
          <div v-if="activeTab === 'transactions'" class="tab-panel">
            <div class="select-wrap">
              <select v-model="sortBy" class="sort-select">
                <option value="date_desc">Date (Newest)</option>
                <option value="date_asc">Date (Oldest)</option>
                <option value="amount_desc">Amount (High)</option>
                <option value="amount_asc">Amount (Low)</option>
              </select>
              <i class="fa-solid fa-chevron-down select-caret"></i>
            </div>

            <div class="table-card">
              <div class="table-wrap">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Type</th>
                      <th>Qty</th>
                      <th>Unit Price</th>
                      <th>Total</th>
                      <th>Reference</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="t in transactions" :key="t.id" @click="openModal(t)" class="clickable-row">
                      <td class="cell-muted">{{ t.date }}</td>
                      <td><span class="type-badge" :class="typeBadgeClass(t.type)">{{ t.type }}</span></td>
                      <td class="cell-mono">{{ Math.abs(t.quantity) }}</td>
                      <td class="cell-mono">₱{{ t.unit_price }}</td>
                      <td class="cell-total">₱{{ t.total }}</td>
                      <td class="cell-faint">{{ t.reference }}</td>
                    </tr>
                    <tr v-if="transactions.length === 0">
                      <td colspan="6" class="td-empty">
                        <div class="empty-state">
                          <i class="fa-solid fa-receipt"></i>
                          <p>No transactions found</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- HISTORY -->
          <div v-if="activeTab === 'history'" class="tab-panel history-list">
            <div v-for="h in history" :key="h.event_date" class="history-item">
              <div class="history-dot"></div>
              <div>
                <p class="history-desc">{{ h.description }}</p>
                <p class="history-date">{{ h.event_date }}</p>
              </div>
            </div>
            <div v-if="history.length === 0" class="empty-state empty-state--inline">
              <i class="fa-solid fa-clock-rotate-left"></i>
              <p>No history events found</p>
            </div>
          </div>

        </main>
      </div>
    </div>

    <!-- TRANSACTION DETAIL MODAL -->
    <transition name="modal-fade">
      <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
        <div class="detail-modal">
          <div class="detail-modal-header">
            <div class="detail-modal-icon"><i class="fa-solid fa-receipt"></i></div>
            <div>
              <p class="detail-modal-eyebrow">Record</p>
              <h3 class="detail-modal-title">Transaction Details</h3>
            </div>
            <button class="modal-close-btn" @click="closeModal"><i class="fa-solid fa-xmark"></i></button>
          </div>

          <div class="detail-modal-body">
            <div class="detail-row"><span class="detail-label">Date</span><span class="detail-val">{{ modalData?.date }}</span></div>
            <div class="detail-row">
              <span class="detail-label">Type</span>
              <span class="detail-val"><span class="type-badge" :class="typeBadgeClass(modalData?.type)">{{ modalData?.type }}</span></span>
            </div>
            <div class="detail-row"><span class="detail-label">Quantity</span><span class="detail-val">{{ modalData?.quantity }}</span></div>
            <div class="detail-row"><span class="detail-label">Unit Price</span><span class="detail-val">₱{{ modalData?.unit_price }}</span></div>
            <div class="detail-divider"></div>
            <div class="detail-row detail-row--total"><span class="detail-label">Total</span><span class="detail-val">₱{{ modalData?.total }}</span></div>
            <div class="detail-row"><span class="detail-label">Reference</span><span class="detail-val detail-val--mono">{{ modalData?.reference }}</span></div>
          </div>

          <div class="detail-modal-footer">
            <button class="btn btn--ghost" style="flex:1; justify-content:center" @click="closeModal">Close</button>
          </div>
        </div>
      </div>
    </transition>
  </Layout>
</template>

<style scoped>
/* ── TOKEN BRIDGE ── */
.pt-root {
  --t-bg:             var(--c-bg);
  --t-surface:        var(--c-surface);
  --t-surface-raised: var(--c-surface-raised);
  --t-surface-sunken: var(--c-surface-sunken);
  --t-border:         var(--c-border);
  --t-border-strong:  var(--c-border-strong);
  --t-text-primary:   var(--c-text-primary);
  --t-text-secondary: var(--c-text-secondary);
  --t-text-muted:     var(--c-text-muted);
  --t-text-faint:     var(--c-text-faint);
  --t-accent:         var(--c-accent);
  --t-accent-soft:    var(--c-accent-soft);
  --t-accent-border:  var(--c-accent-border);
  --t-ring:           var(--c-accent-ring);
  --t-green:          #10b981;
  --t-red:            #f43f5e;
  --t-blue:           #3b82f6;
  --t-amber:          #f59e0b;

  display: flex;
  height: calc(100vh - 65px);
  background: var(--t-bg);
  font-family: 'Inter', system-ui, sans-serif;
  overflow: hidden;
  transition: background-color .22s;
}

/* SIDEBAR */
.pt-sidebar {
  width: 280px; min-width: 280px;
  background: var(--t-surface); border-right: 1px solid var(--t-border);
  padding: 20px; display: flex; flex-direction: column; gap: 16px; overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.sidebar-header { display: flex; align-items: center; justify-content: space-between; }
.sidebar-title  { font-size: 15px; font-weight: 700; color: var(--t-text-primary); margin: 0; transition: color .22s; }
.sidebar-add-btn {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 600; color: #fff;
  background: linear-gradient(135deg, var(--t-accent), var(--c-accent-deep));
  border-radius: 8px; padding: 6px 11px; text-decoration: none;
  box-shadow: var(--c-shadow-accent); transition: filter .15s, transform .15s;
}
.sidebar-add-btn:hover { filter: brightness(1.08); transform: translateY(-1px); }

.sidebar-search { position: relative; display: flex; align-items: center; flex-shrink: 0; }
.search-icon { position: absolute; left: 11px; color: var(--t-text-faint); font-size: 12px; pointer-events: none; }
.search-input {
  width: 100%; height: 38px;
  border: 1.5px solid var(--t-border-strong); border-radius: 9px;
  padding: 0 12px 0 32px; font-size: 12.5px; font-family: inherit;
  background: var(--t-surface-sunken); color: var(--t-text-primary); outline: none;
  transition: border-color .18s, background-color .22s, color .22s;
}
.search-input:focus { border-color: var(--t-accent); box-shadow: 0 0 0 3px var(--t-ring); background: var(--t-surface); }
.search-input::placeholder { color: var(--t-text-faint); }

.sidebar-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; scrollbar-width: thin; scrollbar-color: var(--t-border) transparent; }
.sidebar-list::-webkit-scrollbar { width: 4px; }
.sidebar-list::-webkit-scrollbar-thumb { background: var(--t-border); border-radius: 4px; }

.sidebar-item {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;
  padding: 10px 12px; border-radius: 10px; cursor: pointer;
  transition: background-color .15s;
}
.sidebar-item:hover { background: var(--t-surface-raised); }
.sidebar-item--active { background: var(--t-accent-soft); border: 1px solid var(--t-accent-border); }
.sidebar-item-main  { min-width: 0; }
.sidebar-item-name  { font-size: 12.5px; font-weight: 600; color: var(--t-text-primary); margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color .22s; }
.sidebar-item-sku   { font-size: 10.5px; color: var(--t-text-faint); }
.sidebar-item-stock { font-size: 10.5px; color: var(--t-text-muted); margin-top: 2px; }
.sidebar-item-price { font-size: 12.5px; font-weight: 700; color: var(--t-accent); flex-shrink: 0; }

/* MAIN PANEL */
.pt-main { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }

.pt-header {
  height: 64px; flex-shrink: 0;
  background: var(--t-surface); border-bottom: 1px solid var(--t-border);
  display: flex; align-items: center; justify-content: space-between; padding: 0 24px;
  transition: background-color .22s, border-color .22s;
}
.pt-title    { font-size: 15.5px; font-weight: 700; color: var(--t-text-primary); margin: 0; transition: color .22s; }
.pt-subtitle { font-size: 11.5px; color: var(--t-text-faint); margin: 2px 0 0; }
.pt-header-actions { display: flex; gap: 10px; }

.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 12.5px; font-weight: 600; font-family: inherit;
  border-radius: 9px; padding: 8px 16px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn--ghost { background: var(--t-surface); border-color: var(--t-border-strong); color: var(--t-text-secondary); }
.btn--ghost:hover { background: var(--t-surface-raised); border-color: var(--t-accent-border); color: var(--t-text-primary); }
.btn--primary { background: linear-gradient(135deg, var(--t-accent), var(--c-accent-deep)); color: #fff; box-shadow: var(--c-shadow-accent); }
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }

.pt-body { flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 20px; }

.search-wrap { position: relative; display: flex; align-items: center; }
.search-input--wide { padding-left: 36px; height: 42px; }

/* SUMMARY */
.summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.summary-card {
  display: flex; align-items: center; gap: 12px;
  background: var(--t-surface); border: 1px solid var(--t-border);
  border-radius: 14px; padding: 16px;
  transition: background-color .22s, border-color .22s;
}
.summary-icon {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.summary-icon--green { background: rgba(16,185,129,.12); color: var(--t-green); }
.summary-icon--red   { background: rgba(244,63,94,.12);  color: var(--t-red);   }
.summary-icon--blue  { background: rgba(59,130,246,.12); color: var(--t-blue);  }
.summary-icon--amber { background: rgba(245,158,11,.12); color: var(--t-amber); }
html[data-theme="dark"] .summary-icon--green { background: rgba(16,185,129,.18); }
html[data-theme="dark"] .summary-icon--red   { background: rgba(244,63,94,.18);  }
html[data-theme="dark"] .summary-icon--blue  { background: rgba(59,130,246,.18); }
html[data-theme="dark"] .summary-icon--amber { background: rgba(245,158,11,.18); }
.summary-label { font-size: 11px; font-weight: 600; color: var(--t-text-faint); text-transform: uppercase; letter-spacing: .06em; margin: 0 0 3px; }
.summary-val   { font-size: 18px; font-weight: 800; color: var(--t-text-primary); letter-spacing: -.02em; margin: 0; transition: color .22s; }

/* TABS */
.tabs-row { display: flex; gap: 4px; border-bottom: 1px solid var(--t-border); transition: border-color .22s; }
.tab-btn {
  background: none; border: none; cursor: pointer;
  font-size: 13px; font-weight: 600; color: var(--t-text-muted);
  padding: 10px 4px; margin-right: 20px;
  border-bottom: 2px solid transparent; font-family: inherit;
  transition: color .15s, border-color .15s;
}
.tab-btn:hover { color: var(--t-text-primary); }
.tab-btn--active { color: var(--t-accent); border-bottom-color: var(--t-accent); }

.tab-panel { display: flex; flex-direction: column; gap: 14px; }

/* SORT SELECT */
.select-wrap { position: relative; display: inline-flex; align-items: center; align-self: flex-start; }
.sort-select {
  height: 38px; padding: 0 32px 0 12px;
  border: 1.5px solid var(--t-border-strong); border-radius: 9px;
  background: var(--t-surface); color: var(--t-text-secondary);
  font-size: 12.5px; font-weight: 600; font-family: inherit;
  outline: none; appearance: none; -webkit-appearance: none; cursor: pointer;
  transition: border-color .18s, background-color .22s, color .22s;
}
.sort-select:focus { border-color: var(--t-accent); box-shadow: 0 0 0 3px var(--t-ring); }
.select-caret { position: absolute; right: 10px; font-size: 9px; color: var(--t-text-faint); pointer-events: none; }

/* TABLE */
.table-card { background: var(--t-surface); border: 1px solid var(--t-border); border-radius: 14px; overflow: hidden; transition: background-color .22s, border-color .22s; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.data-table thead th {
  background: var(--t-surface-raised); color: var(--t-text-faint);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  text-align: left; padding: 11px 16px; border-bottom: 1px solid var(--t-border);
  white-space: nowrap; transition: background-color .22s, color .22s, border-color .22s;
}
.data-table tbody td { padding: 12px 16px; border-bottom: 1px solid var(--t-border); transition: background-color .15s, border-color .22s; }
.clickable-row { cursor: pointer; transition: background-color .15s; }
.clickable-row:hover td { background: var(--t-surface-raised); }
.data-table tbody tr:last-child td { border-bottom: none; }

.cell-muted { color: var(--t-text-muted); }
.cell-faint { color: var(--t-text-faint); font-size: 12.5px; }
.cell-mono  { font-variant-numeric: tabular-nums; color: var(--t-text-secondary); }
.cell-total { font-weight: 700; color: var(--t-text-primary); font-variant-numeric: tabular-nums; transition: color .22s; }

.type-badge { display: inline-flex; font-size: 11px; font-weight: 700; border-radius: 20px; padding: 3px 10px; text-transform: capitalize; }
.type-badge--sale     { background: rgba(16,185,129,.12); color: var(--t-green); }
.type-badge--return   { background: rgba(244,63,94,.12);  color: var(--t-red);   }
.type-badge--neutral  { background: rgba(59,130,246,.12); color: var(--t-blue);  }
html[data-theme="dark"] .type-badge--sale    { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .type-badge--return  { background: rgba(244,63,94,.18);  color: #F87171; }
html[data-theme="dark"] .type-badge--neutral { background: rgba(59,130,246,.18); color: #93C5FD; }

.td-empty { padding: 48px 24px !important; text-align: center; border-bottom: none !important; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 10px; color: var(--t-text-faint); }
.empty-state--inline { padding: 40px 0; }
.empty-state i { font-size: 30px; }
.empty-state p { font-size: 13px; font-weight: 600; color: var(--t-text-muted); margin: 0; }

/* HISTORY */
.history-list { gap: 10px; }
.history-item { display: flex; align-items: flex-start; gap: 12px; background: var(--t-surface); border: 1px solid var(--t-border); border-radius: 12px; padding: 12px 16px; transition: background-color .22s, border-color .22s; }
.history-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--t-accent); margin-top: 5px; flex-shrink: 0; }
.history-desc { font-size: 13px; font-weight: 600; color: var(--t-text-primary); margin: 0 0 2px; transition: color .22s; }
.history-date { font-size: 11.5px; color: var(--t-text-faint); margin: 0; }

/* MODAL */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(0,0,0,.48); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.95) translateY(10px); }

.detail-modal {
  width: 100%; max-width: 400px;
  background: var(--t-surface); border: 1px solid var(--t-border); border-radius: 20px;
  box-shadow: var(--c-shadow-xl); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.detail-modal-header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px 14px; border-bottom: 1px solid var(--t-border);
  transition: border-color .22s;
}
.detail-modal-icon {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  background: var(--t-accent-soft); color: var(--t-accent);
  display: flex; align-items: center; justify-content: center; font-size: 15px;
  border: 1px solid var(--t-accent-border);
}
.detail-modal-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: .12em; color: var(--t-accent); text-transform: uppercase; margin: 0 0 2px; }
.detail-modal-title   { font-size: 15px; font-weight: 700; color: var(--t-text-primary); margin: 0; transition: color .22s; }
.modal-close-btn {
  margin-left: auto; width: 28px; height: 28px; border: none; border-radius: 7px;
  background: var(--t-surface-raised); color: var(--t-text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 11px;
  transition: background .15s, color .15s;
}
.modal-close-btn:hover { background: var(--t-border-strong); color: var(--t-text-primary); }

.detail-modal-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 10px; }
.detail-row  { display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
.detail-label{ color: var(--t-text-faint); font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: .05em; }
.detail-val  { color: var(--t-text-primary); font-weight: 600; transition: color .22s; }
.detail-val--mono { font-variant-numeric: tabular-nums; font-size: 12px; }
.detail-divider { height: 1px; background: var(--t-border); margin: 4px 0; transition: background .22s; }
.detail-row--total .detail-val { color: var(--t-accent); font-size: 15px; font-weight: 800; }

.detail-modal-footer { display: flex; padding: 0 20px 20px; }

@media (max-width: 900px) {
  .pt-root    { flex-direction: column; height: auto; }
  .pt-sidebar { width: 100%; min-width: 0; height: auto; max-height: 280px; }
  .summary-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>