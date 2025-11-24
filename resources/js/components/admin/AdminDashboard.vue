<template>
  <div class="space-y-6">
    <nav class="bg-white dark:bg-gray-800 shadow">
      <div class="flex space-x-8">
          <router-link
            to="/admin/dashboard"
            :class="[
              'border-b-2 py-4 px-1 text-sm font-medium',
              $route.path === '/admin/dashboard'
                ? 'border-yellow-500 text-yellow-600 dark:text-yellow-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            Tableau de bord
          </router-link>
          <router-link
            to="/admin/users"
            :class="[
              'border-b-2 py-4 px-1 text-sm font-medium',
              $route.path === '/admin/users'
                ? 'border-yellow-500 text-yellow-600 dark:text-yellow-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            Utilisateurs
          </router-link>
          <router-link
            to="/admin/sessions"
            :class="[
              'border-b-2 py-4 px-1 text-sm font-medium',
              $route.path === '/admin/sessions'
                ? 'border-yellow-500 text-yellow-600 dark:text-yellow-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            Sessions
          </router-link>
          <router-link
            to="/admin/settings"
            :class="[
              'border-b-2 py-4 px-1 text-sm font-medium',
              $route.path === '/admin/settings'
                ? 'border-yellow-500 text-yellow-600 dark:text-yellow-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            Paramètres
          </router-link>
      </div>
    </nav>

    <!-- Main Content -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-500"></div>
    </div>

    <div v-else>
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Total Utilisateurs
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats?.total_users || 0 }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Utilisateurs Actifs
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats?.active_users || 0 }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Sessions Actives
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats?.active_sessions || 0 }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Super Utilisateurs
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats?.super_users || 0 }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Activité Récente
            </h3>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              <p>Nouveaux utilisateurs cette semaine : {{ stats?.recent_users || 0 }}</p>
              <p>Total des sessions : {{ stats?.total_sessions || 0 }}</p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
            Actions Rapides
          </h3>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <router-link
              to="/admin/users"
              class="relative group bg-white dark:bg-gray-800 p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-yellow-500 rounded-lg shadow hover:shadow-md transition-shadow"
            >
              <div>
                <span class="rounded-lg inline-flex p-3 bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-400 ring-4 ring-white dark:ring-gray-800">
                  <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                  </svg>
                </span>
              </div>
              <div class="mt-8">
                <h3 class="text-lg font-medium text-white">
                  <span class="absolute inset-0" aria-hidden="true"></span>
                  Gérer les Utilisateurs
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  Voir, modifier et gérer tous les utilisateurs du système
                </p>
              </div>
            </router-link>

            <router-link
              to="/admin/sessions"
              class="relative group bg-white dark:bg-gray-800 p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-yellow-500 rounded-lg shadow hover:shadow-md transition-shadow"
            >
              <div>
                <span class="rounded-lg inline-flex p-3 bg-green-50 dark:bg-green-900 text-green-600 dark:text-green-400 ring-4 ring-white dark:ring-gray-800">
                  <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                  </svg>
                </span>
              </div>
              <div class="mt-8">
                <h3 class="text-lg font-medium text-white">
                  <span class="absolute inset-0" aria-hidden="true"></span>
                  Gérer les Sessions
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  Surveiller et gérer les sessions utilisateur actives
                </p>
              </div>
            </router-link>

            <router-link
              to="/admin/settings"
              class="relative group bg-white dark:bg-gray-800 p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-yellow-500 rounded-lg shadow hover:shadow-md transition-shadow"
            >
              <div>
                <span class="rounded-lg inline-flex p-3 bg-yellow-50 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 ring-4 ring-white dark:ring-gray-800">
                  <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                </span>
              </div>
              <div class="mt-8">
                <h3 class="text-lg font-medium text-white">
                  <span class="absolute inset-0" aria-hidden="true"></span>
                  Paramètres Système
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  Configurer les paramètres globaux du système
                </p>
              </div>
            </router-link>
          </div>
        </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'

export default {
  name: 'AdminDashboard',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const loading = ref(true)
    const stats = ref(null)
    const error = ref('')
    
    const user = computed(() => authStore.user)

    const fetchDashboardData = async () => {
      try {
        const response = await axios.get('/admin/dashboard')
        if (response.data.success) {
          stats.value = response.data.data.stats
        }
      } catch (err) {
        error.value = 'Erreur lors du chargement des données'
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      // Vérifier si l'utilisateur est un super utilisateur ou admin
      if (!user.value || !['admin', 'super_user'].includes(user.value.role)) {
        router.push('/')
        return
      }
      
      fetchDashboardData()
    })

    return {
      loading,
      stats,
      error,
      user
    }
  }
}
</script>