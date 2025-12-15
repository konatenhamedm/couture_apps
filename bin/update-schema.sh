#!/bin/bash

# Script pour mettre à jour le schéma de base de données

if [ "$1" == "dev" ]; then
    echo "🔄 Mise à jour du schéma de la base DEV..."
    php bin/console doctrine:schema:update --force --em=dev
    echo "✅ Base DEV mise à jour!"
    
elif [ "$1" == "prod" ]; then
    echo "🔄 Mise à jour du schéma de la base PROD..."
    php bin/console doctrine:schema:update --force --em=prod
    echo "✅ Base PROD mise à jour!"
    
elif [ "$1" == "all" ]; then
    echo "🔄 Mise à jour de toutes les bases de données..."
    echo ""
    echo "📊 Mise à jour de DEFAULT..."
    php bin/console doctrine:schema:update --force --em=default
    echo ""
    echo "📊 Mise à jour de DEV..."
    php bin/console doctrine:schema:update --force --em=dev
    echo ""
    echo "📊 Mise à jour de PROD..."
    php bin/console doctrine:schema:update --force --em=prod
    echo ""
    echo "✅ Toutes les bases ont été mises à jour!"
    
else
    echo "❌ Usage: ./bin/update-schema.sh [dev|prod|all]"
    echo ""
    echo "Exemples:"
    echo "  ./bin/update-schema.sh dev   # Mettre à jour la base DEV"
    echo "  ./bin/update-schema.sh prod  # Mettre à jour la base PROD"
    echo "  ./bin/update-schema.sh all   # Mettre à jour toutes les bases"
    exit 1
fi