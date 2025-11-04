# Analyse de Sécurité et Performance - Compte Herime

## 📋 Vue d'ensemble

Cette analyse identifie les failles de sécurité potentielles et les opportunités d'optimisation des performances sans modifier la logique métier.

---

## 🔒 SÉCURITÉ

### ✅ Points Positifs

1. **Hachage des mots de passe** : Utilisation correcte de `Hash::make()` et cast `'hashed'` dans le modèle User
2. **Validation des entrées** : Validators Laravel utilisés partout
3. **ORM Eloquent** : Protection contre SQL injection par paramètres liés
4. **Tokens OAuth2** : Passport correctement configuré
5. **2FA** : Implémentation Fortify correcte
6. **CORS** : Configuration présente avec `supports_credentials: true`

### ⚠️ Failles et Recommandations

#### 1. **Validation des Uploads d'Images** (CRITIQUE)
**Fichier**: `app/Http/Controllers/Api/UserController.php:70`

**Problème**: 
- Validation `mimes:jpeg,png,jpg,gif,webp` peut être contournée en renommant un fichier malveillant
- Pas de vérification réelle du contenu MIME du fichier

**Recommandation**:
```php
// Ajouter validation du vrai MIME type
$mimeType = $file->getMimeType();
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mimeType, $allowedMimes)) {
    return response()->json(['error' => 'Type de fichier non autorisé'], 422);
}

// Vérifier le contenu réel avec getimagesize()
$imageInfo = @getimagesize($file->getRealPath());
if ($imageInfo === false) {
    return response()->json(['error' => 'Fichier image invalide'], 422);
}
```

#### 2. **Autorisation Manquante sur AvatarController** (MOYEN)
**Fichier**: `app/Http/Controllers/Api/AvatarController.php:18-36`

**Problème**: 
- Les avatars sont accessibles sans authentification stricte
- Un utilisateur peut deviner les IDs et accéder aux avatars d'autres utilisateurs

**Recommandation**:
```php
// Vérifier que l'utilisateur authentifié demande son propre avatar ou est admin
if (!$authenticatedUser || ($authenticatedUser->id != $userId && !$authenticatedUser->isAdmin())) {
    return response()->json(['error' => 'Unauthorized'], 403);
}
```

#### 3. **Rate Limiting Manquant** (MOYEN)
**Fichier**: `routes/api.php`

**Problème**: 
- Pas de rate limiting sur les routes de login, register, password reset
- Risque d'attaque brute force

**Recommandation**:
```php
// Dans routes/api.php
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('login', [SimpleAuthController::class, 'login']);
    Route::post('register', [SimpleAuthController::class, 'register']);
    Route::post('password/forgot', [PasswordResetController::class, 'sendResetLink']);
});

Route::middleware(['throttle:3,1'])->group(function () {
    Route::post('password/reset', [PasswordResetController::class, 'reset']);
});
```

#### 4. **Logs avec Données Sensibles** (FAIBLE - DÉJÀ CORRIGÉ)
**Statut**: ✅ Déjà corrigé dans les commits précédents
- Logs conditionnés par `config('app.debug')`
- Console désactivée en production

#### 5. **DB::raw() Utilisation** (FAIBLE)
**Fichier**: `app/Http/Controllers/Api/AdminController.php:40-41`

**Statut**: ✅ Sécurisé
- Pas de concaténation de user input
- Utilisation de paramètres Laravel

#### 6. **Validation Website URL** (FAIBLE)
**Fichier**: `app/Http/Controllers/Api/UserController.php:69`

**Problème**: Validation `url` peut accepter `javascript:` ou `data:` URLs

**Recommandation**:
```php
'website' => 'sometimes|nullable|url|max:255|regex:/^https?:\/\//',
```

#### 7. **CORS Configuration Large** (FAIBLE)
**Fichier**: `config/cors.php:22`

**Problème**: `'allowed_origins' => ['*']` permet toutes les origines

**Recommandation** (si applicable):
```php
'allowed_origins' => [
    'https://account.herime.com',
    'https://academie.herime.com',
    // ... autres sous-domaines
],
```

---

## ⚡ PERFORMANCES

### ✅ Points Positifs

1. **Pagination** : Implémentée sur les listes (users, sessions)
2. **Indexes DB** : Probablement présents sur les clés étrangères
3. **Compression Images** : Service dédié avec compression intelligente
4. **Eager Loading** : Utilisation de `->load()` et `->with()` dans certains endroits

### ⚠️ Optimisations Recommandées

#### 1. **N+1 Queries dans AdminController** (IMPORTANT)
**Fichier**: `app/Http/Controllers/Api/AdminController.php:159-175`

**Problème**: 
- `$user->avatar_url` déclenche un accessor qui peut faire des requêtes
- Pas de eager loading pour les relations

**Recommandation**:
```php
// Dans users() method
$users = $query->with(['sessions' => function($q) {
    $q->where('is_current', true)->latest()->limit(1);
}])->orderBy('created_at', 'desc')->paginate($perPage);
```

