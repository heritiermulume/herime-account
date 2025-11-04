#!/bin/bash

# Script pour supprimer les migrations OAuth en double
# Ce script supprime les migrations OAuth avec des dates récentes (> 2025_10_24)
# et garde seulement les migrations Passport originales publiées

echo "🔍 Recherche des migrations OAuth en double..."

MIGRATIONS_DIR="database/migrations"

# Trouver toutes les migrations OAuth
OAUTH_MIGRATIONS=$(find "$MIGRATIONS_DIR" -name "*oauth*.php" -type f)

if [ -z "$OAUTH_MIGRATIONS" ]; then
    echo "✅ Aucune migration OAuth trouvée."
    exit 0
fi

echo "📋 Migrations OAuth trouvées:"
echo "$OAUTH_MIGRATIONS" | while read migration; do
    echo "   - $(basename $migration)"
done

# Identifier les migrations à supprimer (dates récentes, créées automatiquement)
# On garde seulement les migrations Passport originales (2016_06_01_* et 2024_06_01_*)
# Supprimer toutes les migrations OAuth sauf celles avec les dates originales de Passport
DUPLICATES=$(find "$MIGRATIONS_DIR" -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" || true)

if [ -z "$DUPLICATES" ]; then
    echo "✅ Aucune migration OAuth en double trouvée."
    echo ""
    echo "Les migrations OAuth actuelles sont:"
    find "$MIGRATIONS_DIR" -name "*oauth*.php" -type f | sort
    exit 0
fi

echo ""
echo "⚠️  Migrations OAuth en double trouvées (seront supprimées):"
echo "$DUPLICATES" | while read migration; do
    echo "   - $(basename $migration)"
done

echo ""
read -p "Voulez-vous supprimer ces migrations en double ? (o/N) " -n 1 -r
echo ""

# Mode automatique si stdin n'est pas un terminal (non-interactif)
if [ ! -t 0 ]; then
    AUTO_MODE=true
else
    AUTO_MODE=false
fi

if [ "$AUTO_MODE" = true ] || [[ $REPLY =~ ^[Oo]$ ]]; then
    # Supprimer les migrations en double
    echo "$DUPLICATES" | while read migration; do
        if [ -f "$migration" ]; then
            rm "$migration"
            echo "✅ Supprimé: $(basename $migration)"
        fi
    done
    
    echo ""
    echo "✅ Migrations OAuth en double supprimées."
    echo ""
    echo "Les migrations OAuth restantes sont:"
    find "$MIGRATIONS_DIR" -name "*oauth*.php" -type f | sort | while read migration; do
        echo "   - $(basename $migration)"
    done
    echo ""
    echo "📝 Prochaines étapes :"
    echo "   1. Vérifier l'état des migrations : php artisan migrate:status"
    echo "   2. Réessayer : php artisan migrate --force"
else
    echo "❌ Suppression annulée."
fi

