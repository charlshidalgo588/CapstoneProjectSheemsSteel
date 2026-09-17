import { defineStore } from 'pinia'
import api from '@/api/axios'

interface User {
  id: number
  name: string
  email: string
  must_change_password?: boolean   
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    authenticated: false,
    loading: false,

    // Has fetchUser() ever resolved (success or failure) at least once?
    // Lets callers (router guard, Layout.vue, etc.) know the store
    // already reflects the server's answer, so they can skip fetching
    // again and just read `user` / `authenticated` directly.
    initialized: false,

    // In-flight fetchUser() promise, if any. If multiple places call
    // fetchUser()/checkAuth() around the same time (router guard +
    // Layout.vue mounting together, or a guard firing twice on a fast
    // redirect), they all await this SAME promise instead of each
    // firing their own /api/user request.
    _fetchPromise: null as Promise<void> | null,
  }),

  actions: {
    async getCsrfCookie() {
      await api.get('/sanctum/csrf-cookie')
    },

    async login(credentials: {
      email: string
      password: string
      remember?: boolean
    }) {
      this.loading = true

      try {
        await this.getCsrfCookie()

        const loginResponse = await api.post('/login', {
          email: credentials.email,
          password: credentials.password,
          remember: credentials.remember ?? false,
        })

        console.log('LOGIN RESPONSE:', loginResponse.data)

        const userResponse = await api.get('/api/user')

        console.log('USER RESPONSE:', userResponse.data)

        if (!userResponse.data) {
          throw new Error('User not authenticated')
        }

        this.user = userResponse.data
        this.authenticated = true
        this.initialized = true

        return userResponse.data
      } catch (error) {
        console.error('LOGIN ERROR:', error)

        this.user = null
        this.authenticated = false
        this.initialized = true

        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Fetches the current user from the server. Safe to call from
     * multiple places (router guard, Layout.vue, anywhere else) without
     * worrying about duplicate network requests — if a fetch is already
     * in flight, everyone awaits that same one instead of starting a
     * new one.
     */
    async fetchUser() {
      if (this._fetchPromise) return this._fetchPromise

      this._fetchPromise = (async () => {
        try {
          const res = await api.get('/api/user')

          if (!res.data) {
            this.user = null
            this.authenticated = false
            return
          }

          this.user = res.data
          this.authenticated = true
        } catch (error) {
          this.user = null
          this.authenticated = false
        } finally {
          this.initialized = true
          this._fetchPromise = null
        }
      })()

      return this._fetchPromise
    },

    async logout() {
      try {
        await api.post('/logout')
      } catch (error) {
        console.error(error)
      } finally {
        this.user = null
        this.authenticated = false
        // Deliberately NOT resetting `initialized` — we've still just
        // gotten a definitive answer (logged out), so callers shouldn't
        // re-fetch, they should just see authenticated = false.
      }
    },

    /**
     * Ensures the store has checked auth status at least once. Cheap to
     * call repeatedly (e.g. from a router guard on every navigation) —
     * after the first real check, this is a no-op read instead of a
     * network call. Pass `force: true` to bypass the cache and re-check
     * (e.g. after a 401 elsewhere in the app).
     */
    async checkAuth(force = false) {
      if (this.initialized && !force) return
      await this.fetchUser()
    },
  },
})