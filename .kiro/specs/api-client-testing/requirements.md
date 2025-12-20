# Requirements Document

## Introduction

Ce document définit les exigences pour tester l'API de gestion des clients (`ApiClientController`) dans le système Ateliya. L'API permet de gérer les clients associés aux boutiques et succursales avec des fonctionnalités CRUD complètes, gestion de photos, et contrôle d'accès basé sur les abonnements et les rôles utilisateur.

## Glossary

- **Client_API**: L'ensemble des endpoints REST pour la gestion des clients dans le contrôleur ApiClientController
- **Client_Entity**: Une entité représentant un client avec nom, prénom, numéro de téléphone, photo optionnelle, et associations avec boutique/succursale
- **Subscription_System**: Le système de vérification des abonnements actifs requis pour accéder aux fonctionnalités
- **Role_Based_Access**: Le système de contrôle d'accès basé sur les types d'utilisateurs (SADM, ADB, etc.)
- **Photo_Upload**: Le système de téléchargement et gestion des photos de clients
- **Environment_Context**: Le contexte d'environnement (entreprise) dans lequel les opérations sont effectuées
- **Pagination_System**: Le système de pagination des résultats de liste
- **Bulk_Operations**: Les opérations en masse comme la suppression multiple

## Requirements

### Requirement 1

**User Story:** En tant que développeur, je veux tester l'endpoint de liste des clients, afin de m'assurer qu'il retourne correctement tous les clients avec pagination.

#### Acceptance Criteria

1. WHEN the Client_API receives a GET request to `/api/client/` THEN the system SHALL return a paginated list of all clients in the environment
2. WHEN the pagination is applied THEN the Client_API SHALL return results in the expected pagination format with proper metadata
3. WHEN an error occurs during retrieval THEN the Client_API SHALL return a 500 status code with appropriate error message
4. WHEN the response is successful THEN the Client_API SHALL include client properties: id, nom, prenom, numero, photo, boutique, succursale, entreprise, createdAt
5. WHEN the response is generated THEN the Client_API SHALL set proper Content-Type headers to application/json

### Requirement 2

**User Story:** En tant qu'utilisateur authentifié, je veux lister les clients selon mes droits d'accès, afin de voir seulement les clients que je suis autorisé à gérer.

#### Acceptance Criteria

1. WHEN a user without active subscription accesses `/api/client/entreprise` THEN the Client_API SHALL return a 403 error with subscription required message
2. WHEN a SADM user accesses the endpoint THEN the Client_API SHALL return all clients of the managed enterprise
3. WHEN an ADB user accesses the endpoint THEN the Client_API SHALL return only clients of their boutique
4. WHEN other user types access the endpoint THEN the Client_API SHALL return only clients of their succursale
5. WHEN the filtering is applied THEN the Client_API SHALL return results ordered by id in ascending order

### Requirement 3

**User Story:** En tant qu'utilisateur, je veux récupérer les détails d'un client spécifique, afin de consulter toutes ses informations.

#### Acceptance Criteria

1. WHEN the Client_API receives a GET request to `/api/client/get/one/{id}` with valid subscription THEN the system SHALL return the client details
2. WHEN the client ID does not exist THEN the Client_API SHALL return a 404 status code with "Cette ressource est inexistante" message
3. WHEN the user lacks active subscription THEN the Client_API SHALL return a 403 error with subscription required message
4. WHEN an exception occurs THEN the Client_API SHALL return a 500 status code with the exception message
5. WHEN the client is found THEN the Client_API SHALL return complete client information including relationships

### Requirement 4

**User Story:** En tant qu'utilisateur, je veux créer un nouveau client pour une succursale, afin d'enregistrer ses informations avec une photo optionnelle.

#### Acceptance Criteria

1. WHEN the Client_API receives a POST request to `/api/client/create` with required fields THEN the system SHALL create a new client
2. WHEN required fields (nom, prenoms, numero) are missing THEN the Client_API SHALL return validation errors
3. WHEN a photo file is uploaded THEN the Client_API SHALL save the file with proper naming convention and path
4. WHEN boutique and succursale IDs are provided THEN the Client_API SHALL associate the client with the corresponding entities
5. WHEN the user lacks active subscription THEN the Client_API SHALL return a 403 error with subscription required message

