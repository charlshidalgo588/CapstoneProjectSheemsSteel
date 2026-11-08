<template>
  <div
    class="app-shell"
    v-bind="shellAttrs"
    :style="{ '--sw': sidebarOpen ? '260px' : '68px' }"
    @mouseover="onTipOver"
    @focusin="onTipOver"
    @mouseleave="hideTip"
    @focusout="hideTip"
    @click="hideTip"
  >
    <!-- Keyboard users: jump straight past the sidebar + topbar. -->
    <a href="#main-content" class="skip-link">Skip to content</a>

    <!-- ═══════════════════════════════════════════════════
         GLOBAL LOADING OVERLAY — full-screen, blocking. Reserved for
         the initial auth check on first load only (there's genuinely
         nothing to show yet, so blocking is fine and expected).
    ════════════════════════════════════════════════════ -->
    <transition name="loader-fade">
      <div v-if="initializing" class="loading-overlay" role="status" aria-label="Loading">
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

    <!-- SLIM TOP PROGRESS BAR — non-blocking busy indicator for every
         other API call (navigating pages, submitting forms, etc). Sits
         above everything but never intercepts clicks or dims the UI. -->
    <transition name="topbar-fade">
      <div v-if="apiBusy" class="top-progress-bar" aria-hidden="true">
        <div class="top-progress-fill"></div>
      </div>
    </transition>

    <!-- MOBILE SIDEBAR BACKDROP -->
    <transition name="fade">
      <div
        v-if="isMobile && sidebarOpen"
        class="mobile-backdrop"
        aria-hidden="true"
        @click="sidebarOpen = false"
      />
    </transition>

    <!-- SIDEBAR -->
    <aside
      class="sidebar"
      :class="{ 'sidebar--mobile': isMobile, 'sidebar--open': sidebarOpen }"
      aria-label="Main navigation"
    >
      <div class="sidebar-header">
        <button
          class="burger"
          :aria-expanded="sidebarOpen"
          aria-label="Toggle sidebar"
          :data-tip="sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'"
          data-tip-desc="Show or hide menu labels"
          data-tip-icon="fa-solid fa-table-columns"
          @click="sidebarOpen = !sidebarOpen"
        >
          <span class="bline" />
          <span class="bline bline--mid" :class="{ 'bline--mid-hidden': !sidebarOpen }" />
          <span class="bline" />
        </button>
        <router-link v-show="sidebarOpen" to="/home" class="brand-wrap">
          <img :src="logo" class="brand-logo" alt="" />
          <div class="brand-text">
            <span class="brand-name">Sheem Steel</span>
            <span class="brand-sub">POS &amp; Inventory</span>
          </div>
        </router-link>
      </div>

      <nav class="sidebar-nav">
        <NavLink to="/home" icon="fa-solid fa-house" label="Home" :collapsed="!sidebarOpen" />

        <div class="nav-divider" role="separator" />

        <SidebarDropdown label="Inventory" icon="fa-solid fa-boxes-stacked" :open="inventoryOpen" :collapsed="!sidebarOpen" @toggle="inventoryOpen = !inventoryOpen">
          <NavLink to="/products"        menu-item label="Product List" icon="fa-solid fa-list"        :collapsed="false" />
          <NavLink to="/products/create" menu-item label="Add Item"     icon="fa-solid fa-plus"        :collapsed="false" />
          <NavLink to="/categories"      menu-item label="Categories"   icon="fa-solid fa-layer-group" :collapsed="false" />
        </SidebarDropdown>
        <SidebarDropdown label="Sales" icon="fa-solid fa-cart-shopping" :open="salesOpen" :collapsed="!sidebarOpen" @toggle="salesOpen = !salesOpen">
          <NavLink to="/point-of-sale"     menu-item label="POS"         icon="fa-solid fa-cash-register" :collapsed="false" />
          <NavLink to="/sales-transaction" menu-item label="Transaction" icon="fa-solid fa-receipt"       :collapsed="false" />
          <NavLink to="/customers"         menu-item label="Customers"   icon="fa-solid fa-users"         :collapsed="false" />
        </SidebarDropdown>

        <div class="nav-divider" role="separator" />

        <NavLink to="/suppliers"      label="Suppliers"      icon="fa-solid fa-truck-field" :collapsed="!sidebarOpen" />
        <NavLink to="/reports"        label="Reports"        icon="fa-solid fa-chart-line"  :collapsed="!sidebarOpen" />
        <NavLink to="/inventory-logs" label="Inventory Logs" icon="fa-solid fa-folder-open" :collapsed="!sidebarOpen" />
      </nav>

      <!-- SIDEBAR FOOTER — user card doubles as the account menu trigger -->
      <div class="sidebar-footer">
        <button
          ref="userMiniRef"
          class="user-mini"
          :class="{ 'user-mini--center': !sidebarOpen, 'user-mini--open': userMenuOpen }"
          aria-haspopup="menu"
          :aria-expanded="userMenuOpen"
          :data-tip="user?.name || 'User'"
          data-tip-desc="Account and sign out"
          data-tip-icon="fa-solid fa-user"
          data-tip-collapsed
          @click="toggleUserMenu"
        >
          <div class="user-avatar-wrap">
            <div class="user-avatar-sm user-avatar-vector" aria-hidden="true">
              <i class="fa-solid fa-user"></i>
            </div>
            <span class="user-status-dot" :class="{ 'user-status-dot--offline': !isOnline }"></span>
          </div>
          <div v-show="sidebarOpen" class="user-mini-info">
            <p class="user-mini-name">{{ user?.name || 'User' }}</p>
            <p class="user-mini-role">Administrator</p>
          </div>
          <i v-show="sidebarOpen" class="fa-solid fa-chevron-up user-mini-caret" :class="{ 'user-mini-caret--down': userMenuOpen }" aria-hidden="true"></i>
        </button>
      </div>
    </aside>

    <!-- THEMED TOOLTIP — replaces the browser's plain native `title`
         tooltip everywhere in the shell (sidebar icons, topbar buttons):
         icon chip, label, short description, "You're here" marker.
         Driven by event delegation on .app-shell (see onTipOver), so any
         element can opt in with data-tip / data-tip-desc / data-tip-icon. -->
    <transition name="tip">
      <div
        v-if="tip.show"
        class="nav-tip"
        :class="`nav-tip--${tip.place}`"
        role="tooltip"
        :style="tip.style"
      >
        <span class="nav-tip-icon" aria-hidden="true"><i :class="tip.icon"></i></span>
        <span class="nav-tip-text">
          <span class="nav-tip-label">{{ tip.label }}</span>
          <span v-if="tip.desc" class="nav-tip-desc">{{ tip.desc }}</span>
        </span>
        <span v-if="tip.current" class="nav-tip-current">You're here</span>
      </div>
    </transition>

    <!-- TELEPORTED ACCOUNT POPUP — escapes the sidebar's overflow:hidden -->
    <Teleport to="body">
      <transition name="dropdown">
        <div v-if="userMenuOpen" class="user-popup-menu" ref="userPopupRef" role="menu" :style="userMenuStyle">
          <div class="user-popup-header">
            <div class="pmenu-avatar-wrap">
              <div class="profile-menu-avatar user-avatar-vector" aria-hidden="true">
                <i class="fa-solid fa-user"></i>
              </div>
              <span class="pmenu-status-dot"></span>
            </div>
            <div class="user-popup-id">
              <p class="profile-menu-name">{{ user?.name || 'User' }}</p>
              <p class="profile-menu-email">admin@sheemsteel.com</p>
            </div>
          </div>
          <div class="pmenu-divider" />
          <router-link to="/settings" class="pmenu-item" role="menuitem" @click="userMenuOpen = false">
            <span class="pmenu-icon-wrap"><i class="fa-solid fa-gear"></i></span>
            Settings
          </router-link>
          <div class="pmenu-divider" />
          <button class="pmenu-item pmenu-item--danger" role="menuitem" @click="showLogoutModal = true; userMenuOpen = false">
            <span class="pmenu-icon-wrap pmenu-icon-wrap--danger"><i class="fa-solid fa-right-from-bracket"></i></span>
            Sign out
          </button>
        </div>
      </transition>
    </Teleport>

    <!-- MAIN COLUMN -->
    <div class="main-column">

      <!-- TOP BAR — gains a soft shadow once the page content scrolls
           beneath it, so it reads as a fixed layer instead of a flat strip. -->
      <header class="topbar" :class="{ 'topbar--scrolled': pageScrolled }">
        <button v-if="isMobile" class="icon-btn mobile-menu-btn" aria-label="Open menu" @click="sidebarOpen = true">
          <i class="fa-solid fa-bars"></i>
        </button>

        <!-- GREETING — the logo only shows here while the sidebar is
             collapsed (it's hidden inside the sidebar in that state). -->
        <div class="topbar-greeting">
          <router-link
            v-if="!sidebarOpen"
            to="/home"
            class="greeting-logo-link"
            aria-label="Sheem Steel home"
          >
            <img :src="logo" class="greeting-logo" alt="" />
          </router-link>
          <div class="greeting-text">
            <p class="greeting-name">{{ greetingPhrase }}, <span>{{ user?.name || 'User' }}</span></p>
            <p class="greeting-sub">{{ todayLabel }}</p>
          </div>
        </div>

        <div class="search-wrap">
          <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
          <input
            ref="searchInputRef"
            v-model="search"
            type="search"
            aria-label="Search products"
            @keyup.enter="goToProducts"
            @keydown.esc="$event.target.blur()"
            @focus="searchFocused = true"
            @blur="searchFocused = false"
            class="search-input"
            placeholder="Search products"
          />
          <button v-if="search" class="search-clear" aria-label="Clear search" @click="clearSearch">
            <i class="fa-solid fa-xmark"></i>
          </button>
          <kbd v-else-if="!searchFocused" class="search-kbd" aria-hidden="true">/</kbd>
        </div>

        <div class="topbar-actions">
          <!-- Only appears when the connection drops — a POS that silently
               fails to save is worse than one that tells you up front. -->
          <transition name="fade">
            <span v-if="!isOnline" class="status-chip status-chip--offline" role="status">
              <i class="fa-solid fa-wifi" aria-hidden="true"></i>
              Offline
            </span>
          </transition>

          <span class="topbar-clock" aria-label="Current time">{{ clockLabel }}</span>

          <span class="topbar-sep" aria-hidden="true"></span>

          <router-link to="/products/create" class="action-link" tabindex="-1">
            <button
              class="action-btn action-btn--primary"
              aria-label="Add item"
              data-tip="Add item"
              data-tip-desc="Create a new product"
              data-tip-icon="fa-solid fa-plus"
            >
              <i class="fa-solid fa-plus"></i>
              <span>Add item</span>
            </button>
          </router-link>

          <!-- NOTIFICATIONS -->
          <div class="notif-wrap" ref="notifWrapRef">
            <button
              class="icon-btn"
              aria-label="Notifications"
              data-tip="Notifications"
              :data-tip-desc="unreadCount > 0 ? unreadCount + ' unread' : 'No new alerts'"
              data-tip-icon="fa-solid fa-bell"
              aria-haspopup="true"
              :aria-expanded="notifOpen"
              :class="{ 'icon-btn--active': notifOpen }"
              @click="notifOpen = !notifOpen"
            >
              <i class="fa-solid fa-bell"></i>
              <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
            </button>

            <transition name="dropdown">
              <div v-if="notifOpen" class="notif-menu">
                <div class="notif-menu-header">
                  <div class="notif-menu-heading">
                    <p class="notif-menu-title">Notifications</p>
                    <span v-if="unreadCount > 0" class="notif-count">{{ unreadCount }} new</span>
                  </div>
                  <button v-if="unreadCount > 0" class="notif-mark-all" @click="notificationsStore.markAllRead()">Mark all as read</button>
                </div>
                <div class="pmenu-divider" />

                <div v-if="notifications.length === 0" class="notif-empty">
                  <i class="fa-regular fa-bell-slash"></i>
                  <p>You're all caught up</p>
                  <span>New sales and stock alerts will show up here.</span>
                </div>

                <ul v-else class="notif-list">
                  <li
                    v-for="n in notifications"
                    :key="n.id"
                    class="notif-item"
                    :class="{ 'notif-item--unread': !n.read_at }"
                    tabindex="0"
                    @click="goToNotification(n)"
                    @keydown.enter="goToNotification(n)"
                  >
                    <span class="notif-item-icon" :class="`notif-item-icon--${n.type}`">
                      <i :class="notifIcon(n.type)"></i>
                    </span>
                    <div class="notif-item-body">
                      <p class="notif-item-title">{{ n.title }}</p>
                      <p class="notif-item-desc">{{ n.description }}</p>
                      <p class="notif-item-time">{{ timeAgo(n.created_at) }}</p>
                    </div>
                    <span v-if="!n.read_at" class="notif-item-dot" aria-label="Unread"></span>
                  </li>
                </ul>
              </div>
            </transition>
          </div>
        </div>
      </header>

      <!-- PAGE CONTENT -->
      <main id="main-content" class="page-content" tabindex="-1" @scroll.passive="onPageScroll">
        <div class="content-inner">
          <slot />
        </div>
      </main>
    </div>

    <!-- ═══════════════════════════════════════════════════
         GLOBAL FLOATING "GO TO POS" BUTTON
         Shows the full pill briefly, then auto-docks to the right edge
         as an icon-only tab. Hover / focus pops it back out.
    ════════════════════════════════════════════════════ -->
    <router-link
      v-if="!isPosPage"
      to="/point-of-sale"
      class="pos-fab"
      :class="{ 'pos-fab--docked': posFabDocked }"
      aria-label="Go to POS"
      @mouseenter="expandPosFab"
      @mouseleave="schedulePosFabDock"
      @focus="expandPosFab"
      @blur="schedulePosFabDock"
    >
      <span class="pos-fab-icon"><i class="fa-solid fa-cash-register"></i></span>
      <span class="pos-fab-label">Go to POS</span>
    </router-link>

  </div>

  <!-- LOGOUT MODAL -->
  <transition name="fade">
    <div v-if="showLogoutModal" class="modal-backdrop" @click.self="!loggingOut && (showLogoutModal = false)">
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="logout-title">
        <div class="modal-icon-wrap">
          <i class="fa-solid fa-right-from-bracket" style="font-size:18px;color:var(--c-accent)"></i>
        </div>
        <h2 id="logout-title" class="modal-title">Sign out of Sheem Steel?</h2>
        <p class="modal-desc">You'll need to sign in again to use the system.</p>
        <div class="modal-actions">
          <button class="btn-ghost" :disabled="loggingOut" @click="showLogoutModal = false">Cancel</button>
          <button class="btn-danger" :disabled="loggingOut" @click="confirmLogout">
            <i v-if="loggingOut" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
            <span>{{ loggingOut ? 'Signing out…' : 'Sign out' }}</span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick, useAttrs } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import api from '@/api/axios'

