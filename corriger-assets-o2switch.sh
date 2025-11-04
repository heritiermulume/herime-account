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
    echo "   Les assets doivent être compilés localement et poussés sur GitHub"
    exit 1
fi

# Extraire les noms des fichiers depuis le manifest
CSS_FILE=$(grep -A 3 '"resources/css/app.css"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)
JS_FILE=$(grep -A 3 '"resources/js/app.js"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)

echo "   CSS attendu: $CSS_FILE"
echo "   JS attendu: $JS_FILE"

if [ -z "$CSS_FILE" ]; then
    echo "❌ ERREUR: Impossible de trouver le fichier CSS dans le manifest"
    exit 1
fi

if [ -z "$JS_FILE" ]; then
    echo "❌ ERREUR: Impossible de trouver le fichier JS dans le manifest"
    exit 1
fi

if [ ! -f "public/build/$CSS_FILE" ]; then
    echo "❌ ERREUR: $CSS_FILE manquant !"
    echo "   Vérifiez que les assets sont bien commités sur GitHub"
    echo "   Liste des fichiers présents dans public/build/assets/:"
    ls -la public/build/assets/ 2>/dev/null || echo "   (dossier vide ou inexistant)"
    exit 1
fi

if [ ! -f "public/build/$JS_FILE" ]; then
    echo "❌ ERREUR: $JS_FILE manquant !"
    echo "   Vérifiez que les assets sont bien commités sur GitHub"
    echo "   Liste des fichiers présents dans public/build/assets/:"
    ls -la public/build/assets/ 2>/dev/null || echo "   (dossier vide ou inexistant)"
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

