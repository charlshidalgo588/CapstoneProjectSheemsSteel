<template>
  <Layout title="Suppliers">
    <div ref="pageRef" class="sup-page">

      <!-- ── PAGE HEADER ── -->
      <header ref="headerRef" class="page-header">
        <div class="page-heading">
          <h1 class="page-title">Suppliers</h1>
          <p class="page-sub">
            <span v-if="loading">Loading…</span>
            <span v-else>{{ suppliers.length }} {{ suppliers.length === 1 ? 'supplier' : 'suppliers' }} in your directory</span>
          </p>
        </div>
        <RouterLink to="/suppliers/create" class="btn btn--primary">
          <i class="fa-solid fa-plus"></i> Add Supplier
        </RouterLink>
      </header>

      <!-- ── ALERTS ── -->
      <transition name="alert-fade">
        <div v-if="successMessage" class="alert alert--success" role="status">
          <i class="fa-solid fa-circle-check"></i>
          <span>{{ successMessage }}</span>
          <button type="button" class="alert-close" aria-label="Dismiss" @click="successMessage = ''">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </transition>
      <transition name="alert-fade">
        <div v-if="errorMessage" class="alert alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>{{ errorMessage }}</span>
          <button v-if="loadFailed" type="button" class="link-btn" @click="loadSuppliers">Try again</button>
          <button type="button" class="alert-close" aria-label="Dismiss" @click="errorMessage = ''">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </transition>

      <!-- ── TABLE CARD ── -->
      <section class="table-card" aria-label="Supplier list">

        <!-- Toolbar: swaps to a selection bar while suppliers are selected -->
        <div class="toolbar" :class="{ 'toolbar--selecting': selectedCount > 0 }">

          <!-- Selection bar -->
          <div v-if="selectedCount > 0" class="selbar" role="region" aria-label="Selection actions">
            <p class="selbar-count" aria-live="polite">
              {{ selectedCount }} selected
            </p>
            <button
              v-if="!allSelected && filtered.length > selectedCount"
              type="button"
              class="selbar-link"
              @click="selectAllResults"
            >
              Select all {{ filtered.length }}{{ search ? ' results' : '' }}
            </button>

            <div class="selbar-actions">
              <button type="button" class="btn btn--ghost btn--sm" @click="clearSelection">Clear</button>
              <button type="button" class="btn btn--danger btn--sm" @click="promptBulkDelete($event)">
                <i class="fa-solid fa-trash"></i>
                Delete {{ selectedCount === 1 ? 'selected' : selectedCount }}
              </button>
            </div>
          </div>

          <!-- Search + count + add -->
          <template v-else>
            <label class="search" :class="{ 'search--filled': !!search }">
              <i class="fa-solid fa-magnifying-glass search-ico"></i>
              <input
                v-model="search"
                type="search"
                class="search-input"
                placeholder="Search by name, phone, email or address"
                aria-label="Search suppliers"
                autocomplete="off"
              />
              <button v-if="search" type="button" class="search-clear" aria-label="Clear search" @click="search = ''">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </label>

            <div class="toolbar-right">
              <!-- Phones hide the table header, so select-all lives here instead -->
              <label v-if="pageRows.length" class="mobile-select">
                <span class="chk">
                  <input
                    type="checkbox"
                    class="chk-input"
                    :checked="pageAllSelected"
                    :indeterminate="pageSomeSelected"
                    aria-label="Select all suppliers on this page"
                    @change="togglePage"
                  />
                </span>
                Select all
              </label>

              <span v-if="!loading && suppliers.length" class="result-count" aria-live="polite">
                {{ filtered.length === suppliers.length
                  ? `${suppliers.length} total`
                  : `${filtered.length} of ${suppliers.length}` }}
              </span>

              <!-- Shows only after the page header has scrolled out of view -->
              <RouterLink
                v-if="headerOut"
                to="/suppliers/create"
                class="btn btn--primary btn--sm toolbar-add"
              >
                <i class="fa-solid fa-plus"></i> Add Supplier
              </RouterLink>
            </div>
          </template>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="th-select">
                  <label class="chk">
                    <input
                      type="checkbox"
                      class="chk-input"
                      :checked="pageAllSelected"
                      :indeterminate="pageSomeSelected"
                      :disabled="!pageRows.length"
                      aria-label="Select all suppliers on this page"
                      title="Select all on this page"
                      @change="togglePage"
                    />
                  </label>
                </th>
                <th :aria-sort="sortDir === 'asc' ? 'ascending' : 'descending'">
                  <button type="button" class="th-sort" @click="toggleSort">
                    Supplier
                    <i class="fa-solid" :class="sortDir === 'asc' ? 'fa-arrow-up-a-z' : 'fa-arrow-down-z-a'"></i>
                  </button>
                </th>
                <th>Contact</th>
                <th>Email</th>
                <th>Address</th>
                <th class="th-actions">Actions</th>
              </tr>
            </thead>

            <tbody v-if="loading && !suppliers.length">
              <tr v-for="n in 5" :key="'sk' + n" class="skeleton-row" aria-hidden="true">
                <td class="td-select"><div class="sk sk--chk"></div></td>
                <td><div class="sk sk--name"></div></td>
                <td><div class="sk sk--md"></div></td>
                <td><div class="sk sk--lg"></div></td>
                <td><div class="sk sk--lg"></div></td>
                <td><div class="sk sk--btn"></div></td>
              </tr>
            </tbody>

            <tbody v-else>
              <tr
                v-for="(supplier, i) in pageRows"
                :key="supplier.SupplierID"
                :class="{ 'row--selected': isSelected(supplier.SupplierID) }"
              >
                <td class="td-select">
                  <label class="chk">
                    <input
                      type="checkbox"
                      class="chk-input"
                      :checked="isSelected(supplier.SupplierID)"
                      :aria-label="`Select ${supplier.SupplierName}`"
                      @click="toggleRow(supplier, i, $event)"
                    />
                  </label>
                </td>

                <td data-label="Supplier" class="td-name">
                  <div class="cell-name">
                    <div class="cell-avatar" :style="avatarStyle(supplier.SupplierName)">
                      {{ initial(supplier.SupplierName) }}
                    </div>
                    <span class="cell-primary" :title="supplier.SupplierName">{{ supplier.SupplierName }}</span>
                  </div>
                </td>

                <td data-label="Contact" class="td-contact">
                  <a v-if="supplier.ContactNumber" :href="`tel:${telHref(supplier.ContactNumber)}`" class="cell-link">
                    <i class="fa-solid fa-phone cell-icon"></i>{{ supplier.ContactNumber }}
                  </a>
                  <span v-else class="cell-empty">—</span>
                </td>

                <td data-label="Email" class="td-email">
                  <a v-if="supplier.Email" :href="`mailto:${supplier.Email}`" class="cell-link" :title="supplier.Email">
                    <i class="fa-regular fa-envelope cell-icon"></i><span class="cell-ellipsis">{{ supplier.Email }}</span>
                  </a>
                  <span v-else class="cell-empty">—</span>
                </td>

                <td data-label="Address" class="td-address">
                  <span v-if="supplier.Address" class="cell-address-text" :title="supplier.Address">{{ supplier.Address }}</span>
                  <span v-else class="cell-empty">—</span>
                </td>

                <td class="td-actions">
                  <div class="row-actions">
                    <RouterLink
                      :to="`/suppliers/${supplier.SupplierID}/edit`"
                      class="row-btn row-btn--edit"
                      :aria-label="`Edit ${supplier.SupplierName}`"
                    >
                      <i class="fa-solid fa-pen-to-square"></i>
                      <span>Edit</span>
                    </RouterLink>
                    <button
                      type="button"
                      class="row-btn row-btn--delete"
                      :aria-label="`Delete ${supplier.SupplierName}`"
                      @click="promptDelete(supplier, $event)"
                    >
                      <i class="fa-solid fa-trash"></i>
                      <span>Delete</span>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Directory is empty -->
              <tr v-if="!suppliers.length && !loadFailed" class="row-empty">
                <td colspan="6" class="td-empty">
                  <div class="empty-state">
                    <i class="fa-solid fa-truck-field"></i>
                    <p class="empty-title">No suppliers yet</p>
                    <p class="empty-sub">Add the businesses you buy stock from so you can keep their details in one place.</p>
                    <RouterLink to="/suppliers/create" class="btn btn--primary btn--sm">
                      <i class="fa-solid fa-plus"></i> Add First Supplier
                    </RouterLink>
                  </div>
                </td>
              </tr>

              <!-- Nothing matches the search -->
              <tr v-else-if="suppliers.length && !filtered.length" class="row-empty">
                <td colspan="6" class="td-empty">
                  <div class="empty-state">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <p class="empty-title">No matches for “{{ search }}”</p>
                    <p class="empty-sub">Check the spelling or try a different word.</p>
                    <button type="button" class="btn btn--ghost btn--sm" @click="search = ''">Clear search</button>
                  </div>
                </td>
              </tr>

              <!-- Load failed -->
              <tr v-else-if="loadFailed && !suppliers.length" class="row-empty">
                <td colspan="6" class="td-empty">
                  <div class="empty-state">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                    <p class="empty-title">Couldn't load suppliers</p>
                    <p class="empty-sub">Check your connection and try again.</p>
                    <button type="button" class="btn btn--ghost btn--sm" @click="loadSuppliers">
                      <i class="fa-solid fa-rotate"></i> Try again
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── PAGINATION (10 suppliers per page) ── -->
        <nav v-if="filtered.length" class="pager" aria-label="Supplier pages">
          <p class="pager-info" aria-live="polite">
            Showing <strong>{{ rangeStart }}–{{ rangeEnd }}</strong> of <strong>{{ filtered.length }}</strong>
          </p>

          <div v-if="totalPages > 1" class="pager-controls">
            <button
              type="button"
              class="pg-btn pg-btn--nav"
              :disabled="page === 1"
              aria-label="Previous page"
              @click="goToPage(page - 1)"
            >
              <i class="fa-solid fa-chevron-left"></i>
              <span class="pg-nav-text">Prev</span>
            </button>

            <template v-for="(p, i) in pageItems" :key="'p' + i">
              <span v-if="p === '…'" class="pg-gap" aria-hidden="true">…</span>
              <button
                v-else
                type="button"
                class="pg-btn pg-btn--num"
                :class="{ 'pg-btn--active': p === page }"
                :aria-current="p === page ? 'page' : undefined"
                :aria-label="`Page ${p}`"
                @click="goToPage(p)"
              >{{ p }}</button>
            </template>

            <button
              type="button"
              class="pg-btn pg-btn--nav"
              :disabled="page === totalPages"
              aria-label="Next page"
              @click="goToPage(page + 1)"
            >
              <span class="pg-nav-text">Next</span>
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>
        </nav>
      </section>

    </div>

    <!-- ══════════════════════════════════════
         DELETE CONFIRMATION MODAL (one or many)
    ══════════════════════════════════════ -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div
          v-if="showDeleteModal"
          class="modal-backdrop"
          @click.self="cancelDelete"
        >
          <div class="del-modal" role="alertdialog" aria-modal="true" aria-labelledby="del-modal-title" aria-describedby="del-modal-desc">

            <div class="del-modal-icon-ring">
              <div class="del-modal-icon">
                <i class="fa-solid fa-trash-can"></i>
              </div>
            </div>

            <div class="del-modal-body">
              <h3 class="del-modal-title" id="del-modal-title">
                {{ isBulk ? `Delete ${deleteTargets.length} suppliers?` : 'Delete supplier?' }}
              </h3>

              <p v-if="!isBulk" class="del-modal-desc" id="del-modal-desc">
                You are about to permanently delete
                <strong class="del-modal-name">{{ firstTarget?.SupplierName }}</strong>.
                This action cannot be undone and will remove all associated records.
              </p>
              <p v-else class="del-modal-desc" id="del-modal-desc">
                You are about to permanently delete
                <strong class="del-modal-name">{{ deleteTargets.length }} suppliers</strong>.
                This action cannot be undone and will remove all of their associated records.
              </p>

              <!-- One supplier: detail chip -->
              <div v-if="!isBulk" class="del-modal-chip">
                <div class="del-modal-chip-avatar">
                  {{ initial(firstTarget?.SupplierName) }}
                </div>
                <div class="del-modal-chip-info">
                  <p class="del-modal-chip-name">{{ firstTarget?.SupplierName }}</p>
                  <p class="del-modal-chip-meta">
                    {{ firstTarget?.Email || firstTarget?.ContactNumber || 'No contact info' }}
                  </p>
                </div>
              </div>

              <!-- Several suppliers: compact list -->
              <ul v-else class="del-modal-list" aria-label="Suppliers to delete">
                <li v-for="s in deleteTargets" :key="s.SupplierID" class="del-modal-list-item">
                  <span class="del-modal-list-avatar">{{ initial(s.SupplierName) }}</span>
                  <span class="del-modal-list-name">{{ s.SupplierName }}</span>
                </li>
              </ul>
            </div>

            <div class="del-modal-footer">
              <button
                ref="cancelBtnRef"
                type="button"
                class="del-modal-btn del-modal-btn--cancel"
                @click="cancelDelete"
                :disabled="deleting"
              >
                Cancel
              </button>
              <button
                type="button"
                class="del-modal-btn del-modal-btn--confirm"
                @click="confirmDelete"
                :disabled="deleting"
              >
                <i v-if="deleting" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-trash-can"></i>
                {{ deleting ? 'Deleting…' : (isBulk ? `Yes, Delete ${deleteTargets.length}` : 'Yes, Delete') }}
              </button>
            </div>

          </div>
        </div>
      </transition>
    </Teleport>
  </Layout>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick, onBeforeUnmount } from 'vue'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const pageRef        = ref(null)