import NavLink from './NavLink.vue'
import SidebarDropdown from './SidebarDropdown.vue'
import logo from '@/assets/sheems_logo.png'
import { useDarkMode } from '@/composables/useDarkMode'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

defineOptions({ inheritAttrs: false })

// Attributes passed down by the parent are forwarded to the shell root —
// EXCEPT `title`. A page-level title (e.g. <Layout title="Home">) would
// otherwise turn into a plain native browser tooltip over every part of the
// shell that doesn't have its own.
const attrs = useAttrs()
const shellAttrs = computed(() => {
  // eslint-disable-next-line no-unused-vars
  const { title, ...rest } = attrs
  return rest
})

const router = useRouter()
const route  = useRoute()

// Single source of truth for the current user — shared with the router
// guard (and anywhere else that uses the store), instead of Layout doing
// its own independent /api/user fetch. See auth.ts: fetchUser()
// de-dupes concurrent calls, and checkAuth() skips the network entirely
// once the store has already resolved once this session.
const authStore = useAuthStore()
const { user, authenticated } = storeToRefs(authStore)

// Notification state lives in a Pinia store (a singleton for the whole
// app session) instead of local refs here — Layout.vue remounts on every
// navigation, so local refs couldn't survive a route change.
const notificationsStore = useNotificationsStore()
const { notifications, unreadCount } = storeToRefs(notificationsStore)

const search          = ref('')
const inventoryOpen   = ref(false)
const salesOpen       = ref(false)
const showLogoutModal = ref(false)
// Drives the spinner/disabled state on the logout modal's buttons.
const loggingOut      = ref(false)

