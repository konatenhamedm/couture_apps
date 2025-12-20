# Requirements Document

## Introduction

This specification defines the standardization of the TypeMesure category update functionality in the ApiTypeMesureController. The current implementation has inconsistent payload handling and needs to be aligned with a clear, standardized API contract for updating TypeMesure entities and their associated categories.

## Glossary

- **TypeMesure**: A measurement type entity (e.g., "veste", "pantalon", "boubou") that defines categories of measurements for clothing items
- **CategorieMesure**: A measurement category entity (e.g., "longueur", "largeur", "hauteur") that defines specific measurement dimensions
- **CategorieTypeMesure**: A junction entity that links TypeMesure with CategorieMesure, scoped by Entreprise
- **API_Controller**: The ApiTypeMesureController that handles HTTP requests for TypeMesure operations
- **Payload**: The JSON request body sent to the update endpoint
- **Entreprise**: The business entity that owns the TypeMesure and its categories

## Requirements

### Requirement 1: Standardized Update Payload

**User Story:** As an API client, I want to update a TypeMesure with a consistent payload structure, so that I can reliably manage measurement types and their categories.

#### Acceptance Criteria

1. WHEN a PUT/POST request is made to `/api/typeMesure/update/{id}`, THE API_Controller SHALL accept a payload with `libelle` and `categories` fields
2. WHEN the payload contains a `categories` array, THE API_Controller SHALL process each category with `idCategorie` field
3. WHEN the payload is valid, THE API_Controller SHALL update the TypeMesure libelle and replace all associated categories
4. WHEN the payload contains invalid category IDs, THE API_Controller SHALL return a validation error with specific details
5. THE API_Controller SHALL maintain backward compatibility during the transition period

### Requirement 2: Category Association Management

**User Story:** As a business user, I want to update the categories associated with a measurement type, so that I can maintain accurate measurement definitions for my clothing items.

#### Acceptance Criteria

1. WHEN categories are provided in the update payload, THE System SHALL remove all existing CategorieTypeMesure associations for the TypeMesure
2. WHEN new categories are specified, THE System SHALL create new CategorieTypeMesure associations linking the TypeMesure to each CategorieMesure
3. WHEN a category ID does not exist, THE System SHALL return an error and not modify any associations
4. WHEN the categories array is empty, THE System SHALL remove all category associations from the TypeMesure
5. THE System SHALL ensure all category associations are scoped to the current user's Entreprise

### Requirement 3: Data Validation and Error Handling

**User Story:** As an API client, I want clear validation and error messages, so that I can handle update failures appropriately.

#### Acceptance Criteria

1. WHEN the TypeMesure ID does not exist, THE API_Controller SHALL return a 404 error with message "TypeMesure not found"
2. WHEN a category ID in the payload does not exist, THE API_Controller SHALL return a 400 error with message "Category {id} not found"
3. WHEN the user lacks subscription access, THE API_Controller SHALL return a 403 error with subscription message
4. WHEN the payload is malformed JSON, THE API_Controller SHALL return a 400 error with parsing details
5. WHEN database operations fail, THE API_Controller SHALL return a 500 error and rollback any partial changes

### Requirement 4: Response Consistency

**User Story:** As an API client, I want consistent response formats, so that I can reliably process update results.

#### Acceptance Criteria

1. WHEN an update succeeds, THE API_Controller SHALL return the updated TypeMesure with associated categories in the response
2. WHEN returning the TypeMesure, THE API_Controller SHALL include category details with id, idCategorie, and libelleCategorie fields
3. THE API_Controller SHALL use the 'group1' serialization group for consistent response formatting
4. WHEN an error occurs, THE API_Controller SHALL return a standardized error response with status code and message
5. THE API_Controller SHALL set appropriate HTTP status codes for all response scenarios

### Requirement 5: Transaction Integrity

**User Story:** As a system administrator, I want atomic updates, so that partial failures don't leave the system in an inconsistent state.

#### Acceptance Criteria

1. WHEN updating categories, THE System SHALL perform all database operations within a single transaction
2. WHEN any category association fails, THE System SHALL rollback all changes including the libelle update
3. WHEN the TypeMesure update succeeds, THE System SHALL commit all category associations together
4. THE System SHALL ensure referential integrity between TypeMesure, CategorieTypeMesure, and CategorieMesure entities
5. WHEN concurrent updates occur, THE System SHALL handle them safely without data corruption