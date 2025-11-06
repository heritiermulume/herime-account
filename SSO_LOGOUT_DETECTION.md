# Détection Immédiate de la Déconnexion SSO

Ce document décrit comment les sites externes peuvent détecter immédiatement lorsqu'un utilisateur se déconnecte du système SSO central (`compte.herime.com`).

## 🔍 Problème

Lorsqu'un utilisateur se déconnecte de `compte.herime.com`, tous ses tokens Passport sont révoqués et toutes ses sessions sont marquées comme inactives. Cependant, les sites externes (comme `academie.herime.com`) ne le découvrent que lors de la prochaine validation du token, ce qui peut prendre du temps.

## ✅ Solutions Disponibles

### 1. Polling Périodique (Recommandé)

La méthode la plus simple et la plus fiable consiste à vérifier périodiquement si le token est toujours valide.

#### Endpoint de Vérification Légère

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

#### Implémentation JavaScript (Exemple Optimisé)

```javascript
class SSOSessionManager {
    constructor(ssoToken, checkInterval = 120000) { // 120 secondes (2 minutes) par défaut - OPTIMISÉ
        this.ssoToken = ssoToken;
        this.checkInterval = checkInterval;
        this.checkTimer = null;
        this.isValid = true;
        this.isPageVisible = true;
        
        // Détecter quand la page devient inactive
        document.addEventListener('visibilitychange', () => {
            this.isPageVisible = !document.hidden;
            if (!this.isPageVisible) {
                this.stopPolling(); // Arrêter le polling quand la page est en arrière-plan
            } else {
                this.startPolling(); // Reprendre quand la page redevient active
            }
        });
    }

    startPolling() {
        // Vérifier immédiatement
        this.checkToken();
        
        // Puis vérifier périodiquement
        this.checkTimer = setInterval(() => {
            this.checkToken();
        }, this.checkInterval);
    }

    stopPolling() {
        if (this.checkTimer) {
            clearInterval(this.checkTimer);
            this.checkTimer = null;
        }
    }

    async checkToken() {
        try {
            const response = await fetch('https://compte.herime.com/api/sso/check-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    token: this.ssoToken
                })
            });

            const data = await response.json();

            if (data.success && data.valid) {
                // Token toujours valide
                this.isValid = true;
            } else {
                // Token révoqué ou invalide
                this.isValid = false;
                this.handleLogout();
            }
        } catch (error) {
            // En cas d'erreur réseau, ne pas déconnecter immédiatement
            // Attendre la prochaine vérification
            console.error('Erreur lors de la vérification du token SSO:', error);
        }
    }

    handleLogout() {
        // Arrêter le polling
        this.stopPolling();
        
        // Nettoyer les données de session locale
        localStorage.removeItem('sso_token');
        sessionStorage.clear();
        
        // Rediriger vers la page de connexion ou afficher un message
        window.location.href = '/login?message=session_expired';
    }
}

// Utilisation
const ssoToken = getSSOTokenFromUrl(); // Récupérer le token depuis l'URL
if (ssoToken) {
    const sessionManager = new SSOSessionManager(ssoToken, 30000); // Vérifier toutes les 30 secondes
    sessionManager.startPolling();
    
    // Arrêter le polling quand l'utilisateur quitte la page
    window.addEventListener('beforeunload', () => {
        sessionManager.stopPolling();
    });
}
```

#### Implémentation avec Vue.js (Exemple Optimisé)

