#!/bin/bash
# Script pour vérifier que toutes les routes API sont correctes en production
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Vérification des routes API en production"
echo "=============================================="

# Vérifier que bootstrap.js a la bonne baseURL
echo ""
log "1. Vérification de bootstrap.js..."
if grep -q "baseURL = window.location.origin + '/api'" resources/js/bootstrap.js 2>/dev/null; then
    log "baseURL correctement configuré"
else
    error "baseURL incorrect dans bootstrap.js"
fi

# Vérifier qu'il n'y a pas de /api/ dans les appels
echo ""
log "2. Vérification des appels API dans les composants..."
BAD_ROUTES=$(grep -r "['\"\`]/api/" resources/js/components/ --include="*.vue" --include="*.js" 2>/dev/null | grep -v "node_modules" | wc -l)
if [ "$BAD_ROUTES" -eq 0 ]; then
    log "Aucun appel avec /api/ trouvé (correct)"
else
    error "$BAD_ROUTES appels avec /api/ trouvés (incorrect)"
    grep -r "['\"\`]/api/" resources/js/components/ --include="*.vue" --include="*.js" 2>/dev/null | grep -v "node_modules"
fi

# Vérifier les routes dans les stores
echo ""
log "3. Vérification des stores..."
STORE_ROUTES=$(grep -r "['\"\`]/api/" resources/js/stores/ --include="*.js" 2>/dev/null | grep -v "node_modules" | wc -l)
if [ "$STORE_ROUTES" -eq 0 ]; then
    log "Aucun appel avec /api/ dans les stores (correct)"
else
    error "$STORE_ROUTES appels avec /api/ dans les stores"
    grep -r "['\"\`]/api/" resources/js/stores/ --include="*.js" 2>/dev/null | grep -v "node_modules"
fi

# Vérifier les assets compilés
echo ""
log "4. Vérification des assets compilés..."
if [ -d "public/build/assets" ]; then
    COMPILED_ROUTES=$(grep -r "/api/user/profile\|/api/sso/sessions" public/build/assets/*.js 2>/dev/null | wc -l)
    if [ "$COMPILED_ROUTES" -eq 0 ]; then
        log "Aucune route /api/ dans les assets compilés (correct)"
    else
        error "$COMPILED_ROUTES routes /api/ trouvées dans les assets compilés"
        warning "Il faut recompiler les assets avec: npm run build"
    fi
else
    warning "Dossier public/build/assets n'existe pas"
fi

echo ""
echo "=============================================="
log "✅ Vérification terminée !"

