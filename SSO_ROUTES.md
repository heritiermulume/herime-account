# Routes SSO - Référence rapide

## 📋 Routes API disponibles

### Routes publiques (sans authentification)

#### 1. Valider un token SSO
```
POST /api/sso/validate-token
```

**Body** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Réponse (succès)** :
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "John Doe",
      "email": "john@example.com",
      "avatar": "https://compte.herime.com/storage/avatars/...",
      "phone": "+243...",
      "company": "Herime",
      "position": "Developer",
      "last_login_at": "2025-11-16T10:30:00.000000Z"
    },
    "permissions": ["profile"],
    "session": {
      "active": true,
      "last_activity": "2025-11-16T10:35:00.000000Z"
    }
  }
}
```

**Réponse (token révoqué)** :
```json
{
  "success": false,
  "message": "Token révoqué (utilisateur déconnecté)",
  "session_active": false
}
```

#### 2. Vérifier rapidement un token (polling)
```
POST /api/sso/check-token
```

**Body** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Réponse** :
```json
{
  "valid": true,
  "user_id": 2,
  "session_active": true
}
```

#### 3. Valider un token avec secret SSO (pour services externes)
```
POST /api/validate-token
Authorization: Bearer <SSO_SECRET>
```

**Body** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Réponse** :
```json
{
  "valid": true,
  "user": {
    "id": 2,
    "email": "john@example.com",
    "name": "John Doe",
    "avatar": "https://compte.herime.com/storage/avatars/...",
    "role": "user",
    "is_verified": true,
    "is_active": true
  },
  "session": {
    "active": true,
    "last_activity": "2025-11-16T10:35:00.000000Z"
  }
}
```

### Routes protégées (nécessitent authentification)

#### 4. Générer un token SSO
```
POST /api/sso/generate-token
Authorization: Bearer <access_token>
```

**Body** :
```json
{
  "redirect": "https://academie.herime.com/sso/callback"
}
```

**Réponse** :
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "redirect_url": "https://academie.herime.com/sso/callback",
    "callback_url": "https://academie.herime.com/sso/callback?redirect=https%3A%2F%2Facademie.herime.com&token=eyJ0eXAi..."
  }
}
```

## 🌐 Routes Web

### 1. Page de login avec SSO
```
GET /login?force_token=1&redirect=<URL_CALLBACK>
```

**Paramètres** :
- `force_token=1` : Force la génération d'un token SSO
- `redirect=<URL>` : URL de callback du site externe (encodée)

**Exemple** :
```
https://compte.herime.com/login?force_token=1&redirect=https%3A%2F%2Facademie.herime.com%2Fsso%2Fcallback%3Fredirect%3Dhttps%253A%252F%252Facademie.herime.com
```

**Comportement** :
- Si utilisateur connecté : Génération token SSO + redirection automatique
- Si utilisateur non connecté : Affichage du formulaire de login

### 2. Redirection SSO (legacy)
```
GET /sso/redirect?redirect=<URL>&_token=<TOKEN>
```

**Note** : Cette route est maintenant gérée par `/login` avec `force_token=1`.

## 📝 Conventions de nommage

### Routes API

**Format** : `kebab-case` (avec tirets)

✅ **Correct** :
- `/api/sso/validate-token`
- `/api/sso/check-token`
- `/api/sso/generate-token`

❌ **Incorrect** :
- `/api/sso/validateToken` (camelCase)
- `/api/sso/checkToken` (camelCase)
- `/api/sso/generateToken` (camelCase)

### Méthodes de contrôleur

**Format** : `camelCase`

✅ **Correct** :
- `SSOController@validateToken`
- `SSOController@checkToken`
- `SSOController@generateToken`

## 🧪 Tests des routes

### Test 1 : Valider un token

```bash
curl -X POST https://compte.herime.com/api/sso/validate-token \
  -H "Content-Type: application/json" \
  -d '{"token": "eyJ0eXAi..."}'
```

### Test 2 : Vérifier un token

```bash
curl -X POST https://compte.herime.com/api/sso/check-token \
  -H "Content-Type: application/json" \
  -d '{"token": "eyJ0eXAi..."}'
```

### Test 3 : Générer un token (nécessite authentification)

```bash
curl -X POST https://compte.herime.com/api/sso/generate-token \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <access_token>" \
  -d '{"redirect": "https://academie.herime.com/sso/callback"}'
```

## 🔍 Vérifier les routes disponibles

Sur le serveur :

```bash
php artisan route:list | grep sso
```

**Résultat attendu** :
```
POST   api/sso/validate-token ................ SSOController@validateToken
POST   api/sso/check-token ................... SSOController@checkToken
POST   api/sso/generate-token ................ SSOController@generateToken (auth:api)
POST   api/validate-token .................... SSOController@validateTokenWithSecret
GET    login ................................. LoginController@show
```

## 🐛 Erreurs courantes

### Erreur 404 : Route not found

**Message** :
```
The route api/sso/generateToken could not be found.
```

**Cause** : Mauvais format d'URL (camelCase au lieu de kebab-case)

**Solution** : Utiliser `/api/sso/generate-token` (avec tirets)

### Erreur 401 : Unauthenticated

**Message** :
```
Unauthenticated.
```

**Cause** : Token manquant ou invalide pour une route protégée

**Solution** : Ajouter le header `Authorization: Bearer <token>`

### Erreur 422 : Validation failed

**Message** :
```
Token requis
```

**Cause** : Paramètre `token` manquant dans le body

**Solution** : Ajouter `{"token": "..."}` dans le body

## 📚 Documentation complète

- **`SSO_SYSTEM.md`** : Documentation complète du système SSO
- **`DIAGNOSTIC_SSO.md`** : Guide de diagnostic des problèmes
- **`DEPLOYMENT.md`** : Guide de déploiement en production
- **`SSO_ROUTES.md`** : Référence rapide des routes (ce fichier)

---

**Dernière mise à jour** : 16 novembre 2025

