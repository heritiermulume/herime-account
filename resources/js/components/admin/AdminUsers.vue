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
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
          <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <div>
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Rechercher
                </label>
                <input
                  v-model="filters.search"
                  type="text"
                  id="search"
                  placeholder="Nom, email, entreprise..."
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  @input="debouncedSearch"
                />
              </div>
              <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Rôle
                </label>
                <select
                  v-model="filters.role"
                  id="role"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  @change="fetchUsers"
                >
                  <option value="">Tous les rôles</option>
                  <option value="user">Utilisateur</option>
                  <option value="admin">Administrateur</option>
                  <option value="super_user">Super Utilisateur</option>
                </select>
              </div>
              <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Statut
                </label>
                <select
                  v-model="filters.status"
                  id="status"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  @change="fetchUsers"
                >
                  <option value="">Tous les statuts</option>
                  <option value="active">Actif</option>
                  <option value="inactive">Inactif</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-md sm:rounded-lg">
          <div v-if="loading" class="flex justify-center items-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-500"></div>
          </div>

          <div v-else-if="error" class="px-4 py-5 sm:p-6">
            <div class="rounded-md bg-red-50 dark:bg-red-900 p-4">
              <div class="text-sm text-red-800 dark:text-red-200">{{ error }}</div>
            </div>
          </div>

          <div v-else-if="users.length === 0" class="px-4 py-5 sm:p-6 text-center">
            <p class="text-gray-500 dark:text-gray-400">Aucun utilisateur trouvé</p>
          </div>

          <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
            <li v-for="user in users" :key="user.id" class="px-4 py-4 sm:px-6">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div v-if="getAvatarUrl(user)" class="h-10 w-10 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                      <img :src="getAvatarUrl(user)" :alt="user.name" class="h-full w-full object-cover" @error="onAvatarError($event)" />
                    </div>
                    <div v-else class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ user.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ user.name }}
                      <span v-if="user.role === 'super_user'" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        Super User
                      </span>
                      <span v-else-if="user.role === 'admin'" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        Admin
                      </span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ user.email }}
                    </div>
                    <div v-if="user.company" class="text-sm text-gray-500 dark:text-gray-400">
                      {{ user.company }} - {{ user.position }}
                    </div>
                  </div>
                </div>
                <div class="w-full sm:w-auto mt-3 sm:mt-0 flex items-center space-x-2">
                  <span :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    user.is_active 
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                      : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                  ]">
                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                  </span>
                  <div class="flex items-center justify-start sm:justify-end gap-1 sm:gap-2 max-w-full overflow-x-auto whitespace-nowrap">
                    <!-- Activer / Désactiver -->
                    <button
                      @click="toggleUserStatus(user)"
                      :disabled="user.role === 'super_user'"
                      class="order-1 flex-none w-9 h-9 p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                      :title="user.is_active ? 'Désactiver' : 'Activer'"
                      aria-label="Basculer le statut"
                    >
                      <!-- Icône Power (clair et standard) -->
                      <svg v-if="user.is_active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-blue-600 dark:text-blue-400">
                        <path d="M12 2a1 1 0 011 1v8a1 1 0 11-2 0V3a1 1 0 011-1z"/>
                        <path d="M7.05 5.636a8 8 0 1010.9 0 1 1 0 011.414 1.414 10 10 0 11-13.728 0A1 1 0 117.05 5.636z"/>
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-blue-600 dark:text-blue-400">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 5v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                      </svg>
                    </button>

                    <!-- Nommer / Retirer admin -->
                    <button
                      v-if="user.role !== 'super_user'"
                      @click="toggleAdminRole(user)"
                      class="order-2 flex-none w-9 h-9 p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                      :title="user.role === 'admin' ? 'Retirer admin' : 'Nommer admin'"
                      aria-label="Basculer rôle admin"
                    >
                      <!-- Icônes admin: bouclier avec check (admin) / bouclier avec plus (nommer) -->
                      <svg v-if="user.role === 'admin'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-yellow-600 dark:text-yellow-400">
                        <path d="M12 2l7 3v6c0 5.25-3.438 9.75-7 11-3.562-1.25-7-5.75-7-11V5l7-3z"/>
                        <path d="M10.5 12.5l1.5 1.5 3.5-3.5" stroke="#1f2937" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-yellow-600 dark:text-yellow-400">
                        <path d="M12 2l7 3v6c0 5.25-3.438 9.75-7 11-3.562-1.25-7-5.75-7-11V5l7-3z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M12 9v6M9 12h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                      </svg>
                    </button>

                    <!-- Voir -->
                    <button
                      @click="openPreview(user)"
                      class="order-3 flex-none w-9 h-9 p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                      title="Voir"
                      aria-label="Voir"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 3C5 3 1 7.5 1 10s4 7 9 7 9-4.5 9-7-4-7-9-7zm0 12a5 5 0 110-10 5 5 0 010 10z" />
                      </svg>
                    </button>

                    <!-- Modifier -->
                    <button
                      @click="openEdit(user)"
                      class="order-4 flex-none w-9 h-9 p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                      title="Modifier"
                      aria-label="Modifier"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828L7 16H4v-3l9.586-9.414z" />
                      </svg>
                    </button>

                    <!-- Supprimer -->
                    <button
                      v-if="user.role !== 'super_user'"
                      @click="deleteUser(user)"
                      class="order-5 flex-none w-9 h-9 p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                      title="Supprimer"
                      aria-label="Supprimer"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        <path d="M4 5h12l-1 12a2 2 0 01-2 2H7a2 2 0 01-2-2L4 5zM9 2h2a1 1 0 011 1v1H8V3a1 1 0 011-1z" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </li>
          </ul>

          <!-- Pagination (alignée comme la liste des sessions) -->
          <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <Pagination :page="pagination?.current_page || 1" :perPage="15" :total="pagination?.total || 0" @update:page="changePage" />
          </div>
        </div>

        <!-- Preview Modal -->
        <teleport to="body">
          <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showPreview" class="fixed inset-0 z-50 flex items-center justify-center">
              <div class="fixed inset-0 bg-black bg-opacity-50" @click="showPreview = false"></div>
              <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aperçu de l'utilisateur</h3>
                  <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" @click="showPreview = false" aria-label="Fermer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                  </button>
                </div>
                <div v-if="previewUser" class="space-y-2">
                  <div class="flex items-center space-x-3">
                    <img v-if="getAvatarUrl(previewUser)" :src="getAvatarUrl(previewUser)" class="h-12 w-12 rounded-full object-cover" @error="onAvatarError($event)" />
                    <div>
                      <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ previewUser.name }}</div>
                      <div class="text-sm text-gray-600 dark:text-gray-300">{{ previewUser.email }}</div>
                    </div>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Rôle: <span class="text-gray-900 dark:text-white">{{ previewUser.role }}</span></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Statut: <span class="text-gray-900 dark:text-white">{{ previewUser.is_active ? 'Actif' : 'Inactif' }}</span></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Entreprise: <span class="text-gray-900 dark:text-white">{{ previewUser.company || '-' }}</span></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Poste: <span class="text-gray-900 dark:text-white">{{ previewUser.position || '-' }}</span></div>
                  </div>
                </div>
                <div class="mt-4 flex justify-end">
                  <button class="px-4 py-2 rounded bg-yellow-500 text-white hover:bg-yellow-600" @click="showPreview = false">Fermer</button>
                </div>
              </div>
            </div>
          </transition>
        </teleport>

        <!-- Edit Modal -->
        <teleport to="body">
          <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showEdit" class="fixed inset-0 z-50 flex items-center justify-center">
              <div class="fixed inset-0 bg-black bg-opacity-50" @click="showEdit = false"></div>
              <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">Modifier l'utilisateur</h3>
                  <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" @click="showEdit = false" aria-label="Fermer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                  </button>
                </div>
                <div v-if="editUser" class="space-y-4">
                  <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Nom</label>
                    <input v-model="editUser.name" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm" />
                  </div>
                  <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Email</label>
                    <input v-model="editUser.email" type="email" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm" />
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm text-gray-700 dark:text-gray-300">Entreprise</label>
                      <input v-model="editUser.company" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm" />
                    </div>
                    <div>
                      <label class="block text-sm text-gray-700 dark:text-gray-300">Poste</label>
                      <input v-model="editUser.position" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white sm:text-sm" />
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <input id="is_active" type="checkbox" v-model="editUser.is_active" class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500" />
                    <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Actif</label>
                  </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                  <button class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600" @click="showEdit = false">Annuler</button>
                  <button class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700" @click="saveEdit">Enregistrer</button>
                </div>
              </div>
            </div>
          </transition>
        </teleport>

        <!-- Delete Confirm Modal -->
        <teleport to="body">
          <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showDelete" class="fixed inset-0 z-50 flex items-center justify-center">
              <div class="fixed inset-0 bg-black bg-opacity-50" @click="showDelete = false"></div>
              <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">Confirmer la suppression</h3>
                  <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" @click="showDelete = false" aria-label="Fermer">
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
                    <p class="text-sm text-gray-700 dark:text-gray-200">Voulez-vous vraiment supprimer l'utilisateur
                      <span class="font-semibold">{{ deleteTarget?.name }}</span> ? Cette action est irréversible.</p>
                    <p v-if="deleteError" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ deleteError }}</p>
                  </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                  <button class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600" @click="showDelete = false">Annuler</button>
                  <button :disabled="deleteLoading" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-50" @click="confirmDeleteUser">
                    <span v-if="deleteLoading">Suppression...</span>
                    <span v-else>Supprimer</span>
                  </button>
                </div>
              </div>
            </div>
          </transition>
        </teleport>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import Pagination from '../Pagination.vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'

