# Guide d'Installation - Ateliya

Ce guide vous accompagne dans l'installation complète d'Ateliya sur votre serveur.

## Prérequis système

### Serveur

- **OS** : Ubuntu 20.04+ / CentOS 8+ / Debian 11+
- **RAM** : 2GB minimum, 4GB recommandé
- **Stockage** : 10GB minimum
- **CPU** : 2 cœurs minimum

### Logiciels requis

- **PHP** : 8.2 ou supérieur
- **MySQL** : 8.0 ou supérieur
- **Nginx** : 1.18+ ou Apache 2.4+
- **Composer** : 2.0+
- **Node.js** : 18+ (pour les assets)
- **Redis** : 6.0+ (optionnel, pour le cache)

### Extensions PHP requises

```bash
php -m | grep -E "(ctype|iconv|json|mbstring|openssl|pdo|pdo_mysql|tokenizer|xml|curl|gd|intl|zip)"
```

## Installation pas à pas

### 1. Préparation du serveur

#### Ubuntu/Debian

```bash
# Mise à jour du système
sudo apt update && sudo apt upgrade -y

# Installation des dépendances
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl \
    php8.2-gd php8.2-mbstring php8.2-zip php8.2-intl php8.2-bcmath \
    mysql-server nginx composer nodejs npm git unzip

# Installation de Redis (optionnel)
sudo apt install -y redis-server
```

#### CentOS/RHEL

```bash
# Installation des dépôts
sudo dnf install -y epel-release
sudo dnf module enable php:8.2

# Installation des dépendances
sudo dnf install -y php php-fpm php-mysqlnd php-xml php-curl php-gd \
    php-mbstring php-zip php-intl php-bcmath mysql-server nginx \
    composer nodejs npm git unzip

# Installation de Redis (optionnel)
sudo dnf install -y redis
```

### 2. Configuration de MySQL

```bash
# Démarrage et activation de MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# Sécurisation de MySQL
sudo mysql_secure_installation

# Création de la base de données
sudo mysql -u root -p
```

```sql
CREATE DATABASE ateliya_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ateliya_user'@'localhost' IDENTIFIED BY 'mot_de_passe_securise';
GRANT ALL PRIVILEGES ON ateliya_db.* TO 'ateliya_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Téléchargement d'Ateliya

```bash
# Création du répertoire
sudo mkdir -p /var/www/ateliya
cd /var/www/ateliya

# Clonage du repository (ou téléchargement de l'archive)
sudo git clone https://github.com/votre-repo/ateliya.git .

# Ou téléchargement direct
# sudo wget https://github.com/votre-repo/ateliya/archive/main.zip
# sudo unzip main.zip && sudo mv ateliya-main/* .

# Attribution des permissions
sudo chown -R www-data:www-data /var/www/ateliya
sudo chmod -R 755 /var/www/ateliya
```

### 4. Installation des dépendances

```bash
# Dépendances PHP
cd /var/www/ateliya
sudo -u www-data composer install --no-dev --optimize-autoloader

# Dépendances Node.js (si nécessaire)
sudo -u www-data npm install
sudo -u www-data npm run build
```

### 5. Configuration de l'application

```bash
# Copie du fichier de configuration
sudo -u www-data cp .env .env.local

# Édition de la configuration
sudo -u www-data nano .env.local
```

Configuration `.env.local` :

```env
# Environnement
APP_ENV=prod
APP_SECRET=votre_secret_application_tres_long_et_securise

# Base de données
DATABASE_URL="mysql://ateliya_user:mot_de_passe_securise@127.0.0.1:3306/ateliya_db?serverVersion=8.0&charset=utf8mb4"

# JWT
JWT_SECRET=votre_secret_jwt_tres_long_et_securise
JWT_TTL=3600

# Email (exemple avec Gmail)
MAILER_DSN=smtp://username:password@smtp.gmail.com:587

# Redis (si utilisé)
REDIS_URL=redis://localhost:6379

# CORS
CORS_ALLOW_ORIGIN="https://votre-domaine.com"
```

### 6. Initialisation de la base de données

```bash
cd /var/www/ateliya

# Création des tables
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction

# Chargement des données de base (optionnel)
sudo -u www-data php bin/console doctrine:fixtures:load --no-interaction

# Génération des assets
sudo -u www-data php bin/console asset-map:compile
```

### 7. Configuration de Nginx

Créez le fichier `/etc/nginx/sites-available/ateliya` :

```nginx
server {
    listen 80;
    server_name votre-domaine.com www.votre-domaine.com;
    root /var/www/ateliya/public;
    index index.php;

    # Logs
    access_log /var/log/nginx/ateliya_access.log;
    error_log /var/log/nginx/ateliya_error.log;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Main location
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    # PHP-FPM
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    # Deny access to other PHP files
    location ~ \.php$ {
        return 404;
    }

    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /(config|src|templates|translations|var|vendor)/ {
        deny all;
    }
}
```

Activation du site :

```bash
# Activation du site
sudo ln -s /etc/nginx/sites-available/ateliya /etc/nginx/sites-enabled/

