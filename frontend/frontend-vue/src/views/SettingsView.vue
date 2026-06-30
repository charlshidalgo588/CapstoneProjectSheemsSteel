<template>
  <Layout title="Settings">
    <div class="settings-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div class="breadcrumb">
          <RouterLink to="/home" class="breadcrumb-link">
            <i class="fa-solid fa-house"></i> Dashboard
          </RouterLink>
          <i class="fa-solid fa-chevron-right breadcrumb-sep"></i>
          <span class="breadcrumb-current">Settings</span>
        </div>

        <div class="header-row">
          <div>
            <p class="page-eyebrow">Configuration</p>
            <h1 class="page-title">Account Settings</h1>
            <p class="page-sub">Manage your account information and security preferences.</p>
          </div>
          <div class="last-updated" v-if="lastUpdated">
            <i class="fa-regular fa-clock"></i>
            Last updated: {{ lastUpdated }}
          </div>
        </div>
      </div>

      <!-- ── ALERTS ── -->
      <transition name="alert-fade">
        <div v-if="successMessage" class="alert alert--success">
          <span class="alert-icon"><i class="fa-solid fa-circle-check"></i></span>
          <span>{{ successMessage }}</span>
          <button class="alert-close" @click="successMessage = ''">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </transition>

      <transition name="alert-fade">
        <div v-if="errors.length" class="alert alert--error">
          <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
          <ul class="alert-list">
            <li v-for="(e, i) in errors" :key="i">{{ e }}</li>
          </ul>
          <button class="alert-close" @click="errors = []">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </transition>

      <!-- ── FORM ── -->
      <form @submit.prevent="saveChanges" class="settings-form">

        <!-- PERSONAL INFORMATION -->
        <div class="settings-card">
          <div class="settings-card-header">
            <div class="settings-card-icon settings-card-icon--blue">
              <i class="fa-solid fa-user"></i>
            </div>
            <div>
              <h2 class="settings-card-title">Personal Information</h2>
              <p class="settings-card-sub">Update your name and email address.</p>
            </div>
          </div>

          <div class="form-grid">
            <div class="form-field">
              <label class="form-label">Full Name</label>
              <div class="input-wrap">
                <i class="fa-solid fa-user input-icon"></i>
                <input
                  type="text"
                  v-model="form.name"
                  class="form-input"
                  placeholder="Enter your full name"
                />
              </div>
            </div>

            <div class="form-field">
              <label class="form-label">Email Address</label>
              <div class="input-wrap">
                <i class="fa-solid fa-envelope input-icon"></i>
                <input
                  type="email"
                  v-model="form.email"
                  class="form-input"
                  placeholder="Enter your email"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- SECURITY -->
        <div class="settings-card">
          <div class="settings-card-header">
            <div class="settings-card-icon settings-card-icon--orange">
              <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
              <h2 class="settings-card-title">Security</h2>
              <p class="settings-card-sub">Change your password. Leave blank to keep your current one.</p>
            </div>
          </div>

          <div class="form-stack">
            <!-- Current password -->
            <div class="form-field form-field--full">
              <label class="form-label">Current Password</label>
              <div class="input-wrap">
                <i class="fa-solid fa-lock input-icon"></i>
                <input
                  :type="visibility.current ? 'text' : 'password'"
                  v-model="form.current_password"
                  class="form-input form-input--pw"
                  placeholder="Enter your current password"
                />
                <button type="button" class="pw-toggle" @click="toggle('current')">
                  <i :class="visibility.current ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                </button>
              </div>
            </div>

            <!-- New + Confirm -->
            <div class="form-grid">
              <div class="form-field">
                <label class="form-label">New Password</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-key input-icon"></i>
                  <input
                    :type="visibility.new ? 'text' : 'password'"
                    v-model="form.new_password"
                    class="form-input form-input--pw"
                    placeholder="Enter new password"
                  />
                  <button type="button" class="pw-toggle" @click="toggle('new')">
                    <i :class="visibility.new ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                  </button>
                </div>
                <!-- strength bar -->
                <div class="pw-strength" v-if="form.new_password">
                  <div class="pw-strength-bar">
                    <div class="pw-strength-fill" :class="pwStrength.cls" :style="{ width: pwStrength.pct }"></div>
                  </div>
                  <span class="pw-strength-label" :class="pwStrength.cls">{{ pwStrength.label }}</span>
                </div>
              </div>

              <div class="form-field">
                <label class="form-label">Confirm New Password</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-key input-icon"></i>
                  <input
                    :type="visibility.confirm ? 'text' : 'password'"
                    v-model="form.new_password_confirmation"
                    class="form-input form-input--pw"
                    :class="{ 'form-input--mismatch': pwMismatch }"
                    placeholder="Confirm new password"
                  />
                  <button type="button" class="pw-toggle" @click="toggle('confirm')">
                    <i :class="visibility.confirm ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                  </button>
                </div>
                <p class="form-hint form-hint--error" v-if="pwMismatch">Passwords do not match.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- FORM ACTIONS -->
        <div class="form-actions">
          <RouterLink to="/home" class="btn btn--ghost">
            <i class="fa-solid fa-arrow-left"></i> Cancel
          </RouterLink>
          <button type="submit" class="btn btn--primary" :disabled="saving">
            <i class="fa-solid fa-floppy-disk"></i>
            {{ saving ? 'Saving…' : 'Save Changes' }}
          </button>
        </div>

      </form>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'

