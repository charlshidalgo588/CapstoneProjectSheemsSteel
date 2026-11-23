<template>
  <Layout title="Add New Category">
    <div class="cat-form-page">

      <!-- PAGE HEADER (same compact row as Product List: INVENTORY / Add Category) -->
      <header class="page-header">
        <div class="page-heading">
          <div class="page-title-row">
            <span class="page-eyebrow">Inventory</span>
            <span class="page-crumb-sep" aria-hidden="true">/</span>
            <h1 class="page-title">Add Category</h1>
          </div>
        </div>
        <div class="header-actions">
          <RouterLink to="/categories" class="btn btn--ghost btn--sm">Cancel</RouterLink>
          <button type="submit" form="category-form" class="btn btn--primary btn--sm" :disabled="saving || !categoryName.trim()">
            <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-floppy-disk"></i>
            {{ saving ? 'Saving…' : 'Save Category' }}
          </button>
        </div>
      </header>

      <!-- ALERTS -->
      <transition name="alert-fade">
        <div v-if="successMessage" class="alert alert--success" role="status">
          <i class="fa-solid fa-circle-check"></i>
          <span>{{ successMessage }}</span>
          <button type="button" class="alert-close" aria-label="Dismiss" @click="successMessage = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>
      <transition name="alert-fade">
        <div v-if="errorMessage" class="alert alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>{{ errorMessage }}</span>
          <button type="button" class="alert-close" aria-label="Dismiss" @click="errorMessage = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>

      <div class="form-layout">
        <!-- FORM -->
        <form id="category-form" @submit.prevent="saveCategory" class="form-card">
          <div class="form-card-header">
            <div class="form-card-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div class="form-card-text">
              <h2 class="form-card-title">Category information</h2>
              <p class="form-card-sub">Give this category a clear, recognizable name. It appears in product filters and reports.</p>
            </div>
          </div>

          <div class="form-body">
            <div class="form-field">
              <label class="form-label" for="category-name">Category name <span class="form-required">*</span></label>
              <div class="input-wrap">
                <i class="fa-solid fa-tag input-icon"></i>
                <input
                  id="category-name"
                  v-model="categoryName"
                  type="text"
                  class="form-input"
                  placeholder="e.g. Roofing Materials"
                  required
                  autofocus
                  autocomplete="off"
                />
              </div>
            </div>
          </div>
        </form>

        <!-- PREVIEW -->
        <aside class="preview-card" aria-label="Preview">
          <p class="preview-title">Preview</p>
          <p class="preview-sub">How it will look in your category list.</p>
          <div class="preview-row">
            <div class="preview-avatar"><i class="fa-solid fa-layer-group"></i></div>
            <span class="preview-name" :class="{ 'preview-name--empty': !categoryName.trim() }">
              {{ categoryName.trim() || 'Category name' }}
            </span>
            <span class="preview-pill"><i class="fa-solid fa-box"></i> 0 products</span>
          </div>
        </aside>
      </div>

    </div>
  </Layout>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const router = useRouter()

const categoryName   = ref('')
const successMessage = ref('')
const errorMessage   = ref('')
const saving         = ref(false)

