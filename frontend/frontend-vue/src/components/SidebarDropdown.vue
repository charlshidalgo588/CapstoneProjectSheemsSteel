<template>
  <div class="dropdown-wrap">
    <button
      class="dropdown-trigger"
      :class="{ 'is-open': open, 'is-collapsed': collapsed }"
      @click="$emit('toggle')"
      :title="collapsed ? label : undefined"
    >
      <span class="nav-icon-wrap" :class="{ 'icon-open': open }">
        <i :class="['nav-icon', icon]"></i>
      </span>
      <template v-if="!collapsed">
        <span class="dropdown-label">{{ label }}</span>
        <i class="fa-solid fa-chevron-down dropdown-caret" :class="{ rotated: open }"></i>
      </template>
      <span v-if="collapsed" class="nav-tooltip">{{ label }}</span>
    </button>

    <transition name="submenu">
      <ul v-if="open && !collapsed" class="submenu">
        <slot />
      </ul>
    </transition>
  </div>
</template>

<script>
export default {
  props: {
    label:     { type: String,  required: true },
    icon:      { type: String,  required: true },
    open:      { type: Boolean, default: false },
    collapsed: { type: Boolean, default: false },
  },
  emits: ['toggle'],
}
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

/* Icon wrap */
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

/* Label & caret */
.dropdown-label { flex: 1; }
.dropdown-caret {
  font-size: 9px;
  color: var(--c-text-faint);
  transition: transform .2s ease, color .16s ease;
  flex-shrink: 0;
}
.dropdown-caret.rotated { transform: rotate(180deg); }

/* Submenu */
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

/* Tooltip */
.nav-tooltip {
  position: absolute;
  left: calc(100% + 14px);
  top: 50%;
  transform: translateY(-50%);
  background: var(--c-text-primary);
  color: var(--c-bg);
  font-size: 12px;
  font-weight: 500;
  padding: 5px 10px;
  border-radius: 7px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity .15s ease;
  box-shadow: var(--c-shadow-lg);
  z-index: 999;
}
.nav-tooltip::before {
  content: '';
  position: absolute;
  right: 100%;
  top: 50%;
  transform: translateY(-50%);
  border: 5px solid transparent;
  border-right-color: var(--c-text-primary);
}
.dropdown-trigger.is-collapsed:hover .nav-tooltip { opacity: 1; }

/* Transition */
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