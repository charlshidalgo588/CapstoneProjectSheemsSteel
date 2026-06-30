<template>
  <div class="app-shell" :style="{ '--sw': sidebarOpen ? '260px' : '64px' }">

    <!-- ═══════════════════════════════════════════════════
         GLOBAL LOADING OVERLAY
    ════════════════════════════════════════════════════ -->
    <transition name="loader-fade">
      <div v-if="loading" class="loading-overlay" role="status" aria-label="Loading">
        <div class="loading-bar"><div class="loading-bar-fill"></div></div>
        <img :src="logo" class="loading-logo" alt="Sheem Steel" />
        <div class="loading-dots">
          <span class="dot" style="--i:0"></span>
          <span class="dot" style="--i:1"></span>
          <span class="dot" style="--i:2"></span>
        </div>
        <p class="loading-text">Loading…</p>
      </div>
    </transition>

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <button class="burger" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar">
          <span class="bline" />
          <span class="bline bline--mid" :class="{ 'bline--mid-hidden': !sidebarOpen }" />
          <span class="bline" />
        </button>
        <router-link v-show="sidebarOpen" to="/home" class="brand-wrap">
          <img :src="logo" class="brand-logo" alt="Sheem Steel" />
          <div class="brand-text">
            <span class="brand-name">Sheem Steel</span>
            <span class="brand-sub">POS &amp; Inventory</span>
          </div>
        </router-link>
      </div>

      <div class="sidebar-divider" />

      <nav class="sidebar-nav">
        <NavLink to="/home"           icon="fa-solid fa-house"         label="Home"           :collapsed="!sidebarOpen" />
        <SidebarDropdown label="Inventory" icon="fa-solid fa-boxes-stacked" :open="inventoryOpen" :collapsed="!sidebarOpen" @toggle="inventoryOpen = !inventoryOpen">
          <NavLink to="/products"        menu-item label="Product List" icon="fa-solid fa-list"        :collapsed="false" />
          <NavLink to="/products/create" menu-item label="Add Item"     icon="fa-solid fa-plus"        :collapsed="false" />
          <NavLink to="/categories"      menu-item label="Categories"   icon="fa-solid fa-layer-group" :collapsed="false" />
        </SidebarDropdown>
        <SidebarDropdown label="Sales" icon="fa-solid fa-cart-shopping" :open="salesOpen" :collapsed="!sidebarOpen" @toggle="salesOpen = !salesOpen">
          <NavLink to="/point-of-sale"     menu-item label="POS"         icon="fa-solid fa-cash-register" :collapsed="false" />
          <NavLink to="/sales-transaction" menu-item label="Transaction" icon="fa-solid fa-receipt"       :collapsed="false" />
        </SidebarDropdown>
        <NavLink to="/suppliers"      label="Suppliers"      icon="fa-solid fa-truck-field"  :collapsed="!sidebarOpen" />
        <NavLink to="/reports"        label="Reports"        icon="fa-solid fa-chart-line"   :collapsed="!sidebarOpen" />
        <NavLink to="/inventory-logs" label="Inventory Logs" icon="fa-solid fa-folder-open"  :collapsed="!sidebarOpen" />
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-divider" />
        <div class="user-mini" :class="{ 'user-mini--center': !sidebarOpen }">
          <div class="user-avatar-wrap">
            <img :src="profileIcon" class="user-avatar-sm" />
            <span class="user-status-dot"></span>
          </div>
          <div v-show="sidebarOpen" class="user-mini-info">
            <p class="user-mini-name">{{ user.name }}</p>
            <p class="user-mini-role">Administrator</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- MAIN COLUMN -->
    <div class="main-column">

      <!-- TOP BAR -->
      <header class="topbar">
        <div class="topbar-greeting">
          <p class="greeting-name">Hello, <span>{{ user.name }}</span>!</p>
          <p class="greeting-sub">Welcome back — here's what's happening today.</p>
        </div>

        <div class="search-wrap">
          <i class="fa-solid fa-magnifying-glass search-icon"></i>
          <input v-model="search" @keyup.enter="goToProducts" class="search-input" placeholder="Search products…" />
          <kbd class="search-kbd">↵</kbd>
        </div>

        <div class="topbar-actions">
          <router-link to="/products/create">
            <button class="action-btn action-btn--primary" title="Add Product">
              <i class="fa-solid fa-plus"></i>
              <span v-show="sidebarOpen">Add Item</span>
            </button>
          </router-link>

          <!-- THEME TOGGLE — pill-style switch -->
          <button class="theme-pill" @click="toggleDark" :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'" :title="darkMode ? 'Light mode' : 'Dark mode'">
            <!-- Track -->
            <span class="pill-track" :class="{ 'pill-track--dark': darkMode }">
              <!-- Sun icon (light) -->
              <span class="pill-icon pill-icon--sun" :class="{ 'pill-icon--active': !darkMode }">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="4"/>
                  <line x1="12" y1="2"  x2="12" y2="5"/>
                  <line x1="12" y1="19" x2="12" y2="22"/>
                  <line x1="4.22" y1="4.22"   x2="6.34" y2="6.34"/>
                  <line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/>
                  <line x1="2"  y1="12" x2="5"  y2="12"/>
                  <line x1="19" y1="12" x2="22" y2="12"/>
                  <line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/>
                  <line x1="17.66" y1="6.34"  x2="19.78" y2="4.22"/>
                </svg>
              </span>
              <!-- Thumb -->
              <span class="pill-thumb" :class="{ 'pill-thumb--right': darkMode }"></span>
              <!-- Moon icon (dark) -->
              <span class="pill-icon pill-icon--moon" :class="{ 'pill-icon--active': darkMode }">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
              </span>
            </span>
          </button>

          <button class="icon-btn" title="Notifications" style="position:relative">
            <i class="fa-solid fa-bell"></i>
            <span class="notif-dot"></span>
          </button>

          <div class="profile-wrap">
            <button class="profile-trigger" @click="profileOpen = !profileOpen">
              <img :src="profileIcon" class="profile-avatar" />
              <div class="profile-info">
                <p class="profile-name">{{ user.name }}</p>
                <p class="profile-role">Admin</p>
              </div>
              <i class="fa-solid fa-chevron-down profile-caret" :class="{ 'profile-caret--up': profileOpen }"></i>
            </button>
            <transition name="dropdown">
              <div v-if="profileOpen" class="profile-menu">
                <div class="profile-menu-header">
                  <div class="pmenu-avatar-wrap">
                    <img :src="profileIcon" class="profile-menu-avatar" />
                    <span class="pmenu-status-dot"></span>
                  </div>
                  <div>
                    <p class="profile-menu-name">{{ user.name }}</p>
                    <p class="profile-menu-email">admin@sheemsteel.com</p>
                  </div>
                </div>
                <div class="pmenu-divider" />
                <router-link to="/settings" class="pmenu-item" @click="profileOpen = false">
                  <span class="pmenu-icon-wrap"><i class="fa-solid fa-gear"></i></span>
                  Settings
                </router-link>
                <div class="pmenu-divider" />
                <button class="pmenu-item pmenu-item--danger" @click="showLogoutModal = true; profileOpen = false">
                  <span class="pmenu-icon-wrap pmenu-icon-wrap--danger"><i class="fa-solid fa-right-from-bracket"></i></span>
                  Sign Out
                </button>
              </div>
            </transition>
          </div>
        </div>
      </header>

      <!-- PAGE CONTENT -->
      <main class="page-content">
        <div class="content-inner">
          <slot />
        </div>
      </main>
    </div>

    <!-- ═══════════════════════════════════════════════════
         GLOBAL FLOATING "GO TO POS" BUTTON
         Lives in Layout so every page that wraps with <Layout>
         gets it automatically. Hidden specifically on the POS
         page itself (no point linking to the page you're on).
    ════════════════════════════════════════════════════ -->
    <router-link
      v-if="!isPosPage"
      to="/point-of-sale"
      class="pos-fab"
      aria-label="Go to POS"
    >
      <span class="pos-fab-icon"><i class="fa-solid fa-cash-register"></i></span>
      <span class="pos-fab-label">Go to POS</span>
    </router-link>

  </div>

  <!-- LOGOUT MODAL -->
  <transition name="fade">
    <div v-if="showLogoutModal" class="modal-backdrop" @click.self="showLogoutModal = false">
      <div class="modal">
        <div class="modal-icon-wrap">
          <i class="fa-solid fa-right-from-bracket" style="font-size:20px;color:var(--c-accent)"></i>
        </div>
        <h2 class="modal-title">Sign out?</h2>
        <p class="modal-desc">You'll need to sign in again to access the system.</p>
        <div class="modal-actions">
          <button class="btn-ghost" @click="showLogoutModal = false">Cancel</button>
          <button class="btn-danger" @click="confirmLogout">Sign Out</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/api/axios'

import NavLink from './NavLink.vue'
import SidebarDropdown from './SidebarDropdown.vue'
import logo from '@/assets/sheems_logo.png'
import profileIcon from '@/assets/profile-icon.png'

const router = useRouter()
const route  = useRoute()

const search          = ref('')
const sidebarOpen     = ref(true)
const inventoryOpen   = ref(false)
const salesOpen       = ref(false)
const profileOpen     = ref(false)
const showLogoutModal = ref(false)
const user            = ref({ name: 'User' })

/* ─────────────────────────────────────────────────────────
   GLOBAL "GO TO POS" BUTTON VISIBILITY
   Hidden only on the POS route itself — visible on every
   other page that renders through this Layout (Home,
   Reports, Inventory Logs, Products, Suppliers, etc.)
───────────────────────────────────────────────────────── */
const isPosPage = computed(() => route.path.startsWith('/point-of-sale'))

/* ─────────────────────────────────────────────────────────
   DARK MODE
   - Toggles data-theme="dark" on <html> so ALL pages inherit
   - Persisted to localStorage
   - Falls back to OS preference on first visit
───────────────────────────────────────────────────────── */
const DARK_KEY = 'sheem-dark-mode'
const darkMode = ref(false)

function applyTheme(isDark) {
  document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light')
}

function toggleDark() {
  darkMode.value = !darkMode.value
  applyTheme(darkMode.value)
  localStorage.setItem(DARK_KEY, String(darkMode.value))
}

/* ─── LOADING ─────────────────────────────────────────── */
const loading    = ref(false)
let requestCount = 0
let loadingTimer = null
const MIN_MS     = 300

function startLoading() {
  requestCount++
  if (loadingTimer) { clearTimeout(loadingTimer); loadingTimer = null }
  loading.value = true
}
function stopLoading() {
  requestCount = Math.max(0, requestCount - 1)
  if (requestCount > 0) return
  loadingTimer = setTimeout(() => { loading.value = false; loadingTimer = null }, MIN_MS)
}

let reqI = null, resI = null

onMounted(async () => {
  /* Init theme before anything renders */
  const stored = localStorage.getItem(DARK_KEY)
  darkMode.value = stored !== null
    ? stored === 'true'
    : window.matchMedia('(prefers-color-scheme: dark)').matches
  applyTheme(darkMode.value)

  reqI = api.interceptors.request.use(
    c => { startLoading(); return c },
    e => { stopLoading();  return Promise.reject(e) }
  )
  resI = api.interceptors.response.use(
    r => { stopLoading(); return r },
    e => { stopLoading(); return Promise.reject(e) }
  )

  try {
    const res = await api.get('/api/user')
    user.value = res.data
  } catch {
    router.push('/login')
  }
})

onBeforeUnmount(() => {
  if (reqI !== null) api.interceptors.request.eject(reqI)
  if (resI !== null) api.interceptors.response.eject(resI)
  if (loadingTimer) clearTimeout(loadingTimer)
})

const goToProducts = () => {
  if (!search.value.trim()) return
  router.push({ path: '/products', query: { search: search.value } })
}

watch(search, val => {
  if (!route.path.startsWith('/products')) return
  router.replace({ query: { ...route.query, search: val || undefined } })
})

const confirmLogout = async () => {
  try { await api.post('/api/logout') }
  catch (err) { console.error('Logout error:', err) }
  finally { showLogoutModal.value = false; profileOpen.value = false; router.push('/login') }
}
</script>

<style scoped>
/* ─── SHELL ───────────────────────────────────────────── */
/*
  FIX: width: 100vw was the root cause of horizontal scrolling
  appearing across the whole app at 100% zoom, even on large
  monitors. `100vw` is 100% of the viewport INCLUDING the
  vertical scrollbar's width — but the actual visible content
  area (window.innerWidth) is narrower than that by however
  wide the scrollbar gutter is. Since .app-shell is the
  outermost container for every page, that tiny excess width
  forced a horizontal scrollbar on any page tall enough to need
  a vertical one — which is most pages here.

  `width: 100%` instead fills the parent (<body>) exactly,
  with no scrollbar-gutter mismatch, while height/overflow
  behavior is unchanged.
*/
.app-shell {
  display: flex;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: var(--c-bg);
  font-family: 'Inter', system-ui, sans-serif;
  color: var(--c-text-primary);
  position: relative;
}

/* ═══════════════════════════════════════════════════════
   LOADING OVERLAY
════════════════════════════════════════════════════════ */
.loading-overlay {
  position: fixed;
  inset: 0;
  z-index: 9998;
  background: var(--c-overlay-blur);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 18px;
}
.loading-bar {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--c-accent-ring);
  overflow: hidden;
}
.loading-bar-fill {
  height: 100%;
  width: 42%;
  background: linear-gradient(90deg, var(--c-accent), var(--c-accent-h), var(--c-accent));
  border-radius: 0 3px 3px 0;
  animation: bar-slide 1.5s ease-in-out infinite;
}
@keyframes bar-slide {
  0%   { transform: translateX(-130%); }
  65%  { transform: translateX(280%); }
  100% { transform: translateX(280%); }
}
.loading-logo {
  width: 56px; height: 56px;
  object-fit: contain; border-radius: 14px;
  animation: logo-pulse 1.8s ease-in-out infinite;
}
@keyframes logo-pulse {
  0%, 100% { opacity: 1;    transform: scale(1); }
  50%       { opacity: 0.6; transform: scale(0.93); }
}
.loading-dots { display: flex; gap: 7px; align-items: center; }
.dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--c-accent);
  animation: dot-bounce 1.2s ease-in-out infinite;
  animation-delay: calc(var(--i) * 0.18s);
}
@keyframes dot-bounce {
  0%, 80%, 100% { transform: translateY(0);    opacity: 0.4; }
  40%           { transform: translateY(-8px); opacity: 1; }
}
.loading-text {
  font-size: 11px; font-weight: 600;
  color: var(--c-text-faint);
  letter-spacing: 0.14em; text-transform: uppercase;
}
.loader-fade-enter-active { transition: opacity .18s ease; }
.loader-fade-leave-active { transition: opacity .30s ease; }
.loader-fade-enter-from, .loader-fade-leave-to { opacity: 0; }

