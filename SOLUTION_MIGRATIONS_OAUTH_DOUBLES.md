# 🔧 Solution définitive : Migrations OAuth en double

## ❌ Problème

Lors de `php artisan migrate:fresh` ou `php artisan migrate`, des erreurs apparaissent :
```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'oauth_auth_codes' already exists
```

**Cause :** Passport publie automatiquement ses migrations lors de `php artisan passport:install`, créant des migrations avec des timestamps récents qui entrent en conflit avec les migrations déjà exécutées.

## ✅ Solution mise en place

### 1. Migrations Passport originales dans le repository

Les migrations Passport originales (avec leurs dates originales) sont maintenant dans le repository :
- `2016_06_01_000001_create_oauth_auth_codes_table.php`
- `2016_06_01_000002_create_oauth_access_tokens_table.php`
- `2016_06_01_000003_create_oauth_refresh_tokens_table.php`
- `2016_06_01_000004_create_oauth_clients_table.php`
- `2024_06_01_000001_create_oauth_device_codes_table.php`

**Ces migrations NE SERONT PLUS PUBLIÉES** lors de `passport:install`.

### 2. Script de nettoyage automatique

Le script `supprimer-migrations-oauth-dupliquees.sh` supprime automatiquement toutes les migrations OAuth qui ne sont pas les originales Passport.

### 3. Script de déploiement modifié

Le script `deploy-o2switch.sh` :
- ✅ Ne publie plus les migrations Passport lors de l'installation
- ✅ Nettoie automatiquement les migrations OAuth en double avant d'exécuter les migrations
- ✅ Crée seulement les clés Passport (sans publier les migrations)

## 🚀 Utilisation en production

### Sur O2Switch :

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# Option 1 : Utiliser le script de déploiement automatique
./deploy-o2switch.sh

# Option 2 : Déploiement manuel
git pull origin main
composer install --no-dev --optimize-autoloader

# Nettoyer les migrations OAuth en double AVANT les migrations
./supprimer-migrations-oauth-dupliquees.sh

# Exécuter les migrations
php artisan migrate --force

# Installer Passport (sans publier les migrations)
php artisan passport:keys --force

# Seeders
php artisan db:seed --force
```

### Nettoyage manuel des migrations en double :

```bash
# Supprimer toutes les migrations OAuth sauf les originales Passport
find database/migrations -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" | xargs rm -f

# Vérifier
ls database/migrations/*oauth*.php
```

Vous devriez voir uniquement :
- `2016_06_01_000001_create_oauth_auth_codes_table.php`
- `2016_06_01_000002_create_oauth_access_tokens_table.php`
- `2016_06_01_000003_create_oauth_refresh_tokens_table.php`
- `2016_06_01_000004_create_oauth_clients_table.php`
- `2024_06_01_000001_create_oauth_device_codes_table.php`

## 📋 Séquence complète pour résoudre le problème

Si vous avez déjà des migrations OAuth en double :

```bash
# 1. Se connecter
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# 2. Supprimer les migrations OAuth en double
find database/migrations -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" | xargs rm -f

# 3. Vérifier qu'il ne reste que les migrations originales
ls database/migrations/*oauth*.php

# 4. Réessayer les migrations
php artisan migrate:fresh --force

# 5. Installer Passport (sans migrations)
php artisan passport:keys --force

# 6. Seeders
php artisan db:seed --force
```

## ⚠️ Important

1. **NE JAMAIS** exécuter `php artisan vendor:publish --tag=passport-migrations` après le déploiement initial
2. **TOUJOURS** nettoyer les migrations OAuth en double avant d'exécuter les migrations
3. Les migrations Passport originales sont maintenant dans le repository et ne doivent plus être republiées

## 🎯 Commandes rapides

```bash
# Nettoyer les migrations OAuth en double
./supprimer-migrations-oauth-dupliquees.sh

# Ou manuellement
find database/migrations -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" | xargs rm -f

# Vérifier
php artisan migrate:status | grep oauth
```

## 📚 Fichiers modifiés

- ✅ `database/migrations/2016_06_01_*_create_oauth_*.php` - Migrations Passport originales
- ✅ `database/migrations/2024_06_01_*_create_oauth_device_codes_table.php` - Migration device codes
- ✅ `deploy-o2switch.sh` - Script de déploiement modifié
- ✅ `supprimer-migrations-oauth-dupliquees.sh` - Script de nettoyage automatique

---

**Cette solution est définitive et empêchera toute création future de migrations OAuth en double.**

