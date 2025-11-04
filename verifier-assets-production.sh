#!/bin/bash
# Script pour vérifier et corriger le chargement des assets en production
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Vérification des assets en production"
echo "========================================"

# 1. Vérifier le manifest.json
echo ""
log "1. Vérification du manifest.json..."
if [ -f "public/build/manifest.json" ]; then
    log "Manifest.json présent"
    MANIFEST_SIZE=$(wc -c < public/build/manifest.json)
    if [ "$MANIFEST_SIZE" -gt 0 ]; then
        log "Manifest.json non vide ($MANIFEST_SIZE bytes)"
        
        # Vérifier les entrées
        if grep -q "resources/js/app.js" public/build/manifest.json; then
            APP_JS=$(grep -A 3 '"resources/js/app.js"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)
            log "App JS: $APP_JS"
            
            if [ -f "public/build/$APP_JS" ]; then
                JS_SIZE=$(wc -c < "public/build/$APP_JS")
                log "Fichier JS présent ($JS_SIZE bytes)"
            else
                error "Fichier JS manquant: $APP_JS"
            fi
        else
            error "Entry app.js non trouvée dans le manifest"
        fi
        
        if grep -q "resources/css/app.css" public/build/manifest.json; then
            APP_CSS=$(grep -A 3 '"resources/css/app.css"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)
            log "App CSS: $APP_CSS"
            
            if [ -f "public/build/$APP_CSS" ]; then
                CSS_SIZE=$(wc -c < "public/build/$APP_CSS")
                log "Fichier CSS présent ($CSS_SIZE bytes)"
            else
                error "Fichier CSS manquant: $APP_CSS"
            fi
        else
            error "Entry app.css non trouvée dans le manifest"
        fi
    else
        error "Manifest.json vide !"
    fi
else
    error "Manifest.json manquant !"
    warning "Vous devez compiler les assets avec: npm run build"
    exit 1
fi

# 2. Vérifier les permissions
echo ""
log "2. Vérification des permissions..."
if [ -d "public/build" ]; then
    PERMS=$(stat -c "%a" public/build 2>/dev/null || stat -f "%A" public/build 2>/dev/null || echo "unknown")
    log "Permissions de public/build: $PERMS"
    
    if [ "$PERMS" != "755" ] && [ "$PERMS" != "775" ]; then
        warning "Permissions de public/build: $PERMS (recommandé: 755)"
        log "Correction des permissions..."
        chmod -R 755 public/build 2>/dev/null || warning "Impossible de changer les permissions"
    fi
fi

# 3. Vérifier que les fichiers sont accessibles
echo ""
log "3. Vérification de l'accessibilité..."
if [ -f "public/build/manifest.json" ]; then
    APP_JS=$(grep -A 3 '"resources/js/app.js"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)
    APP_CSS=$(grep -A 3 '"resources/css/app.css"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)
    
    if [ -n "$APP_JS" ] && [ -f "public/build/$APP_JS" ]; then
        if [ -r "public/build/$APP_JS" ]; then
            log "Fichier JS accessible en lecture"
        else
            error "Fichier JS non accessible en lecture"
        fi
    fi
    
    if [ -n "$APP_CSS" ] && [ -f "public/build/$APP_CSS" ]; then
        if [ -r "public/build/$APP_CSS" ]; then
            log "Fichier CSS accessible en lecture"
        else
            error "Fichier CSS non accessible en lecture"
        fi
    fi
fi

# 4. Vérifier la configuration Laravel
echo ""
log "4. Vérification de la configuration Laravel..."
if [ -f ".env" ]; then
    APP_ENV=$(grep "^APP_ENV=" .env | cut -d'=' -f2)
    APP_DEBUG=$(grep "^APP_DEBUG=" .env | cut -d'=' -f2)
    
    log "APP_ENV: $APP_ENV"
    log "APP_DEBUG: $APP_DEBUG"
    
    if [ "$APP_ENV" != "production" ]; then
        warning "APP_ENV n'est pas 'production' (actuel: $APP_ENV)"
    fi
else
    warning ".env non trouvé"
fi

# 5. Vérifier les URLs dans le manifest
echo ""
log "5. Vérification du contenu du manifest..."
if grep -q "assets/" public/build/manifest.json; then
    log "Les chemins dans le manifest utilisent 'assets/' (correct)"
else
    warning "Les chemins dans le manifest ne semblent pas corrects"
fi

echo ""
echo "========================================"
log "✅ Vérification terminée !"
warning "📋 Si des erreurs sont présentes :"
echo "   1. Vérifier que les assets sont bien compilés localement"
echo "   2. Vérifier que les assets sont bien commités sur GitHub"
echo "   3. Faire git pull sur O2Switch"
echo "   4. Vérifier les permissions: chmod -R 755 public/build"
echo "   5. Vérifier les logs du serveur web pour les erreurs 404"