#### 2. **Cache pour SystemSettings** (IMPORTANT)
**Fichier**: `app/Models/SystemSetting.php`

**Problème**: 
- Chaque requête lit directement depuis la DB
- Les settings changent rarement

**Recommandation**:
```php
// Dans SystemSetting model
public static function get(string $key, $default = null): ?string
{
    return Cache::remember("system_setting:{$key}", 3600, function () use ($key, $default) {
        return static::where('key', $key)->value('value') ?? $default;
    });
}

public static function set(string $key, string $value): void
{
    static::updateOrCreate(['key' => $key], ['value' => $value]);
    Cache::forget("system_setting:{$key}");
    Cache::forget('system_settings:all');
}
```

#### 3. **Cache pour User Avatar URL** (MOYEN)
**Fichier**: `app/Models/User.php` (accessor `avatar_url`)

**Problème**: 
- Calcul de l'URL à chaque accès
- Peut être mis en cache

**Recommandation**:
```php
// Dans User model, ajouter un attribut cache
protected $appends = ['avatar_url'];

public function getAvatarUrlAttribute(): ?string
{
    // Cache pour éviter recalcul
    return Cache::remember("user_avatar_url:{$this->id}", 300, function () {
        // Logique existante
    });
}
```

#### 4. **Optimisation Dashboard Stats** (MOYEN)
**Fichier**: `app/Http/Controllers/Api/AdminController.php:21-46`

**Problème**: 
- 6 requêtes COUNT séparées
- Peut être optimisé avec une seule requête

**Recommandation**:
```php
// Utiliser selectRaw pour une seule requête
$stats = User::selectRaw('
    COUNT(*) as total_users,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users,
    SUM(CASE WHEN role = "super_user" THEN 1 ELSE 0 END) as super_users
')->first();
```

#### 5. **Indexes Manquants** (MOYEN)
**Recommandation**: Ajouter des indexes sur :
- `users.is_active`
- `users.role`
- `users.created_at`
- `user_sessions.user_id`
- `user_sessions.is_current`
- `user_sessions.last_activity`

**Migration**:
```php
Schema::table('users', function (Blueprint $table) {
    $table->index('is_active');
    $table->index('role');
    $table->index('created_at');
});

Schema::table('user_sessions', function (Blueprint $table) {
    $table->index(['user_id', 'is_current']);
    $table->index('last_activity');
});
```

#### 6. **Eager Loading dans SSOController** (FAIBLE)
**Fichier**: `app/Http/Controllers/Api/SSOController.php:57`

**Problème**: `$accessToken->user` peut déclencher une requête supplémentaire

**Recommandation**:
```php
$accessToken = \Laravel\Passport\Token::with('user')
    ->where('id', $token)
    ->where('revoked', false)
    ->first();
```

#### 7. **Optimisation Requêtes LIKE** (FAIBLE)
**Fichier**: `app/Http/Controllers/Api/AdminController.php:138-140`

**Problème**: 
- `LIKE '%search%'` ne peut pas utiliser d'index
- Performance dégradée sur grandes tables

**Recommandation**:
- Ajouter un index FULLTEXT sur `name`, `email`, `company` si MySQL 5.6+
- Utiliser `MATCH()` au lieu de `LIKE` pour les recherches textuelles

#### 8. **Cache des Sessions** (FAIBLE)
**Fichier**: `app/Http/Controllers/Api/SSOController.php:129`

**Problème**: 
- Chargement des sessions à chaque requête
- Peut être mis en cache avec TTL court

**Recommandation**:
```php
$sessions = Cache::remember("user_sessions:{$user->id}", 60, function () use ($user) {
    return $user->sessions()
        ->orderBy('last_activity', 'desc')
        ->orderBy('created_at', 'desc')
        ->get();
});
```

---

## 📊 Résumé des Priorités

### 🔴 CRITIQUE (À corriger immédiatement)
1. Validation MIME type réel des uploads d'images
2. Autorisation stricte sur AvatarController

### 🟠 IMPORTANT (À corriger rapidement)
1. Rate limiting sur routes d'authentification
2. Cache pour SystemSettings
3. Optimisation N+1 queries dans AdminController

### 🟡 MOYEN (À planifier)
1. Cache avatar URL
2. Optimisation dashboard stats
3. Ajout d'indexes DB
4. Validation URL website

### 🟢 FAIBLE (Améliorations futures)
1. Eager loading dans SSOController
2. Optimisation requêtes LIKE
3. Cache des sessions
4. Restriction CORS si applicable

---

## 📝 Notes

- **Aucune modification de logique métier** : Toutes les recommandations préservent le comportement existant
- **Tests recommandés** : Tester chaque optimisation en environnement de staging
- **Monitoring** : Ajouter des logs de performance pour identifier les bottlenecks réels

---

**Date de l'analyse**: 2025-01-XX
**Version analysée**: Commit actuel

