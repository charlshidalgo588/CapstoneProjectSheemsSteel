<template>
  <Layout title="Categories">
    <div class="cat-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Inventory</p>
          <h1 class="page-title">Categories</h1>
        </div>
        <RouterLink to="/categories/create" class="btn btn--primary">
          <i class="fa-solid fa-plus"></i> Add Category
        </RouterLink>
      </div>

      <!-- ── TABLE CARD ── -->
      <div class="table-card">
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width:55%">Category Name</th>
                <th style="width:25%">Products Count</th>
                <th style="width:20%; text-align:right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories" :key="cat.CategoryID">
                <td>
                  <div class="cell-name">
                    <div class="cell-avatar">
                      <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <span class="cell-primary">{{ cat.CategoryName }}</span>
                  </div>
                </td>
                <td>
                  <span class="count-pill">
                    <i class="fa-solid fa-box"></i>
                    {{ cat.products_count ?? 0 }} product{{ (cat.products_count ?? 0) !== 1 ? 's' : '' }}
                  </span>
                </td>
                <td class="td-actions">
                  <button
                    :ref="setMenuButtonRef(cat.CategoryID)"
                    @click="toggleMenu(cat.CategoryID)"
                    class="menu-btn"
                    :class="{ 'menu-btn--active': openMenuId === cat.CategoryID }"
                  >
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                  </button>
                </td>
              </tr>

              <tr v-if="categories.length === 0">
                <td colspan="3" class="td-empty">
                  <div class="empty-state">
                    <i class="fa-solid fa-layer-group"></i>
                    <p>No categories yet</p>
                    <RouterLink to="/categories/create" class="btn btn--primary btn--sm">
                      <i class="fa-solid fa-plus"></i> Add First Category
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
        v-if="openMenuId && currentCategory"
        ref="dropdownRef"
        :style="dropdownStyle"
        class="cat-dropdown"
      >
        <RouterLink
          :to="`/categories/${currentCategory.CategoryID}/edit`"
          class="cat-dropdown-item"
          @click="closeMenu"
        >
          <span class="cat-dropdown-icon cat-dropdown-icon--blue">
            <i class="fa-solid fa-pen-to-square"></i>
          </span>
          Edit Category
        </RouterLink>
        <div class="cat-dropdown-divider"></div>
        <button class="cat-dropdown-item cat-dropdown-item--danger" @click="promptDelete(currentCategory)">
          <span class="cat-dropdown-icon cat-dropdown-icon--red">
            <i class="fa-solid fa-trash"></i>
          </span>
          Delete
        </button>
      </div>
    </Teleport>

    <!-- ── DELETE CONFIRMATION MODAL ── -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="showDeleteModal" class="modal-backdrop" @click.self="cancelDelete">
          <div class="del-modal">
            <div class="del-modal-icon-ring">
              <div class="del-modal-icon"><i class="fa-solid fa-trash-can"></i></div>
            </div>
            <div class="del-modal-body">
              <h3 class="del-modal-title">Delete Category</h3>
              <p class="del-modal-desc">
                You are about to permanently delete
                <strong class="del-modal-name">{{ categoryToDelete?.CategoryName }}</strong>.
                <span v-if="categoryToDelete?.products_count > 0">
                  This category has <strong>{{ categoryToDelete.products_count }}</strong> product(s) assigned to it.
                </span>
                This action cannot be undone.
              </p>
              <div class="del-modal-chip">
                <div class="del-modal-chip-avatar">
                  <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="del-modal-chip-info">
                  <p class="del-modal-chip-name">{{ categoryToDelete?.CategoryName }}</p>
                  <p class="del-modal-chip-meta">{{ categoryToDelete?.products_count ?? 0 }} products assigned</p>
                </div>
              </div>
            </div>
            <div class="del-modal-footer">
              <button class="del-modal-btn del-modal-btn--cancel" @click="cancelDelete" :disabled="deleting">Cancel</button>
              <button class="del-modal-btn del-modal-btn--confirm" @click="confirmDelete" :disabled="deleting">
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
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'
import { ref, onMounted, computed, nextTick, onBeforeUnmount } from 'vue'

const categories = ref([])

/* dropdown */
const openMenuId     = ref(null)
const dropdownRef    = ref(null)
const dropdownStyle  = ref({ position: 'fixed', top: '0px', left: '0px' })
const menuButtonRefs = new Map()

/* delete modal */
const showDeleteModal  = ref(false)
const categoryToDelete = ref(null)
const deleting         = ref(false)

const setMenuButtonRef = (id) => (el) => {
  if (el) menuButtonRefs.set(id, el)
  else    menuButtonRefs.delete(id)
}

