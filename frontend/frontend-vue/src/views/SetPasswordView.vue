<template>
  <div class="sp">
    <div class="sp-mesh" aria-hidden="true"></div>

    <div class="sp-card">
      <template v-if="success">
        <div class="sp-icon-wrap sp-icon-wrap--success">
          <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        </div>
        <h1 class="sp-title">Password set</h1>
        <p class="sp-desc">Taking you to your dashboard…</p>
      </template>

      <template v-else>
      <div class="sp-icon-wrap">
        <i class="fa-solid fa-key" aria-hidden="true"></i>
      </div>

      <h1 class="sp-title">Set your password</h1>
      <p class="sp-desc">
        You're signing in with a temporary password. Choose your own before continuing —
        you won't be asked again.
      </p>

      <Transition name="sp-shake">
        <div v-if="error" class="sp-error" role="alert">
          <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
          {{ error }}
        </div>
      </Transition>

      <form @submit.prevent="submit" class="sp-form" novalidate>
        <div class="sp-field">
          <label for="sp-new" class="sp-lbl">New password</label>
          <div class="sp-input-wrap">
            <i class="fa-solid fa-lock sp-ico" aria-hidden="true"></i>
            <input
              id="sp-new"
              :type="showPw ? 'text' : 'password'"
              v-model="password"
              class="sp-inp"
              :class="{ 'sp-inp--error': fieldErrors.password }"
              placeholder="At least 8 characters"
              autocomplete="new-password"
              @input="fieldErrors.password = ''"
            />
            <button type="button" class="sp-eye" @click="showPw = !showPw" :aria-label="showPw ? 'Hide password' : 'Show password'">
              <i :class="showPw ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" aria-hidden="true"></i>
            </button>
          </div>
          <div class="sp-strength" v-if="password">
            <div class="sp-strength-bar">
              <div class="sp-strength-fill" :class="pwStrength.cls" :style="{ width: pwStrength.pct }"></div>
            </div>
            <span class="sp-strength-label" :class="pwStrength.cls">{{ pwStrength.label }}</span>
          </div>
          <p v-if="fieldErrors.password" class="sp-field-error">{{ fieldErrors.password }}</p>
        </div>

        <div class="sp-field">
          <label for="sp-confirm" class="sp-lbl">Confirm new password</label>
          <div class="sp-input-wrap">
            <i class="fa-solid fa-lock sp-ico" aria-hidden="true"></i>
            <input
              id="sp-confirm"
              :type="showPw ? 'text' : 'password'"
              v-model="confirmPassword"
              class="sp-inp"
              :class="{ 'sp-inp--error': fieldErrors.confirm }"
              placeholder="Type it again"
              autocomplete="new-password"
              @input="fieldErrors.confirm = ''"
            />
          </div>
          <p v-if="fieldErrors.confirm" class="sp-field-error">{{ fieldErrors.confirm }}</p>
        </div>

        <button type="submit" class="sp-btn" :disabled="loading">
          <i v-if="loading" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
          <i v-else class="fa-solid fa-check" aria-hidden="true"></i>
          <span>{{ loading ? 'Saving…' : 'Set password & continue' }}</span>
        </button>
      </form>

      <button type="button" class="sp-signout" @click="signOut">
        Not you? Sign out
      </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const router = useRouter()
const auth   = useAuthStore()

const password        = ref('')
const confirmPassword = ref('')
const showPw          = ref(false)
const loading         = ref(false)
const success         = ref(false)
const error           = ref('')
const fieldErrors     = ref({ password: '', confirm: '' })

const pwStrength = computed(() => {
  const pw = password.value
  if (!pw) return { pct: '0%', cls: '', label: '' }
  let score = 0
  if (pw.length >= 8)            score++
  if (pw.length >= 12)           score++
  if (/[A-Z]/.test(pw))          score++
  if (/[0-9]/.test(pw))          score++
  if (/[^A-Za-z0-9]/.test(pw))   score++
  if (score <= 1) return { pct: '25%',  cls: 'sp-strength--weak',   label: 'Weak' }
  if (score <= 2) return { pct: '50%',  cls: 'sp-strength--fair',   label: 'Fair' }
  if (score <= 3) return { pct: '75%',  cls: 'sp-strength--good',   label: 'Good' }
  return                { pct: '100%', cls: 'sp-strength--strong', label: 'Strong' }
})

