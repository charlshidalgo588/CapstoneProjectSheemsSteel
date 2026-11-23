<template>
  <!-- One router-link (custom) that renders a plain <a>. The Layout's
       tooltip reads aria-current to show "You're here", so it is set
       by hand below. -->
  <router-link
    :to="to"
    custom
    v-slot="{ href, navigate, isActive, isExactActive }"
  >
    <a
      :href="href"
      class="nav-link-inner"
      :class="{
        'is-active': active(isActive, isExactActive),
        'menu-item': menuItem,
        'collapsed': collapsed && !menuItem,
      }"
      :aria-current="active(isActive, isExactActive) ? 'page' : undefined"
      :aria-label="collapsed && !menuItem ? label : undefined"
      :title="collapsed && !menuItem ? label : undefined"
      @click="navigate"
    >
      <span class="nav-icon-wrap">
        <i :class="['nav-icon', icon]"></i>
      </span>

      <!-- Always rendered (hidden with opacity) so the label can fade
           while the sidebar width animates, instead of popping. -->
      <span class="nav-label" :aria-hidden="collapsed && !menuItem">{{ label }}</span>

      <span
        v-if="!menuItem"
        class="nav-active-pip"
        :class="{ 'nav-active-pip--on': active(isActive, isExactActive) && !collapsed }"
      ></span>
    </a>
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
  methods: {
    // Dropdown items need an EXACT match (so "Product List" is not active
    // on /products/create). Top-level links also count their sub-pages.
    active(isActive, isExactActive) {
      return this.menuItem ? isExactActive : isActive
    },
  },
}
</script>

<style scoped>
.nav-link-inner {
  display: flex;
  align-items: center;
  gap: 10px;
  /* 9px left keeps the icon centred in the 68px collapsed sidebar, so
     the icon does not shift when the sidebar opens or closes. */
  padding: 8px 10px 8px 9px;
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

/* Hover only on devices that can really hover. Touch screens (and mobile
   emulation) otherwise keep the last tapped item stuck in the hover state. */
@media (hover: hover) {
  .nav-link-inner:hover {
    background: var(--c-accent-soft);
    color: var(--c-text-primary);
  }
  .nav-link-inner:hover .nav-icon-wrap {
    background: var(--c-accent-border);
  }
}

/* Active comes AFTER hover, so it always wins */
.nav-link-inner.is-active,
.nav-link-inner.is-active:hover {
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
.nav-link-inner.is-active .nav-icon-wrap,
.nav-link-inner.is-active:hover .nav-icon-wrap {
  background: var(--c-accent);
}
.nav-icon {
  font-size: 12.5px;
  color: inherit;
  transition: color .16s ease;
}
.nav-link-inner.is-active .nav-icon { color: #fff; }

/* Label: fades in a little AFTER the width starts opening, and out fast
   when collapsing. */
.nav-label {
  flex: 1;
  line-height: 1;
  opacity: 1;
  transition: opacity .2s ease .1s;
}
.nav-link-inner.collapsed .nav-label {
  opacity: 0;
  transition: opacity .08s ease;
}

/* Active pip (the single indicator bar, top-level links only) */
.nav-active-pip {
  width: 3px;
  height: 16px;
  border-radius: 2px;
  background: var(--c-accent);
  flex-shrink: 0;
  opacity: 0;
  transition: opacity .18s ease;
}
.nav-active-pip--on { opacity: 1; }

/* Sub-nav (dropdown items) */
.nav-link-inner.menu-item {
  padding: 6px 10px 6px 14px;
  font-size: 12.5px;
  color: var(--c-text-faint);
  border-radius: 7px;
}
.nav-link-inner.menu-item.is-active,
.nav-link-inner.menu-item.is-active:hover {
  color: var(--c-accent);
  font-weight: 600;
  background: var(--c-accent-soft);
}
.nav-link-inner.menu-item .nav-icon-wrap {
  width: 22px;
  height: 22px;
  border-radius: 5px;
}
@media (hover: hover) {
  .nav-link-inner.menu-item:hover {
    background: var(--c-accent-soft);
    color: var(--c-text-primary);
  }
}

.nav-link-inner:focus-visible {
  outline: 2px solid var(--c-accent);
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .nav-link-inner, .nav-icon-wrap, .nav-icon, .nav-label, .nav-active-pip {
    transition-duration: .01ms !important;
    transition-delay: 0s !important;
  }
}
</style>