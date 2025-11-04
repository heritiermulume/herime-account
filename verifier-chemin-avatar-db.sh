#!/bin/bash
# Script pour vérifier le chemin exact de l'avatar dans la DB
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Vérification du chemin avatar dans la DB"
echo "==========================================="

# 1. Récupérer le chemin depuis la DB avec une commande plus simple
echo ""
log "1. Récupération du chemin depuis la DB..."
AVATAR_DB=$(php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
\$user = \App\Models\User::whereNotNull('avatar')->first();
if (\$user) {
    echo \$user->avatar . PHP_EOL;
    echo 'USER_ID:' . \$user->id . PHP_EOL;
} else {
    echo 'NO_AVATAR' . PHP_EOL;
}
" 2>/dev/null | grep -v "^$" | head -2)

if echo "$AVATAR_DB" | grep -q "NO_AVATAR"; then
    error "❌ Aucun utilisateur avec avatar trouvé dans la DB"
    exit 1
fi

AVATAR_PATH=$(echo "$AVATAR_DB" | head -1 | tr -d '\r\n')
USER_ID=$(echo "$AVATAR_DB" | grep "USER_ID:" | cut -d: -f2 | tr -d '\r\n')

log "✅ Avatar trouvé dans DB pour user ID $USER_ID"
log "   Chemin dans DB: '$AVATAR_PATH'"

# 2. Vérifier les fichiers réels
echo ""
log "2. Fichiers réels dans storage/app/public/avatars/..."
if [ -d "storage/app/public/avatars" ]; then
    AVATAR_FILES=$(ls -1 storage/app/public/avatars/ 2>/dev/null | head -5)
    if [ -n "$AVATAR_FILES" ]; then
        log "✅ Fichiers trouvés:"
        echo "$AVATAR_FILES" | while read file; do
            echo "   - $file"
        done
    else
        warning "⚠️ Aucun fichier dans storage/app/public/avatars/"
    fi
else
    error "❌ Répertoire storage/app/public/avatars/ n'existe pas"
fi

# 3. Vérifier si le chemin correspond
echo ""
log "3. Vérification de la correspondance..."
AVATAR_FILENAME=$(basename "$AVATAR_PATH" 2>/dev/null || echo "")
if [ -n "$AVATAR_FILENAME" ]; then
    log "   Nom de fichier extrait: '$AVATAR_FILENAME'"
    if [ -f "storage/app/public/avatars/$AVATAR_FILENAME" ]; then
        log "✅ Fichier trouvé: storage/app/public/avatars/$AVATAR_FILENAME"
    else
        error "❌ Fichier non trouvé: storage/app/public/avatars/$AVATAR_FILENAME"
        log "   Recherche de fichiers similaires..."
        find storage/app/public -name "*$AVATAR_FILENAME*" 2>/dev/null || echo "   Aucun fichier similaire trouvé"
    fi
fi

# 4. Vérifier le chemin complet
echo ""
log "4. Vérification du chemin complet..."
if [ -f "storage/app/public/$AVATAR_PATH" ]; then
    log "✅ Fichier existe avec le chemin complet: storage/app/public/$AVATAR_PATH"
    ls -lh "storage/app/public/$AVATAR_PATH"
else
    error "❌ Fichier n'existe pas: storage/app/public/$AVATAR_PATH"
fi

# 5. Générer l'URL
echo ""
log "5. URL générée..."
AVATAR_URL=$(php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
\$user = \App\Models\User::find($USER_ID);
if (\$user) {
    echo \$user->avatar_url . PHP_EOL;
}
" 2>/dev/null | grep -v "^$" | head -1)

log "✅ URL générée: $AVATAR_URL"

# 6. Test de l'URL
echo ""
log "6. Test de l'accessibilité..."
if [[ "$AVATAR_URL" == http* ]]; then
    log "✅ URL complète: $AVATAR_URL"
    log "   Vous pouvez tester cette URL dans votre navigateur"
else
    warning "⚠️ URL relative ou vide: '$AVATAR_URL'"
fi

echo ""
echo "==========================================="
log "✅ Vérification terminée !"

