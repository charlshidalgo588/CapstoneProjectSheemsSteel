<template>
  <div class="lw">

    <!-- NAVBAR -->
    <header class="lw-nav">
      <div class="lw-nav-inner">
        <div class="lw-brand">
          <img src="@/assets/cutout_logo.png" alt="Shem Steel logo" class="lw-logo-img" />
          <div class="lw-brand-text">
            <div class="lw-brand-name">Shem Steel</div>
            <div class="lw-brand-sub">Construction Company</div>
          </div>
        </div>
        <div class="lw-nav-right">
          <span class="lw-nav-hint">Inventory &amp; Sales Management System</span>
          <div class="lw-online-pill">
            <span class="lw-online-dot"></span>
            System Online
          </div>
        </div>
      </div>
    </header>

    <!-- SPLIT -->
    <main class="lw-split">

      <!-- LEFT HERO -->
      <div class="lw-left">
        <div class="lw-mesh" aria-hidden="true"></div>
        <div class="lw-left-fade" aria-hidden="true"></div>

        <div class="lw-left-body">

          <div class="lw-eyebrow">
            <span class="lw-eyebrow-bar"></span>
            IMS · SMS Platform · Est. 2024
          </div>

          <h1 class="lw-h1">
            Steel-grade<br />
            <em class="lw-h1-em">precision</em><br />
            for your<br />
            operations.
          </h1>

          <p class="lw-tagline">
            Unified inventory tracking, real-time sales reporting, and intelligent
            dashboards — purpose-built for Shem Steel.
          </p>

          <div class="lw-features">
            <div class="lw-feat">
              <span class="lw-feat-icon">
                <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
              </span>
              <div>
                <div class="lw-feat-name">Live Inventory</div>
                <div class="lw-feat-desc">Real-time stock levels with low-stock alerts</div>
              </div>
            </div>
            <div class="lw-feat">
              <span class="lw-feat-icon">
                <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
              </span>
              <div>
                <div class="lw-feat-name">Automated Reports</div>
                <div class="lw-feat-desc">Daily &amp; monthly summaries, always ready</div>
              </div>
            </div>
            <div class="lw-feat">
              <span class="lw-feat-icon">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
              </span>
              <div>
                <div class="lw-feat-name">Secure Access</div>
                <div class="lw-feat-desc">Role-based permissions with full audit trail</div>
              </div>
            </div>
          </div>

          <div class="lw-stats">
            <div class="lw-stat">
              <span class="lw-stat-n">100%</span>
              <span class="lw-stat-l">Inventory Accuracy</span>
            </div>
            <span class="lw-stat-div" aria-hidden="true"></span>
            <div class="lw-stat">
              <span class="lw-stat-n">Live</span>
              <span class="lw-stat-l">Sales Tracking</span>
            </div>
            <span class="lw-stat-div" aria-hidden="true"></span>
            <div class="lw-stat">
              <span class="lw-stat-n">Auto</span>
              <span class="lw-stat-l">Reporting</span>
            </div>
          </div>

        </div>
      </div>

      <!-- RIGHT LOGIN -->
      <div class="lw-right">

        <div class="lw-card">

          <!-- Card header -->
          <div class="lw-card-head">
            <div class="lw-card-brand">
              <img src="@/assets/cutout_logo.png" alt="" class="lw-card-logo-img" aria-hidden="true" />
              <div>
                <div class="lw-card-title">Shem Steel</div>
                <div class="lw-card-title-sub">Management Portal</div>
              </div>
            </div>
            <div class="lw-secure-tag">
              <i class="fa-solid fa-lock" aria-hidden="true"></i>
              Secure
            </div>
          </div>

          <!-- Divider label -->
          <div class="lw-card-divider">
            <span>Sign in to your account</span>
          </div>

          <!-- Card body -->
          <div class="lw-card-body">

            <Transition name="lw-shake">
              <div v-if="error" class="lw-error" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                {{ error }}
              </div>
            </Transition>

            <form @submit.prevent="submitLogin" class="lw-form" novalidate>

              <div class="lw-field">
                <label for="lw-email" class="lw-lbl">Email address</label>
                <div class="lw-input-wrap">
                  <i class="fa-solid fa-envelope lw-ico" aria-hidden="true"></i>
                  <input
                    id="lw-email"
                    v-model="email"
                    type="email"
                    required
                    placeholder="you@sheems.example"
                    class="lw-inp"
                    autocomplete="email"
                  />
                </div>
              </div>

              <div class="lw-field">
                <div class="lw-lbl-row">
                  <label for="lw-pass" class="lw-lbl">Password</label>
                  <button type="button" class="lw-forgot">Forgot password?</button>
                </div>
                <div class="lw-input-wrap">
                  <i class="fa-solid fa-lock lw-ico" aria-hidden="true"></i>
                  <input
                    id="lw-pass"
                    :type="showPassword ? 'text' : 'password'"
                    v-model="password"
                    placeholder="••••••••"
                    class="lw-inp"
                    autocomplete="current-password"
                  />
                  <button
                    type="button"
                    class="lw-eye"
                    @click="showPassword = !showPassword"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  >
                    <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" aria-hidden="true"></i>
                  </button>
                </div>
              </div>

              <label class="lw-remember">
                <input type="checkbox" v-model="remember" class="lw-chk" />
                <span>Keep me signed in for 30 days</span>
              </label>

              <button type="submit" class="lw-btn" :disabled="loading">
                <i v-if="loading" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                <i v-else class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
                <span>{{ loading ? 'Signing in…' : 'Sign in' }}</span>
              </button>

            </form>
          </div>

          <!-- Card footer -->
          <div class="lw-card-foot">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            Need access? Contact your system administrator.
          </div>

        </div>

      </div>

    </main>

    <!-- FOOTER -->
    <footer class="lw-footer">
      <span>© {{ year }} Shem Steel Construction Company. All rights reserved.</span>
      <div class="lw-footer-right">
        <span>support@sheems.example</span>
        <span class="lw-sep" aria-hidden="true">·</span>
        <span>+63 912 345 6789</span>
      </div>
    </footer>

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()

