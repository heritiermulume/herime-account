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
  console.log('Router guard: navigating to', to.path)
  
  const authStore = useAuthStore()
  
  // Vérifier l'authentification
  await authStore.checkAuth()
  
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
      console.log('User not authenticated, redirecting to login')
      next('/login')
      return
    }
    
    // Vérifier les routes qui nécessitent un super utilisateur
    if (to.meta.requiresSuperUser && user?.role !== 'super_user') {
      console.log('User is not a super user, redirecting to dashboard')
      next('/dashboard')
      return
    }
  }
  
  // Vérifier les routes qui nécessitent d'être un invité (non authentifié)
  if (to.meta.requiresGuest && isAuthenticated) {
    // Si force_token est présent, permettre l'accès à la page de login
    // Le composant Auth.vue gérera la redirection SSO AVANT le montage
    const hasForceToken = to.query.force_token === '1' || 
                         to.query.force_token === 1 || 
                         to.query.force_token === true || 
                         to.query.force_token === 'true'
    
    if (hasForceToken) {
      console.log('[Router] User authenticated with force_token, allowing access for SSO redirect')
      next()
      return
    }
    console.log('[Router] User authenticated without force_token, redirecting to dashboard')
    next('/dashboard')
    return
  }
  
  next()
})

export default router
