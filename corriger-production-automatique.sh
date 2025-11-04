#!/bin/bash

# Script de correction automatique complète pour la production O2Switch
# Usage: ./corriger-production-automatique.sh
# Ce script doit être exécuté sur le serveur O2Switch

set -e

echo "🔧 Correction automatique de la production"
echo "=========================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[✓]${NC} $1"
}

error() {
    echo -e "${RED}[✗]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

info() {
    echo -e "${BLUE}[i]${NC} $1"
}

fix() {
    echo -e "${YELLOW}[🔧]${NC} $1"
}

# 1. Corriger les permissions
echo "1. Correction des permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chmod -R 755 storage/logs storage/framework 2>/dev/null || true
log "Permissions corrigées"

# 2. Vérifier et corriger .env
echo ""
echo "2. Vérification de .env..."
if [ ! -f ".env" ]; then
    error "Fichier .env manquant"
    exit 1
fi

if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    fix "Génération de APP_KEY..."
    php artisan key:generate --force
    log "APP_KEY généré"
fi

# 3. Marquer les migrations OAuth comme exécutées si les tables existent
echo ""
echo "3. Vérification des migrations OAuth..."
OAUTH_TABLES_EXIST=$(php artisan tinker --execute="echo Schema::hasTable('oauth_auth_codes') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes" && echo "yes" || echo "no")

if [ "$OAUTH_TABLES_EXIST" = "yes" ]; then
    info "Tables OAuth existent, marquage des migrations..."
    php artisan tinker --execute="
        DB::table('migrations')->insertOrIgnore([
            ['migration' => '2016_06_01_000001_create_oauth_auth_codes_table', 'batch' => 1],
            ['migration' => '2016_06_01_000002_create_oauth_access_tokens_table', 'batch' => 1],
            ['migration' => '2016_06_01_000003_create_oauth_refresh_tokens_table', 'batch' => 1],
            ['migration' => '2016_06_01_000004_create_oauth_clients_table', 'batch' => 1],
            ['migration' => '2024_06_01_000001_create_oauth_device_codes_table', 'batch' => 1]
        ]);
        echo 'done';
    " > /dev/null 2>&1
    log "Migrations OAuth marquées comme exécutées"
fi

# 4. Nettoyer les migrations OAuth en double
echo ""
echo "4. Nettoyage des migrations OAuth en double..."
find database/migrations -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" | xargs rm -f 2>/dev/null || true
log "Migrations OAuth en double supprimées"

# 5. Vérifier et créer les clés Passport
echo ""
echo "5. Vérification des clés Passport..."
if [ ! -f "storage/oauth-private.key" ] || [ ! -f "storage/oauth-public.key" ]; then
    fix "Génération des clés Passport..."
    php artisan passport:keys --force
    log "Clés Passport générées"
else
    log "Clés Passport existent"
fi

# 6. Vérifier et créer le client d'accès personnel
echo ""
echo "6. Vérification du client d'accès personnel Passport..."
# Vérifier via la table oauth_personal_access_clients
if php artisan tinker --execute="echo Schema::hasTable('oauth_personal_access_clients') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
    CLIENT_EXISTS=$(php artisan tinker --execute="echo DB::table('oauth_personal_access_clients')->exists() ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists" && echo "yes" || echo "no")
    
    if [ "$CLIENT_EXISTS" = "no" ]; then
        fix "Création du client d'accès personnel..."
        php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction
        log "Client d'accès personnel créé"
    else
        log "Client d'accès personnel existe"
    fi
else
    # Si la table n'existe pas, créer le client (elle sera créée automatiquement)
    fix "Création du client d'accès personnel..."
    php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction
    log "Client d'accès personnel créé"
fi

# 7. Vérifier les assets frontend
echo ""
echo "7. Vérification des assets frontend..."
if [ -f "public/build/manifest.json" ]; then
    log "Assets frontend compilés (manifest.json existe)"
else
    error "Assets frontend manquants (manifest.json introuvable)"
    warning "Vous devez pull les assets depuis GitHub ou les compiler"
fi

# 8. Vider les caches
echo ""
echo "8. Nettoyage des caches..."
php artisan config:clear > /dev/null 2>&1 || true
php artisan cache:clear > /dev/null 2>&1 || true
php artisan route:clear > /dev/null 2>&1 || true
php artisan view:clear > /dev/null 2>&1 || true
log "Caches vidés"

# 9. Recréer les caches de production
echo ""
echo "9. Recréation des caches de production..."
php artisan config:cache > /dev/null 2>&1 || error "Impossible de créer le cache de config"
php artisan route:cache > /dev/null 2>&1 || error "Impossible de créer le cache de routes"
php artisan view:cache > /dev/null 2>&1 || error "Impossible de créer le cache de vues"
php artisan optimize > /dev/null 2>&1 || error "Impossible d'optimiser"
log "Caches de production recréés"

# 10. Test final
echo ""
echo "10. Test de création de token..."
if php artisan tinker --execute="\$user = \App\Models\User::first(); if(\$user) { try { \$token = \$user->createToken('Test Token'); echo 'SUCCESS'; } catch(\Exception \$e) { echo 'ERROR: ' . \$e->getMessage(); } } else { echo 'NO_USER'; }" 2>/dev/null | grep -q "SUCCESS"; then
    log "Test de création de token réussi"
else
    error "Test de création de token échoué"
    warning "Vérifiez les logs pour plus de détails"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}✅ Correction automatique terminée !${NC}"
echo ""
echo "Prochaines étapes :"
echo "1. Tester la connexion dans le navigateur"
echo "2. Vérifier les logs si nécessaire: tail -f storage/logs/laravel.log"
echo ""

