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
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
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
              <div class="flex items-center justify-between">
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
                <div class="flex items-center space-x-2">
                  <span :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    user.is_active 
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                      : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                  ]">
                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                  </span>
                  <div class="flex space-x-1">
                    <button
                      @click="toggleUserStatus(user)"
                      :disabled="user.role === 'super_user'"
                      class="text-sm text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {{ user.is_active ? 'Désactiver' : 'Activer' }}
                    </button>
                    <button
                      v-if="user.role !== 'super_user'"
                      @click="makeAdmin(user)"
                      class="text-sm text-yellow-600 hover:text-yellow-700 dark:text-yellow-400 dark:hover:text-yellow-300"
                    >
                      Nommer admin
                    </button>
                    <button
                      @click="openPreview(user)"
                      class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                    >
                      Voir
                    </button>
                    <button
                      @click="openEdit(user)"
                      class="text-sm text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300"
                    >
                      Modifier
                    </button>
                    <button
                      v-if="user.role !== 'super_user'"
                      @click="deleteUser(user)"
                      class="text-sm text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                    >
                      Supprimer
                    </button>
                  </div>
                </div>
              </div>
            </li>
          </ul>

          <!-- Pagination -->
          <div v-if="pagination && pagination.last_page > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
            <Pagination :page="pagination.current_page" :perPage="15" :total="pagination.total" @update:page="changePage" />
          </div>
        </div>
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
        console.log('=== FETCH USERS START ===')
        loading.value = true
        const params = {
          page,
          per_page: 15,
          ...filters
        }
        console.log('Fetching users with params:', params)
        
        const response = await axios.get('/admin/users', { params })
        console.log('Users response:', response.status, response.data)
        
        console.log('Checking response.data.success:', response.data.success)
        console.log('Response.data structure:', Object.keys(response.data))
        console.log('Response.data.data structure:', Object.keys(response.data.data || {}))
        
        if (response.data.success) {
          users.value = response.data.data.data
          console.log('Users loaded:', users.value.length, 'users')
          console.log('Users content:', users.value)
          pagination.value = {
            current_page: response.data.data.current_page,
            last_page: response.data.data.last_page,
            from: response.data.data.from,
            to: response.data.data.to,
            total: response.data.data.total,
            prev_page_url: response.data.data.prev_page_url,
            next_page_url: response.data.data.next_page_url
          }
          console.log('Pagination:', pagination.value)
        } else {
          console.error('API returned success=false:', response.data)
          error.value = 'Réponse inattendue du serveur'
        }
      } catch (err) {
        console.error('=== FETCH USERS ERROR ===')
        console.error('Error fetching users:', err)
        console.error('Error response:', err.response?.data)
        console.error('Error status:', err.response?.status)
        console.error('Error headers:', err.response?.headers)
        error.value = 'Erreur lors du chargement des utilisateurs'
      } finally {
        loading.value = false
        console.log('=== FETCH USERS END ===')
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

    const makeAdmin = async (u) => {
      try {
        await axios.put(`/admin/users/${u.id}/role`, { role: 'admin' })
        await fetchUsers(pagination.value?.current_page || 1)
      } catch (err) {
        console.error('Error promoting to admin:', err)
      }
    }

    const previewUser = ref(null)
    const editUser = ref(null)
    const showPreview = ref(false)
    const showEdit = ref(false)
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
        console.error('Error updating user:', err)
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
        console.error('Error toggling user status:', err)
        error.value = 'Erreur lors de la modification du statut'
      }
    }

    const deleteUser = async (user) => {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${user.name} ?`)) {
        return
      }
      
      try {
        const response = await axios.delete(`/admin/users/${user.id}`)
        
        if (response.data.success) {
          await fetchUsers(pagination.value.current_page)
        }
      } catch (err) {
        console.error('Error deleting user:', err)
        error.value = 'Erreur lors de la suppression de l\'utilisateur'
      }
    }

    onMounted(() => {
      // Vérifier si l'utilisateur est un super utilisateur
      if (!user.value || user.value.role !== 'super_user') {
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
      deleteUser
    }
  }
}
</script>