const headerRef      = ref(null)
const headerOut      = ref(false)   // true once the page header has scrolled out of view
let   headerObserver = null
const suppliers      = ref([])
const loading        = ref(true)
const loadFailed     = ref(false)
const successMessage = ref('')
const errorMessage   = ref('')

/* ── search + sort ── */
const search  = ref('')
const sortDir = ref('asc') // 'asc' | 'desc' by supplier name

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  const list = q
    ? suppliers.value.filter((s) =>
        [s.SupplierName, s.ContactNumber, s.Email, s.Address]
          .some((v) => String(v ?? '').toLowerCase().includes(q))
      )
    : [...suppliers.value]

  list.sort((a, b) =>
    String(a.SupplierName ?? '').localeCompare(String(b.SupplierName ?? ''), undefined, { sensitivity: 'base' })
  )
  return sortDir.value === 'asc' ? list : list.reverse()
})

function toggleSort() {
  sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
}

/* ── pagination: 10 suppliers per page ── */
const PAGE_SIZE = 10
const page = ref(1)

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE)))
const pageRows   = computed(() => filtered.value.slice((page.value - 1) * PAGE_SIZE, page.value * PAGE_SIZE))
const rangeStart = computed(() => (filtered.value.length ? (page.value - 1) * PAGE_SIZE + 1 : 0))
const rangeEnd   = computed(() => Math.min(page.value * PAGE_SIZE, filtered.value.length))

