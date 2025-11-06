<template>
  <div :class="['flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900', requiresTwoFactor ? 'min-h-screen overflow-y-auto' : 'h-screen overflow-hidden']">
    <div class="max-w-lg w-full">
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
            Bienvenue
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Connectez-vous à votre compte HERIME
          </p>
        </div>
        
        <form class="space-y-4" @submit.prevent="requiresTwoFactor ? handleVerify2FA() : handleLogin()">
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
              placeholder="votre@email.com"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.email[0] }}
            </p>
          </div>
          
          <div v-if="!requiresTwoFactor">
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

          <div v-if="requiresTwoFactor" class="space-y-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
              <div class="flex">
                <svg class="h-5 w-5 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <div class="ml-3">
                  <p class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">
                    Authentification à deux facteurs requise
                  </p>
                  <p class="text-sm text-blue-700 dark:text-blue-300">
                    Veuillez entrer le code à 6 chiffres de votre application d'authentification.
                  </p>
                </div>
              </div>
            </div>
            
            <div>
              <label for="two_factor_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Code d'authentification à deux facteurs
              </label>
              <input
                id="two_factor_code"
                v-model="twoFactorCode"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                required
                placeholder="000000"
                autofocus
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 dark:bg-gray-700 dark:text-white text-center text-2xl tracking-widest font-mono"
                :class="{ 'border-red-500 focus:ring-red-500': errors.two_factor_code }"
                @input="errors.two_factor_code = null; twoFactorCode = twoFactorCode.replace(/[^0-9]/g, '')"
              />
              <p v-if="errors.two_factor_code" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.two_factor_code[0] || errors.two_factor_code }}
              </p>
              <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ error }}
              </p>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Vous pouvez également utiliser un code de récupération à la place.
              </p>
            </div>
            <div class="flex items-center justify-between pt-2">
              <button type="button" class="text-sm text-gray-600 dark:text-gray-300 hover:underline" @click="requiresTwoFactor = false; twoFactorCode = ''; error = ''; errors = {}">
                ← Retour à la connexion
              </button>
            </div>
          </div>

          <div v-if="!requiresTwoFactor" class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember-me"
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                style="accent-color: #003366;"
              />
              <label for="remember-me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Se souvenir de moi
              </label>
            </div>

            <div class="text-sm">
              <button 
                type="button"
                @click="showForgotPassword = true"
                class="font-medium hover:opacity-80" 
                style="color: #ffcc33;"
              >
                Mot de passe oublié ?
              </button>
            </div>
          </div>

        <div v-if="error && !requiresTwoFactor" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
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
              Connexion...
            </span>
            <span v-else>{{ requiresTwoFactor ? 'Vérifier' : 'Se connecter' }}</span>
          </button>
        </form>
        
        <!-- Lien vers l'inscription -->
        <div class="text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Vous n'avez pas de compte ?
            <button
              @click="handleSwitchToRegister"
              class="font-medium hover:opacity-80 ml-1"
              style="color: #ffcc33;"
            >
              Créer un compte
            </button>
          </p>
        </div>
        
        <!-- Bouton retour au site externe -->
        <div v-if="externalSiteUrl" class="text-center mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <button
            @click="handleReturnToSite"
            class="inline-flex items-center text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
          >
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour au site
          </button>
        </div>
      </div>
    </div>
    
    <!-- Forgot Password Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showForgotPassword" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showForgotPassword = false"></div>
          
          <!-- Modal container -->
          <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <!-- Modal panel -->
            <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg" @click.stop>
              <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10" style="background-color: #e0f2fe;">
                    <svg class="h-6 w-6" style="color: #003366;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                    </svg>
                  </div>
                  <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left flex-1">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">
                      Mot de passe oublié
                    </h3>
                    <div class="mt-2">
                      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
                      </p>
                      
                      <form @submit.prevent="handleForgotPassword" class="space-y-4">
                        <div>
                          <label for="forgot-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Adresse email
                          </label>
                          <input
                            id="forgot-email"
                            v-model="forgotEmail"
                            type="email"
                            required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 dark:bg-gray-700 dark:text-white"
                            :class="{ 'border-red-500 focus:ring-red-500': forgotPasswordError }"
                            placeholder="votre@email.com"
                          />
                          <p v-if="forgotPasswordError" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ forgotPasswordError }}
                          </p>
                        </div>
                        
                        <div v-if="forgotPasswordSuccess" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                          <div class="flex">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                              <p class="text-sm text-green-800 dark:text-green-200">{{ forgotPasswordSuccess }}</p>
                              <p v-if="resetUrl" class="mt-2 text-xs text-green-700 dark:text-green-300">
                                <strong>Lien de réinitialisation (dev uniquement):</strong><br>
                                <a :href="resetUrl" target="_blank" class="underline break-all">{{ resetUrl }}</a>
                              </p>
                            </div>
                          </div>
                        </div>
                        
                        <div class="mt-5 sm:mt-6 flex flex-col-reverse sm:flex-row-reverse gap-3">
                          <button
                            type="submit"
                            :disabled="forgotPasswordLoading"
                            class="inline-flex w-full justify-center rounded-md px-3 py-2.5 text-sm font-semibold text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                            style="background-color: #003366;"
                            @mouseenter="$event.target.style.backgroundColor = '#ffcc33'"
                            @mouseleave="$event.target.style.backgroundColor = '#003366'"
                          >
                            <span v-if="forgotPasswordLoading" class="flex items-center">
                              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                              Envoi...
                            </span>
                            <span v-else>Envoyer le lien</span>
                          </button>
                          <button
                            type="button"
                            @click="showForgotPassword = false; forgotEmail = ''; forgotPasswordError = ''; forgotPasswordSuccess = ''; resetUrl = ''"
                            :disabled="forgotPasswordLoading"
                            class="inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-700 px-3 py-2.5 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:w-auto disabled:opacity-50"
                          >
                            Annuler
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
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
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'

