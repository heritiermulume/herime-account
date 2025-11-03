# HERIME SSO - Système d'Authentification Centralisée

## 🌟 Vue d'ensemble

HERIME SSO est un système d'authentification centralisée (Single Sign-On) développé avec Laravel 11 et Vue.js 3. Il permet aux utilisateurs de se connecter une seule fois et d'accéder à tous les services HERIME.

## 🏗️ Architecture

### Sous-domaines HERIME
- `account.herime.com` - Serveur d'authentification central
- `academie.herime.com` - Plateforme de formation
- `store.herime.com` - Boutique en ligne
- `events.herime.com` - Plateforme d'événements
- `studio.herime.com` - Espace créatif

### Technologies utilisées

#### Backend
- **Laravel 11** - Framework PHP
- **Laravel Passport** - OAuth2 + JWT
- **Laravel Fortify** - Authentification et 2FA
- **Laravel Socialite** - Authentification sociale
- **MySQL** - Base de données

#### Frontend
- **Vue.js 3** - Framework JavaScript
- **Tailwind CSS** - Framework CSS
- **Pinia** - Gestion d'état
- **Vue Router** - Routage
- **Axios** - Client HTTP

## 🚀 Installation

### Prérequis
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+
- NPM/Yarn

### Installation locale

1. **Cloner le projet**
```bash
git clone <repository-url>
cd account
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Installer les dépendances Node.js**
```bash
npm install
```

4. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configuration de la base de données**
```bash
# Éditer .env avec vos paramètres de base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=herime_sso
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. **Exécuter les migrations**
```bash
php artisan migrate
```

7. **Installer Passport**
```bash
php artisan passport:install
```

8. **Créer les clients OAuth**
```bash
# Client personnel
php artisan passport:client --personal --name="Herime SSO Personal Access Client"

# Clients pour les sous-domaines
php artisan passport:client --public --name="Herime Academy" --redirect_uri="https://academie.herime.com/sso/callback"
php artisan passport:client --public --name="Herime Store" --redirect_uri="https://store.herime.com/sso/callback"
php artisan passport:client --public --name="Herime Events" --redirect_uri="https://events.herime.com/sso/callback"
php artisan passport:client --public --name="Herime Studio" --redirect_uri="https://studio.herime.com/sso/callback"
```

9. **Compiler les assets**
```bash
npm run build
```

10. **Démarrer le serveur**
```bash
php artisan serve
```

## 🔧 Configuration

### Variables d'environnement importantes

```env
# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=herime_sso
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Passport
PASSPORT_PRIVATE_KEY=""
PASSPORT_PUBLIC_KEY=""

# Mail (pour les notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls

# Socialite (optionnel)
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
FACEBOOK_CLIENT_ID=your-facebook-client-id
FACEBOOK_CLIENT_SECRET=your-facebook-client-secret
```

## 📡 API Endpoints

### Authentification
- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `POST /api/auth/logout` - Déconnexion
- `GET /api/auth/me` - Profil utilisateur
- `POST /api/auth/refresh` - Rafraîchir le token

### Gestion du profil
- `GET /api/user/profile` - Obtenir le profil
- `PUT /api/user/profile` - Modifier le profil
- `POST /api/user/change-password` - Changer le mot de passe
- `PUT /api/user/preferences` - Modifier les préférences
- `POST /api/user/deactivate` - Désactiver le compte
- `DELETE /api/user/account` - Supprimer le compte

### SSO
- `POST /api/sso/validate` - Valider un token SSO
- `POST /api/sso/create-session` - Créer une session SSO
- `GET /api/sso/sessions` - Obtenir les sessions
- `DELETE /api/sso/sessions/{id}` - Révoquer une session
- `DELETE /api/sso/sessions` - Révoquer toutes les sessions

## 🔐 Sécurité

### Fonctionnalités de sécurité implémentées
- **HTTPS obligatoire** sur tous les sous-domaines
- **Tokens JWT signés RSA** pour la sécurité
- **Refresh Tokens** pour les sessions prolongées
- **Protection CSRF** intégrée
- **Double authentification (2FA)** avec Fortify
- **Validation des données** stricte
- **Gestion des sessions** avancée

### Recommandations de sécurité
1. Utiliser HTTPS en production
2. Configurer des clés RSA fortes
3. Limiter les tentatives de connexion
4. Surveiller les logs d'accès
5. Mettre à jour régulièrement les dépendances

## 🎨 Interface utilisateur