// Page buttons: 1 … 4 5 6 … 12 (always shows first, last and the pages around the current one).
const pageItems = computed(() => {
  const total = totalPages.value
  const cur = page.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const items = [1]
  const start = Math.max(2, cur - 1)
  const end   = Math.min(total - 1, cur + 1)
  if (start > 2) items.push('…')
  for (let p = start; p <= end; p++) items.push(p)
  if (end < total - 1) items.push('…')
  items.push(total)
  return items
})

function goToPage(p) {
  page.value = Math.min(Math.max(1, p), totalPages.value)
}

// A new search or sort order starts again from page 1.
watch([search, sortDir], () => { page.value = 1 })
// After a delete (or reload) the current page may no longer exist.
watch(totalPages, (n) => { if (page.value > n) page.value = n })

/* ── selection (survives page changes, limited to what the search shows) ── */
const selected = ref(new Set())   // SupplierIDs
const lastIdx  = ref(null)        // last clicked row on this page, for Shift+click ranges

const isSelected = (id) => selected.value.has(id)

function setSelected(ids, on) {
  const next = new Set(selected.value)
  ids.forEach((id) => (on ? next.add(id) : next.delete(id)))
  selected.value = next
}

// Only suppliers that are currently in the (filtered) list count, so a bulk
// delete can never touch a row the person can't see.
const selectedSuppliers = computed(() => filtered.value.filter((s) => selected.value.has(s.SupplierID)))
const selectedCount     = computed(() => selectedSuppliers.value.length)
const allSelected       = computed(() => filtered.value.length > 0 && selectedCount.value === filtered.value.length)
const pageAllSelected   = computed(() => pageRows.value.length > 0 && pageRows.value.every((s) => isSelected(s.SupplierID)))
const pageSomeSelected  = computed(() => !pageAllSelected.value && pageRows.value.some((s) => isSelected(s.SupplierID)))