/* ─── SIDEBAR ─────────────────────────────────────────── */
.sidebar {
  width: var(--sw, 260px);
  min-width: var(--sw, 260px);
  height: 100vh;
  background: var(--c-sidebar-bg);
  border-right: 1px solid var(--c-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition:
    width 0.26s cubic-bezier(.4,0,.2,1),
    min-width 0.26s cubic-bezier(.4,0,.2,1),
    background-color 0.22s ease,
    border-color 0.22s ease;
  flex-shrink: 0;
  z-index: 50;
}
.sidebar::before {
  content: '';
  display: block;
  height: 3px;
  background: var(--c-sidebar-bar);
  flex-shrink: 0;
}

.sidebar-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 10px;
  min-height: 62px;
  flex-shrink: 0;
}

.burger {
  width: 34px; height: 34px; min-width: 34px;
  display: flex; flex-direction: column;
  align-items: flex-start; justify-content: center;
  gap: 5px;
  background: none; border: none; cursor: pointer;
  padding: 6px; border-radius: 8px;
  transition: background 0.18s ease;
  flex-shrink: 0;
}
.burger:hover { background: var(--c-accent-soft); }

.bline {
  display: block; height: 1.8px; width: 18px;
  background: var(--c-text-muted);
  border-radius: 2px;
  transition: width .22s ease, opacity .22s ease, background-color 0.22s ease;
}
.bline--mid        { width: 12px; }
.bline--mid-hidden { opacity: 0; width: 0; }

.brand-wrap {
  display: flex; align-items: center; gap: 9px;
  text-decoration: none; overflow: hidden; white-space: nowrap; flex: 1;
}
.brand-logo { width: 30px; height: 30px; object-fit: contain; border-radius: 7px; flex-shrink: 0; }
.brand-text { display: flex; flex-direction: column; line-height: 1; }
.brand-name { font-size: 13.5px; font-weight: 700; color: var(--c-text-primary); letter-spacing: .01em; }
.brand-sub  { font-size: 10px; color: var(--c-text-faint); margin-top: 2px; font-weight: 500; letter-spacing: .02em; text-transform: uppercase; }

.sidebar-divider { height: 1px; background: var(--c-border); margin: 0 14px; flex-shrink: 0; }

.sidebar-nav {
  flex: 1; padding: 10px 8px;
  overflow-y: auto; overflow-x: hidden;
  display: flex; flex-direction: column; gap: 1px;
  scrollbar-width: none;
}
.sidebar-nav::-webkit-scrollbar { display: none; }

.sidebar-footer { padding: 8px 8px 14px; flex-shrink: 0; }

.user-mini {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 10px; border-radius: 10px;
  overflow: hidden; white-space: nowrap; margin-top: 8px;
  background: var(--c-surface-raised);
  border: 1px solid var(--c-border);
  transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
  cursor: default;
}
.user-mini--center { justify-content: center; }
.user-mini:hover   { background: var(--c-accent-soft); border-color: var(--c-accent-border); box-shadow: 0 0 0 3px var(--c-accent-ring); }

