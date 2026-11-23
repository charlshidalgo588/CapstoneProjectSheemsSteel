<template>
  <Layout title="Customers">
    <div class="cust-page" ref="pageRef">

      <!-- ── PAGE HEADER (same compact row as Product List / Transaction: SALES / Customers) ── -->
      <header ref="headerRef" class="page-header">
        <div class="page-heading">
          <div class="page-title-row">
            <span class="page-eyebrow">Sales</span>
            <span class="page-crumb-sep" aria-hidden="true">/</span>
            <h1 class="page-title">Customers</h1>
          </div>
        </div>

        <div class="header-actions">
          <button
            type="button"
            class="btn btn--primary btn--sm"
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
                class="btn btn--primary btn--sm tool-btn toolbar-add"
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
          <!-- Phone-only tools: sort (the table header is hidden on phones) -->
          <div class="mobile-tools">
            <div class="mobile-sort">
              <span>Sort</span>
              <button
                ref="sortTrigger"
                type="button"
                class="sort-trigger"
                :class="{ 'sort-trigger--open': sortOpen }"
                aria-haspopup="listbox"
                :aria-expanded="sortOpen"
                @click="toggleSort"
              >
                <span class="sort-trigger-label">{{ sortLabel }}</span>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
              </button>
            </div>
          </div>

          <!-- Table (turns into customer cards on phones) -->
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
                  <td class="td-cust">
                    <div class="cell-person">
                      <span class="avatar" :class="{ 'avatar--repeat': c.is_repeat }" aria-hidden="true">{{ initials(c.CustomerName) }}</span>
                      <div class="cell-person-info">
                        <div class="cell-name">
                          {{ c.CustomerName || 'Unnamed' }}
                          <span v-if="c.is_repeat" class="pill pill--repeat">Repeat buyer</span>
                        </div>
                        <div class="cell-sub">{{ c.ContactNumber || 'no phone' }} · {{ c.CustomerCode }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="td-vis" data-label="Purchases">
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
                  <td class="td-spent cell-strong" data-label="Total spent">{{ peso(c.total_spent) }}</td>
                  <td class="td-last" data-label="Last purchase">
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

          <!-- Footer: range + pagination (10 per page) -->
          <div class="table-foot">
            <span>Showing <strong>{{ rangeStart }}–{{ rangeEnd }}</strong> of {{ visibleRows.length }}</span>

            <nav v-if="totalPages > 1" class="pager" aria-label="Pagination">
              <button
                type="button"
                class="pager-btn pager-btn--nav"
                :disabled="page === 1"
                aria-label="Previous page"
                @click="goPage(page - 1)"
              >
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span class="pager-nav-text">Prev</span>
              </button>

              <template v-for="(p, i) in pageItems" :key="`${p}-${i}`">
                <span v-if="p === '…'" class="pager-gap" aria-hidden="true">…</span>
                <button
                  v-else
                  type="button"
                  class="pager-btn"
                  :class="{ 'pager-btn--on': p === page }"
                  :aria-current="p === page ? 'page' : null"
                  :aria-label="`Page ${p}`"
                  @click="goPage(p)"
                >{{ p }}</button>
              </template>

              <button
                type="button"
                class="pager-btn pager-btn--nav"
                :disabled="page === totalPages"
                aria-label="Next page"
                @click="goPage(page + 1)"
              >
                <span class="pager-nav-text">Next</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </button>
            </nav>
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
              after {{ minVisits }} purchases. A name that is already a repeat buyer cannot be added again.
              If the name is only being tracked, that customer is upgraded instead of duplicated.
            </p>

            <label class="field">
              <span class="field-label">Customer name <em>required</em></span>
              <input
                ref="nameInput"
                v-model="form.name"
                type="text"
                class="field-input"
                placeholder="e.g. Juan Dela Cruz or BuildRight Construction"
                @input="form.error = ''"
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
                @input="form.error = ''"
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

      <!-- ── SORT DROPDOWN (phones): small custom menu anchored to the Sort button ── -->
      <Transition name="menu">
        <div v-if="sortOpen" class="menu-backdrop" @click="closeSort">
          <div
            ref="menuEl"
            class="sort-menu"
            :class="{ 'sort-menu--up': menuPos.up }"
            :style="menuPos.style"
            role="listbox"
            aria-label="Sort customers"
            @click.stop
            @keydown="onMenuKey"
          >
            <p class="sort-menu-title">Sort by</p>
            <button
              v-for="(o, i) in sortOptions"
              :key="o.value"
              type="button"
              role="option"
              class="sort-opt"
              :class="{ 'sort-opt--on': sortChoice === o.value, 'sort-opt--sep': i > 0 && i % 2 === 0 }"
              :aria-selected="sortChoice === o.value"
              @click="chooseSort(o.value)"
            >
              <i :class="o.icon" class="sort-opt-icon" aria-hidden="true"></i>
              <span>{{ o.label }}</span>
              <i v-if="sortChoice === o.value" class="fa-solid fa-check sort-opt-check" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      </Transition>

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

const PAGE = 10           // customers per page
const page = ref(1)       // current page (1-based)

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

// Same rule as the server: ignore capitals and extra spaces when comparing names.
function normName(s) { return String(s || '').trim().replace(/\s+/g, ' ').toLowerCase() }

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
  window.removeEventListener('orientationchange', onResizeSticky)
}

