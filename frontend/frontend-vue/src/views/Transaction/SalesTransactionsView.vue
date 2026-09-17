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
            <i class="fa-solid fa-trash"></i> Delete Selected
            <span class="selected-count">{{ selectedSales.length }}</span>
          </button>
          <RouterLink to="/pos" class="btn btn--primary">
            <i class="fa-solid fa-plus"></i> New Sale
          </RouterLink>
        </div>
      </div>

      <!-- ── PERIOD DROPDOWN ── -->
      <div class="period-dropdown-wrap period-dropdown">
        <button @click="togglePeriodDropdown" class="period-trigger">
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

      <!-- ── TABLE CARD ── -->
      <div class="table-card">
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="th-checkbox">
                  <label class="checkbox-wrap">
                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" />
                    <span class="checkbox-box"><i class="fa-solid fa-check"></i></span>
                  </label>
                </th>
                <th>Date</th>
                <th>Receipt #</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Clerk</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="sale in filteredSales"
                :key="sale.SaleID"
                :data-sale-row="sale.SaleID"
                :class="{ 'row--selected': selectedSales.includes(sale.SaleID), 'row-highlight': highlightedSaleId === sale.SaleID }"
              >
                <td class="th-checkbox">
                  <label class="checkbox-wrap">
                    <input type="checkbox" :value="sale.SaleID" v-model="selectedSales" />
                    <span class="checkbox-box"><i class="fa-solid fa-check"></i></span>
                  </label>
                </td>
                <td class="cell-muted">{{ sale.formatted_date }}</td>
                <td>
                  <RouterLink :to="`/sales/${sale.SaleID}`" class="receipt-link">
                    SR-{{ String(sale.SaleID).padStart(5,'0') }}
                  </RouterLink>
                </td>
                <td class="cell-secondary">{{ sale.CustomerName }}</td>
                <td class="cell-amount">₱{{ formatAmount(sale.TotalAmount) }}</td>
                <td class="cell-muted">{{ sale.PaymentMethod }}</td>
                <td>
                  <span class="status-pill" :class="sale.Status === 'Paid' ? 'status-pill--paid' : 'status-pill--void'">
                    {{ sale.Status }}
                  </span>
                </td>
                <td class="cell-muted">{{ sale.ClerkName }}</td>
              </tr>

              <tr v-if="filteredSales.length === 0">
                <td colspan="8" class="td-empty">
                  <div class="empty-state">
                    <i class="fa-solid fa-receipt"></i>
                    <p>No sales found for this period</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
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
              Enter your admin password to confirm this action.
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
    <!-- Shows after deletion completes — more prominent and intentional
         than a banner, so the owner can't miss that the action succeeded. -->
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

            <!-- Summary chip listing what was deleted -->
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
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const route = useRoute()

const sales = ref([])

async function loadSales() {
  try {
    const res = await api.get('/api/sales')
    sales.value = res.data.map(s => ({
      ...s,
      formatted_date: s.SaleDate,
      CustomerName: s.CustomerName || 'Unknown',
      ClerkName: s.ClerkName || 'Unknown',
    }))
  } catch (err) {
    console.error('Failed to load sales:', err)
  }
}

const periods = [
  { key: 'today',   label: 'Today' },
  { key: 'week',    label: 'This Week' },
  { key: 'month',   label: 'This Month' },
  { key: 'quarter', label: 'This Quarter' },
  { key: 'year',    label: 'This Year' },
]
const selectedPeriod = ref('month')
const periodDropdown = ref(false)
const selectedPeriodLabel = computed(() => periods.find(p => p.key === selectedPeriod.value)?.label)

function togglePeriodDropdown() { periodDropdown.value = !periodDropdown.value }
function setPeriod(p) { selectedPeriod.value = p; periodDropdown.value = false }

function handleClickOutside(e) {
  if (!e.target.closest('.period-dropdown')) periodDropdown.value = false
}

/* row highlight — set when arriving from a "New Sale" notification
   click (?highlightSale=<SaleID>). Cleared automatically after a few
   seconds so it reads as a "flash", not a permanent state. */
const highlightedSaleId = ref(null)
let highlightTimer = null

