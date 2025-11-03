# 🔧 Résoudre l'erreur "Access denied" MySQL sur O2Switch

## ❌ Erreur

```
SQLSTATE[HY000] [1045] Access denied for user 'votre_db_user'@'localhost'
```

## 🔍 Cause

Cette erreur signifie que Laravel essaie de se connecter avec des identifiants incorrects ou des valeurs d'exemple du fichier `.env`.

## ✅ Solution

### Étape 1 : Vérifier le fichier .env

Sur O2Switch, connectez-vous en SSH :

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com
nano .env
```

### Étape 2 : Vérifier les valeurs de base de données

Cherchez les lignes suivantes dans le `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=votre_db_name
DB_USERNAME=votre_db_user
DB_PASSWORD=votre_db_password
```

⚠️ **Problème** : Les valeurs `votre_db_name`, `votre_db_user`, `votre_db_password` sont des placeholders à remplacer !

### Étape 3 : Remplacer par les vraies valeurs

Remplacez par les **vraies** informations de votre base de données O2Switch :

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=herime_account
DB_USERNAME=votre_vrai_user_mysql
DB_PASSWORD=votre_vrai_mot_de_passe
```

**Où trouver ces informations ?**

1. **Via le panneau O2Switch** :
   - Connectez-vous à votre espace client
   - Allez dans "Hébergement" → "Bases de données"
   - Vous verrez la liste avec le nom, l'utilisateur, etc.

2. **Via SSH** :
   ```bash
   # Se connecter à MySQL
   mysql -u root -p
   
   # Voir les bases de données
   SHOW DATABASES;
   
   # Voir les utilisateurs
   SELECT User, Host FROM mysql.user;
   ```

### Étape 4 : Important - Conventions O2Switch

O2Switch préfixe souvent les noms avec votre identifiant :

- Si votre identifiant est `heritiermulume`
- La base pourrait être : `heritiermulume_herime_account`
- L'utilisateur pourrait être : `heritiermulume_herime` ou `heritiermulume`

**Exemple réel** :

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=heritiermulume_herime_account
DB_USERNAME=heritiermulume_herime
DB_PASSWORD=le_mot_de_passe_que_vous_avez_créé
```

### Étape 5 : Vérifier la connexion

Après avoir modifié le `.env`, testez la connexion :

```bash
# Vérifier la connexion
php artisan db:show

# Ou tester directement avec MySQL
mysql -u votre_vrai_user_mysql -p herime_account
```

### Étape 6 : Vider le cache de configuration

Laravel met en cache la configuration, il faut la vider :

```bash
php artisan config:clear
php artisan cache:clear
```

### Étape 7 : Réessayer

```bash
php artisan migrate:status
```

---

## 🔍 Vérification étape par étape

### 1. Vérifier que le fichier .env existe

```bash
ls -la .env
cat .env | grep DB_
```

### 2. Vérifier que les valeurs ne sont pas des placeholders

```bash
# Si vous voyez encore "votre_db_user", c'est que ce n'est pas configuré
grep "votre_db" .env
```

### 3. Vérifier la connexion MySQL directe

```bash
# Tester avec les identifiants
mysql -u votre_user_mysql -p herime_account
```

Si cette commande échoue, le problème vient des identifiants MySQL, pas de Laravel.

### 4. Vérifier les permissions de l'utilisateur MySQL

```bash
mysql -u root -p

# Vérifier les privilèges
SHOW GRANTS FOR 'votre_user_mysql'@'localhost';

# Si aucun privilège, les ajouter
GRANT ALL PRIVILEGES ON herime_account.* TO 'votre_user_mysql'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 🆘 Dépannage avancé

### Erreur : "Unknown database"

```bash
# Vérifier que la base existe
mysql -u root -p -e "SHOW DATABASES LIKE 'herime_account';"

# Si elle n'existe pas, la créer
mysql -u root -p
CREATE DATABASE herime_account CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Erreur : "User doesn't exist"

```bash
mysql -u root -p

# Créer l'utilisateur
CREATE USER 'herime_user'@'localhost' IDENTIFIED BY 'mot_de_passe_fort';
GRANT ALL PRIVILEGES ON herime_account.* TO 'herime_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Vérifier que le fichier .env est bien lu

```bash
# Afficher les valeurs (sans le mot de passe)
php artisan tinker
>>> config('database.connections.mysql.database')
>>> config('database.connections.mysql.username')
# Note : le mot de passe ne s'affiche pas pour sécurité
```

---

## 📋 Checklist de résolution

- [ ] Fichier `.env` existe et est accessible
- [ ] Les valeurs `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` ne sont **pas** des placeholders
- [ ] Les identifiants correspondent à ceux créés dans O2Switch
- [ ] La base de données existe (`SHOW DATABASES;`)
- [ ] L'utilisateur MySQL existe et a les bons privilèges
- [ ] La connexion MySQL directe fonctionne (`mysql -u user -p db`)
- [ ] Le cache Laravel est vidé (`php artisan config:clear`)
- [ ] La commande `php artisan db:show` fonctionne

---

## 💡 Exemple de configuration correcte

```env
# Configuration MySQL O2Switch
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=heritiermulume_herime_account
DB_USERNAME=heritiermulume_herime
DB_PASSWORD=MonMotDePasseSecure123!
```

**Important** :
- ✅ Pas d'espaces autour du `=`
- ✅ Pas de guillemets autour des valeurs (sauf pour les chaînes avec espaces)
- ✅ Vérifier les majuscules/minuscules
- ✅ Le mot de passe doit correspondre exactement

---

## 🎯 Commandes rapides pour résoudre

```bash
# 1. Éditer le .env
nano .env

# 2. Vérifier les valeurs
grep DB_ .env

# 3. Vider le cache
php artisan config:clear

# 4. Tester la connexion
php artisan db:show

# 5. Si ça fonctionne, exécuter les migrations
php artisan migrate --force
```

---

## 📚 Ressources

- [Documentation Laravel - Configuration](https://laravel.com/docs/11.x/configuration)
- [Documentation O2Switch - Bases de données](https://www.o2switch.fr/support/)
- Consultez `CREATE_DATABASE_O2SWITCH.md` pour créer la base de données

---

**Note importante** : Si vous avez créé la base de données via le panneau O2Switch, utilisez **exactement** les identifiants fournis par O2Switch, pas ceux que vous avez peut-être créés manuellement.