function setupSticky() {
  teardownSticky()
  scrollEl = findScrollParent(pageRef.value)
  ;(scrollEl || window).addEventListener('scroll', onScrollSticky, { passive: true })
  window.addEventListener('resize', onResizeSticky)
  window.addEventListener('orientationchange', onResizeSticky)
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

/* ── pagination (10 per page) ── */
const totalPages = computed(() => Math.max(1, Math.ceil(visibleRows.value.length / PAGE)))

const displayRows = computed(() => {
  const start = (page.value - 1) * PAGE
  return visibleRows.value.slice(start, start + PAGE)
})

const rangeStart = computed(() => (visibleRows.value.length ? (page.value - 1) * PAGE + 1 : 0))
const rangeEnd   = computed(() => Math.min(page.value * PAGE, visibleRows.value.length))

// Page buttons: 1 … 4 5 6 … 12 (never more than 7 slots)
const pageItems = computed(() => {
  const total = totalPages.value
  const cur   = page.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const items = [1]
  const from = Math.max(2, cur - 1)
  const to   = Math.min(total - 1, cur + 1)
  if (from > 2) items.push('…')
  for (let p = from; p <= to; p++) items.push(p)
  if (to < total - 1) items.push('…')
  items.push(total)
  return items
})

function goPage(p) {
  const next = Math.min(Math.max(1, p), totalPages.value)
  if (next === page.value) return
  page.value = next
  scrollToDirectory(false)
}

// Any change to the list starts again from the first page.
watch([view, search, sortKey, sortDir], () => { page.value = 1 })
// If the list shrinks (delete, reload), never stay on a page that no longer exists.
watch(totalPages, (t) => { if (page.value > t) page.value = t })

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

// Phone sort: one value that maps onto sortKey + sortDir.
const sortChoice = computed({
  get: () => `${sortKey.value}-${sortDir.value}`,
  set: (v) => {
    const [k, d] = String(v).split('-')
    sortKey.value = k
    sortDir.value = d
  },
})

/* ── Phone sort dropdown ── */
const sortOpen    = ref(false)
const sortTrigger = ref(null)
const menuEl      = ref(null)
const menuPos     = ref({ up: false, style: {} })
const MENU_W      = 232
const MENU_EST_H  = 360

const sortOptions = [
  { value: 'visits-desc',        label: 'Most purchases',   icon: 'fa-solid fa-cart-shopping' },
  { value: 'visits-asc',         label: 'Fewest purchases', icon: 'fa-solid fa-cart-shopping' },
  { value: 'total_spent-desc',   label: 'Highest spent',    icon: 'fa-solid fa-peso-sign' },
  { value: 'total_spent-asc',    label: 'Lowest spent',     icon: 'fa-solid fa-peso-sign' },
  { value: 'last_purchase-desc', label: 'Latest purchase',  icon: 'fa-solid fa-clock' },
  { value: 'last_purchase-asc',  label: 'Oldest purchase',  icon: 'fa-solid fa-clock' },
  { value: 'CustomerName-asc',   label: 'Name: A to Z',     icon: 'fa-solid fa-arrow-down-a-z' },
  { value: 'CustomerName-desc',  label: 'Name: Z to A',     icon: 'fa-solid fa-arrow-down-z-a' },
]
const sortLabel = computed(() => sortOptions.find((o) => o.value === sortChoice.value)?.label || 'Sort')

// Place the menu under the Sort button (right-aligned). Flips above it if there is more room there.
function placeMenu() {
  const r = sortTrigger.value?.getBoundingClientRect()
  if (!r) return
  const vw = window.innerWidth
  const vh = window.innerHeight
  const below = vh - r.bottom - 12
  const above = r.top - 12
  const up = below < MENU_EST_H && above > below
  const space = up ? above : below
  menuPos.value = {
    up,
    style: {
      width: Math.min(MENU_W, vw - 24) + 'px',
      right: Math.max(12, vw - r.right) + 'px',
      maxHeight: Math.max(160, space - 6) + 'px',
      ...(up ? { bottom: vh - r.top + 6 + 'px' } : { top: r.bottom + 6 + 'px' }),
    },
  }
}
function toggleSort() {
  if (sortOpen.value) { closeSort(); return }
  placeMenu()
  sortOpen.value = true
}
function closeSort() { sortOpen.value = false }
function chooseSort(value) {
  sortChoice.value = value
  closeSort()
  sortTrigger.value?.focus()
}
// Arrow keys move through the options
function onMenuKey(e) {
  if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(e.key)) return
  e.preventDefault()
  const items = Array.from(menuEl.value?.querySelectorAll('.sort-opt') || [])
  if (!items.length) return
  const i = items.indexOf(document.activeElement)
  let next = i
  if (e.key === 'ArrowDown') next = i < items.length - 1 ? i + 1 : 0
  else if (e.key === 'ArrowUp') next = i > 0 ? i - 1 : items.length - 1
  else if (e.key === 'Home') next = 0
  else next = items.length - 1
  items[next].focus()
}
// The menu is fixed to the screen, so close it if the page moves underneath it.
function onScrollClose(e) {
  if (menuEl.value && menuEl.value.contains(e.target)) return
  closeSort()
}
watch(sortOpen, async (open) => {
  if (open) {
    window.addEventListener('resize', closeSort)
    window.addEventListener('scroll', onScrollClose, true)
    await nextTick()
    menuEl.value?.querySelector('.sort-opt--on')?.focus()
  } else {
    window.removeEventListener('resize', closeSort)
    window.removeEventListener('scroll', onScrollClose, true)
  }
})

