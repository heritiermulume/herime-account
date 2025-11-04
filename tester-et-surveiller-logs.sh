#!/bin/bash
# Script pour tester l'API et surveiller les logs en temps réel
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Test API avec surveillance des logs"
echo "======================================"

# 1. Créer un token
echo ""
log "1. Création d'un token de test..."
TOKEN_OUTPUT=$(php artisan tinker --execute='
$user = \App\Models\User::find(3);
if ($user) {
    $token = $user->createToken("Test Token")->accessToken;
    echo $token . PHP_EOL;
} else {
    echo "USER_NOT_FOUND" . PHP_EOL;
}
' 2>&1 | grep -v "Tinker" | tail -1)

if [ "$TOKEN_OUTPUT" = "USER_NOT_FOUND" ]; then
    error "Utilisateur ID 3 non trouvé"
    exit 1
fi

TOKEN="$TOKEN_OUTPUT"
log "Token créé"

# 2. Vider le fichier de log temporairement pour ne voir que les nouveaux logs
echo ""
log "2. Nettoyage des anciens logs de session..."
LOG_LINES=$(wc -l < storage/logs/laravel.log)

# 3. Tester l'API
echo ""
log "3. Test de l'API /api/sso/sessions..."
DOMAIN=$(grep "^APP_URL=" .env 2>/dev/null | cut -d'=' -f2 | sed 's|https\?://||' | sed 's|/$||' || echo "localhost")
if [ "$DOMAIN" = "localhost" ]; then
    API_URL="http://localhost/api/sso/sessions"
else
    API_URL="https://$DOMAIN/api/sso/sessions"
fi

# Capturer les logs après le test
RESPONSE=$(curl -s -w "\n%{http_code}" -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" "$API_URL" 2>&1 || echo "ERROR")

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | head -n -1)

echo ""
log "4. Résultat de l'API :"
echo "   Status: $HTTP_CODE"
if [ "$HTTP_CODE" = "200" ]; then
    log "✅ API fonctionne !"
    echo "   Réponse (premiers 500 caractères):"
    echo "$BODY" | head -c 500
    echo ""
else
    error "❌ Erreur API (Status: $HTTP_CODE)"
    echo "   Réponse:"
    echo "$BODY"
fi

# 4. Voir les nouveaux logs
echo ""
log "5. Nouveaux logs générés :"
NEW_LOG_LINES=$(wc -l < storage/logs/laravel.log)
if [ "$NEW_LOG_LINES" -gt "$LOG_LINES" ]; then
    tail -n $((NEW_LOG_LINES - LOG_LINES)) storage/logs/laravel.log | grep -E "ERROR|Exception|Sessions|session" || warning "Aucun log de session ou d'erreur dans les nouveaux logs"
else
    warning "Aucun nouveau log généré"
fi

# 5. Nettoyer le token
echo ""
log "6. Nettoyage du token..."
php artisan tinker --execute="
\$user = \App\Models\User::find(3);
if (\$user) {
    \$user->tokens()->where('name', 'Test Token')->delete();
    echo 'Token supprime' . PHP_EOL;
}
" 2>&1 | grep -v "Tinker" > /dev/null || true

echo ""
echo "======================================"
log "✅ Test terminé !"

