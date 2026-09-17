<template>
  <div class="app-shell" v-bind="$attrs" :style="{ '--sw': sidebarOpen ? '260px' : '64px' }">
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
         above everything but never intercepts clicks or dims the UI, so
         the app still feels responsive while work happens in the background. -->
    <transition name="topbar-fade">
      <div v-if="apiBusy" class="top-progress-bar" aria-hidden="true">
        <div class="top-progress-fill"></div>
      </div>
    </transition>

    <!-- MOBILE SIDEBAR BACKDROP — only exists on phone/small-tablet
         widths while the drawer is open. Tapping it closes the drawer,
         same as tapping outside any other overlay in this app. -->
    <transition name="fade">
      <div
        v-if="isMobile && sidebarOpen"
        class="mobile-backdrop"
        aria-hidden="true"
        @click="sidebarOpen = false"
      />
    </transition>

    <!-- SIDEBAR -->
    <aside class="sidebar" :class="{ 'sidebar--mobile': isMobile, 'sidebar--open': sidebarOpen }">
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

      <!-- SIDEBAR FOOTER — user card doubles as the account menu trigger -->
      <div class="sidebar-footer">
        <div class="sidebar-divider" />
        <button
          ref="userMiniRef"
          class="user-mini"
          :class="{ 'user-mini--center': !sidebarOpen, 'user-mini--open': userMenuOpen }"
          @click="toggleUserMenu"
        >
          <div class="user-avatar-wrap">
            <!-- Vector profile icon in a branded circle, instead of a
                 static clipart image — crisp at any size and matches
                 the icon style used everywhere else in the sidebar. -->
            <div class="user-avatar-sm user-avatar-vector" aria-hidden="true">
              <i class="fa-solid fa-user"></i>
            </div>
            <span class="user-status-dot"></span>
          </div>
          <div v-show="sidebarOpen" class="user-mini-info">
            <p class="user-mini-name">{{ user?.name || 'User' }}</p>
            <p class="user-mini-role">Administrator</p>
          </div>
          <i v-show="sidebarOpen" class="fa-solid fa-chevron-down user-mini-caret" :class="{ 'user-mini-caret--up': userMenuOpen }"></i>
        </button>
      </div>
    </aside>

    <!-- TELEPORTED ACCOUNT POPUP — escapes the sidebar's overflow:hidden -->
    <Teleport to="body">
      <transition name="dropdown">
        <div v-if="userMenuOpen" class="user-popup-menu" ref="userPopupRef" :style="userMenuStyle">
          <div class="user-popup-header">
            <div class="pmenu-avatar-wrap">
              <div class="profile-menu-avatar user-avatar-vector" aria-hidden="true">
                <i class="fa-solid fa-user"></i>
              </div>
              <span class="pmenu-status-dot"></span>
            </div>
            <div>
              <p class="profile-menu-name">{{ user?.name || 'User' }}</p>
              <p class="profile-menu-email">admin@sheemsteel.com</p>
            </div>
          </div>
          <div class="pmenu-divider" />
          <router-link to="/settings" class="pmenu-item" @click="userMenuOpen = false">
            <span class="pmenu-icon-wrap"><i class="fa-solid fa-gear"></i></span>
            Settings
          </router-link>
          <div class="pmenu-divider" />
          <button class="pmenu-item pmenu-item--danger" @click="showLogoutModal = true; userMenuOpen = false">
            <span class="pmenu-icon-wrap pmenu-icon-wrap--danger"><i class="fa-solid fa-right-from-bracket"></i></span>
            Sign Out
          </button>
        </div>
      </transition>
    </Teleport>

    <!-- MAIN COLUMN -->
    <div class="main-column">

      <!-- TOP BAR -->
      <header class="topbar">
        <!-- MOBILE MENU TRIGGER — the sidebar's own burger is inside the
             off-canvas drawer, so once it's closed there's nothing left
             on screen to reopen it from. This lives in the topbar instead,
             and only exists at mobile widths. -->
        <button v-if="isMobile" class="icon-btn mobile-menu-btn" aria-label="Open menu" @click="sidebarOpen = true">
          <i class="fa-solid fa-bars"></i>
        </button>

        <!-- GREETING — icon chip shifts color/glyph with time of day
             (sunrise amber → midday gold → accent evening → indigo
             night), paired with a matching greeting phrase. The date
             line now also carries a live clock, separated by a dot,
             both driven off the same ticking `now` ref so neither can
             drift out of sync with the other. -->
        <div class="topbar-greeting">
          <!-- The sidebar's own logo (brand-wrap, up in .sidebar-header) is
               hidden once the sidebar collapses to icon-only width — it's
               behind v-show="sidebarOpen". Rather than filling that gap
               with a decorative time-of-day icon that just duplicated the
               brand mark, show the real logo here instead, and only while
               it's genuinely missing from the sidebar. -->
          <router-link v-if="!sidebarOpen" to="/home" class="greeting-logo-link" aria-label="Sheem Steel home">
            <img :src="logo" class="greeting-logo" alt="Sheem Steel" />
          </router-link>
          <div class="greeting-text">
            <p class="greeting-name">{{ greetingPhrase }}, <span>{{ user?.name || 'User' }}</span></p>
            <p class="greeting-sub">
              <span class="greeting-date">{{ todayLabel }}</span>
              <span class="greeting-sep">·</span>
              <span class="greeting-clock">{{ clockLabel }}</span>
            </p>
          </div>
        </div>

        <div class="search-wrap">
          <i class="fa-solid fa-magnifying-glass search-icon"></i>
          <input
            ref="searchInputRef"
            v-model="search"
            @keyup.enter="goToProducts"
            @focus="searchFocused = true"
            @blur="searchFocused = false"
            class="search-input"
            placeholder="Search products…"
          />
          <kbd v-if="!searchFocused" class="search-kbd">/</kbd>
        </div>

        <div class="topbar-actions">
          <router-link to="/products/create">
            <button class="action-btn action-btn--primary" title="Add Product">
              <i class="fa-solid fa-plus"></i>
              <span v-show="sidebarOpen">Add Item</span>
            </button>
          </router-link>

          <!-- NOTIFICATIONS -->
          <div class="notif-wrap" ref="notifWrapRef">
            <button class="icon-btn" title="Notifications" style="position:relative" @click="notifOpen = !notifOpen">
              <i class="fa-solid fa-bell"></i>
              <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
            </button>

            <transition name="dropdown">
              <div v-if="notifOpen" class="notif-menu">
                <div class="notif-menu-header">
                  <p class="notif-menu-title">Notifications</p>
                  <button v-if="unreadCount > 0" class="notif-mark-all" @click="notificationsStore.markAllRead()">Mark all read</button>
                </div>
                <div class="pmenu-divider" />

                <div v-if="notifications.length === 0" class="notif-empty">
                  <i class="fa-regular fa-bell-slash"></i>
                  <p>You're all caught up.</p>
                </div>

                <ul v-else class="notif-list">
                  <li
                    v-for="n in notifications"
                    :key="n.id"
                    class="notif-item"
                    :class="{ 'notif-item--unread': !n.read_at }"
                    @click="goToNotification(n)"
                  >
                    <span class="notif-item-icon" :class="`notif-item-icon--${n.type}`">
                      <i :class="notifIcon(n.type)"></i>
                    </span>
                    <div class="notif-item-body">
                      <p class="notif-item-title">{{ n.title }}</p>
                      <p class="notif-item-desc">{{ n.description }}</p>
                      <p class="notif-item-time">{{ timeAgo(n.created_at) }}</p>
                    </div>
                    <span v-if="!n.read_at" class="notif-item-dot"></span>
                  </li>
                </ul>
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
    <div v-if="showLogoutModal" class="modal-backdrop" @click.self="!loggingOut && (showLogoutModal = false)">
      <div class="modal">
        <div class="modal-icon-wrap">
          <i class="fa-solid fa-right-from-bracket" style="font-size:20px;color:var(--c-accent)"></i>
        </div>
        <h2 class="modal-title">Sign out?</h2>
        <p class="modal-desc">You'll need to sign in again to access the system.</p>
        <div class="modal-actions">
          <button class="btn-ghost" :disabled="loggingOut" @click="showLogoutModal = false">Cancel</button>
          <button class="btn-danger" :disabled="loggingOut" @click="confirmLogout">
            <i v-if="loggingOut" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
            <span>{{ loggingOut ? 'Signing out…' : 'Sign Out' }}</span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
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

