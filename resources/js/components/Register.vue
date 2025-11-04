<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-md w-full">
      <!-- Logo -->
      <div class="text-center mb-8">
        <img 
          src="/logo.png" 
          alt="HERIME Logo" 
          class="h-12 w-auto mx-auto mb-4"
        />
      </div>
      
      <!-- Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-8 space-y-6">
        <div class="text-center">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Créer un compte
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Rejoignez l'écosystème HERIME
          </p>
        </div>
        
        <form class="space-y-4" @submit.prevent="handleRegister">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nom complet
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 dark:bg-gray-700 dark:text-white"
              :class="{ 'border-red-500 focus:ring-red-500': errors.name }"
              placeholder="Votre nom complet"
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.name[0] }}
            </p>
          </div>
          
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Adresse email
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 dark:bg-gray-700 dark:text-white"
              :class="{ 'border-red-500 focus:ring-red-500': errors.email }"
              placeholder="mail@exemple.com"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.email[0] }}
            </p>
          </div>
          
          <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Téléphone (optionnel)
            </label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 dark:bg-gray-700 dark:text-white"
              :class="{ 'border-red-500 focus:ring-red-500': errors.phone }"
              placeholder="+243 000 000 000"
            />
            <p v-if="errors.phone" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.phone[0] }}
            </p>
          </div>
          
          <div>
            <label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Entreprise (optionnel)
            </label>
            <input
              id="company"
              v-model="form.company"
              type="text"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 dark:bg-gray-700 dark:text-white"
              :class="{ 'border-red-500 focus:ring-red-500': errors.company }"
              placeholder="Nom de l'entreprise"
            />
            <p v-if="errors.company" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.company[0] }}
            </p>
          </div>
          
          <div>
            <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Poste (optionnel)
            </label>
            <input
              id="position"
              v-model="form.position"
              type="text"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 dark:bg-gray-700 dark:text-white"
              :class="{ 'border-red-500 focus:ring-red-500': errors.position }"
              placeholder="Votre poste"
            />
            <p v-if="errors.position" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.position[0] }}
            </p>
          </div>
          
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Mot de passe
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 dark:bg-gray-700 dark:text-white pr-12"
                :class="{ 'border-red-500 focus:ring-red-500': errors.password }"
                placeholder="Votre mot de passe"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <svg v-if="!showPassword" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                </svg>
              </button>
            </div>
            <p v-if="errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.password[0] }}
            </p>
          </div>
          
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Confirmer le mot de passe
            </label>
            <div class="relative">
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                :type="showPasswordConfirmation ? 'text' : 'password'"
                required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 dark:bg-gray-700 dark:text-white pr-12"
                :class="{ 'border-red-500 focus:ring-red-500': errors.password_confirmation }"
                placeholder="Confirmez votre mot de passe"
              />
              <button
                type="button"
                @click="showPasswordConfirmation = !showPasswordConfirmation"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <svg v-if="!showPasswordConfirmation" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                </svg>
              </button>
            </div>
            <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.password_confirmation[0] }}
            </p>
          </div>

          <div class="flex items-center">
            <input
              id="terms"
              v-model="form.terms"
              type="checkbox"
              required
              class="h-4 w-4 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
              style="accent-color: #003366;"
            />
            <label for="terms" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
              J'accepte les
              <a href="#" class="hover:opacity-80" style="color: #ffcc33;">
                conditions d'utilisation
              </a>
              et la
              <a href="#" class="hover:opacity-80" style="color: #ffcc33;">
                politique de confidentialité
              </a>
            </label>
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

          <button
            type="submit"
            :disabled="loading"
            class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 text-white font-medium rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            style="background-color: #003366;"
            @mouseenter="$event.target.style.backgroundColor = '#ffcc33'"
            @mouseleave="$event.target.style.backgroundColor = '#003366'"
          >
            <span v-if="loading" class="flex items-center justify-center">
              <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
              Création du compte...
            </span>
            <span v-else>Créer le compte</span>
          </button>
        </form>
        
        <!-- Lien vers la connexion -->
        <div class="text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Vous avez déjà un compte ?
            <button
              @click="$emit('switch-to-login')"
              class="font-medium hover:opacity-80 ml-1"
              style="color: #ffcc33;"
            >
              Se connecter
            </button>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'Register',
  emits: ['switch-to-login'],
  setup(props, { emit }) {
    const router = useRouter()
    const authStore = useAuthStore()
    
    // Initialiser le thème sombre si nécessaire
    onMounted(() => {
      // Vérifier la préférence dark mode sauvegardée
      const savedDarkMode = localStorage.getItem('darkMode')
      if (savedDarkMode !== null) {
        const isDark = savedDarkMode === 'true'
        document.documentElement.classList.toggle('dark', isDark)
      } else {
        // Vérifier la préférence système
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        document.documentElement.classList.toggle('dark', prefersDark)
      }
    })
    
    const form = reactive({
      name: '',
      email: '',
      phone: '',
      company: '',
      position: '',
      password: '',
      password_confirmation: '',
      terms: false
    })
    
    const errors = ref({})
    const error = ref('')
    const loading = ref(false)
    const showPassword = ref(false)
    const showPasswordConfirmation = ref(false)

    const handleRegister = async () => {
      loading.value = true
      errors.value = {}
      error.value = ''

      try {
        await authStore.register(form)
        // Redirect to dashboard after successful registration
        router.push('/dashboard')
      } catch (err) {
        console.error('Register error:', err)
        if (err.response?.data?.errors) {
          errors.value = err.response.data.errors
        } else if (err.response?.data?.message) {
          error.value = err.response.data.message
        } else if (err.response?.status === 422) {
          error.value = 'Veuillez vérifier les informations saisies.'
        } else if (err.response?.status === 409) {
          error.value = 'Cette adresse email est déjà utilisée.'
        } else if (err.response?.status === 404) {
          error.value = 'Service non disponible. Veuillez réessayer plus tard.'
        } else {
          error.value = err.message || 'Une erreur est survenue lors de la création du compte.'
        }
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      errors,
      error,
      loading,
      showPassword,
      showPasswordConfirmation,
      handleRegister
    }
  }
}
</script>