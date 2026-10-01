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
    initialized: false,
    _fetchPromise: null as Promise<void> | null,
  }),

  actions: {
    async login(credentials: { email: string; password: string; remember?: boolean }) {
      this.loading = true

      try {
        const loginResponse = await api.post('/api/login', {
          email: credentials.email,
          password: credentials.password,
          remember: credentials.remember ?? false,
        })

        const { user, token } = loginResponse.data

        localStorage.setItem('auth_token', token)

        this.user = user
        this.authenticated = true
        this.initialized = true

        return user
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
          localStorage.removeItem('auth_token')
        } finally {
          this.initialized = true
          this._fetchPromise = null
        }
      })()

      return this._fetchPromise
    },

    async logout() {
      try {
        await api.post('/api/logout')
      } catch (error) {
        console.error(error)
      } finally {
        localStorage.removeItem('auth_token')
        this.user = null
        this.authenticated = false
      }
    },

    async checkAuth(force = false) {
      if (this.initialized && !force) return
      await this.fetchUser()
    },
  },
})