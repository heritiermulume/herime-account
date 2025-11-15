# 🔄 Recréer toutes les migrations et seeders - Guide Complet

## 🎯 Objectif

Réinitialiser complètement la base de données et recréer toutes les tables avec les migrations et seeders.

## ⚠️ ATTENTION

Cette opération **supprime toutes les données** de la base de données ! Utilisez uniquement si vous êtes sûr de vouloir tout réinitialiser.

---

## 📋 Méthode 1 : Fresh Migration (Recommandée)

### Sur O2Switch :

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# 1. Supprimer toutes les tables et les recréer
php artisan migrate:fresh --force

# 2. Installer Passport
php artisan passport:install --force

# 3. Exécuter tous les seeders
php artisan db:seed --force

# 4. Vérifier que tout est OK
php artisan migrate:status
php artisan db:show
```

### Explication :

- `migrate:fresh` : Supprime toutes les tables et recrée la base de données
- `passport:install` : Crée les clés OAuth et les tables Passport
- `db:seed` : Exécute tous les seeders pour peupler la base

---

## 📋 Méthode 2 : Reset complet (Plus drastique)

### Si vous voulez aussi supprimer la table migrations :

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# 1. Se connecter à MySQL
mysql -u votre_user_mysql -p herime_account

# 2. Supprimer toutes les tables (ATTENTION : supprime tout !)
DROP DATABASE herime_account;
CREATE DATABASE herime_account CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 3. Quitter MySQL
EXIT;

# 4. Recréer la table migrations
php artisan migrate:install

# 5. Exécuter toutes les migrations
php artisan migrate --force

# 6. Installer Passport
php artisan passport:install --force

# 7. Exécuter les seeders
php artisan db:seed --force

# 8. Vérifier
php artisan migrate:status
```

---

## 📋 Méthode 3 : Rollback puis re-migration

### Si vous voulez garder la structure mais réinitialiser :

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# 1. Annuler toutes les migrations (supprime les tables)
php artisan migrate:reset --force

# 2. Réexécuter toutes les migrations
php artisan migrate --force

# 3. Installer Passport
php artisan passport:install --force

# 4. Exécuter les seeders
php artisan db:seed --force
```

---

## 🎯 Séquence complète recommandée

### Sur O2Switch :

```bash
# 1. Se connecter
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# 2. Vérifier l'état actuel (optionnel)
php artisan migrate:status

# 3. Supprimer et recréer toutes les tables
php artisan migrate:fresh --force

# 4. Installer Passport (crée les clés et tables OAuth)
php artisan passport:install --force

# 5. Exécuter tous les seeders
php artisan db:seed --force

# 6. Vérifier que tout est OK
php artisan migrate:status
php artisan db:show

# 7. Vérifier les données créées
mysql -u votre_user_mysql -p herime_account -e "SELECT COUNT(*) as users FROM users; SELECT COUNT(*) as admins FROM admins;"
```

---

## 📊 Vérifications après recréation

### Vérifier les tables créées :

```bash
mysql -u votre_user_mysql -p herime_account -e "SHOW TABLES;"
```

Vous devriez voir :
- `users`
- `admins`
- `oauth_*` (tables OAuth)
- `migrations`
- `cache`
- `jobs`
- `user_sessions`
- etc.

### Vérifier les données seedées :

```bash
mysql -u votre_user_mysql -p herime_account -e "SELECT email, role FROM users; SELECT email, role FROM admins;"
```

Vous devriez voir :
- `admin@example.com` (super_user)
- `test@example.com` (utilisateur test)
- `admin@example.com` (super_admin dans admins)

---

## 🔧 En cas de problème

### Si migrate:fresh échoue :

```bash
# Vérifier les erreurs
php artisan migrate:fresh --force 2>&1 | tee migrate-error.log

# Voir les logs
tail -f storage/logs/laravel.log
```

### Si Passport échoue :

```bash
# Supprimer les anciennes clés
rm -f storage/oauth-private.key storage/oauth-public.key