function validate() {
  const errs = { password: '', confirm: '' }
  if (!password.value || password.value.length < 8) {
    errs.password = 'Use at least 8 characters.'
  }
  if (confirmPassword.value !== password.value) {
    errs.confirm = 'Passwords don\u2019t match.'
  }
  fieldErrors.value = errs
  return !errs.password && !errs.confirm
}

async function submit() {
  error.value = ''
  if (!validate()) return

  loading.value = true
  try {
    await api.put('/api/user/set-initial-password', {
      password: password.value,
      password_confirmation: confirmPassword.value,
    })
    // Patch the store directly rather than trusting fetchUser() to pick
    // this up — if the auth store dedupes/skips re-fetching once
    // `authenticated` is already true (worth checking in stores/auth.js),
    // fetchUser() here could hand back a stale cached user that still
    // shows must_change_password: true, and the guard would bounce us
    // straight back to this same screen. We already know the save
    // succeeded, so just reflect that locally.
    if (auth.user) {
      auth.user.must_change_password = false
    }
    loading.value = false
    success.value = true
    // Brief pause so the success state is actually seen rather than
    // flashing past on the way to /home — long enough to register,
    // short enough not to feel like a stall.
    setTimeout(() => router.push('/home'), 1100)
  } catch (err) {
    loading.value = false
    error.value = err?.response?.data?.message || 'Couldn\u2019t update your password. Try again.'
  }
}

async function signOut() {
  try { await api.post('/logout') }
  catch (e) { console.error('Logout error:', e) }
  finally {
    auth.user = null
    auth.authenticated = false
    router.push('/login')
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.sp {
  --sp-accent:       #F07020;
  --sp-accent-dark:  #C85810;
  --sp-bg:           #F4F2ED;
  --sp-card-bg:      #FFFFFF;
  --sp-card-border:  #E5DACA;
  --sp-border:       #E4DDD3;
  --sp-border-input: #DDD0BC;
  --sp-text-primary: #14100A;
  --sp-text-muted:   #7A5030;
  --sp-text-faint:   #A08060;
  --sp-placeholder:  #D0B898;
  --sp-error-bg:     #FFF4F0;
  --sp-error-border: #FDCFB8;
  --sp-error-text:   #8A2800;

  min-height: 100vh;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--sp-bg);
  font-family: 'Inter', system-ui, sans-serif;
  position: relative;
  overflow: hidden;
  padding: 20px;
  transition: background-color 0.22s ease;
}

html[data-theme="dark"] .sp {
  --sp-bg:           #0F172A;
  --sp-card-bg:      #1E293B;
  --sp-card-border:  #334155;
  --sp-border:       #334155;
  --sp-border-input: #334155;
  --sp-text-primary: #F1F5F9;
  --sp-text-muted:   #94A3B8;
  --sp-text-faint:   #64748B;
  --sp-placeholder:  #64748B;
  --sp-error-bg:     rgba(244, 63, 94, 0.14);
  --sp-error-border: rgba(244, 63, 94, 0.32);
  --sp-error-text:   #FB7185;
}

.sp-mesh {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(61,127,193,0.06) 1px, transparent 1px),
    linear-gradient(90deg, rgba(61,127,193,0.06) 1px, transparent 1px);
  background-size: 42px 42px;
  pointer-events: none;
}

.sp-card {
  position: relative;
  width: 100%;
  max-width: 400px;
  background: var(--sp-card-bg);
  border: 1px solid var(--sp-card-border);
  border-radius: 20px;
  padding: 36px 32px 30px;
  text-align: center;
  box-shadow: 0 8px 24px rgba(80,40,10,0.08), 0 24px 64px rgba(80,40,10,0.08);
  transition: background-color 0.22s ease, border-color 0.22s ease;
}