const router = useRouter()
const route  = useRoute()

// Single source of truth for the current user — shared with the router
// guard (and anywhere else that uses the store), instead of Layout doing
// its own independent /api/user fetch. See auth.ts: fetchUser()
// de-dupes concurrent calls, and checkAuth() skips the network entirely
// once the store has already resolved once this session.
const authStore = useAuthStore()
const { user, authenticated } = storeToRefs(authStore)

// Notification state now lives in a Pinia store (a singleton for the
// whole app session) instead of local refs here — see stores/notifications.js
// for why: Layout.vue remounts on every navigation, so local refs had
// no way to survive a route change, which is what made "mark as read"
// unreliable no matter how the network call was sequenced.
const notificationsStore = useNotificationsStore()
const { notifications, unreadCount } = storeToRefs(notificationsStore)

const search          = ref('')
const inventoryOpen   = ref(false)
const salesOpen       = ref(false)
const showLogoutModal = ref(false)
// Drives the spinner/disabled state on the logout modal's buttons — the
// request itself already triggers the slim top progress bar via the
// axios interceptors below, but that's a 3px line easy to miss under a
// blurred modal backdrop. This puts feedback directly on the button
// the user just clicked instead.
const loggingOut      = ref(false)

/* ─────────────────────────────────────────────────────────
   LIVE CLOCK — backs the greeting phrase, date, and time-of-day icon.
   `now` ticks every 30s (not computed once) so the topbar stays
   accurate even if the page is left open across a day/hour boundary —
   previously this only "worked" as a side effect of Layout remounting
   on every route change, which is not something to depend on.
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
   RESPONSIVE BREAKPOINT — drives the sidebar's mode.
   >768px:  sidebar lives in normal flow, `sidebarOpen` just toggles
            between the 260px expanded / 64px icon-only widths (unchanged
            desktop behavior).
   ≤768px:  sidebar becomes a fixed off-canvas drawer. `sidebarOpen` now
            means "drawer open/closed" instead of "expanded/collapsed",
            and a backdrop appears behind it.

   Computed eagerly (not in onMounted) so there's no flash of the
   desktop-expanded sidebar before the breakpoint check runs.
───────────────────────────────────────────────────────── */
const MOBILE_BREAKPOINT = 768
const isMobile    = ref(window.innerWidth <= MOBILE_BREAKPOINT)
const sidebarOpen = ref(!isMobile.value)

