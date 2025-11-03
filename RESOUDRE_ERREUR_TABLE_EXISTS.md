# 🔧 Résoudre l'erreur "Table already exists" - OAuth

## ❌ Erreur

```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'oauth_auth_codes' already exists
```

## 🔍 Cause

Cette erreur signifie que :
1. Les tables OAuth existent déjà dans la base de données
2. Mais Laravel pense qu'elles doivent être créées (migrations pas marquées comme exécutées)

Cela arrive souvent quand :
- Les migrations ont été exécutées manuellement
- Passport a créé les tables mais les migrations ne sont pas dans la table `migrations`
- Il y a des migrations en double

## ✅ Solution 1 : Vérifier l'état des migrations

Sur O2Switch :

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# Voir l'état des migrations
php artisan migrate:status
```

Vous verrez quelles migrations sont marquées comme "Ran" et lesquelles sont "Pending".

## ✅ Solution 2 : Marquer les migrations comme exécutées

Si les tables existent déjà mais que les migrations ne sont pas marquées :

### Option A : Marquer une migration spécifique comme exécutée

```bash
# Marquer la migration oauth_auth_codes comme exécutée
php artisan migrate --pretend --force
```

Mais cette commande ne fonctionne pas toujours. Utilisez plutôt l'Option B.

### Option B : Insérer manuellement dans la table migrations

```bash
# Se connecter à MySQL
mysql -u votre_user_mysql -p herime_account

# Voir les migrations déjà exécutées
SELECT * FROM migrations;

# Insérer les migrations OAuth comme exécutées
INSERT INTO migrations (migration, batch) VALUES 
('2025_10_23_232815_create_oauth_auth_codes_table', 1),
('2025_10_23_232816_create_oauth_access_tokens_table', 1),
('2025_10_23_232817_create_oauth_refresh_tokens_table', 1),
('2025_10_23_232818_create_oauth_clients_table', 1),
('2025_10_23_232819_create_oauth_device_codes_table', 1);

# Quitter
EXIT;
```

Ensuite :

```bash
# Vérifier
php artisan migrate:status

# Réessayer les migrations
php artisan migrate --force
```

## ✅ Solution 3 : Supprimer les tables et réexécuter (si données non importantes)

⚠️ **ATTENTION** : Cette méthode supprime toutes les données OAuth !

```bash
# Se connecter à MySQL
mysql -u votre_user_mysql -p herime_account

# Supprimer les tables OAuth
DROP TABLE IF EXISTS oauth_auth_codes;
DROP TABLE IF EXISTS oauth_access_tokens;
DROP TABLE IF EXISTS oauth_refresh_tokens;
DROP TABLE IF EXISTS oauth_clients;
DROP TABLE IF EXISTS oauth_device_codes;

# Quitter
EXIT;

# Réexécuter les migrations
php artisan migrate --force
```

## ✅ Solution 4 : Vérifier les migrations en double

Sur O2Switch, vérifiez s'il y a des migrations Passport en double :

```bash
# Lister les migrations OAuth
ls -la database/migrations/*oauth*

# Vous devriez voir :
# - 2025_10_23_232815_create_oauth_auth_codes_table.php (votre migration)
# - 2025_11_03_225808_create_oauth_auth_codes_table.php (Passport, si créée)
```

Si vous voyez des migrations du type `2025_11_03_*oauth*`, ce sont des migrations créées par Passport qui sont en double.

**Supprimez-les** :

```bash
# Supprimer les migrations Passport en double
rm database/migrations/2025_11_03_*oauth*.php

# Réessayer
php artisan migrate --force
```

## ✅ Solution 5 : Vérifier que les tables existent vraiment

```bash
# Se connecter à MySQL
mysql -u votre_user_mysql -p herime_account

# Voir les tables OAuth
SHOW TABLES LIKE 'oauth_%';

# Si les tables existent, vérifier leur structure
DESCRIBE oauth_auth_codes;

# Quitter
EXIT;
```

## 🎯 Solution recommandée (étape par étape)

### Étape 1 : Vérifier l'état actuel

```bash
php artisan migrate:status
mysql -u votre_user_mysql -p herime_account -e "SHOW TABLES LIKE 'oauth_%';"
```

### Étape 2 : Si les tables existent mais pas dans migrations

```bash
mysql -u votre_user_mysql -p herime_account

# Insérer les migrations comme exécutées
INSERT INTO migrations (migration, batch) VALUES 
('2025_10_23_232815_create_oauth_auth_codes_table', 1),
('2025_10_23_232816_create_oauth_access_tokens_table', 1),
('2025_10_23_232817_create_oauth_refresh_tokens_table', 1),
('2025_10_23_232818_create_oauth_clients_table', 1),
('2025_10_23_232819_create_oauth_device_codes_table', 1);

EXIT;
```

### Étape 3 : Vérifier

```bash
php artisan migrate:status
php artisan migrate --force
```

## 🆘 Dépannage avancé

### Si la table migrations n'existe pas

```bash
php artisan migrate:install
php artisan migrate:status
```

### Si vous avez des migrations Passport en double

```bash
# Lister toutes les migrations
ls -la database/migrations/ | grep oauth

# Supprimer celles créées par Passport (date récente)
rm database/migrations/2025_11_03_*oauth*.php

# Vérifier
php artisan migrate:status
```

### Vérifier la structure des tables existantes

```bash
mysql -u votre_user_mysql -p herime_account

# Voir la structure de oauth_auth_codes
DESCRIBE oauth_auth_codes;

# Si la structure est différente de celle attendue, vous devrez peut-être
# supprimer et recréer
EXIT;
```

## 📋 Checklist de résolution

- [ ] Vérifié l'état des migrations (`php artisan migrate:status`)
- [ ] Vérifié que les tables OAuth existent (`SHOW TABLES LIKE 'oauth_%'`)
- [ ] Vérifié s'il y a des migrations en double (`ls database/migrations/*oauth*`)
- [ ] Marqué les migrations comme exécutées dans la table `migrations`
- [ ] Ou supprimé les tables et réexécuté les migrations
- [ ] Testé avec `php artisan migrate --force`

## 💡 Astuce : Commandes rapides

```bash
# Vérifier l'état
php artisan migrate:status

# Voir les tables OAuth
mysql -u votre_user_mysql -p herime_account -e "SHOW TABLES LIKE 'oauth_%';"

# Si les tables existent, les marquer comme créées
mysql -u votre_user_mysql -p herime_account << EOF
INSERT IGNORE INTO migrations (migration, batch) VALUES 
('2025_10_23_232815_create_oauth_auth_codes_table', 1),
('2025_10_23_232816_create_oauth_access_tokens_table', 1),
('2025_10_23_232817_create_oauth_refresh_tokens_table', 1),
('2025_10_23_232818_create_oauth_clients_table', 1),
('2025_10_23_232819_create_oauth_device_codes_table', 1);
EOF

# Vérifier
php artisan migrate:status
```

---

**Note** : Si vous avez déjà des données OAuth importantes, utilisez la Solution 2. Si c'est une nouvelle installation, la Solution 3 est plus rapide.