.sp-icon-wrap {
  width: 52px; height: 52px;
  border-radius: 14px;
  margin: 0 auto 16px;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(145deg, #FFF3E8, #FDE6D0);
  border: 1px solid #FAD8B4;
  color: var(--sp-accent);
  font-size: 19px;
}
html[data-theme="dark"] .sp-icon-wrap {
  background: linear-gradient(145deg, rgba(240,112,32,0.20), rgba(240,112,32,0.10));
  border-color: rgba(240,112,32,0.35);
}

.sp-title { font-size: 18px; font-weight: 700; color: var(--sp-text-primary); margin-bottom: 8px; letter-spacing: -0.01em; }
.sp-desc  { font-size: 12.5px; color: var(--sp-text-muted); line-height: 1.7; margin-bottom: 20px; }

.sp-error {
  display: flex; align-items: center; gap: 7px;
  padding: 10px 13px;
  background: var(--sp-error-bg);
  border: 1px solid var(--sp-error-border);
  border-left: 3px solid var(--sp-accent);
  border-radius: 10px;
  font-size: 12px; color: var(--sp-error-text);
  margin-bottom: 14px; text-align: left;
}
.sp-shake-enter-active { animation: sp-shake 0.36s ease; }
@keyframes sp-shake {
  0%, 100% { transform: translateX(0); }
  25%      { transform: translateX(-5px); }
  75%      { transform: translateX(5px); }
}

.sp-form { display: flex; flex-direction: column; gap: 14px; text-align: left; }
.sp-field { display: flex; flex-direction: column; gap: 5px; }
.sp-lbl { font-size: 9px; font-weight: 700; color: var(--sp-text-muted); text-transform: uppercase; letter-spacing: 0.1em; }

.sp-input-wrap { position: relative; display: flex; align-items: center; }
.sp-ico {
  position: absolute; left: 0; width: 38px; height: 100%;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; color: var(--sp-text-faint); pointer-events: none;
}
.sp-inp {
  width: 100%; height: 41px;
  border: 1.5px solid var(--sp-border-input);
  border-radius: 10px;
  padding: 0 38px;
  font-size: 13px; color: var(--sp-text-primary);
  background: var(--sp-card-bg);
  outline: none; font-family: inherit;
  transition: border-color 0.13s, box-shadow 0.13s;
}
.sp-inp::placeholder { color: var(--sp-placeholder); }
.sp-inp:focus { border-color: var(--sp-accent); box-shadow: 0 0 0 3.5px rgba(240,112,32,0.13); }
.sp-inp--error { border-color: var(--sp-error-text); }

.sp-eye {
  position: absolute; right: 10px;
  background: none; border: none; cursor: pointer;
  color: var(--sp-text-faint); font-size: 12px; padding: 4px; line-height: 1;
}
.sp-eye:hover { color: var(--sp-accent); }

.sp-field-error { font-size: 10.5px; color: var(--sp-error-text); font-weight: 500; }

.sp-strength { display: flex; align-items: center; gap: 8px; }
.sp-strength-bar { flex: 1; height: 4px; border-radius: 2px; background: var(--sp-border); overflow: hidden; }
.sp-strength-fill { height: 100%; border-radius: 2px; transition: width 0.3s ease, background-color 0.3s ease; }
.sp-strength-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap; }
.sp-strength--weak   .sp-strength-fill { background: #f43f5e; }
.sp-strength--fair   .sp-strength-fill { background: #f59e0b; }
.sp-strength--good   .sp-strength-fill { background: #3b82f6; }
.sp-strength--strong .sp-strength-fill { background: #10b981; }
.sp-strength--weak   { color: #f43f5e; }
.sp-strength--fair   { color: #f59e0b; }
.sp-strength--good   { color: #3b82f6; }
.sp-strength--strong { color: #10b981; }

.sp-btn {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%; height: 43px;
  border: none; border-radius: 11px;
  cursor: pointer; font-family: inherit;
  font-size: 13px; font-weight: 700; color: #fff;
  background: linear-gradient(135deg, #F58330 0%, #F07020 45%, #D9640F 100%);
  box-shadow: 0 4px 14px rgba(240,112,32,0.34);
  transition: transform 0.1s, box-shadow 0.13s, opacity 0.13s;
  margin-top: 2px;
}
.sp-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(240,112,32,0.42); }
.sp-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.sp-signout {
  display: block; margin: 18px auto 0;
  background: none; border: none; cursor: pointer;
  font-family: inherit; font-size: 11.5px; font-weight: 600;
  color: var(--sp-text-faint);
  transition: color 0.15s ease;
}
.sp-signout:hover { color: var(--sp-text-muted); }
</style>