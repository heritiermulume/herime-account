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
    const session = data.data.session;
    
    // Stocker la session côté client
    localStorage.setItem('sso_token', token);
    localStorage.setItem('user', JSON.stringify(user));
    
    // Vérifier l'état de la session
    if (session.active) {
      console.log('Session active, dernière activité:', session.last_activity);
    } else {
      console.log('Session inactive, rediriger vers login');
      // Rediriger vers la page de login si la session n'est pas active
      window.location.href = 'https://compte.herime.com/login?redirect=' + 
        encodeURIComponent(window.location.origin + '/sso/callback');
    }
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
  
  // Vérifier à la fois la validité du token et l'état de la session
  if (!data.valid || !data.session_active) {
    // Token invalide ou session inactive, déconnecter l'utilisateur
    localStorage.removeItem('sso_token');
    localStorage.removeItem('user');
    window.location.href = 'https://compte.herime.com/login?redirect=' + 
      encodeURIComponent(window.location.origin + '/sso/callback');
    return false;
  }
  
  return true;
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

---

## Guide d’intégration pour sites externes (SSO Herime)

Cette section décrit comment un site externe (ex: `academie.herime.com`) doit intégrer le SSO avec `compte.herime.com`.

Objectif: lorsqu’un utilisateur non connecté visite le site externe, il est redirigé vers `compte.herime.com` pour s’authentifier, puis revient automatiquement sur le site externe avec un jeton SSO afin d’ouvrir une session locale.

### 1) URL SSO à utiliser

Quand l’utilisateur n’est pas connecté sur votre site externe, redirigez-le vers:

```
https://compte.herime.com/login?redirect=ENCODED_CALLBACK&force_token=1
```

où:
- `ENCODED_CALLBACK` est l’URL encodée de votre route `/sso/callback` qui, elle-même, reçoit un paramètre `redirect` pointant vers la page finale désirée (souvent votre page d’accueil ou la page initialement demandée).

Exemple concret (externes = `academie.herime.com`):

```
https://compte.herime.com/login
  ?redirect=https%3A%2F%2Facademie.herime.com%2Fsso%2Fcallback%3Fredirect%3Dhttps%253A%252F%252Facademie.herime.com%252F
  &force_token=1
```

Décomposition:
- redirect (niveau 1): `https://academie.herime.com/sso/callback?redirect=https%3A%2F%2Facademie.herime.com%2F` (entièrement encodé)
- force_token=1: impose la génération d’un token SSO et le retour immédiat vers votre callback.

### 2) Responsabilités de votre route `/sso/callback`

Votre endpoint `/sso/callback` doit:
1. Lire `token` (ajouté par `compte.herime.com`) et `redirect` (votre destination finale).
2. Si `token` est absent: renvoyer l’utilisateur vers `compte.herime.com/login?force_token=1&redirect=...` (auto-rattrapage).
3. Valider le `token` en appelant l’API de `compte.herime.com` avec un `Authorization: Bearer <token>`:
   - Recommandé: un endpoint de validation dédié (ex: `POST /api/sso/validateToken`) si disponible.
   - À défaut: `GET /api/user` (ou `/api/me`) qui retourne l’utilisateur lié au token OAuth2 (Passport).
4. Trouver/créer l’utilisateur local sur votre site (via email/ID), mettre à jour les champs utiles.
5. Ouvrir une session locale (login serveur).
6. Rediriger vers `redirect` (ou `/` si absent) — important: doit rester sur VOTRE domaine.

Sécurité à respecter:
- Toujours valider que `redirect` pointe vers votre propre domaine (anti open-redirect).
- Utiliser HTTPS partout.
- Ne jamais exposer le token SSO côté client; il ne sert qu’à établir la session serveur.

### 3) Implémentation Laravel (externe)

routes/web.php:

```php
use App\Http\Controllers\SSOCallbackController;

Route::get('/sso/callback', [SSOCallbackController::class, 'handle'])->name('sso.callback');
```

app/Http/Controllers/SSOCallbackController.php:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\User;

class SSOCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $token = $request->query('token');
        $finalRedirect = $request->query('redirect', url('/'));

        // 1) Si pas de token: renvoyer vers compte.herime.com pour (re)générer un token SSO
        if (!$token) {
            $callback = route('sso.callback', ['redirect' => $finalRedirect]);
            $loginUrl = 'https://compte.herime.com/login?force_token=1&redirect=' . urlencode($callback);
            return redirect()->away($loginUrl);
        }

        // 2) Valider le token avec POST /api/sso/validate-token (recommandé)
        // Cette méthode retourne les infos utilisateur ET l'état de la session
        $resp = Http::acceptJson()
            ->post('https://compte.herime.com/api/sso/validate-token', [
                'token' => $token
            ]);

        if (!$resp->ok()) {
            // Token invalide/expiré → repartir vers SSO
            $callback = route('sso.callback', ['redirect' => $finalRedirect]);
            $loginUrl = 'https://compte.herime.com/login?force_token=1&redirect=' . urlencode($callback);
            return redirect()->away($loginUrl);
        }

        $data = $resp->json();
        
        // Vérifier si la réponse est un succès
        if (!data_get($data, 'success')) {
            $callback = route('sso.callback', ['redirect' => $finalRedirect]);
            $loginUrl = 'https://compte.herime.com/login?force_token=1&redirect=' . urlencode($callback);
            return redirect()->away($loginUrl);
        }
        
        // Vérifier l'état de la session
        $sessionActive = data_get($data, 'data.session.active', false);
        if (!$sessionActive) {
            // Session inactive (utilisateur déconnecté) → redemander connexion
            \Log::warning('SSO: Session inactive pour l\'utilisateur', [
                'email' => data_get($data, 'data.user.email'),
            ]);
            $callback = route('sso.callback', ['redirect' => $finalRedirect]);
            $loginUrl = 'https://compte.herime.com/login?force_token=1&redirect=' . urlencode($callback);
            return redirect()->away($loginUrl);
        }

        $remoteUser = data_get($data, 'data.user');
        $remoteId   = data_get($remoteUser, 'id');
        $email      = data_get($remoteUser, 'email');
        $name       = data_get($remoteUser, 'name');

        if (!$email) {
            abort(403, 'SSO: email manquant');
        }

        // 3) Trouver/Créer l’utilisateur local
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name'        => $name ?: $email,
                'email'       => $email,
                'password'    => bcrypt(Str::random(32)),
                'provider'    => 'herime-account',
                'provider_id' => $remoteId,
            ]);
        } else {
            $user->forceFill([
                'name'        => $name ?: $user->name,
                'provider'    => 'herime-account',
                'provider_id' => $remoteId ?? $user->provider_id,
            ])->save();
        }

        // 4) Connecter localement
        Auth::login($user, true);

        // 5) Redirection finale (sécuriser pour rester sur votre domaine)
        return redirect()->to($this->safeRedirect($finalRedirect));
    }

    private function safeRedirect(string $url): string
    {
        // Autoriser uniquement les URLs vers votre domaine
        // Ex: academie.herime.com, sous-domaines éventuels, ou chemins relatifs
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return url('/'); // relatif → OK
        }
        $host = strtolower(preg_replace('/^www\./', '', $parsed['host']));
        if ($host === 'academie.herime.com') {
            return $url;
        }
        return url('/'); // fallback sûr
    }
}
```

Middleware/contrôleur externe pour déclencher le SSO quand non connecté:

```php
if (!auth()->check()) {
    $desired  = url()->full(); // où l’on voulait aller
    $callback = route('sso.callback', ['redirect' => $desired]);
    $ssoUrl   = 'https://compte.herime.com/login?force_token=1&redirect=' . urlencode($callback);
    return redirect()->away($ssoUrl);
}
```

### 4) Implémentation Node/Express (externe)

Routes (Express):

```js
const express = require('express');
const axios = require('axios');
const router = express.Router();

// Helper: construit l’URL SSO de redirection vers compte.herime.com
function buildSSOLoginUrl(callbackUrl) {
  return `https://compte.herime.com/login?force_token=1&redirect=${encodeURIComponent(callbackUrl)}`;
}

// Helper: sécurité open-redirect (adapter le domaine autorisé)
function isAllowedRedirect(url) {
  try {
    const u = new URL(url, 'https://academie.herime.com');
    return u.hostname === 'academie.herime.com';
  } catch {
    return false;
  }
}

router.get('/sso/callback', async (req, res) => {
  const token = req.query.token;
  const finalRedirect = req.query.redirect || '/';

  if (!token) {
    const callback = `https://academie.herime.com/sso/callback?redirect=${encodeURIComponent(finalRedirect)}`;
    return res.redirect(buildSSOLoginUrl(callback));
  }

  try {
    // Valider le token avec POST /api/sso/validate-token (recommandé)
    const resp = await axios.post('https://compte.herime.com/api/sso/validate-token', 
      { token },
      { headers: { Accept: 'application/json' } }
    );

    const data = resp.data;
    
    // Vérifier le succès et l'état de la session
    if (!data.success || !data.data.session.active) {
      console.warn('SSO: Token invalide ou session inactive');
      const callback = `https://academie.herime.com/sso/callback?redirect=${encodeURIComponent(finalRedirect)}`;
      return res.redirect(buildSSOLoginUrl(callback));
    }

    const remoteUser = data.data.user;
    const email = remoteUser?.email;
    const name  = remoteUser?.name || `${remoteUser?.first_name || ''} ${remoteUser?.last_name || ''}`.trim();
    if (!email) return res.status(403).send('SSO: email manquant');

    // TODO: trouver/créer l’utilisateur local et ouvrir la session
    // const user = await upsertUser({ email, name, provider: 'herime-account', providerId: remoteUser.id })
    // req.session.userId = user.id

    const safeUrl = isAllowedRedirect(finalRedirect) ? finalRedirect : '/';
    return res.redirect(safeUrl);
  } catch (e) {
    const callback = `https://academie.herime.com/sso/callback?redirect=${encodeURIComponent(finalRedirect)}`;
    return res.redirect(buildSSOLoginUrl(callback));
  }
});