function onWindowResize() {
  const mobile = window.innerWidth <= MOBILE_BREAKPOINT
  if (mobile === isMobile.value) return
  isMobile.value = mobile
  // Crossing the breakpoint changes what sidebarOpen *means*, so reset
  // to the sensible default for the mode we just entered rather than
  // carrying over a value that meant something else a second ago.
  sidebarOpen.value = !mobile
}

// On mobile the drawer sits on top of the page; after navigating
// somewhere new there's nothing left to see behind it worth hiding, so
// close it automatically instead of making the user tap the backdrop.
watch(() => route.path, () => {
  if (isMobile.value) sidebarOpen.value = false
})

/* ─────────────────────────────────────────────────────────
   GLOBAL "GO TO POS" BUTTON VISIBILITY
───────────────────────────────────────────────────────── */
const isPosPage = computed(() => route.path.startsWith('/point-of-sale'))

/* ─────────────────────────────────────────────────────────
   DARK MODE — the toggle UI lives only on the Settings page now.
   Layout just needs to apply the persisted/preferred theme as soon as
   it mounts, so there's no flash of the wrong theme before Settings
   (or the composable's own system-preference listener) takes over.
───────────────────────────────────────────────────────── */
const { initDarkMode } = useDarkMode()

/* ─── LOADING ─────────────────────────────────────────────
   Two separate busy states now, instead of one overlay driving both:

   - `initializing`: true only until the very first /api/user check
     resolves. Drives the full-screen blocking overlay — appropriate
     here because there's genuinely nothing behind it to show yet.
   - `apiBusy`: drives the slim, non-blocking top progress bar for
     every request AFTER that point (page navigations, form submits,
     etc). It never dims or blocks the screen, so the app stays
     interactive and feels fast even while a request is in flight.

   apiBusy still uses a show-delay / hide-grace pair so quick requests
   don't flicker the bar on and off:
   - SHOW_DELAY: don't reveal the bar unless a request is still pending
     after this long — most calls finish well inside this window.
   - HIDE_GRACE: once shown, hold briefly after the last request settles
     so rapid sequential calls don't flash the bar on/off repeatedly.
─────────────────────────────────────────────────────────── */
const initializing = ref(true)
const apiBusy       = ref(false)
let requestCount     = 0
let showTimer        = null   // pending "reveal the bar" timer
let hideTimer        = null   // pending "hide the bar" grace timer

