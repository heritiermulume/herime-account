import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import Auth from '../components/Auth.vue'
import Dashboard from '../components/Dashboard.vue'
import Profile from '../components/Profile.vue'
import Security from '../components/Security.vue'
import Notifications from '../components/Notifications.vue'
import About from '../components/About.vue'
import ResetPassword from '../components/ResetPassword.vue'
import AdminLogin from '../components/admin/AdminLogin.vue'
import AdminDashboard from '../components/admin/AdminDashboard.vue'
import AdminUsers from '../components/admin/AdminUsers.vue'
import AdminSessions from '../components/admin/AdminSessions.vue'
import AdminSettings from '../components/admin/AdminSettings.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Auth,
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: Auth,
    meta: { requiresGuest: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
    meta: { requiresAuth: true }
  },
  {
    path: '/security',
    name: 'Security',
    component: Security,
    meta: { requiresAuth: true }
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: Notifications,
    meta: { requiresAuth: true }
  },
  {
    path: '/about',
    name: 'About',
    component: About,
    meta: { requiresAuth: true }
  },
  {
    path: '/reset-password',
    name: 'ResetPassword',
    component: ResetPassword,
    meta: { requiresGuest: true }
  },
  // Admin routes - Accessible only to super users
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: AdminLogin,
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, requiresSuperUser: true }
  },
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: AdminUsers,
    meta: { requiresAuth: true, requiresSuperUser: true }
  },
  {
    path: '/admin/sessions',
    name: 'AdminSessions',
    component: AdminSessions,
    meta: { requiresAuth: true, requiresSuperUser: true }
  },
  {
    path: '/admin/settings',
    name: 'AdminSettings',
    component: AdminSettings,
    meta: { requiresAuth: true, requiresSuperUser: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  
  const authStore = useAuthStore()
  
  // VÉRIFIER FORCE_TOKEN EN PREMIER (avant même de vérifier l'authentification)
  const hasForceToken = to.query.force_token === '1' || 
                       to.query.force_token === 1 || 
                       to.query.force_token === true || 
                       to.query.force_token === 'true' ||
                       to.query.force_token === 'yes' ||
                       to.query.force_token === 'on'
  
  // Vérifier si une boucle SSO a été détectée - si oui, permettre l'accès à la page de login
  if (typeof window !== 'undefined' && sessionStorage.getItem('sso_loop_detected') === 'true') {
    console.log('[ROUTER] SSO loop detected, allowing access to login page')
    if (to.path === '/login' || to.path === '/register') {
      next()
      return
    }
  }
  
  // Vérifier la boucle directement dans le router AVANT toute autre vérification
  // MAIS seulement si l'utilisateur n'est pas authentifié
  if (hasForceToken && to.path === '/login' && typeof window !== 'undefined') {
    const lastRedirectTo = sessionStorage.getItem('sso_last_redirect_to')
    const redirectingTimestamp = sessionStorage.getItem('sso_redirecting_timestamp')
    
    if (lastRedirectTo && redirectingTimestamp) {
      const now = Date.now()
      const elapsed = now - parseInt(redirectingTimestamp, 10)
      const currentHost = window.location.hostname.replace(/^www\./, '').toLowerCase()
      
      // Si on a redirigé vers un domaine externe récemment (moins de 10 secondes)
      // ET qu'on est de retour sur compte.herime.com avec force_token, c'est probablement une boucle
      // MAIS seulement si l'utilisateur n'est pas authentifié
      if (elapsed < 10000 && lastRedirectTo !== 'compte.herime.com' && currentHost === 'compte.herime.com') {
        // Vérifier si l'utilisateur est authentifié avant de détecter une boucle
        const token = localStorage.getItem('access_token')
        if (!token) {
          // Pas de token, c'est probablement une boucle
          console.log('[ROUTER] SSO loop detected in router (no token), setting flag and allowing access to login page')
          sessionStorage.setItem('sso_loop_detected', 'true')
          // Nettoyer les flags
          sessionStorage.removeItem('sso_redirecting')
          sessionStorage.removeItem('sso_redirecting_timestamp')
          sessionStorage.removeItem('sso_redirecting_url')
          sessionStorage.removeItem('sso_redirect_attempts')
          sessionStorage.removeItem('sso_last_redirect_to')
          // Nettoyer ce flag après 30 secondes
          setTimeout(() => {
            sessionStorage.removeItem('sso_loop_detected')
          }, 30000)
          // Permettre l'accès à la page de login
          next()
          return
        } else {
          // L'utilisateur a un token, ce n'est probablement pas une boucle
          // Nettoyer les anciens flags pour permettre une nouvelle tentative
          console.log('[ROUTER] User has token, clearing old redirect flags to allow new attempt')
          sessionStorage.removeItem('sso_redirecting')
          sessionStorage.removeItem('sso_redirecting_timestamp')
          sessionStorage.removeItem('sso_redirecting_url')
          sessionStorage.removeItem('sso_redirect_attempts')
          sessionStorage.removeItem('sso_last_redirect_to')
          sessionStorage.removeItem('sso_loop_detected')
          // Continuer normalement, laisser Auth.vue gérer la redirection
        }
      }
    }
  }
  
  if (hasForceToken && to.path === '/login') {
    // OPTIMISATION: Éviter toute vérification dans le router quand on a force_token
    // Laisser Auth.vue gérer complètement la logique de redirection SSO
    // Cela évite les appels redondants à checkAuth()
    next()
    return
  }
  
  // OPTIMISATION: Vérifier l'authentification pour les autres cas avec timeout
  // Vérifier d'abord si on a un token avant d'appeler checkAuth()
  const token = typeof window !== 'undefined' ? localStorage.getItem('access_token') : null
  
  if (token) {
    try {
      await Promise.race([
        authStore.checkAuth(),
        new Promise((_, reject) => setTimeout(() => reject(new Error('Auth check timeout')), 5000))
      ])
    } catch (error) {
      // En cas de timeout, utiliser l'état actuel du store
    }
  } else {
    // Pas de token, forcer l'état non authentifié
    authStore.authenticated = false
    authStore.user = null
  }
  
  const isAuthenticated = authStore.authenticated
  const user = authStore.user
  
  // Si l'utilisateur est sur la page d'accueil, rediriger selon son statut
  if (to.path === '/') {
    if (isAuthenticated) {
      next('/dashboard')
    } else {
      next('/login')
    }
    return
  }
  
  // Vérifier les routes qui nécessitent une authentification
  if (to.meta.requiresAuth) {
    if (!isAuthenticated) {
      next('/login')
      return
    }
    
    // Vérifier les routes qui nécessitent un super utilisateur
    if (to.meta.requiresSuperUser && user?.role !== 'super_user') {
      next('/dashboard')
      return
    }
  }
  
  // Vérifier les routes qui nécessitent d'être un invité (non authentifié)
  if (to.meta.requiresGuest && isAuthenticated) {
    // Vérifier si une boucle SSO a été détectée - si oui, permettre l'accès à la page de login
    if (typeof window !== 'undefined' && sessionStorage.getItem('sso_loop_detected') === 'true') {
      console.log('[ROUTER] SSO loop detected, allowing access to login page')
      next()
      return
    }
    
    // Vérifier si on doit rediriger vers un site externe (SSO)
    const redirectParam = to.query.redirect
    if (redirectParam && typeof redirectParam === 'string') {
      try {
        // Décoder l'URL
        let decoded = redirectParam
        for (let i = 0; i < 3; i++) {
          try {
            const temp = decodeURIComponent(decoded)
            if (temp === decoded) break
            decoded = temp
          } catch (e) {
            break
          }
        }
        
        if (decoded.startsWith('http')) {
          const url = new URL(decoded)
          const currentHost = window.location.hostname.replace(/^www\./, '').toLowerCase()
          const urlHost = url.hostname.replace(/^www\./, '').toLowerCase()
          
          // Si c'est un domaine externe, ne pas rediriger vers dashboard
          // Laisser Auth.vue gérer la redirection SSO
          if (urlHost !== currentHost && urlHost !== 'compte.herime.com') {
            next()
            return
          }
        }
      } catch (e) {
        // Ignorer les erreurs
      }
    }
    
    // Si on arrive ici et que l'utilisateur est authentifié, mais pas de force_token
    // C'est une visite normale sur /login ou /register, rediriger vers dashboard
    next('/dashboard')
    return
  }
  
  next()
})

export default router
