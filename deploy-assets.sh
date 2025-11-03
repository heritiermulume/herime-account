#!/bin/bash

# Script de déploiement des assets pour O2Switch
# Usage: ./deploy-assets.sh [user@host] [path]
# Exemple: ./deploy-assets.sh user@o2switch.fr www/votre-domaine.com

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration par défaut (à modifier selon vos besoins)
DEFAULT_HOST="${1:-votre-identifiant@o2switch.fr}"
DEFAULT_PATH="${2:-www/votre-domaine.com}"

echo -e "${BLUE}🚀 Déploiement des assets pour la production${NC}"
echo ""

# Étape 1 : Compiler les assets
echo -e "${YELLOW}📦 Étape 1 : Compilation des assets...${NC}"
if ! npm run build; then
    echo -e "${RED}❌ Erreur lors de la compilation${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Assets compilés avec succès${NC}"
echo ""

# Vérifier que public/build existe
if [ ! -d "public/build" ]; then
    echo -e "${RED}❌ Erreur : Le dossier public/build/ n'existe pas${NC}"
    exit 1
fi

# Afficher la taille des assets
ASSETS_SIZE=$(du -sh public/build | cut -f1)
echo -e "${BLUE}📊 Taille des assets compilés : ${ASSETS_SIZE}${NC}"
echo ""

# Étape 2 : Transférer sur O2Switch
echo -e "${YELLOW}📤 Étape 2 : Transfert sur O2Switch...${NC}"
echo -e "${BLUE}   Destination : ${DEFAULT_HOST}:${DEFAULT_PATH}/public/${NC}"
echo ""

read -p "Voulez-vous transférer maintenant ? (o/N) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Oo]$ ]]; then
    echo -e "${YELLOW}Transfert en cours...${NC}"
    
    if scp -r public/build/ "${DEFAULT_HOST}:${DEFAULT_PATH}/public/"; then
        echo -e "${GREEN}✅ Assets transférés avec succès !${NC}"
        echo ""
        echo -e "${BLUE}📝 Prochaines étapes sur O2Switch :${NC}"
        echo "   1. Se connecter en SSH : ssh ${DEFAULT_HOST}"
        echo "   2. Aller dans le dossier : cd ${DEFAULT_PATH}"
        echo "   3. Vider le cache : php artisan view:clear"
        echo "   4. Tester l'application dans le navigateur"
    else
        echo -e "${RED}❌ Erreur lors du transfert${NC}"
        echo ""
        echo -e "${YELLOW}💡 Vous pouvez transférer manuellement avec :${NC}"
        echo "   scp -r public/build/ ${DEFAULT_HOST}:${DEFAULT_PATH}/public/"
        exit 1
    fi
else
    echo -e "${YELLOW}Transfert annulé${NC}"
    echo ""
    echo -e "${BLUE}💡 Pour transférer manuellement, exécutez :${NC}"
    echo "   scp -r public/build/ ${DEFAULT_HOST}:${DEFAULT_PATH}/public/"
fi

echo ""
echo -e "${GREEN}✨ Déploiement terminé !${NC}"

