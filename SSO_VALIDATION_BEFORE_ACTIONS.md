# Validation Avant Actions Importantes - Guide d'Implémentation SSO

## 📋 Vue d'ensemble

Cette méthode consiste à valider le token SSO **uniquement avant les actions importantes** de l'utilisateur, plutôt que d'utiliser un polling continu. C'est la méthode **recommandée** car elle offre le meilleur équilibre entre sécurité, performance et charge serveur.

## ✅ Avantages

- ✅ **Charge serveur minimale** : Pas de requêtes inutiles
- ✅ **Détection immédiate** : La déconnexion est détectée avant chaque action
- ✅ **Meilleure expérience utilisateur** : Pas de latence inutile
- ✅ **Sécurité renforcée** : Validation systématique avant les actions critiques
- ✅ **Simple à implémenter** : Pas de gestion complexe de timers

## 🎯 Quand Utiliser Cette Méthode

Cette méthode est idéale pour :
- ✅ La plupart des applications web
- ✅ Applications avec actions utilisateur ponctuelles
- ✅ Applications où la performance est importante
- ✅ Applications avec beaucoup d'utilisateurs simultanés

## 🔧 Implémentation

### 1. Endpoint de Vérification

**Endpoint :** `POST https://compte.herime.com/api/sso/check-token`

**Corps de la requête (JSON) :**
```json
{
    "token": "VOTRE_TOKEN_SSO"
}
```

**Réponse (Token valide) :**
```json
{
    "success": true,
    "valid": true,
    "user_id": 1
}
```

**Réponse (Token révoqué/invalide) :**
```json
{
    "success": false,
    "valid": false,
    "message": "Token not found or revoked"
}
```

### 2. Fonction de Validation Réutilisable

#### JavaScript Vanilla

```javascript
/**
 * Vérifie si le token SSO est toujours valide
 * @param {string} token - Le token SSO
 * @returns {Promise<boolean>} - true si le token est valide, false sinon
 */
async function validateSSOToken(token) {
    try {
        const response = await fetch('https://compte.herime.com/api/sso/check-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ token })
        });

        const data = await response.json();
        return data.success && data.valid === true;
    } catch (error) {
        // En cas d'erreur réseau, considérer le token comme invalide par sécurité
        console.error('Erreur lors de la validation du token SSO:', error);
        return false;
    }
}

/**
 * Récupère le token SSO depuis le stockage
 * @returns {string|null} - Le token SSO ou null
 */
function getSSOToken() {
    // Récupérer depuis localStorage, sessionStorage, ou cookie
    return localStorage.getItem('sso_token') || 
           sessionStorage.getItem('sso_token') ||
           getCookie('sso_token');
}

/**
 * Gère la déconnexion de l'utilisateur
 */
function handleSSOLogout() {
    // Nettoyer les données de session
    localStorage.removeItem('sso_token');
    sessionStorage.clear();
    
    // Rediriger vers la page de connexion SSO
    const currentUrl = encodeURIComponent(window.location.href);
    window.location.href = `https://compte.herime.com/login?redirect=${currentUrl}&force_token=1`;
}

/**
 * Valide le token avant une action importante
 * @param {Function} action - La fonction à exécuter si le token est valide
 * @returns {Promise<void>}
 */
