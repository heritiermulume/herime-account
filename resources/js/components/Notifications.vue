<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Gérez vos préférences de notifications et restez informé de ce qui vous intéresse
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Email Notifications -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Notifications par e-mail
        </h3>

        <!-- Master switch info -->
        <div v-if="!emailEnabled" class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-4">
          <p class="text-sm text-yellow-800 dark:text-yellow-200">
            Les notifications par e-mail sont désactivées dans vos paramètres de profil. Les options ci-dessous sont inactives tant que ce paramètre reste désactivé.
          </p>
        </div>
        
        <div class="space-y-6">
          <!-- Account Notifications -->
          <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
              Compte et sécurité
            </h4>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                    Connexions suspectes
                  </h5>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Recevez une alerte en cas de connexion depuis un nouvel appareil ou lieu
                  </p>
                </div>
                <button
                  @click="toggleNotification('suspicious_logins')"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                    notifications.suspicious_logins ? 'bg-herime-600' : 'bg-gray-200 dark:bg-gray-600'
                  ]"
                  :style="notifications.suspicious_logins ? 'background-color: #003366;' : ''"
                  :disabled="!emailEnabled"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      notifications.suspicious_logins ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
              </div>

              <div class="flex items-center justify-between">
                <div>
                  <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                    Changements de mot de passe
                  </h5>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Soyez notifié lorsque votre mot de passe est modifié
                  </p>
                </div>
                <button
                  @click="toggleNotification('password_changes')"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                    notifications.password_changes ? 'bg-herime-600' : 'bg-gray-200 dark:bg-gray-600'
                  ]"
                  :style="notifications.password_changes ? 'background-color: #003366;' : ''"
                  :disabled="!emailEnabled"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      notifications.password_changes ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
              </div>

              <div class="flex items-center justify-between">
                <div>
                  <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                    Modifications du profil
                  </h5>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Recevez une confirmation des modifications de votre profil
                  </p>
                </div>
                <button
                  @click="toggleNotification('profile_changes')"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                    notifications.profile_changes ? 'bg-herime-600' : 'bg-gray-200 dark:bg-gray-600'
                  ]"
                  :style="notifications.profile_changes ? 'background-color: #003366;' : ''"
                  :disabled="!emailEnabled"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      notifications.profile_changes ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
              </div>
            </div>
          </div>

          <!-- Product Updates -->
          <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
              Mises à jour produit
            </h4>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                    Nouvelles fonctionnalités
                  </h5>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Découvrez les nouvelles fonctionnalités et améliorations
                  </p>
                </div>
                <button
                  @click="toggleNotification('new_features')"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                    notifications.new_features ? 'bg-herime-600' : 'bg-gray-200 dark:bg-gray-600'
                  ]"
                  :style="notifications.new_features ? 'background-color: #003366;' : ''"
                  :disabled="!emailEnabled"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      notifications.new_features ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
              </div>

              <div class="flex items-center justify-between">
                <div>
                  <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                    Maintenance système
                  </h5>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Soyez informé des maintenances programmées
                  </p>
                </div>
                <button
                  @click="toggleNotification('maintenance')"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                    notifications.maintenance ? 'bg-herime-600' : 'bg-gray-200 dark:bg-gray-600'
                  ]"
                  :style="notifications.maintenance ? 'background-color: #003366;' : ''"
                  :disabled="!emailEnabled"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      notifications.maintenance ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
              </div>
            </div>
          </div>

          <!-- Marketing -->
          <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
              Marketing et communications
            </h4>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                    Newsletter
                  </h5>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Recevez notre newsletter avec les dernières actualités
                  </p>
                </div>
                <button
                  @click="toggleNotification('newsletter')"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                    notifications.newsletter ? 'bg-herime-600' : 'bg-gray-200 dark:bg-gray-600'
                  ]"
                  :style="notifications.newsletter ? 'background-color: #003366;' : ''"
                  :disabled="!emailEnabled"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      notifications.newsletter ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
              </div>

              <div class="flex items-center justify-between">
                <div>
                  <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                    Offres spéciales
                  </h5>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Découvrez nos offres et promotions exclusives
                  </p>
                </div>
                <button
                  @click="toggleNotification('special_offers')"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                    notifications.special_offers ? 'bg-herime-600' : 'bg-gray-200 dark:bg-gray-600'
                  ]"
                  :style="notifications.special_offers ? 'background-color: #003366;' : ''"
                  :disabled="!emailEnabled"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      notifications.special_offers ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Push Notifications -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Notifications push
        </h3>
        
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                Notifications push du navigateur
              </h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Recevez des notifications instantanées dans votre navigateur
              </p>
            </div>
            <div class="flex items-center space-x-3">
              <span                 :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  pushNotificationsSupported ? 'text-white' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                ]"
                :style="pushNotificationsSupported ? 'background-color: #ffcc33;' : ''">
                {{ pushNotificationsSupported ? 'Supporté' : 'Non supporté' }}
              </span>
              <button
                v-if="pushNotificationsSupported"
                @click="togglePushNotifications"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                    pushNotificationsEnabled ? 'bg-herime-600' : 'bg-gray-200 dark:bg-gray-600'
                  ]"
                  :style="pushNotificationsEnabled ? 'background-color: #003366;' : ''"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    pushNotificationsEnabled ? 'translate-x-5' : 'translate-x-0'
                  ]"
                />
              </button>
            </div>
          </div>

          <div v-if="!pushNotificationsSupported" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                  Notifications push non supportées
                </h3>
                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                  <p>Votre navigateur ne supporte pas les notifications push ou elles sont désactivées.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notification Frequency -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Fréquence des notifications
        </h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
              Fréquence des e-mails
            </label>
            <div class="space-y-2">
              <label v-for="frequency in emailFrequencies" :key="frequency.value" class="flex items-center">
                <input
                  :id="frequency.value"
                  v-model="selectedEmailFrequency"
                  :value="frequency.value"
                  type="radio"
                  class="h-4 w-4 border-gray-300 dark:border-gray-600"
                  style="accent-color: #003366;"
                />
                <label :for="frequency.value" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                  {{ frequency.label }}
                </label>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
      <button
        @click="saveNotifications"
        :disabled="saving"
        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
        style="background-color: #003366; focus:ring-color: #003366;"
        @mouseenter="$event.target.style.backgroundColor = '#ffcc33'"
        @mouseleave="$event.target.style.backgroundColor = '#003366'"
      >
        <div v-if="saving" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
        {{ saving ? 'Sauvegarde...' : 'Sauvegarder les préférences' }}
      </button>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, inject, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'

