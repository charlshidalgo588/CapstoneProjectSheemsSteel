<template>
  <router-link
    :to="to"
    class="nav-link"
    :class="{ 'nav-link--menu': menuItem, 'nav-link--collapsed': collapsed && !menuItem }"
    v-slot="{ isActive }"
    custom
  >
    <component
      :is="'router-link'"
      :to="to"
      class="nav-link-inner"
      :class="{
        'is-active': isActive,
        'menu-item': menuItem,
        'collapsed': collapsed && !menuItem,
      }"
      :title="collapsed && !menuItem ? label : undefined"
    >
      <span class="nav-icon-wrap" :class="{ 'icon-active': isActive }">
        <i :class="['nav-icon', icon]"></i>
      </span>
      <span v-if="!collapsed || menuItem" class="nav-label">{{ label }}</span>
      <span v-if="isActive && !collapsed && !menuItem" class="nav-active-pip"></span>
      <span v-if="collapsed && !menuItem" class="nav-tooltip">{{ label }}</span>
    </component>
  </router-link>
</template>

<script>
export default {
  props: {
    to:        { type: String,  required: true },
    label:     { type: String,  required: true },
    icon:      { type: String,  required: true },
    menuItem:  { type: Boolean, default: false },
    collapsed: { type: Boolean, default: false },
  },
}
</script>

<style scoped>
.nav-link { display: block; }

.nav-link-inner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 9px;
  text-decoration: none;
  color: var(--c-text-muted);
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  transition: background-color .16s ease, color .16s ease;
  position: relative;
}
.nav-link-inner:hover {
  background: var(--c-accent-soft);
  color: var(--c-text-primary);
}
.nav-link-inner.is-active {
  background: var(--c-accent-soft);
  color: var(--c-accent);
  font-weight: 600;
}

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
.nav-link-inner:hover .nav-icon-wrap {
  background: var(--c-accent-border);
}
.nav-link-inner.is-active .nav-icon-wrap {
  background: var(--c-accent);
}
.nav-icon {
  font-size: 12.5px;
  color: inherit;
  transition: color .16s ease;
}
.nav-link-inner.is-active .nav-icon { color: #fff; }

/* Label */
.nav-label { flex: 1; line-height: 1; }

/* Active pip */
.nav-active-pip {
  width: 3px;
  height: 16px;
  border-radius: 2px;
  background: var(--c-accent);
  flex-shrink: 0;
}

/* Sub-nav */
.nav-link-inner.menu-item {
  padding: 6px 10px 6px 14px;
  font-size: 12.5px;
  color: var(--c-text-faint);
  border-radius: 7px;
}
.nav-link-inner.menu-item:hover {
  background: var(--c-accent-soft);
  color: var(--c-text-primary);
}
.nav-link-inner.menu-item.is-active {
  color: var(--c-accent);
  font-weight: 600;
  background: var(--c-accent-soft);
}
.nav-link-inner.menu-item .nav-icon-wrap {
  width: 22px;
  height: 22px;
  border-radius: 5px;
}

/* Collapsed */
.nav-link-inner.collapsed { justify-content: center; padding: 9px; }

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
  transition: opacity .15s ease, transform .15s ease;
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
.nav-link-inner.collapsed:hover .nav-tooltip {
  opacity: 1;
  transform: translateY(-50%) translateX(2px);
}
</style>