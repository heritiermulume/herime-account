import axios from 'axios';
window.axios = axios;


// Désactiver les logs en production (mais garder les erreurs)
if (import.meta && import.meta.env && import.meta.env.PROD) {
  const noop = () => {};
  console.log = noop;
  console.debug = noop;
  console.info = noop;
}

// Configure axios defaults
const baseURL = window.location.origin + '/api'
axios.defaults.baseURL = baseURL
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
// Set timeout to 10 seconds to prevent long waits
axios.defaults.timeout = 10000

// Désactiver les logs d'erreur Axios en production
if (import.meta && import.meta.env && import.meta.env.PROD) {
  // Intercepter et supprimer les logs d'erreur automatiques
  const originalError = console.error
  console.error = function(...args) {
    // Ne pas afficher les erreurs Axios dans la console en production
    if (args[0] && typeof args[0] === 'string' && 
        (args[0].includes('POST') || args[0].includes('GET') || args[0].includes('PUT') || args[0].includes('DELETE')) &&
        (args[0].includes('401') || args[0].includes('404') || args[0].includes('500') || args[0].includes('403'))) {
      return
    }
    originalError.apply(console, args)
  }
}

// Set up axios interceptor for token
axios.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('access_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    // Supprimer les logs d'erreur de requête
    return Promise.reject(error)
  }
)

// Set up response interceptor for auto-logout on 401
let isRedirecting = false

axios.interceptors.response.use(
  (response) => {
    return response
  },
  async (error) => {
    // Personnaliser les messages d'erreur en français
    if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
      error.message = 'La requête a pris trop de temps. Veuillez vérifier votre connexion et réessayer.'
    } else if (error.code === 'ERR_NETWORK' || error.message.includes('Network Error')) {
      error.message = 'Erreur de connexion au serveur. Veuillez vérifier votre connexion internet.'
    } else if (error.code === 'ERR_BAD_REQUEST' && !error.response) {
      error.message = 'Erreur lors de la requête. Veuillez réessayer.'
    }
    
    // If 401 (Unauthorized), force logout and redirect to login (except on auth pages)
    if (error.response?.status === 401 && !isRedirecting) {
      const currentPath = window.location.pathname
      if (currentPath !== '/login' && currentPath !== '/register') {
        isRedirecting = true
        try {
          const { useAuthStore } = await import('./stores/auth')
          const authStore = useAuthStore()
          if (typeof authStore.forceLogout === 'function') {
            authStore.forceLogout()
          } else {
            authStore.logout().catch(() => {})
          }
          setTimeout(() => {
            if (window.location.pathname !== '/login' && window.location.pathname !== '/register') {
              window.location.replace('/login')
            }
            isRedirecting = false
          }, 0)
        } catch (_) {
          isRedirecting = false
        }
      }
    }
    
    return Promise.reject(error)
  }
)
