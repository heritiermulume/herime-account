#!/bin/bash

# Script pour nettoyer le cache en production et vérifier le déploiement
# Usage: ./clear_cache_production.sh

echo "=== Nettoyage du cache Laravel ==="
echo ""

# Vérifier que le fichier SSORedirectController.php existe
if [ ! -f "app/Http/Controllers/Web/SSORedirectController.php" ]; then
    echo "❌ ERREUR: SSORedirectController.php n'existe pas!"
    echo "   Exécutez: git pull origin main"
    exit 1
fi

echo "✅ SSORedirectController.php existe"
echo ""

echo "Régénération de l'autoload Composer..."
composer dump-autoload

echo ""
echo "Nettoyage du cache..."
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "✅ Cache nettoyé avec succès!"
echo ""
echo "Vérification de la route..."
php artisan route:list --path=sso/redirect

echo ""
echo "✅ Vérification terminée"

