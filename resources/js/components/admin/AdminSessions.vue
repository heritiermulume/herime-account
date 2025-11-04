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
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-md sm:rounded-lg">
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
                    @click="openRevoke(session)"
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

        <!-- Revoke Session Modal -->
        <teleport to="body">
          <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showRevoke" class="fixed inset-0 z-50 flex items-center justify-center">
              <div class="fixed inset-0 bg-black bg-opacity-50" @click="showRevoke = false"></div>
              <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">Confirmer la révocation</h3>
                  <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" @click="showRevoke = false" aria-label="Fermer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                  </button>
                </div>
                <div class="flex items-start space-x-3">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-red-300" viewBox="0 0 24 24" fill="currentColor"><path d="M12 9v4m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                    </div>
                  </div>
                  <div>
                    <p class="text-sm text-gray-700 dark:text-gray-200">Voulez-vous vraiment révoquer cette session ?</p>
                    <div v-if="revokeTarget" class="mt-2 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                      <div><span class="text-gray-500">Utilisateur:</span> {{ revokeTarget.user?.name || 'Inconnu' }}</div>
                      <div><span class="text-gray-500">Appareil:</span> {{ revokeTarget.device_name }} - {{ revokeTarget.platform }} - {{ revokeTarget.browser }}</div>
                      <div><span class="text-gray-500">IP:</span> {{ revokeTarget.ip_address }}</div>
                    </div>
                    <p v-if="revokeError" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ revokeError }}</p>
                  </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                  <button class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600" @click="showRevoke = false">Annuler</button>
                  <button :disabled="revokeLoading" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-50" @click="confirmRevoke">
                    <span v-if="revokeLoading">Révocation...</span>
                    <span v-else>Révoquer</span>
                  </button>
                </div>
              </div>
            </div>
          </transition>
        </teleport>
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

    // Revoke modal state
    const showRevoke = ref(false)
    const revokeTarget = ref(null)
    const revokeLoading = ref(false)
    const revokeError = ref('')

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

    const openRevoke = (session) => {
      revokeTarget.value = session
      revokeError.value = ''
      showRevoke.value = true
    }

    const confirmRevoke = async () => {
      if (!revokeTarget.value) return
      revokeLoading.value = true
      revokeError.value = ''
      try {
        const response = await axios.delete(`/admin/sessions/${revokeTarget.value.id}`)
        if (response.data.success) {
          showRevoke.value = false
          revokeTarget.value = null
          await fetchSessions()
        } else {
          revokeError.value = response.data.message || 'Révocation échouée'
        }
      } catch (err) {
        console.error('Error revoking session:', err)
        revokeError.value = 'Erreur lors de la révocation de la session'
      } finally {
        revokeLoading.value = false
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
      openRevoke,
      confirmRevoke,
      showRevoke,
      revokeTarget,
      revokeLoading,
      revokeError,
      formatDate
    }
  }
}
</script>