onMounted(async () => {
  try {
    const res = await api.get('/api/categories')
    categories.value = res.data.categories
  } catch (err) {
    console.error('Failed to load categories', err)
  }
})

const currentCategory = computed(() =>
  categories.value.find((c) => c.CategoryID === openMenuId.value)
)

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
      left: `${rect.right - 176}px`,
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

function promptDelete(cat) {
  closeMenu()
  categoryToDelete.value = cat
  showDeleteModal.value  = true
}
function cancelDelete() {
  if (deleting.value) return
  showDeleteModal.value  = false
  categoryToDelete.value = null
}

async function confirmDelete() {
  if (!categoryToDelete.value || deleting.value) return
  deleting.value = true
  try {
    await api.delete(`/api/categories/${categoryToDelete.value.CategoryID}`, { withCredentials: true })
    categories.value = categories.value.filter(c => c.CategoryID !== categoryToDelete.value.CategoryID)
    showDeleteModal.value  = false
    categoryToDelete.value = null
  } catch (err) {
    console.error('Delete failed:', err)
    alert('Could not delete category.')
  } finally {
    deleting.value = false
  }
}
</script>

<style scoped>
/* ── TOKEN BRIDGE ── */
.cat-page {
  --c-bg-local:        var(--c-bg);
  --c-surface-local:   var(--c-surface);
  --c-surface-raised-local: var(--c-surface-raised);
  --c-border-local:    var(--c-border);
  --c-border-strong-local: var(--c-border-strong);
  --c-text-primary-local:   var(--c-text-primary);
  --c-text-secondary-local: var(--c-text-secondary);
  --c-text-muted-local:     var(--c-text-muted);
  --c-text-faint-local:     var(--c-text-faint);
  --c-accent-local:        var(--c-accent);
  --c-accent-soft-local:   var(--c-accent-soft);
  --c-accent-border-local: var(--c-accent-border);
  --radius: 16px;

  min-height: 100%;
  background: var(--c-bg-local);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 20px;
  transition: background-color .22s ease;
}

.page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--c-accent-local); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--c-text-primary-local); letter-spacing: -.03em; margin: 0; transition: color .22s; }

.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn--primary {
  background: linear-gradient(135deg, var(--c-accent-local) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }

.table-card {
  background: var(--c-surface-local); border: 1px solid var(--c-border-local);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.table-wrap { overflow-x: auto; }

.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; table-layout: fixed; }
.data-table thead th {
  background: var(--c-surface-raised-local); color: var(--c-text-faint-local);
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--c-border-local);
  white-space: nowrap; transition: background-color .22s, color .22s, border-color .22s;
}
.data-table tbody tr { transition: background-color .15s; }
.data-table tbody tr:hover td { background: var(--c-surface-raised-local); }
.data-table tbody td {
  padding: 14px 16px; border-bottom: 1px solid var(--c-border-local);
  vertical-align: middle; transition: background-color .15s, border-color .22s;
}
.data-table tbody tr:last-child td { border-bottom: none; }

.cell-name   { display: flex; align-items: center; gap: 10px; }
.cell-avatar {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  background: var(--c-accent-soft-local); color: var(--c-accent-local);
  display: flex; align-items: center; justify-content: center; font-size: 13px;
  border: 1px solid var(--c-accent-border-local);
  transition: background-color .22s, border-color .22s;
}
.cell-primary { font-weight: 600; color: var(--c-text-primary-local); transition: color .22s; }

.count-pill {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 600;
  color: var(--c-text-muted-local);
  background: var(--c-surface-raised-local);
  border: 1px solid var(--c-border-local);
  border-radius: 20px; padding: 4px 11px;
  transition: background-color .22s, border-color .22s, color .22s;
}
.count-pill i { font-size: 10px; color: var(--c-text-faint-local); }

.td-actions { text-align: right; }
.menu-btn {
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: none; color: var(--c-text-faint-local); cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center; font-size: 14px;
  transition: background-color .15s, color .15s;
}
.menu-btn:hover, .menu-btn--active { background: var(--c-surface-raised-local); color: var(--c-text-primary-local); }

.td-empty { padding: 56px 24px !important; border-bottom: none !important; text-align: center; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 12px; color: var(--c-text-faint-local); }
.empty-state i { font-size: 36px; }
.empty-state p { font-size: 14px; font-weight: 600; color: var(--c-text-muted-local); margin: 0; }

/* MODAL */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(0,0,0,.48); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-fade-enter-active { transition: opacity .22s ease, transform .22s ease; }
.modal-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.95) translateY(10px); }

