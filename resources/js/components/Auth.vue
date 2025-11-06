<template>
  <div class="fixed inset-0 z-50 bg-gray-50 dark:bg-gray-900 overflow-y-auto">
    <Login v-if="currentView === 'login'" @switch-to-register="currentView = 'register'" />
    <Register v-else @switch-to-login="currentView = 'login'" />
  </div>
</template>

<script>
import { ref, onMounted, onBeforeMount } from 'vue'
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

    // Protection contre les boucles de redirection
    const checkForRedirectLoop = () => {
      const redirectingKey = 'sso_redirecting'
      const checkingKey = 'sso_checking'
      const redirectingTimestamp = sessionStorage.getItem(redirectingKey)
      const checkingTimestamp = sessionStorage.getItem(checkingKey)
      
      // Vérifier si on a force_token dans l'URL - si oui, c'est une demande SSO légitime
      const hasForceToken = route.query.force_token === '1' || 
                           route.query.force_token === 1 || 
                           route.query.force_token === true || 
                           route.query.force_token === 'true' ||
                           route.query.force_token === 'yes' ||
                           route.query.force_token === 'on'
      
      // Si on a force_token et un redirect, c'est une demande SSO légitime, pas une boucle
      if (hasForceToken && route.query.redirect) {
        // Vérifier que le redirect pointe vers un domaine externe
        try {
          const redirectUrl = route.query.redirect
          let decodedUrl = redirectUrl
          try {
            decodedUrl = decodeURIComponent(redirectUrl)
          } catch (e) {
            // Si le décodage échoue, utiliser l'URL telle quelle
          }
          
          const redirectHost = new URL(decodedUrl).hostname
          const currentHost = window.location.hostname
          
          // Si le redirect pointe vers un domaine externe, ce n'est pas une boucle
          if (redirectHost !== currentHost && redirectHost !== 'compte.herime.com') {
            // Si on est en train de vérifier (< 5 secondes), c'est normal, ne pas bloquer
            if (checkingTimestamp) {
              const now = Date.now()
              const elapsed = now - parseInt(checkingTimestamp, 10)
              if (elapsed < 5000) {
                // C'est normal, on est en train de vérifier l'authentification
                return false
              }
            }
            
            // Si on est en train de rediriger (< 10 secondes), c'est normal, ne pas bloquer
            if (redirectingTimestamp) {
              const now = Date.now()
              const elapsed = now - parseInt(redirectingTimestamp, 10)
              if (elapsed < 10000) {
                // C'est normal, on est en train de rediriger
                return false
              }
            }
            
            // Plus de 10 secondes, nettoyer et considérer comme une nouvelle tentative
            sessionStorage.removeItem(redirectingKey)
            sessionStorage.removeItem(checkingKey)
            return false
          }
        } catch (e) {
          // Si le parsing échoue, continuer quand même (peut être une URL encodée)
        }
      }
      
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
            sessionStorage.removeItem(checkingKey)
            return false
          }
        } catch (e) {
          // Ignorer les erreurs de parsing
        }
      }
      
      // Si on a un timestamp de redirection mais pas de force_token, c'est suspect
      if (redirectingTimestamp && !hasForceToken) {
        const now = Date.now()
        const elapsed = now - parseInt(redirectingTimestamp, 10)
        
        // Si moins de 2 secondes se sont écoulées, on est probablement dans une boucle
        if (elapsed < 2000) {
          console.error('[Auth] ⚠️ BOUCLE DE REDIRECTION DÉTECTÉE!', {
            elapsed,
            timestamp: redirectingTimestamp,
            referer: document.referer,
            currentUrl: window.location.href,
            has_force_token: hasForceToken
          })
          // Nettoyer et arrêter
          sessionStorage.removeItem(redirectingKey)
          sessionStorage.removeItem(checkingKey)
          
          // Rediriger vers dashboard pour arrêter la boucle
          console.log('[Auth] Redirection vers dashboard pour arrêter la boucle')
          router.push('/dashboard')
          return true
        }
        // Plus de 2 secondes, considérer que c'est une nouvelle tentative
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
      
      // Vérifier si on est déjà en train de rediriger (pour éviter les appels multiples)
      const redirectingKey = 'sso_redirecting'
      const checkingKey = 'sso_checking'
      const redirectingTimestamp = sessionStorage.getItem(redirectingKey)
      if (redirectingTimestamp && redirectPromise.value) {
        // On est déjà en train de rediriger, ne pas recommencer
        console.log('[Auth] Redirection déjà en cours, attente...')
        try {
          await redirectPromise.value
        } catch (e) {
          // Ignorer les erreurs
        }
        return
      }
      
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
      
      // Ne pas logger redirect car il peut contenir des informations sensibles
      
      if (hasForceToken) {
        // Vérifier que le redirect ne pointe pas vers compte.herime.com (éviter boucle)
        const redirectUrl = route.query.redirect
        if (redirectUrl && typeof redirectUrl === 'string') {
          try {
            // Décoder l'URL si nécessaire
            let decodedUrl = redirectUrl
            try {
              decodedUrl = decodeURIComponent(redirectUrl)
            } catch (e) {
              // Si le décodage échoue, utiliser l'URL telle quelle
            }
            
            const redirectHost = new URL(decodedUrl).hostname
            const currentHost = window.location.hostname
            
            if (redirectHost === currentHost || redirectHost === 'compte.herime.com') {
              console.error('[Auth] ⚠️ Redirect pointe vers le même domaine, risque de boucle!', {
                redirectHost,
                currentHost
              })
              // Ne pas rediriger si ça pointe vers le même domaine
              return
            }
          } catch (e) {
            // Si le parsing échoue, continuer quand même (peut être une URL encodée)
            console.warn('[Auth] Impossible de parser redirect URL, continuant quand même')
          }
        }
        
        // Vérifier le token dans localStorage directement
        const token = localStorage.getItem('access_token')
        // Ne pas logger le token (même partiel) pour des raisons de sécurité
        
        if (!token) {
          console.log('[Auth] Pas de token, affichage du formulaire de login')
          return
        }
        
        // Marquer qu'on va vérifier l'authentification (pour éviter les appels multiples)
        sessionStorage.setItem(checkingKey, Date.now().toString())
        
        // Vérifier l'authentification
        console.log('[Auth] Vérification de l\'authentification...')
        let isAuthenticated = false
        
        try {
          isAuthenticated = await authStore.checkAuth()
          console.log('[Auth] Résultat checkAuth:', isAuthenticated)
        } catch (error) {
          console.error('[Auth] Erreur lors de checkAuth:', error)
          sessionStorage.removeItem(checkingKey)
          return
        } finally {
          sessionStorage.removeItem(checkingKey)
        }
        
        if (isAuthenticated) {
          console.log('[Auth] Utilisateur authentifié, génération token SSO...')
          isRedirecting.value = true
          
          // Marquer qu'on est en train de rediriger (AVANT de créer la promesse)
          sessionStorage.setItem(redirectingKey, Date.now().toString())
          
          // Créer une promesse de redirection pour éviter les redirections multiples
          if (!redirectPromise.value) {
            redirectPromise.value = (async () => {
              try {
                const redirect = route.query.redirect
                // Ne pas logger redirect car il peut contenir des informations sensibles
                
                if (!redirect) {
                  console.error('[Auth] No redirect URL provided')
                  sessionStorage.removeItem(redirectingKey)
                  redirectPromise.value = null
                  return false
                }
                
                // Ne pas logger les URLs qui peuvent contenir des tokens
                
                const response = await axios.post('/sso/generate-token', {
                  redirect: redirect
                })
                
                // Ne pas logger les URLs de callback car elles contiennent des tokens
                
                if (response.data && response.data.success && response.data.data && response.data.data.callback_url) {
                  const callbackUrl = response.data.data.callback_url
                  
                  // Vérifier une dernière fois que callbackUrl ne pointe pas vers le même domaine
                  try {
                    const callbackHost = new URL(callbackUrl).hostname
                    const currentHost = window.location.hostname
                    
                    if (callbackHost === currentHost || callbackHost === 'compte.herime.com') {
                      // Ne pas logger l'URL complète car elle contient un token
                      console.error('[Auth] ⚠️ Callback URL pointe vers le même domaine, ARRÊT!', {
                        callbackHost,
                        currentHost
                      })
                      sessionStorage.removeItem(redirectingKey)
                      redirectPromise.value = null
                      return false
                    }
                  } catch (e) {
                    // Ne pas logger l'URL car elle peut contenir un token
                    console.warn('[Auth] Impossible de parser callback URL')
                    sessionStorage.removeItem(redirectingKey)
                    redirectPromise.value = null
                    return false
                  }
                  
                  // Redirection immédiate et définitive (sans setTimeout pour éviter les problèmes)
                  console.log('[Auth] Redirection vers domaine externe...')
                  sessionStorage.removeItem(redirectingKey)
                  window.location.replace(callbackUrl)
                  
                  // Ne jamais arriver ici car window.location.replace fait une navigation
                  return true
                } else {
                  console.error('[Auth] Structure de réponse invalide', {
                    has_data: !!response.data,
                    has_success: !!response.data?.success,
                    has_data_data: !!response.data?.data,
                    has_callback_url: !!response.data?.data?.callback_url
                  })
                  sessionStorage.removeItem(redirectingKey)
                  redirectPromise.value = null
                  return false
                }
              } catch (error) {
                // Ne pas logger les données de réponse qui peuvent contenir des tokens
                console.error('[Auth] Erreur lors de la génération du token SSO:', {
                  message: error.message,
                  status: error.response?.status,
                  response_data: error.response?.data?.message
                })
                sessionStorage.removeItem(redirectingKey)
                redirectPromise.value = null
                return false
              }
            })()
          }
          
          // Attendre la redirection
          try {
            const redirected = await redirectPromise.value
            if (redirected) {
              console.log('[Auth] Redirection en cours...')
              // Ne pas continuer, la redirection va se faire
              return
            }
          } catch (error) {
            console.error('[Auth] Erreur lors de la redirection:', error)
            redirectPromise.value = null
            sessionStorage.removeItem(redirectingKey)
          }
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
        path: route.path
      })
      
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
      isRedirecting
    }
  }
}
</script>
