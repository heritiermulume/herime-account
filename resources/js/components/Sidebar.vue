<template>
  <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
    <div class="flex-1 flex flex-col min-h-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
      <!-- Logo -->
      <div class="flex items-center h-16 flex-shrink-0 px-4" style="background-color: #003366;">
        <img 
          src="/logo.png" 
          alt="HERIME Logo" 
          class="h-8 w-auto"
        />
      </div>
      
      <!-- Navigation -->
      <div class="flex-1 flex flex-col overflow-y-auto">
        <nav class="flex-1 px-2 py-4 space-y-1">
          <!-- Accueil -->
          <router-link
            to="/dashboard"
            :class="[
              'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200',
              $route.path === '/dashboard'
                ? 'text-white'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
            ]"
            :style="$route.path === '/dashboard' ? 'background-color: #ffcc33; color: #003366;' : ''"
          >
            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
            </svg>
            Accueil
          </router-link>

          <!-- Profile -->
          <router-link
            to="/profile"
            :class="[
              'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200',
              $route.path === '/profile'
                ? 'text-white'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
            ]"
            :style="$route.path === '/profile' ? 'background-color: #ffcc33; color: #003366;' : ''"
          >
            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Profil
          </router-link>

          <!-- Security -->
          <router-link
            to="/security"
            :class="[
              'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200',
              $route.path === '/security'
                ? 'text-white'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
            ]"
            :style="$route.path === '/security' ? 'background-color: #ffcc33; color: #003366;' : ''"
          >
            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            Sécurité
          </router-link>

          <!-- Notifications -->
          <router-link
            to="/notifications"
            :class="[
              'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200',
              $route.path === '/notifications'
                ? 'text-white'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
            ]"
            :style="$route.path === '/notifications' ? 'background-color: #ffcc33; color: #003366;' : ''"
          >
            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12 7H4.828zM4.828 17l2.586-2.586a2 2 0 012.828 0L12 17H4.828z"></path>
            </svg>
            Notifications
          </router-link>

          <!-- About -->
          <router-link
            to="/about"
            :class="[
              'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200',
              $route.path === '/about'
                ? 'text-white'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
            ]"
            :style="$route.path === '/about' ? 'background-color: #ffcc33; color: #003366;' : ''"
          >
            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            À propos
          </router-link>

          <!-- Administration - Only for super users and admins -->
              <router-link
                v-if="user && ['admin', 'super_user'].includes(user.role)"
                to="/admin/dashboard"
                :class="[
                  'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                  $route.path.startsWith('/admin')
                    ? 'text-white'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                ]"
                :style="$route.path.startsWith('/admin') ? 'background-color: #ffcc33; color: #003366;' : ''"
              >
            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            Administration
          </router-link>
        </nav>
      </div>
    </div>
  </div>

  <!-- Mobile sidebar -->
  <div v-if="mobileMenuOpen" class="fixed inset-0 flex z-40 md:hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="mobileMenuOpen = false"></div>
    
    <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white dark:bg-gray-800">
      <div class="absolute top-0 right-0 -mr-12 pt-2">
        <button
          @click="mobileMenuOpen = false"
          class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
        >
          <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
        <div class="flex-shrink-0 flex items-center px-4">
          <img 
            src="/logo.png" 
            alt="HERIME Logo" 
            class="h-8 w-auto"
          />
        </div>
        <nav class="mt-5 px-2 space-y-1">
          <!-- Mobile navigation items -->
          <router-link
            to="/dashboard"
            @click="mobileMenuOpen = false"
            :class="[
              'group flex items-center px-2 py-2 text-base font-medium rounded-md',
              $route.path === '/dashboard'
                ? 'text-white'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
            ]"
          >
            <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
            </svg>
            Accueil
          </router-link>

          <router-link
            to="/profile"
            @click="mobileMenuOpen = false"
            :class="[
              'group flex items-center px-2 py-2 text-base font-medium rounded-md',
              $route.path === '/profile'
                ? 'text-white'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
            ]"
          >
            <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Profil
          </router-link>

          <router-link
            to="/security"
            @click="mobileMenuOpen = false"
            :class="[
              'group flex items-center px-2 py-2 text-base font-medium rounded-md',
              $route.path === '/security'
                ? 'text-white'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
            ]"
          >
            <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            Sécurité
          </router-link>

          <router-link
            to="/notifications"
            @click="mobileMenuOpen = false"
            :class="[
              'group flex items-center px-2 py-2 text-base font-medium rounded-md',
              $route.path === '/notifications'
                ? 'text-white'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
            ]"
          >
            <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12 7H4.828zM4.828 17l2.586-2.586a2 2 0 012.828 0L12 17H4.828z"></path>
            </svg>
            Notifications
          </router-link>

          <router-link
            to="/about"
            @click="mobileMenuOpen = false"
            :class="[
              'group flex items-center px-2 py-2 text-base font-medium rounded-md',
              $route.path === '/about'
                ? 'text-white'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
            ]"
          >
            <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            À propos
          </router-link>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, computed } from 'vue'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'Sidebar',
  props: {
    mobileMenuOpen: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:mobileMenuOpen'],
  setup(props, { emit }) {
    const authStore = useAuthStore()
    const localMobileMenuOpen = ref(props.mobileMenuOpen)

    const user = computed(() => authStore.user)

    // Watch for prop changes
    watch(() => props.mobileMenuOpen, (newValue) => {
      localMobileMenuOpen.value = newValue
    })

    // Watch for local changes and emit to parent
    watch(localMobileMenuOpen, (newValue) => {
      emit('update:mobileMenuOpen', newValue)
    })

    return {
      mobileMenuOpen: localMobileMenuOpen,
      user
    }
  }
}
</script>