// If arriving from a "New Sale" notification, the sale might not fall
// inside the currently-selected period (default is "This Month"). Bump
// the period up to whichever bucket actually contains the sale's date,
// so the row we're about to highlight is guaranteed to be visible.
function periodContaining(date) {
  const now = new Date()
  if (date.toDateString() === now.toDateString()) return 'today'
  const weekStart = new Date(now); weekStart.setDate(now.getDate() - now.getDay()); weekStart.setHours(0,0,0,0)
  if (date >= weekStart) return 'week'
  if (date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear()) return 'month'
  if (Math.floor(date.getMonth()/3) === Math.floor(now.getMonth()/3) && date.getFullYear() === now.getFullYear()) return 'quarter'
  if (date.getFullYear() === now.getFullYear()) return 'year'
  return 'year' // fallback — oldest bucket we have
}

/** Called once sales are loaded, when the page was opened from a "New
 *  Sale" notification click carrying ?highlightSale=<SaleID>.
 *  notifiable_id is populated on sale notifications, so this matches
 *  directly against SaleID — no name-based guessing needed. Silently
 *  does nothing if no match is found (e.g. the sale was deleted since
 *  the notification fired). */
function applyHighlightFromQuery() {
  const saleId = route.query.highlightSale
  if (!saleId) return

  const match = sales.value.find(s => String(s.SaleID) === String(saleId))
  if (!match) return

  selectedPeriod.value = periodContaining(new Date(match.SaleDate))
  highlightedSaleId.value = match.SaleID

  nextTick(() => {
    const el = document.querySelector(`[data-sale-row="${match.SaleID}"]`)
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  })

  if (highlightTimer) clearTimeout(highlightTimer)
  highlightTimer = setTimeout(() => { highlightedSaleId.value = null }, 3000)
}

onMounted(async () => {
  document.addEventListener('click', handleClickOutside)
  await loadSales()
  applyHighlightFromQuery()
})
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  if (highlightTimer) clearTimeout(highlightTimer)
})

const filteredSales = computed(() => {
  const now = new Date()
  return sales.value.filter(s => {
    const d = new Date(s.SaleDate)
    if (selectedPeriod.value === 'today') return d.toDateString() === now.toDateString()
    if (selectedPeriod.value === 'week') {
      const start = new Date(now); start.setDate(now.getDate() - now.getDay()); start.setHours(0,0,0,0)
      return d >= start
    }
    if (selectedPeriod.value === 'month')   return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear()
    if (selectedPeriod.value === 'quarter') return Math.floor(d.getMonth()/3) === Math.floor(now.getMonth()/3) && d.getFullYear() === now.getFullYear()
    if (selectedPeriod.value === 'year')    return d.getFullYear() === now.getFullYear()
    return true
  })
})

const selectedSales = ref([])
const selectAll = ref(false)
function toggleSelectAll() { selectedSales.value = selectAll.value ? filteredSales.value.map(s => s.SaleID) : [] }

const passwordModal = ref(false)
const adminPassword = ref('')
const showPw         = ref(false)
const deleting        = ref(false)
const deleteError     = ref('')

// Success confirmation modal — shown after deletion completes.
// deletedCount captures how many were deleted before selectedSales
// is cleared, so the message stays accurate.
const successModal = ref(false)
const deletedCount = ref(0)

function openPasswordModal() {
  if (!selectedSales.value.length) { alert('Select a sale.'); return }
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
      admin_password: adminPassword.value
    })

    // Step 1: capture count and close the password modal FIRST,
    // before clearing selectedSales — otherwise Vue re-renders the
    // still-open modal with "0 sale receipts" before it closes,
    // which is exactly the confusing flash the user was seeing.
    deletedCount.value = count
    passwordModal.value = false
    adminPassword.value = ''
    showPw.value = false
    deleteError.value = ''

    // Step 2: wait for Vue to finish closing the password modal
    // before we clear selection, reload, and open the success modal.
    await nextTick()

    selectedSales.value = []
    selectAll.value = false
    await loadSales()

    // Step 3: now open the success modal — password modal is already
    // gone, so there's no overlap and no "0 receipts" flash.
    successModal.value = true

  } catch (err) {
    deleteError.value = err?.response?.data?.message || 'Incorrect password or request failed.'
  } finally {
    deleting.value = false
  }
}