const year         = new Date().getFullYear()
const email        = ref('')
const password     = ref('')
const remember     = ref(false)
const showPassword = ref(false)
const loading      = ref(false)
const error        = ref('')

async function submitLogin() {
  error.value   = ''
  loading.value = true
  try {
    await auth.login({ email: email.value, password: password.value, remember: remember.value })
    router.push('/home')
  } catch (err: any) {
    error.value = err?.response?.data?.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ─── TOKENS ─────────────────────────────── */
/* Orange  : #F07020  (logo primary)          */
/* Orange dk: #C85810                         */
/* Steel-B : #3D7FC1  (logo house accent)     */
/* Steel-B lt: #E8F1FB                        */
/* Warm-wh : #FAFAF8  (surfaces)              */
/* Off-wh  : #F4F2ED  (page bg)               */
/* Border  : #E4DDD3                          */
/* Text-dk : #18120A                          */
/* Text-md : #5C4830                          */
/* Text-sm : #A08060                          */

/* ─── ROOT ───────────────────────────────── */
.lw {
  display: flex;
  flex-direction: column;
  width: 100vw;
  height: 100vh;
  font-family: 'Inter', 'DM Sans', system-ui, -apple-system, sans-serif;
  background: #F4F2ED;
  overflow: hidden;
  color: #18120A;
}

/* ─── NAVBAR ─────────────────────────────── */
.lw-nav {
  flex-shrink: 0;
  height: 54px;
  background: #FFFFFF;
  border-bottom: 1px solid #E8E0D4;
  z-index: 10;
}
.lw-nav-inner {
  height: 100%;
  padding: 0 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.lw-brand { display: flex; align-items: center; gap: 10px; }
.lw-logo-img {
  width: 34px;
  height: 34px;
  object-fit: contain;
  flex-shrink: 0;
}
.lw-brand-text { display: flex; flex-direction: column; gap: 1px; }
.lw-brand-name {
  font-size: 13px;
  font-weight: 700;
  color: #18120A;
  letter-spacing: -0.01em;
  line-height: 1.25;
}
.lw-brand-sub {
  font-size: 9.5px;
  color: #A08060;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  line-height: 1;
}
.lw-nav-right { display: flex; align-items: center; gap: 12px; }
.lw-nav-hint { font-size: 11.5px; color: #B8A080; }
.lw-online-pill {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 10.5px;
  font-weight: 600;
  color: #15803D;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  padding: 3px 10px;
  border-radius: 100px;
}
.lw-online-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #22C55E;
  animation: lw-pulse 2.5s ease-in-out infinite;
}
@keyframes lw-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.25; } }

/* ─── SPLIT ──────────────────────────────── */
.lw-split {
  flex: 1;
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 0;
}

/* ─── LEFT ───────────────────────────────── */
.lw-left {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  background: #FAFAF8;
}

/*
  Signature element: isometric steel-mesh grid — 
  two diagonal line sets at +45° and -45° overlaid,
  evoking actual structural steel lattice.
  Kept very subtle so content reads clearly.
*/
.lw-mesh {
  position: absolute;
  inset: 0;
  background-image:
    repeating-linear-gradient(
      45deg,
      transparent,
      transparent 38px,
      rgba(61, 127, 193, 0.07) 38px,
      rgba(61, 127, 193, 0.07) 39px
    ),
    repeating-linear-gradient(
      -45deg,
      transparent,
      transparent 38px,
      rgba(61, 127, 193, 0.07) 38px,
      rgba(61, 127, 193, 0.07) 39px
    );
  pointer-events: none;
}

.lw-left-fade {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 65% 70% at 10% 50%, rgba(250, 250, 248, 0.96) 0%, rgba(250, 250, 248, 0.6) 60%, transparent 100%),
    radial-gradient(ellipse 50% 40% at 50% 100%, rgba(240, 112, 32, 0.05) 0%, transparent 70%);
  pointer-events: none;
  z-index: 1;
}

