# 🔧 Résoudre l'erreur "Vite manifest not found" en production

## ❌ Erreur

```
Vite manifest not found at: /home/muhe3594/herime-account/public/build/manifest.json
```

## 🔍 Cause

Les assets frontend (Vue.js, CSS, JS) n'ont pas été compilés et transférés en production. Le fichier `public/build/manifest.json` est manquant.

## ✅ Solution 1 : Compiler et transférer les assets (RECOMMANDÉ)

### Sur votre machine locale :

```bash
# 1. Compiler les assets
npm run build

# 2. Vérifier que le dossier build existe
ls -la public/build/

# 3. Transférer le dossier build vers O2Switch
# Format O2Switch : votre-identifiant@ssh.o2switch.net
scp -r public/build/ muhe3594@ssh.o2switch.net:/home/muhe3594/herime-account/public/
```

### Sur O2Switch, vérifier :

```bash
ssh votre-identifiant@o2switch.fr
cd /home/muhe3594/herime-account

# Vérifier que le manifest existe
ls -la public/build/manifest.json

# Si le dossier build n'existe pas, créer la structure
mkdir -p public/build
chmod -R 755 public/build
```

## ✅ Solution 2 : Compiler directement sur O2Switch (si Node.js est disponible)

```bash
ssh votre-identifiant@o2switch.fr
cd /home/muhe3594/herime-account

# Installer les dépendances Node.js
npm install --production

# Compiler les assets
npm run build

# Vérifier
ls -la public/build/manifest.json
```

## ✅ Solution 3 : Utiliser le script de déploiement automatique

Le script `deploy-assets.sh` peut être utilisé pour automatiser le transfert :

```bash
# Sur votre machine locale
./deploy-assets.sh votre-identifiant@o2switch.fr /home/muhe3594/herime-account
```

## 📋 Séquence complète de correction

### Option A : Compilation locale + Transfert SCP

```bash
# Sur votre machine locale
cd /path/to/account

# Compiler les assets
npm run build

# Transférer vers O2Switch (format: identifiant@ssh.o2switch.net)
scp -r public/build/ muhe3594@ssh.o2switch.net:/home/muhe3594/herime-account/public/

# Sur O2Switch, vérifier
ssh votre-identifiant@o2switch.fr
cd /home/muhe3594/herime-account
ls -la public/build/manifest.json
```

### Option B : Compilation sur le serveur

```bash
# Sur O2Switch
ssh votre-identifiant@o2switch.fr
cd /home/muhe3594/herime-account

# Installer Node.js si nécessaire (vérifier d'abord)
node --version
npm --version

# Si Node.js n'est pas disponible, utilisez l'Option A

# Installer les dépendances
npm install --production

# Compiler
npm run build

# Vérifier
ls -la public/build/manifest.json

# Vider le cache Laravel
php artisan view:clear
php artisan config:clear
```

## 🎯 Commandes rapides

```bash
# Compiler localement
npm run build

# Transférer le dossier build (format: identifiant@ssh.o2switch.net)
scp -r public/build/ muhe3594@ssh.o2switch.net:/home/muhe3594/herime-account/public/

# Sur O2Switch, vérifier et vider le cache
ssh utilisateur@o2switch.fr
cd /home/muhe3594/herime-account
ls public/build/manifest.json
php artisan view:clear
```

## ⚠️ Important

1. **Ne pas commit `public/build/`** - Ce dossier est dans `.gitignore` et doit être compilé localement ou sur le serveur
2. **Toujours compiler en production** avant de déployer : `npm run build`
3. **Vérifier les permissions** du dossier `public/build/` : `chmod -R 755 public/build`
4. **Vider le cache Laravel** après le transfert : `php artisan view:clear`

## 🔄 Intégration dans le workflow de déploiement

Modifiez votre script de déploiement pour inclure la compilation des assets :

```bash
# Dans deploy-o2switch.sh ou votre script personnalisé
npm run build
# Puis continuez avec git pull, composer install, etc.
```

## 📚 Ressources

- Consultez `DEPLOY_SANS_NPM.md` pour plus de détails sur le déploiement sans Node.js
- Documentation Vite : https://vitejs.dev/

