<template>
  <Layout title="Customers">
    <div class="cust-page">

      <!-- PAGE HEADER -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Sales Intelligence</p>
          <h1 class="page-title">Customers</h1>
        </div>

        <div class="header-tools">
          <div class="seg">
            <button type="button" class="seg-btn" :class="{ 'seg-btn--active': view === 'all' }" @click="view = 'all'">All</button>
            <button type="button" class="seg-btn" :class="{ 'seg-btn--active': view === 'repeat' }" @click="view = 'repeat'">Repeat buyers</button>
            <button type="button" class="seg-btn" :class="{ 'seg-btn--active': view === 'tracking' }" @click="view = 'tracking'">Still tracking</button>
          </div>

          <div class="search-wrap">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input
              v-model="search"
              type="text"
              class="search-input"
              placeholder="Search name, phone or code"
            />
          </div>

          <button
            type="button"
            class="btn-primary"
            title="Only for buyers already on your client's repeat buyer list"
            @click="openCreate"
          >
            <i class="fa-solid fa-plus"></i> Add repeat buyer
          </button>
        </div>
      </div>

      <!-- KPI STRIP -->
      <div class="kpi-strip">
        <div class="kpi-card kpi-card--featured">
          <p class="kpi-label">Customers tracked</p>
          <p class="kpi-value">{{ kpis.total }}</p>
        </div>
        <div class="kpi-card">
          <p class="kpi-label">Repeat buyers</p>
          <p class="kpi-value kpi-value--accent">{{ kpis.repeat }}</p>
        </div>
        <div class="kpi-card">
          <p class="kpi-label">Still tracking</p>
          <p class="kpi-value">{{ kpis.tracking }}</p>
        </div>
        <div class="kpi-card">
          <p class="kpi-label">Spent by repeat buyers</p>
          <p class="kpi-value kpi-value--green">{{ peso(kpis.repeatSpent) }}</p>
        </div>
      </div>

      <!-- TABLE -->
      <div class="card">
        <div class="card-header">
          <div>
            <p class="card-eyebrow">Directory</p>
            <h2 class="card-title">
              {{ viewTitle }}
              <span class="count-chip">{{ visibleRows.length }}</span>
            </h2>
          </div>
          <p class="card-note">Customers qualify as repeat buyers after {{ minVisits }} purchases, or when added from your client's list.</p>
        </div>

        <p v-if="loadError" class="state state--error">{{ loadError }}</p>
        <p v-else-if="loading" class="state"><i class="fa-solid fa-spinner fa-spin"></i> Loading customers…</p>
        <p v-else-if="!visibleRows.length" class="state">
          <template v-if="!customers.length">
            No customers yet. Every named customer at the POS is tracked here automatically.
            Use <strong>Add repeat buyer</strong> for buyers already on your client's repeat buyer list.
          </template>
          <template v-else-if="view === 'repeat'">No repeat buyers yet.</template>
          <template v-else-if="view === 'tracking'">Nobody is being tracked right now.</template>
          <template v-else>No customers match your search.</template>
        </p>

        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th @click="setSort('CustomerName')">Customer <i :class="sortIcon('CustomerName')"></i></th>
                <th @click="setSort('visits')">Purchases <i :class="sortIcon('visits')"></i></th>
                <th @click="setSort('total_spent')">Total spent <i :class="sortIcon('total_spent')"></i></th>
                <th @click="setSort('last_purchase')">Last purchase <i :class="sortIcon('last_purchase')"></i></th>
                <th class="th-actions">Edit</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in visibleRows" :key="c.CustomerID" class="row-click" @click="openHistory(c)">
                <td>
                  <div class="cell-name">
                    {{ c.CustomerName || 'Unnamed' }}
                    <span v-if="c.is_repeat" class="pill pill--repeat">Repeat buyer</span>
                  </div>
                  <div class="cell-sub">{{ c.ContactNumber || 'no phone' }} · {{ c.CustomerCode }}</div>
                </td>
                <td>
                  <div class="cell-strong">{{ c.visits }}</div>
                  <div v-if="c.is_repeat" class="cell-sub">
                    {{ c.qualified_by === 'list' ? "From client's list" : 'Reached ' + minVisits + ' purchases' }}
                  </div>
                  <template v-else>
                    <div class="progress"><span :style="{ width: progress(c) + '%' }"></span></div>
                    <div class="cell-sub">{{ c.visits }} of {{ minVisits }} · {{ remaining(c) }} more to qualify</div>
                  </template>
                </td>
                <td class="cell-strong">{{ peso(c.total_spent) }}</td>
                <td class="cell-faint">{{ c.last_purchase ? formatDate(c.last_purchase) : 'No purchases yet' }}</td>
                <td class="td-actions">
                  <button type="button" class="icon-btn" title="Edit customer" @click.stop="openEdit(c)">
                    <i class="fa-solid fa-pen"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ADD / EDIT MODAL -->
      <div v-if="form.open" class="modal-backdrop" @click.self="closeForm">
        <div class="modal modal--form" role="dialog" aria-modal="true">
          <div class="modal-head">
            <div>
              <p class="card-eyebrow">{{ form.mode === 'create' ? 'Repeat buyer' : 'Edit customer' }}</p>
              <h3 class="modal-title">{{ form.mode === 'create' ? 'Add a repeat buyer' : 'Customer details' }}</h3>
            </div>
            <button type="button" class="modal-close" @click="closeForm" aria-label="Close">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="form-body">
            <p v-if="form.mode === 'create'" class="form-note">
              <strong>This is only for adding repeat buyers</strong>: buyers who are already on your client's
              own repeat buyer list. They qualify right away, without needing {{ minVisits }} purchases.<br />
              Other customers are tracked automatically when their name is entered at the POS, and qualify
              after {{ minVisits }} purchases. If the name is already being tracked, that customer is
              upgraded instead of duplicated.
            </p>

            <label class="field">
              <span class="field-label">Customer name <em>required</em></span>
              <input
                ref="nameInput"
                v-model="form.name"
                type="text"
                class="field-input"
                placeholder="e.g. Juan Dela Cruz or BuildRight Construction"
                @keydown.enter="saveCustomer"
              />
            </label>

            <label class="field">
              <span class="field-label">Phone number <em>optional</em></span>
              <input
                v-model="form.phone"
                type="tel"
                inputmode="numeric"
                class="field-input"
                placeholder="09171234567"
                @keydown.enter="saveCustomer"
              />
              <span class="field-hint">Each number can belong to one customer.</span>
            </label>

            <p v-if="form.error" class="form-error"><i class="fa-solid fa-circle-exclamation"></i> {{ form.error }}</p>
          </div>

          <div class="form-actions">
            <template v-if="form.confirmDelete">
              <p class="confirm-text">Delete this customer? Their past sales stay recorded, but are no longer linked to them.</p>
              <button type="button" class="btn-ghost" :disabled="form.saving" @click="form.confirmDelete = false">Keep</button>
              <button type="button" class="btn-danger" :disabled="form.saving" @click="deleteCustomer">
                <i v-if="form.saving" class="fa-solid fa-spinner fa-spin"></i> Delete
              </button>
            </template>
            <template v-else>
              <button
                v-if="form.mode === 'edit'"
                type="button"
                class="btn-link-danger"
                :disabled="form.saving"
                @click="form.confirmDelete = true"
              >Delete</button>
              <span class="spacer"></span>
              <button type="button" class="btn-ghost" :disabled="form.saving" @click="closeForm">Cancel</button>
              <button type="button" class="btn-primary" :disabled="form.saving" @click="saveCustomer">
                <i v-if="form.saving" class="fa-solid fa-spinner fa-spin"></i>
                {{ form.mode === 'create' ? 'Add repeat buyer' : 'Save changes' }}
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- PURCHASE HISTORY MODAL -->
      <div v-if="detail.open" class="modal-backdrop" @click.self="closeDetail">
        <div class="modal" role="dialog" aria-modal="true">
          <div class="modal-head">
            <div>
              <p class="card-eyebrow">Purchase history</p>
              <h3 class="modal-title">{{ detail.customer?.CustomerName || detail.name }}</h3>
              <p class="cell-sub">{{ detail.customer?.ContactNumber || 'no phone' }} · {{ detail.customer?.CustomerCode }}</p>
            </div>
            <button type="button" class="modal-close" @click="closeDetail" aria-label="Close">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <p v-if="detail.loading" class="state"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</p>
          <p v-else-if="detail.error" class="state state--error">{{ detail.error }}</p>

          <div v-else class="modal-body">
            <div v-for="s in detail.sales" :key="s.SaleID" class="sale">
              <div class="sale-top">
                <div>
                  <p class="sale-id">Sale #{{ s.SaleID }}</p>
                  <p class="cell-sub">{{ formatDate(s.SaleDate) }}<span v-if="s.PaymentMethod"> · {{ s.PaymentMethod }}</span></p>
                </div>
                <p class="sale-total">{{ peso(s.TotalAmount) }}</p>
              </div>
              <ul class="sale-items">
                <li v-for="(it, i) in s.items" :key="i">
                  <span>{{ it.ProductName }}</span>
                  <span class="cell-sub">{{ it.Quantity }}{{ it.Unit ? ' ' + it.Unit : '' }} × {{ peso(it.PriceAtSale) }}</span>
                </li>
              </ul>
            </div>
            <p v-if="!detail.sales.length" class="state">No purchases recorded yet.</p>
          </div>
        </div>
      </div>

    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const customers = ref([])
