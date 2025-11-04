# 🔧 Résoudre l'erreur "Table already exists" en production

## ❌ Erreur

```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'oauth_auth_codes' already exists
```

## 🔍 Cause

Les tables OAuth existent déjà dans la base de données, mais Passport essaie de créer les migrations depuis le vendor directory lors de l'exécution de `php artisan migrate`.

## ✅ Solution : Marquer les migrations OAuth comme exécutées

### Sur O2Switch :

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# Se connecter à MySQL
mysql -u votre_user_mysql -p herime_account
```

Puis exécutez ces commandes SQL :

```sql
-- Vérifier les migrations existantes
SELECT * FROM migrations WHERE migration LIKE '%oauth%';

-- Si les migrations OAuth ne sont pas dans la table, les ajouter
INSERT IGNORE INTO migrations (migration, batch) VALUES 
('2016_06_01_000001_create_oauth_auth_codes_table', 1),
('2016_06_01_000002_create_oauth_access_tokens_table', 1),
('2016_06_01_000003_create_oauth_refresh_tokens_table', 1),
('2016_06_01_000004_create_oauth_clients_table', 1),
('2024_06_01_000001_create_oauth_device_codes_table', 1);

-- Vérifier
SELECT * FROM migrations WHERE migration LIKE '%oauth%';

-- Quitter
EXIT;
```

### Vérifier et réessayer :

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

# 2. Marquer les migrations OAuth comme exécutées
mysql -u votre_user_mysql -p herime_account << EOF
INSERT IGNORE INTO migrations (migration, batch) VALUES 
('2016_06_01_000001_create_oauth_auth_codes_table', 1),
('2016_06_01_000002_create_oauth_access_tokens_table', 1),
('2016_06_01_000003_create_oauth_refresh_tokens_table', 1),
('2016_06_01_000004_create_oauth_clients_table', 1),
('2024_06_01_000001_create_oauth_device_codes_table', 1);
EOF

# 3. Vérifier
php artisan migrate:status

# 4. Réessayer les migrations
php artisan migrate --force
```
