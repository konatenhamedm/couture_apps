# 🧵 Ateliya - Plateforme de Gestion de Couture

[![Symfony](https://img.shields.io/badge/Symfony-7.4-000000.svg?style=flat&logo=symfony)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4.svg?style=flat&logo=php)](https://php.net/)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)

Ateliya est une plateforme complète de gestion d'ateliers de couture qui permet aux couturiers de gérer leurs clients, mesures, réservations, stocks et paiements de manière efficace.

## 📋 Table des matières

- [Fonctionnalités](#-fonctionnalités)
- [Technologies utilisées](#-technologies-utilisées)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [API Documentation](#-api-documentation)
- [Structure du projet](#-structure-du-projet)
- [Utilisation](#-utilisation)
- [Contribution](#-contribution)
- [Support](#-support)
- [Licence](#-licence)

## ✨ Fonctionnalités

### 🏪 Gestion des Boutiques
- Création et gestion de boutiques multiples
- Gestion des succursales
- Configuration des paramètres par boutique

### 👥 Gestion des Clients
- Profils clients détaillés
- Historique des commandes
- Système de notifications

### 📏 Système de Mesures
- Prise de mesures personnalisées
- Catégories de mesures
- Types de mesures configurables
- Modèles de vêtements

### 📅 Réservations
- Système de réservation en ligne
- Gestion des créneaux
- Notifications automatiques

### 💰 Gestion Financière
- Système de facturation
- Gestion des paiements
- Abonnements et modules
- Rapports financiers

### 📦 Gestion des Stocks
- Suivi des entrées/sorties
- Gestion des inventaires
- Alertes de stock

### 🔐 Sécurité
- Authentification JWT
- Système OTP pour la réinitialisation de mot de passe
- Gestion des rôles et permissions

### 📱 Notifications
- Notifications push Firebase
- Emails automatiques
- Notifications in-app

## 🛠 Technologies utilisées

- **Backend**: Symfony 7.4
- **Base de données**: MySQL 8.0
- **Authentification**: JWT (Firebase JWT)
- **Notifications**: Firebase Cloud Messaging
- **Email**: Symfony Mailer
- **Documentation API**: Nelmio API Doc
- **QR Code**: Endroid QR Code
- **Tests**: PHPUnit

## 🚀 Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL 8.0
- Node.js (pour les assets)

### Étapes d'installation

1. **Cloner le repository**
```bash
git clone https://github.com/votre-username/ateliya.git
cd ateliya
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configuration de l'environnement**
```bash
cp .env .env.local
```

4. **Configurer la base de données**
Modifier le fichier `.env.local` avec vos paramètres de base de données :
```env
DATABASE_URL="mysql://username:password@127.0.0.1:3306/ateliya_db?serverVersion=8.0&charset=utf8mb4"
```

5. **Créer la base de données**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

6. **Installer les assets**
```bash
php bin/console importmap:install
```

7. **Démarrer le serveur de développement**
```bash
symfony server:start
```

## ⚙️ Configuration

### Variables d'environnement

Configurez les variables suivantes dans votre fichier `.env.local` :

```env
# JWT Configuration
JWT_SECRET=votre_secret_jwt_tres_securise
JWT_TTL=3600

# Email Configuration
MAILER_DSN=smtp://username:password@smtp.example.com:587

# Firebase Configuration (pour les notifications push)
# Placez votre fichier firebase_credentials.json dans config/

# CORS Configuration
CORS_ALLOW_ORIGIN="*"
```

### Configuration Firebase

1. Créez un projet Firebase
2. Téléchargez le fichier de configuration JSON
3. Placez-le dans `config/firebase_credentials.json`

## 📚 API Documentation

L'API est documentée avec Swagger/OpenAPI. Une fois l'application démarrée, accédez à :

```
http://localhost:8000/api/doc
```

### Endpoints principaux

- **Authentification**: `/api/auth/*`
- **Utilisateurs**: `/api/users/*`
- **Boutiques**: `/api/boutiques/*`
- **Clients**: `/api/clients/*`
- **Réservations**: `/api/reservations/*`
- **Mesures**: `/api/mesures/*`
- **Paiements**: `/api/paiements/*`
- **Statistiques**: `/api/statistique/*` - [Documentation détaillée](API_STATISTICS.md)

## 📁 Structure du projet

```
ateliya/
├── config/                 # Configuration Symfony
├── migrations/             # Migrations de base de données
├── public/                 # Point d'entrée web
├── src/
│   ├── Command/           # Commandes console
│   ├── Controller/        # Contrôleurs API
│   ├── Entity/           # Entités Doctrine
│   ├── Repository/       # Repositories
│   ├── Security/         # Authentification JWT
│   └── Service/          # Services métier
├── templates/            # Templates Twig (emails)
└── tests/               # Tests unitaires
```

## 💡 Utilisation

### Authentification

1. **Inscription/Connexion**
```bash
POST /api/auth/register
POST /api/auth/login
```

2. **Utilisation du token JWT**
Incluez le token dans l'en-tête Authorization :
```
Authorization: Bearer votre_token_jwt
```

### API Statistiques

1. **Dashboard avec métriques avancées**
```bash
POST /api/statistique/dashboard
```

2. **Graphiques d'évolution**
```bash
POST /api/statistique/revenus/evolution
POST /api/statistique/commandes/evolution
```

3. **Analyses clients**
```bash
POST /api/statistique/top-clients
POST /api/statistique/comparatif
```

📊 **[Voir la documentation complète des statistiques](API_STATISTICS.md)**

### Gestion des mesures

1. **Créer une catégorie de mesure**
2. **Définir les types de mesures**
3. **Prendre les mesures client**
4. **Associer aux modèles**

### Système de réservation

1. **Créer des créneaux disponibles**
2. **Permettre aux clients de réserver**
3. **Gérer les confirmations**

## 🤝 Contribution

1. Fork le projet
2. Créez votre branche feature (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

### Standards de code

- Suivez les standards PSR-12
- Utilisez PHPStan pour l'analyse statique
- Écrivez des tests pour les nouvelles fonctionnalités

## 📞 Support

Pour obtenir de l'aide :

- 📧 Email: support@ateliya.com
- 📱 Téléphone: +XXX XXX XXX XXX
- 🌐 Site web: https://ateliya.com

## 📄 Licence

Ce projet est sous licence propriétaire. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🙏 Remerciements

- L'équipe Symfony pour le framework
- La communauté PHP
- Tous les contributeurs du projet

---

**Développé avec ❤️ par l'équipe Ateliya**