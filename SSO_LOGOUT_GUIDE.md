# Guide de déconnexion SSO

Guide simple pour déconnecter un utilisateur depuis un site externe (ex: academie.herime.com)

---

## 🚪 Déconnexion SSO

Il existe **deux méthodes** pour déconnecter un utilisateur :

### Méthode 1 : Déconnexion locale uniquement ❌

**Déconnecte l'utilisateur SEULEMENT de votre site actuel**

```javascript
function logoutLocal() {
    // Supprimer le token et les données utilisateur
    localStorage.removeItem('sso_token');
    localStorage.removeItem('user');
    sessionStorage.clear();
    
    // Rediriger vers votre page de connexion
    window.location.href = '/login';
}
```

⚠️ **Limite** : L'utilisateur reste connecté sur :
- account.herime.com
- Tous les autres sites (store.herime.com, events.herime.com, etc.)

---

### Méthode 2 : Déconnexion globale SSO ✅ (Recommandé)

**Déconnecte l'utilisateur de TOUS les sites Herime**

```javascript
async function logoutSSO() {
    const token = localStorage.getItem('sso_token');
    
    // 1. Nettoyer immédiatement les données locales
    localStorage.removeItem('sso_token');
    localStorage.removeItem('user');
    sessionStorage.clear();
    
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
            console.error('Erreur lors de la révocation du token:', error);
        }
    }
    
    // 3. Rediriger vers la page de connexion SSO
    window.location.href = 'https://account.herime.com/login';
}
```

✅ **Avantage** : Déconnecte l'utilisateur partout, c'est plus sécurisé.

---

## 📱 Exemples d'implémentation

### HTML + JavaScript Pur

```html
<!DOCTYPE html>
<html>
<head>
    <title>Academie Herime</title>
</head>
<body>
    <button onclick="logout()">Se déconnecter</button>
    
    <script>
        async function logout() {
            const token = localStorage.getItem('sso_token');
            
            // Nettoyer local
            localStorage.removeItem('sso_token');
            localStorage.removeItem('user');
            
            // Révoquer sur le serveur
            if (token) {
                await fetch('https://account.herime.com/api/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                    }
                });
            }
            
            // Rediriger
            window.location.href = 'https://account.herime.com/login';
        }
    </script>
</body>
</html>
```

---

### Vue.js

```vue
<template>
  <button @click="logout" class="btn-logout">
    Déconnexion
  </button>
</template>

<script setup>
async function logout() {
    const token = localStorage.getItem('sso_token');
    
    // Nettoyer
    localStorage.removeItem('sso_token');
    localStorage.removeItem('user');
    
    // Révoquer
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
            console.error('Erreur déconnexion:', error);
        }
    }
    
    // Rediriger
    window.location.href = 'https://account.herime.com/login';
}
</script>
```

---

### React

```jsx
import React from 'react';

function LogoutButton() {
    const handleLogout = async () => {
        const token = localStorage.getItem('sso_token');
        
        // Nettoyer
        localStorage.removeItem('sso_token');
        localStorage.removeItem('user');
        
        // Révoquer
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
                console.error('Erreur déconnexion:', error);
            }
        }
        
        // Rediriger
        window.location.href = 'https://account.herime.com/login';
    };
    
    return (
        <button onClick={handleLogout} className="btn-logout">
            Déconnexion
        </button>
    );
}

export default LogoutButton;
```

---

### PHP (Laravel/Symfony)

```php
<?php

// Route pour la déconnexion
public function logout(Request $request)
{
    $token = $request->session()->get('sso_token');
    
    // Nettoyer la session
    $request->session()->forget('sso_token');
    $request->session()->forget('user');
    $request->session()->flush();
    
    // Révoquer le token sur le serveur SSO
    if ($token) {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post('https://account.herime.com/api/logout');
        } catch (\Exception $e) {
            \Log::error('Erreur révocation token SSO: ' . $e->getMessage());
        }
    }
    
    // Rediriger
    return redirect('https://account.herime.com/login');
}
```

---

### Python (Django/Flask)

```python
import requests
from django.shortcuts import redirect
from django.http import HttpResponse

def logout(request):
    token = request.session.get('sso_token')
    
    # Nettoyer la session
    request.session.flush()
    
    # Révoquer le token sur le serveur SSO
    if token:
        try:
            requests.post(
                'https://account.herime.com/api/logout',
                headers={
                    'Authorization': f'Bearer {token}',
                    'Content-Type': 'application/json',
                }
            )
        except Exception as e:
            print(f'Erreur révocation token SSO: {e}')
    
    # Rediriger
    return redirect('https://account.herime.com/login')
```

---

## 🔍 Ce qui se passe côté serveur SSO

Lorsque vous appelez `/api/logout`, le serveur SSO :

1. ✅ **Révoque le token Passport** (le rend invalide)
2. ✅ **Marque toutes les sessions comme inactives** (`is_current = false`)
3. ✅ **Déconnecte l'utilisateur** de tous les sites

**Résultat** : L'utilisateur doit se reconnecter sur tous les sites Herime.

---

## 🧪 Test de la déconnexion

### Avec cURL

```bash
curl -X POST https://account.herime.com/api/logout \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI" \
  -H "Content-Type: application/json"
```

**Réponse attendue :**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

---

## ⚠️ Gestion des erreurs

```javascript
async function logout() {
    const token = localStorage.getItem('sso_token');
    
    // Toujours nettoyer les données locales d'abord
    localStorage.removeItem('sso_token');
    localStorage.removeItem('user');
    
    // Essayer de révoquer le token (non bloquant)
    if (token) {
        try {
            const response = await fetch('https://account.herime.com/api/logout', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
                // Timeout de 5 secondes
                signal: AbortSignal.timeout(5000)
            });
            
            if (!response.ok) {
                console.warn('Token déjà révoqué ou invalide');
            }
        } catch (error) {
            // Ne pas bloquer la déconnexion si le serveur ne répond pas
            console.error('Erreur révocation token (non bloquant):', error);
        }
    }
    
    // TOUJOURS rediriger, même si la révocation échoue
    window.location.href = 'https://account.herime.com/login';
}
```

---

## 📋 Checklist de déconnexion

- [ ] Nettoyer `localStorage.removeItem('sso_token')`
- [ ] Nettoyer `localStorage.removeItem('user')`
- [ ] Nettoyer `sessionStorage.clear()`
- [ ] Appeler `/api/logout` avec le token en Authorization header
- [ ] Rediriger vers `https://account.herime.com/login`
- [ ] Gérer les erreurs sans bloquer la déconnexion
- [ ] Tester que l'utilisateur est bien déconnecté partout

---

## 🔐 Sécurité

### ✅ Bonnes pratiques

1. **Toujours nettoyer les données locales EN PREMIER** (avant l'appel API)
2. **Ne pas bloquer la déconnexion** si l'API ne répond pas
3. **Toujours rediriger** après la déconnexion
4. **Utiliser HTTPS** en production

### ❌ À éviter

1. Ne jamais garder le token après déconnexion
2. Ne jamais bloquer la déconnexion par une erreur API
3. Ne jamais laisser l'utilisateur "semi-déconnecté"

---

## 📞 Support

Si la déconnexion ne fonctionne pas :

1. Vérifier que le token est bien envoyé dans le header `Authorization`
2. Vérifier la console pour les erreurs
3. Vérifier les logs du serveur SSO : `storage/logs/laravel.log`
4. S'assurer que HTTPS est activé

---

**C'est tout !** La déconnexion SSO en 3 étapes simples : Nettoyer → Révoquer → Rediriger