```javascript
// Dans votre composant Vue ou store Pinia
import { ref, onMounted, onUnmounted } from 'vue'

export function useSSOSession(token, options = {}) {
    const isValid = ref(true)
    let checkTimer = null
    const {
        checkInterval = 120000, // 2 minutes par défaut (optimisé)
        enablePolling = false, // Désactivé par défaut - utiliser validation avant actions
        stopWhenInactive = true // Arrêter quand la page est inactive
    } = options

    const checkToken = async () => {
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

            if (!data.success || !data.valid) {
                isValid.value = false
                handleLogout()
            }
        } catch (error) {
            // En cas d'erreur réseau, ne pas déconnecter immédiatement
            // Attendre la prochaine vérification
            console.error('Erreur lors de la vérification du token SSO:', error)
        }
    }

    const handleLogout = () => {
        // Nettoyer et rediriger
        localStorage.removeItem('sso_token')
        window.location.href = '/login?message=session_expired'
    }

    const startPolling = (interval = checkInterval) => {
        // Ne pas démarrer si la page est inactive
        if (stopWhenInactive && document.hidden) {
            return
        }
        
        checkToken() // Vérifier immédiatement
        checkTimer = setInterval(() => {
            // Vérifier si la page est toujours visible
            if (stopWhenInactive && document.hidden) {
                stopPolling()
                return
            }
            checkToken()
        }, interval)
    }

    const stopPolling = () => {
        if (checkTimer) {
            clearInterval(checkTimer)
            checkTimer = null
        }
    }

    // Détecter les changements de visibilité de la page
    const handleVisibilityChange = () => {
        if (document.hidden) {
            stopPolling() // Arrêter quand la page est en arrière-plan
        } else if (enablePolling) {
            startPolling() // Reprendre quand la page redevient active
        }
    }

    onMounted(() => {
        if (token) {
            // Valider immédiatement au chargement
            checkToken()
            
            // Démarrer le polling seulement si activé
            if (enablePolling) {
                startPolling()
                document.addEventListener('visibilitychange', handleVisibilityChange)
            }
        }
    })

    onUnmounted(() => {
        stopPolling()
        document.removeEventListener('visibilitychange', handleVisibilityChange)
    })

    // Fonction pour valider avant une action importante
    const validateBeforeAction = async () => {
        await checkToken()
        return isValid.value
    }

    return {
        isValid,
        checkToken,
        validateBeforeAction, // Utiliser cette fonction avant les actions importantes
        startPolling,
        stopPolling
    }
}

// Utilisation recommandée (sans polling continu)
const { validateBeforeAction } = useSSOSession(token, {
    enablePolling: false // Pas de polling continu
})

// Valider seulement avant les actions importantes
async function saveData() {
    if (!await validateBeforeAction()) {
        return // L'utilisateur sera déconnecté
    }
    // Procéder avec l'action...
}
```

### 2. Validation Avant Chaque Action Importante

Valider le token avant chaque action importante (création, modification, suppression, etc.) :

```javascript
async function performImportantAction(actionData) {
    // Vérifier d'abord si le token est toujours valide
    const tokenCheck = await fetch('https://compte.herime.com/api/sso/check-token', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            token: getSSOToken()
        })
    });

    const checkData = await tokenCheck.json();

    if (!checkData.success || !checkData.valid) {
        // Token invalide, déconnecter l'utilisateur
        handleLogout();
        return;
    }

    // Token valide, procéder avec l'action
    // ... votre code d'action ...
}
```

### 3. Validation Complète (Endpoint existant)

Pour obtenir les informations complètes de l'utilisateur, utilisez l'endpoint de validation complet :

**Endpoint :** `POST https://compte.herime.com/api/sso/validate-token`

**Corps de la requête (JSON) :**
```json
{
    "token": "VOTRE_TOKEN_SSO",
    "client_domain": "academie.herime.com"
}
```

**Réponse (Token valide) :**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "Nom de l'utilisateur",
            "email": "utilisateur@example.com",
            "avatar": "https://compte.herime.com/api/user/avatar/1",
            ...
        },
        "session": {...},
        "permissions": ["profile"]
    }
}
```

**Réponse (Token révoqué/invalide) :**
```json
{
    "success": false,
    "message": "Invalid or expired token",
    "code": "TOKEN_NOT_FOUND" // Ou TOKEN_REVOKED, TOKEN_EXPIRED
}
```

## ⚙️ Configuration Recommandée (Optimisée pour Performance)

### ⚠️ IMPORTANT : Éviter la Surcharge du Serveur

Pour éviter de surcharger le serveur SSO, suivez ces recommandations :

### Intervalle de Polling (Recommandations Optimisées)

- **Production standard :** 60-120 secondes (1-2 minutes) - **RECOMMANDÉ**
- **Applications avec activité utilisateur :** 90-180 secondes (1.5-3 minutes)
- **Applications peu actives :** 180-300 secondes (3-5 minutes)
- **Développement/Test :** 30-60 secondes (uniquement pour les tests)
- **Applications critiques :** 60 secondes maximum (si vraiment nécessaire)

**⚠️ Ne jamais utiliser un intervalle inférieur à 30 secondes en production !**

### Stratégie Recommandée : Polling Intelligent

Au lieu d'un polling continu, utilisez un **polling intelligent** qui s'adapte à l'activité de l'utilisateur :

```javascript
class IntelligentSSOPolling {
    constructor(token) {
        this.token = token;
        this.pollInterval = 120000; // 2 minutes par défaut
        this.activeInterval = 60000; // 1 minute quand l'utilisateur est actif
        this.idleInterval = 300000; // 5 minutes quand l'utilisateur est inactif
        this.lastActivity = Date.now();
        this.timer = null;
    }