const SHOW_DELAY = 150  // ms a request must be pending before we show anything
const HIDE_GRACE = 120  // ms to hold the bar open after the last request settles

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
  if (!apiBusy.value) return // never actually shown, nothing to hide

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
  // Clamp so the 230px popup can't run off the right edge on narrow
  // screens (the sidebar drawer can sit close to the left edge, but
  // the popup itself still needs to fit within the viewport).
  const popupWidth = 230
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
   The kbd hint next to the search input now advertises a real global
   shortcut instead of just decorating the Enter key (which already had
   its own job: submitting the search). Pressing "/" anywhere outside
   a text field jumps focus straight into search, same convention as
   GitHub/Slack/etc. The hint itself hides once the field is focused,
   since at that point it's no longer something the user needs to be
   told about.
──────────────────────────────────────────────────────────── */
const searchInputRef = ref(null)
const searchFocused  = ref(false)

function isTypingTarget(el) {
  if (!el) return false
  const tag = el.tagName
  return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable
}

function onGlobalKeydown(e) {
  if (e.key !== '/') return
  if (e.metaKey || e.ctrlKey || e.altKey) return
  if (isTypingTarget(document.activeElement)) return
  e.preventDefault()
  searchInputRef.value?.focus()
}

/* ─── NOTIFICATIONS ───────────────────────────────────── */
const notifOpen      = ref(false)
const notifWrapRef   = ref(null)
// notifications / unreadCount now come from notificationsStore (see above)

// Where clicking a given notification type should take the user.
// Keyed the same way notifIcon() is, so both stay in sync — add a new
// notification type to both maps together if the backend adds one.
const NOTIF_ROUTES = {
  sale:              '/sales-transaction',
  low_stock:         '/products',
  out_of_stock:      '/products',
  supplier_delivery: '/suppliers',
  product_created:   '/products',
}

// low_stock / out_of_stock descriptions embed the product name, e.g.
// "rew has only 9 units left." or "rew is out of stock." — there's no
// dedicated product_id field on the notification payload, so we pull
// the name back out of the text and hand it to the Product List page
// as a query param, which matches it against ProductName to scroll to
// and highlight the right row. If the backend ever adds a proper
// product reference to notifications, swap this out for that instead —
// name-matching is a best-effort fallback, not bulletproof (won't
// survive duplicate product names or a rename after the alert fired).
function extractProductName(n) {
  const desc = n.description || ''
  let m = desc.match(/^(.*?) has (?:only )?\d+ units? left\.?$/i)
  if (m) return m[1].trim()
  m = desc.match(/^(.*?) is out of stock\.?$/i)
  if (m) return m[1].trim()
  return null
}

// fetchNotifications / markRead / markAllRead now live on notificationsStore
// (see stores/notifications.js) so they survive Layout remounting on
// every navigation. Thin local wrappers kept only where useful below.