const minVisits = ref(10) // overwritten by the API (REPEAT_MIN_PURCHASES in CustomerController)
const loading   = ref(false)
const loadError = ref('')

const view   = ref('all') // 'all' | 'repeat' | 'tracking'
const search = ref('')

const sortKey = ref('visits')
const sortDir = ref('desc')

const nameInput = ref(null)
const form = ref({
  open: false, mode: 'create', id: null,
  name: '', phone: '', saving: false, error: '', confirmDelete: false,
})
const detail = ref({ open: false, loading: false, error: '', name: '', customer: null, sales: [] })

function num(v) {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}
function peso(v) {
  return '₱' + num(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
// Backend sends naive local datetime strings (e.g. "2026-09-17 18:19:22").
// Parse the pieces directly so the browser does not shift the timezone.
function formatDate(raw) {
  if (!raw) return ''
  const m = String(raw).match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/)
  if (!m) return raw
  const [, y, mo, d, h, mi, s] = m.map(Number)
  return new Date(y, mo - 1, d, h, mi, s).toLocaleString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: 'numeric', minute: '2-digit', hour12: true,
  })
}
function remaining(c) { return Math.max(0, minVisits.value - num(c.visits)) }
function progress(c) { return Math.min(100, Math.round((num(c.visits) / minVisits.value) * 100)) }

