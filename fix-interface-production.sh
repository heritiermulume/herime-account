#!/bin/bash
# Script pour corriger l'interface qui ne s'affiche plus en production
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔧 Correction de l'interface en production"
echo "==========================================="

# 1. Pull les dernières modifications
echo ""
log "1. Pull des dernières modifications..."
git pull origin main || warning "Impossible de pull (vérifiez manuellement)"

# 2. Vérifier les permissions
echo ""
log "2. Vérification des permissions..."
chmod -R 755 storage bootstrap/cache 2>/dev/null || warning "Impossible de changer les permissions"
chmod -R 755 public/build 2>/dev/null || warning "Impossible de changer les permissions de public/build"

# 3. Vérifier que les assets sont présents
echo ""
log "3. Vérification des assets..."
if [ -f "public/build/manifest.json" ]; then
    log "Manifest.json présent"
    if [ -d "public/build/assets" ] && [ "$(ls -A public/build/assets/*.js 2>/dev/null | wc -l)" -gt 0 ]; then
        log "Assets JS présents"
    else
        error "Assets JS manquants !"
        warning "Vous devez compiler les assets localement et les pousser sur GitHub"
        exit 1
    fi
else
    error "Manifest.json manquant !"
    warning "Vous devez compiler les assets localement et les pousser sur GitHub"
    exit 1
fi

# 4. Vider les caches Laravel
echo ""
log "4. Vidage des caches Laravel..."
php artisan config:clear 2>/dev/null || warning "config:clear échoué"
php artisan cache:clear 2>/dev/null || warning "cache:clear échoué"
php artisan route:clear 2>/dev/null || warning "route:clear échoué"
php artisan view:clear 2>/dev/null || warning "view:clear échoué"

# 5. Recréer les caches
echo ""
log "5. Recréation des caches..."
php artisan config:cache 2>/dev/null || warning "config:cache échoué"
php artisan route:cache 2>/dev/null || warning "route:cache échoué"
php artisan view:cache 2>/dev/null || warning "view:cache échoué"

# 6. Vérifier le lien symbolique storage
echo ""
log "6. Vérification du lien symbolique storage..."
if [ ! -L "public/storage" ]; then
    warning "Lien symbolique public/storage manquant, création..."
    php artisan storage:link 2>/dev/null || warning "storage:link échoué"
else
    log "Lien symbolique storage présent"
fi

echo ""
echo "==========================================="
log "✅ Corrections appliquées !"
warning "📋 Si l'interface ne s'affiche toujours pas :"
echo "   1. Vérifier la console navigateur (F12) pour les erreurs JS"
echo "   2. Vérifier les logs Laravel: tail -f storage/logs/laravel.log"
echo "   3. Vérifier que les assets sont bien servis (Network tab dans F12)"
echo "   4. Vider le cache navigateur (Ctrl+Shift+R)"