/** Marks the notification read, closes the dropdown, then navigates
 *  wherever that notification type is relevant — a sale notification
 *  goes to Transactions, a stock alert goes to the Product List so the
 *  user can act on it immediately, and a new-product notification also
 *  goes to the Product List.
 *
 *  markRead() is intentionally NOT awaited here — it updates the
 *  notifications store instantly (see stores/notifications.js), and
 *  since that store is a singleton that survives navigation (unlike
 *  Layout.vue itself, which remounts on every route change), there's
 *  nothing left to race by navigating away immediately. Waiting on the
 *  network round trip here would only add a visible delay before the
 *  click does anything, for no correctness benefit.
 *
 *  For low_stock / out_of_stock alerts, we flip the Product List into
 *  its "Low Stock" filter and pass the product NAME (extracted from the
 *  description text) so that page can scroll to and highlight the exact
 *  row — these predate notifiable_id being populated on notifications.
 *
 *  For product_created and sale notifications, notifiable_id IS
 *  populated (see ProductController@store), so we pass that straight
 *  through as highlightId / highlightSale instead of guessing from
 *  text — Product List / Sales Transaction match it directly against
 *  ProductID / SaleID.
 *
 *  Unrecognized types just mark-as-read and close the dropdown without
 *  navigating anywhere. */
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
// waiting on the 30s poll — e.g. POS dispatches this right after a
// successful checkout so a new low/out-of-stock alert shows up instantly.
function onNotifyRefresh() { notificationsStore.fetchNotifications() }

// Pause background notification polling while the tab isn't visible —
// no point burning a request every 30s on a hidden/inactive tab. Polling
// itself is guarded inside the store (singleton), so even though this
// fires from every Layout instance's onMounted, it can't stack up
// duplicate timers across remounts.
function onVisibilityChange() {
  if (document.hidden) {
    notificationsStore.stopPolling()
  } else {
    notificationsStore.fetchNotifications() // catch up immediately on return
    notificationsStore.startPolling()
  }
}

