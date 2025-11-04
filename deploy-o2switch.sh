#!/bin/bash

# Script de déploiement pour O2Switch
# Usage: ./deploy-o2switch.sh [environment]
# Ce script doit être exécuté sur le serveur O2Switch

set -e

ENVIRONMENT=${1:-production}
APP_NAME="HERIME Account"

echo "🚀 Déploiement de $APP_NAME en mode $ENVIRONMENT"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction de log
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERREUR: $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] ATTENTION: $1${NC}"
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}"
}

# Vérifier les prérequis
check_prerequisites() {
    log "Vérification des prérequis..."
    
    if ! command -v php &> /dev/null; then
        error "PHP n'est pas installé ou pas dans le PATH"
    fi
    
    if ! command -v composer &> /dev/null; then
        warning "Composer n'est pas installé. Vous devrez installer les dépendances manuellement."
    fi
    
    info "Version PHP: $(php -v | head -n 1)"
    if command -v composer &> /dev/null; then
        info "Version Composer: $(composer --version)"
    fi
    
    log "✅ Prérequis vérifiés"
}

# Créer la sauvegarde
create_backup() {
    log "Création d'une sauvegarde..."
    BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # Sauvegarder les fichiers importants
    if [ -f ".env" ]; then
        cp .env "$BACKUP_DIR/.env.backup"
        log "✅ Fichier .env sauvegardé"
    fi
    
    if [ -d "storage" ]; then
        cp -r storage "$BACKUP_DIR/"
        log "✅ Dossier storage sauvegardé"
    fi
    
    log "✅ Sauvegarde créée dans $BACKUP_DIR"
}

# Cloner ou mettre à jour le code
update_code() {
    log "Mise à jour du code source..."
    
    # Vérifier si c'est un dépôt Git
    if [ ! -d ".git" ]; then
        warning "Ce n'est pas un dépôt Git. Assurez-vous que le code est à jour."
        return
    fi
    
    # Récupérer les dernières modifications
    log "Récupération des modifications..."
    git fetch origin || warning "Impossible de récupérer les modifications"
    git reset --hard origin/main || warning "Impossible de réinitialiser la branche"
    
    log "✅ Code source mis à jour"
}

# Installer les dépendances PHP
install_php_dependencies() {
    log "Installation des dépendances PHP..."
    
    if ! command -v composer &> /dev/null; then
        warning "Composer n'est pas disponible. Ignorer cette étape."
        info "Vous devrez exécuter manuellement: composer install --no-dev --optimize-autoloader"
        return
    fi
    
    composer install --no-dev --optimize-autoloader --no-interaction
    
    log "✅ Dépendances PHP installées"
}

# Compiler les assets frontend
build_assets() {
    log "Compilation des assets frontend..."
    
    if ! command -v npm &> /dev/null && ! command -v node &> /dev/null; then
        warning "Node.js/NPM n'est pas disponible sur le serveur."
        info "Vous devrez compiler les assets localement et les transférer via FTP/SCP"
        info "Commande locale: npm run build"
        return
    fi
    
    # Compiler les assets
    npm install --production --no-audit
    npm run build
    
    log "✅ Assets compilés"
}

# Configuration de l'environnement
setup_environment() {
    log "Configuration de l'environnement..."
    
    if [ ! -f ".env" ]; then
        if [ -f "env.o2switch.example" ]; then
            log "Création du fichier .env depuis env.o2switch.example..."
            cp env.o2switch.example .env
        elif [ -f ".env.example" ]; then
            log "Création du fichier .env depuis .env.example..."
            cp .env.example .env
        else
            error "Fichier .env manquant et aucun fichier d'exemple introuvable"
        fi
    else
        log "Fichier .env existe déjà"
    fi
    
    # Générer la clé d'application si nécessaire
    php artisan key:generate --force
    
    log "✅ Environnement configuré"
}

# Supprimer les migrations OAuth en double
clean_duplicate_migrations() {
    log "Nettoyage des migrations OAuth en double..."
    
    if [ -f "supprimer-migrations-oauth-dupliquees.sh" ]; then
        # Exécuter le script de nettoyage automatiquement (mode non-interactif)
        bash supprimer-migrations-oauth-dupliquees.sh <<< "o" 2>/dev/null || true
        
        # Alternative: supprimer directement les migrations qui ne sont pas les originales Passport
        find database/migrations -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" | xargs -r rm -f
        
        log "✅ Migrations OAuth en double supprimées"
    else
        # Supprimer directement les migrations qui ne sont pas les originales Passport
        find database/migrations -name "*oauth*.php" -type f | grep -vE "(2016_06_01|2024_06_01)" | xargs -r rm -f
        log "✅ Nettoyage des migrations OAuth effectué"
    fi
}

# Exécuter les migrations
run_migrations() {
    log "Exécution des migrations..."
    
    # Nettoyer les migrations OAuth en double AVANT d'exécuter les migrations
    clean_duplicate_migrations
    
    # Vérifier la connexion à la base de données
    php artisan migrate:status || warning "Impossible de vérifier le statut des migrations"
    
    # Exécuter les migrations
    php artisan migrate --force --no-interaction
    
    log "✅ Migrations exécutées"
}

