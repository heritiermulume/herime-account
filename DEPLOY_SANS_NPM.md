# Guide de Déploiement Sans Node.js/NPM (O2Switch)

## ⚠️ Problème

O2Switch n'inclut pas Node.js/NPM par défaut, donc vous ne pouvez pas compiler les assets frontend directement sur le serveur.

## ✅ Solution : Compiler Localement et Transférer

### Étape 1 : Compiler les assets sur votre machine locale

Sur **votre ordinateur** :

```bash
# Se placer dans le projet
cd /Users/heritiermulume/Autres/Herime/Projets/Web/account

# Installer les dépendances Node.js (si pas déjà fait)
npm install

# Compiler les assets pour la production
npm run build
```

Cette commande va créer un dossier `public/build/` avec tous les fichiers compilés.

### Étape 2 : Transférer les assets sur O2Switch

#### Option A : Via SCP (recommandée)

```bash
# Depuis votre machine locale
scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-domaine.com/public/
```

#### Option B : Via FTP

1. Connectez-vous à votre compte FTP O2Switch
2. Naviguez vers `www/votre-domaine.com/public/`
3. Transférez le contenu du dossier `public/build/`

#### Option C : Via le panneau O2Switch

Utilisez le gestionnaire de fichiers de votre panneau d'administration pour téléverser les fichiers.

### Étape 3 : Vérifier sur le serveur

```bash
# Se connecter en SSH
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com/public

# Vérifier que les fichiers sont présents
ls -la build/
```

Vous devriez voir :
```
build/
├── manifest.json
├── assets/
│   ├── app-*.js
│   ├── app-*.css
│   └── ...
```

## 🔄 Mise à jour après modifications Frontend

À chaque fois que vous modifiez des fichiers Vue.js ou CSS :

### Sur votre machine locale

```bash
# 1. Compiler
npm run build

# 2. Transférer sur O2Switch
scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-domaine.com/public/
```

### Sur O2Switch (SSH)

```bash
# Récupérer les mises à jour du code
git pull origin main

# Transférer les nouveaux assets depuis votre machine
# (répéter l'étape 2 ci-dessus)
```

## 📝 Procédure Complète de Déploiement Sans NPM

### Sur votre machine (Préparation)

```bash
# 1. Compiler les assets
npm run build

# 2. Installer les dépendances PHP et préparer le transfert
composer install --no-dev --optimize-autoloader --no-scripts

# Créer une archive temporaire (optionnel)
tar -czf deploy-assets.tar.gz public/build/
tar -czf deploy-vendor.tar.gz vendor/
```

### Sur O2Switch (Déploiement)

```bash
# 1. Se connecter
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# 2. Cloner ou mettre à jour le code
git clone https://github.com/heritiermulume/herime-account.git .
# ou: git pull origin main

# 3. Transférer vendor/ (si besoin)
# Depuis votre machine locale :
# scp -r vendor/ votre-identifiant@o2switch.fr:www/votre-domaine.com/

# 4. Créer et configurer .env
cp env.o2switch.example .env
nano .env  # Éditer avec vos informations

# 5. Configurer les permissions
chmod -R 755 storage bootstrap/cache public

# 6. Générer la clé
php artisan key:generate

# 7. Exécuter les migrations
php artisan migrate --force

# 8. Installer Passport
php artisan passport:install --force

# 9. Créer l'admin
php artisan db:seed --force

# 10. Optimiser
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Retour sur votre machine (Transférer les assets)

```bash
# Transférer public/build/
scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-domaine.com/public/
```

### Vérification sur O2Switch

```bash
# Tester l'application
curl https://votre-domaine.com

# Vérifier les logs si erreur
tail -f storage/logs/laravel.log
```

## 🎯 Script Automatique pour le Transfert

Créez un fichier `deploy-assets.sh` sur votre machine locale :

```bash
#!/bin/bash

echo "🚀 Compilation des assets..."
npm run build

echo "📤 Transfert sur O2Switch..."
scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-domaine.com/public/

echo "✅ Assets transférés avec succès!"
```

Rendez-le exécutable :

```bash
chmod +x deploy-assets.sh
```

Utilisation :

```bash
./deploy-assets.sh
```

## 🔧 Alternative : Utiliser un Service de CI/CD

Pour automatiser complètement, vous pouvez configurer GitHub Actions :

1. Créer `.github/workflows/deploy.yml`
2. Configurer la compilation automatique des assets
3. Transférer automatiquement sur O2Switch après chaque push

## ⚠️ Points Importants

1. **Ne jamais commiter `public/build/`** : Ce dossier est dans `.gitignore` et doit être généré localement

2. **Vérifier la taille** : Le dossier `public/build/` fait ~150-200 KB une fois compressé

3. **Compression gzip** : O2Switch active généralement la compression gzip automatiquement

4. **Cache des navigateurs** : Après mise à jour, vider le cache du navigateur (Ctrl+F5)

## 📋 Checklist de Déploiement

- [ ] Compiler les assets localement (`npm run build`)
- [ ] Vérifier que `public/build/` existe et contient les fichiers
- [ ] Transférer `public/build/` sur O2Switch
- [ ] Vérifier les permissions (`chmod 755 public/build`)
- [ ] Tester l'application dans le navigateur
- [ ] Vérifier que les CSS et JS se chargent correctement
- [ ] Vérifier la console du navigateur pour les erreurs

## 🆘 Dépannage

### Les assets ne se chargent pas

```bash
# Sur O2Switch, vérifier les permissions
chmod -R 755 public/build

# Vérifier que les fichiers existent
ls -la public/build/assets/
```

### Erreur 404 sur les assets

```bash
# Vérifier que public/build/manifest.json existe
ls -la public/build/manifest.json

# Vérifier le contenu
cat public/build/manifest.json
```

### Assets obsolètes

```bash
# Recompiler sur local
npm run build

# Retransférer
scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-domaine.com/public/
```

## 🔗 Ressources

- [Documentation Laravel - Assets](https://laravel.com/docs/11.x/mix)
- [Documentation Vite](https://vitejs.dev/)
- [Guide O2Switch](./DEPLOY_O2SWITCH.md)

