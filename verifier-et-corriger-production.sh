#!/bin/bash

# Script de vérification et correction automatique pour la production
# Usage: ./verifier-et-corriger-production.sh

set -e

echo "🔍 Vérification et correction automatique de la production"
echo "============================================================"
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Variables
ERRORS_FOUND=0
FIXES_APPLIED=0

# Fonction de log
log() {
    echo -e "${GREEN}[✓]${NC} $1"
}

error() {
    echo -e "${RED}[✗]${NC} $1"
    ERRORS_FOUND=$((ERRORS_FOUND + 1))
}

warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

info() {
    echo -e "${BLUE}[i]${NC} $1"
}

fix() {
    echo -e "${YELLOW}[🔧]${NC} $1"
    FIXES_APPLIED=$((FIXES_APPLIED + 1))
}

echo "1. Vérification des permissions..."
if [ -d "storage" ] && [ -d "bootstrap/cache" ]; then
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    chmod -R 755 storage/logs storage/framework 2>/dev/null || true
    log "Permissions vérifiées"
else
    error "Dossiers storage ou bootstrap/cache manquants"
fi

echo ""
echo "2. Vérification de la configuration .env..."
if [ -f ".env" ]; then
    if grep -q "APP_KEY=" .env && ! grep -q "APP_KEY=$" .env; then
        log ".env existe et APP_KEY est défini"
    else
        fix "APP_KEY manquant, génération..."
        php artisan key:generate --force > /dev/null 2>&1 || error "Impossible de générer APP_KEY"
    fi
    
    if grep -q "APP_ENV=production" .env; then
        log "APP_ENV=production configuré"
    else
        warning "APP_ENV n'est pas en production"
    fi
    
    if grep -q "APP_DEBUG=false" .env; then
        log "APP_DEBUG=false configuré"
    else
        warning "APP_DEBUG n'est pas false"
    fi
else
    error "Fichier .env manquant"
fi

echo ""
echo "3. Vérification de la base de données..."
if php artisan migrate:status > /dev/null 2>&1; then
    log "Base de données accessible"
else
    error "Base de données non accessible"
fi

echo ""
echo "4. Vérification des migrations..."
if php artisan migrate:status | grep -q "Ran"; then
    log "Migrations exécutées"
else
    warning "Aucune migration exécutée"
fi

echo ""
echo "5. Vérification des migrations OAuth en double..."
OAUTH_DUPLICATES=$(find database/migrations -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" || true)
if [ -z "$OAUTH_DUPLICATES" ]; then
    log "Aucune migration OAuth en double"
else
    fix "Suppression des migrations OAuth en double..."
    echo "$OAUTH_DUPLICATES" | xargs rm -f 2>/dev/null || true
    log "Migrations OAuth en double supprimées"
fi

echo ""
echo "6. Vérification de Passport (clés)..."
if [ -f "storage/oauth-private.key" ] && [ -f "storage/oauth-public.key" ]; then
    log "Clés Passport existent"
else
    fix "Génération des clés Passport..."
    php artisan passport:keys --force > /dev/null 2>&1 || error "Impossible de générer les clés Passport"
fi

echo ""
echo "7. Vérification du client d'accès personnel Passport..."
# Vérifier via la base de données si le client existe
if php artisan tinker --execute="echo \Laravel\Passport\Client::where('personal_access_client', 1)->exists() ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists"; then
    log "Client d'accès personnel existe"
else
    fix "Création du client d'accès personnel..."
    php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction > /dev/null 2>&1 || error "Impossible de créer le client personnel"
    log "Client d'accès personnel créé"
fi

echo ""
echo "8. Vérification des assets frontend..."
if [ -f "public/build/manifest.json" ]; then
    log "Assets frontend compilés (manifest.json existe)"
else
    error "Assets frontend non compilés (manifest.json manquant)"
    warning "Vous devez compiler les assets: npm run build"
fi

echo ""
echo "9. Nettoyage des caches..."
php artisan config:clear > /dev/null 2>&1 || true
php artisan cache:clear > /dev/null 2>&1 || true
php artisan route:clear > /dev/null 2>&1 || true
php artisan view:clear > /dev/null 2>&1 || true
log "Caches vidés"

echo ""
echo "10. Recréation des caches de production..."
php artisan config:cache > /dev/null 2>&1 || error "Impossible de créer le cache de config"
php artisan route:cache > /dev/null 2>&1 || error "Impossible de créer le cache de routes"
php artisan view:cache > /dev/null 2>&1 || error "Impossible de créer le cache de vues"
php artisan optimize > /dev/null 2>&1 || error "Impossible d'optimiser"
log "Caches de production recréés"

echo ""
echo "11. Vérification finale..."
if php artisan migrate:status > /dev/null 2>&1 && [ -f "storage/oauth-private.key" ]; then
    log "Vérifications finales OK"
else
    error "Problèmes détectés lors de la vérification finale"
fi

echo ""
echo "============================================================"
echo "📊 Résumé"
echo "============================================================"
echo "Erreurs trouvées: $ERRORS_FOUND"
echo "Corrections appliquées: $FIXES_APPLIED"
echo ""

if [ $ERRORS_FOUND -eq 0 ]; then
    echo -e "${GREEN}✅ Tout est prêt pour la production !${NC}"
    echo ""
    echo "Prochaines étapes :"
    echo "1. Tester la connexion dans le navigateur"
    echo "2. Vérifier les logs si nécessaire: tail -f storage/logs/laravel.log"
    exit 0
else
    echo -e "${RED}❌ Des erreurs ont été détectées${NC}"
    echo ""
    echo "Vérifiez les erreurs ci-dessus et corrigez-les manuellement"
    exit 1
fi