const lastUpdated    = ref('')
const successMessage = ref('')
const errors         = ref([])
const saving         = ref(false)

const form = ref({
  name:                     '',
  email:                    '',
  current_password:         '',
  new_password:             '',
  new_password_confirmation:'',
})

const visibility = ref({ current: false, new: false, confirm: false })
const toggle = f => { visibility.value[f] = !visibility.value[f] }

/* password strength */
const pwStrength = computed(() => {
  const pw = form.value.new_password
  if (!pw) return { pct:'0%', cls:'', label:'' }
  let score = 0
  if (pw.length >= 8)              score++
  if (pw.length >= 12)             score++
  if (/[A-Z]/.test(pw))           score++
  if (/[0-9]/.test(pw))           score++
  if (/[^A-Za-z0-9]/.test(pw))   score++
  if (score <= 1) return { pct:'25%',  cls:'pw-strength--weak',   label:'Weak' }
  if (score <= 2) return { pct:'50%',  cls:'pw-strength--fair',   label:'Fair' }
  if (score <= 3) return { pct:'75%',  cls:'pw-strength--good',   label:'Good' }
  return              { pct:'100%', cls:'pw-strength--strong', label:'Strong' }
})

const pwMismatch = computed(() =>
  form.value.new_password_confirmation &&
  form.value.new_password !== form.value.new_password_confirmation
)

async function loadUser() {
  try {
    const res = await api.get('/api/user')
    form.value.name  = res.data.name
    form.value.email = res.data.email
    lastUpdated.value = new Date().toLocaleDateString('en-US', { month:'short', day:'2-digit', year:'numeric' })
  } catch (e) { console.error(e) }
}
onMounted(loadUser)

