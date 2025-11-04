import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    authenticated: localStorage.getItem('authenticated') === 'true',
    loading: false,
    error: null,
    twoFactorToken: null
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
      if (import.meta.env.DEV) console.log('=== AUTH STORE LOGIN START ===')
      if (import.meta.env.DEV) console.log('Base URL:', axios.defaults.baseURL)
      
      this.loading = true
      this.error = null

      try {
        if (import.meta.env.DEV) console.log('Making login request...')
        const response = await axios.post('/login', credentials)
        if (import.meta.env.DEV) console.log('Response received:', response.status)
        
        // Vérifier si la réponse est valide
        if (!response.data) {
          console.error('No data received from server')
          throw new Error('No data received from server')
        }
        
        if (import.meta.env.DEV) console.log('Response data success value:', response.data.success)
        
        // Vérifier si la 2FA est requise AVANT de vérifier le success
        if (response.data.requires_two_factor === true) {
          if (import.meta.env.DEV) console.log('AuthStore: 2FA required detected in response')
          // Stocker le jeton temporaire pour la vérification
          this.twoFactorToken = response.data.two_factor_token || null
          const customError = new Error(response.data.message || 'Code 2FA requis')
          customError.requiresTwoFactor = true
          customError.response = { data: response.data }
          throw customError
        }
        
        // Vérifier success avec différentes méthodes
        const isSuccess = response.data.success === true || 
                         response.data.success === 'true' || 
                         response.data.success === 1
        
        if (import.meta.env.DEV) console.log('Is success (all methods):', isSuccess)
        
        if (isSuccess) {
          if (import.meta.env.DEV) console.log('Login successful, processing user data...')
          this.user = response.data.data.user
          this.authenticated = true
          
          // Store user and token in localStorage
          localStorage.setItem('user', JSON.stringify(this.user))
          localStorage.setItem('authenticated', 'true')
          const token = response.data.data.access_token
          localStorage.setItem('access_token', token)
          
          if (import.meta.env.DEV) console.log('AuthStore: Login successful')
          return response.data
        } else {
          if (import.meta.env.DEV) console.log('Login failed - success check failed')
          throw new Error(response.data.message || 'Login failed')
        }
      } catch (error) {
        if (import.meta.env.DEV) console.error('=== AUTH STORE LOGIN ERROR ===')
        if (import.meta.env.DEV) console.error('AuthStore: Login error:', error)
        if (import.meta.env.DEV) console.error('AuthStore: Error message:', error.message)
        if (import.meta.env.DEV) console.error('AuthStore: Error status:', error.response?.status)
        
        // Si la 2FA est requise, retourner une erreur spéciale
        // Vérifier aussi dans response.data direct (cas où success: false mais status 200)
        if (error.response?.data?.requires_two_factor === true) {
          if (import.meta.env.DEV) console.log('AuthStore: 2FA required detected')
          this.twoFactorToken = error.response.data.two_factor_token || null
          const customError = new Error(error.response.data.message || 'Code 2FA requis')
          customError.requiresTwoFactor = true
          throw customError
        }
        
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
        if (import.meta.env.DEV) console.log('=== AUTH STORE LOGIN END ===')
      }
    },

    async verifyTwoFactor(email, code) {
      this.loading = true
      this.error = null

      try {
        const response = await axios.post('/login/verify-2fa', {
          email,
          code,
          two_factor_token: this.twoFactorToken
        })
        
        if (response.data.success) {
          this.user = response.data.data.user
          this.authenticated = true
          
          // Store user and token in localStorage
          localStorage.setItem('user', JSON.stringify(this.user))
          localStorage.setItem('authenticated', 'true')
          const token = response.data.data.access_token
          localStorage.setItem('access_token', token)
          this.twoFactorToken = null
          
          if (import.meta.env.DEV) console.log('AuthStore: 2FA verification successful')
          return response.data
        } else {
          throw new Error(response.data.message || '2FA verification failed')
        }
      } catch (error) {
        if (import.meta.env.DEV) console.error('AuthStore: 2FA verification error:', error)
        if (error.response?.data?.message) {
          this.error = error.response.data.message
        } else if (error.response?.status === 422) {
          this.error = 'Code de vérification invalide.'
        } else if (error.response?.status === 401) {
          this.error = 'Code expiré. Veuillez vous reconnecter.'
        } else {
          this.error = error.message || 'Une erreur est survenue lors de la vérification du code.'
        }
        throw error
      } finally {
        this.loading = false
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
        if (import.meta.env.DEV) console.error('Logout error:', error)
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
          if (import.meta.env.DEV) console.error('Auth check failed:', error)
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
        if (import.meta.env.DEV) console.error('Auth check failed:', error)
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
      if (import.meta.env.DEV) console.log('🔄 updateUser called')
      if (import.meta.env.DEV) console.log('   avatar_url in userData:', userData?.avatar_url)
      if (import.meta.env.DEV) console.log('   avatar in userData:', userData?.avatar)
      if (import.meta.env.DEV) console.log('   Current user avatar_url:', this.user?.avatar_url)
      if (import.meta.env.DEV) console.log('   Current user avatar:', this.user?.avatar)
      
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
      
      if (import.meta.env.DEV) console.log('User avatar updated')
      
      localStorage.setItem('user', JSON.stringify(this.user))
      
      if (import.meta.env.DEV) console.log('✅ User updated in store and localStorage')
    },

    clearError() {
      this.error = null
    }
  }
})
