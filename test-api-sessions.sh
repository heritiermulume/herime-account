#!/bin/bash
# Script pour tester l'API sessions
# Usage: ./test-api-sessions.sh

echo "🧪 Test API Sessions"
echo "===================="
echo ""

# Récupérer le token depuis localStorage (nécessite d'être exécuté dans le navigateur)
echo "Pour tester l'API sessions, ouvrez la console du navigateur (F12) et exécutez :"
echo ""
echo "const token = localStorage.getItem('access_token');"
echo "fetch('https://account.herime.com/api/sso/sessions', {"
echo "  headers: { 'Authorization': 'Bearer ' + token }"
echo "}).then(r => r.json()).then(console.log).catch(console.error);"
echo ""
echo "OU depuis le terminal avec curl (remplacez TOKEN par votre token) :"
echo ""
echo "curl -H 'Authorization: Bearer TOKEN' https://account.herime.com/api/sso/sessions"
echo ""