    start() {
        // Détecter l'activité de l'utilisateur
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => {
                this.lastActivity = Date.now();
                this.adjustPollingInterval();
            }, { passive: true });
        });

        // Vérifier immédiatement
        this.checkToken();
        
        // Démarrer le polling
        this.scheduleNextCheck();
    }

    adjustPollingInterval() {
        const timeSinceActivity = Date.now() - this.lastActivity;
        const isActive = timeSinceActivity < 300000; // 5 minutes

        // Arrêter le timer actuel
        if (this.timer) {
            clearTimeout(this.timer);
        }

        // Ajuster l'intervalle selon l'activité
        this.pollInterval = isActive ? this.activeInterval : this.idleInterval;
        
        // Programmer la prochaine vérification
        this.scheduleNextCheck();
    }

    scheduleNextCheck() {
        this.timer = setTimeout(() => {
            this.checkToken();
            this.scheduleNextCheck();
        }, this.pollInterval);
    }

    async checkToken() {
        // Votre code de vérification...
    }
}
```

### Validation Avant Actions (Alternative Efficace)

**Recommandation principale :** Utilisez la validation uniquement avant les actions importantes plutôt qu'un polling continu.

**Avantages :**
- ✅ Pas de requêtes inutiles
- ✅ Détection immédiate lors des actions
- ✅ Charge serveur minimale
- ✅ Meilleure expérience utilisateur

**Exemple :**
```javascript
// Au lieu de polling continu, valider seulement avant les actions
async function saveImportantData(data) {
    // Valider le token avant l'action
    const isValid = await validateSSOToken();
    if (!isValid) {
        handleLogout();
        return;
    }
    
    // Procéder avec l'action
    await api.save(data);
}
```

### Gestion des Erreurs

1. **Erreur réseau :** Ne pas déconnecter immédiatement, attendre la prochaine vérification
2. **Token révoqué :** Déconnecter immédiatement et rediriger vers la page de connexion
3. **Token expiré :** Déconnecter et rediriger vers la page de connexion SSO

## 🔄 Flux de Déconnexion

1. **Utilisateur se déconnecte de `compte.herime.com`**
   - Tous les tokens Passport sont révoqués (`revoked = true`)
   - Toutes les sessions sont marquées comme inactives (`is_current = false`)

2. **Site externe vérifie le token (polling ou avant action)**
   - Appel à `/api/sso/check-token` ou `/api/sso/validate-token`
   - Le serveur détecte que le token est révoqué
   - Retourne `success: false, valid: false`

3. **Site externe détecte la déconnexion**
   - Arrête le polling
   - Nettoie les données de session locale
   - Redirige vers la page de connexion ou affiche un message

## 📊 Comparaison des Méthodes

| Méthode | Réactivité | Charge Serveur | Complexité | Recommandation |
|---------|------------|----------------|------------|----------------|
| Polling périodique | Moyenne (30-60s) | Faible | Simple | ⭐⭐⭐⭐⭐ |
| Validation avant action | Immédiate | Faible | Simple | ⭐⭐⭐⭐ |
| Webhooks | Immédiate | Moyenne | Complexe | ⭐⭐⭐ |
| Server-Sent Events | Immédiate | Moyenne | Complexe | ⭐⭐ |
| WebSockets | Immédiate | Élevée | Très complexe | ⭐ |

## 🎯 Recommandation (Optimisée pour Performance)

### ⭐ Approche Recommandée : Validation Avant Actions

**Pour éviter de surcharger le serveur, utilisez principalement :**

1. **Validation avant actions importantes** (méthode principale)
   - Valider le token uniquement avant les actions critiques
   - Pas de requêtes inutiles
   - Détection immédiate
   - Charge serveur minimale

2. **Polling intelligent optionnel** (si vraiment nécessaire)
   - Intervalle de 60-120 secondes minimum
   - S'arrêter quand l'utilisateur est inactif
   - Reprendre quand l'utilisateur revient
   - Utiliser uniquement pour les applications très critiques

### 📊 Comparaison de Charge Serveur

**Scénario : 1000 utilisateurs connectés simultanément**

| Méthode | Requêtes/minute | Charge serveur | Recommandation |
|---------|----------------|----------------|----------------|
| Polling 30s | 2000 req/min | ⚠️ Élevée | ❌ Non recommandé |
| Polling 60s | 1000 req/min | ⚠️ Moyenne | ⚠️ Acceptable si nécessaire |
| Polling 120s | 500 req/min | ✅ Faible | ✅ Recommandé |
| Validation avant action | ~50-100 req/min | ✅ Très faible | ⭐⭐⭐⭐⭐ Idéal |
| Polling intelligent | ~200-400 req/min | ✅ Faible | ✅ Bon compromis |

### 🎯 Stratégie Optimale

**Pour la plupart des applications :**
```javascript
// 1. Valider le token au chargement de la page
await validateTokenOnPageLoad();

