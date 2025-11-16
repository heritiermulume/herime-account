# Guide de Migration en Production

## 🎯 Migration à exécuter

**Fichier** : `2025_11_16_074003_add_token_id_to_user_sessions_table.php`

**Description** : Ajoute la colonne `token_id` à la table `user_sessions` pour lier les sessions aux tokens Passport.

**Impact** : 
- ✅ Pas de perte de données
- ✅ Colonne nullable (pas d'erreur sur les sessions existantes)
- ✅ Ajout d'index pour performance
- ⚠️ Temps d'exécution : ~1-5 secondes (selon le nombre de sessions)

---

## 📋 Étapes de déploiement

### 1. Connexion au serveur de production

```bash
ssh utilisateur@compte.herime.com
# ou
ssh utilisateur@IP_DU_SERVEUR
```

### 2. Naviguer vers le répertoire du projet

```bash
cd /var/www/compte.herime.com
# ou le chemin où se trouve votre application
cd /home/utilisateur/compte.herime.com
```

### 3. Activer le mode maintenance (optionnel mais recommandé)

```bash
php artisan down --message="Mise à jour en cours, nous revenons dans 2 minutes" --retry=60
```

**Explication** :
- `--message` : Message affiché aux utilisateurs
- `--retry=60` : Les navigateurs réessayeront après 60 secondes

### 4. Récupérer les dernières modifications

```bash
git pull origin main
```

**Vérification** :
```bash
git log --oneline -5
```

Vous devriez voir le commit :
```
aace2d6 feat(sessions): révoquer le token lors de la désactivation/suppression de session
```

### 5. Installer les dépendances (si nécessaire)

```bash
composer install --no-dev --optimize-autoloader
```

**Note** : `--no-dev` exclut les dépendances de développement en production.

### 6. Exécuter la migration

```bash
php artisan migrate --force
```

**Explication** :
- `--force` : Nécessaire en production (Laravel demande confirmation par défaut)

**Sortie attendue** :
```
INFO  Running migrations.

2025_11_16_074003_add_token_id_to_user_sessions_table ........ 367.42ms DONE
```

### 7. Vider les caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 8. Compiler les assets (si modifiés)

```bash
npm run build
```

**Note** : Pas nécessaire pour cette migration, mais bonne pratique après un `git pull`.

### 9. Redémarrer les services

#### Pour PHP-FPM :
```bash
sudo systemctl restart php8.2-fpm
# ou
sudo systemctl restart php8.3-fpm
# ou
sudo service php-fpm restart
```

#### Pour Nginx :
```bash
sudo systemctl reload nginx
# ou
sudo nginx -s reload
```

#### Pour Apache :
```bash
sudo systemctl restart apache2
# ou
sudo service apache2 restart
```

### 10. Désactiver le mode maintenance

```bash
php artisan up
```

### 11. Vérifier que tout fonctionne

```bash
# Vérifier les logs
tail -f storage/logs/laravel.log

# Vérifier la structure de la table
php artisan tinker
>>> \DB::select("DESCRIBE user_sessions");
>>> exit
```

**Sortie attendue** :
```
[
  ...
  {
    "Field": "token_id",
    "Type": "varchar(100)",
    "Null": "YES",
    "Key": "MUL",
    "Default": null,
    "Extra": ""
  },
  ...
]
```

---

## 🔍 Vérification post-migration

### 1. Tester une connexion

```bash
# Depuis votre navigateur
https://compte.herime.com/login
```

1. Se connecter avec un compte
2. Aller sur Dashboard → Sessions récentes
3. Vérifier que les sessions s'affichent correctement

### 2. Tester la désactivation d'une session

1. Se connecter sur 2 appareils différents
2. Depuis le premier, désactiver la session du second
3. Vérifier que le second appareil est déconnecté

### 3. Vérifier les logs

```bash
tail -f storage/logs/laravel.log | grep "Token revoked"
```

Vous devriez voir :
```
[2025-11-16 08:00:00] local.INFO: UserController: Token revoked for session {"user_id":2,"session_id":123,"token_id":"abc123"}
```

---

## ⚠️ En cas de problème

### Rollback de la migration

Si quelque chose ne va pas, vous pouvez annuler la migration :

```bash
php artisan migrate:rollback --step=1
```

**Effet** :
- Supprime la colonne `token_id` de la table `user_sessions`
- Supprime l'index associé
- Les sessions existantes restent intactes

### Vérifier l'état des migrations

```bash
php artisan migrate:status
```

**Sortie** :
```
Migration name                                                    Ran?
2025_11_16_074003_add_token_id_to_user_sessions_table ........... Yes
```

### Réexécuter la migration

Si vous avez fait un rollback et voulez réexécuter :

```bash
php artisan migrate --force
```

---

## 🛠️ Commandes utiles

### Vérifier la connexion à la base de données

```bash
php artisan tinker
>>> \DB::connection()->getPdo();
>>> exit
```

### Vérifier les permissions

```bash
# Les fichiers doivent appartenir à l'utilisateur web
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Vérifier les variables d'environnement

```bash
cat .env | grep DB_
```

**Sortie attendue** :
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=compte_herime
DB_USERNAME=root
DB_PASSWORD=********
```

---

## 📊 Script de déploiement automatique (optionnel)

Créez un fichier `deploy.sh` pour automatiser le processus :

```bash
#!/bin/bash

echo "🚀 Déploiement en cours..."

# Mode maintenance
php artisan down

# Récupérer les modifications
git pull origin main

# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Exécuter les migrations
php artisan migrate --force

# Vider les caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Compiler les assets (si nécessaire)
# npm run build

# Redémarrer PHP-FPM
sudo systemctl restart php8.2-fpm

# Désactiver le mode maintenance
php artisan up

echo "✅ Déploiement terminé avec succès !"
```

**Utilisation** :
```bash
chmod +x deploy.sh
./deploy.sh
```

---

## 🔐 Sécurité

### Backup de la base de données (IMPORTANT !)

**Avant toute migration, faites un backup** :

```bash
# Backup complet
mysqldump -u root -p compte_herime > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup de la table user_sessions uniquement
mysqldump -u root -p compte_herime user_sessions > backup_user_sessions_$(date +%Y%m%d_%H%M%S).sql
```

**Restauration en cas de problème** :
```bash
mysql -u root -p compte_herime < backup_20251116_080000.sql
```

### Tester sur un environnement de staging

Si vous avez un environnement de staging, testez d'abord là-bas :

```bash
# Sur staging
ssh utilisateur@staging.compte.herime.com
cd /var/www/staging
git pull origin main
php artisan migrate --force
# Tester...
```

---

## 📞 Support

En cas de problème pendant la migration :

1. **Vérifier les logs** : `tail -f storage/logs/laravel.log`
2. **Vérifier les logs MySQL** : `sudo tail -f /var/log/mysql/error.log`
3. **Vérifier les logs Nginx** : `sudo tail -f /var/log/nginx/error.log`
4. **Rollback** : `php artisan migrate:rollback --step=1`
5. **Restaurer le backup** : `mysql -u root -p compte_herime < backup.sql`

---

## ✅ Checklist de déploiement

- [ ] Backup de la base de données effectué
- [ ] Mode maintenance activé
- [ ] `git pull origin main` exécuté
- [ ] `composer install` exécuté (si nécessaire)
- [ ] `php artisan migrate --force` exécuté avec succès
- [ ] Caches vidés (`config:cache`, `route:cache`, etc.)
- [ ] Services redémarrés (PHP-FPM, Nginx/Apache)
- [ ] Mode maintenance désactivé
- [ ] Tests de connexion effectués
- [ ] Tests de désactivation de session effectués
- [ ] Logs vérifiés (pas d'erreurs)

---

**Date de création** : 16 novembre 2025  
**Dernière mise à jour** : 16 novembre 2025

