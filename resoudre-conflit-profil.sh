#!/bin/bash
# Script pour résoudre les conflits Git lors du pull des modifications du profil
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔧 Résolution des conflits Git pour le profil"
echo "============================================="

# 1. Supprimer les fichiers de build qui entrent en conflit
echo ""
log "1. Suppression des fichiers de build en conflit..."
rm -f public/build/assets/app-esfM1Ri2.js 2>/dev/null || true
rm -f public/build/manifest.json 2>/dev/null || true

# 2. Supprimer les fichiers .gitignore non suivis qui entreraient en conflit
echo ""
log "2. Nettoyage des fichiers .gitignore non suivis..."
rm -f bootstrap/cache/.gitignore 2>/dev/null || true
rm -f storage/app/.gitignore 2>/dev/null || true
rm -f storage/app/private/.gitignore 2>/dev/null || true
rm -f storage/app/public/.gitignore 2>/dev/null || true
rm -f storage/framework/.gitignore 2>/dev/null || true
rm -f storage/framework/cache/.gitignore 2>/dev/null || true
rm -f storage/framework/cache/data/.gitignore 2>/dev/null || true
rm -f storage/framework/sessions/.gitignore 2>/dev/null || true
rm -f storage/framework/testing/.gitignore 2>/dev/null || true
rm -f storage/framework/views/.gitignore 2>/dev/null || true
rm -f storage/logs/.gitignore 2>/dev/null || true

# 3. Supprimer les scripts temporaires qui seraient écrasés
echo ""
log "3. Nettoyage des scripts temporaires..."
rm -f complete-fix.sh 2>/dev/null || true
rm -f fix-passport-final.sh 2>/dev/null || true

# 4. Faire le pull
echo ""
log "4. Pull des dernières modifications..."
git pull origin main

# 5. Vérifier que les assets sont bien présents
echo ""
log "5. Vérification des assets..."
if [ -f "public/build/manifest.json" ]; then
    log "✅ manifest.json présent"
else
    warning "⚠️ manifest.json manquant, récupération depuis Git..."
    git checkout HEAD -- public/build/manifest.json 2>/dev/null || true
fi

# 6. Exécuter la migration
echo ""
log "6. Exécution de la migration..."
php artisan migrate --force

# 7. Vider les caches
echo ""
log "7. Vidage des caches..."
php artisan config:clear > /dev/null 2>&1 || true
php artisan cache:clear > /dev/null 2>&1 || true
php artisan route:clear > /dev/null 2>&1 || true
php artisan view:clear > /dev/null 2>&1 || true

# 8. Recréer les caches
echo ""
log "8. Recréation des caches..."
php artisan config:cache > /dev/null 2>&1 || true
php artisan route:cache > /dev/null 2>&1 || true

echo ""
echo "============================================="
log "✅ Conflits résolus et migration appliquée !"
echo ""
warning "💡 Vérifiez que tout fonctionne :"
echo "   - Les champs du profil sont tous visibles"
echo "   - La photo de profil peut être uploadée"
echo "   - Les préférences sont sauvegardées"

