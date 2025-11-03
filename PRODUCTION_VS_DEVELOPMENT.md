# 🔄 Développement vs Production - Guide Complet

## 📋 Vue d'ensemble

### En Développement (Local)
- **2 serveurs nécessaires** : Laravel + Vite
- **Laravel** : `php artisan serve` (port 8000)
- **Vite** : `npm run dev` (port 5173)
- **Assets** : Compilés en temps réel par Vite

### En Production (O2Switch)
- **1 seul serveur** : Laravel (via Nginx/Apache)
- **Assets** : Pré-compilés une fois avec `npm run build`
- **Pas besoin de Vite** : Les assets sont servis depuis `public/build/`

---

## 🔍 Comment ça fonctionne ?

### Laravel détecte automatiquement l'environnement

Laravel utilise le helper `@vite()` dans `welcome.blade.php` qui :

1. **En développement** (`APP_ENV=local`) :
   - Se connecte au serveur Vite sur `localhost:5173`
   - Charge les assets en temps réel (hot reload)

2. **En production** (`APP_ENV=production`) :
   - Vérifie si `public/build/manifest.json` existe
   - Si oui : charge les assets compilés depuis `public/build/`
   - Si non : erreur (assets manquants)

---

## 🚀 Déploiement sur O2Switch

### Approche recommandée : Compiler localement puis transférer

**Étape 1 : Compiler les assets sur votre machine locale**

```bash
cd /Users/heritiermulume/Autres/Herime/Projets/Web/account

# Compiler les assets pour la production
npm run build
```

Cela crée le dossier `public/build/` avec :
- `manifest.json` : Liste des fichiers compilés
- `assets/` : Fichiers JS et CSS minifiés et optimisés

**Étape 2 : Transférer sur O2Switch**

```bash
# Depuis votre machine locale
scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-domaine.com/public/
```

**Étape 3 : Configurer le .env sur O2Switch**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com
```

**Étape 4 : Optimiser Laravel**

```bash
# Sur O2Switch
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 📝 Script de déploiement automatisé

### Script local : `deploy-assets.sh`

Créez ce fichier sur votre machine locale :

```bash
#!/bin/bash

echo "🚀 Compilation des assets pour la production..."
npm run build

if [ $? -eq 0 ]; then
    echo "✅ Assets compilés avec succès"
    echo ""
    echo "📤 Transférez maintenant les assets sur O2Switch :"
    echo "   scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-domaine.com/public/"
else
    echo "❌ Erreur lors de la compilation"
    exit 1
fi
```

### Utilisation

```bash
chmod +x deploy-assets.sh
./deploy-assets.sh
```

---

## 🔄 Mise à jour après modifications Frontend

Quand vous modifiez des fichiers Vue.js ou CSS :

### Sur votre machine locale :

```bash
# 1. Compiler les nouveaux assets
npm run build

# 2. Transférer sur O2Switch
scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-domaine.com/public/
```

### Sur O2Switch (si nécessaire) :

```bash
# Vider le cache des vues
php artisan view:clear
```

---

## ⚙️ Configuration O2Switch

### Fichier `.env` sur O2Switch

```env
APP_NAME="HERIME Account"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com

# Base de données
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=herime_account
DB_USERNAME=votre_user
DB_PASSWORD=votre_password
```

### Vérification

```bash
# Sur O2Switch, vérifier que les assets sont présents
ls -la public/build/

# Vous devriez voir :
# - manifest.json
# - assets/ (dossier avec les fichiers JS/CSS)
```

---

## 🆘 Dépannage

### Les assets ne se chargent pas en production

**Vérifier :**
```bash
# 1. Les fichiers existent-ils ?
ls -la public/build/manifest.json

# 2. Les permissions sont-elles correctes ?
chmod -R 755 public/build

# 3. APP_ENV est-il en production ?
grep APP_ENV .env
```

**Solution :**
```bash
# Recompiler localement
npm run build

# Retransférer
scp -r public/build/ votre-identifiant@o2switch.fr:www/votre-domaine.com/public/
```

### Erreur "Vite manifest not found"

Cela signifie que `public/build/manifest.json` est absent.

**Solution :** Compiler les assets et les transférer (voir ci-dessus).

### Erreur 404 sur les assets

**Vérifier :**
- Les fichiers sont dans `public/build/assets/`
- Les permissions sont correctes (755)
- Le serveur web (Nginx/Apache) peut accéder au dossier `public/`

---

## 📊 Comparaison

| Aspect | Développement | Production |
|--------|--------------|------------|
| **Serveurs** | 2 (Laravel + Vite) | 1 (Laravel via Nginx) |
| **Compilation** | Temps réel (hot reload) | Pré-compilée (`npm run build`) |
| **Assets** | `localhost:5173` | `public/build/` |
| **Performance** | Plus lent (compilation à la volée) | Plus rapide (assets optimisés) |
| **Debug** | Source maps disponibles | Minifié et optimisé |

---

## ✅ Checklist de déploiement

Avant de mettre en production :

- [ ] Compiler les assets localement (`npm run build`)
- [ ] Vérifier que `public/build/manifest.json` existe
- [ ] Transférer `public/build/` sur O2Switch
- [ ] Configurer `.env` avec `APP_ENV=production`
- [ ] Exécuter `php artisan config:cache`
- [ ] Exécuter `php artisan route:cache`
- [ ] Exécuter `php artisan view:cache`
- [ ] Tester l'application dans le navigateur
- [ ] Vérifier la console du navigateur (pas d'erreurs 404)
- [ ] Vérifier que les CSS et JS se chargent correctement

---

## 🎯 Résumé

**En production sur O2Switch :**
- ✅ Compiler une fois : `npm run build`
- ✅ Transférer : `scp -r public/build/ ...`
- ✅ Configurer : `APP_ENV=production`
- ✅ Optimiser : `php artisan optimize`
- ❌ Pas besoin de serveur Vite
- ❌ Pas besoin de `npm run dev`

**Résultat :** Un seul serveur web (Nginx/Apache) qui sert Laravel + les assets pré-compilés.

