# 🔧 Corriger les migrations Passport en double

## ❌ Erreur

```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'oauth_auth_codes' already exists
Migration: 2025_11_03_163923_create_oauth_auth_codes_table
```

## 🔍 Cause

Passport a créé automatiquement des migrations en double (date `2025_11_03`) alors que les migrations OAuth originales existent déjà (date `2025_10_23`).

## ✅ Solution : Supprimer les migrations Passport en double

### Sur O2Switch :

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# Option 1 : Utiliser le script automatique
./supprimer-migrations-passport-dupliquees.sh

# Option 2 : Supprimer manuellement
rm database/migrations/2025_11_03_*oauth*.php
```

### Vérifier les migrations restantes :

```bash
ls -la database/migrations/*oauth*
```

Vous devriez voir uniquement les migrations du `2025_10_23` :
- `2025_10_23_232815_create_oauth_auth_codes_table.php`
- `2025_10_23_232816_create_oauth_access_tokens_table.php`
- `2025_10_23_232817_create_oauth_refresh_tokens_table.php`
- `2025_10_23_232818_create_oauth_clients_table.php`
- `2025_10_23_232819_create_oauth_device_codes_table.php`

## ✅ Solution : Marquer les migrations comme exécutées

Si les tables OAuth existent déjà dans la base de données, marquez les migrations comme exécutées :

```bash
# Se connecter à MySQL
mysql -u votre_user_mysql -p herime_account
```

Puis exécutez :

```sql
-- Vérifier les migrations existantes
SELECT * FROM migrations WHERE migration LIKE '%oauth%';

-- Si les migrations OAuth ne sont pas dans la table, les ajouter
INSERT IGNORE INTO migrations (migration, batch) VALUES 
('2025_10_23_232815_create_oauth_auth_codes_table', 1),
('2025_10_23_232816_create_oauth_access_tokens_table', 1),
('2025_10_23_232817_create_oauth_refresh_tokens_table', 1),
('2025_10_23_232818_create_oauth_clients_table', 1),
('2025_10_23_232819_create_oauth_device_codes_table', 1);

-- Quitter
EXIT;
```

## ✅ Vérifier et réessayer

```bash
# Vérifier l'état des migrations
php artisan migrate:status

# Réessayer les migrations
php artisan migrate --force
```

## 📋 Séquence complète de correction

```bash
# 1. Se connecter
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# 2. Supprimer les migrations Passport en double
rm database/migrations/2025_11_03_*oauth*.php

# 3. Vérifier les migrations restantes
ls -la database/migrations/*oauth*

# 4. Marquer les migrations OAuth comme exécutées (si les tables existent)
mysql -u votre_user_mysql -p herime_account << EOF
INSERT IGNORE INTO migrations (migration, batch) VALUES 
('2025_10_23_232815_create_oauth_auth_codes_table', 1),
('2025_10_23_232816_create_oauth_access_tokens_table', 1),
('2025_10_23_232817_create_oauth_refresh_tokens_table', 1),
('2025_10_23_232818_create_oauth_clients_table', 1),
('2025_10_23_232819_create_oauth_device_codes_table', 1);
EOF

# 5. Vérifier
php artisan migrate:status

# 6. Réessayer
php artisan migrate --force
```

## 🎯 Commandes rapides

```bash
# Supprimer toutes les migrations Passport en double
find database/migrations -name "*2025_11_*oauth*.php" -delete

# Vérifier
php artisan migrate:status

# Si les tables existent, les marquer comme créées
mysql -u votre_user_mysql -p herime_account -e "INSERT IGNORE INTO migrations (migration, batch) VALUES ('2025_10_23_232815_create_oauth_auth_codes_table', 1), ('2025_10_23_232816_create_oauth_access_tokens_table', 1), ('2025_10_23_232817_create_oauth_refresh_tokens_table', 1), ('2025_10_23_232818_create_oauth_clients_table', 1), ('2025_10_23_232819_create_oauth_device_codes_table', 1);"
```

## ⚠️ Prévention

Pour éviter que Passport crée de nouvelles migrations en double :

1. **Ne pas exécuter** `php artisan passport:install` si les migrations OAuth existent déjà
2. **Vérifier** avant d'installer Passport : `ls database/migrations/*oauth*`
3. Si les migrations OAuth existent, utiliser uniquement : `php artisan passport:keys --force`

## 📚 Ressources

- Consultez `RESOUDRE_ERREUR_TABLE_EXISTS.md` pour plus de détails
- Documentation Laravel Passport : https://laravel.com/docs/11.x/passport

