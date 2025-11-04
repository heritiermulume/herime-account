#!/bin/bash
# Script simple pour corriger les assets sur O2Switch
set -e

echo "🔧 Correction des assets sur O2Switch"
echo "========================================"

# 1. Pull
echo ""
echo "1. Pull des dernières modifications..."
git pull origin main

# 2. Vérifier les assets
echo ""
echo "2. Vérification des assets..."
if [ ! -f "public/build/manifest.json" ]; then
    echo "❌ ERREUR: manifest.json manquant !"
    exit 1
fi

if [ ! -f "public/build/assets/app-DVlYVwTs.css" ]; then
    echo "❌ ERREUR: app-DVlYVwTs.css manquant !"
    exit 1
fi

if [ ! -f "public/build/assets/app-udSiXqFf.js" ]; then
    echo "❌ ERREUR: app-udSiXqFf.js manquant !"
    exit 1
fi

echo "✅ Assets présents"

# 3. Permissions
echo ""
echo "3. Correction des permissions..."
chmod -R 755 public/build
chmod -R 644 public/build/assets/*

# 4. Caches Laravel
echo ""
echo "4. Vidage des caches..."
php artisan config:clear
php artisan view:clear
php artisan config:cache

echo ""
echo "✅ Terminé !"
echo ""
echo "📋 Vérifier dans le navigateur :"
echo "   - Ouvrir F12 → Network"
echo "   - Recharger la page (Ctrl+Shift+R)"
echo "   - Vérifier que app-DVlYVwTs.css et app-udSiXqFf.js sont chargés (200)"
echo ""
echo "🔍 URLs à tester :"
echo "   https://account.herime.com/build/assets/app-DVlYVwTs.css"
echo "   https://account.herime.com/build/assets/app-udSiXqFf.js"

