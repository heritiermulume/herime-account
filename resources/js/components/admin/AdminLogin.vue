<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div v-if="loading" class="text-center">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-yellow-400">
          <svg class="animate-spin h-8 w-8 text-gray-900" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Vérification des permissions...
        </h2>
      </div>

      <div v-else-if="!isAuthenticated" class="text-center">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-red-400">
          <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Accès refusé
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          Vous devez être connecté pour accéder à l'administration
        </p>
        <div class="mt-6">
          <router-link 
            to="/login" 
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Se connecter
          </router-link>
        </div>
      </div>

      <div v-else-if="!isSuperUser" class="text-center">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-red-400">
          <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
          </svg>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Accès refusé
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          Seuls les super utilisateurs peuvent accéder à l'administration
        </p>
        <div class="mt-6">
          <router-link 
            to="/" 
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Retour à l'accueil
          </router-link>
        </div>
      </div>

      <div v-else class="text-center">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-400">
          <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Redirection...
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          Accès autorisé, redirection vers le tableau de bord
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'AdminLogin',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const loading = ref(true)
    
    const isAuthenticated = computed(() => authStore.authenticated)
    const isSuperUser = computed(() => authStore.user?.role === 'super_user')

    onMounted(async () => {
      // Vérifier l'authentification
      await authStore.checkAuth()
      
      loading.value = false
      
      // Si l'utilisateur est authentifié et super utilisateur, rediriger vers le dashboard
      if (isAuthenticated.value && isSuperUser.value) {
        setTimeout(() => {
          router.push('/admin/dashboard')
        }, 1000)
      }
    })

    return {
      loading,
      isAuthenticated,
      isSuperUser
    }
  }
}
</script>