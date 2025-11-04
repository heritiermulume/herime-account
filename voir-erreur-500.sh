#!/bin/bash
# Script pour voir l'erreur 500 exacte
set -e

echo "🔍 Recherche de l'erreur 500 dans les logs"
echo "=========================================="

echo ""
echo "Dernières erreurs (50 dernières lignes) :"
tail -n 50 storage/logs/laravel.log | grep -A 20 -B 5 "ERROR\|Exception\|Fatal" | tail -30

echo ""
echo "=========================================="
echo "💡 Pour voir les logs en temps réel :"
echo "   tail -f storage/logs/laravel.log"

