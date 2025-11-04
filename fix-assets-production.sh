#!/bin/bash
# Script pour corriger le chargement des assets en production
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔧 Correction du chargement des assets en production"
echo "====================================================="

# 1. Pull les dernières modifications
echo ""
log "1. Pull des dernières modifications..."
git pull origin main || warning "Impossible de pull (vérifiez manuellement)"

# 2. Vérifier que les assets sont présents
echo ""
log "2. Vérification des assets..."
if [ ! -f "public/build/manifest.json" ]; then
    error "Manifest.json manquant !"
    warning "Les assets doivent être compilés localement et poussés sur GitHub"
    exit 1
fi

# 3. Vérifier les permissions
echo ""
log "3. Correction des permissions..."
chmod -R 755 public/build 2>/dev/null || warning "Impossible de changer les permissions"
chmod -R 644 public/build/assets/* 2>/dev/null || warning "Impossible de changer les permissions des assets"

# 4. Vérifier que les fichiers référencés existent
echo ""
log "4. Vérification des fichiers référencés..."
APP_JS=$(grep -A 3 '"resources/js/app.js"' public/build/manifest.json 2>/dev/null | grep '"file"' | cut -d'"' -f4)
APP_CSS=$(grep -A 3 '"resources/css/app.css"' public/build/manifest.json 2>/dev/null | grep '"file"' | cut -d'"' -f4)

if [ -n "$APP_JS" ]; then
    if [ -f "public/build/$APP_JS" ]; then
        log "Fichier JS présent: $APP_JS"
    else
        error "Fichier JS manquant: $APP_JS"
        warning "Les assets doivent être recompilés et poussés sur GitHub"
    fi
fi

if [ -n "$APP_CSS" ]; then
    if [ -f "public/build/$APP_CSS" ]; then
        log "Fichier CSS présent: $APP_CSS"
    else
        error "Fichier CSS manquant: $APP_CSS"
        warning "Les assets doivent être recompilés et poussés sur GitHub"
    fi
fi

# 5. Vider les caches Laravel
echo ""
log "5. Vidage des caches Laravel..."
php artisan config:clear 2>/dev/null || warning "config:clear échoué"
php artisan cache:clear 2>/dev/null || warning "cache:clear échoué"
php artisan view:clear 2>/dev/null || warning "view:clear échoué"

# 6. Recréer les caches
echo ""
log "6. Recréation des caches..."
php artisan config:cache 2>/dev/null || warning "config:cache échoué"
php artisan view:cache 2>/dev/null || warning "view:cache échoué"

# 7. Vérifier le serveur web peut accéder aux fichiers
echo ""
log "7. Vérification de l'accessibilité..."
if [ -n "$APP_JS" ] && [ -f "public/build/$APP_JS" ]; then
    if [ -r "public/build/$APP_JS" ]; then
        log "Fichier JS accessible"
    else
        error "Fichier JS non accessible"
        chmod 644 "public/build/$APP_JS" 2>/dev/null || true
    fi
fi

if [ -n "$APP_CSS" ] && [ -f "public/build/$APP_CSS" ]; then
    if [ -r "public/build/$APP_CSS" ]; then
        log "Fichier CSS accessible"
    else
        error "Fichier CSS non accessible"
        chmod 644 "public/build/$APP_CSS" 2>/dev/null || true
    fi
fi

# 8. Afficher les URLs à vérifier
echo ""
log "8. URLs à vérifier dans le navigateur..."
if [ -n "$APP_JS" ]; then
    echo "   JS: https://account.herime.com/build/$APP_JS"
fi
if [ -n "$APP_CSS" ]; then
    echo "   CSS: https://account.herime.com/build/$APP_CSS"
fi

echo ""
echo "====================================================="
log "✅ Corrections appliquées !"
warning "📋 Si les assets ne se chargent toujours pas :"
echo "   1. Vérifier dans la console navigateur (F12) les erreurs 404"
echo "   2. Vérifier que les fichiers sont accessibles via les URLs ci-dessus"
echo "   3. Vérifier les logs du serveur web"
echo "   4. Vérifier que .env a APP_ENV=production"
echo "   5. Vider le cache navigateur (Ctrl+Shift+R)"

