import axios from 'axios';
window.axios = axios;

// Configure axios defaults
const baseURL = window.location.origin + '/api'
axios.defaults.baseURL = baseURL
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

console.log('Bootstrap: Axios configured with baseURL:', baseURL)

// Set up axios interceptor for token
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  console.log('=== AXIOS REQUEST ===', config.method?.toUpperCase(), config.url)
  console.log('Token in localStorage:', token ? token.substring(0, 50) + '...' : 'NO TOKEN')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
    console.log('Authorization header set')
  } else {
    console.error('NO TOKEN FOUND IN LOCALSTORAGE!')
  }
  console.log('Request config:', config.headers)
  return config
})

// Set up response interceptor for debugging and auto-logout on 401
let isRedirecting = false

axios.interceptors.response.use(
  (response) => {
    console.log('Axios response interceptor:', response.status, response.config.url)
    console.log('Response data in interceptor (first 200 chars):', JSON.stringify(response.data).substring(0, 200))
    return response
  },
  async (error) => {
    console.error('Axios error interceptor:', error.message, error.config?.url, error.response?.data)
    
    // Si l'erreur est 401 (Unauthorized), l'utilisateur doit être déconnecté
    if (error.response?.status === 401 && !isRedirecting) {
      const currentPath = window.location.pathname
      
      // Ignorer les erreurs 401 sur les routes de login/register
      if (currentPath === '/login' || currentPath === '/register') {
        return Promise.reject(error)
      }
      
      console.log('401 Unauthorized detected, logging out user')
      isRedirecting = true
      
      try {
        // Récupérer le store auth de manière dynamique
        const { useAuthStore } = await import('./stores/auth')
        const authStore = useAuthStore()
        
        // Déconnecter l'utilisateur (sans attendre)
        authStore.logout().catch(err => {
          console.error('Error during logout:', err)
        })
        
        // Rediriger vers la page de login de manière asynchrone pour éviter le blocage
        setTimeout(() => {
          if (window.location.pathname !== '/login' && window.location.pathname !== '/register') {
            console.log('Redirecting to login page')
            window.location.replace('/login')
          }
          isRedirecting = false
        }, 0)
      } catch (err) {
        console.error('Error in 401 handler:', err)
        isRedirecting = false
      }
    }
    
    return Promise.reject(error)
  }
)
