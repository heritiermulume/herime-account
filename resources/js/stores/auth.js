import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    authenticated: localStorage.getItem('authenticated') === 'true',
    loading: false,
    error: null,
    twoFactorToken: null,
    isSSORedirecting: false // Flag pour indiquer qu'une redirection SSO est en cours
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
      
      this.loading = true
      this.error = null

      try {
        const response = await axios.post('/login', credentials)
        
        // Vérifier si la réponse est valide
        if (!response.data) {
          throw new Error('No data received from server')
        }
        
        
        // Vérifier si la 2FA est requise AVANT de vérifier le success
        if (response.data.requires_two_factor === true) {
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
        
        
        if (isSuccess) {
          // Stocker les données utilisateur
          this.user = response.data.data.user
          this.authenticated = true
          
          // Store user and token in localStorage
          localStorage.setItem('user', JSON.stringify(this.user))
          localStorage.setItem('authenticated', 'true')
          const token = response.data.data.access_token
          localStorage.setItem('access_token', token)
          
          // Si une redirection SSO est nécessaire, la gérer
          if (response.data.data?.sso_redirect_url) {
            this.isSSORedirecting = true
            
            // Marquer la redirection dans sessionStorage AVANT de rediriger
            if (typeof window !== 'undefined' && typeof sessionStorage !== 'undefined') {
              sessionStorage.setItem('sso_redirecting', 'true');
              sessionStorage.setItem('sso_redirect_url', response.data.data.sso_redirect_url);
              
              // Rediriger immédiatement vers le site externe
              // Utiliser replace() pour éviter d'ajouter à l'historique
              window.location.replace(response.data.data.sso_redirect_url);
              // Ne pas retourner, la redirection va se produire
              return response.data;
            }
          } else {
          }
          
          // Nettoyer les flags SSO seulement si pas de redirection SSO en cours
          if (typeof window !== 'undefined' && sessionStorage) {
            const isRedirecting = sessionStorage.getItem('sso_redirecting') === 'true';
            if (!isRedirecting) {
              sessionStorage.removeItem('sso_loop_detected')
              sessionStorage.removeItem('sso_redirecting')
              sessionStorage.removeItem('sso_redirecting_timestamp')
              sessionStorage.removeItem('sso_redirecting_url')
              sessionStorage.removeItem('sso_redirect_attempts')
              sessionStorage.removeItem('sso_last_redirect_to')
            }
          }
          
          return response.data
        } else {
          throw new Error(response.data.message || 'Login failed')
        }
      } catch (error) {
        
        // Si la 2FA est requise, retourner une erreur spéciale
        // Vérifier aussi dans response.data direct (cas où success: false mais status 200)
        if (error.response?.data?.requires_two_factor === true) {
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
      }
    },

    async verifyTwoFactor(email, code, additionalData = {}) {
      this.loading = true
      this.error = null

      try {
        const requestData = {
          email,
          code,
          two_factor_token: this.twoFactorToken,
          ...additionalData
        }
        
        const response = await axios.post('/login/verify-2fa', requestData)
        
        if (response.data.success) {
          // Stocker les données utilisateur
          this.user = response.data.data.user
          this.authenticated = true
          
          // Store user and token in localStorage
          localStorage.setItem('user', JSON.stringify(this.user))
          localStorage.setItem('authenticated', 'true')
          const token = response.data.data.access_token
          localStorage.setItem('access_token', token)
          this.twoFactorToken = null
          
          // Si une redirection SSO est nécessaire, la gérer
          if (response.data.data?.sso_redirect_url) {
            this.isSSORedirecting = true
            
            // Marquer la redirection dans sessionStorage AVANT de rediriger
            if (typeof window !== 'undefined' && typeof sessionStorage !== 'undefined') {
              sessionStorage.setItem('sso_redirecting', 'true');
              sessionStorage.setItem('sso_redirect_url', response.data.data.sso_redirect_url);
              
              // Rediriger immédiatement vers le site externe
              // Utiliser replace() pour éviter d'ajouter à l'historique
              window.location.replace(response.data.data.sso_redirect_url);
              // Ne pas retourner, la redirection va se produire
              return response.data;
            }
          } else {
          }
          
          // Nettoyer les flags SSO seulement si pas de redirection SSO en cours
          if (typeof window !== 'undefined' && sessionStorage) {
            const isRedirecting = sessionStorage.getItem('sso_redirecting') === 'true';
            if (!isRedirecting) {
              sessionStorage.removeItem('sso_loop_detected')
              sessionStorage.removeItem('sso_redirecting')
              sessionStorage.removeItem('sso_redirecting_timestamp')
              sessionStorage.removeItem('sso_redirecting_url')
              sessionStorage.removeItem('sso_redirect_attempts')
              sessionStorage.removeItem('sso_last_redirect_to')
            }
          }
          
          return response.data
        } else {
          throw new Error(response.data.message || '2FA verification failed')
        }
      } catch (error) {
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
          
          // Si une redirection SSO est nécessaire, la gérer
          if (response.data.data?.sso_redirect_url) {
            this.isSSORedirecting = true
            
            // Marquer la redirection dans sessionStorage AVANT de rediriger
            if (typeof window !== 'undefined' && typeof sessionStorage !== 'undefined') {
              sessionStorage.setItem('sso_redirecting', 'true');
              sessionStorage.setItem('sso_redirect_url', response.data.data.sso_redirect_url);
              
              // Rediriger immédiatement vers le site externe
              // Utiliser replace() pour éviter d'ajouter à l'historique
              window.location.replace(response.data.data.sso_redirect_url);
              // Ne pas retourner, la redirection va se produire
              return response.data;
            }
          } else {
          }
          
          // Nettoyer les flags SSO seulement si pas de redirection SSO en cours
          if (typeof window !== 'undefined' && sessionStorage) {
            const isRedirecting = sessionStorage.getItem('sso_redirecting') === 'true';
            if (!isRedirecting) {
              sessionStorage.removeItem('sso_loop_detected')
              sessionStorage.removeItem('sso_redirecting')
              sessionStorage.removeItem('sso_redirecting_timestamp')
              sessionStorage.removeItem('sso_redirecting_url')
              sessionStorage.removeItem('sso_redirect_attempts')
              sessionStorage.removeItem('sso_last_redirect_to')
            }
          }
          
          return response.data
        } else {
          throw new Error(response.data.message || 'Registration failed')
        }
      } catch (error) {
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

    resetAuthState() {
      this.user = null
      this.authenticated = false
      this.error = null
      this.isSSORedirecting = false

      localStorage.removeItem('user')
      localStorage.removeItem('authenticated')
      localStorage.removeItem('access_token')
    },

    async logout() {
      this.loading = true

      try {
        const response = await axios.post('/logout')
        
        // Si le serveur retourne des warnings, les logger mais continuer
        if (response.data?.warnings) {
          console.warn('Logout warnings:', response.data.warnings)
        }
      } catch (error) {
        // Ignorer les erreurs et forcer la déconnexion locale
        // Cela garantit que l'utilisateur est déconnecté même si le serveur est down
        console.warn('Logout error (ignored, forcing local logout):', error.response?.status, error.message)
        
        // Si le backend recommande une déconnexion locale, la faire
        if (error.response?.data?.local_logout_recommended) {
          console.info('Local logout recommended by server')
        }
      } finally {
        // TOUJOURS déconnecter localement, même en cas d'erreur serveur
        this.resetAuthState()
        this.loading = false
      }
    },

    forceLogout() {
      this.resetAuthState()
      this.loading = false
    },

    async checkAuth() {
      // Réinitialiser le flag SSO si on fait un checkAuth (nouvelle vérification)
      // Sauf si on est vraiment en train de rediriger (vérifier dans l'URL)
      if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search)
        const hasRedirect = urlParams.has('redirect') || urlParams.has('force_token')
        if (!hasRedirect) {
          this.isSSORedirecting = false
        }
      }
      
      // Vérifier si on a un token dans localStorage - OPTIMISATION: retourner immédiatement si pas de token
      const token = localStorage.getItem('access_token')
      if (!token) {
        this.user = null
        this.authenticated = false
        this.isSSORedirecting = false
        return false
      }

      // OPTIMISATION: Si on a déjà un user dans le state et authenticated, vérifier rapidement avec un timeout court
      if (this.authenticated && this.user) {
        // Vérifier avec l'API mais avec un timeout court pour éviter les attentes longues
        try {
          const response = await Promise.race([
            axios.get('/me', { timeout: 3000 }), // Timeout de 3 secondes pour les vérifications rapides
            new Promise((_, reject) => setTimeout(() => reject(new Error('Timeout')), 3000))
          ])
          
          if (response.data.success && response.data.data.user) {
            this.user = response.data.data.user
            this.authenticated = true
            localStorage.setItem('user', JSON.stringify(this.user))
            localStorage.setItem('authenticated', 'true')
            return true
          }
        } catch (error) {
          // Si l'erreur est 401 (unauthorized), le token est invalide
          if (error.response?.status === 401) {
            this.forceLogout()
            return false
          }
          // Pour les autres erreurs (timeout, network, etc.), garder l'utilisateur connecté si on a déjà un user en cache
          // Cela évite de déconnecter l'utilisateur en cas de problème réseau temporaire
          return true
        }
      }

      // Si pas de user mais on a un token, essayer de récupérer l'utilisateur avec timeout
      try {
        const response = await Promise.race([
          axios.get('/me', { timeout: 3000 }), // Timeout de 3 secondes pour accélérer
          new Promise((_, reject) => setTimeout(() => reject(new Error('Timeout')), 3000))
        ])
        
        if (response.data.success && response.data.data.user) {
          this.user = response.data.data.user
          this.authenticated = true
          localStorage.setItem('user', JSON.stringify(this.user))
          localStorage.setItem('authenticated', 'true')
          return true
        } else {
          this.forceLogout()
          return false
        }
      } catch (error) {
        // Si 401, le token est invalide, déconnecter
        if (error.response?.status === 401) {
          this.forceLogout()
          return false
        }
        // Pour les autres erreurs (timeout, network, 500, etc.), ne pas déconnecter si on a déjà un user en cache
        // Cela évite de déconnecter l'utilisateur en cas de problème réseau temporaire
        if (this.user) {
          return true
        }
        // Si pas de user en cache et erreur réseau/timeout, considérer comme non authentifié
        this.forceLogout()
        return false
      }
    },


    updateUser(userData) {
      
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
      
      
      localStorage.setItem('user', JSON.stringify(this.user))
      
    },

    clearError() {
      this.error = null
    }
  }
})
