<template>
  <Layout title="Customers">
    <div class="cust-page" ref="pageRef">

      <!-- ── PAGE HEADER ── -->
      <header ref="headerRef" class="page-header">
        <div class="page-heading">
          <h1 class="page-title">Customers</h1>
          <p class="page-sub">
            <span>{{ loading && !customers.length ? 'Loading…' : kpis.total + (kpis.total === 1 ? ' customer' : ' customers') + ' tracked' }}</span>
            <span v-if="kpis.repeat" class="chip chip--live">{{ kpis.repeat }} repeat {{ kpis.repeat === 1 ? 'buyer' : 'buyers' }}</span>
          </p>
        </div>

        <div class="report-toolbar">
          <button
            type="button"
            class="rt-btn rt-btn--primary"
            title="Only for buyers already on your client's repeat buyer list"
            @click="openCreate"
          >
            <i class="fa-solid fa-plus"></i> Add repeat buyer
          </button>
        </div>
      </header>

      <!-- ── STICKY BLOCK: cards + search stay pinned at the top while the table scrolls ── -->
      <div class="sticky-controls" :class="{ 'is-stuck': isStuck }" ref="stickyRef">

        <!-- KPI cards: each one is also the list filter. Click the active card again to go back to all. -->
        <section class="kpi-strip" aria-label="Customer summary. Select a card to filter the list.">
          <button
            type="button"
            class="kpi-card kpi-card--featured"
            :class="{ 'kpi-card--on': activeKpi === 'total' }"
            :aria-pressed="activeKpi === 'total'"
            @click="pickCard('total')"
          >
            <span class="kpi-icon kpi-icon--orange"><i class="fa-solid fa-users"></i></span>
            <span class="kpi-body">
              <span class="kpi-label">Customers tracked</span>
              <span class="kpi-value">{{ kpis.total }}</span>
              <span class="kpi-hint">Show all <i class="fa-solid fa-arrow-down"></i></span>
            </span>
          </button>

          <button
            type="button"
            class="kpi-card"
            :class="{ 'kpi-card--on': activeKpi === 'repeat' }"
            :aria-pressed="activeKpi === 'repeat'"
            @click="pickCard('repeat')"
          >
            <span class="kpi-icon kpi-icon--violet"><i class="fa-solid fa-rotate"></i></span>
            <span class="kpi-body">
              <span class="kpi-label">Repeat buyers</span>
              <span class="kpi-value kpi-value--accent">{{ kpis.repeat }}</span>
              <span class="kpi-hint">Show repeat buyers <i class="fa-solid fa-arrow-down"></i></span>
            </span>
          </button>

          <button
            type="button"
            class="kpi-card"
            :class="{ 'kpi-card--on': activeKpi === 'tracking' }"
            :aria-pressed="activeKpi === 'tracking'"
            @click="pickCard('tracking')"
          >
            <span class="kpi-icon kpi-icon--blue"><i class="fa-solid fa-hourglass-half"></i></span>
            <span class="kpi-body">
              <span class="kpi-label">Still tracking</span>
              <span class="kpi-value">{{ kpis.tracking }}</span>
              <span class="kpi-hint">Closest to qualifying <i class="fa-solid fa-arrow-down"></i></span>
            </span>
          </button>

          <button
            type="button"
            class="kpi-card"
            :class="{ 'kpi-card--on': activeKpi === 'spent' }"
            :aria-pressed="activeKpi === 'spent'"
            @click="pickCard('spent')"
          >
            <span class="kpi-icon kpi-icon--green"><i class="fa-solid fa-peso-sign"></i></span>
            <span class="kpi-body">
              <span class="kpi-label">Spent by repeat buyers</span>
              <span class="kpi-value kpi-value--green">{{ peso(kpis.repeatSpent) }}</span>
              <span class="kpi-hint">Top spenders <i class="fa-solid fa-arrow-down"></i></span>
            </span>
          </button>
        </section>

        <!-- Toolbar: search (+ compact Add button once the page header scrolls away) -->
        <section class="toolbar" aria-label="Search customers">
          <div class="toolbar-row">
            <div class="search-wrap">
              <i class="fa-solid fa-magnifying-glass search-icon"></i>
              <input
                ref="searchInput"
                v-model="search"
                type="search"
                class="search-input"
                placeholder="Search name, phone or code"
                aria-label="Search customers"
                @keydown.esc.stop="search = ''"
              />
              <button v-if="search" type="button" class="search-clear" aria-label="Clear search" @click="search = ''; searchInput && searchInput.focus()">
                <i class="fa-solid fa-xmark"></i>
              </button>
              <kbd v-else class="search-kbd" aria-hidden="true">/</kbd>
            </div>

            <div class="toolbar-end">
              <!-- Shows only after the page header has scrolled out of view -->
              <button
                v-if="headerOut"
                type="button"
                class="rt-btn rt-btn--primary toolbar-add"
                title="Only for buyers already on your client's repeat buyer list"
                @click="openCreate"
              >
                <i class="fa-solid fa-plus"></i> Add repeat buyer
              </button>
            </div>
          </div>
        </section>
      </div>

      <!-- ── DIRECTORY ── -->
      <section
        ref="directoryEl"
        id="customer-directory"
        class="card directory"
        :class="{ 'directory--flash': flash }"
        tabindex="-1"
        aria-labelledby="dir-title"
      >
        <header class="card-head">
          <div>
            <h2 id="dir-title" class="card-title">
              {{ viewTitle }}
              <span class="count-chip">{{ visibleRows.length }}</span>
            </h2>
            <p class="card-sub">
              Customers qualify as repeat buyers after {{ minVisits }} purchases, or when added from your client's list.
            </p>
          </div>
        </header>

        <!-- States -->
        <div v-if="loadError" class="state state--error" role="alert">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <p>{{ loadError }}</p>
          <button type="button" class="link-btn" @click="loadCustomers">Try again</button>
        </div>

        <div v-else-if="loading && !customers.length" class="skeleton-list" aria-hidden="true">
          <span v-for="n in 5" :key="n" class="skeleton-row"></span>
        </div>

        <div v-else-if="!visibleRows.length" class="state">
          <i class="fa-solid" :class="customers.length ? 'fa-user-slash' : 'fa-user-plus'"></i>
          <template v-if="!customers.length">
            <p>No customers yet. Every named customer at the POS is tracked here automatically.</p>
            <p class="state-sub">Use <strong>Add repeat buyer</strong> for buyers already on your client's repeat buyer list.</p>
            <button type="button" class="link-btn" @click="openCreate">Add repeat buyer</button>
          </template>
          <template v-else>
            <p v-if="search">Nobody matches “{{ search }}”<span v-if="view !== 'all'"> in {{ viewTitle.toLowerCase() }}</span>.</p>
            <p v-else-if="view === 'repeat'">No repeat buyers yet.</p>
            <p v-else>Nobody is being tracked right now.</p>
            <button type="button" class="link-btn" @click="clearFilters">Show all customers</button>
          </template>
        </div>

        <template v-else>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th :aria-sort="ariaSort('CustomerName')">
                    <button type="button" class="th-btn" @click="setSort('CustomerName')">Customer <i :class="sortIcon('CustomerName')"></i></button>
                  </th>
                  <th :aria-sort="ariaSort('visits')">
                    <button type="button" class="th-btn" @click="setSort('visits')">Purchases <i :class="sortIcon('visits')"></i></button>
                  </th>
                  <th :aria-sort="ariaSort('total_spent')">
                    <button type="button" class="th-btn" @click="setSort('total_spent')">Total spent <i :class="sortIcon('total_spent')"></i></button>
                  </th>
                  <th :aria-sort="ariaSort('last_purchase')">
                    <button type="button" class="th-btn" @click="setSort('last_purchase')">Last purchase <i :class="sortIcon('last_purchase')"></i></button>
                  </th>
                  <th class="th-actions"><span class="sr-only">Edit</span></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="c in displayRows"
                  :key="c.CustomerID"
                  class="row-click"
                  tabindex="0"
                  :aria-label="`View purchases for ${c.CustomerName || 'Unnamed'}`"
                  @click="openHistory(c)"
                  @keydown.enter.self="openHistory(c)"
                  @keydown.space.self.prevent="openHistory(c)"
                >
                  <td>
                    <div class="cell-person">
                      <span class="avatar" :class="{ 'avatar--repeat': c.is_repeat }" aria-hidden="true">{{ initials(c.CustomerName) }}</span>
                      <div>
                        <div class="cell-name">
                          {{ c.CustomerName || 'Unnamed' }}
                          <span v-if="c.is_repeat" class="pill pill--repeat">Repeat buyer</span>
                        </div>
                        <div class="cell-sub">{{ c.ContactNumber || 'no phone' }} · {{ c.CustomerCode }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="cell-strong">{{ c.visits }}</div>
                    <div v-if="c.is_repeat" class="cell-sub">
                      {{ c.qualified_by === 'list' ? "From client's list" : 'Reached ' + minVisits + ' purchases' }}
                    </div>
                    <template v-else>
                      <div
                        class="progress"
                        role="progressbar"
                        :aria-valuenow="num(c.visits)"
                        aria-valuemin="0"
                        :aria-valuemax="minVisits"
                      ><span :style="{ width: progress(c) + '%' }"></span></div>
                      <div class="cell-sub">{{ c.visits }} of {{ minVisits }} · {{ remaining(c) }} more to qualify</div>
                    </template>
                  </td>
                  <td class="cell-strong">{{ peso(c.total_spent) }}</td>
                  <td>
                    <template v-if="c.last_purchase">
                      <div class="cell-faint">{{ formatDay(c.last_purchase) }}</div>
                      <div class="cell-sub">{{ ago(c.last_purchase) }}</div>
                    </template>
                    <span v-else class="cell-faint">No purchases yet</span>
                  </td>
                  <td class="td-actions">
                    <button type="button" class="icon-btn" title="Edit customer" aria-label="Edit customer" @click.stop="openEdit(c)" @keydown.enter.stop @keydown.space.stop>
                      <i class="fa-solid fa-pen"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="table-foot">
            <span>Showing {{ displayRows.length }} of {{ visibleRows.length }}</span>
            <button v-if="displayRows.length < visibleRows.length" type="button" class="link-btn" @click="limit += PAGE">
              Show {{ Math.min(PAGE, visibleRows.length - displayRows.length) }} more
            </button>
          </div>
        </template>
      </section>

      <!-- ── ADD / EDIT MODAL ── -->
      <div v-if="form.open" class="modal-backdrop" @click.self="closeForm">
        <div class="modal modal--form" role="dialog" aria-modal="true" :aria-label="form.mode === 'create' ? 'Add a repeat buyer' : 'Customer details'">
          <div class="modal-head">
            <div>
              <p class="modal-eyebrow">{{ form.mode === 'create' ? 'Repeat buyer' : 'Edit customer' }}</p>
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

            <p v-if="form.error" class="form-error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> {{ form.error }}</p>
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

      <!-- ── PURCHASE HISTORY MODAL ── -->
      <div v-if="detail.open" class="modal-backdrop" @click.self="closeDetail">
        <div class="modal" role="dialog" aria-modal="true" aria-label="Purchase history">
          <div class="modal-head">
            <div>
              <p class="modal-eyebrow">Purchase history</p>
              <h3 class="modal-title">{{ detail.customer?.CustomerName || detail.name }}</h3>
              <p class="cell-sub">{{ detail.customer?.ContactNumber || 'no phone' }} · {{ detail.customer?.CustomerCode }}</p>
            </div>
            <button type="button" class="modal-close" @click="closeDetail" aria-label="Close">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <p v-if="detail.loading" class="state state--inline"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</p>
          <p v-else-if="detail.error" class="state state--inline state--error">{{ detail.error }}</p>

          <template v-else>
            <div v-if="detail.sales.length" class="detail-stats">
              <div><span>Purchases</span><strong>{{ detail.sales.length }}</strong></div>
              <div><span>Total spent</span><strong>{{ peso(detailTotal) }}</strong></div>
              <div><span>Average sale</span><strong>{{ peso(detailTotal / detail.sales.length) }}</strong></div>
            </div>

            <div class="modal-body">
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
              <p v-if="!detail.sales.length" class="state state--inline">No purchases recorded yet.</p>
            </div>
          </template>
        </div>
      </div>

      <!-- ── TOAST ── -->
      <Transition name="toast">
        <div v-if="toast" class="toast" role="status"><i class="fa-solid fa-circle-check"></i> {{ toast }}</div>
      </Transition>

    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const customers = ref([])
const minVisits = ref(10) // overwritten by the API (REPEAT_MIN_PURCHASES in CustomerController)
const loading   = ref(false)
const loadError = ref('')

const view   = ref('all') // 'all' | 'repeat' | 'tracking'  (set by the summary cards)
const search = ref('')

const sortKey = ref('visits')
const sortDir = ref('desc')

const PAGE  = 25
const limit = ref(PAGE)

// page + sticky block refs
const pageRef      = ref(null)   // page root (holds the --sticky-top variable)
const headerRef    = ref(null)   // page header (watched to show the compact Add button)
const stickyRef    = ref(null)   // sticky block (cards + search)
const isStuck      = ref(false)  // true while the block is pinned (adds a soft shadow)
const headerOut    = ref(false)  // true once the page header has scrolled out of view
let   headerObserver = null

const directoryEl = ref(null)
const searchInput = ref(null)
const nameInput   = ref(null)
const flash       = ref(false)
const toast       = ref('')
let toastTimer = null
let flashTimer = null

const form = ref({
  open: false, mode: 'create', id: null,
  name: '', phone: '', saving: false, error: '', confirmDelete: false,
})
const detail = ref({ open: false, loading: false, error: '', name: '', customer: null, sales: [] })

/* ── helpers ── */
function num(v) {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}
function peso(v) {
  return '₱' + num(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Backend sends naive local datetime strings (e.g. "2026-09-17 18:19:22").
// Parse the pieces directly so the browser does not shift the timezone.
function parseLocal(raw) {
  const m = String(raw ?? '').match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/)
  if (!m) return null
  const [, y, mo, d, h, mi, s] = m.map(Number)
  return new Date(y, mo - 1, d, h, mi, s)
}
function formatDate(raw) {
  if (!raw) return ''
  const d = parseLocal(raw)
  if (!d) return raw
  return d.toLocaleString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: 'numeric', minute: '2-digit', hour12: true,
  })
}
function formatDay(raw) {
  const d = parseLocal(raw)
  return d ? d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : String(raw)
}
// "Today", "Yesterday", "5 days ago", "3 months ago"
function ago(raw) {
  const d = parseLocal(raw)
  if (!d) return ''
  const start = (x) => new Date(x.getFullYear(), x.getMonth(), x.getDate())
  const days = Math.round((start(new Date()) - start(d)) / 86400000)
  if (days <= 0) return 'Today'
  if (days === 1) return 'Yesterday'
  if (days < 30) return `${days} days ago`
  const months = Math.floor(days / 30)
  if (months < 12) return `${months} ${months === 1 ? 'month' : 'months'} ago`
  const years = Math.floor(days / 365)
  return `${years} ${years === 1 ? 'year' : 'years'} ago`
}
function initials(name) {
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '?'
  return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase()
}
function remaining(c) { return Math.max(0, minVisits.value - num(c.visits)) }
function progress(c) { return Math.min(100, Math.round((num(c.visits) / minVisits.value) * 100)) }

