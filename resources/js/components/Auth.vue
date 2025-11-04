<template>
  <div>
    <Login v-if="currentView === 'login'" @switch-to-register="currentView = 'register'" />
    <Register v-else @switch-to-login="currentView = 'login'" />
  </div>
</template>

<script>
import { ref, onMounted, onBeforeMount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'
import Login from './Login.vue'
import Register from './Register.vue'

export default {
  name: 'Auth',
  components: {
    Login,
    Register
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const authStore = useAuthStore()
    const currentView = ref('login')
    const isRedirecting = ref(false)
    const redirectPromise = ref(null)

    // Gérer la redirection SSO AVANT que le composant ne soit monté
    onBeforeMount(async () => {
      console.log('[Auth] onBeforeMount - Début', {
        path: route.path,
        query: route.query,
        force_token: route.query.force_token
      })
      
      // Vérifier immédiatement si on doit rediriger SSO
      const hasForceToken = route.query.force_token === '1' || 
                           route.query.force_token === 1 || 
                           route.query.force_token === true || 
                           route.query.force_token === 'true' ||
                           route.query.force_token === 'yes' ||
                           route.query.force_token === 'on'
      
      console.log('[Auth] hasForceToken check:', {
        hasForceToken,
        force_token_value: route.query.force_token,
        redirect: route.query.redirect
      })
      
      if (hasForceToken) {
        console.log('[Auth] force_token détecté dans onBeforeMount', {
          force_token: route.query.force_token,
          redirect: route.query.redirect,
          path: route.path
        })
        
        // Vérifier le token dans localStorage directement
        const token = localStorage.getItem('access_token')
        console.log('[Auth] Token dans localStorage:', token ? token.substring(0, 20) + '...' : 'AUCUN')
        
        if (!token) {
          console.log('[Auth] Pas de token, affichage du formulaire de login')
          return
        }
        
        // Vérifier l'authentification
        console.log('[Auth] Vérification de l\'authentification...')
        let isAuthenticated = false
        
        try {
          isAuthenticated = await authStore.checkAuth()
          console.log('[Auth] Résultat checkAuth:', isAuthenticated)
        } catch (error) {
          console.error('[Auth] Erreur lors de checkAuth:', error)
        }
        
        if (isAuthenticated) {
          console.log('[Auth] Utilisateur authentifié, génération token SSO...')
          isRedirecting.value = true
          
          // Créer une promesse de redirection pour éviter les redirections multiples
          if (!redirectPromise.value) {
            redirectPromise.value = (async () => {
              try {
                const redirect = route.query.redirect
                console.log('[Auth] Redirect URL extraite:', redirect)
                
                if (!redirect) {
                  console.error('[Auth] No redirect URL provided')
                  return false
                }
                
                console.log('[Auth] Appel API /api/sso/generate-token...', {
                  redirect: redirect,
                  token_present: !!token
                })
                
                const response = await axios.post('/api/sso/generate-token', {
                  redirect: redirect
                })
                
                console.log('[Auth] SSO token response reçue:', {
                  status: response.status,
                  success: response.data?.success,
                  has_data: !!response.data?.data,
                  has_callback_url: !!response.data?.data?.callback_url
                })
                
                if (response.data && response.data.success && response.data.data && response.data.data.callback_url) {
                  const callbackUrl = response.data.data.callback_url
                  console.log('[Auth] Redirection vers:', callbackUrl)
                  
                  // Redirection immédiate et définitive - utiliser setTimeout pour être sûr que c'est après le rendu
                  setTimeout(() => {
                    console.log('[Auth] Exécution de window.location.replace...')
                    window.location.replace(callbackUrl)
                  }, 0)
                  
                  return true
                } else {
                  console.error('[Auth] Structure de réponse invalide:', response.data)
                  return false
                }
              } catch (error) {
                console.error('[Auth] Erreur lors de la génération du token SSO:', {
                  message: error.message,
                  response: error.response?.data,
                  status: error.response?.status,
                  config: {
                    url: error.config?.url,
                    method: error.config?.method,
                    headers: error.config?.headers
                  }
                })
                return false
              }
            })()
          }
          
          // Attendre la redirection
          const redirected = await redirectPromise.value
          if (redirected) {
            console.log('[Auth] Redirection en cours...')
            return // Ne pas continuer
          }
        } else {
          console.log('[Auth] User not authenticated, will show login form')
        }
      } else {
        console.log('[Auth] Pas de force_token, comportement normal')
      }
    })

    onMounted(async () => {
      console.log('[Auth] onMounted - Début', {
        isRedirecting: isRedirecting.value,
        path: route.path
      })
      
      // Si on est en train de rediriger, ne pas continuer
      if (isRedirecting.value || redirectPromise.value) {
        console.log('[Auth] Redirection en cours, arrêt du montage')
        return
      }
      
      // Determine view based on current route
      if (route.path === '/register') {
        currentView.value = 'register'
      } else {
        currentView.value = 'login'
      }

      // Check if user is already authenticated (pour redirection normale sans force_token)
      const hasForceToken = route.query.force_token === '1' || 
                           route.query.force_token === 1 || 
                           route.query.force_token === true || 
                           route.query.force_token === 'true'
      
      if (hasForceToken) {
        console.log('[Auth] force_token présent dans onMounted, ne pas rediriger vers dashboard')
        return
      }
      
      const isAuthenticated = await authStore.checkAuth()
      if (isAuthenticated && !isRedirecting.value) {
        console.log('[Auth] User authenticated without force_token, redirecting to dashboard')
        // Redirection normale vers dashboard seulement si pas de force_token
        router.push('/dashboard')
      }
    })

    return {
      currentView,
      isRedirecting
    }
  }
}
</script>
