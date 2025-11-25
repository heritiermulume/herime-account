<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="close">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Modifier le profil
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

        <form @submit.prevent="updateProfile" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Nom complet
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="input-field mt-1"
                :class="{ 'border-red-500': errors.name }"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.name[0] }}
              </p>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Email
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                disabled
                class="input-field mt-1 bg-gray-100 dark:bg-gray-700"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                L'email ne peut pas être modifié
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Téléphone
              </label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                class="input-field mt-1"
                :class="{ 'border-red-500': errors.phone }"
              />
              <p v-if="errors.phone" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.phone[0] }}
              </p>
            </div>

            <div>
              <label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Entreprise
              </label>
              <input
                id="company"
                v-model="form.company"
                type="text"
                class="input-field mt-1"
                :class="{ 'border-red-500': errors.company }"
              />
              <p v-if="errors.company" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.company[0] }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Sexe
              </label>
              <select
                id="gender"
                v-model="form.gender"
                class="input-field mt-1"
                :class="{ 'border-red-500': errors.gender }"
              >
                <option value="">Sélectionnez votre sexe</option>
                <option value="masculin">Masculin</option>
                <option value="feminin">Féminin</option>
                <option value="autre">Autre</option>
              </select>
              <p v-if="errors.gender" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.gender[0] }}
              </p>
            </div>

            <div>
              <label for="birthdate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Date de naissance
              </label>
              <input
                id="birthdate"
                v-model="form.birthdate"
                type="date"
                placeholder="Sélectionnez votre date de naissance"
                :max="new Date().toISOString().split('T')[0]"
                min="1900-01-01"
                class="input-field mt-1"
                :class="{ 'border-red-500': errors.birthdate }"
                style="max-width: 100%; box-sizing: border-box; -webkit-appearance: none; appearance: none;"
              />
              <p v-if="errors.birthdate" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.birthdate[0] }}
              </p>
            </div>
          </div>

          <div>
            <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Poste
            </label>
            <input
              id="position"
              v-model="form.position"
              type="text"
              class="input-field mt-1"
              :class="{ 'border-red-500': errors.position }"
            />
            <p v-if="errors.position" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.position[0] }}
            </p>
          </div>

          <div>
            <label for="avatar" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Photo de profil
            </label>
            <div class="mt-1 flex items-center space-x-4">
              <div v-if="(previewUrl || (user.avatar_url && user.avatar_url !== ''))" class="h-16 w-16 rounded-full overflow-hidden bg-gray-200">
                <img
                  :src="previewUrl || getAvatarUrl()"
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
              <div>
                <input
                  id="avatar"
                  ref="avatarInput"
                  type="file"
                  accept="image/*"
                  @change="handleAvatarChange"
                  class="hidden"
                />
                <button
                  type="button"
                  @click="$refs.avatarInput.click()"
                  class="btn-secondary"
                >
                  Changer la photo
                </button>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  JPG, PNG ou GIF. Max 2MB.
                </p>
              </div>
            </div>
            <p v-if="errors.avatar" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.avatar[0] }}
            </p>
          </div>

          <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex">
              <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
              </svg>
              <div class="ml-3">
                <p class="text-sm text-red-800 dark:text-red-200">{{ error }}</p>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="close"
              class="btn-secondary"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="btn-primary"
            >
              {{ loading ? 'Sauvegarde...' : 'Sauvegarder' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'

export default {
  name: 'ProfileModal',
  emits: ['close'],
  setup(props, { emit }) {
    const authStore = useAuthStore()
    
    const form = reactive({
      name: '',
      phone: '',
      gender: '',
      birthdate: '',
      company: '',
      position: ''
    })
    
    const errors = ref({})
    const error = ref('')
    const loading = ref(false)
    const previewUrl = ref(null)
    const avatarFile = ref(null)

    const user = computed(() => authStore.user)

    // Initialize form with user data
    if (user.value) {
      form.name = user.value.name || ''
      form.phone = user.value.phone || ''
      form.gender = user.value.gender || ''
      form.birthdate = user.value.birthdate || ''
      form.company = user.value.company || ''
      form.position = user.value.position || ''
    }

    const handleAvatarChange = (event) => {
      const file = event.target.files[0]
      if (file) {
        avatarFile.value = file
        previewUrl.value = URL.createObjectURL(file)
      }
    }

    const updateProfile = async () => {
      loading.value = true
      errors.value = {}
      error.value = ''

      try {
        const formData = new FormData()
        // Toujours envoyer tous les champs, même s'ils sont vides
        formData.append('name', form.name || '')
        formData.append('phone', form.phone || '')
        formData.append('gender', form.gender || '')
        formData.append('birthdate', form.birthdate || '')
        formData.append('company', form.company || '')
        formData.append('position', form.position || '')
        
        if (avatarFile.value) {
          formData.append('avatar', avatarFile.value)
        }

        
        const response = await axios.post('/user/profile', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        

        if (response.data.success) {
          // Update user in store
          authStore.user = response.data.data.user
          emit('close')
        } else {
          throw new Error(response.data.message || 'Update failed')
        }
      } catch (err) {
        if (err.response?.data?.errors) {
          errors.value = err.response.data.errors
        } else {
          error.value = err.message
        }
      } finally {
        loading.value = false
      }
    }

    const getAvatarUrl = () => {
      // Si on a un aperçu local (previewUrl), l'utiliser en priorité
      if (previewUrl.value) {
        return previewUrl.value
      }
      
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

    const close = () => {
      emit('close')
    }

    return {
      user,
      form,
      errors,
      error,
      loading,
      previewUrl,
      handleAvatarChange,
      getAvatarUrl,
      handleImageError,
      updateProfile,
      close
    }
  }
}
</script>
