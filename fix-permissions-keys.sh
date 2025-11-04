#!/bin/bash
# Script pour corriger les permissions des clés Passport
# Usage: ./fix-permissions-keys.sh

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }

echo "🔐 Correction des permissions des clés Passport"
echo "================================================"

if [ -f "storage/oauth-private.key" ]; then
    chmod 600 storage/oauth-private.key
    log "Permissions oauth-private.key corrigées (600)"
else
    error "oauth-private.key introuvable"
fi

if [ -f "storage/oauth-public.key" ]; then
    chmod 644 storage/oauth-public.key
    log "Permissions oauth-public.key corrigées (644)"
else
    error "oauth-public.key introuvable"
fi

echo ""
echo "✅ Permissions corrigées !"

