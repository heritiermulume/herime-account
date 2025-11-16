# Guide de Diagnostic SSO

## 🔍 Problème : "ça ne redirige toujours pas"

### Étape 1 : Vérifier les logs de la console

Ouvrez la console du navigateur (F12 → Console) et accédez à l'URL SSO :

```
https://compte.herime.com/login?force_token=1&redirect=https%3A%2F%2Facademie.herime.com%2Fsso%2Fcallback%3Fredirect%3Dhttps%253A%252F%252Facademie.herime.com
```

**Logs attendus** :

```
[BLADE] Template loaded, URL: https://compte.herime.com/login?force_token=1&redirect=...
[BLADE] Has sso_redirect: false (ou true)
[BLADE] localStorage.access_token: EXISTS (ou NOT_FOUND)
[BLADE] sessionStorage.sso_redirecting: null (ou true)
[BLADE] force_token and redirect detected, checking for SSO redirect
[BLADE] forceToken: 1
[BLADE] redirect: https://academie.herime.com/sso/callback?redirect=...
```

### Étape 2 : Identifier le scénario

#### Scénario A : `Has sso_redirect: true`

**Logs** :
```
[BLADE] Has sso_redirect: true
[BLADE] SSO redirect: Redirecting to https://academie.herime.com/...
[BLADE] Executing immediate redirect to: ...
```

**Signification** : Le serveur a généré la redirection SSO, l'utilisateur est authentifié.

**Problème possible** : La redirection JavaScript est bloquée.

**Solution** :
1. Vérifier qu'il n'y a pas d'erreur JavaScript qui bloque
2. Vérifier que le site externe (academie.herime.com) est accessible
3. Vérifier les logs serveur : `tail -f storage/logs/laravel.log | grep "SSO Redirect"`

#### Scénario B : `Has sso_redirect: false` + `localStorage.access_token: EXISTS`

**Logs** :
```
[BLADE] Has sso_redirect: false
[BLADE] localStorage.access_token: EXISTS
[BLADE] Checking localStorage for token: FOUND
[BLADE] User has token, requesting SSO redirect
[BLADE] API error: 401 {...}
```

**Signification** : L'utilisateur a un token dans localStorage mais pas de session Laravel.

**Problème possible** : Token révoqué ou expiré.

**Solution** :
1. Vider le localStorage : `localStorage.clear()`
2. Recharger la page : `Ctrl+Shift+R`
3. Se reconnecter

#### Scénario C : `Has sso_redirect: false` + `localStorage.access_token: NOT_FOUND`

**Logs** :
```
[BLADE] Has sso_redirect: false
[BLADE] localStorage.access_token: NOT_FOUND
[BLADE] Checking localStorage for token: NOT_FOUND
[BLADE] No token found in localStorage, user needs to login
```

**Signification** : L'utilisateur n'est pas connecté.

**Comportement attendu** : Le formulaire de login devrait s'afficher.

**Problème possible** : Vue.js ne se charge pas.

**Solution** :
1. Vérifier que les assets sont chargés (F12 → Network → app-*.js)
2. Vérifier qu'il n'y a pas d'erreur JavaScript
3. Attendre 10 secondes pour voir le message d'erreur de chargement

### Étape 3 : Vérifier le source HTML

Clic droit → Afficher le code source, cherchez :

```html
<!-- SSO_REDIRECT: SET ou NOT_SET -->
<!-- SSO_REDIRECT_VALUE: URL ou NONE -->
<!-- URL: https://compte.herime.com/login?force_token=1&redirect=... -->
```

**Si `SSO_REDIRECT: SET`** :
- Le serveur a détecté l'utilisateur comme authentifié
- La redirection devrait se faire automatiquement
- Vérifier que le JavaScript n'est pas bloqué

**Si `SSO_REDIRECT: NOT_SET`** :
- Le serveur n'a pas détecté l'utilisateur comme authentifié
- Le formulaire de login devrait s'afficher
- Vérifier que Vue.js se charge

### Étape 4 : Vérifier les logs Laravel

Sur le serveur :

```bash
tail -f storage/logs/laravel.log | grep -E "LoginController|SSO"
```

