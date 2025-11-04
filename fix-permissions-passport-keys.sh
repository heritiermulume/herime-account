#!/bin/bash
# Script pour corriger les permissions des clés Passport
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔧 Correction des permissions des clés Passport"
echo "================================================"

# 1. Vérifier que les fichiers existent
if [ ! -f "storage/oauth-private.key" ]; then
    error "Fichier oauth-private.key non trouvé"
    exit 1
fi

if [ ! -f "storage/oauth-public.key" ]; then
    error "Fichier oauth-public.key non trouvé"
    exit 1
fi

# 2. Afficher les permissions actuelles
echo ""
log "1. Permissions actuelles :"
ls -la storage/oauth-*.key

# 3. Corriger les permissions
echo ""
log "2. Correction des permissions..."
chmod 600 storage/oauth-private.key
chmod 600 storage/oauth-public.key

# 4. Vérifier les nouvelles permissions
echo ""
log "3. Vérification des nouvelles permissions :"
NEW_PERMS=$(ls -l storage/oauth-private.key | awk '{print $1}')
if [[ "$NEW_PERMS" == *"rw-------"* ]] || [[ "$NEW_PERMS" == *"-rw-------"* ]]; then
    log "✅ Permissions oauth-private.key correctes (600)"
else
    error "❌ Permissions oauth-private.key incorrectes: $NEW_PERMS"
fi

NEW_PERMS=$(ls -l storage/oauth-public.key | awk '{print $1}')
if [[ "$NEW_PERMS" == *"rw-------"* ]] || [[ "$NEW_PERMS" == *"-rw-------"* ]]; then
    log "✅ Permissions oauth-public.key correctes (600)"
else
    error "❌ Permissions oauth-public.key incorrectes: $NEW_PERMS"
fi

# 5. Vérifier le propriétaire
echo ""
log "4. Vérification du propriétaire :"
OWNER=$(ls -l storage/oauth-private.key | awk '{print $3":"$4}')
log "Propriétaire: $OWNER"

# 6. Vider les caches
echo ""
log "5. Vidage des caches..."
php artisan config:clear > /dev/null 2>&1 || true
php artisan cache:clear > /dev/null 2>&1 || true
php artisan route:clear > /dev/null 2>&1 || true

echo ""
echo "================================================"
log "✅ Corrections appliquées !"
echo ""
warning "💡 Testez maintenant l'API :"
echo "   ./tester-et-surveiller-logs.sh"

