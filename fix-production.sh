#!/bin/bash

# Script master pour résoudre TOUS les problèmes de production
# Usage: ./fix-production.sh

set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔧 Fix Production - Résolution automatique complète"
echo "=================================================="

# 1. Résoudre les conflits Git
echo ""
log "1. Résolution des conflits Git..."
git fetch origin main 2>/dev/null || true

# Supprimer les fichiers conflictuels du cache
git rm --cached bootstrap/cache/.gitignore storage/app/.gitignore storage/app/private/.gitignore storage/app/public/.gitignore storage/framework/.gitignore storage/framework/cache/.gitignore storage/framework/cache/data/.gitignore storage/framework/sessions/.gitignore storage/framework/testing/.gitignore storage/framework/views/.gitignore storage/logs/.gitignore 2>/dev/null || true

# Supprimer les fichiers localement (clé pour résoudre les conflits)
rm -f public/build/manifest.json 2>/dev/null || true
rm -f public/build/assets/*.css 2>/dev/null || true
rm -f public/build/assets/*.js 2>/dev/null || true
git clean -fd public/build/ 2>/dev/null || true

# Pull
if ! git pull origin main 2>/dev/null; then
    warning "Pull échoué, tentative de reset..."
    git reset --hard origin/main 2>/dev/null || error "Impossible de résoudre les conflits Git"
    git pull origin main 2>/dev/null || error "Impossible de faire le pull"
fi
log "Git pull réussi"

# 2. Permissions
echo ""
log "2. Correction des permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chmod 644 bootstrap/cache/.gitignore storage/app/.gitignore storage/framework/.gitignore storage/logs/.gitignore 2>/dev/null || true

# 3. .env et APP_KEY
echo ""
log "3. Vérification .env..."
if [ ! -f ".env" ]; then
    error "Fichier .env manquant"
fi
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    php artisan key:generate --force >/dev/null 2>&1 || warning "Échec génération APP_KEY"
    log "APP_KEY généré"
fi

# 4. Migrations OAuth
echo ""
log "4. Vérification migrations OAuth..."
OAUTH_EXISTS=$(php artisan tinker --execute="echo Schema::hasTable('oauth_auth_codes') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes" && echo "yes" || echo "no")
if [ "$OAUTH_EXISTS" = "yes" ]; then
    php artisan tinker --execute="
        DB::table('migrations')->insertOrIgnore([
            ['migration' => '2016_06_01_000001_create_oauth_auth_codes_table', 'batch' => 1],
            ['migration' => '2016_06_01_000002_create_oauth_access_tokens_table', 'batch' => 1],
            ['migration' => '2016_06_01_000003_create_oauth_refresh_tokens_table', 'batch' => 1],
            ['migration' => '2016_06_01_000004_create_oauth_clients_table', 'batch' => 1],
            ['migration' => '2024_06_01_000001_create_oauth_device_codes_table', 'batch' => 1]
        ]);
        echo 'done';
    " >/dev/null 2>&1
    log "Migrations OAuth marquées"
fi

# Nettoyer migrations en double
find database/migrations -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" | xargs rm -f 2>/dev/null || true

# 5. Passport keys
echo ""
log "5. Vérification clés Passport..."
if [ ! -f "storage/oauth-private.key" ] || [ ! -f "storage/oauth-public.key" ]; then
    php artisan passport:keys --force >/dev/null 2>&1 || warning "Échec génération clés Passport"
    log "Clés Passport générées"
fi

# 6. Client personnel Passport
echo ""
log "6. Vérification client Passport..."
if php artisan tinker --execute="echo Schema::hasTable('oauth_personal_access_clients') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
    CLIENT_EXISTS=$(php artisan tinker --execute="echo DB::table('oauth_personal_access_clients')->exists() ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists" && echo "yes" || echo "no")
    if [ "$CLIENT_EXISTS" = "no" ]; then
        php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction >/dev/null 2>&1 || warning "Échec création client"
        log "Client créé"
    fi
else
    php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction >/dev/null 2>&1 || warning "Échec création client"
    log "Client créé"
fi

# 7. Assets frontend
echo ""
log "7. Vérification assets frontend..."
if [ ! -f "public/build/manifest.json" ]; then
    warning "Assets frontend manquants"
else
    log "Assets présents"
fi

# 8. Caches
echo ""
log "8. Nettoyage et reconstruction des caches..."
php artisan config:clear >/dev/null 2>&1 || true
php artisan cache:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true
php artisan config:cache >/dev/null 2>&1 || error "Échec cache config"
php artisan route:cache >/dev/null 2>&1 || error "Échec cache routes"
php artisan view:cache >/dev/null 2>&1 || error "Échec cache vues"
php artisan optimize >/dev/null 2>&1 || error "Échec optimize"
log "Caches reconstruits"

# 9. Test final
echo ""
log "9. Test création token..."
if php artisan tinker --execute="\$user = \App\Models\User::first(); if(\$user) { try { \$token = \$user->createToken('Test'); echo 'SUCCESS'; } catch(\Exception \$e) { echo 'ERROR'; } } else { echo 'NO_USER'; }" 2>/dev/null | grep -q "SUCCESS"; then
    log "Test token réussi"
else
    warning "Test token échoué"
fi

echo ""
echo "=================================================="
echo -e "${GREEN}✅ Fix Production terminé !${NC}"
echo ""

