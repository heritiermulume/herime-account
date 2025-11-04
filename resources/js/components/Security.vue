<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sécurité</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Gérez vos identifiants, l'authentification à deux facteurs et les appareils connectés
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Password Section -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Mot de passe
        </h3>
        
        <form @submit.prevent="updatePassword" class="space-y-6">
          <!-- Current Password -->
          <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Mot de passe actuel
            </label>
            <input
              id="current_password"
              v-model="passwordForm.current_password"
              type="password"
              required
              placeholder="Entrez votre mot de passe actuel"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
          </div>

          <!-- New Password -->
          <div>
            <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Nouveau mot de passe
            </label>
            <input
              id="new_password"
              v-model="passwordForm.new_password"
              type="password"
              required
              minlength="8"
              placeholder="Entrez votre nouveau mot de passe"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Le mot de passe doit contenir au moins 8 caractères
            </p>
          </div>

          <!-- Confirm Password -->
          <div>
            <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Confirmer le nouveau mot de passe
            </label>
            <input
              id="confirm_password"
              v-model="passwordForm.confirm_password"
              type="password"
              required
              placeholder="Confirmez votre nouveau mot de passe"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="passwordLoading"
              class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
              style="background-color: #003366; focus:ring-color: #003366;"
              @mouseenter="$event.target.style.backgroundColor = '#ffcc33'"
              @mouseleave="$event.target.style.backgroundColor = '#003366'"
            >
              <div v-if="passwordLoading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              {{ passwordLoading ? 'Mise à jour...' : 'Mettre à jour le mot de passe' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Two-Factor Authentication -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Authentification à deux facteurs
        </h3>
        
        <div class="space-y-4">
          <!-- 2FA Status -->
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                Authentification à deux facteurs
              </h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Ajoutez une couche de sécurité supplémentaire à votre compte
              </p>
            </div>
            <div class="flex items-center space-x-3">
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                twoFactorEnabled ? 'text-white' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
              ]"
              :style="twoFactorEnabled ? 'background-color: #ffcc33;' : ''"
              >
                {{ twoFactorEnabled ? 'Activé' : 'Désactivé' }}
              </span>
              <button
                @click="toggleTwoFactor"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                  twoFactorEnabled ? 'bg-herime-600' : 'bg-gray-200 dark:bg-gray-600'
                ]"
                :style="twoFactorEnabled ? 'background-color: #003366;' : ''"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    twoFactorEnabled ? 'translate-x-5' : 'translate-x-0'
                  ]"
                />
              </button>
            </div>
          </div>

          <!-- 2FA Setup (if disabled) -->
          <div v-if="!twoFactorEnabled" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                  Authentification à deux facteurs recommandée
                </h3>
                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                  <p>Activez l'authentification à deux facteurs pour renforcer la sécurité de votre compte.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Connected Devices -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Appareils connectés
        </h3>
        
        <div class="space-y-4">
          <div v-for="device in devices" :key="device.id" class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
            <div class="flex items-center space-x-4">
              <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ device.device_name }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ device.platform }} • {{ device.browser }} • {{ device.ip_address }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                  Dernière activité: {{ formatDate(device.last_activity) }}
                </p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span v-if="device.is_current" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white" style="background-color: #ffcc33;">
                Appareil actuel
              </span>
              <button
                v-if="!device.is_current"
                @click="revokeDevice(device.id)"
                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
              >
                Révoquer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Login History -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Historique de connexion
        </h3>
        
        <div class="flow-root">
          <ul class="-mb-8">
            <li v-for="(login, index) in loginHistory" :key="login.id">
              <div class="relative pb-8">
                <div v-if="index !== loginHistory.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600"></div>
                <div class="relative flex space-x-3">
                  <div>
                    <span :class="[
                      'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800',
                      login.success ? 'bg-green-500' : 'bg-red-500'
                    ]">
                      <svg v-if="login.success" class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </span>
                  </div>
                  <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                    <div>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ login.device_name }} • {{ login.platform }} • {{ login.browser }}
                      </p>
                      <p class="text-sm text-gray-900 dark:text-white">
                        {{ login.ip_address }}
                      </p>
                    </div>
                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                      <time :datetime="login.created_at">
                        {{ formatDate(login.created_at) }}
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
  </div>
</template>

