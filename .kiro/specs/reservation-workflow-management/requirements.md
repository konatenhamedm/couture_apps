# Requirements Document

## Introduction

Ce document définit les exigences pour l'amélioration du système de gestion des réservations. L'objectif est d'introduire un workflow de confirmation/annulation des réservations avec gestion différée de la déduction des stocks. Actuellement, le stock est déduit immédiatement lors de la création d'une réservation, ce qui pose des problèmes en cas d'annulation ou de non-retrait. Le nouveau système permettra de gérer les réservations avec un état (en attente, confirmée, annulée) et de ne déduire le stock qu'au moment de la confirmation.

## Glossary

- **Reservation_System**: Le système de gestion des réservations de vêtements
- **Reservation**: Une réservation de produits effectuée par un client avec un acompte
- **Reservation_Status**: L'état d'une réservation (en_attente, confirmee, annulee)
- **Stock**: La quantité disponible d'un modèle dans une boutique
- **ModeleBoutique**: Un modèle de vêtement disponible dans une boutique spécifique
- **Confirmation_Process**: Le processus de validation d'une réservation avec vérification et déduction du stock
- **Cancellation_Process**: Le processus d'annulation d'une réservation
- **Stock_Deduction**: La réduction de la quantité disponible en stock
- **User**: Un utilisateur du système (administrateur, gérant de boutique, vendeur)
- **Client**: Un client effectuant une réservation

## Requirements

### Requirement 1: Gestion de l'état des réservations

**User Story:** En tant qu'utilisateur du système, je veux que chaque réservation ait un état clairement défini, afin de suivre son cycle de vie de la création à la finalisation.

#### Acceptance Criteria

1. THE Reservation_System SHALL store a status field for each Reservation with possible values: "en_attente", "confirmee", "annulee"
2. WHEN a new Reservation is created, THE Reservation_System SHALL set the initial status to "en_attente"
3. THE Reservation_System SHALL prevent direct modification of the status field except through dedicated confirmation or cancellation endpoints
4. WHEN retrieving Reservation data, THE Reservation_System SHALL include the status field in the response
5. THE Reservation_System SHALL maintain an audit trail of status changes with timestamp and user information

### Requirement 2: Création de réservation sans déduction immédiate du stock

**User Story:** En tant que vendeur, je veux créer une réservation sans déduire immédiatement le stock, afin de permettre au client de confirmer sa réservation avant de bloquer les articles.

#### Acceptance Criteria

1. WHEN a User creates a new Reservation, THE Reservation_System SHALL NOT deduct quantities from Stock
2. WHEN a User creates a new Reservation, THE Reservation_System SHALL NOT modify the ModeleBoutique quantity
3. WHEN a User creates a new Reservation, THE Reservation_System SHALL NOT modify the global model quantity
4. THE Reservation_System SHALL validate that sufficient Stock exists for the requested quantities without modifying the Stock
5. WHEN a Reservation is created successfully, THE Reservation_System SHALL return the Reservation with status "en_attente"
6. THE Reservation_System SHALL record the acompte payment without affecting Stock levels

### Requirement 3: Confirmation de réservation avec déduction du stock

**User Story:** En tant que vendeur, je veux confirmer une réservation en vérifiant la disponibilité du stock et en déduisant les quantités, afin de finaliser la transaction et bloquer les articles pour le client.

#### Acceptance Criteria

1. WHEN a User confirms a Reservation with status "en_attente", THE Reservation_System SHALL verify that sufficient Stock exists for all reserved items
2. IF sufficient Stock exists, THEN THE Reservation_System SHALL deduct the reserved quantities from ModeleBoutique stock
3. IF sufficient Stock exists, THEN THE Reservation_System SHALL deduct the reserved quantities from global model stock
4. IF sufficient Stock exists, THEN THE Reservation_System SHALL update the Reservation status to "confirmee"
5. IF insufficient Stock exists for any item, THEN THE Reservation_System SHALL return an error message indicating which items have insufficient stock
6. IF insufficient Stock exists, THEN THE Reservation_System SHALL NOT modify any Stock quantities
7. IF insufficient Stock exists, THEN THE Reservation_System SHALL NOT change the Reservation status
8. WHEN a Reservation is confirmed, THE Reservation_System SHALL record the confirmation timestamp and confirming User
9. THE Reservation_System SHALL prevent confirmation of a Reservation that is not in "en_attente" status
10. WHEN a Reservation is confirmed, THE Reservation_System SHALL send a notification to the Client

### Requirement 4: Annulation de réservation

**User Story:** En tant que vendeur ou administrateur, je veux annuler une réservation en attente, afin de libérer la réservation sans affecter le stock puisqu'il n'a pas encore été déduit.

#### Acceptance Criteria

1. WHEN a User cancels a Reservation with status "en_attente", THE Reservation_System SHALL update the status to "annulee"
2. WHEN a User cancels a Reservation with status "en_attente", THE Reservation_System SHALL NOT modify any Stock quantities
3. WHEN a Reservation is cancelled, THE Reservation_System SHALL record the cancellation timestamp and cancelling User
4. THE Reservation_System SHALL prevent cancellation of a Reservation with status "confirmee"
5. THE Reservation_System SHALL prevent cancellation of a Reservation with status "annulee"
6. WHEN a Reservation is cancelled, THE Reservation_System SHALL send a notification to the Client
7. WHEN a Reservation is cancelled, THE Reservation_System SHALL maintain the payment records for accounting purposes

