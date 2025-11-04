import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    authenticated: localStorage.getItem('authenticated') === 'true',
    loading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => state.authenticated && !!state.user,
    userInitials: (state) => {
      if (!state.user) return ''
      return state.user.name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
    }
  },

  actions: {
    async login(credentials) {
      console.log('=== AUTH STORE LOGIN START ===')
      console.log('Credentials:', credentials)
      console.log('Base URL:', axios.defaults.baseURL)
      
      this.loading = true
      this.error = null

      try {
        console.log('Making login request...')
        const response = await axios.post('/login', credentials)
        console.log('Response received:', response.status)
        console.log('Response data type:', typeof response.data)
        console.log('Response data keys:', Object.keys(response.data || {}))
        
        // Vérifier si la réponse est valide
        if (!response.data) {
          console.error('No data received from server')
          throw new Error('No data received from server')
        }
        
        console.log('Response data success value:', response.data.success)
        console.log('Response data success type:', typeof response.data.success)
        console.log('Response data success === true:', response.data.success === true)
        console.log('Response data success == true:', response.data.success == true)
        
        // Vérifier success avec différentes méthodes
        const isSuccess = response.data.success === true || 
                         response.data.success === 'true' || 
                         response.data.success === 1
        
        console.log('Is success (all methods):', isSuccess)
        
        if (isSuccess) {
          console.log('Login successful, processing user data...')
          this.user = response.data.data.user
          this.authenticated = true
          
          // Store user and token in localStorage
          localStorage.setItem('user', JSON.stringify(this.user))
          localStorage.setItem('authenticated', 'true')
          const token = response.data.data.access_token
          localStorage.setItem('access_token', token)
          
          console.log('AuthStore: Login successful, user:', this.user)
          console.log('AuthStore: Token stored:', token ? token.substring(0, 50) + '...' : 'NO TOKEN')
          return response.data
        } else {
          console.log('Login failed - success check failed')
          console.log('Success value:', response.data.success)
          console.log('Success type:', typeof response.data.success)
          throw new Error(response.data.message || 'Login failed')
        }
      } catch (error) {
        console.error('=== AUTH STORE LOGIN ERROR ===')
        console.error('AuthStore: Login error:', error)
        console.error('AuthStore: Error message:', error.message)
        console.error('AuthStore: Error response:', error.response)
        console.error('AuthStore: Error status:', error.response?.status)
        console.error('AuthStore: Error data:', error.response?.data)
        
        if (error.response?.data?.message) {
          // Utiliser le message du serveur (déjà traduit en français)
          this.error = error.response.data.message
        } else if (error.response?.status === 401) {
          this.error = 'Identifiants incorrects. Veuillez vérifier votre email et mot de passe.'
        } else if (error.response?.status === 403) {
          this.error = 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
        } else if (error.response?.status === 404) {
          this.error = 'Service non disponible. Veuillez réessayer plus tard.'
        } else if (error.code === 'NETWORK_ERROR' || error.message.includes('Network Error')) {
          this.error = 'Erreur de réseau. Vérifiez votre connexion internet.'
        } else {
          this.error = error.message || 'Une erreur est survenue lors de la connexion.'
        }
        throw error
      } finally {
        this.loading = false
        console.log('=== AUTH STORE LOGIN END ===')
      }
    },

    async register(userData) {
      this.loading = true
      this.error = null

      try {
        const response = await axios.post('/register', userData)
        
        if (response.data.success) {
          this.user = response.data.data.user
          this.authenticated = true
          
          // Store user and token in localStorage
          localStorage.setItem('user', JSON.stringify(this.user))
          localStorage.setItem('authenticated', 'true')
          localStorage.setItem('access_token', response.data.data.access_token)
          
          return response.data
        } else {
          throw new Error(response.data.message || 'Registration failed')
        }
      } catch (error) {
        console.error('AuthStore: Register error:', error)
        if (error.response?.data?.message) {
          this.error = error.response.data.message
        } else if (error.response?.status === 422) {
          this.error = 'Veuillez vérifier les informations saisies.'
        } else if (error.response?.status === 409) {
          this.error = 'Cette adresse email est déjà utilisée.'
        } else if (error.response?.status === 404) {
          this.error = 'Service non disponible. Veuillez réessayer plus tard.'
        } else {
          this.error = error.message || 'Une erreur est survenue lors de la création du compte.'
        }
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      this.loading = true

      try {
        await axios.post('/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        // Clear state regardless of API call success
        this.user = null
        this.authenticated = false
        this.error = null
        
        // Remove from localStorage
        localStorage.removeItem('user')
        localStorage.removeItem('authenticated')
        localStorage.removeItem('access_token')
        
        this.loading = false
      }
    },

    async checkAuth() {
      // Vérifier si on a un token dans localStorage
      const token = localStorage.getItem('access_token')
      if (!token) {
        this.user = null
        this.authenticated = false
        return false
      }

      // Si on a déjà un user dans le state et authenticated, le retourner
      if (this.authenticated && this.user) {
        // Vérifier quand même avec l'API pour s'assurer que le token est toujours valide
        try {
          const response = await axios.get('/me')
          if (response.data.success && response.data.data.user) {
            this.user = response.data.data.user
            this.authenticated = true
            localStorage.setItem('user', JSON.stringify(this.user))
            localStorage.setItem('authenticated', 'true')
            return true
          }
        } catch (error) {
          console.error('Auth check failed:', error)
          // Si l'erreur est 401 (unauthorized), le token est invalide
          if (error.response?.status === 401) {
            this.logout()
            return false
          }
          // Pour les autres erreurs, garder l'utilisateur connecté
          return true
        }
      }

      // Si pas de user mais on a un token, essayer de récupérer l'utilisateur
      try {
        const response = await axios.get('/me')
        
        if (response.data.success && response.data.data.user) {
          this.user = response.data.data.user
          this.authenticated = true
          localStorage.setItem('user', JSON.stringify(this.user))
          localStorage.setItem('authenticated', 'true')
          return true
        } else {
          this.logout()
          return false
        }
      } catch (error) {
        console.error('Auth check failed:', error)
        // Si 401, le token est invalide, déconnecter
        if (error.response?.status === 401) {
          this.logout()
          return false
        }
        // Pour les autres erreurs (network, 500, etc.), essayer de garder l'utilisateur si on a déjà un user en cache
        if (this.user) {
          return true
        }
        this.logout()
        return false
      }
    },


    updateUser(userData) {
      console.log('🔄 updateUser called with:', userData)
      console.log('   avatar_url in userData:', userData?.avatar_url)
      console.log('   avatar in userData:', userData?.avatar)
      console.log('   Current user avatar_url:', this.user?.avatar_url)
      console.log('   Current user avatar:', this.user?.avatar)
      
      // Mettre à jour l'utilisateur
      this.user = { ...this.user, ...userData }
      
      // S'assurer que avatar_url est bien inclus
      if (userData?.avatar_url) {
        this.user.avatar_url = userData.avatar_url
      }
      
      // S'assurer que avatar est aussi mis à jour (contient l'URL complète)
      if (userData?.avatar) {
        this.user.avatar = userData.avatar
      }
      
      // Forcer le rechargement de l'URL avec un timestamp pour éviter le cache
      if (this.user?.avatar_url && !this.user.avatar_url.includes('?t=')) {
        const separator = this.user.avatar_url.includes('?') ? '&' : '?'
        this.user.avatar_url = this.user.avatar_url + separator + 't=' + Date.now()
      }
      
      console.log('   Updated user avatar_url:', this.user?.avatar_url)
      console.log('   Updated user avatar:', this.user?.avatar)
      
      localStorage.setItem('user', JSON.stringify(this.user))
      
      console.log('✅ User updated in store and localStorage')
    },

    clearError() {
      this.error = null
    }
  }
})