/* ─────────────────────────────────────────────────────────
   LIVE CLOCK — backs the greeting phrase, date and time.
   `now` ticks every 30s so the topbar stays accurate even if the page
   is left open across a day/hour boundary.
───────────────────────────────────────────────────────── */
const now = ref(new Date())
let clockTimer = null

const todayLabel = computed(() =>
  now.value.toLocaleDateString('en-US', {
    weekday: 'long', month: 'long', day: 'numeric', year: 'numeric',
  })
)
const clockLabel = computed(() =>
  now.value.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
)

const dayPeriod = computed(() => {
  const h = now.value.getHours()
  if (h < 12) return 'morning'
  if (h < 17) return 'afternoon'
  if (h < 21) return 'evening'
  return 'night'
})
const greetingPhrase = computed(() => ({
  morning:   'Good morning',
  afternoon: 'Good afternoon',
  evening:   'Good evening',
  night:     'Good evening',
}[dayPeriod.value]))

/* ─────────────────────────────────────────────────────────
   CONNECTION STATUS — a POS needs to say so when it's offline.
   Drives the red "Offline" chip in the topbar and the status dot on
   the sidebar's user card (green → grey).
───────────────────────────────────────────────────────── */
const isOnline = ref(navigator.onLine)
function onOnline()  { isOnline.value = true;  notificationsStore.fetchNotifications() }
function onOffline() { isOnline.value = false }

/* ─────────────────────────────────────────────────────────
   TOPBAR SCROLL SHADOW — flips on once the page content has scrolled
   past the very top, so the topbar separates from the content.
───────────────────────────────────────────────────────── */
const pageScrolled = ref(false)
function onPageScroll(e) {
  pageScrolled.value = e.target.scrollTop > 4
}

/* ─────────────────────────────────────────────────────────
   RESPONSIVE BREAKPOINT — drives the sidebar's mode.
   >768px:  sidebar lives in normal flow; `sidebarOpen` toggles between
            the 260px expanded / 68px icon-only widths.
            DEFAULT: collapsed (icon-only) — the user expands it on demand.
   ≤768px:  sidebar becomes a fixed off-canvas drawer; `sidebarOpen`
            means "drawer open/closed", and a backdrop appears behind it.
            DEFAULT: closed.
───────────────────────────────────────────────────────── */
const MOBILE_BREAKPOINT = 768
const isMobile    = ref(window.innerWidth <= MOBILE_BREAKPOINT)
// Minimized is the main state on every screen size.
const sidebarOpen = ref(false)

function onWindowResize() {
  const mobile = window.innerWidth <= MOBILE_BREAKPOINT
  if (mobile === isMobile.value) return
  isMobile.value = mobile
  // Crossing the breakpoint changes what sidebarOpen *means*, so reset
  // to the default for the mode we just entered: minimized / closed.
  sidebarOpen.value = false
}

// On mobile, close the drawer automatically after navigating.
watch(() => route.path, () => {
  if (isMobile.value) sidebarOpen.value = false
})

/* ─────────────────────────────────────────────────────────
   GLOBAL "GO TO POS" BUTTON VISIBILITY
───────────────────────────────────────────────────────── */
const isPosPage = computed(() => route.path.startsWith('/point-of-sale'))

/* ─────────────────────────────────────────────────────────
   "GO TO POS" BUTTON — AUTO-DOCK
   Starts expanded (icon + label) so it's discoverable, then after
   POS_FAB_DOCK_DELAY of no interaction it slides against the right edge
   and collapses to just the icon.
   - Hover / keyboard focus  → expands immediately and cancels the timer.
   - Mouse leave / blur      → schedules the dock again.
   - Leaving the POS page    → pops back out briefly, then re-docks.
───────────────────────────────────────────────────────── */
const POS_FAB_DOCK_DELAY = 3000 // ms before the button tucks itself away
const posFabDocked = ref(false)
let posFabTimer = null

function clearPosFabTimer() {
  if (posFabTimer) { clearTimeout(posFabTimer); posFabTimer = null }
}
function schedulePosFabDock() {
  clearPosFabTimer()
  posFabTimer = setTimeout(() => {
    posFabDocked.value = true
    posFabTimer = null
  }, POS_FAB_DOCK_DELAY)
}
function expandPosFab() {
  clearPosFabTimer()
  posFabDocked.value = false
}

watch(isPosPage, (onPos) => {
  if (onPos) { clearPosFabTimer(); return }
  expandPosFab()
  schedulePosFabDock()
})

/* ─────────────────────────────────────────────────────────
   DARK MODE — the toggle UI lives only on the Settings page.
   Layout just applies the persisted/preferred theme on mount.
───────────────────────────────────────────────────────── */
const { initDarkMode } = useDarkMode()

/* ─── LOADING ─────────────────────────────────────────────
   - `initializing`: true only until the very first /api/user check
     resolves. Drives the full-screen blocking overlay.
   - `apiBusy`: drives the slim, non-blocking top progress bar for every
     request AFTER that point.

   SHOW_DELAY / HIDE_GRACE stop quick requests from flickering the bar.
─────────────────────────────────────────────────────────── */
const initializing = ref(true)
const apiBusy       = ref(false)
let requestCount     = 0
let showTimer        = null
let hideTimer        = null

const SHOW_DELAY = 150
const HIDE_GRACE = 120

function startLoading() {
  requestCount++
  if (hideTimer) { clearTimeout(hideTimer); hideTimer = null }
  if (apiBusy.value || showTimer) return
  showTimer = setTimeout(() => {
    showTimer = null
    if (requestCount > 0) apiBusy.value = true
  }, SHOW_DELAY)
}
function stopLoading() {
  requestCount = Math.max(0, requestCount - 1)
  if (requestCount > 0) return

  if (showTimer) { clearTimeout(showTimer); showTimer = null }
  if (!apiBusy.value) return

  if (hideTimer) clearTimeout(hideTimer)
  hideTimer = setTimeout(() => { apiBusy.value = false; hideTimer = null }, HIDE_GRACE)
}

let reqI = null, resI = null

/* ─── ACCOUNT POPUP (sidebar footer) ──────────────────── */
const userMenuOpen = ref(false)
const userMiniRef  = ref(null)
const userPopupRef = ref(null)
const userMenuCoords = ref({ left: 0, bottom: 0 })

const userMenuStyle = computed(() => ({
  left:   userMenuCoords.value.left + 'px',
  bottom: userMenuCoords.value.bottom + 'px',
}))

function measureUserMenu() {
  const el = userMiniRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  // Clamp so the 240px popup can't run off the right edge on narrow screens.
  const popupWidth = 240
  const maxLeft = window.innerWidth - popupWidth - 10
  userMenuCoords.value = {
    left:   Math.min(rect.left, Math.max(10, maxLeft)),
    bottom: window.innerHeight - rect.top + 10, // 10px gap above the trigger
  }
}

async function toggleUserMenu() {
  if (!userMenuOpen.value) { await nextTick(); measureUserMenu() }
  userMenuOpen.value = !userMenuOpen.value
}

function onScrollOrResizeUserMenu() {
  if (userMenuOpen.value) measureUserMenu()
}
window.addEventListener('scroll', onScrollOrResizeUserMenu, true)
window.addEventListener('resize', onScrollOrResizeUserMenu)

/* ─── SEARCH — "/" TO FOCUS ────────────────────────────────
   Pressing "/" anywhere outside a text field jumps focus straight into
   search (GitHub/Slack convention). Escape closes whatever is open:
   notifications, account menu, or the sign-out dialog.
──────────────────────────────────────────────────────────── */
const searchInputRef = ref(null)
const searchFocused  = ref(false)

function isTypingTarget(el) {
  if (!el) return false
  const tag = el.tagName
  return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable
}

function onGlobalKeydown(e) {
  if (e.key === 'Escape') {
    if (showLogoutModal.value && !loggingOut.value) { showLogoutModal.value = false; return }
    if (userMenuOpen.value) { userMenuOpen.value = false; userMiniRef.value?.focus(); return }
    if (notifOpen.value)    { notifOpen.value = false; return }
    return
  }
  if (e.key !== '/') return
  if (e.metaKey || e.ctrlKey || e.altKey) return
  if (isTypingTarget(document.activeElement)) return
  e.preventDefault()
  searchInputRef.value?.focus()
}