async function executeWithValidation(action) {
    const token = getSSOToken();
    
    if (!token) {
        handleSSOLogout();
        return;
    }

    const isValid = await validateSSOToken(token);
    
    if (!isValid) {
        handleSSOLogout();
        return;
    }

    // Token valide, exécuter l'action
    await action();
}
```

### 3. Utilisation dans les Actions Importantes

#### Exemple 1 : Sauvegarde de Données

```javascript
async function saveUserData(userData) {
    const token = getSSOToken();
    
    // Valider le token avant de sauvegarder
    if (!await validateSSOToken(token)) {
        handleSSOLogout();
        return;
    }

    // Token valide, procéder avec la sauvegarde
    try {
        const response = await fetch('/api/user/data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(userData)
        });

        if (!response.ok) {
            throw new Error('Erreur lors de la sauvegarde');
        }

        const result = await response.json();
        console.log('Données sauvegardées avec succès', result);
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la sauvegarde des données');
    }
}
```

#### Exemple 2 : Suppression de Données

```javascript
async function deleteItem(itemId) {
    const token = getSSOToken();
    
    // Valider le token avant la suppression
    if (!await validateSSOToken(token)) {
        handleSSOLogout();
        return;
    }

    // Demander confirmation
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
        return;
    }

    // Token valide, procéder avec la suppression
    try {
        const response = await fetch(`/api/items/${itemId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            throw new Error('Erreur lors de la suppression');
        }

        console.log('Élément supprimé avec succès');
        // Rafraîchir la liste
        loadItems();
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression');
    }
}
```

#### Exemple 3 : Modification de Données

```javascript
async function updateProfile(profileData) {
    const token = getSSOToken();
    
    // Valider le token avant la modification
    if (!await validateSSOToken(token)) {
        handleSSOLogout();
        return;
    }

    // Token valide, procéder avec la modification
    try {
        const response = await fetch('/api/user/profile', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(profileData)
        });

        if (!response.ok) {
            throw new Error('Erreur lors de la modification');
        }

        const result = await response.json();
        console.log('Profil modifié avec succès', result);
        showSuccessMessage('Profil modifié avec succès');
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la modification du profil');
    }
}
```

### 4. Implémentation avec Vue.js

#### Composable Vue 3

```javascript
// composables/useSSOValidation.js
import { ref } from 'vue'

export function useSSOValidation() {
    const isValidating = ref(false)

    /**
     * Valide le token SSO
     */
    const validateToken = async (token) => {
        if (!token) {
            return false
        }

        isValidating.value = true

        try {
            const response = await fetch('https://compte.herime.com/api/sso/check-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ token })
            })

            const data = await response.json()
            return data.success && data.valid === true
        } catch (error) {
            console.error('Erreur lors de la validation du token SSO:', error)
            return false
        } finally {
            isValidating.value = false
        }
    }

    /**
     * Valide le token avant une action importante
     */
    const validateBeforeAction = async (token, action) => {
        const isValid = await validateToken(token)
        
        if (!isValid) {
            handleLogout()
            return false
        }

        // Token valide, exécuter l'action
        if (typeof action === 'function') {
            await action()
        }

        return true
    }

    /**
     * Gère la déconnexion
     */
    const handleLogout = () => {
        localStorage.removeItem('sso_token')
        sessionStorage.clear()
        const currentUrl = encodeURIComponent(window.location.href)
        window.location.href = `https://compte.herime.com/login?redirect=${currentUrl}&force_token=1`
    }

    return {
        isValidating,
        validateToken,
        validateBeforeAction,
        handleLogout
    }
}
```

#### Utilisation dans un Composant Vue

```vue
<template>
  <div>
    <button 
      @click="handleSave" 
      :disabled="isSaving || isValidating"
    >
      <span v-if="isSaving">Enregistrement...</span>
      <span v-else>Enregistrer</span>
    </button>

    <button 
      @click="handleDelete" 
      :disabled="isDeleting || isValidating"
    >
      Supprimer
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useSSOValidation } from '@/composables/useSSOValidation'

const { validateBeforeAction, isValidating } = useSSOValidation()
const isSaving = ref(false)
const isDeleting = ref(false)

// Récupérer le token depuis le store ou localStorage
const ssoToken = localStorage.getItem('sso_token')