async function loadCustomers() {
  loading.value = true
  loadError.value = ''
  try {
    const { data } = await api.get('/api/customers/list')
    customers.value = data.customers || []
    minVisits.value = data.min_visits || 10
  } catch (e) {
    loadError.value = 'Could not load customers. Please try again.'
    console.error('customers load error:', e)
  } finally {
    loading.value = false
  }
}

const kpis = computed(() => {
  const repeatRows = customers.value.filter((c) => c.is_repeat)
  return {
    total: customers.value.length,
    repeat: repeatRows.length,
    tracking: customers.value.length - repeatRows.length,
    repeatSpent: repeatRows.reduce((sum, c) => sum + num(c.total_spent), 0),
  }
})

const viewTitle = computed(() => ({
  all: 'All customers',
  repeat: 'Repeat buyers',
  tracking: 'Still tracking',
}[view.value]))

const visibleRows = computed(() => {
  const q = search.value.trim().toLowerCase()
  const rows = customers.value.filter((c) => {
    if (view.value === 'repeat' && !c.is_repeat) return false
    if (view.value === 'tracking' && c.is_repeat) return false
    if (!q) return true
    return (
      String(c.CustomerName || '').toLowerCase().includes(q) ||
      String(c.ContactNumber || '').toLowerCase().includes(q) ||
      String(c.CustomerCode || '').toLowerCase().includes(q)
    )
  })

  const key = sortKey.value
  const dir = sortDir.value === 'asc' ? 1 : -1
  const numeric = key === 'visits' || key === 'total_spent'
  return rows.slice().sort((a, b) => {
    if (numeric) return (num(a[key]) - num(b[key])) * dir
    return String(a[key] || '').localeCompare(String(b[key] || '')) * dir
  })
})

