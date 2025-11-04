# 🔧 Résoudre l'erreur "Personal access client not found" Passport

## ❌ Erreur

```
Personal access client not found for 'users' user provider. Please create one.
```

## 🔍 Cause

Passport nécessite un "Personal Access Client" pour créer des tokens d'accès personnels. Ce client n'existe pas dans la base de données.

## ✅ Solution : Créer le client d'accès personnel

### Sur O2Switch :

```bash
ssh muhe3594@[hostname-o2switch]
cd /home/muhe3594/herime-account

# Créer le client d'accès personnel
php artisan passport:client --personal --name="Herime SSO Personal Access Client"

# Vérifier que le client a été créé
php artisan passport:client --list
```

### Commande complète (non-interactive) :

```bash
php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction
```

## 📋 Séquence complète de correction

```bash
# 1. Se connecter
ssh muhe3594@[hostname-o2switch]
cd /home/muhe3594/herime-account

# 2. Créer le client d'accès personnel
php artisan passport:client --personal --name="Herime SSO Personal Access Client"

# 3. Vérifier que le client existe
php artisan passport:client --list

# 4. Vérifier les clés Passport
php artisan passport:keys --force

# 5. Vider le cache
php artisan config:clear
php artisan cache:clear

# 6. Recréer les caches
php artisan config:cache
php artisan route:cache
php artisan optimize
```

## 🎯 Commandes rapides

```bash
# Créer le client d'accès personnel
php artisan passport:client --personal --name="Herime SSO" --no-interaction

# Vérifier
php artisan passport:client --list

# Si besoin, réinstaller Passport complètement
php artisan passport:install --force
```

## 🔄 Solution alternative : Réinstaller Passport

Si le problème persiste, réinstaller Passport complètement :

```bash
# Réinstaller Passport (créera automatiquement le client personnel)
php artisan passport:install --force

# Vérifier
php artisan passport:client --list
```

## ⚠️ Important

1. **Le client personnel est requis** pour `createToken()` dans les contrôleurs
2. **Un seul client personnel** est nécessaire par provider ('users')
3. **Après création**, vider le cache : `php artisan config:clear`

## 📚 Ressources

- Documentation Laravel Passport : https://laravel.com/docs/passport
- Consultez `RESOUDRE_ERREUR_CONNEXION.md` pour plus de détails

