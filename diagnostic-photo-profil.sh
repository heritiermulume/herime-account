#!/bin/bash
# Script pour diagnostiquer pourquoi la photo de profil ne s'affiche pas
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Diagnostic de la photo de profil"
echo "===================================="

# 1. Vérifier le lien symbolique
echo ""
log "1. Vérification du lien symbolique public/storage..."
if [ -L "public/storage" ]; then
    TARGET=$(readlink -f public/storage)
    if [ -d "$TARGET" ]; then
        log "✅ Lien symbolique existe et pointe vers: $TARGET"
    else
        error "❌ Lien symbolique cassé (pointe vers: $TARGET)"
    fi
elif [ -d "public/storage" ]; then
    warning "⚠️ public/storage est un répertoire, pas un lien symbolique"
else
    error "❌ Le lien symbolique public/storage n'existe pas"
    log "   Création du lien..."
    php artisan storage:link
fi

# 2. Vérifier les avatars dans la DB
echo ""
log "2. Vérification des avatars dans la base de données..."
AVATAR_PATH=$(php artisan tinker --execute='
$user = \App\Models\User::whereNotNull("avatar")->first();
if ($user) {
    echo $user->avatar . PHP_EOL;
    echo "USER_ID:" . $user->id . PHP_EOL;
} else {
    echo "NO_AVATAR" . PHP_EOL;
}
' 2>&1 | grep -v "Tinker" | head -2)

if [ "$AVATAR_PATH" = "NO_AVATAR" ]; then
    warning "⚠️ Aucun utilisateur avec avatar trouvé dans la DB"
else
    AVATAR_DB=$(echo "$AVATAR_PATH" | head -1)
    USER_ID=$(echo "$AVATAR_PATH" | grep "USER_ID:" | cut -d: -f2)
    log "✅ Avatar trouvé dans DB pour user ID $USER_ID: $AVATAR_DB"
    
    # 3. Vérifier si le fichier existe
    echo ""
    log "3. Vérification de l'existence du fichier..."
    FULL_PATH="storage/app/public/$AVATAR_DB"
    if [ -f "$FULL_PATH" ]; then
        log "✅ Fichier existe: $FULL_PATH"
        ls -lh "$FULL_PATH"
    else
        error "❌ Fichier n'existe pas: $FULL_PATH"
        log "   Recherche dans storage/app/public..."
        find storage/app/public -name "$(basename $AVATAR_DB)" 2>/dev/null || echo "   Fichier non trouvé"
    fi
    
    # 4. Vérifier l'URL générée
    echo ""
    log "4. Vérification de l'URL générée..."
    AVATAR_URL=$(php artisan tinker --execute="
    \$user = \App\Models\User::find($USER_ID);
    if (\$user) {
        echo \$user->avatar_url . PHP_EOL;
    }
    " 2>&1 | grep -v "Tinker" | head -1)
    log "✅ URL générée: $AVATAR_URL"
    
    # 5. Vérifier l'accès via le lien symbolique
    echo ""
    log "5. Vérification de l'accès via public/storage..."
    PUBLIC_PATH="public/storage/$AVATAR_DB"
    if [ -f "$PUBLIC_PATH" ]; then
        log "✅ Fichier accessible via public/storage: $PUBLIC_PATH"
    else
        error "❌ Fichier non accessible via public/storage: $PUBLIC_PATH"
        warning "   Le lien symbolique ne fonctionne pas correctement"
    fi
fi

# 6. Vérifier les permissions
echo ""
log "6. Vérification des permissions..."
if [ -d "storage/app/public" ]; then
    PERM=$(stat -c "%a" storage/app/public 2>/dev/null || stat -f "%OLp" storage/app/public 2>/dev/null || echo "unknown")
    log "✅ Permissions storage/app/public: $PERM"
    if [ "$PERM" != "775" ] && [ "$PERM" != "755" ]; then
        warning "⚠️ Permissions recommandées: 775 ou 755"
    fi
else
    error "❌ Répertoire storage/app/public n'existe pas"
fi

# 7. Vérifier APP_URL
echo ""
log "7. Vérification de APP_URL..."
APP_URL=$(grep "^APP_URL=" .env 2>/dev/null | cut -d'=' -f2 || echo "non défini")
log "APP_URL: $APP_URL"

# 8. Tester l'URL complète
if [ -n "$AVATAR_URL" ] && [ "$AVATAR_URL" != "NO_AVATAR" ]; then
    echo ""
    log "8. Test de l'URL complète..."
    if [[ "$AVATAR_URL" == http* ]]; then
        log "✅ URL complète: $AVATAR_URL"
    else
        warning "⚠️ URL relative: $AVATAR_URL"
        if [ -n "$APP_URL" ] && [ "$APP_URL" != "non défini" ]; then
            FULL_URL="${APP_URL%/}/$AVATAR_URL"
            log "   URL complète serait: $FULL_URL"
        fi
    fi
fi

echo ""
echo "===================================="
log "✅ Diagnostic terminé !"
echo ""
warning "💡 Solutions possibles :"
echo "   1. Si le lien symbolique n'existe pas: php artisan storage:link"
echo "   2. Si le fichier n'existe pas: vérifier le chemin dans la DB"
echo "   3. Si les permissions sont incorrectes: chmod -R 775 storage/app/public"
echo "   4. Si APP_URL est incorrect: corriger dans .env"

