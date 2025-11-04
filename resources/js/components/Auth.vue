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
        if (route.query.force_token) {
          try {
            const redirect = route.query.redirect
            const response = await axios.post('/api/sso/generate-token', {
              redirect: redirect
            })
            
            if (response.data.success && response.data.data.callback_url) {
              // Rediriger vers l'URL de callback avec le token SSO
              window.location.href = response.data.data.callback_url
              return
            }
          } catch (error) {
            console.error('Error generating SSO token:', error)
            // En cas d'erreur, rediriger vers le dashboard
            router.push('/dashboard')
            return
          }
        }
        
        // Sinon, rediriger vers le dashboard
        router.push('/dashboard')
      }
    })

    return {
      currentView
    }
  }
}
</script>