**Logs attendus pour utilisateur connecté** :
```
[LoginController] show method called: is_authenticated=true
[LoginController] User authenticated with force_token: user_id=2
[SSO Redirect] Redirecting to external site: callback_url=...
```

**Logs attendus pour utilisateur non connecté** :
```
[LoginController] show method called: is_authenticated=false
[LoginController] No token found in any location
```

### Étape 5 : Tests de diagnostic

#### Test 1 : Vérifier l'authentification

```javascript
// Dans la console du navigateur
fetch('/api/user', {
    headers: {
        'Authorization': 'Bearer ' + localStorage.getItem('access_token'),
        'Accept': 'application/json'
    }
})
.then(r => r.json())
.then(data => console.log('User:', data))
.catch(e => console.error('Error:', e));
```

**Résultat attendu** :
- Si connecté : `{success: true, data: {user: {...}}}`
- Si non connecté : `401 Unauthorized`

#### Test 2 : Vérifier la génération de token SSO

```javascript
// Dans la console du navigateur (si vous avez un token)
fetch('/api/sso/generateToken', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + localStorage.getItem('access_token'),
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        redirect: 'https://academie.herime.com/sso/callback'
    })
})
.then(r => r.json())
.then(data => console.log('SSO Token:', data))
.catch(e => console.error('Error:', e));
```

**Résultat attendu** :
- Si token valide : `{success: true, data: {token: '...', callback_url: '...'}}`
- Si token révoqué : `401 Unauthorized`

### Étape 6 : Solutions par problème

#### Problème : "Rien ne se passe"

**Causes possibles** :
1. JavaScript bloqué par le navigateur
2. Assets non chargés (erreur 404)
3. Erreur JavaScript qui bloque tout

**Solutions** :
1. Ouvrir la console (F12) et chercher les erreurs en rouge
2. Vérifier l'onglet Network pour voir si les assets se chargent
3. Vider le cache : `Ctrl+Shift+R`

#### Problème : "Formulaire de login ne s'affiche pas"

**Causes possibles** :
1. Vue.js ne se charge pas
2. Erreur JavaScript
3. Assets non compilés

**Solutions** :
1. Vérifier la console pour les erreurs
2. Attendre 10 secondes pour voir le message d'erreur
3. Sur le serveur : `npm run build`

#### Problème : "Redirection vers /dashboard au lieu du site externe"

**Causes possibles** :
1. Protection contre les boucles activée (3 tentatives)
2. URL de redirection invalide
3. Domaine de redirection = même domaine

**Solutions** :
1. Vérifier les logs : "Too many redirect attempts"
2. Vérifier que l'URL redirect pointe vers un domaine externe
3. Attendre 5 minutes et réessayer

#### Problème : "Boucle infinie"

**Causes possibles** :
1. Token révoqué dans localStorage
2. Session expirée mais token présent
3. Protection contre les boucles pas déclenchée

**Solutions** :
1. Vider le localStorage : `localStorage.clear()`
2. Vider le sessionStorage : `sessionStorage.clear()`
3. Recharger : `Ctrl+Shift+R`

### Étape 7 : Checklist complète

- [ ] Console ouverte (F12)
- [ ] Logs visibles dans la console
- [ ] `Has sso_redirect` vérifié
- [ ] `localStorage.access_token` vérifié
- [ ] Source HTML vérifié (SSO_REDIRECT)
- [ ] Logs Laravel vérifiés
- [ ] Tests de diagnostic exécutés
- [ ] Cache navigateur vidé
- [ ] Assets compilés sur le serveur

### Étape 8 : Informations à fournir pour le support

Si le problème persiste, fournir :

1. **Logs de la console** (copier-coller complet)
2. **Source HTML** (les commentaires de debug)
3. **Logs Laravel** (dernières 50 lignes avec LoginController)
4. **État du localStorage** : `console.log({...localStorage})`
5. **État du sessionStorage** : `console.log({...sessionStorage})`
6. **URL exacte** utilisée
7. **Comportement observé** vs comportement attendu

---

**Dernière mise à jour** : 16 novembre 2025

