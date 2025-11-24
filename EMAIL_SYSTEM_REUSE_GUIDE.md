# Guide de réutilisation du système d'envoi d'emails

Ce guide explique comment copier et réutiliser le système complet d'envoi d'emails de ce projet dans un autre projet Laravel.

---

## 📦 Composants du système

Le système comprend :

1. **NotificationService** - Service principal pour gérer les emails
2. **Classes Mailable** - Templates d'emails (NewLoginMail, PasswordChangedMail, etc.)
3. **Templates Blade** - Vues des emails
4. **Modèle UserEmailNotification** (optionnel) - Pour les digests quotidiens/hebdomadaires
5. **Configuration** - Paramètres SMTP et préférences utilisateur

---

## 🚀 Étape 1 : Copier les fichiers

### 1.1 Copier le service NotificationService

```bash
# Dans votre nouveau projet
mkdir -p app/Services
```

Copiez le fichier :
- `app/Services/NotificationService.php`

### 1.2 Copier les classes Mailable

```bash
# Dans votre nouveau projet
mkdir -p app/Mail
```

Copiez les fichiers que vous voulez utiliser :
- `app/Mail/NewLoginMail.php`
- `app/Mail/PasswordChangedMail.php`
- `app/Mail/AccountDeactivatedMail.php`
- `app/Mail/PasswordResetMail.php`

### 1.3 Copier les templates d'emails

```bash
# Dans votre nouveau projet
mkdir -p resources/views/emails
```

Copiez les templates correspondants :
- `resources/views/emails/new-login.blade.php`
- `resources/views/emails/password-changed.blade.php`
- `resources/views/emails/account-deactivated.blade.php`
- `resources/views/emails/password-reset.blade.php`

### 1.4 Copier le logo (optionnel)

Si vous utilisez un logo dans vos emails :
```bash
cp public/logo.png votre-projet/public/logo.png
```

---

## ⚙️ Étape 2 : Configuration de la base de données

### 2.1 Ajouter le champ preferences au modèle User

Si votre modèle `User` n'a pas encore le champ `preferences`, ajoutez-le :

```bash
php artisan make:migration add_preferences_to_users_table
```

Dans la migration :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('preferences')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferences');
        });
    }
};
```

### 2.2 Mettre à jour le modèle User

Dans `app/Models/User.php` :

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'preferences', // Ajouter ceci
    // ... autres champs
];

protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'preferences' => 'array', // Ajouter ceci
    ];
}
```

### 2.3 Créer la table pour les digests (Optionnel)

Si vous voulez utiliser le système de digest (emails groupés quotidiens/hebdomadaires) :

```bash
php artisan make:migration create_user_email_notifications_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_email_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('event_key'); // suspicious_logins, password_changes, etc.
            $table->json('payload')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'sent_at']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_email_notifications');
    }
};
```

Créez le modèle :

```bash
php artisan make:model UserEmailNotification
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEmailNotification extends Model
{
    protected $fillable = [
        'user_id',
        'event_key',
        'payload',
        'scheduled_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 2.4 Exécuter les migrations

```bash
php artisan migrate
```

---

## 📧 Étape 3 : Configuration SMTP

### 3.1 Configurer le fichier .env

Ajoutez ou modifiez ces variables dans votre `.env` :

```env
# Configuration SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votreapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 3.2 Tester la configuration

Créez un test simple :

```bash
php artisan tinker
```

```php
Mail::raw('Test email', function ($message) {
    $message->to('votre-email@gmail.com')
            ->subject('Test Email');
});
```

---

## 💻 Étape 4 : Utilisation du système

### 4.1 Envoi simple (toujours envoyé)

```php
use App\Services\NotificationService;
use App\Mail\NewLoginMail;

$user = User::find(1);

// Créer le mailable
$mailable = new NewLoginMail(
    firstName: $user->name,
    lastName: null,
    ip: request()->ip(),
    device: request()->userAgent(),
    time: now()->format('d/m/Y à H:i')
);

// Envoyer si les notifications sont activées
NotificationService::sendIfEnabled($user, $mailable);
```

### 4.2 Envoi avec respect des préférences granulaires

```php
use App\Services\NotificationService;
use App\Mail\PasswordChangedMail;

$user = User::find(1);

$mailable = new PasswordChangedMail(
    firstName: $user->name
);

// Envoyer uniquement si l'utilisateur a activé les notifications de changement de mot de passe
NotificationService::sendForEvent(
    user: $user,
    eventKey: 'password_changes',
    mailable: $mailable
);
```

