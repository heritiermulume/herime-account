#!/bin/bash
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'
log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Diagnostic complet de la production"
echo "======================================"
echo ""

echo "1. Dernières erreurs dans les logs:"
echo "-----------------------------------"
tail -n 200 storage/logs/laravel.log | grep -B 5 -A 30 "local.ERROR" | tail -40
echo ""

echo "2. Vérification Passport:"
echo "-----------------------------------"
if [ -f "storage/oauth-private.key" ] && [ -f "storage/oauth-public.key" ]; then
    log "Clés Passport présentes"
else
    error "Clés Passport manquantes"
fi

if php artisan tinker --execute="echo DB::table('oauth_personal_access_clients')->exists() ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists"; then
    log "Client Passport existe"
else
    error "Client Passport manquant"
fi
echo ""

echo "3. Vérification base de données:"
echo "-----------------------------------"
if php artisan migrate:status >/dev/null 2>&1; then
    log "Base de données accessible"
else
    error "Base de données inaccessible"
fi
echo ""

echo "4. Vérification .env:"
echo "-----------------------------------"
if grep -q "APP_KEY=" .env && ! grep -q "APP_KEY=$" .env; then
    log "APP_KEY configuré"
else
    error "APP_KEY manquant"
fi

if grep -q "APP_ENV=production" .env; then
    log "APP_ENV=production"
else
    warning "APP_ENV n'est pas en production"
fi

if grep -q "APP_DEBUG=false" .env; then
    log "APP_DEBUG=false"
else
    warning "APP_DEBUG n'est pas false"
fi
echo ""

echo "5. Vérification caches:"
echo "-----------------------------------"
if [ -f "bootstrap/cache/config.php" ]; then
    log "Cache config présent"
else
    warning "Cache config manquant"
fi

if [ -f "bootstrap/cache/routes-v7.php" ] || [ -f "bootstrap/cache/routes.php" ]; then
    log "Cache routes présent"
else
    warning "Cache routes manquant"
fi
echo ""