# Test de la configuration
sudo nginx -t

# Redémarrage de Nginx
sudo systemctl restart nginx
sudo systemctl enable nginx
```

### 8. Configuration de PHP-FPM

Éditez `/etc/php/8.2/fpm/pool.d/www.conf` :

```ini
; Utilisateur et groupe
user = www-data
group = www-data

; Configuration des processus
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Limites de mémoire
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 10M
```

Redémarrage de PHP-FPM :

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm
```

### 9. Configuration SSL avec Let's Encrypt

```bash
# Installation de Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtention du certificat
sudo certbot --nginx -d votre-domaine.com -d www.votre-domaine.com

# Test du renouvellement automatique
sudo certbot renew --dry-run
```

### 10. Configuration des tâches cron

```bash
# Édition du crontab
sudo -u www-data crontab -e
```

Ajoutez ces lignes :

```cron
# Nettoyage des logs (quotidien à 2h)
0 2 * * * cd /var/www/ateliya && php bin/console app:logs:cleanup

# Renouvellement des abonnements (quotidien à 3h)
0 3 * * * cd /var/www/ateliya && php bin/console app:subscriptions:renew

# Envoi des notifications (toutes les 15 minutes)
*/15 * * * * cd /var/www/ateliya && php bin/console app:notifications:send

# Sauvegarde de la base de données (quotidien à 1h)
0 1 * * * mysqldump -u ateliya_user -p'mot_de_passe_securise' ateliya_db > /var/backups/ateliya_$(date +\%Y\%m\%d).sql
```

## Configuration avancée

### Redis pour le cache

Configuration dans `.env.local` :

```env
CACHE_ADAPTER=cache.adapter.redis
REDIS_URL=redis://localhost:6379
```

### Monitoring avec Supervisor

Installation :

```bash
sudo apt install -y supervisor
```

Configuration `/etc/supervisor/conf.d/ateliya-worker.conf` :

```ini
[program:ateliya-worker]
command=php /var/www/ateliya/bin/console messenger:consume async --time-limit=3600
user=www-data
numprocs=2
startsecs=0
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
```

### Sauvegarde automatique

Script de sauvegarde `/usr/local/bin/backup-ateliya.sh` :

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/ateliya"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="ateliya_db"
DB_USER="ateliya_user"
DB_PASS="mot_de_passe_securise"

# Création du répertoire de sauvegarde
mkdir -p $BACKUP_DIR

# Sauvegarde de la base de données
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Sauvegarde des fichiers uploadés
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz -C /var/www/ateliya/public uploads/

# Nettoyage des anciennes sauvegardes (garde 30 jours)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

## Vérification de l'installation

### Tests de fonctionnement

```bash
# Test de l'API
curl -X GET https://votre-domaine.com/api/health

# Test de la base de données
cd /var/www/ateliya
sudo -u www-data php bin/console doctrine:schema:validate

# Test des permissions
sudo -u www-data php bin/console cache:clear --env=prod
```

### Monitoring des logs

```bash
# Logs Nginx
sudo tail -f /var/log/nginx/ateliya_error.log

# Logs PHP
sudo tail -f /var/log/php8.2-fpm.log

# Logs application
sudo tail -f /var/www/ateliya/var/log/prod.log
```

## Dépannage

### Problèmes courants

#### Erreur 500

```bash
# Vérifier les logs
sudo tail -f /var/log/nginx/ateliya_error.log
sudo tail -f /var/www/ateliya/var/log/prod.log

# Vérifier les permissions
sudo chown -R www-data:www-data /var/www/ateliya/var
sudo chmod -R 775 /var/www/ateliya/var
```

#### Problème de base de données

```bash
# Test de connexion
cd /var/www/ateliya
sudo -u www-data php bin/console doctrine:database:create --if-not-exists
sudo -u www-data php bin/console doctrine:migrations:status
```

#### Problème de cache

```bash
# Vider le cache
cd /var/www/ateliya
sudo -u www-data php bin/console cache:clear --env=prod
sudo -u www-data php bin/console cache:warmup --env=prod
```

## Mise à jour

### Processus de mise à jour

```bash
# Sauvegarde
sudo /usr/local/bin/backup-ateliya.sh

# Mise en mode maintenance
sudo -u www-data touch /var/www/ateliya/public/maintenance.html

# Mise à jour du code
cd /var/www/ateliya
sudo -u www-data git pull origin main

# Mise à jour des dépendances
sudo -u www-data composer install --no-dev --optimize-autoloader

# Migrations de base de données
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction

# Compilation des assets
sudo -u www-data php bin/console asset-map:compile

# Nettoyage du cache
sudo -u www-data php bin/console cache:clear --env=prod

# Sortie du mode maintenance
sudo rm /var/www/ateliya/public/maintenance.html
```

## Support

Pour obtenir de l'aide lors de l'installation :

- **Documentation** : https://docs.ateliya.com
- **Email** : support@ateliya.com
- **Discord** : [Lien vers le serveur Discord]