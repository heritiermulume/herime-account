<template>
  <div>
    <Login v-if="currentView === 'login'" @switch-to-register="currentView = 'register'" />
    <Register v-else @switch-to-login="currentView = 'login'" />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
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

    onMounted(async () => {
      // Determine view based on current route
      if (route.path === '/register') {
        currentView.value = 'register'
      } else {
        currentView.value = 'login'
      }

      // Check if user is already authenticated
      const isAuthenticated = await authStore.checkAuth()
      if (isAuthenticated) {
        // Si force_token est présent dans l'URL, générer un token SSO et rediriger
        // Vérifier force_token de plusieurs façons pour être sûr
        const hasForceToken = route.query.force_token === '1' || 
                             route.query.force_token === 1 || 
                             route.query.force_token === true || 
                             route.query.force_token === 'true'
        
        if (hasForceToken) {
          console.log('force_token detected, generating SSO token...', {
            force_token: route.query.force_token,
            redirect: route.query.redirect,
            allQuery: route.query
          })
          
          try {
            const redirect = route.query.redirect
            if (!redirect) {
              console.error('No redirect URL provided')
              router.push('/dashboard')
              return
            }
            
            console.log('Calling /api/sso/generate-token with redirect:', redirect)
            
            const response = await axios.post('/api/sso/generate-token', {
              redirect: redirect
            })
            
            console.log('SSO token response:', response.data)
            
            if (response.data && response.data.success && response.data.data && response.data.data.callback_url) {
              const callbackUrl = response.data.data.callback_url
              console.log('Redirecting to:', callbackUrl)
              
              // Utiliser window.location.replace pour éviter que l'utilisateur puisse revenir
              window.location.replace(callbackUrl)
              return
            } else {
              console.error('Invalid response from SSO token generation:', response.data)
              // En cas de réponse invalide, ne pas rediriger vers dashboard, afficher erreur
              throw new Error('Invalid response from SSO token generation')
            }
          } catch (error) {
            console.error('Error generating SSO token:', error)
            console.error('Error details:', {
              message: error.message,
              response: error.response?.data,
              status: error.response?.status
            })
            // En cas d'erreur, rediriger vers le dashboard seulement après un délai pour voir les logs
            setTimeout(() => {
              router.push('/dashboard')
            }, 2000)
            return
          }
        } else {
          // Sinon, rediriger vers le dashboard seulement si pas de force_token
          router.push('/dashboard')
        }
      }
    })

    return {
      currentView
    }
  }
}
</script>