/* ─── CLICK-OUTSIDE-TO-CLOSE (notif dropdown + account popup) ────
   Both popups were previously only closeable by clicking their own
   trigger button again. This adds a single document-level listener
   that closes whichever popup is open when a click lands outside of
   it (and outside its trigger). It's attached in the capture-less
   bubble phase, so it fires *after* the trigger's own @click handler
   has already toggled the value — that's what stops a click that
   just opened a popup from immediately closing it again on the same
   event.
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

  // Interceptors now respect a per-request `skipLoading` flag so silent
  // background calls (notification polling, read receipts) don't flash
  // the full-screen loading overlay. Regular navigational requests are
  // unaffected and still show the loader as before.
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
  document.addEventListener('click', onDocumentClick)

  // Ticks the topbar's greeting/date/clock. 30s is frequent enough that
  // the displayed minute is never stale for long, without generating
  // any meaningful re-render cost.
  clockTimer = setInterval(() => { now.value = new Date() }, 30_000)

  // Auth check + first notifications fetch run in parallel — they're
  // independent, so neither should wait on the other before starting.
  //
  // authStore.checkAuth() is a no-op network-wise if the router guard
  // already resolved auth for this session (see auth.ts: `initialized`
  // flag + in-flight de-dupe on fetchUser()). This is what stops
  // Layout.vue from firing its own separate /api/user call on top of
  // whatever the router guard already did — previously this page load
  // alone could trigger /api/user 2-3x.
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
  // Polling is NOT stopped here — it belongs to notificationsStore (a
  // singleton), not this Layout instance. Stopping it on every
  // navigation-triggered unmount would just make the next page's
  // Layout start it right back up, and briefly leaves no poller running
  // at all mid-navigation.
  document.removeEventListener('visibilitychange', onVisibilityChange)
  document.removeEventListener('click', onDocumentClick)
  window.removeEventListener('scroll', onScrollOrResizeUserMenu, true)
  window.removeEventListener('resize', onScrollOrResizeUserMenu)
  window.removeEventListener('resize', onWindowResize)
  window.removeEventListener('keydown', onGlobalKeydown)
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

/* ═══════════════════════════════════════════════════════
   SLIM TOP PROGRESS BAR — non-blocking busy indicator
════════════════════════════════════════════════════════ */
.top-progress-bar {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: 3px;
  z-index: 10000; /* above the sidebar/topbar, but never intercepts clicks */
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
   and becomes a full-height drawer pinned to the left edge, hidden by
   default (translated off-screen) and slid in when `sidebarOpen` is
   true. Always full width here — the icon-only collapsed state only
   makes sense when the sidebar shares space with page content. */
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

/* user-mini is now a <button> acting as the account-menu trigger */
.user-mini {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 10px; border-radius: 10px;
  overflow: hidden; white-space: nowrap; margin-top: 8px;
  background: var(--c-surface-raised);
  border: 1px solid var(--c-border);
  transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
  cursor: pointer;
  width: 100%;
  font-family: inherit;
  text-align: left;
}
.user-mini--center { justify-content: center; }
.user-mini:hover,
.user-mini--open   { background: var(--c-accent-soft); border-color: var(--c-accent-border); box-shadow: 0 0 0 3px var(--c-accent-ring); }

.user-avatar-wrap  { position: relative; flex-shrink: 0; }
.user-avatar-sm    { width: 32px; height: 32px; border-radius: 8px; object-fit: cover; display: block; }
.user-status-dot   { position: absolute; bottom: -1px; right: -1px; width: 9px; height: 9px; background: var(--c-online); border-radius: 50%; border: 2px solid var(--c-sidebar-bg); }

/* Vector-icon avatar — used for both the sidebar-mini and the
   teleported popup avatar (sizing/radius come from .user-avatar-sm /
   .profile-menu-avatar, this just adds the fill + centers the icon). */
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
.user-mini-name { font-size: 12.5px; font-weight: 600; color: var(--c-text-primary); line-height: 1.2; }
.user-mini-role { font-size: 10px; color: var(--c-accent); margin-top: 2px; font-weight: 600; letter-spacing: .03em; text-transform: uppercase; }

.user-mini-caret {
  font-size: 9px;
  color: var(--c-text-faint);
  transition: transform .2s ease;
  flex-shrink: 0;
}
.user-mini-caret--up { transform: rotate(180deg); }

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

.mobile-menu-btn { flex-shrink: 0; }

/* ─── GREETING ────────────────────────────────────────── */
.topbar-greeting {
  display: flex;
  align-items: center;
  gap: 11px;
  flex-shrink: 0;
  min-width: 0;
}

/* Stand-in for the sidebar's brand-logo while the sidebar is collapsed
   and can't show it itself — same sizing language as .brand-logo, just
   a touch larger since it's standing alone in the topbar. */
.greeting-logo-link {
  display: flex;
  flex-shrink: 0;
  border-radius: 9px;
  transition: transform 0.15s ease;
}
.greeting-logo-link:hover { transform: scale(1.06); }
.greeting-logo {
  width: 34px; height: 34px;
  object-fit: contain;
  border-radius: 9px;
  display: block;
}

.greeting-text { min-width: 0; }

.greeting-name {
  font-size: 14px; font-weight: 700;
  color: var(--c-text-primary);
  line-height: 1.2;
  white-space: nowrap;
  letter-spacing: -0.01em;
}
.greeting-name span { color: var(--c-accent); }

.greeting-sub {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; color: var(--c-text-faint);
  margin-top: 2px; white-space: nowrap; font-weight: 500;
}
.greeting-sep { opacity: 0.5; }
.greeting-clock {
  font-variant-numeric: tabular-nums;
  color: var(--c-text-muted);
  font-weight: 600;
}

.search-wrap { flex: 1; min-width: 0; max-width: 400px; margin: 0 auto; position: relative; }
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
.search-kbd {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  font-size: 10.5px; font-weight: 700;
  color: var(--c-text-faint);
  background: var(--c-border);
  border-radius: 5px;
  padding: 2px 7px;
  pointer-events: none;
  font-family: inherit;
}

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
  transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, transform 0.15s ease;
  position: relative;
  /* overflow: hidden removed — it was clipping the notif-badge, which
     is intentionally positioned half outside the button's box to sit
     on its corner. The button is a rounded square with no internal
     content that needs clipping, so this is safe to drop. */
}
.icon-btn:hover { background: var(--c-accent-soft); border-color: var(--c-accent-border); color: var(--c-accent); box-shadow: 0 0 0 3px var(--c-accent-ring); }