export default {
  name: 'Notifications',
  setup() {
    const notify = inject('notify')
    const authStore = useAuthStore()
    const saving = ref(false)
    const pushNotificationsSupported = ref(false)
    const pushNotificationsEnabled = ref(false)
    const selectedEmailFrequency = ref('daily')
    const emailEnabled = computed(() => {
      const prefs = authStore.user?.preferences || {}
      return prefs.email_notifications !== false
    })

    const notifications = reactive({
      suspicious_logins: true,
      password_changes: true,
      profile_changes: false,
      new_features: true,
      maintenance: true,
      newsletter: false,
      special_offers: false
    })

    const emailFrequencies = [
      { value: 'immediate', label: 'Immédiatement' },
      { value: 'daily', label: 'Quotidiennement' },
      { value: 'weekly', label: 'Hebdomadairement' },
      { value: 'monthly', label: 'Mensuellement' },
      { value: 'never', label: 'Jamais' }
    ]

    const toggleNotification = (key) => {
      notifications[key] = !notifications[key]
    }

    const togglePushNotifications = async () => {
      if (!pushNotificationsEnabled.value) {
        // Request permission
        const permission = await Notification.requestPermission()
        if (permission === 'granted') {
          pushNotificationsEnabled.value = true
        } else {
          notify.warning('Permission refusée', 'Permission refusée pour les notifications push')
        }
      } else {
        pushNotificationsEnabled.value = false
      }
    }

    const saveNotifications = async () => {
      saving.value = true
      try {
        // 1) Charger les préférences actuelles du serveur pour éviter d'écraser d'autres clés
        let current = {}
        try {
          const cur = await axios.get('/user/profile')
          if (cur.data?.success && cur.data.data?.user?.preferences) {
            current = cur.data.data.user.preferences
          }
        } catch (e) {
          // ignorer, on utilisera seulement nos valeurs locales
        }

        // 2) Construire l'objet notifications à partir de l'état local
        const notifPayload = {
          suspicious_logins: !!notifications.suspicious_logins,
          password_changes: !!notifications.password_changes,
          profile_changes: !!notifications.profile_changes,
          new_features: !!notifications.new_features,
          maintenance: !!notifications.maintenance,
          newsletter: !!notifications.newsletter,
          special_offers: !!notifications.special_offers,
        }

        // 3) Merge côté client pour préserver les autres préférences existantes
        const merged = {
          ...current,
          notifications: {
            ...(current.notifications || {}),
            ...notifPayload,
          },
          email_frequency: selectedEmailFrequency.value,
          push_notifications: !!pushNotificationsEnabled.value,
        }

        const response = await axios.put('/user/preferences', {
          preferences: merged
        })
        
        if (response.data.success) {
          notify.success('Succès', 'Préférences de notifications sauvegardées!')
          // Mettre à jour le store utilisateur pour refléter immédiatement les changements
          const updated = response.data.data?.preferences || merged
          authStore.updateUser({ preferences: updated })
        }
      } catch (error) {
        console.error('Error saving notifications:', error)
        if (error.response?.data?.message) {
          notify.error('Erreur', error.response.data.message)
        } else {
          notify.error('Erreur', 'Erreur lors de la sauvegarde des préférences')
        }
      } finally {
        saving.value = false
      }
    }

    onMounted(async () => {
      // Check if push notifications are supported
      pushNotificationsSupported.value = 'Notification' in window
      
      // Check current permission status
      if (pushNotificationsSupported.value) {
        pushNotificationsEnabled.value = Notification.permission === 'granted'
      }

      // Charger les préférences depuis l'API pour l'utilisateur courant
      try {
        const res = await axios.get('/user/profile')
        if (res.data?.success && res.data.data?.user) {
          const prefs = res.data.data.user.preferences || {}
          // Mettre à jour aussi le store pour cohérence globale
          authStore.updateUser(res.data.data.user)
          // Fréquence
          if (prefs.email_frequency) {
            selectedEmailFrequency.value = prefs.email_frequency
          }
          // Push
          if (typeof prefs.push_notifications === 'boolean') {
            pushNotificationsEnabled.value = prefs.push_notifications
          }
          // Notifications granulaires
          if (prefs.notifications && typeof prefs.notifications === 'object') {
            Object.assign(notifications, {
              suspicious_logins: prefs.notifications.suspicious_logins ?? notifications.suspicious_logins,
              password_changes: prefs.notifications.password_changes ?? notifications.password_changes,
              profile_changes: prefs.notifications.profile_changes ?? notifications.profile_changes,
              new_features: prefs.notifications.new_features ?? notifications.new_features,
              maintenance: prefs.notifications.maintenance ?? notifications.maintenance,
              newsletter: prefs.notifications.newsletter ?? notifications.newsletter,
              special_offers: prefs.notifications.special_offers ?? notifications.special_offers,
            })
          }
        }
      } catch (e) {
        console.error('Failed to load preferences:', e)
      }
    })

    return {
      saving,
      notifications,
      pushNotificationsSupported,
      pushNotificationsEnabled,
      selectedEmailFrequency,
      emailFrequencies,
      emailEnabled,
      toggleNotification,
      togglePushNotifications,
      saveNotifications
    }
  }
}
</script>