function clearFilters() {
  search.value = ''
  view.value = 'all'
  sortKey.value = 'visits'
  sortDir.value = 'desc'
}

/* ── cards → filter + scroll to the list ──
   Clicking a card applies its filter, then scrolls down to the directory so the
   data the card stands for is on screen. The table stops just below the sticky
   block instead of sliding under it. Click the active card again to go back to the full list.
   (Also used by the pager, without the highlight flash.) */
const prefersReducedMotion = () =>
  typeof window !== 'undefined' && !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

async function scrollToDirectory(withFlash = true) {
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
  if (withFlash && !reduce) {
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

/* Quick duplicate check before calling the server (the server checks again).
   Returns an error message, or '' when the name is fine. */
function duplicateMessage(name, mode, ignoreId) {
  const key = normName(name)
  const match = customers.value.find(
    (c) => normName(c.CustomerName) === key && (mode === 'create' || c.CustomerID !== ignoreId)
  )
  if (!match) return ''
  if (mode === 'create') {
    // A name that is only being tracked is upgraded by the server, so only block real repeat buyers.
    return match.is_repeat
      ? `“${match.CustomerName}” is already a repeat buyer (${match.CustomerCode}). No duplicate was added.`
      : ''
  }
  return `Another customer is already named “${match.CustomerName}” (${match.CustomerCode}).`
}

async function saveCustomer() {
  if (form.value.saving) return
  const name = form.value.name.trim().replace(/\s+/g, ' ')
  if (!name) { form.value.error = 'Customer name is required.'; return }

  const creating = form.value.mode === 'create'

  // Edit: only complain when the name actually changed, so old duplicates can still be edited.
  const original = customers.value.find((c) => c.CustomerID === form.value.id)
  const nameChanged = creating || normName(original?.CustomerName) !== normName(name)
  if (nameChanged) {
    const dup = duplicateMessage(name, form.value.mode, form.value.id)
    if (dup) { form.value.error = dup; return }
  }

  form.value.saving = true
  form.value.error = ''
  const payload = { CustomerName: name, ContactNumber: form.value.phone.trim() || null }
  try {
    let upgraded = false
    if (creating) {
      const res = await api.post('/api/customers', payload)
      upgraded = !!res?.data?.upgraded
    } else {
      await api.put(`/api/customers/${form.value.id}`, payload)
    }
    form.value.saving = false
    form.value.open = false
    await loadCustomers()
    showToast(
      creating
        ? (upgraded ? `${name} was already tracked and is now a repeat buyer` : `${name} added as a repeat buyer`)
        : 'Changes saved'
    )
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
    if (sortOpen.value) { closeSort(); sortTrigger.value?.focus(); return }
    if (form.value.open) closeForm()
    else if (detail.value.open) closeDetail()
    return
  }
  // "/" jumps to search (unless the person is already typing somewhere).
  if (e.key === '/' && !form.value.open && !detail.value.open && !sortOpen.value) {
    const tag = (e.target && e.target.tagName) || ''
    if (/^(INPUT|TEXTAREA|SELECT)$/.test(tag) || (e.target && e.target.isContentEditable)) return
    e.preventDefault()
    searchInput.value?.focus()
  }
}

// The table height changes with the list, so re-measure the sticky block.
watch(loading, () => nextTick(syncSticky))
watch(page, () => nextTick(syncSticky))

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
  window.removeEventListener('resize', closeSort)
  window.removeEventListener('scroll', onScrollClose, true)
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

  /* ── SPACE TUNING (same values as Product List, Transaction, Home, Reports) ──
     The app shell (Layout.vue) adds its own padding around every page.
     --bleed-x / --bleed-y let this page reclaim part of that padding
     (left, right and top) on EVERY screen size; each breakpoint below sets
     its own values. --il-pad-x is the page's own inner side padding.
     Raise a bleed value to push content closer to the edge; lower it (or set
     it to 0px) if content ever touches the sidebar, top bar or screen edge.

     IMPORTANT: do NOT add `max-width: 100%` to .cust-page. It caps the width at
     the shell's content box, so the negative side margins below can no longer
     stretch the page and a strip is left empty. */
  --bleed-x: 30px;
  --bleed-y: 30px;
  --il-gap: 12px;
  --il-pad-x: 24px;

  /* Where the sticky block stops. Set automatically by the script so the block
     sits flush at the very top; leave it alone unless you need to nudge it. */
  --sticky-top: 0px;

  min-height: 100%;
  overflow-x: clip;             /* never scrolls sideways (clip, so sticky keeps working) */
  background: var(--c-bg, #0f172a);
  padding: 8px max(var(--il-pad-x), env(safe-area-inset-right)) 40px max(var(--il-pad-x), env(safe-area-inset-left));
  /* reclaim the shell's top / left / right padding on every screen size */
  margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) 0;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--il-gap);
  transition: background-color 0.22s ease;
  -webkit-text-size-adjust: 100%;
}
.cust-page > * { min-width: 0; }

