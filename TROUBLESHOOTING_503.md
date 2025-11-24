# 🔧 Guide de résolution des erreurs 503 lors de l'inscription

## 🐛 Symptôme

Erreur intermittente lors de l'inscription :
```
POST https://compte.herime.com/api/register 503 (Service Unavailable)
```

## 🔍 Diagnostic

### 1️⃣ Vérifier l'installation de Passport

Connectez-vous en SSH et exécutez :

```bash
cd /chemin/vers/votre/projet
php artisan passport:check
```

Cette commande va diagnostiquer automatiquement :
- ✅ Présence des tables Passport dans la base de données
- ✅ Existence des clés de chiffrement RSA
- ✅ Configuration des clients OAuth
- ✅ Configuration des Personal Access Clients

### 2️⃣ Vérifier les logs Laravel

```bash
tail -n 100 storage/logs/laravel.log | grep -A 20 "register\|TOKEN_CREATION_FAILED"
```

Recherchez les erreurs liées à :
- `TOKEN_CREATION_FAILED` : Échec de création de token
- Erreurs de connexion à la base de données
- Erreurs de mémoire PHP

### 3️⃣ Vérifier la configuration PHP

Créez un fichier `info.php` dans le dossier `public/` :

```php
<?php
phpinfo();
```

Accédez à `https://compte.herime.com/info.php` et vérifiez :
- `memory_limit` : doit être ≥ 256M
- `max_execution_time` : doit être ≥ 60
- `max_input_time` : doit être ≥ 60

⚠️ **N'oubliez pas de supprimer ce fichier après vérification !**

### 4️⃣ Vérifier la base de données

```bash
php artisan tinker
```

Puis dans Tinker :

```php
// Tester la connexion
DB::connection()->getPdo();

// Compter les tokens
DB::table('oauth_access_tokens')->count();

// Vérifier les tables Passport
Schema::hasTable('oauth_clients'); // doit retourner true
Schema::hasTable('oauth_access_tokens'); // doit retourner true
```

## 🔧 Solutions

### Solution 1 : Réinstaller Passport

Si des tables sont manquantes :

```bash
# 1. Exécuter les migrations
php artisan migrate

# 2. Installer Passport
php artisan passport:install

# 3. Vérifier l'installation
php artisan passport:check
```

### Solution 2 : Régénérer les clés de chiffrement

Si les clés RSA sont manquantes :

```bash
# Supprimer les anciennes clés (si présentes)
rm storage/oauth-*.key

# Réinstaller Passport pour régénérer les clés
php artisan passport:install --force

# Vérifier
php artisan passport:check
```

### Solution 3 : Augmenter les limites PHP

Si vous utilisez **O2Switch** ou un hébergement partagé :

1. Vérifiez que le fichier `public/.user.ini` existe avec ce contenu :

```ini
memory_limit = 256M
max_execution_time = 60
max_input_time = 60
post_max_size = 20M
upload_max_filesize = 20M
```

2. Si le fichier n'est pas pris en compte, créez `.htaccess` :

```apache
php_value memory_limit 256M
php_value max_execution_time 60
php_value max_input_time 60
```

3. Redémarrez PHP-FPM (si accessible) :

```bash
# Sur O2Switch, contactez le support pour redémarrer PHP-FPM
```

### Solution 4 : Optimiser la base de données

Si la base de données est lente :

```bash
# Nettoyer les vieux tokens expirés
php artisan passport:purge

# Optimiser les tables
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Solution 5 : Vérifier les connexions simultanées

Si le problème est lié aux connexions DB :

```bash
# Dans Tinker
DB::connection()->select('SHOW PROCESSLIST');
```

Si trop de connexions sont ouvertes, ajoutez dans `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=votre_db
DB_USERNAME=votre_user
DB_PASSWORD=votre_pass

# Optimisations importantes
DB_POOL_SIZE=5
DB_TIMEOUT=5
```

## 📊 Surveillance en temps réel

Pour surveiller les erreurs en temps réel pendant les tests :

```bash
# Terminal 1 : Surveiller les logs Laravel
tail -f storage/logs/laravel.log

# Terminal 2 : Surveiller les logs Apache/Nginx
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/nginx/error.log
```

## ✅ Test de validation

Après avoir appliqué les solutions, testez :

1. **Test simple** : Créez un compte sans paramètre redirect
   ```
   https://compte.herime.com/register
   ```

2. **Test avec redirect** : Créez un compte avec redirection SSO
   ```
   https://compte.herime.com/register?redirect=https://store.herime.com/sso/callback
   ```

3. **Test de charge** : Créez plusieurs comptes rapidement (5-10 en succession)

4. **Vérifiez les logs** : Aucune erreur ne doit apparaître dans les logs

## 🆘 Support d'urgence

Si le problème persiste après toutes ces étapes :

1. **Désactiver temporairement l'inscription** :
   ```bash
   php artisan tinker
   ```
   ```php
   SystemSetting::set('registration_enabled', '0');
   ```

2. **Contacter le support O2Switch** pour :
   - Vérifier les quotas de ressources
   - Redémarrer PHP-FPM
   - Vérifier les logs système

3. **Mode dégradé** : Désactiver la génération de tokens SSO temporairement en commentant la logique de redirection dans `SimpleAuthController.php`

## 📝 Checklist de maintenance préventive

- [ ] Nettoyer les tokens expirés chaque semaine : `php artisan passport:purge`
- [ ] Surveiller la taille de la table `oauth_access_tokens`
- [ ] Vérifier les logs chaque jour
- [ ] Tester l'inscription régulièrement
- [ ] Maintenir PHP et Laravel à jour
- [ ] Optimiser le cache Laravel régulièrement

## 🔗 Ressources utiles

- [Documentation Laravel Passport](https://laravel.com/docs/11.x/passport)
- [Guide O2Switch PHP-FPM](https://faq.o2switch.fr/)
- Logs Laravel : `storage/logs/laravel.log`
- Command de diagnostic : `php artisan passport:check`

---

**Dernière mise à jour** : Novembre 2025  
**Version Laravel** : 11.x  
**Version Passport** : Latest

