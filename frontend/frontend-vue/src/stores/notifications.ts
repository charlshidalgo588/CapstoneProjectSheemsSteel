// src/stores/notifications.ts
//
// Notification state used to live as local refs inside Layout.vue. That
// broke "mark as read" in a way that was hard to see: Layout.vue is
// imported fresh into every page component rather than mounted once at
// the app root, so every navigation destroys the old Layout instance
// (and its notification state) and mounts a brand new one, which
// immediately re-fetches from the backend. If that fetch resolved
// before (or raced) the "mark as read" request finishing on the
// server, the notification would appear to un-read itself.
//
// Moving this into a Pinia store fixes it at the root: the store is a
// singleton for the whole app session (same pattern as useAuthStore),
// so marking something read updates state that survives navigation
// with zero dependency on network timing. fetchNotifications() also
// MERGES with what we already know locally instead of blindly
// overwriting, so even a slow/late backend response can never flip a
// notification back to unread once we've marked it read client-side.
import { defineStore } from 'pinia'
import api from '@/api/axios'

// Explicit shape for a notification. This is what fixes the `never`
// cascade in the Problems panel: with `notifications: []` and no
// annotation, TS infers `never[]` for the array, so every property
// access on an element (n.read_at, n.id, ...) fails with
// "Property 'x' does not exist on type 'never'". Giving the state
// function a return type (see NotificationsState below) fixes all of
// those at once instead of one at a time.
export interface AppNotification {
  id: number | string
  type: 'sale' | 'low_stock' | 'out_of_stock' | 'supplier_delivery' | 'product_created' | string
  title: string
  description: string
  created_at: string
  read_at: string | null
  notifiable_id?: number | string | null
}

interface NotificationsState {
  notifications: AppNotification[]
  unreadCount: number
  initialized: boolean
  // setInterval's return type differs between browser (number) and
  // Node/@types/node (NodeJS.Timeout) — ReturnType<typeof setInterval>
  // resolves to whichever is correct for your configured lib/tsconfig,
  // so this doesn't need to be hardcoded either way. This is also what
  // fixes the "Type 'number' is not assignable to type 'null'" error:
  // `pollTimer: null` alone infers the literal type `null`, which then
  // rejects any later assignment of an actual timer handle.
  pollTimer: ReturnType<typeof setInterval> | null
}

export const useNotificationsStore = defineStore('notifications', {
  state: (): NotificationsState => ({
    notifications: [],
    unreadCount: 0,
    initialized: false,
    pollTimer: null,
  }),

  actions: {
    /** Fetches the latest notifications from the backend and merges them
     *  with what we already know locally. Any notification we've
     *  already marked read on this client (optimistically) stays read
     *  in the merged result even if this particular fetch reflects a
     *  backend state that hasn't caught up yet — we never let a fetch
     *  regress something back to unread. */
    async fetchNotifications() {
      try {
        const res = await api.get('/api/notifications', { skipLoading: true })
        const locallyReadIds = new Set(
          this.notifications.filter(n => n.read_at).map(n => n.id)
        )
        const merged: AppNotification[] = res.data.notifications.map((fresh: AppNotification) => {
          if (locallyReadIds.has(fresh.id) && !fresh.read_at) {
            return { ...fresh, read_at: new Date().toISOString() }
          }
          return fresh
        })
        this.notifications = merged
        this.unreadCount = merged.filter(n => !n.read_at).length
        this.initialized = true
      } catch (err) {
        console.error('Failed to fetch notifications:', err)
      }
    },

    /** Marks a single notification read. Updates local state instantly
     *  (so the UI responds with zero delay — callers should NOT await
     *  this before navigating away) and fires the backend request in
     *  the background. If that request actually fails, the optimistic
     *  update is rolled back so the failure is visible rather than
     *  silently reverting later. */
    async markRead(id: AppNotification['id']) {
      const n = this.notifications.find(n => n.id === id)
      if (!n || n.read_at) return

      const previousReadAt = n.read_at
      n.read_at = new Date().toISOString()
      this.unreadCount = Math.max(0, this.unreadCount - 1)

      try {
        await api.post(`/api/notifications/${id}/read`, {}, { skipLoading: true, withCredentials: true })
      } catch (err) {
        console.error('Failed to mark notification as read:', err)
        n.read_at = previousReadAt
        this.unreadCount += 1
      }
    },

    async markAllRead() {
      const previous = this.notifications.map(n => ({ n, readAt: n.read_at }))
      const previousUnread = this.unreadCount
      this.notifications.forEach(n => { n.read_at = n.read_at || new Date().toISOString() })
      this.unreadCount = 0
      try {
        await api.post('/api/notifications/read-all', {}, { skipLoading: true, withCredentials: true })
      } catch (err) {
        console.error('Failed to mark all notifications as read:', err)
        previous.forEach(({ n, readAt }) => { n.read_at = readAt })
        this.unreadCount = previousUnread
      }
    },

    // Guarded the same way the old Layout-local notifTimer was, but now
    // it's guarded at the store (singleton) level, so re-mounting
    // Layout on navigation can't spin up a second concurrent timer.
    startPolling() {
      if (this.pollTimer) return
      this.pollTimer = setInterval(() => this.fetchNotifications(), 30000)
    },
    stopPolling() {
      if (this.pollTimer) { clearInterval(this.pollTimer); this.pollTimer = null }
    },
  },
})