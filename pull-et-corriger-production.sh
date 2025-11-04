#!/bin/bash

# Script pour pull les modifications et corriger automatiquement la production
# Usage: ./pull-et-corriger-production.sh

set -e

echo "📥 Pull et correction automatique de la production"
echo "=================================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[✓]${NC} $1"
}

error() {
    echo -e "${RED}[✗]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

info() {
    echo -e "${BLUE}[i]${NC} $1"
}

# 1. Sauvegarder les assets locaux si nécessaire
echo "1. Sauvegarde des assets locaux..."
if [ -f "public/build/manifest.json" ]; then
    BACKUP_DIR="backup_build_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    cp -r public/build "$BACKUP_DIR/" 2>/dev/null || true
    log "Assets sauvegardés dans $BACKUP_DIR/"
else
    info "Aucun asset local à sauvegarder"
fi

# 2. Stash les modifications locales (sauf les assets qui seront écrasés)
echo ""
echo "2. Sauvegarde des modifications locales..."
if git status --porcelain | grep -q .; then
    # Sauvegarder les fichiers .gitignore modifiés
    if [ -f "bootstrap/cache/.gitignore" ]; then
        cp bootstrap/cache/.gitignore bootstrap/cache/.gitignore.local 2>/dev/null || true
    fi
    
    # Stash les modifications
    git stash push -m "Sauvegarde avant pull $(date +%Y%m%d_%H%M%S)" || warning "Aucune modification à sauvegarder"
    log "Modifications locales sauvegardées"
else
    info "Aucune modification locale"
fi

# 3. Reset les fichiers qui posent problème (pour forcer le pull)
echo ""
echo "3. Réinitialisation des fichiers conflictuels..."
git checkout HEAD -- bootstrap/cache/.gitignore 2>/dev/null || true
git checkout HEAD -- storage/app/.gitignore 2>/dev/null || true
git checkout HEAD -- storage/app/private/.gitignore 2>/dev/null || true
git checkout HEAD -- storage/app/public/.gitignore 2>/dev/null || true
git checkout HEAD -- storage/framework/.gitignore 2>/dev/null || true
git checkout HEAD -- storage/framework/cache/.gitignore 2>/dev/null || true
git checkout HEAD -- storage/framework/cache/data/.gitignore 2>/dev/null || true
git checkout HEAD -- storage/framework/sessions/.gitignore 2>/dev/null || true
git checkout HEAD -- storage/framework/testing/.gitignore 2>/dev/null || true
git checkout HEAD -- storage/framework/views/.gitignore 2>/dev/null || true
git checkout HEAD -- storage/logs/.gitignore 2>/dev/null || true
log "Fichiers .gitignore réinitialisés"

# 3.1. Gérer les assets dans public/build/ (souvent en conflit)
echo ""
echo "3.1. Gestion des assets frontend..."
if git status --porcelain | grep -q "public/build/"; then
    info "Assets locaux détectés, sauvegarde..."
    # Sauvegarder les assets modifiés localement
    if [ -f "public/build/manifest.json" ]; then
        cp public/build/manifest.json "$BACKUP_DIR/manifest.json.local" 2>/dev/null || true
    fi
    if [ -d "public/build/assets" ]; then
        cp -r public/build/assets "$BACKUP_DIR/assets.local" 2>/dev/null || true
    fi
    # Supprimer les fichiers modifiés pour permettre le pull
    git checkout HEAD -- public/build/ 2>/dev/null || true
    # Si checkout ne fonctionne pas, supprimer les fichiers modifiés
    git rm --cached public/build/assets/*.css public/build/manifest.json 2>/dev/null || true
    log "Assets préparés pour le pull"
fi

# 4. Pull les modifications
echo ""
echo "4. Pull des modifications depuis GitHub..."
if git pull origin main; then
    log "Pull réussi"
else
    error "Échec du pull"
    exit 1
fi

# 5. Rétablir les permissions correctes sur les .gitignore
echo ""
echo "5. Correction des permissions..."
chmod 644 bootstrap/cache/.gitignore 2>/dev/null || true
chmod 644 storage/app/.gitignore 2>/dev/null || true
chmod 644 storage/app/private/.gitignore 2>/dev/null || true
chmod 644 storage/app/public/.gitignore 2>/dev/null || true
chmod 644 storage/framework/.gitignore 2>/dev/null || true
chmod 644 storage/framework/cache/.gitignore 2>/dev/null || true
chmod 644 storage/framework/cache/data/.gitignore 2>/dev/null || true
chmod 644 storage/framework/sessions/.gitignore 2>/dev/null || true
chmod 644 storage/framework/testing/.gitignore 2>/dev/null || true
chmod 644 storage/framework/views/.gitignore 2>/dev/null || true
chmod 644 storage/logs/.gitignore 2>/dev/null || true
log "Permissions corrigées"

# 6. Vérifier que les assets sont présents
echo ""
echo "6. Vérification des assets frontend..."
if [ -f "public/build/manifest.json" ]; then
    log "Assets frontend présents (manifest.json existe)"
else
    warning "Assets frontend manquants (manifest.json introuvable)"
    if [ -d "$BACKUP_DIR/build" ]; then
        info "Restauration des assets depuis la sauvegarde..."
        cp -r "$BACKUP_DIR/build" public/ 2>/dev/null || true
        log "Assets restaurés"
    fi
fi

# 7. Rendre les scripts exécutables
echo ""
echo "7. Configuration des scripts..."
chmod +x corriger-production-automatique.sh 2>/dev/null || true
chmod +x verifier-et-corriger-production.sh 2>/dev/null || true
chmod +x deploy-o2switch.sh 2>/dev/null || true
log "Scripts rendus exécutables"

# 8. Exécuter le script de correction automatique
echo ""
echo "8. Exécution de la correction automatique..."
if [ -f "corriger-production-automatique.sh" ]; then
    ./corriger-production-automatique.sh
else
    error "Script de correction automatique introuvable"
    exit 1
fi

echo ""
echo "=================================================="
echo -e "${GREEN}✅ Pull et correction terminés avec succès !${NC}"
echo ""
echo "Si vous aviez des modifications locales importantes, elles sont dans :"
echo "  git stash list"
echo "  git stash show -p stash@{0}  # Pour voir les modifications"
echo "  git stash pop  # Pour les restaurer (si nécessaire)"
echo ""