// 2. Valider avant chaque action importante
async function performAction() {
    if (!await checkTokenBeforeAction()) {
        return; // Déconnexion gérée
    }
    // Action...
}

// 3. Polling optionnel uniquement si nécessaire (120s minimum)
// Et seulement si l'utilisateur est actif
```

**Pour les applications critiques nécessitant une détection rapide :**
- Polling intelligent avec intervalle adaptatif (60-180s)
- Validation avant actions importantes
- Arrêt du polling quand l'utilisateur est inactif

## 📝 Exemple Complet d'Intégration (Optimisé)

```javascript
// sso-manager.js - Version optimisée pour performance
class SSOManager {
    constructor(options = {}) {
        this.token = this.getTokenFromStorage();
        // Intervalle par défaut : 120 secondes (2 minutes) - OPTIMISÉ
        this.pollingInterval = options.pollingInterval || 120000;
        this.enablePolling = options.enablePolling || false; // Désactivé par défaut
        this.pollTimer = null;
        this.isPageVisible = true;
    }

    init() {
        if (!this.token) {
            this.redirectToSSO();
            return;
        }

        // Valider le token initial
        this.validateToken().then(valid => {
            if (!valid) {
                this.redirectToSSO();
                return;
            }

            // Démarrer le polling seulement si activé
            if (this.enablePolling) {
                this.setupVisibilityListener();
                this.startPolling();
            }
        });
    }

    setupVisibilityListener() {
        // Arrêter le polling quand la page est en arrière-plan
        document.addEventListener('visibilitychange', () => {
            this.isPageVisible = !document.hidden;
            if (!this.isPageVisible) {
                this.stopPolling();
            } else if (this.enablePolling) {
                this.startPolling();
            }
        });
    }

    getTokenFromStorage() {
        // Récupérer le token depuis localStorage, sessionStorage, ou cookie
        return localStorage.getItem('sso_token') || 
               sessionStorage.getItem('sso_token') ||
               this.getTokenFromCookie();
    }

    async validateToken() {
        try {
            const response = await fetch('https://compte.herime.com/api/sso/check-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ token: this.token })
            });

            const data = await response.json();
            return data.success && data.valid;
        } catch (error) {
            console.error('Erreur de validation SSO:', error);
            return false;
        }
    }

    startPolling() {
        // Ne pas démarrer si la page est inactive
        if (!this.isPageVisible) {
            return;
        }

        // Arrêter le timer existant si présent
        this.stopPolling();

        this.pollTimer = setInterval(async () => {
            // Ne pas vérifier si la page est inactive
            if (!this.isPageVisible) {
                return;
            }

            const valid = await this.validateToken();
            if (!valid) {
                this.handleLogout();
            }
        }, this.pollingInterval);
    }

    stopPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    }

    async checkBeforeAction() {
        const valid = await this.validateToken();
        if (!valid) {
            this.handleLogout();
            return false;
        }
        return true;
    }

    handleLogout() {
        this.stopPolling();
        localStorage.removeItem('sso_token');
        sessionStorage.clear();
        this.redirectToSSO();
    }

    redirectToSSO() {
        const currentUrl = encodeURIComponent(window.location.href);
        window.location.href = `https://compte.herime.com/login?redirect=${currentUrl}&force_token=1`;
    }
}

