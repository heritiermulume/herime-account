# Guide de Déploiement - compte.herime.com

## 🚀 Déploiement en Production

### 1. Sur le serveur de production

```bash
# Se connecter au serveur
ssh user@compte.herime.com

# Aller dans le répertoire du projet
cd /path/to/account

# Mettre à jour le code
git pull origin main

# Installer les dépendances PHP (si nécessaire)
composer install --no-dev --optimize-autoloader

# Installer les dépendances Node.js (si nécessaire)
npm install

# Compiler les assets pour la production
npm run build

# Vider tous les caches Laravel
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Optimiser pour la production (optionnel mais recommandé)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Redémarrer PHP-FPM ou le serveur web
sudo systemctl restart php8.2-fpm  # Adapter selon votre version PHP
# OU
sudo service nginx reload
```

### 2. Vérifications après déploiement

1. **Vérifier que les assets sont accessibles** :
   ```bash
   curl -I https://compte.herime.com/build/manifest.json
   # Doit retourner 200 OK
   ```

2. **Vérifier les permissions** :
   ```bash
   # Les fichiers dans public/build doivent être lisibles
   ls -la public/build/
   chmod -R 755 public/build/
   ```

3. **Tester l'URL SSO** :
   - Ouvrir : `https://compte.herime.com/login?force_token=1&redirect=https%3A%2F%2Facademie.herime.com%2Fsso%2Fcallback`
   - Vérifier que le formulaire de login s'affiche
   - Vérifier la console du navigateur (F12) pour les erreurs

4. **Vérifier le source HTML** :
   - Clic droit → Afficher le code source
   - Chercher les commentaires de debug :
     ```html
     <!-- SSO_REDIRECT: NOT_SET -->
     <!-- URL: https://compte.herime.com/login?force_token=1&redirect=... -->
     ```

### 3. Résolution des problèmes courants

#### Problème : Page blanche

**Diagnostic** :
```bash
# Vérifier les logs Laravel
tail -f storage/logs/laravel.log

# Vérifier les logs Nginx/Apache
tail -f /var/log/nginx/error.log
```

**Solutions** :
1. Vider le cache navigateur : `Ctrl+Shift+R` (ou `Cmd+Shift+R` sur Mac)
2. Vérifier que les assets sont compilés :
   ```bash
   ls -lh public/build/assets/
   # Doit afficher app-*.js et app-*.css
   ```
3. Recompiler les assets :
   ```bash
   npm run build
   ```

#### Problème : Assets non trouvés (404)

**Diagnostic** :
```bash
# Vérifier le manifest
cat public/build/manifest.json
```

**Solutions** :
1. Vérifier la configuration Vite dans `vite.config.js`
2. Vérifier que `APP_URL` est correct dans `.env`
3. Recompiler :
   ```bash
   rm -rf public/build
   npm run build
   ```

#### Problème : "Vue.js failed to load after 10 seconds"

**Causes possibles** :
1. Assets non déployés
2. Erreur JavaScript
3. Problème de CORS
4. Cache navigateur

**Solutions** :
1. Vérifier la console du navigateur (F12 → Console)
2. Vérifier l'onglet Network (F12 → Network) pour voir les requêtes échouées
3. Vider le cache : `Ctrl+Shift+R`
4. Vérifier les headers CORS :
   ```bash
   curl -I https://compte.herime.com/build/assets/app-*.js
   ```

### 4. Commandes de maintenance

#### Vider tous les caches

```bash
php artisan optimize:clear
# Équivalent à :
# php artisan config:clear
# php artisan cache:clear
# php artisan view:clear
# php artisan route:clear
```

#### Optimiser pour la production

```bash
php artisan optimize
# Équivalent à :
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache
```

#### Vérifier la configuration

```bash
php artisan config:show
php artisan route:list
```

### 5. Checklist de déploiement

- [ ] `git pull origin main` ✅
- [ ] `composer install --no-dev --optimize-autoloader` (si composer.lock modifié)
- [ ] `npm install` (si package-lock.json modifié)
- [ ] `npm run build` ✅
- [ ] `php artisan migrate` (si nouvelles migrations)
- [ ] `php artisan config:clear` ✅
- [ ] `php artisan cache:clear` ✅
- [ ] `php artisan view:clear` ✅
- [ ] `php artisan route:clear` ✅
- [ ] `php artisan config:cache` (optionnel)
- [ ] `php artisan route:cache` (optionnel)
- [ ] Redémarrer PHP-FPM ✅
- [ ] Tester l'URL SSO ✅
- [ ] Vérifier les logs ✅

### 6. Rollback en cas de problème

```bash
# Revenir à la version précédente
git log --oneline -5  # Voir les derniers commits
git reset --hard COMMIT_HASH  # Remplacer COMMIT_HASH par le commit précédent

# Recompiler
npm run build

# Vider les caches
php artisan optimize:clear

# Redémarrer
sudo systemctl restart php8.2-fpm
```

### 7. Monitoring

#### Logs à surveiller

```bash
# Logs Laravel
tail -f storage/logs/laravel.log | grep -E "LoginController|SSOController|AuthController"

# Logs Nginx
tail -f /var/log/nginx/access.log | grep "/login"
tail -f /var/log/nginx/error.log
```

#### Métriques à vérifier

- Temps de chargement de la page `/login`
- Taux d'erreur 500
- Taux d'erreur 404 sur `/build/assets/*`
- Nombre de connexions SSO réussies

### 8. Tests après déploiement

#### Test 1 : Login normal

```bash
# Ouvrir dans le navigateur
https://compte.herime.com/login

# Vérifier :
- Formulaire de login s'affiche
- Pas d'erreur dans la console
- Connexion fonctionne
```

#### Test 2 : SSO (utilisateur non connecté)

```bash
# Ouvrir dans le navigateur
https://compte.herime.com/login?force_token=1&redirect=https%3A%2F%2Facademie.herime.com%2Fsso%2Fcallback

# Vérifier :
- Formulaire de login s'affiche
- Message "Chargement de l'application..." puis formulaire
- Pas d'erreur dans la console
```

#### Test 3 : SSO (utilisateur connecté)

```bash
# Se connecter d'abord sur compte.herime.com
# Puis ouvrir :
https://compte.herime.com/login?force_token=1&redirect=https%3A%2F%2Facademie.herime.com%2Fsso%2Fcallback

# Vérifier :
- Redirection automatique vers academie.herime.com
- Token présent dans l'URL
- Pas d'erreur dans la console
```

#### Test 4 : Déconnexion centralisée

```bash
# 1. Se connecter sur academie.herime.com via SSO
# 2. Se déconnecter de compte.herime.com
# 3. Valider le token depuis academie.herime.com

# Vérifier dans les logs :
tail -f storage/logs/laravel.log | grep "Token revoked"

# Doit afficher :
# [SSOController] Token revoked: user_id=X, user_email=...
```

### 9. Variables d'environnement importantes

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://compte.herime.com

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=account
DB_USERNAME=...
DB_PASSWORD=...

# Passport
PASSPORT_PRIVATE_KEY=...
PASSPORT_PUBLIC_KEY=...

# SSO
SSO_SECRET=...  # Secret partagé avec les sites externes
```

### 10. Contact et support

En cas de problème :
1. Vérifier les logs Laravel : `storage/logs/laravel.log`
2. Vérifier les logs serveur : `/var/log/nginx/error.log`
3. Vérifier la console navigateur (F12)
4. Consulter la documentation SSO : `SSO_SYSTEM.md`

---

**Dernière mise à jour** : 16 novembre 2025
**Version** : 1.0.0