/* ── STICKY BLOCK ─────────────────────────────────────────────────
   The cards + search pin to the VERY top of the scrolling area.
   The app shell's scroll area has its own top padding, so we find the scrolling
   parent and pull --sticky-top up by exactly that padding. After the first real
   scroll we also measure any leftover gap and correct it (same logic as Product List). */
let scrollEl      = null   // the shell's scrolling element (null = the window scrolls)
let stickyTop     = 0
let calibrations  = 0
let scrollTicking = false

function findScrollParent(el) {
  let node = el?.parentElement
  while (node && node !== document.body && node !== document.documentElement) {
    if (/(auto|scroll|overlay)/.test(getComputedStyle(node).overflowY)) return node
    node = node.parentElement
  }
  return null
}
const scrollOffset = () => (scrollEl ? scrollEl.scrollTop : window.scrollY)
const viewportTop  = () => (scrollEl ? scrollEl.getBoundingClientRect().top + scrollEl.clientTop : 0)

function applyStickyTop(px) {
  stickyTop = px
  pageRef.value?.style.setProperty('--sticky-top', `${px}px`)
}

function syncSticky() {
  const sticky = stickyRef.value
  if (!sticky || getComputedStyle(sticky).position !== 'sticky') { isStuck.value = false; return }

  const gap      = sticky.getBoundingClientRect().top - viewportTop()
  const scrolled = scrollOffset()
  isStuck.value = scrolled > 0 && gap <= 1

  if (scrolled > 250 && Math.abs(gap) > 1 && Math.abs(gap) < 160 && calibrations < 4) {
    calibrations++
    applyStickyTop(stickyTop - gap)
    requestAnimationFrame(syncSticky)
  }
}