/* ─── THEMED TOOLTIPS ─────────────────────────────────────
   Event delegation on .app-shell: whichever target the pointer (or
   keyboard focus) lands on, we read its label, swap out the native
   `title` (which would show the browser's plain black tooltip) and show
   our own themed card beside it.

   Who gets one:
   - Any element with a `data-tip` attribute (topbar buttons, burger,
     user card). Optional: data-tip-desc, data-tip-icon.
     `data-tip-collapsed` = only while the sidebar is collapsed.
   - Sidebar nav links, only while the sidebar is collapsed (when it's
     expanded the label is already visible).
   Dropdown triggers (<button>s that open a flyout) are skipped on
   purpose — a tooltip would sit on top of their submenu.

   Placement: right of the element for the sidebar, below it for the
   topbar (flipped to right-aligned when close to the screen's edge so
   it can never be clipped). Never shown on mobile (no hover on touch).
──────────────────────────────────────────────────────────── */
const TIP_DESCRIPTIONS = {
  'home':           'Dashboard and daily overview',
  'suppliers':      'Delivery partners and contacts',
  'reports':        'Sales and profit analytics',
  'inventory logs': 'History of every stock change',
}
const tip = ref({ show: false, label: '', desc: '', icon: '', current: false, place: 'right', style: {} })
let tipTimer = null
let tipEl = null

function hideTip() {
  if (tipTimer) { clearTimeout(tipTimer); tipTimer = null }
  tipEl = null
  tip.value.show = false
}

function onTipOver(e) {
  if (isMobile.value) return
  const target = e.target
  if (!(target instanceof Element)) return

  // Strip native `title`s on the target and every ancestor inside the
  // sidebar / topbar, BEFORE any early return — otherwise the browser's plain
  // tooltip still shows when we decide not to show ours (e.g. sidebar expanded).
  for (let n = target; n; n = n.parentElement) {
    if (n.hasAttribute('title') && n.closest('.sidebar, .topbar')) {
      if (!n.dataset.tipLabel) n.dataset.tipLabel = n.getAttribute('title')
      n.removeAttribute('title')
    }
    if (n === e.currentTarget) break
  }

  const el = target.closest('[data-tip], .sidebar-nav a')
  if (!el) { hideTip(); return }
  if (el === tipEl) return // already showing / pending for this one

  const inSidebar = !!el.closest('.sidebar')
  const hasTip    = el.hasAttribute('data-tip')

  // Sidebar nav links only need a tooltip while collapsed; the user card
  // (data-tip-collapsed) follows the same rule.
  if (inSidebar && sidebarOpen.value && (!hasTip || el.hasAttribute('data-tip-collapsed'))) {
    hideTip(); return
  }
  // The notification panel is open — don't float a tooltip over it.
  if (notifOpen.value && el.closest('.notif-wrap')) { hideTip(); return }

  const label = (
    el.getAttribute('data-tip') ||
    el.closest('[data-tip-label]')?.dataset.tipLabel ||
    el.querySelector('[data-tip-label]')?.dataset.tipLabel ||
    el.getAttribute('aria-label') ||
    el.textContent || ''
  ).replace(/\s+/g, ' ').trim()
  if (!label) { hideTip(); return }

  const icon =
    el.getAttribute('data-tip-icon') ||
    el.querySelector('i[class*="fa-"]')?.className ||
    ''
  const desc =
    el.getAttribute('data-tip-desc') ||
    TIP_DESCRIPTIONS[label.toLowerCase()] ||
    ''
  const current =
    el.getAttribute('aria-current') === 'page' ||
    el.classList.contains('router-link-active') ||
    el.classList.contains('router-link-exact-active')

  tipEl = el
  if (tipTimer) clearTimeout(tipTimer)
  // Short delay so sweeping the pointer across the UI doesn't flicker a
  // tooltip on everything it crosses; an already-visible tooltip jumps instantly.
  tipTimer = setTimeout(() => {
    if (!tipEl || !tipEl.isConnected) return
    const r = tipEl.getBoundingClientRect()
    let place, style

    if (inSidebar) {
      place = 'right'
      style = { top: r.top + r.height / 2 + 'px', left: r.right + 14 + 'px' }
    } else if (r.left + r.width / 2 < 150) {
      // near the left edge: left-align with the element
      place = 'bottom-start'
      style = {
        top: r.bottom + 12 + 'px',
        left: Math.max(8, r.left) + 'px',
        '--arrow-x': Math.max(14, r.width / 2) + 'px',
      }
    } else if (r.left + r.width / 2 > window.innerWidth - 150) {
      // near the right edge: right-align the tooltip with the element and
      // slide the arrow so it still points at the element's centre
      place = 'bottom-end'
      style = {
        top: r.bottom + 12 + 'px',
        right: Math.max(8, window.innerWidth - r.right) + 'px',
        '--arrow-x': Math.max(14, r.width / 2) + 'px',
      }
    } else {
      place = 'bottom'
      style = { top: r.bottom + 12 + 'px', left: r.left + r.width / 2 + 'px' }
    }

    tip.value = { show: true, label, desc, icon, current, place, style }
    tipTimer = null
  }, tip.value.show ? 0 : 140)
}

function clearSearch() {
  search.value = ''
  searchInputRef.value?.focus()
}

/* ─── NOTIFICATIONS ───────────────────────────────────── */
const notifOpen      = ref(false)
const notifWrapRef   = ref(null)

// Where clicking a given notification type should take the user.
// Keyed the same way notifIcon() is — add new types to both together.
const NOTIF_ROUTES = {
  sale:              '/sales-transaction',
  low_stock:         '/products',
  out_of_stock:      '/products',
  supplier_delivery: '/suppliers',
  product_created:   '/products',
}

// low_stock / out_of_stock descriptions embed the product name, e.g.
// "rew has only 9 units left." or "rew is out of stock." — there's no
// dedicated product_id field on the payload, so we pull the name back
// out of the text and hand it to the Product List page as a query param.
// Best-effort fallback: won't survive duplicate names or later renames.
function extractProductName(n) {
  const desc = n.description || ''
  let m = desc.match(/^(.*?) has (?:only )?\d+ units? left\.?$/i)
  if (m) return m[1].trim()
  m = desc.match(/^(.*?) is out of stock\.?$/i)
  if (m) return m[1].trim()
  return null
}

/** Marks the notification read, closes the dropdown, then navigates
 *  wherever that notification type is relevant.
 *
 *  markRead() is intentionally NOT awaited — it updates the store
 *  instantly, and the store is a singleton that survives navigation, so
 *  there's nothing to race by navigating away immediately.
 *
 *  low_stock / out_of_stock: Product List in its "Low Stock" filter,
 *  highlighting the product by NAME (extracted from the description).
 *  product_created / sale: notifiable_id is populated, so it's passed
 *  straight through as highlightId / highlightSale.
 *
 *  Unrecognized types just mark-as-read and close the dropdown. */
function goToNotification(n) {
  notificationsStore.markRead(n.id)
  notifOpen.value = false

  const path = NOTIF_ROUTES[n.type]
  if (!path) return

  if (n.type === 'low_stock' || n.type === 'out_of_stock') {
    const productName = extractProductName(n)
    router.push({
      path,
      query: {
        stockFilter: 'low',
        highlight: productName || undefined,
      },
    })
  } else if (n.type === 'product_created') {
    router.push({
      path,
      query: {
        highlightId: n.notifiable_id || undefined,
      },
    })
  } else if (n.type === 'sale') {
    router.push({
      path,
      query: {
        highlightSale: n.notifiable_id || undefined,
      },
    })
  } else {
    router.push(path)
  }
}

const notifIcon = (type) => ({
  low_stock: 'fa-solid fa-triangle-exclamation',
  out_of_stock: 'fa-solid fa-circle-exclamation',
  sale: 'fa-solid fa-cash-register',
  supplier_delivery: 'fa-solid fa-truck',
  product_created: 'fa-solid fa-box-open',
}[type] || 'fa-solid fa-bell')

function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr)) / 1000
  if (diff < 60) return 'just now'
  if (diff < 3600) return Math.floor(diff / 60) + 'm ago'
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago'
  return Math.floor(diff / 86400) + 'd ago'
}

