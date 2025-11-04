#!/bin/bash
# Script pour corriger le chargement des données en production
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔧 Correction du chargement des données en production"
echo "======================================================"

# 1. Pull
echo ""
log "1. Pull des dernières modifications..."
git pull origin main || warning "Pull échoué (vérifiez manuellement)"

# 2. Vérifier les clés Passport
echo ""
log "2. Vérification des clés Passport..."
if [ -f "storage/oauth-private.key" ] && [ -f "storage/oauth-public.key" ]; then
    log "Clés Passport présentes"
    chmod 600 storage/oauth-private.key 2>/dev/null || true
    chmod 644 storage/oauth-public.key 2>/dev/null || true
else
    warning "Clés Passport manquantes, génération..."
    php artisan passport:keys --force 2>/dev/null || warning "Génération des clés échouée"
fi

# 3. Vérifier le client Passport
echo ""
log "3. Vérification du client Passport..."
CLIENT_COUNT=$(php artisan tinker --execute='echo \Laravel\Passport\Client::where("personal_access_client", 1)->where("revoked", 0)->count();' 2>&1 | grep -v "Tinker" | tail -1)
if [ "$CLIENT_COUNT" -eq 0 ]; then
    warning "Client Passport manquant, création..."
    php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction 2>/dev/null || warning "Création du client échouée"
else
    log "Client Passport présent ($CLIENT_COUNT client(s))"
fi

# 4. Vider TOUS les caches
echo ""
log "4. Vidage complet des caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan event:clear 2>/dev/null || true
php artisan optimize:clear 2>/dev/null || true

# 5. Recréer les caches
echo ""
log "5. Recréation des caches..."
php artisan config:cache 2>/dev/null || warning "config:cache échoué"
php artisan route:cache 2>/dev/null || warning "route:cache échoué"
php artisan view:cache 2>/dev/null || warning "view:cache échoué"

# 6. Vérifier la connexion DB
echo ""
log "6. Vérification de la connexion DB..."
php artisan tinker --execute='try { DB::connection()->getPdo(); echo "DB OK" . PHP_EOL; } catch (\Exception $e) { echo "DB ERROR: " . $e->getMessage() . PHP_EOL; }' 2>&1 | grep -v "Tinker" || warning "Erreur de connexion DB"

# 7. Vérifier les sessions
echo ""
log "7. Vérification des sessions en DB..."
php artisan tinker --execute='$count = \App\Models\UserSession::count(); echo "Sessions: " . $count . PHP_EOL;' 2>&1 | grep -v "Tinker" || warning "Erreur lors de la vérification"

# 8. Vérifier les permissions
echo ""
log "8. Correction des permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || warning "Impossible de changer les permissions"
chmod -R 755 public/build 2>/dev/null || warning "Impossible de changer les permissions de public/build"

# 9. Vérifier APP_ENV
echo ""
log "9. Vérification de APP_ENV..."
APP_ENV=$(grep "^APP_ENV=" .env 2>/dev/null | cut -d'=' -f2 || echo "non trouvé")
log "APP_ENV: $APP_ENV"
if [ "$APP_ENV" != "production" ]; then
    warning "APP_ENV n'est pas 'production' (actuel: $APP_ENV)"
fi

# 10. Vérifier APP_DEBUG
echo ""
log "10. Vérification de APP_DEBUG..."
APP_DEBUG=$(grep "^APP_DEBUG=" .env 2>/dev/null | cut -d'=' -f2 || echo "non trouvé")
log "APP_DEBUG: $APP_DEBUG"
if [ "$APP_DEBUG" = "true" ]; then
    warning "APP_DEBUG est 'true' - devrait être 'false' en production"
fi

echo ""
echo "======================================================"
log "✅ Corrections appliquées !"
warning "📋 Si le problème persiste :"
echo "   1. Vérifier les logs: tail -f storage/logs/laravel.log"
echo "   2. Vérifier la console navigateur (F12)"
echo "   3. Tester l'API: ./tester-api-sessions.sh"
echo "   4. Vérifier que le token est bien envoyé dans les headers"