function onScrollSticky() {
  if (scrollTicking) return
  scrollTicking = true
  requestAnimationFrame(() => { scrollTicking = false; syncSticky() })
}

function initStickyTop() {
  calibrations = 0
  const padTop = scrollEl ? parseFloat(getComputedStyle(scrollEl).paddingTop) || 0 : 0
  applyStickyTop(-padTop)
}

function onResizeSticky() { initStickyTop(); syncSticky() }

function teardownSticky() {
  (scrollEl || window).removeEventListener('scroll', onScrollSticky)
  window.removeEventListener('resize', onResizeSticky)
}

function setupSticky() {
  teardownSticky()
  scrollEl = findScrollParent(pageRef.value)
  ;(scrollEl || window).addEventListener('scroll', onScrollSticky, { passive: true })
  window.addEventListener('resize', onResizeSticky)
  initStickyTop()
  syncSticky()
}

/* Once the page header has scrolled out of view, the sticky toolbar shows a compact
   "Add repeat buyer" button (wide screens only, where the block is sticky). */
function setupHeaderObserver() {
  headerObserver?.disconnect()
  if (!headerRef.value || !('IntersectionObserver' in window)) return
  headerObserver = new IntersectionObserver(
    ([entry]) => { headerOut.value = !entry.isIntersecting },
    { threshold: 0 }
  )
  headerObserver.observe(headerRef.value)
}

