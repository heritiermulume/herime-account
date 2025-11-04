# 🔧 Résoudre l'erreur 500 en production

## ❌ Erreur

```
500 Erreur serveur
```

## 🔍 Diagnostic

### 1. Vérifier les logs Laravel

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# Voir les dernières erreurs
tail -n 100 storage/logs/laravel.log

# Voir les erreurs en temps réel
tail -f storage/logs/laravel.log
```

### 2. Vérifier les permissions

```bash
# Vérifier les permissions des dossiers
ls -la storage bootstrap/cache

# Corriger les permissions si nécessaire
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
# OU sur O2Switch, utilisez votre utilisateur
chown -R votre-utilisateur:www-data storage bootstrap/cache
```

### 3. Vérifier la configuration

```bash
# Vérifier le fichier .env
cat .env | grep -E "APP_ENV|APP_DEBUG|APP_KEY"

# Vérifier que APP_KEY est défini
php artisan key:generate --show

# Vider les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 4. Vérifier les erreurs PHP

```bash
# Vérifier les logs PHP
tail -n 50 /var/log/php-fpm/error.log
# OU selon votre configuration O2Switch
tail -n 50 /var/log/php_errors.log
```

### 5. Vérifier la base de données

```bash
# Tester la connexion à la base de données
php artisan migrate:status

# Vérifier la configuration
php artisan db:show
```

## ✅ Solutions courantes

### Solution 1 : Permissions incorrectes

```bash
# Sur O2Switch
chmod -R 755 storage bootstrap/cache public
chmod -R 775 storage/logs storage/framework
```

### Solution 2 : Fichier .env manquant ou incorrect

```bash
# Vérifier que .env existe
ls -la .env

# Si manquant, copier depuis l'exemple
cp env.o2switch.example .env

# Régénérer la clé
php artisan key:generate

# Vérifier les variables importantes
cat .env | grep -E "APP_ENV|APP_DEBUG|DB_|APP_URL"
```

### Solution 3 : Caches corrompus

```bash
# Vider tous les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Recréer les caches (en production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Solution 4 : Erreurs de syntaxe PHP

```bash
# Vérifier la syntaxe PHP
php -l app/Http/Kernel.php
php -l routes/web.php
php -l routes/api.php

# Vérifier toutes les routes
php artisan route:list
```

### Solution 5 : Extensions PHP manquantes

```bash
# Vérifier les extensions requises
php -m | grep -E "pdo|mbstring|openssl|tokenizer|json|curl|xml"

# Extensions nécessaires pour Laravel
# - pdo_mysql
# - mbstring
# - openssl
# - tokenizer
# - json
# - curl
# - xml
```

### Solution 6 : Mémoire insuffisante

```bash
# Vérifier la limite de mémoire PHP
php -i | grep memory_limit

# Augmenter si nécessaire dans .env ou php.ini
# memory_limit = 256M
```

## 📋 Séquence complète de diagnostic

```bash
# 1. Se connecter
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# 2. Vérifier les logs
tail -n 100 storage/logs/laravel.log

# 3. Vérifier les permissions
ls -la storage bootstrap/cache

# 4. Vérifier la configuration
php artisan config:show | head -20

# 5. Vider les caches
php artisan config:clear
php artisan cache:clear

# 6. Vérifier les routes
php artisan route:list

# 7. Tester la connexion DB
php artisan migrate:status

# 8. Recréer les caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## 🎯 Commandes rapides de diagnostic

```bash
# Voir la dernière erreur
tail -n 50 storage/logs/laravel.log | grep -A 10 "ERROR"

# Vérifier les permissions
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

# Vérifier la configuration
php artisan about

# Test rapide
curl -I http://votre-domaine.com
```

## ⚠️ Points importants

1. **APP_DEBUG=false** en production
2. **Permissions** correctes sur storage et bootstrap/cache
3. **APP_KEY** doit être défini
4. **Base de données** accessible
5. **Logs** à vérifier en premier

## 📚 Ressources

- Consultez les logs Laravel : `storage/logs/laravel.log`
- Vérifiez les logs PHP : `/var/log/php-fpm/error.log`
- Vérifiez les logs Nginx/Apache : `/var/log/nginx/error.log`

