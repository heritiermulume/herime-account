<template>
  <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 z-50">
    <nav class="flex justify-around items-center py-2">
      <!-- Accueil -->
      <router-link
        to="/dashboard"
        :class="[
          'flex flex-col items-center py-2 px-3 rounded-lg transition-colors duration-200',
          $route.path === '/dashboard'
            ? 'text-white'
            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
        ]"
        :style="$route.path === '/dashboard' ? 'background-color: #ffcc33; color: #003366;' : ''"
      >
        <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
        </svg>
        <span class="text-xs font-medium">Accueil</span>
      </router-link>

      <!-- Profile -->
      <router-link
        to="/profile"
        :class="[
          'flex flex-col items-center py-2 px-3 rounded-lg transition-colors duration-200',
          $route.path === '/profile'
            ? 'text-white'
            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
        ]"
        :style="$route.path === '/profile' ? 'background-color: #ffcc33; color: #003366;' : ''"
      >
        <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
        <span class="text-xs font-medium">Profil</span>
      </router-link>

      <!-- Security -->
      <router-link
        to="/security"
        :class="[
          'flex flex-col items-center py-2 px-3 rounded-lg transition-colors duration-200',
          $route.path === '/security'
            ? 'text-white'
            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
        ]"
        :style="$route.path === '/security' ? 'background-color: #ffcc33; color: #003366;' : ''"
      >
        <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
        </svg>
        <span class="text-xs font-medium">Sécurité</span>
      </router-link>

      <!-- Notifications -->
      <router-link
        to="/notifications"
        :class="[
          'flex flex-col items-center py-2 px-3 rounded-lg transition-colors duration-200',
          $route.path === '/notifications'
            ? 'text-white'
            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
        ]"
        :style="$route.path === '/notifications' ? 'background-color: #ffcc33; color: #003366;' : ''"
      >
        <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12 7H4.828zM4.828 17l2.586-2.586a2 2 0 012.828 0L12 17H4.828z"></path>
        </svg>
        <span class="text-xs font-medium">Notifications</span>
      </router-link>

              <!-- About -->
              <router-link
                to="/about"
                :class="[
                  'flex flex-col items-center py-2 px-3 rounded-lg transition-colors duration-200',
                  $route.path === '/about'
                    ? 'text-white'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                ]"
                :style="$route.path === '/about' ? 'background-color: #ffcc33; color: #003366;' : ''"
              >
                <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-xs font-medium">À propos</span>
              </router-link>

              <!-- Administration - Only for super users -->
             <router-link
               v-if="user && user.role === 'super_user'"
               to="/admin/dashboard"
               :class="[
                 'flex flex-col items-center py-2 px-3 rounded-lg transition-colors duration-200',
                 $route.path.startsWith('/admin/')
                   ? 'text-white'
                   : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
               ]"
               :style="$route.path.startsWith('/admin/') ? 'background-color: #ffcc33; color: #003366;' : ''"
             >
                <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span class="text-xs font-medium">Admin</span>
              </router-link>
            </nav>
          </div>
        </template>

<script>
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'MobileNavigation',
  setup() {
    const authStore = useAuthStore()
    
    const user = computed(() => authStore.user)

    return {
      user
    }
  }
}
</script>
