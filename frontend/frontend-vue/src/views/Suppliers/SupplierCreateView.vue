<template>
  <Layout title="Add Supplier">
    <div class="sup-form-page">

      <!-- ── PAGE HEADER ── -->
      <header class="page-header">
        <div class="page-heading">
          <h1 class="page-title">Add Supplier</h1>
          <p class="page-sub">
            <span>Fill in the details for the new supplier</span>
            <span v-if="isDirty" class="chip chip--dirty">Unsaved draft</span>
          </p>
        </div>
      </header>

      <!-- ── ALERTS ── -->
      <transition name="alert-fade">
        <div v-if="success" class="alert alert--success" role="status">
          <i class="fa-solid fa-circle-check"></i>
          <span>{{ success }}</span>
          <button type="button" class="alert-close" aria-label="Dismiss" @click="success = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>
      <transition name="alert-fade">
        <div v-if="error" class="alert alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>{{ error }}</span>
          <button type="button" class="alert-close" aria-label="Dismiss" @click="error = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>

      <!-- ── FORM ── -->
      <form class="layout" novalidate @submit.prevent="saveSupplier(false)">

        <!-- Main form card -->
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-icon"><i class="fa-solid fa-truck-field"></i></div>
            <div>
              <h2 class="form-card-title">Supplier Information</h2>
              <p class="form-card-sub">Fields marked <span class="form-required">*</span> are required.</p>
            </div>
          </div>

          <div class="form-body">
            <!-- Supplier Name -->
            <div class="form-field">
              <label class="form-label" for="sup-name">Supplier Name <span class="form-required">*</span></label>
              <div class="input-wrap" :class="{ 'input-wrap--error': showError('SupplierName') }">
                <i class="fa-solid fa-building input-icon"></i>
                <input
                  id="sup-name"
                  ref="nameRef"
                  v-model="form.SupplierName"
                  type="text"
                  class="form-input"
                  placeholder="e.g. ABC Steel Corp"
                  autocomplete="organization"
                  :aria-invalid="showError('SupplierName') ? 'true' : 'false'"
                  :aria-describedby="showError('SupplierName') ? 'sup-name-err' : undefined"
                  @input="clearServer('SupplierName')"
                  @blur="touched.SupplierName = true"
                />
              </div>
              <p v-if="showError('SupplierName')" id="sup-name-err" class="field-error">
                <i class="fa-solid fa-circle-exclamation"></i>{{ fieldError('SupplierName') }}
              </p>
            </div>

            <!-- Contact + Email -->
            <div class="form-grid">
              <div class="form-field">
                <label class="form-label" for="sup-phone">Contact Number</label>
                <div class="input-wrap" :class="{ 'input-wrap--error': showError('ContactNumber') }">
                  <i class="fa-solid fa-phone input-icon"></i>
                  <input
                    id="sup-phone"
                    v-model="form.ContactNumber"
                    type="tel"
                    inputmode="tel"
                    class="form-input"
                    placeholder="+63 9XX XXX XXXX"
                    autocomplete="tel"
                    :aria-invalid="showError('ContactNumber') ? 'true' : 'false'"
                    :aria-describedby="showError('ContactNumber') ? 'sup-phone-err' : undefined"
                    @input="clearServer('ContactNumber')"
                    @blur="touched.ContactNumber = true"
                  />
                </div>
                <p v-if="showError('ContactNumber')" id="sup-phone-err" class="field-error">
                  <i class="fa-solid fa-circle-exclamation"></i>{{ fieldError('ContactNumber') }}
                </p>
              </div>

              <div class="form-field">
                <label class="form-label" for="sup-email">Email Address</label>
                <div class="input-wrap" :class="{ 'input-wrap--error': showError('Email') }">
                  <i class="fa-regular fa-envelope input-icon"></i>
                  <input
                    id="sup-email"
                    v-model="form.Email"
                    type="email"
                    inputmode="email"
                    class="form-input"
                    placeholder="supplier@email.com"
                    autocomplete="email"
                    :aria-invalid="showError('Email') ? 'true' : 'false'"
                    :aria-describedby="showError('Email') ? 'sup-email-err' : undefined"
                    @input="clearServer('Email')"
                    @blur="touched.Email = true"
                  />
                </div>
                <p v-if="showError('Email')" id="sup-email-err" class="field-error">
                  <i class="fa-solid fa-circle-exclamation"></i>{{ fieldError('Email') }}
                </p>
              </div>
            </div>

            <!-- Address (grows to fill the remaining card height) -->
            <div class="form-field form-field--grow">
              <label class="form-label" for="sup-address">Address</label>
              <div class="input-wrap input-wrap--textarea" :class="{ 'input-wrap--error': showError('Address') }">
                <i class="fa-solid fa-location-dot input-icon input-icon--top"></i>
                <textarea
                  id="sup-address"
                  v-model="form.Address"
                  rows="3"
                  class="form-input form-textarea"
                  placeholder="Complete business address…"
                  autocomplete="street-address"
                  :aria-invalid="showError('Address') ? 'true' : 'false'"
                  :aria-describedby="showError('Address') ? 'sup-address-err' : undefined"
                  @input="clearServer('Address')"
                  @blur="touched.Address = true"
                ></textarea>
              </div>
              <p v-if="showError('Address')" id="sup-address-err" class="field-error">
                <i class="fa-solid fa-circle-exclamation"></i>{{ fieldError('Address') }}
              </p>
            </div>
          </div>

          <!-- Footer: status + actions -->
          <div class="form-footer">
            <div class="form-actions">
              <RouterLink to="/suppliers" class="btn btn--ghost">Cancel</RouterLink>
              <button type="button" class="btn btn--ghost" :disabled="!isDirty || saving" @click="resetForm">
                <i class="fa-solid fa-rotate-left"></i> Clear
              </button>
              <button type="button" class="btn btn--ghost" :disabled="saving" @click="saveSupplier(true)">
                <i class="fa-solid fa-plus"></i> Save &amp; add another
              </button>
              <button type="submit" class="btn btn--primary" :disabled="saving" :title="isMac ? '⌘S' : 'Ctrl+S'">
                <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                {{ saving ? 'Saving…' : 'Save Supplier' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Side column: live preview + checklist + tip -->
        <aside class="aside" aria-label="Supplier preview">
          <div class="side-card">
            <p class="side-eyebrow">Live preview</p>

            <div class="preview">
              <div class="preview-avatar">{{ initial(form.SupplierName) }}</div>
              <div class="preview-head">
                <p class="preview-name" :class="{ 'preview-empty': !form.SupplierName.trim() }">
                  {{ form.SupplierName.trim() || 'Supplier name' }}
                </p>
                <p class="preview-id">New supplier</p>
              </div>
            </div>

            <ul class="preview-list">
              <li :class="{ 'is-empty': !form.ContactNumber.trim() }">
                <i class="fa-solid fa-phone"></i>
                <span>{{ form.ContactNumber.trim() || 'No contact number' }}</span>
              </li>
              <li :class="{ 'is-empty': !form.Email.trim() }">
                <i class="fa-regular fa-envelope"></i>
                <span>{{ form.Email.trim() || 'No email' }}</span>
              </li>
              <li :class="{ 'is-empty': !form.Address.trim() }">
                <i class="fa-solid fa-location-dot"></i>
                <span>{{ form.Address.trim() || 'No address' }}</span>
              </li>
            </ul>
          </div>

          <div class="side-card side-card--grow">
            <div class="side-head">
              <p class="side-eyebrow">Checklist</p>
              <span class="side-count">{{ filledCount }} of {{ FIELDS.length }} filled</span>
            </div>
            <div class="progress" role="progressbar" :aria-valuenow="filledCount" aria-valuemin="0" :aria-valuemax="FIELDS.length">
              <div class="progress-bar" :style="{ width: (filledCount / FIELDS.length * 100) + '%' }"></div>
            </div>
            <ul class="check-list">
              <li v-for="c in checklist" :key="c.key" class="check-item" :class="{ 'is-done': c.done, 'is-bad': c.bad }">
                <i class="fa-solid" :class="c.bad ? 'fa-circle-exclamation' : c.done ? 'fa-circle-check' : 'fa-circle'"></i>
                <span class="check-label">{{ c.label }}</span>
                <span class="check-tag">{{ c.required ? 'Required' : 'Optional' }}</span>
              </li>
            </ul>
          </div>

          <p class="side-tip">
            <i class="fa-regular fa-lightbulb"></i>
            Press <kbd>{{ isMac ? '⌘' : 'Ctrl' }}</kbd> + <kbd>S</kbd> to save quickly.
          </p>
        </aside>
      </form>

    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter, onBeforeRouteLeave } from 'vue-router'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const router = useRouter()

const saving  = ref(false)
const success = ref('')
const error   = ref('')

const FIELDS = [
  { key: 'SupplierName',  label: 'Name',           required: true  },
  { key: 'ContactNumber', label: 'Contact number', required: false },
  { key: 'Email',         label: 'Email',          required: false },
  { key: 'Address',       label: 'Address',        required: false },
]

const blank = () => ({ SupplierName: '', ContactNumber: '', Email: '', Address: '' })
const form = ref(blank())

const nameRef = ref(null)
const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/i.test(navigator.platform || navigator.userAgent || '')
const initial = (name) => String(name ?? '').trim().charAt(0).toUpperCase() || '?'

/* ── validation (same rules as Edit Supplier) ── */
const touched   = reactive({ SupplierName: false, ContactNumber: false, Email: false, Address: false })
const submitted = ref(false)
const serverErrors = ref({}) // { Email: 'The email has already been taken.' }

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/
const PHONE_RE = /^[+()\d][\d\s().-]{5,19}$/

const clientErrors = computed(() => {
  const e = {}
  if (!form.value.SupplierName.trim()) e.SupplierName = 'Supplier name is required.'
  const phone = form.value.ContactNumber.trim()
  if (phone && !PHONE_RE.test(phone)) e.ContactNumber = 'Enter a valid phone number, like +63 912 345 6789.'
  const email = form.value.Email.trim()
  if (email && !EMAIL_RE.test(email)) e.Email = 'Enter a valid email, like name@company.com.'
  return e
})
const fieldError = (k) => clientErrors.value[k] || serverErrors.value[k] || ''
const showError  = (k) => !!fieldError(k) && (touched[k] || submitted.value || !!serverErrors.value[k])

// Editing a field clears the server's complaint about it.
function clearServer(k) {
  if (!serverErrors.value[k]) return
  const next = { ...serverErrors.value }
  delete next[k]
  serverErrors.value = next
}

/* ── draft tracking ── */
const trim = (v) => String(v ?? '').trim()
const filledCount = computed(() => FIELDS.filter((f) => trim(form.value[f.key])).length)
const isDirty = computed(() => filledCount.value > 0)

const checklist = computed(() =>
  FIELDS.map((f) => ({
    ...f,
    done: !!trim(form.value[f.key]) && !clientErrors.value[f.key],
    bad:  !!trim(form.value[f.key]) && !!clientErrors.value[f.key],
  }))
)

function resetForm() {
  form.value = blank()
  serverErrors.value = {}
  submitted.value = false
  Object.keys(touched).forEach((k) => { touched[k] = false })
  nextTick(() => nameRef.value?.focus())
}

/* ── save ── */
let allowLeave = false
let successTimer = null

async function saveSupplier(addAnother = false) {
  if (saving.value) return
  success.value = ''
  error.value = ''
  serverErrors.value = {}
  submitted.value = true

  if (Object.keys(clientErrors.value).length) {
    error.value = 'Please fix the highlighted fields.'
    await nextTick()
    document.querySelector('.input-wrap--error input, .input-wrap--error textarea')?.focus()
    return
  }

  saving.value = true
  try {
    const payload = {
      SupplierName:  trim(form.value.SupplierName),
      ContactNumber: trim(form.value.ContactNumber),
      Email:         trim(form.value.Email),
      Address:       trim(form.value.Address),
    }
    await api.post('/api/suppliers', payload)

    if (addAnother) {
      resetForm()
      success.value = `"${payload.SupplierName}" added. You can add another supplier now.`
      clearTimeout(successTimer)
      successTimer = setTimeout(() => { success.value = '' }, 4000)
    } else {
      allowLeave = true
      success.value = 'Supplier added successfully!'
      setTimeout(() => router.push('/suppliers'), 800)
    }
  } catch (err) {
    console.error(err)
    const data = err?.response?.data
    if (err?.response?.status === 422 && data?.errors) {
      const map = {}
      Object.entries(data.errors).forEach(([k, v]) => { map[k] = Array.isArray(v) ? v[0] : String(v) })
      serverErrors.value = map
      error.value = data.message || 'Please fix the highlighted fields.'
      await nextTick()
      document.querySelector('.input-wrap--error input, .input-wrap--error textarea')?.focus()
    } else {
      error.value = data?.message || 'Failed to save supplier. Please check your input and try again.'
    }
  } finally {
    saving.value = false
  }
}

/* ── leaving with an unsaved draft ── */
onBeforeRouteLeave(() => {
  if (allowLeave || !isDirty.value) return true
  return window.confirm('You have an unsaved supplier. Leave this page without saving?')
})

function onBeforeUnload(e) {
  if (isDirty.value && !allowLeave) {
    e.preventDefault()
    e.returnValue = ''
  }
}

function onKeydown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
    e.preventDefault()
    saveSupplier(false)
  }
}