/* ── data ── */
async function loadCustomers() {
  loading.value = true
  loadError.value = ''
  try {
    const { data } = await api.get('/api/customers/list')
    customers.value = data.customers || []
    minVisits.value = data.min_visits || 10
  } catch (e) {
    loadError.value = 'Could not load customers. Check your connection and try again.'
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

// Which KPI card matches the list currently on screen.
const activeKpi = computed(() => {
  if (view.value === 'all') return 'total'
  if (view.value === 'tracking') return 'tracking'
  return sortKey.value === 'total_spent' && sortDir.value === 'desc' ? 'spent' : 'repeat'
})

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

const displayRows = computed(() => visibleRows.value.slice(0, limit.value))

// Any change to the list starts again from the first page.
watch([view, search, sortKey, sortDir], () => { limit.value = PAGE })

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
function ariaSort(key) {
  if (sortKey.value !== key) return 'none'
  return sortDir.value === 'asc' ? 'ascending' : 'descending'
}
function clearFilters() {
  search.value = ''
  view.value = 'all'
  sortKey.value = 'visits'
  sortDir.value = 'desc'
}

/* ── cards → filter + scroll to the list ──
   Clicking a card applies its filter, then scrolls down to the directory so the
   data the card stands for is on screen. The table stops just below the sticky
   block instead of sliding under it. Click the active card again to go back to the full list. */
const prefersReducedMotion = () =>
  typeof window !== 'undefined' && !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

async function scrollToDirectory() {
  await nextTick()
  const el = directoryEl.value
  if (!el) return
  const sticky = stickyRef.value
  const stuck  = !!sticky && getComputedStyle(sticky).position === 'sticky'
  el.style.scrollMarginTop = `${(stuck ? sticky.offsetHeight : 0) + 12}px`
  const reduce = prefersReducedMotion()
  el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' })
  el.focus({ preventScroll: true })

  // brief highlight so it is clear where the page landed
  if (!reduce) {
    flash.value = false
    clearTimeout(flashTimer)
    await nextTick()
    flash.value = true
    flashTimer = setTimeout(() => { flash.value = false }, 1400)
  }
}

function pickCard(key) {
  if (key === 'total' || activeKpi.value === key) {
    view.value = 'all'
    sortKey.value = 'visits'
    sortDir.value = 'desc'
  } else if (key === 'repeat')   { view.value = 'repeat';   sortKey.value = 'visits';      sortDir.value = 'desc' }
  else if (key === 'tracking')   { view.value = 'tracking'; sortKey.value = 'visits';      sortDir.value = 'desc' }
  else if (key === 'spent')      { view.value = 'repeat';   sortKey.value = 'total_spent'; sortDir.value = 'desc' }
  scrollToDirectory()
}

function showToast(message) {
  toast.value = message
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = '' }, 2600)
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
  const creating = form.value.mode === 'create'
  const payload = { CustomerName: name, ContactNumber: form.value.phone.trim() || null }
  try {
    if (creating) await api.post('/api/customers', payload)
    else await api.put(`/api/customers/${form.value.id}`, payload)
    form.value.saving = false
    form.value.open = false
    await loadCustomers()
    showToast(creating ? `${name} added as a repeat buyer` : 'Changes saved')
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
    showToast('Customer deleted')
  } catch (e) {
    form.value.error = firstError(e, 'Could not delete this customer.')
    form.value.confirmDelete = false
    console.error('customer delete error:', e)
  } finally {
    form.value.saving = false
  }
}

