# Implementation Plan: TypeMesure Category Update

## Overview

This implementation plan standardizes the TypeMesure category update functionality to handle the payload structure `{"libelle": "veste","categories": [{"idCategorie": 4},{"idCategorie": 6}]}` with proper validation, transaction handling, and consistent responses.

## Tasks

- [x] 1. Update controller method signature and payload parsing
  - Modify the update method in ApiTypeMesureController to handle the new payload structure
  - Replace existing payload parsing logic to expect `libelle` and `categories` fields
  - Add proper JSON decoding with error handling
  - _Requirements: 1.1, 1.2, 3.4_

- [x] 1.1 Write property test for payload processing
  - **Property 1: Complete Payload Processing**
  - **Validates: Requirements 1.1, 1.2, 1.3**

- [ ] 2. Implement category association replacement logic
  - [ ] 2.1 Add transaction management for atomic updates
    - Wrap all database operations in a single transaction
    - Ensure rollback on any failure
    - _Requirements: 5.1, 5.2, 5.3_

  - [ ] 2.2 Implement category validation and association replacement
    - Validate all category IDs exist before making changes
    - Remove existing CategorieTypeMesure associations
    - Create new associations for provided categories
    - _Requirements: 2.1, 2.2, 2.3_

- [ ] 2.3 Write property test for category replacement
  - **Property 2: Category Association Replacement**
  - **Validates: Requirements 2.1, 2.2**

- [ ] 2.4 Write property test for transaction atomicity
  - **Property 9: Transaction Atomicity**
  - **Validates: Requirements 5.1, 5.2, 5.3, 5.4**

- [ ] 3. Implement validation and error handling
  - [ ] 3.1 Add category existence validation
    - Check each category ID exists in CategorieMesure repository
    - Return specific error messages for non-existent categories
    - _Requirements: 1.4, 2.3, 3.2_

  - [ ] 3.2 Add empty categories array handling
    - Handle empty categories array by removing all associations
    - _Requirements: 2.4_

  - [ ] 3.3 Add enterprise data isolation
    - Ensure category associations are scoped to user's enterprise
    - _Requirements: 2.5_

- [ ] 3.4 Write property test for invalid category error handling
  - **Property 3: Invalid Category Error Handling**
  - **Validates: Requirements 1.4, 2.3, 3.2**

- [ ] 3.5 Write property test for empty categories handling
  - **Property 4: Empty Categories Handling**
  - **Validates: Requirements 2.4**

- [ ] 3.6 Write property test for enterprise data isolation
  - **Property 5: Enterprise Data Isolation**
  - **Validates: Requirements 2.5**

- [ ] 4. Update response formatting
  - [ ] 4.1 Modify response to include formatted category details
    - Return updated TypeMesure with category information
    - Include id, idCategorie, and libelleCategorie fields for each category
    - Use existing 'group1' serialization group
    - _Requirements: 4.1, 4.2, 4.3_

  - [ ] 4.2 Standardize error responses
    - Ensure consistent error response format for all error scenarios
    - Set appropriate HTTP status codes (400, 404, 403, 500)
    - _Requirements: 4.4, 4.5_

- [ ] 4.3 Write property test for response format
  - **Property 7: Complete Response Format**
  - **Validates: Requirements 4.1, 4.2, 4.3**

- [ ] 4.4 Write property test for HTTP status codes
  - **Property 8: HTTP Status Code Consistency**
  - **Validates: Requirements 4.4, 4.5**

- [ ] 5. Add malformed JSON handling
  - Add try-catch for JSON parsing errors
  - Return appropriate 400 error for malformed JSON
  - _Requirements: 3.4_

- [ ] 5.1 Write property test for malformed JSON handling
  - **Property 6: Malformed JSON Error Handling**
  - **Validates: Requirements 3.4**

- [ ] 6. Update OpenAPI documentation
  - Update the @OA\Post annotation to reflect new payload structure
  - Document the categories array with idCategorie field
  - _Requirements: 1.1, 1.2_

- [ ] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 8. Integration testing and cleanup
  - [ ] 8.1 Test complete update flow with real database
    - Verify transaction behavior with actual database operations
    - Test with various payload combinations
    - _Requirements: All_

  - [ ] 8.2 Remove unused imports and clean up code
    - Remove unused imports identified in the current controller
    - Clean up any deprecated code paths
    - _Requirements: Code quality_

- [ ] 8.3 Write integration tests
  - Test end-to-end update functionality
  - Test subscription validation integration
  - _Requirements: All_

- [ ] 9. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases