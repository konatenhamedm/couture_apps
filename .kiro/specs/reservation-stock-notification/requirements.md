# Requirements Document

## Introduction

Cette spécification définit les modifications nécessaires à l'API de création de réservation pour permettre la création de réservations même lorsque les stocks sont insuffisants, tout en notifiant l'administrateur de la situation pour qu'il puisse prendre des décisions de ravitaillement appropriées.

## Glossary

- **Système**: L'application de gestion des réservations et stocks
- **Administrateur**: Utilisateur avec les droits d'administration de l'entreprise (Super-admin)
- **Réservation**: Commande de produits avec acompte et retrait ultérieur
- **Stock_Insuffisant**: Situation où la quantité demandée dépasse la quantité disponible
- **Notification_Push**: Notification en temps réel envoyée via l'application
- **Email_Notification**: Notification envoyée par email
- **Boutique**: Point de vente où les produits sont stockés et retirés

## Requirements

### Requirement 1: Création de réservation avec stock insuffisant

**User Story:** En tant qu'utilisateur de la boutique, je veux pouvoir créer une réservation même si les stocks sont insuffisants, afin de ne pas perdre la vente et permettre à l'administrateur de prendre une décision de ravitaillement.

#### Acceptance Criteria

1. WHEN une réservation est créée avec des quantités supérieures au stock disponible, THE Système SHALL créer la réservation avec un statut spécial
2. WHEN les stocks sont insuffisants lors de la création, THE Système SHALL marquer la réservation comme "en_attente_stock"
3. WHEN une réservation est créée avec stock insuffisant, THE Système SHALL enregistrer les détails des articles en rupture
4. THE Système SHALL permettre la création de réservations indépendamment du niveau de stock disponible
5. WHEN une réservation est créée, THE Système SHALL calculer et enregistrer le déficit de stock pour chaque article

### Requirement 2: Notification automatique à l'administrateur

**User Story:** En tant qu'administrateur, je veux être notifié immédiatement lorsqu'une réservation est créée avec des stocks insuffisants, afin de pouvoir prendre rapidement des décisions de ravitaillement.

#### Acceptance Criteria

1. WHEN une réservation est créée avec stock insuffisant, THE Système SHALL envoyer une notification push à l'administrateur
2. WHEN une réservation est créée avec stock insuffisant, THE Système SHALL envoyer un email détaillé à l'administrateur
3. THE Email_Notification SHALL contenir les détails de la réservation et les articles en rupture
4. THE Email_Notification SHALL inclure les quantités demandées versus disponibles pour chaque article
5. THE Notification_Push SHALL indiquer clairement qu'une action de ravitaillement est nécessaire
6. WHEN plusieurs articles sont en rupture, THE Système SHALL lister tous les articles concernés dans la notification

### Requirement 3: Contenu détaillé des notifications

**User Story:** En tant qu'administrateur, je veux recevoir des informations complètes sur les stocks insuffisants, afin de pouvoir identifier précisément quels articles ravitailler et en quelle quantité.

#### Acceptance Criteria

1. THE Email_Notification SHALL inclure le nom de la boutique concernée
2. THE Email_Notification SHALL inclure les informations du client (nom, téléphone)
3. THE Email_Notification SHALL lister chaque article avec quantité demandée et stock disponible
4. THE Email_Notification SHALL calculer et afficher le déficit total par article
5. THE Email_Notification SHALL inclure la date de retrait prévue de la réservation
6. THE Email_Notification SHALL inclure l'utilisateur qui a créé la réservation
7. THE Notification_Push SHALL inclure un résumé concis avec le nombre d'articles en rupture

### Requirement 4: Gestion des statuts de réservation

**User Story:** En tant qu'utilisateur du système, je veux que les réservations avec stock insuffisant soient clairement identifiées, afin de pouvoir les traiter différemment des réservations normales.

#### Acceptance Criteria

1. WHEN une réservation est créée avec stock suffisant, THE Système SHALL assigner le statut "en_attente"
2. WHEN une réservation est créée avec stock insuffisant, THE Système SHALL assigner le statut "en_attente_stock"
3. THE Système SHALL permettre la transition du statut "en_attente_stock" vers "en_attente" après ravitaillement
4. WHEN le stock est reconstitué, THE Système SHALL permettre la confirmation de la réservation
5. THE Système SHALL maintenir un historique des changements de statut pour traçabilité

### Requirement 5: Préservation de la logique existante

**User Story:** En tant qu'utilisateur du système, je veux que les fonctionnalités existantes continuent de fonctionner normalement, afin d'assurer la continuité du service.

#### Acceptance Criteria

1. WHEN une réservation a un stock suffisant, THE Système SHALL fonctionner exactement comme avant
2. THE Système SHALL continuer à valider tous les autres champs requis (client, boutique, montants)
3. THE Système SHALL continuer à gérer les paiements d'acompte normalement
4. THE Système SHALL continuer à envoyer les notifications de réservation standard
5. WHEN une réservation est confirmée, THE Système SHALL déduire le stock comme précédemment

### Requirement 6: Gestion des erreurs et logging

**User Story:** En tant qu'administrateur système, je veux que les erreurs de notification soient gérées proprement, afin que l'échec d'envoi de notification n'empêche pas la création de réservation.

#### Acceptance Criteria

1. WHEN l'envoi de notification échoue, THE Système SHALL créer quand même la réservation
2. THE Système SHALL logger les erreurs d'envoi de notification pour diagnostic
3. WHEN l'email ne peut pas être envoyé, THE Système SHALL tenter d'envoyer uniquement la notification push
4. THE Système SHALL continuer le processus de création même si les notifications échouent
5. WHEN les notifications échouent, THE Système SHALL enregistrer l'événement pour retry ultérieur