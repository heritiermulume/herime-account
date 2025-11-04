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

    // Gérer la redirection SSO AVANT que le composant ne soit monté
    onBeforeMount(async () => {
      // Vérifier immédiatement si on doit rediriger SSO
      const hasForceToken = route.query.force_token === '1' || 
                           route.query.force_token === 1 || 
                           route.query.force_token === true || 
                           route.query.force_token === 'true'
      
      if (hasForceToken) {
        console.log('[Auth] force_token détecté dans onBeforeMount', {
          force_token: route.query.force_token,
          redirect: route.query.redirect,
          path: route.path
        })
        
        // Vérifier l'authentification
        const isAuthenticated = await authStore.checkAuth()
        
        if (isAuthenticated) {
          console.log('[Auth] Utilisateur authentifié, génération token SSO...')
          isRedirecting.value = true
          
          try {
            const redirect = route.query.redirect
            if (!redirect) {
              console.error('[Auth] No redirect URL provided')
              return
            }
            
            console.log('[Auth] Calling /api/sso/generate-token with redirect:', redirect)
            
            const response = await axios.post('/api/sso/generate-token', {
              redirect: redirect
            })
            
            console.log('[Auth] SSO token response:', response.data)
            
            if (response.data && response.data.success && response.data.data && response.data.data.callback_url) {
              const callbackUrl = response.data.data.callback_url
              console.log('[Auth] Redirecting to:', callbackUrl)
              
              // Redirection immédiate et définitive
              window.location.replace(callbackUrl)
              return // Ne pas continuer
            } else {
              console.error('[Auth] Invalid response structure:', response.data)
            }
          } catch (error) {
            console.error('[Auth] Error generating SSO token:', error)
            console.error('[Auth] Error details:', {
              message: error.message,
              response: error.response?.data,
              status: error.response?.status
            })
          }
        } else {
          console.log('[Auth] User not authenticated, will show login form')
        }
      }
    })

    onMounted(async () => {
      // Si on est en train de rediriger, ne pas continuer
      if (isRedirecting.value) {
        return
      }
      
      // Determine view based on current route
      if (route.path === '/register') {
        currentView.value = 'register'
      } else {
        currentView.value = 'login'
      }

      // Check if user is already authenticated (pour redirection normale sans force_token)
      const isAuthenticated = await authStore.checkAuth()
      if (isAuthenticated && !isRedirecting.value) {
        // Vérifier à nouveau force_token au cas où
        const hasForceToken = route.query.force_token === '1' || 
                             route.query.force_token === 1 || 
                             route.query.force_token === true || 
                             route.query.force_token === 'true'
        
        if (!hasForceToken) {
          // Redirection normale vers dashboard seulement si pas de force_token
          router.push('/dashboard')
        }
      }
    })

    return {
      currentView,
      isRedirecting
    }
  }
}
</script>
