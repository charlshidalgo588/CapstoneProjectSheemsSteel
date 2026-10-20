import { ref, type Ref } from 'vue'

export type ThemeMode = 'light' | 'dark' | 'system'

const MODE_KEY = 'sheem-theme-mode'        // 'light' | 'dark' | 'system'
const LEGACY_DARK_KEY = 'sheem-dark-mode'  // old boolean flag — migrated on first read

// Module-level singleton — every component that calls useDarkMode() shares
// the same reactive state, so toggling it from Settings (or anywhere else)
// stays in sync with the rest of the app without prop-drilling or events.
const themeMode: Ref<ThemeMode> = ref('system')  // what the user picked
const darkMode: Ref<boolean> = ref(false)        // resolved boolean actually applied to the DOM

let mql: MediaQueryList | null = null
let unfreezeRaf = 0 // guards against rapid toggles removing the freeze class too early

function systemPrefersDark(): boolean {
  return window.matchMedia('(prefers-color-scheme: dark)').matches
}

function resolveDark(mode: ThemeMode): boolean {
  if (mode === 'dark') return true
  if (mode === 'light') return false
  return systemPrefersDark()
}

/**
 * Applies the theme in a single paint. Transitions are frozen while the
 * data-theme attribute flips, so every element changes at the same moment
 * instead of some fading over .22s and others snapping instantly (which is
 * what made the switch look delayed). They are re-enabled two frames later,
 * so hover effects, dropdowns, etc. animate normally afterwards.
 */
function applyTheme(isDark: boolean): void {
  const root = document.documentElement
  const next = isDark ? 'dark' : 'light'
  if (root.getAttribute('data-theme') === next) return // nothing to change

  cancelAnimationFrame(unfreezeRaf)
  root.classList.add('theme-switching')
  root.setAttribute('data-theme', next)

  void root.offsetHeight // force a reflow so the new styles apply with transitions off

  unfreezeRaf = requestAnimationFrame(() => {
    unfreezeRaf = requestAnimationFrame(() => {
      root.classList.remove('theme-switching')
    })
  })
}

function sync(): void {
  darkMode.value = resolveDark(themeMode.value)
  applyTheme(darkMode.value)
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

  sync()

  // Keep "System" mode live if the OS theme changes while the app is open.
  if (!mql) {
    mql = window.matchMedia('(prefers-color-scheme: dark)')
    mql.addEventListener('change', () => {
      if (themeMode.value === 'system') sync()
    })
  }
}

// Explicit 3-way picker — this is what the Settings page uses.
function setThemeMode(mode: ThemeMode): void {
  if (!['light', 'dark', 'system'].includes(mode)) return
  themeMode.value = mode
  localStorage.setItem(MODE_KEY, mode)
  localStorage.removeItem(LEGACY_DARK_KEY)
  sync()
}

// Kept for any existing call sites (e.g. a quick topbar toggle) — flips
// explicitly between light/dark, same as picking one of those two by hand.
function toggleDark(): void {
  setThemeMode(darkMode.value ? 'light' : 'dark')
}

export function useDarkMode() {
  return { darkMode, themeMode, toggleDark, setThemeMode, initDarkMode }
}