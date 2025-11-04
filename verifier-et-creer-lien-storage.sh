#!/bin/bash
# Script pour vérifier et créer le lien symbolique storage
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Vérification du lien symbolique storage"
echo "============================================"

# 1. Vérifier si le lien existe
echo ""
log "1. Vérification du lien symbolique..."
if [ -L "public/storage" ]; then
    log "✅ Le lien symbolique public/storage existe"
    TARGET=$(readlink -f public/storage)
    log "   Pointe vers: $TARGET"
elif [ -d "public/storage" ]; then
    warning "⚠️ public/storage existe mais n'est pas un lien symbolique"
    log "   C'est un répertoire, suppression..."
    rm -rf public/storage
else
    warning "⚠️ Le lien symbolique public/storage n'existe pas"
fi

# 2. Créer le lien symbolique si nécessaire
echo ""
log "2. Création du lien symbolique..."
if [ ! -L "public/storage" ]; then
    php artisan storage:link
    if [ -L "public/storage" ]; then
        log "✅ Lien symbolique créé avec succès"
    else
        error "❌ Échec de la création du lien symbolique"
        exit 1
    fi
else
    log "✅ Lien symbolique déjà présent"
fi

# 3. Vérifier les permissions
echo ""
log "3. Vérification des permissions..."
if [ -d "storage/app/public" ]; then
    chmod -R 775 storage/app/public 2>/dev/null || true
    log "✅ Permissions corrigées sur storage/app/public"
else
    warning "⚠️ Le répertoire storage/app/public n'existe pas"
    mkdir -p storage/app/public
    chmod -R 775 storage/app/public
    log "✅ Répertoire créé avec les bonnes permissions"
fi

# 4. Créer le répertoire avatars s'il n'existe pas
echo ""
log "4. Vérification du répertoire avatars..."
if [ ! -d "storage/app/public/avatars" ]; then
    mkdir -p storage/app/public/avatars
    chmod 775 storage/app/public/avatars
    log "✅ Répertoire avatars créé"
else
    log "✅ Répertoire avatars existe"
fi

# 5. Vérifier un exemple d'avatar
echo ""
log "5. Vérification des avatars existants..."
AVATAR_COUNT=$(find storage/app/public/avatars -type f 2>/dev/null | wc -l)
if [ "$AVATAR_COUNT" -gt 0 ]; then
    log "✅ $AVATAR_COUNT avatar(s) trouvé(s)"
    ls -lh storage/app/public/avatars/ | head -5
else
    warning "⚠️ Aucun avatar trouvé dans storage/app/public/avatars"
fi

echo ""
echo "============================================"
log "✅ Vérification terminée !"
echo ""
warning "💡 Si la photo ne se charge toujours pas :"
echo "   1. Vérifiez que APP_URL est correct dans .env"
echo "   2. Vérifiez les logs : tail -f storage/logs/laravel.log"
echo "   3. Testez l'URL directement dans le navigateur"