const formatAmount = v => Number(v).toLocaleString()
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
  display: flex; flex-direction: column; gap: 18px;
  transition: background-color .22s;
}

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
.btn--primary { background: linear-gradient(135deg, var(--sp-accent), var(--c-accent-deep)); color: #fff; box-shadow: var(--c-shadow-accent); }
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--danger {
  background: rgba(244,63,94,.10); border-color: rgba(244,63,94,.30); color: var(--sp-red);
}
.btn--danger:hover { background: rgba(244,63,94,.16); border-color: rgba(244,63,94,.45); }
html[data-theme="dark"] .btn--danger { background: rgba(244,63,94,.14); color: #F87171; border-color: rgba(244,63,94,.35); }
html[data-theme="dark"] .btn--danger:hover { background: rgba(244,63,94,.20); }
.selected-count {
  background: var(--sp-red); color: #fff; font-size: 11px; font-weight: 700;
  border-radius: 20px; padding: 1px 7px; margin-left: 2px;
}

/* SUCCESS CONFIRMATION MODAL */
.success-modal {
  width: 100%; max-width: 420px;
  background: #FFFFFF; border: 1px solid #E5E7EB;
  border-radius: 22px; box-shadow: 0 24px 64px rgba(0,0,0,.35); overflow: hidden;
  display: flex; flex-direction: column;
  transition: background-color .22s, border-color .22s;
}
html[data-theme="dark"] .success-modal {
  background: #1E2130; border-color: #2A2D3E;
  box-shadow: 0 24px 64px rgba(0,0,0,.6);
}
.success-modal::before {
  content: ''; display: block; height: 4px;
  background: linear-gradient(90deg, #10b981, #34d399);
}
.success-modal-icon-ring {
  display: flex; align-items: center; justify-content: center;
  padding: 28px 0 0;
}
.success-modal-icon {
  width: 64px; height: 64px; border-radius: 50%;
  background: rgba(16,185,129,.12); border: 2px solid rgba(16,185,129,.30);
  display: flex; align-items: center; justify-content: center;
  font-size: 26px; color: #10b981;
  box-shadow: 0 0 0 10px rgba(16,185,129,.06);
  animation: success-pop .4s cubic-bezier(.34,1.56,.64,1) both;
}
html[data-theme="dark"] .success-modal-icon {
  background: rgba(16,185,129,.16); color: #4ADE80;
  box-shadow: 0 0 0 10px rgba(16,185,129,.08);
}
@keyframes success-pop {
  from { transform: scale(0.5); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}

.success-modal-body {
  padding: 18px 26px 22px;
  display: flex; flex-direction: column;
  align-items: center; gap: 10px; text-align: center;
}
.success-modal-title {
  font-size: 19px; font-weight: 800;
  color: var(--sp-text-primary); letter-spacing: -.02em; margin: 0;
  transition: color .22s;
}
.success-modal-desc {
  font-size: 13.5px; line-height: 1.65;
  color: var(--sp-text-muted); margin: 0;
}
.success-modal-desc strong { color: var(--sp-text-primary); }

/* Summary chip — shows what was just deleted */
.success-summary-chip {
  display: flex; align-items: center; gap: 12px; width: 100%;
  background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.20);
  border-radius: 12px; padding: 12px 14px; margin-top: 6px; text-align: left;
  transition: background .22s, border-color .22s;
}
html[data-theme="dark"] .success-summary-chip {
  background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25);
}
.success-summary-icon {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  background: rgba(16,185,129,.15); color: #10b981;
  display: flex; align-items: center; justify-content: center; font-size: 14px;
}
html[data-theme="dark"] .success-summary-icon { color: #4ADE80; background: rgba(16,185,129,.20); }
.success-summary-info { flex: 1; min-width: 0; }
.success-summary-label {
  font-size: 10.5px; font-weight: 700; letter-spacing: .08em;
  text-transform: uppercase; color: #10b981; margin: 0 0 2px;
}
html[data-theme="dark"] .success-summary-label { color: #4ADE80; }
.success-summary-val {
  font-size: 13.5px; font-weight: 700;
  color: var(--sp-text-primary); margin: 0;
  transition: color .22s;
}
.success-summary-badge {
  display: inline-flex; align-items: center;
  font-size: 11px; font-weight: 700; letter-spacing: .04em;
  background: rgba(16,185,129,.15); color: #10b981;
  border-radius: 20px; padding: 3px 10px; flex-shrink: 0;
}
html[data-theme="dark"] .success-summary-badge { background: rgba(16,185,129,.22); color: #4ADE80; }

.success-modal-footer { padding: 0 26px 26px; }
.success-modal-btn {
  width: 100%; height: 44px;
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 14px; font-weight: 700; font-family: 'Inter', system-ui, sans-serif;
  border-radius: 11px; cursor: pointer; border: none;
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff; box-shadow: 0 2px 12px rgba(16,185,129,.30);
  transition: filter .18s, transform .15s, box-shadow .18s;
}
.success-modal-btn:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
  box-shadow: 0 4px 20px rgba(16,185,129,.40);
}
.success-modal-btn:active { transform: scale(.97); }

/* PERIOD DROPDOWN */
.period-dropdown-wrap { position: relative; display: inline-block; align-self: flex-start; }
.period-trigger {
  display: inline-flex; align-items: center; gap: 8px;
  height: 40px; padding: 0 16px;
  border: 1.5px solid var(--sp-border-strong); border-radius: 10px;
  background: var(--sp-surface); color: var(--sp-text-secondary);
  font-size: 13px; font-family: inherit; cursor: pointer;
  transition: border-color .18s, background-color .22s, color .22s;
}
.period-trigger:hover { border-color: var(--sp-accent-border); }
.period-trigger-label { font-weight: 700; color: var(--sp-text-faint); font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
.period-trigger-val   { font-weight: 600; color: var(--sp-text-primary); transition: color .22s; }
.period-caret { font-size: 10px; color: var(--sp-text-faint); transition: transform .2s; }
.period-caret--open { transform: rotate(180deg); }

.period-menu {
  position: absolute; top: calc(100% + 8px); right: 0;
  width: 200px; background: var(--sp-surface); border: 1px solid var(--sp-border);
  border-radius: 13px; box-shadow: var(--c-shadow-lg); z-index: 100; padding: 5px; overflow: hidden;
  transition: background-color .22s, border-color .22s;
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

/* TABLE CARD */
.table-card { background: var(--sp-surface); border: 1px solid var(--sp-border); border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden; transition: background-color .22s, border-color .22s; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.data-table thead th {
  background: var(--sp-surface-raised); color: var(--sp-text-faint);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--sp-border);
  white-space: nowrap; transition: background-color .22s, color .22s, border-color .22s;
}
.th-checkbox { width: 44px; }

.data-table tbody tr { transition: background-color .15s; }
.data-table tbody tr:hover td { background: var(--sp-surface-raised); }
.data-table tbody tr.row--selected td { background: var(--sp-accent-soft); }
.data-table tbody td { padding: 13px 16px; border-bottom: 1px solid var(--sp-border); white-space: nowrap; transition: background-color .15s, border-color .22s; }
.data-table tbody tr:last-child td { border-bottom: none; }

/* ROW HIGHLIGHT — flashed briefly when arriving from a "New Sale"
   notification click, so the exact receipt the alert referred to is
   unmistakable in the (possibly long) filtered list. Uses the same
   background as row--selected so it stays legible, layered with a
   short pulse animation using the green "sale" accent to distinguish
   it from a manual checkbox selection. */
.row-highlight td {
  background: var(--sp-accent-soft) !important;
  animation: sale-row-pulse 1.6s ease-in-out 1;
}
@keyframes sale-row-pulse {
  0%   { background: var(--sp-accent-soft); }
  50%  { background: rgba(16,185,129,.18); }
  100% { background: var(--sp-accent-soft); }
}
html[data-theme="dark"] .row-highlight td { background: rgba(16,185,129,.14) !important; }

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
.checkbox-wrap input:checked + .checkbox-box {
  background: var(--sp-accent); border-color: var(--sp-accent); color: #fff;
}
.checkbox-wrap input:focus-visible + .checkbox-box { box-shadow: 0 0 0 3px var(--sp-ring); }

/* CELLS */
.cell-muted     { color: var(--sp-text-muted); transition: color .22s; }
.cell-secondary { color: var(--sp-text-secondary); transition: color .22s; }
.cell-amount    { font-weight: 700; color: var(--sp-text-primary); font-variant-numeric: tabular-nums; transition: color .22s; }
.receipt-link {
  font-weight: 700; color: var(--sp-accent); text-decoration: none; font-variant-numeric: tabular-nums;
  transition: opacity .15s;
}
.receipt-link:hover { text-decoration: underline; }

.status-pill { display: inline-flex; font-size: 11px; font-weight: 700; border-radius: 20px; padding: 3px 11px; letter-spacing: .03em; }
.status-pill--paid { background: rgba(16,185,129,.12); color: var(--sp-green); }
.status-pill--void  { background: rgba(244,63,94,.12);  color: var(--sp-red);   }
html[data-theme="dark"] .status-pill--paid { background: rgba(16,185,129,.18); color: #4ADE80; }
html[data-theme="dark"] .status-pill--void  { background: rgba(244,63,94,.18);  color: #F87171; }

/* EMPTY */
.td-empty { padding: 56px 24px !important; text-align: center; border-bottom: none !important; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 10px; color: var(--sp-text-faint); }
.empty-state i { font-size: 32px; }
.empty-state p { font-size: 13.5px; font-weight: 600; color: var(--sp-text-muted); margin: 0; }

/* MODAL — solid, opaque overlay; color adapts to theme rather than a
   translucent black wash, so it never blends into a dark-mode page.
   No blur, alpha is effectively 1. */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(15, 23, 42, .70); /* light mode: solid dark slate overlay */
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
html[data-theme="dark"] .modal-backdrop {
  background: rgba(0, 0, 0, .82); /* dark mode: solid near-black overlay */
}
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.95) translateY(10px); }

.del-modal {
  width: 100%; max-width: 420px;
  background: #FFFFFF; border: 1px solid #E5E7EB;
  border-radius: 22px; box-shadow: 0 24px 64px rgba(0,0,0,.35); overflow: hidden;
  display: flex; flex-direction: column;
  transition: background-color .22s, border-color .22s;
}
html[data-theme="dark"] .del-modal {
  background: #1E2130; border-color: #2A2D3E;
  box-shadow: 0 24px 64px rgba(0,0,0,.6);
}
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
.del-modal-title { font-size: 18px; font-weight: 800; color: var(--sp-text-primary); letter-spacing: -.02em; margin: 0; transition: color .22s; }
.del-modal-desc  { font-size: 13px; line-height: 1.65; color: var(--sp-text-muted); margin: 0; }
.del-modal-desc strong { color: var(--sp-text-primary); }

.pw-field { width: 100%; text-align: left; margin-top: 6px; display: flex; flex-direction: column; gap: 6px; }
.pw-label { font-size: 12px; font-weight: 600; color: var(--sp-text-secondary); transition: color .22s; }
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
.pw-toggle {
  position: absolute; right: 12px; background: none; border: none; cursor: pointer;
  color: var(--sp-text-faint); font-size: 13px; padding: 4px; transition: color .15s;
}
.pw-toggle:hover { color: var(--sp-text-muted); }
.pw-error { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--sp-red); margin: 2px 0 0; }

.del-modal-footer { display: flex; gap: 10px; padding: 18px 26px 26px; }
.del-modal-btn {
  flex: 1; height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 13.5px; font-weight: 600; font-family: 'Inter',system-ui,sans-serif;
  border-radius: 11px; cursor: pointer; transition: background-color .18s, border-color .18s, opacity .18s, transform .15s;
}
.del-modal-btn:active { transform: scale(.97); }
.del-modal-btn:disabled { opacity: .55; cursor: not-allowed; transform: none; }
.del-modal-btn--cancel { background: var(--sp-surface-raised); border: 1.5px solid var(--sp-border-strong); color: var(--sp-text-secondary); }
.del-modal-btn--cancel:hover:not(:disabled) { background: var(--sp-border); color: var(--sp-text-primary); }
.del-modal-btn--confirm { background: linear-gradient(135deg,#f43f5e,#e11d48); border: none; color: #fff; box-shadow: 0 2px 12px rgba(244,63,94,.35); }
.del-modal-btn--confirm:hover:not(:disabled) { filter: brightness(1.08); box-shadow: 0 4px 20px rgba(244,63,94,.50); transform: translateY(-1px); }

@media (max-width: 700px) {
  .sales-page { padding: 16px 16px 48px; }
  .header-actions { flex-direction: column; align-items: stretch; }
  .period-dropdown-wrap { align-self: stretch; }
  .period-trigger { width: 100%; justify-content: space-between; }
}
</style>