function toggleRow(supplier, index, evt) {
  const on = evt.target.checked
  if (evt.shiftKey && lastIdx.value !== null) {
    const from = Math.min(lastIdx.value, index)
    const to   = Math.max(lastIdx.value, index)
    setSelected(pageRows.value.slice(from, to + 1).map((s) => s.SupplierID), on)
  } else {
    setSelected([supplier.SupplierID], on)
  }
  lastIdx.value = index
}

function togglePage() {
  setSelected(pageRows.value.map((s) => s.SupplierID), !pageAllSelected.value)
}

function selectAllResults() {
  setSelected(filtered.value.map((s) => s.SupplierID), true)
}

function clearSelection() {
  selected.value = new Set()
  lastIdx.value = null
}

// Narrowing the search drops selections that are no longer visible.
watch(filtered, (list) => {
  if (!selected.value.size) return
  const visible = new Set(list.map((s) => s.SupplierID))
  const keep = [...selected.value].filter((id) => visible.has(id))
  if (keep.length !== selected.value.size) selected.value = new Set(keep)
})
watch([page, search, sortDir], () => { lastIdx.value = null })

/* ── display helpers ── */
const initial = (name) => String(name ?? '?').trim().charAt(0).toUpperCase() || '?'
const telHref = (n) => String(n ?? '').replace(/[^\d+]/g, '')

// A stable soft colour per supplier so rows are easier to tell apart.
const AVATAR_HUES = [22, 160, 215, 265, 330, 40, 190]
function avatarStyle(name) {
  const s = String(name ?? '')
  let h = 0
  for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0
  const hue = AVATAR_HUES[h % AVATAR_HUES.length]
  return {
    background: `hsl(${hue} 85% 55% / 0.14)`,
    color: `hsl(${hue} 70% 42%)`,
    borderColor: `hsl(${hue} 85% 55% / 0.30)`,
  }
}

/* ── load ── */
async function loadSuppliers() {
  loading.value = true
  loadFailed.value = false
  try {
    const res = await api.get('/api/suppliers')
    suppliers.value = res.data.suppliers ?? []
    if (errorMessage.value === 'Failed to load suppliers.') errorMessage.value = ''
  } catch {
    loadFailed.value = true
    errorMessage.value = 'Failed to load suppliers.'
  } finally {
    loading.value = false
  }
}

/* ── delete modal (one supplier from a row, or the whole selection) ── */
const showDeleteModal = ref(false)
const deleteTargets   = ref([])
const deleting        = ref(false)
const cancelBtnRef    = ref(null)
let   lastTrigger     = null // button that opened the modal, so focus can go back to it

const isBulk      = computed(() => deleteTargets.value.length > 1)
const firstTarget = computed(() => deleteTargets.value[0] ?? null)

function openDeleteModal(targets, evt) {
  if (!targets.length) return
  lastTrigger = evt?.currentTarget ?? null
  deleteTargets.value   = targets
  showDeleteModal.value = true
  // Focus the safe option first so Enter never deletes by accident.
  nextTick(() => cancelBtnRef.value && cancelBtnRef.value.focus())
}

const promptDelete     = (supplier, evt) => openDeleteModal([supplier], evt)
const promptBulkDelete = (evt) => openDeleteModal([...selectedSuppliers.value], evt)

function cancelDelete() {
  if (deleting.value) return
  showDeleteModal.value = false
  nextTick(() => {
    deleteTargets.value = []
    if (lastTrigger && document.contains(lastTrigger)) lastTrigger.focus()
  })
}

async function confirmDelete() {
  if (!deleteTargets.value.length || deleting.value) return
  const targets = [...deleteTargets.value]
  deleting.value = true

  const deleted = []
  const failed  = []

  try {
    // No bulk endpoint needed: delete in small batches so a big selection
    // doesn't fire dozens of requests at once.
    const BATCH = 5
    for (let i = 0; i < targets.length; i += BATCH) {
      const batch = targets.slice(i, i + BATCH)
      const results = await Promise.allSettled(
        batch.map((t) => api.delete(`/api/suppliers/${t.SupplierID}`, { withCredentials: true }))
      )
      results.forEach((r, idx) => {
        if (r.status === 'fulfilled') deleted.push(batch[idx])
        else failed.push({ supplier: batch[idx], reason: r.reason })
      })
    }
  } finally {
    deleting.value = false
  }

  // Remove what worked right away so rows disappear without waiting for the reload.
  if (deleted.length) {
    const gone = new Set(deleted.map((t) => t.SupplierID))
    suppliers.value = suppliers.value.filter((s) => !gone.has(s.SupplierID))
    setSelected([...gone], false)

    successMessage.value = deleted.length === 1
      ? `“${deleted[0].SupplierName}” was deleted.`
      : `${deleted.length} suppliers were deleted.`
    setTimeout(() => { successMessage.value = '' }, 5000)
  }

  if (failed.length) {
    // Failed ones stay selected so the person can simply try again.
    errorMessage.value = failed.length === 1
      ? (failed[0].reason?.response?.data?.message || `Couldn't delete “${failed[0].supplier.SupplierName}”. Please try again.`)
      : `${failed.length} suppliers couldn't be deleted. They're still selected so you can try again.`
  } else {
    errorMessage.value = ''
  }

  showDeleteModal.value = false
  nextTick(() => { deleteTargets.value = [] })
  if (deleted.length) loadSuppliers()
}

/* ── sticky offset ──
   Find the element that scrolls the page and cancel its top padding, so the
   sticky search bar sits flush against the top bar with nothing showing above it. */
