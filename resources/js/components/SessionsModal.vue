<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="close">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Gérer les sessions
          </h3>
          <button
            @click="close"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <div class="mb-4">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Gérez les appareils et navigateurs qui ont accès à votre compte. 
            Déconnectez-vous de tous les appareils que vous ne reconnaissez pas.
          </p>
        </div>

        <div class="space-y-4">
          <div
            v-for="session in sessions"
            :key="session.id"
            class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-600 rounded-lg"
            :class="{ 'border-2': session.is_current }"
            :style="session.is_current ? 'background-color: rgba(255, 204, 51, 0.1); border-color: #ffcc33;' : ''"
          >
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <div class="h-10 w-10 rounded-full flex items-center justify-center" style="background-color: rgba(255, 204, 51, 0.2);">
                  <svg class="h-6 w-6" style="color: #ffcc33;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                  </svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ session.device_name }}
                  </p>
                  <span
                    v-if="session.is_current"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-white"
                    style="background-color: #ffcc33;"
                  >
                    Session actuelle
                  </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ session.platform }} • {{ session.browser }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                  {{ session.ip_address }} • Dernière activité: {{ formatDate(session.last_activity) }}
                </p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <button
                v-if="!session.is_current"
                @click="revokeSession(session.id)"
                :disabled="loading"
                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium disabled:opacity-50"
              >
                Déconnecter
              </button>
            </div>
          </div>
        </div>

        <div v-if="sessions.length === 0" class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucune session</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Aucune session active trouvée.
          </p>
        </div>

        <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-600">
          <button
            @click="revokeAllSessions"
            :disabled="loading || sessions.filter(s => !s.is_current).length === 0"
            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium disabled:opacity-50"
          >
            Déconnecter tous les autres appareils
          </button>
          <button
            @click="close"
            class="btn-secondary"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'SessionsModal',
  emits: ['close'],
  setup(props, { emit }) {
    const sessions = ref([])
    const loading = ref(false)

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
      loading.value = true
      try {
        const response = await axios.get('/api/user/sessions')
        if (response.data.success) {
          sessions.value = response.data.data.sessions
        }
      } catch (error) {
        console.error('Erreur lors du chargement des sessions:', error)
        sessions.value = []
      } finally {
        loading.value = false
      }
    }

    const revokeSession = async (sessionId) => {
      if (!confirm('Êtes-vous sûr de vouloir déconnecter cette session ?')) {
        return
      }

      loading.value = true
      try {
        const response = await axios.delete(`/api/user/sessions/${sessionId}`)
        if (response.data.success) {
          // Recharger les sessions
          await loadSessions()
          alert('Session déconnectée avec succès')
        }
      } catch (error) {
        console.error('Erreur lors de la déconnexion de la session:', error)
        alert('Erreur lors de la déconnexion de la session')
      } finally {
        loading.value = false
      }
    }

    const revokeAllSessions = async () => {
      if (!confirm('Êtes-vous sûr de vouloir déconnecter tous les autres appareils ?')) {
        return
      }

      loading.value = true
      try {
        const response = await axios.post('/api/user/sessions/revoke-all')
        if (response.data.success) {
          // Recharger les sessions
          await loadSessions()
          alert(response.data.message)
        }
      } catch (error) {
        console.error('Erreur lors de la déconnexion des sessions:', error)
        alert('Erreur lors de la déconnexion des sessions')
      } finally {
        loading.value = false
      }
    }

    const close = () => {
      emit('close')
    }

    onMounted(() => {
      loadSessions()
    })

    return {
      sessions,
      loading,
      formatDate,
      revokeSession,
      revokeAllSessions,
      close
    }
  }
}
</script>