async function saveCategory() {
  successMessage.value = ''
  errorMessage.value   = ''

  if (!categoryName.value.trim()) {
    errorMessage.value = 'Category name is required.'
    return
  }

  saving.value = true
  try {
    await api.post('/api/categories', { CategoryName: categoryName.value.trim() })
    successMessage.value = 'Category added successfully!'
    setTimeout(() => router.push('/categories'), 700)
  } catch (err) {
    console.error('Save category error:', err)
    errorMessage.value =
      err.response?.data?.errors?.CategoryName?.[0] ||
      err.response?.data?.message ||
      'Failed to add category.'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
/* ── TOKEN BRIDGE ── */
.cat-form-page {
  --f-bg:             var(--c-bg);
  --f-surface:        var(--c-surface);
  --f-surface-raised: var(--c-surface-raised);
  --f-surface-sunken: var(--c-surface-sunken);
  --f-border:         var(--c-border);
  --f-border-strong:  var(--c-border-strong);
  --f-text-primary:   var(--c-text-primary);
  --f-text-secondary: var(--c-text-secondary);
  --f-text-muted:     var(--c-text-muted);
  --f-text-faint:     var(--c-text-faint);
  --f-accent:         var(--c-accent);
  --f-accent-soft:    var(--c-accent-soft);
  --f-accent-border:  var(--c-accent-border);
  --f-ring:           var(--c-accent-ring);
  --f-green:          #10b981;
  --f-red:            #f43f5e;
  --radius: 16px;

  /* ── SPACE TUNING (identical to Home and the Categories list, on EVERY screen size) ──
     The app shell (Layout.vue) pads the scrolling area. --bleed-x / --bleed-y
     reclaim part of that padding on the left, right and top, and --bleed-b does
     the same at the bottom. --pad-x is this page's own inner side padding.
     Each breakpoint at the bottom of this file sets its own values.
     Raise a bleed value to push content closer to the edge; lower it (or set
     0px) if content ever touches the sidebar, top bar or screen edge. */
  --bleed-x: 30px;
  --bleed-y: 26px;
  --bleed-b: 26px;
  --pad-x: 24px;
  --f-gap: 16px;

  box-sizing: border-box;
  background: var(--f-bg);
  padding:
    16px
    max(var(--pad-x), env(safe-area-inset-right))
    24px
    max(var(--pad-x), env(safe-area-inset-left));
  margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) calc(-1 * var(--bleed-b));
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--f-gap);
  -webkit-font-smoothing: antialiased;
  -webkit-tap-highlight-color: transparent;
  transition: background-color .22s;
}
.cat-form-page *, .cat-form-page *::before, .cat-form-page *::after { box-sizing: border-box; }
.cat-form-page button:focus-visible,
.cat-form-page a:focus-visible,
.cat-form-page input:focus-visible { outline: 2px solid var(--f-accent); outline-offset: 2px; }

/* ── PAGE HEADER (matches Product List: INVENTORY / Add Category, actions on the right) ── */
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.page-heading { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.page-title-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: 4px 10px; min-width: 0; }
.page-eyebrow { font-size: 16px; font-weight: 700; letter-spacing: .12em; color: var(--f-accent); text-transform: uppercase; line-height: 1.2; margin: 0; }
.page-crumb-sep { font-size: 16px; font-weight: 300; color: var(--f-text-faint); line-height: 1.2; }
.page-title { font-size: 16px; font-weight: 800; color: var(--f-text-primary); letter-spacing: -.02em; line-height: 1.2; margin: 0; transition: color .22s; }
.header-actions { display: flex; align-items: center; gap: 10px; }

