#!/bin/bash

# Script de déploiement pour HERIME SSO
# Usage: ./deploy.sh [environment]

set -e

ENVIRONMENT=${1:-production}
APP_NAME="HERIME SSO"
APP_DIR="/var/www/herime-sso"

echo "🚀 Déploiement de $APP_NAME en mode $ENVIRONMENT"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# Vérifier les prérequis
check_prerequisites() {
    log "Vérification des prérequis..."
    
    if ! command -v docker &> /dev/null; then
        error "Docker n'est pas installé"
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose n'est pas installé"
    fi
    
    if ! command -v php &> /dev/null; then
        error "PHP n'est pas installé"
    fi
    
    if ! command -v composer &> /dev/null; then
        error "Composer n'est pas installé"
    fi
    
    if ! command -v npm &> /dev/null; then
        error "NPM n'est pas installé"
    fi
    
    log "✅ Tous les prérequis sont satisfaits"
}

# Créer la sauvegarde
create_backup() {
    if [ -d "$APP_DIR" ]; then
        log "Création d'une sauvegarde..."
        BACKUP_DIR="/var/backups/herime-sso/$(date +%Y%m%d_%H%M%S)"
        mkdir -p "$BACKUP_DIR"
        cp -r "$APP_DIR" "$BACKUP_DIR/"
        log "✅ Sauvegarde créée dans $BACKUP_DIR"
    fi
}

# Cloner ou mettre à jour le code
update_code() {
    log "Mise à jour du code source..."
    
    if [ ! -d "$APP_DIR" ]; then
        log "Clonage du repository..."
        git clone <repository-url> "$APP_DIR"
    else
        log "Mise à jour du repository..."
        cd "$APP_DIR"
        git fetch origin
        git reset --hard origin/main
    fi
    
    cd "$APP_DIR"
    log "✅ Code source mis à jour"
}

# Installer les dépendances
install_dependencies() {
    log "Installation des dépendances..."
    
    cd "$APP_DIR"
    
    # Dépendances PHP
    log "Installation des dépendances PHP..."
    composer install --no-dev --optimize-autoloader
    
    # Dépendances Node.js
    log "Installation des dépendances Node.js..."
    npm install --production
    
    # Compiler les assets
    log "Compilation des assets..."
    npm run build
    
    log "✅ Dépendances installées"
}

# Configuration de l'environnement
setup_environment() {
    log "Configuration de l'environnement..."
    
    cd "$APP_DIR"
    
    if [ ! -f ".env" ]; then
        log "Création du fichier .env..."
        cp .env.example .env
    fi
    
    # Générer la clé d'application
    php artisan key:generate
    
    # Configurer les permissions
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    
    log "✅ Environnement configuré"
}

# Exécuter les migrations
run_migrations() {
    log "Exécution des migrations..."
    
    cd "$APP_DIR"
    
    # Attendre que la base de données soit prête
    log "Attente de la base de données..."
    until php artisan migrate:status &> /dev/null; do
        sleep 5
    done
    
    # Exécuter les migrations
    php artisan migrate --force
    
    # Installer Passport si nécessaire
    if [ ! -f "storage/oauth-private.key" ]; then
        log "Installation de Passport..."
        php artisan passport:install --force
    fi
    
    log "✅ Migrations exécutées"
}

# Optimiser l'application
optimize_application() {
    log "Optimisation de l'application..."
    
    cd "$APP_DIR"
    
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

# Démarrer les services
start_services() {
    log "Démarrage des services..."
    
    cd "$APP_DIR"
    
    # Arrêter les services existants
    docker-compose down 2>/dev/null || true
    
    # Démarrer les services
    docker-compose up -d
    
    # Attendre que les services soient prêts
    log "Attente du démarrage des services..."
    sleep 30
    
    # Vérifier la santé des services
    if ! docker-compose ps | grep -q "Up"; then
        error "Échec du démarrage des services"
    fi
    
    log "✅ Services démarrés"
}

# Tests de santé
health_check() {
    log "Vérification de la santé de l'application..."
    
    # Attendre que l'application soit prête
    sleep 10
    
    # Test de l'API
    if curl -f http://localhost/api/auth/me > /dev/null 2>&1; then
        log "✅ API accessible"
    else
        warning "⚠️  API non accessible (normal si pas d'authentification)"
    fi
    
    # Test de la base de données
    cd "$APP_DIR"
    if php artisan migrate:status > /dev/null 2>&1; then
        log "✅ Base de données accessible"
    else
        error "❌ Base de données non accessible"
    fi
    
    log "✅ Vérifications de santé terminées"
}

# Nettoyage
cleanup() {
    log "Nettoyage..."
    
    # Supprimer les anciens logs
    find /var/log -name "*.log" -mtime +7 -delete 2>/dev/null || true
    
    # Nettoyer Docker
    docker system prune -f
    
    log "✅ Nettoyage terminé"
}

# Fonction principale
main() {
    log "Début du déploiement de $APP_NAME"
    
    check_prerequisites
    create_backup
    update_code
    install_dependencies
    setup_environment
    run_migrations
    optimize_application
    start_services
    health_check
    cleanup
    
    log "🎉 Déploiement terminé avec succès!"
    log "Application accessible sur: https://account.herime.com"
    log "Interface d'administration: https://account.herime.com/dashboard"
}

# Gestion des erreurs
trap 'error "Déploiement interrompu"' INT TERM

# Exécution
main "$@"