// Lets any page force an immediate notification refresh instead of
// waiting on the 30s poll — e.g. POS dispatches this after checkout.
function onNotifyRefresh() { notificationsStore.fetchNotifications() }

// Pause background notification polling while the tab isn't visible.
// Polling is guarded inside the store (singleton), so it can't stack up
// duplicate timers across Layout remounts.
function onVisibilityChange() {
  if (document.hidden) {
    notificationsStore.stopPolling()
  } else {
    notificationsStore.fetchNotifications() // catch up immediately on return
    notificationsStore.startPolling()
  }
}

/* ─── CLICK-OUTSIDE-TO-CLOSE (notif dropdown + account popup) ────
   One document-level listener closes whichever popup is open when a
   click lands outside it (and outside its trigger). Bubble phase, so it
   fires after the trigger's own @click has toggled the value.
──────────────────────────────────────────────────────────────── */
function onDocumentClick(e) {
  if (notifOpen.value && notifWrapRef.value && !notifWrapRef.value.contains(e.target)) {
    notifOpen.value = false
  }
  if (userMenuOpen.value) {
    const insideTrigger = userMiniRef.value?.contains(e.target)
    const insidePopup    = userPopupRef.value?.contains(e.target)
    if (!insideTrigger && !insidePopup) {
      userMenuOpen.value = false
    }
  }
}

onMounted(async () => {
  /* Init theme before anything renders */
  initDarkMode()

  // Start the "Go to POS" button's auto-dock countdown for this page.
  if (!isPosPage.value) schedulePosFabDock()

  // Interceptors respect a per-request `skipLoading` flag so silent
  // background calls (notification polling, read receipts) don't flash
  // the progress bar.
  reqI = api.interceptors.request.use(
    c => { if (!c.skipLoading) startLoading(); return c },
    e => { if (!e?.config?.skipLoading) stopLoading(); return Promise.reject(e) }
  )
  resI = api.interceptors.response.use(
    r => { if (!r.config?.skipLoading) stopLoading(); return r },
    e => { if (!e?.config?.skipLoading) stopLoading(); return Promise.reject(e) }
  )

  window.addEventListener('resize', onWindowResize)
  window.addEventListener('keydown', onGlobalKeydown)
  window.addEventListener('online', onOnline)
  window.addEventListener('offline', onOffline)
  document.addEventListener('click', onDocumentClick)

  clockTimer = setInterval(() => { now.value = new Date() }, 30_000)

  // Auth check + first notifications fetch run in parallel — they're
  // independent. authStore.checkAuth() is a no-op network-wise if the
  // router guard already resolved auth for this session.
  await Promise.allSettled([
    authStore.checkAuth(),
    notificationsStore.fetchNotifications(),
  ])

  initializing.value = false // first check settled either way — drop the blocking overlay

  if (!authenticated.value) {
    router.push('/login')
    return // no point starting notif polling if we're bouncing to login
  }

  notificationsStore.startPolling()
  document.addEventListener('visibilitychange', onVisibilityChange)
  window.addEventListener('notifications:refresh', onNotifyRefresh)
})