function findScroller(el) {
  let p = el?.parentElement
  while (p && p !== document.documentElement) {
    const oy = getComputedStyle(p).overflowY
    if (oy === 'auto' || oy === 'scroll' || oy === 'overlay') return p
    p = p.parentElement
  }
  return null
}
function syncStickyTop() {
  if (!pageRef.value) return
  const scroller = findScroller(pageRef.value)
  const pad = scroller ? parseFloat(getComputedStyle(scroller).paddingTop) || 0 : 0
  pageRef.value.style.setProperty('--sticky-top', `${-pad}px`)
}

function onKeydown(e) {
  if (e.key !== 'Escape') return
  if (showDeleteModal.value) { cancelDelete(); return }
  const tag = e.target?.tagName
  if (selectedCount.value && tag !== 'INPUT' && tag !== 'TEXTAREA') clearSelection()
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  window.addEventListener('resize', syncStickyTop)
  nextTick(syncStickyTop)

  // Watch the page header: once it scrolls away, the sticky toolbar offers Add Supplier.
  if (headerRef.value && 'IntersectionObserver' in window) {
    headerObserver = new IntersectionObserver(
      ([entry]) => { headerOut.value = !entry.isIntersecting },
      { threshold: 0 }
    )
    headerObserver.observe(headerRef.value)
  }

  loadSuppliers()
})
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  window.removeEventListener('resize', syncStickyTop)
  headerObserver?.disconnect()
})
</script>

<style scoped>
/* ── TOKEN BRIDGE ── */
.sup-page {
  --s-bg:             var(--c-bg);
  --s-surface:        var(--c-surface);
  --s-surface-raised: var(--c-surface-raised);
  --s-surface-sunken: var(--c-surface-sunken);
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
  --s-red-soft:       rgba(244,63,94,0.10);
  --s-blue:           #3b82f6;
  --s-blue-soft:      rgba(59,130,246,0.12);
  --radius:           16px;
  --s-ease:           cubic-bezier(.16, 1, .3, 1);

  /* ── SPACE TUNING (same values as Home, Reports and Inventory Logs) ──
     The app shell (Layout.vue) adds roughly 64px of its own padding around
     every page. --bleed-* lets this page reclaim part of it on desktop.
     Raise a value to push the content closer to the edge, lower it (or set
     it to 0px) if content ever touches the sidebar or top bar.
     --bleed-b reclaims the shell's bottom padding, which is what stops a
     short page from showing a scrollbar. */
  --bleed-x: 30px;
  --bleed-y: 26px;
  --bleed-b: 26px;
  /* --sticky-top: where the search bar sticks. The app shell pads the scrolling area,
     and sticky elements stick at the inside edge of that padding, which left a gap
     above the bar. The page measures that padding on load / resize and cancels it
     (see syncStickyTop); -32px is only the starting value.
     --toolbar-h: height of the search/selection bar; the column titles stick right below it. */
  --sticky-top: -32px;
  --toolbar-h: 67px;
  --s-gap: 16px;

  /* Height follows the content: no min-height, so a short list never forces a scrollbar.
     border-box keeps the padding inside the width. */
  box-sizing: border-box;
  background: var(--s-bg);
  padding: 16px 24px 24px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--s-gap);
  transition: background-color .22s ease;
}
.sup-page *, .sup-page *::before, .sup-page *::after { box-sizing: border-box; }

.sup-page button:focus-visible,
.sup-page a:focus-visible,
.sup-page input:focus-visible { outline: 2px solid var(--s-accent); outline-offset: 2px; }

/* ── PAGE HEADER ── */
.page-header {
  display: flex; align-items: flex-end;
  justify-content: space-between; gap: 14px; flex-wrap: wrap;
}
.page-title {
  font-size: 26px; font-weight: 800; color: var(--s-text-primary);
  letter-spacing: -.03em; margin: 0; transition: color .22s;
}
.page-sub { margin: 6px 0 0; font-size: 14px; font-weight: 600; color: var(--s-text-secondary); }

