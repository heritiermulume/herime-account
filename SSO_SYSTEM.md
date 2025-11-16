# Système SSO - Documentation

## Vue d'ensemble

Le nouveau système SSO permet aux sites externes (comme `academie.herime.com`) de s'authentifier via le serveur central (`compte.herime.com`) et d'être automatiquement redirigés après connexion.

## Fonctionnement

### 1. Flux d'authentification

1. **Site externe** → Redirige vers `compte.herime.com/login?redirect=https://academie.herime.com/sso/callback`
2. **Utilisateur se connecte** → Le système détecte automatiquement l'URL de redirection
3. **Token SSO généré** → Un token JWT est créé via Laravel Passport
4. **Redirection automatique** → L'utilisateur est redirigé vers `https://academie.herime.com/sso/callback?token=XXX`
5. **Site externe valide le token** → Appel à `/api/sso/validate-token` avec le token

### 2. Détection automatique de session

Le système détecte automatiquement si l'utilisateur est déjà connecté :

- **Session web** : Si l'utilisateur a une session active sur `compte.herime.com`
- **Token dans l'URL** : Si un token est passé en paramètre `_token`
- **Redirection automatique** : Si une session est détectée, redirection immédiate vers le site externe

### 3. Endpoints API

#### Public

- `POST /api/sso/validate-token` - Valider un token SSO et obtenir les infos utilisateur
- `POST /api/sso/check-token` - Vérification rapide de validité (pour polling)
- `POST /api/validate-token` - Validation avec secret SSO (pour services externes)

#### Protégé (nécessite authentification)

- `POST /api/sso/generate-token` - Générer un token SSO pour redirection

### 4. Route Web

- `GET /sso/redirect?redirect=URL&_token=TOKEN` - Redirection côté serveur avec détection automatique de session

## Utilisation

### Pour les sites externes

#### 1. Rediriger vers la page de login

```javascript
// Depuis academie.herime.com
window.location.href = 'https://compte.herime.com/login?redirect=' + 
  encodeURIComponent('https://academie.herime.com/sso/callback');
```

#### 2. Valider le token reçu

```javascript
// Dans /sso/callback
const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get('token');

if (token) {
  // Valider le token
  const response = await fetch('https://compte.herime.com/api/sso/validate-token', {
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
    // Stocker la session côté client
    localStorage.setItem('sso_token', token);
    localStorage.setItem('user', JSON.stringify(user));
  }
}
```

#### 3. Vérifier la validité du token (polling)

```javascript
// Vérifier périodiquement si le token est toujours valide
const checkToken = async () => {
  const token = localStorage.getItem('sso_token');
  if (!token) return false;
  
  const response = await fetch('https://compte.herime.com/api/sso/check-token', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ token })
  });
  
  const data = await response.json();
  return data.valid === true;
};
```

### Pour le serveur central

Le système détecte automatiquement :

1. **Paramètre `redirect`** dans l'URL
2. **Paramètre `client_domain`** pour construire l'URL de callback
3. **Header `Referer`** pour détecter l'origine
4. **Header `Origin`** pour détecter l'origine

## Sécurité

- ✅ Tokens JWT signés avec Laravel Passport
- ✅ Validation stricte des URLs de redirection (pas de redirection vers le même domaine)
- ✅ Vérification de l'état actif de l'utilisateur
- ✅ Expiration automatique des tokens
- ✅ Support du secret SSO pour validation sécurisée

## Configuration

### Variables d'environnement

```env
SSO_SECRET=your-secret-key-here
```

### Clients Passport

Les sites externes doivent être enregistrés comme clients Passport :

```bash
php artisan passport:client --public --name="Herime Academy" --redirect_uri="https://academie.herime.com/sso/callback"
```

## Avantages du nouveau système

1. **Simplicité** : Code simplifié et maintenable
2. **Détection automatique** : Détecte les sessions sans intervention
3. **Redirection automatique** : Redirige immédiatement après connexion
4. **Efficacité** : Moins de requêtes, meilleures performances
5. **Fiabilité** : Gestion d'erreurs améliorée

## Migration

L'ancien système a été complètement remplacé. Les endpoints suivants ont été supprimés :

- `POST /api/sso/create-session`
- `GET /api/sso/sessions`
- `DELETE /api/sso/sessions/{sessionId}`
- `POST /api/sso/sessions/revoke-all`

Ces fonctionnalités sont maintenant gérées automatiquement par le système.

