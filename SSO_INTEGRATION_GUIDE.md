# Guide d'intégration SSO pour les sites externes

Ce guide explique comment intégrer l'authentification SSO Herime dans vos applications externes (academie.herime.com, store.herime.com, etc.).

## 🔐 Flux d'authentification SSO

### 1. Connexion SSO (Login)

#### Étape 1 : Rediriger vers le SSO

Depuis votre site externe (`academie.herime.com`), redirigez l'utilisateur vers le SSO :

```javascript
// Dans votre application (academie.herime.com)
function redirectToSSO() {
    const currentUrl = window.location.href;
    const ssoUrl = `https://account.herime.com/login?redirect=${encodeURIComponent(currentUrl)}&force_token=1`;
    window.location.href = ssoUrl;
}
```

#### Étape 2 : L'utilisateur se connecte

L'utilisateur se connecte sur `account.herime.com` avec son email/téléphone et mot de passe.

#### Étape 3 : Récupérer le token

Après connexion, l'utilisateur est redirigé vers votre site avec un token :

```
https://academie.herime.com/votre-page?token=eyJ0eXAiOiJKV1QiLCJhbGc...
```

Récupérez le token depuis l'URL :

```javascript
// Dans votre application (academie.herime.com)
function handleSSOCallback() {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    if (token) {
        // Valider le token
        validateToken(token);
    }
}

// Appeler au chargement de la page
window.addEventListener('DOMContentLoaded', handleSSOCallback);
```

#### Étape 4 : Valider le token

Validez le token auprès du serveur SSO :

```javascript
async function validateToken(token) {
    try {
        const response = await fetch('https://account.herime.com/api/sso/validate-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ token })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Utilisateur authentifié
            const user = data.data.user;
            
            // Stocker le token
            localStorage.setItem('sso_token', token);
            localStorage.setItem('user', JSON.stringify(user));
            
            // Rediriger vers le dashboard
            window.location.href = '/dashboard';
        } else {
            console.error('Token invalide:', data.message);
            redirectToSSO();
        }
    } catch (error) {
        console.error('Erreur de validation SSO:', error);
        redirectToSSO();
    }
}
```

#### Étape 5 : Vérifier la session périodiquement

Vérifiez régulièrement si la session est toujours active :

```javascript
// Vérifier toutes les 5 minutes si la session est active
setInterval(async () => {
    const token = localStorage.getItem('sso_token');
    if (!token) return;
    
    try {
        const response = await fetch('https://account.herime.com/api/sso/check-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ token })
        });
        
        const data = await response.json();
        
        if (!data.valid || !data.session_active) {
            // Session expirée ou révoquée
            handleLogout();
        }
    } catch (error) {
        console.error('Erreur de vérification de session:', error);
    }
}, 5 * 60 * 1000); // 5 minutes
```

---

## 🚪 Flux de déconnexion SSO (Logout)

### Option 1 : Déconnexion locale uniquement

Déconnectez l'utilisateur uniquement de votre site :

```javascript
function logoutLocal() {
    // Supprimer les données locales
    localStorage.removeItem('sso_token');
    localStorage.removeItem('user');
    
    // Rediriger vers la page de connexion
    window.location.href = '/login';
}
```

⚠️ **Attention** : L'utilisateur restera connecté sur les autres sites et sur account.herime.com.

### Option 2 : Déconnexion globale SSO (Recommandé)

Déconnectez l'utilisateur de tous les sites :

```javascript
async function logoutSSO() {
    const token = localStorage.getItem('sso_token');
    
    // 1. Supprimer les données locales d'abord
    localStorage.removeItem('sso_token');
    localStorage.removeItem('user');
    
    // 2. Révoquer le token sur le serveur SSO
    if (token) {
        try {
            await fetch('https://account.herime.com/api/logout', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                }
            });
        } catch (error) {
            console.error('Erreur lors de la déconnexion SSO:', error);
        }
    }
    
    // 3. Rediriger vers la page de connexion SSO
    window.location.href = 'https://account.herime.com/login';
}
```

---

## 🔄 Implémentation complète (Exemple avec Vue.js)

### Store Pinia pour la gestion SSO

```javascript
// stores/sso.js
import { defineStore } from 'pinia'

