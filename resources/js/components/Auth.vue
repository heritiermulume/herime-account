<template>
  <!-- Utiliser uniquement les variables réactives pour éviter les problèmes de synchronisation -->
  <div v-if="isRedirecting || shouldShowSSOOverlay" class="fixed inset-0 z-[99999] flex items-center justify-center bg-gray-900" style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 99999 !important;">
    <div class="text-center rounded-lg p-6 md:p-8" style="background-color: #003366;">
      <div class="animate-spin rounded-full h-10 w-10 md:h-12 md:w-12 border-4 border-t-transparent mx-auto mb-3 md:mb-4" style="border-color: #ffcc33;"></div>
      <p class="text-base md:text-lg font-medium text-white px-4">Redirection en cours...</p>
    </div>
  </div>
  <div v-else class="fixed inset-0 z-50 bg-gray-50 dark:bg-gray-900 overflow-y-auto">
    <Login v-if="currentView === 'login'" @switch-to-register="currentView = 'register'" />
    <Register v-else @switch-to-login="currentView = 'login'" />
  </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeMount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'
import Login from './Login.vue'
import Register from './Register.vue'

export default {
  name: 'Auth',
  components: {
    Login,
    Register
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const authStore = useAuthStore()
    const currentView = ref('login')
    const isRedirecting = ref(false)
    const redirectPromise = ref(null)
    
    // Vérifier si on doit afficher l'overlay SSO
    const shouldShowSSOOverlay = computed(() => {
      // Utiliser uniquement les variables réactives pour éviter les problèmes de synchronisation
      // Ne pas vérifier sessionStorage directement dans le computed car ça peut causer des problèmes
      if (isRedirecting.value || authStore.isSSORedirecting) {
        // Vérifier qu'on a bien les paramètres nécessaires
        if (route && route.query && (route.query.redirect || route.query.force_token)) {
          return true
        }
      }
      return false
    })

    // Protection contre les boucles de redirection
    const checkForRedirectLoop = () => {
      const redirectingKey = 'sso_redirecting'
      const redirectingTimestampKey = 'sso_redirecting_timestamp'
      const redirectingUrlKey = 'sso_redirecting_url'
      const redirectAttemptsKey = 'sso_redirect_attempts'
      const redirectingTimestamp = sessionStorage.getItem(redirectingTimestampKey)
      const redirectingUrl = sessionStorage.getItem(redirectingUrlKey)
      const redirectAttempts = parseInt(sessionStorage.getItem(redirectAttemptsKey) || '0', 10)
      const currentUrl = window.location.href
      
      // Vérifier si on vient d'un autre domaine (academie.herime.com, etc.)
      // Si oui, nettoyer sessionStorage car c'est une nouvelle session
      const referer = document.referer
      if (referer) {
        try {
          const refererHost = new URL(referer).hostname.replace(/^www\./, '').toLowerCase()
          const currentHost = window.location.hostname.replace(/^www\./, '').toLowerCase()
          
          // Si on vient d'un autre domaine (pas compte.herime.com), c'est normal, nettoyer
          if (refererHost !== currentHost && refererHost !== 'compte.herime.com' && currentHost === 'compte.herime.com') {
            sessionStorage.removeItem(redirectingKey)
            sessionStorage.removeItem(redirectingTimestampKey)
            sessionStorage.removeItem(redirectingUrlKey)
            sessionStorage.removeItem(redirectAttemptsKey)
            return false
          }
        } catch (e) {
          // Ignorer les erreurs de parsing
        }
      }
      
      // Vérifier le nombre de tentatives - si plus de 3 tentatives en moins de 10 secondes, c'est une boucle
      if (redirectAttempts >= 3) {
        const now = Date.now()
        const elapsed = redirectingTimestamp ? now - parseInt(redirectingTimestamp, 10) : 0
        
        if (elapsed < 10000) {
          // Plus de 3 tentatives en moins de 10 secondes = boucle
          sessionStorage.removeItem(redirectingKey)
          sessionStorage.removeItem(redirectingTimestampKey)
          sessionStorage.removeItem(redirectingUrlKey)
          sessionStorage.removeItem(redirectAttemptsKey)
          isRedirecting.value = false
          authStore.isSSORedirecting = false
          console.error('SSO redirect loop detected: too many attempts')
          return true
        } else {
          // Plus de 10 secondes, réinitialiser le compteur
          sessionStorage.setItem(redirectAttemptsKey, '0')
        }
      }
      
      // Vérifier si on est sur la même URL que la dernière tentative de redirection
      if (redirectingTimestamp && redirectingUrl) {
        const now = Date.now()
        const elapsed = now - parseInt(redirectingTimestamp, 10)
        
        // Si moins de 3 secondes se sont écoulées ET qu'on est sur la même URL, on est dans une boucle
        if (elapsed < 3000 && redirectingUrl === currentUrl) {
          // Incrémenter le compteur de tentatives
          sessionStorage.setItem(redirectAttemptsKey, (redirectAttempts + 1).toString())
          
          // Si trop de tentatives, arrêter
          if (redirectAttempts + 1 >= 3) {
            sessionStorage.removeItem(redirectingKey)
            sessionStorage.removeItem(redirectingTimestampKey)
            sessionStorage.removeItem(redirectingUrlKey)
            sessionStorage.removeItem(redirectAttemptsKey)
            isRedirecting.value = false
            authStore.isSSORedirecting = false
            console.error('SSO redirect loop detected: same URL in less than 3 seconds')
            return true
          }
        }
        // Plus de 3 secondes ou URL différente, réinitialiser le compteur
        if (elapsed >= 3000 || redirectingUrl !== currentUrl) {
          sessionStorage.setItem(redirectAttemptsKey, '0')
        }
      }
      
      return false
    }

    // Gérer la redirection SSO AVANT que le composant ne soit monté
    onBeforeMount(async () => {
      console.log('[SSO] onBeforeMount called, URL:', window.location.href)
      
      // Vérifier si on est dans une boucle (AVANT toute autre vérification)
      if (checkForRedirectLoop()) {
        console.log('[SSO] Loop detected, stopping redirect')
        // Si on est dans une boucle, nettoyer TOUT et afficher le formulaire de login
        isRedirecting.value = false
        authStore.isSSORedirecting = false
        if (typeof window !== 'undefined') {
          sessionStorage.removeItem('sso_redirecting')
          sessionStorage.removeItem('sso_redirecting_timestamp')
          sessionStorage.removeItem('sso_redirecting_url')
          sessionStorage.removeItem('sso_redirect_attempts')
        }
        // Ne pas essayer de rediriger, juste afficher le formulaire
        return
      }
      
      // Vérifier immédiatement si on doit rediriger SSO
      const hasForceToken = route.query.force_token === '1' || 
                           route.query.force_token === 1 || 
                           route.query.force_token === true || 
                           route.query.force_token === 'true' ||
                           route.query.force_token === 'yes' ||
                           route.query.force_token === 'on'
      
      
      if (hasForceToken) {
        // Vérifier que le redirect ne pointe pas vers compte.herime.com (éviter boucle)
        const redirectUrl = route.query.redirect
        if (redirectUrl && typeof redirectUrl === 'string') {
          try {
            const redirectHost = new URL(redirectUrl).hostname
            const currentHost = window.location.hostname
            
            if (redirectHost === currentHost || redirectHost === 'compte.herime.com') {
              // Ne pas rediriger si ça pointe vers le même domaine
              return
            }
          } catch (e) {
          }
        }
        
        
        // Marquer IMMÉDIATEMENT les flags AVANT toute vérification
        // Cela empêche App.vue de rendre l'interface même pendant la vérification
        if (typeof window !== 'undefined') {
          const currentAttempts = parseInt(sessionStorage.getItem('sso_redirect_attempts') || '0', 10)
          sessionStorage.setItem('sso_redirecting', 'true')
          sessionStorage.setItem('sso_redirecting_timestamp', Date.now().toString())
          sessionStorage.setItem('sso_redirecting_url', window.location.href)
          sessionStorage.setItem('sso_redirect_attempts', (currentAttempts + 1).toString())
        }
        isRedirecting.value = true
        authStore.isSSORedirecting = true
        
        // Vérifier le token dans localStorage directement
        const token = localStorage.getItem('access_token')
        
        if (!token) {
          console.log('[SSO] No token found, showing login form')
          // Nettoyer les flags si pas de token - l'utilisateur n'est pas connecté, afficher le formulaire
          if (typeof window !== 'undefined') {
            sessionStorage.removeItem('sso_redirecting')
            sessionStorage.removeItem('sso_redirecting_timestamp')
            sessionStorage.removeItem('sso_redirecting_url')
            sessionStorage.removeItem('sso_redirect_attempts')
          }
          isRedirecting.value = false
          authStore.isSSORedirecting = false
          // Ne pas retourner, laisser le composant afficher le formulaire de login
          return
        }
        
        console.log('[SSO] Token found, checking authentication...')
        
        // OPTIMISATION: Vérifier l'authentification avec timeout court (3 secondes) pour accélérer
        let isAuthenticated = false
        
        try {
          isAuthenticated = await Promise.race([
            authStore.checkAuth(),
            new Promise((_, reject) => setTimeout(() => reject(new Error('Auth check timeout')), 3000))
          ])
        } catch (error) {
          // En cas d'erreur ou timeout, nettoyer les flags et considérer comme non authentifié
          if (typeof window !== 'undefined') {
            sessionStorage.removeItem('sso_redirecting')
            sessionStorage.removeItem('sso_redirecting_timestamp')
            sessionStorage.removeItem('sso_redirecting_url')
            sessionStorage.removeItem('sso_redirect_attempts')
          }
          isRedirecting.value = false
          authStore.isSSORedirecting = false
          return
        }
        
        if (!isAuthenticated) {
          console.log('[SSO] User not authenticated, showing login form')
          // Nettoyer les flags si l'utilisateur n'est pas authentifié - afficher le formulaire de login
          if (typeof window !== 'undefined') {
            sessionStorage.removeItem('sso_redirecting')
            sessionStorage.removeItem('sso_redirecting_timestamp')
            sessionStorage.removeItem('sso_redirecting_url')
            sessionStorage.removeItem('sso_redirect_attempts')
          }
          isRedirecting.value = false
          authStore.isSSORedirecting = false
          // Ne pas retourner, laisser le composant afficher le formulaire de login
          // L'utilisateur pourra se connecter et ensuite être redirigé
          return
        }
        
        console.log('[SSO] User authenticated, proceeding with redirect')
        
        // Utilisateur authentifié, continuer avec la génération du token SSO
        // Les flags sont déjà marqués au début, pas besoin de les remettre
        
        // Générer le token SSO et rediriger immédiatement
        // Exécuter la fonction async immédiatement
        const redirect = route.query.redirect
        
        if (!redirect) {
          if (typeof window !== 'undefined') {
            sessionStorage.removeItem('sso_redirecting')
            sessionStorage.removeItem('sso_redirecting_timestamp')
            sessionStorage.removeItem('sso_redirecting_url')
            sessionStorage.removeItem('sso_redirect_attempts')
          }
          isRedirecting.value = false
          authStore.isSSORedirecting = false
          return
        }
        
        // Exécuter la génération du token et la redirection immédiatement
        // Utiliser une fonction async et l'exécuter immédiatement
        const performRedirect = async () => {
          try {
            console.log('[SSO] Starting token generation for redirect:', redirect)
            // OPTIMISATION: Timeout court pour la génération du token SSO (3 secondes)
            const response = await Promise.race([
              axios.post('/sso/generate-token', {
                redirect: redirect
              }, { timeout: 3000 }),
              new Promise((_, reject) => setTimeout(() => reject(new Error('SSO token generation timeout')), 3000))
            ])
            
            console.log('[SSO] Token generation response:', response.data)
            
            
            if (response.data && response.data.success && response.data.data && response.data.data.callback_url) {
              const callbackUrl = response.data.data.callback_url
              
              console.log('[SSO] Callback URL received:', callbackUrl)
              
              // Vérifier une dernière fois que callbackUrl ne pointe pas vers le même domaine
              try {
                const callbackHost = new URL(callbackUrl).hostname.replace(/^www\./, '').toLowerCase()
                const currentHost = window.location.hostname.replace(/^www\./, '').toLowerCase()
                
                console.log('[SSO] Checking callback host:', callbackHost, 'vs current host:', currentHost)
                
                // Si le callback pointe vers compte.herime.com, c'est une boucle - arrêter
                if (callbackHost === currentHost || callbackHost === 'compte.herime.com') {
                  console.error('[SSO] LOOP DETECTED: Callback URL points to same domain, stopping redirect')
                  if (typeof window !== 'undefined') {
                    sessionStorage.removeItem('sso_redirecting')
                    sessionStorage.removeItem('sso_redirecting_timestamp')
                    sessionStorage.removeItem('sso_redirecting_url')
                    sessionStorage.removeItem('sso_redirect_attempts')
                  }
                  isRedirecting.value = false
                  authStore.isSSORedirecting = false
                  // Afficher le formulaire de login au lieu de créer une boucle
                  return
                }
              } catch (e) {
                console.error('[SSO] Error parsing callback URL:', e)
              }
              
              // IMPORTANT: Nettoyer les flags AVANT la redirection pour éviter les boucles
              // Ne pas garder les flags car on va quitter cette page
              if (typeof window !== 'undefined') {
                sessionStorage.removeItem('sso_redirecting')
                sessionStorage.removeItem('sso_redirecting_timestamp')
                sessionStorage.removeItem('sso_redirecting_url')
                sessionStorage.removeItem('sso_redirect_attempts')
              }
              
              console.log('[SSO] Redirecting to:', callbackUrl)
              // Redirection immédiate et définitive - utiliser replace pour éviter de revenir en arrière
              window.location.replace(callbackUrl)
              
              // Cette ligne ne sera jamais exécutée
              return
            } else {
              if (typeof window !== 'undefined') {
                sessionStorage.removeItem('sso_redirecting')
                sessionStorage.removeItem('sso_redirecting_timestamp')
              }
              isRedirecting.value = false
              authStore.isSSORedirecting = false
              return
            }
          } catch (error) {
            if (typeof window !== 'undefined') {
              sessionStorage.removeItem('sso_redirecting')
              sessionStorage.removeItem('sso_redirecting_timestamp')
            }
            isRedirecting.value = false
            authStore.isSSORedirecting = false
            return
          }
        }
        
        // Exécuter immédiatement et stocker la promesse pour éviter les doubles exécutions
        if (!redirectPromise.value) {
          redirectPromise.value = performRedirect()
        }
        
        // Ne pas continuer, la redirection est en cours
        return
      } else {
      }
    })

    onMounted(async () => {
      
      // Si une redirection est déjà en cours (promesse en cours), ne rien faire de plus
      if (redirectPromise.value) {
        try {
          await redirectPromise.value
        } catch (e) {
        }
        return
      }
      
      // TOUJOURS réinitialiser isRedirecting au début de onMounted (sauf si redirection en cours)
      // Mais seulement si on n'a pas de paramètres SSO
      if (!route.query.redirect && !route.query.force_token) {
        isRedirecting.value = false
      }
      
      // Réinitialiser le flag SSO si on arrive sur la page sans paramètre redirect/force_token
      if (!route.query.redirect && !route.query.force_token) {
        authStore.isSSORedirecting = false
        isRedirecting.value = false
        redirectPromise.value = null
        if (typeof window !== 'undefined') {
          sessionStorage.removeItem('sso_redirecting')
          sessionStorage.removeItem('sso_redirecting_timestamp')
        }
      }
      
      // OPTIMISATION: Si onBeforeMount a déjà géré la redirection (redirectPromise existe), ne rien faire
      // Cela évite les appels redondants et les boucles
      if (redirectPromise.value) {
        return
      }
      
      // Si une redirection SSO est en cours ET qu'on a les paramètres, vérifier si c'est vraiment nécessaire
      // Mais seulement si onBeforeMount n'a pas déjà géré la redirection
      if (typeof window !== 'undefined' && sessionStorage.getItem('sso_redirecting') === 'true' && !redirectPromise.value) {
        // Si on n'a pas de paramètres redirect/force_token, nettoyer le flag
        if (!route.query.redirect && !route.query.force_token) {
          sessionStorage.removeItem('sso_redirecting')
          sessionStorage.removeItem('sso_redirecting_timestamp')
          isRedirecting.value = false
          authStore.isSSORedirecting = false
        } else {
          // On a les paramètres, vérifier si l'utilisateur est authentifié
          const token = localStorage.getItem('access_token')
          if (!token) {
            sessionStorage.removeItem('sso_redirecting')
            sessionStorage.removeItem('sso_redirecting_timestamp')
            sessionStorage.removeItem('sso_redirecting_url')
            sessionStorage.removeItem('sso_redirect_attempts')
            isRedirecting.value = false
            authStore.isSSORedirecting = false
          } else {
            // OPTIMISATION: Vérifier l'authentification avec timeout
            let isAuthenticated = false
            try {
              isAuthenticated = await Promise.race([
                authStore.checkAuth(),
                new Promise((_, reject) => setTimeout(() => reject(new Error('Auth check timeout')), 3000))
              ])
            } catch (error) {
              // En cas de timeout, considérer comme non authentifié
              isAuthenticated = false
            }
            
            if (!isAuthenticated) {
              sessionStorage.removeItem('sso_redirecting')
              sessionStorage.removeItem('sso_redirecting_timestamp')
              isRedirecting.value = false
              authStore.isSSORedirecting = false
            } else {
              // Si la redirection n'a pas déjà été déclenchée dans onBeforeMount, la déclencher ici
              if (!redirectPromise.value && route.query.redirect) {
                isRedirecting.value = true
                const performRedirect = async () => {
                  try {
                    // OPTIMISATION: Timeout court pour la génération du token SSO (3 secondes)
                    const response = await Promise.race([
                      axios.post('/sso/generate-token', {
                        redirect: route.query.redirect
                      }, { timeout: 3000 }),
                      new Promise((_, reject) => setTimeout(() => reject(new Error('SSO token generation timeout')), 3000))
                    ])
                    
                    if (response.data?.success && response.data?.data?.callback_url) {
                      const callbackUrl = response.data.data.callback_url
                      if (typeof window !== 'undefined') {
                        sessionStorage.setItem('sso_redirecting', 'true')
                        sessionStorage.setItem('sso_redirecting_timestamp', Date.now().toString())
                      }
                      window.location.replace(callbackUrl)
                    }
                  } catch (error) {
                    sessionStorage.removeItem('sso_redirecting')
                    sessionStorage.removeItem('sso_redirecting_timestamp')
                    isRedirecting.value = false
                    authStore.isSSORedirecting = false
                  }
                }
                redirectPromise.value = performRedirect()
              } else {
                isRedirecting.value = true
              }
              return
            }
          }
        }
      }
      
      // Si on est en train de rediriger, ne pas continuer
      if (isRedirecting.value || redirectPromise.value) {
        return
      }
      
      // Determine view based on current route
      if (route.path === '/register') {
        currentView.value = 'register'
      } else {
        currentView.value = 'login'
      }

      // Check if user is already authenticated (pour redirection normale sans force_token)
      const hasForceToken = route.query.force_token === '1' || 
                           route.query.force_token === 1 || 
                           route.query.force_token === true || 
                           route.query.force_token === 'true'
      
      if (hasForceToken) {
        return
      }
      
      // OPTIMISATION: Vérifier l'authentification avec timeout
      let isAuthenticated = false
      try {
        isAuthenticated = await Promise.race([
          authStore.checkAuth(),
          new Promise((_, reject) => setTimeout(() => reject(new Error('Auth check timeout')), 5000))
        ])
      } catch (error) {
        // En cas de timeout, utiliser l'état actuel du store
        isAuthenticated = authStore.authenticated
      }
      
      if (isAuthenticated && !isRedirecting.value) {
        // Redirection normale vers dashboard seulement si pas de force_token
          router.push('/dashboard')
      }
    })

    return {
      currentView,
      isRedirecting,
      shouldShowSSOOverlay
    }
  }
}
</script>
