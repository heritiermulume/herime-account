# 🔧 Résoudre l'erreur "Server Error" lors de la connexion

## ❌ Erreur

```
Server Error
```

Lors de la tentative de connexion, une erreur serveur 500 apparaît.

## 🔍 Diagnostic immédiat

### 1. Vérifier les logs Laravel (PRIORITÉ ABSOLUE)

```bash
ssh muhe3594@[hostname-o2switch]
cd /home/muhe3594/herime-account

# Voir les dernières erreurs
tail -n 100 storage/logs/laravel.log | grep -A 20 "ERROR"

# Voir les erreurs en temps réel
tail -f storage/logs/laravel.log
```

**Les logs vous indiqueront la cause exacte de l'erreur.**

## ✅ Causes courantes et solutions

### Cause 1 : Clés Passport manquantes

**Erreur typique :** `Passport keys not found` ou `OAuth keys missing`

**Solution :**
```bash
# Générer les clés Passport
php artisan passport:keys --force

# Vérifier que les clés existent
ls -la storage/oauth-*.key

# Si les clés n'existent pas, réinstaller Passport
php artisan passport:install --force
```

### Cause 2 : Erreur lors de la création du token

**Erreur typique :** `Call to undefined method` ou `createToken()`

**Solution :**
```bash
# Vérifier que Passport est bien installé
php artisan passport:install --force

# Vérifier les clients OAuth
php artisan passport:client --list

# Créer un client personnel si nécessaire
php artisan passport:client --personal --name="Herime SSO Personal Access Client"
```

### Cause 3 : Base de données non accessible

**Erreur typique :** `SQLSTATE[HY000] [2002]` ou `Connection refused`

**Solution :**
```bash
# Tester la connexion à la base de données
php artisan migrate:status

# Vérifier la configuration
cat .env | grep DB_

# Vérifier que la base de données existe
php artisan db:show
```

### Cause 4 : Tables manquantes

**Erreur typique :** `Table 'users' doesn't exist` ou `Base table or view not found`

**Solution :**
```bash
# Vérifier les migrations
php artisan migrate:status

# Exécuter les migrations
php artisan migrate --force
```

### Cause 5 : Permissions insuffisantes

**Erreur typique :** `Permission denied` ou `Unable to write`

**Solution :**
```bash
# Vérifier les permissions
ls -la storage bootstrap/cache

# Corriger les permissions
chmod -R 775 storage bootstrap/cache
chmod -R 755 storage/logs storage/framework
```

### Cause 6 : Caches corrompus

**Solution :**
```bash
# Vider tous les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Recréer les caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Cause 7 : APP_KEY manquante ou invalide

**Solution :**
```bash
# Vérifier APP_KEY
cat .env | grep APP_KEY

# Si manquante, générer une nouvelle clé
php artisan key:generate --force

# Vider le cache
php artisan config:clear
php artisan config:cache
```

## 📋 Séquence complète de diagnostic et correction

```bash
# 1. Se connecter
ssh muhe3594@[hostname-o2switch]
cd /home/muhe3594/herime-account

# 2. Voir les logs (PRIORITÉ)
tail -n 100 storage/logs/laravel.log | grep -A 20 "ERROR"

# 3. Vérifier Passport
php artisan passport:keys --force
php artisan passport:client --list

# 4. Vérifier la base de données
php artisan migrate:status
php artisan db:show

# 5. Vérifier les permissions
chmod -R 775 storage bootstrap/cache

# 6. Vider les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 7. Recréer les caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 8. Vérifier APP_KEY
php artisan key:generate --force
php artisan config:cache

# 9. Réessayer la connexion
```

## 🎯 Commandes rapides de diagnostic

```bash
# Voir la dernière erreur complète
tail -n 200 storage/logs/laravel.log | grep -B 5 -A 30 "ERROR"

# Vérifier Passport
php artisan passport:keys --force
php artisan passport:client --list

# Vérifier la DB
php artisan migrate:status

# Tout corriger d'un coup
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan optimize
```

## ⚠️ Points importants

1. **TOUJOURS vérifier les logs en premier** - Ils indiquent la cause exacte
2. **Passport doit être configuré** - Les clés OAuth doivent exister
3. **Les clients OAuth doivent exister** - Utilisez `passport:client --list`
4. **La base de données doit être accessible** - Vérifiez avec `migrate:status`
5. **APP_KEY doit être défini** - Généré avec `key:generate`

## 📚 Ressources

- Consultez les logs : `storage/logs/laravel.log`
- Vérifiez Passport : `php artisan passport:keys`
- Documentation Laravel Passport : https://laravel.com/docs/passport

