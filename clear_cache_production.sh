#!/bin/bash

# Script pour nettoyer le cache en production
# Usage: ./clear_cache_production.sh

echo "Nettoyage du cache Laravel..."

php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "Cache nettoyé avec succès!"

