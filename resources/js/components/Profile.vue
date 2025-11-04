<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profil</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Gérez vos informations personnelles et vos préférences de compte
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Form -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Informations personnelles
        </h3>
        
        <form @submit.prevent="updateProfile" class="space-y-6">
          <!-- Avatar Section -->
          <div class="flex items-center space-x-6">
            <div class="flex-shrink-0">
              <div v-if="getAvatarUrl()" class="h-20 w-20 rounded-full overflow-hidden bg-gray-200">
                <img
                  :src="getAvatarUrl()"
                  :alt="form.name"
                  class="h-full w-full object-cover"
                  @error="handleImageError"
                  @load="handleImageLoad"
                />
              </div>
              <div v-else class="h-20 w-20 rounded-full flex items-center justify-center" style="background-color: #ffcc33;">
                <svg class="h-12 w-12" style="color: #003366;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
            </div>
            <div>
              <button
                type="button"
                @click="triggerAvatarUpload"
                class="bg-white dark:bg-gray-700 py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="focus:ring-color: #ffcc33;"
              >
                Changer la photo
              </button>
              <input
                ref="avatarInput"
                type="file"
                accept="image/*"
                @change="handleAvatarChange"
                class="hidden"
              />
            </div>
          </div>

          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Nom complet
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Adresse e-mail
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
          </div>

          <!-- Phone -->
          <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Téléphone
            </label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
          </div>

          <!-- Company -->
          <div>
            <label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Entreprise
            </label>
            <input
              id="company"
              v-model="form.company"
              type="text"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
          </div>

          <!-- Position -->
          <div>
            <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Poste
            </label>
            <input
              id="position"
              v-model="form.position"
              type="text"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
          </div>

          <!-- Bio -->
          <div>
            <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Biographie
            </label>
            <textarea
              id="bio"
              v-model="form.bio"
              rows="3"
              class="mt-1 block w-full px-3 py-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
              placeholder="Parlez-nous de vous..."
            ></textarea>
          </div>

          <!-- Location -->
          <div>
            <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Localisation
            </label>
            <input
              id="location"
              v-model="form.location"
              type="text"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
              placeholder="Ville, Pays"
            />
          </div>

          <!-- Website -->
          <div>
            <label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Site web
            </label>
            <input
              id="website"
              v-model="form.website"
              type="url"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
              placeholder="https://example.com"
            />
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="loading"
              class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
              style="background-color: #003366; focus:ring-color: #003366;"
              @mouseenter="$event.target.style.backgroundColor = '#ffcc33'"
              @mouseleave="$event.target.style.backgroundColor = '#003366'"
            >
              <div v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              {{ loading ? 'Mise à jour...' : 'Mettre à jour le profil' }}
            </button>
          </div>
        </form>
      </div>
    </div>

          <!-- Account Settings -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
                Paramètres du compte
              </h3>
              
        <div class="space-y-4">
          <!-- Email Notifications -->
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                Notifications par e-mail
              </h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Recevez des notifications importantes par e-mail
              </p>
            </div>
            <button
              @click="toggleEmailNotifications"
              :class="[
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                form.email_notifications ? 'bg-herime-blue-500' : 'bg-gray-200 dark:bg-gray-600'
              ]"
              :style="form.email_notifications ? 'background-color: #003366;' : ''"
            >
              <span
                :class="[
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.email_notifications ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </div>

          <!-- Marketing Emails -->
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                E-mails marketing
              </h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Recevez des mises à jour sur nos produits et services
              </p>
            </div>
            <button
              @click="toggleMarketingEmails"
              :class="[
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                form.marketing_emails ? 'bg-herime-blue-500' : 'bg-gray-200 dark:bg-gray-600'
              ]"
              :style="form.marketing_emails ? 'background-color: #003366;' : ''"
            >
              <span
                :class="[
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.marketing_emails ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </div>
        </div>
        
        <!-- Danger Zone -->
        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-medium text-red-600 dark:text-red-400 mb-4">
            Zone dangereuse
          </h4>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            La suppression de votre compte désactivera votre compte. Seul un administrateur peut définitivement supprimer un compte.
          </p>
          <button
            @click="showDeleteModal = true; passwordError = ''; deletePassword = ''; deleteReason = ''"
            class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md text-sm transition-colors duration-200"
          >
            Supprimer le compte
          </button>
        </div>
      </div>
    </div>
    
    <!-- Delete Account Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDeleteModal" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDeleteModal = false; passwordError = ''; deletePassword = ''; deleteReason = ''"></div>
          
          <!-- Modal container -->
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Modal panel -->
            <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
              <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                  </div>
                  <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left flex-1">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">
                      Supprimer le compte
                    </h3>
                    <div class="mt-2">
                      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Cette action désactivera votre compte. Vous ne pourrez plus vous connecter. Seul un administrateur peut définitivement supprimer un compte.
                      </p>
                      
                      <div class="mb-4">
                        <label for="delete-reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                          Raison de la suppression <span class="text-red-600">*</span>
                        </label>
                        <textarea
                          id="delete-reason"
                          v-model="deleteReason"
                          rows="4"
                          required
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm"
                          placeholder="Expliquez pourquoi vous souhaitez supprimer votre compte..."
                        ></textarea>
                      </div>
                      
                      <div class="mb-4">
                        <label for="delete-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                          Confirmez votre mot de passe <span class="text-red-600">*</span>
                        </label>
                        <input
                          id="delete-password"
                          v-model="deletePassword"
                          type="password"
                          required
                          :class="[
                            'mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 sm:text-sm',
                            passwordError 
                              ? 'border-red-500 dark:border-red-500 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white' 
                              : 'border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500'
                          ]"
                          placeholder="Votre mot de passe"
                          @input="passwordError = ''"
                        />
                        <p v-if="passwordError" class="mt-1 text-sm text-red-600 dark:text-red-400">
                          {{ passwordError }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button
                  type="button"
                  @click="confirmDeleteAccount"
                  :disabled="!deleteReason || !deletePassword || deleteLoading"
                  class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="deleteLoading">Suppression...</span>
                  <span v-else>Confirmer la suppression</span>
                </button>
                <button
                  type="button"
                  @click="showDeleteModal = false; passwordError = ''; deletePassword = ''; deleteReason = ''"
                  :disabled="deleteLoading"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto disabled:opacity-50"
                >
                  Annuler
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, inject, Teleport, Transition } from 'vue'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'

