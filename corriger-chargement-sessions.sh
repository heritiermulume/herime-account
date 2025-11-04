#!/bin/bash
# Script pour corriger le problème de chargement des sessions
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔧 Correction du chargement des sessions"
echo "========================================"

# 1. Vérifier les dernières erreurs dans les logs
echo ""
log "1. Vérification des dernières erreurs..."
ERRORS=$(tail -n 100 storage/logs/laravel.log 2>/dev/null | grep -E "ERROR|Exception" | tail -5)
if [ -n "$ERRORS" ]; then
    warning "Erreurs trouvées dans les logs :"
    echo "$ERRORS"
else
    log "Aucune erreur récente dans les logs"
fi

# 2. Tester l'API directement
echo ""
log "2. Test de l'API sessions..."
php artisan tinker --execute='$user = \App\Models\User::find(3); if ($user) { echo "User: " . $user->email . PHP_EOL; $count = $user->sessions()->count(); echo "Sessions: " . $count . PHP_EOL; } else { echo "User non trouve" . PHP_EOL; }' 2>&1 | grep -v "Tinker" || warning "Erreur lors du test"

# 3. Vérifier la syntaxe PHP
echo ""
log "3. Vérification de la syntaxe PHP..."
php -l app/Http/Controllers/Api/SSOController.php 2>&1 | grep -v "No syntax errors" || log "Syntaxe PHP correcte"

# 4. Vérifier les casts du modèle UserSession
echo ""
log "4. Vérification du modèle UserSession..."
php artisan tinker --execute='$session = \App\Models\UserSession::first(); if ($session) { echo "Session ID: " . $session->id . PHP_EOL; echo "last_activity type: " . gettype($session->last_activity) . PHP_EOL; echo "created_at type: " . gettype($session->created_at) . PHP_EOL; } else { echo "Aucune session" . PHP_EOL; }' 2>&1 | grep -v "Tinker" || warning "Erreur lors de la vérification"

# 5. Vider les caches
echo ""
log "5. Vidage des caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# 6. Recréer les caches
echo ""
log "6. Recréation des caches..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo ""
echo "========================================"
log "✅ Corrections appliquées !"
warning "📋 Si le problème persiste :"
echo "   1. Vérifier les logs: tail -f storage/logs/laravel.log"
echo "   2. Vérifier la console navigateur (F12)"
echo "   3. Tester l'API directement avec curl"

