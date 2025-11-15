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
      const lastRedirectToKey = 'sso_last_redirect_to'
      const redirectingTimestamp = sessionStorage.getItem(redirectingTimestampKey)
      const redirectingUrl = sessionStorage.getItem(redirectingUrlKey)
      const redirectAttempts = parseInt(sessionStorage.getItem(redirectAttemptsKey) || '0', 10)
      const lastRedirectTo = sessionStorage.getItem(lastRedirectToKey)
      const currentUrl = window.location.href
      
      const route = useRoute()
      const hasForceToken = route.query.force_token === '1' || 
                           route.query.force_token === 1 || 
                           route.query.force_token === true || 
                           route.query.force_token === 'true' ||
                           route.query.force_token === 'yes' ||
                           route.query.force_token === 'on'
      const currentHost = window.location.hostname.replace(/^www\./, '').toLowerCase()
      
      console.log('[SSO] Checking for redirect loop:', {
        redirectAttempts,
        redirectingTimestamp,
        redirectingUrl,
        lastRedirectTo,
        currentUrl,
        referer: document.referer,
        hasForceToken,
        currentHost
      })
      
      // DÉTECTION PRINCIPALE : Si on a lastRedirectTo ET force_token ET qu'on est sur compte.herime.com
      // C'est probablement une boucle car on revient de la redirection
      if (hasForceToken && currentHost === 'compte.herime.com' && lastRedirectTo) {
        const now = Date.now()
        const elapsed = redirectingTimestamp ? now - parseInt(redirectingTimestamp, 10) : null
        
        // Si on a un timestamp valide ET qu'on a redirigé vers un domaine externe récemment (moins de 30 secondes)
        // ET qu'on est de retour sur compte.herime.com avec force_token, c'est une boucle
        // Augmenter à 30 secondes pour être plus sûr de détecter les boucles
        if (elapsed !== null && elapsed < 30000 && lastRedirectTo !== 'compte.herime.com') {
          console.error('[SSO] LOOP DETECTED: Returned to compte.herime.com with force_token after redirecting to', lastRedirectTo, 'too quickly (', elapsed, 'ms)')
          sessionStorage.setItem('sso_loop_detected', 'true')
          sessionStorage.removeItem(redirectingKey)
          sessionStorage.removeItem(redirectingTimestampKey)
          sessionStorage.removeItem(redirectingUrlKey)
          sessionStorage.removeItem(redirectAttemptsKey)
          sessionStorage.removeItem(lastRedirectToKey)
          // Nettoyer le flag après 60 secondes
          setTimeout(() => {
            sessionStorage.removeItem('sso_loop_detected')
          }, 60000)
          return true
        }
        
        // Si plus de 30 secondes se sont écoulées, c'est probablement une nouvelle tentative légitime
        // Nettoyer les flags pour permettre une nouvelle tentative
        if (elapsed !== null && elapsed >= 30000) {
          console.log('[SSO] More than 30 seconds elapsed since last redirect, allowing new attempt')
          sessionStorage.removeItem(redirectingKey)
          sessionStorage.removeItem(redirectingTimestampKey)
          sessionStorage.removeItem(redirectingUrlKey)
          sessionStorage.removeItem(redirectAttemptsKey)
          sessionStorage.removeItem(lastRedirectToKey)
          sessionStorage.removeItem('sso_loop_detected')
          // Ne pas retourner true, permettre la redirection
        }
        
        // Si on n'a pas de timestamp mais qu'on a lastRedirectTo, c'est peut-être une ancienne redirection
        // Nettoyer les flags pour permettre une nouvelle tentative
        if (elapsed === null && lastRedirectTo !== 'compte.herime.com') {
          console.log('[SSO] No timestamp found but lastRedirectTo exists, cleaning flags to allow new attempt')
          sessionStorage.removeItem(redirectingKey)
          sessionStorage.removeItem(redirectingTimestampKey)
          sessionStorage.removeItem(redirectingUrlKey)
          sessionStorage.removeItem(redirectAttemptsKey)
          sessionStorage.removeItem(lastRedirectToKey)
          sessionStorage.removeItem('sso_loop_detected')
          // Ne pas retourner true, permettre la redirection
        }
      }
      
      // Vérifier si on vient d'un autre domaine (academie.herime.com, etc.)
      // Si oui, vérifier si on a déjà redirigé vers ce domaine récemment
      const referer = document.referer
      if (referer) {
        try {
          const refererHost = new URL(referer).hostname.replace(/^www\./, '').toLowerCase()
          
          // DÉTECTION CRITIQUE : Si on vient d'un domaine externe (academie.herime.com, etc.)
          // ET qu'on est sur compte.herime.com avec force_token
          // ET qu'on a déjà redirigé vers ce domaine récemment, c'est une boucle
          if (refererHost !== 'compte.herime.com' && refererHost !== currentHost && currentHost === 'compte.herime.com') {
            if (hasForceToken) {
              const now = Date.now()
              const elapsed = redirectingTimestamp ? now - parseInt(redirectingTimestamp, 10) : 0
              
              // Si on a redirigé vers ce domaine récemment (moins de 30 secondes), c'est une boucle
              if (lastRedirectTo === refererHost && elapsed < 30000) {
                console.error('[SSO] LOOP DETECTED: Returned from', refererHost, 'too quickly (', elapsed, 'ms) - referer check')
                sessionStorage.setItem('sso_loop_detected', 'true')
                sessionStorage.removeItem(redirectingKey)
                sessionStorage.removeItem(redirectingTimestampKey)
                sessionStorage.removeItem(redirectingUrlKey)
                sessionStorage.removeItem(redirectAttemptsKey)
                sessionStorage.removeItem(lastRedirectToKey)
                // Nettoyer le flag après 60 secondes
                setTimeout(() => {
                  sessionStorage.removeItem('sso_loop_detected')
                }, 60000)
                return true
              }
              
              // Si on vient d'un domaine externe mais qu'on n'a pas de lastRedirectTo correspondant
              // ET qu'on a un timestamp récent, c'est peut-être une boucle aussi
              if (elapsed < 5000 && redirectingTimestamp) {
                console.error('[SSO] LOOP DETECTED: Returned from external domain', refererHost, 'too quickly (', elapsed, 'ms) - potential loop')
                sessionStorage.setItem('sso_loop_detected', 'true')
                sessionStorage.removeItem(redirectingKey)
                sessionStorage.removeItem(redirectingTimestampKey)
                sessionStorage.removeItem(redirectingUrlKey)
                sessionStorage.removeItem(redirectAttemptsKey)
                sessionStorage.removeItem(lastRedirectToKey)
                setTimeout(() => {
                  sessionStorage.removeItem('sso_loop_detected')
                }, 60000)
                return true
              }
            }
          }
          
          // Si on vient d'un autre domaine (pas compte.herime.com), nettoyer les flags de redirection en cours
          // MAIS garder lastRedirectTo et timestamp pour détecter les boucles
          if (refererHost !== currentHost && refererHost !== 'compte.herime.com' && currentHost === 'compte.herime.com') {
            sessionStorage.removeItem(redirectingKey)
            sessionStorage.removeItem(redirectingUrlKey)
            sessionStorage.removeItem(redirectAttemptsKey)
            // Ne pas supprimer lastRedirectTo et timestamp, on en a besoin pour détecter les boucles
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
        console.log('[SSO] Loop detected, stopping redirect and showing login form')
        // Si on est dans une boucle, nettoyer TOUT et afficher le formulaire de login
        // NE PAS supprimer les paramètres de l'URL car cela pourrait causer une redirection vers le dashboard
        isRedirecting.value = false
        authStore.isSSORedirecting = false
        if (typeof window !== 'undefined') {
          sessionStorage.removeItem('sso_redirecting')
          sessionStorage.removeItem('sso_redirecting_timestamp')
          sessionStorage.removeItem('sso_redirecting_url')
          sessionStorage.removeItem('sso_redirect_attempts')
          sessionStorage.removeItem('sso_last_redirect_to')
          
          // Marquer qu'on a détecté une boucle pour éviter de réessayer
          sessionStorage.setItem('sso_loop_detected', 'true')
          // Nettoyer ce flag après 30 secondes pour permettre une nouvelle tentative
          setTimeout(() => {
            sessionStorage.removeItem('sso_loop_detected')
          }, 30000)
        }
        // S'assurer que le formulaire de login s'affiche
        console.log('[SSO] Login form should now be visible. isRedirecting:', isRedirecting.value, 'authStore.isSSORedirecting:', authStore.isSSORedirecting)
        // Ne pas essayer de rediriger, juste afficher le formulaire
        return
      }
      
      // Vérifier si une boucle a été détectée récemment (dans les 30 dernières secondes)
      // MAIS seulement si l'utilisateur n'est pas authentifié
      if (typeof window !== 'undefined' && sessionStorage.getItem('sso_loop_detected') === 'true') {
        // Vérifier si l'utilisateur est authentifié avant de skip la redirection
        const token = localStorage.getItem('access_token')
        if (token) {
          // Si l'utilisateur a un token, vérifier l'authentification
          try {
            const isAuthenticated = await Promise.race([
              authStore.checkAuth(),
              new Promise((_, reject) => setTimeout(() => reject(new Error('Auth check timeout')), 2000))
            ])
            
            if (isAuthenticated) {
              // L'utilisateur est authentifié, nettoyer le flag et permettre la redirection
              console.log('[SSO] User is authenticated, clearing loop detection flag and allowing redirect')
              sessionStorage.removeItem('sso_loop_detected')
              // Ne pas return, continuer avec la logique de redirection ci-dessous
            } else {
              // L'utilisateur n'est pas authentifié, skip la redirection
              console.log('[SSO] Loop was recently detected and user is not authenticated, skipping redirect attempt')
              isRedirecting.value = false
              authStore.isSSORedirecting = false
              console.log('[SSO] Flags reset. isRedirecting:', isRedirecting.value, 'authStore.isSSORedirecting:', authStore.isSSORedirecting, 'shouldShowSSOOverlay:', shouldShowSSOOverlay.value)
              return
            }
          } catch (error) {
            // En cas d'erreur, considérer comme non authentifié et skip la redirection
            console.log('[SSO] Auth check failed, skipping redirect attempt due to loop detection')
            isRedirecting.value = false
            authStore.isSSORedirecting = false
            return
          }
        } else {
          // Pas de token, skip la redirection
          console.log('[SSO] Loop was recently detected and no token found, skipping redirect attempt')
          isRedirecting.value = false
          authStore.isSSORedirecting = false
          console.log('[SSO] Flags reset. isRedirecting:', isRedirecting.value, 'authStore.isSSORedirecting:', authStore.isSSORedirecting, 'shouldShowSSOOverlay:', shouldShowSSOOverlay.value)
          return
        }
      }
      
      // Vérifier immédiatement si on doit rediriger SSO
      const hasForceToken = route.query.force_token === '1' || 
                           route.query.force_token === 1 || 
                           route.query.force_token === true || 
                           route.query.force_token === 'true' ||
                           route.query.force_token === 'yes' ||
                           route.query.force_token === 'on'
      
      
      if (hasForceToken) {
        // Vérifier si on vient déjà de /sso/redirect (boucle détectée)
        const referer = document.referer
        if (referer && referer.includes('/sso/redirect')) {
          console.log('[SSO] Loop detected: coming from /sso/redirect, stopping redirect')
          isRedirecting.value = false
          authStore.isSSORedirecting = false
          if (typeof window !== 'undefined') {
            sessionStorage.removeItem('sso_redirecting')
            sessionStorage.removeItem('sso_redirecting_timestamp')
            sessionStorage.removeItem('sso_redirecting_url')
            sessionStorage.removeItem('sso_redirect_attempts')
            sessionStorage.removeItem('sso_last_redirect_to')
            sessionStorage.setItem('sso_loop_detected', 'true')
            setTimeout(() => {
              sessionStorage.removeItem('sso_loop_detected')
            }, 30000)
          }
          return
        }
        
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
          sessionStorage.removeItem('sso_last_redirect_to')
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
          console.log('[SSO] Auth check failed or timeout:', error)
          if (typeof window !== 'undefined') {
            sessionStorage.removeItem('sso_redirecting')
            sessionStorage.removeItem('sso_redirecting_timestamp')
            sessionStorage.removeItem('sso_redirecting_url')
            sessionStorage.removeItem('sso_redirect_attempts')
            sessionStorage.removeItem('sso_last_redirect_to')
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
            sessionStorage.removeItem('sso_last_redirect_to')
          }
          isRedirecting.value = false
          authStore.isSSORedirecting = false
          // Ne pas retourner, laisser le composant afficher le formulaire de login
          // L'utilisateur pourra se connecter et ensuite être redirigé
          return
        }
        
        // Vérifier une dernière fois que l'utilisateur est bien authentifié
        // et que le token est valide en appelant /me directement
        try {
          const meResponse = await Promise.race([
            axios.get('/me', { timeout: 2000 }),
            new Promise((_, reject) => setTimeout(() => reject(new Error('Me check timeout')), 2000))
          ])
          
          if (!meResponse.data?.success || !meResponse.data?.data?.user) {
            console.log('[SSO] /me check failed, user not authenticated')
            if (typeof window !== 'undefined') {
              sessionStorage.removeItem('sso_redirecting')
              sessionStorage.removeItem('sso_redirecting_timestamp')
              sessionStorage.removeItem('sso_redirecting_url')
              sessionStorage.removeItem('sso_redirect_attempts')
              sessionStorage.removeItem('sso_last_redirect_to')
            }
            isRedirecting.value = false
            authStore.isSSORedirecting = false
            return
          }
        } catch (error) {
          // Si /me échoue, ne pas rediriger
          console.log('[SSO] /me check failed:', error)
          if (typeof window !== 'undefined') {
            sessionStorage.removeItem('sso_redirecting')
            sessionStorage.removeItem('sso_redirecting_timestamp')
            sessionStorage.removeItem('sso_redirecting_url')
            sessionStorage.removeItem('sso_redirect_attempts')
            sessionStorage.removeItem('sso_last_redirect_to')
          }
          isRedirecting.value = false
          authStore.isSSORedirecting = false
          return
        }
        
        console.log('[SSO] User authenticated, proceeding with redirect')
        
        // NOUVEAU SYSTÈME: Redirection côté serveur
        // Au lieu de générer le token côté client et rediriger avec JavaScript,
        // on utilise une route Laravel qui fait tout : génération du token + redirection HTTP 302
        // Cela contourne complètement Vue Router et les problèmes JavaScript
        
        const redirect = route.query.redirect
        
        if (!redirect) {
          // Pas d'URL de redirection, nettoyer et afficher le formulaire
          if (typeof window !== 'undefined') {
            sessionStorage.removeItem('sso_redirecting')
            sessionStorage.removeItem('sso_redirecting_timestamp')
            sessionStorage.removeItem('sso_redirecting_url')
            sessionStorage.removeItem('sso_redirect_attempts')
            sessionStorage.removeItem('sso_last_redirect_to')
          }
          isRedirecting.value = false
          authStore.isSSORedirecting = false
          return
        }
        
        // SIMPLE REDIRECTION vers la route serveur qui fait tout
        // Cette route génère le token et redirige via HTTP 302
        // Cela évite tous les problèmes de JavaScript/Vue Router
        
        // Récupérer le token depuis localStorage pour l'envoyer au serveur
        const accessToken = localStorage.getItem('access_token')
        
        // Construire l'URL avec le token et l'URL de redirection
        const serverRedirectUrl = '/sso/redirect?redirect=' + encodeURIComponent(redirect) + 
          (accessToken ? '&_token=' + encodeURIComponent(accessToken) : '')
        
        console.log('[SSO] Redirecting to server-side redirect endpoint:', serverRedirectUrl)
        
        // Simple redirection - Laravel fait le reste (génération token + redirection HTTP 302)
        window.location.href = serverRedirectUrl
        
        // Nettoyer les flags
        if (typeof window !== 'undefined') {
          sessionStorage.removeItem('sso_redirecting')
          sessionStorage.removeItem('sso_redirecting_timestamp')
          sessionStorage.removeItem('sso_redirecting_url')
          sessionStorage.removeItem('sso_redirect_attempts')
          sessionStorage.removeItem('sso_last_redirect_to')
        }
        
        return
      } else {
      }
    })

    onMounted(async () => {
      // NOUVEAU SYSTÈME: onMounted ne fait plus de redirection SSO
      // La redirection est gérée entièrement dans onBeforeMount avec le nouveau système serveur
      
      // Nettoyer les flags si on n'a pas de paramètres SSO
      if (!route.query.redirect && !route.query.force_token) {
        authStore.isSSORedirecting = false
        isRedirecting.value = false
        redirectPromise.value = null
        if (typeof window !== 'undefined') {
          sessionStorage.removeItem('sso_redirecting')
          sessionStorage.removeItem('sso_redirecting_timestamp')
          sessionStorage.removeItem('sso_redirecting_url')
          sessionStorage.removeItem('sso_redirect_attempts')
          sessionStorage.removeItem('sso_last_redirect_to')
        }
      }
      
      // Déterminer la vue basée sur la route actuelle
      if (route.path === '/register') {
        currentView.value = 'register'
      } else {
        currentView.value = 'login'
      }

      // Vérifier si l'utilisateur est déjà authentifié (pour redirection normale sans force_token)
      const hasForceToken = route.query.force_token === '1' || 
                           route.query.force_token === 1 || 
                           route.query.force_token === true || 
                           route.query.force_token === 'true'
      
      // Si on a force_token, ne pas faire de redirection automatique vers dashboard
      // Laisser onBeforeMount gérer la redirection SSO
      if (hasForceToken) {
        return
      }
      
      // Vérifier l'authentification seulement pour redirection normale (sans SSO)
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
      
      // Redirection normale vers dashboard seulement si pas de force_token et pas de redirection SSO
      if (isAuthenticated && !isRedirecting.value && !hasForceToken) {
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