/* ── BUTTONS ── */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 11px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s, filter .18s;
}
.btn:active { transform: scale(.98); }
.btn--primary {
  background: linear-gradient(135deg, var(--s-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--ghost {
  background: var(--s-surface-sunken); border-color: var(--s-border-strong); color: var(--s-text-secondary);
}
.btn--ghost:hover { background: var(--s-surface-raised); border-color: var(--s-accent-border); color: var(--s-text-primary); }
.btn--danger {
  background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
  color: #fff; box-shadow: 0 2px 10px rgba(244,63,94,.30);
}
.btn--danger:hover { filter: brightness(1.08); box-shadow: 0 4px 16px rgba(244,63,94,.42); transform: translateY(-1px); }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }

.link-btn {
  border: none; background: transparent; cursor: pointer;
  font-family: inherit; font-size: 13px; font-weight: 700;
  color: inherit; text-decoration: underline; padding: 4px 6px; border-radius: 8px;
}

/* ── ALERTS ── */
.alert {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-radius: 12px;
  font-size: 13.5px; font-weight: 500; border: 1px solid transparent;
}
.alert span { flex: 1; min-width: 0; }
.alert--success { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); color: var(--s-green); }
.alert--error   { background: rgba(244,63,94,.09);  border-color: rgba(244,63,94,.22);  color: var(--s-red); }
html[data-theme="dark"] .alert--success { background: rgba(16,185,129,.14); color: #4ADE80; }
html[data-theme="dark"] .alert--error   { background: rgba(244,63,94,.14);  color: #F87171; }
.alert-close {
  background: none; border: none; cursor: pointer;
  color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 4px; transition: opacity .15s;
}
.alert-close:hover { opacity: 1; }
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── TABLE CARD ── */
.table-card {
  background: var(--s-surface); border: 1px solid var(--s-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm);
  overflow: hidden; overflow: clip; /* clip: rounded corners without breaking position: sticky */
  transition: background-color .22s, border-color .22s;
}

/* Toolbar */
.toolbar {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  padding: 14px 16px; border-bottom: 1px solid var(--s-border);
  min-height: 67px; /* same height with or without a selection, so nothing jumps */
  transition: background-color .18s ease, border-color .18s ease;
}
.toolbar--selecting { background: var(--s-accent-soft); border-bottom-color: var(--s-accent-border); }
.search {
  position: relative; display: flex; align-items: center;
  flex: 1 1 280px; max-width: 420px; min-width: 0;
}
.search-ico { position: absolute; left: 13px; font-size: 12px; color: var(--s-text-faint); pointer-events: none; }
.search-input {
  width: 100%; height: 38px; padding: 0 36px 0 36px;
  font-family: inherit; font-size: 13.5px; font-weight: 500;
  color: var(--s-text-primary);
  background: var(--s-surface-sunken);
  border: 1.5px solid var(--s-border-strong); border-radius: 11px;
  transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
}
.search-input::placeholder { color: var(--s-text-faint); font-weight: 500; }
.search-input::-webkit-search-cancel-button { display: none; }
.search-input:hover { border-color: var(--s-accent-border); }
.search-input:focus { outline: none; border-color: var(--s-accent); background: var(--s-surface); box-shadow: 0 0 0 3px var(--s-accent-soft); }
.search-clear {
  position: absolute; right: 7px; width: 24px; height: 24px; border-radius: 7px;
  border: none; background: transparent; cursor: pointer;
  color: var(--s-text-muted); font-size: 11px;
  display: inline-flex; align-items: center; justify-content: center;
}
.search-clear:hover { background: var(--s-surface-raised); color: var(--s-text-primary); }
.toolbar-right { display: flex; align-items: center; gap: 10px; margin-left: auto; }
.result-count { font-size: 12.5px; font-weight: 600; color: var(--s-text-muted); white-space: nowrap; }
/* Compact Add Supplier in the sticky toolbar: hidden by default, shown on desktop/tablet below */
.toolbar-add { display: none; white-space: nowrap; }

/* ── SELECTION BAR ── */
.selbar { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; flex-wrap: wrap; }
.selbar-count { margin: 0; font-size: 14px; font-weight: 800; color: var(--s-text-primary); letter-spacing: -.01em; font-variant-numeric: tabular-nums; }
.selbar-link {
  border: none; background: transparent; cursor: pointer; padding: 4px 6px; border-radius: 7px;
  font-family: inherit; font-size: 13px; font-weight: 700; color: var(--s-accent);
  text-decoration: underline; text-underline-offset: 3px;
}
.selbar-link:hover { background: var(--s-surface); }
.selbar-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }

/* ── CHECKBOXES ── */
.chk { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; margin: -8px 0 -8px -8px; cursor: pointer; flex-shrink: 0; }
.chk-input {
  -webkit-appearance: none; appearance: none;
  width: 18px; height: 18px; margin: 0; cursor: pointer;
  border: 1.5px solid var(--s-border-strong); border-radius: 6px;
  background: var(--s-surface-sunken) center / 12px 12px no-repeat;
  transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
}
.chk-input:hover:not(:disabled) { border-color: var(--s-accent); }
.chk-input:checked,
.chk-input:indeterminate {
  background-color: var(--s-accent); border-color: var(--s-accent);
}
.chk-input:checked {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath d='M2.4 6.4l2.4 2.4 4.8-5.2' fill='none' stroke='white' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
}
.chk-input:indeterminate:not(:checked) {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath d='M2.8 6h6.4' fill='none' stroke='white' stroke-width='1.9' stroke-linecap='round'/%3E%3C/svg%3E");
}
.chk-input:disabled { opacity: .45; cursor: not-allowed; }

.mobile-select { display: none; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 600; color: var(--s-text-secondary); cursor: pointer; }
.mobile-select .chk { margin: 0; width: 22px; height: 22px; }

.table-wrap { overflow: hidden; overflow: clip; }

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
/* Select, Contact and Actions have fixed widths; Supplier, Email and Address
   share whatever space is left, so the table always fits with no sideways scroll. */
.data-table thead th:nth-child(1) { width: 52px; padding-right: 0; }
.data-table thead th:nth-child(3) { width: 172px; }
.data-table thead th:nth-child(6) { width: 196px; }
.th-actions { text-align: right !important; }
.th-select { vertical-align: middle; }

.th-sort {
  display: inline-flex; align-items: center; gap: 7px;
  border: none; background: transparent; cursor: pointer; padding: 0;
  font: inherit; letter-spacing: inherit; text-transform: inherit; color: inherit;
  transition: color .15s ease;
}
.th-sort i { font-size: 10px; color: var(--s-accent); }
.th-sort:hover { color: var(--s-text-primary); }

.data-table tbody tr { transition: background-color .15s; }
.data-table tbody tr:not(.row-empty):not(.skeleton-row):hover td { background: var(--s-surface-raised); }
.data-table tbody td {
  padding: 14px 16px; border-bottom: 1px solid var(--s-border);
  vertical-align: middle; transition: background-color .15s, border-color .22s, box-shadow .15s;
}
.data-table tbody td.td-select { padding-right: 0; }
.data-table tbody tr:last-child td { border-bottom: none; }

/* Selected rows */
.data-table tbody tr.row--selected:not(.row-empty):not(.skeleton-row) td { background: var(--s-accent-soft); }
.data-table tbody tr.row--selected:not(.row-empty):not(.skeleton-row):hover td { background: var(--s-accent-soft); filter: brightness(.98); }
.data-table tbody tr.row--selected td:first-child { box-shadow: inset 3px 0 0 var(--s-accent); }

/* ── CELLS ── */
.cell-name     { display: flex; align-items: center; gap: 11px; min-width: 0; }
.cell-avatar   {
  width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
  background: var(--s-accent-soft); color: var(--s-accent);
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 800; border: 1px solid var(--s-accent-border);
  transition: background-color .22s, border-color .22s;
}
.cell-primary  { font-weight: 600; color: var(--s-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; transition: color .22s; }

.td-contact, .td-email { color: var(--s-text-muted); white-space: nowrap; }
.cell-link {
  display: inline-flex; align-items: center; gap: 8px; max-width: 100%;
  color: var(--s-text-secondary); text-decoration: none; border-radius: 6px;
  transition: color .15s ease;
}
.cell-link:hover { color: var(--s-accent); text-decoration: underline; }
.cell-icon { font-size: 11px; color: var(--s-text-faint); flex-shrink: 0; }
.cell-ellipsis { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
.td-address { color: var(--s-text-muted); transition: color .22s; }
.cell-address-text {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.45;
  overflow-wrap: anywhere;
}.cell-empty { color: var(--s-text-faint); font-size: 13px; }

/* ── ROW ACTIONS (direct buttons, no menu) ── */
.td-actions { text-align: right; }
.row-actions { display: inline-flex; align-items: center; justify-content: flex-end; gap: 8px; }
.row-btn {
  display: inline-flex; align-items: center; gap: 7px;
  height: 34px; padding: 0 11px; border-radius: 9px;
  font-family: inherit; font-size: 12.5px; font-weight: 700; line-height: 1;
  border: 1px solid transparent; cursor: pointer; text-decoration: none; white-space: nowrap;
  transition: background-color .15s ease, border-color .15s ease, color .15s ease, transform .15s var(--s-ease);
}
.row-btn i { font-size: 11.5px; }
.row-btn:active { transform: scale(.97); }
.row-btn--edit { color: var(--s-blue); background: var(--s-blue-soft); border-color: rgba(59,130,246,.22); }
.row-btn--edit:hover { background: rgba(59,130,246,.2); border-color: rgba(59,130,246,.4); }
.row-btn--delete { color: var(--s-red); background: var(--s-red-soft); border-color: rgba(244,63,94,.22); }
.row-btn--delete:hover { background: rgba(244,63,94,.2); border-color: rgba(244,63,94,.4); }
html[data-theme="dark"] .row-btn--edit   { color: #93C5FD; }
html[data-theme="dark"] .row-btn--delete { color: #F87171; }

/* ── SKELETON LOADING ── */
.sk {
  height: 14px; border-radius: 7px;
  background: linear-gradient(90deg, var(--s-surface-sunken) 25%, var(--s-surface-raised) 50%, var(--s-surface-sunken) 75%);
  background-size: 200% 100%;
  animation: sk-shimmer 1.3s ease-in-out infinite;
}
.sk--chk { width: 18px; height: 18px; border-radius: 6px; }
.sk--name { width: 70%; height: 36px; border-radius: 10px; }
.sk--md { width: 75%; }
.sk--lg { width: 90%; }
.sk--btn { width: 120px; height: 34px; border-radius: 9px; margin-left: auto; }
@keyframes sk-shimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }

/* ── EMPTY STATES ── */
.td-empty { padding: 56px 24px !important; border-bottom: none !important; text-align: center; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 10px; color: var(--s-text-faint); }
.empty-state > i { font-size: 34px; margin-bottom: 4px; }
.empty-title { font-size: 15px; font-weight: 700; color: var(--s-text-primary); margin: 0; }
.empty-sub { font-size: 13px; color: var(--s-text-muted); margin: 0 0 6px; max-width: 360px; line-height: 1.5; }

/* ── PAGINATION ── */
.pager {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  padding: 12px 16px; border-top: 1px solid var(--s-border);
  background: var(--s-surface);
}
.pager-info { margin: 0; font-size: 12.5px; color: var(--s-text-muted); }
.pager-info strong { color: var(--s-text-primary); font-weight: 700; font-variant-numeric: tabular-nums; }
.pager-controls { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.pg-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 7px;
  min-width: 34px; height: 34px; padding: 0 10px;
  font-family: inherit; font-size: 12.5px; font-weight: 700; font-variant-numeric: tabular-nums;
  color: var(--s-text-secondary);
  background: var(--s-surface-sunken);
  border: 1px solid var(--s-border-strong); border-radius: 9px; cursor: pointer;
  transition: background-color .15s ease, border-color .15s ease, color .15s ease;
}
.pg-btn i { font-size: 10px; }
.pg-btn:hover:not(:disabled):not(.pg-btn--active) { background: var(--s-accent-soft); border-color: var(--s-accent-border); color: var(--s-accent); }
.pg-btn:disabled { opacity: .4; cursor: not-allowed; }
.pg-btn--active { background: var(--s-accent); border-color: var(--s-accent); color: #fff; cursor: default; }
.pg-btn--nav { padding: 0 12px; }
.pg-gap { min-width: 22px; text-align: center; font-size: 13px; color: var(--s-text-faint); user-select: none; }

/* ── RESPONSIVE (same breakpoints as Home, Reports and Inventory Logs) ──
   Desktop only: pull the page outward into the shell's padding (top, left, right, bottom). */
@media (min-width: 1001px) {
  .sup-page { margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) calc(-1 * var(--bleed-b)); }
}
/* Sticky: the search / selection bar and the column titles stay visible while
   the page scrolls. Desktop and tablet only (phones show rows as cards). */
@media (min-width: 901px) {
  .toolbar {
    position: sticky; top: var(--sticky-top); z-index: 4;
    height: var(--toolbar-h); flex-wrap: nowrap;
    background: var(--s-surface);
  }
  .toolbar--selecting {
    background: linear-gradient(var(--s-accent-soft), var(--s-accent-soft)), var(--s-surface);
  }
  .toolbar-add { display: inline-flex; }
  .data-table thead th {
    position: sticky; top: calc(var(--sticky-top) + var(--toolbar-h)); z-index: 3;
    box-shadow: 0 1px 0 var(--s-border); /* border-collapse drops the border on stuck cells */
  }
}
/* Medium screens: row buttons become icon-only so the table still fits */
@media (min-width: 901px) and (max-width: 1180px) {
  .data-table thead th:nth-child(3) { width: 150px; }
  .data-table thead th:nth-child(6) { width: 100px; }
  .row-btn { width: 34px; padding: 0; justify-content: center; }
  .row-btn span { display: none; }
  .row-actions { gap: 6px; }
}
@media (max-width: 900px) {
  .sup-page { padding: 12px 14px 24px; --s-gap: 14px; }

  .mobile-select { display: inline-flex; }

  /* Each row becomes a labelled card */
  .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
  .data-table { table-layout: auto; }
  .data-table thead { display: none; }
  .data-table tbody { padding: 12px; }
  .data-table tbody tr:not(.row-empty) {
    position: relative;
    border: 1px solid var(--s-border); border-radius: 14px;
    padding: 6px 4px; margin-bottom: 12px; background: var(--s-surface);
  }
  .data-table tbody tr.row--selected:not(.row-empty) { border-color: var(--s-accent); background: var(--s-accent-soft); }
  .data-table tbody tr:last-child { margin-bottom: 0; }
  .data-table tbody td { border-bottom: none; padding: 8px 12px; }
  .data-table tbody tr.row--selected:not(.row-empty):not(.skeleton-row) td { background: transparent; }
  .data-table tbody tr.row--selected td:first-child { box-shadow: none; }
  .data-table tbody td[data-label]::before {
    content: attr(data-label);
    display: block; margin-bottom: 3px;
    font-size: 10.5px; font-weight: 700; letter-spacing: .07em;
    text-transform: uppercase; color: var(--s-text-faint);
  }
  .data-table tbody td.td-name::before { display: none; }
  .data-table tbody td.td-name { padding-right: 52px; }
  .data-table tbody td.td-select {
    position: absolute; top: 12px; right: 8px; width: auto; padding: 0;
    z-index: 1;
  }
  .data-table tbody td.td-select .chk { margin: 0; }
  .data-table tbody tr:hover td { background: transparent !important; }
  .td-actions { padding-top: 10px !important; border-top: 1px solid var(--s-border) !important; margin-top: 4px; }
  .row-actions { display: flex; width: 100%; }
  .row-btn { flex: 1; justify-content: center; height: 38px; }
  .skeleton-row td.td-select { display: none; }
  .skeleton-row td:nth-child(n+3) { display: none; }
  .sk--btn { margin-left: 0; width: 100%; }
}
@media (max-width: 700px) {
  .sup-page { padding: 16px 16px 32px; }
  .page-header { flex-direction: column; align-items: stretch; }
  .page-header .btn { justify-content: center; }
  .search { max-width: none; flex-basis: 100%; }
  .toolbar-right { width: 100%; justify-content: space-between; margin-left: 0; }
  .selbar-actions { width: 100%; margin-left: 0; }
  .selbar-actions .btn { flex: 1; justify-content: center; }
  .pager { flex-direction: column; align-items: stretch; text-align: center; }
  .pager-controls { justify-content: center; }
  .pg-nav-text { display: none; }
}

@media (prefers-reduced-motion: reduce) {
  .sup-page * { transition: none !important; }
  .sk { animation: none; }
}
</style>

<!-- ══════════════════════════════════════════════════════
     GLOBAL — Teleported delete modal
     Must be unscoped; class names are specific to avoid leaking.
══════════════════════════════════════════════════════ -->
<style>

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
  max-height: calc(100dvh - 40px);
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
  padding: 32px 0 0; flex-shrink: 0;
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
  min-height: 0;
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

/* ─── MULTI-SUPPLIER LIST ── */
.del-modal-list {
  list-style: none; margin: 6px 0 0; padding: 4px;
  width: 100%; max-height: 176px; overflow-y: auto;
  background: #F9FAFB;
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  text-align: left;
}
html[data-theme="dark"] .del-modal-list { background: #222535; border-color: #2A2D3E; }
.del-modal-list-item {
  display: flex; align-items: center; gap: 10px;
  padding: 7px 8px; border-radius: 8px;
}
.del-modal-list-avatar {
  width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0;
  background: rgba(244,63,94,.12); color: #f43f5e;
  border: 1px solid rgba(244,63,94,.20);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800;
}
html[data-theme="dark"] .del-modal-list-avatar {
  background: rgba(244,63,94,.18); color: #F87171; border-color: rgba(244,63,94,.30);
}
.del-modal-list-name {
  flex: 1; min-width: 0;
  font-size: 13px; font-weight: 600; color: #111827;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
html[data-theme="dark"] .del-modal-list-name { color: #E8EAF0; }

/* ─── FOOTER ACTIONS ── */
.del-modal-footer {
  display: flex; gap: 10px;
  padding: 0 28px 28px; flex-shrink: 0;
}
.del-modal-btn {
  flex: 1; height: 44px;
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 14px; font-weight: 600; font-family: 'Inter', system-ui, sans-serif;
  border-radius: 11px; cursor: pointer;
  transition: background-color .18s, border-color .18s, color .18s, opacity .18s, transform .15s;
}
.del-modal-btn:focus-visible { outline: 2px solid #f43f5e; outline-offset: 2px; }
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