### 4.3 Exemple complet dans un contrôleur

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use App\Mail\NewLoginMail;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // ... validation et authentification
        
        $user = auth()->user();
        
        // Envoyer la notification de nouvelle connexion
        $mailable = new NewLoginMail(
            firstName: $user->name,
            lastName: null,
            ip: $request->ip(),
            device: $request->userAgent(),
            time: now()->format('d/m/Y à H:i')
        );
        
        NotificationService::sendForEvent(
            user: $user,
            eventKey: 'suspicious_logins',
            mailable: $mailable
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie'
        ]);
    }
}
```

---

## 🎨 Étape 5 : Créer vos propres emails

### 5.1 Créer une classe Mailable

```bash
php artisan make:mail WelcomeMail
```

Dans `app/Mail/WelcomeMail.php` :

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;

    public function __construct(string $userName)
    {
        $this->userName = $userName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue sur notre plateforme',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
        );
    }
}
```

### 5.2 Créer le template Blade

Dans `resources/views/emails/welcome.blade.php` :

```blade
<x-mail::message>
<div style="text-align:center; margin-bottom:16px;">
    <img src="{{ asset('logo.png') }}" alt="Logo" style="height:48px; width:auto;" />
    <div style="height:8px;"></div>
    <strong style="font-size:14px; color:#003366;">Votre Application</strong>
</div>

# Bienvenue {{ $userName }} ! 🎉

Nous sommes ravis de vous accueillir sur notre plateforme.

<x-mail::button :url="config('app.url')">
Commencer maintenant
</x-mail::button>

Merci,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
```

### 5.3 Utiliser votre email

```php
use App\Mail\WelcomeMail;
use App\Services\NotificationService;

$user = User::find(1);

$mailable = new WelcomeMail($user->name);

NotificationService::sendForEvent(
    user: $user,
    eventKey: 'welcome',
    mailable: $mailable
);
```

---

## ⚙️ Étape 6 : Gérer les préférences utilisateur

### 6.1 Structure des préférences

Les préférences sont stockées dans le champ JSON `preferences` :

```json
{
  "email_notifications": true,
  "marketing_emails": false,
  "email_frequency": "immediate",
  "notifications": {
    "suspicious_logins": true,
    "password_changes": true,
    "profile_changes": false,
    "new_features": true,
    "maintenance": true,
    "newsletter": false,
    "special_offers": false,
    "welcome": true
  }
}
```

### 6.2 Mettre à jour les préférences

```php
$user = User::find(1);

$user->update([
    'preferences' => [
        'email_notifications' => true,
        'marketing_emails' => false,
        'email_frequency' => 'daily', // immediate, daily, weekly, monthly, never
        'notifications' => [
            'welcome' => true,
            'suspicious_logins' => true,
            'password_changes' => true,
        ]
    ]
]);
```

### 6.3 Créer une API pour les préférences

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserPreferencesController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'marketing_emails' => 'boolean',
            'email_frequency' => 'in:immediate,daily,weekly,monthly,never',
            'notifications' => 'array',
        ]);
        
        $user->update([
            'preferences' => array_merge(
                $user->preferences ?? [],
                $validated
            )
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Préférences mises à jour'
        ]);
    }
}
```

---

## 📅 Étape 7 : Système de digest (Optionnel)

Si vous avez créé la table `user_email_notifications`, vous pouvez envoyer des emails groupés.

### 7.1 Créer une commande artisan

```bash
php artisan make:command SendEmailDigests
```

Dans `app/Console/Commands/SendEmailDigests.php` :

```php
<?php

namespace App\Console\Commands;