export default {
  name: 'AdminUsers',
  components: { Pagination },
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const loading = ref(true)
    const users = ref([])
    const pagination = ref(null)
    const error = ref('')
    
    const user = computed(() => authStore.user)
    
    const filters = reactive({
      search: '',
      role: '',
      status: ''
    })

    let searchTimeout = null

    const debouncedSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        fetchUsers()
      }, 500)
    }

    const fetchUsers = async (page = 1) => {
      try {
        loading.value = true
        const params = {
          page,
          per_page: 15,
          ...filters
        }
        
        const response = await axios.get('/admin/users', { params })
        
        
        if (response.data.success) {
          users.value = response.data.data.data
          pagination.value = {
            current_page: response.data.data.current_page,
            last_page: response.data.data.last_page,
            from: response.data.data.from,
            to: response.data.data.to,
            total: response.data.data.total,
            prev_page_url: response.data.data.prev_page_url,
            next_page_url: response.data.data.next_page_url
          }
        } else {
          error.value = 'Réponse inattendue du serveur'
        }
      } catch (err) {
        error.value = 'Erreur lors du chargement des utilisateurs'
      } finally {
        loading.value = false
      }
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        fetchUsers(page)
      }
    }

    const visiblePages = computed(() => {
      if (!pagination.value) return []
      
      const current = pagination.value.current_page
      const last = pagination.value.last_page
      const pages = []
      
      const start = Math.max(1, current - 2)
      const end = Math.min(last, current + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })

    const getAvatarUrl = (u) => {
      if (u?.avatar_url && u.avatar_url.startsWith('http')) {
        const sep = u.avatar_url.includes('?') ? '&' : '?'
        return u.avatar_url.includes('?t=') ? u.avatar_url : u.avatar_url + sep + 't=' + Date.now()
      }
      if (u?.avatar && u?.id) {
        return `/api/user/avatar/${u.id}?t=${Date.now()}`
      }
      if (u?.avatar_url && u.avatar_url.startsWith('/api')) {
        return u.avatar_url.includes('?t=') ? u.avatar_url : u.avatar_url + '?t=' + Date.now()
      }
      return null
    }

    const onAvatarError = (e) => { e.target.style.display = 'none' }

    const toggleAdminRole = async (u) => {
      try {
        const targetRole = u.role === 'admin' ? 'user' : 'admin'
        await axios.put(`/admin/users/${u.id}/role`, { role: targetRole })
        await fetchUsers(pagination.value?.current_page || 1)
      } catch (err) {
      }
    }

    const previewUser = ref(null)
    const editUser = ref(null)
    const showPreview = ref(false)
    const showEdit = ref(false)
    const showDelete = ref(false)
    const deleteTarget = ref(null)
    const deleteLoading = ref(false)
    const deleteError = ref('')
    const openPreview = (u) => { previewUser.value = u; showPreview.value = true }
    const openEdit = (u) => { editUser.value = { ...u }; showEdit.value = true }
    const saveEdit = async () => {
      try {
        const u = editUser.value
        await axios.put(`/admin/users/${u.id}`, {
          name: u.name,
          email: u.email,
          phone: u.phone,
          company: u.company,
          position: u.position,
          is_active: u.is_active
        })
        showEdit.value = false
        await fetchUsers(pagination.value?.current_page || 1)
      } catch (err) {
      }
    }

    const toggleUserStatus = async (user) => {
      try {
        const response = await axios.put(`/admin/users/${user.id}/status`, {
          is_active: !user.is_active
        })
        
        if (response.data.success) {
          user.is_active = !user.is_active
        }
      } catch (err) {
        error.value = 'Erreur lors de la modification du statut'
      }
    }

    const deleteUser = (user) => {
      deleteTarget.value = user
      deleteError.value = ''
      showDelete.value = true
    }

    const confirmDeleteUser = async () => {
      if (!deleteTarget.value) return
      deleteLoading.value = true
      deleteError.value = ''
      try {
        const response = await axios.delete(`/admin/users/${deleteTarget.value.id}`)
        if (response.data.success) {
          showDelete.value = false
          deleteTarget.value = null
          await fetchUsers(pagination.value?.current_page || 1)
        } else {
          deleteError.value = response.data.message || 'Suppression échouée'
        }
      } catch (err) {
        deleteError.value = 'Erreur lors de la suppression de l\'utilisateur'
      } finally {
        deleteLoading.value = false
      }
    }

    onMounted(() => {
      // Vérifier si l'utilisateur est un super utilisateur ou admin
      if (!user.value || !['admin', 'super_user'].includes(user.value.role)) {
        router.push('/')
        return
      }
      
      fetchUsers()
    })

    return {
      loading,
      users,
      pagination,
      error,
      user,
      filters,
      debouncedSearch,
      fetchUsers,
      changePage,
      visiblePages,
      toggleUserStatus,
      deleteUser,
      confirmDeleteUser,
      getAvatarUrl,
      onAvatarError,
      toggleAdminRole,
      openPreview,
      openEdit,
      saveEdit,
      previewUser,
      editUser,
      showPreview,
      showEdit,
      showDelete,
      deleteTarget,
      deleteLoading,
      deleteError
    }
  }
}
</script>