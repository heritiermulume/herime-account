#!/bin/bash
# Script pour vérifier les sessions par utilisateur
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Vérification des sessions par utilisateur"
echo "=============================================="

# 1. Vérifier les sessions avec user_id
echo ""
log "1. Vérification des sessions avec user_id..."
php artisan tinker --execute='$sessions = \App\Models\UserSession::whereNotNull("user_id")->get(); echo "Sessions avec user_id: " . $sessions->count() . PHP_EOL; foreach($sessions->take(5) as $s) { echo "  Session ID: " . $s->id . " - User ID: " . $s->user_id . " - Device: " . ($s->device_name ?? "N/A") . PHP_EOL; }' 2>&1 | grep -v "Tinker"

# 2. Vérifier les sessions sans user_id
echo ""
log "2. Vérification des sessions sans user_id..."
php artisan tinker --execute='$sessions = \App\Models\UserSession::whereNull("user_id")->count(); echo "Sessions sans user_id: " . $sessions . PHP_EOL;' 2>&1 | grep -v "Tinker"

# 3. Lister tous les utilisateurs et leurs sessions
echo ""
log "3. Liste des utilisateurs et leurs sessions..."
php artisan tinker --execute='$users = \App\Models\User::all(); foreach($users as $user) { $count = $user->sessions()->count(); if ($count > 0) { echo "User ID: " . $user->id . " (" . $user->email . ") - Sessions: " . $count . PHP_EOL; } }' 2>&1 | grep -v "Tinker"

# 4. Vérifier la dernière session créée
echo ""
log "4. Dernière session créée..."
php artisan tinker --execute='$session = \App\Models\UserSession::latest("created_at")->first(); if ($session) { echo "Session ID: " . $session->id . PHP_EOL; echo "User ID: " . ($session->user_id ?? "NULL") . PHP_EOL; echo "Device: " . ($session->device_name ?? "N/A") . PHP_EOL; echo "Created: " . $session->created_at . PHP_EOL; }' 2>&1 | grep -v "Tinker"

echo ""
echo "=============================================="
log "✅ Vérification terminée !"