html[data-theme="dark"] .cust-page {
  --il-green-icon:   rgba(16,185,129,0.18);
  --il-blue-icon:    rgba(59,130,246,0.18);
  --il-violet-icon:  rgba(99,102,241,0.18);
  --il-green-soft:   rgba(16,185,129,0.16);
  --il-red-soft:     rgba(244,63,94,0.16);
}

/* Snappier taps on touch screens */
.cust-page button, .cust-page a { touch-action: manipulation; -webkit-tap-highlight-color: transparent; }

.cust-page button:focus-visible,
.cust-page input:focus-visible,
.cust-page tr:focus-visible,
.cust-page a:focus-visible { outline: 2px solid var(--il-accent); outline-offset: 2px; }

.sr-only {
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
  overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0;
}

/* ── PAGE HEADER (same as Product List / Transaction: SALES / Customers) ──
   "Sales" and "Customers" share the same size (16px), each keeping its own style. */
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-heading { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.page-title-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: 4px 10px; min-width: 0; }
.page-eyebrow { font-size: 16px; font-weight: 700; letter-spacing: .12em; color: var(--il-accent); text-transform: uppercase; line-height: 1.2; margin: 0; }
.page-crumb-sep { font-size: 16px; font-weight: 300; color: var(--il-text-faint); line-height: 1.2; }
.page-title { font-size: 16px; font-weight: 800; color: var(--il-text-primary); letter-spacing: -.02em; line-height: 1.2; margin: 0; }
.header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

