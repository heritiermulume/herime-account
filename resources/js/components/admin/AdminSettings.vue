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
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
              Paramètres Système
            </h3>

            <div v-if="loading" class="flex justify-center items-center h-64">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-500"></div>
            </div>

            <div v-else-if="error" class="rounded-md bg-red-50 dark:bg-red-900 p-4 mb-4">
              <div class="text-sm text-red-800 dark:text-red-200">{{ error }}</div>
            </div>

            <div v-else class="space-y-6">
              <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nom de l'application
                  </label>
                  <input
                    v-model="settings.app_name"
                    type="text"
                    disabled
                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    URL de l'application
                  </label>
                  <input
                    v-model="settings.app_url"
                    type="text"
                    disabled
                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  />
                </div>

                <div>
                  <label class="flex items-center">
                    <input
                      v-model="settings.maintenance_mode"
                      type="checkbox"
                      @change="updateSettings"
                      class="rounded border-gray-300 text-yellow-600 shadow-sm focus:border-yellow-300 focus:ring focus:ring-yellow-200 focus:ring-opacity-50"
                    />
                    <span class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Mode maintenance
                    </span>
                  </label>
                </div>

                <div>
                  <label class="flex items-center">
                    <input
                      v-model="settings.registration_enabled"
                      type="checkbox"
                      @change="updateSettings"
                      class="rounded border-gray-300 text-yellow-600 shadow-sm focus:border-yellow-300 focus:ring focus:ring-yellow-200 focus:ring-opacity-50"
                    />
                    <span class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Inscription activée
                    </span>
                  </label>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Sessions max par utilisateur
                  </label>
                  <input
                    v-model.number="settings.max_sessions_per_user"
                    type="number"
                    min="1"
                    max="10"
                    @change="updateSettings"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Délai d'expiration (heures)
                  </label>
                  <input
                    v-model.number="settings.session_timeout"
                    type="number"
                    min="1"
                    max="168"
                    @change="updateSettings"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  />
                </div>
              </div>

              <div v-if="successMessage" class="rounded-md bg-green-50 dark:bg-green-900 p-4">
                <div class="text-sm text-green-800 dark:text-green-200">{{ successMessage }}</div>
              </div>
            </div>
          </div>
        </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'

export default {
  name: 'AdminSettings',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const loading = ref(true)
    const error = ref('')
    const successMessage = ref('')
    
    const user = computed(() => authStore.user)
    
    const settings = reactive({
      app_name: '',
      app_url: '',
      maintenance_mode: false,
      registration_enabled: true,
      max_sessions_per_user: 5,
      session_timeout: 24
    })

    const fetchSettings = async () => {
      try {
        loading.value = true
        const response = await axios.get('/admin/settings')
        
        if (response.data.success) {
          Object.assign(settings, response.data.data)
        }
      } catch (err) {
        error.value = 'Erreur lors du chargement des paramètres'
      } finally {
        loading.value = false
      }
    }

    const updateSettings = async () => {
      try {
        error.value = ''
        successMessage.value = ''
        
        const response = await axios.put('/admin/settings', settings)
        
        if (response.data.success) {
          successMessage.value = 'Paramètres mis à jour avec succès'
          setTimeout(() => {
            successMessage.value = ''
          }, 3000)
        }
      } catch (err) {
        error.value = 'Erreur lors de la mise à jour des paramètres'
      }
    }

    onMounted(() => {
      if (!user.value || !['admin', 'super_user'].includes(user.value.role)) {
        router.push('/')
        return
      }
      
      fetchSettings()
    })

    return {
      loading,
      settings,
      error,
      successMessage,
      user,
      fetchSettings,
      updateSettings
    }
  }
}
</script>

