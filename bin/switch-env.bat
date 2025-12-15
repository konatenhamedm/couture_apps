@echo off
REM Script pour basculer entre les environnements dev et prod (Windows)

if "%1"=="dev" (
    echo Basculement vers l'environnement DEV...
    set APP_ENV=dev
    
    echo Nettoyage du cache...
    php bin/console cache:clear
    
    echo Base de donnees: app_couture_dev
    echo Environnement DEV active!
    echo.
    echo Pour demarrer le serveur:
    echo   symfony server:start
    echo   ou
    echo   php -S localhost:8000 -t public/
    
) else if "%1"=="prod" (
    echo Basculement vers l'environnement PROD...
    set APP_ENV=prod
    
    echo Nettoyage du cache de production...
    php bin/console cache:clear --env=prod --no-warmup
    
    echo Prechauffage du cache...
    php bin/console cache:warmup --env=prod
    
    echo Optimisation de l'autoloader...
    composer dump-autoload --optimize --classmap-authoritative
    
    echo Base de donnees: app_couture_prod
    echo Environnement PROD active!
    echo.
    echo ATTENTION: Vous etes en mode PRODUCTION!
    echo.
    echo Pour demarrer le serveur:
    echo   set APP_ENV=prod ^&^& symfony server:start
    echo   ou
    echo   set APP_ENV=prod ^&^& php -S localhost:8000 -t public/
    
) else (
    echo Usage: bin\switch-env.bat [dev^|prod]
    echo.
    echo Exemples:
    echo   bin\switch-env.bat dev   REM Basculer en developpement
    echo   bin\switch-env.bat prod  REM Basculer en production
    exit /b 1
)