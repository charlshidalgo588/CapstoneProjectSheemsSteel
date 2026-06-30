<template>
  <Layout title="Suppliers">
    <div class="sup-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Directory</p>
          <h1 class="page-title">Suppliers</h1>
        </div>
        <RouterLink to="/suppliers/create" class="btn btn--primary">
          <i class="fa-solid fa-plus"></i> Add Supplier
        </RouterLink>
      </div>

      <!-- ── ALERTS ── -->
      <transition name="alert-fade">
        <div v-if="successMessage" class="alert alert--success">
          <i class="fa-solid fa-circle-check"></i>
          <span>{{ successMessage }}</span>
          <button class="alert-close" @click="successMessage = ''">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </transition>
      <transition name="alert-fade">
        <div v-if="errorMessage" class="alert alert--error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>{{ errorMessage }}</span>
          <button class="alert-close" @click="errorMessage = ''">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </transition>

      <!-- ── TABLE CARD ── -->
      <div class="table-card">
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Supplier Name</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Address</th>
                <th class="th-actions">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="supplier in suppliers" :key="supplier.SupplierID">
                <td class="td-name">
                  <div class="cell-name">
                    <div class="cell-avatar">
                      {{ supplier.SupplierName.charAt(0).toUpperCase() }}
                    </div>
                    <span class="cell-primary">{{ supplier.SupplierName }}</span>
                  </div>
                </td>
                <td class="td-contact">
                  <span v-if="supplier.ContactNumber" class="cell-with-icon">
                    <i class="fa-solid fa-phone cell-icon"></i>
                    {{ supplier.ContactNumber }}
                  </span>
                  <span v-else class="cell-empty">—</span>
                </td>
                <td class="td-email">
                  <span v-if="supplier.Email" class="cell-with-icon">
                    <i class="fa-regular fa-envelope cell-icon"></i>
                    {{ supplier.Email }}
                  </span>
                  <span v-else class="cell-empty">—</span>
                </td>
                <td class="td-address">
                  <span v-if="supplier.Address" class="cell-address-text">{{ supplier.Address }}</span>
                  <span v-else class="cell-empty">—</span>
                </td>
                <td class="td-actions">
                  <button
                    :ref="setMenuButtonRef(supplier.SupplierID)"
                    @click="toggleMenu(supplier.SupplierID)"
                    class="menu-btn"
                    :class="{ 'menu-btn--active': openMenuId === supplier.SupplierID }"
                  >
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                  </button>
                </td>
              </tr>

              <tr v-if="suppliers.length === 0">
                <td colspan="5" class="td-empty">
                  <div class="empty-state">
                    <i class="fa-solid fa-truck-field"></i>
                    <p>No suppliers yet</p>
                    <RouterLink to="/suppliers/create" class="btn btn--primary btn--sm">
                      <i class="fa-solid fa-plus"></i> Add First Supplier
                    </RouterLink>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- ── DROPDOWN (Teleported) ── -->
    <Teleport to="body">
      <div
        v-if="openMenuId && currentSupplier"
        ref="dropdownRef"
        :style="dropdownStyle"
        class="supplier-dropdown"
      >
        <RouterLink
          :to="`/suppliers/${currentSupplier.SupplierID}/edit`"
          class="supplier-dropdown-item"
          @click="closeMenu"
        >
          <span class="supplier-dropdown-icon supplier-dropdown-icon--blue">
            <i class="fa-solid fa-pen-to-square"></i>
          </span>
          Edit Supplier
        </RouterLink>
        <div class="supplier-dropdown-divider"></div>
        <button
          class="supplier-dropdown-item supplier-dropdown-item--danger"
          @click="promptDelete(currentSupplier)"
        >
          <span class="supplier-dropdown-icon supplier-dropdown-icon--red">
            <i class="fa-solid fa-trash"></i>
          </span>
          Delete
        </button>
      </div>
    </Teleport>

    <!-- ══════════════════════════════════════
         DELETE CONFIRMATION MODAL
    ══════════════════════════════════════ -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div
          v-if="showDeleteModal"
          class="modal-backdrop"
          @click.self="cancelDelete"
        >
          <div class="del-modal" role="dialog" aria-modal="true" aria-labelledby="del-modal-title">

            <!-- Top icon -->
            <div class="del-modal-icon-ring">
              <div class="del-modal-icon">
                <i class="fa-solid fa-trash-can"></i>
              </div>
            </div>

            <!-- Content -->
            <div class="del-modal-body">
              <h3 class="del-modal-title" id="del-modal-title">Delete Supplier</h3>
              <p class="del-modal-desc">
                You are about to permanently delete
                <strong class="del-modal-name">{{ supplierToDelete?.SupplierName }}</strong>.
                This action cannot be undone and will remove all associated records.
              </p>

              <!-- Supplier info chip -->
              <div class="del-modal-chip">
                <div class="del-modal-chip-avatar">
                  {{ supplierToDelete?.SupplierName?.charAt(0).toUpperCase() }}
                </div>
                <div class="del-modal-chip-info">
                  <p class="del-modal-chip-name">{{ supplierToDelete?.SupplierName }}</p>
                  <p class="del-modal-chip-meta">
                    {{ supplierToDelete?.Email || supplierToDelete?.ContactNumber || 'No contact info' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="del-modal-footer">
              <button
                class="del-modal-btn del-modal-btn--cancel"
                @click="cancelDelete"
                :disabled="deleting"
              >
                Cancel
              </button>
              <button
                class="del-modal-btn del-modal-btn--confirm"
                @click="confirmDelete"
                :disabled="deleting"
              >
                <i v-if="deleting" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-trash-can"></i>
                {{ deleting ? 'Deleting…' : 'Yes, Delete' }}
              </button>
            </div>

          </div>
        </div>
      </transition>
    </Teleport>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, onBeforeUnmount } from 'vue'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const suppliers      = ref([])
const successMessage = ref('')
const errorMessage   = ref('')

/* ── dropdown ── */
const openMenuId     = ref(null)
const dropdownRef    = ref(null)
const dropdownStyle  = ref({ position: 'fixed', top: '0px', left: '0px' })
const menuButtonRefs = new Map()

/* ── delete modal ── */
const showDeleteModal  = ref(false)
const supplierToDelete = ref(null)
const deleting         = ref(false)

const setMenuButtonRef = (id) => (el) => {
  if (el) menuButtonRefs.set(id, el)
  else    menuButtonRefs.delete(id)
}

const currentSupplier = computed(() =>
  suppliers.value.find((s) => s.SupplierID === openMenuId.value)
)

async function loadSuppliers() {
  try {
    const res = await api.get('/api/suppliers')
    suppliers.value = res.data.suppliers
  } catch {
    errorMessage.value = 'Failed to load suppliers.'
  }
}
onMounted(loadSuppliers)

function toggleMenu(id) {
  if (openMenuId.value === id) { closeMenu(); return }
  openMenuId.value = id
  nextTick(() => {
    const btn = menuButtonRefs.get(id)
    if (!btn) return
    const rect = btn.getBoundingClientRect()
    dropdownStyle.value = {
      position: 'fixed',
      top:  `${rect.bottom + 8}px`,
      left: `${rect.right - 172}px`,
    }
  })
}
function closeMenu() { openMenuId.value = null }

function onClickOutside(e) {
  if (!openMenuId.value) return
  const dd  = dropdownRef.value
  const btn = menuButtonRefs.get(openMenuId.value)
  if (dd && !dd.contains(e.target) && btn && !btn.contains(e.target)) closeMenu()
}
function onKeydown(e) {
  if (e.key === 'Escape') {
    if (showDeleteModal.value) cancelDelete()
    else closeMenu()
  }
}

document.addEventListener('click', onClickOutside)
document.addEventListener('keydown', onKeydown)
onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  document.removeEventListener('keydown', onKeydown)
})

