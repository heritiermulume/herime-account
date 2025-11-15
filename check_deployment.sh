#!/bin/bash

# Script pour vérifier que tous les fichiers nécessaires sont déployés

echo "=== Vérification du déploiement ==="
echo ""

# Vérifier que le fichier SSORedirectController.php existe
if [ -f "app/Http/Controllers/Web/SSORedirectController.php" ]; then
    echo "✅ SSORedirectController.php existe"
else
    echo "❌ SSORedirectController.php MANQUANT - Exécutez: git pull origin main"
    exit 1
fi

# Vérifier que le fichier routes/web.php existe et contient la route
if grep -q "SSORedirectController" routes/web.php; then
    echo "✅ La route /sso/redirect est définie dans routes/web.php"
else
    echo "❌ La route /sso/redirect n'est pas définie dans routes/web.php"
    exit 1
fi

# Vérifier que le cache est nettoyé
echo ""
echo "Nettoyage du cache..."
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "✅ Vérification terminée - Tout est OK"

