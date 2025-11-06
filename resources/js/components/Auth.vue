<template>
  <!-- Vérifier sessionStorage directement dans le template pour réactivité immédiate -->
  <div v-if="isRedirecting || shouldShowSSOOverlay || (typeof window !== 'undefined' && window.sessionStorage && window.sessionStorage.getItem('sso_redirecting') === 'true' && route && route.query && (route.query.redirect || route.query.force_token))" class="fixed inset-0 z-[99999] bg-gray-50 dark:bg-gray-900 flex items-center justify-center" style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 99999 !important; background-color: rgb(249 250 251) !important;">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4" style="border-color: #003366;"></div>
      <p class="text-gray-600 dark:text-gray-400">Redirection en cours...</p>
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
    
    // Vérifier directement sessionStorage pour une réactivité immédiate
    const shouldShowSSOOverlay = computed(() => {
      if (typeof window === 'undefined') return false
      const ssoRedirecting = sessionStorage.getItem('sso_redirecting') === 'true'
      if (!ssoRedirecting) return false
      // Vérifier qu'on a bien les paramètres nécessaires
      if (route && route.query && (route.query.redirect || route.query.force_token)) {
        return true
      }
      return false
    })

    // Protection contre les boucles de redirection
    const checkForRedirectLoop = () => {
      const redirectingKey = 'sso_redirecting'
      const redirectingTimestamp = sessionStorage.getItem(redirectingKey)
      
      // Vérifier si on vient d'un autre domaine (academie.herime.com)
      // Si oui, nettoyer sessionStorage car c'est une nouvelle session
      const referer = document.referer
      if (referer) {
        try {
          const refererHost = new URL(referer).hostname
          const currentHost = window.location.hostname
          
          // Si on vient d'un autre domaine, c'est normal, nettoyer
          if (refererHost !== currentHost && refererHost !== 'compte.herime.com') {
            console.log('[Auth] Vient d\'un autre domaine, nettoyage sessionStorage', {
              refererHost,
              currentHost
            })
            sessionStorage.removeItem(redirectingKey)
            return false
          }
        } catch (e) {
          // Ignorer les erreurs de parsing
        }
      }
      
      if (redirectingTimestamp) {
        const now = Date.now()
        const elapsed = now - parseInt(redirectingTimestamp, 10)
        
        // Si moins de 5 secondes se sont écoulées, on est probablement dans une boucle
        if (elapsed < 5000) {
          console.error('[Auth] ⚠️ BOUCLE DE REDIRECTION DÉTECTÉE!', {
            elapsed,
            timestamp: redirectingTimestamp,
            referer: document.referer,
            currentUrl: window.location.href
          })
          // Nettoyer et arrêter
          sessionStorage.removeItem(redirectingKey)
          
          // Rediriger vers dashboard pour arrêter la boucle
          console.log('[Auth] Redirection vers dashboard pour arrêter la boucle')
          router.push('/dashboard')
          return true
        }
        // Plus de 5 secondes, considérer que c'est une nouvelle tentative
        sessionStorage.removeItem(redirectingKey)
      }
      
      return false
    }

    // Gérer la redirection SSO AVANT que le composant ne soit monté
    onBeforeMount(async () => {
      console.log('[Auth] onBeforeMount - Début', {
        path: route.path,
        query: route.query,
        force_token: route.query.force_token,
        referer: document.referer
      })
      
      // Vérifier si on est dans une boucle (AVANT toute autre vérification)
      if (checkForRedirectLoop()) {
        console.error('[Auth] Boucle détectée, arrêt de la redirection SSO et redirection vers dashboard')
        return
      }
      
      // Vérifier immédiatement si on doit rediriger SSO
      const hasForceToken = route.query.force_token === '1' || 
                           route.query.force_token === 1 || 
                           route.query.force_token === true || 
                           route.query.force_token === 'true' ||
                           route.query.force_token === 'yes' ||
                           route.query.force_token === 'on'
      
      console.log('[Auth] hasForceToken check:', {
        hasForceToken,
        force_token_value: route.query.force_token,
        redirect: route.query.redirect
      })
      
      if (hasForceToken) {
        // Vérifier que le redirect ne pointe pas vers compte.herime.com (éviter boucle)
        const redirectUrl = route.query.redirect
        if (redirectUrl && typeof redirectUrl === 'string') {
          try {
            const redirectHost = new URL(redirectUrl).hostname
            const currentHost = window.location.hostname
            
            if (redirectHost === currentHost || redirectHost === 'compte.herime.com') {
              console.error('[Auth] ⚠️ Redirect pointe vers le même domaine, risque de boucle!', {
                redirectHost,
                currentHost,
                redirectUrl
              })
              // Ne pas rediriger si ça pointe vers le même domaine
              return
            }
          } catch (e) {
            console.warn('[Auth] Impossible de parser redirect URL:', redirectUrl)
          }
        }
        
        console.log('[Auth] force_token détecté dans onBeforeMount', {
          force_token: route.query.force_token,
          redirect: route.query.redirect,
          path: route.path
        })
        
        // Vérifier le token dans localStorage directement
        const token = localStorage.getItem('access_token')
        console.log('[Auth] Token dans localStorage:', token ? token.substring(0, 20) + '...' : 'AUCUN')
        
        if (!token) {
          console.log('[Auth] Pas de token, affichage du formulaire de login')
          return
        }
        
        // Vérifier l'authentification
        console.log('[Auth] Vérification de l\'authentification...')
        let isAuthenticated = false
        
        try {
          isAuthenticated = await authStore.checkAuth()
          console.log('[Auth] Résultat checkAuth:', isAuthenticated)
        } catch (error) {
          console.error('[Auth] Erreur lors de checkAuth:', error)
        }
        
        if (isAuthenticated) {
          // Vérifier à nouveau si une redirection est déjà en cours (double vérification)
          if (typeof window !== 'undefined' && sessionStorage.getItem('sso_redirecting') === 'true') {
            console.log('[Auth] Redirection SSO déjà en cours (double vérification), masquer interface')
            isRedirecting.value = true
            return
          }
          
          console.log('[Auth] Utilisateur authentifié, génération token SSO...')
          
          // Marquer IMMÉDIATEMENT qu'on est en train de rediriger AVANT toute autre opération
          // Cela empêchera App.vue de rendre l'interface
          // IMPORTANT: Faire cela SYNCHRONEMENT avant toute opération asynchrone
          if (typeof window !== 'undefined') {
            sessionStorage.setItem('sso_redirecting', 'true')
          }
          isRedirecting.value = true
          authStore.isSSORedirecting = true
          
          // Forcer Vue à mettre à jour le DOM immédiatement
          // Utiliser nextTick pour s'assurer que Vue a mis à jour le DOM
          await import('vue').then(vue => {
            vue.nextTick(() => {
              // Forcer un re-render
            })
          })
          
          // Générer le token SSO et rediriger immédiatement
          // Exécuter la fonction async immédiatement
          const redirect = route.query.redirect
          console.log('[Auth] Redirect URL extraite:', redirect)
          
          if (!redirect) {
            console.error('[Auth] No redirect URL provided')
            if (typeof window !== 'undefined') {
              sessionStorage.removeItem('sso_redirecting')
            }
            isRedirecting.value = false
            return
          }
          
          // Exécuter la génération du token et la redirection immédiatement
          ;(async () => {
            try {
              console.log('[Auth] Appel API /sso/generate-token...', {
                redirect: redirect,
                token_present: !!token
              })
              
              const response = await axios.post('/sso/generate-token', {
                redirect: redirect
              })
              
              console.log('[Auth] SSO token response reçue:', {
                status: response.status,
                success: response.data?.success,
                has_data: !!response.data?.data,
                has_callback_url: !!response.data?.data?.callback_url,
                full_response: response.data
              })
              
              if (response.data && response.data.success && response.data.data && response.data.data.callback_url) {
                const callbackUrl = response.data.data.callback_url
                console.log('[Auth] Redirection vers:', callbackUrl)
                
                // Vérifier une dernière fois que callbackUrl ne pointe pas vers le même domaine
                try {
                  const callbackHost = new URL(callbackUrl).hostname
                  const currentHost = window.location.hostname
                  
                  if (callbackHost === currentHost || callbackHost === 'compte.herime.com') {
                    console.error('[Auth] ⚠️ Callback URL pointe vers le même domaine, ARRÊT!', {
                      callbackHost,
                      currentHost,
                      callbackUrl
                    })
                    if (typeof window !== 'undefined') {
                      sessionStorage.removeItem('sso_redirecting')
                    }
                    isRedirecting.value = false
                    return
                  }
                } catch (e) {
                  console.warn('[Auth] Impossible de parser callback URL:', callbackUrl, e)
                }
                
                // IMPORTANT: S'assurer que les flags sont bien à true avant la redirection
                if (typeof window !== 'undefined') {
                  sessionStorage.setItem('sso_redirecting', 'true')
                  // Arrêter tout chargement en cours pour éviter que Vue ne rende
                  if (window.stop) {
                    window.stop()
                  }
                }
                isRedirecting.value = true
                authStore.isSSORedirecting = true
                
                // Redirection immédiate et définitive
                console.log('[Auth] Exécution de window.location.href vers:', callbackUrl)
                // Utiliser window.location.href pour forcer une navigation immédiate
                window.location.href = callbackUrl
                
                // Cette ligne ne sera jamais exécutée car window.location.href redirige
                return
              } else {
                console.error('[Auth] Structure de réponse invalide ou callback_url manquant:', {
                  response_data: response.data,
                  has_success: !!response.data?.success,
                  has_data: !!response.data?.data,
                  has_callback_url: !!response.data?.data?.callback_url
                })
                if (typeof window !== 'undefined') {
                  sessionStorage.removeItem('sso_redirecting')
                }
                isRedirecting.value = false
                return
              }
            } catch (error) {
              console.error('[Auth] Erreur lors de la génération du token SSO:', {
                message: error.message,
                response: error.response?.data,
                status: error.response?.status,
                config: {
                  url: error.config?.url,
                  method: error.config?.method,
                  headers: error.config?.headers
                },
                stack: error.stack
              })
              if (typeof window !== 'undefined') {
                sessionStorage.removeItem('sso_redirecting')
              }
              isRedirecting.value = false
              return
            }
          })()
          
          // Ne pas continuer, la redirection est en cours
          return
        } else {
          console.log('[Auth] User not authenticated, will show login form')
        }
      } else {
        console.log('[Auth] Pas de force_token, comportement normal')
      }
    })

    onMounted(async () => {
      console.log('[Auth] onMounted - Début', {
        isRedirecting: isRedirecting.value,
        path: route.path,
        hasRedirect: !!route.query.redirect,
        hasForceToken: !!route.query.force_token,
        ssoRedirecting: typeof window !== 'undefined' ? sessionStorage.getItem('sso_redirecting') : null
      })
      
      // TOUJOURS réinitialiser isRedirecting au début de onMounted
      isRedirecting.value = false
      
      // Réinitialiser le flag SSO si on arrive sur la page sans paramètre redirect/force_token
      if (!route.query.redirect && !route.query.force_token) {
        console.log('[Auth] Pas de paramètres redirect/force_token, réinitialisation des flags')
        authStore.isSSORedirecting = false
        isRedirecting.value = false
        redirectPromise.value = null
        if (typeof window !== 'undefined') {
          sessionStorage.removeItem('sso_redirecting')
        }
      }
      
      // Si une redirection SSO est en cours ET qu'on a les paramètres, vérifier si c'est vraiment nécessaire
      if (typeof window !== 'undefined' && sessionStorage.getItem('sso_redirecting') === 'true') {
        // Si on n'a pas de paramètres redirect/force_token, nettoyer le flag
        if (!route.query.redirect && !route.query.force_token) {
          console.log('[Auth] Flag SSO présent mais pas de paramètres, nettoyage')
          sessionStorage.removeItem('sso_redirecting')
          isRedirecting.value = false
          authStore.isSSORedirecting = false
        } else {
          // On a les paramètres, vérifier si l'utilisateur est authentifié
          const token = localStorage.getItem('access_token')
          if (!token) {
            console.log('[Auth] Pas de token, nettoyage du flag SSO')
            sessionStorage.removeItem('sso_redirecting')
            isRedirecting.value = false
            authStore.isSSORedirecting = false
          } else {
            const isAuthenticated = await authStore.checkAuth()
            if (!isAuthenticated) {
              console.log('[Auth] Utilisateur non authentifié, nettoyage du flag SSO')
              sessionStorage.removeItem('sso_redirecting')
              isRedirecting.value = false
              authStore.isSSORedirecting = false
            } else {
              console.log('[Auth] Utilisateur authentifié avec paramètres SSO, redirection en cours')
              // La redirection sera gérée par onBeforeMount
              isRedirecting.value = true
              return
            }
          }
        }
      }
      
      // Si on est en train de rediriger, ne pas continuer
      if (isRedirecting.value || redirectPromise.value) {
        console.log('[Auth] Redirection en cours, arrêt du montage')
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
        console.log('[Auth] force_token présent dans onMounted, ne pas rediriger vers dashboard')
        return
      }
      
      const isAuthenticated = await authStore.checkAuth()
      if (isAuthenticated && !isRedirecting.value) {
        console.log('[Auth] User authenticated without force_token, redirecting to dashboard')
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