.del-modal {
  width: 100%; max-width: 400px;
  background: var(--c-surface-local); border: 1px solid var(--c-border-local);
  border-radius: 22px; box-shadow: var(--c-shadow-xl); overflow: hidden;
  display: flex; flex-direction: column;
  transition: background-color .22s, border-color .22s;
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
.del-modal-title { font-size: 18px; font-weight: 800; color: var(--c-text-primary-local); letter-spacing: -.02em; margin: 0; transition: color .22s; }
.del-modal-desc  { font-size: 13px; line-height: 1.65; color: var(--c-text-muted-local); margin: 0; }
.del-modal-name  { color: var(--c-text-primary-local); font-weight: 700; }
.del-modal-chip {
  display: flex; align-items: center; gap: 12px; width: 100%; margin-top: 6px;
  background: var(--c-surface-raised-local); border: 1px solid var(--c-border-local);
  border-radius: 12px; padding: 11px 14px; text-align: left;
  transition: background-color .22s, border-color .22s;
}
.del-modal-chip-avatar {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  background: rgba(244,63,94,.12); color: #f43f5e;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; border: 1px solid rgba(244,63,94,.22);
}
html[data-theme="dark"] .del-modal-chip-avatar { background: rgba(244,63,94,.18); color: #F87171; }
.del-modal-chip-info { flex: 1; min-width: 0; }
.del-modal-chip-name { font-size: 13px; font-weight: 700; color: var(--c-text-primary-local); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 2px; }
.del-modal-chip-meta { font-size: 11.5px; color: var(--c-text-faint-local); margin: 0; }
.del-modal-footer { display: flex; gap: 10px; padding: 0 26px 26px; }
.del-modal-btn {
  flex: 1; height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 13.5px; font-weight: 600; font-family: 'Inter',system-ui,sans-serif;
  border-radius: 11px; cursor: pointer; transition: background-color .18s, border-color .18s, opacity .18s, transform .15s;
}
.del-modal-btn:active { transform: scale(.97); }
.del-modal-btn:disabled { opacity: .55; cursor: not-allowed; transform: none; }
.del-modal-btn--cancel { background: var(--c-surface-raised-local); border: 1.5px solid var(--c-border-strong-local); color: var(--c-text-secondary-local); }
.del-modal-btn--cancel:hover:not(:disabled) { background: var(--c-border-local); color: var(--c-text-primary-local); }
.del-modal-btn--confirm { background: linear-gradient(135deg,#f43f5e,#e11d48); border: none; color: #fff; box-shadow: 0 2px 12px rgba(244,63,94,.35); }
.del-modal-btn--confirm:hover:not(:disabled) { filter: brightness(1.08); box-shadow: 0 4px 20px rgba(244,63,94,.50); transform: translateY(-1px); }
</style>

<!-- GLOBAL — Teleported dropdown -->
<style>
.cat-dropdown {
  width: 176px;
  background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 13px;
  box-shadow: 0 8px 32px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06);
  z-index: 9999; padding: 5px; overflow: hidden;
}
html[data-theme="dark"] .cat-dropdown { background: #1E2130; border-color: #2A2D3E; box-shadow: 0 8px 40px rgba(0,0,0,.55); }

.cat-dropdown-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px; font-size: 13px; font-weight: 500;
  color: #374151; border-radius: 8px; text-decoration: none;
  background: none; border: none; width: 100%; cursor: pointer;
  font-family: 'Inter', system-ui, sans-serif; transition: background-color .15s, color .15s;
}
.cat-dropdown-item:hover { background: #F9FAFB; color: #111827; }
html[data-theme="dark"] .cat-dropdown-item       { color: #C4C8D6; }
html[data-theme="dark"] .cat-dropdown-item:hover { background: #222535; color: #E8EAF0; }
.cat-dropdown-item--danger       { color: #EF4444 !important; }
.cat-dropdown-item--danger:hover { background: rgba(244,63,94,.10) !important; }
html[data-theme="dark"] .cat-dropdown-item--danger:hover { background: rgba(244,63,94,.14) !important; color: #F87171 !important; }

.cat-dropdown-icon {
  width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 11.5px;
}
.cat-dropdown-icon--blue { background: rgba(59,130,246,.12); color: #3b82f6; }
.cat-dropdown-icon--red  { background: rgba(244,63,94,.12);  color: #f43f5e; }
html[data-theme="dark"] .cat-dropdown-icon--blue { background: rgba(59,130,246,.18); }
html[data-theme="dark"] .cat-dropdown-icon--red  { background: rgba(244,63,94,.18); color: #F87171; }
.cat-dropdown-divider { height: 1px; background: #E5E7EB; margin: 3px 0; }
html[data-theme="dark"] .cat-dropdown-divider { background: #2A2D3E; }
</style>