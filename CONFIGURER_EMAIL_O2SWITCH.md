# Configuration Email pour O2Switch

## 📧 Configuration de l'envoi d'emails

L'envoi d'email pour la réinitialisation de mot de passe est maintenant activé. Vous devez configurer les paramètres SMTP dans votre fichier `.env` sur O2Switch.

## 🔧 Configuration SMTP sur O2Switch

### Option 1 : Utiliser le serveur SMTP d'O2Switch

Ajoutez ces variables dans votre fichier `.env` sur O2Switch :

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.o2switch.net
MAIL_PORT=587
MAIL_USERNAME=votre-email@votre-domaine.com
MAIL_PASSWORD=votre-mot-de-passe-email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="Compte Herime"
```

### Option 2 : Utiliser un service externe (Gmail, SendGrid, Mailgun, etc.)

#### Gmail (avec App Password)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre-email@gmail.com
MAIL_FROM_NAME="Compte Herime"
```

**Note :** Pour Gmail, vous devez créer un "App Password" dans les paramètres de sécurité de votre compte Google.

#### SendGrid

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=votre-api-key-sendgrid
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="Compte Herime"
```

#### Mailgun

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@votre-domaine.mailgun.org
MAIL_PASSWORD=votre-password-mailgun
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="Compte Herime"
```

## 📝 Étapes pour configurer sur O2Switch

1. **Connectez-vous en SSH à O2Switch :**
   ```bash
   ssh votre-identifiant@o2switch.fr
   cd www/votre-domaine.com
   ```

2. **Éditez le fichier `.env` :**
   ```bash
   nano .env
   ```

3. **Ajoutez/modifiez les variables MAIL_* :**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=mail.o2switch.net
   MAIL_PORT=587
   MAIL_USERNAME=votre-email@votre-domaine.com
   MAIL_PASSWORD=votre-mot-de-passe
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@votre-domaine.com
   MAIL_FROM_NAME="Compte Herime"
   ```

4. **Videz le cache de configuration :**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

5. **Testez l'envoi d'email :**
   - Allez sur la page de login
   - Cliquez sur "Mot de passe oublié ?"
   - Entrez votre email
   - Vérifiez vos logs si l'email n'arrive pas :
     ```bash
     tail -n 100 storage/logs/laravel.log
     ```

## 🔍 Diagnostic des problèmes

### Vérifier les logs

```bash
tail -n 100 storage/logs/laravel.log | grep -i "mail\|password reset"
```

### Tester la configuration email avec Tinker

```bash
php artisan tinker
```

Puis dans Tinker :
```php
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;

Mail::to('votre-email@test.com')->send(new PasswordResetMail('https://example.com/reset-password?token=test&email=test@example.com'));
```

### Vérifier les erreurs communes

1. **Erreur "Connection timeout"** :
   - Vérifiez que le port (587 ou 465) n'est pas bloqué par un firewall
   - Vérifiez que `MAIL_HOST` est correct

2. **Erreur "Authentication failed"** :
   - Vérifiez que `MAIL_USERNAME` et `MAIL_PASSWORD` sont corrects
   - Pour Gmail, utilisez un "App Password", pas votre mot de passe normal

3. **Email non reçu** :
   - Vérifiez le dossier spam
   - Vérifiez les logs Laravel pour des erreurs
   - Vérifiez que `MAIL_FROM_ADDRESS` est une adresse valide

## 📚 Ressources

- [Documentation Laravel Mail](https://laravel.com/docs/mail)
- [Configuration O2Switch](https://www.o2switch.fr/support/)

