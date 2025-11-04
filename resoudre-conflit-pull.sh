#!/bin/bash

# Script pour résoudre les conflits Git et faire le pull
# Usage: ./resoudre-conflit-pull.sh

set -e

echo "🔧 Résolution des conflits Git et pull"
echo "======================================="
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

# 1. Sauvegarder les fichiers locaux si nécessaire
echo "1. Sauvegarde des fichiers locaux..."
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

if [ -f "public/build/manifest.json" ]; then
    cp public/build/manifest.json "$BACKUP_DIR/" 2>/dev/null || true
    info "manifest.json sauvegardé"
fi

if [ -f "public/build/assets/app-C8OVHuWe.css" ]; then
    mkdir -p "$BACKUP_DIR/assets"
    cp public/build/assets/app-C8OVHuWe.css "$BACKUP_DIR/assets/" 2>/dev/null || true
    info "app-C8OVHuWe.css sauvegardé"
fi

log "Sauvegarde créée dans $BACKUP_DIR/"

# 2. Supprimer les fichiers conflictuels du cache Git
echo ""
echo "2. Suppression des fichiers du cache Git..."
git rm --cached public/build/assets/app-C8OVHuWe.css public/build/manifest.json 2>/dev/null || true
log "Fichiers supprimés du cache Git"

# 3. Supprimer les fichiers localement
echo ""
echo "3. Suppression des fichiers locaux..."
rm -f public/build/manifest.json 2>/dev/null || true
rm -f public/build/assets/app-C8OVHuWe.css 2>/dev/null || true
log "Fichiers supprimés localement"

# 4. Nettoyer le cache Git
echo ""
echo "4. Nettoyage du cache Git..."
git clean -fd public/build/ 2>/dev/null || true
log "Cache Git nettoyé"

# 5. Pull les modifications
echo ""
echo "5. Pull des modifications depuis GitHub..."
if git pull origin main; then
    log "Pull réussi"
else
    error "Échec du pull"
    warning "Les fichiers sont sauvegardés dans $BACKUP_DIR/"
    exit 1
fi

# 6. Vérifier que les assets sont présents
echo ""
echo "6. Vérification des assets..."
if [ -f "public/build/manifest.json" ]; then
    log "Assets présents (manifest.json existe)"
else
    warning "Assets manquants, restauration depuis la sauvegarde..."
    if [ -f "$BACKUP_DIR/manifest.json" ]; then
        cp "$BACKUP_DIR/manifest.json" public/build/ 2>/dev/null || true
    fi
    if [ -f "$BACKUP_DIR/assets/app-C8OVHuWe.css" ]; then
        mkdir -p public/build/assets
        cp "$BACKUP_DIR/assets/app-C8OVHuWe.css" public/build/assets/ 2>/dev/null || true
    fi
fi

# 7. Rendre les scripts exécutables
echo ""
echo "7. Configuration des scripts..."
chmod +x pull-et-corriger-production.sh 2>/dev/null || true
chmod +x corriger-production-automatique.sh 2>/dev/null || true
chmod +x verifier-et-corriger-production.sh 2>/dev/null || true
log "Scripts rendus exécutables"

echo ""
echo "======================================="
echo -e "${GREEN}✅ Conflits résolus et pull terminé !${NC}"
echo ""
echo "Les fichiers sauvegardés sont dans : $BACKUP_DIR/"
echo "Vous pouvez maintenant exécuter :"
echo "  ./corriger-production-automatique.sh"
echo ""

