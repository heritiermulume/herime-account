#!/bin/bash
# Script de diagnostic pour l'interface qui ne s'affiche plus en production
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Diagnostic de l'interface en production"
echo "=========================================="

# 1. Vérifier le manifest.json
echo ""
log "1. Vérification du manifest.json..."
if [ -f "public/build/manifest.json" ]; then
    log "Manifest.json existe"
    if grep -q "resources/js/app.js" public/build/manifest.json; then
        log "Entry point app.js trouvé dans le manifest"
        APP_JS=$(grep -A 3 '"resources/js/app.js"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)
        if [ -n "$APP_JS" ]; then
            if [ -f "public/build/$APP_JS" ]; then
                log "Fichier JS trouvé: $APP_JS"
            else
                error "Fichier JS manquant: $APP_JS"
            fi
        fi
    else
        error "Entry point app.js non trouvé dans le manifest"
    fi
else
    error "Manifest.json manquant !"
fi

# 2. Vérifier les fichiers CSS
echo ""
log "2. Vérification des fichiers CSS..."
if [ -f "public/build/manifest.json" ]; then
    CSS_FILE=$(grep -A 3 '"resources/css/app.css"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)
    if [ -n "$CSS_FILE" ]; then
        if [ -f "public/build/$CSS_FILE" ]; then
            log "Fichier CSS trouvé: $CSS_FILE"
        else
            error "Fichier CSS manquant: $CSS_FILE"
        fi
    else
        warning "Fichier CSS non trouvé dans le manifest"
    fi
fi

# 3. Vérifier les assets
echo ""
log "3. Vérification des assets..."
ASSET_COUNT=$(ls -1 public/build/assets/*.js 2>/dev/null | wc -l)
if [ "$ASSET_COUNT" -gt 0 ]; then
    log "$ASSET_COUNT fichiers JS trouvés dans public/build/assets/"
else
    error "Aucun fichier JS trouvé dans public/build/assets/"
fi

# 4. Vérifier la configuration Vite
echo ""
log "4. Vérification de la configuration..."
if [ -f "vite.config.js" ]; then
    log "vite.config.js existe"
else
    error "vite.config.js manquant"
fi

# 5. Vérifier les routes dans bootstrap.js
echo ""
log "5. Vérification de bootstrap.js..."
if grep -q "baseURL = window.location.origin + '/api'" resources/js/bootstrap.js 2>/dev/null; then
    log "baseURL correctement configuré"
else
    error "baseURL incorrect ou manquant"
fi

# 6. Vérifier s'il y a des appels /api/ dans le code
echo ""
log "6. Vérification des routes API..."
BAD_ROUTES=$(grep -r "['\"\`]/api/" resources/js/components/ --include="*.vue" 2>/dev/null | wc -l)
if [ "$BAD_ROUTES" -eq 0 ]; then
    log "Aucun appel avec /api/ trouvé (correct)"
else
    error "$BAD_ROUTES appels avec /api/ trouvés"
fi

echo ""
echo "=========================================="
warning "📋 Actions recommandées si erreurs :"
echo "   1. Recompiler les assets: npm run build"
echo "   2. Vérifier les logs Laravel: tail -f storage/logs/laravel.log"
echo "   3. Vérifier la console navigateur (F12)"
echo "   4. Vérifier que APP_ENV=production dans .env"
echo "   5. Vider les caches: php artisan config:clear && php artisan cache:clear"

