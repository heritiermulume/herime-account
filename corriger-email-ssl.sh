#!/bin/bash

# Script pour corriger la configuration email SSL/TLS

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔧 CORRECTION DE LA CONFIGURATION EMAIL"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Vérifier si on est dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo "❌ Erreur : Ce script doit être exécuté depuis la racine du projet Laravel"
    exit 1
fi

echo "📋 1. Vérification de la configuration actuelle dans .env :"
echo ""

if [ -f ".env" ]; then
    MAIL_ENCRYPTION=$(grep "^MAIL_ENCRYPTION=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | tr -d ' ')
    
    echo "   MAIL_ENCRYPTION actuel: '${MAIL_ENCRYPTION}'"
    echo ""
    
    if [ -z "$MAIL_ENCRYPTION" ] || [ "$MAIL_ENCRYPTION" = "" ]; then
        echo "   ⚠️  MAIL_ENCRYPTION est vide ou non défini !"
        echo ""
        echo "   🔧 Correction automatique..."
        
        # Chercher la ligne MAIL_ENCRYPTION et la remplacer
        if grep -q "^MAIL_ENCRYPTION=" .env; then
            # Remplacer la ligne existante
            sed -i 's/^MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=ssl/' .env
        else
            # Ajouter la ligne si elle n'existe pas
            echo "MAIL_ENCRYPTION=ssl" >> .env
        fi
        
        echo "   ✅ MAIL_ENCRYPTION défini à 'ssl'"
    else
        echo "   ✅ MAIL_ENCRYPTION est défini"
    fi
else
    echo "   ❌ Fichier .env non trouvé !"
    exit 1
fi

echo ""
echo "📋 2. Vérification du format de MAIL_ENCRYPTION :"
echo ""

# Vérifier s'il y a des guillemets ou espaces
MAIL_ENCRYPTION=$(grep "^MAIL_ENCRYPTION=" .env | cut -d '=' -f2)
if [[ "$MAIL_ENCRYPTION" =~ ^[\"\'[:space:]] ]] || [[ "$MAIL_ENCRYPTION" =~ [\"\'[:space:]]$ ]]; then
    echo "   ⚠️  MAIL_ENCRYPTION contient des guillemets ou espaces, nettoyage..."
    sed -i "s/^MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=ssl/" .env
    echo "   ✅ MAIL_ENCRYPTION nettoyé"
else
    echo "   ✅ Format correct"
fi

echo ""
echo "📋 3. Vérification de la configuration complète :"
echo ""

cat .env | grep "^MAIL_" | while IFS= read -r line; do
    echo "   $line"
done

echo ""
echo "📋 4. Vidage des caches Laravel :"
echo ""

php artisan config:clear
php artisan cache:clear

echo ""
echo "📋 5. Vérification de la configuration après cache :"
echo ""

php artisan tinker --execute="
echo '   MAIL_MAILER: ' . config('mail.default') . PHP_EOL;
echo '   MAIL_HOST: ' . config('mail.mailers.smtp.host') . PHP_EOL;
echo '   MAIL_PORT: ' . config('mail.mailers.smtp.port') . PHP_EOL;
echo '   MAIL_USERNAME: ' . config('mail.mailers.smtp.username') . PHP_EOL;
echo '   MAIL_ENCRYPTION: [' . (config('mail.mailers.smtp.encryption') ?: 'VIDE') . ']' . PHP_EOL;
echo '   MAIL_FROM_ADDRESS: ' . config('mail.from.address') . PHP_EOL;
"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💡 PROCHAINES ÉTAPES :"
echo ""
echo "   1. Si MAIL_ENCRYPTION est toujours vide, essayez avec TLS :"
echo "      sed -i 's/^MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=tls/' .env"
echo "      php artisan config:clear"
echo ""
echo "   2. Tester l'envoi d'email :"
echo "      php test-email.php"
echo ""
echo "   3. Vérifier les logs pour des erreurs :"
echo "      tail -n 50 storage/logs/laravel.log | grep -i 'mail\|error'"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

