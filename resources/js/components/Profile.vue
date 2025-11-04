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
              <div v-if="form.avatar_url" class="h-20 w-20 rounded-full overflow-hidden">
                <img
                  :src="form.avatar_url"
                  :alt="form.name"
                  class="h-full w-full object-cover"
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
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'

export default {
  name: 'Profile',
  setup() {
    const authStore = useAuthStore()
    const notify = inject('notify')
    const loading = ref(false)
    const avatarInput = ref(null)

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
        // Vérifier la taille (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
          notify.error('Erreur', 'La photo ne doit pas dépasser 2MB')
          return
        }
        // Vérifier le type
        if (!file.type.startsWith('image/')) {
          notify.error('Erreur', 'Le fichier doit être une image')
          return
        }
        form.avatar_file = file
        const reader = new FileReader()
        reader.onload = (e) => {
          form.avatar_url = e.target.result
        }
        reader.readAsDataURL(file)
      }
    }

    const toggleEmailNotifications = () => {
      form.email_notifications = !form.email_notifications
    }

    const toggleMarketingEmails = () => {
      form.marketing_emails = !form.marketing_emails
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
        
        // Mettre à jour les préférences
        const preferences = {
          email_notifications: form.email_notifications,
          marketing_emails: form.marketing_emails
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
        const preferencesResponse = await axios.post('/user/preferences', {
          preferences: preferences
        })
        
        console.log('✅ Preferences update response:', preferencesResponse.data)
        
        if (profileResponse.data.success && preferencesResponse.data.success) {
          // Update user in store
          authStore.updateUser(profileResponse.data.data.user)
          
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

    onMounted(() => {
      if (user.value) {
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
          email_notifications: user.value.preferences?.email_notifications !== false,
          marketing_emails: user.value.preferences?.marketing_emails === true
        })
      }
    })

    return {
      user,
      form,
      loading,
      avatarInput,
      triggerAvatarUpload,
      handleAvatarChange,
      toggleEmailNotifications,
      toggleMarketingEmails,
      updateProfile
    }
  }
}
</script>
