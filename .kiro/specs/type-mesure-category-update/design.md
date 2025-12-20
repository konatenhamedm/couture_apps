# Design Document

## Overview

This design standardizes the TypeMesure category update functionality in the ApiTypeMesureController to handle a consistent payload structure: `{"libelle": "veste","categories": [{"idCategorie": 4},{"idCategorie": 6}]}`. The current implementation uses inconsistent field names and lacks proper transaction handling. This design ensures atomic updates, proper validation, and consistent API responses.

## Architecture

The solution follows the existing Symfony controller pattern with these key components:

- **Controller Layer**: ApiTypeMesureController handles HTTP requests and response formatting
- **Entity Layer**: TypeMesure, CategorieMesure, and CategorieTypeMesure entities manage data relationships
- **Repository Layer**: Doctrine repositories handle database operations
- **Service Layer**: Existing ApiInterface provides common functionality (validation, responses, subscription checking)

## Components and Interfaces

### Updated Controller Method

```php
#[Route('/update/{id}', methods: ['PUT', 'POST'])]
public function update(
    Request $request, 
    TypeMesure $typeMesure, 
    TypeMesureRepository $typeMesureRepository,
    CategorieMesureRepository $categorieMesureRepository,
    CategorieTypeMesureRepository $categorieTypeMesureRepository
): Response
```

### Payload Structure

```json
{
    "libelle": "string",
    "categories": [
        {"idCategorie": "integer"}
    ]
}
```

### Response Structure

```json
{
    "code": 200,
    "message": "Operation effectuée avec succes",
    "data": {
        "id": 1,
        "libelle": "veste",
        "categories": [
            {
                "id": 1,
                "idCategorie": 4,
                "libelleCategorie": "longueur"
            },
            {
                "id": 2,
                "idCategorie": 6,
                "libelleCategorie": "largeur"
            }
        ]
    },
    "errors": []
}
```

## Data Models

### TypeMesure Entity
- **id**: Primary key
- **libelle**: Measurement type name (e.g., "veste", "pantalon")
- **entreprise**: Foreign key to Entreprise
- **categorieTypeMesures**: One-to-many relationship with CategorieTypeMesure

### CategorieMesure Entity
- **id**: Primary key
- **libelle**: Category name (e.g., "longueur", "largeur")
- **entreprise**: Foreign key to Entreprise

### CategorieTypeMesure Entity (Junction Table)
- **id**: Primary key
- **typeMesure**: Foreign key to TypeMesure
- **categorieMesure**: Foreign key to CategorieMesure
- **entreprise**: Foreign key to Entreprise (for data isolation)

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Complete Payload Processing
*For any* valid update request with libelle and categories array containing idCategorie fields, the controller should successfully parse the payload and update both the TypeMesure libelle and replace all associated categories.
**Validates: Requirements 1.1, 1.2, 1.3**

### Property 2: Category Association Replacement
*For any* TypeMesure update with categories, all existing CategorieTypeMesure associations should be removed and replaced with new associations linking to each specified CategorieMesure.
**Validates: Requirements 2.1, 2.2**

### Property 3: Invalid Category Error Handling
*For any* payload containing non-existent category IDs, the system should return a 400 error with specific details and not modify any existing associations.
**Validates: Requirements 1.4, 2.3, 3.2**

### Property 4: Empty Categories Handling
*For any* update with an empty categories array, the system should remove all existing category associations from the TypeMesure.
**Validates: Requirements 2.4**

### Property 5: Enterprise Data Isolation
*For any* category association created, it should be scoped to the current user's Entreprise and not accessible to other enterprises.
**Validates: Requirements 2.5**

### Property 6: Malformed JSON Error Handling
*For any* malformed JSON payload, the API should return a 400 error with parsing details.
**Validates: Requirements 3.4**

### Property 7: Complete Response Format
*For any* successful update, the response should include the updated TypeMesure with category details containing id, idCategorie, and libelleCategorie fields using 'group1' serialization.
**Validates: Requirements 4.1, 4.2, 4.3**

### Property 8: HTTP Status Code Consistency
*For any* response scenario (success, validation error, not found), the API should return appropriate HTTP status codes with standardized error response format.
**Validates: Requirements 4.4, 4.5**

### Property 9: Transaction Atomicity
*For any* update operation, either all changes (libelle and category associations) should be committed together, or all should be rolled back on failure, ensuring referential integrity.
**Validates: Requirements 5.1, 5.2, 5.3, 5.4**

## Error Handling

### Validation Errors
- **404**: TypeMesure not found
- **400**: Invalid category ID
- **400**: Malformed JSON payload
- **403**: Subscription required

### Database Errors
- **500**: Transaction rollback on partial failure
- **500**: Constraint violation errors

### Error Response Format
```json
{
    "code": 400,
    "message": "Validation failed",
    "errors": ["Category 999 not found"]
}
```

## Testing Strategy

### Unit Tests
- Test payload parsing with valid and invalid JSON
- Test category validation with existing and non-existing IDs
- Test subscription checking logic
- Test error response formatting

### Property-Based Tests
- **Property 1**: Generate random valid payloads and verify consistent processing
- **Property 2**: Generate random category sets and verify complete replacement
- **Property 3**: Simulate failures at different points and verify rollback
- **Property 4**: Generate invalid category IDs and verify error handling
- **Property 5**: Generate multi-enterprise scenarios and verify data isolation
- **Property 6**: Generate various update scenarios and verify response format

Each property test should run a minimum of 100 iterations and be tagged with:
**Feature: type-mesure-category-update, Property {number}: {property_text}**

### Integration Tests
- Test complete update flow with database transactions
- Test concurrent update scenarios
- Test subscription validation integration