const handleSave = async () => {
  isSaving.value = true
  
  try {
    await validateBeforeAction(ssoToken, async () => {
      // Action à exécuter si le token est valide
      const response = await fetch('/api/data', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${ssoToken}`
        },
        body: JSON.stringify(formData.value)
      })
      
      if (!response.ok) {
        throw new Error('Erreur lors de la sauvegarde')
      }
      
      const result = await response.json()
      console.log('Données sauvegardées', result)
    })
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    isSaving.value = false
  }
}

const handleDelete = async () => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ?')) {
    return
  }

  isDeleting.value = true
  
  try {
    await validateBeforeAction(ssoToken, async () => {
      const response = await fetch(`/api/items/${itemId.value}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${ssoToken}`
        }
      })
      
      if (!response.ok) {
        throw new Error('Erreur lors de la suppression')
      }
      
      // Rafraîchir la liste
      await loadItems()
    })
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    isDeleting.value = false
  }
}
</script>
```

### 5. Implémentation avec Axios Interceptor

Pour automatiser la validation avant chaque requête API importante :

```javascript
import axios from 'axios'

// Créer une instance Axios
const apiClient = axios.create({
    baseURL: '/api',
    timeout: 10000
})

// Intercepteur pour valider le token avant les requêtes importantes
apiClient.interceptors.request.use(
    async (config) => {
        // Liste des méthodes qui nécessitent une validation
        const methodsRequiringValidation = ['POST', 'PUT', 'PATCH', 'DELETE']
        
        if (methodsRequiringValidation.includes(config.method.toUpperCase())) {
            const token = getSSOToken()
            
            if (token) {
                // Valider le token avant la requête
                const isValid = await validateSSOToken(token)
                
                if (!isValid) {
                    // Token invalide, annuler la requête et déconnecter
                    handleSSOLogout()
                    return Promise.reject(new Error('Token SSO invalide'))
                }
            }
        }
        
        // Ajouter le token à l'en-tête Authorization
        const token = getSSOToken()
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        
        return config
    },
    (error) => {
        return Promise.reject(error)
    }
)

// Utilisation
async function saveData(data) {
    try {
        // La validation sera automatique grâce à l'intercepteur
        const response = await apiClient.post('/user/data', data)
        console.log('Données sauvegardées', response.data)
    } catch (error) {
        if (error.message === 'Token SSO invalide') {
            // La déconnexion est déjà gérée par handleSSOLogout()
            return
        }
        console.error('Erreur:', error)
    }
}
```

### 6. Implémentation avec Pinia Store (Vue.js)

```javascript
// stores/sso.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSSOStore = defineStore('sso', () => {
    const token = ref(localStorage.getItem('sso_token'))
    const isValidating = ref(false)

    /**
     * Valide le token SSO
     */
    const validateToken = async () => {
        if (!token.value) {
            return false
        }

        isValidating.value = true

        try {
            const response = await fetch('https://compte.herime.com/api/sso/check-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ token: token.value })
            })

            const data = await response.json()
            return data.success && data.valid === true
        } catch (error) {
            console.error('Erreur lors de la validation du token SSO:', error)
            return false
        } finally {
            isValidating.value = false
        }
    }

    /**
     * Valide le token avant une action importante
     */
    const validateBeforeAction = async (action) => {
        const isValid = await validateToken()
        
        if (!isValid) {
            logout()
            return false
        }

        // Token valide, exécuter l'action
        if (typeof action === 'function') {
            await action()
        }

        return true
    }

    /**
     * Déconnexion
     */
    const logout = () => {
        token.value = null
        localStorage.removeItem('sso_token')
        sessionStorage.clear()
        const currentUrl = encodeURIComponent(window.location.href)
        window.location.href = `https://compte.herime.com/login?redirect=${currentUrl}&force_token=1`
    }

    return {
        token,
        isValidating,
        validateToken,
        validateBeforeAction,
        logout
    }
})
```

#### Utilisation dans un Composant

```vue
<script setup>
import { useSSOStore } from '@/stores/sso'

const ssoStore = useSSOStore()

const handleSave = async () => {
    await ssoStore.validateBeforeAction(async () => {
        // Votre code de sauvegarde
        const response = await fetch('/api/data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${ssoStore.token}`
            },
            body: JSON.stringify(formData.value)
        })
        // ...
    })
}
</script>
```

## 📝 Actions à Valider

### Actions Critiques (Toujours valider)

- ✅ **Création de données** (POST)
- ✅ **Modification de données** (PUT, PATCH)
- ✅ **Suppression de données** (DELETE)
- ✅ **Changement de mot de passe**
- ✅ **Modification du profil**
- ✅ **Actions administratives**
- ✅ **Paiements et transactions**
- ✅ **Export de données sensibles**

### Actions Non-Critiques (Validation optionnelle)

- ⚠️ **Lecture de données** (GET) - Valider seulement pour les données sensibles
- ⚠️ **Navigation** - Pas besoin de validation
- ⚠️ **Affichage de contenu** - Pas besoin de validation

## 🎯 Exemples par Type d'Application

### Application E-commerce

```javascript
// Ajout au panier
async function addToCart(productId, quantity) {
    await validateBeforeAction(async () => {
        await api.post('/cart/add', { productId, quantity })
    })
}

// Passage de commande
async function checkout(orderData) {
    await validateBeforeAction(async () => {
        await api.post('/orders', orderData)
    })
}

// Annulation de commande
async function cancelOrder(orderId) {
    await validateBeforeAction(async () => {
        await api.delete(`/orders/${orderId}`)
    })
}
```

### Application de Gestion de Contenu

```javascript
// Publication d'article
async function publishArticle(articleData) {
    await validateBeforeAction(async () => {
        await api.post('/articles', articleData)
    })
}

// Modification d'article
async function updateArticle(articleId, articleData) {
    await validateBeforeAction(async () => {
        await api.put(`/articles/${articleId}`, articleData)
    })
}

// Suppression d'article
async function deleteArticle(articleId) {
    await validateBeforeAction(async () => {
        await api.delete(`/articles/${articleId}`)
    })
}
```

### Application de Formation

```javascript
// Inscription à un cours
async function enrollInCourse(courseId) {
    await validateBeforeAction(async () => {
        await api.post(`/courses/${courseId}/enroll`)
    })
}

