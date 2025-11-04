#!/bin/bash

# Script pour vérifier la configuration email sur O2Switch

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 VÉRIFICATION DE LA CONFIGURATION EMAIL"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Vérifier si on est dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo "❌ Erreur : Ce script doit être exécuté depuis la racine du projet Laravel"
    exit 1
fi

echo "📋 1. Vérification des variables dans .env :"
echo ""

if [ -f ".env" ]; then
    echo "   ✅ Fichier .env trouvé"
    echo ""
    
    # Vérifier les variables MAIL_*
    MAIL_MAILER=$(grep "^MAIL_MAILER=" .env | cut -d '=' -f2 | tr -d '"')
    MAIL_HOST=$(grep "^MAIL_HOST=" .env | cut -d '=' -f2 | tr -d '"')
    MAIL_PORT=$(grep "^MAIL_PORT=" .env | cut -d '=' -f2 | tr -d '"')
    MAIL_USERNAME=$(grep "^MAIL_USERNAME=" .env | cut -d '=' -f2 | tr -d '"')
    MAIL_ENCRYPTION=$(grep "^MAIL_ENCRYPTION=" .env | cut -d '=' -f2 | tr -d '"')
    MAIL_FROM_ADDRESS=$(grep "^MAIL_FROM_ADDRESS=" .env | cut -d '=' -f2 | tr -d '"')
    
    echo "   MAIL_MAILER: ${MAIL_MAILER:-❌ Non défini}"
    echo "   MAIL_HOST: ${MAIL_HOST:-❌ Non défini}"
    echo "   MAIL_PORT: ${MAIL_PORT:-❌ Non défini}"
    echo "   MAIL_USERNAME: ${MAIL_USERNAME:-❌ Non défini}"
    echo "   MAIL_ENCRYPTION: ${MAIL_ENCRYPTION:-❌ Non défini}"
    echo "   MAIL_FROM_ADDRESS: ${MAIL_FROM_ADDRESS:-❌ Non défini}"
    echo ""
    
    # Vérifier si toutes les variables sont définies
    if [ -z "$MAIL_MAILER" ] || [ -z "$MAIL_HOST" ] || [ -z "$MAIL_PORT" ] || [ -z "$MAIL_USERNAME" ]; then
        echo "   ⚠️  Certaines variables MAIL_* ne sont pas définies !"
        echo ""
    else
        echo "   ✅ Toutes les variables MAIL_* sont définies"
        echo ""
    fi
else
    echo "   ❌ Fichier .env non trouvé !"
    echo ""
fi

echo "📋 2. Configuration Laravel (après cache) :"
echo ""

php artisan tinker --execute="
echo '   MAIL_MAILER: ' . config('mail.default') . PHP_EOL;
echo '   MAIL_HOST: ' . config('mail.mailers.smtp.host') . PHP_EOL;
echo '   MAIL_PORT: ' . config('mail.mailers.smtp.port') . PHP_EOL;
echo '   MAIL_USERNAME: ' . config('mail.mailers.smtp.username') . PHP_EOL;
echo '   MAIL_ENCRYPTION: ' . config('mail.mailers.smtp.encryption') . PHP_EOL;
echo '   MAIL_FROM_ADDRESS: ' . config('mail.from.address') . PHP_EOL;
echo '   MAIL_FROM_NAME: ' . config('mail.from.name') . PHP_EOL;
"

echo ""
echo "📋 3. Vérification des logs récents (erreurs email) :"
echo ""

if [ -f "storage/logs/laravel.log" ]; then
    echo "   Dernières erreurs liées à l'email :"
    tail -n 50 storage/logs/laravel.log | grep -i "mail\|email\|password reset" | tail -n 10
    echo ""
else
    echo "   ⚠️  Aucun log trouvé"
    echo ""
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💡 COMMANDES UTILES :"
echo ""
echo "   Pour tester l'envoi d'email :"
echo "   php test-email.php"
echo ""
echo "   Pour vider les caches :"
echo "   php artisan config:clear && php artisan cache:clear"
echo ""
echo "   Pour voir les logs en temps réel :"
echo "   tail -f storage/logs/laravel.log"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

