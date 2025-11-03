<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="!user" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2" style="border-color: #003366;"></div>
          <span class="ml-3 text-gray-700 dark:text-gray-300">Chargement du dashboard...</span>
        </div>
      </div>
    </div>

    <!-- Welcome Section -->
    <div v-else class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div v-if="user.avatar_url" class="h-16 w-16 rounded-full overflow-hidden">
              <img
                :src="user.avatar_url"
                :alt="user.name"
                class="h-full w-full object-cover"
              />
            </div>
            <div v-else class="h-16 w-16 rounded-full flex items-center justify-center" style="background-color: #ffcc33;">
              <svg class="h-10 w-10" style="color: #003366;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                Bienvenue
              </dt>
              <dd class="text-lg font-medium text-gray-900 dark:text-white">
                {{ user.name }}
              </dd>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6" style="color: #ffcc33;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  Sessions actives
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ sessions.length }}
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
              <svg class="h-6 w-6" style="color: #ffcc33;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  Statut du compte
                </dt>
                <dd class="text-lg font-medium" style="color: #ffcc33;">
                  Actif
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
              <svg class="h-6 w-6" style="color: #ffcc33;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  Dernière connexion
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ formatDate(user.last_login_at) }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
          Actions rapides
        </h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <router-link
            to="/profile"
            class="relative rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-6 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-herime-blue-500 transition-all duration-200 hover:shadow-md"
          >
            <div class="flex items-center">
              <svg class="h-6 w-6" style="color: #ffcc33;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                Modifier le profil
              </span>
            </div>
          </router-link>

          <router-link
            to="/security"
            class="relative rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-6 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-herime-blue-500 transition-all duration-200 hover:shadow-md"
          >
            <div class="flex items-center">
              <svg class="h-6 w-6" style="color: #ffcc33;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
              </svg>
              <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                Sécurité
              </span>
            </div>
          </router-link>

          <router-link
            to="/notifications"
            class="relative rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-6 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-herime-blue-500 transition-all duration-200 hover:shadow-md"
          >
            <div class="flex items-center">
              <svg class="h-6 w-6" style="color: #ffcc33;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12 7H4.828zM4.828 17l2.586-2.586a2 2 0 012.828 0L12 17H4.828z"></path>
              </svg>
              <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                Notifications
              </span>
            </div>
          </router-link>

          <router-link
            to="/about"
            class="relative rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-6 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-herime-blue-500 transition-all duration-200 hover:shadow-md"
          >
            <div class="flex items-center">
              <svg class="h-6 w-6" style="color: #ffcc33;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                À propos
              </span>
            </div>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Recent Sessions -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
          Sessions récentes
        </h3>
        <div class="flow-root">
          <ul class="-mb-8">
            <li v-for="(session, index) in sessions.slice(0, 5)" :key="session.id">
              <div class="relative pb-8">
                <div v-if="index !== sessions.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600"></div>
                <div class="relative flex space-x-3">
                  <div>
                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800" style="background-color: #003366;">
                      <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                      </svg>
                    </span>
                  </div>
                  <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                    <div>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ session.device_name }} • {{ session.platform }} • {{ session.browser }}
                      </p>
                      <p class="text-sm text-gray-900 dark:text-white">
                        {{ session.ip_address }}
                      </p>
                    </div>
                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                      <time :datetime="session.last_activity">
                        {{ formatDate(session.last_activity) }}
                      </time>
                    </div>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ProfileModal v-if="showProfileModal" @close="showProfileModal = false" />
    <SessionsModal v-if="showSessionsModal" @close="showSessionsModal = false" />
    <SecurityModal v-if="showSecurityModal" @close="showSecurityModal = false" />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import ProfileModal from './ProfileModal.vue'
import SessionsModal from './SessionsModal.vue'
import SecurityModal from './SecurityModal.vue'

export default {
  name: 'Dashboard',
  components: {
    ProfileModal,
    SessionsModal,
    SecurityModal
  },
  setup() {
    const authStore = useAuthStore()
    
    const showProfileModal = ref(false)
    const showSessionsModal = ref(false)
    const showSecurityModal = ref(false)
    const sessions = ref([])

    const user = computed(() => authStore.user)

    const formatDate = (date) => {
      if (!date) return 'Jamais'
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const loadSessions = async () => {
      try {
        const response = await axios.get('/sso/sessions')
        if (response.data.success) {
          sessions.value = response.data.data.sessions
        }
      } catch (error) {
        console.error('Error loading sessions:', error)
      }
    }

    onMounted(async () => {
      console.log('Dashboard mounted, user:', authStore.user)
      console.log('Authenticated:', authStore.authenticated)
      
      // Load user if not already loaded
      if (!authStore.user) {
        console.log('Loading user...')
        await authStore.checkAuth()
        console.log('User after checkAuth:', authStore.user)
      }
      loadSessions()
    })

    return {
      user,
      sessions,
      showProfileModal,
      showSessionsModal,
      showSecurityModal,
      formatDate
    }
  }
}
</script>