### Requirement 5

**User Story:** En tant qu'utilisateur, je veux créer un nouveau client pour une boutique, afin d'enregistrer ses informations avec une photo optionnelle.

#### Acceptance Criteria

1. WHEN the Client_API receives a POST request to `/api/client/create/boutique` with required fields THEN the system SHALL create a new client for boutique
2. WHEN required fields (nom, prenoms, numero, boutique) are missing THEN the Client_API SHALL return validation errors
3. WHEN a photo file is uploaded THEN the Client_API SHALL save the file using the document naming convention
4. WHEN boutique and succursale associations are provided THEN the Client_API SHALL properly link the client to these entities
5. WHEN the client is created THEN the Client_API SHALL configure proper trait entity values including managed enterprise

### Requirement 6

**User Story:** En tant qu'utilisateur, je veux mettre à jour les informations d'un client existant, afin de maintenir ses données à jour.

#### Acceptance Criteria

1. WHEN the Client_API receives a PUT/POST request to `/api/client/update/{id}` THEN the system SHALL update the existing client
2. WHEN the client ID does not exist THEN the Client_API SHALL return a 404 status code with appropriate message
3. WHEN optional fields are provided THEN the Client_API SHALL update only the provided fields
4. WHEN a new photo is uploaded THEN the Client_API SHALL replace the existing photo with the new one
5. WHEN the update is successful THEN the Client_API SHALL set updatedAt timestamp and updatedBy user

### Requirement 7

**User Story:** En tant qu'utilisateur, je veux supprimer un client, afin de retirer ses informations du système.

#### Acceptance Criteria

1. WHEN the Client_API receives a DELETE request to `/api/client/delete/{id}` THEN the system SHALL remove the client from environment
2. WHEN the client ID does not exist THEN the Client_API SHALL return a 404 status code with "Cette ressource est inexistante" message
3. WHEN the deletion is successful THEN the Client_API SHALL return success message "Operation effectuées avec succès"
4. WHEN an exception occurs during deletion THEN the Client_API SHALL return a 500 status code with error message
5. WHEN the user lacks active subscription THEN the Client_API SHALL return a 403 error with subscription required message

### Requirement 8

**User Story:** En tant qu'utilisateur, je veux supprimer plusieurs clients en une seule opération, afin d'optimiser la gestion en masse.

#### Acceptance Criteria

1. WHEN the Client_API receives a DELETE request to `/api/client/delete/all/items` with array of IDs THEN the system SHALL delete all specified clients
2. WHEN the request body contains invalid JSON THEN the Client_API SHALL return a 400 status code with appropriate error
3. WHEN some client IDs do not exist THEN the Client_API SHALL skip non-existent clients and continue with valid ones
4. WHEN all deletions are processed THEN the Client_API SHALL return success message "Operation effectuées avec succès"
5. WHEN an exception occurs during bulk deletion THEN the Client_API SHALL return a 500 status code with error message

### Requirement 9

**User Story:** En tant que système, je veux valider l'upload de photos, afin de m'assurer que seuls les fichiers acceptés sont traités.

#### Acceptance Criteria

1. WHEN a photo file is uploaded THEN the Photo_Upload SHALL validate file format and size constraints
2. WHEN the file format is not supported THEN the Photo_Upload SHALL reject the file and return appropriate error
3. WHEN the file is valid THEN the Photo_Upload SHALL generate a unique filename using document prefix and slug
4. WHEN the file is saved THEN the Photo_Upload SHALL store it in the configured upload directory
5. WHEN the upload fails THEN the Photo_Upload SHALL handle the error gracefully without breaking the client creation

### Requirement 10

**User Story:** En tant que système, je veux gérer les erreurs de validation, afin de fournir des messages d'erreur clairs aux utilisateurs.

#### Acceptance Criteria

1. WHEN validation errors occur THEN the Client_API SHALL return structured error responses with field-specific messages
2. WHEN entity constraints are violated THEN the Client_API SHALL return appropriate HTTP status codes
3. WHEN database errors occur THEN the Client_API SHALL return 500 status code with generic error message
4. WHEN authentication fails THEN the Client_API SHALL return 401 status code
5. WHEN authorization fails THEN the Client_API SHALL return 403 status code with specific reason