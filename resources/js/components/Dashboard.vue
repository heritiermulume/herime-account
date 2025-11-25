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
            <div v-if="user.avatar_url && user.avatar_url !== ''" class="h-16 w-16 rounded-full overflow-hidden bg-gray-200">
              <img
                :src="getAvatarUrl()"
                :alt="user.name"
                class="h-full w-full object-cover"
                @error="handleImageError"
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
                {{ user?.name || 'Utilisateur' }}
              </dd>
              <dd v-if="user?.email" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ user.email }}
              </dd>
              <dd v-if="user?.company" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ user.company }}
              </dd>
              <dd v-if="user?.position" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ user.position }}
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
                  {{ activeSessions }}
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
                    <svg class="h-6 w-6" :style="{ color: user?.is_active ? '#ffcc33' : '#ef4444' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                  <div class="ml-5 w-0 flex-1">
                    <dl>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                        Statut du compte
                      </dt>
                      <dd class="text-lg font-medium" :style="{ color: user?.is_active ? '#ffcc33' : '#ef4444' }">
                        {{ user?.is_active ? 'Actif' : 'Inactif' }}
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
                        {{ user?.last_login_at ? formatDate(user.last_login_at) : 'Jamais' }}
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

    <!-- Nos plateformes -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
          Nos plateformes
        </h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <a
            href="https://academie.herime.com"
            target="_blank"
            rel="noopener noreferrer"
            class="relative rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-6 py-5 text-left hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-herime-blue-500 transition-all duration-200 hover:shadow-md group"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10 rounded-lg flex items-center justify-center" style="background-color: #ffcc33;">
                  <svg class="h-6 w-6" style="color: #003366;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Herime Académie
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    Formation en ligne
                  </p>
                </div>
              </div>
              <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
              </svg>
            </div>
          </a>

          <a
            href="https://store.herime.com"
            target="_blank"
            rel="noopener noreferrer"
            class="relative rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-6 py-5 text-left hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-herime-blue-500 transition-all duration-200 hover:shadow-md group"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10 rounded-lg flex items-center justify-center" style="background-color: #ffcc33;">
                  <svg class="h-6 w-6" style="color: #003366;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Herime Store
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    Boutique en ligne
                  </p>
                </div>
              </div>
              <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
              </svg>
            </div>
          </a>

          <a
            href="https://www.herime.com"
            target="_blank"
            rel="noopener noreferrer"
            class="relative rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-6 py-5 text-left hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-herime-blue-500 transition-all duration-200 hover:shadow-md group"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10 rounded-lg flex items-center justify-center" style="background-color: #ffcc33;">
                  <svg class="h-6 w-6" style="color: #003366;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Herime
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    Site principal
                  </p>
                </div>
              </div>
              <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
              </svg>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- Recent Sessions -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Toutes les sessions
          </h3>
          <div class="flex items-center space-x-4 text-sm">
            <div class="flex items-center">
              <span class="h-3 w-3 rounded-full mr-2" style="background-color: #ffcc33;"></span>
              <span class="text-gray-600 dark:text-gray-400">Active</span>
            </div>
            <div class="flex items-center">
              <span class="h-3 w-3 rounded-full bg-gray-400 mr-2"></span>
              <span class="text-gray-600 dark:text-gray-400">Inactive</span>
            </div>
          </div>
        </div>
        <div class="flow-root">
          <div v-if="sessions.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucune session</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Aucune session active trouvée.
            </p>
          </div>
          <ul v-else class="-mb-8">
            <li v-for="(session, index) in paginatedSessions" :key="session.id">
              <div class="relative pb-8">
                <div v-if="index !== paginatedSessions.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600"></div>
                <div class="relative flex space-x-3">
                  <div>
                    <span 
                      class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800" 
                      :style="session.is_current ? 'background-color: #ffcc33;' : 'background-color: #9ca3af;'"
                    >
                      <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                      </svg>
                    </span>
                  </div>
                  <div class="min-w-0 flex-1 pt-1.5">
                    <div class="flex justify-between items-start space-x-4">
                      <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-1">
                          <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ session.device_name }}
                          </p>
                          <span
                            v-if="session.is_current"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-white"
                            style="background-color: #ffcc33;"
                          >
                            Active
                          </span>
                          <span
                            v-else
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600"
                          >
                            Inactive
                          </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                          {{ session.platform }} • {{ session.browser }}
                        </p>
                        <p class="text-sm text-gray-900 dark:text-white">
                          {{ session.ip_address }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                          Dernière activité: {{ formatDate(session.last_activity) }}
                        </p>
                      </div>
                      <div class="flex items-center space-x-2">
                        <button
                          v-if="!session.is_current"
                          @click="deactivateSession(session.id)"
                          :disabled="loadingSessionId === session.id"
                          class="text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300 text-sm font-medium disabled:opacity-50"
                          title="Désactiver cette session"
                        >
                          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                          </svg>
                        </button>
                        <button
                          @click="deleteSession(session.id)"
                          :disabled="loadingSessionId === session.id || session.is_current"
                          class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium disabled:opacity-50"
                          :title="session.is_current ? 'Impossible de supprimer la session actuelle' : 'Supprimer cette session'"
                        >
                          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </li>
          </ul>
          <div v-if="sessions.length > 0" class="mt-4">
            <Pagination :page="page" :perPage="perPage" :total="totalSessions" @update:page="val => page = val" />
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ProfileModal v-if="showProfileModal" @close="showProfileModal = false" />
    <SessionsModal v-if="showSessionsModal" @close="showSessionsModal = false" />
    <SecurityModal v-if="showSecurityModal" @close="showSecurityModal = false" />
    
    <!-- Confirm Dialogs -->
    <ConfirmDialog
      v-model:show="showDeactivateDialog"
      type="warning"
      title="Désactiver la session"
      :message="deactivateDialogMessage"
      confirm-text="Désactiver"
      cancel-text="Annuler"
      @confirm="confirmDeactivate"
    />
    
    <ConfirmDialog
      v-model:show="showDeleteDialog"
      type="danger"
      title="Supprimer la session"
      :message="deleteDialogMessage"
      confirm-text="Supprimer"
      cancel-text="Annuler"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'
import ProfileModal from './ProfileModal.vue'
import SessionsModal from './SessionsModal.vue'
import SecurityModal from './SecurityModal.vue'
import Pagination from './Pagination.vue'
import ConfirmDialog from './ConfirmDialog.vue'

export default {
  name: 'Dashboard',
  components: {
    ProfileModal,
    SessionsModal,
    SecurityModal,
    Pagination,
    ConfirmDialog
  },
  setup() {
    const authStore = useAuthStore()
    
    const showProfileModal = ref(false)
    const showSessionsModal = ref(false)
    const showSecurityModal = ref(false)
    const sessions = ref([])
    const page = ref(1)
    const perPage = ref(15)
    const loadingSessionId = ref(null)
    const totalSessions = computed(() => sessions.value.length)
    const activeSessions = computed(() => sessions.value.filter(s => s.is_current).length)
    
    // Dialog states
    const showDeactivateDialog = ref(false)
    const showDeleteDialog = ref(false)
    const pendingSessionId = ref(null)
    const deactivateDialogMessage = ref('')
    const deleteDialogMessage = ref('')
    const paginatedSessions = computed(() => {
      const start = (page.value - 1) * perPage.value
      return sessions.value.slice(start, start + perPage.value)
    })

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

    const getAvatarUrl = () => {
      // Si on a un avatar_url qui est une URL complète (commence par http), la retourner avec timestamp
      if (user.value?.avatar_url && user.value.avatar_url.startsWith('http')) {
        const separator = user.value.avatar_url.includes('?') ? '&' : '?'
        return user.value.avatar_url.includes('?t=') ? user.value.avatar_url : user.value.avatar_url + separator + 't=' + Date.now()
      }
      
      // Si on a un avatar mais pas d'avatar_url, construire l'URL avec timestamp
      if (user.value?.avatar && user.value?.id) {
        const baseURL = (typeof window !== 'undefined' && window.axios?.defaults?.baseURL) 
          ? window.axios.defaults.baseURL 
          : '/api'
        return `${baseURL}/user/avatar/${user.value.id}?t=` + Date.now()
      }
      
      // Si on a un avatar_url qui commence par /api, le retourner avec timestamp
      if (user.value?.avatar_url && user.value.avatar_url.startsWith('/api')) {
        return user.value.avatar_url.includes('?t=') ? user.value.avatar_url : user.value.avatar_url + '?t=' + Date.now()
      }
      
      // Sinon, pas d'avatar
      return null
    }

    const handleImageError = (event) => {
    }

    const loadSessions = async () => {
      try {
        const response = await axios.get('user/sessions')
        if (response.data.success) {
          sessions.value = response.data.data.sessions
        }
      } catch (error) {
        // Ne pas bloquer l'affichage si les sessions ne se chargent pas
        sessions.value = []
      }
    }

    const deactivateSession = (sessionId) => {
      const session = sessions.value.find(s => s.id === sessionId)
      if (session) {
        deactivateDialogMessage.value = `Voulez-vous vraiment désactiver la session "${session.device_name}" ?`
      } else {
        deactivateDialogMessage.value = 'Voulez-vous vraiment désactiver cette session ?'
      }
      pendingSessionId.value = sessionId
      showDeactivateDialog.value = true
    }

    const confirmDeactivate = async () => {
      if (!pendingSessionId.value) return

      loadingSessionId.value = pendingSessionId.value
      try {
        const response = await axios.delete(`user/sessions/${pendingSessionId.value}`)
        if (response.data.success) {
          // Recharger les sessions
          await loadSessions()
        }
      } catch (error) {
        // Vous pouvez ajouter un toast de notification ici
      } finally {
        loadingSessionId.value = null
        pendingSessionId.value = null
      }
    }

    const deleteSession = (sessionId) => {
      const session = sessions.value.find(s => s.id === sessionId)
      if (session) {
        deleteDialogMessage.value = `Voulez-vous vraiment supprimer définitivement la session "${session.device_name}" ? Cette action est irréversible.`
      } else {
        deleteDialogMessage.value = 'Voulez-vous vraiment supprimer définitivement cette session ? Cette action est irréversible.'
      }
      pendingSessionId.value = sessionId
      showDeleteDialog.value = true
    }

    const confirmDelete = async () => {
      if (!pendingSessionId.value) return

      loadingSessionId.value = pendingSessionId.value
      try {
        const response = await axios.delete(`user/sessions/${pendingSessionId.value}/permanent`)
        if (response.data.success) {
          // Recharger les sessions
          await loadSessions()
        }
      } catch (error) {
        // Vous pouvez ajouter un toast de notification ici
      } finally {
        loadingSessionId.value = null
        pendingSessionId.value = null
      }
    }

    onMounted(async () => {
      
      // Load user if not already loaded
      if (!authStore.user) {
        await authStore.checkAuth()
      }
      
      // Recharger les données utilisateur pour s'assurer d'avoir les dernières informations
      try {
        const response = await axios.get('/user/profile')
        if (response.data.success && response.data.data.user) {
          authStore.updateUser(response.data.data.user)
        }
      } catch (error) {
      }
      
      loadSessions()
    })

    return {
      user,
      sessions,
      page,
      perPage,
      totalSessions,
      activeSessions,
      paginatedSessions,
      showProfileModal,
      showSessionsModal,
      showSecurityModal,
      formatDate,
      getAvatarUrl,
      handleImageError,
      loadingSessionId,
      deactivateSession,
      deleteSession,
      showDeactivateDialog,
      showDeleteDialog,
      deactivateDialogMessage,
      deleteDialogMessage,
      confirmDeactivate,
      confirmDelete
    }
  }
}
</script>