/* ── purchase history ── */
const detailTotal = computed(() => detail.value.sales.reduce((s, x) => s + num(x.TotalAmount), 0))

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

/* ── keyboard ── */
function onKey(e) {
  if (e.key === 'Escape') {
    if (form.value.open) closeForm()
    else if (detail.value.open) closeDetail()
    return
  }
  // "/" jumps to search (unless the person is already typing somewhere).
  if (e.key === '/' && !form.value.open && !detail.value.open) {
    const tag = (e.target && e.target.tagName) || ''
    if (/^(INPUT|TEXTAREA|SELECT)$/.test(tag) || (e.target && e.target.isContentEditable)) return
    e.preventDefault()
    searchInput.value?.focus()
  }
}

// The table height changes with the list, so re-measure the sticky block.
watch(loading, () => nextTick(syncSticky))

onMounted(() => {
  window.addEventListener('keydown', onKey)
  nextTick(setupSticky)         // pin the cards + search to the very top
  nextTick(setupHeaderObserver) // show the compact Add button once the header scrolls away
  loadCustomers()
})
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey)
  clearTimeout(toastTimer)
  clearTimeout(flashTimer)
  teardownSticky()
  headerObserver?.disconnect()
})
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
  --il-shadow:         var(--c-shadow-sm, none);
  --il-green:          #10b981;
  --il-green-soft:     rgba(16,185,129,0.12);
  --il-blue:           #3b82f6;
  --il-violet:         #6366f1;
  --il-red:            #f43f5e;
  --il-red-soft:       rgba(244,63,94,0.12);

  --il-green-icon:     #D1FAE5;
  --il-blue-icon:      #DBEAFE;
  --il-violet-icon:    #E0E7FF;

  --il-ease: cubic-bezier(.16, 1, .3, 1);
  --radius: 16px;

  /* ── SPACE TUNING (same values as the Product List) ──
     The app shell (Layout.vue) adds roughly 64px of its own padding around every page.
     --bleed-* reclaims part of it on desktop, on the top AND on both sides.
     Lower a value (or set it to 0px) if content ever touches the sidebar or top bar. */
  --bleed-x: 30px;
  --bleed-y: 26px;
  --il-gap: 16px;

  /* Where the sticky block stops. Set automatically by the script so the block
     sits flush at the very top; leave it alone unless you need to nudge it. */
  --sticky-top: 0px;

  min-height: 100%;
  overflow-x: clip;             /* never scrolls sideways (clip, so sticky keeps working) */
  background: var(--c-bg, #0f172a);
  padding: 16px 24px 40px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--il-gap);
  transition: background-color 0.22s ease;
}
.cust-page > * { min-width: 0; }

html[data-theme="dark"] .cust-page {
  --il-green-icon:   rgba(16,185,129,0.18);
  --il-blue-icon:    rgba(59,130,246,0.18);
  --il-violet-icon:  rgba(99,102,241,0.18);
  --il-green-soft:   rgba(16,185,129,0.16);
  --il-red-soft:     rgba(244,63,94,0.16);
}

.cust-page button:focus-visible,
.cust-page input:focus-visible,
.cust-page tr:focus-visible,
.cust-page a:focus-visible { outline: 2px solid var(--il-accent); outline-offset: 2px; }

.sr-only {
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
  overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0;
}

/* ── PAGE HEADER ── */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.page-title  { font-size: 26px; font-weight: 800; color: var(--il-text-primary); letter-spacing: -.03em; margin: 0; }
.page-sub    { display: flex; align-items: center; gap: 8px; margin: 6px 0 0; font-size: 14px; font-weight: 600; color: var(--il-text-secondary); }
.chip { font-size: 11.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }
.chip--live { color: var(--il-green); background: var(--il-green-soft); }

