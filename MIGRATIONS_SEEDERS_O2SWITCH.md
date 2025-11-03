# 📊 Migrations et Seeders sur O2Switch - Guide Complet

## 🎯 Commandes essentielles

### Se connecter au serveur

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com
```

---

## 🔄 Migrations

### 1. Vérifier le statut des migrations

```bash
php artisan migrate:status
```

Affiche la liste de toutes les migrations et leur statut (Ran/Pending).

### 2. Exécuter toutes les migrations

```bash
php artisan migrate --force
```

⚠️ **Important** : Le flag `--force` est nécessaire en production pour éviter les confirmations interactives.

### 3. Exécuter une migration spécifique

```bash
# Exécuter jusqu'à une migration spécifique
php artisan migrate --path=/database/migrations/2025_10_23_232815_create_oauth_auth_codes_table.php --force
```

### 4. Rollback (annuler la dernière migration)

```bash
php artisan migrate:rollback --force
```

### 5. Rollback toutes les migrations

```bash
php artisan migrate:reset --force
```

⚠️ **Attention** : Cette commande supprime toutes les tables !

### 6. Réinitialiser complètement la base

```bash
php artisan migrate:fresh --force
```

⚠️ **DANGER** : Cette commande supprime toutes les tables et les recrée. Toutes les données seront perdues !

---

## 🌱 Seeders

### 1. Exécuter tous les seeders

```bash
php artisan db:seed --force
```

Exécute tous les seeders définis dans `DatabaseSeeder.php`.

### 2. Exécuter un seeder spécifique

```bash
# Seeder principal
php artisan db:seed --class=DatabaseSeeder --force

# Seeder Admin
php artisan db:seed --class=AdminSeeder --force
```

### 3. Réinitialiser et seed (fresh + seed)

```bash
php artisan migrate:fresh --seed --force
```

Supprime toutes les tables, recrée les migrations et exécute les seeders.

---

## 🔐 Passport OAuth2

### 1. Installer Passport

```bash
php artisan passport:install --force
```

Crée les clés de chiffrement et les tables OAuth nécessaires.

### 2. Réinstaller Passport (si erreur)

```bash
php artisan passport:keys --force
php artisan passport:install --force
```

### 3. Créer un client OAuth

```bash
# Client personnel
php artisan passport:client --personal --name="Herime SSO Personal Access Client"

# Client public
php artisan passport:client --public --name="Herime Academy" --redirect_uri="https://academie.herime.com/sso/callback"
```

---

## 📋 Séquence complète de déploiement initial

Voici la séquence complète pour un nouveau déploiement :

```bash
# 1. Se connecter
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# 2. Vérifier la connexion à la base de données
php artisan db:show

# 3. Exécuter les migrations
php artisan migrate --force

# 4. Installer Passport
php artisan passport:install --force

# 5. Exécuter les seeders
php artisan db:seed --force

# 6. Vérifier que tout est OK
php artisan migrate:status
```

---

## 🔄 Séquence de mise à jour (après git pull)

Quand vous mettez à jour le code :

```bash
# 1. Mettre à jour le code
git pull origin main

# 2. Installer les nouvelles dépendances
composer install --no-dev --optimize-autoloader

# 3. Exécuter les nouvelles migrations
php artisan migrate --force

# 4. Vérifier le statut
php artisan migrate:status
```

⚠️ **Note** : Les seeders ne sont généralement pas réexécutés lors d'une mise à jour pour éviter de dupliquer les données.

---

## ✅ Vérifications

### Vérifier que les migrations sont bien exécutées

```bash
php artisan migrate:status
```

Vous devriez voir toutes les migrations avec le statut `[X] Ran`.

### Vérifier la connexion à la base de données

```bash
php artisan db:show
```

Affiche les informations de connexion et le nombre de tables.

### Vérifier les tables créées

```bash
php artisan db:table
```

Liste toutes les tables de la base de données.

---

## 🆘 Dépannage

### Erreur : "Migration table not found"

```bash
# Créer la table de migrations
php artisan migrate:install
php artisan migrate --force
```

### Erreur : "Table already exists"

Cela signifie que la table existe déjà mais n'est pas dans la table `migrations`.

**Solution 1** : Marquer la migration comme exécutée (sans la créer)

```bash
php artisan migrate --pretend --force
```

**Solution 2** : Supprimer la table manuellement (si vous pouvez la recréer)

```bash
# En SSH, se connecter à MySQL
mysql -u votre_user -p herime_account

# Supprimer la table
DROP TABLE nom_de_la_table;

# Quitter
EXIT;

# Réexécuter la migration
php artisan migrate --force
```

### Erreur : "Class not found" pour un seeder

Vérifiez que le seeder existe dans `database/seeders/` :

```bash
ls -la database/seeders/
```

### Erreur : "Passport keys already exist"

```bash
# Supprimer les anciennes clés
rm storage/oauth-private.key
rm storage/oauth-public.key

# Réinstaller
php artisan passport:install --force
```

### Erreur : "SQLSTATE[HY000] [2002] Connection refused"

Vérifiez votre fichier `.env` :

```bash
# Vérifier la configuration
cat .env | grep DB_
```

Assurez-vous que :
- `DB_HOST=localhost` (ou l'IP correcte)
- `DB_DATABASE=herime_account` (nom correct)
- `DB_USERNAME` et `DB_PASSWORD` sont corrects

---

## 📝 Commandes rapides de référence

```bash
# Migrations
php artisan migrate --force                    # Exécuter toutes les migrations
php artisan migrate:status                     # Vérifier le statut
php artisan migrate:rollback --force           # Annuler la dernière migration
php artisan migrate:fresh --force              # Réinitialiser (DANGER)

# Seeders
php artisan db:seed --force                    # Exécuter tous les seeders
php artisan db:seed --class=DatabaseSeeder --force  # Seeder spécifique

# Passport
php artisan passport:install --force           # Installer Passport
php artisan passport:keys --force              # Régénérer les clés

# Vérifications
php artisan db:show                           # Infos de connexion
php artisan migrate:status                    # Statut des migrations
```

---

## 🎯 Checklist complète

Avant de déployer en production :

- [ ] Base de données `herime_account` créée
- [ ] Fichier `.env` configuré avec les bonnes valeurs
- [ ] Test de connexion réussi (`php artisan db:show`)
- [ ] Migrations exécutées (`php artisan migrate --force`)
- [ ] Passport installé (`php artisan passport:install --force`)
- [ ] Seeders exécutés (`php artisan db:seed --force`)
- [ ] Vérification du statut (`php artisan migrate:status`)
- [ ] Application testée dans le navigateur

---

## 📚 Ressources

- [Documentation Laravel - Migrations](https://laravel.com/docs/11.x/migrations)
- [Documentation Laravel - Seeders](https://laravel.com/docs/11.x/seeding)
- [Documentation Laravel Passport](https://laravel.com/docs/11.x/passport)

---

**Note** : En production, utilisez toujours le flag `--force` pour éviter les confirmations interactives qui peuvent bloquer les scripts automatiques.

