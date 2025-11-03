# Guide de Déploiement sur O2Switch

Ce guide vous explique comment déployer votre application Laravel + Vue.js sur O2Switch depuis GitHub.

## 📋 Prérequis

- Un compte O2Switch avec accès SSH
- Un domaine configuré (ou sous-domaine)
- PHP 8.2+ (vérifiez la version disponible)
- MySQL/MariaDB
- Git
- Accès au FTP/SSH pour les fichiers

## 🚀 Étape 1 : Préparation sur votre machine locale

### 1.1 Vérifier que tout est sur GitHub

```bash
git status
git push origin main
```

### 1.2 Créer un fichier .env.exemple pour la production

Créez un fichier `.env.o2switch.example` avec les variables d'environnement adaptées à o2switch.

## 🔧 Étape 2 : Connexion à O2Switch

### 2.1 Se connecter en SSH

```bash
ssh votre-identifiant@o2switch.fr
```

Ou via OVH/autre hébergeur si O2Switch est votre revendeur.

### 2.2 Naviguer vers le répertoire de votre site

```bash
cd www/votre-site.com  # Ou le chemin fourni par O2Switch
```

## 📥 Étape 3 : Cloner le projet depuis GitHub

### 3.1 Cloner le repository

```bash
git clone https://github.com/heritiermulume/herime-account.git .
```

⚠️ **Attention** : Le `.` à la fin clone directement dans le répertoire courant.

### 3.2 Vérifier les branches

```bash
git branch -a
git checkout main
```

## 🛠️ Étape 4 : Configuration de l'application

### 4.1 Créer le fichier .env

```bash
cp .env.o2switch.example .env
nano .env  # ou vi .env
```

### 4.2 Configurer les variables d'environnement

Éditez le fichier `.env` avec les bonnes valeurs :

```env
APP_NAME="HERIME Account"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://votre-domaine.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Base de données O2Switch
DB_CONNECTION=mysql
DB_HOST=localhost  # Ou l'hôte fourni par O2Switch
DB_PORT=3306
DB_DATABASE=votre_db_name
DB_USERNAME=votre_db_user
DB_PASSWORD=votre_db_password

# Redis (si disponible sur O2Switch, sinon utiliser 'file')
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Mail (configuration SMTP O2Switch)
MAIL_MAILER=smtp
MAIL_HOST=smtp.o2switch.net
MAIL_PORT=587
MAIL_USERNAME=votre-email@votre-domaine.com
MAIL_PASSWORD=votre-mot-de-passe-mail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@votre-domaine.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 4.3 Générer la clé d'application

```bash
php artisan key:generate
```

### 4.4 Installer les dépendances Composer

```bash
composer install --no-dev --optimize-autoloader
```

**Note** : Si Composer n'est pas installé sur le serveur, vous devez soit :
- Le télécharger localement
- Installer les dépendances sur votre machine et transférer le dossier `vendor`

Option 2 (recommandée) :
```bash
# Sur votre machine locale
composer install --no-dev --optimize-autoloader --no-scripts

# Puis transférer via FTP/SCP
scp -r vendor/ votre-identifiant@o2switch.fr:www/votre-site.com/
```

## 🎨 Étape 5 : Compiler les assets frontend

### Option A : Compiler sur le serveur (si Node.js est disponible)

```bash
npm install --production
npm run build
```

### Option B : Compiler localement et transférer (recommandée)

Sur votre machine locale :
```bash
npm install
npm run build
```

Puis transférer les fichiers compilés :
```bash
scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-site.com/public/
```

## 🗄️ Étape 6 : Configuration de la base de données

### 6.1 Créer la base de données

Via le panneau O2Switch (phpMyAdmin ou interface MySQL) :
1. Créer une nouvelle base de données
2. Créer un utilisateur MySQL
3. Accorder tous les droits à cet utilisateur sur la base

### 6.2 Exécuter les migrations

```bash
php artisan migrate --force
```

### 6.3 Installer Passport

```bash
php artisan passport:install --force
```

### 6.4 Créer les clients OAuth

```bash
php artisan passport:client --personal --name="Personal Access Client"
php artisan passport:client --password --name="Password Grant Client"
```

### 6.5 Créer l'administrateur par défaut

```bash
php artisan db:seed --class=DatabaseSeeder
```

## ⚙️ Étape 7 : Configuration du serveur web

### 7.1 Configuration Apache (si applicable)

Créez un fichier `.htaccess` dans le répertoire racine du site :

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 7.2 Définir le DocumentRoot

Dans votre configuration Apache (accessible via le panneau O2Switch) :
- DocumentRoot : `/www/votre-site.com/public`

## 🔐 Étape 8 : Configuration des permissions

```bash
# Donner les bonnes permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache public
```

**Note** : L'utilisateur peut varier selon la configuration O2Switch. Vérifiez avec votre hébergeur.

## 🚀 Étape 9 : Optimisation pour la production

```bash
# Cacher la configuration
php artisan config:cache

# Cacher les routes
php artisan route:cache

# Cacher les vues
php artisan view:cache

# Optimisation générale
php artisan optimize
```

## 🔄 Étape 10 : Mise à jour automatique via GitHub (optionnel)

Créez un script `deploy.sh` sur le serveur :

```bash
nano deploy.sh
```

Contenu du script :
```bash
#!/bin/bash
echo "🚀 Déploiement en cours..."
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo "✅ Déploiement terminé"
```

Rendre le script exécutable :
```bash
chmod +x deploy.sh
```

## 🔍 Étape 11 : Configuration HTTPS/SSL

### Via Let's Encrypt (automatique via O2Switch)

Configurez le certificat SSL via le panneau O2Switch.

## ✅ Vérification

### 11.1 Test de l'application

1. Visitez `https://votre-domaine.com`
2. Tentez de vous connecter avec le compte admin créé
3. Vérifiez que l'API fonctionne

### 11.2 Vérifier les logs en cas d'erreur

```bash
tail -f storage/logs/laravel.log
```

## 🔧 Étape 12 : Configuration des cron jobs (si nécessaire)

Si vous utilisez des tâches planifiées, configurez un cron job :

```bash
crontab -e
```

Ajoutez :
```
* * * * * cd /www/votre-site.com && php artisan schedule:run >> /dev/null 2>&1
```

## 🐛 Dépannage

### Problème : "Composer not found"
**Solution** : Installez Composer manuellement ou compilez localement et transférez `vendor`.

### Problème : "Node.js not found"
**Solution** : Compilez les assets sur votre machine locale et transférez `public/build`.

### Problème : "Permission denied" sur storage
**Solution** : 
```bash
chmod -R 775 storage bootstrap/cache
```

### Problème : Routes 404
**Solution** : Vérifiez que le DocumentRoot pointe vers `/public` et que `.htaccess` est correct.

### Problème : Base de données non accessible
**Solution** : Vérifiez les identifiants dans `.env` et que l'hôte MySQL est correct.

## 📝 Mise à jour de l'application

Pour mettre à jour l'application après un push sur GitHub :

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## 🔗 Ressources

- [Documentation Laravel - Déploiement](https://laravel.com/docs/11.x/deployment)
- [Support O2Switch](https://www.o2switch.fr/support/)
- [Laravel Passport](https://laravel.com/docs/11.x/passport)

## 📞 Support

En cas de problème, vérifiez :
1. Les logs Laravel : `storage/logs/laravel.log`
2. Les logs du serveur web (Apache/Nginx)
3. Les erreurs PHP dans le panneau O2Switch