use App\Models\UserEmailNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendEmailDigests extends Command
{
    protected $signature = 'email:send-digests';
    protected $description = 'Envoyer les digests d\'emails programmés';

    public function handle()
    {
        $pending = UserEmailNotification::whereNull('sent_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $this->info("Envoi de {$pending->count()} emails...");

        foreach ($pending as $notification) {
            NotificationService::sendQueued($notification);
        }

        $this->info('Terminé !');
    }
}
```

### 7.2 Planifier la commande

Dans `app/Console/Kernel.php` :

```php
protected function schedule(Schedule $schedule): void
{
    // Envoyer les digests quotidiens à 8h du matin
    $schedule->command('email:send-digests')
             ->dailyAt('08:00');
}
```

### 7.3 Exécuter manuellement

```bash
php artisan email:send-digests
```

---

## 🧪 Étape 8 : Tests

### 8.1 Test unitaire

Créez un test :

```bash
php artisan make:test EmailNotificationTest
```

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Mail\WelcomeMail;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_sent_when_enabled()
    {
        Mail::fake();

        $user = User::factory()->create([
            'preferences' => [
                'email_notifications' => true,
                'notifications' => [
                    'welcome' => true
                ]
            ]
        ]);

        $mailable = new WelcomeMail($user->name);
        
        NotificationService::sendForEvent($user, 'welcome', $mailable);

        Mail::assertSent(WelcomeMail::class);
    }

    public function test_email_not_sent_when_disabled()
    {
        Mail::fake();

        $user = User::factory()->create([
            'preferences' => [
                'email_notifications' => false
            ]
        ]);

        $mailable = new WelcomeMail($user->name);
        
        NotificationService::sendForEvent($user, 'welcome', $mailable);

        Mail::assertNotSent(WelcomeMail::class);
    }
}
```

```bash
php artisan test
```

---

## 🎯 Cas d'usage courants

### 1. Email de bienvenue

```php
use App\Mail\WelcomeMail;
use App\Services\NotificationService;

// À l'inscription
$mailable = new WelcomeMail($user->name);
NotificationService::sendForEvent($user, 'welcome', $mailable);
```

### 2. Email de confirmation

```php
use App\Mail\ConfirmationMail;
use App\Services\NotificationService;

$mailable = new ConfirmationMail($order);
NotificationService::sendIfEnabled($user, $mailable);
```

### 3. Email marketing

```php
use App\Mail\NewsletterMail;
use App\Services\NotificationService;

$mailable = new NewsletterMail($content);
NotificationService::sendForEvent($user, 'newsletter', $mailable, isMarketing: true);
```

### 4. Email de sécurité (toujours envoyé)

```php
use App\Mail\SecurityAlertMail;
use Illuminate\Support\Facades\Mail;

// Pour les emails critiques, bypass les préférences
Mail::to($user->email)->send(new SecurityAlertMail($alert));
```

---

## 🚨 Dépannage

### Emails ne sont pas envoyés

1. **Vérifier la configuration SMTP**
```bash
php artisan tinker
```
```php
config('mail.mailers.smtp');
```

2. **Vérifier les logs**
```bash
tail -f storage/logs/laravel.log
```

3. **Tester avec Mailtrap ou Mailhog**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre-username
MAIL_PASSWORD=votre-password
```

### Emails envoyés en spam

1. **Configurer SPF, DKIM, DMARC** dans votre DNS
2. **Utiliser un service professionnel** (SendGrid, Mailgun, Amazon SES)
3. **Éviter les mots spam** dans vos sujets et contenus

### Performance

Pour de gros volumes :

```php
use Illuminate\Support\Facades\Queue;

// Mettre les emails en file d'attente
Queue::push(function() use ($user, $mailable) {
    NotificationService::sendIfEnabled($user, $mailable);
});
```

---

## 📚 Ressources

- Documentation Laravel Mail : https://laravel.com/docs/mail
- Documentation Markdown Mail : https://laravel.com/docs/mail#markdown-mailables
- Tester vos emails : https://mailtrap.io
- Services SMTP : SendGrid, Mailgun, Amazon SES, Postmark

---

## ✅ Checklist de migration

- [ ] Copier `NotificationService.php`
- [ ] Copier les classes Mailable nécessaires
- [ ] Copier les templates Blade
- [ ] Ajouter le champ `preferences` au modèle User
- [ ] Créer la migration pour `preferences`
- [ ] (Optionnel) Créer la table `user_email_notifications`
- [ ] Configurer SMTP dans `.env`
- [ ] Tester l'envoi d'un email simple
- [ ] Créer vos propres emails
- [ ] Configurer les préférences utilisateur
- [ ] (Optionnel) Planifier les digests
- [ ] Écrire des tests
- [ ] Documenter pour votre équipe

---

**Félicitations !** 🎉 Vous pouvez maintenant envoyer des emails avec gestion des préférences utilisateur dans votre projet Laravel.

