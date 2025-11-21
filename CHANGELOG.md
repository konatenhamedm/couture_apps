# Changelog - Ateliya

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Versioning Sémantique](https://semver.org/lang/fr/).

## [Non publié]

### Ajouté
- Documentation complète du projet
- Politique de confidentialité et CGU
- Guide de contribution pour les développeurs

## [2.1.0] - 2024-01-15

### Ajouté
- Système de notifications push Firebase
- Export PDF des factures
- Gestion des codes QR pour les réservations
- API de statistiques avancées
- Support multi-langues (FR/EN)

### Modifié
- Interface utilisateur redesignée
- Performance des requêtes base de données améliorée
- Système d'authentification JWT renforcé

### Corrigé
- Problème de calcul des mesures pour les enfants
- Bug d'affichage sur mobile pour les tableaux
- Erreur de validation des emails avec caractères spéciaux

### Sécurité
- Mise à jour des dépendances de sécurité
- Renforcement de la validation des entrées utilisateur

## [2.0.0] - 2023-12-01

### Ajouté
- **BREAKING**: Nouvelle architecture API REST
- Système d'abonnements et modules
- Gestion multi-boutiques
- Paiements en ligne intégrés
- Système de réservations avancé
- Notifications par email automatiques
- Tableau de bord avec analytics

### Modifié
- **BREAKING**: Migration vers Symfony 7.4
- **BREAKING**: Nouvelle structure de base de données
- Interface d'administration repensée
- Système de permissions granulaires

### Supprimé
- **BREAKING**: Ancienne API v1
- Support PHP < 8.2
- Interface legacy

### Migration
Pour migrer depuis la v1.x :
```bash
php bin/console app:migrate:v2
```

## [1.9.2] - 2023-10-15

### Corrigé
- Correction critique de sécurité dans l'authentification
- Bug de synchronisation des stocks
- Problème d'envoi d'emails en production

### Sécurité
- Patch de sécurité pour les uploads de fichiers
- Renforcement des validations CSRF

## [1.9.1] - 2023-09-20

### Corrigé
- Erreur de calcul des taxes
- Problème d'affichage des mesures
- Bug de pagination sur les listes clients

### Modifié
- Amélioration des performances de l'API
- Optimisation des requêtes de recherche

## [1.9.0] - 2023-08-30

### Ajouté
- Système de mesures personnalisées
- Export Excel des données clients
- Intégration avec les services de livraison
- Mode sombre pour l'interface

### Modifié
- Amélioration de l'UX mobile
- Optimisation du système de cache
- Mise à jour des dépendances

### Corrigé
- Problème de timezone dans les réservations
- Bug d'arrondi dans les calculs de prix

## [1.8.5] - 2023-07-10

### Corrigé
- Correction urgente du système de paiement
- Bug critique dans la gestion des stocks

### Sécurité
- Patch de sécurité pour l'upload d'images

## [1.8.4] - 2023-06-25

### Ajouté
- Sauvegarde automatique des brouillons
- Historique des modifications client

### Corrigé
- Problème de synchronisation temps réel
- Erreur dans le calcul des remises

## [1.8.3] - 2023-06-01

### Corrigé
- Bug d'affichage des calendriers
- Problème de validation des formulaires
- Erreur de redirection après connexion

### Modifié
- Amélioration des messages d'erreur
- Optimisation du chargement des pages

## [1.8.2] - 2023-05-15

### Ajouté
- Système de commentaires sur les commandes
- Notifications de rappel automatiques

### Corrigé
- Problème d'encodage des caractères spéciaux
- Bug de tri dans les listes

## [1.8.1] - 2023-04-30

### Corrigé
- Correction critique du système de facturation
- Bug de calcul des délais de livraison

### Sécurité
- Mise à jour de sécurité des dépendances

## [1.8.0] - 2023-04-01

### Ajouté
- Gestion des rendez-vous récurrents
- Système de fidélité client
- Intégration calendrier externe (Google, Outlook)
- API webhooks pour les intégrations tierces

### Modifié
- Refonte du système de recherche
- Amélioration des performances générales
- Interface de gestion des stocks simplifiée

### Corrigé
- Problèmes de concurrence dans les réservations
- Bugs d'affichage sur Internet Explorer

### Déprécié
- Ancienne API de recherche (sera supprimée en v2.0)

## [1.7.0] - 2023-02-15

### Ajouté
- Système de templates d'emails personnalisables
- Gestion des promotions et codes de réduction
- Rapport de performance mensuel
- Support des devises multiples

### Modifié
- Amélioration de l'interface mobile
- Optimisation de la base de données
- Mise à jour vers Symfony 6.2

### Corrigé
- Problème de cache sur les mises à jour de stock
- Bug de validation des numéros de téléphone internationaux

## [1.6.0] - 2023-01-10

### Ajouté
- Système de sauvegarde automatique
- Gestion des fournisseurs
- Intégration avec les réseaux sociaux
- Mode hors ligne pour l'application mobile

### Modifié
- Refonte du système de notifications
- Amélioration de la sécurité des mots de passe
- Interface d'administration modernisée

### Corrigé
- Problèmes de synchronisation multi-utilisateurs
- Bugs d'import/export de données

## [1.5.0] - 2022-11-20

### Ajouté
- Première version du système de réservations
- Gestion basique des stocks
- Système de notifications par email
- Interface d'administration

### Modifié
- Migration vers Symfony 6.0
- Amélioration des performances
- Nouvelle charte graphique

### Corrigé
- Multiples corrections de bugs
- Amélioration de la stabilité

## [1.0.0] - 2022-06-01

### Ajouté
- Version initiale d'Ateliya
- Gestion des clients et mesures
- Système d'authentification basique
- Interface web responsive
- API REST de base

---

## Types de changements

- `Ajouté` pour les nouvelles fonctionnalités
- `Modifié` pour les changements dans les fonctionnalités existantes
- `Déprécié` pour les fonctionnalités qui seront supprimées prochainement
- `Supprimé` pour les fonctionnalités supprimées
- `Corrigé` pour les corrections de bugs
- `Sécurité` pour les vulnérabilités corrigées

## Liens

- [Comparer les versions](https://github.com/votre-repo/ateliya/compare)
- [Releases](https://github.com/votre-repo/ateliya/releases)
- [Issues](https://github.com/votre-repo/ateliya/issues)