/* ── BUTTONS (same as Product List / Transaction) ── */
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s, filter .18s;
}
.btn:active { transform: scale(.98); }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }
.btn--primary {
  background: linear-gradient(135deg, var(--il-accent), var(--c-accent-deep, var(--il-accent)));
  color: #fff; box-shadow: var(--c-shadow-accent, none);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h, none); transform: translateY(-1px); }

/* ── STICKY BLOCK (cards + search) ── */
.sticky-controls { display: flex; flex-direction: column; gap: 10px; min-width: 0; }

/* ── KPI CARDS (compact, one row, also the list filter; same as Product List) ── */
.kpi-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.kpi-card {
  display: flex; align-items: center; gap: 10px; width: 100%; min-width: 0;
  text-align: left; font: inherit; cursor: pointer;
  background: var(--il-surface); border: 1px solid var(--il-border);
  border-radius: 14px; box-shadow: var(--il-shadow);
  padding: 8px 12px;
  transition: border-color .18s ease, box-shadow .2s ease, background-color .18s ease;
  animation: kpi-rise .6s var(--il-ease) backwards;
}
.kpi-card:nth-child(2) { animation-delay: 60ms; }
.kpi-card:nth-child(3) { animation-delay: 120ms; }
.kpi-card:nth-child(4) { animation-delay: 180ms; }
@keyframes kpi-rise { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
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

.kpi-body  { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.kpi-label {
  font-size: 10.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
  color: var(--il-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.kpi-value {
  font-size: 20px; font-weight: 800; color: var(--il-text-primary);
  letter-spacing: -.03em; line-height: 1.1; font-variant-numeric: tabular-nums;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.kpi-value--accent { color: var(--il-accent); }
.kpi-value--green  { color: var(--il-green); }
.kpi-hint {
  font-size: 11.5px; font-weight: 600; color: var(--il-text-faint);
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
.tool-btn { height: 38px; }

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
  position: absolute; right: 8px; width: 24px; height: 24px; border: none; border-radius: 6px; cursor: pointer;
  background: transparent; color: var(--il-text-faint); font-size: 11px;
  display: flex; align-items: center; justify-content: center;
}
.search-clear:hover { background: var(--il-surface-raised); color: var(--il-text-primary); }
.search-kbd {
  position: absolute; right: 10px; min-width: 20px; padding: 1px 6px; text-align: center;
  font: 600 11px 'Inter', system-ui, sans-serif; color: var(--il-text-faint);
  border: 1px solid var(--il-border-strong); border-radius: 6px; pointer-events: none;
}

/* hidden by default; turned on below where the toolbar is sticky (wide screens) */
.toolbar-add { display: none; white-space: nowrap; }

/* Modal buttons */
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

/* ── DIRECTORY CARD (edge-to-edge table, same look as the Product List table card) ── */
.card {
  background: var(--il-surface); border: 1px solid var(--il-border);
  border-radius: var(--radius); box-shadow: var(--il-shadow);
  padding: 0; min-width: 0; overflow: hidden;
}
.directory { outline: none; scroll-margin-top: 120px; transition: box-shadow .3s ease, border-color .3s ease; }
.directory--flash { animation: dir-flash 1.4s ease-out; }
@keyframes dir-flash {
  0%   { box-shadow: 0 0 0 0 var(--il-accent-border); border-color: var(--il-accent); }
  30%  { box-shadow: 0 0 0 5px var(--il-accent-soft); border-color: var(--il-accent); }
  100% { box-shadow: 0 0 0 0 transparent; }
}

.card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 16px 18px 14px; border-bottom: 1px solid var(--il-border); }
.card-title { font-size: 16px; font-weight: 700; color: var(--il-text-primary); margin: 0; display: flex; align-items: center; gap: 8px; letter-spacing: -.01em; }
.card-sub   { margin: 4px 0 0; font-size: 12.5px; color: var(--il-text-muted); line-height: 1.45; max-width: 520px; }
.count-chip { font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px; background: var(--il-accent-soft); color: var(--il-accent); }

/* States */
.state {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  padding: 44px 16px; text-align: center; font-size: 13.5px; line-height: 1.6; color: var(--il-text-muted);
}
.state > i { font-size: 32px; color: var(--il-text-faint); margin-bottom: 4px; }
.state p { margin: 0; }
.state-sub { font-size: 12.5px; color: var(--il-text-faint); }
.state strong { color: var(--il-text-primary); }
.state--error, .state--error > i { color: var(--il-red); }
.state--inline { padding: 20px 0; flex-direction: row; justify-content: center; }
.state--inline > i { font-size: 16px; margin: 0; }

.skeleton-list { display: flex; flex-direction: column; gap: 10px; padding: 16px 18px 18px; }
.skeleton-row {
  display: block; height: 52px; border-radius: 12px;
  background: linear-gradient(90deg, var(--il-surface-sunken) 25%, var(--il-surface-raised) 50%, var(--il-surface-sunken) 75%);
  background-size: 200% 100%; animation: shimmer 1.4s linear infinite;
}
@keyframes shimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }

/* Phone-only tools (sort) */
.mobile-tools { display: none; }

/* Table */
.table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.data-table thead th {
  text-align: left; padding: 0; white-space: nowrap;
  background: var(--il-surface-raised); border-bottom: 1px solid var(--il-border);
}
.th-btn {
  width: 100%; text-align: left; padding: 12px 16px; border: none; background: transparent; cursor: pointer;
  font-family: inherit; font-size: 11px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
  color: var(--il-text-faint); user-select: none; transition: color .15s;
}
.th-btn:hover { color: var(--il-text-primary); }
.th-actions { width: 60px; }
.data-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--il-border); white-space: nowrap; color: var(--il-text-secondary); vertical-align: middle; transition: background-color .15s; }
.data-table tbody tr:last-child td { border-bottom: none; }
.td-actions { text-align: right; }
.row-click { cursor: pointer; outline: none; }
.row-click:hover td, .row-click:focus-visible td { background: var(--il-surface-raised); }
.row-click:focus-visible { outline: 2px solid var(--il-accent); outline-offset: -2px; }

.sort-icon { margin-left: 4px; font-size: 10px; opacity: .5; }
.sort-icon--on { opacity: 1; color: var(--il-accent); }

.cell-person { display: flex; align-items: center; gap: 12px; min-width: 0; }
.cell-person-info { min-width: 0; }
.avatar {
  width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; letter-spacing: .02em;
  background: var(--il-surface-sunken); color: var(--il-text-muted); border: 1px solid var(--il-border);
}
.avatar--repeat { background: var(--il-accent-soft); color: var(--il-accent); border-color: var(--il-accent-border); }

.cell-name { font-weight: 700; color: var(--il-text-primary); display: flex; align-items: center; flex-wrap: wrap; gap: 4px 8px; font-size: 13.5px; }
.cell-sub { font-size: 11.5px; color: var(--il-text-faint); margin-top: 2px; font-variant-numeric: tabular-nums; }
.cell-strong { font-weight: 700; color: var(--il-text-primary); font-variant-numeric: tabular-nums; }
.cell-faint { color: var(--il-text-secondary); font-size: 12.5px; }

.pill { font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }
.pill--repeat { background: var(--il-accent-soft); color: var(--il-accent); border: 1px solid var(--il-accent-border); }

.progress { width: 120px; max-width: 100%; height: 5px; margin-top: 5px; border-radius: 4px; background: var(--il-surface-sunken); overflow: hidden; }
.progress span { display: block; height: 100%; border-radius: 4px; background: var(--il-accent); transition: width .3s ease; }

/* Footer: range + pagination */
.table-foot {
  display: flex; align-items: center; justify-content: space-between; gap: 8px 16px; flex-wrap: wrap;
  padding: 11px 18px; border-top: 1px solid var(--il-border);
  font-size: 12px; color: var(--il-text-muted); background: var(--il-surface-raised);
}
.table-foot strong { color: var(--il-text-primary); }

/* Pagination */
.pager { display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.pager-btn {
  min-width: 32px; height: 32px; padding: 0 10px; border-radius: 9px; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  border: 1px solid var(--il-border-strong); background: var(--il-surface);
  color: var(--il-text-secondary); font-family: inherit; font-size: 12.5px; font-weight: 700;
  font-variant-numeric: tabular-nums;
  transition: background-color .16s ease, border-color .16s ease, color .16s ease;
}
.pager-btn:hover:not(:disabled):not(.pager-btn--on) { color: var(--il-accent); border-color: var(--il-accent-border); background: var(--il-accent-soft); }
.pager-btn--on { background: var(--il-accent); border-color: var(--il-accent); color: #fff; cursor: default; }
.pager-btn:disabled { opacity: .4; cursor: not-allowed; }
.pager-btn i { font-size: 10px; }
.pager-gap { min-width: 20px; text-align: center; color: var(--il-text-faint); font-weight: 700; user-select: none; }

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
  width: 32px; height: 32px; border-radius: 9px; cursor: pointer; flex-shrink: 0;
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
.form-error {
  margin: 0; padding: 10px 12px; border-radius: 10px;
  background: var(--il-red-soft); border: 1px solid rgba(244,63,94,.35);
  font-size: 12.5px; font-weight: 600; line-height: 1.5; color: var(--il-red);
  display: flex; align-items: flex-start; gap: 8px;
}
.form-error i { margin-top: 2px; flex-shrink: 0; }

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

/* ── SORT DROPDOWN (phones): compact custom menu, replaces the native <select> list ── */
.menu-backdrop { position: fixed; inset: 0; z-index: 1000; background: transparent; }
.sort-menu {
  position: fixed; display: flex; flex-direction: column; overflow-y: auto;
  padding: 6px; border-radius: 14px;
  background: var(--il-surface); border: 1px solid var(--il-border-strong);
  box-shadow: 0 18px 44px rgba(0,0,0,.45), 0 2px 6px rgba(0,0,0,.2);
  transform-origin: top right; overscroll-behavior: contain;
}
.sort-menu--up { transform-origin: bottom right; }
.sort-menu-title {
  margin: 4px 10px 6px; font-size: 10.5px; font-weight: 700;
  letter-spacing: .08em; text-transform: uppercase; color: var(--il-text-faint);
}
.sort-opt {
  position: relative; display: flex; align-items: center; gap: 10px;
  width: 100%; min-height: 40px; padding: 0 10px;
  border: none; border-radius: 9px; background: transparent;
  color: var(--il-text-secondary); font: inherit; font-size: 13px; font-weight: 600;
  text-align: left; cursor: pointer;
  transition: background-color .12s ease, color .12s ease;
}
/* thin divider between each pair (purchases / spent / date / name) */
.sort-opt--sep { margin-top: 5px; }
.sort-opt--sep::before {
  content: ''; position: absolute; left: 10px; right: 10px; top: -3px;
  height: 1px; background: var(--il-border);
}
.sort-opt-icon { width: 14px; text-align: center; font-size: 12px; color: var(--il-text-faint); flex-shrink: 0; }
.sort-opt:active, .sort-opt:focus-visible { background: var(--il-surface-raised); outline: none; }
.sort-opt--on { background: var(--il-accent-soft); color: var(--il-accent); }
.sort-opt--on .sort-opt-icon { color: var(--il-accent); }
.sort-opt-check { margin-left: auto; font-size: 12px; }

/* pop in from the Sort button */
.menu-enter-active, .menu-leave-active { transition: opacity .16s ease; }
.menu-enter-from, .menu-leave-to { opacity: 0; }
.menu-enter-active .sort-menu, .menu-leave-active .sort-menu { transition: transform .18s var(--il-ease); }
.menu-enter-from .sort-menu, .menu-leave-to .sort-menu { transform: scale(.95) translateY(-6px); }
.menu-enter-from .sort-menu--up, .menu-leave-to .sort-menu--up { transform: scale(.95) translateY(6px); }

/* The Sort button only exists on phones; never show the menu on wider screens */
@media (min-width: 701px) { .menu-backdrop { display: none; } }

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

/* ══════════════════════════════════════════════════════════
   RESPONSIVE
   --bleed-x / --bleed-y reclaim the shell's padding at every size,
   --il-pad-x is the page's own inner side padding. These are the SAME
   values the Product List and Transaction pages use at every breakpoint.
══════════════════════════════════════════════════════════ */

/* ── Large screens: more breathing room ── */
@media (min-width: 1500px) {
  .cust-page { --il-gap: 16px; --il-pad-x: 32px; padding-bottom: 48px; }
}

/* ── Wide screens: the cards + search stay pinned at the very top while the table scrolls.
   The block reaches the page edges on both sides (negative margin + matching padding),
   so no row can peek out beside it, and a soft shadow shows when it is pinned. ── */
@media (min-width: 1101px) {
  .sticky-controls {
    position: sticky; top: var(--sticky-top); z-index: 20;
    background: var(--c-bg, #0f172a);
    padding: 10px var(--il-pad-x); margin: -10px calc(-1 * var(--il-pad-x));
    transition: box-shadow .2s ease;
  }
  .sticky-controls.is-stuck {
    box-shadow: 0 1px 0 var(--il-border), 0 14px 18px -16px rgba(0, 0, 0, .55);
  }
  .toolbar-add { display: inline-flex; }
}

/* ── Tablet landscape ── */
@media (max-width: 1100px) {
  .cust-page { --bleed-x: 22px; --bleed-y: 24px; }
  .kpi-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

/* ── Tablet portrait ── */
@media (max-width: 900px) {
  .cust-page { --il-pad-x: 16px; --il-gap: 12px; padding-top: 8px; padding-bottom: 32px; }
}

/* ── Phone ──
   Goals (same as Product List / Transaction): edge-to-edge layout that uses the shell's top and
   side padding, big touch targets (≥ 40px), and customer cards instead of a wide table. */
@media (max-width: 700px) {
  .cust-page {
    --bleed-x: 16px; --bleed-y: 12px; --il-pad-x: 12px; --il-gap: 10px;
    padding-top: 12px;
    padding-bottom: calc(32px + env(safe-area-inset-bottom, 0px));
  }

  /* Header: title stays on one line, the Add button goes full width below it */
  .page-eyebrow, .page-title { font-size: 14px; }
  .page-header { flex-direction: column; align-items: stretch; gap: 10px; }
  .header-actions { flex-direction: column; align-items: stretch; }
  .header-actions .btn { min-height: 44px; }

  /* Summary cards: 2 × 2 grid, icon beside the text (same as the other pages) */
  .kpi-strip { gap: 8px; }
  .kpi-card { padding: 10px 11px; gap: 9px; }
  .kpi-icon { width: 32px; height: 32px; font-size: 13px; }
  .kpi-label { font-size: 10px; }
  .kpi-value { font-size: 19px; }
  .kpi-hint { font-size: 11px; }

  /* Toolbar: big tap targets */
  .toolbar { padding: 8px; }
  .toolbar-row { flex-direction: column; align-items: stretch; gap: 8px; }
  .search-wrap { flex-basis: auto; }
  .search-input { height: 44px; font-size: 16px; }   /* 16px stops iOS zoom */
  .search-kbd { display: none; }
  .toolbar-end { margin-left: 0; }
  .toolbar-end .btn, .tool-btn { flex: 1; height: 44px; }

  /* Directory card */
  .card { border-radius: 14px; }
  .card-head { padding: 14px 14px 12px; }

  /* Sort row */
  .mobile-tools {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 10px 14px; border-bottom: 1px solid var(--il-border);
    background: var(--il-surface-raised);
  }
  .mobile-sort { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: var(--il-text-faint); min-width: 0; flex: 1; justify-content: space-between; }
  .sort-trigger {
    flex: 1; max-width: 210px; min-width: 0; height: 40px; padding: 0 12px;
    display: inline-flex; align-items: center; justify-content: space-between; gap: 8px;
    border: 1.5px solid var(--il-border-strong); border-radius: 10px;
    background: var(--il-surface); color: var(--il-text-primary);
    font: inherit; font-size: 13px; font-weight: 600; cursor: pointer;
  }
  .sort-trigger i { font-size: 11px; color: var(--il-text-faint); transition: transform .2s ease; }
  .sort-trigger--open { border-color: var(--il-accent); box-shadow: 0 0 0 3px var(--il-accent-soft); }
  .sort-trigger--open i { transform: rotate(180deg); color: var(--il-accent); }
  .sort-trigger-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

  /* Table rows → customer cards */
  .table-wrap { overflow-x: visible; }
  .data-table, .data-table tbody { display: block; width: 100%; }
  .data-table thead { display: none; }

  .data-table tbody tr.row-click {
    position: relative; display: grid; width: 100%;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    grid-template-areas:
      "cust  cust"
      "vis   spent"
      "last  last";
    gap: 10px 12px; padding: 12px 14px;
    border-bottom: 1px solid var(--il-border);
  }
  .data-table tbody tr.row-click:last-child { border-bottom: none; }
  .data-table tbody tr.row-click td {
    display: block; min-width: 0; padding: 0; border-bottom: none;
    white-space: normal; overflow-wrap: anywhere;
  }
  .data-table tbody tr.row-click:hover td { background: transparent; }

  .td-cust  { grid-area: cust; padding-right: 52px !important; }
  .td-vis   { grid-area: vis; }
  .td-spent { grid-area: spent; }
  .td-last  { grid-area: last; }
  .td-actions { position: absolute; top: 10px; right: 10px; }
  .icon-btn { width: 40px; height: 40px; }

  .data-table tbody td[data-label]::before {
    content: attr(data-label);
    display: block; margin-bottom: 3px;
    font-size: 10.5px; font-weight: 700; letter-spacing: .07em;
    text-transform: uppercase; color: var(--il-text-faint);
  }

  /* Footer: stacked and centred, big pager buttons */
  .table-foot { flex-direction: column; align-items: stretch; text-align: center; gap: 8px; padding: 12px 14px; }
  .pager { justify-content: center; gap: 5px; }
  .pager-btn { min-width: 40px; height: 40px; }
  .pager-btn--nav { padding: 0 12px; }

  /* Modals: a bit more compact so they fit short screens */
  .modal-backdrop { padding: 12px; }
  .modal { padding: 18px 16px 16px; }
  .field-input { height: 46px; font-size: 16px; }   /* 16px stops iOS zoom */
  .btn-primary, .btn-ghost, .btn-danger { height: 46px; }
  .detail-stats { grid-template-columns: 1fr; }
  .toast { bottom: calc(16px + env(safe-area-inset-bottom, 0px)); max-width: calc(100vw - 24px); }
}

/* ── Very narrow phones ── */
@media (max-width: 380px) {
  .cust-page { --il-pad-x: 10px; }
  .kpi-strip { gap: 6px; }
  .kpi-card { padding: 9px; }
  .kpi-value { font-size: 17px; }
  .data-table tbody tr.row-click { padding: 12px 10px; }
  .pager-btn { min-width: 36px; padding: 0 8px; }
  .pager-nav-text { display: none; }   /* arrows only, so the page numbers fit */
}

@media (prefers-reduced-motion: reduce) {
  .cust-page *, .cust-page *::before, .cust-page *::after { transition: none !important; animation: none !important; }
}
</style>