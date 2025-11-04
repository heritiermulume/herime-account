#!/bin/bash
# Script de diagnostic complet pour les problèmes en production
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Diagnostic complet de production"
echo "===================================="

# 1. Vérifier la connexion à la base de données
echo ""
log "1. Vérification de la connexion DB..."
php artisan tinker --execute='try { DB::connection()->getPdo(); echo "Connexion DB OK" . PHP_EOL; } catch (\Exception $e) { echo "Erreur DB: " . $e->getMessage() . PHP_EOL; }' 2>&1 | grep -v "Tinker"

# 2. Vérifier que les tables existent
echo ""
log "2. Vérification des tables..."
php artisan tinker --execute='$tables = ["users", "user_sessions"]; foreach($tables as $table) { $exists = Schema::hasTable($table); echo $table . ": " . ($exists ? "OK" : "MANQUANT") . PHP_EOL; }' 2>&1 | grep -v "Tinker"

# 3. Vérifier les sessions dans la DB
echo ""
log "3. Vérification des sessions en DB..."
php artisan tinker --execute='$count = \App\Models\UserSession::count(); echo "Sessions totales: " . $count . PHP_EOL; $withUser = \App\Models\UserSession::whereNotNull("user_id")->count(); echo "Sessions avec user_id: " . $withUser . PHP_EOL;' 2>&1 | grep -v "Tinker"

# 4. Tester la récupération des sessions pour un utilisateur
echo ""
log "4. Test récupération sessions utilisateur..."
php artisan tinker --execute='$user = \App\Models\User::find(3); if ($user) { $sessions = $user->sessions()->get(); echo "User ID 3 sessions: " . $sessions->count() . PHP_EOL; if ($sessions->count() > 0) { $first = $sessions->first(); echo "Premiere session ID: " . $first->id . PHP_EOL; echo "Device: " . ($first->device_name ?? "N/A") . PHP_EOL; } } else { echo "User ID 3 non trouve" . PHP_EOL; }' 2>&1 | grep -v "Tinker"

# 5. Tester la méthode getSessions du contrôleur
echo ""
log "5. Test méthode getSessions..."
php artisan tinker --execute='$user = \App\Models\User::find(3); if ($user) { $sessionsRaw = $user->sessions()->orderBy("last_activity", "desc")->get(); $sessions = collect([]); foreach($sessionsRaw as $session) { try { $lastActivity = null; if ($session->last_activity && is_object($session->last_activity) && method_exists($session->last_activity, "format")) { $lastActivity = $session->last_activity->format("c"); } $sessions->push(["id" => $session->id, "device_name" => $session->device_name ?? "Unknown", "last_activity" => $lastActivity]); } catch (\Exception $e) { echo "Erreur mapping: " . $e->getMessage() . PHP_EOL; } } echo "Sessions mappees: " . $sessions->count() . PHP_EOL; }' 2>&1 | grep -v "Tinker"

# 6. Vérifier les permissions
echo ""
log "6. Vérification des permissions..."
ls -la storage/logs/ 2>/dev/null | head -3 || warning "Impossible de vérifier les permissions"

# 7. Vérifier les logs récents
echo ""
log "7. Dernières erreurs dans les logs..."
tail -n 50 storage/logs/laravel.log 2>/dev/null | grep -E "ERROR|Exception|Fatal" | tail -3 || warning "Aucune erreur récente trouvée"

# 8. Vérifier la configuration
echo ""
log "8. Vérification de la configuration..."
php artisan tinker --execute='echo "APP_ENV: " . config("app.env") . PHP_EOL; echo "APP_DEBUG: " . (config("app.debug") ? "true" : "false") . PHP_EOL;' 2>&1 | grep -v "Tinker"

# 9. Vérifier les routes API
echo ""
log "9. Vérification des routes API..."
php artisan route:list --path=api/sso 2>&1 | head -5 || warning "Impossible de lister les routes"

# 10. Vider les caches
echo ""
log "10. Vidage des caches..."
php artisan config:clear 2>/dev/null && log "Config cache vidé" || warning "Erreur vidage config"
php artisan cache:clear 2>/dev/null && log "Cache vidé" || warning "Erreur vidage cache"
php artisan route:clear 2>/dev/null && log "Route cache vidé" || warning "Erreur vidage route"

echo ""
echo "===================================="
log "✅ Diagnostic terminé !"