// Soumission d'examen
async function submitExam(examId, answers) {
    await validateBeforeAction(async () => {
        await api.post(`/exams/${examId}/submit`, { answers })
    })
}

// Téléchargement de certificat
async function downloadCertificate(certificateId) {
    await validateBeforeAction(async () => {
        const response = await api.get(`/certificates/${certificateId}/download`)
        // Télécharger le fichier...
    })
}
```

## ⚡ Optimisations

### 1. Cache de Validation (Optionnel)

Pour éviter de valider plusieurs fois le token dans un court laps de temps :

```javascript
let lastValidationTime = 0
let lastValidationResult = null
const VALIDATION_CACHE_DURATION = 5000 // 5 secondes

async function validateSSOTokenWithCache(token) {
    const now = Date.now()
    
    // Utiliser le cache si la validation est récente
    if (lastValidationResult !== null && 
        (now - lastValidationTime) < VALIDATION_CACHE_DURATION) {
        return lastValidationResult
    }
    
    // Valider le token
    const isValid = await validateSSOToken(token)
    
    // Mettre à jour le cache
    lastValidationTime = now
    lastValidationResult = isValid
    
    return isValid
}
```

### 2. Validation en Parallèle avec l'Action

Pour réduire la latence perçue :

```javascript
async function saveDataWithParallelValidation(data) {
    const token = getSSOToken()
    
    // Démarrer la validation et la préparation des données en parallèle
    const [isValid, preparedData] = await Promise.all([
        validateSSOToken(token),
        prepareData(data)
    ])
    
    if (!isValid) {
        handleSSOLogout()
        return
    }
    
    // Token valide, procéder avec la sauvegarde
    await api.post('/data', preparedData)
}
```

### 3. Gestion des Erreurs de Réseau

```javascript
async function validateSSOTokenWithRetry(token, maxRetries = 2) {
    for (let i = 0; i <= maxRetries; i++) {
        try {
            const response = await fetch('https://compte.herime.com/api/sso/check-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ token })
            })

            const data = await response.json()
            return data.success && data.valid === true
        } catch (error) {
            if (i === maxRetries) {
                // Dernière tentative échouée
                console.error('Erreur lors de la validation du token SSO:', error)
                return false
            }
            // Attendre avant de réessayer
            await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)))
        }
    }
    
    return false
}
```

## 🔒 Sécurité

### Bonnes Pratiques

1. **Toujours valider côté serveur** : Ne jamais faire confiance uniquement à la validation côté client
2. **HTTPS obligatoire** : Toutes les communications doivent être chiffrées
3. **Ne pas exposer le token** : Ne pas logger ou afficher le token dans la console
4. **Gérer les erreurs** : Ne pas exposer d'informations sensibles dans les messages d'erreur
5. **Timeout des requêtes** : Limiter le temps d'attente des requêtes de validation

### Exemple de Validation Côté Serveur

```javascript
// Côté client : Valider avant l'action
async function saveData(data) {
    if (!await validateSSOToken(token)) {
        handleSSOLogout()
        return
    }
    
    // Envoyer la requête avec le token
    await api.post('/data', data, {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    })
}

// Côté serveur : Valider à nouveau le token
// (Le serveur doit toujours valider le token, même si le client l'a déjà fait)
```

## 📊 Comparaison avec le Polling

| Aspect | Validation Avant Actions | Polling Continu |
|--------|-------------------------|-----------------|
| **Charge serveur** | ✅ Très faible | ⚠️ Élevée |
| **Détection** | ✅ Immédiate | ⚠️ Délai (30-120s) |
| **Complexité** | ✅ Simple | ⚠️ Plus complexe |
| **Performance** | ✅ Excellente | ⚠️ Bonne |
| **Expérience utilisateur** | ✅ Optimale | ✅ Bonne |
| **Recommandation** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |

## 🎯 Résumé

### Méthode Recommandée

1. **Valider le token au chargement de la page** (une seule fois)
2. **Valider avant chaque action importante** (POST, PUT, DELETE, etc.)
3. **Ne pas utiliser de polling continu**
4. **Gérer la déconnexion automatiquement** si le token est invalide

### Code Minimal

```javascript
// Fonction de validation
async function validateSSOToken(token) {
    const response = await fetch('https://compte.herime.com/api/sso/check-token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token })
    })
    const data = await response.json()
    return data.success && data.valid === true
}

// Utilisation avant une action
async function saveData(data) {
    const token = getSSOToken()
    if (!await validateSSOToken(token)) {
        handleSSOLogout()
        return
    }
    // Procéder avec l'action...
}
```

Cette méthode offre le meilleur équilibre entre sécurité, performance et charge serveur ! 🚀