async function saveChanges() {
  if (pwMismatch.value) return
  successMessage.value = ''
  errors.value         = []
  saving.value         = true
  try {
    await api.put('/api/user', { name: form.value.name, email: form.value.email })
    if (form.value.new_password) {
      await api.put('/api/user/password', {
        current_password:          form.value.current_password,
        new_password:              form.value.new_password,
        new_password_confirmation: form.value.new_password_confirmation,
      })
    }
    successMessage.value = 'Your changes have been saved successfully.'
    form.value.current_password          = ''
    form.value.new_password              = ''
    form.value.new_password_confirmation = ''
    lastUpdated.value = new Date().toLocaleDateString('en-US', { month:'short', day:'2-digit', year:'numeric' })
  } catch (err) {
    errors.value = err.response?.data?.errors
      ? Object.values(err.response.data.errors).flat()
      : ['Something went wrong. Please try again.']
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
/* ── TOKEN BRIDGE ── */
.settings-page {
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
  --s-shadow:         var(--c-shadow-sm);
  --s-shadow-md:      var(--c-shadow-md);
  --s-green:          #10b981;
  --s-red:            #f43f5e;
  --s-blue:           #3b82f6;
  --radius: 16px;

  min-height: 100%;
  background: var(--s-bg);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 24px;
  max-width: 860px;
  margin: 0 auto;
  transition: background-color .22s ease;
}

/* ── PAGE HEADER ── */
.breadcrumb {
  display: flex; align-items: center; gap: 8px;
  font-size: 12.5px; margin-bottom: 14px;
}
.breadcrumb-link {
  color: var(--s-text-muted); text-decoration: none; font-weight: 500;
  display: flex; align-items: center; gap: 5px;
  transition: color .15s;
}
.breadcrumb-link:hover { color: var(--s-accent); }
.breadcrumb-sep   { font-size: 9px; color: var(--s-text-faint); }
.breadcrumb-current { color: var(--s-text-secondary); font-weight: 600; }

.header-row {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 16px; flex-wrap: wrap;
}
.page-eyebrow {
  font-size: 11px; font-weight: 700; letter-spacing: .12em;
  color: var(--s-accent); text-transform: uppercase; margin: 0 0 5px;
}
.page-title {
  font-size: 26px; font-weight: 800;
  color: var(--s-text-primary);
  letter-spacing: -.03em; margin: 0 0 4px;
  transition: color .22s;
}
.page-sub {
  font-size: 13px; color: var(--s-text-muted); margin: 0;
}
.last-updated {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--s-text-faint);
  background: var(--s-surface-raised);
  border: 1px solid var(--s-border);
  border-radius: 8px; padding: 6px 12px;
  white-space: nowrap;
  transition: background .22s, border-color .22s;
}

/* ── ALERTS ── */
.alert {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 14px 16px; border-radius: 12px;
  font-size: 13.5px; font-weight: 500;
  border: 1px solid transparent;
  position: relative;
}
.alert--success {
  background: rgba(16,185,129,.10);
  border-color: rgba(16,185,129,.25);
  color: var(--s-green);
}
.alert--error {
  background: rgba(244,63,94,.09);
  border-color: rgba(244,63,94,.22);
  color: var(--s-red);
}
html[data-theme="dark"] .alert--success {
  background: rgba(16,185,129,.12);
  border-color: rgba(16,185,129,.28);
  color: #4ADE80;
}
html[data-theme="dark"] .alert--error {
  background: rgba(244,63,94,.13);
  border-color: rgba(244,63,94,.30);
  color: #F87171;
}
.alert-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
.alert-list { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 3px; flex: 1; }
.alert-close {
  background: none; border: none; cursor: pointer;
  color: inherit; opacity: .6; margin-left: auto; flex-shrink: 0;
  font-size: 13px; padding: 2px; line-height: 1;
  transition: opacity .15s;
}
.alert-close:hover { opacity: 1; }

.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s ease, transform .22s ease; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── SETTINGS FORM ── */
.settings-form { display: flex; flex-direction: column; gap: 20px; }

/* ── SETTINGS CARD ── */
.settings-card {
  background: var(--s-surface);
  border: 1px solid var(--s-border);
  border-radius: var(--radius);
  box-shadow: var(--s-shadow);
  overflow: hidden;
  transition: background-color .22s, border-color .22s;
}

.settings-card-header {
  display: flex; align-items: flex-start; gap: 14px;
  padding: 22px 24px 18px;
  border-bottom: 1px solid var(--s-border);
  transition: border-color .22s;
}
.settings-card-icon {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
}
.settings-card-icon--blue   { background: rgba(59,130,246,.12); color: var(--s-blue); }
.settings-card-icon--orange { background: var(--s-accent-soft); color: var(--s-accent); }

.settings-card-title {
  font-size: 15px; font-weight: 700; color: var(--s-text-primary);
  margin: 0 0 3px; transition: color .22s;
}
.settings-card-sub {
  font-size: 12.5px; color: var(--s-text-muted); margin: 0;
}

/* FORM FIELDS inside card */
.form-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 22px 24px; }
.form-stack { display: flex; flex-direction: column; gap: 0; }
.form-stack .form-field--full { padding: 22px 24px 0; }
.form-stack .form-grid { padding-top: 20px; padding-bottom: 22px; }

.form-field { display: flex; flex-direction: column; gap: 7px; }
.form-field--full { width: 100%; }

.form-label {
  font-size: 12.5px; font-weight: 600;
  color: var(--s-text-secondary);
  letter-spacing: .01em;
  transition: color .22s;
}