.user-avatar-wrap  { position: relative; flex-shrink: 0; }
.user-avatar-sm    { width: 32px; height: 32px; border-radius: 8px; object-fit: cover; display: block; }
.user-status-dot   { position: absolute; bottom: -1px; right: -1px; width: 9px; height: 9px; background: var(--c-online); border-radius: 50%; border: 2px solid var(--c-sidebar-bg); }

.user-mini-info { overflow: hidden; }
.user-mini-name { font-size: 12.5px; font-weight: 600; color: var(--c-text-primary); line-height: 1.2; }
.user-mini-role { font-size: 10px; color: var(--c-accent); margin-top: 2px; font-weight: 600; letter-spacing: .03em; text-transform: uppercase; }

/* ─── MAIN COLUMN ─────────────────────────────────────── */
.main-column { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; min-width: 0; }

/* ─── TOP BAR ─────────────────────────────────────────── */
.topbar {
  height: 65px; min-height: 65px;
  background: var(--c-surface);
  border-bottom: 1px solid var(--c-border);
  display: flex; align-items: center; gap: 16px;
  padding: 0 24px;
  z-index: 40; flex-shrink: 0;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}

.topbar-greeting { flex-shrink: 0; }
.greeting-name   { font-size: 14px; font-weight: 700; color: var(--c-text-primary); line-height: 1.2; }
.greeting-name span { color: var(--c-accent); }
.greeting-sub    { font-size: 11px; color: var(--c-text-faint); margin-top: 1px; }

