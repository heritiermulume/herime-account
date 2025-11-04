#!/bin/bash
set -e
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'
log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }

echo "🔧 Fix Production"
git fetch origin main 2>/dev/null || true
git rm --cached bootstrap/cache/.gitignore storage/app/.gitignore storage/app/private/.gitignore storage/app/public/.gitignore storage/framework/.gitignore storage/framework/cache/.gitignore storage/framework/cache/data/.gitignore storage/framework/sessions/.gitignore storage/framework/testing/.gitignore storage/framework/views/.gitignore storage/logs/.gitignore 2>/dev/null || true
rm -f public/build/manifest.json public/build/assets/*.css public/build/assets/*.js 2>/dev/null || true
git clean -fd public/build/ 2>/dev/null || true
if ! git pull origin main 2>/dev/null; then
    git reset --hard origin/main 2>/dev/null || error "Erreur Git"
    git pull origin main 2>/dev/null || error "Erreur pull"
fi
log "Git OK"
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    php artisan key:generate --force >/dev/null 2>&1
fi
OAUTH_EXISTS=$(php artisan tinker --execute="echo Schema::hasTable('oauth_auth_codes') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes" && echo "yes" || echo "no")
if [ "$OAUTH_EXISTS" = "yes" ]; then
    php artisan tinker --execute="DB::table('migrations')->insertOrIgnore([['migration' => '2016_06_01_000001_create_oauth_auth_codes_table', 'batch' => 1], ['migration' => '2016_06_01_000002_create_oauth_access_tokens_table', 'batch' => 1], ['migration' => '2016_06_01_000003_create_oauth_refresh_tokens_table', 'batch' => 1], ['migration' => '2016_06_01_000004_create_oauth_clients_table', 'batch' => 1], ['migration' => '2024_06_01_000001_create_oauth_device_codes_table', 'batch' => 1]]); echo 'done';" >/dev/null 2>&1
fi
find database/migrations -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" | xargs rm -f 2>/dev/null || true
if [ ! -f "storage/oauth-private.key" ] || [ ! -f "storage/oauth-public.key" ]; then
    php artisan passport:keys --force >/dev/null 2>&1
fi
if php artisan tinker --execute="echo Schema::hasTable('oauth_personal_access_clients') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
    CLIENT_EXISTS=$(php artisan tinker --execute="echo DB::table('oauth_personal_access_clients')->exists() ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists" && echo "yes" || echo "no")
    if [ "$CLIENT_EXISTS" = "no" ]; then
        php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction >/dev/null 2>&1
    fi
else
    php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction >/dev/null 2>&1
fi
php artisan config:clear >/dev/null 2>&1
php artisan cache:clear >/dev/null 2>&1
php artisan route:clear >/dev/null 2>&1
php artisan view:clear >/dev/null 2>&1
php artisan config:cache >/dev/null 2>&1 || error "Cache config"
php artisan route:cache >/dev/null 2>&1 || error "Cache routes"
php artisan view:cache >/dev/null 2>&1 || error "Cache vues"
php artisan optimize >/dev/null 2>&1 || error "Optimize"
log "✅ Fix terminé !"