/* INPUT WRAP */
.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon {
  position: absolute; left: 13px;
  color: var(--s-text-faint); font-size: 12px;
  pointer-events: none; transition: color .22s;
  z-index: 1;
}

.form-input {
  width: 100%; height: 42px;
  padding: 0 14px 0 36px;
  border: 1.5px solid var(--s-border-strong);
  border-radius: 10px;
  background: var(--s-surface-sunken);
  color: var(--s-text-primary);
  font-size: 13.5px; font-family: inherit;
  outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-input::placeholder { color: var(--s-text-faint); }
.form-input:focus {
  border-color: var(--s-accent);
  box-shadow: 0 0 0 3px var(--s-ring);
  background: var(--s-surface);
}
.form-input:focus + .input-icon,
.input-wrap:focus-within .input-icon { color: var(--s-accent); }

/* password field has extra right padding for toggle */
.form-input--pw { padding-right: 42px; }

/* mismatch state */
.form-input--mismatch {
  border-color: var(--s-red) !important;
  box-shadow: 0 0 0 3px rgba(244,63,94,.12) !important;
}

/* pw toggle button */
.pw-toggle {
  position: absolute; right: 12px;
  background: none; border: none; cursor: pointer;
  color: var(--s-text-faint); font-size: 13px; padding: 4px;
  transition: color .15s; line-height: 1;
  display: flex; align-items: center;
}
.pw-toggle:hover { color: var(--s-text-muted); }

/* HINTS */
.form-hint { font-size: 11.5px; margin: 2px 0 0; }
.form-hint--error { color: var(--s-red); }

/* PASSWORD STRENGTH */
.pw-strength {
  display: flex; align-items: center; gap: 8px; margin-top: 6px;
}
.pw-strength-bar {
  flex: 1; height: 4px; border-radius: 2px;
  background: var(--s-border-strong);
  overflow: hidden;
}
.pw-strength-fill {
  height: 100%; border-radius: 2px;
  transition: width .3s ease, background-color .3s ease;
}
.pw-strength-label {
  font-size: 11px; font-weight: 700; letter-spacing: .04em;
  text-transform: uppercase; white-space: nowrap;
}

.pw-strength--weak   .pw-strength-fill  { background: #f43f5e; }
.pw-strength--fair   .pw-strength-fill  { background: #f59e0b; }
.pw-strength--good   .pw-strength-fill  { background: #3b82f6; }
.pw-strength--strong .pw-strength-fill  { background: #10b981; }

.pw-strength--weak   { color: #f43f5e; }
.pw-strength--fair   { color: #f59e0b; }
.pw-strength--good   { color: #3b82f6; }
.pw-strength--strong { color: #10b981; }

/* ── FORM ACTIONS ── */
.form-actions {
  display: flex; align-items: center; justify-content: flex-end;
  gap: 10px; padding-top: 4px;
}

.btn {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: 13.5px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 22px; cursor: pointer;
  border: 1.5px solid transparent;
  text-decoration: none;
  transition: background-color .18s, border-color .18s, color .18s, box-shadow .18s, opacity .18s, transform .15s;
}
.btn:active { transform: scale(0.98); }

.btn--ghost {
  background: var(--s-surface);
  border-color: var(--s-border-strong);
  color: var(--s-text-secondary);
}
.btn--ghost:hover {
  background: var(--s-surface-raised);
  border-color: var(--s-accent-border);
  color: var(--s-text-primary);
}

.btn--primary {
  background: linear-gradient(135deg, var(--s-accent) 0%, var(--c-accent-deep) 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover {
  filter: brightness(1.08);
  box-shadow: var(--c-shadow-accent-h);
  transform: translateY(-1px);
}
.btn--primary:disabled {
  opacity: .6; cursor: not-allowed;
  filter: none; transform: none; box-shadow: none;
}

/* ── RESPONSIVE ── */
@media (max-width: 640px) {
  .settings-page { padding: 16px 16px 48px; }
  .form-grid { grid-template-columns: 1fr; }
  .form-stack .form-grid { grid-template-columns: 1fr; }
  .header-row { flex-direction: column; }
  .form-actions { flex-direction: column-reverse; align-items: stretch; }
  .btn { justify-content: center; }
}
</style>