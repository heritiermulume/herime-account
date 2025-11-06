<template>
  <div id="app" class="min-h-screen bg-gray-50 dark:bg-gray-900">

    <!-- Loading State - Masquer l'interface si redirection SSO en cours -->
    <div v-if="loading || isSSORedirecting || authStore.isSSORedirecting" class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-50 dark:bg-gray-900">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2" style="border-color: #003366;"></div>
        <span class="text-gray-700 dark:text-gray-300">{{ isSSORedirecting || authStore.isSSORedirecting ? 'Redirection en cours...' : 'Chargement...' }}</span>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else-if="!isSSORedirecting && !authStore.isSSORedirecting" class="min-h-screen">
        <!-- Sidebar - Hide on login/register pages -->
        <Sidebar v-if="user && route && route.path !== '/login' && route.path !== '/register'" />
      
      <!-- Main Content -->
      <div :class="user && route && route.path !== '/login' && route.path !== '/register' ? 'md:pl-64' : ''" class="flex flex-col flex-1">
        <!-- Top Navigation - Only show when user is logged in and not on auth pages -->
        <nav v-if="user && route && route.path !== '/login' && route.path !== '/register'" class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
          <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
              <div class="flex items-center">
                <!-- Page Title -->
                <div class="ml-4 flex items-center">
                  <img 
                    v-if="pageTitle === 'Accueil'"
                    src="/logo.png" 
                    alt="HERIME Logo" 
                    class="h-8 w-auto md:hidden"
                  />
                  <h1 v-else class="text-xl font-semibold" style="color: #ffcc33;">
                    {{ pageTitle }}
                  </h1>
                  <h1 v-if="pageTitle === 'Accueil'" class="text-xl font-semibold hidden md:block" style="color: #ffcc33;">
                    {{ pageTitle }}
                  </h1>
                </div>
              </div>
              
              <div class="flex items-center space-x-4">
                <!-- User info -->
                <div class="flex items-center space-x-3">
                  <div v-if="user.avatar_url && user.avatar_url !== ''" class="h-8 w-8 rounded-full overflow-hidden bg-gray-200">
                    <img
                      :src="getAvatarUrl()"
                      :alt="user.name"
                      class="h-full w-full object-cover"
                      @error="handleImageError"
                    />
                  </div>
                  <div v-else class="h-8 w-8 rounded-full flex items-center justify-center" style="background-color: #ffcc33;">
                    <svg class="h-5 w-5" style="color: #003366;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                  </div>
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ user.name }}
                  </span>
                  <button
                    @click="logout"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                  >
                    Déconnexion
                  </button>
                </div>
              </div>
            </div>
          </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 py-6 pb-20 md:pb-6">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <router-view />
          </div>
        </main>
      </div>
    </div>

    <!-- Mobile Navigation - Hide on login/register pages -->
    <MobileNavigation v-if="user && route && route.path !== '/login' && route.path !== '/register'" />

    <!-- Toast Container -->
    <ToastContainer ref="toastContainer" />

  </div>
</template>

<script>
import { ref, onMounted, computed, provide } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import Sidebar from './Sidebar.vue'
import MobileNavigation from './MobileNavigation.vue'
import ToastContainer from './ToastContainer.vue'

