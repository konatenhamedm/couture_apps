# Guide de Configuration des Environnements

## Structure des fichiers d'environnement

- `.env` : Valeurs par défaut (base de développement)
- `.env.dev` : Configuration spécifique DEV (BD: app_couture_dev)
- `.env.prod` : Configuration spécifique PROD (BD: app_couture_prod)
- `.env.local` : Surcharges locales (non commité dans Git)

## Bases de données

- **Développement** : `app_couture_dev`
- **Production** : `app_couture_prod`

## Comment basculer entre les environnements

### Mode DEV (par défaut)

```bash
# S'assurer que APP_ENV=dev dans .env ou .env.local
export APP_ENV=dev

# Vider le cache
php bin/console cache:clear

# Créer la base de données si elle n'existe pas
php bin/console doctrine:database:create --if-not-exists

# Exécuter les migrations
php bin/console doctrine:migrations:migrate -n
```

### Mode PROD

```bash
# Définir l'environnement en production
export APP_ENV=prod

# Vider le cache de production
php bin/console cache:clear --env=prod

# Créer la base de données si elle n'existe pas
php bin/console doctrine:database:create --if-not-exists --env=prod

# Exécuter les migrations
php bin/console doctrine:migrations:migrate -n --env=prod

# Optimiser l'autoloader
composer dump-autoload --optimize --classmap-authoritative
```

### Vérifier l'environnement actuel

```bash
php bin/console about
```

## Démarrer le serveur

### En DEV
```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

### En PROD
```bash
# Assurez-vous d'avoir APP_ENV=prod
APP_ENV=prod symfony server:start
# ou
APP_ENV=prod php -S localhost:8000 -t public/
```

## Variables d'environnement importantes

- `APP_ENV` : dev ou prod
- `DATABASE_URL` : Connexion à la base de données
- `APP_SECRET` : Clé secrète de l'application

## Bonnes pratiques

1. **Ne jamais commiter** `.env.local` dans Git
2. **Toujours tester** en dev avant de déployer en prod
3. **Sauvegarder** la base de données prod avant les migrations
4. **Utiliser** des secrets Symfony pour les données sensibles en production

## Création des bases de données

```bash
# Créer la base DEV
APP_ENV=dev php bin/console doctrine:database:create

# Créer la base PROD
APP_ENV=prod php bin/console doctrine:database:create
```

## Dump de la base de données

```bash
# Exporter la base DEV
mysqldump -u root app_couture_dev > backup_dev.sql

# Exporter la base PROD
mysqldump -u root app_couture_prod > backup_prod.sql

# Importer dans une base
mysql -u root app_couture_dev < backup_dev.sql
```