onBeforeUnmount(() => {
  if (reqI !== null) api.interceptors.request.eject(reqI)
  if (resI !== null) api.interceptors.response.eject(resI)
  if (showTimer) clearTimeout(showTimer)
  if (hideTimer) clearTimeout(hideTimer)
  if (clockTimer) clearInterval(clockTimer)
  clearPosFabTimer()
  if (tipTimer) clearTimeout(tipTimer)
  // Polling is NOT stopped here — it belongs to notificationsStore (a
  // singleton), not this Layout instance.
  document.removeEventListener('visibilitychange', onVisibilityChange)
  document.removeEventListener('click', onDocumentClick)
  window.removeEventListener('scroll', onScrollOrResizeUserMenu, true)
  window.removeEventListener('resize', onScrollOrResizeUserMenu)
  window.removeEventListener('resize', onWindowResize)
  window.removeEventListener('keydown', onGlobalKeydown)
  window.removeEventListener('online', onOnline)
  window.removeEventListener('offline', onOffline)
  window.removeEventListener('notifications:refresh', onNotifyRefresh)
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
  loggingOut.value = true
  try { await api.post('/logout') }
  catch (err) { console.error('Logout error:', err) }
  finally {
    loggingOut.value = false
    showLogoutModal.value = false
    userMenuOpen.value = false
    router.push('/login')
  }
}
</script>

<style scoped>
/* ─── SHELL ───────────────────────────────────────────── */
.app-shell {
  display: flex;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: var(--c-bg);
  font-family: 'Inter', system-ui, sans-serif;
  color: var(--c-text-primary);
  position: relative;
  -webkit-font-smoothing: antialiased;
}

/* Skip link — invisible until focused with the keyboard */
.skip-link {
  position: fixed;
  top: 10px; left: 10px;
  z-index: 10001;
  padding: 8px 14px;
  border-radius: 8px;
  background: var(--c-accent);
  color: #fff;
  font-size: 13px; font-weight: 600;
  text-decoration: none;
  transform: translateY(-160%);
  transition: transform .18s ease;
}
.skip-link:focus { transform: translateY(0); }

/* Visible keyboard focus everywhere that's interactive in the shell */
.burger:focus-visible,
.icon-btn:focus-visible,
.action-btn:focus-visible,
.user-mini:focus-visible,
.pos-fab:focus-visible,
.search-clear:focus-visible,
.notif-mark-all:focus-visible,
.notif-item:focus-visible,
.btn-ghost:focus-visible,
.btn-danger:focus-visible,
.greeting-logo-link:focus-visible,
.brand-wrap:focus-visible {
  outline: 2px solid var(--c-accent);
  outline-offset: 2px;
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
  border: solid rgb(255, 255, 255) 1px; border-radius: 50px;
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
  font-size: 12px; font-weight: 500;
  color: var(--c-text-faint);
  letter-spacing: 0.04em;
}
.loader-fade-enter-active { transition: opacity .18s ease; }
.loader-fade-leave-active { transition: opacity .30s ease; }
.loader-fade-enter-from, .loader-fade-leave-to { opacity: 0; }

/* ═══════════════════════════════════════════════════════
   SLIM TOP PROGRESS BAR — non-blocking busy indicator
════════════════════════════════════════════════════════ */
.top-progress-bar {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: 3px;
  z-index: 10000;
  pointer-events: none;
  background: var(--c-accent-ring);
  overflow: hidden;
}
.top-progress-fill {
  height: 100%;
  width: 42%;
  background: linear-gradient(90deg, var(--c-accent), var(--c-accent-h), var(--c-accent));
  border-radius: 0 3px 3px 0;
  animation: bar-slide 1.1s ease-in-out infinite;
}
.topbar-fade-enter-active { transition: opacity .12s ease; }
.topbar-fade-leave-active { transition: opacity .18s ease; }
.topbar-fade-enter-from, .topbar-fade-leave-to { opacity: 0; }

/* ═══════════════════════════════════════════════════════
   MOBILE SIDEBAR BACKDROP
════════════════════════════════════════════════════════ */
.mobile-backdrop {
  position: fixed;
  inset: 0;
  z-index: 480;
  background: var(--c-overlay);
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
}

/* ─── SIDEBAR ─────────────────────────────────────────── */
.sidebar {
  width: var(--sw, 68px);
  min-width: var(--sw, 68px);
  height: 100vh;
  background: var(--c-sidebar-bg);
  border-right: 1px solid var(--c-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition:
    width 0.26s cubic-bezier(.4,0,.2,1),
    min-width 0.26s cubic-bezier(.4,0,.2,1),
    transform 0.28s cubic-bezier(.4,0,.2,1),
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

/* Below the mobile breakpoint the sidebar leaves the flex flow entirely
   and becomes a full-height off-canvas drawer. */
.sidebar--mobile {
  position: fixed;
  top: 0;
  left: 0;
  width: 260px !important;
  min-width: 260px !important;
  transform: translateX(-100%);
  box-shadow: var(--c-shadow-xl);
  z-index: 500;
}
.sidebar--mobile.sidebar--open {
  transform: translateX(0);
}

.sidebar-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 12px;
  height: 64px;
  min-height: 64px;
  flex-shrink: 0;
  border-bottom: 1px solid var(--c-border);
}

.burger {
  width: 40px; height: 40px; min-width: 40px;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 4.5px;
  background: none; border: none; cursor: pointer;
  padding: 0; border-radius: 10px;
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
.bline--mid        { width: 12px; align-self: flex-start; margin-left: 11px; }
.bline--mid-hidden { opacity: 0; width: 0; }

.brand-wrap {
  display: flex; align-items: center; gap: 10px;
  text-decoration: none; overflow: hidden; white-space: nowrap; flex: 1;
  border-radius: 8px;
}
.brand-logo { width: 32px; height: 32px; object-fit: fill; border: solid rgb(255, 255, 255) 1px; border-radius: 50px; flex-shrink: 0; }
.brand-text { display: flex; flex-direction: column; line-height: 1.15; }
.brand-name { font-size: 14px; font-weight: 700; color: var(--c-text-primary); letter-spacing: -.005em; }
.brand-sub  { font-size: 11.5px; color: var(--c-text-faint); margin-top: 1px; font-weight: 500; }

.sidebar-nav {
  flex: 1; padding: 12px 10px;
  overflow-y: auto; overflow-x: hidden;
  display: flex; flex-direction: column; gap: 2px;
  scrollbar-width: none;
}
.sidebar-nav::-webkit-scrollbar { display: none; }

/* Divider between nav groups (overview / daily work / management) */
.nav-divider {
  height: 1px;
  background: var(--c-border);
  margin: 8px 6px;
  flex-shrink: 0;
}

.sidebar-footer {
  padding: 10px;
  flex-shrink: 0;
  border-top: 1px solid var(--c-border);
}

/* user-mini is a <button> acting as the account-menu trigger */
.user-mini {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px; border-radius: 10px;
  overflow: hidden; white-space: nowrap;
  background: transparent;
  border: 1px solid transparent;
  transition: background-color 0.18s ease, border-color 0.18s ease;
  cursor: pointer;
  width: 100%;
  font-family: inherit;
  text-align: left;
}
.user-mini--center { justify-content: center; padding: 8px 0; }
.user-mini:hover,
.user-mini--open   { background: var(--c-surface-raised); border-color: var(--c-border); }

.user-avatar-wrap  { position: relative; flex-shrink: 0; }
.user-avatar-sm    { width: 34px; height: 34px; border-radius: 9px; object-fit: cover; display: block; }
.user-status-dot   { position: absolute; bottom: -2px; right: -2px; width: 10px; height: 10px; background: var(--c-online); border-radius: 50%; border: 2px solid var(--c-sidebar-bg); transition: background-color .2s ease; }
.user-status-dot--offline { background: var(--c-text-faint); }

/* Vector-icon avatar — shared by sidebar-mini and the teleported popup */
.user-avatar-vector {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--c-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff;
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.14);
}
.user-avatar-sm.user-avatar-vector       { font-size: 14px; }
.profile-menu-avatar.user-avatar-vector  { font-size: 17px; }

.user-mini-info { overflow: hidden; flex: 1; }
.user-mini-name { font-size: 13px; font-weight: 600; color: var(--c-text-primary); line-height: 1.25; overflow: hidden; text-overflow: ellipsis; }
.user-mini-role { font-size: 11.5px; color: var(--c-text-faint); margin-top: 1px; font-weight: 500; }

.user-mini-caret {
  font-size: 10px;
  color: var(--c-text-faint);
  transition: transform .2s ease;
  flex-shrink: 0;
}
.user-mini-caret--down { transform: rotate(180deg); }

/* ─── MAIN COLUMN ─────────────────────────────────────── */
.main-column { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; min-width: 0; }

/* ─── TOP BAR ─────────────────────────────────────────── */
.topbar {
  height: 64px; min-height: 64px;
  background: var(--c-surface);
  border-bottom: 1px solid var(--c-border);
  display: flex; align-items: center; gap: 20px;
  padding: 0 28px;
  z-index: 40; flex-shrink: 0;
  position: relative;
  transition: background-color 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
}
.topbar--scrolled {
  box-shadow: 0 1px 2px rgba(16,24,40,.05), 0 4px 12px -4px rgba(16,24,40,.08);
}

.mobile-menu-btn { flex-shrink: 0; }

/* ─── GREETING ────────────────────────────────────────── */
.topbar-greeting {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
  min-width: 0;
}

.greeting-logo-link {
  display: flex;
  flex-shrink: 0;
  border-radius: 9px;
  transition: transform 0.15s ease;
}
.greeting-logo-link:hover { transform: scale(1.05); }
.greeting-logo {
  width: 34px; height: 34px;
  object-fit: contain;
  display: block;
  border: solid rgb(255, 255, 255) 1px; border-radius: 50px;
}

.greeting-text { min-width: 0; }

.greeting-name {
  font-size: 14.5px; font-weight: 650;
  color: var(--c-text-primary);
  line-height: 1.25;
  white-space: nowrap;
  letter-spacing: -0.01em;
}
.greeting-name span { color: var(--c-text-primary); font-weight: 700; }

.greeting-sub {
  font-size: 12px; color: var(--c-text-faint);
  margin-top: 1px; white-space: nowrap; font-weight: 500;
}

/* ─── SEARCH ──────────────────────────────────────────── */
.search-wrap { flex: 1; min-width: 0; max-width: 440px; margin: 0 auto; position: relative; }
.search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--c-text-placeholder); font-size: 12px; pointer-events: none; transition: color .18s ease; }
.search-wrap:focus-within .search-icon { color: var(--c-accent); }
.search-input {
  width: 100%; height: 40px;
  border: 1px solid var(--c-border-input);
  border-radius: 10px;
  padding: 0 40px 0 36px;
  font-size: 13.5px;
  color: var(--c-text-primary);
  background: var(--c-surface-sunken);
  outline: none;
  transition: border-color .18s ease, box-shadow .18s ease, background-color 0.22s ease, color 0.22s ease;
  font-family: inherit;
  -webkit-appearance: none;
  appearance: none;
}
.search-input::-webkit-search-cancel-button { -webkit-appearance: none; display: none; }
.search-input:hover { border-color: var(--c-border-strong); }
.search-input:focus { border-color: var(--c-accent); box-shadow: 0 0 0 3px var(--c-accent-ring); background: var(--c-surface); }
.search-input::placeholder { color: var(--c-text-placeholder); }
.search-kbd {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  font-size: 11px; font-weight: 600;
  color: var(--c-text-faint);
  background: var(--c-surface);
  border: 1px solid var(--c-border-strong);
  border-bottom-width: 2px;
  border-radius: 5px;
  min-width: 20px;
  padding: 0 6px;
  line-height: 18px;
  text-align: center;
  pointer-events: none;
  font-family: inherit;
}
.search-clear {
  position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
  width: 24px; height: 24px;
  display: flex; align-items: center; justify-content: center;
  border: none; border-radius: 6px;
  background: transparent;
  color: var(--c-text-faint);
  font-size: 12px; cursor: pointer;
  transition: background-color .15s ease, color .15s ease;
}
.search-clear:hover { background: var(--c-border); color: var(--c-text-primary); }

/* ─── TOPBAR ACTIONS ──────────────────────────────────── */
.topbar-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