# Installer Passport
install_passport() {
    log "Configuration de Passport..."
    
    # Vérifier si Passport est déjà installé
    if php artisan passport:keys --quiet 2>/dev/null; then
        log "Passport est déjà configuré"
    else
        log "Installation de Passport..."
        
        # Ne PAS publier les migrations (elles sont déjà dans le repository)
        # Créer les clés seulement
        php artisan passport:keys --force || warning "Échec de la création des clés Passport"
        
        # Publier uniquement la config si nécessaire
        php artisan vendor:publish --tag=passport-config --force > /dev/null 2>&1 || true
        
        # NE PAS publier les migrations pour éviter les doublons
        # php artisan vendor:publish --tag=passport-migrations --force
        
        log "✅ Passport configuré (migrations non publiées, déjà présentes dans le repository)"
    fi
    
    # Créer le client d'accès personnel si nécessaire
    log "Vérification du client d'accès personnel Passport..."
    if ! php artisan passport:client --list --quiet 2>/dev/null | grep -q "Personal Access Client"; then
        log "Création du client d'accès personnel..."
        php artisan passport:client --personal --name="Herime SSO Personal Access Client" --no-interaction || warning "Échec de la création du client personnel"
        log "✅ Client d'accès personnel créé"
    else
        log "Client d'accès personnel existe déjà"
    fi
    
    # Nettoyer les migrations OAuth en double après installation
    clean_duplicate_migrations
    
    log "✅ Passport configuré"
}

# Créer l'administrateur
seed_database() {
    log "Création de l'administrateur par défaut..."
    
    php artisan db:seed --class=DatabaseSeeder --force || warning "Échec du seed de la base de données"
    
    log "✅ Base de données initialisée"
}

# Optimiser l'application
optimize_application() {
    log "Optimisation de l'application..."
    
    # Cache de configuration
    php artisan config:cache
    
    # Cache des routes
    php artisan route:cache
    
    # Cache des vues
    php artisan view:cache
    
    # Optimisation générale
    php artisan optimize
    
    log "✅ Application optimisée"
}

# Configurer les permissions
setup_permissions() {
    log "Configuration des permissions..."
    
    # Essayer différents utilisateurs possibles
    if command -v whoami &> /dev/null; then
        CURRENT_USER=$(whoami)
        info "Utilisateur actuel: $CURRENT_USER"
    fi
    
    # Permissions de base (peut nécessiter des ajustements selon O2Switch)
    chmod -R 755 storage bootstrap/cache
    chmod -R 755 public
    
    log "✅ Permissions configurées"
    warning "Vous devrez peut-être ajuster les permissions selon la configuration O2Switch"
}

# Tests de santé
health_check() {
    log "Vérification de la santé de l'application..."
    
    # Test de la base de données
    if php artisan migrate:status > /dev/null 2>&1; then
        log "✅ Base de données accessible"
    else
        error "❌ Base de données non accessible"
    fi
    
    # Vérifier les fichiers importants
    if [ -d "vendor" ]; then
        log "✅ Dossier vendor présent"
    else
        warning "⚠️  Dossier vendor manquant"
    fi
    
    if [ -d "public/build" ]; then
        log "✅ Assets compilés présents"
    else
        warning "⚠️  Assets compilés manquants"
    fi
    
    log "✅ Vérifications de santé terminées"
}

# Afficher les informations de connexion
display_info() {
    log "📋 Informations de déploiement"
    
    info "Fichier .env utilisé: $(pwd)/.env"
    
    if [ -f ".env" ]; then
        if grep -q "APP_URL=" .env; then
            APP_URL=$(grep "APP_URL=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
            info "URL de l'application: $APP_URL"
        fi
    fi
    
    if [ -f ".env" ]; then
        if grep -q "DB_DATABASE=" .env; then
            DB_NAME=$(grep "DB_DATABASE=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
            info "Base de données: $DB_NAME"
        fi
    fi
    
    log "🔐 Identifiants administrateur par défaut:"
    echo "   Email: admin@example.com"
    echo "   Mot de passe: password"
    warning "⚠️  CHANGEZ CES IDENTIFIANTS IMMÉDIATEMENT APRÈS LE PREMIER LOGIN!"
}

# Fonction principale
main() {
    log "Début du déploiement de $APP_NAME sur O2Switch"
    
    check_prerequisites
    create_backup
    update_code
    install_php_dependencies
    build_assets
    setup_environment
    run_migrations
    install_passport
    seed_database
    optimize_application
    setup_permissions
    health_check
    display_info
    
    log "🎉 Déploiement terminé avec succès!"
    log ""
    log "📝 Prochaines étapes:"
    log "   1. Testez l'application dans votre navigateur"
    log "   2. Connectez-vous avec le compte admin par défaut"
    log "   3. Changez le mot de passe admin immédiatement"
    log "   4. Configurez les identifiants de base de données dans .env si nécessaire"
    log "   5. Vérifiez les logs en cas d'erreur: storage/logs/laravel.log"
}

# Gestion des erreurs
trap 'error "Déploiement interrompu"' INT TERM

# Vérifier si on est à la racine du projet Laravel
if [ ! -f "artisan" ]; then
    error "Ce script doit être exécuté à la racine du projet Laravel"
fi

# Exécution
main "$@"