### Requirement 5: API de confirmation de réservation

**User Story:** En tant que développeur frontend, je veux une API pour confirmer une réservation, afin d'intégrer cette fonctionnalité dans l'interface utilisateur.

#### Acceptance Criteria

1. THE Reservation_System SHALL provide a POST endpoint at "/api/reservation/confirm/{id}"
2. WHEN the confirm endpoint is called with a valid Reservation ID, THE Reservation_System SHALL execute the Confirmation_Process
3. WHEN the confirm endpoint is called with an invalid Reservation ID, THE Reservation_System SHALL return a 404 error
4. WHEN the confirm endpoint is called by an unauthenticated User, THE Reservation_System SHALL return a 401 error
5. WHEN the Confirmation_Process succeeds, THE Reservation_System SHALL return a 200 response with the updated Reservation data
6. WHEN the Confirmation_Process fails due to insufficient stock, THE Reservation_System SHALL return a 400 response with detailed error information
7. THE Reservation_System SHALL include OpenAPI documentation for the confirm endpoint

### Requirement 6: API d'annulation de réservation

**User Story:** En tant que développeur frontend, je veux une API pour annuler une réservation, afin d'intégrer cette fonctionnalité dans l'interface utilisateur.

#### Acceptance Criteria

1. THE Reservation_System SHALL provide a POST endpoint at "/api/reservation/cancel/{id}"
2. WHEN the cancel endpoint is called with a valid Reservation ID, THE Reservation_System SHALL execute the Cancellation_Process
3. WHEN the cancel endpoint is called with an invalid Reservation ID, THE Reservation_System SHALL return a 404 error
4. WHEN the cancel endpoint is called by an unauthenticated User, THE Reservation_System SHALL return a 401 error
5. WHEN the Cancellation_Process succeeds, THE Reservation_System SHALL return a 200 response with the updated Reservation data
6. WHEN the Cancellation_Process fails due to invalid status, THE Reservation_System SHALL return a 400 response with an error message
7. THE Reservation_System SHALL include OpenAPI documentation for the cancel endpoint

### Requirement 7: Filtrage des réservations par état

**User Story:** En tant qu'utilisateur, je veux filtrer les réservations par état, afin de visualiser facilement les réservations en attente, confirmées ou annulées.

#### Acceptance Criteria

1. WHEN retrieving Reservations, THE Reservation_System SHALL support filtering by status parameter
2. WHEN a status filter is provided, THE Reservation_System SHALL return only Reservations matching that status
3. WHEN no status filter is provided, THE Reservation_System SHALL return all Reservations
4. THE Reservation_System SHALL support multiple status values in a single filter request
5. WHEN an invalid status value is provided, THE Reservation_System SHALL return a 400 error with a descriptive message

### Requirement 8: Migration des données existantes

**User Story:** En tant qu'administrateur système, je veux que les réservations existantes soient migrées vers le nouveau système avec état, afin de maintenir la cohérence des données historiques.

#### Acceptance Criteria

1. THE Reservation_System SHALL provide a database migration script to add the status field to existing Reservations
2. WHEN the migration runs, THE Reservation_System SHALL set the status of all existing Reservations to "confirmee"
3. WHEN the migration runs, THE Reservation_System SHALL add default values for confirmation timestamp and confirming user
4. THE Reservation_System SHALL ensure the migration is idempotent and can be run multiple times safely
5. THE Reservation_System SHALL log all migration activities for audit purposes

### Requirement 9: Validation de cohérence des données

**User Story:** En tant que système, je veux valider la cohérence entre l'état de la réservation et les déductions de stock, afin de garantir l'intégrité des données.

#### Acceptance Criteria

1. WHEN confirming a Reservation, THE Reservation_System SHALL use database transactions to ensure atomicity
2. IF any Stock_Deduction fails during confirmation, THEN THE Reservation_System SHALL rollback all changes
3. THE Reservation_System SHALL prevent concurrent confirmations of the same Reservation
4. THE Reservation_System SHALL validate that the sum of reserved quantities does not exceed available Stock
5. WHEN a database error occurs during confirmation, THE Reservation_System SHALL rollback the transaction and return an error

### Requirement 10: Notifications et traçabilité

**User Story:** En tant qu'administrateur, je veux être notifié des confirmations et annulations de réservations, afin de suivre l'activité du système.

#### Acceptance Criteria

1. WHEN a Reservation is confirmed, THE Reservation_System SHALL send a notification to the Client with confirmation details
2. WHEN a Reservation is cancelled, THE Reservation_System SHALL send a notification to the Client with cancellation reason
3. WHEN a Reservation status changes, THE Reservation_System SHALL log the change with user, timestamp, and reason
4. THE Reservation_System SHALL maintain a complete audit trail of all Reservation status changes
5. WHEN a Reservation confirmation fails, THE Reservation_System SHALL notify the User with specific error details
