#!/bin/bash
# Script pour migrer les avatars du dossier public vers privé
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔒 Migration des avatars vers le stockage privé"
echo "================================================"

# 1. Créer le répertoire privé avatars s'il n'existe pas
echo ""
log "1. Création du répertoire privé..."
mkdir -p storage/app/private/avatars
chmod 775 storage/app/private/avatars
log "✅ Répertoire créé: storage/app/private/avatars"

# 2. Compter les avatars dans le dossier public
echo ""
log "2. Analyse des avatars existants..."
if [ -d "storage/app/public/avatars" ]; then
    AVATAR_COUNT=$(find storage/app/public/avatars -type f 2>/dev/null | wc -l)
    log "✅ $AVATAR_COUNT avatar(s) trouvé(s) dans storage/app/public/avatars"
    
    if [ "$AVATAR_COUNT" -gt 0 ]; then
        # 3. Migrer les avatars
        echo ""
        log "3. Migration des avatars..."
        MIGRATED=0
        FAILED=0
        
        for file in storage/app/public/avatars/*; do
            if [ -f "$file" ]; then
                filename=$(basename "$file")
                if cp "$file" "storage/app/private/avatars/$filename" 2>/dev/null; then
                    MIGRATED=$((MIGRATED + 1))
                    log "   ✅ Migré: $filename"
                else
                    FAILED=$((FAILED + 1))
                    error "   ❌ Échec: $filename"
                fi
            fi
        done
        
        log "✅ Migration terminée: $MIGRATED réussi, $FAILED échec"
        
        # 4. Mettre à jour la base de données (garder seulement le nom du fichier)
        echo ""
        log "4. Mise à jour de la base de données..."
        php artisan tinker --execute="
        \$users = \App\Models\User::whereNotNull('avatar')->get();
        foreach (\$users as \$user) {
            \$oldPath = \$user->avatar;
            // Extraire uniquement le nom du fichier
            \$filename = basename(\$oldPath);
            // Si le chemin contient 'avatars/', extraire juste le nom
            if (strpos(\$oldPath, 'avatars/') !== false) {
                \$filename = basename(\$oldPath);
            }
            \$user->update(['avatar' => \$filename]);
            echo 'Updated user ' . \$user->id . ': ' . \$oldPath . ' -> ' . \$filename . PHP_EOL;
        }
        " 2>&1 | grep -v "Tinker" || true
        
        log "✅ Base de données mise à jour"
        
        # 5. Optionnel: Supprimer les anciens avatars du dossier public
        echo ""
        warning "⚠️ Les anciens avatars sont toujours dans storage/app/public/avatars"
        log "   Vous pouvez les supprimer manuellement après vérification:"
        log "   rm -rf storage/app/public/avatars/*"
    else
        log "✅ Aucun avatar à migrer"
    fi
else
    warning "⚠️ Le répertoire storage/app/public/avatars n'existe pas"
fi

# 6. Vérifier les permissions
echo ""
log "5. Vérification des permissions..."
chmod -R 775 storage/app/private 2>/dev/null || true
log "✅ Permissions corrigées"

echo ""
echo "================================================"
log "✅ Migration terminée !"
echo ""
warning "💡 Important :"
echo "   - Les avatars sont maintenant dans storage/app/private/avatars"
echo "   - L'accès se fait via /api/user/avatar/{userId} (authentifié)"
echo "   - Les anciens avatars dans public/avatars peuvent être supprimés"

