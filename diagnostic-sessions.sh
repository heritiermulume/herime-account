#!/bin/bash
# Script pour diagnostiquer les problèmes de sessions
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Diagnostic des sessions"
echo "=========================="

# 1. Vérifier que la table existe
echo ""
log "1. Vérification de la table user_sessions..."
php artisan tinker --execute="
try {
    \$count = \App\Models\UserSession::count();
    echo 'Nombre de sessions: ' . \$count . PHP_EOL;
    log 'Table user_sessions existe et contient des données';
} catch (\Exception \$e) {
    error 'Erreur: ' . \$e->getMessage();
}
" 2>&1 | grep -v "Tinker" || warning "Erreur lors de la vérification"

# 2. Vérifier les relations
echo ""
log "2. Vérification des relations..."
php artisan tinker --execute="
try {
    \$user = \App\Models\User::first();
    if (\$user) {
        \$sessions = \$user->sessions()->count();
        echo 'Sessions pour l\'utilisateur: ' . \$sessions . PHP_EOL;
        log 'Relation sessions() fonctionne';
    } else {
        warning 'Aucun utilisateur trouvé';
    }
} catch (\Exception \$e) {
    error 'Erreur: ' . \$e->getMessage();
}
" 2>&1 | grep -v "Tinker" || warning "Erreur lors de la vérification"

# 3. Vérifier la structure de la table
echo ""
log "3. Vérification de la structure de la table..."
php artisan tinker --execute="
try {
    \$columns = \Illuminate\Support\Facades\Schema::getColumnListing('user_sessions');
    echo 'Colonnes: ' . implode(', ', \$columns) . PHP_EOL;
    log 'Structure de la table vérifiée';
} catch (\Exception \$e) {
    error 'Erreur: ' . \$e->getMessage();
}
" 2>&1 | grep -v "Tinker" || warning "Erreur lors de la vérification"

echo ""
echo "=========================="
log "✅ Diagnostic terminé !"