onMounted(async () => {
  window.addEventListener('beforeunload', onBeforeUnload)
  window.addEventListener('keydown', onKeydown)
  await nextTick()
  nameRef.value?.focus()
})
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', onBeforeUnload)
  window.removeEventListener('keydown', onKeydown)
  clearTimeout(successTimer)
})
</script>

<style scoped>
.sup-form-page {
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
  --s-ring:           var(--c-accent-ring);
  --s-green:          #10b981;
  --s-red:            #f43f5e;
  --s-amber:          #f59e0b;
  --radius: 16px;

  /* ── SPACE TUNING (identical in Add Supplier and Edit Supplier) ──
     --bleed-*  : how much of the app shell's own padding this page reclaims
                  on desktop (top / sides / bottom). Lower a value if content
                  ever touches the sidebar or top bar.
     --fit-offset : total height of everything ABOVE and BELOW the page
                  (top bar + shell padding − the bleeds). The page is exactly
                  100vh minus this, so the form fits the screen with no page
                  scrollbar. If a scrollbar appears, raise it by a few px;
                  if there's a gap at the bottom, lower it. */
  --bleed-x: 30px;
  --bleed-y: 26px;
  --bleed-b: 26px;
  --fit-offset: 84px;
  --s-gap: 12px;

  box-sizing: border-box;
  background: var(--s-bg);
  padding: 10px 20px 14px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--s-gap);
  transition: background-color .22s;
}
.sup-form-page *, .sup-form-page *::before, .sup-form-page *::after { box-sizing: border-box; }