// S'assurer que axios est disponible globalement aussi
const getAxios = () => {
  return window.axios || axios
}

export default {
  name: 'Profile',
  setup() {
    const authStore = useAuthStore()
    const notify = inject('notify')
    const loading = ref(false)
    const avatarInput = ref(null)
    const showDeleteModal = ref(false)
    const deleteReason = ref('')
    const deletePassword = ref('')
    const deleteLoading = ref(false)
    const passwordError = ref('')

    const user = computed(() => authStore.user)

    const form = reactive({
      name: '',
      email: '',
      phone: '',
      company: '',
      position: '',
      bio: '',
      location: '',
      website: '',
      avatar_url: '',
      avatar_preview: null, // Pour l'aperçu immédiat
      avatar_file: null,
      email_notifications: true,
      marketing_emails: false
    })

    const triggerAvatarUpload = () => {
      avatarInput.value?.click()
    }

    const handleAvatarChange = (event) => {
      const file = event.target.files[0]
      if (file) {
        // Vérifier la taille (max 5MB avant compression - sera compressé à 1MB si nécessaire)
        if (file.size > 5 * 1024 * 1024) {
          notify.error('Erreur', 'La photo ne doit pas dépasser 5MB (elle sera compressée automatiquement)')
          return
        }
        // Vérifier le type
        if (!file.type.startsWith('image/')) {
          notify.error('Erreur', 'Le fichier doit être une image')
          return
        }
        form.avatar_file = file
        
        // Afficher un aperçu immédiat avec data URL
        const reader = new FileReader()
        reader.onload = (e) => {
          // Stocker l'aperçu dans un champ séparé pour l'affichage
          form.avatar_preview = e.target.result
          form.avatar_url = e.target.result // Pour l'affichage immédiat
          console.log('✅ Avatar preview loaded:', form.avatar_url.substring(0, 50) + '...')
        }
        reader.readAsDataURL(file)
        
        // Afficher un message si le fichier est > 1MB
        if (file.size > 1024 * 1024) {
          notify.info('Information', 'La photo sera automatiquement compressée pour optimiser l\'espace')
        }
      }
    }

    const updatePreferenceImmediate = async (key, value) => {
      try {
        // Charger préférences actuelles pour merge
        let serverPrefs = {}
        try {
          const cur = await axios.get('/user/profile')
          if (cur.data?.success && cur.data.data?.user?.preferences) {
            serverPrefs = cur.data.data.user.preferences
          }
        } catch (e) {}

        const preferences = { ...serverPrefs, [key]: value }

        const res = await axios.put('/user/preferences', {
          preferences
        })

        if (res.data?.success) {
          // Mettre à jour le store et le form immédiatement
          const updatedPrefs = res.data.data?.preferences || preferences
          authStore.updateUser({ preferences: updatedPrefs })
          form.email_notifications = updatedPrefs.email_notifications !== false
          form.marketing_emails = updatedPrefs.marketing_emails === true
        } else {
          throw new Error(res.data?.message || 'Erreur de mise à jour des préférences')
        }
      } catch (e) {
        // rollback visuel
        if (key === 'email_notifications') {
          form.email_notifications = !value
        } else if (key === 'marketing_emails') {
          form.marketing_emails = !value
        }
        notify.error('Erreur', e.message || 'Impossible de mettre à jour la préférence')
      }
    }

    const toggleEmailNotifications = async () => {
      form.email_notifications = !form.email_notifications
      await updatePreferenceImmediate('email_notifications', form.email_notifications)
    }

    const toggleMarketingEmails = async () => {
      form.marketing_emails = !form.marketing_emails
      await updatePreferenceImmediate('marketing_emails', form.marketing_emails)
    }

    const getAvatarUrl = () => {
      // Si on a un aperçu local (data URL), l'utiliser en priorité
      if (form.avatar_preview) {
        return form.avatar_preview
      }
      
      // Si on a un avatar_url qui est une data URL (commence par data:), l'utiliser
      if (form.avatar_url && form.avatar_url.startsWith('data:')) {
        return form.avatar_url
      }
      
      // Si on a un avatar_url qui est une URL complète (commence par http), la retourner
      // Si elle contient déjà un timestamp, la retourner telle quelle
      if (form.avatar_url && (form.avatar_url.startsWith('http') || form.avatar_url.startsWith('/api/'))) {
        // Si elle contient déjà un timestamp, la retourner
        if (form.avatar_url.includes('?t=')) {
          return form.avatar_url
        }
        // Sinon, ajouter un timestamp pour éviter le cache
        const separator = form.avatar_url.includes('?') ? '&' : '?'
        return form.avatar_url + separator + 't=' + Date.now()
      }
      
      // Sinon, construire l'URL vers l'API sécurisée avec timestamp pour éviter le cache
      if (user.value?.id && user.value?.avatar) {
        const baseURL = (typeof window !== 'undefined' && window.axios?.defaults?.baseURL) 
          ? window.axios.defaults.baseURL 
          : '/api'
        
        const url = `${baseURL}/user/avatar/${user.value.id}?t=` + Date.now()
        
        console.log('🔗 Constructed avatar URL with timestamp:', url, 'from user avatar:', user.value.avatar)
        return url
      }
      
      // Si on a un avatar_url depuis la réponse API, l'utiliser
      if (form.avatar_url && (form.avatar_url.startsWith('/api/') || form.avatar_url.includes('/api/user/avatar/'))) {
        // Si c'est une URL relative, s'assurer qu'elle commence par /api
        if (form.avatar_url.startsWith('/api/')) {
          // Ajouter un timestamp si pas déjà présent
          if (!form.avatar_url.includes('?t=')) {
            return form.avatar_url + '?t=' + Date.now()
          }
          return form.avatar_url
        }
        // Si c'est une URL complète (http), la retourner telle quelle
        if (form.avatar_url.startsWith('http')) {
          return form.avatar_url
        }
        return form.avatar_url
      }
      
      // Si pas d'avatar, retourner null pour afficher l'icône
      return null
    }

    const handleImageError = (event) => {
      console.error('❌ Image load error:', event.target.src)
      console.error('   Form avatar_url:', form.avatar_url)
      console.error('   User avatar_url:', user.value?.avatar_url)
      console.error('   User avatar:', user.value?.avatar)
      
      // Si l'erreur vient de l'API, essayer de retirer le timestamp
      if (event.target.src.includes('?t=')) {
        const urlWithoutTimestamp = event.target.src.split('?t=')[0]
        console.log('   Retrying without timestamp:', urlWithoutTimestamp)
        // Réessayer sans timestamp
        setTimeout(() => {
          form.avatar_url = urlWithoutTimestamp
        }, 100)
        return
      }
      
      // Fallback vers l'avatar généré - masquer l'image
      form.avatar_url = ''
      form.avatar_preview = null
    }

    const handleImageLoad = () => {
      console.log('✅ Image loaded successfully:', form.avatar_url)
    }

    const updateProfile = async () => {
      loading.value = true
      try {
        console.log('🔄 Updating profile with data:', form)
        
        // Préparer FormData pour envoyer tous les champs, y compris l'avatar
        const formData = new FormData()
        
        // Ajouter tous les champs texte (même s'ils sont vides pour permettre de les effacer)
        formData.append('name', form.name || '')
        formData.append('phone', form.phone || '')
        formData.append('company', form.company || '')
        formData.append('position', form.position || '')
        formData.append('bio', form.bio || '')
        formData.append('location', form.location || '')
        formData.append('website', form.website || '')
        
        // Ajouter l'avatar si un fichier a été sélectionné
        if (form.avatar_file) {
          formData.append('avatar', form.avatar_file)
        }
        
        // Mettre à jour les préférences (merge avec celles du serveur pour ne rien perdre)
        let serverPrefs = {}
        try {
          const cur = await axios.get('/user/profile')
          if (cur.data?.success && cur.data.data?.user?.preferences) {
            serverPrefs = cur.data.data.user.preferences
          }
        } catch (e) {}

        const preferences = {
          ...serverPrefs,
          email_notifications: !!form.email_notifications,
          marketing_emails: !!form.marketing_emails,
        }
        
        console.log('📤 Sending profile data')
        console.log('📤 Sending preferences:', preferences)
        
        // Envoyer les données du profil
        const profileResponse = await axios.post('/user/profile', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        
        console.log('✅ Profile update response:', profileResponse.data)
        
        // Envoyer les préférences
        const preferencesResponse = await axios.put('/user/preferences', {
          preferences: preferences
        })
        
        console.log('✅ Preferences update response:', preferencesResponse.data)
        
        if (profileResponse.data.success && preferencesResponse.data.success) {
          // Log pour debug
          console.log('🔄 Updating user in store with:', profileResponse.data.data.user)
          console.log('   avatar_url:', profileResponse.data.data.user?.avatar_url)
          console.log('   avatar:', profileResponse.data.data.user?.avatar)
          
          // Update user in store (profil + prefs)
          const updatedUser = profileResponse.data.data.user
          const updatedPrefs = preferencesResponse.data.data?.preferences || preferences
          authStore.updateUser({ ...updatedUser, preferences: updatedPrefs })
          
          // Vérifier que avatar_url est bien mis à jour
          console.log('✅ User updated in store')
          console.log('   New avatar_url:', authStore.user?.avatar_url)
          console.log('   New avatar:', authStore.user?.avatar)
          
          // Mettre à jour form.avatar_url avec la nouvelle URL de l'API
          // Effacer l'aperçu pour forcer l'utilisation de l'URL de l'API
          form.avatar_preview = null
          form.avatar_file = null
          
          // Mettre à jour avec la nouvelle URL de l'API
          // Forcer la mise à jour avec un nouveau timestamp pour éviter le cache
          if (profileResponse.data.data.user?.avatar_url) {
            // Retirer le timestamp existant s'il y en a un
            const urlWithoutTimestamp = profileResponse.data.data.user.avatar_url.split('?t=')[0]
            form.avatar_url = urlWithoutTimestamp + '?t=' + Date.now()
            console.log('✅ form.avatar_url updated from API response with timestamp:', form.avatar_url)
          } else if (profileResponse.data.data.user?.avatar && authStore.user?.id) {
            // Si avatar_url n'est pas dans la réponse mais avatar existe, construire l'URL
            const baseURL = (typeof window !== 'undefined' && window.axios?.defaults?.baseURL) 
              ? window.axios.defaults.baseURL 
              : '/api'
            form.avatar_url = `${baseURL}/user/avatar/${authStore.user.id}?t=` + Date.now()
            console.log('✅ form.avatar_url constructed from avatar field with timestamp:', form.avatar_url)
          } else if (authStore.user?.id) {
            // Si rien n'est disponible, construire l'URL avec timestamp
            const baseURL = (typeof window !== 'undefined' && window.axios?.defaults?.baseURL) 
              ? window.axios.defaults.baseURL 
              : '/api'
            form.avatar_url = `${baseURL}/user/avatar/${authStore.user.id}?t=` + Date.now()
            console.log('✅ form.avatar_url constructed with timestamp:', form.avatar_url)
          }
          
          // Show success message
          notify.success('Succès', 'Profil mis à jour avec succès!')
        } else {
          throw new Error(profileResponse.data.message || 'Update failed')
        }
      } catch (error) {
        console.error('❌ Error updating profile:', error)
        console.error('   Status:', error.response?.status)
        console.error('   Data:', error.response?.data)
        if (error.response?.data?.message) {
          notify.error('Erreur', error.response.data.message)
        } else {
          notify.error('Erreur', 'Erreur lors de la mise à jour du profil')
        }
      } finally {
        loading.value = false
      }
    }

    onMounted(async () => {
      if (user.value) {
        console.log('📋 Loading user data into form:', user.value)
        console.log('   avatar_url from user:', user.value.avatar_url)
        console.log('   avatar from user:', user.value.avatar)
        
        Object.assign(form, {
          name: user.value.name || '',
          email: user.value.email || '',
          phone: user.value.phone || '',
          company: user.value.company || '',
          position: user.value.position || '',
          bio: user.value.bio || '',
          location: user.value.location || '',
          website: user.value.website || '',
          avatar_url: user.value.avatar_url || '',
          avatar_preview: null, // Pas d'aperçu au chargement
          email_notifications: user.value.preferences?.email_notifications !== false,
          marketing_emails: user.value.preferences?.marketing_emails === true
        })
        
        // Si on a un avatar mais pas d'avatar_url, construire l'URL
        if (user.value.avatar && !form.avatar_url && user.value.id) {
          form.avatar_url = `/api/user/avatar/${user.value.id}`
          console.log('✅ Constructed avatar_url:', form.avatar_url)
        }
        
        console.log('✅ Form initialized, avatar_url:', form.avatar_url)
      }

      // Recharger depuis l'API pour s'assurer d'avoir les dernières préférences et infos
      try {
        const res = await axios.get('/user/profile')
        if (res.data?.success && res.data.data?.user) {
          const u = res.data.data.user
          authStore.updateUser(u)
          form.email_notifications = u.preferences?.email_notifications !== false
          form.marketing_emails = u.preferences?.marketing_emails === true
        }
      } catch (e) {
        console.error('Failed to refresh profile on mount:', e)
      }
    })

    const confirmDeleteAccount = async () => {
      // Réinitialiser l'erreur de mot de passe
      passwordError.value = ''
      
      if (!deleteReason.value || !deletePassword.value) {
        notify.error('Erreur', 'Veuillez remplir tous les champs')
        return
      }
      
      deleteLoading.value = true
      try {
        const response = await axios.delete('/user/delete', {
          data: {
            password: deletePassword.value,
            reason: deleteReason.value
          }
        })
        
        if (response.data.success) {
          notify.success('Succès', response.data.message || 'Votre compte a été désactivé avec succès')
          // Déconnexion après désactivation
          await authStore.logout()
          // Rediriger vers la page de login
          setTimeout(() => {
            window.location.href = '/login'
          }, 2000)
        } else {
          throw new Error(response.data.message || 'Erreur lors de la suppression')
        }
      } catch (error) {
        console.error('Error deleting account:', error)
        
        // Vérifier le code de statut HTTP pour identifier le type d'erreur
        if (error.response?.status === 400) {
          // Erreur 400 = mot de passe incorrect
          // Afficher le message d'erreur sous le champ de mot de passe
          passwordError.value = error.response?.data?.message === 'Password is incorrect' 
            ? 'Le mot de passe est incorrect' 
            : (error.response?.data?.message || 'Le mot de passe est incorrect')
          // Réinitialiser le champ mot de passe pour permettre une nouvelle tentative
          deletePassword.value = ''
        } else if (error.response?.status === 422) {
          // Erreur 422 = validation failed
          const errorMessage = error.response?.data?.message || 'Veuillez vérifier les informations saisies'
          notify.error('Erreur de validation', errorMessage)
        } else if (error.response?.data?.message) {
          // Autre erreur avec message spécifique
          notify.error('Erreur', error.response.data.message)
        } else {
          // Erreur générique
          notify.error('Erreur', 'Erreur lors de la suppression du compte')
        }
      } finally {
        deleteLoading.value = false
      }
    }

    return {
      user,
      form,
      loading,
      avatarInput,
      showDeleteModal,
      deleteReason,
      deletePassword,
      deleteLoading,
      triggerAvatarUpload,
      handleAvatarChange,
      toggleEmailNotifications,
      toggleMarketingEmails,
      getAvatarUrl,
      handleImageError,
      handleImageLoad,
      updateProfile,
      confirmDeleteAccount
    }
  }
}
</script>

<style scoped>
/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active :deep(.relative),
.modal-leave-active :deep(.relative) {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from :deep(.relative),
.modal-leave-to :deep(.relative) {
  opacity: 0;
  transform: scale(0.95);
}
</style>
