#!/bin/bash

# Script pour vérifier le format du mot de passe email dans .env

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 VÉRIFICATION DU MOT DE PASSE EMAIL"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ ! -f ".env" ]; then
    echo "❌ Fichier .env non trouvé !"
    exit 1
fi

echo "📋 Vérification du format de MAIL_PASSWORD dans .env :"
echo ""

# Extraire la ligne MAIL_PASSWORD
MAIL_PASSWORD_LINE=$(grep "^MAIL_PASSWORD=" .env)

if [ -z "$MAIL_PASSWORD_LINE" ]; then
    echo "   ❌ MAIL_PASSWORD non trouvé dans .env !"
    exit 1
fi

echo "   Ligne complète : $MAIL_PASSWORD_LINE"
echo ""

# Extraire la valeur (tout ce qui est après le =)
MAIL_PASSWORD_VALUE=$(echo "$MAIL_PASSWORD_LINE" | cut -d '=' -f2-)

# Vérifier s'il y a des guillemets
if [[ "$MAIL_PASSWORD_VALUE" =~ ^\".*\"$ ]] || [[ "$MAIL_PASSWORD_VALUE" =~ ^\'.*\'$ ]]; then
    echo "   ⚠️  Le mot de passe est entouré de guillemets"
    echo "   💡 Les guillemets peuvent causer des problèmes"
    echo "   🔧 Correction recommandée : enlever les guillemets"
    echo ""
    # Proposer de corriger
    read -p "   Voulez-vous enlever les guillemets automatiquement ? (o/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[OoYy]$ ]]; then
        # Enlever les guillemets
        MAIL_PASSWORD_CLEAN=$(echo "$MAIL_PASSWORD_VALUE" | sed 's/^["'\'']//;s/["'\'']$//')
        sed -i "s|^MAIL_PASSWORD=.*|MAIL_PASSWORD=$MAIL_PASSWORD_CLEAN|" .env
        echo "   ✅ Guillemets enlevés"
    fi
else
    echo "   ✅ Pas de guillemets détectés"
fi

# Vérifier s'il y a des espaces au début ou à la fin
MAIL_PASSWORD_TRIMMED=$(echo "$MAIL_PASSWORD_VALUE" | sed 's/^["'\'']//;s/["'\'']$//' | xargs)
if [ "$MAIL_PASSWORD_VALUE" != "$MAIL_PASSWORD_TRIMMED" ]; then
    echo "   ⚠️  Des espaces détectés au début ou à la fin"
    echo "   🔧 Correction recommandée : enlever les espaces"
    echo ""
    read -p "   Voulez-vous enlever les espaces automatiquement ? (o/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[OoYy]$ ]]; then
        sed -i "s|^MAIL_PASSWORD=.*|MAIL_PASSWORD=$MAIL_PASSWORD_TRIMMED|" .env
        echo "   ✅ Espaces enlevés"
    fi
fi

echo ""
echo "📋 Configuration actuelle :"
echo ""

cat .env | grep "^MAIL_" | sed 's/\(MAIL_PASSWORD=\).*/\1***MASQUÉ***/'

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💡 PROBLÈME IDENTIFIÉ :"
echo ""
echo "   ❌ Erreur 535 : Incorrect authentication data"
echo ""
echo "🔧 SOLUTIONS :"
echo ""
echo "   1. Vérifier que le mot de passe dans .env est exactement :"
echo "      s6e)4ew)3b92messagerie."
echo "      (sans guillemets, sans espaces)"
echo ""
echo "   2. Vérifier que MAIL_USERNAME est exactement :"
echo "      mail@herime.com"
echo ""
echo "   3. Si le mot de passe contient des caractères spéciaux, vérifier :"
echo "      - Pas de guillemets autour"
echo "      - Pas d'espaces avant/après"
echo "      - Tous les caractères sont corrects"
echo ""
echo "   4. Après modification, vider les caches :"
echo "      php artisan config:clear"
echo ""
echo "   5. Tester à nouveau :"
echo "      php test-email.php"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

