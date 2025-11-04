#!/bin/bash
# Script pour tester que toutes les données sont sauvegardées
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🧪 Test complet de sauvegarde des données"
echo "=========================================="

# 1. Test mise à jour profil avec tous les champs
log "1. Test mise à jour profil..."
USER_ID=$(php artisan tinker --execute="echo \App\Models\User::first()->id;" 2>/dev/null)
php artisan tinker --execute="
\$user = \App\Models\User::find($USER_ID);
\$user->update([
    'name' => 'Test User Complete',
    'phone' => '+33612345678',
    'company' => 'Herime Corp',
    'position' => 'Senior Developer'
]);
\$user->refresh();
echo 'Profil: ' . json_encode(\$user->only(['name', 'phone', 'company', 'position']), JSON_PRETTY_PRINT) . PHP_EOL;
" 2>&1 | grep -q "Test User Complete" && log "Profil OK" || error "Profil échoué"

# 2. Test préférences
log "2. Test préférences..."
php artisan tinker --execute="
\$user = \App\Models\User::first();
\$user->update([
    'preferences' => [
        'theme' => 'dark',
        'language' => 'fr',
        'notifications' => [
            'email' => true,
            'sms' => false,
            'push' => true
        ]
    ]
]);
\$user->refresh();
echo 'Preferences: ' . json_encode(\$user->preferences, JSON_PRETTY_PRINT) . PHP_EOL;
" 2>&1 | grep -q "dark" && log "Préférences OK" || error "Préférences échoué"

# 3. Test sessions
log "3. Test sessions..."
php artisan tinker --execute="
\$user = \App\Models\User::first();
\$session = \App\Models\UserSession::create([
    'user_id' => \$user->id,
    'session_id' => \Illuminate\Support\Str::random(40),
    'ip_address' => '192.168.1.100',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    'device_name' => 'Windows PC',
    'platform' => 'Windows',
    'browser' => 'Chrome',
    'is_current' => false,
    'last_activity' => now()
]);
echo 'Session créée: ' . \$session->id . PHP_EOL;
" 2>&1 | grep -q "Session créée" && log "Sessions OK" || error "Sessions échoué"

# 4. Test dernières connexions
log "4. Test dernières connexions..."
php artisan tinker --execute="
\$user = \App\Models\User::first();
\$user->update([
    'last_login_at' => now(),
    'last_login_ip' => '192.168.1.1',
    'last_login_user_agent' => 'Mozilla/5.0 Test'
]);
\$user->refresh();
echo 'Last login: ' . \$user->last_login_at . ' from ' . \$user->last_login_ip . PHP_EOL;
" 2>&1 | grep -q "Last login" && log "Dernières connexions OK" || error "Dernières connexions échoué"

# 5. Vérifier champs dans DB
log "5. Vérification champs dans DB..."
COLUMNS=$(php artisan tinker --execute="echo implode(', ', Schema::getColumnListing('users'));" 2>/dev/null)
REQUIRED_FIELDS=("name" "email" "phone" "avatar" "company" "position" "preferences" "last_login_at" "last_login_ip" "two_factor_secret")
for field in "${REQUIRED_FIELDS[@]}"; do
    if echo "$COLUMNS" | grep -q "$field"; then
        log "Champ $field présent"
    else
        error "Champ $field manquant"
    fi
done

echo ""
echo "=========================================="
log "✅ Tests terminés !"