function setSort(key) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = key === 'CustomerName' ? 'asc' : 'desc'
  }
}
function sortIcon(key) {
  if (sortKey.value !== key) return 'fa-solid fa-sort sort-icon'
  return sortDir.value === 'asc' ? 'fa-solid fa-sort-up sort-icon sort-icon--on' : 'fa-solid fa-sort-down sort-icon sort-icon--on'
}

/* ── add repeat buyer / edit / delete ── */
function openCreate() {
  form.value = { open: true, mode: 'create', id: null, name: '', phone: '', saving: false, error: '', confirmDelete: false }
  nextTick(() => nameInput.value?.focus())
}
function openEdit(c) {
  form.value = {
    open: true, mode: 'edit', id: c.CustomerID,
    name: c.CustomerName || '', phone: c.ContactNumber || '',
    saving: false, error: '', confirmDelete: false,
  }
  nextTick(() => nameInput.value?.focus())
}
function closeForm() {
  if (form.value.saving) return
  form.value.open = false
}
function firstError(e, fallback) {
  const errs = e?.response?.data?.errors
  if (errs) {
    const first = Object.values(errs)[0]
    if (Array.isArray(first) && first[0]) return first[0]
  }
  return e?.response?.data?.message || fallback
}
async function saveCustomer() {
  if (form.value.saving) return
  const name = form.value.name.trim()
  if (!name) { form.value.error = 'Customer name is required.'; return }

  form.value.saving = true
  form.value.error = ''
  const payload = { CustomerName: name, ContactNumber: form.value.phone.trim() || null }
  try {
    if (form.value.mode === 'create') await api.post('/api/customers', payload)
    else await api.put(`/api/customers/${form.value.id}`, payload)
    form.value.saving = false
    form.value.open = false
    await loadCustomers()
  } catch (e) {
    form.value.error = firstError(e, 'Could not save this customer. Please try again.')
    console.error('customer save error:', e)
  } finally {
    form.value.saving = false
  }
}
async function deleteCustomer() {
  if (form.value.saving) return
  form.value.saving = true
  form.value.error = ''
  try {
    await api.delete(`/api/customers/${form.value.id}`)
    form.value.saving = false
    form.value.open = false
    await loadCustomers()
  } catch (e) {
    form.value.error = firstError(e, 'Could not delete this customer.')
    form.value.confirmDelete = false
    console.error('customer delete error:', e)
  } finally {
    form.value.saving = false
  }
}

/* ── purchase history ── */
async function openHistory(c) {
  detail.value = { open: true, loading: true, error: '', name: c.CustomerName, customer: null, sales: [] }
  try {
    const { data } = await api.get(`/api/customers/${c.CustomerID}`)
    detail.value.customer = data.customer
    detail.value.sales = data.sales || []
  } catch (e) {
    detail.value.error = 'Could not load this customer\'s purchases.'
    console.error('customer detail error:', e)
  } finally {
    detail.value.loading = false
  }
}
function closeDetail() { detail.value.open = false }

function onEsc(e) {
  if (e.key !== 'Escape') return
  if (form.value.open) closeForm()
  else if (detail.value.open) closeDetail()
}

