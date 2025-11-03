<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="close">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Paramètres de sécurité
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

        <div class="space-y-6">
          <!-- Change Password -->
          <div class="border-b border-gray-200 dark:border-gray-600 pb-6">
            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
              Changer le mot de passe
            </h4>
            <form @submit.prevent="changePassword" class="space-y-4">
              <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Mot de passe actuel
                </label>
                <input
                  id="current_password"
                  v-model="passwordForm.current_password"
                  type="password"
                  required
                  class="input-field mt-1"
                  :class="{ 'border-red-500': passwordErrors.current_password }"
                />
                <p v-if="passwordErrors.current_password" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ passwordErrors.current_password[0] }}
                </p>
              </div>

              <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Nouveau mot de passe
                </label>
                <input
                  id="new_password"
                  v-model="passwordForm.password"
                  type="password"
                  required
                  class="input-field mt-1"
                  :class="{ 'border-red-500': passwordErrors.password }"
                />
                <p v-if="passwordErrors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ passwordErrors.password[0] }}
                </p>
              </div>

              <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Confirmer le nouveau mot de passe
                </label>
                <input
                  id="password_confirmation"
                  v-model="passwordForm.password_confirmation"
                  type="password"
                  required
                  class="input-field mt-1"
                  :class="{ 'border-red-500': passwordErrors.password_confirmation }"
                />
                <p v-if="passwordErrors.password_confirmation" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ passwordErrors.password_confirmation[0] }}
                </p>
              </div>

              <div v-if="passwordError" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex">
                  <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                  </svg>
                  <div class="ml-3">
                    <p class="text-sm text-red-800 dark:text-red-200">{{ passwordError }}</p>
                  </div>
                </div>
              </div>

              <div v-if="passwordSuccess" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex">
                  <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                  </svg>
                  <div class="ml-3">
                    <p class="text-sm text-green-800 dark:text-green-200">{{ passwordSuccess }}</p>
                  </div>
                </div>
              </div>

              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="passwordLoading"
                  class="btn-primary"
                >
                  {{ passwordLoading ? 'Changement...' : 'Changer le mot de passe' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Two-Factor Authentication -->
          <div class="border-b border-gray-200 dark:border-gray-600 pb-6">
            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
              Authentification à deux facteurs
            </h4>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Ajoutez une couche de sécurité supplémentaire à votre compte
                </p>
              </div>
              <button
                class="btn-secondary"
                disabled
              >
                Bientôt disponible
              </button>
            </div>
          </div>

          <!-- Account Actions -->
          <div class="space-y-4">
            <h4 class="text-md font-medium text-gray-900 dark:text-white">
              Actions du compte
            </h4>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
              <div class="flex">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.726-1.36 3.491 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    Zone de danger
                  </h3>
                  <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                    <p>Ces actions sont irréversibles. Soyez prudent.</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-between items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
              <div>
                <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                  Désactiver le compte
                </h5>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Votre compte sera désactivé mais pourra être réactivé plus tard
                </p>
              </div>
              <button
                @click="showDeactivateModal = true"
                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
              >
                Désactiver
              </button>
            </div>

            <div class="flex justify-between items-center p-4 border border-red-200 dark:border-red-800 rounded-lg">
              <div>
                <h5 class="text-sm font-medium text-red-900 dark:text-red-200">
                  Supprimer le compte
                </h5>
                <p class="text-sm text-red-600 dark:text-red-400">
                  Supprimer définitivement votre compte et toutes vos données
                </p>
              </div>
              <button
                @click="showDeleteModal = true"
                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
              >
                Supprimer
              </button>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button
            @click="close"
            class="btn-secondary"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Deactivate Modal -->
    <div v-if="showDeactivateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-60" @click="showDeactivateModal = false">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Désactiver le compte
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Êtes-vous sûr de vouloir désactiver votre compte ? Vous pourrez le réactiver plus tard.
          </p>
          <div class="flex justify-end space-x-3">
            <button
              @click="showDeactivateModal = false"
              class="btn-secondary"
            >
              Annuler
            </button>
            <button
              @click="deactivateAccount"
              :disabled="deactivateLoading"
              class="bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-4 rounded-lg disabled:opacity-50"
            >
              {{ deactivateLoading ? 'Désactivation...' : 'Désactiver' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-60" @click="showDeleteModal = false">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
        <div class="mt-3">
          <h3 class="text-lg font-medium text-red-900 dark:text-red-200 mb-4">
            Supprimer le compte
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Cette action est irréversible. Toutes vos données seront supprimées définitivement.
          </p>
          <div class="mb-4">
            <label for="confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Tapez "SUPPRIMER" pour confirmer
            </label>
            <input
              id="confirmation"
              v-model="deleteConfirmation"
              type="text"
              class="input-field mt-1"
              placeholder="SUPPRIMER"
            />
          </div>
          <div class="flex justify-end space-x-3">
            <button
              @click="showDeleteModal = false"
              class="btn-secondary"
            >
              Annuler
            </button>
            <button
              @click="deleteAccount"
              :disabled="deleteLoading || deleteConfirmation !== 'SUPPRIMER'"
              class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg disabled:opacity-50"
            >
              {{ deleteLoading ? 'Suppression...' : 'Supprimer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'SecurityModal',
  emits: ['close'],
  setup(props, { emit }) {
    const authStore = useAuthStore()
    const router = useRouter()
    
    const passwordForm = reactive({
      current_password: '',
      password: '',
      password_confirmation: ''
    })
    
    const passwordErrors = ref({})
    const passwordError = ref('')
    const passwordSuccess = ref('')
    const passwordLoading = ref(false)
    
    const showDeactivateModal = ref(false)
    const showDeleteModal = ref(false)
    const deactivateLoading = ref(false)
    const deleteLoading = ref(false)
    const deleteConfirmation = ref('')

    const changePassword = async () => {
      passwordLoading.value = true
      passwordErrors.value = {}
      passwordError.value = ''
      passwordSuccess.value = ''

      try {
        const response = await axios.post('/api/user/change-password', passwordForm)
        
        if (response.data.success) {
          passwordSuccess.value = 'Mot de passe changé avec succès'
          passwordForm.current_password = ''
          passwordForm.password = ''
          passwordForm.password_confirmation = ''
        } else {
          throw new Error(response.data.message || 'Password change failed')
        }
      } catch (err) {
        if (err.response?.data?.errors) {
          passwordErrors.value = err.response.data.errors
        } else {
          passwordError.value = err.message
        }
      } finally {
        passwordLoading.value = false
      }
    }

    const deactivateAccount = async () => {
      deactivateLoading.value = true
      
      try {
        const response = await axios.post('/api/user/deactivate', {
          password: passwordForm.current_password
        })
        
        if (response.data.success) {
          await authStore.logout()
          router.push('/login')
        }
      } catch (err) {
        alert(err.response?.data?.message || 'Erreur lors de la désactivation')
      } finally {
        deactivateLoading.value = false
        showDeactivateModal.value = false
      }
    }

    const deleteAccount = async () => {
      deleteLoading.value = true
      
      try {
        const response = await axios.delete('/api/user/account', {
          data: {
            password: passwordForm.current_password,
            confirmation: deleteConfirmation.value
          }
        })
        
        if (response.data.success) {
          await authStore.logout()
          router.push('/login')
        }
      } catch (err) {
        alert(err.response?.data?.message || 'Erreur lors de la suppression')
      } finally {
        deleteLoading.value = false
        showDeleteModal.value = false
      }
    }

    const close = () => {
      emit('close')
    }

    return {
      passwordForm,
      passwordErrors,
      passwordError,
      passwordSuccess,
      passwordLoading,
      showDeactivateModal,
      showDeleteModal,
      deactivateLoading,
      deleteLoading,
      deleteConfirmation,
      changePassword,
      deactivateAccount,
      deleteAccount,
      close
    }
  }
}
</script>
