#!/bin/bash
# Script pour tester directement l'API sessions
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Test de l'API sessions"
echo "========================"

# 1. Créer un token de test pour un utilisateur
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
log "Token créé: ${TOKEN:0:20}..."

# 2. Tester l'endpoint API
echo ""
log "2. Test de l'endpoint /api/sso/sessions..."
# Utiliser le domaine de production si disponible, sinon localhost
DOMAIN=$(grep "^APP_URL=" .env 2>/dev/null | cut -d'=' -f2 | sed 's|https\?://||' | sed 's|/$||' || echo "localhost")
if [ "$DOMAIN" = "localhost" ]; then
    API_URL="http://localhost/api/sso/sessions"
else
    API_URL="https://$DOMAIN/api/sso/sessions"
fi
log "URL testée: $API_URL"
RESPONSE=$(curl -s -w "\n%{http_code}" -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" "$API_URL" 2>&1 || echo "ERROR")

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | head -n -1)

if [ "$HTTP_CODE" = "200" ]; then
    log "Status HTTP: $HTTP_CODE"
    echo "Réponse:"
    echo "$BODY" | head -20
    echo ""
    
    # Vérifier le contenu de la réponse
    if echo "$BODY" | grep -q '"success":true'; then
        log "Réponse JSON valide avec success=true"
        SESSIONS_COUNT=$(echo "$BODY" | grep -o '"sessions":\[.*\]' | grep -o '{' | wc -l || echo "0")
        echo "Nombre de sessions dans la réponse: $SESSIONS_COUNT"
    else
        error "Réponse JSON invalide ou success=false"
        echo "$BODY"
    fi
else
    error "Status HTTP: $HTTP_CODE"
    echo "Réponse:"
    echo "$BODY"
fi

# 3. Nettoyer le token
echo ""
log "3. Nettoyage du token de test..."
php artisan tinker --execute="
\$user = \App\Models\User::find(3);
if (\$user) {
    \$user->tokens()->where('name', 'Test Token')->delete();
    echo 'Token supprime' . PHP_EOL;
}
" 2>&1 | grep -v "Tinker" > /dev/null || true

echo ""
echo "========================"
log "✅ Test terminé !"