export const useSSOStore = defineStore('sso', {
    state: () => ({
        token: localStorage.getItem('sso_token') || null,
        user: JSON.parse(localStorage.getItem('user') || 'null'),
        isAuthenticated: false,
        checkInterval: null,
    }),
    
    actions: {
        // Initialiser l'authentification SSO
        async initSSO() {
            // Vérifier si on a un token dans l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            
            if (token) {
                await this.validateToken(token);
                // Nettoyer l'URL
                window.history.replaceState({}, '', window.location.pathname);
            } else if (this.token) {
                // Vérifier le token existant
                await this.checkToken();
            }
            
            // Démarrer la vérification périodique
            this.startSessionCheck();
        },
        
        // Valider le token SSO
        async validateToken(token) {
            try {
                const response = await fetch('https://account.herime.com/api/sso/validate-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ token })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.token = token;
                    this.user = data.data.user;
                    this.isAuthenticated = true;
                    
                    localStorage.setItem('sso_token', token);
                    localStorage.setItem('user', JSON.stringify(this.user));
                    
                    return true;
                } else {
                    this.logout();
                    return false;
                }
            } catch (error) {
                console.error('Erreur de validation SSO:', error);
                this.logout();
                return false;
            }
        },
        
        // Vérifier si le token est toujours valide
        async checkToken() {
            if (!this.token) {
                this.isAuthenticated = false;
                return false;
            }
            
            try {
                const response = await fetch('https://account.herime.com/api/sso/check-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ token: this.token })
                });
                
                const data = await response.json();
                
                if (data.valid && data.session_active) {
                    this.isAuthenticated = true;
                    return true;
                } else {
                    this.logout();
                    return false;
                }
            } catch (error) {
                console.error('Erreur de vérification de token:', error);
                return false;
            }
        },
        
        // Démarrer la vérification périodique de la session
        startSessionCheck() {
            if (this.checkInterval) {
                clearInterval(this.checkInterval);
            }
            
            this.checkInterval = setInterval(() => {
                this.checkToken();
            }, 5 * 60 * 1000); // 5 minutes
        },
        
        // Rediriger vers le SSO pour connexion
        redirectToSSO() {
            const currentUrl = window.location.href;
            const ssoUrl = `https://account.herime.com/login?redirect=${encodeURIComponent(currentUrl)}&force_token=1`;
            window.location.href = ssoUrl;
        },
        
        // Déconnexion globale
        async logout() {
            const token = this.token;
            
            // Nettoyer l'état local
            this.token = null;
            this.user = null;
            this.isAuthenticated = false;
            localStorage.removeItem('sso_token');
            localStorage.removeItem('user');
            
            // Arrêter la vérification périodique
            if (this.checkInterval) {
                clearInterval(this.checkInterval);
            }
            
            // Révoquer le token sur le serveur
            if (token) {
                try {
                    await fetch('https://account.herime.com/api/logout', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                        }
                    });
                } catch (error) {
                    console.error('Erreur lors de la déconnexion SSO:', error);
                }
            }
            
            // Rediriger vers la page de connexion SSO
            window.location.href = 'https://account.herime.com/login';
        }
    }
});
```

### Composant de connexion

```vue
<!-- components/LoginButton.vue -->
<template>
  <div>
    <button 
      v-if="!isAuthenticated" 
      @click="login"
      class="btn btn-primary"
    >
      Se connecter
    </button>
    <div v-else class="user-menu">
      <img :src="user.avatar" :alt="user.name" class="avatar" />
      <span>{{ user.name }}</span>
      <button @click="logout" class="btn btn-secondary">
        Déconnexion
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useSSOStore } from '../stores/sso'

const ssoStore = useSSOStore()

const isAuthenticated = computed(() => ssoStore.isAuthenticated)
const user = computed(() => ssoStore.user)

function login() {
  ssoStore.redirectToSSO()
}

function logout() {
  ssoStore.logout()
}
</script>
```

### Guard de navigation (Router)

```javascript
// router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useSSOStore } from '../stores/sso'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/dashboard',
      component: () => import('../views/Dashboard.vue'),
      meta: { requiresAuth: true }
    },
    // ... autres routes
  ]
})

router.beforeEach(async (to, from, next) => {
  const ssoStore = useSSOStore()
  
  if (to.meta.requiresAuth && !ssoStore.isAuthenticated) {
    // Vérifier d'abord si on a un token valide
    const hasValidToken = await ssoStore.checkToken()
    
    if (!hasValidToken) {
      // Rediriger vers le SSO
      ssoStore.redirectToSSO()
      return
    }
  }
  
  next()
})

export default router
```

### Initialisation dans App.vue

```vue
<!-- App.vue -->
<script setup>
import { onMounted } from 'vue'
import { useSSOStore } from './stores/sso'

const ssoStore = useSSOStore()

onMounted(() => {
  // Initialiser l'authentification SSO
  ssoStore.initSSO()
})
</script>
```

---

## 🔒 Sécurité et bonnes pratiques

### 1. Validation avec secret (Plus sécurisé)

Pour les appels serveur-à-serveur, utilisez l'endpoint avec secret :

```javascript
// Backend de academie.herime.com (Node.js/PHP)
async function validateTokenSecure(token) {
    const response = await fetch('https://account.herime.com/api/validate-token', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${process.env.SSO_SECRET}`,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token })
    });
    
    return await response.json();
}
```

### 2. Gestion des erreurs

```javascript
function handleSSOError(error) {
    console.error('Erreur SSO:', error);
    
    // Nettoyer les données locales
    localStorage.removeItem('sso_token');
    localStorage.removeItem('user');
    
    // Afficher un message à l'utilisateur
    alert('Votre session a expiré. Veuillez vous reconnecter.');
    
    // Rediriger vers la page de connexion
    window.location.href = 'https://account.herime.com/login';
}
```

### 3. Protection contre les attaques CSRF

Toujours vérifier l'origine du token et utiliser HTTPS en production.

---

## 📋 Checklist d'intégration

- [ ] Implémenter la redirection vers le SSO
- [ ] Gérer la récupération du token depuis l'URL
- [ ] Valider le token auprès du serveur SSO
- [ ] Stocker le token en toute sécurité
- [ ] Implémenter la vérification périodique de session
- [ ] Implémenter la déconnexion globale
- [ ] Protéger les routes nécessitant une authentification
- [ ] Gérer les erreurs et les cas limites
- [ ] Tester sur tous les navigateurs
- [ ] Vérifier que HTTPS est activé en production

---

## 🐛 Débogage

### Vérifier si le token est valide

```javascript
console.log('Token:', localStorage.getItem('sso_token'));
console.log('User:', localStorage.getItem('user'));
```

### Tester la validation manuellement

```bash
curl -X POST https://account.herime.com/api/sso/validate-token \
  -H "Content-Type: application/json" \
  -d '{"token": "VOTRE_TOKEN_ICI"}'
```

---

## 📞 Support

Pour toute question ou problème d'intégration SSO :
- Consulter la documentation complète : README.md
- Vérifier les logs : `storage/logs/laravel.log`
- Créer une issue sur GitHub

---

**Dernière mise à jour** : Novembre 2025

