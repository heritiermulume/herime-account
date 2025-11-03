# 📊 Créer la base de données MySQL sur O2Switch

## 🎯 Objectif

Créer la base de données `herime_account` sur votre serveur O2Switch.

---

## 📋 Méthode 1 : Via le panneau O2Switch (Recommandée)

### Étape 1 : Accéder au panneau O2Switch

1. Connectez-vous à votre espace client O2Switch : https://www.o2switch.fr/
2. Allez dans **"Mon espace"** → **"Hébergement"**
3. Sélectionnez votre hébergement
4. Cliquez sur **"Bases de données"** ou **"MySQL"**

### Étape 2 : Créer la base de données

1. Cliquez sur **"Créer une base de données"** ou **"Ajouter"**
2. Remplissez les informations :
   - **Nom de la base** : `herime_account`
   - **Utilisateur** : (généralement préfixé avec votre identifiant, ex: `identifiant_herime`)
   - **Mot de passe** : Créez un mot de passe fort
   - **Encodage** : `utf8mb4_unicode_ci` (recommandé)

3. Cliquez sur **"Créer"** ou **"Valider"**

### Étape 3 : Noter les informations

Notez précieusement :
- **Nom de la base** : `herime_account` (ou `identifiant_herime_account`)
- **Utilisateur** : `identifiant_herime`
- **Mot de passe** : (celui que vous avez créé)
- **Hôte** : Généralement `localhost` ou `127.0.0.1`

⚠️ **Important** : Ces informations seront nécessaires pour configurer votre fichier `.env`

---

## 🔧 Méthode 2 : Via SSH (Ligne de commande)

### Étape 1 : Se connecter en SSH

```bash
ssh votre-identifiant@o2switch.fr
```

### Étape 2 : Se connecter à MySQL

```bash
mysql -u root -p
```

Ou si vous avez un utilisateur MySQL spécifique :

```bash
mysql -u votre_user_mysql -p
```

### Étape 3 : Créer la base de données

Une fois connecté à MySQL, exécutez :

```sql
CREATE DATABASE herime_account CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Étape 4 : Créer un utilisateur (si nécessaire)

```sql
CREATE USER 'herime_user'@'localhost' IDENTIFIED BY 'votre_mot_de_passe_fort';
GRANT ALL PRIVILEGES ON herime_account.* TO 'herime_user'@'localhost';
FLUSH PRIVILEGES;
```

### Étape 5 : Vérifier

```sql
SHOW DATABASES LIKE 'herime_account';
```

Vous devriez voir la base de données dans la liste.

### Étape 6 : Quitter MySQL

```sql
EXIT;
```

---

## 🔧 Méthode 3 : Via phpMyAdmin

### Étape 1 : Accéder à phpMyAdmin

1. Connectez-vous au panneau O2Switch
2. Trouvez **phpMyAdmin** dans les outils disponibles
3. Cliquez pour ouvrir phpMyAdmin

### Étape 2 : Créer la base de données

1. Dans le menu de gauche, cliquez sur **"Nouvelle base de données"** ou **"New"**
2. Remplissez :
   - **Nom de la base** : `herime_account`
   - **Interclassement** : `utf8mb4_unicode_ci`
3. Cliquez sur **"Créer"** ou **"Create"**

### Étape 3 : Créer un utilisateur (si nécessaire)

1. Allez dans l'onglet **"Utilisateurs"** ou **"Users"**
2. Cliquez sur **"Ajouter un utilisateur"** ou **"Add user"**
3. Remplissez les informations :
   - **Nom d'utilisateur** : `herime_user`
   - **Hôte** : `localhost`
   - **Mot de passe** : (générer un mot de passe fort)
4. Dans **"Privilèges pour la base de données"**, sélectionnez `herime_account`
5. Cochez **"Tous les privilèges"** ou **"ALL PRIVILEGES"**
6. Cliquez sur **"Exécuter"** ou **"Go"**

---

## ⚙️ Configuration dans le fichier .env

Une fois la base de données créée, mettez à jour votre fichier `.env` sur O2Switch :

```bash
# Sur O2Switch, éditer le .env
nano .env
```

Configurez :

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=herime_account
DB_USERNAME=votre_user_mysql
DB_PASSWORD=votre_mot_de_passe
```

⚠️ **Important** : Remplacez `votre_user_mysql` et `votre_mot_de_passe` par les valeurs réelles fournies par O2Switch.

---

## ✅ Vérification

### Test de connexion depuis SSH

```bash
# Sur O2Switch
cd www/votre-domaine.com
php artisan db:show
```

Vous devriez voir :
```
MySQL ........................................................ 9.3.0
Connection ................................................... mysql
Database ............................................. herime_account
Host ........................................................ localhost
```

### Test de connexion directe MySQL

```bash
mysql -u votre_user_mysql -p herime_account
```

Si la connexion réussit, vous êtes connecté à la base de données.

---

## 🚀 Exécuter les migrations

Une fois la base de données créée et configurée :

```bash
# Sur O2Switch
cd www/votre-domaine.com
php artisan migrate --force
```

Cela va créer toutes les tables nécessaires dans `herime_account`.

---

## 📝 Notes importantes

### Conventions de nommage O2Switch

O2Switch préfixe souvent les noms de bases de données avec votre identifiant :
- Si votre identifiant est `heritiermulume`
- La base pourrait être : `heritiermulume_herime_account`
- Vérifiez dans le panneau d'administration

### Encodage

Utilisez toujours `utf8mb4_unicode_ci` pour :
- ✅ Support des emojis
- ✅ Support de tous les caractères Unicode
- ✅ Compatibilité avec Laravel

### Sécurité

- ✅ Utilisez un mot de passe fort (minimum 12 caractères)
- ✅ Ne partagez jamais les identifiants
- ✅ Utilisez `localhost` comme hôte (pas d'accès externe)
- ✅ Limitez les privilèges de l'utilisateur à la base spécifique

---

## 🆘 Dépannage

### Erreur : "Access denied"

```bash
# Vérifier les identifiants
mysql -u votre_user -p

# Vérifier les privilèges
mysql -u root -p
SHOW GRANTS FOR 'votre_user'@'localhost';
```

### Erreur : "Database doesn't exist"

```sql
-- Vérifier que la base existe
SHOW DATABASES;

-- Si elle n'existe pas, la créer
CREATE DATABASE herime_account CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Erreur : "Table doesn't exist"

```bash
# Exécuter les migrations
php artisan migrate --force
```

---

## 📚 Ressources

- [Documentation O2Switch - Bases de données](https://www.o2switch.fr/support/)
- [Documentation Laravel - Base de données](https://laravel.com/docs/11.x/database)
- [Documentation MySQL - CREATE DATABASE](https://dev.mysql.com/doc/refman/8.0/en/create-database.html)

---

## ✅ Checklist

Avant de continuer :

- [ ] Base de données `herime_account` créée
- [ ] Utilisateur MySQL créé avec les bons privilèges
- [ ] Identifiants notés et sécurisés
- [ ] Fichier `.env` configuré avec les bonnes valeurs
- [ ] Test de connexion réussi (`php artisan db:show`)
- [ ] Migrations exécutées (`php artisan migrate --force`)

Une fois tout cela fait, votre application Laravel pourra utiliser la base de données MySQL sur O2Switch ! 🎉

