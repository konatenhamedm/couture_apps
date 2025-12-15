@echo off
REM Script pour mettre à jour le schéma de base de données (Windows)

if "%1"=="dev" (
    echo Mise a jour du schema de la base DEV...
    php bin/console doctrine:schema:update --force --em=dev
    echo Base DEV mise a jour!
    
) else if "%1"=="prod" (
    echo Mise a jour du schema de la base PROD...
    php bin/console doctrine:schema:update --force --em=prod
    echo Base PROD mise a jour!
    
) else if "%1"=="all" (
    echo Mise a jour de toutes les bases de donnees...
    echo.
    echo Mise a jour de DEFAULT...
    php bin/console doctrine:schema:update --force --em=default
    echo.
    echo Mise a jour de DEV...
    php bin/console doctrine:schema:update --force --em=dev
    echo.
    echo Mise a jour de PROD...
    php bin/console doctrine:schema:update --force --em=prod
    echo.
    echo Toutes les bases ont ete mises a jour!
    
) else (
    echo Usage: bin\update-schema.bat [dev^|prod^|all]
    echo.
    echo Exemples:
    echo   bin\update-schema.bat dev   REM Mettre a jour la base DEV
    echo   bin\update-schema.bat prod  REM Mettre a jour la base PROD
    echo   bin\update-schema.bat all   REM Mettre a jour toutes les bases
    exit /b 1
)