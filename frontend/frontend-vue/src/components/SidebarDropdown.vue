<template>
  <div class="dropdown-wrap">
    <button
      ref="triggerRef"
      class="dropdown-trigger"
      :class="{ 'is-open': open, 'is-collapsed': collapsed }"
      @click="handleClick"
      @mouseenter="handleMouseEnter"
      @mouseleave="handleMouseLeave"
      :title="collapsed ? label : undefined"
    >
      <span class="nav-icon-wrap" :class="{ 'icon-open': open }">
        <i :class="['nav-icon', icon]"></i>
      </span>
      <template v-if="!collapsed">
        <span class="dropdown-label">{{ label }}</span>
        <i class="fa-solid fa-chevron-down dropdown-caret" :class="{ rotated: open }"></i>
      </template>
    </button>

    <!-- Inline submenu (expanded sidebar) -->
    <transition name="submenu">
      <ul v-if="open && !collapsed" class="submenu">
        <slot />
      </ul>
    </transition>

    <!-- Flyout submenu (collapsed sidebar) — teleported so sidebar-nav's
         overflow:hidden / overflow-x:hidden can't clip it -->
    <Teleport to="body">
      <transition name="flyout-fade">
        <div
          v-if="collapsed && flyoutVisible"
          class="flyout"
          :style="flyoutStyle"
          @mouseenter="handleMouseEnter"
          @mouseleave="handleMouseLeave"
        >
          <div class="flyout-label">{{ label }}</div>
          <ul class="flyout-submenu">
            <slot />
          </ul>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps({
  label:     { type: String,  required: true },
  icon:      { type: String,  required: true },
  open:      { type: Boolean, default: false },
  collapsed: { type: Boolean, default: false },
})
const emit = defineEmits(['toggle'])

const triggerRef    = ref(null)
const hoverVisible   = ref(false)
const coords        = ref({ top: 0, left: 0 })

// Flyout shows either while hovering, or while `open` is true (so a click
// keeps it pinned even if the mouse moves away from the trigger).
const flyoutVisible = computed(() => hoverVisible.value || props.open)

const flyoutStyle = computed(() => ({
  top:  coords.value.top + 'px',
  left: coords.value.left + 'px',
}))

function measure() {
  const el = triggerRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  coords.value = {
    top:  rect.top,
    left: rect.right + 14, // matches the 14px gap used elsewhere
  }
}

function handleClick() {
  measure()
  emit('toggle')
}

function handleMouseEnter() {
  if (!props.collapsed) return
  measure()
  hoverVisible.value = true
}

function handleMouseLeave() {
  hoverVisible.value = false
}

// Re-measure if the trigger moves (e.g. sidebar items reordering) while open
watch(() => props.open, async (val) => {
  if (val) { await nextTick(); measure() }
})

function onScrollOrResize() {
  if (flyoutVisible.value) measure()
}
window.addEventListener('scroll', onScrollOrResize, true)
window.addEventListener('resize', onScrollOrResize)
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScrollOrResize, true)
  window.removeEventListener('resize', onScrollOrResize)
})
</script>

<style scoped>
.dropdown-wrap { display: flex; flex-direction: column; }

.dropdown-trigger {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 10px;
  border: none;
  border-radius: 9px;
  background: none;
  color: var(--c-text-muted);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  transition: background-color .16s ease, color .16s ease;
  position: relative;
  font-family: inherit;
}
.dropdown-trigger:hover {
  background: var(--c-accent-soft);
  color: var(--c-text-primary);
}
.dropdown-trigger.is-open {
  color: var(--c-text-primary);
  background: var(--c-accent-soft);
}
.dropdown-trigger.is-collapsed { justify-content: center; padding: 9px; }

.nav-icon-wrap {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 7px;
  background: transparent;
  flex-shrink: 0;
  transition: background-color .16s ease;
}
.dropdown-trigger:hover .nav-icon-wrap,
.dropdown-trigger.is-open .nav-icon-wrap {
  background: var(--c-accent-border);
}

.nav-icon { font-size: 12.5px; }

.dropdown-label { flex: 1; }
.dropdown-caret {
  font-size: 9px;
  color: var(--c-text-faint);
  transition: transform .2s ease, color .16s ease;
  flex-shrink: 0;
}
.dropdown-caret.rotated { transform: rotate(180deg); }

/* Inline submenu (expanded sidebar) */
.submenu {
  list-style: none;
  margin: 2px 0 2px 0;
  padding: 2px 0 2px 14px;
  display: flex;
  flex-direction: column;
  gap: 1px;
  position: relative;
}
.submenu::before {
  content: '';
  position: absolute;
  left: 24px;
  top: 6px;
  bottom: 6px;
  width: 1.5px;
  background: linear-gradient(to bottom, var(--c-accent-border), var(--c-accent-soft));
  border-radius: 2px;
}

.submenu-enter-active {
  transition: opacity .18s ease, max-height .22s ease;
  max-height: 300px;
  overflow: hidden;
}
.submenu-leave-active {
  transition: opacity .14s ease, max-height .18s ease;
  overflow: hidden;
}
.submenu-enter-from, .submenu-leave-to { opacity: 0; max-height: 0; }
</style>

<style>
/* Unscoped — this is teleported to <body>, outside this component's
   scoped attribute, so scoped styles wouldn't apply to it anyway. */
.flyout {
  position: fixed;
  min-width: 180px;
  background: var(--c-surface-overlay, var(--c-bg));
  border: 1px solid var(--c-accent-border);
  border-radius: 10px;
  box-shadow: var(--c-shadow-lg);
  padding: 8px;
  z-index: 9999;
}
.flyout-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--c-text-primary);
  padding: 4px 8px 8px;
  border-bottom: 1px solid var(--c-accent-soft);
  margin-bottom: 4px;
  white-space: nowrap;
}
.flyout-submenu {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.flyout-fade-enter-active { transition: opacity .15s ease, transform .15s ease; }
.flyout-fade-leave-active { transition: opacity .12s ease, transform .12s ease; }
.flyout-fade-enter-from, .flyout-fade-leave-to {
  opacity: 0;
  transform: translateX(-6px);
}
</style>