/* ── Delete flow ── */
function promptDelete(supplier) {
  closeMenu()
  supplierToDelete.value = supplier
  showDeleteModal.value  = true
}

function cancelDelete() {
  if (deleting.value) return
  showDeleteModal.value  = false
  supplierToDelete.value = null
}

async function confirmDelete() {
  if (!supplierToDelete.value || deleting.value) return
  deleting.value = true
  try {
    await api.delete(`/api/suppliers/${supplierToDelete.value.SupplierID}`, { withCredentials: true })
    successMessage.value   = `"${supplierToDelete.value.SupplierName}" deleted successfully.`
    errorMessage.value     = ''
    showDeleteModal.value  = false
    supplierToDelete.value = null
    loadSuppliers()
  } catch {
    errorMessage.value    = 'Failed to delete supplier. Please try again.'
    showDeleteModal.value = false
  } finally {
    deleting.value = false
  }
}
</script>

<style scoped>
/* ── TOKEN BRIDGE ── */
.sup-page {
  --s-bg:             var(--c-bg);
  --s-surface:        var(--c-surface);
  --s-surface-raised: var(--c-surface-raised);
  --s-border:         var(--c-border);
  --s-border-strong:  var(--c-border-strong);
  --s-text-primary:   var(--c-text-primary);
  --s-text-secondary: var(--c-text-secondary);
  --s-text-muted:     var(--c-text-muted);
  --s-text-faint:     var(--c-text-faint);
  --s-accent:         var(--c-accent);
  --s-accent-soft:    var(--c-accent-soft);
  --s-accent-border:  var(--c-accent-border);
  --s-green:          #10b981;
  --s-red:            #f43f5e;
  --s-blue:           #3b82f6;
  --radius:           16px;

  min-height: 100%;
  background: var(--s-bg);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 20px;
  transition: background-color .22s ease;
}

