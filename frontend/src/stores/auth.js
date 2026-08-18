import { defineStore } from 'pinia'
import api from '../services/api'
import { Notify } from 'quasar'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('siicobs_token') || null,
    user: JSON.parse(localStorage.getItem('siicobs_user') || 'null'),
    acl: JSON.parse(localStorage.getItem('siicobs_acl') || '{}'),
    loading: false
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.isAdmin === true,
    userFullName: (state) => state.user?.name || state.user?.username || 'Usuario',
  },

  actions: {
    async login(username, password) {
      this.loading = true
      try {
        const response = await api.post('/auth/login', { username, password })
        if (response.data.success) {
          this.token = response.data.token
          this.user = response.data.user
          this.acl = response.data.acl || {}

          localStorage.setItem('siicobs_token', this.token)
          localStorage.setItem('siicobs_user', JSON.stringify(this.user))
          localStorage.setItem('siicobs_acl', JSON.stringify(this.acl))

          Notify.create({
            type: 'positive',
            message: response.data.message || 'Bienvenido a SIICOBS Moderno',
            icon: 'check_circle'
          })

          return true
        }
      } catch (error) {
        const msg = error.response?.data?.message || 'Error al iniciar sesión'
        Notify.create({
          type: 'negative',
          message: msg,
          icon: 'error'
        })
        return false
      } finally {
        this.loading = false
      }
    },

    hasPermission(resource, action = 'can_view') {
      if (this.isAdmin) return true
      if (!this.acl) return false
      const res = this.acl[resource]
      return !!(res && res[action])
    },

    async fetchMe() {
      if (!this.token) return
      try {
        const response = await api.get('/auth/me')
        if (response.data.success) {
          this.user = response.data.user
          this.acl = response.data.acl || {}
          localStorage.setItem('siicobs_user', JSON.stringify(this.user))
          localStorage.setItem('siicobs_acl', JSON.stringify(this.acl))
        }
      } catch (error) {
        // Ignorar si falla
      }
    },

    logout() {
      try {
        api.post('/auth/logout')
      } catch (e) {}

      this.token = null
      this.user = null
      this.acl = {}
      localStorage.removeItem('siicobs_token')
      localStorage.removeItem('siicobs_user')
      localStorage.removeItem('siicobs_acl')

      Notify.create({
        type: 'info',
        message: 'Sesión finalizada',
        icon: 'logout'
      })
    }
  }
})