### Fonctionnalités de l'interface
- **Design moderne et responsive** avec Tailwind CSS
- **Mode sombre/clair** automatique
- **Formulaires de connexion/inscription** intuitifs
- **Tableau de bord utilisateur** complet
- **Gestion des sessions** en temps réel
- **Paramètres de sécurité** avancés

### Composants Vue.js
- `App.vue` - Composant principal
- `Login.vue` - Formulaire de connexion
- `Register.vue` - Formulaire d'inscription
- `Dashboard.vue` - Tableau de bord
- `ProfileModal.vue` - Gestion du profil
- `SessionsModal.vue` - Gestion des sessions
- `SecurityModal.vue` - Paramètres de sécurité

## 🚀 Déploiement

### Déploiement sur O2Switch

Pour déployer rapidement votre application sur O2Switch depuis GitHub, nous avons créé des guides et scripts automatisés :

**📚 Documentation :**
- **[DEPLOY_QUICKSTART.md](./DEPLOY_QUICKSTART.md)** - Guide rapide de déploiement (10 minutes)
- **[DEPLOY_O2SWITCH.md](./DEPLOY_O2SWITCH.md)** - Guide complet et détaillé

**🚀 Déploiement automatique :**
```bash
# 1. Se connecter en SSH à O2Switch
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# 2. Cloner le projet
git clone https://github.com/heritiermulume/herime-account.git .

# 3. Configurer l'environnement
cp env.o2switch.example .env
nano .env  # Éditer avec vos informations

# 4. Exécuter le script de déploiement
chmod +x deploy-o2switch.sh
./deploy-o2switch.sh
```

Le script `deploy-o2switch.sh` automatise tout :
- ✅ Installation des dépendances
- ✅ Compilation des assets
- ✅ Configuration de l'environnement
- ✅ Exécution des migrations
- ✅ Installation de Passport
- ✅ Création de l'administrateur
- ✅ Optimisation de l'application

### Production (Configuration manuelle)

1. **Serveur web (Nginx/Apache)**
```nginx
server {
    listen 443 ssl http2;
    server_name account.herime.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    root /path/to/account/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

2. **Configuration Laravel**
```bash
# Optimiser pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Compiler les assets
npm run build
```

3. **Permissions**
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## 🔄 Intégration avec les applications clientes

### Exemple d'intégration (JavaScript)

```javascript
// Redirection vers le SSO
function redirectToSSO() {
    const clientId = 'your-client-id';
    const redirectUri = encodeURIComponent('https://your-domain.com/sso/callback');
    const state = generateRandomState();
    
    const ssoUrl = `https://account.herime.com/oauth/authorize?` +
        `client_id=${clientId}&` +
        `redirect_uri=${redirectUri}&` +
        `response_type=code&` +
        `scope=profile&` +
        `state=${state}`;
    
    window.location.href = ssoUrl;
}

// Traitement du callback
function handleSSOCallback() {
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get('code');
    const state = urlParams.get('state');
    
    if (code && state) {
        // Échanger le code contre un token
        exchangeCodeForToken(code);
    }
}

// Échanger le code contre un token
async function exchangeCodeForToken(code) {
    try {
        const response = await fetch('https://account.herime.com/oauth/token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                grant_type: 'authorization_code',
                client_id: 'your-client-id',
                client_secret: 'your-client-secret',
                redirect_uri: 'https://your-domain.com/sso/callback',
                code: code
            })
        });
        
        const data = await response.json();
        
        if (data.access_token) {
            // Stocker le token et rediriger
            localStorage.setItem('access_token', data.access_token);
            window.location.href = '/dashboard';
        }
    } catch (error) {
        console.error('Erreur SSO:', error);
    }
}
```

## 🧪 Tests

### Tests unitaires
```bash
php artisan test
```

### Tests d'intégration
```bash
# Tester l'API
curl -X POST https://account.herime.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

## 📊 Monitoring

### Logs importants
- `storage/logs/laravel.log` - Logs généraux
- `storage/logs/sso.log` - Logs SSO spécifiques

### Métriques à surveiller
- Nombre de connexions par jour
- Taux d'échec de connexion
- Sessions actives
- Temps de réponse API

## 🤝 Support

Pour toute question ou problème :
- Créer une issue sur le repository
- Contacter l'équipe de développement
- Consulter la documentation Laravel Passport

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.

---

**HERIME SSO** - Authentification centralisée pour l'écosystème HERIME