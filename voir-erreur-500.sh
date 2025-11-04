#!/bin/bash
# Script pour voir l'erreur 500 exacte
set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "🔍 Recherche de l'erreur 500 dans les logs"
echo "=========================================="

# 1. Vérifier que le fichier de log existe
if [ ! -f "storage/logs/laravel.log" ]; then
    error "Fichier de log non trouvé: storage/logs/laravel.log"
    exit 1
fi

# 2. Voir les dernières erreurs
echo ""
log "1. Dernières erreurs (100 dernières lignes) :"
tail -n 100 storage/logs/laravel.log | grep -A 30 -B 5 "ERROR\|Exception\|Fatal" | tail -50 || warning "Aucune erreur trouvée dans les 100 dernières lignes"

# 3. Voir toutes les erreurs de la session
echo ""
log "2. Toutes les erreurs de la journée :"
grep -E "ERROR|Exception|Fatal" storage/logs/laravel.log | tail -10 || warning "Aucune erreur trouvée aujourd'hui"

# 4. Voir les logs récents (sessions)
echo ""
log "3. Logs récents concernant les sessions :"
tail -n 200 storage/logs/laravel.log | grep -i "session\|Sessions" | tail -20 || warning "Aucun log de session trouvé"

# 5. Voir la taille du fichier de log
echo ""
log "4. Taille du fichier de log :"
ls -lh storage/logs/laravel.log | awk '{print "Taille: " $5}'

echo ""
echo "=========================================="
warning "💡 Pour tester l'API et voir les logs en temps réel :"
echo "   ./tester-api-sessions.sh"
echo "   # Dans un autre terminal :"
echo "   tail -f storage/logs/laravel.log"

