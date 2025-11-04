#!/bin/bash
# Script pour afficher les erreurs récentes des logs Laravel

echo "🔍 Diagnostic des erreurs Laravel"
echo "==================================="
echo ""
echo "📋 Dernières erreurs (top 3):"
echo "-----------------------------------"
tail -n 500 storage/logs/laravel.log | grep -A 20 "ERROR" | tail -60
echo ""
echo "📋 Dernière erreur complète:"
echo "-----------------------------------"
tail -n 200 storage/logs/laravel.log | grep -B 5 -A 60 "ERROR" | tail -70

