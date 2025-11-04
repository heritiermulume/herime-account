#!/bin/bash
# Script pour vérifier que toutes les données sont sauvegardées correctement
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Vérification complète de la sauvegarde des données"
echo "======================================================"

# Test 1: Profil complet
echo ""
log "1. Test profil complet (name, phone, company, position)..."
php artisan tinker --execute="
\$user = \App\Models\User::first();
\$user->update([
    'name' => 'Test Complete',
    'phone' => '+33612345678',
    'company' => 'Herime',
    'position' => 'Developer'
]);
\$user->refresh();
\$data = \$user->only(['name', 'phone', 'company', 'position']);
if (\$data['name'] === 'Test Complete' && \$data['phone'] === '+33612345678' && \$data['company'] === 'Herime' && \$data['position'] === 'Developer') {
    echo 'OK';
} else {
    echo 'FAIL';
}
" 2>/dev/null | grep -q "OK" && log "Profil complet OK" || error "Profil complet échoué"

# Test 2: Préférences
echo ""
log "2. Test préférences (theme, language, notifications)..."
php artisan tinker --execute="
\$user = \App\Models\User::first();
\$prefs = [
    'theme' => 'dark',
    'language' => 'fr',
    'notifications' => ['email' => true, 'sms' => false, 'push' => true]
];
\$user->update(['preferences' => \$prefs]);
\$user->refresh();
if (\$user->preferences['theme'] === 'dark' && \$user->preferences['language'] === 'fr') {
    echo 'OK';
} else {
    echo 'FAIL';
}
" 2>/dev/null | grep -q "OK" && log "Préférences OK" || error "Préférences échoué"

# Test 3: Dernières connexions
echo ""
log "3. Test dernières connexions (last_login_at, last_login_ip)..."
php artisan tinker --execute="
\$user = \App\Models\User::first();
\$user->update([
    'last_login_at' => now(),
    'last_login_ip' => '192.168.1.1',
    'last_login_user_agent' => 'Mozilla/5.0 Test'
]);
\$user->refresh();
if (\$user->last_login_at && \$user->last_login_ip === '192.168.1.1') {
    echo 'OK';
} else {
    echo 'FAIL';
}
" 2>/dev/null | grep -q "OK" && log "Dernières connexions OK" || error "Dernières connexions échoué"

# Test 4: Sessions
echo ""
log "4. Test sessions (création et récupération)..."
SESSION_COUNT=$(php artisan tinker --execute="\$user = \App\Models\User::first(); echo \$user->sessions()->count();" 2>/dev/null)
if [ "$SESSION_COUNT" -gt 0 ]; then
    log "Sessions OK ($SESSION_COUNT sessions trouvées)"
else
    warning "Aucune session trouvée (normal si pas de login récent)"
fi

# Test 5: Avatar path
echo ""
log "5. Test avatar (champ présent dans DB)..."
AVATAR_EXISTS=$(php artisan tinker --execute="echo Schema::hasColumn('users', 'avatar') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes" && echo "yes" || echo "no")
if [ "$AVATAR_EXISTS" = "yes" ]; then
    log "Champ avatar présent dans DB"
else
    error "Champ avatar manquant"
fi

# Test 6: 2FA
echo ""
log "6. Test 2FA (champs présents)..."
TWO_FA_EXISTS=$(php artisan tinker --execute="echo Schema::hasColumn('users', 'two_factor_secret') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes" && echo "yes" || echo "no")
if [ "$TWO_FA_EXISTS" = "yes" ]; then
    log "Champs 2FA présents"
else
    error "Champs 2FA manquants"
fi

echo ""
echo "======================================================"
log "✅ Vérification terminée !"

