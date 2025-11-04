#!/bin/bash
# Script pour corriger tous les problèmes de chargement de données
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔧 Correction complète du chargement de données"
echo "================================================"

# 1. Permissions clés Passport
log "1. Correction permissions clés Passport..."
chmod 600 storage/oauth-private.key 2>/dev/null || error "Impossible de changer permissions oauth-private.key"
chmod 600 storage/oauth-public.key 2>/dev/null || error "Impossible de changer permissions oauth-public.key"
log "Permissions corrigées (600/600)"

# 2. Vérifier client Passport
log "2. Vérification client Passport..."
if ! php artisan tinker --execute="echo DB::table('oauth_personal_access_clients')->exists() ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists"; then
    warning "Client manquant, création..."
    php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction >/dev/null 2>&1 || error "Échec création client"
    log "Client créé"
else
    log "Client existe"
fi

# 3. Vider les caches
log "3. Nettoyage des caches..."
php artisan config:clear >/dev/null 2>&1
php artisan cache:clear >/dev/null 2>&1
php artisan route:clear >/dev/null 2>&1
php artisan view:clear >/dev/null 2>&1

# 4. Reconstruire les caches
log "4. Reconstruction des caches..."
php artisan config:cache >/dev/null 2>&1 || error "Échec cache config"
php artisan route:cache >/dev/null 2>&1 || error "Échec cache routes"
php artisan view:cache >/dev/null 2>&1 || error "Échec cache vues"
php artisan optimize >/dev/null 2>&1 || error "Échec optimize"

# 5. Test final
log "5. Test de l'API..."
if php artisan tinker --execute="\$user = \App\Models\User::first(); if(\$user) { try { \$token = \$user->createToken('Test'); echo 'SUCCESS'; } catch(\Exception \$e) { echo 'ERROR'; } } else { echo 'NO_USER'; }" 2>/dev/null | grep -q "SUCCESS"; then
    log "Test token réussi"
else
    error "Test token échoué"
fi

echo ""
echo "================================================"
log "✅ Correction terminée !"
echo ""
echo "Les données devraient maintenant se charger correctement."