.topbar-clock {
  font-size: 13px; font-weight: 600;
  color: var(--c-text-muted);
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
.topbar-sep {
  width: 1px; height: 22px;
  background: var(--c-border-strong);
  margin: 0 2px;
}

.status-chip {
  display: inline-flex; align-items: center; gap: 6px;
  height: 28px; padding: 0 10px;
  border-radius: 999px;
  font-size: 12px; font-weight: 600;
  white-space: nowrap;
}
.status-chip--offline {
  background: var(--c-danger-soft);
  color: var(--c-danger);
}

.action-link { display: flex; text-decoration: none; }

.action-btn {
  display: flex; align-items: center; gap: 7px;
  height: 38px; padding: 0 16px;
  border-radius: 10px; border: none;
  font-size: 13.5px; font-weight: 600;
  cursor: pointer; font-family: inherit; text-decoration: none;
  transition: background 0.18s ease, transform 0.15s ease, box-shadow 0.18s ease, filter .18s ease;
}
.action-btn--primary {
  background: var(--c-accent);
  color: #fff;
  box-shadow: 0 1px 2px rgba(16,24,40,.12), inset 0 1px 0 rgba(255,255,255,.14);
}
.action-btn--primary:hover { filter: brightness(1.07); box-shadow: var(--c-shadow-accent); }
.action-btn--primary:active { transform: translateY(1px); filter: brightness(.97); }

.icon-btn {
  width: 38px; height: 38px;
  border: 1px solid var(--c-border-strong);
  border-radius: 10px;
  background: var(--c-surface);
  color: var(--c-text-muted);
  font-size: 14px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
  position: relative;
  /* overflow stays visible — the notif-badge sits half outside the
     button's box on purpose. */
}
.icon-btn:hover,
.icon-btn--active { background: var(--c-accent-soft); border-color: var(--c-accent-border); color: var(--c-accent); }
.icon-btn--active { box-shadow: 0 0 0 3px var(--c-accent-ring); }

/* Notification count badge — a number (capped at "9+") so it's a
   triage signal, not just a "something happened" flag. */
.notif-badge {
  position: absolute;
  top: -5px; right: -5px;
  min-width: 18px; height: 18px;
  padding: 0 5px;
  display: flex; align-items: center; justify-content: center;
  background: var(--c-accent);
  color: #fff;
  font-size: 10.5px; font-weight: 700;
  line-height: 1;
  border-radius: 999px;
  border: 2px solid var(--c-surface);
  font-variant-numeric: tabular-nums;
}

/* ─── NOTIFICATIONS ───────────────────────────────────── */
.notif-wrap { position: relative; }

.notif-menu {
  position: absolute; right: 0; top: calc(100% + 10px);
  width: 360px; max-width: calc(100vw - 24px); max-height: 460px;
  display: flex; flex-direction: column;
  background: var(--c-surface-overlay);
  border: 1px solid var(--c-border);
  border-radius: 14px;
  box-shadow: var(--c-shadow-lg);
  z-index: 200; padding: 6px;
  transition: background-color 0.22s ease, border-color 0.22s ease;
}

.notif-menu-header {
  display: flex; align-items: center; justify-content: space-between;
  gap: 10px;
  padding: 10px 10px 10px;
}
.notif-menu-heading { display: flex; align-items: center; gap: 8px; }
.notif-menu-title { font-size: 14px; font-weight: 700; color: var(--c-text-primary); }
.notif-count {
  font-size: 11.5px; font-weight: 600;
  color: var(--c-accent);
  background: var(--c-accent-soft);
  padding: 2px 8px;
  border-radius: 999px;
}
.notif-mark-all {
  background: none; border: none; cursor: pointer; font-family: inherit;
  font-size: 12px; font-weight: 600; color: var(--c-accent);
  padding: 4px 8px; border-radius: 6px;
  transition: background-color .15s ease;
}
.notif-mark-all:hover { background: var(--c-accent-soft); }

.notif-list { list-style: none; margin: 4px 0 0; padding: 0; overflow-y: auto; display: flex; flex-direction: column; gap: 2px; }

.notif-item {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 10px; border-radius: 10px; cursor: pointer; position: relative;
  transition: background-color .15s ease;
}
/* :not(.notif-item--unread) keeps the two hover rules from ever
   applying to the same element. */
.notif-item:not(.notif-item--unread):hover { background: var(--c-surface-raised); }
.notif-item--unread { background: var(--c-accent-soft) !important; }
/* Dedicated hover token (--c-accent-soft-hover, defined per-theme in
   theme.css) — deriving it from --c-accent-soft with a filter clipped to
   pure white in light mode. */
.notif-item--unread:hover { background: var(--c-accent-soft-hover) !important; }

.notif-item-icon {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px;
  background: var(--c-surface-sunken); color: var(--c-text-muted);
}
.notif-item-icon--low_stock,
.notif-item-icon--out_of_stock       { background: var(--c-danger-soft);   color: var(--c-danger); }
.notif-item-icon--sale               { background: var(--c-accent-soft);   color: var(--c-accent); }
.notif-item-icon--supplier_delivery  { background: var(--c-surface-sunken); color: var(--c-text-secondary); }
/* New-product notifications get their own green so they read as a
   positive "something was added" event. */
.notif-item-icon--product_created    { background: rgba(16,185,129,0.12); color: #10b981; }
html[data-theme="dark"] .notif-item-icon--product_created { background: rgba(16,185,129,0.18); color: #4ADE80; }

.notif-item-body { flex: 1; min-width: 0; }
.notif-item-title { font-size: 13px; font-weight: 600; color: var(--c-text-primary); line-height: 1.35; }
.notif-item-desc  { font-size: 12.5px; color: var(--c-text-muted); margin-top: 2px; line-height: 1.45; }
.notif-item-time  { font-size: 11.5px; color: var(--c-text-faint); margin-top: 4px; font-weight: 500; }

.notif-item-dot {
  width: 8px; height: 8px; border-radius: 50%; background: var(--c-accent);
  flex-shrink: 0; margin-top: 6px;
}

.notif-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 36px 20px; color: var(--c-text-faint); gap: 6px; text-align: center;
}
.notif-empty i { font-size: 24px; margin-bottom: 4px; }
.notif-empty p { font-size: 13.5px; font-weight: 600; color: var(--c-text-secondary); }
.notif-empty span { font-size: 12.5px; line-height: 1.5; max-width: 220px; }

.pmenu-divider { height: 1px; background: var(--c-border); margin: 2px 0; }

.pmenu-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px; font-size: 13.5px;
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
  padding: 32px 32px 40px;
  background: var(--c-bg);
  transition: background-color 0.22s ease;
  scrollbar-width: thin;
  scrollbar-color: var(--c-border-strong) transparent;
  outline: none;
}
.page-content::-webkit-scrollbar { width: 10px; }
.page-content::-webkit-scrollbar-track { background: transparent; }
.page-content::-webkit-scrollbar-thumb {
  background: var(--c-border-strong);
  border-radius: 999px;
  border: 3px solid transparent;
  background-clip: content-box;
}
.page-content::-webkit-scrollbar-thumb:hover { background: var(--c-text-faint); background-clip: content-box; }
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
  opacity: 0.92;
  box-shadow: var(--c-shadow-accent), 0 2px 6px rgba(0,0,0,0.12);
  transition:
    right .32s cubic-bezier(.4,0,.2,1),
    gap .32s cubic-bezier(.4,0,.2,1),
    padding .32s cubic-bezier(.4,0,.2,1),
    border-radius .32s cubic-bezier(.4,0,.2,1),
    transform .32s cubic-bezier(.4,0,.2,1),
    opacity .22s ease,
    box-shadow .18s ease,
    background-color .18s ease,
    filter .18s ease;
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
  transition: background-color .25s ease;
}
.pos-fab-label {
  white-space: nowrap;
  overflow: hidden;
  max-width: 120px;
  opacity: 1;
  transition: max-width .32s cubic-bezier(.4,0,.2,1), opacity .2s ease;
}

/* DOCKED — tucks into the right edge as a small icon-only tab. */
.pos-fab--docked {
  right: 0;
  gap: 0;
  padding: 11px 10px 11px 12px;
  border-radius: 999px 0 0 999px;
  opacity: 0.7;
  transform: translateX(4px);
}
.pos-fab--docked:hover { transform: translateX(0); }
.pos-fab--docked .pos-fab-label {
  max-width: 0;
  opacity: 0;
}
.pos-fab--docked .pos-fab-icon { background: transparent; }

@media (max-width: 560px) {
  .pos-fab {
    right: 16px;
    bottom: 16px;
    padding: 13px;
    border-radius: 50%;
  }
  .pos-fab-label { display: none; }
  .pos-fab-icon { width: 18px; height: 18px; background: transparent; }

  .pos-fab--docked {
    right: 0;
    padding: 13px 10px 13px 13px;
    border-radius: 999px 0 0 999px;
  }
}

/* ─── LOGOUT MODAL ────────────────────────────────────── */
.modal-backdrop {
  position: fixed; inset: 0;
  background: var(--c-overlay);
  backdrop-filter: blur(6px);
  z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
}
.modal {
  background: var(--c-surface-overlay);
  border-radius: 16px;
  padding: 32px 28px 24px;
  width: 380px;
  max-width: 100%;
  box-shadow: var(--c-shadow-xl);
  text-align: center;
  border: 1px solid var(--c-border);
  transition: background-color 0.22s ease, border-color 0.22s ease;
}
.modal-icon-wrap {
  width: 48px; height: 48px;
  background: var(--c-accent-soft);
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 16px;
  border: 1px solid var(--c-accent-border);
}
.modal-title { font-size: 17px; font-weight: 700; color: var(--c-text-primary); margin-bottom: 6px; letter-spacing: -.01em; }
.modal-desc  { font-size: 13.5px; color: var(--c-text-muted); margin-bottom: 24px; line-height: 1.6; }
.modal-actions { display: flex; gap: 10px; }
.modal-actions > button { flex: 1; }