# Réinstaller
php artisan passport:install --force
```

### Si les seeders échouent :

```bash
# Exécuter un seeder spécifique
php artisan db:seed --class=DatabaseSeeder --force
php artisan db:seed --class=AdminSeeder --force
```

---

## 📝 Script automatisé

Créez un fichier `recreer-base.sh` sur O2Switch :

```bash
#!/bin/bash

echo "🔄 Réinitialisation complète de la base de données..."

# Vérifier qu'on est dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo "❌ Erreur : Ce script doit être exécuté à la racine du projet Laravel"
    exit 1
fi

# Confirmation
read -p "⚠️  Cette opération va supprimer TOUTES les données. Continuer ? (o/N) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Oo]$ ]]; then
    echo "❌ Opération annulée"
    exit 1
fi

# Fresh migration
echo "📦 Suppression et recréation des tables..."
php artisan migrate:fresh --force

# Passport
echo "🔐 Installation de Passport..."
php artisan passport:install --force

# Seeders
echo "🌱 Exécution des seeders..."
php artisan db:seed --force

# Vérification
echo "✅ Vérification..."
php artisan migrate:status

echo ""
echo "✨ Réinitialisation terminée avec succès !"
echo ""
echo "📋 Identifiants par défaut :"
echo "   Email: admin@example.com"
echo "   Mot de passe: password"
echo ""
echo "⚠️  Changez ces identifiants immédiatement !"
```

### Utilisation :

```bash
chmod +x recreer-base.sh
./recreer-base.sh
```

---

## 🎯 Commandes rapides de référence

```bash
# Recréer tout (méthode recommandée)
php artisan migrate:fresh --force
php artisan passport:install --force
php artisan db:seed --force

# Vérifier
php artisan migrate:status
php artisan db:show

# Voir les données
mysql -u votre_user_mysql -p herime_account -e "SELECT * FROM users; SELECT * FROM admins;"
```

---

## 📚 Différences entre les méthodes

| Méthode | Supprime les données | Supprime la structure | Recommandé pour |
|---------|---------------------|----------------------|-----------------|
| `migrate:fresh` | ✅ Oui | ✅ Oui | Réinitialisation complète |
| `migrate:reset` + `migrate` | ✅ Oui | ✅ Oui | Même chose que fresh |
| `DROP DATABASE` | ✅ Oui | ✅ Oui | Réinitialisation totale |
| `migrate:rollback` | ✅ Oui | ✅ Oui | Annuler dernières migrations |

---

## ✅ Checklist de réinitialisation

- [ ] Sauvegardé les données importantes (si nécessaire)
- [ ] Exécuté `php artisan migrate:fresh --force`
- [ ] Exécuté `php artisan passport:install --force`
- [ ] Exécuté `php artisan db:seed --force`
- [ ] Vérifié avec `php artisan migrate:status`
- [ ] Vérifié les données avec `php artisan db:show`
- [ ] Testé la connexion à l'application
- [ ] Changé les identifiants par défaut

---

## 🆘 Dépannage

### Erreur : "Migration table not found"

```bash
php artisan migrate:install
php artisan migrate:fresh --force
```

### Erreur : "Table already exists"

Voir `RESOUDRE_ERREUR_TABLE_EXISTS.md` ou `CORRIGER_MIGRATIONS_PASSPORT.md`

### Erreur : "Access denied"

Voir `RESOUDRE_ERREUR_ACCESS_DENIED.md`

---

## 📚 Ressources

- Consultez `MIGRATIONS_SEEDERS_O2SWITCH.md` pour les commandes détaillées
- Consultez `CREATE_DATABASE_O2SWITCH.md` pour créer la base de données
- Documentation Laravel : https://laravel.com/docs/11.x/migrations

---

**Note importante** : `migrate:fresh` est la méthode la plus simple et la plus sûre pour réinitialiser complètement la base de données.









