<template>
  <div id="app" class="min-h-screen bg-gray-50 dark:bg-gray-900">

    <!-- Loading State -->
    <div v-if="loading" class="min-h-screen flex items-center justify-center">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2" style="border-color: #003366;"></div>
        <span class="text-gray-700 dark:text-gray-300">Chargement...</span>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="min-h-screen">
        <!-- Sidebar -->
        <Sidebar v-if="user" />
      
      <!-- Main Content -->
      <div :class="user ? 'md:pl-64' : ''" class="flex flex-col flex-1">
        <!-- Top Navigation - Only show when user is logged in -->
        <nav v-if="user" class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
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
                <button
                  @click="toggleDarkMode"
                  class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                  <svg v-if="!isDarkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                  </svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                  </svg>
                </button>
                
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

    <!-- Mobile Navigation -->
    <MobileNavigation v-if="user" />

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
      return route.path !== '/dashboard'
    })

    const toggleDarkMode = () => {
      isDarkMode.value = !isDarkMode.value
      document.documentElement.classList.toggle('dark', isDarkMode.value)
      localStorage.setItem('darkMode', isDarkMode.value)
    }

    const logout = async () => {
      loading.value = true
      try {
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
      console.log('Current route:', route.path)
      
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
      if (!user.value?.avatar_url || user.value.avatar_url === '') {
        return null
      }
      
      // Si c'est déjà une URL complète (commence par http), la retourner telle quelle
      if (user.value.avatar_url.startsWith('http')) {
        return user.value.avatar_url
      }
      
      // Construire l'URL vers l'API sécurisée
      if (user.value?.id) {
        return `/api/user/avatar/${user.value.id}`
      }
      
      return user.value.avatar_url
    }

    const handleImageError = (event) => {
      console.error('❌ Image load error in App:', event.target.src)
    }

    return {
      loading,
      isDarkMode,
      user,
      pageTitle,
      showPageTitle,
      toggleDarkMode,
      logout,
      authStore,
      toastContainer,
      getAvatarUrl,
      handleImageError
    }
  }
}
</script>