/* Notification count badge — replaces the old plain dot with an actual
   number (capped at "9+") so it's a triage signal, not just a "something
   happened" flag. */
.notif-badge {
  position: absolute;
  top: -4px; right: -4px;
  min-width: 17px; height: 17px;
  padding: 0 4px;
  display: flex; align-items: center; justify-content: center;
  background: var(--c-accent);
  color: #fff;
  font-size: 10px; font-weight: 700;
  line-height: 1;
  border-radius: 999px;
  border: 2px solid var(--c-surface);
  box-shadow: var(--c-shadow-accent);
}

/* ─── NOTIFICATIONS ───────────────────────────────────── */
.notif-wrap { position: relative; }

.notif-menu {
  position: absolute; right: 0; top: calc(100% + 10px);
  width: 320px; max-width: calc(100vw - 24px); max-height: 420px;
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
  padding: 8px 10px 10px;
}
.notif-menu-title { font-size: 13.5px; font-weight: 700; color: var(--c-text-primary); }
.notif-mark-all {
  background: none; border: none; cursor: pointer; font-family: inherit;
  font-size: 11px; font-weight: 600; color: var(--c-accent);
  padding: 3px 6px; border-radius: 6px;
  transition: background-color .15s ease;
}
.notif-mark-all:hover { background: var(--c-accent-soft); }

.notif-list { list-style: none; margin: 0; padding: 0; overflow-y: auto; }

.notif-item {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 10px; border-radius: 10px; cursor: pointer; position: relative;
  transition: background-color .15s ease;
}
/* :not(.notif-item--unread) means these two hover rules can never both
   apply to the same element — there's no specificity race to get wrong.
   An unread item is only ever matched by the second rule below. */
.notif-item:not(.notif-item--unread):hover { background: var(--c-surface-raised); }
.notif-item--unread { background: var(--c-accent-soft) !important; }
/* FIX: was `background: var(--c-accent-soft) !important; filter: brightness(1.05);`
   --c-accent-soft is a near-white SOLID color in light mode (#FFF7ED), so
   brightness(1.05) clipped it to pure white on hover — that was the bug.
   In dark mode --c-accent-soft is a low-alpha translucent overlay, so the
   same filter looked fine there, which is why this only showed up in light
   mode. Fixed by pointing at a dedicated, deliberately-tinted hover token
   (--c-accent-soft-hover, defined per-theme in theme.css) instead of trying
   to derive the hover color from --c-accent-soft with a filter. */
.notif-item--unread:hover { background: var(--c-accent-soft-hover) !important; }

.notif-item-icon {
  width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; margin-top: 1px;
  background: var(--c-surface-sunken); color: var(--c-text-muted);
}
.notif-item-icon--low_stock,
.notif-item-icon--out_of_stock       { background: var(--c-danger-soft);   color: var(--c-danger); }
.notif-item-icon--sale               { background: var(--c-accent-soft);   color: var(--c-accent); }
.notif-item-icon--supplier_delivery  { background: var(--c-surface-sunken); color: var(--c-text-secondary); }
/* New-product notifications get their own green (not accent/red) so
   they read as a positive "something was added" event, distinct from
   both sale activity (accent) and stock alerts (danger). */
