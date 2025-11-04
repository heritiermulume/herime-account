#!/bin/bash

# Script pour afficher l'erreur complète d'envoi d'email

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 AFFICHAGE DE L'ERREUR EMAIL COMPLÈTE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ ! -f "storage/logs/laravel.log" ]; then
    echo "❌ Fichier de log non trouvé : storage/logs/laravel.log"
    exit 1
fi

echo "📋 Dernières erreurs d'envoi d'email (50 dernières lignes) :"
echo ""

# Chercher les erreurs liées à l'email
tail -n 200 storage/logs/laravel.log | grep -B 20 -A 5 -i "Failed to send\|mail\|email\|password reset" | tail -n 50

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💡 Pour voir l'erreur complète avec le message d'exception :"
echo ""
echo "   tail -n 500 storage/logs/laravel.log | grep -B 30 'Failed to send'"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

