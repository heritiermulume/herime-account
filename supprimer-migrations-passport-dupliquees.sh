#!/bin/bash

# Script pour supprimer les migrations Passport en double sur O2Switch
# Usage: ./supprimer-migrations-passport-dupliquees.sh

echo "🔍 Recherche des migrations Passport en double..."

# Trouver les migrations Passport créées après le 2025_10_23
# (les migrations Passport ont généralement une date récente)
MIGRATIONS_DOUBLES=$(find database/migrations -name "*2025_11_*oauth*.php" -o -name "*2025_11_*oauth*.php" 2>/dev/null)

if [ -z "$MIGRATIONS_DOUBLES" ]; then
    echo "✅ Aucune migration Passport en double trouvée."
    echo ""
    echo "Les migrations OAuth existantes sont :"
    ls -la database/migrations/*oauth* 2>/dev/null
    exit 0
fi

echo "⚠️  Migrations Passport en double trouvées :"
echo "$MIGRATIONS_DOUBLES"
echo ""

read -p "Voulez-vous supprimer ces migrations ? (o/N) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Oo]$ ]]; then
    # Supprimer les migrations en double
    find database/migrations -name "*2025_11_*oauth*.php" -delete
    
    echo "✅ Migrations Passport en double supprimées."
    echo ""
    echo "Les migrations OAuth restantes sont :"
    ls -la database/migrations/*oauth* 2>/dev/null
    echo ""
    echo "📝 Prochaines étapes :"
    echo "   1. Vérifier l'état des migrations : php artisan migrate:status"
    echo "   2. Si les tables existent déjà, marquer les migrations comme exécutées"
    echo "   3. Réessayer : php artisan migrate --force"
else
    echo "❌ Suppression annulée."
fi