export default {
  name: 'Login',
  emits: ['switch-to-register'],
  setup(props, { emit }) {
    const router = useRouter()
    const route = useRoute()
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
      email: '',
      password: '',
      remember: false
    })
    
    const errors = ref({})
    const error = ref('')
    const loading = ref(false)
    const showPassword = ref(false)
    const requiresTwoFactor = ref(false)
    const twoFactorCode = ref('')
    
    // Forgot password
    const showForgotPassword = ref(false)
    const forgotEmail = ref('')
    const forgotPasswordError = ref('')
    const forgotPasswordSuccess = ref('')
    const forgotPasswordLoading = ref(false)
    const resetUrl = ref('')
    
    const notify = inject('notify', {
      success: () => {},
      error: () => {},
      info: () => {}
    })
    
    // Détecter si l'utilisateur vient d'un site externe
    const externalSiteUrl = computed(() => {
      const redirectParam = route.query.redirect
      console.log('[Login] externalSiteUrl computed - redirectParam:', redirectParam)
      
      if (!redirectParam || typeof redirectParam !== 'string') {
        console.log('[Login] No redirect param or not string')
        return null
      }
      
      try {
        // Décoder l'URL si nécessaire (peut être double-encodé)
        let redirectUrl = redirectParam
        
        // Essayer de décoder plusieurs fois si nécessaire
        for (let i = 0; i < 3; i++) {
          try {
            const decoded = decodeURIComponent(redirectUrl)
            if (decoded === redirectUrl) break // Plus de décodage nécessaire
            // Vérifier si c'est une URL valide
            try {
              new URL(decoded)
              redirectUrl = decoded
            } catch (e) {
              break // Garder la dernière URL valide
            }
          } catch (e) {
            break // Erreur de décodage, garder ce qu'on a
          }
        }
        
        console.log('[Login] Decoded redirectUrl:', redirectUrl)
        
        // Vérifier si c'est une URL valide
        if (!redirectUrl || !redirectUrl.startsWith('http')) {
          console.log('[Login] Redirect URL is not valid HTTP URL')
          return null
        }
        
        // Extraire l'URL de base (sans les paramètres de query comme token)
        const url = new URL(redirectUrl)
        const currentHost = window.location.hostname
        
        console.log('[Login] URL parsed:', {
          protocol: url.protocol,
          hostname: url.hostname,
          pathname: url.pathname,
          search: url.search,
          currentHost: currentHost
        })
        
        // Normaliser les hostnames (enlever www. si présent)
        const urlHost = url.hostname.replace(/^www\./, '')
        const currentHostNormalized = currentHost.replace(/^www\./, '')
        
        console.log('[Login] Host comparison:', {
          urlHost,
          currentHostNormalized,
          isExternal: urlHost !== currentHostNormalized && urlHost !== 'compte.herime.com'
        })
        
        // Vérifier si c'est un domaine externe
        if (urlHost !== currentHostNormalized && urlHost !== 'compte.herime.com') {
          // Retourner l'URL de base du site externe
          // Si pathname est /sso/callback, on retourne juste la racine du site
          let returnPath = url.pathname
          if (returnPath === '/sso/callback' || returnPath.startsWith('/sso/')) {
            returnPath = '/'
          }
          
          const returnUrl = `${url.protocol}//${url.hostname}${returnPath}`
          console.log('[Login] External site URL detected:', returnUrl)
          return returnUrl
        }
        
        console.log('[Login] Not an external site')
      } catch (e) {
        console.error('[Login] Error parsing redirect URL:', e)
      }
      
      return null
    })
    
    const handleReturnToSite = () => {
      console.log('[Login] handleReturnToSite called', {
        externalSiteUrl: externalSiteUrl.value,
        redirectParam: route.query.redirect
      })
      
      // Utiliser directement le computed si disponible
      if (externalSiteUrl.value) {
        console.log('[Login] Redirecting to computed URL:', externalSiteUrl.value)
        window.location.replace(externalSiteUrl.value)
        return
      }
      
      // Fallback: traiter directement le paramètre redirect
      const redirectParam = route.query.redirect
      if (!redirectParam || typeof redirectParam !== 'string') {
        console.warn('[Login] No redirect parameter found')
        return
      }
      
      try {
        // Décoder l'URL si nécessaire (peut être double-encodé)
        let redirectUrl = redirectParam
        for (let i = 0; i < 3; i++) {
          try {
            const decoded = decodeURIComponent(redirectUrl)
            if (decoded === redirectUrl) break
            try {
              new URL(decoded)
              redirectUrl = decoded
            } catch (e) {
              break
            }
          } catch (e) {
            break
          }
        }
        
        console.log('[Login] Decoded redirectUrl:', redirectUrl)
        
        // Vérifier si c'est une URL valide
        if (!redirectUrl || !redirectUrl.startsWith('http')) {
          console.error('[Login] Invalid redirect URL:', redirectUrl)
          return
        }
        
        // Extraire l'URL de base
        const url = new URL(redirectUrl)
        const currentHost = window.location.hostname.replace(/^www\./, '')
        const urlHost = url.hostname.replace(/^www\./, '')
        
        console.log('[Login] Host comparison:', {
          urlHost,
          currentHost,
          isExternal: urlHost !== currentHost && urlHost !== 'compte.herime.com'
        })
        
        // Vérifier si c'est un domaine externe
        if (urlHost !== currentHost && urlHost !== 'compte.herime.com') {
          // Si pathname est /sso/callback, retourner à la racine
          let returnPath = url.pathname
          if (returnPath === '/sso/callback' || returnPath.startsWith('/sso/')) {
            returnPath = '/'
          }
          
          const returnUrl = `${url.protocol}//${url.hostname}${returnPath}`
          console.log('[Login] Redirecting to external site:', returnUrl)
          window.location.replace(returnUrl)
        } else {
          console.warn('[Login] Not an external site, cannot redirect')
        }
      } catch (e) {
        console.error('[Login] Error in handleReturnToSite:', e)
      }
    }
    const handleSwitchToRegister = async () => {
      try {
        // Toujours interroger le serveur pour éviter un cache obsolète
        const baseURL = window.location.origin + '/api'
        const response = await fetch(`${baseURL}/settings/public`, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })
        let enabled = true
        if (response.ok) {
          const data = await response.json()
          if (data?.success && data?.data) {
            enabled = !!data.data.registration_enabled
            localStorage.setItem('registration_enabled', String(enabled))
          }
        } else {
          // Fallback: utiliser la dernière valeur connue si disponible
          const cached = localStorage.getItem('registration_enabled')
          if (cached !== null) enabled = cached === 'true'
        }

        if (!enabled) {
          notify.info('Information', 'Les inscriptions sont actuellement désactivées.')
          return
        }
        emit('switch-to-register')
      } catch (e) {
        console.error('Failed to check registration setting', e)
        // Dernier recours: utiliser le cache, sinon autoriser
        const cached = localStorage.getItem('registration_enabled')
        if (cached === 'false') {
          notify.info('Information', 'Les inscriptions sont actuellement désactivées.')
          return
        }
        emit('switch-to-register')
      }
    }

    const handleLogin = async () => {
      console.log('Login attempt started', form)
      loading.value = true
      errors.value = {}
      error.value = ''
      requiresTwoFactor.value = false // Reset au début

      try {
        // Récupérer les paramètres redirect et force_token de l'URL pour les passer à l'API
        const loginData = { ...form }
        if (route.query.redirect) {
          loginData.redirect = route.query.redirect
        }
        if (route.query.force_token) {
          loginData.force_token = route.query.force_token
        }
        if (route.query.client_domain) {
          loginData.client_domain = route.query.client_domain
        }
        
        console.log('Calling authStore.login...', loginData)
        const result = await authStore.login(loginData)
        console.log('Login successful:', result)
        
        // Vérifier s'il y a une redirection SSO vers un domaine externe
        if (result?.data?.sso_redirect_url) {
          // Rediriger vers le domaine externe avec le token SSO
          window.location.href = result.data.sso_redirect_url
          return
        }
        
        // Sinon, rediriger vers le dashboard local
        router.push('/dashboard')
      } catch (err) {
        console.error('Login error:', err)
        console.log('Error requiresTwoFactor:', err.requiresTwoFactor)
        console.log('Error response data:', err.response?.data)
        console.log('Error response requires_two_factor:', err.response?.data?.requires_two_factor)
        
        // Vérifier si la 2FA est requise - vérifier plusieurs endroits
        const needs2FA = err.requiresTwoFactor === true || 
                        err.response?.data?.requires_two_factor === true ||
                        (err.response?.status === 200 && err.response?.data?.requires_two_factor)
        
        console.log('Needs 2FA:', needs2FA)
        
        if (needs2FA) {
          console.log('Setting requiresTwoFactor to true')
          requiresTwoFactor.value = true
          error.value = err.response?.data?.message || err.message || 'Veuillez entrer le code d\'authentification à deux facteurs.'
          console.log('requiresTwoFactor.value after setting:', requiresTwoFactor.value)
          console.log('Error message set:', error.value)
          loading.value = false
          return
        }
        
        if (err.response?.data?.errors) {
          errors.value = err.response.data.errors
        } else if (err.response?.data?.message) {
          // Utiliser le message du serveur (déjà traduit en français)
          error.value = err.response.data.message
        } else if (err.response?.status === 401) {
          error.value = 'Identifiants incorrects. Veuillez vérifier votre email et mot de passe.'
        } else if (err.response?.status === 403) {
          error.value = 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
        } else if (err.response?.status === 404) {
          error.value = 'Service non disponible. Veuillez réessayer plus tard.'
        } else {
          error.value = err.message || 'Une erreur est survenue lors de la connexion.'
        }
      } finally {
        loading.value = false
      }
    }

    const handleVerify2FA = async () => {
      if (!twoFactorCode.value || twoFactorCode.value.length !== 6) {
        errors.value = { two_factor_code: 'Veuillez entrer un code de 6 chiffres' }
        return
      }

      loading.value = true
      errors.value = {}
      error.value = ''

      try {
        const result = await authStore.verifyTwoFactor(form.email, twoFactorCode.value)
        console.log('2FA verification successful:', result)
        
        // Vérifier s'il y a une redirection SSO vers un domaine externe
        if (result?.data?.sso_redirect_url) {
          // Rediriger vers le domaine externe avec le token SSO
          window.location.href = result.data.sso_redirect_url
          return
        }
        
        // Sinon, rediriger vers le dashboard local
        router.push('/dashboard')
      } catch (err) {
        console.error('2FA verification error:', err)
        if (err.response?.data?.errors) {
          errors.value = err.response.data.errors
        } else if (err.response?.data?.message) {
          error.value = err.response.data.message
        } else {
          error.value = err.message || 'Code de vérification invalide.'
        }
      } finally {
        loading.value = false
      }
    }
    
    const handleForgotPassword = async () => {
      forgotPasswordError.value = ''
      forgotPasswordSuccess.value = ''
      resetUrl.value = ''
      forgotPasswordLoading.value = true
      
      try {
        const response = await axios.post('/password/forgot', {
          email: forgotEmail.value
        })
        
        if (response.data.success) {
          forgotPasswordSuccess.value = response.data.message || 'Un lien de réinitialisation a été envoyé à votre adresse email.'
          
          // En développement, afficher l'URL de réinitialisation
          if (response.data.data?.reset_url) {
            resetUrl.value = response.data.data.reset_url
          }
          
          notify.success('Succès', forgotPasswordSuccess.value)
          
          // Fermer la modal après 3 secondes
          setTimeout(() => {
            showForgotPassword.value = false
            forgotEmail.value = ''
            forgotPasswordSuccess.value = ''
            resetUrl.value = ''
          }, 3000)
        } else {
          throw new Error(response.data.message || 'Erreur lors de l\'envoi du lien')
        }
      } catch (err) {
        console.error('Forgot password error:', err)
        if (err.response?.data?.errors?.email) {
          forgotPasswordError.value = err.response.data.errors.email[0]
        } else if (err.response?.data?.message) {
          forgotPasswordError.value = err.response.data.message
        } else if (err.response?.status === 404) {
          forgotPasswordError.value = 'Cette adresse email n\'existe pas dans notre système.'
        } else if (err.response?.status === 403) {
          forgotPasswordError.value = 'Votre compte est désactivé. Veuillez contacter l\'administrateur.'
        } else {
          forgotPasswordError.value = err.message || 'Une erreur est survenue. Veuillez réessayer.'
        }
        notify.error('Erreur', forgotPasswordError.value)
      } finally {
        forgotPasswordLoading.value = false
      }
    }

    return {
      form,
      errors,
      error,
      loading,
      showPassword,
      requiresTwoFactor,
      twoFactorCode,
      handleLogin,
      handleVerify2FA,
      showForgotPassword,
      forgotEmail,
      forgotPasswordError,
      forgotPasswordSuccess,
      forgotPasswordLoading,
      resetUrl,
      handleForgotPassword,
      handleSwitchToRegister,
      externalSiteUrl,
      handleReturnToSite
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