onMounted(() => {
  window.addEventListener('keydown', onEsc)
  loadCustomers()
})
onBeforeUnmount(() => window.removeEventListener('keydown', onEsc))
</script>

<style scoped>
.cust-page {
  --il-surface:        var(--c-surface, #1e293b);
  --il-surface-raised: var(--c-surface-raised, #26364b);
  --il-surface-sunken: var(--c-surface-sunken, #0b1524);
  --il-border:         var(--c-border, #334155);
  --il-border-strong:  var(--c-border-strong, #47566b);
  --il-text-primary:   var(--c-text-primary, #f1f5f9);
  --il-text-secondary: var(--c-text-secondary, #cbd5e1);
  --il-text-muted:     var(--c-text-muted, #94a3b8);
  --il-text-faint:     var(--c-text-faint, #64748b);
  --il-accent:         var(--c-accent, #f97316);
  --il-accent-soft:    var(--c-accent-soft, rgba(249,115,22,.15));
  --il-accent-border:  var(--c-accent-border, rgba(249,115,22,.35));
  --il-green:          #10b981;
  --il-red:            #f43f5e;
  --radius: 16px;

  min-height: 100%;
  background: var(--c-bg, #0f172a);
  padding: 28px 28px 56px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 24px;
}

/* Header */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--il-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title { font-size: 26px; font-weight: 800; color: var(--il-text-primary); letter-spacing: -.03em; margin: 0; }
.header-tools { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.seg {
  display: inline-flex; gap: 2px; padding: 3px;
  background: var(--il-surface-sunken); border: 1px solid var(--il-border); border-radius: 12px;
}
.seg-btn {
  border: none; background: transparent; cursor: pointer;
  font-family: inherit; font-size: 12.5px; font-weight: 600;
  color: var(--il-text-muted); padding: 8px 14px; border-radius: 9px; white-space: nowrap;
  transition: background-color .18s, color .18s;
}
.seg-btn:hover { color: var(--il-text-secondary); }
.seg-btn--active { background: var(--il-accent); color: #fff; }
.seg-btn--active:hover { color: #fff; }

.search-wrap { position: relative; display: flex; align-items: center; }
.search-icon { position: absolute; left: 12px; font-size: 12px; color: var(--il-text-faint); pointer-events: none; }
.search-input {
  width: 230px; max-width: 100%; height: 40px;
  border: 1.5px solid var(--il-border-strong); border-radius: 11px;
  padding: 0 12px 0 34px; font-family: inherit; font-size: 13px;
  color: var(--il-text-primary); background: var(--il-surface); outline: none;
  transition: border-color .15s;
}
.search-input:focus { border-color: var(--il-accent); }
.search-input::placeholder { color: var(--il-text-faint); }

/* Buttons */
.btn-primary {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  height: 40px; padding: 0 18px; border: none; border-radius: 11px; cursor: pointer;
  background: linear-gradient(135deg, var(--il-accent) 0%, var(--c-accent-deep, #c2410c) 100%);
  color: #fff; font-family: inherit; font-size: 13px; font-weight: 700;
  box-shadow: var(--c-shadow-accent, 0 4px 14px rgba(249,115,22,.35));
  transition: transform .15s, filter .15s;
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.06); }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }
.btn-ghost {
  height: 40px; padding: 0 18px; border-radius: 11px; cursor: pointer;
  border: 1.5px solid var(--il-border-strong); background: transparent;
  color: var(--il-text-secondary); font-family: inherit; font-size: 13px; font-weight: 600;
}
.btn-ghost:hover:not(:disabled) { background: var(--il-surface-raised); color: var(--il-text-primary); }
.btn-ghost:disabled { opacity: .6; cursor: not-allowed; }
.btn-danger {
  display: inline-flex; align-items: center; gap: 8px;
  height: 40px; padding: 0 18px; border: none; border-radius: 11px; cursor: pointer;
  background: var(--il-red); color: #fff; font-family: inherit; font-size: 13px; font-weight: 700;
}
.btn-danger:disabled { opacity: .6; cursor: not-allowed; }
.btn-link-danger {
  border: none; background: none; cursor: pointer; padding: 0 4px;
  color: var(--il-red); font-family: inherit; font-size: 13px; font-weight: 600;
}
.btn-link-danger:hover { text-decoration: underline; }

.icon-btn {
  width: 32px; height: 32px; border-radius: 9px; cursor: pointer;
  border: 1px solid var(--il-border-strong); background: var(--il-surface-sunken);
  color: var(--il-text-muted); font-size: 12px;
}
.icon-btn:hover { color: var(--il-accent); border-color: var(--il-accent-border); background: var(--il-accent-soft); }

/* KPIs */
.kpi-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.kpi-card {
  background: var(--il-surface); border: 1px solid var(--il-border);
  border-radius: var(--radius); padding: 18px 20px;
}
.kpi-card--featured { background: var(--il-accent-soft); border-color: var(--il-accent-border); }
.kpi-label { margin: 0 0 6px; font-size: 11px; font-weight: 600; color: var(--il-text-muted); text-transform: uppercase; letter-spacing: .07em; }
.kpi-value { margin: 0; font-size: 24px; font-weight: 800; color: var(--il-text-primary); letter-spacing: -.04em; }
.kpi-value--accent { color: var(--il-accent); }
.kpi-value--green { color: var(--il-green); }

/* Card + table */
.card {
  background: var(--il-surface); border: 1px solid var(--il-border);
  border-radius: var(--radius); padding: 22px 24px 20px;
}
.card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.card-eyebrow { font-size: 10.5px; font-weight: 700; letter-spacing: .12em; color: var(--il-accent); text-transform: uppercase; margin: 0 0 4px; }
.card-title { font-size: 16px; font-weight: 700; color: var(--il-text-primary); margin: 0; display: flex; align-items: center; gap: 8px; }
.card-note { margin: 0; font-size: 12px; color: var(--il-text-faint); align-self: center; max-width: 420px; line-height: 1.5; }
.count-chip {
  font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px;
  background: var(--il-accent-soft); color: var(--il-accent);
}

.state { margin: 8px 0; padding: 24px 0; text-align: center; font-size: 13.5px; line-height: 1.6; color: var(--il-text-muted); }
.state strong { color: var(--il-text-primary); }
.state--error { color: var(--il-red); }

.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.data-table th {
  text-align: left; padding: 12px 16px; white-space: nowrap; cursor: pointer; user-select: none;
  font-size: 11px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
  color: var(--il-text-muted); background: var(--il-surface-raised);
}
.data-table th:first-child { border-radius: 10px 0 0 10px; }
.data-table th:last-child { border-radius: 0 10px 10px 0; }
.th-actions { cursor: default; text-align: right; }
.data-table td { padding: 12px 16px; border-bottom: 1px solid var(--il-border); white-space: nowrap; color: var(--il-text-secondary); }
.data-table tbody tr:last-child td { border-bottom: none; }
.td-actions { text-align: right; }
.row-click { cursor: pointer; }
.row-click:hover td { background: var(--il-surface-raised); }

.sort-icon { margin-left: 4px; font-size: 9px; color: var(--il-text-faint); }
.sort-icon--on { color: var(--il-accent); }

.cell-name { font-weight: 600; color: var(--il-text-primary); display: flex; align-items: center; gap: 8px; }
.cell-sub { font-size: 11.5px; color: var(--il-text-faint); margin-top: 2px; }
.cell-strong { font-weight: 700; color: var(--il-text-primary); }
.cell-faint { color: var(--il-text-muted); font-size: 12.5px; }

.pill { font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }
.pill--repeat { background: var(--il-accent-soft); color: var(--il-accent); border: 1px solid var(--il-accent-border); }

/* Progress toward repeat buyer */
.progress { width: 120px; height: 5px; margin-top: 5px; border-radius: 4px; background: var(--il-surface-sunken); overflow: hidden; }
.progress span { display: block; height: 100%; border-radius: 4px; background: var(--il-accent); transition: width .3s ease; }

/* Modals */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 1000; padding: 20px;
  background: rgba(8,10,16,.6);
  display: flex; align-items: center; justify-content: center;
}
.modal {
  width: min(560px, 100%); max-height: min(86vh, 700px);
  display: flex; flex-direction: column; gap: 14px;
  background: var(--il-surface); border: 1px solid var(--il-border);
  border-radius: var(--radius); padding: 22px 24px 18px;
  box-shadow: 0 24px 60px rgba(0,0,0,.5);
}
.modal--form { width: min(480px, 100%); overflow-y: auto; }
.modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.modal-title { margin: 0; font-size: 17px; font-weight: 700; color: var(--il-text-primary); }
.modal-close {
  width: 32px; height: 32px; border-radius: 9px; cursor: pointer;
  border: 1px solid var(--il-border-strong); background: var(--il-surface-sunken); color: var(--il-text-secondary);
}
.modal-close:hover { color: var(--il-text-primary); border-color: var(--il-accent-border); }
.modal-body { overflow-y: auto; min-height: 0; display: flex; flex-direction: column; gap: 10px; }

/* Form */
.form-body { display: flex; flex-direction: column; gap: 16px; }
.form-note {
  margin: 0; padding: 12px 14px; border-radius: 10px;
  background: var(--il-accent-soft); border: 1px solid var(--il-accent-border);
  font-size: 12.5px; line-height: 1.6; color: var(--il-text-secondary);
}
.form-note strong { color: var(--il-text-primary); }
.field { display: flex; flex-direction: column; gap: 6px; }
.field-label { font-size: 12.5px; font-weight: 700; color: var(--il-text-secondary); }
.field-label em { font-style: normal; font-weight: 500; color: var(--il-text-faint); margin-left: 4px; font-size: 11.5px; }
.field-input {
  height: 42px; border: 1.5px solid var(--il-border-strong); border-radius: 10px;
  padding: 0 14px; font-family: inherit; font-size: 13.5px;
  color: var(--il-text-primary); background: var(--il-surface-sunken); outline: none;
  transition: border-color .15s, background-color .15s;
}
.field-input:focus { border-color: var(--il-accent); background: var(--il-surface); }
.field-input::placeholder { color: var(--il-text-faint); }
.field-hint { font-size: 11.5px; color: var(--il-text-faint); line-height: 1.5; }
.form-error { margin: 0; font-size: 12.5px; font-weight: 600; color: var(--il-red); display: flex; align-items: center; gap: 6px; }

.form-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding-top: 4px; }
.form-actions .spacer { flex: 1; }
.confirm-text { flex: 1 1 100%; margin: 0 0 4px; font-size: 12.5px; color: var(--il-text-secondary); line-height: 1.5; }

/* Purchase history */
.sale { background: var(--il-surface-raised); border: 1px solid var(--il-border); border-radius: 12px; padding: 12px 14px; }
.sale-top { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
.sale-id { margin: 0; font-size: 13px; font-weight: 700; color: var(--il-text-primary); }
.sale-total { margin: 0; font-size: 14px; font-weight: 800; color: var(--il-accent); }
.sale-items { list-style: none; margin: 10px 0 0; padding: 10px 0 0; border-top: 1px solid var(--il-border); display: flex; flex-direction: column; gap: 6px; }
.sale-items li { display: flex; justify-content: space-between; gap: 12px; font-size: 12.5px; color: var(--il-text-secondary); }

@media (max-width: 1100px) { .kpi-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 700px) {
  .cust-page { padding: 16px 16px 40px; }
  .kpi-strip { grid-template-columns: 1fr; }
  .header-tools { width: 100%; }
  .search-wrap, .search-input { width: 100%; }
  .btn-primary { flex: 1; }
}
</style>