.lw-left-body {
  position: relative;
  z-index: 2;
  padding: 0 52px 0 48px;
  width: 100%;
}

.lw-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 9px;
  font-weight: 700;
  color: #F07020;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  margin-bottom: 18px;
}
.lw-eyebrow-bar {
  display: block;
  width: 18px;
  height: 2px;
  background: #F07020;
  border-radius: 2px;
}

.lw-h1 {
  font-size: clamp(28px, 3vw, 46px);
  font-weight: 800;
  line-height: 1.06;
  color: #18120A;
  letter-spacing: -0.04em;
  margin-bottom: 14px;
}
.lw-h1-em {
  font-style: italic;
  color: #F07020;
}

.lw-tagline {
  font-size: 12.5px;
  line-height: 1.85;
  color: #7A5030;
  max-width: 310px;
  margin-bottom: 26px;
}

/* Feature rows */
.lw-features { display: flex; flex-direction: column; gap: 8px; margin-bottom: 26px; }
.lw-feat {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 9px 13px;
  background: #FFFFFF;
  border: 1px solid #EAE0D0;
  border-left: 3px solid #F07020;
  border-radius: 10px;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.lw-feat:hover {
  border-color: #D4A070;
  border-left-color: #C85810;
  box-shadow: 0 2px 10px rgba(240, 112, 32, 0.08);
}
.lw-feat-icon {
  width: 30px;
  height: 30px;
  border-radius: 7px;
  background: #FFF3E8;
  border: 1px solid #FAD8B4;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11.5px;
  color: #F07020;
  flex-shrink: 0;
}
.lw-feat-name { font-size: 12px; font-weight: 700; color: #1A0E04; line-height: 1.2; }
.lw-feat-desc { font-size: 10.5px; color: #9A6840; margin-top: 1px; }

/* Stats */
.lw-stats {
  display: flex;
  align-items: center;
  gap: 18px;
  padding-top: 18px;
  border-top: 1px solid #E4DDD3;
}
.lw-stat { display: flex; flex-direction: column; gap: 3px; }
.lw-stat-n {
  font-size: 16px;
  font-weight: 800;
  color: #18120A;
  letter-spacing: -0.02em;
}
.lw-stat-l {
  font-size: 8.5px;
  text-transform: uppercase;
  letter-spacing: 0.13em;
  color: #A08060;
}
.lw-stat-div {
  width: 1px;
  height: 26px;
  background: #E4DDD3;
  flex-shrink: 0;
}

/* ─── RIGHT ──────────────────────────────── */
.lw-right {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px 36px;
  background: #F4F2ED;
  overflow: hidden;
}

/* ─── CARD ───────────────────────────────── */
.lw-card {
  width: 100%;
  max-width: 390px;
  background: #FFFFFF;
  border-radius: 18px;
  border: 1px solid #E0D4C4;
  overflow: hidden;
  position: relative;
  z-index: 1;
  box-shadow:
    0 1px 2px rgba(80, 40, 10, 0.04),
    0 4px 16px rgba(80, 40, 10, 0.07),
    0 16px 48px rgba(80, 40, 10, 0.07);
}

/* Card header */
.lw-card-head {
  padding: 16px 20px 14px;
  background: #FAFAF8;
  border-bottom: 1px solid #EDE6D8;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.lw-card-brand { display: flex; align-items: center; gap: 10px; }
.lw-card-logo-img {
  width: 38px;
  height: 38px;
  object-fit: contain;
  flex-shrink: 0;
}
.lw-card-title {
  font-size: 14px;
  font-weight: 700;
  color: #18120A;
  letter-spacing: -0.01em;
  line-height: 1.25;
}
.lw-card-title-sub { font-size: 10.5px; color: #A08060; margin-top: 2px; }
.lw-secure-tag {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 9.5px;
  font-weight: 700;
  color: #166534;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  padding: 3px 9px;
  border-radius: 100px;
  letter-spacing: 0.03em;
}
.lw-secure-tag i { font-size: 9px; }

/* Divider */
.lw-card-divider {
  display: flex;
  align-items: center;
  padding: 0 20px;
  margin-top: 16px;
}
.lw-card-divider::before,
.lw-card-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #EDE6D8;
}
.lw-card-divider span {
  font-size: 9.5px;
  font-weight: 700;
  color: #C4A882;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  padding: 0 12px;
  white-space: nowrap;
}

/* Card body */
.lw-card-body { padding: 14px 20px 18px; }

/* Error */
.lw-error {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 9px 12px;
  background: #FFF4F0;
  border: 1px solid #FDCFB8;
  border-left: 3px solid #F07020;
  border-radius: 9px;
  font-size: 12px;
  color: #8A2800;
  margin-bottom: 12px;
}
.lw-shake-enter-active { animation: lw-shake 0.36s ease; }
@keyframes lw-shake {
  0%, 100% { transform: translateX(0); }
  25%       { transform: translateX(-5px); }
  75%       { transform: translateX(5px); }
}

/* Form */
.lw-form { display: flex; flex-direction: column; gap: 11px; }
.lw-field { display: flex; flex-direction: column; gap: 5px; }
.lw-lbl-row { display: flex; align-items: center; justify-content: space-between; }
.lw-lbl {
  font-size: 9px;
  font-weight: 700;
  color: #5C3818;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}
.lw-forgot {
  font-size: 11px;
  font-weight: 600;
  color: #F07020;
  background: none;
  border: none;
  cursor: pointer;
  font-family: inherit;
  padding: 0;
  transition: opacity 0.15s;
}
.lw-forgot:hover { opacity: 0.65; }

/* Inputs */
.lw-input-wrap { position: relative; display: flex; align-items: center; }
.lw-ico {
  position: absolute;
  left: 0;
  width: 38px;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  color: #C8A882;
  pointer-events: none;
  z-index: 1;
}
.lw-inp {
  width: 100%;
  height: 40px;
  border: 1.5px solid #DDD0BC;
  border-radius: 9px;
  padding: 0 38px 0 36px;
  font-size: 13px;
  color: #18120A;
  background: #FDFCFA;
  outline: none;
  font-family: inherit;
  transition: border-color 0.13s, box-shadow 0.13s, background 0.13s;
}
.lw-inp::placeholder { color: #D0B898; }
.lw-inp:hover { border-color: #BEA07A; background: #FFFFFF; }
.lw-inp:focus {
  border-color: #F07020;
  background: #FFFFFF;
  box-shadow: 0 0 0 3px rgba(240, 112, 32, 0.12);
}
.lw-eye {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  cursor: pointer;
  color: #C8A882;
  font-size: 12px;
  padding: 4px;
  transition: color 0.13s;
  line-height: 1;
}
.lw-eye:hover { color: #F07020; }

/* Remember */
.lw-remember {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #7A5030;
  cursor: pointer;
  user-select: none;
}
.lw-chk {
  width: 14px;
  height: 14px;
  accent-color: #F07020;
  cursor: pointer;
  flex-shrink: 0;
}

/* Submit */
.lw-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  height: 42px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.015em;
  color: #FFFFFF;
  background: #F07020;
  transition: background 0.13s, transform 0.1s, box-shadow 0.13s, opacity 0.13s;
  box-shadow:
    0 1px 2px rgba(120, 50, 0, 0.12),
    0 3px 10px rgba(240, 112, 32, 0.30),
    inset 0 1px 0 rgba(255, 255, 255, 0.15);
}
.lw-btn:hover:not(:disabled) {
  background: #C85810;
  transform: translateY(-1px);
  box-shadow:
    0 2px 4px rgba(120, 50, 0, 0.16),
    0 6px 18px rgba(240, 112, 32, 0.36),
    inset 0 1px 0 rgba(255, 255, 255, 0.12);
}
.lw-btn:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 1px 3px rgba(120, 50, 0, 0.12);
}
.lw-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.lw-btn:focus-visible {
  outline: 3px solid rgba(240, 112, 32, 0.38);
  outline-offset: 2px;
}

/* Steel-blue accent line at top of btn for premium feel */
.lw-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: 20%;
  right: 20%;
  height: 1px;
  background: rgba(255, 255, 255, 0.22);
  border-radius: 100px;
}
.lw-btn { position: relative; }

/* Card footer */
.lw-card-foot {
  padding: 10px 20px 12px;
  background: #FAFAF8;
  border-top: 1px solid #EDE6D8;
  font-size: 10.5px;
  color: #B89570;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}
.lw-card-foot i { font-size: 10px; }

/* ─── FOOTER ─────────────────────────────── */
.lw-footer {
  flex-shrink: 0;
  height: 40px;
  background: #FFFFFF;
  border-top: 1px solid #E8E0D4;
  padding: 0 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 10.5px;
  color: #C0A880;
}
.lw-footer-right { display: flex; align-items: center; gap: 8px; }
.lw-sep { opacity: 0.35; }

/* ─── RESPONSIVE ─────────────────────────── */
@media (max-width: 960px) {
  .lw { height: auto; overflow: auto; }
  .lw-split { grid-template-columns: 1fr; height: auto; }
  .lw-left { min-height: 360px; border-bottom: 1px solid #E8E0D4; }
  .lw-left-body { padding: 40px 32px 56px; }
  .lw-right { padding: 28px 20px 40px; }
  .lw-nav-hint { display: none; }
  .lw-footer {
    flex-direction: column;
    height: auto;
    gap: 4px;
    padding: 10px 20px;
    text-align: center;
  }
}
@media (max-width: 480px) {
  .lw-nav-inner { padding: 0 16px; }
  .lw-left-body { padding: 32px 18px 48px; }
  .lw-right { padding: 20px 12px 32px; }
  .lw-card-head,
  .lw-card-body,
  .lw-card-foot,
  .lw-card-divider { padding-left: 16px; padding-right: 16px; }
}
</style>