// Utilisation RECOMMANDÉE (sans polling continu)
const ssoManager = new SSOManager({
    enablePolling: false // Pas de polling continu - OPTIMISÉ
});
ssoManager.init();

// Avant une action importante - VALIDATION UNIQUEMENT AVANT ACTIONS
async function saveData(data) {
    if (!await ssoManager.checkBeforeAction()) {
        return; // L'utilisateur sera redirigé
    }
    
    // Procéder avec l'action
    // ...
}

// Utilisation AVEC polling (seulement si vraiment nécessaire)
const ssoManagerWithPolling = new SSOManager({
    enablePolling: true,
    pollingInterval: 120000 // 2 minutes minimum
});
ssoManagerWithPolling.init();
```

## 🔐 Sécurité

- **HTTPS obligatoire** : Toutes les communications doivent être chiffrées
- **Validation côté serveur** : Ne jamais faire confiance uniquement au token côté client
- **Gestion des erreurs** : Ne pas exposer d'informations sensibles dans les messages d'erreur
- **Rate limiting** : Le serveur peut limiter le nombre de requêtes de vérification par IP/token

## ⚡ Optimisation et Performance

### Bonnes Pratiques pour Éviter la Surcharge

1. **Utiliser la validation avant actions plutôt que le polling continu**
   - ✅ Réduit drastiquement le nombre de requêtes
   - ✅ Détection immédiate lors des actions
   - ✅ Meilleure expérience utilisateur

2. **Si polling nécessaire, utiliser des intervalles longs**
   - ✅ Minimum 60 secondes (recommandé : 120 secondes)
   - ✅ Arrêter le polling quand la page est inactive
   - ✅ Reprendre seulement quand l'utilisateur revient

3. **Implémenter un système de backoff en cas d'erreur**
   ```javascript
   let retryDelay = 60000; // 1 minute
   const maxDelay = 300000; // 5 minutes maximum
   
   async function checkTokenWithBackoff() {
       try {
           await checkToken();
           retryDelay = 60000; // Réinitialiser en cas de succès
       } catch (error) {
           // En cas d'erreur, augmenter le délai progressivement
           retryDelay = Math.min(retryDelay * 2, maxDelay);
           setTimeout(checkTokenWithBackoff, retryDelay);
       }
   }
   ```

4. **Ne pas vérifier si la page est en arrière-plan**
   ```javascript
   if (document.hidden) {
       // Page en arrière-plan, ne pas vérifier
       return;
   }
   ```

5. **Utiliser l'endpoint `/api/sso/check-token` au lieu de `/api/sso/validate-token`**
   - ✅ Plus léger (pas de chargement des données utilisateur)
   - ✅ Plus rapide
   - ✅ Moins de charge serveur

### Calcul de Charge Serveur

**Exemple avec 1000 utilisateurs :**

| Configuration | Requêtes/heure | Impact |
|--------------|----------------|--------|
| Polling 30s | 120,000 req/h | ⚠️⚠️⚠️ Très élevé |
| Polling 60s | 60,000 req/h | ⚠️⚠️ Élevé |
| Polling 120s | 30,000 req/h | ⚠️ Modéré |
| Validation avant action | ~3,000-6,000 req/h | ✅ Faible |
| Polling intelligent (120s, arrêt si inactif) | ~10,000-15,000 req/h | ✅✅ Très faible |

**Recommandation :** Utiliser la validation avant actions pour réduire la charge de 90-95% !

## 📚 Ressources

- [Documentation Laravel Passport](https://laravel.com/docs/passport)
- [Guide d'intégration SSO](./SSO_INTEGRATION.md) (si disponible)