/* PAGE HEADER */
.page-header {
  display: flex; align-items: flex-end;
  justify-content: space-between; gap: 12px; flex-wrap: wrap;
}
.page-eyebrow {
  font-size: 11px; font-weight: 700; letter-spacing: .12em;
  color: var(--s-accent); text-transform: uppercase; margin: 0 0 5px;
}
.page-title {
  font-size: 26px; font-weight: 800; color: var(--s-text-primary);
  letter-spacing: -.03em; margin: 0; transition: color .22s;
}

/* BUTTONS */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn--primary {
  background: linear-gradient(135deg, var(--s-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }

/* ALERTS */
.alert {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-radius: 12px;
  font-size: 13.5px; font-weight: 500; border: 1px solid transparent;
}
.alert--success { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); color: var(--s-green); }
.alert--error   { background: rgba(244,63,94,.09);  border-color: rgba(244,63,94,.22);  color: var(--s-red); }
html[data-theme="dark"] .alert--success { background: rgba(16,185,129,.14); color: #4ADE80; }
html[data-theme="dark"] .alert--error   { background: rgba(244,63,94,.14);  color: #F87171; }
.alert-close {
  background: none; border: none; cursor: pointer;
  color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 2px; transition: opacity .15s;
}
.alert-close:hover { opacity: 1; }
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* TABLE CARD */
.table-card {
  background: var(--s-surface); border: 1px solid var(--s-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.table-wrap { overflow-x: auto; }

.data-table {
  width: 100%; border-collapse: collapse;
  font-size: 13.5px; table-layout: fixed;
}
.data-table thead th {
  background: var(--s-surface-raised); color: var(--s-text-faint);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--s-border);
  white-space: nowrap;
  transition: background-color .22s, color .22s, border-color .22s;
}
.data-table thead th:nth-child(1) { width: 26%; }
.data-table thead th:nth-child(2) { width: 16%; }
.data-table thead th:nth-child(3) { width: 22%; }
.data-table thead th:nth-child(4) { width: 26%; }
.data-table thead th:nth-child(5) { width: 10%; }
.th-actions { text-align: right; }

.data-table tbody tr { transition: background-color .15s; }
.data-table tbody tr:hover td { background: var(--s-surface-raised); }
.data-table tbody td {
  padding: 14px 16px; border-bottom: 1px solid var(--s-border);
  vertical-align: middle; transition: background-color .15s, border-color .22s;
}
.data-table tbody tr:last-child td { border-bottom: none; }

/* CELLS */
.cell-name     { display: flex; align-items: center; gap: 10px; }
.cell-avatar   {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  background: var(--s-accent-soft); color: var(--s-accent);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 800; border: 1px solid var(--s-accent-border);
  transition: background-color .22s, border-color .22s;
}
.cell-primary  { font-weight: 600; color: var(--s-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color .22s; }
.td-contact,
.td-email      { color: var(--s-text-muted); white-space: nowrap; transition: color .22s; }
.cell-with-icon{ display: flex; align-items: center; gap: 7px; }
.cell-icon     { font-size: 11px; color: var(--s-text-faint); flex-shrink: 0; }
.td-address    { color: var(--s-text-muted); transition: color .22s; }
.cell-address-text { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }
.cell-empty    { color: var(--s-text-faint); font-size: 13px; }
.td-actions    { text-align: right; }
.menu-btn {
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: none; color: var(--s-text-faint); cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center; font-size: 14px;
  transition: background-color .15s, color .15s;
}
.menu-btn:hover,
.menu-btn--active { background: var(--s-surface-raised); color: var(--s-text-primary); }

.td-empty { padding: 56px 24px !important; border-bottom: none !important; text-align: center; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 12px; color: var(--s-text-faint); }
.empty-state i { font-size: 36px; }
.empty-state p { font-size: 14px; font-weight: 600; color: var(--s-text-muted); margin: 0; }
</style>

<!-- ══════════════════════════════════════════════════════
     GLOBAL — Teleported elements (dropdown + delete modal)
     Must be unscoped; namespaced to avoid leaking.
══════════════════════════════════════════════════════ -->
<style>

/* ─── DROPDOWN ─────────────────────────────────────────── */
.supplier-dropdown {
  width: 176px;
  background: #FFFFFF; border: 1px solid #E5E7EB;
  border-radius: 13px;
  box-shadow: 0 8px 32px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06);
  z-index: 9999; padding: 5px; overflow: hidden;
}
html[data-theme="dark"] .supplier-dropdown {
  background: #1E2130; border-color: #2A2D3E;
  box-shadow: 0 8px 40px rgba(0,0,0,.55), 0 2px 10px rgba(0,0,0,.30);
}
.supplier-dropdown-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px; font-size: 13px; font-weight: 500;
  color: #374151; border-radius: 8px; text-decoration: none;
  background: none; border: none; width: 100%; cursor: pointer;
  font-family: 'Inter', system-ui, sans-serif;
  transition: background-color .15s, color .15s;
}
.supplier-dropdown-item:hover { background: #F9FAFB; color: #111827; }
html[data-theme="dark"] .supplier-dropdown-item       { color: #C4C8D6; }
html[data-theme="dark"] .supplier-dropdown-item:hover { background: #222535; color: #E8EAF0; }
.supplier-dropdown-item--danger       { color: #EF4444 !important; }
.supplier-dropdown-item--danger:hover { background: rgba(244,63,94,.10) !important; color: #EF4444 !important; }
html[data-theme="dark"] .supplier-dropdown-item--danger       { color: #F87171 !important; }
html[data-theme="dark"] .supplier-dropdown-item--danger:hover { background: rgba(244,63,94,.14) !important; }
.supplier-dropdown-icon {
  width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 11.5px;
}
.supplier-dropdown-icon--blue { background: rgba(59,130,246,.12); color: #3b82f6; }
.supplier-dropdown-icon--red  { background: rgba(244,63,94,.12);  color: #f43f5e; }
html[data-theme="dark"] .supplier-dropdown-icon--blue { background: rgba(59,130,246,.18); }
html[data-theme="dark"] .supplier-dropdown-icon--red  { background: rgba(244,63,94,.18); color: #F87171; }
.supplier-dropdown-divider { height: 1px; background: #E5E7EB; margin: 3px 0; }
html[data-theme="dark"] .supplier-dropdown-divider { background: #2A2D3E; }

/* ─── DELETE MODAL BACKDROP ────────────────────────────── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(0, 0, 0, 0.48);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
}

/* ─── DELETE MODAL CARD ────────────────────────────────── */
.del-modal {
  width: 100%; max-width: 400px;
  background: #FFFFFF;
  border: 1px solid #E5E7EB;
  border-radius: 22px;
  box-shadow:
    0 32px 80px rgba(0,0,0,.18),
    0 8px 24px rgba(0,0,0,.10),
    0 0 0 1px rgba(255,255,255,.06);
  overflow: hidden;
  display: flex; flex-direction: column;
}
html[data-theme="dark"] .del-modal {
  background: #1A1D27;
  border-color: #2A2D3E;
  box-shadow:
    0 32px 80px rgba(0,0,0,.70),
    0 8px 24px rgba(0,0,0,.40),
    0 0 0 1px rgba(255,255,255,.04);
}

/* Red top accent bar */
.del-modal::before {
  content: '';
  display: block;
  height: 4px;
  background: linear-gradient(90deg, #f43f5e 0%, #fb7185 100%);
  flex-shrink: 0;
}

/* ─── ICON RING ── */
.del-modal-icon-ring {
  display: flex; align-items: center; justify-content: center;
  padding: 32px 0 0;
}
.del-modal-icon {
  width: 64px; height: 64px;
  border-radius: 18px;
  background: rgba(244,63,94,.10);
  border: 1.5px solid rgba(244,63,94,.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 24px;
  color: #f43f5e;
  box-shadow: 0 0 0 8px rgba(244,63,94,.06);
}
html[data-theme="dark"] .del-modal-icon {
  background: rgba(244,63,94,.14);
  border-color: rgba(244,63,94,.30);
  color: #F87171;
  box-shadow: 0 0 0 8px rgba(244,63,94,.08);
}

/* ─── BODY ── */
.del-modal-body {
  padding: 20px 28px 24px;
  display: flex; flex-direction: column; align-items: center;
  gap: 10px; text-align: center;
}
.del-modal-title {
  font-size: 18px; font-weight: 800;
  color: #111827;
  letter-spacing: -.02em; margin: 0;
}
html[data-theme="dark"] .del-modal-title { color: #E8EAF0; }

.del-modal-desc {
  font-size: 13.5px; line-height: 1.65;
  color: #6B7280; margin: 0;
}
html[data-theme="dark"] .del-modal-desc { color: #8B90A8; }

.del-modal-name {
  color: #111827; font-weight: 700;
}
html[data-theme="dark"] .del-modal-name { color: #E8EAF0; }

/* ─── SUPPLIER CHIP ── */
.del-modal-chip {
  display: flex; align-items: center; gap: 12px;
  width: 100%; margin-top: 6px;
  background: #F9FAFB;
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  padding: 12px 14px;
  text-align: left;
}
html[data-theme="dark"] .del-modal-chip {
  background: #222535;
  border-color: #2A2D3E;
}
.del-modal-chip-avatar {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  background: rgba(244,63,94,.12);
  color: #f43f5e;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; font-weight: 800;
  border: 1px solid rgba(244,63,94,.20);
}
html[data-theme="dark"] .del-modal-chip-avatar {
  background: rgba(244,63,94,.18);
  color: #F87171;
  border-color: rgba(244,63,94,.30);
}
.del-modal-chip-info { flex: 1; min-width: 0; }
.del-modal-chip-name {
  font-size: 13.5px; font-weight: 700; color: #111827;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 2px;
}
html[data-theme="dark"] .del-modal-chip-name { color: #E8EAF0; }
.del-modal-chip-meta {
  font-size: 12px; color: #9CA3AF;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0;
}
html[data-theme="dark"] .del-modal-chip-meta { color: #555B72; }

/* ─── FOOTER ACTIONS ── */
.del-modal-footer {
  display: flex; gap: 10px;
  padding: 0 28px 28px;
}
.del-modal-btn {
  flex: 1; height: 44px;
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 14px; font-weight: 600; font-family: 'Inter', system-ui, sans-serif;
  border-radius: 11px; cursor: pointer;
  transition: background-color .18s, border-color .18s, color .18s, opacity .18s, transform .15s;
}
.del-modal-btn:active  { transform: scale(.97); }
.del-modal-btn:disabled{ opacity: .55; cursor: not-allowed; transform: none; }

.del-modal-btn--cancel {
  background: #F9FAFB; border: 1.5px solid #D1D5DB; color: #374151;
}
.del-modal-btn--cancel:hover:not(:disabled) { background: #F3F4F6; border-color: #9CA3AF; }

html[data-theme="dark"] .del-modal-btn--cancel {
  background: #222535; border-color: #2A2D3E; color: #C4C8D6;
}
html[data-theme="dark"] .del-modal-btn--cancel:hover:not(:disabled) {
  background: #2A2D3E; border-color: #353849; color: #E8EAF0;
}

.del-modal-btn--confirm {
  background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
  border: none; color: #fff;
  box-shadow: 0 2px 12px rgba(244,63,94,.35);
}
.del-modal-btn--confirm:hover:not(:disabled) {
  filter: brightness(1.08);
  box-shadow: 0 4px 20px rgba(244,63,94,.50);
  transform: translateY(-1px);
}
html[data-theme="dark"] .del-modal-btn--confirm {
  box-shadow: 0 2px 16px rgba(244,63,94,.45);
}

/* ─── MODAL TRANSITION ── */
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
  transform: scale(.95) translateY(10px);
}
</style>