<script>
import { ref, reactive, onMounted, inject } from 'vue'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'Security',
  setup() {
    const authStore = useAuthStore()
    const notify = inject('notify')
    const passwordLoading = ref(false)
    const twoFactorEnabled = ref(false)
    const devices = ref([])
    const loginHistory = ref([])

    const passwordForm = reactive({
      current_password: '',
      new_password: '',
      confirm_password: ''
    })

    const updatePassword = async () => {
      if (passwordForm.new_password !== passwordForm.confirm_password) {
        notify.error('Erreur', 'Les mots de passe ne correspondent pas')
        return
      }

      passwordLoading.value = true
      try {
        await axios.put('/user/password', {
          current_password: passwordForm.current_password,
          password: passwordForm.new_password,
          password_confirmation: passwordForm.confirm_password
        })
        
        notify.success('Succès', 'Mot de passe mis à jour avec succès!')
        
        // Reset form
        Object.assign(passwordForm, {
          current_password: '',
          new_password: '',
          confirm_password: ''
        })
      } catch (error) {
        console.error('Error updating password:', error)
        if (error.response?.data?.message) {
          notify.error('Erreur', error.response.data.message)
        } else {
          notify.error('Erreur', 'Erreur lors de la mise à jour du mot de passe')
        }
      } finally {
        passwordLoading.value = false
      }
    }

    const toggleTwoFactor = async () => {
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 500))
        twoFactorEnabled.value = !twoFactorEnabled.value
        notify.success('Succès', twoFactorEnabled.value ? '2FA activé!' : '2FA désactivé!')
      } catch (error) {
        console.error('Error toggling 2FA:', error)
        notify.error('Erreur', 'Erreur lors de la modification de la 2FA')
      }
    }

    const revokeDevice = async (deviceId) => {
      if (confirm('Êtes-vous sûr de vouloir révoquer cet appareil?')) {
        try {
          await axios.delete(`/sso/sessions/${deviceId}`)
          devices.value = devices.value.filter(device => device.id !== deviceId)
          notify.success('Succès', 'Appareil révoqué avec succès!')
        } catch (error) {
          console.error('Error revoking device:', error)
          if (error.response?.data?.message) {
            notify.error('Erreur', error.response.data.message)
          } else {
            notify.error('Erreur', 'Erreur lors de la révocation de l\'appareil')
          }
        }
      }
    }

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

    const loadDevices = async () => {
      try {
        const response = await axios.get('/sso/sessions')
        if (response.data.success) {
          devices.value = response.data.data.sessions
        }
      } catch (error) {
        console.error('Error loading devices:', error)
        notify.error('Erreur', 'Erreur lors du chargement des appareils')
      }
    }

    const loadLoginHistory = async () => {
      try {
        console.log('🔄 Loading login history from API...')
        // Use the same sessions data for login history since we don't have a separate endpoint
        const response = await axios.get('/sso/sessions')
        console.log('✅ Login history API response:', response.status, response.data)
        
        if (response.data && response.data.success && response.data.data && Array.isArray(response.data.data.sessions)) {
          loginHistory.value = response.data.data.sessions.map(session => {
            // S'assurer que toutes les propriétés nécessaires existent avec des valeurs par défaut
            return {
              id: session.id || null,
              device_name: session.device_name || 'Unknown Device',
              platform: session.platform || 'Unknown',
              browser: session.browser || 'Unknown',
              ip_address: session.ip_address || 'Unknown',
              success: true, // All sessions are successful by definition
              created_at: session.created_at || session.last_activity || null,
              last_activity: session.last_activity || session.created_at || null
            }
          }).filter(session => session.id !== null) // Filtrer les sessions invalides
          console.log('📊 Login history loaded:', loginHistory.value.length, 'entries')
        } else {
          console.warn('⚠️ Login history response not successful or missing data:', response.data)
          loginHistory.value = []
          // Ne pas afficher d'erreur si c'est juste qu'il n'y a pas de sessions
          if (response.data && response.data.success && (!response.data.data || !response.data.data.sessions || response.data.data.sessions.length === 0)) {
            console.log('   No sessions available (normal for new users)')
          } else {
            notify.warning('Avertissement', 'Impossible de charger l\'historique de connexion')
          }
        }
      } catch (error) {
        console.error('❌ Error loading login history:', error)
        console.error('   Status:', error.response?.status)
        console.error('   Message:', error.response?.data?.message || error.message)
        console.error('   Data:', error.response?.data)
        console.error('   Error details:', error)
        
        // Ne pas afficher l'erreur si c'est juste qu'il n'y a pas de sessions ou si l'utilisateur n'est pas autorisé
        if (error.response?.status === 404 || error.response?.status === 401) {
          console.warn('   No sessions found or unauthorized')
          loginHistory.value = []
        } else if (error.response?.status === 500) {
          console.error('   Server error - check logs')
          notify.error('Erreur serveur', 'Erreur lors du chargement de l\'historique de connexion. Veuillez réessayer.')
          loginHistory.value = []
        } else {
          notify.error('Erreur', 'Erreur lors du chargement de l\'historique de connexion')
          loginHistory.value = []
        }
      }
    }

    onMounted(() => {
      loadDevices()
      loadLoginHistory()
    })

    return {
      passwordForm,
      passwordLoading,
      twoFactorEnabled,
      devices,
      loginHistory,
      updatePassword,
      toggleTwoFactor,
      revokeDevice,
      formatDate
    }
  }
}
</script>