/* ── ALERTS ── */
.alert {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-radius: 12px;
  font-size: 13.5px; font-weight: 500; border: 1px solid transparent;
}
.alert span { flex: 1; min-width: 0; overflow-wrap: anywhere; }
.alert--success { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); color: var(--f-green); }
.alert--error   { background: rgba(244,63,94,.09);  border-color: rgba(244,63,94,.22);  color: var(--f-red); }
html[data-theme="dark"] .alert--success { background: rgba(16,185,129,.14); color: #4ADE80; }
html[data-theme="dark"] .alert--error   { background: rgba(244,63,94,.14);  color: #F87171; }
.alert-close { background: none; border: none; cursor: pointer; color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 4px; transition: opacity .15s; }
.alert-close:hover { opacity: 1; }
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── BODY: form + preview side by side ── */
.form-layout {
  display: grid; grid-template-columns: minmax(0, 1fr) 340px;
  gap: var(--f-gap); align-items: start;
}

.form-card {
  background: var(--f-surface); border: 1px solid var(--f-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.form-card-header {
  display: flex; align-items: center; gap: 14px;
  padding: 18px 24px; border-bottom: 1px solid var(--f-border);
  transition: border-color .22s;
}
.form-card-icon {
  width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
  background: var(--f-accent-soft); color: var(--f-accent);
  display: flex; align-items: center; justify-content: center; font-size: 16px;
  border: 1px solid var(--f-accent-border);
}
.form-card-text { min-width: 0; }
.form-card-title { font-size: 15px; font-weight: 700; color: var(--f-text-primary); margin: 0 0 3px; }
.form-card-sub   { font-size: 12.5px; color: var(--f-text-muted); margin: 0; line-height: 1.5; overflow-wrap: anywhere; }

.form-body  { padding: 24px; }
.form-field { display: flex; flex-direction: column; gap: 7px; }
.form-label { font-size: 12.5px; font-weight: 600; color: var(--f-text-secondary); letter-spacing: .01em; }
.form-required { color: var(--f-accent); }

.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon {
  position: absolute; left: 13px; font-size: 12px;
  color: var(--f-text-faint); pointer-events: none; z-index: 1;
}
.form-input {
  width: 100%; height: 44px; padding: 0 14px 0 36px;
  border: 1.5px solid var(--f-border-strong); border-radius: 10px;
  background: var(--f-surface-sunken); color: var(--f-text-primary);
  font-size: 14px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-input::placeholder { color: var(--f-text-faint); }
.form-input:focus { border-color: var(--f-accent); box-shadow: 0 0 0 3px var(--f-ring); background: var(--f-surface); outline: none; }

/* ── PREVIEW CARD ── */
.preview-card {
  background: var(--f-surface); border: 1px solid var(--f-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm);
  padding: 18px 20px; display: flex; flex-direction: column; gap: 12px;
}
.preview-title { margin: 0; font-size: 13px; font-weight: 700; color: var(--f-text-primary); }
.preview-sub   { margin: -6px 0 0; font-size: 12px; color: var(--f-text-muted); }
.preview-row {
  display: flex; align-items: center; gap: 11px; min-width: 0;
  background: var(--f-surface-raised); border: 1px solid var(--f-border);
  border-radius: 12px; padding: 12px 14px;
}
.preview-avatar {
  width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
  background: var(--f-accent-soft); color: var(--f-accent);
  border: 1px solid var(--f-accent-border);
  display: flex; align-items: center; justify-content: center; font-size: 13px;
}
.preview-name { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 600; color: var(--f-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.preview-name--empty { color: var(--f-text-faint); font-weight: 500; }
.preview-pill {
  display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;
  font-size: 12px; font-weight: 600; white-space: nowrap;
  color: var(--f-text-faint); background: var(--f-surface);
  border: 1px solid var(--f-border); border-radius: 20px; padding: 4px 11px;
}
.preview-pill i { font-size: 10px; }

/* ── BUTTONS ── */
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 11px; padding: 10px 18px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  touch-action: manipulation;
  transition: background-color .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s, filter .18s;
}
.btn:active { transform: scale(.98); }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }
.btn--ghost { background: var(--f-surface-sunken); border-color: var(--f-border-strong); color: var(--f-text-secondary); }
.btn--primary {
  background: linear-gradient(135deg, var(--f-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:disabled { opacity: .55; cursor: not-allowed; filter: none; transform: none; box-shadow: none; }

/* Hover effects: only on devices that really hover (no sticky hover on touch) */
@media (hover: hover) and (pointer: fine) {
  .btn--ghost:hover { background: var(--f-surface-raised); border-color: var(--f-accent-border); color: var(--f-text-primary); }
  .btn--primary:hover:not(:disabled) { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
  .form-input:hover:not(:focus) { border-color: var(--f-accent-border); }
}

/* ══════════════════════════════════════════════════════
   RESPONSIVE (same breakpoints and values as Home and the Categories list)
══════════════════════════════════════════════════════ */

/* Large screens: more breathing room */
@media (min-width: 1500px) {
  .cat-form-page { --f-gap: 20px; --pad-x: 32px; }
}

/* Tablets and below */
@media (max-width: 1000px) {
  .cat-form-page { --bleed-x: 22px; --bleed-y: 20px; --bleed-b: 20px; }
}

/* Tablets (portrait) and phones: preview drops under the form */
@media (max-width: 900px) {
  .cat-form-page { --pad-x: 16px; --f-gap: 14px; }
  .form-layout { grid-template-columns: minmax(0, 1fr); }
}

/* Phones */
@media (max-width: 700px) {
  .page-eyebrow, .page-title { font-size: 14px; }
  .page-header { flex-direction: column; align-items: stretch; gap: 12px; }
  /* Cancel and Save sit side by side, Save gets the wider button */
  .header-actions { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr); gap: 10px; }
  .header-actions .btn { height: 44px; font-size: 13px; }
  .form-card-header { padding: 16px 18px; }
  .form-body { padding: 18px; }
  .form-input { height: 46px; font-size: 16px; } /* 16px stops iOS zooming into the field */
  .preview-card { padding: 16px 16px; }
}

/* Phones: spacing tokens */
@media (max-width: 600px) {
  .cat-form-page { --bleed-x: 16px; --bleed-y: 16px; --bleed-b: 16px; --pad-x: 12px; --f-gap: 12px; }
  .form-card, .preview-card { border-radius: 14px; }
  .alert { padding: 11px 12px; font-size: 13px; }
}

/* Very small phones */
@media (max-width: 360px) {
  .cat-form-page { --pad-x: 10px; }
  .form-card-header { gap: 12px; padding: 14px; }
  .form-body { padding: 14px; }
  .preview-row { padding: 10px 12px; gap: 10px; }
}

@media (prefers-reduced-motion: reduce) {
  .cat-form-page * { transition: none !important; }
}
</style>