.btn-ghost {
  padding: 10px 22px;
  border: 1px solid var(--c-border-strong);
  border-radius: 10px;
  background: var(--c-surface);
  font-size: 13.5px; font-weight: 600;
  color: var(--c-text-secondary);
  cursor: pointer; font-family: inherit;
  transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}
.btn-ghost:hover { background: var(--c-surface-raised); color: var(--c-text-primary); }
.btn-ghost:disabled { opacity: 0.55; cursor: not-allowed; pointer-events: none; }

.btn-danger {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 10px 22px; border: none; border-radius: 10px;
  background: var(--c-danger);
  font-size: 13.5px; font-weight: 600; color: #fff;
  cursor: pointer; font-family: inherit;
  box-shadow: 0 1px 2px rgba(16,24,40,.12), inset 0 1px 0 rgba(255,255,255,.14);
  transition: filter 0.18s ease, transform 0.18s ease;
}
.btn-danger:hover { filter: brightness(1.08); }
.btn-danger:active { transform: translateY(1px); }
.btn-danger:disabled {
  opacity: 0.75; cursor: not-allowed; pointer-events: none; transform: none;
}
/* min-width keeps the button from resizing when the label switches
   between "Sign out" and "Signing out…" */
.btn-danger { min-width: 124px; }

/* ─── THEMED TOOLTIP ──────────────────────────────────── */
.nav-tip {
  position: fixed;
  z-index: 9990;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 7px 12px 7px 7px;
  background: var(--c-surface-overlay);
  border: 1px solid var(--c-border-strong);
  border-radius: 12px;
  box-shadow: var(--c-shadow-lg);
  white-space: nowrap;
  pointer-events: none;
}
.nav-tip--right      { transform: translateY(-50%); }
.nav-tip--bottom     { transform: translateX(-50%); }
.nav-tip--bottom-end,
.nav-tip--bottom-start { transform: none; }

/* little arrow pointing back at the trigger */
.nav-tip::before {
  content: '';
  position: absolute;
  width: 9px; height: 9px;
  background: var(--c-surface-overlay);
  border: 1px solid var(--c-border-strong);
}
.nav-tip--right::before {
  left: -5px; top: 50%;
  border-right: none; border-top: none;
  transform: translateY(-50%) rotate(45deg);
  border-bottom-left-radius: 2px;
}
.nav-tip--bottom::before {
  top: -5px; left: 50%;
  border-bottom: none; border-right: none;
  transform: translateX(-50%) rotate(45deg);
  border-top-left-radius: 2px;
}
.nav-tip--bottom-start::before {
  top: -5px; left: var(--arrow-x, 20px);
  border-bottom: none; border-right: none;
  transform: translateX(-50%) rotate(45deg);
  border-top-left-radius: 2px;
}
.nav-tip--bottom-end::before {
  top: -5px; right: var(--arrow-x, 20px);
  border-bottom: none; border-right: none;
  transform: translateX(50%) rotate(45deg);
  border-top-left-radius: 2px;
}

.nav-tip-icon {
  width: 30px; height: 30px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px;
  background: var(--c-accent-soft);
  color: var(--c-accent);
  flex-shrink: 0;
}
.nav-tip-text { display: flex; flex-direction: column; line-height: 1.25; }
.nav-tip-label { font-size: 13px; font-weight: 650; color: var(--c-text-primary); letter-spacing: -.005em; }
.nav-tip-desc  { font-size: 11.5px; font-weight: 500; color: var(--c-text-faint); margin-top: 1px; }
.nav-tip-current {
  margin-left: 4px;
  font-size: 11px; font-weight: 600;
  color: var(--c-accent);
  background: var(--c-accent-soft);
  padding: 2px 8px;
  border-radius: 999px;
}

.tip-enter-active { transition: opacity .16s ease, transform .16s cubic-bezier(.2,.8,.2,1); }
.tip-leave-active { transition: opacity .1s ease; }
.tip-enter-from, .tip-leave-to { opacity: 0; }
.tip-enter-from.nav-tip--right      { transform: translate(-6px, -50%); }
.tip-enter-from.nav-tip--bottom     { transform: translate(-50%, -6px); }
.tip-enter-from.nav-tip--bottom-end,
.tip-enter-from.nav-tip--bottom-start { transform: translateY(-6px); }

/* ─── TRANSITIONS ─────────────────────────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity .22s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }
.dropdown-enter-active { transition: opacity .18s ease, transform .18s ease; }
.dropdown-leave-active { transition: opacity .14s ease, transform .14s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px) scale(.98); }

/* ═══════════════════════════════════════════════════════
   RESPONSIVE — SMALL DESKTOP (≤1180px)
   The live clock is the first thing to go when space gets tight.
════════════════════════════════════════════════════════ */
@media (max-width: 1180px) {
  .topbar-clock, .topbar-sep { display: none; }
}

/* ═══════════════════════════════════════════════════════
   RESPONSIVE — TABLET (≤1024px)
════════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .topbar { padding: 0 20px; gap: 14px; }
  .search-wrap { max-width: 300px; }
  .page-content { padding: 24px 22px 32px; }
}

/* ═══════════════════════════════════════════════════════
   RESPONSIVE — MOBILE (≤768px)
   Sidebar is an off-canvas drawer (see .sidebar--mobile above); the
   topbar sheds secondary text and shrinks controls to fit.
════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
  .topbar { height: 58px; min-height: 58px; padding: 0 14px; gap: 10px; }
  .greeting-sub { display: none; }
  .greeting-name { font-size: 13px; }
  .greeting-logo { width: 28px; height: 28px; border-radius: 8px; }
  .search-kbd { display: none; }
  .search-wrap { max-width: none; }
  .search-input { height: 38px; }
  /* Collapses "Add item" to an icon-only button on small screens. */
  .action-btn--primary span { display: none; }
  .action-btn--primary { padding: 0 11px; height: 38px; }
  .page-content { padding: 16px 16px 28px; }
  .content-inner { max-width: 100%; }
}

@media (max-width: 640px) {
  .topbar-greeting { display: none; }
}

@media (max-width: 400px) {
  .modal { padding: 26px 18px 20px; }
  .notif-menu { right: -8px; }
}

/* Respect reduced-motion: no sliding, pulsing or bouncing */
@media (prefers-reduced-motion: reduce) {
  .sidebar, .pos-fab, .pos-fab-label, .dropdown-enter-active, .dropdown-leave-active,
  .fade-enter-active, .fade-leave-active, .skip-link, .topbar, .tip-enter-active, .tip-leave-active {
    transition-duration: .01ms !important;
  }
  .loading-bar-fill, .top-progress-fill, .loading-logo, .dot {
    animation: none !important;
  }
}
</style>

<style>
/* Unscoped — the account popup is teleported to <body>, outside this
   component's scoped attribute, so scoped styles wouldn't reach it
   (same pattern as the flyout submenu in SidebarDropdown.vue). */
.user-popup-menu {
  position: fixed;
  width: 240px;
  max-width: calc(100vw - 20px);
  background: var(--c-surface-overlay);
  border: 1px solid var(--c-border);
  border-radius: 14px;
  box-shadow: var(--c-shadow-lg);
  z-index: 9999; padding: 6px;
}
.user-popup-header { display: flex; align-items: center; gap: 10px; padding: 10px 10px 12px; }
.pmenu-avatar-wrap   { position: relative; flex-shrink: 0; }
.profile-menu-avatar { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; display: block; }
.pmenu-status-dot    { position: absolute; bottom: -2px; right: -2px; width: 10px; height: 10px; background: var(--c-online); border-radius: 50%; border: 2px solid var(--c-surface-overlay); }
.user-popup-id       { min-width: 0; }
.profile-menu-name   { font-size: 13.5px; font-weight: 700; color: var(--c-text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.profile-menu-email  { font-size: 12px; color: var(--c-text-faint); margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>