module.exports = router;
```

### 5) Points de contrôle et dépannage

- Vous voyez « Redirecting to https://academie.herime.com/sso/callback?... » dans la console de `compte.herime.com`, mais vous revenez quand même à `compte.herime.com/dashboard`:
  - Cela indique que la redirection depuis le site externe vous renvoie (directement ou indirectement) vers `compte.herime.com`. Vérifiez votre `/sso/callback` et vos middlewares: la redirection finale doit rester sur votre domaine externe.
- Votre `/sso/callback` reçoit souvent aucun `token`:
  - Reprenez le flux: renvoyez l’utilisateur vers `compte.herime.com/login?force_token=1&redirect=<votre callback encodé>`.
- Le paramètre `redirect` est ignoré:
  - Assurez-vous de bien l’encoder à chaque niveau et de vérifier sa sûreté (domaine autorisé) avant la redirection finale.

### 6) État de session (session.active)

Depuis la mise à jour du système SSO, tous les endpoints de validation retournent l'état de la session :

#### Structure de la réponse

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

#### Comportement de `session.active`

- **`true`** : L'utilisateur a une session active sur `compte.herime.com`
  - Soit une session normale (connexion directe)
  - Soit une session SSO pour votre domaine externe
  - L'utilisateur peut accéder à vos ressources protégées

- **`false`** : L'utilisateur s'est déconnecté
  - Tous les tokens ont été révoqués
  - Toutes les sessions marquées comme inactives
  - Le site externe doit redemander une connexion

#### Cas d'usage

**1. Lors de la validation initiale du token**

```php
$data = $resp->json();

if (!data_get($data, 'success') || !data_get($data, 'data.session.active')) {
    // Redemander la connexion
    return redirect()->away('https://compte.herime.com/login?force_token=1&redirect=...');
}
```

**2. Lors du polling périodique**

```javascript
// Vérifier toutes les 5 minutes
setInterval(async () => {
  const response = await fetch('https://compte.herime.com/api/sso/check-token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ token: localStorage.getItem('sso_token') })
  });
  
  const data = await response.json();
  
  if (!data.valid || !data.session_active) {
    // Déconnecter l'utilisateur et redemander connexion
    logout();
    window.location.href = 'https://compte.herime.com/login?redirect=...';
  }
}, 5 * 60 * 1000);
```

**3. Déconnexion centralisée**

Quand un utilisateur se déconnecte de `compte.herime.com` :
1. Tous ses tokens Passport sont révoqués (`revoked = true`)
2. Toutes ses sessions sont marquées inactives (`is_current = false`)
3. Les sites externes détectent `session.active = false` lors du prochain polling
4. Les sites externes déconnectent automatiquement l'utilisateur

#### Avantages

- ✅ **Déconnexion centralisée** : Se déconnecter de `compte.herime.com` déconnecte de tous les sites
- ✅ **Sécurité renforcée** : Les tokens révoqués sont immédiatement détectés
- ✅ **Synchronisation** : Tous les sites externes restent synchronisés avec l'état de session central
- ✅ **Audit** : L'historique des sessions est préservé pour l'audit

### 7) Résumé (checklist)

- Construire l'URL SSO: `https://compte.herime.com/login?force_token=1&redirect=<ENCODED_CALLBACK>`
- `ENCODED_CALLBACK` = `https://votre-domaine/sso/callback?redirect=<ENCODED_FINAL_URL>`
- Implémenter `/sso/callback`:
  - Si pas de `token` → repartir vers login SSO (avec `redirect=callback`).
  - Sinon, valider le token via `POST /api/sso/validate-token`.
  - **Vérifier `data.success` ET `data.data.session.active`**.
  - Trouver/créer l'utilisateur local et ouvrir la session.
  - Rediriger vers `redirect` (vérifié et sur votre domaine).
- Implémenter un polling périodique pour vérifier `session.active`.

