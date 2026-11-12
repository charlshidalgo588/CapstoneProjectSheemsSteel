import { ref, type Ref } from 'vue'

export type ThemeMode = 'light' | 'dark' | 'system'

const MODE_KEY = 'sheem-theme-mode'        // 'light' | 'dark' | 'system'
const LEGACY_DARK_KEY = 'sheem-dark-mode'  // old boolean flag — migrated on first read

// How long the light ↔ dark cross-fade lasts. Keep in sync with the CSS
// (::view-transition-* and .theme-fade) in theme.css.
const THEME_FADE_MS = 320

// Module-level singleton — every component that calls useDarkMode() shares
// the same reactive state, so toggling it from Settings (or anywhere else)
// stays in sync with the rest of the app without prop-drilling or events.
const themeMode: Ref<ThemeMode> = ref('system')  // what the user picked
const darkMode: Ref<boolean> = ref(false)        // resolved boolean actually applied to the DOM

let mql: MediaQueryList | null = null
let unfreezeRaf = 0                                   // guards the instant-flip path
let fadeTimer: ReturnType<typeof setTimeout> | null = null // guards the fallback fade path

function systemPrefersDark(): boolean {
  return window.matchMedia('(prefers-color-scheme: dark)').matches
}

function resolveDark(mode: ThemeMode): boolean {
  if (mode === 'dark') return true
  if (mode === 'light') return false
  return systemPrefersDark()
}

function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

/**
 * Applies the theme.
 *
 * animate = false  (first load)
 *   Instant. Transitions are frozen while data-theme flips so nothing
 *   fades or flashes on page load.
 *
 * animate = true   (user toggled the theme)
 *   One unified cross-fade for the WHOLE page, so every element changes
 *   together instead of each fading on its own timing:
 *     1. View Transitions API (Chrome, Edge, Safari, new Firefox) —
 *        the browser snapshots the old page, flips the theme, and fades
 *        between the two snapshots. Handles gradients, images, teleported
 *        modals, everything.
 *     2. Fallback (older browsers) — a temporary `.theme-fade` class gives
 *        every element the same colour transition duration.
 *   Users who prefer reduced motion always get the instant flip.
 */
function applyTheme(isDark: boolean, animate = false): void {
  const root = document.documentElement
  const next = isDark ? 'dark' : 'light'
  if (root.getAttribute('data-theme') === next) return // nothing to change

  const flip = () => root.setAttribute('data-theme', next)
  const canAnimate = animate && !prefersReducedMotion()

  // 1) View Transitions API
  const startVT = (document as any).startViewTransition as
    | ((cb: () => void) => { finished: Promise<void> })
    | undefined

  if (canAnimate && typeof startVT === 'function') {
    cancelAnimationFrame(unfreezeRaf)
    if (fadeTimer) { clearTimeout(fadeTimer); fadeTimer = null }
    root.classList.remove('theme-fade')

    // Per-component CSS transitions must be off while the snapshot is
    // taken, otherwise the "new" snapshot is captured mid-transition.
    root.classList.add('theme-switching')
    const vt = startVT.call(document, flip)
    vt.finished.finally(() => root.classList.remove('theme-switching'))
    return
  }

  // 2) Fallback cross-fade: same duration on every element
  if (canAnimate) {
    cancelAnimationFrame(unfreezeRaf)
    root.classList.remove('theme-switching')
    root.classList.add('theme-fade')
    flip()

    if (fadeTimer) clearTimeout(fadeTimer)
    fadeTimer = setTimeout(() => {
      root.classList.remove('theme-fade')
      fadeTimer = null
    }, THEME_FADE_MS + 60)
    return
  }

  // 3) Instant flip (first load / reduced motion): freeze transitions so
  //    every element changes in the same paint.
  cancelAnimationFrame(unfreezeRaf)
  root.classList.add('theme-switching')
  flip()

  void root.offsetHeight // force a reflow so the new styles apply with transitions off

  unfreezeRaf = requestAnimationFrame(() => {
    unfreezeRaf = requestAnimationFrame(() => {
      root.classList.remove('theme-switching')
    })
  })
}

function sync(animate = false): void {
  darkMode.value = resolveDark(themeMode.value)
  applyTheme(darkMode.value, animate)
}

function initDarkMode(): void {
  const stored = localStorage.getItem(MODE_KEY)
  if (stored === 'light' || stored === 'dark' || stored === 'system') {
    themeMode.value = stored
  } else {
    // Migrate the old explicit boolean flag if that's all we have on disk.
    const legacy = localStorage.getItem(LEGACY_DARK_KEY)
    themeMode.value = legacy !== null ? (legacy === 'true' ? 'dark' : 'light') : 'system'
  }

  sync(false) // never animate the very first paint

  // Keep "System" mode live if the OS theme changes while the app is open.
  if (!mql) {
    mql = window.matchMedia('(prefers-color-scheme: dark)')
    mql.addEventListener('change', () => {
      if (themeMode.value === 'system') sync(true)
    })
  }
}

// Explicit 3-way picker — this is what the Settings page uses.
function setThemeMode(mode: ThemeMode): void {
  if (!['light', 'dark', 'system'].includes(mode)) return
  themeMode.value = mode
  localStorage.setItem(MODE_KEY, mode)
  localStorage.removeItem(LEGACY_DARK_KEY)
  sync(true)
}

// Kept for any existing call sites (e.g. a quick topbar toggle) — flips
// explicitly between light/dark, same as picking one of those two by hand.
function toggleDark(): void {
  setThemeMode(darkMode.value ? 'light' : 'dark')
}

export function useDarkMode() {
  return { darkMode, themeMode, toggleDark, setThemeMode, initDarkMode }
}