.sup-form-page button:focus-visible,
.sup-form-page a:focus-visible,
.sup-form-page input:focus-visible,
.sup-form-page textarea:focus-visible { outline: 2px solid var(--s-accent); outline-offset: 2px; }

/* ── HEADER (one compact row: back button + title) ── */
.page-header { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.page-heading { min-width: 0; }
.page-title { font-size: 21px; font-weight: 800; color: var(--s-text-primary); letter-spacing: -.03em; line-height: 1.15; margin: 0; transition: color .22s; }
.page-sub { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 2px 0 0; font-size: 13px; font-weight: 600; color: var(--s-text-secondary); }
.chip { font-size: 11.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }
.chip--dirty { color: #B45309; background: rgba(245,158,11,.16); }
html[data-theme="dark"] .chip--dirty { color: #FCD34D; background: rgba(245,158,11,.18); }

/* ── ALERTS ── */
.alert {
  display: flex; align-items: center; gap: 10px; flex-shrink: 0;
  padding: 10px 14px; border-radius: 12px;
  font-size: 13px; font-weight: 500; border: 1px solid transparent;
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
.link-btn {
  border: none; background: transparent; cursor: pointer;
  font-family: inherit; font-size: 13px; font-weight: 700;
  color: inherit; text-decoration: underline; padding: 4px 6px; border-radius: 8px;
}
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── TWO-COLUMN LAYOUT ── */
.layout {
  display: grid;
  grid-template-columns: minmax(0, 1.65fr) minmax(280px, 1fr);
  gap: var(--s-gap);
  align-items: start;
}
.aside { display: flex; flex-direction: column; gap: var(--s-gap); min-width: 0; min-height: 0; }

/* ── FORM CARD ── */
.form-card {
  background: var(--s-surface); border: 1px solid var(--s-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden; min-width: 0;
  display: flex; flex-direction: column;
  transition: background-color .22s, border-color .22s;
}
.form-card-header {
  display: flex; align-items: center; gap: 14px; flex-shrink: 0;
  padding: 11px 18px; border-bottom: 1px solid var(--s-border);
}
.form-card-icon {
  width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0;
  background: var(--s-accent-soft); color: var(--s-accent);
  display: flex; align-items: center; justify-content: center; font-size: 13px;
  border: 1px solid var(--s-accent-border);
}
.form-card-title { font-size: 14px; font-weight: 700; color: var(--s-text-primary); margin: 0 0 2px; }
.form-card-sub   { font-size: 12px; color: var(--s-text-muted); margin: 0; }

.form-body { padding: 14px 18px; display: flex; flex-direction: column; gap: 12px; flex: 1; min-height: 0; }
.form-body > .form-field:not(.form-field--grow), .form-body > .form-grid { flex-shrink: 0; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.form-field--grow { flex: 1 0 auto; min-height: 84px; }

.form-label    { font-size: 12px; font-weight: 600; color: var(--s-text-secondary); letter-spacing: .01em; }
.form-required { color: var(--s-accent); }

.input-wrap           { position: relative; display: flex; align-items: center; }
.input-wrap--textarea { align-items: stretch; flex: 1; min-height: 60px; }
.input-icon           { position: absolute; left: 13px; font-size: 12px; color: var(--s-text-faint); pointer-events: none; z-index: 1; transition: color .18s; }
.input-icon--top      { top: 12px; }
.input-wrap:focus-within .input-icon { color: var(--s-accent); }

.form-input {
  width: 100%; height: 38px;
  padding: 0 12px 0 35px;
  border: 1.5px solid var(--s-border-strong); border-radius: 11px;
  background: var(--s-surface-sunken); color: var(--s-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-input::placeholder { color: var(--s-text-faint); }
.form-input:hover { border-color: var(--s-accent-border); }
.form-input:focus { border-color: var(--s-accent); box-shadow: 0 0 0 3px var(--s-ring); background: var(--s-surface); }
.form-textarea { height: auto; min-height: 60px; padding-top: 9px; padding-bottom: 9px; resize: vertical; line-height: 1.6; }

.input-wrap--error .form-input { border-color: var(--s-red); background: rgba(244,63,94,.04); }
.input-wrap--error .form-input:focus { box-shadow: 0 0 0 3px rgba(244,63,94,.18); }
.input-wrap--error .input-icon { color: var(--s-red); }
.field-error { display: flex; align-items: center; gap: 6px; margin: 0; font-size: 12px; font-weight: 600; color: var(--s-red); }
.field-error i { font-size: 11px; }
html[data-theme="dark"] .field-error { color: #F87171; }

/* ── FOOTER ── */
.form-footer {
  display: flex; align-items: center; justify-content: flex-end; gap: 14px; flex-wrap: wrap; flex-shrink: 0;
  padding: 9px 18px; border-top: 1px solid var(--s-border);
  background: var(--s-surface-raised);
}
.form-status { display: inline-flex; align-items: center; gap: 8px; margin: 0; font-size: 12.5px; font-weight: 600; color: var(--s-text-muted); }
.form-status i { font-size: 11px; }
.dot-dirty { color: var(--s-amber); font-size: 8px !important; }
.dot-clean { color: var(--s-green); }
.form-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: auto; }

.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 7px 14px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background-color .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s, filter .18s;
}
.btn:active:not(:disabled) { transform: scale(.98); }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }
.btn--ghost { background: var(--s-surface); border-color: var(--s-border-strong); color: var(--s-text-secondary); }
.btn--ghost:hover:not(:disabled) { background: var(--s-surface-raised); border-color: var(--s-accent-border); color: var(--s-text-primary); }
.btn--ghost:disabled { opacity: .5; cursor: not-allowed; }
.btn--primary {
  background: linear-gradient(135deg, var(--s-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover:not(:disabled) { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--primary:disabled { opacity: .5; cursor: not-allowed; filter: none; transform: none; box-shadow: none; }

/* ── SIDE CARDS ── */
.side-card {
  background: var(--s-surface); border: 1px solid var(--s-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm);
  padding: 12px 16px; min-width: 0; flex-shrink: 0;
  transition: background-color .22s, border-color .22s;
}
.side-card--grow { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
.side-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
.side-head .side-eyebrow { margin: 0; }
.side-count { font-size: 12px; font-weight: 700; color: var(--s-text-muted); }
.side-eyebrow { margin: 0 0 8px; font-size: 10.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--s-accent); }
.side-muted { margin: 0; font-size: 12.5px; line-height: 1.55; color: var(--s-text-muted); }

.preview { display: flex; align-items: center; gap: 12px; }
.preview-avatar {
  width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
  background: var(--s-accent-soft); color: var(--s-accent); border: 1px solid var(--s-accent-border);
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 800;
}
.preview-head { min-width: 0; }
.preview-name { margin: 0; font-size: 15px; font-weight: 800; letter-spacing: -.02em; color: var(--s-text-primary); overflow-wrap: anywhere; }
.preview-empty { color: var(--s-text-faint); font-weight: 600; }
.preview-id { margin: 2px 0 0; font-size: 11.5px; font-weight: 600; color: var(--s-text-faint); }

.preview-list { list-style: none; margin: 10px 0 0; padding: 10px 0 0; border-top: 1px solid var(--s-border); display: flex; flex-direction: column; gap: 7px; }
.preview-list li { display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; color: var(--s-text-secondary); line-height: 1.4; }
.preview-list li i { width: 14px; margin-top: 3px; font-size: 11.5px; text-align: center; color: var(--s-text-faint); flex-shrink: 0; }
.preview-list li span { min-width: 0; overflow-wrap: anywhere; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.preview-list li.is-empty { color: var(--s-text-faint); font-style: italic; }

.change-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
.change-item { display: flex; flex-direction: column; gap: 4px; padding: 9px 12px; border-radius: 10px; background: var(--s-surface-sunken); border-left: 3px solid var(--s-amber); }
.change-label { font-size: 10.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--s-text-muted); }
.change-diff { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 12.5px; min-width: 0; }
.change-diff i { font-size: 9px; color: var(--s-text-faint); }
.change-old { color: var(--s-text-muted); text-decoration: line-through; overflow-wrap: anywhere; }
.change-new { color: var(--s-text-primary); font-weight: 700; overflow-wrap: anywhere; }

.side-tip { margin: 0; padding: 0 4px; display: flex; align-items: center; gap: 9px; font-size: 12px; color: var(--s-text-muted); flex-shrink: 0; }
.side-tip i { color: var(--s-accent); }
kbd {
  font-family: inherit; font-size: 11px; font-weight: 700;
  padding: 2px 6px; border-radius: 6px;
  background: var(--s-surface-raised); border: 1px solid var(--s-border-strong); border-bottom-width: 2px;
  color: var(--s-text-secondary);
}


/* ── ADD-ONLY: checklist + progress (replaces the Changes card) ── */
.progress { height: 5px; border-radius: 6px; background: var(--s-surface-sunken); overflow: hidden; margin: 0 0 10px; }
.progress-bar { height: 100%; border-radius: 6px; background: linear-gradient(90deg, var(--s-accent), var(--c-accent-deep)); transition: width .25s ease; }
.check-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.check-item { display: flex; align-items: center; gap: 9px; font-size: 12.5px; font-weight: 600; color: var(--s-text-muted); }
.check-item i { font-size: 13px; color: var(--s-text-faint); width: 14px; text-align: center; }
.check-item.is-done { color: var(--s-text-primary); }
.check-item.is-done i { color: var(--s-green); }
.check-item.is-bad { color: var(--s-red); }
.check-item.is-bad i { color: var(--s-red); }
.check-label { flex: 1; min-width: 0; }
.check-tag { font-size: 10.5px; font-weight: 700; letter-spacing: .04em; color: var(--s-text-faint); }

/* ── SKELETON ── */
.sk {
  background: linear-gradient(90deg, var(--s-surface-sunken) 25%, var(--s-surface-raised) 50%, var(--s-surface-sunken) 75%);
  background-size: 200% 100%;
  animation: sk-shimmer 1.3s ease-in-out infinite;
  border-radius: 8px;
}
.sk--icon { width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0; }
.sk-lines { display: flex; flex-direction: column; gap: 8px; }
.sk--wide { width: 170px; height: 12px; }
.sk--narrow { width: 110px; height: 12px; }
.sk--field { height: 42px; border-radius: 11px; }
.sk--tall { height: 110px; }
.sk--preview { height: 180px; border-radius: 12px; }
@keyframes sk-shimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }

/* ── LOAD FAILED ── */
.form-card--center { justify-content: center; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 48px 24px; text-align: center; color: var(--s-text-faint); }
.empty-state > i { font-size: 34px; margin-bottom: 4px; }
.empty-title { margin: 0; font-size: 15px; font-weight: 700; color: var(--s-text-primary); }
.empty-sub { margin: 0 0 6px; font-size: 13px; color: var(--s-text-muted); max-width: 360px; line-height: 1.5; }
.empty-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }

/* ── RESPONSIVE ──
   Desktop only: pull the page outward into the shell's padding. */
@media (min-width: 1001px) {
  .sup-form-page { margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) calc(-1 * var(--bleed-b)); }
}

/* Fit-to-screen mode: two columns and enough height → no page scrolling.
   The form card stretches to the page height and the Address box takes
   whatever space is left. */
@media (min-width: 1101px) and (min-height: 560px) {
  .sup-form-page { height: calc(100dvh - var(--fit-offset)); overflow: hidden; }
  .layout { flex: 1; min-height: 0; grid-template-rows: minmax(0, 1fr); align-items: stretch; }
  .layout > .form-card { min-height: 0; }
  .form-card--center { flex: 1; }
  .form-textarea { height: 100%; resize: none; }
}

@media (max-width: 1100px) {
  .layout { grid-template-columns: minmax(0, 1fr); }
  .side-card--grow { overflow: visible; }
}
@media (max-width: 900px) {
  .sup-form-page { padding: 12px 14px 24px; --s-gap: 14px; }
}
@media (max-width: 700px) {
  .sup-form-page { padding: 14px 16px 32px; }
  .page-title { font-size: 22px; }
  .form-grid { grid-template-columns: 1fr; }
  .form-textarea { min-height: 110px; }
  .form-card-header, .form-body, .form-footer { padding-left: 18px; padding-right: 18px; }
  .form-footer { flex-direction: column; align-items: stretch; }
  .form-status { justify-content: center; }
  .form-actions { flex-direction: column-reverse; align-items: stretch; margin-left: 0; }
  .btn { justify-content: center; }
}

@media (prefers-reduced-motion: reduce) {
  .sup-form-page * { transition: none !important; }
  .sk { animation: none; }
}
</style>