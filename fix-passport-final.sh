#!/bin/bash
set -e
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'
log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔧 Correction finale Passport"
echo "=============================="

# 1. Corriger les permissions des clés Passport
log "1. Correction permissions clés Passport..."
chmod 600 storage/oauth-private.key 2>/dev/null || error "Impossible de changer permissions oauth-private.key"
chmod 644 storage/oauth-public.key 2>/dev/null || error "Impossible de changer permissions oauth-public.key"
log "Permissions corrigées (600/644)"

# 2. Vérifier et créer le client personnel correctement
log "2. Vérification client Passport..."
CLIENT_EXISTS=$(php artisan tinker --execute="echo DB::table('oauth_personal_access_clients')->exists() ? 'yes' : 'no';" 2>/dev/null | grep -q "yes" && echo "yes" || echo "no")

if [ "$CLIENT_EXISTS" = "no" ]; then
    warning "Client manquant, création..."
    # Supprimer les anciens clients personnels s'ils existent
    php artisan tinker --execute="DB::table('oauth_clients')->where('name', 'LIKE', '%Personal Access Client%')->delete(); echo 'cleaned';" >/dev/null 2>&1 || true
    php artisan tinker --execute="DB::table('oauth_personal_access_clients')->delete(); echo 'cleaned';" >/dev/null 2>&1 || true
    
    # Créer le nouveau client
    OUTPUT=$(php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction 2>&1)
    if echo "$OUTPUT" | grep -q "successfully"; then
        log "Client créé avec succès"
    else
        error "Échec création client"
    fi
else
    log "Client existe déjà"
fi

# 3. Vérifier que le client est bien lié au provider 'users'
log "3. Vérification association client/provider..."
CLIENT_ID=$(php artisan tinker --execute="\$client = DB::table('oauth_personal_access_clients')->first(); if(\$client) { \$c = DB::table('oauth_clients')->where('id', \$client->client_id)->first(); echo \$c ? \$c->id : 'none'; } else { echo 'none'; }" 2>/dev/null)

if [ "$CLIENT_ID" = "none" ] || [ -z "$CLIENT_ID" ]; then
    warning "Client non associé, recréation..."
    php artisan tinker --execute="DB::table('oauth_personal_access_clients')->delete(); DB::table('oauth_clients')->where('name', 'LIKE', '%Personal Access Client%')->delete(); echo 'cleaned';" >/dev/null 2>&1 || true
    php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction >/dev/null 2>&1 || error "Échec recréation"
    log "Client recréé"
fi

# 4. Vérification finale
log "4. Test final..."
if php artisan tinker --execute="\$user = \App\Models\User::first(); if(\$user) { try { \$token = \$user->createToken('Test'); echo 'SUCCESS'; } catch(\Exception \$e) { echo 'ERROR: ' . \$e->getMessage(); } } else { echo 'NO_USER'; }" 2>/dev/null | grep -q "SUCCESS"; then
    log "Test token réussi ✅"
else
    error "Test token échoué"
fi

echo ""
echo "=============================="
log "✅ Correction Passport terminée !"

