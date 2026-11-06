<template>
  <Layout>
    <div class="sales-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Sales</p>
          <h1 class="page-title">Sales Receipts</h1>
        </div>
        <div class="header-actions">
          <button v-if="selectedSales.length > 0" @click="openPasswordModal" class="btn btn--danger">
            <i class="fa-solid fa-trash"></i> Delete selected
            <span class="selected-count">{{ selectedSales.length }}</span>
          </button>
          <RouterLink to="/point-of-sale" class="btn btn--primary">
            <i class="fa-solid fa-plus"></i> New Sale
          </RouterLink>
        </div>
      </div>

      <!-- ── TOOLBAR: SEARCH + PERIOD ── -->
      <div class="toolbar">
        <div class="search-field">
          <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
          <input
            ref="searchInput"
            v-model="search"
            type="search"
            class="search-input"
            placeholder="Search receipt no., customer, clerk, amount…"
            aria-label="Search sales receipts"
            autocomplete="off"
            @keydown.esc="search = ''"
          />
          <button v-if="search" class="search-clear" @click="clearSearch" aria-label="Clear search">
            <i class="fa-solid fa-xmark"></i>
          </button>
          <kbd v-else class="search-kbd" aria-hidden="true">/</kbd>
        </div>

        <div class="period-dropdown-wrap period-dropdown" :class="{ 'period-dropdown-wrap--muted': isSearching }">
          <button @click="togglePeriodDropdown" class="period-trigger" :aria-expanded="periodDropdown">
            <span class="period-trigger-label">View by</span>
            <span class="period-trigger-val">{{ selectedPeriodLabel }}</span>
            <i class="fa-solid fa-chevron-down period-caret" :class="{ 'period-caret--open': periodDropdown }"></i>
          </button>

          <transition name="dropdown-fade">
            <div v-if="periodDropdown" class="period-menu">
              <button
                v-for="p in periods"
                :key="p.key"
                @click="setPeriod(p.key)"
                class="period-menu-item"
                :class="{ 'period-menu-item--active': selectedPeriod === p.key }"
              >
                <i class="fa-solid fa-check period-check" v-if="selectedPeriod === p.key"></i>
                <span :style="{ marginLeft: selectedPeriod === p.key ? '0' : '20px' }">{{ p.label }}</span>
              </button>
            </div>
          </transition>
        </div>
      </div>

      <!-- Search context: tells the user the period is ignored while searching -->
      <p v-if="isSearching" class="search-note" role="status">
        <i class="fa-solid fa-circle-info"></i>
        Searching all dates —
        <strong>{{ filteredSales.length }}</strong> match{{ filteredSales.length === 1 ? '' : 'es' }}
        for “{{ search.trim() }}”.
        <button class="link-btn" @click="clearSearch">Clear search</button>
      </p>

      <!-- ── SUMMARY STRIP ── -->
      <div v-if="!loading && !loadError" class="stats">
        <div class="stat">
          <span class="stat-label">Receipts</span>
          <strong class="stat-val">{{ filteredSales.length }}</strong>
        </div>
        <div class="stat">
          <span class="stat-label">Total sales</span>
          <strong class="stat-val">₱{{ formatAmount(stats.total) }}</strong>
        </div>
        <div class="stat">
          <span class="stat-label">Average sale</span>
          <strong class="stat-val">₱{{ formatAmount(stats.average) }}</strong>
        </div>
        <span class="stat-scope">{{ isSearching ? 'All dates' : selectedPeriodLabel }}</span>
      </div>

      <!-- ── TABLE CARD ── -->
      <div class="table-card">

        <!-- Loading -->
        <div v-if="loading" class="state-block" aria-busy="true">
          <div class="skel-row" v-for="n in 6" :key="n"></div>
        </div>

        <!-- Error -->
        <div v-else-if="loadError" class="state-block state-block--center" role="alert">
          <i class="fa-solid fa-triangle-exclamation state-icon state-icon--error"></i>
          <p class="state-title">{{ loadError }}</p>
          <button class="btn btn--ghost" @click="loadSales">Try again</button>
        </div>

        <!-- Table -->
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="th-checkbox">
                  <label class="checkbox-wrap" :class="{ 'checkbox-wrap--partial': somePageSelected }">
                    <input
                      type="checkbox"
                      :checked="allPageSelected"
                      :disabled="!pagedSales.length"
                      aria-label="Select all receipts on this page"
                      @change="toggleSelectAll"
                    />
                    <span class="checkbox-box">
                      <i class="fa-solid" :class="somePageSelected ? 'fa-minus' : 'fa-check'"></i>
                    </span>
                  </label>
                </th>
                <th><button class="sort-btn" @click="setSort('date')">Date <i :class="sortIcon('date')"></i></button></th>
                <th>Receipt no.</th>
                <th><button class="sort-btn" @click="setSort('customer')">Customer <i :class="sortIcon('customer')"></i></button></th>
                <th class="th-right"><button class="sort-btn sort-btn--right" @click="setSort('amount')">Amount <i :class="sortIcon('amount')"></i></button></th>
                <th>Payment</th>
                <th>Status</th>
                <th>Clerk</th>
                <th class="th-go"><span class="sr-only">Open</span></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="sale in pagedSales"
                :key="sale.SaleID"
                :data-sale-row="sale.SaleID"
                class="row-click"
                :class="{ 'row--selected': selectedSales.includes(sale.SaleID), 'row-highlight': highlightedSaleId === sale.SaleID }"
                @click="onRowClick($event, sale)"
              >
                <td class="th-checkbox">
                  <label class="checkbox-wrap">
                    <input
                      type="checkbox"
                      :checked="selectedSales.includes(sale.SaleID)"
                      :aria-label="`Select ${receiptNo(sale)}`"
                      @change="toggleOne(sale.SaleID)"
                    />
                    <span class="checkbox-box"><i class="fa-solid fa-check"></i></span>
                  </label>
                </td>
                <td class="cell-muted">{{ sale.formatted_date }}</td>
                <td>
                  <RouterLink :to="`/sales/${sale.SaleID}`" class="receipt-link">
                    <template v-for="(s, i) in segments(receiptNo(sale))" :key="i"><mark v-if="s.m">{{ s.t }}</mark><template v-else>{{ s.t }}</template></template>
                  </RouterLink>
                </td>
                <td class="cell-secondary">
                  <template v-for="(s, i) in segments(sale.CustomerName)" :key="i"><mark v-if="s.m">{{ s.t }}</mark><template v-else>{{ s.t }}</template></template>
                </td>
                <td class="cell-amount">₱{{ formatAmount(sale.TotalAmount) }}</td>
                <td class="cell-muted">{{ sale.PaymentMethod }}</td>
                <td>
                  <span class="status-pill" :class="sale.Status === 'Paid' ? 'status-pill--paid' : 'status-pill--void'">
                    {{ sale.Status }}
                  </span>
                </td>
                <td class="cell-muted">
                  <template v-for="(s, i) in segments(sale.ClerkName)" :key="i"><mark v-if="s.m">{{ s.t }}</mark><template v-else>{{ s.t }}</template></template>
                </td>
                <td class="th-go"><i class="fa-solid fa-chevron-right row-go" aria-hidden="true"></i></td>
              </tr>

              <tr v-if="filteredSales.length === 0">
                <td colspan="9" class="td-empty">
                  <div class="empty-state">
                    <i :class="isSearching ? 'fa-solid fa-magnifying-glass' : 'fa-solid fa-receipt'"></i>
                    <template v-if="isSearching">
                      <p>No receipts match “{{ search.trim() }}”</p>
                      <span>Try a receipt number like SR-00085, a customer name, or an amount.</span>
                      <button class="btn btn--ghost btn--sm" @click="clearSearch">Clear search</button>
                    </template>
                    <template v-else>
                      <p>No sales found for {{ selectedPeriodLabel.toLowerCase() }}</p>
                      <span>Pick a longer period, or search to find a receipt from any date.</span>
                      <button v-if="selectedPeriod !== 'all'" class="btn btn--ghost btn--sm" @click="setPeriod('all')">Show all time</button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="!loading && !loadError && filteredSales.length > 0" class="pager">
          <span class="pager-info">
            Showing <strong>{{ rangeStart }}–{{ rangeEnd }}</strong> of <strong>{{ filteredSales.length }}</strong>
          </span>
          <div class="pager-controls">
            <label class="pager-size">
              Rows
              <select v-model.number="pageSize" aria-label="Rows per page">
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
            </label>
            <button class="pager-btn" :disabled="page <= 1" @click="page = 1" aria-label="First page"><i class="fa-solid fa-angles-left"></i></button>
            <button class="pager-btn" :disabled="page <= 1" @click="page--" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button>
            <span class="pager-page">Page {{ page }} of {{ totalPages }}</span>
            <button class="pager-btn" :disabled="page >= totalPages" @click="page++" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button>
            <button class="pager-btn" :disabled="page >= totalPages" @click="page = totalPages" aria-label="Last page"><i class="fa-solid fa-angles-right"></i></button>
          </div>
        </div>
      </div>

    </div>

    <!-- ── ADMIN PASSWORD MODAL ── -->
    <transition name="modal-fade">
      <div v-if="passwordModal" class="modal-backdrop" @click.self="closePasswordModal">
        <div class="del-modal">
          <div class="del-modal-icon-ring">
            <div class="del-modal-icon"><i class="fa-solid fa-shield-halved"></i></div>
          </div>
          <div class="del-modal-body">
            <h3 class="del-modal-title">Admin Verification Required</h3>
            <p class="del-modal-desc">
              You're about to delete <strong>{{ selectedSales.length }}</strong> sale receipt{{ selectedSales.length !== 1 ? 's' : '' }}.
              Stock for the items sold will be returned to inventory. Enter your admin password to confirm.
            </p>

            <div class="pw-field">
              <label class="pw-label">Admin Password</label>
              <div class="input-wrap">
                <i class="fa-solid fa-lock input-icon"></i>
                <input
                  :type="showPw ? 'text' : 'password'"
                  v-model="adminPassword"
                  class="pw-input"
                  placeholder="Enter your password"
                  @keyup.enter="confirmDelete"
                />
                <button type="button" class="pw-toggle" @click="showPw = !showPw">
                  <i :class="showPw ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                </button>
              </div>
              <p v-if="deleteError" class="pw-error"><i class="fa-solid fa-circle-exclamation"></i> {{ deleteError }}</p>
            </div>
          </div>
          <div class="del-modal-footer">
            <button class="del-modal-btn del-modal-btn--cancel" @click="closePasswordModal" :disabled="deleting">Cancel</button>
            <button class="del-modal-btn del-modal-btn--confirm" @click="confirmDelete" :disabled="deleting || !adminPassword">
              <i v-if="deleting" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-trash-can"></i>
              {{ deleting ? 'Deleting…' : 'Confirm Delete' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── SUCCESS CONFIRMATION MODAL ── -->
    <transition name="modal-fade">
      <div v-if="successModal" class="modal-backdrop" @click.self="closeSuccessModal">
        <div class="success-modal">
          <div class="success-modal-icon-ring">
            <div class="success-modal-icon">
              <i class="fa-solid fa-circle-check"></i>
            </div>
          </div>
          <div class="success-modal-body">
            <h3 class="success-modal-title">Successfully Deleted</h3>
            <p class="success-modal-desc">
              <strong>{{ deletedCount }}</strong>
              sale receipt{{ deletedCount !== 1 ? 's have' : ' has' }} been permanently deleted.
              This action cannot be undone.
            </p>

            <div class="success-summary-chip">
              <div class="success-summary-icon">
                <i class="fa-solid fa-trash-can"></i>
              </div>
              <div class="success-summary-info">
                <p class="success-summary-label">Deleted Receipts</p>
                <p class="success-summary-val">{{ deletedCount }} record{{ deletedCount !== 1 ? 's' : '' }} removed</p>
              </div>
              <span class="success-summary-badge">Done</span>
            </div>
          </div>
          <div class="success-modal-footer">
            <button class="success-modal-btn" @click="closeSuccessModal">
              <i class="fa-solid fa-check"></i> Got it
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Layout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const route  = useRoute()
const router = useRouter()

/* ────────────────────────────────────────────────────────────
   HELPERS
──────────────────────────────────────────────────────────── */
const formatAmount = v =>
  Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const receiptNo = s => `SR-${String(s.SaleID).padStart(5, '0')}`

/* ────────────────────────────────────────────────────────────
   LOAD
──────────────────────────────────────────────────────────── */
const sales     = ref([])
const loading   = ref(true)
const loadError = ref('')

async function loadSales() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await api.get('/api/sales')
    sales.value = res.data.map(s => {
      const mapped = {
        ...s,
        formatted_date: s.SaleDate,
        CustomerName: s.CustomerName || 'Unknown',
        ClerkName: s.ClerkName || 'Unknown',
        _date: new Date(s.SaleDate),
      }
      // One lowercase string per receipt, built once, so typing in the
      // search box stays fast even with thousands of receipts.
      mapped._hay = [
        receiptNo(mapped), String(mapped.SaleID), mapped.CustomerName, mapped.ClerkName,
        mapped.PaymentMethod, mapped.Status, mapped.formatted_date,
        String(Number(mapped.TotalAmount)), formatAmount(mapped.TotalAmount),
      ].join(' ').toLowerCase()
      return mapped
    })
  } catch (err) {
    console.error('Failed to load sales:', err)
    loadError.value = 'We couldn’t load the sales receipts. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

/* ────────────────────────────────────────────────────────────
   PERIOD
──────────────────────────────────────────────────────────── */
const periods = [
  { key: 'today',   label: 'Today' },
  { key: 'week',    label: 'This week' },
  { key: 'month',   label: 'This month' },
  { key: 'quarter', label: 'This quarter' },
  { key: 'year',    label: 'This year' },
  { key: 'all',     label: 'All time' },
]
const selectedPeriod = ref('month')
const periodDropdown = ref(false)
const selectedPeriodLabel = computed(() => periods.find(p => p.key === selectedPeriod.value)?.label)

function togglePeriodDropdown() { periodDropdown.value = !periodDropdown.value }
function setPeriod(p) { selectedPeriod.value = p; periodDropdown.value = false }

function inPeriod(d, key, now) {
  if (key === 'all') return true
  if (key === 'today') return d.toDateString() === now.toDateString()
  if (key === 'week') {
    const start = new Date(now); start.setDate(now.getDate() - now.getDay()); start.setHours(0, 0, 0, 0)
    return d >= start
  }
  if (key === 'month')   return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear()
  if (key === 'quarter') return Math.floor(d.getMonth() / 3) === Math.floor(now.getMonth() / 3) && d.getFullYear() === now.getFullYear()
  if (key === 'year')    return d.getFullYear() === now.getFullYear()
  return true
}

/* Smallest period that still contains a given date. */
function periodContaining(date) {
  const now = new Date()
  for (const key of ['today', 'week', 'month', 'quarter', 'year']) {
    if (inPeriod(date, key, now)) return key
  }
  return 'all'
}

/* ────────────────────────────────────────────────────────────
   SEARCH
   - Every word must match somewhere on the receipt (receipt no.,
     customer, clerk, payment, status, date, amount).
   - "sr-85", "85" and "00085" all find SR-00085.
   - While searching, the period filter is ignored so an older
     receipt can always be found.
──────────────────────────────────────────────────────────── */
const search = ref('')
const searchInput = ref(null)

const tokens = computed(() =>
  search.value.trim().toLowerCase().split(/\s+/).map(t => t.replace(/^₱/, '')).filter(Boolean)
)
const isSearching = computed(() => tokens.value.length > 0)

function matchToken(s, t) {
  if (s._hay.includes(t)) return true
  const m = t.match(/^sr-?0*(\d+)$/)
  return !!m && String(s.SaleID) === m[1]
}

/* Splits text into [{ t, m }] so matches can be wrapped in <mark>. */
function segments(text) {
  const str = String(text ?? '')
  if (!tokens.value.length) return [{ t: str, m: false }]
  const lower = str.toLowerCase()
  const ranges = []
  for (const t of tokens.value) {
    let i = 0
    while ((i = lower.indexOf(t, i)) !== -1) { ranges.push([i, i + t.length]); i += t.length }
  }
  if (!ranges.length) return [{ t: str, m: false }]
  ranges.sort((a, b) => a[0] - b[0])
  const merged = [ranges[0].slice()]
  for (const [a, b] of ranges.slice(1)) {
    const last = merged[merged.length - 1]
    if (a <= last[1]) last[1] = Math.max(last[1], b)
    else merged.push([a, b])
  }
  const out = []
  let pos = 0
  for (const [a, b] of merged) {
    if (a > pos) out.push({ t: str.slice(pos, a), m: false })
    out.push({ t: str.slice(a, b), m: true })
    pos = b
  }
  if (pos < str.length) out.push({ t: str.slice(pos), m: false })
  return out
}

function clearSearch() { search.value = ''; searchInput.value?.focus() }

/* "/" jumps to the search box from anywhere (unless typing in a field). */
function onGlobalKey(e) {
  if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return
  const tag = document.activeElement?.tagName
  if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return
  e.preventDefault()
  searchInput.value?.focus()
}

/* ────────────────────────────────────────────────────────────
   FILTER + SORT + PAGE
──────────────────────────────────────────────────────────── */
const filteredSales = computed(() => {
  const now = new Date()
  if (isSearching.value) {
    return sales.value.filter(s => tokens.value.every(t => matchToken(s, t)))
  }
  return sales.value.filter(s => inPeriod(s._date, selectedPeriod.value, now))
})

const sortKey = ref('date')
const sortDir = ref('desc')

function setSort(key) {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = key === 'customer' ? 'asc' : 'desc' }
}
function sortIcon(key) {
  if (sortKey.value !== key) return 'fa-solid fa-sort sort-icon'
  return sortDir.value === 'asc' ? 'fa-solid fa-sort-up sort-icon sort-icon--on' : 'fa-solid fa-sort-down sort-icon sort-icon--on'
}

const sortedSales = computed(() => {
  const dir = sortDir.value === 'asc' ? 1 : -1
  const list = filteredSales.value.slice()
  list.sort((a, b) => {
    if (sortKey.value === 'amount')   return (Number(a.TotalAmount) - Number(b.TotalAmount)) * dir
    if (sortKey.value === 'customer') return String(a.CustomerName).localeCompare(String(b.CustomerName)) * dir
    return (a.SaleID - b.SaleID) * dir // date: newest receipt = highest id
  })
  return list
})

const page     = ref(1)
const pageSize = ref(25)
const totalPages = computed(() => Math.max(1, Math.ceil(sortedSales.value.length / pageSize.value)))
const pagedSales = computed(() => {
  const start = (page.value - 1) * pageSize.value
  return sortedSales.value.slice(start, start + pageSize.value)
})
const rangeStart = computed(() => (sortedSales.value.length ? (page.value - 1) * pageSize.value + 1 : 0))
const rangeEnd   = computed(() => Math.min(page.value * pageSize.value, sortedSales.value.length))

const stats = computed(() => {
  const paid = filteredSales.value.filter(s => s.Status === 'Paid')
  const total = paid.reduce((sum, s) => sum + Number(s.TotalAmount || 0), 0)
  return { total, average: paid.length ? total / paid.length : 0 }
})

/* Back to page 1 whenever the result set changes. */
watch([search, selectedPeriod, sortKey, sortDir, pageSize], () => { page.value = 1 })

/* ────────────────────────────────────────────────────────────
   SELECTION (applies to the rows on the current page only,
   so "select all" can never grab receipts you can't see)
──────────────────────────────────────────────────────────── */
const selectedSales = ref([])
const allPageSelected = computed(() =>
  pagedSales.value.length > 0 && pagedSales.value.every(s => selectedSales.value.includes(s.SaleID))
)
const somePageSelected = computed(() =>
  !allPageSelected.value && pagedSales.value.some(s => selectedSales.value.includes(s.SaleID))
)
function toggleSelectAll() {
  selectedSales.value = allPageSelected.value ? [] : pagedSales.value.map(s => s.SaleID)
}
function toggleOne(id) {
  selectedSales.value = selectedSales.value.includes(id)
    ? selectedSales.value.filter(x => x !== id)
    : [...selectedSales.value, id]
}
watch([search, selectedPeriod, sortKey, sortDir, pageSize, page], () => { selectedSales.value = [] })

/* Click anywhere on a row to open it (checkbox / link clicks keep their own behavior). */
function onRowClick(e, sale) {
  if (e.target.closest('a, label, input, button')) return
  router.push(`/sales/${sale.SaleID}`)
}

/* ────────────────────────────────────────────────────────────
   HIGHLIGHT (arriving from a "New Sale" notification)
──────────────────────────────────────────────────────────── */
const highlightedSaleId = ref(null)
let highlightTimer = null

async function applyHighlightFromQuery() {
  const saleId = route.query.highlightSale
  if (!saleId) return
  const match = sales.value.find(s => String(s.SaleID) === String(saleId))
  if (!match) return

  search.value = ''
  selectedPeriod.value = periodContaining(match._date)
  await nextTick() // let the filter watchers settle before jumping to the right page

  const idx = sortedSales.value.findIndex(s => s.SaleID === match.SaleID)
  if (idx >= 0) page.value = Math.floor(idx / pageSize.value) + 1
  highlightedSaleId.value = match.SaleID

  await nextTick()
  document.querySelector(`[data-sale-row="${match.SaleID}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' })

  clearTimeout(highlightTimer)
  highlightTimer = setTimeout(() => { highlightedSaleId.value = null }, 3000)
}

/* ────────────────────────────────────────────────────────────
   LIFECYCLE
──────────────────────────────────────────────────────────── */
function handleClickOutside(e) {
  if (!e.target.closest('.period-dropdown')) periodDropdown.value = false
}
onMounted(async () => {
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('keydown', onGlobalKey)
  await loadSales()
  applyHighlightFromQuery()
})
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('keydown', onGlobalKey)
  clearTimeout(highlightTimer)
})

/* ────────────────────────────────────────────────────────────
   DELETE
──────────────────────────────────────────────────────────── */
const passwordModal = ref(false)
const adminPassword = ref('')
const showPw        = ref(false)
const deleting      = ref(false)
const deleteError   = ref('')
const successModal  = ref(false)
const deletedCount  = ref(0)

function openPasswordModal() {
  if (!selectedSales.value.length) return
  deleteError.value = ''
  passwordModal.value = true
}
function closePasswordModal() {
  if (deleting.value) return
  passwordModal.value = false
  adminPassword.value = ''
  showPw.value = false
  deleteError.value = ''
}
function closeSuccessModal() { successModal.value = false }

async function confirmDelete() {
  if (!adminPassword.value || deleting.value) return
  deleting.value = true
  deleteError.value = ''
  try {
    const count = selectedSales.value.length
    await api.post('/api/sales/bulk-delete', {
      saleIds: selectedSales.value,
      admin_password: adminPassword.value,
    })

    // Capture the count and close the password modal BEFORE clearing the
    // selection, so the modal never flashes "0 sale receipts".
    deletedCount.value = count
    passwordModal.value = false
    adminPassword.value = ''
    showPw.value = false
    deleteError.value = ''
    await nextTick()

    selectedSales.value = []
    await loadSales()
    if (page.value > totalPages.value) page.value = totalPages.value
    successModal.value = true
  } catch (err) {
    deleteError.value = err?.response?.data?.message || 'Incorrect password or request failed.'
  } finally {
    deleting.value = false
  }
}
</script>

<style scoped>
/* ── TOKEN BRIDGE ── */
.sales-page {
  --sp-bg:             var(--c-bg);
  --sp-surface:        var(--c-surface);
  --sp-surface-raised: var(--c-surface-raised);
  --sp-surface-sunken: var(--c-surface-sunken);
  --sp-border:         var(--c-border);
  --sp-border-strong:  var(--c-border-strong);
  --sp-text-primary:   var(--c-text-primary);
  --sp-text-secondary: var(--c-text-secondary);
  --sp-text-muted:     var(--c-text-muted);
  --sp-text-faint:     var(--c-text-faint);
  --sp-accent:         var(--c-accent);
  --sp-accent-soft:    var(--c-accent-soft);
  --sp-accent-border:  var(--c-accent-border);
  --sp-ring:           var(--c-accent-ring);
  --sp-green:          #10b981;
  --sp-red:            #f43f5e;
  --radius: 16px;

  min-height: 100%;
  background: var(--sp-bg);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 16px;
  transition: background-color .22s;
}
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

/* PAGE HEADER */
.page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--sp-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--sp-text-primary); letter-spacing: -.03em; margin: 0; transition: color .22s; }
.header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

/* BUTTONS */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn:focus-visible { outline: 2px solid var(--sp-accent); outline-offset: 2px; }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }
.btn--primary { background: linear-gradient(135deg, var(--sp-accent), var(--c-accent-deep)); color: #fff; box-shadow: var(--c-shadow-accent); }
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--ghost { background: var(--sp-surface); border-color: var(--sp-border-strong); color: var(--sp-text-secondary); }
.btn--ghost:hover { border-color: var(--sp-accent-border); color: var(--sp-accent); }
.btn--danger { background: rgba(244,63,94,.10); border-color: rgba(244,63,94,.30); color: var(--sp-red); }
.btn--danger:hover { background: rgba(244,63,94,.16); border-color: rgba(244,63,94,.45); }
html[data-theme="dark"] .btn--danger { background: rgba(244,63,94,.14); color: #F87171; border-color: rgba(244,63,94,.35); }
html[data-theme="dark"] .btn--danger:hover { background: rgba(244,63,94,.20); }
.selected-count { background: var(--sp-red); color: #fff; font-size: 11px; font-weight: 700; border-radius: 20px; padding: 1px 7px; margin-left: 2px; }

/* TOOLBAR */
.toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.search-field { position: relative; flex: 1 1 320px; display: flex; align-items: center; }
.search-icon { position: absolute; left: 14px; font-size: 13px; color: var(--sp-text-faint); pointer-events: none; }
.search-input {
  width: 100%; height: 42px; padding: 0 44px 0 40px;
  border: 1.5px solid var(--sp-border-strong); border-radius: 10px;
  background: var(--sp-surface); color: var(--sp-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
  -webkit-appearance: none; appearance: none;
}
.search-input::-webkit-search-cancel-button { display: none; }
.search-input:focus { border-color: var(--sp-accent); box-shadow: 0 0 0 3px var(--sp-ring); }
.search-input::placeholder { color: var(--sp-text-faint); }
.search-clear {
  position: absolute; right: 8px; width: 28px; height: 28px; border: none; border-radius: 7px;
  background: none; color: var(--sp-text-faint); cursor: pointer; font-size: 12px;
  display: flex; align-items: center; justify-content: center; transition: background .15s, color .15s;
}
.search-clear:hover { background: var(--sp-surface-raised); color: var(--sp-text-primary); }
.search-kbd {
  position: absolute; right: 12px; min-width: 22px; height: 22px; padding: 0 6px;
  display: flex; align-items: center; justify-content: center;
  font: 600 11px 'Inter', system-ui, sans-serif; color: var(--sp-text-faint);
  border: 1px solid var(--sp-border-strong); border-radius: 6px; pointer-events: none;
}
.search-note {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 0;
  font-size: 13px; color: var(--sp-text-muted);
}
.search-note i { color: var(--sp-accent); }
.search-note strong { color: var(--sp-text-primary); }
.link-btn { background: none; border: none; padding: 0; font: inherit; font-weight: 600; color: var(--sp-accent); cursor: pointer; }
.link-btn:hover { text-decoration: underline; }

/* PERIOD DROPDOWN */
.period-dropdown-wrap { position: relative; display: inline-block; transition: opacity .18s; }
.period-dropdown-wrap--muted { opacity: .55; }
.period-trigger {
  display: inline-flex; align-items: center; gap: 8px;
  height: 42px; padding: 0 16px;
  border: 1.5px solid var(--sp-border-strong); border-radius: 10px;
  background: var(--sp-surface); color: var(--sp-text-secondary);
  font-size: 13px; font-family: inherit; cursor: pointer;
  transition: border-color .18s, background-color .22s, color .22s;
}
.period-trigger:hover { border-color: var(--sp-accent-border); }
.period-trigger:focus-visible { outline: 2px solid var(--sp-accent); outline-offset: 2px; }
.period-trigger-label { font-weight: 600; color: var(--sp-text-faint); font-size: 12px; }
.period-trigger-val   { font-weight: 600; color: var(--sp-text-primary); transition: color .22s; }
.period-caret { font-size: 10px; color: var(--sp-text-faint); transition: transform .2s; }
.period-caret--open { transform: rotate(180deg); }
.period-menu {
  position: absolute; top: calc(100% + 8px); right: 0;
  width: 200px; background: var(--sp-surface); border: 1px solid var(--sp-border);
  border-radius: 13px; box-shadow: var(--c-shadow-lg); z-index: 100; padding: 5px; overflow: hidden;
}
.period-menu-item {
  display: flex; align-items: center; width: 100%;
  padding: 9px 10px; font-size: 13px; font-weight: 500;
  color: var(--sp-text-secondary); background: none; border: none; border-radius: 8px;
  cursor: pointer; text-align: left; font-family: inherit;
  transition: background-color .15s, color .15s;
}
.period-menu-item:hover { background: var(--sp-surface-raised); color: var(--sp-text-primary); }
.period-menu-item--active { color: var(--sp-accent); font-weight: 700; background: var(--sp-accent-soft); }
.period-check { font-size: 10px; margin-right: 8px; color: var(--sp-accent); }
.dropdown-fade-enter-active, .dropdown-fade-leave-active { transition: opacity .15s ease, transform .15s ease; }
.dropdown-fade-enter-from, .dropdown-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* SUMMARY STRIP */
.stats {
  display: flex; align-items: stretch; flex-wrap: wrap;
  background: var(--sp-surface); border: 1px solid var(--sp-border); border-radius: 12px;
  transition: background-color .22s, border-color .22s;
}
.stat { display: flex; flex-direction: column; gap: 3px; padding: 12px 22px; border-right: 1px solid var(--sp-border); }
.stat-label { font-size: 12px; color: var(--sp-text-muted); }
.stat-val { font-size: 18px; font-weight: 800; letter-spacing: -.02em; color: var(--sp-text-primary); font-variant-numeric: tabular-nums; }
.stat-scope { margin-left: auto; align-self: center; padding: 0 20px; font-size: 12px; font-weight: 600; color: var(--sp-text-faint); }

/* TABLE CARD */
.table-card { background: var(--sp-surface); border: 1px solid var(--sp-border); border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden; transition: background-color .22s, border-color .22s; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.data-table thead th {
  background: var(--sp-surface-raised); color: var(--sp-text-faint);
  font-size: 12px; font-weight: 600;
  text-align: left; padding: 11px 16px; border-bottom: 1px solid var(--sp-border);
  white-space: nowrap; transition: background-color .22s, color .22s, border-color .22s;
}
.th-right { text-align: right !important; }
.th-checkbox { width: 44px; }
.th-go { width: 36px; padding-left: 0 !important; text-align: right; }

.sort-btn {
  display: inline-flex; align-items: center; gap: 6px; padding: 0; background: none; border: none;
  font: inherit; color: inherit; cursor: pointer; transition: color .15s;
}
.sort-btn--right { flex-direction: row-reverse; }
.sort-btn:hover { color: var(--sp-text-primary); }
.sort-btn:focus-visible { outline: 2px solid var(--sp-accent); outline-offset: 3px; border-radius: 4px; }
.sort-icon { font-size: 10px; opacity: .5; }
.sort-icon--on { opacity: 1; color: var(--sp-accent); }

.data-table tbody tr { transition: background-color .15s; }
.data-table tbody tr:hover td { background: var(--sp-surface-raised); }
.data-table tbody tr.row--selected td { background: var(--sp-accent-soft); }
.data-table tbody td { padding: 13px 16px; border-bottom: 1px solid var(--sp-border); white-space: nowrap; transition: background-color .15s, border-color .22s; }
.data-table tbody tr:last-child td { border-bottom: none; }
.row-click { cursor: pointer; }
.row-go { font-size: 11px; color: var(--sp-text-faint); transition: color .15s, transform .15s; }
.row-click:hover .row-go { color: var(--sp-accent); transform: translateX(2px); }

/* ROW HIGHLIGHT (arriving from a notification) */
.row-highlight td { background: var(--sp-accent-soft) !important; animation: sale-row-pulse 1.6s ease-in-out 1; }
@keyframes sale-row-pulse {
  0%   { background: var(--sp-accent-soft); }
  50%  { background: rgba(16,185,129,.18); }
  100% { background: var(--sp-accent-soft); }
}
html[data-theme="dark"] .row-highlight td { background: rgba(16,185,129,.14) !important; }

/* SEARCH MATCH */
.data-table mark {
  background: var(--sp-accent-soft); color: var(--sp-accent);
  border-radius: 3px; padding: 0 2px; font-weight: 700;
}

/* CHECKBOX */
.checkbox-wrap { position: relative; display: inline-flex; cursor: pointer; }
.checkbox-wrap input { position: absolute; opacity: 0; width: 18px; height: 18px; cursor: pointer; margin: 0; }
.checkbox-box {
  width: 18px; height: 18px; border-radius: 5px;
  border: 1.5px solid var(--sp-border-strong); background: var(--sp-surface);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: transparent;
  transition: background-color .15s, border-color .15s, color .15s;
}
.checkbox-wrap input:checked + .checkbox-box,
.checkbox-wrap--partial .checkbox-box { background: var(--sp-accent); border-color: var(--sp-accent); color: #fff; }
.checkbox-wrap input:focus-visible + .checkbox-box { box-shadow: 0 0 0 3px var(--sp-ring); }

/* CELLS */
.cell-muted     { color: var(--sp-text-muted); }
.cell-secondary { color: var(--sp-text-secondary); }
.cell-amount    { font-weight: 700; color: var(--sp-text-primary); text-align: right; font-variant-numeric: tabular-nums; }
.receipt-link { font-weight: 700; color: var(--sp-accent); text-decoration: none; font-variant-numeric: tabular-nums; }
.receipt-link:hover { text-decoration: underline; }
.receipt-link:focus-visible { outline: 2px solid var(--sp-accent); outline-offset: 2px; border-radius: 3px; }

.status-pill { display: inline-flex; font-size: 11px; font-weight: 700; border-radius: 20px; padding: 3px 11px; letter-spacing: .03em; }
.status-pill--paid { background: rgba(16,185,129,.12); color: var(--sp-green); }
.status-pill--void { background: rgba(244,63,94,.12);  color: var(--sp-red); }
html[data-theme="dark"] .status-pill--paid { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .status-pill--void { background: rgba(244,63,94,.18);  color: #F87171; }

/* EMPTY / LOADING / ERROR */
.td-empty { padding: 56px 24px !important; text-align: center; border-bottom: none !important; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--sp-text-faint); white-space: normal; }
.empty-state i { font-size: 32px; margin-bottom: 4px; }
.empty-state p { font-size: 14px; font-weight: 600; color: var(--sp-text-secondary); margin: 0; }
.empty-state span { font-size: 13px; max-width: 360px; line-height: 1.5; }
.empty-state .btn { margin-top: 8px; }

.state-block { padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; }
.state-block--center { align-items: center; padding: 56px 20px; text-align: center; }
.state-icon { font-size: 30px; }
.state-icon--error { color: var(--sp-red); }
.state-title { margin: 0; font-size: 14px; color: var(--sp-text-muted); max-width: 420px; line-height: 1.5; }
.skel-row {
  height: 40px; border-radius: 8px;
  background: linear-gradient(90deg, var(--sp-surface-raised) 25%, var(--sp-border) 50%, var(--sp-surface-raised) 75%);
  background-size: 200% 100%; animation: skel-shimmer 1.4s linear infinite;
}
@keyframes skel-shimmer { to { background-position: -200% 0; } }

/* PAGINATION */
.pager {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  padding: 12px 16px; border-top: 1px solid var(--sp-border); font-size: 13px; color: var(--sp-text-muted);
}
.pager-info strong { color: var(--sp-text-primary); }
.pager-controls { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pager-size { display: inline-flex; align-items: center; gap: 8px; margin-right: 8px; }
.pager-size select {
  height: 32px; padding: 0 8px; border: 1.5px solid var(--sp-border-strong); border-radius: 8px;
  background: var(--sp-surface); color: var(--sp-text-secondary); font: inherit; cursor: pointer;
}
.pager-page { padding: 0 6px; font-weight: 600; color: var(--sp-text-secondary); }
.pager-btn {
  width: 32px; height: 32px; border: 1.5px solid var(--sp-border-strong); border-radius: 8px;
  background: var(--sp-surface); color: var(--sp-text-secondary); cursor: pointer; font-size: 11px;
  display: flex; align-items: center; justify-content: center;
  transition: border-color .15s, color .15s, background .15s;
}
.pager-btn:hover:not(:disabled) { border-color: var(--sp-accent-border); color: var(--sp-accent); background: var(--sp-accent-soft); }
.pager-btn:disabled { opacity: .4; cursor: not-allowed; }
.pager-btn:focus-visible, .pager-size select:focus-visible { outline: 2px solid var(--sp-accent); outline-offset: 2px; }

/* SUCCESS CONFIRMATION MODAL */
.success-modal {
  width: 100%; max-width: 420px;
  background: #FFFFFF; border: 1px solid #E5E7EB;
  border-radius: 22px; box-shadow: 0 24px 64px rgba(0,0,0,.35); overflow: hidden;
  display: flex; flex-direction: column;
}
html[data-theme="dark"] .success-modal { background: #1E2130; border-color: #2A2D3E; box-shadow: 0 24px 64px rgba(0,0,0,.6); }
.success-modal::before { content: ''; display: block; height: 4px; background: linear-gradient(90deg, #10b981, #34d399); }
.success-modal-icon-ring { display: flex; align-items: center; justify-content: center; padding: 28px 0 0; }
.success-modal-icon {
  width: 64px; height: 64px; border-radius: 50%;
  background: rgba(16,185,129,.12); border: 2px solid rgba(16,185,129,.30);
  display: flex; align-items: center; justify-content: center;
  font-size: 26px; color: #10b981; box-shadow: 0 0 0 10px rgba(16,185,129,.06);
  animation: success-pop .4s cubic-bezier(.34,1.56,.64,1) both;
}
html[data-theme="dark"] .success-modal-icon { background: rgba(16,185,129,.16); color: #4ADE80; box-shadow: 0 0 0 10px rgba(16,185,129,.08); }
@keyframes success-pop { from { transform: scale(.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.success-modal-body { padding: 18px 26px 22px; display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center; }
.success-modal-title { font-size: 19px; font-weight: 800; color: var(--sp-text-primary); letter-spacing: -.02em; margin: 0; }
.success-modal-desc { font-size: 13.5px; line-height: 1.65; color: var(--sp-text-muted); margin: 0; }
.success-modal-desc strong { color: var(--sp-text-primary); }
.success-summary-chip {
  display: flex; align-items: center; gap: 12px; width: 100%;
  background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.20);
  border-radius: 12px; padding: 12px 14px; margin-top: 6px; text-align: left;
}
html[data-theme="dark"] .success-summary-chip { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); }
.success-summary-icon {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  background: rgba(16,185,129,.15); color: #10b981;
  display: flex; align-items: center; justify-content: center; font-size: 14px;
}
html[data-theme="dark"] .success-summary-icon { color: #4ADE80; background: rgba(16,185,129,.20); }
.success-summary-info { flex: 1; min-width: 0; }
.success-summary-label { font-size: 12px; font-weight: 600; color: #10b981; margin: 0 0 2px; }
html[data-theme="dark"] .success-summary-label { color: #4ADE80; }
.success-summary-val { font-size: 13.5px; font-weight: 700; color: var(--sp-text-primary); margin: 0; }
.success-summary-badge {
  display: inline-flex; align-items: center; font-size: 11px; font-weight: 700;
  background: rgba(16,185,129,.15); color: #10b981; border-radius: 20px; padding: 3px 10px; flex-shrink: 0;
}
html[data-theme="dark"] .success-summary-badge { background: rgba(16,185,129,.22); color: #4ADE80; }
.success-modal-footer { padding: 0 26px 26px; }
.success-modal-btn {
  width: 100%; height: 44px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 14px; font-weight: 700; font-family: 'Inter', system-ui, sans-serif;
  border-radius: 11px; cursor: pointer; border: none;
  background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 2px 12px rgba(16,185,129,.30);
  transition: filter .18s, transform .15s, box-shadow .18s;
}
.success-modal-btn:hover { filter: brightness(1.08); transform: translateY(-1px); box-shadow: 0 4px 20px rgba(16,185,129,.40); }
.success-modal-btn:active { transform: scale(.97); }

/* MODALS */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(15, 23, 42, .70);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
html[data-theme="dark"] .modal-backdrop { background: rgba(0, 0, 0, .82); }
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.95) translateY(10px); }

.del-modal {
  width: 100%; max-width: 420px;
  background: #FFFFFF; border: 1px solid #E5E7EB;
  border-radius: 22px; box-shadow: 0 24px 64px rgba(0,0,0,.35); overflow: hidden;
  display: flex; flex-direction: column;
}
html[data-theme="dark"] .del-modal { background: #1E2130; border-color: #2A2D3E; box-shadow: 0 24px 64px rgba(0,0,0,.6); }
.del-modal::before { content: ''; display: block; height: 4px; background: linear-gradient(90deg, #f43f5e, #fb7185); }
.del-modal-icon-ring { display: flex; align-items: center; justify-content: center; padding: 28px 0 0; }
.del-modal-icon {
  width: 60px; height: 60px; border-radius: 16px;
  background: rgba(244,63,94,.10); border: 1.5px solid rgba(244,63,94,.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; color: #f43f5e; box-shadow: 0 0 0 8px rgba(244,63,94,.06);
}
html[data-theme="dark"] .del-modal-icon { background: rgba(244,63,94,.14); color: #F87171; box-shadow: 0 0 0 8px rgba(244,63,94,.08); }
.del-modal-body { padding: 18px 26px 22px; display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center; }
.del-modal-title { font-size: 18px; font-weight: 800; color: var(--sp-text-primary); letter-spacing: -.02em; margin: 0; }
.del-modal-desc  { font-size: 13px; line-height: 1.65; color: var(--sp-text-muted); margin: 0; }
.del-modal-desc strong { color: var(--sp-text-primary); }

.pw-field { width: 100%; text-align: left; margin-top: 6px; display: flex; flex-direction: column; gap: 6px; }
.pw-label { font-size: 12px; font-weight: 600; color: var(--sp-text-secondary); }
.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon { position: absolute; left: 13px; font-size: 12px; color: var(--sp-text-faint); pointer-events: none; z-index: 1; }
.pw-input {
  width: 100%; height: 42px; padding: 0 40px 0 36px;
  border: 1.5px solid var(--sp-border-strong); border-radius: 10px;
  background: var(--sp-surface-sunken); color: var(--sp-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.pw-input:focus { border-color: var(--sp-red); box-shadow: 0 0 0 3px rgba(244,63,94,.12); background: var(--sp-surface); }
.pw-input::placeholder { color: var(--sp-text-faint); }
.pw-toggle { position: absolute; right: 12px; background: none; border: none; cursor: pointer; color: var(--sp-text-faint); font-size: 13px; padding: 4px; transition: color .15s; }
.pw-toggle:hover { color: var(--sp-text-muted); }
.pw-error { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--sp-red); margin: 2px 0 0; }

.del-modal-footer { display: flex; gap: 10px; padding: 18px 26px 26px; }
.del-modal-btn {
  flex: 1; height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 13.5px; font-weight: 600; font-family: 'Inter', system-ui, sans-serif;
  border-radius: 11px; cursor: pointer; transition: background-color .18s, border-color .18s, opacity .18s, transform .15s;
}
.del-modal-btn:active { transform: scale(.97); }
.del-modal-btn:disabled { opacity: .55; cursor: not-allowed; transform: none; }
.del-modal-btn--cancel { background: var(--sp-surface-raised); border: 1.5px solid var(--sp-border-strong); color: var(--sp-text-secondary); }
.del-modal-btn--cancel:hover:not(:disabled) { background: var(--sp-border); color: var(--sp-text-primary); }
.del-modal-btn--confirm { background: linear-gradient(135deg, #f43f5e, #e11d48); border: none; color: #fff; box-shadow: 0 2px 12px rgba(244,63,94,.35); }
.del-modal-btn--confirm:hover:not(:disabled) { filter: brightness(1.08); box-shadow: 0 4px 20px rgba(244,63,94,.50); transform: translateY(-1px); }

@media (max-width: 700px) {
  .sales-page { padding: 16px 16px 48px; }
  .header-actions { flex-direction: column; align-items: stretch; }
  .period-dropdown-wrap { flex: 1 1 100%; }
  .period-trigger { width: 100%; justify-content: space-between; }
  .period-menu { left: 0; right: 0; width: auto; }
  .stat { padding: 10px 14px; flex: 1 1 33%; }
  .stat-scope { display: none; }
  .pager { justify-content: center; }
}

@media (prefers-reduced-motion: reduce) {
  .skel-row, .row-highlight td, .success-modal-icon { animation: none; }
}
</style>