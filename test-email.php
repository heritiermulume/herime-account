<?php

/**
 * Script de test pour l'envoi d'email
 * 
 * Usage: php test-email.php
 * 
 * Ce script teste la configuration email et envoie un email de test
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🧪 TEST D'ENVOI D'EMAIL\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Vérifier la configuration
echo "📋 Configuration actuelle :\n";
echo "   MAIL_MAILER: " . config('mail.default') . "\n";
echo "   MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "   MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "   MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "   MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption') . "\n";
echo "   MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "   MAIL_FROM_NAME: " . config('mail.from.name') . "\n\n";

// Demander l'email de test
echo "📧 Entrez l'adresse email pour le test (ou appuyez sur Entrée pour utiliser mail@herime.com): ";
$email = trim(fgets(STDIN));
if (empty($email)) {
    $email = 'mail@herime.com';
}

echo "\n📤 Envoi de l'email de test à : $email\n\n";

try {
    $testUrl = config('app.url') . '/reset-password?token=test-token-123&email=' . urlencode($email);
    
    Mail::to($email)->send(new PasswordResetMail($testUrl));
    
    echo "✅ Email envoyé avec succès !\n";
    echo "   Vérifiez votre boîte mail (et les spams) pour l'email de test.\n\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR lors de l'envoi de l'email :\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . "\n";
    echo "   Ligne: " . $e->getLine() . "\n\n";
    echo "📋 Trace complète :\n";
    echo $e->getTraceAsString() . "\n\n";
    
    echo "💡 Suggestions :\n";
    echo "   1. Vérifiez que les variables MAIL_* sont correctement configurées dans .env\n";
    echo "   2. Vérifiez que php artisan config:clear a été exécuté\n";
    echo "   3. Vérifiez les logs : tail -n 100 storage/logs/laravel.log\n";
    echo "   4. Essayez avec MAIL_ENCRYPTION=tls au lieu de ssl\n";
    exit(1);
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

