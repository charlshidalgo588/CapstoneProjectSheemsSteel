<template>
  <Layout title="Add Supplier">
    <div class="sup-form-page">

      <!-- PAGE HEADER -->
      <div class="page-header">
        <div class="breadcrumb">
          <RouterLink to="/suppliers" class="breadcrumb-link">
            <i class="fa-solid fa-arrow-left"></i> Suppliers
          </RouterLink>
        </div>
        <div>
          <p class="page-eyebrow">New Record</p>
          <h1 class="page-title">Add Supplier</h1>
        </div>
      </div>

      <!-- ALERTS -->
      <transition name="alert-fade">
        <div v-if="success" class="alert alert--success">
          <i class="fa-solid fa-circle-check"></i> {{ success }}
          <button class="alert-close" @click="success = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>
      <transition name="alert-fade">
        <div v-if="error" class="alert alert--error">
          <i class="fa-solid fa-circle-exclamation"></i> {{ error }}
          <button class="alert-close" @click="error = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>

      <!-- FORM -->
      <form @submit.prevent="saveSupplier" class="sup-form">
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-icon">
              <i class="fa-solid fa-truck-field"></i>
            </div>
            <div>
              <h2 class="form-card-title">Supplier Information</h2>
              <p class="form-card-sub">Fill in the details for the new supplier.</p>
            </div>
          </div>

          <div class="form-body">
            <!-- Supplier Name -->
            <div class="form-field form-field--full">
              <label class="form-label">Supplier Name <span class="form-required">*</span></label>
              <div class="input-wrap">
                <i class="fa-solid fa-building input-icon"></i>
                <input
                  v-model="form.SupplierName"
                  type="text"
                  class="form-input"
                  placeholder="e.g. ABC Steel Corp"
                  required
                />
              </div>
            </div>

            <!-- Contact + Email -->
            <div class="form-grid">
              <div class="form-field">
                <label class="form-label">Contact Number</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-phone input-icon"></i>
                  <input
                    v-model="form.ContactNumber"
                    type="tel"
                    class="form-input"
                    placeholder="+63 9XX XXX XXXX"
                  />
                </div>
              </div>
              <div class="form-field">
                <label class="form-label">Email Address</label>
                <div class="input-wrap">
                  <i class="fa-regular fa-envelope input-icon"></i>
                  <input
                    v-model="form.Email"
                    type="email"
                    class="form-input"
                    placeholder="supplier@email.com"
                  />
                </div>
              </div>
            </div>

            <!-- Address -->
            <div class="form-field form-field--full">
              <label class="form-label">Address</label>
              <div class="input-wrap input-wrap--textarea">
                <i class="fa-solid fa-location-dot input-icon input-icon--top"></i>
                <textarea
                  v-model="form.Address"
                  rows="3"
                  class="form-input form-textarea"
                  placeholder="Complete business address…"
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- ACTIONS -->
        <div class="form-actions">
          <RouterLink to="/suppliers" class="btn btn--ghost">
            <i class="fa-solid fa-arrow-left"></i> Cancel
          </RouterLink>
          <button type="submit" class="btn btn--primary" :disabled="saving">
            <i class="fa-solid fa-floppy-disk"></i>
            {{ saving ? 'Saving…' : 'Save Supplier' }}
          </button>
        </div>
      </form>

    </div>
  </Layout>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const router = useRouter()
const saving = ref(false)
const success = ref('')
const error   = ref('')

const form = ref({
  SupplierName:  '',
  ContactNumber: '',
  Email:         '',
  Address:       '',
})

async function saveSupplier() {
  success.value = ''; error.value = ''; saving.value = true
  try {
    await api.post('/api/suppliers', form.value)
    success.value = 'Supplier added successfully!'
    setTimeout(() => router.push('/suppliers'), 800)
  } catch (err) {
    console.error(err)
    error.value = 'Failed to save supplier. Please check your input.'
  } finally {
    saving.value = false
  }
}
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
  --radius: 16px;

  min-height: 100%;
  background: var(--s-bg);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 20px;
  max-width: 760px; margin: 0 auto;
  transition: background-color .22s;
}

.breadcrumb { margin-bottom: 10px; }
.breadcrumb-link {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12.5px; font-weight: 600;
  color: var(--s-text-muted); text-decoration: none;
  transition: color .15s;
}
.breadcrumb-link:hover { color: var(--s-accent); }

.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--s-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--s-text-primary); letter-spacing: -.03em; margin: 0; transition: color .22s; }

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
  color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 2px;
  transition: opacity .15s;
}
.alert-close:hover { opacity: 1; }
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* FORM CARD */
.sup-form { display: flex; flex-direction: column; gap: 20px; }

.form-card {
  background: var(--s-surface); border: 1px solid var(--s-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.form-card-header {
  display: flex; align-items: center; gap: 14px;
  padding: 20px 24px 16px; border-bottom: 1px solid var(--s-border);
  transition: border-color .22s;
}
.form-card-icon {
  width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
  background: var(--s-accent-soft); color: var(--s-accent);
  display: flex; align-items: center; justify-content: center; font-size: 16px;
  border: 1px solid var(--s-accent-border);
}
.form-card-title { font-size: 15px; font-weight: 700; color: var(--s-text-primary); margin: 0 0 3px; transition: color .22s; }
.form-card-sub   { font-size: 12.5px; color: var(--s-text-muted); margin: 0; }

.form-body { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.form-field { display: flex; flex-direction: column; gap: 7px; }
.form-field--full { width: 100%; }

.form-label    { font-size: 12.5px; font-weight: 600; color: var(--s-text-secondary); letter-spacing: .01em; transition: color .22s; }
.form-required { color: var(--s-accent); }

.input-wrap { position: relative; display: flex; align-items: center; }
.input-wrap--textarea { align-items: flex-start; }
.input-icon {
  position: absolute; left: 13px; font-size: 12px;
  color: var(--s-text-faint); pointer-events: none; z-index: 1;
  transition: color .22s;
}
.input-icon--top { top: 13px; }

.form-input {
  width: 100%; height: 42px;
  padding: 0 14px 0 36px;
  border: 1.5px solid var(--s-border-strong); border-radius: 10px;
  background: var(--s-surface-sunken); color: var(--s-text-primary);
  font-size: 13.5px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-input::placeholder { color: var(--s-text-faint); }
.form-input:focus {
  border-color: var(--s-accent); box-shadow: 0 0 0 3px var(--s-ring);
  background: var(--s-surface);
}
.form-textarea {
  height: auto; padding-top: 11px; padding-bottom: 11px; resize: vertical; line-height: 1.6;
}

/* ACTIONS */
.form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; }

.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13.5px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 20px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background-color .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn--ghost {
  background: var(--s-surface); border-color: var(--s-border-strong); color: var(--s-text-secondary);
}
.btn--ghost:hover { background: var(--s-surface-raised); border-color: var(--s-accent-border); color: var(--s-text-primary); }
.btn--primary {
  background: linear-gradient(135deg, var(--s-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--primary:disabled { opacity: .6; cursor: not-allowed; filter: none; transform: none; box-shadow: none; }

@media (max-width: 600px) {
  .sup-form-page { padding: 16px 16px 48px; }
  .form-grid     { grid-template-columns: 1fr; }
  .form-actions  { flex-direction: column-reverse; align-items: stretch; }
  .btn           { justify-content: center; }
}
</style>