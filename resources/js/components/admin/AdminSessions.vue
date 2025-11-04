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
        <!-- Sessions Table -->
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
          <div v-if="loading" class="flex justify-center items-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-500"></div>
          </div>

          <div v-else-if="error" class="px-4 py-5 sm:p-6">
            <div class="rounded-md bg-red-50 dark:bg-red-900 p-4">
              <div class="text-sm text-red-800 dark:text-red-200">{{ error }}</div>
            </div>
          </div>

          <div v-else-if="sessions.length === 0" class="px-4 py-5 sm:p-6 text-center">
            <p class="text-gray-500 dark:text-gray-400">Aucune session trouvée</p>
          </div>

          <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
            <li v-for="session in paginatedSessions" :key="session.id" class="px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ session.user?.name || 'Utilisateur inconnu' }}
                      <span v-if="session.is_current" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Active
                      </span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ session.user?.email || '' }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ session.device_name }} - {{ session.platform }} - {{ session.browser }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ session.ip_address }}
                    </div>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatDate(session.created_at) }}
                  </span>
                  <button
                    @click="revokeSession(session.id)"
                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-xs font-medium"
                  >
                    Révoquer
                  </button>
                </div>
              </div>
            </li>
          </ul>
          <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <Pagination :page="page" :perPage="15" :total="sessions.length" @update:page="val => page = val" />
          </div>
        </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'
import Pagination from '../Pagination.vue'

export default {
  name: 'AdminSessions',
  components: { Pagination },
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const loading = ref(true)
    const sessions = ref([])
    const error = ref('')
    const page = ref(1)
    const perPage = ref(15)
    const paginatedSessions = computed(() => {
      const start = (page.value - 1) * perPage.value
      return sessions.value.slice(start, start + perPage.value)
    })
    
    const user = computed(() => authStore.user)

    const fetchSessions = async () => {
      try {
        loading.value = true
        const response = await axios.get('/admin/sessions')
        
        if (response.data.success) {
          sessions.value = response.data.data.data || response.data.data
        }
      } catch (err) {
        console.error('Error fetching sessions:', err)
        error.value = 'Erreur lors du chargement des sessions'
      } finally {
        loading.value = false
      }
    }

    const revokeSession = async (id) => {
      if (!confirm('Êtes-vous sûr de vouloir révoquer cette session ?')) {
        return
      }
      
      try {
        const response = await axios.delete(`/admin/sessions/${id}`)
        
        if (response.data.success) {
          await fetchSessions()
        }
      } catch (err) {
        console.error('Error revoking session:', err)
        error.value = 'Erreur lors de la révocation de la session'
      }
    }

    const formatDate = (date) => {
      if (!date) return 'Jamais'
      return new Date(date).toLocaleString('fr-FR')
    }

    onMounted(() => {
      if (!user.value || user.value.role !== 'super_user') {
        router.push('/')
        return
      }
      
      fetchSessions()
    })

    return {
      loading,
      sessions,
      error,
      page,
      perPage,
      paginatedSessions,
      user,
      fetchSessions,
      revokeSession,
      formatDate
    }
  }
}
</script>