.notif-item-icon--product_created    { background: rgba(16,185,129,0.12); color: #10b981; }
html[data-theme="dark"] .notif-item-icon--product_created { background: rgba(16,185,129,0.18); color: #4ADE80; }

.notif-item-body { flex: 1; min-width: 0; }
.notif-item-title { font-size: 12.5px; font-weight: 600; color: var(--c-text-primary); line-height: 1.3; }
.notif-item-desc  { font-size: 11.5px; color: var(--c-text-muted); margin-top: 2px; line-height: 1.4; }
.notif-item-time  { font-size: 10px; color: var(--c-text-faint); margin-top: 4px; font-weight: 500; }

.notif-item-dot {
  width: 7px; height: 7px; border-radius: 50%; background: var(--c-accent);
  flex-shrink: 0; margin-top: 6px;
}

.notif-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 32px 10px; color: var(--c-text-faint); gap: 8px;
}
.notif-empty i { font-size: 22px; }
.notif-empty p { font-size: 12.5px; font-weight: 500; }

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
  padding: 16px;
}
.modal {
  background: var(--c-surface-overlay);
  border-radius: 20px;
  padding: 36px 32px;
  width: 360px;
  max-width: 100%;
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
.btn-ghost:disabled { opacity: 0.55; cursor: not-allowed; pointer-events: none; }

.btn-danger {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 9px 22px; border: none; border-radius: 9px;
  background: linear-gradient(135deg, var(--c-danger) 0%, var(--c-danger-deep) 100%);
  font-size: 13.5px; font-weight: 600; color: #fff;
  cursor: pointer; font-family: inherit;
  box-shadow: var(--c-shadow-danger);
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.btn-danger:hover { opacity: .9; transform: translateY(-1px); }
.btn-danger:disabled {
  opacity: 0.75; cursor: not-allowed; pointer-events: none; transform: none;
}
/* min-width keeps the button from visibly resizing when the label
   switches between "Sign Out" and "Signing out…" */
.btn-danger { min-width: 108px; }

/* ─── TRANSITIONS ─────────────────────────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity .22s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }
.dropdown-enter-active { transition: opacity .18s ease, transform .18s ease; }
.dropdown-leave-active { transition: opacity .14s ease, transform .14s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px) scale(.97); }

/* ═══════════════════════════════════════════════════════
   RESPONSIVE — TABLET (≤1024px)
   Content gets tight next to a full 260px sidebar at this width, so
   trim the topbar's breathing room a little before mobile kicks in.
════════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .topbar { padding: 0 18px; gap: 12px; }
  .search-wrap { max-width: 280px; }
  .page-content { padding: 22px; }
}

/* ═══════════════════════════════════════════════════════
   RESPONSIVE — MOBILE (≤768px)
   Sidebar is now an off-canvas drawer (see .sidebar--mobile above);
   the topbar sheds secondary text and shrinks controls to fit.
════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
  .topbar { height: 58px; min-height: 58px; padding: 0 14px; gap: 10px; }
  .greeting-sub { display: none; }
  .greeting-name { font-size: 13px; }
  .greeting-logo { width: 28px; height: 28px; border-radius: 8px; }
  .search-kbd { display: none; }
  .search-wrap { max-width: none; }
  .search-input { padding: 0 14px 0 34px; }
  /* Overrides the v-show="sidebarOpen" inline style, which on mobile
     tracks the drawer rather than available topbar width — the label
     text isn't useful once the button is icon-sized regardless. */
  .action-btn--primary span { display: none; }
  .action-btn--primary { padding: 0 10px; }
  .page-content { padding: 16px; }
  .content-inner { max-width: 100%; }
}

@media (max-width: 640px) {
  .topbar-greeting { display: none; }
}

@media (max-width: 400px) {
  .modal { padding: 28px 20px; }
  .notif-menu { right: -8px; }
}
</style>

<style>
/* Unscoped — the account popup is teleported to <body>, outside this
   component's scoped attribute, so scoped styles wouldn't reach it
   (same pattern as the flyout submenu in SidebarDropdown.vue). */
.user-popup-menu {
  position: fixed;
  width: 230px;
  max-width: calc(100vw - 20px);
  background: var(--c-surface-overlay);
  border: 1px solid var(--c-border);
  border-radius: 14px;
  box-shadow: var(--c-shadow-lg);
  z-index: 9999; padding: 6px;
}
.user-popup-header { display: flex; align-items: center; gap: 10px; padding: 10px 10px 12px; }
.pmenu-avatar-wrap   { position: relative; flex-shrink: 0; }
.profile-menu-avatar { width: 38px; height: 38px; border-radius: 9px; object-fit: cover; display: block; }
.pmenu-status-dot    { position: absolute; bottom: -1px; right: -1px; width: 9px; height: 9px; background: var(--c-online); border-radius: 50%; border: 2px solid var(--c-surface-overlay); }
.profile-menu-name   { font-size: 13px; font-weight: 700; color: var(--c-text-primary); }
.profile-menu-email  { font-size: 11px; color: var(--c-text-faint); margin-top: 1px; }
</style>