.report-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.rt-btn {
  display: flex; align-items: center; gap: 8px;
  border: 1px solid var(--il-border-strong);
  background: var(--il-surface-sunken);
  color: var(--il-text-secondary);
  font-family: inherit; font-size: 13px; font-weight: 600;
  padding: 8px 15px; border-radius: 11px; cursor: pointer;
  transition: background-color .16s ease, border-color .16s ease, color .16s ease;
}
.rt-btn i { font-size: 12px; }
.rt-btn--primary { background: var(--il-accent); border-color: var(--il-accent); color: #fff; }
.rt-btn--primary:hover:not(:disabled) { background: #FB923C; border-color: #FB923C; color: #fff; }

/* ── STICKY BLOCK (cards + search) ── */
.sticky-controls { display: flex; flex-direction: column; gap: 10px; min-width: 0; }

/* ── KPI CARDS (compact, one row, also the list filter) ── */
.kpi-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.kpi-card {
  display: flex; align-items: center; gap: 10px; width: 100%; min-width: 0;
  text-align: left; font: inherit; cursor: pointer;
  background: var(--il-surface); border: 1px solid var(--il-border);
  border-radius: 14px; box-shadow: var(--il-shadow);
  padding: 8px 12px;
  transition: border-color .18s ease, box-shadow .2s ease, background-color .18s ease;
}
.kpi-card:hover { border-color: var(--il-accent-border); }   /* no lift: the block is pinned, a lift would clip under its edge */
.kpi-card--featured { background: var(--il-accent-soft); border-color: var(--il-accent-border); }
.kpi-card--on { border-color: var(--il-accent); box-shadow: 0 0 0 3px var(--il-accent-soft); }

.kpi-icon {
  width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.kpi-icon--orange { background: var(--il-accent-soft); color: var(--il-accent); }
.kpi-icon--violet { background: var(--il-violet-icon); color: var(--il-violet); }
.kpi-icon--blue   { background: var(--il-blue-icon);   color: var(--il-blue); }
.kpi-icon--green  { background: var(--il-green-icon);  color: var(--il-green); }

.kpi-body  { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.kpi-label { font-size: 11.5px; font-weight: 600; color: var(--il-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.kpi-value {
  font-size: 20px; font-weight: 800; color: var(--il-text-primary);
  letter-spacing: -.04em; line-height: 1.1; font-variant-numeric: tabular-nums;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.kpi-value--accent { color: var(--il-accent); }
.kpi-value--green  { color: var(--il-green); }
.kpi-hint {
  font-size: 11px; font-weight: 600; color: var(--il-text-faint);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  transition: color .16s ease;
}
.kpi-hint i { font-size: 9px; margin-left: 3px; transition: transform .2s var(--il-ease); }
.kpi-card:hover .kpi-hint, .kpi-card--on .kpi-hint { color: var(--il-accent); }
.kpi-card:hover .kpi-hint i { transform: translateY(2px); }

/* ── TOOLBAR (search + compact Add button) ── */
.toolbar {
  background: var(--il-surface); border: 1px solid var(--il-border);
  border-radius: var(--radius); padding: 8px 10px;
  box-shadow: var(--il-shadow);
}
.toolbar-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.toolbar-end { display: flex; gap: 8px; margin-left: auto; flex-wrap: wrap; }

.search-wrap { position: relative; display: flex; align-items: center; flex: 1 1 260px; min-width: 0; }
.search-icon { position: absolute; left: 13px; font-size: 12px; color: var(--il-text-faint); pointer-events: none; }
.search-input {
  width: 100%; height: 38px;
  border: 1.5px solid var(--il-border-strong); border-radius: 10px;
  padding: 0 36px; font-family: inherit; font-size: 13.5px;
  color: var(--il-text-primary); background: var(--il-surface-sunken); outline: none;
  transition: border-color .15s ease, box-shadow .15s ease;
  -webkit-appearance: none; appearance: none;
}
.search-input::-webkit-search-cancel-button { display: none; }
.search-input:focus { border-color: var(--il-accent); box-shadow: 0 0 0 3px var(--il-accent-soft); }
.search-input::placeholder { color: var(--il-text-faint); }
.search-clear {
  position: absolute; right: 8px; width: 24px; height: 24px; border: none; border-radius: 7px; cursor: pointer;
  background: transparent; color: var(--il-text-muted); font-size: 11px;
}
.search-clear:hover { background: var(--il-surface-raised); color: var(--il-text-primary); }
.search-kbd {
  position: absolute; right: 10px; min-width: 20px; padding: 1px 6px; text-align: center;
  font: 600 11px 'Inter', system-ui, sans-serif; color: var(--il-text-faint);
  border: 1px solid var(--il-border-strong); border-radius: 6px; pointer-events: none;
}

/* hidden by default; turned on below where the toolbar is sticky (wide screens) */
.toolbar-add { display: none; height: 38px; padding: 0 15px; white-space: nowrap; }

/* Buttons */
.btn-primary {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  height: 40px; padding: 0 18px; border: none; border-radius: 11px; cursor: pointer;
  background: var(--il-accent); color: #fff;
  font-family: inherit; font-size: 13px; font-weight: 700;
  transition: background-color .16s ease;
}
.btn-primary:hover:not(:disabled) { background: #FB923C; }
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
.link-btn {
  border: none; background: transparent; cursor: pointer;
  font-family: inherit; font-size: 13px; font-weight: 700;
  color: var(--il-accent); padding: 6px 8px; border-radius: 8px;
}
.link-btn:hover { background: var(--il-accent-soft); }

.icon-btn {
  width: 32px; height: 32px; border-radius: 9px; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  border: 1px solid var(--il-border-strong); background: var(--il-surface);
  color: var(--il-text-secondary); font-size: 12px;
  transition: background-color .16s ease, border-color .16s ease, color .16s ease;
}
.icon-btn:hover:not(:disabled) { color: var(--il-accent); border-color: var(--il-accent-border); background: var(--il-accent-soft); }
.icon-btn:disabled { opacity: .4; cursor: not-allowed; }

/* ── DIRECTORY CARD ── */
.card {
  background: var(--il-surface); border: 1px solid var(--il-border);
  border-radius: var(--radius); box-shadow: var(--il-shadow);
  padding: 20px 24px 16px; min-width: 0;
}
.directory { outline: none; scroll-margin-top: 16px; transition: box-shadow .3s ease, border-color .3s ease; }
.directory--flash { animation: dir-flash 1.4s ease-out; }
@keyframes dir-flash {
  0%   { box-shadow: 0 0 0 0 var(--il-accent-border); border-color: var(--il-accent); }
  30%  { box-shadow: 0 0 0 5px var(--il-accent-soft); border-color: var(--il-accent); }
  100% { box-shadow: 0 0 0 0 transparent; }
}

.card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
.card-title { font-size: 16px; font-weight: 700; color: var(--il-text-primary); margin: 0; display: flex; align-items: center; gap: 8px; letter-spacing: -.01em; }
.card-sub   { margin: 4px 0 0; font-size: 12.5px; color: var(--il-text-muted); line-height: 1.45; max-width: 520px; }
.count-chip { font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px; background: var(--il-accent-soft); color: var(--il-accent); }

/* States */
.state {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  padding: 36px 12px; text-align: center; font-size: 13.5px; line-height: 1.6; color: var(--il-text-muted);
}
.state > i { font-size: 24px; color: var(--il-text-faint); }
.state p { margin: 0; }
.state-sub { font-size: 12.5px; color: var(--il-text-faint); }
.state strong { color: var(--il-text-primary); }
.state--error, .state--error > i { color: var(--il-red); }
.state--inline { padding: 20px 0; flex-direction: row; justify-content: center; }

.skeleton-list { display: flex; flex-direction: column; gap: 10px; padding: 6px 0; }
.skeleton-row {
  display: block; height: 52px; border-radius: 12px;
  background: linear-gradient(90deg, var(--il-surface-sunken) 25%, var(--il-surface-raised) 50%, var(--il-surface-sunken) 75%);
  background-size: 200% 100%; animation: shimmer 1.4s linear infinite;
}
@keyframes shimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }

/* Table */
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.data-table th {
  text-align: left; padding: 0; white-space: nowrap;
  background: var(--il-surface-raised);
}
.data-table th:first-child { border-radius: 10px 0 0 10px; }
.data-table th:last-child  { border-radius: 0 10px 10px 0; }
.th-btn {
  width: 100%; text-align: left; padding: 12px 16px; border: none; background: transparent; cursor: pointer;
  font-family: inherit; font-size: 11px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
  color: var(--il-text-muted); user-select: none;
}
.th-btn:hover { color: var(--il-text-primary); }
.th-actions { width: 60px; }
.data-table td { padding: 12px 16px; border-bottom: 1px solid var(--il-border); white-space: nowrap; color: var(--il-text-secondary); vertical-align: middle; }
.data-table tbody tr:last-child td { border-bottom: none; }
.td-actions { text-align: right; }
.row-click { cursor: pointer; outline: none; }
.row-click:hover td, .row-click:focus-visible td { background: var(--il-surface-raised); }
.row-click:focus-visible { outline: 2px solid var(--il-accent); outline-offset: -2px; }

.sort-icon { margin-left: 4px; font-size: 9px; color: var(--il-text-faint); }
.sort-icon--on { color: var(--il-accent); }

.cell-person { display: flex; align-items: center; gap: 12px; }
.avatar {
  width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; letter-spacing: .02em;
  background: var(--il-surface-sunken); color: var(--il-text-muted); border: 1px solid var(--il-border);
}
.avatar--repeat { background: var(--il-accent-soft); color: var(--il-accent); border-color: var(--il-accent-border); }

.cell-name { font-weight: 600; color: var(--il-text-primary); display: flex; align-items: center; gap: 8px; }
.cell-sub { font-size: 11.5px; color: var(--il-text-faint); margin-top: 2px; }
.cell-strong { font-weight: 700; color: var(--il-text-primary); font-variant-numeric: tabular-nums; }
.cell-faint { color: var(--il-text-secondary); font-size: 12.5px; }

.pill { font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }
.pill--repeat { background: var(--il-accent-soft); color: var(--il-accent); border: 1px solid var(--il-accent-border); }

.progress { width: 120px; height: 5px; margin-top: 5px; border-radius: 4px; background: var(--il-surface-sunken); overflow: hidden; }
.progress span { display: block; height: 100%; border-radius: 4px; background: var(--il-accent); transition: width .3s ease; }

.table-foot {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  margin-top: 8px; padding-top: 12px; border-top: 1px solid var(--il-border);
  font-size: 12.5px; color: var(--il-text-muted);
}

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
.modal-eyebrow { margin: 0 0 4px; font-size: 12px; font-weight: 600; color: var(--il-accent); }
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
  transition: border-color .15s, background-color .15s, box-shadow .15s;
}
.field-input:focus { border-color: var(--il-accent); background: var(--il-surface); box-shadow: 0 0 0 3px var(--il-accent-soft); }
.field-input::placeholder { color: var(--il-text-faint); }
.field-hint { font-size: 11.5px; color: var(--il-text-faint); line-height: 1.5; }
.form-error { margin: 0; font-size: 12.5px; font-weight: 600; color: var(--il-red); display: flex; align-items: center; gap: 6px; }

.form-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding-top: 4px; }
.form-actions .spacer { flex: 1; }
.confirm-text { flex: 1 1 100%; margin: 0 0 4px; font-size: 12.5px; color: var(--il-text-secondary); line-height: 1.5; }

/* Purchase history */
.detail-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.detail-stats > div {
  display: flex; flex-direction: column; gap: 2px; padding: 10px 12px;
  background: var(--il-surface-sunken); border: 1px solid var(--il-border); border-radius: 12px;
}
.detail-stats span { font-size: 11.5px; color: var(--il-text-muted); }
.detail-stats strong { font-size: 15px; font-weight: 800; color: var(--il-text-primary); letter-spacing: -.02em; font-variant-numeric: tabular-nums; }

.sale { background: var(--il-surface-raised); border: 1px solid var(--il-border); border-radius: 12px; padding: 12px 14px; }
.sale-top { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
.sale-id { margin: 0; font-size: 13px; font-weight: 700; color: var(--il-text-primary); }
.sale-total { margin: 0; font-size: 14px; font-weight: 800; color: var(--il-accent); }
.sale-items { list-style: none; margin: 10px 0 0; padding: 10px 0 0; border-top: 1px solid var(--il-border); display: flex; flex-direction: column; gap: 6px; }
.sale-items li { display: flex; justify-content: space-between; gap: 12px; font-size: 12.5px; color: var(--il-text-secondary); }

/* Toast */
.toast {
  position: fixed; left: 50%; bottom: 28px; transform: translateX(-50%); z-index: 1100;
  display: inline-flex; align-items: center; gap: 9px;
  padding: 11px 18px; border-radius: 12px;
  background: var(--il-surface-raised); color: var(--il-text-primary);
  border: 1px solid var(--il-border-strong);
  font-size: 13px; font-weight: 600;
  box-shadow: 0 16px 40px rgba(0,0,0,.35);
}
.toast i { color: var(--il-green); }
.toast-enter-active, .toast-leave-active { transition: opacity .2s ease, transform .25s var(--il-ease); }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translate(-50%, 10px); }

/* ── RESPONSIVE ── */

/* Desktop only: pull the page outward into the shell's padding (top, left AND right),
   exactly like the Product List, so the edges are used as well. */
@media (min-width: 1001px) {
  .cust-page { margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) 0; }
}

/* Wide screens: the cards + search stay pinned at the very top while the table scrolls.
   The block reaches the page edges on both sides (negative margin + matching padding),
   so no row can peek out beside it, and a soft shadow shows when it is pinned. */
@media (min-width: 1101px) {
  .sticky-controls {
    position: sticky; top: var(--sticky-top); z-index: 20;
    background: var(--c-bg, #0f172a);
    padding: 10px 24px 10px; margin: -10px -24px -10px;
    transition: box-shadow .2s ease;
  }
  .sticky-controls.is-stuck {
    box-shadow: 0 1px 0 var(--il-border), 0 14px 18px -16px rgba(0, 0, 0, .55);
  }
  .toolbar-add { display: inline-flex; }
}

@media (max-width: 1100px) { .kpi-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 900px)  { .cust-page { padding: 12px 14px 32px; --il-gap: 14px; } }
@media (max-width: 700px) {
  .cust-page { padding: 16px 16px 40px; }
  .page-header { flex-direction: column; align-items: stretch; }
  .report-toolbar { justify-content: stretch; }
  .rt-btn { flex: 1; justify-content: center; }
  .kpi-strip { grid-template-columns: 1fr; }
  .card { padding: 18px 16px 14px; }
  .toolbar-row { flex-direction: column; align-items: stretch; }
  .search-wrap { flex-basis: auto; }
  .detail-stats { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
  .cust-page *, .cust-page *::before, .cust-page *::after { transition: none !important; animation: none !important; }
}
</style>