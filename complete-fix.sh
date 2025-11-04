#!/bin/bash
set -e
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'
log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔧 Correction complète de la production"
echo "========================================"

# 1. Permissions
log "1. Permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# 2. APP_KEY
log "2. Vérification APP_KEY..."
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    php artisan key:generate --force >/dev/null 2>&1 || warning "Échec APP_KEY"
fi

# 3. Migrations OAuth
log "3. Migrations OAuth..."
OAUTH_EXISTS=$(php artisan tinker --execute="echo Schema::hasTable('oauth_auth_codes') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes" && echo "yes" || echo "no")
if [ "$OAUTH_EXISTS" = "yes" ]; then
    php artisan tinker --execute="DB::table('migrations')->insertOrIgnore([['migration' => '2016_06_01_000001_create_oauth_auth_codes_table', 'batch' => 1], ['migration' => '2016_06_01_000002_create_oauth_access_tokens_table', 'batch' => 1], ['migration' => '2016_06_01_000003_create_oauth_refresh_tokens_table', 'batch' => 1], ['migration' => '2016_06_01_000004_create_oauth_clients_table', 'batch' => 1], ['migration' => '2024_06_01_000001_create_oauth_device_codes_table', 'batch' => 1]]); echo 'done';" >/dev/null 2>&1
    log "Migrations OAuth marquées"
fi

# 4. Nettoyer migrations en double
log "4. Nettoyage migrations OAuth en double..."
find database/migrations -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" | xargs rm -f 2>/dev/null || true

# 5. Clés Passport
log "5. Clés Passport..."
if [ ! -f "storage/oauth-private.key" ] || [ ! -f "storage/oauth-public.key" ]; then
    php artisan passport:keys --force >/dev/null 2>&1 || warning "Échec clés Passport"
    log "Clés générées"
fi

# 6. Client Passport
log "6. Client Passport..."
if php artisan tinker --execute="echo Schema::hasTable('oauth_personal_access_clients') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
    CLIENT_EXISTS=$(php artisan tinker --execute="echo DB::table('oauth_personal_access_clients')->exists() ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists" && echo "yes" || echo "no")
    if [ "$CLIENT_EXISTS" = "no" ]; then
        php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction >/dev/null 2>&1 || warning "Échec client"
        log "Client créé"
    else
        log "Client existe"
    fi
else
    php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction >/dev/null 2>&1 || warning "Échec client"
    log "Client créé"
fi

# 7. Assets
log "7. Assets frontend..."
if [ -f "public/build/manifest.json" ]; then
    log "Assets présents"
else
    warning "Assets manquants"
fi

# 8. Test token
log "8. Test création token..."
if php artisan tinker --execute="\$user = \App\Models\User::first(); if(\$user) { try { \$token = \$user->createToken('Test'); echo 'SUCCESS'; } catch(\Exception \$e) { echo 'ERROR: ' . \$e->getMessage(); } } else { echo 'NO_USER'; }" 2>/dev/null | grep -q "SUCCESS"; then
    log "Test token réussi"
else
    warning "Test token échoué"
fi

echo ""
echo "========================================"
log "✅ Correction complète terminée !"
