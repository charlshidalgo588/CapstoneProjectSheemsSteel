import { defineStore } from 'pinia'
import api from '@/api/axios'

interface User {
  id: number
  name: string
  email: string
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    authenticated: false,
    loading: false,
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

        return userResponse.data
      } catch (error) {
        console.error('LOGIN ERROR:', error)

        this.user = null
        this.authenticated = false

        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchUser() {
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
      }
    },

    async logout() {
      try {
        await api.post('/logout')
      } catch (error) {
        console.error(error)
      } finally {
        this.user = null
        this.authenticated = false
      }
    },

    async checkAuth() {
      await this.fetchUser()
    },
  },
})