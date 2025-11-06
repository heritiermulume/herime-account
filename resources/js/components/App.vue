<template>
  <div id="app" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Loading State - Masquer l'interface si redirection SSO en cours -->
    <!-- IMPORTANT: Vérifier sessionStorage directement dans le template pour réactivité immédiate -->
    <!-- Utiliser une fonction inline pour vérifier sessionStorage directement -->
    <div v-if="loading || isSSORedirecting || shouldShowSSOOverlay || initialSSOCheck || (typeof window !== 'undefined' && window.sessionStorage && window.sessionStorage.getItem('sso_redirecting') === 'true' && route && route.query && (route.query.redirect || route.query.force_token))" class="fixed inset-0 z-[99999] flex items-center justify-center bg-gray-900" style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 99999 !important;">
      <div class="rounded-lg p-6 md:p-8 flex flex-col items-center space-y-3 md:space-y-4" style="background-color: #003366;">
        <div class="animate-spin rounded-full h-10 w-10 md:h-12 md:w-12 border-4 border-t-transparent" style="border-color: #ffcc33;"></div>
        <span class="text-base md:text-lg font-medium text-white text-center px-4">{{ (isSSORedirecting || shouldShowSSOOverlay || initialSSOCheck || (typeof window !== 'undefined' && window.sessionStorage && window.sessionStorage.getItem('sso_redirecting') === 'true')) ? 'Redirection en cours...' : 'Chargement...' }}</span>
      </div>
    </div>

    <!-- Main Content -->
    <!-- IMPORTANT: Toujours rendre le contenu pour que Auth.vue puisse se monter et déclencher la redirection -->
    <div class="min-h-screen" :class="{ 'opacity-0 pointer-events-none': loading || isSSORedirecting || shouldShowSSOOverlay || initialSSOCheck || (typeof window !== 'undefined' && window.sessionStorage && window.sessionStorage.getItem('sso_redirecting') === 'true' && route && route.query && (route.query.redirect || route.query.force_token)) }">
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
    
    // Vérification SYNCHRONE INITIALE avant même le montage pour éviter tout rendu
    // Cette vérification se fait AVANT que Vue ne commence à rendre quoi que ce soit
    const initialSSOCheck = (() => {
      if (typeof window === 'undefined') return false
      
      // Vérifier si on est sur /login avec paramètres SSO et token présent
      // Cette vérification est SYNCHRONE et se fait AVANT le montage
      try {
        const currentPath = window.location.pathname
        const searchParams = new URLSearchParams(window.location.search)
        const hasRedirect = searchParams.has('redirect') || searchParams.has('force_token')
        
        if (currentPath === '/login' && hasRedirect) {
          const token = localStorage.getItem('access_token')
          if (token) {
            // Marquer immédiatement les flags (synchrone)
            sessionStorage.setItem('sso_redirecting', 'true')
            authStore.isSSORedirecting = true
            return true
          }
        }
      } catch (e) {
        console.error('[App] Error in initial SSO check:', e)
      }
      return false
    })()
    
    // Vérifier directement sessionStorage pour une réactivité immédiate
    const shouldShowSSOOverlay = computed(() => {
      if (typeof window === 'undefined') return false
      
      // Utiliser la vérification initiale (synchrone, avant le montage)
      if (initialSSOCheck) {
        return true
      }
      
      // Vérification SYNCHRONE : si on est sur /login avec paramètres SSO et qu'on a un token
      // Marquer immédiatement les flags pour afficher l'overlay AVANT même checkAuth()
      if (route && route.path === '/login') {
        const hasRedirectParams = route.query && (route.query.redirect || route.query.force_token)
        if (hasRedirectParams) {
          // Vérifier si on a un token de manière synchrone
          const token = localStorage.getItem('access_token')
          if (token) {
            // Si on a un token ET des paramètres SSO, marquer immédiatement (synchrone)
            // Cela affichera l'overlay avant même que checkAuth() ne soit appelé
            if (sessionStorage.getItem('sso_redirecting') !== 'true') {
              sessionStorage.setItem('sso_redirecting', 'true')
              authStore.isSSORedirecting = true
            }
            return true
          }
        }
      }
      
      // Vérifier sessionStorage directement pour une détection immédiate
      const ssoRedirecting = sessionStorage.getItem('sso_redirecting') === 'true'
      if (!ssoRedirecting) return false
      
      // Ne pas afficher sur dashboard ou autres routes sans paramètres
      if (route && route.path && route.path !== '/login' && route.path !== '/register') {
        if (!route.query.redirect && !route.query.force_token) {
          return false
        }
      }
      
      // Vérifier qu'on a bien les paramètres nécessaires
      if (route && route.query && (route.query.redirect || route.query.force_token)) {
        return true
      }
      
      return false
    })
    
    // Vérifier si une redirection SSO est en cours
    const isSSORedirecting = computed(() => {
      // Ne pas afficher l'overlay SSO si on est sur dashboard ou d'autres routes sans paramètres redirect
      if (route && route.path && route.path !== '/login' && route.path !== '/register') {
        // Si on n'est pas sur une page d'auth, ne pas afficher l'overlay SSO
        // Sauf si on a vraiment des paramètres redirect/force_token dans l'URL
        if (!route.query.redirect && !route.query.force_token) {
          return false
        }
      }
      
      // Vérifier sessionStorage pour détecter une redirection SSO en cours
      // Seulement si on est sur /login ou /register avec des paramètres
      if (typeof window !== 'undefined' && sessionStorage.getItem('sso_redirecting') === 'true') {
        // Vérifier qu'on a bien les paramètres nécessaires
        if (route && route.query && (route.query.redirect || route.query.force_token)) {
          return true
        }
        // Si pas de paramètres, ne pas afficher l'overlay
        return false
      }
      // Vérifier aussi le flag dans authStore
      return authStore.isSSORedirecting
    })

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
      console.log('SSO redirecting:', typeof window !== 'undefined' ? sessionStorage.getItem('sso_redirecting') : 'N/A')
      
      // Vérification SYNCHRONE IMMÉDIATE avant tout autre rendu : si on est sur /login avec paramètres SSO et token présent
      // Cette vérification est SYNCHRONE pour éviter que Vue ne rende l'interface
      if (typeof window !== 'undefined' && route && route.path === '/login') {
        const hasRedirectParams = route.query && (route.query.redirect || route.query.force_token)
        if (hasRedirectParams) {
          // Vérifier si on a un token de manière SYNCHRONE (pas await)
          const token = localStorage.getItem('access_token')
          if (token) {
            console.log('[App] Token détecté sur /login avec paramètres SSO, marquer flags immédiatement (synchrone)')
            // Marquer IMMÉDIATEMENT et SYNCHRONEMENT pour afficher l'overlay
            // Cela empêchera Vue de rendre l'interface
            sessionStorage.setItem('sso_redirecting', 'true')
            authStore.isSSORedirecting = true
            // Ne pas continuer le rendu, la redirection sera gérée par Auth.vue
            // L'overlay s'affichera grâce au computed shouldShowSSOOverlay
            return
          }
        }
      }
      
      // Nettoyer le flag SSO si on n'est pas sur une route avec redirect/force_token
      if (typeof window !== 'undefined') {
        const hasRedirectParams = route && route.query && (route.query.redirect || route.query.force_token)
        if (!hasRedirectParams && sessionStorage.getItem('sso_redirecting') === 'true') {
          console.log('[App] Pas de paramètres redirect/force_token, nettoyage du flag SSO')
          sessionStorage.removeItem('sso_redirecting')
          authStore.isSSORedirecting = false
        }
        
        // Vérifier si une redirection SSO est en cours dès le montage
        // Seulement si on a vraiment les paramètres redirect/force_token
        if (sessionStorage.getItem('sso_redirecting') === 'true' && hasRedirectParams) {
          console.log('[App] Redirection SSO détectée avec paramètres, masquer interface')
          // Ne pas continuer le rendu normal, l'overlay sera affiché
          return
        }
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
      isSSORedirecting,
      shouldShowSSOOverlay,
      initialSSOCheck
    }
  }
}
</script>