.search-wrap { flex: 1; max-width: 400px; margin: 0 auto; position: relative; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--c-text-placeholder); font-size: 12px; pointer-events: none; }
.search-input {
  width: 100%; height: 38px;
  border: 1.5px solid var(--c-border-input);
  border-radius: 10px;
  padding: 0 44px 0 36px;
  font-size: 13px;
  color: var(--c-text-primary);
  background: var(--c-surface-sunken);
  outline: none;
  transition: border-color .18s ease, box-shadow .18s ease, background-color 0.22s ease, color 0.22s ease;
  font-family: inherit;
}
.search-input:focus { border-color: var(--c-accent); box-shadow: 0 0 0 3px var(--c-accent-ring); background: var(--c-surface); }
.search-input::placeholder { color: var(--c-text-placeholder); }
.search-kbd { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 10px; color: var(--c-text-faint); background: var(--c-border); border-radius: 4px; padding: 2px 6px; pointer-events: none; font-family: inherit; }

.topbar-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

.action-btn {
  display: flex; align-items: center; gap: 6px;
  height: 36px; padding: 0 14px;
  border-radius: 9px; border: none;
  font-size: 13px; font-weight: 600;
  cursor: pointer; font-family: inherit; text-decoration: none;
  transition: background 0.18s ease, transform 0.15s ease, box-shadow 0.18s ease;
}
.action-btn--primary {
  background: linear-gradient(135deg, var(--c-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff;
  box-shadow: var(--c-shadow-accent);
}
.action-btn--primary:hover { transform: translateY(-1px); box-shadow: var(--c-shadow-accent-h); filter: brightness(1.08); }

.icon-btn {
  width: 36px; height: 36px;
  border: 1.5px solid var(--c-border-strong);
  border-radius: 9px;
  background: var(--c-surface);
  color: var(--c-text-muted);
  font-size: 14px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
  position: relative; overflow: hidden;
}
.icon-btn:hover { background: var(--c-accent-soft); border-color: var(--c-accent-border); color: var(--c-accent); box-shadow: 0 0 0 3px var(--c-accent-ring); }

/* ─── THEME PILL TOGGLE ───────────────────────────────── */
.theme-pill {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  outline: none;
}
.theme-pill:focus-visible .pill-track {
  box-shadow: 0 0 0 3px var(--c-accent-ring), 0 0 0 1px var(--c-accent);
}

.pill-track {
  position: relative;
  display: flex;
  align-items: center;
  width: 64px;
  height: 30px;
  border-radius: 999px;
  background: var(--c-surface-sunken);
  border: 1.5px solid var(--c-border-strong);
  padding: 0 4px;
  transition: background-color 0.26s ease, border-color 0.26s ease, box-shadow 0.18s ease;
  gap: 0;
  justify-content: space-between;
}
.pill-track--dark {
  background: #1E2130;
  border-color: var(--c-accent-border);
  box-shadow: 0 0 10px rgba(251,146,60,0.15);
}
.pill-track:hover {
  border-color: var(--c-accent-border);
  box-shadow: 0 0 0 3px var(--c-accent-ring);
}

/* Sun icon on left */
.pill-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  z-index: 1;
  transition: color 0.22s ease, opacity 0.22s ease;
  flex-shrink: 0;
}
.pill-icon--sun  { color: var(--c-text-faint); }
.pill-icon--moon { color: var(--c-text-faint); }
.pill-icon--active.pill-icon--sun  { color: #F59E0B; }
.pill-icon--active.pill-icon--moon { color: #818CF8; }

/* Sliding thumb */
.pill-thumb {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #FFFFFF;
  box-shadow: 0 1px 4px rgba(0,0,0,0.20), 0 2px 8px rgba(0,0,0,0.12);
  transition: transform 0.26s cubic-bezier(.4,0,.2,1), background-color 0.26s ease;
  z-index: 2;
}
.pill-thumb--right {
  transform: translateX(34px);
  background: var(--c-accent);
  box-shadow: 0 1px 4px rgba(0,0,0,0.30), 0 0 8px rgba(251,146,60,0.40);
}

.notif-dot { position: absolute; top: 6px; right: 6px; width: 7px; height: 7px; background: var(--c-accent); border-radius: 50%; border: 1.5px solid var(--c-surface); }

/* ─── PROFILE ─────────────────────────────────────────── */
.profile-wrap    { position: relative; }
.profile-trigger {
  display: flex; align-items: center; gap: 8px;
  padding: 4px 10px 4px 4px;
  border: 1.5px solid var(--c-border-strong);
  border-radius: 10px;
  background: var(--c-surface-raised);
  cursor: pointer;
  transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}
.profile-trigger:hover { background: var(--c-accent-soft); border-color: var(--c-accent-border); box-shadow: 0 0 0 3px var(--c-accent-ring); }
.profile-avatar  { width: 30px; height: 30px; border-radius: 7px; object-fit: cover; }
.profile-info    { text-align: left; line-height: 1.2; }
.profile-name    { font-size: 12.5px; font-weight: 600; color: var(--c-text-primary); white-space: nowrap; }
.profile-role    { font-size: 10px; color: var(--c-accent); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
.profile-caret   { font-size: 10px; color: var(--c-text-faint); transition: transform .2s ease; }
.profile-caret--up { transform: rotate(180deg); }

.profile-menu {
  position: absolute; right: 0; top: calc(100% + 10px);
  width: 230px;
  background: var(--c-surface-overlay);
  border: 1px solid var(--c-border);
  border-radius: 14px;
  box-shadow: var(--c-shadow-lg);
  z-index: 200; padding: 6px;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.profile-menu-header { display: flex; align-items: center; gap: 10px; padding: 10px 10px 12px; }
.pmenu-avatar-wrap   { position: relative; flex-shrink: 0; }
.profile-menu-avatar { width: 38px; height: 38px; border-radius: 9px; object-fit: cover; display: block; }
.pmenu-status-dot    { position: absolute; bottom: -1px; right: -1px; width: 9px; height: 9px; background: var(--c-online); border-radius: 50%; border: 2px solid var(--c-surface-overlay); }
.profile-menu-name   { font-size: 13px; font-weight: 700; color: var(--c-text-primary); }
.profile-menu-email  { font-size: 11px; color: var(--c-text-faint); margin-top: 1px; }

.pmenu-divider { height: 1px; background: var(--c-border); margin: 2px 0; }

.pmenu-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px; font-size: 13px;
  color: var(--c-text-secondary);
  border-radius: 8px; text-decoration: none;
  background: none; border: none; width: 100%;
  cursor: pointer; font-family: inherit; font-weight: 500;
  transition: background-color 0.15s ease, color 0.15s ease;
}
.pmenu-item:hover { background: var(--c-surface-raised); color: var(--c-text-primary); }

.pmenu-icon-wrap {
  width: 28px; height: 28px; border-radius: 7px;
  background: var(--c-surface-sunken);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; color: var(--c-text-muted);
  flex-shrink: 0;
  transition: background-color 0.15s ease, color 0.15s ease;
}
.pmenu-item:hover .pmenu-icon-wrap { background: var(--c-border); color: var(--c-text-secondary); }

.pmenu-icon-wrap--danger { background: var(--c-danger-soft); color: var(--c-danger); }
.pmenu-item--danger      { color: var(--c-danger); }
.pmenu-item--danger:hover { background: var(--c-danger-soft); color: var(--c-danger); }
.pmenu-item--danger:hover .pmenu-icon-wrap--danger { background: var(--c-danger-soft-h); }

/* ─── PAGE CONTENT ────────────────────────────────────── */
.page-content {
  flex: 1; overflow-y: auto; overflow-x: hidden;
  padding: 28px;
  background: var(--c-bg);
  transition: background-color 0.22s ease;
}
.content-inner { max-width: 1600px; width: 100%; margin: 0 auto; }

/* ─── GLOBAL FLOATING "GO TO POS" BUTTON ──────────────── */
.pos-fab {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 60;
  display: flex;
  align-items: center;
  gap: 9px;
  background: var(--c-accent);
  color: #fff;
  text-decoration: none;
  padding: 11px 18px 11px 14px;
  border-radius: 999px;
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: -.01em;
  /* Slightly translucent at rest so it overlays content without fully
     blocking it, with a backdrop blur so whatever's underneath stays
     legible through the button rather than being hidden by it. */
  opacity: 0.88;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  box-shadow: var(--c-shadow-accent), 0 2px 6px rgba(0,0,0,0.12);
  transition: opacity .18s ease, transform .18s ease, box-shadow .18s ease, background-color .18s ease, filter .18s ease;
}
.pos-fab:hover {
  opacity: 1;
  transform: translateY(-2px);
  box-shadow: var(--c-shadow-accent-h), 0 3px 8px rgba(0,0,0,0.16);
  filter: brightness(1.05);
  color: #fff;
}
.pos-fab:active { transform: translateY(0); }

.pos-fab-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: rgba(255,255,255,0.18);
  font-size: 12px;
  flex-shrink: 0;
}
.pos-fab-label { white-space: nowrap; }

/* On small screens, shrink to an icon-only circular button so it
   doesn't crowd a narrow viewport */
@media (max-width: 560px) {
  .pos-fab {
    right: 16px;
    bottom: 16px;
    padding: 13px;
    border-radius: 50%;
  }
  .pos-fab-label { display: none; }
  .pos-fab-icon { width: 18px; height: 18px; background: transparent; }
}

/* ─── LOGOUT MODAL ────────────────────────────────────── */
.modal-backdrop {
  position: fixed; inset: 0;
  background: var(--c-overlay);
  backdrop-filter: blur(8px);
  z-index: 9999;
  display: flex; align-items: center; justify-content: center;
}
.modal {
  background: var(--c-surface-overlay);
  border-radius: 20px;
  padding: 36px 32px;
  width: 360px;
  box-shadow: var(--c-shadow-xl);
  text-align: center;
  border: 1px solid var(--c-border);
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.modal-icon-wrap {
  width: 52px; height: 52px;
  background: var(--c-accent-soft);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 16px;
  border: 1px solid var(--c-accent-border);
}
.modal-title { font-size: 17px; font-weight: 700; color: var(--c-text-primary); margin-bottom: 8px; }
.modal-desc  { font-size: 13px; color: var(--c-text-muted); margin-bottom: 26px; line-height: 1.7; }
.modal-actions { display: flex; gap: 10px; justify-content: center; }

.btn-ghost {
  padding: 9px 22px;
  border: 1.5px solid var(--c-border-strong);
  border-radius: 9px;
  background: var(--c-surface);
  font-size: 13.5px; font-weight: 600;
  color: var(--c-text-secondary);
  cursor: pointer; font-family: inherit;
  transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}
.btn-ghost:hover { background: var(--c-surface-raised); color: var(--c-text-primary); }

.btn-danger {
  padding: 9px 22px; border: none; border-radius: 9px;
  background: linear-gradient(135deg, var(--c-danger) 0%, var(--c-danger-deep) 100%);
  font-size: 13.5px; font-weight: 600; color: #fff;
  cursor: pointer; font-family: inherit;
  box-shadow: var(--c-shadow-danger);
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.btn-danger:hover { opacity: .9; transform: translateY(-1px); }

/* ─── TRANSITIONS ─────────────────────────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity .22s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }
.dropdown-enter-active { transition: opacity .18s ease, transform .18s ease; }
.dropdown-leave-active { transition: opacity .14s ease, transform .14s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px) scale(.97); }
</style>