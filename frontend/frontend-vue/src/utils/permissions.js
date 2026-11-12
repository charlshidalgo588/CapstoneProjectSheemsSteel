// Single source of truth for who can open which page.
// Mirror any change in backend/routes/api.php.
export const ROLES = {
  ADMIN: 'Administrator',
  CASHIER: 'Cashier',
  INVENTORY: 'Inventory Staff',
}

// Every section that is role-restricted. A path that is not inside one of
// these (e.g. /login, set-initial-password, 404) is never blocked here.
const SECTIONS = [
  '/home',
  '/point-of-sale',
  '/sales-transaction',
  '/sales',            // single receipt page: /sales/:id
  '/customers',
  '/products',         // list + /products/create + product pages
  '/categories',
  '/suppliers',
  '/inventory-logs',
  '/reports',
  '/settings',
]

const ACCESS = {
  [ROLES.CASHIER]: [
    '/home', '/point-of-sale', '/sales-transaction', '/sales', '/customers', '/settings',
  ],
  [ROLES.INVENTORY]: [
    '/home', '/products', '/categories', '/suppliers', '/inventory-logs', '/settings',
  ],
}

const matches = (path, prefix) => path === prefix || path.startsWith(prefix + '/')

export function canAccess(role, path) {
  if (role === ROLES.ADMIN) return true

  const restricted = SECTIONS.some((s) => matches(path, s))
  if (!restricted) return true

  const allowed = ACCESS[role]
  if (!allowed) return false
  return allowed.some((p) => matches(path, p))
}