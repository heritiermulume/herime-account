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

#### Implémentation JavaScript (Exemple)

```javascript
class SSOSessionManager {
    constructor(ssoToken, checkInterval = 30000) { // 30 secondes par défaut
        this.ssoToken = ssoToken;
        this.checkInterval = checkInterval;
        this.checkTimer = null;
        this.isValid = true;
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

#### Implémentation avec Vue.js (Exemple)

```javascript
// Dans votre composant Vue ou store Pinia
import { ref, onMounted, onUnmounted } from 'vue'

export function useSSOSession(token) {
    const isValid = ref(true)
    let checkTimer = null

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
            console.error('Erreur lors de la vérification du token SSO:', error)
        }
    }

    const handleLogout = () => {
        // Nettoyer et rediriger
        localStorage.removeItem('sso_token')
        window.location.href = '/login?message=session_expired'
    }

    const startPolling = (interval = 30000) => {
        checkToken() // Vérifier immédiatement
        checkTimer = setInterval(checkToken, interval)
    }

    const stopPolling = () => {
        if (checkTimer) {
            clearInterval(checkTimer)
            checkTimer = null
        }
    }

    onMounted(() => {
        if (token) {
            startPolling(30000) // Vérifier toutes les 30 secondes
        }
    })

    onUnmounted(() => {
        stopPolling()
    })

    return {
        isValid,
        checkToken,
        startPolling,
        stopPolling
    }
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

## ⚙️ Configuration Recommandée

### Intervalle de Polling

- **Développement :** 10-15 secondes (pour tester rapidement)
- **Production :** 30-60 secondes (équilibre entre réactivité et charge serveur)
- **Applications critiques :** 15-30 secondes (détection plus rapide)

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

## 🎯 Recommandation

**Utilisez une combinaison de :**
1. **Polling périodique** (30-60 secondes) pour la détection automatique
2. **Validation avant actions importantes** pour une sécurité maximale

Cette approche offre un bon équilibre entre réactivité, performance et simplicité d'implémentation.

## 📝 Exemple Complet d'Intégration

```javascript
// sso-manager.js
class SSOManager {
    constructor() {
        this.token = this.getTokenFromStorage();
        this.pollingInterval = 30000; // 30 secondes
        this.pollTimer = null;
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

            // Démarrer le polling
            this.startPolling();
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
        this.pollTimer = setInterval(async () => {
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

// Utilisation
const ssoManager = new SSOManager();
ssoManager.init();

// Avant une action importante
async function saveData(data) {
    if (!await ssoManager.checkBeforeAction()) {
        return; // L'utilisateur sera redirigé
    }
    
    // Procéder avec l'action
    // ...
}
```

## 🔐 Sécurité

- **HTTPS obligatoire** : Toutes les communications doivent être chiffrées
- **Validation côté serveur** : Ne jamais faire confiance uniquement au token côté client
- **Gestion des erreurs** : Ne pas exposer d'informations sensibles dans les messages d'erreur
- **Rate limiting** : Le serveur peut limiter le nombre de requêtes de vérification par IP/token

## 📚 Ressources

- [Documentation Laravel Passport](https://laravel.com/docs/passport)
- [Guide d'intégration SSO](./SSO_INTEGRATION.md) (si disponible)

