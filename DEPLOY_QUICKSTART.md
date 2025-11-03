# Guide Rapide de Déploiement sur O2Switch

## 🚀 Déploiement Automatique (Recommandé)

### 1. Connexion SSH à O2Switch

```bash
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com  # Remplacer par votre chemin
```

### 2. Cloner le projet depuis GitHub

```bash
# Vider le répertoire s'il contient déjà quelque chose
rm -rf * .*

# Cloner le repository
git clone https://github.com/heritiermulume/herime-account.git .

# Rendre le script de déploiement exécutable
chmod +x deploy-o2switch.sh
```

### 3. Configurer les variables d'environnement

```bash
# Copier le fichier d'exemple
cp env.o2switch.example .env

# Éditer avec vos informations
nano .env  # ou utiliser l'éditeur de votre choix
```

**Variables importantes à configurer :**
- `APP_URL` : Votre domaine complet (https://votre-domaine.com)
- `DB_DATABASE` : Nom de la base MySQL fournie par O2Switch
- `DB_USERNAME` : Utilisateur MySQL
- `DB_PASSWORD` : Mot de passe MySQL
- `MAIL_*` : Configuration email O2Switch

### 4. Exécuter le script de déploiement

```bash
./deploy-o2switch.sh
```

Le script va automatiquement :
- ✅ Vérifier les prérequis
- ✅ Créer une sauvegarde
- ✅ Installer les dépendances PHP
- ✅ Compiler les assets (si Node.js est disponible)
- ✅ Configurer l'environnement
- ✅ Exécuter les migrations
- ✅ Installer Passport
- ✅ Créer l'administrateur par défaut
- ✅ Optimiser l'application
- ✅ Configurer les permissions

### 5. Tester l'application

Ouvrez votre navigateur et visitez : `https://votre-domaine.com`

**Identifiants admin par défaut :**
- Email: `admin@example.com`
- Mot de passe: `password`

⚠️ **IMPORTANT** : Changez ces identifiants immédiatement !

## 🛠️ Déploiement Manuel (Si le script ne fonctionne pas)

### 1. Installer les dépendances PHP

```bash
composer install --no-dev --optimize-autoloader
```

Si Composer n'est pas disponible sur le serveur :
```bash
# Sur votre machine locale
composer install --no-dev --optimize-autoloader --no-scripts

# Transférer via SCP
scp -r vendor/ votre-identifiant@o2switch.fr:www/votre-domaine.com/
```

### 2. Compiler les assets frontend

**Option A : Sur le serveur (si Node.js disponible)**
```bash
npm install --production
npm run build
```

**Option B : Localement et transférer**
```bash
# Sur votre machine locale
npm install
npm run build

# Transférer via SCP
scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-domaine.com/public/
```

### 3. Configuration

```bash
php artisan key:generate
php artisan migrate --force
php artisan passport:install --force
php artisan db:seed --force
```

### 4. Optimiser

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 5. Permissions

```bash
chmod -R 755 storage bootstrap/cache public
chown -R www-data:www-data storage bootstrap/cache public
```

## 🔄 Mises à jour futures

Pour mettre à jour l'application après un push sur GitHub :

```bash
# Se connecter en SSH
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# Récupérer les modifications
git pull origin main

# Mettre à jour les dépendances
composer install --no-dev --optimize-autoloader

# Exécuter les migrations
php artisan migrate --force

# Recompiler si nécessaire
npm run build  # ou transférer depuis local

# Re-optimiser
php artisan optimize
```

## 🐛 Dépannage

### Erreur : "Composer not found"
**Solution** : Installez Composer sur le serveur ou transférez le dossier `vendor` depuis votre machine locale.

### Erreur : "Node.js not found"
**Solution** : Compilez les assets localement avec `npm run build` et transférez `public/build`.

### Erreur : "500 Internal Server Error"
**Solution** :
1. Vérifiez les logs : `tail -f storage/logs/laravel.log`
2. Vérifiez les permissions : `chmod -R 755 storage bootstrap/cache`
3. Vérifiez que `.env` est correctement configuré

### Erreur : "SQLSTATE[HY000] [2002] Connection refused"
**Solution** : Vérifiez la configuration de la base de données dans `.env` :
- `DB_HOST=localhost` (ou l'hôte fourni par O2Switch)
- Identifiants MySQL corrects

### Erreur : "Routes 404"
**Solution** :
1. Vérifiez que le DocumentRoot pointe vers `/public`
2. Vérifiez que `.htaccess` existe dans `/public`
3. Exécutez : `php artisan route:cache`

### Assets non chargés
**Solution** :
1. Vérifiez que `public/build` contient les fichiers compilés
2. Videz le cache : `php artisan view:clear`
3. Recompilez : `npm run build` (ou transférez depuis local)

## 📞 Support

Si vous rencontrez des problèmes :
1. Consultez les logs : `storage/logs/laravel.log`
2. Vérifiez les logs du serveur web (via le panneau O2Switch)
3. Contactez le support O2Switch
4. Vérifiez la [Documentation Laravel](https://laravel.com/docs/11.x)

## 📝 Checklist de déploiement

- [ ] Code cloné depuis GitHub
- [ ] Fichier `.env` configuré avec les bonnes variables
- [ ] Base de données créée et accessible
- [ ] Dépendances PHP installées (`vendor/` présent)
- [ ] Assets compilés (`public/build/` présent)
- [ ] Migrations exécutées
- [ ] Passport installé
- [ ] Administrateur créé
- [ ] Permissions configurées
- [ ] Application optimisée
- [ ] HTTPS/SSL configuré
- [ ] Test de connexion réussi
- [ ] Mot de passe admin modifié

## 🔗 Liens utiles

- [GitHub Repository](https://github.com/heritiermulume/herime-account)
- [Documentation Laravel](https://laravel.com/docs/11.x/deployment)
- [Laravel Passport](https://laravel.com/docs/11.x/passport)
- [Support O2Switch](https://www.o2switch.fr/support/)