export default {
  name: 'App',
  components: {
    Sidebar,
    MobileNavigation,
    ToastContainer
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const authStore = useAuthStore()
    
    const loading = ref(false)
    const isDarkMode = ref(false)
    const toastContainer = ref(null)

    const user = computed(() => authStore.user)
    const isSSORedirecting = computed(() => authStore.isSSORedirecting)
    
    // Vérifier si on est en train de rediriger vers un site externe
    const checkSSORedirect = () => {
      const redirectParam = route.query.redirect
      if (redirectParam && typeof redirectParam === 'string') {
        try {
          // Décoder l'URL
          let decoded = redirectParam
          for (let i = 0; i < 3; i++) {
            try {
              const temp = decodeURIComponent(decoded)
              if (temp === decoded) break
              decoded = temp
            } catch (e) {
              break
            }
          }
          
          if (decoded.startsWith('http')) {
            const url = new URL(decoded)
            const currentHost = window.location.hostname.replace(/^www\./, '').toLowerCase()
            const urlHost = url.hostname.replace(/^www\./, '').toLowerCase()
            
            // Si c'est un domaine externe et qu'on est authentifié, on va rediriger
            // Le flag isSSORedirecting est déjà géré par l'authStore
            if (urlHost !== currentHost && urlHost !== 'compte.herime.com' && authStore.authenticated) {
              return true
            }
          }
        } catch (e) {
          // Ignorer les erreurs
        }
      }
      return false
    }

    // Notification service
    const notify = {
      success: (title, message = '') => {
        if (toastContainer.value) {
          toastContainer.value.success(title, message)
        }
      },
      error: (title, message = '') => {
        if (toastContainer.value) {
          toastContainer.value.error(title, message)
        }
      },
      info: (title, message = '') => {
        if (toastContainer.value) {
          toastContainer.value.info(title, message)
        }
      },
      warning: (title, message = '') => {
        if (toastContainer.value) {
          toastContainer.value.warning(title, message)
        }
      }
    }

    // Provide notification service to child components
    provide('notify', notify)

    const pageTitle = computed(() => {
      if (!route || !route.path) return 'HERIME'
      const titles = {
        '/dashboard': 'Accueil',
        '/profile': 'Profil',
        '/security': 'Sécurité',
        '/notifications': 'Notifications',
        '/about': 'À propos'
      }
      return titles[route.path] || 'HERIME'
    })

    const showPageTitle = computed(() => {
      return route && route.path && route.path !== '/dashboard'
    })

    const toggleDarkMode = () => {
      isDarkMode.value = !isDarkMode.value
      document.documentElement.classList.toggle('dark', isDarkMode.value)
      localStorage.setItem('darkMode', isDarkMode.value)
    }

    const logout = async () => {
      loading.value = true
      try {
        // Vérifier si un paramètre redirect est présent dans l'URL
        const redirectParam = route.query.redirect
        let logoutUrl = '/logout'
        
        if (redirectParam && typeof redirectParam === 'string') {
          // Ajouter le paramètre redirect à l'URL de logout
          logoutUrl = `/logout?redirect=${encodeURIComponent(redirectParam)}`
        }
        
        // Si on a un redirect, rediriger directement vers la route web logout
        // qui gérera le logout et la redirection
        if (redirectParam) {
          window.location.href = logoutUrl
          return
        }
        
        // Sinon, utiliser le logout normal via l'API
        await authStore.logout()
        router.push('/login')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        loading.value = false
      }
    }

    onMounted(async () => {
      console.log('App mounted')
      console.log('Initial user:', authStore.user)
      console.log('Initial authenticated:', authStore.authenticated)
      console.log('Current route:', route?.path)
      
      // Vérifier si on doit masquer l'interface pour une redirection SSO
      if (route.path === '/login' || route.path === '/register') {
        checkSSORedirect()
      }
      
      // Check for saved dark mode preference
      const savedDarkMode = localStorage.getItem('darkMode')
      if (savedDarkMode !== null) {
        isDarkMode.value = savedDarkMode === 'true'
        document.documentElement.classList.toggle('dark', isDarkMode.value)
      } else {
        // Check system preference
        isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
        document.documentElement.classList.toggle('dark', isDarkMode.value)
      }

      // Check authentication for protected routes
      if (route.meta.requiresAuth) {
        try {
          const isAuth = await authStore.checkAuth()
          if (!isAuth) {
            console.log('User not authenticated, redirecting to login')
            router.push('/login')
          }
        } catch (error) {
          console.error('Auth check error:', error)
          router.push('/login')
        }
      }
    })

    const getAvatarUrl = () => {
      // Si on a un avatar_url qui est une URL complète (commence par http), la retourner avec timestamp
      if (user.value?.avatar_url && user.value.avatar_url.startsWith('http')) {
        const separator = user.value.avatar_url.includes('?') ? '&' : '?'
        return user.value.avatar_url.includes('?t=') ? user.value.avatar_url : user.value.avatar_url + separator + 't=' + Date.now()
      }
      
      // Si on a un avatar mais pas d'avatar_url, construire l'URL avec timestamp
      if (user.value?.avatar && user.value?.id) {
        const baseURL = (typeof window !== 'undefined' && window.axios?.defaults?.baseURL) 
          ? window.axios.defaults.baseURL 
          : '/api'
        return `${baseURL}/user/avatar/${user.value.id}?t=` + Date.now()
      }
      
      // Si on a un avatar_url qui commence par /api, le retourner avec timestamp
      if (user.value?.avatar_url && user.value.avatar_url.startsWith('/api')) {
        return user.value.avatar_url.includes('?t=') ? user.value.avatar_url : user.value.avatar_url + '?t=' + Date.now()
      }
      
      // Sinon, pas d'avatar
      return null
    }

    const handleImageError = (event) => {
      console.error('❌ Image load error in App:', event.target.src)
    }

    return {
      loading,
      isDarkMode,
      user,
      route,
      router,
      pageTitle,
      showPageTitle,
      toggleDarkMode,
      logout,
      authStore,
      toastContainer,
      getAvatarUrl,
      handleImageError,
      isSSORedirecting
    }
  }
}
</script>
