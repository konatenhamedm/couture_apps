#!/bin/bash

# Script pour basculer entre les environnements dev et prod

if [ "$1" == "dev" ]; then
    echo "🔄 Basculement vers l'environnement DEV..."
    export APP_ENV=dev
    
    echo "🗑️  Nettoyage du cache..."
    php bin/console cache:clear
    
    echo "📊 Base de données: app_couture_dev"
    echo "✅ Environnement DEV activé!"
    echo ""
    echo "Pour démarrer le serveur:"
    echo "  symfony server:start"
    echo "  ou"
    echo "  php -S localhost:8000 -t public/"
    
elif [ "$1" == "prod" ]; then
    echo "🔄 Basculement vers l'environnement PROD..."
    export APP_ENV=prod
    
    echo "🗑️  Nettoyage du cache de production..."
    php bin/console cache:clear --env=prod --no-warmup
    
    echo "🔥 Préchauffage du cache..."
    php bin/console cache:warmup --env=prod
    
    echo "⚡ Optimisation de l'autoloader..."
    composer dump-autoload --optimize --classmap-authoritative
    
    echo "📊 Base de données: app_couture_prod"
    echo "✅ Environnement PROD activé!"
    echo ""
    echo "⚠️  ATTENTION: Vous êtes en mode PRODUCTION!"
    echo ""
    echo "Pour démarrer le serveur:"
    echo "  APP_ENV=prod symfony server:start"
    echo "  ou"
    echo "  APP_ENV=prod php -S localhost:8000 -t public/"
    
else
    echo "❌ Usage: ./bin/switch-env.sh [dev|prod]"
    echo ""
    echo "Exemples:"
    echo "  ./bin/switch-env.sh dev   # Basculer en développement"
    echo "  ./bin/switch-env.sh prod  # Basculer en production"
    exit 1
fi